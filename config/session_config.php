<?php
/**
 * Session Configuration for SACCO System
 * Must be called BEFORE session_start()
 * 
 * This file configures PHP session settings for security and compatibility
 * with the SACCO application structure.
 */

// Prevent direct access
if (!defined('SACCO_SYSTEM')) {
    // Allow access from included files
}

/**
 * Session Security Settings
 */

// Set custom session name (helps prevent session hijacking)
ini_set('session.name', 'SACCOSESSID');

// Security: Only allow cookies (no URL session IDs)
ini_set('session.use_only_cookies', 1);

// Security: Make cookies HTTP-only (prevents JavaScript access)
ini_set('session.cookie_httponly', 1);

// Security: Set to 1 if using HTTPS, 0 for HTTP (localhost)
ini_set('session.cookie_secure', 0); // Change to 1 in production with SSL

// Security: SameSite cookie attribute
ini_set('session.cookie_samesite', 'Lax');

// Session expires when browser closes
ini_set('session.cookie_lifetime', 0);

// Set cookie path to match your application folder
ini_set('session.cookie_path', '/sacco'); // ✅ Matches lowercase folder

// Set cookie domain (localhost for development)
ini_set('session.cookie_domain', 'localhost');

/**
 * Session Behavior Settings
 */

// Session garbage collection lifetime (1 hour)
ini_set('session.gc_maxlifetime', 3600);

// Probability of garbage collection (1% chance)
ini_set('session.gc_probability', 1);
ini_set('session.gc_divisor', 100);

// Use strict session mode (regenerates session ID for invalid sessions)
ini_set('session.use_strict_mode', 1);

// Disable transparent session ID (security)
ini_set('session.use_trans_sid', 0);

// Cache settings
ini_set('session.cache_limiter', 'nocache');
ini_set('session.cache_expire', 30); // 30 minutes

/**
 * Session Storage Settings
 */

// Use default file-based session storage
// In production, consider database or Redis storage
ini_set('session.save_handler', 'files');

// Optional: Set custom session save path (uncomment if needed)
// ini_set('session.save_path', sys_get_temp_dir() . '/sacco_sessions');

/**
 * Advanced Security Settings
 */

// Regenerate session ID to prevent fixation attacks
ini_set('session.use_strict_mode', 1);

// Entropy settings for better session ID generation
if (version_compare(PHP_VERSION, '7.1.0', '<')) {
    // For PHP versions before 7.1
    ini_set('session.entropy_file', '/dev/urandom');
    ini_set('session.entropy_length', 32);
    ini_set('session.hash_function', 'sha256');
    ini_set('session.hash_bits_per_character', 6);
}

/**
 * SACCO-specific Session Constants
 */

// Define session timeout (must match constants.php)
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800); // 30 minutes
}

/**
 * Session Helper Functions
 */

/**
 * Secure session start with error handling
 */
function sacco_session_start() {
    if (session_status() === PHP_SESSION_NONE) {
        if (!session_start()) {
            error_log('SACCO: Failed to start session');
            return false;
        }

        // Regenerate session ID on first start for security
        if (!isset($_SESSION['initiated'])) {
            session_regenerate_id(true);
            $_SESSION['initiated'] = true;
        }

        // Set session timeout tracking
        if (!isset($_SESSION['created'])) {
            $_SESSION['created'] = time();
        }

        // Check for session timeout
        if (isset($_SESSION['created']) && (time() - $_SESSION['created'] > SESSION_TIMEOUT)) {
            session_unset();
            session_destroy();
            return false;
        }

        return true;
    }
    return true;
}

/**
 * Secure session destroy
 */
function sacco_session_destroy() {
    if (session_status() === PHP_SESSION_ACTIVE) {
        // Unset all session variables
        $_SESSION = array();

        // Delete the session cookie
        if (ini_get("session.use_cookies")) {
            $params = session_get_cookie_params();
            setcookie(session_name(), '', time() - 42000,
                $params["path"], $params["domain"],
                $params["secure"], $params["httponly"]
            );
        }

        // Destroy the session
        session_destroy();
        return true;
    }
    return false;
}

/**
 * Check if session is valid and not expired
 */
function sacco_session_valid() {
    if (session_status() !== PHP_SESSION_ACTIVE) {
        return false;
    }

    // Check if session has required data
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['expires_at'])) {
        return false;
    }

    // Check expiration
    if ($_SESSION['expires_at'] < time()) {
        return false;
    }

    return true;
}

/**
 * Extend session expiration
 */
function sacco_session_extend() {
    if (sacco_session_valid()) {
        $_SESSION['expires_at'] = time() + SESSION_TIMEOUT;
        $_SESSION['last_activity'] = time();
        return true;
    }
    return false;
}

/**
 * Environment-specific Settings
 */

// Development environment settings
if ($_SERVER['SERVER_NAME'] === 'localhost' || 
    strpos($_SERVER['SERVER_NAME'], '127.0.0.1') !== false ||
    strpos($_SERVER['SERVER_NAME'], 'dev.') === 0) {

    // Development-specific session settings
    ini_set('session.cookie_secure', 0); // Allow HTTP in development
    error_reporting(E_ALL);

} else {
    // Production environment settings
    ini_set('session.cookie_secure', 1); // Force HTTPS in production
    ini_set('session.cookie_domain', $_SERVER['HTTP_HOST']); // Use actual domain
    error_reporting(0); // Hide errors in production
}

/**
 * Create session directory if using custom path
 */
/*
$custom_session_path = sys_get_temp_dir() . '/sacco_sessions';
if (!is_dir($custom_session_path)) {
    mkdir($custom_session_path, 0700, true);
}
ini_set('session.save_path', $custom_session_path);
*/

/**
 * Session cleanup function (call occasionally)
 */
function sacco_session_cleanup() {
    // This would clean up old session files
    // Can be called from a cron job or periodically
    $session_path = session_save_path();
    if ($session_path && is_dir($session_path)) {
        $files = glob($session_path . '/sess_*');
        $now = time();
        foreach ($files as $file) {
            if (is_file($file) && ($now - filemtime($file)) > SESSION_TIMEOUT) {
                unlink($file);
            }
        }
    }
}

// Mark that configuration has been loaded
if (!defined('SACCO_SESSION_CONFIG_LOADED')) {
    define('SACCO_SESSION_CONFIG_LOADED', true);
}

?>
