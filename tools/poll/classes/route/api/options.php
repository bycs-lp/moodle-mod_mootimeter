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

namespace mootimetertool_poll\route\api;

use core\router\route;

/**
 * The answer options of a poll page.
 *
 * The routes are inherited from the quiz tool.
 *
 * @package    mootimetertool_poll
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[route(
    path: '/pages/{pageId}/options',
    pathtypes: [new \mod_mootimeter\route\parameters\path_page()],
)]
class options extends \mootimetertool_quiz\route\api\options {
    /** @var string The tool of the pages. */
    const TOOL = 'poll';
}
