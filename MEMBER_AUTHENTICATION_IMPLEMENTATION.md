# Member Authentication System - Implementation Guide

## Overview
This guide explains how to integrate the Member Authentication System into your existing SACCO application. It covers database setup, credential auto-creation during member registration, and security best practices.

---

## 1. Prerequisites

### Required Files
- ✅ `migrations/004_member_authentication.sql` - Database schema
- ✅ `app/Services/MemberAuthenticationService.php` - Authentication service
- ✅ `member-login.php` - Member login page
- ✅ `member/` - Portal pages directory
- ✅ `api/v1/member.php` - API endpoints

### Dependencies
- PHP 8.2+
- MySQL 5.7+ or MariaDB 10.2+
- Bootstrap 5.1.3 (already in project)
- PDO PHP extension

---

## 2. Database Migration Setup

### Step 1: Run the Migration
```bash
# Navigate to project root
cd d:\wamp64\www\sacco

# Using PHP CLI
php D:\wamp64\bin\php\php8.2.0\php.exe -f migrations/run_migrations.php

# Or manually execute SQL
mysql -u root < migrations/004_member_authentication.sql
```

### Step 2: Verify Migration
```sql
-- Check new tables were created
SHOW TABLES LIKE 'member_%';

-- Check users table alterations
DESCRIBE users;

-- Check members table alterations
DESCRIBE members;
```

### Expected Output
Should show these new tables:
- `password_reset_tokens`
- `member_login_credentials_history`
- `member_login_audit`
- `member_otp_tokens`
- `member_devices`
- `member_sessions`
- `member_security_preferences`
- `sms_queue` (if not already existing)

---

## 3. Update Member Registration

### Current Registration File
Location: `members/register.php`

### Integration Steps

1. **Add Service Import**
```php
require_once '../app/Services/MemberAuthenticationService.php';
use SACCO\Services\MemberAuthenticationService;

$authService = new MemberAuthenticationService($db);
```

2. **After Member Creation**
Add this after the member record is successfully created:

```php
// Get the newly created member ID
$memberId = $db->lastInsertId();

// Create login credentials
$result = $authService->createMemberCredentials(
    $memberId,
    $membershipNumber,      // From form input
    $member['full_name'],   // From form input
    $member['phone']        // From form input
);

if ($result['success']) {
    $message = 'Member registered successfully. Login credentials have been sent via SMS.';
    // Optionally log the temporary password (for testing only)
    // error_log('Member ' . $memberId . ' credentials: ' . $result['username'] . '/' . $result['password']);
} else {
    $message = 'Member created but failed to generate credentials: ' . $result['message'];
}
```

### Complete Updated Code Example
```php
<?php
// members/register.php - UPDATED

session_start();
require_once '../config/db_connection.php';
require_once '../app/Services/MemberAuthenticationService.php';
use SACCO\Services\MemberAuthenticationService;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $db = getDB();
    $authService = new MemberAuthenticationService($db);

    $membershipNumber = $_POST['membership_number'] ?? '';
    $fullName = $_POST['full_name'] ?? '';
    $email = $_POST['email'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $idNumber = $_POST['id_number'] ?? '';

    try {
        $db->beginTransaction();

        // Create member record
        $stmt = $db->prepare("
            INSERT INTO members (
                membership_number, full_name, email, phone, id_number,
                join_date, status
            ) VALUES (?, ?, ?, ?, ?, NOW(), 'active')
        ");
        
        $stmt->execute([
            $membershipNumber,
            $fullName,
            $email,
            $phone,
            $idNumber
        ]);

        $memberId = $db->lastInsertId();

        // Create login credentials
        $result = $authService->createMemberCredentials(
            $memberId,
            $membershipNumber,
            $fullName,
            $phone
        );

        if (!$result['success']) {
            throw new Exception('Failed to create credentials: ' . $result['message']);
        }

        $db->commit();

        // Show success message with instructions
        $message = 'Member registered successfully!<br>Login credentials have been sent via SMS to ' . $phone;
        $messageType = 'success';

    } catch (Exception $e) {
        $db->rollBack();
        $message = 'Registration failed: ' . $e->getMessage();
        $messageType = 'danger';
    }
}
?>
```

---

## 4. Access Control Middleware

The authentication middleware is provided in `member/auth-middleware.php`.

