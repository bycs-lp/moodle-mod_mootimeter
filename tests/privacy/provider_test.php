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

namespace mod_mootimeter\privacy;

use core\context\module as context_module;
use core_privacy\local\request\approved_contextlist;
use core_privacy\local\request\approved_userlist;
use core_privacy\local\request\userlist;
use core_privacy\local\request\writer;
use core_privacy\tests\provider_testcase;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the privacy providers of mod_mootimeter and its tools.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(provider::class)]
#[CoversClass(\mootimetertool_quiz\privacy\provider::class)]
#[CoversClass(\mootimetertool_poll\privacy\provider::class)]
#[CoversClass(\mootimetertool_wordcloud\privacy\provider::class)]
final class provider_test extends provider_testcase {
    /** @var string Name of the mootimeter instance. */
    const INSTANCE_NAME = 'Mootimeter privacy';

    /** @var \stdClass[] Pages by tool. */
    private array $pages = [];

    /** @var \stdClass[] Options of the quiz and poll pages by tool. */
    private array $options = [];

    /** @var \stdClass[] Users. */
    private array $users = [];

    /** @var context_module Context of the mootimeter instance. */
    private context_module $context;

    #[\Override]
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        /** @var \mod_mootimeter_generator $mtmgenerator */
        $mtmgenerator = $generator->get_plugin_generator('mod_mootimeter');
        $course = $generator->create_course();
        $mootimeter = $generator->create_module('mootimeter', ['course' => $course->id, 'name' => self::INSTANCE_NAME]);
        $this->context = context_module::instance($mootimeter->cmid);

        $this->setAdminUser();
        foreach (['quiz', 'poll', 'wordcloud'] as $tool) {
            $this->pages[$tool] = $mtmgenerator->create_page(['instance' => $mootimeter->id, 'tool' => $tool]);
            if ($tool !== 'wordcloud') {
                $toolhelper = \mod_mootimeter\helper::get_toolhelper($tool);
                $toolhelper->update_settings($this->pages[$tool], ['maxAnswers' => 0]);
                $toolhelper->replace_options($this->pages[$tool], [['text' => "$tool option 1"], ['text' => "$tool option 2"]]);
                $this->options[$tool] = array_values($toolhelper->get_answer_options($this->pages[$tool]->id));
            }
        }

