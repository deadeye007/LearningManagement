<?php
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$thread_id = isset($_GET['tid']) ? (int)$_GET['tid'] : 0;

if ($thread_id <= 0) {
    header('Location: assignments.php');
    exit;
}

$thread = getDiscussionThread($thread_id);
if (!$thread) {
    header('Location: assignments.php');
    exit;
}

$assignment = getAssignment($thread['assignment_id']);
$course_id = $assignment['course_id'];
$is_instructor = isInstructor($_SESSION['user_id'], $course_id);

// Handle new post creation
$post_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_post') {
    if ($thread['is_locked'] && !$is_instructor) {
        $post_message = '<div class="alert alert-warning">This discussion is locked and new posts are not allowed.</div>';
    } else {
        $content = isset($_POST['post_content']) ? trim($_POST['post_content']) : '';
        $is_anonymous = isset($_POST['is_anonymous']) ? 1 : 0;
        
        if (strlen($content) > 0) {
            $post_id = createDiscussionPost($thread_id, $assignment['id'], $user_id, $content, $is_anonymous);
            if ($post_id) {
                // Redirect to show new post
                header('Location: discussion_thread.php?tid=' . $thread_id);
                exit;
            } else {
                $post_message = '<div class="alert alert-danger">Failed to post. Please try again.</div>';
            }
        } else {
            $post_message = '<div class="alert alert-warning">Post content is required.</div>';
        }
    }
}

// Handle like toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_like') {
    $post_id = isset($_POST['post_id']) ? (int)$_POST['post_id'] : 0;
    if ($post_id > 0) {
        toggleDiscussionPostLike($post_id, $user_id);
        header('Location: discussion_thread.php?tid=' . $thread_id);
        exit;
    }
}

// Handle subscription toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'toggle_subscribe') {
    $sub_type = isset($_POST['subscription_type']) ? $_POST['subscription_type'] : 'all_replies';
    toggleDiscussionSubscription($thread_id, $user_id, $sub_type);
    header('Location: discussion_thread.php?tid=' . $thread_id);
    exit;
}

// Get all posts in thread
$posts = getDiscussionPosts($thread_id);

// Get user subscription
$user_subscription = getUserSubscriptionStatus($thread_id, $user_id);

$title = htmlspecialchars($thread['title']);
$page_title = $title;

include 'includes/header.php';
?>

