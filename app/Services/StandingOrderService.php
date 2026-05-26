<?php
namespace SACCO\Services;

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/LedgerService.php';

class StandingOrderService {
    private $db;

    public function __construct() {
        $this->db = \Database::getInstance()->getConnection();
    }

    public function createStandingOrder($memberId, $amount, $frequency, $nextRunDate, $savingsAccountId = null, $loanId = null, $endDate = null, $createdBy = null) {
        $stmt = $this->db->prepare("INSERT INTO standing_orders (member_id, savings_account_id, loan_id, amount, frequency, next_run_date, end_date, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$memberId, $savingsAccountId, $loanId, $amount, $frequency, $nextRunDate, $endDate, $createdBy]);
        return $this->db->lastInsertId();
    }

    public function cancelStandingOrder($standingOrderId) {
        $stmt = $this->db->prepare("UPDATE standing_orders SET is_active = 0 WHERE standing_order_id = ?");
        return $stmt->execute([$standingOrderId]);
    }

    public function getDueOrders($date = null) {
        $date = $date ?: date('Y-m-d');
        $stmt = $this->db->prepare("SELECT * FROM standing_orders WHERE is_active = 1 AND next_run_date <= ?");
        $stmt->execute([$date]);
        return $stmt->fetchAll();
    }

    public function processDueOrders($date = null, $processedBy = null) {
        $date = $date ?: date('Y-m-d');
        $orders = $this->getDueOrders($date);
        $results = [];

        foreach ($orders as $order) {
            $this->db->beginTransaction();
            try {
                // If targeting savings
                if ($order['savings_account_id']) {
                    $savingsService = new \SACCO\Services\SavingsService();
                    $res = $savingsService->deposit($order['member_id'], $order['savings_account_id'], (float)$order['amount'], 'StandingOrder', $processedBy, 'SO-' . $order['standing_order_id']);
                    if (!$res['success']) throw new \Exception($res['message']);
                    $txRef = 'SO-SAV-' . uniqid();
                }

                // If targeting loan repayment
                if ($order['loan_id']) {
                    $loanService = new \SACCO\Services\LoanService();
                    $res = $loanService->processRepayment($order['loan_id'], (float)$order['amount'], 'StandingOrder', 'SO-' . $order['standing_order_id'], $processedBy);
                    if (!$res['success']) throw new \Exception($res['message']);
                    $txRef = 'SO-LOAN-' . uniqid();
                }

                // Record run
                $runStmt = $this->db->prepare("INSERT INTO standing_order_runs (standing_order_id, run_date, status, amount, transaction_reference, processed_at, processed_by) VALUES (?, ?, 'processed', ?, ?, NOW(), ?)");
                $runStmt->execute([$order['standing_order_id'], $date, $order['amount'], $txRef ?? null, $processedBy]);

                // Advance next_run_date
                $next = $this->calculateNextRun($order['next_run_date'], $order['frequency']);
                $upd = $this->db->prepare("UPDATE standing_orders SET next_run_date = ? WHERE standing_order_id = ?");
                $upd->execute([$next, $order['standing_order_id']]);

                $this->db->commit();
                $results[] = ['standing_order_id' => $order['standing_order_id'], 'status' => 'processed'];
            } catch (\Exception $e) {
                $this->db->rollBack();
                $runStmt = $this->db->prepare("INSERT INTO standing_order_runs (standing_order_id, run_date, status, amount, processed_at) VALUES (?, ?, 'failed', ?, NOW())");
                $runStmt->execute([$order['standing_order_id'], $date, $order['amount']]);
                $results[] = ['standing_order_id' => $order['standing_order_id'], 'status' => 'failed', 'error' => $e->getMessage()];
            }
        }

        return $results;
    }

    private function calculateNextRun($currentDate, $frequency) {
        $dt = new \DateTime($currentDate);
        switch ($frequency) {
            case 'weekly': $dt->modify('+7 days'); break;
            case 'fortnightly': $dt->modify('+14 days'); break;
            case 'monthly': default: $dt->modify('+1 month'); break;
        }
        return $dt->format('Y-m-d');
    }
}

?>