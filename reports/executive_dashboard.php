<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';
require_once '../app/Services/ExecutiveDashboardService.php';

$auth->requireLogin();
$auth->requirePermission('reports.view');

$dashboardService = new \SACCO\Services\ExecutiveDashboardService();
$dashboard = $dashboardService->getExecutiveDashboard();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Executive Dashboard - <?php echo APP_NAME; ?></title>
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
                        <h1 class="h4 mb-0">Executive Dashboard</h1>
                        <p class="text-muted mb-0">High-level financial and portfolio KPIs.</p>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-3">
                        <div class="card bg-light h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase text-muted">Members</h6>
                                <p class="display-6 mb-0"><?php echo number_format($dashboard['financial_position']['total_members']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase text-muted">Total Savings</h6>
                                <p class="display-6 mb-0"><?php echo formatMoney($dashboard['financial_position']['total_savings']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase text-muted">Total Shares</h6>
                                <p class="display-6 mb-0"><?php echo formatMoney($dashboard['financial_position']['total_shares']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-light h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase text-muted">Loans Outstanding</h6>
                                <p class="display-6 mb-0"><?php echo formatMoney($dashboard['financial_position']['total_loans_outstanding']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-primary h-100">
                            <div class="card-body">
                                <h5 class="card-title">Revenue MTD</h5>
                                <p class="display-6 mb-0"><?php echo formatMoney($dashboard['current_month']['revenue_mtd']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-danger h-100">
                            <div class="card-body">
                                <h5 class="card-title">Expenses MTD</h5>
                                <p class="display-6 mb-0"><?php echo formatMoney($dashboard['current_month']['expenses_mtd']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-success h-100">
                            <div class="card-body">
                                <h5 class="card-title">Profit MTD</h5>
                                <p class="display-6 mb-0"><?php echo formatMoney($dashboard['current_month']['profit_mtd']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card border-secondary h-100">
                            <div class="card-body">
                                <h5 class="card-title">Active Loans</h5>
                                <p class="display-6 mb-0"><?php echo number_format($dashboard['portfolio']['active_loans']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-secondary h-100">
                            <div class="card-body">
                                <h5 class="card-title">Interest Receivable</h5>
                                <p class="display-6 mb-0"><?php echo formatMoney($dashboard['portfolio']['interest_receivable']); ?></p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card border-secondary h-100">
                            <div class="card-body">
                                <h5 class="card-title">Loan Portfolio Value</h5>
                                <p class="display-6 mb-0"><?php echo formatMoney($dashboard['portfolio']['loan_portfolio_value']); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card bg-white border-success h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase text-muted">Revenue Growth</h6>
                                <p class="display-6 mb-0"><?php echo $dashboard['growth']['revenue_growth_pct']; ?>%</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-white border-danger h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase text-muted">Expense Growth</h6>
                                <p class="display-6 mb-0"><?php echo $dashboard['growth']['expense_growth_pct']; ?>%</p>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-white border-info h-100">
                            <div class="card-body">
                                <h6 class="text-uppercase text-muted">Member Growth</h6>
                                <p class="display-6 mb-0"><?php echo $dashboard['growth']['member_growth_pct']; ?>%</p>
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
