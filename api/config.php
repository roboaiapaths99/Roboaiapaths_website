<?php
session_start();

// PayU Credentials
define('PAYU_ENV', 'live'); // 'test' or 'live'
define('PAYU_KEY', 'aHh60S');
define('PAYU_SALT', 'AxiquForToDvI5quAcXQtuOjEnYVgBWz');
define('PAYU_BASE_URL', PAYU_ENV === 'test' ? 'https://test.payu.in' : 'https://secure.payu.in');

$protocol = isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http";
$host = $_SERVER['HTTP_HOST'];
define('BASE_URL', $protocol . "://" . $host);

define('PAYU_SUCCESS_URL', BASE_URL . '/api/payu_success.php');
define('PAYU_FAIL_URL', BASE_URL . '/api/payu_fail.php');

// MetaReach SMS Credentials
define('METAREACH_API_KEY', 'i0caSeRfCMXWdVij');
define('METAREACH_SENDER_ID', 'AGPKAC');
define('METAREACH_TEMPLATE_ID', '1707177071739047190');

// Debug Mode Settings (Disable for Production)
define('DEBUG_MODE', false);
define('DEBUG_OTP', '5555');

// Email Settings
define('ADMIN_EMAILS', 'roboaiapaths@gmail.com,roboaiapaths9@gmail.com');

// Admin Settings
define('ADMIN_MOBILE', '9990911093');

// Chatbot AI Configuration (loaded from secrets.php - not committed to git)
require_once __DIR__ . '/secrets.php';

// Helper to send JSON response
function sendJsonResponse($status, $message, $data = [])
{
    header('Content-Type: application/json');
    echo json_encode(array_merge(['status' => $status, 'message' => $message], $data));
    exit;
}
