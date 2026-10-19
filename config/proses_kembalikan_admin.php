<?php
require 'cek_session_admin.php';
require 'koneksi.php';

// Mendukung POST (dari form scan) maupun GET (dari tombol aksi kembali di daftar)
$kode_item    = trim($_POST['kode_item'] ?? $_GET['kode_item'] ?? '');
$transaksi_id = trim($_POST['transaksi_id'] ?? $_GET['transaksi_id'] ?? '');
$anggotaId    = $_SESSION['admin_kembali_anggota_id'] ?? null;

if (empty($kode_item) && empty($transaksi_id)) {
    header('Location: ../admin/pengembalian.php?error=empty');
    exit;
}

$transaksi = null;
$item      = null;

if (!empty($transaksi_id)) {
    // Cari transaksi aktif berdasarkan ID transaksi
    $stmtTrx = $koneksi->prepare("
        SELECT transaksi.*, anggota.nama_lengkap, anggota.kelas, buku.judul, buku_item.kode_item
        FROM transaksi
        JOIN anggota ON transaksi.anggota_id = anggota.id
        JOIN buku_item ON transaksi.buku_item_id = buku_item.id
        JOIN buku ON buku_item.buku_id = buku.id
        WHERE transaksi.id = :id AND transaksi.status = 'dipinjam'
        LIMIT 1
    ");
    $stmtTrx->bindParam(':id', $transaksi_id);
    $stmtTrx->execute();
    $transaksi = $stmtTrx->fetch();

    if ($transaksi) {
        $stmtItem = $koneksi->prepare("SELECT * FROM buku_item WHERE id = :id LIMIT 1");
        $stmtItem->bindParam(':id', $transaksi['buku_item_id']);
        $stmtItem->execute();
        $item = $stmtItem->fetch();
    }
} else {
    // Cari transaksi aktif milik anggota yang sedang di-scan untuk buku dengan barcode ini
    if ($anggotaId) {
        $stmtTrx = $koneksi->prepare("
            SELECT transaksi.*, anggota.nama_lengkap, anggota.kelas, buku.judul, buku_item.kode_item
            FROM transaksi
            JOIN anggota ON transaksi.anggota_id = anggota.id
            JOIN buku_item ON transaksi.buku_item_id = buku_item.id
            JOIN buku ON buku_item.buku_id = buku.id
            WHERE transaksi.anggota_id = :anggotaId 
              AND transaksi.status = 'dipinjam' 
              AND buku_item.kode_item = :kode_item
            ORDER BY transaksi.id ASC 
            LIMIT 1
        ");
        $stmtTrx->bindParam(':anggotaId', $anggotaId);
        $stmtTrx->bindParam(':kode_item', $kode_item);
        $stmtTrx->execute();
        $transaksi = $stmtTrx->fetch();

        if ($transaksi) {
            $stmtItem = $koneksi->prepare("SELECT * FROM buku_item WHERE id = :id LIMIT 1");
            $stmtItem->bindParam(':id', $transaksi['buku_item_id']);
            $stmtItem->execute();
            $item = $stmtItem->fetch();
        } else {
            // Cek apakah buku dengan barcode ini sedang dipinjam siswa lain atau tidak sedang dipinjam
            $cekLain = $koneksi->prepare("
                SELECT transaksi.*, anggota.nama_lengkap, buku.judul
                FROM transaksi
                JOIN anggota ON transaksi.anggota_id = anggota.id
                JOIN buku_item ON transaksi.buku_item_id = buku_item.id
                JOIN buku ON buku_item.buku_id = buku.id
                WHERE transaksi.status = 'dipinjam' AND buku_item.kode_item = :kode_item
                LIMIT 1
            ");
            $cekLain->bindParam(':kode_item', $kode_item);
            $cekLain->execute();
            $pinjamLain = $cekLain->fetch();

            if ($pinjamLain) {
                header('Location: ../admin/pengembalian.php?error=bukan_milik_anggota&peminjam=' . urlencode($pinjamLain['nama_lengkap']) . '&judul=' . urlencode($pinjamLain['judul']));
                exit;
            } else {
                header('Location: ../admin/pengembalian.php?error=bukan_dipinjam');
                exit;
            }
        }
    } else {
        // Fallback jika tidak ada anggota aktif di session
        $stmtTrx = $koneksi->prepare("
            SELECT transaksi.*, anggota.nama_lengkap, anggota.kelas, buku.judul, buku_item.kode_item
            FROM transaksi
            JOIN anggota ON transaksi.anggota_id = anggota.id
            JOIN buku_item ON transaksi.buku_item_id = buku_item.id
            JOIN buku ON buku_item.buku_id = buku.id
            WHERE buku_item.kode_item = :kode_item AND transaksi.status = 'dipinjam'
            ORDER BY transaksi.id ASC LIMIT 1
        ");
        $stmtTrx->bindParam(':kode_item', $kode_item);
        $stmtTrx->execute();
        $transaksi = $stmtTrx->fetch();

        if ($transaksi) {
            $stmtItem = $koneksi->prepare("SELECT * FROM buku_item WHERE id = :id LIMIT 1");
            $stmtItem->bindParam(':id', $transaksi['buku_item_id']);
            $stmtItem->execute();
            $item = $stmtItem->fetch();
        }
    }
}

if (!$transaksi || !$item) {
    header('Location: ../admin/pengembalian.php?error=tidak_ditemukan');
    exit;
}

$tglSekarang   = date('Y-m-d');
$isTerlambat   = $tglSekarang > $transaksi['tgl_kembali_rencana'];
$statusFinal   = $isTerlambat ? 'terlambat' : 'dikembalikan';
$hariTerlambat = 0;
if ($isTerlambat) {
    $hariTerlambat = (int) round((strtotime($tglSekarang) - strtotime($transaksi['tgl_kembali_rencana'])) / 86400);
}

try {
    $koneksi->beginTransaction();

    $stmtUpdateTrx = $koneksi->prepare("
        UPDATE transaksi SET tgl_kembali_aktual = :tgl, status = :status WHERE id = :id
    ");
    $stmtUpdateTrx->bindParam(':tgl',    $tglSekarang);
    $stmtUpdateTrx->bindParam(':status', $statusFinal);
    $stmtUpdateTrx->bindParam(':id',     $transaksi['id']);
    $stmtUpdateTrx->execute();

    $stmtUpdateItem = $koneksi->prepare("UPDATE buku_item SET status = 'tersedia' WHERE id = :id");
    $stmtUpdateItem->bindParam(':id', $item['id']);
    $stmtUpdateItem->execute();

    $koneksi->commit();

    $redirect = '../admin/pengembalian.php?success=kembali'
              . '&judul=' . urlencode($transaksi['judul'])
              . '&nama=' . urlencode($transaksi['nama_lengkap'])
              . '&kode_item=' . urlencode($transaksi['kode_item']);
    if ($isTerlambat) {
        $redirect .= '&terlambat=1&hari=' . $hariTerlambat;
    }

    header('Location: ' . $redirect);
    exit;

} catch (Exception $e) {
    $koneksi->rollBack();
    header('Location: ../admin/pengembalian.php?error=system');
    exit;
}