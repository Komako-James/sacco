<?php
// Dry-run backfill script (read-only) for savings unmatched transactions
// - Uses existing DB connection
// - Does NOT INSERT or UPDATE anything
// - Prints actions that would be taken (LedgerService call simulation)

require_once __DIR__ . '/../config/db_connection.php';
require_once __DIR__ . '/../app/Services/LedgerService.php';

$db = getDB();
$ledgerService = new \App\Services\LedgerService($db);

echo "[DRY RUN] Backfill simulation for unmatched savings transactions\n";
echo "Generated at: " . date('c') . "\n\n";

// Query unmatched savings similar to the analysis script
$sql = "SELECT st.trans_id AS transaction_id, st.account_id AS member_id, st.transaction_date, st.transaction_type, st.amount, st.receipt_no, st.reference_no, st.payment_method
    FROM savings_transactions st
    WHERE NOT EXISTS (
        SELECT 1 FROM ledger_entries le WHERE (
            (le.transaction_reference IS NOT NULL AND le.transaction_reference != '' AND (le.transaction_reference = st.receipt_no OR le.transaction_reference = st.reference_no))
            OR (le.receipt_number IS NOT NULL AND le.receipt_number != '' AND (le.receipt_number = st.receipt_no OR le.receipt_number = st.reference_no))
        )
    )
    ORDER BY st.transaction_date ASC";

$stmt = $db->prepare($sql);
$stmt->execute();
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

$grand_debit = [];
$grand_credit = [];
$total_amount = 0.0;
$count = 0;

foreach ($rows as $r) {
    $count++;
    $txid = $r['transaction_id'];
    $ref = !empty($r['receipt_no']) ? $r['receipt_no'] : $r['reference_no'];
    $amt = (float)$r['amount'];
    $type = $r['transaction_type'];

    // Determine accounts (same logic as simulated_journals)
    switch ($type) {
        case 'deposit':
            $debit = ($r['payment_method'] === 'bank_transfer') ? LedgerService::COA_BANK : LedgerService::COA_CASH;
            $credit = LedgerService::COA_MEMBER_SAVINGS;
            break;
        case 'withdrawal':
            $debit = LedgerService::COA_MEMBER_SAVINGS;
            $credit = ($r['payment_method'] === 'bank_transfer') ? LedgerService::COA_BANK : LedgerService::COA_CASH;
            break;
        case 'transfer_out':
            $debit = LedgerService::COA_MEMBER_SAVINGS;
            $credit = LedgerService::COA_MEMBER_SHARES;
            break;
        default:
            $debit = LedgerService::COA_CASH;
            $credit = LedgerService::COA_MEMBER_SAVINGS;
    }

    $grand_debit[$debit] = ($grand_debit[$debit] ?? 0) + $amt;
    $grand_credit[$credit] = ($grand_credit[$credit] ?? 0) + $amt;
    $total_amount += $amt;

    echo "[DRY RUN]\n";
    echo "Transaction ID: {$txid}\n";
    echo "Reference: {$ref}\n";
    echo "Date: {$r['transaction_date']}\n";
    echo "Amount: " . number_format($amt,2) . "\n";
    echo "Debit: {$debit} ({$ledgerService->getAccountName($debit)})\n";
    echo "Credit: {$credit} ({$ledgerService->getAccountName($credit)})\n";
    echo "Would call: LedgerService::postJournalEntries( [debit => {$debit}, credit => {$credit}], amount => {$amt}, transaction_reference => '{$ref}', source_table => 'savings_transactions', source_id => {$txid} )\n";
    echo "Status: DRY_RUN_ONLY\n\n";
}

// Grand totals
echo "---- SUMMARY ----\n";
echo "Transactions simulated: {$count}\n";
echo "Total amount simulated: " . number_format($total_amount,2) . "\n\n";

echo "Per-account Debit Totals:\n";
foreach ($grand_debit as $acct => $amt) {
    echo "  {$acct} - " . number_format($amt,2) . "\n";
}

echo "Per-account Credit Totals:\n";
foreach ($grand_credit as $acct => $amt) {
    echo "  {$acct} - " . number_format($amt,2) . "\n";
}

// Verify debits == credits
$sum_debits = array_sum($grand_debit);
$sum_credits = array_sum($grand_credit);

echo "\nVerification: Sum debits = " . number_format($sum_debits,2) . ", Sum credits = " . number_format($sum_credits,2) . "\n";
if (abs($sum_debits - $sum_credits) < 0.01) {
    echo "Result: BALANCED (dry-run)\n";
} else {
    echo "Result: UNBALANCED - investigate before applying\n";
}

echo "\nNote: This script performs NO writes. To perform production backfill, review scripts/backfill_savings_gl.php specification.\n";

// End of dry-run script
