<?php
require '../config/cek_session_siswa.php';
require '../config/koneksi.php';

$keyword    = trim($_GET['keyword'] ?? '');
$kategoriId = trim($_GET['kategori'] ?? '');

$sql = "
    SELECT buku.*, kategori.nama_kategori,
           COUNT(buku_item.id) AS total_eksemplar,
           SUM(CASE WHEN buku_item.status = 'tersedia' THEN 1 ELSE 0 END) AS total_tersedia
    FROM buku
    LEFT JOIN kategori ON buku.kategori_id = kategori.id
    LEFT JOIN buku_item ON buku_item.buku_id = buku.id
    WHERE 1=1
";

$params = [];
if (!empty($keyword)) {
    $sql .= " AND (buku.judul LIKE :kw OR buku.pengarang LIKE :kw OR buku.penerbit LIKE :kw)";
    $params[':kw'] = "%$keyword%";
}
if (!empty($kategoriId)) {
    $sql .= " AND buku.kategori_id = :kat";
    $params[':kat'] = $kategoriId;
}

$sql .= " GROUP BY buku.id ORDER BY buku.judul ASC";

$stmt = $koneksi->prepare($sql);
$stmt->execute($params);
$data = $stmt->fetchAll();

$kategoriList = $koneksi->query("SELECT * FROM kategori ORDER BY nama_kategori ASC")->fetchAll();
$pageTitle = 'Katalog Buku';
$currentPage = 'daftar_buku';
require 'includes/header.php';
?>
        <div class="page-header-wrap">
            <div>
                <h1>Katalog Buku Perpustakaan</h1>
                <p class="page-subtitle">Jelajahi buku pelajaran, referensi, novel, dan buku ilmiah yang tersedia di sekolah.</p>
            </div>
        </div>

        <!-- SEARCH & FILTER FORM -->
        <form action="daftar_buku.php" method="GET" class="search-box" autocomplete="off">
            <input type="text" name="keyword" placeholder="Ketik judul buku, topik, atau nama pengarang..." value="<?= htmlspecialchars($keyword) ?>" autocomplete="off">

            <select name="kategori">
                <option value="">Semua Kategori Koleksi</option>
                <?php foreach ($kategoriList as $kat): ?>
                    <option value="<?= $kat['id'] ?>" <?= $kategoriId == $kat['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($kat['nama_kategori']) ?>
                    </option>
                <?php endforeach; ?>
            </select>

            <button type="submit" class="btn btn-primary">Cari Katalog</button>
            <?php if (!empty($keyword) || !empty($kategoriId)): ?>
                <a href="daftar_buku.php" class="btn-cancel">Reset</a>
            <?php endif; ?>
        </form>

        <!-- KATALOG GRID -->
        <div class="katalog-grid">
            <?php if (empty($data)): ?>
                <div class="card" style="grid-column: 1 / -1; text-align: center; padding: 48px 24px;">
                    <div style="width: 44px; height: 44px; border-radius: 50%; background: var(--slate-100); display: inline-flex; align-items: center; justify-content: center; margin-bottom: 12px; color: var(--slate-400);">
                        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <circle cx="11" cy="11" r="8"/>
                            <line x1="21" y1="21" x2="16.65" y2="16.65"/>
                        </svg>
                    </div>
                    <h3 style="margin-bottom: 6px;">Buku Tidak Ditemukan</h3>
                    <p style="color: var(--slate-500); margin: 0;">Tidak ada judul buku yang cocok dengan kata kunci pencarian Anda. Coba kata kunci lain atau reset filter.</p>
                </div>
            <?php else: ?>
                <?php foreach ($data as $row): 
                    $tersedia = $row['total_tersedia'] > 0;
                ?>
                    <div class="katalog-card">
                        <div>
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 8px; margin-bottom: 8px;">
                                <span class="badge-info" style="font-size: 11px;">
                                    <?= htmlspecialchars($row['nama_kategori'] ?? 'Umum') ?>
                                </span>
                                <span style="font-size: 11px; color: var(--slate-400); font-family: var(--font-mono);">
                                    <?= htmlspecialchars($row['kode_buku']) ?>
                                </span>
                            </div>

                            <h3><?= htmlspecialchars($row['judul']) ?></h3>

                            <p><strong>Penulis:</strong> <?= htmlspecialchars($row['pengarang'] ?: '-') ?></p>
                            <p><strong>Penerbit:</strong> <?= htmlspecialchars($row['penerbit'] ?: '-') ?> (<?= htmlspecialchars($row['tahun_terbit'] ?: '-') ?>)</p>
                        </div>

                        <div>
                            <div class="ketersediaan <?= $tersedia ? 'tersedia' : 'habis' ?>">
                                <?php if ($tersedia): ?>
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--success-solid); display: inline-block; flex-shrink: 0;"></span>
                                    <span><strong><?= $row['total_tersedia'] ?></strong> dari <?= $row['total_eksemplar'] ?> buku tersedia di rak</span>
                                <?php else: ?>
                                    <span style="width: 8px; height: 8px; border-radius: 50%; background: var(--danger-solid); display: inline-block; flex-shrink: 0;"></span>
                                    <span>Sedang dipinjam semua (0/<?= $row['total_eksemplar'] ?>)</span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <div class="info-tip">
            <strong>Petunjuk Peminjaman:</strong> Ambil fisik buku di rak perpustakaan, bawa ke meja sirkulasi, dan tunjukkan kartu RFID atau NIS Anda kepada petugas perpustakaan untuk proses peminjaman.
        </div>
<?php require 'includes/footer.php'; ?>