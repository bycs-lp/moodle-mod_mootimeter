<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Upgrade steps for mod_mootimeter.
 *
 * @package     mod_mootimeter
 * @copyright   2025 ISB Bayern
 * @author      Thomas Schönlein
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * Execute mod_mootimeter upgrade steps.
 *
 * @param int $oldversion The version we are upgrading from.
 * @return bool
 */
function xmldb_mootimeter_upgrade(int $oldversion): bool {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2025110900) {
        // Add missing foreign key to mootimeter_tool_settings.pageid.
        $table = new xmldb_table('mootimeter_tool_settings');
        $key = new xmldb_key('pageid-id', XMLDB_KEY_FOREIGN, ['pageid'], 'mootimeter_pages', ['id']);
        $dbman->add_key($table, $key);

        upgrade_mod_savepoint(true, 2025110900, 'mootimeter');
    }
    return true;
}
