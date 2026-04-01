<?php
require_once 'config.php';

// Ensure user is logged in
if (!isset($_SESSION['user_logged_in']) || $_SESSION['user_logged_in'] !== true) {
    sendJsonResponse('error', 'User not logged in. Please verify OTP first.');
}

$data = json_decode(file_get_contents('php://input'), true);

if (!$data) {
    $data = $_POST; // Fallback
}

$payment_method = isset($data['payment_method']) ? trim($data['payment_method']) : 'card';
$firstname = isset($data['firstname']) ? trim($data['firstname']) : '';
$email = isset($data['email']) ? trim($data['email']) : '';
$phone = $_SESSION['user_mobile']; // enforce using the logged in mobile
$cart = isset($data['cart']) ? $data['cart'] : [];
$coupon = isset($data['coupon_code']) ? strtoupper(trim($data['coupon_code'])) : '';

$productinfo = "Robotics Kits Order";
$address = isset($data['address']) ? trim($data['address']) : '';
$city = isset($data['city']) ? trim($data['city']) : ''; // Added city parameter
$state = isset($data['state']) ? trim($data['state']) : '';
$zip = isset($data['zip']) ? trim($data['zip']) : '';
$cartData = isset($data['cart']) ? json_encode($data['cart']) : '[]';

// Securely calculate amount from backend prices mapping
$valid_prices = [
    'kit2' => 5909,
    'kit3' => 10067,
    'kit4' => 4369,
    'kit5' => 8217,
    'kit6' => 5737,
    'kit7' => 5394,
    'kit8' => 4369,
    'kit9' => 5566,
    'kit10' => 6593,
    'kit11' => 6593,
];

$calculated_subtotal = 0;
foreach ($cart as $item) {
    if (isset($valid_prices[$item['id']])) {
        $calculated_subtotal += $valid_prices[$item['id']] * (int) $item['quantity'];
    }
}

if (empty($firstname) || empty($email) || empty($address) || empty($city) || $calculated_subtotal <= 0) {
    sendJsonResponse('error', 'Missing required order details or cart invalid.');
}

// Out of stock items — reject if cart contains these
$out_of_stock = ['kit3', 'kit4', 'kit7', 'kit8']; // Otto Ninja, Plug & Play, Smart IOT Home, MR Bot
foreach ($cart as $item) {
    if (in_array($item['id'], $out_of_stock)) {
        sendJsonResponse('error', 'Sorry, "' . ($item['name'] ?? $item['id']) . '" is currently out of stock.');
    }
}

$discountAmount = 0;
if ($coupon) {
    $validCoupons = [
        'ROBO10' => ['type' => 'percent', 'value' => 10],
        'FLAT500' => ['type' => 'fixed', 'value' => 500],
        'SAPNA40' => ['type' => 'percent', 'value' => 40]  // 40% off all kits
    ];
    if (array_key_exists($coupon, $validCoupons)) {
        $discountDetails = $validCoupons[$coupon];
        if ($discountDetails['type'] === 'percent') {
            $discountAmount = floor($calculated_subtotal * ($discountDetails['value'] / 100));
        } else if ($discountDetails['type'] === 'fixed') {
            $discountAmount = $discountDetails['value'];
        }
        if ($discountAmount >= $calculated_subtotal) {
            $discountAmount = $calculated_subtotal - 1;
        }
    }
}

$subtotalAfterDiscount = max(0, $calculated_subtotal - $discountAmount);
$gst = round($subtotalAfterDiscount * 0.18);
$amount = $subtotalAfterDiscount + $gst;

// Generate unique transaction ID
$txnid = substr(hash('sha256', mt_rand() . microtime()), 0, 20);

// Save order to session temporarily for email sending and success tracking
$_SESSION['current_order'] = [
    'txnid' => $txnid,
    'firstname' => $firstname,
    'email' => $email,
    'phone' => $phone,
    'amount' => $amount,
    'address' => $address,
    'city' => $city,
    'state' => $state,
    'zip' => $zip,
    'cart' => $cartData,
    'subtotal' => $calculated_subtotal,
    'discount' => $discountAmount,
    'coupon' => $coupon,
    'gst' => $gst,
    'status' => 'pending'
];

// Database Integration: Save Order
require_once 'db_connect.php';
if (isset($conn) && $conn->ping()) {
    $stmt = $conn->prepare("INSERT INTO orders (user_mobile, txnid, subtotal, discount, gst, total_amount, coupon_used, status, address, city, state, zip) VALUES (?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, ?, ?)");
    $stmt->bind_param("ssddddsssss", $phone, $txnid, $calculated_subtotal, $discountAmount, $gst, $amount, $coupon, $address, $city, $state, $zip);
    
    if ($stmt->execute()) {
        $order_id = $stmt->insert_id;
        $_SESSION['current_order']['id'] = $order_id;

        // Insert Order Items
        $item_stmt = $conn->prepare("INSERT INTO order_items (order_id, product_id, quantity, price_at_time) VALUES (?, ?, ?, ?)");
        foreach ($cart as $item) {
            if (isset($valid_prices[$item['id']])) {
                $item_stmt->bind_param("isid", $order_id, $item['id'], $item['quantity'], $valid_prices[$item['id']]);
                $item_stmt->execute();
            }
        }
        $item_stmt->close();
    }
    $stmt->close();
}

if ($payment_method === 'cod') {
    // Process COD order directly
    require_once 'send_email.php';

    $_SESSION['current_order']['status'] = 'success';
    $_SESSION['current_order']['payment_method'] = 'Cash on Delivery (COD)';

    sendOrderEmail($_SESSION['current_order']);

    // Clear cart session if any, but since cart is in localStorage, we just tell frontend to clear it
    sendJsonResponse('success', 'Order placed successfully via COD.', ['redirect' => 'checkout.html?status=success']);
} else {
    // Initiate PayU
    // Hash sequence: key|txnid|amount|productinfo|firstname|email|udf1|udf2|udf3|udf4|udf5||||||SALT
    $hashseq = PAYU_KEY . '|' . $txnid . '|' . $amount . '|' . $productinfo . '|' . $firstname . '|' . $email . '|||||||||||' . PAYU_SALT;
    $hash = strtolower(hash("sha512", $hashseq));

    $payu_data = [
        'key' => PAYU_KEY,
        'txnid' => $txnid,
        'amount' => $amount,
        'productinfo' => $productinfo,
        'firstname' => $firstname,
        'email' => $email,
        'phone' => $phone,
        'surl' => PAYU_SUCCESS_URL,
        'furl' => PAYU_FAIL_URL,
        'hash' => $hash,
        'service_provider' => 'payu_paisa'
    ];

    sendJsonResponse('success', 'Redirecting to payment gateway...', [
        'payu_url' => PAYU_BASE_URL . '/_payment',
        'payu_data' => $payu_data
    ]);
}
?>