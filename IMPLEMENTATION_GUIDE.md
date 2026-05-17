# SACCO System - Implementation Guide & Best Practices

## PART 1: DATABASE SETUP

### Step 1: Create Database

```bash
# Login to MySQL
mysql -u root -p

# Run the schema
SOURCE config/database_refactored.sql;

# Verify tables created
USE sacco_system;
SHOW TABLES;
```

### Step 2: Configure Database Connection

Update [config/db_connection.php](config/db_connection.php):

```php
define('DB_HOST', 'localhost');
define('DB_NAME', 'sacco_system');
define('DB_USER', 'sacco_user');
define('DB_PASS', 'strong_password_here');
```

## PART 2: INSTALLATION STRUCTURE

### Directory Organization

```
d:\wamp64\www\SACCO\
├── app/
│   ├── Models/
│   │   └── Entities.php                 # Entity classes
│   ├── Services/
│   │   ├── LoanService.php
│   │   ├── LedgerService.php
│   │   ├── InterestCalculationService.php
│   │   ├── SalaryDeductionService.php
│   │   ├── AuditAuthNotificationServices.php
│   │   ├── MemberService.php
│   │   ├── ReportService.php
│   │   └── ValidatorService.php
│   ├── Repositories/
│   │   ├── MemberRepository.php
│   │   ├── LoanRepository.php
│   │   ├── SavingsRepository.php
│   │   └── LedgerRepository.php
│   └── Utils/
│       ├── Calculator.php
│       ├── Formatter.php
│       └── Validator.php
├── api/
│   └── v1/
│       ├── Router.php
│       ├── endpoints/
│       │   ├── members.php
│       │   ├── loans.php
│       │   ├── savings.php
│       │   ├── reports.php
│       │   └── auth.php
│       └── index.php
├── config/
│   ├── database_refactored.sql          # Enhanced schema
│   ├── db_connection.php
│   ├── constants.php
│   └── session_config.php
├── public/
│   ├── member-portal/                   # Member UI
│   ├── admin-dashboard/                 # Admin UI
│   ├── css/
│   ├── js/
│   └── uploads/
├── tests/
│   ├── unit/
│   ├── integration/
│   └── load-test/
├── migrations/
│   ├── 001_create_tables.sql
│   ├── 002_add_indexes.sql
│   ├── 003_seed_initial_data.sql
│   └── migrate.php
├── ARCHITECTURE.md                      # System design
├── IMPLEMENTATION_GUIDE.md              # This file
└── README.md
```

## PART 3: CORE WORKFLOWS

### 3.1 Member Registration Workflow

```
1. Member fills registration form (HTML form or mobile app)
   ├─ Validate inputs (phone, national ID, membership number)
   ├─ Check uniqueness (phone, national ID, membership number)
   └─ Generate 3-digit membership number (001-700)

2. Create member record
   ├─ Status: active
   ├─ KYC status: pending
   ├─ Biometric: not_enrolled
   └─ Account status: normal

3. Enroll biometric
   ├─ Capture fingerprint
   ├─ Store template (encrypted)
   └─ Set biometric_enrolled = TRUE

4. Verify KYC
   ├─ Check documents
   ├─ Verify ID
   └─ Set KYC status: verified

5. Create default accounts
   ├─ Monthly savings account
   ├─ Share capital account
   └─ Set initial interest rates
```

### 3.2 Loan Application Workflow

```
1. Member applies for loan
   ├─ Select product (Salary or Business)
   ├─ Enter amount
   ├─ Provide purpose
   └─ Status: applied

2. System checks eligibility
   ├─ Active member
   ├─ 3+ months savings history
   ├─ No active loans
   ├─ Biometric enrolled
   └─ If fail: reject application

3. Loan officer reviews
   ├─ Check member credit history
   ├─ Assess purpose
   ├─ Approve/Reject
   └─ Status: approved or rejected

4. Approve loan
   ├─ Set approved amount
   ├─ Generate repayment schedule
   └─ Status: approved

5. Disburse funds
   ├─ Transfer to member account
   ├─ Deduct processing fee
   ├─ Post to ledger
   └─ Status: disbursed

6. Send SMS notification
   ├─ Loan approved amount
   ├─ First payment date
   └─ Contact for questions
```

### 3.3 Salary Deduction Workflow

```
1. Upload payroll batch
   ├─ Employer uploads CSV
   ├─ Validate format (employee_name, membership_number, amount)
   ├─ Parse and store as batch
   └─ Status: uploaded

2. Match members
   ├─ Try exact match on membership_number
   ├─ Try fuzzy match on name (SOUNDEX)
   ├─ For unmatched: show manual matching UI
   └─ Matching score calculation

3. Allocate deductions
   For each matched deduction:
   ├─ Get member's active loans
   ├─ Allocate to interest (if due)
   ├─ Allocate to principal
   ├─ Remainder to savings
   └─ If shares required: allocate small percentage

4. Post to ledger
   ├─ Create batch entry
   ├─ Debit: Bank account (salary in)
   ├─ Credit: Interest income, Loans, Savings, Shares
   ├─ Verify debit = credit
   └─ All-or-nothing transaction

5. Generate report
   ├─ Total deducted
   ├─ Successfully matched
   ├─ Failed/unmatched
   ├─ Allocation breakdown
   └─ Bank reconciliation details
```

