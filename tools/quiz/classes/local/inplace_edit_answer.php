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
 * @package     mootimetertool_quiz
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mootimetertool_quiz\local;

use coding_exception;
use dml_exception;

/**
 * Helper class to handle inplace edit of answeroptions.
 *
 * @package     mootimetertool_quiz
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

        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;
        $toolhelper = new $classname();

        $answeroptionstrings = \mootimetertool_quiz\quiz::extract_answer_option_strings($toolhelper->get_answer_options($page->id));

        $useransweroptionids = explode(';', $answer->optionid);
        $useransweroptionstrings = implode(
            ' | ',
            array_map(fn($useransweroptionid) => $answeroptionstrings[$useransweroptionid], $useransweroptionids)
        );

        $instance = $toolhelper::get_instance_by_pageid($page->id);
        $cm = $toolhelper::get_cm_by_instance($instance);

        parent::__construct(
            'mootimeter',
            'quiz_editanswerselect',
            $page->id . "_" . $answer->id,
            has_capability('mod/mootimeter:moderator', \context_module::instance($cm->id)),
            $useransweroptionstrings,
            json_encode($useransweroptionids),
        );
        $this->set_type_autocomplete(
            $answeroptionstrings,
            [
                        'multiple' => intval($toolhelper::get_tool_config($page->id, "maxanswersperuser")) !== 1,
                ]
        );
    }

    /**
     * Updates the value in database and returns itself, called from inplace_editable callback
     *
     * @param int $itemid
     * @param mixed $newvalue
     * @return \self
     */
    public static function update($itemid, $newvalue) {
        global $DB, $PAGE;

        // Clean the new value.
        $newvalue = clean_param($newvalue, PARAM_NOTAGS);

        // Extract pageid and answerid.
        [$pageid, $answerid] = explode("_", $itemid);
        $helper = new \mod_mootimeter\helper();
        $page = $helper->get_page($pageid);
        $classname = "\mootimetertool_" . $page->tool . "\\" . $page->tool;
        /** @var \mootimetertool_quiz\quiz $toolhelper */
        $toolhelper = new $classname();

        $answeroptions = $toolhelper->get_answer_options($page->id);

        // Check capabilities.
        $instance = $helper::get_instance_by_pageid($pageid);
        $cm = $helper::get_cm_by_instance($instance);
        $modulecontext = \context_module::instance($cm->id);
        $PAGE->set_context($modulecontext);
        require_capability('mod/mootimeter:moderator', \context_module::instance($cm->id));

        $answertable = $helper->get_tool_answer_table($pageid);
        $answercol = $helper->get_tool_answer_column($pageid);

        $userid = intval($DB->get_record($answertable, ['id' => $answerid])->usermodified);

        $newansweroptionids = json_decode($newvalue, true);

        $answers = $toolhelper->get_answers($answertable, $pageid);
        $answersofuser = array_filter($answers, fn($answer) => intval($answer->usermodified) === $userid);

        if (empty($newansweroptionids)) {
            // The user has deselected all answers, that must not happen.
            throw new \moodle_exception('atleastoneanswer', 'mootimetertool_quiz');
        }

        if (!is_array($newansweroptionids)) {
            // In case of just a single answer is allowed, the inplace_editable will only send a string containing an id, so we have
            // to bring it into array form.
            $newansweroptionids = (array) $newansweroptionids;
        }

        $maxanswersperuser = intval($toolhelper::get_tool_config($page->id, 'maxanswersperuser'));
        if ($maxanswersperuser !== 0 && count($newansweroptionids) > $maxanswersperuser) {
            throw new \moodle_exception('morethanmaxanswers', 'mootimetertool_quiz', '', $maxanswersperuser);
        }

        // If the user has removed all the options, we throw an exception. The user would have to delete the answer.
        // Now check if the answer option ids are allowed ones/belong to the current question.
        foreach ($newansweroptionids as $answeroptionid) {
            if (!array_key_exists(intval($answeroptionid), array_map(fn($answeroption) => $answeroption->id, $answeroptions))) {
                throw new \moodle_exception('invalidparameter', 'debug');
            }
        }

        // Now update the answer objects.
        // We iterate over the currently existing answers, and update them with the new values of the user.
        // If we have new answers left, we add one. If we have more current answers then new user answers, we add them.
        foreach ($answersofuser as $answerofuserid => $answerofuser) {
            $newansweroptionid = array_pop($newansweroptionids);
            if (!is_null($newansweroptionid)) {
                $answerofuser->{$answercol} = $newansweroptionid;
                $answerofuser->timemodified = time();
                $DB->update_record($answertable, $answerofuser);
            } else {
                $DB->delete_records($answertable, ['id' => $answerofuser->id]);
            }
            unset($answersofuser[$answerofuserid]);
        }
        if (!empty($newansweroptionids)) {
            foreach ($newansweroptionids as $newansweroptionid) {
                $record = new \stdClass();
                $record->pageid = $pageid;
                $record->usermodified = $userid;
                $record->optionid = $newansweroptionid;
                $record->timecreated = time();
                $DB->insert_record($answertable, $record);
            }
        }

        // Now clear the answers cache to make the new answer instantly viewable.
        $helper->clear_caches($pageid);

        // Finally return itself.
        $groupedanswers = $toolhelper->convert_answers_to_grouped_answers($toolhelper->get_answers($answertable, $pageid));
        $groupedanswers = array_filter($groupedanswers, fn($groupedanswer) => intval($groupedanswer->usermodified) === $userid);
        $tmpl = new self($helper->get_page($pageid), array_pop($groupedanswers));
        return $tmpl;
    }
}
