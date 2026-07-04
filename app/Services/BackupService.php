<?php
namespace SACCO\Services;

require_once __DIR__ . '/../../config/db_connection.php';
require_once __DIR__ . '/../../includes/functions.php';

use PDO;
use ZipArchive;

class BackupService
{
    private PDO $db;
    private string $backupDir;
    private string $defaultBackupFolder = 'db_backups';
    private string $filenamePattern = '/^backup_[0-9]{4}_[0-9]{2}_[0-9]{2}_[0-9]{2}_[0-9]{2}_[0-9]{2}(?:\.sql|\.sql\.zip)$/';

    public function __construct(string $backupFolder = null)
    {
        $this->db = Database::getInstance()->getConnection();
        $this->backupDir = $this->normalizeBackupFolder($backupFolder ?: $this->defaultBackupFolder);
        $this->ensureBackupFolder();
        $this->ensureSchema();
    }

    public function getDefaultSettings(): array
    {
        return [
            'backup_folder' => 'db_backups',
            'compression_enabled' => true,
            'max_backups' => 25,
            'auto_cleanup' => true,
            'auto_frequency' => 'weekly',
            'auto_backup_status' => 'enabled',
            'last_run' => null,
            'next_run' => null,
        ];
    }

    public function getBackupFolder(): string
    {
        return $this->backupDir;
    }

    public function getStats(): array
    {
        $totalBackups = 0;
        $latestBackup = null;
        $totalSize = 0;
        $largestBackup = null;
        $oldestBackup = null;
        $newestBackup = null;
        $backupFolderSize = $this->calculateFolderSize($this->backupDir);
        $backupFiles = $this->db->query("SELECT COUNT(*) AS total, MAX(created_at) AS latest, MAX(filesize) AS largest, MIN(created_at) AS oldest, MAX(created_at) AS newest FROM backup_history WHERE status != 'deleted'")->fetch(PDO::FETCH_ASSOC);

        if ($backupFiles) {
            $totalBackups = (int)$backupFiles['total'];
            $latestBackup = $backupFiles['latest'];
            $largestBackup = (int)$backupFiles['largest'];
            $oldestBackup = $backupFiles['oldest'];
            $newestBackup = $backupFiles['newest'];
        }

        return [
            'total_backups' => $totalBackups,
            'latest_backup' => $latestBackup,
            'backup_folder_size' => $backupFolderSize,
            'largest_backup' => $largestBackup,
            'oldest_backup' => $oldestBackup,
            'newest_backup' => $newestBackup,
            'database_size' => $this->getDatabaseSize(),
            'available_disk_space' => $this->getAvailableDiskSpace(),
        ];
    }

