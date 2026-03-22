<?php
require_once '../includes/functions.php';

if (!isAdmin()) {
    header('Location: ../login.php');
    exit;
}

$title = 'Manage Users';
$content = '<h2>Manage Users</h2>';

// Get all users
global $pdo;
$stmt = $pdo->query("SELECT id, username, email, role, created_at FROM users ORDER BY created_at DESC");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

$content .= '<div class="table-responsive">';
$content .= '<table class="table table-striped">';
$content .= '<thead><tr><th>ID</th><th>Username</th><th>Email</th><th>Role</th><th>Registered</th><th>Actions</th></tr></thead>';
$content .= '<tbody>';

foreach ($users as $user) {
    $content .= '<tr>';
    $content .= '<td>' . htmlspecialchars($user['id']) . '</td>';
    $content .= '<td>' . htmlspecialchars($user['username']) . '</td>';
    $content .= '<td>' . htmlspecialchars($user['email']) . '</td>';
    $content .= '<td>' . htmlspecialchars($user['role']) . '</td>';
    $content .= '<td>' . htmlspecialchars($user['created_at']) . '</td>';
    $content .= '<td>';
    if ($user['role'] === 'user') {
        $content .= '<a href="?promote=' . $user['id'] . '" class="btn btn-sm btn-warning">Promote to Admin</a> ';
    } else {
        $content .= '<a href="?demote=' . $user['id'] . '" class="btn btn-sm btn-secondary">Demote to User</a> ';
    }
    $content .= '<a href="?delete=' . $user['id'] . '" class="btn btn-sm btn-danger" onclick="return confirm(\'Are you sure?\')">Delete</a>';
    $content .= '</td>';
    $content .= '</tr>';
}

$content .= '</tbody></table>';
$content .= '</div>';

// Handle actions
if (isset($_GET['promote']) && is_numeric($_GET['promote'])) {
    $user_id = (int)$_GET['promote'];
    $stmt = $pdo->prepare("UPDATE users SET role = 'admin' WHERE id = ?");
    $stmt->execute([$user_id]);
    header('Location: users.php');
    exit;
}

if (isset($_GET['demote']) && is_numeric($_GET['demote'])) {
    $user_id = (int)$_GET['demote'];
    $stmt = $pdo->prepare("UPDATE users SET role = 'user' WHERE id = ?");
    $stmt->execute([$user_id]);
    header('Location: users.php');
    exit;
}

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    // Don't allow deleting yourself
    if ($user_id !== $_SESSION['user_id']) {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
    }
    header('Location: users.php');
    exit;
}

include '../includes/header.php';
?>