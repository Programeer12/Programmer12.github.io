<?php
require_once 'includes/auth.php';
require_once 'includes/assignment_helper.php';

// Require student role
$auth->requireRole('student');

// Get student data
$student = $auth->getCurrentUser();
$student_id = $student['id'];

// Check if assignment ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: student_assignments.php");
    exit();
}

$assignment_id = $_GET['id'];

// Get assignment details
$stmt = $pdo->prepare("SELECT a.*, u.name as teacher_name FROM assignments a 
                      LEFT JOIN users u ON a.teacher_id = u.id 
                      WHERE a.id = ? AND a.status = 'active'");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch();

// Check if assignment exists
if (!$assignment) {
    header("Location: student_assignments.php");
    exit();
}

// Get student's submission for this assignment
$stmt = $pdo->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
$stmt->execute([$assignment_id, $student_id]);
$submission = $stmt->fetch();

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
    <title>View Assignment - EduAssign Student</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
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
        .card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
            margin-bottom: 2rem;
        }
        h2 {
            color: #333;
            margin-top: 0;
        }
        .assignment-details {
            margin-bottom: 2rem;
        }
        .detail-row {
            display: flex;
            margin-bottom: 0.5rem;
        }
        .detail-label {
            width: 150px;
            color: #666;
            font-weight: 500;
        }
        .detail-value {
            color: #333;
            font-weight: 600;
        }
        .submission-details {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
            margin-bottom: 2rem;
        }
        .submission-details h3 {
            margin-top: 0;
            color: #333;
        }
        .status-badge {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
        }
        .status-submitted {
            background: #cce5ff;
            color: #004085;
        }
        .status-graded {
            background: #d4edda;
            color: #155724;
        }
        .status-late {
            background: #fff3cd;
            color: #856404;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
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
        .period-status {
            display: inline-block;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin-left: 1rem;
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
        .feedback-section {
            background: #e8f4fd;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 1rem;
            margin-top: 1rem;
        }
        .feedback-section h4 {
            margin-top: 0;
            color: #004085;
        }
        .grade-display {
            font-size: 1.2rem;
            font-weight: 700;
            color: #155724;
            margin-bottom: 0.5rem;
        }
        .file-info {
            background: #f8f9fa;
            padding: 0.75rem;
            border-radius: 8px;
            margin-top: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .file-info i {
            font-size: 1.5rem;
            color: #6c757d;
        }
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                padding: 1rem;
            }
            nav {
                margin-top: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }
            .main {
                padding: 1rem;
            }
            .detail-row {
                flex-direction: column;
            }
            .detail-label {
                width: 100%;
                margin-bottom: 0.25rem;
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
        <div class="card">
            <h2>
                <i class="fas fa-file-alt"></i> 
                <?php echo htmlspecialchars($assignment['title']); ?>
                <?php 
                $period_status = '';
                if (strtotime('now') >= strtotime($assignment['assignment_period_start']) && 
                    strtotime('now') <= strtotime($assignment['assignment_period_end'])) {
                    $period_status = 'active';
                } elseif (strtotime('now') < strtotime($assignment['assignment_period_start'])) {
                    $period_status = 'upcoming';
                } else {
                    $period_status = 'expired';
                }
                ?>
                <span class="period-status status-<?php echo $period_status; ?>">
                    <?php echo ucfirst($period_status); ?>
                </span>
            </h2>
            
            <div class="assignment-details">
                <div class="detail-row">
                    <span class="detail-label">Subject:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($assignment['subject']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Teacher:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($assignment['teacher_name']); ?></span>
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
                <div class="detail-row">
                    <span class="detail-label">Description:</span>
                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></span>
                </div>
            </div>
            
            <?php if ($submission): ?>
                <div class="submission-details">
                    <h3>Your Submission</h3>
                    <div class="detail-row">
                        <span class="detail-label">Submitted On:</span>
                        <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?></span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">Status:</span>
                        <span class="detail-value">
                            <span class="status-badge status-<?php echo $submission['status']; ?>">
                                <?php echo ucfirst($submission['status']); ?>
                            </span>
                        </span>
                    </div>
                    <div class="detail-row">
                        <span class="detail-label">File:</span>
                        <span class="detail-value">
                            <div class="file-info">
                                <i class="fas fa-file"></i>
                                <?php echo basename($submission['file_path']); ?>
                            </div>
                        </span>
                    </div>
                    
                    <?php if ($submission['status'] === 'graded'): ?>
                        <div class="feedback-section">
                            <h4>Grading & Feedback</h4>
                            <div class="grade-display">
                                Score: <?php echo $submission['grade']; ?> / <?php echo $assignment['max_score']; ?>
                            </div>
                            <div class="feedback-text">
                                <strong>Teacher's Feedback:</strong><br>
                                <?php echo nl2br(htmlspecialchars($submission['feedback'] ?? 'No feedback provided')); ?>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <?php if ($period_status === 'active'): ?>
                    <a href="student_submit_assignment.php?id=<?php echo $assignment_id; ?>" class="btn btn-primary">
                        <i class="fas fa-edit"></i> Edit Submission
                    </a>
                <?php endif; ?>
            <?php else: ?>
                <div class="submission-details">
                    <h3>No Submission Yet</h3>
                    <p>You haven't submitted this assignment yet.</p>
                    
                    <?php if ($period_status === 'active'): ?>
                        <a href="student_submit_assignment.php?id=<?php echo $assignment_id; ?>" class="btn btn-primary">
                            <i class="fas fa-upload"></i> Submit Assignment
                        </a>
                    <?php elseif ($period_status === 'upcoming'): ?>
                        <p>This assignment is not available for submission yet.</p>
                    <?php else: ?>
                        <p>The submission period for this assignment has ended.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
            
            <div style="margin-top: 2rem;">
                <a href="student_assignments.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Back to Assignments
                </a>
            </div>
        </div>
    </main>
</body>
</html>