<?php
require '../config/cek_session_admin.php';
require '../config/koneksi.php';

// Cek apakah ada anggota yang di-scan
$anggota = null;
$listDipinjam = [];
$jumlahAktif = 0;

// Reset scan anggota jika diminta
if (isset($_GET['reset'])) {
    unset($_SESSION['admin_scan_anggota_pengembalian_id']);
    header('Location: pengembalian.php');
    exit;
}

if (isset($_SESSION['admin_scan_anggota_pengembalian_id'])) {
    $anggotaId = $_SESSION['admin_scan_anggota_pengembalian_id'];

    $stmtA = $koneksi->prepare("SELECT * FROM anggota WHERE id = :id LIMIT 1");
    $stmtA->bindParam(':id', $anggotaId);
    $stmtA->execute();
    $anggota = $stmtA->fetch();

    if (!$anggota) {
        unset($_SESSION['admin_scan_anggota_pengembalian_id']);
        header('Location: pengembalian.php?error=anggota_hilang');
        exit;
    }

    // Ambil daftar buku yang sedang dipinjam anggota ini
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

// Statistik ringkas sirkulasi
$statAktif = $koneksi->query("SELECT COUNT(*) AS total FROM transaksi WHERE status = 'dipinjam'")->fetch()['total'] ?? 0;
$statTerlambat = $koneksi->query("SELECT COUNT(*) AS total FROM transaksi WHERE status = 'dipinjam' AND tgl_kembali_rencana < CURDATE()")->fetch()['total'] ?? 0;

$today = date('Y-m-d');
$pageTitle = 'Pengembalian Buku';
$currentPage = 'pengembalian';
require 'includes/header.php';
?>
        <div class="page-header-wrap">
            <div>
                <h1>Pengembalian Buku</h1>
                <p class="page-subtitle">Verifikasi dan catat pengembalian buku pinjaman siswa ke perpustakaan.</p>
            </div>
            <!-- STATUS RINGKAS -->
            <div style="display: flex; gap: 12px; flex-wrap: wrap;">
                <div style="background: #ffffff; border: 1px solid var(--slate-200); padding: 8px 16px; border-radius: var(--radius-md); box-shadow: var(--shadow-xs); display: flex; align-items: center; gap: 10px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--slate-600);">
                        <path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/>
                    </svg>
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--slate-400); font-weight: 700;">Sedang Dipinjam</div>
                        <div style="font-size: 18px; font-weight: 800; color: var(--slate-900);"><?= $statAktif ?></div>
                    </div>
                </div>
                <div style="background: var(--danger-bg); border: 1px solid var(--danger-border); padding: 8px 16px; border-radius: var(--radius-md); display: flex; align-items: center; gap: 10px;">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="color: var(--danger-solid);">
                        <circle cx="12" cy="12" r="10"/>
                        <polyline points="12 6 12 12 16 14"/>
                    </svg>
                    <div>
                        <div style="font-size: 11px; text-transform: uppercase; color: var(--danger-solid); font-weight: 700;">Terlambat</div>
                        <div style="font-size: 18px; font-weight: 800; color: var(--danger-solid);"><?= $statTerlambat ?></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- STEP INDICATOR -->
        <div class="step-indicator">
            <div class="step-box <?= $anggota ? 'done' : 'active' ?>">
                <span class="step-num"><?= $anggota ? '✓' : '1' ?></span>
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--slate-400);">Langkah 1</div>
                    <div>Scan Kartu Siswa</div>
                </div>
            </div>
            <span class="step-arrow">&rarr;</span>
            <div class="step-box <?= $anggota ? 'active' : '' ?>">
                <span class="step-num">2</span>
                <div>
                    <div style="font-size: 11px; text-transform: uppercase; color: var(--slate-400);">Langkah 2</div>
                    <div>Scan / Pilih Buku Dikembalikan</div>
                </div>
            </div>
        </div>

        <!-- NOTIFIKASI SUKSES / ERROR -->
        <?php if (isset($_GET['success']) && $_GET['success'] === 'kembali'): ?>
            <div class="alert alert-success">
                <div>
                    <strong>Pengembalian Berhasil!</strong> Buku "<strong><?= htmlspecialchars($_GET['judul'] ?? '') ?></strong>"
                    (Kode: <code><?= htmlspecialchars($_GET['kode_item'] ?? '') ?></code>) atas nama
                    <strong><?= htmlspecialchars($_GET['nama'] ?? '') ?></strong> telah berhasil dicatat kembali ke stok perpustakaan.
                    <?php if (isset($_GET['terlambat'])): ?>
                        <div style="color: var(--danger-solid); font-weight: 700; margin-top: 4px;">Catatan: Pengembalian ini terlambat <?= htmlspecialchars($_GET['hari'] ?? '') ?> hari dari batas waktu yang ditentukan.</div>
                    <?php else: ?>
                        <div style="color: var(--success-solid); font-weight: 600; margin-top: 4px;">Buku dikembalikan tepat waktu.</div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error'])): ?>
            <?php
            $peminjamErr = htmlspecialchars($_GET['peminjam'] ?? 'anggota lain');
            $judulErr    = htmlspecialchars($_GET['judul'] ?? 'Buku ini');
            $errMap = [
                'tidak_ditemukan'      => 'Data anggota atau buku tidak ditemukan di sistem. Pastikan kartu RFID / NIS sudah terdaftar.',
                'bukan_dipinjam'       => 'Buku "' . $judulErr . '" tidak sedang berstatus dipinjam (stok sudah tersedia di perpustakaan).',
                'bukan_milik_anggota'  => 'Buku "' . $judulErr . '" tidak sedang dipinjam oleh anggota ini, melainkan oleh ' . $peminjamErr . '.',
                'anggota_hilang'       => 'Data anggota tidak ditemukan. Silakan scan ulang kartu anggota.',
                'empty'                => 'Input scan tidak boleh kosong.',
                'system'               => 'Terjadi kesalahan sistem saat memproses pengembalian buku.',
            ];
            ?>
            <div class="alert alert-error"><?= $errMap[$_GET['error']] ?? 'Terjadi kesalahan pada proses pengembalian.' ?></div>
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
                        <h3 style="margin-top: 0; margin-bottom: 4px;">Identifikasi Siswa yang Mengembalikan Buku</h3>
                        <p class="form-desc" style="margin-bottom: 18px;">
                            Tap kartu RFID siswa pada card reader, atau ketik NIS siswa secara manual.
                        </p>

                        <form action="../config/proses_scan_anggota_admin.php" method="POST" id="form-kartu" style="display: flex; gap: 10px; max-width: 620px;" autocomplete="off">
                            <input type="hidden" name="redirect" value="pengembalian.php">
                            <input type="text" name="kode_kartu" id="input-kartu" autofocus autocomplete="off"
                                   placeholder="Scan kartu RFID / ketik NIS siswa..."
                                   style="font-family: var(--font-mono); font-size: 15px; font-weight: 600; padding: 12px 16px; height: 48px;">
                            <button type="submit" class="btn btn-primary" style="height: 48px; padding: 0 24px;">
                                Cari Siswa &rarr;
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <script>
                const inp = document.getElementById('input-kartu');
                if (inp) {
                    inp.focus();
                    inp.addEventListener('keydown', e => {
                        if (e.key === 'Enter') { e.preventDefault(); document.getElementById('form-kartu').submit(); }
                    });
                }
            </script>

        <?php else: ?>
            <!-- STEP 2: ANGGOTA DITEMUKAN — PENGEMBALIAN BUKU -->

            <!-- KARTU SISWA -->
            <div class="anggota-card" style="background: var(--slate-900);">
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
                        </p>
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 16px;">
                    <div class="badge-slot">
                        <strong><?= $jumlahAktif ?></strong>
                        Buku Sedang Dipinjam
                    </div>
                    <a href="pengembalian.php?reset=1" class="btn-reset">
                        &larr; Ganti Siswa
                    </a>
                </div>
            </div>

            <?php if ($jumlahAktif === 0): ?>
                <div class="alert alert-success" style="padding: 24px; margin-top: 16px;">
                    <div>
                        <h4 style="margin: 0 0 6px; font-size: 16px;">Semua Beres! Tidak Ada Pinjaman Aktif</h4>
                        <p style="margin: 0; color: var(--success-text);">
                            Siswa <strong><?= htmlspecialchars($anggota['nama_lengkap']) ?></strong> saat ini tidak memiliki pinjaman buku yang tertunggak. Semua buku telah dikembalikan dengan lengkap.
                        </p>
                        <div style="margin-top: 12px;">
                            <a href="pengembalian.php?reset=1" class="btn btn-success" style="font-size: 13px;">
                                Scan Siswa Lain &rarr;
                            </a>
                        </div>
                    </div>
                </div>
            <?php else: ?>

                <!-- TERMINAL SCAN QR BUKU PENGEMBALIAN -->
                <div class="form-box" style="padding: 28px;">
                    <div style="display: flex; gap: 16px; align-items: flex-start;">
                        <div style="width: 44px; height: 44px; border-radius: 10px; background: var(--slate-900); color: #fff; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="9 14 4 9 9 4"/>
                                <path d="M20 20v-7a4 4 0 0 0-4-4H4"/>
                            </svg>
                        </div>
                        <div style="flex: 1;">
                            <h3 style="margin-top: 0;">Scan QR Buku yang Diserahkan</h3>
                            <p class="form-desc" style="margin-bottom: 16px;">
                                Arahkan scanner ke label QR fisik pada buku yang diserahkan oleh siswa untuk pengembalian instan.
                            </p>

                            <form action="../config/proses_kembalikan_admin.php" method="POST" id="form-scan-kembali" style="display: flex; gap: 10px; max-width: 620px;" autocomplete="off">
                                <input type="text" name="kode_item" id="input-scan-kembali" autofocus autocomplete="off"
                                       placeholder="Scan kode QR buku yang dikembalikan..."
                                       style="font-family: var(--font-mono); font-size: 15px; font-weight: 600; padding: 12px 16px; height: 48px;">
                                <button type="submit" class="btn btn-primary" style="height: 48px; padding: 0 24px;">
                                    Proses Pengembalian &rarr;
                                </button>
                            </form>
                        </div>
                    </div>
                </div>

                <script>
                    const inputScan = document.getElementById('input-scan-kembali');
                    if (inputScan) {
                        inputScan.focus();
                        inputScan.addEventListener('keydown', function (e) {
                            if (e.key === 'Enter') {
                                e.preventDefault();
                                document.getElementById('form-scan-kembali').submit();
                            }
                        });
                    }
                </script>

                <!-- TABEL BUKU YANG SEDANG DIPINJAM OLEH SISWA INI -->
                <div style="margin-top: 32px;">
                    <h3 style="margin-bottom: 12px;">Daftar Buku yang Sedang Dipinjam Siswa Ini (<?= $jumlahAktif ?>):</h3>
                    <div class="table-container">
                        <table class="table-data">
                            <thead>
                                <tr>
                                    <th style="width: 48px;">No</th>
                                    <th>Judul Buku</th>
                                    <th>Kode Item</th>
                                    <th>Tgl Pinjam</th>
                                    <th>Batas Waktu</th>
                                    <th>Status Batas Waktu</th>
                                    <th style="text-align: right; width: 170px;">Aksi Cepat</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($listDipinjam as $i => $row):
                                    $isTerlambat = $today > $row['tgl_kembali_rencana'];
                                    $selisih = (int) round((strtotime($today) - strtotime($row['tgl_kembali_rencana'])) / 86400);
                                    $sisaHari = (int) round((strtotime($row['tgl_kembali_rencana']) - strtotime($today)) / 86400);
                                ?>
                                    <tr>
                                        <td><?= $i + 1 ?></td>
                                        <td><strong style="color: var(--slate-900);"><?= htmlspecialchars($row['judul']) ?></strong></td>
                                        <td><code><?= htmlspecialchars($row['kode_item']) ?></code></td>
                                        <td><?= htmlspecialchars($row['tgl_pinjam']) ?></td>
                                        <td><?= htmlspecialchars($row['tgl_kembali_rencana']) ?></td>
                                        <td>
                                            <?php if ($isTerlambat): ?>
                                                <span class="badge-danger">Terlambat <?= $selisih ?> hari</span>
                                            <?php else: ?>
                                                <span class="badge-info">Sisa <?= $sisaHari ?> hari</span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="text-align: right;">
                                            <a href="../config/proses_kembalikan_admin.php?transaksi_id=<?= $row['id'] ?>"
                                               class="btn btn-success"
                                               style="font-size: 12px; padding: 6px 12px;"
                                               onclick="return confirm('Konfirmasi pengembalian buku \'<?= htmlspecialchars(addslashes($row['judul'])) ?>\'?')">
                                                Kembalikan
                                            </a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

            <?php endif; ?>
        <?php endif; ?>
<?php require 'includes/footer.php'; ?>
