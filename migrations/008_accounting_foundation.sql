-- Accounting foundation tables and seed data

CREATE TABLE IF NOT EXISTS chart_of_accounts (
    account_code VARCHAR(20) PRIMARY KEY,
    account_name VARCHAR(150) NOT NULL,
    account_type ENUM('asset','liability','equity','income','expense') NOT NULL,
    account_category VARCHAR(100) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT NULL,
    updated_by INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_coa_type (account_type),
    INDEX idx_coa_active (is_active),
    INDEX idx_coa_category (account_category),
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS journal_entries (
    journal_entry_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    entry_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reference_number VARCHAR(100) NOT NULL,
    description TEXT,
    posted_by INT NULL,
    approved_by INT NULL,
    status ENUM('draft','posted','reversed') NOT NULL DEFAULT 'posted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_journal_reference (reference_number),
    INDEX idx_journal_status (status),
    FOREIGN KEY (posted_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (approved_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS journal_entry_lines (
    journal_entry_line_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    journal_entry_id BIGINT NOT NULL,
    account_code VARCHAR(20) NOT NULL,
    account_name VARCHAR(150) NOT NULL,
    debit DECIMAL(15,2) DEFAULT 0,
    credit DECIMAL(15,2) DEFAULT 0,
    description TEXT,
    payment_method VARCHAR(50) DEFAULT NULL,
    transaction_reference VARCHAR(100) DEFAULT NULL,
    transaction_type VARCHAR(50) DEFAULT NULL,
    account_type VARCHAR(50) DEFAULT NULL,
    member_id INT DEFAULT NULL,
    related_member_id INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_journal_entry_id (journal_entry_id),
    INDEX idx_journal_account_code (account_code),
    FOREIGN KEY (journal_entry_id) REFERENCES journal_entries(journal_entry_id) ON DELETE CASCADE,
    FOREIGN KEY (account_code) REFERENCES chart_of_accounts(account_code) ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS bank_accounts (
    bank_account_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    bank_name VARCHAR(150) NOT NULL,
    account_number VARCHAR(100) NOT NULL,
    branch VARCHAR(100) DEFAULT NULL,
    currency VARCHAR(20) NOT NULL DEFAULT 'UGX',
    account_type VARCHAR(50) NOT NULL DEFAULT 'current',
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE INDEX idx_bank_account_number (account_number),
    INDEX idx_bank_name (bank_name),
    INDEX idx_bank_active (is_active),
    FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS cash_book (
    cash_book_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    cash_book_date DATE NOT NULL,
    description TEXT,
    amount DECIMAL(15,2) NOT NULL,
    entry_type ENUM('receipt','payment') NOT NULL,
    bank_account_id BIGINT DEFAULT NULL,
    reference_number VARCHAR(100) DEFAULT NULL,
    posted_by INT DEFAULT NULL,
    status ENUM('pending','posted','reconciled') NOT NULL DEFAULT 'posted',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_cash_book_date (cash_book_date),
    INDEX idx_cash_book_entry_type (entry_type),
    INDEX idx_cash_book_status (status),
    FOREIGN KEY (bank_account_id) REFERENCES bank_accounts(bank_account_id) ON DELETE SET NULL,
    FOREIGN KEY (posted_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO chart_of_accounts (account_code, account_name, account_type, account_category)
VALUES
('1010', 'Cash', 'asset', 'Current Asset'),
('1020', 'Main Bank Account', 'asset', 'Current Asset'),
('1030', 'Loans Receivable', 'asset', 'Current Asset'),
('2010', 'Member Savings', 'liability', 'Member Liability'),
('2020', 'Member Deposits', 'liability', 'Member Liability'),
('3010', 'Share Capital', 'equity', 'Owner Equity'),
('3020', 'Retained Earnings', 'equity', 'Owner Equity'),
('4010', 'Loan Interest Income', 'income', 'Operating Income'),
('4020', 'Processing Fee Income', 'income', 'Operating Income'),
('4030', 'Penalty Income', 'income', 'Operating Income'),
('4040', 'Other Income', 'income', 'Operating Income'),
('5010', 'Staff Costs', 'expense', 'Operating Expense'),
('5020', 'Administrative Expenses', 'expense', 'Operating Expense'),
('5030', 'Utilities', 'expense', 'Operating Expense'),
('5040', 'Rent', 'expense', 'Operating Expense'),
('5050', 'Fuel', 'expense', 'Operating Expense'),
('5060', 'Internet', 'expense', 'Operating Expense')
ON DUPLICATE KEY UPDATE account_name = VALUES(account_name), account_type = VALUES(account_type), account_category = VALUES(account_category);
