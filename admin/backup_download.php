<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/BackupService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo 'Invalid request';
    exit();
}

$service = new \SACCO\Services\BackupService();
$backup = $service->getBackupById($id);
if (!$backup || ($backup['status'] ?? '') === 'deleted') {
    http_response_code(404);
    echo 'Backup not found';
    exit();
}

$path = $service->resolveBackupPath($backup['filename']);
if (!$path) {
    http_response_code(404);
    echo 'File not available';
    exit();
}

// Stream the file
$basename = basename($path);
$size = filesize($path);
header('Content-Description: File Transfer');
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $basename . '"');
header('Content-Transfer-Encoding: binary');
header('Expires: 0');
header('Cache-Control: must-revalidate');
header('Pragma: public');
header('Content-Length: ' . $size);
// Log download
logActivity($_SESSION['user_id'] ?? null, 'Backup Downloaded', 'backup_history', $id, null, ['filename' => $backup['filename']]);

// Output in chunks
$chunkSize = 8192;
$fp = fopen($path, 'rb');
if ($fp) {
    while (!feof($fp)) {
        echo fread($fp, $chunkSize);
        flush();
    }
    fclose($fp);
}
exit();
