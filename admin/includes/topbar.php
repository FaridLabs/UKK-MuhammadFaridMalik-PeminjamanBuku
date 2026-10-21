<?php
$hariId = ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'];
$blnId  = ['Januari', 'Februari', 'Maret', 'April', 'Mei', 'Juni', 'Juli', 'Agustus', 'September', 'Oktober', 'November', 'Desember'];
$now    = new DateTime('now', new DateTimeZone('Asia/Jakarta'));
$tglId  = $hariId[(int)$now->format('w')] . ', ' . $now->format('j') . ' ' . $blnId[(int)$now->format('n') - 1] . ' ' . $now->format('Y');
?>
<header class="top">
    <div class="top-l">
        <button type="button" class="burger" aria-label="Buka menu" aria-controls="sidebar"
                onclick="document.getElementById('sidebar').classList.toggle('open')"><?= ic('menu', 22) ?></button>
        <div class="crumb"><span>Admin</span><i>/</i><b><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></b></div>
    </div>
    <div class="clock">
        <div class="clock-ico"><?= ic('calendar') ?></div>
        <div>
            <b id="clockDate"><?= $tglId ?></b>
            <small><span id="clockTime"><?= $now->format('H:i') ?></span> WIB</small>
        </div>
    </div>
</header>