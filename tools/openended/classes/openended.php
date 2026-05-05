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
 * Main pluginlib for the open-ended tool.
 *
 * @package     mootimetertool_openended
 * @copyright   2026, ISB Bayern
 * @author      Benedikt Blumenfelder
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_openended;

use coding_exception;
use dml_exception;

/**
 * Main pluginlib for the open-ended tool.
 *
 * @package     mootimetertool_openended
 * @copyright   2026, ISB Bayern
 */
class openended extends \mod_mootimeter\toolhelper {
    /** @var string Answer column. */
    const ANSWER_COLUMN = 'answer';
    /** @var string Answer table. */
    const ANSWER_TABLE = 'mootimetertool_openended_answers';
    /** @var string Reactions table. */
    const REACTION_TABLE = 'mootimetertool_openended_reactions';
    /** @var string Userid column on the answer table. */
    const ANSWER_USERID_COLUMN = 'usermodified';
    /** @var int Default character limit. */
    const MAX_CHARS_DEFAULT = 200;
    /** @var int Hard upper bound aligned with the DB column. */
    const MAX_CHARS_HARD_LIMIT = 255;

    /** @var int Webservice error code: answer is too long. */
    const ERRORCODE_TOO_LONG = 1003;

    /**
     * Fixed list of reaction emojis. Order is preserved in the UI.
     *
     * @return array<string,string> emoji slug => unicode glyph
     */
    public static function get_emojis(): array {
        return [
            'thumbsup' => '👍',
            'heart'    => '❤️',
            'laugh'    => '😂',
            'wow'      => '😮',
            'think'    => '🤔',
        ];
    }

    /**
     * Pick a font-size bucket ('l', 'm', 's') from the answer length so longer
     * messages render smaller and the masonry grid stays balanced.
     *
     * @param string $text
     * @return string one of 'l', 'm', 's'
     */
    public static function text_size_bucket(string $text): string {
        $len = mb_strlen($text);
        if ($len <= 80) {
            return 'l';
        }
        if ($len <= 200) {
            return 'm';
        }
        return 's';
    }

    /**
     * Get the configured character limit clamped to the tool's supported range.
     *
     * @param int $pageid
     * @return int
     */
    public static function get_effective_maxchars(int $pageid): int {
        $maxchars = (int) self::get_tool_config($pageid, 'maxcharacters');
        if ($maxchars <= 0) {
            $maxchars = self::MAX_CHARS_DEFAULT;
        }
        return min($maxchars, self::MAX_CHARS_HARD_LIMIT);
    }

    /**
     * Build data attributes for the shared answer delete button.
     *
     * @param int $pageid
     * @param int $answerid
     * @return string
     */
    private static function build_delete_dataset(int $pageid, int $answerid): string {
        return implode(' ', [
            'data-ajaxmethode="mod_mootimeter_delete_single_answer"',
            'data-pageid="' . $pageid . '"',
            'data-answerid="' . $answerid . '"',
            'data-confirmationtitlestr="' . get_string('delete_single_answer_dialog_title', 'mod_mootimeter') . '"',
            'data-confirmationquestionstr="' . get_string('delete_single_answer_dialog_question', 'mod_mootimeter') . '"',
            'data-confirmationtype="DELETE_CANCEL"',
        ]);
    }

    /**
     * Get the tool's answer column.
     */
    public function get_answer_column() {
        return self::ANSWER_COLUMN;
    }

    /**
     * Get the tool's answer table.
     */
    public function get_answer_table() {
        return self::ANSWER_TABLE;
    }

    /**
     * Get the userid column name in the answer table.
     */
    public function get_answer_userid_column(): ?string {
        return self::ANSWER_USERID_COLUMN;
    }

    /**
     * Set sensible defaults after the page is created.
     *
     * @param object $page
     * @return void
     */
    public function hook_after_new_page_created(object $page): void {
        $this->set_tool_config($page, 'maxcharacters', (string) self::MAX_CHARS_DEFAULT);
        $this->set_tool_config($page, 'anonymousmode', '1');
        $this->set_tool_config($page, 'enablereactions', '1');
    }

    /**
     * Handle inplace edit of answer text.
     *
     * @param string $itemtype
     * @param string $itemid
     * @param mixed $newvalue
     * @return mixed
     */
    public function handle_inplace_edit(string $itemtype, string $itemid, mixed $newvalue) {
        if ($itemtype === 'editanswer') {
            return \mootimetertool_openended\local\inplace_edit_answer::update($itemid, $newvalue);
        }
    }

