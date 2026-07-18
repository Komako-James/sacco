<?php
namespace SACCO\Services;

use PDO;
use Exception;

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/LedgerService.php';

class InvestmentService
{
    private $db;

    public function __construct(PDO $db = null)
    {
        $this->db = $db ?? \Database::getInstance()->getConnection();
        $this->ensureSchema();
    }

    public function ensureSchema()
    {
        $migrations = [
            __DIR__ . '/../../migrations/005_investments_dividends.sql',
            __DIR__ . '/../../migrations/006_investments_enhancements.sql'
        ];
        foreach ($migrations as $migration) {
            if (!file_exists($migration)) {
                continue;
            }
            $sql = file_get_contents($migration);
            $parts = array_filter(array_map('trim', explode(';', $sql)));
            foreach ($parts as $stmt) {
                if ($stmt === '') {
                    continue;
                }
                try {
                    $this->db->exec($stmt);
                } catch (Exception $e) {
                    // ignore individual statements
                }
            }
        }
        try {
            $this->db->exec("UPDATE investments SET currency = COALESCE(NULLIF(TRIM(currency), ''), 'UGX') WHERE currency IS NULL OR TRIM(currency) = ''");
        } catch (Exception $e) {
            // ignore update failures
        }
        $this->ensureDefaultInvestmentTypes();
    }

    public function ensureDefaultInvestmentTypes(): void
    {
        $defaults = [
            ['Fixed Deposit', 'Fixed deposit investment'],
            ['Treasury Bill', 'Short-term government treasury bill'],
            ['Treasury Bond', 'Government bond investment'],
            ['Corporate Bond', 'Corporate bond investment'],
            ['Unit Trust', 'Unit trust fund'],
            ['Money Market Fund', 'Money market fund'],
            ['Listed Shares', 'Listed share investment'],
            ['Unlisted Shares', 'Unlisted share investment'],
            ['Government Securities', 'Government securities'],
            ['Other', 'Other investment type']
        ];
        foreach ($defaults as [$name, $description]) {
            $existing = $this->db->prepare('SELECT id FROM investment_types WHERE name = ? LIMIT 1');
            $existing->execute([$name]);
            if ($existing->fetchColumn()) {
                continue;
            }
            $this->db->prepare('INSERT INTO investment_types (name, description, created_at) VALUES (?, ?, NOW())')->execute([$name, $description]);
        }
    }

    public function getInvestmentTypes(): array
    {
        $stmt = $this->db->query('SELECT * FROM investment_types ORDER BY name');
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function createInvestment(array $data): array
    {
        $data['currency'] = normalizeCurrencyCode($data['currency'] ?? 'UGX');
        $data['status'] = $this->normalizeStatus($data['status'] ?? 'active');
        $data['expected_return'] = $data['expected_return'] ?? $this->calculateExpectedReturn($data);
        $data['expected_interest'] = $data['expected_interest'] ?? $data['expected_return'] ?? $this->calculateExpectedReturn($data);
        if (!isset($data['current_value']) || $data['current_value'] === '') {
            $data['current_value'] = $data['principal'] ?? 0;
        }
        $validation = $this->validateInvestmentData($data);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        $columns = ['name','type_id','institution','reference','investment_date','maturity_date','principal','interest_rate','expected_return','current_value','currency','status','description','attachments','created_by','created_at'];
        $placeholders = ['?','?','?','?','?','?','?','?','?','?','?','?','?','?','?','NOW()'];
        $values = [
            $data['name'] ?? null,
            $data['type_id'] ?? null,
            $data['institution'] ?? null,
            $data['reference'] ?? null,
            $data['investment_date'] ?? null,
            $data['maturity_date'] ?? null,
            $data['principal'] ?? 0,
            $data['interest_rate'] ?? 0,
            $data['expected_return'] ?? 0,
            $data['current_value'] ?? ($data['principal'] ?? 0),
            $data['currency'] ?? 'UGX',
            $data['status'] ?? 'active',
            $data['description'] ?? null,
            $this->buildAttachmentValue($data['attachments'] ?? null, $data['interest_payment_frequency'] ?? null, !empty($data['auto_recognize_interest']) ? 1 : 0, (float)($data['expected_interest'] ?? $data['expected_return'] ?? 0)),
            $data['created_by'] ?? null
        ];
        $sql = 'INSERT INTO investments (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $placeholders) . ')';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($values);

        $id = (int)$this->db->lastInsertId();

        logActivity($data['created_by'] ?? null, 'Investment Created', 'investments', $id, null, $data);

        if (class_exists('\SACCO\Services\LedgerService')) {
            try {
                \SACCO\Services\LedgerService::postInvestmentPurchase($id, $data['principal'] ?? 0, $data['created_by'] ?? null, $data['reference'] ?? null);
            } catch (Exception $e) {
                // ignore ledger failures here
            }
        }

        return ['success' => true, 'id' => $id];
    }

