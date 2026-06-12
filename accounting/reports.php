<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';
require_once '../config/db_connection.php';
require_once '../app/Services/LedgerService.php';
use SACCO\Services\LedgerService;

$auth->requireLogin();
$auth->requirePermission('accounting.view');

$report = $_GET['report'] ?? 'trial-balance';
$periodStart = $_GET['period_start'] ?? date('Y-m-01');
$periodEnd = $_GET['period_end'] ?? date('Y-m-t');
$asOfDate = $_GET['as_of_date'] ?? date('Y-m-d');
$reportResult = null;
$error = null;

try {
    switch ($report) {
        case 'balance-sheet':
            $reportResult = LedgerService::generateBalanceSheet($asOfDate);
            break;
        case 'income-statement':
            $reportResult = LedgerService::generateIncomeStatement($periodStart, $periodEnd);
            break;
        default:
            $reportResult = LedgerService::generateTrialBalance($asOfDate);
            $report = 'trial-balance';
            break;
    }
} catch (Exception $e) {
    $error = $e->getMessage();
}

if (!is_array($reportResult)) {
    $reportResult = [];
}

$reportResult += [
    'assets' => [],
    'liabilities' => [],
    'equity' => [],
    'income_items' => [],
    'expense_items' => [],
    'total_income' => 0,
    'total_expenses' => 0,
    'net_income' => 0
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounting Reports - <?php echo APP_NAME; ?></title>
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
                    <h1 class="h4 mb-0">Accounting Reports</h1>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <a href="reports.php?report=trial-balance" class="btn btn-outline-primary mb-2">Trial Balance</a>
                    </div>
                    <div class="col-md-4">
                        <a href="reports.php?report=balance-sheet" class="btn btn-outline-primary mb-2">Balance Sheet</a>
                    </div>
                    <div class="col-md-4">
                        <a href="reports.php?report=income-statement" class="btn btn-outline-primary mb-2">Income Statement</a>
                    </div>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body">
                        <?php if ($report === 'trial-balance'): ?>
                            <h5>Trial Balance as of <?php echo htmlspecialchars($asOfDate); ?></h5>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Code</th>
                                            <th>Account</th>
                                            <th>Debit</th>
                                            <th>Credit</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ((array) $reportResult as $row): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($row['ledger_code']); ?></td>
                                                <td><?php echo htmlspecialchars($row['ledger_name']); ?></td>
                                                <td><?php echo number_format($row['total_debit'], 2); ?></td>
                                                <td><?php echo number_format($row['total_credit'], 2); ?></td>
                                                <td><?php echo number_format($row['balance'], 2); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php elseif ($report === 'balance-sheet'): ?>
                            <h5>Balance Sheet as of <?php echo htmlspecialchars($reportResult['date'] ?? $asOfDate); ?></h5>
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header">Assets</div>
                                        <div class="card-body table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead>
                                                    <tr><th>Account</th><th>Balance</th></tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($reportResult['assets'] as $asset): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($asset['ledger_name']); ?></td>
                                                            <td><?php echo number_format($asset['balance'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="card-footer"><strong>Total: <?php echo number_format($reportResult['total_assets'], 2); ?></strong></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header">Liabilities & Equity</div>
                                        <div class="card-body table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead>
                                                    <tr><th>Account</th><th>Balance</th></tr>
                                                </thead>
                                                <tbody>
                                                    <?php foreach ($reportResult['liabilities'] as $liability): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($liability['ledger_name']); ?></td>
                                                            <td><?php echo number_format($liability['balance'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                    <?php foreach ($reportResult['equity'] as $equity): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($equity['ledger_name']); ?></td>
                                                            <td><?php echo number_format($equity['balance'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="card-footer">
                                            <strong>Total L&E: <?php echo number_format($reportResult['total_liabilities'] + $reportResult['total_equity'], 2); ?></strong>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <h5>Income Statement for <?php echo htmlspecialchars($periodStart); ?> to <?php echo htmlspecialchars($periodEnd); ?></h5>
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header">Income</div>
                                        <div class="card-body table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead><tr><th>Account</th><th>Amount</th></tr></thead>
                                                <tbody>
                                                    <?php foreach ($reportResult['income_items'] as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['ledger_name']); ?></td>
                                                            <td><?php echo number_format($item['amount'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        </div>
                                        <div class="card-footer"><strong>Total Income: <?php echo number_format($reportResult['total_income'], 2); ?></strong></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="card mb-3">
                                        <div class="card-header">Expenses</div>
                                        <div class="card-body table-responsive">
                                            <table class="table table-sm mb-0">
                                                <thead><tr><th>Account</th><th>Amount</th></tr></thead>
                                                <tbody>
                                                    <?php foreach ($reportResult['expense_items'] as $item): ?>
                                                        <tr>
                                                            <td><?php echo htmlspecialchars($item['ledger_name']); ?></td>
                                                            <td><?php echo number_format($item['amount'], 2); ?></td>
                                                        </tr>
                                                    <?php endforeach; ?>
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
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
