<?php
/**
 * Member Repayment Schedule Page
 */

require_once '../member/auth-middleware.php';
require_once '../config/constants.php';
require_once '../includes/functions.php';

requireMemberLogin();

$member = getMemberData();

$db = getDB();

// Get loan repayment schedules
$stmt = $db->prepare("
    SELECT l.loan_ref_no AS loan_reference, l.amount_requested AS loan_amount, lrs.*
    FROM loans l
    JOIN loan_repayment_schedule lrs ON l.loan_id = lrs.loan_id
    WHERE l.member_id = ? AND l.status IN ('approved', 'disbursed')
    ORDER BY l.loan_id, lrs.due_date
");
$stmt->execute([$member['member_id']]);
$schedules = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Group by loan
$loanSchedules = [];
foreach ($schedules as $schedule) {
    $loanRef = $schedule['loan_reference'];
    if (!isset($loanSchedules[$loanRef])) {
        $loanSchedules[$loanRef] = [
            'loan_reference' => $loanRef,
            'loan_amount' => $schedule['loan_amount'],
            'schedules' => []
        ];
    }
    $loanSchedules[$loanRef]['schedules'][] = $schedule;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Repayment Schedule - Member Portal</title>
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
                <h2>Repayment Schedule</h2>
            </div>

            <?php if (!empty($loanSchedules)): ?>
                <?php foreach ($loanSchedules as $loanRef => $loanData): ?>
                <div class="card mb-3">
                    <div class="card-header">
                        <h5 class="mb-0">Loan <?php echo htmlspecialchars($loanRef); ?> - <?php echo formatMoney($loanData['loan_amount']); ?></h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-sm">
                                <thead class="table-light">
                                    <tr>
                                        <th>Due Date</th>
                                        <th>Principal</th>
                                        <th>Interest</th>
                                        <th>Total Due</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loanData['schedules'] as $schedule): ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($schedule['due_date'])); ?></td>
                                        <td><?php echo formatMoney($schedule['principal_due']); ?></td>
                                        <td><?php echo formatMoney($schedule['interest_due']); ?></td>
                                        <td><?php echo formatMoney($schedule['total_due']); ?></td>
                                        <td>
                                            <span class="badge bg-<?php echo $schedule['status'] === 'paid' ? 'success' : ($schedule['status'] === 'overdue' ? 'danger' : 'warning'); ?>">
                                                <?php echo ucfirst($schedule['status']); ?>
                                            </span>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            <?php else: ?>
            <div class="alert alert-info">No active loan repayment schedules.</div>
            <?php endif; ?>
        </div>
    </div>
</body>
</html>
