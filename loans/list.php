<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'officer', 'finance']);

$db = Database::getInstance()->getConnection();

// Pagination
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Filters
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';

$query = "
    SELECT l.*, m.full_name, m.membership_no
    FROM loans l
    JOIN members m ON l.member_id = m.member_id
    WHERE 1=1
";
$params = [];

if (!empty($status)) {
    $query .= " AND l.status = ?";
    $params[] = $status;
}

if (!empty($search)) {
    $query .= " AND (m.full_name LIKE ? OR m.membership_no LIKE ? OR l.loan_reference LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM loans l JOIN members m ON l.member_id = m.member_id WHERE 1=1" . ($status ? " AND l.status = ?" : "") . ($search ? " AND (m.full_name LIKE ? OR m.membership_no LIKE ? OR l.loan_reference LIKE ?)" : ""));
$countStmt->execute($params);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / ITEMS_PER_PAGE);

// Get loans
$query .= " ORDER BY l.created_at DESC LIMIT ? OFFSET ?";
$params[] = ITEMS_PER_PAGE;
$params[] = $offset;

$stmt = $db->prepare($query);
$stmt->execute($params);
$loans = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loans - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard.php"><?php echo APP_NAME; ?></a>
            <div class="collapse navbar-collapse ms-auto">
                <ul class="navbar-nav">
                    <li class="nav-item"><a class="nav-link" href="../dashboard.php">Dashboard</a></li>
                    <li class="nav-item"><a class="nav-link active" href="#">Loans</a></li>
                    <li class="nav-item"><a class="nav-link" href="../logout.php">Logout</a></li>
                </ul>
            </div>
        </div>
    </nav>

    <div class="container-fluid mt-4">
        <div class="card">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">Loans Management</h4>
            </div>
            <div class="card-body">
                <div class="row mb-3">
                    <div class="col-md-5">
                        <input type="text" class="form-control" id="search" placeholder="Search member or loan reference...">
                    </div>
                    <div class="col-md-4">
                        <select class="form-select" id="status">
                            <option value="">All Status</option>
                            <option value="application">Application</option>
                            <option value="approved">Approved</option>
                            <option value="disbursed">Disbursed</option>
                            <option value="completed">Completed</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <a href="../members/list.php" class="btn btn-primary w-100">Apply Loan</a>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Reference</th>
                                <th>Member</th>
                                <th>Amount</th>
                                <th>Outstanding</th>
                                <th>Status</th>
                                <th>Applied Date</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($loans as $loan): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($loan['loan_reference']); ?></td>
                                <td><?php echo htmlspecialchars($loan['full_name']); ?></td>
                                <td><?php echo formatMoney($loan['loan_amount']); ?></td>
                                <td><?php echo formatMoney($loan['outstanding_balance']); ?></td>
                                <td>
                                    <span class="badge bg-<?php echo match($loan['status']) {
                                        'application' => 'warning',
                                        'approved' => 'info',
                                        'disbursed' => 'primary',
                                        'completed' => 'success',
                                        'rejected' => 'danger',
                                        default => 'secondary'
                                    }; ?>">
                                        <?php echo ucfirst($loan['status']); ?>
                                    </span>
                                </td>
                                <td><?php echo date('M d, Y', strtotime($loan['created_at'])); ?></td>
                                <td>
                                    <?php if ($loan['status'] === 'application'): ?>
                                    <a href="approve.php?id=<?php echo $loan['loan_id']; ?>" class="btn btn-sm btn-success">Approve</a>
                                    <?php elseif ($loan['status'] === 'approved'): ?>
                                    <button class="btn btn-sm btn-primary" disabled>Disburse</button>
                                    <?php elseif ($loan['status'] === 'disbursed'): ?>
                                    <a href="repay.php?id=<?php echo $loan['loan_id']; ?>" class="btn btn-sm btn-warning">Repay</a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <nav>
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === (int)$page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&status=<?php echo urlencode($status); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
