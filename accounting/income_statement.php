<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';
require_once '../config/db_connection.php';
require_once '../app/Services/LedgerService.php';

use SACCO\Services\LedgerService;

$auth->requireLogin();
$auth->requirePermission('accounting.view');

$periodStart = $_GET['period_start'] ?? date('Y-m-01');
$periodEnd = $_GET['period_end'] ?? date('Y-m-t');
$format = $_GET['format'] ?? 'html';
$error = null;
$reportResult = [];

try {
    $reportResult = LedgerService::generateIncomeStatement($periodStart, $periodEnd);
} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($format === 'csv' && empty($error)) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="income_statement_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Type', 'Ledger Code', 'Account', 'Amount', 'Period Start', 'Period End']);
    foreach (array_merge($reportResult['income_items'], $reportResult['expense_items']) as $row) {
        fputcsv($output, [
            $row['type'],
            $row['ledger_code'],
            $row['ledger_name'],
            number_format($row['amount'], 2, '.', ''),
            $periodStart,
            $periodEnd
        ]);
    }
    fclose($output);
    exit;
}
?><!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Statement - <?php echo APP_NAME; ?></title>
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
                        <h1 class="h4 mb-0">Income Statement</h1>
                        <small class="text-muted"><?php echo htmlspecialchars($periodStart); ?> to <?php echo htmlspecialchars($periodEnd); ?></small>
                    </div>
                    <div class="btn-group" role="group">
                        <a href="reports.php" class="btn btn-outline-secondary">Back to Reports</a>
                        <a href="?period_start=<?php echo urlencode($periodStart); ?>&period_end=<?php echo urlencode($periodEnd); ?>&format=csv" class="btn btn-outline-primary">Export CSV</a>
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
                                <label class="form-label">Period Start</label>
                                <input type="date" name="period_start" value="<?php echo htmlspecialchars($periodStart); ?>" class="form-control" required>
                            </div>
                            <div class="col-auto">
                                <label class="form-label">Period End</label>
                                <input type="date" name="period_end" value="<?php echo htmlspecialchars($periodEnd); ?>" class="form-control" required>
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
                            <div class="card-header">Income</div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr><th>Account</th><th class="text-end">Amount</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($reportResult['income_items'])): ?>
                                            <tr><td colspan="2" class="text-center">No income items found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($reportResult['income_items'] as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['ledger_name']); ?></td>
                                                    <td class="text-end"><?php echo number_format($item['amount'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer"><strong>Total Income: <?php echo number_format($reportResult['total_income'], 2); ?></strong></div>
                        </div>
                    </div>
                    <div class="col-lg-6 mb-4">
                        <div class="card">
                            <div class="card-header">Expenses</div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm mb-0">
                                    <thead>
                                        <tr><th>Account</th><th class="text-end">Amount</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($reportResult['expense_items'])): ?>
                                            <tr><td colspan="2" class="text-center">No expense items found.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($reportResult['expense_items'] as $item): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($item['ledger_name']); ?></td>
                                                    <td class="text-end"><?php echo number_format($item['amount'], 2); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                            <div class="card-footer"><strong>Total Expenses: <?php echo number_format($reportResult['total_expenses'], 2); ?></strong></div>
                        </div>
                    </div>
                </div>

                <div class="alert alert-secondary">
                    <strong>Net Income:</strong> <?php echo number_format($reportResult['net_income'], 2); ?>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
