<?php
/**
 * Notification System Class
 * Handles creation, management, and delivery of notifications
 */

class NotificationSystem {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Create a new notification
     */
    public function createNotification($user_id, $title, $message, $type = 'general', $related_id = null, $related_type = null, $priority = 'medium') {
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO notifications (user_id, title, message, type, related_id, related_type, priority) 
                VALUES (?, ?, ?, ?, ?, ?, ?)
            ");
            $result = $stmt->execute([$user_id, $title, $message, $type, $related_id, $related_type, $priority]);
            
            if ($result) {
                return $this->pdo->lastInsertId();
            }
            return false;
        } catch (PDOException $e) {
            error_log("Notification creation error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create notification for new assignment
     */
    public function notifyNewAssignment($assignment_id, $assignment_title, $teacher_name, $due_date) {
        try {
            // First get the assignment details and teacher's course
            $stmt = $this->pdo->prepare("
                SELECT a.subject, u.course 
                FROM assignments a 
                JOIN users u ON a.teacher_id = u.id 
                WHERE a.id = ?
            ");
            $stmt->execute([$assignment_id]);
            $assignment = $stmt->fetch();
            
            if (!$assignment) {
                error_log("Assignment not found: " . $assignment_id);
                return false;
            }
            
            // Get only students from the same course as the teacher
            $stmt = $this->pdo->prepare("
                SELECT id FROM users 
                WHERE role = 'student' 
                AND status = 'active' 
                AND course = ?
            ");
            $stmt->execute([$assignment['course']]);
            $students = $stmt->fetchAll(PDO::FETCH_COLUMN);
            
            $title = "New Assignment Posted";
            $message = "A new assignment '{$assignment_title}' for {$assignment['subject']} has been posted by {$teacher_name}. Due date: " . date('M j, Y g:i A', strtotime($due_date));
            
            $notifications_sent = 0;
            foreach ($students as $student_id) {
                if ($this->createNotification($student_id, $title, $message, 'assignment', $assignment_id, 'assignment', 'high')) {
                    $notifications_sent++;
                }
            }
            
            error_log("Assignment notification sent to {$notifications_sent} students in course: " . $assignment['course']);
            return true;
        } catch (PDOException $e) {
            error_log("Assignment notification error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Create notification for graded assignment
     */
    public function notifyGradeReceived($student_id, $assignment_title, $grade, $max_score) {
        $title = "Assignment Graded";
        $percentage = round(($grade / $max_score) * 100, 1);
        $message = "Your assignment '{$assignment_title}' has been graded. Score: {$grade}/{$max_score} ({$percentage}%)";
        
        return $this->createNotification($student_id, $title, $message, 'grade', null, 'submission', 'high');
    }
    
    /**
     * Create deadline reminder notifications (multiple periods)
     */
    public function sendDeadlineReminders() {
        try {
            // Define reminder periods
            $reminder_periods = [
                ['days' => 7, 'message' => 'one week', 'priority' => 'medium'],
                ['days' => 3, 'message' => '3 days', 'priority' => 'medium'],
                ['days' => 1, 'message' => '1 day', 'priority' => 'high'],
                ['hours' => 2, 'message' => '2 hours', 'priority' => 'high']
            ];
            
            foreach ($reminder_periods as $period) {
                // Calculate time range for this reminder period
                if (isset($period['days'])) {
                    $start_time = "DATE_ADD(NOW(), INTERVAL " . ($period['days'] - 0.1) . " DAY)";
                    $end_time = "DATE_ADD(NOW(), INTERVAL " . ($period['days'] + 0.1) . " DAY)";
                } else {
                    $start_time = "DATE_ADD(NOW(), INTERVAL " . ($period['hours'] - 0.5) . " HOUR)";
                    $end_time = "DATE_ADD(NOW(), INTERVAL " . ($period['hours'] + 0.5) . " HOUR)";
                }
                
                // Get assignments due in this period
                $stmt = $this->pdo->prepare("
                    SELECT a.*, u.name as teacher_name 
                    FROM assignments a 
                    JOIN users u ON a.teacher_id = u.id
                    WHERE a.status = 'active' 
                    AND a.due_date BETWEEN {$start_time} AND {$end_time}
                ");
                $stmt->execute();
                $assignments = $stmt->fetchAll();
                
                foreach ($assignments as $assignment) {
                    // Get students from the same course who haven't submitted yet
                    $stmt = $this->pdo->prepare("
                        SELECT id FROM users 
                        WHERE role = 'student' 
                        AND status = 'active' 
                        AND course = ?
                        AND id NOT IN (
                            SELECT student_id 
                            FROM submissions 
                            WHERE assignment_id = ?
                        )
                    ");
                    $stmt->execute([$assignment['course'], $assignment['id']]);
                    $students = $stmt->fetchAll(PDO::FETCH_COLUMN);
                    
                    $title = "Assignment Deadline Reminder";
                    $message = "⏰ Reminder: Assignment '{$assignment['title']}' for {$assignment['subject']} is due in {$period['message']}. Due date: " . date('M j, Y g:i A', strtotime($assignment['due_date']));
                    
                    foreach ($students as $student_id) {
                        // Check if we already sent this type of reminder for this assignment
                        $existing_reminder = $this->pdo->prepare("
                            SELECT id FROM notifications 
                            WHERE user_id = ? 
                            AND related_id = ? 
                            AND type = 'deadline' 
                            AND message LIKE ?
                            AND created_at > DATE_SUB(NOW(), INTERVAL 1 DAY)
                        ");
                        $existing_reminder->execute([$student_id, $assignment['id'], "%{$period['message']}%"]);
                        
                        if ($existing_reminder->rowCount() == 0) {
                            $this->createNotification($student_id, $title, $message, 'deadline', $assignment['id'], 'assignment', $period['priority']);
                        }
                    }
                }
            }
            
            return true;
        } catch (PDOException $e) {
            error_log("Deadline reminder error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user notifications
     */
    public function getUserNotifications($user_id, $limit = 20, $unread_only = false) {
        try {
            $where_clause = "WHERE user_id = ?";
            $params = [$user_id];
            
            if ($unread_only) {
                $where_clause .= " AND is_read = 0";
            }
            
            $stmt = $this->pdo->prepare("
                SELECT * FROM notifications 
                {$where_clause}
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $params[] = $limit;
            $stmt->execute($params);
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Get notifications error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Mark notification as read
     */
    public function markAsRead($notification_id, $user_id) {
        try {
            // First check if the notification exists and belongs to the user
            $check_stmt = $this->pdo->prepare("
                SELECT id, is_read FROM notifications 
                WHERE id = ? AND user_id = ?
            ");
            $check_stmt->execute([$notification_id, $user_id]);
            $notification = $check_stmt->fetch();
            
            if (!$notification) {
                error_log("Mark as read error: Notification {$notification_id} not found for user {$user_id}");
                return false;
            }
            
            if ($notification['is_read'] == 1) {
                error_log("Mark as read info: Notification {$notification_id} already marked as read");
                return true; // Already read, consider it success
            }
            
            // Check if read_at column exists
            try {
                $columns_stmt = $this->pdo->query("SHOW COLUMNS FROM notifications LIKE 'read_at'");
                $has_read_at = $columns_stmt->rowCount() > 0;
            } catch (PDOException $e) {
                $has_read_at = false;
            }
            
            // Mark as read (with or without read_at column)
            if ($has_read_at) {
                $stmt = $this->pdo->prepare("
                    UPDATE notifications 
                    SET is_read = 1, read_at = NOW() 
                    WHERE id = ? AND user_id = ?
                ");
            } else {
                $stmt = $this->pdo->prepare("
                    UPDATE notifications 
                    SET is_read = 1 
                    WHERE id = ? AND user_id = ?
                ");
            }
            
            $result = $stmt->execute([$notification_id, $user_id]);
            $affected_rows = $stmt->rowCount();
            
            error_log("Mark as read result: notification_id={$notification_id}, user_id={$user_id}, result=" . ($result ? 'true' : 'false') . ", affected_rows={$affected_rows}, has_read_at=" . ($has_read_at ? 'true' : 'false'));
            
            return $result && $affected_rows > 0;
        } catch (PDOException $e) {
            error_log("Mark as read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Mark all notifications as read for user
     */
    public function markAllAsRead($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notifications 
                SET is_read = 1, read_at = NOW() 
                WHERE user_id = ? AND is_read = 0
            ");
            return $stmt->execute([$user_id]);
        } catch (PDOException $e) {
            error_log("Mark all as read error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get unread notification count
     */
    public function getUnreadCount($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) 
                FROM notifications 
                WHERE user_id = ? AND is_read = 0
            ");
            $stmt->execute([$user_id]);
            return $stmt->fetchColumn();
        } catch (PDOException $e) {
            error_log("Get unread count error: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get simple notification statistics
     */
    public function getNotificationStats($user_id) {
        try {
            $stats = [];
            
            // Total notifications
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $stats['total_received'] = $stmt->fetchColumn();
            
            // Unread count
            $stats['unread_count'] = $this->getUnreadCount($user_id);
            
            // Assignments pending (for students)
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM notifications 
                WHERE user_id = ? AND type = 'assignment' AND is_read = 0
            ");
            $stmt->execute([$user_id]);
            $stats['assignments_pending'] = $stmt->fetchColumn();
            
            // Grades received today
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM notifications 
                WHERE user_id = ? AND type = 'grade' AND DATE(created_at) = CURDATE()
            ");
            $stmt->execute([$user_id]);
            $stats['grades_today'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            error_log("Get notification stats error: " . $e->getMessage());
            return ['total_received' => 0, 'unread_count' => 0, 'assignments_pending' => 0, 'grades_today' => 0];
        }
    }
    
    /**
     * Delete old notifications
     */
    public function cleanupOldNotifications($days = 30) {
        try {
            $stmt = $this->pdo->prepare("
                DELETE FROM notifications 
                WHERE created_at < DATE_SUB(NOW(), INTERVAL ? DAY)
            ");
            return $stmt->execute([$days]);
        } catch (PDOException $e) {
            error_log("Cleanup notifications error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Get user notification preferences
     */
    public function getUserPreferences($user_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT * FROM notification_preferences 
                WHERE user_id = ?
            ");
            $stmt->execute([$user_id]);
            $prefs = $stmt->fetch();
            
            if (!$prefs) {
                // Create default preferences
                $stmt = $this->pdo->prepare("
                    INSERT INTO notification_preferences (user_id) VALUES (?)
                ");
                $stmt->execute([$user_id]);
                return $this->getUserPreferences($user_id);
            }
            
            return $prefs;
        } catch (PDOException $e) {
            error_log("Get preferences error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Notify teacher about assignment creation
     */
    public function notifyTeacherAssignmentCreated($teacher_id, $assignment_title, $subject, $assignment_id) {
        $title = "Assignment Created Successfully";
        $message = "You have successfully created the assignment '{$assignment_title}' for {$subject}. Students have been notified and can now view the assignment.";
        
        return $this->createNotification($teacher_id, $title, $message, 'assignment', $assignment_id, 'assignment', 'medium');
    }
    
    /**
     * Notify teacher about assignment update
     */
    public function notifyTeacherAssignmentUpdated($teacher_id, $assignment_title, $assignment_id) {
        $title = "Assignment Updated Successfully";
        $message = "You have successfully updated the assignment '{$assignment_title}'. All changes have been saved and active students have been notified.";
        
        return $this->createNotification($teacher_id, $title, $message, 'assignment', $assignment_id, 'assignment', 'medium');
    }
    
    /**
     * Notify teacher about new submission
     */
    public function notifyTeacherNewSubmission($teacher_id, $assignment_title, $student_name, $submission_id) {
        $title = "New Assignment Submission";
        $message = "{$student_name} has submitted their work for assignment '{$assignment_title}'. You can now review and grade the submission.";
        
        return $this->createNotification($teacher_id, $title, $message, 'submission', $submission_id, 'submission', 'high');
    }
    
    /**
     * Update user notification preferences
     */
    public function updateUserPreferences($user_id, $preferences) {
        try {
            $stmt = $this->pdo->prepare("
                UPDATE notification_preferences 
                SET email_notifications = ?, browser_notifications = ?, assignment_notifications = ?, 
                    grade_notifications = ?, deadline_reminders = ?, general_announcements = ?
                WHERE user_id = ?
            ");
            return $stmt->execute([
                $preferences['email_notifications'],
                $preferences['browser_notifications'], 
                $preferences['assignment_notifications'],
                $preferences['grade_notifications'],
                $preferences['deadline_reminders'],
                $preferences['general_announcements'],
                $user_id
            ]);
        } catch (PDOException $e) {
            error_log("Update preferences error: " . $e->getMessage());
            return false;
        }
    }
}
?>