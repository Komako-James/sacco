<?php
/**
 * Member Statements Page
 */

require_once '../member/auth-middleware.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';

requireMemberLogin();

$member = getMemberData();

$db = getDB();

// Get savings accounts for statement download
$stmt = $db->prepare("
    SELECT * FROM savings_accounts
    WHERE member_id = ? AND status = 'active'
    ORDER BY account_type
");
$stmt->execute([$member['member_id']]);
$accounts = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Statements - Member Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <div class="member-content">
        <div class="container-fluid p-4">
            <div class="mb-4">
                <a href="dashboard.php" class="btn btn-sm btn-outline-secondary mb-3">
                    <i class="bi bi-arrow-left me-1"></i> Back
                </a>
                <h2>Download Statements</h2>
            </div>

            <div class="card">
                <div class="card-body">
                    <form method="POST" action="download-statement.php">
                        <div class="mb-3">
                            <label for="account" class="form-label">Select Account</label>
                            <select class="form-control" id="account" name="account_id" required>
                                <option value="">-- Choose Account --</option>
                                <?php foreach ($accounts as $account): ?>
                                <option value="<?php echo $account['account_id']; ?>">
                                    <?php echo ucfirst($account['account_type']); ?> Account (<?php echo formatMoney($account['balance']); ?>)
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="startDate" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="startDate" name="start_date" required>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="endDate" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="endDate" name="end_date" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="format" class="form-label">Format</label>
                            <select class="form-control" id="format" name="format">
                                <option value="pdf">PDF</option>
                                <option value="csv">CSV</option>
                            </select>
                        </div>

                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-download me-1"></i> Download Statement
                        </button>
                    </form>
                </div>
            </div>

            <div class="alert alert-info mt-4">
                <i class="bi bi-info-circle me-2"></i>
                <strong>Note:</strong> Statements will be generated based on your selected date range and format. You can download statements for any period within the past 3 years.
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const today = new Date();
        const threeMonthsAgo = new Date(today);
        threeMonthsAgo.setMonth(threeMonthsAgo.getMonth() - 3);

        document.getElementById('endDate').valueAsDate = today;
        document.getElementById('startDate').valueAsDate = threeMonthsAgo;
    });
    </script>
</body>
</html>
