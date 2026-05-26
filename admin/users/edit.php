<?php
require_once '../../includes/auth.php';
require_once '../../includes/functions.php';
require_once '../../config/constants.php';
require_once '../../app/Services/UserService.php';

$auth->requireLogin();
$auth->requireRole(['admin']);

$userService = new \SACCO\Services\UserService();
$userId = (int)($_GET['id'] ?? 0);
$user = $userService->getUser($userId);
if (!$user) {
    header('Location: index.php');
    exit;
}

$roles = $userService->getRoleOptions();
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fullName = trim($_POST['full_name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');
    $role = $_POST['role'] ?? $user['role'];
    $status = $_POST['status'] ?? $user['status'];
    $password = $_POST['password'] ?? '';

    try {
        $userService->updateUser($userId, [
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'role' => $role,
            'status' => $status,
            'password' => $password
        ]);
        $success = 'User updated successfully.';
        $user = $userService->getUser($userId);
    } catch (Exception $e) {
        $error = 'Unable to save changes: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit User - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="../../assets/css/style.css">
</head>
<body>
    <?php include '../../includes/navbar.php'; ?>
    <div class="container-fluid">
        <div class="row">
            <?php include '../../includes/sidebar.php'; ?>
            <main class="col-md-10 ms-sm-auto px-md-4">
                <div class="pt-4 pb-3 border-bottom">
                    <h1 class="h2">Edit User</h1>
                    <p class="text-muted">Update account status, role, or reset password for <?php echo htmlspecialchars($user['username']); ?>.</p>
                </div>

                <?php if ($error): ?>
                    <div class="alert alert-danger mt-4"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <?php if ($success): ?>
                    <div class="alert alert-success mt-4"><?php echo htmlspecialchars($success); ?></div>
                <?php endif; ?>

                <div class="card mt-4">
                    <div class="card-body">
                        <form method="POST" class="row g-3 needs-validation" novalidate>
                            <div class="col-md-6">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" id="username" class="form-control" value="<?php echo htmlspecialchars($user['username']); ?>" disabled>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" id="email" name="email" class="form-control" required value="<?php echo htmlspecialchars($user['email']); ?>">
                                <div class="invalid-feedback">Valid email is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="full_name" class="form-label">Full Name</label>
                                <input type="text" id="full_name" name="full_name" class="form-control" required value="<?php echo htmlspecialchars($user['full_name']); ?>">
                                <div class="invalid-feedback">Full name is required.</div>
                            </div>
                            <div class="col-md-6">
                                <label for="phone" class="form-label">Phone</label>
                                <input type="text" id="phone" name="phone" class="form-control" value="<?php echo htmlspecialchars($user['phone']); ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="role" class="form-label">Role</label>
                                <select id="role" name="role" class="form-select" required>
                                    <?php foreach ($roles as $roleKey => $roleLabel): ?>
                                        <option value="<?php echo htmlspecialchars($roleKey); ?>" <?php echo $user['role'] === $roleKey ? 'selected' : ''; ?>><?php echo htmlspecialchars($roleLabel); ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="status" class="form-label">Status</label>
                                <select id="status" name="status" class="form-select">
                                    <option value="active" <?php echo $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                    <option value="inactive" <?php echo $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    <option value="suspended" <?php echo $user['status'] === 'suspended' ? 'selected' : ''; ?>>Suspended</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label for="password" class="form-label">Reset Password</label>
                                <input type="password" id="password" name="password" class="form-control" placeholder="Leave blank to keep current password">
                                <div class="form-text">Enter a new password only if you want to reset it.</div>
                            </div>
                            <div class="col-12 text-end">
                                <button class="btn btn-primary">Save Changes</button>
                                <a href="index.php" class="btn btn-secondary">Back</a>
                            </div>
                        </form>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../../assets/js/main.js"></script>
</body>
</html>
