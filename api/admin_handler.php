<?php
require_once 'config.php';
require_once 'db_connect.php';

$data = json_decode(file_get_contents('php://input'), true);
$action = isset($data['action']) ? $data['action'] : (isset($_GET['action']) ? $_GET['action'] : '');

// 1. Send Admin OTP
if ($action === 'send_otp') {
    $mobile = isset($data['mobile']) ? trim($data['mobile']) : '';
    if ($mobile !== ADMIN_MOBILE) {
        sendJsonResponse('error', 'Unauthorized access.');
    }

    $otp = rand(1000, 9999);
    $_SESSION['admin_login_otp'] = $otp;
    $_SESSION['admin_login_mobile'] = $mobile;
    $_SESSION['admin_otp_time'] = time();

    if (defined('DEBUG_MODE') && DEBUG_MODE === true) {
        $_SESSION['admin_login_otp'] = '9999';
        sendJsonResponse('success', 'Admin Debug: Use 9999', ['debug' => true]);
    }

    // Reuse MetaReach SMS Logic
    $message = urlencode("Robo AI Paths Admin Login. Your OTP is {$otp}. Confidiential.");
    $url = "https://sms.metareach.in/vb/apikey.php?apikey=" . METAREACH_API_KEY . "&senderid=" . METAREACH_SENDER_ID . "&number=" . $mobile . "&message=" . $message . "&templateid=" . METAREACH_TEMPLATE_ID;

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
    $response = curl_exec($ch);
    curl_close($ch);

    if ($response !== false) {
        sendJsonResponse('success', 'Admin OTP sent.');
    } else {
        sendJsonResponse('error', 'Failed to send SMS.');
    }
}

// 2. Verify Admin OTP
if ($action === 'verify_otp') {
    $otp = isset($data['otp']) ? trim($data['otp']) : '';
    if ($otp == $_SESSION['admin_login_otp']) {
        $_SESSION['admin_logged_in'] = true;
        sendJsonResponse('success', 'Admin authorized.');
    } else {
        sendJsonResponse('error', 'Invalid OTP.');
    }
}

// SECURE ALL SUBSEQUENT ACTIONS
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    if ($action !== 'send_otp' && $action !== 'verify_otp') {
        sendJsonResponse('error', 'Unauthorized.');
    }
}

// 3. Fetch Orders
if ($action === 'get_orders') {
    $sql = "SELECT o.*, 
            (SELECT COUNT(*) FROM order_items WHERE order_id = o.id) as item_count 
            FROM orders o ORDER BY o.created_at DESC";
    $result = $conn->query($sql);
    $orders = [];
    while ($row = $result->fetch_assoc()) {
        $orders[] = $row;
    }
    sendJsonResponse('success', 'Orders fetched.', ['orders' => $orders]);
}

// 4. Get Order Items
if ($action === 'get_items') {
    $order_id = isset($data['order_id']) ? intval($data['order_id']) : 0;
    $sql = "SELECT oi.*, p.name FROM order_items oi 
            JOIN product_kits p ON oi.product_id = p.id 
            WHERE oi.order_id = $order_id";
    $result = $conn->query($sql);
    $items = [];
    while ($row = $result->fetch_assoc()) {
        $items[] = $row;
    }
    sendJsonResponse('success', 'Items fetched.', ['items' => $items]);
}

// 5. Update Status
if ($action === 'update_status') {
    $order_id = isset($data['order_id']) ? intval($data['order_id']) : 0;
    $new_status = isset($data['status']) ? $conn->real_escape_string($data['status']) : '';
    
    $allowed = ['pending', 'success', 'failed', 'tampered'];
    if (in_array($new_status, $allowed)) {
        $sql = "UPDATE orders SET status = '$new_status' WHERE id = $order_id";
        if ($conn->query($sql)) {
            sendJsonResponse('success', 'Status updated.');
        } else {
            sendJsonResponse('error', 'Update failed.');
        }
    } else {
        sendJsonResponse('error', 'Invalid status.');
    }
}
?>
