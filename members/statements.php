<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$db = getDB();

// Get member statement based on search
$member_id = $_GET['member_id'] ?? null;
$member = null;
$statements = [];

if ($member_id) {
    // Get member details
    $stmt = $db->prepare("SELECT * FROM members WHERE member_id = ?");
    $stmt->execute([$member_id]);
    $member = $stmt->fetch();

    if ($member) {
        // Get member's savings transactions
        $stmt = $db->prepare("
            SELECT 
                st.*,
                sa.account_number,
                sa.account_type
            FROM savings_transactions st
            JOIN savings_accounts sa ON st.account_id = sa.account_id
            WHERE sa.member_id = ?
            ORDER BY st.transaction_date DESC
        ");
        $stmt->execute([$member_id]);
        $statements = $stmt->fetchAll();
    }
}

// Get all members for dropdown
$stmt = $db->query("SELECT member_id, membership_no, full_name FROM members WHERE status = 'active' ORDER BY full_name");
$all_members = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Statements - <?php echo APP_NAME; ?></title>
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Member Statements</h1>
                    <?php if ($member): ?>
                    <button onclick="window.print()" class="btn btn-secondary">
                        <i class="bi bi-printer"></i> Print Statement
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Member Selection -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form method="GET">
                            <div class="row">
                                <div class="col-md-8">
                                    <label class="form-label">Select Member:</label>
                                    <select name="member_id" class="form-select" required>
                                        <option value="">Choose a member...</option>
                                        <?php foreach ($all_members as $mem): ?>
                                        <option value="<?php echo $mem['member_id']; ?>" <?php echo $member_id == $mem['member_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($mem['full_name'] . ' (' . $mem['membership_no'] . ')'); ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-4" style="padding-top: 32px;">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="bi bi-search"></i> Generate Statement
                                    </button>
                                </div>
                            </div>
                        </form>
                    </div>
                </div>

                <?php if ($member): ?>
                <!-- Member Info -->
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Member Information</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Name:</strong> <?php echo htmlspecialchars($member['full_name']); ?></p>
                                <p><strong>Membership No:</strong> <?php echo htmlspecialchars($member['membership_no']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($member['phone']); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($member['email'] ?: 'N/A'); ?></p>
                                <p><strong>Status:</strong> <span class="badge bg-success"><?php echo ucfirst($member['status']); ?></span></p>
                                <p><strong>Member Since:</strong> <?php echo date('F j, Y', strtotime($member['created_at'])); ?></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Transactions Statement -->
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h5 class="mb-0">Transaction History</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($statements)): ?>
                        <div class="table-responsive">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Account</th>
                                        <th>Type</th>
                                        <th>Description</th>
                                        <th>Amount</th>
                                        <th>Balance</th>
                                        <th>Receipt</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($statements as $stmt_row): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($stmt_row['transaction_date'])); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($stmt_row['account_number']); ?>
                                            <br><small class="text-muted"><?php echo ucfirst(str_replace('_', ' ', $stmt_row['account_type'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="badge <?php echo $stmt_row['transaction_type'] === 'deposit' ? 'bg-success' : 'bg-warning'; ?>">
                                                <?php echo ucfirst($stmt_row['transaction_type']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($stmt_row['description']); ?></td>
                                        <td><?php echo formatMoney($stmt_row['amount']); ?></td>
                                        <td><?php echo formatMoney($stmt_row['balance_after']); ?></td>
                                        <td><?php echo htmlspecialchars($stmt_row['receipt_no']); ?></td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                        <?php else: ?>
                        <div class="text-center py-4">
                            <i class="bi bi-inbox display-4 text-muted"></i>
                            <p class="text-muted mt-2">No transactions found for this member.</p>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
