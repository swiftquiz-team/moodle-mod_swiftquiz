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
 * Ajax callback script for dealing with quiz data
 * This callback handles saving questions as well as instructor actions
 *
 * @package   mod_swiftquiz
 * @author    Sebastian S. Gundersen <sebastsg@stud.ntnu.no>
 * @copyright 2014 University of Wisconsin - Madison
 * @copyright 2018 NTNU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace mod_swiftquiz;

use core_analytics\action;
use core_customfield\data;
use tool_brickfield\local\areas\mod_choice\option;

define('AJAX_SCRIPT', true);

require_once('../../config.php');
require_once($CFG->dirroot . '/mod/swiftquiz/lib.php');
require_once($CFG->libdir . '/filelib.php');
require_once($CFG->dirroot . '/question/engine/lib.php');


require_login();
require_sesskey();

/**
 * Send a list of all the questions tagged for use with improvisation.
 * @param swiftquiz $swiftquiz
 * @return mixed[]
 */
function show_all_improvise_questions(swiftquiz $swiftquiz) {

    $improviser = new improviser($swiftquiz);
    $questionrecords = $improviser->get_all_improvised_question_definitions();

    if (!$questionrecords) {
        return [
            'status' => 'error',
            'message' => 'No improvisation questions'
        ];
    }
    $questions = [];
    foreach ($questionrecords as $question) {
        $questions[] = [
            'questionid' => $question->id,
            'swiftquizquestionid' => 0,
            'name' => str_replace('{IMPROV}', '', $question->name),
            'time' => $swiftquiz->data->defaultquestiontime
        ];
    }
    return [
        'status' => 'success',
        'questions' => $questions
    ];
}

/**
 * Send a list of all the questions added to the quiz.
 * @param swiftquiz $swiftquiz
 * @return mixed[]
 */
function show_all_jump_questions(swiftquiz $swiftquiz) {
    global $DB;
    $sql = 'SELECT q.id AS id, q.name AS name, jq.questiontime AS time, jq.id AS jqid';
    $sql .= '  FROM {swiftquiz_questions} jq';
    $sql .= '  JOIN {question} q ON q.id = jq.questionid';
    $sql .= ' WHERE jq.swiftquizid = ?';
    $sql .= ' ORDER BY jq.slot ASC';
    $questionrecords = $DB->get_records_sql($sql, [$swiftquiz->data->id]);
    $questions = [];
    foreach ($questionrecords as $question) {
        $questions[] = [
            'questionid' => $question->id,
            'swiftquizquestionid' => $question->jqid,
            'name' => $question->name,
            'time' => $question->time
        ];
    }
    return [
        'status' => 'success',
        'questions' => $questions
    ];
}

/**
 * Get the form for the current question.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function get_question_form(swiftquiz_session $session) {
    $session->load_session_questions();
    $slot = optional_param('slot', 0, PARAM_INT);
    if ($slot === 0) {
        $slot = count($session->questions);
    }
    $html = '';
    $js = '';
    $css = [];
    $isalreadysubmitted = true;
    if (!$session->attempt->has_responded($slot)) {
        $swiftquiz = $session->swiftquiz;
        /** @var output\renderer $renderer */
        $renderer = $swiftquiz->renderer;
        $isinstructor = $swiftquiz->is_instructor();
        list($html, $js, $css) = $renderer->render_question_form($slot, $session->attempt, $swiftquiz, $isinstructor);
        $isalreadysubmitted = false;
    }
    $qtype = $session->get_question_type_by_slot($slot);
    $voteable = ['multichoice', 'truefalse'];
    return [
        'html' => $html,
        'js' => $js,
        'css' => $css,
        'question_type' => $qtype,
        'is_already_submitted' => $isalreadysubmitted,
        'voteable' => !in_array($qtype, $voteable)
    ];
}

