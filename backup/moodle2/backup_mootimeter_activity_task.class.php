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

defined('MOODLE_INTERNAL') || die();
global $CFG;
require_once($CFG->dirroot . '/mod/mootimeter/backup/moodle2/backup_mootimeter_stepslib.php');

/**
 * Backup class for mod_mootimeter
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_mootimeter_activity_task extends backup_activity_task {
    /**
     * No specific settings for this activity
     */
    protected function define_my_settings(): void {
    }

    /**
     * Defines a backup step to store the instance data in the mootimeter.xml file
     */
    protected function define_my_steps(): void {
        $this->add_step(new backup_mootimeter_activity_structure_step('mootimeter_structure', 'mootimeter.xml'));
    }

    /**
     * Encodes the links to view.php for backup
     *
     * @param string $content
     * @return string
     */
    public static function encode_content_links($content): string {
        global $CFG;

        $base = preg_quote($CFG->wwwroot . '/mod/mootimeter', '#');

        $pattern = "#(" . $base . "\/view.php\?id\=)([0-9]+)#";
        $content = preg_replace($pattern, '$@MOOTIMETERVIEWBYID*$2@$', $content);
        return $content;
    }
}
