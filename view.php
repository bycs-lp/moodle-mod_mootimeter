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

// Course module id.
$id = optional_param('id', 0, PARAM_INT);
$action = optional_param('a', "", PARAM_ALPHA);
$pageid = optional_param('pageid', 0, PARAM_INT);
$isresultpage = optional_param('r', false, PARAM_BOOL);
$isansweroverview = optional_param('o', false, PARAM_BOOL);
$isfullscreen = optional_param('f', false, PARAM_BOOL);
$helper = new \mod_mootimeter\helper();

// Set the pageid pageparam for $PAGE object.
if ($pageid) {
    $pageparams['pageid'] = $pageid;
}

// Activity instance id.
$m = optional_param('m', 0, PARAM_INT);

if ($id) {
    $cm = get_coursemodule_from_id('mootimeter', $id, 0, false, MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $cm->course], '*', MUST_EXIST);
    $moduleinstance = $DB->get_record('mootimeter', ['id' => $cm->instance], '*', MUST_EXIST);
} else {
    $moduleinstance = $DB->get_record('mootimeter', ['id' => $m], '*', MUST_EXIST);
    $course = $DB->get_record('course', ['id' => $moduleinstance->course], '*', MUST_EXIST);
    $cm = get_coursemodule_from_instance('mootimeter', $moduleinstance->id, $course->id, false, MUST_EXIST);
}

// Set the cm pageparam for $PAGE object.
$pageparams['id'] = $cm->id;

// Check if user is logged in.
require_login($course, true, $cm);
$modulecontext = context_module::instance($cm->id);

$pages = $helper->get_pages($cm->instance);

// Check if this page is in the recent mootimeter instance.
if (!empty($pageid) && !$helper::validate_page_belongs_to_instance($pageid, $pages)) {
    $page = array_pop($pages);
    redirect(new moodle_url('/mod/mootimeter/view.php', ['id' => $cm->id, 'pageid' => $page->id]));
}

// If there is only one page. Redirect to this page if there is no pageid set.
if (count($pages) == 1 && empty($pageid) && $action != "addpage") {
    $page = array_pop($pages);
    redirect(new moodle_url('/mod/mootimeter/view.php', ['id' => $cm->id, 'pageid' => $page->id]));
}

$modulecontext = context_module::instance($cm->id);
mootimeter_trigger_event_course_module_viewed($moduleinstance, $modulecontext, $course);

$PAGE->set_url('/mod/mootimeter/view.php', $pageparams);
$PAGE->set_title(format_string($moduleinstance->name));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context($modulecontext);

$view = 'question';
if ($action === 'addpage') {
    $view = 'addpage';
} else if ($isresultpage) {
    $view = 'results';
} else if ($isansweroverview) {
    $view = 'overview';
}

$props = [
    'cmid' => (int) $cm->id,
    'pageid' => (int) $pageid,
    'view' => $view,
    'fullscreen' => (bool) $isfullscreen,
    'ismoderator' => has_capability('mod/mootimeter:moderator', $modulecontext),
    'isediting' => $PAGE->user_is_editing(),
    'refreshinterval' => (int) get_config('mod_mootimeter', 'refreshinterval'),
];

// START OUTPUT.
echo $OUTPUT->header();
echo $OUTPUT->render_from_template('mod_mootimeter/main', ['props' => json_encode($props)]);
echo $OUTPUT->footer();
