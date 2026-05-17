<?php
require_once 'includes/auth.php';
require_once 'config/constants.php';

// If already logged in, go to dashboard
if ($auth->isLoggedIn()) {
    header('Location: dashboard.php');
} else {
    // Otherwise, go to login
    header('Location: login.php');
}
exit();
?>
