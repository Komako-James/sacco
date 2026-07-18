<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/InvestmentService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit();
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid or expired security token.';
    header('Location: investments.php');
    exit();
}

$investmentId = (int)($_POST['investment_id'] ?? 0);
$type = trim($_POST['type'] ?? '');
$amount = (float)($_POST['amount'] ?? 0);
$description = trim($_POST['description'] ?? '');
if ($investmentId <= 0 || $amount <= 0 || $type === '') {
    $_SESSION['flash_error'] = 'Invalid transaction data';
    header('Location: investments.php');
    exit();
}

$svc = new \SACCO\Services\InvestmentService();
$res = $svc->addTransaction($investmentId, $type, $amount, $_SESSION['user_id'] ?? null, $description, []);
if ($res['success']) {
    $_SESSION['flash_success'] = 'Transaction recorded';
} else {
    $_SESSION['flash_error'] = $res['message'] ?? 'Failed to record transaction';
}
header('Location: investments.php');
exit();
