</main>
    </div><!-- /.stage -->
</div><!-- /.shell -->

<script>
(function () {
    // Jam di topbar (WIB)
    var t = document.getElementById('clockTime');
    function tick() {
        if (!t) return;
        t.textContent = new Date().toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit', hour12: false, timeZone: 'Asia/Jakarta' }).replace('.', ':');
    }
    tick(); setInterval(tick, 20000);

    // Tutup sidebar mobile: klik latar gelap, tombol Esc, atau pilih menu
    var side = document.getElementById('sidebar'), scrim = document.getElementById('scrim');
    function tutup() { side.classList.remove('open'); }
    if (scrim) scrim.addEventListener('click', tutup);
    document.addEventListener('keydown', function (e) { if (e.key === 'Escape') tutup(); });
})();
</script>
<?php if (!empty($extraScripts)) echo $extraScripts; ?>
</body>
</html>