<?php
require_once 'includes/auth.php';
require_once 'includes/assignment_helper.php';
require_once 'includes/notification_system.php';

// Require admin role
$auth->requireRole('admin');

$success_message = '';
$error_message = '';

// Initialize notification system once per request
$notificationSystem = new NotificationSystem($pdo);

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Add new assignment
    if (isset($_POST['add_assignment'])) {
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $subject = trim($_POST['subject']);
        $teacher_id = intval($_POST['teacher_id']);
        $due_date = $_POST['due_date'];
        $max_score = intval($_POST['max_score']);
        $assignment_period_start = $_POST['assignment_period_start'];
        $assignment_period_end = $_POST['assignment_period_end'];
        
        // Validation
        if (empty($title) || empty($description) || empty($subject) || empty($due_date)) {
            $error_message = "Please fill in all required fields.";
        } elseif (strtotime($due_date) <= time()) {
            $error_message = "Due date must be in the future.";
        } elseif (strtotime($assignment_period_end) <= strtotime($assignment_period_start)) {
            $error_message = "Assignment period end must be after start date.";
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO assignments (title, description, subject, teacher_id, due_date, max_score, assignment_period_start, assignment_period_end) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$title, $description, $subject, $teacher_id, $due_date, $max_score, $assignment_period_start, $assignment_period_end]);
                
                $assignment_id = $pdo->lastInsertId();
                
                // Distribute assignment to students
                $distribution_result = $assignmentHelper->distributeAssignment($assignment_id);
                
                // Log activity
                $auth->logActivity($_SESSION['user_id'], 'add_assignment', "Added assignment: {$title} for subject: {$subject}");
                
                if ($distribution_result['success']) {
                    $success_message = "Assignment added successfully! " . $distribution_result['message'];
                } else {
                    $success_message = "Assignment added successfully! Note: " . $distribution_result['message'];
                }

                if ($teacher_id > 0) {
                    try {
                        $teacherStmt = $pdo->prepare("SELECT name FROM users WHERE id = ?");
                        $teacherStmt->execute([$teacher_id]);
                        $teacher = $teacherStmt->fetch();

                        if ($teacher && !empty($teacher['name'])) {
                            $notificationSystem->notifyNewAssignment($assignment_id, $title, $teacher['name'], $due_date);
                            $notificationSystem->notifyTeacherAssignmentCreated($teacher_id, $title, $subject, $assignment_id);
                        }
                    } catch (PDOException $notificationError) {
                        error_log('Error sending assignment notifications: ' . $notificationError->getMessage());
                    }
                }
            } catch (PDOException $e) {
                $error_message = "Error adding assignment: " . $e->getMessage();
            }
        }
    }
    
    // Edit assignment
    if (isset($_POST['edit_assignment'])) {
        $assignment_id = intval($_POST['assignment_id']);
        $title = trim($_POST['title']);
        $description = trim($_POST['description']);
        $subject = trim($_POST['subject']);
        $teacher_id = intval($_POST['teacher_id']);
        $due_date = $_POST['due_date'];
        $max_score = intval($_POST['max_score']);
        $assignment_period_start = $_POST['assignment_period_start'];
        $assignment_period_end = $_POST['assignment_period_end'];
        $status = $_POST['status'];

        if (empty($title) || empty($description) || empty($subject) || empty($due_date)) {
            $error_message = "Please fill in all required fields.";
        } else {
            try {
                $stmt = $pdo->prepare("UPDATE assignments SET title = ?, description = ?, subject = ?, teacher_id = ?, due_date = ?, max_score = ?, assignment_period_start = ?, assignment_period_end = ?, status = ? WHERE id = ?");
                $stmt->execute([$title, $description, $subject, $teacher_id, $due_date, $max_score, $assignment_period_start, $assignment_period_end, $status, $assignment_id]);

                if ($stmt->rowCount() > 0) {
                    $success_message = "Assignment updated successfully!";
                } else {
                    $error_message = "No changes were made to the assignment.";
                }
            } catch (PDOException $e) {
                $error_message = "Error updating assignment: " . $e->getMessage();
            }
        }
    }
    
    // Delete assignment
    if (isset($_POST['delete_assignment'])) {
        $assignment_id = intval($_POST['delete_assignment']);
        try {
            $stmt = $pdo->prepare("DELETE FROM assignments WHERE id = ?");
            $stmt->execute([$assignment_id]);

            if ($stmt->rowCount() > 0) {
                $success_message = "Assignment deleted successfully!";
            } else {
                $error_message = "Assignment not found or already deleted.";
            }
        } catch (PDOException $e) {
            $error_message = "Error deleting assignment: " . $e->getMessage();
        }
    }
    
    // Handle logout
    if (isset($_POST['logout'])) {
        $auth->logout();
        header("Location: admin_login.php");
        exit();
    }
}

