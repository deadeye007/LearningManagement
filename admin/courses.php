<?php
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$title = 'Manage Courses';
$content = '<h2>Manage Courses</h2>';
$content .= '<a href="course_edit.php" class="btn btn-success mb-3">Add New Course</a>';

$courses = getCourses();
if ($courses) {
    $content .= '<div class="table-responsive"><table class="table table-striped">';
    $content .= '<thead><tr><th>Title</th><th>Description</th><th>Actions</th></tr></thead><tbody>';
    foreach ($courses as $course) {
        $content .= '<tr>';
        $content .= '<td>' . htmlspecialchars($course['title']) . '</td>';
        $content .= '<td>' . htmlspecialchars(substr($course['description'], 0, 100)) . '...</td>';
        $content .= '<td>';
        $content .= '<a href="course_edit.php?id=' . $course['id'] . '" class="btn btn-sm btn-primary">Edit</a> ';
        $content .= '<a href="lessons.php?course_id=' . $course['id'] . '" class="btn btn-sm btn-secondary">Lessons</a>';
        $content .= '</td>';
        $content .= '</tr>';
    }
    $content .= '</tbody></table></div>';
} else {
    $content .= '<p>No courses found.</p>';
}

include '../includes/header.php';
?>