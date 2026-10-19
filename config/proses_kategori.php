<?php
require 'cek_session_admin.php';
require 'koneksi.php';

// Ambil action dari POST (form) atau GET (link hapus)
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'tambah') {

    $nama_kategori = trim($_POST['nama_kategori'] ?? '');

    if (empty($nama_kategori)) {
        header('Location: ../admin/kategori.php?error=empty');
        exit;
    }

    $stmt = $koneksi->prepare("INSERT INTO kategori (nama_kategori) VALUES (:nama)");
    $stmt->bindParam(':nama', $nama_kategori);
    $stmt->execute();

    header('Location: ../admin/kategori.php?success=1');
    exit;

} elseif ($action === 'edit') {

    $id            = $_POST['id'] ?? '';
    $nama_kategori = trim($_POST['nama_kategori'] ?? '');

    if (empty($nama_kategori) || empty($id)) {
        header('Location: ../admin/kategori.php?error=empty');
        exit;
    }

    $stmt = $koneksi->prepare("UPDATE kategori SET nama_kategori = :nama WHERE id = :id");
    $stmt->bindParam(':nama', $nama_kategori);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header('Location: ../admin/kategori.php?success=1');
    exit;

} elseif ($action === 'hapus') {

    $id = $_GET['id'] ?? '';

    if (empty($id)) {
        header('Location: ../admin/kategori.php');
        exit;
    }

    // Cek apakah kategori masih dipakai oleh buku
    $cek = $koneksi->prepare("SELECT COUNT(*) AS total FROM buku WHERE kategori_id = :id");
    $cek->bindParam(':id', $id);
    $cek->execute();

    if ($cek->fetch()['total'] > 0) {
        header('Location: ../admin/kategori.php?error=ada_buku');
        exit;
    }

    $stmt = $koneksi->prepare("DELETE FROM kategori WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header('Location: ../admin/kategori.php?success=1');
    exit;

} else {
    header('Location: ../admin/kategori.php');
    exit;
}