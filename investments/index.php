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
$stats = $service->getDashboardStats();
$maturityAlerts = $service->getMaturityAlerts();
$investmentTypes = $service->getInvestmentTypes();

if (isset($_GET['action']) && $_GET['action'] === 'cancel' && !empty($_GET['id'])) {
    $service->deleteInvestment((int)$_GET['id'], $_SESSION['user_id'] ?? null);
    $_SESSION['flash_success'] = 'Investment cancelled.';
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Investments</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="main-content">
        <div class="container-fluid py-4">
            <div class="page-header">
                <div>
                    <nav aria-label="breadcrumb">
                        <ol class="breadcrumb">
                            <li class="breadcrumb-item"><a href="../dashboard.php">Home</a></li>
                            <li class="breadcrumb-item active" aria-current="page">Investments</li>
                        </ol>
                    </nav>
                    <h1 class="page-title">Investment Dashboard</h1>
                    <p class="page-subtitle">Track investment performance, portfolio value, and recent activity.</p>
                </div>
                <a href="add.php" class="btn btn-primary"><i class="bi bi-plus-lg me-2"></i>Add Investment</a>
            </div>
            <?php if (!empty($_SESSION['flash_success'])): ?><div class="alert alert-success"><?php echo htmlspecialchars($_SESSION['flash_success']); ?></div><?php unset($_SESSION['flash_success']); endif; ?>
            <?php if (!empty($_SESSION['flash_error'])): ?><div class="alert alert-danger"><?php echo htmlspecialchars($_SESSION['flash_error']); ?></div><?php unset($_SESSION['flash_error']); endif; ?>
            <div class="row g-3 mb-4">
                <div class="col-md-3"><div class="card dashboard-card p-3"><div class="d-flex align-items-center justify-content-between"><div><small class="text-muted">Total Investments</small><h4 class="mb-0"><?php echo (int)$stats['total_investments']; ?></h4></div><div class="rounded-circle bg-primary-subtle p-3 text-primary"><i class="bi bi-bar-chart-line-fill"></i></div></div></div></div>
                <div class="col-md-3"><div class="card dashboard-card p-3"><div class="d-flex align-items-center justify-content-between"><div><small class="text-muted">Total Principal</small><h4 class="mb-0"><?php echo formatMoney($stats['total_principal']); ?></h4></div><div class="rounded-circle bg-success-subtle p-3 text-success"><i class="bi bi-cash-stack"></i></div></div></div></div>
                <div class="col-md-3"><div class="card dashboard-card p-3"><div class="d-flex align-items-center justify-content-between"><div><small class="text-muted">Portfolio Value</small><h4 class="mb-0"><?php echo formatMoney($stats['current_portfolio_value']); ?></h4></div><div class="rounded-circle bg-info-subtle p-3 text-info"><i class="bi bi-pie-chart-fill"></i></div></div></div></div>
                <div class="col-md-3"><div class="card dashboard-card p-3"><div class="d-flex align-items-center justify-content-between"><div><small class="text-muted">ROI</small><h4 class="mb-0"><?php echo number_format($stats['roi_pct'], 2); ?>%</h4></div><div class="rounded-circle bg-warning-subtle p-3 text-warning"><i class="bi bi-graph-up-arrow"></i></div></div></div></div>
                <div class="col-md-3"><div class="card dashboard-card p-3"><div class="d-flex align-items-center justify-content-between"><div><small class="text-muted">Expected Interest</small><h4 class="mb-0"><?php echo formatMoney($stats['expected_interest']); ?></h4></div><div class="rounded-circle bg-secondary-subtle p-3 text-secondary"><i class="bi bi-award-fill"></i></div></div></div></div>
                <div class="col-md-3"><div class="card dashboard-card p-3"><div class="d-flex align-items-center justify-content-between"><div><small class="text-muted">Interest Received</small><h4 class="mb-0"><?php echo formatMoney($stats['interest_received']); ?></h4></div><div class="rounded-circle bg-danger-subtle p-3 text-danger"><i class="bi bi-currency-dollar"></i></div></div></div></div>
                <div class="col-md-3"><div class="card dashboard-card p-3"><div class="d-flex align-items-center justify-content-between"><div><small class="text-muted">Near Maturity</small><h4 class="mb-0"><?php echo (int)$stats['near_maturity_investments']; ?></h4></div><div class="rounded-circle bg-dark-subtle p-3 text-dark"><i class="bi bi-calendar-week"></i></div></div></div></div>
                <div class="col-md-3"><div class="card dashboard-card p-3"><div class="d-flex align-items-center justify-content-between"><div><small class="text-muted">Active</small><h4 class="mb-0"><?php echo (int)$stats['active_investments']; ?></h4></div><div class="rounded-circle bg-success-subtle p-3 text-success"><i class="bi bi-check-circle-fill"></i></div></div></div></div>
            </div>
            <?php if (!empty($maturityAlerts)): ?>
            <div class="card mb-3">
                <div class="card-header">Upcoming Maturity Alerts</div>
                <div class="card-body">
                    <ul class="mb-0">
                        <?php foreach (array_slice($maturityAlerts, 0, 5) as $alert): ?>
                            <li><?php echo htmlspecialchars($alert['name']); ?> — matures on <?php echo htmlspecialchars($alert['maturity_date']); ?> (in <?php echo (int)$alert['days']; ?> days)</li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
            <form class="card mb-3 p-3 row g-2 align-items-end" method="get">
                <div class="col-md-4"><label class="form-label fw-semibold">Search</label><input type="text" name="search" class="form-control" placeholder="Search investments" value="<?php echo htmlspecialchars($search); ?>"></div>
                <div class="col-md-3"><label class="form-label fw-semibold">Status</label><select name="status" class="form-select"><option value="">All statuses</option><option value="draft" <?php echo $status === 'draft' ? 'selected' : ''; ?>>Draft</option><option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option><option value="matured" <?php echo $status === 'matured' ? 'selected' : ''; ?>>Matured</option><option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option><option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option></select></div>
                <div class="col-md-3"><label class="form-label fw-semibold">Type</label><select name="type_id" class="form-select"><option value="">All types</option><?php foreach ($investmentTypes as $type): ?><option value="<?php echo (int)$type['id']; ?>" <?php echo $typeId == $type['id'] ? 'selected' : ''; ?>><?php echo htmlspecialchars($type['name']); ?></option><?php endforeach; ?></select></div>
                <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
            </form>
            <div class="card">
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead><tr><th>Name</th><th>Institution</th><th>Principal</th><th>Current Value</th><th>Status</th><th>Actions</th></tr></thead>
                            <tbody>
                                <?php foreach ($investments as $investment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($investment['name']); ?></td>
                                        <td><?php echo htmlspecialchars($investment['institution'] ?? '-'); ?></td>
                                        <td><?php echo formatMoney($investment['principal']); ?></td>
                                        <td><?php echo formatMoney($investment['current_value']); ?></td>
                                        <td><span class="badge <?php echo $investment['status'] === 'active' ? 'badge-active' : ($investment['status'] === 'matured' ? 'badge-pending' : 'badge-inactive'); ?>"><?php echo htmlspecialchars(ucfirst($investment['status'])); ?></span></td>
                                        <td>
                                            <a href="add.php?edit_id=<?php echo (int)$investment['id']; ?>" class="btn btn-sm btn-outline-primary">Edit</a>
                                            <a href="index.php?action=cancel&id=<?php echo (int)$investment['id']; ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Cancel this investment?');">Cancel</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <nav class="mt-3"><ul class="pagination justify-content-center">
                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>"><a class="page-link" href="?page=<?php echo max(1, $page - 1); ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type_id=<?php echo urlencode($typeId); ?>">Previous</a></li>
                <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&type_id=<?php echo urlencode($typeId); ?>">Next</a></li>
            </ul></nav>
        </div>
    </div>
</body>
</html>
