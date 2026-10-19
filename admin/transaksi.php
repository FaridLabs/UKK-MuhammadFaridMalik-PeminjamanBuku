<?php
require '../config/cek_session_admin.php';
require '../config/koneksi.php';

$keyword      = trim($_GET['keyword'] ?? '');
$filterStatus = $_GET['status'] ?? '';
$tglDari      = trim($_GET['tgl_dari'] ?? '');
$tglSampai    = trim($_GET['tgl_sampai'] ?? '');
$today        = date('Y-m-d');

// Statistik ringkas transaksi
$totalSemua     = $koneksi->query("SELECT COUNT(*) FROM transaksi")->fetchColumn() ?? 0;
$totalDipinjam  = $koneksi->query("SELECT COUNT(*) FROM transaksi WHERE status = 'dipinjam'")->fetchColumn() ?? 0;
$totalKembali   = $koneksi->query("SELECT COUNT(*) FROM transaksi WHERE status = 'dikembalikan'")->fetchColumn() ?? 0;
$totalTerlambat = $koneksi->query("
    SELECT COUNT(*) FROM transaksi 
    WHERE status = 'terlambat' OR (status = 'dipinjam' AND tgl_kembali_rencana < CURDATE())
")->fetchColumn() ?? 0;

$sql = "
    SELECT transaksi.*, anggota.nama_lengkap, anggota.nis, anggota.kelas, buku.judul, buku_item.kode_item
    FROM transaksi
    LEFT JOIN anggota ON transaksi.anggota_id = anggota.id
    LEFT JOIN buku_item ON transaksi.buku_item_id = buku_item.id
    LEFT JOIN buku ON buku_item.buku_id = buku.id
    WHERE 1=1
";
$params = [];

if (!empty($keyword)) {
    $sql .= " AND (anggota.nama_lengkap LIKE :kw OR anggota.nis LIKE :kw OR buku.judul LIKE :kw OR buku_item.kode_item LIKE :kw OR transaksi.kode_transaksi LIKE :kw)";
    $params[':kw'] = "%$keyword%";
}

if (!empty($filterStatus)) {
    if ($filterStatus === 'terlambat') {
        $sql .= " AND (transaksi.status = 'terlambat' OR (transaksi.status = 'dipinjam' AND transaksi.tgl_kembali_rencana < CURDATE()))";
    } elseif ($filterStatus === 'dipinjam') {
        $sql .= " AND transaksi.status = 'dipinjam'";
    } elseif ($filterStatus === 'dikembalikan') {
        $sql .= " AND (transaksi.status = 'dikembalikan' OR (transaksi.status = 'terlambat' AND transaksi.tgl_kembali_aktual IS NOT NULL))";
    } else {
        $sql .= " AND transaksi.status = :status";
        $params[':status'] = $filterStatus;
    }
}

if (!empty($tglDari)) {
    $sql .= " AND transaksi.tgl_pinjam >= :tgl_dari";
    $params[':tgl_dari'] = $tglDari;
}
if (!empty($tglSampai)) {
    $sql .= " AND transaksi.tgl_pinjam <= :tgl_sampai";
    $params[':tgl_sampai'] = $tglSampai;
}

$sql .= " ORDER BY transaksi.tgl_pinjam DESC, transaksi.id DESC";

$stmt = $koneksi->prepare($sql);
foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}
$stmt->execute();
$data = $stmt->fetchAll();
$extraHead = '<style>
    @media print {
        .sidebar, .topbar, .filter-panel, .btn-print, .btn-cancel, .page-header-wrap a {
            display: none !important;
        }
        body, .app-layout, .main-wrapper {
            background: #ffffff !important;
            color: #000000 !important;
            margin: 0 !important;
            padding: 0 !important;
            display: block !important;
        }
        .content {
            max-width: 100% !important;
            margin: 0 !important;
            padding: 0 !important;
        }
        .stat-grid {
            display: none !important;
        }
        .table-container {
            box-shadow: none !important;
            border: 1px solid #000 !important;
        }
        .table-data th, .table-data td {
            border: 1px solid #ddd !important;
            color: #000 !important;
        }
    }
