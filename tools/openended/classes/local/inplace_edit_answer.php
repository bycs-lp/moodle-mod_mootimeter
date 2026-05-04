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
 * Inplace edit handler for open-ended answer text.
 *
 * @package     mootimetertool_openended
 * @copyright   2026, ISB Bayern
 * @author      Benedikt Blumenfelder
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_openended\local;

use mootimetertool_openended\openended;

/**
 * Inplace edit handler for open-ended answer text.
 */
class inplace_edit_answer extends \core\output\inplace_editable {
    /**
     * Constructor.
     *
     * @param object $page
     * @param object $answer
     */
    public function __construct(object $page, object $answer) {
        $tool = new openended();

        $instance = $tool::get_instance_by_pageid($page->id);
        $cm = $tool::get_cm_by_instance($instance);

        parent::__construct(
            'mootimeter',
            'openended_editanswer',
            $page->id . '_' . $answer->id,
            has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id)),
            format_string($answer->{$tool::ANSWER_COLUMN}),
            $answer->{$tool::ANSWER_COLUMN}
        );
    }

    /**
     * Update callback used by inplace_editable.
     *
     * @param mixed $itemid
     * @param mixed $newvalue
     * @return self
     */
    public static function update(mixed $itemid, mixed $newvalue) {
        global $DB;

        $newvalue = clean_param($newvalue, PARAM_TEXT);
        $newvalue = mb_substr($newvalue, 0, openended::MAX_CHARS_HARD_LIMIT);

        $helper = new \mod_mootimeter\helper();
        [$pageid, $answerid] = explode('_', $itemid);

        $answertable = $helper->get_tool_answer_table($pageid);
        $answercol = $helper->get_tool_answer_column($pageid);

        $answerrecord = $DB->get_record($answertable, ['id' => $answerid]);
        $answerrecord->{$answercol} = $newvalue;
        $answerrecord->timemodified = time();
        $DB->update_record($answertable, $answerrecord);

        $helper->clear_caches($pageid);
        $helper->notify_data_changed($helper->get_page($pageid), 'answers');

        return new self($helper->get_page($pageid), $answerrecord);
    }
}
