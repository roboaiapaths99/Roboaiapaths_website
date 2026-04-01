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

// Check DEBUG_OTP fallback if enabled
$isValid = (string) $_SESSION['login_otp'] === (string) $otp;
if (!$isValid && defined('DEBUG_MODE') && DEBUG_MODE === true && (string) $otp === (string) DEBUG_OTP) {
    $isValid = true;
}

if ($isValid) {
    // Login success
    $_SESSION['user_logged_in'] = true;
    $_SESSION['user_mobile'] = $mobile;

    // Database Integration: Save/Update user
    require_once 'db_connect.php';
    if (isset($conn) && $conn->ping()) {
        $stmt = $conn->prepare("INSERT INTO msg_users (mobile) VALUES (?) ON DUPLICATE KEY UPDATE created_at = CURRENT_TIMESTAMP");
        $stmt->bind_param("s", $mobile);
        $stmt->execute();
        $stmt->close();
    }

    // Clear OTP
    unset($_SESSION['login_otp']);
    unset($_SESSION['login_otp_time']);

    sendJsonResponse('success', 'Login successful.', ['mobile' => $mobile]);
} else {
    sendJsonResponse('error', 'Invalid OTP.');
}
?>