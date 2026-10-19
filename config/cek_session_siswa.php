<?php
// =========================================
// config/cek_session_siswa.php
// Proteksi halaman khusus siswa/anggota
// =========================================

session_start();

if (!isset($_SESSION['anggota_id'])) {
    header('Location: ../index.php?error=invalid');
    exit;
}