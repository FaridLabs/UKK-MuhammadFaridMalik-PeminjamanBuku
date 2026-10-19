<?php
require '../config/cek_session_admin.php';
require '../config/koneksi.php';

$keyword = trim($_GET['keyword'] ?? '');

$sql = "
    SELECT anggota.*,
           (SELECT COUNT(*) FROM transaksi WHERE transaksi.anggota_id = anggota.id AND transaksi.status = 'dipinjam') AS pinjaman_aktif
    FROM anggota 
    WHERE 1=1
";
$params = [];

if (!empty($keyword)) {
    $sql .= " AND (nama_lengkap LIKE :kw OR nis LIKE :kw OR kelas LIKE :kw)";
    $params[':kw'] = "%$keyword%";
}

$sql .= " ORDER BY nama_lengkap ASC";

$stmt = $koneksi->prepare($sql);
foreach ($params as $k => $v) {
    $stmt->bindValue($k, $v);
}
$stmt->execute();
$data = $stmt->fetchAll();

$editData = null;
if (isset($_GET['edit'])) {
    $stmt = $koneksi->prepare("SELECT * FROM anggota WHERE id = :id");
    $stmt->bindParam(':id', $_GET['edit']);
    $stmt->execute();
    $editData = $stmt->fetch();
}

// Mode tap RFID untuk anggota tertentu
$rfidTarget = null;
if (isset($_GET['tap_rfid'])) {
    $stmt = $koneksi->prepare("SELECT * FROM anggota WHERE id = :id");
    $stmt->bindParam(':id', $_GET['tap_rfid']);
    $stmt->execute();
    $rfidTarget = $stmt->fetch();
}
$pageTitle = 'Kelola Anggota';
$currentPage = 'anggota';
require 'includes/header.php';
?>
        <div class="page-header-wrap">
            <div>
                <h1>Kelola Anggota Perpustakaan</h1>
                <p class="page-subtitle">Pendaftaran siswa, pairing kartu RFID fisik, dan monitoring status pinjaman aktif.</p>
            </div>
        </div>

        <?php if (isset($_GET['error']) && $_GET['error'] === 'empty'): ?>
            <div class="alert alert-error">Semua field bertanda wajib harus diisi lengkap.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'nis_ada'): ?>
            <div class="alert alert-error">NIS tersebut sudah terdaftar di sistem. Gunakan NIS unik.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'username_ada'): ?>
            <div class="alert alert-error">Username sudah dipakai oleh anggota lain. Silakan pilih username berbeda.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'rfid_ada'): ?>
            <div class="alert alert-error">Kartu RFID ini sudah terdaftar untuk siswa lain. Setiap kartu fisik harus unik.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'sedang_meminjam'): ?>
            <div class="alert alert-error">Anggota tidak dapat dihapus karena masih meminjam buku. Selesaikan pengembalian buku di menu <strong>Pengembalian</strong> terlebih dahulu.</div>
        <?php endif; ?>
        <?php if (isset($_GET['error']) && $_GET['error'] === 'ada_transaksi'): ?>
            <div class="alert alert-error">Anggota tidak dapat dihapus karena memiliki riwayat pinjaman aktif.</div>
        <?php endif; ?>
        <?php if (isset($_GET['success']) && $_GET['success'] === 'hapus'): ?>
            <div class="alert alert-success">Data anggota berhasil dihapus dari sistem.</div>
        <?php elseif (isset($_GET['success'])): ?>
            <div class="alert alert-success">Data anggota berhasil diperbarui.</div>
        <?php endif; ?>

        <!-- TERMINAL: TAP KARTU RFID -->
        <?php if ($rfidTarget): ?>
            <div class="form-box rfid-box" style="margin-bottom: 28px;">
                <h3 style="display: flex; align-items: center; gap: 8px;">
                    Pairing Kartu RFID Fisik
                </h3>
                <p class="form-desc" style="color: var(--slate-700);">
                    Tempelkan kartu RFID pada card reader USB untuk anggota: <strong><?= htmlspecialchars($rfidTarget['nama_lengkap']) ?></strong> (NIS: <?= htmlspecialchars($rfidTarget['nis']) ?>). Nilai kartu akan langsung tersimpan otomatis.
                </p>
                <form action="../config/proses_anggota.php" method="POST" id="form-rfid" style="display: flex; gap: 10px; align-items: center; max-width: 600px;" autocomplete="off">
                    <input type="hidden" name="action" value="set_rfid">
                    <input type="hidden" name="id" value="<?= $rfidTarget['id'] ?>">
                    <input type="text" name="kode_rfid" id="input-rfid" autofocus autocomplete="off" placeholder="Menunggu kartu di-tap pada reader..." style="font-family: var(--font-mono); font-weight: 600; font-size: 15px; border-color: var(--brand-primary);">
                    <button type="submit" class="btn btn-primary">Simpan Kartu</button>
                    <a href="anggota.php" class="btn-cancel">Batal</a>
                </form>
            </div>
            <script>
                const inputRfid = document.getElementById('input-rfid');
                if (inputRfid) {
                    inputRfid.focus();
                    inputRfid.addEventListener('keydown', function (e) {
                        if (e.key === 'Enter') {
                            e.preventDefault();
                            document.getElementById('form-rfid').submit();
                        }
                    });
                }
            </script>
        <?php endif; ?>

        <!-- FORM TAMBAH / EDIT -->
        <div class="form-box">
            <h3><?= $editData ? 'Edit Data Anggota' : 'Daftarkan Anggota Baru' ?></h3>
            <p class="form-desc">Lengkapi identitas siswa di bawah ini untuk membuat akun peminjaman perpustakaan.</p>

            <form action="../config/proses_anggota.php" method="POST" autocomplete="off">
                <?php if ($editData): ?>
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" value="<?= $editData['id'] ?>">
                <?php else: ?>
                    <input type="hidden" name="action" value="tambah">
                <?php endif; ?>

                <div class="form-grid-3">
                    <div class="form-group">
                        <label>NIS (Nomor Induk Siswa) <span style="color: var(--danger-solid);">*</span></label>
                        <input type="text" name="nis" value="<?= $editData ? htmlspecialchars($editData['nis']) : '' ?>" placeholder="Contoh: 2425001" required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Nama Lengkap Siswa <span style="color: var(--danger-solid);">*</span></label>
                        <input type="text" name="nama_lengkap" value="<?= $editData ? htmlspecialchars($editData['nama_lengkap']) : '' ?>" placeholder="Nama lengkap..." required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Kelas <span style="color: var(--danger-solid);">*</span></label>
                        <input type="text" name="kelas" value="<?= $editData ? htmlspecialchars($editData['kelas']) : '' ?>" placeholder="Contoh: XII RPL 1" required autocomplete="off">
                    </div>
                </div>

                <div class="form-grid-3" style="margin-top: 14px;">
                    <div class="form-group">
                        <label>No. HP / WhatsApp</label>
                        <input type="text" name="no_hp" value="<?= $editData ? htmlspecialchars($editData['no_hp']) : '' ?>" placeholder="08xxxxxxxxxx" autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Username Login <span style="color: var(--danger-solid);">*</span></label>
                        <input type="text" name="username" value="<?= $editData ? htmlspecialchars($editData['username']) : '' ?>" placeholder="Username login siswa..." required autocomplete="off">
                    </div>
                    <div class="form-group">
                        <label>Password <?= $editData ? '<span style="font-weight: 400; color: var(--slate-500);">(kosongkan jika tidak diubah)</span>' : '<span style="color: var(--danger-solid);">*</span>' ?></label>
                        <input type="password" name="password" placeholder="••••••••" <?= $editData ? '' : 'required' ?> autocomplete="off">
                    </div>
                </div>

                <div style="margin-top: 20px;">
                    <button type="submit" class="btn btn-primary"><?= $editData ? 'Simpan Perubahan' : '+ Tambah Anggota' ?></button>
                    <?php if ($editData): ?>
                        <a href="anggota.php" class="btn-cancel">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- SEARCH BAR -->
        <form action="anggota.php" method="GET" class="search-box" autocomplete="off">
            <input type="text" name="keyword" placeholder="Cari nama siswa, NIS, atau kelas..." value="<?= htmlspecialchars($keyword) ?>" autocomplete="off">
            <button type="submit" class="btn btn-primary">Cari Anggota</button>
            <?php if (!empty($keyword)): ?>
                <a href="anggota.php" class="btn-cancel">Reset</a>
            <?php endif; ?>
        </form>

        <!-- TABLE ANGGOTA -->
        <div class="table-container">
            <table class="table-data">
                <thead>
                    <tr>
                        <th style="width: 48px;">No</th>
                        <th>NIS</th>
                        <th>Nama Anggota</th>
                        <th>Kelas</th>
                        <th>Username</th>
                        <th>Kartu RFID Fisik</th>
                        <th>Status Pinjaman</th>
                        <th style="text-align: right; width: 140px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($data)): ?>
                        <tr><td colspan="8">Tidak ada data anggota ditemukan.</td></tr>
                    <?php else: ?>
                        <?php foreach ($data as $i => $row): ?>
                            <tr>
                                <td><?= $i + 1 ?></td>
                                <td><code><?= htmlspecialchars($row['nis']) ?></code></td>
                                <td><strong style="color: var(--slate-900);"><?= htmlspecialchars($row['nama_lengkap']) ?></strong></td>
                                <td><?= htmlspecialchars($row['kelas']) ?></td>
                                <td><span style="color: var(--slate-600);"><?= htmlspecialchars($row['username']) ?></span></td>
                                <td>
                                    <?php if ($row['kode_rfid']): ?>
                                        <div style="display: flex; align-items: center; gap: 6px;">
                                            <span class="badge-success"><?= htmlspecialchars($row['kode_rfid']) ?></span>
                                            <a href="anggota.php?tap_rfid=<?= $row['id'] ?>" class="btn-edit" style="font-size: 11px; padding: 2px 7px;">Ganti</a>
                                        </div>
                                    <?php else: ?>
                                        <a href="anggota.php?tap_rfid=<?= $row['id'] ?>" class="btn-edit" style="font-size: 11.5px; padding: 3px 9px;">
                                            + Tap RFID
                                        </a>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($row['pinjaman_aktif'] > 0): ?>
                                        <span class="badge-warning">Meminjam (<?= $row['pinjaman_aktif'] ?> buku)</span>
                                    <?php else: ?>
                                        <span class="badge-success">Bebas Pinjaman</span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: right;">
                                    <a href="anggota.php?edit=<?= $row['id'] ?>" class="btn-edit">Edit</a>
                                    <?php if ($row['pinjaman_aktif'] > 0): ?>
                                        <button type="button" class="btn-hapus" style="opacity: 0.55; cursor: not-allowed; border: none;"
                                                onclick="alert('Anggota <?= htmlspecialchars(addslashes($row['nama_lengkap'])) ?> masih memiliki <?= $row['pinjaman_aktif'] ?> buku pinjaman aktif. Selesaikan pengembalian terlebih dahulu.');">
                                            Hapus
                                        </button>
                                    <?php else: ?>
                                        <a href="../config/proses_anggota.php?action=hapus&id=<?= $row['id'] ?>"
                                           class="btn-hapus"
                                           onclick="return confirm('Apakah Anda yakin ingin menghapus anggota <?= htmlspecialchars(addslashes($row['nama_lengkap'])) ?>?')">
                                            Hapus
                                        </a>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
<?php require 'includes/footer.php'; ?>