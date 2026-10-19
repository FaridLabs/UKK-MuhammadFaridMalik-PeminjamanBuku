<?php
require '../config/cek_session_siswa.php';
require '../config/koneksi.php';

$anggotaId = $_SESSION['anggota_id'];

// Seluruh riwayat transaksi anggota ini
$stmt = $koneksi->prepare("
    SELECT transaksi.*, buku.judul, buku_item.kode_item
    FROM transaksi
    LEFT JOIN buku_item ON transaksi.buku_item_id = buku_item.id
    LEFT JOIN buku ON buku_item.buku_id = buku.id
    WHERE transaksi.anggota_id = :id
    ORDER BY transaksi.tgl_pinjam DESC, transaksi.id DESC
");
$stmt->bindParam(':id', $anggotaId);
$stmt->execute();
$riwayat = $stmt->fetchAll();
$pageTitle = 'Riwayat Peminjaman';
$currentPage = 'riwayat';
require 'includes/header.php';
?>
        <div class="page-header-wrap">
            <div>
                <h1>Riwayat Peminjaman Buku</h1>
                <p class="page-subtitle">Daftar seluruh buku yang pernah dan sedang Anda pinjam di perpustakaan sekolah.</p>
            </div>
        </div>

        <div class="table-container">
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 48px;">No</th>
                        <th>Judul Buku</th>
                        <th>Kode Eksemplar</th>
                        <th>Tanggal Pinjam</th>
                        <th>Batas Pengembalian</th>
                        <th>Tanggal Dikembalikan</th>
                        <th>Status Sirkulasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($riwayat)): ?>
                        <tr>
                            <td colspan="7" style="padding: 48px 24px; text-align: center;">
                                <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--slate-100); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; color: var(--slate-400);">
                                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                                        <path d="M6 6h10"/>
                                        <path d="M6 10h10"/>
                                    </svg>
                                </div>
                                <h3 style="margin: 0 0 6px;">Belum Ada Riwayat Peminjaman</h3>
                                <p style="color: var(--slate-500); margin: 0 0 16px;">Setiap kali Anda meminjam dan mengembalikan buku, riwayatnya akan tersimpan rapi di sini.</p>
                                <a href="daftar_buku.php" class="btn btn-primary" style="font-size: 13px;">
                                    Mulai Pinjam Buku Pertama &rarr;
                                </a>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($riwayat as $i => $row):
                            $isTerlambat = $row['status'] === 'dipinjam' && $row['tgl_kembali_rencana'] < date('Y-m-d');
                            $labelStatus = $isTerlambat ? 'Terlambat' : ucfirst($row['status']);
                            $badgeClass  = $isTerlambat ? 'badge-danger'
                                         : ($row['status'] === 'dikembalikan' ? 'badge-success'
                                         : ($row['status'] === 'terlambat'    ? 'badge-warning' : 'badge-info'));
                        ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <?php if (!empty($row['judul'])): ?>
                                        <strong style="color: var(--slate-900);"><?= htmlspecialchars($row['judul']) ?></strong>
                                    <?php else: ?>
                                        <span style="color: var(--slate-400); font-style: italic;">[Buku Dihapus]</span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?= !empty($row['kode_item']) ? htmlspecialchars($row['kode_item']) : '-' ?></code></td>
                                <td><?= htmlspecialchars($row['tgl_pinjam']) ?></td>
                                <td><?= htmlspecialchars($row['tgl_kembali_rencana']) ?></td>
                                <td><?= $row['tgl_kembali_aktual'] ? htmlspecialchars($row['tgl_kembali_aktual']) : '<span style="color: var(--slate-400);">-</span>' ?></td>
                                <td>
                                    <span class="<?= $badgeClass ?>"><?= $labelStatus ?></span>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
<?php require 'includes/footer.php'; ?>