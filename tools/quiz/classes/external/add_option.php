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
 * Web service to add an answer option.
 *
 * @package     mootimetertool_quiz
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_quiz\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mootimeter\local\external_util;
use mootimetertool_quiz\quiz;

/**
 * Web service to add an answer option.
 *
 * @package     mootimetertool_quiz
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class add_option extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'Page id'),
        ]);
    }

    /**
     * Add an empty answer option.
     *
     * @param int $pageid
     * @return array
     */
    public static function execute(int $pageid): array {
        global $USER;

        ['pageid' => $pageid] = self::validate_parameters(self::execute_parameters(), ['pageid' => $pageid]);
        [$page] = external_util::validate_page($pageid, true);
        $quiz = quiz::get_for_page($page);

        $record = (object) [
            'pageid' => $page->id,
            'usermodified' => $USER->id,
            'optiontext' => '',
            'timecreated' => time(),
        ];
        if ($quiz->supports_correct_options()) {
            $record->optioniscorrect = 0;
        }

        return ['id' => $quiz->store_answer_option($record)];
    }

    /**
     * Describes the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'id' => new external_value(PARAM_INT, 'Id of the new option'),
        ]);
    }
}
