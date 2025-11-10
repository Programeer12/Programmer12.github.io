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

// Get assignment ID from query string if provided
$assignment_id = isset($_GET['assignment_id']) ? intval($_GET['assignment_id']) : null;

// Get submissions for this teacher
$submissions = [];

try {
    if ($assignment_id) {
        // Get submissions for a specific assignment
        $stmt = $pdo->prepare("SELECT s.*, a.title as assignment_title, u.name as student_name, a.max_score 
                              FROM submissions s 
                              JOIN assignments a ON s.assignment_id = a.id 
                              JOIN users u ON s.student_id = u.id 
                              WHERE a.teacher_id = ? AND a.id = ? 
                              ORDER BY s.submitted_at DESC");
        $stmt->execute([$teacher_id, $assignment_id]);
    } else {
        // Get all submissions for this teacher
        $stmt = $pdo->prepare("SELECT s.*, a.title as assignment_title, u.name as student_name, a.max_score 
                              FROM submissions s 
                              JOIN assignments a ON s.assignment_id = a.id 
                              JOIN users u ON s.student_id = u.id 
                              WHERE a.teacher_id = ? 
                              ORDER BY s.submitted_at DESC");
        $stmt->execute([$teacher_id]);
    }
    
    $submissions = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = "Database error: " . $e->getMessage();
}

// Process grade submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['grade_submission'])) {
    $submission_id = $_POST['submission_id'] ?? 0;
    $grade = $_POST['grade'] ?? '';
    $feedback = $_POST['feedback'] ?? '';
    
    // Validate submission ID
    if (!$submission_id) {
        $error = "Invalid submission ID.";
    } 
    // Validate grade
    elseif (!is_numeric($grade) || $grade < 0) {
        $error = "Grade must be a positive number.";
    } 
    else {
        try {
            // Get the max score for this assignment
            $stmt = $pdo->prepare("SELECT a.max_score, s.student_id, a.id, a.title 
                                  FROM submissions s 
                                  JOIN assignments a ON s.assignment_id = a.id 
                                  WHERE s.id = ?");
            $stmt->execute([$submission_id]);
            $assignment_info = $stmt->fetch();
            
            if (!$assignment_info) {
                $error = "Submission not found.";
            } 
            // Validate grade against max score
            elseif ($grade > $assignment_info['max_score']) {
                $error = "Grade cannot exceed the maximum score of {$assignment_info['max_score']}.";
            } 
            else {
                // Update the submission with grade and feedback
                $stmt = $pdo->prepare("UPDATE submissions SET grade = ?, feedback = ?, status = 'graded' WHERE id = ?");
                $stmt->execute([$grade, $feedback, $submission_id]);
                
                // Get assignment and student info for notifications
                $stmt = $pdo->prepare("
                    SELECT s.student_id, a.id as assignment_id, a.title, a.max_score 
                    FROM submissions s 
                    JOIN assignments a ON s.assignment_id = a.id 
                    WHERE s.id = ?
                ");
                $stmt->execute([$submission_id]);
                $grading_info = $stmt->fetch();
                
                if ($grading_info) {
                    // Send notification to student about grade
                    $notificationSystem->notifyGradeReceived(
                        $grading_info['student_id'], 
                        $grading_info['title'], 
                        $grade, 
                        $grading_info['max_score']
                    );
                }
                
                // Log the activity
                $student_id = $assignment_info['student_id'];
                $assignment_id = $assignment_info['id'];
                $assignment_title = $assignment_info['title'];
                
                $description = "Teacher {$teacher['name']} graded assignment '{$assignment_title}' for student ID {$student_id}";
                $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                $stmt->execute([$teacher_id, "grade_submission", $description, $_SERVER['REMOTE_ADDR']]);
                
                $success = "Submission graded successfully and student notified.";
                
                // Refresh the submissions list
                if ($assignment_id) {
                    $stmt = $pdo->prepare("SELECT s.*, a.title as assignment_title, u.name as student_name, a.max_score 
                                          FROM submissions s 
                                          JOIN assignments a ON s.assignment_id = a.id 
                                          JOIN users u ON s.student_id = u.id 
                                          WHERE a.teacher_id = ? AND a.id = ? 
                                          ORDER BY s.submitted_at DESC");
                    $stmt->execute([$teacher_id, $assignment_id]);
                } else {
                    $stmt = $pdo->prepare("SELECT s.*, a.title as assignment_title, u.name as student_name, a.max_score 
                                          FROM submissions s 
                                          JOIN assignments a ON s.assignment_id = a.id 
                                          JOIN users u ON s.student_id = u.id 
                                          WHERE a.teacher_id = ? 
                                          ORDER BY s.submitted_at DESC");
                    $stmt->execute([$teacher_id]);
                }
                
                $submissions = $stmt->fetchAll();
            }
        } catch (PDOException $e) {
            $error = "Database error: " . $e->getMessage();
        }
    }
}

// Mark submission as viewed
if (isset($_GET['mark_viewed']) && is_numeric($_GET['mark_viewed'])) {
    $submission_id = intval($_GET['mark_viewed']);
    
    try {
        // Check if this submission belongs to an assignment by this teacher
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions s 
                              JOIN assignments a ON s.assignment_id = a.id 
                              WHERE s.id = ? AND a.teacher_id = ?");
        $stmt->execute([$submission_id, $teacher_id]);
        
        if ($stmt->fetchColumn() > 0) {
            // Update the submission to mark as viewed
            $stmt = $pdo->prepare("UPDATE submissions SET viewed = 1 WHERE id = ?");
            $stmt->execute([$submission_id]);
            
            $success = "Submission marked as viewed.";
            
            // Refresh the submissions list
            if ($assignment_id) {
                $stmt = $pdo->prepare("SELECT s.*, a.title as assignment_title, u.name as student_name, a.max_score 
                                      FROM submissions s 
                                      JOIN assignments a ON s.assignment_id = a.id 
                                      JOIN users u ON s.student_id = u.id 
                                      WHERE a.teacher_id = ? AND a.id = ? 
                                      ORDER BY s.submitted_at DESC");
                $stmt->execute([$teacher_id, $assignment_id]);
            } else {
                $stmt = $pdo->prepare("SELECT s.*, a.title as assignment_title, u.name as student_name, a.max_score 
                                      FROM submissions s 
                                      JOIN assignments a ON s.assignment_id = a.id 
                                      JOIN users u ON s.student_id = u.id 
                                      WHERE a.teacher_id = ? 
                                      ORDER BY s.submitted_at DESC");
                $stmt->execute([$teacher_id]);
            }
            
            $submissions = $stmt->fetchAll();
        } else {
            $error = "Invalid submission ID.";
        }
    } catch (PDOException $e) {
        $error = "Database error: " . $e->getMessage();
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
    <title>Teacher Submissions - EduAssign</title>
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
                <a href="teacher_submissions.php" class="active"><i class="fas fa-file-alt"></i> Submissions</a>
                <a href="teacher_approve_students.php"><i class="fas fa-user-check"></i> Approve Students</a>
                <a href="teacher_students.php"><i class="fas fa-users"></i> My Students</a>
                <a href="teacher_profile.php"><i class="fas fa-user"></i> Your Profile</a>
            </nav>
        </div>
    </header>
    <main class="dashboard-main">
        <div class="dashboard-title">Submissions</div>
        
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
        
        <div class="filter-section">
            <span class="filter-label">Filter by Assignment:</span>
            <select class="filter-select" onchange="filterByAssignment()">
                <option value="">All Assignments</option>
                <?php
                // Get all assignments for this teacher for the filter
                try {
                    $stmt = $pdo->prepare("SELECT id, title FROM assignments WHERE teacher_id = ? ORDER BY title");
                    $stmt->execute([$teacher_id]);
                    $teacher_assignments = $stmt->fetchAll();
                    foreach ($teacher_assignments as $assignment) {
                        $selected = ($assignment_id == $assignment['id']) ? 'selected' : '';
                        echo "<option value='{$assignment['id']}' $selected>" . htmlspecialchars($assignment['title']) . "</option>";
                    }
                } catch (PDOException $e) {
                    // Handle error silently
                }
                ?>
            </select>
        </div>
        
        <?php if (empty($submissions)): ?>
            <div class="card">
                <div style="text-align: center; padding: 3rem;">
                    <i class="fas fa-inbox" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <h3 style="color: #666;">No Submissions Found</h3>
                    <p>There are no submissions to display for the selected criteria.</p>
                </div>
            </div>
        <?php else: ?>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th>Student</th>
                        <th>Assignment</th>
                        <th>Submitted At</th>
                        <th>Status</th>
                        <th>Grade</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($submissions as $submission): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($submission['student_name']); ?></td>
                            <td><?php echo htmlspecialchars($submission['assignment_title']); ?></td>
                            <td><?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $submission['status']; ?>">
                                    <?php echo ucfirst($submission['status']); ?>
                                </span>
                            </td>
                            <td>
                                <?php if ($submission['grade'] !== null): ?>
                                    <strong><?php echo $submission['grade']; ?>/<?php echo $submission['max_score']; ?></strong>
                                <?php else: ?>
                                    <span style="color: #999;">Not graded</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="teacher_view_submission.php?id=<?php echo $submission['id']; ?>" class="btn btn-primary btn-sm">
                                    <i class="fas fa-eye"></i> View
                                </a>
                                <?php if ($submission['status'] !== 'graded'): ?>
                                    <button onclick="openGradeModal(<?php echo $submission['id']; ?>, '<?php echo htmlspecialchars($submission['student_name'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($submission['assignment_title'], ENT_QUOTES); ?>', <?php echo $submission['max_score']; ?>)" class="btn btn-warning btn-sm">
                                        <i class="fas fa-star"></i> Grade
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>

        <!-- Grade Modal -->
        <div id="gradeModal" class="modal">
            <div class="modal-content">
                <span class="close">&times;</span>
                <h3 id="gradeModalTitle">Grade Submission</h3>
                <form method="post">
                    <input type="hidden" name="submission_id" id="modalSubmissionId">
                    <input type="hidden" name="grade_submission" value="1">
                    
                    <div class="form-group">
                        <label for="grade">Grade:</label>
                        <input type="number" name="grade" id="grade" min="0" step="0.01" required>
                        <span id="maxScoreText"></span>
                    </div>
                    
                    <div class="form-group">
                        <label for="feedback">Feedback (optional):</label>
                        <textarea name="feedback" id="feedback" rows="4" placeholder="Enter feedback for the student..."></textarea>
                    </div>
                    
                    <div class="form-actions">
                        <button type="submit" class="btn btn-success">Submit Grade</button>
                        <button type="button" class="btn btn-secondary" onclick="closeGradeModal()">Cancel</button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 EduAssign. All rights reserved.</p>
    </footer>

    <script>
        let gradeModal = document.getElementById("gradeModal");
        let closeBtn = document.getElementsByClassName("close")[0];

        function openGradeModal(submissionId, studentName, assignmentTitle, maxScore) {
            document.getElementById("modalSubmissionId").value = submissionId;
            document.getElementById("gradeModalTitle").textContent = `Grade ${studentName}'s submission for "${assignmentTitle}"`;
            document.getElementById("maxScoreText").textContent = `(Max: ${maxScore})`;
            document.getElementById("grade").max = maxScore;
            document.getElementById("grade").value = "";
            document.getElementById("feedback").value = "";
            gradeModal.style.display = "block";
        }

        function closeGradeModal() {
            gradeModal.style.display = "none";
        }

        closeBtn.onclick = function() {
            closeGradeModal();
        }

        function filterByAssignment() {
            const select = document.querySelector('.filter-select');
            const assignmentId = select.value;
            if (assignmentId) {
                window.location.href = `teacher_submissions.php?assignment_id=${assignmentId}`;
            } else {
                window.location.href = 'teacher_submissions.php';
            }
        }

        window.onclick = function(event) {
            if (event.target == gradeModal) {
                closeGradeModal();
            }
        }
    </script>
</body>
</html>
