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

namespace mod_mootimeter\route\scope\mootimeter;

/**
 * The mod_mootimeter:mootimeter:write scope: Manage mootimeter pages, settings and answers.
 *
 * @package    mod_mootimeter
 * @copyright  2026 ISB Bayern
 * @author     Dr. Peter Mayer
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
#[\core\router\scope\identifier_attribute('write')]
#[\core\router\scope\summary_attribute('mootimeter_write_scope_summary', 'mod_mootimeter')]
#[\core\router\scope\description_attribute('mootimeter_write_scope_desc', 'mod_mootimeter')]
class write extends abstract_scope {
}
