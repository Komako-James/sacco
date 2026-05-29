-- Member Shares Migration
-- Adds share holdings and purchase history for members

CREATE TABLE IF NOT EXISTS member_share_holdings (
    share_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    shares_owned INT NOT NULL DEFAULT 0,
    share_price DECIMAL(12,2) NOT NULL DEFAULT 10000.00,
    total_invested DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    last_purchase_date TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    UNIQUE KEY idx_member (member_id)
);

CREATE TABLE IF NOT EXISTS member_share_transactions (
    transaction_id INT PRIMARY KEY AUTO_INCREMENT,
    member_id INT NOT NULL,
    share_id INT NOT NULL,
    account_id INT NOT NULL,
    transaction_type ENUM('purchase') DEFAULT 'purchase',
    shares INT NOT NULL,
    amount DECIMAL(15,2) NOT NULL,
    reference_number VARCHAR(100) NOT NULL,
    transaction_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    description TEXT,
    FOREIGN KEY (member_id) REFERENCES members(member_id) ON DELETE CASCADE,
    FOREIGN KEY (share_id) REFERENCES member_share_holdings(share_id) ON DELETE CASCADE,
    FOREIGN KEY (account_id) REFERENCES savings_accounts(account_id) ON DELETE SET NULL,
    INDEX idx_member (member_id),
    INDEX idx_share (share_id),
    INDEX idx_account (account_id)
);
