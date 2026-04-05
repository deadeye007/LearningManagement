<?php
require_once 'includes/functions.php';

if (!isLoggedIn()) {
    header('Location: login.php');
    exit;
}

$user_id = $_SESSION['user_id'];
$assignment_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($assignment_id <= 0) {
    header('Location: assignments.php');
    exit;
}

$assignment = getAssignment($assignment_id);
if (!$assignment) {
    header('Location: assignments.php');
    exit;
}

$course = getCourse($assignment['course_id']);
$is_instructor = isInstructor($_SESSION['user_id'], $assignment['course_id']);

// Get student's submission if exists
$user_submission = getStudentSubmission($assignment_id, $user_id);

// Get submission grade if exists
$grade = null;
if ($user_submission && $user_submission['is_graded']) {
    $stmt = $GLOBALS['pdo']->prepare("SELECT * FROM submission_grades WHERE submission_id = ?");
    $stmt->execute([$user_submission['id']]);
    $grade = $stmt->fetch(PDO::FETCH_ASSOC);
}

$title = 'Assignment - ' . htmlspecialchars($assignment['title']);
$page_title = $title;

include 'includes/header.php';
?>

<div class="container-fluid my-5">
    <div class="row">
        <div class="col-md-12">
            <div class="mb-3">
                <a href="course.php?id=<?php echo $assignment['course_id']; ?>" class="btn btn-sm btn-secondary">
                    ← Back to Course
                </a>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <!-- Assignment Details -->
            <h2><?php echo htmlspecialchars($assignment['title']); ?></h2>
            
            <div class="alert alert-info" role="alert">
                <strong><?php echo ucfirst($assignment['assignment_type']); ?> Assignment</strong>
            </div>

            <!-- Assignment Description -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Assignment Details</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <h6>Description</h6>
                        <p><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></p>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <strong>Points Possible:</strong> <?php echo $assignment['points_possible']; ?>
                        </div>
                        <div class="col-md-6 mb-3">
                            <strong>Grading Type:</strong> <?php echo ucfirst($assignment['grading_type']); ?>
                        </div>
                    </div>

                    <?php if ($assignment['submission_deadline']): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>Due Date:</strong> <?php echo date('M d, Y @ g:ia', strtotime($assignment['submission_deadline'])); ?>
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Late Submission:</strong> <?php echo $assignment['late_submission_allowed'] ? 'Allowed' : 'Not allowed'; ?>
                            </div>
                        </div>
                    <?php endif; ?>

                    <?php if ($assignment['allow_file_upload']): ?>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <strong>File Upload:</strong> Allowed
                            </div>
                            <div class="col-md-6 mb-3">
                                <strong>Max File Size:</strong> <?php echo $assignment['max_file_size_mb']; ?> MB
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Student Submission Status & Grade -->
            <?php if (!$is_instructor): ?>
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="mb-0">Your Submission</h5>
                    </div>
                    <div class="card-body">
                        <?php if ($user_submission): ?>
                            <div class="alert alert-success">
                                <strong>Status:</strong> Submitted
                                <br>
                                <strong>Submitted:</strong> <?php echo date('M d, Y @ g:ia', strtotime($user_submission['submitted_at'])); ?>
                            </div>

                            <?php if ($grade && $grade['is_published']): ?>
                                <div class="alert alert-info">
                                    <strong>Your Grade:</strong> 
                                    <?php echo $grade['final_points'] ?? $grade['points_earned']; ?> / <?php echo $grade['points_possible']; ?>
                                    (<?php echo round(($grade['final_points'] ?? $grade['points_earned']) / $grade['points_possible'] * 100, 1); ?>%)
                                </div>

                                <?php if ($grade['feedback_text']): ?>
                                    <div class="mb-3">
                                        <strong>Instructor Feedback:</strong>
                                        <p class="mt-2 p-3 bg-light rounded">
                                            <?php echo nl2br(htmlspecialchars($grade['feedback_text'])); ?>
                                        </p>
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($user_submission['is_graded']): ?>
                                <p class="text-muted">Your submission has been graded but the grades have not been released yet.</p>
                            <?php else: ?>
                                <p class="text-muted">Your submission is pending grading.</p>
                            <?php endif; ?>

                            <?php if ($assignment['allow_resubmission']): ?>
                                <a href="submit_assignment.php?id=<?php echo $assignment_id; ?>" class="btn btn-sm btn-warning">
                                    Resubmit Assignment
                                </a>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="text-muted mb-3">You have not submitted this assignment yet.</p>
                            <a href="submit_assignment.php?id=<?php echo $assignment_id; ?>" class="btn btn-primary">
                                Submit Assignment
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Assignments with Discussions -->
            <?php if ($assignment['assignment_type'] === 'discussion'): ?>
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Class Discussions</h5>
                    </div>
                    <div class="card-body">
                        <p class="mb-3">
                            This assignment involves participating in class discussions. Share your thoughts and engage with your classmates.
                        </p>
                        <a href="discussions.php?aid=<?php echo $assignment_id; ?>" class="btn btn-primary btn-lg">
                            → Open Discussions Forum
                        </a>
                    </div>
                </div>
            <?php endif; ?>
        </div>

        <!-- Sidebar -->
        <div class="col-md-4">
            <!-- Quick Info -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Assignment Status</h5>
                </div>
                <div class="card-body">
                    <?php
                    $status_badge = '';
                    $status_text = '';
                    
                    if ($user_submission) {
                        if ($grade && $grade['is_published']) {
                            $status_badge = '<span class="badge bg-success">GRADED</span>';
                            $status_text = 'Graded on ' . date('M d, Y', strtotime($grade['published_at']));
                        } elseif ($user_submission['is_graded']) {
                            $status_badge = '<span class="badge bg-warning">PENDING GRADE REVIEW</span>';
                            $status_text = 'Graded on ' . date('M d, Y', strtotime($user_submission['graded_at']));
                        } else {
                            $status_badge = '<span class="badge bg-info">SUBMITTED</span>';
                            $status_text = 'Submitted on ' . date('M d, Y', strtotime($user_submission['submitted_at']));
                        }
                    } elseif ($assignment['submission_deadline'] && strtotime($assignment['submission_deadline']) < time()) {
                        $status_badge = '<span class="badge bg-danger">OVERDUE</span>';
                        $status_text = 'Due ' . date('M d, Y', strtotime($assignment['submission_deadline']));
                    } elseif ($assignment['submission_deadline']) {
                        $days_left = ceil((strtotime($assignment['submission_deadline']) - time()) / 86400);
                        $status_badge = '<span class="badge bg-warning">DUE SOON</span>';
                        $status_text = 'Due in ' . $days_left . ' day' . ($days_left !== 1 ? 's' : '');
                    } else {
                        $status_badge = '<span class="badge bg-secondary">NOT SUBMITTED</span>';
                        $status_text = 'No submission deadline';
                    }
                    ?>
                    <p class="mb-2">
                        <?php echo $status_badge; ?>
                    </p>
                    <p class="text-muted small mb-0"><?php echo $status_text; ?></p>
                </div>
            </div>

            <!-- Discussion Link (if discussion-type assignment) -->
            <?php if ($assignment['assignment_type'] === 'discussion'): ?>
                <div class="card mb-4 border-primary">
                    <div class="card-header bg-light">
                        <h5 class="mb-0">💬 Participate in Discussions</h5>
                    </div>
                    <div class="card-body">
                        <p class="small mb-3">
                            Join the class discussion forum to share ideas and engage with your peers.
                        </p>
                        <a href="discussions.php?aid=<?php echo $assignment_id; ?>" class="btn btn-primary w-100">
                            Go to Forum
                        </a>
                    </div>
                </div>
            <?php endif; ?>

            <!-- Course Info -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Course</h5>
                </div>
                <div class="card-body">
                    <p class="mb-0">
                        <a href="course.php?id=<?php echo $assignment['course_id']; ?>" class="text-decoration-none">
                            <?php echo htmlspecialchars($course['title']); ?>
                        </a>
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>
