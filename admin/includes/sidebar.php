<?php
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <div class="brand-icon-box">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                    <path d="M6 6h10"/>
                    <path d="M6 10h10"/>
                </svg>
            </div>
            <h2>Pinjam<span class="brand-dot" style="color: #60a5fa;">.In</span> <span class="badge-role">Admin</span></h2>
        </a>
    </div>

    <div class="sidebar-nav">
        <!-- GROUP 1: OVERVIEW -->
        <div class="nav-group">
            <div class="nav-group-label">Ringkasan</div>
            <ul class="nav-group-list">
                <li>
                    <a href="dashboard.php" class="nav-link <?= $currentPage === 'dashboard' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="3" y="3" width="7" height="7"/>
                            <rect x="14" y="3" width="7" height="7"/>
                            <rect x="14" y="14" width="7" height="7"/>
                            <rect x="3" y="14" width="7" height="7"/>
                        </svg>
                        <span>Dashboard</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- GROUP 2: SIRKULASI -->
        <div class="nav-group">
            <div class="nav-group-label">Layanan Sirkulasi</div>
            <ul class="nav-group-list">
                <li>
                    <a href="peminjaman.php" class="nav-link <?= $currentPage === 'peminjaman' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14M5 12h14"/>
                        </svg>
                        <span>Peminjaman Baru</span>
                    </a>
                </li>
                <li>
                    <a href="pengembalian.php" class="nav-link <?= $currentPage === 'pengembalian' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <polyline points="9 14 4 9 9 4"/>
                            <path d="M20 20v-7a4 4 0 0 0-4-4H4"/>
                        </svg>
                        <span>Pengembalian Buku</span>
                    </a>
                </li>
                <li>
                    <a href="transaksi.php" class="nav-link <?= $currentPage === 'transaksi' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                        <span>Riwayat Transaksi</span>
                    </a>
                </li>
            </ul>
        </div>

        <!-- GROUP 3: KOLEKSI & ANGGOTA -->
        <div class="nav-group">
            <div class="nav-group-label">Koleksi &amp; Anggota</div>
            <ul class="nav-group-list">
                <li>
                    <a href="buku.php" class="nav-link <?= in_array($currentPage, ['buku', 'buku_item']) ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                            <path d="M6 6h10"/>
                            <path d="M6 10h10"/>
                        </svg>
                        <span>Data Buku</span>
                    </a>
                </li>
                <li>
                    <a href="kategori.php" class="nav-link <?= $currentPage === 'kategori' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/>
                            <line x1="7" y1="7" x2="7.01" y2="7"/>
                        </svg>
                        <span>Kategori Buku</span>
                    </a>
                </li>
                <li>
                    <a href="anggota.php" class="nav-link <?= $currentPage === 'anggota' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                        <span>Kelola Anggota</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="user-profile-badge">
            <div class="user-avatar">
                <?= strtoupper(substr($_SESSION['admin_nama'] ?? 'A', 0, 1)) ?>
            </div>
            <div class="user-meta">
                <div class="user-meta-name"><?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Administrator') ?></div>
                <div class="user-meta-role">Petugas Perpustakaan</div>
            </div>
        </div>
        <a href="../logout.php" class="btn-sidebar-logout" title="Keluar dari Sistem" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </a>
    </div>
</aside>
