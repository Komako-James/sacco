<?php
require_once __DIR__ . '/../config/db_connection.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->prepare('SELECT loan_id, loan_ref_no, status, amount_requested, amount_approved, disbursement_date, outstanding_balance, product_id FROM loans WHERE loan_id = ?');
$stmt->execute([15]);
$loan = $stmt->fetch(PDO::FETCH_ASSOC);
file_put_contents(__DIR__ . '/query_loan_15_result.json', json_encode($loan, JSON_PRETTY_PRINT));
