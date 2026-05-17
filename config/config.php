<?php
/**
 * SACCO System Configuration
 * Central configuration for all system parameters
 */

// ============================================================================
// APPLICATION SETTINGS
// ============================================================================

return [
    'app' => [
        'name' => 'SACCO Management System',
        'version' => '1.0.0',
        'environment' => env('APP_ENV', 'production'),
        'debug' => env('APP_DEBUG', false),
        'timezone' => 'Africa/Kampala',
    ],

    // ============================================================================
    // DATABASE
    // ============================================================================
    'database' => [
        'host' => env('DB_HOST', 'localhost'),
        'port' => env('DB_PORT', 3306),
        'name' => env('DB_NAME', 'sacco_system'),
        'user' => env('DB_USER', 'root'),
        'password' => env('DB_PASSWORD', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'pool_size' => 10,
        'max_connections' => 50,
    ],

    // ============================================================================
    // MEMBERSHIP SETTINGS
    // ============================================================================
    'membership' => [
        'min_number' => 1,
        'max_number' => 700,
        'number_format' => '3-digit',  // 001, 002, ... 700
        'districts' => ['Rakai', 'Kyotera'],
        'kyc_required' => true,
        'biometric_required' => true,
    ],

    // ============================================================================
    // LOAN SETTINGS
    // ============================================================================
    'loans' => [
        'salary_loan' => [
            'code' => 'SALLOAN',
            'annual_interest_rate' => 26.0,
            'monthly_interest_rate' => 2.167,  // 26% / 12
            'min_amount' => 100000,
            'max_amount' => 5000000,
            'min_repayment_months' => 6,
            'max_repayment_months' => 24,
            'processing_fee_percentage' => 2.0,
            'late_penalty_daily' => 0.5,  // % per day
            'late_penalty_monthly_cap' => 5.0,  // % per month max
            'requires_guarantors' => true,
            'min_guarantors' => 2,
            'max_guarantors' => 5,
            'min_savings_months' => 3,
            'grace_period_days' => 7,
        ],

        'business_loan' => [
            'code' => 'BUSLOAN',
            'monthly_interest_rate' => 5.0,
            'annual_interest_rate' => 60.0,
            'min_amount' => 50000,
            'max_amount' => 1000000,
            'min_repayment_months' => 1,
            'max_repayment_months' => 6,
            'processing_fee_percentage' => 2.5,
            'late_penalty_daily' => 0.75,
            'requires_guarantors' => true,
            'min_guarantors' => 2,
            'max_guarantors' => 3,
            'min_savings_months' => 3,
        ],
    ],

    // ============================================================================
    // SAVINGS SETTINGS
    // ============================================================================
    'savings' => [
        'monthly_savings' => [
            'name' => 'Monthly Savings',
            'default_interest_rate' => 5.0,  // % annual
            'min_opening_balance' => 10000,
        ],
        'voluntary_savings' => [
            'name' => 'Voluntary Savings',
            'default_interest_rate' => 5.0,
        ],
        'fixed_deposit' => [
            'name' => 'Fixed Deposit',
            'default_interest_rate' => 8.0,
        ],
    ],

    // ============================================================================
    // AUTHENTICATION & SECURITY
    // ============================================================================
    'auth' => [
        'session_timeout' => 1800,  // 30 minutes in seconds
        'password_min_length' => 8,
        'password_require_uppercase' => true,
        'password_require_numbers' => true,
        'password_require_special' => true,
        'max_login_attempts' => 5,
        'lockout_duration' => 1800,  // 30 minutes
        'enable_two_factor' => true,
        'two_factor_methods' => ['sms', 'email', 'app'],
        'otp_validity' => 300,  // 5 minutes
        'otp_length' => 6,
        'bcrypt_cost' => 12,
        'session_strict_mode' => true,
        'session_secure_cookies' => true,  // HTTPS only
        'session_httponly' => true,
    ],

    // ============================================================================
    // BIOMETRIC SETTINGS
    // ============================================================================
    'biometric' => [
        'provider' => 'neurotechnology_verilock',  // or 'same_ink', 'mobius', etc
        'template_format' => 'iso_19794_2',
        'liveness_detection' => true,
        'storage_encryption' => 'AES-256-CBC',
        'min_quality_threshold' => 70,  // 0-100
    ],

    // ============================================================================
    // SMS SETTINGS
    // ============================================================================
    'sms' => [
        'provider' => 'africas_talking',  // or 'twilio', 'nexmo', etc
        'africas_talking' => [
            'api_key' => env('AFRICAS_TALKING_API_KEY'),
            'username' => env('AFRICAS_TALKING_USERNAME'),
        ],
        'twilio' => [
            'account_sid' => env('TWILIO_ACCOUNT_SID'),
            'auth_token' => env('TWILIO_AUTH_TOKEN'),
            'from_number' => env('TWILIO_FROM_NUMBER'),
        ],
        'sender_id' => 'SACCO',
        'max_retries' => 3,
        'retry_delay' => 300,  // 5 minutes
        'batch_size' => 100,
    ],

    // ============================================================================
    // EMAIL SETTINGS
    // ============================================================================
    'email' => [
        'driver' => 'smtp',
        'host' => env('MAIL_HOST'),
        'port' => env('MAIL_PORT', 587),
        'username' => env('MAIL_USERNAME'),
        'password' => env('MAIL_PASSWORD'),
        'encryption' => 'tls',
        'from' => [
            'address' => env('MAIL_FROM_ADDRESS', 'noreply@sacco.com'),
            'name' => env('MAIL_FROM_NAME', 'SACCO System'),
        ],
    ],

    // ============================================================================
    // INTEREST CALCULATION
    // ============================================================================
    'interest' => [
        'calculation_day_of_month' => 'last',  // or specific day: 28
        'calculation_time' => '23:00',  // 11 PM
        'rounding_method' => 'round_half_up',
        'decimal_places' => 2,
        'recalculation_on_partial_payment' => true,
    ],

    // ============================================================================
    // LEDGER & ACCOUNTING
    // ============================================================================
    'accounting' => [
        'reconciliation_required' => true,
        'require_approval_for_reversal' => true,
        'disable_manual_posting' => false,
        'balance_sheet_frequency' => 'monthly',
        'income_statement_frequency' => 'monthly',
        'trial_balance_frequency' => 'daily',
    ],

    // ============================================================================
    // FILE UPLOADS
    // ============================================================================
    'files' => [
        'max_upload_size' => 5242880,  // 5MB
        'allowed_extensions' => ['jpg', 'jpeg', 'png', 'pdf', 'csv', 'xls', 'xlsx'],
        'upload_directory' => '/assets/uploads/',
        'scan_for_viruses' => true,
        'quarantine_directory' => '/assets/quarantine/',
    ],

    // ============================================================================
    // REPORTING
    // ============================================================================
    'reports' => [
        'default_format' => 'pdf',  // pdf, excel, csv
        'include_audit_trail' => true,
        'default_timezone' => 'Africa/Kampala',
        'include_logos' => true,
        'include_qr_codes' => true,
        'statement_retention_days' => 2555,  // 7 years
    ],

    // ============================================================================
    // AUDIT & COMPLIANCE
    // ============================================================================
    'audit' => [
        'log_all_changes' => true,
        'log_login_attempts' => true,
        'log_failed_transactions' => true,
        'log_high_value_transactions' => true,
        'high_value_threshold' => 1000000,  // 1M UGX
        'retention_days' => 2555,  // 7 years
        'immutable_logging' => true,
        'alert_on_suspicious_activity' => true,
    ],

    // ============================================================================
    // NOTIFICATIONS
    // ============================================================================
    'notifications' => [
        'send_loan_approval' => true,
        'send_payment_reminders' => true,
        'send_overdue_warnings' => true,
        'send_statement_delivery' => true,
        'send_birthday_wishes' => true,
        'reminder_days_before_due' => 3,
        'overdue_warning_days' => [1, 7, 14, 30, 60, 90],
    ],

    // ============================================================================
    // MOBILE MONEY INTEGRATION
    // ============================================================================
    'mobile_money' => [
        'mtn' => [
            'enabled' => true,
            'api_url' => 'https://api.mtn.com',
            'subscription_key' => env('MTN_SUBSCRIPTION_KEY'),
            'callback_url' => env('APP_URL') . '/api/callbacks/mtn',
        ],
        'airtel' => [
            'enabled' => true,
            'api_url' => 'https://api.airtel.com',
            'merchant_id' => env('AIRTEL_MERCHANT_ID'),
            'callback_url' => env('APP_URL') . '/api/callbacks/airtel',
        ],
    ],

    // ============================================================================
    // RATE LIMITING
    // ============================================================================
    'rate_limiting' => [
        'api_requests_per_minute' => 100,
        'sms_per_hour_per_member' => 5,
        'login_attempts_per_hour' => 10,
        'otp_requests_per_hour' => 5,
    ],

    // ============================================================================
    // LOGGING & MONITORING
    // ============================================================================
    'logging' => [
        'level' => env('LOG_LEVEL', 'info'),
        'channel' => 'stack',
        'storage' => 'file',  // or 'database', 'sentry'
        'directory' => '/var/logs/sacco/',
        'daily_rotation' => true,
        'retention_days' => 30,
        'error_email' => env('ERROR_EMAIL'),
        'slack_webhook' => env('SLACK_WEBHOOK_URL'),
    ],

    // ============================================================================
    // BACKUP & RECOVERY
    // ============================================================================
    'backup' => [
        'enabled' => true,
        'schedule' => '0 2 * * *',  // 2 AM daily
        'storage' => 's3',  // or 'gcs', 'local', 'ftp'
        's3' => [
            'bucket' => env('AWS_BUCKET'),
            'region' => env('AWS_REGION'),
            'key' => env('AWS_ACCESS_KEY_ID'),
            'secret' => env('AWS_SECRET_ACCESS_KEY'),
        ],
        'retention_days' => 90,
        'encrypt_backups' => true,
        'test_restore_weekly' => true,
    ],

    // ============================================================================
    // PAGINATION
    // ============================================================================
    'pagination' => [
        'per_page' => 20,
        'max_per_page' => 100,
    ],

    // ============================================================================
    // API
    // ============================================================================
    'api' => [
        'version' => 'v1',
        'base_url' => env('APP_URL') . '/api',
        'enable_cors' => true,
        'cors_origins' => env('CORS_ORIGINS', '*'),
        'response_format' => 'json',
        'include_api_docs' => true,
        'api_docs_url' => '/api/docs',
    ],
];

/**
 * Helper function to get config values
 */
function config($key, $default = null)
{
    static $config = null;
    if ($config === null) {
        $config = include __DIR__ . '/config.php';
    }

    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (is_array($value) && array_key_exists($k, $value)) {
            $value = $value[$k];
        } else {
            return $default;
        }
    }

    return $value;
}

/**
 * Helper function for environment variables
 */
function env($key, $default = null)
{
    $value = getenv($key);
    return $value !== false ? $value : $default;
}

?>
