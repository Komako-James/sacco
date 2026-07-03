<?php
/**
 * Member Savings Page
 */

require_once __DIR__ . '/auth-middleware.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

requireMemberLogin();

$member = getMemberData();
$savings = getMemberSavings();

$db = getDB();

$pageTitle = 'Savings Accounts - Member Portal';
require_once __DIR__ . '/layout/header.php';
?>
    <div class="container-fluid py-4">
            <div class="mb-4">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <h2>Savings Accounts</h2>
            </div>

            <?php if (!empty($savings)): ?>
            <div class="row">
                <?php foreach ($savings as $account):
                    $stmt = $db->prepare("
                        SELECT 
                            COUNT(*) as transaction_count,
                            MAX(transaction_date) as last_transaction
                        FROM savings_transactions
                        WHERE account_id = ?
                    ");
                    $stmt->execute([$account['account_id']]);
                    $txnInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                ?>
                <div class="col-md-6 mb-3">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="card-title"><?php echo ucfirst($account['account_type']); ?> Account</h5>
                            <dl class="row">
                                <dt class="col-sm-6">Current Balance</dt>
                                <dd class="col-sm-6"><strong><?php echo formatMoney($account['balance']); ?></strong></dd>

                                <dt class="col-sm-6">Account Number</dt>
                                <dd class="col-sm-6"><?php echo htmlspecialchars($account['account_number']); ?></dd>

                                <dt class="col-sm-6">Status</dt>
                                <dd class="col-sm-6">
                                    <span class="badge bg-success"><?php echo ucfirst($account['status']); ?></span>
                                </dd>

                                <dt class="col-sm-6">Opened</dt>
                                <dd class="col-sm-6"><?php echo date('M d, Y', strtotime($account['opened_date'])); ?></dd>

                                <dt class="col-sm-6">Transactions</dt>
                                <dd class="col-sm-6"><?php echo $txnInfo['transaction_count']; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php else: ?>
            <div class="alert alert-info">No savings accounts found.</div>
        <?php endif; ?>
    </div>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
