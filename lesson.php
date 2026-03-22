<?php
require_once 'includes/functions.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: courses.php');
    exit;
}

$lesson_id = (int)$_GET['id'];

// Get lesson details (assuming lesson table has course_id)
global $pdo;
$stmt = $pdo->prepare("SELECT l.*, c.title as course_title FROM lessons l JOIN courses c ON l.course_id = c.id WHERE l.id = ?");
$stmt->execute([$lesson_id]);
$lesson = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$lesson) {
    $title = 'Lesson Not Found';
    $content = '<p>Lesson not found.</p>';
} else {
    $title = htmlspecialchars($lesson['title']);
    $content = '<h2>' . $title . '</h2>';
    $content .= '<p>Course: ' . htmlspecialchars($lesson['course_title']) . '</p>';
    // Lesson content is sanitized at save; render as HTML
    $content .= '<div>' . $lesson['content'] . '</div>';

    $quiz = getQuizByLesson($lesson_id);
    if ($quiz) {
        $content .= '<hr>';
        $content .= '<section class="mt-4">';
        $content .= '<h3>' . htmlspecialchars($quiz['title']) . '</h3>';
        if (!empty($quiz['description'])) {
            $content .= '<p>' . nl2br(htmlspecialchars($quiz['description'])) . '</p>';
        }

        if (isLoggedIn()) {
            $latestAttempt = getQuizLatestAttempt($_SESSION['user_id'], $quiz['id']);
            $attemptCount = getQuizAttemptCount($_SESSION['user_id'], $quiz['id']);
            $remainingAttempts = empty($quiz['max_attempts']) ? null : ((int)$quiz['max_attempts'] - $attemptCount);

            if ($latestAttempt) {
                $content .= '<p><strong>Latest attempt:</strong> ' . htmlspecialchars((string)$latestAttempt['percentage']) . '% (' . ((int)$latestAttempt['passed'] === 1 ? 'Passed' : 'Not passed') . ')</p>';
            } else {
                $content .= '<p>No attempts yet.</p>';
            }

            $content .= '<p><strong>Passing score:</strong> ' . (int)$quiz['passing_score'] . '%';
            if (!empty($quiz['max_attempts'])) {
                $content .= ' | <strong>Attempts remaining:</strong> ' . max(0, $remainingAttempts);
            }
            $content .= '</p>';

            if (canUserAttemptQuiz($_SESSION['user_id'], $quiz)) {
                $content .= '<p><a href="quiz.php?id=' . $quiz['id'] . '" class="btn btn-primary">Take Quiz</a></p>';
            } else {
                $content .= '<p class="text-warning">You have used all available attempts for this quiz.</p>';
            }
        } else {
            $content .= '<p><a href="login.php" class="btn btn-primary">Log In to Take Quiz</a></p>';
        }
        $content .= '</section>';
    }

    if (isLoggedIn()) {
        $progress = getUserProgress($_SESSION['user_id'], $lesson_id);
        if (!$progress || !$progress['completed']) {
            $content .= '<form method="post" action="mark_complete.php">';
            $content .= '<input type="hidden" name="lesson_id" value="' . $lesson_id . '">';
            $content .= '<button type="submit" class="btn btn-success mt-3">Mark as Complete</button>';
            $content .= '</form>';
        } else {
            $content .= '<p class="text-success mt-3">Lesson completed!</p>';
        }
    }
}

include 'includes/header.php';
?>
