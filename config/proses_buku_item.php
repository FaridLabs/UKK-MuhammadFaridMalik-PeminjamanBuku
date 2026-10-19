<?php
require 'cek_session_admin.php';
require 'koneksi.php';

$action = $_POST['action'] ?? $_GET['action'] ?? '';
$bukuId = $_POST['buku_id'] ?? $_GET['buku_id'] ?? '';

if (empty($bukuId)) {
    header('Location: ../admin/buku.php');
    exit;
}

// ============================================================
// 1. GENERATE QR EKSEMPLAR BARU DI ADMIN (UTAMA)
// ============================================================
if ($action === 'generate_qr' || $action === 'generate_baru') {

    $jumlah = (int)($_POST['jumlah'] ?? 1);
    if ($jumlah < 1) $jumlah = 1;
    if ($jumlah > 100) $jumlah = 100;

    // Pastikan buku ada di master buku
    $stmtBuku = $koneksi->prepare("SELECT id, judul FROM buku WHERE id = :id LIMIT 1");
    $stmtBuku->bindParam(':id', $bukuId);
    $stmtBuku->execute();
    $buku = $stmtBuku->fetch();

    if (!$buku) {
        header('Location: ../admin/buku.php');
        exit;
    }

    try {
        $koneksi->beginTransaction();

        // Cari nomor urut terakhir untuk buku ini dengan format BK-{buku_id}-XXX
        $prefix = "BK-{$bukuId}-";
        $stmtAll = $koneksi->prepare("SELECT kode_item FROM buku_item WHERE buku_id = :buku_id AND kode_item LIKE :prefix");
        $likePrefix = $prefix . "%";
        $stmtAll->bindParam(':buku_id', $bukuId);
        $stmtAll->bindParam(':prefix', $likePrefix);
        $stmtAll->execute();
        $existingCodes = $stmtAll->fetchAll(PDO::FETCH_COLUMN);

        $maxUrutan = 0;
        foreach ($existingCodes as $c) {
            $nomorPart = substr($c, strlen($prefix));
            if (is_numeric($nomorPart)) {
                $num = (int)$nomorPart;
                if ($num > $maxUrutan) {
                    $maxUrutan = $num;
                }
            }
        }

        $urutanMulai = $maxUrutan + 1;
        $stmtInsert = $koneksi->prepare("INSERT INTO buku_item (kode_item, buku_id, status) VALUES (:kode_item, :buku_id, 'tersedia')");

        for ($i = 0; $i < $jumlah; $i++) {
            $nomor = $urutanMulai + $i;
            do {
                $kode_item = $prefix . str_pad($nomor, 3, '0', STR_PAD_LEFT);
                // Pastikan unik global di buku_item
                $cek = $koneksi->prepare("SELECT COUNT(*) FROM buku_item WHERE kode_item = :kode");
                $cek->bindParam(':kode', $kode_item);
                $cek->execute();
                $exists = ($cek->fetchColumn() > 0);
                if ($exists) {
                    $nomor++;
                }
            } while ($exists);

            $stmtInsert->bindParam(':kode_item', $kode_item);
            $stmtInsert->bindParam(':buku_id',   $bukuId);
            $stmtInsert->execute();
        }

        $koneksi->commit();
        header("Location: ../admin/buku_item.php?buku_id={$bukuId}&success=generate&jumlah={$jumlah}");
        exit;

    } catch (Exception $e) {
        $koneksi->rollBack();
        header("Location: ../admin/buku_item.php?buku_id=$bukuId&error=system");
        exit;
    }

// ============================================================
// 2. INPUT MANUAL KODE BARCODE / QR (OPSIONAL)
// ============================================================
} elseif ($action === 'tambah_manual' || $action === 'tambah') {

    $kode_item = trim($_POST['kode_item'] ?? '');

    if (empty($kode_item)) {
        header("Location: ../admin/buku_item.php?buku_id=$bukuId&error=kosong");
        exit;
    }

    // Setiap buku fisik harus memiliki kode yang unik
    $cek = $koneksi->prepare("
        SELECT buku_item.id, buku.judul 
        FROM buku_item 
        JOIN buku ON buku_item.buku_id = buku.id 
        WHERE buku_item.kode_item = :kode 
        LIMIT 1
    ");
    $cek->bindParam(':kode', $kode_item);
    $cek->execute();
    $ada = $cek->fetch();

    if ($ada) {
        header("Location: ../admin/buku_item.php?buku_id=$bukuId&error=sudah_ada&kode=" . urlencode($kode_item) . "&judul_ada=" . urlencode($ada['judul']));
        exit;
    }

    $stmt = $koneksi->prepare("INSERT INTO buku_item (kode_item, buku_id, status) VALUES (:kode_item, :buku_id, 'tersedia')");
    $stmt->bindParam(':kode_item', $kode_item);
    $stmt->bindParam(':buku_id',   $bukuId);
    $stmt->execute();

    header("Location: ../admin/buku_item.php?buku_id=$bukuId&success=manual&kode=" . urlencode($kode_item));
    exit;

// ============================================================
// 3. UBAH STATUS EKSEMPLAR (TERSEDIA / RUSAK / HILANG)
// ============================================================
} elseif ($action === 'ubah_status') {

    $id     = $_POST['id'] ?? '';
    $status = $_POST['status'] ?? '';

    if (empty($id) || empty($status)) {
        header("Location: ../admin/buku_item.php?buku_id=$bukuId");
        exit;
    }

    $stmt = $koneksi->prepare("UPDATE buku_item SET status = :status WHERE id = :id");
    $stmt->bindParam(':status', $status);
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header("Location: ../admin/buku_item.php?buku_id=$bukuId&success=status");
    exit;

// ============================================================
// 4. HAPUS EKSEMPLAR FISIK
// ============================================================
} elseif ($action === 'hapus') {

    $id = $_GET['id'] ?? '';
    if (empty($id)) {
        header("Location: ../admin/buku_item.php?buku_id=$bukuId");
        exit;
    }

    $cek = $koneksi->prepare("SELECT status FROM buku_item WHERE id = :id");
    $cek->bindParam(':id', $id);
    $cek->execute();
    $itemData = $cek->fetch();

    if ($itemData && $itemData['status'] === 'dipinjam') {
        header("Location: ../admin/buku_item.php?buku_id=$bukuId&error=dipinjam");
        exit;
    }

    $stmt = $koneksi->prepare("DELETE FROM buku_item WHERE id = :id");
    $stmt->bindParam(':id', $id);
    $stmt->execute();

    header("Location: ../admin/buku_item.php?buku_id=$bukuId&success=hapus");
    exit;

} else {
    header('Location: ../admin/buku.php');
    exit;
}