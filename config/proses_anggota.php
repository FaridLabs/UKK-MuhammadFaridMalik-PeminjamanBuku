<?php
require 'cek_session_admin.php';
require 'koneksi.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'tambah') {

    $nis          = trim($_POST['nis'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $kelas        = trim($_POST['kelas'] ?? '');
    $no_hp        = trim($_POST['no_hp'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';

    if (empty($nis) || empty($nama_lengkap) || empty($kelas) || empty($username) || empty($password)) {
        header('Location: ../admin/anggota.php?error=empty');
        exit;
    }

    $cekNis = $koneksi->prepare("SELECT id FROM anggota WHERE nis = :nis");
    $cekNis->bindParam(':nis', $nis);
    $cekNis->execute();
    if ($cekNis->fetch()) {
        header('Location: ../admin/anggota.php?error=nis_ada');
        exit;
    }

    $cekUsername = $koneksi->prepare("SELECT id FROM anggota WHERE username = :username");
    $cekUsername->bindParam(':username', $username);
    $cekUsername->execute();
    if ($cekUsername->fetch()) {
        header('Location: ../admin/anggota.php?error=username_ada');
        exit;
    }

    $passwordHash = password_hash($password, PASSWORD_BCRYPT);

    $stmt = $koneksi->prepare("
        INSERT INTO anggota (nis, nama_lengkap, kelas, no_hp, username, password)
        VALUES (:nis, :nama_lengkap, :kelas, :no_hp, :username, :password)
    ");
    $stmt->bindParam(':nis', $nis);
    $stmt->bindParam(':nama_lengkap', $nama_lengkap);
    $stmt->bindParam(':kelas', $kelas);
    $stmt->bindParam(':no_hp', $no_hp);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':password', $passwordHash);
    $stmt->execute();

    header('Location: ../admin/anggota.php?success=1');
    exit;

} elseif ($action === 'edit') {

    $id           = $_POST['id'] ?? '';
    $nis          = trim($_POST['nis'] ?? '');
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $kelas        = trim($_POST['kelas'] ?? '');
    $no_hp        = trim($_POST['no_hp'] ?? '');
    $username     = trim($_POST['username'] ?? '');
    $password     = $_POST['password'] ?? '';

    if (empty($nis) || empty($nama_lengkap) || empty($kelas) || empty($username) || empty($id)) {
        header('Location: ../admin/anggota.php?error=empty');
        exit;
    }

    $cekNis = $koneksi->prepare("SELECT id FROM anggota WHERE nis = :nis AND id != :id");
    $cekNis->bindParam(':nis', $nis);
    $cekNis->bindParam(':id', $id);
    $cekNis->execute();
    if ($cekNis->fetch()) {
        header('Location: ../admin/anggota.php?error=nis_ada');
        exit;
    }

    $cekUsername = $koneksi->prepare("SELECT id FROM anggota WHERE username = :username AND id != :id");
    $cekUsername->bindParam(':username', $username);
    $cekUsername->bindParam(':id', $id);
    $cekUsername->execute();
    if ($cekUsername->fetch()) {
        header('Location: ../admin/anggota.php?error=username_ada');
        exit;
    }

    if (!empty($password)) {
        // Password diubah
        $passwordHash = password_hash($password, PASSWORD_BCRYPT);
        $stmt = $koneksi->prepare("
            UPDATE anggota SET nis = :nis, nama_lengkap = :nama_lengkap, kelas = :kelas,
                no_hp = :no_hp, username = :username, password = :password
            WHERE id = :id
        ");
        $stmt->bindParam(':password', $passwordHash);
    } else {
        // Password tidak diubah
        $stmt = $koneksi->prepare("
            UPDATE anggota SET nis = :nis, nama_lengkap = :nama_lengkap, kelas = :kelas,
                no_hp = :no_hp, username = :username
            WHERE id = :id
        ");
    }
    $stmt->bindParam(':nis', $nis);
    $stmt->bindParam(':nama_lengkap', $nama_lengkap);
    $stmt->bindParam(':kelas', $kelas);
    $stmt->bindParam(':no_hp', $no_hp);
    $stmt->bindParam(':username', $username);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header('Location: ../admin/anggota.php?success=1');
    exit;

} elseif ($action === 'set_rfid') {

    $id        = $_POST['id'] ?? '';
    $kode_rfid = trim($_POST['kode_rfid'] ?? '');

    if (empty($id) || empty($kode_rfid)) {
        header('Location: ../admin/anggota.php?error=empty');
        exit;
    }

    // Cek apakah kartu RFID ini sudah dipakai anggota lain
    $cek = $koneksi->prepare("SELECT id FROM anggota WHERE kode_rfid = :kode_rfid AND id != :id");
    $cek->bindParam(':kode_rfid', $kode_rfid);
    $cek->bindParam(':id', $id);
    $cek->execute();
    if ($cek->fetch()) {
        header('Location: ../admin/anggota.php?error=rfid_ada');
        exit;
    }

    $stmt = $koneksi->prepare("UPDATE anggota SET kode_rfid = :kode_rfid WHERE id = :id");
    $stmt->bindParam(':kode_rfid', $kode_rfid);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header('Location: ../admin/anggota.php?success=1');
    exit;

} elseif ($action === 'hapus') {

    $id = $_GET['id'] ?? '';
    if (empty($id)) { header('Location: ../admin/anggota.php'); exit; }

    // Cek apakah anggota ini masih memiliki buku yang sedang dipinjam (pinjaman aktif)
    $cek = $koneksi->prepare("SELECT COUNT(*) AS total FROM transaksi WHERE anggota_id = :id AND status = 'dipinjam'");
    $cek->bindParam(':id', $id);
    $cek->execute();

    if ($cek->fetch()['total'] > 0) {
        header('Location: ../admin/anggota.php?error=sedang_meminjam');
        exit;
    }

    $stmt = $koneksi->prepare("DELETE FROM anggota WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header('Location: ../admin/anggota.php?success=hapus');
    exit;

} else {
    header('Location: ../admin/anggota.php');
    exit;
}