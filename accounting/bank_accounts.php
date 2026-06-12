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

    $bankName = trim($_POST['bank_name'] ?? '');
    $accountNumber = trim($_POST['account_number'] ?? '');
    $branch = trim($_POST['branch'] ?? '');
    $currency = trim($_POST['currency'] ?? 'UGX');

    if ($bankName === '' || $accountNumber === '') {
        $errors[] = 'Bank name and account number are required.';
    } else {
        $stmt = $db->prepare('INSERT INTO bank_accounts (bank_name, account_number, branch, currency, created_by) VALUES (?, ?, ?, ?, ?)');
        try {
            $stmt->execute([$bankName, $accountNumber, $branch ?: null, $currency, $_SESSION['user_id']]);
            $success = 'Bank account saved successfully.';
        } catch (Exception $e) {
            $errors[] = 'Unable to save bank account: ' . $e->getMessage();
        }
    }
}

$banks = [];
try {
    $stmt = $db->query('SELECT bank_account_id, bank_name, account_number, branch, currency, is_active, created_at FROM bank_accounts ORDER BY created_at DESC');
    $banks = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errors[] = 'Failed to load bank account list: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bank Accounts - <?php echo APP_NAME; ?></title>
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
                    <h1 class="h4 mb-0">Bank Accounts</h1>
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
                    <div class="card-header">Create Bank Account</div>
                    <div class="card-body">
                        <form method="post" class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label">Bank Name</label>
                                <input type="text" name="bank_name" class="form-control" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Account Number</label>
                                <input type="text" name="account_number" class="form-control" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">Branch</label>
                                <input type="text" name="branch" class="form-control">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">Currency</label>
                                <input type="text" name="currency" class="form-control" value="UGX">
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-success">Save Bank Account</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Bank</th>
                                    <th>Account</th>
                                    <th>Branch</th>
                                    <th>Currency</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($banks)): ?>
                                    <tr><td colspan="6" class="text-center">No bank accounts found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($banks as $bank): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($bank['bank_name']); ?></td>
                                            <td><?php echo htmlspecialchars($bank['account_number']); ?></td>
                                            <td><?php echo htmlspecialchars($bank['branch']); ?></td>
                                            <td><?php echo htmlspecialchars($bank['currency']); ?></td>
                                            <td><?php echo $bank['is_active'] ? 'Active' : 'Inactive'; ?></td>
                                            <td><?php echo htmlspecialchars(formatDate($bank['created_at'], 'Y-m-d')); ?></td>
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
