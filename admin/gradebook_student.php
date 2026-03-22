<?php
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($course_id <= 0 || $user_id <= 0) {
    header('Location: gradebook.php');
    exit;
}

$gradebook = getUserCourseGradebook($user_id, $course_id);
$student = getUser($user_id);

if (!$gradebook || !$student) {
    header('Location: gradebook.php?course_id=' . $course_id);
    exit;
}

$title = 'Student Gradebook - ' . htmlspecialchars($student['username']);
$content = '<h2>' . $title . '</h2>';
$content .= '<p><a href="gradebook.php?course_id=' . $course_id . '" class="btn btn-secondary btn-sm">Back to Course Gradebook</a></p>';
$content .= '<p><strong>Course:</strong> ' . htmlspecialchars($gradebook['course']['title']) . '<br><strong>Student:</strong> ' . htmlspecialchars($student['username']) . ' (' . htmlspecialchars($student['email']) . ')</p>';
$content .= '<div class="row mb-4">';
$content .= '<div class="col-md-4"><div class="card"><div class="card-body"><h5 class="card-title">Lesson Progress</h5><p>' . (int)$gradebook['summary']['completed_lessons'] . ' / ' . (int)$gradebook['summary']['total_lessons'] . '</p><p>' . htmlspecialchars((string)$gradebook['summary']['lesson_progress_percent']) . '% complete</p></div></div></div>';
$content .= '<div class="col-md-4"><div class="card"><div class="card-body"><h5 class="card-title">Quizzes Passed</h5><p>' . (int)$gradebook['summary']['quizzes_passed'] . ' / ' . (int)$gradebook['summary']['total_quizzes'] . '</p></div></div></div>';
$content .= '<div class="col-md-4"><div class="card"><div class="card-body"><h5 class="card-title">Best Quiz Average</h5><p>' . ($gradebook['summary']['quiz_average_percent'] !== null ? htmlspecialchars((string)$gradebook['summary']['quiz_average_percent']) . '%' : 'No attempts yet') . '</p></div></div></div>';
$content .= '</div>';

$content .= '<div class="table-responsive"><table class="table table-striped">';
$content .= '<thead><tr><th>Lesson</th><th>Completed</th><th>Quiz</th><th>Best Score</th><th>Latest Score</th><th>Attempts</th><th>Status</th></tr></thead><tbody>';
foreach ($gradebook['lessons'] as $lesson) {
    $content .= '<tr>';
    $content .= '<td>' . htmlspecialchars($lesson['lesson_title']) . '</td>';
    $content .= '<td>' . ($lesson['completed'] ? '<span class="text-success">Yes</span>' : '<span class="text-warning">No</span>') . '</td>';
    $content .= '<td>' . htmlspecialchars($lesson['quiz_title'] ?? 'No quiz') . '</td>';
    $content .= '<td>' . ($lesson['quiz_best_percentage'] !== null ? htmlspecialchars((string)$lesson['quiz_best_percentage']) . '%' : '-') . '</td>';
    $content .= '<td>' . ($lesson['quiz_latest_percentage'] !== null ? htmlspecialchars((string)$lesson['quiz_latest_percentage']) . '%' : '-') . '</td>';
    $content .= '<td>' . (int)$lesson['quiz_attempts'] . '</td>';
    $content .= '<td>';
    if ($lesson['quiz_id']) {
        $content .= $lesson['quiz_passed'] ? '<span class="text-success">Passed</span>' : '<span class="text-warning">In progress</span>';
    } else {
        $content .= '<span class="text-muted">N/A</span>';
    }
    $content .= '</td>';
    $content .= '</tr>';
}
$content .= '</tbody></table></div>';

include '../includes/header.php';
?>
