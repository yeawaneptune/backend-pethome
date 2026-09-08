<?php
header("Content-Type: application/json; charset=UTF-8");
require "../db/config.php";

$db = new Database();
$conn = $db->connect();

$pet_id = (int)($_POST['pet_id'] ?? 0);
$user_id = (int)($_POST['user_id'] ?? 0);
$adopter_name = trim($_POST['adopter_name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$address = trim($_POST['address'] ?? '');
$reason = trim($_POST['reason'] ?? '');

if ($pet_id <= 0 || empty($adopter_name) || empty($phone)) {
    echo json_encode([
        'status' => false,
        'message' => 'กรุณากรอกชื่อและเบอร์โทรศัพท์สำหรับติดต่อกลับ'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("INSERT INTO `adoption_requests` (`pet_id`, `user_id`, `adopter_name`, `phone`, `address`, `reason`) 
                        VALUES (?, ?, ?, ?, ?, ?)");
$stmt->bind_param("iissss", $pet_id, $user_id, $adopter_name, $phone, $address, $reason);

if ($stmt->execute()) {
    echo json_encode([
        'status' => true,
        'message' => 'ส่งคำขอรับเลี้ยงเรียบร้อยแล้ว เจ้าหน้าที่จะติดต่อกลับโดยเร็วที่สุด'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'ไม่สามารถส่งคำขอรับเลี้ยงได้'
    ], JSON_UNESCAPED_UNICODE);
}
?>
