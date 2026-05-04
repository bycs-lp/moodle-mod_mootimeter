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
 * Web service to fetch all visible open-ended answers and reaction counts.
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
 * Web service to fetch all visible open-ended answers and reaction counts.
 */
class get_answers extends external_api {
    /**
     * Parameters.
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'The page id to obtain results for.', VALUE_REQUIRED),
        ]);
    }

    /**
     * Execute.
     *
     * @param int $pageid
     * @return array
     */
    public static function execute(int $pageid): array {
        global $USER;

        [
            'pageid' => $pageid,
        ] = self::validate_parameters(self::execute_parameters(), [
            'pageid' => $pageid,
        ]);
        $cm = helper::get_cm_by_pageid($pageid);
        $cmcontext = \context_module::instance($cm->id);
        self::validate_context($cmcontext);
        require_capability('mod/mootimeter:view', $cmcontext);

        $helper = new helper();
        $page = $helper->get_page($pageid);
        $teacherpermission = $helper->get_teacherpermission_to_view($page);
        $enablereactions = !empty(helper::get_tool_config($page->id, 'enablereactions'));

        $bubbles = [];
        if ($teacherpermission) {
            $tool = new openended();
            $bubbles = $tool->get_answers_with_reactions($pageid, $USER->id);
        }

        return [
            'lastupdated' => $helper->get_data_changed($page, 'answers'),
            'teacherpermissiontoview' => (int) $teacherpermission,
            'enablereactions' => $enablereactions,
            'bubbles' => $bubbles,
        ];
    }

    /**
     * Returns.
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'lastupdated' => new external_value(PARAM_INT, 'Server-side last-updated timestamp.'),
            'teacherpermissiontoview' => new external_value(PARAM_INT, 'Whether students may currently see results.'),
            'enablereactions' => new external_value(PARAM_BOOL, 'Whether reactions are enabled for this page.'),
            'bubbles' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Answer id'),
                    'answer' => new external_value(PARAM_TEXT, 'Answer text'),
                    'isown' => new external_value(PARAM_BOOL, 'Whether this contribution belongs to the requesting user'),
                    'reactions' => new external_multiple_structure(
                        new external_single_structure([
                            'key' => new external_value(PARAM_ALPHANUMEXT, 'Emoji slug'),
                            'symbol' => new external_value(PARAM_RAW, 'Emoji glyph'),
                            'count' => new external_value(PARAM_INT, 'Number of reactions for this emoji'),
                            'mine' => new external_value(PARAM_BOOL, 'Whether the requesting user has reacted with this emoji'),
                            'isown' => new external_value(PARAM_BOOL, 'Whether the bubble belongs to the requesting user (mirrors bubble.isown)'),
                        ])
                    ),
                ]),
                'List of visible bubbles with reactions'
            ),
        ]);
    }
}
