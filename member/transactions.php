<?php
/**
 * Member Transactions Page
 */

require_once '../member/auth-middleware.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';

requireMemberLogin();

$member = getMemberData();

$db = getDB();

$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$perPage = 20;
$offset = ($page - 1) * $perPage;

$stmt = $db->prepare("
    SELECT st.*, sa.account_type
    FROM savings_transactions st
    JOIN savings_accounts sa ON st.account_id = sa.account_id
    WHERE sa.member_id = ?
    ORDER BY st.transaction_date DESC
    LIMIT ? OFFSET ?
");
$stmt->execute([$member['member_id'], $perPage, $offset]);
$transactions = $stmt->fetchAll(PDO::FETCH_ASSOC);

$stmt = $db->prepare("SELECT COUNT(*) as total FROM savings_transactions st JOIN savings_accounts sa ON st.account_id = sa.account_id WHERE sa.member_id = ?");
$stmt->execute([$member['member_id']]);
$total = $stmt->fetch()['total'];
$pages = ceil($total / $perPage);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Member Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="member-content">
        <div class="container-fluid p-4">
            <div class="mb-4">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <h2>Transactions</h2>
            </div>

            <?php if (!empty($transactions)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Date</th>
                            <th>Account</th>
                            <th>Type</th>
                            <th>Amount</th>
                            <th>Balance</th>
                            <th>Reference</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transactions as $txn): ?>
                        <tr>
                            <td><?php echo date('M d, Y H:i', strtotime($txn['transaction_date'])); ?></td>
                            <td><?php echo ucfirst($txn['account_type']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $txn['transaction_type'] === 'deposit' ? 'success' : 'warning'; ?>">
                                    <?php echo ucfirst($txn['transaction_type']); ?>
                                </span>
                            </td>
                            <td><?php echo formatMoney($txn['amount']); ?></td>
                            <td><?php echo formatMoney($txn['balance_after']); ?></td>
                            <td><?php echo htmlspecialchars($txn['reference_number']); ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($pages > 1): ?>
            <nav>
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">Previous</a></li>
                    <?php endif; ?>

                    <?php for ($i = 1; $i <= $pages; $i++): ?>
                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($page < $pages): ?>
                    <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">Next</a></li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>

            <?php else: ?>
            <div class="alert alert-info">No transactions found.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
