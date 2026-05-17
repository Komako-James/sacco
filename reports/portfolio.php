<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'finance']);

$db = Database::getInstance()->getConnection();

// Loan portfolio metrics
$stmt = $db->query("
    SELECT 
        COUNT(*) as total_loans,
        SUM(CASE WHEN status = 'disbursed' THEN 1 ELSE 0 END) as active_loans,
        SUM(COALESCE(amount_approved, amount_requested)) as total_disbursed,
        SUM(outstanding_balance) as total_outstanding,
        SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed_loans,
        AVG(interest_rate) as avg_interest_rate
    FROM loans
");
$portfolio = $stmt->fetch();

// Loans by status
$stmt = $db->query("
    SELECT status, COUNT(*) as count, SUM(COALESCE(amount_approved, amount_requested)) as amount
    FROM loans
    GROUP BY status
");
$byStatus = $stmt->fetchAll();

// Loans by product
$stmt = $db->query("
    SELECT 
        lp.product_name,
        COUNT(l.loan_id) as count,
        SUM(COALESCE(l.amount_approved, l.amount_requested)) as total_amount,
        AVG(l.interest_rate) as avg_rate
    FROM loans l
    JOIN loan_products lp ON l.product_id = lp.product_id
    GROUP BY lp.product_id, lp.product_name
");
$byProduct = $stmt->fetchAll();

// Overdue loans
$stmt = $db->query("
            SELECT 
                l.*,
                m.full_name,
                m.membership_no,
                DATEDIFF(NOW(), DATE_ADD(l.disbursement_date, INTERVAL l.repayment_period_months MONTH)) as days_overdue
            FROM loans l
            JOIN members m ON l.member_id = m.member_id
            WHERE l.status = 'disbursed'
            AND DATE_ADD(l.disbursement_date, INTERVAL l.repayment_period_months MONTH) < NOW()
            ORDER BY days_overdue DESC
            LIMIT 20
        ");
$overdueLoans = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Portfolio Report - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php"><?php echo APP_NAME; ?></a>
            <div class="collapse navbar-collapse ms-auto">
                <a href="../logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <h2 class="mb-4">Loan Portfolio Report - <?php echo date('M d, Y'); ?></h2>

        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Loans</h5>
                        <h3><?php echo $portfolio['total_loans']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Active Loans</h5>
                        <h3><?php echo $portfolio['active_loans']; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Total Disbursed</h5>
                        <h3><?php echo formatMoney($portfolio['total_disbursed']); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-warning">
                    <div class="card-body">
                        <h5 class="card-title">Outstanding</h5>
                        <h3><?php echo formatMoney($portfolio['total_outstanding']); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-3">
            <!-- By Status -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Loans by Status</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Status</th>
                                    <th>Count</th>
                                    <th>Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($byStatus as $row): ?>
                                <tr>
                                    <td><span class="badge bg-info"><?php echo ucfirst($row['status']); ?></span></td>
                                    <td><?php echo $row['count']; ?></td>
                                    <td><?php echo formatMoney($row['amount']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- By Product -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Loans by Product</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Count</th>
                                    <th>Total Amount</th>
                                    <th>Avg Rate</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($byProduct as $row): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['product_name']); ?></td>
                                    <td><?php echo $row['count']; ?></td>
                                    <td><?php echo formatMoney($row['total_amount']); ?></td>
                                    <td><?php echo round($row['avg_rate'], 2); ?>%</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Overdue Loans -->
        <div class="card">
            <div class="card-header bg-danger text-white">
                <h5 class="mb-0">Overdue Loans (<?php echo count($overdueLoans); ?>)</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Member</th>
                                <th>Reference</th>
                                <th>Amount</th>
                                <th>Outstanding</th>
                                <th>Days Overdue</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($overdueLoans as $loan): ?>
                            <tr class="table-danger">
                                <td><?php echo htmlspecialchars($loan['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($loan['loan_ref_no']); ?></td>
                                <td><?php echo formatMoney($loan['amount_approved'] ?? $loan['amount_requested']); ?></td>
                                <td><?php echo formatMoney($loan['outstanding_balance']); ?></td>
                                <td><strong><?php echo $loan['days_overdue']; ?></strong></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (empty($overdueLoans)): ?>
                <p class="text-success text-center mt-3">No overdue loans</p>
                <?php endif; ?>
            </div>
        </div>

        <div class="mt-4 mb-4">
            <button onclick="window.print()" class="btn btn-primary">Print Report</button>
            <a href="../dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
