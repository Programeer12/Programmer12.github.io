<?php
/**
 * Cron Job Script for Sending Deadline Reminders
 * Run this script daily to send deadline reminder notifications
 * 
 * Add to crontab:
 * 0 9 * * * /usr/bin/php /path/to/your/project/cron_deadline_reminders.php
 */

// Set the script to run from command line only
if (php_sapi_name() !== 'cli') {
    die('This script can only be run from the command line.');
}

// Include required files
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/notification_system.php';

// Initialize notification system
$notificationSystem = new NotificationSystem($pdo);

echo "[" . date('Y-m-d H:i:s') . "] Starting deadline reminder job...\n";

try {
    // Send deadline reminders
    $result = $notificationSystem->sendDeadlineReminders();
    
    if ($result) {
        echo "[" . date('Y-m-d H:i:s') . "] Deadline reminders sent successfully.\n";
    } else {
        echo "[" . date('Y-m-d H:i:s') . "] No deadline reminders needed or error occurred.\n";
    }
    
    // Clean up old notifications (older than 30 days)
    $cleanup_result = $notificationSystem->cleanupOldNotifications(30);
    
    if ($cleanup_result) {
        echo "[" . date('Y-m-d H:i:s') . "] Old notifications cleaned up.\n";
    }
    
} catch (Exception $e) {
    echo "[" . date('Y-m-d H:i:s') . "] Error: " . $e->getMessage() . "\n";
    error_log("Deadline reminder cron job error: " . $e->getMessage());
}

echo "[" . date('Y-m-d H:i:s') . "] Deadline reminder job completed.\n";
?>