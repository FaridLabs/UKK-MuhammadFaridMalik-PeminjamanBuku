<?php
require '../config/cek_session_admin.php';
require '../config/koneksi.php';

$pageTitle   = 'Dashboard';
$currentPage = 'dashboard';

$bln = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];

/* ---------- 1. Statistik kartu ---------- */
$hitung = fn(string $sql) => (int) ($koneksi->query($sql)->fetchColumn() ?: 0);

$totalJudul     = $hitung("SELECT COUNT(*) FROM buku");
$totalEksemplar = $hitung("SELECT COUNT(*) FROM buku_item");
$totalTersedia  = $hitung("SELECT COUNT(*) FROM buku_item WHERE status = 'tersedia'");
$totalAnggota   = $hitung("SELECT COUNT(*) FROM anggota");
$totalDipinjam  = $hitung("SELECT COUNT(*) FROM transaksi WHERE status = 'dipinjam'");
$totalTerlambat = $hitung("SELECT COUNT(*) FROM transaksi WHERE status = 'dipinjam' AND tgl_kembali_rencana < CURDATE()");
$persenTersedia = $totalEksemplar > 0 ? (int) round($totalTersedia / $totalEksemplar * 100) : 0;

$kartu = [
    ['book',     'green',  $totalJudul,     'Judul Buku Terdaftar',   'di katalog perpustakaan',       $totalEksemplar . ' eksemplar'],
    ['bookmark', 'blue',   $totalEksemplar, 'Total Eksemplar Fisik',  'tiap eksemplar punya kode',     'dari ' . $totalJudul . ' judul'],
    ['check',    'purple', $totalTersedia,  'Eksemplar Tersedia',     'siap dipinjam',                 $persenTersedia . '% tersedia'],
    ['users',    'orange', $totalAnggota,   'Anggota Terdaftar',      'siswa pengguna perpustakaan',   null],
    ['open',     'red',    $totalDipinjam,  'Buku Sedang Dipinjam',   'saat ini',                      $totalDipinjam === 0 ? 'Semua tersedia' : 'Perlu dikembalikan'],
    ['clock',    'slate',  $totalTerlambat, 'Terlambat Dikembalikan', 'melewati batas kembali',        $totalTerlambat === 0 ? 'Aman' : 'Perlu ditindak'],
];

/* ---------- 2. Tren 30 hari (tampilan 7/30 hari dipilih di browser) ---------- */
$mulai = date('Y-m-d', strtotime('-29 days'));
$ambil = function (string $kolom) use ($koneksi, $mulai) {
    $st = $koneksi->prepare("SELECT DATE($kolom) AS d, COUNT(*) AS c FROM transaksi WHERE $kolom IS NOT NULL AND DATE($kolom) >= :s GROUP BY DATE($kolom)");
    $st->execute([':s' => $mulai]);
    return $st->fetchAll(PDO::FETCH_KEY_PAIR);
};
$perPinjam  = $ambil('tgl_pinjam');
$perKembali = $ambil('tgl_kembali_aktual');

$trend = ['labels' => [], 'p' => [], 'k' => []];
for ($i = 29; $i >= 0; $i--) {
    $ts = strtotime("-$i days");
    $d  = date('Y-m-d', $ts);
    $trend['labels'][] = date('j', $ts) . ' ' . $bln[(int) date('n', $ts) - 1];
    $trend['p'][]      = (int) ($perPinjam[$d] ?? 0);
    $trend['k'][]      = (int) ($perKembali[$d] ?? 0);
}

