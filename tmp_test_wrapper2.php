<?php
set_error_handler(function($severity, $message, $file, $line) {
    throw new ErrorException($message, 0, $severity, $file, $line);
});
chdir('D:/wamp64/www/sacco/accounting');
error_reporting(E_ALL);
ini_set('display_errors','1');

$_SERVER['PHP_SELF'] = 'dashboard.php';
$_SERVER['REQUEST_URI'] = '/accounting/dashboard.php';
$_SERVER['SERVER_NAME'] = 'localhost';
$_SERVER['HTTP_HOST'] = 'localhost';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
$_SERVER['HTTPS'] = 'off';
$_SERVER['HTTP_USER_AGENT'] = 'CLI';

require_once '../config/constants.php';
require_once '../config/db_connection.php';
require_once '../includes/auth.php';

$_SESSION = [
    'user_id' => 1,
    'role' => 'manager',
    'username' => 'test',
    'full_name' => 'CLI Manager',
    'email' => 'test@example.com',
    'last_activity' => time(),
    'expires_at' => time() + 3600,
    'user_agent' => 'CLI',
    'ip_address' => '127.0.0.1',
];

file_put_contents('tmp_wrapper_debug.log', 'BEFORE_REQUIRE
');
$ok = require 'dashboard.php';
file_put_contents('tmp_wrapper_debug.log', file_get_contents('tmp_wrapper_debug.log') . 'AFTER_REQUIRE
');
