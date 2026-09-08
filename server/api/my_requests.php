<?php
header("Content-Type: application/json; charset=UTF-8");
require "../db/config.php";
require "../models/pet_home.php";

$db = new Database();
$conn = $db->connect();
$petModel = new Pet_home($conn);

$user_id = (int)($_GET['user_id'] ?? $_POST['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode([
        'status' => false,
        'message' => 'กรุณาระบุ user_id'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$res = $petModel->getUserRequests($user_id);
$requests = [];

if ($res) {
    while ($row = $res->fetch_assoc()) {
        $requests[] = $row;
    }
}

echo json_encode([
    'status' => true,
    'count' => count($requests),
    'data' => $requests
], JSON_UNESCAPED_UNICODE);
?>
