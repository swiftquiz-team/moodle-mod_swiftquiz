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
 * @param swiftquiz $swiftquiz
 * @param \moodle_url $url
 * @throws \coding_exception
 * @throws \dml_exception
 * @throws \moodle_exception
 */
function swiftquiz_view_session_report(swiftquiz $swiftquiz, \moodle_url $url) {
    $sessionid = optional_param('sessionid', 0, PARAM_INT);
    if ($sessionid === 0) {
        // If no session id is specified, we try to load the first one.
        global $DB;
        $sessions = $DB->get_records('swiftquiz_sessions', [
            'swiftquizid' => $swiftquiz->data->id
        ]);
        if (count($sessions) === 0) {
            echo get_string('no_sessions_exist', 'swiftquiz');
            return;
        }
        $sessionid = current($sessions)->id;
    }
    $session = new swiftquiz_session($swiftquiz, $sessionid);
    $session->load_attempts();
    $url->param('sessionid', $sessionid);
    $context = $swiftquiz->renderer->view_session_report($session, $url);
    echo $swiftquiz->renderer->render_from_template('swiftquiz/report', $context);
}

/**
 * @param swiftquiz $swiftquiz
 * @throws \coding_exception
 */
function swiftquiz_export_session_report(swiftquiz $swiftquiz) {
    $what = required_param('what', PARAM_ALPHANUM);
    $sessionid = required_param('sessionid', PARAM_INT);
    $session = new swiftquiz_session($swiftquiz, $sessionid);
    $session->load_attempts();
    $attempt = reset($session->attempts);
    if (!$attempt) {
        return;
    }
    header('Content-Type: application/csv');
    $exporter = new exporter();
    switch ($what) {
        case 'report':
            $exporter->export_session_csv($session, $session->attempts);
            break;
        case 'question':
            $slot = required_param('slot', PARAM_INT);
            $exporter->export_session_question_csv($session, $attempt, $slot);
            break;
        case 'attendance':
            $exporter->export_attendance_csv($session);
            break;
        default:
            break;
    }
}

/**
 * Entry point for viewing reports.
 */
function swiftquiz_reports() {
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
}

swiftquiz_reports();
