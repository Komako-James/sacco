<?php
require_once __DIR__ . '/config/db_connection.php';
$db = Database::getInstance()->getConnection();
$stmt = $db->query("SHOW COLUMNS FROM member_share_transactions LIKE 'transaction_type'");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo $row['Field'] . '\t' . $row['Type'] . '\t' . $row['Null'] . '\t' . $row['Default'] . '\n';
}
$stmt = $db->query('SELECT id, filename FROM schema_migrations ORDER BY id');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "MIGRATION: {$row['id']} {$row['filename']}\n";
}
