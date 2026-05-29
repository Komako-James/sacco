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

function columnExists(PDO $db, string $table, string $column): bool
{
    $stmt = $db->prepare("SELECT 1 FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ? AND column_name = ? LIMIT 1");
    $stmt->execute([$table, $column]);
    return (bool)$stmt->fetchColumn();
}

function indexExists(PDO $db, string $table, string $index): bool
{
    $stmt = $db->prepare("SELECT 1 FROM information_schema.statistics WHERE table_schema = DATABASE() AND table_name = ? AND index_name = ? LIMIT 1");
    $stmt->execute([$table, $index]);
    return (bool)$stmt->fetchColumn();
}

function isIgnorableDuplicateSchemaError(PDOException $e): bool
{
    $errorInfo = $e->errorInfo;
    $errorCode = isset($errorInfo[1]) ? $errorInfo[1] : null;
    return in_array($errorCode, [1060, 1061], true);
}

function rewriteAlterTableIfNotExists(PDO $db, string $sql): string
{
    return preg_replace_callback('/ALTER\s+TABLE\s+`?([A-Za-z0-9_]+)`?\s+(.+?);/is', function ($match) use ($db) {
        $table = $match[1];
        $body = $match[2];
        $replacements = [];

        if (preg_match_all('/ADD\s+COLUMN\s+IF\s+NOT\s+EXISTS\s+`?([A-Za-z0-9_]+)`?\s+((?:(?!ADD\s+COLUMN\s+IF\s+NOT\s+EXISTS).)*)(?:,|$)/is', $body, $cols, PREG_SET_ORDER)) {
            foreach ($cols as $col) {
                $column = $col[1];
                $definition = trim($col[2]);
                if (!columnExists($db, $table, $column)) {
                    $replacements[] = "ALTER TABLE `$table` ADD COLUMN `$column` " . $definition;
                }
            }
        }

        if (preg_match_all('/ADD\s+INDEX\s+IF\s+NOT\s+EXISTS\s+`?([A-Za-z0-9_]+)`?\s*\(([^)]*)\)(?:,|$)/is', $body, $indexes, PREG_SET_ORDER)) {
            foreach ($indexes as $idx) {
                $indexName = $idx[1];
                $definition = trim($idx[2]);
                if (!indexExists($db, $table, $indexName)) {
                    $replacements[] = "ALTER TABLE `$table` ADD INDEX `$indexName` ($definition)";
                }
            }
        }

        if ($replacements) {
            return implode(";\n", $replacements) . ";\n";
        }

        return $match[0];
    }, $sql);
}

function rewriteCreateIndexIfNotExists(PDO $db, string $sql): string
{
    return preg_replace_callback('/CREATE\s+INDEX\s+IF\s+NOT\s+EXISTS\s+`?([A-Za-z0-9_]+)`?\s+ON\s+`?([A-Za-z0-9_]+)`?\s*\(([^)]+)\)/i', function ($match) use ($db) {
        $indexName = $match[1];
        $table = $match[2];
        $definition = trim($match[3]);
        if (!indexExists($db, $table, $indexName)) {
            return "CREATE INDEX `$indexName` ON `$table` ($definition)";
        }
        return '';
    }, $sql);
}

foreach ($files as $file) {
    if (isset($applied[$file])) continue;

    echo "Applying migration: $file\n";
    $sql = file_get_contents($dir . $file);
    try {
        $db->beginTransaction();

        $modifiedSql = $sql;
        $modifiedSql = rewriteAlterTableIfNotExists($db, $modifiedSql);
        $modifiedSql = rewriteCreateIndexIfNotExists($db, $modifiedSql);

        // Execute statement-by-statement
        $statements = preg_split('/;\s*/', $modifiedSql);
        foreach ($statements as $statement) {
            $statement = trim($statement);
            if ($statement === '') continue;

            echo "Executing statement: " . substr($statement, 0, 180) . "\n";
            try {
                $db->exec($statement);
            } catch (PDOException $e) {
                if (isIgnorableDuplicateSchemaError($e)) {
                    echo "  Ignored duplicate schema error: " . $e->getMessage() . "\n";
                    continue;
                }
                throw $e;
            }
        }
        $ins = $db->prepare("INSERT INTO schema_migrations (filename, applied_at) VALUES (?, NOW())");
        $ins->execute([$file]);
        $db->commit();
        echo "Applied: $file\n";
    } catch (Exception $e) {
        try {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
        } catch (Exception $rollbackException) {
            // Ignore rollback failures when the transaction is already closed.
        }
        $errorInfo = method_exists($e, 'errorInfo') ? $e->errorInfo : null;
        echo "Failed: $file - " . $e->getMessage();
        if (is_array($errorInfo)) {
            echo " | SQLSTATE=" . ($errorInfo[0] ?? 'N/A') . " errcode=" . ($errorInfo[1] ?? 'N/A') . "";
        }
        echo "\n";
        break;
    }
}

echo "Migrations complete.\n";
