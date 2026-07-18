-- Migration: create investments and dividends tables
-- Run manually or via migration runner

CREATE TABLE IF NOT EXISTS `investment_types` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(100) NOT NULL,
  `description` TEXT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `investments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `type_id` INT DEFAULT NULL,
  `institution` VARCHAR(255) DEFAULT NULL,
  `reference` VARCHAR(100) DEFAULT NULL,
  `investment_date` DATE DEFAULT NULL,
  `maturity_date` DATE DEFAULT NULL,
  `principal` DECIMAL(18,2) NOT NULL DEFAULT 0.00,
  `interest_rate` DECIMAL(7,4) DEFAULT 0.0000,
  `expected_return` DECIMAL(18,2) DEFAULT 0.00,
  `current_value` DECIMAL(18,2) DEFAULT 0.00,
  `currency` VARCHAR(10) DEFAULT 'KES',
  `status` VARCHAR(50) DEFAULT 'active',
  `description` TEXT DEFAULT NULL,
  `attachments` TEXT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `updated_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_investments_type ON investments(type_id);
CREATE INDEX idx_investments_status ON investments(status);

CREATE TABLE IF NOT EXISTS `investment_transactions` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `investment_id` INT NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `amount` DECIMAL(18,2) DEFAULT 0.00,
  `transaction_date` DATETIME DEFAULT CURRENT_TIMESTAMP,
  `description` TEXT DEFAULT NULL,
  `metadata` JSON DEFAULT NULL,
  `created_by` INT DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_investment_tx_inv ON investment_transactions(investment_id);

CREATE TABLE IF NOT EXISTS `dividend_declarations` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `financial_year` VARCHAR(20) DEFAULT NULL,
  `declaration_date` DATE DEFAULT NULL,
  `payment_date` DATE DEFAULT NULL,
  `rate` DECIMAL(10,4) DEFAULT 0.0000,
  `approval_number` VARCHAR(100) DEFAULT NULL,
  `source` VARCHAR(100) DEFAULT 'share_capital',
  `status` VARCHAR(50) DEFAULT 'draft',
  `description` TEXT DEFAULT NULL,
  `created_by` INT DEFAULT NULL,
  `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS `dividend_payments` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `declaration_id` INT NOT NULL,
  `member_id` INT NOT NULL,
  `shares` DECIMAL(18,4) DEFAULT 0.0000,
  `gross_dividend` DECIMAL(18,2) DEFAULT 0.00,
  `tax` DECIMAL(18,2) DEFAULT 0.00,
  `net_dividend` DECIMAL(18,2) DEFAULT 0.00,
  `status` VARCHAR(50) DEFAULT 'pending',
  `payment_method` VARCHAR(50) DEFAULT NULL,
  `paid_at` DATETIME DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE INDEX idx_dividend_decl_year ON dividend_declarations(financial_year);
