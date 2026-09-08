<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function checkAdminAuth() {
    if (!isset($_SESSION['admin_user']) || $_SESSION['admin_user']['role'] !== 'admin') {
        header("Location: login.php");
        exit;
    }
}
?>