### Usage in Member Pages
```php
<?php
// At the top of any member portal page

require_once '../member/auth-middleware.php';

// This ensures user is logged in as member
requireMemberLogin();

// Get member data
$member = getMemberData();
$memberId = $_SESSION['member_id'];
```

### Helper Functions Available
```php
// Check login status
requireMemberLogin();

// Get member info
$member = getMemberData();

// Get member savings accounts
$savings = getMemberSavings();

// Get member loans
$loans = getMemberLoans();

// Logout member
memberLogout();
```

---

## 5. Configuration

### Database Configuration
File: `config/db_connection.php` (already configured)

### Security Configuration
File: `config/constants.php`

Add these constants if not already present:
```php
// Session configuration
define('SESSION_TIMEOUT', 1800); // 30 minutes
define('SESSION_COOKIE_SECURE', true); // HTTPS only
define('SESSION_COOKIE_HTTPONLY', true); // No JS access

// Password configuration
define('PASSWORD_MIN_LENGTH', 8);
define('PASSWORD_REQUIRE_UPPERCASE', true);
define('PASSWORD_REQUIRE_NUMBERS', true);
define('PASSWORD_REQUIRE_SPECIAL', true);
define('PASSWORD_EXPIRY_DAYS', 90);

// 2FA configuration
define('OTP_LENGTH', 6);
define('OTP_VALIDITY', 300); // 5 minutes

// Lockout configuration
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOCKOUT_DURATION', 1800); // 30 minutes
```

### Service Configuration
```php
// In your service instantiation:
$authService = new MemberAuthenticationService($db, [
    'session_timeout' => SESSION_TIMEOUT,
    'password_min_length' => PASSWORD_MIN_LENGTH,
    'max_login_attempts' => MAX_LOGIN_ATTEMPTS,
    'otp_length' => OTP_LENGTH,
    'bcrypt_cost' => 12
]);
```

---

## 6. Entry Points

### Member Login Page
**URL**: `/member-login.php`

Features:
- Membership number (username) login
- Password input with show/hide toggle
- Remember me option
- Password reset link
- Responsive design

### Member Portal Dashboard
**URL**: `/member/dashboard.php` (after login)

Features:
- Member profile summary
- Savings accounts overview
- Active loans display
- Recent transactions
- Sidebar navigation
- Member menu

### Admin Dashboard Integration
To display member login status in admin:
```php
// Get member login statistics
$stmt = $db->prepare("
    SELECT 
        COUNT(DISTINCT member_id) as total_logins,
        COUNT(CASE WHEN login_timestamp > DATE_SUB(NOW(), INTERVAL 24 HOUR) THEN 1 END) as logins_24h,
        COUNT(CASE WHEN status = 'success' THEN 1 END) as successful_logins,
        COUNT(CASE WHEN status LIKE 'failed%' THEN 1 END) as failed_logins
    FROM member_login_audit
");
$stmt->execute();
$loginStats = $stmt->fetch(PDO::FETCH_ASSOC);
```

---

## 7. API Integration

### Member API Endpoints
Base URL: `/api/v1/member.php`

All endpoints require valid member session (`session_token` in `$_SESSION`).

#### Get Member Profile
```bash
GET /api/v1/member/profile
```

Response:
```json
{
    "success": true,
    "data": {
        "member_id": 1,
        "membership_number": "001",
        "full_name": "John Doe",
        "email": "john@example.com",
        "phone": "+254712345678",
        "user_id": 10,
        "join_date": "2024-01-15"
    }
}
```

#### Get Member Savings
```bash
GET /api/v1/member/savings
```

#### Get Member Loans
```bash
GET /api/v1/member/loans
```

#### Get Transactions
```bash
GET /api/v1/member/transactions?limit=50&offset=0
```

#### Download Statement
```bash
GET /api/v1/member/statements?account_id=1&start_date=2024-01-01&end_date=2024-12-31
```

#### Get Repayment Schedule
```bash
GET /api/v1/member/repayment-schedule
```

#### Change Password
```bash
POST /api/v1/member/password/change
Content-Type: application/json

{
    "current_password": "OldPass123!",
    "new_password": "NewPass456!"
}
```

#### Update Profile
```bash
POST /api/v1/member/profile/update
Content-Type: application/json

{
    "phone": "+254712345678",
    "email": "new@example.com",
    "address": "123 Main St"
}
```

