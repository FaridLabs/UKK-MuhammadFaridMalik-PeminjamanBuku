<?php
session_start();
if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}
if (isset($_SESSION['anggota_id'])) {
    header('Location: siswa/dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Masuk Sistem - Pinjam.In Perpustakaan</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=4.0">
</head>
<body class="auth-page">
    <div class="auth-wrapper">
        <div style="margin-bottom: 16px;">
            <a href="index.php" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; font-weight: 600; color: var(--slate-600); text-decoration: none; padding: 6px 12px; background: rgba(255,255,255,0.7); border: 1px solid var(--slate-200); border-radius: 9999px; transition: var(--transition-fast);">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="19" y1="12" x2="5" y2="12"></line>
                    <polyline points="12 19 5 12 12 5"></polyline>
                </svg>
                Kembali ke Beranda
            </a>
        </div>

        <div class="auth-card">
            <div class="auth-header">
                <div class="auth-logo-badge">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                        <path d="M6 6h10"/>
                        <path d="M6 10h10"/>
                    </svg>
                </div>
                <h1>Pinjam.In</h1>
                <p>Masuk ke Akun Perpustakaan</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php
                        if ($_GET['error'] === 'invalid') echo 'Username atau password yang Anda masukkan salah.';
                        elseif ($_GET['error'] === 'empty') echo 'Semua field wajib diisi lengkap.';
                        else echo 'Terjadi kesalahan autentikasi.';
                    ?>
                </div>
            <?php endif; ?>

            <?php if (isset($_GET['success']) && $_GET['success'] === 'daftar'): ?>
                <div class="alert alert-success">Pendaftaran akun berhasil! Silakan login sekarang.</div>
            <?php endif; ?>

            <form action="config/proses_login.php" method="POST" autocomplete="off">
                <div class="form-group">
                    <label for="username">Username</label>
                    <input type="text" id="username" name="username" placeholder="Masukkan username..." required autofocus autocomplete="off">
                </div>

                <div class="form-group">
                    <label for="password">Password</label>
                    <input type="password" id="password" name="password" placeholder="Masukkan password..." required autocomplete="off">
                </div>

                <button type="submit">Masuk</button>
            </form>

            <div class="auth-footer-nav">
                Belum punya akun? <a href="daftar.php">Daftar sebagai Siswa</a>
            </div>
        </div>

        <div class="auth-system-info">
            &copy; <?= date('Y') ?> Perpustakaan Sekolah. Hak Cipta Dilindungi.
        </div>
    </div>
</body>
</html>
