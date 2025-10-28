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
 * Web service to store a answer by the student.
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

/**
 * Web service to store an option.
 *
 * @package     mootimetertool_quiz
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
            'aoids' => new external_value(PARAM_TEXT, 'The ids of the selected answer options in json format.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     * @param int $pageid
     * @param string $aoids answer option ids selected by the student
     * @return array
     * @throws invalid_parameter_exception
     * @throws dml_exception
     */
    public static function execute(int $pageid, string $aoids): array {

        [
            'pageid' => $pageid,
            'aoids' => $aoids,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'aoids' => $aoids,
        ]);
        $cm = helper::get_cm_by_pageid($pageid);
        $cmcontext = \context_module::instance($cm->id);
        self::validate_context($cmcontext);
        require_capability('mod/mootimeter:view', $cmcontext);

        try {
            $aoids = json_decode($aoids);

            $helper = new helper();
            $page = $helper->get_page($pageid);

            $maxanswersperuser = helper::get_tool_config($page->id, "maxanswersperuser");
            if (count($aoids) > $maxanswersperuser && (int)$maxanswersperuser > 0) {
                return ['code' => helper::ERRORCODE_TO_MANY_ANSWERS, 'string' => get_string(
                    'error_to_many_answers',
                    'mootimetertool_quiz',
                    $maxanswersperuser
                ), ];
            }

            $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;
            $toolhelper = new $classname();
            $toolhelper->insert_answer($page, $aoids);

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
            'What to do after selecting an answer option'
        );
    }
}
