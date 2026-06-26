<?php
/**
 * SACCO Interest Calculation Service
 * Calculates and posts monthly interest for loans and savings
 * Handles daily accrual and monthly posting
 */

namespace SACCO\Services;

use PDO;
use Exception;

class InterestCalculationService
{
    private $db;

    public function __construct(PDO $database)
    {
        $this->db = $database;
    }

    /**
     * Calculate monthly interest for all active loans
     * Salary Loans: 26% annual = 2.17% monthly (declining balance)
     * Business Loans: 5% monthly (compound)
     * 
     * @param string $month YYYY-MM
     * @param int $postedBy
     * @return array ['success' => bool, 'calculation_id' => int, 'total_interest' => float]
     */
    public function calculateMonthlyLoanInterest($month, $postedBy)
    {
        try {
            $this->db->beginTransaction();

            // Get month boundaries
            $firstDay = $month . '-01';
            $lastDay = date('Y-m-t', strtotime($firstDay));

            // Find all active loans
            $stmt = $this->db->prepare("
                SELECT l.*, lp.annual_interest_rate, lp.monthly_interest_rate
                FROM loans l
                JOIN loan_products lp ON l.product_id = lp.product_id
                WHERE l.status = 'disbursed'
                  AND l.disbursement_date <= ?
                  AND (l.last_payment_date IS NULL OR l.last_payment_date <= ?)
            ");
            $stmt->execute([$lastDay, $lastDay]);
            $loans = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalInterest = 0;
            $loanInterestByLoan = [];

            // Calculate interest for each loan
            foreach ($loans as $loan) {
                $interest = $this->calculateLoanMonthlyInterest($loan, $month);
                
                // Update loan with accrued interest
                $stmt = $this->db->prepare("
                    UPDATE loans
                    SET interest_accrued = interest_accrued + ?,
                        outstanding_balance = outstanding_balance + ?
                    WHERE loan_id = ?
                ");
                $stmt->execute([$interest, $interest, $loan['loan_id']]);

                // Update repayment schedule - add interest to remaining installments
                $stmt = $this->db->prepare("
                    UPDATE loan_repayment_schedule
                    SET interest_due = interest_due + ?,
                        total_due = total_due + ?
                    WHERE loan_id = ? AND status IN ('pending', 'partial', 'overdue')
                ");
                $interestPerInstallment = $interest / $this->getRemainningInstallments($loan['loan_id']);
                $stmt->execute([$interestPerInstallment, $interestPerInstallment, $loan['loan_id']]);

                $totalInterest += $interest;
                $loanInterestByLoan[$loan['loan_id']] = $interest;
            }

            // Log calculation
            $stmt = $this->db->prepare("
                INSERT INTO interest_calculations
                (calculation_date, calculation_month, account_type, total_accounts, total_interest, created_by, status)
                VALUES (NOW(), ?, 'loans', ?, ?, ?, 'posted')
            ");
            $stmt->execute([$month, count($loans), $totalInterest, $postedBy]);
            $calculationId = $this->db->lastInsertId();

            // Post to ledger
            LedgerService::postMonthlyInterest($loanInterestByLoan, $month, $postedBy);

            $this->db->commit();

            return [
                'success' => true,
                'calculation_id' => $calculationId,
                'loans_processed' => count($loans),
                'total_interest' => $totalInterest,
                'message' => "Interest calculated for {$month}"
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Calculate monthly interest for all savings accounts
     * Default: 5% annual = 0.417% monthly
     * 
     * @param string $month YYYY-MM
     * @param float $annualRate (optional, default 5%)
     * @param int $postedBy
     * @return array
     */
    public function calculateMonthlySavingsInterest($month, $annualRate = 5.0, $postedBy)
    {
        try {
            $this->db->beginTransaction();

            $monthlyRate = $annualRate / 100 / 12;
            $firstDay = $month . '-01';
            $lastDay = date('Y-m-t', strtotime($firstDay));

            // Get all active savings accounts
            $stmt = $this->db->prepare("
                SELECT sa.*, m.member_id
                FROM savings_accounts sa
                JOIN members m ON sa.member_id = m.member_id
                WHERE sa.status = 'active'
                  AND sa.opening_date <= ?
                  AND (sa.last_interest_posted IS NULL OR sa.last_interest_posted < ?)
            ");
            $stmt->execute([$lastDay, $firstDay]);
            $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $totalInterest = 0;
            $savingsInterestByAccount = [];

            // Calculate interest for each account
            foreach ($accounts as $account) {
                $interest = round($account['balance'] * $monthlyRate, 2);

                // Update account balance
                $stmt = $this->db->prepare("
                    UPDATE savings_accounts
                    SET balance = balance + ?,
                        last_interest_posted = ?
                    WHERE savings_account_id = ?
                ");
                $stmt->execute([$interest, $lastDay, $account['savings_account_id']]);

                // Record as transaction
                $stmt = $this->db->prepare("
                    INSERT INTO savings_transactions
                    (savings_account_id, transaction_type, amount, previous_balance, new_balance,
                     payment_method, receipt_number, description, posted_by, status, transaction_date)
                    VALUES (?, 'interest', ?, ?, ?, 'system', ?, ?, ?, 'posted', NOW())
                ");

                $receiptNumber = 'INT' . date('YmdHis') . rand(100, 999);
                $stmt->execute([
                    $account['savings_account_id'],
                    $interest,
                    $account['balance'],
                    $account['balance'] + $interest,
                    $receiptNumber,
                    "Monthly interest for {$month}",
                    $postedBy
                ]);

                $totalInterest += $interest;
                $savingsInterestByAccount[$account['savings_account_id']] = $interest;
            }

            // Log calculation
            $stmt = $this->db->prepare("
                INSERT INTO interest_calculations
                (calculation_date, calculation_month, account_type, total_accounts, total_interest, created_by, status)
                VALUES (NOW(), ?, 'savings', ?, ?, ?, 'posted')
            ");
            $stmt->execute([$month, count($accounts), $totalInterest, $postedBy]);

                // Post aggregated savings interest to ledger
                if ($totalInterest > 0) {
                    require_once __DIR__ . '/LedgerService.php';
                    \SACCO\Services\LedgerService::postSavingsInterest($totalInterest, $month, $postedBy);
                }

            $this->db->commit();

            return [
                'success' => true,
                'accounts_processed' => count($accounts),
                'total_interest' => $totalInterest,
                'message' => "Savings interest calculated for {$month}"
            ];
        } catch (Exception $e) {
            $this->db->rollBack();
            return ['success' => false, 'message' => $e->getMessage()];
        }
    }

    /**
     * Calculate interest for a specific loan
     * Supports both declining balance and compound methods
     * 
     * @param array $loan
     * @param string $month YYYY-MM
     * @return float interest amount
     */
    private function calculateLoanMonthlyInterest($loan, $month)
    {
        // Get number of days in month
        $firstDay = $month . '-01';
        $daysInMonth = date('t', strtotime($firstDay));
        $dailyRate = $loan['annual_interest_rate'] / 100 / 365;

        $principalBalance = isset($loan['principal_balance']) ? (float)$loan['principal_balance'] : (float)$loan['outstanding_balance'];

        // Calculate based on product type
        if ($loan['product_type'] === 'salary_loan') {
            // Salary loans: simple interest monthly on declining balance
            $monthlyRate = $loan['monthly_interest_rate'] / 100;
            $interest = round($principalBalance * $monthlyRate, 2);
        } else {
            // Business loans: 5% monthly (compound)
            $monthlyRate = 0.05;
            $interest = round($principalBalance * $monthlyRate, 2);
        }

        return $interest;
    }

    /**
     * Calculate daily interest accrual (for reporting purposes)
     * Used to show current interest due on any date
     * 
     * @param int $loanId
     * @param string $asOfDate YYYY-MM-DD
     * @return float
     */
    public function calculateDailyInterest($loanId, $asOfDate = null)
    {
        $asOfDate = $asOfDate ?: date('Y-m-d');

        $stmt = $this->db->prepare("
            SELECT l.*, lp.annual_interest_rate, lp.monthly_interest_rate
            FROM loans l
            JOIN loan_products lp ON l.product_id = lp.product_id
            WHERE l.loan_id = ? AND l.status = 'disbursed'
        ");
        $stmt->execute([$loanId]);
        $loan = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$loan) return 0;

        // Daily interest rate
        $dailyRate = $loan['annual_interest_rate'] / 100 / 365;

        // Days since last posting
        $lastPosted = $loan['last_interest_posted'] ?: $loan['disbursement_date'];
        $daysElapsed = date_diff(
            date_create($lastPosted),
            date_create($asOfDate)
        )->days;

        $principalBalance = isset($loan['principal_balance']) ? (float)$loan['principal_balance'] : (float)$loan['outstanding_balance'];
        return round($principalBalance * $dailyRate * $daysElapsed, 2);
    }

    /**
     * Apply late payment penalties
     * 0.5% daily, max 5% monthly per due date
     * 
     * @param int $loanId
     * @return float total penalties applied
     */
    public function applyPenalties($loanId)
    {
        $today = date('Y-m-d');
        $totalPenalties = 0;

        $stmt = $this->db->prepare("
            SELECT * FROM loan_repayment_schedule
            WHERE loan_id = ? AND due_date < ? AND status IN ('pending', 'partial', 'overdue')
            ORDER BY due_date ASC
        ");
        $stmt->execute([$loanId, $today]);
        $schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($schedules as $schedule) {
            $daysOverdue = date_diff(
                date_create($schedule['due_date']),
                date_create($today)
            )->days;

            if ($daysOverdue > 0) {
                // Calculate penalty: 0.5% per day, but cap at 5% per month
                $dailyPenalty = $schedule['total_due'] * 0.005;
                $totalMonthlyPenalty = $schedule['total_due'] * 0.05;

                $calculatedPenalty = min($dailyPenalty * $daysOverdue, $totalMonthlyPenalty);

                // Update schedule: add late penalty and mark overdue
                $stmt = $this->db->prepare("\n                    UPDATE loan_repayment_schedule\n                    SET late_penalty = COALESCE(late_penalty, 0) + ?,\n                        total_due = total_due + ?,\n                        status = 'overdue'\n                    WHERE schedule_id = ?\n                ");
                $stmt->execute([
                    $calculatedPenalty,
                    $calculatedPenalty,
                    $schedule['schedule_id']
                ]);

                $totalPenalties += $calculatedPenalty;
            }
        }

        // Update loan totals
        if ($totalPenalties > 0) {
            $stmt = $this->db->prepare("
                UPDATE loans
                SET penalty_accrued = penalty_accrued + ?,
                    outstanding_balance = outstanding_balance + ?,
                    days_overdue = DATEDIFF(?, (SELECT MAX(due_date) FROM loan_repayment_schedule WHERE loan_id = ? AND status = 'overdue'))
                WHERE loan_id = ?
            ");
            $stmt->execute([$totalPenalties, $totalPenalties, $today, $loanId, $loanId]);
        }

        return $totalPenalties;
    }

    /**
     * Get interest breakdown for a specific loan and month
     * Shows principal, interest, and penalty for each installment
     * 
     * @param int $loanId
     * @param string $month YYYY-MM
     * @return array
     */
    public function getInterestBreakdown($loanId, $month = null)
    {
        $month = $month ?: date('Y-m');

        $stmt = $this->db->prepare("
            SELECT 
                lrs.installment_number,
                lrs.due_date,
                lrs.principal_due,
                lrs.interest_due,
                lrs.penalty_due,
                lrs.total_due,
                lrs.principal_paid,
                lrs.interest_paid,
                lrs.penalty_paid,
                lrs.status,
                (lrs.principal_due - COALESCE(lrs.principal_paid, 0)) as principal_balance,
                (lrs.interest_due - COALESCE(lrs.interest_paid, 0)) as interest_balance,
                (lrs.penalty_due - COALESCE(lrs.penalty_paid, 0)) as penalty_balance
            FROM loan_repayment_schedule lrs
            WHERE lrs.loan_id = ?
              AND DATE_FORMAT(lrs.due_date, '%Y-%m') = ?
            ORDER BY lrs.installment_number
        ");

        $stmt->execute([$loanId, $month]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Calculate projected loan balance as of specific future date
     * Used for forecasting and reporting
     * 
     * @param int $loanId
     * @param string $targetDate YYYY-MM-DD
     * @return float
     */
    public function projectLoanBalance($loanId, $targetDate)
    {
        $stmt = $this->db->prepare("
            SELECT 
                COALESCE(l.principal_balance, l.outstanding_balance) as principal_balance,
                (SELECT COALESCE(SUM(interest_due - interest_paid), 0)
                 FROM loan_repayment_schedule 
                 WHERE loan_id = ? AND due_date <= ?) as projected_interest
            FROM loans l
            WHERE l.loan_id = ?
        ");

        $stmt->execute([$loanId, $targetDate, $loanId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['principal_balance'] + $result['projected_interest'];
    }

    /**
     * Generate interest report for period
     * Breakdown by loan type, member, etc.
     * 
     * @param string $periodStart YYYY-MM-DD
     * @param string $periodEnd YYYY-MM-DD
     * @return array
     */
    public function generateInterestReport($periodStart, $periodEnd)
    {
        $stmt = $this->db->prepare("
            SELECT 
                lp.product_type,
                lp.product_name,
                COUNT(DISTINCT l.loan_id) as loan_count,
                SUM(lrs.interest_due) as total_interest_due,
                SUM(lrs.interest_paid) as total_interest_paid,
                SUM(lrs.interest_due - COALESCE(lrs.interest_paid, 0)) as interest_outstanding
            FROM loans l
            JOIN loan_products lp ON l.product_id = lp.product_id
            LEFT JOIN loan_repayment_schedule lrs ON l.loan_id = lrs.loan_id
                AND lrs.due_date BETWEEN ? AND ?
            WHERE l.status IN ('disbursed', 'completed')
            GROUP BY lp.product_type, lp.product_name
        ");

        $stmt->execute([$periodStart, $periodEnd]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get remaining installments for a loan
     */
    private function getRemainningInstallments($loanId)
    {
        $stmt = $this->db->prepare("
            SELECT COUNT(*) as count FROM loan_repayment_schedule
            WHERE loan_id = ? AND status IN ('pending', 'partial', 'overdue')
        ");
        $stmt->execute([$loanId]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return max(1, $result['count']);  // Avoid division by zero
    }
}

?>