    public function getInvestments(array $filters = [], int $limit = 50, int $offset = 0): array
    {
        $sql = 'SELECT i.*, it.name AS type_name FROM investments i LEFT JOIN investment_types it ON i.type_id = it.id WHERE 1=1';
        $params = [];
        if (!empty($filters['status'])) {
            $sql .= ' AND i.status = ?'; $params[] = $filters['status'];
        }
        if (!empty($filters['type_id'])) {
            $sql .= ' AND i.type_id = ?'; $params[] = $filters['type_id'];
        }
        if (!empty($filters['search'])) {
            $sql .= ' AND (i.name LIKE ? OR i.institution LIKE ? OR i.reference LIKE ?)';
            $q = '%' . $filters['search'] . '%'; $params[] = $q; $params[] = $q; $params[] = $q;
        }
        $sql .= ' ORDER BY i.investment_date DESC LIMIT ? OFFSET ?';
        $params[] = $limit; $params[] = $offset;
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $investments = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($investments as &$investment) {
            $investment['currency'] = normalizeCurrencyCode($investment['currency'] ?? 'UGX');
        }
        return $investments;
    }

    public function getInvestmentById(int $id)
    {
        $stmt = $this->db->prepare('SELECT * FROM investments WHERE id = ?');
        $stmt->execute([$id]);
        $investment = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($investment) {
            $investment['currency'] = normalizeCurrencyCode($investment['currency'] ?? 'UGX');
        }
        return $investment;
    }

