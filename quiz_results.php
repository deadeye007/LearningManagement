<?php
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$attempt_id = isset($_GET['attempt_id']) ? (int)$_GET['attempt_id'] : 0;
$attempt = $attempt_id > 0 ? getQuizAttemptById($attempt_id, $_SESSION['user_id']) : null;

if (!$attempt) {
    $title = 'Quiz Result Not Found';
    $content = '<p>Quiz attempt not found.</p>';
    include 'includes/header.php';
    exit;
}

$responses = getQuizAttemptResponses($attempt_id);
$quiz = getQuiz($attempt['quiz_id']);

$title = 'Quiz Results';
$content = '<h2>Quiz Results</h2>';
$content .= '<p><a href="lesson.php?id=' . $attempt['lesson_id'] . '" class="btn btn-secondary btn-sm">Back to Lesson</a>';
if ($quiz && canUserAttemptQuiz($_SESSION['user_id'], $quiz)) {
    $content .= ' <a href="quiz.php?id=' . $attempt['quiz_id'] . '" class="btn btn-outline-primary btn-sm">Retake Quiz</a>';
}
$content .= '</p>';
$content .= '<div class="card mb-4"><div class="card-body">';
$content .= '<h4 class="card-title">' . htmlspecialchars($attempt['quiz_title']) . '</h4>';
$content .= '<p><strong>Score:</strong> ' . (int)$attempt['score'] . ' / ' . (int)$attempt['max_score'] . '<br>';
$content .= '<strong>Percentage:</strong> ' . htmlspecialchars((string)$attempt['percentage']) . '%<br>';
$content .= '<strong>Status:</strong> ' . ((int)$attempt['passed'] === 1 ? '<span class="text-success">Passed</span>' : '<span class="text-danger">Did not pass</span>') . '</p>';
$content .= '</div></div>';

if ($responses) {
    foreach ($responses as $index => $response) {
        $content .= '<div class="card mb-3"><div class="card-body">';
        $content .= '<h5 class="card-title">Question ' . ($index + 1) . '</h5>';
        $content .= '<p>' . nl2br(htmlspecialchars($response['question_text'])) . '</p>';
        $content .= '<p><strong>Your answer:</strong> ' . htmlspecialchars($response['selected_answer_text'] ?? 'No answer selected') . '</p>';
        if ((int)$response['is_correct'] !== 1) {
            $content .= '<p><strong>Correct answer:</strong> ' . htmlspecialchars($response['correct_answer_text'] ?? '') . '</p>';
        }
        $content .= '<p><strong>Points earned:</strong> ' . (int)$response['points_awarded'] . ' / ' . (int)$response['points'] . '</p>';
        $content .= '</div></div>';
    }
}

?>
