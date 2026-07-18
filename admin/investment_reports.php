<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/InvestmentService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

$service = new \SACCO\Services\InvestmentService();
$stats = $service->getDashboardStats();
$investments = $service->getInvestments([], 200, 0);

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="investments.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Name','Institution','Reference','Currency','Principal','Current Value','Status']);
    foreach ($investments as $row) {
        $currency = normalizeCurrencyCode($row['currency'] ?? 'UGX');
        fputcsv($out, [$row['name'], $row['institution'], $row['reference'], $currency, formatCurrency($row['principal'] ?? 0, $currency), formatCurrency($row['current_value'] ?? 0, $currency), $row['status']]);
    }
    fclose($out);
    exit();
}

if (($_GET['export'] ?? '') === 'pdf') {
    $html = '<table><tr><th>Name</th><th>Institution</th><th>Currency</th><th>Principal</th><th>Status</th></tr>';
    foreach ($investments as $row) {
        $currency = normalizeCurrencyCode($row['currency'] ?? 'UGX');
        $html .= '<tr><td>' . htmlspecialchars($row['name']) . '</td><td>' . htmlspecialchars($row['institution']) . '</td><td>' . htmlspecialchars($currency) . '</td><td>' . htmlspecialchars(formatCurrency($row['principal'] ?? 0, $currency)) . '</td><td>' . htmlspecialchars($row['status']) . '</td></tr>';
    }
    $html .= '</table>';
    $result = generatePDF($html, 'investment_report');
    if ($result['success']) {
        header('Location: ' . $result['download_url']);
        exit();
    }
}

if (($_GET['export'] ?? '') === 'excel') {
    $rows = [];
    foreach ($investments as $row) {
        $currency = normalizeCurrencyCode($row['currency'] ?? 'UGX');
        $rows[] = [$row['name'], $row['institution'], $row['reference'], $currency, formatCurrency($row['principal'] ?? 0, $currency), formatCurrency($row['current_value'] ?? 0, $currency), $row['status']];
    }
    $result = generateExcelFile($rows, ['Name','Institution','Reference','Currency','Principal','Current Value','Status'], 'investment_report');
    if ($result['success']) {
        header('Location: ' . $result['download_url']);
        exit();
    }
}
?>
<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <title>Investment Reports - <?php echo APP_NAME; ?></title>
    <?php include __DIR__ . '/../includes/head_includes.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/nav.php'; ?>
<div class="container mt-4">
    <h3>Investment Reports</h3>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card p-3"><small>Total Investments</small><h4><?php echo $stats['total_investments']; ?></h4></div></div>
        <div class="col-md-3"><div class="card p-3"><small>Total Principal</small><h4><?php echo formatMoney($stats['total_principal']); ?></h4></div></div>
        <div class="col-md-3"><div class="card p-3"><small>Current Portfolio Value</small><h4><?php echo formatMoney($stats['current_portfolio_value']); ?></h4></div></div>
        <div class="col-md-3"><div class="card p-3"><small>ROI %</small><h4><?php echo number_format($stats['roi_pct'], 2); ?>%</h4></div></div>
    </div>
    <div class="mb-3">
        <a href="?export=csv" class="btn btn-outline-primary">Export CSV</a>
        <a href="?export=excel" class="btn btn-outline-success">Export Excel</a>
        <a href="?export=pdf" class="btn btn-outline-secondary">Export PDF</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Name</th><th>Institution</th><th>Reference</th><th>Currency</th><th>Principal</th><th>Current Value</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($investments as $row): ?><?php $currency = normalizeCurrencyCode($row['currency'] ?? 'UGX'); ?><tr><td><?php echo htmlspecialchars($row['name']); ?></td><td><?php echo htmlspecialchars($row['institution']); ?></td><td><?php echo htmlspecialchars($row['reference']); ?></td><td><?php echo htmlspecialchars($currency); ?></td><td><?php echo formatCurrency($row['principal'] ?? 0, $currency); ?></td><td><?php echo formatCurrency($row['current_value'] ?? 0, $currency); ?></td><td><?php echo htmlspecialchars($row['status']); ?></td></tr><?php endforeach; ?></tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
