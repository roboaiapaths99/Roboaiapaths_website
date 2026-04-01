<?php
require_once 'config.php';

header('Content-Type: application/json');

if (isset($_SESSION['user_logged_in']) && $_SESSION['user_logged_in'] === true) {
    echo json_encode([
        'status' => 'success',
        'logged_in' => true,
        'mobile' => $_SESSION['user_mobile']
    ]);
} else {
    echo json_encode([
        'status' => 'success',
        'logged_in' => false
    ]);
}
?>
