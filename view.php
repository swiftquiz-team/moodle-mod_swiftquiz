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
 * The view page where you can start or participate in a session.
 *
 * @package   mod_swiftquiz
 * @author    Sebastian S. Gundersen <sebastsg@stud.ntnu.no>
 * @copyright 2014 University of Wisconsin - Madison
 * @copyright 2018 NTNU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_swiftquiz;

require_once("../../config.php");
require_once($CFG->dirroot . '/mod/swiftquiz/lib.php');
require_once($CFG->dirroot . '/mod/swiftquiz/locallib.php');
require_once($CFG->libdir . '/questionlib.php');
require_once($CFG->dirroot . '/question/editlib.php');

require_login();

/**
 * View the quiz page.
 * @param swiftquiz $swiftquiz
 * @throws \dml_exception
 * @throws \moodle_exception
 */
function swiftquiz_view_start_quiz(swiftquiz $swiftquiz) {
    global $PAGE;
    // Set the quiz view page to the base layout for 1 column layout.
    $PAGE->set_pagelayout('base');
    $PAGE->requires->js('/mod/swiftquiz/js/wordcloud2/wordcloud2.js');
    $session = $swiftquiz->load_open_session();
    if (!$session) {
        swiftquiz_view_default($swiftquiz);
        return;
    }
    $session->load_session_questions();
    $session->load_attempts();
    $session->initialize_attempt();
    if ($swiftquiz->is_instructor()) {
        $session->attempt->data->status = swiftquiz_attempt::PREVIEW;
    } else {
        $session->attempt->data->status = swiftquiz_attempt::INPROGRESS;
    }
    $session->attempt->save();

    // Initialize JavaScript for the question engine.
    // TODO: Not certain if this is needed. Should be checked further.
    \question_engine::initialise_js();

    $renderer = $swiftquiz->renderer;
    $renderer->header($swiftquiz, 'view');
    $renderer->render_quiz($session);
    $renderer->footer();
}

/**
 * View the "Continue session" or "Start session" form.
 * @param swiftquiz $swiftquiz
 * @throws \dml_exception
 * @throws \moodle_exception
 */
function swiftquiz_view_default_instructor(swiftquiz $swiftquiz) {
    global $PAGE, $DB;
    $startsessionform = new forms\view\start_session($PAGE->url, ['swiftquiz' => $swiftquiz]);
    $data = $startsessionform->get_data();
    if ($data) {
        $sessions = $DB->get_records('swiftquiz_sessions', [
            'swiftquizid' => $swiftquiz->data->id,
            'sessionopen' => 1
        ]);
        // Only create session if not already open.
        if (empty($sessions)) {
            $allowguests = isset($data->allowguests) ? 1 : 0;
            $sessionid = $swiftquiz->create_session($data->session_name, $data->anonymity, $allowguests);
            if ($sessionid === false) {
                return;
            }
        } else {
            redirect($PAGE->url, 'A session is already open.', 0);
            // Note: Redirect exits.
        }
        // Redirect to the quiz start.
        $quizstarturl = clone($PAGE->url);
        $quizstarturl->param('action', 'quizstart');
        redirect($quizstarturl, null, 0);
        // Note: Redirect exits.
    }
    $renderer = $swiftquiz->renderer;
    $renderer->header($swiftquiz, 'view');
    if ($swiftquiz->is_session_open()) {
        $renderer->continue_session_form($swiftquiz);
    } else {
        $renderer->start_session_form($startsessionform);
    }
    $renderer->footer();
}

/**
 * View the "Join quiz" or "Quiz not running" form.
 * @param swiftquiz $swiftquiz
 * @throws \moodle_exception
 */
function swiftquiz_view_default_student(swiftquiz $swiftquiz) {
    global $PAGE;
    $studentstartform = new forms\view\student_start_form($PAGE->url);
    $data = $studentstartform->get_data();
    if ($data) {
        $quizstarturl = clone($PAGE->url);
        $quizstarturl->param('action', 'quizstart');
        redirect($quizstarturl, null, 0);
        // Note: Redirect exits.
    }

    /** @var output\renderer $renderer */
    $renderer = $swiftquiz->renderer;
    $renderer->header($swiftquiz, 'view');
    $session = $swiftquiz->load_open_session();
    if ($session) {
        $renderer->join_quiz_form($studentstartform, $session);
    } else {
        $renderer->quiz_not_running($swiftquiz->cm->id);
    }
    $renderer->footer();
}

/**
 * View appropriate form based on role and session state.
 * @param swiftquiz $swiftquiz
 * @throws \dml_exception
 * @throws \moodle_exception
 */
function swiftquiz_view_default(swiftquiz $swiftquiz) {
    if ($swiftquiz->is_instructor()) {
        // Show "Start quiz" form.
        swiftquiz_view_default_instructor($swiftquiz);
    } else {
        // Show "Join quiz" form.
        swiftquiz_view_default_student($swiftquiz);
    }
}

/**
 * Entry point for viewing a quiz.
 */
function swiftquiz_view() {
    global $PAGE;
    $cmid = optional_param('id', false, PARAM_INT);
    if (!$cmid) {
        // Probably a login redirect that doesn't include any ID.
        // Go back to the main Moodle page, because we have no info.
        header('Location: /');
        exit;
    }
    $action = optional_param('action', '', PARAM_ALPHANUM);
    $swiftquiz = new swiftquiz($cmid);
    $session = $swiftquiz->load_open_session();
    if (!$session || !$session->data->allowguests) {
        require_capability('mod/swiftquiz:attempt', $swiftquiz->context);
    }
    $PAGE->set_pagelayout('incourse');
    $PAGE->set_context($swiftquiz->context);
    $PAGE->set_cm($swiftquiz->cm);
    $modname = get_string('modulename', 'swiftquiz');
    $quizname = format_string($swiftquiz->data->name, true);
    $PAGE->set_title(strip_tags($swiftquiz->course->shortname . ': ' . $modname . ': ' . $quizname));
    $PAGE->set_heading($swiftquiz->course->fullname);
    $url = new \moodle_url('/mod/swiftquiz/view.php');
    $url->param('id', $cmid);
    $url->param('quizid', $swiftquiz->data->id);
    $url->param('action', $action);
    $PAGE->set_url($url);


    if ($action === 'quizstart') {
        swiftquiz_view_start_quiz($swiftquiz);
    } else {
        swiftquiz_view_default($swiftquiz);
    }
    if ($swiftquiz->is_instructor()) {
        $improviser = new improviser($swiftquiz);
        $improviser->insert_default_improvised_question_definitions();
    }

}

swiftquiz_view();
