# SACCO Management System - Complete Architecture

## 1. SYSTEM OVERVIEW

This is a production-grade SACCO (Savings and Credit Cooperative Organization) system designed for civil servants in Uganda (Rakai & Kyotera Districts).

### Key Statistics
- **Membership Range**: 001-700 (3-digit numeric format)
- **Member Base**: Government workers/civil servants
- **Supported Loan Types**: 2 (Salary Loans @ 26% annual, Business Loans @ 5% monthly)
- **Core Modules**: 12 major modules

## 2. ARCHITECTURE LAYERS

```
┌─────────────────────────────────────────────────────────────┐
│                      PRESENTATION LAYER                      │
│  (Member Portal, Admin Dashboard, Mobile App, API Gateway)   │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                      SERVICE LAYER                           │
│  (Business Logic, Validation, Calculations)                  │
│  - LoanService          - PaymentService                     │
│  - MemberService        - InterestCalculationService         │
│  - SalaryDeductionService  - ReportService                   │
│  - AuthenticationService   - NotificationService             │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                      DATA ACCESS LAYER                       │
│  (DAO/Repository Pattern, Database Operations)               │
│  - MemberRepository     - LoanRepository                     │
│  - SavingsRepository    - LedgerRepository                   │
│  - TransactionRepository  - AuditRepository                  │
└─────────────────────────────────────────────────────────────┘
                              ↓
┌─────────────────────────────────────────────────────────────┐
│                      DATABASE LAYER                          │
│  (MySQL, InnoDB, ACID Compliance)                            │
└─────────────────────────────────────────────────────────────┘
```

## 3. DATABASE ENTITY RELATIONSHIP DIAGRAM (ERD)

### Core Entities:

**Members**
- membership_number (001-700, 3-digit unique)
- national_id, full_name, phone
- employer_id → Employers
- district_id → Districts
- Status: active, inactive, deceased, suspended

**Savings & Shares**
- Shares (share_accounts) - equity ownership
- Savings (savings_accounts) - multiple types
- Transactions recorded for each

**Loans**
- loans (main table)
  - member_id → Members
  - product_id → loan_products
  - status: applied, approved, disbursed, completed, defaulted
  - outstanding_balance, interest_accrued, penalty_accrued

- loan_repayment_schedule (pre-calculated)
  - installment_number, due_date
  - principal_due, interest_due, penalty_due
  - status: pending, partial, paid, overdue

- loan_repayments (actual payments)
  - payment_date, amount_paid
  - payment_method, reference_number
  - allocation: principal_paid, interest_paid, penalty_paid

**Accounting/Ledger**
- ledger_entries (double-entry system)
  - debit/credit columns
  - account_type: shares, savings, loans, interest, penalties
  - posted_by, approved_by
  
- chart_of_accounts
- journal_entries
- cash_book
- trial_balance, balance_sheet

**Salary Deductions**
- salary_deduction_batches (monthly payroll)
- salary_deduction_details (per member)
  - matching_status: matched, unmatched, ambiguous
  - allocation: interest, principal, savings, shares

**Standing Orders**
- standing_orders (recurring monthly deductions)
  - bank_reference_number
  - standing_order_postings (monthly receipts)

**Audit & Security**
- audit_logs (all transactions)
- sessions (user logins)
- users (role-based)

## 4. KEY BUSINESS RULES

### Membership Rules
- Membership numbers: 001-700, must be numeric, 3-digit format
- Unique phone per member
- KYC verification required before account activation
- Status lifecycle: pending → active → inactive/suspended/deceased

### Loan Rules

#### Salary Loans (26% Annual)
- Interest rate: 26% per year (2.17% monthly)
- Repayment: 6-24 months via salary deductions
- Monthly salary deduction: max 10% of gross salary
- Interest accrued monthly
- Requires 2-5 guarantors
- Must have 3+ months savings history
- Processing fee: 2% of amount

#### Business Loans (5% Monthly)
- Interest rate: 5% per month (60% annual)
- Maximum duration: 6 months
- Requires 2-3 guarantors
- Processing fee: 2.5% of amount
- Monthly compounding interest

### Interest Calculation
- **Salary Loans**: Monthly compound interest (26% ÷ 12 = 2.17%)
- **Savings**: Monthly posting (configurable %)
- **Business Loans**: Daily accrual, monthly compounding (5% monthly)
- Calculated on reducing balance for loans
- Posted via ledger system monthly

