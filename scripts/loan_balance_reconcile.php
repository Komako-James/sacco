<?php
require_once __DIR__ . '/../config/db_connection.php';
$db = Database::getInstance()->getConnection();
$loanId = isset($argv[1]) ? (int)$argv[1] : null;
if (!$loanId) {
    echo "Usage: php loan_balance_reconcile.php <loan_id>\n";
    exit(1);
}

$loan = $db->prepare('SELECT loan_id, loan_ref_no, amount_approved, outstanding_balance, total_paid, status FROM loans WHERE loan_id = ?');
$loan->execute([$loanId]);
$loanRow = $loan->fetch(PDO::FETCH_ASSOC);

$rep = $db->prepare('SELECT COALESCE(SUM(amount_paid),0) AS sum_amount_paid, COALESCE(SUM(principal_paid),0) AS sum_principal_paid, COALESCE(SUM(interest_paid),0) AS sum_interest_paid, COALESCE(SUM(penalty_paid),0) AS sum_penalty_paid FROM loan_repayments WHERE loan_id = ?');
$rep->execute([$loanId]);
$repRow = $rep->fetch(PDO::FETCH_ASSOC);

$sch = $db->prepare('SELECT COALESCE(SUM(total_due),0) AS sum_total_due, COALESCE(SUM(paid_amount),0) AS sum_paid_amount, COUNT(*) AS total_installments, SUM(CASE WHEN status != "paid" THEN 1 ELSE 0 END) AS unpaid_installments FROM loan_repayment_schedule WHERE loan_id = ?');
$sch->execute([$loanId]);
$schRow = $sch->fetch(PDO::FETCH_ASSOC);

$expectedOutstanding = round((float)$loanRow['amount_approved'] - (float)$repRow['sum_principal_paid'], 2);
$expectedTotalPaid = round((float)$repRow['sum_amount_paid'], 2);
$expectedScheduleBalance = round((float)$schRow['sum_total_due'] - (float)$schRow['sum_paid_amount'], 2);

$output = [
    'loan' => $loanRow,
    'repayments' => $repRow,
    'schedule' => $schRow,
    'expected' => [
        'outstanding_balance' => $expectedOutstanding,
        'total_paid' => $expectedTotalPaid,
        'schedule_balance' => $expectedScheduleBalance,
        'unpaid_installments' => (int)$schRow['unpaid_installments']
    ],
    'mismatch' => [
        'outstanding_balance' => round((float)$loanRow['outstanding_balance'], 2) !== $expectedOutstanding,
        'total_paid' => round((float)$loanRow['total_paid'], 2) !== $expectedTotalPaid,
        'principal_balance' => round((float)$loanRow['outstanding_balance'], 2) !== $expectedOutstanding,
    ]
];

echo json_encode($output, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
