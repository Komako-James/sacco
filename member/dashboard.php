<?php
/**
 * Member Portal Dashboard
 * Displays member's financial overview
 */

require_once '../member/auth-middleware.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';

requireMemberLogin();

$member = getMemberData();
if (!$member) {
    error_log('dashboard: getMemberData returned empty; SESSION=' . print_r(isset($_SESSION) ? $_SESSION : [], true));
    header('Location: ../member-login.php?error=' . urlencode('Please log in'));
    exit();
}
$savings = getMemberSavings();
$loans = getMemberLoans();

$db = getDB();

// Get summary statistics
$stmt = $db->prepare("
    SELECT 
        COALESCE(SUM(balance), 0) as total_savings
    FROM savings_accounts
    WHERE member_id = ? AND status = 'active'
");
$stmt->execute([$member['member_id']]);
$savingsSummary = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$savingsSummary) $savingsSummary = ['total_savings' => 0];

$stmt = $db->prepare("
    SELECT 
        COUNT(*) as total_loans,
        COALESCE(SUM(outstanding_balance), 0) as total_outstanding
    FROM loans
    WHERE member_id = ? AND status IN ('approved', 'disbursed')
");
$stmt->execute([$member['member_id']]);
$loansSummary = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$loansSummary) $loansSummary = ['total_loans' => 0, 'total_outstanding' => 0];

$stmt = $db->prepare("SELECT COALESCE(shares_owned, 0) as total_shares, COALESCE(total_invested, 0) as total_invested FROM member_share_holdings WHERE member_id = ?");
$stmt->execute([$member['member_id']]);
$sharesSummary = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$sharesSummary) $sharesSummary = ['total_shares' => 0, 'total_invested' => 0];

