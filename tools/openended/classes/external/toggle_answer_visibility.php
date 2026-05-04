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
 * Web service to soft-hide / show a single open-ended answer.
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
 * Web service to soft-hide / show a single open-ended answer.
 */
class toggle_answer_visibility extends external_api {
    /**
     * Parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'answerid' => new external_value(PARAM_INT, 'The answer to toggle.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $answerid
     * @return array
     */
    public static function execute(int $answerid): array {
        global $DB;

        ['answerid' => $answerid] = self::validate_parameters(self::execute_parameters(), [
            'answerid' => $answerid,
        ]);

        $answer = $DB->get_record(
            openended::ANSWER_TABLE,
            ['id' => $answerid],
            'id, pageid',
            MUST_EXIST
        );

        $cm = helper::get_cm_by_pageid($answer->pageid);
        $cmcontext = \context_module::instance($cm->id);
        self::validate_context($cmcontext);
        require_capability('mod/mootimeter:moderator', $cmcontext);

        $tool = new openended();
        $newvisible = $tool->toggle_answer_visibility($answerid);

        return [
            'answerid' => $answerid,
            'visible' => $newvisible,
        ];
    }

    /**
     * Returns.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'answerid' => new external_value(PARAM_INT, 'Answer id'),
            'visible' => new external_value(PARAM_INT, 'New visibility flag (0 or 1)'),
        ]);
    }
}
