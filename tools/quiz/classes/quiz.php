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
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_quiz;

use mod_mootimeter\helper;
use required_capability_exception;
use moodle_exception;
use stdClass;

/**
 * Pluginlib
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz extends \mod_mootimeter\toolhelper {
    /**
     * @var string Answer cloum
     */
    const ANSWER_COLUMN = "optionid";

    /**
     * @var string Answer table
     */
    const ANSWER_TABLE = "mootimetertool_quiz_answers";

    /**
     * @var string Name of table column of the answer table where the user id is stored
     */
    const ANSWER_USERID_COLUMN = 'usermodified';

    /**
     * @var string Answer option table name.
     */
    const ANSWER_OPTION_TABLE = "mootimetertool_quiz_options";

    /** @var string ChartJS default color
     * TODO: Make it an admin setting.
     */
    const CHARTJS_DEFAULT_COLOR = "#d33f01";
    /** @var string Answer cloum
     * TODO: Make it an admin setting.
     */
    const CHARTJS_DEFAULT_COLOR_SUCCESS = "#00C431";

    /** @var int Visualization id for pillar chart */
    const VISUALIZATION_ID_CHART_PILLAR = 1;
    /** @var int Visualization id for bar chart */
    const VISUALIZATION_ID_CHART_BAR = 2;
    /** @var int Visualization id for line chart */
    const VISUALIZATION_ID_CHART_LINE = 3;
    /** @var int Visualization id for pie chart */
    const VISUALIZATION_ID_CHART_PIE = 4;

    /** @var int Default value of maximum count of answers */
    const MAXANSWERSDEFAULT = 1;

    /** @var Answer cloum */
    const MTMT_VIEW_RESULT_TEACHERPERMISSION = 2;

    /**
     * Get the tool helper of a quiz or poll page.
     *
     * @param object $page
     * @return quiz
     * @throws \invalid_parameter_exception if the page is neither a quiz nor a poll
     */
    public static function get_for_page(object $page): quiz {
        $toolhelper = helper::get_toolhelper($page->tool);
        if (!$toolhelper instanceof quiz) {
            throw new \invalid_parameter_exception('The page is not a quiz or poll page.');
        }
        return $toolhelper;
    }

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
     * Get the tools answer table.
     * @return string
     */
    public function get_answer_option_table() {
        return self::ANSWER_OPTION_TABLE;
    }

    /**
     * Get the tool specific page settings that may be stored by moderators.
     *
     * @return array of setting name => PARAM_* type
     */
    public function get_setting_definitions(): array {
        return [
            'maxanswersperuser' => PARAM_INT,
            'visualizationtype' => PARAM_INT,
            'showanswercorrection' => PARAM_BOOL,
        ];
    }

    /**
     * Whether this tool supports marking options as correct.
     *
     * @return bool
     */
    public function supports_correct_options(): bool {
        return true;
    }

    /**
     * The quiz adds the answer correction toggle to the content menu.
     *
     * @return array
     */
    public function get_react_components(): array {
        $components = parent::get_react_components();
        $components['menu'] = '@moodle/lms/mootimetertool_quiz/menu';
        return $components;
    }

    /**
     * Get the maximum number of options a participant may select, 0 means unlimited.
     *
     * New pages store MAXANSWERSDEFAULT, pages without the setting have always been unlimited.
     *
     * @param object $page
     * @return int
     */
    public function get_max_answers(object $page): int {
        return max(0, (int) self::get_tool_config($page, 'maxanswersperuser'));
    }

    /**
     * Whether the correct options are revealed to the participants.
     *
     * @param object $page
     * @return bool
     */
    public function is_answer_correction_shown(object $page): bool {
        return $this->supports_correct_options() && !empty(self::get_tool_config($page, 'showanswercorrection'));
    }

    /**
     * Insert the answer.
     *
     * @param object $page
     * @param mixed $aoids
     * @return void
     */
    public function insert_answer(object $page, mixed $aoids) {
        global $DB;

        $records = [];

        // Iterate through each answer option.
        foreach ($aoids as $aoid) {
            // First check if the selected answer is part of the page.
            if (!$DB->record_exists($this->get_answer_option_table(), ['id' => $aoid, 'pageid' => $page->id])) {
                // Skip to the next iteration if the answer is not part of the page.
                continue;
            }

            // Create a new record object.
            $record = new \stdClass();
            $record->pageid = $page->id;
            $record->optionid = $aoid;
            $record->timecreated = time();

            // Add the record to the records array.
            $records[] = $record;
        }

        // Check if multiple answers are allowed.
        $enablemultipleanswers = $this->get_max_answers($page) !== 1;

        // Store the answers in the database.
        $this->store_answer(
            $this->get_answer_table(),
            $records,
            true,
            $this->get_answer_column(),
            $enablemultipleanswers
        );
    }

    /**
     * Will be executed after the page is created.
     * @param object $page
     * @return void
     */
    public function hook_after_new_page_created(object $page): void {
        global $USER;

        $record = new stdClass();
        $record->pageid = $page->id;
        $record->usermodified = $USER->id;
        $record->optiontext = '';
        if ($page->tool == 'quiz') {
            $record->optioniscorrect = 0;
        }
        $record->timecreated = time();

        // Store two answer options as default.
        $this->store_answer_option($record);
        $this->store_answer_option($record);
        $this->set_tool_config($page, 'maxanswersperuser', (string) self::MAXANSWERSDEFAULT);
        return;
    }

    /**
     * Replace the selected options of the participant who gave the referenced answer.
     *
     * Only moderators may change answers of participants.
     *
     * @param object $page
     * @param int $answerid any answer record of the participant on this page
     * @param int[] $optionids the new selected options
     * @return void
     * @throws moodle_exception if the selection is empty, too large or contains foreign options
     */
    public function update_user_answer(object $page, int $answerid, array $optionids): void {
        global $DB;

        $context = \context_module::instance(self::get_cm_by_pageid($page->id)->id);
        require_capability('mod/mootimeter:moderator', $context);

        $answerrecord = $DB->get_record($this->get_answer_table(), ['id' => $answerid, 'pageid' => $page->id], '*', MUST_EXIST);
        $userid = (int) $answerrecord->usermodified;

        $optionids = array_values(array_unique(array_map('intval', $optionids)));
        if (empty($optionids)) {
            throw new moodle_exception('atleastoneanswer', 'mootimetertool_quiz');
        }
        $maxanswers = $this->get_max_answers($page);
        if ($maxanswers !== 0 && count($optionids) > $maxanswers) {
            throw new moodle_exception('morethanmaxanswers', 'mootimetertool_quiz', '', $maxanswers);
        }
        $pageoptionids = array_map('intval', array_keys($this->get_answer_options($page->id)));
        if (array_diff($optionids, $pageoptionids)) {
            throw new moodle_exception('invalidparameter', 'debug');
        }

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records($this->get_answer_table(), ['pageid' => $page->id, 'usermodified' => $userid]);
        foreach ($optionids as $optionid) {
            $DB->insert_record($this->get_answer_table(), (object) [
                'pageid' => $page->id,
                'usermodified' => $userid,
                'optionid' => $optionid,
                'timecreated' => $answerrecord->timecreated,
                'timemodified' => time(),
            ]);
        }
        $transaction->allow_commit();

        $this->clear_caches($page->id);
        $this->notify_data_changed($page, 'answers');
    }

    /**
     * Store an answer option.
     *
     * @param object $record
     * @return int
     */
    public function store_answer_option(object $record): int {
        global $DB;

        $instance = \mod_mootimeter\helper::get_instance_by_pageid($record->pageid);
        $cm = \mod_mootimeter\helper::get_cm_by_instance($instance);
        if (!has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))) {
            return 0;
        }

        // Set the default value for timemodified to 0. This is necessary to make usage of GREATEST SQL method possible.
        $record->timemodified = 0;

        if (!empty($record->id)) {
            $page = $this->get_page($record->pageid);
            $origrecord = $DB->get_record(
                $this->get_answer_option_table(),
                ['id' => $record->id, 'pageid' => $record->pageid],
                '*',
                MUST_EXIST
            );
            $origrecord->optiontext = $record->optiontext;
            if ($page->tool == 'quiz') {
                $origrecord->optioniscorrect = $record->optioniscorrect;
            }
            $origrecord->timemodified = time();

            $DB->update_record($this->get_answer_option_table(), $origrecord);
            $this->notify_data_changed($this->get_page($record->pageid), 'settings');
            return $origrecord->id;
        }

        $insertreturn = $DB->insert_record($this->get_answer_option_table(), $record, true);
        $this->notify_data_changed($this->get_page($record->pageid), 'settings');
        return $insertreturn;
    }

    /**
     * Remove an answer option.
     * @param int $pageid
     * @param int $aoid
     * @return array
     */
    public function remove_answer_option(int $pageid, int $aoid): array {
        global $DB;

        $instance = self::get_instance_by_pageid($pageid);
        $cm = self::get_cm_by_instance($instance);
        $context = \context_module::instance($cm->id);

        if (!has_capability('mod/mootimeter:moderator', $context)) {
            throw new \required_capability_exception($context, 'mod/mootimeter:moderator', 'nopermission', 'mod_mootimeter');
        }

        try {
            $transaction = $DB->start_delegated_transaction();

            $DB->delete_records($this->get_answer_option_table(), ['pageid' => $pageid, 'id' => $aoid]);
            $DB->delete_records($this->get_answer_table(), ['pageid' => $pageid, 'optionid' => $aoid]);

            $transaction->allow_commit();

            $return = ['code' => 200, 'string' => 'ok'];
        } catch (\Exception $e) {
            $transaction->rollback($e);
            $return = ['code' => 500, 'string' => $e->getMessage()];
        }
        $this->notify_data_changed($this->get_page($pageid), 'settings');
        return $return;
    }

    /**
     * Export the data the question view and the settings view need.
     *
     * The correct options are only exported to moderators or once the answer correction is shown.
     *
     * @param object $page
     * @param \context_module $context
     * @param bool $ismoderator
     * @return array
     */
    public function export_page_data(object $page, \context_module $context, bool $ismoderator): array {
        global $USER;

        $answercorrection = $this->is_answer_correction_shown($page);
        $revealcorrect = $this->supports_correct_options() && ($ismoderator || $answercorrection);

        $options = [];
        foreach ($this->get_answer_options($page->id) as $option) {
            $options[] = [
                'id' => (int) $option->id,
                'text' => $ismoderator ? (string) $option->optiontext : '',
                'texthtml' => format_string((string) $option->optiontext, true, ['context' => $context]),
                'iscorrect' => $revealcorrect && !empty($option->optioniscorrect),
            ];
        }

        $selected = array_map('intval', array_keys($this->get_user_answers(
            $this->get_answer_table(),
            $page->id,
            $this->get_answer_column(),
            $USER->id
        )));

        $visualization = (int) self::get_tool_config($page, 'visualizationtype');

        return [
            'options' => $options,
            'selected' => $selected,
            'maxanswers' => $this->get_max_answers($page),
            'answercorrection' => $answercorrection,
            'supportscorrect' => $this->supports_correct_options(),
            'visualization' => $visualization ?: self::VISUALIZATION_ID_CHART_BAR,
        ];
    }

    /**
     * Get an answer option.
     *
     * @param array $conditions
     * @return bool|object
     */
    public function get_answer_option(array $conditions): bool|object {
        global $DB;
        return $DB->get_record($this->get_answer_option_table(), $conditions);
    }

    /**
     * Get all answer options of a page.
     *
     * @param int $pageid
     * @return array
     */
    public function get_answer_options(int $pageid): array {
        global $DB;
        return $DB->get_records($this->get_answer_option_table(), ['pageid' => $pageid]);
    }

    /**
     * Export the results of a page for the chart.
     *
     * The counts are only exported if the current user may see the results.
     *
     * @param object $page
     * @param \context_module $context
     * @return array
     */
    public function export_results(object $page, \context_module $context): array {
        $visible = (bool) $this->get_teacherpermission_to_view($page);
        $answersgrouped = [];
        if ($visible) {
            $answersgrouped = (array) $this->get_answers_grouped(
                $this->get_answer_table(),
                ['pageid' => $page->id],
                $this->get_answer_column()
            );
        }

        $labels = [];
        $values = [];
        foreach ($this->get_answer_options($page->id) as $option) {
            $labels[] = format_string((string) $option->optiontext, true, ['context' => $context, 'escape' => false]);
            $values[] = empty($answersgrouped[$option->id]) ? 0 : (int) ((array) $answersgrouped[$option->id])['cnt'];
        }

        $visualization = (int) self::get_tool_config($page, 'visualizationtype');
        $colors = array_values(array_filter(array_map('trim', explode(',', (string) get_config('mod_mootimeter', 'chartcolors')))));

        return [
            'visible' => $visible,
            'visualization' => $visualization ?: self::VISUALIZATION_ID_CHART_BAR,
            'labels' => $labels,
            'values' => $visible ? $values : [],
            'colors' => $colors ?: [self::CHARTJS_DEFAULT_COLOR],
        ];
    }

    /**
     * Delete all DB entries related to a specific page.
     * @param object $page
     * @return bool
     */
    public function delete_page_tool(object $page) {
        global $DB;
        try {
            $DB->delete_records($this->get_answer_option_table(), ['pageid' => $page->id]);
            $DB->delete_records($this->get_answer_table(), ['pageid' => $page->id]);
        } catch (\Exception $e) {
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
     * Get array of counted values for each answer/ option.
     * @param int $pageid
     * @return array
     * @throws \dml_exception
     */
    public function get_counted_answers(int $pageid) {
        $values = array_map(function ($obj) {
            return $obj['cnt'];
        }, (array)$this->get_answers_grouped($this->get_answer_table(), ["pageid" => $pageid], $this->get_answer_column()));
        return array_values($values);
    }

    /**
     * Export the answer overview for moderators, one row per participant.
     *
     * @param object $page
     * @param \context_module $context
     * @return array
     */
    public function export_overview(object $page, \context_module $context): array {
        $anonymous = !empty(self::get_tool_config($page, 'anonymousmode'));

        $options = [];
        foreach ($this->get_answer_options($page->id) as $option) {
            $options[] = [
                'id' => (int) $option->id,
                'texthtml' => format_string((string) $option->optiontext, true, ['context' => $context]),
            ];
        }

        $rows = [];
        $answers = $this->get_answers($this->get_answer_table(), $page->id, $this->get_answer_column());
        foreach ($this->convert_answers_to_grouped_answers($answers) as $answer) {
            $fullname = get_string('anonymous_name', 'mod_mootimeter');
            if (!$anonymous) {
                $user = $this->get_user_by_id($answer->usermodified);
                $fullname = $user ? fullname($user) : '';
            }
            $rows[] = [
                'answerid' => (int) $answer->id,
                'fullname' => $fullname,
                'optionids' => array_map('intval', explode(';', $answer->optionid)),
                'time' => userdate($answer->timecreated, get_string('strftimedatetimeshortaccurate', 'core_langconfig')),
            ];
        }

        return [
            'anonymous' => $anonymous,
            'maxanswers' => $this->get_max_answers($page),
            'options' => $options,
            'rows' => $rows,
        ];
    }

    /**
     * Based of an array of answers returns an array of answers grouped by userid.
     *
     * @param array $answers the answers to convert
     * @return array array with key: id of the first answer in a group, value is an answer object containing all optionids
     */
    public function convert_answers_to_grouped_answers(array $answers): array {
        $groupedanswers = [];
        foreach ($answers as $answer) {
            $groupedanswers[$answer->usermodified][] = $answer;
        }
        $answers = [];
        foreach ($groupedanswers as $groupedanswer) {
            $optionids = [];
            foreach ($groupedanswer as $answer) {
                $optionids[] = $answer->optionid;
            }
            $firstansweringroup = (object) reset($groupedanswer);
            $firstansweringroup->optionid = implode(';', $optionids);
            $answers[$firstansweringroup->id] = $firstansweringroup;
        }
        return $answers;
    }
}
