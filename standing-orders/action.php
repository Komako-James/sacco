<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/StandingOrderService.php';

$auth->requireLogin();
$service = new \SACCO\Services\StandingOrderService();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo 'Method not allowed.';
    exit;
}

$standingOrderId = isset($_POST['standing_order_id']) ? (int)$_POST['standing_order_id'] : 0;
$action = isset($_POST['action']) ? trim($_POST['action']) : '';
$returnUrl = !empty($_POST['return_url']) ? $_POST['return_url'] : 'list.php';

$order = $service->getStandingOrderById($standingOrderId);
if (!$order) {
    $_SESSION['flash_error'] = 'Standing order not found.';
    header('Location: ' . $returnUrl);
    exit;
}

if ($action === 'suspend') {
    $success = $service->suspendStandingOrder($standingOrderId);
    $_SESSION['flash_success'] = $success ? 'Standing order suspended.' : 'Failed to suspend the order.';
} elseif ($action === 'resume') {
    if (!empty($order['status']) && $order['status'] === 'cancelled') {
        $_SESSION['flash_error'] = 'Cancelled orders cannot be resumed.';
    } else {
        $success = $service->resumeStandingOrder($standingOrderId);
        $_SESSION['flash_success'] = $success ? 'Standing order resumed.' : 'Failed to resume the order.';
    }
} elseif ($action === 'cancel') {
    $success = $service->cancelStandingOrder($standingOrderId);
    $_SESSION['flash_success'] = $success ? 'Standing order cancelled.' : 'Failed to cancel the order.';
} else {
    $_SESSION['flash_error'] = 'Invalid action.';
}

header('Location: ' . $returnUrl);
exit;
