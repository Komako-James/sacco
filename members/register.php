<?php
require_once '../includes/auth.php';
require_once '../includes/functions.php';
require_once '../includes/validation.php';
require_once '../config/constants.php';

$auth->requireLogin();
$auth->requireRole(['admin', 'officer', 'branch_manager']);

$user = $auth->getCurrentUser();
$db = Database::getInstance()->getConnection();

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = $_POST['full_name'] ?? '';
    $nationalId = $_POST['national_id'] ?? '';
    $phone = $_POST['phone'] ?? '';
    $email = $_POST['email'] ?? '';
    $gender = $_POST['gender'] ?? '';
    $dob = $_POST['date_of_birth'] ?? '';
    $occupation = $_POST['occupation'] ?? '';
    
    // Validate
    $validation_errors = [];
    
    if (empty($fullName)) $validation_errors['full_name'] = 'Full name is required';
    if (empty($nationalId)) $validation_errors['national_id'] = 'National ID is required';
    if (!validatePhone($phone)) $validation_errors['phone'] = 'Invalid phone number';
    if (!validateNullableEmail($email)) $validation_errors['email'] = 'Invalid email address';
    if (empty($gender)) $validation_errors['gender'] = 'Gender is required';
    if (empty($dob)) $validation_errors['dob'] = 'Date of birth is required';
    
    if (empty($validation_errors)) {
        try {
            // Generate membership number
            $membershipNo = generateMembershipNumber();
            
            // Insert member
            $stmt = $db->prepare("
                INSERT INTO members 
                (membership_no, national_id, full_name, phone, email, gender, date_of_birth, 
                 occupation, join_date, created_by, status)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW(), ?, 'active')
            ");
            
            $stmt->execute([
                $membershipNo,
                $nationalId,
                $fullName,
                $phone,
                $email ?: null,
                $gender,
                $dob,
                $occupation,
                $user['user_id']
            ]);
            
            $memberId = $db->lastInsertId();
            
            // Log activity
            logActivity($user['user_id'], 'Create', 'members', $memberId, null, $_POST);
            
            $success = "Member registered successfully! Membership No: <strong>$membershipNo</strong>";
            
            // Clear form
            $_POST = [];
            
        } catch (Exception $e) {
            $error = "Error registering member: " . $e->getMessage();
        }
    } else {
        foreach ($validation_errors as $field => $msg) {
            $error .= $msg . "<br>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register Member - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/sidebar.php'; ?>
            
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Register New Member</h1>
                </div>
                
                <?php if ($error): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                
                <?php if ($success): ?>
                    <div class="alert alert-success"><?php echo $success; ?></div>
                <?php endif; ?>
                
                <div class="card">
                    <div class="card-header">Member Information</div>
                    <div class="card-body">
                        <form method="POST">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" required value="<?php echo htmlspecialchars($_POST['full_name'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="national_id" class="form-label">National ID *</label>
                                        <input type="text" class="form-control" id="national_id" name="national_id" required value="<?php echo htmlspecialchars($_POST['national_id'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone *</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" required value="<?php echo htmlspecialchars($_POST['phone'] ?? ''); ?>">
                                        <small class="text-muted">Format: +256XXXXXXXXX or 07XXXXXXXXX</small>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email</label>
                                        <input type="email" class="form-control" id="email" name="email" value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                                    </div>
                                </div>
                                
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="gender" class="form-label">Gender *</label>
                                        <select class="form-select" id="gender" name="gender" required>
                                            <option value="">Select Gender</option>
                                            <option value="Male" <?php echo ($_POST['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                                            <option value="Female" <?php echo ($_POST['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                                            <option value="Other" <?php echo ($_POST['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                                        </select>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="date_of_birth" class="form-label">Date of Birth *</label>
                                        <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" required value="<?php echo htmlspecialchars($_POST['date_of_birth'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="occupation" class="form-label">Occupation</label>
                                        <input type="text" class="form-control" id="occupation" name="occupation" value="<?php echo htmlspecialchars($_POST['occupation'] ?? ''); ?>">
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="employer" class="form-label">Employer</label>
                                        <input type="text" class="form-control" id="employer" name="employer" value="<?php echo htmlspecialchars($_POST['employer'] ?? ''); ?>">
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($_POST['address'] ?? ''); ?></textarea>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <button type="submit" class="btn btn-primary">Register Member</button>
                                <a href="list.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../assets/js/main.js"></script>
</body>
</html>
