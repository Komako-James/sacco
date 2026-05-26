<?php
/**
 * Quick script to change admin password
 */

require_once 'config/db_connection.php';

$db = Database::getInstance()->getConnection();

// New password
$newPassword = 'Welcome@123';
$newPasswordHash = password_hash($newPassword, PASSWORD_BCRYPT);

try {
    $stmt = $db->prepare("UPDATE users SET password_hash = ? WHERE username = 'admin'");
    $stmt->execute([$newPasswordHash]);
    
    echo "✅ Admin password updated successfully!<br><br>";
    echo "New credentials:<br>";
    echo "Username: <code>admin</code><br>";
    echo "Password: <code>" . htmlspecialchars($newPassword) . "</code><br><br>";
    echo "<a href='login.php'>Go to Login</a><br><br>";
    echo "ℹ️ Delete this file after use: <code>change_admin_password.php</code>";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
