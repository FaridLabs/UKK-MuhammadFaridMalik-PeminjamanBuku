<?php
session_start();
if (isset($_SESSION['anggota_id']) || isset($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pendaftaran Siswa - Pinjam.In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css?v=4.0">
</head>
<body class="auth-page">
    <div class="auth-wrapper wide">
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
                <h1>Pendaftaran Anggota Siswa</h1>
                <p>Isi identitas diri Anda untuk membuat akun perpustakaan baru</p>
            </div>

            <?php if (isset($_GET['error'])): ?>
                <div class="alert alert-error">
                    <?php
                        if ($_GET['error'] === 'empty') echo 'Semua field wajib diisi lengkap.';
                        if ($_GET['error'] === 'nis_ada') echo 'NIS ini sudah terdaftar sebelumnya.';
                        if ($_GET['error'] === 'username_ada') echo 'Username telah digunakan, silakan pilih username lain.';
                        if ($_GET['error'] === 'password_beda') echo 'Konfirmasi password tidak cocok dengan password.';
                    ?>
                </div>
            <?php endif; ?>

            <form action="config/proses_daftar.php" method="POST" autocomplete="off">
                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="nis">NIS (Nomor Induk Siswa)</label>
                        <input type="text" id="nis" name="nis" placeholder="Contoh: 2425001" required autofocus autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="kelas">Kelas</label>
                        <input type="text" id="kelas" name="kelas" placeholder="Contoh: XII RPL 1" required autocomplete="off">
                    </div>
                </div>

                <div class="form-group">
                    <label for="nama_lengkap">Nama Lengkap</label>
                    <input type="text" id="nama_lengkap" name="nama_lengkap" placeholder="Masukkan nama lengkap siswa..." required autocomplete="off">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="no_hp">No. WhatsApp / HP</label>
                        <input type="text" id="no_hp" name="no_hp" placeholder="Contoh: 08123456789" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="username">Username</label>
                        <input type="text" id="username" name="username" placeholder="Pilih username unik..." required autocomplete="off">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label for="password">Password</label>
                        <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label for="konfirmasi_password">Konfirmasi Password</label>
                        <input type="password" id="konfirmasi_password" name="konfirmasi_password" placeholder="Ulangi password..." required autocomplete="off">
                    </div>
                </div>

                <button type="submit">Daftarkan Akun</button>
            </form>

            <div class="auth-footer-nav">
                Sudah memiliki akun? <a href="login.php">Masuk di sini</a>
            </div>
        </div>

        <div class="auth-system-info">
            &copy; <?= date('Y') ?> Perpustakaan Sekolah. Hak Cipta Dilindungi.
        </div>
    </div>
</body>
</html>