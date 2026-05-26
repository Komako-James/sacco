CREATE DATABASE IF NOT EXISTS sacco_system;
USE sacco_system;

-- Members Table
CREATE TABLE members (
    member_id INT PRIMARY KEY AUTO_INCREMENT,
    membership_no VARCHAR(20) UNIQUE NOT NULL,
    national_id VARCHAR(20) UNIQUE NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(15) NOT NULL,
    email VARCHAR(100),
    address TEXT,
    date_of_birth DATE,
    gender ENUM('Male','Female','Other'),
    occupation VARCHAR(100),
    employer VARCHAR(100),
    join_date DATE NOT NULL,
    status ENUM('active','inactive','deceased','suspended') DEFAULT 'active',
    photo_path VARCHAR(255),
    signature_path VARCHAR(255),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_membership_no (membership_no),
    INDEX idx_phone (phone),
    INDEX idx_status (status)
);

-- Next of Kin Table
CREATE TABLE next_of_kin (
    kin_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    relationship VARCHAR(50),
    phone VARCHAR(15),
    address TEXT,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    INDEX idx_member (member_id)
);

-- Member Documents
CREATE TABLE member_documents (
    doc_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    document_type ENUM('id_copy','passport_photo','membership_form','employment_letter','other') NOT NULL,
    file_path VARCHAR(255) NOT NULL,
    uploaded_by INT,
    uploaded_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    INDEX idx_member (member_id)
);

-- Savings Accounts
CREATE TABLE savings_accounts (
    account_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    account_type ENUM('monthly_savings','share_capital','voluntary','fixed_deposit') NOT NULL,
    account_number VARCHAR(20) UNIQUE NOT NULL,
    balance DECIMAL(12,2) DEFAULT 0,
    interest_rate DECIMAL(5,2) DEFAULT 0,
    opening_balance DECIMAL(12,2) DEFAULT 0,
    status ENUM('active','dormant','closed') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id),
    INDEX idx_member (member_id),
    INDEX idx_account_number (account_number)
);

-- Savings Transactions
CREATE TABLE savings_transactions (
    trans_id INT PRIMARY KEY AUTO_INCREMENT,
    account_id INT NOT NULL,
    transaction_type ENUM('deposit','withdrawal','interest','transfer_in','transfer_out') NOT NULL,
    amount DECIMAL(12,2) NOT NULL,
    balance_after DECIMAL(12,2) NOT NULL,
    payment_method ENUM('cash','mobile_money','bank_transfer','cheque') NOT NULL,
    reference_no VARCHAR(50),
    receipt_no VARCHAR(50) UNIQUE,
    description TEXT,
    posted_by INT,
    approved_by INT,
    status ENUM('pending','completed','cancelled') DEFAULT 'completed',
    transaction_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (account_id) REFERENCES savings_accounts(account_id),
    INDEX idx_account (account_id),
    INDEX idx_receipt (receipt_no),
    INDEX idx_date (transaction_date)
);

-- Loan Products
CREATE TABLE loan_products (
    product_id INT PRIMARY KEY AUTO_INCREMENT,
    product_name VARCHAR(100) NOT NULL,
    description TEXT,
    min_amount DECIMAL(12,2) NOT NULL,
    max_amount DECIMAL(12,2) NOT NULL,
    default_interest_rate DECIMAL(5,2) NOT NULL,
    min_repayment_months INT NOT NULL,
    max_repayment_months INT NOT NULL,
    processing_fee DECIMAL(12,2) DEFAULT 0,
    late_penalty_rate DECIMAL(5,2) DEFAULT 0,
    requires_guarantors BOOLEAN DEFAULT TRUE,
    min_guarantors INT DEFAULT 2,
    status ENUM('active','inactive') DEFAULT 'active'
);

-- Loans Table
CREATE TABLE loans (
    loan_id INT PRIMARY KEY AUTO_INCREMENT,
    loan_ref_no VARCHAR(20) UNIQUE NOT NULL,
    member_id INT NOT NULL,
    product_id INT NOT NULL,
    amount_requested DECIMAL(12,2) NOT NULL,
    amount_approved DECIMAL(12,2),
    interest_rate DECIMAL(5,2) NOT NULL,
    repayment_period_months INT NOT NULL,
    processing_fee DECIMAL(12,2) DEFAULT 0,
    purpose TEXT,
    application_date DATE NOT NULL,
    approval_date DATE,
    disbursement_date DATE,
    first_payment_date DATE,
    status ENUM('applied','reviewed','approved','rejected','disbursed','completed','defaulted') DEFAULT 'applied',
    outstanding_balance DECIMAL(12,2) DEFAULT 0,
    total_paid DECIMAL(12,2) DEFAULT 0,
    applied_by INT,
    reviewed_by INT,
    approved_by INT,
    disbursed_by INT,
    rejection_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id),
    FOREIGN KEY (product_id) REFERENCES loan_products(product_id),
    INDEX idx_member (member_id),
    INDEX idx_status (status),
    INDEX idx_ref_no (loan_ref_no)
);

