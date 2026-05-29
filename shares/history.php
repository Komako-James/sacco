<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/Services/ShareService.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'manager', 'accountant', 'cashier', 'loan_officer']);
$shareService = new \SACCO\Services\ShareService();

$filters = [];
$filters['membership_no'] = trim($_GET['membership_no'] ?? '');
$filters['transaction_type'] = trim($_GET['transaction_type'] ?? '');
$filters['start_date'] = trim($_GET['start_date'] ?? '');
$filters['end_date'] = trim($_GET['end_date'] ?? '');

$history = $shareService->getShareTransactionHistory($filters, 200, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Transaction History - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid py-4">
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                    <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/shares/index.php">Shares</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Transaction History</li>
                </ol>
            </nav>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i> Filter Share Transactions</h5>
                </div>
                <div class="card-body">
                    <form method="get" class="row g-3">
                        <div class="col-md-3">
                            <label for="membership_no" class="form-label">Membership No / Member</label>
                            <input type="text" class="form-control" id="membership_no" name="membership_no" value="<?php echo htmlspecialchars($filters['membership_no']); ?>">
                        </div>
                        <div class="col-md-3">
                            <label for="transaction_type" class="form-label">Transaction Type</label>
                            <select id="transaction_type" name="transaction_type" class="form-select">
                                <option value="">All Types</option>
                                <option value="purchase" <?php echo $filters['transaction_type'] === 'purchase' ? 'selected' : ''; ?>>Purchase</option>
                                <option value="transfer_in" <?php echo $filters['transaction_type'] === 'transfer_in' ? 'selected' : ''; ?>>Transfer In</option>
                                <option value="transfer_out" <?php echo $filters['transaction_type'] === 'transfer_out' ? 'selected' : ''; ?>>Transfer Out</option>
                                <option value="adjustment" <?php echo $filters['transaction_type'] === 'adjustment' ? 'selected' : ''; ?>>Adjustment</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="start_date" class="form-label">Start Date</label>
                            <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($filters['start_date']); ?>">
                        </div>
                        <div class="col-md-2">
                            <label for="end_date" class="form-label">End Date</label>
                            <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($filters['end_date']); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-table me-2"></i> Share Transaction History</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($history)): ?>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Date</th>
                                        <th>Member</th>
                                        <th>Type</th>
                                        <th>Shares</th>
                                        <th>Amount</th>
                                        <th>Related</th>
                                        <th>Reference</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($history as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['transaction_date']); ?></td>
                                            <td><?php echo htmlspecialchars($row['membership_no'] . ' - ' . $row['member_name']); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $row['transaction_type']))); ?></td>
                                            <td><?php echo number_format($row['shares']); ?></td>
                                            <td><?php echo formatMoney($row['amount']); ?></td>
                                            <td><?php echo htmlspecialchars($row['related_member_name'] ?? '-'); ?></td>
                                            <td><?php echo htmlspecialchars($row['reference_number'] ?? $row['description'] ?? '-'); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No share transactions match the current filter criteria.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            document.getElementById('sidebar').classList.toggle('collapsed');
        });
    </script>
</body>
</html>