// Automatically set assignments to inactive if their end date has passed
try {
    $stmt = $pdo->prepare("UPDATE assignments SET status = 'inactive' WHERE assignment_period_end < NOW() AND status = 'active'");
    $stmt->execute();
} catch (PDOException $e) {
    $error_message = "Error updating assignment statuses: " . $e->getMessage();
}

// Get assignments from database
try {
    $stmt = $pdo->prepare("
        SELECT a.*, u.name as teacher_name 
        FROM assignments a 
        LEFT JOIN users u ON a.teacher_id = u.id 
        ORDER BY a.created_at DESC
    ");
    $stmt->execute();
    $assignments = $stmt->fetchAll();
    
    // Get teachers for dropdown
    $stmt = $pdo->prepare("SELECT id, name, subject FROM users WHERE role = 'teacher' AND status = 'active' ORDER BY name");
    $stmt->execute();
    $teachers = $stmt->fetchAll();
    
} catch (PDOException $e) {
    $error_message = "Database error: " . $e->getMessage();
    $assignments = [];
    $teachers = [];
}

$departmentSource = [];
foreach ($teachers as $teacher) {
    $subject = trim((string)($teacher['subject'] ?? ''));
    if ($subject !== '') {
        $departmentSource[] = $subject;
    }
}
foreach ($assignments as $assignment) {
    $subject = trim((string)($assignment['subject'] ?? ''));
    if ($subject !== '') {
        $departmentSource[] = $subject;
    }
}
$departments = array_values(array_unique($departmentSource));
sort($departments, SORT_NATURAL | SORT_FLAG_CASE);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Assignments - EduAssign Admin</title>
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
        .section {
            margin-bottom: 3rem;
        }
        .section-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1rem;
        }
        .btn {
            background: #667eea;
            color: #fff;
            border: none;
            border-radius: 8px;
            padding: 0.8rem 1.5rem;
            cursor: pointer;
            font-size: 0.9rem;
            font-weight: 600;
            text-decoration: none;
            display: inline-block;
            transition: all 0.3s;
        }
        .btn:hover {
            background: #5a6fd8;
            transform: translateY(-2px);
        }
        .btn.delete {
            background: #e74c3c;
        }
        .btn.delete:hover {
            background: #c0392b;
        }
        .btn.edit {
            background: #f39c12;
        }
        .btn.edit:hover {
            background: #e67e22;
        }
        .btn.add {
            background: #27ae60;
        }
        .btn.add:hover {
            background: #229954;
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
        .status-active {
            color: #27ae60;
            font-weight: 600;
        }
        .status-inactive {
            color: #e74c3c;
            font-weight: 600;
        }
        .modal {
            display: none;
            position: fixed;
            z-index: 1001;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
        }
        .modal-content {
            background-color: #fefefe;
            margin: 5% auto;
            padding: 2rem;
            border-radius: 15px;
            width: 90%;
            max-width: 600px;
            max-height: 80vh;
            overflow-y: auto;
        }
        .close {
            color: #aaa;
            float: right;
            font-size: 28px;
            font-weight: bold;
            cursor: pointer;
        }
        .close:hover {
            color: #000;
        }
        .form-group {
            margin-bottom: 1rem;
        }
        .form-group label {
            display: block;
            margin-bottom: 0.5rem;
            font-weight: 600;
            color: #333;
        }
        .form-control {
            width: 100%;
            padding: 0.8rem;
            border: 2px solid #e1e5e9;
            border-radius: 8px;
            font-size: 1rem;
            transition: border-color 0.3s;
        }
        .form-control:focus {
            outline: none;
            border-color: #667eea;
        }
        .form-row {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
        }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 2rem;
        }
        .stat-card {
            background: #fff;
            padding: 1.5rem;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            text-align: center;
        }
        .stat-card h3 {
            font-size: 2rem;
            color: #667eea;
            margin: 0 0 0.5rem 0;
        }
        .stat-card p {
            color: #666;
            margin: 0;
        }
        @media (max-width: 900px) { 
            .main { 
                padding: 1rem; 
            } 
            table, th, td { 
                font-size: 0.95rem; 
            }
            .form-row {
                grid-template-columns: 1fr;
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
                <a href="admin_manage_users.php"><i class="fas fa-users-cog"></i> Manage Users</a>
                <a href="admin_assignments.php" class="active"><i class="fas fa-tasks"></i> Manage Assignments</a>
                <a href="admin_reports.php"><i class="fas fa-chart-bar"></i> Reports</a>
                <form method="post" style="display:inline;">
                    <button type="submit" name="logout" class="btn delete"><i class="fas fa-sign-out-alt"></i> Logout</button>
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

        <!-- Statistics -->
        <div class="stats-grid">
            <div class="stat-card">
                <h3><?php echo count($assignments); ?></h3>
                <p>Total Assignments</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($assignments, function($a) { return $a['status'] === 'active'; })); ?></h3>
                <p>Active Assignments</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count(array_filter($assignments, function($a) { return strtotime($a['due_date']) > time(); })); ?></h3>
                <p>Upcoming Deadlines</p>
            </div>
            <div class="stat-card">
                <h3><?php echo count($teachers); ?></h3>
                <p>Available Teachers</p>
            </div>
        </div>

        <!-- Add Assignment Section -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-plus-circle"></i> Add New Assignment</h2>
                <button class="btn add" onclick="openModal('addModal')">
                    <i class="fas fa-plus"></i> Add Assignment
                </button>
            </div>
        </div>

        <!-- Assignments Table -->
        <div class="section">
            <div class="section-header">
                <h2><i class="fas fa-tasks"></i> All Assignments</h2>
            </div>
            
            <?php if (empty($assignments)): ?>
                <p style="text-align: center; color: #666; padding: 2rem;">No assignments found.</p>
            <?php else: ?>
        <table>
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Description</th>
                    <th>Subject</th>
                    <th>Teacher</th>
                    <th>Due Date</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($assignments as $assignment): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($assignment['title']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['description']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['subject']); ?></td>
                        <td><?php echo htmlspecialchars($assignment['teacher_name']); ?></td>
                        <td><?php echo date('F j, Y', strtotime($assignment['due_date'])); ?></td>
                        <td>
                            <?php if ($assignment['status'] === 'active'): ?>
                                <span class="status-active">Active</span>
                            <?php else: ?>
                                <span class="status-inactive">Inactive</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <button class="btn edit" onclick="editAssignment(<?php echo htmlspecialchars(json_encode($assignment)); ?>)">Edit</button>
                            <button class="btn delete" onclick="deleteAssignment(<?php echo $assignment['id']; ?>, '<?php echo htmlspecialchars($assignment['title']); ?>')">Delete</button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Add Assignment Modal -->
    <div id="addModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('addModal')">&times;</span>
            <h2><i class="fas fa-plus-circle"></i> Add New Assignment</h2>
            
            <form method="post">
                <div class="form-group">
                    <label for="title">Assignment Title *</label>
                    <input type="text" id="title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="description">Description *</label>
                    <textarea id="description" name="description" class="form-control" rows="4" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="subject">Subject/Department *</label>
                        <?php if (!empty($departments)): ?>
                        <select id="subject" name="subject" class="form-control" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo htmlspecialchars($department); ?>">
                                    <?php echo htmlspecialchars($department); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="text" id="subject" name="subject" class="form-control" required>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="teacher_id">Assigned Teacher</label>
                        <select id="teacher_id" name="teacher_id" class="form-control">
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>" data-subject="<?php echo htmlspecialchars(trim((string)$teacher['subject'])); ?>">
                                    <?php echo htmlspecialchars($teacher['name']); ?> (<?php echo htmlspecialchars($teacher['subject']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="due_date">Due Date *</label>
                        <input type="datetime-local" id="due_date" name="due_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="max_score">Maximum Score</label>
                        <input type="number" id="max_score" name="max_score" class="form-control" value="100" min="1" max="1000">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="assignment_period_start">Assignment Period Start *</label>
                        <input type="datetime-local" id="assignment_period_start" name="assignment_period_start" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="assignment_period_end">Assignment Period End *</label>
                        <input type="datetime-local" id="assignment_period_end" name="assignment_period_end" class="form-control" required>
                    </div>
                </div>
                
                <div style="text-align: right; margin-top: 1rem;">
                    <button type="button" class="btn" onclick="closeModal('addModal')">Cancel</button>
                    <button type="submit" name="add_assignment" class="btn add">Add Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit Assignment Modal -->
    <div id="editModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('editModal')">&times;</span>
            <h2><i class="fas fa-edit"></i> Edit Assignment</h2>
            
            <form method="post">
                <input type="hidden" id="edit_assignment_id" name="assignment_id">
                
                <div class="form-group">
                    <label for="edit_title">Assignment Title *</label>
                    <input type="text" id="edit_title" name="title" class="form-control" required>
                </div>
                
                <div class="form-group">
                    <label for="edit_description">Description *</label>
                    <textarea id="edit_description" name="description" class="form-control" rows="4" required></textarea>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_subject">Subject/Department *</label>
                        <?php if (!empty($departments)): ?>
                        <select id="edit_subject" name="subject" class="form-control" required>
                            <option value="">Select Department</option>
                            <?php foreach ($departments as $department): ?>
                                <option value="<?php echo htmlspecialchars($department); ?>">
                                    <?php echo htmlspecialchars($department); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <?php else: ?>
                        <input type="text" id="edit_subject" name="subject" class="form-control" required>
                        <?php endif; ?>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_teacher_id">Assigned Teacher</label>
                        <select id="edit_teacher_id" name="teacher_id" class="form-control">
                            <option value="">Select Teacher</option>
                            <?php foreach ($teachers as $teacher): ?>
                                <option value="<?php echo $teacher['id']; ?>" data-subject="<?php echo htmlspecialchars(trim((string)$teacher['subject'])); ?>">
                                    <?php echo htmlspecialchars($teacher['name']); ?> (<?php echo htmlspecialchars($teacher['subject']); ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_due_date">Due Date *</label>
                        <input type="datetime-local" id="edit_due_date" name="due_date" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_max_score">Maximum Score</label>
                        <input type="number" id="edit_max_score" name="max_score" class="form-control" min="1" max="1000">
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="edit_assignment_period_start">Assignment Period Start *</label>
                        <input type="datetime-local" id="edit_assignment_period_start" name="assignment_period_start" class="form-control" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="edit_assignment_period_end">Assignment Period End *</label>
                        <input type="datetime-local" id="edit_assignment_period_end" name="assignment_period_end" class="form-control" required>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="edit_status">Status</label>
                    <select id="edit_status" name="status" class="form-control">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>
                
                <div style="text-align: right; margin-top: 1rem;">
                    <button type="button" class="btn" onclick="closeModal('editModal')">Cancel</button>
                    <button type="submit" name="edit_assignment" class="btn edit">Update Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div id="deleteModal" class="modal">
        <div class="modal-content">
            <span class="close" onclick="closeModal('deleteModal')">&times;</span>
            <h2><i class="fas fa-exclamation-triangle"></i> Confirm Delete</h2>
            <p>Are you sure you want to delete the assignment "<span id="deleteAssignmentTitle"></span>"?</p>
            <p><strong>Warning:</strong> This action cannot be undone and will also delete all related submissions.</p>
            
            <form method="post">
                <input type="hidden" id="delete_assignment_id" name="assignment_id">
                <div style="text-align: right; margin-top: 1rem;">
                    <button type="button" class="btn" onclick="closeModal('deleteModal')">Cancel</button>
                    <button type="submit" name="delete_assignment" class="btn delete">Delete Assignment</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function openModal(modalId) {
            document.getElementById(modalId).style.display = 'block';
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        function filterTeachersByDepartment(department, selectElement, selectedTeacherId = '') {
            if (!selectElement || typeof selectElement.options === 'undefined') {
                return;
            }

            const desiredValue = selectedTeacherId !== null && selectedTeacherId !== undefined && selectedTeacherId !== ''
                ? String(selectedTeacherId)
                : '';
            let desiredOptionVisible = false;

            Array.from(selectElement.options).forEach(option => {
                const optionDepartment = option.dataset.subject || '';

                if (!option.dataset.subject) {
                    option.hidden = false;
                    option.disabled = false;
                    return;
                }

                const matches = Boolean(department) && optionDepartment === department;
                option.hidden = !matches;
                option.disabled = !matches;

                if (matches && desiredValue && option.value === desiredValue) {
                    desiredOptionVisible = true;
                }
            });

            if (!department) {
                selectElement.value = '';
                return;
            }

            if (desiredValue && desiredOptionVisible) {
                selectElement.value = desiredValue;
                return;
            }

            if (selectElement.value && selectElement.selectedOptions.length && selectElement.selectedOptions[0].disabled) {
                selectElement.value = '';
            }
        }

        function ensureDepartmentOption(selectElement, value) {
            if (!selectElement || !value || typeof selectElement.options === 'undefined') {
                return;
            }

            const exists = Array.from(selectElement.options).some(option => option.value === value);
            if (!exists) {
                const option = new Option(value, value);
                selectElement.add(option);
            }
        }

        document.addEventListener('DOMContentLoaded', function() {
            const addDepartmentSelect = document.getElementById('subject');
            const addTeacherSelect = document.getElementById('teacher_id');
            const editDepartmentSelect = document.getElementById('edit_subject');
            const editTeacherSelect = document.getElementById('edit_teacher_id');

            if (addDepartmentSelect && addTeacherSelect && addDepartmentSelect.tagName === 'SELECT') {
                filterTeachersByDepartment(addDepartmentSelect.value, addTeacherSelect);
                addDepartmentSelect.addEventListener('change', function() {
                    filterTeachersByDepartment(this.value, addTeacherSelect);
                    addTeacherSelect.value = '';
                });
            }

            if (editDepartmentSelect && editTeacherSelect && editDepartmentSelect.tagName === 'SELECT') {
                filterTeachersByDepartment(editDepartmentSelect.value, editTeacherSelect);
                editDepartmentSelect.addEventListener('change', function() {
                    filterTeachersByDepartment(this.value, editTeacherSelect);
                    editTeacherSelect.value = '';
                });
            }
        });

        function editAssignment(assignment) {
            const editModal = document.getElementById('editModal');

            editModal.querySelector('[name="assignment_id"]').value = assignment.id;
            editModal.querySelector('[name="title"]').value = assignment.title;
            editModal.querySelector('[name="description"]').value = assignment.description;

            const subjectField = editModal.querySelector('[name="subject"]');
            const teacherField = editModal.querySelector('[name="teacher_id"]');
            const subjectValue = (assignment.subject || '').trim();

            if (subjectField) {
                if (subjectField.tagName === 'SELECT') {
                    ensureDepartmentOption(subjectField, subjectValue);
                    subjectField.value = subjectValue;
                    filterTeachersByDepartment(subjectField.value, teacherField, assignment.teacher_id ?? '');
                } else {
                    subjectField.value = subjectValue;
                }
            }

            if (teacherField && subjectField && subjectField.tagName !== 'SELECT') {
                teacherField.value = assignment.teacher_id ? String(assignment.teacher_id) : '';
            }

            editModal.querySelector('[name="due_date"]').value = assignment.due_date;
            editModal.querySelector('[name="max_score"]').value = assignment.max_score;
            editModal.querySelector('[name="assignment_period_start"]').value = assignment.assignment_period_start;
            editModal.querySelector('[name="assignment_period_end"]').value = assignment.assignment_period_end;
            editModal.querySelector('[name="status"]').value = assignment.status;

            openModal('editModal');
        }

        function deleteAssignment(assignmentId, assignmentTitle) {
            if (confirm(`Are you sure you want to delete the assignment "${assignmentTitle}"?`)) {
                // Submit the delete form
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = '';

                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'delete_assignment';
                input.value = assignmentId;
                form.appendChild(input);

                document.body.appendChild(form);
                form.submit();
            }
        }

        // Close modal when clicking outside
        window.onclick = function(event) {
            const modals = document.querySelectorAll('.modal');
            modals.forEach(modal => {
                if (event.target === modal) {
                    modal.style.display = 'none';
                }
            });
        };

        // Auto-hide success/error messages after 5 seconds
        setTimeout(function() {
            const alerts = document.querySelectorAll('.alert');
            alerts.forEach(alert => alert.style.display = 'none');
        }, 5000);
    </script>
</body>
</html>