<?php
require_once 'config.php';

$data = json_decode(file_get_contents('php://input'), true);
$mobile = isset($data['mobile']) ? trim($data['mobile']) : '';

if (empty($mobile) || !preg_match("/^[0-9]{10}$/", $mobile)) {
    sendJsonResponse('error', 'Valid 10-digit mobile number is required.');
}

// Generate 4-digit OTP
$otp = rand(1000, 9999);
$_SESSION['login_otp'] = $otp;
$_SESSION['login_mobile'] = $mobile;
$_SESSION['login_otp_time'] = time();

// Send OTP via MetaReach using EXACT DLT Approved Template
$message = urlencode("Welcome to AGPK Academy login. Your verification code is {$otp}. This OTP will expire in 5 minutes");
$url = "https://sms.metareach.in/vb/apikey.php?apikey=" . METAREACH_API_KEY . "&senderid=" . METAREACH_SENDER_ID . "&number=" . $mobile . "&message=" . $message . "&templateid=" . METAREACH_TEMPLATE_ID;

// Enable error reporting for debug
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0); // Disable SSL verification for shared hosting stability
$response = curl_exec($ch);
$error = curl_error($ch);
curl_close($ch);

if ($response !== false && empty($error)) {
    sendJsonResponse('success', 'OTP sent successfully.', ['gateway_response' => trim($response)]);
} else {
    sendJsonResponse('error', 'Failed to send OTP. SMS Gateway unreachable. Error: ' . $error);
}