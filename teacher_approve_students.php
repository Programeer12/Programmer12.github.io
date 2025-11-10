<?php
require_once 'includes/auth.php';

// Require teacher role
$auth->requireRole('teacher');

$message = '';
$error = '';

// Get current teacher's subject
$teacher_subject = trim($_SESSION['subject'] ?? '');
$teacher_course = trim($_SESSION['course'] ?? '');

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $registration_id = $_POST['registration_id'];
        $result = $auth->approveRegistration($registration_id);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif (isset($_POST['reject'])) {
        $registration_id = $_POST['registration_id'];
        $result = $auth->rejectRegistration($registration_id);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Get pending student registrations for this teacher's subject
try {
    $stmt = $pdo->prepare("SELECT * FROM pending_registrations WHERE role = 'student' AND (
            (subject IS NOT NULL AND subject <> '' AND LOWER(subject) = LOWER(?))
            OR (course IS NOT NULL AND course <> '' AND LOWER(course) = LOWER(?))
        )
        ORDER BY created_at DESC");
    $stmt->execute([$teacher_subject, $teacher_course]);
    $pending_students = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $pending_students = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Students - EduAssign Teacher</title>
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
                <a href="teacher_approve_students.php" class="active"><i class="fas fa-user-check"></i> Approve Students</a>
                <a href="teacher_students.php"><i class="fas fa-users"></i> My Students</a>
                <a href="teacher_profile.php"><i class="fas fa-user"></i> Your Profile</a>
            </nav>
        </div>
    </header>

    <main class="dashboard-main">
        <div class="dashboard-title">Approve Students</div>
        <p style="margin-bottom:2rem;color:#666;">Subject: <?php echo htmlspecialchars($teacher_subject); ?></p>
        
        <?php if ($message): ?>
            <div class="success-message">
                <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
            </div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="error-message">
                <i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?>
            </div>
        <?php endif; ?>
        
        <div class="dashboard-stats" style="margin-bottom: 2rem;">
            <div class="stat-card">
                <i class="fas fa-clock"></i>
                <h3><?php echo count($pending_students); ?></h3>
                <p>Pending Registrations</p>
            </div>
        </div>
        
        <?php if (empty($pending_students)): ?>
            <div class="stat-card" style="text-align: center; padding: 3rem;">
                <i class="fas fa-users" style="font-size: 4rem; color: #ddd; margin-bottom: 1rem;"></i>
                <h3 style="color: #666;">No Pending Registrations</h3>
                <p>All student registrations for your subject have been processed.</p>
            </div>
        <?php else: ?>
            <table class="dashboard-table">
                <thead>
                    <tr>
                        <th><i class="fas fa-user"></i> Name</th>
                        <th><i class="fas fa-envelope"></i> Email</th>
                        <th><i class="fas fa-id-card"></i> Employee ID</th>
                        <th><i class="fas fa-graduation-cap"></i> Course</th>
                        <th><i class="fas fa-calendar"></i> Request Date</th>
                        <th><i class="fas fa-cogs"></i> Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($pending_students as $student): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($student['name']); ?></td>
                            <td><?php echo htmlspecialchars($student['email']); ?></td>
                            <td><?php echo htmlspecialchars($student['employee_id'] ?? 'N/A'); ?></td>
                            <td>
                                <span style="background: linear-gradient(135deg, #667eea, #764ba2); color: white; padding: 0.3rem 0.8rem; border-radius: 15px; font-size: 0.85rem;">
                                    <?php echo htmlspecialchars($student['course'] ?? $student['subject']); ?>
                                </span>
                            </td>
                            <td><?php echo date('M j, Y', strtotime($student['created_at'])); ?></td>
                            <td>
                                <form method="post" style="display: inline;">
                                    <input type="hidden" name="registration_id" value="<?php echo $student['id']; ?>">
                                    <button type="submit" name="approve" class="btn btn-primary" title="Approve Registration">
                                        <i class="fas fa-check"></i> Approve
                                    </button>
                                    <button type="submit" name="reject" class="btn btn-danger" title="Reject Registration">
                                        <i class="fas fa-times"></i> Reject
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
</body>
</html>