    public function getBackups(array $filters, int $limit, int $offset): array
    {
        $where = 'WHERE 1=1';
        $params = [];

        if (!empty($filters['filename'])) {
            $where .= ' AND bh.filename LIKE ?';
            $params[] = '%' . $filters['filename'] . '%';
        }

        if (!empty($filters['description'])) {
            $where .= ' AND bh.description LIKE ?';
            $params[] = '%' . $filters['description'] . '%';
        }

        if (!empty($filters['creator'])) {
            $where .= ' AND (u.username LIKE ? OR u.full_name LIKE ?)';
            $params[] = '%' . $filters['creator'] . '%';
            $params[] = '%' . $filters['creator'] . '%';
        }

        if (!empty($filters['status']) && in_array($filters['status'], ['available', 'failed', 'deleted'], true)) {
            $where .= ' AND bh.status = ?';
            $params[] = $filters['status'];
        }

        if (isset($filters['compressed']) && $filters['compressed'] !== '') {
            $where .= ' AND bh.compressed = ?';
            $params[] = $filters['compressed'] ? 1 : 0;
        }

        if (!empty($filters['backup_type'])) {
            $where .= ' AND bh.backup_type = ?';
            $params[] = $filters['backup_type'];
        }

        if (!empty($filters['date_from']) && strtotime($filters['date_from']) !== false) {
            $where .= ' AND bh.created_at >= ?';
            $params[] = date('Y-m-d 00:00:00', strtotime($filters['date_from']));
        }

        if (!empty($filters['date_to']) && strtotime($filters['date_to']) !== false) {
            $where .= ' AND bh.created_at <= ?';
            $params[] = date('Y-m-d 23:59:59', strtotime($filters['date_to']));
        }

        if (!empty($filters['search'])) {
            $where .= ' AND (
                bh.filename LIKE ? OR
                bh.description LIKE ? OR
                u.username LIKE ? OR
                u.full_name LIKE ?
            )';
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql = "SELECT bh.*, COALESCE(u.full_name, u.username, 'System') AS created_by_name
                FROM backup_history bh
                LEFT JOIN users u ON bh.created_by = u.user_id
                {$where}
                ORDER BY bh.created_at DESC
                LIMIT ? OFFSET ?";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(array_merge($params, [$limit, $offset]));
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function countBackups(array $filters): int
    {
        $where = 'WHERE 1=1';
        $params = [];

        if (!empty($filters['filename'])) {
            $where .= ' AND bh.filename LIKE ?';
            $params[] = '%' . $filters['filename'] . '%';
        }
        if (!empty($filters['description'])) {
            $where .= ' AND bh.description LIKE ?';
            $params[] = '%' . $filters['description'] . '%';
        }
        if (!empty($filters['creator'])) {
            $where .= ' AND (u.username LIKE ? OR u.full_name LIKE ?)';
            $params[] = '%' . $filters['creator'] . '%';
            $params[] = '%' . $filters['creator'] . '%';
        }
        if (!empty($filters['status']) && in_array($filters['status'], ['available', 'failed', 'deleted'], true)) {
            $where .= ' AND bh.status = ?';
            $params[] = $filters['status'];
        }
        if (isset($filters['compressed']) && $filters['compressed'] !== '') {
            $where .= ' AND bh.compressed = ?';
            $params[] = $filters['compressed'] ? 1 : 0;
        }
        if (!empty($filters['backup_type'])) {
            $where .= ' AND bh.backup_type = ?';
            $params[] = $filters['backup_type'];
        }
        if (!empty($filters['date_from']) && strtotime($filters['date_from']) !== false) {
            $where .= ' AND bh.created_at >= ?';
            $params[] = date('Y-m-d 00:00:00', strtotime($filters['date_from']));
        }
        if (!empty($filters['date_to']) && strtotime($filters['date_to']) !== false) {
            $where .= ' AND bh.created_at <= ?';
            $params[] = date('Y-m-d 23:59:59', strtotime($filters['date_to']));
        }
        if (!empty($filters['search'])) {
            $where .= ' AND (
                bh.filename LIKE ? OR
                bh.description LIKE ? OR
                u.username LIKE ? OR
                u.full_name LIKE ?
            )';
            $term = '%' . $filters['search'] . '%';
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
            $params[] = $term;
        }

        $sql = "SELECT COUNT(*) FROM backup_history bh LEFT JOIN users u ON bh.created_by = u.user_id {$where}";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return (int)$stmt->fetchColumn();
    }

    public function getBackupById(int $id): ?array
    {
        $stmt = $this->db->prepare('SELECT * FROM backup_history WHERE id = ?');
        $stmt->execute([$id]);
        $backup = $stmt->fetch(PDO::FETCH_ASSOC);
        return $backup ?: null;
    }

