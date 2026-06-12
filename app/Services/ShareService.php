<?php
/**
 * ShareService - Manages share purchases, transfers, statements, and summary reporting
 */

namespace SACCO\Services;

use PDO;
use Exception;

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/LedgerService.php';

class ShareService
{
    private $db;

    public function __construct(PDO $db = null)
    {
        $this->db = $db ?? \Database::getInstance()->getConnection();
    }

    public function getMemberById(int $memberId)
    {
        $stmt = $this->db->prepare('SELECT * FROM members WHERE member_id = ?');
        $stmt->execute([$memberId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getMemberByMembershipNumber(string $membershipNumber)
    {
        $stmt = $this->db->prepare('SELECT * FROM members WHERE membership_no = ?');
        $stmt->execute([$membershipNumber]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getMemberShareHolding(int $memberId)
    {
        $stmt = $this->db->prepare('SELECT * FROM member_share_holdings WHERE member_id = ?');
        $stmt->execute([$memberId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function getMemberShareTransactions(int $memberId, int $limit = 50)
    {
        $stmt = $this->db->prepare(
            'SELECT mst.*, m.full_name AS member_name, rm.full_name AS related_member_name
             FROM member_share_transactions mst
             LEFT JOIN members m ON mst.member_id = m.member_id
             LEFT JOIN members rm ON mst.related_member_id = rm.member_id
             WHERE mst.member_id = ?
             ORDER BY mst.transaction_date DESC, mst.transaction_id DESC
             LIMIT ?'
        );
        $stmt->execute([$memberId, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentShareTransactions(int $limit = 50)
    {
        $stmt = $this->db->prepare(
            'SELECT mst.*, m.full_name AS member_name, rm.full_name AS related_member_name
             FROM member_share_transactions mst
             LEFT JOIN members m ON mst.member_id = m.member_id
             LEFT JOIN members rm ON mst.related_member_id = rm.member_id
             ORDER BY mst.transaction_date DESC, mst.transaction_id DESC
             LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getRecentShareTransfers(int $limit = 50)
    {
        $stmt = $this->db->prepare(
            'SELECT mst.*, m.full_name AS member_name, rm.full_name AS related_member_name
             FROM member_share_transactions mst
             LEFT JOIN members m ON mst.member_id = m.member_id
             LEFT JOIN members rm ON mst.related_member_id = rm.member_id
             WHERE mst.transaction_type IN ("transfer_in", "transfer_out")
             ORDER BY mst.transaction_date DESC, mst.transaction_id DESC
             LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTopShareholders(int $limit = 20)
    {
        $stmt = $this->db->prepare(
            'SELECT msh.member_id, m.full_name, m.membership_no, msh.shares_owned, msh.total_invested
             FROM member_share_holdings msh
             JOIN members m ON msh.member_id = m.member_id
             ORDER BY msh.shares_owned DESC, msh.total_invested DESC
             LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalSaccoShares(): float
    {
        $stmt = $this->db->query('SELECT COALESCE(SUM(shares_owned), 0) AS total_shares FROM member_share_holdings');
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return (float) ($result['total_shares'] ?? 0);
    }

    public function getShareStatement(int $memberId, string $startDate = null, string $endDate = null): array
    {
        $startDate = $startDate ?: date('Y-m-01');
        $endDate = $endDate ?: date('Y-m-d');

        $stmt = $this->db->prepare(
            'SELECT mst.*, m.full_name AS member_name, rm.full_name AS related_member_name
             FROM member_share_transactions mst
             LEFT JOIN members m ON mst.member_id = m.member_id
             LEFT JOIN members rm ON mst.related_member_id = rm.member_id
             WHERE mst.member_id = ?
               AND mst.transaction_date BETWEEN ? AND ?
             ORDER BY mst.transaction_date ASC, mst.transaction_id ASC'
        );
        $stmt->execute([$memberId, $startDate . ' 00:00:00', $endDate . ' 23:59:59']);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMonthlyShareSummary(int $year = null): array
    {
        $year = $year ?: date('Y');
        $stmt = $this->db->prepare(
            'SELECT MONTH(transaction_date) AS month,
                    SUM(CASE WHEN transaction_type = "purchase" THEN amount ELSE 0 END) AS purchased_amount,
                    SUM(CASE WHEN transaction_type = "transfer_in" THEN amount ELSE 0 END) AS transfer_in_amount,
                    SUM(CASE WHEN transaction_type = "transfer_out" THEN amount ELSE 0 END) AS transfer_out_amount,
                    SUM(CASE WHEN transaction_type = "adjustment" THEN amount ELSE 0 END) AS adjustment_amount,
                    COUNT(*) AS transaction_count
             FROM member_share_transactions
             WHERE YEAR(transaction_date) = ?
             GROUP BY MONTH(transaction_date)
             ORDER BY MONTH(transaction_date)'
        );
        $stmt->execute([$year]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getTotalShareholders(): int
    {
        $stmt = $this->db->query('SELECT COUNT(*) AS total_shareholders FROM member_share_holdings WHERE shares_owned > 0');
        return (int) $stmt->fetchColumn();
    }

    public function getShareHoldings(string $search = null, int $limit = 100, int $offset = 0): array
    {
        $sql = 'SELECT msh.*, m.full_name, m.membership_no, (msh.shares_owned * msh.share_price) AS total_value
                FROM member_share_holdings msh
                JOIN members m ON msh.member_id = m.member_id';
        $params = [];

        if (!empty($search)) {
            $sql .= ' WHERE m.full_name LIKE ? OR m.membership_no LIKE ?';
            $params[] = '%' . $search . '%';
            $params[] = '%' . $search . '%';
        }

        $sql .= ' ORDER BY msh.shares_owned DESC, msh.total_invested DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getShareTransactionHistory(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $sql = 'SELECT mst.*, m.full_name AS member_name, m.membership_no, rm.full_name AS related_member_name
                FROM member_share_transactions mst
                LEFT JOIN members m ON mst.member_id = m.member_id
                LEFT JOIN members rm ON mst.related_member_id = rm.member_id
                WHERE 1=1';
        $params = [];

        if (!empty($filters['membership_no'])) {
            $sql .= ' AND (m.membership_no = ? OR rm.membership_no = ? OR m.full_name LIKE ?)';
            $params[] = $filters['membership_no'];
            $params[] = $filters['membership_no'];
            $params[] = '%' . $filters['membership_no'] . '%';
        }

        if (!empty($filters['transaction_type'])) {
            $sql .= ' AND mst.transaction_type = ?';
            $params[] = $filters['transaction_type'];
        }

        if (!empty($filters['start_date'])) {
            $sql .= ' AND mst.transaction_date >= ?';
            $params[] = $filters['start_date'] . ' 00:00:00';
        }

        if (!empty($filters['end_date'])) {
            $sql .= ' AND mst.transaction_date <= ?';
            $params[] = $filters['end_date'] . ' 23:59:59';
        }

        $sql .= ' ORDER BY mst.transaction_date DESC, mst.transaction_id DESC LIMIT ? OFFSET ?';
        $params[] = $limit;
        $params[] = $offset;

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function adjustMemberShares(int $memberId, int $shareCount, int $postedBy, string $reason, bool $increase = true): array
    {
        if ($shareCount <= 0) {
            return ['success' => false, 'message' => 'Adjustment share quantity must be positive.'];
        }

        $amount = $shareCount * SHARE_PRICE;
        $referenceNumber = 'SADJ-' . $memberId . '-' . time();
        $transactionType = 'adjustment';

        try {
            $this->db->beginTransaction();

            $holdingStmt = $this->db->prepare('SELECT * FROM member_share_holdings WHERE member_id = ? FOR UPDATE');
            $holdingStmt->execute([$memberId]);
            $holding = $holdingStmt->fetch(PDO::FETCH_ASSOC);

            if (!$holding) {
                if (!$increase) {
                    throw new Exception('Member does not have share holdings to decrease.');
                }

                $insertHolding = $this->db->prepare(
                    'INSERT INTO member_share_holdings (member_id, shares_owned, share_price, total_invested, last_purchase_date) VALUES (?, ?, ?, ?, NOW())'
                );
                $insertHolding->execute([$memberId, $shareCount, SHARE_PRICE, $amount]);
                $shareId = $this->db->lastInsertId();
            } else {
                $shareId = $holding['share_id'];
                $newShares = $increase ? $holding['shares_owned'] + $shareCount : $holding['shares_owned'] - $shareCount;
                $newTotalInvested = $increase ? $holding['total_invested'] + $amount : max(0, $holding['total_invested'] - $amount);

                if ($newShares < 0) {
                    throw new Exception('Adjustment would create negative share holdings.');
                }
                if ($newShares < MIN_SHARE_BALANCE) {
                    throw new Exception('Adjustment would violate minimum share balance.');
                }

                $updateHolding = $this->db->prepare(
                    'UPDATE member_share_holdings SET shares_owned = ?, total_invested = ?, updated_at = NOW() WHERE share_id = ?'
                );
                $updateHolding->execute([$newShares, $newTotalInvested, $shareId]);
            }

            $insertTxn = $this->db->prepare(
                'INSERT INTO member_share_transactions
                    (member_id, share_id, account_id, transaction_type, shares, amount, reference_number, transaction_date, created_by, description, status, created_at, updated_at)
                 VALUES (?, ?, NULL, ?, ?, ?, ?, NOW(), ?, ?, ?, NOW(), NOW())'
            );
            $insertTxn->execute([
                $memberId,
                $shareId,
                $transactionType,
                $shareCount,
                $amount,
                $referenceNumber,
                $postedBy,
                $reason,
                'completed'
            ]);

            \SACCO\Services\LedgerService::postShareAdjustment($memberId, $amount, $postedBy, $referenceNumber, $increase ? 'increase' : 'decrease');

            logActivity(
                $postedBy,
                'share_adjustment',
                'member_share_transactions',
                $this->db->lastInsertId(),
                null,
                ['member_id' => $memberId, 'shares' => $shareCount, 'amount' => $amount, 'increase' => $increase, 'reason' => $reason]
            );

            $this->db->commit();
            return ['success' => true, 'message' => 'Share holdings updated successfully.'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function getShareAdjustments(int $limit = 50): array
    {
        $stmt = $this->db->prepare(
            'SELECT mst.*, m.full_name AS member_name
             FROM member_share_transactions mst
             JOIN members m ON mst.member_id = m.member_id
             WHERE mst.transaction_type = "adjustment"
             ORDER BY mst.transaction_date DESC, mst.transaction_id DESC
             LIMIT ?'
        );
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function purchaseSharesFromSavings(int $memberId, int $savingsAccountId, int $shareCount, int $postedBy): array
    {
        if ($shareCount <= 0) {
            return ['success' => false, 'message' => 'Share quantity must be positive.'];
        }

        if ($memberId <= 0 || $savingsAccountId <= 0) {
            return ['success' => false, 'message' => 'Invalid member or savings account.'];
        }

        $totalAmount = $shareCount * SHARE_PRICE;

        try {
            $this->db->beginTransaction();

            $accountStmt = $this->db->prepare('SELECT * FROM savings_accounts WHERE account_id = ? AND member_id = ? AND status = ? FOR UPDATE');
            $accountStmt->execute([$savingsAccountId, $memberId, 'active']);
            $account = $accountStmt->fetch(PDO::FETCH_ASSOC);

            if (!$account) {
                throw new Exception('Savings account not found or not active.');
            }

            if ($account['balance'] < $totalAmount) {
                throw new Exception('Insufficient savings balance to purchase shares.');
            }

            $newBalance = $account['balance'] - $totalAmount;
            $referenceNumber = 'SHR-' . $memberId . '-' . time();
            $receiptNumber = generateReceiptNumber('SP');

            $insertTransaction = $this->db->prepare(
                'INSERT INTO savings_transactions
                    (account_id, transaction_type, amount, balance_after, payment_method, reference_no, receipt_no, description, posted_by, status, transaction_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $insertTransaction->execute([
                $savingsAccountId,
                'transfer_out',
                $totalAmount,
                $newBalance,
                'internal',
                $referenceNumber,
                $receiptNumber,
                'Share purchase from savings account',
                $postedBy,
                'completed'
            ]);

            $updateSavings = $this->db->prepare('UPDATE savings_accounts SET balance = ? WHERE account_id = ?');
            $updateSavings->execute([$newBalance, $savingsAccountId]);

            $holdingStmt = $this->db->prepare('SELECT * FROM member_share_holdings WHERE member_id = ? FOR UPDATE');
            $holdingStmt->execute([$memberId]);
            $holding = $holdingStmt->fetch(PDO::FETCH_ASSOC);

            if ($holding) {
                $shareId = $holding['share_id'];
                $updateHolding = $this->db->prepare(
                    'UPDATE member_share_holdings
                     SET shares_owned = shares_owned + ?,
                         total_invested = total_invested + ?,
                         last_purchase_date = NOW(),
                         updated_at = NOW()
                     WHERE share_id = ?'
                );
                $updateHolding->execute([$shareCount, $totalAmount, $shareId]);
            } else {
                $insertHolding = $this->db->prepare(
                    'INSERT INTO member_share_holdings
                         (member_id, shares_owned, share_price, total_invested, last_purchase_date)
                     VALUES (?, ?, ?, ?, NOW())'
                );
                $insertHolding->execute([$memberId, $shareCount, SHARE_PRICE, $totalAmount]);
                $shareId = $this->db->lastInsertId();
            }

            $insertShareTxn = $this->db->prepare(
                'INSERT INTO member_share_transactions
                    (member_id, share_id, account_id, transaction_type, shares, amount, reference_number, transaction_date, created_by, description, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, NOW(), NOW())'
            );
            $insertShareTxn->execute([
                $memberId,
                $shareId,
                $savingsAccountId,
                'purchase',
                $shareCount,
                $totalAmount,
                $referenceNumber,
                $postedBy,
                'Share purchase from savings account',
                'completed'
            ]);

            LedgerService::postSharePurchaseFromSavings($memberId, $totalAmount, $postedBy, $referenceNumber);

            logActivity(
                $postedBy,
                'share_purchase',
                'member_share_transactions',
                $this->db->lastInsertId(),
                null,
                ['member_id' => $memberId, 'shares' => $shareCount, 'amount' => $totalAmount, 'savings_account_id' => $savingsAccountId]
            );

            $this->db->commit();

            return ['success' => true, 'message' => 'Shares purchased successfully.', 'new_balance' => $newBalance, 'shares_purchased' => $shareCount];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function transferShares(int $sourceMemberId, int $destinationMemberId, int $shareCount, int $postedBy, string $note = ''): array
    {
        if ($shareCount <= 0) {
            return ['success' => false, 'message' => 'Transfer amount must be positive.'];
        }

        if ($sourceMemberId === $destinationMemberId) {
            return ['success' => false, 'message' => 'Source and destination members must be different.'];
        }

        $sourceHolding = $this->getMemberShareHolding($sourceMemberId);
        $sourceMember = $this->getMemberById($sourceMemberId);
        $destinationMember = $this->getMemberById($destinationMemberId);

        if (!$sourceHolding) {
            return ['success' => false, 'message' => 'Source member does not have any share holdings.'];
        }

        if (!$destinationMember) {
            return ['success' => false, 'message' => 'Destination member not found.'];
        }

        if ($sourceHolding['shares_owned'] < $shareCount) {
            return ['success' => false, 'message' => 'Source member does not have enough shares for this transfer.'];
        }

        if ($sourceHolding['shares_owned'] - $shareCount < MIN_SHARE_BALANCE) {
            return ['success' => false, 'message' => 'This transfer would violate minimum share balance requirements.'];
        }

        $totalAmount = $shareCount * SHARE_PRICE;
        $referenceNumber = 'STR-' . $sourceMemberId . '-' . $destinationMemberId . '-' . time();

        try {
            $this->db->beginTransaction();

            $sourceStmt = $this->db->prepare('SELECT * FROM member_share_holdings WHERE member_id = ? FOR UPDATE');
            $sourceStmt->execute([$sourceMemberId]);
            $sourceShare = $sourceStmt->fetch(PDO::FETCH_ASSOC);

            if (!$sourceShare || $sourceShare['shares_owned'] < $shareCount) {
                throw new Exception('Insufficient shares for transfer.');
            }

            $destinationStmt = $this->db->prepare('SELECT * FROM member_share_holdings WHERE member_id = ? FOR UPDATE');
            $destinationStmt->execute([$destinationMemberId]);
            $destinationShare = $destinationStmt->fetch(PDO::FETCH_ASSOC);

            $newSourceShares = $sourceShare['shares_owned'] - $shareCount;
            $newSourceInvested = max(0, $sourceShare['total_invested'] - ($shareCount * $sourceShare['share_price']));

            $updateSource = $this->db->prepare(
                'UPDATE member_share_holdings
                 SET shares_owned = ?, total_invested = ?, updated_at = NOW()
                 WHERE share_id = ?'
            );
            $updateSource->execute([$newSourceShares, $newSourceInvested, $sourceShare['share_id']]);

            if ($destinationShare) {
                $updateDestination = $this->db->prepare(
                    'UPDATE member_share_holdings
                     SET shares_owned = shares_owned + ?, total_invested = total_invested + ?, updated_at = NOW()
                     WHERE share_id = ?'
                );
                $updateDestination->execute([$shareCount, $totalAmount, $destinationShare['share_id']]);
                $destinationShareId = $destinationShare['share_id'];
            } else {
                $insertDestination = $this->db->prepare(
                    'INSERT INTO member_share_holdings
                         (member_id, shares_owned, share_price, total_invested, last_purchase_date)
                     VALUES (?, ?, ?, ?, NOW())'
                );
                $insertDestination->execute([$destinationMemberId, $shareCount, SHARE_PRICE, $totalAmount]);
                $destinationShareId = $this->db->lastInsertId();
            }

            $insertTransfer = $this->db->prepare(
                'INSERT INTO member_share_transfers
                     (source_member_id, destination_member_id, source_share_id, destination_share_id, shares_transferred, amount, reference_number, status, posted_by, notes)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
            );
            $insertTransfer->execute([
                $sourceMemberId,
                $destinationMemberId,
                $sourceShare['share_id'],
                $destinationShareId,
                $shareCount,
                $totalAmount,
                $referenceNumber,
                'completed',
                $postedBy,
                $note
            ]);

            $transferId = $this->db->lastInsertId();

            $insertTransactionOut = $this->db->prepare(
                'INSERT INTO member_share_transactions
                     (member_id, share_id, account_id, transaction_type, shares, amount, reference_number, related_member_id, transfer_id, transaction_date, created_by, description, status, created_at, updated_at)
                 VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, NOW(), NOW())'
            );
            $insertTransactionOut->execute([
                $sourceMemberId,
                $sourceShare['share_id'],
                'transfer_out',
                $shareCount,
                $totalAmount,
                $referenceNumber,
                $destinationMemberId,
                $transferId,
                $postedBy,
                'Share transfer to ' . ($destinationMember['full_name'] ?? $destinationMemberId),
                'completed'
            ]);

            $insertTransactionIn = $this->db->prepare(
                'INSERT INTO member_share_transactions
                     (member_id, share_id, account_id, transaction_type, shares, amount, reference_number, related_member_id, transfer_id, transaction_date, created_by, description, status, created_at, updated_at)
                 VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, NOW(), NOW())'
            );
            $insertTransactionIn->execute([
                $destinationMemberId,
                $destinationShareId,
                'transfer_in',
                $shareCount,
                $totalAmount,
                $referenceNumber,
                $sourceMemberId,
                $transferId,
                $postedBy,
                'Share transfer received from ' . ($sourceMember['full_name'] ?? $sourceMemberId),
                'completed'
            ]);

            LedgerService::postShareTransfer($sourceMemberId, $destinationMemberId, $totalAmount, $referenceNumber, $postedBy);

            logActivity(
                $postedBy,
                'share_transfer',
                'member_share_transfers',
                $transferId,
                null,
                ['source_member_id' => $sourceMemberId, 'destination_member_id' => $destinationMemberId, 'shares' => $shareCount, 'amount' => $totalAmount]
            );

            $this->db->commit();

            return ['success' => true, 'message' => 'Share transfer completed successfully.', 'transfer_id' => $transferId];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function transferSharesByMembershipNumber(int $sourceMemberId, string $destinationMembershipNumber, int $shareCount, int $postedBy, string $note = ''): array
    {
        $destinationMember = $this->getMemberByMembershipNumber($destinationMembershipNumber);
        if (!$destinationMember) {
            return ['success' => false, 'message' => 'Destination member not found. Please check the membership number.'];
        }

        return $this->transferShares($sourceMemberId, $destinationMember['member_id'], $shareCount, $postedBy, $note);
    }

    public function sellShares(int $memberId, int $savingsAccountId, int $shareCount, int $postedBy): array
    {
        if ($shareCount <= 0) {
            return ['success' => false, 'message' => 'Share quantity must be positive.'];
        }

        try {
            $this->db->beginTransaction();

            $holdingStmt = $this->db->prepare('SELECT * FROM member_share_holdings WHERE member_id = ? FOR UPDATE');
            $holdingStmt->execute([$memberId]);
            $holding = $holdingStmt->fetch(PDO::FETCH_ASSOC);

            if (!$holding || $holding['shares_owned'] < $shareCount) {
                throw new Exception('Insufficient shares to complete the sale.');
            }

            $accountStmt = $this->db->prepare('SELECT * FROM savings_accounts WHERE account_id = ? AND member_id = ? AND status = ? FOR UPDATE');
            $accountStmt->execute([$savingsAccountId, $memberId, 'active']);
            $savingsAccount = $accountStmt->fetch(PDO::FETCH_ASSOC);

            if (!$savingsAccount) {
                throw new Exception('Selected savings account was not found or is not active.');
            }

            $totalAmount = $shareCount * ($holding['share_price'] ?? SHARE_PRICE);
            $newBalance = $savingsAccount['balance'] + $totalAmount;
            $referenceNumber = 'SHS-' . $memberId . '-' . time();
            $receiptNumber = generateReceiptNumber('SR');

            $insertSavingsTxn = $this->db->prepare(
                'INSERT INTO savings_transactions
                    (account_id, member_id, transaction_type, amount, balance_after, reference_number, receipt_no, payment_method, description, posted_by, status, transaction_date)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $insertSavingsTxn->execute([
                $savingsAccountId,
                $memberId,
                'deposit',
                $totalAmount,
                $newBalance,
                $referenceNumber,
                $receiptNumber,
                'internal',
                'Share redemption into savings account',
                $postedBy,
                'completed'
            ]);

            $updateSavings = $this->db->prepare('UPDATE savings_accounts SET balance = ? WHERE account_id = ?');
            $updateSavings->execute([$newBalance, $savingsAccountId]);

            $newShares = $holding['shares_owned'] - $shareCount;
            $newInvested = max(0, $holding['total_invested'] - ($shareCount * ($holding['share_price'] ?? SHARE_PRICE)));

            $updateHolding = $this->db->prepare(
                'UPDATE member_share_holdings
                 SET shares_owned = ?, total_invested = ?, updated_at = NOW()
                 WHERE share_id = ?'
            );
            $updateHolding->execute([$newShares, $newInvested, $holding['share_id']]);

            $insertShareTxn = $this->db->prepare(
                'INSERT INTO member_share_transactions
                    (member_id, share_id, account_id, transaction_type, shares, amount, reference_number, transaction_date, created_by, description, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), ?, ?, ?, NOW(), NOW())'
            );
            $insertShareTxn->execute([
                $memberId,
                $holding['share_id'],
                $savingsAccountId,
                'sell',
                $shareCount,
                $totalAmount,
                $referenceNumber,
                $postedBy,
                'Share redemption to savings account',
                'completed'
            ]);

            LedgerService::postShareRedemptionToSavings($memberId, $totalAmount, $postedBy, $referenceNumber);

            logActivity(
                $postedBy,
                'share_sell',
                'member_share_transactions',
                $this->db->lastInsertId(),
                null,
                ['member_id' => $memberId, 'shares' => $shareCount, 'amount' => $totalAmount, 'savings_account_id' => $savingsAccountId]
            );

            $this->db->commit();

            return ['success' => true, 'message' => 'Shares sold successfully.', 'new_balance' => $newBalance, 'shares_sold' => $shareCount];
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    private function createReference(string $prefix = 'SH'): string
    {
        return $prefix . date('YmdHis') . rand(100, 999);
    }
}
