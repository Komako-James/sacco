-- Share Management Extension:
-- Adds ledger posting and share transfer support for member share purchases

-- Ensure ledger entries exist for double-entry postings
CREATE TABLE IF NOT EXISTS ledger_entries (
    entry_id BIGINT PRIMARY KEY AUTO_INCREMENT,
    ledger_code VARCHAR(20) NOT NULL,
    ledger_name VARCHAR(100) NOT NULL,
    entry_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    receipt_number VARCHAR(100),
    transaction_reference VARCHAR(100),
    transaction_type VARCHAR(50),
    debit DECIMAL(15,2) DEFAULT 0,
    credit DECIMAL(15,2) DEFAULT 0,
    description TEXT,
    payment_method VARCHAR(50),
    posted_by INT,
    approved_by INT,
    member_id INT,
    related_member_id INT,
    account_type VARCHAR(50),
    status ENUM('pending','posted','reversed') DEFAULT 'posted',
    reversal_of_id BIGINT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (posted_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    FOREIGN KEY (member_id) REFERENCES members(member_id),
    FOREIGN KEY (related_member_id) REFERENCES members(member_id),
    INDEX idx_ledger_code (ledger_code),
    INDEX idx_entry_date (entry_date),
    INDEX idx_member (member_id),
    INDEX idx_receipt (receipt_number),
    INDEX idx_status (status)
);

-- Add support for internal share purchase from savings
ALTER TABLE savings_transactions
    MODIFY payment_method ENUM('cash','mobile_money','bank_transfer','cheque','internal') NOT NULL DEFAULT 'internal';

-- Extend member share transactions for transfer and audit tracking
ALTER TABLE member_share_transactions
    MODIFY account_id INT NULL,
    MODIFY transaction_type ENUM('purchase','transfer_in','transfer_out','adjustment','reversal') NOT NULL DEFAULT 'purchase';

ALTER TABLE member_share_transactions
    ADD COLUMN related_member_id INT NULL AFTER member_id,
    ADD COLUMN transfer_id INT NULL AFTER related_member_id,
    ADD COLUMN status ENUM('pending','completed','rejected','reversed') NOT NULL DEFAULT 'completed' AFTER description,
    ADD COLUMN created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER status,
    ADD COLUMN updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP AFTER created_at;

ALTER TABLE member_share_transactions
    ADD INDEX idx_member_status (member_id, status),
    ADD INDEX idx_related_member (related_member_id),
    ADD INDEX idx_transfer (transfer_id);

-- Share transfer audit history
CREATE TABLE IF NOT EXISTS member_share_transfers (
    transfer_id INT PRIMARY KEY AUTO_INCREMENT,
    source_member_id INT NOT NULL,
    destination_member_id INT NOT NULL,
    source_share_id INT NOT NULL,
    destination_share_id INT NULL,
    shares_transferred INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_number VARCHAR(100) NOT NULL,
    status ENUM('pending','approved','completed','rejected','reversed') DEFAULT 'completed',
    posted_by INT,
    approved_by INT,
    reversed_by INT,
    notes TEXT,
    transfer_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (source_member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (destination_member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (source_share_id) REFERENCES member_share_holdings(share_id) ON DELETE CASCADE,
    FOREIGN KEY (destination_share_id) REFERENCES member_share_holdings(share_id) ON DELETE SET NULL,
    FOREIGN KEY (posted_by) REFERENCES users(user_id),
    FOREIGN KEY (approved_by) REFERENCES users(user_id),
    FOREIGN KEY (reversed_by) REFERENCES users(user_id),
    INDEX idx_source_member (source_member_id),
    INDEX idx_destination_member (destination_member_id),
    INDEX idx_reference (reference_number)
);

-- Add share-related reference columns to audit_logs if missing
ALTER TABLE audit_logs
    ADD COLUMN entity_type VARCHAR(50) NULL AFTER action,
    ADD COLUMN entity_id INT NULL AFTER entity_type,
    ADD COLUMN old_values JSON NULL AFTER record_id,
    ADD COLUMN new_values JSON NULL AFTER old_values,
    ADD COLUMN status ENUM('success','failure') DEFAULT 'success' AFTER user_id,
    ADD COLUMN error_message TEXT NULL AFTER user_agent,
    ADD COLUMN timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP AFTER error_message;

-- Add index for audit entity lookups
ALTER TABLE audit_logs
    ADD INDEX idx_entity (entity_type, entity_id);

CREATE INDEX idx_audit_user_timestamp ON audit_logs(user_id, timestamp);
CREATE INDEX idx_audit_action_type ON audit_logs(action, entity_type);
