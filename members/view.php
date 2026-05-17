<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'officer']);

$memberId = $_GET['id'] ?? null;
if (!$memberId) {
    header('Location: list.php');
    exit();
}

$db = Database::getInstance()->getConnection();

// Get member details
$stmt = $db->prepare("SELECT * FROM members WHERE member_id = ?");
$stmt->execute([$memberId]);
$member = $stmt->fetch();

if (!$member) {
    header('Location: list.php');
    exit();
}

// Get next of kin
$stmt = $db->prepare("SELECT * FROM next_of_kin WHERE member_id = ?");
$stmt->execute([$memberId]);
$nextOfKin = $stmt->fetchAll();

// Get documents
$stmt = $db->prepare("SELECT * FROM member_documents WHERE member_id = ?");
$stmt->execute([$memberId]);
$documents = $stmt->fetchAll();

// Get accounts
$stmt = $db->prepare("SELECT * FROM savings_accounts WHERE member_id = ?");
$stmt->execute([$memberId]);
$accounts = $stmt->fetchAll();

// Get loans
$stmt = $db->prepare("SELECT * FROM loans WHERE member_id = ? ORDER BY created_at DESC");
$stmt->execute([$memberId]);
$loans = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Details - <?php echo APP_NAME; ?></title>
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
                <a href="list.php" class="btn btn-secondary">← Back to Members</a>
            </div>
        </div>

        <div class="row">
            <div class="col-md-8">
                <!-- Member Information -->
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0">Member Information</h4>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Membership No:</strong> <?php echo htmlspecialchars($member['membership_no']); ?></p>
                                <p><strong>Full Name:</strong> <?php echo htmlspecialchars($member['full_name']); ?></p>
                                <p><strong>Phone:</strong> <?php echo htmlspecialchars($member['phone']); ?></p>
                                <p><strong>Email:</strong> <?php echo htmlspecialchars($member['email'] ?? 'N/A'); ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>National ID:</strong> <?php echo htmlspecialchars($member['national_id']); ?></p>
                                <p><strong>DOB:</strong> <?php echo date('M d, Y', strtotime($member['date_of_birth'])); ?></p>
                                <p><strong>Gender:</strong> <?php echo ucfirst($member['gender']); ?></p>
                                <p><strong>Status:</strong> <span class="badge bg-<?php echo $member['status'] === 'active' ? 'success' : 'danger'; ?>"><?php echo ucfirst($member['status']); ?></span></p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Savings Accounts -->
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Savings Accounts</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($accounts)): ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Account Number</th>
                                    <th>Type</th>
                                    <th>Balance</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($accounts as $account): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($account['account_number']); ?></td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $account['account_type'])); ?></td>
                                    <td><?php echo formatMoney($account['balance']); ?></td>
                                    <td><span class="badge bg-<?php echo $account['status'] === 'active' ? 'success' : 'secondary'; ?>"><?php echo ucfirst($account['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="text-muted">No savings accounts</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Loans -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Loans</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($loans)): ?>
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Reference</th>
                                    <th>Amount</th>
                                    <th>Outstanding</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($loans as $loan): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($loan['loan_reference']); ?></td>
                                    <td><?php echo formatMoney($loan['loan_amount']); ?></td>
                                    <td><?php echo formatMoney($loan['outstanding_balance']); ?></td>
                                    <td><span class="badge bg-info"><?php echo ucfirst($loan['status']); ?></span></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                        <?php else: ?>
                        <p class="text-muted">No loans</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <!-- Photo -->
                <div class="card mb-3">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Photo</h5>
                    </div>
                    <div class="card-body text-center">
                        <?php if ($member['photo_path'] && file_exists('../' . $member['photo_path'])): ?>
                        <img src="../<?php echo htmlspecialchars($member['photo_path']); ?>" class="img-fluid" style="max-width: 100%; max-height: 300px;">
                        <?php else: ?>
                        <div class="alert alert-secondary">No photo available</div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Next of Kin -->
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Next of Kin</h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($nextOfKin)): ?>
                        <?php foreach ($nextOfKin as $kin): ?>
                        <div class="mb-3">
                            <strong><?php echo htmlspecialchars($kin['full_name']); ?></strong>
                            <p class="mb-0 text-muted"><?php echo htmlspecialchars($kin['relationship']); ?></p>
                            <p class="mb-0"><?php echo htmlspecialchars($kin['phone']); ?></p>
                        </div>
                        <?php endforeach; ?>
                        <?php else: ?>
                        <p class="text-muted">No next of kin registered</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
