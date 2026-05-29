-- 001_create_sessions_and_standing_orders.sql
-- Creates sessions table, extends users for 2FA and lockouts, creates standing orders, and schema_migrations table

CREATE TABLE IF NOT EXISTS schema_migrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  applied_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Sessions table
CREATE TABLE IF NOT EXISTS sessions (
  session_id VARCHAR(128) PRIMARY KEY,
  user_id INT NOT NULL,
  ip_address VARCHAR(45),
  user_agent VARCHAR(255),
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  last_activity DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  expires_at DATETIME,
  is_active TINYINT(1) DEFAULT 1,
  INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Extend users table for 2FA and lockouts
ALTER TABLE users
  ADD COLUMN two_factor_enabled TINYINT(1) DEFAULT 0,
  ADD COLUMN two_factor_method VARCHAR(20) DEFAULT 'sms',
  ADD COLUMN two_factor_code VARCHAR(128) DEFAULT NULL,
  ADD COLUMN two_factor_expires DATETIME DEFAULT NULL,
  ADD COLUMN login_attempts INT DEFAULT 0,
  ADD COLUMN locked_until DATETIME DEFAULT NULL;

-- Standing orders
CREATE TABLE IF NOT EXISTS standing_orders (
  standing_order_id INT AUTO_INCREMENT PRIMARY KEY,
  member_id INT NOT NULL,
  savings_account_id INT DEFAULT NULL,
  loan_id INT DEFAULT NULL,
  amount DECIMAL(15,2) NOT NULL,
  frequency ENUM('weekly','monthly','fortnightly') DEFAULT 'monthly',
  next_run_date DATE NOT NULL,
  end_date DATE DEFAULT NULL,
  is_active TINYINT(1) DEFAULT 1,
  created_by INT,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
  INDEX (member_id),
  INDEX (next_run_date)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS standing_order_runs (
  run_id INT AUTO_INCREMENT PRIMARY KEY,
  standing_order_id INT NOT NULL,
  run_date DATE NOT NULL,
  status ENUM('pending','processed','failed') DEFAULT 'pending',
  amount DECIMAL(15,2) NOT NULL,
  transaction_reference VARCHAR(255) DEFAULT NULL,
  processed_at DATETIME DEFAULT NULL,
  processed_by INT DEFAULT NULL,
  INDEX (standing_order_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Safety: create sms_queue if missing
CREATE TABLE IF NOT EXISTS sms_queue (
  sms_id INT AUTO_INCREMENT PRIMARY KEY,
  phone_number VARCHAR(50),
  message_body TEXT,
  message_type VARCHAR(50),
  delivery_status ENUM('pending','sent','failed') DEFAULT 'pending',
  attempts INT DEFAULT 0,
  max_attempts INT DEFAULT 3,
  created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
