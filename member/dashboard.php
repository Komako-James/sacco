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

$pageTitle = 'Member Portal - Dashboard';
require_once __DIR__ . '/layout/header.php';
?>
    <div class="container-fluid py-4">
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
<?php require_once __DIR__ . '/layout/footer.php'; ?>
