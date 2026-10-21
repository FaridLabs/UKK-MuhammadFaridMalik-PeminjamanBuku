<?php
// Helper ikon garis (dipakai sidebar, topbar, dan halaman admin lain). Aman di-include berkali-kali.
if (!function_exists('ic')) {
    function ic(string $nama, int $size = 20): string {
        static $p = [
            'home'     => '<path d="M3 11.5 12 4l9 7.5"/><path d="M5 10v10h5v-6h4v6h5V10"/>',
            'loan'     => '<rect x="5" y="3.5" width="14" height="17.5" rx="2.5"/><path d="M9 3.5v-1h6v1M9 9h6M9 13h6M9 17h3"/>',
            'return'   => '<path d="M9 14 4 9l5-5"/><path d="M4 9h10a6 6 0 0 1 0 12h-3"/>',
            'history'  => '<path d="M3 12a9 9 0 1 0 3-6.7L3 8"/><path d="M3 3v5h5M12 7v5l3 2"/>',
            'book'     => '<path d="M4 19.5v-15A2.5 2.5 0 0 1 6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5Z"/><path d="M8 7h8M8 11h8"/>',
            'bookmark' => '<path d="m19 21-7-4-7 4V5a2 2 0 0 1 2-2h10a2 2 0 0 1 2 2v16Z"/>',
            'check'    => '<circle cx="12" cy="12" r="9.5"/><path d="m8 12.5 2.7 2.7L16 9.5"/>',
            'users'    => '<circle cx="9" cy="8" r="3.8"/><path d="M2.5 20c0-3.6 2.9-6 6.5-6s6.5 2.4 6.5 6M16 4.3a3.8 3.8 0 0 1 0 7.4M18 14.3c2.2.7 3.5 2.5 3.5 5.7"/>',
            'user'     => '<circle cx="12" cy="8" r="4"/><path d="M4 20.5c0-4 3.6-6.5 8-6.5s8 2.5 8 6.5"/>',
            'open'     => '<path d="M2 4h6a4 4 0 0 1 4 4v13a3 3 0 0 0-3-3H2V4ZM22 4h-6a4 4 0 0 0-4 4v13a3 3 0 0 1 3-3h7V4Z"/>',
            'clock'    => '<circle cx="12" cy="12" r="9.5"/><path d="M12 7v5l3.5 2"/>',
            'tag'      => '<path d="M20.6 13.4 13.4 20.6a2 2 0 0 1-2.8 0L2 12V2h10l8.6 8.6a2 2 0 0 1 0 2.8Z"/><circle cx="7.5" cy="7.5" r="1.2"/>',
            'logout'   => '<path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4M16 17l5-5-5-5M21 12H9"/>',
            'plus'     => '<path d="M12 5v14M5 12h14"/>',
            'calendar' => '<rect x="3.5" y="5" width="17" height="15.5" rx="2.5"/><path d="M3.5 10h17M8 3v4M16 3v4"/>',
            'chart'    => '<path d="M4 20V10M10 20V4M16 20v-7M22 20H2"/>',
            'grid'     => '<rect x="3.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="3.5" width="7" height="7" rx="1.5"/><rect x="3.5" y="13.5" width="7" height="7" rx="1.5"/><rect x="13.5" y="13.5" width="7" height="7" rx="1.5"/>',
            'chev'     => '<path d="m9 6 6 6-6 6"/>',
            'menu'     => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        ];
        return '<svg width="' . $size . '" height="' . $size . '" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . ($p[$nama] ?? '') . '</svg>';
    }
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Admin') ?> - Pinjam.In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=4.0">
    <link rel="stylesheet" href="../assets/css/admin.css?v=1.0">
    <?php if (!empty($extraHead)) echo $extraHead; ?>
</head>
<body class="adm">
<div class="shell">
    <?php require __DIR__ . '/sidebar.php'; ?>
    <div class="scrim" id="scrim"></div>
    <div class="stage">
        <?php require __DIR__ . '/topbar.php'; ?>
        <main class="content">