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

use dml_exception;
use coding_exception;
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
     * Get the pix tool. This can be the same between multiple tools e.g. quiz and poll.
     * @return string
     */
    public function get_pix_toolname() {
        return "quiz";
    }

    /**
     * Get chartjs visualization settings.
     * @param int $visualizationtypeid
     * @param mixed $pageid
     * @return array|void
     * @throws dml_exception
     */
    public function get_visualization_settings_charjs(int $visualizationtypeid, $pageid) {
        switch ($visualizationtypeid) {
            case self::VISUALIZATION_ID_CHART_BAR:
                return [
                    'charttype' => "bar",
                    'options' => [
                        'indexAxis' => 'y',
                        'scales' => [
                            'x' => [
                                'ticks' => [
                                    'stepSize' => 1,
                                ],
                            ],
                        ],
                    ],
                    'backgroundColor' => explode(",", str_replace(' ', '', get_config('mod_mootimeter', 'chartcolors'))),
                    'borderRadius' => 20,
                ];
            case self::VISUALIZATION_ID_CHART_LINE:
                return [
                    'charttype' => "line",
                    'options' => [
                        'indexAxis' => 'x',
                        'scales' => [
                            'x' => [
                                'min' => 0,
                                'ticks' => [
                                    'stepSize' => 1,
                                ],
                            ],
                        ],
                    ],
                    'backgroundColor' => explode(",", str_replace(' ', '', get_config('mod_mootimeter', 'chartcolors'))),
                    'pointStyle' => 'circle',
                    'pointRadius' => 10,
                    'pointHoverRadius' => 15,
                ];
            case self::VISUALIZATION_ID_CHART_PILLAR:
                return [
                    'charttype' => "bar",
                    'options' => [
                        'indexAxis' => 'x',
                        'scales' => [
                            'x' => [
                                'ticks' => [
                                    'stepSize' => 1,
                                ],
                            ],
                            'y' => [
                                'ticks' => [
                                    'stepSize' => 1,
                                ],
                            ],
                        ],
                    ],
                    'backgroundColor' => explode(",", str_replace(' ', '', get_config('mod_mootimeter', 'chartcolors'))),
                    'borderRadius' => 20,
                ];
            case self::VISUALIZATION_ID_CHART_PIE:
                return [
                    'charttype' => "pie",
                    'options' => [
                        'responsive' => true,
                        'title' => [
                            'display' => true,
                            'text' => self::get_tool_config($pageid, 'question'),
                        ],
                        'plugins' => [
                            'legend' => [
                                'position' => 'right',
                            ],
                        ],
                    ],
                    'backgroundColor' => explode(",", str_replace(' ', '', get_config('mod_mootimeter', 'chartcolors'))),
                ];
        }
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
            if (!$DB->record_exists($this->get_answer_option_table(), ['id' => $aoid])) {
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
        $enablemultipleanswers = (
            self::get_tool_config($page, 'maxanswersperuser') > 1
            || (int) self::get_tool_config($page, 'maxanswersperuser') == 0
        ) ? true : false;

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
     * @throws required_capability_exception
     * @throws moodle_exception
     */
    public function handle_inplace_edit(string $itemtype, string $itemid, mixed $newvalue) {
        if ($itemtype === 'editanswerselect') {
            return \mootimetertool_quiz\local\inplace_edit_answer::update($itemid, $newvalue);
        }
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
            $origrecord = $DB->get_record($this->get_answer_option_table(), ['id' => $record->id], '*', MUST_EXIST);
            $origrecord->pageid = $record->pageid;
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
     * Get content menu bar params.
     *
     * @param object $page
     * @param array $params Defaultparams
     * @return mixed
     * @throws coding_exception
     * @throws dml_exception
     * @throws moodle_exception
     */
    public function get_content_menu_tool_params(object $page, array $params) {

        $instance = \mod_mootimeter\helper::get_instance_by_pageid($page->id);
        $cm = \mod_mootimeter\helper::get_cm_by_instance($instance);

        if (has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))) {
            $dataseticoncheck = [
                'data-togglename = "showanswercorrection"',
                'data-pageid = ' . $page->id,
                // To configure the response handling.
                'data-iconenabled = "fa-check-square-o"',
                'data-icondisabled = "fa-square-o"',
                'data-tooltipenabled = "' . get_string('tooltip_content_menu_answercorrection', 'mootimetertool_quiz') . '"',
                'data-tooltipdisabled = "' .
                    get_string('tooltip_content_menu_answercorrection_disabled', 'mootimetertool_quiz') . '"',
            ];
            $params['icon-check'] = [
                'id' => 'toggleshowanswercorrectionid',
                'iconid' => 'toggleshowanswercorrectioniconid',
                'dataset' => implode(" ", $dataseticoncheck),
            ];
            if (!empty(self::get_tool_config($page->id, 'showanswercorrection'))) {
                $params['icon-check']['icon'] = "fa-check-square-o";
                $params['icon-check']['tooltip'] = get_string(
                    'tooltip_content_menu_answercorrection_disabled',
                    'mootimetertool_quiz'
                );
            } else if (empty(self::get_tool_config($page->id, 'showanswercorrection'))) {
                $params['icon-check']['icon'] = "fa-square-o";
                $params['icon-check']['tooltip'] = get_string('tooltip_content_menu_answercorrection', 'mootimetertool_quiz');
            }
        }
        return $params;
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
     * Get all parameters that are necessary for rendering the tools view.
     *
     * @param object $page
     * @return array
     * @deprecated
     */
    public function get_renderer_params(object $page) {
        global $USER;

        // Parameter for initial wordcloud rendering.
        $params['pageid'] = $page->id;
        $instance = self::get_instance_by_pageid($page->id);
        $cm = self::get_cm_by_instance($instance);

        if (self::get_tool_config($page->id, 'showanswercorrection')) {
            $params['showanswercorrection'] = true;
        }

        // Parameter for initializing Badges.
        $params["toolname"] = ['pill' => get_string("pluginname", "mootimetertool_" . $page->tool)];
        $params['template'] = "mootimetertool_quiz/view_content";

        $answeroptions = $this->get_answer_options($page->id);

        $useransweroptionsid = array_keys($this->get_user_answers(
            $this->get_answer_table(),
            $page->id,
            $this->get_answer_column(),
            $USER->id
        ));

        $inputtype = 'cb';
        if (intval(self::get_tool_config($page->id, 'maxanswersperuser', self::MAXANSWERSDEFAULT)) === 1) {
            $inputtype = 'rb';
        }
        foreach ($answeroptions as $answeroption) {
            $wrapperadditionalclass = (self::get_tool_config($page->id, 'showanswercorrection')) ? "mootimeter-highlighter" : '';
            $wrapperadditionalclass .= (
                self::get_tool_config($page->id, 'showanswercorrection') && $answeroption->optioniscorrect
            ) ? " mootimeter-success" : '';

            if (
                empty($answeroption->optiontext)
                && has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id))
            ) {
                $optiontext = get_string('enter_answeroption', 'mootimetertool_quiz');
            } else {
                $optiontext = $answeroption->optiontext;
            }

            $params['answeroptions'][$inputtype][] = [
                'wrapper_' . $inputtype . '_with_label_id' => "wrapper_ao_" . $answeroption->id,
                'wrapper_' . $inputtype . '_with_label_additional_class' => $wrapperadditionalclass,

                $inputtype . '_with_label_id' => "ao_" . $answeroption->id,
                $inputtype . '_with_label_text' => $optiontext,
                'pageid' => $page->id,
                $inputtype . '_with_label_name' => 'multipleanswers[]',
                $inputtype . '_with_label_value' => $answeroption->id,
                $inputtype . '_with_label_additional_class' => 'mootimeter_settings_selector ' .
                    'mootimeter-highlighter mootimeter-success',
                $inputtype . '_with_label_checked' => (in_array($answeroption->id, $useransweroptionsid)) ? "checked" : '',
                $inputtype . '_with_label_additional_attribut' => (self::get_tool_config($page->id, 'showanswercorrection')) ?
                    "disabled" : '',
            ];
        }

        if (empty(self::get_tool_config($page->id, 'showanswercorrection'))) {
            $params['sendbutton'] = [
                'mtm-button-id' => 'mtmt_store_answer',
                'mtm-button-text' => get_string('submit_answer', 'mootimetertool_quiz'),
                'mtm-button-dataset' => 'data-pageid="' . $page->id . '"',
            ];

            if (intval(self::get_tool_config($page, 'maxanswersperuser')) == 1) {
                $sendbuttoncontext = get_string('sendbutton_context_one_answers_possible', 'mootimetertool_quiz');
            } else {
                $sendbuttoncontext = get_string('sendbutton_context_more_answers_possible', 'mootimetertool_quiz');
            }
            $params['sendbutton_context'] = [
                'text' => $sendbuttoncontext,
            ];
        }

        return $params;
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

        $params['template'] = 'mootimetertool_quiz/view_settings';

        $params['ispoll'] = false;
        if ($page->tool == "poll") {
            $params['ispoll'] = true;
        }

        $params['question'] = [
            'mtm-input-id' => 'mtm_input_question',
            'mtm-input-value' => clean_param(self::get_tool_config($page, 'question'), PARAM_TEXT),
            'mtm-input-placeholder' => get_string('enter_question', 'mod_mootimeter'),
            'mtm-input-name' => "question",
            'additional_class' => 'mootimeter_settings_selector',
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_setting",
        ];

        $params['notification_mark_correct_anser'] = [
            'notification_id' => 'mtmt_mark_correct_answer',
            'notification_type' => 'info',
            'notification_icon' => 'fa-share fa-flip-vertical',
            'notification_icon_rotation' => 'mootimeter_rotate_110',
            'notification_text' => get_string('mark_correct_answer', 'mootimetertool_quiz'),
        ];

        $ispoll = false;
        if ($page->tool == "poll") {
            $ispoll = true;
        }

        $answeroptions = $this->get_answer_options($page->id);
        foreach ($answeroptions as $answeroption) {
            $params['answeroptions'][] = [
                'aoid' => $answeroption->id,
                'ispoll' => $ispoll,
                'mtm-ao-wrapper-id' => 'ao_wrapper_' . $answeroption->id,

                'mtm-input-id' => 'ao_text_' . $answeroption->id,
                'mtm-input-name' => 'ao_text',
                'mtm-input-value' => $answeroption->optiontext,
                'mtm-input-placeholder' => get_string('enter_answeroption', 'mootimetertool_quiz'),
                'ajaxmethode' => "mootimetertool_quiz_store_answeroption_text",
                'additional_class' => 'mootimeter-answer-options mootimeter_settings_selector',
                'dataset' => 'data-pageid=' . $page->id . ' data-aoid=' . $answeroption->id,

                'mtm-cb-without-label-id' => 'ao_iscorrect_' . $answeroption->id,
                'mtm-cb-without-label-name' => 'ao_iscorrect',
                'mtm-cb-without-label-ajaxmethode' => "mootimetertool_quiz_store_answeroption_is_correct",
                'mtm-cb-without-label-checked' => ($answeroption->optioniscorrect) ? "checked" : '',
                'mtm-cb-without-label-dataset' => 'data-pageid=' . $page->id . ' data-aoid=' . $answeroption->id,

                'button_icon_only_transparent_id' => 'ao_delete_' . $answeroption->id,
                'button_icon_only_transparent_dataset' => 'data-pageid=' . $page->id . ' data-aoid=' . $answeroption->id,
                'button_icon_only_transparent_icon' => 'fa-close',
                'button_icon_only_transparent_additionalclass' => 'mtmt-remove-answeroption',
                'button_icon_only_transparent_ajaxmethod' => 'mootimetertool_quiz_remove_anseroption',
            ];
        }

        $params['addoption'] = [
            'button_icon_only_transparent_icon' => 'fa-plus',
            'button-text' => get_string('add_question_option', 'mootimetertool_quiz'),
            'button_icon_only_transparent_id' => 'add_answer_option',
            'button_icon_only_transparent_dataset' => 'data-pageid="' . $page->id . '"',
        ];

        $visualizationtype = self::get_tool_config($page, 'visualizationtype');

        $params['visualization'] = [
            [
                'mtm-button-icon-id' => 'visualization_' . self::VISUALIZATION_ID_CHART_PILLAR,
                'mtm-button-icon-additionalclass' => 'mtmt_visualization_selector',
                'mtm-button-icon-img' => [
                    'path' => "tools/" . $this->get_pix_toolname() . "/pix/chart-pillar.svg",
                    'width' => "24px",
                ],
                'mtm-button-icon-active' => ($visualizationtype == self::VISUALIZATION_ID_CHART_PILLAR) ? true : false,
                'mtm-button-icon-dataset' => 'data-pageid="' . $page->id . '" data-visuid=' . self::VISUALIZATION_ID_CHART_PILLAR,
            ],
            [
                'mtm-button-icon-id' => 'visualization_' . self::VISUALIZATION_ID_CHART_BAR,
                'mtm-button-icon-additionalclass' => 'mtmt_visualization_selector',
                'mtm-button-icon-img' => [
                    'path' => "tools/" . $this->get_pix_toolname() . "/pix/chart-bar.svg",
                    'width' => "24px",
                ],
                'mtm-button-icon-active' => ($visualizationtype == self::VISUALIZATION_ID_CHART_BAR) ? true : false,
                'mtm-button-icon-dataset' => 'data-pageid="' . $page->id . '" data-visuid=' . self::VISUALIZATION_ID_CHART_BAR,
            ],
            [
                'mtm-button-icon-id' => 'visualization_' . self::VISUALIZATION_ID_CHART_PIE,
                'mtm-button-icon-additionalclass' => 'mtmt_visualization_selector',
                'mtm-button-icon-img' => [
                    'path' => "tools/" . $this->get_pix_toolname() . "/pix/chart-pie.svg",
                    'width' => "24px",
                ],
                'mtm-button-icon-active' => ($visualizationtype == self::VISUALIZATION_ID_CHART_PIE) ? true : false,
                'mtm-button-icon-dataset' => 'data-pageid="' . $page->id . '" data-visuid=' . self::VISUALIZATION_ID_CHART_PIE,
            ],
        ];

        $maxanswers = self::get_tool_config($page->id, "maxanswersperuser", self::MAXANSWERSDEFAULT);
        if (!is_number($maxanswers)) {
            $maxanswers = intval($maxanswers);
        }

        $params['maxanswers'] = [
            'title' => get_string('answers_max_number', 'mootimetertool_quiz'),
            'additional_class' => 'mootimeter_settings_selector',
            'id' => "maxanswersperuser",
            'name' => "maxanswersperuser",
            'min' => '0',
            'pageid' => $page->id,
            'ajaxmethode' => "mod_mootimeter_store_setting",
            'value' => strval($maxanswers),
        ];

        $params['anonymousmode'] = [
            'cb_with_label_id' => 'anonymousmode',
            'pageid' => $page->id,
            'cb_with_label_text' => get_string('anonymousmode', 'mod_mootimeter')
                . " " . get_string('anonymousmode_desc', 'mod_mootimeter'),
            'cb_with_label_name' => 'anonymousmode',
            'cb_with_label_additional_class' => 'mootimeter_settings_selector',
            'cb_with_label_ajaxmethod' => "mod_mootimeter_store_setting",
            'cb_with_label_checked' => (\mod_mootimeter\helper::get_tool_config($page, 'anonymousmode') ? "checked" : ''),
        ];

        $answers = $this->get_answers($this->get_answer_table(), $page->id, $this->get_answer_column());
        // The anonymous mode could not be changed if, there are any answers already given.
        if (!empty($answers)) {
            $params['anonymousmode']['cb_with_label_checked'] .= ' disabled ';
            unset($params['anonymousmode']['cb_with_label_ajaxmethod']);
        }

        $returnparams['colsettings'] = $params;
        return $returnparams;
    }

    /**
     * Get the bar/pillar background color.
     *
     * @param int $pageid
     * @return string|array
     * @throws dml_exception
     */
    public function get_chartjs_background_color(int $pageid): string|array {

        if (!self::get_tool_config($pageid, 'showanswercorrection')) {
            return self::CHARTJS_DEFAULT_COLOR;
        }

        $answeroptions = $this->get_answer_options($pageid);

        $backgroundcolors = [];

        foreach ($answeroptions as $answeroption) {
            if ($answeroption->optioniscorrect) {
                $backgroundcolors[] = self::CHARTJS_DEFAULT_COLOR_SUCCESS;
                continue;
            }
            $backgroundcolors[] = self::CHARTJS_DEFAULT_COLOR;
        }

        return $backgroundcolors;
    }

    /**
     * Get the quiz results in chartjs style.
     * @param object $page
     * @return array
     * @throws dml_exception
     */
    public function get_quiz_results_chartjs(object $page): array {

        $instance = self::get_instance_by_pageid($page->id);
        $cm = self::get_cm_by_instance($instance);

        if ($this->get_teacherpermission_to_view($page)) {
            $answersgrouped = (array)$this->get_answers_grouped(
                $this->get_answer_table(),
                ["pageid" => $page->id],
                $this->get_answer_column()
            );
        } else {
            $answersgrouped = [];
        }
        $answeroptions = $this->get_answer_options($page->id);

        $labels = [];
        $values = [];

        foreach ($answeroptions as $answeroption) {
            $labels[] = $answeroption->optiontext;
            if (empty($answersgrouped[$answeroption->id])) {
                $values[] = 0;
            } else {
                $tmp = (array)$answersgrouped[$answeroption->id];
                $values[] = (int)$tmp['cnt'];
            }
        }

        return [$labels, $values];
    }

    /**
     * Get the result params for chartjs (webservice and first page load.)
     * @param int|object $pageorid
     * @return array
     * @throws dml_exception
     */
    public function get_result_params_chartjs(int|object $pageorid): array {

        if (!is_object($pageorid)) {
            $page = $this->get_page($pageorid);
        } else {
            $page = $pageorid;
        }

        [$labels, $values] = $this->get_quiz_results_chartjs($page);

        $visualizationtype = (self::get_tool_config($page->id, 'visualizationtype'))
            ? self::get_tool_config($page->id, 'visualizationtype')
            : self::VISUALIZATION_ID_CHART_BAR;
        $chartsettings = $this->get_visualization_settings_charjs($visualizationtype, $page->id);

        $params = [
            'chartsettings' => json_encode($chartsettings),
            'labels' => json_encode($labels),
            'values' => json_encode($values),
            'question' => self::get_tool_config($page, 'question'),
        ];

        return $params;
    }

    /**
     * Renders the result page of the quiz.
     *
     * @param object $page
     * @param array $defaultparams
     * @return array
     * @throws dml_exception
     */
    public function get_tool_result_page_params(object $page, array $defaultparams = []): array {

        $paramschart = $this->get_result_params_chartjs($page);
        $params = array_merge($defaultparams, $paramschart);
        $params['pageid'] = $page->id;
        $params['template'] = "mootimetertool_quiz/view_results";

        return $params;
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
     * Get the params for answer overview view.
     *
     * @param object $cm
     * @param object $page
     * @return array
     */
    public function get_tool_answer_overview_params(object $cm, object $page): array {
        global $PAGE;

        $answers = $this->get_answers($this->get_answer_table(), $page->id, $this->get_answer_column());

        $answeroptions = $this->get_answer_options($page->id);

        $answeroptionstemp = [];
        foreach ($answeroptions as $answeroption) {
            $answeroptionstemp[$answeroption->id] = $answeroption->optiontext;
        }

        $params = [];
        $params['template'] = 'mootimetertool_quiz/view_overview';
        $i = 1;

        $renderer = $PAGE->get_renderer('core');

        $answers = $this->convert_answers_to_grouped_answers($answers);

        foreach ($answers as $answer) {
            $user = $this->get_user_by_id($answer->usermodified);

            $userfullname = '';
            if (!empty($user) && empty(self::get_tool_config($page->id, "anonymousmode"))) {
                $userfullname = $user->firstname . " " . $user->lastname;
            } else if (!empty(self::get_tool_config($page->id, "anonymousmode"))) {
                $userfullname = get_string('anonymous_name', 'mod_mootimeter');
            }

            // Add delte button to answer.
            $dataseticonrestart = [
                'data-ajaxmethode = "mod_mootimeter_delete_answers_of_user"',
                'data-pageid="' . $page->id . '"',
                'data-answerid="' . $answer->id . '"',
                'data-userid="' . $answer->usermodified . '"',
                'data-confirmationtitlestr="' . get_string('delete_answers_of_user_dialog_title', 'mod_mootimeter') . '"',
                'data-confirmationquestionstr="' . get_string('delete_answers_of_user_dialog_question', 'mod_mootimeter') . '"',
                'data-confirmationtype="DELETE_CANCEL"',
            ];

            $inplaceedit = new \mootimetertool_quiz\local\inplace_edit_answer($page, $answer);

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
                        'dataset' => join(" ", $dataseticonrestart),
                    ],
                ],
            ];

            $i++;
        }

        return $params;
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

    /**
     * Extracts the answer option names from an array of answer option objects.
     *
     * @param array $answeroptions Array of answer options
     * @return array Array of strings with the names of the answer options
     */
    public static function extract_answer_option_strings(array $answeroptions): array {
        $answeroptionsstrings = [];
        foreach ($answeroptions as $answeroption) {
            $answeroptionsstrings[$answeroption->id] = $answeroption->optiontext;
        }
        return $answeroptionsstrings;
    }

    /**
     * Get the rendered answer overview view.
     *
     * @param object $cm
     * @param object $page
     * @return string
     */
    public function get_answer_overview(object $cm, object $page): string {
        global $OUTPUT;
        $params = $this->get_answer_overview($cm, $page);
        return $OUTPUT->render_from_template("mod_mootimeter/answers_overview", $params);
    }
}
