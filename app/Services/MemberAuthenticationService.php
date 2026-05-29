<?php
/**
 * Member Authentication Service
 * Handles member login, credential management, and security
 */

namespace SACCO\Services;

use PDO;
use Exception;

class MemberAuthenticationService
{
    private $db;
    private $config;

    public function __construct(PDO $database, $config = [])
    {
        $this->db = $database;
        $this->config = array_merge([
            'password_min_length' => 8,
            'password_require_uppercase' => true,
            'password_require_numbers' => true,
            'password_require_special' => true,
            'max_login_attempts' => 5,
            'lockout_duration' => 1800, // 30 minutes
            'session_timeout' => 1800, // 30 minutes
            'password_expiry_days' => 90,
            'otp_validity' => 300, // 5 minutes
            'otp_length' => 6,
            'bcrypt_cost' => 12
        ], $config);
    }

    /**
     * Create member login credentials when member is registered
     * @param int $memberId
     * @param string $membershipNumber
     * @param string $memberName
     * @param string $memberPhone
     * @return array ['success' => bool, 'user_id' => int, 'username' => string, 'password' => string]
     */
    public function createMemberCredentials($memberId, $membershipNumber, $memberName, $memberPhone)
    {
        try {
            $this->db->beginTransaction();

            // Username is the membership number
            $username = $membershipNumber;

            // Generate secure temporary password
            $tempPassword = $this->generateSecurePassword();
            $passwordHash = password_hash($tempPassword, PASSWORD_BCRYPT, ['cost' => $this->config['bcrypt_cost']]);

            // Create user account for member
            $stmt = $this->db->prepare("
                INSERT INTO users (
                    username, email, password_hash, full_name, phone, role, 
                    is_member, linked_member_id, must_change_password, status, created_at
                ) VALUES (?, ?, ?, ?, ?, 'member', TRUE, ?, TRUE, 'active', NOW())
            ");

            $email = strtolower(str_replace(' ', '.', $memberName)) . '@member.local';

            $stmt->execute([
                $username,
                $email,
                $passwordHash,
                $memberName,
                $memberPhone,
                $memberId
            ]);

            $userId = $this->db->lastInsertId();

            // Link user to member
            $stmt = $this->db->prepare("UPDATE members SET user_id = ? WHERE member_id = ?");
            $stmt->execute([$userId, $memberId]);

            // Log credential creation
            $this->logCredentialHistory(
                $userId,
                $memberId,
                null,
                $username,
                null,
                $passwordHash,
                'created',
                $userId,
                'Member account created'
            );

            // Queue SMS with credentials
            $this->queueCredentialSMS($memberId, $memberPhone, $username, $tempPassword, $memberName);

            $this->db->commit();

            return [
                'success' => true,
                'user_id' => $userId,
                'username' => $username,
                'password' => $tempPassword,
                'message' => 'Credentials sent via SMS'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Failed to create credentials: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Member login with security checks
     * @param string $username (membership number)
     * @param string $password
     * @param string $ipAddress
     * @param string $userAgent
     * @return array ['success' => bool, 'user_id' => int, 'member_id' => int, 'requires_2fa' => bool, 'message' => string]
     */
    public function memberLogin($username, $password, $ipAddress, $userAgent)
    {
        try {
            // Get user account by membership number
            $stmt = $this->db->prepare("
                SELECT u.*, m.member_id, m.phone, m.email as member_email
                FROM users u
                LEFT JOIN members m ON u.user_id = m.user_id
                WHERE u.username = ? AND u.role = 'member' AND u.status = 'active'
            ");
            $stmt->execute([$username]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $this->logLoginAttempt(null, null, $username, 'failed_username', $ipAddress, $userAgent);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password'
                ];
            }

            $userId = $user['user_id'];
            $memberId = $user['member_id'];

            // Check if account is locked
            if ($user['locked_until'] && strtotime($user['locked_until']) > time()) {
                $this->logLoginAttempt($userId, $memberId, $username, 'locked', $ipAddress, $userAgent);
                return [
                    'success' => false,
                    'message' => 'Account is locked. Try again later.'
                ];
            }

            // Verify password
            if (!password_verify($password, $user['password_hash'])) {
                $this->incrementFailedLogins($userId);
                $this->logLoginAttempt($userId, $memberId, $username, 'failed_password', $ipAddress, $userAgent);
                return [
                    'success' => false,
                    'message' => 'Invalid username or password'
                ];
            }

            // Check if password has expired
            if ($user['password_expires_at'] && strtotime($user['password_expires_at']) < time()) {
                return [
                    'success' => false,
                    'requires_password_change' => true,
                    'user_id' => $userId,
                    'message' => 'Password has expired. Please change it.'
                ];
            }

            // Check if must change password on first login
            if ($user['must_change_password']) {
                return [
                    'success' => false,
                    'requires_password_change' => true,
                    'first_login' => true,
                    'user_id' => $userId,
                    'member_id' => $memberId,
                    'message' => 'Please change your temporary password'
                ];
            }

            // Check if 2FA is enabled
            $securityPrefs = $this->getMemberSecurityPreferences($userId);
            $requires2FA = $securityPrefs['two_factor_enabled'] ?? false;

            if ($requires2FA) {
                // Generate OTP
                $otp = $this->generateOTP();
                $this->storeOTPToken($userId, $memberId, $otp, 'login_verification');
                
                // Send OTP via SMS
                $this->queueOTPSMS($memberId, $user['phone'], $otp);

                $this->logLoginAttempt($userId, $memberId, $username, 'success', $ipAddress, $userAgent, false);

                return [
                    'success' => false,
                    'requires_2fa' => true,
                    'user_id' => $userId,
                    'member_id' => $memberId,
                    'message' => 'OTP sent to your phone'
                ];
            }

            // Clear failed login attempts
            $stmt = $this->db->prepare("
                UPDATE users SET login_attempts = 0, locked_until = NULL 
                WHERE user_id = ?
            ");
            $stmt->execute([$userId]);

            // Create session
            $sessionToken = $this->createMemberSession($userId, $memberId, $ipAddress, $userAgent);

            // Update last login
            $stmt = $this->db->prepare("
                UPDATE users SET last_login = NOW(), last_login_ip = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$ipAddress, $userId]);

            $this->logLoginAttempt($userId, $memberId, $username, 'success', $ipAddress, $userAgent, true);

            return [
                'success' => true,
                'user_id' => $userId,
                'member_id' => $memberId,
                'session_token' => $sessionToken,
                'message' => 'Login successful'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Login failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Verify OTP for 2FA
     */
    public function verify2FAOTP($userId, $memberId, $otpCode, $ipAddress, $userAgent)
    {
        try {
            // Verify OTP
            $stmt = $this->db->prepare("
                SELECT * FROM member_otp_tokens
                WHERE user_id = ? AND member_id = ? AND purpose = 'login_verification'
                AND is_used = FALSE AND expires_at > NOW()
                ORDER BY created_at DESC LIMIT 1
            ");
            $stmt->execute([$userId, $memberId]);
            $otpRecord = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$otpRecord) {
                return [
                    'success' => false,
                    'message' => 'OTP not found or expired'
                ];
            }

            // Check attempt count
            if ($otpRecord['attempts'] >= $otpRecord['max_attempts']) {
                return [
                    'success' => false,
                    'message' => 'Max OTP attempts exceeded. Request a new OTP.'
                ];
            }

            // Verify OTP
            if (!hash_equals(hash('sha256', $otpCode), $otpRecord['otp_hash'])) {
                $stmt = $this->db->prepare("
                    UPDATE member_otp_tokens SET attempts = attempts + 1
                    WHERE otp_id = ?
                ");
                $stmt->execute([$otpRecord['otp_id']]);

                return [
                    'success' => false,
                    'message' => 'Invalid OTP'
                ];
            }

            // Mark OTP as used
            $stmt = $this->db->prepare("
                UPDATE member_otp_tokens SET is_used = TRUE, used_at = NOW()
                WHERE otp_id = ?
            ");
            $stmt->execute([$otpRecord['otp_id']]);

            // Create session
            $sessionToken = $this->createMemberSession($userId, $memberId, $ipAddress, $userAgent);

            // Update last login
            $stmt = $this->db->prepare("
                UPDATE users SET last_login = NOW(), last_login_ip = ?
                WHERE user_id = ?
            ");
            $stmt->execute([$ipAddress, $userId]);

            $this->logLoginAttempt($userId, $memberId, null, 'success', $ipAddress, $userAgent, true, 'otp');

            return [
                'success' => true,
                'user_id' => $userId,
                'member_id' => $memberId,
                'session_token' => $sessionToken,
                'message' => '2FA verification successful'
            ];

        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'OTP verification failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Change password on first login
     */
    public function changePasswordFirstLogin($userId, $memberId, $newPassword)
    {
        try {
            // Validate password
            $validation = $this->validatePassword($newPassword);
            if (!$validation['valid']) {
                return [
                    'success' => false,
                    'message' => $validation['errors'][0]
                ];
            }

            $this->db->beginTransaction();

            // Get current password
            $stmt = $this->db->prepare("SELECT password_hash FROM users WHERE user_id = ?");
            $stmt->execute([$userId]);
            $user = $stmt->fetch(PDO::FETCH_ASSOC);

            // Hash new password
            $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => $this->config['bcrypt_cost']]);

            // Update password
            $stmt = $this->db->prepare("
                UPDATE users SET 
                    password_hash = ?,
                    must_change_password = FALSE,
                    password_changed_at = NOW(),
                    password_expires_at = DATE_ADD(NOW(), INTERVAL ? DAY)
                WHERE user_id = ?
            ");
            $stmt->execute([
                $newPasswordHash,
                $this->config['password_expiry_days'],
                $userId
            ]);

            // Log password change
            $this->logCredentialHistory(
                $userId,
                $memberId,
                null,
                null,
                $user['password_hash'],
                $newPasswordHash,
                'changed',
                $userId,
                'First login password change'
            );

            $this->db->commit();

            return [
                'success' => true,
                'message' => 'Password changed successfully'
            ];

        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => 'Password change failed: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Generate secure password
     */
    private function generateSecurePassword($length = 12)
    {
        $uppercase = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $lowercase = 'abcdefghijklmnopqrstuvwxyz';
        $numbers = '0123456789';
        $special = '!@#$%^&*()_-+=[]{}|:;<>,.?/~`';

        $password = '';
        $password .= $uppercase[random_int(0, strlen($uppercase) - 1)];
        $password .= $lowercase[random_int(0, strlen($lowercase) - 1)];
        $password .= $numbers[random_int(0, strlen($numbers) - 1)];
        $password .= $special[random_int(0, strlen($special) - 1)];

        $allChars = $uppercase . $lowercase . $numbers . $special;
        for ($i = 4; $i < $length; $i++) {
            $password .= $allChars[random_int(0, strlen($allChars) - 1)];
        }

        return str_shuffle($password);
    }

    /**
     * Generate OTP
     */
    private function generateOTP($length = 6)
    {
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= random_int(0, 9);
        }
        return $otp;
    }

    /**
     * Validate password strength
     */
    public function validatePassword($password)
    {
        $errors = [];

        if (strlen($password) < $this->config['password_min_length']) {
            $errors[] = 'Password must be at least ' . $this->config['password_min_length'] . ' characters';
        }

        if ($this->config['password_require_uppercase'] && !preg_match('/[A-Z]/', $password)) {
            $errors[] = 'Password must contain at least one uppercase letter';
        }

        if ($this->config['password_require_numbers'] && !preg_match('/[0-9]/', $password)) {
            $errors[] = 'Password must contain at least one number';
        }

        if ($this->config['password_require_special'] && !preg_match('/[!@#$%^&*()_\-+=\[\]{}|:;<>,.?\/~`]/', $password)) {
            $errors[] = 'Password must contain at least one special character';
        }

        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * Create member session
     */
    private function createMemberSession($userId, $memberId, $ipAddress, $userAgent)
    {
        $sessionToken = bin2hex(random_bytes(32));
        $expiresAt = date('Y-m-d H:i:s', time() + $this->config['session_timeout']);

        $stmt = $this->db->prepare("
            INSERT INTO member_sessions (
                user_id, member_id, session_token, ip_address, user_agent,
                expires_at, is_active
            ) VALUES (?, ?, ?, ?, ?, ?, TRUE)
        ");

        $stmt->execute([
            $userId,
            $memberId,
            $sessionToken,
            $ipAddress,
            $userAgent
        ]);

        return $sessionToken;
    }

    /**
     * Store OTP token
     */
    private function storeOTPToken($userId, $memberId, $otp, $purpose = 'login_verification')
    {
        $otpHash = hash('sha256', $otp);
        $expiresAt = date('Y-m-d H:i:s', time() + $this->config['otp_validity']);

        $stmt = $this->db->prepare("
            INSERT INTO member_otp_tokens (
                user_id, member_id, otp_code, otp_hash, purpose, expires_at
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $memberId,
            $otp,
            $otpHash,
            $purpose,
            $expiresAt
        ]);
    }

    /**
     * Log login attempt
     */
    private function logLoginAttempt($userId, $memberId, $username, $status, $ipAddress, $userAgent, $mfaVerified = false, $mfaMethod = null)
    {
        $stmt = $this->db->prepare("
            INSERT INTO member_login_audit (
                user_id, member_id, username, status, ip_address, user_agent,
                mfa_verified, mfa_method, login_timestamp
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $stmt->execute([
            $userId,
            $memberId,
            $username,
            $status,
            $ipAddress,
            substr($userAgent, 0, 255),
            $mfaVerified,
            $mfaMethod
        ]);
    }

    /**
     * Increment failed login attempts
     */
    private function incrementFailedLogins($userId)
    {
        $stmt = $this->db->prepare("
            UPDATE users SET login_attempts = login_attempts + 1
            WHERE user_id = ?
        ");
        $stmt->execute([$userId]);

        // Get updated count
        $stmt = $this->db->prepare("SELECT login_attempts FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        // Lock account if max attempts exceeded
        if ($result['login_attempts'] >= $this->config['max_login_attempts']) {
            $lockedUntil = date('Y-m-d H:i:s', time() + $this->config['lockout_duration']);
            $stmt = $this->db->prepare("
                UPDATE users SET locked_until = ? WHERE user_id = ?
            ");
            $stmt->execute([$lockedUntil, $userId]);
        }
    }

    /**
     * Log credential history
     */
    private function logCredentialHistory($userId, $memberId, $oldUsername, $newUsername, $oldPasswordHash, $newPasswordHash, $action, $changedBy, $reason)
    {
        $stmt = $this->db->prepare("
            INSERT INTO member_login_credentials_history (
                user_id, member_id, old_username, new_username, old_password_hash,
                new_password_hash, action, changed_by, change_reason
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $userId,
            $memberId,
            $oldUsername,
            $newUsername,
            $oldPasswordHash,
            $newPasswordHash,
            $action,
            $changedBy,
            $reason
        ]);
    }

    /**
     * Queue SMS with credentials
     */
    private function queueCredentialSMS($memberId, $phone, $username, $password, $memberName)
    {
        $message = "Welcome $memberName! Your SACCO login credentials: Username: $username | Temporary Password: $password | Please change password on first login.";

        $stmt = $this->db->prepare("
            INSERT INTO sms_queue (
                member_id, phone_number, message, message_type, status
            ) VALUES (?, ?, ?, 'login_credentials', 'pending')
        ");

        $stmt->execute([
            $memberId,
            $phone,
            $message
        ]);
    }

    /**
     * Queue OTP SMS
     */
    private function queueOTPSMS($memberId, $phone, $otp)
    {
        $message = "Your OTP is: $otp (Valid for 5 minutes)";

        $stmt = $this->db->prepare("
            INSERT INTO sms_queue (
                member_id, phone_number, message, message_type, status
            ) VALUES (?, ?, ?, 'otp_verification', 'pending')
        ");

        $stmt->execute([
            $memberId,
            $phone,
            $message
        ]);
    }

    /**
     * Get member security preferences
     */
    private function getMemberSecurityPreferences($userId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM member_security_preferences WHERE user_id = ?
        ");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    }

    /**
     * Validate member session
     */
    public function validateSession($sessionToken)
    {
        $stmt = $this->db->prepare("
            SELECT ms.*, u.is_member, m.member_id
            FROM member_sessions ms
            JOIN users u ON ms.user_id = u.user_id
            LEFT JOIN members m ON u.user_id = m.user_id
            WHERE ms.session_token = ? AND ms.is_active = TRUE
            AND ms.expires_at > NOW()
        ");
        $stmt->execute([$sessionToken]);
        $session = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$session) {
            return ['valid' => false, 'message' => 'Invalid or expired session'];
        }

        // Update last activity
        $stmt = $this->db->prepare("
            UPDATE member_sessions SET last_activity = NOW()
            WHERE session_id = ?
        ");
        $stmt->execute([$session['session_id']]);

        return [
            'valid' => true,
            'user_id' => $session['user_id'],
            'member_id' => $session['member_id'],
            'session' => $session
        ];
    }

    /**
     * Member logout
     */
    public function memberLogout($sessionToken)
    {
        $stmt = $this->db->prepare("
            UPDATE member_sessions SET is_active = FALSE, logout_at = NOW()
            WHERE session_token = ?
        ");
        return $stmt->execute([$sessionToken]);
    }
}
