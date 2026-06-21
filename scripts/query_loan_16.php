<?php
require_once __DIR__ . '/../config/db_connection.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT loan_id, loan_ref_no, status, amount_requested, amount_approved, interest_rate, repayment_period_months, outstanding_balance, principal_balance, interest_accrued, penalty_accrued, total_paid, disbursement_date, first_payment_date, last_payment_date FROM loans WHERE loan_id = ?');
$stmt->execute([16]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);
file_put_contents(__DIR__ . '/query_loan_16_result.json', json_encode($loan, JSON_PRETTY_PRINT));
