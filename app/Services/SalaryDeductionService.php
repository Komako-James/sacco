<?php
/**
 * SACCO Salary Deduction Service
 * Handles payroll batch uploads, matching, validation, and posting
 */

namespace SACCO\Services;

use PDO;
use Exception;

class SalaryDeductionService
{
    private $db;

    public function __construct(PDO $database)
    {
        $this->db = $database;
    }

    /**
     * Upload and parse salary deduction CSV
     * Expected columns: employee_name, membership_number, gross_salary, deduction_amount
     * 
     * @param int $employerId
     * @param string $csvFilePath
     * @param string $batchMonth YYYY-MM
     * @param int $uploadedBy
     * @return array
     */
    public function uploadPayrollBatch($employerId, $csvFilePath, $batchMonth, $uploadedBy)
    {
        try {
            $this->db->beginTransaction();

            if (!file_exists($csvFilePath)) {
                throw new Exception('File not found');
            }

            // Validate CSV format
            $rows = array_map('str_getcsv', file($csvFilePath));
            if (count($rows) < 2) {
                throw new Exception('CSV file is empty');
            }

            // Generate batch reference
            $batchReference = 'SD' . date('YmdHis');

            // Create batch record
            $stmt = $this->db->prepare("
                INSERT INTO salary_deduction_batches
                (batch_reference, batch_month, employer_id, file_name, total_records, 
                 status, uploaded_by, created_at)
                VALUES (?, ?, ?, ?, ?, 'uploaded', ?, NOW())
            ");

            $stmt->execute([
                $batchReference,
                $batchMonth . '-01',  // Store as DATE
                $employerId,
                basename($csvFilePath),
                count($rows) - 1  // Exclude header
            ]);

            $batchId = $this->db->lastInsertId();

            // Parse and insert rows
            $header = array_map('strtolower', $rows[0]);
            $totalAmount = 0;
            $recordCount = 0;

            for ($i = 1; $i < count($rows); $i++) {
                $row = $rows[$i];
                
                // Skip empty rows
                if (count(array_filter($row)) === 0) continue;

                // Map to columns
                $data = array_combine($header, $row);

                $employeeName = trim($data['employee_name'] ?? '');
                $membershipNumber = trim($data['membership_number'] ?? '');
                $grossSalary = floatval($data['gross_salary'] ?? 0);
                $deductionAmount = floatval($data['deduction_amount'] ?? 0);

                if (!$deductionAmount) continue;

                // Try to match member
                $memberId = $this->findMemberBySimilarity($membershipNumber, $employeeName);
                $matchingStatus = $memberId ? 'matched' : 'unmatched';
                $matchScore = $memberId ? 100 : 0;

                // Insert detail record
                $stmt = $this->db->prepare("
                    INSERT INTO salary_deduction_details
                    (batch_id, member_id, membership_number, employee_name, gross_salary,
                     deduction_amount, matching_status, match_score, posting_status)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending')
                ");

                $stmt->execute([
                    $batchId,
                    $memberId,
                    $membershipNumber,
                    $employeeName,
                    $grossSalary,
                    $deductionAmount,
                    $matchingStatus,
                    $matchScore
                ]);

                $totalAmount += $deductionAmount;
                $recordCount++;
            }

            // Update batch totals
            $stmt = $this->db->prepare("
                UPDATE salary_deduction_batches
                SET total_records = ?, total_amount = ?
                WHERE batch_id = ?
            ");
            $stmt->execute([$recordCount, $totalAmount, $batchId]);

            AuditService::log(
                $uploadedBy,
                'SALARY_BATCH_UPLOADED',
                'salary_deduction_batches',
                $batchId,
                null,
                ['records' => $recordCount, 'total' => $totalAmount]
            );

            $this->db->commit();

            return [
                'success' => true,
                'batch_id' => $batchId,
                'batch_reference' => $batchReference,
                'records' => $recordCount,
                'total_amount' => $totalAmount,
                'message' => "Batch uploaded successfully with {$recordCount} records"
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Find member using fuzzy matching
     * Matches on membership number or similar name
     * 
     * @param string $membershipNumber
     * @param string $employeeName
     * @return int|null member_id or null if not found
     */
    private function findMemberBySimilarity($membershipNumber, $employeeName)
    {
        // Exact membership number match (preferred)
        if ($membershipNumber) {
            $stmt = $this->db->prepare("
                SELECT member_id FROM members
                WHERE membership_number = ? AND status = 'active'
            ");
            $stmt->execute([$membershipNumber]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) return $result['member_id'];
        }

        // Fuzzy name matching (fallback)
        if ($employeeName) {
            $nameParts = explode(' ', $employeeName);
            $firstName = $nameParts[0] ?? '';
            $lastName = $nameParts[count($nameParts) - 1] ?? '';

            $stmt = $this->db->prepare("
                SELECT member_id FROM members
                WHERE (SOUNDEX(full_name) = SOUNDEX(?) 
                   OR full_name LIKE ? 
                   OR full_name LIKE ?)
                  AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([
                $employeeName,
                "%{$firstName}%",
                "%{$lastName}%"
            ]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($result) return $result['member_id'];
        }

        return null;
    }

    /**
     * Process matched deductions and allocate to accounts
     * Allocation order: Interest (if loan) → Principal (if loan) → Savings → Shares
     * 
     * @param int $batchId
     * @param int $processedBy
     * @return array
     */
    public function processBatchDeductions($batchId, $processedBy)
    {
        try {
            $this->db->beginTransaction();

            // Get batch
            $batch = $this->getBatchById($batchId);
            if (!$batch || $batch['status'] !== 'uploaded') {
                throw new Exception('Batch not found or already processed');
            }

            // Get all matched deductions
            $stmt = $this->db->prepare("
                SELECT * FROM salary_deduction_details
                WHERE batch_id = ? AND matching_status = 'matched'
            ");
            $stmt->execute([$batchId]);
            $deductions = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $successCount = 0;
            $failureCount = 0;
            $totalAllocated = 0;

            foreach ($deductions as $deduction) {
                try {
                    $allocation = $this->allocateDeduction(
                        $deduction['member_id'],
                        $deduction['deduction_amount'],
                        $batch['batch_month']
                    );

                    // Update deduction detail with allocation
                    $stmt = $this->db->prepare("
                        UPDATE salary_deduction_details
                        SET allocation_interest = ?,
                            allocation_principal = ?,
                            allocation_savings = ?,
                            allocation_shares = ?,
                            posting_status = 'posted',
                            posted_at = NOW()
                        WHERE batch_id = ? AND member_id = ?
                    ");
                    $stmt->execute([
                        $allocation['interest'],
                        $allocation['principal'],
                        $allocation['savings'],
                        $allocation['shares'],
                        $batchId,
                        $deduction['member_id']
                    ]);

                    $successCount++;
                    $totalAllocated += $deduction['deduction_amount'];
                } catch (Exception $e) {
                    // Mark as failed
                    $stmt = $this->db->prepare("
                        UPDATE salary_deduction_details
                        SET posting_status = 'failed',
                            posting_error_message = ?
                        WHERE batch_id = ? AND member_id = ?
                    ");
                    $stmt->execute([$e->getMessage(), $batchId, $deduction['member_id']]);
                    $failureCount++;
                }
            }

            // Update batch status
            $stmt = $this->db->prepare("
                UPDATE salary_deduction_batches
                SET status = 'processed',
                    successful_records = ?,
                    failed_records = ?,
                    processed_by = ?,
                    processed_at = NOW()
                WHERE batch_id = ?
            ");
            $stmt->execute([$successCount, $failureCount, $processedBy, $batchId]);

            // Post to ledger
            $stmt = $this->db->prepare("
                SELECT member_id, 
                       SUM(allocation_interest) as interest,
                       SUM(allocation_principal) as principal,
                       SUM(allocation_savings) as savings,
                       SUM(allocation_shares) as shares,
                       SUM(allocation_interest + allocation_principal + allocation_savings + allocation_shares) as total
                FROM salary_deduction_details
                WHERE batch_id = ? AND posting_status = 'posted'
                GROUP BY member_id
            ");
            $stmt->execute([$batchId]);
            $allocations = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $deductions = [];
            foreach ($allocations as $alloc) {
                $deductions[$alloc['member_id']] = [
                    'total' => $alloc['total'],
                    'allocation' => [
                        'interest' => $alloc['interest'],
                        'principal' => $alloc['principal'],
                        'savings' => $alloc['savings'],
                        'shares' => $alloc['shares']
                    ]
                ];
            }

            LedgerService::postSalaryDeductionBatch($batchId, $deductions, $processedBy);

            AuditService::log(
                $processedBy,
                'SALARY_BATCH_PROCESSED',
                'salary_deduction_batches',
                $batchId,
                null,
                ['successful' => $successCount, 'failed' => $failureCount, 'total' => $totalAllocated]
            );

            $this->db->commit();

            return [
                'success' => true,
                'batch_id' => $batchId,
                'successful_records' => $successCount,
                'failed_records' => $failureCount,
                'total_amount' => $totalAllocated,
                'message' => "Batch processed: {$successCount} successful, {$failureCount} failed"
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Allocate a salary deduction to member accounts
     * Priority: Active loan interest → Active loan principal → Savings → Shares
     * 
     * @param int $memberId
     * @param float $totalDeduction
     * @param string $month YYYY-MM
     * @return array ['interest' => x, 'principal' => x, 'savings' => x, 'shares' => x]
     */
    private function allocateDeduction($memberId, $totalDeduction, $month)
    {
        $allocation = [
            'interest' => 0,
            'principal' => 0,
            'savings' => 0,
            'shares' => 0
        ];

        $remaining = $totalDeduction;

        // 1. Allocate to active loan interest
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(interest_accrued), 0) as total_interest
            FROM loans
            WHERE member_id = ? AND status = 'disbursed'
        ");
        $stmt->execute([$memberId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['total_interest'] > 0 && $remaining > 0) {
            $allocation['interest'] = min($remaining, $result['total_interest']);
            $remaining -= $allocation['interest'];
        }

        // 2. Allocate to active loan principal
        $stmt = $this->db->prepare("
            SELECT COALESCE(SUM(principal_balance), 0) as total_principal
            FROM loans
            WHERE member_id = ? AND status = 'disbursed'
        ");
        $stmt->execute([$memberId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['total_principal'] > 0 && $remaining > 0) {
            // Use 50% of remaining for principal
            $allocationAmount = min($remaining * 0.5, $result['total_principal']);
            $allocation['principal'] = $allocationAmount;
            $remaining -= $allocationAmount;
        }

        // 3. Allocate remainder to savings
        if ($remaining > 0) {
            $allocation['savings'] = $remaining;
        }

        return $allocation;
    }

    /**
     * Get batch details
     */
    private function getBatchById($batchId)
    {
        $stmt = $this->db->prepare("
            SELECT sdb.*, e.employer_name
            FROM salary_deduction_batches sdb
            JOIN employers e ON sdb.employer_id = e.employer_id
            WHERE sdb.batch_id = ?
        ");
        $stmt->execute([$batchId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get unmatched records for manual review/matching
     */
    public function getUnmatchedRecords($batchId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM salary_deduction_details
            WHERE batch_id = ? AND matching_status = 'unmatched'
            ORDER BY employee_name
        ");
        $stmt->execute([$batchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Manually match a deduction record to a member
     */
    public function manuallyMatchRecord($deductionDetailId, $memberId, $matchedBy)
    {
        try {
            $stmt = $this->db->prepare("
                UPDATE salary_deduction_details
                SET member_id = ?, matching_status = 'matched', match_score = 100
                WHERE batch_id = ?
            ");
            $stmt->execute([$memberId, $deductionDetailId]);

            return ['success' => true, 'message' => 'Record matched successfully'];
        } catch (Exception $e) {
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Generate batch report with summary
     */
    public function generateBatchReport($batchId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                sdb.batch_reference,
                sdb.batch_month,
                e.employer_name,
                sdb.total_records,
                sdb.successful_records,
                sdb.failed_records,
                sdb.total_amount,
                sdb.status,
                sdb.created_at,
                u.full_name as uploaded_by,
                u2.full_name as processed_by,
                COUNT(DISTINCT CASE WHEN sdd.posting_status = 'posted' THEN sdd.batch_id END) as posted_count,
                SUM(sdd.allocation_interest) as total_interest,
                SUM(sdd.allocation_principal) as total_principal,
                SUM(sdd.allocation_savings) as total_savings,
                SUM(sdd.allocation_shares) as total_shares
            FROM salary_deduction_batches sdb
            JOIN employers e ON sdb.employer_id = e.employer_id
            LEFT JOIN users u ON sdb.uploaded_by = u.user_id
            LEFT JOIN users u2 ON sdb.processed_by = u2.user_id
            LEFT JOIN salary_deduction_details sdd ON sdb.batch_id = sdd.batch_id
            WHERE sdb.batch_id = ?
            GROUP BY sdb.batch_id
        ");

        $stmt->execute([$batchId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Generate failed records report
     */
    public function getFailedRecords($batchId)
    {
        $stmt = $this->db->prepare("
            SELECT 
                sdd.deduction_detail_id,
                sdd.member_id,
                sdd.membership_number,
                sdd.employee_name,
                sdd.gross_salary,
                sdd.deduction_amount,
                sdd.posting_error_message,
                sdd.matching_status
            FROM salary_deduction_details sdd
            WHERE sdd.batch_id = ? AND sdd.posting_status = 'failed'
            ORDER BY sdd.employee_name
        ");

        $stmt->execute([$batchId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

?>
