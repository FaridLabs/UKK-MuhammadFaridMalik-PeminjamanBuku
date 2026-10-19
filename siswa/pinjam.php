<?php
require '../config/cek_session_siswa.php';
require '../config/koneksi.php';

$anggotaId = $_SESSION['anggota_id'];
$batasPinjam = 3;

// Ambil semua buku yang sedang dipinjam
$stmtAktif = $koneksi->prepare("
    SELECT transaksi.*, buku.judul, buku.kode_buku, buku_item.kode_item,
           kategori.nama_kategori
    FROM transaksi
    JOIN buku_item ON transaksi.buku_item_id = buku_item.id
    JOIN buku ON buku_item.buku_id = buku.id
    LEFT JOIN kategori ON buku.kategori_id = kategori.id
    WHERE transaksi.anggota_id = :id AND transaksi.status = 'dipinjam'
    ORDER BY transaksi.tgl_kembali_rencana ASC
");
$stmtAktif->bindParam(':id', $anggotaId);
$stmtAktif->execute();
$listAktif = $stmtAktif->fetchAll();
$jumlahAktif = count($listAktif);

$today = date('Y-m-d');
$pageTitle = 'Buku Pinjaman Saya';
$currentPage = 'pinjam';
require 'includes/header.php';
?>
        <div class="page-header-wrap">
            <div>
                <h1>Buku Pinjaman Saya</h1>
                <p class="page-subtitle">Daftar buku yang sedang Anda bawa (<?= $jumlahAktif ?> dari <?= $batasPinjam ?> slot maksimal terpakai).</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="daftar_buku.php" class="btn btn-secondary">
                    Cari Buku Lain
                </a>
            </div>
        </div>

        <?php if ($jumlahAktif >= $batasPinjam): ?>
            <div class="alert alert-warning">
                Anda telah mencapai batas maksimal peminjaman (<?= $jumlahAktif ?>/<?= $batasPinjam ?> buku). Harap kembalikan salah satu buku terlebih dahulu sebelum meminjam buku baru.
            </div>
        <?php endif; ?>

        <?php
        $adaTerlambat = false;
        foreach ($listAktif as $row) {
            if ($today > $row['tgl_kembali_rencana']) { $adaTerlambat = true; break; }
        }
        if ($adaTerlambat): ?>
            <div class="alert alert-error">
                Perhatian: Terdapat buku yang telah melewati batas tanggal pengembalian. Harap segera serahkan buku ke petugas perpustakaan untuk menghindari sanksi keterlambatan.
            </div>
        <?php endif; ?>

        <div class="table-container">
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 48px;">No</th>
                        <th>Judul Buku</th>
                        <th>Kode Eksemplar Fisik</th>
                        <th>Tanggal Pinjam</th>
                        <th>Batas Waktu Pengembalian</th>
                        <th>Status Waktu</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($listAktif)): ?>
                        <tr>
                            <td colspan="6" style="padding: 48px 24px; text-align: center;">
                                <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--slate-100); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; color: var(--slate-400);">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                                        <path d="M6 6h10"/>
                                        <path d="M6 10h10"/>
                                    </svg>
                                </div>
                                <h3 style="margin: 0 0 6px;">Tidak Ada Buku yang Sedang Dipinjam</h3>
                                <p style="color: var(--slate-500); margin: 0 0 16px;">Anda memiliki 3 slot kosong untuk meminjam buku perpustakaan.</p>
                                <a href="daftar_buku.php" class="btn btn-primary" style="font-size: 13px;">
                                    Jelajahi Katalog Buku &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($listAktif as $i => $row):
                            $terlambat = $today > $row['tgl_kembali_rencana'];
                            $selisih = (int) round((strtotime($today) - strtotime($row['tgl_kembali_rencana'])) / 86400);
                        ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><strong style="color: var(--slate-900);"><?= htmlspecialchars($row['judul']) ?></strong></td>
                                <td><code><?= htmlspecialchars($row['kode_item']) ?></code></td>
                                <td><?= htmlspecialchars($row['tgl_pinjam']) ?></td>
                                <td><?= htmlspecialchars($row['tgl_kembali_rencana']) ?></td>
                                <td>
                                    <?php if ($terlambat): ?>
                                        <span class="badge-danger">Terlambat <?= $selisih ?> hari</span>
                                    <?php else: ?>
                                        <?php $sisaHari = (int) round((strtotime($row['tgl_kembali_rencana']) - strtotime($today)) / 86400); ?>
                                        <span class="badge-info">Sisa <?= $sisaHari ?> hari</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <div class="info-tip">
            <strong>Proses Pengembalian:</strong> Untuk mengembalikan buku, bawa buku ke meja sirkulasi perpustakaan. Petugas akan melakukan scan QR buku dan status di akun Anda akan otomatis terupdate.
        </div>
<?php require 'includes/footer.php'; ?>