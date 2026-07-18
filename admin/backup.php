<?php
<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../config/constants.php';
require_once __DIR__ . '/../app/Services/BackupService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

$service = new \SACCO\Services\BackupService();

$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = ITEMS_PER_PAGE;
$offset = ($page - 1) * $perPage;

$filters = [
    'filename' => trim($_GET['filename'] ?? ''),
    'description' => trim($_GET['description'] ?? ''),
    'creator' => trim($_GET['creator'] ?? ''),
    'status' => trim($_GET['status'] ?? ''),
    'compressed' => isset($_GET['compressed']) ? ($_GET['compressed'] === '1' ? 1 : 0) : '',
    'backup_type' => trim($_GET['backup_type'] ?? ''),
    'date_from' => trim($_GET['date_from'] ?? ''),
    'date_to' => trim($_GET['date_to'] ?? ''),
    'search' => trim($_GET['search'] ?? ''),
];

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'create_backup') {
    $name = trim($_POST['backup_name'] ?? '');
    $description = trim($_POST['backup_description'] ?? '');
    $compress = isset($_POST['compress']) && $_POST['compress'] === '1';
    $includeFiles = isset($_POST['include_files']) && $_POST['include_files'] === '1';
    $result = $service->createBackup($name, $description, $compress, $includeFiles, $_SESSION['user_id'] ?? 0, 'manual');
    if ($result['success']) {
        logActivity($_SESSION['user_id'] ?? null, 'Backup Created', 'backup_history', $result['backup_id'] ?? null, null, ['filename' => $result['filename'] ?? null]);
        $message = 'Backup created successfully.';
    } else {
        logActivity($_SESSION['user_id'] ?? null, 'Backup Creation Failed', 'backup_history', null, null, ['error' => $result['message'] ?? 'unknown']);
        $message = 'Backup failed: ' . ($result['message'] ?? 'Unknown error');
    }
}

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="backup_history_' . date('Ymd_His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Filename', 'Description', 'Created By', 'Type', 'Compressed', 'Filesize', 'Status', 'Protected', 'Checksum', 'Created At', 'Restored At', 'Deleted At']);
    $rows = $service->getBackups($filters, 10000, 0);
    foreach ($rows as $r) {
        fputcsv($out, [
            $r['id'], $r['filename'], $r['description'], $r['created_by_name'] ?? '', $r['backup_type'], $r['compressed'], $r['filesize'], $r['status'], $r['protected'], $r['checksum'], $r['created_at'], $r['restored_at'], $r['deleted_at']
        ]);
    }
    fclose($out);
    exit();
}

$total = $service->countBackups($filters);
$totalPages = max(1, (int)ceil($total / $perPage));
$backups = $service->getBackups($filters, $perPage, $offset);

