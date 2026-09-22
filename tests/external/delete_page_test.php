<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

namespace mod_mootimeter\external;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(delete_page::class, 'execute')]
/**
 * Tests for the delete_page web service.
 *
 * These cover the authorisation of the deletion, which must only be possible for users
 * holding the moderator capability in the context of the mootimeter instance.
 *
 * @package     mod_mootimeter
 * @copyright   2026 ISB Bayern
 * @author      Philipp Memmel
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mod_mootimeter\external\delete_page::execute
 */
final class delete_page_test extends advanced_testcase {
    /**
     * A participant without the moderator capability must not be able to delete a page.
     */
    public function test_execute_without_moderator_capability(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('mootimeter', ['course' => $course->id]);

        // Setting the admin user for convenience to be able to create a page. Later on the correct user will be
        // set for proper capabilities testing.
        $this->setAdminUser();
        /** @var \mod_mootimeter_generator $mtmgenerator */
        $mtmgenerator = $this->getDataGenerator()->get_plugin_generator('mod_mootimeter');
        $page = $mtmgenerator->create_page(['instance' => $module->id, 'tool' => 'wordcloud']);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->assertFalse(has_capability(
            'mod/mootimeter:moderator',
            \context_module::instance($module->cmid),
            $student
        ));
        $this->setUser($student);

        try {
            delete_page::execute($page->id);
            $this->fail('A user without the moderator capability was allowed to delete a page.');
        } catch (\required_capability_exception $e) {
            $this->assertInstanceOf(\required_capability_exception::class, $e);
        }

        $this->assertTrue(
            $DB->record_exists('mootimeter_pages', ['id' => $page->id]),
            'Page was deleted by a user without the moderator capability.'
        );
    }

    /**
     * A moderator of another mootimeter instance must not be able to delete a foreign page.
     */
    public function test_execute_moderator_of_other_course(): void {
        global $DB;
        $this->resetAfterTest();

        $this->setAdminUser();
        /** @var \mod_mootimeter_generator $mtmgenerator */
        $mtmgenerator = $this->getDataGenerator()->get_plugin_generator('mod_mootimeter');

        // Course A: the attacker is a moderator here.
        $coursea = $this->getDataGenerator()->create_course();
        $modulea = $this->getDataGenerator()->create_module('mootimeter', ['course' => $coursea->id]);

        // Course B: the attacker is only a participant, so they must not delete anything here.
        $courseb = $this->getDataGenerator()->create_course();
        $moduleb = $this->getDataGenerator()->create_module('mootimeter', ['course' => $courseb->id]);
        $pageb = $mtmgenerator->create_page(['instance' => $moduleb->id, 'tool' => 'wordcloud']);

        $attacker = $this->getDataGenerator()->create_and_enrol($coursea, 'editingteacher');
        $this->getDataGenerator()->enrol_user($attacker->id, $courseb->id, 'student');
        $this->assertTrue(has_capability(
            'mod/mootimeter:moderator',
            \context_module::instance($modulea->cmid),
            $attacker
        ));
        $this->assertFalse(has_capability(
            'mod/mootimeter:moderator',
            \context_module::instance($moduleb->cmid),
            $attacker
        ));
        $this->setUser($attacker);

        try {
            delete_page::execute($pageb->id);
            $this->fail('A moderator of another course was allowed to delete a foreign page.');
        } catch (\required_capability_exception $e) {
            $this->assertInstanceOf(\required_capability_exception::class, $e);
        }

        $this->assertTrue(
            $DB->record_exists('mootimeter_pages', ['id' => $pageb->id]),
            'Page of a foreign course was deleted by a user without the moderator capability.'
        );
    }

    /**
     * A moderator of the instance is allowed to delete a page.
     */
    public function test_execute_with_moderator_capability(): void {
        global $DB;
        $this->resetAfterTest();

        $course = $this->getDataGenerator()->create_course();
        $module = $this->getDataGenerator()->create_module('mootimeter', ['course' => $course->id]);

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        /** @var \mod_mootimeter_generator $mtmgenerator */
        $mtmgenerator = $this->getDataGenerator()->get_plugin_generator('mod_mootimeter');
        $page = $mtmgenerator->create_page(['instance' => $module->id, 'tool' => 'wordcloud']);

        $result = delete_page::execute($page->id);

        $this->assertEquals(200, $result['code']);
        $this->assertEquals($module->cmid, $result['cmid']);
        $this->assertFalse($DB->record_exists('mootimeter_pages', ['id' => $page->id]));
    }
}
