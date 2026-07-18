<?php
require_once 'includes/auth.php';
require_once 'config/constants.php';

$auth->requireLogin();

// Redirect to actual expenses module
header('Location: ' . APP_URL . '/expenses/index.php');
exit;
?>
