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
 * Local lib
 *
 * @package   mod_swiftquiz
 * @author    John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright 2014 University of Wisconsin - Madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use mod_swiftquiz\swiftquiz;

defined('MOODLE_INTERNAL') || die();

/**
 * @param swiftquiz $swiftquiz
 * @param int $id
 * @param array $row
 * @param string $capability
 * @param string $name
 * @throws coding_exception
 * @throws moodle_exception
 */
function swiftquiz_view_tab(swiftquiz $swiftquiz, int $id, array &$row, string $capability, string $name) {
    if (has_capability($capability, $swiftquiz->context)) {
        $url = new moodle_url("/mod/swiftquiz/$name.php", ['id' => $id]);
        $row[] = new tabobject($name, $url, get_string($name, 'swiftquiz'));
    }
}

function swiftquiz_view_tab_edit(swiftquiz $swiftquiz, int $id, array &$row, string $capability, string $name) {
    if (has_capability($capability, $swiftquiz->context)) {
        $url = new moodle_url("/mod/swiftquiz/$name.php", ['cmid' => $id]);
        $row[] = new tabobject($name, $url, get_string($name, 'swiftquiz'));
    }
}

/**
 * Prints tabs for instructor.
 * @param swiftquiz $swiftquiz
 * @param string $tab
 * @return string HTML string of the tabs
 * @throws coding_exception
 * @throws moodle_exception
 */
function swiftquiz_view_tabs(swiftquiz $swiftquiz, string $tab) : string {
    $tabs = [];
    $row = [];
    $inactive = [];
    $activated = [];
    $id = $swiftquiz->cm->id;
    swiftquiz_view_tab($swiftquiz, $id, $row, 'mod/swiftquiz:attempt', 'view');
    swiftquiz_view_tab_edit($swiftquiz, $id, $row, 'mod/swiftquiz:editquestions', 'edit');
    swiftquiz_view_tab($swiftquiz, $id, $row, 'mod/swiftquiz:seeresponses', 'reports');
    if ($tab === 'view' && count($row) === 1) {
        // No tabs for students.
        return '<br>';
    }
    $tabs[] = $row;
    if ($tab === 'reports' || $tab === 'edit' || $tab === 'view') {
        $activated[] = $tab;
    }
    return print_tabs($tabs, $tab, $inactive, $activated, true);
}
namespace mod_swiftquiz;

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
function swiftquiz_get_qbank_view(\question_edit_contexts $contexts, swiftquiz $swiftquiz, \moodle_url $url, array $pagevars) {
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
function swiftquiz_list_questions(\question_edit_contexts $contexts, swiftquiz $swiftquiz, \moodle_url $url, array $pagevars) {
    $qbankview = swiftquiz_get_qbank_view($contexts, $swiftquiz, $url, $pagevars);
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
    swiftquiz_list_questions($contexts, $swiftquiz, $url, $pagevars);
    $swiftquiz->renderer->footer();
}


