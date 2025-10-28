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
 * Base class for unit tests for mod_mootimeter.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\privacy;

defined('MOODLE_INTERNAL') || die();

global $CFG;

use core_privacy\tests\provider_testcase;
use core_privacy\local\request\writer;
use core_privacy\local\request\approved_contextlist;
use mod_mootimeter\helper;

/**
 * Unit tests for mod/mootimeter/classes/privacy/
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
final class provider_test extends provider_testcase {
    /** @var array The courses used for testing */
    private $courses = [];
    /** @var array The mootimeters used for testing [1=>['instance' => object, 'pages' => [page1, page2]], 2=>[...] ...]*/
    private $mootimeter = [];
    /** @var array The users used for testing */
    private $users;
    /** @var array The roles defined for testing */
    private $roles;
    /** @var object $generator The generator instance */
    private $generator;

    /** @var string The name of the first mootimeter instance */
    const INSTANCE_1_NAME = 'Mootimeter 1';
    /** @var string The name of the second mootimeter instance */
    const INSTANCE_2_NAME = 'Mootimeter 2';
    /** @var string The name of the third mootimeter instance */
    const INSTANCE_3_NAME = 'Mootimeter 3';

    /**
     * Setup the test cases.
     */
    public function setup(): void {
        global $DB;

        $this->generator = $this->getDataGenerator();
        $this->courses[1] = $this->generator->create_course();
        $this->courses[2] = $this->generator->create_course();
        $this->courses[3] = $this->generator->create_course();

        $this->roles['student'] = $DB->get_record('role', ['shortname' => 'student'], '*', MUST_EXIST);
        $this->roles['teacher'] = $DB->get_record('role', ['shortname' => 'teacher'], '*', MUST_EXIST);

        $this->users['teacher'] = $this->generator->create_user();
        $this->users['student1'] = $this->generator->create_user();
        $this->users['student2'] = $this->generator->create_user();
        $this->users['student3'] = $this->generator->create_user();
        $this->users['student_not_in_course'] = $this->generator->create_user();

        $this->generator->role_assign('teacher', $this->users['teacher']->id, \context_course::instance($this->courses[1]->id));
        $this->generator->role_assign('student', $this->users['student1']->id, \context_course::instance($this->courses[1]->id));
        $this->generator->role_assign('student', $this->users['student2']->id, \context_course::instance($this->courses[1]->id));
        $this->generator->role_assign('student', $this->users['student3']->id, \context_course::instance($this->courses[1]->id));

        $this->generator->role_assign('teacher', $this->users['teacher']->id, \context_course::instance($this->courses[2]->id));
        $this->generator->role_assign('student', $this->users['student1']->id, \context_course::instance($this->courses[2]->id));
        $this->generator->role_assign('student', $this->users['student2']->id, \context_course::instance($this->courses[2]->id));

        $this->generator->role_assign('teacher', $this->users['teacher']->id, \context_course::instance($this->courses[3]->id));
        $this->generator->role_assign('student', $this->users['student1']->id, \context_course::instance($this->courses[3]->id));

        assign_capability(
            'mod/mootimeter:moderator',
            CAP_ALLOW,
            $this->roles['teacher']->id,
            \context_system::instance(),
            true
        );

        $helper = new helper();

        // Create multiple mootimeter instances.
        $this->setUser($this->users['teacher']);

        // Mootimeter with a tool quiz.
        $mtmthelper = new \mootimetertool_quiz\quiz();
        $record = ['course' => $this->courses[1], 'name' => self::INSTANCE_1_NAME];
        $this->mootimeter[1]['instance'] = $this->generator->create_module('mootimeter', ['course' => $this->courses[1]]);
        $record = ['instance' => $this->mootimeter[1]['instance']->id, 'tool' => 'quiz', 'visible' => helper::PAGE_VISIBLE];
        $this->mootimeter[1]['pages'][1]['page'] = $helper->get_page($helper->store_page((object) $record));
        $pageid = $this->mootimeter[1]['pages'][1]['page']->id;
        $helper->toggle_state($this->mootimeter[1]['pages'][1]['page'], 'page_visibility');

        // Insert some answeroption text.
        $answeroptions = $mtmthelper->get_answer_options($pageid);
        $ao1 = array_pop($answeroptions);
        $ao2 = array_pop($answeroptions);
        $ao1->optiontext = 'Quiz-Option 1';
        $mtmthelper->store_answer_option($ao1);
        $ao2->optiontext = 'Quiz-Option 2';
        $mtmthelper->store_answer_option($ao2);
        $this->mootimeter[1]['pages'][1]['answeroptions'] = $mtmthelper->get_answer_options($pageid);

        // Mootimeter with a tool poll.
        $mtmthelper = new \mootimetertool_poll\poll();
        $this->mootimeter[2]['instance'] = $this->generator->create_module('mootimeter', ['course' => $this->courses[2]]);
        $record = ['instance' => $this->mootimeter[2]['instance']->id, 'tool' => 'poll', 'visible' => helper::PAGE_VISIBLE];
        $this->mootimeter[2]['pages'][1]['page'] = $helper->get_page($helper->store_page((object) $record));
        $pageid = $this->mootimeter[2]['pages'][1]['page']->id;
        $helper->toggle_state($this->mootimeter[2]['pages'][1]['page'], 'page_visibility');

        // Insert some answeroption text.
        $answeroptions = $mtmthelper->get_answer_options($pageid);
        $ao1 = array_pop($answeroptions);
        $ao2 = array_pop($answeroptions);
        $ao1->optiontext = 'Poll-Option 1';
        $mtmthelper->store_answer_option($ao1);
        $ao2->optiontext = 'Poll-Option 2';
        $mtmthelper->store_answer_option($ao2);

        $this->mootimeter[2]['pages'][1]['answeroptions'] = $mtmthelper->get_answer_options($pageid);

        // Mootimeter with a tool wordcloud.
        $mtmthelper = new \mootimetertool_wordcloud\wordcloud();
        $this->mootimeter[3]['instance'] = $this->generator->create_module('mootimeter', ['course' => $this->courses[3]]);
        $record = ['instance' => $this->mootimeter[3]['instance']->id, 'tool' => 'wordcloud', 'visible' => helper::PAGE_VISIBLE];
        $this->mootimeter[3]['pages'][1]['page'] = $helper->get_page($helper->store_page((object) $record));
        $helper->toggle_state($this->mootimeter[3]['pages'][1]['page'], 'page_visibility');

        $this->generator->enrol_user($this->users['teacher']->id, $this->courses[1]->id, 'teacher');
        $this->generator->enrol_user($this->users['teacher']->id, $this->courses[2]->id, 'teacher');
        $this->generator->enrol_user($this->users['teacher']->id, $this->courses[3]->id, 'teacher');

        $this->generator->enrol_user($this->users['student1']->id, $this->courses[1]->id, 'student');
        $this->generator->enrol_user($this->users['student1']->id, $this->courses[2]->id, 'student');
        $this->generator->enrol_user($this->users['student1']->id, $this->courses[3]->id, 'student');

        $this->generator->enrol_user($this->users['student2']->id, $this->courses[1]->id, 'student');
        $this->generator->enrol_user($this->users['student2']->id, $this->courses[2]->id, 'student');

        $this->generator->enrol_user($this->users['student3']->id, $this->courses[1]->id, 'student');

        parent::setup();
    }


    /**
     * Test that getting the contexts for a user.
     * @covers \core_privacy\local\request\core_userlist_provider::get_contexts_for_userid
     */
    public function test_get_contexts_for_userid(): void {
        $this->resetAfterTest();

        // The user will be in these contexts.
        $usercontextids = [
            \context_module::instance($this->mootimeter[1]['instance']->cmid)->id,
        ];

        // Insert some answers to page 1 in instance 1.
        $mtmthelper = new \mootimetertool_quiz\quiz();
        $ao1 = array_pop($this->mootimeter[1]['pages'][1]['answeroptions']);
        $ao2 = array_pop($this->mootimeter[1]['pages'][1]['answeroptions']);

        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao1->id]);
        $this->setUser($this->users['student2']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao2->id]);
        $this->setUser($this->users['student3']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao1->id]);

        $contextlist = provider::get_contexts_for_userid($this->users['student1']->id);
        $this->assertEquals(count($usercontextids), count($contextlist->get_contextids()));
        // There should be no difference between the contexts.
        $this->assertEmpty(array_diff($usercontextids, $contextlist->get_contextids()));

        // Now add some answers to a poll in second instance.

        // The user will be in these contexts.
        $usercontextids = [
            \context_module::instance($this->mootimeter[1]['instance']->cmid)->id,
            \context_module::instance($this->mootimeter[2]['instance']->cmid)->id,
        ];

        // Insert some answers to page 1 in instance 2.
        $mtmthelper = new \mootimetertool_poll\poll();
        $ao1 = array_pop($this->mootimeter[2]['pages'][1]['answeroptions']);
        $ao2 = array_pop($this->mootimeter[2]['pages'][1]['answeroptions']);

        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[2]['pages'][1]['page'], [$ao1->id]);

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();

        $contextlist = provider::get_contexts_for_userid($this->users['student1']->id);
        $this->assertEquals(count($usercontextids), count($contextlist->get_contextids()));
        // There should be no difference between the contexts.
        $this->assertEmpty(array_diff($usercontextids, $contextlist->get_contextids()));

        // Finally insert some answers to page 1 in instance 3.
        // The user will be in these contexts.
        $usercontextids = [
            \context_module::instance($this->mootimeter[1]['instance']->cmid)->id,
            \context_module::instance($this->mootimeter[2]['instance']->cmid)->id,
            \context_module::instance($this->mootimeter[3]['instance']->cmid)->id,
        ];
        $mtmthelper = new \mootimetertool_wordcloud\wordcloud();
        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[3]['pages'][1]['page'], "Test");

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();

        $contextlist = provider::get_contexts_for_userid($this->users['student1']->id);
        $this->assertEquals(count($usercontextids), count($contextlist->get_contextids()));
        // There should be no difference between the contexts.
        $this->assertEmpty(array_diff($usercontextids, $contextlist->get_contextids()));
    }

    /**
     * Test returning a list of user IDs related to a context.
     * @covers \core_privacy\local\request\core_userlist_provider::get_users_in_context
     */
    public function test_get_users_in_context(): void {
        $this->resetAfterTest();

        $mtmthelper = new \mootimetertool_quiz\quiz();
        $ao1 = array_pop($this->mootimeter[1]['pages'][1]['answeroptions']);
        $ao2 = array_pop($this->mootimeter[1]['pages'][1]['answeroptions']);

        // Two of the three users answered the question.
        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao1->id]);
        $this->setUser($this->users['student2']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao2->id]);

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();
        $context = \context_module::instance($this->mootimeter[1]['instance']->cmid);
        $userlist = new \core_privacy\local\request\userlist($context, 'mootimeter');
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();

        $this->assertTrue(in_array($this->users['student1']->id, $userids));
        $this->assertTrue(in_array($this->users['student2']->id, $userids));
        $this->assertFalse(in_array($this->users['student3']->id, $userids));

        // Now the third user answers the question.
        $this->setUser($this->users['student3']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao1->id]);

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();
        $userlist = new \core_privacy\local\request\userlist($context, 'mootimeter');
        provider::get_users_in_context($userlist);
        $userids = $userlist->get_userids();

        $this->assertTrue(in_array($this->users['student3']->id, $userids));
    }

    /**
     * Test that a student with multiple answers is returned with the correct data.
     * @covers \provider::export_user_data
     */
    public function test_export_user_data(): void {
        $this->resetAfterTest();

        $mtmthelper = new \mootimetertool_quiz\quiz();
        $ao1 = array_pop($this->mootimeter[1]['pages'][1]['answeroptions']);
        $ao2 = array_pop($this->mootimeter[1]['pages'][1]['answeroptions']);

        // Two of the three users answered the question.
        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao1->id, $ao2->id]);
        $this->setUser($this->users['student2']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao2->id]);

        $context = \context_module::instance($this->mootimeter[1]['instance']->cmid);

        $writer = writer::with_context($context);
        $this->assertFalse($writer->has_any_data());

        // The student should have answered the question.
        // Add the course context as well to make sure there is no error.

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();

        $coursecontext = \context_course::instance($this->courses[1]->id);
        $approvedlist = new approved_contextlist($this->users['student1'], 'mod_mootimeter', [$context->id, $coursecontext->id]);
        provider::export_user_data($approvedlist);

        // Check that we have general details about the mootimeter instance.
        $this->assertEquals(self::INSTANCE_1_NAME, $writer->get_data()->name);

        // Check mootimetertools.
        $path = [
            'Path of page with id: ' . $this->mootimeter[1]['pages'][1]['page']->id,
            get_string('privacy:answerspath', 'mootimetertool_quiz') . '_1',
        ];

        $this->assertEquals($ao1->id, $writer->get_data($path)->answeroptionid);
        $this->assertEquals($ao1->optiontext, $writer->get_data($path)->answer);

        // Now test tool poll.

        $mtmthelper = new \mootimetertool_poll\poll();
        $ao1 = array_pop($this->mootimeter[2]['pages'][1]['answeroptions']);
        $ao2 = array_pop($this->mootimeter[2]['pages'][1]['answeroptions']);

        // Two of the three users answered the question.
        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[2]['pages'][1]['page'], [$ao1->id, $ao2->id]);
        $this->setUser($this->users['student2']);
        $mtmthelper->insert_answer($this->mootimeter[2]['pages'][1]['page'], [$ao2->id]);

        $context = \context_module::instance($this->mootimeter[2]['instance']->cmid);

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();
        $writer = writer::with_context($context);
        $this->assertFalse($writer->has_any_data());

        // The student should have answered the question.
        // Add the course context as well to make sure there is no error.

        $coursecontext = \context_course::instance($this->courses[2]->id);
        $approvedlist = new approved_contextlist($this->users['student1'], 'mod_mootimeter', [$context->id, $coursecontext->id]);
        provider::export_user_data($approvedlist);

        // Check that we have general details about the mootimeter instance.
        $this->assertEquals(self::INSTANCE_2_NAME, $writer->get_data()->name);

        // Check mootimetertools.
        $path = [
            'Path of page with id: ' . $this->mootimeter[2]['pages'][1]['page']->id,
            get_string('privacy:answerspath', 'mootimetertool_poll') . '_1',
        ];
        $this->assertEquals($ao1->id, $writer->get_data($path)->answeroptionid);
        $this->assertEquals($ao1->optiontext, $writer->get_data($path)->answer);

        // Now test tool wordcloud.

        $mtmthelper = new \mootimetertool_wordcloud\wordcloud();

        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[3]['pages'][1]['page'], "Answer 1");
        $mtmthelper->insert_answer($this->mootimeter[3]['pages'][1]['page'], "Answer 2");

        $context = \context_module::instance($this->mootimeter[3]['instance']->cmid);

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();
        $writer = writer::with_context($context);
        $this->assertFalse($writer->has_any_data());

        // The student should have answered the question.
        // Add the course context as well to make sure there is no error.

        $coursecontext = \context_course::instance($this->courses[3]->id);
        $approvedlist = new approved_contextlist($this->users['student1'], 'mod_mootimeter', [$context->id, $coursecontext->id]);
        provider::export_user_data($approvedlist);

        // Check that we have general details about the mootimeter instance.
        $this->assertEquals(self::INSTANCE_3_NAME, $writer->get_data()->name);

        // Check mootimetertools.
        $path = [
            'Path of page with id: ' . $this->mootimeter[3]['pages'][1]['page']->id,
            get_string('privacy:answerspath', 'mootimetertool_wordcloud') . '_1',
        ];
        $this->assertEquals("Answer 1", $writer->get_data($path)->scalar);

        $path = [
            'Path of page with id: ' . $this->mootimeter[3]['pages'][1]['page']->id,
            get_string('privacy:answerspath', 'mootimetertool_wordcloud') . '_2',
        ];
        $this->assertEquals("Answer 2", $writer->get_data($path)->scalar);
    }

    /**
     * A test for deleting all user data for a given context.
     * @covers \provider::delete_data_for_all_users_in_context
     */
    public function test_delete_data_for_all_users_in_context(): void {
        $this->resetAfterTest();

        $mtmthelper = new \mootimetertool_quiz\quiz();
        $ao1 = array_pop($this->mootimeter[1]['pages'][1]['answeroptions']);
        $ao2 = array_pop($this->mootimeter[1]['pages'][1]['answeroptions']);

        // Two of the three users answered the question.
        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao1->id, $ao2->id]);
        $this->setUser($this->users['student2']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao2->id]);

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();
        $context = \context_module::instance($this->mootimeter[1]['instance']->cmid);

        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[1]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(3, $answers);

        provider::delete_data_for_all_users_in_context($context);

        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[1]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(0, $answers);

        // Test tool poll.

        $mtmthelper = new \mootimetertool_poll\poll();
        $ao1 = array_pop($this->mootimeter[2]['pages'][1]['answeroptions']);
        $ao2 = array_pop($this->mootimeter[2]['pages'][1]['answeroptions']);

        // Two of the three users answered the question.
        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[2]['pages'][1]['page'], [$ao1->id, $ao2->id]);
        $this->setUser($this->users['student2']);
        $mtmthelper->insert_answer($this->mootimeter[2]['pages'][1]['page'], [$ao2->id]);

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();
        $context = \context_module::instance($this->mootimeter[2]['instance']->cmid);

        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[2]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(3, $answers);

        provider::delete_data_for_all_users_in_context($context);

        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[2]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(0, $answers);

        // Test tool wordcoud.

        $mtmthelper = new \mootimetertool_wordcloud\wordcloud();
        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[3]['pages'][1]['page'], "Answer 1");
        $mtmthelper->insert_answer($this->mootimeter[3]['pages'][1]['page'], "Answer 2");
        $this->setUser($this->users['teacher']);
        $mtmthelper->insert_answer($this->mootimeter[3]['pages'][1]['page'], "Answer 3");

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();
        $context = \context_module::instance($this->mootimeter[3]['instance']->cmid);

        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[3]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(3, $answers);

        provider::delete_data_for_all_users_in_context($context);

        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[3]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(0, $answers);
    }

    /**
     * A test for deleting all user data for one user.
     * @covers \provider::delete_data_for_user
     */
    public function test_delete_data_for_user(): void {
        $this->resetAfterTest();

        $mtmthelper = new \mootimetertool_quiz\quiz();
        $ao1 = array_pop($this->mootimeter[1]['pages'][1]['answeroptions']);
        $ao2 = array_pop($this->mootimeter[1]['pages'][1]['answeroptions']);

        // Two of the three users answered the question.
        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao1->id, $ao2->id]);
        $this->setUser($this->users['student2']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao2->id]);

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();
        $context = \context_module::instance($this->mootimeter[1]['instance']->cmid);

        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[1]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(3, $answers);

        // Delete student1's data.
        $coursecontext = \context_course::instance($this->courses[1]->id);
        $approvedlist = new approved_contextlist($this->users['student1'], 'mod_mootimeter', [$context->id, $coursecontext->id]);

        provider::delete_data_for_user($approvedlist);

        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[1]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(1, $answers);

        // Test tool poll.

        $mtmthelper = new \mootimetertool_poll\poll();
        $ao1 = array_pop($this->mootimeter[2]['pages'][1]['answeroptions']);
        $ao2 = array_pop($this->mootimeter[2]['pages'][1]['answeroptions']);

        // Two of the three users answered the question.
        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[2]['pages'][1]['page'], [$ao1->id, $ao2->id]);
        $this->setUser($this->users['student2']);
        $mtmthelper->insert_answer($this->mootimeter[2]['pages'][1]['page'], [$ao2->id]);

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();
        $context = \context_module::instance($this->mootimeter[2]['instance']->cmid);

        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[2]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(3, $answers);

        // Delete student1's data.
        $coursecontext = \context_course::instance($this->courses[2]->id);
        $approvedlist = new approved_contextlist($this->users['student1'], 'mod_mootimeter', [$context->id, $coursecontext->id]);

        provider::delete_data_for_user($approvedlist);
        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[2]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(1, $answers);

        // Test tool wordcoud.

        $mtmthelper = new \mootimetertool_wordcloud\wordcloud();
        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[3]['pages'][1]['page'], "Answer 1");
        $mtmthelper->insert_answer($this->mootimeter[3]['pages'][1]['page'], "Answer 2");
        $this->setUser($this->users['teacher']);
        $mtmthelper->insert_answer($this->mootimeter[3]['pages'][1]['page'], "Answer 3");

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();
        $context = \context_module::instance($this->mootimeter[3]['instance']->cmid);

        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[3]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(3, $answers);

        // Delete student1's data.
        $coursecontext = \context_course::instance($this->courses[3]->id);
        $approvedlist = new approved_contextlist($this->users['student1'], 'mod_mootimeter', [$context->id, $coursecontext->id]);

        provider::delete_data_for_user($approvedlist);

        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[3]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(1, $answers);
    }

    /**
     * A test for deleting all user data for a bunch of users.
     * @covers \provider::delete_data_for_user
     */
    public function test_delete_data_for_users(): void {
        $this->resetAfterTest();

        $mtmthelper = new \mootimetertool_quiz\quiz();
        $ao1 = array_pop($this->mootimeter[1]['pages'][1]['answeroptions']);
        $ao2 = array_pop($this->mootimeter[1]['pages'][1]['answeroptions']);

        // All users answered the question.
        $this->setUser($this->users['student1']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao1->id, $ao2->id]);
        $this->setUser($this->users['student2']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao2->id]);
        $this->setUser($this->users['student3']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao2->id]);
        $this->setUser($this->users['teacher']);
        $mtmthelper->insert_answer($this->mootimeter[1]['pages'][1]['page'], [$ao1->id, $ao2->id]);

        $context = \context_module::instance($this->mootimeter[1]['instance']->cmid);

        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[1]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(6, $answers);

        // Delete user data in list.
        $userids = [
            $this->users['student1']->id,
            $this->users['student2']->id,
        ];

        // Now switch back to admin user. Because the privacy api works with all capabilities.
        $this->setAdminUser();
        $userlist = new \core_privacy\local\request\approved_userlist($context, 'mootimeter', $userids);
        provider::delete_data_for_users($userlist);

        $answers = (array)$mtmthelper->get_answers(
            $mtmthelper->get_answer_table(),
            $this->mootimeter[1]['pages'][1]['page']->id,
            $mtmthelper->get_answer_column()
        );
        $this->assertCount(3, $answers);
    }
}
