<?php
header('Content-Type: application/json');

$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, true);

$coupon = strtoupper(trim($input['coupon'] ?? ''));
$subtotal = isset($input['subtotal']) ? floatval($input['subtotal']) : 0;

if (!$coupon || $subtotal <= 0) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request']);
    exit;
}

// Hardcoded coupons for demonstration
$validCoupons = [
    'ROBO10' => ['type' => 'percent', 'value' => 10], // 10% off
    'FLAT500' => ['type' => 'fixed', 'value' => 500]  // ₹500 flat off
];

if (array_key_exists($coupon, $validCoupons)) {
    $discountDetails = $validCoupons[$coupon];
    $discountAmount = 0;

    if ($discountDetails['type'] === 'percent') {
        $discountAmount = floor($subtotal * ($discountDetails['value'] / 100));
    } else if ($discountDetails['type'] === 'fixed') {
        $discountAmount = $discountDetails['value'];
    }

    if ($discountAmount >= $subtotal) {
        $discountAmount = $subtotal - 1; // Keep total minimum ₹ 1
    }

    echo json_encode([
        'status' => 'success',
        'discount' => $discountAmount
    ]);
} else {
    echo json_encode([
        'status' => 'error',
        'message' => 'Invalid or expired coupon code'
    ]);
}
