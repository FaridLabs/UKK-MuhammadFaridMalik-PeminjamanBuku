<?php
require 'cek_session_admin.php';
require 'koneksi.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'tambah') {

    $judul            = trim($_POST['judul'] ?? '');
    $pengarang        = trim($_POST['pengarang'] ?? '');
    $penerbit         = trim($_POST['penerbit'] ?? '');
    $tahun_terbit     = $_POST['tahun_terbit'] ?? null;
    $kategori_id      = $_POST['kategori_id'] ?? null;
    $jumlah_eksemplar = (int)($_POST['jumlah_eksemplar'] ?? 1);

    if ($jumlah_eksemplar < 0) $jumlah_eksemplar = 0;
    if ($jumlah_eksemplar > 100) $jumlah_eksemplar = 100;

    if (empty($judul) || empty($kategori_id)) {
        header('Location: ../admin/buku.php?error=empty');
        exit;
    }

    try {
        $koneksi->beginTransaction();

        // Auto-generate kode_buku yang unik
        do {
            $kode_buku = 'BK-' . date('YmdHis') . rand(10, 99);
            $cekKode = $koneksi->prepare("SELECT COUNT(*) AS total FROM buku WHERE kode_buku = :kode");
            $cekKode->bindParam(':kode', $kode_buku);
            $cekKode->execute();
        } while ($cekKode->fetch()['total'] > 0);

        $stmt = $koneksi->prepare("
            INSERT INTO buku (kode_buku, judul, pengarang, penerbit, tahun_terbit, kategori_id)
            VALUES (:kode_buku, :judul, :pengarang, :penerbit, :tahun_terbit, :kategori_id)
        ");
        $stmt->bindParam(':kode_buku',    $kode_buku);
        $stmt->bindParam(':judul',        $judul);
        $stmt->bindParam(':pengarang',    $pengarang);
        $stmt->bindParam(':penerbit',     $penerbit);
        $stmt->bindParam(':tahun_terbit', $tahun_terbit);
        $stmt->bindParam(':kategori_id',  $kategori_id);
        $stmt->execute();

        $bukuId = $koneksi->lastInsertId();

        // Generate N eksemplar fisik dengan kode QR unik otomatis
        if ($jumlah_eksemplar > 0) {
            $stmtItem = $koneksi->prepare("INSERT INTO buku_item (kode_item, buku_id, status) VALUES (:kode_item, :buku_id, 'tersedia')");
            for ($i = 1; $i <= $jumlah_eksemplar; $i++) {
                $kode_item = "BK-{$bukuId}-" . str_pad($i, 3, '0', STR_PAD_LEFT);
                $stmtItem->bindParam(':kode_item', $kode_item);
                $stmtItem->bindParam(':buku_id',   $bukuId);
                $stmtItem->execute();
            }
        }

        $koneksi->commit();
        header('Location: ../admin/buku.php?success=tambah&id=' . $bukuId . '&jumlah=' . $jumlah_eksemplar);
        exit;

    } catch (Exception $e) {
        $koneksi->rollBack();
        header('Location: ../admin/buku.php?error=system');
        exit;
    }

} elseif ($action === 'edit') {

    $id           = $_POST['id'] ?? '';
    $judul        = trim($_POST['judul'] ?? '');
    $pengarang    = trim($_POST['pengarang'] ?? '');
    $penerbit     = trim($_POST['penerbit'] ?? '');
    $tahun_terbit = $_POST['tahun_terbit'] ?? null;
    $kategori_id  = $_POST['kategori_id'] ?? null;

    if (empty($judul) || empty($kategori_id) || empty($id)) {
        header('Location: ../admin/buku.php?error=empty');
        exit;
    }

    $stmt = $koneksi->prepare("
        UPDATE buku SET judul = :judul, pengarang = :pengarang, penerbit = :penerbit,
            tahun_terbit = :tahun_terbit, kategori_id = :kategori_id
        WHERE id = :id
    ");
    $stmt->bindParam(':judul',        $judul);
    $stmt->bindParam(':pengarang',    $pengarang);
    $stmt->bindParam(':penerbit',     $penerbit);
    $stmt->bindParam(':tahun_terbit', $tahun_terbit);
    $stmt->bindParam(':kategori_id',  $kategori_id);
    $stmt->bindParam(':id',           $id);
    $stmt->execute();

    header('Location: ../admin/buku.php?success=edit');
    exit;

} elseif ($action === 'hapus') {

    $id = $_GET['id'] ?? '';
    if (empty($id)) { header('Location: ../admin/buku.php'); exit; }

    // Cek apakah judul masih punya eksemplar
    $cek = $koneksi->prepare("SELECT COUNT(*) AS total FROM buku_item WHERE buku_id = :id");
    $cek->bindParam(':id', $id);
    $cek->execute();

    if ($cek->fetch()['total'] > 0) {
        header('Location: ../admin/buku.php?error=ada_item');
        exit;
    }

    $stmt = $koneksi->prepare("DELETE FROM buku WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header('Location: ../admin/buku.php?success=hapus');
    exit;

} else {
    header('Location: ../admin/buku.php');
    exit;
}