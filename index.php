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
 * Display information about all the mod_mootimeter modules in the requested course.
 *
 * @package     mod_mootimeter
 * @copyright   2023, ISB Bayern
 * @author      Peter Mayer <peter.mayer@isb.bayern.de>
 * @license     https://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

require(__DIR__ . '/../../config.php');

use core\output\html_writer;
use core\url;
use core_table\output\html_table;

$id = required_param('id', PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
require_course_login($course);

$PAGE->set_url(new url('/mod/mootimeter/index.php', ['id' => $id]));
$PAGE->set_title(format_string($course->fullname));
$PAGE->set_heading(format_string($course->fullname));
$PAGE->set_context(\core\context\course::instance($course->id));

$modulenameplural = get_string('modulenameplural', 'mod_mootimeter');
$mootimeters = get_all_instances_in_course('mootimeter', $course);
if (empty($mootimeters)) {
    notice(get_string('thereareno', 'moodle', $modulenameplural), new url('/course/view.php', ['id' => $course->id]));
}

$table = new html_table();
$table->attributes['class'] = 'generaltable mod_index';
$withsection = $course->format === 'weeks' || $course->format === 'topics';
$table->head = [get_string('name')];
if ($withsection) {
    array_unshift($table->head, get_string($course->format === 'weeks' ? 'week' : 'topic'));
}

foreach ($mootimeters as $mootimeter) {
    $link = html_writer::link(
        new url('/mod/mootimeter/view.php', ['id' => $mootimeter->coursemodule]),
        format_string($mootimeter->name, true, ['context' => \core\context\module::instance($mootimeter->coursemodule)]),
        $mootimeter->visible ? [] : ['class' => 'dimmed']
    );
    $table->data[] = $withsection ? [$mootimeter->section, $link] : [$link];
}

echo $OUTPUT->header();
echo $OUTPUT->heading($modulenameplural);
echo html_writer::table($table);
echo $OUTPUT->footer();
