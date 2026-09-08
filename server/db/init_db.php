<?php
header("Content-Type: application/json; charset=UTF-8");
require_once __DIR__ . '/config.php';

$db = new Database();
$conn = $db->connect();

// Create user table
$conn->query("
CREATE TABLE IF NOT EXISTS `user` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'user',
  `phone` VARCHAR(50) DEFAULT '',
  `avatar` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Create pets table
$conn->query("
CREATE TABLE IF NOT EXISTS `pets` (
  `pet_id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT DEFAULT NULL,
  `pet_name` VARCHAR(100) NOT NULL,
  `type` VARCHAR(50) NOT NULL,
  `breed` VARCHAR(100) NOT NULL,
  `age` INT NOT NULL,
  `gender` VARCHAR(50) NOT NULL,
  `province` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `image` TEXT NOT NULL,
  `contact` TEXT NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'available',
  `health_info` TEXT DEFAULT NULL,
  `personality` TEXT DEFAULT NULL,
  `care_info` TEXT DEFAULT NULL,
  `extra_images` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `user`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Add missing columns to pets if table already exists
$cols = [
  'health_info' => 'TEXT DEFAULT NULL',
  'personality' => 'TEXT DEFAULT NULL',
  'care_info' => 'TEXT DEFAULT NULL',
  'extra_images' => 'TEXT DEFAULT NULL'
];
foreach ($cols as $col => $def) {
    $check = $conn->query("SHOW COLUMNS FROM `pets` LIKE '$col'");
    if ($check->num_rows == 0) {
        $conn->query("ALTER TABLE `pets` ADD COLUMN `$col` $def");
    }
}

// Create adoption_requests table
$conn->query("
CREATE TABLE IF NOT EXISTS `adoption_requests` (
  `request_id` INT AUTO_INCREMENT PRIMARY KEY,
  `pet_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `adopter_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `address` TEXT NOT NULL,
  `reason` TEXT NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`pet_id`) REFERENCES `pets`(`pet_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Create favorites table
$conn->query("
CREATE TABLE IF NOT EXISTS `favorites` (
  `fav_id` INT AUTO_INCREMENT PRIMARY KEY,
  `user_id` INT NOT NULL,
  `pet_id` INT NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `user_pet_unique` (`user_id`, `pet_id`),
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE,
  FOREIGN KEY (`pet_id`) REFERENCES `pets`(`pet_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

// Hash for default password '123456'
$defaultPassword = password_hash('123456', PASSWORD_DEFAULT);

// Seed users
$users = [
    [1, 'ผู้ดูแลระบบ Admin', 'admin@pethome.com', $defaultPassword, 'admin', '081-999-8888', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150'],
    [2, 'สมชาย ใจดี', 'user@pethome.com', $defaultPassword, 'user', '089-123-4567', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150'],
    [3, 'แพรวา รักสัตว์', 'praewa@pethome.com', $defaultPassword, 'user', '086-777-6655', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150']
];

foreach ($users as $u) {
    $stmt = $conn->prepare("INSERT INTO `user` (`user_id`, `name`, `email`, `password`, `role`, `phone`, `avatar`) 
                            VALUES (?, ?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE `password` = VALUES(`password`), `name` = VALUES(`name`), `role` = VALUES(`role`), `phone` = VALUES(`phone`)");
    $stmt->bind_param("issssss", $u[0], $u[1], $u[2], $u[3], $u[4], $u[5], $u[6]);
    $stmt->execute();
}

// Seed pets with extended information
$pets = [
    [
        1, 1, 'มอมแมม', 'dog', 'โกลเด้น รีทรีฟเวอร์ ผสม', 2, 'ผู้', 'กรุงเทพมหานคร', 
        'นิสัยร่าเริง ขี้เล่น เข้ากับเด็กและสัตว์ตัวอื่นได้ดี ฉีดวัคซีนครบแล้ว ต้องการบ้านที่มีพื้นที่วิ่งเล่น', 
        'https://images.unsplash.com/photo-1552053831-71594a27632d?w=600', 
        'โทร: 081-999-8888 (คุณแอดมิน) Line: @pethome', 'available',
        'ทำวัคซีนครบแล้ว, ทำหมันแล้ว, สุขภาพแข็งแรงดีมาก',
        'ขี้เล่น, ขี้อ้อน, เข้ากับคนง่าย, ชอบวิ่งเล่น',
        'ต้องการพื้นที่วิ่งเล่น, ให้อาหารวันละ 2 มื้อ, พาแปรงขนสัปดาห์ละ 2 ครั้ง',
        json_encode([
            'https://images.unsplash.com/photo-1537151625747-768eb6cf92b2?w=600',
            'https://images.unsplash.com/photo-1583511655857-d19b40a7a54e?w=600'
        ], JSON_UNESCAPED_SLASHES)
    ],
    [
        2, 2, 'เจ้าส้ม', 'cat', 'สก็อตติช โฟลด์ ผสมไทย', 1, 'ผู้', 'นนทบุรี', 
        'ขี้อ้อนมาก ชอบนอนตัก ขนสีส้มนุ่ม ทานอาหารเม็ดได้ ขับถ่ายในกระบะทรายเป็น ฉีดวัคซีนเข็มแรกแล้ว', 
        'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600', 
        'โทร: 089-123-4567 (คุณสมชาย)', 'pending_review',
        'ฉีดวัคซีนพิษสุนัขบ้าแล้ว, หยดยากันเห็บเห็บแล้ว',
        'ชอบนอนตัก, ขี้อ้อน, ไม่ร้องเสียงดัง, ถ่ายในกระบะทราย',
        'เลี้ยงระบบปิดเท่านั้น, ทานอาหารสูตรแมวโต, เช็ดตาเป็นประจำ',
        json_encode([
            'https://images.unsplash.com/photo-1573865526739-10659fec78a5?w=600',
            'https://images.unsplash.com/photo-1548802673-380ab8ebc7b7?w=600'
        ], JSON_UNESCAPED_SLASHES)
    ],
    [
        3, 1, 'ลัคกี้', 'dog', 'ไซบีเรียน ฮัสกี้', 3, 'เมีย', 'เชียงใหม่', 
        'น้องเรียบร้อย ตาสองสี มีเสน่ห์ ชอบเดินเล่น อากาศเย็นๆ สุขภาพแข็งแรง ทำหมันแล้ว', 
        'https://images.unsplash.com/photo-1537151625747-768eb6cf92b2?w=600', 
        'โทร: 081-999-8888 (บ้านพักพิงสัตว์เชียงใหม่)', 'in_progress',
        'ทำหมันแล้ว, ฉีดวัคซีนรวมประจำปีแล้ว, ตรวจเลือดปกติ',
        'เรียบร้อย, ชอบเดินเล่น, ฉลาด, รู้ภาษา',
        'ชอบอากาศเย็น, พาเดินเล่นวันละ 30 นาที, แปรงขนผลัดขน',
        json_encode([
            'https://images.unsplash.com/photo-1552053831-71594a27632d?w=600'
        ], JSON_UNESCAPED_SLASHES)
    ],
    [
        4, 3, 'มิลค์กี้', 'cat', 'เปอร์เซีย ผสม', 2, 'เมีย', 'ชลบุรี', 
        'ขนสีขาวปุย นิสัยนิ่งๆ เรียบร้อย ชอบความสงบ เหมาะกับเลี้ยงในคอนโดหรือบ้านระบบปิด', 
        'https://images.unsplash.com/photo-1573865526739-10659fec78a5?w=600', 
        'โทร: 086-777-6655 (คุณแพรวา)', 'matched',
        'ฉีดวัคซีนครบ, ถ่ายพยาธิแล้ว, ตรวจเอดส์แมวผลเป็นลบ',
        'เรียบร้อย, ชอบนอน, ไม่ส่งเสียงรบกวน',
        'ต้องหวีขนทุกวันป้องกันขนพันกัน, เลี้ยงในห้องแอร์',
        json_encode([
            'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600'
        ], JSON_UNESCAPED_SLASHES)
    ],
    [
        5, 1, 'พาร็อท', 'bird', 'คอกคาเทล', 1, 'ผู้', 'ปทุมธานี', 
        'น้องนกคอกคาเทลแก้มส้ม เชื่องมาก เกาะมือได้ ร้องเพลงเก่ง กินอาหารเม็ดและเมล็ดธัญพืช', 
        'https://images.unsplash.com/photo-1552728089-57bdde30beb3?w=600', 
        'โทร: 081-999-8888 (Pet Home Center)', 'available',
        'ตรวจสุขภาพนกแล้ว ปีกตัดแต่งเรียบร้อย',
        'เชื่อง, ร้องเพลงเก่ง, ชอบเกาะไหล่',
        'ให้อาหารธัญพืชผสมวิตามิน, ทำความสะอาดกรงทุกวัน',
        json_encode([], JSON_UNESCAPED_SLASHES)
    ],
    [
        6, 2, 'บราวนี่', 'other', 'กระต่าย ฮอลแลนด์ลอป', 1, 'เมีย', 'สมุทรปราการ', 
        'กระต่ายหูตก น่ารัก นิสัยขี้สงสัย กินหญ้าทิโมธีและอาหารเม็ด สุขภาพดี ขนแน่น', 
        'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?w=600', 
        'โทร: 089-123-4567', 'closed',
        'ตรวจฟันและสุขภาพทางเดินอาหารปกติ',
        'ขี้สงสัย, ชอบวิ่งในพื้นที่จำกัด, ไม่กัดสายไฟ',
        'กินหญ้าทิโมธีเป็นหลัก, น้ำสะอาดเติมตลอด',
        json_encode([], JSON_UNESCAPED_SLASHES)
    ],
    [
        7, 3, 'ถั่วดำ', 'cat', 'แมวไทย ดำปลอด', 2, 'ผู้', 'กรุงเทพมหานคร', 
        'แมวดำนำโชค ขนดำขลับ ตาสีเขียวมรกต นิสัยเป็นมิตร ขี้เล่น ได้บ้านใหม่อันอบอุ่นแล้ว', 
        'https://images.unsplash.com/photo-1548802673-380ab8ebc7b7?w=600', 
        'โทร: 086-777-6655', 'adopted',
        'ทำหมันแล้ว, ฉีดวัคซีนครบ',
        'ขี้เล่น, เป็นมิตร, ติดคน',
        'เลี้ยงระบบปิด, ให้อาหารเม็ดสูตรแมวโต',
        json_encode([], JSON_UNESCAPED_SLASHES)
    ]
];

foreach ($pets as $p) {
    $stmt = $conn->prepare("INSERT INTO `pets` (`pet_id`, `owner_id`, `pet_name`, `type`, `breed`, `age`, `gender`, `province`, `description`, `image`, `contact`, `status`, `health_info`, `personality`, `care_info`, `extra_images`) 
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?) 
                            ON DUPLICATE KEY UPDATE 
                            `pet_name`=VALUES(`pet_name`), `type`=VALUES(`type`), `breed`=VALUES(`breed`), `age`=VALUES(`age`), `gender`=VALUES(`gender`), `province`=VALUES(`province`), `description`=VALUES(`description`), `image`=VALUES(`image`), `contact`=VALUES(`contact`), `status`=VALUES(`status`),
                            `health_info`=VALUES(`health_info`), `personality`=VALUES(`personality`), `care_info`=VALUES(`care_info`), `extra_images`=VALUES(`extra_images`)");
    $stmt->bind_param("iisssissssssssss", $p[0], $p[1], $p[2], $p[3], $p[4], $p[5], $p[6], $p[7], $p[8], $p[9], $p[10], $p[11], $p[12], $p[13], $p[14], $p[15]);
    $stmt->execute();
}

// Seed sample adoption requests
$requests = [
    [1, 1, 2, 'สมชาย ใจดี', '089-123-4567', '123/45 ถนนสุขุมวิท กรุงเทพฯ', 'มีบ้านพร้อมสนามหญ้า เคยเลี้ยงโกลเด้นมาแล้ว 5 ปี มีเวลาดูแลน้องเต็มที่', 'approved'],
    [2, 2, 3, 'แพรวา รักสัตว์', '086-777-6655', '88/9 คอนโดวิวสวย นนทบุรี', 'เลี้ยงระบบปิดในคอนโดเลี้ยงสัตว์ได้ มีคอนโดแมวและของเล่นพร้อม', 'pending'],
    [3, 3, 2, 'สมชาย ใจดี', '089-123-4567', '123/45 ถนนสุขุมวิท กรุงเทพฯ', 'ชอบฮัสกี้มาก ที่บ้านมีอากาศเย็น มีรั้วรอบขอบชิด', 'in_progress'],
    [4, 4, 2, 'สมชาย ใจดี', '089-123-4567', '123/45 ถนนสุขุมวิท กรุงเทพฯ', 'อยากได้น้องแมวเปอร์เซียมาเป็นเพื่อนเจ้าส้ม', 'matched'],
    [5, 7, 3, 'แพรวา รักสัตว์', '086-777-6655', '88/9 คอนโดวิวสวย นนทบุรี', 'หลงรักแมวดำ ยินดีอัปเดตชีวิตน้องให้ฟังทุกเดือน', 'adopted']
];

foreach ($requests as $r) {
    $stmt = $conn->prepare("INSERT INTO `adoption_requests` (`request_id`, `pet_id`, `user_id`, `adopter_name`, `phone`, `address`, `reason`, `status`)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                            ON DUPLICATE KEY UPDATE `status`=VALUES(`status`), `reason`=VALUES(`reason`), `address`=VALUES(`address`)");
    $stmt->bind_param("iiisssss", $r[0], $r[1], $r[2], $r[3], $r[4], $r[5], $r[6], $r[7]);
    $stmt->execute();
}

// Seed sample favorites
$favorites = [
    [1, 2, 1], // User 2 favorites Pet 1
    [2, 2, 2], // User 2 favorites Pet 2
    [3, 3, 4]  // User 3 favorites Pet 4
];
foreach ($favorites as $f) {
    $stmt = $conn->prepare("INSERT IGNORE INTO `favorites` (`fav_id`, `user_id`, `pet_id`) VALUES (?, ?, ?)");
    $stmt->bind_param("iii", $f[0], $f[1], $f[2]);
    $stmt->execute();
}

echo json_encode([
    'status' => true,
    'message' => 'Database & sample data initialized successfully with full pet details, statuses, adoption requests, and favorites.',
    'users' => count($users),
    'pets' => count($pets),
    'requests' => count($requests),
    'favorites' => count($favorites)
], JSON_UNESCAPED_UNICODE);
