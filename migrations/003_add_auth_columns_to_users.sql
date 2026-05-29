-- Migration 003: Add auth-related columns to users table
ALTER TABLE users
    ADD COLUMN login_attempts INT DEFAULT 0,
    ADD COLUMN locked_until TIMESTAMP NULL,
    ADD COLUMN last_failed_login TIMESTAMP NULL,
    ADD COLUMN password_changed_at TIMESTAMP NULL,
    ADD COLUMN last_login_ip VARCHAR(45) DEFAULT NULL;
