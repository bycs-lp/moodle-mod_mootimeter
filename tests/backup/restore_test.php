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

use mod_mootimeter\helper;
use PHPUnit\Framework\Attributes\CoversClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/backup/util/includes/restore_includes.php');
require_once($CFG->dirroot . '/mod/mootimeter/backup/moodle2/restore_mootimeter_stepslib.php');

/**
 * Tests for the restore of mootimeter activities.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(\restore_mootimeter_activity_structure_step::class)]
final class restore_test extends \advanced_testcase {
    /**
     * A restored activity starts with open submissions and keeps the other settings.
     */
    public function test_restore_opens_submission(): void {
        $this->resetAfterTest();
        $this->setAdminUser();

        $course = $this->getDataGenerator()->create_course();
        $mootimeter = $this->getDataGenerator()->create_module('mootimeter', ['course' => $course->id]);
        $helper = new helper();
        $page = $this->getDataGenerator()->get_plugin_generator('mod_mootimeter')->create_page([
            'instance' => $mootimeter->id,
            'question' => 'Question',
        ]);
        $helper->set_submission_open($page, false);

        $newcm = \core_courseformat\formatactions::cm($course)->duplicate((int) $mootimeter->cmid);

        $newpage = current($helper->get_pages((int) $newcm->instance));
        $this->assertTrue($helper->is_submission_open($newpage));
        $this->assertSame('Question', helper::get_tool_config($newpage, 'question'));
    }
}
