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
 * Upgrade steps for mootimetertool_openended.
 *
 * @package     mootimetertool_openended
 * @copyright   2026, ISB Bayern
 * @author      Benedikt Blumenfelder
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Upgrade routine.
 *
 * @param int $oldversion previously installed version
 * @return bool
 */
function xmldb_mootimetertool_openended_upgrade($oldversion) {
    global $DB;
    $dbman = $DB->get_manager();

    if ($oldversion < 2026050500) {
        // Switch the reactions table from "one row per (answer, user, emoji)" to
        // "one row per (answer, user)" - users are limited to a single reaction per bubble.
        $table = new xmldb_table('mootimetertool_openended_reactions');

        // Dedupe: keep the most recent reaction per (answerid, usermodified).
        $duplicates = $DB->get_records_sql(
            "SELECT MIN(id) AS keepid, answerid, usermodified, COUNT(id) AS cnt
               FROM {mootimetertool_openended_reactions}
           GROUP BY answerid, usermodified
             HAVING COUNT(id) > 1"
        );
        foreach ($duplicates as $row) {
            $latest = $DB->get_record_sql(
                "SELECT id FROM {mootimetertool_openended_reactions}
                  WHERE answerid = :answerid AND usermodified = :usermodified
               ORDER BY timecreated DESC, id DESC",
                ['answerid' => $row->answerid, 'usermodified' => $row->usermodified],
                IGNORE_MULTIPLE
            );
            $DB->delete_records_select(
                'mootimetertool_openended_reactions',
                'answerid = :answerid AND usermodified = :usermodified AND id <> :keepid',
                ['answerid' => $row->answerid, 'usermodified' => $row->usermodified, 'keepid' => $latest->id]
            );
        }

        // Drop the old unique index over (answerid, usermodified, emoji).
        $oldindex = new xmldb_index('reaction_unique', XMLDB_INDEX_UNIQUE, ['answerid', 'usermodified', 'emoji']);
        if ($dbman->index_exists($table, $oldindex)) {
            $dbman->drop_index($table, $oldindex);
        }

        // Create the new unique index over (answerid, usermodified).
        $newindex = new xmldb_index('reaction_unique', XMLDB_INDEX_UNIQUE, ['answerid', 'usermodified']);
        if (!$dbman->index_exists($table, $newindex)) {
            $dbman->add_index($table, $newindex);
        }

        upgrade_plugin_savepoint(true, 2026050500, 'mootimetertool', 'openended');
    }

    return true;
}
