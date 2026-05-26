<?php
/**
 * Admin Password Reset & Diagnostic Tool
 */

require_once 'config/db_connection.php';

$db = Database::getInstance()->getConnection();

// New password to set
$newPassword = 'Admin@1234';
$newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);

echo '<h2>Admin Password Reset & Diagnostic</h2>';
echo '<hr>';

try {
    // Step 1: Check if admin user exists
    echo '<h3>Step 1: Check Admin User</h3>';
    $stmt = $db->prepare("SELECT user_id, username, email, status FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();
    
    if (!$admin) {
        echo '<div style="color: red;"><strong>ERROR:</strong> Admin user not found in database!</div>';
    } else {
        echo '<div style="color: green;"><strong>✓ Admin user found:</strong></div>';
        echo '<ul>';
        echo '<li>User ID: ' . htmlspecialchars($admin['user_id']) . '</li>';
        echo '<li>Username: ' . htmlspecialchars($admin['username']) . '</li>';
        echo '<li>Email: ' . htmlspecialchars($admin['email']) . '</li>';
        echo '<li>Status: ' . htmlspecialchars($admin['status']) . '</li>';
        echo '</ul>';
    }
    
    // Step 2: Reset the password
    echo '<h3>Step 2: Resetting Password</h3>';
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    if ($stmt->execute([$newPasswordHash])) {
        echo '<div style="color: green;"><strong>✓ Password updated successfully!</strong></div>';
    } else {
        echo '<div style="color: red;"><strong>ERROR:</strong> Failed to update password</div>';
    }
    
    // Step 3: Verify the new password was saved
    echo '<h3>Step 3: Verify Password Hash</h3>';
    $stmt = $db->prepare("SELECT password_hash FROM users WHERE username = 'admin'");
    $stmt->execute();
    $result = $stmt->fetch();
    
    if ($result && password_verify($newPassword, $result['password_hash'])) {
        echo '<div style="color: green;"><strong>✓ Password verification successful!</strong></div>';
        echo '<p>The password <code>' . htmlspecialchars($newPassword) . '</code> will work.</p>';
    } else {
        echo '<div style="color: red;"><strong>ERROR:</strong> Password verification failed!</div>';
    }
    
    echo '<hr>';
    echo '<h3>New Login Credentials:</h3>';
    echo '<p><strong>Username:</strong> <code>admin</code></p>';
    echo '<p><strong>Password:</strong> <code>' . htmlspecialchars($newPassword) . '</code></p>';
    echo '<hr>';
    echo '<p>Use the exact URL: <a href="/sacco/login.php">http://localhost/sacco/login.php</a></p>';
    echo '<p><a href="/sacco/login.php">Go to Login</a></p>';
    echo '<p style="color: red;"><strong>⚠️ Delete this file after use:</strong> <code>admin_password_reset.php</code></p>';
    
} catch (Exception $e) {
    echo '<div style="color: red;"><strong>ERROR:</strong> ' . htmlspecialchars($e->getMessage()) . '</div>';
    echo '<p>Stack trace: <pre>' . htmlspecialchars($e->getTraceAsString()) . '</pre></p>';
}
?>

<style>
    body { font-family: Arial, sans-serif; margin: 20px; }
    h2, h3 { color: #333; }
    code { background: #f0f0f0; padding: 2px 5px; border-radius: 3px; }
    a { color: #0066cc; text-decoration: none; }
    a:hover { text-decoration: underline; }
</style>