        foreach (['student1', 'student2', 'student3'] as $name) {
            $this->users[$name] = $generator->create_and_enrol($course, 'student');
        }
    }

    /**
     * Answer all pages as a user.
     *
     * @param \stdClass $user
     * @param bool $bothoptions whether both options are selected
     */
    private function answer_all_pages(\stdClass $user, bool $bothoptions = false): void {
        $this->setUser($user);
        foreach (['quiz', 'poll'] as $tool) {
            $optionids = array_map(fn($option) => $option->id, $this->options[$tool]);
            \mod_mootimeter\helper::get_toolhelper($tool)->submit_response(
                $this->pages[$tool],
                $bothoptions ? $optionids : [$optionids[0]]
            );
        }
        $wordcloud = new \mootimetertool_wordcloud\wordcloud();
        $wordcloud->submit_answer($this->pages['wordcloud'], 'Answer 1');
        $wordcloud->submit_answer($this->pages['wordcloud'], 'Answer 2');
        $this->setAdminUser();
    }

    /**
     * Count the answers of a user on all pages.
     *
     * @param \stdClass $user
     * @return int
     */
    private function count_answers(\stdClass $user): int {
        global $DB;
        $count = 0;
        foreach ($this->pages as $tool => $page) {
            $count += $DB->count_records("mootimetertool_{$tool}_answers", ['pageid' => $page->id, 'usermodified' => $user->id]);
        }
        return $count;
    }

    /**
     * Only contexts with answers of the user are returned.
     */
    public function test_get_contexts_for_userid(): void {
        $this->assertEmpty(provider::get_contexts_for_userid($this->users['student1']->id)->get_contextids());

        $this->answer_all_pages($this->users['student1']);

        $this->assertEquals(
            [$this->context->id],
            array_values(array_unique(provider::get_contexts_for_userid($this->users['student1']->id)->get_contextids()))
        );
        $this->assertEmpty(provider::get_contexts_for_userid($this->users['student2']->id)->get_contextids());
    }

    /**
     * All users with answers are returned.
     */
    public function test_get_users_in_context(): void {
        $this->answer_all_pages($this->users['student1']);
        $this->answer_all_pages($this->users['student2']);

        $userlist = new userlist($this->context, 'mod_mootimeter');
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();

        $this->assertContainsEquals($this->users['student1']->id, $userids);
        $this->assertContainsEquals($this->users['student2']->id, $userids);
        $this->assertNotContainsEquals($this->users['student3']->id, $userids);
    }

    /**
     * The answers of the user are exported per page.
     */
    public function test_export_user_data(): void {
        $this->answer_all_pages($this->users['student1'], true);
        $this->answer_all_pages($this->users['student2']);

        $approvedlist = new approved_contextlist($this->users['student1'], 'mod_mootimeter', [$this->context->id]);
        provider::export_user_data($approvedlist);

        $writer = writer::with_context($this->context);
        $this->assertEquals(self::INSTANCE_NAME, $writer->get_data()->name);

        foreach (['quiz', 'poll'] as $tool) {
            $pagepath = get_string('privacy:pagepath', 'mod_mootimeter', $this->pages[$tool]->id);
            foreach ($this->options[$tool] as $index => $option) {
                $answerspath = get_string('privacy:answerspath', "mootimetertool_$tool") . '_' . ($index + 1);
                $data = $writer->get_data([$pagepath, $answerspath]);
                $this->assertEquals($option->id, $data->answeroptionid);
                $this->assertEquals($option->optiontext, $data->answer);
            }
        }

        $pagepath = get_string('privacy:pagepath', 'mod_mootimeter', $this->pages['wordcloud']->id);
        $answerspath = get_string('privacy:answerspath', 'mootimetertool_wordcloud');
        $this->assertEquals('Answer 1', $writer->get_data([$pagepath, $answerspath . '_1'])->scalar);
        $this->assertEquals('Answer 2', $writer->get_data([$pagepath, $answerspath . '_2'])->scalar);
    }

    /**
     * All answers of the context are deleted.
     */
    public function test_delete_data_for_all_users_in_context(): void {
        $this->answer_all_pages($this->users['student1']);
        $this->answer_all_pages($this->users['student2']);

        provider::delete_data_for_all_users_in_context($this->context);

        $this->assertSame(0, $this->count_answers($this->users['student1']));
        $this->assertSame(0, $this->count_answers($this->users['student2']));
    }

    /**
     * Only the answers of the user are deleted.
     */
    public function test_delete_data_for_user(): void {
        $this->answer_all_pages($this->users['student1']);
        $this->answer_all_pages($this->users['student2']);

        provider::delete_data_for_user(new approved_contextlist($this->users['student1'], 'mod_mootimeter', [$this->context->id]));

        $this->assertSame(0, $this->count_answers($this->users['student1']));
        $this->assertSame(4, $this->count_answers($this->users['student2']));
    }

    /**
     * Only the answers of the listed users are deleted.
     */
    public function test_delete_data_for_users(): void {
        $this->answer_all_pages($this->users['student1']);
        $this->answer_all_pages($this->users['student2']);
        $this->answer_all_pages($this->users['student3']);

        provider::delete_data_for_users(new approved_userlist(
            $this->context,
            'mod_mootimeter',
            [$this->users['student1']->id, $this->users['student2']->id]
        ));

        $this->assertSame(0, $this->count_answers($this->users['student1']));
        $this->assertSame(0, $this->count_answers($this->users['student2']));
        $this->assertSame(4, $this->count_answers($this->users['student3']));
    }
}
