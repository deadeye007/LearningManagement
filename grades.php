<?php
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$courses = getCourses();
$title = 'My Grades';
$content = '<h2>My Grades</h2>';
$content .= '<p>Review your lesson progress and quiz performance by course.</p>';

if ($courses) {
    foreach ($courses as $course) {
        $gradebook = getUserCourseGradebook($_SESSION['user_id'], (int)$course['id']);
        if (!$gradebook) {
            continue;
        }

        $summary = $gradebook['summary'];
        $content .= '<section class="card mb-4"><div class="card-body">';
        $content .= '<h3 class="card-title">' . htmlspecialchars($course['title']) . '</h3>';
        $content .= '<p><strong>Lesson Progress:</strong> ' . (int)$summary['completed_lessons'] . ' / ' . (int)$summary['total_lessons'] . ' (' . htmlspecialchars((string)$summary['lesson_progress_percent']) . '%)</p>';
        $content .= '<p><strong>Quizzes Passed:</strong> ' . (int)$summary['quizzes_passed'] . ' / ' . (int)$summary['total_quizzes'] . '</p>';
        $content .= '<p><strong>Best Quiz Average:</strong> ' . ($summary['quiz_average_percent'] !== null ? htmlspecialchars((string)$summary['quiz_average_percent']) . '%' : 'No quiz attempts yet') . '</p>';

        $content .= '<div class="table-responsive"><table class="table table-striped">';
        $content .= '<thead><tr><th>Lesson</th><th>Completed</th><th>Quiz</th><th>Best Score</th><th>Latest Score</th><th>Attempts</th></tr></thead><tbody>';
        foreach ($gradebook['lessons'] as $lesson) {
            $content .= '<tr>';
            $content .= '<td>' . htmlspecialchars($lesson['lesson_title']) . '</td>';
            $content .= '<td>' . ($lesson['completed'] ? '<span class="text-success">Yes</span>' : '<span class="text-warning">No</span>') . '</td>';
            $content .= '<td>' . htmlspecialchars($lesson['quiz_title'] ?? 'No quiz') . '</td>';
            $content .= '<td>' . ($lesson['quiz_best_percentage'] !== null ? htmlspecialchars((string)$lesson['quiz_best_percentage']) . '%' : '-') . '</td>';
            $content .= '<td>' . ($lesson['quiz_latest_percentage'] !== null ? htmlspecialchars((string)$lesson['quiz_latest_percentage']) . '%' : '-') . '</td>';
            $content .= '<td>' . (int)$lesson['quiz_attempts'] . '</td>';
            $content .= '</tr>';
        }
        $content .= '</tbody></table></div>';
        $content .= '<p><a href="course.php?id=' . $course['id'] . '" class="btn btn-outline-primary">Open Course</a></p>';
        $content .= '</div></section>';
    }
} else {
    $content .= '<p>No courses available yet.</p>';
}

include 'includes/header.php';
?>
