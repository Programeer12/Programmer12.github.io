<?php
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}
require_once dirname(__DIR__) . '/db.php';

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    public function login($username, $password) {
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password'])) {
                // Set session variables
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['name'] = $user['name'];
                $_SESSION['email'] = $user['email'];
                $_SESSION['subject'] = $user['subject'];
                $_SESSION['course'] = $user['course'];
                
                // Log activity
                $this->logActivity($user['id'], 'login', 'User logged in successfully');
                
                return [
                    'success' => true,
                    'user' => $user
                ];
            } else {
                return [
                    'success' => false,
                    'message' => 'Invalid credentials'
                ];
            }
        } catch (PDOException $e) {
            return [
                'success' => false,
                'message' => 'Database error: ' . $e->getMessage()
            ];
        }
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'logout', 'User logged out');
        }
        session_destroy();
        return ['success' => true];
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function hasRole($role) {
        return $this->isLoggedIn() && $_SESSION['role'] === $role;
    }
    
    public function requireRole($role) {
        if (!$this->isLoggedIn()) {
            $this->redirectToLogin($role);
            exit();
        }
        
        if (!$this->hasRole($role)) {
            $this->redirectToLogin($role);
            exit();
        }
    }
    
    private function redirectToLogin($requiredRole) {
        switch ($requiredRole) {
            case 'admin':
                header("Location: admin_login.php");
                break;
            case 'teacher':
                header("Location: teacher_login.php");
                break;
            case 'student':
                header("Location: student_login.php");
                break;
            default:
                header("Location: index.php");
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        try {
            $stmt = $this->pdo->prepare("SELECT * FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            return null;
        }
    }
    
    public function approveRegistration($registration_id) {
        try {
            // Get the pending registration
            $stmt = $this->pdo->prepare("SELECT * FROM pending_registrations WHERE id = ?");
            $stmt->execute([$registration_id]);
            $pending = $stmt->fetch();
            
            if (!$pending) {
                return ['success' => false, 'message' => 'Registration not found'];
            }
            
            // Check if username or email already exists in users table
            $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$pending['username'], $pending['email']]);
            if ($stmt->fetchColumn() > 0) {
                return ['success' => false, 'message' => 'Username or email already exists'];
            }

            // Enforce subject exclusivity for teachers before approval
            if ($pending['role'] === 'teacher' && !empty($pending['subject'])) {
                $subject = trim((string)$pending['subject']);
                if ($subject !== '') {
                    $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM users WHERE role = 'teacher' AND status = 'active' AND LOWER(subject) = LOWER(?)");
                    $stmt->execute([$subject]);
                    if ($stmt->fetchColumn() > 0) {
                        return ['success' => false, 'message' => "Another teacher is already active for {$subject}. Please reassign or deactivate the existing teacher first."];
                    }
                }
            }
            
            // Insert into users table
            $stmt = $this->pdo->prepare("INSERT INTO users (username, password, role, name, email, subject, course) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $pending['username'],
                $pending['password'],
                $pending['role'],
                $pending['name'],
                $pending['email'],
                $pending['subject'],
                $pending['course']
            ]);
            
            // Delete from pending_registrations
            $stmt = $this->pdo->prepare("DELETE FROM pending_registrations WHERE id = ?");
            $stmt->execute([$registration_id]);
            
            // Log activity
            $this->logActivity($_SESSION['user_id'], 'approve_registration', "Approved registration for {$pending['name']} ({$pending['role']})");
            
            return ['success' => true, 'message' => 'Registration approved successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function rejectRegistration($registration_id) {
        try {
            // Get the pending registration
            $stmt = $this->pdo->prepare("SELECT * FROM pending_registrations WHERE id = ?");
            $stmt->execute([$registration_id]);
            $pending = $stmt->fetch();
            
            if (!$pending) {
                return ['success' => false, 'message' => 'Registration not found'];
            }
            
            // Delete from pending_registrations
            $stmt = $this->pdo->prepare("DELETE FROM pending_registrations WHERE id = ?");
            $stmt->execute([$registration_id]);
            
            // Log activity
            $this->logActivity($_SESSION['user_id'], 'reject_registration', "Rejected registration for {$pending['name']} ({$pending['role']})");
            
            return ['success' => true, 'message' => 'Registration rejected successfully'];
            
        } catch (PDOException $e) {
            return ['success' => false, 'message' => 'Database error: ' . $e->getMessage()];
        }
    }
    
    public function logActivity($user_id, $action, $description) {
        try {
            $stmt = $this->pdo->prepare("INSERT INTO activity_logs (user_id, action, description, ip_address, user_agent) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([
                $user_id,
                $action,
                $description,
                $_SERVER['REMOTE_ADDR'] ?? null,
                $_SERVER['HTTP_USER_AGENT'] ?? null
            ]);
        } catch (PDOException $e) {
            // Silently fail for logging errors
        }
    }
}

// Initialize the auth object
$auth = new Auth($pdo);
?>