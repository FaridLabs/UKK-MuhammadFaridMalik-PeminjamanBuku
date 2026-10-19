<?php
// siswa/includes/topbar.php
?>
<header class="topbar">
    <div class="topbar-left">
        <button type="button" class="sidebar-toggle-btn" onclick="document.querySelector('.sidebar').classList.toggle('open')" aria-label="Toggle Sidebar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="3" y1="12" x2="21" y2="12"></line>
                <line x1="3" y1="6" x2="21" y2="6"></line>
                <line x1="3" y1="18" x2="21" y2="18"></line>
            </svg>
        </button>
        <div class="breadcrumb">
            <span>Siswa</span>
            <span class="sep">/</span>
            <span class="current"><?= htmlspecialchars($pageTitle ?? 'Dashboard') ?></span>
        </div>
    </div>
    <div class="topbar-right">
        <div class="badge-status-pill">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect>
                <line x1="16" y1="2" x2="16" y2="6"></line>
                <line x1="8" y1="2" x2="8" y2="6"></line>
                <line x1="3" y1="10" x2="21" y2="10"></line>
            </svg>
            <?= date('l, d M Y') ?>
        </div>
    </div>
</header>