    public function createBackup(string $name, string $description, bool $compress, bool $includeFiles, int $createdBy, string $backupType = 'manual'): array
    {
        $filename = $this->buildFilename($name, $compress);
        $tempSqlPath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('sacco_backup_', true) . '.sql';
        $dumpResult = $this->dumpDatabase($tempSqlPath);

        if (!$dumpResult['success']) {
            return ['success' => false, 'message' => $dumpResult['message']];
        }

        $storedFilename = $filename;
        $destinationPath = $this->backupDir . DIRECTORY_SEPARATOR . $storedFilename;

        if ($compress) {
            if (!class_exists('ZipArchive')) {
                unlink($tempSqlPath);
                return ['success' => false, 'message' => 'Compression is not available on this server.'];
            }

            $zip = new ZipArchive();
            if ($zip->open($destinationPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                unlink($tempSqlPath);
                return ['success' => false, 'message' => 'Unable to create compressed backup file.'];
            }

            $zip->addFile($tempSqlPath, 'backup.sql');
            if ($includeFiles) {
                $uploadsFolder = realpath(__DIR__ . '/../../uploads');
                if ($uploadsFolder && is_dir($uploadsFolder)) {
                    $this->addDirectoryToZip($zip, $uploadsFolder, 'uploads');
                }
            }
            $zip->close();
            unlink($tempSqlPath);
        } else {
            if (!@rename($tempSqlPath, $destinationPath)) {
                $message = 'Unable to store backup file. Please ensure the backup folder is writable.';
                unlink($tempSqlPath);
                return ['success' => false, 'message' => $message];
            }
        }

        $filesize = file_exists($destinationPath) ? filesize($destinationPath) : 0;
        $checksum = file_exists($destinationPath) ? sha1_file($destinationPath) : null;
        $protected = $backupType === 'emergency' ? 1 : 0;

        $stmt = $this->db->prepare(
            'INSERT INTO backup_history (filename, description, created_by, backup_type, compressed, include_files, filesize, status, protected, checksum, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
        );
        $stmt->execute([
            $storedFilename,
            $description,
            $createdBy,
            $backupType,
            $compress ? 1 : 0,
            $includeFiles ? 1 : 0,
            $filesize,
            $filesize > 0 ? 'available' : 'failed',
            $protected,
            $checksum,
        ]);

        return ['success' => true, 'message' => 'Backup created successfully.', 'backup_id' => (int)$this->db->lastInsertId(), 'filename' => $storedFilename];
    }

    public function deleteBackup(int $id): array
    {
        $backup = $this->getBackupById($id);
        if (!$backup) {
            return ['success' => false, 'message' => 'Backup not found.'];
        }

        if ((int)$backup['protected'] === 1) {
            return ['success' => false, 'message' => 'This backup is protected and cannot be deleted.'];
        }

        $filePath = $this->resolveBackupPath($backup['filename']);
        if (!$filePath || !is_file($filePath)) {
            return ['success' => false, 'message' => 'Backup file is missing.'];
        }

        if (!unlink($filePath)) {
            return ['success' => false, 'message' => 'Unable to delete the backup file.'];
        }

        $stmt = $this->db->prepare('UPDATE backup_history SET status = ?, deleted_at = NOW() WHERE id = ?');
        $stmt->execute(['deleted', $id]);

        return ['success' => true, 'message' => 'Backup deleted successfully.'];
    }

    public function restoreBackup(int $id): array
    {
        $backup = $this->getBackupById($id);
        if (!$backup) {
            return ['success' => false, 'message' => 'Backup not found.'];
        }

        if ((string)$backup['status'] === 'deleted') {
            return ['success' => false, 'message' => 'Deleted backups cannot be restored.'];
        }

        $filePath = $this->resolveBackupPath($backup['filename']);
        if (!$filePath || !is_file($filePath)) {
            return ['success' => false, 'message' => 'Backup file was not found on disk.'];
        }

        $emergencyResult = $this->createBackup(
            'emergency_' . date('Y_m_d_H_i_s'),
            'Emergency backup created automatically before restoring ' . $backup['filename'],
            false,
            false,
            $backup['created_by'] ?? 0,
            'emergency'
        );

        if (!$emergencyResult['success']) {
            return ['success' => false, 'message' => 'Emergency backup failed before restore: ' . $emergencyResult['message']];
        }

        $importResult = $this->importBackupFile($filePath);
        if (!$importResult['success']) {
            return ['success' => false, 'message' => 'Restore failed: ' . $importResult['message'], 'emergency_backup_id' => $emergencyResult['backup_id'] ?? null];
        }

        $stmt = $this->db->prepare('UPDATE backup_history SET restored_at = NOW() WHERE id = ?');
        $stmt->execute([$id]);

        return ['success' => true, 'message' => 'Backup restored successfully.', 'emergency_backup_id' => $emergencyResult['backup_id'] ?? null];
    }

    public function cleanupOldBackups(int $keep): array
    {
        $cleanup = ['deleted' => 0, 'skipped' => 0];
        $keep = max(0, $keep);

        if ($keep === 0) {
            return $cleanup;
        }

        $stmt = $this->db->prepare('SELECT id, filename FROM backup_history WHERE status = ? AND protected = 0 ORDER BY created_at DESC LIMIT 18446744073709551615 OFFSET ?');
        $stmt->execute(['available', $keep]);
        $oldBackups = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($oldBackups as $backup) {
            $result = $this->deleteBackup((int)$backup['id']);
            if ($result['success']) {
                $cleanup['deleted']++;
            } else {
                $cleanup['skipped']++;
            }
        }

        return $cleanup;
    }

    public function resolveBackupPath(string $filename): ?string
    {
        $basename = basename($filename);
        if (!preg_match($this->filenamePattern, $basename)) {
            return null;
        }

        $path = $this->backupDir . DIRECTORY_SEPARATOR . $basename;
        $realPath = realpath($path);
        if ($realPath === false) {
            return null;
        }

        if (strpos($realPath, realpath($this->backupDir)) !== 0) {
            return null;
        }

        return $realPath;
    }

    private function normalizeBackupFolder(string $backupFolder): string
    {
        if (empty($backupFolder)) {
            $backupFolder = $this->defaultBackupFolder;
        }

        if (preg_match('/^(?:[A-Za-z]:\\|\\\\|\/)/', $backupFolder)) {
            return rtrim($backupFolder, '\\/');
        }

        return rtrim(realpath(__DIR__ . '/../../') ?: __DIR__ . '/../../', '\\/\') . DIRECTORY_SEPARATOR . trim($backupFolder, '\\/');
    }

    private function ensureBackupFolder(): void
    {
        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    private function ensureSchema(): void
    {
        $sql = "CREATE TABLE IF NOT EXISTS backup_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            filename VARCHAR(255) NOT NULL,
            description TEXT,
            created_by INT DEFAULT NULL,
            backup_type ENUM('manual','automatic','emergency') NOT NULL DEFAULT 'manual',
            compressed TINYINT(1) NOT NULL DEFAULT 0,
            include_files TINYINT(1) NOT NULL DEFAULT 0,
            filesize BIGINT NOT NULL DEFAULT 0,
            status ENUM('available','failed','deleted') NOT NULL DEFAULT 'available',
            protected TINYINT(1) NOT NULL DEFAULT 0,
            checksum VARCHAR(64) DEFAULT NULL,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            restored_at DATETIME DEFAULT NULL,
            deleted_at DATETIME DEFAULT NULL,
            updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX idx_created_at (created_at),
            INDEX idx_status (status),
            INDEX idx_backup_type (backup_type),
            INDEX idx_protected (protected)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4";
        $this->db->exec($sql);
    }

    private function buildFilename(string $name, bool $compress): string
    {
        $prefix = 'backup_' . date('Y_m_d_H_i_s');
        if ($name !== '') {
            $safeName = preg_replace('/[^a-zA-Z0-9_-]/', '_', trim($name));
            if ($safeName !== '') {
                $prefix .= '_' . $safeName;
            }
        }

        return $prefix . ($compress ? '.sql.zip' : '.sql');
    }

    private function dumpDatabase(string $outputPath): array
    {
        if ($this->isMysqldumpAvailable()) {
            $command = $this->buildMysqldumpCommand($outputPath);
            $result = $this->executeCommand($command);
            if ($result['success'] && file_exists($outputPath)) {
                return ['success' => true, 'message' => 'Database dump completed.'];
            }
            return ['success' => false, 'message' => 'Database dump failed: ' . implode(' ', $result['output'])];
        }

        return $this->dumpDatabaseManually($outputPath);
    }

    private function isMysqldumpAvailable(): bool
    {
        $command = stripos(PHP_OS, 'WIN') === 0 ? 'where mysqldump 2>NUL' : 'command -v mysqldump 2>/dev/null';
        $output = shell_exec($command);
        return !empty($output);
    }

    private function buildMysqldumpCommand(string $outputPath): string
    {
        $host = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASS;
        $dbName = DB_NAME;
        $target = escapeshellarg($outputPath);

        $passwordString = $pass === '' ? '' : '--password=' . escapeshellarg($pass);

        return sprintf(
            'mysqldump --single-transaction --quick --skip-lock-tables --default-character-set=utf8mb4 -h %s -u %s %s %s > %s',
            escapeshellarg($host),
            escapeshellarg($user),
            $passwordString,
            escapeshellarg($dbName),
            $target
        );
    }

    private function dumpDatabaseManually(string $outputPath): array
    {
        $handle = fopen($outputPath, 'w');
        if (!$handle) {
            return ['success' => false, 'message' => 'Unable to write backup file.'];
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=0;\n");
        fwrite($handle, "SET NAMES utf8mb4;\n\n");

        $tables = $this->db->query("SHOW FULL TABLES WHERE Table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_NUM);
        foreach ($tables as $tableRow) {
            $table = $tableRow[0];
            $createStmt = $this->db->query("SHOW CREATE TABLE `{$table}`")->fetch(PDO::FETCH_ASSOC);
            if (!isset($createStmt['Create Table'])) {
                continue;
            }

            fwrite($handle, "DROP TABLE IF EXISTS `{$table}`;\n");
            fwrite($handle, $createStmt['Create Table'] . ";\n\n");

            $rows = $this->db->query("SELECT * FROM `{$table}`");
            foreach ($rows as $row) {
                $values = array_map(function ($value) {
                    return $this->quoteValue($value);
                }, array_values($row));
                $columns = array_map(fn($col) => "`{$col}`", array_keys($row));
                fwrite($handle, 'INSERT INTO `' . $table . '` (' . implode(', ', $columns) . ') VALUES (' . implode(', ', $values) . ');\n');
            }

            fwrite($handle, "\n");
        }

        fwrite($handle, "SET FOREIGN_KEY_CHECKS=1;\n");
        fclose($handle);

        return ['success' => true, 'message' => 'Database dump created using PHP fallback.'];
    }

    private function quoteValue($value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        return $this->db->quote((string)$value);
    }

    private function importBackupFile(string $filePath): array
    {
        $isZip = str_ends_with($filePath, '.sql.zip');
        $sqlFile = $filePath;
        $tempFile = null;

        if ($isZip) {
            if (!class_exists('ZipArchive')) {
                return ['success' => false, 'message' => 'Zip support is required to restore this backup.'];
            }

            $zip = new ZipArchive();
            if ($zip->open($filePath) !== true) {
                return ['success' => false, 'message' => 'Unable to open compressed backup file.'];
            }

            $sqlName = null;
            for ($i = 0; $i < $zip->numFiles; $i++) {
                $entry = $zip->getNameIndex($i);
                if (str_ends_with($entry, '.sql')) {
                    $sqlName = $entry;
                    break;
                }
            }

            if (!$sqlName) {
                $zip->close();
                return ['success' => false, 'message' => 'Backup archive did not contain a SQL file.'];
            }

            $tempFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . uniqid('sacco_restore_', true) . '.sql';
            if (!$zip->extractTo(dirname($tempFile), $sqlName)) {
                $zip->close();
                return ['success' => false, 'message' => 'Unable to extract SQL from backup archive.'];
            }

            $zip->close();
            $sqlFile = dirname($tempFile) . DIRECTORY_SEPARATOR . basename($sqlName);
        }

        $result = $this->importSqlFile($sqlFile);

        if ($tempFile && file_exists($tempFile)) {
            @unlink($tempFile);
        }

        return $result;
    }

    private function importSqlFile(string $sqlFile): array
    {
        if (!is_file($sqlFile) || !is_readable($sqlFile)) {
            return ['success' => false, 'message' => 'SQL file is not readable.'];
        }

        if ($this->isMysqlAvailable()) {
            $command = $this->buildMysqlCommand($sqlFile);
            $result = $this->executeCommand($command);
            if ($result['success']) {
                return ['success' => true, 'message' => 'Restore completed using MySQL CLI.'];
            }
        }

        return $this->importSqlManually($sqlFile);
    }

    private function isMysqlAvailable(): bool
    {
        $command = stripos(PHP_OS, 'WIN') === 0 ? 'where mysql 2>NUL' : 'command -v mysql 2>/dev/null';
        $output = shell_exec($command);
        return !empty($output);
    }

    private function buildMysqlCommand(string $sqlFile): string
    {
        $host = DB_HOST;
        $user = DB_USER;
        $pass = DB_PASS;
        $dbName = DB_NAME;
        $passwordString = $pass === '' ? '' : '--password=' . escapeshellarg($pass);

        return sprintf(
            'mysql --default-character-set=utf8mb4 -h %s -u %s %s %s < %s',
            escapeshellarg($host),
            escapeshellarg($user),
            $passwordString,
            escapeshellarg($dbName),
            escapeshellarg($sqlFile)
        );
    }

    private function importSqlManually(string $sqlFile): array
    {
        $handle = fopen($sqlFile, 'r');
        if (!$handle) {
            return ['success' => false, 'message' => 'Unable to read SQL file for manual restore.'];
        }

        $delimiter = ';';
        $statement = '';

        while (($line = fgets($handle)) !== false) {
            $trimmed = trim($line);
            if ($trimmed === '' || str_starts_with($trimmed, '--') || str_starts_with($trimmed, '#')) {
                continue;
            }

            if (preg_match('/^DELIMITER\s+(.*)$/i', $trimmed, $matches)) {
                $delimiter = $matches[1];
                continue;
            }

            $statement .= $line;
            if (substr(trim($statement), -strlen($delimiter)) === $delimiter) {
                $sql = substr($statement, 0, -strlen($delimiter));
                $statement = '';

                try {
                    $this->db->exec($sql);
                } catch (\PDOException $e) {
                    fclose($handle);
                    return ['success' => false, 'message' => 'SQL execution failed: ' . $e->getMessage()];
                }
            }
        }

        fclose($handle);
        return ['success' => true, 'message' => 'Restore completed using PHP SQL import.'];
    }

    private function executeCommand(string $command): array
    {
        $output = [];
        $return = 0;
        exec($command, $output, $return);
        return ['success' => $return === 0, 'output' => $output, 'return' => $return];
    }

    private function addDirectoryToZip(ZipArchive $zip, string $sourceDir, string $entryPath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \FilesystemIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $localPath = $entryPath . DIRECTORY_SEPARATOR . ltrim(str_replace($sourceDir, '', $item->getPathname()), '\\/');
            if ($item->isDir()) {
                $zip->addEmptyDir($localPath);
            } else {
                $zip->addFile($item->getPathname(), $localPath);
            }
        }
    }

    private function getDatabaseSize(): int
    {
        try {
            $stmt = $this->db->prepare('SELECT IFNULL(SUM(data_length + index_length), 0) AS db_size FROM information_schema.tables WHERE table_schema = ?');
            $stmt->execute([DB_NAME]);
            $result = $stmt->fetch(PDO::FETCH_ASSOC);
            return (int)($result['db_size'] ?? 0);
        } catch (\Exception $e) {
            return 0;
        }
    }

    private function getAvailableDiskSpace(): int
    {
        $space = disk_free_space($this->backupDir);
        return $space === false ? 0 : (int)$space;
    }

    private function calculateFolderSize(string $directory): int
    {
        if (!is_dir($directory)) {
            return 0;
        }

        $size = 0;
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory, \FilesystemIterator::SKIP_DOTS));
        foreach ($iterator as $file) {
            if ($file->isFile()) {
                $size += $file->getSize();
            }
        }

        return $size;
    }
}
