-- Add support for share sell transactions in member share history

ALTER TABLE member_share_transactions
    MODIFY transaction_type ENUM('purchase','sell','transfer_in','transfer_out','adjustment','reversal') NOT NULL DEFAULT 'purchase';