### Repayment Logic
1. **Allocation Order**: Interest first → Principal second → Penalties third
2. **Salary Deductions**: Automatic via payroll
3. **Standing Orders**: Bank-initiated monthly transfers
4. **Payment Methods**:
   - Salary deduction (primary for salary loans)
   - Standing order (bank-automated)
   - Direct bank deposit
   - Cash payment
   - Mobile money (MTN, Airtel)

### Default/Overdue Management
- Grace period: 7 days after due date
- Penalty accrual: 0.5% daily (max 5% monthly)
- Status transitions: pending → overdue → default
- Defaulters list tracking
- Legal action triggers at 90+ days

## 5. CORE SERVICES & WORKFLOWS

### 5.1 Member Management Service
```php
MemberService::
  - registerMember($memberData) → creates member, KYC pending
  - updateMemberProfile($memberId, $data) → audit logged
  - validateMemberEligibility($memberId) → checks requirements
  - enrollBiometric($memberId, $biometricData)
  - activateMember($memberId) → KYC verified, account active
  - suspendMember($memberId, $reason)
  - getMemberStatement($memberId, $dateRange)
```

### 5.2 Loan Service
```php
LoanService::
  - applyForLoan($memberId, $productId, $amount, $purpose)
  - reviewLoanApplication($loanId, $recommendation)
  - approveLoan($loanId, $approvedAmount, $approvedBy)
  - disburseLoan($loanId, $method, $bankDetails)
  - calculateRepaymentSchedule($amount, $rate, $months)
  - generateLoanStatement($loanId)
  - calculateLoanBalance($loanId, $asOfDate)
  - getDefaulters($status)
```

### 5.3 Payment Service
```php
PaymentService::
  - processLoanRepayment($loanId, $amount, $method, $reference)
  - recordSavingsDeposit($memberId, $amount, $method)
  - recordSavingsWithdrawal($memberId, $accountId, $amount)
  - processPartialPayment($loanId, $amount) → splits: interest, principal, penalty
  - allocateRepayment($loanId, $amount) → returns allocation breakdown
  - reverseTransaction($transactionId, $reason) → audit trail, reversal entry
  - generateReceipt($transactionId) → receipt_number auto-increment
```

### 5.4 Salary Deduction Service
```php
SalaryDeductionService::
  - uploadPayrollBatch($employerId, $csvFile) → stores batch
  - validateBatchData($batchId) → file format, totals, records
  - matchMembersInBatch($batchId) → fuzzy matching algorithm
  - reviewUnmatchedRecords($batchId) → manual matching interface
  - processBatch($batchId) → creates salary_deduction_details
  - allocateDeductions($deductionDetailId) → splits to interest/principal/savings/shares
  - postBatchToLedger($batchId) → atomically posts all transactions
  - generateBatchReport($batchId) → summary, failed records, reconciliation
```

### 5.5 Ledger Service (Double-Entry Accounting)
```php
LedgerService::
  - postTransaction($entry) → debit & credit simultaneous, balanced
  - postLoanDisbursement($loanId, $amount)
  - postMonthlyInterest($month) → batch posting
  - postSalaryDeduction($deductionDetailId)
  - postRepayment($repaymentId)
  - reverseEntry($entryId, $reason) → creates reversal entries
  - reconcileEntries($dateRange, $expectedBalance)
  - generateTrialBalance($asOfDate)
  - generateBalanceSheet($asOfDate)
  - generateIncomeStatement($periodStart, $periodEnd)
```

### 5.6 Interest Calculation Service
```php
InterestCalculationService::
  - calculateMonthlyInterest($month) → for all active loans/savings
  - accrueLoanInterest($loanId, $throughDate)
  - postInterestToLedger($calculationBatch) → bulk posting
  - calculateDailyInterest($loanId, $date)
  - applyPenalties($loanId, $daysOverdue)
  - getInterestBreakdown($loanId, $month)
```

### 5.7 Standing Order Service
```php
StandingOrderService::
  - createStandingOrder($memberId, $amount, $frequency, $bankRef)
  - reconcileStandingOrders($month) → expected vs received
  - processStandingOrderReceipt($standingOrderId, $amount, $bankRef)
  - markMissedStandingOrder($standingOrderId, $month)
  - updateStandingOrder($standingOrderId, $newData)
  - cancelStandingOrder($standingOrderId, $reason)
  - generateStandingOrderReport($period)
```