// Get recent transactions
$stmt = $db->prepare("
    SELECT st.*, sa.account_type
    FROM savings_transactions st
    JOIN savings_accounts sa ON st.account_id = sa.account_id
    WHERE sa.member_id = ?
    ORDER BY st.transaction_date DESC
    LIMIT 10
");
$stmt->execute([$member['member_id']]);
$recentTransactions = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Portal - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        body {
            background: #f8f9fa;
        }
        .navbar-member {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        .member-sidebar {
            background: #2c3e50;
            min-height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            width: 250px;
            padding-top: 60px;
            overflow-y: auto;
        }
        .member-sidebar a {
            display: flex;
            align-items: center;
            padding: 12px 20px;
            color: #bdc3c7;
            text-decoration: none;
            transition: all 0.2s;
        }
        .member-sidebar a:hover,
        .member-sidebar a.active {
            background: #3498db;
            color: white;
            padding-left: 25px;
        }
        .member-sidebar i {
            margin-right: 12px;
            width: 20px;
        }
        .member-content {
            margin-left: 250px;
            padding-top: 60px;
            padding: 20px;
        }
        .stat-card {
            background: white;
            border-radius: 10px;
            padding: 20px;
            margin-bottom: 20px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.08);
            border-left: 4px solid #3498db;
        }
        .stat-card h6 {
            color: #7f8c8d;
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 10px;
        }
        .stat-card .amount {
            font-size: 1.8rem;
            font-weight: 700;
            color: #2c3e50;
        }
        .stat-card.savings {
            border-left-color: #27ae60;
        }
        .stat-card.loans {
            border-left-color: #e74c3c;
        }
        .stat-card.shares {
            border-left-color: #f39c12;
        }
        @media (max-width: 768px) {
            .member-sidebar {
                width: 0;
                padding-top: 0;
                transition: width 0.3s;
            }
            .member-sidebar.show {
                width: 250px;
                padding-top: 60px;
            }
            .member-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-dark navbar-member fixed-top">
        <div class="container-fluid">
            <span class="navbar-brand">
                <i class="bi bi-list me-2" style="cursor: pointer; font-size: 1.3rem;" onclick="toggleSidebar()"></i>
                Member Portal
            </span>
            <div>
                <span class="text-white me-3">
                    <i class="bi bi-person-circle me-2"></i>
                    <?php echo htmlspecialchars($member['full_name']); ?>
                </span>
                <a href="logout.php" class="btn btn-sm btn-outline-light">
                    <i class="bi bi-box-arrow-right me-1"></i>
                    Logout
                </a>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div class="member-sidebar" id="memberSidebar">
        <ul class="list-unstyled">
            <li>
                <a href="dashboard.php" class="active">
                    <i class="bi bi-speedometer2"></i>
                    Dashboard
                </a>
            </li>
            <li>
                <a href="savings.php">
                    <i class="bi bi-piggy-bank"></i>
                    Savings Accounts
                </a>
            </li>
            <li>
                <a href="shares.php">
                    <i class="bi bi-graph-up"></i>
                    Shares
                </a>
            </li>
            <li>
                <a href="loans.php">
                    <i class="bi bi-cash-coin"></i>
                    My Loans
                </a>
            </li>
            <li>
                <a href="transactions.php">
                    <i class="bi bi-receipt"></i>
                    Transactions
                </a>
            </li>
            <li>
                <a href="statements.php">
                    <i class="bi bi-file-earmark-pdf"></i>
                    Statements
                </a>
            </li>
            <li>
                <a href="repayment-schedule.php">
                    <i class="bi bi-calendar-event"></i>
                    Repayment Schedule
                </a>
            </li>
            <li>
                <a href="profile.php">
                    <i class="bi bi-person"></i>
                    My Profile
                </a>
            </li>
            <li>
                <a href="security.php">
                    <i class="bi bi-shield-lock"></i>
                    Security
                </a>
            </li>
        </ul>
    </div>

    <!-- Main Content -->
    <div class="member-content">
        <div class="container-fluid">
            <!-- Page Header -->
            <div class="mb-4">
                <h1 class="h3 mb-1">Welcome, <?php echo htmlspecialchars($member['full_name']); ?>!</h1>
                <p class="text-muted mb-0">Member #<?php echo htmlspecialchars($member['membership_no']); ?></p>
            </div>

            <!-- Summary Cards -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="stat-card savings">
                        <h6>Total Savings</h6>
                        <div class="amount"><?php echo formatMoney($savingsSummary['total_savings']); ?></div>
                        <small class="text-muted">Across all accounts</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card loans">
                        <h6>Active Loans</h6>
                        <div class="amount"><?php echo $loansSummary['total_loans']; ?></div>
                        <small class="text-muted">Outstanding: <?php echo formatMoney($loansSummary['total_outstanding']); ?></small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-card shares">
                        <h6>Share Holdings</h6>
                        <div class="amount"><?php echo number_format($sharesSummary['total_shares']); ?> shares</div>
                        <small class="text-muted">Value: <?php echo formatMoney($sharesSummary['total_invested']); ?></small>
                    </div>
                </div>
            </div>

            <!-- Main Section -->
            <div class="row">
                <!-- Savings Accounts -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-piggy-bank me-2"></i>
                                Savings Accounts
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($savings)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Account Type</th>
                                            <th>Balance</th>
                                            <th>Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($savings as $account): ?>
                                        <tr>
                                            <td><?php echo ucfirst($account['account_type']); ?></td>
                                            <td><?php echo formatMoney($account['balance']); ?></td>
                                            <td>
                                                <span class="badge bg-success">
                                                    <?php echo ucfirst($account['status']); ?>
                                                </span>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="savings.php" class="btn btn-sm btn-outline-primary mt-2">View Details</a>
                            <?php else: ?>
                            <p class="text-muted">No savings accounts</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Loans -->
                <div class="col-lg-6 mb-4">
                    <div class="card">
                        <div class="card-header bg-danger text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-cash-coin me-2"></i>
                                Active Loans
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($loans)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Reference</th>
                                            <th>Amount</th>
                                            <th>Outstanding</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach (array_slice($loans, 0, 3) as $loan): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($loan['loan_reference']); ?></td>
                                            <td><?php echo formatMoney($loan['loan_amount']); ?></td>
                                            <td><?php echo formatMoney($loan['outstanding_balance']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="loans.php" class="btn btn-sm btn-outline-danger mt-2">View All Loans</a>
                            <?php else: ?>
                            <p class="text-muted">No active loans</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Transactions -->
            <div class="row">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">
                                <i class="bi bi-receipt me-2"></i>
                                Recent Transactions
                            </h5>
                        </div>
                        <div class="card-body">
                            <?php if (!empty($recentTransactions)): ?>
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Date</th>
                                            <th>Account</th>
                                            <th>Type</th>
                                            <th>Amount</th>
                                            <th>Balance</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($recentTransactions as $txn): ?>
                                        <tr>
                                            <td><?php echo date('M d, Y', strtotime($txn['transaction_date'])); ?></td>
                                            <td><?php echo ucfirst($txn['account_type']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $txn['transaction_type'] === 'deposit' ? 'success' : 'warning'; ?>">
                                                    <?php echo ucfirst($txn['transaction_type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo formatMoney($txn['amount']); ?></td>
                                            <td><?php echo formatMoney($txn['balance_after']); ?></td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <a href="transactions.php" class="btn btn-sm btn-outline-success mt-2">View All Transactions</a>
                            <?php else: ?>
                            <p class="text-muted">No transactions yet</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function toggleSidebar() {
        document.getElementById('memberSidebar').classList.toggle('show');
    }

    // Set active menu item
    document.addEventListener('DOMContentLoaded', function() {
        const currentPage = window.location.pathname.split('/').pop() || 'dashboard.php';
        document.querySelectorAll('.member-sidebar a').forEach(link => {
            if (link.href.includes(currentPage)) {
                link.classList.add('active');
            } else {
                link.classList.remove('active');
            }
        });
    });
    </script>
</body>
</html>
