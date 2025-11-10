<?php
require_once 'includes/auth.php';

// Require admin role
$auth->requireRole('admin');

$success_message = '';
$error_message = '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Approve registration
    if (isset($_POST['approve_registration'])) {
        $registration_id = intval($_POST['registration_id']);
        $result = $auth->approveRegistration($registration_id);
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
    }
    
    // Reject registration
    if (isset($_POST['reject_registration'])) {
        $registration_id = intval($_POST['registration_id']);
        $result = $auth->rejectRegistration($registration_id);
        if ($result['success']) {
            $success_message = $result['message'];
        } else {
            $error_message = $result['message'];
        }
    }
    
    // Delete user
    if (isset($_POST['delete_user'])) {
        $user_id = intval($_POST['user_id']);
        try {
            $stmt = $pdo->prepare("DELETE FROM users WHERE id = ? AND role != 'admin'");
            $stmt->execute([$user_id]);
            if ($stmt->rowCount() > 0) {
                $success_message = "User deleted successfully!";
            } else {
                $error_message = "User not found or cannot delete admin.";
            }
        } catch (PDOException $e) {
            $error_message = "Error deleting user: " . $e->getMessage();
        }
    }
    
    // Update user status
    if (isset($_POST['update_status'])) {
        $user_id = intval($_POST['user_id']);
        $new_status = $_POST['update_status']; // The button value contains the new status
        try {
            $stmt = $pdo->prepare("UPDATE users SET status = ? WHERE id = ? AND role != 'admin'");
            $stmt->execute([$new_status, $user_id]);
            if ($stmt->rowCount() > 0) {
                $success_message = "User status updated successfully!";
            } else {
                $error_message = "User not found or cannot update admin.";
            }
        } catch (PDOException $e) {
            $error_message = "Error updating user: " . $e->getMessage();
        }
    }
}

