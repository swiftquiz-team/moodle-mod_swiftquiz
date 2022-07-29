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
use Fukuball\Jieba\Jieba;
use Fukuball\Jieba\Finalseg;
use Fukuball\Jieba\JiebaAnalyse;
/**
 * Library of functions and constants for module swiftquiz
 *
 * @package   mod_swiftquiz
 * @author    John Hoopes <moodle@madisoncreativeweb.com>
 * @copyright 2014 University of Wisconsin - Madison
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will create a new instance and return the id number
 * of the new instance.
 *
 * @param \stdClass $swiftquiz An object from the form in mod.html
 * @return int The id of the newly inserted swiftquiz record
 * @throws dml_exception
 */
function swiftquiz_add_instance(\stdClass $swiftquiz) {
    global $DB;
    $swiftquiz->timemodified = time();
    $swiftquiz->timecreated = time();
    $swiftquiz->id = $DB->insert_record('swiftquiz', $swiftquiz);
    return $swiftquiz->id;
}

/**
 * Given an object containing all the necessary data,
 * (defined by the form in mod.html) this function
 * will update an existing instance with new data.
 *
 * @param \stdClass $swiftquiz An object from the form in mod.html
 * @return boolean Success/Fail
 * @throws dml_exception
 */
function swiftquiz_update_instance(\stdClass $swiftquiz) {
    global $DB;
    $swiftquiz->timemodified = time();
    $swiftquiz->id = $swiftquiz->instance;
    $DB->update_record('swiftquiz', $swiftquiz);
    return true;
}

/**
 * Given an ID of an instance of this module,
 * this function will permanently delete the instance and any data that depends on it.
 *
 * @param int $id Id of the module instance
 * @return boolean Success/Failure
 **/
function swiftquiz_delete_instance($id) {
    global $DB, $CFG;

    require_once($CFG->dirroot . '/mod/swiftquiz/locallib.php');
    require_once($CFG->libdir . '/questionlib.php');
    require_once($CFG->dirroot . '/question/editlib.php');

    try {
        $swiftquiz = $DB->get_record('swiftquiz', ['id' => $id], '*', MUST_EXIST);
        // Go through each session and then delete them (also deletes all attempts for them).
        $sessions = $DB->get_records('swiftquiz_sessions', ['swiftquizid' => $swiftquiz->id]);
        foreach ($sessions as $session) {
            \mod_swiftquiz\swiftquiz_session::delete($session->id);
        }
        // Delete all questions for this quiz.
        $DB->delete_records('swiftquiz_questions', ['swiftquizid' => $swiftquiz->id]);
        // Finally delete the swiftquiz object.
        $DB->delete_records('swiftquiz', ['id' => $swiftquiz->id]);
    } catch (Exception $e) {
        return false;
    }
    return true;
}

/**
 * Function to be run periodically according to the moodle cron
 * This function searches for things that need to be done, such
 * as sending out mail, toggling flags etc ...
 *
 * @uses $CFG
 * @return boolean
 **/
function swiftquiz_cron() {
    return true;
}

/**
 * This function extends the settings navigation block for the site.
 *
 * @param settings_navigation $settings
 * @param navigation_node $swiftquiznode
 * @return void
 */
function swiftquiz_extend_settings_navigation($settings, $swiftquiznode) {
    global $PAGE, $CFG;

    // Require {@link questionlib.php}
    // Included here as we only ever want to include this file if we really need to.
    require_once($CFG->libdir . '/questionlib.php');

    question_extend_settings_navigation($swiftquiznode, $PAGE->cm->context)->trim_if_empty();
}

/**
 * @param \stdClass|int $course
 * @param \stdClass $cm
 * @param \context $context
 * @param string $filearea
 * @param array $args
 * @param mixed $forcedownload
 * @param array $options
 * @return bool
 * @throws coding_exception
 * @throws dml_exception
 */
function swiftquiz_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload, $options = []) {
    global $DB;
    if ($context->contextlevel != CONTEXT_MODULE) {
        return false;
    }
    if ($filearea != 'question') {
        return false;
    }
    require_course_login($course, true, $cm);

    $questionid = (int)array_shift($args);
    $quiz = $DB->get_record('swiftquiz', ['id' => $cm->instance]);
    if (!$quiz) {
        return false;
    }
    $question = $DB->get_record('swiftquiz_question', [
        'id' => $questionid,
        'quizid' => $cm->instance
    ]);
    if (!$question) {
        return false;
    }
    $fs = get_file_storage();
    $relative = implode('/', $args);
    $fullpath = "/$context->id/mod_swiftquiz/$filearea/$questionid/$relative";
    if (!$file = $fs->get_file_by_hash(sha1($fullpath)) or $file->is_directory()) {
        return false;
    }
    send_stored_file($file);
    return false;
}

