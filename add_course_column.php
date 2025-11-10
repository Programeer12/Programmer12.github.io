<?php
require_once 'db.php';

try {
    // Add course column to assignments table if it doesn't exist
    $checkColumnSql = "SHOW COLUMNS FROM assignments LIKE 'course'";
    $stmt = $pdo->prepare($checkColumnSql);
    $stmt->execute();
    
    if ($stmt->rowCount() == 0) {
        // Column doesn't exist, add it
        $alterSql = "ALTER TABLE assignments ADD COLUMN course varchar(50) DEFAULT NULL AFTER subject";
        $pdo->exec($alterSql);
        echo "<p>Success: 'course' column added to assignments table.</p>";
    } else {
        echo "<p>The 'course' column already exists in assignments table.</p>";
    }
    
    // Update existing assignments with course information from teachers
    $updateSql = "UPDATE assignments a 
                 INNER JOIN users u ON a.teacher_id = u.id 
                 SET a.course = u.course 
                 WHERE a.course IS NULL";
    $updateStmt = $pdo->prepare($updateSql);
    $updateStmt->execute();
    
    $rowCount = $updateStmt->rowCount();
    echo "<p>Updated course information for {$rowCount} assignments.</p>";
    
    echo "<p><a href='admin_dashboard.php'>Return to Admin Dashboard</a></p>";
    
} catch (PDOException $e) {
    echo "<p>Error: " . $e->getMessage() . "</p>";
}
?>