### 3.4 Monthly Interest Calculation Workflow

```
1. Run interest calculation batch (monthly, last business day)

2. For each ACTIVE LOAN:
   ├─ Salary Loan: 26% annual = 2.17% monthly
   │  └─ Interest = Principal_Balance * 2.17%
   ├─ Business Loan: 5% monthly
   │  └─ Interest = Principal_Balance * 5%
   └─ Accrue interest

3. For each ACTIVE SAVINGS:
   ├─ Default: 5% annual = 0.417% monthly
   ├─ Interest = Balance * Monthly_Rate
   └─ Credit to account

4. Post to ledger
   ├─ Debit: Interest Receivable (Asset)
   ├─ Credit: Interest Income
   ├─ Verify balanced
   └─ Post atomically

5. Apply penalties for OVERDUE loans
   ├─ 7 days grace (no penalty)
   ├─ After grace: 0.5% daily
   ├─ Cap: 5% per month
   └─ Add to penalty_accrued

6. Update loan balances
   └─ outstanding_balance = principal + interest + penalties
```

### 3.5 Loan Repayment Allocation

```
Payment Received: UGX 500,000

Allocation Order (Priority):
1. PENALTIES FIRST
   ├─ Overdue penalties due: UGX 50,000
   ├─ Allocate: UGX 50,000
   └─ Remaining: UGX 450,000

2. INTEREST SECOND
   ├─ Accrued interest: UGX 150,000
   ├─ Allocate: UGX 150,000
   └─ Remaining: UGX 300,000

3. PRINCIPAL LAST
   ├─ Principal balance: UGX 1,000,000
   ├─ Allocate: UGX 300,000
   └─ Remaining: 0 (fully allocated)

Result:
├─ Penalty paid: UGX 50,000
├─ Interest paid: UGX 150,000
├─ Principal paid: UGX 300,000
├─ New principal balance: UGX 700,000
└─ New interest accrued: UGX 0 (after allocation)
```

## PART 4: VALIDATION RULES

### Membership Number Validation

```php
// Must be 3-digit numeric, unique
function validateMembershipNumber($number) {
    if (!preg_match('/^\d{3}$/', $number)) {
        return ['valid' => false, 'error' => 'Must be 3 digits'];
    }
    
    if ($number < 1 || $number > 700) {
        return ['valid' => false, 'error' => 'Must be between 001 and 700'];
    }
    
    // Check uniqueness
    $stmt = $db->prepare("SELECT COUNT(*) FROM members WHERE membership_number = ?");
    $stmt->execute([$number]);
    if ($stmt->fetchColumn() > 0) {
        return ['valid' => false, 'error' => 'Membership number already exists'];
    }
    
    return ['valid' => true];
}
```

### Phone Number Validation

```php
// Uganda format: +256XXXXXXXXX or 0XXXXXXXXX
function validatePhoneNumber($phone) {
    if (preg_match('/^(\+256|0)[789]\d{8}$/', $phone)) {
        return ['valid' => true];
    }
    return ['valid' => false, 'error' => 'Invalid Uganda phone number'];
}
```

### Loan Amount Validation

```php
function validateLoanAmount($amount, $productId) {
    $product = getLoanProduct($productId);
    
    if ($amount < $product['min_amount']) {
        return ['valid' => false, 'error' => "Minimum: {$product['min_amount']}"];
    }
    
    if ($amount > $product['max_amount']) {
        return ['valid' => false, 'error' => "Maximum: {$product['max_amount']}"];
    }
    
    return ['valid' => true];
}
```

### Interest Rate Validation

```php
// Fixed rates per product type
const SALARY_LOAN_RATE = 26.0;      // 26% annual
const BUSINESS_LOAN_RATE = 5.0;     // 5% monthly (60% annual)

function validateInterestRate($rate, $productType) {
    if ($productType === 'salary_loan' && $rate != SALARY_LOAN_RATE) {
        return ['valid' => false, 'error' => 'Salary loan rate must be 26%'];
    }
    
    if ($productType === 'business_loan' && $rate != BUSINESS_LOAN_RATE) {
        return ['valid' => false, 'error' => 'Business loan rate must be 5%'];
    }
    
    return ['valid' => true];
}
```

## PART 5: SECURITY IMPLEMENTATION

### Password Hashing

```php
// Store passwords with bcrypt
$hash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 12]);

// Verify
if (password_verify($inputPassword, $hash)) {
    // Correct password
}
```

