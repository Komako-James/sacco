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
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo APP_URL; ?>/assets/css/style.css">
</head>
<body class="login-shell">
    <div class="container py-5">
        <div class="row justify-content-center align-items-center min-vh-100">
            <div class="col-lg-9 col-xl-8">
                <div class="card login-card shadow-lg">
                    <div class="row g-0">
                        <div class="col-md-5 login-illustration d-flex flex-column justify-content-center">
                            <div class="text-center mb-4">
                                <img src="<?php echo COMPANY_LOGO; ?>" alt="<?php echo htmlspecialchars(COMPANY_NAME); ?>" class="img-fluid rounded-circle bg-white p-2" style="max-height: 110px;">
                            </div>
                            <h2 class="h4 fw-bold text-center mb-2">Secure SACCO Access</h2>
                            <p class="text-center mb-0 opacity-75">Access member, savings, loans, and reporting tools from a single secure portal.</p>
                        </div>
                        <div class="col-md-7">
                            <div class="card-body">
                                <h3 class="h4 mb-1">Welcome back</h3>
                                <p class="text-muted mb-4">Sign in to continue to your operations dashboard.</p>
                                <?php if ($error): ?>
                                    <div class="alert alert-danger"><?php echo $error; ?></div>
                                <?php endif; ?>
                                <form method="POST" action="<?php echo (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . APP_URL . '/login.php'; ?>">
                                    <div class="mb-3">
                                        <label for="username" class="form-label">Username</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-person"></i></span>
                                            <input type="text" class="form-control" id="username" name="username" placeholder="Enter username" required>
                                        </div>
                                    </div>
                                    <div class="mb-3">
                                        <label for="password" class="form-label">Password</label>
                                        <div class="input-group">
                                            <span class="input-group-text"><i class="bi bi-lock"></i></span>
                                            <input type="password" class="form-control" id="password" name="password" placeholder="Enter password" required>
                                        </div>
                                    </div>
                                    <button type="submit" class="btn btn-primary w-100">Login</button>
                                </form>
                                <div class="mt-3 text-center">
                                    <small class="text-muted"><i class="bi bi-shield-lock me-1"></i>Protected by secure session management</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