// Get real data from database
try {
    // Get pending registrations
    $stmt = $pdo->prepare("SELECT * FROM pending_registrations ORDER BY role, name");
    $stmt->execute();
    $pending_registrations = $stmt->fetchAll();
    
    // Get BCA teachers
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'teacher' AND course = 'BCA' ORDER BY name");
    $stmt->execute();
    $bca_teachers = $stmt->fetchAll();
    
    // Get BCom teachers
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'teacher' AND course = 'BCom' ORDER BY name");
    $stmt->execute();
    $bcom_teachers = $stmt->fetchAll();
    
    // Get BCA students
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'student' AND course = 'BCA' ORDER BY name");
    $stmt->execute();
    $bca_students = $stmt->fetchAll();
    
    // Get BCom students
    $stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'student' AND course = 'BCom' ORDER BY name");
    $stmt->execute();
    $bcom_students = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $pending_registrations = [];
    $bca_teachers = [];
    $bcom_teachers = [];
    $bca_students = [];
    $bcom_students = [];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Users - EduAssign Admin</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { 
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; 
            background: #f6f7fb; 
            margin: 0; 
        }
        header { 
            background: #fff; 
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
        .main { 
            margin-top: 90px; 
            padding: 2rem; 
            max-width: 1200px; 
            margin-left: auto; 
            margin-right: auto; 
        }
        h2 { 
            color: #333; 
            margin-bottom: 1rem;
        }
        .alert {
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        table { 
            width: 100%; 
            border-collapse: collapse; 
            margin-bottom: 2rem; 
            background: #fff; 
            border-radius: 10px; 
            overflow: hidden; 
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        th, td { 
            padding: 1rem; 
            border-bottom: 1px solid #eee; 
            text-align: left; 
        }
        th { 
            background: #f3f4fa; 
            font-weight: 600;
        }
        tr:last-child td { 
            border-bottom: none; 
        }
        tr:hover {
            background: #f8f9fa;
        }
        .action-btn { 
            background: #667eea; 
            color: #fff; 
            border: none; 
            border-radius: 5px; 
            padding: 0.4rem 1rem; 
            cursor: pointer; 
            margin-right: 0.5rem; 
            font-size: 0.9rem;
        }
        .action-btn.delete { 
            background: #e74c3c; 
        }
        .action-btn.warning { 
            background: #f39c12; 
        }
        .action-btn:hover { 
            opacity: 0.85; 
        }
        .status-active {
            color: #27ae60;
            font-weight: 600;
        }
        .status-inactive {
            color: #e74c3c;
            font-weight: 600;
        }
        .role-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }
        .role-student {
            background: #e3f2fd;
            color: #1976d2;
        }
        .role-teacher {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        .course-badge {
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }
        .course-bca {
            background: #e8f5e9;
            color: #388e3c;
        }
        .course-bcom {
            background: #fff3e0;
            color: #e65100;
        }
        .section {
            margin-bottom: 3rem;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .count-badge {
            background: #667eea;
            color: white;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-size: 0.9rem;
            font-weight: 600;
        }
        .course-section {
            margin-bottom: 2rem;
            background: white;
            border-radius: 10px;
            overflow: hidden;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .course-header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 1rem 1.5rem;
            font-size: 1.2rem;
            font-weight: 600;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .course-header .count {
            background: rgba(255,255,255,0.2);
            padding: 0.2rem 0.6rem;
            border-radius: 20px;
            font-size: 0.9rem;
        }
        .tabs {
            display: flex;
            background: #f3f4fa;
            border-bottom: 1px solid #eee;
        }
        .tab {
            padding: 1rem 1.5rem;
            cursor: pointer;
            font-weight: 500;
            border-bottom: 3px solid transparent;
        }
        .tab.active {
            border-bottom-color: #667eea;
            color: #667eea;
        }
        .tab-content {
            display: none;
            opacity: 0;
            transition: opacity 0.3s ease;
            position: absolute;
            width: 100%;
            pointer-events: none;
        }
        .tab-content.active {
            display: block;
            opacity: 1;
            position: relative;
            pointer-events: auto;
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
        .tab {
            transition: all 0.3s ease;
            cursor: pointer;
        }
        .tab:hover {
            background-color: #f0f1f7;
        }
        @media (max-width: 900px) { 
            .main { 
                padding: 1rem; 
            } 
            table, th, td { 
                font-size: 0.95rem; 
            } 
        }
    </style>
</head>
<body>
    <header>
        <div class="header-container">
            <a href="admin_dashboard.php" class="logo">
                <i class="fas fa-user-shield"></i>
                <h1>EduAssign Admin</h1>
            </a>
            <nav>
                <a href="admin_dashboard.php"><i class="fas fa-tachometer-alt"></i> Dashboard</a>
                <a href="admin_manage_users.php" class="active"><i class="fas fa-users-cog"></i> Manage Users</a>
                <a href="admin_assignments.php"><i class="fas fa-tasks"></i> Manage Assignments</a>
                <a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <form method="post" style="display:inline;">
                    <button type="submit" name="logout" class="action-btn delete"><i class="fas fa-sign-out-alt"></i> Logout</button>
                </form>
            </nav>
        </div>
    </header>
    
    <div class="main">
        <?php if ($success_message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($success_message); ?></div>
        <?php endif; ?>
        
        <?php if ($error_message): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error_message); ?></div>
        <?php endif; ?>

        <!-- Pending Registrations Section -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-user-clock"></i> Pending Registrations</h2>
                <span class="count-badge"><?php echo count($pending_registrations); ?> pending</span>
            </div>
            
            <?php if (empty($pending_registrations)): ?>
                <p style="text-align: center; color: #666; padding: 2rem;">No pending registrations.</p>
            <?php else: ?>
        <table>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Course</th>
                        <th>Subject</th>
                        <th>Registered</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($pending_registrations as $reg): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($reg['name']); ?></td>
                        <td><?php echo htmlspecialchars($reg['username']); ?></td>
                        <td><?php echo htmlspecialchars($reg['email']); ?></td>
                        <td>
                            <span class="role-badge role-<?php echo $reg['role']; ?>">
                                <?php echo ucfirst($reg['role']); ?>
                            </span>
                        </td>
                        <td>
                            <span class="course-badge course-<?php echo strtolower($reg['course']); ?>">
                                <?php echo $reg['course']; ?>
                            </span>
                        </td>
                        <td><?php echo htmlspecialchars($reg['subject']); ?></td>
                        <td><?php echo date('M j, Y', strtotime($reg['created_at'])); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="registration_id" value="<?php echo $reg['id']; ?>">
                                <button class="action-btn" name="approve_registration" onclick="return confirm('Approve this registration?')">
                                    <i class="fas fa-check"></i> Approve
                                </button>
                                <button class="action-btn delete" name="reject_registration" onclick="return confirm('Reject this registration?')">
                                    <i class="fas fa-times"></i> Reject
                                </button>
                            </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
            <?php endif; ?>
        </div>

        <!-- BCA Course Section -->
        <div class="course-section">
            <div class="course-header">
                <span><i class="fas fa-laptop-code"></i> BCA Course</span>
                <span class="count"><?php echo count($bca_teachers) + count($bca_students); ?> users</span>
            </div>
            <div class="tabs">
                <div class="tab active" data-tab="bca-teachers">Teachers (<?php echo count($bca_teachers); ?>)</div>
                <div class="tab" data-tab="bca-students">Students (<?php echo count($bca_students); ?>)</div>
            </div>
            
            <!-- BCA Teachers Tab -->
            <div id="bca-teachers" class="tab-content active">
                <?php if (empty($bca_teachers)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">No BCA teachers found.</p>
                <?php else: ?>
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($bca_teachers as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['subject'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="status-<?php echo $user['status']; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                
                                <!-- Status Toggle -->
                                <?php if ($user['status'] === 'active'): ?>
                                    <button class="action-btn warning" name="update_status" value="inactive" onclick="return confirm('Deactivate this user?')">
                                        <i class="fas fa-pause"></i> Deactivate
                                    </button>
                                <?php else: ?>
                                    <button class="action-btn" name="update_status" value="active" onclick="return confirm('Activate this user?')">
                                        <i class="fas fa-play"></i> Activate
                                    </button>
                                <?php endif; ?>
                                
                                <!-- Delete User -->
                                <button class="action-btn delete" name="delete_user" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- BCA Students Tab -->
            <div id="bca-students" class="tab-content">
                <?php if (empty($bca_students)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">No BCA students found.</p>
                <?php else: ?>
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($bca_students as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['subject'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="status-<?php echo $user['status']; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                
                                <!-- Status Toggle -->
                                <?php if ($user['status'] === 'active'): ?>
                                    <button class="action-btn warning" name="update_status" value="inactive" onclick="return confirm('Deactivate this user?')">
                                        <i class="fas fa-pause"></i> Deactivate
                                    </button>
                                <?php else: ?>
                                    <button class="action-btn" name="update_status" value="active" onclick="return confirm('Activate this user?')">
                                        <i class="fas fa-play"></i> Activate
                                    </button>
                                <?php endif; ?>
                                
                                <!-- Delete User -->
                                <button class="action-btn delete" name="delete_user" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            </div>
        </div>
        
        <!-- BCom Course Section -->
        <div class="course-section">
            <div class="course-header" style="background: linear-gradient(135deg, #ff6b6b, #ee5a24);">
                <span><i class="fas fa-chart-line"></i> BCom Course</span>
                <span class="count"><?php echo count($bcom_teachers) + count($bcom_students); ?> users</span>
            </div>
            <div class="tabs">
                <div class="tab active" data-tab="bcom-teachers">Teachers (<?php echo count($bcom_teachers); ?>)</div>
                <div class="tab" data-tab="bcom-students">Students (<?php echo count($bcom_students); ?>)</div>
            </div>
            
            <!-- BCom Teachers Tab -->
            <div id="bcom-teachers" class="tab-content active">
                <?php if (empty($bcom_teachers)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">No BCom teachers found.</p>
                <?php else: ?>
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($bcom_teachers as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['subject'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="status-<?php echo $user['status']; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                
                                <!-- Status Toggle -->
                                <?php if ($user['status'] === 'active'): ?>
                                    <button class="action-btn warning" name="update_status" value="inactive" onclick="return confirm('Deactivate this user?')">
                                        <i class="fas fa-pause"></i> Deactivate
                                    </button>
                                <?php else: ?>
                                    <button class="action-btn" name="update_status" value="active" onclick="return confirm('Activate this user?')">
                                        <i class="fas fa-play"></i> Activate
                                    </button>
                                <?php endif; ?>
                                
                                <!-- Delete User -->
                                <button class="action-btn delete" name="delete_user" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            </div>
            
            <!-- BCom Students Tab -->
            <div id="bcom-students" class="tab-content">
                <?php if (empty($bcom_students)): ?>
                    <p style="text-align: center; color: #666; padding: 2rem;">No BCom students found.</p>
                <?php else: ?>
                <table>
                    <tr>
                        <th>Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Subject</th>
                        <th>Status</th>
                        <th>Joined</th>
                        <th>Actions</th>
                    </tr>
                    <?php foreach ($bcom_students as $user): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($user['name']); ?></td>
                        <td><?php echo htmlspecialchars($user['username']); ?></td>
                        <td><?php echo htmlspecialchars($user['email']); ?></td>
                        <td><?php echo htmlspecialchars($user['subject'] ?? 'N/A'); ?></td>
                        <td>
                            <span class="status-<?php echo $user['status']; ?>">
                                <?php echo ucfirst($user['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('M j, Y', strtotime($user['created_at'])); ?></td>
                        <td>
                            <form method="post" style="display:inline;">
                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                
                                <!-- Status Toggle -->
                                <?php if ($user['status'] === 'active'): ?>
                                    <button class="action-btn warning" name="update_status" value="inactive" onclick="return confirm('Deactivate this user?')">
                                        <i class="fas fa-pause"></i> Deactivate
                                    </button>
                                <?php else: ?>
                                    <button class="action-btn" name="update_status" value="active" onclick="return confirm('Activate this user?')">
                                        <i class="fas fa-play"></i> Activate
                                    </button>
                                <?php endif; ?>
                                
                                <!-- Delete User -->
                                <button class="action-btn delete" name="delete_user" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                                    <i class="fas fa-trash"></i> Delete
                                </button>
                            </form>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </table>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        // Simple direct tab switching functionality
        function showTab(tabId) {
            // Hide all tab contents first
            document.querySelectorAll('.tab-content').forEach(content => {
                content.classList.remove('active');
            });
            
            // Show the selected tab content
            document.getElementById(tabId).classList.add('active');
            
            // Remove active class from all tabs
            document.querySelectorAll('.tab').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Add active class to the clicked tab using data-tab attribute
            document.querySelector(`.tab[data-tab="${tabId}"]`).classList.add('active');
        }
        
        // Add direct event listeners to all tabs when the page loads
        document.addEventListener('DOMContentLoaded', function() {
            // Add click listeners to all tabs using data-tab attribute
            document.querySelectorAll('.tab').forEach(tab => {
                tab.addEventListener('click', function() {
                    const tabId = this.getAttribute('data-tab');
                    if (tabId) {
                        showTab(tabId);
                    }
                });
            });
            
            // Initialize the tabs (show the active ones)
            document.querySelectorAll('.course-section').forEach(section => {
                const activeTab = section.querySelector('.tab.active');
                if (activeTab) {
                    const tabId = activeTab.getAttribute('data-tab');
                    if (tabId) {
                        showTab(tabId);
                    }
                }
            });
        });
        
        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => {
                alert.style.display = 'none';
            });
        }, 5000);
    </script>
</body>
</html>

<?php
// Handle logout
if (isset($_POST['logout'])) {
    $auth->logout();
    header("Location: admin_login.php");
    exit();
}
?>