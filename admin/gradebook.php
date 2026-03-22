<?php
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$course_id = isset($_GET['course_id']) ? (int)$_GET['course_id'] : 0;

if ($course_id > 0) {
    $gradebook = getCourseGradebook($course_id);
    if (!$gradebook) {
        header('Location: gradebook.php');
        exit;
    }

    $title = 'Gradebook - ' . htmlspecialchars($gradebook['course']['title']);
    $content = '<h2>' . $title . '</h2>';
    $content .= '<p><a href="gradebook.php" class="btn btn-secondary btn-sm">Back to Course Gradebooks</a> <a href="courses.php" class="btn btn-outline-secondary btn-sm">Course Manager</a></p>';

    if ($gradebook['students']) {
        $content .= '<div class="table-responsive"><table class="table table-striped">';
        $content .= '<thead><tr><th>Student</th><th>Email</th><th>Lessons</th><th>Lesson Progress</th><th>Quizzes Passed</th><th>Best Quiz Avg</th><th>Actions</th></tr></thead><tbody>';
        foreach ($gradebook['students'] as $student) {
            $summary = $student['summary'];
            $quizAverage = $summary['quiz_average_percent'] !== null ? $summary['quiz_average_percent'] . '%' : 'No attempts';
            $content .= '<tr>';
            $content .= '<td>' . htmlspecialchars($student['username']) . '</td>';
            $content .= '<td>' . htmlspecialchars($student['email']) . '</td>';
            $content .= '<td>' . (int)$summary['completed_lessons'] . ' / ' . (int)$summary['total_lessons'] . '</td>';
            $content .= '<td>' . htmlspecialchars((string)$summary['lesson_progress_percent']) . '%</td>';
            $content .= '<td>' . (int)$summary['quizzes_passed'] . ' / ' . (int)$summary['total_quizzes'] . '</td>';
            $content .= '<td>' . htmlspecialchars((string)$quizAverage) . '</td>';
            $content .= '<td><a href="gradebook_student.php?course_id=' . $course_id . '&user_id=' . $student['id'] . '" class="btn btn-sm btn-primary">View Detail</a></td>';
            $content .= '</tr>';
        }
        $content .= '</tbody></table></div>';
    } else {
        $content .= '<p>No student progress or quiz attempts yet for this course.</p>';
    }

    include '../includes/header.php';
    exit;
}

$courses = getCourses();
$title = 'Gradebook';
$content = '<h2>Gradebook</h2>';
$content .= '<p>Select a course to view student grades and progress.</p>';

if ($courses) {
    $content .= '<div class="table-responsive"><table class="table table-striped">';
    $content .= '<thead><tr><th>Course</th><th>Description</th><th>Actions</th></tr></thead><tbody>';
    foreach ($courses as $course) {
        $content .= '<tr>';
        $content .= '<td>' . htmlspecialchars($course['title']) . '</td>';
        $content .= '<td>' . htmlspecialchars(substr(strip_tags($course['description'] ?? ''), 0, 120)) . '</td>';
        $content .= '<td><a href="gradebook.php?course_id=' . $course['id'] . '" class="btn btn-sm btn-primary">Open Gradebook</a></td>';
        $content .= '</tr>';
    }
    $content .= '</tbody></table></div>';
} else {
    $content .= '<p>No courses available yet.</p>';
}

include '../includes/header.php';
?>
