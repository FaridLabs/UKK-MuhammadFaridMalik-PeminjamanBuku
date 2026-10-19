<?php
require 'cek_session_siswa.php';
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../siswa/riwayat.php');
    exit;
}

$anggotaId = $_SESSION['anggota_id'];
$kode_item = trim($_POST['kode_item'] ?? '');

if (empty($kode_item)) {
    header('Location: ../siswa/riwayat.php');
    exit;
}

// Cari buku_item berdasarkan kode_item hasil scan
$stmtItem = $koneksi->prepare("
    SELECT buku_item.*, buku.judul 
    FROM buku_item 
    JOIN buku ON buku_item.buku_id = buku.id
    WHERE kode_item = :kode_item LIMIT 1
");
$stmtItem->bindParam(':kode_item', $kode_item);
$stmtItem->execute();
$item = $stmtItem->fetch();

if (!$item) {
    header('Location: ../siswa/riwayat.php?error=tidak_ditemukan');
    exit;
}

// Cari transaksi aktif (status dipinjam) untuk buku_item ini, dan pastikan milik anggota yang login
$stmtTrx = $koneksi->prepare("
    SELECT * FROM transaksi 
    WHERE buku_item_id = :buku_item_id AND status = 'dipinjam'
    ORDER BY id DESC LIMIT 1
");
$stmtTrx->bindParam(':buku_item_id', $item['id']);
$stmtTrx->execute();
$transaksi = $stmtTrx->fetch();

if (!$transaksi) {
    header('Location: ../siswa/riwayat.php?error=tidak_ditemukan');
    exit;
}

if ($transaksi['anggota_id'] != $anggotaId) {
    header('Location: ../siswa/riwayat.php?error=bukan_milik');
    exit;
}

// Cek keterlambatan
$tglSekarang = date('Y-m-d');
$isTerlambat = $tglSekarang > $transaksi['tgl_kembali_rencana'];
$statusFinal = $isTerlambat ? 'terlambat' : 'dikembalikan';

try {
    $koneksi->beginTransaction();

    $stmtUpdateTrx = $koneksi->prepare("
        UPDATE transaksi 
        SET tgl_kembali_aktual = :tgl_kembali_aktual, status = :status
        WHERE id = :id
    ");
    $stmtUpdateTrx->bindParam(':tgl_kembali_aktual', $tglSekarang);
    $stmtUpdateTrx->bindParam(':status', $statusFinal);
    $stmtUpdateTrx->bindParam(':id', $transaksi['id']);
    $stmtUpdateTrx->execute();

    $stmtUpdateItem = $koneksi->prepare("UPDATE buku_item SET status = 'tersedia' WHERE id = :id");
    $stmtUpdateItem->bindParam(':id', $item['id']);
    $stmtUpdateItem->execute();

    $koneksi->commit();

    $redirect = '../siswa/riwayat.php?success=' . urlencode($item['judul']);
    if ($isTerlambat) {
        $redirect .= '&terlambat=1';
    }
    header('Location: ' . $redirect);
    exit;

} catch (Exception $e) {
    $koneksi->rollBack();
    header('Location: ../siswa/riwayat.php?error=tidak_ditemukan');
    exit;
}