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

namespace mod_swiftquiz;

defined('MOODLE_INTERNAL') || die();

/**
 * An attempt for the quiz. Maps to individual question attempts.
 *
 * @package   mod_swiftquiz
 * @author    Sebastian S. Gundersen <sebastian@sgundersen.com>
 * @copyright 2014 University of Wisconsin - Madison
 * @copyright 2019 NTNU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class swiftquiz_attempt {

    /** Constants for the status of the attempt */
    const NOTSTARTED = 0;
    const INPROGRESS = 10;
    const PREVIEW = 20;
    const FINISHED = 30;

    /** @var \stdClass */
    public $data;

    /** @var \question_usage_by_activity $quba the question usage by activity for this attempt */
    public $quba;

    /**
     * Construct the class. If data is passed in we set it, otherwise initialize empty class
     * @param \context_module $context
     * @param \stdClass $data
     */
    public function __construct(\context_module $context, \stdClass $data = null) {
        if (empty($data)) {
            // Create new attempt.
            $this->data = new \stdClass();
            // Create a new quba since we're creating a new attempt.
            $this->quba = \question_engine::make_questions_usage_by_activity('mod_swiftquiz', $context);
            $this->quba->set_preferred_behaviour('immediatefeedback');
        } else {
            // Load it up in this class instance.
            $this->data = $data;
            $this->quba = \question_engine::load_questions_usage_by_activity($this->data->questionengid);
        }
    }

    public function belongs_to_current_user() {
        global $USER;
        return $this->data->userid == $USER->id || $this->data->guestsession == $USER->sesskey;
    }

    /**
     * Check if this attempt is currently in progress.
     * @return bool
     */
    public function is_active() {
        switch ($this->data->status) {
            case self::INPROGRESS:
            case self::PREVIEW:
                return true;
            default:
                return false;
        }
    }

    /**
     * @param swiftquiz_session $session
     * @return bool false if invalid question id
     */
    public function create_missing_attempts(swiftquiz_session $session) {
        foreach ($session->questions as $slot => $question) {
            if ($this->quba->next_slot_number() > $slot) {
                continue;
            }
            $questiondefinitions = question_load_questions([$question->questionid]);
            $questiondefinition = reset($questiondefinitions);
            if (!$questiondefinition) {
                return false;
            }
            $question = \question_bank::make_question($questiondefinition);
            $slot = $this->quba->add_question($question);
            $this->quba->start_question($slot);
            $this->data->responded = 0;
        }
        return true;
    }

    /**
     * Anonymize all the question attempt steps for this quiz attempt.
     * It will be impossible to identify which user answered all questions linked to this question usage.
     * @throws \dml_exception
     */
    public function anonymize_answers() {
        global $DB;
        $attempts = $DB->get_records('question_attempts', ['questionusageid' => $this->data->questionengid]);
        foreach ($attempts as $attempt) {
            $steps = $DB->get_records('question_attempt_steps', ['questionattemptid' => $attempt->id]);
            foreach ($steps as $step) {
                $step->userid = null;
                $DB->update_record('question_attempt_steps', $step);
            }
        }
        $this->data->userid = null;
        $this->save();
    }

    /**
     * Saves the current attempt.
     * @return bool
     */
    public function save() {
        global $DB;

        // Save the question usage by activity object.
        if ($this->quba->question_count() > 0) {
            \question_engine::save_questions_usage_by_activity($this->quba);
        } else {
            // TODO: Don't suppress the error if it becomes possible to save QUBAs without slots.
            @\question_engine::save_questions_usage_by_activity($this->quba);
        }

        // Add the quba id as the questionengid.
        // This is here because for new usages there is no id until we save it.
        $this->data->questionengid = $this->quba->get_id();
        $this->data->timemodified = time();
        try {
            if (isset($this->data->id)) {
                $DB->update_record('swiftquiz_attempts', $this->data);
            } else {
                $this->data->id = $DB->insert_record('swiftquiz_attempts', $this->data);
            }
        } catch (\Exception $e) {
            return false;
        }
        return true;
    }

    /**
     * Saves a question attempt from the swiftquiz question
     * @param int $slot
     */
    public function save_question($slot) {
        global $DB;
        $transaction = $DB->start_delegated_transaction();
        $this->quba->process_all_actions();
        $this->quba->finish_question($slot, time());
        $this->data->timemodified = time();
        $this->data->responded = 1;
        $this->save();
        $transaction->allow_commit();
    }

    /**
     * Gets the feedback for the specified question slot
     *
     * If no slot is defined, we attempt to get that from the slots param passed
     * back from the form submission
     *
     * @param swiftquiz $swiftquiz
     * @param int $slot The slot for which we want to get feedback
     * @return string HTML fragment of the feedback
     */
    public function get_question_feedback(swiftquiz $swiftquiz, $slot = -1) {
        global $PAGE;
        if ($slot === -1) {
            // Attempt to get it from the slots param sent back from a question processing.
            $slots = required_param('slots', PARAM_ALPHANUMEXT);
            $slots = explode(',', $slots);
            $slot = $slots[0]; // Always just get the first thing from explode.
        }
        $question = $this->quba->get_question($slot);
        $renderer = $question->get_renderer($PAGE);
        $displayoptions = $swiftquiz->get_display_options(false, '');
        return $renderer->feedback($this->quba->get_question_attempt($slot), $displayoptions);
    }

    /**
     * Returns whether current user has responded
     * @param int $slot
     * @return bool
     */
    public function has_responded($slot) {
        $response = $this->quba->get_question_attempt($slot)->get_response_summary();
        return $response !== null && $response !== '';
    }

    /**
     * Returns response data as an array
     * @param int $slot
     * @return string[]
     */
    public function get_response_data($slot) {
        $questionattempt = $this->quba->get_question_attempt($slot);
        $response = $questionattempt->get_response_summary();
        if ($response === null || $response === '') {
            return [];
        }
        $qtype = $questionattempt->get_question()->get_type_name();
        switch ($qtype) {
            case 'stack':
                // TODO: Figure out a better way to get rid of the input name.
                $response = str_replace('ans1: ', '', $response);
                $response = str_replace(' [valid]', '', $response);
                $response = str_replace(' [score]', '', $response);
                return [$response];
            case 'multichoice':
                return explode("\n; ", trim($response, "\n"));
            default:
                return [$response];
        }
    }

    /**
     * Closes the attempt
     * @param swiftquiz $swiftquiz
     */
    public function close_attempt(swiftquiz $swiftquiz) {
        $this->quba->finish_all_questions(time());
        // We want the instructor to remain in preview mode.
        if (!$swiftquiz->is_instructor()) {
            $this->data->status = self::FINISHED;
        }
        $this->data->timefinish = time();
        $this->save();
    }

    public function total_answers() : int {
        $count = 0;
        foreach ($this->quba->get_slots() as $slot) {
            if ($this->has_responded($slot)) {
                $count++;
            }
        }
        return $count;
    }

}
