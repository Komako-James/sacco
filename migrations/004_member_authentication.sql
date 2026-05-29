-- Member Authentication & Portal Migration
-- Extends existing schema with member login credentials and access control

-- Add member_user_id foreign key to members table to link to users
ALTER TABLE members ADD COLUMN user_id INT NULL AFTER created_by;
ALTER TABLE members ADD FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE;
ALTER TABLE members ADD INDEX idx_user_id (user_id);

-- Track password reset requests
CREATE TABLE IF NOT EXISTS password_reset_tokens (
    token_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    token VARCHAR(191) UNIQUE NOT NULL,
    token_hash VARCHAR(255) NOT NULL,
    purpose ENUM('reset','first_login','change') DEFAULT 'reset',
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    ip_address VARCHAR(45),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    INDEX idx_token (token_hash),
    INDEX idx_user_expires (user_id, expires_at),
    INDEX idx_unused (used_at)
);

-- Member login credentials history (audit trail)
CREATE TABLE IF NOT EXISTS member_login_credentials_history (
    history_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    member_id INT NOT NULL,
    old_username VARCHAR(50),
    new_username VARCHAR(50),
    old_password_hash VARCHAR(255),
    new_password_hash VARCHAR(255),
    action VARCHAR(50), -- 'created', 'reset', 'changed'
    changed_by INT,
    change_reason TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(user_id),
    INDEX idx_member (member_id),
    INDEX idx_user (user_id),
    INDEX idx_created_at (created_at)
);

-- Track member login attempts and security events
CREATE TABLE IF NOT EXISTS member_login_audit (
    audit_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT,
    member_id INT,
    username VARCHAR(50),
    status ENUM('success','failed_password','failed_username','locked','suspicious') DEFAULT 'failed_password',
    ip_address VARCHAR(45) NOT NULL,
    user_agent TEXT,
    login_timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    mfa_verified BOOLEAN DEFAULT FALSE,
    mfa_method VARCHAR(20),
    geographic_anomaly BOOLEAN DEFAULT FALSE,
    device_fingerprint VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE SET NULL,
    INDEX idx_user (user_id),
    INDEX idx_member (member_id),
    INDEX idx_timestamp (login_timestamp),
    INDEX idx_status (status)
);

-- SMS OTP for member verification
CREATE TABLE IF NOT EXISTS member_otp_tokens (
    otp_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    member_id INT NOT NULL,
    otp_code VARCHAR(10) NOT NULL,
    otp_hash VARCHAR(255) NOT NULL,
    purpose ENUM('login_verification','password_reset','phone_change','device_change') DEFAULT 'login_verification',
    is_used BOOLEAN DEFAULT FALSE,
    used_at TIMESTAMP NULL,
    expires_at TIMESTAMP NOT NULL,
    attempts INT DEFAULT 0,
    max_attempts INT DEFAULT 3,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    INDEX idx_user (user_id),
    INDEX idx_unused (is_used, expires_at)
);

-- Member device tracking for security
CREATE TABLE IF NOT EXISTS member_devices (
    device_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    member_id INT NOT NULL,
    device_name VARCHAR(100) NOT NULL,
    device_type VARCHAR(20), -- 'mobile', 'tablet', 'desktop'
    device_fingerprint VARCHAR(191) UNIQUE,
    ip_address VARCHAR(45),
    is_trusted BOOLEAN DEFAULT FALSE,
    is_blocked BOOLEAN DEFAULT FALSE,
    last_used TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    INDEX idx_member (member_id),
    INDEX idx_fingerprint (device_fingerprint)
);

-- Track member session activity
CREATE TABLE IF NOT EXISTS member_sessions (
    session_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    member_id INT NOT NULL,
    session_token VARCHAR(191) UNIQUE NOT NULL,
    device_id INT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    location VARCHAR(100),
    is_active BOOLEAN DEFAULT TRUE,
    login_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_activity TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    logout_at TIMESTAMP NULL,
    expires_at TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (device_id) REFERENCES member_devices(device_id) ON DELETE SET NULL,
    INDEX idx_member (member_id),
    INDEX idx_token (session_token),
    INDEX idx_active (is_active, expires_at)
);

-- Member security preferences
CREATE TABLE IF NOT EXISTS member_security_preferences (
    preference_id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL UNIQUE,
    member_id INT NOT NULL UNIQUE,
    two_factor_enabled BOOLEAN DEFAULT FALSE,
    two_factor_method ENUM('sms','email','authenticator_app') DEFAULT 'sms',
    trusted_devices_only BOOLEAN DEFAULT FALSE,
    notification_on_login BOOLEAN DEFAULT TRUE,
    notification_on_transaction BOOLEAN DEFAULT TRUE,
    session_timeout_minutes INT DEFAULT 30,
    allowed_login_hours VARCHAR(50), -- e.g., "08:00-17:00"
    require_password_change_days INT DEFAULT 90,
    failed_login_threshold INT DEFAULT 5,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE
);

-- Add columns to users table for member-specific settings (if not exists)
ALTER TABLE users ADD COLUMN must_change_password BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN password_expires_at TIMESTAMP NULL;
ALTER TABLE users ADD COLUMN is_member BOOLEAN DEFAULT FALSE;
ALTER TABLE users ADD COLUMN linked_member_id INT NULL;

-- Update existing audit_logs to track password-related actions
-- Audit log indexes relying on columns added in later migrations are created there.
