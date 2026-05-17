<?php
/**
 * Database Setup & Default User Creation
 * Run this script once to initialize the database with default admin user
 * Then delete this file for security
 */

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
    die('Admin user already exists!');
}

// Default credentials
$defaultUsername = 'admin';
$defaultPassword = 'Admin@1234'; // Change this!
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
    echo "<strong>Default Credentials:</strong><br>";
    echo "Username: <code>admin</code><br>";
    echo "Password: <code>Admin@1234</code><br><br>";
    echo "<strong>⚠️ IMPORTANT:</strong><br>";
    echo "1. Change the default password immediately after first login<br>";
    echo "2. Delete this setup.php file for security<br>";
    echo "3. Go to <a href='login.php'>Login Page</a>";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
