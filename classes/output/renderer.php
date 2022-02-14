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

namespace mod_swiftquiz\output;

use mod_swiftquiz\forms\view\student_start_form;
use mod_swiftquiz\swiftquiz;
use mod_swiftquiz\swiftquiz_attempt;
use mod_swiftquiz\swiftquiz_session;

defined('MOODLE_INTERNAL') || die;

require_once($CFG->libdir . '/questionlib.php');

/**
 * To load a question without refreshing the page, we need the JavaScript for the question.
 * Moodle stores this in page_requirements_manager, but there is no way to read the JS that is required.
 * This class takes in the manager and keeps the JS for when we want to get a diff.
 * NOTE: This class is placed here because it will only ever be used by renderer::render_question_form()
 * TODO: Look into removing this class in the future.
 * @package   mod_swiftquiz\output
 * @author    Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright 2018 NTNU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class page_requirements_diff extends \page_requirements_manager {

    /** @var array $beforeinitjs */
    private $beforeinitjs;

    /** @var array $beforeamdjs */
    private $beforeamdjs;

    /** @var array $beforecss */
    private $beforecss;

    /**
     * Constructor.
     * @param \page_requirements_manager $manager
     */
    public function __construct(\page_requirements_manager $manager) {
        $this->beforeinitjs = $manager->jsinitcode;
        $this->beforeamdjs = $manager->amdjscode;
        $this->beforecss = $manager->cssurls;
    }

    /**
     * Run an array_diff on the required JavaScript when this
     * was constructed and the one passed to this function.
     * @param \page_requirements_manager $manager
     * @return array the JavaScript that was added in-between constructor and this call.
     */
    public function get_js_diff(\page_requirements_manager $manager) : array {
        $jsinitcode = array_diff($manager->jsinitcode, $this->beforeinitjs);
        $amdjscode = array_diff($manager->amdjscode, $this->beforeamdjs);
        return array_merge($jsinitcode, $amdjscode);
    }

    /**
     * Run an array_diff on the required CSS when this
     * was constructed and the one passed to this function.
     * @param \page_requirements_manager $manager
     * @return array the CSS that was added in-between constructor and this call.
     */
    public function get_css_diff(\page_requirements_manager $manager) : array {
        return array_keys(array_diff($manager->cssurls, $this->beforecss));
    }

}

