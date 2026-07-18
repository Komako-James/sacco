<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/placeholder.php';

$auth->requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dividends</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="main-content">
        <div class="container-fluid mt-4">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h2">Dividends Dashboard</h1>
                <a href="../reports/profitability.php" class="btn btn-outline-secondary"><i class="bi bi-graph-up"></i> View Reports</a>
            </div>

            <div class="card">
                <div class="card-body">
                    <p class="text-muted">Dividend calculations and distribution are available in the accounting module. Use the reports link to review historical dividend figures.</p>

                    <div class="row mt-3">
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Calculate Dividends</h5>
                                    <p class="text-muted">Run calculations from the backend accounting tools (restricted access).</p>
                                    <a href="calculate.php" class="btn btn-sm btn-primary">Open</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Distribute Dividends</h5>
                                    <p class="text-muted">Prepare and send dividend distributions once calculations are final.</p>
                                    <a href="distribute.php" class="btn btn-sm btn-primary">Open</a>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card">
                                <div class="card-body">
                                    <h5 class="card-title">Dividend History</h5>
                                    <p class="text-muted">Review previously recorded distributions.</p>
                                    <a href="history.php" class="btn btn-sm btn-primary">Open</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
