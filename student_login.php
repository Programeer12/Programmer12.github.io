<?php
require_once 'includes/auth.php';

// Check if student is already logged in
if ($auth->isLoggedIn() && $auth->hasRole('student')) {
    header("Location: student_dashboard.php");
    exit();
}

// Handle login form submission
$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['login'])) {
        $username = trim($_POST['email']); // Using email field as username
        $password = $_POST['password'];
        
        // Basic validation
        if (empty($username) || empty($password)) {
            $error_message = "Please fill in all fields.";
        } else {
            // Use the auth system to login
            $result = $auth->login($username, $password);
            if ($result['success'] && $result['user']['role'] === 'student') {
                header("Location: student_dashboard.php");
                exit();
            } else {
                $error_message = "Invalid credentials or insufficient privileges.";
            }
        }
    }
    
    if (isset($_POST['register'])) {
        // Student registration submitted from the login page
        $name = trim($_POST['reg_name'] ?? '');
        $username = trim($_POST['reg_username'] ?? '');
        $email = trim($_POST['reg_email'] ?? '');
        $student_id = trim($_POST['student_id'] ?? '');
        $course = trim($_POST['reg_course'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        // Validation
        if (!$name || !$username || !$email || !$password || !$confirm_password || !$course) {
            $error_message = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = 'Please enter a valid email address.';
        } elseif ($password !== $confirm_password) {
            $error_message = 'Passwords do not match.';
        } elseif (strlen($password) < 6) {
            $error_message = 'Password must be at least 6 characters long.';
        } else {
            try {
                // Check for existing username/email in pending_registrations and users
                $stmt = $pdo->prepare('SELECT COUNT(*) FROM pending_registrations WHERE username = ? OR email = ?');
                $stmt->execute([$username, $email]);
                if ($stmt->fetchColumn() > 0) {
                    $error_message = 'Username or email already pending approval.';
                } else {
                    $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE username = ? OR email = ?');
                    $stmt->execute([$username, $email]);
                    if ($stmt->fetchColumn() > 0) {
                        $error_message = 'Username or email already registered.';
                    } else {
                        $hashed_password = password_hash($password, PASSWORD_DEFAULT);
                        $stmt = $pdo->prepare('INSERT INTO pending_registrations (name, username, email, password, role, subject, course) VALUES (?, ?, ?, ?, ?, ?, ?)');
                        $stmt->execute([$name, $username, $email, $hashed_password, 'student', '', $course]);
                        $success_message = 'Registration submitted! Awaiting admin approval.';

                        // Notify all active admins about the new pending registration
                        try {
                            require_once 'includes/notification_system.php';
                            $notificationSystem = new NotificationSystem($pdo);
                            $stmtAdmins = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
                            $stmtAdmins->execute();
                            $adminIds = $stmtAdmins->fetchAll(PDO::FETCH_COLUMN);
                            $notifTitle = "New Student Registration";
                            $notifMsg = "$name ({$username}) has registered and is awaiting approval for course: $course.";
                            foreach ($adminIds as $adminId) {
                                $notificationSystem->createNotification($adminId, $notifTitle, $notifMsg, 'general', null, 'registration', 'high');
                            }
                        } catch (Exception $e) {
                            error_log('Failed to create admin notification for registration: ' . $e->getMessage());
                        }

                        // Clear POST to avoid showing form values
                        $_POST = [];
                    }
                }
            } catch (PDOException $e) {
                $error_message = 'Database error: ' . $e->getMessage();
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Student Login - EduAssign</title>
    <meta name="description" content="Student login portal for EduAssign - Access your assignments, track progress, and collaborate with peers.">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            line-height: 1.6;
            color: #333;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            position: relative;
            overflow-x: hidden;
        }

        /* Animated Background Elements */
        .background-animation {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            z-index: -1;
            overflow: hidden;
        }

        .floating-element {
            position: absolute;
            color: rgba(255, 255, 255, 0.06);
            animation: float 10s ease-in-out infinite;
            pointer-events: none;
        }

        .floating-element:nth-child(1) {
            top: 8%;
            left: 5%;
            font-size: 2.8rem;
            animation-delay: 0s;
        }

        .floating-element:nth-child(2) {
            top: 20%;
            right: 10%;
            font-size: 2.4rem;
            animation-delay: 1.5s;
        }

        .floating-element:nth-child(3) {
            top: 60%;
            left: 8%;
            font-size: 2.2rem;
            animation-delay: 3s;
        }

        .floating-element:nth-child(4) {
            top: 80%;
            right: 15%;
            font-size: 3.2rem;
            animation-delay: 4.5s;
        }

        .floating-element:nth-child(5) {
            top: 40%;
            left: 90%;
            font-size: 2.6rem;
            animation-delay: 6s;
        }

        .floating-element:nth-child(6) {
            top: 15%;
            left: 50%;
            font-size: 2.3rem;
            animation-delay: 7.5s;
        }

        .floating-element:nth-child(7) {
            top: 70%;
            left: 30%;
            font-size: 2.7rem;
            animation-delay: 2s;
        }

        .floating-element:nth-child(8) {
            top: 35%;
            right: 25%;
            font-size: 2.9rem;
            animation-delay: 5s;
        }

        /* Geometric shapes */
        .geometric-shapes {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .shape {
            position: absolute;
            opacity: 0.03;
            animation: rotate 30s linear infinite;
        }

        .shape:nth-child(1) {
            top: 25%;
            left: 15%;
            width: 80px;
            height: 80px;
            background: white;
            border-radius: 50%;
        }

        .shape:nth-child(2) {
            top: 70%;
            right: 20%;
            width: 60px;
            height: 60px;
            background: white;
            transform: rotate(45deg);
            animation-delay: 10s;
        }

        .shape:nth-child(3) {
            top: 45%;
            left: 70%;
            width: 0;
            height: 0;
            border-left: 30px solid transparent;
            border-right: 30px solid transparent;
            border-bottom: 50px solid white;
            animation-delay: 20s;
        }

        /* Floating particles */
        .particles {
            position: absolute;
            width: 100%;
            height: 100%;
        }

        .particle {
            position: absolute;
            width: 3px;
            height: 3px;
            background: rgba(255, 255, 255, 0.2);
            border-radius: 50%;
            animation: particleFloat 12s linear infinite;
        }

        .particle:nth-child(1) { left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { left: 25%; animation-delay: 2s; }
        .particle:nth-child(3) { left: 40%; animation-delay: 4s; }
        .particle:nth-child(4) { left: 55%; animation-delay: 6s; }
        .particle:nth-child(5) { left: 70%; animation-delay: 8s; }
        .particle:nth-child(6) { left: 85%; animation-delay: 10s; }

        /* Animation Keyframes */
        @keyframes float {
            0%, 100% {
                transform: translateY(0px) rotate(0deg);
                opacity: 0.06;
            }
            50% {
                transform: translateY(-30px) rotate(180deg);
                opacity: 0.15;
            }
        }

        @keyframes rotate {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @keyframes particleFloat {
            0% {
                transform: translateY(100vh) scale(0);
                opacity: 0;
            }
            10% {
                opacity: 1;
                transform: translateY(90vh) scale(1);
            }
            90% {
                opacity: 1;
                transform: translateY(10vh) scale(1);
            }
            100% {
                transform: translateY(-10vh) scale(0);
                opacity: 0;
            }
        }

        /* Header Styles */
        header {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(15px);
            box-shadow: 0 2px 25px rgba(0, 0, 0, 0.1);
            position: fixed;
            width: 100%;
            top: 0;
            z-index: 1000;
            transition: all 0.3s ease;
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
            font-size: 2.2rem;
            color: #667eea;
            animation: pulse 2s infinite;
        }

        .logo h1 {
            font-size: 1.6rem;
            color: #333;
            font-weight: 700;
            letter-spacing: -0.5px;
        }

        @keyframes pulse {
            0%, 100% { transform: scale(1); }
            50% { transform: scale(1.05); }
        }

        /* Navigation Styles */
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
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        nav a::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #667eea, #764ba2);
            transition: left 0.3s ease;
            z-index: -1;
        }

        nav a:hover::before {
            left: 0;
        }

        nav a:hover {
            color: white;
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(102, 126, 234, 0.4);
        }

        nav a.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
        }

        /* Mobile Menu Toggle */
        .menu-toggle {
            display: none;
            flex-direction: column;
            cursor: pointer;
            z-index: 1001;
        }

        .menu-toggle span {
            width: 28px;
            height: 3px;
            background: #333;
            margin: 4px 0;
            transition: 0.3s;
            border-radius: 2px;
        }

        /* Main Content */
        .main-content {
            margin-top: 80px;
            padding: 3rem 2rem;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: calc(100vh - 80px);
            position: relative;
            z-index: 1;
        }

        .login-container {
            background: rgba(255, 255, 255, 0.95);
            backdrop-filter: blur(20px);
            border-radius: 25px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.1);
            padding: 3rem;
            width: 100%;
            max-width: 450px;
            position: relative;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .login-container::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            height: 4px;
            background: linear-gradient(135deg, #667eea, #764ba2);
        }

        .login-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .login-header i {
            font-size: 3rem;
            color: #667eea;
            margin-bottom: 1rem;
            animation: bounce 2s infinite;
        }

        @keyframes bounce {
            0%, 20%, 50%, 80%, 100% { transform: translateY(0); }
            40% { transform: translateY(-10px); }
            60% { transform: translateY(-5px); }
        }

        .login-header h2 {
            font-size: 2rem;
            color: #333;
            margin-bottom: 0.5rem;
            font-weight: 700;
        }

        .login-header p {
            color: #666;
            font-size: 1rem;
        }

        /* Tab Navigation */
        .tab-navigation {
            display: flex;
            margin-bottom: 2rem;
            background: rgba(102, 126, 234, 0.1);
            border-radius: 15px;
            padding: 0.3rem;
        }

        .tab-btn {
            flex: 1;
            padding: 0.8rem;
            background: transparent;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            color: #667eea;
        }

        .tab-btn.active {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.3);
        }

        /* Form Styles */
        .form-container {
            position: relative;
        }

        .form-group {
            margin-bottom: 1.5rem;
            position: relative;
        }

        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            color: #333;
            font-weight: 500;
            font-size: 0.9rem;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: #667eea;
            font-size: 1.1rem;
        }

        .form-input {
            width: 100%;
            padding: 1rem 1rem 1rem 3rem;
            border: 2px solid rgba(102, 126, 234, 0.2);
            border-radius: 15px;
            font-size: 1rem;
            transition: all 0.3s ease;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(10px);
        }

        .form-input:focus {
            outline: none;
            border-color: #667eea;
            box-shadow: 0 0 20px rgba(102, 126, 234, 0.2);
            background: white;
        }

        .form-input::placeholder {
            color: #999;
        }

        /* Password Toggle */
        .password-toggle {
            position: absolute;
            right: 1rem;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            color: #667eea;
            font-size: 1.1rem;
            z-index: 10;
        }

        /* Submit Button */
        .submit-btn {
            width: 100%;
            padding: 1rem;
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            border: none;
            border-radius: 15px;
            font-size: 1.1rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }

        .submit-btn::before {
            content: '';
            position: absolute;
            top: 0;
            left: -100%;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #764ba2, #667eea);
            transition: left 0.3s ease;
        }

        .submit-btn:hover::before {
            left: 0;
        }

        .submit-btn span {
            position: relative;
            z-index: 1;
        }

        .submit-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(102, 126, 234, 0.4);
        }

        .submit-btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
            transform: none;
        }

        /* Alert Messages */
        .alert {
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
            font-weight: 500;
            animation: slideIn 0.3s ease;
        }

        .alert-error {
            background: rgba(220, 53, 69, 0.1);
            color: #dc3545;
            border: 1px solid rgba(220, 53, 69, 0.2);
        }

        .alert-success {
            background: rgba(40, 167, 69, 0.1);
            color: #28a745;
            border: 1px solid rgba(40, 167, 69, 0.2);
        }

        @keyframes slideIn {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        /* Additional Links */
        .additional-links {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid rgba(102, 126, 234, 0.2);
        }

        .additional-links a {
            color: #667eea;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .additional-links a:hover {
            color: #764ba2;
            text-decoration: underline;
        }

        /* Hidden Forms */
        .form-section {
            display: none;
        }

        .form-section.active {
            display: block;
            animation: fadeIn 0.3s ease;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .menu-toggle {
                display: flex;
            }

            nav {
                display: none;
                position: absolute;
                top: 100%;
                left: 0;
                width: 100%;
                background: rgba(255, 255, 255, 0.98);
                backdrop-filter: blur(15px);
                flex-direction: column;
                padding: 2rem;
                box-shadow: 0 5px 20px rgba(0, 0, 0, 0.1);
                border-radius: 0 0 20px 20px;
            }

            nav.active {
                display: flex;
            }

            nav a {
                margin: 0.5rem 0;
                text-align: center;
            }

            .header-container {
                padding: 1rem;
            }

            .main-content {
                padding: 2rem 1rem;
            }

            .login-container {
                padding: 2rem;
                margin: 1rem;
            }

            .login-header h2 {
                font-size: 1.8rem;
            }

            .floating-element {
                font-size: 1.5rem !important;
            }
        }

        @media (max-width: 480px) {
            .login-container {
                padding: 1.5rem;
                margin: 0.5rem;
            }

            .login-header h2 {
                font-size: 1.6rem;
            }

            .tab-btn {
                padding: 0.6rem;
                font-size: 0.9rem;
            }

            .floating-element {
                font-size: 1.2rem !important;
            }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="background-animation">
        <!-- Floating Educational Icons -->
        <div class="floating-element"><i class="fas fa-book-open"></i></div>
        <div class="floating-element"><i class="fas fa-user-graduate"></i></div>
        <div class="floating-element"><i class="fas fa-pencil-alt"></i></div>
        <div class="floating-element"><i class="fas fa-laptop"></i></div>
        <div class="floating-element"><i class="fas fa-calculator"></i></div>
        <div class="floating-element"><i class="fas fa-lightbulb"></i></div>
        <div class="floating-element"><i class="fas fa-brain"></i></div>
        <div class="floating-element"><i class="fas fa-graduation-cap"></i></div>

        <!-- Geometric Shapes -->
        <div class="geometric-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
            <div class="shape"></div>
        </div>

        <!-- Floating Particles -->
        <div class="particles">
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
            <div class="particle"></div>
        </div>
    </div>

    <!-- Header -->
    <header>
        <div class="header-container">
            <a href="index.php" class="logo">
                <i class="fas fa-graduation-cap"></i>
                <h1>EduAssign</h1>
            </a>
            <nav id="nav">
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="student_login.php" class="active"><i class="fas fa-user-graduate"></i> Student Login</a>
                <a href="teacher_login.php"><i class="fas fa-chalkboard-teacher"></i> Teacher Login</a>
                <a href="admin_login.php"><i class="fas fa-user-shield"></i> Admin Login</a>
            </nav>
            <div class="menu-toggle" onclick="toggleMenu()">
                <span></span>
                <span></span>
                <span></span>
            </div>
        </div>
    </header>

    <!-- Main Content -->
    <main class="main-content">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-user-graduate"></i>
                <h2>Student Portal</h2>
                <p>Access your assignments and track your academic progress</p>
            </div>

            <!-- Tab Navigation -->
            <div class="tab-navigation">
                <button class="tab-btn active" onclick="switchTab('login')">Login</button>
                <button class="tab-btn" onclick="switchTab('register')">Register</button>
            </div>

            <!-- Alert Messages -->
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error_message); ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($success_message); ?>
                </div>
            <?php endif; ?>

            <!-- Login Form -->
            <div class="form-section active" id="login-form">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="email">Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="email" name="email" class="form-input" 
                                   placeholder="Enter your username" required 
                                   value="<?php echo isset($_POST['email']) ? htmlspecialchars($_POST['email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="password" name="password" class="form-input" 
                                   placeholder="Enter your password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('password')"></i>
                        </div>
                    </div>

                    <button type="submit" name="login" class="submit-btn">
                        <span><i class="fas fa-sign-in-alt"></i> Login to Portal</span>
                    </button>
                </form>

                <div class="additional-links">
                    <a href="#" onclick="alert('Password reset functionality would be implemented here')">
                        Forgot your password?
                    </a>
                </div>
            </div>

            <!-- Registration Form -->
            <div class="form-section" id="register-form">
                <form method="POST" action="">
                    <div class="form-group">
                        <label for="reg_name">Full Name</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="reg_name" name="reg_name" class="form-input" 
                                   placeholder="Enter your full name" required
                                   value="<?php echo isset($_POST['reg_name']) ? htmlspecialchars($_POST['reg_name']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg_username">Username</label>
                        <div class="input-wrapper">
                            <i class="fas fa-user"></i>
                            <input type="text" id="reg_username" name="reg_username" class="form-input" 
                                   placeholder="Choose a username" required
                                   value="<?php echo isset($_POST['reg_username']) ? htmlspecialchars($_POST['reg_username']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="student_id">Student ID</label>
                        <div class="input-wrapper">
                            <i class="fas fa-id-card"></i>
                            <input type="text" id="student_id" name="student_id" class="form-input" 
                                   placeholder="Enter your student ID" required
                                   value="<?php echo isset($_POST['student_id']) ? htmlspecialchars($_POST['student_id']) : ''; ?>">
                        </div>
                    </div>


                    <div class="form-group">
                        <label for="reg_course">Course</label>
                        <div class="input-wrapper">
                            <i class="fas fa-book"></i>
                            <select id="reg_course" name="reg_course" class="form-input" required>
                                <option value="">Select Course</option>
                                <option value="BCA">BCA</option>
                                <option value="BCom">BCom</option>
                            </select>
                        </div>
                    </div> 

                    <div class="form-group">
                        <label for="reg_email">Email Address</label>
                        <div class="input-wrapper">
                            <i class="fas fa-envelope"></i>
                            <input type="email" id="reg_email" name="reg_email" class="form-input" 
                                   placeholder="Enter your email address" required
                                   value="<?php echo isset($_POST['reg_email']) ? htmlspecialchars($_POST['reg_email']) : ''; ?>">
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="reg_password">Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="reg_password" name="reg_password" class="form-input" 
                                   placeholder="Create a password (min. 6 characters)" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('reg_password')"></i>
                        </div>
                    </div>

                    <div class="form-group">
                        <label for="confirm_password">Confirm Password</label>
                        <div class="input-wrapper">
                            <i class="fas fa-lock"></i>
                            <input type="password" id="confirm_password" name="confirm_password" class="form-input" 
                                   placeholder="Confirm your password" required>
                            <i class="fas fa-eye password-toggle" onclick="togglePassword('confirm_password')"></i>
                        </div>
                    </div>

                    

                    <button type="submit" name="register" class="submit-btn">
                        <span><i class="fas fa-user-plus"></i> Create Account</span>
                    </button>
                </form>

                <div class="additional-links">
                    <p>By registering, you agree to our <a href="#">Terms of Service</a> and <a href="#">Privacy Policy</a></p>
                </div>
            </div>
        </div>
    </main>

    <script>
        function toggleMenu() {
            const nav = document.getElementById('nav');
            const toggle = document.querySelector('.menu-toggle');
            nav.classList.toggle('active');
            toggle.classList.toggle('active');
        }

        function switchTab(tab) {
            // Update tab buttons
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            event.target.classList.add('active');

            // Update form sections
            document.querySelectorAll('.form-section').forEach(section => section.classList.remove('active'));
            document.getElementById(tab + '-form').classList.add('active');

            // Clear any existing alerts
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => alert.remove());
        }

        function togglePassword(inputId) {
            const input = document.getElementById(inputId);
            const toggle = input.parentElement.querySelector('.password-toggle');
            
            if (input.type === 'password') {
                input.type = 'text';
                toggle.classList.remove('fa-eye');
                toggle.classList.add('fa-eye-slash');
            } else {
                input.type = 'password';
                toggle.classList.remove('fa-eye-slash');
                toggle.classList.add('fa-eye');
            }
        }



        // Header scroll effect
        window.addEventListener('scroll', function() {
            const header = document.querySelector('header');
            if (window.scrollY > 100) {
                header.style.background = 'rgba(255, 255, 255, 0.98)';
                header.style.boxShadow = '0 2px 30px rgba(0, 0, 0, 0.15)';
            } else {
                header.style.background = 'rgba(255, 255, 255, 0.95)';
                header.style.boxShadow = '0 2px 25px rgba(0, 0, 0, 0.1)';
            }
        });

        // Close mobile menu when clicking outside
        document.addEventListener('click', function(e) {
            const nav = document.getElementById('nav');
            const toggle = document.querySelector('.menu-toggle');
            const header = document.querySelector('header');
            
            if (!header.contains(e.target) && nav.classList.contains('active')) {
                nav.classList.remove('active');
                toggle.classList.remove('active');
            }
        });

        // Form validation enhancement
        document.querySelectorAll('.form-input').forEach(input => {
            input.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.style.borderColor = 'rgba(220, 53, 69, 0.5)';
                } else {
                    this.style.borderColor = 'rgba(40, 167, 69, 0.5)';
                }
            });

            input.addEventListener('focus', function() {
                this.style.borderColor = '#667eea';
            });
        });

        // Auto-hide success messages
        setTimeout(function() {
            const successAlerts = document.querySelectorAll('.alert-success');
            successAlerts.forEach(alert => {
                alert.style.opacity = '0';
                alert.style.transform = 'translateY(-10px)';
                setTimeout(() => alert.remove(), 300);
            });
        }, 5000);
    </script>
</body>
</html>
