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
 * Tests for resetting mootimeter user data during a course reset.
 *
 * @package     mod_mootimeter
 * @copyright   2026, ISB Bayern
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter;

use advanced_testcase;
use context_module;
use PHPUnit\Framework\Attributes\CoversFunction;
use PHPUnit\Framework\Attributes\CoversMethod;

/**
 * Tests for resetting mootimeter user data during a course reset.
 *
 * @package     mod_mootimeter
 * @copyright   2026, ISB Bayern
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversFunction('mootimeter_reset_userdata')]
#[CoversMethod(toolhelper::class, 'reset_userdata')]
#[CoversMethod(helper::class, 'delete_page_answers')]
final class reset_userdata_test extends advanced_testcase {
    /**
     * A course reset must delete all answers of the course, even if the current user
     * has no moderator capability (e.g. cron, CLI or a user who was unenrolled before the reset).
     *
     * Answers on hidden pages and in hidden activities must be deleted as well,
     * answers in other courses must not be touched.
     *
     * @return void
     */
    public function test_reset_userdata_without_moderator_capability(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/mootimeter/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        /** @var \mootimetertool_wordcloud_generator $wcgenerator */
        $wcgenerator = $generator->get_plugin_generator('mootimetertool_wordcloud');
        /** @var \mod_mootimeter_generator $mtmgenerator */
        $mtmgenerator = $generator->get_plugin_generator('mod_mootimeter');

        $course = $generator->create_course();
        $othercourse = $generator->create_course();
        $student = $generator->create_user();

        // Visible page with answers.
        $visiblepage = $wcgenerator->create_wordcloud_page($course);
        $wcgenerator->create_answer($visiblepage->id, $student->id, 'visible 1');
        $wcgenerator->create_answer($visiblepage->id, $student->id, 'visible 2');

        // Hidden page in the same activity.
        $hiddenpage = $mtmgenerator->create_page([
            'instance' => $visiblepage->instance,
            'tool' => 'wordcloud',
            'visible' => helper::PAGE_UNVISIBLE,
        ]);
        $wcgenerator->create_answer($hiddenpage->id, $student->id, 'hidden page');

        // Visible page in a hidden activity.
        $hiddenactivitypage = $wcgenerator->create_wordcloud_page($course);
        set_coursemodule_visible($hiddenactivitypage->cmid, 0);
        $wcgenerator->create_answer($hiddenactivitypage->id, $student->id, 'hidden activity');

        // Answers in another course must survive.
        $otherpage = $wcgenerator->create_wordcloud_page($othercourse);
        $wcgenerator->create_answer($otherpage->id, $student->id, 'other course');

        // The user running the reset is not enrolled and has no moderator capability,
        // like a teachSHARE author who was unenrolled right before the reset.
        $resetuser = $generator->create_user();
        $this->setUser($resetuser);
        $this->assertFalse(
            has_capability('mod/mootimeter:moderator', context_module::instance($visiblepage->cmid))
        );

        $status = mootimeter_reset_userdata((object) [
            'id' => $course->id,
            'courseid' => $course->id,
            'reset_mootimetertool_wordcloud_answers' => 1,
        ]);

        $this->assertCount(1, $status);
        $this->assertFalse($status[0]['error']);

        $coursepageids = [$visiblepage->id, $hiddenpage->id, $hiddenactivitypage->id];
        [$insql, $params] = $DB->get_in_or_equal($coursepageids);
        $this->assertEquals(0, $DB->count_records_select('mootimetertool_wordcloud_answers', "pageid $insql", $params));
        $this->assertEquals(1, $DB->count_records('mootimetertool_wordcloud_answers', ['pageid' => $otherpage->id]));
    }

    /**
     * Answers are kept when the reset option of the tool is not selected.
     *
     * @return void
     */
    public function test_reset_userdata_option_not_selected(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/mod/mootimeter/lib.php');

        $this->resetAfterTest();
        $this->setAdminUser();

        $generator = $this->getDataGenerator();
        $wcgenerator = $generator->get_plugin_generator('mootimetertool_wordcloud');

        $course = $generator->create_course();
        $page = $wcgenerator->create_wordcloud_page($course);
        $wcgenerator->create_answer($page->id, get_admin()->id, 'keep me');

        $status = mootimeter_reset_userdata((object) ['id' => $course->id, 'courseid' => $course->id]);

        $this->assertSame([], $status);
        $this->assertEquals(1, $DB->count_records('mootimetertool_wordcloud_answers', ['pageid' => $page->id]));
    }
}
