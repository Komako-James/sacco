<?php
require_once __DIR__ . '/../config/session_config.php';
session_start();
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/functions.php';

class Auth {
    private $db;
    
    public function __construct() {
        $this->db = getDB();
    }
    
    public function login($username, $password) {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
        $stmt->execute([$username]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password_hash'])) {
            $_SESSION['user_id'] = $user['user_id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['full_name'] = $user['full_name'];
            $_SESSION['role'] = $user['role'];
            $_SESSION['branch_id'] = $user['branch_id'];
            
            // Update last login
            $update = $this->db->prepare("UPDATE users SET last_login = NOW() WHERE user_id = ?");
            $update->execute([$user['user_id']]);
            
            // Log the login
            $this->logActivity($user['user_id'], 'Login', 'users', $user['user_id'], null, null);
            
            return true;
        }
        return false;
    }
    
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'Logout', 'users', $_SESSION['user_id'], null, null);
        }
        session_destroy();
        return true;
    }
    
    public function isLoggedIn() {
        return isset($_SESSION['user_id']);
    }
    
    public function hasRole($roles) {
        if (!is_array($roles)) {
            $roles = [$roles];
        }
        return in_array($_SESSION['role'], $roles);
    }
    
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            header('Location: /SACCO/login.php');
            exit();
        }
    }
    
    public function requireRole($roles) {
        $this->requireLogin();
        if (!$this->hasRole($roles)) {
            die("Access denied. Insufficient permissions.");
        }
    }
    
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }
        
        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }
    
    private function logActivity($userId, $action, $table, $recordId, $oldData, $newData) {
        $stmt = $this->db->prepare("
            INSERT INTO audit_logs (user_id, action, table_name, record_id, old_data, new_data, ip_address, user_agent)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;
        
        $stmt->execute([
            $userId, $action, $table, $recordId, 
            $oldData ? json_encode($oldData) : null,
            $newData ? json_encode($newData) : null,
            $ip, $userAgent
        ]);
    }
}

// Initialize Auth
$auth = new Auth();

// Check if user is logged in for all pages except login
$public_pages = ['login.php', 'api/mobile_money.php'];
$current_page = basename($_SERVER['PHP_SELF']);
if (!in_array($current_page, $public_pages)) {
    $auth->requireLogin();
}
?>
