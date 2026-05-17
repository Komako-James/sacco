<?php
/**
 * SACCO Audit Service
 * Logs all user activities for compliance and audit purposes
 */

namespace SACCO\Services;

use PDO;

class AuditService
{
    private static $db;

    public static function setDatabase(PDO $database)
    {
        self::$db = $database;
    }

    /**
     * Log an action to the audit trail
     * 
     * @param int $userId
     * @param string $action (e.g., 'LOAN_APPROVED', 'MEMBER_CREATED')
     * @param string $entityType (e.g., 'loans', 'members')
     * @param int $entityId
     * @param array|null $oldValues before values (JSON)
     * @param array|null $newValues after values (JSON)
     * @param bool $success
     * @param string|null $errorMessage
     */
    public static function log($userId, $action, $entityType, $entityId, $oldValues = null, $newValues = null, $success = true, $errorMessage = null)
    {
        try {
            $stmt = self::$db->prepare("
                INSERT INTO audit_logs
                (user_id, action, entity_type, entity_id, old_values, new_values, 
                 ip_address, user_agent, status, error_message, timestamp)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
            ");

            $oldValuesJson = $oldValues ? json_encode($oldValues) : null;
            $newValuesJson = $newValues ? json_encode($newValues) : null;
            $ipAddress = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
            $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
            $statusText = $success ? 'success' : 'failure';

            $stmt->execute([
                $userId,
                $action,
                $entityType,
                $entityId,
                $oldValuesJson,
                $newValuesJson,
                $ipAddress,
                $userAgent,
                $statusText,
                $errorMessage
            ]);

            return true;
        } catch (\Exception $e) {
            error_log("Audit logging failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Get audit trail for an entity
     */
    public static function getEntityHistory($entityType, $entityId, $limit = 50)
    {
        $stmt = self::$db->prepare("
            SELECT al.*, u.full_name, u.username
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            WHERE al.entity_type = ? AND al.entity_id = ?
            ORDER BY al.timestamp DESC
            LIMIT ?
        ");

        $stmt->execute([$entityType, $entityId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get user activity log
     */
    public static function getUserActivity($userId, $limit = 100)
    {
        $stmt = self::$db->prepare("
            SELECT *
            FROM audit_logs
            WHERE user_id = ?
            ORDER BY timestamp DESC
            LIMIT ?
        ");

        $stmt->execute([$userId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get all failed actions
     */
    public static function getFailedActions($since = null, $limit = 100)
    {
        $since = $since ?: date('Y-m-d H:i:s', strtotime('-7 days'));

        $stmt = self::$db->prepare("
            SELECT al.*, u.full_name, u.username
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            WHERE al.status = 'failure' AND al.timestamp >= ?
            ORDER BY al.timestamp DESC
            LIMIT ?
        ");

        $stmt->execute([$since, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Audit high-value transactions
     */
    public static function auditHighValueTransaction($userId, $transactionType, $amount, $memberId = null)
    {
        // Log as alert if over threshold
        $threshold = 1000000;  // 1M UGX
        if ($amount >= $threshold) {
            self::log(
                $userId,
                'HIGH_VALUE_' . strtoupper($transactionType),
                'transactions',
                $memberId,
                null,
                ['amount' => $amount, 'type' => $transactionType]
            );
        }
    }
}

/**
 * SACCO Authentication Service
 * Handles user login, 2FA, biometric auth, and session management
 */
class AuthenticationService
{
    private $db;

    public function __construct(PDO $database)
    {
        $this->db = $database;
    }

    /**
     * Authenticate user with username and password
     * 
     * @param string $username
     * @param string $password
     * @param string $ipAddress
     * @return array ['success' => bool, 'user_id' => int, 'session_id' => string, 'requires_2fa' => bool]
     */
    public function login($username, $password, $ipAddress = null)
    {
        try {
            $ipAddress = $ipAddress ?: $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

            // Get user
            $stmt = $this->db->prepare("SELECT * FROM users WHERE username = ? AND status = 'active'");
            $stmt->execute([$username]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$user) {
                AuditService::log(null, 'LOGIN_FAILED', 'users', null, null, null, false, 'User not found');
                return ['success' => false, 'message' => 'Invalid username or password'];
            }

            // Check lock
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                return ['success' => false, 'message' => 'Account temporarily locked. Try again later.'];
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                // Increment failed attempts
                $attempts = $user['login_attempts'] + 1;
                $lockedUntil = $attempts >= 5 ? date('Y-m-d H:i:s', strtotime('+30 minutes')) : null;

                $stmt = $this->db->prepare("
                    UPDATE users
                    SET login_attempts = ?, locked_until = ?
                    WHERE user_id = ?
                ");
                $stmt->execute([$attempts, $lockedUntil, $user['user_id']]);

                AuditService::log($user['user_id'], 'LOGIN_FAILED', 'users', $user['user_id'], null, null, false, 'Invalid password');
                return ['success' => false, 'message' => 'Invalid username or password'];
            }

            // Reset failed attempts
            $stmt = $this->db->prepare("
                UPDATE users
                SET login_attempts = 0, locked_until = NULL, last_login = NOW()
                WHERE user_id = ?
            ");
            $stmt->execute([$user['user_id']]);

            // Create session
            $sessionId = $this->createSession($user['user_id'], $ipAddress);

            AuditService::log($user['user_id'], 'LOGIN_SUCCESS', 'users', $user['user_id']);

            // Check if 2FA is enabled
            if ($user['two_factor_enabled']) {
                $this->sendOTP($user['user_id'], $user['two_factor_method']);
                return [
                    'success' => true,
                    'user_id' => $user['user_id'],
                    'session_id' => $sessionId,
                    'requires_2fa' => true,
                    'message' => 'OTP sent to your registered contact'
                ];
            }

            return [
                'success' => true,
                'user_id' => $user['user_id'],
                'session_id' => $sessionId,
                'requires_2fa' => false,
                'user' => [
                    'username' => $user['username'],
                    'full_name' => $user['full_name'],
                    'role' => $user['role']
                ]
            ];
        } catch (\Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Validate biometric authentication
     * 
     * @param int $memberId
     * @param string $biometricData (fingerprint template)
     * @return bool
     */
    public function validateBiometric($memberId, $biometricData)
    {
        try {
            $stmt = $this->db->prepare("
                SELECT biometric_template FROM members WHERE member_id = ?
            ");
            $stmt->execute([$memberId]);
            $member = $stmt->fetch(\PDO::FETCH_ASSOC);

            if (!$member || !$member['biometric_template']) {
                return false;
            }

            // Compare biometric templates (implementation depends on biometric SDK)
            // This is a placeholder - actual implementation uses Neurotechnology VeriLook or similar
            $templateMatch = $this->compareBiometricTemplates(
                base64_decode($member['biometric_template']),
                base64_decode($biometricData)
            );

            return $templateMatch;
        } catch (\Exception $e) {
            error_log("Biometric validation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Request OTP
     */
    private function sendOTP($userId, $method = 'sms')
    {
        $otp = str_pad(rand(0, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $stmt = $this->db->prepare("
            UPDATE users
            SET two_factor_code = ?, two_factor_expires = ?
            WHERE user_id = ?
        ");
        $stmt->execute([hash('sha256', $otp), $expiresAt, $userId]);

        // Send via SMS or Email
        if ($method === 'sms') {
            $stmt = $this->db->prepare("SELECT phone FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(\PDO::FETCH_ASSOC);
            NotificationService::sendSMS($user['phone'], "Your SACCO OTP: {$otp}");
        }

        return true;
    }

    /**
     * Verify OTP
     */
    public function verifyOTP($userId, $otp)
    {
        $stmt = $this->db->prepare("
            SELECT two_factor_code, two_factor_expires FROM users WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$user) return false;

        // Check expiry
        if (strtotime($user['two_factor_expires']) < time()) {
            return false;
        }

        // Verify hash
        if (hash('sha256', $otp) !== $user['two_factor_code']) {
            return false;
        }

        // Clear codes
        $stmt = $this->db->prepare("
            UPDATE users SET two_factor_code = NULL, two_factor_expires = NULL WHERE user_id = ?
        ");
        $stmt->execute([$userId]);

        return true;
    }

    /**
     * Create session
     */
    private function createSession($userId, $ipAddress)
    {
        $sessionId = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', strtotime('+30 minutes'));
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';

        $stmt = $this->db->prepare("
            INSERT INTO sessions (session_id, user_id, ip_address, user_agent, expires_at, is_active)
            VALUES (?, ?, ?, ?, ?, TRUE)
        ");
        $stmt->execute([$sessionId, $userId, $ipAddress, $userAgent]);

        return $sessionId;
    }

    /**
     * Validate session
     */
    public function validateSession($sessionId, $ipAddress = null)
    {
        $ipAddress = $ipAddress ?: $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

        $stmt = $this->db->prepare("
            SELECT * FROM sessions
            WHERE session_id = ? AND is_active = TRUE AND expires_at > NOW()
        ");
        $stmt->execute([$sessionId]);
        $session = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$session) return null;

        // Verify IP (optional, configurable)
        if ($session['ip_address'] !== $ipAddress) {
            // Could enforce strict IP checking or allow with warning
        }

        // Update last activity
        $stmt = $this->db->prepare("
            UPDATE sessions
            SET last_activity = NOW(), expires_at = DATE_ADD(NOW(), INTERVAL 30 MINUTE)
            WHERE session_id = ?
        ");
        $stmt->execute([$sessionId]);

        return $session;
    }

    /**
     * Logout
     */
    public function logout($sessionId)
    {
        $stmt = $this->db->prepare("
            UPDATE sessions SET is_active = FALSE WHERE session_id = ?
        ");
        return $stmt->execute([$sessionId]);
    }

    /**
     * Compare biometric templates
     * Placeholder - actual implementation uses biometric SDK
     */
    private function compareBiometricTemplates($template1, $template2)
    {
        // This would use Neurotechnology VeriLook SDK or similar
        // For now, simple byte comparison (not production-ready)
        return $template1 === $template2;
    }
}

/**
 * SACCO Notification Service
 * Handles SMS, email, and in-app notifications
 */
class NotificationService
{
    private $db;

    public function __construct(PDO $database)
    {
        $this->db = $database;
    }

    /**
     * Send SMS notification
     * Uses Africa's Talking or Twilio API
     */
    public static function sendSMS($phoneNumber, $message, $type = 'general')
    {
        // Queue for async sending
        global $db;
        
        $stmt = $db->prepare("
            INSERT INTO sms_queue (phone_number, message_body, message_type, delivery_status, created_at)
            VALUES (?, ?, ?, 'pending', NOW())
        ");
        $stmt->execute([$phoneNumber, $message, $type]);

        // Could trigger background job to send immediately or batch
        return true;
    }

    /**
     * Notify loan approval
     */
    public static function notifyLoanApproval($memberId, $loanId, $approvedAmount)
    {
        $stmt = $GLOBALS['db']->prepare("
            SELECT phone FROM members WHERE member_id = ?
        ");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(\PDO::FETCH_ASSOC);

        $message = "Your loan application has been approved for UGX " . number_format($approvedAmount);
        self::sendSMS($member['phone'], $message, 'loan_approval');
    }

    /**
     * Send payment reminder SMS
     */
    public static function sendPaymentReminder($memberId, $loanId, $amountDue, $dueDate)
    {
        $stmt = $GLOBALS['db']->prepare("
            SELECT phone FROM members WHERE member_id = ?
        ");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(\PDO::FETCH_ASSOC);

        $message = "Your loan payment of UGX " . number_format($amountDue) . " is due on {$dueDate}";
        self::sendSMS($member['phone'], $message, 'payment_reminder');
    }

    /**
     * Process SMS queue
     * Called by background job/cron
     */
    public static function processSMSQueue()
    {
        global $db;

        $stmt = $db->prepare("
            SELECT * FROM sms_queue
            WHERE delivery_status = 'pending' AND attempts < max_attempts
            LIMIT 100
        ");
        $stmt->execute();
        $messages = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        foreach ($messages as $msg) {
            $sent = self::_sendViaProvider($msg['phone_number'], $msg['message_body']);

            $status = $sent ? 'sent' : 'failed';
            $stmt = $db->prepare("
                UPDATE sms_queue
                SET delivery_status = ?, attempts = attempts + 1
                WHERE sms_id = ?
            ");
            $stmt->execute([$status, $msg['sms_id']]);
        }
    }

    /**
     * Send via SMS provider (Africa's Talking or Twilio)
     */
    private static function _sendViaProvider($phone, $message)
    {
        // Placeholder for actual SMS API integration
        // Would call Africa's Talking API or Twilio
        // For now, just log
        error_log("SMS to {$phone}: {$message}");
        return true;
    }
}

?>
