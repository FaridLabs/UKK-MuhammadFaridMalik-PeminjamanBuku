<?php
// =========================================
// config/proses_logout.php
// Logout untuk admin maupun siswa
// =========================================

session_start();
session_unset();
session_destroy();

header('Location: ../index.php');
exit;