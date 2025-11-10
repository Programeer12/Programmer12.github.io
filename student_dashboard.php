<?php
require_once 'includes/auth.php';
require_once 'includes/notification_system.php';

// Require student role
$auth->requireRole('student');

// Initialize notification system
$notificationSystem = new NotificationSystem($pdo);

// Get student data
$student = $auth->getCurrentUser();
$student_name = $student['name'] ?? "Student";
$student_id = $student['id'] ?? "ID";
$student_email = $student['email'] ?? "student@example.com";

// Get real statistics from database
try {
    // Count submitted assignments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE student_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $submitted_count = $stmt->fetchColumn();
    
    // Count pending assignments (assignments not yet submitted)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments a WHERE a.status = 'active' AND a.due_date > NOW() AND NOT EXISTS (SELECT 1 FROM submissions s WHERE s.assignment_id = a.id AND s.student_id = ?)");
    $stmt->execute([$_SESSION['user_id']]);
    $pending_count = $stmt->fetchColumn();
    
    // Count graded submissions
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE student_id = ? AND status = 'graded'");
    $stmt->execute([$_SESSION['user_id']]);
    $graded_count = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $submitted_count = 0;
    $pending_count = 0;
    $graded_count = 0;
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
    <title>Student Dashboard - EduAssign</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
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
        .dashboard-main {
            margin-top: 90px;
            padding: 3rem 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        .dashboard-title {
            font-size: 2.2rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: #333;
        }
        .dashboard-info {
            margin-bottom: 2rem;
            color: #666;
        }
        .dashboard-stats {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            margin-bottom: 2.5rem;
        }
        .stat-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
            padding: 2rem 2.5rem;
            flex: 1 1 220px;
            text-align: center;
            min-width: 200px;
            border: 1px solid rgba(102,126,234,0.08);
            transition: box-shadow 0.3s;
        }
        .stat-card i {
            font-size: 2.2rem;
            color: #667eea;
            margin-bottom: 0.8rem;
        }
        .stat-card h3 {
            font-size: 2.2rem;
            margin: 0.5rem 0;
            color: #764ba2;
        }
        .stat-card p {
            color: #666;
            font-size: 1.1rem;
        }
        .dashboard-actions {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
        }
        .action-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border-radius: 18px;
            padding: 2rem 2.5rem;
            flex: 1 1 320px;
            min-width: 260px;
            text-align: center;
            box-shadow: 0 8px 24px rgba(102,126,234,0.12);
            transition: transform 0.3s;
            text-decoration: none;
        }
        .action-card:hover {
            transform: translateY(-8px) scale(1.03);
            box-shadow: 0 16px 32px rgba(102,126,234,0.18);
        }
        .action-card i {
            font-size: 2.2rem;
            margin-bottom: 0.8rem;
        }
        .action-card h4 {
            margin: 0.5rem 0 0.7rem 0;
            font-size: 1.3rem;
            font-weight: 600;
        }
        @media (max-width: 900px) {
            .dashboard-stats, .dashboard-actions {
                flex-direction: column;
                gap: 1.2rem;
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
                <a href="student_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="student_assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
                <a href="student_grades.php"><i class="fas fa-chart-line"></i> Grades</a>
                <a href="notification_center.php"><i class="fas fa-bell"></i> Notifications</a>
                <a href="student_profile.php"><i class="fas fa-user"></i> Profile</a>
                <form method="post" style="display:inline;">
                    <button type="submit" name="logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="dashboard-main">
        <div class="dashboard-title">Welcome, <?php echo htmlspecialchars($student_name); ?>!</div>
        <div class="dashboard-info">
           <b style="color: white;"> Student ID: <?php echo htmlspecialchars($student_id); ?></b><br>
            <b style="color: white;">Email: <?php echo htmlspecialchars($student_email); ?></b>
        </div>
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-upload"></i>
                <h3><?php echo $submitted_count; ?></h3>
                <p>Assignments Submitted</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-hourglass-half"></i>
                <h3><?php echo $pending_count; ?></h3>
                <p>Pending Assignments</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3><?php echo $graded_count; ?></h3>
                <p>Graded Assignments</p>
            </div>
        </div>
        <div class="dashboard-actions">
            <a href="student_assignments.php" class="action-card">
                <i class="fas fa-tasks"></i>
                <h4>View Assignments</h4>
                <p>See your current and past assignments.</p>
            </a>
            <a href="student_grades.php" class="action-card">
                <i class="fas fa-chart-line"></i>
                <h4>View Grades</h4>
                <p>Check your grades and performance.</p>
            </a>
            <a href="student_profile.php" class="action-card">
                <i class="fas fa-user"></i>
                <h4>Edit Profile</h4>
                <p>Update your personal information.</p>
            </a>
        </div>
    </main>
    
    <script src="js/notifications.js"></script>
    <script>
        // Dashboard notification system
        // Use different variable names to avoid conflicts
        let dashboardLastNotificationId = 0;
        let dashboardNotificationManager;

        // Initialize notification system for dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Student Dashboard loaded, initializing notifications...');
            
            // Restore last seen notification ID from local storage (persists across page loads)
            const storedLastNotificationId = localStorage.getItem('studentLastNotificationId');
            if (storedLastNotificationId) {
                dashboardLastNotificationId = parseInt(storedLastNotificationId);
                console.log('📝 Restored last seen notification ID from localStorage:', dashboardLastNotificationId);
            } else {
                console.log('📝 No previous notification ID found, starting fresh');
            }
            
            // Check if CSS styles are loaded
            const testDiv = document.createElement('div');
            testDiv.className = 'glass-notification';
            testDiv.style.position = 'absolute';
            testDiv.style.left = '-9999px';
            document.body.appendChild(testDiv);
            
            const computedStyle = window.getComputedStyle(testDiv);
            const hasBackdropFilter = computedStyle.backdropFilter || computedStyle.webkitBackdropFilter;
            
            if (hasBackdropFilter && hasBackdropFilter !== 'none') {
                console.log('✅ Glass notification CSS styles loaded');
            } else {
                console.error('❌ Glass notification CSS styles missing!');
            }
            document.body.removeChild(testDiv);
            
            if (typeof NotificationManager !== 'undefined') {
                try {
                    dashboardNotificationManager = new NotificationManager();
                    console.log('✅ NotificationManager created successfully');
                    
                    // Check if student notifications have already been shown in this session
                    const studentWelcomeShown = sessionStorage.getItem('studentWelcomeShown');
                    
                    // Show welcome popup only once per session
                    if (!studentWelcomeShown) {
                        setTimeout(() => {
                            console.log('🎉 Showing student welcome notification (first time this session)...');
                            try {
                                dashboardNotificationManager.showGlassNotification({
                                    id: 999,
                                    title: '🎓 Welcome Student!',
                                    message: 'Glass notifications are working! You will see assignment and grade notifications here.',
                                    type: 'general',
                                    priority: 'medium',
                                    created_at: new Date().toISOString()
                                });
                                // Mark as shown for this session
                                sessionStorage.setItem('studentWelcomeShown', 'true');
                                console.log('✅ Student welcome notification shown and marked as displayed');
                            } catch (error) {
                                console.error('❌ Error showing glass notification:', error);
                            }
                        }, 2000);
                    } else {
                        console.log('ℹ️ Student welcome notification already shown in this session, skipping...');
                    }
                    
                    // Get initial notification state
                    checkForNewNotifications();
                    
                    // Check for new notifications every 15 seconds
                    setInterval(checkForNewNotifications, 15000);
                    
                } catch (error) {
                    console.error('❌ Error creating NotificationManager:', error);
                }
            } else {
                console.error('❌ NotificationManager class not found! Check if js/notifications.js is loaded.');
            }
        });

        function checkForNewNotifications() {
            console.log('🔍 Checking for new notifications...');
            
            fetch('check_notifications.php')
                .then(response => {
                    console.log('📡 Response received:', response.status, response.statusText);
                    
                    if (!response.ok) {
                        throw new Error(`HTTP ${response.status}: ${response.statusText}`);
                    }
                    
                    return response.text();
                })
                .then(text => {
                    console.log('📄 Raw response:', text.substring(0, 200) + '...');
                    
                    try {
                        const data = JSON.parse(text);
                        console.log('📊 Parsed notification data:', data);
                        
                        if (data.success && data.has_notifications) {
                            const latestNotification = data.latest_notification;
                            
                            // Check if this is a new notification (different from last seen)
                            if (latestNotification.id > dashboardLastNotificationId) {
                                console.log('🆕 New notification detected:', latestNotification.title, '(ID:', latestNotification.id, ', Last seen:', dashboardLastNotificationId, ')');
                                dashboardLastNotificationId = latestNotification.id;
                                
                                // Save the last seen notification ID to local storage (persists across page loads)
                                localStorage.setItem('studentLastNotificationId', dashboardLastNotificationId.toString());
                                console.log('💾 Saved last notification ID to localStorage:', dashboardLastNotificationId);
                                
                                // Show glass notification for new notifications
                                if (dashboardNotificationManager) {
                                    dashboardNotificationManager.showGlassNotification(latestNotification);
                                } else {
                                    console.error('❌ NotificationManager not available');
                                }
                            } else {
                                console.log('ℹ️ No new notifications (current ID:', latestNotification.id, ', last seen:', dashboardLastNotificationId, ') - skipping popup');
                            }
                        } else if (data.error) {
                            console.error('❌ API error:', data.error);
                        } else {
                            console.log('ℹ️ No notifications found');
                        }
                        
                        // Update unread count display if indicator exists
                        updateNotificationIndicator(data.unread_count || 0);
                        
                    } catch (parseError) {
                        console.error('❌ JSON parse error:', parseError.message);
                        console.error('🔍 This usually means PHP errors or login redirect');
                        console.error('📄 Full response:', text);
                    }
                })
                .catch(error => {
                    console.error('❌ Error checking notifications:', error);
                });
        }

        function updateNotificationIndicator(count) {
            const indicator = document.querySelector('.notification-indicator, #notification-indicator');
            if (indicator) {
                if (count > 0) {
                    indicator.textContent = count > 99 ? '99+' : count;
                    indicator.style.display = 'inline-block';
                } else {
                    indicator.style.display = 'none';
                }
            }
        }
    </script>
</body>
</html> 