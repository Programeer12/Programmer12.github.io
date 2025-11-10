<?php
require_once 'includes/auth.php';

// Require admin role
$auth->requireRole('admin');

// Initialize statistics array
$report_stats = [];
$recent_activities = [];
$subject_stats = [];
$monthly_trends = [];
$avg_submission_time = 0;
$ontime_submission_rate = 0;
$course_completion_rates = [];

try {
    // Overall statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'");
    $stmt->execute();
    $report_stats['total_students'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'active'");
    $stmt->execute();
    $report_stats['total_teachers'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE status = 'active'");
    $stmt->execute();
    $report_stats['total_assignments'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE status IN ('submitted','graded')");
    $stmt->execute();
    $report_stats['completed_assignments'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare(
        "SELECT COUNT(*) FROM assignments a
        WHERE a.status = 'active'
        AND NOT EXISTS (
            SELECT 1 FROM submissions s WHERE s.assignment_id = a.id
        )"
    );
    $stmt->execute();
    $report_stats['pending_assignments'] = $stmt->fetchColumn();

    // Course-specific statistics
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active' AND course = 'BCA'");
    $stmt->execute();
    $report_stats['bca_students'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active' AND course LIKE 'BCom%'");
    $stmt->execute();
    $report_stats['bcom_students'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'active' AND course = 'BCA'");
    $stmt->execute();
    $report_stats['bca_teachers'] = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'active' AND course LIKE 'BCom%'");
    $stmt->execute();
    $report_stats['bcom_teachers'] = $stmt->fetchColumn();

    // Get recent activity logs
    $stmt = $pdo->prepare("SELECT a.*, u.name, u.role FROM activity_logs a 
                          LEFT JOIN users u ON a.user_id = u.id 
                          ORDER BY a.created_at DESC LIMIT 10");
    $stmt->execute();
    $recent_activities = $stmt->fetchAll();

    // Subject-wise assignment statistics
    $stmt = $pdo->prepare("SELECT subject, COUNT(*) as count FROM assignments 
                          GROUP BY subject ORDER BY count DESC LIMIT 5");
    $stmt->execute();
    $subject_stats = $stmt->fetchAll();

    // Monthly assignment submission trends
    $stmt = $pdo->prepare("SELECT 
                            DATE_FORMAT(submitted_at, '%Y-%m') as month,
                            COUNT(*) as count 
                          FROM submissions 
                          WHERE status IN ('submitted','graded') 
                          GROUP BY DATE_FORMAT(submitted_at, '%Y-%m') 
                          ORDER BY month DESC LIMIT 6");
    $stmt->execute();
    $monthly_trends = $stmt->fetchAll();
    $monthly_trends = array_reverse($monthly_trends);

    // Performance metrics
    // 1. Average time to submit assignments (in days)
    $stmt = $pdo->prepare("SELECT AVG(DATEDIFF(s.submitted_at, a.created_at)) as avg_days 
                          FROM submissions s 
                          JOIN assignments a ON s.assignment_id = a.id 
                          WHERE s.status IN ('submitted','graded')");
    $stmt->execute();
    $avg_submission_time = round($stmt->fetchColumn(), 1);

    // 2. On-time submission rate
    $stmt = $pdo->prepare("SELECT 
                            (COUNT(CASE WHEN s.submitted_at <= a.due_date THEN 1 END) / COUNT(*)) * 100 as ontime_rate
                          FROM submissions s 
                          JOIN assignments a ON s.assignment_id = a.id 
                          WHERE s.status IN ('submitted','graded')");
    $stmt->execute();
    $ontime_submission_rate = round($stmt->fetchColumn(), 1);

    // 3. Course-wise completion rates
    $stmt = $pdo->prepare("SELECT 
                            u.course,
                            COUNT(s.id) as total,
                            COUNT(CASE WHEN s.status IN ('submitted','graded') THEN 1 END) as completed,
                            (COUNT(CASE WHEN s.status IN ('submitted','graded') THEN 1 END) / COUNT(s.id)) * 100 as completion_rate
                          FROM submissions s
                          JOIN users u ON s.student_id = u.id
                          GROUP BY u.course");
    $stmt->execute();
    $course_completion_rates = $stmt->fetchAll();

} catch (PDOException $e) {
    // Handle database errors
    echo 'Database error: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports & Analytics - EduAssign Admin</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Chart.js for data visualization -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            display: flex;
            align-items: center;
            gap: 8px;
        }
        nav a.active, nav a:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .main {
            margin-top: 90px;
            padding: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        .dashboard-container {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .dashboard-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            overflow: hidden;
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        .dashboard-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 30px rgba(0,0,0,0.15);
        }
        .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .card-header h2 {
            font-size: 1.2rem;
            margin: 0;
            color: #333;
        }
        .card-header .icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .card-body {
            padding: 1.5rem;
        }
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .report-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            padding: 1.5rem;
            text-align: center;
            transition: transform 0.3s ease;
        }
        .report-card:hover {
            transform: translateY(-5px);
        }
        .report-card h3 {
            font-size: 2.5rem;
            color: #764ba2;
            margin: 0.5rem 0;
            font-weight: 700;
        }
        .report-card p {
            color: #666;
            font-size: 1rem;
            margin: 0;
        }
        .report-card .icon {
            font-size: 1.5rem;
            color: #667eea;
            margin-bottom: 0.5rem;
        }
        .chart-container {
            position: relative;
            height: 300px;
            width: 100%;
        }
        .activity-list {
            list-style: none;
            padding: 0;
            margin: 0;
        }
        .activity-item {
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
            display: flex;
            align-items: flex-start;
            gap: 1rem;
        }
        .activity-item:last-child {
            border-bottom: none;
        }
        .activity-icon {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: #f0f4ff;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #667eea;
            flex-shrink: 0;
        }
        .activity-content {
            flex-grow: 1;
        }
        .activity-title {
            font-weight: 600;
            margin-bottom: 0.25rem;
        }
        .activity-time {
            font-size: 0.85rem;
            color: #999;
        }
        .course-stats {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
        }
        .course-card {
            background: white;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(0,0,0,0.1);
            padding: 1.5rem;
        }
        .course-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .course-header h3 {
            font-size: 1.2rem;
            margin: 0;
        }
        .course-icon {
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
        }
        .bca-icon {
            background: linear-gradient(135deg, #667eea, #764ba2);
        }
        .bcom-icon {
            background: linear-gradient(135deg, #ff9a9e, #fad0c4);
        }
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 0.75rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .stat-row:last-child {
            border-bottom: none;
        }
        .stat-label {
            color: #666;
        }
        .stat-value {
            font-weight: 600;
        }
        .tab-container {
            margin-bottom: 2rem;
        }
        .tabs {
            display: flex;
            border-bottom: 1px solid #f0f0f0;
            margin-bottom: 1.5rem;
        }
        .tab {
            padding: 0.75rem 1.5rem;
            cursor: pointer;
            font-weight: 500;
            color: #666;
            border-bottom: 3px solid transparent;
            transition: all 0.3s;
        }
        .tab.active {
            color: #667eea;
            border-bottom-color: #667eea;
        }
        .tab-content {
            display: none;
        }
        .tab-content.active {
            display: block;
        }
        @media (max-width: 900px) {
            .header-container {
                flex-direction: column;
                padding: 1rem;
            }
            nav {
                margin-top: 1rem;
                width: 100%;
                justify-content: center;
                flex-wrap: wrap;
            }
            .main {
                padding: 1rem;
                margin-top: 140px;
            }
            .dashboard-container,
            .report-grid,
            .course-stats {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="admin_dashboard.php" class="logo">
                <i class="fas fa-user-shield"></i>
                <h1>EduAssign Admin</h1>
            </a>
            <nav>
                <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="admin_manage_users.php"><i class="fas fa-users-cog"></i> Manage Users</a>
                <a href="admin_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a>
                <a href="admin_reports.php" class="active"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="admin_login.php" onclick="document.getElementById('logout-form').submit();"><i class="fas fa-sign-out-alt"></i> Logout</a>
            </nav>
            <form id="logout-form" action="" method="post" style="display: none;">
                <input type="hidden" name="logout" value="1">
            </form>
        </div>
    </header>
    
    <div class="main">
        <h2><i class="fas fa-chart-line"></i> Reports & Analytics</h2>
        
        <!-- Overview Stats -->
        <div class="report-grid">
            <div class="report-card">
                <div class="icon"><i class="fas fa-user-graduate"></i></div>
                <h3><?php echo $report_stats['total_students']; ?></h3>
                <p>Total Students</p>
            </div>
            <div class="report-card">
                <div class="icon"><i class="fas fa-chalkboard-teacher"></i></div>
                <h3><?php echo $report_stats['total_teachers']; ?></h3>
                <p>Total Teachers</p>
            </div>
            <div class="report-card">
                <div class="icon"><i class="fas fa-tasks"></i></div>
                <h3><?php echo $report_stats['total_assignments']; ?></h3>
                <p>Total Assignments</p>
            </div>
            <div class="report-card">
                <div class="icon"><i class="fas fa-check-circle"></i></div>
                <h3><?php echo $report_stats['completed_assignments']; ?></h3>
                <p>Completed Assignments</p>
            </div>
            <div class="report-card">
                <div class="icon"><i class="fas fa-clock"></i></div>
                <h3><?php echo $report_stats['pending_assignments']; ?></h3>
                <p>Pending Assignments</p>
            </div>
        </div>
        
        <!-- Tab Navigation -->
        <div class="tab-container">
            <div class="tabs">
                <div class="tab active" data-tab="overview">Overview</div>
                <div class="tab" data-tab="course-stats">Course Statistics</div>
                <div class="tab" data-tab="activity">Recent Activity</div>
            </div>
            
            <!-- Overview Tab -->
            <div class="tab-content active" id="overview">
                <div class="dashboard-container">
                    <!-- Assignment Completion Chart -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2>Assignment Completion</h2>
                            <div class="icon"><i class="fas fa-chart-pie"></i></div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="assignmentChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Subject Distribution Chart -->
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2>Subject Distribution</h2>
                            <div class="icon"><i class="fas fa-chart-bar"></i></div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="subjectChart"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Monthly Trends Chart -->
                    <div class="dashboard-card" style="grid-column: 1 / -1;">
                        <div class="card-header">
                            <h2>Monthly Submission Trends</h2>
                            <div class="icon"><i class="fas fa-chart-line"></i></div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="trendChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Course Statistics Tab -->
            <div class="tab-content" id="course-stats">
                <!-- Performance Metrics Section -->
                <div class="dashboard-card" style="margin-bottom: 2rem;">
                    <div class="card-header">
                        <h2>Performance Metrics</h2>
                        <div class="icon"><i class="fas fa-tachometer-alt"></i></div>
                    </div>
                    <div class="card-body">
                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 1.5rem;">
                            <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                <div style="font-size: 2.5rem; font-weight: 700; color: #667eea;"><?php echo $avg_submission_time; ?></div>
                                <div style="color: #666;">Average Days to Submit</div>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                <div style="font-size: 2.5rem; font-weight: 700; color: #667eea;"><?php echo $ontime_submission_rate; ?>%</div>
                                <div style="color: #666;">On-time Submission Rate</div>
                            </div>
                            <div style="text-align: center; padding: 1rem; background: #f8f9fa; border-radius: 8px;">
                                <div style="font-size: 2.5rem; font-weight: 700; color: #667eea;">
                                    <?php 
                                    $total_rate = 0;
                                    $count = count($course_completion_rates);
                                    if ($count > 0) {
                                        foreach ($course_completion_rates as $rate) {
                                            $total_rate += $rate['completion_rate'];
                                        }
                                        echo round($total_rate / $count, 1);
                                    } else {
                                        echo '0';
                                    }
                                    ?>%
                                </div>
                                <div style="color: #666;">Overall Completion Rate</div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="course-stats">
                    <!-- BCA Statistics -->
                    <div class="course-card">
                        <div class="course-header">
                            <h3>BCA Course Statistics</h3>
                            <div class="course-icon bca-icon"><i class="fas fa-laptop-code"></i></div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-label">Students</div>
                            <div class="stat-value"><?php echo $report_stats['bca_students']; ?></div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-label">Teachers</div>
                            <div class="stat-value"><?php echo $report_stats['bca_teachers']; ?></div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-label">Student-Teacher Ratio</div>
                            <div class="stat-value">
                                <?php 
                                    echo $report_stats['bca_teachers'] > 0 
                                        ? round($report_stats['bca_students'] / $report_stats['bca_teachers'], 1) . ':1' 
                                        : 'N/A'; 
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- BCom Statistics -->
                    <div class="course-card">
                        <div class="course-header">
                            <h3>BCom Course Statistics</h3>
                            <div class="course-icon bcom-icon"><i class="fas fa-chart-line"></i></div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-label">Students</div>
                            <div class="stat-value"><?php echo $report_stats['bcom_students']; ?></div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-label">Teachers</div>
                            <div class="stat-value"><?php echo $report_stats['bcom_teachers']; ?></div>
                        </div>
                        <div class="stat-row">
                            <div class="stat-label">Student-Teacher Ratio</div>
                            <div class="stat-value">
                                <?php 
                                    echo $report_stats['bcom_teachers'] > 0 
                                        ? round($report_stats['bcom_students'] / $report_stats['bcom_teachers'], 1) . ':1' 
                                        : 'N/A'; 
                                ?>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Course Comparison Chart -->
                    <div class="dashboard-card" style="grid-column: 1 / -1;">
                        <div class="card-header">
                            <h2>Course Comparison</h2>
                            <div class="icon"><i class="fas fa-chart-bar"></i></div>
                        </div>
                        <div class="card-body">
                            <div class="chart-container">
                                <canvas id="courseComparisonChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Recent Activity Tab -->
            <div class="tab-content" id="activity">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2>Recent System Activity</h2>
                        <div class="icon"><i class="fas fa-history"></i></div>
                    </div>
                    <div class="card-body">
                        <ul class="activity-list">
                            <?php if (empty($recent_activities)): ?>
                                <li class="activity-item">
                                    <div class="activity-icon"><i class="fas fa-info-circle"></i></div>
                                    <div class="activity-content">
                                        <div class="activity-title">No recent activities found</div>
                                    </div>
                                </li>
                            <?php else: ?>
                                <?php foreach ($recent_activities as $activity): ?>
                                    <li class="activity-item">
                                        <div class="activity-icon">
                                            <?php 
                                            $icon = 'fas fa-info-circle';
                                            switch ($activity['action_type'] ?? '') {
                                                case 'login': $icon = 'fas fa-sign-in-alt'; break;
                                                case 'logout': $icon = 'fas fa-sign-out-alt'; break;
                                                case 'add_assignment': $icon = 'fas fa-plus-circle'; break;
                                                case 'edit_assignment': $icon = 'fas fa-edit'; break;
                                                case 'delete_assignment': $icon = 'fas fa-trash-alt'; break;
                                                case 'submit_assignment': $icon = 'fas fa-paper-plane'; break;
                                                case 'grade_assignment': $icon = 'fas fa-star'; break;
                                            }
                                            echo "<i class='{$icon}'></i>";
                                            ?>
                                        </div>
                                        <div class="activity-content">
                                            <div class="activity-title">
                                                <?php echo htmlspecialchars($activity['name']); ?> (<?php echo ucfirst($activity['role']); ?>)
                                                <?php echo htmlspecialchars($activity['description']); ?>
                                            </div>
                                            <div class="activity-time">
                                                <?php echo date('F j, Y, g:i a', strtotime($activity['created_at'])); ?>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script>
        // Tab switching functionality
        document.addEventListener('DOMContentLoaded', function() {
            const tabs = document.querySelectorAll('.tab');
            const tabContents = document.querySelectorAll('.tab-content');
            
            tabs.forEach(tab => {
                tab.addEventListener('click', () => {
                    const tabId = tab.getAttribute('data-tab');
                    
                    // Remove active class from all tabs and contents
                    tabs.forEach(t => t.classList.remove('active'));
                    tabContents.forEach(c => c.classList.remove('active'));
                    
                    // Add active class to current tab and content
                    tab.classList.add('active');
                    document.getElementById(tabId).classList.add('active');
                });
            });
            
            // Initialize charts
            // Assignment Completion Chart
            const assignmentCtx = document.getElementById('assignmentChart').getContext('2d');
            new Chart(assignmentCtx, {
                type: 'doughnut',
                data: {
                    labels: ['Completed', 'Pending'],
                    datasets: [{
                        data: [
                            <?php echo $report_stats['completed_assignments']; ?>,
                            <?php echo $report_stats['pending_assignments']; ?>
                        ],
                        backgroundColor: [
                            '#667eea',
                            '#fad0c4'
                        ],
                        borderWidth: 0
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: {
                            position: 'bottom'
                        }
                    },
                    cutout: '70%'
                }
            });
            
            // Subject Distribution Chart
            const subjectCtx = document.getElementById('subjectChart').getContext('2d');
            new Chart(subjectCtx, {
                type: 'bar',
                data: {
                    labels: [
                        <?php 
                        if (!empty($subject_stats)) {
                            foreach ($subject_stats as $subject) {
                                echo "'" . $subject['subject'] . "', ";
                            }
                        } else {
                            echo "'No Data'";
                        }
                        ?>
                    ],
                    datasets: [{
                        label: 'Assignments per Subject',
                        data: [
                            <?php 
                            if (!empty($subject_stats)) {
                                foreach ($subject_stats as $subject) {
                                    echo $subject['count'] . ", ";
                                }
                            } else {
                                echo "0";
                            }
                            ?>
                        ],
                        backgroundColor: 'rgba(102, 126, 234, 0.7)',
                        borderColor: 'rgba(102, 126, 234, 1)',
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Course Comparison Chart
            const courseCtx = document.getElementById('courseComparisonChart').getContext('2d');
            new Chart(courseCtx, {
                type: 'bar',
                data: {
                    labels: ['Students', 'Teachers'],
                    datasets: [
                        {
                            label: 'BCA',
                            data: [
                                <?php echo $report_stats['bca_students']; ?>,
                                <?php echo $report_stats['bca_teachers']; ?>
                            ],
                            backgroundColor: 'rgba(102, 126, 234, 0.7)'
                        },
                        {
                            label: 'BCom',
                            data: [
                                <?php echo $report_stats['bcom_students']; ?>,
                                <?php echo $report_stats['bcom_teachers']; ?>
                            ],
                            backgroundColor: 'rgba(255, 154, 158, 0.7)'
                        }
                    ]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    }
                }
            });
            
            // Monthly Trends Chart
            const trendCtx = document.getElementById('trendChart').getContext('2d');
            new Chart(trendCtx, {
                type: 'line',
                data: {
                    labels: [
                        <?php 
                        if (!empty($monthly_trends)) {
                            foreach ($monthly_trends as $trend) {
                                // Format month for display (e.g., 2023-01 to Jan 2023)
                                $date = new DateTime($trend['month'] . '-01');
                                echo "'" . $date->format('M Y') . "', ";
                            }
                        } else {
                            // If no data, show last 6 months
                            $date = new DateTime();
                            for ($i = 5; $i >= 0; $i--) {
                                $date = new DateTime();
                                $date->modify("-$i month");
                                echo "'" . $date->format('M Y') . "', ";
                            }
                        }
                        ?>
                    ],
                    datasets: [{
                        label: 'Assignments Submitted',
                        data: [
                            <?php 
                            if (!empty($monthly_trends)) {
                                foreach ($monthly_trends as $trend) {
                                    echo $trend['count'] . ", ";
                                }
                            } else {
                                // If no data, show zeros
                                for ($i = 0; $i < 6; $i++) {
                                    echo "0, ";
                                }
                            }
                            ?>
                        ],
                        borderColor: 'rgba(102, 126, 234, 1)',
                        backgroundColor: 'rgba(102, 126, 234, 0.1)',
                        borderWidth: 2,
                        fill: true,
                        tension: 0.4
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            ticks: {
                                precision: 0
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Assignment Submission Trends (Last 6 Months)',
                            font: {
                                size: 16
                            }
                        }
                    }
                }
            });
        });
    </script>
</body>
</html>