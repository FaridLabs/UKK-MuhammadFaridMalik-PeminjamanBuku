<?php
require 'cek_session_admin.php';
require 'koneksi.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../admin/peminjaman.php');
    exit;
}

$anggotaId      = $_SESSION['admin_scan_anggota_id'] ?? null;
$kode_item      = trim($_POST['kode_item'] ?? '');
$batasPinjam    = 3;
$lamaPinjamHari = 7;

if (!$anggotaId || empty($kode_item)) {
    header('Location: ../admin/peminjaman.php?error=empty');
    exit;
}

// ============================================================
// 1. CARI EKSEMPLAR BUKU BERDASARKAN KODE QR UNIK
// ============================================================
$stmtItem = $koneksi->prepare("
    SELECT buku_item.*, buku.judul, buku.id AS master_buku_id
    FROM buku_item 
    JOIN buku ON buku_item.buku_id = buku.id
    WHERE buku_item.kode_item = :kode_item
    LIMIT 1
");
$stmtItem->bindParam(':kode_item', $kode_item);
$stmtItem->execute();
$item = $stmtItem->fetch();

if (!$item) {
    header('Location: ../admin/peminjaman.php?error=buku_tidak_ditemukan');
    exit;
}

// Cek status fisik buku ini
if ($item['status'] === 'dipinjam') {
    header('Location: ../admin/peminjaman.php?error=sedang_dipinjam&judul=' . urlencode($item['judul']));
    exit;
} elseif ($item['status'] !== 'tersedia') {
    header('Location: ../admin/peminjaman.php?error=status_tidak_valid&judul=' . urlencode($item['judul']));
    exit;
}

// ============================================================
// 2. CEK ANTI DOBEL PINJAM: SISWA TIDAK MEMINJAM 2 BUKU JUDUL SAMA
// ============================================================
$cekSudahPinjam = $koneksi->prepare("
    SELECT transaksi.id, buku.judul 
    FROM transaksi 
    JOIN buku_item ON transaksi.buku_item_id = buku_item.id
    JOIN buku ON buku_item.buku_id = buku.id
    WHERE transaksi.anggota_id = :anggotaId 
      AND transaksi.status = 'dipinjam' 
      AND buku_item.buku_id = :buku_id
    LIMIT 1
");
$cekSudahPinjam->bindParam(':anggotaId', $anggotaId);
$cekSudahPinjam->bindParam(':buku_id',   $item['buku_id']);
$cekSudahPinjam->execute();
$pinjamanSama = $cekSudahPinjam->fetch();

if ($pinjamanSama) {
    header('Location: ../admin/peminjaman.php?error=sudah_pinjam_judul&judul=' . urlencode($pinjamanSama['judul']));
    exit;
}

// ============================================================
// 3. CEK BATAS MAKSIMAL KUOTA PEMINJAMAN ANGGOTA (3 BUKU)
// ============================================================
$cekAktif = $koneksi->prepare("SELECT COUNT(*) AS total FROM transaksi WHERE anggota_id = :id AND status = 'dipinjam'");
$cekAktif->bindParam(':id', $anggotaId);
$cekAktif->execute();
if ($cekAktif->fetch()['total'] >= $batasPinjam) {
    header('Location: ../admin/peminjaman.php?error=batas');
    exit;
}

// ============================================================
// 4. EKSEKUSI PEMINJAMAN (TRANSAKSI DATABASE ATOMIC)
// ============================================================
try {
    $koneksi->beginTransaction();

    $tglPinjam     = date('Y-m-d');
    $tglRencana    = date('Y-m-d', strtotime("+{$lamaPinjamHari} days"));
    $kodeTransaksi = 'TRX-' . date('YmdHis') . '-' . $anggotaId;

    $stmtInsert = $koneksi->prepare("
        INSERT INTO transaksi (kode_transaksi, anggota_id, buku_item_id, tgl_pinjam, tgl_kembali_rencana, status)
        VALUES (:kode_transaksi, :anggota_id, :buku_item_id, :tgl_pinjam, :tgl_kembali_rencana, 'dipinjam')
    ");
    $stmtInsert->bindParam(':kode_transaksi', $kodeTransaksi);
    $stmtInsert->bindParam(':anggota_id',     $anggotaId);
    $stmtInsert->bindParam(':buku_item_id',   $item['id']);
    $stmtInsert->bindParam(':tgl_pinjam',     $tglPinjam);
    $stmtInsert->bindParam(':tgl_kembali_rencana', $tglRencana);
    $stmtInsert->execute();

    $stmtUpdate = $koneksi->prepare("UPDATE buku_item SET status = 'dipinjam' WHERE id = :id");
    $stmtUpdate->bindParam(':id', $item['id']);
    $stmtUpdate->execute();

    $koneksi->commit();
    header('Location: ../admin/peminjaman.php?success=pinjam&judul=' . urlencode($item['judul']));
    exit;

} catch (Exception $e) {
    $koneksi->rollBack();
    header('Location: ../admin/peminjaman.php?error=system');
    exit;
}
