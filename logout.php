<?php
require_once 'includes/auth.php';
require_once 'config/constants.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$redirectUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . APP_URL . '/login.php';

$auth->logout();
header('Location: ' . $redirectUrl);
exit();
?>
