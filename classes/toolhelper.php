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
 * The toolhelper methods must be implemented of each tool.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_mootimeter;

use coding_exception;
use cache_exception;
use dml_exception;
use stdClass;

/**
 * The toolhelper methods must be implemented of each tool.
 *
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class toolhelper extends helper {
    /**
     * Get the tools answer column.
     * @return string
     */
    abstract public function get_answer_column();

    /**
     * Get the tools answer table.
     * @return string
     */
    abstract public function get_answer_table();

    /**
     * Get the userid column name in the answer table of the tool.
     * @return ?string the column name where the user id is stored in the answer table, null if no user id is stored
     */
    public function get_answer_userid_column(): ?string {
        return null;
    }

    /**
     * Insert the answer.
     *
     * @param object $page
     * @param mixed $answer
     * @return void
     */
    abstract public function insert_answer(object $page, $answer);

    /**
     * Delete Page
     *
     * @param object $page
     * @return bool
     */
    abstract public function delete_page_tool(object $page);

    /**
     * Get all parameters that are necessary for rendering the tools view.
     *
     * @param object $page
     * @return array
     */
    abstract public function get_renderer_params(object $page);

    /**
     * Get the settings column.
     *
     * @param object $page
     * @param array $defaultparams
     * @return mixed
     */
    abstract public function get_col_settings_tool_params(object $page, array $defaultparams);

    /**
     * Will be executed after the page is created.
     * @param object $page
     * @return void
     */
    abstract public function hook_after_new_page_created(object $page);

    /**
     * Handels inplace_edit.
     * @param string $itemtype
     * @param string $itemid
     * @param mixed $newvalue
     * @return mixed
     */
    abstract public function handle_inplace_edit(string $itemtype, string $itemid, mixed $newvalue);

    /**
     * Get the rendered answer overview view.
     *
     * @param object $cm
     * @param object $page
     * @return string
     */
    abstract public function get_answer_overview(object $cm, object $page): string;

    /**
     * Checks if a select option is selected.
     *
     * @param int $optionid
     * @param object $config
     * @param string $attribute
     * @return bool
     */
    public function is_option_selected(int $optionid, object $config, string $attribute): bool {

        if (empty($config->{$attribute})) {
            return false;
        }

        if ($config->{$attribute} == $optionid) {
            return true;
        }

        return false;
    }


    /**
     * Helper function to add the form definition for this subplugin to the reset userdata form.
     *
     * @param \MoodleQuickForm $mform The mform object to use
     */
    public function reset_course_form_definition(\MoodleQuickForm $mform): void {
        $shortclassname = preg_replace('/.*\\\\/', '', get_class($this));
        $mform->addElement(
            'checkbox',
            'reset_mootimetertool_' . $shortclassname . '_answers',
            get_string(
                'resetuserdata',
                'mod_mootimeter',
                get_string('pluginname', 'mootimetertool_' . $shortclassname)
            )
        );
    }

    /**
     * Defines the form defaults for the course reset form.
     *
     * @param stdClass $course the course object
     * @return array the array of defaults
     */
    public function reset_course_form_defaults(stdClass $course): array {
        $shortclassname = preg_replace('/.*\\\\/', '', get_class($this));
        return [
            'reset_mootimetertool_' . $shortclassname . '_answers' => 1,
        ];
    }

    /**
     * Callback function for the form definition of the reset course functionality.
     *
     * @param stdClass $data The data from the mform submission
     * @return array array of associative arrays representing the status of the course reset
     */
    public function reset_userdata(stdClass $data): array {
        $shortclassname = preg_replace('/.*\\\\/', '', get_class($this));
        foreach (get_all_instances_in_course('mootimeter', get_course($data->courseid)) as $instance) {
            $pages = $this->get_pages($instance->id);
            $pages = array_filter($pages, fn($page) => $page->tool === $shortclassname);
            foreach ($pages as $page) {
                $this->delete_all_answers($this->get_answer_table(), $page->id);
            }
        }

        return [
            [
                'component' => get_string('pluginname', 'mootimetertool_' . $shortclassname),
                'item' => get_string(
                    'resetuserdata',
                    'mod_mootimeter',
                    get_string('pluginname', 'mootimetertool_' . $shortclassname)
                ),
                'error' => false,
            ],
        ];
    }
}
