<?php
session_start();
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../index.php');
    exit;
}

$username = trim($_POST['username'] ?? '');
$password = $_POST['password'] ?? '';

if (empty($username) || empty($password)) {
    header('Location: ../index.php?error=empty');
    exit;
}

// ================= CEK KE TABEL ADMIN DULU =================
$stmtAdmin = $koneksi->prepare("SELECT * FROM admin WHERE username = :username LIMIT 1");
$stmtAdmin->bindParam(':username', $username);
$stmtAdmin->execute();
$admin = $stmtAdmin->fetch();

if ($admin && password_verify($password, $admin['password'])) {
    $_SESSION['admin_id']   = $admin['id'];
    $_SESSION['admin_nama'] = $admin['nama_lengkap'];
    header('Location: ../admin/dashboard.php');
    exit;
}

// ================= KALAU BUKAN ADMIN, CEK KE TABEL ANGGOTA (SISWA) =================
$stmtAnggota = $koneksi->prepare("SELECT * FROM anggota WHERE username = :username LIMIT 1");
$stmtAnggota->bindParam(':username', $username);
$stmtAnggota->execute();
$anggota = $stmtAnggota->fetch();

if ($anggota && password_verify($password, $anggota['password'])) {
    $_SESSION['anggota_id']    = $anggota['id'];
    $_SESSION['anggota_nama']  = $anggota['nama_lengkap'];
    $_SESSION['anggota_kelas'] = $anggota['kelas'];
    header('Location: ../siswa/dashboard.php');
    exit;
}

// ================= KALAU DUA-DUANYA TIDAK COCOK =================
header('Location: ../index.php?error=invalid');
exit;