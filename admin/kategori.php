<?php
require '../config/cek_session_admin.php';
require '../config/koneksi.php';

// Ambil semua data kategori beserta jumlah bukunya
$data = $koneksi->query("
    SELECT kategori.*, COUNT(buku.id) AS total_buku
    FROM kategori
    LEFT JOIN buku ON buku.kategori_id = kategori.id
    GROUP BY kategori.id
    ORDER BY kategori.nama_kategori ASC
")->fetchAll();

// Jika mode edit, ambil data kategori yang dipilih
$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $koneksi->prepare("SELECT * FROM kategori WHERE id = :id");
    $stmt->bindParam(':id', $_GET['edit']);
    $stmt->execute();
    $editData = $stmt->fetch();
}
$pageTitle = 'Kelola Kategori';
$currentPage = 'kategori';
require 'includes/header.php';
?>
        <div class="page-header-wrap">
            <div>
                <h1>Kelola Kategori Buku</h1>
                <p class="page-subtitle">Atur kelompok klasifikasi kategori buku pada katalog perpustakaan.</p>
            </div>
        </div>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'ada_buku'): ?>
            <div class="alert alert-error">Kategori tidak dapat dihapus karena masih terkait dengan data buku aktif. Pindahkan kategori buku tersebut terlebih dahulu.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'empty'): ?>
            <div class="alert alert-error">Nama kategori tidak boleh dibiarkan kosong.</div>
        <?php endif; ?>
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success">Data kategori berhasil diperbarui.</div>
        <?php endif; ?>

        <div class="kategori-grid">
            <!-- FORM TAMBAH / EDIT -->
            <div class="form-box">
                <h3><?= $editData ? 'Edit Kategori' : 'Tambah Kategori' ?></h3>
                <p class="form-desc"><?= $editData ? 'Ubah label nama kategori yang dipilih.' : 'Masukkan nama kategori baru untuk koleksi buku.' ?></p>

                <form action="../config/proses_kategori.php" method="POST" autocomplete="off">
                    <?php if ($editData): ?>
                        <input type="hidden" name="action" value="edit">
                        <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                    <?php else: ?>
                        <input type="hidden" name="action" value="tambah">
                    <?php endif; ?>

                    <div class="form-group">
                        <label for="nama_kategori">Nama Kategori</label>
                        <input type="text" id="nama_kategori" name="nama_kategori" value="<?= $editData ? htmlspecialchars($editData['nama_kategori']) : '' ?>" placeholder="Contoh: Novel, Sains, Teknologi..." required autofocus autocomplete="off">
                    </div>

                    <div style="margin-top: 18px; display: flex; gap: 8px;">
                        <button type="submit" class="btn btn-primary">
                            <?= $editData ? 'Simpan Perubahan' : 'Tambah Kategori' ?>
                        </button>
                        <?php if ($editData): ?>
                            <a href="kategori.php" class="btn-cancel">Batal</a>
                        <?php endif; ?>
                    </div>
                </form>
            </div>

            <!-- TABEL DATA KATEGORI -->
            <div class="table-container">
                <table class="table-data">
                    <thead>
                        <tr>
                            <th style="width: 50px;">No</th>
                            <th>Nama Kategori</th>
                            <th>Jumlah Buku</th>
                            <th style="text-align: right; width: 140px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($data)): ?>
                            <tr><td colspan="4" style="text-align: center; color: var(--slate-400); padding: 32px 16px;">Belum ada data kategori yang terdaftar.</td></tr>
                        <?php else: ?>
                            <?php foreach ($data as $i => $row): ?>
                                <tr>
                                    <td><?= $i + 1 ?></td>
                                    <td>
                                        <strong style="color: var(--slate-900);"><?= htmlspecialchars($row['nama_kategori']) ?></strong>
                                    </td>
                                    <td>
                                        <span class="badge-info"><?= (int)$row['total_buku'] ?> judul buku</span>
                                    </td>
                                    <td style="text-align: right;">
                                        <a href="kategori.php?edit=<?= $row['id'] ?>" class="btn-edit">Edit</a>
                                        <a href="../config/proses_kategori.php?action=hapus&id=<?= $row['id'] ?>"
                                           class="btn-hapus"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus kategori ini?')">Hapus</a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
<?php require 'includes/footer.php'; ?>