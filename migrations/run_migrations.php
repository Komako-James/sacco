<?php
require_once __DIR__ . '/../config/db_connection.php';

$db = Database::getInstance()->getConnection();

$dir = __DIR__ . '/';
$files = array_values(array_filter(scandir($dir), function($f){
    return preg_match('/^\d+_.*\.sql$/', $f);
}));

// Ensure schema_migrations exists
$db->exec("CREATE TABLE IF NOT EXISTS schema_migrations (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(255) NOT NULL,
  applied_at DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

$applied = [];
$stmt = $db->prepare("SELECT filename FROM schema_migrations");
$stmt->execute();
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $applied[$row['filename']] = true;
}

foreach ($files as $file) {
    if (isset($applied[$file])) continue;

    echo "Applying migration: $file\n";
    $sql = file_get_contents($dir . $file);
    try {
        $db->beginTransaction();

        // Preprocess: rewrite ALTER TABLE users blocks that use
        // "ADD COLUMN IF NOT EXISTS" into individual ALTER statements
        // for each column so we can check INFORMATION_SCHEMA first.
        $modifiedSql = $sql;
        if (preg_match('/ALTER\s+TABLE\s+`?users`?(.+?);/is', $sql, $m)) {
            $alterBlock = $m[0];
            if (preg_match_all('/ADD\s+COLUMN\s+IF\s+NOT\s+EXISTS\s+`?([a-zA-Z0-9_]+)`?\s+([^,;]+)/i', $alterBlock, $cols, PREG_SET_ORDER)) {
                $newStatements = [];
                foreach ($cols as $c) {
                    $col = $c[1];
                    $def = trim($c[2]);
                    $newStatements[] = "ALTER TABLE users ADD COLUMN `" . $col . "` " . $def;
                }
                $modifiedSql = str_replace($alterBlock, implode(";\n", $newStatements) . ";\n", $sql);
            }
        }

        // Execute statement-by-statement
        $statements = preg_split('/;\s*/', $modifiedSql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') continue;

            $db->exec($statement);
        }
        $ins = $db->prepare("INSERT INTO schema_migrations (filename, applied_at) VALUES (?, NOW())");
        $ins->execute([$file]);
        $db->commit();
        echo "Applied: $file\n";
    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo "Failed: $file - " . $e->getMessage() . "\n";
        break;
    }
}

echo "Migrations complete.\n";