    /**
     * Insert a new answer.
     *
     * @param object $page
     * @param string $answer
     * @return void
     */
    public function insert_answer(object $page, $answer): void {
        $record = new \stdClass();
        $record->pageid = $page->id;
        $record->answer = mb_substr($answer, 0, self::get_effective_maxchars($page->id));
        $record->visible = 1;
        $record->timecreated = time();

        $this->store_answer(self::ANSWER_TABLE, $record);
    }

    /**
     * Delete page-related data (answers and their reactions).
     *
     * @param object $page
     * @return bool
     */
    public function delete_page_tool(object $page) {
        global $DB;
        try {
            $DB->delete_records(self::REACTION_TABLE, ['pageid' => $page->id]);
            $DB->delete_records(self::ANSWER_TABLE, ['pageid' => $page->id]);
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Delete answers by condition. Reactions for matching answers are removed first.
     *
     * @param object $page
     * @param array $conditions
     * @return void
     */
    public function delete_answers_tool(object $page, array $conditions): void {
        global $DB;
        $answers = $DB->get_records($this->get_answer_table(), $conditions, '', 'id');
        if (!empty($answers)) {
            [$insql, $inparams] = $DB->get_in_or_equal(array_keys($answers));
            $DB->delete_records_select(self::REACTION_TABLE, "answerid $insql", $inparams);
        }
        $DB->delete_records($this->get_answer_table(), $conditions);
        $this->clear_caches($page->id);
    }

    /**
     * Render parameters for the student input view.
     *
     * @param object $page
     * @return array
     */
    public function get_renderer_params(object $page) {
        global $USER;

        $maxchars = self::get_effective_maxchars($page->id);

        $params = [];
        $params['pageid'] = $page->id;
        $params['toolname'] = ['pill' => get_string('pluginname', 'mootimetertool_openended')];
        $params['template'] = 'mootimetertool_openended/view_content';
        $params['maxcharacters'] = $maxchars;
        $params['placeholder'] = get_string('type_answer', 'mootimetertool_openended');

        // Show the user's own contributions as deletable pills.
        $userpills = [];
        foreach ($this->get_user_answers(self::ANSWER_TABLE, $page->id, self::ANSWER_COLUMN, $USER->id) as $element) {
            $userpills[] = [
                'pill' => $element->answer,
                'additional_class' => 'mootimeter-pill-inline',
                'deletebutton' => [
                    'id' => 'mtmt_delete_answer_' . $element->id,
                    'iconid' => 'mtmt_delete_iconid_' . $element->id,
                    'dataset' => self::build_delete_dataset($page->id, (int) $element->id),
                ],
            ];
        }
        $params['answers'] = array_values($userpills);

        $params['button_answer'] = [
            'mtm-button-id' => 'openended_enter_answer',
            'mtm-button-text' => get_string('submit', 'mootimetertool_openended'),
        ];

        return $params;
    }

    /**
     * Build the result page params (the bubble grid).
     *
     * @param object $page
     * @param array $params
     * @return array
     */
    public function get_tool_result_page_params(object $page, array $params = []): array {
        global $USER;

        $params['pageid'] = $page->id;
        $params['lastupdated'] = $this->get_data_changed($page, 'answers');
        $params['enablereactions'] = !empty(self::get_tool_config($page->id, 'enablereactions'));
        // The result-render code path doesn't merge the page-level question, so
        // grab it here so the title can render at the top of the bubble grid.
        $params['question'] = clean_param(self::get_tool_config($page, 'question'), PARAM_TEXT);
        $params['template'] = 'mootimetertool_openended/view_results';

        if (empty($params['teacherpermissiontoview'])) {
            return $params;
        }

        $bubbles = $this->get_answers_with_reactions($page->id, $USER->id);
        $params['bubbles'] = $bubbles;
        $params['hasbubbles'] = !empty($bubbles);
        return $params;
    }

    /**
     * Build the moderator answer overview.
     *
     * @param object $cm
     * @param object $page
     * @return array
     */
    public function get_tool_answer_overview_params(object $cm, object $page): array {
        global $DB, $PAGE;

        $renderer = $PAGE->get_renderer('core');
        $answers = $this->get_answers(self::ANSWER_TABLE, $page->id, self::ANSWER_COLUMN);
        // Reaction totals per answer for the overview.
        $reactioncounts = $DB->get_records_sql(
            "SELECT answerid, COUNT(id) AS cnt
               FROM {" . self::REACTION_TABLE . "}
              WHERE pageid = :pageid
           GROUP BY answerid",
            ['pageid' => $page->id]
        );

        $params = [];
        $params['template'] = 'mootimetertool_openended/view_overview';
        $i = 1;
        foreach ($answers as $answer) {
            $user = $this->get_user_by_id($answer->usermodified);
            $userfullname = '';
            if (!empty($user)) {
                $userfullname = $user->firstname . ' ' . $user->lastname;
            }

            $isvisible = (int) ($answer->visible ?? 1);

            $inplaceedit = new \mootimetertool_openended\local\inplace_edit_answer($page, $answer);

            $params['answers'][] = [
                'nbr' => $i,
                'userfullname' => $userfullname,
                'answer' => $inplaceedit->export_for_template($renderer),
                'datetime' => userdate($answer->timecreated, get_string('strftimedatetimeshortaccurate', 'core_langconfig')),
                'reactioncount' => isset($reactioncounts[$answer->id]) ? (int) $reactioncounts[$answer->id]->cnt : 0,
                'visible' => $isvisible,
                'visibility_button' => [
                    'answerid' => $answer->id,
                    'icon' => $isvisible ? 'fa-eye' : 'fa-eye-slash',
                ],
                'delete_button' => [
                    'icon' => 'fa-trash',
                    'id' => 'mtmt_delete_answer_' . $answer->id,
                    'iconid' => 'mtmt_delete_iconid_' . $answer->id,
                    'dataset' => self::build_delete_dataset($page->id, (int) $answer->id),
                ],
            ];
            $i++;
        }
        if (empty($params['answers'])) {
            $params['answers'] = [];
        }
        return $params;
    }

    /**
     * Render the answer overview.
     *
     * @param object $cm
     * @param object $page
     * @return string
     */
    public function get_answer_overview(object $cm, object $page): string {
        global $OUTPUT;
        $params = $this->get_answer_overview_params($cm, $page);
        return $OUTPUT->render_from_template('mod_mootimeter/answers_overview', $params['pagecontent']);
    }

    /**
     * Build the settings panel parameters.
     *
     * @param object $page
     * @param array $params
     * @return array
     */
    public function get_col_settings_tool_params(object $page, array $params = []) {
        global $USER;

        $instance = self::get_instance_by_pageid($page->id);
        $cm = self::get_cm_by_instance($instance);
        if (!has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id)) || empty($USER->editing)) {
            return [];
        }

        $params['template'] = 'mootimetertool_openended/view_settings';

        $params['question'] = [
            'mtm-input-id' => 'mtm_input_question',
            'mtm-input-value' => clean_param(self::get_tool_config($page, 'question'), PARAM_TEXT),
            'mtm-input-placeholder' => get_string('enter_question', 'mod_mootimeter'),
            'mtm-input-name' => 'question',
            'additional_class' => 'mootimeter_settings_selector',
            'pageid' => $page->id,
            'ajaxmethode' => 'mod_mootimeter_store_setting',
        ];

        $maxchars = self::get_tool_config($page->id, 'maxcharacters');
        $maxinputsperuser = self::get_tool_config($page->id, 'maxinputsperuser');
        $params['maxcharacters'] = [
            'title' => get_string('maxcharacters', 'mootimetertool_openended'),
            'additional_class' => 'mootimeter_settings_selector',
            'id' => 'maxcharacters',
            'name' => 'maxcharacters',
            'min' => 1,
            'pageid' => $page->id,
            'ajaxmethode' => 'mod_mootimeter_store_setting',
            'value' => empty($maxchars) ? (string) self::MAX_CHARS_DEFAULT : $maxchars,
        ];

        $params['maxinputsperuser'] = [
            'title' => get_string('answers_max_number', 'mootimetertool_openended'),
            'additional_class' => 'mootimeter_settings_selector',
            'id' => 'maxinputsperuser',
            'name' => 'maxinputsperuser',
            'min' => 0,
            'pageid' => $page->id,
            'ajaxmethode' => 'mod_mootimeter_store_setting',
            'value' => empty($maxinputsperuser)
                ? '0'
                : $maxinputsperuser,
        ];

        $params['settings']['enablereactions'] = [
            'cb_with_label_id' => 'enablereactions',
            'pageid' => $page->id,
            'cb_with_label_text' => get_string('enablereactions', 'mootimetertool_openended'),
            'cb_with_label_name' => 'enablereactions',
            'cb_with_label_additional_class' => 'mootimeter_settings_selector',
            'cb_with_label_ajaxmethod' => 'mod_mootimeter_store_setting',
            'cb_with_label_checked' => self::get_tool_config($page, 'enablereactions') ? 'checked' : '',
        ];

        $params['settings']['anonymousmode'] = [
            'cb_with_label_id' => 'anonymousmode',
            'pageid' => $page->id,
            'cb_with_label_text' => get_string('anonymousmode', 'mod_mootimeter')
                . ' ' . get_string('anonymousmode_desc', 'mod_mootimeter'),
            'cb_with_label_name' => 'anonymousmode',
            'cb_with_label_additional_class' => 'mootimeter_settings_selector',
            'cb_with_label_ajaxmethod' => 'mod_mootimeter_store_setting',
            'cb_with_label_checked' => self::get_tool_config($page, 'anonymousmode') ? 'checked' : '',
        ];

        // Once any answer exists, anonymousmode must not be flipped.
        $answers = $this->get_answers(self::ANSWER_TABLE, $page->id, self::ANSWER_COLUMN);
        if (!empty($answers)) {
            $params['settings']['anonymousmode']['cb_with_label_disabled'] = 'disabled';
            unset($params['settings']['anonymousmode']['cb_with_label_ajaxmethod']);
        }

        $params['settings']['showonteacherpermission'] = [
            'cb_with_label_id' => 'showonteacherpermission',
            'pageid' => $page->id,
            'cb_with_label_text' => get_string('showresultteacherpermission', 'mootimetertool_openended'),
            'cb_with_label_name' => 'showonteacherpermission',
            'cb_with_label_additional_class' => 'mootimeter_settings_selector',
            'cb_with_label_ajaxmethod' => 'mod_mootimeter_store_setting',
            'cb_with_label_checked' => self::get_tool_config($page, 'showonteacherpermission') ? 'checked' : '',
        ];

        $params['settings']['showresultlive'] = [
            'cb_with_label_id' => 'showresultlive',
            'pageid' => $page->id,
            'cb_with_label_text' => get_string('showresultlive', 'mootimetertool_openended'),
            'cb_with_label_name' => 'showresultlive',
            'cb_with_label_additional_class' => 'mootimeter_settings_selector',
            'cb_with_label_ajaxmethod' => 'mod_mootimeter_store_setting',
            'cb_with_label_checked' => self::get_tool_config($page, 'showresultlive') ? 'checked' : '',
        ];

        $returnparams['colsettings'] = $params;
        return $returnparams;
    }

