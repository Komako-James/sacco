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
        $stmt = $this->db->prepare("INSERT INTO standing_orders (member_id, savings_account_id, loan_id, amount, frequency, next_run_date, end_date, status, is_active, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, 'active', 1, ?)");
        $stmt->execute([$memberId, $savingsAccountId, $loanId, $amount, $frequency, $nextRunDate, $endDate, $createdBy]);
        return $this->db->lastInsertId();
    }

    public function cancelStandingOrder($standingOrderId) {
        $stmt = $this->db->prepare("UPDATE standing_orders SET status = 'cancelled', is_active = 0 WHERE standing_order_id = ?");
        return $stmt->execute([$standingOrderId]);
    }

    public function getDueOrders($date = null) {
        $date = $date ?: date('Y-m-d');
        $stmt = $this->db->prepare(
            "SELECT * FROM standing_orders WHERE status = 'active' AND next_run_date <= ? " .
            "AND (end_date IS NULL OR end_date >= ?)"
        );
        $stmt->execute([$date, $date]);
        return $stmt->fetchAll();
    }

    public function getAllStandingOrders($limit = 100, $offset = 0) {
        if ($limit === null) {
            $stmt = $this->db->prepare("SELECT * FROM standing_orders ORDER BY standing_order_id DESC");
            $stmt->execute();
        } else {
            $stmt = $this->db->prepare("SELECT * FROM standing_orders ORDER BY standing_order_id DESC LIMIT ? OFFSET ?");
            $stmt->execute([$limit, $offset]);
        }
        return $stmt->fetchAll();
    }

    public function getStandingOrderById($standingOrderId) {
        $stmt = $this->db->prepare("SELECT * FROM standing_orders WHERE standing_order_id = ?");
        $stmt->execute([$standingOrderId]);
        return $stmt->fetch();
    }

    public function updateStandingOrder($standingOrderId, array $data) {
        $allowed = [
            'member_id',
            'savings_account_id',
            'loan_id',
            'amount',
            'frequency',
            'next_run_date',
            'end_date',
            'is_active'
        ];

        $fields = [];
        $params = [];

        foreach ($allowed as $field) {
            if (array_key_exists($field, $data)) {
                if ($field === 'is_active') {
                    $params[] = $data[$field] ? 1 : 0;
                } elseif ($field === 'amount') {
                    $params[] = (float)$data[$field];
                } else {
                    $params[] = $data[$field];
                }
                $fields[] = "$field = ?";
            }
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $standingOrderId;
        $stmt = $this->db->prepare("UPDATE standing_orders SET " . implode(', ', $fields) . " WHERE standing_order_id = ?");
        return $stmt->execute($params);
    }

    public function getStandingOrderHistory($standingOrderId) {
        $stmt = $this->db->prepare("SELECT * FROM standing_order_runs WHERE standing_order_id = ? ORDER BY run_date DESC, run_id DESC");
        $stmt->execute([$standingOrderId]);
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