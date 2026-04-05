<?php
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$assignment_id = isset($_GET['aid']) ? (int)$_GET['aid'] : 0;

if ($assignment_id <= 0) {
    header('Location: assignments.php');
    exit;
}

$assignment = getAssignment($assignment_id);
if (!$assignment) {
    header('Location: assignments.php');
    exit;
}

// Check if user has access to this assignment
$course_id = $assignment['course_id'];
$course = getCourse($course_id);

// Get user's role in course
$is_instructor = isInstructor($_SESSION['user_id'], $course_id);

$title = 'Discussion - ' . htmlspecialchars($assignment['title']);
$page_title = $title;

// Get all discussion threads for this assignment
$threads = getAssignmentDiscussions($assignment_id, 'recent');

// Handle new thread creation
$create_message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_thread') {
    $thread_title = isset($_POST['thread_title']) ? trim($_POST['thread_title']) : '';
    $thread_description = isset($_POST['thread_description']) ? trim($_POST['thread_description']) : '';
    $allow_anonymous = isset($_POST['allow_anonymous']) ? 1 : 0;
    
    if (strlen($thread_title) > 0 && strlen($thread_title) <= 255) {
        $thread_id = createDiscussionThread($assignment_id, $course_id, $user_id, $thread_title, $thread_description, $allow_anonymous);
        if ($thread_id) {
            header('Location: discussion_thread.php?tid=' . $thread_id);
            exit;
        } else {
            $create_message = '<div class="alert alert-danger">Failed to create discussion thread. Please try again.</div>';
        }
    } else {
        $create_message = '<div class="alert alert-warning">Thread title is required and must be less than 255 characters.</div>';
    }
}

// Start output
include 'includes/header.php';
?>

<div class="container-fluid my-5">
    <div class="row mb-4">
        <div class="col-md-12">
            <h2><?php echo htmlspecialchars($assignment['title']); ?> - Discussions</h2>
            <p class="text-muted">
                <a href="course.php?id=<?php echo $course_id; ?>" class="btn btn-sm btn-secondary">Back to Course</a>
                <a href="assignment_view.php?id=<?php echo $assignment_id; ?>" class="btn btn-sm btn-secondary">View Assignment</a>
            </p>
        </div>
    </div>

    <?php if ($create_message): ?>
        <?php echo $create_message; ?>
    <?php endif; ?>

    <div class="row mb-4">
        <div class="col-md-9">
            <h4 class="mb-4">Discussion Threads (<?php echo count($threads); ?>)</h4>
            
            <?php if (empty($threads)): ?>
                <div class="card">
                    <div class="card-body text-center text-muted py-5">
                        <p>No discussions started yet. Be the first to start one!</p>
                    </div>
                </div>
            <?php else: ?>
                <div class="list-group">
                    <?php foreach ($threads as $thread): ?>
                        <a href="discussion_thread.php?tid=<?php echo $thread['id']; ?>" class="list-group-item list-group-item-action">
                            <div class="d-flex justify-content-between align-items-start">
                                <div class="flex-grow-1">
                                    <h6 class="mb-2">
                                        <?php if ($thread['is_pinned']): ?>
                                            <span class="badge bg-info me-2">PINNED</span>
                                        <?php endif; ?>
                                        <?php if ($thread['is_locked']): ?>
                                            <span class="badge bg-danger me-2">LOCKED</span>
                                        <?php endif; ?>
                                        <?php echo htmlspecialchars($thread['title']); ?>
                                    </h6>
                                    <p class="mb-2 small text-muted">
                                        Started by <strong><?php echo htmlspecialchars($thread['created_by_name'] ?? 'Unknown'); ?></strong>
                                        • <?php echo date('M d, Y @ g:ia', strtotime($thread['created_at'])); ?>
                                    </p>
                                    <?php if (!empty($thread['description'])): ?>
                                        <p class="mb-0 small"><?php echo htmlspecialchars(substr($thread['description'], 0, 100)); ?></p>
                                    <?php endif; ?>
                                </div>
                                <div class="text-end">
                                    <div class="badge bg-secondary"><?php echo $thread['post_count'] ?? 0; ?> replies</div>
                                    <div class="small text-muted mt-2">
                                        <?php if ($thread['last_post_date']): ?>
                                            Last: <?php echo date('M d @ g:ia', strtotime($thread['last_post_date'])); ?>
                                        <?php else: ?>
                                            No replies yet
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>

        <div class="col-md-3">
            <div class="card card-secondary">
                <div class="card-header">
                    <h5 class="mb-0">Start New Discussion</h5>
                </div>
                <div class="card-body">
                    <form method="post">
                        <input type="hidden" name="action" value="create_thread">
                        
                        <div class="mb-3">
                            <label for="thread_title" class="form-label">Discussion Title</label>
                            <input 
                                type="text" 
                                class="form-control" 
                                id="thread_title" 
                                name="thread_title" 
                                placeholder="Enter topic title"
                                maxlength="255"
                                required
                            >
                            <small class="form-text text-muted">Be specific and clear</small>
                        </div>

                        <div class="mb-3">
                            <label for="thread_description" class="form-label">Description (Optional)</label>
                            <textarea 
                                class="form-control" 
                                id="thread_description" 
                                name="thread_description" 
                                rows="4" 
                                placeholder="Add more details about your discussion topic..."
                            ></textarea>
                        </div>

                        <div class="mb-3 form-check">
                            <input 
                                type="checkbox" 
                                class="form-check-input" 
                                id="allow_anonymous" 
                                name="allow_anonymous"
                            >
                            <label class="form-check-label" for="allow_anonymous">
                                Allow anonymous posts in this thread
                            </label>
                            <small class="d-block text-muted mt-1">Students can post anonymously if enabled</small>
                        </div>

                        <button type="submit" class="btn btn-primary w-100">Create Discussion</button>
                    </form>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h5 class="mb-0">Assignment Info</h5>
                </div>
                <div class="card-body">
                    <p class="mb-2">
                        <strong>Type:</strong><br>
                        <span class="badge bg-primary"><?php echo ucfirst($assignment['assignment_type']); ?></span>
                    </p>
                    <?php if ($assignment['submission_deadline']): ?>
                        <p class="mb-2">
                            <strong>Due:</strong><br>
                            <?php echo date('M d, Y @ g:ia', strtotime($assignment['submission_deadline'])); ?>
                        </p>
                    <?php endif; ?>
                    <p class="mb-0">
                        <strong>Points:</strong> <?php echo $assignment['points_possible']; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.card-secondary {
    border-top: 3px solid #6c757d;
}

.card-secondary .card-header {
    background-color: #f8f9fa;
    border-bottom: 1px solid #dee2e6;
}

.list-group-item {
    border: 1px solid #dee2e6;
    margin-bottom: 0.5rem;
}

.list-group-item:hover {
    background-color: #f8f9fa;
}

.list-group-item h6 {
    color: #0d6efd;
    font-weight: 600;
}

@media (prefers-color-scheme: dark) {
    .card-secondary {
        border-top: 3px solid #495057;
    }

    .card-secondary .card-header {
        background-color: #2d2d2d;
        border-bottom: 1px solid #444;
    }

    .list-group-item {
        background-color: #1e1e1e;
        border-color: #444;
    }

    .list-group-item:hover {
        background-color: #2d2d2d;
    }

    .list-group-item h6 {
        color: #66b3ff;
    }

    .text-muted {
        color: #999 !important;
    }
}
</style>

<?php include 'includes/footer.php'; ?>
