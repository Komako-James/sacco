<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/Services/ShareService.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'manager', 'accountant']);
$shareService = new \SACCO\Services\ShareService();

$year = (int) ($_GET['year'] ?? date('Y'));
$year = $year >= 2000 ? $year : date('Y');

$report = $shareService->getMonthlyShareSummary($year);
$totalShares = $shareService->getTotalSaccoShares();
$totalShareholders = $shareService->getTotalShareholders();
$topShareholders = $shareService->getTopShareholders(25);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Reports - <?php echo APP_NAME; ?></title>
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
                    <li class="breadcrumb-item active" aria-current="page">Share Reports</li>
                </ol>
            </nav>

            <div class="row g-3 mb-4">
                <div class="col-md-4">
                    <div class="card shadow-sm border-start border-success border-4">
                        <div class="card-body">
                            <h6>Total Shares</h6>
                            <p class="display-6 mb-0"><?php echo number_format($totalShares); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-start border-primary border-4">
                        <div class="card-body">
                            <h6>Total Shareholders</h6>
                            <p class="display-6 mb-0"><?php echo number_format($totalShareholders); ?></p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card shadow-sm border-start border-warning border-4">
                        <div class="card-body">
                            <h6>Report Year</h6>
                            <p class="display-6 mb-0"><?php echo htmlspecialchars($year); ?></p>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm mb-4">
                <div class="card-header bg-dark text-white d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="bi bi-bar-chart-line-fill me-2"></i> Monthly Share Movement</h5>
                    <form method="get" class="d-flex gap-2 align-items-center mb-0">
                        <label class="text-white mb-0">Year</label>
                        <input type="number" class="form-control form-control-sm" name="year" value="<?php echo htmlspecialchars($year); ?>" min="2000" max="2100" style="width:100px;">
                        <button type="submit" class="btn btn-sm btn-light">Update</button>
                    </form>
                </div>
                <div class="card-body">
                    <?php if (!empty($report)): ?>
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Month</th>
                                        <th>Shares Issued</th>
                                        <th>Shares Redeemed</th>
                                        <th>Net Change</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($report as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(date('F', mktime(0, 0, 0, $row['month'], 1))); ?></td>
                                            <td><?php echo number_format($row['purchased_amount'] ?? 0); ?></td>
                                            <td><?php echo number_format($row['transfer_out_amount'] ?? 0); ?></td>
                                            <td><?php echo number_format(($row['purchased_amount'] ?? 0) - ($row['transfer_out_amount'] ?? 0)); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No monthly share movement data available for this year.</p>
                    <?php endif; ?>
                </div>
            </div>

            <div class="row">
                <div class="col-lg-7 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-trophy me-2"></i> Top Shareholders</h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($topShareholders)): ?>
                                <div class="table-responsive">
                                    <table class="table table-sm table-striped align-middle">
                                        <thead class="table-light">
                                            <tr>
                                                <th>Member</th>
                                                <th>Membership</th>
                                                <th>Shares</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($topShareholders as $leader): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($leader['full_name']); ?></td>
                                                    <td><?php echo htmlspecialchars($leader['membership_no']); ?></td>
                                                    <td><?php echo number_format($leader['shares_owned']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <p class="text-muted mb-0">No data found for top shareholders.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5 mb-4">
                    <div class="card shadow-sm">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-file-earmark-spreadsheet me-2"></i> Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="list-group list-group-flush">
                                <a href="buy.php" class="list-group-item list-group-item-action">Buy Shares</a>
                                <a href="transfer.php" class="list-group-item list-group-item-action">Transfer Shares</a>
                                <a href="statement.php" class="list-group-item list-group-item-action">Member Share Statement</a>
                                <a href="history.php" class="list-group-item list-group-item-action">Share Transaction History</a>
                            </div>
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
