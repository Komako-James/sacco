<?php
/**
 * SavingsService - Handles savings account operations
 */

namespace SACCO\Services;

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/LedgerService.php';
use PDO;

class SavingsService {
    private $db;

    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function getAccount(int $accountId) {
        $stmt = $this->db->prepare("SELECT * FROM savings_accounts WHERE account_id = ?");
        $stmt->execute([$accountId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function listTransactions(int $accountId, int $limit = 50) {
        $stmt = $this->db->prepare("SELECT * FROM savings_transactions WHERE account_id = ? ORDER BY transaction_date DESC, trans_id DESC LIMIT ?");
        $stmt->execute([$accountId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Open a savings account and optionally post an initial deposit via the SavingsService
     * Returns array ['success'=>bool, 'account_id'=>int, 'account_number'=>string, 'new_balance'=>float]
     */
    public function openAccount(int $memberId, string $accountType, string $accountNumber, float $initialDeposit = 0.0, string $paymentMethod = 'cash', int $postedBy = 0, string $reference = null): array {
        try {
            $ownTx = false;
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $ownTx = true;
            }

            $stmt = $this->db->prepare('INSERT INTO savings_accounts (member_id, account_type, account_number, balance, opening_balance, status, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())');
            $stmt->execute([$memberId, $accountType, $accountNumber, $initialDeposit, $initialDeposit, 'active']);
            $accountId = $this->db->lastInsertId();

            $result = ['success' => true, 'account_id' => $accountId, 'account_number' => $accountNumber, 'new_balance' => $initialDeposit];

            // If there's an initial deposit, delegate to deposit() which is transaction-aware
            if ($initialDeposit > 0) {
                $depositRes = $this->deposit($memberId, (int)$accountId, $initialDeposit, $paymentMethod, $postedBy, $reference);
                if ($ownTx) {
                    $this->db->commit();
                }
                return array_merge($result, $depositRes);
            }

            if ($ownTx) {
                $this->db->commit();
            }
            return $result;
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function deposit(int $memberId, int $savingsAccountId, float $amount, string $method, int $postedBy, string $reference = null) : array {
        try {
            if ($amount <= 0) throw new \Exception('Amount must be greater than zero');

            $ownTx = false;
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $ownTx = true;
            }

            // Get current balance (lock row)
            $stmt = $this->db->prepare("SELECT balance FROM savings_accounts WHERE account_id = ? FOR UPDATE");
            $stmt->execute([$savingsAccountId]);
            $acc = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$acc) throw new \Exception('Savings account not found');

            $newBalance = (float)$acc['balance'] + $amount;

            // Insert transaction
            $txStmt = $this->db->prepare("INSERT INTO savings_transactions (account_id, transaction_type, amount, balance_after, payment_method, reference_no, receipt_no, description, posted_by, status, transaction_date) VALUES (?, 'deposit', ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())");
            $receipt = \SACCO\Services\LedgerService::generateReceiptNumber('DEP');
            $txStmt->execute([$savingsAccountId, $amount, $newBalance, $method, $reference, $receipt, null, $postedBy]);

            // Update account
            $upd = $this->db->prepare("UPDATE savings_accounts SET balance = ?, last_transaction_date = NOW() WHERE account_id = ?");
            $upd->execute([$newBalance, $savingsAccountId]);

            // Post to ledger
            \SACCO\Services\LedgerService::postSavingsDeposit($memberId, $amount, $method, $postedBy);

            if ($ownTx) {
                $this->db->commit();
            }
            return ['success' => true, 'new_balance' => $newBalance, 'receipt' => $receipt];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function withdraw(int $memberId, int $savingsAccountId, float $amount, string $method, int $postedBy, string $reference = null) : array {
        try {
            if ($amount <= 0) throw new \Exception('Amount must be greater than zero');

            $ownTx = false;
            if (!$this->db->inTransaction()) {
                $this->db->beginTransaction();
                $ownTx = true;
            }

            // Lock account
            $stmt = $this->db->prepare("SELECT balance FROM savings_accounts WHERE account_id = ? FOR UPDATE");
            $stmt->execute([$savingsAccountId]);
            $acc = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$acc) throw new \Exception('Savings account not found');

            if ((float)$acc['balance'] < $amount) throw new \Exception('Insufficient balance');

            $newBalance = (float)$acc['balance'] - $amount;

            $receipt = \SACCO\Services\LedgerService::generateReceiptNumber('WTH');
            $txStmt = $this->db->prepare("INSERT INTO savings_transactions (account_id, transaction_type, amount, balance_after, reference_no, payment_method, receipt_no, description, posted_by, status, transaction_date) VALUES (?, 'withdrawal', ?, ?, ?, ?, ?, ?, ?, 'completed', NOW())");
            $txStmt->execute([$savingsAccountId, $amount, $newBalance, $reference, $method, $receipt, null, $postedBy]);

            $upd = $this->db->prepare("UPDATE savings_accounts SET balance = ?, last_transaction_date = NOW() WHERE account_id = ?");
            $upd->execute([$newBalance, $savingsAccountId]);

            // Post to ledger
            \SACCO\Services\LedgerService::postSavingsWithdrawal($memberId, $amount, $method, $postedBy);

            if ($ownTx) {
                $this->db->commit();
            }
            return ['success' => true, 'new_balance' => $newBalance, 'receipt' => $receipt];
        } catch (\Exception $e) {
            if ($this->db->inTransaction()) $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}

?>