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
 * Prints an instance of mod_mootimeter.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');
require_once(__DIR__ . '/lib.php');

$id = optional_param('id', 0, PARAM_INT);
$instanceid = optional_param('m', 0, PARAM_INT);
$pageid = optional_param('pageid', 0, PARAM_INT);
$action = optional_param('a', '', PARAM_ALPHA);
$isresultpage = optional_param('r', false, PARAM_BOOL);
$isansweroverview = optional_param('o', false, PARAM_BOOL);
$isfullscreen = optional_param('f', false, PARAM_BOOL);

if ($id) {
    [$course, $cm] = get_course_and_cm_from_cmid($id, 'mootimeter');
} else {
    [$course, $cm] = get_course_and_cm_from_instance($instanceid, 'mootimeter');
}
$moduleinstance = $DB->get_record('mootimeter', ['id' => $cm->instance], '*', MUST_EXIST);

require_login($course, true, $cm);
$context = \core\context\module::instance($cm->id);
require_capability('mod/mootimeter:view', $context);

if ($pageid && !$DB->record_exists('mootimeter_pages', ['id' => $pageid, 'instance' => $cm->instance])) {
    $pageid = 0;
}

mootimeter_trigger_event_course_module_viewed($moduleinstance, $context, $course);

$PAGE->set_url(new \core\url('/mod/mootimeter/view.php', array_filter(['id' => $cm->id, 'pageid' => $pageid])));
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($context);

$view = 'question';
if ($action === 'addpage') {
    $view = 'addpage';
} else if ($isresultpage) {
    $view = 'results';
} else if ($isansweroverview) {
    $view = 'overview';
}

echo $OUTPUT->header();
echo $OUTPUT->render(new \mod_mootimeter\output\app(
    cmid: (int) $cm->id,
    pageid: $pageid,
    view: $view,
    fullscreen: $isfullscreen,
    ismoderator: has_capability('mod/mootimeter:moderator', $context),
    isediting: $PAGE->user_is_editing(),
    refreshinterval: (int) get_config('mod_mootimeter', 'refreshinterval'),
));
echo $OUTPUT->footer();
