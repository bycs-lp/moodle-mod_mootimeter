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

namespace mootimetertool_wordcloud;

use advanced_testcase;
use stdClass;

defined('MOODLE_INTERNAL') || die();

global $CFG;
require_once($CFG->dirroot . '/lib/external/externallib.php');

/**
 * Tests for the inplace edit of wordcloud answers.
 *
 * These cover the authorisation of the {@see \core_external::update_inplace_editable()}
 * web-service callback, which is the real write path (the capability flag passed to the
 * renderer in the constructor only controls whether the edit link is drawn).
 *
 * @package     mootimetertool_wordcloud
 * @copyright   Johannes Funk, 2026 ISB Bayern
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mootimetertool_wordcloud\local\inplace_edit_answer::update
 */
final class inplace_edit_answer_test extends advanced_testcase {
    /**
     * Create a wordcloud page inside a fresh mootimeter activity in the given course.
     *
     * @param stdClass $course
     * @return stdClass the page record
     */
    private function create_wordcloud_page(stdClass $course): stdClass {
        global $DB;
        $generator = $this->getDataGenerator();
        /** @var \mod_mootimeter_generator $mtmgenerator */
        $mtmgenerator = $generator->get_plugin_generator('mod_mootimeter');
        $module = $generator->create_module('mootimeter', ['course' => $course->id]);
        $page = $mtmgenerator->create_page($this, ['instance' => $module->id, 'tool' => 'wordcloud']);
        // Pages are created hidden by default; reveal it so participants (non-moderators)
        // can actually reach it, mirroring a page a teacher has opened for answering.
        $DB->set_field('mootimeter_pages', 'visible', \mod_mootimeter\helper::PAGE_VISIBLE, ['id' => $page->id]);
        $page->visible = \mod_mootimeter\helper::PAGE_VISIBLE;
        // Keep the cmid handy for context lookups.
        $page->cmid = $module->cmid;
        return $page;
    }

    /**
     * Store a wordcloud answer on a page and return its id.
     *
     * @param int $pageid
     * @param int $userid submitter
     * @param string $answer
     * @return int the answer id
     */
    private function create_answer(int $pageid, int $userid, string $answer): int {
        global $DB;
        return (int) $DB->insert_record('mootimetertool_wordcloud_answers', (object) [
            'pageid' => $pageid,
            'usermodified' => $userid,
            'answer' => $answer,
            'timecreated' => time(),
            'timemodified' => time(),
        ]);
    }

    /**
     * A moderator of course A must not be able to overwrite a wordcloud answer in course B
     * by combining their own page id with a foreign answer id.
     *
     * Exercises the answer row being loaded by id alone, uncorrelated with the validated page.
     */
    public function test_update_cross_course_answer(): void {
        global $DB;
        $this->resetAfterTest();

        $generator = $this->getDataGenerator();

        // Course A: the attacker is teacher here and owns a wordcloud.
        $coursea = $generator->create_course();
        $pagea = $this->create_wordcloud_page($coursea);

        // Course B: the attacker has no role at all here.
        $courseb = $generator->create_course();
        $pageb = $this->create_wordcloud_page($courseb);
        $victim = $generator->create_and_enrol($courseb, 'student');
        $victimanswerid = $this->create_answer($pageb->id, $victim->id, 'original answer');

        $attacker = $generator->create_and_enrol($coursea, 'editingteacher');
        // Attacker is a moderator in A, but holds nothing in B.
        $this->assertTrue(has_capability(
            'mod/mootimeter:moderator',
            \context_module::instance($pagea->cmid),
            $attacker
        ));
        $this->assertFalse(has_capability(
            'mod/mootimeter:moderator',
            \context_module::instance($pageb->cmid),
            $attacker
        ));
        $this->setUser($attacker);

        // Own page id + foreign answer id.
        $itemid = $pagea->id . '_' . $victimanswerid;
        try {
            \core_external::update_inplace_editable('mootimeter', 'wordcloud_editanswer', $itemid, 'falsified');
            $this->fail('A moderator of another course was allowed to update a foreign answer.');
        } catch (\moodle_exception $e) {
            // Expected once the row lookup is bound to the validated page.
            $this->assertInstanceOf(\moodle_exception::class, $e);
        }

        $this->assertEquals(
            'original answer',
            $DB->get_field('mootimetertool_wordcloud_answers', 'answer', ['id' => $victimanswerid]),
            'Answer in a foreign course was overwritten via a mismatched page id.'
        );
    }
}
