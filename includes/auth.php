<?php
require_once __DIR__ . '/../config/session_config.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/functions.php';

class Auth {
    private $db;
    private $maxLoginAttempts = 5;
    private $lockoutDuration = 30; // minutes

    public function __construct() {
        try {
            $this->db = getDB();
        } catch (Exception $e) {
            error_log("Auth class database connection failed: " . $e->getMessage());
            die("Database connection failed");
        }
    }

    /**
     * Authenticate user login
     */
    public function login($username, $password) {
        try {
            // Validate input
            if (empty($username) || empty($password)) {
                return ['success' => false, 'message' => 'Username and password are required'];
            }

            // Get user from database
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch();

            if (!$user) {
                $this->logFailedLogin($username);
                return ['success' => false, 'message' => 'Invalid username or password'];
            }

            // Check if account is locked
            if ($this->isAccountLocked($user)) {
                $lockoutTime = ceil((strtotime($user['locked_until']) - time()) / 60);
                return ['success' => false, 'message' => "Account locked. Try again in {$lockoutTime} minutes"];
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->incrementFailedLogin($user);
                return ['success' => false, 'message' => 'Invalid username or password'];
            }

            // Reset login attempts and set session
            $this->resetLoginAttempts($user['user_id']);
            $this->setSession($user);

            // Update last login
            $this->updateLastLogin($user['user_id']);

            // Log successful login
            $this->logActivity($user['user_id'], 'Login', 'users', $user['user_id'], null, null);

            return ['success' => true, 'message' => 'Login successful'];

        } catch (Exception $e) {
            error_log("Login error: " . $e->getMessage());
            return ['success' => false, 'message' => 'An error occurred during login'];
        }
    }

    /**
     * Set user session data
     */
    private function setSession(array $user) {
        // Regenerate session ID for security
        session_regenerate_id(true);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['role'] = $user['role'];
        $_SESSION['branch_id'] = $user['branch_id'] ?? null;
        $_SESSION['last_activity'] = time();
        $_SESSION['expires_at'] = time() + SESSION_TIMEOUT;
        $_SESSION['ip_address'] = getClientIpAddress();
        $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    }

    /**
     * Check if account is locked due to failed login attempts
     */
    private function isAccountLocked($user) {
        return !empty($user['locked_until']) && strtotime($user['locked_until']) > time();
    }

    /**
     * Increment failed login attempts
     */
    private function incrementFailedLogin(array $user) {
        $attempts = ($user['login_attempts'] ?? 0) + 1;
        $lockedUntil = null;

        if ($attempts >= $this->maxLoginAttempts) {
            $lockedUntil = date('Y-m-d H:i:s', strtotime("+{$this->lockoutDuration} minutes"));
        }

        $stmt = $this->db->prepare("UPDATE users SET login_attempts = ?, locked_until = ?, last_failed_login = NOW() WHERE user_id = ?");
        $stmt->execute([$attempts, $lockedUntil, $user['user_id']]);

        $this->logActivity($user['user_id'], 'Login Failed', 'users', $user['user_id'], null, ['attempts' => $attempts]);
    }

    /**
     * Log failed login attempt with username only
     */
    private function logFailedLogin($username) {
        $stmt = $this->db->prepare("INSERT INTO security_logs (event_type, username, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, NOW())");
        $stmt->execute(['failed_login', $username, getClientIpAddress(), $_SERVER['HTTP_USER_AGENT'] ?? '']);
    }

    /**
     * Reset login attempts after successful login
     */
    private function resetLoginAttempts(int $userId) {
        $stmt = $this->db->prepare("UPDATE users SET login_attempts = 0, locked_until = NULL WHERE user_id = ?");
        $stmt->execute([$userId]);
    }

    /**
     * Update last login timestamp
     */
    private function updateLastLogin(int $userId) {
        $stmt = $this->db->prepare("UPDATE users SET last_login = NOW(), last_login_ip = ? WHERE user_id = ?");
        $stmt->execute([getClientIpAddress(), $userId]);
    }

    /**
     * User logout
     */
    public function logout() {
        if (isset($_SESSION['user_id'])) {
            $this->logActivity($_SESSION['user_id'], 'Logout', 'users', $_SESSION['user_id'], null, null);
        }

        // Clear session data
        $_SESSION = [];

        // Destroy session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy session
        session_destroy();

        return true;
    }

    /**
     * Check if user is logged in
     */
    public function isLoggedIn() {
        if (!isset($_SESSION['user_id']) || !isset($_SESSION['expires_at'])) {
            return false;
        }

        // Check if session has expired
        if ($_SESSION['expires_at'] < time()) {
            $this->logout();
            return false;
        }

        // Check for session hijacking
        if (!$this->validateSession()) {
            $this->logout();
            return false;
        }

        return true;
    }

