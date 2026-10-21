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

// Jika ada foto sendiri (mis. foto rak buku perpustakaan sekolah), taruh di sini dan otomatis dipakai.
$fotoHero = 'assets/img/login-hero.jpg';
$pakaiFoto = file_exists(__DIR__ . '/' . $fotoHero);

// Rak buku buram untuk ilustrasi (deterministik, tidak berubah tiap refresh)
mt_srand(7);
$warnaBuku = ['#3E7D5A', '#2C5C86', '#C8742C', '#B9503F', '#E6D3A8', '#6B8F71', '#8A5A3C', '#D8B24A', '#4C6A92'];
$rakSvg = '';
foreach ([128, 222, 316] as $dasar) {
    $x = 72;
    while ($x < 520) {
        $w = mt_rand(9, 17); $h = mt_rand(48, 78);
        $rakSvg .= '<rect x="' . $x . '" y="' . ($dasar - $h) . '" width="' . $w . '" height="' . $h . '" fill="' . $warnaBuku[mt_rand(0, 8)] . '"/>';
        $x += $w + mt_rand(0, 2);
    }
}
$tumpukan = [
    ['Pemrograman', '#1F5A3E', 0, 0],
    ['Jaringan Komputer', '#2C5C86', -8, 52],
    ['Basis Data', '#C8742C', 6, 104],
    ['Desain Web', '#1E4B38', -4, 156],
];
?>
<!DOCTYPE html>
<html lang="id">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Masuk Sistem - Pinjam.In Perpustakaan</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=Caveat:wght@500&display=swap" rel="stylesheet">
<style>
:root{--g:#217350;--g-dark:#1A5C41;--g-text:#1F7A4D;--g-soft:#E7F3EB;--ink:#14232B;--muted:#5F6D73;--line:#D5DCDE;--bad:#A8322B;
--sans:'Plus Jakarta Sans',system-ui,-apple-system,'Segoe UI',Roboto,sans-serif;--hand:'Caveat','Segoe Script','Bradley Hand','Brush Script MT',cursive}
*,*::before,*::after{box-sizing:border-box}
html,body{height:100%}
body{margin:0;font-family:var(--sans);color:var(--ink);background:#F7FAF8;-webkit-font-smoothing:antialiased}
h1,h2,p{margin:0}
a{color:inherit}
:focus-visible{outline:3px solid rgba(33,115,80,.45);outline-offset:2px}

.page{display:grid;grid-template-columns:47% 53%;min-height:100vh}

/* ===== PANEL KIRI ===== */
.left{position:relative;overflow:hidden;background:#fff;min-height:100vh;display:flex;flex-direction:column}
.left::before{content:"";position:absolute;top:-150px;right:-70px;width:380px;height:380px;border-radius:50%;background:var(--g-soft);opacity:.75}
.left-content{position:relative;z-index:2;flex:none;padding:clamp(22px,4vh,44px) clamp(32px,4.6vw,72px) 0}
.brand{display:flex;align-items:center;gap:12px;text-decoration:none}
.brand svg{width:50px;height:42px;flex:none}
.brand-name{font-size:28px;font-weight:700;letter-spacing:-.02em;line-height:1}
.brand-name b{color:var(--g-text);font-weight:700}
.brand-sub{display:block;margin-top:5px;font-size:14px;color:var(--muted);font-weight:500}
.headline{margin-top:clamp(24px,5.5vh,60px);font-size:clamp(1.7rem,2.6vw,2.6rem);line-height:1.18;white-space:nowrap;font-weight:700;letter-spacing:-.02em}
.headline span{display:block;color:var(--g-text)}
.lead{margin-top:12px;font-size:clamp(14px,1.15vw,17px);line-height:1.55;color:var(--muted);max-width:30em}
.features{margin-top:clamp(18px,3.4vh,34px);display:grid;grid-template-columns:repeat(4,auto);justify-content:start;gap:clamp(18px,2.3vw,40px)}
.feat-ico{width:46px;height:46px;border-radius:13px;background:var(--g-soft);color:var(--g);display:grid;place-items:center}
.feat b{display:block;margin-top:10px;font-size:13.5px;font-weight:600}
.feat small{display:block;margin-top:3px;font-size:12px;color:#7B878C}

.art{position:relative;flex:1;min-height:230px;margin-top:clamp(16px,3vh,32px);z-index:1}
.art-img{position:absolute;inset:0;clip-path:url(#waveTop)}
.art-img svg,.art-img img{display:block;width:100%;height:100%;object-fit:cover}
.wave{position:absolute;left:0;bottom:0;width:100%;height:clamp(90px,19vh,170px);z-index:2}
.tagline{position:absolute;left:clamp(28px,4vw,56px);bottom:clamp(22px,4.5vh,48px);z-index:3;color:#fff;font-family:var(--hand);font-size:clamp(22px,2.1vw,30px);line-height:1.1;transform:rotate(-7deg);transform-origin:left bottom}
.tagline i{display:block;width:150px;height:3px;margin-top:8px;border-radius:3px;background:#fff;transform:rotate(-3deg);opacity:.9}

/* ===== PANEL KANAN ===== */
.right{position:relative;display:flex;flex-direction:column;align-items:center;min-height:100vh;padding:18px clamp(20px,4vw,56px) 16px;overflow:hidden}
.right::after{content:"";position:absolute;right:-190px;top:56%;width:340px;height:340px;border-radius:50%;background:#EAF3EE;opacity:.8;z-index:0}
.topbar{position:relative;z-index:1;width:100%;display:flex;justify-content:flex-end}
.back{display:inline-flex;align-items:center;gap:7px;padding:8px 15px;border:1px solid var(--line);border-radius:99px;background:rgba(255,255,255,.8);font-size:13.5px;font-weight:600;color:var(--g-text);text-decoration:none;transition:background .15s,border-color .15s}
.back:hover{background:#fff;border-color:var(--g)}
.card-wrap{position:relative;z-index:1;flex:1;width:100%;padding-block:14px;display:flex;align-items:center;justify-content:center}
.card{width:min(100%,578px);background:#fff;border-radius:20px;padding:clamp(22px,3.4vh,36px) clamp(24px,4vw,50px);box-shadow:0 18px 50px -20px rgba(20,35,43,.16),0 2px 8px rgba(20,35,43,.04)}
.card-head{text-align:center}
.card-head svg{width:72px;height:60px}
.card-brand{margin-top:0;font-size:30px;font-weight:700;letter-spacing:-.02em;line-height:1.1}
.card-brand b{color:var(--g-text);font-weight:700}
.card-tag{margin-top:4px;font-size:14px;color:var(--muted)}
.welcome{margin-top:clamp(16px,3vh,30px);text-align:center;font-size:25px;font-weight:700;letter-spacing:-.015em}
.welcome-sub{margin-top:6px;text-align:center;font-size:14.5px;color:var(--muted)}

.alert{margin-top:20px;padding:12px 14px;border-radius:10px;font-size:14px;font-weight:500;line-height:1.45}
.alert-error{background:#FBE9E7;color:var(--bad);border:1px solid #F1C7C2}
.alert-success{background:var(--g-soft);color:var(--g-dark);border:1px solid #C3E2CF}

form{margin-top:clamp(16px,2.8vh,28px)}
.field+.field{margin-top:16px}
.field label{display:block;margin-bottom:7px;font-size:14.5px;font-weight:600}
.input{position:relative}
.input>svg.lead-ico{position:absolute;left:16px;top:50%;transform:translateY(-50%);width:20px;height:20px;color:var(--ink);pointer-events:none}
.input input{width:100%;height:48px;padding:0 48px 0 50px;border:1.5px solid var(--line);border-radius:11px;background:#fff;font:inherit;font-size:15.5px;color:var(--ink);transition:border-color .15s,box-shadow .15s}
.input input::placeholder{color:#8D989D}
.input input:focus{outline:none;border-color:var(--g);box-shadow:0 0 0 4px rgba(33,115,80,.14)}
.eye{position:absolute;right:6px;top:50%;transform:translateY(-50%);width:40px;height:40px;border:0;background:none;border-radius:8px;color:var(--ink);display:grid;place-items:center;cursor:pointer}
.eye:hover{background:#F0F4F2}
.eye svg{width:21px;height:21px}
.eye .off{display:none}.eye[aria-pressed="true"] .on{display:none}.eye[aria-pressed="true"] .off{display:block}
.submit{margin-top:20px;width:100%;height:48px;border:0;border-radius:10px;background:var(--g);color:#fff;font:inherit;font-size:17px;font-weight:600;display:flex;align-items:center;justify-content:center;gap:10px;cursor:pointer;transition:background .15s,transform .15s}
.submit:hover{background:var(--g-dark)}.submit:active{transform:translateY(1px)}
.submit svg{width:20px;height:20px;transition:transform .2s}.submit:hover svg{transform:translateX(3px)}
.divider{margin-top:20px;border-top:1px solid #E6EBEC}
.card-foot{margin-top:16px;text-align:center;font-size:14.5px;color:var(--muted)}
.card-foot a{color:var(--g-text);font-weight:600;text-decoration:none}.card-foot a:hover{text-decoration:underline}

.bottom{position:relative;z-index:1;text-align:center;font-size:12.5px;color:#7B878C}
.secure{display:inline-flex;align-items:center;gap:8px;color:#6B787D}
.secure svg{width:17px;height:17px;color:#6A8A79}
.bottom p{margin-top:8px}

@media(max-width:1100px){.features{grid-template-columns:repeat(2,auto);row-gap:22px}}
@media(max-width:900px){
  .page{grid-template-columns:1fr}
  .left{display:none}
  .right{padding-top:20px}
}
@media(max-width:1100px) and (min-width:901px){.headline{white-space:normal}}
@media(max-height:700px){.card-head svg{width:56px;height:47px}.card-tag{display:none}.welcome-sub{display:none}.welcome{margin-top:14px}}
@media(max-width:480px){.card{padding:26px 20px}.welcome{font-size:24px}}
@media(prefers-reduced-motion:reduce){*{transition:none!important}}
</style>
</head>
<body>

<!-- bentuk potongan gelombang untuk foto -->
<svg width="0" height="0" style="position:absolute" aria-hidden="true">
  <defs>
    <clipPath id="waveTop" clipPathUnits="objectBoundingBox"><path d="M0,0.14 C0.18,0.02 0.42,-0.01 0.62,0.06 C0.8,0.12 0.93,0.08 1,0 L1,1 L0,1 Z"/></clipPath>
    <symbol id="logo" viewBox="0 0 48 40">
      <path d="M24 15C23 6 29 2 37 3C37 11 32 16 24 15Z" fill="#2E9A67"/>
      <path d="M24 15C25 11 28 8 31 6" fill="none" stroke="#fff" stroke-width="1.2" stroke-linecap="round" opacity=".7"/>
      <path d="M3 13Q3 11 5.2 11.2Q14 12 24 17.5V36Q14 31 5.2 31Q3 31 3 29Z" fill="#1F6E47"/>
      <path d="M45 13Q45 11 42.8 11.2Q34 12 24 17.5V36Q34 31 42.8 31Q45 31 45 29Z" fill="#2A8A5A"/>
      <path d="M7 15.5Q14 16.5 21 20.5M7 20Q14 21 21 25" fill="none" stroke="#fff" stroke-width="1.1" stroke-linecap="round" opacity=".45"/>
    </symbol>
  </defs>
</svg>

<div class="page">

  <!-- ===== KIRI ===== -->
  <section class="left" aria-hidden="false">
    <div class="left-content">
      <a href="index.php" class="brand" aria-label="Pinjam.In, beranda">
        <svg><use href="#logo"/></svg>
        <span>
          <span class="brand-name">Pinjam<b>.In</b></span>
          <span class="brand-sub">Perpustakaan Digital Sekolah</span>
        </span>
      </a>

      <h2 class="headline">Membaca Hari Ini,<span>Membangun Masa Depan</span></h2>
      <p class="lead">Sistem Peminjaman Buku Perpustakaan Sekolah yang mudah, cepat, dan terintegrasi.</p>

      <div class="features">
        <div class="feat">
          <div class="feat-ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="currentColor"><path d="M12 6.2C10.4 5 8.2 4.5 4.5 4.5a1 1 0 0 0-1 1v11.8a1 1 0 0 0 1 1c3.5 0 5.6.5 7.5 1.9V6.2Zm1 0v13c1.9-1.4 4-1.9 7.5-1.9a1 1 0 0 0 1-1V5.5a1 1 0 0 0-1-1c-3.7 0-5.9.5-7.5 1.7Z"/></svg></div>
          <b>Koleksi Buku</b><small>Lengkap &amp; Terupdate</small>
        </div>
        <div class="feat">
          <div class="feat-ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="2.5" y="5" width="19" height="14" rx="2.5"/><path d="M2.5 10h19M6.5 15h4"/></svg></div>
          <b>Kartu Pelajar</b><small>RFID, Tap &amp; Pinjam</small>
        </div>
        <div class="feat">
          <div class="feat-ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 8V5.5A2.5 2.5 0 0 1 5.5 3H8M16 3h2.5A2.5 2.5 0 0 1 21 5.5V8M21 16v2.5a2.5 2.5 0 0 1-2.5 2.5H16M8 21H5.5A2.5 2.5 0 0 1 3 18.5V16"/><rect x="7.5" y="7.5" width="3.5" height="3.5" rx=".6"/><rect x="13" y="7.5" width="3.5" height="3.5" rx=".6"/><rect x="7.5" y="13" width="3.5" height="3.5" rx=".6"/><path d="M13 13h3.5v3.5"/></svg></div>
          <b>QR Unik</b><small>Per Eksemplar</small>
        </div>
        <div class="feat">
          <div class="feat-ico"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="9"/><path d="M12 7v5l3.5 2"/></svg></div>
          <b>Riwayat</b><small>Pinjaman Tercatat</small>
        </div>
      </div>
    </div>

    <div class="art">
      <div class="art-img">
      <?php if ($pakaiFoto): ?>
        <img src="<?= htmlspecialchars($fotoHero, ENT_QUOTES, 'UTF-8') ?>" alt="">
      <?php else: ?>
        <svg viewBox="0 0 722 520" preserveAspectRatio="xMidYMax slice" role="img" aria-label="Tumpukan buku di atas meja dengan rak buku di belakangnya">
          <defs>
            <linearGradient id="gWall" x1="0" y1="0" x2="1" y2="0"><stop offset="0" stop-color="#E6D6C0"/><stop offset="1" stop-color="#F0E9DC"/></linearGradient>
            <linearGradient id="gTable" x1="0" y1="0" x2="0" y2="1"><stop offset="0" stop-color="#E4C294"/><stop offset="1" stop-color="#C79A63"/></linearGradient>
            <filter id="blur" x="-5%" y="-5%" width="110%" height="110%"><feGaussianBlur stdDeviation="3"/></filter>
            <filter id="blurS"><feGaussianBlur stdDeviation="9"/></filter>
          </defs>
          <rect width="722" height="520" fill="url(#gWall)"/>
          <g filter="url(#blur)">
            <rect x="52" y="34" width="490" height="310" fill="#BF9460"/>
            <rect x="62" y="44" width="470" height="290" fill="#EADFCB"/>
            <?= $rakSvg ?>
            <rect x="62" y="128" width="470" height="9" fill="#B5844F"/>
            <rect x="62" y="222" width="470" height="9" fill="#B5844F"/>
            <rect x="62" y="316" width="470" height="9" fill="#B5844F"/>
            <rect x="566" y="18" width="156" height="330" fill="#DFEDE0"/>
            <circle cx="650" cy="80" r="70" fill="#8DBE93"/><circle cx="700" cy="190" r="80" fill="#6FA97B"/><circle cx="610" cy="250" r="60" fill="#9CC8A0"/>
            <ellipse cx="38" cy="240" rx="26" ry="110" fill="#4F8B4F" transform="rotate(14 38 240)"/>
            <ellipse cx="78" cy="300" rx="20" ry="80" fill="#3F7A45" transform="rotate(-18 78 300)"/>
          </g>
          <rect y="330" width="722" height="190" fill="url(#gTable)"/>
          <rect y="330" width="722" height="5" fill="#F0D2A8" opacity=".7"/>
          <ellipse cx="390" cy="446" rx="250" ry="22" fill="#000" opacity=".22" filter="url(#blurS)"/>
          <g transform="translate(646 252)">
            <ellipse cx="0" cy="-6" rx="46" ry="48" fill="#5C9A5C"/><ellipse cx="-24" cy="-26" rx="22" ry="30" fill="#4C8A50" transform="rotate(-20)"/><ellipse cx="26" cy="-30" rx="20" ry="28" fill="#6BAA66" transform="rotate(22)"/>
            <path d="M-30 30H30L24 74Q23 78 19 78H-19Q-23 78 -24 74Z" fill="#F4F3EF"/><ellipse cx="0" cy="30" rx="30" ry="6" fill="#5A4430"/>
          </g>
          <g transform="translate(205 224) rotate(-4)">
            <?php foreach ($tumpukan as [$judul, $warna, $dx, $dy]): ?>
            <g transform="translate(<?= $dx ?> <?= $dy ?>)">
              <rect x="0" y="0" width="338" height="50" rx="3" fill="<?= $warna ?>"/>
              <rect x="0" y="0" width="338" height="5" fill="#fff" opacity=".14"/>
              <rect x="338" y="3" width="44" height="44" fill="#F2EAD7"/>
              <path d="M338 12H382M338 20H382M338 28H382M338 36H382" stroke="#CFC4A9" stroke-width="1"/>
              <rect x="338" y="3" width="44" height="44" fill="none" stroke="#000" stroke-opacity=".12"/>
              <path d="M16 8V42M22 8V42" stroke="#fff" stroke-opacity=".28" stroke-width="2"/>
              <text x="40" y="32" fill="#fff" font-family="Plus Jakarta Sans,system-ui,sans-serif" font-size="21" font-weight="500"><?= htmlspecialchars($judul, ENT_QUOTES, 'UTF-8') ?></text>
            </g>
            <?php endforeach; ?>
          </g>
        </svg>
      <?php endif; ?>
      </div>
    </div>

    <svg class="wave" viewBox="0 0 722 300" preserveAspectRatio="none" aria-hidden="true"><path d="M0,30 C170,60 420,185 722,222 L722,300 L0,300 Z" fill="#23734E"/></svg>
    <p class="tagline">Buku hari ini,<br>ilmu selamanya<i></i></p>
  </section>

  <!-- ===== KANAN ===== -->
  <main class="right">
    <div class="topbar">
      <a href="index.php" class="back">
        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="19" y1="12" x2="5" y2="12"/><polyline points="12 19 5 12 12 5"/></svg>
        Kembali ke Beranda
      </a>
    </div>

    <div class="card-wrap">
      <div class="card">
        <div class="card-head">
          <svg aria-hidden="true"><use href="#logo"/></svg>
          <h1 class="card-brand">Pinjam<b>.In</b></h1>
          <p class="card-tag">Sistem Peminjaman Buku Perpustakaan</p>
        </div>

        <h2 class="welcome">Selamat Datang!</h2>
        <p class="welcome-sub">Silakan masuk untuk melanjutkan ke akun Anda.</p>

        <?php if (isset($_GET['error'])): ?>
          <div class="alert alert-error" role="alert">
            <?php
              if ($_GET['error'] === 'invalid') echo 'Username atau password yang Anda masukkan salah.';
              elseif ($_GET['error'] === 'empty') echo 'Semua field wajib diisi lengkap.';
              else echo 'Terjadi kesalahan autentikasi.';
            ?>
          </div>
        <?php endif; ?>

        <?php if (isset($_GET['success']) && $_GET['success'] === 'daftar'): ?>
          <div class="alert alert-success" role="status">Pendaftaran akun berhasil! Silakan login sekarang.</div>
        <?php endif; ?>

        <form action="config/proses_login.php" method="POST" autocomplete="off">
          <div class="field">
            <label for="username">Username</label>
            <div class="input">
              <svg class="lead-ico" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="8" r="4"/><path d="M4 20.5c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/></svg>
              <input type="text" id="username" name="username" placeholder="Masukkan username..." required autofocus autocomplete="off">
            </div>
          </div>

          <div class="field">
            <label for="password">Password</label>
            <div class="input">
              <svg class="lead-ico" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M7 10V7.5a5 5 0 0 1 10 0V10h.5A2.5 2.5 0 0 1 20 12.5v6a2.5 2.5 0 0 1-2.5 2.5h-11A2.5 2.5 0 0 1 4 18.5v-6A2.5 2.5 0 0 1 6.5 10H7Zm2 0h6V7.5a3 3 0 0 0-6 0V10Z"/></svg>
              <input type="password" id="password" name="password" placeholder="Masukkan password..." required autocomplete="off">
              <button type="button" class="eye" id="eye" aria-pressed="false" aria-label="Tampilkan password">
                <svg class="on" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z"/><circle cx="12" cy="12" r="3"/></svg>
                <svg class="off" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M9.9 5.2A10 10 0 0 1 12 5c6.4 0 10 7 10 7a17 17 0 0 1-3.2 4.1M6.6 6.6A16.5 16.5 0 0 0 2 12s3.6 7 10 7a9.7 9.7 0 0 0 4.4-1M14.1 14.1a3 3 0 1 1-4.2-4.2M2 2l20 20"/></svg>
              </button>
            </div>
          </div>

          <button type="submit" class="submit">Masuk
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M5 12h14M12 5l7 7-7 7"/></svg>
          </button>
        </form>

        <div class="divider"></div>
        <p class="card-foot">Belum punya akun? <a href="daftar.php">Daftar sebagai Siswa</a></p>
      </div>
    </div>

    <div class="bottom">
      <span class="secure">
        <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><path d="M12 2 4 5v6c0 5 3.4 9.3 8 11 4.6-1.7 8-6 8-11V5l-8-3Zm-1.2 14L7.5 12.7l1.4-1.4 1.9 1.9 4.3-4.3 1.4 1.4-5.7 5.7Z"/></svg>
        Aman &bull; Cepat &bull; Terpercaya
      </span>
      <p>&copy; <?= date('Y') ?> Pinjam.In Perpustakaan Sekolah. Hak Cipta Dilindungi.</p>
    </div>
  </main>
</div>

<script>
(function () {
  var eye = document.getElementById('eye'), pw = document.getElementById('password');
  eye.addEventListener('click', function () {
    var show = pw.type === 'password';
    pw.type = show ? 'text' : 'password';
    eye.setAttribute('aria-pressed', show ? 'true' : 'false');
    eye.setAttribute('aria-label', show ? 'Sembunyikan password' : 'Tampilkan password');
  });
})();
</script>
</body>
</html>