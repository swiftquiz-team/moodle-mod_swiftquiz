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
 * @package   mod_swiftquiz
 * @author    Sebastian S. Gundersen <sebastsg@stud.ntnu.no>
 * @copyright 2014 University of Wisconsin - Madison
 * @copyright 2018 NTNU
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

define(['jquery', 'mod_swiftquiz/core'], function ($, swift) {

    const Quiz = swift.Quiz;
    const Question = swift.Question;
    const Ajax = swift.Ajax;
    const setText = swift.setText;

    class Student {

        /**
         * @param {Quiz} quiz
         */
        constructor(quiz) {
            this.quiz = quiz;
            this.voteAnswer = undefined;
            let self = this;
            $(document).on('submit', '#swiftquiz_question_form', function(event) {
                event.preventDefault();
                self.submitAnswer();
            }).on('click', '#swiftquiz_save_vote', function() {
                self.saveVote();
            }).on('click', '.swiftquiz-select-vote', function() {
                self.voteAnswer = this.value;
            });
        }

        onNotRunning(data) {
            setText(Quiz.info, 'instructions_for_student');
        }

        onPreparing(data) {
            setText(Quiz.info, 'wait_for_instructor');
        }

        onRunning(data) {
            if (this.quiz.question.isRunning) {
                return;
            }
            const started = this.quiz.question.startCountdown(data.questiontime, data.delay);
            if (!started) {
                setText(Quiz.info, 'wait_for_instructor');
            }
        }

        onReviewing(data) {
            this.quiz.question.isVoteRunning = false;
            this.quiz.question.isRunning = false;
            this.quiz.question.hideTimer();
            Quiz.hide(Question.box);
            setText(Quiz.info, 'wait_for_instructor');
        }

        onSessionClosed(data) {
            window.location = location.href.split('&')[0];
        }

        onVoting(data) {
            if (this.quiz.question.isVoteRunning) {
                return;
            }
            Quiz.info.html(data.html);
            Quiz.show(Quiz.info);
            const options = data.options;
            for (let i = 0; i < options.length; i++) {
                Quiz.addMathjaxElement($('#' + options[i].content_id), options[i].text);
                if (options[i].question_type === 'stack') {
                    Quiz.renderMaximaEquation(options[i].text, options[i].content_id);
                }
            }
            this.quiz.question.isVoteRunning = true;
        }

        onStateChange(state) {

        }

        onQuestionTimerEnding() {

        }

        onTimerTick(timeLeft) {
            setText(Question.timer, 'question_will_end_in_x_seconds', 'swiftquiz', timeLeft);
        }

        onQuestionRefreshed(data) {

        }

        /**
         * Submit answer for the current question.
         */
        submitAnswer() {
            if (this.quiz.question.isSaving) {
                // Don't save twice.
                return;
            }
            this.quiz.question.isSaving = true;
            if (typeof tinyMCE !== 'undefined') {
                tinyMCE.triggerSave();
            }
            const serialized = Question.form.serializeArray();
            let data = {};
            for (let name in serialized) {
                if (serialized.hasOwnProperty(name)) {
                    data[serialized[name].name] = serialized[name].value;
                }
            }
            Ajax.post('save_question', data, data => {
                if (data.feedback.length > 0) {
                    Quiz.show(Quiz.info.html(data.feedback));
                } else {
                    setText(Quiz.info, 'wait_for_instructor');
                }
                this.quiz.question.isSaving = false;
                if (!this.quiz.question.isRunning) {
                    return;
                }
                if (this.quiz.question.isVoteRunning) {
                    return;
                }
                Quiz.hide(Question.box);
                this.quiz.question.hideTimer();
            }).fail(() => {
                this.quiz.question.isSaving = false;
            });
        }

        saveVote() {
            Ajax.post('save_vote', {vote: this.voteAnswer}, data => {
                if (data.status === 'success') {
                    setText(Quiz.info, 'wait_for_instructor');
                } else {
                    setText(Quiz.info, 'you_already_voted');
                }
            });
        }

    }

    return {
        initialize: () => {
            let quiz = new Quiz(Student);
            quiz.poll(2000);
        }
    }

});
