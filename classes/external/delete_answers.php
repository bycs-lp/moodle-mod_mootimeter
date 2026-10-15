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
 * Web service to delete answers of a page.
 *
 * @package     mod_mootimeter
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;
use invalid_parameter_exception;
use mod_mootimeter\helper;
use mod_mootimeter\local\external_util;

/**
 * Web service to delete answers of a page.
 *
 * @package     mod_mootimeter
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class delete_answers extends external_api {
    /** @var string Delete all answers of the page. */
    const SCOPE_ALL = 'all';
    /** @var string Delete all answers of the user who gave the referenced answer. */
    const SCOPE_USER = 'user';
    /** @var string Delete only the referenced answer. */
    const SCOPE_SINGLE = 'single';

    /**
     * Describes the parameters.
     *
     * @return external_function_parameters
     */
    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'pageid' => new external_value(PARAM_INT, 'Page id'),
            'scope' => new external_value(PARAM_ALPHA, 'One of all, user, single'),
            'answerid' => new external_value(PARAM_INT, 'Referenced answer for the scopes user and single', VALUE_DEFAULT, 0),
        ]);
    }

    /**
     * Delete answers.
     *
     * Moderators may delete everything, participants only their own answers.
     *
     * @param int $pageid
     * @param string $scope
     * @param int $answerid
     * @return array
     */
    public static function execute(int $pageid, string $scope, int $answerid = 0): array {
        global $DB;

        ['pageid' => $pageid, 'scope' => $scope, 'answerid' => $answerid] = self::validate_parameters(
            self::execute_parameters(),
            ['pageid' => $pageid, 'scope' => $scope, 'answerid' => $answerid]
        );
        [$page, , $context] = external_util::validate_page($pageid);

        $helper = new helper();
        $toolhelper = helper::get_toolhelper($page->tool);
        $table = $toolhelper->get_answer_table();

        switch ($scope) {
            case self::SCOPE_ALL:
                require_capability('mod/mootimeter:moderator', $context);
                $success = $helper->delete_all_answers($table, $page->id);
                break;
            case self::SCOPE_SINGLE:
                $success = $helper->delete_single_answer($table, $page->id, $answerid);
                break;
            case self::SCOPE_USER:
                $answer = $DB->get_record($table, ['id' => $answerid, 'pageid' => $page->id], '*', MUST_EXIST);
                $userid = (int) $answer->{$toolhelper->get_answer_userid_column()};
                $success = $helper->delete_answers_of_user($table, $page->id, $userid);
                break;
            default:
                throw new invalid_parameter_exception('Unknown scope: ' . $scope);
        }

        return ['success' => (bool) $success];
    }

    /**
     * Describes the return structure.
     *
     * @return external_single_structure
     */
    public static function execute_returns(): external_single_structure {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'Whether the answers have been deleted'),
        ]);
    }
}
