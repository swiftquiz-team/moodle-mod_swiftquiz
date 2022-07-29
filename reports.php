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
 * The reports page
 *
 * This handles displaying results
 *
 * @package   mod_swiftquiz
 * @author    Sebastian S. Gundersen <sebastsg@stud.ntnu.no>
 * @copyright 2018 NTNU
 * @copyright 2014 University of Wisconsin - Madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_swiftquiz;

require_once("../../config.php");
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/mod/swiftquiz/locallib.php');
require_once($CFG->dirroot . '/question/editlib.php');
require_once($CFG->dirroot . '/mod/swiftquiz/lib.php');
require_once($CFG->libdir . '/tablelib.php');

require_login();



/**
 * Entry point for viewing reports.
 */

global $PAGE;
$cmid = optional_param('id', false, PARAM_INT);
if (!$cmid) {
    // Probably a login redirect that doesn't include any ID.
    // Go back to the main Moodle page, because we have no info.
    header('Location: /');
    exit;
}
$swiftquiz = new swiftquiz($cmid);
require_capability('mod/swiftquiz:seeresponses', $swiftquiz->context);
$action = optional_param('action', '', PARAM_ALPHANUM);

$url = new \moodle_url('/mod/swiftquiz/reports.php');
$url->param('id', $cmid);
$url->param('quizid', $swiftquiz->data->id);
$url->param('action', $action);

$modulename = get_string('modulename', 'swiftquiz');
$quizname = format_string($swiftquiz->data->name, true);

$PAGE->set_pagelayout('incourse');
$PAGE->set_context($swiftquiz->context);
$PAGE->set_title(strip_tags($swiftquiz->course->shortname . ': ' . $modulename . ': ' . $quizname));
$PAGE->set_heading($swiftquiz->course->fullname);
$PAGE->set_url($url);
$isdownload = optional_param('download', false, PARAM_BOOL);
if (!$isdownload) {
    $swiftquiz->renderer->header($swiftquiz, 'reports');
}
switch ($action) {
    case 'view':
        swiftquiz_view_session_report($swiftquiz, $url);
        break;
    case 'export':
        swiftquiz_export_session_report($swiftquiz);
        break;
    default:
        swiftquiz_view_session_report($swiftquiz, $url);
        break;
}
if (!$isdownload) {
    $swiftquiz->renderer->footer();
}
