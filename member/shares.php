<?php
/**
 * Member Shares Page
 */

require_once __DIR__ . '/auth-middleware.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../app/Services/ShareService.php';

requireMemberLogin();

$member = getMemberData();
$savings = getMemberSavings();
$shareService = new \SACCO\Services\ShareService();

$shareHolding = $shareService->getMemberShareHolding($member['member_id']);
$shareTransactions = $shareService->getMemberShareTransactions($member['member_id'], 20);
$message = '';
$error = '';

$transferMembershipNumber = '';
$sellShares = 0;
$sellSavingsAccountId = 0;
$transferShares = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? 'purchase';

    if ($action === 'purchase') {
        $savingsAccountId = isset($_POST['savings_account_id']) ? (int) $_POST['savings_account_id'] : 0;
        $sharesToBuy = isset($_POST['shares']) ? (int) $_POST['shares'] : 0;

        if ($savingsAccountId <= 0 || $sharesToBuy <= 0) {
            $error = 'Please select a savings account and enter a valid number of shares.';
        } else {
            $result = $shareService->purchaseSharesFromSavings($member['member_id'], $savingsAccountId, $sharesToBuy, $_SESSION['member_user_id']);
            if ($result['success']) {
                $message = $result['message'];
                $shareHolding = $shareService->getMemberShareHolding($member['member_id']);
                $shareTransactions = $shareService->getMemberShareTransactions($member['member_id'], 20);
            } else {
                $error = $result['message'];
            }
        }
    } elseif ($action === 'sell') {
        $sellSavingsAccountId = isset($_POST['sell_savings_account_id']) ? (int) $_POST['sell_savings_account_id'] : 0;
        $sellShares = isset($_POST['sell_shares']) ? (int) $_POST['sell_shares'] : 0;

        if ($sellSavingsAccountId <= 0 || $sellShares <= 0) {
            $error = 'Please select a savings account and enter a valid number of shares to sell.';
        } else {
            $result = $shareService->sellShares($member['member_id'], $sellSavingsAccountId, $sellShares, $_SESSION['member_user_id']);
            if ($result['success']) {
                $message = $result['message'];
                $shareHolding = $shareService->getMemberShareHolding($member['member_id']);
                $shareTransactions = $shareService->getMemberShareTransactions($member['member_id'], 20);
            } else {
                $error = $result['message'];
            }
        }
    } elseif ($action === 'transfer') {
        $transferMembershipNumber = trim($_POST['transfer_membership_number'] ?? '');
        $transferShares = isset($_POST['transfer_shares']) ? (int) $_POST['transfer_shares'] : 0;

        if ($transferShares <= 0 || empty($transferMembershipNumber)) {
            $error = 'Please enter a valid destination membership number and number of shares to transfer.';
        } else {
            $result = $shareService->transferSharesByMembershipNumber(
                $member['member_id'],
                $transferMembershipNumber,
                $transferShares,
                $_SESSION['member_user_id'],
                'Member initiated share transfer'
            );

            if ($result['success']) {
                $message = $result['message'];
                $shareHolding = $shareService->getMemberShareHolding($member['member_id']);
                $shareTransactions = $shareService->getMemberShareTransactions($member['member_id'], 20);
            } else {
                $error = $result['message'];
            }
        }
    }
}

