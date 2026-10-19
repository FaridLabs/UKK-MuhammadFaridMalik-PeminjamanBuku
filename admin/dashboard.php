<?php
require '../config/cek_session_admin.php';
require '../config/koneksi.php';

$pageTitle = 'Dashboard';
$currentPage = 'dashboard';

// 1. Statistik ringkas kartu
$totalBuku      = $koneksi->query("SELECT COUNT(*) AS total FROM buku_item")->fetch()['total'] ?? 0;
$totalTersedia  = $koneksi->query("SELECT COUNT(*) AS total FROM buku_item WHERE status = 'tersedia'")->fetch()['total'] ?? 0;
$totalJudul     = $koneksi->query("SELECT COUNT(*) AS total FROM buku")->fetch()['total'] ?? 0;
$totalAnggota   = $koneksi->query("SELECT COUNT(*) AS total FROM anggota")->fetch()['total'] ?? 0;
$totalDipinjam  = $koneksi->query("SELECT COUNT(*) AS total FROM transaksi WHERE status = 'dipinjam'")->fetch()['total'] ?? 0;
$totalTerlambat = $koneksi->query("
    SELECT COUNT(*) AS total FROM transaksi 
    WHERE status = 'dipinjam' AND tgl_kembali_rencana < CURDATE()
")->fetch()['total'] ?? 0;

// 2. Data Grafik 1: Tren Sirkulasi 7 Hari Terakhir
$chartDates   = [];
$chartPinjam  = [];
$chartKembali = [];

for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $chartDates[] = date('d M', strtotime($d));

    $stmtP = $koneksi->prepare("SELECT COUNT(*) AS total FROM transaksi WHERE tgl_pinjam = :d");
    $stmtP->bindParam(':d', $d);
    $stmtP->execute();
    $chartPinjam[] = (int) ($stmtP->fetch()['total'] ?? 0);

    $stmtK = $koneksi->prepare("SELECT COUNT(*) AS total FROM transaksi WHERE tgl_kembali_aktual = :d");
    $stmtK->bindParam(':d', $d);
    $stmtK->execute();
    $chartKembali[] = (int) ($stmtK->fetch()['total'] ?? 0);
}

