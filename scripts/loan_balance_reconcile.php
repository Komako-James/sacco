<?php
require_once __DIR__ . '/../config/db_connection.php';
$db = Database::getInstance()->getConnection();
$loanId = isset($_GET['loan_id']) ? (int)$_GET['loan_id'] : 16;
$loan = $db->prepare('SELECT loan_id, outstanding_balance, total_paid, amount_approved, status FROM loans WHERE loan_id = ?');
$loan->execute([$loanId]);
$loanRow = $loan->fetch(PDO::FETCH_ASSOC);
$rep = $db->prepare('SELECT COALESCE(SUM(amount_paid),0) AS sum_amount_paid, COALESCE(SUM(principal_paid),0) AS sum_principal_paid, COALESCE(SUM(interest_paid),0) AS sum_interest_paid, COALESCE(SUM(penalty_paid),0) AS sum_penalty_paid FROM loan_repayments WHERE loan_id = ?');
$rep->execute([$loanId]);
$repRow = $rep->fetch(PDO::FETCH_ASSOC);
$sch = $db->prepare('SELECT COALESCE(SUM(total_due),0) AS sum_total_due, COALESCE(SUM(paid_amount),0) AS sum_paid_amount FROM loan_repayment_schedule WHERE loan_id = ?');
$sch->execute([$loanId]);
$schRow = $sch->fetch(PDO::FETCH_ASSOC);
echo json_encode(['loan' => $loanRow, 'repayments' => $repRow, 'schedule' => $schRow], JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
