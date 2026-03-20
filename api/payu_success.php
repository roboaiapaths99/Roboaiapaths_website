<?php
require_once 'config.php';
require_once 'send_email.php';

$status = $_POST["status"];
$firstname = $_POST["firstname"];
$amount = $_POST["amount"];
$txnid = $_POST["txnid"];
$posted_hash = $_POST["hash"];
$key = $_POST["key"];
$productinfo = $_POST["productinfo"];
$email = $_POST["email"];
$salt = PAYU_SALT;

// Salt should be in correct order: salt|status|||||||||||email|firstname|productinfo|amount|txnid|key
$retHashSeq = $salt . '|' . $status . '|||||||||||' . $email . '|' . $firstname . '|' . $productinfo . '|' . $amount . '|' . $txnid . '|' . $key;
$hash = strtolower(hash("sha512", $retHashSeq));

if ($hash != $posted_hash) {
    // Hash mismatch
    // Redirect to fail or show tampered message
    header("Location: ../checkout.html?status=tampered");
    exit;
} else {
    // Valid payment
    if (isset($_SESSION['current_order']) && $_SESSION['current_order']['txnid'] === $txnid) {
        $_SESSION['current_order']['status'] = 'success';
        $_SESSION['current_order']['payment_method'] = 'PayU';

        // Send email
        sendOrderEmail($_SESSION['current_order']);
    }

    // Redirect to frontend order success UI
    header("Location: ../checkout.html?status=success");
    exit;
}
?>