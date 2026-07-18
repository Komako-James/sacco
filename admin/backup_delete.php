<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/BackupService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid backup id';
    header('Location: backup.php');
    exit();
}

$service = new \SACCO\Services\BackupService();
$backup = $service->getBackupById($id);
if (!$backup) {
    $_SESSION['flash_error'] = 'Backup not found';
    header('Location: backup.php');
    exit();
}

$result = $service->deleteBackup($id);
if ($result['success']) {
    logActivity($_SESSION['user_id'] ?? null, 'Backup Deleted', 'backup_history', $id, null, ['filename' => $backup['filename']]);
    $_SESSION['flash_success'] = 'Backup deleted';
} else {
    logActivity($_SESSION['user_id'] ?? null, 'Backup Deletion Failed', 'backup_history', $id, null, ['error' => $result['message'] ?? null]);
    $_SESSION['flash_error'] = 'Delete failed: ' . ($result['message'] ?? 'Unknown');
}

header('Location: backup.php');
exit();
