<?php
/**
 * SACCO Ledger Service - Double-Entry Accounting System
 * Ensures balanced, auditable transactions following IFRS principles
 */

namespace SACCO\Services;

require_once __DIR__ . '/../../config/db_connection.php';

use SACCO\Models\LedgerEntry;
use PDO;
use Exception;

class LedgerService
{
    private $db;
    
    // Chart of Accounts - Pre-defined
    const COA_CASH = '1010';
    const COA_BANK = '1020';
    const COA_LOANS = '1030';
    const COA_INTEREST_RECEIVABLE = '1040';
    const COA_MEMBER_SHARES = '2010';
    const COA_MEMBER_SAVINGS = '2020';
    const COA_INTEREST_PAYABLE = '2030';
    const COA_RETAINED_EARNINGS = '3010';
    const COA_INTEREST_INCOME = '4010';
    const COA_PROCESSING_FEES = '4020';
    const COA_PENALTY_INCOME = '4030';
    const COA_LOAN_LOSS_PROVISION = '5010';
    const COA_STAFF_COSTS = '5020';
    const COA_ADMIN_EXPENSES = '5030';

    public function __construct(PDO $database)
    {
        $this->db = $database;
    }

    private static function getConnection(): PDO
    {
        global $db;

        if ($db instanceof PDO) {
            return $db;
        }

        return \Database::getInstance()->getConnection();
    }