    /**
     * Hook for content menu (currently no extra icons).
     */
    public function get_content_menu_tool_params(object $page, array $params) {
        return $params;
    }

    /**
     * Build the bubble list with reaction counts and the "I reacted" flag for the given user.
     *
     * Each user can have at most ONE active reaction per bubble. Users cannot react to
     * their own contributions; on those bubbles the buttons are rendered disabled and
     * the {@code mine} flag is always false.
     *
     * @param int $pageid
     * @param int $userid
     * @return array list of arrays: [id, answer, isown, reactions => [[key, symbol, count, mine], ...]]
     */
    public function get_answers_with_reactions(int $pageid, int $userid): array {
        global $DB;

        $answers = $DB->get_records(
            self::ANSWER_TABLE,
            ['pageid' => $pageid, 'visible' => 1],
            'timecreated ASC',
            'id, answer, usermodified, timecreated'
        );
        if (empty($answers)) {
            return [];
        }

        [$insql, $inparams] = $DB->get_in_or_equal(array_keys($answers), SQL_PARAMS_NAMED, 'a');

        // Count reactions per (answerid, emoji).
        $countrows = $DB->get_records_sql(
            "SELECT " . $DB->sql_concat_join("'_'", ['answerid', 'emoji']) . " AS uniqkey,
                    answerid, emoji, COUNT(id) AS cnt
               FROM {" . self::REACTION_TABLE . "}
              WHERE answerid $insql
           GROUP BY answerid, emoji",
            $inparams
        );

        $counts = [];
        foreach ($countrows as $row) {
            $counts[$row->answerid][$row->emoji] = (int) $row->cnt;
        }

        // The user has at most one active emoji per answer.
        $mineparams = $inparams;
        $mineparams['userid'] = $userid;
        $minerows = $DB->get_records_sql(
            "SELECT id, answerid, emoji
               FROM {" . self::REACTION_TABLE . "}
              WHERE answerid $insql AND usermodified = :userid",
            $mineparams
        );
        $myactive = [];
        foreach ($minerows as $row) {
            $myactive[$row->answerid] = $row->emoji;
        }

        $emojis = self::get_emojis();
        $bubbles = [];
        foreach ($answers as $answer) {
            $isown = ((int) $answer->usermodified === $userid);
            $reactions = [];
            $index = 0;
            foreach ($emojis as $key => $symbol) {
                $reactions[] = [
                    'key' => $key,
                    'symbol' => $symbol,
                    'count' => $counts[$answer->id][$key] ?? 0,
                    'mine' => !$isown && (($myactive[$answer->id] ?? null) === $key),
                    // Duplicated onto each reaction so the Mustache template can render
                    // the disabled state without parent-context lookups.
                    'isown' => $isown,
                    // 0-based position drives the picker stagger animation in CSS.
                    'index0' => $index++,
                ];
            }
            $bubbles[] = [
                'id' => (int) $answer->id,
                'answer' => $answer->answer,
                'isown' => $isown,
                'reactions' => $reactions,
                // Bucket by character count so the template can shrink long messages
                // and keep the bubble compact alongside short ones.
                'textsize' => self::text_size_bucket($answer->answer),
            ];
        }
        return $bubbles;
    }

