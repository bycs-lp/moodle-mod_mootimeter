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
 * Restore steps for mod_mootimeter
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_mootimeter_activity_structure_step extends restore_activity_structure_step {
    /**
     * List of elements that can be restored
     *
     * @return array
     * @throws base_step_exception
     */
    protected function define_structure(): array {
        $paths = [];
        $paths[] = new restore_path_element('mootimeter', '/activity/mootimeter');

        $page = new restore_path_element('page', '/activity/mootimeter/pages/page');
        $paths[] = $page;
        $paths[] = new restore_path_element('tool_setting', '/activity/mootimeter/pages/page/tool_settings/tool_setting');

        $this->add_subplugin_structure('mootimetertool', $page);

        return $this->prepare_activity_structure($paths);
    }

    /**
     * Restore a mootimeter record.
     *
     * @param array|object $data
     * @throws base_step_exception
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_mootimeter($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->course = $this->get_courseid();

        $newid = $DB->insert_record('mootimeter', $data);
        $this->set_mapping('mootimeter_id', $oldid, $newid);
        $this->apply_activity_instance($newid);
    }

    /**
     * Restore a page record.
     *
     * @param array|object $data
     * @throws base_step_exception
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_page($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->instance = $this->get_mappingid('mootimeter_id', $data->instance);
        $newid = $DB->insert_record('mootimeter_pages', $data);
        $this->set_mapping('mootimeter_page_id', $oldid, $newid);
    }

    /**
     * Restore a tool_settings record.
     *
     * @param array|object $data
     * @throws base_step_exception
     * @throws dml_exception
     * @throws restore_step_exception
     */
    protected function process_tool_setting($data): void {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;

        $data->pageid = $this->get_mappingid('mootimeter_page_id', $data->pageid);

        $newid = $DB->insert_record('mootimeter_tool_settings', $data);
        $this->set_mapping('mootimeter_tool_settings_id', $oldid, $newid);
    }

    /**
     * After restoring this instance restore related files too.
     *
     * @return void
     */
    protected function after_execute() {
        // Add mootimeter related files, no need to match by itemname (just internally handled context).
        $this->add_related_files('mod_mootimeter', 'intro', null);
    }
}
