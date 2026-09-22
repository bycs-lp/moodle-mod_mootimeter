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
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(\mootimetertool_wordcloud\local\inplace_edit_answer::class, 'update')]
/**
 * Tests for the inplace edit of wordcloud answers.
 *
 * These cover the authorisation of the {@see \core_external::update_inplace_editable()}
 * web-service callback, which is the real write path (the capability flag passed to the
 * renderer in the constructor only controls whether the edit link is drawn).
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2026 ISB Bayern
 * @author      Johannes Funk
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mootimetertool_wordcloud\local\inplace_edit_answer::update
 */
final class inplace_edit_answer_test extends advanced_testcase {
    /**
     * A moderator of course A must not be able to overwrite a wordcloud answer in course B
     * by combining their own page id with a foreign answer id.
     *
     * Exercises the answer row being loaded by id alone, uncorrelated with the validated page.
     */
    public function test_update_cross_course_answer(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/lib/external/externallib.php');

        $this->resetAfterTest();

        $toolgenerator = $this->getDataGenerator()->get_plugin_generator('mootimetertool_wordcloud');

        // Course A: the attacker is teacher here and owns a wordcloud.
        $coursea = $this->getDataGenerator()->create_course();
        // Setting the admin user for convenience to be able to create a page. Later on the correct user will be
        // set for proper capabilities testing.
        $this->setAdminUser();
        $pagea = $toolgenerator->create_wordcloud_page($coursea);

        // Course B: the attacker has no role at all here.
        $courseb = $this->getDataGenerator()->create_course();
        $pageb = $toolgenerator->create_wordcloud_page($courseb);
        $victim = $this->getDataGenerator()->create_and_enrol($courseb, 'student');
        $victimanswerid = $toolgenerator->create_answer($pageb->id, $victim->id, 'original answer');

        $attacker = $this->getDataGenerator()->create_and_enrol($coursea, 'editingteacher');
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