<div class="container-fluid my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="mb-3">
                <a href="discussions.php?aid=<?php echo $assignment['id']; ?>" class="btn btn-sm btn-secondary">
                    ← Back to Discussions
                </a>
            </div>
            
            <div class="d-flex justify-content-between align-items-start mb-3">
                <div>
                    <h2><?php echo $title; ?></h2>
                    <p class="text-muted mb-0">
                        Started by <strong><?php echo htmlspecialchars($thread['created_by_name']); ?></strong>
                        • <?php echo date('M d, Y @ g:ia', strtotime($thread['created_at'])); ?>
                    </p>
                </div>
                <div>
                    <?php if ($thread['is_pinned']): ?>
                        <span class="badge bg-info">PINNED</span>
                    <?php endif; ?>
                    <?php if ($thread['is_locked']): ?>
                        <span class="badge bg-danger">LOCKED</span>
                    <?php endif; ?>
                </div>
            </div>

            <?php if (!empty($thread['description'])): ?>
                <div class="card mb-4">
                    <div class="card-body">
                        <?php echo nl2br(htmlspecialchars($thread['description'])); ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($post_message): ?>
        <?php echo $post_message; ?>
    <?php endif; ?>

    <div class="row">
        <div class="col-md-8">
            <!-- Posts List -->
            <h4 class="mb-3">Replies (<?php echo count($posts); ?>)</h4>

            <?php if (empty($posts)): ?>
                <div class="card mb-4">
                    <div class="card-body text-center text-muted py-5">
                        <p>No replies yet. Be the first to respond!</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="discussion-posts mb-4">
                    <?php foreach ($posts as $post): ?>
                        <div class="card mb-3 post-card" id="post-<?php echo $post['id']; ?>">
                            <div class="card-header d-flex justify-content-between align-items-start">
                                <div>
                                    <strong><?php echo htmlspecialchars($post['posted_by_name']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo date('M d, Y @ g:ia', strtotime($post['created_at'])); ?>
                                        <?php if ($post['edited_at']): ?>
                                            <em>(edited <?php echo date('M d @ g:ia', strtotime($post['edited_at'])); ?>)</em>
                                        <?php endif; ?>
                                    </small>
                                </div>
                                <div>
                                    <?php if ($post['is_instructor_response']): ?>
                                        <span class="badge bg-success">Instructor</span>
                                    <?php endif; ?>
                                    <?php if ($post['is_anonymous']): ?>
                                        <span class="badge bg-secondary">Anonymous</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="card-body">
                                <p class="card-text"><?php echo nl2br(htmlspecialchars($post['content'])); ?></p>
                            </div>
                            <div class="card-footer bg-light d-flex justify-content-between align-items-center">
                                <div class="post-actions">
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="action" value="toggle_like">
                                        <input type="hidden" name="post_id" value="<?php echo $post['id']; ?>">
                                        <button type="submit" class="btn btn-sm btn-link text-decoration-none">
                                            <?php if (hasUserLikedPost($post['id'], $user_id)): ?>
                                                ❤️
                                            <?php else: ?>
                                                🤍
                                            <?php endif; ?>
                                            <span class="likes-count"><?php echo $post['likes_count']; ?></span>
                                        </button>
                                    </form>
                                </div>
                                <div>
                                    <?php if ($post['reply_count'] > 0): ?>
                                        <small class="text-muted"><?php echo $post['reply_count']; ?> replies</small>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Post Reply Form -->
            <?php if (!$thread['is_locked'] || $is_instructor): ?>
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Add Your Reply</h5>
                    </div>
                    <div class="card-body">
                        <form method="post">
                            <input type="hidden" name="action" value="add_post">
                            
                            <div class="mb-3">
                                <label for="post_content" class="form-label">Your Response</label>
                                <textarea 
                                    class="form-control" 
                                    id="post_content" 
                                    name="post_content" 
                                    rows="5" 
                                    placeholder="Share your thoughts..."
                                    required
                                ></textarea>
                                <small class="form-text text-muted">Be respectful and constructive in your response</small>
                            </div>

                            <?php if ($thread['allow_anonymous']): ?>
                                <div class="mb-3 form-check">
                                    <input 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        id="is_anonymous" 
                                        name="is_anonymous"
                                    >
                                    <label class="form-check-label" for="is_anonymous">
                                        Post anonymously
                                    </label>
                                </div>
                            <?php endif; ?>

                            <button type="submit" class="btn btn-primary">Post Reply</button>
                        </form>
                    </div>
                </div>
            <?php elseif ($thread['is_locked']): ?>
                <div class="alert alert-info">
                    This discussion is locked. New posts are not allowed.
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4 ps-md-4">
            <!-- Subscription -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Thread Subscription</h5>
                </div>
                <div class="card-body">
                    <p class="mb-3 small text-muted">
                        Get notified when new replies are posted.
                    </p>
                    <form method="post">
                        <input type="hidden" name="action" value="toggle_subscribe">
                        <select name="subscription_type" class="form-select form-select-sm mb-2">
                            <option value="all_replies" <?php echo $user_subscription === 'all_replies' ? 'selected' : ''; ?>>
                                All replies
                            </option>
                            <option value="instructor_only" <?php echo $user_subscription === 'instructor_only' ? 'selected' : ''; ?>>
                                Instructor replies only
                            </option>
                            <option value="none" <?php echo $user_subscription === 'none' ? 'selected' : ''; ?>>
                                Unsubscribe
                            </option>
                        </select>
                        <button type="submit" class="btn btn-sm btn-primary w-100">Update</button>
                    </form>
                </div>
            </div>

            <!-- Thread Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Thread Info</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong><?php echo count($posts); ?></strong><br>
                        <small class="text-muted">Replies</small>
                    </p>
                    <p class="mb-2">
                        <strong><?php echo date('M d, Y \a\t g:ia', strtotime($thread['created_at'])); ?></strong><br>
                        <small class="text-muted">Started</small>
                    </p>
                    <?php if ($thread['last_activity']): ?>
                        <p class="mb-0">
                            <strong><?php echo date('M d, Y \a\t g:ia', strtotime($thread['last_activity'])); ?></strong><br>
                            <small class="text-muted">Last activity</small>
                        </p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.post-card {
    border-left: 4px solid #0d6efd;
}

.post-card:hover {
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
}

.card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

@media (prefers-color-scheme: dark) {
    .post-card {
        border-left: 4px solid #66b3ff;
        background-color: #1e1e1e;
    }

    .card-header {
        background-color: #2d2d2d;
        border-bottom: 1px solid #444;
    }

    .text-muted {
        color: #999 !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
