<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';

$auth->requireLogin();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Standing Orders Reconcile - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 d-none d-md-block">
                <?php include __DIR__ . '/../includes/sidebar.php'; ?>
            </div>
            <main class="col-md-10 ms-sm-auto px-md-4">
                <nav aria-label="breadcrumb" class="mb-4">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/dashboard.php">Dashboard</a></li>
                        <li class="breadcrumb-item"><a href="<?php echo APP_URL; ?>/standing-orders/reconcile.php">Standing Orders</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reconcile</li>
                    </ol>
                </nav>

                <div class="card shadow-sm">
                    <div class="card-body">
                        <h1 class="h4 mb-3">Standing Orders Reconcile</h1>
                        <p class="text-muted">Module under development.</p>

                        <div class="mb-4">
                            <span class="badge bg-warning">Status: Planned</span>
                        </div>

                        <h5>Future Features</h5>
                        <ul>
                            <li>Review standing order execution</li>
                            <li>Resolve failed or pending payments</li>
                            <li>Confirm reconciliation status for payroll and contributions</li>
                        </ul>

                        <div class="alert alert-info mt-4">
                            This placeholder page keeps the standing orders navigation intact while the reconciliation workflow is designed.
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
