<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';

$auth->requirePermission('audit.view');

$db = getDB();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$filters = [
    'status' => trim($_GET['status'] ?? ''),
    'entity_type' => trim($_GET['entity_type'] ?? ''),
    'action' => trim($_GET['action'] ?? ''),
    'user' => trim($_GET['user'] ?? ''),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to' => trim($_GET['date_to'] ?? ''),
    'search' => trim($_GET['search'] ?? ''),
];

$whereSql = 'WHERE 1=1';
$params = [];

if ($filters['status'] === 'success' || $filters['status'] === 'failure') {
    $whereSql .= ' AND al.status = ?';
    $params[] = $filters['status'];
}

if ($filters['entity_type'] !== '') {
    $whereSql .= ' AND al.entity_type LIKE ?';
    $params[] = '%' . $filters['entity_type'] . '%';
}

if ($filters['action'] !== '') {
    $whereSql .= ' AND al.action LIKE ?';
    $params[] = '%' . $filters['action'] . '%';
}

if ($filters['user'] !== '') {
    $whereSql .= ' AND (u.username LIKE ? OR u.full_name LIKE ?)';
    $params[] = '%' . $filters['user'] . '%';
    $params[] = '%' . $filters['user'] . '%';
}

if ($filters['date_from'] !== '' && strtotime($filters['date_from']) !== false) {
    $whereSql .= ' AND al.timestamp >= ?';
    $params[] = date('Y-m-d 00:00:00', strtotime($filters['date_from']));
}

if ($filters['date_to'] !== '' && strtotime($filters['date_to']) !== false) {
    $whereSql .= ' AND al.timestamp <= ?';
    $params[] = date('Y-m-d 23:59:59', strtotime($filters['date_to']));
}

if ($filters['search'] !== '') {
    $whereSql .= ' AND (
        al.action LIKE ? OR
        al.entity_type LIKE ? OR
        al.table_name LIKE ? OR
        al.ip_address LIKE ? OR
        al.user_agent LIKE ? OR
        al.error_message LIKE ? OR
        al.old_values LIKE ? OR
        al.new_values LIKE ? OR
        u.username LIKE ? OR
        u.full_name LIKE ?
    )';
    $searchTerm = '%' . $filters['search'] . '%';
    for ($i = 0; $i < 10; $i++) {
        $params[] = $searchTerm;
    }
}

function formatAuditValue($value) {
    if ($value === null || $value === '') {
        return '—';
    }

    $decoded = json_decode($value, true);
    if (json_last_error() === JSON_ERROR_NONE) {
        $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        return $pretty ?: $value;
    }

    return $value;
}

function truncateText($text, $length = 120) {
    $text = trim($text);
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length - 3) . '...';
}

function buildQueryString(array $filters, array $overrides = []) {
    $query = [];
    foreach ($filters as $key => $value) {
        if ($value !== null && $value !== '') {
            $query[$key] = $value;
        }
    }
    foreach ($overrides as $key => $value) {
        $query[$key] = $value;
    }
    return http_build_query($query);
}

$export = ($_GET['export'] ?? '') === 'csv';

if ($export) {
    $exportStmt = $db->prepare(
        "SELECT al.*, COALESCE(u.full_name, u.username, 'System') AS performed_by, u.username AS performed_username
         FROM audit_logs al
         LEFT JOIN users u ON al.user_id = u.user_id
         {$whereSql}
         ORDER BY al.timestamp DESC"
    );
    $exportStmt->execute($params);
    $rows = $exportStmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="audit_logs_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');
    fputcsv($output, [
        'Timestamp', 'Status', 'Action', 'User ID', 'Performed By', 'Username', 'Entity Type', 'Entity ID', 'Table Name', 'Record ID', 'IP Address', 'User Agent', 'Error Message', 'Old Values', 'New Values'
    ]);

    foreach ($rows as $row) {
        fputcsv($output, [
            $row['timestamp'] ?? $row['created_at'] ?? '',
            $row['status'] ?? '',
            $row['action'] ?? '',
            $row['user_id'] ?? '',
            $row['performed_by'] ?? '',
            $row['performed_username'] ?? '',
            $row['entity_type'] ?? '',
            $row['entity_id'] ?? '',
            $row['table_name'] ?? '',
            $row['record_id'] ?? '',
            $row['ip_address'] ?? '',
            $row['user_agent'] ?? '',
            $row['error_message'] ?? '',
            $row['old_values'] ?? $row['old_data'] ?? '',
            $row['new_values'] ?? $row['new_data'] ?? ''
        ]);
    }

    fclose($output);
    exit();
}

