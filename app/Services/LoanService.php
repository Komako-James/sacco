<?php
/**
 * SACCO Loan Service - Core Loan Operations
 * Handles loan applications, approvals, disbursements, and tracking
 */

namespace SACCO\Services;

use SACCO\Models\Loan;
use SACCO\Models\LoanRepaymentSchedule;
use SACCO\Models\PaymentAllocation;
use PDO;
use Exception;

require_once __DIR__ . '/LedgerService.php';
require_once __DIR__ . '/AuditAuthNotificationServices.php';

class LoanService
{
    private $db;
    private $loanORM;
    private $scheduleORM;

    public function __construct(PDO $database)
    {
        $this->db = $database;
        // Ensure AuditService and other statics have DB access when autoloader isn't present
        try {
            AuditService::setDatabase($database);
        } catch (\Exception $e) {
            // ignore if AuditService not available yet
        }

        // Some legacy static helpers expect $GLOBALS['db']
        $GLOBALS['db'] = $database;
    }

    /**
     * Apply for a loan
     * @param int $memberId
     * @param int $productId
     * @param float $amountRequested
     * @param string $loanPurpose
     * @param int $userId
     * @return array ['success' => bool, 'loan_id' => int, 'message' => string]
     */
    public function applyForLoan($memberId, $productId, $amountRequested, $loanPurpose, $userId)
    {
        try {
            $this->db->beginTransaction();

            // Validate member eligibility
            $eligibility = $this->validateMemberEligibility($memberId, $productId);
            if (!$eligibility['eligible']) {
                throw new Exception($eligibility['reason']);
            }

            // Get loan product details
            $product = $this->getLoanProduct($productId);
            if (!$product) {
                throw new Exception('Loan product not found');
            }

            // Validate amount
            if ($amountRequested < $product['min_amount'] || $amountRequested > $product['max_amount']) {
                throw new Exception("Amount must be between {$product['min_amount']} and {$product['max_amount']}");
            }

            // Generate unique loan reference
            $loanReference = $this->generateLoanReference();

            // Insert loan application
            $stmt = $this->db->prepare("
                INSERT INTO loans (
                    loan_ref_no, member_id, product_id, amount_requested,
                    annual_interest_rate, monthly_interest_rate, repayment_period_months,
                    loan_purpose, application_date, status, applied_by
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), 'applied', ?)
            ");

            $stmt->execute([
                $loanReference,
                $memberId,
                $productId,
                $amountRequested,
                $product['annual_interest_rate'],
                $product['monthly_interest_rate'],
                $product['min_repayment_months'],
                $loanPurpose,
                $userId
            ]);

            $loanId = $this->db->lastInsertId();

            // Log audit trail
            AuditService::log(
                $userId,
                'LOAN_APPLIED',
                'loans',
                $loanId,
                null,
                ['member_id' => $memberId, 'amount' => $amountRequested, 'product_id' => $productId]
            );

            $this->db->commit();

            return [
                'success' => true,
                'loan_id' => $loanId,
                'loan_reference' => $loanReference,
                'message' => 'Loan application submitted successfully'
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    /**
     * Approve a loan application
     * @param int $loanId
     * @param float $approvedAmount
     * @param int $approvedBy
     * @return array
     */
    public function approveLoan($loanId, $approvedAmount, $approvedBy)
    {
        try {
            $this->db->beginTransaction();

            // Get current loan
            $loan = $this->getLoanById($loanId);
            if (!$loan) {
                throw new Exception('Loan not found');
            }

            if ($loan['status'] !== 'applied') {
                throw new Exception('Only applied loans can be approved');
            }

            if ($approvedAmount <= 0 || $approvedAmount > $loan['amount_requested']) {
                throw new Exception('Invalid approved amount');
            }

            // Update loan
            $stmt = $this->db->prepare("
                UPDATE loans
                SET amount_approved = ?, approval_date = NOW(), status = 'approved', 
                reviewed_by = ?, outstanding_balance = ?
            WHERE loan_id = ?
        ");

            $stmt->execute([
                $approvedAmount,
                $approvedBy,
                $approvedAmount,
                $loanId
            ]);

            // Log audit
            AuditService::log(
                $approvedBy,
                'LOAN_APPROVED',
                'loans',
                $loanId,
                ['status' => 'applied', 'amount_approved' => null],
                ['status' => 'approved', 'amount_approved' => $approvedAmount]
            );

            // Send SMS notification
            NotificationService::notifyLoanApproval($loan['member_id'], $loanId, $approvedAmount);

            $this->db->commit();

            return ['success' => true, 'message' => 'Loan approved successfully'];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage(), 'trace' => $e->getTraceAsString()];
        }
    }

    /**
     * Disburse a loan (transfer funds to member)
     * @param int $loanId
     * @param string $disbursementMethod
     * @param array $bankDetails
     * @param int $disbursedBy
     * @return array
     */
    public function disburseLoan($loanId, $disbursementMethod, $bankDetails, $disbursedBy)
    {
        try {
            $this->db->beginTransaction();

            $loan = $this->getLoanById($loanId);
            if (!$loan || $loan['status'] !== 'approved') {
                throw new Exception('Only approved loans can be disbursed');
            }

            $disbursementAmount = $loan['amount_approved'];
            $processingFee = $this->calculateProcessingFee($disbursementAmount, $loan['product_id']);
            $netDisbursement = $disbursementAmount - $processingFee;

            // Validate disbursement amount
            $disbursementAmount = (float) $disbursementAmount;
            if ($disbursementAmount <= 0) {
                throw new Exception('Disbursement amount is not set or is zero');
            }

            // Update loan
            $stmt = $this->db->prepare("
                UPDATE loans
                SET status = 'disbursed', disbursement_date = NOW(), disbursed_by = ?,
                    first_payment_date = DATE_ADD(NOW(), INTERVAL 1 MONTH)
                WHERE loan_id = ?
            ");
            $stmt->execute([$disbursedBy, $loanId]);

            // Create ledger entries for disbursement
            LedgerService::postLoanDisbursement($loanId, $disbursementAmount, $processingFee, $disbursedBy);

            // Generate repayment schedule using product/loan interest and term
            $product = $this->getLoanProduct($loan['product_id']);
            $annualRate = isset($loan['interest_rate']) ? $loan['interest_rate'] : ($product['default_interest_rate'] ?? 0);
            $months = isset($loan['repayment_period_months']) ? (int)$loan['repayment_period_months'] : (int)($product['min_repayment_months'] ?? 12);
            $this->generateRepaymentSchedule($loanId, $disbursementAmount, $annualRate, $months);

            // Log audit
            AuditService::log(
                $disbursedBy,
                'LOAN_DISBURSED',
                'loans',
                $loanId,
                ['status' => 'approved'],
                ['status' => 'disbursed', 'disbursement_amount' => $disbursementAmount]
            );

            $this->db->commit();

            return [
                'success' => true,
                'disbursement_amount' => $netDisbursement,
                'processing_fee' => $processingFee,
                'message' => 'Loan disbursed successfully'
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Generate amortization schedule for loan repayment
     * Uses declining balance method for interest calculation
     * @param int $loanId
     * @param float $principalAmount
     * @param float $annualRate
     * @param int $months
     */
    public function generateRepaymentSchedule($loanId, $principalAmount, $annualRate, $months)
    {
        $monthlyRate = $annualRate / 100 / 12;
        $monthlyPayment = $this->calculateMonthlyPayment($principalAmount, $monthlyRate, $months);

        $balance = $principalAmount;
        $firstPaymentDate = date('Y-m-d', strtotime('+1 month'));

        for ($i = 1; $i <= $months; $i++) {
            $dueDate = date('Y-m-d', strtotime("+{$i} month", strtotime($firstPaymentDate)));
            $interestPayment = round($balance * $monthlyRate, 2);
            $principalPayment = round($monthlyPayment - $interestPayment, 2);
            $balance -= $principalPayment;

            if ($i == $months) {
                // Last payment - adjust for rounding
                $principalPayment = round($principalPayment + $balance, 2);
                $balance = 0;
            }

            $stmt = $this->db->prepare("
                INSERT INTO loan_repayment_schedule
                (loan_id, installment_no, due_date, principal_amount, interest_amount, total_due, status)
                VALUES (?, ?, ?, ?, ?, ?, 'pending')
            ");

            $stmt->execute([
                $loanId,
                $i,
                $dueDate,
                $principalPayment,
                $interestPayment,
                $principalPayment + $interestPayment
            ]);
        }
    }

    /**
     * Calculate monthly payment using amortization formula
     * PMT = P * [r(1+r)^n] / [(1+r)^n - 1]
     */
    private function calculateMonthlyPayment($principal, $monthlyRate, $months)
    {
        if ($monthlyRate == 0) {
            return $principal / $months;
        }

        $numerator = $principal * $monthlyRate * pow(1 + $monthlyRate, $months);
        $denominator = pow(1 + $monthlyRate, $months) - 1;
        return round($numerator / $denominator, 2);
    }

    /**
     * Process a loan repayment
     * Allocates payment to interest, principal, then penalties
     * @param int $loanId
     * @param float $amountPaid
     * @param string $paymentMethod
     * @param string $referenceNumber
     * @param int $postedBy
     * @return array
     */
    public function processRepayment($loanId, $amountPaid, $paymentMethod, $referenceNumber, $postedBy)
    {
        try {
            $this->db->beginTransaction();

            $loan = $this->getLoanById($loanId);
            if (!$loan || !in_array($loan['status'], ['disbursed'])) {
                throw new Exception('Loan not in disbursed status');
            }

            if ($amountPaid <= 0) {
                throw new Exception('Payment amount must be greater than 0');
            }

            // Allocate payment
            $allocation = $this->allocateRepayment($loanId, $amountPaid);

            // Create receipt
            $receiptNumber = $this->generateReceiptNumber('RP');

            // Insert repayment record
            $stmt = $this->db->prepare("\n                INSERT INTO loan_repayments\n                (loan_id, schedule_id, amount_paid, principal_paid, interest_paid, \n                 penalty_paid, payment_method, reference_no, receipt_no, posted_by)\n                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)\n            ");

            $stmt->execute([
                $loanId,
                $allocation['schedule_id'] ?? null,
                $amountPaid,
                $allocation['principal_paid'],
                $allocation['interest_paid'],
                $allocation['penalty_paid'],
                $paymentMethod,
                $referenceNumber,
                $receiptNumber,
                $postedBy
            ]);

            $repaymentId = $this->db->lastInsertId();

            // Update loan balances
            $this->updateLoanBalance($loanId, $allocation);

            // Post to ledger
            LedgerService::postLoanRepayment($repaymentId, $allocation, $postedBy);

            // Update repayment schedule status
            $this->updateScheduleStatus($loanId);

            // Check for loan completion
            if ($this->isLoanCompleted($loanId)) {
                $this->completeLoan($loanId);
            }

            // Log audit
            AuditService::log(
                $postedBy,
                'LOAN_REPAYMENT',
                'loan_repayments',
                $repaymentId,
                null,
                $allocation
            );

            $this->db->commit();

            return [
                'success' => true,
                'receipt_number' => $receiptNumber,
                'allocation' => $allocation,
                'message' => 'Repayment processed successfully'
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Allocate a payment across interest, principal, and penalties
     * Priority: Penalties → Interest → Principal
     * @param int $loanId
     * @param float $totalPayment
     * @return PaymentAllocation
     */
    public function allocateRepayment($loanId, $totalPayment)
    {
        $allocation = [
            'total_payment' => $totalPayment,
            'penalty_paid' => 0,
            'interest_paid' => 0,
            'principal_paid' => 0,
            'balance_remaining' => 0,
            'schedule_id' => null
        ];

        $remaining = $totalPayment;

        $stmt = $this->db->prepare(
            "SELECT schedule_id, installment_no, due_date, principal_amount, interest_amount, total_due, paid_amount, late_penalty " .
            "FROM loan_repayment_schedule " .
            "WHERE loan_id = ? AND status IN ('pending', 'partial', 'overdue') " .
            "ORDER BY due_date ASC, installment_no ASC"
        );
        $stmt->execute([$loanId]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($schedules)) {
            // No schedule rows available; treat entire payment as principal reduction.
            $allocation['principal_paid'] = $remaining;
            $allocation['balance_remaining'] = 0;
            return $allocation;
        }

        $updateStmt = $this->db->prepare(
            "UPDATE loan_repayment_schedule SET paid_amount = ?, status = ?, paid_date = ? WHERE schedule_id = ?"
        );

        $today = date('Y-m-d');

        foreach ($schedules as $schedule) {
            if ($remaining <= 0) {
                break;
            }

            $paidAmount = (float) $schedule['paid_amount'];
            $totalDue = (float) $schedule['total_due'];
            $latePenalty = (float) $schedule['late_penalty'];
            $interestAmount = (float) $schedule['interest_amount'];
            $principalAmount = (float) $schedule['principal_amount'];

            $alreadyPaidPenalty = min($paidAmount, $latePenalty);
            $remainingPaid = max(0, $paidAmount - $alreadyPaidPenalty);
            $alreadyPaidInterest = min($remainingPaid, $interestAmount);
            $alreadyPaidPrincipal = max(0, $remainingPaid - $alreadyPaidInterest);

            $penaltyRemaining = max(0, $latePenalty - $alreadyPaidPenalty);
            $interestRemaining = max(0, $interestAmount - $alreadyPaidInterest);
            $principalRemaining = max(0, $principalAmount - $alreadyPaidPrincipal);

            if ($penaltyRemaining <= 0 && $interestRemaining <= 0 && $principalRemaining <= 0) {
                continue;
            }

            $payPenalty = min($remaining, $penaltyRemaining);
            $remaining -= $payPenalty;
            $allocation['penalty_paid'] += $payPenalty;

            $payInterest = min($remaining, $interestRemaining);
            $remaining -= $payInterest;
            $allocation['interest_paid'] += $payInterest;

            $payPrincipal = min($remaining, $principalRemaining);
            $remaining -= $payPrincipal;
            $allocation['principal_paid'] += $payPrincipal;

            $paymentApplied = $payPenalty + $payInterest + $payPrincipal;
            if ($paymentApplied <= 0) {
                continue;
            }

            $newPaidAmount = $paidAmount + $paymentApplied;
            $newStatus = $newPaidAmount >= $totalDue ? 'paid' : 'partial';
            $paidDate = $newPaidAmount > 0 ? $today : null;
            $newPrincipalBalance = max(0, $principalAmount - ($alreadyPaidPrincipal + $payPrincipal));

            $updateStmt->execute([
                $newPaidAmount,
                $newStatus,
                $paidDate,
                $schedule['schedule_id']
            ]);

            if ($allocation['schedule_id'] === null) {
                $allocation['schedule_id'] = $schedule['schedule_id'];
            }
        }

        $allocation['balance_remaining'] = max(0, $remaining);
        return $allocation;
    }

    /**
     * Validate member eligibility for loan
     * Checks: active status, savings requirement, existing loans, biometric
     */
    private function validateMemberEligibility($memberId, $productId)
    {
        // Check member status
        $stmt = $this->db->prepare("SELECT status, biometric_enrolled FROM members WHERE member_id = ?");
        $stmt->execute([$memberId]);
        $member = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$member || $member['status'] !== 'active') {
            return ['eligible' => false, 'reason' => 'Member is not active'];
        }

        if (!$member['biometric_enrolled']) {
            return ['eligible' => false, 'reason' => 'Biometric verification required'];
        }

        // Get product details
        $product = $this->getLoanProduct($productId);

        // Check savings history
        $monthsRequired = $product['requires_savings_months'] ?? 3;
        $stmt = $this->db->prepare("
            SELECT COUNT(DISTINCT DATE_FORMAT(st.transaction_date, '%Y-%m')) as months
            FROM savings_transactions st
            JOIN savings_accounts sa ON st.account_id = sa.savings_account_id
            WHERE sa.member_id = ? AND st.transaction_type = 'deposit'
            AND st.transaction_date >= DATE_SUB(NOW(), INTERVAL ? MONTH)
        ");
        $stmt->execute([$memberId, $monthsRequired]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['months'] < $monthsRequired) {
            return ['eligible' => false, 'reason' => "Must have savings for at least {$monthsRequired} months"];
        }

        // Check for active loans
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM loans
            WHERE member_id = ? AND status IN ('approved', 'disbursed')
        ");
        $stmt->execute([$memberId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result['count'] > 0) {
            return ['eligible' => false, 'reason' => 'Member has an active loan'];
        }

        return ['eligible' => true];
    }

    /**
     * Get loan details by ID
     */
    public function getLoanById($loanId)
    {
        $stmt = $this->db->prepare("
            SELECT l.*, lp.product_name, m.full_name, m.membership_no AS membership_no
            FROM loans l
            JOIN loan_products lp ON l.product_id = lp.product_id
            JOIN members m ON l.member_id = m.member_id
            WHERE l.loan_id = ?
        ");
        $stmt->execute([$loanId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Get loan product details
     */
    private function getLoanProduct($productId)
    {
        $stmt = $this->db->prepare("
            SELECT * FROM loan_products WHERE product_id = ? AND status = 'active'
        ");
        $stmt->execute([$productId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    /**
     * Generate unique loan reference number
     * Format: LN + YYYYMMDD + 4-digit random
     */
    private function generateLoanReference()
    {
        $base = 'LN' . date('Ymd');
        $random = str_pad(rand(1, 9999), 4, '0', STR_PAD_LEFT);
        return $base . $random;
    }

    /**
     * Generate receipt number
     */
    private function generateReceiptNumber($prefix)
    {
        return $prefix . date('YmdHis') . rand(100, 999);
    }

    /**
     * Calculate processing fee
     */
    private function calculateProcessingFee($amount, $productId)
    {
        $product = $this->getLoanProduct($productId);
        // loan_products uses 'processing_fee' (percentage) field
        $pct = isset($product['processing_fee']) ? $product['processing_fee'] : 0;
        return round($amount * ($pct / 100), 2);
    }

    /**
     * Update loan balance after repayment
     */
    private function updateLoanBalance($loanId, $allocation)
    {
        // Recompute outstanding principal based on total principal repaid so far.
        $stmt = $this->db->prepare(
            "SELECT COALESCE(SUM(COALESCE(principal_paid,0)), 0) AS principal_repaid " .
            "FROM loan_repayments WHERE loan_id = ?"
        );
        $stmt->execute([$loanId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        $principalRepaid = (float) ($result['principal_repaid'] ?? 0);

        $stmt = $this->db->prepare("SELECT amount_approved FROM loans WHERE loan_id = ?");
        $stmt->execute([$loanId]);
        $loan = $stmt->fetch(PDO::FETCH_ASSOC);
        $approvedAmount = $loan ? (float) $loan['amount_approved'] : 0;

        $newOutstanding = max(0, $approvedAmount - $principalRepaid);

        $stmt = $this->db->prepare("\n            UPDATE loans\n            SET total_paid = total_paid + ?,\n                outstanding_balance = ?\n            WHERE loan_id = ?\n        ");

        $stmt->execute([
            $allocation['total_payment'],
            $newOutstanding,
            $loanId
        ]);
    }

    /**
     * Calculate total penalties accrued
     */
    private function calculateTotalPenalties($loanId)
    {
        $stmt = $this->db->prepare("\n            SELECT COALESCE(SUM(late_penalty), 0) as total\n            FROM loan_repayment_schedule\n            WHERE loan_id = ? AND status IN ('pending', 'partial', 'overdue')\n        ");
        $stmt->execute([$loanId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'];
    }

    /**
     * Update repayment schedule status based on payments
     */
    private function updateScheduleStatus($loanId)
    {
        // Mark fully paid installments and set paid date where appropriate
        $stmt = $this->db->prepare(
            "UPDATE loan_repayment_schedule " .
            "SET status = 'paid', paid_date = CASE WHEN paid_date IS NULL THEN NOW() ELSE paid_date END " .
            "WHERE loan_id = ? AND paid_amount >= total_due"
        );
        $stmt->execute([$loanId]);

        // Preserve overdue on partially paid overdue installments, otherwise mark partial.
        $stmt = $this->db->prepare(
            "UPDATE loan_repayment_schedule " .
            "SET status = 'overdue' " .
            "WHERE loan_id = ? AND status = 'overdue' AND paid_amount > 0 AND paid_amount < total_due"
        );
        $stmt->execute([$loanId]);

        $stmt = $this->db->prepare(
            "UPDATE loan_repayment_schedule " .
            "SET status = 'partial' " .
            "WHERE loan_id = ? AND status IN ('pending', 'partial') AND paid_amount > 0 AND paid_amount < total_due"
        );
        $stmt->execute([$loanId]);
    }

    /**
     * Check if loan is fully paid
     */
    private function isLoanCompleted($loanId)
    {
        $stmt = $this->db->prepare(
            "SELECT COUNT(*) as unpaid_count " .
            "FROM loan_repayment_schedule " .
            "WHERE loan_id = ? AND status != 'paid'"
        );
        $stmt->execute([$loanId]);
        $scheduleRow = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($scheduleRow && $scheduleRow['unpaid_count'] == 0) {
            return true;
        }

        $stmt = $this->db->prepare("SELECT outstanding_balance FROM loans WHERE loan_id = ?");
        $stmt->execute([$loanId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result && $result['outstanding_balance'] <= 0;
    }

    /**
     * Mark loan as completed
     */
    private function completeLoan($loanId)
    {
        $stmt = $this->db->prepare("
            UPDATE loans SET status = 'completed', last_payment_date = NOW()
            WHERE loan_id = ?
        ");
        $stmt->execute([$loanId]);
    }

    /**
     * Get defaulters list
     */
    public function getDefaulters($status = 'active', $limit = 100)
    {
        $stmt = $this->db->prepare("
            SELECT d.*, l.loan_ref_no AS loan_reference, m.full_name, m.membership_no AS membership_no,
                   m.phone, l.amount_approved, l.outstanding_balance
            FROM defaulters_list d
            JOIN loans l ON d.loan_id = l.loan_id
            JOIN members m ON d.member_id = m.member_id
            WHERE d.status = ?
            ORDER BY d.days_overdue DESC
            LIMIT ?
        ");
        $stmt->execute([$status, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate loan balance as of specific date
     */
    public function calculateLoanBalance($loanId, $asOfDate = null)
    {
        $asOfDate = $asOfDate ?: date('Y-m-d');

        $stmt = $this->db->prepare("
            SELECT 
                l.principal_balance,
                (SELECT COALESCE(SUM(interest_due - interest_paid), 0)
                 FROM loan_repayment_schedule 
                 WHERE loan_id = ? AND due_date <= ?) as accrued_interest,
                (SELECT COALESCE(SUM(late_penalty), 0)
                 FROM loan_repayment_schedule 
                 WHERE loan_id = ? AND due_date <= ?) as accrued_penalties
            FROM loans l
            WHERE l.loan_id = ?
        ");

        $stmt->execute([$loanId, $asOfDate, $loanId, $asOfDate, $loanId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

?>
