<?php
require_once 'config.php';

session_unset();
session_destroy();

header('Content-Type: application/json');
echo json_encode(['status' => 'success', 'message' => 'Logged out successfully']);
?>
