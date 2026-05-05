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
 * External service definitions for mootimetertool_openended.
 *
 * @package     mootimetertool_openended
 * @copyright   2026, ISB Bayern
 * @author      Benedikt Blumenfelder
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mootimetertool_openended_store_answer' => [
        'classname'     => 'mootimetertool_openended\external\store_answer',
        'methodname'    => 'execute',
        'description'   => 'Store an open-ended text answer.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mootimetertool_openended_get_answers' => [
        'classname'     => 'mootimetertool_openended\external\get_answers',
        'methodname'    => 'execute',
        'description'   => 'Get all visible open-ended answers including reactions.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mootimetertool_openended_toggle_reaction' => [
        'classname'     => 'mootimetertool_openended\external\toggle_reaction',
        'methodname'    => 'execute',
        'description'   => 'Toggle the current users emoji reaction on an answer.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mootimetertool_openended_toggle_answer_visibility' => [
        'classname'     => 'mootimetertool_openended\external\toggle_answer_visibility',
        'methodname'    => 'execute',
        'description'   => 'Toggle the visibility of a single answer (moderator only).',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
];
