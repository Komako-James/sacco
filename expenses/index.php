<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'accountant', 'manager']);
$user = $auth->getCurrentUser();
$db = getDB();

// Get expense statistics
$stmt = $db->query("SELECT COUNT(*) as total FROM expenses");
$totalExpenses = $stmt->fetch()['total'] ?? 0;

$stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total_amount FROM expenses WHERE DATE(expense_date) = CURDATE()");
$todayExpenses = $stmt->fetch()['total_amount'] ?? 0;

$stmt = $db->query("SELECT COALESCE(SUM(amount), 0) as total_amount FROM expenses WHERE MONTH(expense_date) = MONTH(CURDATE()) AND YEAR(expense_date) = YEAR(CURDATE())");
$monthExpenses = $stmt->fetch()['total_amount'] ?? 0;

// Get expense categories
$stmt = $db->query("SELECT category, COALESCE(SUM(amount), 0) as total FROM expenses GROUP BY category");
$categories = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Expenses</title>
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
                <h1 class="h2">Expense Dashboard</h1>
                <a href="create.php" class="btn btn-primary"><i class="bi bi-plus-lg"></i> Record Expense</a>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Total Expenses</h5>
                            <h3><?php echo formatMoney(array_sum(array_column($categories, 'total'))); ?></h3>
                            <small class="text-muted"><?php echo $totalExpenses; ?> records</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">Today's Expenses</h5>
                            <h3><?php echo formatMoney($todayExpenses); ?></h3>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title">This Month</h5>
                            <h3><?php echo formatMoney($monthExpenses); ?></h3>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Category Breakdown -->
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Expenses by Category</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Category</th>
                                    <th>Amount</th>
                                    <th>Percentage</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $total = array_sum(array_column($categories, 'total'));
                                foreach ($categories as $cat): 
                                ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($cat['category']); ?></td>
                                        <td><?php echo formatMoney($cat['total']); ?></td>
                                        <td><?php echo $total > 0 ? round(($cat['total'] / $total) * 100, 1) : 0; ?>%</td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="mt-3">
                <a href="reports.php" class="btn btn-outline-secondary"><i class="bi bi-graph-up"></i> View Reports</a>
            </div>
        </div>
    </div>
</body>
</html>
