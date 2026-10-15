<?php
// This file is part of Moodle - https://moodle.org/
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
// along with Moodle.  If not, see <https://www.gnu.org/licenses/>.

/**
 * Pluginlib
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_wordcloud;

use dml_exception;

/**
 * Pluginlib
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wordcloud extends \mod_mootimeter\toolhelper {
    /**
     * @var string Answer cloum
     */
    const ANSWER_COLUMN = "answer";
    /**
     * @var string Answer table
     */
    const ANSWER_TABLE = "mootimetertool_wordcloud_answers";

    /**
     * @var string Name of table column of the answer table where the user id is stored
     */
    const ANSWER_USERID_COLUMN = 'usermodified';

    /** Show Results live */
    const MTMT_VIEW_RESULT_LIVE = 1;
    /** Show Results after teacher permission */
    const MTMT_VIEW_RESULT_TEACHERPERMISSION = 2;

    /**
     * Get the tools answer column.
     * @return string
     */
    public function get_answer_column() {
        return self::ANSWER_COLUMN;
    }

    /**
     * Get the tools answer table.
     * @return string
     */
    public function get_answer_table() {
        return self::ANSWER_TABLE;
    }

    /**
     * Get the userid column name in the answer table of the tool.
     *
     * @return ?string the column name where the user id is stored in the answer table, null if no user id is stored
     */
    public function get_answer_userid_column(): ?string {
        return self::ANSWER_USERID_COLUMN;
    }

    /**
     * Will be executed after the page is created.
     * @param object $page
     * @return void
     */
    public function hook_after_new_page_created(object $page): void {
        return;
    }

    /**
     * Get the tool specific page settings that may be stored by moderators.
     *
     * @return array of setting name => PARAM_* type
     */
    public function get_setting_definitions(): array {
        return [
            'maxinputsperuser' => PARAM_INT,
            'allowduplicateanswers' => PARAM_BOOL,
        ];
    }

    /**
     * Export the own answers of the current user and the settings of the page.
     *
     * @param object $page
     * @return array
     */
    public function export_page_data(object $page): array {
        global $USER;

        $answers = [];
        foreach ($this->get_user_answers(self::ANSWER_TABLE, $page->id, self::ANSWER_COLUMN, $USER->id) as $answer) {
            $answers[] = ['id' => (int) $answer->id, 'text' => (string) $answer->answer];
        }

        return [
            'answers' => $answers,
            'maxinputsperuser' => max(0, (int) self::get_tool_config($page, 'maxinputsperuser')),
            'allowduplicateanswers' => !empty(self::get_tool_config($page, 'allowduplicateanswers')),
        ];
    }

    /**
     * Export the counted answers for the wordcloud.
     *
     * The answers are only exported if the current user may see the results.
     *
     * @param object $page
     * @return array
     */
    public function export_results(object $page): array {
        $visible = (bool) $this->get_teacherpermission_to_view($page);
        $words = [];
        if ($visible) {
            foreach ($this->get_answers_grouped(self::ANSWER_TABLE, ['pageid' => $page->id]) as $row) {
                $row = (array) $row;
                $words[] = ['text' => (string) $row['answer'], 'count' => (int) $row['cnt']];
            }
        }
        return ['visible' => $visible, 'words' => $words];
    }

    /**
     * Export the answer overview for moderators.
     *
     * @param object $page
     * @return array
     */
    public function export_overview(object $page): array {
        $anonymous = !empty(self::get_tool_config($page, 'anonymousmode'));

        $rows = [];
        foreach ($this->get_answers(self::ANSWER_TABLE, $page->id, self::ANSWER_COLUMN) as $answer) {
            $fullname = get_string('anonymous_name', 'mod_mootimeter');
            if (!$anonymous) {
                $user = $this->get_user_by_id($answer->usermodified);
                $fullname = $user ? fullname($user) : '';
            }
            $rows[] = [
                'answerid' => (int) $answer->id,
                'fullname' => $fullname,
                'text' => (string) $answer->answer,
                'time' => userdate($answer->timecreated, get_string('strftimedatetimeshortaccurate', 'core_langconfig')),
            ];
        }

        return ['anonymous' => $anonymous, 'rows' => $rows];
    }

    /**
     * Change the text of an answer. Only moderators may change answers.
     *
     * @param object $page
     * @param int $answerid
     * @param string $text
     * @return void
     */
    public function update_answer(object $page, int $answerid, string $text): void {
        global $DB;

        $context = \context_module::instance(self::get_cm_by_pageid($page->id)->id);
        require_capability('mod/mootimeter:moderator', $context);

        $answer = $DB->get_record(self::ANSWER_TABLE, ['id' => $answerid, 'pageid' => $page->id], '*', MUST_EXIST);
        $answer->answer = $text;
        $answer->timemodified = time();
        $DB->update_record(self::ANSWER_TABLE, $answer);

        $this->clear_caches($page->id);
        $this->notify_data_changed($page, 'answers');
    }

    /**
     * Page type specivic insert_answer
     *
     * @param object $page
     * @param string $answer
     * @return void
     */
    public function insert_answer(object $page, $answer): void {

        $record = new \stdClass();
        $record->pageid = $page->id;
        $record->answer = $answer;
        $record->timecreated = time();

        $this->store_answer(self::ANSWER_TABLE, $record);
    }

    /**
     * Get a pure list of unique answers, without id etc.
     *
     * @param int $pageid
     * @param int $userid
     * @return array
     * @throws dml_exception
     */
    public function get_answer_list_array(int $pageid, int $userid = 0): array {
        global $DB;

        $params = [
            'pageid' => $pageid,
        ];

        if (!empty($userid)) {
            $params['usermodified'] = $userid;
        }

        return array_keys($DB->get_records_menu(self::ANSWER_TABLE, $params, "", "answer"));
    }

    /**
     * Delete all DB entries related to a specific page.
     * @param object $page
     * @return bool
     */
    public function delete_page_tool(object $page) {
        global $DB;
        try {
            // Table not written yet.
            $DB->delete_records(self::ANSWER_TABLE, ['pageid' => $page->id]);
        } catch (\Exception $e) {
            // Todo handling.
            echo 'Something went wrong';
            return false;
        }
        return true;
    }

    /**
     * Delete answers by condition.
     *
     * @param object $page
     * @param array $conditions
     * @return void
     */
    public function delete_answers_tool(object $page, array $conditions): void {
        global $DB;
        $DB->delete_records($this->get_answer_table(), $conditions);
        $this->clear_caches($page->id);
    }
}
