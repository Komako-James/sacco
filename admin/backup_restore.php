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
$confirm = trim($_POST['confirm_text'] ?? '');
if ($id <= 0 || strtoupper($confirm) !== 'RESTORE') {
    $_SESSION['flash_error'] = 'Restore confirmation failed. Type RESTORE to confirm.';
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

// Log attempt
logActivity($_SESSION['user_id'] ?? null, 'Backup Restore Initiated', 'backup_history', $id, null, ['filename' => $backup['filename']]);

$result = $service->restoreBackup($id);
if ($result['success']) {
    logActivity($_SESSION['user_id'] ?? null, 'Backup Restored', 'backup_history', $id, null, ['emergency_backup_id' => $result['emergency_backup_id'] ?? null]);
    $_SESSION['flash_success'] = 'Restore completed successfully.';
} else {
    logActivity($_SESSION['user_id'] ?? null, 'Backup Restore Failed', 'backup_history', $id, null, ['error' => $result['message'] ?? null]);
    $_SESSION['flash_error'] = 'Restore failed: ' . ($result['message'] ?? 'Unknown');
}

header('Location: backup.php');
exit();
