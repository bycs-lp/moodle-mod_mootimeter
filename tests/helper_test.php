<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_mootimeter;

use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the helper of mod_mootimeter.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(helper::class)]
final class helper_test extends \advanced_testcase {
    /** @var string Question used in the tests. */
    const QUESTION = 'Is this a test question?';

    /**
     * Create a mootimeter instance.
     *
     * @return \stdClass the module record with cmid
     */
    private function create_mootimeter(): \stdClass {
        $course = $this->getDataGenerator()->create_course();
        return $this->getDataGenerator()->create_module('mootimeter', ['course' => $course->id]);
    }

    /**
     * Get the generator of mod_mootimeter.
     *
     * @return \mod_mootimeter_generator
     */
    private function get_generator(): \mod_mootimeter_generator {
        return $this->getDataGenerator()->get_plugin_generator('mod_mootimeter');
    }

    /**
     * Creating a page appends it to the page list and runs the tool hook.
     */
    public function test_create_page(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $mootimeter = $this->create_mootimeter();
        $helper = new helper();
        $cm = helper::get_cm_by_instance($mootimeter->id);

        set_config('default_new_page_visibility', 0, 'mod_mootimeter');
        $first = $helper->create_page($cm, 'wordcloud');
        set_config('default_new_page_visibility', 1, 'mod_mootimeter');
        $second = $helper->create_page($cm, 'quiz');

        $this->assertEquals(0, $first->sortorder);
        $this->assertEquals(helper::PAGE_UNVISIBLE, $first->visible);
        $this->assertEquals(1, $second->sortorder);
        $this->assertEquals(helper::PAGE_VISIBLE, $second->visible);
        $this->assertSame([$first->id, $second->id], array_keys($helper->get_pages($mootimeter->id)));
        $this->assertSame([$second->id], array_keys($helper->get_pages($mootimeter->id, true)));

        // The quiz hook creates two empty answer options.
        $this->assertEquals(2, $DB->count_records('mootimetertool_quiz_options', ['pageid' => $second->id]));
    }

    /**
     * An unknown tool is rejected.
     */
    public function test_get_toolhelper_unknown_tool(): void {
        $this->expectException(\core\exception\coding_exception::class);
        helper::get_toolhelper('unknown');
    }

    /**
     * The installed tools are listed.
     */
    public function test_get_installed_tools(): void {
        $tools = helper::get_installed_tools();
        foreach (['quiz', 'poll', 'wordcloud'] as $tool) {
            $this->assertContains($tool, $tools);
        }
    }

    /**
     * Pages can be moved to any position, the other pages keep their order.
     */
    public function test_move_page(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $mootimeter = $this->create_mootimeter();
        $helper = new helper();
        $pages = [];
        for ($i = 0; $i < 4; $i++) {
            $pages[] = $this->get_generator()->create_page(['instance' => $mootimeter->id]);
        }
        $order = fn(): array => array_map('intval', array_keys($helper->get_pages($mootimeter->id)));
        $ids = array_map(fn($page) => (int) $page->id, $pages);

        $helper->move_page($helper->get_page($ids[2]), 0);
        $this->assertSame([$ids[2], $ids[0], $ids[1], $ids[3]], $order());

        $helper->move_page($helper->get_page($ids[2]), 3);
        $this->assertSame([$ids[0], $ids[1], $ids[3], $ids[2]], $order());

        $helper->move_page($helper->get_page($ids[2]), 1);
        $this->assertSame([$ids[0], $ids[2], $ids[1], $ids[3]], $order());

        // Positions outside the list are clamped.
        $helper->move_page($helper->get_page($ids[0]), 99);
        $this->assertSame([$ids[2], $ids[1], $ids[3], $ids[0]], $order());
        $this->assertSame([0, 1, 2, 3], array_map(
            fn($page) => (int) $page->sortorder,
            array_values($helper->get_pages($mootimeter->id))
        ));
    }

    /**
     * Deleting a page removes its settings, options and answers.
     */
    public function test_delete_page(): void {
        global $DB;
        $this->resetAfterTest();
        $this->setAdminUser();

        $mootimeter = $this->create_mootimeter();
        $helper = new helper();
        $page = $this->get_generator()->create_page([
            'instance' => $mootimeter->id,
            'tool' => 'quiz',
            'question' => self::QUESTION,
        ]);
        $other = $this->get_generator()->create_page(['instance' => $mootimeter->id, 'tool' => 'quiz']);
        $options = array_keys((new \mootimetertool_quiz\quiz())->get_answer_options($page->id));
        (new \mootimetertool_quiz\quiz())->submit_response($page, [$options[0]]);

        $helper->delete_page($page);

        $this->assertNull($helper->get_page($page->id));
        $this->assertFalse($DB->record_exists('mootimeter_tool_settings', ['pageid' => $page->id]));
        $this->assertFalse($DB->record_exists('mootimetertool_quiz_options', ['pageid' => $page->id]));
        $this->assertFalse($DB->record_exists('mootimetertool_quiz_answers', ['pageid' => $page->id]));
        $this->assertNotNull($helper->get_page($other->id));
        $this->assertEquals(2, $DB->count_records('mootimetertool_quiz_options', ['pageid' => $other->id]));
    }