#### Upload Profile Photo
```bash
POST /api/v1/member/profile/photo
Content-Type: multipart/form-data

photo: <image_file>
```

---

## 8. Security Considerations

### HTTPS Configuration
Required for production:

```php
// Force HTTPS
if (empty($_SERVER['HTTPS']) || $_SERVER['HTTPS'] === 'off') {
    header('Location: https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']);
    exit;
}

// Session security
ini_set('session.cookie_secure', true);
ini_set('session.cookie_httponly', true);
ini_set('session.cookie_samesite', 'Strict');
```

### Password Storage Verification
```php
// Verify passwords are bcrypted
$stmt = $db->prepare("
    SELECT user_id, password_hash FROM users 
    WHERE password_hash NOT LIKE '$2y$%'
");
$stmt->execute();
$nonBcrypted = $stmt->fetchAll();

if (!empty($nonBcrypted)) {
    // Alert: Found non-bcrypted passwords
}
```

### Session Validation
```php
// Validate session on every request
$sessionValidation = $authService->validateSession($_SESSION['session_token']);
if (!$sessionValidation['valid']) {
    session_destroy();
    header('Location: ../member-login.php?error=Session expired');
    exit;
}
```

---

## 9. Testing

### Unit Tests

1. **Create Member Credentials**
```php
// Test credential creation
$result = $authService->createMemberCredentials(
    1,
    '001',
    'Test Member',
    '+254712345678'
);

assert($result['success'] === true);
assert(!empty($result['user_id']));
assert(!empty($result['username']));
assert(!empty($result['password']);
```

2. **Member Login**
```php
// Test successful login
$result = $authService->memberLogin(
    '001',
    'TestPass123!',
    '192.168.1.1',
    'Mozilla/5.0...'
);

assert($result['success'] === true);
assert(!empty($result['session_token']));
```

3. **Password Change**
```php
// Test password change
$result = $authService->changePasswordFirstLogin(
    $userId,
    $memberId,
    'NewPass456!'
);

assert($result['success'] === true);
```

### Integration Tests

1. **Full Registration Flow**
   - Navigate to members/register.php
   - Fill form with test data
   - Verify member created
   - Check SMS queue for credentials
   - Verify user account created

2. **Full Login Flow**
   - Navigate to member-login.php
   - Enter membership number and password
   - Verify redirect to dashboard
   - Check session created
   - Verify audit log entry

3. **2FA Flow**
   - Enable 2FA in security settings
   - Logout and login
   - Verify OTP requested
   - Enter OTP
   - Verify login completes

### Security Tests

1. **SQL Injection**
```php
// Test SQL injection prevention
$result = $authService->memberLogin(
    "' OR '1'='1",
    "test",
    "127.0.0.1",
    "test"
);
assert($result['success'] === false);
```

2. **Brute Force Protection**
```php
// Test lockout after 5 failed attempts
for ($i = 0; $i < 5; $i++) {
    $authService->memberLogin('001', 'wrong', '127.0.0.1', 'test');
}

// 6th attempt should be locked
$result = $authService->memberLogin('001', 'correct', '127.0.0.1', 'test');
assert(strpos($result['message'], 'locked') !== false);
```

3. **Session Hijacking**
```php
// Test invalid session token
$result = $authService->validateSession('invalid_token');
assert($result['valid'] === false);
```

---

## 10. Troubleshooting

### Common Issues

#### "Undefined column 'user_id' in members table"
**Solution**: Run migration `004_member_authentication.sql`
```bash
php migrations/run_migrations.php
```

#### "OTP not sent to member phone"
**Check**:
1. SMS gateway configuration in `config/config.php`
2. Phone number format in sms_queue table
3. sms_queue processing script is running

**Verify**:
```sql
SELECT * FROM sms_queue WHERE status = 'pending' LIMIT 5;
```

#### "Login fails with 'Invalid username or password'"
**Check**:
1. Username is membership_number, not member_id
2. Password is correct (check audit log for failed attempts)
3. User account was created (check users table)
4. User status is 'active'

**Verify**:
```sql
SELECT u.user_id, u.username, u.status FROM users u
WHERE u.is_member = TRUE;
```

#### "Session expires immediately"
**Check**:
1. SESSION_TIMEOUT in config/constants.php
2. Session cookie settings (HTTPS required in production)
3. Session table has records (member_sessions table)

