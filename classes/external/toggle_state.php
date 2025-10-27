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
 * Web service to get all answers.
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
use mod_mootimeter\helper;

/**
 * Web service to toggle a state.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class toggle_state extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
            'statename' => new external_value(PARAM_TEXT, 'The name of the state to be toggled', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute the service.
     *
     * @param int $pageid
     * @param string $statename
     * @return array
     */
    public static function execute(int $pageid, string $statename): array {

        [
            'pageid' => $pageid,
            'statename' => $statename,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
            'statename' => $statename,
        ]);
        $cm = helper::get_cm_by_pageid($pageid);
        self::validate_context(\context_module::instance($cm->id));

        try {
            $mtmhelper = new helper();
            $page = $mtmhelper->get_page($pageid);
            $newstate = $mtmhelper->toggle_state($page, $statename);

            if ($newstate == -1) {
                return ['code' => 403, 'string' => 'Forbidden'];
            }

            $return = ['code' => 200, 'string' => 'ok', 'newstate' => $newstate];
        } catch (\Exception $e) {
            $return = ['code' => 500, 'string' => $e->getMessage(), 'newstate' => 0];
        }
        return $return;
    }

    /**
     * Describes the return structure of the service..
     *
     * @return external_single_structure
     */
    public static function execute_returns() {
        return new external_single_structure(
            [
                'code' => new external_value(PARAM_INT, 'Return code of storage process.'),
                'string' => new external_value(PARAM_TEXT, 'Return string of storage process.'),
                'newstate' => new external_value(PARAM_INT, 'The newly setted state'),
            ],
            'Information about state toggle process'
        );
    }
}
