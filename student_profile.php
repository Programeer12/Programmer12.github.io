<?php
require_once 'includes/auth.php';

// Require student role
$auth->requireRole('student');

// Get student data
$student = $auth->getCurrentUser();
$student_id = $student['id'];

// Initialize variables
$success_message = '';
$error_message = '';

// Get detailed student information
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ? AND role = 'student'");
    $stmt->execute([$student_id]);
    $student_profile = $stmt->fetch();
    
    if (!$student_profile) {
        header("Location: student_dashboard.php");
        exit();
    }
} catch (PDOException $e) {
    $error_message = "Error loading profile data.";
    $student_profile = $student;
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $username = trim($_POST['username'] ?? '');
    $current_password = $_POST['current_password'] ?? '';
    $new_password = $_POST['new_password'] ?? '';
    $confirm_password = $_POST['confirm_password'] ?? '';
    
    // Validation
    if (empty($name) || empty($email) || empty($username)) {
        $error_message = "Name, email, and username are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error_message = "Please enter a valid email address.";
    } else {
        try {
            // Check if email is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
            $stmt->execute([$email, $student_id]);
            if ($stmt->fetch()) {
                $error_message = "Email address is already taken by another user.";
            }
            
            // Check if username is already taken by another user
            $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ? AND id != ?");
            $stmt->execute([$username, $student_id]);
            if ($stmt->fetch()) {
                $error_message = "Username is already taken by another user.";
            }
            
            if (empty($error_message)) {
                // Handle password change if provided
                $update_password = false;
                if (!empty($new_password)) {
                    if (empty($current_password)) {
                        $error_message = "Current password is required to change password.";
                    } elseif (!password_verify($current_password, $student_profile['password'])) {
                        $error_message = "Current password is incorrect.";
                    } elseif (strlen($new_password) < 6) {
                        $error_message = "New password must be at least 6 characters long.";
                    } elseif ($new_password !== $confirm_password) {
                        $error_message = "New password and confirmation do not match.";
                    } else {
                        $update_password = true;
                    }
                }
                
                if (empty($error_message)) {
                    // Update profile information
                    if ($update_password) {
                        $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, username = ?, password = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$name, $email, $username, $hashed_password, $student_id]);
                    } else {
                        $stmt = $pdo->prepare("UPDATE users SET name = ?, email = ?, username = ?, updated_at = NOW() WHERE id = ?");
                        $stmt->execute([$name, $email, $username, $student_id]);
                    }
                    
                    // Update session data
                    $_SESSION['user_name'] = $name;
                    $_SESSION['user_email'] = $email;
                    $_SESSION['user_username'] = $username;
                    
                    // Refresh profile data
                    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
                    $stmt->execute([$student_id]);
                    $student_profile = $stmt->fetch();
                    
                    $success_message = "Profile updated successfully!" . ($update_password ? " Password has been changed." : "");
                }
            }
        } catch (PDOException $e) {
            $error_message = "Error updating profile. Please try again.";
        }
    }
}

