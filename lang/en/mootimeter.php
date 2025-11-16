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
 * @package     mod_mootimeter
 * @category    string
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['addpage'] = 'Add page';
$string['anonymous_name'] = 'Anonymous';
$string['anonymousmode'] = 'If checked, teachers can not view voters names.';
$string['anonymousmode_desc'] = 'This setting can not be changed when answers exist!';
$string['cachedef_answers'] = 'Contains the pages answers.';
$string['cachedef_lastupdated'] = 'Contains the timestamps for managing frontend live updates.';
$string['cacheidentifiernotallowed'] = 'The used cache identifier is not allowed here.';
$string['cannotview'] = 'You are not allowed to access this page!';
$string['chartcolors'] = 'background color for charts';
$string['chartcolors_desc'] = 'background color for geometric elements, comma-separated list';
$string['createnewpagesettings'] = 'New page settings';
$string['default_new_page_visibility'] = 'New pages visible by default';
$string['default_new_page_visibility_desc'] = 'If enabled, new pages are visible by default and can be edited by users immediately';
$string['delete_all_answers_dialog_question'] = 'Do you really want to delete all answers of this page and start from scratch?';
$string['delete_all_answers_dialog_title'] = 'Delete all answers?';
$string['delete_answers_of_user_dialog_question'] = 'Do you really want to delete all answers of this user?';
$string['delete_answers_of_user_dialog_title'] = 'Delete all answers of user';
$string['delete_page'] = 'Delete page';
$string['delete_single_answer_dialog_question'] = 'Do you really want to delete this answer?';
$string['delete_single_answer_dialog_title'] = 'Delete this answer?';
$string['deleteerror'] = 'Something went wrong while deleting the page. Please contact your support.';
$string['dialogue_move_pagelistelement'] = 'Move question page';
$string['enter_question'] = 'Please enter a question.';
$string['loading'] = 'Loading! Please wait...';
$string['modulename'] = 'Mootimeter';
$string['modulename_help'] = 'The Mootimeter activity allows your students to participate live in class in a variety of ways.
<ul>
    <li>Poll: Your learners respond directly to one or more questions.</li>
    <li>Quiz: Live quiz which allows you to show users the correct answers afterwards.</li>
    <li>Wordcloud: Appears immediately after the terms are entered.</li>
</ul>';
$string['modulenameplural'] = 'Mootimeter';
$string['mootimeter:addinstance'] = 'Add mootimeter activity';
$string['mootimeter:moderator'] = 'Mootimeter moderator capability';
$string['mootimeter:view'] = 'Mootimeter view capability';
$string['mootimeterfieldset'] = "More mootimeter settings";
$string['mootimetername'] = "Name";
$string['mootimetername_help'] = "Title of the activity";
$string['mootimetersettings'] = "Settings";
$string['no_pages_header'] = 'Recently there are no questions to answer.';
$string['notification_success_store_answer'] = 'Answer stored successfully';
$string['notification_warning_store_answer'] = 'Answer NOT stored.';
$string['page_detail_could_not_be_store'] = 'Content couldn\'t be stored';
$string['pageaccessexception'] = 'The provided pageid is not part of Mootimeter instance.';
$string['pagetype'] = 'Page Type';
$string['please_select'] = 'Please select!';
$string['please_select_a_page'] = 'Please select a page, to answer a question';
$string['pluginadministration'] = 'Mootimeter administration';
$string['pluginname'] = 'Mootimeter';
$string['pluginnameplural'] = 'Mootimeter activities';
$string['privacy:metadata:mootimetertoolpluginsummary'] = 'Mootimetertools subplugin summary';
$string['privacy:pagepath'] = 'Path of page with id: {$a}';
$string['question'] = 'Question';
$string['refreshinterval'] = 'Refresh interval';
$string['refreshinterval_desc'] = 'Refresh interval in ms for result polling';
$string['resetuserdata'] = 'Delete user data for {$a}';
$string['select_page_type_heading'] = 'Please select the question type of a new question.';
$string['select_page_type_paragraph'] = '';
$string['settings'] = 'settings';
$string['show_answer_overview'] = 'Answer overview';
$string['show_options'] = 'Show options';
$string['show_results'] = 'Show results';
$string['sortorder'] = 'Order';
$string['subplugintype_mootimetertool'] = 'Mootimeter tool';
$string['subplugintype_mootimetertool_plural'] = 'Mootimeter tools';
$string['title'] = 'Page title';
$string['toolsettings'] = 'Tool settings';
$string['tooltip_content_menu_teacherpermission'] = 'Click to allow students to view the results.';
$string['tooltip_content_menu_teacherpermission_disabled'] = 'Click to hide the results from the students.';
$string['tooltip_delete_all_answers'] = 'Delete all answers';
$string['tooltip_disable_page'] = 'Click to disable this page for participants.';
$string['tooltip_enable_page'] = 'Click to enable this page for participants.';
$string['page_hidden_for_participants'] = 'Hidden for participants';
$string['tooltip_fullscreen_compress'] = 'Leave fullscreen mode';
$string['tooltip_fullscreen_expand'] = 'Expand to fullscreen';
$string['tooltip_show_question_page'] = 'Show question page.';
$string['tooltip_show_results_page'] = 'Show results page.';
