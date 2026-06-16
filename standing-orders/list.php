<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/Services/StandingOrderService.php';

$auth->requireLogin();
$service = new \SACCO\Services\StandingOrderService();

$page = max(1, (int)($_GET['page'] ?? 1));
$search = trim($_GET['search'] ?? '');
$status = $_GET['status'] ?? '';
$offset = ($page - 1) * ITEMS_PER_PAGE;

$total = $service->countStandingOrders($search, $status);
$totalPages = max(1, ceil($total / ITEMS_PER_PAGE));
$orders = $service->getAllStandingOrders(ITEMS_PER_PAGE, $offset, $search, $status);

function getStandingOrderStatusLabel($order) {
    $today = date('Y-m-d');
    if (!empty($order['end_date']) && $order['end_date'] < $today) {
        return 'expired';
    }

    if (isset($order['status'])) {
        return $order['status'];
    }

    return !empty($order['is_active']) ? 'active' : 'suspended';
}

function getStandingOrderStatusClass($status) {
    return match ($status) {
        'active' => 'success',
        'suspended' => 'warning',
        'cancelled' => 'danger',
        'expired' => 'secondary',
        'completed' => 'primary',
        default => 'dark',
    };
}

function getStandingOrderSource($order) {
    $bankName = trim($order['member_bank_name'] ?? $order['bank_name'] ?? '');
    $accountNumber = trim($order['member_bank_account_number'] ?? $order['bank_account_number'] ?? '');
    if ($bankName || $accountNumber) {
        return trim("{$bankName} {$accountNumber}");
    }
    return 'Member Bank';
}

function getStandingOrderDestination($order) {
    if (!empty($order['savings_account_number'])) {
        $type = !empty($order['savings_account_type']) ? ucfirst(str_replace('_', ' ', $order['savings_account_type'])) : 'Savings';
        return "{$type} / {$order['savings_account_number']}";
    }
    if (!empty($order['loan_ref_no'])) {
        return "Loan / {$order['loan_ref_no']}";
    }
    return 'Unknown';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standing Orders - <?php echo APP_NAME; ?></title>
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
                        <li class="breadcrumb-item active" aria-current="page">Standing Orders</li>
                    </ol>
                </nav>

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h1 class="h4 mb-0">Standing Orders</h1>
                        <p class="text-muted mb-0">Recurring payment schedule and status management.</p>
                    </div>
                    <a href="<?php echo APP_URL; ?>/standing-orders/create.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg"></i> Create New
                    </a>
                </div>

                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <?php if (!empty($_SESSION['flash_success'])): ?>
                            <div class="alert alert-success alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($_SESSION['flash_success']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['flash_success']); ?>
                        <?php endif; ?>
                        <?php if (!empty($_SESSION['flash_error'])): ?>
                            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                                <?php echo htmlspecialchars($_SESSION['flash_error']); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                            </div>
                            <?php unset($_SESSION['flash_error']); ?>
                        <?php endif; ?>
                        <form class="row g-2" method="get" action="list.php">
                            <div class="col-md-4">
                                <input type="search" name="search" class="form-control" placeholder="Search member, membership or order ID..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-md-3">
                                <select name="status" class="form-select">
                                    <option value=""<?php echo $status === '' ? ' selected' : ''; ?>>All Statuses</option>
                                    <option value="active"<?php echo $status === 'active' ? ' selected' : ''; ?>>Active</option>
                                    <option value="suspended"<?php echo $status === 'suspended' ? ' selected' : ''; ?>>Suspended</option>
                                    <option value="expired"<?php echo $status === 'expired' ? ' selected' : ''; ?>>Expired</option>
                                    <option value="cancelled"<?php echo $status === 'cancelled' ? ' selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <button type="submit" class="btn btn-outline-primary w-100">Filter</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th scope="col">Order ID</th>
                                        <th scope="col">Member</th>
                                        <th scope="col">Source Account</th>
                                        <th scope="col">Destination Account</th>
                                        <th scope="col">Amount</th>
                                        <th scope="col">Frequency</th>
                                        <th scope="col">Next Run Date</th>
                                        <th scope="col">Status</th>
                                        <th scope="col">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($orders)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center py-4">No standing orders found.</td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($orders as $order): ?>
                                        <?php $statusLabel = getStandingOrderStatusLabel($order); ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($order['standing_order_id']); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($order['full_name']); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($order['membership_no']); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars(getStandingOrderSource($order)); ?></td>
                                            <td><?php echo htmlspecialchars(getStandingOrderDestination($order)); ?></td>
                                            <td><?php echo formatMoney($order['amount']); ?></td>
                                            <td><?php echo ucfirst(str_replace('-', ' ', $order['frequency'])); ?></td>
                                            <td><?php echo htmlspecialchars($order['next_run_date'] ?? 'N/A'); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo getStandingOrderStatusClass($statusLabel); ?>">
                                                    <?php echo ucfirst($statusLabel); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <a href="view.php?id=<?php echo $order['standing_order_id']; ?>" class="btn btn-sm btn-outline-secondary mb-1">View</a>
                                                <a href="edit.php?id=<?php echo $order['standing_order_id']; ?>" class="btn btn-sm btn-outline-primary mb-1">Edit</a>

                                                <?php if ($statusLabel === 'active'): ?>
                                                    <form method="post" action="action.php" class="d-inline">
                                                        <input type="hidden" name="standing_order_id" value="<?php echo $order['standing_order_id']; ?>">
                                                        <input type="hidden" name="action" value="suspend">
                                                        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-warning mb-1">Suspend</button>
                                                    </form>
                                                    <form method="post" action="action.php" class="d-inline">
                                                        <input type="hidden" name="standing_order_id" value="<?php echo $order['standing_order_id']; ?>">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger mb-1">Cancel</button>
                                                    </form>
                                                <?php elseif ($statusLabel === 'suspended'): ?>
                                                    <form method="post" action="action.php" class="d-inline">
                                                        <input type="hidden" name="standing_order_id" value="<?php echo $order['standing_order_id']; ?>">
                                                        <input type="hidden" name="action" value="resume">
                                                        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-success mb-1">Resume</button>
                                                    </form>
                                                    <form method="post" action="action.php" class="d-inline">
                                                        <input type="hidden" name="standing_order_id" value="<?php echo $order['standing_order_id']; ?>">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <input type="hidden" name="return_url" value="<?php echo htmlspecialchars($_SERVER['REQUEST_URI']); ?>">
                                                        <button type="submit" class="btn btn-sm btn-danger mb-1">Cancel</button>
                                                    </form>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Standing orders pagination">
                                <ul class="pagination justify-content-center mt-3">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>&search=<?php echo urlencode($search); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
