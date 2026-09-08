<?php

header("Content-Type: application/json; charset=UTF-8");

require "../db/config.php";

$db = new Database();
$conn = $db->connect();

$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($email) || empty($password)) {

    echo json_encode([
        "status" => false,
        "message" => "กรุณากรอกอีเมลและรหัสผ่าน"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$sql = "SELECT user_id, name, email, password, role
        FROM user
        WHERE email = ?";

$stmt = $conn->prepare($sql);

$stmt->bind_param("s", $email);

$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows === 0) {

    echo json_encode([
        "status" => false,
        "message" => "ไม่พบอีเมลนี้"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

$user = $result->fetch_assoc();

// ตรวจสอบ password
if (!password_verify($password, $user['password'])) {

    echo json_encode([
        "status" => false,
        "message" => "รหัสผ่านไม่ถูกต้อง"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// ไม่ส่ง password กลับไป Flutter
unset($user['password']);

echo json_encode([
    "status" => true,
    "message" => "เข้าสู่ระบบสำเร็จ",
    "user" => $user
], JSON_UNESCAPED_UNICODE);