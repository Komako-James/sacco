<?php
require_once 'includes/auth.php';
require_once 'includes/functions.php';
require_once 'config/constants.php';

$auth->requireLogin();
$user = $auth->getCurrentUser();

$db = getDB();

// Get statistics
$stats = [];

// Total members
$stmt = $db->query("SELECT COUNT(*) as total FROM members WHERE status = 'active'");
$stats['total_members'] = $stmt->fetch()['total'];

// Total savings
$stmt = $db->query("SELECT COALESCE(SUM(balance), 0) as total FROM savings_accounts WHERE status = 'active'");
$stats['total_savings'] = $stmt->fetch()['total'];

// Active loans
$stmt = $db->query("SELECT COUNT(*) as total FROM loans WHERE status = 'disbursed'");
$stats['active_loans'] = $stmt->fetch()['total'];

// Total loan portfolio
$stmt = $db->query("SELECT COALESCE(SUM(outstanding_balance), 0) as total FROM loans WHERE status = 'disbursed'");
$stats['loan_portfolio'] = $stmt->fetch()['total'];

// Recent members
$stmt = $db->query("SELECT * FROM members ORDER BY created_at DESC LIMIT 5");
$recent_members = $stmt->fetchAll();

// Recent transactions
$stmt = $db->query("
    SELECT st.*, m.full_name, sa.account_type 
    FROM savings_transactions st
    JOIN savings_accounts sa ON st.account_id = sa.account_id
    JOIN members m ON sa.member_id = m.member_id
    ORDER BY st.transaction_date DESC 
    LIMIT 10
");
$recent_transactions = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#"><?php echo APP_NAME; ?></a>
            <div class="dropdown">
                <button class="btn btn-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <?php echo htmlspecialchars($_SESSION['full_name']); ?> (<?php echo $_SESSION['role']; ?>)
                </button>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="container-fluid">
        <div class="row">
            <!-- Sidebar -->
            <nav class="col-md-2 d-md-block bg-light sidebar">
                <div class="position-sticky pt-3">
                    <ul class="nav flex-column">
                        <li class="nav-item">
                            <a class="nav-link active" href="dashboard.php">
                                <i class="bi bi-speedometer2"></i> Dashboard
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="members/list.php">
                                <i class="bi bi-people"></i> Members
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="members/register.php">
                                <i class="bi bi-person-plus"></i> Register Member
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="savings/deposit.php">
                                <i class="bi bi-cash-stack"></i> Savings Deposit
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="loans/apply.php">
                                <i class="bi bi-file-text"></i> Loan Application
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="loans/approve.php">
                                <i class="bi bi-check-circle"></i> Approve Loans
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="loans/repay.php">
                                <i class="bi bi-currency-dollar"></i> Loan Repayment
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="reports/portfolio.php">
                                <i class="bi bi-graph-up"></i> Reports
                            </a>
                        </li>
                    </ul>
                </div>
            </nav>
            
            <!-- Main content -->
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Dashboard</h1>
                </div>
                
                <!-- Stats Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card text-white bg-primary">
                            <div class="card-body">
                                <h5 class="card-title">Total Members</h5>
                                <h2><?php echo number_format($stats['total_members']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-success">
                            <div class="card-body">
                                <h5 class="card-title">Total Savings</h5>
                                <h2><?php echo formatMoney($stats['total_savings']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-warning">
                            <div class="card-body">
                                <h5 class="card-title">Active Loans</h5>
                                <h2><?php echo number_format($stats['active_loans']); ?></h2>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card text-white bg-info">
                            <div class="card-body">
                                <h5 class="card-title">Loan Portfolio</h5>
                                <h2><?php echo formatMoney($stats['loan_portfolio']); ?></h2>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Recent Members -->
                <div class="row">
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Recent Members
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <thead>
                                        <tr><th>Membership No</th><th>Name</th><th>Phone</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_members as $member): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($member['membership_no']); ?></td>
                                            <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Recent Transactions -->
                    <div class="col-md-6">
                        <div class="card">
                            <div class="card-header">
                                Recent Transactions
                            </div>
                            <div class="card-body">
                                <table class="table table-sm">
                                    <thead>
                                        <tr><th>Receipt</th><th>Member</th><th>Amount</th></tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_transactions as $trans): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($trans['receipt_no']); ?></td>
                                            <td><?php echo htmlspecialchars($trans['full_name']); ?></td>
                                            <td><?php echo formatMoney($trans['amount']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