    /**
     * Settings are stored per page and default values are returned for missing settings.
     */
    public function test_set_get_tool_config(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $mootimeter = $this->create_mootimeter();
        $helper = new helper();
        $page = $this->get_generator()->create_page(['instance' => $mootimeter->id]);

        $this->assertSame('default', helper::get_tool_config($page, 'question', 'default'));

        $helper->set_tool_config($page, 'question', self::QUESTION);
        $this->assertSame(self::QUESTION, helper::get_tool_config($page, 'question'));
        $this->assertSame(self::QUESTION, helper::get_tool_config((int) $page->id, 'question'));

        $helper->set_tool_config($page, 'question', self::QUESTION . '2');
        $this->assertSame(self::QUESTION . '2', helper::get_tool_config($page, 'question'));
    }

    /**
     * Answers are stored for the current user and optionally replace the previous answers.
     */
    public function test_store_answer(): void {
        $this->resetAfterTest();

        $mootimeter = $this->create_mootimeter();
        $helper = new helper();
        $this->setAdminUser();
        $page = $this->get_generator()->create_page(['instance' => $mootimeter->id]);
        $table = 'mootimetertool_wordcloud_answers';
        $answer = fn(string $text) => (object) ['pageid' => $page->id, 'answer' => $text];

        $user = $this->getDataGenerator()->create_user();
        $this->setUser($user);
        $this->assertFalse($helper->has_answers($page));

        $helper->store_answer($table, [$answer('one'), $answer('two')]);
        $this->assertCount(2, $helper->get_answers($table, $page->id));
        $this->assertTrue($helper->has_answers($page));

        $helper->store_answer($table, [$answer('three')], true);
        $useranswers = $helper->get_user_answers($table, $page->id, 'answer', $user->id);
        $this->assertSame(['three'], array_keys($useranswers));

        $helper->store_answer($table, [$answer('three')]);
        $this->assertSame(['three' => 2], $helper->get_answers_grouped($table, $page->id, 'answer'));
    }

    /**
     * Answers without a page are rejected.
     */
    public function test_store_answer_without_page(): void {
        $this->expectException(\core\exception\coding_exception::class);
        (new helper())->store_answer('mootimetertool_wordcloud_answers', [(object) ['answer' => 'Test']]);
    }

    /**
     * Answers can be deleted completely or per user.
     */
    public function test_delete_answers(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $mootimeter = $this->create_mootimeter();
        $helper = new helper();
        $page = $this->get_generator()->create_page(['instance' => $mootimeter->id]);
        $table = 'mootimetertool_wordcloud_answers';
        $users = [$this->getDataGenerator()->create_user(), $this->getDataGenerator()->create_user()];
        foreach ($users as $user) {
            $this->setUser($user);
            $helper->store_answer($table, [(object) ['pageid' => $page->id, 'answer' => 'Test']]);
        }

        $helper->delete_answers($page, ['usermodified' => $users[0]->id]);
        $this->assertSame([(int) $users[1]->id], array_map(
            fn($answer) => (int) $answer->usermodified,
            array_values($helper->get_answers($table, $page->id))
        ));

        $helper->delete_all_answers($page);
        $this->assertFalse($helper->has_answers($page));
    }

    /**
     * Changes get a new revision.
     */
    public function test_notify_data_changed(): void {
        $this->resetAfterTest();

        $clock = $this->mock_clock_with_frozen(1000);
        $helper = new helper();

        $helper->notify_data_changed(5, helper::CHANGE_ANSWERS);
        $this->assertEquals(1000000, $helper->get_data_changed(5, helper::CHANGE_ANSWERS));

        $clock->bump(2);
        $helper->notify_data_changed(5, helper::CHANGE_ANSWERS);
        $this->assertEquals(1002000, $helper->get_data_changed(5, helper::CHANGE_ANSWERS));

        // Unknown data gets the current revision.
        $this->assertEquals(1002000, $helper->get_data_changed(6, helper::CHANGE_SETTINGS));
    }

    /**
     * Participants only see the results once they are published.
     */
    public function test_can_view_results(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $mootimeter = $this->create_mootimeter();
        $helper = new helper();
        $page = $this->get_generator()->create_page(['instance' => $mootimeter->id]);

        $this->assertTrue($helper->can_view_results($page, true));
        $this->assertFalse($helper->can_view_results($page, false));

        $helper->set_tool_config($page, 'showonteacherpermission', '1');
        $this->assertTrue($helper->can_view_results($page, false));
    }
}
