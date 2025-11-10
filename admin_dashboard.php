<?php
require_once 'includes/auth.php';
require_once 'includes/notification_system.php';

// Require admin role
$auth->requireRole('admin');

// Handle logout BEFORE any HTML output
if (isset($_POST['logout'])) {
    $auth->logout();
    header("Location: admin_login.php");
    exit();
}

// Get real statistics from database
try {
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active'");
    $stmt->execute();
    $student_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'active'");
    $stmt->execute();
    $teacher_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE status = 'active'");
    $stmt->execute();
    $assignment_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM pending_registrations");
    $stmt->execute();
    $pending_count = $stmt->fetchColumn();
    
    // Get course-specific counts
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active' AND course = 'BCA'");
    $stmt->execute();
    $bca_student_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active' AND course = 'BCom'");
    $stmt->execute();
    $bcom_student_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'active' AND course = 'BCA'");
    $stmt->execute();
    $bca_teacher_count = $stmt->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'active' AND course = 'BCom'");
    $stmt->execute();
    $bcom_teacher_count = $stmt->fetchColumn();
    
    // Get pending registrations with course and subject details
    $stmt = $pdo->prepare("SELECT * FROM pending_registrations ORDER BY created_at DESC LIMIT 5");
    $stmt->execute();
    $recent_pending = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $student_count = 0;
    $teacher_count = 0;
    $assignment_count = 0;
    $pending_count = 0;
    $bca_student_count = 0;
    $bcom_student_count = 0;
    $bca_teacher_count = 0;
    $bcom_teacher_count = 0;
    $recent_pending = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Admin Dashboard - EduAssign</title>
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
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        nav a.active, nav a:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .notification-badge {
            display: none;
            background: #e74c3c;
            color: white;
            padding: 0.1rem 0.6rem;
            border-radius: 999px;
            font-size: 0.85rem;
            font-weight: 600;
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
        .course-stats {
            display: flex;
            gap: 2rem;
            flex-wrap: wrap;
            margin-bottom: 2.5rem;
        }
        .course-card {
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
            padding: 1.5rem;
            flex: 1 1 45%;
            min-width: 300px;
            border: 1px solid rgba(102,126,234,0.08);
        }
        .course-card h3 {
            margin: 0 0 1rem 0;
            color: #333;
            font-size: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #667eea;
        }
        .course-stat {
            display: flex;
            justify-content: space-between;
            padding: 0.8rem 0;
            border-bottom: 1px solid #eee;
        }
        .course-stat:last-child {
            border-bottom: none;
        }
        .course-stat-label {
            font-weight: 500;
            color: #555;
        }
        .course-stat-value {
            font-weight: 600;
            color: #764ba2;
        }
        .notifications-section {
            background: white;
            border-radius: 18px;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
            padding: 1.5rem;
            margin-bottom: 2.5rem;
        }
        .notifications-section h3 {
            margin: 0 0 1rem 0;
            color: #333;
            font-size: 1.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #667eea;
        }
        .notification-item {
            display: flex;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #eee;
        }
        .notification-item:last-child {
            border-bottom: none;
        }
        .notification-icon {
            background: #f3f4fa;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
        }
        .notification-icon i {
            color: #667eea;
            font-size: 1.2rem;
        }
        .notification-content {
            flex: 1;
        }
        .notification-title {
            font-weight: 600;
            margin-bottom: 0.3rem;
        }
        .notification-meta {
            display: flex;
            font-size: 0.85rem;
            color: #666;
        }
        .notification-meta span {
            margin-right: 1rem;
        }
        .badge {
            display: inline-block;
            padding: 0.25rem 0.5rem;
            border-radius: 20px;
            font-size: 0.75rem;
            font-weight: 600;
            margin-right: 0.5rem;
        }
        .badge-bca {
            background: #e3f2fd;
            color: #1976d2;
        }
        .badge-bcom {
            background: #e8f5e9;
            color: #388e3c;
        }
        .badge-student {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .badge-teacher {
            background: #fff3e0;
            color: #e65100;
        }
        .view-all {
            display: block;
            text-align: center;
            margin-top: 1rem;
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
        }
        .view-all:hover {
            text-decoration: underline;
        }
        @media (max-width: 900px) {
            .dashboard-stats, .dashboard-actions, .course-stats {
                flex-direction: column;
                gap: 1.2rem;
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
                <a href="admin_dashboard.php" class="active"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="admin_manage_users.php"><i class="fas fa-users-cog"></i> Manage Users</a>
                <a href="admin_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a>
                <a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <a href="notification_center.php">
                    Notifications
                    <span id="notification-indicator" class="notification-badge"></span>
                </a>
                <form method="post" style="display:inline;">
                    <button type="submit" name="logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="dashboard-main">
        <div class="dashboard-title">Welcome, Admin!</div>
        <div class="dashboard-stats">
            <div class="stat-card">
                <i class="fas fa-user-graduate"></i>
                <h3><?php echo $student_count; ?></h3>
                <p>Students</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-chalkboard-teacher"></i>
                <h3><?php echo $teacher_count; ?></h3>
                <p>Teachers</p>
            </div>
            <div class="stat-card">
                <i class="fas fa-tasks"></i>
                <h3><?php echo $assignment_count; ?></h3>
                <p>Assignments</p>
            </div>
            <div class="stat-card" style="background: #ffffff; color: #333;">
                <i class="fas fa-user-clock"></i>
                <h3><?php echo $pending_count; ?></h3>
                <p>Pending Approvals</p>
            </div>
        </div>
        
        <!-- Course-specific Statistics -->
        <div class="course-stats">
            <div class="course-card">
                <h3><i class="fas fa-laptop-code"></i> BCA Course</h3>
                <div class="course-stat">
                    <div class="course-stat-label">Students</div>
                    <div class="course-stat-value"><?php echo $bca_student_count; ?></div>
                </div>
                <div class="course-stat">
                    <div class="course-stat-label">Teachers</div>
                    <div class="course-stat-value"><?php echo $bca_teacher_count; ?></div>
                </div>
                <div class="course-stat">
                    <div class="course-stat-label">Subjects</div>
                    <div class="course-stat-value">5</div>
                </div>
            </div>
            <div class="course-card">
                <h3><i class="fas fa-chart-line"></i> BCom Course</h3>
                <div class="course-stat">
                    <div class="course-stat-label">Students</div>
                    <div class="course-stat-value"><?php echo $bcom_student_count; ?></div>
                </div>
                <div class="course-stat">
                    <div class="course-stat-label">Teachers</div>
                    <div class="course-stat-value"><?php echo $bcom_teacher_count; ?></div>
                </div>
                <div class="course-stat">
                    <div class="course-stat-label">Subjects</div>
                    <div class="course-stat-value">3</div>
                </div>
            </div>
        </div>
        
        <!-- Recent Notifications Section -->
        <?php if (!empty($recent_pending)): ?>
        <div class="notifications-section">
            <h3><i class="fas fa-bell"></i> Recent Registration Requests</h3>
            <?php foreach ($recent_pending as $reg): ?>
            <div class="notification-item">
                <div class="notification-icon">
                    <i class="<?php echo $reg['role'] === 'student' ? 'fas fa-user-graduate' : 'fas fa-chalkboard-teacher'; ?>"></i>
                </div>
                <div class="notification-content">
                    <div class="notification-title">
                        <?php echo htmlspecialchars($reg['name']); ?> 
                        <span class="badge badge-<?php echo strtolower($reg['course']); ?>"><?php echo $reg['course']; ?></span>
                        <span class="badge badge-<?php echo $reg['role']; ?>"><?php echo ucfirst($reg['role']); ?></span>
                    </div>
                    <div class="notification-meta">
                        <span><i class="fas fa-envelope"></i> <?php echo htmlspecialchars($reg['email']); ?></span>
                        <span><i class="fas fa-book"></i> <?php echo htmlspecialchars($reg['subject']); ?></span>
                        <span><i class="fas fa-clock"></i> <?php echo date('M j, Y', strtotime($reg['created_at'])); ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
            <a href="admin_approve_registrations.php" class="view-all">View All Pending Registrations</a>
        </div>
        <?php endif; ?>
        
        <div class="dashboard-actions">
            <a href="admin_manage_users.php" class="action-card" style="background: linear-gradient(135deg, #ff6b6b, #ee5a24);">
                <i class="fas fa-users-cog"></i>
                <h4>Manage Users</h4>
                <p>Approve registrations, edit, or remove student and teacher accounts.</p>
            </a>
            <a href="admin_assignments.php" class="action-card">
                <i class="fas fa-tasks"></i>
                <h4>Manage Assignments</h4>
                <p>View, edit, or delete assignments across all courses.</p>
            </a>
            <a href="admin_reports.php" class="action-card">
                <i class="fas fa-chart-bar"></i>
                <h4>Reports & Analytics</h4>
                <p>Generate and download usage and performance reports.</p>
            </a>
        </div>
    </main>
    
    <script src="js/notifications.js"></script>
    <script>
        // Dashboard notification system for Admin
        let lastNotificationId = 0;
        
        // Initialize notification system for dashboard
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Admin Dashboard loaded, initializing notifications...');
            
            // Restore last seen notification ID from local storage (persists across page loads)
            const storedLastNotificationId = localStorage.getItem('adminLastNotificationId');
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
                    console.log('✅ NotificationManager created successfully');
                    
                    // Check if admin notifications have already been shown in this session
                    const adminWelcomeShown = sessionStorage.getItem('adminWelcomeShown');
                    const adminPendingShown = sessionStorage.getItem('adminPendingShown');
                    
                    // Show admin welcome popup only once per session
                    if (!adminWelcomeShown) {
                        setTimeout(() => {
                            console.log('🎯 Showing admin welcome notification (first time this session)...');
                            notificationManager.showGlassNotification({
                                id: 995,
                                title: '👑 Welcome Admin!',
                                message: 'Admin dashboard notifications are active. You will see important system alerts here.',
                                type: 'general',
                                priority: 'high',
                                created_at: new Date().toISOString()
                            });
                            console.log('✅ Admin welcome notification displayed');
                            // Mark as shown for this session
                            sessionStorage.setItem('adminWelcomeShown', 'true');
                        }, 1000);
                    } else {
                        console.log('ℹ️ Admin welcome notification already shown in this session, skipping...');
                    }
                    
                    // Show pending registrations alert if any, only once per session
                    const pendingCount = <?php echo $pending_count; ?>;
                    if (pendingCount > 0 && !adminPendingShown) {
                        setTimeout(() => {
                            console.log('📋 Showing pending registrations notification (first time this session)...');
                            notificationManager.showGlassNotification({
                                id: 996,
                                title: '📋 Pending Registrations',
                                message: `You have ${pendingCount} pending registration${pendingCount > 1 ? 's' : ''} waiting for approval.`,
                                type: 'assignment',
                                priority: 'high',
                                created_at: new Date().toISOString()
                            });
                            console.log('✅ Pending registrations notification displayed');
                            // Mark as shown for this session
                            sessionStorage.setItem('adminPendingShown', 'true');
                        }, 3000);
                    } else if (pendingCount > 0) {
                        console.log('ℹ️ Pending registrations notification already shown in this session, skipping...');
                    }
                    
                    // Get initial notification state
                    checkForNewNotifications();
                    
                    // Check for new notifications every 30 seconds
                    setInterval(checkForNewNotifications, 30000);
                    
                } else {
                    console.error('❌ NotificationManager class not found!');
                    alert('Notification system failed to load. Please check the console for details.');
                }
            } catch (error) {
                console.error('❌ Error initializing admin notification system:', error);
                alert('Error initializing notifications: ' + error.message);
            }
        });
        
        function checkForNewNotifications() {
            console.log('🔍 Admin checking for new notifications...');
            
            fetch('check_notifications.php')
                .then(response => {
                    console.log('📡 Admin notification API response status:', response.status);
                    if (!response.ok) {
                        throw new Error('Network response was not ok: ' + response.status);
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('📊 Admin notification check response:', data);
                    
                    if (data.success && data.has_notifications) {
                        const latestNotification = data.latest_notification;
                        
                        // Check if this is a new notification
                        if (latestNotification.id > lastNotificationId) {
                            console.log('🆕 New admin notification detected:', latestNotification.title, '(ID:', latestNotification.id, ', Last seen:', lastNotificationId, ')');
                            lastNotificationId = latestNotification.id;
                            
                            // Save the last seen notification ID to local storage (persists across page loads)
                            localStorage.setItem('adminLastNotificationId', lastNotificationId.toString());
                            console.log('💾 Saved last notification ID to localStorage:', lastNotificationId);
                            
                            // Show glass notification for new notifications
                            if (notificationManager) {
                                notificationManager.showGlassNotification(latestNotification);
                            }
                        } else {
                            console.log('ℹ️ No new notifications (current ID:', latestNotification.id, ', last seen:', lastNotificationId, ') - skipping popup');
                        }
                    } else if (data.error) {
                        console.warn('⚠️ Admin notification API error:', data.error);
                    }
                    
                    // Update unread count display if indicator exists
                    updateNotificationIndicator(data.unread_count || 0);
                })
                .catch(error => {
                    console.error('❌ Error checking admin notifications:', error);
                    // Show a test notification when API fails
                    if (notificationManager) {
                        notificationManager.showGlassNotification({
                            id: 994,
                            title: '⚠️ System Alert',
                            message: 'Notification service temporarily unavailable. This is a test popup to verify functionality.',
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