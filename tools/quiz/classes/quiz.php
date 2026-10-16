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

namespace mootimetertool_quiz;

use core\context\module as context_module;
use core\param;
use mod_mootimeter\exception\conflict_exception;
use mod_mootimeter\exception\not_found_exception;
use mod_mootimeter\exception\validation_exception;
use mod_mootimeter\local\access;
use mod_mootimeter\route\api_util;

/**
 * Answer options, answers and results of quiz pages. The poll tool reuses it without correct options.
 *
 * A response is the set of options a participant selected, identified by the id of its first answer record.
 *
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class quiz extends \mod_mootimeter\toolhelper {
    /** @var string Answer column. */
    const ANSWER_COLUMN = 'optionid';

    /** @var string Answer table. */
    const ANSWER_TABLE = 'mootimetertool_quiz_answers';

    /** @var string Answer option table. */
    const ANSWER_OPTION_TABLE = 'mootimetertool_quiz_options';

    /** @var string Default chart color. */
    const CHARTJS_DEFAULT_COLOR = '#d33f01';

    /** @var int[] Stored visualization type => name in the REST API. */
    const VISUALIZATIONS = [1 => 'pillar', 2 => 'bar', 3 => 'line', 4 => 'pie'];

    /** @var int Visualization of pages without the setting. */
    const VISUALIZATION_DEFAULT = 2;

    /** @var int Default value of maximum count of answers. */
    const MAXANSWERSDEFAULT = 1;

    /** @var int Maximum number of answer options that can be added at once. */
    const MAX_IMPORT_OPTIONS = 100;

    #[\Override]
    public function get_answer_column(): string {
        return static::ANSWER_COLUMN;
    }

    #[\Override]
    public function get_answer_table(): string {
        return static::ANSWER_TABLE;
    }

    /**
     * Get the answer option table.
     *
     * @return string
     */
    public function get_answer_option_table(): string {
        return static::ANSWER_OPTION_TABLE;
    }

    /**
     * Whether this tool supports marking options as correct.
     *
     * @return bool
     */
    public function supports_correct_options(): bool {
        return true;
    }

    #[\Override]
    public function get_react_components(): array {
        $components = parent::get_react_components();
        $components['menu'] = '@moodle/lms/mootimetertool_quiz/menu';
        return $components;
    }

    /**
     * Get the maximum number of options a participant may select, 0 means unlimited.
     *
     * @param \stdClass $page
     * @return int
     */
    public function get_max_answers(\stdClass $page): int {
        return max(0, (int) self::get_tool_config($page, 'maxanswersperuser', (string) self::MAXANSWERSDEFAULT));
    }

    /**
     * Get the visualization of the results.
     *
     * @param \stdClass $page
     * @return string one of the values of VISUALIZATIONS
     */
    public function get_visualization(\stdClass $page): string {
        $type = (int) self::get_tool_config($page, 'visualizationtype');
        return self::VISUALIZATIONS[$type] ?? self::VISUALIZATIONS[self::VISUALIZATION_DEFAULT];
    }

    /**
     * Whether the correct options are revealed to the participants.
     *
     * @param \stdClass $page
     * @return bool
     */
    public function is_answer_correction_shown(\stdClass $page): bool {
        return $this->supports_correct_options() && !empty(self::get_tool_config($page, 'showanswercorrection'));
    }

    #[\Override]
    public function hook_after_new_page_created(\stdClass $page): void {
        $this->create_option($page, '', false);
        $this->create_option($page, '', false);
        $this->set_tool_config($page, 'maxanswersperuser', (string) self::MAXANSWERSDEFAULT);
    }

    /**
     * Change the settings of a page.
     *
     * @param \stdClass $page
     * @param array $settings with the optional keys maxAnswers, visualization and correctionShown
     * @throws validation_exception
     */
    public function update_settings(\stdClass $page, array $settings): void {
        if (array_key_exists('correctionShown', $settings) && !$this->supports_correct_options()) {
            throw new validation_exception('correctionnotsupported', 'mootimetertool_quiz', null, [
                ['field' => 'correctionShown', 'message' => get_string('correctionnotsupported', 'mootimetertool_quiz')],
            ]);
        }
        if (array_key_exists('maxAnswers', $settings)) {
            $this->set_tool_config($page, 'maxanswersperuser', (string) max(0, (int) $settings['maxAnswers']));
        }
        if (array_key_exists('visualization', $settings)) {
            $type = array_search($settings['visualization'], self::VISUALIZATIONS, true);
            $this->set_tool_config($page, 'visualizationtype', (string) $type);
        }
        if (array_key_exists('correctionShown', $settings)) {
            $this->set_tool_config($page, 'showanswercorrection', $settings['correctionShown'] ? '1' : '0');
        }
    }

    /**
     * Get all answer options of a page.
     *
     * @param int $pageid
     * @return \stdClass[]
     */
    public function get_answer_options(int $pageid): array {
        global $DB;
        return $DB->get_records($this->get_answer_option_table(), ['pageid' => $pageid], 'id ASC');
    }

    /**
     * Get an answer option.
     *
     * @param array $conditions
     * @return \stdClass|null
     */
    public function get_answer_option(array $conditions): ?\stdClass {
        global $DB;
        return $DB->get_record($this->get_answer_option_table(), $conditions) ?: null;
    }

    /**
     * Get an answer option of a page.
     *
     * @param \stdClass $page
     * @param int $optionid
     * @return \stdClass
     * @throws not_found_exception
     */
    public function get_option(\stdClass $page, int $optionid): \stdClass {
        return $this->get_answer_option(['id' => $optionid, 'pageid' => $page->id])
            ?? throw new not_found_exception('optionnotfound', 'mootimetertool_quiz', $optionid);
    }

    /**
     * Add an answer option.
     *
     * @param \stdClass $page
     * @param string $text
     * @param bool $iscorrect
     * @return \stdClass the new option
     */
    public function create_option(\stdClass $page, string $text, bool $iscorrect): \stdClass {
        global $DB, $USER;

        $option = (object) [
            'pageid' => $page->id,
            'usermodified' => $USER->id,
            'optiontext' => trim(param::TEXT->clean($text)),
            'timecreated' => self::now(),
            'timemodified' => 0,
        ];
        if ($this->supports_correct_options()) {
            $option->optioniscorrect = (int) $iscorrect;
        }
        $option->id = $DB->insert_record($this->get_answer_option_table(), $option);

        $this->notify_data_changed((int) $page->id, self::CHANGE_SETTINGS);
        return $option;
    }

    /**
     * Change an answer option.
     *
     * @param \stdClass $page
     * @param \stdClass $option
     * @param string|null $text null to keep the text
     * @param bool|null $iscorrect null to keep the correctness
     * @return \stdClass the changed option
     */
    public function update_option(\stdClass $page, \stdClass $option, ?string $text, ?bool $iscorrect): \stdClass {
        global $DB;

        if ($text !== null) {
            $option->optiontext = trim(param::TEXT->clean($text));
        }
        if ($iscorrect !== null && $this->supports_correct_options()) {
            $option->optioniscorrect = (int) $iscorrect;
        }
        $option->timemodified = self::now();
        $DB->update_record($this->get_answer_option_table(), $option);

        $this->clear_caches($page->id);
        $this->notify_data_changed((int) $page->id, self::CHANGE_SETTINGS);
        $this->notify_data_changed((int) $page->id, self::CHANGE_ANSWERS);
        return $option;
    }

    /**
     * Delete an answer option and all answers selecting it.
     *
     * @param \stdClass $page
     * @param \stdClass $option
     */
    public function delete_option(\stdClass $page, \stdClass $option): void {
        global $DB;

        $transaction = $DB->start_delegated_transaction();
        $DB->delete_records($this->get_answer_table(), ['pageid' => $page->id, 'optionid' => $option->id]);
        $DB->delete_records($this->get_answer_option_table(), ['pageid' => $page->id, 'id' => $option->id]);
        $transaction->allow_commit();

        $this->clear_caches($page->id);
        $this->notify_data_changed((int) $page->id, self::CHANGE_SETTINGS);
        $this->notify_data_changed((int) $page->id, self::CHANGE_ANSWERS);
    }

    /**
     * Replace the answer options of a page.
     *
     * Items with an id keep the existing option, items without an id add an option. Options that are not listed
     * are deleted together with the answers selecting them.
     *
     * @param \stdClass $page
     * @param array $items list of ['id' => ?int, 'text' => string, 'isCorrect' => ?bool]
     * @throws validation_exception if items reference foreign options or too many options are added
     */
    public function replace_options(\stdClass $page, array $items): void {
        global $DB;

        $existing = $this->get_answer_options($page->id);
        $keep = [];
        $new = 0;
        foreach ($items as $index => $item) {
            if (!empty($item['id'])) {
                if (!isset($existing[$item['id']])) {
                    throw new validation_exception('unknownoption', 'mootimetertool_quiz', null, [
                        ['field' => "items[$index].id", 'message' => get_string('unknownoption', 'mootimetertool_quiz')],
                    ]);
                }
                $keep[(int) $item['id']] = $item;
            } else {
                $new++;
            }
        }
        if ($new > self::MAX_IMPORT_OPTIONS) {
            throw new validation_exception('toomanyoptions', 'mootimetertool_quiz', self::MAX_IMPORT_OPTIONS, [
                ['field' => 'items', 'message' => get_string('toomanyoptions', 'mootimetertool_quiz', self::MAX_IMPORT_OPTIONS)],
            ]);
        }

        $transaction = $DB->start_delegated_transaction();
        foreach ($existing as $option) {
            if (!isset($keep[$option->id])) {
                $this->delete_option($page, $option);
            }
        }
        foreach ($items as $item) {
            $text = (string) ($item['text'] ?? '');
            $iscorrect = isset($item['isCorrect']) ? (bool) $item['isCorrect'] : null;
            if (empty($item['id'])) {
                $this->create_option($page, $text, (bool) $iscorrect);
            } else {
                $this->update_option($page, $existing[$item['id']], $text, $iscorrect);
            }
        }
        $transaction->allow_commit();
    }

    /**
     * Validate the options a participant selected.
     *
     * @param \stdClass $page
     * @param int[] $optionids
     * @return int[] the unique option ids
     * @throws validation_exception
     */
    protected function validate_selection(\stdClass $page, array $optionids): array {
        $optionids = array_values(array_unique(array_map('intval', $optionids)));
        $field = fn(string $identifier, $a = null) => [
            ['field' => 'optionIds', 'message' => get_string($identifier, 'mootimetertool_quiz', $a)],
        ];

        if (!$optionids) {
            throw new validation_exception('atleastoneanswer', 'mootimetertool_quiz', null, $field('atleastoneanswer'));
        }
        if (array_diff($optionids, array_keys($this->get_answer_options($page->id)))) {
            throw new validation_exception('unknownoption', 'mootimetertool_quiz', null, $field('unknownoption'));
        }
        $maxanswers = $this->get_max_answers($page);
        if ($maxanswers > 0 && count($optionids) > $maxanswers) {
            throw new validation_exception(
                'toomanyanswers',
                'mootimetertool_quiz',
                $maxanswers,
                $field('toomanyanswers', $maxanswers)
            );
        }
        return $optionids;
    }

    /**
     * Store the selected options of the current user, replacing a previous response.
     *
     * @param \stdClass $page
     * @param int[] $optionids
     * @return array the response, see {@see self::get_responses()}
     * @throws validation_exception|conflict_exception
     */
    public function submit_response(\stdClass $page, array $optionids): array {
        global $USER;

        $optionids = $this->validate_selection($page, $optionids);
        if ($this->is_answer_correction_shown($page)) {
            throw new conflict_exception('answeringclosed', 'mootimetertool_quiz');
        }
        $this->write_response($page, (int) $USER->id, $optionids);
        return $this->get_response_of_user($page, (int) $USER->id);
    }

    /**
     * Replace the selected options of a response.
     *
     * @param \stdClass $page
     * @param array $response see {@see self::get_responses()}
     * @param int[] $optionids
     * @return array the changed response
     * @throws validation_exception
     */
    public function replace_response(\stdClass $page, array $response, array $optionids): array {
        $optionids = $this->validate_selection($page, $optionids);
        $this->write_response($page, $response['userid'], $optionids);
        return $this->get_response_of_user($page, $response['userid']);
    }

    /**
     * Write the answers of a user. The first answer record is kept, so the id of the response does not change.
     *
     * @param \stdClass $page
     * @param int $userid
     * @param int[] $optionids
     */
    protected function write_response(\stdClass $page, int $userid, array $optionids): void {
        global $DB;

        $table = $this->get_answer_table();
        $records = array_values($DB->get_records($table, ['pageid' => $page->id, 'usermodified' => $userid], 'id ASC'));

        $transaction = $DB->start_delegated_transaction();
        foreach ($optionids as $index => $optionid) {
            if (isset($records[$index])) {
                $DB->update_record($table, (object) [
                    'id' => $records[$index]->id,
                    'optionid' => $optionid,
                    'timemodified' => self::now(),
                ]);
            } else {
                $DB->insert_record($table, (object) [
                    'pageid' => $page->id,
                    'usermodified' => $userid,
                    'optionid' => $optionid,
                    'timecreated' => $records[0]->timecreated ?? self::now(),
                    'timemodified' => 0,
                ]);
            }
        }
        foreach (array_slice($records, count($optionids)) as $record) {
            $DB->delete_records($table, ['id' => $record->id]);
        }
        $transaction->allow_commit();

        $this->clear_caches($page->id);
        $this->notify_data_changed((int) $page->id, self::CHANGE_ANSWERS);
    }

    /**
     * Get the responses of a page.
     *
     * @param \stdClass $page
     * @return array[] list of ['id' => int, 'userid' => int, 'optionids' => int[], 'timecreated' => int], ordered by id
     */
    public function get_responses(\stdClass $page): array {
        $responses = [];
        foreach ($this->get_answers($this->get_answer_table(), $page->id) as $answer) {
            $userid = (int) $answer->usermodified;
            $responses[$userid] ??= [
                'id' => (int) $answer->id,
                'userid' => $userid,
                'optionids' => [],
                'timecreated' => (int) $answer->timecreated,
            ];
            $responses[$userid]['optionids'][] = (int) $answer->optionid;
        }
        return array_values($responses);
    }

    /**
     * Get a response of a page.
     *
     * @param \stdClass $page
     * @param int $responseid
     * @return array see {@see self::get_responses()}
     * @throws not_found_exception
     */
    public function get_response(\stdClass $page, int $responseid): array {
        foreach ($this->get_responses($page) as $response) {
            if ($response['id'] === $responseid) {
                return $response;
            }
        }
        throw new not_found_exception('responsenotfound', 'mootimetertool_quiz', $responseid);
    }

    /**
     * Get the response of a user.
     *
     * @param \stdClass $page
     * @param int $userid
     * @return array|null see {@see self::get_responses()}
     */
    public function get_response_of_user(\stdClass $page, int $userid): ?array {
        foreach ($this->get_responses($page) as $response) {
            if ($response['userid'] === $userid) {
                return $response;
            }
        }
        return null;
    }

    /**
     * Delete a response.
     *
     * @param \stdClass $page
     * @param array $response see {@see self::get_responses()}
     */
    public function delete_response(\stdClass $page, array $response): void {
        $this->delete_answers($page, ['usermodified' => $response['userid']]);
    }

    /**
     * Export an answer option.
     *
     * The text for editing is only exported to moderators, the correctness only to moderators or once the
     * answer correction is shown.
     *
     * @param \stdClass $page
     * @param \stdClass $option
     * @param access $access
     * @return array
     */
    public function export_option(\stdClass $page, \stdClass $option, access $access): array {
        $revealcorrect = $this->supports_correct_options() && ($access->ismoderator || $this->is_answer_correction_shown($page));
        return [
            'id' => (int) $option->id,
            'text' => $access->ismoderator ? (string) $option->optiontext : '',
            'textHtml' => format_string((string) $option->optiontext, true, ['context' => $access->context]),
            'isCorrect' => $revealcorrect && !empty($option->optioniscorrect),
        ];
    }

    /**
     * Export the data the question view and the settings view need.
     *
     * @param access $access access to the page
     * @return array
     */
    public function export_page_data(access $access): array {
        global $USER;

        $page = $access->get_page();
        $response = $this->get_response_of_user($page, (int) $USER->id);

        return [
            'pageId' => (int) $page->id,
            'options' => array_values(array_map(
                fn(\stdClass $option): array => $this->export_option($page, $option, $access),
                $this->get_answer_options($page->id)
            )),
            'selectedOptionIds' => $response['optionids'] ?? [],
            'maxAnswers' => $this->get_max_answers($page),
            'correctionShown' => $this->is_answer_correction_shown($page),
            'supportsCorrectOptions' => $this->supports_correct_options(),
            'visualization' => $this->get_visualization($page),
        ];
    }

    /**
     * Export a response.
     *
     * @param \stdClass $page
     * @param array $response see {@see self::get_responses()}
     * @return array
     */
    public function export_response(\stdClass $page, array $response): array {
        return [
            'id' => $response['id'],
            'fullName' => $this->get_participant_name($page, $response['userid']),
            'optionIds' => $response['optionids'],
            'createdAt' => api_util::format_datetime($response['timecreated']),
        ];
    }

    /**
     * Export the counted answers per option for the chart.
     *
     * The counts are only exported if the current user may see the results.
     *
     * @param access $access access to the page
     * @return array
     */
    public function export_results(access $access): array {
        $page = $access->get_page();
        $available = $this->can_view_results($page, $access->ismoderator);

        $options = [];
        if ($available) {
            $counts = $this->get_answers_grouped($this->get_answer_table(), $page->id, $this->get_answer_column());
            foreach ($this->get_answer_options($page->id) as $option) {
                $options[] = [
                    'id' => (int) $option->id,
                    'label' => format_string((string) $option->optiontext, true, [
                        'context' => $access->context,
                        'escape' => false,
                    ]),
                    'count' => $counts[$option->id] ?? 0,
                ];
            }
        }
        $colors = array_values(array_filter(array_map('trim', explode(',', (string) get_config('mod_mootimeter', 'chartcolors')))));

        return [
            'available' => $available,
            'visualization' => $this->get_visualization($page),
            'options' => $options,
            'colors' => $colors ?: [self::CHARTJS_DEFAULT_COLOR],
        ];
    }

    #[\Override]
    public function export_results_csv(\stdClass $page, context_module $context): array {
        $counts = $this->get_answers_grouped($this->get_answer_table(), $page->id, $this->get_answer_column());

        $summarycolumns = [get_string('csv_answeroption', 'mootimetertool_quiz'), get_string('csv_count', 'mod_mootimeter')];
        if ($this->supports_correct_options()) {
            $summarycolumns[] = get_string('correct_answer', 'mootimetertool_quiz');
        }

        $optiontexts = [];
        $summary = [];
        foreach ($this->get_answer_options($page->id) as $option) {
            $text = format_string((string) $option->optiontext, true, ['context' => $context, 'escape' => false]);
            $optiontexts[(int) $option->id] = $text;
            $row = [$text, $counts[$option->id] ?? 0];
            if ($this->supports_correct_options()) {
                $row[] = get_string(empty($option->optioniscorrect) ? 'no' : 'yes');
            }
            $summary[] = $row;
        }

        $answers = [];
        foreach ($this->get_responses($page) as $response) {
            $texts = array_map(fn(int $optionid): string => $optiontexts[$optionid] ?? '', $response['optionids']);
            $answers[] = [
                $this->get_participant_name($page, $response['userid']),
                implode(' | ', $texts),
                userdate($response['timecreated'], get_string('strftimedatetimeshortaccurate', 'core_langconfig')),
            ];
        }

        return [
            ['columns' => $summarycolumns, 'rows' => $summary],
            [
                'columns' => [
                    get_string('csv_participant', 'mod_mootimeter'),
                    get_string('csv_answer', 'mod_mootimeter'),
                    get_string('csv_time', 'mod_mootimeter'),
                ],
                'rows' => $answers,
            ],
        ];
    }

    #[\Override]
    public function delete_page_tool(\stdClass $page): void {
        global $DB;
        $DB->delete_records($this->get_answer_table(), ['pageid' => $page->id]);
        $DB->delete_records($this->get_answer_option_table(), ['pageid' => $page->id]);
    }
}
