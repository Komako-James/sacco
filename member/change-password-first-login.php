<?php
/**
 * Change Password on First Login
 */

session_start();

require_once '../config/db_connection.php';
require_once '../app/Services/MemberAuthenticationService.php';

use SACCO\Services\MemberAuthenticationService;

$error = '';
$message = '';

// Check if user is in first login state
if (!isset($_SESSION['temp_user_id'])) {
    header('Location: ../member-login.php');
    exit();
}

$db = getDB();
$authService = new MemberAuthenticationService($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($newPassword) || empty($confirmPassword)) {
        $error = 'All fields are required';
    } elseif ($newPassword !== $confirmPassword) {
        $error = 'Passwords do not match';
    } else {
        $result = $authService->changePasswordFirstLogin(
            $_SESSION['temp_user_id'],
            $_SESSION['temp_member_id'] ?? 0,
            $newPassword
        );

        if ($result['success']) {
            $_SESSION['member_user_id'] = $_SESSION['temp_user_id'];
            $_SESSION['is_member'] = true;

            unset($_SESSION['temp_user_id']);
            unset($_SESSION['temp_member_id']);
            unset($_SESSION['first_login']);

            header('Location: ../member-login.php?message=' . urlencode('Password changed. Please login with your new password.'));
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
    <title>Change Password - First Login</title>
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
        .password-container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.2);
            max-width: 500px;
            width: 100%;
            padding: 2rem;
        }
        .password-header {
            text-align: center;
            margin-bottom: 2rem;
        }
        .password-header i {
            font-size: 3rem;
            color: #f39c12;
            margin-bottom: 1rem;
        }
        .btn-change {
            background: linear-gradient(135deg, #3498db 0%, #2980b9 100%);
            border: none;
        }
        .btn-change:hover {
            background: linear-gradient(135deg, #2980b9 0%, #1f618d 100%);
            color: white;
        }
        .requirements {
            background: #f8f9fa;
            padding: 1rem;
            border-radius: 8px;
            margin-bottom: 1rem;
        }
        .requirement {
            font-size: 0.9rem;
            margin-bottom: 0.5rem;
        }
        .requirement.met {
            color: #27ae60;
        }
        .requirement.unmet {
            color: #e74c3c;
        }
    </style>
</head>
<body>
    <div class="password-container">
        <div class="password-header">
            <i class="bi bi-key"></i>
            <h3>Change Your Password</h3>
            <p class="text-muted">This is your first login. Please set a new password.</p>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger" role="alert">
            <i class="bi bi-exclamation-circle me-2"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
        <?php endif; ?>

        <form method="POST" id="passwordForm">
            <div class="requirements">
                <p class="fw-bold mb-2">Password Requirements:</p>
                <div class="requirement unmet" id="req-length">
                    <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                    Minimum 8 characters
                </div>
                <div class="requirement unmet" id="req-uppercase">
                    <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                    At least one uppercase letter (A-Z)
                </div>
                <div class="requirement unmet" id="req-number">
                    <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                    At least one number (0-9)
                </div>
                <div class="requirement unmet" id="req-special">
                    <i class="bi bi-circle-fill" style="font-size: 0.5rem;"></i>
                    At least one special character (!@#$%^&*)
                </div>
            </div>

            <div class="mb-3">
                <label for="newPassword" class="form-label">New Password</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="newPassword" 
                    name="new_password"
                    required
                    oninput="validatePassword()"
                >
            </div>

            <div class="mb-3">
                <label for="confirmPassword" class="form-label">Confirm Password</label>
                <input 
                    type="password" 
                    class="form-control" 
                    id="confirmPassword" 
                    name="confirm_password"
                    required
                >
            </div>

            <button type="submit" class="btn btn-change w-100 mb-2" id="submitBtn" disabled>
                Change Password
            </button>
        </form>

        <div class="text-center mt-3">
            <small class="text-muted">You will be redirected to login after changing your password</small>
        </div>
    </div>

    <script>
    function validatePassword() {
        const password = document.getElementById('newPassword').value;
        const confirmPassword = document.getElementById('confirmPassword').value;
        const submitBtn = document.getElementById('submitBtn');

        // Check requirements
        const hasLength = password.length >= 8;
        const hasUppercase = /[A-Z]/.test(password);
        const hasNumber = /[0-9]/.test(password);
        const hasSpecial = /[!@#$%^&*()_\-+=\[\]{}|:;<>,.?\/~`]/.test(password);
        const passwordsMatch = password && password === confirmPassword && password.length > 0;

        // Update UI
        updateRequirement('req-length', hasLength);
        updateRequirement('req-uppercase', hasUppercase);
        updateRequirement('req-number', hasNumber);
        updateRequirement('req-special', hasSpecial);

        // Enable submit button if all requirements met
        submitBtn.disabled = !(hasLength && hasUppercase && hasNumber && hasSpecial && passwordsMatch);
    }

    function updateRequirement(id, met) {
        const el = document.getElementById(id);
        if (met) {
            el.classList.remove('unmet');
            el.classList.add('met');
        } else {
            el.classList.remove('met');
            el.classList.add('unmet');
        }
    }

    // Add listener to confirm password
    document.getElementById('confirmPassword').addEventListener('input', validatePassword);
    </script>
</body>
</html>
