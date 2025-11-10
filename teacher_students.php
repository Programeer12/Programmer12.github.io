<?php
require_once 'includes/auth.php';

// Require teacher role
$auth->requireRole('teacher');

// Get teacher data
$teacher = $auth->getCurrentUser();
$teacher_name = $teacher['name'] ?? "Teacher";

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['activate'])) {
        $student_email = $_POST['student_email'];
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = 'active' WHERE email = ? AND role = 'student'");
            $stmt->execute([$student_email]);
            $success_message = "Student activated successfully.";
        } catch (PDOException $e) {
            $error_message = "Failed to activate student.";
        }
    } elseif (isset($_POST['deactivate'])) {
        $student_email = $_POST['student_email'];
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = 'inactive' WHERE email = ? AND role = 'student'");
            $stmt->execute([$student_email]);
            $success_message = "Student deactivated successfully.";
        } catch (PDOException $e) {
            $error_message = "Failed to deactivate student.";
        }
    } elseif (isset($_POST['delete'])) {
        $student_email = $_POST['student_email'];
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE email = ? AND role = 'student'");
            $stmt->execute([$student_email]);
            $success_message = "Student deleted successfully.";
        } catch (PDOException $e) {
            $error_message = "Failed to delete student.";
        }
    }
}

// Get all students in the teacher's course (both active and inactive)
try {
    $stmt = $pdo->prepare("SELECT id, name, email, status FROM users WHERE role = 'student' AND course = ? ORDER BY status DESC, name ASC");
    $stmt->execute([$teacher['course']]);
    $students_in_course = $stmt->fetchAll();
} catch (PDOException $e) {
    $students_in_course = [];
    $error_message = "Failed to load students.";
}

// Count active and inactive students
$active_count = 0;
$inactive_count = 0;
foreach ($students_in_course as $student) {
    if ($student['status'] === 'active') {
        $active_count++;
    } else {
        $inactive_count++;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Students - EduAssign Teacher</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        .status-badge {
            padding: 4px 8px;
            border-radius: 12px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        .status-active {
            background-color: #d4edda;
            color: #155724;
        }
        .status-inactive {
            background-color: #f8d7da;
            color: #721c24;
        }
        .student-stats {
            display: flex;
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-item {
            background: white;
            padding: 1rem;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-number {
            font-size: 1.5rem;
            font-weight: bold;
            color: #764ba2;
        }
        .stat-label {
            color: #666;
            font-size: 0.9rem;
        }
        .btn-sm {
            padding: 0.4rem 0.8rem;
            font-size: 0.8rem;
            margin: 0 2px;
        }
        .actions-cell {
            white-space: nowrap;
        }
        .confirm-delete {
            background-color: #dc3545 !important;
        }
        .no-students {
            text-align: center;
            color: #666;
            font-style: italic;
            padding: 2rem;
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
                <a href="teacher_assignments.php"><i class="fas fa-tasks"></i> My Assignments</a>
                <a href="teacher_submissions.php"><i class="fas fa-file-alt"></i> Submissions</a>
                <a href="teacher_approve_students.php"><i class="fas fa-user-check"></i> Approve Students</a>
                <a href="teacher_students.php" class="active"><i class="fas fa-users"></i> My Students</a>
                <a href="teacher_profile.php"><i class="fas fa-user"></i> Your Profile</a>
            </nav>
        </div>
    </header>

    <main class="dashboard-main">
        <div class="dashboard-title">My Students</div>
        <p style="margin-bottom:1rem;color:#666;">Course: <?php echo htmlspecialchars($teacher['course']); ?></p>
        
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
        
        <div class="student-stats">
            <div class="stat-item">
                <div class="stat-number"><?php echo $active_count; ?></div>
                <div class="stat-label">Active Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo $inactive_count; ?></div>
                <div class="stat-label">Inactive Students</div>
            </div>
            <div class="stat-item">
                <div class="stat-number"><?php echo count($students_in_course); ?></div>
                <div class="stat-label">Total Students</div>
            </div>
        </div>
        
        <?php if (empty($students_in_course)): ?>
            <div class="stat-card">
                <div class="no-students">
                    <i class="fas fa-users" style="font-size: 3rem; color: #ddd; margin-bottom: 1rem;"></i>
                    <h3>No students found</h3>
                    <p>No students are enrolled in your course yet.</p>
                </div>
            </div>
        <?php else: ?>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Name</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th><i class="fas fa-info-circle"></i> Status</th>
                        <th><i class="fas fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($students_in_course as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td>
                                <span class="status-badge status-<?php echo $student['status']; ?>">
                                    <?php if ($student['status'] === 'active'): ?>
                                        <i class="fas fa-check-circle"></i> Active
                                    <?php else: ?>
                                        <i class="fas fa-times-circle"></i> Inactive
                                    <?php endif; ?>
                                </span>
                            </td>
                            <td class="actions-cell">
                                <form method="post" style="display:inline;" onsubmit="return confirmAction(event, this)">
                                    <input type="hidden" name="student_email" value="<?php echo htmlspecialchars($student['email']); ?>">
                                    
                                    <?php if ($student['status'] === 'active'): ?>
                                        <button type="submit" name="deactivate" class="btn btn-warning btn-sm" title="Deactivate Student">
                                            <i class="fas fa-pause"></i> Deactivate
                                        </button>
                                    <?php else: ?>
                                        <button type="submit" name="activate" class="btn btn-success btn-sm" title="Activate Student">
                                            <i class="fas fa-play"></i> Activate
                                        </button>
                                    <?php endif; ?>
                                    
                                    <button type="submit" name="delete" class="btn btn-danger btn-sm" title="Delete Student" data-confirm="delete">
                                        <i class="fas fa-trash"></i> Delete
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
        
        <div style="margin-top: 2rem; text-align: center;">
            <a href="teacher_dashboard.php" class="btn btn-primary">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
        </div>
    </main>
    <footer>
        <p>&copy; 2025 EduAssign. All rights reserved.</p>
    </footer>
    
    <script>
        function confirmAction(event, form) {
            const action = event.submitter.name;
            const studentEmail = form.querySelector('input[name="student_email"]').value;
            
            let message = '';
            if (action === 'delete') {
                message = `Are you sure you want to permanently delete the student "${studentEmail}"? This action cannot be undone.`;
            } else if (action === 'deactivate') {
                message = `Are you sure you want to deactivate the student "${studentEmail}"?`;
            } else if (action === 'activate') {
                message = `Are you sure you want to activate the student "${studentEmail}"?`;
            }
            
            return confirm(message);
        }
    </script>
</body>
</html>