/**
 * Called via pluginfile.php -> question_pluginfile to serve files belonging to
 * a question in a question_attempt when that attempt is a quiz attempt.
 *
 * @package mod_swiftquiz
 * @category files
 * @param stdClass $course course settings object
 * @param stdClass $context context object
 * @param string $component the name of the component we are serving files for.
 * @param string $filearea the name of the file area.
 * @param int $qubaid the attempt usage id.
 * @param int $slot the id of a question in this quiz attempt.
 * @param array $args the remaining bits of the file path.
 * @param bool $forcedownload whether the user must be forced to download the file.
 * @param array $options additional options affecting the file serving
 */
function mod_swiftquiz_question_pluginfile($course, $context, $component, $filearea, $qubaid, $slot,
                                          $args, $forcedownload, $options = []) {
    $fs = get_file_storage();
    $relative = implode('/', $args);
    $full = "/$context->id/$component/$filearea/$relative";
    if (!$file = $fs->get_file_by_hash(sha1($full)) or $file->is_directory()) {
        send_file_not_found();
    }
    send_stored_file($file, 0, 0, $forcedownload, $options);
}

/**
 * Check whether swiftquiz supports a certain feature or not.
 * @param string $feature
 * @return bool
 */
function swiftquiz_supports(string $feature) : bool {
    switch ($feature) {
        case FEATURE_MOD_INTRO:
        case FEATURE_BACKUP_MOODLE2:
        case FEATURE_SHOW_DESCRIPTION:
        case FEATURE_USES_QUESTIONS:
            return true;
        default:
            return false;
    }
}

//Clear spaces before and after array elements
function swiftquiz_trim_array($arr){
    if (!is_array($arr)){
        return trim($arr);
    }
    return array_map('swiftquiz_trim_array',$arr);
}

/**
 * @param responses
 */
function swiftquiz_get_wordclound($responses,$top_k=20) {
    global $CFG;
    require_once($CFG->dirroot.'/mod/swiftquiz/jieba/vendor/autoload.php');
    ini_set('memory_limit', '1024M');
    Jieba::init(array('mode'=>'defalut','dict'=>'normal'));
    Finalseg::init();
    JiebaAnalyse::init();
    $str="";
    foreach ($responses as $val){
        $str.=$val["response"]." ";
    }
    $tags = JiebaAnalyse::extractTags($str, $top_k);
    arsort($tags);
    $max=reset($tags);
    $ratio=$max/100;
    $wordFreqData=[];
    foreach ($tags as $tag=>$value){
        $newvalue=$value/$ratio;
        $wordFreqData[]=[$tag,$newvalue];
    }
    return $wordFreqData;
}

/**
 * get the question choice array
 * @param array $arr
 * @return array
 */
function swiftquiz_get_choice(array $arr): array{
    $arr[0] = substr($arr[0], strrpos($arr[0], "\n:")+3);
    $arr[0] = trim($arr[0]);
    return $arr;
}

/**
 * get the options array of ddwtos question
 * @param string $str
 * @return array
 */
function swiftquiz_get_ddwtos_options_by_question_summary(string $str): array{
    $str = substr($str, strrpos($str, "\n;")+3);
    $left = strpos($str, '{') + 1;
    $right = strrpos($str, '}');
    $str = substr($str, $left, $right - $left);
    $arr = explode('/', $str);
    $arr = swiftquiz_trim_array($arr);
    return $arr;
}

///**
// * @param object $question
// * @return array
// */
//function swiftquiz_get_ddwtos_options_by_question_object(object $question): array{
//    $options = [];
//    $n = count($question->choices[1]);
//    for($i = 1; $i <= $n; $i++){
//        $options[] = $question->choices[1][$i]['text'];
//    }
//    return $options;
//}

/**
 * get the number of the blanks
 * @param string $str
 * @return int
 */
function swiftquiz_get_blanks_num_by_question_summary(string $str){
    $str = substr($str, 0, strrpos($str, "\n;"));
    $sum = 0;
    $idx = 0;
    while(is_numeric($idx = strpos($str, "[[", $idx))){
        $sum++;
        $idx++;
    }
    return $sum;
}

/**
 * get response array
 * @param string $str
 * @return array
 */
function swiftquiz_ddwtos_resopnse_explode(string $str): array{
    $left = 0;
    $right = 0;
    $arr = array();
    while(is_numeric($left = strpos($str, "{", $right))){
        $right = strpos($str, "}", $left);
        $arr[] = substr($str, $left + 1, $right - $left - 1);
    }
    $arr = swiftquiz_trim_array($arr);
    return $arr;
}