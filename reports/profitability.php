<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';
require_once '../app/Services/LedgerService.php';

$auth->requireLogin();
$auth->requirePermission('reports.view');

$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

$profitability = \SACCO\Services\LedgerService::getProfitabilitySummary($startDate, $endDate);

$grossProfit = $profitability['total_revenue'] - $profitability['total_expenses'];
$profitMargin = $profitability['total_revenue'] > 0 ? round(($grossProfit / $profitability['total_revenue']) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profitability Analysis - <?php echo APP_NAME; ?></title>
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
                        <h1 class="h4 mb-0">Profitability Analysis</h1>
                        <p class="text-muted mb-0">Review net profit and margin for the selected period.</p>
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
                                <p class="mb-0"><strong>Profit Margin:</strong> <?php echo $profitMargin; ?>%</p>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-success h-100">
                            <div class="card-body">
                                <h5 class="card-title">Revenue</h5>
                                <p class="card-text display-6 mb-0"><?php echo formatMoney($profitability['total_revenue']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-danger h-100">
                            <div class="card-body">
                                <h5 class="card-title">Expenses</h5>
                                <p class="card-text display-6 mb-0"><?php echo formatMoney($profitability['total_expenses']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-primary h-100">
                            <div class="card-body">
                                <h5 class="card-title">Net Profit</h5>
                                <p class="card-text display-6 mb-0"><?php echo formatMoney($grossProfit); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">Profitability Details</h5>
                    </div>
                    <div class="card-body table-responsive">
                        <table class="table table-sm table-hover">
                            <tbody>
                                <tr>
                                    <th scope="row">Total Revenue</th>
                                    <td class="text-end"><?php echo formatMoney($profitability['total_revenue']); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Total Expenses</th>
                                    <td class="text-end"><?php echo formatMoney($profitability['total_expenses']); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Gross Profit</th>
                                    <td class="text-end"><?php echo formatMoney($grossProfit); ?></td>
                                </tr>
                                <tr>
                                    <th scope="row">Profit Margin</th>
                                    <td class="text-end"><?php echo $profitMargin; ?>%</td>
                                </tr>
                            </tbody>
                        </table>
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
