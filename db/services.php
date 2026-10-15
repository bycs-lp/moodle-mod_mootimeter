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
 * External service definitions for mod_mootimeter.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mod_mootimeter_get_pages' => [
        'classname'     => 'mod_mootimeter\external\get_pages',
        'description'   => 'Get the page list of a mootimeter instance.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mod_mootimeter_get_page' => [
        'classname'     => 'mod_mootimeter\external\get_page',
        'description'   => 'Get the common data of a page.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mod_mootimeter_get_state' => [
        'classname'     => 'mod_mootimeter\external\get_state',
        'description'   => 'Get the change timestamps used for live updates.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mod_mootimeter_add_page' => [
        'classname'     => 'mod_mootimeter\external\add_page',
        'description'   => 'Add a new page.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mod_mootimeter_delete_page' => [
        'classname'     => 'mod_mootimeter\external\delete_page',
        'description'   => 'Delete a page.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mod_mootimeter_reorder_page' => [
        'classname'     => 'mod_mootimeter\external\reorder_page',
        'description'   => 'Move a page to another position.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mod_mootimeter_set_page_visibility' => [
        'classname'     => 'mod_mootimeter\external\set_page_visibility',
        'description'   => 'Show or hide a page for participants.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mod_mootimeter_store_setting' => [
        'classname'     => 'mod_mootimeter\external\store_setting',
        'description'   => 'Store a page setting.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mod_mootimeter_delete_answers' => [
        'classname'     => 'mod_mootimeter\external\delete_answers',
        'description'   => 'Delete all answers, the answers of a user or a single answer of a page.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
];
