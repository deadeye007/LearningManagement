<?php
require_once 'includes/functions.php';

$title = 'Courses';
$content = '<h2>Available Courses</h2>';

$courses = getCourses();
if ($courses) {
    $content .= '<div class="row">';
    foreach ($courses as $course) {
        $course_description = strip_tags(html_entity_decode($course['description'], ENT_QUOTES | ENT_HTML5, 'UTF-8'), '<strong><em><b><i><u>');
        $content .= '
            <div class="col-md-4 mb-4">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">' . htmlspecialchars($course['title']) . '</h5>
                        <p class="card-text">' . $course_description . '</p>
                        <a href="course.php?id=' . $course['id'] . '" class="btn btn-primary">View Course</a>
                    </div>
                </div>
            </div>
        ';
    }
    $content .= '</div>';
} else {
    $content .= '<p>No courses available yet.</p>';
}

include 'includes/header.php';
?>