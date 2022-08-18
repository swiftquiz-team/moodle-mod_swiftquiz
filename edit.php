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


    global $PAGE, $COURSE;
    $action = optional_param('action', 'listquestions', PARAM_ALPHA);
   
    $id = optional_param('id', false, PARAM_INT);
    // Inconsistency in question_edit_setup.
    // if (isset($_GET['id'])) {
    //     $_GET['cmid'] = $_GET['id'];
    // }
    // if (isset($_POST['id'])) {
    //     $_POST['cmid'] = $_POST['id'];
    // }
    if ($id) {
        $_GET['cmid'] = $id;
    }
    if ($id) {
        $_POST['cmid'] = $id;
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
