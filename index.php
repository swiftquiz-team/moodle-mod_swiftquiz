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
 * This page lists all the instances of swiftquiz in a particular course
 *
 * @package   mod_swiftquiz
 * @author    Davo Smith
 * @copyright Davo Smith
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 **/

require_once('../../config.php');
require_once('lib.php');

$id = required_param('id', PARAM_INT); // Course ID.
$course = $DB->get_record('course', ['id' => $id]);
if (!$course) {
    error('Course ID is incorrect');
}

$PAGE->set_url(new moodle_url('/mod/swiftquiz/index.php', ['id' => $course->id]));
require_course_login($course);
$PAGE->set_pagelayout('incourse');

// Get all required strings.
$strswiftquizzes = get_string('modulenameplural', 'swiftquiz');
$strswiftquiz = get_string('modulename', 'swiftquiz');

$PAGE->navbar->add($strswiftquizzes);
$PAGE->set_title(strip_tags($course->shortname . ': ' . $strswiftquizzes));
$PAGE->set_heading($course->fullname);
echo $OUTPUT->header();

// Get all the appropriate data.
$swiftquizzes = get_all_instances_in_course('swiftquiz', $course);
if (!$swiftquizzes) {
    notice('There are no swiftquizes', "../../course/view.php?id=$course->id");
    die;
}

// Print the list of instances.
$timenow = time();
$strname = get_string('name');
$strweek = get_string('week');
$strtopic = get_string('topic');

$table = new html_table();

if ($course->format == 'weeks') {
    $table->head = [$strweek, $strname];
    $table->align = ['center', 'left'];
} else if ($course->format == "topics") {
    $table->head = [$strtopic, $strname];
    $table->align = ['center', 'left'];
} else {
    $table->head = [$strname];
    $table->align = ['left', 'left'];
}

foreach ($swiftquizzes as $swiftquiz) {
    $url = new moodle_url('/mod/swiftquiz/view.php', [
        'cmid' => $swiftquiz->coursemodule
    ]);
    if (!$swiftquiz->visible) {
        // Show dimmed if the mod is hidden.
        $link = '<a class="dimmed" href="' . $url . '">' . $swiftquiz->name . '</a>';
    } else {
        // Show normal if the mod is visible.
        $link = '<a href="' . $url . '">' . $swiftquiz->name . '</a>';
    }
    if ($course->format == 'weeks' || $course->format == 'topics') {
        $table->data[] = [$swiftquiz->section, $link];
    } else {
        $table->data[] = [$link];
    }
}

echo html_writer::table($table);
echo $OUTPUT->footer();

