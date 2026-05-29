<?php
try {
    $db = new PDO('mysql:host=127.0.0.1;dbname=sacco_system;charset=utf8mb4', 'root', '');
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $tables = [];
    foreach ($db->query('SHOW TABLES') as $row) {
        $tables[] = $row[0];
    }
    echo "TABLES=" . implode(',', $tables) . "\n";
    if (in_array('schema_migrations', $tables, true)) {
        foreach ($db->query('SELECT filename, applied_at FROM schema_migrations ORDER BY applied_at') as $row) {
            echo "MIGRATION=" . $row['filename'] . "@" . $row['applied_at'] . "\n";
        }
    }
} catch (PDOException $e) {
    echo "ERROR=" . $e->getMessage() . "\n";
}
