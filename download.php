<?php
// This file is part of Moodle - http://moodle.org/
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
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * Download the results of a mootimeter page as CSV.
 *
 * @package     mod_mootimeter
 * @copyright   2026 ISB Bayern
 * @author      Dr. Peter Mayer
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once($CFG->libdir . '/csvlib.class.php');

use mod_mootimeter\helper;

$pageid = required_param('pageid', PARAM_INT);

$page = $DB->get_record('mootimeter_pages', ['id' => $pageid], '*', MUST_EXIST);
[$course, $cm] = get_course_and_cm_from_instance($page->instance, 'mootimeter');

require_login($course, false, $cm);
$context = \core\context\module::instance($cm->id);
require_capability('mod/mootimeter:moderator', $context);

$PAGE->set_url(new \core\url('/mod/mootimeter/download.php', ['pageid' => $page->id]));

$question = format_string(helper::get_tool_config($page, 'question'), true, ['context' => $context, 'escape' => false]);
$sections = helper::get_toolhelper($page->tool)->export_results_csv($page, $context);

$csv = new csv_export_writer('comma', '"', 'text/csv', true);
$csv->set_filename(format_string($cm->name, true, ['context' => $context, 'escape' => false]) . '-' .
    get_string('pluginname', 'mootimetertool_' . $page->tool));
$csv->add_data([get_string('question', 'mod_mootimeter'), $question]);
foreach ($sections as $section) {
    $csv->add_data([]);
    $csv->add_data($section['columns']);
    foreach ($section['rows'] as $row) {
        $csv->add_data($row);
    }
}
$csv->download_file();
