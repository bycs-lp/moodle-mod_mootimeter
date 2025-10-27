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
 * Web service for deleting all answers of a user.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use dml_transaction_exception;
use mod_mootimeter\helper;
use Throwable;

/**
 * Web service for deleting all answers of a user.
 *
 * @package     mod_mootimeter
 * @copyright   2024 ISB Bayern
 * @author      Philipp Memmel
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_answers_of_user extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
                'pageid' => new external_value(PARAM_INT, 'The new pageid.', VALUE_REQUIRED),
                'thisDataset' => new external_value(PARAM_RAW, 'The dataset of the button.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param mixed $thisdataset
     * @return array
     * @throws dml_transaction_exception
     * @throws Throwable
     */
    public static function execute(int $pageid, $thisdataset): array {
        global $DB;
        [
                'pageid' => $pageid,
                'thisDataset' => $thisdataset
        ] = self::validate_parameters(self::execute_parameters(), [
                'pageid' => $pageid,
                'thisDataset' => $thisdataset,
        ]);
        $cm = helper::get_cm_by_pageid($pageid);
        self::validate_context(\context_module::instance($cm->id));

        try {
            $transaction = $DB->start_delegated_transaction();
            $mtmhelper = new helper();

            $page = $mtmhelper->get_page($pageid);
            $dataset = json_decode($thisdataset);

            $answertable = $mtmhelper->get_tool_answer_table($page);
            $success = $mtmhelper->delete_answers_of_user($answertable, $pageid, intval($dataset->userid));

            if (!$success) {
                $transaction->dispose();
                return ['code' => 403, 'string' => 'Forbidden'];
            }

            $transaction->allow_commit();

            $return = ['code' => 200, 'string' => 'ok', 'reload' => true];
        } catch (\Exception $e) {
            $transaction->rollback($e);
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
                        'reload' => new external_value(PARAM_BOOL, 'Indicator to reload page.', VALUE_DEFAULT, false),
                ],
            'Delete page status.'
        );
    }
}
