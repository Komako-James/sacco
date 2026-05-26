<?php
require_once 'config/db_connection.php';
require_once 'includes/auth.php';
require_once 'config/constants.php';

if ($auth->isLoggedIn()) {
    header('Location: ' . APP_URL . '/dashboard.php');
    exit();
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';
    
    $loginResult = $auth->login($username, $password);
    if (!empty($loginResult['success'])) {
        $redirectUrl = $_SESSION['redirect_after_login'] ?? APP_URL . '/dashboard.php';
        unset($_SESSION['redirect_after_login']);

        // Ensure redirect URL uses APP_URL base
        if (!preg_match('#^https?://#', $redirectUrl) && strpos($redirectUrl, APP_URL) !== 0) {
            $redirectUrl = APP_URL . '/dashboard.php';
        }

        if (!preg_match('#^https?://#', $redirectUrl)) {
            $redirectUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . $redirectUrl;
        }

        header('Location: ' . $redirectUrl);
        exit();
    }

    $error = $loginResult['message'] ?? 'Invalid username or password';
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo APP_NAME; ?> - Login</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body class="bg-light">
    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-4">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white text-center">
                        <h4><?php echo APP_NAME; ?></h4>
                        <small>Login to your account</small>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        <form method="POST" action="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . APP_URL . '/login.php'; ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Login</button>
                        </form>
                        <div class="mt-3 text-center">
                            <small class="text-muted">Default: admin / password (change after first login)</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
