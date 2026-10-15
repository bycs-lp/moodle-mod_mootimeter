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
 * Web service to store a answer.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_wordcloud\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mootimeter\helper;
use mod_mootimeter\local\external_util;
use mootimetertool_wordcloud\wordcloud;

/**
 * Web service to store a answer.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class store_answer extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'answer' => new external_value(PARAM_TEXT, 'The answer the user entered.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param string $answer
     * @return array
     */
    public static function execute(int $pageid, string $answer): array {
        global $USER;

        [
            'pageid' => $pageid,
            'answer' => $answer,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'answer' => $answer,
        ]);
        [$page] = external_util::validate_page($pageid);
        if ($page->tool !== 'wordcloud') {
            throw new \invalid_parameter_exception('The page is not a wordcloud page.');
        }

        $answer = trim($answer);
        if ($answer === '') {
            return [
                'code' => helper::ERRORCODE_EMPTY_ANSWER,
                'message' => get_string('error_empty_answers', 'mootimetertool_wordcloud'),
            ];
        }

        $maxnumberofanswers = (int) helper::get_tool_config($page->id, "maxinputsperuser");
        $wordcloud = new wordcloud();
        $submittedanswers = $wordcloud->get_user_answers(wordcloud::ANSWER_TABLE, $page->id, wordcloud::ANSWER_COLUMN, $USER->id);

        if (count($submittedanswers) >= $maxnumberofanswers && $maxnumberofanswers > 0) {
            return [
                'code' => helper::ERRORCODE_TO_MANY_ANSWERS,
                'message' => get_string('error_to_many_answers', 'mootimetertool_wordcloud'),
            ];
        }

        $submittedanswers = array_map('strtolower', $wordcloud->get_answer_list_array($page->id, $USER->id));
        // Use strtolower in order to ignore case sensitive inputs of users. This makes the result more precise.
        if (!helper::get_tool_config($page->id, "allowduplicateanswers") && in_array(strtolower($answer), $submittedanswers)) {
            return [
                'code' => helper::ERRORCODE_DUPLICATE_ANSWER,
                'message' => get_string('error_no_duplicate_answers', 'mootimetertool_wordcloud'),
            ];
        }

        $wordcloud->insert_answer($page, $answer);
        return [
            'code' => helper::ERRORCODE_OK,
            'message' => get_string('notification_success_store_answer', 'mod_mootimeter'),
        ];
    }

    /**
     * Describes the return structure of the service..
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'code' => new external_value(PARAM_INT, 'Result code, 200 on success'),
                'message' => new external_value(PARAM_TEXT, 'Message for the participant'),
            ],
            'Status of answer storing process'
        );
    }
}