/**
 * Start a new question.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function start_question(swiftquiz_session $session) {
    $session->load_session_questions();
    $session->load_attempts();
    $method = required_param('method', PARAM_ALPHA);
    // Variable $questionid is a Moodle question id.
    switch ($method) {
        case 'improvise':
            $questionid = required_param('questionid', PARAM_INT);
            $questiontime = optional_param('questiontime', 0, PARAM_INT);
            $swiftquizquestionid = optional_param('swiftquizquestionid', 0, PARAM_INT);
            if ($swiftquizquestionid !== 0) {
                $swiftquizquestion = $session->swiftquiz->get_question_by_id($swiftquizquestionid);
                $session->data->slot = $swiftquizquestion->data->slot;
            }
            //插入内容
//            global $DB;
//            $improv = new improviser($session->swiftquiz);
//            $question = $improv->make_generic_question_definition('multichoice', '3 test');
//            $DB->insert_record('question', $question);

//            $improv = new improviser($session->swiftquiz);
//            $improv->build_question('test 67', '1 + 3 = ?',['qdwqd', '123', 'abcd']);

            break;
        case 'jump':
            $questionid = required_param('questionid', PARAM_INT);
            $questiontime = optional_param('questiontime', 0, PARAM_INT);
            $swiftquizquestionid = optional_param('swiftquizquestionid', 0, PARAM_INT);
            if ($swiftquizquestionid !== 0) {
                $swiftquizquestion = $session->swiftquiz->get_question_by_id($swiftquizquestionid);
                $session->data->slot = $swiftquizquestion->data->slot;
            }
            break;
        case 'repoll':
            $lastslot = count($session->questions);
            if ($lastslot === 0) {
                return [
                    'status' => 'error',
                    'message' => 'Nothing to repoll.'
                ];
            }
            $questionid = $session->questions[$lastslot]->questionid;
            $questiontime = $session->data->currentquestiontime;
            break;
        case 'next':
            $lastslot = count($session->swiftquiz->questions);
            if ($session->data->slot >= $lastslot) {
                return [
                    'status' => 'error',
                    'message' => 'No next question.'
                ];
            }
            $session->data->slot++;
            $swiftquizquestion = $session->swiftquiz->questions[$session->data->slot];
            $questionid = $swiftquizquestion->question->id;
            $questiontime = $swiftquizquestion->data->questiontime;
            break;
        default:
            return [
                'status' => 'error',
                'message' => "Invalid method $method"
            ];
    }
    if($method == 'improvise') {
        list($success, $questiontime) = $session->start_improvise_question($questionid, $questiontime);
    } else {
        list($success, $questiontime) = $session->start_question($questionid, $questiontime);
    }
    if (!$success) {
        return [
            'status' => 'error',
            'message' => "Failed to start question $questionid for session"
        ];
    }

    $session->data->status = 'running';
    $session->save();

    return [
        'status' => 'success',
        'questiontime' => $questiontime,
        'delay' => $session->data->nextstarttime - time()
    ];
}

/**
 * Start the quiz.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function start_quiz(swiftquiz_session $session) {
    if ($session->data->status !== 'notrunning') {
        return [
            'status' => 'error',
            'message' => 'Quiz is already running'
        ];
    }
    $session->data->status = 'preparing';
    $session->save();
    return ['status' => 'success'];
}

/**
 * Submit a response for the current question.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function save_question(swiftquiz_session $session) {
    $attempt = $session->attempt;
    if (!$attempt->belongs_to_current_user()) {
        return [
            'status' => 'error',
            'message' => 'Invalid user'
        ];
    }
    $session->load_session_questions();
    $attempt->save_question(count($session->questions));
    $session->update_attendance_for_current_user();
    // Only give feedback if specified in session.
    $feedback = '';
    if ($session->data->showfeedback) {
        $feedback = $attempt->get_question_feedback($session->swiftquiz);
    }
    return [
        'status' => 'success',
        'feedback' => $feedback
    ];
}

/**
 * Start a vote.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function run_voting(swiftquiz_session $session) {
    $questions = required_param('questions', PARAM_RAW);
    $questions = json_decode(urldecode($questions), true);
    if (!$questions) {
        return [
            'status' => 'error',
            'message' => 'Failed to decode questions'
        ];
    }
    $qtype = optional_param('question_type', '', PARAM_ALPHANUM);

    $session->load_session_questions();
    $vote = new swiftquiz_vote($session->data->id);
    $slot = count($session->questions);
    $vote->prepare_options($session->swiftquiz->data->id, $qtype, $questions, $slot);
    $session->data->status = 'voting';
    $session->save();
    return ['status' => 'success'];
}

/**
 * Save a vote.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function save_vote(swiftquiz_session $session) {
    $voteid = required_param('vote', PARAM_INT);
    $vote = new swiftquiz_vote($session->data->id);
    $status = $vote->save_vote($voteid);
    return ['status' => ($status ? 'success' : 'error')];
}

/**
 * Get the vote results.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function get_vote_results(swiftquiz_session $session) {
    $session->load_session_questions();
    $session->load_attempts();
    $slot = count($session->questions);
    $vote = new swiftquiz_vote($session->data->id, $slot);
    $votes = $vote->get_results();
    return [
        'answers' => $votes,
        'total_students' => $session->get_student_count()
    ];
}

/**
 * End the current question.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function end_question($session) {
    $session->data->status = 'reviewing';
    $session->save();
    return ['status' => 'success'];
}

/**
 * Get the correct answer for the current question.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function get_right_response(swiftquiz_session $session) {
    $session->load_session_questions();
    return ['right_answer' => $session->get_question_right_response()];
}

/**
 * Close a session.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function close_session(swiftquiz_session $session) {
    global $DB;


    $session->load_attempts();
    $sql = "SELECT     q.id,
                        q.qtype
                      FROM {question} q
                     WHERE q.name LIKE :prefix
                  ";
    $questions = $DB->get_records_sql($sql, ['prefix' => '{IMPROV}%']);
    foreach ($questions as $question){
        $DB->delete_records('qtype_'.$question->qtype.'_options', ['id' => $question->id]);
        $DB->delete_records('question_answers', ['id' => $question->id]);
        $DB->delete_records('question', ['id' => $question->id]);
    }
    $session->end_session();
    return ['status' => 'success'];
}

/**
 * Get the results for a session.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function get_results(swiftquiz_session $session) {
    $session->load_session_questions();
    $session->load_attempts();
    $slot = count($session->questions);
    $qtype = $session->get_question_type_by_slot($slot);
    $results = $session->get_question_results_list($slot);
    list($results['responses'], $mergecount) = $session->get_merged_responses($slot, $results['responses']);

    $is_improvise = false;
    //Add correct answer mark
    if ($attempt = reset($session->attempts)) {
        $qattempt = $attempt->quba->get_question_attempt($slot);
        $question = $qattempt->get_question();//Problem object

//add fake responses
        if ($question->qtype->name() != "shortanswer") {
//            $options = explode(';', $question->get_question_summary());
            $prefix = '<p dir="ltr" style="text-align: left;">';
            $qs = $question->get_question_summary();
            $qt = $question->questiontext;
            if(substr($qt, 0, strlen($prefix)) == $prefix){
                $qt = substr($qt, strlen($prefix));
                $qt = substr($qt, 0, strlen($qt) - 4);
            }
            if(substr($qt, 0, 1) == '*'){
                $is_improvise = 'true';
            }
            $qs = substr($qs, strlen($qt)+2);
            $options = explode(';', $qs);
            $options = swiftquiz_trim_array($options);
            if (count($results['responses']) > 0) {
                if (!($question instanceof \qtype_ddwtos_question)) {
                    $fake_response = $results['responses'][0];
                    $results['responses'][0]['option_num'] = count($options);
                    foreach ($options as $option) {
                        $fake_response["response"] = $option;
                        $results['responses'][] = $fake_response;
                    }
                }
            }
        }


        $rightanswers=explode(';',$question->get_right_answer_summary());
        $rightanswers=swiftquiz_trim_array($rightanswers);//Clear the space before and after the answer
        foreach ($results['responses'] as $k=>$val){
            if( $question->qtype->name()=="shortanswer" || !method_exists($question,'get_right_answer_summary') || empty($question->get_right_answer_summary())){  //Judge whether the test question has the correct answer
                $results['responses'][$k]["rightanswer"]=-1;
            }else{
                if($question instanceof \qtype_multichoice_multi_question ){ //Multiple choice questions
                    $results['responses'][$k]["rightanswer"]=(in_array(trim($val["response"]),$rightanswers))?true:false;
                }else{
                    $results['responses'][$k]["rightanswer"]=(trim($question->get_right_answer_summary())==trim($val["response"]))?true:false;
                }
            }
        }

    }
    //Count the number of correct answers
    $rightcount=0; //Number of correct answers
    if($question instanceof \qtype_multichoice_multi_question ){ //Multiple choice questions
        foreach ($session->attempts as $attempt) {//$attempt Individual user participation
            if ($attempt->data->responded != 1) {// Judge whether someone answers this question (if not 1, no one answers)
                continue;
            }
            $responses=$attempt->get_response_data($slot);
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

    //adapt rightcount
    if($question instanceof \qtype_multichoice_multi_question ){
        if(count($rightanswers) == 1){
            $rightcount--;
        }
    } else if (!($question instanceof \qtype_ddwtos_question)){
        $rightcount--;
    }

    $show_abs = 'false';
    $show_percent = 'true';
    if($question instanceof \qtype_multichoice_multi_question){
        $show_abs = 'true';
        $show_percent='false';
    }
    if($question->qtype->name()=="shortanswer"){
        $show_abs = false;
    }

// ddwtos

    $buf_responses = [];
    $blank_num = 0;
    if($question instanceof \qtype_ddwtos_question && count($results['responses']) > 0) {

        // deal with responses
        // store responses in detail
        //create a 2-dimensional array
        $result_list = [];
        for($i = 0; $i < $blank_num; $i++){
            $arr = swiftquiz_get_ddwtos_options_by_question_summary($question->get_question_summary());
            $arr[] = '';
            foreach ($arr as $option){
                $col[trim($option)] = 0;
            }
            $col[''] = 0;
            $result_list[$i] = $col;
        }

        //store the data in the array[][]
        $responses=array_column($results['responses'],'response');
        if(method_exists($question,'get_right_answer_summary')){ //Determine whether there is a way to get the correct answer to the question
            foreach ($responses as $response){
                $i = 0;
                foreach (swiftquiz_ddwtos_resopnse_explode($response) as $option) {
                    $result_list[$i++][trim($option)]++;
                }
            }
        }

        // num of blanks
        $blank_num = swiftquiz_get_blanks_num_by_question_summary($question->get_question_summary());
        // correct answer array
        $correct_answer = swiftquiz_ddwtos_resopnse_explode($question->get_right_answer_summary());
        // create format response
        $fake_response = $results['responses'][0];
        // fill in the buf_responses with result_list
        for($i = 0; $i < $blank_num; $i++) {
            $buf_column = $fake_response;
            $buf_column['response'] = "BOX".($i+1);
            $buf_column['rightanswer'] = -1;
            $buf_responses[] = $buf_column;
            $arr = swiftquiz_get_ddwtos_options_by_question_summary($question->get_question_summary());
            $arr[] = '';
            foreach ($arr as $option) {
                $buf_column['response'] = ($i + 1) . ': ' . (($option === '')?'None Selector':$option);
                $buf_column['rightanswer'] = ($option == trim($correct_answer[$i]));
                $buf_column['count'] = (isset($result_list[$i][trim($option)]))?$result_list[$i][trim($option)]:0;
                $buf_responses[] = $buf_column;
            }
        }
        $buf_responses[0]['option_num'] = $blank_num;
        // replace the response with buf_response
        $results['responses'] = $buf_responses;
    }
    //shortanswer  word cloud
    $shortanswertags=[];
    if($question->qtype->name()=="shortanswer"){
        $tags=swiftquiz_get_wordclound($results['responses'],20);
        $shortanswertags=json_decode(json_encode($tags));
    }
    //end

    if($is_improvise == 'true'){
        foreach ($results['responses'] as $k => $val) {
            $results['responses'][$k]["rightanswer"] = -1;
        }
    }

    // test
    $shortanswertags1=[];
    if ($question->qtype->name()=="shortanswer") {
        // 如果大小不敏感，就遍历它的每一个值，然后把他转化为小写，加一个属性
        $results['responses1'] = $results['responses'];
        foreach ($results['responses1'] as $k=>$val) {
            $results['responses1'][$k]["response"]=strtolower($val["response"]);
        }
        $tags=swiftquiz_get_wordclound($results['responses1'],20);
        $shortanswertags1=json_decode(json_encode($tags));
    }

    // Check if this has been voted on before.
    $vote = new swiftquiz_vote($session->data->id, $slot);
    $hasvotes = count($vote->get_results()) > 0;

    return [
        'has_votes' => $hasvotes,
        'question_type' => $qtype,
        'responses' => $results['responses'],
        'responded' => $results['responded'],
        'total_students' => $results['student_count'],
        'merge_count' => $mergecount,
        'question' => $buf_responses,
        'options' => $options,
        'option_num' => count($options),
        'rightcount'=>$rightcount,   //Number of complete correct answers
        'shortanswertags'=>$shortanswertags,//
        'shortanswertags1'=>$shortanswertags1,
        'question_id' => $question->id,
        'session' => $session,
        'show_abs' => $show_abs,
        'show_percent' => $show_percent
    ];
}

/**
 * Merge a response into another.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function merge_responses(swiftquiz_session $session) {
    $session->load_session_questions();
    $slot = optional_param('slot', count($session->questions), PARAM_INT);
    if (!isset($session->questions[$slot])) {
        return ['status' => 'error'];
    }
    $from = required_param('from', PARAM_TEXT);
    $into = required_param('into', PARAM_TEXT);
    $session->merge_responses($slot, $from, $into);
    return ['status' => 'success'];
}

/**
 * Undo the last merge.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function undo_merge(swiftquiz_session $session) {
    $session->load_session_questions();
    $slot = optional_param('slot', count($session->questions), PARAM_INT);
    if (!isset($session->questions[$slot])) {
        return ['status' => 'error'];
    }
    $session->undo_merge($slot);
    return ['status' => 'success'];
}

/**
 * Convert STACK (Maxima) string to LaTeX format.
 * @return mixed[]
 */
