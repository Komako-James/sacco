<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/InvestmentService.php';
require_once __DIR__ . '/../app/Services/DividendService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

$investmentService = new \SACCO\Services\InvestmentService();
$dividendService = new \SACCO\Services\DividendService();
$investmentStats = $investmentService->getDashboardStats();
$dividendStats = $dividendService->getDashboardStats();
$upcoming = $investmentService->getMaturitySummary();
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Investment & Dividend Dashboard - <?php echo APP_NAME; ?></title>
    <?php include __DIR__ . '/../includes/head_includes.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/nav.php'; ?>
<div class="container mt-4">
    <h3>Investment & Dividend Dashboard</h3>
    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card p-3"><small>Total Investments</small><h4><?php echo $investmentStats['total_investments']; ?></h4></div></div>
        <div class="col-md-3"><div class="card p-3"><small>Current Portfolio Value</small><h4><?php echo formatMoney($investmentStats['current_portfolio_value']); ?></h4></div></div>
        <div class="col-md-3"><div class="card p-3"><small>Declared Amount</small><h4><?php echo formatMoney($dividendStats['declared_amount']); ?></h4></div></div>
        <div class="col-md-3"><div class="card p-3"><small>Paid Amount</small><h4><?php echo formatMoney($dividendStats['paid_amount']); ?></h4></div></div>
    </div>
    <div class="row g-3">
        <div class="col-md-6">
            <div class="card p-3">
                <h5>Quick Links</h5>
                <ul class="list-group list-group-flush">
                    <li class="list-group-item"><a href="investments.php">Manage Investments</a></li>
                    <li class="list-group-item"><a href="dividends.php">Manage Dividends</a></li>
                    <li class="list-group-item"><a href="investment_reports.php">Investment Reports</a></li>
                    <li class="list-group-item"><a href="dividend_reports.php">Dividend Reports</a></li>
                </ul>
            </div>
        </div>
        <div class="col-md-6">
            <div class="card p-3">
                <h5>Upcoming Maturity</h5>
                <?php if (empty($upcoming)): ?><p class="text-muted">No investments due soon.</p><?php else: ?><ul><?php foreach ($upcoming as $row): ?><li><?php echo htmlspecialchars($row['name']); ?> — <?php echo htmlspecialchars($row['maturity_date']); ?></li><?php endforeach; ?></ul><?php endif; ?>
            </div>
        </div>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
