-- สร้างฐานข้อมูลหากยังไม่มี
CREATE DATABASE IF NOT EXISTS `pet_home` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `pet_home`;

-- ตารางผู้ใช้งาน (user)
DROP TABLE IF EXISTS `adoption_requests`;
DROP TABLE IF EXISTS `pets`;
DROP TABLE IF EXISTS `user`;

CREATE TABLE `user` (
  `user_id` INT AUTO_INCREMENT PRIMARY KEY,
  `name` VARCHAR(255) NOT NULL,
  `email` VARCHAR(255) NOT NULL UNIQUE,
  `password` VARCHAR(255) NOT NULL,
  `role` VARCHAR(50) NOT NULL DEFAULT 'user',
  `phone` VARCHAR(50) DEFAULT '',
  `avatar` TEXT DEFAULT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางสัตว์เลี้ยง (pets)
CREATE TABLE `pets` (
  `pet_id` INT AUTO_INCREMENT PRIMARY KEY,
  `owner_id` INT DEFAULT NULL,
  `pet_name` VARCHAR(100) NOT NULL,
  `type` VARCHAR(50) NOT NULL, -- dog, cat, bird, other
  `breed` VARCHAR(100) NOT NULL,
  `age` INT NOT NULL, -- in months or years
  `gender` VARCHAR(50) NOT NULL, -- ผู้ / เมีย
  `province` VARCHAR(200) NOT NULL,
  `description` TEXT NOT NULL,
  `image` TEXT NOT NULL,
  `contact` TEXT NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'available', -- available, pending, adopted
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`owner_id`) REFERENCES `user`(`user_id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ตารางคำขอรับเลี้ยง (adoption_requests)
CREATE TABLE `adoption_requests` (
  `request_id` INT AUTO_INCREMENT PRIMARY KEY,
  `pet_id` INT NOT NULL,
  `user_id` INT NOT NULL,
  `adopter_name` VARCHAR(255) NOT NULL,
  `phone` VARCHAR(50) NOT NULL,
  `address` TEXT NOT NULL,
  `reason` TEXT NOT NULL,
  `status` VARCHAR(50) NOT NULL DEFAULT 'pending', -- pending, approved, rejected
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (`pet_id`) REFERENCES `pets`(`pet_id`) ON DELETE CASCADE,
  FOREIGN KEY (`user_id`) REFERENCES `user`(`user_id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- เพิ่มข้อมูลบัญชีผู้ใช้เริ่มต้น
-- รหัสผ่านคือ '123456' สำหรับทั้ง Admin และ User ทั่วไป (bcrypt hash)
INSERT INTO `user` (`user_id`, `name`, `email`, `password`, `role`, `phone`, `avatar`) VALUES
(1, 'ผู้ดูแลระบบ Admin', 'admin@pethome.com', '$2y$10$tZz27l2w1iQY6WjVbY3hEuT3LqGk4tN7.Y3pXvY6f9t3y9.zL/Q6S', 'admin', '081-999-8888', 'https://images.unsplash.com/photo-1534528741775-53994a69daeb?w=150'),
(2, 'สมชาย ใจดี', 'user@pethome.com', '$2y$10$tZz27l2w1iQY6WjVbY3hEuT3LqGk4tN7.Y3pXvY6f9t3y9.zL/Q6S', 'user', '089-123-4567', 'https://images.unsplash.com/photo-1535713875002-d1d0cf377fde?w=150'),
(3, 'แพรวา รักสัตว์', 'praewa@pethome.com', '$2y$10$tZz27l2w1iQY6WjVbY3hEuT3LqGk4tN7.Y3pXvY6f9t3y9.zL/Q6S', 'user', '086-777-6655', 'https://images.unsplash.com/photo-1494790108377-be9c29b29330?w=150');

-- เพิ่มข้อมูลสัตว์เลี้ยงตัวอย่างสำหรับหาบ้าน
INSERT INTO `pets` (`pet_id`, `owner_id`, `pet_name`, `type`, `breed`, `age`, `gender`, `province`, `description`, `image`, `contact`, `status`) VALUES
(1, 1, 'มอมแมม', 'dog', 'โกลเด้น รีทรีฟเวอร์ ผสม', 2, 'ผู้', 'กรุงเทพมหานคร', 'นิสัยร่าเริง ขี้เล่น เข้ากับเด็กและสัตว์ตัวอื่นได้ดี ฉีดวัคซีนครบแล้ว ต้องการบ้านที่มีพื้นที่วิ่งเล่น', 'https://images.unsplash.com/photo-1552053831-71594a27632d?w=600', 'โทร: 081-999-8888 (คุณแอดมิน) Line: @pethome', 'available'),
(2, 2, 'เจ้าส้ม', 'cat', 'สก็อตติช โฟลด์ ผสมไทย', 1, 'ผู้', 'นนทบุรี', 'ขี้อ้อนมาก ชอบนอนตัก ขนสีส้มนุ่ม ทานอาหารเม็ดได้ ขับถ่ายในกระบะทรายเป็น ฉีดวัคซีนเข็มแรกแล้ว', 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600', 'โทร: 089-123-4567 (คุณสมชาย)', 'available'),
(3, 1, 'ลัคกี้', 'dog', 'ไซบีเรียน ฮัสกี้', 3, 'เมีย', 'เชียงใหม่', 'น้องเรียบร้อย ตาสองสี มีเสน่ห์ ชอบเดินเล่น อากาศเย็นๆ สุขภาพแข็งแรง ทำหมันแล้ว', 'https://images.unsplash.com/photo-1537151625747-768eb6cf92b2?w=600', 'โทร: 081-999-8888 (บ้านพักพิงสัตว์เชียงใหม่)', 'available'),
(4, 3, 'มิลค์กี้', 'cat', 'เปอร์เซีย ผสม', 2, 'เมีย', 'ชลบุรี', 'ขนสีขาวปุย นิสัยนิ่งๆ เรียบร้อย ชอบความสงบ เหมาะกับเลี้ยงในคอนโดหรือบ้านระบบปิด', 'https://images.unsplash.com/photo-1573865526739-10659fec78a5?w=600', 'โทร: 086-777-6655 (คุณแพรวา)', 'available'),
(5, 1, 'พาร็อท', 'bird', 'คอกคาเทล', 1, 'ผู้', 'ปทุมธานี', 'น้องนกคอกคาเทลแก้มส้ม เชื่องมาก เกาะมือได้ ร้องเพลงเก่ง กินอาหารเม็ดและเมล็ดธัญพืช', 'https://images.unsplash.com/photo-1552728089-57bdde30beb3?w=600', 'โทร: 081-999-8888 (Pet Home Center)', 'available'),
(6, 2, 'บราวนี่', 'other', 'กระต่าย ฮอลแลนด์ลอป', 1, 'เมีย', 'สมุทรปราการ', 'กระต่ายหูตก น่ารัก นิสัยขี้สงสัย กินหญ้าทิโมธีและอาหารเม็ด สุขภาพดี ขนแน่น', 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?w=600', 'โทร: 089-123-4567', 'available'),
(7, 3, 'ถั่วดำ', 'cat', 'แมวไทย ดำปลอด', 2, 'ผู้', 'กรุงเทพมหานคร', 'แมวดำนำโชค ขนดำขลับ ตาสีเขียวมรกต นิสัยเป็นมิตร ขี้เล่น ได้บ้านใหม่อันอบอุ่นแล้ว', 'https://images.unsplash.com/photo-1548802673-380ab8ebc7b7?w=600', 'โทร: 086-777-6655', 'adopted');
