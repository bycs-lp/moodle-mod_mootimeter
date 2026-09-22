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

namespace mootimetertool_quiz;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversMethod;

#[CoversMethod(\mootimetertool_quiz\local\inplace_edit_answer::class, 'update')]
/**
 * Tests for the inplace edit of quiz answers.
 *
 * These cover the authorisation of the {@see \core_external::update_inplace_editable()}
 * web-service callback, which is the real write path (the capability flag passed to the
 * renderer in the constructor only controls whether the edit link is drawn).
 *
 * @package     mootimetertool_quiz
 * @copyright   2026 ISB Bayern
 * @author      Philipp Memmel
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mootimetertool_quiz\local\inplace_edit_answer::update
 */
final class inplace_edit_answer_test extends advanced_testcase {
    /**
     * A moderator of course A must not be able to touch a quiz answer of course B
     * by combining their own page id with a foreign answer id.
     *
     * Exercises the answer row being loaded by id alone, uncorrelated with the validated page.
     */
    public function test_update_cross_course_answer(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/lib/external/externallib.php');

        $this->resetAfterTest();

        /** @var \mootimetertool_quiz_generator $toolgenerator */
        $toolgenerator = $this->getDataGenerator()->get_plugin_generator('mootimetertool_quiz');

        // Course A: the attacker is teacher here and owns a quiz.
        $coursea = $this->getDataGenerator()->create_course();
        // Setting the admin user for convenience to be able to create a page. Later on the correct user will be
        // set for proper capabilities testing.
        $this->setAdminUser();
        $pagea = $toolgenerator->create_quiz_page($coursea);
        $optionaid = $toolgenerator->get_answer_option_ids($pagea->id)[0];

        // Course B: the attacker has no role at all here.
        $courseb = $this->getDataGenerator()->create_course();
        $pageb = $toolgenerator->create_quiz_page($courseb);
        $optionbid = $toolgenerator->get_answer_option_ids($pageb->id)[0];
        $victim = $this->getDataGenerator()->create_and_enrol($courseb, 'student');
        $victimanswerid = $toolgenerator->create_answer($pageb->id, $victim->id, $optionbid);

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
            \core_external::update_inplace_editable(
                'mootimeter',
                'quiz_editanswerselect',
                $itemid,
                json_encode([$optionaid])
            );
            $this->fail('A moderator of another course was allowed to use a foreign answer.');
        } catch (\moodle_exception $e) {
            // Expected once the row lookup is bound to the validated page.
            $this->assertInstanceOf(\moodle_exception::class, $e);
        }

        $this->assertEquals(
            $optionbid,
            $DB->get_field('mootimetertool_quiz_answers', 'optionid', ['id' => $victimanswerid]),
            'Answer in a foreign course was modified via a mismatched page id.'
        );
        $this->assertEquals(
            0,
            $DB->count_records('mootimetertool_quiz_answers', ['pageid' => $pagea->id]),
            'An answer was created on behalf of a foreign user via a mismatched page id.'
        );
    }

    /**
     * A moderator may edit an answer that actually belongs to the given page.
     */
    public function test_update_own_page_answer(): void {
        global $CFG, $DB;
        require_once($CFG->dirroot . '/lib/external/externallib.php');

        $this->resetAfterTest();

        /** @var \mootimetertool_quiz_generator $toolgenerator */
        $toolgenerator = $this->getDataGenerator()->get_plugin_generator('mootimetertool_quiz');

        $course = $this->getDataGenerator()->create_course();
        // Setting the admin user for convenience to be able to create a page. Later on the correct user will be
        // set for proper capabilities testing.
        $this->setAdminUser();
        $page = $toolgenerator->create_quiz_page($course);
        [$firstoptionid, $secondoptionid] = $toolgenerator->get_answer_option_ids($page->id);

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $answerid = $toolgenerator->create_answer($page->id, $student->id, $firstoptionid);

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        \core_external::update_inplace_editable(
            'mootimeter',
            'quiz_editanswerselect',
            $page->id . '_' . $answerid,
            json_encode([$secondoptionid])
        );

        $this->assertEquals(
            $secondoptionid,
            $DB->get_field('mootimetertool_quiz_answers', 'optionid', ['id' => $answerid]),
            'A moderator was not able to edit an answer of their own page.'
        );
    }
}
