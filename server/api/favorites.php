<?php
header("Content-Type: application/json; charset=UTF-8");
require "../db/config.php";
require "../models/pet_home.php";

$db = new Database();
$conn = $db->connect();
$petModel = new Pet_home($conn);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

if ($method === 'POST') {
    $user_id = (int)($_POST['user_id'] ?? 0);
    $pet_id = (int)($_POST['pet_id'] ?? 0);

    if ($user_id <= 0 || $pet_id <= 0) {
        echo json_encode([
            'status' => false,
            'message' => 'กรุณาระบุ user_id และ pet_id'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $result = $petModel->toggleFavorite($user_id, $pet_id);
    echo json_encode([
        'status' => true,
        'is_favorite' => $result['is_favorite'],
        'message' => $result['message']
    ], JSON_UNESCAPED_UNICODE);
} else {
    $user_id = (int)($_GET['user_id'] ?? 0);

    if ($user_id <= 0) {
        echo json_encode([
            'status' => false,
            'message' => 'กรุณาระบุ user_id'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $res = $petModel->getFavorites($user_id);
    $favorites = [];

    if ($res) {
        while ($row = $res->fetch_assoc()) {
            if (!empty($row['extra_images'])) {
                $decoded = json_decode($row['extra_images'], true);
                $row['extra_images'] = is_array($decoded) ? $decoded : [];
            } else {
                $row['extra_images'] = [];
            }
            $row['is_favorite'] = true;
            $favorites[] = $row;
        }
    }

    echo json_encode([
        'status' => true,
        'count' => count($favorites),
        'data' => $favorites
    ], JSON_UNESCAPED_UNICODE);
}
?>
