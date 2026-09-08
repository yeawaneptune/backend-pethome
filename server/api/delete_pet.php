<?php
header("Content-Type: application/json; charset=UTF-8");
require "../db/config.php";
require "../models/pet_home.php";

$db = new Database();
$conn = $db->connect();
$petModel = new Pet_home($conn);

$pet_id = (int)($_POST['pet_id'] ?? $_GET['pet_id'] ?? 0);

if ($pet_id <= 0) {
    echo json_encode([
        'status' => false,
        'message' => 'ไม่พบรหัสสัตว์เลี้ยง (Invalid pet_id)'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

if ($petModel->delete($pet_id)) {
    echo json_encode([
        'status' => true,
        'message' => 'ลบข้อมูลสัตว์เลี้ยงสำเร็จ'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'ลบข้อมูลไม่สำเร็จ'
    ], JSON_UNESCAPED_UNICODE);
}
?>
