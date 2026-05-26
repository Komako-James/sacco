<?php
/**
 * Login Debug Trace - Shows exactly what happens during login
 */

require_once 'config/db_connection.php';
require_once 'includes/functions.php';

$db = Database::getInstance()->getConnection();

echo '<h2>Login Debug Trace</h2>';
echo '<hr>';

$testUsername = 'admin';
$testPassword = 'TestPass@123';

echo '<h3>Step 1: Fetch Admin User</h3>';
$stmt = $db->prepare("SELECT * FROM users WHERE username = ?");
$stmt->execute([$testUsername]);
$user = $stmt->fetch();

if (!$user) {
    echo '<div style="color: red;"><strong>ERROR:</strong> User not found!</div>';
    exit;
}

echo '<div style="color: green;"><strong>✓ User found</strong></div>';
echo '<ul>';
echo '<li>User ID: ' . htmlspecialchars($user['user_id']) . '</li>';
echo '<li>Username: ' . htmlspecialchars($user['username']) . '</li>';
echo '<li>Email: ' . htmlspecialchars($user['email']) . '</li>';
echo '<li>Status: ' . htmlspecialchars($user['status']) . '</li>';
echo '<li>Password Hash (first 50 chars): ' . htmlspecialchars(substr($user['password_hash'], 0, 50)) . '...</li>';
echo '<li>Failed Attempts: ' . htmlspecialchars($user['failed_login_attempts'] ?? 0) . '</li>';
echo '<li>Locked Until: ' . htmlspecialchars($user['locked_until'] ?? 'Not locked') . '</li>';
echo '</ul>';

echo '<h3>Step 2: Check Status</h3>';
if ($user['status'] !== 'active') {
    echo '<div style="color: red;"><strong>ERROR:</strong> User status is "' . htmlspecialchars($user['status']) . '", not "active"!</div>';
} else {
    echo '<div style="color: green;"><strong>✓ User status is active</strong></div>';
}

echo '<h3>Step 3: Check Account Lock</h3>';
if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
    $remainingTime = ceil((strtotime($user['locked_until']) - time()) / 60);
    echo '<div style="color: red;"><strong>ERROR:</strong> Account is locked! Try again in ' . $remainingTime . ' minutes.</div>';
} else {
    echo '<div style="color: green;"><strong>✓ Account is not locked</strong></div>';
}

echo '<h3>Step 4: Test Password Verification</h3>';
echo '<p>Testing: <code>password_verify("' . htmlspecialchars($testPassword) . '", hash)</code></p>';

if (password_verify($testPassword, $user['password_hash'])) {
    echo '<div style="color: green;"><strong>✓ Password verification PASSED!</strong></div>';
} else {
    echo '<div style="color: red;"><strong>ERROR:</strong> Password verification FAILED!</div>';
    echo '<p>This means the password hash in the database does not match the password you entered.</p>';
    
    // Let's try to reset it again
    echo '<h3>Step 5: Attempting to reset password again...</h3>';
    $newHash = password_hash($testPassword, PASSWORD_BCRYPT);
    $resetStmt = $db->prepare("UPDATE users SET password_hash = ?, failed_login_attempts = 0, locked_until = NULL WHERE username = ?");
    if ($resetStmt->execute([$newHash, $testUsername])) {
        echo '<div style="color: green;"><strong>✓ Password reset attempt completed</strong></div>';
        
        // Verify again
        $verifyStmt = $db->prepare("SELECT password_hash FROM users WHERE username = ?");
        $verifyStmt->execute([$testUsername]);
        $verifyResult = $verifyStmt->fetch();
        
        if (password_verify($testPassword, $verifyResult['password_hash'])) {
            echo '<div style="color: green;"><strong>✓ Re-verification PASSED! Try logging in again.</strong></div>';
        } else {
            echo '<div style="color: red;"><strong>ERROR:</strong> Re-verification still failed!</div>';
        }
    } else {
        echo '<div style="color: red;"><strong>ERROR:</strong> Failed to reset password</div>';
    }
}

echo '<hr>';
echo '<h3>Login Credentials to Try:</h3>';
echo '<p><strong>Username:</strong> <code>' . htmlspecialchars($testUsername) . '</code></p>';
echo '<p><strong>Password:</strong> <code>' . htmlspecialchars($testPassword) . '</code></p>';
echo '<p><a href="login.php">Go to Login</a></p>';
echo '<p style="color: red;"><strong>⚠️ Delete this file after use:</strong> <code>login_debug.php</code></p>';
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2, h3 { color: #333; }
    code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; font-family: monospace; }
    a { color: #0066cc; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
