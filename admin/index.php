<?php
require_once "auth.php";
checkAdminAuth();

require_once __DIR__ . '/../server/db/config.php';
require_once __DIR__ . '/../server/models/pet_home.php';

$db = new Database();
$conn = $db->connect();
$petModel = new Pet_home($conn);

$admin = $_SESSION['admin_user'];
$stats = $petModel->getDetailedStats();
$notifications = $petModel->getNotifications();

// Handle Actions (POST / GET)
$message = '';
$msgType = 'success';

// Update Pet Status (Quick Action)
if (isset($_GET['update_pet_status']) && isset($_GET['status'])) {
    $pet_id = (int)$_GET['update_pet_status'];
    $new_status = $_GET['status'];
    $petModel->updateStatus($pet_id, $new_status);
    header("Location: index.php?tab=pets&msg=status_updated");
    exit;
}

// Delete Pet
if (isset($_GET['delete_pet'])) {
    $pet_id = (int)$_GET['delete_pet'];
    $petModel->delete($pet_id);
    header("Location: index.php?tab=pets&msg=pet_deleted");
    exit;
}

// Toggle User Role
if (isset($_GET['toggle_user_role'])) {
    $u_id = (int)$_GET['toggle_user_role'];
    $current_role = $_GET['current'] ?? 'user';
    if ($u_id !== 1) {
        $new_role = ($current_role === 'admin') ? 'user' : 'admin';
        $stmt = $conn->prepare("UPDATE `user` SET `role` = ? WHERE `user_id` = ?");
        $stmt->bind_param("si", $new_role, $u_id);
        $stmt->execute();
    }
    header("Location: index.php?tab=users&msg=role_updated");
    exit;
}

// Add User (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_user']) && $_POST['action_user'] === 'add') {
    $name = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '123456';
    $role = $_POST['role'] ?? 'user';
    $phone = trim($_POST['phone'] ?? '');

    if (!empty($name) && !empty($email)) {
        $hashed = password_hash($password, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("INSERT INTO `user` (`name`, `email`, `password`, `role`, `phone`) VALUES (?, ?, ?, ?, ?)");
        $stmt->bind_param("sssss", $name, $email, $hashed, $role, $phone);
        if ($stmt->execute()) {
            header("Location: index.php?tab=users&msg=user_added");
            exit;
        }
    }
}

// Delete User
if (isset($_GET['delete_user'])) {
    $u_id = (int)$_GET['delete_user'];
    if ($u_id !== 1) {
        $stmt = $conn->prepare("DELETE FROM `user` WHERE `user_id` = ?");
        $stmt->bind_param("i", $u_id);
        $stmt->execute();
    }
    header("Location: index.php?tab=users&msg=user_deleted");
    exit;
}

// Adoption Request Actions
if (isset($_GET['req_action']) && isset($_GET['req_id'])) {
    $req_id = (int)$_GET['req_id'];
    $action = $_GET['req_action'];
    $pet_id = (int)($_GET['pet_id'] ?? 0);

    if ($action === 'approve') {
        $conn->query("UPDATE `adoption_requests` SET `status` = 'approved' WHERE `request_id` = $req_id");
        if ($pet_id > 0) {
            $conn->query("UPDATE `pets` SET `status` = 'adopted' WHERE `pet_id` = $pet_id");
        }
    } elseif ($action === 'in_progress') {
        $conn->query("UPDATE `adoption_requests` SET `status` = 'in_progress' WHERE `request_id` = $req_id");
        if ($pet_id > 0) {
            $conn->query("UPDATE `pets` SET `status` = 'in_progress' WHERE `pet_id` = $pet_id");
        }
    } elseif ($action === 'reject') {
        $conn->query("UPDATE `adoption_requests` SET `status` = 'rejected' WHERE `request_id` = $req_id");
    } elseif ($action === 'delete') {
        $conn->query("DELETE FROM `adoption_requests` WHERE `request_id` = $req_id");
    }
    header("Location: index.php?tab=requests&msg=req_updated");
    exit;
}

// Add or Edit Pet (POST)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action_pet'])) {
    $action = $_POST['action_pet'];
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
    $extra_images = trim($_POST['extra_images'] ?? '[]');

    if (empty($image)) {
        if ($type === 'cat') $image = 'https://images.unsplash.com/photo-1514888286974-6c03e2ca1dba?w=600';
        elseif ($type === 'bird') $image = 'https://images.unsplash.com/photo-1552728089-57bdde30beb3?w=600';
        elseif ($type === 'other') $image = 'https://images.unsplash.com/photo-1585110396000-c9ffd4e4b308?w=600';
        else $image = 'https://images.unsplash.com/photo-1552053831-71594a27632d?w=600';
    }

    $petData = [
        'owner_id' => $admin['user_id'],
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

    if ($action === 'add') {
        $petModel->create($petData);
        header("Location: index.php?tab=pets&msg=pet_added");
        exit;
    } elseif ($action === 'edit') {
        $pet_id = (int)$_POST['pet_id'];
        $petModel->update($pet_id, $petData);
        header("Location: index.php?tab=pets&msg=pet_updated");
        exit;
    }
}

// Fetch Data
$pets = $conn->query("SELECT p.*, u.name as owner_name FROM `pets` p LEFT JOIN `user` u ON p.owner_id = u.user_id ORDER BY p.pet_id DESC");
$users = $conn->query("SELECT * FROM `user` ORDER BY user_id DESC");
$requests = $conn->query("SELECT r.*, p.pet_name, p.image as pet_image, p.breed FROM `adoption_requests` r LEFT JOIN `pets` p ON r.pet_id = p.pet_id ORDER BY r.request_id DESC");

$activeTab = $_GET['tab'] ?? 'dashboard';

