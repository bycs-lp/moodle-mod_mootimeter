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
 * Backup definition for mootimetertool_openended.
 *
 * @package     mootimetertool_openended
 * @copyright   2026, ISB Bayern
 * @author      Benedikt Blumenfelder
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Backup definition for mootimetertool_openended.
 */
class backup_mootimetertool_openended_subplugin extends backup_subplugin {
    /**
     * Returns the nested structure of this content type.
     *
     * @return \backup_subplugin_element
     */
    protected function define_page_subplugin_structure() {
        $subplugin = $this->get_subplugin_element();
        $userinfo = $this->get_setting_value('userinfo');
        $subpluginwrapper = new backup_nested_element($this->get_recommended_name());
        $subplugin->add_child($subpluginwrapper);

        if ($userinfo) {
            $answers = new backup_nested_element(
                'mootimetertool_openended_answers',
                ['id'],
                ['pageid', 'usermodified', 'answer', 'visible', 'timecreated', 'timemodified']
            );
            $reactions = new backup_nested_element(
                'mootimetertool_openended_reactions',
                ['id'],
                ['answerid', 'pageid', 'usermodified', 'emoji', 'timecreated']
            );
            $subpluginwrapper->add_child($answers);
            $answers->add_child($reactions);

            $answers->set_source_table(
                'mootimetertool_openended_answers',
                ['pageid' => backup::VAR_PARENTID]
            );
            $reactions->set_source_table(
                'mootimetertool_openended_reactions',
                ['answerid' => backup::VAR_PARENTID]
            );
        }
        return $subplugin;
    }
}
