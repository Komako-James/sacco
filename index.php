<?php
require_once 'includes/auth.php';
require_once 'config/constants.php';

// Simple redirect logic using APP_URL constant
$baseUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off' ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . APP_URL;
if ($auth->isLoggedIn()) {
    header('Location: ' . $baseUrl . '/dashboard.php');
} else {
    header('Location: ' . $baseUrl . '/login.php');
}
exit();
?>
