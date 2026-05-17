<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();

$memberId = $_GET['id'] ?? $_SESSION['member_id'] ?? null;
if (!$memberId) {
    header('Location: list.php');
    exit();
}

$db = Database::getInstance()->getConnection();

// Get member
$stmt = $db->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    header('Location: list.php');
    exit();
}

// Get date range
$startDate = $_GET['start_date'] ?? date('Y-m-01');
$endDate = $_GET['end_date'] ?? date('Y-m-d');
$accountId = $_GET['account_id'] ?? null;

// Get accounts
$stmt = $db->prepare("SELECT * FROM savings_accounts WHERE member_id = ?");
$stmt->execute([$memberId]);
$accounts = $stmt->fetchAll();

// Get transactions
$query = "
    SELECT st.*, sa.account_number, sa.account_type
    FROM savings_transactions st
    JOIN savings_accounts sa ON st.account_id = sa.account_id
    WHERE sa.member_id = ?
    AND DATE(st.transaction_date) BETWEEN ? AND ?
";
$params = [$memberId, $startDate, $endDate];

if ($accountId) {
    $query .= " AND st.account_id = ?";
    $params[] = $accountId;
}

$query .= " ORDER BY st.transaction_date DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$transactions = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Statement - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php"><?php echo APP_NAME; ?></a>
            <div class="collapse navbar-collapse ms-auto">
                <a href="../logout.php" class="nav-link">Logout</a>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="row mb-3">
            <div class="col-md-12">
                <a href="view.php?id=<?php echo $memberId; ?>" class="btn btn-secondary">← Back</a>
                <button class="btn btn-primary float-end" onclick="window.print()">Print Statement</button>
            </div>
        </div>

        <div class="card">
            <div class="card-body">
                <h3 class="text-center mb-4"><?php echo APP_NAME; ?> - Member Statement</h3>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <p><strong>Member:</strong> <?php echo htmlspecialchars($member['full_name']); ?></p>
                        <p><strong>Membership No:</strong> <?php echo htmlspecialchars($member['membership_no']); ?></p>
                    </div>
                    <div class="col-md-6 text-end">
                        <p><strong>Statement Date:</strong> <?php echo date('M d, Y'); ?></p>
                        <p><strong>Period:</strong> <?php echo date('M d, Y', strtotime($startDate)); ?> to <?php echo date('M d, Y', strtotime($endDate)); ?></p>
                    </div>
                </div>

                <form method="GET" class="mb-3" id="filterForm">
                    <input type="hidden" name="id" value="<?php echo $memberId; ?>">
                    <div class="row">
                        <div class="col-md-3">
                            <label>Account:</label>
                            <select name="account_id" class="form-select">
                                <option value="">All Accounts</option>
                                <?php foreach ($accounts as $acc): ?>
                                <option value="<?php echo $acc['account_id']; ?>" <?php echo $accountId == $acc['account_id'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($acc['account_number']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label>From:</label>
                            <input type="date" name="start_date" class="form-control" value="<?php echo $startDate; ?>">
                        </div>
                        <div class="col-md-3">
                            <label>To:</label>
                            <input type="date" name="end_date" class="form-control" value="<?php echo $endDate; ?>">
                        </div>
                        <div class="col-md-3">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                    </div>
                </form>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Date</th>
                                <th>Receipt No</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Balance</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($transactions as $trans): ?>
                            <tr>
                                <td><?php echo date('M d, Y H:i', strtotime($trans['transaction_date'])); ?></td>
                                <td><?php echo htmlspecialchars($trans['receipt_no'] ?? 'N/A'); ?></td>
                                <td><?php echo ucfirst(str_replace('_', ' ', $trans['transaction_type'])); ?></td>
                                <td><?php echo htmlspecialchars($trans['description'] ?? ''); ?></td>
                                <td class="text-end">
                                    <?php echo in_array($trans['transaction_type'], ['withdrawal', 'transfer_out']) ? formatMoney($trans['amount']) : '-'; ?>
                                </td>
                                <td class="text-end">
                                    <?php echo !in_array($trans['transaction_type'], ['withdrawal', 'transfer_out']) ? formatMoney($trans['amount']) : '-'; ?>
                                </td>
                                <td class="text-end"><?php echo formatMoney($trans['balance_after']); ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <?php if (empty($transactions)): ?>
                <p class="text-muted text-center mt-4">No transactions found for the selected period</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
