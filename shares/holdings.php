<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/Services/ShareService.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'manager', 'accountant', 'cashier', 'loan_officer']);
$shareService = new \SACCO\Services\ShareService();

$search = trim($_GET['q'] ?? '');
$holdings = $shareService->getShareHoldings($search, 200, 0);
$totalShareholders = $shareService->getTotalShareholders();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Share Holdings - <?php echo APP_NAME; ?></title>
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
                    <li class="breadcrumb-item active" aria-current="page">Share Holdings</li>
                </ol>
            </nav>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-1">Shareholders</h5>
                            <p class="text-muted mb-0"><?php echo number_format($totalShareholders); ?> members own shares.</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <h5 class="mb-1">Search Holdings</h5>
                            <form method="get" class="d-flex gap-2">
                                <input type="search" name="q" class="form-control" placeholder="Search member or membership number" value="<?php echo htmlspecialchars($search); ?>">
                                <button type="submit" class="btn btn-primary">Search</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0"><i class="bi bi-people-fill me-2"></i> Share Holdings</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($holdings)): ?>
                        <div class="table-responsive">
                            <table class="table table-hover table-sm align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Membership No</th>
                                        <th>Member</th>
                                        <th>Shares Owned</th>
                                        <th>Total Invested</th>
                                        <th>Share Value</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($holdings as $holding): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($holding['membership_no']); ?></td>
                                            <td><?php echo htmlspecialchars($holding['full_name']); ?></td>
                                            <td><?php echo number_format($holding['shares_owned']); ?></td>
                                            <td><?php echo formatMoney($holding['total_invested']); ?></td>
                                            <td><?php echo formatMoney($holding['total_value']); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <p class="text-muted mb-0">No share holdings found. Search or add share purchases to build holdings.</p>
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
