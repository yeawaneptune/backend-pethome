<?php
header("Content-Type: application/json; charset=UTF-8");
require "../db/config.php";

$db = new Database();
$conn = $db->connect();

$user_id = (int)($_POST['user_id'] ?? $_GET['user_id'] ?? 0);

if ($user_id <= 0) {
    echo json_encode([
        'status' => false,
        'message' => 'ไม่พบรหัสผู้ใช้งาน (Invalid user_id)'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// ป้องกันการลบ Admin บัญชีหลัก (user_id = 1)
if ($user_id === 1) {
    echo json_encode([
        'status' => false,
        'message' => 'ไม่สามารถลบ Super Admin หลักได้'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$stmt = $conn->prepare("DELETE FROM `user` WHERE user_id = ?");
$stmt->bind_param("i", $user_id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => true,
        'message' => 'ลบผู้ใช้งานสำเร็จ'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'ไม่สามารถลบผู้ใช้งานได้'
    ], JSON_UNESCAPED_UNICODE);
}
?>
