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

namespace mootimetertool_wordcloud\external;

use advanced_testcase;
use mod_mootimeter\helper;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the wordcloud web services.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(get_results::class)]
#[CoversClass(store_answer::class)]
#[CoversClass(update_answer::class)]
final class wordcloud_services_test extends advanced_testcase {
    /** @var \mootimetertool_wordcloud_generator */
    private $toolgenerator;

    /**
     * Set up the generator.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->toolgenerator = $this->getDataGenerator()->get_plugin_generator('mootimetertool_wordcloud');
    }

    /**
     * Create a released wordcloud page with one answer of a teacher and a student.
     *
     * @param int $visible page visibility
     * @return array [page, student, teacher, answerid]
     */
    private function setup_page(int $visible): array {
        global $DB;
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->toolgenerator->create_wordcloud_page($course);
        $DB->set_field('mootimeter_pages', 'visible', $visible, ['id' => $page->id]);
        (new helper())->set_tool_config($page->id, 'showonteacherpermission', '1');
        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $answerid = $this->toolgenerator->create_answer($page->id, $teacher->id, 'secretword');
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        return [$page, $student, $teacher, $answerid];
    }

    /**
     * A student cannot read the answers of a hidden page.
     */
    public function test_hidden_page_answers_are_not_returned(): void {
        [$page, $student] = $this->setup_page(helper::PAGE_UNVISIBLE);
        $this->setUser($student);

        $this->expectException(\moodle_exception::class);
        get_results::execute($page->id);
    }

    /**
     * A student reads the answers of a visible and released page.
     */
    public function test_visible_page_answers_are_returned(): void {
        [$page, $student] = $this->setup_page(helper::PAGE_VISIBLE);
        $this->setUser($student);

        $result = get_results::execute($page->id);

        $this->assertTrue($result['visible']);
        $this->assertEquals([['text' => 'secretword', 'count' => 1]], $result['words']);
    }

    /**
     * Empty, duplicate and too many answers are rejected.
     */
    public function test_store_answer_validation(): void {
        [$page, $student] = $this->setup_page(helper::PAGE_VISIBLE);
        $this->setAdminUser();
        (new helper())->set_tool_config($page->id, 'maxinputsperuser', '2');
        $this->setUser($student);

        $this->assertEquals(helper::ERRORCODE_EMPTY_ANSWER, store_answer::execute($page->id, '  ')['code']);
        $this->assertEquals(helper::ERRORCODE_OK, store_answer::execute($page->id, 'Apple')['code']);
        $this->assertEquals(helper::ERRORCODE_DUPLICATE_ANSWER, store_answer::execute($page->id, 'apple')['code']);
        $this->assertEquals(helper::ERRORCODE_OK, store_answer::execute($page->id, 'Pear')['code']);
        $this->assertEquals(helper::ERRORCODE_TO_MANY_ANSWERS, store_answer::execute($page->id, 'Plum')['code']);
    }

    /**
     * Only moderators of the page may change an answer.
     */
    public function test_update_answer_requires_moderator(): void {
        global $DB;
        [$page, $student, $teacher, $answerid] = $this->setup_page(helper::PAGE_VISIBLE);

        $this->setUser($student);
        try {
            update_answer::execute($page->id, $answerid, 'falsified');
            $this->fail('A participant was allowed to change an answer.');
        } catch (\required_capability_exception $e) {
            $this->assertInstanceOf(\required_capability_exception::class, $e);
        }
        $this->assertEquals('secretword', $DB->get_field('mootimetertool_wordcloud_answers', 'answer', ['id' => $answerid]));

        $this->setUser($teacher);
        update_answer::execute($page->id, $answerid, 'corrected');
        $this->assertEquals('corrected', $DB->get_field('mootimetertool_wordcloud_answers', 'answer', ['id' => $answerid]));
    }

    /**
     * A moderator of course A must not be able to change an answer of course B.
     */
    public function test_update_answer_cross_course(): void {
        global $DB;
        [, , , $victimanswerid] = $this->setup_page(helper::PAGE_VISIBLE);

        $coursea = $this->getDataGenerator()->create_course();
        $pagea = $this->toolgenerator->create_wordcloud_page($coursea);
        $attacker = $this->getDataGenerator()->create_and_enrol($coursea, 'editingteacher');
        $this->setUser($attacker);

        try {
            update_answer::execute($pagea->id, $victimanswerid, 'falsified');
            $this->fail('A moderator of another course was allowed to change a foreign answer.');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\moodle_exception::class, $e);
        }
        $this->assertEquals('secretword', $DB->get_field('mootimetertool_wordcloud_answers', 'answer', ['id' => $victimanswerid]));
    }
}
