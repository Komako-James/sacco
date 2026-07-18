<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/DividendService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

$service = new \SACCO\Services\DividendService();
$stats = $service->getDashboardStats();
$db = \Database::getInstance()->getConnection();
$payments = $db->query('SELECT dp.*, dd.name AS declaration_name, m.full_name FROM dividend_payments dp JOIN dividend_declarations dd ON dp.declaration_id = dd.id LEFT JOIN members m ON dp.member_id = m.member_id ORDER BY dp.id DESC LIMIT 200')->fetchAll(PDO::FETCH_ASSOC);

if (($_GET['export'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="dividends.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['Member','Declaration','Shares','Gross Dividend','Tax','Net Dividend','Status']);
    foreach ($payments as $row) {
        fputcsv($out, [$row['full_name'] ?? $row['member_id'], $row['declaration_name'], $row['shares'], $row['gross_dividend'], $row['tax'], $row['net_dividend'], $row['status']]);
    }
    fclose($out);
    exit();
}

if (($_GET['export'] ?? '') === 'pdf') {
    $html = '<table><tr><th>Member</th><th>Declaration</th><th>Net Dividend</th><th>Status</th></tr>';
    foreach ($payments as $row) {
        $html .= '<tr><td>' . htmlspecialchars($row['full_name'] ?? $row['member_id']) . '</td><td>' . htmlspecialchars($row['declaration_name']) . '</td><td>' . htmlspecialchars($row['net_dividend']) . '</td><td>' . htmlspecialchars($row['status']) . '</td></tr>';
    }
    $html .= '</table>';
    $result = generatePDF($html, 'dividend_report');
    if ($result['success']) {
        header('Location: ' . $result['download_url']);
        exit();
    }
}

if (($_GET['export'] ?? '') === 'excel') {
    $rows = [];
    foreach ($payments as $row) {
        $rows[] = [$row['full_name'] ?? $row['member_id'], $row['declaration_name'], $row['shares'], $row['gross_dividend'], $row['tax'], $row['net_dividend'], $row['status']];
    }
    $result = generateExcelFile($rows, ['Member','Declaration','Shares','Gross Dividend','Tax','Net Dividend','Status'], 'dividend_report');
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
    <title>Dividend Reports - <?php echo APP_NAME; ?></title>
    <?php include __DIR__ . '/../includes/head_includes.php'; ?>
</head>
<body>
<?php include __DIR__ . '/../includes/nav.php'; ?>
<div class="container mt-4">
    <h3>Dividend Reports</h3>
    <div class="row g-3 mb-3">
        <div class="col-md-3"><div class="card p-3"><small>Declared Amount</small><h4><?php echo formatMoney($stats['declared_amount']); ?></h4></div></div>
        <div class="col-md-3"><div class="card p-3"><small>Paid Amount</small><h4><?php echo formatMoney($stats['paid_amount']); ?></h4></div></div>
        <div class="col-md-3"><div class="card p-3"><small>Pending Amount</small><h4><?php echo formatMoney($stats['pending_amount']); ?></h4></div></div>
        <div class="col-md-3"><div class="card p-3"><small>Total Payments</small><h4><?php echo $stats['total_payments']; ?></h4></div></div>
    </div>
    <div class="mb-3">
        <a href="?export=csv" class="btn btn-outline-primary">Export CSV</a>
        <a href="?export=excel" class="btn btn-outline-success">Export Excel</a>
        <a href="?export=pdf" class="btn btn-outline-secondary">Export PDF</a>
    </div>
    <div class="table-responsive">
        <table class="table table-striped">
            <thead><tr><th>Member</th><th>Declaration</th><th>Shares</th><th>Gross</th><th>Tax</th><th>Net</th><th>Status</th></tr></thead>
            <tbody><?php foreach ($payments as $row): ?><tr><td><?php echo htmlspecialchars($row['full_name'] ?? $row['member_id']); ?></td><td><?php echo htmlspecialchars($row['declaration_name']); ?></td><td><?php echo htmlspecialchars($row['shares']); ?></td><td><?php echo formatMoney($row['gross_dividend']); ?></td><td><?php echo formatMoney($row['tax']); ?></td><td><?php echo formatMoney($row['net_dividend']); ?></td><td><?php echo htmlspecialchars($row['status']); ?></td></tr><?php endforeach; ?></tbody>
        </table>
    </div>
</div>
<?php include __DIR__ . '/../includes/footer.php'; ?>
</body>
</html>
