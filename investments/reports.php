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
$investments = $service->getInvestments($filters, 100, 0);
$stats = $service->getDashboardStats();

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="investment_report.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name','Institution','Reference','Currency','Principal','Current Value','Status']);
    foreach ($investments as $row) {
        $currency = normalizeCurrencyCode($row['currency'] ?? 'UGX');
        fputcsv($out, [$row['name'], $row['institution'] ?? '', $row['reference'] ?? '', $currency, formatCurrency($row['principal'] ?? 0, $currency), formatCurrency($row['current_value'] ?? 0, $currency), $row['status']]);
    }
    fclose($out);
    exit;
}
if (($_GET['export'] ?? '') === 'excel') {
    $rows = [];
    foreach ($investments as $row) {
        $currency = normalizeCurrencyCode($row['currency'] ?? 'UGX');
        $rows[] = [$row['name'], $row['institution'] ?? '', $row['reference'] ?? '', $currency, formatCurrency($row['principal'] ?? 0, $currency), formatCurrency($row['current_value'] ?? 0, $currency), $row['status']];
    }
    $result = generateExcelFile($rows, ['Name','Institution','Reference','Currency','Principal','Current Value','Status'], 'investment_report');
    if ($result['success']) { header('Location: ' . $result['download_url']); exit; }
}
if (($_GET['export'] ?? '') === 'pdf') {
    $html = '<table><tr><th>Name</th><th>Institution</th><th>Currency</th><th>Principal</th><th>Status</th></tr>';
    foreach ($investments as $row) {
        $currency = normalizeCurrencyCode($row['currency'] ?? 'UGX');
        $html .= '<tr><td>' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['institution'] ?? '') . '</td><td>' . htmlspecialchars($currency) . '</td><td>' . htmlspecialchars(formatCurrency($row['principal'] ?? 0, $currency)) . '</td><td>' . htmlspecialchars($row['status']) . '</td></tr>';
    }
    $html .= '</table>';
    $result = generatePDF($html, 'investment_report');
    if ($result['success']) { header('Location: ' . $result['download_url']); exit; }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Investment Reports</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>
    <div class="container-fluid py-4">
        <h1 class="h3 mb-1">Investment Reports</h1>
        <p class="text-muted mb-4">Export portfolio data and review current performance metrics.</p>
        <div class="row g-3 mb-4">
            <div class="col-md-3"><div class="card p-3"><small>Total Investments</small><h4><?php echo (int)$stats['total_investments']; ?></h4></div></div>
            <div class="col-md-3"><div class="card p-3"><small>Principal</small><h4><?php echo formatMoney($stats['total_principal']); ?></h4></div></div>
            <div class="col-md-3"><div class="card p-3"><small>Current Value</small><h4><?php echo formatMoney($stats['current_portfolio_value']); ?></h4></div></div>
            <div class="col-md-3"><div class="card p-3"><small>ROI</small><h4><?php echo number_format($stats['roi_pct'], 2); ?>%</h4></div></div>
            <div class="col-md-3"><div class="card p-3"><small>Expected Interest</small><h4><?php echo formatMoney($stats['expected_interest']); ?></h4></div></div>
            <div class="col-md-3"><div class="card p-3"><small>Interest Received</small><h4><?php echo formatMoney($stats['interest_received']); ?></h4></div></div>
            <div class="col-md-3"><div class="card p-3"><small>Active Investments</small><h4><?php echo (int)$stats['active_investments']; ?></h4></div></div>
            <div class="col-md-3"><div class="card p-3"><small>Near Maturity</small><h4><?php echo (int)$stats['near_maturity_investments']; ?></h4></div></div>
        </div>
        <div class="mb-3">
            <a href="reports.php?export=csv" class="btn btn-outline-primary">Export CSV</a>
            <a href="reports.php?export=excel" class="btn btn-outline-success">Export Excel</a>
            <a href="reports.php?export=pdf" class="btn btn-outline-secondary">Export PDF</a>
        </div>
        <form class="row g-2 mb-3" method="get">
            <div class="col-md-6"><input type="text" name="search" class="form-control" placeholder="Search report data" value="<?php echo htmlspecialchars($search); ?>"></div>
            <div class="col-md-4"><select name="status" class="form-select"><option value="">All statuses</option><option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option><option value="matured" <?php echo $status === 'matured' ? 'selected' : ''; ?>>Matured</option><option value="closed" <?php echo $status === 'closed' ? 'selected' : ''; ?>>Closed</option></select></div>
            <div class="col-md-2"><button class="btn btn-outline-secondary w-100">Filter</button></div>
        </form>
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-striped mb-0">
                        <thead><tr><th>Name</th><th>Institution</th><th>Reference</th><th>Currency</th><th>Principal</th><th>Current Value</th><th>Status</th></tr></thead>
                        <tbody>
                            <?php foreach ($investments as $investment): ?>
                                <?php $currency = normalizeCurrencyCode($investment['currency'] ?? 'UGX'); ?>
                                <tr><td><?php echo htmlspecialchars($investment['name']); ?></td><td><?php echo htmlspecialchars($investment['institution'] ?? '-'); ?></td><td><?php echo htmlspecialchars($investment['reference'] ?? '-'); ?></td><td><?php echo htmlspecialchars($currency); ?></td><td><?php echo formatCurrency($investment['principal'] ?? 0, $currency); ?></td><td><?php echo formatCurrency($investment['current_value'] ?? 0, $currency); ?></td><td><?php echo htmlspecialchars(ucfirst($investment['status'])); ?></td></tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
