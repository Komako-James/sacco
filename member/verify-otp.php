<?php
/**
 * Verify OTP for 2FA
 */

session_start();

require_once '../config/db_connection.php';
require_once '../app/Services/MemberAuthenticationService.php';

use SACCO\Services\MemberAuthenticationService;

$error = '';

// Check if user is in pending 2FA state
if (!isset($_SESSION['pending_2fa']) || !isset($_SESSION['temp_user_id'])) {
    header('Location: ../member-login.php');
    exit();
}

$db = getDB();
$authService = new MemberAuthenticationService($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $otp = $_POST['otp'] ?? '';

    if (empty($otp)) {
        $error = 'Please enter the OTP';
    } else {
        $result = $authService->verify2FAOTP(
            $_SESSION['temp_user_id'],
            $_SESSION['temp_member_id'],
            $otp,
            $_SERVER['REMOTE_ADDR'],
            $_SERVER['HTTP_USER_AGENT']
        );

        if ($result['success']) {
            $_SESSION['member_user_id'] = $result['user_id'];
            $_SESSION['member_id'] = $result['member_id'];
            $_SESSION['session_token'] = $result['session_token'];
            $_SESSION['is_member'] = true;

            unset($_SESSION['pending_2fa']);
            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_member_id']);

            // Diagnostic: write session state to file
            file_put_contents(__DIR__ . '/../tmp/member_debug.log', date('c') . " VERIFY OTP SET SESSION: " . print_r($_SESSION, true) . "\n", FILE_APPEND | LOCK_EX);

            header('Location: dashboard.php');
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
    <title>Verify OTP - Member Login</title>
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
        .verify-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 400px;
            width: 100%;
            padding: 2rem;
        }
        .verify-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .verify-header i {
            font-size: 3rem;
            color: #3498db;
            margin-bottom: 1rem;
        }
        .otp-input {
            font-size: 2rem;
            letter-spacing: 10px;
            text-align: center;
            font-weight: bold;
        }
        .btn-verify {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border: none;
        }
        .btn-verify:hover {
            background: linear-gradient(135deg, #2980b9 0%, #1f618d 100%);
            color: white;
        }
    </style>
</head>
<body>
    <div class="verify-container">
        <div class="verify-header">
            <i class="bi bi-shield-check"></i>
            <h3>Verify Your Identity</h3>
            <p class="text-muted">Enter the OTP sent to your phone</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST">
            <div class="mb-3">
                <label for="otp" class="form-label">One-Time Password (OTP)</label>
                <input 
                    type="text" 
                    class="form-control otp-input" 
                    id="otp" 
                    name="otp"
                    placeholder="000000"
                    maxlength="6"
                    pattern="[0-9]{6}"
                    required
                    inputmode="numeric"
                >
                <small class="text-muted">6-digit code</small>
            </div>

            <button type="submit" class="btn btn-verify w-100 mb-2">Verify OTP</button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">Didn't receive the OTP?</small><br>
            <a href="../member-login.php" class="text-decoration-none">Try logging in again</a>
        </div>
    </div>

    <script>
    // Auto-focus on OTP input
    document.getElementById('otp').focus();

    // Only allow numbers
    document.getElementById('otp').addEventListener('keypress', function(e) {
        if (!/[0-9]/.test(e.key)) {
            e.preventDefault();
        }
    });
    </script>
</body>
</html>