// 3. Data Grafik 2: Distribusi Kategori Buku
$kategoriStats = $koneksi->query("
    SELECT k.nama_kategori, COUNT(b.id) AS total_buku
    FROM kategori k
    LEFT JOIN buku b ON b.kategori_id = k.id
    GROUP BY k.id
    ORDER BY total_buku DESC
")->fetchAll();

$catLabels = [];
$catCounts = [];
foreach ($kategoriStats as $ks) {
    $catLabels[] = $ks['nama_kategori'];
    $catCounts[] = (int) $ks['total_buku'];
}

// 4. 5 Transaksi terbaru untuk ringkasan aktivitas
$recent = $koneksi->query("
    SELECT transaksi.*, anggota.nama_lengkap, anggota.nis, buku.judul, buku_item.kode_item
    FROM transaksi
    LEFT JOIN anggota ON transaksi.anggota_id = anggota.id
    LEFT JOIN buku_item ON transaksi.buku_item_id = buku_item.id
    LEFT JOIN buku ON buku_item.buku_id = buku.id
    ORDER BY transaksi.id DESC
    LIMIT 5
")->fetchAll();

$today = date('Y-m-d');

// Extra head: Load Chart.js CDN
$extraHead = '<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>';

// Extra script: Initialize Charts
ob_start();
?>
<script>
document.addEventListener('DOMContentLoaded', function () {
    // 1. Chart Tren Sirkulasi (Line Chart)
    const ctxTrend = document.getElementById('chartTrend').getContext('2d');
    
    // Gradient Pinjam
    const gradPinjam = ctxTrend.createLinearGradient(0, 0, 0, 240);
    gradPinjam.addColorStop(0, 'rgba(37, 99, 235, 0.2)');
    gradPinjam.addColorStop(1, 'rgba(37, 99, 235, 0.0)');

    // Gradient Kembali
    const gradKembali = ctxTrend.createLinearGradient(0, 0, 0, 240);
    gradKembali.addColorStop(0, 'rgba(16, 185, 129, 0.2)');
    gradKembali.addColorStop(1, 'rgba(16, 185, 129, 0.0)');

    new Chart(ctxTrend, {
        type: 'line',
        data: {
            labels: <?= json_encode($chartDates) ?>,
            datasets: [
                {
                    label: 'Peminjaman',
                    data: <?= json_encode($chartPinjam) ?>,
                    borderColor: '#2563eb',
                    backgroundColor: gradPinjam,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#2563eb',
                    pointRadius: 3,
                    pointHoverRadius: 6
                },
                {
                    label: 'Pengembalian',
                    data: <?= json_encode($chartKembali) ?>,
                    borderColor: '#059669',
                    backgroundColor: gradKembali,
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.35,
                    pointBackgroundColor: '#059669',
                    pointRadius: 3,
                    pointHoverRadius: 6
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            interaction: {
                mode: 'index',
                intersect: false
            },
            plugins: {
                legend: {
                    position: 'top',
                    labels: {
                        boxWidth: 12,
                        boxHeight: 12,
                        usePointStyle: true,
                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: '600' },
                        color: '#475569'
                    }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: '700' },
                    bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                    cornerRadius: 8
                }
            },
            scales: {
                x: {
                    grid: { display: false },
                    ticks: { font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 }, color: '#64748b' }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        stepSize: 1,
                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 },
                        color: '#64748b'
                    },
                    grid: { color: 'rgba(226, 232, 240, 0.8)' }
                }
            }
        }
    });

    // 2. Chart Distribusi Kategori (Doughnut Chart)
    const ctxCat = document.getElementById('chartCategory').getContext('2d');
    new Chart(ctxCat, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($catLabels) ?>,
            datasets: [{
                data: <?= json_encode($catCounts) ?>,
                backgroundColor: [
                    '#2563eb',
                    '#10b981',
                    '#f59e0b',
                    '#6366f1',
                    '#ec4899',
                    '#06b6d4',
                    '#8b5cf6'
                ],
                borderWidth: 2,
                borderColor: '#ffffff',
                hoverOffset: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'bottom',
                    labels: {
                        boxWidth: 10,
                        boxHeight: 10,
                        usePointStyle: true,
                        font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '500' },
                        color: '#475569',
                        padding: 14
                    }
                },
                tooltip: {
                    backgroundColor: '#0f172a',
                    padding: 10,
                    titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: '700' },
                    bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                    cornerRadius: 8
                }
            },
            cutout: '68%'
        }
    });
});
</script>
<?php
$extraScripts = ob_get_clean();

require 'includes/header.php';
?>

<div class="page-header-wrap">
    <div>
        <h1>Dashboard Utama</h1>
        <p class="page-subtitle">Selamat datang, <?= htmlspecialchars($_SESSION['admin_nama']) ?>. Ringkasan operasional dan sirkulasi buku hari ini.</p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="peminjaman.php" class="btn btn-primary" style="display: inline-flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="12" y1="5" x2="12" y2="19"></line>
                <line x1="5" y1="12" x2="19" y2="12"></line>
            </svg>
            Peminjaman Baru
        </a>
        <a href="pengembalian.php" class="btn btn-secondary" style="display: inline-flex; align-items: center; gap: 8px;">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 14 4 9 9 4"></polyline>
                <path d="M20 20v-7a4 4 0 0 0-4-4H4"></path>
            </svg>
            Scan Pengembalian
        </a>
    </div>
</div>

