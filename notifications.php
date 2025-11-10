<?php
/**
 * Notification API Endpoints
 * Handles AJAX requests for notifications
 */

header('Content-Type: application/json');
header('Cache-Control: no-cache, must-revalidate');

require_once dirname(__DIR__) . '/includes/auth.php';
require_once dirname(__DIR__) . '/includes/notification_system.php';

// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

// Initialize notification system
$notificationSystem = new NotificationSystem($pdo);

$user_id = $_SESSION['user_id'];
$action = $_GET['action'] ?? $_POST['action'] ?? '';

switch ($action) {
    case 'get_notifications':
        $limit = intval($_GET['limit'] ?? 20);
        $unread_only = isset($_GET['unread_only']) && $_GET['unread_only'] === 'true';
        $since_id = intval($_GET['since_id'] ?? 0);
        
        $where_conditions = ["user_id = ?"];
        $params = [$user_id];
        
        if ($unread_only) {
            $where_conditions[] = "is_read = 0";
        }
        
        if ($since_id > 0) {
            $where_conditions[] = "id > ?";
            $params[] = $since_id;
        }
        
        $where_clause = "WHERE " . implode(" AND ", $where_conditions);
        
        $notifications = $notificationSystem->getUserNotifications($user_id, $limit, $unread_only);
        $unread_count = $notificationSystem->getUnreadCount($user_id);
        
        echo json_encode([
            'success' => true,
            'notifications' => $notifications,
            'unread_count' => $unread_count
        ]);
        break;
        
    case 'mark_as_read':
        $notification_id = intval($_POST['notification_id'] ?? 0);
        
        if ($notification_id > 0) {
            $result = $notificationSystem->markAsRead($notification_id, $user_id);
            $unread_count = $notificationSystem->getUnreadCount($user_id);
            
            echo json_encode([
                'success' => $result,
                'unread_count' => $unread_count
            ]);
        } else {
            echo json_encode(['error' => 'Invalid notification ID']);
        }
        break;
        
    case 'mark_all_read':
        $result = $notificationSystem->markAllAsRead($user_id);
        $unread_count = $notificationSystem->getUnreadCount($user_id);
        
        echo json_encode([
            'success' => $result,
            'unread_count' => $unread_count
        ]);
        break;
        
    case 'get_unread_count':
        $unread_count = $notificationSystem->getUnreadCount($user_id);
        
        echo json_encode([
            'success' => true,
            'unread_count' => $unread_count
        ]);
        break;
        
    case 'get_preferences':
        $preferences = $notificationSystem->getUserPreferences($user_id);
        
        echo json_encode([
            'success' => true,
            'preferences' => $preferences
        ]);
        break;
        
    case 'update_preferences':
        $preferences = [
            'email_notifications' => intval($_POST['email_notifications'] ?? 1),
            'browser_notifications' => intval($_POST['browser_notifications'] ?? 1),
            'assignment_notifications' => intval($_POST['assignment_notifications'] ?? 1),
            'grade_notifications' => intval($_POST['grade_notifications'] ?? 1),
            'deadline_reminders' => intval($_POST['deadline_reminders'] ?? 1),
            'general_announcements' => intval($_POST['general_announcements'] ?? 1)
        ];
        
        $result = $notificationSystem->updateUserPreferences($user_id, $preferences);
        
        echo json_encode([
            'success' => $result,
            'message' => $result ? 'Preferences updated successfully' : 'Failed to update preferences'
        ]);
        break;
        
    default:
        http_response_code(400);
        echo json_encode(['error' => 'Invalid action']);
        break;
}
?>