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
    const COA_MEMBER_SHARES = '3010';
    const COA_MEMBER_SAVINGS = '2010';
    const COA_INTEREST_PAYABLE = '2030';
    const COA_RETAINED_EARNINGS = '3020';
    // Capital reserve must not collide with retained earnings
    const COA_CAPITAL_RESERVE = '3030';
    const COA_SAVINGS_INTEREST_EXPENSE = '5040';
    const COA_INTEREST_INCOME = '4010';
    const COA_PROCESSING_FEES = '4020';
    const COA_PENALTY_INCOME = '4030';
    const COA_OTHER_INCOME = '4040';
    const COA_LOAN_LOSS_PROVISION = '5010';
    const COA_STAFF_COSTS = '5020';
    const COA_ADMIN_EXPENSES = '5030';

    const ACCOUNT_TYPE_ASSET = 'asset';
    const ACCOUNT_TYPE_LIABILITY = 'liability';
    const ACCOUNT_TYPE_EQUITY = 'equity';
    const ACCOUNT_TYPE_INCOME = 'income';
    const ACCOUNT_TYPE_EXPENSE = 'expense';

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

    private static function validateAccount(string $accountCode, string $expectedType = null): array
    {
        $db = self::getConnection();
        if (!self::tableExists($db, 'chart_of_accounts')) {
            throw new Exception('Chart of accounts table is unavailable');
        }

        $stmt = $db->prepare(
            'SELECT account_code, account_name, account_type, is_active FROM chart_of_accounts WHERE account_code = ? LIMIT 1'
        );
        $stmt->execute([$accountCode]);
        $account = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$account) {
            throw new Exception("Account {$accountCode} not found in chart of accounts");
        }

        if ((int) $account['is_active'] !== 1) {
            throw new Exception("Account {$accountCode} is inactive");
        }

        if ($expectedType !== null && $account['account_type'] !== $expectedType) {
            throw new Exception("Account {$accountCode} is not a {$expectedType} account");
        }

        return $account;
    }

    private static function getAssetAccountForPaymentMethod(string $paymentMethod): string
    {
        switch (strtolower($paymentMethod)) {
            case 'cash':
                return self::COA_CASH;
            case 'bank':
                return self::COA_BANK;
            default:
                throw new Exception('Unsupported payment method for asset account');
        }
    }

    public static function postCashRevenueEvent(
        float $amount,
        string $incomeAccountCode,
        string $paymentMethod,
        string $description,
        int $postedBy,
        string $transactionType = 'revenue',
        ?string $referenceNumber = null
    ) {
        if ($amount <= 0) {
            throw new Exception('Revenue amount must be greater than zero');
        }

        $incomeAccount = self::validateAccount($incomeAccountCode, self::ACCOUNT_TYPE_INCOME);
        $assetAccountCode = self::getAssetAccountForPaymentMethod($paymentMethod);
        self::validateAccount($assetAccountCode, self::ACCOUNT_TYPE_ASSET);

        $receiptNumber = $referenceNumber ?: self::generateReceiptNumber('REV');

        $entries = [
            [
                'ledger_code' => $assetAccountCode,
                'debit' => $amount,
                'credit' => 0,
                'description' => $description,
                'payment_method' => $paymentMethod,
                'transaction_type' => $transactionType
            ],
            [
                'ledger_code' => $incomeAccount['account_code'],
                'debit' => 0,
                'credit' => $amount,
                'description' => $description,
                'transaction_type' => $transactionType
            ]
        ];

        self::postJournalEntries($entries, $receiptNumber, $description, $postedBy);
    }

    public static function postOperatingExpense(
        float $amount,
        string $expenseAccountCode,
        string $paymentMethod,
        string $description,
        int $postedBy,
        string $transactionType = 'expense',
        ?string $referenceNumber = null
    ) {
        if ($amount <= 0) {
            throw new Exception('Expense amount must be greater than zero');
        }

        $expenseAccount = self::validateAccount($expenseAccountCode, self::ACCOUNT_TYPE_EXPENSE);
        $assetAccountCode = self::getAssetAccountForPaymentMethod($paymentMethod);
        self::validateAccount($assetAccountCode, self::ACCOUNT_TYPE_ASSET);

        $receiptNumber = $referenceNumber ?: self::generateReceiptNumber('EXP');

        $entries = [
            [
                'ledger_code' => $expenseAccount['account_code'],
                'debit' => $amount,
                'credit' => 0,
                'description' => $description,
                'transaction_type' => $transactionType
            ],
            [
                'ledger_code' => $assetAccountCode,
                'debit' => 0,
                'credit' => $amount,
                'description' => $description,
                'payment_method' => $paymentMethod,
                'transaction_type' => $transactionType
            ]
        ];

        self::postJournalEntries($entries, $receiptNumber, $description, $postedBy);
    }

    public static function postAccruedRevenueEvent(
        float $amount,
        string $receivableAccountCode,
        string $incomeAccountCode,
        string $description,
        int $postedBy,
        string $transactionType = 'accrual',
        ?string $referenceNumber = null
    ) {
        if ($amount <= 0) {
            throw new Exception('Accrued revenue amount must be greater than zero');
        }

        $receivableAccount = self::validateAccount($receivableAccountCode, self::ACCOUNT_TYPE_ASSET);
        $incomeAccount = self::validateAccount($incomeAccountCode, self::ACCOUNT_TYPE_INCOME);

        $receiptNumber = $referenceNumber ?: self::generateReceiptNumber('ACR');

        $entries = [
            [
                'ledger_code' => $receivableAccount['account_code'],
                'debit' => $amount,
                'credit' => 0,
                'description' => $description,
                'transaction_type' => $transactionType
            ],
            [
                'ledger_code' => $incomeAccount['account_code'],
                'debit' => 0,
                'credit' => $amount,
                'description' => $description,
                'transaction_type' => $transactionType
            ]
        ];

        self::postJournalEntries($entries, $receiptNumber, $description, $postedBy);
    }

    public static function postReceivableSettlement(
        float $amount,
        string $settlementAccountCode,
        string $receivableAccountCode,
        string $description,
        int $postedBy,
        string $transactionType = 'settlement',
        ?string $referenceNumber = null
    ) {
        if ($amount <= 0) {
            throw new Exception('Settlement amount must be greater than zero');
        }

        $settlementAccount = self::validateAccount($settlementAccountCode, self::ACCOUNT_TYPE_ASSET);
        $receivableAccount = self::validateAccount($receivableAccountCode, self::ACCOUNT_TYPE_ASSET);

        $receiptNumber = $referenceNumber ?: self::generateReceiptNumber('RST');

        $entries = [
            [
                'ledger_code' => $settlementAccount['account_code'],
                'debit' => $amount,
                'credit' => 0,
                'description' => $description,
                'transaction_type' => $transactionType
            ],
            [
                'ledger_code' => $receivableAccount['account_code'],
                'debit' => 0,
                'credit' => $amount,
                'description' => $description,
                'transaction_type' => $transactionType
            ]
        ];

        self::postJournalEntries($entries, $receiptNumber, $description, $postedBy);
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
        // Validate inputs to avoid creating zero-valued journal lines
        $principalAmount = isset($principalAmount) ? (float)$principalAmount : 0.0;
        $processingFee = isset($processingFee) ? (float)$processingFee : 0.0;
        if ($principalAmount <= 0) {
            throw new Exception('Invalid principal amount for disbursement');
        }

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
        ];

        // Only include processing fee lines when there is a non-zero fee
        if ($processingFee > 0) {
            $entries[] = [
                'ledger_code' => self::COA_BANK,
                'debit' => $processingFee,
                'credit' => 0,
                'description' => "Processing fee collected"
            ];
            $entries[] = [
                'ledger_code' => self::COA_PROCESSING_FEES,
                'debit' => 0,
                'credit' => $processingFee,
                'description' => "Processing fee income"
            ];
        }
        

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

        // Credit: Interest - prefer clearing accrued receivable first to avoid double recognition
        if ($allocation['interest_paid'] > 0) {
            $interestToAllocate = $allocation['interest_paid'];

            // Check current Interest Receivable balance (debit - credit)
            $db = self::getConnection();
            $stmt = $db->prepare('SELECT COALESCE(SUM(debit - credit), 0) AS balance FROM ledger_entries WHERE ledger_code = ? AND status = "posted"');
            $stmt->execute([self::COA_INTEREST_RECEIVABLE]);
            $receivableBalance = (float) $stmt->fetchColumn();

            if ($receivableBalance > 0) {
                $appliedToReceivable = min($interestToAllocate, $receivableBalance);
                $entries[] = [
                    'ledger_code' => self::COA_INTEREST_RECEIVABLE,
                    'debit' => 0,
                    'credit' => $appliedToReceivable,
                    'description' => "Interest received - settle accrued receivable"
                ];

                $interestToAllocate -= $appliedToReceivable;
            }

            // Any remaining interest not previously accrued should be recognized as income
            if ($interestToAllocate > 0) {
                $entries[] = [
                    'ledger_code' => self::COA_INTEREST_INCOME,
                    'debit' => 0,
                    'credit' => $interestToAllocate,
                    'description' => "Interest received (not previously accrued)"
                ];
            }
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

        // Use accrued revenue posting helper to ensure accounts are validated and consistent
        $description = "Monthly interest accrued for {$month}";
        // Post as accrued revenue: Debit Interest Receivable, Credit Interest Income
        self::postAccruedRevenueEvent($totalInterest, self::COA_INTEREST_RECEIVABLE, self::COA_INTEREST_INCOME, $description, $postedBy, 'interest_accrual');
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

        // Ensure we explicitly use the Member Savings liability account for
        // savings->shares transfers (prevents accidental use of Member Deposits 2020).
        $savingsAccount = self::validateAccount(self::COA_MEMBER_SAVINGS, self::ACCOUNT_TYPE_LIABILITY);
        $savingsLedgerCode = $savingsAccount['account_code'];

        $sharesAccount = self::validateAccount(self::COA_MEMBER_SHARES, self::ACCOUNT_TYPE_EQUITY);
        $sharesLedgerCode = $sharesAccount['account_code'];

        $entries = [
            [
                'ledger_code' => $savingsLedgerCode,
                'debit' => $amount,
                'credit' => 0,
                'description' => "Transfer from savings to share capital",
                'member_id' => $memberId,
                'transaction_type' => 'SHARE_PURCHASE',
                'payment_method' => 'internal',
                'account_type' => 'savings'
            ],
            [
                'ledger_code' => $sharesLedgerCode,
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
     * Post aggregated savings interest to ledger
     * Debit: Savings Interest Expense (Expense)
     * Credit: Member Savings (Liability)
     */
    public static function postSavingsInterest(float $amount, string $month, int $postedBy)
    {
        if ($amount <= 0) return;

        // Validate accounts
        $expenseAccount = self::validateAccount(self::COA_SAVINGS_INTEREST_EXPENSE, self::ACCOUNT_TYPE_EXPENSE);
        $memberSavings = self::validateAccount(self::COA_MEMBER_SAVINGS, self::ACCOUNT_TYPE_LIABILITY);

        $reference = self::generateReceiptNumber('SINT');

        $entries = [
            [
                'ledger_code' => $expenseAccount['account_code'],
                'debit' => $amount,
                'credit' => 0,
                'description' => "Savings interest expense for {$month}",
                'transaction_type' => 'savings_interest'
            ],
            [
                'ledger_code' => $memberSavings['account_code'],
                'debit' => 0,
                'credit' => $amount,
                'description' => "Savings interest credited to members for {$month}",
                'transaction_type' => 'savings_interest'
            ]
        ];

        self::postJournalEntries($entries, $reference, "Savings Interest Posting", $postedBy);
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

            foreach ($entries as $idx => $entry) {
                // Normalize missing values
                $debit = isset($entry['debit']) ? (float)$entry['debit'] : 0.0;
                $credit = isset($entry['credit']) ? (float)$entry['credit'] : 0.0;

                // Reject negative amounts
                if ($debit < 0 || $credit < 0) {
                    throw new Exception('Journal entry contains negative debit or credit');
                }

                // Require exactly one side to be > 0
                if (($debit > 0 && $credit > 0) || ($debit == 0 && $credit == 0)) {
                    throw new Exception('Each journal line must have exactly one of debit or credit greater than zero');
                }

                $totalDebits += $debit;
                $totalCredits += $credit;

                // Write normalized values back to entries array so insertion uses correct numbers
                $entries[$idx]['debit'] = $debit;
                $entries[$idx]['credit'] = $credit;
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

        if (self::tableExists($db, 'chart_of_accounts')) {
            $stmt = $db->prepare("
                SELECT
                    le.ledger_code,
                    COALESCE(coa.account_name, le.ledger_name) AS ledger_name,
                    coa.account_type,
                    coa.account_category,
                    SUM(le.debit) AS total_debit,
                    SUM(le.credit) AS total_credit,
                    SUM(le.debit - le.credit) AS balance
                FROM ledger_entries le
                LEFT JOIN chart_of_accounts coa ON le.ledger_code = coa.account_code
                WHERE le.entry_date <= ? AND le.status = 'posted'
                GROUP BY le.ledger_code, COALESCE(coa.account_name, le.ledger_name), coa.account_type, coa.account_category
                ORDER BY le.ledger_code
            ");

            $stmt->execute([$asOfDate]);
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

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
        $db = self::getConnection();
        $asOfDate = $asOfDate ?: date('Y-m-d');

        $assets = [];
        $liabilities = [];
        $equity = [];

        if (!self::tableExists($db, 'chart_of_accounts')) {
            return [
                'date' => $asOfDate,
                'assets' => [],
                'liabilities' => [],
                'equity' => [],
                'total_assets' => 0,
                'total_liabilities' => 0,
                'total_equity' => 0
            ];
        }

        $stmt = $db->prepare("
            SELECT
                coa.account_code,
                coa.account_name,
                coa.account_type,
                SUM(le.debit) AS total_debit,
                SUM(le.credit) AS total_credit,
                SUM(le.debit - le.credit) AS balance
            FROM ledger_entries le
            JOIN chart_of_accounts coa ON le.ledger_code = coa.account_code
            WHERE le.entry_date <= ? AND le.status = 'posted'
            GROUP BY coa.account_code, coa.account_name, coa.account_type
            ORDER BY coa.account_code
        ");

        $stmt->execute([$asOfDate]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as $entry) {
            switch ($entry['account_type']) {
                case 'asset':
                    $assets[] = $entry;
                    break;
                case 'liability':
                    $liabilities[] = $entry;
                    break;
                case 'equity':
                    $equity[] = $entry;
                    break;
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

        if (!self::tableExists($db, 'chart_of_accounts')) {
            return [
                'period_start' => $periodStart,
                'period_end' => $periodEnd,
                'income_items' => [],
                'expense_items' => [],
                'total_income' => 0,
                'total_expenses' => 0,
                'net_income' => 0
            ];
        }

        $stmt = $db->prepare("
            SELECT
                coa.account_code,
                coa.account_name,
                coa.account_type,
                SUM(le.debit) AS total_debit,
                SUM(le.credit) AS total_credit
            FROM ledger_entries le
            JOIN chart_of_accounts coa ON le.ledger_code = coa.account_code
            WHERE le.entry_date BETWEEN ? AND ?
                AND le.status = 'posted'
                AND coa.account_type IN ('income', 'expense')
            GROUP BY coa.account_code, coa.account_name, coa.account_type
            ORDER BY coa.account_type, coa.account_code
        ");

        $stmt->execute([$periodStart, $periodEnd]);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $incomeItems = [];
        $expenseItems = [];

        foreach ($rows as $entry) {
            if ($entry['account_type'] === 'income') {
                $incomeItems[] = [
                    'type' => 'Income',
                    'ledger_code' => $entry['account_code'],
                    'ledger_name' => $entry['account_name'],
                    'amount' => (float) $entry['total_credit']
                ];
            } elseif ($entry['account_type'] === 'expense') {
                $expenseItems[] = [
                    'type' => 'Expense',
                    'ledger_code' => $entry['account_code'],
                    'ledger_name' => $entry['account_name'],
                    'amount' => (float) $entry['total_debit']
                ];
            }
        }

        $totalIncome = array_sum(array_column($incomeItems, 'amount'));
        $totalExpenses = array_sum(array_column($expenseItems, 'amount'));

        return [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'income_items' => $incomeItems,
            'expense_items' => $expenseItems,
            'total_income' => $totalIncome,
            'total_expenses' => $totalExpenses,
            'net_income' => $totalIncome - $totalExpenses
        ];
    }

    /**
     * Get revenue by income account source
     */
    public static function getRevenueBySource($startDate = null, $endDate = null)
    {
        $db = self::getConnection();
        if (!self::tableExists($db, 'ledger_entries') || !self::tableExists($db, 'chart_of_accounts')) {
            return [];
        }

        $sql = "
            SELECT
                coa.account_code,
                coa.account_name,
                COALESCE(SUM(le.credit), 0) AS amount
            FROM ledger_entries le
            JOIN chart_of_accounts coa ON le.ledger_code = coa.account_code
            WHERE le.status = 'posted'
              AND coa.account_type = 'income'
        ";
        $params = [];

        if ($startDate) {
            $sql .= ' AND le.entry_date >= ?';
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND le.entry_date <= ?';
            $params[] = $endDate;
        }

        $sql .= ' GROUP BY coa.account_code, coa.account_name ORDER BY amount DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get monthly revenue trend
     */
    public static function getMonthlyRevenueTrend($startDate = null, $endDate = null)
    {
        $db = self::getConnection();
        if (!self::tableExists($db, 'ledger_entries') || !self::tableExists($db, 'chart_of_accounts')) {
            return [];
        }

        $sql = "
            SELECT
                DATE_FORMAT(le.entry_date, '%Y-%m') AS month,
                DATE_FORMAT(le.entry_date, '%b %Y') AS month_label,
                COALESCE(SUM(le.credit), 0) AS revenue
            FROM ledger_entries le
            JOIN chart_of_accounts coa ON le.ledger_code = coa.account_code
            WHERE le.status = 'posted'
              AND coa.account_type = 'income'
        ";
        $params = [];

        if ($startDate) {
            $sql .= ' AND le.entry_date >= ?';
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND le.entry_date <= ?';
            $params[] = $endDate;
        }

        $sql .= ' GROUP BY month, month_label ORDER BY month';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get expense breakdown by account
     */
    public static function getExpenseBreakdown($startDate = null, $endDate = null)
    {
        $db = self::getConnection();
        if (!self::tableExists($db, 'ledger_entries') || !self::tableExists($db, 'chart_of_accounts')) {
            return [];
        }

        $sql = "
            SELECT
                coa.account_code,
                coa.account_name,
                COALESCE(SUM(le.debit), 0) AS amount
            FROM ledger_entries le
            JOIN chart_of_accounts coa ON le.ledger_code = coa.account_code
            WHERE le.status = 'posted'
              AND coa.account_type = 'expense'
        ";
        $params = [];

        if ($startDate) {
            $sql .= ' AND le.entry_date >= ?';
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND le.entry_date <= ?';
            $params[] = $endDate;
        }

        $sql .= ' GROUP BY coa.account_code, coa.account_name ORDER BY amount DESC';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get monthly expense trend
     */
    public static function getMonthlyExpenseTrend($startDate = null, $endDate = null)
    {
        $db = self::getConnection();
        if (!self::tableExists($db, 'ledger_entries') || !self::tableExists($db, 'chart_of_accounts')) {
            return [];
        }

        $sql = "
            SELECT
                DATE_FORMAT(le.entry_date, '%Y-%m') AS month,
                DATE_FORMAT(le.entry_date, '%b %Y') AS month_label,
                COALESCE(SUM(le.debit), 0) AS expenses
            FROM ledger_entries le
            JOIN chart_of_accounts coa ON le.ledger_code = coa.account_code
            WHERE le.status = 'posted'
              AND coa.account_type = 'expense'
        ";
        $params = [];

        if ($startDate) {
            $sql .= ' AND le.entry_date >= ?';
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND le.entry_date <= ?';
            $params[] = $endDate;
        }

        $sql .= ' GROUP BY month, month_label ORDER BY month';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Get profit and loss summary for a period
     */
    public static function getProfitAndLoss($startDate = null, $endDate = null)
    {
        $db = self::getConnection();
        if (!self::tableExists($db, 'ledger_entries') || !self::tableExists($db, 'chart_of_accounts')) {
            return [
                'total_revenue' => 0,
                'total_expenses' => 0,
                'net_profit' => 0
            ];
        }

        $sql = "
            SELECT
                SUM(CASE WHEN coa.account_type = 'income' THEN le.credit ELSE 0 END) AS total_revenue,
                SUM(CASE WHEN coa.account_type = 'expense' THEN le.debit ELSE 0 END) AS total_expenses
            FROM ledger_entries le
            JOIN chart_of_accounts coa ON le.ledger_code = coa.account_code
            WHERE le.status = 'posted'
              AND coa.account_type IN ('income', 'expense')
        ";
        $params = [];

        if ($startDate) {
            $sql .= ' AND le.entry_date >= ?';
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND le.entry_date <= ?';
            $params[] = $endDate;
        }

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        $totalRevenue = (float) ($result['total_revenue'] ?? 0);
        $totalExpenses = (float) ($result['total_expenses'] ?? 0);

        return [
            'total_revenue' => $totalRevenue,
            'total_expenses' => $totalExpenses,
            'net_profit' => $totalRevenue - $totalExpenses
        ];
    }

    /**
     * Get profit and loss summary for a period
     */
    public static function getProfitabilitySummary($startDate = null, $endDate = null)
    {
        $pl = self::getProfitAndLoss($startDate, $endDate);

        return [
            'total_revenue' => $pl['total_revenue'],
            'total_expenses' => $pl['total_expenses'],
            'net_profit' => $pl['net_profit']
        ];
    }

    /**
     * Get monthly profit trend for a date range
     */
    public static function getMonthlyProfitTrend($startDate = null, $endDate = null)
    {
        $db = self::getConnection();
        if (!self::tableExists($db, 'ledger_entries') || !self::tableExists($db, 'chart_of_accounts')) {
            return [];
        }

        $sql = "
            SELECT
                DATE_FORMAT(le.entry_date, '%Y-%m') AS month,
                DATE_FORMAT(le.entry_date, '%b %Y') AS month_label,
                SUM(CASE WHEN coa.account_type = 'income' THEN le.credit ELSE 0 END) AS revenue,
                SUM(CASE WHEN coa.account_type = 'expense' THEN le.debit ELSE 0 END) AS expenses
            FROM ledger_entries le
            JOIN chart_of_accounts coa ON le.ledger_code = coa.account_code
            WHERE le.status = 'posted'
              AND coa.account_type IN ('income', 'expense')
        ";
        $params = [];

        if ($startDate) {
            $sql .= ' AND le.entry_date >= ?';
            $params[] = $startDate;
        }

        if ($endDate) {
            $sql .= ' AND le.entry_date <= ?';
            $params[] = $endDate;
        }

        $sql .= ' GROUP BY month, month_label ORDER BY month';

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($rows as &$row) {
            $row['revenue'] = (float) $row['revenue'];
            $row['expenses'] = (float) $row['expenses'];
            $row['profit'] = $row['revenue'] - $row['expenses'];
        }

        return $rows;
    }

    /**
     * Get loan portfolio summary metrics.
     */
    public static function getLoanPortfolioSummary()
    {
        $db = self::getConnection();
        if (!self::tableExists($db, 'loans')) {
            return [
                'total_loans_issued' => 0,
                'outstanding_principal' => 0,
                'interest_accrued' => 0,
                'interest_collected' => 0,
                'active_loans' => 0
            ];
        }

        $stmt = $db->query(
            "SELECT
                COALESCE(SUM(CASE WHEN amount_approved IS NOT NULL THEN amount_approved ELSE amount_requested END), 0) AS total_loans_issued,
                COALESCE(SUM(outstanding_balance), 0) AS outstanding_principal,
                COALESCE(SUM(interest_accrued), 0) AS interest_accrued,
                SUM(CASE WHEN status = 'disbursed' THEN 1 ELSE 0 END) AS active_loans
            FROM loans"
        );
        $loanSummary = $stmt->fetch(PDO::FETCH_ASSOC);

        $interestCollected = 0;
        if (self::tableExists($db, 'loan_repayments')) {
            $stmt = $db->query('SELECT COALESCE(SUM(interest_paid), 0) AS interest_collected FROM loan_repayments');
            $interestData = $stmt->fetch(PDO::FETCH_ASSOC);
            $interestCollected = (float) ($interestData['interest_collected'] ?? 0);
        }

        return [
            'total_loans_issued' => (float) ($loanSummary['total_loans_issued'] ?? 0),
            'outstanding_principal' => (float) ($loanSummary['outstanding_principal'] ?? 0),
            'interest_accrued' => (float) ($loanSummary['interest_accrued'] ?? 0),
            'interest_collected' => $interestCollected,
            'active_loans' => (int) ($loanSummary['active_loans'] ?? 0)
        ];
    }

    /**
     * Get revenue contribution by loan product type.
     */
    public static function getIncomeContributionByProduct()
    {
        $db = self::getConnection();

        if (!self::tableExists($db, 'loan_products') || !self::tableExists($db, 'loan_repayments') || !self::tableExists($db, 'loans')) {
            return [
                'available' => false,
                'message' => 'Loan product revenue contribution cannot be calculated because loan_products or loan_repayments tables are unavailable.',
                'data' => []
            ];
        }

        $stmt = $db->prepare(
            'SELECT
                lp.product_name,
                COALESCE(SUM(lr.interest_paid), 0) AS revenue
            FROM loan_repayments lr
            JOIN loans l ON lr.loan_id = l.loan_id
            JOIN loan_products lp ON l.product_id = lp.product_id
            WHERE lr.status = ?
            GROUP BY lp.product_id, lp.product_name
            ORDER BY revenue DESC'
        );
        $stmt->execute(['posted']);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'available' => true,
            'message' => null,
            'data' => $rows
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
            self::COA_CAPITAL_RESERVE => 'Capital Reserve',
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
    public static function generateReceiptNumber($prefix)
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
