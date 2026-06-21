<?php
require_once __DIR__ . '/../config/db_connection.php';
$db = Database::getInstance()->getConnection();
foreach (['loans','loan_repayment_schedule','loan_repayments'] as $table) {
    echo strtoupper($table), PHP_EOL;
    $stmt = $db->prepare('SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? ORDER BY ORDINAL_POSITION');
    $stmt->execute([$table]);
    foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $column) {
        echo $column, PHP_EOL;
    }
}
