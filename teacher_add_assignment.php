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
$teacher_subject = $teacher['subject'];
$teacher_course = $teacher['course'];

$success_message = '';
$error_message = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_assignment'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subject = trim($_POST['subject']);
    $due_date = $_POST['due_date'];
    $max_score = intval($_POST['max_score']);
    $assignment_period_start = $_POST['assignment_period_start'];
    $assignment_period_end = $_POST['assignment_period_end'];
    
    // Validation
    if (empty($title) || empty($description) || empty($subject) || empty($due_date) || 
        empty($assignment_period_start) || empty($assignment_period_end)) {
        $error_message = "All fields are required.";
    } elseif ($max_score <= 0) {
        $error_message = "Max score must be greater than 0.";
    } elseif (strtotime($assignment_period_start) >= strtotime($assignment_period_end)) {
        $error_message = "Assignment period end must be after start date.";
    } elseif (strtotime($due_date) <= strtotime($assignment_period_end)) {
        $error_message = "Due date must be after assignment period end.";
    } else {
        try { 
            // Insert assignment
            $stmt = $pdo->prepare("
                INSERT INTO assignments (title, description, subject, teacher_id, due_date, max_score, assignment_period_start, assignment_period_end, status) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmt->execute([$title, $description, $subject, $teacher_id, $due_date, $max_score, $assignment_period_start, $assignment_period_end]);
            
            $assignment_id = $pdo->lastInsertId();
            
            // Distribute assignment to students
            $distribution_result = $assignmentHelper->distributeAssignment($assignment_id);
            
            // Send notifications to students about new assignment
            $notificationSystem->notifyNewAssignment($assignment_id, $title, $teacher['name'], $due_date);
            
            // Send notification to teacher about successful creation
            $notificationSystem->createNotification(
                $teacher_id,
                "Assignment Created Successfully",
                "You have successfully created the assignment '{$title}' for {$subject}. The assignment is now active and students have been notified.",
                'assignment',
                $assignment_id,
                'assignment',
                'medium'
            );
            
            // Log activity
            $auth->logActivity($teacher_id, 'add_assignment', "Added assignment: {$title} for subject: {$subject}");
            
            if ($distribution_result['success']) {
                $success_message = "Assignment created successfully! You and your students have been notified. " . $distribution_result['message'];
            } else {
                $success_message = "Assignment created successfully! You and your students have been notified. " . $distribution_result['message'];
            }
            
            // Clear form data on success
            $_POST = array();
            
        } catch (PDOException $e) {
            $error_message = "Error creating assignment: " . $e->getMessage();
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Assignment - EduAssign Teacher</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
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
        <div class="dashboard-title">Create Assignment</div>
        
        <?php if ($success_message): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error_message); ?>
            </div>
        <?php endif; ?>
        
        <div class="card" style="max-width: 800px; margin: 0 auto;">
            <div class="info-box" style="background: #f8f9ff; border: 1px solid #e0e6ed; border-radius: 10px; padding: 1.5rem; margin-bottom: 2rem;">
                <h4 style="margin: 0 0 0.8rem 0; color: #667eea;"><i class="fas fa-info-circle"></i> Assignment Information</h4>
                <p style="margin: 0; color: #666; line-height: 1.5;">Create a new assignment for your students. Fill in all required fields and set appropriate dates for the assignment period and due date.</p>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label for="title">Assignment Title *</label>
                    <input type="text" id="title" name="title" class="form-control" value="<?php echo htmlspecialchars($_POST['title'] ?? ''); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" class="form-control" rows="4" required><?php echo htmlspecialchars($_POST['description'] ?? ''); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" class="form-control" value="<?php echo htmlspecialchars($_POST['subject'] ?? $teacher_subject); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_score">Max Score *</label>
                        <input type="number" id="max_score" name="max_score" class="form-control" value="<?php echo htmlspecialchars($_POST['max_score'] ?? '100'); ?>" min="1" max="1000" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="assignment_period_start">Assignment Period Start *</label>
                        <input type="datetime-local" id="assignment_period_start" name="assignment_period_start" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['assignment_period_start'] ?? ''); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="assignment_period_end">Assignment Period End *</label>
                        <input type="datetime-local" id="assignment_period_end" name="assignment_period_end" class="form-control"
                               value="<?php echo htmlspecialchars($_POST['assignment_period_end'] ?? ''); ?>" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="due_date">Due Date *</label>
                    <input type="datetime-local" id="due_date" name="due_date" class="form-control"
                           value="<?php echo htmlspecialchars($_POST['due_date'] ?? ''); ?>" required>
                </div>
                
                <div class="form-actions" style="display: flex; gap: 1rem; justify-content: flex-end; margin-top: 2rem; padding-top: 2rem; border-top: 1px solid #e0e6ed;">
                    <a href="teacher_assignments.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Assignments
                    </a>
                    <button type="submit" name="add_assignment" class="btn btn-primary">
                        <i class="fas fa-plus"></i> Create Assignment
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 EduAssign. All rights reserved.</p>
    </footer>
    
    <script>
        // Set default dates
        document.addEventListener('DOMContentLoaded', function() {
            const now = new Date();
            const tomorrow = new Date(now);
            tomorrow.setDate(tomorrow.getDate() + 1);
            
            const nextWeek = new Date(now);
            nextWeek.setDate(nextWeek.getDate() + 7);
            
            const twoWeeks = new Date(now);
            twoWeeks.setDate(twoWeeks.getDate() + 14);
            
            // Format dates for datetime-local input
            function formatDate(date) {
                return date.toISOString().slice(0, 16);
            }
            
            // Set default values if not already set
            if (!document.getElementById('assignment_period_start').value) {
                document.getElementById('assignment_period_start').value = formatDate(tomorrow);
            }
            if (!document.getElementById('assignment_period_end').value) {
                document.getElementById('assignment_period_end').value = formatDate(nextWeek);
            }
            if (!document.getElementById('due_date').value) {
                document.getElementById('due_date').value = formatDate(twoWeeks);
            }
        });
    </script>
</body>
</html>
