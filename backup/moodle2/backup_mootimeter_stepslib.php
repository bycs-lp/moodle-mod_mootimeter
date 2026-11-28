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
 * Backup steps for mod_mootimeter
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_mootimeter_activity_structure_step extends backup_activity_structure_step {
    /**
     * Defines the XML structure for mootimeter backups
     *
     * @return backup_nested_element
     */
    protected function define_structure(): backup_nested_element {
        $mootimeter = new backup_nested_element(
            'mootimeter',
            ['id'],
            [
                'course', 'name', 'intro', 'introformat', 'timecreated', 'timemodified', 'completionanswerpages',
                'completionanswerall', 'completioncorrectpages',
            ]
        );
        $mootimeter->set_source_table('mootimeter', ['id' => backup::VAR_ACTIVITYID]);
        $mootimeter->annotate_files('mod_mootimeter', 'intro', null);

        $pages = new backup_nested_element('pages');
        $page = new backup_nested_element(
            'page',
            ['id'],
            ['instance', 'tool', 'title', 'visible', 'sortorder', 'timecreated', 'timemodified']
        );
        $mootimeter->add_child($pages);
        $pages->add_child($page);
        $page->set_source_table('mootimeter_pages', ['instance' => backup::VAR_ACTIVITYID]);

        $toolsettings = new backup_nested_element('tool_settings');
        $toolsetting = new backup_nested_element(
            'tool_setting',
            ['id'],
            ['tool', 'pageid', 'name', 'value', 'timemodified']
        );
        $page->add_child($toolsettings);
        $toolsettings->add_child($toolsetting);
        $toolsetting->set_source_table('mootimeter_tool_settings', ['pageid' => backup::VAR_PARENTID]);

        $this->add_subplugin_structure('mootimetertool', $page, true);

        return $this->prepare_activity_structure($mootimeter);
    }
}
