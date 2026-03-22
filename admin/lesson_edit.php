<?php
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$lesson = null;
$course_id = $_GET['course_id'] ?? null;

if (isset($_GET['id'])) {
    $lesson = getLesson($_GET['id']);
    $course_id = $lesson ? null : $course_id; // If editing, course_id from lesson
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $content_text = sanitizeInput($_POST['content']);
    $order_num = (int)$_POST['order_num'];
    $target_course_id = $_POST['course_id'];

    global $pdo;
    if ($lesson) {
        // Update
        $stmt = $pdo->prepare("UPDATE lessons SET title = ?, content = ?, order_num = ? WHERE id = ?");
        $stmt->execute([$title, $content_text, $order_num, $lesson['id']]);
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO lessons (course_id, title, content, order_num) VALUES (?, ?, ?, ?)");
        $stmt->execute([$target_course_id, $title, $content_text, $order_num]);
    }
    header('Location: lessons.php?course_id=' . $target_course_id);
    exit;
}

$title = $lesson ? 'Edit Lesson' : 'Add Lesson';
$content = '<h2>' . $title . '</h2>';
$content .= '<form method="post">';

if (!$lesson) {
    $courses = getCourses();
    $content .= '<div class="mb-3"><label for="course_id" class="form-label">Course</label><select class="form-control" id="course_id" name="course_id" required>';
    foreach ($courses as $c) {
        $selected = ($c['id'] == $course_id) ? 'selected' : '';
        $content .= '<option value="' . $c['id'] . '" ' . $selected . '>' . htmlspecialchars($c['title']) . '</option>';
    }
    $content .= '</select></div>';
} else {
    $content .= '<input type="hidden" name="course_id" value="' . $course_id . '">';
}

$content .= '<div class="mb-3"><label for="title" class="form-label">Title</label><input type="text" class="form-control" id="title" name="title" value="' . htmlspecialchars($lesson['title'] ?? '') . '" required></div>';
$content .= '<div class="mb-3"><label for="order_num" class="form-label">Order</label><input type="number" class="form-control" id="order_num" name="order_num" value="' . ($lesson['order_num'] ?? 1) . '" required></div>';
$content .= '<div class="mb-3"><label for="content" class="form-label">Content</label><textarea class="form-control" id="content" name="content" rows="10" required>' . htmlspecialchars($lesson['content'] ?? '') . '</textarea></div>';
$content .= '<button type="submit" class="btn btn-primary">Save</button> <a href="lessons.php?course_id=' . $course_id . '" class="btn btn-secondary">Cancel</a>';
$content .= '</form>';

include '../includes/header.php';
?>