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
 * Restore definition for this tool
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class restore_mootimetertool_quiz_subplugin extends restore_subplugin {

    /**
     * Returns the paths to be handled by the subplugin at mootimeter level
     * @return array
     */
    protected function define_page_subplugin_structure() {
        $paths = [];

        $elepath = $this->get_pathfor('/mootimetertool_quiz_options');
        $paths[] = new restore_path_element('quiz_options', $elepath);

        $elepath = $this->get_pathfor('/mootimetertool_quiz_answers');
        $paths[] = new restore_path_element('quiz_answers', $elepath);

        return $paths;
    }

    /**
     * Processes the quiz options
     * @param array $data
     * @return void
     */
    public function process_quiz_options($data) {
        global $DB;

        $data = (object)$data;
        $oldid = $data->id;
        $data->pageid = $this->get_mappingid('mootimeter_page_id', $data->pageid);
        $newid = $DB->insert_record('mootimetertool_quiz_options', $data);
        $this->set_mapping('mootimetertool_quiz_options', $oldid, $newid);
    }

    /**
     * Processes the quiz answers
     * @param array $data
     * @return void
     */
    public function process_quiz_answers($data) {
        global $DB;

        $data = (object)$data;
        $data->pageid = $this->get_mappingid('mootimeter_page_id', $data->pageid);
        $data->optionid = $this->get_mappingid('mootimetertool_quiz_options', $data->optionid);
        $data->usermodified = $this->get_mappingid('user', $data->usermodified, 0);
        $DB->insert_record('mootimetertool_quiz_answers', $data);
    }
}
