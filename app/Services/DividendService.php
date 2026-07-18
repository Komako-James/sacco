<?php
namespace SACCO\Services;

use PDO;
use Exception;

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/functions.php';
require_once __DIR__ . '/ShareService.php';
require_once __DIR__ . '/LedgerService.php';

class DividendService
{
    private $db;
    private $shareService;

    public function __construct(PDO $db = null)
    {
        $this->db = $db ?? \Database::getInstance()->getConnection();
        $this->shareService = new ShareService($this->db);
        $this->ensureSchema();
    }

    public function ensureSchema()
    {
        $migration = __DIR__ . '/../../migrations/005_investments_dividends.sql';
        if (!file_exists($migration)) {
            return;
        }
        $sql = file_get_contents($migration);
        $parts = array_filter(array_map('trim', explode(';', $sql)));
        foreach ($parts as $stmt) {
            try { $this->db->exec($stmt); } catch (Exception $e) { }
        }
    }

    public function declareDividend(array $data): array
    {
        if (empty($data['name'])) return ['success' => false, 'message' => 'Dividend name is required'];
        if (empty($data['financial_year'])) return ['success' => false, 'message' => 'Financial year is required'];
        if (empty($data['rate']) || (float)$data['rate'] < 0) return ['success' => false, 'message' => 'Dividend rate must be zero or positive'];

        $stmt = $this->db->prepare('INSERT INTO dividend_declarations (name, financial_year, declaration_date, payment_date, rate, approval_number, source, status, description, created_by, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())');
        $stmt->execute([
            $data['name'] ?? null,
            $data['financial_year'] ?? null,
            $data['declaration_date'] ?? null,
            $data['payment_date'] ?? null,
            $data['rate'] ?? 0,
            $data['approval_number'] ?? null,
            $data['source'] ?? 'share_capital',
            $data['status'] ?? 'draft',
            $data['description'] ?? null,
            $data['created_by'] ?? null
        ]);

        $id = (int)$this->db->lastInsertId();
        logActivity($data['created_by'] ?? null, 'Dividend Declared', 'dividend_declarations', $id, null, $data);

        if (class_exists('\SACCO\Services\LedgerService')) {
            try { \SACCO\Services\LedgerService::postDividendDeclaration($id, $data['rate'] ?? 0, $data['created_by'] ?? null); } catch (Exception $e) { }
        }

        return ['success' => true, 'id' => $id];
    }

    public function calculateDividends(int $declarationId, float $taxRate = 0.0): array
    {
        $stmt = $this->db->prepare('SELECT * FROM dividend_declarations WHERE id = ?');
        $stmt->execute([$declarationId]);
        $decl = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$decl) return ['success' => false, 'message' => 'Declaration not found'];

        $holdingsStmt = $this->db->query('SELECT member_id, shares_owned FROM member_share_holdings WHERE shares_owned > 0');
        $holders = $holdingsStmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($holders)) return ['success' => false, 'message' => 'No share holdings found'];

        $created = 0;
        $this->db->beginTransaction();
        try {
            foreach ($holders as $h) {
                $memberShares = (float)$h['shares_owned'];
                $perShare = (float)$decl['rate'];
                $gross = round($memberShares * $perShare, 2);
                $tax = round($gross * $taxRate, 2);
                $net = round($gross - $tax, 2);

                $ins = $this->db->prepare('INSERT INTO dividend_payments (declaration_id, member_id, shares, gross_dividend, tax, net_dividend, status) VALUES (?, ?, ?, ?, ?, ?, ?)');
                $ins->execute([$declarationId, $h['member_id'], $memberShares, $gross, $tax, $net, 'pending']);
                $created++;
            }

            $update = $this->db->prepare('UPDATE dividend_declarations SET status = ? WHERE id = ?');
            $update->execute(['declared', $declarationId]);

            $this->db->commit();
            logActivity(null, 'Dividend Calculated', 'dividend_declarations', $declarationId, null, ['payments_created' => $created]);
            return ['success' => true, 'payments_created' => $created];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function payDividend(int $paymentId, string $method, int $postedBy): array
    {
        $stmt = $this->db->prepare('SELECT * FROM dividend_payments WHERE id = ? FOR UPDATE');
        $stmt->execute([$paymentId]);
        $p = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$p) return ['success' => false, 'message' => 'Payment not found'];
        if ($p['status'] === 'paid') return ['success' => false, 'message' => 'Already paid'];

        try {
            $this->db->beginTransaction();
            $upd = $this->db->prepare('UPDATE dividend_payments SET status = ?, payment_method = ?, paid_at = NOW() WHERE id = ?');
            $upd->execute(['paid', $method, $paymentId]);

            if (class_exists('\SACCO\Services\LedgerService')) {
                try { \SACCO\Services\LedgerService::postDividendPayment($p['declaration_id'], $p['member_id'], $p['net_dividend'], $postedBy, $method); } catch (Exception $e) { }
            }

            logActivity($postedBy, 'Dividend Paid', 'dividend_payments', $paymentId, null, ['member_id' => $p['member_id'], 'amount' => $p['net_dividend']]);
            $this->db->commit();
            return ['success' => true];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    public function updateDeclarationStatus(int $declarationId, string $status, int $userId = null): array
    {
        $stmt = $this->db->prepare('UPDATE dividend_declarations SET status = ? WHERE id = ?');
        $stmt->execute([$status, $declarationId]);
        logActivity($userId, 'Dividend Status Updated', 'dividend_declarations', $declarationId, null, ['status' => $status]);
        return ['success' => true];
    }

    public function getDashboardStats(): array
    {
        $stats = $this->db->query('SELECT SUM(rate) AS declared_amount, SUM(CASE WHEN status = "paid" THEN net_dividend ELSE 0 END) AS paid_amount, SUM(CASE WHEN status = "pending" THEN net_dividend ELSE 0 END) AS pending_amount, COUNT(*) AS total_payments FROM dividend_payments')->fetch(PDO::FETCH_ASSOC);
        return [
            'declared_amount' => (float)($stats['declared_amount'] ?? 0),
            'paid_amount' => (float)($stats['paid_amount'] ?? 0),
            'pending_amount' => (float)($stats['pending_amount'] ?? 0),
            'total_payments' => (int)($stats['total_payments'] ?? 0)
        ];
    }

    public function getMemberStatement(int $memberId): array
    {
        $stmt = $this->db->prepare('SELECT dp.*, dd.name, dd.financial_year, dd.rate FROM dividend_payments dp JOIN dividend_declarations dd ON dp.declaration_id = dd.id WHERE dp.member_id = ? ORDER BY dd.declaration_date DESC');
        $stmt->execute([$memberId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