// Status badge helper function
function renderStatusBadge($status) {
    switch ($status) {
        case 'available':
            return '<span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1"><i class="bi bi-circle-fill fs-6 text-success me-1"></i>🟢 เปิดรับเลี้ยง</span>';
        case 'pending_review':
            return '<span class="badge bg-warning bg-opacity-10 text-warning border border-warning border-opacity-25 px-2 py-1"><i class="bi bi-circle-fill fs-6 text-warning me-1"></i>🟡 รอตรวจสอบ</span>';
        case 'in_progress':
            return '<span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25 px-2 py-1"><i class="bi bi-circle-fill fs-6 text-primary me-1"></i>🔵 กำลังดำเนินการ</span>';
        case 'matched':
            return '<span class="badge bg-info bg-opacity-10 text-info border border-info border-opacity-25 px-2 py-1"><i class="bi bi-circle-fill fs-6 text-info me-1"></i>🟣 จับคู่แล้ว</span>';
        case 'adopted':
            return '<span class="badge bg-success text-white px-2 py-1"><i class="bi bi-stars me-1"></i>🎉 รับเลี้ยงสำเร็จ</span>';
        case 'closed':
            return '<span class="badge bg-secondary bg-opacity-25 text-dark px-2 py-1"><i class="bi bi-x-circle me-1"></i>⚪ ปิดการรับเลี้ยง</span>';
        default:
            return '<span class="badge bg-light text-dark">'.$status.'</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pet Home Admin | ระบบจัดการแอดมิน</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <!-- Chart.js -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <!-- Google Fonts: Prompt -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">

    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background-color: #f6f8fd;
            color: #333560;
        }
        .sidebar {
            width: 260px;
            background: #ffffff;
            min-height: 100vh;
            border-right: 1px solid #eef0f7;
            position: fixed;
            top: 0;
            left: 0;
            z-index: 100;
            box-shadow: 2px 0 15px rgba(0,0,0,0.02);
        }
        .main-content {
            margin-left: 260px;
            padding: 30px;
        }
        @media (max-width: 992px) {
            .sidebar {
                position: relative;
                width: 100%;
                min-height: auto;
            }
            .main-content {
                margin-left: 0;
                padding: 15px;
            }
        }
        .nav-link {
            font-weight: 500;
            color: #64748b;
            padding: 12px 18px;
            border-radius: 12px;
            margin-bottom: 6px;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        .nav-link i {
            font-size: 18px;
            margin-right: 12px;
        }
        .nav-link:hover {
            color: #5558a6;
            background: #f1f3fd;
        }
        .nav-link.active {
            color: #ffffff;
            background: #5558a6;
            box-shadow: 0 4px 12px rgba(85, 88, 166, 0.3);
        }
        .stat-card {
            background: #ffffff;
            border-radius: 18px;
            padding: 20px;
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
            transition: transform 0.2s;
        }
        .stat-card:hover {
            transform: translateY(-3px);
        }
        .stat-icon {
            width: 52px;
            height: 52px;
            border-radius: 14px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
        }
        .card {
            border-radius: 18px;
            border: none;
            box-shadow: 0 4px 20px rgba(0,0,0,0.03);
        }
        .btn-custom {
            border-radius: 12px;
            font-weight: 500;
            padding: 8px 16px;
        }
        .quick-action-btn {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 12px 16px;
            font-weight: 600;
            color: #333560;
            display: flex;
            align-items: center;
            gap: 10px;
            transition: all 0.2s;
            text-decoration: none;
        }
        .quick-action-btn:hover {
            background: #5558a6;
            color: #ffffff;
            border-color: #5558a6;
            box-shadow: 0 4px 12px rgba(85, 88, 166, 0.25);
        }
        .notification-dropdown {
            width: 340px;
            max-height: 420px;
            overflow-y: auto;
            border-radius: 16px;
            border: none;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar d-flex flex-column p-3">
    <!-- Brand -->
    <div class="d-flex align-items-center mb-4 px-2 pt-2">
        <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3 me-2" style="background-color: rgba(124,131,253,0.15)!important; color: #5558a6!important;">
            <i class="bi bi-shield-fill-check fs-4"></i>
        </div>
        <div>
            <h5 class="fw-bold mb-0 text-dark">Pet Home</h5>
            <span class="badge bg-secondary" style="font-size: 10px; background-color: #5558a6!important;">ADMIN PANEL</span>
        </div>
    </div>

    <!-- Navigation -->
    <ul class="nav nav-pills flex-column mb-auto">
        <li class="nav-item">
            <a href="index.php?tab=dashboard" class="nav-link <?php echo ($activeTab === 'dashboard') ? 'active' : ''; ?>">
                <i class="bi bi-grid-fill"></i> ภาพรวมระบบ
            </a>
        </li>
        <li>
            <a href="index.php?tab=pets" class="nav-link <?php echo ($activeTab === 'pets') ? 'active' : ''; ?>">
                <i class="bi bi-heart-fill"></i> จัดการสัตว์เลี้ยง
            </a>
        </li>
        <li>
            <a href="index.php?tab=users" class="nav-link <?php echo ($activeTab === 'users') ? 'active' : ''; ?>">
                <i class="bi bi-people-fill"></i> จัดการผู้ใช้งาน
            </a>
        </li>
        <li>
            <a href="index.php?tab=requests" class="nav-link <?php echo ($activeTab === 'requests') ? 'active' : ''; ?>">
                <i class="bi bi-inbox-fill"></i> คำขอรับเลี้ยง
                <?php if ($requests && $requests->num_rows > 0): ?>
                    <span class="badge bg-danger rounded-pill ms-auto" style="font-size: 10px;"><?php echo $requests->num_rows; ?></span>
                <?php endif; ?>
            </a>
        </li>
        <li>
            <a href="index.php?tab=reports" class="nav-link <?php echo ($activeTab === 'reports') ? 'active' : ''; ?>">
                <i class="bi bi-bar-chart-line-fill"></i> รายงานสถิติ
            </a>
        </li>
    </ul>

    <!-- Admin Profile / Logout -->
    <hr class="text-muted">
    <div class="d-flex align-items-center px-2">
        <div class="avatar text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 40px; height: 40px; background-color: #5558a6;">
            <i class="bi bi-person-fill fs-5"></i>
        </div>
        <div class="flex-grow-1 overflow-hidden">
            <h6 class="mb-0 text-truncate fw-semibold" style="font-size: 14px;"><?php echo htmlspecialchars($admin['name']); ?></h6>
            <small class="text-muted text-truncate d-block" style="font-size: 11px;"><?php echo htmlspecialchars($admin['email']); ?></small>
        </div>
        <a href="logout.php" class="text-danger ms-2 p-1" title="ออกจากระบบ">
            <i class="bi bi-box-arrow-right fs-5"></i>
        </a>
    </div>
</div>

<!-- Main Content Area -->
<div class="main-content">
    
    <!-- Top Header Bar with Notifications & Quick Links -->
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-3">
        <div>
            <h3 class="fw-bold mb-1">
                <?php
                if ($activeTab === 'pets') echo '🐾 จัดการข้อมูลสัตว์เลี้ยง';
                elseif ($activeTab === 'users') echo '👥 จัดการผู้ใช้งานระบบ';
                elseif ($activeTab === 'requests') echo '📩 รายการคำขอรับเลี้ยงสัตว์';
                elseif ($activeTab === 'reports') echo '📈 รายงานสถิติและการวิเคราะห์';
                else echo '📊 แดชบอร์ดภาพรวมระบบ';
                ?>
            </h3>
            <p class="text-muted small mb-0">ระบบบริหารจัดการ Pet Home Adoption Platform</p>
        </div>

        <div class="d-flex align-items-center gap-2">
            <!-- 🔔 Bell Notification Dropdown -->
            <div class="dropdown">
                <button class="btn btn-white position-relative border rounded-circle p-2 shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" style="width: 44px; height: 44px;">
                    <i class="bi bi-bell-fill text-secondary fs-5"></i>
                    <?php if ($notifications['unread_total'] > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size: 10px;">
                            <?php echo $notifications['unread_total']; ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end notification-dropdown p-2">
                    <div class="d-flex justify-content-between align-items-center p-2 border-bottom mb-2">
                        <h6 class="fw-bold mb-0"><i class="bi bi-bell me-1 text-primary"></i> แจ้งเตือนรายการใหม่</h6>
                        <span class="badge bg-primary rounded-pill"><?php echo $notifications['unread_total']; ?> รายการ</span>
                    </div>

                    <!-- Category Badges Summary -->
                    <div class="p-2 bg-light rounded-3 mb-2 small">
                        <div class="d-flex justify-content-between mb-1">
                            <span>📩 คำขอรับเลี้ยงใหม่:</span>
                            <strong class="text-warning"><?php echo $notifications['new_requests_count']; ?> คำขอ</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>👤 สมาชิกใหม่:</span>
                            <strong class="text-primary"><?php echo $notifications['new_users_count']; ?> คน</strong>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>🟡 สัตว์เลี้ยงรอตรวจสอบ:</span>
                            <strong class="text-danger"><?php echo $notifications['pending_pets_count']; ?> ตัว</strong>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span>🎉 รับเลี้ยงสำเร็จล่าสุด:</span>
                            <strong class="text-success"><?php echo $notifications['adopted_pets_count']; ?> ตัว</strong>
                        </div>
                    </div>

                    <!-- Notification Items -->
                    <?php if (!empty($notifications['new_requests'])): ?>
                        <div class="small fw-semibold text-muted px-2 my-1">คำขอรับเลี้ยงใหม่:</div>
                        <?php foreach ($notifications['new_requests'] as $nr): ?>
                            <a href="index.php?tab=requests" class="dropdown-item p-2 rounded-2 d-flex align-items-center gap-2 mb-1">
                                <img src="<?php echo htmlspecialchars($nr['pet_image']); ?>" width="35" height="35" class="rounded-circle" onerror="this.src='https://via.placeholder.com/35'">
                                <div class="overflow-hidden">
                                    <div class="fw-semibold text-truncate small"><?php echo htmlspecialchars($nr['adopter_name']); ?> ขอรับเลี้ยง <?php echo htmlspecialchars($nr['pet_name']); ?></div>
                                    <small class="text-muted d-block"><?php echo htmlspecialchars($nr['phone']); ?></small>
                                </div>
                            </a>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <div class="text-center pt-2 border-top">
                        <a href="index.php?tab=requests" class="small text-primary text-decoration-none fw-semibold">ดูคำขอรับเลี้ยงทั้งหมด →</a>
                    </div>
                </div>
            </div>

            <!-- API Link & Logout -->
            <a href="logout.php" class="btn btn-danger btn-custom shadow-sm">
                <i class="bi bi-box-arrow-right me-1"></i> ออกจากระบบ
            </a>
        </div>
    </div>

    <!-- ⚡ Quick Management & Action Bar (ปุ่มจัดการด่วน) -->
    <div class="row g-2 mb-4">
        <div class="col-6 col-md-3">
            <button class="quick-action-btn w-100" data-bs-toggle="modal" data-bs-target="#addPetModal">
                <div class="bg-primary bg-opacity-10 text-primary p-2 rounded-3" style="color: #5558a6!important;">
                    <i class="bi bi-plus-lg fs-5"></i>
                </div>
                <span>+ เพิ่มสัตว์เลี้ยง</span>
            </button>
        </div>
        <div class="col-6 col-md-3">
            <button class="quick-action-btn w-100" data-bs-toggle="modal" data-bs-target="#addUserModal">
                <div class="bg-success bg-opacity-10 text-success p-2 rounded-3">
                    <i class="bi bi-person-plus-fill fs-5"></i>
                </div>
                <span>+ เพิ่มผู้ใช้ใหม่</span>
            </button>
        </div>
        <div class="col-6 col-md-3">
            <a href="index.php?tab=requests" class="quick-action-btn w-100">
                <div class="bg-warning bg-opacity-10 text-warning p-2 rounded-3">
                    <i class="bi bi-clipboard-check-fill fs-5"></i>
                </div>
                <span>📋 ตรวจสอบคำขอ</span>
            </a>
        </div>
        <div class="col-6 col-md-3">
            <a href="index.php?tab=reports" class="quick-action-btn w-100">
                <div class="bg-info bg-opacity-10 text-info p-2 rounded-3">
                    <i class="bi bi-bar-chart-fill fs-5"></i>
                </div>
                <span>📊 ดูรายงานสรุป</span>
            </a>
        </div>
    </div>

    <!-- ======================================================== -->
    <!-- Tab 1: Dashboard Overview -->
    <!-- ======================================================== -->
    <?php if ($activeTab === 'dashboard'): ?>
        <!-- Stat Cards Grid -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">สัตว์เลี้ยงทั้งหมด</span>
                            <h2 class="fw-bold mb-0 mt-2 text-dark"><?php echo $stats['total_pets']; ?></h2>
                            <small class="text-primary"><i class="bi bi-arrow-up-short"></i> ในระบบทั้งหมด</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(124, 131, 253, 0.15); color: #5558a6;">
                            <i class="bi bi-heart-pulse-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">รอคนรับเลี้ยง (รอบ้าน)</span>
                            <h2 class="fw-bold mb-0 mt-2 text-warning"><?php echo $stats['available_pets']; ?></h2>
                            <small class="text-muted">🟢 เปิดรับเลี้ยงในระบบ</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(255, 159, 69, 0.15); color: #ff9f45;">
                            <i class="bi bi-house-door-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">รับเลี้ยงสำเร็จแล้ว</span>
                            <h2 class="fw-bold mb-0 mt-2 text-success"><?php echo $stats['adopted_pets']; ?></h2>
                            <small class="text-success"><i class="bi bi-check-circle-fill"></i> ได้บ้านใหม่อบอุ่น</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(34, 197, 94, 0.15); color: #16a34a;">
                            <i class="bi bi-stars"></i>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-sm-6 col-xl-3">
                <div class="stat-card">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <span class="text-muted small fw-semibold">อัตราการรับเลี้ยง (Adoption Rate)</span>
                            <h2 class="fw-bold mb-0 mt-2 text-primary"><?php echo $stats['adoption_rate']; ?>%</h2>
                            <small class="text-muted">ความสำเร็จการหาบ้าน</small>
                        </div>
                        <div class="stat-icon" style="background: rgba(99, 102, 241, 0.15); color: #4f46e5;">
                            <i class="bi bi-pie-chart-fill"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- 📈 Charts Section -->
        <div class="row g-4 mb-4">
            <!-- Monthly Adoption Trend Chart -->
            <div class="col-lg-8">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">📊 กราฟสถิติการรับเลี้ยงสัตว์เลี้ยงในแต่ละเดือน</h5>
                        <span class="badge bg-light text-secondary">ปี <?php echo date('Y'); ?></span>
                    </div>
                    <div style="height: 280px;">
                        <canvas id="monthlyAdoptionChart"></canvas>
                    </div>
                </div>
            </div>

            <!-- Pet Popularity Doughnut Chart -->
            <div class="col-lg-4">
                <div class="card p-4 h-100">
                    <h5 class="fw-bold mb-3">🐶 ความนิยมสัตว์เลี้ยงแต่ละประเภท</h5>
                    <div style="height: 240px; position: relative;">
                        <canvas id="popularityChart"></canvas>
                    </div>
                    <div class="mt-3 text-center small text-muted">
                        คำนวณจากสัดส่วนสัตว์เลี้ยงทั้งหมดในระบบ
                    </div>
                </div>
            </div>
        </div>

        <!-- Recent Adoption Requests & Recent Pets Grid -->
        <div class="row g-4">
            <!-- Recent Requests Widget -->
            <div class="col-lg-6">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">📩 คำขอรับเลี้ยงล่าสุด</h5>
                        <a href="index.php?tab=requests" class="text-decoration-none small text-primary fw-semibold">ดูทั้งหมด →</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>สัตว์เลี้ยง</th>
                                    <th>ผู้ขอรับเลี้ยง</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recentReqs = $conn->query("SELECT r.*, p.pet_name, p.image as pet_image FROM `adoption_requests` r LEFT JOIN `pets` p ON r.pet_id = p.pet_id ORDER BY r.request_id DESC LIMIT 4");
                                while ($rq = $recentReqs->fetch_assoc()):
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($rq['pet_image'] ?? ''); ?>" width="40" height="40" class="rounded-circle me-2" onerror="this.src='https://via.placeholder.com/40'">
                                            <div class="fw-semibold small"><?php echo htmlspecialchars($rq['pet_name'] ?? 'สัตว์เลี้ยง'); ?></div>
                                        </div>
                                    </td>
                                    <td>
                                        <div class="fw-bold small"><?php echo htmlspecialchars($rq['adopter_name']); ?></div>
                                        <small class="text-muted d-block"><?php echo htmlspecialchars($rq['phone']); ?></small>
                                    </td>
                                    <td>
                                        <?php if ($rq['status'] === 'approved'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success">อนุมัติแล้ว</span>
                                        <?php elseif ($rq['status'] === 'rejected'): ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger">ไม่อนุมัติ</span>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning">รอตรวจสอบ</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Recent Pets -->
            <div class="col-lg-6">
                <div class="card p-4 h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="fw-bold mb-0">🐾 สัตว์เลี้ยงล่าสุดที่ลงประกาศ</h5>
                        <a href="index.php?tab=pets" class="text-decoration-none small text-primary fw-semibold">ดูทั้งหมด →</a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>สัตว์เลี้ยง</th>
                                    <th>ประเภท</th>
                                    <th>สถานะ</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php
                                $recentPets = $conn->query("SELECT * FROM `pets` ORDER BY pet_id DESC LIMIT 4");
                                while ($p = $recentPets->fetch_assoc()):
                                ?>
                                <tr>
                                    <td>
                                        <div class="d-flex align-items-center">
                                            <img src="<?php echo htmlspecialchars($p['image']); ?>" width="40" height="40" class="rounded-3 me-2" onerror="this.src='https://via.placeholder.com/40'">
                                            <div>
                                                <div class="fw-bold small"><?php echo htmlspecialchars($p['pet_name']); ?></div>
                                                <small class="text-muted"><?php echo htmlspecialchars($p['breed']); ?></small>
                                            </div>
                                        </div>
                                    </td>
                                    <td><span class="badge bg-light text-dark"><?php echo $p['type']; ?></span></td>
                                    <td><?php echo renderStatusBadge($p['status']); ?></td>
                                </tr>
                                <?php endwhile; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <!-- ======================================================== -->
    <!-- Tab 2: Pets Management -->
    <!-- ======================================================== -->
    <?php if ($activeTab === 'pets'): ?>
        <div class="card p-4">
            <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                <h5 class="fw-bold mb-0">รายการสัตว์เลี้ยงทั้งหมด (<?php echo $pets->num_rows; ?> ตัว)</h5>
                <button class="btn btn-primary btn-custom" style="background-color: #5558a6; border-color: #5558a6;" data-bs-toggle="modal" data-bs-target="#addPetModal">
                    <i class="bi bi-plus-circle me-1"></i> เพิ่มสัตว์เลี้ยงใหม่
                </button>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>รูปภาพ</th>
                            <th>ชื่อสัตว์เลี้ยง</th>
                            <th>ประเภท / สายพันธุ์</th>
                            <th>เพศ/อายุ</th>
                            <th>จังหวัด</th>
                            <th>สถานะการรับเลี้ยง</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($pet = $pets->fetch_assoc()): ?>
                        <tr>
                            <td>
                                <img src="<?php echo htmlspecialchars($pet['image']); ?>" width="55" height="55" class="rounded-3" onerror="this.src='https://via.placeholder.com/55'">
                            </td>
                            <td>
                                <div class="fw-bold fs-6"><?php echo htmlspecialchars($pet['pet_name']); ?></div>
                                <small class="text-muted">ผู้ลง: <?php echo htmlspecialchars($pet['owner_name'] ?? 'Admin'); ?></small>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark text-capitalize"><?php echo htmlspecialchars($pet['type']); ?></span>
                                <div class="small text-muted"><?php echo htmlspecialchars($pet['breed']); ?></div>
                            </td>
                            <td>
                                <div><?php echo htmlspecialchars($pet['gender']); ?></div>
                                <small class="text-muted"><?php echo $pet['age']; ?> ปี</small>
                            </td>
                            <td>
                                <small><i class="bi bi-geo-alt-fill text-danger"></i> <?php echo htmlspecialchars($pet['province']); ?></small>
                            </td>
                            <td>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                        <?php echo renderStatusBadge($pet['status']); ?>
                                    </button>
                                    <ul class="dropdown-menu">
                                        <li><a class="dropdown-item" href="index.php?update_pet_status=<?php echo $pet['pet_id']; ?>&status=available">🟢 เปิดรับเลี้ยง</a></li>
                                        <li><a class="dropdown-item" href="index.php?update_pet_status=<?php echo $pet['pet_id']; ?>&status=pending_review">🟡 รอตรวจสอบ</a></li>
                                        <li><a class="dropdown-item" href="index.php?update_pet_status=<?php echo $pet['pet_id']; ?>&status=in_progress">🔵 กำลังดำเนินการ</a></li>
                                        <li><a class="dropdown-item" href="index.php?update_pet_status=<?php echo $pet['pet_id']; ?>&status=matched">🟣 จับคู่แล้ว</a></li>
                                        <li><a class="dropdown-item" href="index.php?update_pet_status=<?php echo $pet['pet_id']; ?>&status=adopted">🎉 รับเลี้ยงสำเร็จ</a></li>
                                        <li><a class="dropdown-item" href="index.php?update_pet_status=<?php echo $pet['pet_id']; ?>&status=closed">⚪ ปิดการรับเลี้ยง</a></li>
                                    </ul>
                                </div>
                            </td>
                            <td>
                                <button class="btn btn-sm btn-outline-primary rounded-3 me-1" onclick='openEditPetModal(<?php echo json_encode($pet, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP); ?>)'>
                                    <i class="bi bi-pencil"></i>
                                </button>
                                <a href="javascript:void(0)" onclick="confirmDeletePet(<?php echo $pet['pet_id']; ?>, '<?php echo addslashes($pet['pet_name']); ?>')" class="btn btn-sm btn-outline-danger rounded-3">
                                    <i class="bi bi-trash"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- ======================================================== -->
    <!-- Tab 3: Users Management -->
    <!-- ======================================================== -->
    <?php if ($activeTab === 'users'): ?>
        <div class="card p-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h5 class="fw-bold mb-0">รายชื่อผู้ใช้งานทั้งหมด (<?php echo $users->num_rows; ?> คน)</h5>
                <button class="btn btn-success btn-custom" data-bs-toggle="modal" data-bs-target="#addUserModal">
                    <i class="bi bi-person-plus-fill me-1"></i> เพิ่มผู้ใช้ใหม่
                </button>
            </div>
            <div class="table-responsive">
                <table class="table table-hover align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>User ID</th>
                            <th>ชื่อ - นามสกุล</th>
                            <th>อีเมล</th>
                            <th>เบอร์โทรศัพท์</th>
                            <th>สิทธิ์ (Role)</th>
                            <th>จัดการ</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php while ($u = $users->fetch_assoc()): ?>
                        <tr>
                            <td><span class="badge bg-light text-dark">#<?php echo $u['user_id']; ?></span></td>
                            <td class="fw-bold"><?php echo htmlspecialchars($u['name']); ?></td>
                            <td><?php echo htmlspecialchars($u['email']); ?></td>
                            <td><?php echo !empty($u['phone']) ? htmlspecialchars($u['phone']) : '-'; ?></td>
                            <td>
                                <?php if ($u['role'] === 'admin'): ?>
                                    <span class="badge bg-primary text-white" style="background-color: #5558a6!important;">ADMIN</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary">USER</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($u['user_id'] != 1): ?>
                                    <a href="index.php?toggle_user_role=<?php echo $u['user_id']; ?>&current=<?php echo $u['role']; ?>" class="btn btn-sm btn-outline-secondary rounded-3 me-1" title="เปลี่ยนสิทธิ์">
                                        <i class="bi bi-arrow-repeat me-1"></i> สลับสิทธิ์
                                    </a>
                                    <a href="javascript:void(0)" onclick="confirmDeleteUser(<?php echo $u['user_id']; ?>, '<?php echo addslashes($u['name']); ?>')" class="btn btn-sm btn-outline-danger rounded-3">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-warning bg-opacity-25 text-dark">Super Admin หลัก</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- ======================================================== -->
    <!-- Tab 4: Adoption Requests -->
    <!-- ======================================================== -->
    <?php if ($activeTab === 'requests'): ?>
        <div class="card p-4">
            <h5 class="fw-bold mb-3">คำขอรับเลี้ยงสัตว์เลี้ยงทั้งหมด (<?php echo $requests->num_rows; ?> รายการ)</h5>
            <?php if ($requests->num_rows === 0): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                    ยังไม่มีคำขอรับเลี้ยงเข้ามาในระบบ
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>สัตว์ที่ขอรับเลี้ยง</th>
                                <th>ผู้ขอรับเลี้ยง</th>
                                <th>เบอร์โทรศัพท์ / ที่อยู่</th>
                                <th>เหตุผลความพร้อม</th>
                                <th>สถานะ</th>
                                <th>จัดการคำขอ</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($r = $requests->fetch_assoc()): ?>
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="<?php echo htmlspecialchars($r['pet_image'] ?? ''); ?>" width="45" height="45" class="me-2 rounded-3" onerror="this.src='https://via.placeholder.com/45'">
                                        <div>
                                            <div class="fw-bold"><?php echo htmlspecialchars($r['pet_name'] ?? 'สัตว์เลี้ยง #'.$r['pet_id']); ?></div>
                                            <small class="text-muted"><?php echo htmlspecialchars($r['breed'] ?? ''); ?></small>
                                        </div>
                                    </div>
                                </td>
                                <td class="fw-bold"><?php echo htmlspecialchars($r['adopter_name']); ?></td>
                                <td>
                                    <div><i class="bi bi-telephone-fill text-success small"></i> <?php echo htmlspecialchars($r['phone']); ?></div>
                                    <small class="text-muted"><?php echo htmlspecialchars($r['address']); ?></small>
                                </td>
                                <td><small><?php echo htmlspecialchars($r['reason']); ?></small></td>
                                <td>
                                    <?php if ($r['status'] === 'approved'): ?>
                                        <span class="badge bg-success text-white">อนุมัติแล้ว</span>
                                    <?php elseif ($r['status'] === 'in_progress'): ?>
                                        <span class="badge bg-primary bg-opacity-10 text-primary">กำลังดำเนินการ</span>
                                    <?php elseif ($r['status'] === 'rejected'): ?>
                                        <span class="badge bg-danger bg-opacity-10 text-danger">ปฏิเสธ</span>
                                    <?php else: ?>
                                        <span class="badge bg-warning bg-opacity-10 text-warning">รอตรวจสอบ</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($r['status'] !== 'approved'): ?>
                                        <a href="index.php?req_action=approve&req_id=<?php echo $r['request_id']; ?>&pet_id=<?php echo $r['pet_id']; ?>" class="btn btn-sm btn-success rounded-3 me-1" title="อนุมัติการรับเลี้ยง">
                                            <i class="bi bi-check-lg"></i> อนุมัติ
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($r['status'] !== 'in_progress'): ?>
                                        <a href="index.php?req_action=in_progress&req_id=<?php echo $r['request_id']; ?>&pet_id=<?php echo $r['pet_id']; ?>" class="btn btn-sm btn-primary rounded-3 me-1" title="กำลังดำเนินการ">
                                            <i class="bi bi-gear-fill"></i>
                                        </a>
                                    <?php endif; ?>
                                    <?php if ($r['status'] !== 'rejected'): ?>
                                        <a href="index.php?req_action=reject&req_id=<?php echo $r['request_id']; ?>" class="btn btn-sm btn-warning rounded-3 me-1" title="ปฏิเสธคำขอ">
                                            <i class="bi bi-x-lg"></i>
                                        </a>
                                    <?php endif; ?>
                                    <a href="index.php?req_action=delete&req_id=<?php echo $r['request_id']; ?>" class="btn btn-sm btn-outline-danger rounded-3" onclick="return confirm('ยืนยันลบคำขอนี้?')">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <!-- ======================================================== -->
    <!-- Tab 5: Full Reports & Analytics -->
    <!-- ======================================================== -->
    <?php if ($activeTab === 'reports'): ?>
        <div class="card p-4 mb-4">
            <h5 class="fw-bold mb-3">📈 รายงานวิเคราะห์ประสิทธิภาพการรับเลี้ยงสัตว์</h5>
            
            <div class="row g-3 mb-4">
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <small class="text-muted d-block mb-1">สัตว์เลี้ยงทั้งหมด</small>
                        <h3 class="fw-bold text-dark mb-0"><?php echo $stats['total_pets']; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <small class="text-muted d-block mb-1">รับเลี้ยงสำเร็จแล้ว</small>
                        <h3 class="fw-bold text-success mb-0"><?php echo $stats['adopted_pets']; ?></h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <small class="text-muted d-block mb-1">อัตราความสำเร็จ</small>
                        <h3 class="fw-bold text-primary mb-0"><?php echo $stats['adoption_rate']; ?>%</h3>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="p-3 bg-light rounded-3 text-center">
                        <small class="text-muted d-block mb-1">คำขอรับเลี้ยงทั้งหมด</small>
                        <h3 class="fw-bold text-warning mb-0"><?php echo $stats['total_requests']; ?></h3>
                    </div>
                </div>
            </div>

            <div class="row g-4">
                <div class="col-lg-6">
                    <h6 class="fw-bold mb-3">สถิติการรับเลี้ยงตามประเภทสัตว์</h6>
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-middle">
                            <span>🐶 สุนัข (Dog)</span>
                            <strong class="text-primary"><?php echo $stats['popularity']['dog']; ?> ตัว</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-middle">
                            <span>🐱 แมว (Cat)</span>
                            <strong class="text-warning"><?php echo $stats['popularity']['cat']; ?> ตัว</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-middle">
                            <span>🦜 นก (Bird)</span>
                            <strong class="text-success"><?php echo $stats['popularity']['bird']; ?> ตัว</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-middle">
                            <span>🐰 อื่นๆ (Other)</span>
                            <strong class="text-secondary"><?php echo $stats['popularity']['other']; ?> ตัว</strong>
                        </li>
                    </ul>
                </div>
                <div class="col-lg-6">
                    <h6 class="fw-bold mb-3">สรุปสถานะสัตว์เลี้ยงในระบบ</h6>
                    <div class="d-flex flex-column gap-2">
                        <div class="d-flex justify-content-between p-2 border rounded-3">
                            <span>🟢 เปิดรับเลี้ยง (Available):</span>
                            <strong><?php echo $stats['available_pets']; ?> ตัว</strong>
                        </div>
                        <div class="d-flex justify-content-between p-2 border rounded-3">
                            <span>🟡 รอตรวจสอบ (Pending Review):</span>
                            <strong><?php echo $stats['pending_pets']; ?> ตัว</strong>
                        </div>
                        <div class="d-flex justify-content-between p-2 border rounded-3">
                            <span>🔵 กำลังดำเนินการ (In Progress):</span>
                            <strong><?php echo $stats['in_progress_pets']; ?> ตัว</strong>
                        </div>
                        <div class="d-flex justify-content-between p-2 border rounded-3">
                            <span>🎉 รับเลี้ยงสำเร็จ (Adopted):</span>
                            <strong><?php echo $stats['adopted_pets']; ?> ตัว</strong>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>

</div>

<!-- Modal: เพิ่มผู้ใช้ใหม่ (Quick Action) -->
<div class="modal fade" id="addUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">👤 เพิ่มผู้ใช้งานใหม่</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?tab=users">
                <input type="hidden" name="action_user" value="add">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">ชื่อ - นามสกุล *</label>
                        <input type="text" name="name" class="form-control" required placeholder="สมชาย ใจดี">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">อีเมล *</label>
                        <input type="email" name="email" class="form-control" required placeholder="user@example.com">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">รหัสผ่าน *</label>
                        <input type="password" name="password" class="form-control" value="123456" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">เบอร์โทรศัพท์</label>
                        <input type="text" name="phone" class="form-control" placeholder="089-123-4567">
                    </div>
                    <div class="mb-3">
                        <label class="form-label small fw-semibold">สิทธิ์ (Role)</label>
                        <select name="role" class="form-select">
                            <option value="user">USER (ผู้ใช้งานทั่วไป)</option>
                            <option value="admin">ADMIN (ผู้ดูแลระบบ)</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-success">บันทึกข้อมูลผู้ใช้</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: เพิ่มสัตว์เลี้ยงใหม่ -->
<div class="modal fade" id="addPetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">➕ เพิ่มสัตว์เลี้ยงใหม่เข้าระบบ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?tab=pets">
                <input type="hidden" name="action_pet" value="add">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">ชื่อสัตว์เลี้ยง *</label>
                            <input type="text" name="pet_name" class="form-control" required placeholder="เช่น เจ้าถั่ว, มอมแมม">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">ประเภทสัตว์ *</label>
                            <select name="type" class="form-select" required>
                                <option value="dog">🐶 สุนัข (Dog)</option>
                                <option value="cat">🐱 แมว (Cat)</option>
                                <option value="bird">🦜 นก (Bird)</option>
                                <option value="other">🐰 อื่นๆ (Other)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">สายพันธุ์ *</label>
                            <input type="text" name="breed" class="form-control" required placeholder="เช่น โกลเด้น, สก็อตติช, ไทย">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">อายุ (ปี) *</label>
                            <input type="number" name="age" class="form-control" value="1" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">เพศ *</label>
                            <select name="gender" class="form-select" required>
                                <option value="ผู้">♂ เพศผู้</option>
                                <option value="เมีย">♀ เพศเมีย</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">จังหวัดที่อยู่ *</label>
                            <input type="text" name="province" class="form-control" value="กรุงเทพมหานคร" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">สถานะ *</label>
                            <select name="status" class="form-select" required>
                                <option value="available">🟢 เปิดรับเลี้ยง (Available)</option>
                                <option value="pending_review">🟡 รอตรวจสอบ (Pending Review)</option>
                                <option value="in_progress">🔵 กำลังดำเนินการ (In Progress)</option>
                                <option value="matched">🟣 จับคู่แล้ว (Matched)</option>
                                <option value="adopted">🎉 รับเลี้ยงสำเร็จ (Adopted)</option>
                                <option value="closed">⚪ ปิดการรับเลี้ยง (Closed)</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">ลิงก์รูปภาพหลัก (Image URL)</label>
                            <input type="url" name="image" class="form-control" placeholder="https://images.unsplash.com/...">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">รายละเอียด / เรื่องราวสัตว์เลี้ยง</label>
                            <textarea name="description" class="form-control" rows="2" placeholder="นิสัย ร่าเริง ขี้อ้อน..."></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">🏥 ข้อมูลสุขภาพ</label>
                            <input type="text" name="health_info" class="form-control" placeholder="ทำวัคซีนแล้ว, ทำหมันแล้ว">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">🐾 นิสัยใจคอ</label>
                            <input type="text" name="personality" class="form-control" placeholder="ขี้เล่น, ร่าเริง, ติดคน">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">🍖 การดูแล</label>
                            <input type="text" name="care_info" class="form-control" placeholder="ให้อาหารวันละ 2 มื้อ">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">ข้อมูลติดต่อ *</label>
                            <input type="text" name="contact" class="form-control" value="โทร: 081-999-8888 (แอดมิน Pet Home)" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #5558a6;">บันทึกข้อมูลสัตว์เลี้ยง</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal: แก้ไขสัตว์เลี้ยง -->
<div class="modal fade" id="editPetModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius: 20px;">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">✏️ แก้ไขข้อมูลสัตว์เลี้ยง</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST" action="index.php?tab=pets">
                <input type="hidden" name="action_pet" value="edit">
                <input type="hidden" name="pet_id" id="edit_pet_id">
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">ชื่อสัตว์เลี้ยง *</label>
                            <input type="text" name="pet_name" id="edit_pet_name" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">ประเภทสัตว์ *</label>
                            <select name="type" id="edit_type" class="form-select" required>
                                <option value="dog">🐶 สุนัข (Dog)</option>
                                <option value="cat">🐱 แมว (Cat)</option>
                                <option value="bird">🦜 นก (Bird)</option>
                                <option value="other">🐰 อื่นๆ (Other)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">สายพันธุ์ *</label>
                            <input type="text" name="breed" id="edit_breed" class="form-control" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">อายุ (ปี) *</label>
                            <input type="number" name="age" id="edit_age" class="form-control" min="0" required>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small fw-semibold">เพศ *</label>
                            <select name="gender" id="edit_gender" class="form-select" required>
                                <option value="ผู้">♂ เพศผู้</option>
                                <option value="เมีย">♀ เพศเมีย</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">จังหวัดที่อยู่ *</label>
                            <input type="text" name="province" id="edit_province" class="form-control" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">สถานะ *</label>
                            <select name="status" id="edit_status" class="form-select" required>
                                <option value="available">🟢 เปิดรับเลี้ยง (Available)</option>
                                <option value="pending_review">🟡 รอตรวจสอบ (Pending Review)</option>
                                <option value="in_progress">🔵 กำลังดำเนินการ (In Progress)</option>
                                <option value="matched">🟣 จับคู่แล้ว (Matched)</option>
                                <option value="adopted">🎉 รับเลี้ยงสำเร็จ (Adopted)</option>
                                <option value="closed">⚪ ปิดการรับเลี้ยง (Closed)</option>
                            </select>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">ลิงก์รูปภาพหลัก (Image URL)</label>
                            <input type="url" name="image" id="edit_image" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">รายละเอียด / เรื่องราวสัตว์เลี้ยง</label>
                            <textarea name="description" id="edit_description" class="form-control" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">🏥 ข้อมูลสุขภาพ</label>
                            <input type="text" name="health_info" id="edit_health_info" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">🐾 นิสัยใจคอ</label>
                            <input type="text" name="personality" id="edit_personality" class="form-control">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">🍖 การดูแล</label>
                            <input type="text" name="care_info" id="edit_care_info" class="form-control">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">ข้อมูลติดต่อ *</label>
                            <input type="text" name="contact" id="edit_contact" class="form-control" required>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">ยกเลิก</button>
                    <button type="submit" class="btn btn-primary" style="background-color: #5558a6;">บันทึกการแก้ไข</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Bootstrap 5 JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<script>
// Render Chart.js Graphs
<?php if ($activeTab === 'dashboard'): ?>
document.addEventListener("DOMContentLoaded", function() {
    // 1. Monthly Adoption Trend Chart
    const ctx1 = document.getElementById('monthlyAdoptionChart').getContext('2d');
    new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: <?php echo json_encode($stats['months'], JSON_UNESCAPED_UNICODE); ?>,
            datasets: [
                {
                    label: 'การรับเลี้ยงสำเร็จ (ตัว)',
                    data: <?php echo json_encode($stats['monthly_adoptions']); ?>,
                    backgroundColor: 'rgba(85, 88, 166, 0.85)',
                    borderRadius: 8,
                },
                {
                    label: 'คำขอรับเลี้ยงเข้ามา (คำขอ)',
                    data: <?php echo json_encode($stats['monthly_requests']); ?>,
                    backgroundColor: 'rgba(255, 159, 69, 0.55)',
                    borderRadius: 8,
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'top' }
            },
            scales: {
                y: { beginAtZero: true, grid: { borderDash: [4, 4] } },
                x: { grid: { display: false } }
            }
        }
    });

    // 2. Pet Popularity Doughnut Chart
    const ctx2 = document.getElementById('popularityChart').getContext('2d');
    new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: ['🐶 สุนัข', '🐱 แมว', '🦜 นก', '🐰 อื่นๆ'],
            datasets: [{
                data: [
                    <?php echo $stats['popularity']['dog']; ?>,
                    <?php echo $stats['popularity']['cat']; ?>,
                    <?php echo $stats['popularity']['bird']; ?>,
                    <?php echo $stats['popularity']['other']; ?>
                ],
                backgroundColor: ['#5558a6', '#ff9f45', '#16a34a', '#64748b'],
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom' }
            }
        }
    });
});
<?php endif; ?>

