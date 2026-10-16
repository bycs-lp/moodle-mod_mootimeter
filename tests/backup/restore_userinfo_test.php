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

namespace mod_mootimeter\backup;

/**
 * Tests that restoring a mootimeter honours the userinfo setting for answers.
 *
 * @package     mod_mootimeter
 * @copyright   2026 ISB Bayern
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class restore_userinfo_test extends \advanced_testcase {
    /** @var array Answer tables of the tools */
    private const ANSWER_TABLES = [
        'poll' => 'mootimetertool_poll_answers',
        'quiz' => 'mootimetertool_quiz_answers',
        'wordcloud' => 'mootimetertool_wordcloud_answers',
    ];

    #[\PHPUnit\Framework\Attributes\Group('baseline')]
    /**
     * Answers must not be restored when the restore excludes user data.
     *
     * @covers \restore_mootimetertool_poll_subplugin
     * @covers \restore_mootimetertool_quiz_subplugin
     * @covers \restore_mootimetertool_wordcloud_subplugin
     */
    public function test_restore_without_userinfo_skips_answers(): void {
        $counts = $this->backup_and_restore(false);
        $this->assertSame(['poll' => 0, 'quiz' => 0, 'wordcloud' => 0], $counts);
    }

    #[\PHPUnit\Framework\Attributes\Group('baseline')]
    /**
     * Answers are restored when the restore includes user data.
     *
     * @covers \restore_mootimetertool_poll_subplugin
     * @covers \restore_mootimetertool_quiz_subplugin
     * @covers \restore_mootimetertool_wordcloud_subplugin
     */
    public function test_restore_with_userinfo_keeps_answers(): void {
        $counts = $this->backup_and_restore(true);
        $this->assertSame(['poll' => 1, 'quiz' => 1, 'wordcloud' => 1], $counts);
    }

    /**
     * Back up a course with one answer per tool and restore it into a new course.
     *
     * @param bool $restoreuserinfo
     * @return array number of restored answers per tool
     */
    private function backup_and_restore(bool $restoreuserinfo): array {
        global $CFG, $DB, $USER;
        require_once($CFG->dirroot . '/backup/util/includes/backup_includes.php');
        require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');

        $this->resetAfterTest();
        $this->setAdminUser();
        $CFG->backup_file_logger_level = \backup::LOG_NONE;

        $course = $this->getDataGenerator()->create_course();
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $module = $this->getDataGenerator()->create_module('mootimeter', ['course' => $course->id]);
        $generator = $this->getDataGenerator()->get_plugin_generator('mod_mootimeter');

        foreach (self::ANSWER_TABLES as $tool => $table) {
            $page = $generator->create_page(['instance' => $module->id, 'tool' => $tool]);
            $answer = ['pageid' => $page->id, 'usermodified' => $student->id, 'timecreated' => time(), 'timemodified' => time()];
            if ($tool === 'wordcloud') {
                $answer['answer'] = 'Answer';
            } else {
                $answer['optionid'] = $DB->get_field("mootimetertool_{$tool}_options", 'MIN(id)', ['pageid' => $page->id]);
            }
            $DB->insert_record($table, (object) $answer);
        }

        $bc = new \backup_controller(
            \backup::TYPE_1COURSE,
            $course->id,
            \backup::FORMAT_MOODLE,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id
        );
        $bc->get_plan()->get_setting('users')->set_value(true);
        $bc->execute_plan();
        $backupid = $bc->get_backupid();
        $bc->get_results()['backup_destination']->extract_to_pathname(
            get_file_packer('application/vnd.moodle.backup'),
            make_backup_temp_directory($backupid)
        );
        $bc->destroy();

        $newcourseid = \restore_dbops::create_new_course('Restored', 'RESTORED', $course->category);
        $rc = new \restore_controller(
            $backupid,
            $newcourseid,
            \backup::INTERACTIVE_NO,
            \backup::MODE_GENERAL,
            $USER->id,
            \backup::TARGET_NEW_COURSE
        );
        $rc->get_plan()->get_setting('users')->set_value($restoreuserinfo);
        $this->assertTrue($rc->execute_precheck());
        $rc->execute_plan();
        $rc->destroy();

        $instanceid = $DB->get_field('mootimeter', 'id', ['course' => $newcourseid], MUST_EXIST);
        $pageids = $DB->get_fieldset('mootimeter_pages', 'id', ['instance' => $instanceid]);
        [$insql, $params] = $DB->get_in_or_equal($pageids);
        $counts = [];
        foreach (self::ANSWER_TABLES as $tool => $table) {
            $counts[$tool] = $DB->count_records_select($table, "pageid $insql", $params);
        }
        return $counts;
    }
}
