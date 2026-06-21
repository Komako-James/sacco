<?php
require_once __DIR__ . '/../config/db_connection.php';
$db = Database::getInstance()->getConnection();

function j($value) { return json_encode($value, JSON_UNESCAPED_SLASHES); }

$stmt = $db->prepare('SELECT * FROM loans WHERE loan_id = ?');
$stmt->execute([16]);
echo "LOAN\n";
echo j($stmt->fetch(PDO::FETCH_ASSOC)), PHP_EOL;

$stmt = $db->prepare('SELECT * FROM loan_repayments WHERE loan_id = ? ORDER BY repayment_id');
$stmt->execute([16]);
echo "REPAYMENTS\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo j($row), PHP_EOL;
}

$stmt = $db->prepare('SELECT * FROM loan_repayment_schedule WHERE loan_id = ? ORDER BY installment_no');
$stmt->execute([16]);
echo "SCHEDULE\n";
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo j($row), PHP_EOL;
}
