<?php
header("Content-Type: application/json; charset=UTF-8");
require "../db/config.php";

$db = new Database();
$conn = $db->connect();

$sql = "SELECT user_id, name, email, role, phone, avatar, created_at FROM `user` ORDER BY user_id DESC";
$result = $conn->query($sql);

$users = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}

echo json_encode([
    'status' => true,
    'count' => count($users),
    'data' => $users
], JSON_UNESCAPED_UNICODE);
?>
