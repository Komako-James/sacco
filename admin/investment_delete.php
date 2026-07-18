<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/InvestmentService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    $_SESSION['flash_error'] = 'Invalid investment id';
    header('Location: investments.php');
    exit();
}

$svc = new \SACCO\Services\InvestmentService();
$res = $svc->deleteInvestment($id, $_SESSION['user_id'] ?? null);
if ($res['success']) {
    $_SESSION['flash_success'] = 'Investment cancelled';
} else {
    $_SESSION['flash_error'] = $res['message'] ?? 'Failed to cancel investment';
}
header('Location: investments.php');
exit();
