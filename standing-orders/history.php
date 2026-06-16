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

function getStatusClass($status) {
    return match ($status) {
        'processed' => 'success',
        'failed' => 'danger',
        'pending' => 'warning',
        default => 'secondary',
    };
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standing Order History - <?php echo APP_NAME; ?></title>
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
                        <li class="breadcrumb-item active" aria-current="page">Execution History</li>
                    </ol>
                </nav>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h1 class="h4 mb-1">Execution History</h1>
                                <p class="text-muted mb-0">Historical runs for standing order #<?php echo htmlspecialchars($order['standing_order_id']); ?>.</p>
                            </div>
                            <a href="view.php?id=<?php echo $order['standing_order_id']; ?>" class="btn btn-secondary">Back to Order</a>
                        </div>

                        <?php if (empty($history)): ?>
                            <div class="alert alert-info">No history records found for this standing order.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-striped align-middle">
                                    <thead>
                                        <tr>
                                            <th>Run Date</th>
                                            <th>Amount</th>
                                            <th>Status</th>
                                            <th>Transaction Reference</th>
                                            <th>Error Message</th>
                                            <th>Processed At</th>
                                            <th>Processed By</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($history as $run): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($run['run_date']); ?></td>
                                                <td><?php echo formatMoney($run['amount']); ?></td>
                                                <td><span class="badge bg-<?php echo getStatusClass($run['status']); ?>"><?php echo ucfirst($run['status']); ?></span></td>
                                                <td><?php echo htmlspecialchars($run['transaction_reference'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($run['error_message'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($run['processed_at'] ?? '-'); ?></td>
                                                <td><?php echo htmlspecialchars($run['processed_by'] ?? '-'); ?></td>
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
