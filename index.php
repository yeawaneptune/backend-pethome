<?php
// Quick API health check
$apiBase = (isset($_SERVER["HTTPS"]) ? "https" : "http") . "://" . $_SERVER["HTTP_HOST"];
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PetHome API Dashboard</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: "Segoe UI", sans-serif;
            background: #0f172a;
            color: #e2e8f0;
            min-height: 100vh;
        }
        header {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            border-bottom: 1px solid #334155;
            padding: 24px 40px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .logo { font-size: 2rem; }
        header h1 { font-size: 1.5rem; font-weight: 700; color: #f8fafc; }
        header p { font-size: 0.85rem; color: #94a3b8; margin-top: 2px; }
        .badge {
            margin-left: auto;
            background: #22c55e20;
            border: 1px solid #22c55e;
            color: #22c55e;
            padding: 6px 16px;
            border-radius: 999px;
            font-size: 0.8rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 6px;
        }
        .dot {
            width: 8px; height: 8px;
            background: #22c55e;
            border-radius: 50%;
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
        .container { max-width: 1100px; margin: 40px auto; padding: 0 24px; }
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 16px;
            margin-bottom: 40px;
        }
        .stat-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 20px 24px;
        }
        .stat-card .icon { font-size: 1.8rem; margin-bottom: 8px; }
        .stat-card .label { font-size: 0.8rem; color: #64748b; text-transform: uppercase; letter-spacing: 0.05em; }
        .stat-card .value { font-size: 1.6rem; font-weight: 700; color: #f8fafc; margin-top: 4px; }
        .section-title {
            font-size: 1rem;
            font-weight: 600;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            margin-bottom: 16px;
        }
        .endpoints-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(480px, 1fr));
            gap: 16px;
            margin-bottom: 40px;
        }
        .endpoint-card {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            overflow: hidden;
        }
        .endpoint-card .card-header {
            background: #0f172a;
            padding: 12px 20px;
            font-size: 0.85rem;
            font-weight: 600;
            color: #94a3b8;
            border-bottom: 1px solid #334155;
        }
        .endpoint-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 20px;
            border-bottom: 1px solid #1e293b;
            transition: background 0.15s;
        }
        .endpoint-item:last-child { border-bottom: none; }
        .endpoint-item:hover { background: #0f172a30; }
        .method {
            font-size: 0.72rem;
            font-weight: 700;
            padding: 3px 8px;
            border-radius: 4px;
            min-width: 44px;
            text-align: center;
        }
        .get  { background: #0ea5e920; color: #38bdf8; border: 1px solid #0ea5e9; }
        .post { background: #22c55e20; color: #4ade80; border: 1px solid #22c55e; }
        .endpoint-path {
            font-family: "Courier New", monospace;
            font-size: 0.82rem;
            color: #cbd5e1;
            flex: 1;
        }
        .endpoint-desc {
            font-size: 0.78rem;
            color: #64748b;
        }
        .copy-btn {
            background: none;
            border: none;
            color: #475569;
            cursor: pointer;
            padding: 4px;
            border-radius: 4px;
            font-size: 0.9rem;
            transition: color 0.15s;
        }
        .copy-btn:hover { color: #94a3b8; }
        .base-url-box {
            background: #1e293b;
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 20px 24px;
            margin-bottom: 40px;
            display: flex;
            align-items: center;
            gap: 16px;
        }
        .base-url-box .label { font-size: 0.8rem; color: #64748b; white-space: nowrap; }
        .base-url-box code {
            font-family: "Courier New", monospace;
            font-size: 0.9rem;
            color: #a78bfa;
            flex: 1;
        }
        footer {
            text-align: center;
            padding: 32px;
            color: #334155;
            font-size: 0.8rem;
            border-top: 1px solid #1e293b;
            margin-top: 40px;
        }
    </style>
</head>
<body>

<header>
    <div class="logo">🐾</div>
    <div>
        <h1>PetHome API</h1>
        <p>Backend Service Dashboard</p>
    </div>
    <div class="badge">
        <span class="dot"></span>
        Online
    </div>
</header>

<div class="container">

    <!-- Stats -->
    <div class="stats-grid">
        <div class="stat-card">
            <div class="icon">🔌</div>
            <div class="label">Endpoints</div>
            <div class="value">15</div>
        </div>
        <div class="stat-card">
            <div class="icon">🗄️</div>
            <div class="label">Database</div>
            <div class="value">TiDB Cloud</div>
        </div>
        <div class="stat-card">
            <div class="icon">⚙️</div>
            <div class="label">PHP Version</div>
            <div class="value"><?= PHP_MAJOR_VERSION . "." . PHP_MINOR_VERSION ?></div>
        </div>
        <div class="stat-card">
            <div class="icon">🕐</div>
            <div class="label">Server Time</div>
            <div class="value" style="font-size:1rem"><?= date("H:i:s") ?></div>
        </div>
    </div>

    <!-- Base URL -->
    <div class="section-title">Base URL</div>
    <div class="base-url-box">
        <span class="label">🌐 API Base</span>
        <code id="baseUrl"><?= $apiBase ?>/server/api/</code>
        <button class="copy-btn" onclick="copyBase()" title="Copy">📋</button>
    </div>

    <!-- Endpoints -->
    <div class="section-title">API Endpoints</div>
    <div class="endpoints-grid">

        <!-- Auth -->
        <div class="endpoint-card">
            <div class="card-header">🔐 Authentication</div>
            <div class="endpoint-item">
                <span class="method post">POST</span>
                <span class="endpoint-path">/server/api/login.php</span>
                <span class="endpoint-desc">เข้าสู่ระบบ</span>
            </div>
            <div class="endpoint-item">
                <span class="method post">POST</span>
                <span class="endpoint-path">/server/api/register.php</span>
                <span class="endpoint-desc">สมัครสมาชิก</span>
            </div>
        </div>

        <!-- Pets -->
        <div class="endpoint-card">
            <div class="card-header">🐶 Pets</div>
            <div class="endpoint-item">
                <span class="method get">GET</span>
                <span class="endpoint-path">/server/api/read.php</span>
                <span class="endpoint-desc">ดึงรายการสัตว์เลี้ยง</span>
            </div>
            <div class="endpoint-item">
                <span class="method post">POST</span>
                <span class="endpoint-path">/server/api/add_pet.php</span>
                <span class="endpoint-desc">เพิ่มสัตว์เลี้ยง</span>
            </div>
            <div class="endpoint-item">
                <span class="method post">POST</span>
                <span class="endpoint-path">/server/api/update_pet.php</span>
                <span class="endpoint-desc">อัปเดตข้อมูล</span>
            </div>
            <div class="endpoint-item">
                <span class="method post">POST</span>
                <span class="endpoint-path">/server/api/delete_pet.php</span>
                <span class="endpoint-desc">ลบสัตว์เลี้ยง</span>
            </div>
        </div>

        <!-- Adoption -->
        <div class="endpoint-card">
            <div class="card-header">🏠 Adoption</div>
            <div class="endpoint-item">
                <span class="method post">POST</span>
                <span class="endpoint-path">/server/api/adopt_pet.php</span>
                <span class="endpoint-desc">ส่งคำขอรับเลี้ยง</span>
            </div>
            <div class="endpoint-item">
                <span class="method get">GET</span>
                <span class="endpoint-path">/server/api/my_requests.php</span>
                <span class="endpoint-desc">คำขอของฉัน</span>
            </div>
        </div>

        <!-- Favorites -->
        <div class="endpoint-card">
            <div class="card-header">❤️ Favorites</div>
            <div class="endpoint-item">
                <span class="method get">GET</span>
                <span class="endpoint-path">/server/api/favorites.php</span>
                <span class="endpoint-desc">รายการโปรด</span>
            </div>
            <div class="endpoint-item">
                <span class="method post">POST</span>
                <span class="endpoint-path">/server/api/favorites.php</span>
                <span class="endpoint-desc">Toggle รายการโปรด</span>
            </div>
        </div>

        <!-- Users -->
        <div class="endpoint-card">
            <div class="card-header">👤 Users</div>
            <div class="endpoint-item">
                <span class="method get">GET</span>
                <span class="endpoint-path">/server/api/get_users.php</span>
                <span class="endpoint-desc">ดึงรายชื่อผู้ใช้</span>
            </div>
            <div class="endpoint-item">
                <span class="method post">POST</span>
                <span class="endpoint-path">/server/api/update_user.php</span>
                <span class="endpoint-desc">อัปเดตข้อมูลผู้ใช้</span>
            </div>
            <div class="endpoint-item">
                <span class="method post">POST</span>
                <span class="endpoint-path">/server/api/delete_user.php</span>
                <span class="endpoint-desc">ลบผู้ใช้</span>
            </div>
        </div>

        <!-- Admin -->
        <div class="endpoint-card">
            <div class="card-header">📊 Admin & Stats</div>
            <div class="endpoint-item">
                <span class="method get">GET</span>
                <span class="endpoint-path">/server/api/stats.php</span>
                <span class="endpoint-desc">สถิติภาพรวม</span>
            </div>
            <div class="endpoint-item">
                <span class="method get">GET</span>
                <span class="endpoint-path">/server/api/notifications.php</span>
                <span class="endpoint-desc">การแจ้งเตือน</span>
            </div>
            <div class="endpoint-item">
                <span class="method get">GET</span>
                <span class="endpoint-path">/admin/</span>
                <span class="endpoint-desc">Admin Panel</span>
            </div>
        </div>

    </div>
</div>

<footer>
    PetHome Backend &nbsp;|&nbsp; PHP <?= PHP_VERSION ?> &nbsp;|&nbsp; Apache &nbsp;|&nbsp; TiDB Cloud
</footer>

<script>
function copyBase() {
    const text = document.getElementById("baseUrl").textContent;
    navigator.clipboard.writeText(text).then(() => {
        const btn = document.querySelector(".copy-btn");
        btn.textContent = "✅";
        setTimeout(() => btn.textContent = "📋", 1500);
    });
}
</script>

</body>
</html>