-- Loan Guarantors
CREATE TABLE loan_guarantors (
    guarantor_id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    guarantor_member_id INT NOT NULL,
    amount_guaranteed DECIMAL(12,2) NOT NULL,
    percentage_guarantee DECIMAL(5,2) NOT NULL,
    status ENUM('active','released','called','defaulted') DEFAULT 'active',
    release_date DATE,
    notes TEXT,
    FOREIGN KEY (loan_id) REFERENCES loans(loan_id) ON DELETE CASCADE,
    FOREIGN KEY (guarantor_member_id) REFERENCES members(member_id),
    INDEX idx_loan (loan_id),
    INDEX idx_guarantor (guarantor_member_id),
    UNIQUE KEY unique_guarantor_loan (loan_id, guarantor_member_id)
);

-- Loan Repayment Schedule
CREATE TABLE loan_repayment_schedule (
    schedule_id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    installment_no INT NOT NULL,
    due_date DATE NOT NULL,
    principal_amount DECIMAL(12,2) NOT NULL,
    interest_amount DECIMAL(12,2) NOT NULL,
    total_due DECIMAL(12,2) NOT NULL,
    paid_amount DECIMAL(12,2) DEFAULT 0,
    principal_balance DECIMAL(12,2),
    paid_date DATE,
    status ENUM('pending','paid','partial','overdue') DEFAULT 'pending',
    late_penalty DECIMAL(12,2) DEFAULT 0,
    FOREIGN KEY (loan_id) REFERENCES loans(loan_id) ON DELETE CASCADE,
    INDEX idx_loan (loan_id),
    INDEX idx_due_date (due_date)
);

-- Loan Repayments
CREATE TABLE loan_repayments (
    repayment_id INT PRIMARY KEY AUTO_INCREMENT,
    loan_id INT NOT NULL,
    schedule_id INT,
    amount_paid DECIMAL(12,2) NOT NULL,
    principal_paid DECIMAL(12,2),
    interest_paid DECIMAL(12,2),
    penalty_paid DECIMAL(12,2) DEFAULT 0,
    payment_method ENUM('cash','mobile_money','bank_transfer','salary_deduction') NOT NULL,
    reference_no VARCHAR(50),
    receipt_no VARCHAR(50) UNIQUE,
    payment_date DATETIME DEFAULT CURRENT_TIMESTAMP,
    posted_by INT,
    notes TEXT,
    FOREIGN KEY (loan_id) REFERENCES loans(loan_id),
    FOREIGN KEY (schedule_id) REFERENCES loan_repayment_schedule(schedule_id),
    INDEX idx_loan (loan_id),
    INDEX idx_receipt (receipt_no)
);

-- Users/Roles
CREATE TABLE users (
    user_id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    email VARCHAR(100),
    phone VARCHAR(15),
    role ENUM('admin','branch_manager','loan_officer','teller','accountant','viewer') NOT NULL,
    branch_id INT,
    status ENUM('active','inactive','suspended') DEFAULT 'active',
    last_login DATETIME,
    login_attempts INT DEFAULT 0,
    locked_until TIMESTAMP NULL,
    last_failed_login TIMESTAMP NULL,
    password_changed_at TIMESTAMP NULL,
    last_login_ip VARCHAR(45) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_username (username),
    INDEX idx_role (role)
);

-- Branches
CREATE TABLE branches (
    branch_id INT PRIMARY KEY AUTO_INCREMENT,
    branch_code VARCHAR(10) UNIQUE NOT NULL,
    branch_name VARCHAR(100) NOT NULL,
    location VARCHAR(255),
    phone VARCHAR(15),
    email VARCHAR(100),
    manager_id INT,
    status ENUM('active','inactive') DEFAULT 'active',
    FOREIGN KEY (manager_id) REFERENCES users(user_id)
);

-- Audit Log
CREATE TABLE audit_logs (
    log_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    action VARCHAR(100) NOT NULL,
    table_name VARCHAR(50),
    record_id INT,
    old_data TEXT,
    new_data TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_action (action)
);

-- Notifications
CREATE TABLE notifications (
    notification_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT,
    user_id INT,
    notification_type ENUM('sms','email','in_app') NOT NULL,
    title VARCHAR(255),
    message TEXT,
    is_sent BOOLEAN DEFAULT FALSE,
    sent_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id),
    INDEX idx_member (member_id),
    INDEX idx_sent (is_sent)
);

-- Insert Default Data
INSERT INTO users (username, password_hash, full_name, role, status) VALUES 
('admin', '$2y$10$YourHashedPasswordHere', 'System Administrator', 'admin', 'active');

INSERT INTO loan_products (product_name, min_amount, max_amount, default_interest_rate, min_repayment_months, max_repayment_months, requires_guarantors, min_guarantors) VALUES
('Emergency Loan', 50000, 500000, 12.00, 1, 6, TRUE, 1),
('Development Loan', 100000, 5000000, 15.00, 6, 24, TRUE, 2),
('School Fees Loan', 50000, 2000000, 10.00, 3, 12, TRUE, 1),
('Business Loan', 200000, 10000000, 18.00, 12, 36, TRUE, 2);

-- Insert default branch
INSERT INTO branches (branch_code, branch_name, location) VALUES ('HQ', 'Head Office', 'Kampala');
