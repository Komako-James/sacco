<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'finance', 'officer']);

$db = Database::getInstance()->getConnection();

// Date range
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');

// Get summary statistics
$stmt = $db->prepare("
    SELECT 
        SUM(CASE WHEN transaction_type = 'deposit' THEN amount ELSE 0 END) as total_deposits,
        SUM(CASE WHEN transaction_type = 'withdrawal' THEN amount ELSE 0 END) as total_withdrawals,
        SUM(CASE WHEN transaction_type = 'interest' THEN amount ELSE 0 END) as total_interest,
        COUNT(*) as total_transactions
    FROM savings_transactions
    WHERE DATE(transaction_date) BETWEEN ? AND ?
");
$stmt->execute([$startDate, $endDate]);
$summary = $stmt->fetch();

// Get by transaction type
$stmt = $db->prepare("
    SELECT 
        transaction_type,
        COUNT(*) as count,
        SUM(amount) as total_amount
    FROM savings_transactions
    WHERE DATE(transaction_date) BETWEEN ? AND ?
    GROUP BY transaction_type
");
$stmt->execute([$startDate, $endDate]);
$byType = $stmt->fetchAll();

// Get by payment method
$stmt = $db->prepare("
    SELECT 
        payment_method,
        COUNT(*) as count,
        SUM(amount) as total_amount
    FROM savings_transactions
    WHERE DATE(transaction_date) BETWEEN ? AND ?
    GROUP BY payment_method
");
$stmt->execute([$startDate, $endDate]);
$byMethod = $stmt->fetchAll();

// Get top members by savings
$stmt = $db->prepare("
    SELECT 
        m.member_id,
        m.full_name,
        m.membership_no,
        COUNT(*) as transaction_count,
        SUM(CASE WHEN st.transaction_type = 'deposit' THEN st.amount ELSE 0 END) as total_deposits,
        SUM(CASE WHEN st.transaction_type = 'withdrawal' THEN st.amount ELSE 0 END) as total_withdrawals
    FROM savings_transactions st
    JOIN savings_accounts sa ON st.account_id = sa.account_id
    JOIN members m ON sa.member_id = m.member_id
    WHERE DATE(st.transaction_date) BETWEEN ? AND ?
    GROUP BY m.member_id, m.full_name, m.membership_no
    ORDER BY total_deposits DESC
    LIMIT 20
");
$stmt->execute([$startDate, $endDate]);
$topMembers = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savings Reports - <?php echo APP_NAME; ?></title>
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
        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label>From:</label>
                        <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>To:</label>
                        <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                    </div>
                    <div class="col-md-3" style="padding-top: 32px;">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                    <div class="col-md-3" style="padding-top: 32px;">
                        <button type="button" class="btn btn-success w-100" onclick="window.print()">Print Report</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-3">
            <div class="col-md-3">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5 class="card-title">Total Deposits</h5>
                        <h3><?php echo formatMoney($summary['total_deposits'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-danger">
                    <div class="card-body">
                        <h5 class="card-title">Total Withdrawals</h5>
                        <h3><?php echo formatMoney($summary['total_withdrawals'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5 class="card-title">Total Interest</h5>
                        <h3><?php echo formatMoney($summary['total_interest'] ?? 0); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5 class="card-title">Total Transactions</h5>
                        <h3><?php echo $summary['total_transactions'] ?? 0; ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <!-- By Type -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Transactions by Type</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Type</th>
                                    <th>Count</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($byType as $row): ?>
                                <tr>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $row['transaction_type'])); ?></td>
                                    <td><?php echo $row['count']; ?></td>
                                    <td><?php echo formatMoney($row['total_amount']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- By Method -->
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Transactions by Payment Method</h5>
                    </div>
                    <div class="card-body">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>Count</th>
                                    <th>Total Amount</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($byMethod as $row): ?>
                                <tr>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $row['payment_method'])); ?></td>
                                    <td><?php echo $row['count']; ?></td>
                                    <td><?php echo formatMoney($row['total_amount']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Top Members -->
        <div class="card mt-3">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Top Members by Deposits</h5>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Membership No</th>
                                <th>Member Name</th>
                                <th>Transactions</th>
                                <th>Total Deposits</th>
                                <th>Total Withdrawals</th>
                                <th>Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($topMembers as $member): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($member['membership_no']); ?></td>
                                <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                <td><?php echo $member['transaction_count']; ?></td>
                                <td><?php echo formatMoney($member['total_deposits']); ?></td>
                                <td><?php echo formatMoney($member['total_withdrawals']); ?></td>
                                <td><?php echo formatMoney($member['total_deposits'] - $member['total_withdrawals']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
