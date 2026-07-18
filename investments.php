<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/config/constants.php';
require_once __DIR__ . '/includes/functions.php';

$auth->requireLogin();
header('Location: investments/index.php');
exit;
