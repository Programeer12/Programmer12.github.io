<?php
// Shared header for all pages
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <script src="js/notifications.js" defer></script>
    <title>EduAssign</title>
</head>
<body>
<header>
    <div class="header-container">
        <a href="teacher_dashboard.php" class="logo">
            <i class="fas fa-chalkboard-teacher"></i>
            <h1>EduAssign Teacher</h1>
        </a>
        <nav>
            <a href="teacher_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
            <a href="teacher_assignments.php"><i class="fas fa-tasks"></i> My Assignments</a>
            <a href="teacher_submissions.php"><i class="fas fa-file-alt"></i> Submissions</a>
            <a href="teacher_approve_students.php"><i class="fas fa-user-check"></i> Approve Students</a>
            <a href="teacher_students.php"><i class="fas fa-users"></i> My Students</a>
            <a href="teacher_profile.php"><i class="fas fa-user"></i> Your Profile</a>
        </nav>
    </div>
</header>
