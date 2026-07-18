<?php
require_once 'includes/auth.php';
require_once 'config/constants.php';

$auth->requireLogin();

// Redirect to actual dividends module
header('Location: ' . APP_URL . '/admin/dividends.php');
exit;
?>
