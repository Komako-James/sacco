<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';
require_once '../app/Services/LedgerService.php';

$auth->requireLogin();
$auth->requirePermission('reports.view');

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$expenseBreakdown = \SACCO\Services\LedgerService::getExpenseBreakdown($startDate, $endDate);
$monthlyExpenseTrend = \SACCO\Services\LedgerService::getMonthlyExpenseTrend($startDate, $endDate);

$totalExpenses = array_sum(array_column($expenseBreakdown, 'amount'));
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Analysis - <?php echo APP_NAME; ?></title>
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
                        <h1 class="h4 mb-0">Expense Analysis</h1>
                        <p class="text-muted mb-0">Review expense composition and monthly spend trend.</p>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET" class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">Start Date</label>
                                <input type="date" name="start_date" class="form-control" value="<?php echo htmlspecialchars($startDate); ?>">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">End Date</label>
                                <input type="date" name="end_date" class="form-control" value="<?php echo htmlspecialchars($endDate); ?>">
                            </div>
                            <div class="col-md-2">
                                <button type="submit" class="btn btn-primary w-100">Filter</button>
                            </div>
                            <div class="col-md-4 text-end">
                                <p class="mb-0"><strong>Total Expenses:</strong> <?php echo formatMoney($totalExpenses); ?></p>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-danger text-white">
                                <h5 class="mb-0">Expense Breakdown</h5>
                            </div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Account</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($expenseBreakdown)): ?>
                                            <tr><td colspan="2" class="text-center">No expense data for this period.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($expenseBreakdown as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['account_name']); ?></td>
                                                    <td class="text-end"><?php echo formatMoney($row['amount']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="card h-100">
                            <div class="card-header bg-warning text-white">
                                <h5 class="mb-0">Monthly Expense Trend</h5>
                            </div>
                            <div class="card-body table-responsive">
                                <table class="table table-sm table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Month</th>
                                            <th class="text-end">Expenses</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($monthlyExpenseTrend)): ?>
                                            <tr><td colspan="2" class="text-center">No expense trend data available.</td></tr>
                                        <?php else: ?>
                                            <?php foreach ($monthlyExpenseTrend as $row): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($row['month_label']); ?></td>
                                                    <td class="text-end"><?php echo formatMoney($row['expenses']); ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mb-4">
                    <a href="../reports/transactions.php" class="btn btn-outline-secondary">Back to Reports</a>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
