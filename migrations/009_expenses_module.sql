-- Expense module foundation
-- Creates expense categories, expenses, and expense attachments tables in a non-destructive way.

CREATE TABLE IF NOT EXISTS expense_categories (
    expense_category_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(150) NOT NULL,
    account_code VARCHAR(20) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_expense_category_name (name),
    INDEX idx_expense_category_active (is_active),
    INDEX idx_expense_category_account (account_code),
    CONSTRAINT fk_expense_categories_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_expense_categories_updated_by FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expenses (
    expense_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    expense_date DATE NOT NULL,
    expense_category_id BIGINT DEFAULT NULL,
    category VARCHAR(150) DEFAULT NULL,
    account_code VARCHAR(20) DEFAULT NULL,
    amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    description TEXT DEFAULT NULL,
    payment_method VARCHAR(50) NOT NULL DEFAULT 'cash',
    reference_no VARCHAR(100) DEFAULT NULL,
    status ENUM('draft','posted','cancelled','reversed') NOT NULL DEFAULT 'posted',
    created_by INT DEFAULT NULL,
    updated_by INT DEFAULT NULL,
    ledger_entry_id BIGINT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_expense_date (expense_date),
    INDEX idx_expense_status (status),
    INDEX idx_expense_payment_method (payment_method),
    INDEX idx_expense_created_by (created_by),
    INDEX idx_expense_category (expense_category_id),
    CONSTRAINT fk_expenses_category FOREIGN KEY (expense_category_id) REFERENCES expense_categories(expense_category_id) ON DELETE SET NULL,
    CONSTRAINT fk_expenses_created_by FOREIGN KEY (created_by) REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_expenses_updated_by FOREIGN KEY (updated_by) REFERENCES users(user_id) ON DELETE SET NULL,
    CONSTRAINT fk_expenses_account_code FOREIGN KEY (account_code) REFERENCES chart_of_accounts(account_code) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS expense_attachments (
    expense_attachment_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    expense_id BIGINT NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    mime_type VARCHAR(150) DEFAULT NULL,
    file_size INT DEFAULT 0,
    uploaded_by INT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_expense_attachment_expense (expense_id),
    INDEX idx_expense_attachment_uploaded_by (uploaded_by),
    CONSTRAINT fk_expense_attachment_expense FOREIGN KEY (expense_id) REFERENCES expenses(expense_id) ON DELETE CASCADE,
    CONSTRAINT fk_expense_attachment_uploader FOREIGN KEY (uploaded_by) REFERENCES users(user_id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT INTO expense_categories (name, account_code, description, is_active, created_by)
SELECT 'Utilities', '5030', 'Utilities and communications', 1, NULL
WHERE NOT EXISTS (SELECT 1 FROM expense_categories WHERE name = 'Utilities');

INSERT INTO expense_categories (name, account_code, description, is_active, created_by)
SELECT 'Administrative', '5020', 'Administrative and office expenses', 1, NULL
WHERE NOT EXISTS (SELECT 1 FROM expense_categories WHERE name = 'Administrative');

INSERT INTO expense_categories (name, account_code, description, is_active, created_by)
SELECT 'Staff Costs', '5010', 'Payroll and staff expenses', 1, NULL
WHERE NOT EXISTS (SELECT 1 FROM expense_categories WHERE name = 'Staff Costs');
