<?php
require_once 'includes/auth.php';
require_once 'includes/assignment_helper.php';

// Require student role
$auth->requireRole('student');

// Get student data
$student = $auth->getCurrentUser();
$student_id = $student['id'];

// Get student assignments using AssignmentHelper
$assignments = $assignmentHelper->getStudentAssignments($student_id);

// Handle logout
if (isset($_POST['logout'])) {
    $auth->logout();
    header("Location: student_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>My Assignments - EduAssign Student</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <script src="js/notifications.js" defer></script>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            color: #333;
        }
        header {
            background: rgba(255,255,255,0.97);
            box-shadow: 0 2px 25px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .logo i {
            font-size: 2rem;
            color: #667eea;
        }
        .logo h1 {
            font-size: 1.4rem;
            color: #333;
            font-weight: 700;
        }
        nav {
            display: flex;
            gap: 2rem;
        }
        nav a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            transition: all 0.3s;
        }
        nav a.active, nav a:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
            margin-left: 1rem;
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: #c0392b;
        }
        .main {
            margin-top: 90px;
            padding: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        h2 {
            color: #333;
            margin-bottom: 2rem;
        }
        .assignments-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(350px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .assignment-card {
            background: white;
            border-radius: 15px;
            padding: 1.5rem;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
            border: 1px solid rgba(102,126,234,0.08);
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .assignment-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 32px rgba(102,126,234,0.12);
        }
        .assignment-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 1rem;
        }
        .assignment-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin: 0;
        }
        .period-status {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-active {
            background: #d4edda;
            color: #155724;
        }
        .status-upcoming {
            background: #fff3cd;
            color: #856404;
        }
        .status-expired {
            background: #f8d7da;
            color: #721c24;
        }
        .status-submitted {
            background: #cce5ff;
            color: #004085;
        }
        .status-graded {
            background: #d4edda;
            color: #155724;
        }
        .btn-success {
            background: #28a745;
            color: white;
        }
        .assignment-details {
            margin-bottom: 1rem;
        }
        .detail-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
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
            font-size: 0.9rem;
            line-height: 1.5;
            margin-bottom: 1rem;
        }
        .assignment-actions {
            display: flex;
            gap: 0.5rem;
        }
        .btn {
            padding: 0.5rem 1rem;
            border: none;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.3);
        }
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #dee2e6;
        }
        .btn-secondary:hover {
            background: #e9ecef;
        }
        .btn-disabled {
            background: #6c757d;
            color: white;
            cursor: not-allowed;
        }
        .btn-disabled:hover {
            transform: none;
            box-shadow: none;
        }
        .no-assignments {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
        }
        .no-assignments i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        .no-assignments h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        .no-assignments p {
            color: #666;
        }
        @media (max-width: 768px) {
            .assignments-grid {
                grid-template-columns: 1fr;
            }
            .assignment-header {
                flex-direction: column;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="student_dashboard.php" class="logo">
                <i class="fas fa-user-graduate"></i>
                <h1>EduAssign Student</h1>
            </a>
            <nav>
                <a href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="student_assignments.php" class="active"><i class="fas fa-tasks"></i> Assignments</a>
                <a href="student_grades.php"><i class="fas fa-chart-line"></i> Grades</a>
                <a href="student_profile.php"><i class="fas fa-user"></i> Profile</a>
                <form method="post" style="display:inline;">
                    <button type="submit" name="logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="main">
        <h2><i class="fas fa-tasks"></i> My Assignments</h2>
        
        <?php if (empty($assignments)): ?>
            <div class="no-assignments">
                <i class="fas fa-inbox"></i>
                <h3>No Assignments Available</h3>
                <p>You don't have any assignments at the moment. Check back later for new assignments.</p>
            </div>
        <?php else: ?>
            <div class="assignments-grid">
                <?php foreach ($assignments as $assignment): ?>
                    <div class="assignment-card">
                        <div class="assignment-header">
                            <h3 class="assignment-title"><?php echo htmlspecialchars($assignment['title']); ?></h3>
                            <div>
                                <span class="period-status status-<?php echo $assignment['period_status']; ?>">
                                    <?php echo ucfirst($assignment['period_status']); ?>
                                </span>
                                <?php if ($assignment['submission_id']): ?>
                                    <span class="period-status status-<?php echo $assignment['submission_status']; ?>" style="margin-left: 0.5rem;">
                                        <?php 
                                        if ($assignment['submission_status'] === 'graded') {
                                            echo 'Graded (' . $assignment['submission_grade'] . '/' . $assignment['max_score'] . ')';
                                        } else {
                                            echo 'Submitted';
                                        }
                                        ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <div class="assignment-details">
                            <div class="detail-row">
                                <span class="detail-label">Subject:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($assignment['subject']); ?></span>
                            </div>
                            <div class="detail-row">
                                <span class="detail-label">Teacher:</span>
                                <span class="detail-value"><?php echo htmlspecialchars($assignment['teacher_name'] ?? 'Unknown'); ?></span>
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
                        
                        <div class="assignment-actions">
                            <?php if ($assignment['submission_id']): ?>
                                <!-- Already submitted -->
                                <span class="btn btn-success" style="background: #28a745; cursor: default;">
                                    <i class="fas fa-check-circle"></i> 
                                    <?php echo ($assignment['submission_status'] === 'graded') ? 'Graded' : 'Submitted'; ?>
                                </span>
                                <a href="student_view_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i> View Submission
                                </a>
                            <?php elseif ($assignment['period_status'] === 'active'): ?>
                                <!-- Can submit -->
                                <a href="student_submit_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-primary">
                                    <i class="fas fa-upload"></i> Submit Assignment
                                </a>
                                <a href="student_view_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            <?php else: ?>
                                <!-- Cannot submit (upcoming or expired) -->
                                <button class="btn btn-disabled" disabled>
                                    <i class="fas fa-clock"></i> 
                                    <?php echo $assignment['period_status'] === 'upcoming' ? 'Not Available Yet' : 'Assignment Closed'; ?>
                                </button>
                                <a href="student_view_assignment.php?id=<?php echo $assignment['id']; ?>" class="btn btn-secondary">
                                    <i class="fas fa-eye"></i> View Details
                                </a>
                            <?php endif; ?>
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