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
 * The main mod_mootimeter configuration form.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/course/moodleform_mod.php');

/**
 * Module instance settings form.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class mod_mootimeter_mod_form extends moodleform_mod {
    /**
     * Defines forms elements
     */
    public function definition() {
        global $CFG;

        $mform = $this->_form;

        // Adding the "general" fieldset, where all the common settings are shown.
        $mform->addElement('header', 'general', get_string('general', 'form'));

        // Adding the standard "name" field.
        $mform->addElement('text', 'name', get_string('mootimetername', 'mod_mootimeter'), ['size' => '64']);

        if (!empty($CFG->formatstringstriptags)) {
            $mform->setType('name', PARAM_TEXT);
        } else {
            $mform->setType('name', PARAM_CLEANHTML);
        }

        $mform->addRule('name', null, 'required', null, 'client');
        $mform->addRule('name', get_string('maximumchars', '', 255), 'maxlength', 255, 'client');
        $mform->addHelpButton('name', 'mootimetername', 'mod_mootimeter');

        // Adding the standard "intro" and "introformat" fields.
        $this->standard_intro_elements();

        // Add standard elements.
        $this->standard_coursemodule_elements();

        // Add standard buttons.
        $this->add_action_buttons();
    }

    #[\Override]
    public function add_completion_rules() {
        $mform = $this->_form;
        $suffix = $this->get_suffix();
        $groups = [];

        foreach (['completionanswerpages', 'completioncorrectpages'] as $rule) {
            $enabled = $rule . 'enabled' . $suffix;
            $number = $rule . $suffix;
            $group = [
                $mform->createElement('checkbox', $enabled, '', get_string($rule, 'mod_mootimeter')),
                $mform->createElement('text', $number, '', ['size' => 3]),
            ];
            $mform->setType($number, PARAM_INT);
            $groups[] = $rule . 'group' . $suffix;
            $mform->addGroup($group, $rule . 'group' . $suffix, '', ' ', false);
            $mform->hideIf($number, $enabled, 'notchecked');
            $mform->addHelpButton($rule . 'group' . $suffix, $rule, 'mod_mootimeter');
        }

        $answerall = 'completionanswerall' . $suffix;
        $mform->addElement('checkbox', $answerall, '', get_string('completionanswerall', 'mod_mootimeter'));
        $mform->addHelpButton($answerall, 'completionanswerall', 'mod_mootimeter');
        $groups[] = $answerall;

        return $groups;
    }

    #[\Override]
    public function completion_rule_enabled($data) {
        $suffix = $this->get_suffix();
        foreach (['completionanswerpages', 'completioncorrectpages'] as $rule) {
            if (!empty($data[$rule . 'enabled' . $suffix]) && !empty($data[$rule . $suffix])) {
                return true;
            }
        }
        return !empty($data['completionanswerall' . $suffix]);
    }

    #[\Override]
    public function data_preprocessing(&$defaultvalues) {
        parent::data_preprocessing($defaultvalues);

        $suffix = $this->get_suffix();
        foreach (['completionanswerpages', 'completioncorrectpages'] as $rule) {
            // The number is preset with 1, it only applies once the checkbox is ticked.
            $defaultvalues[$rule . 'enabled' . $suffix] = empty($defaultvalues[$rule . $suffix]) ? 0 : 1;
            if (empty($defaultvalues[$rule . $suffix])) {
                $defaultvalues[$rule . $suffix] = 1;
            }
        }
    }

    #[\Override]
    public function data_postprocessing($data) {
        parent::data_postprocessing($data);

        if (empty($data->completionunlocked)) {
            return;
        }
        $suffix = $this->get_suffix();
        $completion = $data->{'completion' . $suffix} ?? COMPLETION_TRACKING_NONE;
        $automatic = $completion == COMPLETION_TRACKING_AUTOMATIC;
        foreach (['completionanswerpages', 'completioncorrectpages'] as $rule) {
            if (!$automatic || empty($data->{$rule . 'enabled' . $suffix})) {
                $data->{$rule . $suffix} = 0;
            }
        }
        $data->{'completionanswerall' . $suffix} = $automatic && !empty($data->{'completionanswerall' . $suffix}) ? 1 : 0;
    }

    #[\Override]
    public function validation($data, $files) {
        $errors = parent::validation($data, $files);

        $suffix = $this->get_suffix();
        foreach (['completionanswerpages', 'completioncorrectpages'] as $rule) {
            if (!empty($data[$rule . 'enabled' . $suffix]) && (int) $data[$rule . $suffix] < 1) {
                $errors[$rule . 'group' . $suffix] = get_string('completionpagesatleastone', 'mod_mootimeter');
            }
        }
        return $errors;
    }
}
