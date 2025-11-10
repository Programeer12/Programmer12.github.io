<?php
require_once 'includes/auth.php';
require_once 'includes/notification_system.php';
require_once 'includes/time_helper.php';

// Check if user is logged in
if (!$auth->isLoggedIn()) {
    header("Location: student_login.php");
    exit();
}

// Initialize notification system
$notificationSystem = new NotificationSystem($pdo);

$user = $auth->getCurrentUser();
$user_id = $user['id'];
$user_role = $user['role'];

// Get notifications and statistics
$notifications = $notificationSystem->getUserNotifications($user_id, 50);
$unread_count = $notificationSystem->getUnreadCount($user_id);
$stats = $notificationSystem->getNotificationStats($user_id);

// Handle AJAX requests
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['ajax'])) {
    header('Content-Type: application/json');
    
    try {
        // Debug logging
        error_log("AJAX Request received: " . print_r($_POST, true));
        
        if ($_POST['action'] === 'mark_as_read' && isset($_POST['notification_id'])) {
            $notification_id = $_POST['notification_id'];
            error_log("Attempting to mark notification {$notification_id} as read for user {$user_id}");
            
            // Validate input
            if (!is_numeric($notification_id) || !is_numeric($user_id)) {
                echo json_encode([
                    'success' => false,
                    'error' => 'Invalid notification ID or user ID',
                    'notification_id' => $notification_id,
                    'user_id' => $user_id,
                    'debug' => 'Input validation failed'
                ]);
                exit();
            }
            
            $result = $notificationSystem->markAsRead($notification_id, $user_id);
            error_log("Mark as read result: " . ($result ? "SUCCESS" : "FAILED"));
            
            echo json_encode([
                'success' => $result,
                'notification_id' => $notification_id,
                'user_id' => $user_id,
                'debug' => 'Mark as read attempt completed',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit();
        }
        
        if ($_POST['action'] === 'mark_all_read') {
            error_log("Attempting to mark all notifications as read for user {$user_id}");
            $result = $notificationSystem->markAllAsRead($user_id);
            error_log("Mark all as read result: " . ($result ? "SUCCESS" : "FAILED"));
            
            echo json_encode([
                'success' => $result,
                'user_id' => $user_id,
                'debug' => 'Mark all as read attempt completed',
                'timestamp' => date('Y-m-d H:i:s')
            ]);
            exit();
        }
        
        // If we get here, the action wasn't recognized
        echo json_encode([
            'success' => false,
            'error' => 'Unknown action',
            'received_action' => $_POST['action'] ?? 'none',
            'debug' => 'Action not recognized',
            'available_actions' => ['mark_as_read', 'mark_all_read']
        ]);
        exit();
        
    } catch (Exception $e) {
        error_log("AJAX Error: " . $e->getMessage());
        echo json_encode([
            'success' => false,
            'error' => 'Server error: ' . $e->getMessage(),
            'debug' => 'Exception caught in AJAX handler',
            'timestamp' => date('Y-m-d H:i:s')
        ]);
        exit();
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    $auth->logout();
    header("Location: " . $user_role . "_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Notification Center - EduAssign</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <style>
        .notification-center {
            max-width: 1000px;
            margin: 2rem auto;
            padding: 0 1rem;
        }
        
        .notification-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 2rem;
            padding: 1.5rem;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 15px;
            color: white;
        }
        
        .notification-header h1 {
            margin: 0;
            font-size: 1.8rem;
        }
        
        .notification-stats {
            display: flex;
            gap: 1rem;
            align-items: center;
        }
        
        .stat-badge {
            background: rgba(255, 255, 255, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 25px;
            font-size: 0.9rem;
            backdrop-filter: blur(10px);
        }
        
        .notification-actions {
            margin-bottom: 2rem;
            display: flex;
            gap: 1rem;
            flex-wrap: wrap;
        }
        
        .filter-btn, .action-btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 500;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .filter-btn {
            background: #f8f9fa;
            color: #495057;
        }
        
        .filter-btn.active {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
        }
        
        .action-btn {
            background: linear-gradient(135deg, #ff6b6b, #ee5a24);
            color: white;
        }
        
        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255, 107, 107, 0.3);
        }
        
        .notifications-list {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .notification-item {
            padding: 1.5rem;
            border-bottom: 1px solid #f0f0f0;
            transition: all 0.3s ease;
            cursor: pointer;
            position: relative;
        }
        
        .notification-item:last-child {
            border-bottom: none;
        }
        
        .notification-item:hover {
            background: #f8f9fa;
        }
        
        .notification-item.unread {
            background: linear-gradient(90deg, #e3f2fd 0%, #f3e5f5 100%);
            border-left: 4px solid #2196F3;
        }

        /* Notification type specific colors */
        .notification-item.notification-assignment {
            border-left: 4px solid #007bff !important;
        }
        
        .notification-item.notification-grade {
            border-left: 4px solid #28a745 !important;
        }
        
        .notification-item.notification-deadline {
            border-left: 4px solid #dc3545 !important;
        }
        
        .notification-item.notification-general {
            border-left: 4px solid #ffc107 !important;
        }
        
        .notification-item.notification-approval {
            border-left: 4px solid #6f42c1 !important;
        }

        /* Add subtle animation for new notifications */
        .notification-item.notification-new {
            animation: glow 2s ease-in-out infinite alternate;
        }
        
        @keyframes glow {
            from { box-shadow: 0 0 5px rgba(33, 150, 243, 0.3); }
            to { box-shadow: 0 0 20px rgba(33, 150, 243, 0.6); }
        }
        
        .notification-item.unread::before {
            content: '';
            position: absolute;
            right: 1rem;
            top: 1rem;
            width: 8px;
            height: 8px;
            background: #2196F3;
            border-radius: 50%;
        }
        
        .notification-content {
            flex: 1;
        }
        
        .notification-title {
            font-weight: 600;
            color: #333;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        
        .notification-message {
            color: #666;
            line-height: 1.5;
            margin-bottom: 0.5rem;
        }
        
        .notification-meta {
            display: flex;
            justify-content: space-between;
            align-items: center;
            font-size: 0.85rem;
            color: #999;
        }
        
        .notification-time {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }
        
        .notification-type {
            padding: 0.25rem 0.75rem;
            border-radius: 15px;
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
        }
        
        .type-assignment {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .type-grade {
            background: #e8f5e8;
            color: #2e7d2e;
        }
        
        .type-deadline {
            background: #fff3e0;
            color: #f57c00;
        }
        
        .type-general {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .empty-notifications {
            text-align: center;
            padding: 3rem;
            color: #666;
        }
        
        .empty-notifications i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 1rem;
        }
        
        .preferences-panel {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            padding: 2rem;
            margin-top: 2rem;
            display: none;
        }
        
        .preferences-panel.active {
            display: block;
        }
        
        .preference-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        
        .preference-item:last-child {
            border-bottom: none;
        }
        
        .preference-label {
            font-weight: 500;
            color: #333;
        }
        
        .preference-description {
            font-size: 0.9rem;
            color: #666;
            margin-top: 0.25rem;
        }
        
        .toggle-switch {
            position: relative;
            width: 50px;
            height: 25px;
            background: #ccc;
            border-radius: 25px;
            cursor: pointer;
            transition: background 0.3s;
        }
        
        .toggle-switch.active {
            background: #4CAF50;
        }
        
        .toggle-switch::after {
            content: '';
            position: absolute;
            width: 21px;
            height: 21px;
            background: white;
            border-radius: 50%;
            top: 2px;
            left: 2px;
            transition: left 0.3s;
        }
        
        .toggle-switch.active::after {
            left: 27px;
        }
        
        @media (max-width: 768px) {
            .notification-center {
                padding: 0 0.5rem;
            }
            
            .notification-header {
                flex-direction: column;
                gap: 1rem;
                text-align: center;
            }
            
            .notification-actions {
                justify-content: center;
            }
            
            .filter-btn, .action-btn {
                font-size: 0.9rem;
                padding: 0.6rem 1.2rem;
            }
            
            .notification-item {
                padding: 1rem;
            }
            
            .notification-meta {
                flex-direction: column;
                align-items: flex-start;
                gap: 0.5rem;
            }
        }
    </style>
</head>
<body>
    <br><br><br><br>
    <header>
        <div class="header-container">
            <a href="<?php echo $user_role; ?>_dashboard.php" class="logo">
                <i class="fas fa-<?php echo $user_role === 'student' ? 'user-graduate' : ($user_role === 'teacher' ? 'chalkboard-teacher' : 'user-shield'); ?>"></i>
                <h1>EduAssign <?php echo ucfirst($user_role); ?></h1>
            </a>
            <nav>
                <a href="<?php echo $user_role; ?>_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <?php if ($user_role === 'student'): ?>
                    <a href="student_assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
                    <a href="student_grades.php"><i class="fas fa-chart-line"></i> Grades</a>
                <?php elseif ($user_role === 'teacher'): ?>
                    <a href="teacher_assignments.php"><i class="fas fa-tasks"></i> My Assignments</a>
                    <a href="teacher_submissions.php"><i class="fas fa-file-alt"></i> Submissions</a>
                    <a href="teacher_students.php"><i class="fas fa-users"></i> My Students</a>
                <?php elseif ($user_role === 'admin'): ?>
                    <a href="admin_manage_users.php"><i class="fas fa-users"></i> Manage Users</a>
                    <a href="admin_assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
                    <a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <?php endif; ?>
                <a href="notification_center.php" class="active"><i class="fas fa-bell"></i> Notifications</a>
                <a href="<?php echo $user_role; ?>_profile.php"><i class="fas fa-user"></i> Profile</a>
                <form method="post" style="display:inline;">
                    <button type="submit" name="logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </nav>
        </div>
    </header>

    <main class="main">
        <div class="notification-center">
            <div class="notification-header">
                <div>
                    <h1><i class="fas fa-bell"></i> Notification Center</h1>
                    <p style="margin: 0.5rem 0 0 0; opacity: 0.9;">Stay updated with your academic activities</p>
                </div>
                <div class="notification-stats">
                    <div class="stat-badge">
                        <i class="fas fa-envelope"></i>
                        <span id="unread-count"><?php echo $stats['unread_count']; ?></span> Unread
                    </div>
                    <div class="stat-badge">
                        <i class="fas fa-list"></i>
                        <?php echo $stats['total_received']; ?> Total
                    </div>
                    <?php if ($user_role === 'student'): ?>
                    <div class="stat-badge">
                        <i class="fas fa-tasks"></i>
                        <?php echo $stats['assignments_pending']; ?> Pending
                    </div>
                    <?php if ($stats['grades_today'] > 0): ?>
                    <div class="stat-badge">
                        <i class="fas fa-star"></i>
                        <?php echo $stats['grades_today']; ?> Today
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <div class="notification-actions">
                <button class="filter-btn active" data-filter="all">
                    <i class="fas fa-list"></i> All Notifications
                </button>
                <button class="filter-btn" data-filter="unread">
                    <i class="fas fa-envelope"></i> Unread Only
                </button>
                <button class="filter-btn" data-filter="assignment">
                    <i class="fas fa-tasks"></i> Assignments
                </button>
                <button class="filter-btn" data-filter="grade">
                    <i class="fas fa-star"></i> Grades
                </button>
                <button class="action-btn" id="mark-all-read">
                    <i class="fas fa-check-double"></i> Mark All Read
                </button>
            </div>

            <div class="notifications-list" id="notifications-list">
                <?php if (empty($notifications)): ?>
                    <div class="empty-notifications">
                        <i class="fas fa-bell-slash"></i>
                        <h3>No Notifications</h3>
                        <p>You're all caught up! Check back later for updates.</p>
                    </div>
                <?php else: ?>
                    <?php foreach ($notifications as $notification): ?>
                        <div class="notification-item notification-<?php echo $notification['type']; ?> <?php echo $notification['is_read'] ? '' : 'unread'; ?>" 
                             data-id="<?php echo $notification['id']; ?>"
                             data-type="<?php echo $notification['type']; ?>"
                             data-related-id="<?php echo $notification['related_id'] ?? ''; ?>"
                             data-related-type="<?php echo $notification['related_type'] ?? ''; ?>"
                             style="cursor: pointer;">
                            <div class="notification-content">
                                <div class="notification-title">
                                    <?php
                                    $icon = '';
                                    switch ($notification['type']) {
                                        case 'assignment':
                                            $icon = 'fas fa-tasks';
                                            break;
                                        case 'grade':
                                            $icon = 'fas fa-star';
                                            break;
                                        case 'deadline':
                                            $icon = 'fas fa-clock';
                                            break;
                                        default:
                                            $icon = 'fas fa-info-circle';
                                    }
                                    ?>
                                    <i class="<?php echo $icon; ?>"></i>
                                    <?php echo htmlspecialchars($notification['title']); ?>
                                </div>
                                <div class="notification-message">
                                    <?php echo htmlspecialchars($notification['message']); ?>
                                </div>
                                <div class="notification-meta">
                                    <div class="notification-time">
                                        <i class="fas fa-clock"></i>
                                        <?php echo TimeHelper::formatTimeAgo($notification['created_at']); ?>
                                    </div>
                                    <div class="notification-type type-<?php echo $notification['type']; ?>">
                                        <?php echo ucfirst($notification['type']); ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 EduAssign. All rights reserved.</p>
    </footer>

    <script>
    let currentFilter = 'all';
    let notifications = <?php echo json_encode($notifications); ?>;
    const userRole = <?php echo json_encode($user_role); ?>;

        // Initialize
        document.addEventListener('DOMContentLoaded', function() {
            initializeNotifications();
            
            // Request notification permission
            if ('Notification' in window && Notification.permission === 'default') {
                Notification.requestPermission();
            }
        });

        // Filter notifications
        document.querySelectorAll('.filter-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                document.querySelectorAll('.filter-btn').forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentFilter = this.dataset.filter;
                filterNotifications();
            });
        });

        function filterNotifications() {
            const items = document.querySelectorAll('.notification-item');
            items.forEach(item => {
                const type = item.dataset.type;
                const isUnread = item.classList.contains('unread');
                
                let show = true;
                if (currentFilter === 'unread' && !isUnread) {
                    show = false;
                } else if (currentFilter !== 'all' && currentFilter !== 'unread' && type !== currentFilter) {
                    show = false;
                }
                
                item.style.display = show ? 'block' : 'none';
            });
        }

        // Mark notification as read and navigate
        document.addEventListener('click', function(e) {
            const notificationItem = e.target.closest('.notification-item');
            if (!notificationItem) {
                return;
            }

            const notifType = notificationItem.dataset.type;
            const relatedIdRaw = notificationItem.dataset.relatedId;
            const relatedTypeRaw = notificationItem.dataset.relatedType;
            const relatedId = relatedIdRaw && relatedIdRaw !== 'null' ? relatedIdRaw : null;
            const relatedType = relatedTypeRaw && relatedTypeRaw !== 'null' ? relatedTypeRaw : null;
            let targetUrl = notificationItem.dataset.link || null;

            // Determine navigation target using server-side role instead of URL heuristics
            if (!targetUrl) {
                if (notifType === 'assignment' || notifType === 'deadline') {
                    if (userRole === 'student') {
                        targetUrl = relatedId ? `student_view_assignment.php?id=${relatedId}` : 'student_assignments.php';
                    } else if (userRole === 'teacher') {
                        targetUrl = relatedId ? `teacher_edit_assignment.php?id=${relatedId}` : 'teacher_assignments.php';
                    } else if (userRole === 'admin') {
                        targetUrl = relatedId ? `admin_assignments.php?focus=${relatedId}` : 'admin_assignments.php';
                    }
                } else if (notifType === 'grade') {
                    if (userRole === 'student') {
                        targetUrl = relatedId ? `student_view_assignment.php?id=${relatedId}` : 'student_grades.php';
                    } else if (userRole === 'teacher') {
                        targetUrl = 'teacher_submissions.php';
                    }
                } else if (relatedType === 'registration') {
                    if (userRole === 'admin') {
                        targetUrl = 'admin_approve_registrations.php';
                    } else if (userRole === 'teacher') {
                        targetUrl = 'teacher_approve_students.php';
                    }
                } else if (relatedType === 'submission') {
                    if (userRole === 'teacher') {
                        targetUrl = relatedId ? `teacher_view_submission.php?id=${relatedId}` : 'teacher_submissions.php';
                    } else if (userRole === 'student') {
                        targetUrl = relatedId ? `student_view_assignment.php?id=${relatedId}` : 'student_assignments.php';
                    }
                }
            }

            if (notificationItem.classList.contains('unread')) {
                markAsRead(notificationItem.dataset.id);
            }

            if (targetUrl) {
                setTimeout(() => {
                    window.location.href = targetUrl;
                }, 200);
            }
        });

        function markAsRead(notificationId) {
            console.log('📖 Starting markAsRead for notification:', notificationId);
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'mark_as_read');
            formData.append('notification_id', notificationId);
            
            console.log('📖 Sending AJAX request with data:', {
                ajax: '1',
                action: 'mark_as_read',
                notification_id: notificationId
            });
            
            fetch('notification_center.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('📖 Response status:', response.status);
                console.log('📖 Response headers:', response.headers);
                
                // Check if response is JSON
                const contentType = response.headers.get('content-type');
                if (!contentType || !contentType.includes('application/json')) {
                    console.error('❌ Response is not JSON:', contentType);
                    return response.text().then(text => {
                        console.error('❌ Response text:', text);
                        throw new Error('Server returned non-JSON response: ' + text.substring(0, 200));
                    });
                }
                
                return response.json();
            })
            .then(data => {
                console.log('📖 Mark as read response:', data);
                if (data.success) {
                    const item = document.querySelector(`[data-id="${notificationId}"]`);
                    if (item) {
                        item.classList.remove('unread');
                        console.log('✅ Notification marked as read successfully');
                        
                        // Update visual styling
                        item.style.backgroundColor = '#f8f9fa';
                        item.style.borderLeft = '3px solid #28a745';
                        
                        // Reset styling after animation
                        setTimeout(() => {
                            item.style.backgroundColor = '';
                            item.style.borderLeft = '';
                        }, 2000);
                    }
                    // Update unread count
                    const currentCount = parseInt(document.getElementById('unread-count').textContent);
                    updateUnreadCount(Math.max(0, currentCount - 1));
                } else {
                    console.error('❌ Failed to mark notification as read:', data);
                    
                    // Show detailed error to user
                    let errorMsg = 'Failed to mark notification as read.';
                    if (data.debug) errorMsg += '\nDebug: ' + data.debug;
                    if (data.error) errorMsg += '\nError: ' + data.error;
                    if (data.notification_id) errorMsg += '\nNotification ID: ' + data.notification_id;
                    if (data.user_id) errorMsg += '\nUser ID: ' + data.user_id;
                    
                    alert(errorMsg);
                }
            })
            .catch(error => {
                console.error('❌ Error marking notification as read:', error);
                alert('Error marking notification as read:\n' + error.message + '\n\nCheck console for details.');
            });
        }

        // Mark all as read
        document.getElementById('mark-all-read').addEventListener('click', function() {
            console.log('📖 Marking all notifications as read...');
            
            // Add visual feedback
            this.disabled = true;
            this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Marking...';
            
            const formData = new FormData();
            formData.append('ajax', '1');
            formData.append('action', 'mark_all_read');
            
            fetch('notification_center.php', {
                method: 'POST',
                body: formData
            })
            .then(response => {
                console.log('📖 Mark all response status:', response.status);
                return response.json();
            })
            .then(data => {
                console.log('📖 Mark all as read response:', data);
                if (data.success) {
                    document.querySelectorAll('.notification-item.unread').forEach(item => {
                        item.classList.remove('unread');
                    });
                    updateUnreadCount(0);
                    console.log('✅ All notifications marked as read successfully');
                    
                    // Success feedback
                    this.innerHTML = '<i class="fas fa-check"></i> Done!';
                    this.style.background = '#28a745';
                } else {
                    console.error('❌ Failed to mark all notifications as read:', data);
                    this.innerHTML = '<i class="fas fa-times"></i> Failed';
                    this.style.background = '#dc3545';
                    alert('Failed to mark all notifications as read. Please try again.');
                }
                
                // Restore button after 2 seconds
                setTimeout(() => {
                    this.disabled = false;
                    this.innerHTML = '<i class="fas fa-check-double"></i> Mark All Read';
                    this.style.background = '';
                }, 2000);
            })
            .catch(error => {
                console.error('❌ Error marking all notifications as read:', error);
                this.disabled = false;
                this.innerHTML = '<i class="fas fa-times"></i> Error';
                this.style.background = '#dc3545';
                
                setTimeout(() => {
                    this.innerHTML = '<i class="fas fa-check-double"></i> Mark All Read';
                    this.style.background = '';
                }, 2000);
            });
        });

        function updateUnreadCount(count) {
            document.getElementById('unread-count').textContent = count;
        }

        // Initialize browser notifications
        function initializeNotifications() {
            if ('Notification' in window && Notification.permission === 'granted') {
                // Poll for new notifications every 30 seconds
                setInterval(checkForNewNotifications, 30000);
            }
        }

        function checkForNewNotifications() {
            fetch('check_notifications.php')
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const currentCount = parseInt(document.getElementById('unread-count').textContent);
                    if (data.unread_count > currentCount) {
                        // New notification received
                        updateUnreadCount(data.unread_count);
                        
                        // Show browser notification if permission granted
                        if (Notification.permission === 'granted') {
                            new Notification('New EduAssign Notification', {
                                body: 'You have received a new notification',
                                icon: 'favicon.ico'
                            });
                        }
                        
                        // Refresh notifications list
                        setTimeout(() => {
                            location.reload();
                        }, 1000);
                    }
                }
            })
            .catch(error => console.error('Error:', error));
        }

        // Auto-refresh every 2 minutes (with debugging)
        setInterval(() => {
            console.log('🔄 Auto-refresh check...');
            console.log('🔄 Refreshing notification center...');
            location.reload();
        }, 120000);
        
        // Add visual feedback for actions
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Notification Center loaded successfully');
            console.log('📊 Total notifications:', notifications.length);
            console.log('🔔 Unread count:', document.getElementById('unread-count').textContent);
        });
    </script>
</body>
</html>