### 5.8 Report Service
```php
ReportService::
  - generateMemberStatement($memberId, $dateRange) → PDF/Excel
  - generateLoanSchedule($loanId) → with amortization
  - generateDefaultersReport($asOfDate)
  - generateSavingsReport($period)
  - generatePortfolioAnalysis()
  - generateFinancialSummary($period)
  - generateDaySheet($date)
  - generateTrialBalance($asOfDate)
  - generateBalanceSheet($asOfDate)
  - generateIncomeExpenseStatement($period)
```

### 5.9 Authentication Service
```php
AuthenticationService::
  - login($username, $password, $ipAddress) → creates session
  - validateBiometric($memberId, $biometricData)
  - validateOTP($memberId, $otp, $method)
  - enableTwoFactor($userId, $method)
  - logout($sessionId)
  - validateSession($sessionId) → checks expiry, IP
  - lockAccount($userId, $reason) → after failed attempts
```

### 5.10 Notification Service
```php
NotificationService::
  - sendSMSNotification($memberId, $message, $type) → queues SMS
  - sendEmailNotification($memberId, $message, $subject)
  - sendLoanApprovalNotification($loanId)
  - sendPaymentReminderSMS($memberId, $loanId)
  - sendOverdueWarning($loanId)
  - broadcastAnnouncement($message, $targetGroup)
  - processSMSQueue() → sends pending, handles delivery
```

## 6. API ENDPOINTS

### Authentication
- `POST /api/auth/login` - Username/password authentication
- `POST /api/auth/biometric` - Biometric verification
- `POST /api/auth/otp/request` - Request OTP
- `POST /api/auth/otp/verify` - Verify OTP
- `POST /api/auth/logout` - Logout
- `GET /api/auth/session` - Current session info

### Members
- `GET /api/members` - List members (paginated)
- `POST /api/members` - Register new member
- `GET /api/members/{id}` - Member details
- `PUT /api/members/{id}` - Update member profile
- `GET /api/members/{id}/statement` - Member statement
- `GET /api/members/{id}/balances` - Account balances
- `POST /api/members/{id}/biometric` - Enroll biometric

### Loans
- `POST /api/loans/apply` - Apply for loan
- `GET /api/loans/{id}` - Loan details
- `PUT /api/loans/{id}/approve` - Approve loan
- `PUT /api/loans/{id}/disburse` - Disburse loan
- `GET /api/loans/{id}/schedule` - Repayment schedule
- `GET /api/loans/{id}/balance` - Current balance
- `POST /api/loans/{id}/repay` - Record repayment

### Savings & Shares
- `GET /api/accounts/savings` - Savings accounts
- `POST /api/accounts/savings/deposit` - Record deposit
- `POST /api/accounts/savings/withdraw` - Record withdrawal
- `GET /api/accounts/shares` - Share account

### Salary Deductions
- `POST /api/salary-deductions/upload` - Upload payroll batch
- `GET /api/salary-deductions/batches/{id}` - Batch details
- `POST /api/salary-deductions/batches/{id}/process` - Process batch
- `GET /api/salary-deductions/batches/{id}/report` - Batch report

### Standing Orders
- `POST /api/standing-orders` - Create standing order
- `GET /api/standing-orders/{id}` - Standing order details
- `POST /api/standing-orders/{id}/receipt` - Record receipt

### Reports
- `GET /api/reports/defaulters` - Defaulters list
- `GET /api/reports/day-sheet` - Day sheet
- `GET /api/reports/trial-balance` - Trial balance
- `GET /api/reports/balance-sheet` - Balance sheet
- `GET /api/reports/income-statement` - Income statement
- `GET /api/reports/member-statements` - Member statements bulk

## 7. VALIDATION RULES

### Membership Number
- Format: 3-digit numeric (001-700)
- Unique, immutable
- Auto-generated or manual assignment

### National ID
- Unique per member
- Format validation per Uganda standards
- Verified during KYC

### Phone Number
- Unique per member
- Uganda format: +256XXXXXXXXX or 0XXXXXXXXX
- Used for SMS notifications and 2FA

### Loan Amounts
- Salary Loans: UGX 100,000 - 5,000,000
- Business Loans: UGX 50,000 - 1,000,000

### Savings Minimum
- Opening balance: configurable (typically UGX 10,000)

### Interest Rates
- Salary Loans: Fixed at 26% annual
- Business Loans: Fixed at 5% monthly
- Savings: Configurable (default 5% annual)

## 8. SECURITY IMPLEMENTATION

### Authentication Methods
1. **Username/Password** (MD5 hash → bcrypt recommended)
2. **Biometric** (fingerprint template matching)
3. **OTP via SMS** (6-digit, 5-minute expiry)
4. **Two-Factor Authentication** (SMS or authenticator app)

