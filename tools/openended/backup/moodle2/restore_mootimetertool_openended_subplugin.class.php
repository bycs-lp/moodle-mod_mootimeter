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
 * Restore definition for mootimetertool_openended.
 *
 * @package     mootimetertool_openended
 * @copyright   2026, ISB Bayern
 * @author      Benedikt Blumenfelder
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Restore definition for mootimetertool_openended.
 */
class restore_mootimetertool_openended_subplugin extends restore_subplugin {
    /**
     * Returns the paths to be handled by the subplugin at mootimeter level.
     *
     * @return array
     */
    protected function define_page_subplugin_structure() {
        $paths = [];
        $paths[] = new restore_path_element(
            'openended_answer',
            $this->get_pathfor('/mootimetertool_openended_answers')
        );
        $paths[] = new restore_path_element(
            'openended_reaction',
            $this->get_pathfor('/mootimetertool_openended_answers/mootimetertool_openended_reactions')
        );
        return $paths;
    }

    /**
     * Process a restored answer record.
     *
     * @param array $data
     */
    public function process_openended_answer($data) {
        global $DB;

        $data = (object) $data;
        $oldid = $data->id;
        $data->pageid = $this->get_mappingid('mootimeter_page_id', $data->pageid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified, 0);
        if (!isset($data->visible)) {
            $data->visible = 1;
        }
        $newid = $DB->insert_record('mootimetertool_openended_answers', $data);
        $this->set_mapping('mootimetertool_openended_answer', $oldid, $newid);
    }

    /**
     * Process a restored reaction record.
     *
     * @param array $data
     */
    public function process_openended_reaction($data) {
        global $DB;

        $data = (object) $data;
        $data->answerid = $this->get_mappingid('mootimetertool_openended_answer', $data->answerid);
        $data->pageid = $this->get_mappingid('mootimeter_page_id', $data->pageid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified, 0);
        if (empty($data->answerid)) {
            return;
        }
        $DB->insert_record('mootimetertool_openended_reactions', $data);
    }
}
