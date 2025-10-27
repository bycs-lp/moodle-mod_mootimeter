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
 * Backup definition for this tool
 * @package     mootimetertool_poll
 * @copyright   2024, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_mootimetertool_poll_subplugin extends backup_subplugin {
    /**
     * Returns the nested structure of this content type
     * @return \backup_subplugin_element
     */
    protected function define_page_subplugin_structure() {
        $subplugin = $this->get_subplugin_element();
        $userinfo = $this->get_setting_value('userinfo');
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $subplugin->add_child($subpluginwrapper);

        $subpluginpolloptions = new backup_nested_element(
            'mootimetertool_poll_options',
            ['id'],
            ['pageid', 'optiontext', 'timecreated', 'timemodified']
        );
        $subpluginwrapper->add_child($subpluginpolloptions);
        $subpluginpolloptions->set_source_table('mootimetertool_poll_options', ['pageid' => backup::VAR_PARENTID]);

        if ($userinfo) {
            $subpluginpollanswers = new backup_nested_element(
                'mootimetertool_poll_answers',
                ['id'],
                ['pageid', 'usermodified', 'optionid', 'timecreated', 'timemodified']
            );
            $subpluginwrapper->add_child($subpluginpollanswers);
            $subpluginpollanswers->set_source_table('mootimetertool_poll_answers', ['pageid' => backup::VAR_PARENTID]);
        }

        return $subplugin;
    }
}
