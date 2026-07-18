<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/InvestmentService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405); exit();
}

if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['flash_error'] = 'Invalid or expired security token.';
    header('Location: investments.php');
    exit();
}

$data = [
    'name' => trim($_POST['name'] ?? ''),
    'type_id' => $_POST['type_id'] ?? null,
    'institution' => $_POST['institution'] ?? null,
    'reference' => $_POST['reference'] ?? null,
    'investment_date' => $_POST['investment_date'] ?? null,
    'maturity_date' => $_POST['maturity_date'] ?? null,
    'principal' => $_POST['principal'] ?? 0,
    'interest_rate' => $_POST['interest_rate'] ?? 0,
    'expected_return' => $_POST['expected_return'] ?? 0,
    'current_value' => $_POST['current_value'] ?? null,
    'currency' => normalizeCurrencyCode($_POST['currency'] ?? 'UGX'),
    'status' => $_POST['status'] ?? 'active',
    'description' => $_POST['description'] ?? null,
    'attachments' => $_POST['attachments'] ?? null,
    'interest_payment_frequency' => $_POST['interest_payment_frequency'] ?? 'At Maturity',
    'auto_recognize_interest' => !empty($_POST['auto_recognize_interest']) ? 1 : 0,
    'expected_interest' => $_POST['expected_interest'] ?? ($_POST['expected_return'] ?? 0),
    'created_by' => $_SESSION['user_id'] ?? null
];

$svc = new \SACCO\Services\InvestmentService();
$res = $svc->createInvestment($data);
if ($res['success']) {
    $_SESSION['flash_success'] = 'Investment created';
} else {
    $_SESSION['flash_error'] = 'Failed to create investment';
}
header('Location: investments.php'); exit();
