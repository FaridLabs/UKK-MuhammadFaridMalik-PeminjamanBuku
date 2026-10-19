<?php
// =========================================
// config/koneksi.php
// Koneksi Database Pinjam.In menggunakan PDO
// =========================================

$host   = 'localhost';
$dbname = 'pinjam_in';
$user   = 'root';
$pass   = ''; // sesuaikan jika password MySQL XAMPP kamu diisi

try {
    $koneksi = new PDO(
        "mysql:host=$host;dbname=$dbname;charset=utf8mb4",
        $user,
        $pass
    );

    // Supaya error ditampilkan sebagai Exception (memudahkan debugging)
    $koneksi->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Default fetch mode: associative array
    $koneksi->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    die("Koneksi database gagal: " . $e->getMessage());
}