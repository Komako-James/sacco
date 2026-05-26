-- Migration 003: Add auth-related columns to users table
ALTER TABLE users
    ADD COLUMN IF NOT EXISTS login_attempts INT DEFAULT 0,
    ADD COLUMN IF NOT EXISTS locked_until TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS last_failed_login TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS password_changed_at TIMESTAMP NULL,
    ADD COLUMN IF NOT EXISTS last_login_ip VARCHAR(45) DEFAULT NULL;
