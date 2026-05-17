<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'branch_manager', 'loan_officer']);
$user = $auth->getCurrentUser();
$db = Database::getInstance()->getConnection();

$error = '';
$success = '';
$member = null;

// Search member
if (isset($_GET['search'])) {
    $stmt = $db->prepare("
        SELECT * FROM members 
        WHERE membership_no = ? OR phone = ? OR national_id = ?
        LIMIT 1
    ");
    $stmt->execute([$_GET['search'], $_GET['search'], $_GET['search']]);
    $member = $stmt->fetch();
    
    if ($member) {
        $eligibility = checkMemberEligibility($member['member_id']);
        if (!$eligibility['eligible']) {
            $error = $eligibility['reason'];
            $member = null;
        }
    } else {
        $error = "Member not found";
    }
}

// Get loan products
$products = $db->query("SELECT * FROM loan_products WHERE status = 'active'")->fetchAll();

// Submit application
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['member_id'])) {
    try {
        $db->beginTransaction();
        
        $loan_ref = generateLoanReference();
        
        // Get product details
        $stmt = $db->prepare("SELECT * FROM loan_products WHERE product_id = ?");
        $stmt->execute([$_POST['product_id']]);
        $product = $stmt->fetch();
        
        // Insert loan
        $stmt = $db->prepare("
            INSERT INTO loans (loan_ref_no, member_id, product_id, amount_requested, interest_rate, 
                             repayment_period_months, purpose, application_date, applied_by, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'applied')
        ");
        
        $stmt->execute([
            $loan_ref,
            $_POST['member_id'],
            $_POST['product_id'],
            $_POST['amount'],
            $product['default_interest_rate'],
            $_POST['period'],
            $_POST['purpose'],
            date('Y-m-d'),
            $user['user_id']
        ]);
        
        $loan_id = $db->lastInsertId();
        
        // Add guarantors
        if (!empty($_POST['guarantors'])) {
            foreach ($_POST['guarantors'] as $guarantor_id) {
                // Check guarantor exposure
                $exposure = getMemberGuarantorExposure($guarantor_id);
                $savings = getMemberSavingsBalance($guarantor_id);
                $available = $savings - $exposure;
                
                if ($available > 0) {
                    $guarantee_amount = min($_POST['amount'] / count($_POST['guarantors']), $available);
                    $percentage = ($guarantee_amount / $_POST['amount']) * 100;
                    
                    $stmt = $db->prepare("
                        INSERT INTO loan_guarantors (loan_id, guarantor_member_id, amount_guaranteed, percentage_guarantee)
                        VALUES (?, ?, ?, ?)
                    ");
                    $stmt->execute([$loan_id, $guarantor_id, $guarantee_amount, $percentage]);
                }
            }
        }
        
        $db->commit();
        $success = "Loan application submitted successfully! Reference: $loan_ref";
        
        logActivity($user['user_id'], 'Create', 'loans', $loan_id, null, $_POST);
        
    } catch (Exception $e) {
        $db->rollback();
        $error = "Application failed: " . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loan Application - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Loan Application</h1>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <!-- Search Member -->
                <?php if (!$member && !isset($_POST['member_id'])): ?>
                <div class="card mb-4">
                    <div class="card-header">Find Member</div>
                    <div class="card-body">
                        <form method="GET">
                            <div class="input-group">
                                <input type="text" name="search" class="form-control" placeholder="Membership No / Phone / National ID" required>
                                <button type="submit" class="btn btn-primary">Search</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
                
                <!-- Member Details -->
                <?php if ($member): ?>
                <div class="card mb-4">
                    <div class="card-header bg-info text-white">Member Details</div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <strong>Name:</strong> <?php echo htmlspecialchars($member['full_name']); ?><br>
                                <strong>Membership No:</strong> <?php echo $member['membership_no']; ?><br>
                                <strong>Phone:</strong> <?php echo $member['phone']; ?>
                            </div>
                            <div class="col-md-6">
                                <strong>Total Savings:</strong> <?php echo formatMoney(getMemberSavingsBalance($member['member_id'])); ?><br>
                                <strong>Active Guarantor Exposure:</strong> <?php echo formatMoney(getMemberGuarantorExposure($member['member_id'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Loan Application Form -->
                <div class="card">
                    <div class="card-header">Loan Details</div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="member_id" value="<?php echo $member['member_id']; ?>">
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label>Loan Product *</label>
                                        <select name="product_id" id="product_id" class="form-control" required>
                                            <option value="">Select Product</option>
                                            <?php foreach ($products as $product): ?>
                                            <option value="<?php echo $product['product_id']; ?>"
                                                    data-min="<?php echo $product['min_amount']; ?>"
                                                    data-max="<?php echo $product['max_amount']; ?>"
                                                    data-rate="<?php echo $product['default_interest_rate']; ?>"
                                                    data-min-months="<?php echo $product['min_repayment_months']; ?>"
                                                    data-max-months="<?php echo $product['max_repayment_months']; ?>">
                                                <?php echo $product['product_name']; ?> 
                                                (<?php echo formatMoney($product['min_amount']); ?> - <?php echo formatMoney($product['max_amount']); ?>, 
                                                <?php echo $product['default_interest_rate']; ?>%)
                                            </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label>Amount Requested *</label>
                                        <input type="number" name="amount" id="amount" class="form-control" required>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label>Repayment Period (Months) *</label>
                                        <input type="number" name="period" id="period" class="form-control" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label>Purpose of Loan *</label>
                                        <textarea name="purpose" class="form-control" rows="3" required></textarea>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label>Guarantors (Hold Ctrl to select multiple)</label>
                                        <select name="guarantors[]" class="form-control" multiple size="5">
                                            <?php
                                            $other_members = $db->prepare("
                                                SELECT member_id, full_name, membership_no 
                                                FROM members 
                                                WHERE member_id != ? AND status = 'active'
                                                ORDER BY full_name
                                            ");
                                            $other_members->execute([$member['member_id']]);
                                            while ($g = $other_members->fetch()):
                                                $exposure = getMemberGuarantorExposure($g['member_id']);
                                                $savings = getMemberSavingsBalance($g['member_id']);
                                                $available = $savings - $exposure;
                                            ?>
                                            <option value="<?php echo $g['member_id']; ?>" 
                                                    data-available="<?php echo $available; ?>">
                                                <?php echo $g['full_name']; ?> (<?php echo $g['membership_no']; ?>) 
                                                - Available: <?php echo formatMoney($available); ?>
                                            </option>
                                            <?php endwhile; ?>
                                        </select>
                                        <small class="text-muted">Select members who will guarantee this loan</small>
                                    </div>
                                </div>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">Submit Application</button>
                            <a href="apply.php" class="btn btn-secondary">New Application</a>
                        </form>
                    </div>
                </div>
                <?php endif; ?>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    document.getElementById('product_id').addEventListener('change', function() {
        var option = this.options[this.selectedIndex];
        var min = option.getAttribute('data-min');
        var max = option.getAttribute('data-max');
        var minMonths = option.getAttribute('data-min-months');
        var maxMonths = option.getAttribute('data-max-months');
        
        document.getElementById('amount').min = min;
        document.getElementById('amount').max = max;
        document.getElementById('period').min = minMonths;
        document.getElementById('period').max = maxMonths;
    });
    </script>
</body>
</html>
