<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'officer']);

$db = Database::getInstance()->getConnection();

// Filters
$status = $_GET['status'] ?? '';
$joinDateFrom = $_GET['join_date_from'] ?? date('Y-m-01', strtotime('-1 year'));
$joinDateTo = $_GET['join_date_to'] ?? date('Y-m-d');

$query = "SELECT * FROM members WHERE 1=1";
$params = [];

if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
}

if (!empty($joinDateFrom) && !empty($joinDateTo)) {
    $query .= " AND DATE(join_date) BETWEEN ? AND ?";
    $params[] = $joinDateFrom;
    $params[] = $joinDateTo;
}

$query .= " ORDER BY join_date DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();

// Summary
$totalActive = 0;
$totalSavings = 0;
foreach ($members as $member) {
    if ($member['status'] === 'active') $totalActive++;
    
    $accStmt = $db->prepare("SELECT SUM(balance) as total FROM savings_accounts WHERE member_id = ? AND status = 'active'");
    $accStmt->execute([$member['member_id']]);
    $acc = $accStmt->fetch();
    $totalSavings += $acc['total'] ?? 0;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members List Report - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php"><?php echo APP_NAME; ?></a>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <!-- Filters -->
        <div class="card mb-3">
            <div class="card-body">
                <form method="GET" class="row g-3">
                    <div class="col-md-3">
                        <label>Status:</label>
                        <select name="status" class="form-select">
                            <option value="">All Status</option>
                            <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                            <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                            <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label>From:</label>
                        <input type="date" name="join_date_from" class="form-control" value="<?php echo $joinDateFrom; ?>">
                    </div>
                    <div class="col-md-3">
                        <label>To:</label>
                        <input type="date" name="join_date_to" class="form-control" value="<?php echo $joinDateTo; ?>">
                    </div>
                    <div class="col-md-3" style="padding-top: 32px;">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Summary -->
        <div class="row mb-3">
            <div class="col-md-4">
                <div class="card text-white bg-primary">
                    <div class="card-body">
                        <h5>Total Members</h5>
                        <h3><?php echo count($members); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-success">
                    <div class="card-body">
                        <h5>Active Members</h5>
                        <h3><?php echo $totalActive; ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card text-white bg-info">
                    <div class="card-body">
                        <h5>Total Savings</h5>
                        <h3><?php echo formatMoney($totalSavings); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Members Table -->
        <div class="card">
            <div class="card-header bg-primary text-white d-flex justify-content-between">
                <h5 class="mb-0">Members Report</h5>
                <button onclick="window.print()" class="btn btn-sm btn-light">Print</button>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover table-sm">
                        <thead class="table-light">
                            <tr>
                                <th>No</th>
                                <th>Membership No</th>
                                <th>Full Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Join Date</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $count = 1; ?>
                            <?php foreach ($members as $member): ?>
                            <tr>
                                <td><?php echo $count++; ?></td>
                                <td><?php echo htmlspecialchars($member['membership_no']); ?></td>
                                <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                <td><?php echo htmlspecialchars($member['email'] ?? ''); ?></td>
                                <td><?php echo date('M d, Y', strtotime($member['join_date'])); ?></td>
                                <td><span class="badge bg-<?php echo $member['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($member['status']); ?></span></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <p class="text-muted mt-3">Report generated: <?php echo date('M d, Y H:i'); ?></p>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
