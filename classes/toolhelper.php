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
     * Get the pages of the tool a user answered.
     *
     * @param int[] $pageids pages of this tool
     * @param int $userid
     * @return int[]
     */
    public function get_answered_pageids(array $pageids, int $userid): array {
        global $DB;

        if (!$pageids) {
            return [];
        }
        [$insql, $params] = $DB->get_in_or_equal($pageids, SQL_PARAMS_NAMED);
        $params['userid'] = $userid;
        return array_map('intval', $DB->get_fieldset_sql(
            "SELECT DISTINCT pageid FROM {" . $this->get_answer_table() . "} WHERE pageid $insql AND usermodified = :userid",
            $params
        ));
    }

    /**
     * Get the pages of the tool a user answered correctly. Tools without correct answers return none.
     *
     * @param int[] $pageids pages of this tool
     * @param int $userid
     * @return int[]
     */
    public function get_correct_pageids(array $pageids, int $userid): array {
        return [];
    }

    /**
     * Whether the tool lets participants answer, in addition to the common submission state of the page.
     *
     * @param \stdClass $page
     * @return bool
     */
    public function is_submission_open_tool(\stdClass $page): bool {
        return true;
    }

    /**
     * React on a moderator opening the submission of a page, e.g. by undoing a tool specific closing.
     *
     * @param \stdClass $page
     */
    public function hook_submission_opened(\stdClass $page): void {
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
     * Whether the answers of a page are anonymous.
     *
     * @param \stdClass $page
     * @return bool
     */
    public function is_anonymous(\stdClass $page): bool {
        return !empty(self::get_tool_config($page, 'anonymousmode'));
    }

    /**
     * Order the answers of an anonymous page so that neither the order nor the ids reveal when they were submitted.
     *
     * Otherwise the order could be matched with the completion times of the participants. The order is stable, so
     * pagination still works.
     *
     * @param \stdClass $page
     * @param array $items answers
     * @param \Closure $idof returns the id of an answer
     * @return array the answers in a pseudo-random order
     */
    public function order_anonymously(\stdClass $page, array $items, \Closure $idof): array {
        $key = get_config('mod_mootimeter', 'anonymousorderkey');
        if (!$key) {
            $key = random_string(32);
            set_config('anonymousorderkey', $key, 'mod_mootimeter');
        }
        $rank = fn($item): string => hash_hmac('sha256', $page->id . ':' . $idof($item), $key);
        usort($items, fn($a, $b): int => strcmp($rank($a), $rank($b)));
        return $items;
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
