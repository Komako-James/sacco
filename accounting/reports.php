<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requirePermission('accounting.view');
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
                    <div>
                        <h1 class="h4 mb-0">Accounting Reports</h1>
                        <p class="text-muted mb-0">Launch the existing LedgerService-backed reports below.</p>
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Trial Balance</h5>
                                <p class="card-text">See ledger account balances as of a selected date.</p>
                            </div>
                            <div class="card-footer bg-white border-0">
                                <a href="trial_balance.php" class="btn btn-primary">Open Trial Balance</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Balance Sheet</h5>
                                <p class="card-text">Review assets, liabilities, and equity balances as of a chosen date.</p>
                            </div>
                            <div class="card-footer bg-white border-0">
                                <a href="balance_sheet.php" class="btn btn-primary">Open Balance Sheet</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">Income Statement</h5>
                                <p class="card-text">View income and expense totals for a selected period.</p>
                            </div>
                            <div class="card-footer bg-white border-0">
                                <a href="income_statement.php" class="btn btn-primary">Open Income Statement</a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Other report views</h5>
                        <p class="card-text mb-0">General ledger, cash book, and day sheet are not exposed in this phase because the current LedgerService only provides trial balance, balance sheet, and income statement reporting functions.</p>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
