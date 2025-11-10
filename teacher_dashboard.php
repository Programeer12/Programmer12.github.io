<?php
require_once 'includes/auth.php';
require_once 'includes/notification_system.php';

// Require teacher role
$auth->requireRole('teacher');

// Initialize notification system
$notificationSystem = new NotificationSystem($pdo);

// Get teacher data
$teacher = $auth->getCurrentUser();
$teacher_name = $teacher['name'] ?? "Teacher";
$teacher_subject = $teacher['subject'] ?? "Subject";
$teacher_department = "Department"; // Default value since department is not stored in users table

// Get real statistics from database
try {
    // Count assignments created by this teacher
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE teacher_id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $assigned_count = $stmt->fetchColumn();
    
    // Count pending submissions for this teacher's assignments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE a.teacher_id = ? AND s.status = 'submitted'");
    $stmt->execute([$_SESSION['user_id']]);
    $pending_count = $stmt->fetchColumn();
    
    // Count graded submissions
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions s JOIN assignments a ON s.assignment_id = a.id WHERE a.teacher_id = ? AND s.status = 'graded'");
    $stmt->execute([$_SESSION['user_id']]);
    $graded_count = $stmt->fetchColumn();
    
    // Count pending student registrations for this teacher's subject
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pending_registrations WHERE role = 'student' AND subject = ?");
    $stmt->execute([$teacher_subject]);
    $pending_students_count = $stmt->fetchColumn();
    
    // Get students in the teacher's course
    $stmt = $pdo->prepare("SELECT name, email FROM users WHERE role = 'student' AND course = ? AND status = 'active'");
    $stmt->execute([$teacher['course']]);
    $students_in_course = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $assigned_count = 0;
    $pending_count = 0;
    $graded_count = 0;
    $pending_students_count = 0;
    $students_in_course = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Teacher Dashboard - EduAssign</title>
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
            <a href="teacher_dashboard.php" class="logo">
                <i class="fas fa-chalkboard-teacher"></i>
                <h1>EduAssign Teacher</h1>
            </a>
            <nav>
                <a href="teacher_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="teacher_assignments.php"><i class="fas fa-tasks"></i> My Assignments</a>
                <a href="teacher_submissions.php"><i class="fas fa-file-alt"></i> Submissions</a>
                <a href="teacher_approve_students.php"><i class="fas fa-user-check"></i> Approve Students</a>
                <a href="teacher_students.php"><i class="fas fa-users"></i> My Students</a>
                <a href="notification_center.php"><i class="fas fa-bell"></i> Notifications</a>
                <a href="teacher_profile.php"><i class="fas fa-user"></i> Your Profile</a>
            </nav>
        </div>
    </header>
    <main class="dashboard-main">
        <div class="dashboard-title">Welcome, <?php echo htmlspecialchars($teacher_name); ?>!</div>
        <p style="margin-bottom:2rem;color:#666;">Department: <?php echo htmlspecialchars($teacher_department); ?></p>
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-tasks"></i>
                <h3><?php echo $assigned_count; ?></h3>
                <p>Assignments Created</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-hourglass-half"></i>
                <h3><?php echo $pending_count; ?></h3>
                <p>Pending Submissions</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-check-circle"></i>
                <h3><?php echo $graded_count; ?></h3>
                <p>Graded Submissions</p>
            </div>
            <div class="stat-card" style="background: linear-gradient(135deg, #ff6b6b, #ee5a24);">
                <i class="fas fa-user-clock"></i>
                <h3><?php echo $pending_students_count; ?></h3>
                <p>Pending Students</p>
            </div>
        </div>
        <div class="dashboard-actions">
            <a href="teacher_assignments.php" class="action-card">
                <i class="fas fa-tasks"></i>
                <h4>Manage Assignments</h4>
                <p>Create, edit, or remove assignments for your classes.</p>
            </a>
            <a href="teacher_submissions.php" class="action-card">
                <i class="fas fa-file-alt"></i>
                <h4>View Submissions</h4>
                <p>Review and grade student submissions.</p>
            </a>
            <a href="teacher_students.php" class="action-card">
                <i class="fas fa-users"></i>
                <h4>My Students</h4>
                <p>Manage enrolled students and their status.</p>
            </a>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 EduAssign. All rights reserved.</p>
    </footer>

    <script src="js/notifications.js"></script>
    <script>
        // Dashboard notification system
    let lastNotificationId = 0;
        
        // Initialize notification system for dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Teacher Dashboard loaded, initializing notifications...');
            
            // Restore last seen notification ID from local storage (persists across page loads)
            const storedLastNotificationId = localStorage.getItem('teacherLastNotificationId');
            if (storedLastNotificationId) {
                lastNotificationId = parseInt(storedLastNotificationId);
                console.log('📝 Restored last seen notification ID from localStorage:', lastNotificationId);
            } else {
                console.log('📝 No previous notification ID found, starting fresh');
            }
            
            try {
                if (typeof NotificationManager !== 'undefined') {
                    if (typeof notificationManager === 'undefined' || !notificationManager) {
                        notificationManager = new NotificationManager();
                    }
                    console.log('✅ NotificationManager ready');
                    
                    // Test if glass notification container exists
                    const container = document.getElementById('glass-notifications-container');
                    if (!container) {
                        console.warn('❌ Glass notification container not found');
                    }
                    
                    // Check if welcome notification has already been shown in this session
                    const teacherWelcomeShown = sessionStorage.getItem('teacherWelcomeShown');
                    
                    // Show welcome popup only once per session
                    if (!teacherWelcomeShown) {
                        setTimeout(() => {
                            console.log('🎉 Showing teacher welcome notification (first time this session)...');
                            try {
                                notificationManager.showGlassNotification({
                                    id: 999,
                                    title: 'Welcome Teacher!',
                                    message: 'Glass notifications are working! You will see assignment and student notifications here.',
                                    type: 'general',
                                    priority: 'medium',
                                    created_at: new Date().toISOString()
                                });
                                // Mark as shown for this session
                                sessionStorage.setItem('teacherWelcomeShown', 'true');
                                console.log('✅ Teacher welcome notification shown and marked as displayed');
                            } catch (error) {
                                console.error('❌ Error showing glass notification:', error);
                            }
                        }, 2000);
                    } else {
                        console.log('ℹ️ Teacher welcome notification already shown in this session, skipping...');
                    }
                    
                    // Get initial notification state
                    checkForNewNotifications();
                    
                    // Check for new notifications every 30 seconds
                    setInterval(checkForNewNotifications, 30000);
                    
                } else {
                    console.error('❌ NotificationManager class not found!');
                    console.error('❌ Check if js/notifications.js is loaded properly');
                    alert('CRITICAL ERROR: NotificationManager not found! Check if js/notifications.js is loaded.');
                    
                    // Try to show a basic alert as fallback
                    setTimeout(() => {
                        alert('Teacher Dashboard: Notification system failed to load!');
                    }, 2000);
                }
            } catch (error) {
                console.error('❌ Error initializing notification system:', error);
                alert('Error initializing notifications: ' + error.message);
            }
        });
        
        function checkForNewNotifications() {
            console.log('🔍 Checking for new notifications...');
            
            fetch('check_notifications.php')
                .then(response => {
                    console.log('📡 Notification API response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('📊 Notification check response:', data);
                    
                    if (data.success && data.has_notifications) {
                        const latestNotification = data.latest_notification;
                        
                        // Check if this is a new notification (different from last seen)
                        if (latestNotification.id > lastNotificationId) {
                            console.log('🆕 New notification detected:', latestNotification.title, '(ID:', latestNotification.id, ', Last seen:', lastNotificationId, ')');
                            lastNotificationId = latestNotification.id;
                            
                            // Save the last seen notification ID to local storage (persists across page loads)
                            localStorage.setItem('teacherLastNotificationId', lastNotificationId.toString());
                            console.log('💾 Saved last notification ID to localStorage:', lastNotificationId);
                            
                            // Show glass notification for new notifications
                            if (notificationManager) {
                                notificationManager.showGlassNotification(latestNotification);
                            }
                        } else {
                            console.log('ℹ️ No new notifications (current ID:', latestNotification.id, ', last seen:', lastNotificationId, ') - skipping popup');
                        }
                    } else if (data.error) {
                        console.warn('⚠️ Notification API error:', data.error);
                    } else {
                        console.log('ℹ️ No notifications available');
                    }
                    
                    // Update unread count display if indicator exists
                    updateNotificationIndicator(data.unread_count || 0);
                })
                .catch(error => {
                    console.error('❌ Error checking notifications:', error);
                    // Show a test notification instead when API fails
                    if (notificationManager) {
                        notificationManager.showGlassNotification({
                            id: 997,
                            title: '⚠️ API Connection Issue',
                            message: 'Unable to connect to notification service. This is a test notification to verify popup functionality.',
                            type: 'general',
                            priority: 'low',
                            created_at: new Date().toISOString()
                        });
                    }
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
<?php
// Handle logout
if (isset($_POST['logout'])) {
    $auth->logout();
    header("Location: teacher_login.php");
    exit();
}
?>