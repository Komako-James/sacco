-- Enhanced SACCO Management System Database Schema
-- Production-grade, audit-compliant system for civil servants
-- Uganda-focused SACCO (Rakai & Kyotera Districts)

DROP DATABASE IF EXISTS sacco_system;
CREATE DATABASE sacco_system CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE sacco_system;

-- ============================================================================
-- CORE SYSTEM TABLES
-- ============================================================================

-- Users with role-based access
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15),
    role ENUM('admin','manager','accountant','loan_officer','cashier','member','audit') NOT NULL,
    status ENUM('active','inactive','suspended','deleted') DEFAULT 'active',
    last_login TIMESTAMP NULL,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    biometric_data LONGBLOB,
    biometric_enabled BOOLEAN DEFAULT FALSE,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_method ENUM('sms','email','app') DEFAULT 'sms',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_username (username),
    INDEX idx_status (status),
    INDEX idx_role (role)
);

-- Audit trail for all activities
CREATE TABLE audit_logs (
    log_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    entity_type VARCHAR(50) NOT NULL,
    entity_id INT,
    old_values JSON,
    new_values JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    status ENUM('success','failure') DEFAULT 'success',
    error_message TEXT,
    timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_entity (entity_type, entity_id),
    INDEX idx_timestamp (timestamp),
    INDEX idx_action (action)
);

-- Session management
CREATE TABLE sessions (
    session_id VARCHAR(64) PRIMARY KEY,
    user_id INT NOT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    expires_at TIMESTAMP,
    is_active BOOLEAN DEFAULT TRUE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_expires (expires_at)
);

-- ============================================================================
-- MEMBER MANAGEMENT TABLES
-- ============================================================================

-- Districts - Raaki and Kyotera
CREATE TABLE districts (
    district_id INT PRIMARY KEY AUTO_INCREMENT,
    district_name VARCHAR(50) NOT NULL UNIQUE,
    code VARCHAR(10) UNIQUE,
    region VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Employers/Workstations
CREATE TABLE employers (
    employer_id INT PRIMARY KEY AUTO_INCREMENT,
    employer_name VARCHAR(150) NOT NULL,
    employer_code VARCHAR(20) UNIQUE NOT NULL,
    district_id INT,
    category VARCHAR(50), -- Ministry, Local Government, Other
    contact_person VARCHAR(100),
    phone VARCHAR(15),
    email VARCHAR(100),
    address TEXT,
    payroll_contact_email VARCHAR(100),
    payroll_contact_phone VARCHAR(15),
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (district_id) REFERENCES districts(district_id),
    INDEX idx_code (employer_code),
    INDEX idx_district (district_id)
);

-- Members - Core table with 3-digit membership numbers (001-700)
CREATE TABLE members (
    member_id INT PRIMARY KEY AUTO_INCREMENT,
    membership_number VARCHAR(3) UNIQUE NOT NULL,  -- 001 to 700 (numeric format)
    national_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL UNIQUE,
    email VARCHAR(100),
    date_of_birth DATE NOT NULL,
    gender ENUM('M','F','Other') NOT NULL,
    employer_id INT,
    district_id INT NOT NULL,
    postal_address TEXT,
    residential_address TEXT,
    next_of_kin_name VARCHAR(100),
    next_of_kin_phone VARCHAR(15),
    next_of_kin_relationship VARCHAR(50),
    join_date DATE NOT NULL,
    resignation_date DATE,
    status ENUM('active','inactive','deceased','suspended') DEFAULT 'active',
    account_status ENUM('normal','restricted','frozen') DEFAULT 'normal',
    profile_photo_path VARCHAR(255),
    signature_path VARCHAR(255),
    biometric_enrolled BOOLEAN DEFAULT FALSE,
    biometric_template LONGBLOB,
    salary_scale VARCHAR(20),
    gross_salary DECIMAL(12,2),
    bank_account_number VARCHAR(20),
    bank_name VARCHAR(100),
    bank_branch VARCHAR(100),
    standing_order_amount DECIMAL(12,2),
    standing_order_frequency ENUM('monthly','bi-weekly','weekly') DEFAULT 'monthly',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employer_id) REFERENCES employers(employer_id),
    FOREIGN KEY (district_id) REFERENCES districts(district_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    UNIQUE KEY unique_membership (membership_number),
    INDEX idx_membership (membership_number),
    INDEX idx_phone (phone),
    INDEX idx_status (status),
    INDEX idx_employer (employer_id),
    INDEX idx_district (district_id)
);

-- Member documents
CREATE TABLE member_documents (
    doc_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    document_type ENUM('national_id','passport','employment_letter','payslip','recommendation_letter','other') NOT NULL,
    file_name VARCHAR(255) NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    file_size INT,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    verified BOOLEAN DEFAULT FALSE,
    verified_by INT,
    verified_at TIMESTAMP NULL,
    notes TEXT,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id),
    FOREIGN KEY (verified_by) REFERENCES users(user_id),
    INDEX idx_member (member_id),
    INDEX idx_type (document_type)
);

