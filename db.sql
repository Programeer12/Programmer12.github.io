create DATABASE eduassign_main;

use eduassign_main;
SELECT * FROM `submissions`;

ALTER TABLE submissions ADD COLUMN viewed TINYINT(1) NOT NULL DEFAULT 0 AFTER status;
-- Users table for approved users
CREATE TABLE `users` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `username` varchar(50) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `role` enum('student','teacher','admin') NOT NULL,
    `name` varchar(100) NOT NULL,
    `email` varchar(100) NOT NULL UNIQUE,
    `subject` varchar(50) DEFAULT NULL,
    `course` varchar(50) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Pending registrations table
CREATE TABLE `pending_registrations` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `name` varchar(100) NOT NULL,
    `username` varchar(50) NOT NULL UNIQUE,
    `email` varchar(100) NOT NULL UNIQUE,
    `password` varchar(255) NOT NULL,
    `role` enum('student','teacher') NOT NULL,
    `subject` varchar(50) NOT NULL,
    `course` varchar(50) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Assignments table
CREATE TABLE `assignments` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(200) NOT NULL,
    `description` text NOT NULL,
    `subject` varchar(50) NOT NULL,
    `teacher_id` int(11) NOT NULL,
    `due_date` datetime NOT NULL,
    `max_score` int(11) NOT NULL DEFAULT 100,
    `assignment_period_start` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `assignment_period_end` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (`id`),
    KEY `teacher_id` (`teacher_id`),
    CONSTRAINT `assignments_ibfk_1` FOREIGN KEY (`teacher_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Submissions table
CREATE TABLE `submissions` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `assignment_id` int(11) NOT NULL,
    `student_id` int(11) NOT NULL,
    `file_path` varchar(255) NOT NULL,
    `submitted_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `grade` decimal(5,2) DEFAULT NULL,
    `feedback` text DEFAULT NULL,
    `status` enum('submitted','graded','late') NOT NULL DEFAULT 'submitted',
    PRIMARY KEY (`id`),
    KEY `assignment_id` (`assignment_id`),
    KEY `student_id` (`student_id`),
    CONSTRAINT `submissions_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `submissions_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Announcements table
CREATE TABLE `announcements` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `title` varchar(200) NOT NULL,
    `content` text NOT NULL,
    `author_id` int(11) NOT NULL,
    `subject` varchar(50) DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `status` enum('active','inactive') NOT NULL DEFAULT 'active',
    PRIMARY KEY (`id`),
    KEY `author_id` (`author_id`),
    CONSTRAINT `announcements_ibfk_1` FOREIGN KEY (`author_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- System settings table
CREATE TABLE `system_settings` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `setting_key` varchar(100) NOT NULL UNIQUE,
    `setting_value` text NOT NULL,
    `description` varchar(255) DEFAULT NULL,
    `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Activity logs table
CREATE TABLE `activity_logs` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `user_id` int(11) DEFAULT NULL,
    `action` varchar(100) NOT NULL,
    `description` text NOT NULL,
    `ip_address` varchar(45) DEFAULT NULL,
    `user_agent` text DEFAULT NULL,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `user_id` (`user_id`),
    CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Assignment notifications table
CREATE TABLE `assignment_notifications` (
    `id` int(11) NOT NULL AUTO_INCREMENT,
    `assignment_id` int(11) NOT NULL,
    `student_id` int(11) NOT NULL,
    `notification_type` enum('new_assignment','reminder','graded') NOT NULL DEFAULT 'new_assignment',
    `is_read` tinyint(1) NOT NULL DEFAULT 0,
    `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `assignment_id` (`assignment_id`),
    KEY `student_id` (`student_id`),
    CONSTRAINT `assignment_notifications_ibfk_1` FOREIGN KEY (`assignment_id`) REFERENCES `assignments` (`id`) ON DELETE CASCADE,
    CONSTRAINT `assignment_notifications_ibfk_2` FOREIGN KEY (`student_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert default admin user (username: admin, password: admin123)
INSERT INTO `users` (`username`, `password`, `role`, `name`, `email`, `subject`) VALUES
('admin', '$2y$10$WlnXNo5CNSA6g2I1P8fLru6WW1AQZpoURMwPfM36CZRmTLKEuiWHK', 'admin', 'System Administrator', 'admin@eduassign.com', NULL);

-- Insert default system settings
INSERT INTO `system_settings` (`setting_key`, `setting_value`, `description`) VALUES
('site_name', 'EduAssign', 'Website name'),
('site_description', 'Online College Assignment Management System', 'Website description'),
('max_file_size', '10485760', 'Maximum file upload size in bytes (10MB)'),
('allowed_file_types', 'pdf,doc,docx,txt,jpg,jpeg,png', 'Allowed file types for submissions'),
('late_submission_penalty', '10', 'Percentage penalty for late submissions');

select * from users;

SELECT COUNT(*) AS assignment_count
FROM assignments
WHERE teacher_id = 9 AND status = 'active';

SELECT * FROM users;
