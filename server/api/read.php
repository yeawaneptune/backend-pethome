<?php
header("Content-Type: application/json; charset=UTF-8");
require "../db/config.php";
require "../models/pet_home.php";

$db = new Database();
$conn = $db->connect();
$pet_home = new Pet_home($conn);

$type = $_GET['type'] ?? '';
$status = $_GET['status'] ?? '';
$search = $_GET['search'] ?? '';
$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

$result = $pet_home->getAll($type, $status, $search);

$data = [];
if ($result) {
    while ($row = $result->fetch_assoc()) {
        if (!empty($row['extra_images'])) {
            $decoded = json_decode($row['extra_images'], true);
            $row['extra_images'] = is_array($decoded) ? $decoded : [];
        } else {
            $row['extra_images'] = [];
        }

        if ($user_id > 0) {
            $row['is_favorite'] = $pet_home->isFavorite($user_id, (int)$row['pet_id']);
        } else {
            $row['is_favorite'] = false;
        }

        $data[] = $row;
    }
}

echo json_encode([
    'status' => true,
    'count' => count($data),
    'data' => $data
], JSON_UNESCAPED_UNICODE);
?>