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
 * Web service to toggle a user's reaction on an open-ended answer.
 *
 * @package     mootimetertool_openended
 * @copyright   2026, ISB Bayern
 * @author      Benedikt Blumenfelder
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_openended\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;
use mod_mootimeter\helper;
use mootimetertool_openended\openended;

/**
 * Web service to toggle a user's reaction on an open-ended answer.
 */
class toggle_reaction extends external_api {
    /**
     * Parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'answerid' => new external_value(PARAM_INT, 'The answer to react to.', VALUE_REQUIRED),
            'emoji' => new external_value(PARAM_ALPHANUMEXT, 'Emoji slug.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $answerid
     * @param string $emoji
     * @return array
     */
    public static function execute(int $answerid, string $emoji): array {
        global $DB, $USER;

        [
            'answerid' => $answerid,
            'emoji' => $emoji,
        ] = self::validate_parameters(self::execute_parameters(), [
            'answerid' => $answerid,
            'emoji' => $emoji,
        ]);

        $answer = $DB->get_record(
            openended::ANSWER_TABLE,
            ['id' => $answerid],
            'id, pageid, visible',
            MUST_EXIST
        );

        $cm = helper::get_cm_by_pageid($answer->pageid);
        $cmcontext = \context_module::instance($cm->id);
        self::validate_context($cmcontext);
        require_capability('mod/mootimeter:view', $cmcontext);

        // Reactions are only allowed when the page enabled them and the answer is visible.
        if (empty(helper::get_tool_config($answer->pageid, 'enablereactions'))) {
            throw new \moodle_exception('error_reactions_disabled', 'mootimetertool_openended');
        }
        if (empty($answer->visible) && !has_capability('mod/mootimeter:moderator', $cmcontext)) {
            throw new \moodle_exception('error_answer_hidden', 'mootimetertool_openended');
        }

        $tool = new openended();
        return $tool->toggle_reaction($answerid, $emoji, $USER->id);
    }

    /**
     * Returns.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'answerid' => new external_value(PARAM_INT, 'Answer id'),
            'isown' => new external_value(PARAM_BOOL, 'Whether the answer belongs to the calling user'),
            'reactions' => new external_multiple_structure(
                new external_single_structure([
                    'key' => new external_value(PARAM_ALPHANUMEXT, 'Emoji slug'),
                    'symbol' => new external_value(PARAM_RAW, 'Emoji glyph'),
                    'count' => new external_value(PARAM_INT, 'Total count for this emoji on this answer'),
                    'mine' => new external_value(PARAM_BOOL, 'Whether the calling user is now reacting with this emoji'),
                    'isown' => new external_value(PARAM_BOOL, 'Whether the answer belongs to the calling user'),
                ]),
                'Full reaction state for the affected bubble.'
            ),
        ]);
    }
}
