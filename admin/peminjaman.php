<?php
require '../config/cek_session_admin.php';
require '../config/koneksi.php';

// Reset → ganti anggota
if (isset($_GET['reset'])) {
    unset($_SESSION['admin_scan_anggota_id']);
    header('Location: peminjaman.php');
    exit;
}

$anggota      = null;
$listDipinjam = [];
$jumlahAktif  = 0;
$batasPinjam  = 3;

// Jika anggota sudah di-scan (ada di session)
if (isset($_SESSION['admin_scan_anggota_id'])) {
    $anggotaId = $_SESSION['admin_scan_anggota_id'];

    $stmtA = $koneksi->prepare("SELECT * FROM anggota WHERE id = :id LIMIT 1");
    $stmtA->bindParam(':id', $anggotaId);
    $stmtA->execute();
    $anggota = $stmtA->fetch();

    if (!$anggota) {
        unset($_SESSION['admin_scan_anggota_id']);
        header('Location: peminjaman.php?error=anggota_hilang');
        exit;
    }

    $stmtD = $koneksi->prepare("
        SELECT transaksi.*, buku.judul, buku_item.kode_item
        FROM transaksi
        JOIN buku_item ON transaksi.buku_item_id = buku_item.id
        JOIN buku ON buku_item.buku_id = buku.id
        WHERE transaksi.anggota_id = :id AND transaksi.status = 'dipinjam'
        ORDER BY transaksi.tgl_kembali_rencana ASC
    ");
    $stmtD->bindParam(':id', $anggotaId);
    $stmtD->execute();
    $listDipinjam = $stmtD->fetchAll();
    $jumlahAktif  = count($listDipinjam);
}

$today = date('Y-m-d');
$pageTitle = 'Peminjaman Buku';
$currentPage = 'peminjaman';
require 'includes/header.php';
?>
        <div class="page-header-wrap">
            <div>
                <h1>Peminjaman Buku</h1>
                <p class="page-subtitle">Alur peminjaman cepat berbasis RFID Kartu Anggota dan Barcode/QR Buku.</p>
            </div>
        </div>

        <!-- STEP INDICATOR -->
        <div class="step-indicator">
            <div class="step-box <?= $anggota ? 'done' : 'active' ?>">
                <span class="step-num"><?= $anggota ? '✓' : '1' ?></span>
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--slate-400);">Langkah 1</div>
                    <div>Scan Kartu Anggota / NIS</div>
                </div>
            </div>
            <span class="step-arrow">&rarr;</span>
            <div class="step-box <?= $anggota ? 'active' : '' ?>">
                <span class="step-num">2</span>
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--slate-400);">Langkah 2</div>
                    <div>Scan QR Buku Fisik</div>
                </div>
            </div>
        </div>

        <!-- NOTIFIKASI -->
        <?php if (isset($_GET['success']) && $_GET['success'] === 'pinjam'): ?>
            <div class="alert alert-success">
                Peminjaman berhasil dicatat! Buku "<strong><?= htmlspecialchars($_GET['judul'] ?? '') ?></strong>" berhasil dipinjamkan. Batas waktu pengembalian adalah 7 hari ke depan.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <?php 
            $judulError = htmlspecialchars($_GET['judul'] ?? 'Buku ini');
            $errMap = [
                'tidak_ditemukan'      => 'Kartu anggota tidak dikenali. Pastikan RFID atau NIS sudah terdaftar di data anggota.',
                'buku_tidak_ditemukan' => 'Kode QR/barcode buku tidak ditemukan di sistem.',
                'batas'                => 'Anggota telah mencapai batas maksimal peminjaman (3 buku). Kembalikan buku terlebih dahulu.',
                'sudah_pinjam_judul'   => '<strong>Cegah Dobel Scan:</strong> Siswa ini sudah meminjam 1 eksemplar buku "' . $judulError . '". Sistem otomatis menolak peminjaman ganda buku yang sama.',
                'stok_habis'           => 'Semua eksemplar fisik buku "' . $judulError . '" saat ini sedang dipinjam oleh siswa lain.',
                'sedang_dipinjam'      => 'Buku "' . $judulError . '" berstatus sedang dipinjam dan belum dikembalikan.',
                'status_tidak_valid'   => 'Status fisik buku tidak tersedia untuk dipinjam (rusak atau hilang).',
                'system'               => 'Terjadi kendala pada sistem. Silakan ulangi proses.',
                'anggota_hilang'       => 'Data anggota tidak ditemukan. Silakan scan ulang.',
                'empty'                => 'Input scan tidak boleh kosong.',
            ]; 
            ?>
            <div class="alert alert-error"><?= $errMap[$_GET['error']] ?? 'Terjadi kesalahan pada transaksi peminjaman.' ?></div>
        <?php endif; ?>

        <?php if (!$anggota): ?>
            <!-- STEP 1: SCAN KARTU ANGGOTA -->
            <div class="form-box rfid-box" style="padding: 32px 28px;">
                <div style="display: flex; gap: 16px; align-items: flex-start;">
                    <div style="width: 44px; height: 44px; border-radius: 10px; background: var(--slate-900); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <rect x="2" y="5" width="20" height="14" rx="2"/>
                            <line x1="2" y1="10" x2="22" y2="10"/>
                        </svg>
                    </div>
                    <div style="flex: 1;">
                        <h3 style="margin-top: 0; margin-bottom: 4px;">Scan Kartu RFID / Ketik NIS Anggota</h3>
                        <p class="form-desc" style="margin-bottom: 18px;">
                            Arahkan scanner ke kartu RFID anggota siswa, atau ketikkan NIS secara manual lalu tekan Enter.
                        </p>

                        <form action="../config/proses_scan_anggota_admin.php" method="POST" id="form-kartu" style="display: flex; gap: 10px; max-width: 620px;" autocomplete="off">
                            <input type="text" name="kode_kartu" id="input-kartu" autofocus autocomplete="off"
                                   placeholder="Scan kartu RFID / ketik NIS siswa..."
                                   style="font-family: var(--font-mono); font-size: 15px; font-weight: 600; padding: 12px 16px; height: 48px;">
                            <button type="submit" id="btn-kartu" class="btn btn-primary" style="height: 48px; padding: 0 24px;">
                                Cari Anggota &rarr;
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <script>
                const formKartu = document.getElementById('form-kartu');
                const btnKartu  = document.getElementById('btn-kartu');
                const inp       = document.getElementById('input-kartu');
                if (formKartu) {
                    let submitting = false;
                    formKartu.addEventListener('submit', function (e) {
                        if (submitting) { e.preventDefault(); return false; }
                        submitting = true;
                        btnKartu.disabled = true;
                    });
                }
                if (inp) {
                    inp.focus();
                    inp.addEventListener('keydown', e => {
                        if (e.key === 'Enter') { e.preventDefault(); formKartu.requestSubmit ? formKartu.requestSubmit() : formKartu.submit(); }
                    });
                }
            </script>

        <?php else: ?>
            <!-- STEP 2: ANGGOTA DITEMUKAN — SCAN QR BUKU -->

            <!-- KARTU IDENTITAS DIGITAL ANGGOTA -->
            <div class="anggota-card">
                <div style="display: flex; align-items: center; gap: 16px;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(255, 255, 255, 0.15); display: flex; align-items: center; justify-content: center; color: #fff; flex-shrink: 0;">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2"/>
                            <circle cx="12" cy="7" r="4"/>
                        </svg>
                    </div>
                    <div class="info">
                        <h3><?= htmlspecialchars($anggota['nama_lengkap']) ?></h3>
                        <p>
                            NIS: <strong><?= htmlspecialchars($anggota['nis']) ?></strong> &bull; 
                            Kelas: <strong><?= htmlspecialchars($anggota['kelas']) ?></strong>
                            <?php if ($anggota['kode_rfid']): ?>
                                &bull; RFID: <code><?= htmlspecialchars($anggota['kode_rfid']) ?></code>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 16px;">
                    <div class="badge-slot">
                        <strong><?= $jumlahAktif ?> / <?= $batasPinjam ?></strong>
                        Slot Kuota Terpakai
                    </div>
                    <a href="peminjaman.php?reset=1" class="btn-reset">
                        &larr; Ganti Anggota
                    </a>
                </div>
            </div>

            <?php if ($jumlahAktif >= $batasPinjam): ?>
                <div class="action-hint warn">
                    Anggota ini sudah mencapai <strong>batas maksimal peminjaman (3 buku)</strong>.
                    Siswa tidak dapat meminjam buku tambahan sebelum mengembalikan salah satu buku pinjaman.
                    <div style="margin-top: 10px;">
                        <a href="pengembalian.php" style="color: var(--brand-primary); font-weight: 700; text-decoration: underline;">Menuju Halaman Pengembalian &rarr;</a>
                    </div>
                </div>
            <?php else: ?>
                <div class="action-hint">
                    Kartu anggota terverifikasi. Sisa kuota peminjaman: <strong><?= $batasPinjam - $jumlahAktif ?> buku</strong>. Silakan scan label buku yang ingin dipinjam.
                </div>

                <!-- FORM SCAN BUKU -->
                <div class="form-box" style="padding: 28px;">
                    <div style="display: flex; gap: 16px; align-items: flex-start;">
                        <div style="width: 44px; height: 44px; border-radius: 10px; background: var(--slate-900); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                                <path d="M6 6h10"/>
                                <path d="M6 10h10"/>
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <h3 style="margin-top: 0;">Scan Kode QR / Barcode Eksemplar Buku</h3>
                            <p class="form-desc" style="margin-bottom: 16px;">
                                Tempelkan scanner pada label QR stiker yang tertempel pada fisik buku perpustakaan.
                            </p>

                            <form action="../config/proses_transaksi_admin.php" method="POST" id="form-buku" style="display: flex; gap: 10px; max-width: 620px;" autocomplete="off">
                                <input type="text" name="kode_item" id="input-buku" autofocus autocomplete="off"
                                       placeholder="Scan QR kode item buku (Contoh: BK-...)"
                                       style="font-family: var(--font-mono); font-size: 15px; font-weight: 600; padding: 12px 16px; height: 48px;">
                                <button type="submit" id="btn-buku" class="btn btn-primary" style="height: 48px; padding: 0 24px;">
                                    Proses Pinjam &rarr;
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <script>
                    const formBuku = document.getElementById('form-buku');
                    const btnBuku  = document.getElementById('btn-buku');
                    const inpBuku  = document.getElementById('input-buku');
                    if (formBuku) {
                        let submittingBuku = false;
                        formBuku.addEventListener('submit', function (e) {
                            if (submittingBuku) { e.preventDefault(); return false; }
                            submittingBuku = true;
                            btnBuku.disabled = true;
                        });
                    }
                    if (inpBuku) {
                        inpBuku.focus();
                        inpBuku.addEventListener('keydown', e => {
                            if (e.key === 'Enter') { e.preventDefault(); formBuku.requestSubmit ? formBuku.requestSubmit() : formBuku.submit(); }
                        });
                    }
                </script>
            <?php endif; ?>

            <!-- BUKU YANG SEDANG AKTIF DIPINJAM -->
            <div style="margin-top: 32px;">
                <h3 style="margin-bottom: 12px;">Buku yang Sedang Dipinjam oleh <?= htmlspecialchars($anggota['nama_lengkap']) ?> (<?= $jumlahAktif ?> buku):</h3>
                
                <div class="table-container">
                    <table class="table-data">
                        <thead>
                            <tr>
                                <th style="width: 48px;">No</th>
                                <th>Judul Buku</th>
                                <th>Kode Item Fisik</th>
                                <th>Tanggal Pinjam</th>
                                <th>Batas Pengembalian</th>
                                <th>Status Pinjaman</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($listDipinjam)): ?>
                                <tr><td colspan="6">Anggota ini sedang tidak memiliki buku yang dipinjam.</td></tr>
                            <?php else: ?>
                                <?php foreach ($listDipinjam as $i => $row):
                                    $terlambat = $today > $row['tgl_kembali_rencana'];
                                    $selisih = (int) round((strtotime($today) - strtotime($row['tgl_kembali_rencana'])) / 86400);
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
                                            <span class="badge-info">Dipinjam</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>

        <?php endif; ?>
<?php require 'includes/footer.php'; ?>
