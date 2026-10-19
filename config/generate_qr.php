<?php
require 'cek_session_admin.php';
require 'koneksi.php';

$bukuId  = $_GET['buku_id'] ?? null;
$itemId  = $_GET['item_id'] ?? null;
$kode    = trim($_GET['kode'] ?? '');
$from    = $_GET['from'] ?? '';
$newQty  = isset($_GET['new_qty']) ? (int)$_GET['new_qty'] : null;

$labels   = [];
$bukuInfo = null;

if (!empty($bukuId)) {
    // Mode Struk Buku: Ambil eksemplar buku
    $stmtBuku = $koneksi->prepare("
        SELECT buku.*, kategori.nama_kategori 
        FROM buku 
        LEFT JOIN kategori ON buku.kategori_id = kategori.id 
        WHERE buku.id = :id
    ");
    $stmtBuku->bindParam(':id', $bukuId);
    $stmtBuku->execute();
    $bukuInfo = $stmtBuku->fetch();

    if (!$bukuInfo) {
        header('Location: ../admin/buku.php');
        exit;
    }

    $stmtItems = $koneksi->prepare("
        SELECT * FROM buku_item 
        WHERE buku_id = :buku_id 
        ORDER BY id ASC
    ");
    $stmtItems->bindParam(':buku_id', $bukuId);
    $stmtItems->execute();
    $rawItems = $stmtItems->fetchAll();

    $total = count($rawItems);

    // Jika dipanggil setelah generate baru dan ada parameter new_qty, bisa ambil yang terakhir saja jika diminta
    if ($newQty !== null && $newQty > 0 && $newQty < $total && isset($_GET['only_new'])) {
        $rawItems = array_slice($rawItems, -$newQty);
    }

    foreach ($rawItems as $idx => $it) {
        $labels[] = [
            'id'            => $it['id'],
            'kode_item'     => $it['kode_item'],
            'judul'         => $bukuInfo['judul'],
            'pengarang'     => $bukuInfo['pengarang'] ?? '-',
            'nama_kategori' => $bukuInfo['nama_kategori'] ?? 'Umum',
            'no_fisik'      => $idx + 1,
            'total_fisik'   => $total,
            'buku_id'       => $bukuInfo['id']
        ];
    }

} elseif (!empty($itemId)) {
    // Mode Satuan berdasarkan item_id
    $stmtItem = $koneksi->prepare("
        SELECT buku_item.*, buku.judul, buku.pengarang, kategori.nama_kategori
        FROM buku_item
        JOIN buku ON buku_item.buku_id = buku.id
        LEFT JOIN kategori ON buku.kategori_id = kategori.id
        WHERE buku_item.id = :id
        LIMIT 1
    ");
    $stmtItem->bindParam(':id', $itemId);
    $stmtItem->execute();
    $it = $stmtItem->fetch();

    if (!$it) {
        header('Location: ../admin/buku.php');
        exit;
    }

    // Hitung posisi nomor urut fisik
    $stmtCount = $koneksi->prepare("
        SELECT COUNT(*) AS pos 
        FROM buku_item 
        WHERE buku_id = :buku_id AND id <= :item_id
    ");
    $stmtCount->bindParam(':buku_id', $it['buku_id']);
    $stmtCount->bindParam(':item_id', $it['id']);
    $stmtCount->execute();
    $pos = $stmtCount->fetchColumn() ?: 1;

    $stmtTot = $koneksi->prepare("SELECT COUNT(*) FROM buku_item WHERE buku_id = :buku_id");
    $stmtTot->bindParam(':buku_id', $it['buku_id']);
    $stmtTot->execute();
    $tot = $stmtTot->fetchColumn() ?: 1;

    $bukuInfo = ['judul' => $it['judul'], 'id' => $it['buku_id']];
    $labels[] = [
        'id'            => $it['id'],
        'kode_item'     => $it['kode_item'],
        'judul'         => $it['judul'],
        'pengarang'     => $it['pengarang'] ?? '-',
        'nama_kategori' => $it['nama_kategori'] ?? 'Umum',
        'no_fisik'      => $pos,
        'total_fisik'   => $tot,
        'buku_id'       => $it['buku_id']
    ];

} elseif (!empty($kode)) {
    // Fallback: Mode berdasarkan string kode
    $stmtItem = $koneksi->prepare("
        SELECT buku_item.*, buku.judul, buku.pengarang, kategori.nama_kategori
        FROM buku_item
        JOIN buku ON buku_item.buku_id = buku.id
        LEFT JOIN kategori ON buku.kategori_id = kategori.id
        WHERE buku_item.kode_item = :kode
        LIMIT 1
    ");
    $stmtItem->bindParam(':kode', $kode);
    $stmtItem->execute();
    $it = $stmtItem->fetch();

    if ($it) {
        $bukuInfo = ['judul' => $it['judul'], 'id' => $it['buku_id']];
        $labels[] = [
            'id'            => $it['id'],
            'kode_item'     => $it['kode_item'],
            'judul'         => $it['judul'],
            'pengarang'     => $it['pengarang'] ?? '-',
            'nama_kategori' => $it['nama_kategori'] ?? 'Umum',
            'no_fisik'      => 1,
            'total_fisik'   => 1,
            'buku_id'       => $it['buku_id']
        ];
    } else {
        $labels[] = [
            'id'            => 0,
            'kode_item'     => $kode,
            'judul'         => 'Label Buku',
            'pengarang'     => '-',
            'nama_kategori' => '-',
            'no_fisik'      => 1,
            'total_fisik'   => 1,
            'buku_id'       => 0
        ];
    }
} else {
    header('Location: ../admin/buku.php');
    exit;
}

$backUrl = (!empty($bukuInfo) && !empty($bukuInfo['id'])) 
    ? "../admin/buku_item.php?buku_id=" . $bukuInfo['id'] 
    : "../admin/buku.php";
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Label QR Buku - <?= !empty($bukuInfo) ? htmlspecialchars($bukuInfo['judul']) : 'Pinjam.In' ?></title>
    <script src="../assets/js/qrcode.min.js"></script>
    <style>
        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif;
            background: #f1f5f9;
            color: #0f172a;
            padding: 24px 16px;
            line-height: 1.4;
        }

        /* Top Action Bar (tidak dicetak) */
        .no-print {
            max-width: 960px;
            margin: 0 auto 20px auto;
            background: #ffffff;
            padding: 18px 24px;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            box-shadow: 0 4px 14px rgba(0,0,0,0.06);
        }
        .banner-info {
            background: #ecfdf5;
            color: #065f46;
            border: 1px solid #a7f3d0;
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 14px;
            font-size: 13.5px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .top-bar-title h2 {
            font-size: 1.25rem;
            color: #0f172a;
            margin-bottom: 4px;
            font-weight: 700;
        }
        .top-bar-title p {
            font-size: 0.88rem;
            color: #64748b;
        }

        .controls-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            margin-top: 16px;
            padding-top: 14px;
            border-top: 1px solid #f1f5f9;
        }

        .grid-selector {
            display: flex;
            gap: 8px;
            align-items: center;
            font-size: 13px;
            color: #475569;
        }
        .grid-selector span {
            font-weight: 600;
            color: #334155;
        }
        .layout-btn {
            border: 1px solid #cbd5e1;
            background: #f8fafc;
            color: #334155;
            padding: 6px 12px;
            border-radius: 6px;
            cursor: pointer;
            font-size: 12.5px;
            font-weight: 600;
            transition: all 0.15s ease;
        }
        .layout-btn:hover {
            background: #e2e8f0;
        }
        .layout-btn.active {
            background: #2563eb;
            color: #ffffff;
            border-color: #2563eb;
        }

        .top-bar-actions {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }
        .btn-print {
            background: #2563eb;
            color: #ffffff;
            border: none;
            padding: 9px 20px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13.5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: background 0.15s;
        }
        .btn-print:hover { 
            background: #1d4ed8; 
        }
        .btn-back {
            background: #ffffff;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 8px 14px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
            font-weight: 500;
            transition: all 0.15s ease;
        }
        .btn-back:hover { 
            background: #f1f5f9; 
            color: #0f172a; 
        }

        .print-tip {
            font-size: 12px;
            color: #64748b;
            margin-top: 10px;
            background: #f8fafc;
            padding: 6px 12px;
            border-radius: 6px;
            display: inline-block;
        }

        /* Lembar Kertas Cetak (Simulasi A4 di layar) */
        .page-sheet {
            max-width: 960px;
            margin: 0 auto;
            background: #ffffff;
            padding: 24px;
            border-radius: 10px;
            box-shadow: 0 4px 16px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
        }

        /* Grid Label */
        .labels-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 14px;
        }
        .labels-grid.cols-2 {
            grid-template-columns: repeat(2, 1fr);
            gap: 16px;
        }
        .labels-grid.cols-4 {
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
        }

        /* Kartu Label Stiker Buku */
        .label-card {
            border: 1.5px dashed #94a3b8;
            border-radius: 8px;
            background: #ffffff;
            padding: 10px 12px;
            display: flex;
            align-items: center;
            gap: 12px;
            page-break-inside: avoid;
            break-inside: avoid;
            position: relative;
            background-color: #fafbfc;
        }

        /* Tanda Gunting Potong */
        .cut-mark {
            position: absolute;
            top: -9px;
            right: 8px;
            background: #ffffff;
            padding: 0 4px;
            font-size: 10px;
            color: #94a3b8;
            font-family: monospace;
        }

        /* Area QR Code */
        .label-qr-section {
            flex-shrink: 0;
            text-align: center;
            background: #ffffff;
            padding: 6px;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        .label-qr-section .qr-target {
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .label-qr-section canvas,
        .label-qr-section img {
            display: block;
            margin: 0 auto;
        }
        .label-copy-badge {
            margin-top: 4px;
            font-size: 10px;
            font-weight: 700;
            color: #1e293b;
            background: #e2e8f0;
            padding: 2px 6px;
            border-radius: 4px;
            display: inline-block;
            letter-spacing: 0.3px;
        }

        /* Area Info Buku */
        .label-info-section {
            flex: 1;
            min-width: 0;
            display: flex;
            flex-direction: column;
            justify-content: center;
        }
        .label-lib-brand {
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 0.8px;
            color: #2563eb;
            text-transform: uppercase;
            margin-bottom: 2px;
            display: flex;
            align-items: center;
            justify-content: space-between;
        }
        .label-category-pill {
            font-size: 8.5px;
            background: #f1f5f9;
            color: #475569;
            padding: 1px 5px;
            border-radius: 3px;
            font-weight: 600;
            border: 1px solid #e2e8f0;
            text-transform: none;
            letter-spacing: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
            max-width: 90px;
        }
        .label-book-code {
            font-family: 'Consolas', 'Courier New', monospace;
            font-size: 14px;
            font-weight: 800;
            color: #0f172a;
            letter-spacing: 0.8px;
            background: #ffffff;
            border: 1.5px solid #0f172a;
            padding: 3px 8px;
            border-radius: 4px;
            display: inline-block;
            margin: 3px 0 4px 0;
            width: fit-content;
        }
        .label-book-title {
            font-size: 12px;
            font-weight: 700;
            color: #1e293b;
            line-height: 1.25;
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
            margin-bottom: 2px;
        }
        .label-book-meta {
            font-size: 10.5px;
            color: #64748b;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        /* Kustomisasi saat kolom 2 (Label lebih besar) */
        .labels-grid.cols-2 .label-card {
            padding: 14px 18px;
            gap: 16px;
        }
        .labels-grid.cols-2 .label-book-code {
            font-size: 16px;
            padding: 4px 10px;
        }
        .labels-grid.cols-2 .label-book-title {
            font-size: 13.5px;
        }
        .labels-grid.cols-2 .label-book-meta {
            font-size: 11.5px;
        }

        /* Kustomisasi saat kolom 4 (Label lebih kecil) */
        .labels-grid.cols-4 .label-card {
            padding: 8px 10px;
            gap: 8px;
        }
        .labels-grid.cols-4 .label-book-code {
            font-size: 12px;
            padding: 2px 6px;
        }
        .labels-grid.cols-4 .label-book-title {
            font-size: 11px;
            -webkit-line-clamp: 1;
        }
        .labels-grid.cols-4 .label-lib-brand {
            font-size: 8.5px;
        }

        /* PENGATURAN CETAK PRINTER BIASA (@media print) */
        @media print {
            @page {
                size: A4 portrait;
                margin: 8mm;
            }
            html, body {
                background: #ffffff !important;
                padding: 0 !important;
                margin: 0 !important;
                color: #000000 !important;
                -webkit-print-color-adjust: exact !important;
                print-color-adjust: exact !important;
            }
            .no-print {
                display: none !important;
            }
            .page-sheet {
                max-width: 100% !important;
                padding: 0 !important;
                margin: 0 !important;
                box-shadow: none !important;
                border: none !important;
            }
            .labels-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 5mm !important;
            }
            .labels-grid.cols-2 {
                grid-template-columns: repeat(2, 1fr) !important;
                gap: 6mm !important;
            }
            .labels-grid.cols-4 {
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 4mm !important;
            }
            .label-card {
                background: #ffffff !important;
                border: 1px dashed #475569 !important;
                page-break-inside: avoid !important;
                break-inside: avoid !important;
            }
            .label-qr-section {
                border: 1px solid #cbd5e1 !important;
            }
            .label-book-code {
                border: 1.5px solid #000000 !important;
                color: #000000 !important;
            }
            .cut-mark {
                display: inline-block !important;
                background: #ffffff !important;
                color: #64748b !important;
            }
        }
    </style>
</head>
<body>

    <!-- TOP ACTION BAR (Hanya terlihat di layar) -->
    <div class="no-print">
        <?php if ($from === 'tambah'): ?>
            <div class="banner-info">
                <span>✅</span>
                <div>
                    <strong>Buku berhasil ditambahkan!</strong> Sistem telah membuat <strong><?= count($labels) ?> kode QR unik</strong> untuk setiap eksemplar buku. Silakan cetak label untuk ditempelkan pada fisik buku.
                </div>
            </div>
        <?php endif; ?>

        <div class="top-bar-title">
            <h2>Pratinjau Cetak Label QR Buku (<?= count($labels) ?> Eksemplar)</h2>
            <p>Format label stiker untuk printer biasa (Kertas A4 / HVS / Kertas Stiker Label).</p>
        </div>

        <div class="controls-row">
            <div class="grid-selector">
                <span>Pilihan Kolom Cetak:</span>
                <button type="button" class="layout-btn" id="btn-col-2" onclick="setLayout(2)">2 Kolom (Besar)</button>
                <button type="button" class="layout-btn active" id="btn-col-3" onclick="setLayout(3)">3 Kolom (Standar A4)</button>
                <button type="button" class="layout-btn" id="btn-col-4" onclick="setLayout(4)">4 Kolom (Kompak)</button>
            </div>

            <div class="top-bar-actions">
                <button class="btn-print" onclick="window.print()">
                    🖨️ Cetak Label Sekarang
                </button>
                <a href="../admin/buku.php" class="btn-back">&laquo; Ke Data Buku</a>
                <?php if (!empty($bukuInfo) && !empty($bukuInfo['id'])): ?>
                    <a href="../admin/buku_item.php?buku_id=<?= $bukuInfo['id'] ?>" class="btn-back">Kelola Eksemplar</a>
                <?php endif; ?>
            </div>
        </div>

        <div class="print-tip">
            💡 <strong>Tips Printer:</strong> Pada jendela pratinjau cetak browser, pilih <em>Ukuran Kertas: A4</em> dan centang <em>Cetak Gambar Latar (Background graphics)</em> agar tampilan border optimal.
        </div>
    </div>

    <!-- LEMBAR CETAK LABEL A4 -->
    <div class="page-sheet">
        <div class="labels-grid" id="labels-grid">
            <?php foreach ($labels as $idx => $lbl): ?>
                <div class="label-card">
                    <span class="cut-mark">✂ gunting</span>

                    <!-- QR CODE SECTION -->
                    <div class="label-qr-section">
                        <div class="qr-target" data-code="<?= htmlspecialchars($lbl['kode_item'], ENT_QUOTES) ?>"></div>
                        <div class="label-copy-badge">
                            #<?= $lbl['no_fisik'] ?> / <?= $lbl['total_fisik'] ?>
                        </div>
                    </div>

                    <!-- INFO BUKU SECTION -->
                    <div class="label-info-section">
                        <div class="label-lib-brand">
                            <span>PINJAM.IN</span>
                            <span class="label-category-pill"><?= htmlspecialchars($lbl['nama_kategori']) ?></span>
                        </div>
                        <div class="label-book-code"><?= htmlspecialchars($lbl['kode_item']) ?></div>
                        <div class="label-book-title" title="<?= htmlspecialchars($lbl['judul']) ?>">
                            <?= htmlspecialchars($lbl['judul']) ?>
                        </div>
                        <div class="label-book-meta">
                            ✍ <?= htmlspecialchars($lbl['pengarang']) ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>

    <script>
        function setLayout(cols) {
            const grid = document.getElementById('labels-grid');
            const btn2 = document.getElementById('btn-col-2');
            const btn3 = document.getElementById('btn-col-3');
            const btn4 = document.getElementById('btn-col-4');

            grid.classList.remove('cols-2', 'cols-4');
            btn2.classList.remove('active');
            btn3.classList.remove('active');
            btn4.classList.remove('active');

            if (cols === 2) {
                grid.classList.add('cols-2');
                btn2.classList.add('active');
            } else if (cols === 4) {
                grid.classList.add('cols-4');
                btn4.classList.add('active');
            } else {
                btn3.classList.add('active');
            }
        }

        // Render QR Code tajam & proporsional untuk printer standar
        function renderAllQRCodes() {
            const qrElements = document.querySelectorAll('.qr-target');
            const qrSize = 90; // Ukuran proporsional untuk label buku A4

            qrElements.forEach(function (el) {
                const text = el.getAttribute('data-code');
                if (text && (!el.hasChildNodes || !el.hasChildNodes())) {
                    new QRCode(el, {
                        text: text,
                        width: qrSize,
                        height: qrSize,
                        colorDark: "#000000",
                        colorLight: "#ffffff",
                        correctLevel: QRCode.CorrectLevel.M
                    });
                }
            });
        }

        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', renderAllQRCodes);
        } else {
            renderAllQRCodes();
        }
    </script>
</body>
</html>