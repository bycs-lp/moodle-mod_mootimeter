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
 * Web service returning the data of a quiz or poll page.
 *
 * @package     mootimetertool_quiz
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_quiz\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mootimeter\local\external_util;
use mootimetertool_quiz\quiz;

/**
 * Web service returning the data of a quiz or poll page.
 *
 * @package     mootimetertool_quiz
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class get_page_data extends external_api {
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
     * Return the options, the own selection and the settings of the page.
     *
     * @param int $pageid
     * @return array
     */
    public static function execute(int $pageid): array {
        ['pageid' => $pageid] = self::validate_parameters(self::execute_parameters(), ['pageid' => $pageid]);
        [$page, , $context, $ismoderator] = external_util::validate_page($pageid);

        return quiz::get_for_page($page)->export_page_data($page, $context, $ismoderator);
    }

    /**
     * Describes the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'options' => new external_multiple_structure(new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Option id'),
                'text' => new external_value(PARAM_RAW, 'Raw option text for editing, empty for participants'),
                'texthtml' => new external_value(PARAM_RAW, 'Formatted option text'),
                'iscorrect' => new external_value(PARAM_BOOL, 'Whether the option is correct, only revealed if allowed'),
            ])),
            'selected' => new external_multiple_structure(new external_value(PARAM_INT, 'Option id selected by the user')),
            'maxanswers' => new external_value(PARAM_INT, 'Maximum number of selectable options, 0 for unlimited'),
            'answercorrection' => new external_value(PARAM_BOOL, 'Whether the correct options are revealed'),
            'supportscorrect' => new external_value(PARAM_BOOL, 'Whether options can be marked as correct'),
            'visualization' => new external_value(PARAM_INT, 'Chart type of the results'),
        ]);
    }
}
