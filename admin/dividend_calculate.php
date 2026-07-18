<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/DividendService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit(); }

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid or expired security token.';
    header('Location: dividends.php');
    exit();
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) { $_SESSION['flash_error'] = 'Invalid declaration id'; header('Location: dividends.php'); exit(); }

$svc = new \SACCO\Services\DividendService();
$res = $svc->calculateDividends($id, 0.0);
if ($res['success']) {
    $_SESSION['flash_success'] = 'Dividends calculated: ' . ($res['payments_created'] ?? 0) . ' payments created.';
} else {
    $_SESSION['flash_error'] = 'Calculation failed: ' . ($res['message'] ?? 'Unknown');
}
header('Location: dividends.php'); exit();
