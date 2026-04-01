<?php
require_once 'config.php';

// Log failure details for debugging
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
$logData = date('Y-m-d H:i:s') . " | PayU FAIL | " . json_encode($_POST) . "\n";
@file_put_contents($logDir . '/payu_fails.log', $logData, FILE_APPEND);

header("Location: ../checkout.html?status=failed");
exit;
?>