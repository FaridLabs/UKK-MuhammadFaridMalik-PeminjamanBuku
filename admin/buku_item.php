<?php
require '../config/cek_session_admin.php';
require '../config/koneksi.php';

$bukuId = $_GET['buku_id'] ?? null;
if (empty($bukuId)) {
    header('Location: buku.php');
    exit;
}

// Data master buku terkait
$stmtBuku = $koneksi->prepare("
    SELECT buku.*, kategori.nama_kategori 
    FROM buku 
    LEFT JOIN kategori ON buku.kategori_id = kategori.id 
    WHERE buku.id = :id
");
$stmtBuku->bindParam(':id', $bukuId);
$stmtBuku->execute();
$buku = $stmtBuku->fetch();

if (!$buku) {
    header('Location: buku.php');
    exit;
}

// Ambil semua eksemplar dari judul ini
$stmtItem = $koneksi->prepare("SELECT * FROM buku_item WHERE buku_id = :buku_id ORDER BY id ASC");
$stmtItem->bindParam(':buku_id', $bukuId);
$stmtItem->execute();
$items = $stmtItem->fetchAll();

$totalEksemplar = count($items);
$totalTersedia  = 0;
$totalDipinjam  = 0;
$totalLainnya   = 0;

foreach ($items as $item) {
    if ($item['status'] === 'tersedia') {
        $totalTersedia++;
    } elseif ($item['status'] === 'dipinjam') {
        $totalDipinjam++;
    } else {
        $totalLainnya++;
    }
}
$pageTitle = 'Kelola Eksemplar - ' . $buku['judul'];
$currentPage = 'buku';
require 'includes/header.php';
?>
        <!-- HEADER -->
        <div class="page-header-wrap">
            <div>
                <h1>Eksemplar: <?= htmlspecialchars($buku['judul']) ?></h1>
                <p class="page-subtitle">
                    Kategori: <strong><?= htmlspecialchars($buku['nama_kategori'] ?? 'Umum') ?></strong> &bull;
                    Pengarang: <strong><?= htmlspecialchars($buku['pengarang'] ?? '-') ?></strong> &bull;
                    Penerbit: <strong><?= htmlspecialchars($buku['penerbit'] ?? '-') ?> (<?= htmlspecialchars($buku['tahun_terbit'] ?? '-') ?>)</strong>
                </p>
            </div>
            <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                <?php if (!empty($items)): ?>
                    <a href="../config/generate_qr.php?buku_id=<?= $buku['id'] ?>" target="_blank" class="btn btn-primary">
                        Cetak Semua Label QR
                    </a>
                <?php endif; ?>
                <a href="buku.php" class="btn btn-secondary">&larr; Kembali ke Data Buku</a>
            </div>
        </div>

        <!-- STAT SUMMARY BAR -->
        <div class="stat-grid" style="grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); margin-bottom: 24px;">
            <div class="stat-card">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16z"/>
                        </svg>
                    </div>
                </div>
                <h3><?= $totalEksemplar ?></h3>
                <p>Total Buku Fisik</p>
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
                <p>Sedang Dipinjam</p>
            </div>
            <div class="stat-card stat-warning">
                <div class="stat-card-header">
                    <div class="stat-icon">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="12" cy="12" r="10"/>
                            <line x1="12" y1="8" x2="12" y2="12"/>
                            <line x1="12" y1="16" x2="12.01" y2="16"/>
                        </svg>
                    </div>
                </div>
                <h3><?= $totalLainnya ?></h3>
                <p>Rusak / Hilang</p>
            </div>
        </div>

        <!-- NOTIFIKASI -->
        <?php if (isset($_GET['success']) && $_GET['success'] === 'generate'): ?>
            <div class="alert alert-success">
                Berhasil men-generate <strong><?= htmlspecialchars($_GET['jumlah'] ?? '1') ?></strong> kode QR unik eksemplar buku baru!
                <a href="../config/generate_qr.php?buku_id=<?= $buku['id'] ?>" target="_blank" style="font-weight: 700; text-decoration: underline; margin-left: 8px;">
                    Cetak Lembar Label Stiker QR &rarr;
                </a>
            </div>
        <?php elseif (isset($_GET['success']) && $_GET['success'] === 'manual'): ?>
            <div class="alert alert-success">
                ✅ Berhasil mendaftarkan kode eksemplar manual: <code><?= htmlspecialchars($_GET['kode'] ?? '') ?></code>.
            </div>
        <?php elseif (isset($_GET['success']) && $_GET['success'] === 'status'): ?>
            <div class="alert alert-success">Status eksemplar buku berhasil diperbarui.</div>
        <?php elseif (isset($_GET['success']) && $_GET['success'] === 'hapus'): ?>
            <div class="alert alert-success">Eksemplar buku berhasil dihapus dari sistem.</div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="alert alert-success">Data eksemplar berhasil diperbarui.</div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'sudah_ada'): ?>
            <div class="alert alert-error">
                ❌ Kode barcode/QR <code><?= htmlspecialchars($_GET['kode'] ?? '') ?></code> sudah digunakan oleh judul lain ("<strong><?= htmlspecialchars($_GET['judul_ada'] ?? '') ?></strong>"). Setiap fisik buku harus memiliki kode unik.
            </div>
        <?php endif; ?>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'dipinjam'): ?>
            <div class="alert alert-error">❌ Eksemplar buku tidak dapat dihapus karena saat ini sedang dipinjam oleh siswa.</div>
        <?php endif; ?>

        <!-- FORM UTAMA: GENERATE KODE QR EKSEMPLAR BARU -->
        <div class="form-box" style="padding: 24px 28px;">
            <h3 style="display: flex; align-items: center; gap: 8px; margin-top: 0;">
                ⚡ Terbitkan &amp; Generate QR Eksemplar Baru
            </h3>
            <p class="form-desc">
                Sistem akan otomatis menerbitkan serial unik barcode/QR untuk tiap eksemplar fisik buku (misal: <code>BK-<?= $buku['id'] ?>-001</code>) yang siap dicetak sebagai stiker label buku.
            </p>

            <form action="../config/proses_buku_item.php" method="POST" id="form-generate-qr" autocomplete="off">
                <input type="hidden" name="action" value="generate_qr">
                <input type="hidden" name="buku_id" value="<?= $buku['id'] ?>">

                <div style="display: flex; gap: 16px; align-items: flex-end; flex-wrap: wrap;">
                    <div>
                        <label style="margin-top: 0; font-size: 13px; font-weight: 700; color: var(--slate-700);">Jumlah Eksemplar Fisik Tambahan</label>
                        <div style="display: flex; align-items: center; gap: 6px; margin-top: 6px;">
                            <button type="button" class="btn btn-secondary" style="padding: 0 14px; height: 42px; font-size: 18px;" onclick="tambahJumlah(-1)">-</button>
                            <input type="number" name="jumlah" id="input-jumlah" value="1" min="1" max="100" style="width: 80px; height: 42px; text-align: center; font-weight: 700; font-size: 16px; margin: 0;" required autocomplete="off">
                            <button type="button" class="btn btn-secondary" style="padding: 0 14px; height: 42px; font-size: 18px;" onclick="tambahJumlah(1)">+</button>
                        </div>
                        <div style="display: flex; gap: 6px; margin-top: 8px;">
                            <button type="button" class="btn btn-secondary" style="font-size: 11.5px; padding: 3px 8px;" onclick="setJumlah(1)">+1 Buku</button>
                            <button type="button" class="btn btn-secondary" style="font-size: 11.5px; padding: 3px 8px;" onclick="setJumlah(2)">+2 Buku</button>
                            <button type="button" class="btn btn-secondary" style="font-size: 11.5px; padding: 3px 8px;" onclick="setJumlah(5)">+5 Buku</button>
                            <button type="button" class="btn btn-secondary" style="font-size: 11.5px; padding: 3px 8px;" onclick="setJumlah(10)">+10 Buku</button>
                        </div>
                    </div>

                    <div>
                        <button type="submit" id="btn-generate" class="btn btn-primary" style="height: 42px;">
                            ⚡ Terbitkan <span id="label-btn-qty">1</span> Kode QR Baru
                        </button>
                    </div>
                </div>
            </form>

            <!-- OPSIONAL: DAFTARKAN KODE MANUAL -->
            <details style="margin-top: 20px; padding-top: 14px; border-top: 1px dashed var(--slate-200);">
                <summary style="cursor: pointer; font-size: 13px; font-weight: 600; color: var(--slate-600); user-select: none;">
                    ➕ Daftarkan Barcode / Kode Manual Khusus (Opsional)
                </summary>
                <div style="margin-top: 12px; background: var(--slate-50); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--slate-200);">
                    <p style="font-size: 13px; color: var(--slate-500); margin-bottom: 12px;">
                        Gunakan ini jika buku fisik sudah memiliki label barcode pabrikan yang ingin langsung diintegrasikan.
                    </p>
                    <form action="../config/proses_buku_item.php" method="POST" style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;" autocomplete="off">
                        <input type="hidden" name="action" value="tambah_manual">
                        <input type="hidden" name="buku_id" value="<?= $buku['id'] ?>">
                        <input type="text" name="kode_item" placeholder="Ketik/scan kode barcode unik..." required style="max-width: 320px; margin: 0;" autocomplete="off">
                        <button type="submit" class="btn btn-secondary">Simpan Kode Manual</button>
                    </form>
                </div>
            </details>
        </div>

        <!-- TABEL EKSEMPLAR -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; flex-wrap: wrap; gap: 10px;">
            <h3 style="margin: 0;">
                Daftar Eksemplar Fisik &amp; Kode QR (Total: <?= count($items) ?> fisik)
            </h3>
            <?php if (!empty($items)): ?>
                <a href="../config/generate_qr.php?buku_id=<?= $buku['id'] ?>" target="_blank" class="btn btn-secondary" style="font-size: 12.5px; padding: 6px 14px;">
                    🖨️ Cetak Lembar Label (Semua)
                </a>
            <?php endif; ?>
        </div>

        <div class="table-container">
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 48px;">No</th>
                        <th>Nomor Fisik</th>
                        <th>Kode QR Eksemplar</th>
                        <th>Status Fisik Buku</th>
                        <th style="text-align: right; width: 220px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($items)): ?>
                        <tr>
                            <td colspan="5">
                                Belum ada eksemplar fisik untuk judul buku ini.<br>
                                Silakan gunakan tombol <strong>⚡ Terbitkan Kode QR Baru</strong> di atas untuk membuat eksemplar.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($items as $i => $item): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <strong style="color: var(--slate-900);">Eksemplar #<?= $i + 1 ?></strong>
                                </td>
                                <td>
                                    <code style="font-size: 13.5px; font-weight: 700; color: var(--brand-primary); background: var(--brand-primary-subtle); border-color: var(--brand-primary-border);">
                                        <?= htmlspecialchars($item['kode_item']) ?>
                                    </code>
                                </td>
                                <td>
                                    <form action="../config/proses_buku_item.php" method="POST" style="display: inline;" autocomplete="off">
                                        <input type="hidden" name="action" value="ubah_status">
                                        <input type="hidden" name="id" value="<?= $item['id'] ?>">
                                        <input type="hidden" name="buku_id" value="<?= $buku['id'] ?>">
                                        <select name="status" onchange="this.form.submit()" 
                                                <?= $item['status'] === 'dipinjam' ? 'disabled' : '' ?>
                                                style="padding: 4px 10px; font-size: 13px; border-radius: var(--radius-sm); font-weight: 600; width: auto; margin: 0;">
                                            <option value="tersedia" <?= $item['status'] === 'tersedia' ? 'selected' : '' ?>>Tersedia</option>
                                            <option value="rusak"    <?= $item['status'] === 'rusak'    ? 'selected' : '' ?>>Rusak</option>
                                            <option value="hilang"   <?= $item['status'] === 'hilang'   ? 'selected' : '' ?>>Hilang</option>
                                            <option value="dipinjam" <?= $item['status'] === 'dipinjam' ? 'selected' : '' ?>>Sedang Dipinjam</option>
                                        </select>
                                    </form>
                                </td>
                                <td style="text-align: right;">
                                    <a href="../config/generate_qr.php?item_id=<?= $item['id'] ?>" target="_blank" class="btn btn-secondary" style="font-size: 12px; padding: 4px 10px;">
                                        Cetak QR
                                    </a>
                                    <?php if ($item['status'] !== 'dipinjam'): ?>
                                        <a href="../config/proses_buku_item.php?action=hapus&id=<?= $item['id'] ?>&buku_id=<?= $buku['id'] ?>"
                                           class="btn-hapus"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus eksemplar ini?')">Hapus</a>
                                    <?php else: ?>
                                        <span style="font-size: 12px; color: var(--slate-400); margin-left: 6px;">(Sedang Dipinjam)</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    <script>
        const inputJumlah = document.getElementById('input-jumlah');
        const labelBtnQty = document.getElementById('label-btn-qty');

        function updateBtnLabel() {
            let val = parseInt(inputJumlah.value) || 1;
            labelBtnQty.innerText = val;
        }

        function tambahJumlah(delta) {
            let val = parseInt(inputJumlah.value) || 1;
            val += delta;
            if (val < 1) val = 1;
            if (val > 100) val = 100;
            inputJumlah.value = val;
            updateBtnLabel();
        }

        function setJumlah(n) {
            inputJumlah.value = n;
            updateBtnLabel();
        }

        if (inputJumlah) {
            inputJumlah.addEventListener('input', updateBtnLabel);
        }
    </script>
<?php require 'includes/footer.php'; ?>