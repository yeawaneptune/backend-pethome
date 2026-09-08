<?php
header("Content-Type: application/json; charset=UTF-8");
require "../db/config.php";
require "../models/pet_home.php";

$db = new Database();
$conn = $db->connect();
$petModel = new Pet_home($conn);

$notifications = $petModel->getNotifications();

echo json_encode([
    'status' => true,
    'data' => $notifications
], JSON_UNESCAPED_UNICODE);
?>
