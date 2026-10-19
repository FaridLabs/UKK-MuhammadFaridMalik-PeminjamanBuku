<?php
// siswa/includes/sidebar.php
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');

$namaSiswa = $_SESSION['anggota_nama'] ?? 'Siswa Perpustakaan';
$initial = strtoupper(substr($namaSiswa, 0, 1));
$kelasSiswa = $_SESSION['anggota_kelas'] ?? '-';

// Hitung buku dipinjam untuk badge menu
$badgeDipinjam = 0;
if (isset($koneksi) && isset($_SESSION['anggota_id'])) {
    $stmtBadge = $koneksi->prepare("SELECT COUNT(*) FROM transaksi WHERE anggota_id = :id AND status = 'dipinjam'");
    $stmtBadge->execute([':id' => $_SESSION['anggota_id']]);
    $badgeDipinjam = (int)$stmtBadge->fetchColumn();
}
?>
<aside class="sidebar" id="sidebar">
    <div class="sidebar-header">
        <a href="dashboard.php" class="sidebar-brand">
            <div class="brand-icon-box" style="background: linear-gradient(135deg, #059669 0%, #10b981 100%);">
                <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                    <path d="M6 6h10"/>
                    <path d="M6 10h10"/>
                </svg>
            </div>
            <h2>Pinjam<span class="brand-dot" style="color: #34d399;">.In</span> <span class="badge-role" style="background: rgba(16, 185, 129, 0.2); color: #34d399; border-color: rgba(52, 211, 153, 0.25);">Siswa</span></h2>
        </a>
    </div>

    <div class="sidebar-nav">
        <!-- GROUP 1: LAYANAN SISWA -->
        <div class="nav-group">
            <div class="nav-group-label">Menu Utama</div>
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
                <li>
                    <a href="daftar_buku.php" class="nav-link <?= $currentPage === 'daftar_buku' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                        <span>Katalog Buku</span>
                    </a>
                </li>
                <li>
                    <a href="pinjam.php" class="nav-link <?= $currentPage === 'pinjam' ? 'active' : '' ?>" style="justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M19 21l-7-5-7 5V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2z"/>
                            </svg>
                            <span>Buku Saya</span>
                        </div>
                        <?php if ($badgeDipinjam > 0): ?>
                            <span style="background: var(--brand-primary); color: #fff; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 9999px;">
                                <?= $badgeDipinjam ?>
                            </span>
                        <?php endif; ?>
                    </a>
                </li>
                <li>
                    <a href="riwayat.php" class="nav-link <?= $currentPage === 'riwayat' ? 'active' : '' ?>">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <polyline points="12 6 12 12 16 14"/>
                        </svg>
                        <span>Riwayat Pinjam</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>

    <div class="sidebar-footer">
        <div class="user-profile-badge">
            <div class="user-avatar" style="background: #059669;">
                <?= $initial ?>
            </div>
            <div class="user-meta">
                <div class="user-meta-name"><?= htmlspecialchars($namaSiswa) ?></div>
                <div class="user-meta-role">Kelas: <?= htmlspecialchars($kelasSiswa) ?></div>
            </div>
        </div>
        <a href="../logout.php" class="btn-sidebar-logout" title="Keluar dari Akun" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/>
                <polyline points="16 17 21 12 16 7"/>
                <line x1="21" y1="12" x2="9" y2="12"/>
            </svg>
        </a>
    </div>
</aside>
