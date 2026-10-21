<?php
$currentPage = $currentPage ?? basename($_SERVER['PHP_SELF'], '.php');
$namaAdmin   = $_SESSION['admin_nama'] ?? 'Administrator';

$menu = [
    'MENU UTAMA' => [
        ['dashboard',    'dashboard.php',    'home',    'Dashboard'],
        ['peminjaman',   'peminjaman.php',   'loan',    'Peminjaman Buku'],
        ['pengembalian', 'pengembalian.php', 'return',  'Pengembalian Buku'],
        ['transaksi',    'transaksi.php',    'history', 'Riwayat Transaksi'],
    ],
    'KOLEKSI & ANGGOTA' => [
        ['buku',     'buku.php',     'book',  'Data Buku'],
        ['kategori', 'kategori.php', 'tag',   'Kategori Buku'],
        ['anggota',  'anggota.php',  'users', 'Kelola Anggota'],
    ],
];
// Halaman turunan yang tetap menandai menu induknya
$alias = ['buku_item' => 'buku'];
$aktif = $alias[$currentPage] ?? $currentPage;
?>
<aside class="side" id="sidebar" aria-label="Menu admin">
    <a href="dashboard.php" class="side-brand" aria-label="Pinjam.In, dashboard">
        <svg viewBox="0 0 48 40" aria-hidden="true">
            <path d="M24 15C23 6 29 2 37 3C37 11 32 16 24 15Z" fill="#5FD39B"/>
            <path d="M3 13Q3 11 5.2 11.2Q14 12 24 17.5V36Q14 31 5.2 31Q3 31 3 29Z" fill="#E3F2EA"/>
            <path d="M45 13Q45 11 42.8 11.2Q34 12 24 17.5V36Q34 31 42.8 31Q45 31 45 29Z" fill="#B7DEC9"/>
        </svg>
        <span><b>Pinjam.In</b><small>Perpustakaan Digital Sekolah</small></span>
    </a>

    <div class="side-user">
        <div class="side-ava"><?= ic('user', 24) ?></div>
        <div class="side-who">
            <b><?= htmlspecialchars($namaAdmin) ?></b>
            <small>Administrator</small>
        </div>
        <span class="pill-on">Online</span>
    </div>

    <nav class="side-nav">
        <?php foreach ($menu as $judul => $items): ?>
            <div class="side-label"><?= htmlspecialchars($judul) ?></div>
            <?php foreach ($items as [$kode, $href, $ikon, $label]): ?>
                <a href="<?= $href ?>" class="<?= $aktif === $kode ? 'active' : '' ?>" <?= $aktif === $kode ? 'aria-current="page"' : '' ?>>
                    <?= ic($ikon) ?><span><?= $label ?></span>
                </a>
            <?php endforeach; ?>
        <?php endforeach; ?>
    </nav>

    <div class="side-foot">
        <a href="../logout.php" onclick="return confirm('Apakah Anda yakin ingin keluar?')">
            <?= ic('logout') ?><span>Logout</span>
        </a>
    </div>
</aside>