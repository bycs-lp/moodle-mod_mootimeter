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

namespace mootimetertool_quiz\external;

use advanced_testcase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Group;

#[CoversClass(store_answeroption::class)]
/**
 * Tests for the web service storing the text of an answer option.
 *
 * @package     mootimetertool_quiz
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @covers      \mootimetertool_quiz\external\store_answeroption
 */
final class store_answeroption_test extends advanced_testcase {
    /**
     * A moderator of course A must not be able to overwrite an answer option of course B
     * by combining their own page id with a foreign answer option id.
     */
    #[Group('baseline')]
    public function test_store_foreign_answer_option_is_rejected(): void {
        global $DB;
        $this->resetAfterTest();

        /** @var \mootimetertool_quiz_generator $toolgenerator */
        $toolgenerator = $this->getDataGenerator()->get_plugin_generator('mootimetertool_quiz');
        $this->setAdminUser();
        $coursea = $this->getDataGenerator()->create_course();
        $pagea = $toolgenerator->create_quiz_page($coursea);
        $courseb = $this->getDataGenerator()->create_course();
        $pageb = $toolgenerator->create_quiz_page($courseb);
        $optionbid = $toolgenerator->get_answer_option_ids($pageb->id)[0];
        $before = $DB->get_record('mootimetertool_quiz_options', ['id' => $optionbid]);

        $attacker = $this->getDataGenerator()->create_and_enrol($coursea, 'editingteacher');
        $this->setUser($attacker);

        $result = store_answeroption::execute($pagea->id, $optionbid, 'gone', 'x');

        $this->assertNotEquals(200, $result['code']);
        $after = $DB->get_record('mootimetertool_quiz_options', ['id' => $optionbid]);
        $this->assertEquals($before->pageid, $after->pageid);
        $this->assertEquals($before->optiontext, $after->optiontext);
    }

    /**
     * A moderator may still change the text of an answer option of their own page.
     */
    #[Group('baseline')]
    public function test_store_own_answer_option(): void {
        global $DB;
        $this->resetAfterTest();

        /** @var \mootimetertool_quiz_generator $toolgenerator */
        $toolgenerator = $this->getDataGenerator()->get_plugin_generator('mootimetertool_quiz');
        $this->setAdminUser();
        $course = $this->getDataGenerator()->create_course();
        $page = $toolgenerator->create_quiz_page($course);
        $optionid = $toolgenerator->get_answer_option_ids($page->id)[0];

        $teacher = $this->getDataGenerator()->create_and_enrol($course, 'editingteacher');
        $this->setUser($teacher);

        $result = store_answeroption::execute($page->id, $optionid, 'Renamed', 'x');

        $this->assertEquals(200, $result['code']);
        $after = $DB->get_record('mootimetertool_quiz_options', ['id' => $optionid]);
        $this->assertEquals($page->id, $after->pageid);
        $this->assertEquals('Renamed', $after->optiontext);
    }
}
