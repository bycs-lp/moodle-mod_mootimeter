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

namespace mod_mootimeter;

use core\context\module as context_module;

/**
 * Base class of the tool helpers each mootimetertool subplugin implements.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
abstract class toolhelper extends helper {
    /** @var int Number of users loaded with one query. */
    const USER_CHUNK_SIZE = 500;

    /**
     * Get the column of the answer table holding the answer.
     *
     * @return string
     */
    abstract public function get_answer_column(): string;

    /**
     * Get the answer table of the tool. The user is stored in the column usermodified.
     *
     * @return string
     */
    abstract public function get_answer_table(): string;

    /**
     * Delete the tool specific data of a page.
     *
     * @param \stdClass $page
     */
    abstract public function delete_page_tool(\stdClass $page): void;

    /**
     * Initialise a new page.
     *
     * @param \stdClass $page
     */
    abstract public function hook_after_new_page_created(\stdClass $page): void;

    /**
     * Get the short name of the tool, e.g. "quiz".
     *
     * @return string
     */
    public function get_toolname(): string {
        return preg_replace('/.*\\\\/', '', get_class($this));
    }

    /**
     * Get the ESM specifiers of the React components rendering the tool.
     *
     * Each component is mounted by mod_mootimeter with the props described in mod_mootimeter/lib/types.
     * The optional menu component adds tool specific buttons to the content menu of moderators.
     *
     * @return array with the keys content, settings, results, overview and menu (empty string for none)
     */
    public function get_react_components(): array {
        $prefix = '@moodle/lms/mootimetertool_' . $this->get_toolname() . '/';
        return [
            'content' => $prefix . 'content',
            'settings' => $prefix . 'settings',
            'results' => $prefix . 'results',
            'overview' => $prefix . 'overview',
            'menu' => '',
        ];
    }

    /**
     * Get the results of a page for the CSV download.
     *
     * Each section is written as a header row followed by its rows, sections are separated by an empty row.
     *
     * @param \stdClass $page
     * @param context_module $context
     * @return array list of sections with the keys columns (string[]) and rows (array[])
     */
    public function export_results_csv(\stdClass $page, context_module $context): array {
        return [];
    }

    /**
     * Get the names to show for the authors of answers, with one query per chunk of users.
     *
     * @param \stdClass $page
     * @param int[] $userids
     * @return string[] names keyed by user id, an empty string for users that do not exist anymore
     */
    public function get_participant_names(\stdClass $page, array $userids): array {
        global $DB;

        $userids = array_values(array_unique($userids));
        if (!empty(self::get_tool_config($page, 'anonymousmode'))) {
            return array_fill_keys($userids, get_string('anonymous_name', 'mod_mootimeter'));
        }

        $names = array_fill_keys($userids, '');
        $fields = 'id, ' . \core_user\fields::for_name()->get_sql('', false, '', '', false)->selects;
        foreach (array_chunk($userids, self::USER_CHUNK_SIZE) as $chunk) {
            foreach ($DB->get_records_list('user', 'id', $chunk, '', $fields) as $user) {
                $names[(int) $user->id] = fullname($user);
            }
        }
        return $names;
    }

    /**
     * Add the form definition for this subplugin to the reset userdata form.
     *
     * @param \MoodleQuickForm $mform The mform object to use
     */
    public function reset_course_form_definition(\MoodleQuickForm $mform): void {
        $mform->addElement(
            'checkbox',
            'reset_mootimetertool_' . $this->get_toolname() . '_answers',
            get_string('resetuserdata', 'mod_mootimeter', get_string('pluginname', 'mootimetertool_' . $this->get_toolname()))
        );
    }

    /**
     * Defines the form defaults for the course reset form.
     *
     * @param \stdClass $course the course object
     * @return array the array of defaults
     */
    public function reset_course_form_defaults(\stdClass $course): array {
        return ['reset_mootimetertool_' . $this->get_toolname() . '_answers' => 1];
    }

    /**
     * Delete the answers of all pages of this tool in a course.
     *
     * @param \stdClass $data The data from the mform submission
     * @return array array of associative arrays representing the status of the course reset
     */
    public function reset_userdata(\stdClass $data): array {
        foreach (get_all_instances_in_course('mootimeter', get_course($data->courseid)) as $instance) {
            foreach ($this->get_pages($instance->id) as $page) {
                if ($page->tool === $this->get_toolname()) {
                    $this->delete_all_answers($page);
                }
            }
        }

        $toolname = get_string('pluginname', 'mootimetertool_' . $this->get_toolname());
        return [
            [
                'component' => $toolname,
                'item' => get_string('resetuserdata', 'mod_mootimeter', $toolname),
                'error' => false,
            ],
        ];
    }
}
