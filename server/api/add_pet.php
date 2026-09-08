<?php
header("Content-Type: application/json; charset=UTF-8");
require "../db/config.php";
require "../models/pet_home.php";

$db = new Database();
$conn = $db->connect();
$petModel = new Pet_home($conn);

// Validate required fields
if (!isset($_POST['owner_id'])) {
    echo json_encode([
        'status' => false,
        'message' => 'ต้องระบุ owner_id ของผู้ใช้ที่เป็นเจ้าของ'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}
$owner_id = (int)$_POST['owner_id'];

$pet_name = trim($_POST['pet_name'] ?? '');
$breed = trim($_POST['breed'] ?? '');
$province = trim($_POST['province'] ?? '');
if (empty($pet_name) || empty($breed) || empty($province)) {
    echo json_encode([
        'status' => false,
        'message' => 'กรุณากรอกชื่อสัตว์เลี้ยง สายพันธุ์ และจังหวัด'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$type = trim($_POST['type'] ?? 'dog');
$age = (int)($_POST['age'] ?? 1);
$gender = trim($_POST['gender'] ?? 'ผู้');
$description = trim($_POST['description'] ?? '');
$image = trim($_POST['image'] ?? '');
$contact = trim($_POST['contact'] ?? '');
$status = trim($_POST['status'] ?? 'available');
$health_info = trim($_POST['health_info'] ?? '');
$personality = trim($_POST['personality'] ?? '');
$care_info = trim($_POST['care_info'] ?? '');
$extra_images = $_POST['extra_images'] ?? '[]';

if (empty($image)) {
    if ($type === 'cat') {
        $image = 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600';
    } elseif ($type === 'bird') {
        $image = 'https://images.unsplash.com/photo-1552728089-57bdde30beb3?w=600';
    } elseif ($type === 'other') {
        $image = 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?w=600';
    } else {
        $image = 'https://images.unsplash.com/photo-1552053831-71594a27632d?w=600';
    }
}

$data = [
    'owner_id' => $owner_id,
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

$newId = $petModel->create($data);

if ($newId) {
    echo json_encode([
        'status' => true,
        'message' => 'เพิ่มข้อมูลสัตว์เลี้ยงสำเร็จ',
        'pet_id' => $newId
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'เพิ่มข้อมูลสัตว์เลี้ยงไม่สำเร็จ'
    ], JSON_UNESCAPED_UNICODE);
}
?>
