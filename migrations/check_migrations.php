<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require_once __DIR__ . '/../config/db_connection.php';
try {
    $db = Database::getInstance()->getConnection();
    $stmt = $db->query("SELECT filename, applied_at FROM schema_migrations");
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($rows)) {
        echo "(no migrations recorded)\n";
    } else {
        foreach ($rows as $r) {
            echo $r['filename'] . " -> " . $r['applied_at'] . "\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
