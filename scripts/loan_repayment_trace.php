<?php
require_once __DIR__ . '/../config/db_connection.php';
$db = Database::getInstance()->getConnection();
$loanId = isset($_GET['loan_id']) ? (int)$_GET['loan_id'] : 16;
$stmt = $db->prepare('SELECT loan_id, loan_ref_no, amount_approved, interest_rate, repayment_period_months, outstanding_balance, total_paid, status FROM loans WHERE loan_id = ?');
$stmt->execute([$loanId]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);
echo "LOAN\n" . json_encode($loan, JSON_UNESCAPED_SLASHES) . "\n";
$stmt = $db->prepare('SELECT repayment_id, amount_paid, principal_paid, interest_paid, penalty_paid, payment_method, reference_no, receipt_no, payment_date FROM loan_repayments WHERE loan_id = ? ORDER BY repayment_id');
$stmt->execute([$loanId]);
echo "REPAYMENTS\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
}
        $stmt = $db->prepare('SELECT schedule_id, installment_no, due_date, principal_amount, interest_amount, total_due, paid_amount, principal_balance, status, paid_date, late_penalty FROM loan_repayment_schedule WHERE loan_id = ? ORDER BY installment_no');
$stmt->execute([$loanId]);
echo "SCHEDULE\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES) . "\n";
}
