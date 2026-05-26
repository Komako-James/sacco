<?php
/**
 * SavingsService - Handles savings account operations
 */

namespace SACCO\Services;

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/LedgerService.php';

class SavingsService {
    private $db;

    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function getAccount(int $accountId) {
        $stmt = $this->db->prepare("SELECT * FROM savings_accounts WHERE savings_account_id = ?");
        $stmt->execute([$accountId]);
        return $stmt->fetch();
    }

    public function listTransactions(int $accountId, int $limit = 50) {
        $stmt = $this->db->prepare("SELECT * FROM savings_transactions WHERE savings_account_id = ? ORDER BY transaction_date DESC, transaction_id DESC LIMIT ?");
        $stmt->execute([$accountId, $limit]);
        return $stmt->fetchAll();
    }

    public function deposit(int $memberId, int $savingsAccountId, float $amount, string $method, int $postedBy, string $reference = null) : array {
        try {
            if ($amount <= 0) throw new \Exception('Amount must be greater than zero');
            $this->db->beginTransaction();

            // Get current balance
            $stmt = $this->db->prepare("SELECT current_balance FROM savings_accounts WHERE savings_account_id = ? FOR UPDATE");
            $stmt->execute([$savingsAccountId]);
            $acc = $stmt->fetch();
            if (!$acc) throw new \Exception('Savings account not found');

            $newBalance = $acc['current_balance'] + $amount;

            // Insert transaction
            $txStmt = $this->db->prepare("INSERT INTO savings_transactions (savings_account_id, member_id, transaction_type, amount, running_balance, reference_number, payment_method, transaction_date, date_created, created_by) VALUES (?, ?, 'Deposit', ?, ?, ?, ?, ?, NOW(), ?)");
            $txStmt->execute([$savingsAccountId, $memberId, $amount, $newBalance, $reference, $method, date('Y-m-d'), $postedBy]);

            // Update account
            $upd = $this->db->prepare("UPDATE savings_accounts SET current_balance = ?, total_deposits = total_deposits + ?, last_transaction_date = NOW() WHERE savings_account_id = ?");
            $upd->execute([$newBalance, $amount, $savingsAccountId]);

            // Post to ledger (memberId, amount, paymentMethod, postedBy)
            \SACCO\Services\LedgerService::postSavingsDeposit($memberId, $amount, $method, $postedBy);

            $this->db->commit();
            return ['success' => true, 'new_balance' => $newBalance];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function withdraw(int $memberId, int $savingsAccountId, float $amount, string $method, int $postedBy, string $reference = null) : array {
        try {
            if ($amount <= 0) throw new \Exception('Amount must be greater than zero');
            $this->db->beginTransaction();

            // Lock account
            $stmt = $this->db->prepare("SELECT current_balance FROM savings_accounts WHERE savings_account_id = ? FOR UPDATE");
            $stmt->execute([$savingsAccountId]);
            $acc = $stmt->fetch();
            if (!$acc) throw new \Exception('Savings account not found');

            if ($acc['current_balance'] < $amount) throw new \Exception('Insufficient balance');

            $newBalance = $acc['current_balance'] - $amount;

            $txStmt = $this->db->prepare("INSERT INTO savings_transactions (savings_account_id, member_id, transaction_type, amount, running_balance, reference_number, payment_method, transaction_date, date_created, created_by) VALUES (?, ?, 'Withdrawal', ?, ?, ?, ?, ?, NOW(), ?)");
            $txStmt->execute([$savingsAccountId, $memberId, $amount, $newBalance, $reference, $method, date('Y-m-d'), $postedBy]);

            $upd = $this->db->prepare("UPDATE savings_accounts SET current_balance = ?, total_withdrawals = total_withdrawals + ?, last_transaction_date = NOW() WHERE savings_account_id = ?");
            $upd->execute([$newBalance, $amount, $savingsAccountId]);

            // Post to ledger (memberId, amount, paymentMethod, postedBy)
            \SACCO\Services\LedgerService::postSavingsWithdrawal($memberId, $amount, $method, $postedBy);

            $this->db->commit();
            return ['success' => true, 'new_balance' => $newBalance];
        } catch (\Exception $e) {
            $this->db->rollback();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

?>