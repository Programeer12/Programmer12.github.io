<?php
require_once 'includes/auth.php';

// Require teacher role
$auth->requireRole('teacher');

// Get teacher data
$teacher = $auth->getCurrentUser();
$teacher_name = $teacher['name'] ?? "Teacher";
$teacher_subject = $teacher['subject'] ?? "Subject";

// Fetch teacher's status and additional details
try {
    $stmt = $pdo->prepare("SELECT status, last_updated FROM teachers WHERE id = ?");
    $stmt->execute([$_SESSION['user_id']]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    $status = $result['status'] ?? "Unknown";
    $last_updated = $result['last_updated'] ?? "Not Available";
} catch (PDOException $e) {
    $status = "Unknown";
    $last_updated = "Not Available";
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Check Status - EduAssign</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            color: #333;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .status-container {
            background: white;
            padding: 2rem 3rem;
            border-radius: 12px;
            box-shadow: 0 8px 24px rgba(102,126,234,0.2);
            text-align: center;
        }
        .status-container h1 {
            font-size: 2rem;
            color: #764ba2;
            margin-bottom: 1rem;
        }
        .status-container p {
            font-size: 1.2rem;
            color: #333;
        }
        .status-container .last-updated {
            margin-top: 1rem;
            font-size: 0.9rem;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="status-container">
        <h1>Welcome, <?php echo htmlspecialchars($teacher_name); ?>!</h1>
        <p>Your current status: <strong><?php echo htmlspecialchars($status); ?></strong></p>
        <p class="last-updated">Last updated: <?php echo htmlspecialchars($last_updated); ?></p>
    </div>

    <footer>
        <p>&copy; 2025 EduAssign. All rights reserved.</p>
    </footer>
</body>
</html>
