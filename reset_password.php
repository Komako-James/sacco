<?php
require_once 'config/db_connection.php';
require_once 'config/constants.php';
require_once 'includes/functions.php';

$token = $_GET['token'] ?? '';
$error = '';
$success = '';
$valid = false;

$db = Database::getInstance()->getConnection();
if (!empty($token)) {
    $stmt = $db->prepare('SELECT pr.*, u.email, u.full_name FROM password_resets pr JOIN users u ON pr.user_id = u.user_id WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()');
    $stmt->execute([$token]);
    $reset = $stmt->fetch();
    $valid = (bool)$reset;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $token = $_POST['token'] ?? '';
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm_password'] ?? '';

    if (empty($password) || empty($confirm)) {
        $error = 'Both password fields are required.';
    } elseif ($password !== $confirm) {
        $error = 'Passwords do not match.';
    } else {
        $stmt = $db->prepare('SELECT pr.user_id FROM password_resets pr WHERE pr.token = ? AND pr.used = 0 AND pr.expires_at > NOW()');
        $stmt->execute([$token]);
        $reset = $stmt->fetch();

        if (!$reset) {
            $error = 'This reset link is invalid or has expired.';
        } else {
            $hash = password_hash($password, PASSWORD_BCRYPT);
            $update = $db->prepare('UPDATE users SET password_hash = ? WHERE user_id = ?');
            $update->execute([$hash, $reset['user_id']]);

            $mark = $db->prepare('UPDATE password_resets SET used = 1 WHERE token = ?');
            $mark->execute([$token]);

            $success = 'Password has been reset successfully. You may now login.';
            $valid = false;
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reset Password - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-5">
                <div class="card shadow-sm">
                    <div class="card-header bg-primary text-white text-center">
                        <h4>Set New Password</h4>
                        <div class="small">Secure your SACCO account</div>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                        <?php endif; ?>
                        <?php if ($success): ?>
                            <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
                        <?php endif; ?>

                        <?php if ($valid): ?>
                            <form method="POST">
                                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
                                <div class="mb-3">
                                    <label for="password" class="form-label">New Password</label>
                                    <input type="password" class="form-control" name="password" id="password" required>
                                </div>
                                <div class="mb-3">
                                    <label for="confirm_password" class="form-label">Confirm Password</label>
                                    <input type="password" class="form-control" name="confirm_password" id="confirm_password" required>
                                </div>
                                <button type="submit" class="btn btn-primary w-100">Reset Password</button>
                            </form>
                        <?php else: ?>
                            <div class="alert alert-secondary">This password reset link has expired or is invalid.</div>
                            <div class="text-center mt-3"><a href="forgot_password.php">Request a new reset link</a></div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
