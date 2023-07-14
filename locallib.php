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
    swiftquiz_view_tab($swiftquiz, $id, $row, 'mod/swiftquiz:editquestions', 'edit');
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
