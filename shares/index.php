<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/Services/ShareService.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'manager', 'accountant', 'cashier', 'loan_officer']);
$user = $auth->getCurrentUser();
$db = getDB();
$shareService = new \SACCO\Services\ShareService();

$messages = [];
$shareTablesAvailable = true;

try {
    $totalShares = $shareService->getTotalSaccoShares();
    $totalShareholders = $shareService->getTotalShareholders();
    $topShareholders = $shareService->getTopShareholders(10);
    $recentShareTransactions = $shareService->getRecentShareTransactions(15);
} catch (Exception $e) {
    $shareTablesAvailable = false;
    $totalShares = 0;
    $totalShareholders = 0;
    $topShareholders = [];
    $recentShareTransactions = [];
    $messages[] = ['type' => 'warning', 'text' => 'Share module tables are unavailable. Please ensure share migrations are applied.'];
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Dashboard - <?php echo APP_NAME; ?></title>
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
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="h3 mb-1">Share Dashboard</h1>
                    <p class="text-muted mb-0">Central overview of share capital, shareholder holdings, recent activity, and quick actions.</p>
                </div>
            </div>

            <?php foreach ($messages as $msg): ?>
                <div class="alert alert-<?php echo htmlspecialchars($msg['type']); ?>">
                    <?php echo htmlspecialchars($msg['text']); ?>
                </div>
            <?php endforeach; ?>

            <div class="row g-4 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-uppercase text-muted mb-2">Total SACCO Shares</p>
                            <h2 class="display-6 mb-0"><?php echo number_format($totalShares); ?></h2>
                            <p class="text-muted small mb-0">Total shares held by all members.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-uppercase text-muted mb-2">Total Shareholders</p>
                            <h2 class="display-6 mb-0"><?php echo number_format($totalShareholders); ?></h2>
                            <p class="text-muted small mb-0">Active members holding shares.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm h-100">
                        <div class="card-body">
                            <p class="text-uppercase text-muted mb-2">Recent Activity</p>
                            <h2 class="display-6 mb-0"><?php echo number_format(count($recentShareTransactions)); ?></h2>
                            <p class="text-muted small mb-0">Transactions in the latest activity feed.</p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <div class="col-lg-3 col-md-6">
                    <a href="buy.php" class="text-decoration-none">
                        <div class="card border-start border-4 border-warning shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <h6 class="mb-1">Buy Shares</h6>
                                        <p class="text-muted mb-0">Purchase from savings.</p>
                                    </div>
                                    <i class="bi bi-cart-plus fs-2 text-warning"></i>
                                </div>
                                <span class="badge bg-warning text-dark">New</span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <a href="transfer.php" class="text-decoration-none">
                        <div class="card border-start border-4 border-info shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <h6 class="mb-1">Transfer Shares</h6>
                                        <p class="text-muted mb-0">Move shares between members.</p>
                                    </div>
                                    <i class="bi bi-arrow-left-right fs-2 text-info"></i>
                                </div>
                                <span class="badge bg-info text-dark">Action</span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <a href="holdings.php" class="text-decoration-none">
                        <div class="card border-start border-4 border-success shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <h6 class="mb-1">Share Holdings</h6>
                                        <p class="text-muted mb-0">Member holdings list.</p>
                                    </div>
                                    <i class="bi bi-people-fill fs-2 text-success"></i>
                                </div>
                                <span class="badge bg-success">Browse</span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-lg-3 col-md-6">
                    <a href="reports.php" class="text-decoration-none">
                        <div class="card border-start border-4 border-primary shadow-sm h-100">
                            <div class="card-body">
                                <div class="d-flex align-items-center justify-content-between mb-3">
                                    <div>
                                        <h6 class="mb-1">Share Reports</h6>
                                        <p class="text-muted mb-0">Share capital analytics.</p>
                                    </div>
                                    <i class="bi bi-graph-up fs-2 text-primary"></i>
                                </div>
                                <span class="badge bg-primary">Reports</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-xl-8">
                    <div class="card shadow-sm">
                        <div class="card-header bg-secondary text-white">
                            <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i> Recent Share Transactions</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recentShareTransactions)): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover table-sm">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Date</th>
                                                <th>Member</th>
                                                <th>Type</th>
                                                <th>Shares</th>
                                                <th>Amount</th>
                                                <th>Reference</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($recentShareTransactions as $txn): ?>
                                                <tr>
                                                    <td><?php echo date('Y-m-d H:i', strtotime($txn['transaction_date'])); ?></td>
                                                    <td><?php echo htmlspecialchars($txn['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $txn['transaction_type']))); ?></td>
                                                    <td><?php echo htmlspecialchars($txn['shares']); ?></td>
                                                    <td><?php echo formatMoney($txn['amount']); ?></td>
                                                    <td><?php echo htmlspecialchars($txn['reference_number']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No share transaction history available yet.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-xl-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-trophy me-2"></i> Top Shareholders</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($topShareholders)): ?>
                                <ul class="list-group list-group-flush">
                                    <?php foreach ($topShareholders as $holder): ?>
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong><?php echo htmlspecialchars($holder['full_name']); ?></strong><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($holder['membership_no']); ?></small>
                                            </div>
                                            <span class="badge bg-primary rounded-pill"><?php echo number_format($holder['shares_owned']); ?> shares</span>
                                        </li>
                                    <?php endforeach; ?>
                                </ul>
                            <?php else: ?>
                                <p class="text-muted mb-0">No shareholder data available.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="card shadow-sm mt-4">
                        <div class="card-header bg-dark text-white">
                            <h5 class="mb-0"><i class="bi bi-info-circle me-2"></i> Notes</h5>
                        </div>
                        <div class="card-body">
                            <p class="text-muted mb-3">Use dedicated Shares pages to manage purchases, transfers, holdings, reports and adjustments in a consistent workflow.</p>
                            <a href="history.php" class="btn btn-outline-dark btn-sm">Open Share History</a>
                        </div>
                    </div>
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
