<?php
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$course_id = $_GET['course_id'] ?? null;
if (!$course_id) {
    header('Location: courses.php');
    exit;
}

$course = getCourse($course_id);
$title = 'Manage Lessons - ' . htmlspecialchars($course['title']);
$content = '<h2>' . $title . '</h2>';
$content .= '<a href="lesson_edit.php?course_id=' . $course_id . '" class="btn btn-success mb-3">Add New Lesson</a>';

$lessons = getLessons($course_id);
if ($lessons) {
    $content .= '<div class="table-responsive"><table class="table table-striped">';
    $content .= '<thead><tr><th>Order</th><th>Title</th><th>Actions</th></tr></thead><tbody>';
    foreach ($lessons as $lesson) {
        $content .= '<tr>';
        $content .= '<td>' . $lesson['order_num'] . '</td>';
        $content .= '<td>' . htmlspecialchars($lesson['title']) . '</td>';
        $content .= '<td>';
        $content .= '<a href="lesson_edit.php?id=' . $lesson['id'] . '&course_id=' . $course_id . '" class="btn btn-sm btn-primary">Edit</a>';
        $content .= '</td>';
        $content .= '</tr>';
    }
    $content .= '</tbody></table></div>';
} else {
    $content .= '<p>No lessons found.</p>';
}

$content .= '<a href="courses.php" class="btn btn-secondary">Back to Courses</a>';

include '../includes/header.php';
?>