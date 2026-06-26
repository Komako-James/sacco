<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'officer', 'finance']);

$db = Database::getInstance()->getConnection();

$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset = ($page - 1) * ITEMS_PER_PAGE;
$status = $_GET['status'] ?? '';
$search = trim($_GET['search'] ?? '');

$whereClauses = [];
$params = [];
$batches = [];
$total = 0;
$totalPages = 1;
$batchTablesAvailable = true;
$warningMessage = '';

if (!empty($status)) {
    $whereClauses[] = 'sdb.status = ?';
    $params[] = $status;
}

if (!empty($search)) {
    $whereClauses[] = "(sdb.batch_reference LIKE ? OR e.employer_name LIKE ? OR sdb.file_name LIKE ? OR DATE_FORMAT(sdb.batch_month, '%Y-%m') LIKE ? )";
    $term = "%{$search}%";
    $params = array_merge($params, [$term, $term, $term, $term]);
}

$whereSql = '';
if (!empty($whereClauses)) {
    $whereSql = ' AND ' . implode(' AND ', $whereClauses);
}

try {
    $countQuery = "SELECT COUNT(*) AS total FROM salary_deduction_batches sdb LEFT JOIN employers e ON sdb.employer_id = e.employer_id WHERE 1=1" . $whereSql;
    $countStmt = $db->prepare($countQuery);
    $countStmt->execute($params);
    $total = $countStmt->fetch()['total'];
    $totalPages = max(1, ceil($total / ITEMS_PER_PAGE));

    $query = "SELECT sdb.*, e.employer_name, u.full_name AS uploaded_by_name FROM salary_deduction_batches sdb LEFT JOIN employers e ON sdb.employer_id = e.employer_id LEFT JOIN users u ON sdb.uploaded_by = u.user_id WHERE 1=1" . $whereSql . " ORDER BY sdb.created_at DESC LIMIT ? OFFSET ?";
    $paramsWithPaging = array_merge($params, [ITEMS_PER_PAGE, $offset]);

    $stmt = $db->prepare($query);
    $stmt->execute($paramsWithPaging);
    $batches = $stmt->fetchAll();
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Base table or view not found') !== false) {
        $batchTablesAvailable = false;
        $warningMessage = 'The salary batch tables are not initialized. Please run the required database migration or create the table <code>salary_deduction_batches</code> in the database.';
    } else {
        throw $e;
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Batch Uploads - <?php echo APP_NAME; ?></title>
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
                    <h1 class="h4 mb-0">Uploaded Batches</h1>
                    <a href="upload.php" class="btn btn-primary">Upload New Batch</a>
                </div>

                <?php if (!$batchTablesAvailable): ?>
                <div class="alert alert-warning">
                    <?php echo $warningMessage; ?>
                </div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-body row gy-2">
                        <div class="col-md-5">
                            <input type="text" id="search" class="form-control" placeholder="Search batch reference, employer, file name..." value="<?php echo htmlspecialchars($search); ?>" <?php echo $batchTablesAvailable ? '' : 'disabled'; ?>>
                        </div>
                        <div class="col-md-3">
                            <select id="status" class="form-select" <?php echo $batchTablesAvailable ? '' : 'disabled'; ?>>
                                <option value="">All Statuses</option>
                                <option value="uploaded" <?php echo $status === 'uploaded' ? 'selected' : ''; ?>>Uploaded</option>
                                <option value="processing" <?php echo $status === 'processing' ? 'selected' : ''; ?>>Processing</option>
                                <option value="processed" <?php echo $status === 'processed' ? 'selected' : ''; ?>>Processed</option>
                                <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button class="btn btn-outline-primary w-100" onclick="applyFilters()" <?php echo $batchTablesAvailable ? '' : 'disabled'; ?>>Filter</button>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <?php if (!$batchTablesAvailable): ?>
                        <div class="card-body">
                            <p class="text-muted mb-0">Batch listing is unavailable while the salary batch tables are not initialized.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Batch Ref</th>
                                        <th>Employer</th>
                                        <th>Month</th>
                                        <th>File</th>
                                        <th>Records</th>
                                        <th>Amount</th>
                                        <th>Status</th>
                                        <th>Uploaded By</th>
                                        <th>Uploaded On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($batches)): ?>
                                        <tr><td colspan="9" class="text-center">No batches found.</td></tr>
                                    <?php else: ?>
                                        <?php foreach ($batches as $batch): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($batch['batch_reference']); ?></td>
                                                <td><?php echo htmlspecialchars($batch['employer_name'] ?: 'Unknown'); ?></td>
                                                <td><?php echo date('F Y', strtotime($batch['batch_month'])); ?></td>
                                                <td><?php echo htmlspecialchars($batch['file_name']); ?></td>
                                                <td><?php echo (int)$batch['total_records']; ?></td>
                                                <td><?php echo formatMoney($batch['total_amount']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $batch['status'] === 'failed' ? 'danger' : ($batch['status'] === 'processed' ? 'success' : ($batch['status'] === 'processing' ? 'info' : 'secondary')); ?>">
                                                        <?php echo ucfirst($batch['status']); ?>
                                                    </span>
                                                </td>
                                                <td><?php echo htmlspecialchars($batch['uploaded_by_name'] ?? 'System'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($batch['created_at'])); ?></td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>

                <nav aria-label="Page navigation" class="mt-3">
                    <ul class="pagination justify-content-center">
                        <?php if ($totalPages > 1): ?>
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                    <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>"><?php echo $i; ?></a>
                                </li>
                            <?php endfor; ?>
                        <?php endif; ?>
                    </ul>
                </nav>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        function applyFilters() {
            const search = document.getElementById('search').value;
            const status = document.getElementById('status').value;
            window.location.href = `?search=${encodeURIComponent(search)}&status=${encodeURIComponent(status)}`;
        }
    </script>
</body>
</html>
