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
 * External service definitions for mootimetertool_quiz.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mootimetertool_quiz_get_page_data' => [
        'classname'     => 'mootimetertool_quiz\external\get_page_data',
        'description'   => 'Get the options, the own selection and the settings of a quiz or poll page.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mootimetertool_quiz_store_answer' => [
        'classname'     => 'mootimetertool_quiz\external\store_answer',
        'description'   => 'Store the selected options of the current user.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mootimetertool_quiz_get_results' => [
        'classname'     => 'mootimetertool_quiz\external\get_results',
        'description'   => 'Get the chart data of a quiz or poll page.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mootimetertool_quiz_add_option' => [
        'classname'     => 'mootimetertool_quiz\external\add_option',
        'description'   => 'Add an answer option.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mootimetertool_quiz_update_option' => [
        'classname'     => 'mootimetertool_quiz\external\update_option',
        'description'   => 'Change the text or the correctness of an answer option.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mootimetertool_quiz_delete_option' => [
        'classname'     => 'mootimetertool_quiz\external\delete_option',
        'description'   => 'Delete an answer option including its answers.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mootimetertool_quiz_get_overview' => [
        'classname'     => 'mootimetertool_quiz\external\get_overview',
        'description'   => 'Get the answers of all participants.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mootimetertool_quiz_update_user_answer' => [
        'classname'     => 'mootimetertool_quiz\external\update_user_answer',
        'description'   => 'Change the selection of a participant.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
];