function buildQuery(array $overrides = []) {
    $q = array_merge($_GET, $overrides);
    return http_build_query($q);
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>System Backup - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>.small-pre{white-space:pre-wrap;word-break:break-word;}</style>
</head>
<body>
    <?php include __DIR__ . '/../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include __DIR__ . '/../includes/sidebar.php'; ?>
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between align-items-center pt-4 pb-3 border-bottom">
                    <div>
                        <h1 class="h2">Backup & Restore</h1>
                        <p class="text-muted mb-0">Manage database backups, downloads, and restores. Restores create an emergency backup automatically.</p>
                    </div>
                    <div>
                        <a href="?<?php echo htmlspecialchars(buildQuery(['export' => 'csv'])); ?>" class="btn btn-outline-primary"><i class="bi bi-file-earmark-spreadsheet"></i> Export CSV</a>
                    </div>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-info mt-3"><?php echo htmlspecialchars($message); ?></div>
                <?php endif; ?>

                <div class="row mt-4">
                    <div class="col-lg-4">
                        <div class="card mb-3">
                            <div class="card-body">
                                <h5 class="card-title">Create Backup</h5>
                                <form method="POST">
                                    <input type="hidden" name="action" value="create_backup">
                                    <div class="mb-2">
                                        <label class="form-label">Backup name (optional)</label>
                                        <input type="text" name="backup_name" class="form-control">
                                    </div>
                                    <div class="mb-2">
                                        <label class="form-label">Description (optional)</label>
                                        <textarea name="backup_description" class="form-control" rows="2"></textarea>
                                    </div>
                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="checkbox" name="compress" value="1" id="compress" checked>
                                        <label class="form-check-label" for="compress">Compress (zip)</label>
                                    </div>
                                    <div class="form-check mb-3">
                                        <input class="form-check-input" type="checkbox" name="include_files" value="1" id="include_files">
                                        <label class="form-check-label" for="include_files">Include uploaded files (future-ready)</label>
                                    </div>
                                    <div class="text-end">
                                        <button class="btn btn-primary">Create Full Backup</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body">
                                <h6 class="mb-2">Storage</h6>
                                <?php $stats = $service->getStats(); ?>
                                <p class="mb-1"><strong>Database size:</strong> <?php echo number_format(($stats['database_size'] ?? 0) / 1024 / 1024, 2); ?> MB</p>
                                <p class="mb-1"><strong>Backup folder size:</strong> <?php echo number_format(($stats['backup_folder_size'] ?? 0) / 1024 / 1024, 2); ?> MB</p>
                                <p class="mb-1"><strong>Total backups:</strong> <?php echo (int)($stats['total_backups'] ?? $total); ?></p>
                                <p class="mb-1"><strong>Latest backup:</strong> <?php echo htmlspecialchars($stats['latest_backup'] ?? 'N/A'); ?></p>
                                <p class="mb-1"><strong>Available disk space:</strong> <?php echo number_format((($stats['available_disk_space'] ?? 0) / 1024 / 1024), 2); ?> MB</p>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-8">
                        <div class="card mb-3">
                            <div class="card-body">
                                <form class="row g-2" method="GET">
                                    <div class="col-md-4"><input type="text" name="search" class="form-control" placeholder="Search filename, description, creator" value="<?php echo htmlspecialchars($_GET['search'] ?? ''); ?>"></div>
                                    <div class="col-md-3"><select name="status" class="form-select"><option value="">All status</option><option value="available" <?php echo (($_GET['status'] ?? '') === 'available') ? 'selected' : ''; ?>>Available</option><option value="failed" <?php echo (($_GET['status'] ?? '') === 'failed') ? 'selected' : ''; ?>>Failed</option><option value="deleted" <?php echo (($_GET['status'] ?? '') === 'deleted') ? 'selected' : ''; ?>>Deleted</option></select></div>
                                    <div class="col-md-3"><input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($_GET['date_from'] ?? ''); ?>"></div>
                                    <div class="col-md-2 text-end"><button class="btn btn-outline-secondary">Filter</button></div>
                                </form>
                            </div>
                        </div>

                        <div class="card">
                            <div class="card-body table-responsive">
                                <table class="table table-sm table-hover align-middle">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Filename</th>
                                            <th>Created By</th>
                                            <th>Date</th>
                                            <th>Size</th>
                                            <th>Status</th>
                                            <th></th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($backups)): ?>
                                            <tr><td colspan="6" class="text-center text-muted">No backups have been created.</td></tr>
                                        <?php endif; ?>
                                        <?php foreach ($backups as $b): ?>
                                            <tr>
                                                <td class="small-pre"><?php echo htmlspecialchars($b['filename']); ?><br><small class="text-muted"><?php echo htmlspecialchars($b['description']); ?></small></td>
                                                <td><?php echo htmlspecialchars($b['created_by_name'] ?? 'System'); ?></td>
                                                <td><?php echo htmlspecialchars($b['created_at']); ?></td>
                                                <td><?php echo number_format(((int)$b['filesize']) / 1024 / 1024, 2); ?> MB</td>
                                                <td><span class="badge bg-<?php echo $b['status'] === 'available' ? 'success' : ($b['status'] === 'failed' ? 'danger' : 'secondary'); ?>"><?php echo htmlspecialchars(ucfirst($b['status'])); ?></span></td>
                                                <td class="text-end">
                                                    <a href="backup_download.php?id=<?php echo (int)$b['id']; ?>" class="btn btn-sm btn-outline-primary">Download</a>
                                                    <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo (int)$b['id']; ?>">Delete</button>
                                                    <button class="btn btn-sm btn-outline-warning" data-bs-toggle="modal" data-bs-target="#restoreModal<?php echo (int)$b['id']; ?>">Restore</button>
                                                </td>
                                            </tr>

                                            <!-- Delete modal -->
                                            <div class="modal fade" id="deleteModal<?php echo (int)$b['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog">
                                                    <div class="modal-content">
                                                        <form method="POST" action="backup_delete.php">
                                                        <div class="modal-header"><h5 class="modal-title">Delete backup</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                        <div class="modal-body">Are you sure you want to delete <strong><?php echo htmlspecialchars($b['filename']); ?></strong>? This action cannot be undone.</div>
                                                        <div class="modal-footer"><input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-danger">Delete</button></div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Restore modal -->
                                            <div class="modal fade" id="restoreModal<?php echo (int)$b['id']; ?>" tabindex="-1" aria-hidden="true">
                                                <div class="modal-dialog modal-lg">
                                                    <div class="modal-content">
                                                        <form method="POST" action="backup_restore.php">
                                                        <div class="modal-header"><h5 class="modal-title">Restore backup</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
                                                        <div class="modal-body">
                                                            <p>Restoring will overwrite the current database. An emergency backup will be created automatically before restoring.</p>
                                                            <p>Type <strong>RESTORE</strong> to enable the restore button.</p>
                                                            <div class="mb-3"><input type="text" name="confirm_text" class="form-control" placeholder="Type RESTORE to confirm"></div>
                                                            <input type="hidden" name="id" value="<?php echo (int)$b['id']; ?>">
                                                        </div>
                                                        <div class="modal-footer"><button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button><button class="btn btn-warning">Restore</button></div>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <?php if ($totalPages > 1): ?>
                            <nav aria-label="Backup pagination">
                                <ul class="pagination justify-content-center mt-3">
                                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>"><a class="page-link" href="?<?php echo htmlspecialchars(buildQuery(['page' => $i])); ?>"><?php echo $i; ?></a></li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