    private static function tableExists(PDO $db, string $table): bool
    {
        try {
            $stmt = $db->prepare(
                "SELECT 1 FROM information_schema.tables WHERE table_schema = DATABASE() AND table_name = ? LIMIT 1"
            );
            $stmt->execute([$table]);
            return (bool) $stmt->fetchColumn();
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Post a loan disbursement to the ledger
     * Debit: Loan Portfolio (Assets)
     * Credit: Bank/Cash Account (Assets) OR Member Loan Liability
     * 
     * @param int $loanId
     * @param float $principalAmount
     * @param float $processingFee
     * @param int $postedBy
     */
    public static function postLoanDisbursement($loanId, $principalAmount, $processingFee, $postedBy)
    {
        $receiptNumber = self::generateReceiptNumber('LD');
        
        $entries = [
            // Debit: Loan Portfolio (Asset)
            [
                'ledger_code' => self::COA_LOANS,
                'debit' => $principalAmount,
                'credit' => 0,
                'description' => "Loan disbursement for loan #{$loanId}"
            ],
            // Credit: Bank Account (Asset reduced)
            [
                'ledger_code' => self::COA_BANK,
                'debit' => 0,
                'credit' => $principalAmount,
                'description' => "Loan disbursement payment"
            ],
            // Debit: Bank Account (for fee collection)
            [
                'ledger_code' => self::COA_BANK,
                'debit' => $processingFee,
                'credit' => 0,
                'description' => "Processing fee collected"
            ],
            // Credit: Fee Income
            [
                'ledger_code' => self::COA_PROCESSING_FEES,
                'debit' => 0,
                'credit' => $processingFee,
                'description' => "Processing fee income"
            ]
        ];

        self::postJournalEntries($entries, $receiptNumber, "Loan Disbursement", $postedBy);
    }

    /**
     * Post a loan repayment to the ledger
     * Debit: Bank/Cash
     * Credit: Loan Portfolio (principal), Interest Income, Penalty Income
     * 
     * @param int $repaymentId
     * @param array $allocation ['principal_paid', 'interest_paid', 'penalty_paid']
     * @param int $postedBy
     */
    public static function postLoanRepayment($repaymentId, $allocation, $postedBy)
    {
        $totalPayment = $allocation['principal_paid'] + $allocation['interest_paid'] + $allocation['penalty_paid'];
        $receiptNumber = self::generateReceiptNumber('RP');

        $entries = [];

        // Debit: Cash/Bank (Asset)
        $entries[] = [
            'ledger_code' => self::COA_CASH,
            'debit' => $totalPayment,
            'credit' => 0,
            'description' => "Loan repayment received"
        ];

        // Credit: Principal
        if ($allocation['principal_paid'] > 0) {
            $entries[] = [
                'ledger_code' => self::COA_LOANS,
                'debit' => 0,
                'credit' => $allocation['principal_paid'],
                'description' => "Principal repayment"
            ];
        }

        // Credit: Interest Income
        if ($allocation['interest_paid'] > 0) {
            $entries[] = [
                'ledger_code' => self::COA_INTEREST_INCOME,
                'debit' => 0,
                'credit' => $allocation['interest_paid'],
                'description' => "Interest received"
            ];
        }

        // Credit: Penalty Income
        if ($allocation['penalty_paid'] > 0) {
            $entries[] = [
                'ledger_code' => self::COA_PENALTY_INCOME,
                'debit' => 0,
                'credit' => $allocation['penalty_paid'],
                'description' => "Penalty collected"
            ];
        }

        self::postJournalEntries($entries, $receiptNumber, "Loan Repayment", $postedBy);
    }

    /**
     * Post savings deposit to ledger
     * Debit: Cash/Bank
     * Credit: Member Savings Liability
     * 
     * @param int $memberId
     * @param float $amount
     * @param string $paymentMethod
     * @param int $postedBy
     */
    public static function postSavingsDeposit($memberId, $amount, $paymentMethod, $postedBy)
    {
        $receiptNumber = self::generateReceiptNumber('SD');

        $entries = [
            // Debit: Cash or Bank (depending on payment method)
            [
                'ledger_code' => $paymentMethod === 'cash' ? self::COA_CASH : self::COA_BANK,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Savings deposit received ({$paymentMethod})"
            ],
            // Credit: Member Savings (Liability)
            [
                'ledger_code' => self::COA_MEMBER_SAVINGS,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Member savings deposit"
            ]
        ];

        self::postJournalEntries($entries, $receiptNumber, "Savings Deposit", $postedBy);
    }

    /**
     * Post savings withdrawal from ledger
     * Debit: Member Savings Liability
     * Credit: Cash/Bank
     * 
     * @param int $memberId
     * @param float $amount
     * @param string $paymentMethod
     * @param int $postedBy
     */
    public static function postSavingsWithdrawal($memberId, $amount, $paymentMethod, $postedBy)
    {
        $receiptNumber = self::generateReceiptNumber('SW');

        $entries = [
            // Debit: Member Savings (Liability reduces)
            [
                'ledger_code' => self::COA_MEMBER_SAVINGS,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Savings withdrawal"
            ],
            // Credit: Cash or Bank
            [
                'ledger_code' => $paymentMethod === 'cash' ? self::COA_CASH : self::COA_BANK,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Savings paid out ({$paymentMethod})"
            ]
        ];

        self::postJournalEntries($entries, $receiptNumber, "Savings Withdrawal", $postedBy);
    }

    /**
     * Post monthly interest accrual
     * Debit: Interest Receivable (Asset)
     * Credit: Interest Income (Income)
     * 
     * @param array $interestByAccount ['account_id' => amount, ...]
     * @param string $month YYYY-MM
     * @param int $postedBy
     */
    public static function postMonthlyInterest($interestByAccount, $month, $postedBy)
    {
        $totalInterest = array_sum($interestByAccount);
        if ($totalInterest <= 0) return;

        $receiptNumber = self::generateReceiptNumber('INT');

        $entries = [
            // Debit: Interest Receivable/Accrued (Asset)
            [
                'ledger_code' => self::COA_INTEREST_RECEIVABLE,
                'debit' => $totalInterest,
                'credit' => 0,
                'description' => "Monthly interest accrued for {$month}"
            ],
            // Credit: Interest Income
            [
                'ledger_code' => self::COA_INTEREST_INCOME,
                'debit' => 0,
                'credit' => $totalInterest,
                'description' => "Interest income earned for {$month}"
            ]
        ];

        self::postJournalEntries($entries, $receiptNumber, "Interest Accrual", $postedBy);
    }

    /**
     * Post share purchase/capital contribution
     * Debit: Cash
     * Credit: Member Share Capital
     * 
     * @param int $memberId
     * @param float $amount
     * @param int $postedBy
     */
    public static function postSharePurchase($memberId, $amount, $postedBy)
    {
        $receiptNumber = self::generateReceiptNumber('SH');

        $entries = [
            // Debit: Cash
            [
                'ledger_code' => self::COA_CASH,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Share capital received"
            ],
            // Credit: Member Shares (Liability/Equity)
            [
                'ledger_code' => self::COA_MEMBER_SHARES,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Member share contribution"
            ]
        ];

        self::postJournalEntries($entries, $receiptNumber, "Share Purchase", $postedBy);
    }

    /**
     * Post share purchase funded from savings account
     * Debit: Member Savings (liability reduction)
     * Credit: Member Share Capital (equity increase)
     *
     * @param int $memberId
     * @param float $amount
     * @param int $postedBy
     * @param string|null $referenceNumber
     */
    public static function postSharePurchaseFromSavings($memberId, $amount, $postedBy, $referenceNumber = null)
    {
        $referenceNumber = $referenceNumber ?: self::generateReceiptNumber('SH');

        $entries = [
            [
                'ledger_code' => self::COA_MEMBER_SAVINGS,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Transfer from savings to share capital",
                'member_id' => $memberId,
                'transaction_type' => 'SHARE_PURCHASE',
                'payment_method' => 'internal',
                'account_type' => 'savings'
            ],
            [
                'ledger_code' => self::COA_MEMBER_SHARES,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Member share capital increase",
                'member_id' => $memberId,
                'transaction_type' => 'SHARE_PURCHASE',
                'payment_method' => 'internal',
                'account_type' => 'shares'
            ]
        ];

        self::postJournalEntries($entries, $referenceNumber, "Share Purchase from Savings", $postedBy);
    }

    /**
     * Post share redemption when shares are sold back into savings
     * Debit: Member Savings (liability increase)
     * Credit: Member Shares (equity reduction)
     *
     * @param int $memberId
     * @param float $amount
     * @param int $postedBy
     * @param string|null $referenceNumber
     */
    public static function postShareRedemptionToSavings($memberId, $amount, $postedBy, $referenceNumber = null)
    {
        $referenceNumber = $referenceNumber ?: self::generateReceiptNumber('SR');

        $entries = [
            [
                'ledger_code' => self::COA_MEMBER_SAVINGS,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Share redemption deposited to savings",
                'member_id' => $memberId,
                'transaction_type' => 'SHARE_REDEMPTION',
                'account_type' => 'savings'
            ],
            [
                'ledger_code' => self::COA_MEMBER_SHARES,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Member share capital reduction",
                'member_id' => $memberId,
                'transaction_type' => 'SHARE_REDEMPTION',
                'account_type' => 'shares'
            ]
        ];

        self::postJournalEntries($entries, $referenceNumber, "Share Redemption to Savings", $postedBy);
    }

    /**
     * Post a share transfer between members
     * Debit: Member shares for source
     * Credit: Member shares for destination
     *
     * @param int $sourceMemberId
     * @param int $destinationMemberId
     * @param float $amount
     * @param string $referenceNumber
     * @param int $postedBy
     */
    public static function postShareTransfer($sourceMemberId, $destinationMemberId, $amount, $referenceNumber, $postedBy)
    {
        $entries = [
            [
                'ledger_code' => self::COA_MEMBER_SHARES,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Share transfer out to member {$destinationMemberId}",
                'member_id' => $sourceMemberId,
                'related_member_id' => $destinationMemberId,
                'transaction_type' => 'SHARE_TRANSFER_OUT',
                'account_type' => 'shares'
            ],
            [
                'ledger_code' => self::COA_MEMBER_SHARES,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Share transfer in from member {$sourceMemberId}",
                'member_id' => $destinationMemberId,
                'related_member_id' => $sourceMemberId,
                'transaction_type' => 'SHARE_TRANSFER_IN',
                'account_type' => 'shares'
            ]
        ];

        self::postJournalEntries($entries, $referenceNumber, "Share Transfer {$referenceNumber}", $postedBy);
    }

    /**
     * Post a manual share adjustment for corrections or audit updates.
     * Debit/Credit: Member Shares vs Retained Earnings as adjustment clearing.
     *
     * @param int $memberId
     * @param float $amount
     * @param int $postedBy
     * @param string $referenceNumber
     * @param string $adjustmentType
     */
    public static function postShareAdjustment($memberId, $amount, $postedBy, $referenceNumber, $adjustmentType = 'increase')
    {
        $entries = [];

        if ($adjustmentType === 'increase') {
            $entries[] = [
                'ledger_code' => self::COA_RETAINED_EARNINGS,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Manual share adjustment increase for member {$memberId}",
                'member_id' => $memberId,
                'transaction_type' => 'SHARE_ADJUSTMENT',
                'account_type' => 'adjustment'
            ];
            $entries[] = [
                'ledger_code' => self::COA_MEMBER_SHARES,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Manual share adjustment increase for member {$memberId}",
                'member_id' => $memberId,
                'transaction_type' => 'SHARE_ADJUSTMENT',
                'account_type' => 'shares'
            ];
        } else {
            $entries[] = [
                'ledger_code' => self::COA_MEMBER_SHARES,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Manual share adjustment decrease for member {$memberId}",
                'member_id' => $memberId,
                'transaction_type' => 'SHARE_ADJUSTMENT',
                'account_type' => 'shares'
            ];
            $entries[] = [
                'ledger_code' => self::COA_RETAINED_EARNINGS,
                'debit' => 0,
                'credit' => $amount,
                'description' => "Manual share adjustment decrease for member {$memberId}",
                'member_id' => $memberId,
                'transaction_type' => 'SHARE_ADJUSTMENT',
                'account_type' => 'adjustment'
            ];
        }

        self::postJournalEntries($entries, $referenceNumber, "Share Adjustment {$referenceNumber}", $postedBy);
    }

    /**
     * Post salary deduction batch
     * Multiple entries for each member's deduction allocation
     * 
     * @param int $batchId
     * @param array $deductions [
     *     'member_id' => 'total_deduction',
     *     'allocation' => ['interest' => x, 'principal' => x, 'savings' => x, 'shares' => x]
     * ]
     * @param int $postedBy
     */
    public static function postSalaryDeductionBatch($batchId, $deductions, $postedBy)
    {
        $receiptNumber = self::generateReceiptNumber('SD');
        $totalDeduction = 0;

        foreach ($deductions as $memberId => $data) {
            $totalDeduction += $data['total'];
        }

        if ($totalDeduction <= 0) return;

        $entries = [
            // Debit: Bank (salary in)
            [
                'ledger_code' => self::COA_BANK,
                'debit' => $totalDeduction,
                'credit' => 0,
                'description' => "Salary deductions batch #{$batchId}"
            ]
        ];

        // Credits: Allocated to various accounts
        foreach ($deductions as $memberId => $data) {
            if ($data['allocation']['interest'] > 0) {
                $entries[] = [
                    'ledger_code' => self::COA_INTEREST_INCOME,
                    'debit' => 0,
                    'credit' => $data['allocation']['interest'],
                    'description' => "Interest via salary deduction"
                ];
            }

            if ($data['allocation']['principal'] > 0) {
                $entries[] = [
                    'ledger_code' => self::COA_LOANS,
                    'debit' => 0,
                    'credit' => $data['allocation']['principal'],
                    'description' => "Loan principal via salary deduction"
                ];
            }

            if ($data['allocation']['savings'] > 0) {
                $entries[] = [
                    'ledger_code' => self::COA_MEMBER_SAVINGS,
                    'debit' => 0,
                    'credit' => $data['allocation']['savings'],
                    'description' => "Savings via salary deduction"
                ];
            }

            if ($data['allocation']['shares'] > 0) {
                $entries[] = [
                    'ledger_code' => self::COA_MEMBER_SHARES,
                    'debit' => 0,
                    'credit' => $data['allocation']['shares'],
                    'description' => "Share capital via salary deduction"
                ];
            }
        }

        self::postJournalEntries($entries, $receiptNumber, "Salary Deduction Batch", null, $postedBy);
    }

    /**
     * Post journal entries atomically (all or nothing)
     * Ensures debit = credit for balanced accounting
     * 
     * @param array $entries
     * @param string $receiptNumber
     * @param string $description
     * @param int $postedBy
     * @return bool
     */
    private static function postJournalEntries($entries, $receiptNumber, $description, $postedBy)
    {
        $db = self::getConnection();
        $ownTransaction = false;

        try {
            if (!$db->inTransaction()) {
                $db->beginTransaction();
                $ownTransaction = true;
            }

            $totalDebits = 0;
            $totalCredits = 0;

            foreach ($entries as $entry) {
                $totalDebits += $entry['debit'];
                $totalCredits += $entry['credit'];
            }

            if (abs($totalDebits - $totalCredits) > 0.01) {
                throw new Exception("Journal entries not balanced: Debit {$totalDebits} != Credit {$totalCredits}");
            }

            foreach ($entries as $entry) {
                $stmt = $db->prepare("
                    INSERT INTO ledger_entries
                    (ledger_code, ledger_name, entry_date, receipt_number, transaction_reference,
                     transaction_type, debit, credit, description, payment_method, posted_by, approved_by,
                     member_id, related_member_id, account_type, status, reversal_of_id)
                    VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $entry['ledger_code'],
                    self::getAccountName($entry['ledger_code']),
                    $receiptNumber,
                    $entry['transaction_reference'] ?? $receiptNumber,
                    $entry['transaction_type'] ?? 'general_entry',
                    $entry['debit'],
                    $entry['credit'],
                    $entry['description'],
                    $entry['payment_method'] ?? null,
                    $postedBy,
                    $entry['approved_by'] ?? null,
                    $entry['member_id'] ?? null,
                    $entry['related_member_id'] ?? null,
                    $entry['account_type'] ?? null,
                    $entry['status'] ?? 'posted',
                    $entry['reversal_of_id'] ?? null
                ]);
            }

            if ($ownTransaction) {
                $db->commit();
            }
            return true;
        } catch (Exception $e) {
            if ($ownTransaction && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Ledger posting failed: " . $e->getMessage());
            throw new Exception("Ledger posting failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Generate trial balance
     * @param string $asOfDate YYYY-MM-DD
     * @return array
     */
    public static function generateTrialBalance($asOfDate = null)
    {
        $db = self::getConnection();
        $asOfDate = $asOfDate ?: date('Y-m-d');

        $stmt = $db->prepare("
            SELECT 
                ledger_code,
                ledger_name,
                SUM(debit) as total_debit,
                SUM(credit) as total_credit,
                SUM(debit - credit) as balance
            FROM ledger_entries
            WHERE entry_date <= ? AND status = 'posted'
            GROUP BY ledger_code, ledger_name
            ORDER BY ledger_code
        ");

        $stmt->execute([$asOfDate]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Generate balance sheet
     * Assets = Liabilities + Equity
     * 
     * @param string $asOfDate YYYY-MM-DD
     * @return array
     */
    public static function generateBalanceSheet($asOfDate = null)
    {
        $asOfDate = $asOfDate ?: date('Y-m-d');
        $trialBalance = self::generateTrialBalance($asOfDate);

        $assets = [];
        $liabilities = [];
        $equity = [];

        // Categorize accounts
        $assetCodes = [self::COA_CASH, self::COA_BANK, self::COA_LOANS, self::COA_INTEREST_RECEIVABLE];
        $liabilityCodes = [self::COA_MEMBER_SAVINGS, self::COA_INTEREST_PAYABLE];
        $equityCodes = [self::COA_MEMBER_SHARES, self::COA_RETAINED_EARNINGS];

        foreach ($trialBalance as $entry) {
            if (in_array($entry['ledger_code'], $assetCodes)) {
                $assets[] = $entry;
            } elseif (in_array($entry['ledger_code'], $liabilityCodes)) {
                $liabilities[] = $entry;
            } elseif (in_array($entry['ledger_code'], $equityCodes)) {
                $equity[] = $entry;
            }
        }

        return [
            'date' => $asOfDate,
            'assets' => $assets,
            'liabilities' => $liabilities,
            'equity' => $equity,
            'total_assets' => array_sum(array_column($assets, 'balance')),
            'total_liabilities' => array_sum(array_column($liabilities, 'balance')),
            'total_equity' => array_sum(array_column($equity, 'balance'))
        ];
    }

    /**
     * Generate income statement
     * Income - Expenses = Net Income
     * 
     * @param string $periodStart YYYY-MM-DD
     * @param string $periodEnd YYYY-MM-DD
     * @return array
     */
    public static function generateIncomeStatement($periodStart, $periodEnd)
    {
        $db = self::getConnection();

        $stmt = $db->prepare("
            SELECT 
                'Income' as type,
                ledger_code,
                ledger_name,
                SUM(credit) as amount
            FROM ledger_entries
            WHERE entry_date BETWEEN ? AND ? 
                AND status = 'posted'
                AND ledger_code IN (?, ?, ?)
            GROUP BY ledger_code, ledger_name
            UNION ALL
            SELECT 
                'Expense' as type,
                ledger_code,
                ledger_name,
                SUM(debit) as amount
            FROM ledger_entries
            WHERE entry_date BETWEEN ? AND ? 
                AND status = 'posted'
                AND ledger_code IN (?, ?, ?)
            GROUP BY ledger_code, ledger_name
        ");

        $stmt->execute([
            $periodStart, $periodEnd, self::COA_INTEREST_INCOME, self::COA_PROCESSING_FEES, self::COA_PENALTY_INCOME,
            $periodStart, $periodEnd, self::COA_LOAN_LOSS_PROVISION, self::COA_STAFF_COSTS, self::COA_ADMIN_EXPENSES
        ]);

        $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $income = array_sum(array_map(fn($x) => $x['type'] === 'Income' ? $x['amount'] : 0, $results));
        $expenses = array_sum(array_map(fn($x) => $x['type'] === 'Expense' ? $x['amount'] : 0, $results));

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'income_items' => array_filter($results, fn($x) => $x['type'] === 'Income'),
            'expense_items' => array_filter($results, fn($x) => $x['type'] === 'Expense'),
            'total_income' => $income,
            'total_expenses' => $expenses,
            'net_income' => $income - $expenses
        ];
    }

    /**
     * Get account name from chart of accounts
     */
    private static function getAccountName($code)
    {
        $db = self::getConnection();

        if (self::tableExists($db, 'chart_of_accounts')) {
            try {
                $stmt = $db->prepare('SELECT account_name FROM chart_of_accounts WHERE account_code = ? LIMIT 1');
                $stmt->execute([$code]);
                $name = $stmt->fetchColumn();
                if ($name) {
                    return $name;
                }
            } catch (Exception $e) {
                // Fall back to static mapping if the table is unavailable or query fails
            }
        }

        $accounts = [
            self::COA_CASH => 'Cash in Hand',
            self::COA_BANK => 'Bank Account',
            self::COA_LOANS => 'Member Loans',
            self::COA_INTEREST_RECEIVABLE => 'Interest Receivable',
            self::COA_MEMBER_SHARES => 'Member Share Capital',
            self::COA_MEMBER_SAVINGS => 'Member Savings',
            self::COA_INTEREST_PAYABLE => 'Interest Payable',
            self::COA_RETAINED_EARNINGS => 'Retained Earnings',
            self::COA_INTEREST_INCOME => 'Interest Income',
            self::COA_PROCESSING_FEES => 'Processing Fees Income',
            self::COA_PENALTY_INCOME => 'Penalty Income',
            self::COA_LOAN_LOSS_PROVISION => 'Loan Loss Provision',
            self::COA_STAFF_COSTS => 'Staff Costs',
            self::COA_ADMIN_EXPENSES => 'Administrative Expenses'
        ];

        return $accounts[$code] ?? 'Unknown Account';
    }

    /**
     * Generate receipt number
     */
    private static function generateReceiptNumber($prefix)
    {
        return $prefix . date('YmdHis') . rand(100, 999);
    }

    /**
     * Reverse/Cancel a ledger entry
     * Creates offsetting entries (debit becomes credit, vice versa)
     * 
     * @param int $entryId
     * @param string $reason
     * @param int $reversedBy
     */
    public static function reverseEntry($entryId, $reason, $reversedBy)
    {
        global $db;

        try {
            $db->beginTransaction();

            $stmt = $db->prepare("SELECT * FROM ledger_entries WHERE entry_id = ?");
            $stmt->execute([$entryId]);
            $entry = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$entry) {
                throw new Exception("Entry not found");
            }

            if ($entry['status'] === 'reversed') {
                throw new Exception("Entry already reversed");
            }

            // Mark original as reversed
            $stmt = $db->prepare("UPDATE ledger_entries SET status = 'reversed' WHERE entry_id = ?");
            $stmt->execute([$entryId]);

            // Create reversal entries (swap debit/credit)
            $stmt = $db->prepare("
                INSERT INTO ledger_entries
                (ledger_code, ledger_name, entry_date, receipt_number, transaction_reference,
                 debit, credit, description, posted_by, member_id, status, reversal_of_id)
                VALUES (?, ?, NOW(), ?, ?, ?, ?, ?, ?, ?, 'posted', ?)
            ");

            $stmt->execute([
                $entry['ledger_code'],
                $entry['ledger_name'],
                'REV' . date('YmdHis'),
                "Reversal: {$reason}",
                $entry['credit'],  // Swap
                $entry['debit'],   // Swap
                "Reversal of entry #{$entryId}: {$reason}",
                $reversedBy,
                $entry['member_id'],
                $entryId
            ]);

            $db->commit();
            return true;
        } catch (Exception $e) {
            $db->rollBack();
            error_log("Entry reversal failed: " . $e->getMessage());
            return false;
        }
    }
}

?>
