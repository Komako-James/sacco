<?php
/**
 * SACCO Management System - Core Models and Entities
 * Represents the domain model classes
 */

namespace SACCO\Models;

use DateTime;

/**
 * Member Entity
 * Represents a member of the SACCO
 */
class Member
{
    public $member_id;
    public $membership_number;  // 001-700
    public $national_id;
    public $full_name;
    public $phone;
    public $email;
    public $date_of_birth;
    public $gender;
    public $employer_id;
    public $district_id;
    public $join_date;
    public $status;  // active, inactive, deceased, suspended
    public $account_status;  // normal, restricted, frozen
    public $profile_photo_path;
    public $signature_path;
    public $biometric_enrolled;
    public $gross_salary;
    public $bank_account_number;
    public $standing_order_amount;
    public $standing_order_frequency;
    
    public function isActive()
    {
        return $this->status === 'active' && $this->account_status === 'normal';
    }

    public function canApplyForLoan()
    {
        return $this->isActive() && $this->biometric_enrolled;
    }

    public function getSavingsMonths($joinDate)
    {
        $join = new DateTime($joinDate);
        $now = new DateTime();
        return $now->diff($join)->m + ($now->diff($join)->y * 12);
    }
}

/**
 * Loan Entity
 * Represents a loan issued to a member
 */
class Loan
{
    public $loan_id;
    public $loan_reference_number;
    public $member_id;
    public $product_id;
    public $amount_requested;
    public $amount_approved;
    public $annual_interest_rate;
    public $monthly_interest_rate;
    public $repayment_period_months;
    public $processing_fee;
    public $loan_purpose;
    public $application_date;
    public $approval_date;
    public $disbursement_date;
    public $first_payment_date;
    public $status;  // applied, approved, disbursed, completed, defaulted
    public $outstanding_balance;
    public $principal_balance;
    public $interest_accrued;
    public $penalty_accrued;
    public $total_paid;
    public $days_overdue;
    public $default_status;  // none, early_warning, warning, default, legal_action
    
    public function isActive()
    {
        return in_array($this->status, ['approved', 'disbursed']);
    }

    public function isDisbursed()
    {
        return $this->status === 'disbursed';
    }

    public function isInDefault()
    {
        return $this->default_status !== 'none' && $this->days_overdue > 0;
    }

    public function getTotalOutstanding()
    {
        return $this->principal_balance + $this->interest_accrued + $this->penalty_accrued;
    }
}

/**
 * LoanRepaymentSchedule Entity
 * Represents individual installments
 */
class LoanRepaymentSchedule
{
    public $schedule_id;
    public $loan_id;
    public $installment_number;
    public $due_date;
    public $principal_due;
    public $interest_due;
    public $penalty_due;
    public $total_due;
    public $principal_paid;
    public $interest_paid;
    public $penalty_paid;
    public $total_paid;
    public $principal_balance;
    public $paid_date;
    public $status;  // pending, partial, paid, overdue
    public $days_overdue;
    
    public function isOverdue()
    {
        return $this->days_overdue > 0;
    }

    public function getBalanceDue()
    {
        return $this->total_due - $this->total_paid;
    }

    public function getPaymentStatus()
    {
        if ($this->getBalanceDue() == 0) {
            return 'paid';
        } elseif ($this->total_paid > 0) {
            return 'partial';
        } elseif ($this->isOverdue()) {
            return 'overdue';
        }
        return 'pending';
    }
}

/**
 * SavingsAccount Entity
 */
class SavingsAccount
{
    public $savings_account_id;
    public $member_id;
    public $account_type;  // monthly_savings, voluntary_savings, fixed_deposit, emergency_fund
    public $account_number;
    public $balance;
    public $interest_rate;
    public $opening_balance;
    public $opening_date;
    public $maturity_date;
    public $last_interest_posted;
    public $status;  // active, dormant, closed
    
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isMature()
    {
        if ($this->account_type === 'fixed_deposit' && $this->maturity_date) {
            return strtotime(date('Y-m-d')) >= strtotime($this->maturity_date);
        }
        return false;
    }
}

/**
 * ShareAccount Entity
 */
class ShareAccount
{
    public $share_account_id;
    public $member_id;
    public $account_number;
    public $balance;
    public $par_value;  // Par value per share
    public $number_of_shares;
    public $status;  // active, dormant, closed
    