/**
 * Quiz renderer
 *
 * @package   mod_swiftquiz
 * @author    Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright 2014 University of Wisconsin - Madison
 * @copyright 2019 NTNU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class renderer extends \plugin_renderer_base {

    /**
     * Render the header for the page.
     * @param swiftquiz $swiftquiz
     * @param string $tab The active tab on the page
     */
    public function header(swiftquiz $swiftquiz, string $tab) {
        echo $this->output->header();
        echo swiftquiz_view_tabs($swiftquiz, $tab);
    }

    /**
     * Render the footer for the page.
     */
    public function footer() {
        echo $this->output->footer();
    }

    /**
     * For instructors.
     * @param \moodleform $sessionform
     */
    public function start_session_form(\moodleform $sessionform) {
        echo $this->render_from_template('swiftquiz/start_session', ['form' => $sessionform->render()]);
    }

    /**
     * For instructors.
     * @param swiftquiz $swiftquiz
     */
    public function continue_session_form(swiftquiz $swiftquiz) {
        global $PAGE;
        $cmid = $swiftquiz->cm->id;
        $id = $swiftquiz->data->id;
        echo $this->render_from_template('swiftquiz/continue_session', [
            'path' => $PAGE->url->get_path() . "?id=$cmid&quizid=$id&action=quizstart"
        ]);
    }

    /**
     * Show the "join quiz" form for students.
     * @param student_start_form $form
     * @param swiftquiz_session $session
     * @throws \moodle_exception
     */
    public function join_quiz_form(student_start_form $form, swiftquiz_session $session) {
        $anonstr = ['', 'anonymous_answers_info', 'fully_anonymous_info', 'nonanonymous_session_info'];
        echo $this->render_from_template('swiftquiz/join_session', [
            'name' => $session->data->name,
            'started' => ($session->attempt !== false),
            'anonymity_info' => get_string($anonstr[$session->data->anonymity], 'swiftquiz'),
            'form' => $form->render()
        ]);
    }

    /**
     * Show the "quiz not running" page for students.
     * @param int $cmid the course module id for the quiz
     * @throws \moodle_exception
     */
    public function quiz_not_running($cmid) {
        global $PAGE;
        echo $this->render_from_template('swiftquiz/no_session', [
            'reload' => $PAGE->url->get_path() . '?id=' . $cmid
        ]);
    }

    /**
     * Renders the quiz to the page
     * @param swiftquiz_session $session
     * @throws \moodle_exception
     */
    public function render_quiz(swiftquiz_session $session) {
        $this->require_quiz($session);
        $buttons = function($buttons) {
            $result = [];
            foreach ($buttons as $button) {
                $result[] = [
                    'icon' => $button[0],
                    'id' => $button[1],
                    'text' => get_string($button[1], 'swiftquiz')
                ];
            }
            return $result;
        };
        echo $this->render_from_template('swiftquiz/quiz', [
            'buttons' => $buttons([
                ['repeat', 'repoll'],
                ['bar-chart', 'vote'],
                ['edit', 'improvise'],
                ['forward', 'triggerBtn'],
                ['bars', 'jump'],
                ['forward', 'next'],
                ['close', 'end'],
                ['expand', 'fullscreen'],
                ['window-close', 'quit'],
                ['square-o', 'show_word_cloud'],
                ['square-o', 'show_statistic'],
                ['square-o', 'responses'],
                ['square-o', 'toggle_test'],
                ['square-o', 'answer']
            ]),
            'instructor' => $session->swiftquiz->is_instructor()
        ]);
    }

    /**
     * Render the question specified by slot
     * @param swiftquiz $swiftquiz
     * @param \question_usage_by_activity $quba
     * @param int $slot
     * @param bool $review Whether or not we're reviewing the attempt
     * @param string|\stdClass $reviewoptions Can be string for overall actions like "edit" or an object of review options
     * @return string the HTML fragment for the question
     */
    public function render_question(swiftquiz $swiftquiz, $quba, $slot, bool $review, $reviewoptions) : string {
        $displayoptions = $swiftquiz->get_display_options($review, $reviewoptions);
        $quba->render_question_head_html($slot);
        return $quba->render_question($slot, $displayoptions, $slot);
    }

    /**
     * Render a specific question in its own form so it can be submitted
     * independently of the rest of the questions
     * @param int $slot the id of the question we're rendering
     * @param swiftquiz_attempt $attempt
     * @param swiftquiz $swiftquiz
     * @param bool $instructor
     * @return string[] html, javascript, css
     * @throws \moodle_exception
     */
    public function render_question_form($slot, swiftquiz_attempt $attempt, swiftquiz $swiftquiz, bool $instructor) : array {
        global $PAGE;
        $differ = new page_requirements_diff($PAGE->requires);
        ob_start();
        $questionhtml = $this->render_question($swiftquiz, $attempt->quba, $slot, false, '');
        $questionhtmlechoed = ob_get_clean();
        $js = implode("\n", $differ->get_js_diff($PAGE->requires));
        $css = $differ->get_css_diff($PAGE->requires);
        $output = $this->render_from_template('swiftquiz/question', [
            'instructor' => $instructor,
            'question' => $questionhtml . $questionhtmlechoed,
            'slot' => $slot
        ]);
        return [$output, $js, $css];
    }

    /**
     * Renders and echos the home page for the responses section
     * @param \moodle_url $url
     * @param \stdClass[] $sessions
     * @param int $selectedid
     * @return array
     * @throws \coding_exception
     */
    public function get_select_session_context(\moodle_url $url, array $sessions, $selectedid) : array {
        $selecturl = clone($url);
        $selecturl->param('action', 'view');
        usort($sessions, function ($a, $b) {
            return strcmp(strtolower($a->name), strtolower($b->name));
        });
        return [
            'method' => 'get',
            'action' => $selecturl->out_omit_querystring(),
            'formid' => 'swiftquiz_select_session_form',
            'id' => 'swiftquiz_select_session',
            'name' => 'sessionid',
            'options' => array_map(function ($session) use ($selectedid) {
                return [
                    'name' => $session->name,
                    'value' => $session->id,
                    'selected' => intval($selectedid) === intval($session->id),
                    'optgroup' => false
                ];
            }, $sessions),
            'params' => array_map(function ($key, $value) {
                return [
                    'name' => $key,
                    'value' => $value
                ];
            }, array_keys($selecturl->params()), $selecturl->params()),
        ];
    }

    /**
     * Render the list questions view for the edit page
     * @param swiftquiz $swiftquiz
     * @param array $questions Array of questions
     * @param string $questionbankview HTML for the question bank view
     * @param \moodle_url $url
     * @throws \moodle_exception
     */
    public function list_questions(swiftquiz $swiftquiz, array $questions, string $questionbankview, \moodle_url $url) {
        $slot = 1;
        $list = [];
        foreach ($questions as $question) {
            $editurl = clone($url);
            $editurl->param('action', 'editquestion');
            $editurl->param('questionid', $question->data->id);
            $list[] = [
                'id' => $question->data->id,
                'name' => $question->question->name,
                'first' => $slot === 1,
                'last' => $slot === count($questions),
                'slot' => $slot,
                'editurl' => $editurl,
                'icon' => print_question_icon($question->question)
            ];
            $slot++;
        }
        echo $this->render_from_template('swiftquiz/edit_question_list', [
            'questions' => $list,
            'qbank' => $questionbankview
        ]);
        $this->require_edit($swiftquiz->cm->id);
    }

    public function session_is_open_error() {
        echo \html_writer::tag('h3', get_string('edit_page_open_session_error', 'swiftquiz'));
    }

    /**
     * @param swiftquiz_session $session
     * @param \moodle_url $url
     * @return mixed[]
     * @throws \coding_exception
     */
    public function view_session_report(swiftquiz_session $session, \moodle_url $url) : array {

        $attempt = reset($session->attempts);
        if (!$attempt) {
            $strnoattempts = get_string('no_attempts_found', 'swiftquiz');
            echo '<div class="swiftquiz-box"><p>' . $strnoattempts . '</p></div>';
            return [];
        }
        $slots = $shortanswerslots=[];
        foreach ($attempt->quba->get_slots() as $qubaslot) { //$attempt->quba->get_slots() List of test sequence numbers for this problem. $qubaslot Question serial number
            $qattempt = $attempt->quba->get_question_attempt($qubaslot); //$qattempt It is the participant of the question, including the situation of students' participation in answering the question and the object of the question
            $question = $qattempt->get_question();//Problem object
            $results = $session->get_question_results_list($qubaslot);//Get the answer list of the question object
            list($results['responses'], $mergecount) = $session->get_merged_responses($qubaslot, $results['responses']);
            //Mark whether the answer is correct
            $rightanswers=[];
            foreach ($results['responses'] as $k=>$val){
                if( $question->qtype->name()=="shortanswer" || !method_exists($question,'get_right_answer_summary') || empty($question->get_right_answer_summary())){  //Judge whether the test question has the correct answer
                    $results['responses'][$k]["rightanswer"]=-1;
                }else{
                    if($question instanceof \qtype_multichoice_multi_question ){ //Multiple choice questions
                        $rightanswers=explode(';',$question->get_right_answer_summary());
                        $rightanswers=swiftquiz_trim_array($rightanswers);//Clear the space before and after the answer
                        $results['responses'][$k]["rightanswer"]=(in_array(trim($val["response"]),$rightanswers))?true:false;
                    }else{
                        $results['responses'][$k]["rightanswer"]=(trim($question->get_right_answer_summary())==trim($val["response"]))?true:false;
                    }
                }
            }
            //Count the number of correct answers
            $rightcount=0; //the number of correct answers
            if($question instanceof \qtype_multichoice_multi_question ){ //Multiple choice questions
                foreach ($session->attempts as $attempt) { //$attempt Individual user participation
                    if ($attempt->data->responded != 1) {// Judge whether someone answers this question (if not 1, no one answers)
                        continue;
                    }
                    $responses=$attempt->get_response_data($qubaslot);
                    $responses=swiftquiz_trim_array($responses);
                    //To judge whether the user's answer is completely correct, the number of answers selected by the user is the same as the number of correct answers, and the content is completely consistent
                    if(!empty($rightanswers) && count($responses)==count($rightanswers) && empty(array_diff($rightanswers,$responses))){  //The answer is absolutely correct
                        $rightcount++;
                    }
                }
            }else{//Single choice questions
                $responses=array_column($results['responses'],'response');  //Get all answers to this question
                if(method_exists($question,'get_right_answer_summary')){ //Determine whether there is a way to get the correct answer to the question
                    foreach ($responses as $response){
                        if($response==trim($question->get_right_answer_summary())){ // Traverse each answer and compare it with the correct answer. If it is the same, add 1 to the correct number
                            $rightcount++;
                        }
                    }
                }
            }
            //end
            $slots[] = [
                'num' => $qubaslot,
                'name' => str_replace('{IMPROV}', '', $question->name),
                'type' => $attempt->quba->get_question_attempt($qubaslot)->get_question()->get_type_name(),
                'description' => $question->questiontext,
                'responses' => $results['responses'],
                'rightcount'=>$rightcount
            ];
            //Gets the shortanswerslots array
            if($question->qtype->name()=="shortanswer"){
                $tags=swiftquiz_get_wordclound($results['responses'],20);
                //var_dump($tags);exit();
                $shortanswerslots[]=[
                    'num' => $qubaslot,
                    'name' => str_replace('{IMPROV}', '', $question->name),
                    'tags' => json_decode(json_encode($tags)),
                ];
            }

        }
        // TODO: Slots should not be passed as parameter to AMD module.
        // It quickly gets over 1KB, which shows debug warning.
        $this->require_review($session, $slots);

        $attendances = $session->get_attendances();
        $swiftquiz = $session->swiftquiz;
        $sessions = $swiftquiz->get_sessions();
        return [
            'select_session' => $swiftquiz->renderer->get_select_session_context($url, $sessions, $session->data->id),
            'session' => [
                'slots' => $slots,
                'students' => $attendances,
                'count_total' => count($session->attempts) - 1, // TODO: For loop and check if preview instead?
                'count_answered' => count($attendances),
                'cmid' => $swiftquiz->cm->id,
                'quizid' => $swiftquiz->data->id,
                'id' => $session->data->id
            ],
            "shortanswerslots"=>json_encode($shortanswerslots)// shortanswer data
        ];
    }

    /**
     * @param swiftquiz_session $session
     */
    public function require_core(swiftquiz_session $session) {
        $this->page->requires->js_call_amd('mod_swiftquiz/core', 'initialize', [
            $session->swiftquiz->cm->id,
            $session->swiftquiz->data->id,
            $session->data->id,
            $session->attempt ? $session->attempt->data->id : 0,
            sesskey()
        ]);
    }

    /**
     * @param swiftquiz_session $session
     */
    public function require_quiz(swiftquiz_session $session) {
        $this->require_core($session);
        $this->page->requires->js('/question/qengine.js');
        if ($session->swiftquiz->is_instructor()) {
            $count = count($session->swiftquiz->questions);
            $params = [$count, false, []];
            $this->page->requires->js_call_amd('mod_swiftquiz/instructor', 'initialize', $params);
        } else {
            $this->page->requires->js_call_amd('mod_swiftquiz/student', 'initialize');
        }
    }

    /**
     * @param int $cmid
     */
    public function require_edit($cmid) {
        $this->page->requires->js('/mod/swiftquiz/js/sortable.min.js');
        $this->page->requires->js_call_amd('mod_swiftquiz/edit', 'initialize', [$cmid]);
    }

    /**
     * @param swiftquiz_session $session
     * @param array $slots
     */
    public function require_review(swiftquiz_session $session, array $slots) {
        $this->require_core($session);
        $count = count($session->swiftquiz->questions);
        $params = [$count, true, $slots];
        $this->page->requires->js('/mod/swiftquiz/js/wordcloud2.js');
        $this->page->requires->js_call_amd('mod_swiftquiz/instructor', 'initialize', $params);


    }

}
