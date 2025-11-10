<?php
/**
 * Simple notification checker for dashboard
 * Returns JSON with latest notification data
 */
header('Content-Type: application/json');
require_once 'includes/auth.php';
require_once 'includes/notification_system.php';

// Check if user is authenticated
if (!$auth->isLoggedIn()) {
    echo json_encode(['error' => 'Not authenticated']);
    exit();
}

// Initialize notification system
$notificationSystem = new NotificationSystem($pdo);

$user_id = $auth->getCurrentUser()['id'];

try {
    // Get latest notification
    $notifications = $notificationSystem->getUserNotifications($user_id, 1);
    $unread_count = $notificationSystem->getUnreadCount($user_id);
    
    $response = [
        'success' => true,
        'unread_count' => $unread_count,
        'has_notifications' => !empty($notifications),
        'latest_notification' => !empty($notifications) ? $notifications[0] : null
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>