    /**
     * Validate session for security
     */
    private function validateSession() {
        // Check IP address (optional - might cause issues with dynamic IPs)
        $checkIp = false; // Set to true if you want strict IP checking
        if ($checkIp && isset($_SESSION['ip_address'])) {
            if ($_SESSION['ip_address'] !== getClientIpAddress()) {
                return false;
            }
        }

        // Check user agent
        if (isset($_SESSION['user_agent'])) {
            if ($_SESSION['user_agent'] !== ($_SERVER['HTTP_USER_AGENT'] ?? '')) {
                return false;
            }
        }

        return true;
    }

    /**
     * Extend session timeout
     */
    public function extendSession() {
        if ($this->isLoggedIn()) {
            $_SESSION['expires_at'] = time() + SESSION_TIMEOUT;
            $_SESSION['last_activity'] = time();
            return true;
        }
        return false;
    }

    /**
     * Check if user has specific role(s)
     */
    public function hasRole($roles) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        return isset($_SESSION['role']) && in_array($_SESSION['role'], $roles, true);
    }

    /**
     * Check if user has specific permission
     */
    public function can($permission) {
        if (!$this->isLoggedIn()) {
            return false;
        }

        if (empty($_SESSION['role'])) {
            return false;
        }

        // Admin has all permissions
        if ($_SESSION['role'] === 'admin') {
            return true;
        }

        $role = $_SESSION['role'];
        $rolePermissions = [
            'manager' => [
                'members.view', 'members.create', 'members.edit', 'members.delete',
                'loans.view', 'loans.create', 'loans.edit', 'loans.approve', 'loans.reject',
                'savings.view', 'savings.create', 'savings.edit', 'savings.delete',
                'reports.view', 'reports.generate', 'reports.export',
                'users.view', 'users.create', 'users.edit',
                'settings.manage', 'audit.view'
            ],
            'accountant' => [
                'members.view', 'members.create', 'members.edit',
                'loans.view', 'loans.create', 'loans.edit',
                'savings.view', 'savings.create', 'savings.edit',
                'reports.view', 'reports.generate', 'reports.export',
                'audit.view'
            ],
            'loan_officer' => [
                'members.view', 'members.create', 'members.edit',
                'loans.view', 'loans.create', 'loans.edit', 'loans.approve',
                'savings.view',
                'reports.view'
            ],
            'cashier' => [
                'members.view',
                'loans.view', 'loans.repayment',
                'savings.view', 'savings.deposit', 'savings.withdraw',
                'reports.view'
            ],
            'teller' => [
                'members.view',
                'savings.view', 'savings.deposit', 'savings.withdraw',
                'reports.view'
            ],
            'audit' => [
                'members.view', 'loans.view', 'savings.view',
                'reports.view', 'reports.generate', 'reports.export',
                'audit.view', 'audit.export'
            ],
            'viewer' => [
                'members.view', 'loans.view', 'savings.view',
                'reports.view'
            ]
        ];

        return in_array($permission, $rolePermissions[$role] ?? [], true);
    }

    /**
     * Require user to be logged in
     */
    public function requireLogin() {
        if (!$this->isLoggedIn()) {
            // Store the current page to redirect after login
            $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '';

            // Redirect to login page using APP_URL constant
            $loginPath = (defined('APP_URL') ? APP_URL : '/sacco') . '/login.php';
            $loginUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $loginPath;
            header('Location: ' . $loginUrl);
            exit();
        }

        // Extend session on each request
        $this->extendSession();
    }

    /**
     * Require user to have specific role(s)
     */
    public function requireRole($roles) {
        $this->requireLogin();

        if (!$this->hasRole($roles)) {
            http_response_code(403);
            include __DIR__ . '/../403.php';
            exit();
        }
    }

    /**
     * Require user to have specific permission
     */
    public function requirePermission($permission) {
        $this->requireLogin();

        if (!$this->can($permission)) {
            http_response_code(403);
            include __DIR__ . '/../403.php';
            exit();
        }
    }

    /**
     * Get current logged-in user data
     */
    public function getCurrentUser() {
        if (!$this->isLoggedIn()) {
            return null;
        }

        $stmt = $this->db->prepare('SELECT user_id, username, email, full_name, role, branch_id, phone, status, created_at, last_login FROM users WHERE user_id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        return $stmt->fetch();
    }

    /**
     * Get user by ID
     */
    public function getUserById($userId) {
        $stmt = $this->db->prepare('SELECT user_id, username, email, full_name, role, branch_id, phone, status, created_at, last_login FROM users WHERE user_id = ?');
        $stmt->execute([$userId]);
        return $stmt->fetch();
    }

    /**
     * Change user password
     */
    public function changePassword($userId, $oldPassword, $newPassword) {
        // Get current user
        $stmt = $this->db->prepare('SELECT password_hash FROM users WHERE user_id = ?');
        $stmt->execute([$userId]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($oldPassword, $user['password_hash'])) {
            return ['success' => false, 'message' => 'Current password is incorrect'];
        }

        // Validate new password strength
        if (!validateStrongPassword($newPassword)) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, number and special character'];
        }

        // Update password
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);
        $stmt = $this->db->prepare('UPDATE users SET password_hash = ?, password_changed_at = NOW() WHERE user_id = ?');

        if ($stmt->execute([$hashedPassword, $userId])) {
            $this->logActivity($userId, 'Password Changed', 'users', $userId, null, null);
            return ['success' => true, 'message' => 'Password changed successfully'];
        }

        return ['success' => false, 'message' => 'Failed to update password'];
    }

    /**
     * Generate password reset token
     */
    public function generatePasswordResetToken($email) {
        $stmt = $this->db->prepare('SELECT user_id, username FROM users WHERE email = ? AND status = "active"');
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        if (!$user) {
            return ['success' => false, 'message' => 'Email address not found'];
        }

        $token = generateToken(32);
        $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));

        $stmt = $this->db->prepare('INSERT INTO password_resets (user_id, token, expires_at, created_at) VALUES (?, ?, ?, NOW()) ON DUPLICATE KEY UPDATE token = VALUES(token), expires_at = VALUES(expires_at), created_at = VALUES(created_at)');

        if ($stmt->execute([$user['user_id'], $token, $expires])) {
            // In a real application, send email here
            $this->logActivity($user['user_id'], 'Password Reset Requested', 'users', $user['user_id'], null, null);
            return ['success' => true, 'message' => 'Password reset link sent to your email', 'token' => $token];
        }

        return ['success' => false, 'message' => 'Failed to generate reset token'];
    }

    /**
     * Reset password with token
     */
    public function resetPassword($token, $newPassword) {
        $stmt = $this->db->prepare('SELECT pr.user_id FROM password_resets pr WHERE pr.token = ? AND pr.expires_at > NOW() AND pr.used = 0');
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            return ['success' => false, 'message' => 'Invalid or expired reset token'];
        }

        if (!validateStrongPassword($newPassword)) {
            return ['success' => false, 'message' => 'Password must be at least 8 characters with uppercase, lowercase, number and special character'];
        }

        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Update password and mark token as used
        $this->db->beginTransaction();

        try {
            $stmt = $this->db->prepare('UPDATE users SET password_hash = ?, password_changed_at = NOW() WHERE user_id = ?');
            $stmt->execute([$hashedPassword, $reset['user_id']]);

            $stmt = $this->db->prepare('UPDATE password_resets SET used = 1 WHERE token = ?');
            $stmt->execute([$token]);

            $this->db->commit();

            $this->logActivity($reset['user_id'], 'Password Reset', 'users', $reset['user_id'], null, null);
            return ['success' => true, 'message' => 'Password reset successfully'];

        } catch (Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => 'Failed to reset password'];
        }
    }

    /**
     * Log user activity
     */
    private function logActivity($userId, $action, $table, $recordId, $oldData = null, $newData = null) {
        try {
            $stmt = $this->db->prepare(
                'INSERT INTO audit_logs (user_id, action, table_name, record_id, old_data, new_data, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );

            $ip = getClientIpAddress();
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? null;

            return $stmt->execute([
                $userId,
                $action,
                $table,
                $recordId,
                $oldData ? json_encode($oldData) : null,
                $newData ? json_encode($newData) : null,
                $ip,
                $userAgent
            ]);
        } catch (Exception $e) {
            error_log("Failed to log activity: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get user's active sessions
     */
    public function getActiveSessions($userId) {
        // This would require a sessions table to track multiple sessions
        // For now, return current session info
        if ($this->isLoggedIn() && $_SESSION['user_id'] == $userId) {
            return [
                [
                    'session_id' => session_id(),
                    'ip_address' => $_SESSION['ip_address'] ?? 'Unknown',
                    'user_agent' => $_SESSION['user_agent'] ?? 'Unknown',
                    'last_activity' => date('Y-m-d H:i:s', $_SESSION['last_activity'] ?? time()),
                    'current' => true
                ]
            ];
        }
        return [];
    }

    /**
     * Check if user is online (active within last 5 minutes)
     */
    public function isUserOnline($userId) {
        return $this->isLoggedIn() && $_SESSION['user_id'] == $userId && 
               (time() - ($_SESSION['last_activity'] ?? 0)) < 300; // 5 minutes
    }
}

// Initialize Auth instance
$auth = new Auth();

// Define public pages that don't require authentication
$public_pages = [
    'login.php', 
    'forgot_password.php', 
    'reset_password.php', 
    'api/mobile_money.php',
    '404.php',
    '403.php'
];

// Get current page
$current_page = basename($_SERVER['PHP_SELF']);

// Require login for non-public pages
if (!in_array($current_page, $public_pages, true)) {
    $auth->requireLogin();
}

?>
