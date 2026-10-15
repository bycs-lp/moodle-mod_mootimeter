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

namespace mod_mootimeter\external;

use advanced_testcase;
use mod_mootimeter\helper;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the mod_mootimeter web services.
 *
 * @package     mod_mootimeter
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(get_pages::class)]
#[CoversClass(get_page::class)]
#[CoversClass(get_state::class)]
#[CoversClass(add_page::class)]
#[CoversClass(set_page_visibility::class)]
#[CoversClass(store_setting::class)]
#[CoversClass(delete_answers::class)]
final class services_test extends advanced_testcase {
    /** @var \stdClass */
    private $course;
    /** @var \stdClass */
    private $module;
    /** @var \stdClass */
    private $teacher;
    /** @var \stdClass */
    private $student;
    /** @var \mod_mootimeter_generator */
    private $mtmgenerator;

    /**
     * Create a course with a mootimeter, a teacher and a student.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->course = $this->getDataGenerator()->create_course();
        $this->module = $this->getDataGenerator()->create_module('mootimeter', ['course' => $this->course->id]);
        $this->teacher = $this->getDataGenerator()->create_and_enrol($this->course, 'editingteacher');
        $this->student = $this->getDataGenerator()->create_and_enrol($this->course, 'student');
        $this->mtmgenerator = $this->getDataGenerator()->get_plugin_generator('mod_mootimeter');
    }

    /**
     * Create a wordcloud page as teacher.
     *
     * @param bool $visible
     * @return \stdClass
     */
    private function create_page(bool $visible): \stdClass {
        global $DB;
        $this->setUser($this->teacher);
        $page = $this->mtmgenerator->create_page(['instance' => $this->module->id, 'tool' => 'wordcloud']);
        $DB->set_field('mootimeter_pages', 'visible', $visible ? 1 : 0, ['id' => $page->id]);
        return $DB->get_record('mootimeter_pages', ['id' => $page->id]);
    }

    /**
     * Participants only see visible pages and no tools.
     */
    public function test_get_pages(): void {
        $visible = $this->create_page(true);
        $hidden = $this->create_page(false);

        $result = get_pages::execute($this->module->cmid);
        $this->assertEquals([$visible->id, $hidden->id], array_column($result['pages'], 'id'));
        $this->assertNotEmpty($result['tools']);

        $this->setUser($this->student);
        $result = get_pages::execute($this->module->cmid);
        $this->assertEquals([$visible->id], array_column($result['pages'], 'id'));
        $this->assertEmpty($result['tools']);
    }

    /**
     * Participants can not load hidden pages and do not get the raw question.
     */
    public function test_get_page(): void {
        $visible = $this->create_page(true);
        $hidden = $this->create_page(false);
        (new helper())->set_tool_config($visible, 'question', 'What?');

        $this->setUser($this->student);
        $result = get_page::execute($visible->id);
        $this->assertEquals('', $result['question']);
        $this->assertEquals('What?', $result['questionhtml']);
        $this->assertFalse($result['resultsvisible']);

        $this->expectException(\moodle_exception::class);
        get_page::execute($hidden->id);
    }

    /**
     * The state of a page of another instance is not reported.
     */
    public function test_get_state_ignores_foreign_page(): void {
        $this->setAdminUser();
        $othermodule = $this->getDataGenerator()->create_module('mootimeter', ['course' => $this->course->id]);
        $foreignpage = $this->mtmgenerator->create_page(['instance' => $othermodule->id]);
        $ownpage = $this->create_page(true);

        $this->setUser($this->student);
        $result = get_state::execute($this->module->cmid, $foreignpage->id);
        $this->assertEquals(0, $result['settings']);
        $this->assertEquals(0, $result['answers']);
        $this->assertGreaterThan(0, $result['pagelist']);

        $result = get_state::execute($this->module->cmid, $ownpage->id);
        $this->assertGreaterThan(0, $result['settings']);
    }

    /**
     * Only enabled tools can be added, and only by moderators.
     */
    public function test_add_page(): void {
        global $DB;
        $this->setUser($this->teacher);
        $result = add_page::execute($this->module->cmid, 'quiz');
        $this->assertTrue($DB->record_exists('mootimeter_pages', ['id' => $result['pageid'], 'tool' => 'quiz']));

        try {
            add_page::execute($this->module->cmid, 'unknown');
            $this->fail('An unknown tool was accepted.');
        } catch (\invalid_parameter_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
        }

        $this->setUser($this->student);
        $this->expectException(\required_capability_exception::class);
        add_page::execute($this->module->cmid, 'quiz');
    }

    /**
     * Only moderators can change the visibility of a page.
     */
    public function test_set_page_visibility(): void {
        global $DB;
        $page = $this->create_page(false);
        set_page_visibility::execute($page->id, true);
        $this->assertEquals(1, $DB->get_field('mootimeter_pages', 'visible', ['id' => $page->id]));

        $this->setUser($this->student);
        $this->expectException(\required_capability_exception::class);
        set_page_visibility::execute($page->id, false);
    }

    /**
     * Only known settings are stored and their values are cleaned.
     */
    public function test_store_setting(): void {
        $page = $this->create_page(true);

        $this->assertEquals('3', store_setting::execute($page->id, 'maxinputsperuser', '3')['value']);
        $this->assertEquals('0', store_setting::execute($page->id, 'maxinputsperuser', '-5')['value']);
        $this->assertEquals('1', store_setting::execute($page->id, 'allowduplicateanswers', 'yes')['value']);

        try {
            store_setting::execute($page->id, 'maxanswersperuser', '2');
            $this->fail('A setting of another tool was accepted.');
        } catch (\invalid_parameter_exception $e) {
            $this->assertInstanceOf(\invalid_parameter_exception::class, $e);
        }

        $this->setUser($this->student);
        $this->expectException(\required_capability_exception::class);
        store_setting::execute($page->id, 'question', 'Hacked');
    }

    /**
     * Participants may only delete their own answers.
     */
    public function test_delete_answers(): void {
        global $DB;
        $page = $this->create_page(true);
        /** @var \mootimetertool_wordcloud_generator $toolgenerator */
        $toolgenerator = $this->getDataGenerator()->get_plugin_generator('mootimetertool_wordcloud');
        $own = $toolgenerator->create_answer($page->id, $this->student->id, 'mine');
        $other = $toolgenerator->create_answer($page->id, $this->teacher->id, 'other');

        $this->setUser($this->student);
        delete_answers::execute($page->id, delete_answers::SCOPE_SINGLE, $own);
        $this->assertFalse($DB->record_exists('mootimetertool_wordcloud_answers', ['id' => $own]));

        try {
            delete_answers::execute($page->id, delete_answers::SCOPE_SINGLE, $other);
            $this->fail('A participant deleted an answer of another user.');
        } catch (\required_capability_exception $e) {
            $this->assertInstanceOf(\required_capability_exception::class, $e);
        }
        try {
            delete_answers::execute($page->id, delete_answers::SCOPE_ALL);
            $this->fail('A participant deleted all answers.');
        } catch (\required_capability_exception $e) {
            $this->assertInstanceOf(\required_capability_exception::class, $e);
        }
        $this->assertTrue($DB->record_exists('mootimetertool_wordcloud_answers', ['id' => $other]));

        $this->setUser($this->teacher);
        delete_answers::execute($page->id, delete_answers::SCOPE_ALL);
        $this->assertFalse($DB->record_exists('mootimetertool_wordcloud_answers', ['pageid' => $page->id]));
    }
}
