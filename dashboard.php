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
    <!-- Mobile Sidebar Toggle -->
    <button class="sidebar-toggle" id="sidebarToggle">
        <i class="bi bi-list"></i>
    </button>

    <!-- Include Sidebar -->
    <?php include 'includes/sidebar.php'; ?>

    <!-- Main Content -->
    <div class="main-content">
        <div class="container-fluid">
            <div class="page-header">
                <div>
                    <h1 class="page-title">Executive Dashboard</h1>
                    <p class="page-subtitle">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>. Your SACCO portfolio is performing.</p>
                </div>
                <div class="text-end">
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-shield-check me-1"></i> Secure access</span>
                    <div class="small text-muted mt-2">Last login: <?php echo $user['last_login'] ? date('M j, Y g:i A', strtotime($user['last_login'])) : 'First time'; ?></div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card hero-panel">
                        <div class="card-body p-4 p-lg-5">
                            <div class="row align-items-center g-4">
                                <div class="col-lg-8">
                                    <h2 class="h4 fw-bold mb-2">Financial operations overview</h2>
                                    <p class="mb-3 opacity-75">Monitor members, savings, loans, shares, and portfolio performance from one polished control center.</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <span class="badge bg-white text-primary"><i class="bi bi-people me-1"></i> Members</span>
                                        <span class="badge bg-white text-primary"><i class="bi bi-piggy-bank me-1"></i> Savings</span>
                                        <span class="badge bg-white text-primary"><i class="bi bi-cash-coin me-1"></i> Loans</span>
                                        <span class="badge bg-white text-primary"><i class="bi bi-diagram-3 me-1"></i> Shares</span>
                                    </div>
                                </div>
                                <div class="col-lg-4 text-lg-end">
                                    <a href="<?php echo APP_URL; ?>/reports/executive_dashboard.php" class="btn btn-light">View Reports</a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Statistics Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6">
                    <div class="card dashboard-card text-white bg-primary border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title mb-1">Total Members</h6>
                                    <h2 class="mb-0"><?php echo number_format($stats['total_members']); ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-people fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card dashboard-card text-white bg-success border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title mb-1">Total Savings</h6>
                                    <h2 class="mb-0"><?php echo formatMoney($stats['total_savings']); ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-piggy-bank fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card dashboard-card text-white bg-warning border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title mb-1">Active Loans</h6>
                                    <h2 class="mb-0"><?php echo number_format($stats['active_loans']); ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-cash-coin fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6">
                    <div class="card dashboard-card text-white bg-info border-0">
                        <div class="card-body">
                            <div class="d-flex justify-content-between">
                                <div>
                                    <h6 class="card-title mb-1">Loan Portfolio</h6>
                                    <h2 class="mb-0"><?php echo formatMoney($stats['loan_portfolio']); ?></h2>
                                </div>
                                <div class="align-self-center">
                                    <i class="bi bi-graph-up fs-1"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions Section -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-primary text-white d-flex align-items-center">
                            <i class="bi bi-lightning me-2"></i>
                            <h5 class="mb-0">Quick Actions</h5>
                        </div>
                        <div class="card-body">
                            <div class="row g-3">
                                <div class="col-lg-2 col-md-4 col-6">
                                    <a href="<?php echo APP_URL; ?>/members/register.php" class="btn btn-outline-primary d-block text-center py-3">
                                        <i class="bi bi-person-plus fs-3 d-block mb-2"></i>
                                        <small>Register Member</small>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6">
                                    <a href="<?php echo APP_URL; ?>/savings/deposit.php" class="btn btn-outline-success d-block text-center py-3">
                                        <i class="bi bi-arrow-down-circle fs-3 d-block mb-2"></i>
                                        <small>Make Deposit</small>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6">
                                    <a href="<?php echo APP_URL; ?>/savings/withdraw.php" class="btn btn-outline-warning d-block text-center py-3">
                                        <i class="bi bi-arrow-up-circle fs-3 d-block mb-2"></i>
                                        <small>Withdrawal</small>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6">
                                    <a href="<?php echo APP_URL; ?>/loans/apply.php" class="btn btn-outline-info d-block text-center py-3">
                                        <i class="bi bi-file-earmark-plus fs-3 d-block mb-2"></i>
                                        <small>Apply Loan</small>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6">
                                    <a href="<?php echo APP_URL; ?>/loans/repay.php" class="btn btn-outline-secondary d-block text-center py-3">
                                        <i class="bi bi-arrow-repeat fs-3 d-block mb-2"></i>
                                        <small>Loan Payment</small>
                                    </a>
                                </div>
                                <div class="col-lg-2 col-md-4 col-6">
                                    <a href="<?php echo APP_URL; ?>/reports/portfolio.php" class="btn btn-outline-dark d-block text-center py-3">
                                        <i class="bi bi-graph-up fs-3 d-block mb-2"></i>
                                        <small>View Reports</small>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Data Section -->
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Members</h5>
                            <a href="<?php echo APP_URL; ?>/members/list.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($recent_members)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Member No</th>
                                            <th>Name</th>
                                            <th>Phone</th>
                                            <th>Joined</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_members as $member): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($member['membership_no']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                            <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                            <td><small><?php echo date('M j', strtotime($member['created_at'])); ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-people text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No recent members</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-header bg-light d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Recent Transactions</h5>
                            <a href="<?php echo APP_URL; ?>/reports/transactions.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                        <div class="card-body p-0">
                            <?php if (!empty($recent_transactions)): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Receipt</th>
                                            <th>Member</th>
                                            <th>Amount</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recent_transactions as $trans): ?>
                                        <tr>
                                            <td><strong><?php echo htmlspecialchars($trans['receipt_no']); ?></strong></td>
                                            <td><?php echo htmlspecialchars($trans['full_name']); ?></td>
                                            <td>
                                                <span class="text-success fw-bold"><?php echo formatMoney($trans['amount']); ?></span>
                                            </td>
                                            <td><small><?php echo date('M j', strtotime($trans['transaction_date'])); ?></small></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php else: ?>
                            <div class="text-center py-4">
                                <i class="bi bi-receipt text-muted" style="font-size: 3rem;"></i>
                                <p class="text-muted mt-2">No recent transactions</p>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/main.js"></script>
</body>
</html>
