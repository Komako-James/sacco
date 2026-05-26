<?php
require_once 'config/constants.php';

$scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
$baseUrl = $scheme . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost') . APP_URL;

echo '<pre>';
echo "Request URI: " . htmlspecialchars($_SERVER['REQUEST_URI']) . "\n";
echo "Script Filename: " . htmlspecialchars($_SERVER['SCRIPT_FILENAME']) . "\n";
echo "HTTP_HOST: " . htmlspecialchars($_SERVER['HTTP_HOST'] ?? 'N/A') . "\n";
echo "APP_URL constant: " . htmlspecialchars(APP_URL) . "\n";
echo "Computed baseUrl: " . htmlspecialchars($baseUrl) . "\n";
echo "Expected login redirect: " . htmlspecialchars($baseUrl . '/login.php') . "\n";
echo '</pre>';
?>
