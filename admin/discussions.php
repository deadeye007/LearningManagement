<?php
require_once '../includes/functions.php';

if (!isLoggedIn() || !isAdmin($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit;
}

$user_id = $_SESSION['user_id'];

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    
    if ($action === 'toggle_pin' && isset($_POST['thread_id'])) {
        $thread_id = (int)$_POST['thread_id'];
        $thread = getDiscussionThread($thread_id);
        toggleThreadPin($thread_id, !$thread['is_pinned']);
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
    if ($action === 'toggle_lock' && isset($_POST['thread_id'])) {
        $thread_id = (int)$_POST['thread_id'];
        $thread = getDiscussionThread($thread_id);
        toggleThreadLock($thread_id, !$thread['is_locked']);
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
    
    if ($action === 'delete_post' && isset($_POST['post_id'])) {
        $post_id = (int)$_POST['post_id'];
        deleteDiscussionPost($post_id);
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit;
    }
}

$title = 'Discussion Management';

// Build content
$content = '<h2>Discussion Management</h2>';
$content .= '<p><a href="index.php" class="btn btn-secondary btn-sm">Back to Admin</a></p>';

// Discussion Statistics
$stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) as count FROM discussion_threads");
$stmt->execute();
$thread_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) as count FROM discussion_posts");
$stmt->execute();
$post_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) as count FROM discussion_threads WHERE is_locked = 1");
$stmt->execute();
$locked_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$stmt = $GLOBALS['pdo']->prepare("SELECT COUNT(*) as count FROM discussion_posts WHERE is_flagged = 1");
$stmt->execute();
$flagged_count = $stmt->fetch(PDO::FETCH_ASSOC)['count'];

$content .= '<div class="row mb-4">';
$content .= '<div class="col-md-3"><div class="card text-center"><div class="card-body"><h3>' . $thread_count . '</h3><p class="text-muted mb-0">Total Threads</p></div></div></div>';
$content .= '<div class="col-md-3"><div class="card text-center"><div class="card-body"><h3>' . $post_count . '</h3><p class="text-muted mb-0">Total Posts</p></div></div></div>';
$content .= '<div class="col-md-3"><div class="card text-center"><div class="card-body"><h3>' . $locked_count . '</h3><p class="text-muted mb-0">Locked Threads</p></div></div></div>';
$content .= '<div class="col-md-3"><div class="card text-center"><div class="card-body"><h3>' . $flagged_count . '</h3><p class="text-muted mb-0">Flagged Posts</p></div></div></div>';
$content .= '</div>';

// Recent Threads
$content .= '<div class="card mb-4">';
$content .= '<div class="card-header"><h5 class="mb-0">Recent Discussion Threads</h5></div>';
$content .= '<div class="card-body"><div class="table-responsive"><table class="table table-hover">';
$content .= '<thead><tr><th>Thread Title</th><th>Assignment</th><th>Started By</th><th>Posts</th><th>Status</th><th>Last Activity</th><th>Actions</th></tr></thead>';
$content .= '<tbody>';

