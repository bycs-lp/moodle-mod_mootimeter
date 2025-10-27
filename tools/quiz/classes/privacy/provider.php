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
 * Privacy class for requesting user data.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_quiz\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use mod_mootimeter\privacy\mootimeter_plugin_request_data;

/**
 * Privacy class for requesting user data.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class provider implements
    \mod_mootimeter\privacy\mootimetertool_provider,
    \mod_mootimeter\privacy\mootimetertool_user_provider,
    \core_privacy\local\metadata\provider {
    /**
     * Provides meta data that is stored about a user with mod_assign
     *
     * @param  collection $collection A collection of meta data items to be added to.
     * @return  collection Returns the collection of metadata.
     */
    public static function get_metadata(collection $collection): collection {

        $collection->add_database_table(
            'mootimetertool_quiz_answers',
            [
                'usermodified' => 'privacy:metadata:mootimetertool_quiz_answers:userid',
                'pageid' => 'privacy:metadata:mootimetertool_quiz_answers:pageid',
                'optionid' => 'privacy:metadata:mootimetertool_quiz_answers:optionid',
                'timecreated' => 'privacy:metadata:mootimetertool_quiz_answers:timecreated',
                'timemodified' => 'privacy:metadata:mootimetertool_quiz_answers:timemodified',

            ],
            'privacy:metadata:mootimetertool_quiz_answers'
        );

        $collection->add_database_table(
            'mootimetertool_quiz_options',
            [
                'pageid' => 'privacy:metadata:mootimetertool_quiz_options:pageid',
                'optiontext' => 'privacy:metadata:mootimetertool_quiz_options:optiontext',
                'optioniscorrect' => 'privacy:metadata:mootimetertool_quiz_options:optioniscorrect',
                'timecreated' => 'privacy:metadata:mootimetertool_quiz_options:timecreated',
                'timemodified' => 'privacy:metadata:mootimetertool_quiz_options:timemodified',

            ],
            'privacy:metadata:mootimetertool_quiz_options'
        );
        return $collection;
    }

    /**
     * Returns all of the contexts that has information relating to the userid.
     *
     * @param  int $userid The user ID.
     * @param  contextlist $contextlist The contextlist
     * @return contextlist an object with the contexts related to a userid.
     */
    public static function get_context_for_userid_within_mootimetertool(int $userid, contextlist $contextlist) {

        $params = [
            'modulename' => 'mootimeter',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        $sql = "SELECT DISTINCT ctx.id
                  FROM {course_modules} cm
                  JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                  JOIN {mootimeter} mtm ON cm.instance = mtm.id
                  JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                  JOIN {mootimeter_pages} mtmp ON mtmp.instance = mtm.id
                  JOIN {mootimetertool_quiz_answers} mtmta ON mtmta.pageid = mtmp.id AND mtmta.usermodified = :userid";

        $contextlist->add_from_sql($sql, $params);

        return $contextlist;
    }

    /**
     * If you have tables that contain userids please fill in this method.
     *
     * @param  \core_privacy\local\request\userlist $userlist The userlist object
     */
    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist) {

        $context = $userlist->get_context();

        $params = [
            'modulename' => 'mootimeter',
            'contextid' => $context->id,
            'contextlevel' => CONTEXT_MODULE,
        ];

        $sql = "SELECT DISTINCT mtmta.usermodified as userid
                  FROM {context} ctx
                  JOIN {course_modules} cm ON cm.id = ctx.instanceid
                  JOIN {mootimeter} mtm ON cm.instance = mtm.id
                  JOIN {mootimeter_pages} mtmp ON mtmp.instance = mtm.id
                  JOIN {mootimetertool_quiz_answers} mtmta ON mtmta.pageid = mtmp.id
                  WHERE ctx.id = :contextid AND ctx.contextlevel = :contextlevel";
        $userlist->add_from_sql('userid', $sql, $params);
    }

    /**
     * Export the mootimetertool user data.
     *
     * @param mootimeter_plugin_request_data $exportdata
     * @return void
     */
    public static function export_mootimetertool_user_data(mootimeter_plugin_request_data $exportdata): void {

        if ($exportdata->get_page()->tool != "quiz") {
            return;
        }

        $mtmthelper = new \mootimetertool_quiz\quiz();

        $contextdata = $mtmthelper->get_user_answers(
            $mtmthelper->get_answer_table(),
            $exportdata->get_page()->id,
            $mtmthelper->get_answer_column(),
            $exportdata->get_user()->id
        );
        $i = 1;
        foreach ($contextdata as $row) {
            $answeroption = $mtmthelper->get_answer_option(['id' => $row->optionid]);
            $answer = ['answeroptionid' => $row->optionid, 'answer' => $answeroption->optiontext];
            $currentpath = $exportdata->get_subcontext();
            $currentpath[] = get_string('privacy:answerspath', 'mootimetertool_quiz') . "_" . $i;
            writer::with_context($exportdata->get_context())->export_data($currentpath, (object)$answer);
            $i++;
        }
    }

    /**
     * Any call to this method should delete all user data for the context defined in the deletion_criteria.
     *
     * @param  mootimeter_plugin_request_data $requestdata Data useful for deleting user data from this sub-plugin.
     */
    public static function delete_answers_for_context(mootimeter_plugin_request_data $requestdata) {

        if ($requestdata->get_page()->tool != "quiz") {
            return;
        }

        $mtmthelper = new \mootimetertool_quiz\quiz();
        $page = $requestdata->get_page();
        $mtmthelper->delete_answers_tool($page, ['pageid' => $page->id]);
    }

    /**
     * Any call to this method should delete users data for the context defined in the deletion_criteria.
     *
     * @param  mootimeter_plugin_request_data $requestdata Data useful for deleting user data from this sub-plugin.
     */
    public static function delete_answers_for_user($requestdata) {
        if ($requestdata->get_page()->tool != "quiz") {
            return;
        }

        $mtmthelper = new \mootimetertool_quiz\quiz();
        $user = $requestdata->get_user();
        $page = $requestdata->get_page();
        $mtmthelper->delete_answers_tool($page, ['pageid' => $page->id, 'usermodified' => $user->id]);
    }
}