$countSql = "SELECT COUNT(*) FROM audit_logs al LEFT JOIN users u ON al.user_id = u.user_id {$whereSql}";
$countStmt = $db->prepare($countSql);
$countStmt->execute($params);
$totalLogs = (int)$countStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalLogs / $perPage));

$dataSql = "SELECT al.*, COALESCE(u.full_name, u.username, 'System') AS performed_by, u.username AS performed_username
            FROM audit_logs al
            LEFT JOIN users u ON al.user_id = u.user_id
            {$whereSql}
            ORDER BY al.timestamp DESC
            LIMIT ? OFFSET ?";
$dataStmt = $db->prepare($dataSql);
$dataStmt->execute(array_merge($params, [$perPage, $offset]));
$logs = $dataStmt->fetchAll(PDO::FETCH_ASSOC);

$queryString = buildQueryString($filters);
$exportQueryString = buildQueryString($filters, ['export' => 'csv']);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <?php include __DIR__ . '/../includes/sidebar.php'; ?>

    <div class="container-fluid">
        <div class="row">
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between align-items-center pt-4 pb-3 border-bottom">
                    <div>
                        <h1 class="h2">Audit Logs</h1>
                        <p class="text-muted mb-0">View the system audit trail for user activity, entity changes, and operational events.</p>
                    </div>
                    <div>
                        <a href="?<?php echo htmlspecialchars($exportQueryString); ?>" class="btn btn-outline-primary">
                            <i class="bi bi-file-earmark-spreadsheet"></i> Export CSV
                        </a>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <form class="row g-3" method="GET">
                            <div class="col-md-2">
                                <label class="form-label">Status</label>
                                <select class="form-select" name="status">
                                    <option value="">All</option>
                                    <option value="success" <?php echo $filters['status'] === 'success' ? 'selected' : ''; ?>>Success</option>
                                    <option value="failure" <?php echo $filters['status'] === 'failure' ? 'selected' : ''; ?>>Failure</option>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Entity Type</label>
                                <input type="text" name="entity_type" class="form-control" placeholder="e.g. loans" value="<?php echo htmlspecialchars($filters['entity_type']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">Action</label>
                                <input type="text" name="action" class="form-control" placeholder="e.g. Create" value="<?php echo htmlspecialchars($filters['action']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">User</label>
                                <input type="text" name="user" class="form-control" placeholder="Username or name" value="<?php echo htmlspecialchars($filters['user']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">From</label>
                                <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($filters['date_from']); ?>">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">To</label>
                                <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($filters['date_to']); ?>">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Search</label>
                                <input type="search" name="search" class="form-control" placeholder="Search action, table, IP, error, values" value="<?php echo htmlspecialchars($filters['search']); ?>">
                            </div>
                            <div class="col-md-4 d-flex align-items-end justify-content-end">
                                <button type="submit" class="btn btn-primary me-2">Filter</button>
                                <a href="admin/audit_logs.php" class="btn btn-outline-secondary">Reset</a>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <strong><?php echo number_format($totalLogs); ?></strong> log entries found
                            </div>
                            <div class="text-muted">Page <?php echo $page; ?> of <?php echo $totalPages; ?></div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Timestamp</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                        <th>User</th>
                                        <th>Entity</th>
                                        <th>Record</th>
                                        <th>Table</th>
                                        <th>IP / Agent</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($logs)): ?>
                                        <tr>
                                            <td colspan="9" class="text-center text-muted">No audit entries match the selected filters.</td>
                                        </tr>
                                    <?php endif; ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['timestamp'] ?? $log['created_at'] ?? ''); ?></td>
                                            <td>
                                                <span class="badge bg-<?php echo $log['status'] === 'failure' ? 'danger' : 'success'; ?>">
                                                    <?php echo htmlspecialchars(ucfirst($log['status'] ?? 'success')); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['action'] ?? ''); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($log['performed_by'] ?? 'System'); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars($log['performed_username'] ?? ''); ?></small>
                                            </td>
                                            <td><?php echo htmlspecialchars($log['entity_type'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($log['entity_id'] ?? $log['record_id'] ?? ''); ?></td>
                                            <td><?php echo htmlspecialchars($log['table_name'] ?? ''); ?></td>
                                            <td>
                                                <?php echo htmlspecialchars($log['ip_address'] ?? ''); ?><br>
                                                <small class="text-muted"><?php echo htmlspecialchars(truncateText($log['user_agent'] ?? '', 60)); ?></small>
                                            </td>
                                            <td class="text-end">
                                                <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#auditDetailsModal<?php echo (int)$log['log_id']; ?>">
                                                    Details
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Audit log pagination">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?<?php echo htmlspecialchars(buildQueryString($filters, ['page' => $i])); ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>

                <?php foreach ($logs as $log): ?>
                    <div class="modal fade" id="auditDetailsModal<?php echo (int)$log['log_id']; ?>" tabindex="-1" aria-labelledby="auditDetailsLabel<?php echo (int)$log['log_id']; ?>" aria-hidden="true">
                        <div class="modal-dialog modal-xl modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="auditDetailsLabel<?php echo (int)$log['log_id']; ?>">Audit details for #<?php echo (int)$log['log_id']; ?></h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <strong>Timestamp</strong>
                                            <div><?php echo htmlspecialchars($log['timestamp'] ?? $log['created_at'] ?? ''); ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>Status</strong>
                                            <div><?php echo htmlspecialchars(ucfirst($log['status'] ?? 'success')); ?></div>
                                        </div>
                                        <div class="col-md-4">
                                            <strong>User</strong>
                                            <div><?php echo htmlspecialchars($log['performed_by'] ?? 'System'); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($log['performed_username'] ?? ''); ?></small>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-3">
                                            <strong>Action</strong>
                                            <div><?php echo htmlspecialchars($log['action'] ?? ''); ?></div>
                                        </div>
                                        <div class="col-md-3">
                                            <strong>Entity Type</strong>
                                            <div><?php echo htmlspecialchars($log['entity_type'] ?? ''); ?></div>
                                        </div>
                                        <div class="col-md-2">
                                            <strong>Entity ID</strong>
                                            <div><?php echo htmlspecialchars($log['entity_id'] ?? ''); ?></div>
                                        </div>
                                        <div class="col-md-2">
                                            <strong>Table</strong>
                                            <div><?php echo htmlspecialchars($log['table_name'] ?? ''); ?></div>
                                        </div>
                                        <div class="col-md-2">
                                            <strong>Record ID</strong>
                                            <div><?php echo htmlspecialchars($log['record_id'] ?? ''); ?></div>
                                        </div>
                                    </div>

                                    <div class="row mb-3">
                                        <div class="col-md-6">
                                            <strong>IP Address</strong>
                                            <div><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></div>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>User Agent</strong>
                                            <div><?php echo htmlspecialchars($log['user_agent'] ?? ''); ?></div>
                                        </div>
                                    </div>

                                    <?php if (!empty($log['error_message'])): ?>
                                        <div class="mb-3">
                                            <strong>Error message</strong>
                                            <pre class="bg-light p-3 rounded"><?php echo htmlspecialchars($log['error_message']); ?></pre>
                                        </div>
                                    <?php endif; ?>

                                    <div class="row">
                                        <div class="col-md-6">
                                            <strong>Old values</strong>
                                            <pre class="bg-light p-3 rounded"><?php echo htmlspecialchars(formatAuditValue($log['old_values'] ?? $log['old_data'] ?? '')); ?></pre>
                                        </div>
                                        <div class="col-md-6">
                                            <strong>New values</strong>
                                            <pre class="bg-light p-3 rounded"><?php echo htmlspecialchars(formatAuditValue($log['new_values'] ?? $log['new_data'] ?? '')); ?></pre>
                                        </div>
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
