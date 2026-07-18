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
$typeId = trim($_GET['type_id'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;
if ($search !== '') { $filters['search'] = $search; }
if ($status !== '') { $filters['status'] = $status; }
if ($typeId !== '') { $filters['type_id'] = $typeId; }
$offset = ($page - 1) * $perPage;
$investments = $service->getInvestments($filters, $perPage, $offset);
$investmentTypes = $service->getInvestmentTypes();
$summary = $service->getDashboardStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Investment Portfolio</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="container-fluid py-4">
        <h1 class="h3 mb-1">Investment Portfolio</h1>
        <p class="text-muted mb-4">Review the current portfolio composition and recent investment positions.</p>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card p-3"><small>Portfolio Value</small><h4><?php echo formatMoney($summary['current_portfolio_value']); ?></h4></div></div>
            <div class="col-md-3"><div class="card p-3"><small>Total Principal</small><h4><?php echo formatMoney($summary['total_principal']); ?></h4></div></div>
            <div class="col-md-3"><div class="card p-3"><small>Active Investments</small><h4><?php echo (int)$summary['active_investments']; ?></h4></div></div>
            <div class="col-md-3"><div class="card p-3"><small>Returns</small><h4><?php echo formatMoney($summary['total_returns']); ?></h4></div></div>
        </div>
        <form class="row g-2 mb-3" method="get">
            <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search portfolio" value="<?php echo htmlspecialchars($search); ?>"></div>
            <div class="col-md-3"><select name="status" class="form-select"><option value="">All statuses</option><option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option><option value="matured" <?php echo $status === 'matured' ? 'selected' : ''; ?>>Matured</option><option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option></select></div>
            <div class="col-md-3"><select name="type_id" class="form-select"><option value="">All types</option><?php foreach ($investmentTypes as $type): ?><option value="<?php echo (int)$type['id']; ?>" <?php echo $typeId == $type['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['name']); ?></option><?php endforeach; ?></select></div>
            <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
        </form>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead><tr><th>Name</th><th>Type</th><th>Principal</th><th>Current Value</th><th>ROI</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($investments as $investment): $roi = $service->calculateROI($investment); ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($investment['name']); ?></td>
                                    <td><?php echo htmlspecialchars($investment['type_name'] ?? '-'); ?></td>
                                    <td><?php echo formatMoney($investment['principal']); ?></td>
                                    <td><?php echo formatMoney($investment['current_value']); ?></td>
                                    <td><?php echo number_format($roi['return_pct'], 2); ?>%</td>
                                    <td><span class="badge bg-secondary"><?php echo htmlspecialchars(ucfirst($investment['status'])); ?></span></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <nav class="mt-3"><ul class="pagination"><li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type_id=<?php echo urlencode($typeId); ?>">Previous</a></li><li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type_id=<?php echo urlencode($typeId); ?>">Next</a></li></ul></nav>
    </div>
</body>
</html>
