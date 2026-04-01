<?php
require_once 'config.php';
require_once 'db_connect.php';

// Secure the script
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    die("Unauthorized access.");
}

// Fetch all orders
$sql = "SELECT o.*, 
        (SELECT GROUP_CONCAT(p.name SEPARATOR ' | ') FROM order_items oi 
         JOIN product_kits p ON oi.product_id = p.id 
         WHERE oi.order_id = o.id) as items
        FROM orders o ORDER BY o.created_at DESC";
$result = $conn->query($sql);

$filename = "RoboAIAPaths_Orders_" . date('Y-m-d_H-i') . ".csv";

// Set Headers for Download
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=' . $filename);

// Open output stream
$output = fopen('php://output', 'w');

// Add CSV Header row
fputcsv($output, ['Order ID', 'Date', 'Transaction ID', 'Customer Mobile', 'Subtotal', 'Discount', 'GST', 'Total Amount', 'Status', 'Address', 'City', 'State', 'Zip', 'Items Purchased']);

// Add Data rows
if ($result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        fputcsv($output, [
            $row['id'],
            $row['created_at'],
            $row['txnid'],
            $row['user_mobile'],
            $row['subtotal'],
            $row['discount'],
            $row['gst'],
            $row['total_amount'],
            $row['status'],
            $row['address'],
            $row['city'],
            $row['state'],
            $row['zip'],
            $row['items']
        ]);
    }
}

fclose($output);
exit;
?>
