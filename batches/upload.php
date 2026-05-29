<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'officer', 'finance']);

$db = Database::getInstance()->getConnection();

$errors = [];
$success = '';

// Load employers for the dropdown if the table exists
$employers = [];
try {
    $employersStmt = $db->prepare('SELECT employer_id, employer_name FROM employers WHERE status = ? ORDER BY employer_name ASC');
    $employersStmt->execute(['active']);
    $employers = $employersStmt->fetchAll();
} catch (PDOException $e) {
    // Employers table is not present in this installation.
    $employers = [];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $batchMonth = $_POST['batch_month'] ?? '';
    $employerId = $_POST['employer_id'] ?? null;

    if (empty($batchMonth)) {
        $errors[] = 'Batch month is required.';
    }

    if (!empty($employers)) {
        if (empty($employerId) || !ctype_digit($employerId)) {
            $errors[] = 'Please select a valid employer.';
        }
    } else {
        $employerId = null;
    }

    if (!isset($_FILES['batch_file']) || $_FILES['batch_file']['error'] !== UPLOAD_ERR_OK) {
        $errors[] = 'Please select a batch CSV file to upload.';
    } else {
        $uploadedFile = $_FILES['batch_file'];
        $fileInfo = pathinfo($uploadedFile['name']);
        $fileExtension = strtolower($fileInfo['extension'] ?? '');

        if ($fileExtension !== 'csv') {
            $errors[] = 'Only CSV files are allowed for batch uploads.';
        }
    }

    if (empty($errors)) {
        $uploadDir = UPLOAD_PATH . 'batches/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        $batchReference = 'SD' . date('YmdHis') . rand(100, 999);
        $targetFileName = $batchReference . '-' . preg_replace('/[^a-zA-Z0-9._-]/', '_', $uploadedFile['name']);
        $targetPath = $uploadDir . $targetFileName;

        if (move_uploaded_file($uploadedFile['tmp_name'], $targetPath)) {
            $filePathDb = 'assets/uploads/batches/' . $targetFileName;
            $batchMonthDate = date('Y-m-01', strtotime($batchMonth . '-01'));

            $stmt = $db->prepare(
                'INSERT INTO salary_deduction_batches (batch_reference, batch_month, employer_id, file_name, file_path, uploaded_by, status, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, NOW())'
            );
            $stmt->execute([
                $batchReference,
                $batchMonthDate,
                $employerId,
                $uploadedFile['name'],
                $filePathDb,
                $_SESSION['user_id'],
                'uploaded'
            ]);

            $success = 'Batch uploaded successfully. Reference: ' . htmlspecialchars($batchReference);
        } else {
            $errors[] = 'Failed to save the uploaded file. Please check folder permissions.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Batch - <?php echo APP_NAME; ?></title>
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
                    <h1 class="h4 mb-0">Upload Payroll Batch</h1>
                    <a href="list.php" class="btn btn-secondary">View Batches</a>
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

                <?php if (!empty($success)): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-body">
                        <form method="POST" enctype="multipart/form-data">
                            <div class="mb-3">
                                <label for="batch_month" class="form-label">Batch Month</label>
                                <input type="month" name="batch_month" id="batch_month" class="form-control" value="<?php echo htmlspecialchars($_POST['batch_month'] ?? ''); ?>" required>
                            </div>
                            <?php if (!empty($employers)): ?>
                            <div class="mb-3">
                                <label for="employer_id" class="form-label">Employer</label>
                                <select name="employer_id" id="employer_id" class="form-select" required>
                                    <option value="">Select employer</option>
                                    <?php foreach ($employers as $employer): ?>
                                        <option value="<?php echo $employer['employer_id']; ?>" <?php echo isset($_POST['employer_id']) && $_POST['employer_id'] == $employer['employer_id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($employer['employer_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <?php else: ?>
                            <div class="alert alert-info">Employer lookup is not configured for this installation. You can still upload a batch and the employer will be recorded as unknown.</div>
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="batch_file" class="form-label">Batch File (CSV)</label>
                                <input type="file" name="batch_file" id="batch_file" class="form-control" accept=".csv" required>
                            </div>

                            <button type="submit" class="btn btn-primary">Upload Batch</button>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