function stack_to_latex() {
    global $DB;
    $input = required_param('input', PARAM_RAW);
    $input = urldecode($input);
    $question = $DB->get_record_sql('SELECT id FROM {question} WHERE qtype = ? AND name LIKE ?', ['stack', '{IMPROV}%']);
    if (!$question) {
        return [
            'message' => 'STACK question not found.',
            'latex' => $input,
            'original' => $input
        ];
    }

    /** @var \qtype_stack_question $question */
    $question = \question_bank::load_question($question->id);
    $question->initialise_question_from_seed();
    $state = $question->get_input_state('ans1', ['ans1' => $input]);
    $latex = $state->contentsdisplayed;

    return [
        'latex' => $latex,
        'original' => $input
    ];
}

/**
 * Retrieve the current state of the session.
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function session_info(swiftquiz_session $session) {
    global $DB;
    switch ($session->data->status) {
        // Just a generic response with the state.
        case 'notrunning':
            if ($session->swiftquiz->is_instructor()) {
                $session = new swiftquiz_session($session->swiftquiz, $session->data->id);
                $session->load_attempts();
                return [
                    'status' => $session->data->status,
                    'student_count' => $session->get_student_count()
                ];
            }
            // Fall-through.
        case 'preparing':
        case 'reviewing':
            return [
                'status' => $session->data->status,
                'slot' => $session->data->slot // For the preplanned questions.
            ];

        case 'voting':
            $voteoptions = $DB->get_records('swiftquiz_votes', ['sessionid' => $session->data->id]);
            $options = [];
            $html = '<div class="swiftquiz-vote swiftquiz-response-container">';
            $i = 0;
            foreach ($voteoptions as $voteoption) {
                $options[] = [
                    'text' => $voteoption->attempt,
                    'id' => $voteoption->id,
                    'question_type' => $voteoption->qtype,
                    'content_id' => "vote_answer_label_$i"
                ];
                $html .= '<label>';
                $html .= '<input class="swiftquiz-select-vote" type="radio" name="vote" value="' . $voteoption->id . '">';
                $html .= '<span id="vote_answer_label_' . $i . '">' . $voteoption->attempt . '</span>';
                $html .= '</label><br>';
                $i++;
            }
            $html .= '</div>';
            $html .= '<button id="swiftquiz_save_vote" class="btn btn-primary">Save</button>';
            return [
                'status' => 'voting',
                'html' => $html,
                'options' => $options
            ];

        // Send the currently active question.
        case 'running':
            return [
                'status' => 'running',
                'questiontime' => $session->data->currentquestiontime,
                'delay' => $session->data->nextstarttime - time()
            ];

        // This should not be reached, but if it ever is, let's just assume the quiz is not running.
        default:
            return [
                'status' => 'notrunning',
                'message' => 'Unknown error. State: ' . $session->data->status
            ];
    }
}

/**
 *test
 */
