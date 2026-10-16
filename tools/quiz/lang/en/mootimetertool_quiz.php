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
 * Plugin strings are defined here.
 *
 * @package     mootimetertool_quiz
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['add_question_option'] = "Add answer option";
$string['answeringclosed'] = 'Answering is closed because the correct answers are shown.';
$string['answeroptions'] = "Answer options";
$string['answers_max_number'] = 'Maximum number of answers that can be selected per participant (0: unlimited)';
$string['atleastoneanswer'] = 'You need to select at least one answer';
$string['correct_answer'] = 'Correct answer';
$string['correctionnotsupported'] = 'Polls have no correct answers.';
$string['csv_answeroption'] = 'Answer option';
$string['enter_answeroption'] = 'Please enter an answer option.';
$string['import_options'] = 'Import answer options';
$string['import_options_append'] = 'Keep them and append the imported answer options';
$string['import_options_description'] = 'Upload a CSV or text file with one answer option per line. Only the first column is used. Comma, semicolon and tab are recognised as separators; an answer option containing the separator must be enclosed in double quotes. At most {$a} answer options can be imported at once.';
$string['import_options_example'] = 'Download example file';
$string['import_options_file'] = 'CSV file';
$string['import_options_mode'] = 'Existing answer options';
$string['import_options_none'] = 'The file does not contain any answer options. Expected is one answer option per line.';
$string['import_options_replace'] = 'Replace them; all answers given so far are deleted';
$string['import_options_submit'] = 'Import';
$string['import_options_success'] = '{$a} answer options imported.';
$string['import_options_toomany'] = 'At most {$a} answer options can be imported at once.';
$string['mark_correct_answer'] = 'Please mark the correct answer (optional)';
$string['no_answer_due_to_showteacherpermission'] = 'The teacher must first allow the results to be displayed.';
$string['optionnotfound'] = 'The answer option {$a} does not exist.';
$string['pluginname'] = 'Quiz';
$string['privacy:answerspath'] = 'Antworten';
$string['privacy:metadata:mootimetertool_quiz_answers'] = 'The quiz tool stores user answers.';
$string['privacy:metadata:mootimetertool_quiz_answers:optionid'] = 'Option ID';
$string['privacy:metadata:mootimetertool_quiz_answers:pageid'] = 'Page ID';
$string['privacy:metadata:mootimetertool_quiz_answers:timecreated'] = 'Answer created';
$string['privacy:metadata:mootimetertool_quiz_answers:timemodified'] = 'Answer modified';
$string['privacy:metadata:mootimetertool_quiz_answers:userid'] = 'User ID';
$string['privacy:metadata:mootimetertool_quiz_options'] = 'The quiz tool stores answer options.';
$string['privacy:metadata:mootimetertool_quiz_options:optioniscorrect'] = 'Whether the option is correct';
$string['privacy:metadata:mootimetertool_quiz_options:optiontext'] = 'Option text';
$string['privacy:metadata:mootimetertool_quiz_options:pageid'] = 'Page ID';
$string['privacy:metadata:mootimetertool_quiz_options:timecreated'] = 'Option created';
$string['privacy:metadata:mootimetertool_quiz_options:timemodified'] = 'Option modified';
$string['responsenotfound'] = 'The response {$a} does not exist.';
$string['sendbutton_context_more_answers_possible'] = 'You can choose more than one answer.';
$string['sendbutton_context_one_answers_possible'] = 'You can choose one answer.';
$string['show_results'] = "Show results";
$string['submit_answer'] = 'Submit answer';
$string['tool_description_short'] = "Live quiz with correct answers";
$string['tooltip_content_menu_answercorrection'] = 'Click to show students if answer is correct';
$string['tooltip_content_menu_answercorrection_disabled'] = 'Click to do not show students if answer is correct';
$string['toomanyanswers'] = 'You can select at most {$a} answers.';
$string['toomanyoptions'] = 'At most {$a} answer options can be added at once.';
$string['unknownoption'] = 'The answer option does not belong to this page.';
$string['visualization'] = 'Visualization';
$string['visualization_bar'] = 'Bar chart';
$string['visualization_pie'] = 'Pie chart';
$string['visualization_pillar'] = 'Column chart';
