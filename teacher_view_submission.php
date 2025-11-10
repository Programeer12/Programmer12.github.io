<?php
require_once 'includes/auth.php';
require_once 'includes/assignment_helper.php';
require_once 'includes/notification_system.php';

// Require teacher role
$auth->requireRole('teacher');

// Initialize notification system
$notificationSystem = new NotificationSystem($pdo);

// Get teacher data
$teacher = $auth->getCurrentUser();
$teacher_id = $teacher['id'];

// Get submission ID from query string
$submission_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$submission_id) {
    header("Location: teacher_submissions.php");
    exit();
}

// Get submission details
$submission = null;

try {
    // Check if this submission belongs to an assignment by this teacher
    $stmt = $pdo->prepare("SELECT s.*, a.*, u.name as student_name, u.email as student_email, u.course as student_course 
                          FROM submissions s 
                          JOIN assignments a ON s.assignment_id = a.id 
                          JOIN users u ON s.student_id = u.id 
                          WHERE s.id = ? AND a.teacher_id = ?");
    $stmt->execute([$submission_id, $teacher_id]);
    $result = $stmt->fetch();
    
    if (!$result) {
        // Submission not found or doesn't belong to this teacher
        header("Location: teacher_submissions.php");
        exit();
    }
    
    $submission = $result;
    
    // Mark as viewed if not already
    if (!($submission['viewed'] ?? false)) {
        $stmt = $pdo->prepare("UPDATE submissions SET viewed = 1 WHERE id = ?");
        $stmt->execute([$submission_id]);
        $submission['viewed'] = 1;
    }
    
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Process grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
    $grade = $_POST['grade'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    
    // Validate grade
    if (!is_numeric($grade) || $grade < 0) {
        $error = "Grade must be a positive number.";
    } 
    // Validate grade against max score
    elseif ($grade > $submission['max_score']) {
        $error = "Grade cannot exceed the maximum score of {$submission['max_score']}.";
    } 
    else {
        try {
            // Update the submission with grade and feedback
            $stmt = $pdo->prepare("UPDATE submissions SET grade = ?, feedback = ?, status = 'graded' WHERE id = ?");
            $stmt->execute([$grade, $feedback, $submission_id]);
            
            // Send notification to student about grade
            $percentage = round(($grade / $submission['max_score']) * 100, 1);
            $notificationSystem->createNotification(
                $submission['student_id'],
                "Assignment Graded",
                "Your assignment '{$submission['title']}' has been graded by {$teacher['name']}. Score: {$grade}/{$submission['max_score']} ({$percentage}%)" . 
                ($feedback ? " - Feedback: " . substr($feedback, 0, 100) . (strlen($feedback) > 100 ? "..." : "") : ""),
                'grade',
                $submission_id,
                'submission',
                'high'
            );
            
            // Send notification to teacher about successful grading
            $notificationSystem->createNotification(
                $teacher_id,
                "Assignment Graded Successfully",
                "You have successfully graded {$submission['student_name']}'s assignment '{$submission['title']}'. Grade: {$grade}/{$submission['max_score']} ({$percentage}%)",
                'grade',
                $submission_id,
                'submission',
                'medium'
            );
            
            // Log the activity
            $description = "Teacher {$teacher['name']} graded assignment '{$submission['title']}' for student {$submission['student_name']}";
            $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
            $stmt->execute([$teacher_id, "grade_submission", $description, $_SERVER['REMOTE_ADDR']]);
            
            $success = "Submission graded successfully! Student has been notified.";
            
            // Refresh the submission data
            $stmt = $pdo->prepare("SELECT s.*, a.*, u.name as student_name, u.email as student_email, u.course as student_course 
                                  FROM submissions s 
                                  JOIN assignments a ON s.assignment_id = a.id 
                                  JOIN users u ON s.student_id = u.id 
                                  WHERE s.id = ?");
            $stmt->execute([$submission_id]);
            $submission = $stmt->fetch();
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    $auth->logout();
    header("Location: teacher_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Submission - EduAssign</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="styles.css">
</head>
<body>
    <header>
        <div class="header-container">
            <a href="teacher_dashboard.php" class="logo">
                <i class="fas fa-chalkboard-teacher"></i>
                <h1>EduAssign Teacher</h1>
            </a>
            <nav>
                <a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="teacher_assignments.php"><i class="fas fa-tasks"></i> My Assignments</a>
                <a href="teacher_submissions.php"><i class="fas fa-file-alt"></i> Submissions</a>
                <a href="teacher_approve_students.php"><i class="fas fa-user-check"></i> Approve Students</a>
                <a href="teacher_students.php"><i class="fas fa-users"></i> My Students</a>
                <a href="teacher_profile.php"><i class="fas fa-user"></i> Your Profile</a>
            </nav>
        </div>
    </header>
    <main class="dashboard-main">
        <div class="dashboard-title">
            <a href="teacher_submissions.php" class="back-link">
                <i class="fas fa-arrow-left"></i> Back to Submissions
            </a>
            View Submission
        </div>
        
        <?php if (isset($error)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($success)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($submission): ?>
            <div class="card">
                <div class="submission-header">
                    <h2><?php echo htmlspecialchars($submission['title']); ?></h2>
                    <div class="submission-meta">
                        <span class="status-badge status-<?php echo $submission['status']; ?>">
                            <?php echo ucfirst($submission['status']); ?>
                        </span>
                    </div>
                </div>
                
                <div class="submission-details">
                    <div class="detail-row">
                        <strong>Student:</strong> 
                        <?php echo htmlspecialchars($submission['student_name']); ?>
                        (<?php echo htmlspecialchars($submission['student_email']); ?>)
                    </div>
                    <div class="detail-row">
                        <strong>Course:</strong> 
                        <?php echo htmlspecialchars($submission['student_course'] ?? 'N/A'); ?>
                    </div>
                    <div class="detail-row">
                        <strong>Assignment:</strong> 
                        <?php echo htmlspecialchars($submission['title']); ?>
                    </div>
                    <div class="detail-row">
                        <strong>Due Date:</strong> 
                        <?php echo date('M j, Y g:i A', strtotime($submission['due_date'])); ?>
                    </div>
                    <div class="detail-row">
                        <strong>Submitted At:</strong> 
                        <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?>
                        <?php if (strtotime($submission['submitted_at']) > strtotime($submission['due_date'])): ?>
                            <span class="late-badge">LATE</span>
                        <?php endif; ?>
                    </div>
                    <?php if ($submission['grade'] !== null): ?>
                        <div class="detail-row">
                            <strong>Grade:</strong> 
                            <?php echo $submission['grade']; ?>/<?php echo $submission['max_score']; ?>
                        </div>
                    <?php endif; ?>
                    <?php if (!empty($submission['feedback'])): ?>
                        <div class="detail-row">
                            <strong>Feedback:</strong>
                            <div class="feedback-text"><?php echo nl2br(htmlspecialchars($submission['feedback'])); ?></div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="card">
                <h3>Submission Content</h3>
                
                <?php if (!empty($submission['content'])): ?>
                    <div class="submission-content">
                        <h4>Text Content:</h4>
                        <div class="content-text">
                            <?php echo nl2br(htmlspecialchars($submission['content'])); ?>
                        </div>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($submission['file_path'])): ?>
                    <div class="submission-files">
                        <h4>Submitted Files:</h4>
                        <?php
                        $files = explode(',', $submission['file_path']);
                        foreach ($files as $file) {
                            $file = trim($file);
                            if (!empty($file) && file_exists($file)) {
                                $fileName = basename($file);
                                $fileSize = filesize($file);
                                $fileSizeFormatted = number_format($fileSize / 1024, 2) . ' KB';
                                ?>
                                <div class="file-item">
                                    <div class="file-info">
                                        <i class="fas fa-file"></i>
                                        <span class="file-name"><?php echo htmlspecialchars($fileName); ?></span>
                                        <span class="file-size">(<?php echo $fileSizeFormatted; ?>)</span>
                                    </div>
                                    <a href="<?php echo htmlspecialchars($file); ?>" target="_blank" class="btn btn-primary btn-sm">
                                        <i class="fas fa-download"></i> Download
                                    </a>
                                </div>
                                <?php
                            }
                        }
                        ?>
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($submission['status'] !== 'graded'): ?>
                <div class="card">
                    <h3>Grade Submission</h3>
                    <form method="post" class="grade-form">
                        <input type="hidden" name="grade_submission" value="1">
                        
                        <div class="form-group">
                            <label for="grade">Grade (out of <?php echo $submission['max_score']; ?>):</label>
                            <input type="number" name="grade" id="grade" min="0" max="<?php echo $submission['max_score']; ?>" step="0.01" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="feedback">Feedback (optional):</label>
                            <textarea name="feedback" id="feedback" rows="6" placeholder="Enter feedback for the student..."></textarea>
                        </div>
                        
                        <div class="form-actions">
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-star"></i> Submit Grade
                            </button>
                            <a href="teacher_submissions.php" class="btn btn-secondary">
                                <i class="fas fa-times"></i> Cancel
                            </a>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 EduAssign. All rights reserved.</p>
    </footer>
</body>
</html>
