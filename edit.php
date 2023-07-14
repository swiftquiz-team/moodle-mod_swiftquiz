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
 * Edit page for editing questions on the quiz
 *
 * @package   mod_swiftquiz
 * @author    Sebastian S. Gundersen <sebastsg@stud.ntnu.no>
 * @copyright 2014 University of Wisconsin - Madison
 * @copyright 2018 NTNU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_swiftquiz;

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/swiftquiz/lib.php');
require_once($CFG->dirroot . '/mod/swiftquiz/locallib.php');
require_once($CFG->dirroot . '/question/editlib.php');

require_login();

/**
 * Check if this swiftquiz has an open session.
 * @param int $swiftquizid
 * @return bool
 * @throws \dml_exception
 */
function swiftquiz_session_open($swiftquizid) {
    global $DB;
    $sessions = $DB->get_records('swiftquiz_sessions', [
        'swiftquizid' => $swiftquizid,
        'sessionopen' => 1
    ]);
    return count($sessions) > 0;
}

/**
 * Gets the question bank view based on the options passed in at the page setup.
 * @param \question_edit_contexts $contexts
 * @param swiftquiz $swiftquiz
 * @param \moodle_url $url
 * @param array $pagevars
 * @return string
 * @throws \coding_exception
 */
function get_qbank_view(\question_edit_contexts $contexts, swiftquiz $swiftquiz, \moodle_url $url, array $pagevars) {
    $qperpage = optional_param('qperpage', 10, PARAM_INT);
    $qpage = optional_param('qpage', 0, PARAM_INT);
    // Capture question bank display in buffer to have the renderer render output.
    ob_start();
    $questionbank = new bank\swiftquiz_question_bank_view($contexts, $url, $swiftquiz->course, $swiftquiz->cm);
    $questionbank->display('editq', $qpage, $qperpage, $pagevars['cat'], true, true, true);
    return ob_get_clean();
}

/**
 * Echos the list of questions using the renderer for swiftquiz.
 * @param \question_edit_contexts $contexts
 * @param swiftquiz $swiftquiz
 * @param \moodle_url $url
 * @param array $pagevars
 * @throws \coding_exception
 * @throws \moodle_exception
 */
function list_questions(\question_edit_contexts $contexts, swiftquiz $swiftquiz, \moodle_url $url, array $pagevars) {
    $qbankview = get_qbank_view($contexts, $swiftquiz, $url, $pagevars);
    $swiftquiz->renderer->list_questions($swiftquiz, $swiftquiz->questions, $qbankview, $url);
}

/**
 * @param swiftquiz $swiftquiz
 * @throws \coding_exception
 */
function swiftquiz_edit_order(swiftquiz $swiftquiz) {
    $order = required_param('order', PARAM_RAW);
    $order = json_decode($order);
    $swiftquiz->set_question_order($order);
}

/**
 * @param swiftquiz $swiftquiz
 * @param \moodle_url $url
 * @throws \coding_exception
 * @throws \moodle_exception
 */
function swiftquiz_edit_add_question(swiftquiz $swiftquiz, \moodle_url $url) {
    $questionids = required_param('questionids', PARAM_TEXT);
    $questionids = explode(',', $questionids);
    foreach ($questionids as $questionid) {
        $swiftquiz->add_question($questionid);
    }
    // Ensure there is no action or questionid in the base url.
    $url->remove_params('action', 'questionids');
    redirect($url, null, 0);
}

/**
 * @param swiftquiz $swiftquiz
 * @throws \coding_exception
 */
function swiftquiz_edit_edit_question(swiftquiz $swiftquiz) {
    $questionid = required_param('questionid', PARAM_INT);
    $swiftquiz->edit_question($questionid);
}

/**
 * @param swiftquiz $swiftquiz
 * @param \question_edit_contexts $contexts
 * @param \moodle_url $url
 * @param $pagevars
 * @throws \coding_exception
 * @throws \moodle_exception
 */
function swiftquiz_edit_qlist(swiftquiz $swiftquiz, \question_edit_contexts $contexts, \moodle_url $url, array $pagevars) {
    $swiftquiz->renderer->header($swiftquiz, 'edit');
    list_questions($contexts, $swiftquiz, $url, $pagevars);
    $swiftquiz->renderer->footer();
}

/**
 * View edit page.
 */
function swiftquiz_edit() {
    global $PAGE, $COURSE;
    $action = optional_param('action', 'listquestions', PARAM_ALPHA);

    // Inconsistency in question_edit_setup.
    if (isset($_GET['id'])) {
        $_GET['cmid'] = $_GET['id'];
    }
    if (isset($_POST['id'])) {
        $_POST['cmid'] = $_POST['id'];
    }

    list(
        $url,
        $contexts,
        $cmid,
        $cm,
        $module, // swiftquiz database record.
        $pagevars) = question_edit_setup('editq', '/mod/swiftquiz/edit.php', true);

    $swiftquiz = new swiftquiz($cmid);
    $renderer = $swiftquiz->renderer;

    $modulename = get_string('modulename', 'swiftquiz');
    $quizname = format_string($swiftquiz->data->name, true);

    $PAGE->set_url($url);
    $PAGE->set_title(strip_tags($swiftquiz->course->shortname . ': ' . $modulename . ': ' . $quizname));
    $PAGE->set_heading($swiftquiz->course->fullname);

    if (swiftquiz_session_open($swiftquiz->data->id)) {
        // Can't edit during a session.
        $renderer->header($swiftquiz, 'edit');
        $renderer->session_is_open_error();
        $renderer->footer();
        return;
    }

    // Process moving, deleting and unhiding questions...
    $questionbank = new \core_question\bank\view($contexts, $url, $COURSE, $cm);
    $questionbank->process_actions();

    switch ($action) {
        case 'order':
            swiftquiz_edit_order($swiftquiz);
            break;
        case 'addquestion':
            swiftquiz_edit_add_question($swiftquiz, $url);
            break;
        case 'editquestion':
            swiftquiz_edit_edit_question($swiftquiz);
            break;
        case 'listquestions':
            swiftquiz_edit_qlist($swiftquiz, $contexts, $url, $pagevars);
            break;
        default:
            break;
    }
}

swiftquiz_edit();
