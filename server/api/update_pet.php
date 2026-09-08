<?php
header("Content-Type: application/json; charset=UTF-8");
require "../db/config.php";
require "../models/pet_home.php";

$db = new Database();
$conn = $db->connect();
$petModel = new Pet_home($conn);

$pet_id = (int)($_POST['pet_id'] ?? 0);

if ($pet_id <= 0) {
    echo json_encode([
        'status' => false,
        'message' => 'ไม่พบรหัสสัตว์เลี้ยง (Invalid pet_id)'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check if updating only status
if (isset($_POST['status_only']) && $_POST['status_only'] == '1') {
    $newStatus = trim($_POST['status'] ?? 'available');
    if ($petModel->updateStatus($pet_id, $newStatus)) {
        echo json_encode([
            'status' => true,
            'message' => 'อัปเดตสถานะสำเร็จ'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'อัปเดตสถานะไม่สำเร็จ'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$pet_name = trim($_POST['pet_name'] ?? '');
$type = trim($_POST['type'] ?? 'dog');
$breed = trim($_POST['breed'] ?? '');
$age = (int)($_POST['age'] ?? 1);
$gender = trim($_POST['gender'] ?? 'ผู้');
$province = trim($_POST['province'] ?? '');
$description = trim($_POST['description'] ?? '');
$image = trim($_POST['image'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$status = trim($_POST['status'] ?? 'available');

$health_info = trim($_POST['health_info'] ?? '');
$personality = trim($_POST['personality'] ?? '');
$care_info = trim($_POST['care_info'] ?? '');
$extra_images = $_POST['extra_images'] ?? '[]';

$data = [
    'pet_name' => $pet_name,
    'type' => $type,
    'breed' => $breed,
    'age' => $age,
    'gender' => $gender,
    'province' => $province,
    'description' => $description,
    'image' => $image,
    'contact' => $contact,
    'status' => $status,
    'health_info' => $health_info,
    'personality' => $personality,
    'care_info' => $care_info,
    'extra_images' => $extra_images
];

if ($petModel->update($pet_id, $data)) {
    echo json_encode([
        'status' => true,
        'message' => 'อัปเดตข้อมูลสัตว์เลี้ยงสำเร็จ'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'อัปเดตข้อมูลสัตว์เลี้ยงไม่สำเร็จ'
    ], JSON_UNESCAPED_UNICODE);
}
?>
