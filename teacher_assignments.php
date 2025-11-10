<?php
require_once 'includes/auth.php';
require_once 'includes/assignment_helper.php';

// Require teacher role
$auth->requireRole('teacher');

// Get teacher data
$teacher = $auth->getCurrentUser();
$teacher_id = $teacher['id'];

// Get teacher assignments using AssignmentHelper
$assignments = $assignmentHelper->getTeacherAssignments($teacher_id);

// Handle assignment deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_assignment'])) {
    $assignment_id = intval($_POST['assignment_id']);
    
    try {
        // Check if assignment belongs to this teacher
        $stmt = $pdo->prepare("SELECT id FROM assignments WHERE id = ? AND teacher_id = ?");
        $stmt->execute([$assignment_id, $teacher_id]);
        
        if ($stmt->fetch()) {
            // Delete assignment (cascade will handle related records)
            $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$assignment_id, $teacher_id]);
            
            // Log activity
            $auth->logActivity($teacher_id, 'delete_assignment', "Deleted assignment ID: {$assignment_id}");
            
            $success_message = "Assignment deleted successfully!";
        } else {
            $error_message = "Assignment not found or you don't have permission to delete it.";
        }
    } catch (PDOException $e) {
        $error_message = "Error deleting assignment: " . $e->getMessage();
    }
    
    // Redirect to refresh the page
    header("Location: teacher_assignments.php");
    exit();
}

// Handle logout
if (isset($_POST['logout'])) {
    $auth->logout();
    header("Location: teacher_login.php");
    exit();
}

$stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE teacher_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$assigned_count = $stmt->fetchColumn();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Assignments - EduAssign Teacher</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <script src="js/notifications.js" defer></script>
    <style>
        /* Additional styles specific to assignments page */
        .assignments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .assignment-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
            padding: 2rem;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 32px rgba(102,126,234,0.15);
        }
        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        .assignment-title {
            font-size: 1.4rem;
            font-weight: 700;
            color: #333;
            margin: 0;
            flex: 1;
        }
        .assignment-status {
            padding: 0.3rem 0.8rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background: #f8d7da;
            color: #721c24;
        }
        .assignment-details {
            margin-bottom: 1.5rem;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }
        .detail-label {
            color: #666;
            font-weight: 500;
        }
        .detail-value {
            color: #333;
            font-weight: 600;
        }
        .assignment-description {
            color: #666;
            margin: 1rem 0;
            font-size: 0.95rem;
            line-height: 1.5;
        }
        .assignment-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }
        .stat-item {
            text-align: center;
            flex: 1;
            padding: 0.8rem;
            background: #f8f9ff;
            border-radius: 10px;
        }
        .assignment-actions {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }
        .add-assignment-btn {
            display: inline-block;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem 2rem;
            border-radius: 25px;
            text-decoration: none;
            font-weight: 600;
            margin-bottom: 2rem;
            transition: transform 0.3s;
        }
        .add-assignment-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 8px 20px rgba(102,126,234,0.3);
        }
        .no-assignments {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
        }
        .no-assignments i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        .no-assignments h3 {
            color: #666;
            margin-bottom: 0.5rem;
        }
        .no-assignments p {
            color: #999;
        }
        .alert {
            padding: 1rem;
            border-radius: 10px;
            margin-bottom: 1.5rem;
            font-weight: 500;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
    </style>
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
                <a href="teacher_assignments.php" class="active"><i class="fas fa-tasks"></i> My Assignments</a>
                <a href="teacher_submissions.php"><i class="fas fa-file-alt"></i> Submissions</a>
                <a href="teacher_approve_students.php"><i class="fas fa-user-check"></i> Approve Students</a>
                <a href="teacher_students.php"><i class="fas fa-users"></i> My Students</a>
                <a href="teacher_profile.php"><i class="fas fa-user"></i> Your Profile</a>
            </nav>
        </div>
    </header>
    <main class="dashboard-main">
        <div class="dashboard-title">My Assignments</div>
        
        <?php if (isset($success_message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i> <?php echo $success_message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($error_message)): ?>
            <div class="alert alert-danger">
                <i class="fas fa-exclamation-triangle"></i> <?php echo $error_message; ?>
            </div>
        <?php endif; ?>
        
        <a href="teacher_add_assignment.php" class="add-assignment-btn">
            <i class="fas fa-plus"></i> Create New Assignment
        </a>
        
        <?php if (empty($assignments)): ?>
            <div class="no-assignments">
                <i class="fas fa-tasks"></i>
                <h3>No Assignments Created</h3>
                <p>You haven't created any assignments yet. Click "Create New Assignment" to get started.</p>
            </div>
        <?php else: ?>
            <div class="assignments-grid">
                <?php foreach ($assignments as $assignment): ?>
                    <div class="assignment-card">
                        <div class="assignment-header">
                            <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                            <span class="assignment-status status-<?php echo $assignment['status']; ?>">
                                <?php echo ucfirst($assignment['status']); ?>
                            </span>
                        </div>
                        
                        <div class="assignment-details">
                            <div class="detail-row">
                                <span class="detail-label">Subject:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($assignment['subject']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Due Date:</span>
                                <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($assignment['due_date'])); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Max Score:</span>
                                <span class="detail-value"><?php echo $assignment['max_score']; ?> points</span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Period:</span>
                                <span class="detail-value">
                                    <?php echo date('M j, Y', strtotime($assignment['assignment_period_start'])); ?> - 
                                    <?php echo date('M j, Y', strtotime($assignment['assignment_period_end'])); ?>
                                </span>
                            </div>
                        </div>
                        
                        <div class="assignment-description">
                            <?php echo nl2br(htmlspecialchars($assignment['description'])); ?>
                        </div>
                        
                        <div class="assignment-stats">
                            <span class="stat-item">
                                <i class="fas fa-users"></i> <?php echo $assignment['total_students'] ?? 0; ?> Students
                            </span>
                            <span class="stat-item">
                                <i class="fas fa-file-upload"></i> <?php echo $assignment['submissions_count'] ?? 0; ?> Submissions
                            </span>
                            <span class="stat-item">
                                <i class="fas fa-check-circle"></i> <?php echo $assignment['graded_count'] ?? 0; ?> Graded
                            </span>
                        </div>
                        
                        <div class="assignment-actions">
                            <a href="teacher_edit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-secondary">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                            <a href="teacher_submissions.php?assignment_id=<?php echo $assignment['id']; ?>" class="btn btn-primary">
                                <i class="fas fa-eye"></i> View Submissions
                            </a>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="assignment_id" value="<?php echo $assignment['id']; ?>">
                                <button type="submit" name="delete_assignment" class="btn btn-danger" 
                                        onclick="return confirm('Are you sure you want to delete this assignment? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </main>
    <footer>
    <p>&copy; 2025 EduAssign. All rights reserved.</p>
</footer>
</body>
</html>