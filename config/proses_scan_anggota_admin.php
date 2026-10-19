<?php
require 'cek_session_admin.php';
require 'koneksi.php';

$redirect = $_POST['redirect'] ?? 'peminjaman.php';
// Validasi redirect agar hanya ke halaman admin yang valid
if ($redirect !== 'pengembalian.php') {
    $redirect = 'peminjaman.php';
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: ../admin/{$redirect}");
    exit;
}

$kodeKartu = trim($_POST['kode_kartu'] ?? '');

if (empty($kodeKartu)) {
    header("Location: ../admin/{$redirect}?error=empty");
    exit;
}

// Cari anggota berdasarkan kode_rfid ATAU nis (fallback jika belum punya RFID)
$stmt = $koneksi->prepare("
    SELECT * FROM anggota 
    WHERE kode_rfid = :kode OR nis = :nis
    LIMIT 1
");
$stmt->bindParam(':kode', $kodeKartu);
$stmt->bindParam(':nis', $kodeKartu);
$stmt->execute();
$anggota = $stmt->fetch();

if (!$anggota) {
    header("Location: ../admin/{$redirect}?error=tidak_ditemukan");
    exit;
}

// Simpan anggota yang dipilih ke session admin sesuai halaman pemanggil
if ($redirect === 'pengembalian.php') {
    $_SESSION['admin_kembali_anggota_id'] = $anggota['id'];
} else {
    $_SESSION['admin_scan_anggota_id'] = $anggota['id'];
}

header("Location: ../admin/{$redirect}");
exit;