// Get some statistics for the profile
try {
    // Get join date
    $join_date = new DateTime($student_profile['created_at']);
    
    // Count total submissions
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM submissions WHERE student_id = ?");
    $stmt->execute([$student_id]);
    $total_submissions = $stmt->fetchColumn();
    
    // Get average grade
    $stmt = $pdo->prepare("SELECT AVG(grade) FROM submissions WHERE student_id = ? AND status = 'graded'");
    $stmt->execute([$student_id]);
    $avg_grade = $stmt->fetchColumn() ?? 0;
    
    // Count total assignments
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM assignments WHERE status = 'active'");
    $stmt->execute();
    $total_assignments = $stmt->fetchColumn();
    
} catch (PDOException $e) {
    $total_submissions = 0;
    $avg_grade = 0;
    $total_assignments = 0;
    $join_date = new DateTime();
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
    <title>My Profile - EduAssign Student</title>
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
        h2 {
            color: white;
            margin-bottom: 2rem;
            font-size: 2rem;
            font-weight: 700;
        }
        .profile-container {
            display: grid;
            grid-template-columns: 1fr 2fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }
        .profile-sidebar {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
            height: fit-content;
        }
        .profile-avatar {
            text-align: center;
            margin-bottom: 2rem;
        }
        .avatar-circle {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 3rem;
            color: white;
        }
        .profile-name {
            font-size: 1.5rem;
            font-weight: 700;
            color: #333;
            margin-bottom: 0.5rem;
        }
        .profile-role {
            color: #666;
            font-size: 1rem;
        }
        .profile-stats {
            margin-top: 2rem;
        }
        .stat-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem 0;
            border-bottom: 1px solid #f0f0f0;
        }
        .stat-item:last-child {
            border-bottom: none;
        }
        .stat-label {
            color: #666;
            font-weight: 500;
        }
        .stat-value {
            color: #333;
            font-weight: 700;
        }
        .profile-form {
            background: white;
            border-radius: 15px;
            padding: 2rem;
            box-shadow: 0 8px 24px rgba(102,126,234,0.08);
        }
        .form-title {
            font-size: 1.5rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 2rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            margin-bottom: 2rem;
        }
        .form-group {
            margin-bottom: 1.5rem;
        }
        .form-group.full-width {
            grid-column: 1 / -1;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid #e9ecef;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s, box-shadow 0.3s;
        }
        .form-control:focus {
            border-color: #667eea;
            box-shadow: 0 0 0 3px rgba(102,126,234,0.1);
            outline: none;
        }
        .form-control:disabled {
            background-color: #f8f9fa;
            color: #6c757d;
        }
        .password-section {
            border-top: 2px solid #f0f0f0;
            padding-top: 2rem;
            margin-top: 2rem;
        }
        .password-title {
            font-size: 1.2rem;
            font-weight: 600;
            color: #333;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .btn {
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 8px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
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
            border: 2px solid #dee2e6;
            margin-right: 1rem;
        }
        .btn-secondary:hover {
            background: #e9ecef;
            border-color: #adb5bd;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-danger {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        .info-card {
            background: #e8f4fd;
            border: 1px solid #b8daff;
            border-radius: 8px;
            padding: 1rem;
            margin-bottom: 2rem;
        }
        .info-card h4 {
            margin: 0 0 0.5rem 0;
            color: #004085;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }
        .info-card p {
            margin: 0;
            color: #004085;
        }
        @media (max-width: 1024px) {
            .profile-container {
                grid-template-columns: 1fr;
            }
            .form-grid {
                grid-template-columns: 1fr;
            }
        }
        @media (max-width: 768px) {
            .main {
                padding: 1rem;
            }
            .profile-sidebar,
            .profile-form {
                padding: 1.5rem;
            }
            .avatar-circle {
                width: 100px;
                height: 100px;
                font-size: 2.5rem;
            }
            .profile-name {
                font-size: 1.3rem;
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
                <a href="student_assignments.php"><i class="fas fa-tasks"></i> Assignments</a>
                <a href="student_grades.php"><i class="fas fa-chart-line"></i> Grades</a>
                <a href="student_profile.php" class="active"><i class="fas fa-user"></i> Profile</a>
                <form method="post" style="display:inline;">
                    <button type="submit" name="logout" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </nav>
        </div>
    </header>
    <main class="main">
        <h2><i class="fas fa-user"></i> My Profile</h2>
        
        <div class="profile-container">
            <!-- Profile Sidebar -->
            <div class="profile-sidebar">
                <div class="profile-avatar">
                    <div class="avatar-circle">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="profile-name"><?php echo htmlspecialchars($student_profile['name']); ?></div>
                    <div class="profile-role">Student</div>
                </div>
                
                <div class="info-card">
                    <h4><i class="fas fa-info-circle"></i> Account Information</h4>
                    <p>Student ID: <?php echo htmlspecialchars($student_profile['id']); ?></p>
                    <p>Username: <?php echo htmlspecialchars($student_profile['username']); ?></p>
                    <p>Email: <?php echo htmlspecialchars($student_profile['email']); ?></p>
                    <p>Joined: <?php echo $join_date->format('M j, Y'); ?></p>
                    <p>Status: <?php echo ucfirst($student_profile['status']); ?></p>
                    <p>Last Updated: <?php echo (new DateTime($student_profile['updated_at']))->format('M j, Y g:i A'); ?></p>
                </div>
                
                <div class="profile-stats">
                    <div class="stat-item">
                        <span class="stat-label"><i class="fas fa-upload"></i> Total Submissions</span>
                        <span class="stat-value"><?php echo $total_submissions; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><i class="fas fa-star"></i> Average Grade</span>
                        <span class="stat-value"><?php echo number_format($avg_grade, 1); ?>%</span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><i class="fas fa-tasks"></i> Total Assignments</span>
                        <span class="stat-value"><?php echo $total_assignments; ?></span>
                    </div>
                    <div class="stat-item">
                        <span class="stat-label"><i class="fas fa-calendar"></i> Member Since</span>
                        <span class="stat-value"><?php echo $join_date->format('M Y'); ?></span>
                    </div>
                </div>
            </div>
            
            <!-- Profile Form -->
            <div class="profile-form">
                <?php if ($success_message): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success_message; ?>
                </div>
                <?php endif; ?>
                
                <?php if ($error_message): ?>
                <div class="alert alert-danger">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $error_message; ?>
                </div>
                <?php endif; ?>
                
                <form method="post">
                    <div class="form-title">
                        <i class="fas fa-edit"></i> Edit Profile Information
                    </div>
                    
                    <div class="form-grid">
                        <div class="form-group">
                            <label for="name"><i class="fas fa-user"></i> Full Name</label>
                            <input type="text" id="name" name="name" class="form-control" 
                                   value="<?php echo htmlspecialchars($student_profile['name']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="email"><i class="fas fa-envelope"></i> Email Address</label>
                            <input type="email" id="email" name="email" class="form-control" 
                                   value="<?php echo htmlspecialchars($student_profile['email']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="username"><i class="fas fa-at"></i> Username</label>
                            <input type="text" id="username" name="username" class="form-control" 
                                   value="<?php echo htmlspecialchars($student_profile['username']); ?>" required>
                        </div>
                        <div class="form-group">
                            <label for="status"><i class="fas fa-check-circle"></i> Account Status</label>
                            <input type="text" id="status" class="form-control" 
                                   value="<?php echo ucfirst($student_profile['status']); ?>" disabled>
                        </div>
                    </div>
                    
                    <!-- Password Change Section -->
                    <div class="password-section">
                        <div class="password-title">
                            <i class="fas fa-lock"></i> Change Password (Optional)
                        </div>
                        <div class="form-grid">
                            <div class="form-group">
                                <label for="current_password">Current Password</label>
                                <input type="password" id="current_password" name="current_password" class="form-control" 
                                       placeholder="Enter current password">
                            </div>
                            <div class="form-group">
                                <label for="new_password">New Password</label>
                                <input type="password" id="new_password" name="new_password" class="form-control" 
                                       placeholder="Enter new password (min. 6 characters)">
                            </div>
                            <div class="form-group full-width">
                                <label for="confirm_password">Confirm New Password</label>
                                <input type="password" id="confirm_password" name="confirm_password" class="form-control" 
                                       placeholder="Confirm new password">
                            </div>
                        </div>
                    </div>
                    
                    <div style="margin-top: 2rem; text-align: right;">
                        <a href="student_dashboard.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> Cancel
                        </a>
                        <button type="submit" name="update_profile" class="btn btn-primary">
                            <i class="fas fa-save"></i> Save Changes
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <footer>
        <p>&copy; 2025 EduAssign. All rights reserved.</p>
    </footer>

    <script>
        // Form validation
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const newPasswordField = document.getElementById('new_password');
            const confirmPasswordField = document.getElementById('confirm_password');
            const currentPasswordField = document.getElementById('current_password');
            
            // Password validation
            function validatePasswords() {
                const newPassword = newPasswordField.value;
                const confirmPassword = confirmPasswordField.value;
                
                if (newPassword && newPassword !== confirmPassword) {
                    confirmPasswordField.setCustomValidity('Passwords do not match');
                } else {
                    confirmPasswordField.setCustomValidity('');
                }
                
                // Require current password if new password is provided
                if (newPassword && !currentPasswordField.value) {
                    currentPasswordField.setCustomValidity('Current password is required to change password');
                } else {
                    currentPasswordField.setCustomValidity('');
                }
            }
            
            newPasswordField.addEventListener('input', validatePasswords);
            confirmPasswordField.addEventListener('input', validatePasswords);
            currentPasswordField.addEventListener('input', validatePasswords);
            
            // Form submission
            form.addEventListener('submit', function(e) {
                validatePasswords();
                
                if (!form.checkValidity()) {
                    e.preventDefault();
                    e.stopPropagation();
                }
            });
        });
    </script>
</body>
</html>
