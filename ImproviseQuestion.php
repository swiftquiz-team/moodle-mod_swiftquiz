<!-- MVC -->
<!-- Model -->
<?php
$flag = false;
$feedback = "";
$text = '';
if (isset($_POST['name']) && isset($_POST["question"]) && isset($_POST['answer1']) && isset($_POST['answer2']) && isset($_POST['answer3'])) {
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
    $name = $_POST['name'];
    $question = $_POST['question'];
    $answer = [];
    $answer[] = $_POST['answer1'];
    $answer[] = $_POST['answer2'];
    $answer[] = $_POST['answer3'];
    $go = 1;
?>
</body>
</html>