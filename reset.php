<?php
/**
 * Admin User Reset Script
 * Resets the admin password to a known default
 */

require_once 'config/session_config.php';
require_once 'config/db_connection.php';
require_once 'config/constants.php';

$db = Database::getInstance()->getConnection();

// Default credentials
$defaultUsername = 'admin';
$defaultPassword = 'Admin@1234';
$passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);

try {
    // Update existing admin user
    $stmt = $db->prepare("
        UPDATE users 
        SET password_hash = ?, full_name = ?, email = ?, status = 'active'
        WHERE username = ?
    ");
    $stmt->execute([
        $passwordHash,
        'System Administrator',
        'admin@sacco.local',
        $defaultUsername
    ]);
    
    echo "✅ Admin user password has been reset successfully!<br><br>";
    echo "<strong>Login Credentials:</strong><br>";
    echo "Username: <code>admin</code><br>";
    echo "Password: <code>Admin@1234</code><br><br>";
    echo "<strong>⚠️ IMPORTANT:</strong><br>";
    echo "1. Login now and change the password immediately<br>";
    echo "2. Delete this reset.php file for security<br>";
    echo "3. <a href='login.php'>Go to Login Page</a>";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
