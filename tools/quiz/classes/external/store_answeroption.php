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
 * Web service to store an answer option changed by the teacher.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_quiz\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use dml_exception;
use invalid_parameter_exception;
use mod_mootimeter\helper;
use mootimetertool_quiz\quiz;

/**
 * Web service to store an option.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class store_answeroption extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'aoid' => new external_value(PARAM_INT, 'The id of the answer option.', VALUE_REQUIRED),
            'value' => new external_value(PARAM_TEXT, 'The text value of the answer option.', VALUE_REQUIRED),
            'id' => new external_value(PARAM_RAW, 'The inputs id.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     * @param int $pageid
     * @param int $aoid "answer option id"
     * @param string $value
     * @param string $id
     * @return void
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function execute(int $pageid, int $aoid, string $value, string $id): array {
        global $USER;
        [
            'pageid' => $pageid,
            'aoid' => $aoid,
            'value' => $value,
            'id' => $id,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'aoid' => $aoid,
            'value' => $value,
            'id' => $id,
        ]);
        $cm = helper::get_cm_by_pageid($pageid);
        self::validate_context(\context_module::instance($cm->id));

        try {
            $quiz = new quiz();

            $record = new \stdClass();
            $record->id = $aoid;
            $record->pageid = $pageid;
            $record->usermodified = $USER->id;
            $record->optiontext = $value;
            $record->optioniscorrect = 0;
            $record->timecreated = time();

            $quiz->store_answer_option($record);
            $return = ['code' => 200, 'string' => 'ok'];
        } catch (\Exception $e) {
            $return = ['code' => 500, 'string' => $e->getMessage()];
        }
        return $return;
    }

    /**
     * Describes the return structure of the service..
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure(
            [
                        'code' => new external_value(PARAM_INT, 'Return code of storage process.'),
                        'string' => new external_value(PARAM_TEXT, 'Return string of storage process.'),
                ],
            'Response of storing the answer option details'
        );
    }
}
