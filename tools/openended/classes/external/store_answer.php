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
 * Web service to store an open-ended answer.
 *
 * @package     mootimetertool_openended
 * @copyright   2026, ISB Bayern
 * @author      Benedikt Blumenfelder
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_openended\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mootimeter\helper;
use mootimetertool_openended\openended;

/**
 * Web service to store an open-ended answer.
 */
class store_answer extends external_api {
    /**
     * Parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'answer' => new external_value(PARAM_TEXT, 'The answer the user entered.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute.
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
        $cm = helper::get_cm_by_pageid($pageid);
        $cmcontext = \context_module::instance($cm->id);
        self::validate_context($cmcontext);
        require_capability('mod/mootimeter:view', $cmcontext);

        $trimmed = trim($answer);
        if ($trimmed === '') {
            return [
                'code' => helper::ERRORCODE_EMPTY_ANSWER,
                'string' => get_string('error_empty_answers', 'mootimetertool_openended'),
            ];
        }

        $helper = new helper();
        $page = $helper->get_page($pageid);

        $maxchars = openended::get_effective_maxchars($page->id);
        if (mb_strlen($trimmed) > $maxchars) {
            return [
                'code' => openended::ERRORCODE_TOO_LONG,
                'string' => get_string('error_too_long', 'mootimetertool_openended', $maxchars),
            ];
        }

        $maxnumberofanswers = (int) helper::get_tool_config($page->id, 'maxinputsperuser');
        $tool = new openended();
        $submittedanswers = $tool->get_user_answers(
            openended::ANSWER_TABLE,
            $page->id,
            openended::ANSWER_COLUMN,
            $USER->id
        );

        if ($maxnumberofanswers > 0 && count($submittedanswers) >= $maxnumberofanswers) {
            return [
                'code' => helper::ERRORCODE_TO_MANY_ANSWERS,
                'string' => get_string('error_to_many_answers', 'mootimetertool_openended'),
            ];
        }

        $tool->insert_answer($page, $trimmed);
        return ['code' => helper::ERRORCODE_OK, 'string' => 'ok'];
    }

    /**
     * Returns.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                'code' => new external_value(PARAM_INT, 'Return code of storage process.'),
                'string' => new external_value(PARAM_TEXT, 'Return string of storage process.'),
            ],
            'Status of answer storing process'
        );
    }
}
