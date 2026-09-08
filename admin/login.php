<?php
session_start();

require_once __DIR__ . '/../server/db/config.php';

$error = '';
if (isset($_SESSION['admin_user']) && $_SESSION['admin_user']['role'] === 'admin') {
    header("Location: index.php");
    exit;
}

if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = 'กรุณากรอกอีเมลและรหัสผ่าน';
    } else {
        $db = new Database();
        $conn = $db->connect();

        $stmt = $conn->prepare("SELECT user_id, name, email, password, role, phone FROM `user` WHERE email = ?");
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows > 0) {
            $user = $res->fetch_assoc();
            if (password_verify($password, $user['password'])) {
                if ($user['role'] === 'admin') {
                    unset($user['password']);
                    $_SESSION['admin_user'] = $user;
                    header("Location: index.php");
                    exit;
                } else {
                    $error = 'บัญชีนี้ไม่มีสิทธิ์เข้าสู่ระบบในฐานะ Admin';
                }
            } else {
                $error = 'รหัสผ่านไม่ถูกต้อง';
            }
        } else {
            $error = 'ไม่พบบัญชีผู้ใช้นี้ในระบบ';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>เข้าสู่ระบบผู้ดูแลระบบ | Pet Home Admin</title>
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.2/font/bootstrap-icons.min.css">
    <!-- Google Fonts: Prompt -->
    <link href="https://fonts.googleapis.com/css2?family=Prompt:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body {
            font-family: 'Prompt', sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .login-card {
            background: #ffffff;
            border-radius: 24px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15);
            width: 100%;
            max-width: 440px;
            padding: 40px 35px;
        }
        .logo-box {
            width: 80px;
            height: 80px;
            background: rgba(124, 131, 253, 0.15);
            color: #5558a6;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 40px;
            margin: 0 auto 15px auto;
        }
        .btn-primary {
            background-color: #5558a6;
            border-color: #5558a6;
            padding: 12px;
            border-radius: 14px;
            font-weight: 600;
        }
        .btn-primary:hover {
            background-color: #43468b;
            border-color: #43468b;
        }
        .form-control {
            border-radius: 12px;
            padding: 12px 16px;
            border: 1px solid #e2e8f0;
        }
        .form-control:focus {
            border-color: #7c83fd;
            box-shadow: 0 0 0 0.25rem rgba(124, 131, 253, 0.25);
        }
    </style>
</head>
<body>

<div class="login-card">
    <div class="text-center mb-4">
        <div class="logo-box">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h3 class="fw-bold text-dark mb-1">Pet Home Admin</h3>
        <p class="text-muted small">ระบบจัดการหลังบ้าน แอปพลิเคชันรับเลี้ยงสัตว์</p>
    </div>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show rounded-3 py-2 px-3 small" role="alert">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?php echo htmlspecialchars($error); ?>
            <button type="button" class="btn-close py-2" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="login.php">
        <div class="mb-3">
            <label class="form-label small fw-semibold text-secondary">อีเมลผู้ดูแลระบบ (Admin Email)</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-envelope text-muted"></i></span>
                <input type="email" id="email" name="email" class="form-control border-start-0 rounded-end-3" placeholder="admin@pethome.com" required value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label small fw-semibold text-secondary">รหัสผ่าน (Password)</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 rounded-start-3"><i class="bi bi-lock text-muted"></i></span>
                <input type="password" id="password" name="password" class="form-control border-start-0 rounded-end-3" placeholder="••••••" required>
            </div>
        </div>

        <button type="submit" class="btn btn-primary w-100 shadow-sm">
            <i class="bi bi-box-arrow-in-right me-1"></i> เข้าสู่ระบบแอดมิน
        </button>
    </form>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
