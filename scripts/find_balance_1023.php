<?php
require_once __DIR__ . '/../config/db_connection.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->query('SELECT loan_id, loan_ref_no, amount_approved, outstanding_balance, total_paid, status FROM loans WHERE ROUND(outstanding_balance, 2) IN (1023.24, 1023.25, 1023.23) ORDER BY loan_id DESC');
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo json_encode($row, JSON_UNESCAPED_SLASHES), PHP_EOL;
}
