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

namespace mootimetertool_quiz\external;

use advanced_testcase;
use mod_mootimeter\helper;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * Tests for the quiz web services.
 *
 * @package     mootimetertool_quiz
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[CoversClass(store_answer::class)]
#[CoversClass(update_option::class)]
#[CoversClass(update_user_answer::class)]
#[CoversClass(get_page_data::class)]
#[CoversClass(get_results::class)]
final class quiz_services_test extends advanced_testcase {
    /** @var \mootimetertool_quiz_generator */
    private $toolgenerator;

    /**
     * Set up the generator.
     */
    protected function setUp(): void {
        parent::setUp();
        $this->resetAfterTest();
        $this->toolgenerator = $this->getDataGenerator()->get_plugin_generator('mootimetertool_quiz');
    }

    /**
     * A participant stores an answer, too many options and closed pages are rejected.
     */
    public function test_store_answer(): void {
        global $DB;
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->toolgenerator->create_quiz_page($course);
        [$first, $second] = $this->toolgenerator->get_answer_option_ids($page->id);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        $result = store_answer::execute($page->id, [$first, $second]);
        $this->assertEquals(helper::ERRORCODE_TO_MANY_ANSWERS, $result['code']);

        $result = store_answer::execute($page->id, []);
        $this->assertEquals(helper::ERRORCODE_EMPTY_ANSWER, $result['code']);

        $result = store_answer::execute($page->id, [$second]);
        $this->assertEquals(helper::ERRORCODE_OK, $result['code']);
        $this->assertEquals(
            [$second],
            array_values($DB->get_fieldset('mootimetertool_quiz_answers', 'optionid', ['pageid' => $page->id]))
        );

        $data = get_page_data::execute($page->id);
        $this->assertEquals([$second], $data['selected']);
        $this->assertFalse($data['options'][0]['iscorrect']);
        $this->assertEquals('', $data['options'][0]['text']);

        $this->setAdminUser();
        (new helper())->set_tool_config($page, 'showanswercorrection', '1');
        $this->setUser($student);
        $result = store_answer::execute($page->id, [$first]);
        $this->assertEquals(helper::ERRORCODE_ANSWERING_CLOSED, $result['code']);
    }

    /**
     * Options of another page are ignored when storing an answer.
     */
    public function test_store_answer_ignores_foreign_option(): void {
        global $DB;
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->toolgenerator->create_quiz_page($course);
        $otherpage = $this->toolgenerator->create_quiz_page($course);
        $foreignoption = $this->toolgenerator->get_answer_option_ids($otherpage->id)[0];
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);

        store_answer::execute($page->id, [$foreignoption]);

