<?php
require_once 'config.php';

// Log failure if needed
// file_put_contents('payu_fail_log.txt', print_r($_POST, true), FILE_APPEND);

header("Location: ../checkout.html?status=failed");
exit;
?>