    public function getNumberOfShares()
    {
        return $this->balance / $this->par_value;
    }

    public function isActive()
    {
        return $this->status === 'active';
    }
}

/**
 * LoanRepayment Entity
 * Represents actual payment made towards a loan
 */
class LoanRepayment
{
    public $repayment_id;
    public $loan_id;
    public $schedule_id;
    public $repayment_date;
    public $amount_paid;
    public $principal_paid;
    public $interest_paid;
    public $penalty_paid;
    public $payment_method;  // cash, mobile_money, bank_transfer, salary_deduction, standing_order
    public $reference_number;
    public $receipt_number;
    public $bank_reference;
    public $posted_by;
    public $status;  // pending, posted, approved, reversed
    
    public function isComplete()
    {
        $total_allocated = $this->principal_paid + $this->interest_paid + $this->penalty_paid;
        return abs($this->amount_paid - $total_allocated) < 0.01;  // Account for floating point
    }
}

/**
 * LedgerEntry Entity
 * Double-entry accounting system
 */
class LedgerEntry
{
    public $entry_id;
    public $ledger_code;
    public $ledger_name;
    public $entry_date;
    public $receipt_number;
    public $transaction_reference;
    public $transaction_type;
    public $debit;
    public $credit;
    public $description;
    public $payment_method;
    public $posted_by;
    public $approved_by;
    public $member_id;
    public $account_type;  // shares, savings, loans, interest, penalties
    public $status;  // pending, posted, reversed
    public $reversal_of_id;
    
    public function isBalanced()
    {
        return abs($this->debit - $this->credit) < 0.01;
    }

    public function isPosted()
    {
        return $this->status === 'posted';
    }

    public function isReversed()
    {
        return $this->status === 'reversed';
    }
}

/**
 * SalaryDeductionBatch Entity
 */
class SalaryDeductionBatch
{
    public $batch_id;
    public $batch_reference;
    public $batch_month;  // YYYY-MM
    public $employer_id;
    public $file_name;
    public $total_records;
    public $successful_records;
    public $failed_records;
    public $total_amount;
    public $status;  // uploaded, processing, processed, failed, cancelled
    public $uploaded_by;
    public $processed_by;
    
    public function getSuccessRate()
    {
        if ($this->total_records == 0) return 0;
        return ($this->successful_records / $this->total_records) * 100;
    }

    public function isProcessed()
    {
        return $this->status === 'processed';
    }

    public function canReprocess()
    {
        return in_array($this->status, ['failed', 'cancelled']);
    }
}

/**
 * StandingOrder Entity
 */
class StandingOrder
{
    public $standing_order_id;
    public $member_id;
    public $standing_order_reference;
    public $bank_reference_number;
    public $start_date;
    public $end_date;
    public $frequency;  // weekly, bi-weekly, monthly
    public $amount;
    public $next_expected_date;
    public $last_received_date;
    public $total_received;
    public $missed_count;
    public $status;  // active, inactive, cancelled, completed
    
    public function isActive()
    {
        return $this->status === 'active' && (!$this->end_date || strtotime(date('Y-m-d')) <= strtotime($this->end_date));
    }

    public function isMissed($asOfDate = null)
    {
        $asOfDate = $asOfDate ?: date('Y-m-d');
        return strtotime($this->next_expected_date) < strtotime($asOfDate) && !$this->last_received_date;
    }
}

/**
 * User Entity
 */
class User
{
    public $user_id;
    public $username;
    public $email;
    public $full_name;
    public $phone;
    public $role;  // admin, manager, accountant, loan_officer, cashier, member, audit
    public $status;  // active, inactive, suspended
    public $last_login;
    public $login_attempts;
    public $locked_until;
    public $biometric_enabled;
    public $two_factor_enabled;
    
    public function isAdmin()
    {
        return $this->role === 'admin';
    }

    public function canApproveLoans()
    {
        return in_array($this->role, ['admin', 'loan_officer']);
    }

    public function canPostTransactions()
    {
        return in_array($this->role, ['admin', 'accountant', 'cashier']);
    }

    public function isLocked()
    {
        return $this->locked_until && strtotime($this->locked_until) > time();
    }

    public function hasRole($requiredRole)
    {
        return $this->role === $requiredRole && $this->status === 'active';
    }
}

