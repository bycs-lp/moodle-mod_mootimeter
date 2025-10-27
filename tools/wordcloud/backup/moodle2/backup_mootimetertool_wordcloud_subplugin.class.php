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
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Stefan Hanauska <stefan.hanauska@csg-in.de>
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class backup_mootimetertool_wordcloud_subplugin extends backup_subplugin {
    /**
     * Returns the nested structure of this content type
     * @return \backup_subplugin_element
     */
    protected function define_page_subplugin_structure() {
        $subplugin = $this->get_subplugin_element();
        $userinfo = $this->get_setting_value('userinfo');
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $subplugin->add_child($subpluginwrapper);

        if ($userinfo) {
            $subpluginwordcloudanswers = new backup_nested_element(
                'mootimetertool_wordcloud_answers',
                ['id'],
                ['pageid', 'usermodified', 'answer', 'timecreated', 'timemodified']
            );
            $subpluginwrapper->add_child($subpluginwordcloudanswers);
            $subpluginwordcloudanswers->set_source_table('mootimetertool_wordcloud_answers', ['pageid' => backup::VAR_PARENTID]);
        }
        return $subplugin;
    }
}
