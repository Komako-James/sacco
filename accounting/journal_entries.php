<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';
require_once '../config/db_connection.php';

$auth->requireLogin();
$auth->requirePermission('accounting.view');

$db = getDB();
$errors = [];
$journalEntries = [];

try {
    $stmt = $db->query(
        'SELECT je.journal_entry_id, je.entry_date, je.reference_number, je.description, je.status, '
        . 'COUNT(jl.journal_entry_line_id) AS line_count '
        . 'FROM journal_entries je '
        . 'LEFT JOIN journal_entry_lines jl ON je.journal_entry_id = jl.journal_entry_id '
        . 'GROUP BY je.journal_entry_id, je.entry_date, je.reference_number, je.description, je.status '
        . 'ORDER BY je.entry_date DESC LIMIT 100'
    );
    $journalEntries = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $errors[] = 'Unable to retrieve journal entries: ' . $e->getMessage();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal Entries - <?php echo APP_NAME; ?></title>
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
                    <h1 class="h4 mb-0">Journal Entries</h1>
                </div>

                <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($errors[0]); ?>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="table-responsive">
                        <table class="table table-hover mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Reference</th>
                                    <th>Description</th>
                                    <th>Status</th>
                                    <th>Lines</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($journalEntries)): ?>
                                    <tr><td colspan="5" class="text-center">No journal entries found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($journalEntries as $entry): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars(formatDate($entry['entry_date'], 'Y-m-d H:i')); ?></td>
                                            <td><?php echo htmlspecialchars($entry['reference_number']); ?></td>
                                            <td><?php echo htmlspecialchars($entry['description']); ?></td>
                                            <td><?php echo htmlspecialchars(ucfirst($entry['status'])); ?></td>
                                            <td><?php echo htmlspecialchars($entry['line_count']); ?></td>
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