/**
 * AuditLog Entity
 */
class AuditLog
{
    public $log_id;
    public $user_id;
    public $action;
    public $entity_type;
    public $entity_id;
    public $old_values;
    public $new_values;
    public $ip_address;
    public $user_agent;
    public $status;  // success, failure
    public $error_message;
    public $timestamp;
    
    public function getChangedFields()
    {
        $old = json_decode($this->old_values, true) ?: [];
        $new = json_decode($this->new_values, true) ?: [];
        
        $changes = [];
        $allKeys = array_unique(array_merge(array_keys($old), array_keys($new)));
        
        foreach ($allKeys as $key) {
            $oldValue = $old[$key] ?? null;
            $newValue = $new[$key] ?? null;
            if ($oldValue !== $newValue) {
                $changes[$key] = ['from' => $oldValue, 'to' => $newValue];
            }
        }
        return $changes;
    }
}

/**
 * Transaction Entity (General)
 */
class Transaction
{
    public $transaction_id;
    public $transaction_type;
    public $amount;
    public $payment_method;
    public $reference_number;
    public $receipt_number;
    public $description;
    public $status;  // pending, posted, approved, reversed
    public $posted_by;
    public $approved_by;
    public $transaction_date;
    public $created_at;
    
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isPosted()
    {
        return $this->status === 'posted';
    }

    public function canReverse()
    {
        return in_array($this->status, ['posted', 'approved']);
    }
}

/**
 * LoanProduct Entity
 */
class LoanProduct
{
    public $product_id;
    public $product_code;
    public $product_name;
    public $product_type;  // salary_loan, business_loan
    public $description;
    public $min_amount;
    public $max_amount;
    public $annual_interest_rate;
    public $monthly_interest_rate;
    public $min_repayment_months;
    public $max_repayment_months;
    public $processing_fee_percentage;
    public $late_penalty_daily;
    public $requires_guarantors;
    public $min_guarantors;
    public $requires_savings_months;
    public $status;  // active, inactive
    
    public function isActive()
    {
        return $this->status === 'active';
    }

    public function isSalaryLoan()
    {
        return $this->product_type === 'salary_loan';
    }

    public function isBusinessLoan()
    {
        return $this->product_type === 'business_loan';
    }
}

/**
 * PaymentAllocation Entity
 * Represents how a payment is split among interest, principal, penalties
 */
class PaymentAllocation
{
    public $total_payment;
    public $interest_paid;
    public $principal_paid;
    public $penalty_paid;
    public $balance_remaining;
    
    public function isComplete()
    {
        return abs($this->balance_remaining) < 0.01;
    }

    public function getSummary()
    {
        return [
            'total' => $this->total_payment,
            'interest' => $this->interest_paid,
            'principal' => $this->principal_paid,
            'penalty' => $this->penalty_paid,
            'remaining' => $this->balance_remaining
        ];
    }
}

/**
 * InterestCalculation Entity
 */
class InterestCalculation
{
    public $calculation_id;
    public $calculation_date;
    public $calculation_month;  // YYYY-MM
    public $account_type;  // savings, loans, shares
    public $total_accounts;
    public $total_amount_calculated;
    public $total_interest;
    public $created_by;
    public $status;  // draft, posted, reversed
    
    public function isPosted()
    {
        return $this->status === 'posted';
    }

    public function getAverageInterestRate()
    {
        if ($this->total_amount_calculated == 0) return 0;
        return ($this->total_interest / $this->total_amount_calculated) * 100;
    }
}

/**
 * MemberStatement Entity
 */
class MemberStatement
{
    public $statement_id;
    public $member_id;
    public $statement_period_start;
    public $statement_period_end;
    public $share_opening_balance;
    public $share_closing_balance;
    public $savings_opening_balance;
    public $savings_closing_balance;
    public $loan_balance;
    public $total_interest_accrued;
    public $generated_at;
    public $file_path;
    public $file_format;  // pdf, excel, csv
    
    public function getShareActivity()
    {
        return $this->share_closing_balance - $this->share_opening_balance;
    }

    public function getSavingsActivity()
    {
        return $this->savings_closing_balance - $this->savings_opening_balance;
    }

    public function getTotalAssetsAtClose()
    {
        return $this->share_closing_balance + $this->savings_closing_balance - $this->loan_balance;
    }
}

?>
