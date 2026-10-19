<?php
session_start();

// Jika sudah login, redirect langsung ke dashboard masing-masing
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}
if (isset($_SESSION['anggota_id'])) {
    header('Location: siswa/dashboard.php');
    exit;
}

// Teruskan query error atau success login/daftar langsung ke login.php agar alur autentikasi bawaan tetap 100% jalan
if (isset($_GET['error']) || (isset($_GET['success']) && $_GET['success'] === 'daftar')) {
    header('Location: login.php?' . $_SERVER['QUERY_STRING']);
    exit;
}

require_once 'config/koneksi.php';

// Ambil statistik real-time dari database
$totalJudul = $koneksi->query("SELECT COUNT(*) FROM buku")->fetchColumn() ?? 0;
$totalFisik = $koneksi->query("SELECT COUNT(*) FROM buku_item")->fetchColumn() ?? 0;
$totalTersedia = $koneksi->query("SELECT COUNT(*) FROM buku_item WHERE status = 'tersedia'")->fetchColumn() ?? 0;
$totalAnggota = $koneksi->query("SELECT COUNT(*) FROM anggota")->fetchColumn() ?? 0;

// Ambil koleksi buku terbaru untuk showcase
$koleksiBuku = $koneksi->query("
    SELECT buku.*, kategori.nama_kategori,
           (SELECT COUNT(*) FROM buku_item WHERE buku_item.buku_id = buku.id) AS total_eksemplar,
           (SELECT COUNT(*) FROM buku_item WHERE buku_item.buku_id = buku.id AND status = 'tersedia') AS total_tersedia
    FROM buku
    LEFT JOIN kategori ON buku.kategori_id = kategori.id
    ORDER BY buku.id DESC
    LIMIT 6
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pinjam.In - Sistem Perpustakaan Digital Cerdas</title>
    <meta name="description" content="Sistem Informasi dan Sirkulasi Perpustakaan Sekolah Digital Modern berbasis RFID dan QR Code.">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=4.0">
</head>
<body class="landing-body">

    <!-- NAVIGATION HEADER -->
    <header class="landing-header">
        <div class="landing-nav-container">
            <a href="index.php" class="landing-brand">
                <div class="landing-brand-logo">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                        <path d="M6 6h10"/>
                        <path d="M6 10h10"/>
                    </svg>
                </div>
                <div class="landing-brand-name">
                    Pinjam<span>.In</span>
                </div>
            </a>

            <nav class="landing-nav-menu">
                <a href="#hero" class="landing-nav-link">Beranda</a>
                <a href="#fitur" class="landing-nav-link">Keunggulan</a>
                <a href="#koleksi" class="landing-nav-link">Koleksi Buku</a>
                <a href="#alur" class="landing-nav-link">Alur Peminjaman</a>
            </nav>

            <div class="landing-nav-actions">
                <a href="daftar.php" class="landing-btn-outline">Daftar Siswa</a>
                <a href="login.php" class="landing-btn-primary">
                    Masuk
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>
        </div>
    </header>

    <!-- HERO SECTION -->
    <section class="landing-hero" id="hero">
        <div class="landing-hero-container">
            <div class="landing-badge">
                <span class="landing-badge-dot"></span>
                Sistem Perpustakaan Sekolah Modern &amp; Cerdas
            </div>

            <h1 class="landing-hero-title">
                Eksplorasi Ilmu Lebih Cepat dengan <span class="highlight">Perpustakaan Cerdas</span>
            </h1>

            <p class="landing-hero-subtitle">
                Platform terpadu sirkulasi perpustakaan sekolah digital dengan teknologi Kartu Pelajar RFID dan QR Code buku. Peminjaman instan, tertib, dan transparan untuk seluruh siswa.
            </p>

            <div class="landing-hero-actions">
                <a href="#koleksi" class="landing-hero-btn-lg landing-hero-btn-primary">
                    Jelajahi Koleksi Buku
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="12" y1="5" x2="12" y2="19"></line>
                        <polyline points="19 12 12 19 5 12"></polyline>
                    </svg>
                </a>
                <a href="login.php" class="landing-hero-btn-lg landing-hero-btn-secondary">
                    Masuk ke Akun
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                </a>
            </div>

            <!-- LIVE STATS -->
            <div class="landing-stats-row">
                <div class="landing-stat-box">
                    <div class="landing-stat-num"><?= (int)$totalJudul ?></div>
                    <div class="landing-stat-label">Judul Buku Terdaftar</div>
                </div>
                <div class="landing-stat-box">
                    <div class="landing-stat-num"><?= (int)$totalFisik ?></div>
                    <div class="landing-stat-label">Total Eksemplar Fisik</div>
                </div>
                <div class="landing-stat-box">
                    <div class="landing-stat-num" style="color: var(--success-solid);"><?= (int)$totalTersedia ?></div>
                    <div class="landing-stat-label">Eksemplar Siap Dipinjam</div>
                </div>
                <div class="landing-stat-box">
                    <div class="landing-stat-num" style="color: var(--brand-primary);"><?= (int)$totalAnggota ?></div>
                    <div class="landing-stat-label">Anggota Siswa Aktif</div>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION FITUR -->
    <section class="landing-section" id="fitur">
        <div class="landing-container">
            <div class="landing-header-group">
                <div class="landing-header-tag">Fitur &amp; Keunggulan</div>
                <h2 class="landing-section-title">Teknologi Modern untuk Literasi Sekolah</h2>
                <p class="landing-section-desc">
                    Dirancang untuk memangkas waktu antre sirkulasi buku dan mempermudah pengelolaan data perpustakaan secara presisi.
                </p>
            </div>

            <div class="landing-features-grid">
                <div class="landing-feature-card">
                    <div class="landing-feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="20" height="14" x="2" y="5" rx="2"/>
                            <line x1="2" x2="22" y1="10" y2="10"/>
                        </svg>
                    </div>
                    <h3 class="landing-feature-title">Kartu Pelajar RFID Cerdas</h3>
                    <p class="landing-feature-text">
                        Autentikasi peminjaman kilat cukup dengan menempelkan kartu siswa ke reader RFID tanpa perlu mengetik ulang NIS secara manual.
                    </p>
                </div>

                <div class="landing-feature-card">
                    <div class="landing-feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect width="5" height="5" x="3" y="3" rx="1"/>
                            <rect width="5" height="5" x="16" y="3" rx="1"/>
                            <rect width="5" height="5" x="3" y="16" rx="1"/>
                            <path d="M21 16h-3a2 2 0 0 0-2 2v3"/>
                            <path d="M21 21v.01"/>
                            <path d="M12 7v3a2 2 0 0 1-2 2H7"/>
                            <path d="M3 12h.01"/>
                            <path d="M12 3h.01"/>
                            <path d="M12 16v.01"/>
                            <path d="M16 12h1"/>
                            <path d="M21 12v.01"/>
                            <path d="M12 21v-1"/>
                        </svg>
                    </div>
                    <h3 class="landing-feature-title">QR Code Unik per Eksemplar</h3>
                    <p class="landing-feature-text">
                        Setiap fisik buku memiliki kode identitas unik tersendiri untuk mencegah kesalahan pendataan dan mempermudah pelacakan inventaris.
                    </p>
                </div>

                <div class="landing-feature-card">
                    <div class="landing-feature-icon">
                        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M2 3h6a4 4 0 0 1 4 4v14a3 3 0 0 0-3-3H2z"/>
                            <path d="M22 3h-6a4 4 0 0 0-4 4v14a3 3 0 0 1 3-3h7z"/>
                        </svg>
                    </div>
                    <h3 class="landing-feature-title">Portal Akses Siswa &amp; Admin</h3>
                    <p class="landing-feature-text">
                        Siswa dapat memantau tanggal pengembalian dan riwayat pinjaman kapan saja, sementara petugas perpustakaan memiliki kontrol sirkulasi penuh.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- SECTION KOLEKSI BUKU -->
    <section class="landing-section alt-bg" id="koleksi">
        <div class="landing-container">
            <div class="landing-header-group">
                <div class="landing-header-tag">Katalog Koleksi</div>
                <h2 class="landing-section-title">Koleksi Buku &amp; Referensi</h2>
                <p class="landing-section-desc">
                    Temukan bahan bacaan, modul pelajaran, dan karya sastra yang tersedia langsung di perpustakaan sekolah.
                </p>
            </div>

            <!-- SEARCH FILTER -->
            <div class="catalog-search-wrap">
                <svg class="catalog-search-icon" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <circle cx="11" cy="11" r="8"/>
                    <path d="m21 21-4.3-4.3"/>
                </svg>
                <input type="text" id="liveBookSearch" class="catalog-search-input" placeholder="Cari judul buku, pengarang, atau kategori..." autocomplete="off">
            </div>

            <!-- BOOK CARDS -->
            <div class="catalog-grid" id="catalogGrid">
                <?php if (empty($koleksiBuku)): ?>
                    <div style="grid-column: 1 / -1; text-align: center; padding: 40px; color: var(--slate-500);">
                        Belum ada koleksi buku yang terdaftar di perpustakaan.
                    </div>
                <?php else: ?>
                    <?php foreach ($koleksiBuku as $buku): ?>
                        <div class="catalog-card book-item" data-search="<?= strtolower(htmlspecialchars($buku['judul'] . ' ' . $buku['pengarang'] . ' ' . ($buku['nama_kategori'] ?? ''))) ?>">
                            <div>
                                <div class="catalog-card-header">
                                    <span class="catalog-card-tag"><?= htmlspecialchars($buku['nama_kategori'] ?? 'Umum') ?></span>
                                    <span class="catalog-card-code"><?= htmlspecialchars($buku['tahun_terbit'] ?? '-') ?></span>
                                </div>
                                <h4 class="catalog-book-title"><?= htmlspecialchars($buku['judul']) ?></h4>
                                <div class="catalog-book-author">Oleh <?= htmlspecialchars($buku['pengarang']) ?></div>
                            </div>
                            <div class="catalog-card-footer">
                                <div>
                                    Penerbit: <strong style="color: var(--slate-700);"><?= htmlspecialchars($buku['penerbit']) ?></strong>
                                </div>
                                <div>
                                    <?php if ($buku['total_tersedia'] > 0): ?>
                                        <span class="badge-success"><?= $buku['total_tersedia'] ?> Tersedia</span>
                                    <?php else: ?>
                                        <span class="badge-danger">Semua Dipinjam</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </section>

    <!-- SECTION ALUR PEMINJAMAN -->
    <section class="landing-section" id="alur">
        <div class="landing-container">
            <div class="landing-header-group">
                <div class="landing-header-tag">Cara Kerja</div>
                <h2 class="landing-section-title">Alur Peminjaman Cepat 3 Langkah</h2>
                <p class="landing-section-desc">
                    Proses sirkulasi tanpa formulir kertas. Lebih cepat, akurat, dan langsung tercatat di sistem.
                </p>
            </div>

            <div class="landing-steps-grid">
                <div class="landing-step-card">
                    <div class="landing-step-badge">1</div>
                    <h4 class="landing-step-title">Pilih Buku di Rak</h4>
                    <p class="landing-step-desc">
                        Cek ketersediaan buku di katalog perpustakaan, lalu ambil buku fisik yang Anda butuhkan dari rak perpustakaan sekolah.
                    </p>
                </div>

                <div class="landing-step-card">
                    <div class="landing-step-badge">2</div>
                    <h4 class="landing-step-title">Tap RFID &amp; Scan QR Buku</h4>
                    <p class="landing-step-desc">
                        Bawa buku ke meja sirkulasi. Tempelkan kartu siswa pada reader dan petugas akan memindai QR code pada eksemplar buku.
                    </p>
                </div>

                <div class="landing-step-card">
                    <div class="landing-step-badge">3</div>
                    <h4 class="landing-step-title">Selesai &amp; Pantau Jadwal</h4>
                    <p class="landing-step-desc">
                        Buku resmi dipinjam. Anda dapat memantau batas waktu pengembalian kapan saja melalui portal dashboard siswa masing-masing.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA BANNER -->
    <div class="landing-cta-banner">
        <div class="landing-cta-box">
            <h3 class="landing-cta-title">Tingkatkan Minat Baca dengan Pinjam.In</h3>
            <p class="landing-cta-desc">
                Gabung bersama ratusan siswa lainnya dan nikmati kemudahan akses literasi berkualitas di perpustakaan sekolah.
            </p>
            <div style="display: flex; gap: 12px; justify-content: center; flex-wrap: wrap;">
                <a href="daftar.php" class="landing-hero-btn-lg landing-hero-btn-primary" style="background: var(--brand-primary);">
                    Daftar Siswa Baru
                </a>
                <a href="login.php" class="landing-hero-btn-lg landing-hero-btn-secondary" style="background: rgba(255,255,255,0.15); color: #fff; border-color: rgba(255,255,255,0.25);">
                    Masuk ke Sistem &rarr;
                </a>
            </div>
        </div>
    </div>

    <!-- FOOTER -->
    <footer class="landing-footer">
        <div class="landing-footer-grid">
            <div>
                <a href="index.php" class="landing-brand">
                    <div class="landing-brand-logo">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                            <path d="M6 6h10"/>
                            <path d="M6 10h10"/>
                        </svg>
                    </div>
                    <div class="landing-brand-name">
                        Pinjam<span>.In</span>
                    </div>
                </a>
                <p class="landing-footer-brand-desc">
                    Sistem otomasi sirkulasi perpustakaan sekolah berbasis RFID dan QR Code untuk pengalaman membaca yang modern, praktis, dan akurat.
                </p>
            </div>

            <div>
                <h5 class="landing-footer-col-title">Navigasi</h5>
                <ul class="landing-footer-links">
                    <li><a href="#hero">Beranda</a></li>
                    <li><a href="#fitur">Keunggulan Sistem</a></li>
                    <li><a href="#koleksi">Katalog Buku</a></li>
                    <li><a href="#alur">Alur Peminjaman</a></li>
                </ul>
            </div>

            <div>
                <h5 class="landing-footer-col-title">Akses Akun</h5>
                <ul class="landing-footer-links">
                    <li><a href="login.php">Masuk Sistem</a></li>
                    <li><a href="daftar.php">Pendaftaran Siswa Baru</a></li>
                    <li><a href="login.php">Portal Petugas Admin</a></li>
                </ul>
            </div>
        </div>

        <div class="landing-footer-bottom">
            <div>
                &copy; <?= date('Y') ?> <strong>Pinjam.In Perpustakaan Sekolah</strong>. Hak Cipta Dilindungi.
            </div>
            <div style="font-size: 12px; color: var(--slate-400);">
                Versi Sistem 4.0 &bull; Fast &amp; Secure
            </div>
        </div>
    </footer>

    <!-- INTERACTIVE SEARCH SCRIPT -->
    <script>
        const searchInput = document.getElementById('liveBookSearch');
        const bookItems   = document.querySelectorAll('.book-item');

        if (searchInput) {
            searchInput.addEventListener('input', function() {
                const query = this.value.toLowerCase().trim();
                bookItems.forEach(item => {
                    const searchData = item.getAttribute('data-search') || '';
                    if (searchData.includes(query)) {
                        item.style.display = 'flex';
                    } else {
                        item.style.display = 'none';
                    }
                });
            });
        }
    </script>
</body>
</html>