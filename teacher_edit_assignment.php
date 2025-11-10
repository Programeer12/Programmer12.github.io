<?php
require_once 'includes/auth.php';
require_once 'includes/assignment_helper.php';
require_once 'includes/notification_system.php';

// Require teacher role
$auth->requireRole('teacher');

// Initialize notification system
$notificationSystem = new NotificationSystem($pdo);

// Get teacher data
$teacher = $auth->getCurrentUser();
$teacher_id = $teacher['id'];
$teacher_course = $teacher['course'];

$success_message = '';
$error_message = '';

// Get assignment ID from URL
$assignment_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if (!$assignment_id) {
    header("Location: teacher_assignments.php");
    exit();
}

// Get assignment data
try {
    $stmt = $pdo->prepare("
        SELECT * FROM assignments 
        WHERE id = ? AND teacher_id = ?
    ");
    $stmt->execute([$assignment_id, $teacher_id]);
    $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$assignment) {
        header("Location: teacher_assignments.php");
        exit();
    }
} catch (PDOException $e) {
    header("Location: teacher_assignments.php");
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_assignment'])) {
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $subject = trim($_POST['subject']);
    $due_date = $_POST['due_date'];
    $max_score = intval($_POST['max_score']);
    $assignment_period_start = $_POST['assignment_period_start'];
    $assignment_period_end = $_POST['assignment_period_end'];
    $status = $_POST['status'];
    
    // Validation
    if (empty($title) || empty($description) || empty($subject) || empty($due_date) || 
        empty($assignment_period_start) || empty($assignment_period_end)) {
        $error_message = "All fields are required.";
    } elseif ($max_score <= 0) {
        $error_message = "Max score must be greater than 0.";
    } elseif (strtotime($assignment_period_start) >= strtotime($assignment_period_end)) {
        $error_message = "Assignment period end must be after start date.";
    } elseif (strtotime($due_date) <= strtotime($assignment_period_end)) {
        $error_message = "Due date must be after assignment period end.";
    } else {
        try {
            // Update assignment
            $stmt = $pdo->prepare("
                UPDATE assignments 
                SET title = ?, description = ?, subject = ?, due_date = ?, max_score = ?, 
                    assignment_period_start = ?, assignment_period_end = ?, status = ?
                WHERE id = ? AND teacher_id = ?
            ");
            $stmt->execute([$title, $description, $subject, $due_date, $max_score, 
                          $assignment_period_start, $assignment_period_end, $status, 
                          $assignment_id, $teacher_id]);
            
            // Log activity
            $auth->logActivity($teacher_id, 'edit_assignment', "Updated assignment: {$title}");
            
            // Send notification to teacher about successful update
            $notificationSystem->createNotification(
                $teacher_id,
                "Assignment Updated Successfully",
                "You have successfully updated the assignment '{$title}'. All changes have been saved and students have been notified if the assignment is active.",
                'assignment',
                $assignment_id,
                'assignment',
                'medium'
            );
            
            // Send notification to students about assignment update if assignment is active
            if ($status === 'active') {
                $notification_title = "Assignment Updated";
                $notification_message = "The assignment '{$title}' has been updated by {$teacher['name']}. Please review the changes.";
                
                // Get all students
                $stmt_students = $pdo->prepare("SELECT id FROM users WHERE role = 'student' AND status = 'active'");
                $stmt_students->execute();
                $students = $stmt_students->fetchAll(PDO::FETCH_COLUMN);
                
                foreach ($students as $student_id) {
                    $notificationSystem->createNotification(
                        $student_id, 
                        $notification_title, 
                        $notification_message, 
                        'assignment', 
                        $assignment_id, 
                        'assignment', 
                        'medium'
                    );
                }
            }
            
            $success_message = "Assignment updated successfully! You and your students have been notified.";
            
            // Refresh assignment data
            $stmt = $pdo->prepare("SELECT * FROM assignments WHERE id = ? AND teacher_id = ?");
            $stmt->execute([$assignment_id, $teacher_id]);
            $assignment = $stmt->fetch(PDO::FETCH_ASSOC);
            
        } catch (PDOException $e) {
            $error_message = "Error updating assignment: " . $e->getMessage();
        }
    }
}

// Handle logout
if (isset($_POST['logout'])) {
    $auth->logout();
    header("Location: teacher_login.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Edit Assignment - EduAssign Teacher</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="styles.css" rel="stylesheet">
    <script src="js/notifications.js" defer></script>
    <style>
        body {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            margin: 0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        .main {
            padding: 6rem 1rem 2rem;
            min-height: 100vh;
        }
        
        .form-container {
            max-width: 800px;
            margin: 2rem auto;
            background: rgba(255, 255, 255, 0.25);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 20px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            padding: 2.5rem;
            position: relative;
            overflow: hidden;
        }
        
        .form-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 2px;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.8), transparent);
            animation: shimmer 3s infinite;
        }
        
        @keyframes shimmer {
            0% { transform: translateX(-100%); }
            100% { transform: translateX(100%); }
        }
        
        .form-container h2 {
            color: #2c3e50;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.8rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }
        
        .alert {
            padding: 1rem 1.5rem;
            border-radius: 15px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .alert-success {
            background: rgba(212, 237, 218, 0.9);
            color: #155724;
            box-shadow: 0 8px 20px rgba(76, 175, 80, 0.2);
        }
        
        .alert-danger {
            background: rgba(248, 215, 218, 0.9);
            color: #721c24;
            box-shadow: 0 8px 20px rgba(244, 67, 54, 0.2);
        }
        
        .info-box {
            background: rgba(227, 242, 253, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            border: 1px solid rgba(33, 150, 243, 0.3);
            border-radius: 15px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: 0 8px 20px rgba(33, 150, 243, 0.1);
        }
        
        .info-box h4 {
            margin: 0 0 0.5rem 0;
            color: #1976d2;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 1.1rem;
        }
        
        .info-box p {
            margin: 0;
            color: #1565c0;
            opacity: 0.9;
        }
        
        .form-group {
            margin-bottom: 1.5rem;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #2c3e50;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }
        
        .form-group input,
        .form-group textarea,
        .form-group select {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            font-size: 1rem;
            transition: all 0.3s ease;
            box-sizing: border-box;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            box-shadow: inset 0 2px 4px rgba(0, 0, 0, 0.06);
        }
        
        .form-group input:focus,
        .form-group textarea:focus,
        .form-group select:focus {
            outline: none;
            border-color: rgba(102, 126, 234, 0.6);
            background: rgba(255, 255, 255, 0.95);
            box-shadow: 0 0 0 3px rgba(102, 126, 234, 0.1), inset 0 2px 4px rgba(0, 0, 0, 0.06);
            transform: translateY(-1px);
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }
        
        .form-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 2.5rem;
            padding-top: 2rem;
            border-top: 1px solid rgba(255, 255, 255, 0.2);
        }
        
        .btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 25px;
            cursor: pointer;
            font-weight: 600;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.3s ease;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 0.9rem;
            position: relative;
            overflow: hidden;
        }
        
        .btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(90deg, transparent, rgba(255, 255, 255, 0.2), transparent);
            transition: left 0.5s;
        }
        
        .btn:hover::before {
            left: 100%;
        }
        
        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 8px 25px rgba(102, 126, 234, 0.3);
        }
        
        .btn-primary:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 35px rgba(102, 126, 234, 0.4);
        }
        
        .btn-secondary {
            background: rgba(255, 255, 255, 0.9);
            color: #6c757d;
            border: 2px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
        }
        
        .btn-secondary:hover {
            background: rgba(255, 255, 255, 1);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.1);
        }
        
        /* Floating elements effect */
        .form-container::after {
            content: '';
            position: absolute;
            top: 20%;
            right: -50px;
            width: 100px;
            height: 100px;
            background: radial-gradient(circle, rgba(255, 255, 255, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }
        
        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }
        
        /* Additional floating element */
        .form-group:nth-child(3)::before {
            content: '';
            position: absolute;
            left: -30px;
            top: 50%;
            width: 60px;
            height: 60px;
            background: radial-gradient(circle, rgba(102, 126, 234, 0.1) 0%, transparent 70%);
            border-radius: 50%;
            animation: float 8s ease-in-out infinite reverse;
            z-index: -1;
        }
        
        @media (max-width: 768px) {
            .form-container {
                margin: 1rem;
                padding: 2rem 1.5rem;
            }
            
            .form-row {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
            
            .form-actions {
                flex-direction: column;
            }
            
            .btn {
                justify-content: center;
            }
        }
        
        /* Enhanced focus effects */
        .form-group:focus-within label {
            color: #667eea;
            transform: translateY(-1px);
        }
        
        /* Smooth transitions for all interactive elements */
        * {
            transition: all 0.3s cubic-bezier(0.25, 0.46, 0.45, 0.94);
        }
    </style>
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
                <a href="teacher_assignments.php" class="active"><i class="fas fa-tasks"></i> My Assignments</a>
                <a href="teacher_submissions.php"><i class="fas fa-file-alt"></i> Submissions</a>
                <a href="teacher_approve_students.php"><i class="fas fa-user-check"></i> Approve Students</a>
                <a href="teacher_students.php"><i class="fas fa-users"></i> My Students</a>
                <a href="notification_center.php"><i class="fas fa-bell"></i> Notifications</a>
                <a href="teacher_profile.php"><i class="fas fa-user"></i> Your Profile</a>
            </nav>
        </div>
    </header>
    <main class="main">
        <div class="form-container">
            <h2><i class="fas fa-edit"></i> Edit Assignment</h2>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>
            
            <div class="info-box">
                <h4><i class="fas fa-info-circle"></i> Assignment Information</h4>
                <p>You can modify the assignment details below. Changes will be reflected immediately for students.</p>
            </div>
            
            <form method="post">
                <div class="form-group">
                    <label for="title">Assignment Title *</label>
                    <input type="text" id="title" name="title" value="<?php echo htmlspecialchars($assignment['title']); ?>" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" required><?php echo htmlspecialchars($assignment['description']); ?></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="subject">Subject *</label>
                        <input type="text" id="subject" name="subject" value="<?php echo htmlspecialchars($assignment['subject']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_score">Max Score *</label>
                        <input type="number" id="max_score" name="max_score" value="<?php echo $assignment['max_score']; ?>" min="1" max="1000" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="assignment_period_start">Assignment Period Start *</label>
                        <input type="datetime-local" id="assignment_period_start" name="assignment_period_start" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($assignment['assignment_period_start'])); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="assignment_period_end">Assignment Period End *</label>
                        <input type="datetime-local" id="assignment_period_end" name="assignment_period_end" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($assignment['assignment_period_end'])); ?>" required>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="due_date">Due Date *</label>
                        <input type="datetime-local" id="due_date" name="due_date" 
                               value="<?php echo date('Y-m-d\TH:i', strtotime($assignment['due_date'])); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="status">Status *</label>
                        <select id="status" name="status" required>
                            <option value="active" <?php echo $assignment['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $assignment['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                        </select>
                    </div>
                </div>
                
                <div class="form-actions">
                    <a href="teacher_assignments.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left"></i> Back to Assignments
                    </a>
                    <button type="submit" name="update_assignment" class="btn btn-primary">
                        <i class="fas fa-save"></i> Update Assignment
                    </button>
                </div>
            </form>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 EduAssign. All rights reserved.</p>
    </footer>
</body>
</html>