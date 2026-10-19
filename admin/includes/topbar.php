<header class="topbar">
    <div class="topbar-left">
        <button type="button" class="btn-mobile-toggle" onclick="document.getElementById('sidebar').classList.toggle('open')" aria-label="Toggle Navigation">
            <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"/>
                <line x1="3" y1="6" x2="21" y2="6"/>
                <line x1="3" y1="18" x2="21" y2="18"/>
            </svg>
        </button>
        <div class="topbar-breadcrumb">
            <span>Admin</span>
            <span>/</span>
            <strong><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></strong>
        </div>
    </div>

    <div class="topbar-right">
        <div class="topbar-date">
            <?= date('l, d M Y') ?>
        </div>
    </div>
</header>
