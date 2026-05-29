<?php
require_once __DIR__ . '/config/db_connection.php';
$db = Database::getInstance()->getConnection();
$tables = [
    'password_reset_tokens',
    'member_login_credentials_history',
    'member_login_audit',
    'member_otp_tokens',
    'member_devices',
    'member_sessions',
    'member_security_preferences',
    'member_share_holdings',
    'member_share_transactions',
    'ledger_entries',
    'member_share_transfers',
];
foreach ($tables as $table) {
    $stmt = $db->query("SHOW TABLES LIKE '$table'");
    $exists = $stmt->fetch(PDO::FETCH_NUM) ? 'yes' : 'no';
    echo "$table: $exists\n";
}
$stmt = $db->query('SELECT id, filename FROM schema_migrations ORDER BY id');
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "MIGRATION: {$row['id']} {$row['filename']}\n";
}
