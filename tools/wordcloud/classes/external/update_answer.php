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
 * Web service for moderators to change an answer of a participant.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_wordcloud\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mootimeter\local\external_util;
use mootimetertool_wordcloud\wordcloud;

/**
 * Web service for moderators to change an answer of a participant.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class update_answer extends external_api {
    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'Page id'),
            'answerid' => new external_value(PARAM_INT, 'Answer id'),
            'text' => new external_value(PARAM_TEXT, 'New answer'),
        ]);
    }

    /**
     * Change the answer.
     *
     * @param int $pageid
     * @param int $answerid
     * @param string $text
     * @return array
     */
    public static function execute(int $pageid, int $answerid, string $text): array {
        ['pageid' => $pageid, 'answerid' => $answerid, 'text' => $text] = self::validate_parameters(
            self::execute_parameters(),
            ['pageid' => $pageid, 'answerid' => $answerid, 'text' => $text]
        );
        [$page] = external_util::validate_page($pageid, true);

        $text = trim($text);
        if ($text === '') {
            throw new \invalid_parameter_exception(get_string('error_empty_answers', 'mootimetertool_wordcloud'));
        }
        (new wordcloud())->update_answer($page, $answerid, $text);

        return ['success' => true];
    }

    /**
     * Describes the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the answer has been changed'),
        ]);
    }
}
