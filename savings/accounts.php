<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';

$auth->requireLogin();
$db = getDB();

// List all savings accounts
$stmt = $db->query("
    SELECT sa.*, m.full_name, m.membership_no 
    FROM savings_accounts sa 
    JOIN members m ON sa.member_id = m.member_id 
    ORDER BY sa.created_at DESC
");
$accounts = $stmt->fetchAll();
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
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Savings Accounts</h1>
                    <a href="open.php" class="btn btn-primary">
                        <i class="bi bi-plus"></i> Open New Account
                    </a>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped">
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
                                    <?php foreach ($accounts as $account): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($account['account_number']); ?></td>
                                        <td>
                                            <?php echo htmlspecialchars($account['full_name']); ?>
                                            <br><small class="text-muted"><?php echo htmlspecialchars($account['membership_no']); ?></small>
                                        </td>
                                        <td><?php echo ucfirst(str_replace('_', ' ', $account['account_type'])); ?></td>
                                        <td><?php echo formatMoney($account['balance']); ?></td>
                                        <td>
                                            <span class="badge <?php echo $account['status'] === 'active' ? 'bg-success' : 'bg-danger'; ?>">
                                                <?php echo ucfirst($account['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo date('M d, Y', strtotime($account['created_at'])); ?></td>
                                        <td>
                                            <a href="deposit.php?account_id=<?php echo $account['account_id']; ?>" class="btn btn-sm btn-success">Deposit</a>
                                            <a href="withdraw.php?account_id=<?php echo $account['account_id']; ?>" class="btn btn-sm btn-warning">Withdraw</a>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
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
