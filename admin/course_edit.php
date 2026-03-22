<?php
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$course = null;
if (isset($_GET['id'])) {
    $course = getCourse($_GET['id']);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitizeInput($_POST['title']);
    $description = sanitizeInput($_POST['description']);

    global $pdo;
    if ($course) {
        // Update
        $stmt = $pdo->prepare("UPDATE courses SET title = ?, description = ? WHERE id = ?");
        $stmt->execute([$title, $description, $course['id']]);
    } else {
        // Insert
        $stmt = $pdo->prepare("INSERT INTO courses (title, description, instructor_id) VALUES (?, ?, ?)");
        $stmt->execute([$title, $description, $_SESSION['user_id']]);
    }
    header('Location: courses.php');
    exit;
}

$title = $course ? 'Edit Course' : 'Add Course';
$content = '<h2>' . $title . '</h2>';
$content .= '<form method="post">';
$content .= '<div class="mb-3"><label for="title" class="form-label">Title</label><input type="text" class="form-control" id="title" name="title" value="' . htmlspecialchars($course['title'] ?? '') . '" required></div>';
$content .= '<div class="mb-3"><label for="description" class="form-label">Description</label><textarea class="form-control" id="description" name="description" rows="5" required>' . htmlspecialchars($course['description'] ?? '') . '</textarea></div>';
$content .= '<button type="submit" class="btn btn-primary">Save</button> <a href="courses.php" class="btn btn-secondary">Cancel</a>';
$content .= '</form>';

include '../includes/header.php';
?>