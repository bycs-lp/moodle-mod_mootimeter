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

/**
 * Test for deleting mootimeter instances when user loses moderator capability
 *
 * @package     mod_mootimeter
 * @copyright   2026, ISB Bayern
 * @author      Andreas Wagner
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter;

use advanced_testcase;
use context_module;
use context_course;

/**
 * Test for deleting mootimeter instances when user loses moderator capability
 *
 * @package     mod_mootimeter
 * @copyright   2026, ISB Bayern
 * @author      Andreas Wagner
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class delete_instance_test extends advanced_testcase {
    /**
     * Test that async deletion succeeds when teacher loses moderator capability
     *
     * This test simulates the scenario where:
     * 1. A teacher deletes a mootimeter instance (triggering async deletion)
     * 2. The teacher is then unenrolled from the course
     * 3. The adhoc task course_delete_modules runs and should succeed
     *
     * @covers \mootimeter_delete_instance
     * @covers \mod_mootimeter\helper::delete_page()
     * @return void
     */
    public function test_async_delete_suceeds_when_teacher_loses_capability(): void {
        global $DB, $CFG;

        require_once($CFG->dirroot . '/course/lib.php');

        $this->resetAfterTest(true);
        $this->setAdminUser();

        // Create course and teacher user.
        $generator = $this->getDataGenerator();
        $course = $generator->create_course();

        // Create teacher with only teacher role.
        $teacher = $generator->create_user([
            'username' => 'lois.lane.async',
            'firstname' => 'Lois',
            'lastname' => 'Lane',
        ]);

        // Create mootimeter instance.
        $mootimeter = $generator->create_module('mootimeter', [
            'course' => $course->id,
            'name' => 'Test Mootimeter for Async Deletion',
        ]);

        // Enrol teacher in course with teacher role.
        $generator->enrol_user($teacher->id, $course->id, 'editingteacher');

        // Get course module and context.
        $cm = get_coursemodule_from_instance('mootimeter', $mootimeter->id);

        // Add at least one page to the mootimeter instance.
        $mtmgenerator = $generator->get_plugin_generator('mod_mootimeter');
        $page = $mtmgenerator->create_page($this, [
            'instance' => $mootimeter->id,
            'tool' => 'wordcloud',
            'title' => 'Test Page for Async Delete',
        ]);

        // Verify page was created.
        $this->assertTrue($DB->record_exists('mootimeter_pages', ['id' => $page->id]));

        // Set user as teacher and trigger async deletion.
        $this->setUser($teacher);

        // Trigger async deletion by calling course_delete_module().
        course_delete_module($cm->id, true);

        // Verify that the adhoc task was created.
        $tasks = $DB->get_records('task_adhoc', ['classname' => '\core_course\task\course_delete_modules']);
        $this->assertCount(1, $tasks);

        // Verify module is marked for deletion.
        $cm = $DB->get_record('course_modules', ['id' => $cm->id]);
        $this->assertEquals(1, $cm->deletioninprogress);

        // Now unenrol the teacher from the course (simulating teacher leaving course).
        $this->setAdminUser();

        // Unenrol from course.
        $enrol = $DB->get_record('enrol', ['courseid' => $course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $enrolplugin = enrol_get_plugin('manual');
        $enrolplugin->unenrol_user($enrol, $teacher->id);

        accesslib_clear_all_caches_for_unit_testing();
        rebuild_course_cache($course->id, true);

        // Verify teacher no longer has access to the module context.
        $this->setUser($teacher);
        $modulecontext = context_module::instance($cm->id);
        $this->assertFalse(has_capability('mod/mootimeter:moderator', $modulecontext));

        // Now execute the adhoc task - it should succeed.
        $this->setAdminUser();
        $task = array_shift($tasks);
        $adhoctask = \core\task\manager::adhoc_task_from_record($task);
        $adhoctask->execute();

        // Verify course modul was deleted.
        $this->assertFalse(
            $DB->record_exists('course_modules', ['id' => $cm->id]),
            'Course module was not deleted.'
        );
        $this->assertFalse(
            $DB->record_exists('mootimeter', ['id' => $mootimeter->id]),
            'Mootimeter instance was not deleted.'
        );
        $this->assertFalse(
            $DB->record_exists('mootimeter_pages', ['id' => $page->id]),
            'Mootimeter page was not deleted.'
        );
    }
}
