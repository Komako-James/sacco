<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'officer', 'cashier']);

$db = Database::getInstance()->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $membershipNo = $_POST['membership_no'] ?? '';
    $accountId = $_POST['account_id'] ?? '';
    $amount = floatval($_POST['amount'] ?? 0);
    $paymentMethod = $_POST['payment_method'] ?? '';
    $referenceNo = $_POST['reference_no'] ?? '';
    
    // Validate
    if (empty($membershipNo) || empty($accountId) || $amount <= 0) {
        $error = 'Please fill all required fields';
    } else {
        try {
            $db->beginTransaction();
            
            // Get account
            $stmt = $db->prepare("SELECT * FROM savings_accounts WHERE account_id = ?");
            $stmt->execute([$accountId]);
            $account = $stmt->fetch();
            
            if (!$account || $account['status'] !== 'active') {
                throw new Exception('Invalid account');
            }
            
            if ($account['balance'] < $amount) {
                throw new Exception('Insufficient balance');
            }
            
            // Record withdrawal
            $receipt = generateReceiptNumber('WTH');
            $newBalance = $account['balance'] - $amount;
            
            $stmt = $db->prepare("
                INSERT INTO savings_transactions 
                (account_id, transaction_type, amount, balance_after, payment_method, reference_no, receipt_no, posted_by, status, transaction_date)
                VALUES (?, 'withdrawal', ?, ?, ?, ?, ?, ?, 'completed', NOW())
            ");
            $stmt->execute([
                $accountId,
                $amount,
                $newBalance,
                $paymentMethod,
                $referenceNo,
                $receipt,
                $_SESSION['user_id']
            ]);
            
            // Update account balance
            $stmt = $db->prepare("UPDATE savings_accounts SET balance = ? WHERE account_id = ?");
            $stmt->execute([$newBalance, $accountId]);
            
            $db->commit();
            $success = 'Withdrawal recorded successfully. Receipt: ' . $receipt;
        } catch (Exception $e) {
            $db->rollback();
            $error = 'Error: ' . $e->getMessage();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Savings Withdrawal - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php"><?php echo APP_NAME; ?></a>
        </div>
    </nav>

    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Record Savings Withdrawal</h4>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php elseif ($success): ?>
                        <div class="alert alert-success"><?php echo $success; ?></div>
                        <?php endif; ?>

                        <form method="POST">
                            <div class="mb-3">
                                <label for="membership_no" class="form-label">Membership Number</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="membership_no" name="membership_no" required>
                                    <button class="btn btn-outline-secondary" type="button" onclick="searchMember()">Search</button>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label for="account_id" class="form-label">Savings Account</label>
                                <select class="form-select" id="account_id" name="account_id" required>
                                    <option value="">Select account</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="amount" class="form-label">Withdrawal Amount</label>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" required placeholder="0.00">
                            </div>

                            <div class="mb-3">
                                <label for="payment_method" class="form-label">Payment Method</label>
                                <select class="form-select" id="payment_method" name="payment_method" required>
                                    <option value="">Select method</option>
                                    <option value="cash">Cash</option>
                                    <option value="mobile_money">Mobile Money</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="reference_no" class="form-label">Reference No</label>
                                <input type="text" class="form-control" id="reference_no" name="reference_no">
                            </div>

                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Record Withdrawal</button>
                                <a href="../dashboard.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function searchMember() {
            const membershipNo = document.getElementById('membership_no').value;
            if (!membershipNo) return;
            
            fetch(`../api/ajax_handler.php?action=get_member_accounts&membership_no=${membershipNo}`)
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.accounts) {
                        const select = document.getElementById('account_id');
                        select.innerHTML = '<option value="">Select account</option>';
                        data.accounts.forEach(acc => {
                            const opt = document.createElement('option');
                            opt.value = acc.account_id;
                            opt.text = `${acc.account_number} (${acc.account_type}) - Balance: ${acc.balance}`;
                            select.appendChild(opt);
                        });
                    }
                });
        }
    </script>
</body>
</html>
