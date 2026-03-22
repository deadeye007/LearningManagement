<?php
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$quiz_id = isset($_GET['quiz_id']) ? (int)$_GET['quiz_id'] : 0;
$quiz = $quiz_id > 0 ? getQuiz($quiz_id, true) : null;

if (!$quiz) {
    header('Location: courses.php');
    exit;
}

if (isset($_GET['delete_question']) && is_numeric($_GET['delete_question'])) {
    deleteQuizQuestion((int)$_GET['delete_question'], $quiz_id);
    header('Location: quiz_questions.php?quiz_id=' . $quiz_id);
    exit;
}

$questions = getQuizQuestionsWithAnswers($quiz_id);

$title = 'Quiz Questions - ' . htmlspecialchars($quiz['title']);
$content = '<h2>' . $title . '</h2>';
$content .= '<p><a href="quiz_edit.php?lesson_id=' . $quiz['lesson_id'] . '" class="btn btn-secondary btn-sm">Back to Quiz Settings</a> <a href="quiz_question_edit.php?quiz_id=' . $quiz_id . '" class="btn btn-success btn-sm">Add Question</a></p>';
$content .= '<p><strong>Lesson:</strong> ' . htmlspecialchars($quiz['lesson_title']) . '<br><strong>Status:</strong> ' . ((int)$quiz['is_published'] === 1 ? '<span class="text-success">Published</span>' : '<span class="text-warning">Draft</span>') . '</p>';

if ($questions) {
    $content .= '<div class="table-responsive"><table class="table table-striped">';
    $content .= '<thead><tr><th>Order</th><th>Question</th><th>Points</th><th>Answers</th><th>Actions</th></tr></thead><tbody>';
    foreach ($questions as $question) {
        $answerSummary = [];
        foreach ($question['answers'] as $answer) {
            $answerSummary[] = htmlspecialchars($answer['answer_text']) . ((int)$answer['is_correct'] === 1 ? ' <strong>(correct)</strong>' : '');
        }

        $content .= '<tr>';
        $content .= '<td>' . (int)$question['order_num'] . '</td>';
        $content .= '<td>' . nl2br(htmlspecialchars($question['question_text'])) . '</td>';
        $content .= '<td>' . (int)$question['points'] . '</td>';
        $content .= '<td>' . implode('<br>', $answerSummary) . '</td>';
        $content .= '<td><a href="quiz_question_edit.php?quiz_id=' . $quiz_id . '&id=' . $question['id'] . '" class="btn btn-sm btn-primary">Edit</a> <a href="quiz_questions.php?quiz_id=' . $quiz_id . '&delete_question=' . $question['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Delete this question?\')">Delete</a></td>';
        $content .= '</tr>';
    }
    $content .= '</tbody></table></div>';
} else {
    $content .= '<p>No questions yet. Add your first question to make this quiz usable.</p>';
}

include '../includes/header.php';
?>
