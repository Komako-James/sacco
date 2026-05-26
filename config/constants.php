<?php
// Application Settings
define('APP_NAME', 'SACCO Management System');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'Africa/Kampala');
date_default_timezone_set(TIMEZONE);

define('APP_URL', '/sacco');

define('ITEMS_PER_PAGE', 20);
if (!defined('SESSION_TIMEOUT')) {
    define('SESSION_TIMEOUT', 1800); // 30 minutes
}

define('COMPANY_NAME', 'Rakai District Employees Savings and Credit Co-operative Society Ltd');
define('COMPANY_ADDRESS', 'P.O. Box 21 Kyotera');
define('COMPANY_PHONE', '+256787187693 | +256702617840');
define('COMPANY_EMAIL', 'radesccs@yahoo.com');
define('COMPANY_LOGO', APP_URL . '/assets/images/logo.svg');

define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_PATH', dirname(__DIR__) . '/assets/uploads/');

// Error Reporting (Turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
