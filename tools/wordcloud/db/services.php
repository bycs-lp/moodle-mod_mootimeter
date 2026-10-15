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
 * External service definitions for mootimetertool_wordcloud.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'mootimetertool_wordcloud_get_page_data' => [
        'classname'     => 'mootimetertool_wordcloud\external\get_page_data',
        'description'   => 'Get the own answers and the settings of a wordcloud page.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mootimetertool_wordcloud_store_answer' => [
        'classname'     => 'mootimetertool_wordcloud\external\store_answer',
        'description'   => 'Store an answer of the current user.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mootimetertool_wordcloud_get_results' => [
        'classname'     => 'mootimetertool_wordcloud\external\get_results',
        'description'   => 'Get the counted answers for the wordcloud.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:view',
    ],
    'mootimetertool_wordcloud_get_overview' => [
        'classname'     => 'mootimetertool_wordcloud\external\get_overview',
        'description'   => 'Get the answers of all participants.',
        'type'          => 'read',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
    'mootimetertool_wordcloud_update_answer' => [
        'classname'     => 'mootimetertool_wordcloud\external\update_answer',
        'description'   => 'Change an answer of a participant.',
        'type'          => 'write',
        'ajax'          => true,
        'capabilities'  => 'mod/mootimeter:moderator',
    ],
];
