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
 * Privacy provider for mootimetertool_openended.
 *
 * @package     mootimetertool_openended
 * @copyright   2026, ISB Bayern
 * @author      Benedikt Blumenfelder
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_openended\privacy;

use core_privacy\local\metadata\collection;
use core_privacy\local\request\contextlist;
use core_privacy\local\request\writer;
use mod_mootimeter\privacy\mootimeter_plugin_request_data;
use mootimetertool_openended\openended;

/**
 * Privacy provider for mootimetertool_openended.
 */
class provider implements
    \core_privacy\local\metadata\provider,
    \mod_mootimeter\privacy\mootimetertool_provider,
    \mod_mootimeter\privacy\mootimetertool_user_provider {

    /**
     * Declare what user data we store.
     */
    public static function get_metadata(collection $collection): collection {
        $collection->add_database_table(
            'mootimetertool_openended_answers',
            [
                'usermodified' => 'privacy:metadata:mootimetertool_openended_answers:userid',
                'pageid' => 'privacy:metadata:mootimetertool_openended_answers:pageid',
                'answer' => 'privacy:metadata:mootimetertool_openended_answers:answer',
                'visible' => 'privacy:metadata:mootimetertool_openended_answers:visible',
                'timecreated' => 'privacy:metadata:mootimetertool_openended_answers:timecreated',
                'timemodified' => 'privacy:metadata:mootimetertool_openended_answers:timemodified',
            ],
            'privacy:metadata:mootimetertool_openended_answers'
        );
        $collection->add_database_table(
            'mootimetertool_openended_reactions',
            [
                'usermodified' => 'privacy:metadata:mootimetertool_openended_reactions:userid',
                'answerid' => 'privacy:metadata:mootimetertool_openended_reactions:answerid',
                'pageid' => 'privacy:metadata:mootimetertool_openended_reactions:pageid',
                'emoji' => 'privacy:metadata:mootimetertool_openended_reactions:emoji',
                'timecreated' => 'privacy:metadata:mootimetertool_openended_reactions:timecreated',
            ],
            'privacy:metadata:mootimetertool_openended_reactions'
        );
        return $collection;
    }

    /**
     * Find module contexts where the user has either an answer or a reaction.
     */
    public static function get_context_for_userid_within_mootimetertool(int $userid, contextlist $contextlist) {
        $params = [
            'modulename' => 'mootimeter',
            'contextlevel' => CONTEXT_MODULE,
            'userid' => $userid,
        ];

        $sqlanswers = "SELECT DISTINCT ctx.id
                         FROM {course_modules} cm
                         JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                         JOIN {mootimeter} mtm ON cm.instance = mtm.id
                         JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                         JOIN {mootimeter_pages} mtmp ON mtmp.instance = mtm.id
                         JOIN {mootimetertool_openended_answers} a ON a.pageid = mtmp.id AND a.usermodified = :userid";
        $contextlist->add_from_sql($sqlanswers, $params);

        $sqlreactions = "SELECT DISTINCT ctx.id
                           FROM {course_modules} cm
                           JOIN {modules} m ON cm.module = m.id AND m.name = :modulename
                           JOIN {mootimeter} mtm ON cm.instance = mtm.id
                           JOIN {context} ctx ON cm.id = ctx.instanceid AND ctx.contextlevel = :contextlevel
                           JOIN {mootimeter_pages} mtmp ON mtmp.instance = mtm.id
                           JOIN {mootimetertool_openended_reactions} r ON r.pageid = mtmp.id AND r.usermodified = :userid";
        $contextlist->add_from_sql($sqlreactions, $params);

        return $contextlist;
    }

    /**
     * Provide the userids that have data in the given context.
     */
    public static function get_userids_from_context(\core_privacy\local\request\userlist $userlist) {
        $context = $userlist->get_context();
        $params = [
            'modulename' => 'mootimeter',
            'contextid' => $context->id,
            'contextlevel' => CONTEXT_MODULE,
        ];

        $sqlanswers = "SELECT DISTINCT a.usermodified AS userid
                         FROM {context} ctx
                         JOIN {course_modules} cm ON cm.id = ctx.instanceid
                         JOIN {mootimeter} mtm ON cm.instance = mtm.id
                         JOIN {mootimeter_pages} mtmp ON mtmp.instance = mtm.id
                         JOIN {mootimetertool_openended_answers} a ON a.pageid = mtmp.id
                        WHERE ctx.id = :contextid AND ctx.contextlevel = :contextlevel";
        $userlist->add_from_sql('userid', $sqlanswers, $params);

        $sqlreactions = "SELECT DISTINCT r.usermodified AS userid
                           FROM {context} ctx
                           JOIN {course_modules} cm ON cm.id = ctx.instanceid
                           JOIN {mootimeter} mtm ON cm.instance = mtm.id
                           JOIN {mootimeter_pages} mtmp ON mtmp.instance = mtm.id
                           JOIN {mootimetertool_openended_reactions} r ON r.pageid = mtmp.id
                          WHERE ctx.id = :contextid AND ctx.contextlevel = :contextlevel";
        $userlist->add_from_sql('userid', $sqlreactions, $params);
    }

    /**
     * Export the user's answers and reactions for a single page.
     */
    public static function export_mootimetertool_user_data(mootimeter_plugin_request_data $exportdata) {
        global $DB;

        if ($exportdata->get_page()->tool != 'openended') {
            return;
        }

        $page = $exportdata->get_page();
        $userid = $exportdata->get_user()->id;
        $context = $exportdata->get_context();
        $basepath = $exportdata->get_subcontext();

        // Export answers.
        $answers = $DB->get_records(
            openended::ANSWER_TABLE,
            ['pageid' => $page->id, 'usermodified' => $userid],
            'timecreated ASC'
        );
        $i = 1;
        foreach ($answers as $row) {
            $path = array_merge($basepath, [get_string('privacy:answerspath', 'mootimetertool_openended') . '_' . $i]);
            writer::with_context($context)->export_data($path, (object) [
                'answer' => $row->answer,
                'visible' => (int) $row->visible,
                'timecreated' => userdate($row->timecreated),
            ]);
            $i++;
        }

        // Export reactions.
        $reactions = $DB->get_records(
            openended::REACTION_TABLE,
            ['pageid' => $page->id, 'usermodified' => $userid],
            'timecreated ASC'
        );
        $i = 1;
        foreach ($reactions as $row) {
            $path = array_merge($basepath, [get_string('privacy:reactionspath', 'mootimetertool_openended') . '_' . $i]);
            writer::with_context($context)->export_data($path, (object) [
                'answerid' => (int) $row->answerid,
                'emoji' => $row->emoji,
                'timecreated' => userdate($row->timecreated),
            ]);
            $i++;
        }
    }

    /**
     * Delete all answers (and their reactions) for a given page context.
     */
    public static function delete_answers_for_context(mootimeter_plugin_request_data $requestdata) {
        if ($requestdata->get_page()->tool != 'openended') {
            return;
        }
        $tool = new openended();
        $page = $requestdata->get_page();
        $tool->delete_answers_tool($page, ['pageid' => $page->id]);
    }

    /**
     * Delete a specific user's answers (and reactions) for a given page.
     */
    public static function delete_answers_for_user(mootimeter_plugin_request_data $requestdata) {
        global $DB;
        if ($requestdata->get_page()->tool != 'openended') {
            return;
        }
        $tool = new openended();
        $page = $requestdata->get_page();
        $user = $requestdata->get_user();

        // Delete the user's reactions on this page.
        $DB->delete_records(openended::REACTION_TABLE, [
            'pageid' => $page->id,
            'usermodified' => $user->id,
        ]);
        // And the user's own answers (and reactions still attached to them).
        $tool->delete_answers_tool($page, ['pageid' => $page->id, 'usermodified' => $user->id]);
    }
}
