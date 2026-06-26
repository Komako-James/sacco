<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';
require_once '../config/db_connection.php';
require_once '../app/Services/LedgerService.php';

use SACCO\Services\LedgerService;

$auth->requireLogin();
$auth->requirePermission('accounting.view');

$asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
$format = $_GET['format'] ?? 'html';
$error = null;
$reportResult = [];

try {
    $reportResult = LedgerService::generateBalanceSheet($asOfDate);
} catch (Exception $e) {
    $error = $e->getMessage();
}

// Normalize keys returned by LedgerService (legacy templates expect 'ledger_code'/'ledger_name')
$normalize = function ($items) {
    if (!is_array($items)) {
        return [];
    }

    $normalized = [];
    foreach ($items as $it) {
        if (is_array($it)) {
            if (isset($it['account_code']) && !isset($it['ledger_code'])) {
                $it['ledger_code'] = $it['account_code'];
            }
            if (isset($it['account_name']) && !isset($it['ledger_name'])) {
                $it['ledger_name'] = $it['account_name'];
            }
            if (isset($it['balance'])) {
                $it['balance'] = (float) $it['balance'];
            }
            $normalized[] = $it;
        }
    }

    return $normalized;
};

$reportResult['assets'] = $normalize($reportResult['assets'] ?? []);
$reportResult['liabilities'] = $normalize($reportResult['liabilities'] ?? []);
$reportResult['equity'] = $normalize($reportResult['equity'] ?? []);

if ($format === 'csv' && empty($error)) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="balance_sheet_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Category', 'Ledger Code', 'Account', 'Balance', 'As Of Date']);
    foreach (['assets' => 'Asset', 'liabilities' => 'Liability', 'equity' => 'Equity'] as $section => $label) {
        foreach ($reportResult[$section] as $row) {
            fputcsv($output, [
                $label,
                $row['ledger_code'],
                $row['ledger_name'],
                number_format($row['balance'], 2, '.', ''),
                $asOfDate
            ]);
        }
    }
    fclose($output);
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Sheet - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 d-none d-md-block">
                <?php include '../includes/sidebar.php'; ?>
            </div>
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <div>
                        <h1 class="h4 mb-0">Balance Sheet</h1>
                        <small class="text-muted">As of <?php echo htmlspecialchars($asOfDate); ?></small>
                    </div>
                    <div class="btn-group" role="group">
                        <a href="reports.php" class="btn btn-outline-secondary">Back to Reports</a>
                        <a href="?as_of_date=<?php echo urlencode($asOfDate); ?>&format=csv" class="btn btn-outline-primary">Export CSV</a>
                        <button type="button" class="btn btn-outline-secondary" onclick="window.print();">Print</button>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <form method="get" class="row gy-2 gx-3 align-items-end">
                            <div class="col-auto">
                                <label class="form-label">As of Date</label>
                                <input type="date" name="as_of_date" value="<?php echo htmlspecialchars($asOfDate); ?>" class="form-control" required>
                            </div>
                            <div class="col-auto">
                                <button type="submit" class="btn btn-primary">Load</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row">
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">Assets</div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr><th>Account</th><th class="text-end">Balance</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($reportResult['assets'])): ?>
                                            <tr><td colspan="2" class="text-center">No assets found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($reportResult['assets'] as $asset): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($asset['ledger_name']); ?></td>
                                                    <td class="text-end"><?php echo number_format($asset['balance'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer"><strong>Total Assets: <?php echo number_format($reportResult['total_assets'], 2); ?></strong></div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">Liabilities & Equity</div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr><th>Account</th><th class="text-end">Balance</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($reportResult['liabilities']) && empty($reportResult['equity'])): ?>
                                            <tr><td colspan="2" class="text-center">No liabilities or equity found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($reportResult['liabilities'] as $liability): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($liability['ledger_name']); ?></td>
                                                    <td class="text-end"><?php echo number_format($liability['balance'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            <?php foreach ($reportResult['equity'] as $equity): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($equity['ledger_name']); ?></td>
                                                    <td class="text-end"><?php echo number_format($equity['balance'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer"><strong>Total L&E: <?php echo number_format($reportResult['total_liabilities'] + $reportResult['total_equity'], 2); ?></strong></div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
