<?php
require_once 'config.php';
require_once 'send_email.php';

// Log all PayU callbacks for audit
$logDir = __DIR__ . '/logs';
if (!is_dir($logDir)) @mkdir($logDir, 0755, true);
$logData = date('Y-m-d H:i:s') . " | PayU SUCCESS | " . json_encode($_POST) . "\n";
@file_put_contents($logDir . '/payu_success.log', $logData, FILE_APPEND);

$status = isset($_POST["status"]) ? $_POST["status"] : '';
$firstname = isset($_POST["firstname"]) ? $_POST["firstname"] : '';
$amount = isset($_POST["amount"]) ? $_POST["amount"] : '';
$txnid = isset($_POST["txnid"]) ? $_POST["txnid"] : '';
$posted_hash = isset($_POST["hash"]) ? $_POST["hash"] : '';
$key = isset($_POST["key"]) ? $_POST["key"] : '';
$productinfo = isset($_POST["productinfo"]) ? $_POST["productinfo"] : '';
$email = isset($_POST["email"]) ? $_POST["email"] : '';
$salt = PAYU_SALT;

// Salt should be in correct order: salt|status|||||||||||email|firstname|productinfo|amount|txnid|key
$retHashSeq = $salt . '|' . $status . '|||||||||||' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|' . $txnid . '|' . $key;
$hash = strtolower(hash("sha512", $retHashSeq));

if ($hash != $posted_hash) {
    // Hash mismatch - possible tampering
    @file_put_contents($logDir . '/payu_tampered.log', date('Y-m-d H:i:s') . " | HASH MISMATCH | txnid=$txnid\n", FILE_APPEND);
    header("Location: ../checkout.html?status=tampered");
    exit;
} else {
    // Valid payment
    if (isset($_SESSION['current_order']) && $_SESSION['current_order']['txnid'] === $txnid) {
        $_SESSION['current_order']['status'] = 'success';
        $_SESSION['current_order']['payment_method'] = 'PayU Online';

        // Database Integration: Update Order Status
        require_once 'db_connect.php';
        if (isset($conn) && $conn->ping()) {
            $stmt = $conn->prepare("UPDATE orders SET status = 'success' WHERE txnid = ?");
            $stmt->bind_param("s", $txnid);
            $stmt->execute();
            $stmt->close();
        }

        // Send confirmation email to admin + customer
        sendOrderEmail($_SESSION['current_order']);
    }

    // Redirect to frontend order success UI
    header("Location: ../checkout.html?status=success");
    exit;
}
?>