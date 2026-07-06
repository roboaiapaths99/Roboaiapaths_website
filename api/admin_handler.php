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
    $message = urlencode("RoboAIAPaths Admin Login. Your OTP is {$otp}. Confidiential.");
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

// ============================================================
// CHATBOT ADMIN ACTIONS (replaces Python FastAPI admin endpoints)
// ============================================================

// 6. Chatbot Stats
if ($action === 'cb_stats') {
    $total_leads = $conn->query("SELECT COUNT(*) as c FROM chatbot_leads")->fetch_assoc()['c'];
    $total_chats = $conn->query("SELECT COUNT(*) as c FROM chatbot_logs")->fetch_assoc()['c'];
    $new_leads = $conn->query("SELECT COUNT(*) as c FROM chatbot_leads WHERE status = 'New'")->fetch_assoc()['c'];
    $contacted = $conn->query("SELECT COUNT(*) as c FROM chatbot_leads WHERE status = 'Contacted'")->fetch_assoc()['c'];
    $demo_scheduled = $conn->query("SELECT COUNT(*) as c FROM chatbot_leads WHERE status = 'Demo Scheduled'")->fetch_assoc()['c'];
    $joined = $conn->query("SELECT COUNT(*) as c FROM chatbot_leads WHERE status = 'Joined'")->fetch_assoc()['c'];
    $not_interested = $conn->query("SELECT COUNT(*) as c FROM chatbot_leads WHERE status = 'Not Interested'")->fetch_assoc()['c'];

    $today = date('Y-m-d');
    $today_leads = $conn->query("SELECT COUNT(*) as c FROM chatbot_leads WHERE DATE(created_at) = '$today'")->fetch_assoc()['c'];

    $conversion_rate = $total_leads > 0 ? round(($joined / $total_leads) * 100, 2) : 0;

    sendJsonResponse('success', 'Stats fetched.', [
        'total_leads' => (int)$total_leads,
        'total_chats' => (int)$total_chats,
        'today_leads' => (int)$today_leads,
        'new_leads' => (int)$new_leads,
        'contacted_leads' => (int)$contacted,
        'demo_scheduled' => (int)$demo_scheduled,
        'joined_leads' => (int)$joined,
        'not_interested' => (int)$not_interested,
        'conversion_rate' => $conversion_rate
    ]);
}

// 7. Get Chatbot Leads
if ($action === 'cb_leads') {
    $search = isset($data['search']) ? $conn->real_escape_string($data['search']) : '';
    $status_filter = isset($data['status_filter']) ? $conn->real_escape_string($data['status_filter']) : 'All';

    $where = [];
    if ($status_filter && $status_filter !== 'All') {
        $where[] = "status = '$status_filter'";
    }
    if ($search) {
        $where[] = "(name LIKE '%$search%' OR phone LIKE '%$search%' OR child_class LIKE '%$search%' OR course_interest LIKE '%$search%' OR message LIKE '%$search%' OR city LIKE '%$search%')";
    }

    $where_sql = count($where) > 0 ? 'WHERE ' . implode(' AND ', $where) : '';
    $sql = "SELECT * FROM chatbot_leads $where_sql ORDER BY created_at DESC";
    $result = $conn->query($sql);

    $leads = [];
    while ($row = $result->fetch_assoc()) {
        // Format dates for frontend
        $row['followup_date'] = $row['followup_date'] ? $row['followup_date'] : '';
        $row['demo_date'] = $row['demo_date'] ? $row['demo_date'] : '';
        $leads[] = $row;
    }

    sendJsonResponse('success', 'Leads fetched.', [
        'total' => count($leads),
        'leads' => $leads
    ]);
}

// 8. Get Chatbot Logs
if ($action === 'cb_chats') {
    $result = $conn->query("SELECT * FROM chatbot_logs ORDER BY created_at DESC LIMIT 200");
    $logs = [];
    while ($row = $result->fetch_assoc()) {
        $logs[] = $row;
    }
    sendJsonResponse('success', 'Logs fetched.', [
        'total' => count($logs),
        'logs' => $logs
    ]);
}

// 9. Update Chatbot Lead Status
if ($action === 'cb_update_status') {
    $lead_id = isset($data['lead_id']) ? intval($data['lead_id']) : 0;
    $new_status = isset($data['status']) ? $conn->real_escape_string($data['status']) : '';

    $allowed = ['New', 'Contacted', 'Demo Scheduled', 'Joined', 'Not Interested'];
    if ($lead_id > 0 && in_array($new_status, $allowed)) {
        $stmt = $conn->prepare("UPDATE chatbot_leads SET status = ? WHERE id = ?");
        $stmt->bind_param("si", $new_status, $lead_id);
        if ($stmt->execute()) {
            sendJsonResponse('success', 'Lead status updated.');
        } else {
            sendJsonResponse('error', 'Failed to update status.');
        }
        $stmt->close();
    } else {
        sendJsonResponse('error', 'Invalid lead ID or status.');
    }
}

// 10. Update Chatbot Lead CRM Details (notes, followup, demo date)
if ($action === 'cb_update_crm') {
    $lead_id = isset($data['lead_id']) ? intval($data['lead_id']) : 0;
    $notes = isset($data['notes']) ? $data['notes'] : '';
    $followup_date = isset($data['followup_date']) && !empty($data['followup_date']) ? $data['followup_date'] : null;
    $demo_date = isset($data['demo_date']) && !empty($data['demo_date']) ? $data['demo_date'] : null;

    if ($lead_id > 0) {
        $stmt = $conn->prepare("UPDATE chatbot_leads SET notes = ?, followup_date = ?, demo_date = ? WHERE id = ?");
        $stmt->bind_param("sssi", $notes, $followup_date, $demo_date, $lead_id);
        if ($stmt->execute()) {
            sendJsonResponse('success', 'CRM details updated.');
        } else {
            sendJsonResponse('error', 'Failed to update CRM details.');
        }
        $stmt->close();
    } else {
        sendJsonResponse('error', 'Invalid lead ID.');
    }
}

// 11. Delete Chatbot Lead
if ($action === 'cb_delete_lead') {
    $lead_id = isset($data['lead_id']) ? intval($data['lead_id']) : 0;
    if ($lead_id > 0) {
        $stmt = $conn->prepare("DELETE FROM chatbot_leads WHERE id = ?");
        $stmt->bind_param("i", $lead_id);
        if ($stmt->execute()) {
            sendJsonResponse('success', 'Lead deleted.');
        } else {
            sendJsonResponse('error', 'Failed to delete lead.');
        }
        $stmt->close();
    } else {
        sendJsonResponse('error', 'Invalid lead ID.');
    }
}

// 12. Export Chatbot Leads as CSV
if ($action === 'cb_export_csv') {
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename=roboaiapaths_chatbot_leads.csv');

    $output = fopen('php://output', 'w');
    fputcsv($output, ['Name', 'Phone', 'Class', 'Course', 'City', 'Status', 'Notes', 'Follow-up Date', 'Demo Date', 'Message', 'Created At']);

    $result = $conn->query("SELECT * FROM chatbot_leads ORDER BY created_at DESC");
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['name'],
            $row['phone'],
            $row['child_class'],
            $row['course_interest'],
            $row['city'],
            $row['status'],
            $row['notes'],
            $row['followup_date'],
            $row['demo_date'],
            $row['message'],
            $row['created_at']
        ]);
    }
    fclose($output);
    exit;
}
?>
