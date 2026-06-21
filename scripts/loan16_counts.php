<?php
require_once __DIR__ . '/../config/db_connection.php';
$db = Database::getInstance()->getConnection();
$queries = [
    'loan_repayments' => 'SELECT COUNT(*) FROM loan_repayments WHERE loan_id = 16',
    'loan_repayment_schedule' => 'SELECT COUNT(*) FROM loan_repayment_schedule WHERE loan_id = 16'
];
$out = [];
foreach ($queries as $key => $sql) {
    $out[$key] = (int)$db->query($sql)->fetchColumn();
}
echo json_encode($out, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