### Session Security

```php
// Session configuration
ini_set('session.use_strict_mode', 1);
ini_set('session.use_only_cookies', 1);
ini_set('session.cookie_httponly', 1);
ini_set('session.cookie_secure', 1);  // HTTPS only
ini_set('session.cookie_samesite', 'Strict');
ini_set('session.gc_maxlifetime', 1800);  // 30 minutes
```

### Biometric Security

```php
// Store encrypted biometric templates
class BiometricService {
    public static function enroll($memberId, $biometricTemplate) {
        $encrypted = openssl_encrypt($biometricTemplate, 'AES-256-CBC', KEY, 0, IV);
        $stmt = $db->prepare("UPDATE members SET biometric_template = ? WHERE member_id = ?");
        $stmt->execute([$encrypted, $memberId]);
    }
    
    public static function verify($memberId, $capturedTemplate) {
        $stmt = $db->prepare("SELECT biometric_template FROM members WHERE member_id = ?");
        $stmt->execute([$memberId]);
        $stored = $stmt->fetchColumn();
        
        $decrypted = openssl_decrypt($stored, 'AES-256-CBC', KEY, 0, IV);
        return $this->compareBiometrics($decrypted, $capturedTemplate);
    }
}
```

### SQL Injection Prevention

```php
// Use prepared statements ALWAYS
$stmt = $db->prepare("SELECT * FROM members WHERE membership_number = ? AND status = ?");
$stmt->execute([$membershipNumber, 'active']);

// Never concatenate user input into queries
// WRONG: "SELECT * FROM members WHERE id = $id"
// RIGHT: Use prepared statements
```

### CSRF Protection

```php
// Generate CSRF token
$token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $token;

// Verify token on form submission
if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('CSRF token validation failed');
}
```

## PART 6: DEPLOYMENT CHECKLIST

### Pre-Production

- [ ] Database backup strategy configured
- [ ] SSL/TLS certificate installed
- [ ] Environment variables set (.env file)
- [ ] Error logging configured (not to screen)
- [ ] API rate limiting enabled
- [ ] Database credentials not in code
- [ ] Sessions configured for security
- [ ] Backup storage (S3/Google Cloud) configured
- [ ] SMS provider credentials tested
- [ ] Biometric SDK installed and tested

### Testing

- [ ] Unit tests passing
- [ ] Integration tests passing
- [ ] Load testing (1000+ concurrent users)
- [ ] Security audit completed
- [ ] Penetration testing completed
- [ ] Data migration testing
- [ ] Backup/restore testing

### Production Setup

```bash
# Create application user
sudo useradd -m -s /bin/bash sacco

# Set permissions
sudo chown -R sacco:sacco /var/www/sacco
sudo chmod -R 750 /var/www/sacco

# Setup auto-backups (cron)
0 2 * * * /scripts/backup-database.sh

# Setup interest calculation cron (last business day of month)
0 23 L * * /scripts/calculate-monthly-interest.sh

# Setup SMS queue processor
*/5 * * * * /scripts/process-sms-queue.sh

# Setup session cleanup
0 * * * * /scripts/cleanup-sessions.sh
```

## PART 7: MONITORING & MAINTENANCE

### Key Metrics

- **Transaction Success Rate**: Should be >99.5%
- **API Response Time**: Target <500ms for 95th percentile
- **Database Query Time**: Target <200ms average
- **SMS Delivery Rate**: Target >98%
- **Uptime**: Target 99.9%

### Daily Tasks

- [ ] Check error logs
- [ ] Verify SMS queue processing
- [ ] Confirm database backups completed
- [ ] Monitor server resources (CPU, memory, disk)
- [ ] Verify outstanding balances accuracy

### Monthly Tasks

- [ ] Generate financial reports
- [ ] Review audit logs for anomalies
- [ ] Reconcile bank accounts
- [ ] Interest calculation verification
- [ ] Defaulters review meeting

### Quarterly Tasks

- [ ] Database optimization (ANALYZE, OPTIMIZE tables)
- [ ] Backup integrity testing
- [ ] Security audit
- [ ] Capacity planning
- [ ] System performance review

## PART 8: TROUBLESHOOTING

### Common Issues

**Issue**: Membership number allocation
**Solution**: Use auto-increment in separate table, fetch next available

**Issue**: Interest calculation rounding errors
**Solution**: Use DECIMAL(12,2), round at final step, test extensively

**Issue**: Salary deduction member matching failures
**Solution**: Implement fuzzy matching algorithm (SOUNDEX), provide manual override UI

**Issue**: Ledger entries unbalanced
**Solution**: Always verify debit = credit before posting, use transactions

**Issue**: OTP not received
**Solution**: Check SMS provider API, verify phone number format, test directly

---

**Last Updated**: 2026-05-17
**Version**: 1.0.0
**Status**: Production Ready
