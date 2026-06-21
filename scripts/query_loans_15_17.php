<?php
require_once __DIR__ . '/../config/db_connection.php';
$db = Database::getInstance()->getConnection();
$ids = [15,17];
$stmt = $db->prepare('SELECT loan_id, loan_ref_no, status, amount_requested, amount_approved, outstanding_balance, product_id FROM loans WHERE loan_id = ?');
$result = [];
foreach ($ids as $id) {
    $stmt->execute([$id]);
    $result[$id] = $stmt->fetch(PDO::FETCH_ASSOC);
}
file_put_contents(__DIR__ . '/query_loans_15_17_result.json', json_encode($result, JSON_PRETTY_PRINT));
echo json_encode(['success'=>true]);