</style>';
$pageTitle = 'Riwayat Transaksi';
$currentPage = 'transaksi';
require 'includes/header.php';
?>
        <div class="page-header-wrap">
            <div>
                <h1>Riwayat &amp; Laporan Transaksi</h1>
                <p class="page-subtitle">Arsip seluruh aktivitas sirkulasi peminjaman dan pengembalian buku siswa.</p>
            </div>
            <button onclick="window.print()" class="btn-print" style="display: inline-flex; align-items: center; gap: 8px;">
                <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <polyline points="6 9 6 2 18 2 18 9"/>
                    <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2"/>
                    <rect x="6" y="14" width="12" height="8"/>
                </svg>
                Cetak Laporan
            </button>
        </div>

        <!-- STAT RINGKAS TRANSAKSI -->
        <div class="stat-grid" style="margin-bottom: 24px;">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </div>
                </div>
                <h3><?= $totalSemua ?></h3>
                <p>Total Seluruh Transaksi</p>
            </div>
            <div class="stat-card stat-primary">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                        </svg>
                    </div>
                </div>
                <h3><?= $totalDipinjam ?></h3>
                <p>Sedang Dipinjam Siswa</p>
            </div>
            <div class="stat-card stat-success">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/>
                            <polyline points="22 4 12 14.01 9 11.01"/>
                        </svg>
                    </div>
                </div>
                <h3><?= $totalKembali ?></h3>
                <p>Selesai Dikembalikan</p>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                    </div>
                </div>
                <h3><?= $totalTerlambat ?></h3>
                <p>Terlambat Dikembalikan</p>
            </div>
        </div>

        <!-- FILTER & PENCARIAN -->
        <div class="filter-panel" style="padding: 20px;">
            <form action="transaksi.php" method="GET" style="width: 100%;" autocomplete="off">
                <div style="display: flex; gap: 14px; flex-wrap: wrap; align-items: flex-end;">
                    <div style="flex: 2; min-width: 220px;">
                        <label style="font-size: 12.5px; font-weight: 700; color: var(--slate-600); margin-bottom: 4px; display: block;">Kata Kunci Pencarian</label>
                        <input type="text" name="keyword" placeholder="Cari nama siswa, NIS, judul buku, kode..." value="<?= htmlspecialchars($keyword) ?>" autocomplete="off">
                    </div>
                    <div style="flex: 1; min-width: 160px;">
                        <label style="font-size: 12.5px; font-weight: 700; color: var(--slate-600); margin-bottom: 4px; display: block;">Status Transaksi</label>
                        <select name="status">
                            <option value="">Semua Status</option>
                            <option value="dipinjam" <?= $filterStatus === 'dipinjam' ? 'selected' : '' ?>>Sedang Dipinjam</option>
                            <option value="dikembalikan" <?= $filterStatus === 'dikembalikan' ? 'selected' : '' ?>>Sudah Dikembalikan</option>
                            <option value="terlambat" <?= $filterStatus === 'terlambat' ? 'selected' : '' ?>>Terlambat</option>
                        </select>
                    </div>
                    <div style="flex: 1; min-width: 140px;">
                        <label style="font-size: 12.5px; font-weight: 700; color: var(--slate-600); margin-bottom: 4px; display: block;">Dari Tanggal</label>
                        <input type="date" name="tgl_dari" value="<?= htmlspecialchars($tglDari) ?>" autocomplete="off">
                    </div>
                    <div style="flex: 1; min-width: 140px;">
                        <label style="font-size: 12.5px; font-weight: 700; color: var(--slate-600); margin-bottom: 4px; display: block;">Sampai Tanggal</label>
                        <input type="date" name="tgl_sampai" value="<?= htmlspecialchars($tglSampai) ?>" autocomplete="off">
                    </div>
                    <div style="display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary" style="height: 42px;">Filter</button>
                        <?php if (!empty($keyword) || !empty($filterStatus) || !empty($tglDari) || !empty($tglSampai)): ?>
                            <a href="transaksi.php" class="btn-cancel" style="margin: 0; height: 42px;">Reset</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>

        <div style="margin-bottom: 14px; font-size: 13.5px; color: var(--slate-500); display: flex; justify-content: space-between; align-items: center;">
            <span>Ditemukan <strong><?= count($data) ?></strong> data transaksi sirkulasi</span>
        </div>

        <!-- TABEL DATA TRANSAKSI -->
        <div class="table-container">
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 44px;">No</th>
                        <th>Kode Transaksi</th>
                        <th>Tgl Pinjam</th>
                        <th>Siswa Peminjam</th>
                        <th>Kelas</th>
                        <th>Judul Buku &amp; Kode Eksemplar</th>
                        <th>Batas Waktu</th>
                        <th>Tgl Kembali</th>
                        <th>Keterangan</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr>
                            <td colspan="10">
                                Tidak ada catatan transaksi yang cocok dengan kriteria filter pencarian.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($data as $i => $row):
                            $isAktifDipinjam = $row['status'] === 'dipinjam';
                            $isTerlambat = ($isAktifDipinjam && $row['tgl_kembali_rencana'] < $today) || $row['status'] === 'terlambat';
                            
                            $keterangan = '-';
                            if ($isAktifDipinjam) {
                                if ($today > $row['tgl_kembali_rencana']) {
                                    $telatHari = (int) round((strtotime($today) - strtotime($row['tgl_kembali_rencana'])) / 86400);
                                    $keterangan = "<span style='color: var(--danger-solid); font-weight: 700;'>Lewat {$telatHari} hari</span>";
                                } else {
                                    $sisaHari = (int) round((strtotime($row['tgl_kembali_rencana']) - strtotime($today)) / 86400);
                                    $keterangan = "<span style='color: var(--brand-primary); font-weight: 600;'>Sisa {$sisaHari} hari</span>";
                                }
                            } else {
                                if ($row['tgl_kembali_aktual']) {
                                    if ($row['tgl_kembali_aktual'] > $row['tgl_kembali_rencana']) {
                                        $telatHari = (int) round((strtotime($row['tgl_kembali_aktual']) - strtotime($row['tgl_kembali_rencana'])) / 86400);
                                        $keterangan = "<span style='color: var(--warning-solid); font-weight: 600;'>Telat {$telatHari} hari</span>";
                                    } else {
                                        $keterangan = "<span style='color: var(--success-solid); font-weight: 600;'>Tepat waktu</span>";
                                    }
                                }
                            }

                            if ($isTerlambat) {
                                $badgeClass = 'badge-danger';
                                $labelStatus = 'Terlambat';
                            } elseif ($row['status'] === 'dikembalikan') {
                                $badgeClass = 'badge-success';
                                $labelStatus = 'Dikembalikan';
                            } else {
                                $badgeClass = 'badge-info';
                                $labelStatus = 'Dipinjam';
                            }
                        ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><code><?= htmlspecialchars($row['kode_transaksi']) ?></code></td>
                                <td><?= htmlspecialchars($row['tgl_pinjam']) ?></td>
                                <td>
                                    <strong style="color: var(--slate-900);"><?= htmlspecialchars($row['nama_lengkap'] ?? '[Anggota Dihapus]') ?></strong>
                                    <div style="font-size: 11px; color: var(--slate-500);">NIS: <?= htmlspecialchars($row['nis'] ?? '-') ?></div>
                                </td>
                                <td><?= htmlspecialchars($row['kelas'] ?? '-') ?></td>
                                <td>
                                    <?php if (!empty($row['judul'])): ?>
                                        <div style="font-weight: 600; color: var(--slate-900);"><?= htmlspecialchars($row['judul']) ?></div>
                                        <div><code><?= htmlspecialchars($row['kode_item'] ?? '-') ?></code></div>
                                    <?php else: ?>
                                        <div style="color: var(--slate-400); font-style: italic;">[Buku / Eksemplar Dihapus]</div>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars($row['tgl_kembali_rencana']) ?></td>
                                <td><?= $row['tgl_kembali_aktual'] ? htmlspecialchars($row['tgl_kembali_aktual']) : '-' ?></td>
                                <td><?= $keterangan ?></td>
                                <td><span class="<?= $badgeClass ?>"><?= $labelStatus ?></span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
<?php require 'includes/footer.php'; ?>