    public function updateInvestment(int $id, array $data): array
    {
        $data['currency'] = normalizeCurrencyCode($data['currency'] ?? 'UGX');
        $investment = $this->getInvestmentById($id);
        if (!$investment) {
            return ['success' => false, 'message' => 'Investment not found'];
        }
        $data['status'] = $this->normalizeStatus($data['status'] ?? $investment['status'] ?? 'active');
        $data['expected_return'] = $data['expected_return'] ?? $this->calculateExpectedReturn($data + $investment);
        $data['expected_interest'] = $data['expected_interest'] ?? $data['expected_return'] ?? $this->calculateExpectedReturn($data + $investment);
        if (!isset($data['current_value']) || $data['current_value'] === '') {
            $data['current_value'] = $investment['current_value'] ?? ($data['principal'] ?? 0);
        }

        $validation = $this->validateInvestmentData($data, $id);
        if (!$validation['valid']) {
            return ['success' => false, 'message' => $validation['message']];
        }

        $sql = 'UPDATE investments SET name = ?, type_id = ?, institution = ?, reference = ?, investment_date = ?, maturity_date = ?, principal = ?, interest_rate = ?, expected_return = ?, current_value = ?, currency = ?, status = ?, description = ?, attachments = ?, updated_at = NOW() WHERE id = ?';
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            $data['name'] ?? null,
            $data['type_id'] ?? null,
            $data['institution'] ?? null,
            $data['reference'] ?? null,
            $data['investment_date'] ?? null,
            $data['maturity_date'] ?? null,
            $data['principal'] ?? 0,
            $data['interest_rate'] ?? 0,
            $data['expected_return'] ?? 0,
            $data['current_value'] ?? ($data['principal'] ?? 0),
            $data['currency'] ?? 'UGX',
            $data['status'] ?? $investment['status'],
            $data['description'] ?? null,
            $this->buildAttachmentValue($data['attachments'] ?? null, $data['interest_payment_frequency'] ?? null, !empty($data['auto_recognize_interest']) ? 1 : 0, (float)($data['expected_interest'] ?? $data['expected_return'] ?? 0)),
            $id
        ]);

        logActivity($data['created_by'] ?? null, 'Investment Updated', 'investments', $id, $investment, $data);
        return ['success' => true];
    }

    public function deleteInvestment(int $id, int $userId = null): array
    {
        $investment = $this->getInvestmentById($id);
        if (!$investment) {
            return ['success' => false, 'message' => 'Investment not found'];
        }

        $stmt = $this->db->prepare('UPDATE investments SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute(['cancelled', $id]);
        logActivity($userId, 'Investment Deleted', 'investments', $id, $investment, ['status' => 'cancelled']);
        return ['success' => true];
    }

    public function addTransaction(int $investmentId, string $type, float $amount, int $userId = null, string $description = null, array $meta = []): array
    {
        $investment = $this->getInvestmentById($investmentId);
        if (!$investment) {
            return ['success' => false, 'message' => 'Investment not found'];
        }

        $stmt = $this->db->prepare('INSERT INTO investment_transactions (investment_id, type, amount, transaction_date, description, metadata, created_by) VALUES (?, ?, ?, NOW(), ?, ?, ?)');
        $stmt->execute([$investmentId, $type, $amount, $description, json_encode($meta), $userId]);
        $txnId = (int)$this->db->lastInsertId();

        if (in_array($type, ['purchase','additional_investment'])) {
            $this->db->prepare('UPDATE investments SET principal = principal + ?, current_value = current_value + ?, updated_at = NOW() WHERE id = ?')->execute([$amount, $amount, $investmentId]);
        } elseif ($type === 'interest_received') {
            $this->db->prepare('UPDATE investments SET current_value = current_value + ?, updated_at = NOW() WHERE id = ?')->execute([$amount, $investmentId]);
        } elseif (in_array($type, ['partial_withdrawal','sale','closure','full_withdrawal'])) {
            $this->db->prepare('UPDATE investments SET current_value = current_value - ?, updated_at = NOW() WHERE id = ?')->execute([$amount, $investmentId]);
        } elseif ($type === 'capital_gain') {
            $this->db->prepare('UPDATE investments SET current_value = current_value + ?, updated_at = NOW() WHERE id = ?')->execute([$amount, $investmentId]);
        } elseif ($type === 'capital_loss') {
            $this->db->prepare('UPDATE investments SET current_value = current_value - ?, updated_at = NOW() WHERE id = ?')->execute([$amount, $investmentId]);
        }
        $this->db->prepare('UPDATE investments SET expected_return = ?, updated_at = NOW() WHERE id = ?')->execute([$this->calculateExpectedReturn($investment), $investmentId]);

        logActivity($userId, 'Investment Transaction', 'investment_transactions', $txnId, null, ['investment_id' => $investmentId, 'type' => $type, 'amount' => $amount, 'metadata' => $meta]);

        if (class_exists('\SACCO\Services\LedgerService')) {
            try {
                if ($type === 'interest_received') {
                    \SACCO\Services\LedgerService::postInvestmentInterest($investmentId, $amount, $userId, $description);
                } elseif (in_array($type, ['sale','closure','full_withdrawal','partial_withdrawal'])) {
                    \SACCO\Services\LedgerService::postInvestmentDisposal($investmentId, $amount, $userId, $description);
                } elseif ($type === 'capital_gain') {
                    \SACCO\Services\LedgerService::postInvestmentGain($investmentId, $amount, $userId, $description);
                } elseif ($type === 'capital_loss') {
                    \SACCO\Services\LedgerService::postInvestmentLoss($investmentId, $amount, $userId, $description);
                }
            } catch (Exception $e) {
                // ignore
            }
        }

        return ['success' => true, 'transaction_id' => $txnId];
    }

    public function calculateROI(array $investment): array
    {
        $principal = (float)$investment['principal'];
        $current = (float)$investment['current_value'];
        $start = strtotime($investment['investment_date'] ?? date('Y-m-d'));
        $end = strtotime($investment['maturity_date'] ?? date('Y-m-d'));
        $days = max(1, ($end - $start) / 86400);

        $lifetimeReturn = $current - $principal;
        $returnPct = $principal ? ($lifetimeReturn / $principal) * 100 : 0;
        $annualized = ($returnPct / max(1, ($days / 365)));

        return [
            'principal' => $principal,
            'current_value' => $current,
            'lifetime_return' => $lifetimeReturn,
            'return_pct' => round($returnPct, 4),
            'annualized_pct' => round($annualized, 4),
            'realized_returns' => max(0, $lifetimeReturn),
            'unrealized_returns' => max(0, $current - $principal),
            'capital_appreciation' => max(0, $lifetimeReturn),
            'capital_loss' => max(0, -$lifetimeReturn)
        ];
    }

    public function updateStatus(int $id, string $status, int $userId = null): array
    {
        $investment = $this->getInvestmentById($id);
        if (!$investment) {
            return ['success' => false, 'message' => 'Investment not found'];
        }

        $stmt = $this->db->prepare('UPDATE investments SET status = ?, updated_at = NOW() WHERE id = ?');
        $stmt->execute([$status, $id]);
        logActivity($userId, 'Investment Status Updated', 'investments', $id, ['status' => $investment['status']], ['status' => $status]);
        return ['success' => true];
    }

    public function getDashboardStats(): array
    {
        $stats = $this->db->query('SELECT COUNT(*) AS total_investments, SUM(principal) AS total_principal, SUM(current_value) AS current_portfolio_value, SUM(expected_return) AS expected_interest, SUM(CASE WHEN status = "active" THEN 1 ELSE 0 END) AS active_investments, SUM(CASE WHEN status = "matured" THEN 1 ELSE 0 END) AS matured_investments FROM investments')->fetch(PDO::FETCH_ASSOC);
        $interestStmt = $this->db->prepare('SELECT COALESCE(SUM(amount),0) AS interest_received FROM investment_transactions WHERE type = ?');
        $interestStmt->execute(['interest_received']);
        $interestRow = $interestStmt->fetch(PDO::FETCH_ASSOC);
        $totalPrincipal = (float)($stats['total_principal'] ?? 0);
        $currentValue = (float)($stats['current_portfolio_value'] ?? 0);
        $expectedInterest = (float)($stats['expected_interest'] ?? 0);
        $interestReceived = (float)($interestRow['interest_received'] ?? 0);
        $nearMaturityStmt = $this->db->prepare('SELECT COUNT(*) AS near_maturity FROM investments WHERE maturity_date BETWEEN ? AND ? AND status IN ("active","matured")');
        $nearMaturityStmt->execute([date('Y-m-d'), date('Y-m-d', strtotime('+30 days'))]);
        $nearMaturityCount = (int)$nearMaturityStmt->fetchColumn();
        return [
            'total_investments' => (int)($stats['total_investments'] ?? 0),
            'active_investments' => (int)($stats['active_investments'] ?? 0),
            'matured_investments' => (int)($stats['matured_investments'] ?? 0),
            'near_maturity_investments' => $nearMaturityCount,
            'total_principal' => $totalPrincipal,
            'current_portfolio_value' => $currentValue,
            'total_returns' => $currentValue - $totalPrincipal,
            'expected_interest' => $expectedInterest,
            'interest_received' => $interestReceived,
            'roi_pct' => $totalPrincipal > 0 ? round((($currentValue - $totalPrincipal) / $totalPrincipal) * 100, 2) : 0
        ];
    }

    public function getMaturitySummary(): array
    {
        $from = date('Y-m-d');
        $to = date('Y-m-d', strtotime('+30 days'));
        $stmt = $this->db->prepare('SELECT * FROM investments WHERE maturity_date BETWEEN ? AND ? AND status IN ("active","draft") ORDER BY maturity_date ASC');
        $stmt->execute([$from, $to]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function getMaturityAlerts(): array
    {
        $today = date('Y-m-d');
        $alerts = [];
        foreach ([7,14,30] as $days) {
            $date = date('Y-m-d', strtotime('+' . $days . ' days'));
            $stmt = $this->db->prepare('SELECT id, name, maturity_date FROM investments WHERE maturity_date BETWEEN ? AND ? AND status IN ("active","matured") ORDER BY maturity_date ASC');
            $stmt->execute([$today, $date]);
            foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $alerts[] = ['days' => $days, 'id' => $row['id'], 'name' => $row['name'], 'maturity_date' => $row['maturity_date']];
            }
        }
        return $alerts;
    }

    public function getPortfolioBreakdown(): array
    {
        $types = $this->db->query('SELECT it.name AS label, COUNT(*) AS count FROM investments i LEFT JOIN investment_types it ON i.type_id = it.id GROUP BY it.name ORDER BY count DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
        $institutions = $this->db->query('SELECT institution AS label, COUNT(*) AS count FROM investments WHERE institution IS NOT NULL AND institution != "" GROUP BY institution ORDER BY count DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
        return ['types' => $types, 'institutions' => $institutions];
    }

    private function buildAttachmentValue($attachments = null, $interestPaymentFrequency = null, $autoRecognizeInterest = 0, float $expectedInterest = 0.0): string {
        $values = [];
        if (!empty($attachments)) {
            $values[] = trim((string)$attachments);
        }
        if (!empty($interestPaymentFrequency)) {
            $values[] = 'frequency:' . trim((string)$interestPaymentFrequency);
        }
        if ((int)$autoRecognizeInterest === 1) {
            $values[] = 'auto-recognize:1';
        }
        if ($expectedInterest > 0) {
            $values[] = 'expected-interest:' . number_format($expectedInterest, 2, '.', '');
        }
        return implode(' | ', $values);
    }

    private function calculateExpectedReturn(array $data): float
    {
        $principal = (float)($data['principal'] ?? 0);
        $interestRate = (float)($data['interest_rate'] ?? 0);
        $startDate = $data['investment_date'] ?? null;
        $maturityDate = $data['maturity_date'] ?? null;
        if ($principal <= 0 || $interestRate <= 0 || empty($startDate)) {
            return 0.0;
        }
        if (empty($maturityDate)) {
            $maturityDate = date('Y-m-d', strtotime($startDate . ' +1 year'));
        }
        $start = strtotime($startDate);
        $end = strtotime($maturityDate);
        if ($start === false || $end === false || $end <= $start) {
            return round($principal * ($interestRate / 100), 2);
        }
        $days = max(1, floor(($end - $start) / 86400));
        $years = $days / 365;
        return round($principal * ($interestRate / 100) * $years, 2);
    }

    private function normalizeStatus(string $status): string
    {
        $status = strtolower(trim($status));
        $allowed = ['active','matured','closed','sold','cancelled','draft'];
        return in_array($status, $allowed, true) ? $status : 'active';
    }

    private function validateInvestmentData(array $data, int $excludeId = null): array
    {
        if (empty($data['name'])) return ['valid' => false, 'message' => 'Investment name is required'];
        if (empty($data['reference'])) return ['valid' => false, 'message' => 'Reference number is required'];
        if (empty($data['principal']) || (float)$data['principal'] < 0) return ['valid' => false, 'message' => 'Principal amount must be zero or positive'];
        if (!empty($data['interest_rate']) && (float)$data['interest_rate'] < 0) return ['valid' => false, 'message' => 'Interest rate cannot be negative'];
        if (!empty($data['investment_date']) && !empty($data['maturity_date']) && $data['maturity_date'] < $data['investment_date']) return ['valid' => false, 'message' => 'Maturity date cannot be earlier than investment date'];

        $refCheck = $this->db->prepare('SELECT id FROM investments WHERE reference = ?' . ($excludeId ? ' AND id != ?' : ''));
        $params = [$data['reference']];
        if ($excludeId) {
            $params[] = $excludeId;
        }
        $refCheck->execute($params);
        if ($refCheck->fetchColumn()) return ['valid' => false, 'message' => 'Reference number already exists'];

        $data['currency'] = normalizeCurrencyCode($data['currency'] ?? 'UGX');
        $currencies = ['UGX','USD','EUR','GBP','KES','TZS','RWF'];
        if (!in_array($data['currency'], $currencies, true)) {
            return ['valid' => false, 'message' => 'Invalid currency'];
        }

        return ['valid' => true];
    }
}

