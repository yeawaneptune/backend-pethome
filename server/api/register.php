<?php

header("Content-Type: application/json; charset=UTF-8");

require "../db/config.php";

$db = new Database();
$conn = $db->connect();

$name = $_POST['name'] ?? '';
$email = $_POST['email'] ?? '';
$password = $_POST['password'] ?? '';

if (empty($name) || empty($email) || empty($password)) {

    echo json_encode([
        "status" => false,
        "message" => "กรุณากรอกข้อมูลให้ครบ"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// ตรวจสอบ email ซ้ำ
$sql = "SELECT user_id FROM user WHERE email = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $email);
$stmt->execute();

$result = $stmt->get_result();

if ($result->num_rows > 0) {

    echo json_encode([
        "status" => false,
        "message" => "อีเมลนี้ถูกใช้งานแล้ว"
    ], JSON_UNESCAPED_UNICODE);

    exit;
}

// เข้ารหัส password
$hashedPassword = password_hash(
    $password,
    PASSWORD_DEFAULT
);

// สมัครสมาชิก
$sql = "INSERT INTO user (name, email, password, role)
        VALUES (?, ?, ?, 'user')";

$stmt = $conn->prepare($sql);

$stmt->bind_param(
    "sss",
    $name,
    $email,
    $hashedPassword
);

if ($stmt->execute()) {

    echo json_encode([
        "status" => true,
        "message" => "สมัครสมาชิกสำเร็จ"
    ], JSON_UNESCAPED_UNICODE);

} else {

    echo json_encode([
        "status" => false,
        "message" => "สมัครสมาชิกไม่สำเร็จ"
    ], JSON_UNESCAPED_UNICODE);
}