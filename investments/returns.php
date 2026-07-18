<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/InvestmentService.php';

$auth->requireLogin();

$service = new \SACCO\Services\InvestmentService();
$filters = [];
$search = trim($_GET['search'] ?? '');
$status = trim($_GET['status'] ?? '');
if ($search !== '') { $filters['search'] = $search; }
if ($status !== '') { $filters['status'] = $status; }
$investments = $service->getInvestments($filters, 50, 0);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Investment Returns</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="container-fluid py-4">
        <h1 class="h3 mb-1">Investment Returns</h1>
        <p class="text-muted mb-4">Review realized and unrealized returns using current portfolio values.</p>
        <form class="row g-2 mb-3" method="get">
            <div class="col-md-6"><input type="text" name="search" class="form-control" placeholder="Search investments" value="<?php echo htmlspecialchars($search); ?>"></div>
            <div class="col-md-4"><select name="status" class="form-select"><option value="">All statuses</option><option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option><option value="matured" <?php echo $status === 'matured' ? 'selected' : ''; ?>>Matured</option><option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option></select></div>
            <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
        </form>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead><tr><th>Name</th><th>Principal</th><th>Current Value</th><th>Lifetime Return</th><th>ROI</th><th>Annualized ROI</th></tr></thead>
                        <tbody>
                            <?php foreach ($investments as $investment): $roi = $service->calculateROI($investment); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($investment['name']); ?></td>
                                    <td><?php echo formatMoney($roi['principal']); ?></td>
                                    <td><?php echo formatMoney($roi['current_value']); ?></td>
                                    <td><?php echo formatMoney($roi['lifetime_return']); ?></td>
                                    <td><?php echo number_format($roi['return_pct'], 2); ?>%</td>
                                    <td><?php echo number_format($roi['annualized_pct'], 2); ?>%</td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
