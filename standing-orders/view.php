<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/Services/StandingOrderService.php';

$auth->requireLogin();
$service = new \SACCO\Services\StandingOrderService();

$standingOrderId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$order = $service->getStandingOrderById($standingOrderId);
if (!$order) {
    http_response_code(404);
    echo 'Standing order not found.';
    exit;
}

$history = $service->getStandingOrderHistory($standingOrderId);

function getStatusLabel($order) {
    $today = date('Y-m-d');
    if (!empty($order['end_date']) && $order['end_date'] < $today) {
        return 'expired';
    }
    if (isset($order['status'])) {
        return $order['status'];
    }
    return !empty($order['is_active']) ? 'active' : 'suspended';
}

function getStatusClass($status) {
    return match ($status) {
        'active' => 'success',
        'suspended' => 'warning',
        'cancelled' => 'danger',
        'expired' => 'secondary',
        'completed' => 'primary',
        default => 'dark',
    };
}

$source = trim(($order['member_bank_name'] ?? '') . ' ' . ($order['member_bank_account_number'] ?? '')) ?: 'Member Bank';
$destination = !empty($order['savings_account_number']) ? 'Savings: ' . $order['savings_account_number'] : (!empty($order['loan_ref_no']) ? 'Loan: ' . $order['loan_ref_no'] : 'Unknown');
$statusLabel = getStatusLabel($order);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standing Order #<?php echo htmlspecialchars($order['standing_order_id']); ?> - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 d-none d-md-block">
                <?php include __DIR__ . '/../includes/sidebar.php'; ?>
            </div>
            <main class="col-md-10 ms-sm-auto px-md-4">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/standing-orders/list.php">Standing Orders</a></li>
                        <li class="breadcrumb-item active" aria-current="page">View Order</li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h1 class="h4 mb-1">Standing Order #<?php echo htmlspecialchars($order['standing_order_id']); ?></h1>
                        <p class="text-muted mb-0">Review order details, execution history and audit metadata.</p>
                    </div>
                    <div>
                        <a href="edit.php?id=<?php echo $order['standing_order_id']; ?>" class="btn btn-primary me-2">Edit</a>
                        <a href="history.php?id=<?php echo $order['standing_order_id']; ?>" class="btn btn-secondary">View History</a>
                    </div>
                </div>

                <div class="row gy-4">
                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Order Information</h5>
                                <dl class="row">
                                    <dt class="col-sm-5">Member</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($order['full_name']); ?> <br><small class="text-muted"><?php echo htmlspecialchars($order['membership_no']); ?></small></dd>

                                    <dt class="col-sm-5">Source Account</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($source); ?></dd>

                                    <dt class="col-sm-5">Destination Account</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($destination); ?></dd>

                                    <dt class="col-sm-5">Amount</dt>
                                    <dd class="col-sm-7"><?php echo formatMoney($order['amount']); ?></dd>

                                    <dt class="col-sm-5">Frequency</dt>
                                    <dd class="col-sm-7"><?php echo ucfirst(str_replace('-', ' ', $order['frequency'])); ?></dd>

                                    <dt class="col-sm-5">Next Run Date</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($order['next_run_date']); ?></dd>

                                    <dt class="col-sm-5">End Date</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($order['end_date'] ?? 'N/A'); ?></dd>

                                    <dt class="col-sm-5">Status</dt>
                                    <dd class="col-sm-7"><span class="badge bg-<?php echo getStatusClass($statusLabel); ?>"><?php echo ucfirst($statusLabel); ?></span></dd>
                                </dl>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-6">
                        <div class="card shadow-sm">
                            <div class="card-body">
                                <h5 class="card-title">Audit Information</h5>
                                <dl class="row">
                                    <dt class="col-sm-5">Created By</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($order['created_by'] ?? 'System'); ?></dd>

                                    <dt class="col-sm-5">Created At</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($order['created_at'] ?? 'N/A'); ?></dd>

                                    <dt class="col-sm-5">Last Run Date</dt>
                                    <dd class="col-sm-7"><?php echo htmlspecialchars($order['last_received_date'] ?? 'N/A'); ?></dd>

                                    <dt class="col-sm-5">Audit Notes</dt>
                                    <dd class="col-sm-7 text-muted">Execution history is available below. Detailed audit logs are not available due to current schema differences.</dd>
                                </dl>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card shadow-sm mt-4">
                    <div class="card-body">
                        <h5 class="card-title">Recent Runs</h5>
                        <?php if (empty($history)): ?>
                            <p class="text-muted">No execution history exists for this standing order.</p>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Run Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Transaction Ref</th>
                                            <th>Error Message</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($history as $run): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($run['run_date']); ?></td>
                                                <td><?php echo formatMoney($run['amount']); ?></td>
                                                <td><span class="badge bg-<?php echo getStatusClass($run['status'] ?? 'processed'); ?>"><?php echo ucfirst($run['status']); ?></span></td>
                                                <td><?php echo htmlspecialchars($run['transaction_reference'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($run['error_message'] ?? '-'); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