### Authorization
- **Role-Based Access Control (RBAC)**
  - Admin: full system access
  - Manager: operational oversight
  - Accountant: ledger, reports, reconciliation
  - Loan Officer: loan processing, approvals
  - Cashier: transaction posting
  - Member: portal access (read-only for most)
  - Audit: read-only audit logs

### Encryption
- Passwords: bcrypt with salt
- Biometric data: AES-256 encryption
- API tokens: JWT with expiry
- Database: SSL/TLS for connections
- Sensitive data: encrypted at rest

### Audit Trail
- All user actions logged in audit_logs
- Before/after values recorded (JSON)
- IP address and user agent captured
- Timestamp with UTC conversion
- Immutable logging (append-only)

### Session Management
- Session expiry: 30 minutes inactivity
- IP validation for security
- User agent validation
- Concurrent session limits
- Secure cookie attributes

### Rate Limiting
- API endpoints: 100 requests/minute per user
- SMS: max 5 per hour per member
- Failed login: lock after 5 attempts

## 9. ERROR HANDLING & VALIDATION

### Validation Layers
1. **Client-side**: HTML5, JavaScript validation
2. **API**: Input sanitization, type checking
3. **Business Logic**: Domain rules enforcement
4. **Database**: Constraints, foreign keys

### Error Responses
```json
{
  "status": "error",
  "code": "VALIDATION_ERROR",
  "message": "Membership number already exists",
  "errors": {
    "membership_number": ["Must be unique 3-digit number"]
  }
}
```

## 10. PERFORMANCE OPTIMIZATION

### Database
- Indexed queries: member_id, status, dates
- Composite indexes: (entry_date, member_id)
- Partitioning: Large tables by year
- Archive strategy: Move old transactions to history

### Caching
- Cache key configurations
- Cache member balances (5-minute TTL)
- Cache loan products and rates
- Cache district and employer lists

### Batch Operations
- Salary deduction processing: atomic batch
- Interest calculation: monthly batch
- Report generation: background queue
- SMS sending: asynchronous queue

## 11. COMPLIANCE & AUDIT

### Financial Compliance
- Double-entry accounting (IFRS/IAS compliant)
- Audit trail for all transactions
- Monthly reconciliation
- Annual financial statements
- Loan loss provisioning

### Data Protection
- GDPR-compliant data retention
- Personal data encryption
- Access control enforcement
- Data backup (daily, encrypted)
- Disaster recovery plan

### Regulatory (Uganda)
- SACCO Societies Act compliance
- BOU (Bank of Uganda) guidelines
- Tax compliance (VAT, income tax)
- NSSF contribution tracking
- Salary deduction accuracy

## 12. DEPLOYMENT & INFRASTRUCTURE

### Technology Stack
- **Backend**: PHP 7.4+ with composer
- **Database**: MySQL 5.7+ (InnoDB)
- **API**: RESTful JSON
- **Frontend**: HTML5, CSS3, JavaScript (Bootstrap, jQuery)
- **Mobile**: Native Android/iOS or React Native
- **SMS**: Africa's Talking, Twilio
- **Biometric**: Neurotechnology VeriLook SDK

### Server Requirements
- Minimum: 4GB RAM, 100GB SSD, Dual-core processor
- Recommended: 8GB RAM, 500GB SSD, Quad-core, CDN

### Backup Strategy
- Database: Daily incremental, weekly full
- Files: Daily to cloud (AWS S3, Google Cloud)
- Retention: 90 days encrypted
- Disaster recovery: RTO 4 hours, RPO 1 hour

## 13. TESTING STRATEGY

### Unit Tests
- Service method testing
- Calculation accuracy
- Validation rules

### Integration Tests
- API endpoint testing
- Ledger posting accuracy
- Transaction atomicity

### Load Testing
- Concurrent user limits
- Batch processing performance
- Report generation speed

### Security Testing
- SQL injection prevention
- XSS protection
- CSRF token validation
- Authentication bypass attempts

## 14. MAINTENANCE & MONITORING

### Logging
- Application logs (daily rotation)
- Database query logs
- API request/response logs
- Error logging to file + Sentry

### Monitoring
- Server CPU, memory, disk usage
- Database query performance
- API response times
- SMS delivery rates
- Error rates and alerts

### Backup Verification
- Weekly test restores
- Integrity checks
- Encryption verification

---

**Version**: 1.0.0
**Last Updated**: 2026-05-17
**Status**: Production Architecture
