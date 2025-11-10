<?php
require_once dirname(__DIR__) . '/db.php';

class AssignmentHelper {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * Distribute assignment to students based on course and subject
     */
    public function distributeAssignment($assignment_id) {
        try {
            // Get assignment details
            $stmt = $this->pdo->prepare("SELECT a.*, u.course FROM assignments a JOIN users u ON a.teacher_id = u.id WHERE a.id = ?");
            $stmt->execute([$assignment_id]);
            $assignment = $stmt->fetch();
            
            // If assignment doesn't have course info, get it from the teacher
            if (empty($assignment['course'])) {
                $teacherStmt = $this->pdo->prepare("SELECT course FROM users WHERE id = ?");
                $teacherStmt->execute([$assignment['teacher_id']]);
                $teacher = $teacherStmt->fetch();
                if ($teacher && !empty($teacher['course'])) {
                    $assignment['course'] = $teacher['course'];
                }
            }
            
            error_log("Distributing assignment ID: {$assignment_id}");
            
            if (!$assignment) {
                error_log("Assignment not found: {$assignment_id}");
                return ['success' => false, 'message' => 'Assignment not found'];
            }
            
            error_log("Assignment subject: {$assignment['subject']}, Course: {$assignment['course']}");
            
            // Get students who match the assignment course (case-insensitive)
            $stmt = $this->pdo->prepare("
                SELECT id, name, email, subject, course 
                FROM users 
                WHERE role = 'student' 
                AND status = 'active' 
                AND LOWER(course) = LOWER(?)
            ");
            $stmt->execute([$assignment['course']]);
            $students = $stmt->fetchAll();
            
            error_log("Found " . count($students) . " students for subject: {$assignment['subject']}");
            
            if (empty($students)) {
                error_log("No students found for subject: {$assignment['subject']}");
                return ['success' => false, 'message' => 'No students found for subject: ' . $assignment['subject']];
            }
            
            // Create notification records for each student
            $notified_count = 0;
            foreach ($students as $student) {
                error_log("Processing student ID: {$student['id']}, Name: {$student['name']}");
                
                // Check if notification already exists
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) FROM assignment_notifications 
                    WHERE assignment_id = ? AND student_id = ?
                ");
                $stmt->execute([$assignment_id, $student['id']]);
                $exists = $stmt->fetchColumn();
                
                error_log("Notification exists check: " . ($exists ? 'Yes' : 'No'));
                
                if ($exists == 0) {
                    // Insert notification
                    try {
                        $stmt = $this->pdo->prepare("
                            INSERT INTO assignment_notifications 
                            (assignment_id, student_id, notification_type, created_at) 
                            VALUES (?, ?, 'new_assignment', NOW())
                        ");
                        $stmt->execute([$assignment_id, $student['id']]);
                        $notified_count++;
                        error_log("Notification created for student ID: {$student['id']}");
                    } catch (PDOException $insertError) {
                        error_log("Error creating notification: " . $insertError->getMessage());
                    }
                }
            }
            
            error_log("Distribution complete. Notified {$notified_count} students");
            
            return [
                'success' => true, 
                'message' => "Assignment distributed to {$notified_count} students",
                'notified_count' => $notified_count
            ];
            
        } catch (PDOException $e) {
            error_log("Error in distributeAssignment: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    /**
     * Get assignments for a specific student
     */
    public function getStudentAssignments($student_id) {
        try {
            // First, get the student's course
            $stmt = $this->pdo->prepare("SELECT course FROM users WHERE id = ? AND role = 'student'");
            $stmt->execute([$student_id]);
            $student = $stmt->fetch();
            
            if (!$student) {
                error_log("Student not found. Student ID: {$student_id}");
                return [];
            }
            
            $student_course = $student['course'];
            
            // Get ALL assignments for this student's course (including past assignments)
            $stmt = $this->pdo->prepare("
                SELECT a.*, u.name as teacher_name,
                       CASE 
                           WHEN NOW() BETWEEN a.assignment_period_start AND a.assignment_period_end THEN 'active'
                           WHEN NOW() < a.assignment_period_start THEN 'upcoming'
                           WHEN NOW() > a.assignment_period_end THEN 'expired'
                       END as period_status,
                       s.id as submission_id,
                       s.status as submission_status,
                       s.grade as submission_grade
                FROM assignments a
                LEFT JOIN users u ON a.teacher_id = u.id
                LEFT JOIN submissions s ON a.id = s.assignment_id AND s.student_id = ?
                WHERE u.course = ? AND a.status = 'active'
                ORDER BY a.created_at DESC, a.due_date DESC
            ");
            $stmt->execute([$student_id, $student_course]);
            $assignments = $stmt->fetchAll();
            
            error_log("Retrieved " . count($assignments) . " assignments for student ID: {$student_id} in course: {$student_course}");
            
            return $assignments;
        } catch (PDOException $e) {
            error_log("Error in getStudentAssignments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Get assignments for a specific teacher
     */
    public function getTeacherAssignments($teacher_id) {
        try {
            // First get teacher's course
            $stmt = $this->pdo->prepare("SELECT course FROM users WHERE id = ? AND role = 'teacher'");
            $stmt->execute([$teacher_id]);
            $teacher = $stmt->fetch();
            
            if (!$teacher || !$teacher['course']) {
                error_log("Teacher course not found for teacher ID: {$teacher_id}");
                return [];
            }
            
            $teacher_course = $teacher['course'];
            
            // Get assignments with student count and submission stats
            $stmt = $this->pdo->prepare("
                SELECT a.*, 
                       COUNT(DISTINCT s.id) as submissions_count,
                       COUNT(DISTINCT CASE WHEN s.status = 'graded' THEN s.id END) as graded_count,
                       (SELECT COUNT(*) FROM users WHERE role = 'student' AND status = 'active' AND course = ?) as total_students
                FROM assignments a
                LEFT JOIN submissions s ON a.id = s.assignment_id
                WHERE a.teacher_id = ?
                GROUP BY a.id
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$teacher_course, $teacher_id]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error in getTeacherAssignments: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if assignment is currently active (within period)
     */
    public function isAssignmentActive($assignment_id) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM assignments 
                WHERE id = ? 
                AND status = 'active'
                AND NOW() BETWEEN assignment_period_start AND assignment_period_end
            ");
            $stmt->execute([$assignment_id]);
            return $stmt->fetchColumn() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }
    
    /**
     * Get assignment statistics
     */
    public function getAssignmentStats() {
        try {
            $stats = [];
            
            // Total assignments
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM assignments");
            $stmt->execute();
            $stats['total'] = $stmt->fetchColumn();
            
            // Active assignments
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM assignments WHERE status = 'active'");
            $stmt->execute();
            $stats['active'] = $stmt->fetchColumn();
            
            // Currently available assignments
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM assignments 
                WHERE status = 'active' 
                AND NOW() BETWEEN assignment_period_start AND assignment_period_end
            ");
            $stmt->execute();
            $stats['available'] = $stmt->fetchColumn();
            
            // Overdue assignments
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) FROM assignments 
                WHERE status = 'active' 
                AND due_date < NOW()
            ");
            $stmt->execute();
            $stats['overdue'] = $stmt->fetchColumn();
            
            return $stats;
        } catch (PDOException $e) {
            return ['total' => 0, 'active' => 0, 'available' => 0, 'overdue' => 0];
        }
    }
    
    /**
     * Fix missing assignment notifications for all students
     * This can be run as a maintenance task
     */
    public function fixMissingAssignmentNotifications() {
        try {
            error_log("Starting to fix missing assignment notifications");
            
            // Get all active students
            $stmt = $this->pdo->prepare("SELECT id, subject FROM users WHERE role = 'student' AND status = 'active'");
            $stmt->execute();
            $students = $stmt->fetchAll();
            
            $fixed_count = 0;
            
            foreach ($students as $student) {
                $student_id = $student['id'];
                $student_subject = $student['subject'];
                
                if (empty($student_subject)) {
                    error_log("Skipping student ID: {$student_id} - No subject assigned");
                    continue;
                }
                
                error_log("Processing student ID: {$student_id}, Subject: {$student_subject}");
                
                // Get assignments for this student's subject
                $stmt = $this->pdo->prepare("
                    SELECT a.id 
                    FROM assignments a 
                    WHERE LOWER(a.subject) = LOWER(?) 
                    AND a.status = 'active'
                ");
                $stmt->execute([$student_subject]);
                $assignments = $stmt->fetchAll();
                
                foreach ($assignments as $assignment) {
                    $assignment_id = $assignment['id'];
                    
                    // Check if notification already exists
                    $stmt = $this->pdo->prepare("
                        SELECT COUNT(*) FROM assignment_notifications 
                        WHERE assignment_id = ? AND student_id = ?
                    ");
                    $stmt->execute([$assignment_id, $student_id]);
                    
                    if ($stmt->fetchColumn() == 0) {
                        // Insert notification
                        $stmt = $this->pdo->prepare("
                            INSERT INTO assignment_notifications 
                            (assignment_id, student_id, notification_type, created_at) 
                            VALUES (?, ?, 'new_assignment', NOW())
                        ");
                        $stmt->execute([$assignment_id, $student_id]);
                        $fixed_count++;
                        error_log("Created missing notification for student ID: {$student_id}, assignment ID: {$assignment_id}");
                    }
                }
            }
            
            error_log("Fixed {$fixed_count} missing assignment notifications");
            return [
                'success' => true,
                'message' => "Fixed {$fixed_count} missing assignment notifications",
                'fixed_count' => $fixed_count
            ];
            
        } catch (PDOException $e) {
            error_log("Error in fixMissingAssignmentNotifications: " . $e->getMessage());
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }

}

// Initialize the helper and make it available globally
global $pdo;
$assignmentHelper = new AssignmentHelper($pdo);
$GLOBALS['assignmentHelper'] = $assignmentHelper;
?>