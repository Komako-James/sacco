<?php
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';

$auth->requireLogin();
$db = getDB();

function getTableColumns(PDO $db, string $table): array
{
    $stmt = $db->prepare("SELECT COLUMN_NAME FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = ?");
    $stmt->execute([$table]);
    return array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'COLUMN_NAME');
}

$error = '';
$accounts = [];

try {
    $memberColumns = getTableColumns($db, 'members');
    $accountColumns = getTableColumns($db, 'savings_accounts');

    $memberNumberColumn = in_array('membership_no', $memberColumns, true)
        ? 'membership_no'
        : (in_array('membership_number', $memberColumns, true) ? 'membership_number' : null);

    $accountIdColumn = in_array('account_id', $accountColumns, true)
        ? 'account_id'
        : (in_array('savings_account_id', $accountColumns, true) ? 'savings_account_id' : null);

    if ($memberNumberColumn === null || $accountIdColumn === null) {
        throw new RuntimeException('Unsupported database schema for savings accounts.');
    }

    $sql = "
        SELECT
            sa.{$accountIdColumn} AS account_id,
            sa.account_number,
            sa.account_type,
            sa.balance,
            sa.status,
            sa.created_at,
            m.full_name,
            m.{$memberNumberColumn} AS membership_no
        FROM savings_accounts sa
        JOIN members m ON sa.member_id = m.member_id
        ORDER BY sa.created_at DESC
    ";

    $stmt = $db->query($sql);
    $accounts = $stmt->fetchAll();
} catch (Throwable $e) {
    $error = $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savings Accounts - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>

            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="page-header">
                    <div>
                        <nav aria-label="breadcrumb">
                            <ol class="breadcrumb">
                                <li class="breadcrumb-item"><a href="../dashboard.php">Home</a></li>
                                <li class="breadcrumb-item active" aria-current="page">Savings</li>
                            </ol>
                        </nav>
                        <h1 class="page-title">Savings Accounts</h1>
                        <p class="page-subtitle">Review account balances and manage member savings activity.</p>
                    </div>
                    <a href="open.php" class="btn btn-primary">
                        <i class="bi bi-plus-lg me-2"></i>Open New Account
                    </a>
                </div>

                <?php if ($error): ?>
                <div class="alert alert-danger">
                    <strong>Unable to load savings accounts.</strong> <?php echo htmlspecialchars($error); ?>
                </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead>
                                    <tr>
                                        <th>Account No</th>
                                        <th>Member</th>
                                        <th>Account Type</th>
                                        <th>Balance</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($accounts)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">No savings accounts found.</td>
                                    </tr>
                                    <?php else: ?>
                                    <?php foreach ($accounts as $account): ?>
                                    <tr>
                                        <td><strong><?php echo htmlspecialchars($account['account_number']); ?></strong></td>
                                        <td>
                                            <?php echo htmlspecialchars($account['full_name']); ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($account['membership_no']); ?></small>
                                        </td>
                                        <td><?php echo htmlspecialchars(ucfirst(str_replace('_', ' ', $account['account_type']))); ?></td>
                                        <td><strong><?php echo formatMoney($account['balance']); ?></strong></td>
                                        <td>
                                            <span class="badge <?php echo $account['status'] === 'active' ? 'badge-active' : 'badge-inactive'; ?>">
                                                <?php echo htmlspecialchars(ucfirst($account['status'])); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars(date('M d, Y', strtotime($account['created_at']))); ?></td>
                                        <td>
                                            <a href="deposit.php?account_id=<?php echo urlencode($account['account_id']); ?>" class="btn btn-sm btn-outline-success">Deposit</a>
                                            <a href="withdraw.php?account_id=<?php echo urlencode($account['account_id']); ?>" class="btn btn-sm btn-outline-warning">Withdraw</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
