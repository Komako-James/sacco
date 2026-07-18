<?php
/**
 * Database Setup & Default User Creation
 * IMPORTANT: This file should be DELETED after the system is initialized.
 * Keeping this file is a SECURITY RISK.
 */

// Prevent execution if system is already set up
$isSetUp = file_exists(__DIR__ . '/.setup_complete');
if ($isSetUp) {
    die('System is already set up. Please delete setup.php for security.');
}

require_once 'config/session_config.php';
require_once 'config/db_connection.php';
require_once 'config/constants.php';

$db = Database::getInstance()->getConnection();

// Check if users table exists
$stmt = $db->query("SHOW TABLES LIKE 'users'");
if ($stmt->rowCount() === 0) {
    die('Users table not found. Please import database.sql first.');
}

// Check if admin already exists
$stmt = $db->prepare("SELECT COUNT(*) as count FROM users WHERE username = 'admin'");
$stmt->execute();
$result = $stmt->fetch();

if ($result['count'] > 0) {
    die('Admin user already exists! Please delete this file and use your credentials to login.');
}

// Default credentials
$defaultUsername = 'admin';
$defaultPassword = 'Admin@1234'; // CHANGE THIS IMMEDIATELY!
$passwordHash = password_hash($defaultPassword, PASSWORD_BCRYPT);

try {
    // Insert admin user
    $stmt = $db->prepare("
        INSERT INTO users (username, password_hash, full_name, email, role, status)
        VALUES (?, ?, ?, ?, ?, 'active')
    ");
    $stmt->execute([
        $defaultUsername,
        $passwordHash,
        'System Administrator',
        'admin@sacco.local',
        'admin'
    ]);
    
    echo "✅ Default admin user created successfully!<br><br>";
    echo "<strong>⚠️ IMPORTANT SECURITY STEPS:</strong><br>";
    echo "1. Change the default password IMMEDIATELY after first login<br>";
    echo "2. Delete this setup.php file right now for security<br>";
    echo "3. Go to <a href='login.php'>Login Page</a><br><br>";
    echo "<strong>Default Credentials (CHANGE IMMEDIATELY):</strong><br>";
    echo "Username: <code>admin</code><br>";
    echo "Password: <code>Admin@1234</code><br>";
    
    // Create marker file
    touch(__DIR__ . '/.setup_complete');
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
