<?php
require_once __DIR__ . '/config/db_connection.php';
$db = Database::getInstance()->getConnection();

function dumpQuery(PDO $db, string $label, string $sql, array $params = []): void {
    echo $label, PHP_EOL;
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
        echo json_encode($row, JSON_UNESCAPED_SLASHES), PHP_EOL;
    }
}

dumpQuery($db, 'LOAN', 'SELECT loan_id, loan_ref_no, member_id, product_id, amount_requested, amount_approved, interest_rate, repayment_period_months, outstanding_balance, principal_balance, interest_accrued, penalty_accrued, total_paid, status, application_date, approval_date, disbursement_date, first_payment_date, last_payment_date FROM loans WHERE loan_id = ?', [16]);
dumpQuery($db, 'REPAYMENTS', 'SELECT repayment_id, amount_paid, principal_paid, interest_paid, penalty_paid, payment_method, reference_number, receipt_number, repayment_date, status FROM loan_repayments WHERE loan_id = ? ORDER BY repayment_id', [16]);
dumpQuery($db, 'SCHEDULE', 'SELECT schedule_id, installment_number, due_date, principal_due, interest_due, penalty_due, total_due, principal_paid, interest_paid, penalty_paid, total_paid, status, paid_date FROM loan_repayment_schedule WHERE loan_id = ? ORDER BY installment_number', [16]);
