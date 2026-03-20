<?php
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$mobile = isset($data['mobile']) ? trim($data['mobile']) : '';
$otp = isset($data['otp']) ? trim($data['otp']) : '';

if (empty($mobile) || empty($otp)) {
    sendJsonResponse('error', 'Mobile and OTP are required.');
}

if (!isset($_SESSION['login_otp']) || $_SESSION['login_mobile'] !== $mobile) {
    sendJsonResponse('error', 'OTP session expired or invalid. Please request a new OTP.');
}

if (time() - $_SESSION['login_otp_time'] > 300) { // 5 mins validity
    sendJsonResponse('error', 'OTP has expired.');
}

if ((string) $_SESSION['login_otp'] === (string) $otp) {
    // Login success
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_mobile'] = $mobile;

    // Clear OTP
    unset($_SESSION['login_otp']);
    unset($_SESSION['login_otp_time']);

    sendJsonResponse('success', 'Login successful.', ['mobile' => $mobile]);
} else {
    sendJsonResponse('error', 'Invalid OTP.');
}
?>