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
 * Web service to store the selected options of a participant.
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
use mod_mootimeter\helper;
use mod_mootimeter\local\external_util;
use mootimetertool_quiz\quiz;

/**
 * Web service to store the selected options of a participant.
 *
 * @package     mootimetertool_quiz
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
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
            'pageid' => new external_value(PARAM_INT, 'Page id'),
            'optionids' => new external_multiple_structure(new external_value(PARAM_INT, 'Selected option id')),
        ]);
    }

    /**
     * Store the answer.
     *
     * @param int $pageid
     * @param int[] $optionids
     * @return array
     */
    public static function execute(int $pageid, array $optionids): array {
        ['pageid' => $pageid, 'optionids' => $optionids] = self::validate_parameters(
            self::execute_parameters(),
            ['pageid' => $pageid, 'optionids' => $optionids]
        );
        [$page] = external_util::validate_page($pageid);
        $quiz = quiz::get_for_page($page);

        $optionids = array_values(array_intersect(
            array_unique($optionids),
            array_map('intval', array_keys($quiz->get_answer_options($page->id)))
        ));
        if (empty($optionids)) {
            return [
                'code' => helper::ERRORCODE_EMPTY_ANSWER,
                'message' => get_string('atleastoneanswer', 'mootimetertool_quiz'),
            ];
        }

        $maxanswers = $quiz->get_max_answers($page);
        if ($maxanswers > 0 && count($optionids) > $maxanswers) {
            return [
                'code' => helper::ERRORCODE_TO_MANY_ANSWERS,
                'message' => get_string('error_to_many_answers', 'mootimetertool_quiz', $maxanswers),
            ];
        }

        if ($quiz->is_answer_correction_shown($page)) {
            return [
                'code' => helper::ERRORCODE_ANSWERING_CLOSED,
                'message' => get_string('notification_warning_store_answer', 'mod_mootimeter'),
            ];
        }

        $quiz->insert_answer($page, $optionids);

        return [
            'code' => helper::ERRORCODE_OK,
            'message' => get_string('notification_success_store_answer', 'mod_mootimeter'),
        ];
    }

    /**
     * Describes the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'code' => new external_value(PARAM_INT, 'Result code, 200 on success'),
            'message' => new external_value(PARAM_TEXT, 'Message for the participant'),
        ]);
    }
}
