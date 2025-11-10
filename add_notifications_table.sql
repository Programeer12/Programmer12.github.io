-- Add notifications table for the real-time notification system
-- Run this SQL to add the notifications functionality

-- Create notifications table
CREATE TABLE IF NOT EXISTS `notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL,
    `title` varchar(255) NOT NULL,
    `message` text NOT NULL,
    `type` enum('assignment','grade','deadline','general','approval') NOT NULL DEFAULT 'general',
    `related_id` int(11) DEFAULT NULL,
    `related_type` enum('assignment','submission','user') DEFAULT NULL,
    `is_read` tinyint(1) NOT NULL DEFAULT 0,
    `priority` enum('low','medium','high') NOT NULL DEFAULT 'medium',
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `read_at` timestamp NULL DEFAULT NULL,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    KEY `is_read` (`is_read`),
    KEY `created_at` (`created_at`),
    CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Create user notification preferences table
CREATE TABLE IF NOT EXISTS `notification_preferences` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) NOT NULL UNIQUE,
    `email_notifications` tinyint(1) NOT NULL DEFAULT 1,
    `browser_notifications` tinyint(1) NOT NULL DEFAULT 1,
    `assignment_notifications` tinyint(1) NOT NULL DEFAULT 1,
    `grade_notifications` tinyint(1) NOT NULL DEFAULT 1,
    `deadline_reminders` tinyint(1) NOT NULL DEFAULT 1,
    `general_announcements` tinyint(1) NOT NULL DEFAULT 1,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    CONSTRAINT `notification_preferences_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default notification preferences for existing users
INSERT IGNORE INTO `notification_preferences` (`user_id`, `email_notifications`, `browser_notifications`, `assignment_notifications`, `grade_notifications`, `deadline_reminders`, `general_announcements`)
SELECT `id`, 1, 1, 1, 1, 1, 1 FROM `users`;