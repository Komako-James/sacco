<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/DividendService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid or expired security token.';
    header('Location: dividends.php');
    exit();
}

$id = (int)($_POST['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid payment id';
    header('Location: dividends.php');
    exit();
}

$svc = new \SACCO\Services\DividendService();
$res = $svc->payDividend($id, 'cash', $_SESSION['user_id'] ?? null);
if ($res['success']) {
    $_SESSION['flash_success'] = 'Dividend payment marked as paid';
} else {
    $_SESSION['flash_error'] = $res['message'] ?? 'Failed to mark payment as paid';
}
header('Location: dividends.php');
exit();
