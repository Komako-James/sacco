<?php
/**
 * Complete Authentication Debugger - Detects all issues
 */

// Avoid loading auth.php to prevent session conflicts
require_once 'config/db_connection.php';
require_once 'config/constants.php';

echo '<h1>🔍 SACCO Authentication Debugger</h1>';
echo '<style>
    body { font-family: Arial, sans-serif; margin: 20px; max-width: 900px; }
    .success { color: #28a745; background: #d4edda; padding: 8px; border-radius: 4px; margin: 5px 0; }
    .error { color: #dc3545; background: #f8d7da; padding: 8px; border-radius: 4px; margin: 5px 0; }
    .warning { color: #856404; background: #fff3cd; padding: 8px; border-radius: 4px; margin: 5px 0; }
    .info { color: #0c5460; background: #d1ecf1; padding: 8px; border-radius: 4px; margin: 5px 0; }
    code { background: #f8f9fa; padding: 2px 4px; border-radius: 3px; }
    table { width: 100%; border-collapse: collapse; margin: 10px 0; }
    th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
    th { background-color: #f2f2f2; }
</style>';

$issues = [];
$fixes = [];

try {
    $db = Database::getInstance()->getConnection();
    echo '<div class="success">✅ Database connection successful</div>';
} catch (Exception $e) {
    echo '<div class="error">❌ Database connection failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
    exit;
}

// Test 1: Check constants
echo '<h2>1️⃣ Configuration Check</h2>';
echo '<table>';
echo '<tr><th>Setting</th><th>Value</th><th>Status</th></tr>';
echo '<tr><td>APP_URL</td><td>' . htmlspecialchars(APP_URL) . '</td><td>' . (APP_URL === '/sacco' ? '<span class="error">❌ Wrong case</span>' : '<span class="success">✅</span>') . '</td></tr>';
echo '<tr><td>SESSION_TIMEOUT</td><td>' . SESSION_TIMEOUT . '</td><td><span class="success">✅</span></td></tr>';
echo '</table>';

if (APP_URL === '/sacco') {
    $issues[] = "APP_URL is set to '/sacco' but your folder is '/SACCO' (uppercase)";
    $fixes[] = "Change APP_URL in config/constants.php to '/SACCO'";
}

// Test 2: Database schema check
echo '<h2>2️⃣ Database Schema Check</h2>';

// Check users table structure
try {
    $stmt = $db->prepare("DESCRIBE users");
    $stmt->execute();
    $columns = $stmt->fetchAll();

    $columnNames = array_column($columns, 'Field');
    $requiredColumns = ['user_id', 'username', 'password_hash', 'full_name', 'role', 'status', 'login_attempts', 'locked_until'];

    echo '<table>';
    echo '<tr><th>Required Column</th><th>Present</th><th>Status</th></tr>';
    foreach ($requiredColumns as $col) {
        $present = in_array($col, $columnNames);
        echo '<tr><td>' . htmlspecialchars($col) . '</td><td>' . ($present ? 'Yes' : 'No') . '</td><td>' . ($present ? '<span class="success">✅</span>' : '<span class="error">❌</span>') . '</td></tr>';

        if (!$present) {
            $issues[] = "Missing column: {$col}";
            $fixes[] = "Run: ALTER TABLE users ADD COLUMN {$col} " . ($col === 'login_attempts' ? 'INT DEFAULT 0' : ($col === 'locked_until' ? 'DATETIME NULL' : 'VARCHAR(255)'));
        }
    }
    echo '</table>';

} catch (Exception $e) {
    echo '<div class="error">❌ Cannot check users table: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Test 3: Admin user check
echo '<h2>3️⃣ Admin User Check</h2>';
try {
    $stmt = $db->prepare("SELECT user_id, username, password_hash, status, login_attempts, locked_until FROM users WHERE username = 'admin'");
    $stmt->execute();
    $admin = $stmt->fetch();

    if ($admin) {
        echo '<div class="success">✅ Admin user found</div>';
        echo '<table>';
        echo '<tr><th>Field</th><th>Value</th><th>Status</th></tr>';

        // Check each field
        $checks = [
            'user_id' => ['value' => $admin['user_id'], 'status' => !empty($admin['user_id'])],
            'username' => ['value' => $admin['username'], 'status' => $admin['username'] === 'admin'],
            'password_hash' => ['value' => substr($admin['password_hash'], 0, 20) . '...', 'status' => !empty($admin['password_hash'])],
            'status' => ['value' => $admin['status'], 'status' => $admin['status'] === 'active'],
            'login_attempts' => ['value' => $admin['login_attempts'] ?? 'NULL', 'status' => ($admin['login_attempts'] ?? 0) < 5],
            'locked_until' => ['value' => $admin['locked_until'] ?? 'NULL', 'status' => empty($admin['locked_until']) || strtotime($admin['locked_until']) <= time()]
        ];

        foreach ($checks as $field => $check) {
            $statusIcon = $check['status'] ? '<span class="success">✅</span>' : '<span class="error">❌</span>';
            echo "<tr><td>{$field}</td><td>" . htmlspecialchars($check['value']) . "</td><td>{$statusIcon}</td></tr>";
        }
        echo '</table>';

        // Test password
        $testPasswords = ['Welcome@123', 'password', 'admin', 'TestAdmin@123'];
        echo '<h3>Password Testing</h3>';
        $passwordWorked = false;

        foreach ($testPasswords as $testPass) {
            $verified = password_verify($testPass, $admin['password_hash']);
            $icon = $verified ? '<span class="success">✅</span>' : '<span class="error">❌</span>';
            echo "<div>Testing '<code>{$testPass}</code>': {$icon}</div>";
            if ($verified) {
                $passwordWorked = true;
                echo '<div class="success"><strong>✅ Working password found: ' . htmlspecialchars($testPass) . '</strong></div>';
                break;
            }
        }

        if (!$passwordWorked) {
            $issues[] = "None of the common passwords work for admin user";
            $fixes[] = "Run the password reset script or update password manually";
        }

    } else {
        echo '<div class="error">❌ Admin user not found</div>';
        $issues[] = "No admin user exists in database";
        $fixes[] = "Create admin user or run setup script";
    }

} catch (Exception $e) {
    echo '<div class="error">❌ Cannot check admin user: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Test 4: Auth class compatibility
echo '<h2>4️⃣ Authentication Flow Test</h2>';
try {
    // Include functions first
    require_once 'includes/functions.php';

    // Test if getClientIpAddress function exists
    if (function_exists('getClientIpAddress')) {
        echo '<div class="success">✅ getClientIpAddress function exists</div>';
    } else {
        echo '<div class="error">❌ getClientIpAddress function missing</div>';
        $issues[] = "getClientIpAddress function not found in functions.php";
    }

    // Test session configuration
    echo '<h3>Session Configuration</h3>';
    $sessionPath = ini_get('session.cookie_path');
    echo "<div>Session cookie path: <code>{$sessionPath}</code></div>";

    if ($sessionPath !== APP_URL) {
        echo '<div class="warning">⚠️ Session cookie path mismatch!</div>';
        $issues[] = "Session cookie path ({$sessionPath}) doesn't match APP_URL (" . APP_URL . ")";
        $fixes[] = "Update session_config.php to set correct cookie path";
    }

} catch (Exception $e) {
    echo '<div class="error">❌ Auth compatibility check failed: ' . htmlspecialchars($e->getMessage()) . '</div>';
}

// Test 5: Create working admin if requested
echo '<h2>5️⃣ Quick Fix Admin User</h2>';

if (isset($_GET['create_admin'])) {
    try {
        $newPassword = 'FixedAdmin@123';
        $hashedPassword = password_hash($newPassword, PASSWORD_DEFAULT);

        // Add missing columns if they don't exist
        $alterQueries = [
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS login_attempts INT DEFAULT 0",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS locked_until DATETIME NULL",
            "ALTER TABLE users ADD COLUMN IF NOT EXISTS last_failed_login DATETIME NULL"
        ];

        foreach ($alterQueries as $query) {
            try {
                $db->exec($query);
            } catch (Exception $e) {
                // Ignore if column exists
                if (strpos($e->getMessage(), 'Duplicate column name') === false) {
                    echo '<div class="warning">⚠️ ' . htmlspecialchars($e->getMessage()) . '</div>';
                }
            }
        }

        // Delete and recreate admin
        $db->prepare("DELETE FROM users WHERE username = 'admin'")->execute();

        $stmt = $db->prepare("
            INSERT INTO users (username, password_hash, full_name, email, role, status, login_attempts, created_at) 
            VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
        ");

        $result = $stmt->execute([
            'admin',
            $hashedPassword,
            'System Administrator',
            'admin@sacco.local',
            'admin',
            'active',
            0
        ]);

        if ($result) {
            echo '<div class="success">✅ Admin user created successfully!</div>';
            echo '<div class="info">';
            echo '<strong>Fixed Admin Credentials:</strong><br>';
            echo 'Username: <code>admin</code><br>';
            echo 'Password: <code>' . htmlspecialchars($newPassword) . '</code>';
            echo '</div>';

            // Verify the password works
            if (password_verify($newPassword, $hashedPassword)) {
                echo '<div class="success">✅ Password verification test passed!</div>';
            } else {
                echo '<div class="error">❌ Password verification test failed!</div>';
            }
        }

    } catch (Exception $e) {
        echo '<div class="error">❌ Failed to create admin: ' . htmlspecialchars($e->getMessage()) . '</div>';
    }
} else {
    echo '<a href="?create_admin=1" style="background: #007cba; color: white; padding: 10px 15px; text-decoration: none; border-radius: 5px;">🔧 Create Working Admin User</a>';
}

// Summary
echo '<hr><h2>🎯 Issues Summary</h2>';
if (empty($issues)) {
    echo '<div class="success">🎉 No major issues found! Try logging in now.</div>';
} else {
    echo '<h3>❌ Issues Found:</h3>';
    foreach ($issues as $issue) {
        echo '<div class="error">• ' . htmlspecialchars($issue) . '</div>';
    }

    echo '<h3>🔧 Suggested Fixes:</h3>';
    foreach ($fixes as $fix) {
        echo '<div class="info">• ' . htmlspecialchars($fix) . '</div>';
    }
}

echo '<hr>';
echo '<h2>🚀 Next Steps:</h2>';
echo '<ol>';
echo '<li>Fix any red ❌ issues above</li>';
echo '<li>If admin was created, use the new credentials</li>';
echo '<li>Try login: <a href="login.php">login.php</a></li>';
echo '<li>Check browser console for JavaScript errors</li>';
echo '</ol>';

echo '<div class="warning">';
echo '<strong>⚠️ Security Note:</strong> Delete this debug file after use!';
echo '</div>';
?>
