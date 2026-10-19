<?php
// siswa/includes/header.php
if (!isset($pageTitle)) $pageTitle = 'Portal Siswa - Pinjam.In';
if (!isset($currentPage)) $currentPage = 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?> - Pinjam.In</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&family=JetBrains+Mono:wght@400;500;600&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css?v=4.0">
    <?php if (isset($extraHead)) echo $extraHead; ?>
</head>
<body>
    <div class="app-layout">
        <?php require __DIR__ . '/sidebar.php'; ?>
        <div class="main-wrapper">
            <?php require __DIR__ . '/topbar.php'; ?>
            <main class="content">
