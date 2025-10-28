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
 * Helper class to handle inplace edit of answeroptions.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_wordcloud\local;

use coding_exception;
use dml_exception;

/**
 * Helper class to handle inplace edit of answeroptions.
 *
 * @package     mootimetertool_wordcloud
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class inplace_edit_answer extends \core\output\inplace_editable {
    /**
     * Constructor.
     *
     * @param object $page
     * @param object $answer
     * @return void
     * @throws dml_exception
     * @throws coding_exception
     */
    public function __construct(object $page, object $answer) {

        $wordcloud = new \mootimetertool_wordcloud\wordcloud();

        $instance = $wordcloud::get_instance_by_pageid($page->id);
        $cm = $wordcloud::get_cm_by_instance($instance);

        parent::__construct(
            'mootimeter',
            'wordcloud_editanswer',
            $page->id . "_" . $answer->id,
            has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id)),
            format_string($answer->{$wordcloud::ANSWER_COLUMN}),
            $answer->{$wordcloud::ANSWER_COLUMN}
        );
    }

    /**
     * Updates the value in database and returns itself, called from inplace_editable callback
     *
     * @param mixed $itemid
     * @param mixed $newvalue
     * @return \self
     */
    public static function update(mixed $itemid, mixed $newvalue) {
        global $DB;

        $newvalue = clean_param($newvalue, PARAM_TEXT);

        $helper = new \mod_mootimeter\helper();

        [$pageid, $answerid] = explode("_", $itemid);

        $answertable = $helper->get_tool_answer_table($pageid);
        $answercol = $helper->get_tool_answer_column($pageid);

        // First update the existing value.
        $answerrecord = $DB->get_record($answertable, ['id' => $answerid]);
        $answerrecord->{$answercol} = $newvalue;
        $DB->update_record($answertable, $answerrecord);

        // Now clear the answers cache.
        $helper->clear_caches($pageid);

        // Finally return itself.
        $tmpl = new self($helper->get_page($pageid), $answerrecord);
        return $tmpl;
    }
}
