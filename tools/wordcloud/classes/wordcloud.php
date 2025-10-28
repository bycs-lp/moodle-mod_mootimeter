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

use coding_exception;
use dml_exception;
use moodle_exception;
use moodle_url;
use pix_icon;

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
     * Handels inplace_edit.
     *
     * @param string $itemtype
     * @param string $itemid
     * @param mixed $newvalue
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     */
    public function handle_inplace_edit(string $itemtype, string $itemid, mixed $newvalue) {
        if ($itemtype === 'editanswer') {
            return \mootimetertool_wordcloud\local\inplace_edit_answer::update($itemid, $newvalue);
        }
    }

    /**
     * Get the answer overview params.
     *
     * @param object $cm
     * @param object $page
     * @return array
     */
    public function get_tool_answer_overview_params(object $cm, object $page): array {
        global $PAGE;

        $answers = $this->get_answers($this::ANSWER_TABLE, $page->id, $this::ANSWER_COLUMN);
        $params = [];
        $params['template'] = 'mootimetertool_wordcloud/view_overview';
        $i = 1;

        $renderer = $PAGE->get_renderer('core');

        foreach ($answers as $answer) {
            $user = $this->get_user_by_id($answer->usermodified);

            $userfullname = "";
            if (!empty($user) && empty(self::get_tool_config($page->id, "anonymousmode"))) {
                $userfullname = $user->firstname . " " . $user->lastname;
            } else if (!empty(self::get_tool_config($page->id, "anonymousmode"))) {
                $userfullname = get_string('anonymous_name', 'mod_mootimeter');
            }

            // Add delete button to answer.
            $dataseticontrash = [
                'data-ajaxmethode = "mod_mootimeter_delete_single_answer"',
                'data-pageid="' . $page->id . '"',
                'data-answerid="' . $answer->id . '"',
                'data-confirmationtitlestr="' . get_string('delete_single_answer_dialog_title', 'mod_mootimeter') . '"',
                'data-confirmationquestionstr="' . get_string('delete_single_answer_dialog_question', 'mod_mootimeter') . '"',
                'data-confirmationtype="DELETE_CANCEL"',
            ];

            $inplaceedit = new \mootimetertool_wordcloud\local\inplace_edit_answer($page, $answer);

            $params['answers'][] = [
                'nbr' => $i,
                'userfullname' => $userfullname,
                'answer' => $inplaceedit->export_for_template($renderer),
                'datetime' => userdate($answer->timecreated, get_string('strftimedatetimeshortaccurate', 'core_langconfig')),
                'options' => [
                    [
                        'icon' => 'fa-trash',
                        'id' => 'mtmt_delete_answer_' . $answer->id,
                        'iconid' => 'mtmt_delete_iconid_' . $answer->id,
                        'dataset' => implode(" ", $dataseticontrash),
                    ],
                ],
            ];

            // Count up ansers.
            $i++;
        }

        return $params;
    }

    /**
     * Get the answer overview.
     *
     * @param object $cm
     * @param object $page
     * @return string
     */
    public function get_answer_overview(object $cm, object $page): string {
        global $OUTPUT;
        $params = $this->get_answer_overview_params($cm, $page);
        return $OUTPUT->render_from_template("mod_mootimeter/answers_overview", $params['pagecontent']);
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
     * Get all grouped and counted answers of a page.
     *
     * @param int $pageid
     * @param int $userid
     * @return array
     * @throws dml_exception
     */
    protected function get_answers_list(int $pageid, int $userid = 0) {

        $instance = self::get_instance_by_pageid($pageid);
        $cm = self::get_cm_by_instance($instance);

        // We only want to deliver results if showresults is true or the teacher allowed to view it.
        if (
            !empty(self::get_tool_config($pageid, 'showonteacherpermission'))
            || has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))
        ) {
            $params = [
                'pageid' => $pageid,
            ];

            if (!empty($userid)) {
                $params['usermodified'] = $userid;
            }

            return (array)$this->get_answers_grouped(self::ANSWER_TABLE, $params);
        }

        return [['answer' => get_string('no_answer_due_to_showteacherpermission', 'mootimetertool_wordcloud'), 'cnt' => 1]];
    }

    /**
     * Convert a grouped answer list to an array, that is readable by wordcloud2.js.
     *
     * @param array $answerslist
     * @return array
     */
    protected function convert_answer_list_to_wordcloud_list(array $answerslist) {
        $templist = [];
        foreach ($answerslist as $key => $value) {
            $templist[] = array_values((array)$value);
        }

        return $templist;
    }

    /**
     * Get the answer list in wordcloud2 format.
     * [{"answer":"Answer word 1","cnt":"3"},{"answer":"Answer word 2","cnt":"1"}, ...]
     *
     * @param int $pageid
     * @param int $userid
     * @return array
     * @throws dml_exception
     */
    public function get_answerlist_wordcloud(int $pageid, int $userid = 0): array {

        $answerlist = $this->get_answers_list($pageid, $userid);
        return $this->convert_answer_list_to_wordcloud_list($answerlist);
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
     * Get all parameters that are necessary for rendering the tools view.
     *
     * @param object $page
     * @return array
     */
    public function get_renderer_params(object $page) {
        global $USER;

        // Parameter for initial wordcloud rendering.
        $params['answerslist'] = json_encode([]);

        $params['pageid'] = $page->id;

        // Parameter for initializing Badges.
        $params["toolname"] = ['pill' => get_string("pluginname", "mootimetertool_" . $page->tool)];
        $params['template'] = "mootimetertool_" . $page->tool . "/view_content";

        $params["answers"] = array_values(array_map(function ($element) use ($page) {

            // Add delete button to answer.
            $dataseticontrash = [
                'data-ajaxmethode = "mod_mootimeter_delete_single_answer"',
                'data-pageid="' . $page->id . '"',
                'data-answerid="' . $element->id . '"',
                'data-confirmationtitlestr="' . get_string('delete_single_answer_dialog_title', 'mod_mootimeter') . '"',
                'data-confirmationquestionstr="' . get_string('delete_single_answer_dialog_question', 'mod_mootimeter') . '"',
                'data-confirmationtype="DELETE_CANCEL"',
            ];

            return [
                'pill' => $element->answer,
                'additional_class' => 'mootimeter-pill-inline',
                'deletebutton' => [
                    'id' => 'mtmt_delete_answer_' . $element->id,
                    'iconid' => 'mtmt_delete_iconid_' . $element->id,
                    'dataset' => implode(" ", $dataseticontrash),
                ],
            ];
        }, $this->get_user_answers(self::ANSWER_TABLE, $page->id, self::ANSWER_COLUMN, $USER->id)));

        $params['input_answer'] = [
            'mtm-input-id' => 'mootimeter_type_answer',
            'mtm-input-name' => 'answer',
            'mtm-button-id' => 'mootimeter_enter_answer',
            'dataset' => 'data-pageid="' . $page->id . '"',
            'autofocus' => true,
            'additional_class' => 'mtmt-wc-answerinput',
        ];

        $params['button_answer'] = [
            'mtm-button-id' => 'mootimeter_enter_answer',
            'mtm-button-text' => get_string('submit', 'mootimetertool_wordcloud'),
        ];

        return $params;
    }

    /**
     * Get the params for settings column.
     *
     * @param object $page
     * @param array $params
     * @return array
     * @throws dml_exception
     * @throws coding_exception
     */
    public function get_col_settings_tool_params(object $page, array $params = []) {
        global $USER;

        $instance = self::get_instance_by_pageid($page->id);
        $cm = self::get_cm_by_instance($instance);
        if (!has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id)) || empty($USER->editing)) {
            return [];
        }

        $params['template'] = 'mootimetertool_wordcloud/view_settings';

        $params['question'] = [
            'mtm-input-id' => 'mtm_input_question',
            'mtm-input-value' => clean_param(self::get_tool_config($page, 'question'), PARAM_TEXT),
            'mtm-input-placeholder' => get_string('enter_question', 'mod_mootimeter'),
            'mtm-input-name' => "question",
            'additional_class' => 'mootimeter_settings_selector',
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_setting",
        ];

        $params['maxinputsperuser'] = [
            'title' => get_string('answers_max_number', 'mootimetertool_wordcloud'),
            'additional_class' => 'mootimeter_settings_selector',
            'id' => "maxinputsperuser",
            'name' => "maxinputsperuser",
            'min' => 0,
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_setting",
            'value' => (empty(self::get_tool_config($page->id, "maxinputsperuser"))) ? '0' : self::get_tool_config(
                $page->id,
                "maxinputsperuser"
            ),
        ];

        $params['settings']['allowduplicateanswers'] = [
            'cb_with_label_id' => 'allowduplicateanswers',
            'pageid' => $page->id,
            'cb_with_label_text' => get_string('allowduplicateanswers', 'mootimetertool_wordcloud'),
            'cb_with_label_name' => 'allowduplicateanswers',
            'cb_with_label_additional_class' => 'mootimeter_settings_selector',
            'cb_with_label_ajaxmethod' => "mod_mootimeter_store_setting",
            'cb_with_label_checked' => (\mod_mootimeter\helper::get_tool_config($page, 'allowduplicateanswers') ? "checked" : ""),
        ];

        $params['settings']['anonymousmode'] = [
            'cb_with_label_id' => 'anonymousmode',
            'pageid' => $page->id,
            'cb_with_label_text' => get_string('anonymousmode', 'mod_mootimeter')
                . " " . get_string('anonymousmode_desc', 'mod_mootimeter'),
            'cb_with_label_name' => 'anonymousmode',
            'cb_with_label_additional_class' => 'mootimeter_settings_selector',
            'cb_with_label_ajaxmethod' => "mod_mootimeter_store_setting",
            'cb_with_label_checked' => (\mod_mootimeter\helper::get_tool_config($page, 'anonymousmode') ? "checked" : ""),
        ];

        $answers = $this->get_answers(self::ANSWER_TABLE, $page->id, self::ANSWER_COLUMN);
        // The anonymous mode could not be changed if, there are any answers already given.
        if (!empty($answers)) {
            $params['settings']['anonymousmode']['cb_with_label_disabled'] = 'disabled';
            unset($params['settings']['anonymousmode']['cb_with_label_ajaxmethod']);
        }

        $returnparams['colsettings'] = $params;
        return $returnparams;
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

    /**
     * Get content menu bar params.
     *
     * @param object $page
     * @param array $params
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_content_menu_tool_params(object $page, array $params) {
        return $params;
    }

    /**
     * Delivers the result page params of the wordcloud.
     *
     * @param object $page
     * @param array $params
     * @return array
     * @throws dml_exception
     */
    public function get_tool_result_page_params(object $page, array $params = []): array {
        $params['answerslist'] = "";
        $params['lastupdated'] = $this->get_data_changed($page, 'answers');
        $params['pageid'] = $page->id;
        $params['template'] = "mootimetertool_wordcloud/view_results";
        return $params;
    }
}
