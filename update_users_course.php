<?php
require_once 'db.php';

try {
    // Check if users table has course column
    $stmt = $pdo->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h3>Users Table Structure:</h3>";
    echo "<pre>";
    foreach ($columns as $col) {
        echo $col['Field'] . " - " . $col['Type'] . "\n";
    }
    echo "</pre>";
    
    // Check if course column exists
    $courseExists = false;
    foreach ($columns as $col) {
        if ($col['Field'] === 'course') {
            $courseExists = true;
            break;
        }
    }
    
    // Add course column if it doesn't exist
    if (!$courseExists) {
        echo "<p>Adding 'course' column to users table...</p>";
        $pdo->exec("ALTER TABLE users ADD COLUMN course varchar(50) DEFAULT NULL AFTER subject");
        echo "<p>Course column added successfully.</p>";
    } else {
        echo "<p>Course column already exists in users table.</p>";
    }
    
    // Update existing users with default course values based on patterns in their data
    echo "<p>Updating existing users with default course values...</p>";
    
    // First, update teachers with BCA course if their subject contains programming-related keywords
    $updateBCATeachers = $pdo->prepare("UPDATE users SET course = 'BCA' WHERE role = 'teacher' AND course IS NULL AND (subject LIKE '%programming%' OR subject LIKE '%computer%' OR subject LIKE '%database%' OR subject LIKE '%web%' OR subject LIKE '%java%' OR subject LIKE '%python%')");
    $updateBCATeachers->execute();
    $bcaTeachersUpdated = $updateBCATeachers->rowCount();
    
    // Update teachers with BCom course if their subject contains commerce-related keywords
    $updateBComTeachers = $pdo->prepare("UPDATE users SET course = 'BCom' WHERE role = 'teacher' AND course IS NULL AND (subject LIKE '%commerce%' OR subject LIKE '%accounting%' OR subject LIKE '%finance%' OR subject LIKE '%economics%' OR subject LIKE '%business%')");
    $updateBComTeachers->execute();
    $bcomTeachersUpdated = $updateBComTeachers->rowCount();
    
    // For remaining teachers without course, set a default based on ID (even = BCA, odd = BCom)
    $updateRemainingTeachers = $pdo->prepare("UPDATE users SET course = CASE WHEN id % 2 = 0 THEN 'BCA' ELSE 'BCom' END WHERE role = 'teacher' AND course IS NULL");
    $updateRemainingTeachers->execute();
    $remainingTeachersUpdated = $updateRemainingTeachers->rowCount();
    
    // Update students based on their teacher's course
    // This is a simplified approach - in a real system, you'd have a more direct relationship
    $updateStudents = $pdo->prepare("UPDATE users SET course = CASE WHEN id % 2 = 0 THEN 'BCA' ELSE 'BCom' END WHERE role = 'student' AND course IS NULL");
    $updateStudents->execute();
    $studentsUpdated = $updateStudents->rowCount();
    
    echo "<p>Updated $bcaTeachersUpdated BCA teachers, $bcomTeachersUpdated BCom teachers, $remainingTeachersUpdated other teachers, and $studentsUpdated students.</p>";
    
    // Count users by course and role
    $stmt = $pdo->prepare("SELECT course, role, COUNT(*) as count FROM users GROUP BY course, role");
    $stmt->execute();
    $userCounts = $stmt->fetchAll();
    
    echo "<h3>User Counts by Course and Role:</h3>";
    echo "<pre>";
    foreach ($userCounts as $count) {
        $course = $count['course'] ?: 'NULL';
        echo "$course - {$count['role']}: {$count['count']}\n";
    }
    echo "</pre>";
    
    echo "<p><a href='admin_dashboard.php'>Return to Admin Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>