/* ---------- 3. Distribusi kategori ---------- */
$kategoriStats = $koneksi->query("
    SELECT k.nama_kategori, COUNT(b.id) AS total_buku
    FROM kategori k
    LEFT JOIN buku b ON b.kategori_id = k.id
    GROUP BY k.id
    HAVING total_buku > 0
    ORDER BY total_buku DESC
")->fetchAll();

$warnaKat = ['#23A06A', '#2F6FED', '#F5B72E', '#8B5CF6', '#E0568A', '#14A3A8'];
$kat = [];
foreach ($kategoriStats as $i => $ks) {
    if ($i < 5 || count($kategoriStats) <= 6) {
        $kat[] = ['nama' => $ks['nama_kategori'], 'n' => (int) $ks['total_buku']];
    } else {
        if (!isset($kat[5])) $kat[5] = ['nama' => 'Lainnya', 'n' => 0];
        $kat[5]['n'] += (int) $ks['total_buku'];
    }
}
$totalKat = array_sum(array_column($kat, 'n'));
$keliling = 2 * M_PI * 74;

/* ---------- 4. Aktivitas terbaru (peminjaman dan pengembalian sebagai kejadian terpisah) ---------- */
$bagian = "t.id, t.status, t.tgl_kembali_rencana, a.nama_lengkap, a.nis, b.judul, bi.kode_item
    FROM transaksi t
    LEFT JOIN anggota a ON t.anggota_id = a.id
    LEFT JOIN buku_item bi ON t.buku_item_id = bi.id
    LEFT JOIN buku b ON bi.buku_id = b.id";
$aktivitas = $koneksi->query("
    (SELECT 'pinjam' AS jenis, t.tgl_pinjam AS waktu, $bagian WHERE t.tgl_pinjam IS NOT NULL)
    UNION ALL
    (SELECT 'kembali' AS jenis, t.tgl_kembali_aktual AS waktu, $bagian WHERE t.tgl_kembali_aktual IS NOT NULL)
    ORDER BY waktu DESC, id DESC
    LIMIT 5
")->fetchAll();
$hariIni = date('Y-m-d');

function fmtWaktu(string $w, array $bln): string {
    $ts = strtotime($w);
    $s  = date('j', $ts) . ' ' . $bln[(int) date('n', $ts) - 1] . ' ' . date('Y', $ts);
    if (strlen($w) > 10 && substr($w, 11, 8) !== '00:00:00') $s .= ' ' . date('H:i', $ts);
    return $s;
}

/* ---------- Script halaman ---------- */
ob_start();
?>
<script>
(function () {
    var T = <?= json_encode($trend, JSON_UNESCAPED_UNICODE) ?>;
    var svg = document.getElementById('trendSvg'), tip = document.getElementById('trendTip');
    var W = 640, H = 260, L = 34, R = 12, TP = 10, B = 30, range = 7;

    function kurva(arr, x, y) {
        var base = H - B, pts = arr.map(function (v, i) { return [x(i), y(v)]; });
        var d = 'M' + pts[0][0] + ' ' + pts[0][1];
        for (var i = 0; i < pts.length - 1; i++) {
            var p0 = pts[i - 1] || pts[i], p1 = pts[i], p2 = pts[i + 1], p3 = pts[i + 2] || p2;
            var c1y = Math.min(base, Math.max(TP, p1[1] + (p2[1] - p0[1]) / 6));
            var c2y = Math.min(base, Math.max(TP, p2[1] - (p3[1] - p1[1]) / 6));
            d += ' C' + (p1[0] + (p2[0] - p0[0]) / 6) + ' ' + c1y + ' ' + (p2[0] - (p3[0] - p1[0]) / 6) + ' ' + c2y + ' ' + p2[0] + ' ' + p2[1];
        }
        return { d: d, pts: pts };
    }

    function gambar() {
        var n = range, off = T.labels.length - n;
        var lab = T.labels.slice(off), p = T.p.slice(off), k = T.k.slice(off);
        var mx = Math.max(4, Math.max.apply(null, p.concat(k))), step = Math.ceil(mx / 4), top = step * 4;
        var x = function (i) { return L + (W - L - R) * i / (n - 1); };
        var y = function (v) { return TP + (H - TP - B) * (1 - v / top); };
        var base = H - B, s = '';

        for (var t = 0; t <= 4; t++) {
            var yy = y(t * step);
            s += '<line x1="' + L + '" x2="' + (W - R) + '" y1="' + yy + '" y2="' + yy + '" stroke="#E3E9E6"/>' +
                 '<text x="' + (L - 8) + '" y="' + (yy + 4) + '" text-anchor="end" class="ax">' + (t * step) + '</text>';
        }
        var tiap = n > 10 ? 5 : 1;
        lab.forEach(function (l, i) {
            if ((n - 1 - i) % tiap === 0) s += '<text x="' + x(i) + '" y="' + (H - 8) + '" text-anchor="middle" class="ax">' + l + '</text>';
        });
        [[k, '#3CC48A', 'url(#gk)'], [p, '#0E7A52', 'url(#gp)']].forEach(function (sr) {
            var c = kurva(sr[0], x, y);
            s += '<path d="' + c.d + ' L' + x(n - 1) + ' ' + base + ' L' + x(0) + ' ' + base + ' Z" fill="' + sr[2] + '"/>' +
                 '<path d="' + c.d + '" fill="none" stroke="' + sr[1] + '" stroke-width="2.5" stroke-linecap="round"/>';
            c.pts.forEach(function (pt) { s += '<circle cx="' + pt[0] + '" cy="' + pt[1] + '" r="' + (n > 10 ? 2.5 : 3.8) + '" fill="' + sr[1] + '"/>'; });
        });
        s += '<line id="guide" y1="' + TP + '" y2="' + base + '" stroke="#9FB3A9" stroke-dasharray="3 3" opacity="0"/>';
        svg.querySelector('#layer').innerHTML = s;

        svg.onmousemove = function (e) {
            var r = svg.getBoundingClientRect(), vx = (e.clientX - r.left) / r.width * W;
            var i = Math.max(0, Math.min(n - 1, Math.round((vx - L) / ((W - L - R) / (n - 1)))));
            var g = svg.querySelector('#guide');
            g.setAttribute('x1', x(i)); g.setAttribute('x2', x(i)); g.setAttribute('opacity', 1);
            tip.innerHTML = '<b>' + lab[i] + '</b>Peminjaman: ' + p[i] + '<br>Pengembalian: ' + k[i];
            tip.style.left = (x(i) / W * 100) + '%';
            tip.classList.add('show');
        };
        svg.onmouseleave = function () { tip.classList.remove('show'); var g = svg.querySelector('#guide'); if (g) g.setAttribute('opacity', 0); };
    }

    document.querySelectorAll('.seg button').forEach(function (b) {
        b.addEventListener('click', function () {
            range = parseInt(b.dataset.r, 10);
            document.querySelectorAll('.seg button').forEach(function (x) { x.setAttribute('aria-pressed', x === b ? 'true' : 'false'); });
            gambar();
        });
    });
    gambar();
})();
</script>
<?php
$extraScripts = ob_get_clean();

require 'includes/header.php';
?>

<div class="d-head">
    <div>
        <h1>Selamat Datang, <?= htmlspecialchars($_SESSION['admin_nama'] ?? 'Admin') ?></h1>
        <p>Kelola perpustakaan sekolah dengan mudah, cepat, dan terintegrasi.</p>
    </div>
    <div class="d-act">
        <a href="peminjaman.php" class="btn-d main"><?= ic('plus') ?>Peminjaman Baru</a>
        <a href="pengembalian.php" class="btn-d"><?= ic('return') ?>Scan Pengembalian</a>
    </div>
</div>

<section class="d-stats" aria-label="Ringkasan perpustakaan">
    <?php foreach ($kartu as [$ikon, $warna, $angka, $judul, $sub, $tag]): ?>
    <article class="stat t-<?= $warna ?>">
        <div class="stat-ico"><?= ic($ikon, 23) ?></div>
        <strong><?= $angka ?></strong>
        <h3><?= $judul ?></h3>
        <p><?= $sub ?></p>
        <?php if ($tag): ?><span class="tag"><?= htmlspecialchars($tag) ?></span><?php endif; ?>
    </article>
    <?php endforeach; ?>
</section>

<div class="d-grid">
    <section class="panel" aria-labelledby="hTren">
        <div class="panel-h">
            <div class="panel-ico"><?= ic('chart') ?></div>
            <div>
                <h2 id="hTren">Tren Sirkulasi</h2>
                <p>Perbandingan volume peminjaman dan pengembalian buku.</p>
            </div>
            <div class="seg" role="group" aria-label="Rentang waktu">
                <button type="button" data-r="7" aria-pressed="true">7 Hari</button>
                <button type="button" data-r="30" aria-pressed="false">30 Hari</button>
            </div>
        </div>
        <div class="legend">
            <span><i style="background:#0E7A52"></i>Peminjaman</span>
            <span><i style="background:#3CC48A"></i>Pengembalian</span>
        </div>
        <div class="trend">
            <svg id="trendSvg" viewBox="0 0 640 260" role="img" aria-label="Grafik garis peminjaman dan pengembalian per hari">
                <defs>
                    <linearGradient id="gp" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#0E7A52" stop-opacity=".22"/><stop offset="1" stop-color="#0E7A52" stop-opacity="0"/></linearGradient>
                    <linearGradient id="gk" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#3CC48A" stop-opacity=".2"/><stop offset="1" stop-color="#3CC48A" stop-opacity="0"/></linearGradient>
                </defs>
                <g id="layer"></g>
            </svg>
            <div class="tip" id="trendTip"></div>
        </div>
    </section>

    <section class="panel" aria-labelledby="hKat">
        <div class="panel-h">
            <div class="panel-ico"><?= ic('grid') ?></div>
            <div>
                <h2 id="hKat">Distribusi Kategori Koleksi</h2>
                <p>Persentase judul buku berdasarkan kategori.</p>
            </div>
        </div>
        <?php if ($totalKat === 0): ?>
            <p class="empty">Belum ada buku yang dikelompokkan ke kategori.</p>
        <?php else: ?>
        <div class="donut-wrap">
            <div class="donut">
                <svg viewBox="0 0 200 200" role="img" aria-label="Diagram donat kategori buku">
                    <g transform="rotate(-90 100 100)" fill="none" stroke-width="30">
                    <?php $cum = 0; foreach ($kat as $i => $kt):
                        $len = $kt['n'] / $totalKat * $keliling; ?>
                        <circle cx="100" cy="100" r="74" stroke="<?= $warnaKat[$i] ?>"
                                stroke-dasharray="<?= max(0, $len - (count($kat) > 1 ? 2 : 0)) ?> <?= $keliling ?>"
                                stroke-dashoffset="<?= -$cum ?>"/>
                    <?php $cum += $len; endforeach; ?>
                    </g>
                </svg>
                <div class="donut-c"><b><?= $totalJudul ?></b><small>Total Judul</small></div>
            </div>
            <ul class="dl">
                <?php foreach ($kat as $i => $kt): ?>
                <li><i style="background:<?= $warnaKat[$i] ?>"></i><span><?= htmlspecialchars($kt['nama']) ?></span><em><?= $kt['n'] ?> (<?= round($kt['n'] / $totalKat * 100) ?>%)</em></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
    </section>
</div>

<section class="act" aria-labelledby="hAkt">
    <div class="act-top">
        <div class="panel-ico"><?= ic('history') ?></div>
        <div class="panel-h" style="display:block">
            <h2 id="hAkt">Aktivitas Terbaru</h2>
            <p>Transaksi peminjaman dan pengembalian terakhir di perpustakaan.</p>
        </div>
        <a href="transaksi.php" class="more">Lihat Semua <?= ic('chev', 14) ?></a>
    </div>

    <div class="tbl">
        <?php if (empty($aktivitas)): ?>
            <p class="tbl-empty">Belum ada transaksi tercatat. Catat peminjaman pertama lewat tombol "Peminjaman Baru".</p>
        <?php else: ?>
        <table>
            <thead><tr><th>Waktu</th><th>Jenis</th><th>Judul Buku</th><th>Nama Anggota</th><th>Keterangan</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($aktivitas as $r):
                $pinjam    = $r['jenis'] === 'pinjam';
                $terlambat = $pinjam && $r['status'] === 'dipinjam' && $hariIni > $r['tgl_kembali_rencana'];
                if ($pinjam) {
                    $ket = $terlambat ? 'Terlambat, batas kembali ' . fmtWaktu($r['tgl_kembali_rencana'], $bln) : 'Buku dipinjam (1 eksemplar)';
                    $cls = $terlambat ? 'late' : '';
                } else {
                    $ket = 'Buku dikembalikan (1 eksemplar)';
                    $cls = 'ok';
                }
            ?>
                <tr>
                    <td><?= fmtWaktu($r['waktu'], $bln) ?></td>
                    <td><span class="jenis <?= $pinjam ? 'j-pinjam' : 'j-kembali' ?>"><?= ic($pinjam ? 'loan' : 'return', 22) ?><?= $pinjam ? 'Peminjaman' : 'Pengembalian' ?></span></td>
                    <td><?= htmlspecialchars($r['judul'] ?? 'Buku') ?><?php if (!empty($r['kode_item'])): ?><small><?= htmlspecialchars($r['kode_item']) ?></small><?php endif; ?></td>
                    <td><?= htmlspecialchars($r['nama_lengkap'] ?? 'Siswa') ?><?php if (!empty($r['nis'])): ?><small><?= htmlspecialchars($r['nis']) ?></small><?php endif; ?></td>
                    <td class="ket <?= $cls ?>"><?= htmlspecialchars($ket) ?></td>
                    <td class="go"><a href="transaksi.php" aria-label="Lihat transaksi" style="color:inherit"><?= ic('chev', 16) ?></a></td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</section>

<?php require 'includes/footer.php'; ?>