    /**
     * Toggle the requesting user's reaction on a given answer.
     *
     * Semantics:
     *  - At most one reaction per (answer, user). Clicking a different emoji while one
     *    is already active replaces it.
     *  - Clicking the already-active emoji removes the reaction.
     *  - Reacting to one's own contribution is forbidden.
     *
     * @param int $answerid
     * @param string $emoji slug from get_emojis()
     * @param int $userid
     * @return array {answerid, isown, reactions: [{key, symbol, count, mine}, ...]}
     * @throws \moodle_exception when the answer is missing, the emoji is unknown,
     *     or the user is trying to react to their own contribution
     */
    public function toggle_reaction(int $answerid, string $emoji, int $userid): array {
        global $DB;

        $emojis = self::get_emojis();
        if (!isset($emojis[$emoji])) {
            throw new \moodle_exception('error_unknown_emoji', 'mootimetertool_openended');
        }

        $answer = $DB->get_record(
            self::ANSWER_TABLE,
            ['id' => $answerid],
            'id, pageid, usermodified',
            MUST_EXIST
        );

        if ((int) $answer->usermodified === $userid) {
            throw new \moodle_exception('error_cannot_react_to_own', 'mootimetertool_openended');
        }

        // The user has at most one reaction per bubble. Find it.
        $existing = $DB->get_record(self::REACTION_TABLE, [
            'answerid' => $answerid,
            'usermodified' => $userid,
        ]);

        if ($existing && $existing->emoji === $emoji) {
            // Click on the active emoji → toggle off.
            $DB->delete_records(self::REACTION_TABLE, ['id' => $existing->id]);
        } else {
            if ($existing) {
                // Switch from a different emoji.
                $DB->delete_records(self::REACTION_TABLE, ['id' => $existing->id]);
            }
            $DB->insert_record(self::REACTION_TABLE, (object) [
                'answerid' => $answerid,
                'pageid' => $answer->pageid,
                'usermodified' => $userid,
                'emoji' => $emoji,
                'timecreated' => time(),
            ]);
        }

        $page = $this->get_page($answer->pageid, false);
        $this->notify_data_changed($page, 'answers');

        // Return the full reaction state for this bubble so the frontend can sync
        // every button in one round-trip.
        $bubbles = $this->get_answers_with_reactions($answer->pageid, $userid);
        foreach ($bubbles as $bubble) {
            if ($bubble['id'] === (int) $answerid) {
                return [
                    'answerid' => (int) $answerid,
                    'isown' => $bubble['isown'],
                    'reactions' => $bubble['reactions'],
                ];
            }
        }

        // Should never happen — answer just existed.
        return [
            'answerid' => (int) $answerid,
            'isown' => false,
            'reactions' => [],
        ];
    }

    /**
     * Toggle visibility of a single answer.
     *
     * @param int $answerid
     * @return int new visibility value (0 or 1)
     */
    public function toggle_answer_visibility(int $answerid): int {
        global $DB;
        $record = $DB->get_record(self::ANSWER_TABLE, ['id' => $answerid], 'id, pageid, visible', MUST_EXIST);
        $newvisible = empty($record->visible) ? 1 : 0;
        $DB->set_field(self::ANSWER_TABLE, 'visible', $newvisible, ['id' => $record->id]);
        $this->clear_caches($record->pageid);
        $page = $this->get_page($record->pageid, false);
        $this->notify_data_changed($page, 'answers');
        return $newvisible;
    }
}
