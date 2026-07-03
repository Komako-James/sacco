<?php
/**
 * Member Loans Page
 */

require_once __DIR__ . '/auth-middleware.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../includes/functions.php';

requireMemberLogin();

$member = getMemberData();
$loans = getMemberLoans();

$db = getDB();

$pageTitle = 'My Loans - Member Portal';
require_once __DIR__ . '/layout/header.php';
?>
    <div class="container-fluid py-4">
            <div class="mb-4">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <h2>My Loans</h2>
            </div>

            <?php if (!empty($loans)): ?>
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Loan Reference</th>
                            <th>Product</th>
                            <th>Amount</th>
                            <th>Outstanding</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($loans as $loan): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($loan['loan_reference']); ?></td>
                            <td><?php echo htmlspecialchars($loan['product_id']); ?></td>
                            <td><?php echo formatMoney($loan['loan_amount']); ?></td>
                            <td><?php echo formatMoney($loan['outstanding_balance']); ?></td>
                            <td>
                                <span class="badge bg-<?php echo $loan['status'] === 'disbursed' ? 'success' : 'info'; ?>">
                                    <?php echo ucfirst($loan['status']); ?>
                                </span>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary" onclick="showLoanDetails(<?php echo $loan['loan_id']; ?>)">
                                    View Details
                                </button>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
                <div class="alert alert-info">You have no active loans.</div>
            <?php endif; ?>
        </div>
    </div>

    <script>
    function showLoanDetails(loanId) {
        // Placeholder for loan details modal
        alert('Loan details for ID: ' + loanId);
    }
    </script>
<?php require_once __DIR__ . '/layout/footer.php'; ?>
