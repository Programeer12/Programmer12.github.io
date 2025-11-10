<?php
require_once 'includes/auth.php';
require_once 'includes/assignment_helper.php';
require_once 'includes/notification_system.php';

// Require student role
$auth->requireRole('student');

// Initialize notification system
$notificationSystem = new NotificationSystem($pdo);

// Get student data
$student = $auth->getCurrentUser();
$student_id = $student['id'];

// Check if assignment ID is provided
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: student_assignments.php");
    exit();
}

$assignment_id = $_GET['id'];

// Get assignment details
$stmt = $pdo->prepare("SELECT a.*, u.name as teacher_name FROM assignments a 
                      LEFT JOIN users u ON a.teacher_id = u.id 
                      WHERE a.id = ? AND a.status = 'active'");
$stmt->execute([$assignment_id]);
$assignment = $stmt->fetch();

// Check if assignment exists and is active
if (!$assignment) {
    header("Location: student_assignments.php");
    exit();
}

// Check if assignment is currently active (within period)
if (!$assignmentHelper->isAssignmentActive($assignment_id)) {
    $_SESSION['error'] = "This assignment is not currently active.";
    header("Location: student_assignments.php");
    exit();
}

// Check if student has already submitted this assignment
$stmt = $pdo->prepare("SELECT * FROM submissions WHERE assignment_id = ? AND student_id = ?");
$stmt->execute([$assignment_id, $student_id]);
$existing_submission = $stmt->fetch();

// Process submission
$error = "";
$success = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    // Check if file was uploaded
    if (isset($_FILES['assignment_file']) && $_FILES['assignment_file']['error'] === UPLOAD_ERR_OK) {
        // Get system settings for file upload
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'max_file_size'");
        $stmt->execute();
        $max_file_size = (int)$stmt->fetchColumn();
        
        $stmt = $pdo->prepare("SELECT setting_value FROM system_settings WHERE setting_key = 'allowed_file_types'");
        $stmt->execute();
        $allowed_file_types = explode(',', $stmt->fetchColumn());
        
        // Validate file size
        if ($_FILES['assignment_file']['size'] > $max_file_size) {
            $error = "File size exceeds the maximum allowed size (" . ($max_file_size / 1048576) . " MB).";
        } else {
            // Validate file type
            $file_extension = strtolower(pathinfo($_FILES['assignment_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($file_extension, $allowed_file_types)) {
                $error = "Invalid file type. Allowed types: " . implode(', ', $allowed_file_types);
            } else {
                // Create uploads directory if it doesn't exist
                $upload_dir = 'uploads/assignments/' . $assignment_id . '/';
                if (!file_exists($upload_dir)) {
                    mkdir($upload_dir, 0777, true);
                }
                
                // Generate unique filename
                $filename = $student_id . '_' . time() . '_' . $_FILES['assignment_file']['name'];
                $file_path = $upload_dir . $filename;
                
                // Move uploaded file
                if (move_uploaded_file($_FILES['assignment_file']['tmp_name'], $file_path)) {
                    $submission_id = null;
                    
                    // Check if this is a resubmission
                    if ($existing_submission) {
                        // Update existing submission
                        $stmt = $pdo->prepare("UPDATE submissions SET file_path = ?, submitted_at = NOW(), status = 'submitted', grade = NULL, feedback = NULL WHERE id = ?");
                        $stmt->execute([$file_path, $existing_submission['id']]);
                        $submission_id = $existing_submission['id'];
                        $success = "Your assignment has been resubmitted successfully.";
                    } else {
                        // Insert new submission
                        $stmt = $pdo->prepare("INSERT INTO submissions (assignment_id, student_id, file_path, submitted_at, status) VALUES (?, ?, ?, NOW(), 'submitted')");
                        $stmt->execute([$assignment_id, $student_id, $file_path]);
                        $submission_id = $pdo->lastInsertId();
                        $success = "Your assignment has been submitted successfully.";
                    }
                    
                    // Notify teacher about the new/updated submission
                    $action_text = $existing_submission ? "resubmitted" : "submitted";
                    $notificationSystem->createNotification(
                        $assignment['teacher_id'],
                        "New Assignment Submission",
                        "{$student['name']} has {$action_text} their work for assignment '{$assignment['title']}'. You can now review and grade the submission.",
                        'submission',
                        $submission_id,
                        'submission',
                        'high'
                    );
                    
                    // Also notify the student about successful submission
                    $notificationSystem->createNotification(
                        $student_id,
                        "Assignment Submitted Successfully",
                        "Your assignment '{$assignment['title']}' has been {$action_text} successfully. Your teacher will review it soon.",
                        'submission',
                        $submission_id,
                        'submission',
                        'medium'
                    );
                    
                    // Log activity
                    $action = $existing_submission ? "resubmitted" : "submitted";
                    $description = "Student {$student['name']} {$action} assignment '{$assignment['title']}'";
                    $stmt = $pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$student_id, "assignment_{$action}", $description, $_SERVER['REMOTE_ADDR']]);
                    
                    // Redirect to prevent resubmission on refresh
                    header("Location: student_assignments.php?success=" . urlencode($success));
                    exit();
                } else {
                    $error = "Failed to upload file. Please try again.";
                }
            }
        }
    } else {
        $error = "Please select a file to upload.";
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    $auth->logout();
    header("Location: student_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Submit Assignment - EduAssign Student</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            color: #333;
        }
        header {
            background: rgba(255,255,255,0.97);
            box-shadow: 0 2px 25px rgba(0,0,0,0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
        }
        .header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 2rem;
        }
        .logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
        }
        .logo i {
            font-size: 2rem;
            color: #667eea;
        }
        .logo h1 {
            font-size: 1.4rem;
            color: #333;
            font-weight: 700;
        }
        nav {
            display: flex;
            gap: 2rem;
        }
        nav a {
            text-decoration: none;
            color: #333;
            font-weight: 500;
            padding: 0.6rem 1.2rem;
            border-radius: 30px;
            transition: all 0.3s;
        }
        nav a.active, nav a:hover {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .logout-btn {
            background: #e74c3c;
            color: white;
            border: none;
            border-radius: 20px;
            padding: 0.7rem 1.5rem;
            font-weight: 600;
            cursor: pointer;
            margin-left: 1rem;
            transition: background 0.2s;
        }
        .logout-btn:hover {
            background: #c0392b;
        }
        .main {
            margin-top: 90px;
            padding: 2rem;
            max-width: 1200px;
            margin-left: auto;
            margin-right: auto;
        }
        .card {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
            margin-bottom: 2rem;
        }
        h2 {
            color: #333;
            margin-top: 0;
        }
        .assignment-details {
            margin-bottom: 2rem;
        }
        .detail-row {
            display: flex;
            margin-bottom: 0.5rem;
        }
        .detail-label {
            width: 150px;
            color: #666;
            font-weight: 500;
        }
        .detail-value {
            color: #333;
            font-weight: 600;
        }
        .submission-form {
            background: #f8f9fa;
            padding: 1.5rem;
            border-radius: 10px;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 500;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 1px solid #ddd;
            border-radius: 8px;
            font-size: 1rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
        }
        .btn-primary {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }
        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 4px 12px rgba(102,126,234,0.3);
        }
        .btn-secondary {
            background: #f8f9fa;
            color: #333;
            border: 1px solid #dee2e6;
            margin-right: 1rem;
        }
        .btn-secondary:hover {
            background: #e9ecef;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .existing-submission {
            background: #e8f4fd;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .existing-submission h3 {
            margin-top: 0;
            color: #004085;
        }
        @media (max-width: 768px) {
            .header-container {
                flex-direction: column;
                padding: 1rem;
            }
            nav {
                margin-top: 1rem;
                flex-wrap: wrap;
                justify-content: center;
            }
            .main {
                padding: 1rem;
            }
            .detail-row {
                flex-direction: column;
            }
            .detail-label {
                width: 100%;
                margin-bottom: 0.25rem;
            }
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="student_dashboard.php" class="logo">
                <i class="fas fa-user-graduate"></i>
                <h1>EduAssign Student</h1>
            </a>
            <nav>
                <a href="student_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="student_assignments.php" class="active"><i class="fas fa-tasks"></i> Assignments</a>
                <a href="student_grades.php"><i class="fas fa-chart-line"></i> Grades</a>
                <a href="student_profile.php"><i class="fas fa-user"></i> Profile</a>
                <form method="post" style="display:inline;">
                    <button type="submit" name="logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="main">
        <div class="card">
            <h2><i class="fas fa-upload"></i> Submit Assignment</h2>
            
            <?php if ($error): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo $error; ?>
                </div>
            <?php endif; ?>
            
            <?php if ($success): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <div class="assignment-details">
                <div class="detail-row">
                    <span class="detail-label">Assignment:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($assignment['title']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Subject:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($assignment['subject']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Teacher:</span>
                    <span class="detail-value"><?php echo htmlspecialchars($assignment['teacher_name']); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Due Date:</span>
                    <span class="detail-value"><?php echo date('M j, Y g:i A', strtotime($assignment['due_date'])); ?></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Max Score:</span>
                    <span class="detail-value"><?php echo $assignment['max_score']; ?> points</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label">Description:</span>
                    <span class="detail-value"><?php echo nl2br(htmlspecialchars($assignment['description'])); ?></span>
                </div>
            </div>
            
            <?php if ($existing_submission): ?>
                <div class="existing-submission">
                    <h3><i class="fas fa-info-circle"></i> You have already submitted this assignment</h3>
                    <p>Submitted on: <?php echo date('M j, Y g:i A', strtotime($existing_submission['submitted_at'])); ?></p>
                    <p>Status: <?php echo ucfirst($existing_submission['status']); ?></p>
                    <?php if ($existing_submission['status'] === 'graded'): ?>
                        <p>Grade: <?php echo $existing_submission['grade']; ?> / <?php echo $assignment['max_score']; ?></p>
                        <p>Feedback: <?php echo nl2br(htmlspecialchars($existing_submission['feedback'] ?? 'No feedback provided')); ?></p>
                    <?php endif; ?>
                    <p>You can submit again to replace your previous submission.</p>
                </div>
            <?php endif; ?>
            
            <div class="submission-form">
                <h3>Upload Your Assignment</h3>
                <form method="post" enctype="multipart/form-data">
                    <div class="form-group">
                        <label for="assignment_file">Select File:</label>
                        <input type="file" name="assignment_file" id="assignment_file" class="form-control" required>
                        <small>Allowed file types: pdf, doc, docx, txt, jpg, jpeg, png</small>
                    </div>
                    <div class="form-group">
                        <a href="student_assignments.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Back to Assignments</a>
                        <button type="submit" name="submit" class="btn btn-primary"><i class="fas fa-upload"></i> Submit Assignment</button>
                    </div>
                </form>
            </div>
        </div>
    </main>
</body>
</html>