function test(swiftquiz_session $session){
    $qtype = required_param('qtype', PARAM_TEXT);
//    $name = '*'.required_param('qname', PARAM_TEXT);
    $question =required_param('question', PARAM_TEXT);
    $answers = [];
    if($qtype == '3'){
        $answers[] = required_param('option_a', PARAM_TEXT);
        $answers[] = required_param('option_b', PARAM_TEXT);
        $answers[] = required_param('option_c', PARAM_TEXT);
    } else if($qtype == '4'){
        $answers[] = required_param('option_a', PARAM_TEXT);
        $answers[] = required_param('option_b', PARAM_TEXT);
        $answers[] = required_param('option_c', PARAM_TEXT);
        $answers[] = required_param('option_d', PARAM_TEXT);
    } else if($qtype == '5'){
        $answers[] = required_param('option_a', PARAM_TEXT);
        $answers[] = required_param('option_b', PARAM_TEXT);
        $answers[] = required_param('option_c', PARAM_TEXT);
        $answers[] = required_param('option_d', PARAM_TEXT);
        $answers[] = required_param('option_e', PARAM_TEXT);
    }

    $improv = new improviser($session->swiftquiz);
    $improv->build_question("*".$question, "*".$question,$answers);

    return ['status' => 'success'];
}

/**
 * Handle an instructor request.
 * @param string $action
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function handle_instructor_request(string $action, swiftquiz_session $session) : array {
    switch ($action) {
        case 'start_quiz':
            return start_quiz($session);
        case 'get_question_form':
            return get_question_form($session);
        case 'save_question':
            return save_question($session);
        case 'list_improvise_questions':
            return show_all_improvise_questions($session->swiftquiz);
        case 'list_jump_questions':
            return show_all_jump_questions($session->swiftquiz);
        case 'run_voting':
            return run_voting($session);
        case 'get_vote_results':
            return get_vote_results($session);
        case 'get_results':
            return get_results($session);
        case 'start_question':
            return start_question($session);
        case 'end_question':
            return end_question($session);
        case 'get_right_response':
            return get_right_response($session);
        case 'merge_responses':
            return merge_responses($session);
        case 'undo_merge':
            return undo_merge($session);
        case 'close_session':
            return close_session($session);
        case 'info':
            return session_info($session);
        case 'test':
            return test($session);
//            return test($session);
        default:
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}

/**
 * Handle a student request.
 * @param string $action
 * @param swiftquiz_session $session
 * @return mixed[]
 */