<!-- STAT CARDS -->
<div class="stat-grid">
    <div class="stat-card stat-primary">
        <div class="stat-card-header">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                    <path d="M6 6h10"/>
                    <path d="M6 10h10"/>
                </svg>
            </div>
        </div>
        <h3><?= $totalJudul ?></h3>
        <p>Judul Buku Terdaftar</p>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
                </svg>
            </div>
        </div>
        <h3><?= $totalBuku ?></h3>
        <p>Total Eksemplar Fisik</p>
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
        <h3><?= $totalTersedia ?></h3>
        <p>Eksemplar Tersedia</p>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                    <circle cx="9" cy="7" r="4"/>
                    <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                    <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                </svg>
            </div>
        </div>
        <h3><?= $totalAnggota ?></h3>
        <p>Anggota Terdaftar</p>
    </div>

    <div class="stat-card">
        <div class="stat-card-header">
            <div class="stat-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                    <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                </svg>
            </div>
        </div>
        <h3><?= $totalDipinjam ?></h3>
        <p>Buku Sedang Dipinjam</p>
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

<!-- CHARTS SECTION -->
<div class="chart-grid">
    <!-- Chart 1: Tren Sirkulasi -->
    <div class="chart-card">
        <div class="chart-card-header">
            <div>
                <h3>Tren Sirkulasi 7 Hari Terakhir</h3>
                <p>Perbandingan volume peminjaman dan pengembalian harian.</p>
            </div>
        </div>
        <div class="chart-container-wrap">
            <canvas id="chartTrend"></canvas>
        </div>
    </div>

    <!-- Chart 2: Komposisi Kategori -->
    <div class="chart-card">
        <div class="chart-card-header">
            <div>
                <h3>Distribusi Kategori Koleksi</h3>
                <p>Persentase judul buku berdasarkan kategori.</p>
            </div>
        </div>
        <div class="chart-container-wrap">
            <canvas id="chartCategory"></canvas>
        </div>
    </div>
</div>

<!-- RECENT ACTIVITY TABLE -->
<div class="card" style="padding: 0; overflow: hidden;">
    <div style="padding: 20px 24px; border-bottom: 1px solid var(--slate-200); display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h3 style="margin: 0;">Aktivitas Transaksi Terbaru</h3>
            <p style="margin: 0; font-size: 13px; color: var(--slate-500);">5 riwayat sirkulasi buku terakhir di perpustakaan.</p>
        </div>
        <a href="transaksi.php" style="font-size: 13px; color: var(--brand-primary); font-weight: 600;">Lihat Semua &rarr;</a>
    </div>

    <div class="table-container" style="border: none; box-shadow: none; margin-bottom: 0;">
        <table class="table-data">
            <thead>
                <tr>
                    <th>Kode Transaksi</th>
                    <th>Peminjam</th>
                    <th>Buku</th>
                    <th>Tgl Pinjam</th>
                    <th>Batas Waktu</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($recent)): ?>
                    <tr><td colspan="6" style="text-align: center; color: var(--slate-400); padding: 32px 16px;">Belum ada transaksi tercatat.</td></tr>
                <?php else: ?>
                    <?php foreach ($recent as $r):
                        $isTerlambat = $r['status'] === 'dipinjam' && $today > $r['tgl_kembali_rencana'];
                        $badgeClass = $isTerlambat ? 'badge-warning' : ($r['status'] === 'dikembalikan' ? 'badge-success' : 'badge-info');
                        $statusLabel = $isTerlambat ? 'Terlambat' : ucfirst($r['status']);
                    ?>
                        <tr>
                            <td><code><?= htmlspecialchars($r['kode_transaksi']) ?></code></td>
                            <td>
                                <strong><?= htmlspecialchars($r['nama_lengkap'] ?? 'Siswa') ?></strong>
                                <div style="font-size: 12px; color: var(--slate-500);"><?= htmlspecialchars($r['nis'] ?? '') ?></div>
                            </td>
                            <td>
                                <div><?= htmlspecialchars($r['judul'] ?? 'Buku') ?></div>
                                <code><?= htmlspecialchars($r['kode_item'] ?? '') ?></code>
                            </td>
                            <td><?= htmlspecialchars($r['tgl_pinjam']) ?></td>
                            <td><?= htmlspecialchars($r['tgl_kembali_rencana']) ?></td>
                            <td><span class="<?= $badgeClass ?>"><?= $statusLabel ?></span></td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<?php
require 'includes/footer.php';
?>