<?php
require_once 'includes/auth.php';

// Require student role
$auth->requireRole('student');

// Get student data
$student = $auth->getCurrentUser();
$student_id = $student['id'];

// Get student's graded submissions with assignment details
try {
    $stmt = $pdo->prepare("
        SELECT s.*, a.title as assignment_title, a.subject, a.max_score, a.due_date,
               u.name as teacher_name, a.created_at as assignment_created
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.id
        LEFT JOIN users u ON a.teacher_id = u.id
        WHERE s.student_id = ? AND s.status = 'graded'
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$student_id]);
    $graded_submissions = $stmt->fetchAll();
    
    // Get all submissions (graded and ungraded) for statistics
    $stmt = $pdo->prepare("
        SELECT s.*, a.title as assignment_title, a.subject, a.max_score, a.due_date,
               u.name as teacher_name
        FROM submissions s
        JOIN assignments a ON s.assignment_id = a.id
        LEFT JOIN users u ON a.teacher_id = u.id
        WHERE s.student_id = ?
        ORDER BY s.submitted_at DESC
    ");
    $stmt->execute([$student_id]);
    $all_submissions = $stmt->fetchAll();
    
    // Calculate statistics
    $total_submissions = count($all_submissions);
    $graded_count = count($graded_submissions);
    $pending_count = $total_submissions - $graded_count;
    
    // Calculate average grade and performance metrics
    $total_earned = 0;
    $total_possible = 0;
    $grade_distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
    
    foreach ($graded_submissions as $submission) {
        $total_earned += $submission['grade'];
        $total_possible += $submission['max_score'];
        
        // Calculate letter grade
        $percentage = ($submission['grade'] / $submission['max_score']) * 100;
        if ($percentage >= 90) $grade_distribution['A']++;
        elseif ($percentage >= 80) $grade_distribution['B']++;
        elseif ($percentage >= 70) $grade_distribution['C']++;
        elseif ($percentage >= 60) $grade_distribution['D']++;
        else $grade_distribution['F']++;
    }
    
    $overall_percentage = $total_possible > 0 ? round(($total_earned / $total_possible) * 100, 1) : 0;
    $average_grade = $graded_count > 0 ? round($total_earned / $graded_count, 1) : 0;
    
} catch (PDOException $e) {
    $graded_submissions = [];
    $all_submissions = [];
    $total_submissions = 0;
    $graded_count = 0;
    $pending_count = 0;
    $overall_percentage = 0;
    $average_grade = 0;
    $grade_distribution = ['A' => 0, 'B' => 0, 'C' => 0, 'D' => 0, 'F' => 0];
}

// Function to get letter grade from percentage
function getLetterGrade($percentage) {
    if ($percentage >= 90) return 'A';
    elseif ($percentage >= 80) return 'B';
    elseif ($percentage >= 70) return 'C';
    elseif ($percentage >= 60) return 'D';
    else return 'F';
}

