<?php
/**
 * Member Login Page
 * Separate from admin authentication
 */

session_start();

require_once 'config/db_connection.php';
require_once 'config/constants.php';
require_once 'app/Services/MemberAuthenticationService.php';

use SACCO\Services\MemberAuthenticationService;

$error = '';
$success = '';
$requiresOTP = false;
$userIdForOTP = null;
$memberIdForOTP = null;

$db = getDB();
$authService = new MemberAuthenticationService($db);

// Handle login
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($action === 'login') {
        $ipAddress = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'];

        $result = $authService->memberLogin($username, $password, $ipAddress, $userAgent);

        if ($result['success']) {
            // Set session
            $_SESSION['member_user_id'] = $result['user_id'];
            $_SESSION['member_id'] = $result['member_id'];
            $_SESSION['session_token'] = $result['session_token'];
            $_SESSION['is_member'] = true;

            header('Location: member/dashboard.php');
            exit();
        } elseif ($result['requires_password_change'] ?? false) {
            $_SESSION['temp_user_id'] = $result['user_id'];
            $_SESSION['first_login'] = $result['first_login'] ?? false;
            header('Location: member/change-password-first-login.php');
            exit();
        } elseif ($result['requires_2fa'] ?? false) {
            $_SESSION['temp_user_id'] = $result['user_id'];
            $_SESSION['temp_member_id'] = $result['member_id'];
            $_SESSION['pending_2fa'] = true;
            header('Location: member/verify-otp.php');
            exit();
        } else {
            $error = $result['message'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Member Login - <?php echo APP_NAME; ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.8.1/font/bootstrap-icons.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #2c3e50 0%, #3498db 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .login-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            overflow: hidden;
            max-width: 450px;
            width: 100%;
        }
        .login-header {
            background: linear-gradient(135deg, #2c3e50 0%, #34495e 100%);
            color: white;
            padding: 2rem;
            text-align: center;
        }
        .login-header h2 {
            margin: 0;
            font-weight: 700;
        }
        .login-header p {
            margin: 0.5rem 0 0 0;
            opacity: 0.9;
            font-size: 0.9rem;
        }
        .login-body {
            padding: 2rem;
        }
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #ddd;
            font-size: 0.95rem;
        }
        .form-control:focus {
            border-color: #3498db;
            box-shadow: 0 0 0 0.2rem rgba(52, 152, 219, 0.25);
        }
        .btn-login {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border: none;
            padding: 12px;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.95rem;
        }
        .btn-login:hover {
            background: linear-gradient(135deg, #2980b9 0%, #1f618d 100%);
            color: white;
        }
        .login-footer {
            background: #f8f9fa;
            padding: 1.5rem 2rem;
            text-align: center;
            font-size: 0.9rem;
        }
        .login-footer a {
            color: #3498db;
            text-decoration: none;
        }
        .login-footer a:hover {
            text-decoration: underline;
        }
        .alert {
            border-radius: 8px;
            border: none;
        }
        .input-group-text {
            background: transparent;
            border: 1px solid #ddd;
            cursor: pointer;
        }
        .input-group-text:hover {
            background: #f8f9fa;
        }
        .features {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-top: 2rem;
            font-size: 0.85rem;
        }
        .feature {
            display: flex;
            align-items: center;
        }
        .feature i {
            color: #27ae60;
            margin-right: 0.5rem;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="login-container">
        <!-- Header -->
        <div class="login-header">
            <h2><?php echo APP_NAME; ?></h2>
            <p>Member Portal</p>
        </div>

        <!-- Body -->
        <div class="login-body">
            <?php if ($error): ?>
            <div class="alert alert-danger" role="alert">
                <i class="bi bi-exclamation-circle me-2"></i>
                <?php echo htmlspecialchars($error); ?>
            </div>
            <?php endif; ?>

            <?php if ($success): ?>
            <div class="alert alert-success" role="alert">
                <i class="bi bi-check-circle me-2"></i>
                <?php echo htmlspecialchars($success); ?>
            </div>
            <?php endif; ?>

            <form method="POST" id="loginForm">
                <input type="hidden" name="action" value="login">

                <!-- Username (Membership Number) -->
                <div class="mb-3">
                    <label for="username" class="form-label fw-600">Membership Number</label>
                    <input 
                        type="text" 
                        class="form-control" 
                        id="username" 
                        name="username"
                        placeholder="e.g., 001"
                        required
                        autocomplete="off"
                    >
                    <small class="text-muted">Your 3-digit membership number</small>
                </div>

                <!-- Password -->
                <div class="mb-3">
                    <label for="password" class="form-label fw-600">Password</label>
                    <div class="input-group">
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password"
                            placeholder="Enter your password"
                            required
                            autocomplete="current-password"
                        >
                        <span class="input-group-text" onclick="togglePasswordVisibility()">
                            <i class="bi bi-eye" id="eyeIcon"></i>
                        </span>
                    </div>
                </div>

                <!-- Remember Me -->
                <div class="form-check mb-3">
                    <input 
                        class="form-check-input" 
                        type="checkbox" 
                        id="rememberMe" 
                        name="remember_me"
                    >
                    <label class="form-check-label" for="rememberMe">
                        Remember me on this device
                    </label>
                </div>

                <!-- Login Button -->
                <button type="submit" class="btn btn-login w-100 mb-2">
                    <i class="bi bi-box-arrow-in-right me-2"></i>
                    Login
                </button>
            </form>

            <!-- Features -->
            <div class="features">
                <div class="feature">
                    <i class="bi bi-shield-check"></i>
                    <span>Secure Login</span>
                </div>
                <div class="feature">
                    <i class="bi bi-lock"></i>
                    <span>2FA Support</span>
                </div>
                <div class="feature">
                    <i class="bi bi-eye"></i>
                    <span>Data Privacy</span>
                </div>
                <div class="feature">
                    <i class="bi bi-clock"></i>
                    <span>24/7 Access</span>
                </div>
            </div>
        </div>

        <!-- Footer -->
        <div class="login-footer">
            <p class="mb-2">Having trouble logging in?</p>
            <p>
                <a href="member/forgot-password.php">
                    <i class="bi bi-key me-1"></i>
                    Reset Password
                </a>
                |
                <a href="contact.php">
                    <i class="bi bi-telephone me-1"></i>
                    Contact Support
                </a>
            </p>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
    function togglePasswordVisibility() {
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');

        if (passwordInput.type === 'password') {
            passwordInput.type = 'text';
            eyeIcon.classList.remove('bi-eye');
            eyeIcon.classList.add('bi-eye-slash');
        } else {
            passwordInput.type = 'password';
            eyeIcon.classList.remove('bi-eye-slash');
            eyeIcon.classList.add('bi-eye');
        }
    }

    // Focus on username by default
    document.addEventListener('DOMContentLoaded', function() {
        document.getElementById('username').focus();
    });
    </script>
</body>
</html>
