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

$data = [
    'name' => trim($_POST['name'] ?? ''),
    'financial_year' => $_POST['financial_year'] ?? null,
    'declaration_date' => $_POST['declaration_date'] ?? null,
    'payment_date' => $_POST['payment_date'] ?? null,
    'rate' => $_POST['rate'] ?? 0,
    'approval_number' => $_POST['approval_number'] ?? null,
    'source' => $_POST['source'] ?? 'share_capital',
    'status' => 'draft',
    'description' => $_POST['description'] ?? null,
    'created_by' => $_SESSION['user_id'] ?? null
];

$svc = new \SACCO\Services\DividendService();
$res = $svc->declareDividend($data);
if ($res['success']) {
    $_SESSION['flash_success'] = 'Dividend declared';
} else {
    $_SESSION['flash_error'] = 'Failed to declare dividend';
}
header('Location: dividends.php'); exit();
