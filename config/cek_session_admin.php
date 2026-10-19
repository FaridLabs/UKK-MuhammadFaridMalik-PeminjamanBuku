<?php
// =========================================
// config/cek_session_admin.php
// Proteksi halaman khusus admin
// =========================================

session_start();

if (!isset($_SESSION['admin_id'])) {
    header('Location: ../index.php?error=invalid');
    exit;
}