**Verify**:
```sql
SELECT * FROM member_sessions WHERE is_active = TRUE;
```

#### "2FA not working"
**Check**:
1. 2FA enabled in member_security_preferences
2. SMS queue is processing
3. OTP validity duration (default 5 minutes)

**Verify**:
```sql
SELECT * FROM member_otp_tokens ORDER BY created_at DESC LIMIT 1;
```

#### "Member portal shows blank/error page"
**Steps**:
1. Check PHP error logs
2. Verify member session token is valid
3. Check member_id in session matches member in database
4. Verify all member portal dependencies (functions.php, config.php)

---

## 11. Maintenance

### Daily Tasks
- Monitor login audit logs for suspicious activity
- Check SMS queue for pending messages
- Review failed login attempts
- Check for account lockouts

### Weekly Tasks
- Review password change requests
- Check 2FA enrollment status
- Validate session management
- Review API error logs

### Monthly Tasks
- Archive audit logs
- Review security metrics
- Update password expiry policies if needed
- Test disaster recovery procedures
- Audit member data access logs

### Quarterly Tasks
- Security assessment
- Penetration testing
- Vulnerability scanning
- Access control review
- Compliance audit

---

## 12. Scaling Considerations

### Performance
- Index member_id in audit tables for fast queries
- Archive old audit logs to separate storage
- Cache member data for faster dashboard load
- Implement read replicas for reporting

### High Availability
- Database replication for redundancy
- Load balancer for web servers
- Cache layer (Redis) for sessions
- Backup SMS gateway provider

### Infrastructure
```php
// Example session storage in Redis
$redis = new Redis();
$redis->connect('localhost', 6379);
ini_set('session.save_handler', 'redis');
ini_set('session.save_path', 'tcp://localhost:6379');
```

---

## 13. Useful SQL Queries

### Get login activity for member
```sql
SELECT * FROM member_login_audit
WHERE member_id = ?
ORDER BY login_timestamp DESC
LIMIT 10;
```

### Find suspicious login patterns
```sql
SELECT member_id, COUNT(*) as attempts, MAX(login_timestamp) as last_attempt
FROM member_login_audit
WHERE status != 'success'
AND login_timestamp > DATE_SUB(NOW(), INTERVAL 1 HOUR)
GROUP BY member_id
HAVING attempts > 3;
```

### Check credential changes
```sql
SELECT * FROM member_login_credentials_history
WHERE member_id = ?
ORDER BY created_at DESC;
```

### Get members without user accounts
```sql
SELECT m.* FROM members m
LEFT JOIN users u ON m.user_id = u.user_id
WHERE m.user_id IS NULL;
```

### Session statistics
```sql
SELECT 
    COUNT(*) as total_sessions,
    COUNT(CASE WHEN is_active = TRUE THEN 1 END) as active_sessions,
    AVG(TIMESTAMPDIFF(SECOND, created_at, last_activity)) as avg_session_duration
FROM member_sessions;
```

---

## 14. Rollback Procedures

### If Something Goes Wrong

1. **Revert Authentication**
   - Back up current database
   - Run: `DROP TABLE member_*, password_reset_tokens;`
   - Revert user/members table alterations

2. **Disable 2FA**
   - Set all `two_factor_enabled = FALSE` in member_security_preferences
   - Members can still use password login

3. **Reset Member Password**
   - Generate new temporary password
   - Send via SMS/email
   - Force password change on next login

---

## 15. Support & Documentation

### Key Files Reference
| File | Purpose |
|------|---------|
| `migrations/004_member_authentication.sql` | Database schema |
| `app/Services/MemberAuthenticationService.php` | Core service logic |
| `member/auth-middleware.php` | Authentication middleware |
| `member-login.php` | Member login page |
| `member/dashboard.php` | Portal dashboard |
| `api/v1/member.php` | REST API endpoints |
| `MEMBER_AUTHENTICATION_SECURITY.md` | Security guidelines |

### External Resources
- [OWASP Authentication Cheat Sheet](https://cheatsheetseries.owasp.org/cheatsheets/Authentication_Cheat_Sheet.html)
- [PHP Security Guide](https://www.php.net/manual/en/security.php)
- [NIST Password Guidelines](https://pages.nist.gov/800-63-3/sp800-63b.html)

---

**Implementation Guide Version**: 1.0  
**Last Updated**: 2024  
**For Questions**: Contact Development Team
