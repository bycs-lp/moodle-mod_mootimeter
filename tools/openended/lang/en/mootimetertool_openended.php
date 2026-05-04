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
 * English language strings for mootimetertool_openended.
 *
 * @package     mootimetertool_openended
 * @category    string
 * @copyright   2026, ISB Bayern
 * @author      Benedikt Blumenfelder
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['answers_max_number'] = 'Maximum number of contributions per participant (0: unlimited)';
$string['cannot_react_to_own'] = 'You cannot react to your own contribution.';
$string['enablereactions'] = 'Allow emoji reactions on contributions';
$string['error_answer_hidden'] = 'This contribution has been hidden by the teacher.';
$string['error_cannot_react_to_own'] = 'You cannot react to your own contribution.';
$string['error_empty_answers'] = 'Empty contributions are not allowed';
$string['error_reactions_disabled'] = 'Reactions are disabled for this page.';
$string['error_to_many_answers'] = 'You reached the maximum amount of contributions';
$string['error_too_long'] = 'Contributions may not exceed {$a} characters.';
$string['error_unknown_emoji'] = 'Unknown emoji.';
$string['heading_answer'] = 'Contributions';
$string['maxcharacters'] = 'Maximum length per contribution (1-255)';
$string['maxcharacters_help'] = 'Maximum number of characters allowed per contribution. Default is 200.';
$string['no_answer'] = 'No contributions yet.';
$string['no_answer_due_to_showteacherpermission'] = 'The teacher must first allow the contributions to be displayed.';
$string['pluginname'] = 'Open ended';
$string['privacy:answerspath'] = 'Contributions';
$string['privacy:reactionspath'] = 'Reactions';
$string['privacy:metadata:mootimetertool_openended_answers'] = 'Stores open-ended text contributions per page.';
$string['privacy:metadata:mootimetertool_openended_answers:answer'] = 'Submitted contribution text';
$string['privacy:metadata:mootimetertool_openended_answers:pageid'] = 'Page ID';
$string['privacy:metadata:mootimetertool_openended_answers:timecreated'] = 'Submission created';
$string['privacy:metadata:mootimetertool_openended_answers:timemodified'] = 'Submission modified';
$string['privacy:metadata:mootimetertool_openended_answers:userid'] = 'User ID';
$string['privacy:metadata:mootimetertool_openended_answers:visible'] = 'Visibility flag';
$string['privacy:metadata:mootimetertool_openended_reactions'] = 'Stores per-user emoji reactions on contributions.';
$string['privacy:metadata:mootimetertool_openended_reactions:answerid'] = 'Answer the reaction belongs to';
$string['privacy:metadata:mootimetertool_openended_reactions:emoji'] = 'Emoji slug (e.g. thumbsup)';
$string['privacy:metadata:mootimetertool_openended_reactions:pageid'] = 'Page ID';
$string['privacy:metadata:mootimetertool_openended_reactions:timecreated'] = 'Reaction created';
$string['privacy:metadata:mootimetertool_openended_reactions:userid'] = 'User ID';
$string['reaction_heart'] = 'Love';
$string['reaction_laugh'] = 'Funny';
$string['reaction_thumbsup'] = 'Like';
$string['reaction_think'] = 'Thinking';
$string['reaction_wow'] = 'Wow';
$string['reactioncount'] = 'Reactions';
$string['settings'] = 'Settings';
$string['showresultlive'] = 'Show results live';
$string['showresultteacherpermission'] = 'Show results on teacher permission';
$string['submit'] = 'Submit';
$string['tool_description_short'] = 'Collect anonymous text contributions with emoji reactions';
$string['toggle_visibility_title'] = 'Show or hide this contribution for students';
$string['type_answer'] = 'Write your contribution here…';