function handle_student_request(string $action, swiftquiz_session $session) : array {
    switch ($action) {
        case 'save_question':
            return save_question($session);
        case 'save_vote':
            return save_vote($session);
        case 'get_question_form':
            return get_question_form($session);
        case 'info':
            return session_info($session);
        default:
            return ['status' => 'error', 'message' => 'Invalid action'];
    }
}

/**
 * The entry point to handle instructor and student actions.
 * @return mixed[]
 */
function swiftquiz_ajax() {
    $action = required_param('action', PARAM_ALPHANUMEXT);

    // TODO: Better solution if more non-session actions are added.
    if ($action === 'stack') {
        return stack_to_latex();
    }

    $cmid = required_param('id', PARAM_INT);
    $sessionid = required_param('sessionid', PARAM_INT);

    $swiftquiz = new swiftquiz($cmid);
    $session = new swiftquiz_session($swiftquiz, $sessionid);
    if (!$session->data->sessionopen) {
        return [
            'status' => 'sessionclosed',
            'message' => 'Session is closed'
        ];
    }

    $session->load_attempt();
    if (!$session->attempt) {
        return [
            'status' => 'error',
            'message' => 'No attempt found'
        ];
    }
    if (!$session->attempt->is_active()) {
        return [
            'status' => 'error',
            'message' => 'This attempt is not in progress'
        ];
    }

    if ($swiftquiz->is_instructor()) {
        return handle_instructor_request($action, $session);
    } else {
        return handle_student_request($action, $session);
    }
}

$starttime = microtime(true);
$data = swiftquiz_ajax();
$endtime = microtime(true);
$data['debugmu'] = $endtime - $starttime;
echo json_encode($data);
