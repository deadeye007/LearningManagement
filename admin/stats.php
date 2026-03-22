<?php
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$title = 'Platform Statistics';
$content = '<h2>Platform Statistics</h2>';

// Get statistics
global $pdo;

// Total users
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users");
$total_users = $stmt->fetch()['total'];

// Total courses
$stmt = $pdo->query("SELECT COUNT(*) as total FROM courses");
$total_courses = $stmt->fetch()['total'];

// Total lessons
$stmt = $pdo->query("SELECT COUNT(*) as total FROM lessons");
$total_lessons = $stmt->fetch()['total'];

// Total completed lessons
$stmt = $pdo->query("SELECT COUNT(*) as total FROM user_progress WHERE completed = TRUE");
$total_completed = $stmt->fetch()['total'];

// Total quizzes
$stmt = $pdo->query("SELECT COUNT(*) as total FROM quizzes");
$total_quizzes = $stmt->fetch()['total'];

// Total quiz attempts
$stmt = $pdo->query("SELECT COUNT(*) as total FROM quiz_attempts");
$total_quiz_attempts = $stmt->fetch()['total'];

// Recent registrations (last 30 days)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM users WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$recent_users = $stmt->fetch()['total'];

// Recent course completions (last 30 days)
$stmt = $pdo->query("SELECT COUNT(*) as total FROM user_progress WHERE completed = TRUE AND completed_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)");
$recent_completions = $stmt->fetch()['total'];

$content .= '<div class="row">';
$content .= '<div class="col-md-4"><div class="card"><div class="card-body"><h5 class="card-title">Total Users</h5><h3>' . $total_users . '</h3></div></div></div>';
$content .= '<div class="col-md-4"><div class="card"><div class="card-body"><h5 class="card-title">Total Courses</h5><h3>' . $total_courses . '</h3></div></div></div>';
$content .= '<div class="col-md-4"><div class="card"><div class="card-body"><h5 class="card-title">Total Lessons</h5><h3>' . $total_lessons . '</h3></div></div></div>';
$content .= '</div>';

$content .= '<div class="row mt-3">';
$content .= '<div class="col-md-4"><div class="card"><div class="card-body"><h5 class="card-title">Completed Lessons</h5><h3>' . $total_completed . '</h3></div></div></div>';
$content .= '<div class="col-md-4"><div class="card"><div class="card-body"><h5 class="card-title">Total Quizzes</h5><h3>' . $total_quizzes . '</h3></div></div></div>';
$content .= '<div class="col-md-4"><div class="card"><div class="card-body"><h5 class="card-title">Quiz Attempts</h5><h3>' . $total_quiz_attempts . '</h3></div></div></div>';
$content .= '</div>';

$content .= '<div class="row mt-3">';
$content .= '<div class="col-md-6"><div class="card"><div class="card-body"><h5 class="card-title">New Users (30 days)</h5><h3>' . $recent_users . '</h3></div></div></div>';
$content .= '<div class="col-md-6"><div class="card"><div class="card-body"><h5 class="card-title">Recent Completions (30 days)</h5><h3>' . $recent_completions . '</h3></div></div></div>';
$content .= '</div>';

include '../includes/header.php';
?>