function openEditPetModal(pet) {
    document.getElementById('edit_pet_id').value = pet.pet_id;
    document.getElementById('edit_pet_name').value = pet.pet_name;
    document.getElementById('edit_type').value = pet.type;
    document.getElementById('edit_breed').value = pet.breed;
    document.getElementById('edit_age').value = pet.age;
    document.getElementById('edit_gender').value = pet.gender;
    document.getElementById('edit_province').value = pet.province;
    document.getElementById('edit_status').value = pet.status;
    document.getElementById('edit_image').value = pet.image;
    document.getElementById('edit_description').value = pet.description;
    document.getElementById('edit_contact').value = pet.contact;
    document.getElementById('edit_health_info').value = pet.health_info || '';
    document.getElementById('edit_personality').value = pet.personality || '';
    document.getElementById('edit_care_info').value = pet.care_info || '';

    new bootstrap.Modal(document.getElementById('editPetModal')).show();
}

function confirmDeletePet(petId, petName) {
    Swal.fire({
        title: 'ยืนยันการลบ?',
        text: `คุณต้องการลบข้อมูล "${petName}" ใช่หรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6e7881',
        confirmButtonText: 'ใช่, ลบเลย!',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `index.php?delete_pet=${petId}`;
        }
    });
}

function confirmDeleteUser(userId, userName) {
    Swal.fire({
        title: 'ยืนยันการลบผู้ใช้?',
        text: `คุณต้องการลบ "${userName}" ออกจากระบบหรือไม่?`,
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#6e7881',
        confirmButtonText: 'ใช่, ลบผู้ใช้',
        cancelButtonText: 'ยกเลิก'
    }).then((result) => {
        if (result.isConfirmed) {
            window.location.href = `index.php?delete_user=${userId}`;
        }
    });
}

// Show SweetAlert for URL message params
const urlParams = new URLSearchParams(window.location.search);
const msg = urlParams.get('msg');
if (msg === 'status_updated') {
    Swal.fire({ icon: 'success', title: 'อัปเดตสถานะสำเร็จ!', timer: 1500, showConfirmButton: false });
} else if (msg === 'pet_added') {
    Swal.fire({ icon: 'success', title: 'เพิ่มสัตว์เลี้ยงใหม่สำเร็จ!', timer: 1500, showConfirmButton: false });
} else if (msg === 'pet_updated') {
    Swal.fire({ icon: 'success', title: 'แก้ไขข้อมูลสำเร็จ!', timer: 1500, showConfirmButton: false });
} else if (msg === 'pet_deleted') {
    Swal.fire({ icon: 'success', title: 'ลบข้อมูลเรียบร้อยแล้ว!', timer: 1500, showConfirmButton: false });
} else if (msg === 'role_updated') {
    Swal.fire({ icon: 'success', title: 'เปลี่ยนสิทธิ์สำเร็จ!', timer: 1500, showConfirmButton: false });
} else if (msg === 'user_added') {
    Swal.fire({ icon: 'success', title: 'เพิ่มผู้ใช้ใหม่สำเร็จ!', timer: 1500, showConfirmButton: false });
} else if (msg === 'user_deleted') {
    Swal.fire({ icon: 'success', title: 'ลบผู้ใช้งานสำเร็จ!', timer: 1500, showConfirmButton: false });
} else if (msg === 'req_updated') {
    Swal.fire({ icon: 'success', title: 'ดำเนินการเรียบร้อย!', timer: 1500, showConfirmButton: false });
}
</script>
</body>
</html>
