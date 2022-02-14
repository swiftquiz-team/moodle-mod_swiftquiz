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
    var questionid="";


    /*建立模态框对象*/
    var modalBox = {};
    /*获取模态框*/
    modalBox.modal = document.getElementById("myModal");
    /*获得trigger按钮*/
    modalBox.triggerBtn = document.getElementById("swiftquiz_control_triggerBtn");
    /*获得关闭按钮*/
    modalBox.closeBtn = document.getElementById("closeBtn");
    /*获得保存按钮*/
    modalBox.saveBtn = document.getElementById("save");
    /*题目类型*/
    modalBox.short = document.getElementById("short");
    modalBox.mco3 = document.getElementById("MCO3");
    modalBox.mco4 = document.getElementById("MCO4");
    modalBox.mco5 = document.getElementById("MCO5");
    /*选项*/
    modalBox.option_a = document.getElementById("option_a");
    modalBox.option_b = document.getElementById("option_b");
    modalBox.option_c = document.getElementById("option_c");
    modalBox.option_d = document.getElementById("option_d");
    modalBox.option_e = document.getElementById("option_e");
    modalBox.label_a = document.getElementById("label_a");
    modalBox.label_b = document.getElementById("label_b");
    modalBox.label_c = document.getElementById("label_c");
    modalBox.label_d = document.getElementById("label_d");
    modalBox.label_e = document.getElementById("label_e");
    modalBox.qtype = '5';
    /*模态框显示*/
    modalBox.show = function() {
        console.log(this.modal);
        this.modal.style.display = "block";
    }
    /*模态框关闭*/
    modalBox.close = function() {
        this.modal.style.display = "none";
    }
    /*保存*/
    modalBox.save = function () {
        // var qname = document.getElementById("qname").value;
        var question = document.getElementById("question").value;
        var option_a = document.getElementById("option_a").value;
        var option_b = document.getElementById("option_b").value;
        var option_c = document.getElementById("option_c").value;
        var option_d = document.getElementById("option_d").value;
        var option_e = document.getElementById("option_e").value;
        var qtype = this.qtype;
        // alert(qdata);
        // window.location.href = window.location.href + '&qdata=' + qdata;
        // $.post('ajax.php', {action: 'test',qdata: JSON.stringify(qdata)}, () => {});
        Ajax.post('test', {
            qtype: qtype,
            // qname: qname,
            question: question,
            option_a: option_a,
            option_b: option_b,
            option_c: option_c,
            option_d: option_d,
            option_e: option_e
        }, () => {});
        this.modal.style.display = "none";
    }
    /*当用户点击模态框内容之外的区域，模态框也会关闭*/
    modalBox.outsideClick = function() {
        var modal = this.modal;
        window.onclick = function(event) {
            if(event.target == modal) {
                modal.style.display = "none";
            }
        }
    }
    /*hide option*/
    modalBox.hide_a = function () {
        this.option_a.style.visibility = "hidden";
        this.label_a.style.visibility = "hidden";
    }
    modalBox.hide_b = function () {
        this.option_b.style.visibility = "hidden";
        this.label_b.style.visibility = "hidden";
    }
    modalBox.hide_c = function () {
        this.option_c.style.visibility = "hidden";
        this.label_c.style.visibility = "hidden";
    }
    modalBox.hide_d = function () {
        this.option_d.style.visibility = "hidden";
        this.label_d.style.visibility = "hidden";
    }
    modalBox.hide_e = function () {
        this.option_e.style.visibility = "hidden";
        this.label_e.style.visibility = "hidden";
    }
    /*show option*/
    modalBox.show_a = function () {
        this.option_a.style.visibility = "visible";
        this.label_a.style.visibility = "visible";
    }
    modalBox.show_b = function () {
        this.option_b.style.visibility = "visible";
        this.label_b.style.visibility = "visible";
    }
    modalBox.show_c = function () {
        this.option_c.style.visibility = "visible";
        this.label_c.style.visibility = "visible";
    }
    modalBox.show_d = function () {
        this.option_d.style.visibility = "visible";
        this.label_d.style.visibility = "visible";
    }
    modalBox.show_e = function () {
        this.option_e.style.visibility = "visible";
        this.label_e.style.visibility = "visible";
    }

    /*question type*/
    modalBox.choice3 = function () {
        this.qtype = '3';
        this.show_a();
        this.show_b();
        this.show_c();
        this.hide_d();
        this.hide_e();
    }

    modalBox.choice4 = function () {
        this.qtype = '4';
        this.show_a();
        this.show_b();
        this.show_c();
        this.show_d();
        this.hide_e();
    }

    modalBox.choice5 = function () {
        this.qtype = '5';
        this.show_a();
        this.show_b();
        this.show_c();
        this.show_d();
        this.show_e();
    }

    modalBox.shortanswer = function () {
        this.qtype = 'shortanswer';
        this.hide_a();
        this.hide_b();
        this.hide_c();
        this.hide_d();
        this.hide_e();
    }

    /*模态框初始化*/
    modalBox.init = function() {
        var that = this;
        this.triggerBtn.onclick = function() {
            that.show();
        }
        this.short.onclick = function () {
            that.shortanswer();
        }
        this.mco3.onclick = function () {
            that.choice3();
        }
        this.mco4.onclick = function () {
            that.choice4()
        }
        this.mco5.onclick = function () {
            that.choice5();
        }
        this.closeBtn.onclick = function() {
            that.close();
        }
        this.outsideClick();
        this.saveBtn.onclick = function () {
            that.save();
            that.close();
        }
    }
    modalBox.init();



    class ResponseView {

        /**
         * @param {Quiz} quiz
         */
        constructor(quiz) {
            this.quiz = quiz;
            this.currentResponses = [];
            this.showVotesUponReview = false;
            this.respondedCount = 0;
            this.showResponses = false;
            this.totalStudents = 0;
            // new flags
            this.statisticFlag = true; // 判断是否显示Statistic
            this.wordCloudFlag = true; // 判断是否显示了WordCloud
            this.exwordCloudFlag = true;
            this.refreshFlag = false; // test
            this.refreshFlag2 = false; // test
            $(document).on('click', '#swiftquiz_undo_merge', () => this.undoMerge());
            $(document).on('click', event => {
                // Clicking a row to merge.
                if (event.target.classList.contains('bar')) {
                    this.startMerge(event.target.id);
                } else if (event.target.parentNode && event.target.parentNode.classList.contains('bar')) {
                    this.startMerge(event.target.parentNode.id);
                }
            });
            $(document).on('click', '#review_show_normal_results', () => this.refresh(false));
            $(document).on('click', '#review_show_vote_results', () => this.refreshVotes());
        }

        // toggle test
        toggleTest() {
            this.refreshFlag = !this.refreshFlag;
        }

        toggleHide() {
            if (this.refreshFlag2 === true) {
                Quiz.uncheck(Instructor.control('toggle_test'));
                this.refreshFlag = false;
                this.refreshFlag2 = false;
            }
        }

        /**
         * Clear, but not hide responses.
         */
        clear() {
            Quiz.responses.html('');
            Quiz.responseInfo.html('');
        }

        /**
         * Hides the responses
         */
        hide() {
            Quiz.uncheck(Instructor.control('responses'));
            Quiz.hide(Quiz.responses);
            Quiz.hide(Quiz.responseInfo);
            $("#wordclound_"+questionid).hide();
        }

        /**
         * Shows the responses
         */
        show() {
            Quiz.check(Instructor.control('responses'));
            Quiz.show(Quiz.responses);
            Quiz.show(Quiz.responseInfo);
            $("#wordclound_"+questionid).show();
            if (this.showVotesUponReview) {
                this.refreshVotes();
                this.showVotesUponReview = false;
            } else {
                if (this.refreshFlag === true) {
                    Quiz.check(Instructor.control('toggle_test'));
                    this.refresh1(false);
                } else {
                    Quiz.uncheck(Instructor.control('toggle_test'));
                    this.refresh(false);
                }
                // this.refresh(false);
            }
        }

        /**
         * Toggle whether to show or hide the responses
         */
        toggle() {
            this.showResponses = !this.showResponses;
            if (this.showResponses) {
                this.show();
            } else {
                this.hide();
            }
        }

        hideStat() {
            Quiz.uncheck(Instructor.control('show_statistic'));
            $("#current_responses_wrapper").hide();
        }


        showStat() {
            Quiz.check(Instructor.control('show_statistic'));
            $("#current_responses_wrapper").show();

        }

        toggleStat() {
            this.statisticFlag = !this.statisticFlag;
            if (this.statisticFlag) {
                this.showStat();

            } else {
                this.hideStat();

            }
        }

        hideWC() {
            Quiz.uncheck(Instructor.control('show_word_cloud'));
            $("#wordclound_"+questionid).hide();
            this.exwordCloudFlag = false;
            // this.wordCloudFlag = false;
        }

        showWC() {
            Quiz.check(Instructor.control('show_word_cloud'));
            $("#wordclound_"+questionid).show();
            this.exwordCloudFlag = true;
            // this.wordCloudFlag = true;
        }

        toggleWC() {
            this.wordCloudFlag = !this.wordCloudFlag;
            if (this.wordCloudFlag) {
                this.showWC();
            } else {
                this.hideWC();
            }
        }

        /**
         * End the response merge.
         */
        endMerge() {
            $('.merge-into').removeClass('merge-into');
            $('.merge-from').removeClass('merge-from');
        }

        /**
         * Undo the last response merge.
         */
        undoMerge() {
            Ajax.post('undo_merge', {}, () => this.refresh(true));
        }

        /**
         * Merges responses based on response string.
         * @param {string} from
         * @param {string} into
         */
        merge(from, into) {
            Ajax.post('merge_responses', {from: from, into: into}, () => this.refresh(false));
        }

        /**
         * Start a merge between two responses.
         * @param {string} fromRowBarId
         */
        startMerge(fromRowBarId) {
            const $barCell = $('#' + fromRowBarId);
            let $row = $barCell.parent();
            if ($row.hasClass('merge-from')) {
                this.endMerge();
                return;
            }
            if ($row.hasClass('merge-into')) {
                const $fromRow = $('.merge-from');
                this.merge($fromRow.data('response'), $row.data('response'));
                this.endMerge();
                return;
            }
            $row.addClass('merge-from');
            let $table = $row.parent().parent();
            $table.find('tr').each(function () {
                const $cells = $(this).find('td');
                if ($cells[1].id !== $barCell.attr('id')) {
                    $(this).addClass('merge-into');
                }
            });
        }

        /**
         * Create controls to toggle between the responses of the actual question and the vote that followed.
         * @param {string} name Can be either 'vote_response' or 'current_response'
         */
        createControls(name) {
            if (!this.quiz.question.hasVotes) {
                Quiz.hide(Quiz.responseInfo);
                return;
            }
            // Add button for instructor to change what to review.
            if (this.quiz.state === 'reviewing') {
                let $showNormalResult = $('#review_show_normal_results');
                let $showVoteResult = $('#review_show_vote_results');
                Quiz.show(Quiz.responseInfo);
                if (name === 'vote_response') {
                    if ($showNormalResult.length === 0) {
                        setText(Quiz.responseInfo.html('<h4 class="inline"></h4>').children('h4'), 'showing_vote_results');
                        Quiz.responseInfo.append('<button id="review_show_normal_results" class="btn btn-primary"></button><br>');
                        setText($('#review_show_normal_results'), 'click_to_show_original_results');
                        $showVoteResult.remove();
                    }
                } else if (name === 'current_response') {
                    if ($showVoteResult.length === 0) {
                        setText(Quiz.responseInfo.html('<h4 class="inline"></h4>').children('h4'), 'showing_original_results');
                        Quiz.responseInfo.append('<button id="review_show_vote_results" class="btn btn-primary"></button><br>');
                        setText($('#review_show_vote_results'), 'click_to_show_vote_results');
                        $showNormalResult.remove();
                    }
                }
            }
        }

        /**
         * Create a new and unsorted response bar graph.
         * @param {Array.<Object>} responses
         * @param {string} name
         * @param {string} targetId
         * @param {string} graphId
         * @param {boolean} rebuild If the table should be completely rebuilt or not
         */
        createBarGraph(responses, name, targetId, graphId, rebuild) {
            let target = document.getElementById(targetId);
            if (target === null) {
                return;
            }
            let total = 0;
            let highestResponseCount = 0;
            for (let i = 0; i < responses.length; i++) {
                let count = parseInt(responses[i].count); // In case count is a string.

                total += count;
                if (count > highestResponseCount) {
                    highestResponseCount = count;
                }

            }
            if (total === 0) {
                total = 1;
            }

            // Remove the rows if it should be rebuilt.
            if (rebuild) {
                target.innerHTML = '';
            }

            // Prune rows.
            for (let i = 0; i < target.rows.length; i++) {
                let prune = true;
                for (let j = 0; j < responses.length; j++) {
                    if (target.rows[i].dataset.response === responses[j].response) {
                        prune = false;
                        break;
                    }
                }
                if (prune) {
                    target.deleteRow(i);
                    i--;
                }
            }

            this.createControls(name);

            name += graphId;

            // Add rows.
            for (let i = 0; i < responses.length; i++) {
                //get the real percentage
                let percent = ((parseInt(responses[i].count) - 1) / (total - responses[0].option_num)) * 100;
                // special percent of ddwtos
                if(responses[i].qtype == "ddwtos"){
                    percent = (parseInt(responses[i].count) / ((total - responses[0].option_num) / responses[0].option_num)) * 100;
                }
                // const percent = (parseInt(responses[i].count) / total) * 100;
                // const percent = (parseInt(responses[i].count) / highestResponseCount) * 100;

                // Check if row with same response already exists.
                let rowIndex = -1;
                let currentRowIndex = -1;
                for (let j = 0; j < target.rows.length; j++) {
                    if (target.rows[j].dataset.response === responses[i].response) {
                        rowIndex = target.rows[j].dataset.row_i;
                        currentRowIndex = j;
                        break;
                    }
                }

                if (rowIndex === -1) {
                    rowIndex = target.rows.length;
                    let row = target.insertRow();
                    row.dataset.response_i = i;
                    row.dataset.response = responses[i].response;
                    row.dataset.percent = percent;
                    row.dataset.row_i = rowIndex;
                    row.dataset.count = responses[i].count;
                    row.classList.add('selected-vote-option');
                    if (percent < 15) {
                        row.classList.add('outside');
                    }

                    let percentstring = '';
                    let count = parseInt(responses[i].count) - 1; // count in the bargraph -1 to balance the fake response
                    if(responses[i].qtype == "ddwtos"){
                        count = parseInt(responses[i].count);
                    }
                    if (responses[i].qtype == "shortanswer") {
                        count = parseInt(responses[i].count);
                    }
                    if(responses[i].qtype!=="shortanswer"){
                        percentstring='&nbsp;&nbsp;&nbsp'+percent.toFixed(0)+'%';
                    }
                    let countHtml = '<span id="' + name + '_count_' + rowIndex + '">' + count + percentstring + '</span>';
                    if(responses[i].show_percent == 'false'){
                        countHtml = '<span id="' + name + '_count_' + rowIndex + '">' + count + '</span>';
                    }

                    // blue separator of blanks
                    if(responses[i].rightanswer == -1 && responses[i].qtype == "ddwtos"){
                        countHtml = '<span id="' + name + '_count_' + rowIndex + '">' + '</span>';
                        percent = 2; // the length of the blue bar
                    }

                    let responseCell = row.insertCell(0);
                    responseCell.onclick = function () {
                        $(this).parent().toggleClass('selected-vote-option');
                    };

                    let barCell = row.insertCell(1);
                    barCell.classList.add('bar');
                    barCell.id = name + '_bar_' + rowIndex;

                    let backgroundcolor = "";
                    if(responses[i].rightanswer!=-1){
                        backgroundcolor = "background:red";
                        if(responses[i].rightanswer){
                            backgroundcolor = "background:green";
                        }
                    }
                    // barCell.innerHTML = '<div style="width:' + percent + '%;">' + countHtml + '</div>';
                    // show a small bargraph if none chose the option
                    barCell.innerHTML = '<div style="width:' + (percent + 1) + '%; ' + backgroundcolor + ';">' + countHtml + '</div>';

                    const latexId = name + '_latex_' + rowIndex;
                    responseCell.innerHTML = '<span id="' + latexId + '"></span>';
                    Quiz.addMathjaxElement($('#' + latexId), responses[i].response);
                    if (responses[i].qtype === 'stack') {
                        Quiz.renderMaximaEquation(responses[i].response, latexId);
                    }
                } else {
                    let currentRow = target.rows[currentRowIndex];
                    currentRow.dataset.row_i = rowIndex;
                    currentRow.dataset.response_i = i;
                    currentRow.dataset.percent = percent;
                    currentRow.dataset.count = responses[i].count;
                    const containsOutside = currentRow.classList.contains('outside');
                    if (percent > 15 && containsOutside) {
                        currentRow.classList.remove('outside');
                    } else if (percent < 15 && !containsOutside) {
                        currentRow.classList.add('outside');
                    }
                    let countElement = document.getElementById(name + '_count_' + rowIndex);
                    if (countElement !== null) {

                        countElement.innerHTML = responses[i].count+ percentstring;
                    }
                    let barElement = document.getElementById(name + '_bar_' + rowIndex);
                    if (barElement !== null) {
                        barElement.firstElementChild.style.width = percent + '%';
                    }
                }
            }
        };

        /**
         * Sort the responses in the graph by how many had the same response.
         * @param {string} targetId
         */
        static sortBarGraph(targetId) {
            let target = document.getElementById(targetId);
            if (target === null) {
                return;
            }
            let isSorting = true;
            while (isSorting) {
                isSorting = false;
                for (let i = 0; i < (target.rows.length - 1); i++) {
                    const current = parseInt(target.rows[i].dataset.percent);
                    const next = parseInt(target.rows[i + 1].dataset.percent);
                    if (current < next) {
                        target.rows[i].parentNode.insertBefore(target.rows[i + 1], target.rows[i]);
                        isSorting = true;
                        break;
                    }
                }
            }
        }

        /**
         * Create and sort a bar graph based on the responses passed.
         * @param {string} wrapperId
         * @param {string} tableId
         * @param {Array.<Object>} responses
         * @param {number|undefined} responded How many students responded to the question
         * @param {string} questionType
         * @param {string} graphId
         * @param {boolean} rebuild If the graph should be rebuilt or not.
         */
        set(wrapperId, tableId, responses, responded, questionType, graphId, rebuild) {
            if (responses === undefined) {
                return;
            }

            // Check if any responses to show.
            if (responses.length === 0) {
                Quiz.show(Quiz.responded);
                setText(Quiz.responded.find('h4'), 'a_out_of_b_responded', 'swiftquiz', {
                    a: 0,
                    b: this.totalStudents
                });
                return;
            }

            // Question type specific.
            switch (questionType) {
                case 'shortanswer':
                    for (let i = 0; i < responses.length; i++) {
                        responses[i].response = responses[i].response.trim();
                    }
                    break;
                case 'stack':
                    // Remove all spaces from responses.
                    for (let i = 0; i < responses.length; i++) {
                        responses[i].response = responses[i].response.replace(/\s/g, '');
                    }
                    break;
                default:
                    break;
            }
            // Update data.
            this.currentResponses = [];
            this.respondedCount = 0;
            for (let i = 0; i < responses.length; i++) {
                let exists = false;
                let count = 1;
                if (responses[i].count !== undefined) {
                    count = parseInt(responses[i].count);
                }
                this.respondedCount += count;
                // Check if response is a duplicate.
                for (let j = 0; j < this.currentResponses.length; j++) {
                    if (this.currentResponses[j].response === responses[i].response) {
                        this.currentResponses[j].count += count;
                        //Add correct answer mark
                        this.currentResponses[j].rightanswer = responses[i].rightanswer;
                        exists = true;
                        break;
                    }
                }
                // Add element if not a duplicate.
                if (!exists) {
                    this.currentResponses.push({
                        response: responses[i].response,
                        count: count,
                        qtype: questionType,
                        //Add correct answer mark
                        rightanswer:responses[i].rightanswer,
                        option_num:responses[0].option_num,
                        cnt: responses[i].count,
                        show_percent: responses[i].show_percent
                    });
                }
            }

            // Update responded container.
            if (Quiz.responded.length !== 0 && responded !== undefined) {
                Quiz.show(Quiz.responded);
                setText(Quiz.responded.find('h4'), 'a_out_of_b_responded', 'swiftquiz', {
                    a: responded,
                    b: this.totalStudents
                });
            }

            // Should we show the responses?
            if (!this.showResponses && this.quiz.state !== 'reviewing') {
                Quiz.hide(Quiz.responseInfo);
                Quiz.hide(Quiz.responses);
                return;
            }

            if (document.getElementById(tableId) === null) {
                const html = '<table id="' + tableId + '" class="swiftquiz-responses-overview"></table>';
                Quiz.show($('#' + wrapperId).html(html));
            }
            this.createBarGraph(this.currentResponses, 'current_response', tableId, graphId, rebuild);
            // Dont sort
            if(questionType !== "ddwtos"){
                ResponseView.sortBarGraph(tableId);
            }
        }

        /**
         * Fetch and show results for the ongoing or previous question.
         * @param {boolean} rebuild If the response graph should be rebuilt or not.
         */
        refresh(rebuild) {
            Ajax.get('get_results', {}, data => {
                this.quiz.question.hasVotes = data.has_votes;
                this.totalStudents = parseInt(data.total_students);
                console.log(data);
                this.set('swiftquiz_responses_container', 'current_responses_wrapper',
                    data.responses, data.responded, data.question_type, 'results', rebuild);

                if (data.merge_count > 0) {
                    Quiz.show($('#swiftquiz_undo_merge'));
                } else {
                    Quiz.hide($('#swiftquiz_undo_merge'));
                }

                //Add the display of the number of people who answered completely correctly
                if(data.show_abs=="true"){
                    let right_count = data.rightcount;
                    if(right_count < 0){
                        right_count++;
                    }
                    document.getElementById('rightanwsernum').innerHTML=right_count+" / "+this.totalStudents+" all correct";
                    // document.getElementById('rightanwsernum').style.visibility="hidden";
                }
                questionid=data.question_id;
                var wordFreqData = data.shortanswertags;
                console.log(wordFreqData);
                console.log(wordFreqData.length);
                if((!this.showResponses && this.quiz.state !== 'reviewing') || !wordFreqData.length){
                    return;
                }
                var options = eval({
                    "list": wordFreqData,
                    "gridSize": 6, // Intensity
                    "weightFactor": 1, // Font Size
                    "maxFontSize": 50, //Max
                    "minFontSize": 14, //Min
                    "fontWeight": 'normal', //Font weight
                    "fontFamily": 'Times, serif', // Font
                    "color": 'random-light', // Font Color
                    "backgroundColor": '#333', // 背景颜色
                    "rotateRatio": 1, // Font Tilt
                    "drawOutOfBound ":true,
                    "shrinkToFit":true
                });
                var canvasid="wordclound_"+data.question_id;
                if (document.getElementById(canvasid) === null) {
                    var canvas = document.createElement("div");// create div
                    canvas.id =canvasid;
                    canvas.style.height="400px";
                    canvas.style.width="600px";
                    canvas.style.margin="0 auto";
                    $("#swiftquiz_responses_container").append(canvas);
                }else {
                    canvas = document.getElementById(canvasid);
                }
                WordCloud(canvas, options); //generate

            });
        }
        refresh1(rebuild) {
            Ajax.get('get_results', {}, data => {
                this.quiz.question.hasVotes = data.has_votes;
                this.totalStudents = parseInt(data.total_students);
                console.log(data);
                this.set('swiftquiz_responses_container', 'current_responses_wrapper',
                    data.responses, data.responded, data.question_type, 'results', rebuild);

                if (data.merge_count > 0) {
                    Quiz.show($('#swiftquiz_undo_merge'));
                } else {
                    Quiz.hide($('#swiftquiz_undo_merge'));
                }

                //Add the display of the number of people who answered completely correctly
                if(data.show_abs=="true"){
                    let right_count = data.rightcount;
                    if(right_count < 0){
                        right_count++;
                    }
                    document.getElementById('rightanwsernum').innerHTML=right_count+" / "+this.totalStudents+" all correct";
                    // document.getElementById('rightanwsernum').style.visibility="hidden";
                }
                questionid=data.question_id;
                var wordFreqData = data.shortanswertags1;
                console.log(wordFreqData);
                console.log(wordFreqData.length);
                if((!this.showResponses && this.quiz.state !== 'reviewing') || !wordFreqData.length){
                    return;
                }
                var options = eval({
                    "list": wordFreqData,
                    "gridSize": 6, // Intensity
                    "weightFactor": 1, // Font Size
                    "maxFontSize": 50, //Max
                    "minFontSize": 14, //Min
                    "fontWeight": 'normal', //Font weight
                    "fontFamily": 'Times, serif', // Font
                    "color": 'random-light', // Font Color
                    "backgroundColor": '#333', // 背景颜色
                    "rotateRatio": 1, // Font Tilt
                    "drawOutOfBound ":true,
                    "shrinkToFit":true
                });
                var canvasid="wordclound_"+data.question_id;
                if (document.getElementById(canvasid) === null) {
                    var canvas = document.createElement("div");// create div
                    canvas.id =canvasid;
                    canvas.style.height="400px";
                    canvas.style.width="600px";
                    canvas.style.margin="0 auto";
                    $("#swiftquiz_responses_container").append(canvas);
                }else {
                    canvas = document.getElementById(canvasid);
                }
                WordCloud(canvas, options); //generate

            });
        }

        /**
         * refresh() equivalent for votes.
         */
        refreshVotes() {
            // Should we show the results?
            if (!this.showResponses && this.quiz.state !== 'reviewing') {
                Quiz.hide(Quiz.responseInfo);
                Quiz.hide(Quiz.responses);
                return;
            }
            Ajax.get('get_vote_results', {}, data => {
                const answers = data.answers;
                const targetId = 'wrapper_vote_responses';
                let responses = [];

                this.respondedCount = 0;
                this.totalStudents = parseInt(data.total_students);

                for (let i in answers) {
                    if (!answers.hasOwnProperty(i)) {
                        continue;
                    }
                    responses.push({
                        response: answers[i].attempt,
                        count: answers[i].finalcount,
                        qtype: answers[i].qtype,
                        slot: answers[i].slot
                    });
                    this.respondedCount += parseInt(answers[i].finalcount);
                }

                setText(Quiz.responded.find('h4'), 'a_out_of_b_voted', 'swiftquiz', {
                    a: this.respondedCount,
                    b: this.totalStudents
                });

                if (document.getElementById(targetId) === null) {
                    const html = '<table id="' + targetId + '" class="swiftquiz-responses-overview"></table>';
                    Quiz.show(Quiz.responses.html(html));
                }

                this.createBarGraph(responses, 'vote_response', targetId, 'vote', false);
                ResponseView.sortBarGraph(targetId);
            });
        }

    }

    class Instructor {

        /**
         * @param {Quiz} quiz
         */
        constructor(quiz) {
            this.quiz = quiz;
            this.responses = new ResponseView(quiz);
            this.isShowingCorrectAnswer = false;
            this.totalQuestions = 0;
            this.allowVote = false;

            $(document).on('keyup', event => {
                if (event.keyCode === 27) { // Escape key.
                    Instructor.closeFullscreenView();
                }
            });

            $(document).on('click', event => {
                Instructor.closeQuestionListMenu(event, 'improvise');
                Instructor.closeQuestionListMenu(event, 'jump');
            });

            Instructor.addEvents({
                'repoll': () => this.repollQuestion(),
                'vote': () => this.runVoting(),
                'improvise': () => this.showQuestionListSetup('improvise'),
                'jump': () => this.showQuestionListSetup('jump'),
                'next': () => this.nextQuestion(),
                'end': () => this.endQuestion(),
                'triggerBtn': () => this.test(),
                'fullscreen': () => Instructor.showFullscreenView(),
                'answer': () => this.showCorrectAnswer(),
                'responses': () => this.responses.toggle(),
                'exit': () => this.closeSession(),
                'quit': () => this.closeSession(),
                'startquiz': () => this.startQuiz(),
                'show_word_cloud': () => this.responses.toggleWC(), // show&hide wordCloud
                'show_statistic': () => this.responses.toggleStat(),
                'toggle_test': () => this.responses.toggleTest()
            });

            Instructor.addHotkeys({
                't': 'responses',
                'r': 'repoll',
                'a': 'answer',
                'e': 'end',
                'j': 'jump',
                'i': 'improvise',
                'v': 'vote',
                'n': 'next',
                'f': 'fullscreen'
            });
        }

        static addHotkeys(keys) {
            for (let key in keys) {
                if (keys.hasOwnProperty(key)) {
                    keys[key] = {
                        action: keys[key],
                        repeat: false // TODO: Maybe event.repeat becomes more standard?
                    };
                    $(document).on('keydown', event => {
                        if (keys[key].repeat || event.ctrlKey) {
                            return;
                        }
                        if (String.fromCharCode(event.which).toLowerCase() !== key) {
                            return;
                        }
                        let focusedTag = $(':focus').prop('tagName');
                        if (focusedTag !== undefined) {
                            focusedTag = focusedTag.toLowerCase();
                            if (focusedTag === 'input' || focusedTag === 'textarea') {
                                return;
                            }
                        }
                        event.preventDefault();
                        keys[key].repeat = true;
                        let $control = Instructor.control(keys[key].action);
                        if ($control.length && !$control.prop('disabled')) {
                            $control.click();
                        }
                    });
                    $(document).on('keyup', event => {
                        if (String.fromCharCode(event.which).toLowerCase() === key) {
                            keys[key].repeat = false;
                        }
                    });
                }
            }
        }

        static addEvents(events) {
            for (let key in events) {
                if (events.hasOwnProperty(key)) {
                    $(document).on('click', '#swiftquiz_control_' + key, () => {
                        Instructor.enableControls([]);
                        events[key]();
                    });
                }
            }
        }

        static get controls() {
            return $('#swiftquiz_controls_box');
        }

        static get controlButtons() {
            return Instructor.controls.find('.quiz-control-buttons');
        }

        static control(key) {
            return $('#swiftquiz_control_' + key);
        }

        static get side() {
            return $('#swiftquiz_side_container');
        }

        static get correctAnswer() {
            return $('#swiftquiz_correct_answer_container');
        }

        static get isMerging() {
            return $('.merge-from').length !== 0;
        }

        onNotRunning(data) {
            this.responses.totalStudents = data.student_count;
            Quiz.hide(Instructor.side);
            setText(Quiz.info, 'instructions_for_instructor');
            Instructor.enableControls([]);
            Quiz.hide(Instructor.controlButtons);
            let $studentsJoined = Instructor.control('startquiz').next();
            if (data.student_count === 1) {
                setText($studentsJoined, 'one_student_has_joined');
            } else if (data.student_count > 1) {
                setText($studentsJoined, 'x_students_have_joined', 'swiftquiz', data.student_count);
            } else {
                setText($studentsJoined, 'no_students_have_joined');
            }
            Quiz.show(Instructor.control('startquiz').parent());
        }

        onPreparing(data) {
            Quiz.hide(Instructor.side);
            setText(Quiz.info, 'instructions_for_instructor');
            let enabledButtons = ['improvise', 'triggerBtn', 'jump', 'fullscreen', 'quit', 'toggle_test'];
            if (data.slot < this.totalQuestions) {
                enabledButtons.push('next');
            }
            Instructor.enableControls(enabledButtons);
        }

        onRunning(data) {
            if (!this.responses.showResponses) {
                this.responses.hide();
            }
            Quiz.show(Instructor.side);
            Instructor.enableControls(['end', 'responses', 'fullscreen',  'toggle_test']);
            this.quiz.question.questionTime = data.questiontime;
            if (this.quiz.question.isRunning) {
                // Check if the question has already ended.
                // We need to do this because the state does not update unless an instructor is connected.
                if (data.questionTime > 0 && data.delay < -data.questiontime) {
                    this.endQuestion();
                }
                // Only rebuild results if we are not merging.
                this.responses.refresh(!Instructor.isMerging);
            } else {
                const started = this.quiz.question.startCountdown(data.questiontime, data.delay);
                if (started) {
                    this.quiz.question.isRunning = true;
                }
            }
        }

        onReviewing(data) {
            Quiz.show(Instructor.side);
            let enabledButtons = ['answer', 'repoll', 'fullscreen', 'improvise', 'triggerBtn', 'jump', 'quit', 'show_word_cloud', 'show_statistic', 'toggle_test'];
            if (this.allowVote) {
                enabledButtons.push('vote');
            }
            if (data.slot < this.totalQuestions) {
                enabledButtons.push('next');
            }
            Instructor.enableControls(enabledButtons);

            // In case page was refreshed, we should ensure the question is showing.
            if (!Question.isLoaded()) {
                this.quiz.question.refresh();
            }

            // For now, just always show responses while reviewing.
            // In the future, there should be an additional toggle.
            if (this.quiz.isNewState) {
                this.responses.show();
            }
            // No longer in question.
            this.quiz.question.isRunning = false;
        }

        onSessionClosed(data) {
            Quiz.hide(Instructor.side);
            Quiz.hide(Instructor.correctAnswer);
            Instructor.enableControls([]);
            this.responses.clear();
            this.quiz.question.isRunning = false;
        }

        onVoting(data) {
            if (!this.responses.showResponses) {
                this.responses.hide();
            }
            Quiz.show(Instructor.side);
            Instructor.enableControls(['quit', 'fullscreen', 'answer', 'responses', 'end', 'toggle_test']);
            this.responses.refreshVotes();
        }

        onStateChange(state) {
            $('#region-main').find('ul.nav.nav-tabs').css('display', 'none');
            $('#region-main-settings-menu').css('display', 'none');
            $('.region_main_settings_menu_proxy').css('display', 'none');
            Quiz.show(Instructor.controlButtons);
            Quiz.hide(Instructor.control('startquiz').parent());
        }

        onQuestionRefreshed(data) {
            this.allowVote = data.voteable;
        }

        onTimerEnding() {
            this.endQuestion();
        }

        onTimerTick(timeLeft) {
            setText(Question.timer, 'x_seconds_left', 'swiftquiz', timeLeft);
        }

        /**
         * Start the quiz. Does not start any questions.
         */
        startQuiz() {
            // document.getElementById("swiftquiz_control_show_word_cloud").style.visibility='hidden';
            // document.getElementById("swiftquiz_control_show_word_cloud").style.visibility='hidden';
            Quiz.hide(Instructor.control('startquiz').parent());
            Ajax.post('start_quiz', {}, () => $('#swiftquiz_controls').removeClass('btn-hide'));
        }

        /**
         * End the currently ongoing question or vote.
         */
        endQuestion() {
            document.getElementById("swiftquiz_control_show_word_cloud").style.visibility='visible';
            document.getElementById("swiftquiz_control_toggle_test").style.visibility='visible';
            document.getElementById("swiftquiz_control_show_statistic").style.visibility='visible';
            document.getElementById("rightanwsernum").style.visibility='visible';
            this.quiz.question.hideTimer();
            Ajax.post('end_question', {}, () => {
                if (this.quiz.state === 'voting') {
                    this.responses.showVotesUponReview = true;
                } else {
                    this.quiz.question.isRunning = false;
                    Instructor.enableControls([]);
                }
            });
        }

        /**
         *test
         */
        test() {
            // Ajax.post('test', {}, () => {});
        }

        /**
         * Show a question list dropdown.
         * @param {string} name
         */
        showQuestionListSetup(name) {
            let $controlButton = Instructor.control(name);
            if ($controlButton.hasClass('active')) {
                // It's already open. Let's not send another request.
                return;
            }
            Ajax.get('list_' + name + '_questions', {}, data => {
                let self = this;
                let $menu = $('#swiftquiz_' + name + '_menu');
                const menuMargin = $controlButton.offset().left - $controlButton.parent().offset().left;
                $menu.html('').addClass('active').css('margin-left', menuMargin + 'px');
                $controlButton.addClass('active');
                const questions = data.questions;
                for (let i in questions) {
                    if (!questions.hasOwnProperty(i)) {
                        continue;
                    }
                    let $questionButton = $('<button class="btn"></button>');
                    Quiz.addMathjaxElement($questionButton, questions[i].name);
                    $questionButton.data({
                        'time': questions[i].time,
                        'question-id': questions[i].questionid,
                        'swiftquiz-question-id': questions[i].swiftquizquestionid
                    });
                    $questionButton.data('test', 1);
                    $questionButton.on('click', function () {
                        const questionId = $(this).data('question-id');
                        const time = $(this).data('time');
                        const swiftQuestionId = $(this).data('swiftquiz-question-id');
                        self.jumpQuestion(questionId, time, swiftQuestionId, name);
                        $menu.html('').removeClass('active');
                        $controlButton.removeClass('active');
                    });
                    $menu.append($questionButton);
                }
            });
        }

        /**
         * Get the selected responses.
         * @returns {Array.<Object>} Vote options
         */
        static getSelectedAnswersForVote() {
            let result = [];
            $('.selected-vote-option').each((i, option) => {
                result.push({
                    text: option.dataset.response,
                    count: option.dataset.count
                });
            });
            return result;
        }

        /**
         * Start a vote with the responses that are currently selected.
         */
        runVoting() {
            const options = Instructor.getSelectedAnswersForVote();
            const data = {questions: encodeURIComponent(JSON.stringify(options))};
            Ajax.post('run_voting', data, () => {});
        }

        /**
         * Start a new question in this session.
         * @param {string} method
         * @param {number} questionId
         * @param {number} questionTime
         * @param {number} swiftquizQuestionId
         */
        startQuestion(method, questionId, questionTime, swiftquizQuestionId) {
            Quiz.hide(Quiz.info);
            this.responses.clear();
            this.hideCorrectAnswer();
            Ajax.post('start_question', {
                method: method,
                questionid: questionId,
                questiontime: questionTime,
                swiftquizquestionid: swiftquizQuestionId
            }, data => this.quiz.question.startCountdown(data.questiontime, data.delay));
        }

        /**
         * Jump to a planned question in the quiz.
         * @param {number} questionId
         * @param {number} questionTime
         * @param {number} swiftquizQuestionId
         * @param {string} name
         */
        jumpQuestion(questionId, questionTime, swiftquizQuestionId, name) {
            this.startQuestion(name, questionId, questionTime, swiftquizQuestionId);
        }

        /**
         * Repoll the previously asked question.
         */
        repollQuestion() {
            this.startQuestion('repoll', 0, 0, 0);
        }

        /**
         * Continue on to the next preplanned question.
         */
        nextQuestion() {
            document.getElementById("swiftquiz_control_show_word_cloud").style.visibility='hidden';
            document.getElementById("swiftquiz_control_toggle_test").style.visibility='hidden';
            document.getElementById("swiftquiz_control_show_statistic").style.visibility='hidden';
            document.getElementById('rightanwsernum').style.visibility="hidden";
            this.startQuestion('next', 0, 0, 0);
        }

        /**
         * Close the current session.
         */
        closeSession() {
            Quiz.hide($('#swiftquiz_undo_merge'));
            Quiz.hide(Question.box);
            Quiz.hide(Instructor.controls);
            setText(Quiz.info, 'closing_session');
            Ajax.post('close_session', {}, () => window.location = location.href.split('&')[0]);
        }

        /**
         * Hide the correct answer if showing.
         */
        hideCorrectAnswer() {
            if (this.isShowingCorrectAnswer) {
                Quiz.hide(Instructor.correctAnswer);
                Quiz.uncheck(Instructor.control('answer'));
                this.isShowingCorrectAnswer = false;
            }
        }

        /**
         * Request and show the correct answer for the ongoing or previous question.
         */
        showCorrectAnswer() {
            this.hideCorrectAnswer();
            Ajax.get('get_right_response', {}, data => {
                Quiz.show(Instructor.correctAnswer.html(data.right_answer));
                Quiz.renderAllMathjax();
                Quiz.check(Instructor.control('answer'));
                this.isShowingCorrectAnswer = true;
            });
        }

        /**
         * Enables all buttons passed in arguments, but disables all others.
         * @param {Array.<string>} buttons The unique part of the IDs of the buttons to be enabled.
         */
        static enableControls(buttons) {
            Instructor.controlButtons.children('button').each((index, child) => {
                const id = child.getAttribute('id').replace('swiftquiz_control_', '');
                child.disabled = (buttons.indexOf(id) === -1);
            });
        }

        /**
         * Enter fullscreen mode for better use with projectors.
         */
        static showFullscreenView() {
            if (Quiz.main.hasClass('swiftquiz-fullscreen')) {
                Instructor.closeFullscreenView();
                return;
            }
            // Hide the scrollbar - remember to always set back to auto when closing.
            document.documentElement.style.overflowY = 'hidden';
            // Sets the quiz view to an absolute position that covers the viewport.
            Quiz.main.addClass('swiftquiz-fullscreen');
        }

        /**
         * Exit the fullscreen mode.
         */
        static closeFullscreenView() {
            document.documentElement.style.overflowY = 'auto';
            Quiz.main.removeClass('swiftquiz-fullscreen');
        }

        /**
         * Close the dropdown menu for choosing a question.
         * @param {Event} event
         * @param {string} name
         */
        static closeQuestionListMenu(event, name) {
            const menuId = '#swiftquiz_' + name + '_menu';
            // Close the menu if the click was not inside.
            const menu = $(event.target).closest(menuId);
            if (!menu.length) {
                $(menuId).html('').removeClass('active');
                Instructor.control(name).removeClass('active');
            }
        }

        static addReportEventHandlers() {
            $(document).on('click', '#report_overview_controls button', function () {
                const action = $(this).data('action');
                if (action === 'attendance') {
                    $('#report_overview_responded').fadeIn();
                    $('#report_overview_responses').fadeOut();
                } else if (action === 'responses') {
                    $('#report_overview_responses').fadeIn();
                    $('#report_overview_responded').fadeOut();

                    var shoranwserslots=$(this).data('shortanwserslots');
                    //console.log(shoranwserslots);
                    //generate word cloud
                    shoranwserslots.forEach(slot => {
                        $('#rightcount_'+slot.num).hide();
                        var wordFreqData = slot.tags;
                        console.log(wordFreqData);
                        if(!wordFreqData.length){
                            return;
                        }
                        var options = eval({
                            "list": wordFreqData,
                            "gridSize": 8, // Intensity
                            "weightFactor": 1, // Font Size
                            "maxFontSize": 50, //Max
                            "minFontSize": 14, //Min
                            "fontWeight": 'normal', //Font weight
                            "fontFamily": 'Times, serif', // Font
                            "color": 'random-light', // Font Color
                            "backgroundColor": '#333', // Background Color
                            "rotateRatio": 1 ,// Font Tilt
                            "drawOutOfBound ":true,
                            "shrinkToFit":true
                        });
                        var canvas = document.getElementById("wordclound_"+slot.num);
                        canvas.style.height="400px";
                        canvas.style.width="600px";
                        canvas.style.margin="0 auto";
                        WordCloud(canvas, options); //generate

                    });

                }
            });
        }

    }

    return {
        initialize: function (totalQuestions, reportView, slots) {
            let quiz = new Quiz(Instructor);
            quiz.role.totalQuestions = totalQuestions;
            if (reportView) {
                Instructor.addReportEventHandlers();
                quiz.role.responses.showResponses = true;
                slots.forEach(slot => {
                    const wrapper = 'swiftquiz_wrapper_responses_' + slot.num;
                    const table = 'responses_wrapper_table_' + slot.num;
                    const graph = 'report_' + slot.num;
                    quiz.role.responses.set(wrapper, table, slot.responses, undefined, slot.type, graph, false);
                });
            } else {
                quiz.poll(500);
            }
        }
    }

});


// (function() {
//     /*建立模态框对象*/
//     var modalBox = {};
//     /*获取模态框*/
//     modalBox.modal = document.getElementById("myModal");
//     /*获得trigger按钮*/
//     modalBox.triggerBtn = document.getElementById("swiftquiz_control_triggerBtn");
//     /*获得关闭按钮*/
//     modalBox.closeBtn = document.getElementById("closeBtn");
//     /*模态框显示*/
//     modalBox.show = function() {
//         console.log(this.modal);
//         this.modal.style.display = "block";
//     }
//     /*模态框关闭*/
//     modalBox.close = function() {
//         this.modal.style.display = "none";
//     }
//     /*当用户点击模态框内容之外的区域，模态框也会关闭*/
//     modalBox.outsideClick = function() {
//         var modal = this.modal;
//         window.onclick = function(event) {
//             if(event.target == modal) {
//                 modal.style.display = "none";
//             }
//         }
//     }
//     /*模态框初始化*/
//     modalBox.init = function() {
//         var that = this;
//         this.triggerBtn.onclick = function() {
//             that.show();
//         }
//         this.closeBtn.onclick = function() {
//             that.close();
//         }
//         this.outsideClick();
//     }
//     modalBox.init();
//
// })();