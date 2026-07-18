<?php
// Application Settings
define('APP_NAME', 'SACCO Management System');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'Africa/Kampala');
date_default_timezone_set(TIMEZONE);

define('APP_URL', '/sacco');

define('ITEMS_PER_PAGE', 20);
define('SHARE_PRICE', 10000); // Price per share in UGX
define('MIN_SHARE_BALANCE', 1); // Minimum shares a member must retain after transfer
define('SHARE_TRANSFER_MINIMUM', 1); // Minimum shares allowed for a transfer

define('SHARE_VALUE_RULE', 'fixed'); // Fixed per-share price rule

if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800); // 30 minutes
}

define('COMPANY_NAME', 'Rakai District Employees Savings and Credit Co-operative Society Ltd');
define('COMPANY_ADDRESS', 'P.O. Box 21 Kyotera');
define('COMPANY_PHONE', '+256759672333 | +256760956850 | +256772856021');
define('COMPANY_EMAIL', 'radesccs@yahoo.com');
define('COMPANY_LOGO', APP_URL . '/logo/rakai.jpg');

define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_PATH', dirname(__DIR__) . '/assets/uploads/');

// Error Reporting (Turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', dirname(__DIR__) . '/logs/php_errors.log');
?>
