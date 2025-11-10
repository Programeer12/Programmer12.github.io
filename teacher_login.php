<?php
require_once 'includes/auth.php';

// Check if teacher is already logged in
if ($auth->isLoggedIn() && $auth->hasRole('teacher')) {
    header("Location: teacher_dashboard.php");
    exit();
}

$error_message = '';
$success_message = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    // Login
    if (isset($_POST['login'])) {
        $username = trim($_POST['email'] ?? ''); // Using email field as username
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            $error_message = "Please fill in all fields.";
        } else {
            // Use the auth system to login
            $result = $auth->login($username, $password);
            if ($result['success'] && $result['user']['role'] === 'teacher') {
                header("Location: teacher_dashboard.php");
                exit();
            } else {
                $error_message = "Invalid credentials or insufficient privileges.";
            }
        }
    }

    // Registration
    if (isset($_POST['register'])) {
        $name = trim($_POST['reg_name'] ?? '');
        $username = trim($_POST['reg_username'] ?? '');
        $email = trim($_POST['reg_email'] ?? '');
        $employee_id = trim($_POST['employee_id'] ?? '');
        $department = trim($_POST['department'] ?? '');
    $subject = trim($_POST['subject'] ?? '');
        $course = trim($_POST['reg_course'] ?? '');
        $password = $_POST['reg_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (empty($name) || empty($email) || empty($employee_id) || empty($department) || empty($subject) || empty($password) || empty($confirm_password) || empty($username) || empty($course)) {
            $error_message = "Please fill in all fields.";
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_message = "Please enter a valid email address.";
        } elseif ($password !== $confirm_password) {
            $error_message = "Passwords do not match.";
        } elseif (strlen($password) < 8) {
            $error_message = "Password must be at least 8 characters long.";
        } else {
            try {
                // Check for existing username/email
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
                        // Check if a teacher already exists for this subject and course combination
                        $stmt = $pdo->prepare('SELECT COUNT(*) FROM users WHERE role = "teacher" AND status = "active" AND LOWER(subject) = LOWER(?)');
                        $stmt->execute([$subject]);
                        if ($stmt->fetchColumn() > 0) {
                            $error_message = "A teacher is already registered for {$subject}. Only one teacher can be assigned per subject.";
                        } else {
                            // Check if a teacher is pending approval for this subject
                            $stmt = $pdo->prepare('SELECT COUNT(*) FROM pending_registrations WHERE role = "teacher" AND LOWER(subject) = LOWER(?)');
                            $stmt->execute([$subject]);
                            if ($stmt->fetchColumn() > 0) {
                                $error_message = "A teacher registration is already pending approval for {$subject}.";
                            } else {
                                $hashed = password_hash($password, PASSWORD_DEFAULT);
                                $stmt = $pdo->prepare('INSERT INTO pending_registrations (name, username, email, password, role, subject, course) VALUES (?, ?, ?, ?, ?, ?, ?)');
                                $stmt->execute([$name, $username, $email, $hashed, 'teacher', $subject, $course]);
                                $success_message = 'Registration submitted! Awaiting admin approval.';

                                // Notify all active admins about the new pending registration
                                try {
                                    require_once 'includes/notification_system.php';
                                    $notificationSystem = new NotificationSystem($pdo);
                                    $stmtAdmins = $pdo->prepare("SELECT id FROM users WHERE role = 'admin' AND status = 'active'");
                                    $stmtAdmins->execute();
                                    $adminIds = $stmtAdmins->fetchAll(PDO::FETCH_COLUMN);
                                    $notifTitle = "New Teacher Registration";
                                    $notifMsg = "$name ({$username}) has registered and is awaiting approval for course: $course.";
                                    foreach ($adminIds as $adminId) {
                                        $notificationSystem->createNotification($adminId, $notifTitle, $notifMsg, 'general', null, 'registration', 'high');
                                    }
                                } catch (Exception $e) {
                                    // Logging only; do not block registration flow
                                    error_log('Failed to create admin notification for registration: ' . $e->getMessage());
                                }

                                $_POST = [];
                            }
                        }
                    }
                }
            } catch (PDOException $e) {
                $error_message = 'Database error: ' . $e->getMessage();
            }
        }
    }

    // Status Check
    if (isset($_POST['check_status'])) {
        $email = trim($_POST['status_email'] ?? '');
        if (!empty($email) && filter_var($email, FILTER_VALIDATE_EMAIL)) {
            // Demo: In real app, check pending/approved status
            $success_message = "If your registration is approved, you'll receive an email notification.";
        } else {
            $error_message = "Please enter a valid email address.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Teacher Login - EduAssign</title>
    <meta name="description" content="Teacher login portal for EduAssign - Create assignments, grade submissions, and manage your classes.">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            overflow-x: hidden;
            color: #333;
        }
        /* Animated Background Elements */
        .background-animation {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            z-index: -1; overflow: hidden;
        }
        .floating-element {
            position: absolute;
            color: rgba(255,255,255,0.08);
            animation: float 10s ease-in-out infinite;
            pointer-events: none;
        }
        .floating-element:nth-child(1) { top: 8%; left: 5%; font-size: 2.8rem; animation-delay: 0s; }
        .floating-element:nth-child(2) { top: 20%; right: 10%; font-size: 2.4rem; animation-delay: 1.5s; }
        .floating-element:nth-child(3) { top: 60%; left: 8%; font-size: 2.2rem; animation-delay: 3s; }
        .floating-element:nth-child(4) { top: 80%; right: 15%; font-size: 3.2rem; animation-delay: 4.5s; }
        .floating-element:nth-child(5) { top: 40%; left: 90%; font-size: 2.6rem; animation-delay: 6s; }
        .floating-element:nth-child(6) { top: 15%; left: 50%; font-size: 2.3rem; animation-delay: 7.5s; }
        .floating-element:nth-child(7) { top: 70%; left: 30%; font-size: 2.7rem; animation-delay: 2s; }
        .floating-element:nth-child(8) { top: 35%; right: 25%; font-size: 2.9rem; animation-delay: 5s; }
        .floating-element:nth-child(9) { top: 55%; left: 65%; font-size: 2.5rem; animation-delay: 8s; }
        /* Geometric shapes */
        .geometric-shapes {
            position: absolute; width: 100%; height: 100%;
        }
        .shape {
            position: absolute; opacity: 0.04; animation: rotate 25s linear infinite;
        }
        .shape:nth-child(1) {
            top: 18%; left: 22%; width: 120px; height: 120px;
            background: white; border-radius: 50%; animation-delay: 0s;
        }
        .shape:nth-child(2) {
            top: 65%; right: 18%; width: 90px; height: 90px;
            background: white; transform: rotate(45deg); animation-delay: 6s;
        }
        .shape:nth-child(3) {
            top: 42%; left: 65%; width: 0; height: 0;
            border-left: 50px solid transparent;
            border-right: 50px solid transparent;
            border-bottom: 85px solid white;
            animation-delay: 12s;
        }
        .shape:nth-child(4) {
            top: 72%; left: 8%; width: 140px; height: 140px;
            background: white; border-radius: 25px; animation-delay: 18s;
        }
        .shape:nth-child(5) {
            top: 8%; right: 35%; width: 100px; height: 100px;
            background: white; clip-path: polygon(50% 0%, 0% 100%, 100% 100%);
            animation-delay: 3s;
        }
        /* Floating particles */
        .particles {
            position: absolute; width: 100%; height: 100%;
        }
        .particle {
            position: absolute; width: 5px; height: 5px;
            background: rgba(255,255,255,0.25); border-radius: 50%;
            animation: particleFloat 10s linear infinite;
        }
        .particle:nth-child(1) { left: 10%; animation-delay: 0s; }
        .particle:nth-child(2) { left: 25%; animation-delay: 2s; }
        .particle:nth-child(3) { left: 40%; animation-delay: 4s; }
        .particle:nth-child(4) { left: 55%; animation-delay: 6s; }
        .particle:nth-child(5) { left: 70%; animation-delay: 8s; }
        .particle:nth-child(6) { left: 85%; animation-delay: 10s; }
        @keyframes float {
            0%,100% { transform: translateY(0px) rotate(0deg) scale(1); opacity: 0.08; }
            25% { transform: translateY(-15px) rotate(90deg) scale(1.1); opacity: 0.15; }
            50% { transform: translateY(-25px) rotate(180deg) scale(0.9); opacity: 0.25; }
            75% { transform: translateY(-10px) rotate(270deg) scale(1.05); opacity: 0.12; }
        }
        @keyframes rotate {
            0% { transform: rotate(0deg) scale(1);}
            50% { transform: rotate(180deg) scale(1.1);}
            100% { transform: rotate(360deg) scale(1);}
        }
        @keyframes particleFloat {
            0% { transform: translateY(100vh) scale(0); opacity: 0; }
            15% { opacity: 1; transform: translateY(85vh) scale(1);}
            85% { opacity: 1; transform: translateY(15vh) scale(1);}
            100% { transform: translateY(-10vh) scale(0); opacity: 0;}
        }
        /* Header Styles */
        header {
            background: rgba(255,255,255,0.95);
            backdrop-filter: blur(15px);
            box-shadow: 0 2px 25px rgba(0,0,0,0.1);
            position: fixed; width: 100%; top: 0; z-index: 1000;
        }
        .header-container {
            max-width: 1200px; margin: 0 auto;
            display: flex; justify-content: space-between; align-items: center;
            padding: 1rem 2rem;
        }
        .logo { display: flex; align-items: center; gap: 12px; }
        .logo i { font-size: 2.2rem; color: #667eea; animation: pulse 2s infinite; }
        .logo h1 { font-size: 1.6rem; color: #333; font-weight: 700; }
        @keyframes pulse { 0%,100%{transform:scale(1);} 50%{transform:scale(1.05);} }
        nav { display: flex; gap: 2rem; }
        nav a {
            text-decoration: none; color: #333; font-weight: 500;
            padding: 0.6rem 1.2rem; border-radius: 30px; transition: all 0.3s;
            position: relative; overflow: hidden;
        }
        nav a:hover, nav a.active {
            color: white; background: linear-gradient(135deg, #667eea, #764ba2);
            box-shadow: 0 8px 20px rgba(102,126,234,0.2);
        }
        /* Main Content */
        .main-content {
            margin-top: 110px; display: flex; justify-content: center; align-items: flex-start;
            min-height: 80vh;
        }
        .login-container {
            background: rgba(255,255,255,0.97);
            border-radius: 25px;
            box-shadow: 0 10px 40px rgba(102,126,234,0.08);
            padding: 2.5rem 2rem 2rem 2rem;
            width: 100%; max-width: 430px;
            margin: 2rem 0;
            position: relative;
            overflow: hidden;
        }
        .login-header {
            text-align: center; margin-bottom: 2rem;
        }
        .login-header i {
            font-size: 2.5rem; color: #667eea; margin-bottom: 0.5rem;
            animation: bounce 1.5s infinite alternate;
        }
        @keyframes bounce {
            0% { transform: translateY(0);}
            100% { transform: translateY(-8px);}
        }
        .login-header h2 {
            font-size: 2rem; font-weight: 700; color: #333;
        }
        .login-header p {
            color: #666; font-size: 1rem; margin-top: 0.5rem;
        }
        .tab-navigation {
            display: flex; gap: 1rem; justify-content: center; margin-bottom: 2rem;
        }
        .tab-btn {
            background: none; border: none; font-size: 1.1rem; font-weight: 600;
            color: #667eea; padding: 0.7rem 1.5rem; border-radius: 30px;
            cursor: pointer; transition: background 0.2s;
            position: relative;
        }
        .tab-btn.active, .tab-btn:hover {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            box-shadow: 0 4px 16px rgba(102,126,234,0.12);
        }
        .form-section { display: none; }
        .form-section.active { display: block; animation: fadeIn 0.7s; }
        @keyframes fadeIn { from{opacity:0;} to{opacity:1;} }
        .form-group { margin-bottom: 1.3rem; }
        .form-group label { display: block; margin-bottom: 0.5rem; color: #333; font-weight: 500; }
        .input-wrapper {
            position: relative;
        }
        .input-wrapper i {
            position: absolute; left: 14px; top: 50%; transform: translateY(-50%);
            color: #b3b3b3; font-size: 1rem;
        }
        .form-input {
            width: 100%; padding: 0.8rem 1rem 0.8rem 2.5rem; border-radius: 8px;
            border: 1px solid #ccc; font-size: 1rem; transition: border 0.2s;
            background: #f8f8ff;
        }
        .form-input:focus { border: 1.5px solid #667eea; outline: none; background: #fff; }
        .submit-btn {
            width: 100%; padding: 1rem; border: none; border-radius: 30px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white; font-weight: 700; font-size: 1.1rem; cursor: pointer;
            transition: background 0.2s, transform 0.2s;
            margin-top: 0.5rem;
            box-shadow: 0 4px 16px rgba(102,126,234,0.12);
        }
        .submit-btn:hover { background: #667eea; transform: translateY(-2px);}
        .alert {
            padding: 1rem; border-radius: 8px; margin-bottom: 1.2rem;
            font-size: 1rem; text-align: center;
        }
        .alert-error { background: #ffeaea; color: #c0392b; border: 1px solid #e57373; }
        .alert-success { background: #eaffea; color: #27ae60; border: 1px solid #81c784; }
        @media (max-width: 600px) {
            .login-container { padding: 1.2rem; }
            .main-content { margin-top: 80px; }
        }
    </style>
</head>
<body>
    <!-- Animated Background -->
    <div class="background-animation">
        <!-- Floating Educational Icons -->
        <div class="floating-element"><i class="fas fa-chalkboard-teacher"></i></div>
        <div class="floating-element"><i class="fas fa-book"></i></div>
        <div class="floating-element"><i class="fas fa-laptop"></i></div>
        <div class="floating-element"><i class="fas fa-graduation-cap"></i></div>
        <div class="floating-element"><i class="fas fa-lightbulb"></i></div>
        <div class="floating-element"><i class="fas fa-users"></i></div>
        <div class="floating-element"><i class="fas fa-flask"></i></div>
        <div class="floating-element"><i class="fas fa-brain"></i></div>
        <div class="floating-element"><i class="fas fa-globe"></i></div>
        <!-- Geometric Shapes -->
        <div class="geometric-shapes">
            <div class="shape"></div>
            <div class="shape"></div>
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
            <div class="logo">
                <i class="fas fa-chalkboard-teacher"></i>
                <h1>EduAssign</h1>
            </div>
            <nav>
                <a href="index.php"><i class="fas fa-home"></i> Home</a>
                <a href="student_login.php"><i class="fas fa-user-graduate"></i> Student Login</a>
                <a href="teacher_login.php" class="active" style="font-weight:bold;"><i class="fas fa-chalkboard-teacher"></i> Teacher Login</a>
                <a href="admin_login.php"><i class="fas fa-user-shield"></i> Admin Login</a>
            </nav>
        </div>
    </header>
    <!-- Main Content -->
    <main class="main-content">
        <div class="login-container">
            <div class="login-header">
                <i class="fas fa-chalkboard-teacher"></i>
                <h2>Teacher Portal</h2>
                <p>Welcome! Login or register</p>
            </div>
            <?php if ($error_message): ?>
                <div class="alert alert-error"><?php echo $error_message; ?></div>
            <?php endif; ?>
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?php echo $success_message; ?></div>
            <?php endif; ?>
            <div class="tab-navigation">
                <button class="tab-btn active" onclick="switchTab('login', event)"><i class="fas fa-sign-in-alt"></i> Login</button>
                <button class="tab-btn" onclick="switchTab('register', event)"><i class="fas fa-user-plus"></i> Register</button>
            </div>
            <!-- Login Form -->
            <form class="form-section active" id="login-form" method="post" autocomplete="off">
                <div class="form-group">
                    <label for="email">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input class="form-input" type="text" id="email" name="email" required placeholder="Enter your username">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input class="form-input" type="password" id="password" name="password" required placeholder="Enter your password">
                    </div>
                </div>
                <button class="submit-btn" type="submit" name="login"><i class="fas fa-sign-in-alt"></i> Login</button>
            </form>
            <!-- Register Form -->
            <form class="form-section" id="register-form" method="post" autocomplete="off">
                <div class="form-group">
                    <label for="reg_name">Full Name</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input class="form-input" type="text" id="reg_name" name="reg_name" required placeholder="Enter your full name" value="<?php echo isset($_POST['reg_name'])?htmlspecialchars($_POST['reg_name']):''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg_username">Username</label>
                    <div class="input-wrapper">
                        <i class="fas fa-user"></i>
                        <input class="form-input" type="text" id="reg_username" name="reg_username" required placeholder="Choose a username" value="<?php echo isset($_POST['reg_username'])?htmlspecialchars($_POST['reg_username']):''; ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg_email">Email Address</label>
                    <div class="input-wrapper">
                        <i class="fas fa-envelope"></i>
                        <input class="form-input" type="email" id="reg_email" name="reg_email" required placeholder="Enter your email">
                    </div>
                </div>
                <div class="form-group">
                    <label for="employee_id">Employee ID</label>
                    <div class="input-wrapper">
                        <i class="fas fa-id-badge"></i>
                        <input class="form-input" type="text" id="employee_id" name="employee_id" required placeholder="Enter your employee ID">
                    </div>
                </div>
                <div class="form-group">
                    <label for="department">Department</label>
                    <div class="input-wrapper">
                        <i class="fas fa-building"></i>
                        <select class="form-input" id="department" name="department" required>
                            <option value="">Select Department</option>
                            <option value="BCA" <?php echo (isset($_POST['department']) && $_POST['department']=='BCA')?'selected':''; ?>>BCA</option>
                            <option value="BCom" <?php echo (isset($_POST['department']) && $_POST['department']=='BCom')?'selected':''; ?>>BCom</option>
                        </select>
                    </div>
                </div>
                <div class="form-group">
                    <label for="reg_course">Course</label>
                    <div class="input-wrapper">
                        <i class="fas fa-book"></i>
                        <select id="reg_course" name="reg_course" class="form-input" required>
                            <option value="">Select Course</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="subject">Subject</label>
                    <div class="input-wrapper">
                        <i class="fas fa-book-open"></i>
                        <select class="form-input" id="subject" name="subject" required>
                            <option value="">Select Subject</option>
                        </select>
                    </div>
                </div>
               
                <div class="form-group">
                    <label for="reg_password">Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input class="form-input" type="password" id="reg_password" name="reg_password" required placeholder="Create a password">
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm_password">Confirm Password</label>
                    <div class="input-wrapper">
                        <i class="fas fa-lock"></i>
                        <input class="form-input" type="password" id="confirm_password" name="confirm_password" required placeholder="Confirm your password">
                    </div>
                </div>
                <button class="submit-btn" type="submit" name="register"><i class="fas fa-user-plus"></i> Register</button>
            </form>

        </div>
    </main>
    <script>
        function switchTab(tab, event) {
            document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
            if (event) event.target.classList.add('active');
            document.querySelectorAll('.form-section').forEach(section => section.classList.remove('active'));
            document.getElementById(tab + '-form').classList.add('active');
        }

        // Courses for each department
        const courses = {
            BCA: ["BCA"],
            BCom: ["BCom", "B.com(C&N)"]
        };

        // Subjects for each course
        const subjects = {
            BCA: ["Programming in C", "Data Structures", "Database Management", "Web Technologies", "Operating Systems"],
            BCom: ["Financial Accounting", "Business Law", "Economics", "Taxation", "Marketing"],
            "B.com(F&T)": ["Financial Accounting", "Business Law", "Economics", "Taxation", "Marketing"]
        };

        // Update course dropdown based on selected department
        function updateCourses() {
            const dept = document.getElementById('department').value;
            const courseSelect = document.getElementById('reg_course');
            courseSelect.innerHTML = '<option value="">Select Course</option>';
            if (courses[dept]) {
                courses[dept].forEach(crs => {
                    const option = document.createElement('option');
                    option.value = crs;
                    option.textContent = crs;
                    // If previously selected, keep selected
                    if (crs === "<?php echo isset($_POST['reg_course'])?htmlspecialchars($_POST['reg_course']):''; ?>") {
                        option.selected = true;
                    }
                    courseSelect.appendChild(option);
                });
            }
            updateSubjects(); // Also update subjects when course changes
        }

        // Update subject dropdown based on selected course
        function updateSubjects() {
            const course = document.getElementById('reg_course').value;
            const subjectSelect = document.getElementById('subject');
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            if (subjects[course]) {
                subjects[course].forEach(sub => {
                    const option = document.createElement('option');
                    option.value = sub;
                    option.textContent = sub;
                    // If previously selected, keep selected
                    if (sub === "<?php echo isset($_POST['subject'])?htmlspecialchars($_POST['subject']):''; ?>") {
                        option.selected = true;
                    }
                    subjectSelect.appendChild(option);
                });
            }
        }

        document.getElementById('department').addEventListener('change', updateCourses);
        document.getElementById('reg_course').addEventListener('change', updateSubjects);
        // On page load, set courses and subjects if department/course is preselected
        document.addEventListener('DOMContentLoaded', function() {
            updateCourses();
        });

        // Optional: Auto-hide success messages after 5 seconds
        setTimeout(function() {
            var alert = document.querySelector('.alert-success');
            if(alert) alert.style.display = 'none';
        }, 5000);
    </script>

    <footer>
        <p>&copy; 2025 EduAssign. All rights reserved.</p>
    </footer>
</body>
</html>