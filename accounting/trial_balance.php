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
    $reportResult = LedgerService::generateTrialBalance($asOfDate);
} catch (Exception $e) {
    $error = $e->getMessage();
}

if ($format === 'csv' && empty($error)) {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="trial_balance_' . date('Ymd_His') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['Ledger Code', 'Account', 'Debit', 'Credit', 'Balance', 'As Of Date']);
    foreach ($reportResult as $row) {
        fputcsv($output, [
            $row['ledger_code'],
            $row['ledger_name'],
            number_format($row['total_debit'], 2, '.', ''),
            number_format($row['total_credit'], 2, '.', ''),
            number_format($row['balance'], 2, '.', ''),
            $asOfDate
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
    <title>Trial Balance - <?php echo APP_NAME; ?></title>
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
                        <h1 class="h4 mb-0">Trial Balance</h1>
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

                <div class="card">
                    <div class="card-body table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Account</th>
                                    <th class="text-end">Debit</th>
                                    <th class="text-end">Credit</th>
                                    <th class="text-end">Balance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($reportResult)): ?>
                                    <tr><td colspan="5" class="text-center">No trial balance data available.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($reportResult as $row): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($row['ledger_code']); ?></td>
                                            <td><?php echo htmlspecialchars($row['ledger_name']); ?></td>
                                            <td class="text-end"><?php echo number_format($row['total_debit'], 2); ?></td>
                                            <td class="text-end"><?php echo number_format($row['total_credit'], 2); ?></td>
                                            <td class="text-end"><?php echo number_format($row['balance'], 2); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
