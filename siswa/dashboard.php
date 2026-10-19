<?php
require '../config/cek_session_siswa.php';
require '../config/koneksi.php';

$anggotaId = $_SESSION['anggota_id'];

// Statistik ringkas untuk siswa
$sedangDipinjam = $koneksi->prepare("
    SELECT COUNT(*) AS total FROM transaksi 
    WHERE anggota_id = :id AND status = 'dipinjam'
");
$sedangDipinjam->bindParam(':id', $anggotaId);
$sedangDipinjam->execute();
$totalDipinjam = $sedangDipinjam->fetch()['total'] ?? 0;

$riwayatTotal = $koneksi->prepare("
    SELECT COUNT(*) AS total FROM transaksi WHERE anggota_id = :id
");
$riwayatTotal->bindParam(':id', $anggotaId);
$riwayatTotal->execute();
$totalRiwayat = $riwayatTotal->fetch()['total'] ?? 0;

$totalBukuTersedia = $koneksi->query("SELECT COUNT(*) AS total FROM buku_item WHERE status = 'tersedia'")->fetch()['total'] ?? 0;

// Ambil buku yang sedang dipinjam saat ini
$stmtAktif = $koneksi->prepare("
    SELECT transaksi.*, buku.judul, buku_item.kode_item
    FROM transaksi
    JOIN buku_item ON transaksi.buku_item_id = buku_item.id
    JOIN buku ON buku_item.buku_id = buku.id
    WHERE transaksi.anggota_id = :id AND transaksi.status = 'dipinjam'
    ORDER BY transaksi.tgl_kembali_rencana ASC
");
$stmtAktif->bindParam(':id', $anggotaId);
$stmtAktif->execute();
$listAktif = $stmtAktif->fetchAll();

$today = date('Y-m-d');
$pageTitle = 'Dashboard Siswa';
$currentPage = 'dashboard';
require 'includes/header.php';
?>
        <div class="page-header-wrap">
            <div>
                <h1>Halo, <?= htmlspecialchars($_SESSION['anggota_nama']) ?></h1>
                <p class="page-subtitle">Kelas <?= htmlspecialchars($_SESSION['anggota_kelas']) ?> &bull; Selamat datang di katalog perpustakaan sekolah digital.</p>
            </div>
            <div style="display: flex; gap: 10px;">
                <a href="daftar_buku.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="11" cy="11" r="8"/>
                        <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                    </svg>
                    Jelajahi Katalog Buku
                </a>
            </div>
        </div>

        <!-- STAT GRID SISWA -->
        <div class="stat-grid">
            <div class="stat-card <?= $totalDipinjam > 0 ? 'stat-primary' : '' ?>">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                        </svg>
                    </div>
                </div>
                <h3><?= $totalDipinjam ?> <span style="font-size: 16px; font-weight: 500; color: var(--slate-400);">/ 3 slot</span></h3>
                <p>Buku Sedang Dipinjam</p>
            </div>

            <div class="stat-card stat-success">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                            <path d="M6 6h10"/>
                            <path d="M6 10h10"/>
                        </svg>
                    </div>
                </div>
                <h3><?= $totalRiwayat ?></h3>
                <p>Total Riwayat Peminjaman</p>
            </div>

            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
                        </svg>
                    </div>
                </div>
                <h3><?= $totalBukuTersedia ?></h3>
                <p>Eksemplar Buku Tersedia di Rak</p>
            </div>
        </div>

        <!-- BUKU AKTIF DIPINJAM PREVIEW -->
        <div class="card" style="padding: 0; overflow: hidden;">
            <div style="padding: 20px 24px; border-bottom: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center;">
                <div>
                    <h3 style="margin: 0;">Buku yang Sedang Kamu Pinjam</h3>
                    <p style="margin: 0; font-size: 13px; color: var(--slate-500);">Pastikan mengembalikan buku sebelum tanggal batas waktu berakhir.</p>
                </div>
                <a href="pinjam.php" style="font-size: 13px; color: var(--brand-primary); font-weight: 600;">Lihat Detail &rarr;</a>
            </div>

            <div class="table-container" style="border: none; box-shadow: none; margin-bottom: 0;">
                <table class="table-data">
                    <thead>
                        <tr>
                            <th>No</th>
                            <th>Judul Buku</th>
                            <th>Kode Item</th>
                            <th>Tanggal Pinjam</th>
                            <th>Batas Pengembalian</th>
                            <th>Status Batas Waktu</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($listAktif)): ?>
                            <tr>
                                <td colspan="6" style="padding: 36px 20px;">
                                    Kamu sedang tidak meminjam buku saat ini.<br>
                                    <a href="daftar_buku.php" style="color: var(--brand-primary); font-weight: 600; text-decoration: underline; margin-top: 6px; display: inline-block;">
                                        Cari buku menarik di katalog sekarang &rarr;
                                    </a>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($listAktif as $i => $row):
                                $terlambat = $today > $row['tgl_kembali_rencana'];
                                $selisih = (int) round((strtotime($today) - strtotime($row['tgl_kembali_rencana'])) / 86400);
                                $sisaHari = (int) round((strtotime($row['tgl_kembali_rencana']) - strtotime($today)) / 86400);
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
                                            <span class="badge-info">Sisa <?= $sisaHari ?> hari</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php require 'includes/footer.php'; ?>