        $this->assertEquals(0, $DB->count_records('mootimetertool_quiz_answers', ['optionid' => $foreignoption]));
    }

    /**
     * Results are only returned once the teacher released them.
     */
    public function test_get_results_requires_permission(): void {
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->toolgenerator->create_quiz_page($course);
        $optionid = $this->toolgenerator->get_answer_option_ids($page->id)[0];
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->toolgenerator->create_answer($page->id, $student->id, $optionid);
        $this->setUser($student);

        $result = get_results::execute($page->id);
        $this->assertFalse($result['visible']);
        $this->assertEmpty($result['values']);

        $this->setAdminUser();
        (new helper())->set_tool_config($page, 'showonteacherpermission', '1');
        $this->setUser($student);
        $result = get_results::execute($page->id);
        $this->assertTrue($result['visible']);
        $this->assertEquals([1, 0], $result['values']);
    }

    /**
     * A moderator of course A must not be able to overwrite an answer option of course B
     * by combining their own page id with a foreign answer option id.
     */
    public function test_update_foreign_option_is_rejected(): void {
        global $DB;
        $this->setAdminUser();
        $coursea = $this->getDataGenerator()->create_course();
        $pagea = $this->toolgenerator->create_quiz_page($coursea);
        $courseb = $this->getDataGenerator()->create_course();
        $pageb = $this->toolgenerator->create_quiz_page($courseb);
        $optionbid = $this->toolgenerator->get_answer_option_ids($pageb->id)[0];
        $before = $DB->get_record('mootimetertool_quiz_options', ['id' => $optionbid]);

        $attacker = $this->getDataGenerator()->create_and_enrol($coursea, 'editingteacher');
        $this->setUser($attacker);

        try {
            update_option::execute($pagea->id, $optionbid, 'gone', false);
            $this->fail('A foreign answer option was updated.');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\moodle_exception::class, $e);
        }

        $after = $DB->get_record('mootimetertool_quiz_options', ['id' => $optionbid]);
        $this->assertEquals($before, $after);
    }

    /**
     * A moderator may change an answer option of their own page, a participant may not.
     */
    public function test_update_own_option(): void {
        global $DB;
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->toolgenerator->create_quiz_page($course);
        $optionid = $this->toolgenerator->get_answer_option_ids($page->id)[0];

        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $this->setUser($student);
        try {
            update_option::execute($page->id, $optionid, 'Student', true);
            $this->fail('A participant was allowed to change an answer option.');
        } catch (\required_capability_exception $e) {
            $this->assertInstanceOf(\required_capability_exception::class, $e);
        }

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        update_option::execute($page->id, $optionid, 'Renamed', true);

        $after = $DB->get_record('mootimetertool_quiz_options', ['id' => $optionid]);
        $this->assertEquals('Renamed', $after->optiontext);
        $this->assertEquals(1, $after->optioniscorrect);
    }

    /**
     * A moderator of course A must not be able to change a quiz answer of course B.
     */
    public function test_update_user_answer_cross_course(): void {
        global $DB;
        $this->setAdminUser();
        $coursea = $this->getDataGenerator()->create_course();
        $pagea = $this->toolgenerator->create_quiz_page($coursea);
        $optionaid = $this->toolgenerator->get_answer_option_ids($pagea->id)[0];
        $courseb = $this->getDataGenerator()->create_course();
        $pageb = $this->toolgenerator->create_quiz_page($courseb);
        $optionbid = $this->toolgenerator->get_answer_option_ids($pageb->id)[0];
        $victim = $this->getDataGenerator()->create_and_enrol($courseb, 'student');
        $victimanswerid = $this->toolgenerator->create_answer($pageb->id, $victim->id, $optionbid);

        $attacker = $this->getDataGenerator()->create_and_enrol($coursea, 'editingteacher');
        $this->setUser($attacker);

        try {
            update_user_answer::execute($pagea->id, $victimanswerid, [$optionaid]);
            $this->fail('A moderator of another course was allowed to use a foreign answer.');
        } catch (\moodle_exception $e) {
            $this->assertInstanceOf(\moodle_exception::class, $e);
        }

        $this->assertEquals($optionbid, $DB->get_field('mootimetertool_quiz_answers', 'optionid', ['id' => $victimanswerid]));
        $this->assertEquals(0, $DB->count_records('mootimetertool_quiz_answers', ['pageid' => $pagea->id]));
    }

    /**
     * A moderator may change an answer of their own page.
     */
    public function test_update_user_answer_own_page(): void {
        global $DB;
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $this->toolgenerator->create_quiz_page($course);
        [$firstoptionid, $secondoptionid] = $this->toolgenerator->get_answer_option_ids($page->id);
        $student = $this->getDataGenerator()->create_and_enrol($course, 'student');
        $answerid = $this->toolgenerator->create_answer($page->id, $student->id, $firstoptionid);

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);
        update_user_answer::execute($page->id, $answerid, [$secondoptionid]);

        $this->assertEquals(
            [$secondoptionid],
            array_values($DB->get_fieldset('mootimetertool_quiz_answers', 'optionid', [
                'pageid' => $page->id,
                'usermodified' => $student->id,
            ]))
        );
    }
}
