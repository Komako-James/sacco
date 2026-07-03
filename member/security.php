<?php
/**
 * Member Security Page - Change Password, 2FA Settings
 */

require_once __DIR__ . '/auth-middleware.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../app/Services/MemberAuthenticationService.php';

use SACCO\Services\MemberAuthenticationService;

requireMemberLogin();

$member = getMemberData();
$db = getDB();
$authService = new MemberAuthenticationService($db);

$message = '';
$messageType = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'change_password') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword)) {
        $message = 'All fields are required';
        $messageType = 'danger';
    } elseif ($newPassword !== $confirmPassword) {
        $message = 'New passwords do not match';
        $messageType = 'danger';
    } else {
        // Verify current password
        $stmt = $db->prepare("SELECT password_hash FROM users WHERE user_id = ?");
        $stmt->execute([$_SESSION['member_user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!password_verify($currentPassword, $user['password_hash'])) {
            $message = 'Current password is incorrect';
            $messageType = 'danger';
        } else {
            // Validate new password
            $validation = $authService->validatePassword($newPassword);
            if (!$validation['valid']) {
                $message = $validation['errors'][0];
                $messageType = 'danger';
            } else {
                try {
                    $newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 12]);
                    $stmt = $db->prepare("
                        UPDATE users SET password_hash = ?, password_changed_at = NOW()
                        WHERE user_id = ?
                    ");
                    $stmt->execute([$newPasswordHash, $_SESSION['member_user_id']]);

                    // Log password change
                    $stmt = $db->prepare("
                        INSERT INTO member_login_credentials_history (
                            user_id, member_id, old_password_hash, new_password_hash,
                            action, changed_by, change_reason
                        ) VALUES (?, ?, ?, ?, 'changed', ?, 'Self-service password change')
                    ");
                    $stmt->execute([
                        $_SESSION['member_user_id'],
                        $_SESSION['member_id'],
                        $user['password_hash'],
                        $newPasswordHash,
                        $_SESSION['member_user_id']
                    ]);

                    $message = 'Password changed successfully';
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = 'Failed to change password';
                    $messageType = 'danger';
                }
            }
        }
    }
}

// Handle 2FA toggle
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_POST['action'] === 'toggle_2fa') {
    $enable2FA = $_POST['enable_2fa'] === 'on';

    try {
        $stmt = $db->prepare("
            INSERT INTO member_security_preferences (user_id, member_id, two_factor_enabled)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE two_factor_enabled = ?
        ");
        $stmt->execute([
            $_SESSION['member_user_id'],
            $_SESSION['member_id'],
            $enable2FA ? 1 : 0,
            $enable2FA ? 1 : 0
        ]);

        $message = $enable2FA ? '2FA enabled successfully' : '2FA disabled successfully';
        $messageType = 'success';
    } catch (Exception $e) {
        $message = 'Failed to update 2FA settings';
        $messageType = 'danger';
    }
}

// Get security preferences
$stmt = $db->prepare("SELECT * FROM member_security_preferences WHERE user_id = ?");
$stmt->execute([$_SESSION['member_user_id']]);
$securityPrefs = $stmt->fetch(PDO::FETCH_ASSOC);

$pageTitle = 'Security Settings - Member Portal';
require_once __DIR__ . '/layout/header.php';
?>
    <div class="container-fluid py-4">
            <div class="mb-4">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <h2>Security Settings</h2>
            </div>

            <?php if ($message): ?>
            <div class="alert alert-<?php echo $messageType; ?>"><?php echo $message; ?></div>
            <?php endif; ?>

            <div class="row">
                <!-- Change Password -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-key me-2"></i>
                                Change Password
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="change_password">

                                <div class="mb-3">
                                    <label for="currentPassword" class="form-label">Current Password</label>
                                    <input type="password" class="form-control" id="currentPassword" name="current_password" required>
                                </div>

                                <div class="mb-3">
                                    <label for="newPassword" class="form-label">New Password</label>
                                    <input type="password" class="form-control" id="newPassword" name="new_password" required>
                                    <small class="text-muted">
                                        Minimum 8 characters, with uppercase, numbers, and special characters
                                    </small>
                                </div>

                                <div class="mb-3">
                                    <label for="confirmPassword" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" id="confirmPassword" name="confirm_password" required>
                                </div>

                                <button type="submit" class="btn btn-primary w-100">Change Password</button>
                            </form>
                        </div>
                    </div>
                </div>

                <!-- Two-Factor Authentication -->
                <div class="col-md-6 mb-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-shield-check me-2"></i>
                                Two-Factor Authentication
                            </h5>
                        </div>
                        <div class="card-body">
                            <form method="POST">
                                <input type="hidden" name="action" value="toggle_2fa">

                                <div class="form-check form-switch mb-3">
                                    <input 
                                        class="form-check-input" 
                                        type="checkbox" 
                                        id="enable2FA" 
                                        name="enable_2fa"
                                        <?php echo ($securityPrefs['two_factor_enabled'] ?? false) ? 'checked' : ''; ?>
                                    >
                                    <label class="form-check-label" for="enable2FA">
                                        Enable 2FA via SMS
                                    </label>
                                </div>

                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    <strong>What is 2FA?</strong><br>
                                    Two-factor authentication adds an extra layer of security. You'll receive an OTP (One-Time Password) on your registered phone number during login.
                                </div>

                                <button type="submit" class="btn btn-primary w-100">
                                    <?php echo ($securityPrefs['two_factor_enabled'] ?? false) ? 'Disable 2FA' : 'Enable 2FA'; ?>
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Login Activity -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">
                                <i class="bi bi-clock-history me-2"></i>
                                Recent Login Activity
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php
                            $stmt = $db->prepare("
                                SELECT * FROM member_login_audit
                                WHERE member_id = ?
                                ORDER BY login_timestamp DESC
                                LIMIT 10
                            ");
                            $stmt->execute([$_SESSION['member_id']]);
                            $loginActivity = $stmt->fetchAll(PDO::FETCH_ASSOC);
                            ?>

                            <?php if (!empty($loginActivity)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date & Time</th>
                                            <th>Status</th>
                                            <th>IP Address</th>
                                            <th>Device</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($loginActivity as $activity): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y H:i:s', strtotime($activity['login_timestamp'])); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $activity['status'] === 'success' ? 'success' : 'danger'; ?>">
                                                    <?php echo ucfirst($activity['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['ip_address']); ?></td>
                                            <td><?php echo substr($activity['user_agent'], 0, 50) . '...'; ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <p class="text-muted">No login activity recorded</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
