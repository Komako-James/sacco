<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'officer']);

$db = Database::getInstance()->getConnection();

// Pagination
$page = $_GET['page'] ?? 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;

// Search and filter
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';

$query = "SELECT * FROM members WHERE 1=1";
$params = [];

if (!empty($search)) {
    $query .= " AND (full_name LIKE ? OR membership_no LIKE ? OR phone LIKE ?)";
    $searchTerm = "%$search%";
    $params = array_merge($params, [$searchTerm, $searchTerm, $searchTerm]);
}

if (!empty($status)) {
    $query .= " AND status = ?";
    $params[] = $status;
}

// Count total
$countStmt = $db->prepare("SELECT COUNT(*) as total FROM members WHERE 1=1" . (count($params) > 0 ? " AND (full_name LIKE ? OR membership_no LIKE ? OR phone LIKE ?)" . (!empty($status) ? " AND status = ?" : "") : ""));
$countStmt->execute($params);
$total = $countStmt->fetch()['total'];
$totalPages = ceil($total / ITEMS_PER_PAGE);

// Get members
$query .= " ORDER BY created_at DESC LIMIT ? OFFSET ?";
$params[] = ITEMS_PER_PAGE;
$params[] = $offset;

$stmt = $db->prepare($query);
$stmt->execute($params);
$members = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Members - <?php echo APP_NAME; ?></title>
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
                    <h1 class="h4 mb-0">Members List</h1>
                    <a href="register.php" class="btn btn-success">+ New Member</a>
                </div>

                <div class="card mb-4">
                    <div class="card-body">
                        <div class="row gy-2">
                            <div class="col-lg-6">
                                <input type="text" class="form-control" id="member-search" name="search" placeholder="Search by name, membership no, or phone..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                            <div class="col-lg-3">
                                <select class="form-select" id="status" name="status" onchange="window.location.href='?search=' + encodeURIComponent(document.getElementById('member-search').value) + '&status=' + encodeURIComponent(this.value)">
                                    <option value="">All Status</option>
                                    <option value="active" <?php echo $status === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $status === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo $status === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Membership No</th>
                                    <th>Full Name</th>
                                    <th>Phone</th>
                                    <th>Join Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="search-results">
                                <?php foreach ($members as $member): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($member['membership_no']); ?></td>
                                    <td><?php echo htmlspecialchars($member['full_name']); ?></td>
                                    <td><?php echo htmlspecialchars($member['phone']); ?></td>
                                    <td><?php echo date('M d, Y', strtotime($member['join_date'])); ?></td>
                                    <td>
                                        <span class="badge bg-<?php echo $member['status'] === 'active' ? 'success' : 'danger'; ?>">
                                            <?php echo ucfirst($member['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <a href="view.php?id=<?php echo $member['member_id']; ?>" class="btn btn-sm btn-info">View</a>
                                        <a href="statement.php?id=<?php echo $member['member_id']; ?>" class="btn btn-sm btn-secondary">Statement</a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $i === (int)$page ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                    </ul>
                </nav>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
