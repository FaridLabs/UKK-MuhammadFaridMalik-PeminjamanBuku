<?php
require '../config/cek_session_admin.php';
require '../config/koneksi.php';

$kategoriList = $koneksi->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();

$keyword = trim($_GET['keyword'] ?? '');

$sql = "
    SELECT buku.*, kategori.nama_kategori,
        (SELECT COUNT(*) FROM buku_item WHERE buku_item.buku_id = buku.id) AS total_eksemplar,
        (SELECT COUNT(*) FROM buku_item WHERE buku_item.buku_id = buku.id AND status = 'tersedia') AS total_tersedia
    FROM buku
    LEFT JOIN kategori ON buku.kategori_id = kategori.id
";

if (!empty($keyword)) {
    $sql .= " WHERE judul LIKE :keyword OR pengarang LIKE :keyword";
    $stmt = $koneksi->prepare($sql . " ORDER BY buku.id DESC");
    $stmt->bindValue(':keyword', "%$keyword%");
    $stmt->execute();
} else {
    $stmt = $koneksi->query($sql . " ORDER BY buku.id DESC");
}
$data = $stmt->fetchAll();

$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $koneksi->prepare("SELECT * FROM buku WHERE id = :id");
    $stmt->bindParam(':id', $_GET['edit']);
    $stmt->execute();
    $editData = $stmt->fetch();
}
$pageTitle = 'Data Koleksi Buku';
$currentPage = 'buku';
require 'includes/header.php';
?>
        <div class="page-header-wrap">
            <div>
                <h1>Kelola Data Buku</h1>
                <p class="page-subtitle">Daftarkan judul buku baru, kelola eksemplar fisik, dan cetak label barcode/QR.</p>
            </div>
        </div>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'empty'): ?>
            <div class="alert alert-error">Semua field yang bertanda wajib harus diisi lengkap.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'ada_item'): ?>
            <div class="alert alert-error">Judul ini tidak bisa dihapus karena masih memiliki eksemplar fisik. Hapus semua eksemplar terlebih dahulu di menu Kelola QR.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'system'): ?>
            <div class="alert alert-error">Terjadi kesalahan pada sistem saat menyimpan data buku.</div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'tambah'): ?>
            <div class="alert alert-success">
                Judul buku baru berhasil ditambahkan beserta <strong><?= htmlspecialchars($_GET['jumlah'] ?? '0') ?></strong> kode QR eksemplar fisik!
                <?php if (!empty($_GET['id'])): ?>
                    <a href="buku_item.php?buku_id=<?= htmlspecialchars($_GET['id']) ?>" style="font-weight: 600; text-decoration: underline; margin-left: 8px;">
                        Kelola Eksemplar
                    </a> &bull;
                    <a href="../config/generate_qr.php?buku_id=<?= htmlspecialchars($_GET['id']) ?>" target="_blank" style="font-weight: 600; text-decoration: underline;">
                        Cetak Stiker Label QR
                    </a>
                <?php endif; ?>
            </div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="alert alert-success">Data buku berhasil diperbarui.</div>
        <?php endif; ?>

        <!-- FORM TAMBAH / EDIT BUKU -->
        <div class="form-box">
            <h3><?= $editData ? 'Edit Data Judul Buku' : 'Tambah Judul Buku Baru' ?></h3>
            <p class="form-desc">Isi metadata buku di bawah ini. Eksemplar fisik unik dan kode QR akan digenerate otomatis.</p>

            <form action="../config/proses_buku.php" method="POST" autocomplete="off">
                <?php if ($editData): ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                <?php else: ?>
                    <input type="hidden" name="action" value="tambah">
                <?php endif; ?>

                <div class="form-group">
                    <label>Judul Buku <span style="color: var(--danger-solid);">*</span></label>
                    <input type="text" name="judul" value="<?= $editData ? htmlspecialchars($editData['judul']) : '' ?>" required placeholder="Masukkan judul buku secara lengkap..." autocomplete="off">
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Pengarang / Penulis</label>
                        <input type="text" name="pengarang" value="<?= $editData ? htmlspecialchars($editData['pengarang']) : '' ?>" placeholder="Nama penulis / pengarang..." autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Penerbit</label>
                        <input type="text" name="penerbit" value="<?= $editData ? htmlspecialchars($editData['penerbit']) : '' ?>" placeholder="Nama penerbit buku..." autocomplete="off">
                    </div>
                </div>

                <div class="form-grid-2">
                    <div class="form-group">
                        <label>Tahun Terbit</label>
                        <input type="number" name="tahun_terbit" min="1900" max="2100" value="<?= $editData ? $editData['tahun_terbit'] : '' ?>" placeholder="Contoh: 2024" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Kategori Buku <span style="color: var(--danger-solid);">*</span></label>
                        <select name="kategori_id" required>
                            <option value="">-- Pilih Kategori --</option>
                            <?php foreach ($kategoriList as $kat): ?>
                                <option value="<?= $kat['id'] ?>"
                                    <?= ($editData && $editData['kategori_id'] == $kat['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($kat['nama_kategori']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>

                <?php if (!$editData): ?>
                    <div class="form-group" style="background: var(--slate-50); padding: 14px 18px; border-radius: var(--radius-md); border: 1px solid var(--slate-200); margin-top: 14px;">
                        <label style="margin-top: 0;">Jumlah Eksemplar Fisik Awal (Generate QR Otomatis)</label>
                        <input type="number" name="jumlah_eksemplar" min="0" max="100" value="1" required style="max-width: 180px;" autocomplete="off">
                        <small style="color: var(--slate-500); font-size: 12.5px; display: block; margin-top: 6px;">
                            Sistem akan otomatis menerbitkan N kode QR fisik unik siap cetak yang tertempel pada masing-masing buku fisik.
                        </small>
                    </div>
                <?php endif; ?>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary">
                        <?= $editData ? 'Simpan Perubahan' : 'Tambah Buku &amp; Terbitkan QR' ?>
                    </button>
                    <?php if ($editData): ?>
                        <a href="buku.php" class="btn-cancel">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- SEARCH BAR -->
        <form action="buku.php" method="GET" class="search-box" autocomplete="off">
            <input type="text" name="keyword" placeholder="Cari berdasarkan judul atau pengarang buku..." value="<?= htmlspecialchars($keyword) ?>" autocomplete="off">
            <button type="submit" class="btn btn-primary">Cari Buku</button>
            <?php if (!empty($keyword)): ?>
                <a href="buku.php" class="btn-cancel">Reset</a>
            <?php endif; ?>
        </form>

        <!-- DATA TABLE -->
        <div class="table-container">
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 48px;">No</th>
                        <th>Judul Buku</th>
                        <th>Pengarang</th>
                        <th>Penerbit</th>
                        <th>Tahun</th>
                        <th>Kategori</th>
                        <th>Status Eksemplar</th>
                        <th style="text-align: right; width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr><td colspan="8">Tidak ada data buku yang ditemukan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td>
                                    <div style="font-weight: 700; color: var(--slate-900);"><?= htmlspecialchars($row['judul']) ?></div>
                                    <div style="font-size: 11.5px; color: var(--slate-500); margin-top: 2px;">Kode: <code><?= htmlspecialchars($row['kode_buku']) ?></code></div>
                                </td>
                                <td><?= htmlspecialchars($row['pengarang'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($row['penerbit'] ?: '-') ?></td>
                                <td><?= htmlspecialchars($row['tahun_terbit'] ?: '-') ?></td>
                                <td><span class="badge-info"><?= htmlspecialchars($row['nama_kategori'] ?? 'Umum') ?></span></td>
                                <td>
                                    <div style="font-size: 13px;">
                                        <span style="font-weight: 700; color: var(--success-solid);"><?= $row['total_tersedia'] ?></span> tersedia
                                        <span style="color: var(--slate-400);">/ <?= $row['total_eksemplar'] ?> fisik</span>
                                    </div>
                                    <div style="display: flex; gap: 6px; margin-top: 6px; flex-wrap: wrap;">
                                        <a href="buku_item.php?buku_id=<?= $row['id'] ?>" class="btn-edit" style="font-size: 11.5px; padding: 3px 8px;">
                                            Kelola QR
                                        </a>
                                        <?php if ($row['total_eksemplar'] > 0): ?>
                                            <a href="../config/generate_qr.php?buku_id=<?= $row['id'] ?>" target="_blank" class="btn-edit" style="font-size: 11.5px; padding: 3px 8px; background: var(--slate-800); color: #fff; border-color: var(--slate-700);">
                                                Cetak QR
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align: right;">
                                    <a href="buku.php?edit=<?= $row['id'] ?>" class="btn-edit">Edit</a>
                                    <a href="../config/proses_buku.php?action=hapus&id=<?= $row['id'] ?>"
                                       class="btn-hapus"
                                       onclick="return confirm('Apakah Anda yakin ingin menghapus judul buku ini?')">Hapus</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
<?php require 'includes/footer.php'; ?>