-- Member KYC (Know Your Customer)
CREATE TABLE member_kyc (
    kyc_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL UNIQUE,
    verification_status ENUM('pending','verified','failed','rejected') DEFAULT 'pending',
    verified_by INT,
    verified_at TIMESTAMP NULL,
    rejection_reason TEXT,
    id_verification BOOLEAN DEFAULT FALSE,
    address_verification BOOLEAN DEFAULT FALSE,
    income_verification BOOLEAN DEFAULT FALSE,
    source_of_funds_declaration TEXT,
    pep_check_done BOOLEAN DEFAULT FALSE,
    pep_check_result VARCHAR(50),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(user_id),
    INDEX idx_member (member_id),
    INDEX idx_status (verification_status)
);

-- ============================================================================
-- SAVINGS & SHARES TABLES
-- ============================================================================

-- Share accounts
CREATE TABLE share_accounts (
    share_account_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    account_number VARCHAR(20) UNIQUE NOT NULL,
    balance DECIMAL(12,2) DEFAULT 0,
    par_value DECIMAL(12,2) DEFAULT 100,  -- Par value per share
    number_of_shares INT DEFAULT 0,
    status ENUM('active','dormant','closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id),
    INDEX idx_member (member_id),
    INDEX idx_account (account_number)
);

-- Savings accounts - Multiple types per member
CREATE TABLE savings_accounts (
    savings_account_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    account_type ENUM('monthly_savings','voluntary_savings','fixed_deposit','emergency_fund') NOT NULL,
    account_number VARCHAR(20) UNIQUE NOT NULL,
    balance DECIMAL(12,2) DEFAULT 0,
    interest_rate DECIMAL(5,2) DEFAULT 0,
    opening_balance DECIMAL(12,2) DEFAULT 0,
    opening_date DATE NOT NULL,
    maturity_date DATE,
    last_interest_posted DATE,
    status ENUM('active','dormant','closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    INDEX idx_member (member_id),
    INDEX idx_account (account_number),
    INDEX idx_status (status)
);

-- Ledger system for double-entry accounting
CREATE TABLE ledger_entries (
    entry_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ledger_code VARCHAR(20) NOT NULL,
    ledger_name VARCHAR(100) NOT NULL,
    entry_date DATE NOT NULL,
    receipt_number VARCHAR(50),
    transaction_reference VARCHAR(100),
    transaction_type VARCHAR(50),
    debit DECIMAL(12,2) DEFAULT 0,
    credit DECIMAL(12,2) DEFAULT 0,
    description TEXT,
    payment_method VARCHAR(50),
    posted_by INT NOT NULL,
    approved_by INT,
    member_id INT,
    account_type VARCHAR(50),  -- shares, savings, loans, interest, penalties
    status ENUM('pending','posted','reversed') DEFAULT 'posted',
    reversal_of_id BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    posted_at TIMESTAMP NULL,
    FOREIGN KEY (posted_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE SET NULL,
    INDEX idx_ledger_code (ledger_code),
    INDEX idx_date (entry_date),
    INDEX idx_member (member_id),
    INDEX idx_receipt (receipt_number),
    INDEX idx_status (status)
);

-- Journal entries for audit trail
CREATE TABLE journal_entries (
    journal_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    journal_date DATE NOT NULL,
    journal_reference VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    total_debits DECIMAL(12,2),
    total_credits DECIMAL(12,2),
    created_by INT NOT NULL,
    approved_by INT,
    approval_date TIMESTAMP NULL,
    status ENUM('draft','submitted','approved','rejected') DEFAULT 'draft',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    INDEX idx_date (journal_date),
    INDEX idx_status (status)
);

-- Chart of accounts for accounting
CREATE TABLE chart_of_accounts (
    account_id INT PRIMARY KEY AUTO_INCREMENT,
    account_code VARCHAR(20) UNIQUE NOT NULL,
    account_name VARCHAR(100) NOT NULL,
    account_type ENUM('asset','liability','equity','income','expense') NOT NULL,
    account_category VARCHAR(50),
    description TEXT,
    is_active BOOLEAN DEFAULT TRUE,
    requires_approval BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_code (account_code),
    INDEX idx_type (account_type)
);

-- Cash book for daily reconciliation
CREATE TABLE cash_book (
    cash_book_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    cash_book_date DATE NOT NULL,
    receipt_number VARCHAR(50),
    opening_balance DECIMAL(12,2),
    cash_in DECIMAL(12,2) DEFAULT 0,
    cash_out DECIMAL(12,2) DEFAULT 0,
    closing_balance DECIMAL(12,2),
    reconciled BOOLEAN DEFAULT FALSE,
    reconciled_by INT,
    reconciled_at TIMESTAMP NULL,
    variance DECIMAL(12,2) DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (reconciled_by) REFERENCES users(user_id),
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_date (cash_book_date),
    INDEX idx_reconciled (reconciled)
);

-- ============================================================================
-- LOAN TABLES
-- ============================================================================

-- Loan products/types
CREATE TABLE loan_products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_code VARCHAR(20) UNIQUE NOT NULL,
    product_name VARCHAR(100) NOT NULL,
    product_type ENUM('salary_loan','business_loan') NOT NULL,
    description TEXT,
    min_amount DECIMAL(12,2) NOT NULL,
    max_amount DECIMAL(12,2) NOT NULL,
    annual_interest_rate DECIMAL(5,2) NOT NULL,
    monthly_interest_rate DECIMAL(5,2),
    min_repayment_months INT NOT NULL,
    max_repayment_months INT NOT NULL,
    processing_fee_percentage DECIMAL(5,2) DEFAULT 0,
    processing_fee_fixed DECIMAL(12,2) DEFAULT 0,
    late_penalty_daily DECIMAL(5,2) DEFAULT 0.5,  -- % per day
    late_penalty_monthly_cap DECIMAL(5,2) DEFAULT 5,  -- % per month max
    requires_guarantors BOOLEAN DEFAULT TRUE,
    min_guarantors INT DEFAULT 2,
    max_guarantors INT DEFAULT 5,
    requires_savings_months INT DEFAULT 3,
    min_savings_balance DECIMAL(12,2),
    insurance_percentage DECIMAL(5,2) DEFAULT 0,
    status ENUM('active','inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_product (product_code),
    INDEX idx_type (product_type)
);

-- Salary loan configuration - 26% annual
CREATE TABLE salary_loan_config (
    config_id INT PRIMARY KEY AUTO_INCREMENT,
    annual_interest_rate DECIMAL(5,2) DEFAULT 26.00,
    monthly_interest_rate DECIMAL(5,2) DEFAULT 2.17,  -- 26% / 12
    max_repayment_months INT DEFAULT 24,
    deduction_percentage_of_salary DECIMAL(5,2) DEFAULT 10,
    processing_fee_percentage DECIMAL(5,2) DEFAULT 2,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Business loan configuration - 5% monthly
CREATE TABLE business_loan_config (
    config_id INT PRIMARY KEY AUTO_INCREMENT,
    monthly_interest_rate DECIMAL(5,2) DEFAULT 5.00,
    annual_interest_rate DECIMAL(5,2) DEFAULT 60.00,
    max_duration_months INT DEFAULT 6,
    processing_fee_percentage DECIMAL(5,2) DEFAULT 2.5,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Loans
CREATE TABLE loans (
    loan_id INT PRIMARY KEY AUTO_INCREMENT,
    loan_reference_number VARCHAR(30) UNIQUE NOT NULL,
    member_id INT NOT NULL,
    product_id INT NOT NULL,
    amount_requested DECIMAL(12,2) NOT NULL,
    amount_approved DECIMAL(12,2),
    annual_interest_rate DECIMAL(5,2) NOT NULL,
    monthly_interest_rate DECIMAL(5,2) NOT NULL,
    repayment_period_months INT NOT NULL,
    processing_fee DECIMAL(12,2) DEFAULT 0,
    insurance_premium DECIMAL(12,2) DEFAULT 0,
    loan_purpose TEXT NOT NULL,
    application_date DATE NOT NULL,
    approval_date DATE,
    disbursement_date DATE,
    first_payment_date DATE,
    last_payment_date DATE,
    status ENUM('applied','approved','rejected','disbursed','completed','defaulted','written_off') DEFAULT 'applied',
    outstanding_balance DECIMAL(12,2) DEFAULT 0,
    principal_balance DECIMAL(12,2) DEFAULT 0,
    interest_accrued DECIMAL(12,2) DEFAULT 0,
    penalty_accrued DECIMAL(12,2) DEFAULT 0,
    total_paid DECIMAL(12,2) DEFAULT 0,
    days_overdue INT DEFAULT 0,
    default_status ENUM('none','early_warning','warning','default','legal_action') DEFAULT 'none',
    applied_by INT NOT NULL,
    reviewed_by INT,
    approved_by INT,
    disbursed_by INT,
    rejection_reason TEXT,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES loan_products(product_id),
    FOREIGN KEY (applied_by) REFERENCES users(user_id),
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    FOREIGN KEY (disbursed_by) REFERENCES users(user_id),
    UNIQUE KEY unique_loan_ref (loan_reference_number),
    INDEX idx_member (member_id),
    INDEX idx_status (status),
    INDEX idx_default_status (default_status),
    INDEX idx_approval_date (approval_date)
);

-- Loan guarantors
CREATE TABLE loan_guarantors (
    guarantor_id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    guarantor_member_id INT NOT NULL,
    amount_guaranteed DECIMAL(12,2) NOT NULL,
    percentage_guarantee DECIMAL(5,2) NOT NULL,
    status ENUM('active','released','called','defaulted') DEFAULT 'active',
    release_date DATE,
    called_date DATE,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(loan_id) ON DELETE CASCADE,
    FOREIGN KEY (guarantor_member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    UNIQUE KEY unique_guarantor_loan (loan_id, guarantor_member_id),
    INDEX idx_loan (loan_id),
    INDEX idx_guarantor (guarantor_member_id)
);

-- Loan repayment schedule - calculated and stored
CREATE TABLE loan_repayment_schedule (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    installment_number INT NOT NULL,
    due_date DATE NOT NULL,
    principal_due DECIMAL(12,2) NOT NULL,
    interest_due DECIMAL(12,2) NOT NULL,
    penalty_due DECIMAL(12,2) DEFAULT 0,
    total_due DECIMAL(12,2) NOT NULL,
    principal_paid DECIMAL(12,2) DEFAULT 0,
    interest_paid DECIMAL(12,2) DEFAULT 0,
    penalty_paid DECIMAL(12,2) DEFAULT 0,
    total_paid DECIMAL(12,2) DEFAULT 0,
    principal_balance DECIMAL(12,2),
    paid_date DATE,
    status ENUM('pending','partial','paid','overdue','skipped') DEFAULT 'pending',
    days_overdue INT DEFAULT 0,
    accumulated_penalty DECIMAL(12,2) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(loan_id) ON DELETE CASCADE,
    UNIQUE KEY unique_installment (loan_id, installment_number),
    INDEX idx_loan (loan_id),
    INDEX idx_due_date (due_date),
    INDEX idx_status (status)
);

-- Loan repayments
CREATE TABLE loan_repayments (
    repayment_id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    schedule_id INT,
    repayment_date DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    amount_paid DECIMAL(12,2) NOT NULL,
    principal_paid DECIMAL(12,2) DEFAULT 0,
    interest_paid DECIMAL(12,2) DEFAULT 0,
    penalty_paid DECIMAL(12,2) DEFAULT 0,
    payment_method ENUM('cash','mobile_money','bank_transfer','salary_deduction','standing_order') NOT NULL,
    reference_number VARCHAR(100),
    receipt_number VARCHAR(50) UNIQUE NOT NULL,
    bank_reference VARCHAR(100),
    bank_name VARCHAR(100),
    posted_by INT NOT NULL,
    approved_by INT,
    notes TEXT,
    status ENUM('pending','posted','approved','reversed') DEFAULT 'posted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (loan_id) REFERENCES loans(loan_id) ON DELETE CASCADE,
    FOREIGN KEY (schedule_id) REFERENCES loan_repayment_schedule(schedule_id),
    FOREIGN KEY (posted_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    INDEX idx_loan (loan_id),
    INDEX idx_receipt (receipt_number),
    INDEX idx_date (repayment_date),
    INDEX idx_status (status)
);

-- ============================================================================
-- SALARY DEDUCTION TABLES
-- ============================================================================

-- Salary deduction batches
CREATE TABLE salary_deduction_batches (
    batch_id INT PRIMARY KEY AUTO_INCREMENT,
    batch_reference VARCHAR(50) UNIQUE NOT NULL,
    batch_month DATE NOT NULL,
    employer_id INT NOT NULL,
    file_name VARCHAR(255),
    file_path VARCHAR(255),
    total_records INT DEFAULT 0,
    successful_records INT DEFAULT 0,
    failed_records INT DEFAULT 0,
    total_amount DECIMAL(15,2) DEFAULT 0,
    status ENUM('uploaded','processing','processed','failed','cancelled') DEFAULT 'uploaded',
    uploaded_by INT NOT NULL,
    processed_by INT,
    processing_notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    processed_at TIMESTAMP NULL,
    FOREIGN KEY (employer_id) REFERENCES employers(employer_id),
    FOREIGN KEY (uploaded_by) REFERENCES users(user_id),
    FOREIGN KEY (processed_by) REFERENCES users(user_id),
    INDEX idx_batch_month (batch_month),
    INDEX idx_employer (employer_id),
    INDEX idx_status (status)
);

-- Salary deduction details
CREATE TABLE salary_deduction_details (
    deduction_detail_id INT PRIMARY KEY AUTO_INCREMENT,
    batch_id INT NOT NULL,
    member_id INT,
    membership_number VARCHAR(3),
    employee_name VARCHAR(100),
    gross_salary DECIMAL(12,2),
    deduction_amount DECIMAL(12,2) NOT NULL,
    allocation_interest DECIMAL(12,2) DEFAULT 0,
    allocation_principal DECIMAL(12,2) DEFAULT 0,
    allocation_savings DECIMAL(12,2) DEFAULT 0,
    allocation_shares DECIMAL(12,2) DEFAULT 0,
    matching_status ENUM('matched','unmatched','ambiguous') DEFAULT 'unmatched',
    match_score DECIMAL(5,2),
    posting_status ENUM('pending','posted','failed') DEFAULT 'pending',
    posting_error_message TEXT,
    posted_at TIMESTAMP NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES salary_deduction_batches(batch_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id),
    INDEX idx_batch (batch_id),
    INDEX idx_member (member_id),
    INDEX idx_status (posting_status)
);

-- Standing orders - recurring deductions
CREATE TABLE standing_orders (
    standing_order_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    standing_order_reference VARCHAR(50) UNIQUE NOT NULL,
    bank_reference_number VARCHAR(100),
    start_date DATE NOT NULL,
    end_date DATE,
    frequency ENUM('weekly','bi-weekly','monthly') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    allocation_interest DECIMAL(5,2) DEFAULT 0,
    allocation_principal DECIMAL(5,2) DEFAULT 0,
    allocation_savings DECIMAL(5,2) DEFAULT 0,
    allocation_shares DECIMAL(5,2) DEFAULT 0,
    next_expected_date DATE,
    last_received_date DATE,
    total_received DECIMAL(12,2) DEFAULT 0,
    missed_count INT DEFAULT 0,
    status ENUM('active','inactive','cancelled','completed') DEFAULT 'active',
    bank_name VARCHAR(100),
    account_number VARCHAR(20),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_member (member_id),
    INDEX idx_status (status),
    INDEX idx_next_expected (next_expected_date)
);

-- Standing order postings
CREATE TABLE standing_order_postings (
    posting_id INT PRIMARY KEY AUTO_INCREMENT,
    standing_order_id INT NOT NULL,
    posting_date DATE NOT NULL,
    amount RECEIVED DECIMAL(12,2),
    bank_reference VARCHAR(100),
    posting_status ENUM('received','missed','pending') DEFAULT 'received',
    posted_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (standing_order_id) REFERENCES standing_orders(standing_order_id) ON DELETE CASCADE,
    FOREIGN KEY (posted_by) REFERENCES users(user_id),
    INDEX idx_standing_order (standing_order_id),
    INDEX idx_date (posting_date)
);

-- ============================================================================
-- TRANSACTION TABLES
-- ============================================================================

-- Savings transactions
CREATE TABLE savings_transactions (
    transaction_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    savings_account_id INT NOT NULL,
    transaction_type ENUM('deposit','withdrawal','interest','transfer_in','transfer_out','fee_charged') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    previous_balance DECIMAL(12,2) NOT NULL,
    new_balance DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cash','mobile_money','bank_transfer','salary_deduction','standing_order') NOT NULL,
    reference_number VARCHAR(100),
    receipt_number VARCHAR(50) UNIQUE,
    description TEXT,
    posted_by INT NOT NULL,
    approved_by INT,
    status ENUM('pending','posted','approved','reversed') DEFAULT 'posted',
    reversal_of_id BIGINT,
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    posted_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (savings_account_id) REFERENCES savings_accounts(savings_account_id),
    FOREIGN KEY (posted_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    INDEX idx_account (savings_account_id),
    INDEX idx_member (member_id),
    INDEX idx_receipt (receipt_number),
    INDEX idx_date (transaction_date),
    INDEX idx_status (status)
);

-- Share transactions
CREATE TABLE share_transactions (
    transaction_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    share_account_id INT NOT NULL,
    transaction_type ENUM('purchase','sale','dividend','transfer_in','transfer_out') NOT NULL,
    number_of_shares INT NOT NULL,
    share_price DECIMAL(12,2) NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    previous_balance DECIMAL(12,2),
    new_balance DECIMAL(12,2),
    reference_number VARCHAR(100),
    receipt_number VARCHAR(50) UNIQUE,
    posted_by INT NOT NULL,
    approved_by INT,
    status ENUM('pending','posted','approved','reversed') DEFAULT 'posted',
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (share_account_id) REFERENCES share_accounts(share_account_id),
    FOREIGN KEY (posted_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    INDEX idx_account (share_account_id),
    INDEX idx_receipt (receipt_number),
    INDEX idx_date (transaction_date)
);

-- ============================================================================
-- NOTIFICATION & COMMUNICATION TABLES
-- ============================================================================

-- SMS queue for notifications
CREATE TABLE sms_queue (
    sms_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    member_id INT,
    phone_number VARCHAR(15) NOT NULL,
    message_type VARCHAR(50),  -- balance_notification, loan_approval, payment_reminder, etc
    message_body TEXT NOT NULL,
    sent_at TIMESTAMP NULL,
    delivery_status ENUM('pending','sent','failed','delivered','undelivered') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    provider_reference VARCHAR(100),
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE SET NULL,
    INDEX idx_member (member_id),
    INDEX idx_status (delivery_status),
    INDEX idx_created (created_at)
);

-- Email notifications
CREATE TABLE email_queue (
    email_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    member_id INT,
    email_address VARCHAR(100) NOT NULL,
    subject VARCHAR(255),
    message_body TEXT,
    attachments JSON,
    sent_at TIMESTAMP NULL,
    delivery_status ENUM('pending','sent','failed','bounced') DEFAULT 'pending',
    attempts INT DEFAULT 0,
    error_message TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE SET NULL,
    INDEX idx_status (delivery_status),
    INDEX idx_created (created_at)
);

-- ============================================================================
-- FINANCIAL REPORTS & STATEMENTS
-- ============================================================================

-- Member statements
CREATE TABLE member_statements (
    statement_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    statement_period_start DATE NOT NULL,
    statement_period_end DATE NOT NULL,
    share_opening_balance DECIMAL(12,2),
    share_closing_balance DECIMAL(12,2),
    savings_opening_balance DECIMAL(12,2),
    savings_closing_balance DECIMAL(12,2),
    loan_balance DECIMAL(12,2),
    total_interest_accrued DECIMAL(12,2) DEFAULT 0,
    generated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    generated_by INT,
    file_path VARCHAR(255),
    file_format ENUM('pdf','excel','csv') DEFAULT 'pdf',
    FOREIGN KEY (member_id) REFERENCES members(member_id),
    FOREIGN KEY (generated_by) REFERENCES users(user_id),
    INDEX idx_member (member_id),
    INDEX idx_period (statement_period_start, statement_period_end)
);

-- Daily transaction summary
CREATE TABLE day_sheets (
    day_sheet_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    sheet_date DATE NOT NULL UNIQUE,
    total_shares_purchased DECIMAL(12,2) DEFAULT 0,
    total_savings_deposits DECIMAL(12,2) DEFAULT 0,
    total_savings_withdrawals DECIMAL(12,2) DEFAULT 0,
    total_loan_disbursements DECIMAL(12,2) DEFAULT 0,
    total_loan_repayments DECIMAL(12,2) DEFAULT 0,
    total_interest_posted DECIMAL(12,2) DEFAULT 0,
    total_penalties_posted DECIMAL(12,2) DEFAULT 0,
    created_by INT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_date (sheet_date)
);

-- Trial balance
CREATE TABLE trial_balance (
    trial_id INT PRIMARY KEY AUTO_INCREMENT,
    trial_date DATE NOT NULL,
    account_code VARCHAR(20),
    account_name VARCHAR(100),
    account_type VARCHAR(50),
    debit DECIMAL(12,2) DEFAULT 0,
    credit DECIMAL(12,2) DEFAULT 0,
    balance DECIMAL(12,2),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_date (trial_date),
    INDEX idx_account (account_code)
);

-- Balance sheet
CREATE TABLE balance_sheets (
    balance_sheet_id INT PRIMARY KEY AUTO_INCREMENT,
    sheet_date DATE NOT NULL UNIQUE,
    
    -- Assets
    cash_in_hand DECIMAL(12,2),
    bank_balance DECIMAL(12,2),
    loan_portfolio DECIMAL(12,2),
    fixed_assets DECIMAL(12,2),
    other_assets DECIMAL(12,2),
    total_assets DECIMAL(12,2),
    
    -- Liabilities
    member_shares DECIMAL(12,2),
    member_savings DECIMAL(12,2),
    accrued_interest DECIMAL(12,2),
    other_liabilities DECIMAL(12,2),
    total_liabilities DECIMAL(12,2),
    
    -- Equity
    retained_earnings DECIMAL(12,2),
    current_earnings DECIMAL(12,2),
    total_equity DECIMAL(12,2),
    
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_date (sheet_date)
);

-- Income and expense report
CREATE TABLE income_expense_reports (
    report_id INT PRIMARY KEY AUTO_INCREMENT,
    report_date DATE NOT NULL,
    period_start DATE NOT NULL,
    period_end DATE NOT NULL,
    
    -- Income
    interest_income DECIMAL(12,2) DEFAULT 0,
    processing_fees_income DECIMAL(12,2) DEFAULT 0,
    penalty_income DECIMAL(12,2) DEFAULT 0,
    insurance_income DECIMAL(12,2) DEFAULT 0,
    other_income DECIMAL(12,2) DEFAULT 0,
    total_income DECIMAL(12,2) DEFAULT 0,
    
    -- Expenses
    loan_loss_provision DECIMAL(12,2) DEFAULT 0,
    staff_costs DECIMAL(12,2) DEFAULT 0,
    operational_costs DECIMAL(12,2) DEFAULT 0,
    interest_expense DECIMAL(12,2) DEFAULT 0,
    other_expenses DECIMAL(12,2) DEFAULT 0,
    total_expenses DECIMAL(12,2) DEFAULT 0,
    
    net_income DECIMAL(12,2) DEFAULT 0,
    
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_period (period_start, period_end)
);

-- ============================================================================
-- CONFIGURATION & SETTINGS
-- ============================================================================

-- System configuration
CREATE TABLE system_configuration (
    config_id INT PRIMARY KEY AUTO_INCREMENT,
    config_key VARCHAR(100) UNIQUE NOT NULL,
    config_value TEXT,
    data_type ENUM('string','number','boolean','json') DEFAULT 'string',
    description TEXT,
    last_updated TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    updated_by INT,
    FOREIGN KEY (updated_by) REFERENCES users(user_id),
    INDEX idx_key (config_key)
);

-- Interest calculation history
CREATE TABLE interest_calculations (
    calculation_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    calculation_date DATE NOT NULL,
    calculation_month VARCHAR(7),  -- YYYY-MM
    account_type VARCHAR(50),  -- savings, shares, loans
    total_accounts INT,
    total_amount_calculated DECIMAL(15,2),
    total_interest DECIMAL(15,2),
    created_by INT NOT NULL,
    status ENUM('draft','posted','reversed') DEFAULT 'posted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(user_id),
    INDEX idx_date (calculation_date)
);

-- Defaulters list
CREATE TABLE defaulters_list (
    defaulter_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    loan_id INT,
    default_amount DECIMAL(12,2),
    days_overdue INT,
    default_reason TEXT,
    action_taken VARCHAR(255),
    last_action_date DATE,
    status ENUM('active','cleared','legal_action','written_off') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id),
    FOREIGN KEY (loan_id) REFERENCES loans(loan_id),
    INDEX idx_member (member_id),
    INDEX idx_status (status)
);

-- ============================================================================
-- INSERT INITIAL DATA
-- ============================================================================

-- Insert districts
INSERT INTO districts (district_name, code, region) VALUES
('Rakai', 'RKI', 'Central'),
('Kyotera', 'KYO', 'Central');

-- Insert chart of accounts
INSERT INTO chart_of_accounts (account_code, account_name, account_type, account_category) VALUES
-- Assets
('1010', 'Cash in Hand', 'asset', 'Current Asset'),
('1020', 'Bank Account', 'asset', 'Current Asset'),
('1030', 'Member Loans', 'asset', 'Current Asset'),
('1040', 'Accrued Interest Receivable', 'asset', 'Current Asset'),

-- Liabilities
('2010', 'Member Share Capital', 'liability', 'Member Equity'),
('2020', 'Member Savings Account', 'liability', 'Member Equity'),
('2030', 'Interest Payable', 'liability', 'Current Liability'),

-- Equity
('3010', 'Retained Earnings', 'equity', 'Equity'),

-- Income
('4010', 'Loan Interest Income', 'income', 'Operating Income'),
('4020', 'Processing Fees Income', 'income', 'Operating Income'),
('4030', 'Penalty Income', 'income', 'Operating Income'),

-- Expenses
('5010', 'Loan Loss Provision', 'expense', 'Operating Expense'),
('5020', 'Staff Costs', 'expense', 'Operating Expense'),
('5030', 'Administrative Expenses', 'expense', 'Operating Expense');

-- Insert salary loan product
INSERT INTO loan_products 
(product_code, product_name, product_type, description, min_amount, max_amount, annual_interest_rate, monthly_interest_rate, 
 min_repayment_months, max_repayment_months, processing_fee_percentage, requires_guarantors, min_guarantors, max_guarantors, requires_savings_months)
VALUES
('SALLOAN', 'Salary Loan', 'salary_loan', 'Loan for civil servants - 26% annual, salary deduction', 
 100000, 5000000, 26.00, 2.17, 6, 24, 2.00, TRUE, 2, 5, 3);

-- Insert business loan product
INSERT INTO loan_products 
(product_code, product_name, product_type, description, min_amount, max_amount, monthly_interest_rate, annual_interest_rate,
 min_repayment_months, max_repayment_months, processing_fee_percentage, requires_guarantors, min_guarantors, max_guarantors, requires_savings_months)
VALUES
('BUSLOAN', 'Business Loan', 'business_loan', 'Short-term business loan - 5% monthly, max 6 months', 
 50000, 1000000, 5.00, 60.00, 1, 6, 2.50, TRUE, 2, 3, 3);

-- ============================================================================
-- CREATE INDEXES FOR PERFORMANCE
-- ============================================================================

CREATE INDEX idx_salary_deduction_member ON salary_deduction_details(member_id);
CREATE INDEX idx_salary_deduction_batch ON salary_deduction_details(batch_id);
CREATE INDEX idx_loan_interest_accrued ON loans(interest_accrued);
CREATE INDEX idx_loan_outstanding ON loans(outstanding_balance);
CREATE INDEX idx_standing_order_next ON standing_orders(next_expected_date);
CREATE INDEX idx_ledger_date_member ON ledger_entries(entry_date, member_id);
CREATE INDEX idx_audit_date ON audit_logs(timestamp);
