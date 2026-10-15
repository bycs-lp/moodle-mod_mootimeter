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
 * Web service to change the text or the correctness of an answer option.
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
 * Web service to change the text or the correctness of an answer option.
 *
 * @package     mootimetertool_quiz
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_option extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'Page id'),
            'optionid' => new external_value(PARAM_INT, 'Option id'),
            'text' => new external_value(PARAM_TEXT, 'Option text'),
            'iscorrect' => new external_value(PARAM_BOOL, 'Whether the option is correct'),
        ]);
    }

    /**
     * Update the option.
     *
     * @param int $pageid
     * @param int $optionid
     * @param string $text
     * @param bool $iscorrect
     * @return array
     */
    public static function execute(int $pageid, int $optionid, string $text, bool $iscorrect): array {
        ['pageid' => $pageid, 'optionid' => $optionid, 'text' => $text, 'iscorrect' => $iscorrect] = self::validate_parameters(
            self::execute_parameters(),
            ['pageid' => $pageid, 'optionid' => $optionid, 'text' => $text, 'iscorrect' => $iscorrect]
        );
        [$page] = external_util::validate_page($pageid, true);
        $quiz = quiz::get_for_page($page);

        // The record must belong to the page, store_answer_option() enforces this with MUST_EXIST.
        $quiz->store_answer_option((object) [
            'id' => $optionid,
            'pageid' => $page->id,
            'optiontext' => $text,
            'optioniscorrect' => ($quiz->supports_correct_options() && $iscorrect) ? 1 : 0,
        ]);

        return ['success' => true];
    }

    /**
     * Describes the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the option has been updated'),
        ]);
    }
}
