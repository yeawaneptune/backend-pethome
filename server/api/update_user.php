<?php
header("Content-Type: application/json; charset=UTF-8");
require "../db/config.php";

$db = new Database();
$conn = $db->connect();

$user_id = (int)($_POST['user_id'] ?? 0);
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$role = trim($_POST['role'] ?? '');

if ($user_id <= 0) {
    echo json_encode([
        'status' => false,
        'message' => 'ไม่พบรหัสผู้ใช้งาน'
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

// Check role toggle only
if (isset($_POST['role_only']) && $_POST['role_only'] == '1') {
    if ($user_id === 1) {
        echo json_encode([
            'status' => false,
            'message' => 'ไม่สามารถเปลี่ยนสิทธิ์ Super Admin ได้'
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    $stmt = $conn->prepare("UPDATE `user` SET `role` = ? WHERE user_id = ?");
    $stmt->bind_param("si", $role, $user_id);
    if ($stmt->execute()) {
        echo json_encode([
            'status' => true,
            'message' => 'เปลี่ยนสิทธิ์สำเร็จ'
        ], JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode([
            'status' => false,
            'message' => 'ไม่สามารถเปลี่ยนสิทธิ์ได้'
        ], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

$stmt = $conn->prepare("UPDATE `user` SET `name` = ?, `phone` = ? WHERE user_id = ?");
$stmt->bind_param("ssi", $name, $phone, $user_id);

if ($stmt->execute()) {
    echo json_encode([
        'status' => true,
        'message' => 'อัปเดตข้อมูลผู้ใช้สำเร็จ'
    ], JSON_UNESCAPED_UNICODE);
} else {
    echo json_encode([
        'status' => false,
        'message' => 'อัปเดตข้อมูลไม่สำเร็จ'
    ], JSON_UNESCAPED_UNICODE);
}
?>
