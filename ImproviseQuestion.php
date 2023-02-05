<!-- MVC -->
<!-- Model -->
<?php
require_once('../../config.php');
require_once($CFG->dirroot . '/mod/swiftquiz/lib.php');
require_once($CFG->dirroot . '/mod/swiftquiz/locallib.php');
require_once($CFG->dirroot . '/question/editlib.php');
$flag = false;
$feedback = "";
$text = '';
$name = optional_param('name', '', PARAM_TEXT);
$question = optional_param('question', '', PARAM_TEXT);
$answer1 =optional_param('answer1', '', PARAM_TEXT);
$answer2 =optional_param('answer2', '', PARAM_TEXT);
$answer3 =optional_param('answer3', '', PARAM_TEXT);
if ($name != '' && $question != ''  && $answer1 != ''  && $answwer2 != ''  && $answer3 != '' ) {
    $feedback = "Successful submission";
    $flag = true;
}
?>

<!-- View -->
<html lang="en">
<head>
    <title>QuestionTest</title>
</head>
<body>
<form method="post">
    <p>
        <label for="name">Name:</label>
        <input type="text" name="name" id="name" size="50">
        <br>
        <label for="question">Question:</label>
        <input type="text" name="question" id="question" size="50">
        <br>
        <label for="answer1">Option A:</label>
        <input type="text" name="answer1" id="answer1" size="20">
        <br>
        <label for="answer2">Option B:</label>
        <input type="text" name="answer2" id="answer2" size="20">
        <br>
        <label for="answer3">Option c:</label>
        <input type="text" name="answer3" id="answer3" size="20">
        <br>
        <input type="submit">
</form>
<?php
   $name = optional_param('name', '', PARAM_TEXT);
   $question = optional_param('question', '', PARAM_TEXT);
   $answer1 =optional_param('answer1', '', PARAM_TEXT);
   $answer2 =optional_param('answer2', '', PARAM_TEXT);
   $answer3 =optional_param('answer3', '', PARAM_TEXT);
   $answer = [];
   $answer[] = $answer1;
   $answer[] = $answer2;
   $answer[] = $answer3;
//    require_once ('ajax.php');
    $go = 1;
?>
</body>
</html>