$stmt = $GLOBALS['pdo']->prepare("
    SELECT dt.*, 
           a.title as assignment_title,
           u.username as created_by_name,
           COUNT(dp.id) as post_count
    FROM discussion_threads dt
    LEFT JOIN assignments a ON dt.assignment_id = a.id
    LEFT JOIN users u ON dt.created_by = u.id
    LEFT JOIN discussion_posts dp ON dt.id = dp.thread_id AND dp.is_published = 1
    GROUP BY dt.id
    ORDER BY dt.last_activity DESC
    LIMIT 20
");
$stmt->execute();
$threads = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($threads as $thread) {
    $content .= '<tr>';
    $content .= '<td><a href="../discussion_thread.php?tid=' . $thread['id'] . '" target="_blank">' . htmlspecialchars($thread['title']) . '</a></td>';
    $content .= '<td>' . htmlspecialchars($thread['assignment_title'] ?? 'N/A') . '</td>';
    $content .= '<td>' . htmlspecialchars($thread['created_by_name'] ?? 'Unknown') . '</td>';
    $content .= '<td>' . ($thread['post_count'] ?? 0) . '</td>';
    $content .= '<td>';
    if ($thread['is_pinned']) $content .= '<span class="badge bg-info">PINNED</span> ';
    if ($thread['is_locked']) $content .= '<span class="badge bg-danger">LOCKED</span>';
    $content .= '</td>';
    $content .= '<td>';
    if ($thread['last_activity']) {
        $content .= date('M d, Y g:ia', strtotime($thread['last_activity']));
    } else {
        $content .= 'No activity';
    }
    $content .= '</td>';
    $content .= '<td>';
    $content .= '<form method="post" style="display: inline;">';
    $content .= '<input type="hidden" name="action" value="toggle_pin">';
    $content .= '<input type="hidden" name="thread_id" value="' . $thread['id'] . '">';
    $content .= '<button type="submit" class="btn btn-sm btn-link text-decoration-none">' . ($thread['is_pinned'] ? 'Unpin' : 'Pin') . '</button>';
    $content .= '</form>';
    $content .= '<form method="post" style="display: inline;">';
    $content .= '<input type="hidden" name="action" value="toggle_lock">';
    $content .= '<input type="hidden" name="thread_id" value="' . $thread['id'] . '">';
    $content .= '<button type="submit" class="btn btn-sm btn-link text-decoration-none">' . ($thread['is_locked'] ? 'Unlock' : 'Lock') . '</button>';
    $content .= '</form>';
    $content .= '</td>';
    $content .= '</tr>';
}

$content .= '</tbody></table></div></div></div>';

// Flagged Posts
$content .= '<div class="card">';
$content .= '<div class="card-header"><h5 class="mb-0">Flagged Posts</h5></div>';
$content .= '<div class="card-body">';

$stmt = $GLOBALS['pdo']->prepare("
    SELECT dp.*, 
           dt.title as thread_title,
           u.username as posted_by_name
    FROM discussion_posts dp
    LEFT JOIN discussion_threads dt ON dp.thread_id = dt.id
    LEFT JOIN users u ON dp.posted_by = u.id
    WHERE dp.is_flagged = 1
    ORDER BY dp.created_at DESC
");
$stmt->execute();
$flagged_posts = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (empty($flagged_posts)) {
    $content .= '<p class="text-muted">No flagged posts at this time.</p>';
} else {
    $content .= '<div class="table-responsive"><table class="table table-hover table-sm">';
    $content .= '<thead><tr><th>Posted By</th><th>Thread</th><th>Flag Reason</th><th>Posted</th><th>Action</th></tr></thead>';
    $content .= '<tbody>';
    
    foreach ($flagged_posts as $post) {
        $content .= '<tr>';
        $content .= '<td>' . htmlspecialchars($post['posted_by_name'] ?? 'Unknown') . '</td>';
        $content .= '<td><a href="../discussion_thread.php?tid=' . $post['thread_id'] . '#post-' . $post['id'] . '" target="_blank">' . htmlspecialchars($post['thread_title']) . '</a></td>';
        $content .= '<td>' . htmlspecialchars($post['flag_reason'] ?? '-') . '</td>';
        $content .= '<td>' . date('M d, Y g:ia', strtotime($post['created_at'])) . '</td>';
        $content .= '<td>';
        $content .= '<form method="post" style="display: inline;">';
        $content .= '<input type="hidden" name="action" value="delete_post">';
        $content .= '<input type="hidden" name="post_id" value="' . $post['id'] . '">';
        $content .= '<button type="submit" class="btn btn-sm btn-danger" onclick="return confirm(\'Delete this post?\');">Delete</button>';
        $content .= '</form>';
        $content .= '</td>';
        $content .= '</tr>';
    }
    
    $content .= '</tbody></table></div>';
}

$content .= '</div></div>';

include '../includes/header.php';
?>