// Function to get grade color
function getGradeColor($percentage) {
    if ($percentage >= 90) return '#28a745'; // Green
    elseif ($percentage >= 80) return '#17a2b8'; // Blue
    elseif ($percentage >= 70) return '#ffc107'; // Yellow
    elseif ($percentage >= 60) return '#fd7e14'; // Orange
    else return '#dc3545'; // Red
}

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
    <title>My Grades - EduAssign Student</title>
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
            color: white;
            margin-bottom: 2rem;
            font-size: 2rem;
            font-weight: 700;
        }
        .grade-summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .summary-card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
            text-align: center;
            transition: transform 0.3s, box-shadow 0.3s;
        }
        .summary-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 16px 32px rgba(102,126,234,0.12);
        }
        .summary-card i {
            font-size: 2.5rem;
            margin-bottom: 1rem;
            color: #667eea;
        }
        .summary-card h3 {
            font-size: 2rem;
            margin: 0.5rem 0;
            color: #333;
        }
        .summary-card p {
            color: #666;
            font-size: 1rem;
        }
        .overall-grade {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        .overall-grade i {
            color: white;
        }
        .overall-grade h3 {
            color: white;
            font-size: 2.5rem;
        }
        .overall-grade p {
            color: rgba(255,255,255,0.9);
        }
        .grade-distribution {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
            margin-bottom: 2rem;
        }
        .distribution-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
            text-align: center;
        }
        .distribution-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(100px, 1fr));
            gap: 1rem;
            text-align: center;
        }
        .grade-item {
            padding: 1rem;
            border-radius: 10px;
            background: #f8f9fa;
        }
        .grade-letter {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
        }
        .grade-count {
            font-size: 1.2rem;
            color: #666;
            margin-top: 0.5rem;
        }
        .grades-table {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
            overflow-x: auto;
        }
        .table-title {
            font-size: 1.3rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1.5rem;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 1rem;
        }
        .table th {
            background: #f8f9fa;
            padding: 1rem;
            text-align: left;
            font-weight: 600;
            color: #333;
            border-bottom: 2px solid #dee2e6;
        }
        .table td {
            padding: 1rem;
            border-bottom: 1px solid #dee2e6;
            color: #333;
        }
        .table tr:hover {
            background: #f8f9fa;
        }
        .grade-display {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        .grade-score {
            font-weight: 700;
            font-size: 1.1rem;
        }
        .grade-percentage {
            padding: 0.2rem 0.6rem;
            border-radius: 15px;
            font-size: 0.8rem;
            font-weight: 600;
            color: white;
        }
        .grade-letter-badge {
            padding: 0.3rem 0.7rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 700;
            color: white;
            margin-left: 0.5rem;
        }
        .no-grades {
            text-align: center;
            padding: 3rem;
            background: white;
            border-radius: 15px;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
        }
        .no-grades i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
        }
        .no-grades h3 {
            color: #333;
            margin-bottom: 0.5rem;
        }
        .no-grades p {
            color: #666;
        }
        .subject-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            background: #e9ecef;
            color: #495057;
        }
        .teacher-name {
            color: #6c757d;
            font-size: 0.9rem;
        }
        .date-display {
            color: #6c757d;
            font-size: 0.9rem;
        }
        @media (max-width: 768px) {
            .grade-summary {
                grid-template-columns: 1fr;
            }
            .distribution-grid {
                grid-template-columns: repeat(5, 1fr);
            }
            .main {
                padding: 1rem;
            }
            .table {
                font-size: 0.9rem;
            }
            .table th,
            .table td {
                padding: 0.7rem 0.5rem;
            }
        }
        @media (max-width: 480px) {
            .distribution-grid {
                grid-template-columns: repeat(3, 1fr);
            }
            .grade-summary {
                gap: 1rem;
            }
            .summary-card {
                padding: 1.5rem;
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
                <a href="student_assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
                <a href="student_grades.php" class="active"><i class="fas fa-chart-line"></i> Grades</a>
                <a href="student_profile.php"><i class="fas fa-user"></i> Profile</a>
                <form method="post" style="display:inline;">
                    <button type="submit" name="logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="main">
        <h2><i class="fas fa-chart-line"></i> My Grades & Performance</h2>
        
        <!-- Grade Summary Cards -->
        <div class="grade-summary">
            <div class="summary-card overall-grade">
                <i class="fas fa-trophy"></i>
                <h3><?php echo $overall_percentage; ?>%</h3>
                <p>Overall Grade</p>
                <p style="font-size: 1.1rem; font-weight: 600;">
                    <?php echo getLetterGrade($overall_percentage); ?> Grade
                </p>
            </div>
            <div class="summary-card">
                <i class="fas fa-check-circle"></i>
                <h3><?php echo $graded_count; ?></h3>
                <p>Graded Assignments</p>
            </div>
            <div class="summary-card">
                <i class="fas fa-clock"></i>
                <h3><?php echo $pending_count; ?></h3>
                <p>Pending Grades</p>
            </div>
            <div class="summary-card">
                <i class="fas fa-star"></i>
                <h3><?php echo $average_grade; ?></h3>
                <p>Average Score</p>
            </div>
        </div>

        <!-- Grade Distribution -->
        <?php if ($graded_count > 0): ?>
        <div class="grade-distribution">
            <div class="distribution-title">
                <i class="fas fa-chart-bar"></i> Grade Distribution
            </div>
            <div class="distribution-grid">
                <?php foreach ($grade_distribution as $letter => $count): ?>
                <div class="grade-item">
                    <div class="grade-letter"><?php echo $letter; ?></div>
                    <div class="grade-count"><?php echo $count; ?></div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Detailed Grades Table -->
        <?php if (!empty($graded_submissions)): ?>
        <div class="grades-table">
            <div class="table-title">
                <i class="fas fa-list"></i> Detailed Grade Report
            </div>
            <div style="overflow-x: auto;">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Assignment</th>
                            <th>Subject</th>
                            <th>Teacher</th>
                            <th>Due Date</th>
                            <th>Submitted</th>
                            <th>Grade</th>
                            <th>Percentage</th>
                            <th>Letter Grade</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($graded_submissions as $submission): ?>
                        <?php 
                        $percentage = round(($submission['grade'] / $submission['max_score']) * 100, 1);
                        $letterGrade = getLetterGrade($percentage);
                        $gradeColor = getGradeColor($percentage);
                        ?>
                        <tr>
                            <td>
                                <strong><?php echo htmlspecialchars($submission['assignment_title']); ?></strong>
                            </td>
                            <td>
                                <span class="subject-badge">
                                    <?php echo htmlspecialchars($submission['subject']); ?>
                                </span>
                            </td>
                            <td class="teacher-name">
                                <?php echo htmlspecialchars($submission['teacher_name'] ?? 'Unknown'); ?>
                            </td>
                            <td class="date-display">
                                <?php echo date('M j, Y', strtotime($submission['due_date'])); ?>
                            </td>
                            <td class="date-display">
                                <?php echo date('M j, Y g:i A', strtotime($submission['submitted_at'])); ?>
                            </td>
                            <td>
                                <div class="grade-display">
                                    <span class="grade-score">
                                        <?php echo $submission['grade']; ?>/<?php echo $submission['max_score']; ?>
                                    </span>
                                </div>
                            </td>
                            <td>
                                <span class="grade-percentage" style="background-color: <?php echo $gradeColor; ?>;">
                                    <?php echo $percentage; ?>%
                                </span>
                            </td>
                            <td>
                                <span class="grade-letter-badge" style="background-color: <?php echo $gradeColor; ?>;">
                                    <?php echo $letterGrade; ?>
                                </span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="no-grades">
            <i class="fas fa-chart-line"></i>
            <h3>No Grades Available</h3>
            <p>You haven't received any grades yet. Complete and submit assignments to see your grades here.</p>
        </div>
        <?php endif; ?>
    </main>

    <footer>
        <p>&copy; 2025 EduAssign. All rights reserved.</p>
    </footer>
</body>
</html>
