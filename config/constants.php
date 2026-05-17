<?php
// Application Settings
define('APP_NAME', 'SACCO Management System');
define('APP_VERSION', '1.0.0');
define('TIMEZONE', 'Africa/Kampala');
date_default_timezone_set(TIMEZONE);

// File Upload Settings
define('MAX_FILE_SIZE', 5242880); // 5MB
define('ALLOWED_EXTENSIONS', ['jpg', 'jpeg', 'png', 'pdf']);
define('UPLOAD_PATH', dirname(__DIR__) . '/assets/uploads/');

// Session Settings
// (Session configuration is handled in config/session_config.php before session_start())
date_default_timezone_set(TIMEZONE);
define('ITEMS_PER_PAGE', 20);

// Interest & Penalty Settings
define('DEFAULT_SAVINGS_INTEREST', 5.00);
define('LATE_PAYMENT_GRACE_DAYS', 7);
define('MIN_SAVING_MONTHS_BEFORE_LOAN', 3);

// SMS Settings (Africa's Talking - Example)
define('SMS_API_KEY', 'your_api_key');
define('SMS_USERNAME', 'your_username');
define('SMS_SENDER_ID', 'SACCO');

// Mobile Money Settings (MTN Uganda - Example)
define('MTN_API_URL', 'https://sandbox.momoapi.mtn.com');
define('MTN_SUBSCRIPTION_KEY', 'your_subscription_key');
define('MTN_CALLBACK_URL', 'https://yourdomain.com/api/mtn_callback.php');

// Report Settings
define('COMPANY_NAME', 'Your SACCO Limited');
define('COMPANY_EMAIL', 'info@sacco.com');
define('COMPANY_PHONE', '+256 XXX XXX XXX');

// Error Reporting (Turn off in production)
error_reporting(E_ALL);
ini_set('display_errors', 1);
?>
