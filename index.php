<?php
session_start();

if (isset($_SESSION['admin_id'])) {
    header('Location: admin/dashboard.php');
    exit;
}
if (isset($_SESSION['anggota_id'])) {
    header('Location: siswa/dashboard.php');
    exit;
}
if (isset($_GET['error']) || (isset($_GET['success']) && $_GET['success'] === 'daftar')) {
    header('Location: login.php?' . $_SERVER['QUERY_STRING']);
    exit;
}

require_once 'config/koneksi.php';

$lamaPinjam = 7;

function e($v): string {
    return htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
}

$totalJudul    = $koneksi->query("SELECT COUNT(*) FROM buku")->fetchColumn() ?? 0;
$totalFisik    = $koneksi->query("SELECT COUNT(*) FROM buku_item")->fetchColumn() ?? 0;
$totalTersedia = $koneksi->query("SELECT COUNT(*) FROM buku_item WHERE status = 'tersedia'")->fetchColumn() ?? 0;
$totalAnggota  = $koneksi->query("SELECT COUNT(*) FROM anggota")->fetchColumn() ?? 0;

$koleksiBuku = $koneksi->query("
    SELECT buku.*, kategori.nama_kategori,
           (SELECT COUNT(*) FROM buku_item WHERE buku_item.buku_id = buku.id) AS total_eksemplar,
           (SELECT COUNT(*) FROM buku_item WHERE buku_item.buku_id = buku.id AND status = 'tersedia') AS total_tersedia
    FROM buku
    LEFT JOIN kategori ON buku.kategori_id = kategori.id
    ORDER BY buku.id DESC
    LIMIT 12
")->fetchAll();

$daftarKategori = [];
foreach ($koleksiBuku as $b) {
    $daftarKategori[$b['nama_kategori'] ?? 'Umum'] = true;
}
$daftarKategori = array_keys($daftarKategori);

$warnaSpine = ['#1F5C4D', '#B5472F', '#2F4C8C', '#8F6209', '#6A3F73', '#3A6C85'];

// Judul demo: lewati judul yang hanya angka (mis. data uji "3756")
$demoJudul = $koleksiBuku[0]['judul'] ?? 'Judul buku contoh';
foreach ($koleksiBuku as $b) {
    if (!ctype_digit(trim((string)$b['judul']))) { $demoJudul = $b['judul']; break; }
}
$persenTersedia = $totalFisik > 0 ? round(($totalTersedia / $totalFisik) * 100) : 0;
$contoh1 = $koleksiBuku[0]['judul'] ?? 'Judul buku contoh';
$contoh2 = $koleksiBuku[1]['judul'] ?? 'Judul buku lain';
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Pinjam.In - Perpustakaan Sekolah dengan Kartu RFID dan QR Code</title>
<meta name="description" content="Pinjam buku perpustakaan sekolah cukup dengan tap kartu pelajar RFID dan scan QR Code di setiap buku.">
<style>
:root{--paper:#F4F5F0;--white:#fff;--ink:#18201D;--muted:#5B6661;--line:#DADFD8;--baize:#0F4A40;--deep:#0A3730;--lamp:#F3B63F;--stamp:#B3342A;--go:#2FBF84;--ok:#1B7A55;--bad:#A8322B;
--serif:'Iowan Old Style','Palatino Linotype','Book Antiqua',Georgia,serif;--sans:system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;--mono:ui-monospace,'Cascadia Mono',Consolas,monospace;--wrap:1120px}
*,*::before,*::after{box-sizing:border-box}
html{scroll-behavior:smooth;scroll-padding-top:80px}
body{margin:0;font:16px/1.6 var(--sans);color:var(--ink);background:var(--paper);-webkit-font-smoothing:antialiased}
h1,h2,h3,p,ul,dl,dd{margin:0}ul{padding:0;list-style:none}a{color:inherit}button{font:inherit;color:inherit;cursor:pointer}
:focus-visible{outline:3px solid var(--lamp);outline-offset:3px;border-radius:4px}
.wrap{width:min(var(--wrap),100% - 48px);margin-inline:auto}
.sr-only{position:absolute;left:-9999px}

.btn{display:inline-flex;align-items:center;justify-content:center;padding:11px 20px;border-radius:10px;border:1.5px solid transparent;font-weight:600;font-size:15px;text-decoration:none;transition:background .15s,color .15s,transform .15s}
.btn:active{transform:translateY(1px)}
.btn-solid{background:var(--baize);color:#fff}.btn-solid:hover{background:var(--deep)}
.btn-line{border-color:var(--ink);background:transparent}.btn-line:hover{background:var(--ink);color:#fff}
.btn-lamp{background:var(--lamp);color:var(--ink)}.btn-lamp:hover{background:#fff}
.btn-lg{padding:14px 26px;font-size:16px}

.site-header{position:sticky;top:0;z-index:50;background:rgba(244,245,240,.92);backdrop-filter:blur(8px);border-bottom:1px solid transparent;transition:border-color .2s}
.site-header.is-stuck{border-color:var(--line)}
.site-header .wrap{display:flex;align-items:center;justify-content:space-between;height:66px;gap:24px}
.brand{display:flex;align-items:center;gap:10px;text-decoration:none;font:700 22px var(--serif);letter-spacing:-.01em}
.brand-mark{width:34px;height:34px;border-radius:9px;background:var(--baize);color:var(--lamp);display:grid;place-items:center}
.nav{display:flex;gap:28px}
.nav a{text-decoration:none;font-weight:500;font-size:15px;color:var(--muted);padding:6px 0;border-bottom:2px solid transparent}
.nav a:hover{color:var(--ink);border-bottom-color:var(--lamp)}
.header-actions{display:flex;gap:10px}.header-actions .btn{padding:8px 16px;font-size:14px}

.hero{padding:56px 0 72px}
.hero .wrap{display:grid;grid-template-columns:1fr 1.15fr;gap:52px;align-items:center}
.hero h1{font:700 clamp(2.6rem,5.2vw,4.2rem)/1.04 var(--serif);letter-spacing:-.02em;max-width:12ch}
.hero-lead{margin-top:22px;font-size:18px;color:var(--muted);max-width:44ch}
.hero-actions{margin-top:30px;display:flex;gap:12px;flex-wrap:wrap}
.hero-note{margin-top:18px;font-size:14px;color:var(--muted)}.hero-note a{color:var(--baize);font-weight:600}

/* meja sirkulasi */
.desk{background:var(--baize);color:#fff;border-radius:24px;padding:18px;box-shadow:0 30px 50px -30px rgba(10,55,48,.7)}
.desk-top{display:flex;justify-content:space-between;align-items:center;font-size:13px;color:#A9CBC3;margin-bottom:10px}
.desk-top b{font-weight:600;border:1px solid rgba(255,255,255,.3);border-radius:99px;padding:1px 9px;font-size:12px}
.screen{display:flex;gap:12px;align-items:center;background:#06201B;border-radius:12px;padding:14px 16px;min-height:62px;font:13.5px/1.45 var(--mono)}
.led{flex:none;width:10px;height:10px;border-radius:50%;background:#5F7C75;transition:.2s}
.desk:not([data-step="0"]) .led{background:var(--go);box-shadow:0 0 0 4px rgba(47,191,132,.22)}
.board{display:grid;grid-template-columns:repeat(3,1fr);gap:12px;margin-top:14px;grid-template-areas:"r s p" "c b n"}
.target{position:relative;height:150px;border-radius:16px;border:2px dashed rgba(255,255,255,.3);display:flex;align-items:flex-end;padding:10px;transition:.15s}
.target.is-hot{border-color:var(--lamp);background:rgba(243,182,63,.14)}
.target.is-done{border-style:solid;border-color:var(--go)}
.tl{display:flex;align-items:center;gap:7px;font-size:12.5px;font-weight:600;color:#A9CBC3}
.tl i{font-style:normal;width:19px;height:19px;border-radius:50%;border:1.5px solid #6D9188;display:grid;place-items:center;font-size:11px}
.target.is-next .tl{color:#fff}.target.is-next .tl i{background:#fff;color:var(--deep);border-color:#fff}
.target.is-done .tl i{background:var(--go);border-color:var(--go);color:#06201B}
.t-reader{grid-area:r}.t-scan{grid-area:s}.t-print{grid-area:p;border-style:solid;border-color:rgba(255,255,255,.12);background:var(--deep);overflow:hidden;flex-direction:column;justify-content:space-between;padding:12px 10px 10px}
.t-print.is-done{border-color:rgba(255,255,255,.12)}
.gfx{position:absolute;inset:0 0 34px;display:grid;place-items:center;pointer-events:none}
.rings{position:relative;width:54px;height:54px}
.rings::before,.rings::after{content:"";position:absolute;inset:0;border-radius:50%;border:2px solid rgba(255,255,255,.35)}
.rings::after{inset:14px}
.t-reader.is-reading .rings::before{animation:pulse .7s ease-out 1}
@keyframes pulse{from{transform:scale(.7);border-color:var(--lamp)}to{transform:scale(1.5);opacity:0}}
.win{position:relative;width:60px;height:60px;border-radius:10px;border:2px solid rgba(255,255,255,.35);overflow:hidden}
.win i{position:absolute;left:0;right:0;top:0;height:2px;background:#FF5A4D;box-shadow:0 0 10px 2px rgba(255,90,77,.7);opacity:0}
.t-scan.is-scanning .win i{animation:scan .9s ease-in-out}
@keyframes scan{0%{top:0;opacity:1}100%{top:calc(100% - 2px);opacity:1}}
.slot-bar{height:7px;border-radius:9px;background:#02100D}
.slip-wrap{height:0;overflow:hidden;margin:-1px 4px 0;transition:height .9s steps(9)}
.desk[data-step="2"] .slip-wrap{height:100px}
.slip{position:relative;height:100px;background:#fff;color:var(--ink);padding:8px 8px 0;font:9.5px/1.3 var(--mono);display:flex;flex-direction:column;gap:3px}
.slip-t{font-weight:700;border-bottom:1px dashed var(--line);padding-bottom:3px}
.slip-r{display:flex;justify-content:space-between;gap:6px;color:var(--muted)}
.slip-r b{font-weight:500;color:var(--ink);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;min-width:0}
.stamp{position:absolute;right:5px;bottom:7px;font:700 10px var(--mono);letter-spacing:.06em;color:var(--stamp);border:2.5px double var(--stamp);border-radius:4px;padding:2px 6px;transform:rotate(-9deg) scale(1.9);opacity:0;mix-blend-mode:multiply}
.desk[data-step="2"] .stamp{animation:stamp .3s cubic-bezier(.2,1.5,.4,1) 1s forwards}
@keyframes stamp{to{transform:rotate(-9deg) scale(1);opacity:.92}}

.cell{height:140px;border-radius:16px;background:rgba(0,0,0,.2);display:grid;place-items:center}
.c{grid-area:c}.b{grid-area:b}
.n{grid-area:n;background:none;display:flex;flex-direction:column;justify-content:center;gap:10px;padding:0 4px;font-size:13px;color:#A9CBC3}
.n button{align-self:flex-start;border:0;border-radius:8px;padding:7px 14px;background:#fff;color:var(--deep);font-weight:600;font-size:13px}
.item{--x:0px;--y:0px;--s:1;--r:0deg;position:relative;z-index:5;border:0;padding:0;background:none;touch-action:none;cursor:grab;transform:translate(var(--x),var(--y)) rotate(var(--r)) scale(var(--s));transition:transform .4s cubic-bezier(.3,1.3,.5,1),opacity .2s}
.item.dragging{transition:none;cursor:grabbing;z-index:30;--s:1.06;--r:-3deg}
.item.is-placed{--s:.78}
.item:disabled{cursor:default}.item:disabled:not(.is-placed){opacity:.4}
.item:not(:disabled):not(.dragging):not(.is-placed){animation:nudge 2.4s ease-in-out infinite}
@keyframes nudge{0%,70%,100%{transform:translate(0,0)}80%{transform:translate(0,-6px)}90%{transform:translate(0,0)}}
.card{display:flex;flex-direction:column;justify-content:space-between;width:124px;height:78px;padding:8px;background:#fff;color:var(--ink);border-radius:9px;text-align:left}
.card::before{content:"";width:24px;height:18px;border-radius:4px;background:linear-gradient(135deg,#EBC65E,#C9A23A)}
.card span{font-size:10.5px;font-weight:600;line-height:1.2}.card small{display:block;font-weight:400;color:var(--muted);font-size:9.5px}
.book{display:flex;flex-direction:column;justify-content:space-between;align-items:stretch;width:82px;height:110px;padding:8px 7px 7px 13px;background:#2F4C8C;color:#fff;border-radius:3px 8px 8px 3px;box-shadow:inset 6px 0 0 rgba(0,0,0,.22);text-align:left}
.book span{font:700 10.5px/1.2 var(--serif);display:-webkit-box;-webkit-line-clamp:3;-webkit-box-orient:vertical;overflow:hidden}
.qr{align-self:flex-end;width:38px;height:38px;background:#fff;border-radius:4px;padding:3px}.qr svg{display:block;width:100%;height:100%}

/* ringkasan */
.ledger{background:var(--white);border-block:1px solid var(--line)}
.ledger .wrap{display:flex;justify-content:space-between;align-items:center;gap:28px;flex-wrap:wrap;padding-block:22px}
.ledger p{max-width:62ch}.ledger strong{font-weight:700}
.meter{display:flex;align-items:center;gap:12px;font-size:13px;color:var(--muted)}
.meter-bar{width:200px;height:8px;border-radius:99px;background:var(--line);overflow:hidden}.meter-bar i{display:block;height:100%;background:var(--ok)}

/* rak */
.shelf-section{padding:88px 0 96px}
.section-head{max-width:580px}
.section-head h2{font:700 clamp(1.9rem,3.4vw,2.6rem)/1.1 var(--serif);letter-spacing:-.015em}
.section-head p{margin-top:12px;color:var(--muted);font-size:17px}
.tools{margin-top:30px;display:flex;gap:12px;flex-wrap:wrap;align-items:center}
.search{position:relative;flex:1 1 260px;max-width:360px}
.search svg{position:absolute;left:14px;top:50%;transform:translateY(-50%);color:var(--muted)}
.search input{width:100%;padding:12px 14px 12px 42px;border:1.5px solid var(--line);border-radius:10px;background:var(--white);font:inherit;font-size:15px;color:var(--ink)}
.search input:focus{outline:none;border-color:var(--baize);box-shadow:0 0 0 3px rgba(243,182,63,.5)}
.chips{display:flex;gap:8px;flex-wrap:wrap}
.chip{padding:8px 14px;border:1.5px solid var(--line);border-radius:99px;background:var(--white);font-size:14px;font-weight:500;transition:.15s}
.chip:hover{border-color:var(--ink)}.chip[aria-pressed="true"]{background:var(--baize);border-color:var(--baize);color:#fff}
.count{margin-top:14px;font-size:14px;color:var(--muted)}
.shelf-layout{margin-top:18px;display:grid;grid-template-columns:minmax(0,1fr) 340px;gap:32px;align-items:start}
.shelf-row{display:flex;align-items:flex-end;gap:7px;padding:50px 22px 0;min-height:340px;overflow-x:auto;overflow-y:hidden;background:#E8ECE4;border-bottom:16px solid var(--deep);border-radius:8px 8px 3px 3px;box-shadow:inset 0 14px 20px -16px rgba(0,0,0,.25)}
.spine{flex:none;position:relative;border:0;border-radius:3px 3px 0 0;color:#fff;padding:12px 0 12px;display:flex;flex-direction:column;align-items:center;justify-content:space-between;box-shadow:inset -4px 0 0 rgba(0,0,0,.14);transition:transform .25s cubic-bezier(.3,1.4,.5,1),opacity .2s,filter .2s}
.spine::before{content:"";position:absolute;top:12px;left:6px;right:6px;height:7px;border-block:2px solid rgba(255,255,255,.4)}
.spine-title{writing-mode:vertical-rl;margin-top:28px;max-height:calc(100% - 90px);overflow:hidden;text-overflow:ellipsis;white-space:nowrap;font:700 15px var(--serif)}
.spine-no{background:#fff;color:var(--ink);font:500 10px var(--mono);padding:1px 4px;border-radius:2px;margin-bottom:8px}
.spine-dot{width:9px;height:9px;border-radius:50%;background:#fff;border:2px solid rgba(0,0,0,.25)}
.spine.is-out .spine-dot{background:transparent;border-color:rgba(255,255,255,.7)}
.spine:hover{transform:translateY(-12px)}
.spine[aria-pressed="true"]{transform:translateY(-24px);box-shadow:0 18px 22px -10px rgba(0,0,0,.4),0 0 0 3px var(--lamp);z-index:2}
.spine.is-dim{opacity:.2;filter:grayscale(.7)}
.shelf-empty{display:none;padding-top:16px;color:var(--muted)}.shelf-empty.is-visible{display:block}
.panel{--c:var(--baize);background:var(--white);border:1px solid var(--line);border-top:7px solid var(--c);border-radius:14px;padding:24px;position:sticky;top:92px}
.panel-cat{display:inline-block;font-size:13px;font-weight:600;color:var(--c);background:var(--paper);padding:3px 10px;border-radius:99px}
.panel h3{margin-top:14px;font:700 25px/1.15 var(--serif)}
.panel-author{margin-top:6px;color:var(--muted)}
.panel dl{margin-top:18px;display:grid;grid-template-columns:auto 1fr;gap:6px 16px;font-size:15px;padding-top:16px;border-top:1px solid var(--line)}
.panel dt{color:var(--muted)}.panel dd{font-weight:500}
.dots{margin-top:20px;display:flex;gap:5px;flex-wrap:wrap}
.dots i{width:14px;height:18px;border-radius:2px 3px 3px 2px;background:var(--ok)}.dots i.is-out{background:#F8E1DF;border:1.5px solid var(--bad)}
.copies{margin-top:8px;font-size:14.5px;font-weight:600}.copies.ok{color:var(--ok)}.copies.bad{color:var(--bad)}
.panel .btn{margin-top:20px;width:100%}
.panel-hint{margin-top:12px;font-size:13.5px;color:var(--muted);text-align:center}.panel-hint a{color:var(--baize);font-weight:600}

/* peran */
.roles{padding:0 0 96px}
.tabs{margin-top:30px;display:inline-flex;gap:4px;padding:4px;background:var(--white);border:1px solid var(--line);border-radius:12px}
.tabs button{border:0;background:none;padding:9px 18px;border-radius:9px;font-weight:600;font-size:15px;color:var(--muted)}
.tabs button[aria-selected="true"]{background:var(--baize);color:#fff}
.pane{margin-top:24px;display:grid;grid-template-columns:1fr 1fr;gap:40px;align-items:center}
.pane[hidden]{display:none}
.pane h3{font:700 26px/1.2 var(--serif)}
.pane ul{margin-top:20px;display:grid;gap:12px}
.pane li{display:flex;gap:12px}.pane li svg{flex:none;margin-top:4px;color:var(--ok)}
.pane .btn{margin-top:26px}
.mock{background:var(--white);border:1px solid var(--line);border-radius:14px;overflow:hidden}
.mock-h{display:flex;justify-content:space-between;gap:10px;padding:13px 18px;border-bottom:1px solid var(--line);font-weight:600}
.mock-h small{font-weight:400;color:var(--muted)}
.mock-r{display:flex;justify-content:space-between;align-items:center;gap:14px;padding:14px 18px;border-bottom:1px solid var(--line);font-size:15px}
.mock-r:last-child{border:0}.mock-r span:first-child{min-width:0;overflow:hidden;text-overflow:ellipsis;white-space:nowrap}
.tag{flex:none;font-size:13px;font-weight:600;padding:2px 10px;border-radius:99px;background:#DDF3E8;color:var(--ok)}.tag.warn{background:#FBEBC8;color:#8A5A00}
.mini{flex:none;display:flex;align-items:center;gap:8px;font-size:13px;color:var(--muted)}
.mini i{display:block;width:70px;height:6px;border-radius:9px;background:var(--line);overflow:hidden}.mini b{display:block;height:100%;background:var(--ok)}

.cta{padding-bottom:88px}
.cta-box{display:flex;justify-content:space-between;align-items:center;gap:28px;flex-wrap:wrap;padding:40px;background:var(--baize);color:#fff;border-radius:22px}
.cta-box h2{font:700 clamp(1.6rem,2.8vw,2.2rem)/1.15 var(--serif);max-width:20ch}
.cta-box p{margin-top:8px;color:#BFDAD3;max-width:46ch}
.cta-actions{display:flex;gap:12px;flex-wrap:wrap}.cta-box .btn-line{border-color:#fff;color:#fff}.cta-box .btn-line:hover{background:#fff;color:var(--deep)}
.site-footer{border-top:1px solid var(--line);padding:30px 0 38px;font-size:14px;color:var(--muted)}
.site-footer .wrap{display:flex;justify-content:space-between;gap:20px;flex-wrap:wrap}
.footer-links{display:flex;gap:22px}.footer-links a{text-decoration:none}.footer-links a:hover{color:var(--ink);text-decoration:underline}

@media(max-width:960px){.hero .wrap,.shelf-layout,.pane{grid-template-columns:1fr}.hero .wrap{gap:40px}.panel{position:static}}
@media(max-width:760px){.nav{display:none}}
@media(max-width:560px){.wrap{width:min(var(--wrap),100% - 32px)}.header-actions .btn-line{display:none}.hero{padding-top:36px}
.board{grid-template-columns:1fr 1fr;grid-template-areas:"r s" "c b" "p p" "n n"}.t-print{height:150px}.meter-bar{width:140px}.cta-box{padding:28px 22px}}
@media(prefers-reduced-motion:reduce){html{scroll-behavior:auto}*,*::before,*::after{animation:none!important;transition:none!important}.desk[data-step="2"] .stamp{transform:rotate(-9deg);opacity:.92}}
</style>
</head>
<body>

<header class="site-header" id="header">
  <div class="wrap">
    <a href="index.php" class="brand" aria-label="Pinjam.In, beranda">
      <span class="brand-mark"><svg width="19" height="19" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5a2.5 2.5 0 0 1-2.5-2.5Z"/><path d="M9 7h6"/><path d="M9 11h6"/></svg></span>
      Pinjam.In
    </a>
    <nav class="nav" aria-label="Navigasi utama">
      <a href="#simulasi">Cara pinjam</a>
      <a href="#koleksi">Koleksi buku</a>
      <a href="#peran">Untuk siswa &amp; petugas</a>
    </nav>
    <div class="header-actions">
      <a href="daftar.php" class="btn btn-line">Daftar siswa</a>
      <a href="login.php" class="btn btn-solid">Masuk</a>
    </div>
  </div>
</header>

<main>
  <section class="hero" id="simulasi">
    <div class="wrap">
      <div>
        <h1>Pinjam buku cukup tap kartu pelajar.</h1>
        <p class="hero-lead">Setiap siswa punya kartu RFID, setiap buku punya kode QR sendiri. Peminjaman langsung tercatat di sistem, tanpa formulir kertas.</p>
        <div class="hero-actions">
          <a href="#koleksi" class="btn btn-solid btn-lg">Lihat koleksi buku</a>
          <a href="login.php" class="btn btn-line btn-lg">Masuk ke akun</a>
        </div>
        <p class="hero-note">Belum punya akun? <a href="daftar.php">Daftar sebagai siswa</a></p>
      </div>

      <div class="desk" id="desk" data-step="0">
        <div class="desk-top"><span>Coba sendiri di meja sirkulasi</span><b>Simulasi</b></div>
        <div class="screen"><span class="led" aria-hidden="true"></span><span id="screenTxt" role="status" aria-live="polite"></span></div>

        <div class="board">
          <div class="target t-reader" id="tReader">
            <div class="gfx"><div class="rings"></div></div>
            <span class="tl"><i>1</i>Tap kartu</span>
          </div>
          <div class="target t-scan" id="tScan">
            <div class="gfx"><div class="win"><i></i></div></div>
            <span class="tl"><i>2</i>Scan buku</span>
          </div>
          <div class="target t-print" id="tPrint">
            <div><div class="slot-bar"></div>
              <div class="slip-wrap"><div class="slip">
                <div class="slip-t">SLIP PINJAM</div>
                <div class="slip-r"><span>Siswa</span><b data-slip="nama"></b></div>
                <div class="slip-r"><span>Buku</span><b data-slip="buku"></b></div>
                <div class="slip-r"><span>Pinjam</span><b data-slip="pinjam"></b></div>
                <div class="slip-r"><span>Kembali</span><b data-slip="kembali"></b></div>
                <div class="stamp">DIPINJAM</div>
              </div></div>
            </div>
            <span class="tl"><i>3</i>Cap slip</span>
          </div>

          <div class="cell c">
            <button type="button" class="item card" id="itCard" aria-label="Kartu pelajar. Seret ke pembaca kartu, atau tekan Enter"><span>Siswa Contoh<small>Kartu pelajar</small></span></button>
          </div>
          <div class="cell b">
            <button type="button" class="item book" id="itBook" aria-label="Buku dengan kode QR. Seret ke pemindai, atau tekan Enter"><span><?= e($demoJudul) ?></span><div class="qr" id="qrBox"></div></button>
          </div>
          <div class="cell n">
            <p id="hint"></p>
            <button type="button" id="reset" hidden>Ulangi simulasi</button>
          </div>
        </div>
      </div>
    </div>
  </section>

  <section class="ledger" aria-label="Ringkasan koleksi">
    <div class="wrap">
      <p>Perpustakaan saat ini punya <strong><?= (int)$totalJudul ?> judul</strong> dalam <strong><?= (int)$totalFisik ?> eksemplar</strong>, dengan <strong><?= (int)$totalTersedia ?></strong> siap dipinjam. Sudah <strong><?= (int)$totalAnggota ?> siswa</strong> terdaftar.</p>
      <div class="meter">
        <div class="meter-bar" role="img" aria-label="<?= (int)$persenTersedia ?> persen eksemplar tersedia"><i style="width:<?= (int)$persenTersedia ?>%"></i></div>
        <span><?= (int)$persenTersedia ?>% tersedia</span>
      </div>
    </div>
  </section>

  <section class="shelf-section" id="koleksi">
    <div class="wrap">
      <div class="section-head">
        <h2>Koleksi buku terbaru</h2>
        <p>Klik punggung buku untuk menariknya dari rak. Titik putih di bawah judul artinya masih ada yang bisa dipinjam.</p>
      </div>

      <div class="tools">
        <label class="search">
          <span class="sr-only">Cari buku</span>
          <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.3-4.3"/></svg>
          <input type="search" id="bookSearch" placeholder="Cari judul, pengarang, atau penerbit" autocomplete="off">
        </label>
        <div class="chips" id="chips" role="group" aria-label="Filter">
          <?php if (count($daftarKategori) > 1): ?>
            <button type="button" class="chip" data-cat="" aria-pressed="true">Semua</button>
            <?php foreach ($daftarKategori as $kat): ?>
              <button type="button" class="chip" data-cat="<?= e($kat) ?>" aria-pressed="false"><?= e($kat) ?></button>
            <?php endforeach; ?>
          <?php endif; ?>
          <button type="button" class="chip" id="onlyAvail" aria-pressed="false">Tersedia saja</button>
        </div>
      </div>
      <p class="count" id="count" aria-live="polite"></p>

      <?php if (empty($koleksiBuku)): ?>
        <p style="margin-top:32px;color:var(--muted)">Belum ada buku yang terdaftar. Petugas bisa menambahkannya dari dashboard admin.</p>
      <?php else: ?>
      <div class="shelf-layout">
        <div>
          <div class="shelf-row" id="shelfRow">
            <?php foreach ($koleksiBuku as $buku):
              $kat    = $buku['nama_kategori'] ?? 'Umum';
              $warna  = $warnaSpine[crc32($kat) % count($warnaSpine)];
              $tinggi = 190 + (((int)$buku['id'] * 53) % 80);
              $lebar  = 50 + ((mb_strlen((string)$buku['judul']) % 4) * 6);
              $ada    = (int)$buku['total_tersedia'];
            ?>
            <button type="button" class="spine <?= $ada > 0 ? '' : 'is-out' ?>"
              style="height:<?= $tinggi ?>px;width:<?= $lebar ?>px;background:<?= $warna ?>"
              aria-pressed="false"
              aria-label="<?= e($buku['judul']) ?>, <?= $ada > 0 ? $ada . ' eksemplar tersedia' : 'semua sedang dipinjam' ?>"
              data-judul="<?= e($buku['judul']) ?>"
              data-pengarang="<?= e($buku['pengarang']) ?>"
              data-penerbit="<?= e($buku['penerbit']) ?>"
              data-tahun="<?= e($buku['tahun_terbit'] ?? '-') ?>"
              data-kategori="<?= e($kat) ?>"
              data-warna="<?= e($warna) ?>"
              data-total="<?= (int)$buku['total_eksemplar'] ?>"
              data-tersedia="<?= $ada ?>"
              data-search="<?= e(mb_strtolower($buku['judul'] . ' ' . $buku['pengarang'] . ' ' . $buku['penerbit'] . ' ' . $kat)) ?>">
              <span class="spine-title"><?= e($buku['judul']) ?></span>
              <span class="spine-no"><?= str_pad((string)(int)$buku['id'], 3, '0', STR_PAD_LEFT) ?></span>
              <span class="spine-dot" aria-hidden="true"></span>
            </button>
            <?php endforeach; ?>
          </div>
          <p class="shelf-empty" id="shelfEmpty">Tidak ada buku yang cocok. Hapus kata kunci atau pilih kategori Semua.</p>
        </div>

        <aside class="panel" id="panel" aria-live="polite">
          <span class="panel-cat" id="pCat"></span>
          <h3 id="pTitle"></h3>
          <p class="panel-author" id="pAuthor"></p>
          <dl><dt>Penerbit</dt><dd id="pPub"></dd><dt>Tahun terbit</dt><dd id="pYear"></dd></dl>
          <div class="dots" id="pDots"></div>
          <div class="copies" id="pCopies"></div>
          <a href="login.php" class="btn btn-solid">Masuk untuk meminjam</a>
          <p class="panel-hint">Belum punya akun? <a href="daftar.php">Daftar dulu</a></p>
        </aside>
      </div>
      <?php endif; ?>
    </div>
  </section>

  <section class="roles" id="peran">
    <div class="wrap">
      <div class="section-head">
        <h2>Satu sistem, dua peran</h2>
        <p>Pilih peranmu untuk melihat apa yang bisa dilakukan.</p>
      </div>
      <div class="tabs" role="tablist" aria-label="Pilih peran">
        <button type="button" role="tab" id="tabSiswa" aria-selected="true" aria-controls="paneSiswa">Saya siswa</button>
        <button type="button" role="tab" id="tabPetugas" aria-selected="false" aria-controls="panePetugas" tabindex="-1">Saya petugas</button>
      </div>

      <div class="pane" id="paneSiswa" role="tabpanel" aria-labelledby="tabSiswa">
        <div>
          <h3>Pantau pinjamanmu sendiri.</h3>
          <ul>
            <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Pinjam buku cukup dengan tap kartu pelajar, tidak perlu mengetik NIS.</li>
            <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Cek batas waktu pengembalian kapan saja dari dashboard.</li>
            <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Lihat riwayat buku yang pernah dipinjam.</li>
          </ul>
          <a href="daftar.php" class="btn btn-solid">Daftar siswa baru</a>
        </div>
        <div class="mock" aria-label="Contoh tampilan dashboard siswa">
          <div class="mock-h">Pinjaman saya <small>Contoh tampilan</small></div>
          <div class="mock-r"><span><?= e($contoh1) ?></span><span class="tag">Sisa 5 hari</span></div>
          <div class="mock-r"><span><?= e($contoh2) ?></span><span class="tag warn">Sisa 1 hari</span></div>
        </div>
      </div>

      <div class="pane" id="panePetugas" role="tabpanel" aria-labelledby="tabPetugas" hidden>
        <div>
          <h3>Kelola buku dan sirkulasi dari satu tempat.</h3>
          <ul>
            <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Setiap eksemplar punya kode QR sendiri, jadi stok mudah dilacak.</li>
            <li><svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.6" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>Catat peminjaman dan pengembalian dengan scan, bukan tulis tangan.</li>
          </ul>
          <a href="login.php" class="btn btn-solid">Masuk sebagai petugas</a>
        </div>
        <div class="mock" aria-label="Contoh tampilan stok buku">
          <div class="mock-h">Stok eksemplar <small>Data koleksi saat ini</small></div>
          <?php foreach (array_slice($koleksiBuku, 0, 3) as $bk):
            $tt = (int)$bk['total_eksemplar']; $aa = (int)$bk['total_tersedia'];
            $pp = $tt > 0 ? round($aa / $tt * 100) : 0; ?>
          <div class="mock-r"><span><?= e($bk['judul']) ?></span><span class="mini"><i><b style="width:<?= $pp ?>%"></b></i><?= $aa ?>/<?= $tt ?></span></div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
  </section>

  <section class="cta">
    <div class="wrap">
      <div class="cta-box">
        <div>
          <h2>Daftar sekali, pinjam buku kapan saja.</h2>
          <p>Setelah akun aktif, bawa kartu pelajar ke meja sirkulasi dan pilih buku dari rak.</p>
        </div>
        <div class="cta-actions">
          <a href="daftar.php" class="btn btn-lamp btn-lg">Daftar siswa baru</a>
          <a href="login.php" class="btn btn-line btn-lg">Masuk</a>
        </div>
      </div>
    </div>
  </section>
</main>

<footer class="site-footer">
  <div class="wrap">
    <div>&copy; <?= date('Y') ?> Pinjam.In, Perpustakaan Sekolah</div>
    <nav class="footer-links" aria-label="Tautan footer">
      <a href="#koleksi">Koleksi</a><a href="daftar.php">Daftar</a><a href="login.php">Masuk</a>
    </nav>
  </div>
</footer>

<script>
(function () {
  'use strict';
  var $ = function (i) { return document.getElementById(i); };

  /* header: garis muncul saat scroll */
  var header = $('header');
  function stuck() { header.classList.toggle('is-stuck', window.scrollY > 8); }
  window.addEventListener('scroll', stuck, { passive: true }); stuck();

  /* QR dekoratif */
  function drawQR(seed) {
    var n = 9, cells = '', s = seed || 7;
    function rnd() { s = (s * 9301 + 49297) % 233280; return s / 233280; }
    function finder(x, y) {
      var o = '';
      for (var i = 0; i < 3; i++) for (var j = 0; j < 3; j++) { if (i === 1 && j === 1) continue; o += '<rect x="' + (x + i) + '" y="' + (y + j) + '" width="1" height="1"/>'; }
      return o;
    }
    for (var r = 0; r < n; r++) for (var c = 0; c < n; c++) {
      if ((r < 3 && c < 3) || (r < 3 && c > n - 4) || (r > n - 4 && c < 3)) continue;
      if (rnd() > 0.5) cells += '<rect x="' + c + '" y="' + r + '" width="1" height="1"/>';
    }
    cells += finder(0, 0) + finder(n - 3, 0) + finder(0, n - 3);
    return '<svg viewBox="0 0 ' + n + ' ' + n + '" fill="#18201D" shape-rendering="crispEdges" aria-hidden="true">' + cells + '</svg>';
  }

  /* ---------- Simulasi meja sirkulasi (drag and drop) ---------- */
  var desk = $('desk');
  if (desk) {
    var judul = <?= json_encode($demoJudul, JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>;
    var lama = <?= (int)$lamaPinjam ?>;
    var today = new Date(), due = new Date(today.getTime() + lama * 864e5);
    var short = { day: 'numeric', month: 'short', year: 'numeric' };
    var dueLong = due.toLocaleDateString('id-ID', { day: 'numeric', month: 'long', year: 'numeric' });

    $('qrBox').innerHTML = drawQR(judul.length + 3);

    var txt = $('screenTxt'), hint = $('hint'), reset = $('reset');
    var tR = $('tReader'), tS = $('tScan'), tP = $('tPrint');
    var itC = $('itCard'), itB = $('itBook');
    var slip = {};
    desk.querySelectorAll('[data-slip]').forEach(function (el) { slip[el.dataset.slip] = el; });

    var step = 0, busy = false;
    var msgs = [
      'Siap. Seret kartu pelajar ke pembaca kartu.',
      'Kartu terbaca: Siswa Contoh. Sekarang seret buku ke pemindai.',
      'Dipinjam. Kembalikan sebelum ' + dueLong + '.'
    ];
    var hints = ['Seret kartu ke atas, atau klik kartunya.', 'Seret buku ke atas, atau klik bukunya.', ''];

    function say(m) { txt.textContent = m; }

    function render() {
      desk.dataset.step = step;
      say(msgs[step]);
      hint.textContent = hints[step];
      reset.hidden = step !== 2;
      itC.disabled = step !== 0;
      itB.disabled = step !== 1;
      tR.classList.toggle('is-next', step === 0); tR.classList.toggle('is-done', step > 0);
      tS.classList.toggle('is-next', step === 1); tS.classList.toggle('is-done', step > 1);
      tP.classList.toggle('is-done', step === 2);
      var on = step === 2;
      var v = { nama: 'Siswa Contoh', buku: judul, pinjam: today.toLocaleDateString('id-ID', short), kembali: due.toLocaleDateString('id-ID', short) };
      Object.keys(slip).forEach(function (k) { slip[k].textContent = on ? v[k] : ''; });
    }

    function makeDrag(item, target, wrong, wrongMsg, done) {
      var sx, sy, ox, oy, on = false, moved = false, just = false;
      item._x = 0; item._y = 0;
      function put() { item.style.setProperty('--x', item._x + 'px'); item.style.setProperty('--y', item._y + 'px'); }
      function over(t, e) {
        var r = t.getBoundingClientRect(), m = 20;
        return e.clientX > r.left - m && e.clientX < r.right + m && e.clientY > r.top - m && e.clientY < r.bottom + m;
      }
      function home() { item._x = 0; item._y = 0; put(); }
      function snap() {
        var a = item.getBoundingClientRect(), b = target.getBoundingClientRect();
        item._x += (b.left + b.width / 2) - (a.left + a.width / 2);
        item._y += (b.top + b.height / 2) - (a.top + a.height / 2) - 8;
        put(); item.classList.add('is-placed'); done();
      }
      item.addEventListener('pointerdown', function (e) {
        if (item.disabled || busy) return;
        on = true; moved = false; sx = e.clientX; sy = e.clientY; ox = item._x; oy = item._y;
        item.setPointerCapture(e.pointerId); item.classList.add('dragging');
      });
      item.addEventListener('pointermove', function (e) {
        if (!on) return;
        var dx = e.clientX - sx, dy = e.clientY - sy;
        if (Math.abs(dx) + Math.abs(dy) > 6) moved = true;
        item._x = ox + dx; item._y = oy + dy; put();
        target.classList.toggle('is-hot', over(target, e));
      });
      function end(e, cancel) {
        if (!on) return;
        on = false; item.classList.remove('dragging'); target.classList.remove('is-hot');
        if (!moved) return;
        just = true; setTimeout(function () { just = false; }, 60);
        if (!cancel && over(target, e)) snap();
        else { if (!cancel && over(wrong, e)) say(wrongMsg); home(); }
      }
      item.addEventListener('pointerup', function (e) { end(e, false); });
      item.addEventListener('pointercancel', function (e) { end(e, true); });
      item.addEventListener('click', function () {
        if (just || item.disabled || busy) return;
        snap();
      });
    }

    makeDrag(itC, tR, tS, 'Itu pemindai buku. Kartu ditap di pembaca kartu.', function () {
      busy = true; tR.classList.add('is-reading');
      setTimeout(function () { tR.classList.remove('is-reading'); busy = false; step = 1; render(); }, 700);
    });
    makeDrag(itB, tS, tR, 'Itu pembaca kartu. Buku dipindai di pemindai QR.', function () {
      busy = true; tS.classList.add('is-scanning');
      setTimeout(function () { tS.classList.remove('is-scanning'); busy = false; step = 2; render(); }, 900);
    });

    reset.addEventListener('click', function () {
      [itC, itB].forEach(function (it) { it.classList.remove('is-placed'); it._x = 0; it._y = 0; it.style.setProperty('--x', '0px'); it.style.setProperty('--y', '0px'); });
      step = 0; render(); itC.focus();
    });

    render();
  }

  /* ---------- Rak buku ---------- */
  var row = $('shelfRow');
  if (row) {
    var spines = Array.prototype.slice.call(row.querySelectorAll('.spine'));
    var search = $('bookSearch'), empty = $('shelfEmpty'), count = $('count'), avail = $('onlyAvail');
    var catChips = Array.prototype.slice.call(document.querySelectorAll('.chip[data-cat]'));
    var activeCat = '', onlyAvail = false;
    var panel = $('panel');

    function select(sp) {
      spines.forEach(function (s) { s.setAttribute('aria-pressed', s === sp ? 'true' : 'false'); });
      var d = sp.dataset, total = parseInt(d.total, 10) || 0, ada = parseInt(d.tersedia, 10) || 0;
      panel.style.setProperty('--c', d.warna);
      $('pCat').textContent = d.kategori;
      $('pTitle').textContent = d.judul;
      $('pAuthor').textContent = 'Oleh ' + (d.pengarang || '-');
      $('pPub').textContent = d.penerbit || '-';
      $('pYear').textContent = d.tahun || '-';
      var dots = $('pDots'); dots.innerHTML = '';
      if (total > 0 && total <= 16) for (var i = 0; i < total; i++) { var x = document.createElement('i'); if (i >= ada) x.className = 'is-out'; dots.appendChild(x); }
      var c = $('pCopies');
      c.textContent = ada > 0 ? ada + ' dari ' + total + ' eksemplar tersedia' : 'Semua eksemplar sedang dipinjam';
      c.className = 'copies ' + (ada > 0 ? 'ok' : 'bad');
    }

    function apply() {
      var q = (search.value || '').toLowerCase().trim(), shown = 0;
      spines.forEach(function (s) {
        var m = (!q || (s.dataset.search || '').indexOf(q) !== -1) &&
                (!activeCat || s.dataset.kategori === activeCat) &&
                (!onlyAvail || parseInt(s.dataset.tersedia, 10) > 0);
        s.classList.toggle('is-dim', !m); s.tabIndex = m ? 0 : -1;
        if (m) shown++;
      });
      empty.classList.toggle('is-visible', shown === 0);
      count.textContent = 'Menampilkan ' + shown + ' dari ' + spines.length + ' buku';
    }

    spines.forEach(function (s) { s.addEventListener('click', function () { select(s); }); });
    search.addEventListener('input', apply);
    catChips.forEach(function (c) {
      c.addEventListener('click', function () {
        activeCat = c.dataset.cat;
        catChips.forEach(function (x) { x.setAttribute('aria-pressed', x === c ? 'true' : 'false'); });
        apply();
      });
    });
    avail.addEventListener('click', function () {
      onlyAvail = !onlyAvail; avail.setAttribute('aria-pressed', onlyAvail ? 'true' : 'false'); apply();
    });
    select(spines[0]); apply();
  }

  /* ---------- Tab peran ---------- */
  var tabs = [['tabSiswa', 'paneSiswa'], ['tabPetugas', 'panePetugas']];
  function showTab(i) {
    tabs.forEach(function (t, j) {
      $(t[0]).setAttribute('aria-selected', i === j ? 'true' : 'false');
      $(t[0]).tabIndex = i === j ? 0 : -1;
      $(t[1]).hidden = i !== j;
    });
  }
  tabs.forEach(function (t, i) {
    $(t[0]).addEventListener('click', function () { showTab(i); });
    $(t[0]).addEventListener('keydown', function (e) {
      if (e.key === 'ArrowRight' || e.key === 'ArrowLeft') { var n = 1 - i; showTab(n); $(tabs[n][0]).focus(); }
    });
  });
})();
</script>
</body>
</html>