$pageTitle = 'Shares - Member Portal';
require_once __DIR__ . '/layout/header.php';
?>
    <div class="container-fluid py-4">
            <div class="mb-4 d-flex flex-column flex-md-row justify-content-between align-items-start gap-3">
                <div>
                    <a href="dashboard.php" class="btn btn-sm btn-outline-secondary mb-3">
                        <i class="bi bi-arrow-left me-1"></i> Back
                    </a>
                    <h2>Shares</h2>
                    <p class="text-muted">Each share costs <?php echo formatMoney(SHARE_PRICE); ?> and can be purchased from your savings balance.</p>
                </div>
                <div>
                    <a href="shares-statement.php" class="btn btn-sm btn-primary mb-3">
                        <i class="bi bi-file-earmark-text me-1"></i> View Share Statement
                    </a>
                </div>
            </div>

                    <?php if ($message): ?>
                        <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

            <div class="row">
                <div class="col-lg-5 mb-4">
                    <div class="card">
                        <div class="card-header bg-warning text-dark">
                            <h5 class="mb-0"><i class="bi bi-collection me-2"></i>Your Share Holdings</h5>
                        </div>
                        <div class="card-body">
                            <dl class="row mb-0">
                                <dt class="col-sm-6">Total Shares</dt>
                                <dd class="col-sm-6"><strong><?php echo $shareHolding ? $shareHolding['shares_owned'] : 0; ?></strong></dd>

                                <dt class="col-sm-6">Share Price</dt>
                                <dd class="col-sm-6"><?php echo formatMoney(SHARE_PRICE); ?></dd>

                                <dt class="col-sm-6">Total Invested</dt>
                                <dd class="col-sm-6"><strong><?php echo formatMoney($shareHolding ? $shareHolding['total_invested'] : 0); ?></strong></dd>

                                <dt class="col-sm-6">Last Purchase</dt>
                                <dd class="col-sm-6"><?php echo $shareHolding && $shareHolding['last_purchase_date'] ? date('M d, Y H:i', strtotime($shareHolding['last_purchase_date'])) : 'N/A'; ?></dd>
                            </dl>
                        </div>
                    </div>
                </div>

                <div class="col-lg-7 mb-4">
                    <div class="card">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0"><i class="bi bi-currency-exchange me-2"></i>Purchase Shares</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" novalidate>
                                <input type="hidden" name="action" value="purchase">
                                <div class="mb-3">
                                    <label for="savings_account_id" class="form-label">Savings Account</label>
                                    <select id="savings_account_id" name="savings_account_id" class="form-select" required>
                                        <option value="">Select account</option>
                                        <?php foreach ($savings as $account): ?>
                                        <option value="<?php echo $account['account_id']; ?>">
                                            <?php echo htmlspecialchars($account['account_type']); ?> — <?php echo formatMoney($account['balance']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="shares" class="form-label">Number of Shares</label>
                                    <input type="number" id="shares" name="shares" min="1" class="form-control" placeholder="Enter share quantity" required>
                                    <div class="form-text">Each share costs <?php echo formatMoney(SHARE_PRICE); ?>.</div>
                                </div>
                                <button type="submit" class="btn btn-warning">Buy Shares</button>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0"><i class="bi bi-arrow-down-circle me-2"></i>Sell Shares</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" novalidate>
                                <input type="hidden" name="action" value="sell">
                                <div class="mb-3">
                                    <label for="sell_savings_account_id" class="form-label">Deposit To Savings</label>
                                    <select id="sell_savings_account_id" name="sell_savings_account_id" class="form-select" required>
                                        <option value="">Select account</option>
                                        <?php foreach ($savings as $account): ?>
                                        <option value="<?php echo $account['account_id']; ?>" <?php echo $sellSavingsAccountId === (int) $account['account_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($account['account_type']); ?> — <?php echo formatMoney($account['balance']); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="sell_shares" class="form-label">Number of Shares</label>
                                    <input type="number" id="sell_shares" name="sell_shares" min="1" class="form-control" placeholder="Enter share quantity to sell" value="<?php echo htmlspecialchars($sellShares); ?>" required>
                                    <div class="form-text">Sold shares will be credited to your savings account at <?php echo formatMoney(SHARE_PRICE); ?> per share.</div>
                                </div>
                                <button type="submit" class="btn btn-success">Sell Shares</button>
                            </form>
                        </div>
                    </div>

                    <div class="card mt-4">
                        <div class="card-header bg-info text-white">
                            <h5 class="mb-0"><i class="bi bi-arrow-left-right me-2"></i>Transfer Shares</h5>
                        </div>
                        <div class="card-body">
                            <form method="post" novalidate>
                                <input type="hidden" name="action" value="transfer">
                                <div class="mb-3">
                                    <label for="transfer_membership_number" class="form-label">Destination Member Number</label>
                                    <input type="text" id="transfer_membership_number" name="transfer_membership_number" class="form-control" placeholder="Enter membership number" value="<?php echo htmlspecialchars($transferMembershipNumber); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label for="transfer_shares" class="form-label">Number of Shares</label>
                                    <input type="number" id="transfer_shares" name="transfer_shares" min="1" class="form-control" placeholder="Enter share quantity" value="<?php echo htmlspecialchars($transferShares); ?>" required>
                                </div>
                                <button type="submit" class="btn btn-info text-white">Transfer Shares</button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0"><i class="bi bi-clock-history me-2"></i>Recent Share Transactions</h5>
                </div>
                <div class="card-body">
                    <?php if (!empty($shareTransactions)): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Shares</th>
                                    <th>Amount</th>
                                    <th>Reference</th>
                                    <th>Account</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($shareTransactions as $txn): ?>
                                <tr>
                                    <td><?php echo date('M d, Y H:i', strtotime($txn['transaction_date'])); ?></td>
                                    <td><?php echo htmlspecialchars(ucfirst($txn['transaction_type'])); ?></td>
                                    <td><?php echo htmlspecialchars($txn['shares']); ?></td>
                                    <td><?php echo formatMoney($txn['amount']); ?></td>
                                    <td><?php echo htmlspecialchars($txn['reference_number']); ?></td>
                                    <td><?php echo htmlspecialchars($txn['account_id']); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php else: ?>
                    <p class="text-muted mb-0">No share purchases yet.</p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
