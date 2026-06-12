<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';
require_once '../config/db_connection.php';

$auth->requireLogin();
$auth->requirePermission('accounting.view');

$db = getDB();
$errors = [];
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$auth->can('accounting.create')) {
        http_response_code(403);
        include '../403.php';
        exit();
    }

    $accountCode = trim($_POST['account_code'] ?? '');
    $accountName = trim($_POST['account_name'] ?? '');
    $accountType = trim($_POST['account_type'] ?? '');
    $accountCategory = trim($_POST['account_category'] ?? '');

    if ($accountCode === '' || $accountName === '' || $accountType === '') {
        $errors[] = 'Account code, name and type are required.';
    } else {
        $stmt = $db->prepare('INSERT INTO chart_of_accounts (account_code, account_name, account_type, account_category, created_by) VALUES (?, ?, ?, ?, ?)');
        try {
            $stmt->execute([$accountCode, $accountName, $accountType, $accountCategory ?: null, $_SESSION['user_id']]);
            $success = 'Chart of account entry created successfully.';
        } catch (Exception $e) {
            $errors[] = 'Could not create account: ' . $e->getMessage();
        }
    }
}

$accounts = [];
try {
    $stmt = $db->query('SELECT account_code, account_name, account_type, account_category, is_active, created_at FROM chart_of_accounts ORDER BY account_type, account_code');
    $accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errors[] = 'Failed to load chart of accounts: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Chart of Accounts - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-2 d-none d-md-block">
                <?php include '../includes/sidebar.php'; ?>
            </div>
            <main class="col-md-10 ms-sm-auto px-md-4 main-content">
                <div class="d-flex justify-content-between align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h4 mb-0">Chart of Accounts</h1>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                                <li><?php echo htmlspecialchars($error); ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                <?php endif; ?>

                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">Add New Account</div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <div class="col-md-3">
                                <label class="form-label">Account Code</label>
                                <input type="text" name="account_code" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Account Name</label>
                                <input type="text" name="account_name" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Account Type</label>
                                <select name="account_type" class="form-select" required>
                                    <option value="">Select type</option>
                                    <option value="asset">Asset</option>
                                    <option value="liability">Liability</option>
                                    <option value="equity">Equity</option>
                                    <option value="income">Income</option>
                                    <option value="expense">Expense</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Category</label>
                                <input type="text" name="account_category" class="form-control">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">Create Account</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($accounts)): ?>
                                    <tr><td colspan="6" class="text-center">No chart of accounts found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($accounts as $account): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($account['account_code']); ?></td>
                                            <td><?php echo htmlspecialchars($account['account_name']); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($account['account_type'])); ?></td>
                                            <td><?php echo htmlspecialchars($account['account_category']); ?></td>
                                            <td><?php echo $account['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                            <td><?php echo htmlspecialchars(formatDate($account['created_at'], 'Y-m-d')); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </main>
        </div>
    </div>
</body>
</html>
