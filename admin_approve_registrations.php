<?php
require_once 'includes/auth.php';

// Require admin role
$auth->requireRole('admin');

$message = '';
$error = '';

// Handle approval/rejection
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve'])) {
        $registration_id = $_POST['registration_id'];
        $result = $auth->approveRegistration($registration_id);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    } elseif (isset($_POST['reject'])) {
        $registration_id = $_POST['registration_id'];
        $result = $auth->rejectRegistration($registration_id);
        if ($result['success']) {
            $message = $result['message'];
        } else {
            $error = $result['message'];
        }
    }
}

// Get all pending registrations
try {
    $stmt = $pdo->prepare("SELECT * FROM pending_registrations ORDER BY created_at DESC");
    $stmt->execute();
    $pending_registrations = $stmt->fetchAll();
} catch (PDOException $e) {
    $error = 'Database error: ' . $e->getMessage();
    $pending_registrations = [];
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Approve Registrations - Admin Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            margin: 0;
            padding: 20px;
            min-height: 100vh;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 20px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            margin: 0;
            font-size: 2.5rem;
            font-weight: 700;
        }
        
        .header p {
            margin: 10px 0 0 0;
            opacity: 0.9;
            font-size: 1.1rem;
        }
        
        .content {
            padding: 30px;
        }
        
        .message {
            padding: 15px;
            border-radius: 10px;
            margin-bottom: 20px;
            font-weight: 500;
        }
        
        .message.success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .message.error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: linear-gradient(135deg, #667eea, #764ba2);
            color: white;
            padding: 20px;
            border-radius: 15px;
            text-align: center;
        }
        
        .stat-card h3 {
            margin: 0;
            font-size: 2rem;
            font-weight: 700;
        }
        
        .stat-card p {
            margin: 5px 0 0 0;
            opacity: 0.9;
        }
        
        .registrations-table {
            width: 100%;
            border-collapse: collapse;
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.1);
        }
        
        .registrations-table th,
        .registrations-table td {
            padding: 15px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        
        .registrations-table th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        
        .registrations-table tr:hover {
            background: #f8f9fa;
        }
        
        .btn {
            padding: 8px 16px;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-weight: 500;
            text-decoration: none;
            display: inline-block;
            margin: 2px;
            transition: all 0.3s ease;
        }
        
        .btn-approve {
            background: #28a745;
            color: white;
        }
        
        .btn-approve:hover {
            background: #218838;
            transform: translateY(-2px);
        }
        
        .btn-reject {
            background: #dc3545;
            color: white;
        }
        
        .btn-reject:hover {
            background: #c82333;
            transform: translateY(-2px);
        }
        
        .btn-back {
            background: #6c757d;
            color: white;
            margin-bottom: 20px;
        }
        
        .btn-back:hover {
            background: #5a6268;
        }
        
        .role-badge {
            padding: 4px 8px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 500;
        }
        
        .role-student {
            background: #e3f2fd;
            color: #1976d2;
        }
        
        .role-teacher {
            background: #f3e5f5;
            color: #7b1fa2;
        }
        
        .empty-state {
            text-align: center;
            padding: 60px 20px;
            color: #666;
        }
        
        .empty-state i {
            font-size: 4rem;
            color: #ddd;
            margin-bottom: 20px;
        }
        
        .empty-state h3 {
            margin: 0 0 10px 0;
            color: #333;
        }
        
        .empty-state p {
            margin: 0;
            font-size: 1.1rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-user-check"></i> Approve Registrations</h1>
            <p>Review and approve pending user registrations</p>
        </div>
        
        <div class="content">
            <a href="admin_dashboard.php" class="btn btn-back">
                <i class="fas fa-arrow-left"></i> Back to Dashboard
            </a>
            
            <?php if ($message): ?>
                <div class="message success">
                    <i class="fas fa-check-circle"></i> <?php echo htmlspecialchars($message); ?>
                </div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="message error">
                    <i class="fas fa-exclamation-circle"></i> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php endif; ?>
            
            <div class="stats">
                <div class="stat-card">
                    <h3><?php echo count($pending_registrations); ?></h3>
                    <p>Pending Registrations</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count(array_filter($pending_registrations, function($r) { return $r['role'] === 'student'; })); ?></h3>
                    <p>Student Requests</p>
                </div>
                <div class="stat-card">
                    <h3><?php echo count(array_filter($pending_registrations, function($r) { return $r['role'] === 'teacher'; })); ?></h3>
                    <p>Teacher Requests</p>
                </div>
            </div>
            
            <?php if (empty($pending_registrations)): ?>
                <div class="empty-state">
                    <i class="fas fa-inbox"></i>
                    <h3>No Pending Registrations</h3>
                    <p>All registration requests have been processed.</p>
                </div>
            <?php else: ?>
                <table class="registrations-table">
                    <thead>
                        <tr>
                            <th>Name</th>
                <th>Username</th>
                <th>Email</th>
                <th>Role</th>
                <th>Subject</th>
                <th>Course</th>
                <th>Date</th>
                <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($pending_registrations as $registration): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($registration['name']); ?></td>
                                <td><?php echo htmlspecialchars($registration['username']); ?></td>
                                <td><?php echo htmlspecialchars($registration['email']); ?></td>
                                <td>
                                    <span class="role-badge role-<?php echo $registration['role']; ?>">
                                        <?php echo ucfirst($registration['role']); ?>
                                    </span>
                                </td>
                                <td><?php echo htmlspecialchars($registration['subject']); ?></td>
                                <td><?php echo htmlspecialchars($registration['course']); ?></td>
                                <td><?php echo date('M j, Y g:i A', strtotime($registration['created_at'])); ?></td>
                                <td>
                                    <form method="post" style="display: inline;">
                                        <input type="hidden" name="registration_id" value="<?php echo $registration['id']; ?>">
                                        <button type="submit" name="approve" class="btn btn-approve" onclick="return confirm('Approve this registration?')">
                                            <i class="fas fa-check"></i> Approve
                                        </button>
                                        <button type="submit" name="reject" class="btn btn-reject" onclick="return confirm('Reject this registration?')">
                                            <i class="fas fa-times"></i> Reject
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>