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

namespace mootimetertool_wordcloud;

use core\context\module as context_module;
use core\param;
use mod_mootimeter\exception\conflict_exception;
use mod_mootimeter\exception\not_found_exception;
use mod_mootimeter\exception\validation_exception;
use mod_mootimeter\local\access;
use mod_mootimeter\route\api_util;

/**
 * Answers and results of wordcloud pages.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class wordcloud extends \mod_mootimeter\toolhelper {
    /** @var string Answer column. */
    const ANSWER_COLUMN = 'answer';

    /** @var string Answer table. */
    const ANSWER_TABLE = 'mootimetertool_wordcloud_answers';

    /** @var int Maximum length of an answer. */
    const MAX_ANSWER_LENGTH = 255;

    #[\Override]
    public function get_answer_column(): string {
        return self::ANSWER_COLUMN;
    }

    #[\Override]
    public function get_answer_table(): string {
        return self::ANSWER_TABLE;
    }

    #[\Override]
    public function hook_after_new_page_created(\stdClass $page): void {
    }

    /**
     * Get the maximum number of answers per participant, 0 means unlimited.
     *
     * @param \stdClass $page
     * @return int
     */
    public function get_max_answers(\stdClass $page): int {
        return max(0, (int) self::get_tool_config($page, 'maxinputsperuser'));
    }

    /**
     * Whether a participant may submit the same answer several times.
     *
     * @param \stdClass $page
     * @return bool
     */
    public function allows_duplicate_answers(\stdClass $page): bool {
        return !empty(self::get_tool_config($page, 'allowduplicateanswers'));
    }

    /**
     * Change the settings of a page.
     *
     * @param \stdClass $page
     * @param array $settings with the optional keys maxAnswers and allowDuplicateAnswers
     */
    public function update_settings(\stdClass $page, array $settings): void {
        if (array_key_exists('maxAnswers', $settings)) {
            $this->set_tool_config($page, 'maxinputsperuser', (string) max(0, (int) $settings['maxAnswers']));
        }
        if (array_key_exists('allowDuplicateAnswers', $settings)) {
            $this->set_tool_config($page, 'allowduplicateanswers', $settings['allowDuplicateAnswers'] ? '1' : '0');
        }
    }

    /**
     * Validate the text of an answer.
     *
     * @param string $text
     * @return string the trimmed text
     * @throws validation_exception
     */
    protected function validate_text(string $text): string {
        $text = trim(param::TEXT->clean($text));
        $error = null;
        if ($text === '') {
            $error = 'emptyanswer';
        } else if (\core_text::strlen($text) > self::MAX_ANSWER_LENGTH) {
            $error = 'answertoolong';
        }
        if ($error) {
            throw new validation_exception($error, 'mootimetertool_wordcloud', self::MAX_ANSWER_LENGTH, [
                ['field' => 'text', 'message' => get_string($error, 'mootimetertool_wordcloud', self::MAX_ANSWER_LENGTH)],
            ]);
        }
        return $text;
    }

    /**
     * Store an answer of the current user.
     *
     * @param \stdClass $page
     * @param string $text
     * @return \stdClass the new answer
     * @throws validation_exception|conflict_exception
     */
    public function submit_answer(\stdClass $page, string $text): \stdClass {
        global $DB, $USER;

        $text = $this->validate_text($text);
        $ownanswers = array_filter(
            $this->get_answers(self::ANSWER_TABLE, $page->id),
            fn(\stdClass $answer): bool => (int) $answer->usermodified === (int) $USER->id
        );

        $maxanswers = $this->get_max_answers($page);
        if ($maxanswers > 0 && count($ownanswers) >= $maxanswers) {
            throw new conflict_exception('toomanyanswers', 'mootimetertool_wordcloud');
        }
        // Answers differing only in case count as duplicates, this keeps the wordcloud precise.
        $owntexts = array_map(fn(\stdClass $answer): string => \core_text::strtolower($answer->answer), $ownanswers);
        if (!$this->allows_duplicate_answers($page) && in_array(\core_text::strtolower($text), $owntexts, true)) {
            throw new conflict_exception('duplicateanswer', 'mootimetertool_wordcloud');
        }

        [$answerid] = $this->store_answer(self::ANSWER_TABLE, [(object) ['pageid' => $page->id, 'answer' => $text]]);
        return $DB->get_record(self::ANSWER_TABLE, ['id' => $answerid], '*', MUST_EXIST);
    }

    /**
     * Get an answer of a page.
     *
     * @param \stdClass $page
     * @param int $answerid
     * @return \stdClass
     * @throws not_found_exception
     */
    public function get_answer(\stdClass $page, int $answerid): \stdClass {
        global $DB;
        return $DB->get_record(self::ANSWER_TABLE, ['id' => $answerid, 'pageid' => $page->id])
            ?: throw new not_found_exception('answernotfound', 'mootimetertool_wordcloud', $answerid);
    }

    /**
     * Change the text of an answer.
     *
     * @param \stdClass $page
     * @param \stdClass $answer
     * @param string $text
     * @return \stdClass the changed answer
     * @throws validation_exception
     */
    public function update_answer(\stdClass $page, \stdClass $answer, string $text): \stdClass {
        global $DB;

        $answer->answer = $this->validate_text($text);
        $answer->timemodified = self::now();
        $DB->update_record(self::ANSWER_TABLE, $answer);

        $this->clear_caches($page->id);
        $this->notify_data_changed((int) $page->id, self::CHANGE_ANSWERS);
        return $answer;
    }

    /**
     * Delete an answer.
     *
     * @param \stdClass $page
     * @param \stdClass $answer
     */
    public function delete_answer(\stdClass $page, \stdClass $answer): void {
        $this->delete_answers($page, ['id' => $answer->id]);
    }

    /**
     * Export an answer.
     *
     * @param \stdClass $page
     * @param \stdClass $answer
     * @return array
     */
    public function export_answer(\stdClass $page, \stdClass $answer): array {
        return [
            'id' => (int) $answer->id,
            'text' => (string) $answer->answer,
            'fullName' => $this->get_participant_name($page, (int) $answer->usermodified),
            'createdAt' => api_util::format_datetime((int) $answer->timecreated),
        ];
    }

    /**
     * Export the own answers of the current user and the settings of the page.
     *
     * @param access $access access to the page
     * @return array
     */
    public function export_page_data(access $access): array {
        global $USER;

        $page = $access->get_page();
        $ownanswers = [];
        foreach ($this->get_answers(self::ANSWER_TABLE, $page->id) as $answer) {
            if ((int) $answer->usermodified === (int) $USER->id) {
                $ownanswers[] = $this->export_answer($page, $answer);
            }
        }

        return [
            'pageId' => (int) $page->id,
            'ownAnswers' => $ownanswers,
            'maxAnswers' => $this->get_max_answers($page),
            'allowDuplicateAnswers' => $this->allows_duplicate_answers($page),
        ];
    }

    /**
     * Export the counted answers for the wordcloud.
     *
     * The answers are only exported if the current user may see the results.
     *
     * @param access $access access to the page
     * @return array
     */
    public function export_results(access $access): array {
        $page = $access->get_page();
        $available = $this->can_view_results($page, $access->ismoderator);
        $words = [];
        if ($available) {
            foreach ($this->get_answers_grouped(self::ANSWER_TABLE, $page->id, self::ANSWER_COLUMN) as $text => $count) {
                $words[] = ['text' => (string) $text, 'count' => $count];
            }
        }
        return ['available' => $available, 'words' => $words];
    }

    #[\Override]
    public function export_results_csv(\stdClass $page, context_module $context): array {
        $summary = [];
        foreach ($this->get_answers_grouped(self::ANSWER_TABLE, $page->id, self::ANSWER_COLUMN) as $text => $count) {
            $summary[] = [(string) $text, $count];
        }
        usort($summary, fn(array $a, array $b): int => $b[1] <=> $a[1] ?: strcmp($a[0], $b[0]));

        $answers = [];
        foreach ($this->get_answers(self::ANSWER_TABLE, $page->id) as $answer) {
            $answers[] = [
                $this->get_participant_name($page, (int) $answer->usermodified),
                (string) $answer->answer,
                userdate($answer->timecreated, get_string('strftimedatetimeshortaccurate', 'core_langconfig')),
            ];
        }

        return [
            [
                'columns' => [get_string('csv_term', 'mootimetertool_wordcloud'), get_string('csv_count', 'mod_mootimeter')],
                'rows' => $summary,
            ],
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
        $DB->delete_records(self::ANSWER_TABLE, ['pageid' => $page->id]);
    }
}
