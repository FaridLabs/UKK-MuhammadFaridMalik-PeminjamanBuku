-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Oct 19, 2026 at 06:21 AM
-- Server version: 10.4.32-MariaDB
-- PHP Version: 8.2.12

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `pinjam_in`
--

-- --------------------------------------------------------

--
-- Table structure for table `admin`
--

CREATE TABLE `admin` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `admin`
--

INSERT INTO `admin` (`id`, `username`, `password`, `nama_lengkap`, `created_at`) VALUES
(1, 'admin', '$2y$10$svzgTh8CZFB/.iiFgEXp1udRiydyZMVue8.UeWfDuPLABTMPmXdgy', 'Administrator Perpustakaan', '2026-09-08 06:17:29');

-- --------------------------------------------------------

--
-- Table structure for table `anggota`
--

CREATE TABLE `anggota` (
  `id` int(11) NOT NULL,
  `nis` varchar(20) NOT NULL,
  `kode_rfid` varchar(50) DEFAULT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `kelas` varchar(20) NOT NULL,
  `no_hp` varchar(20) DEFAULT NULL,
  `username` varchar(50) NOT NULL,
  `password` varchar(255) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `anggota`
--

INSERT INTO `anggota` (`id`, `nis`, `kode_rfid`, `nama_lengkap`, `kelas`, `no_hp`, `username`, `password`, `created_at`) VALUES
(15, '2425009', '1709191630', 'Muhammad Farid Malik', 'XII RPL 2', '085', 'farid', '$2y$10$DrAry8ABBHwoQxlj.BTKMOE2cRQaOrsr3Y2qtQEJbft2sJ989DEuK', '2026-09-10 07:46:52'),
(16, '24250223', '1705319278', 'BAGAS NATASAPUTRA', 'XII RPL 2', '085', 'bagas', '$2y$10$Oddy9NtoSApmKhOn.O.iv.DFAbT.kByXzDnAJCP3R0D/pCSHG1KpG', '2026-09-10 07:49:16'),
(17, '5252536', '1709911214', 'Yaya Dadan Jaya', 'XII RPL 2', '085', 'yaya', '$2y$10$y7AmlbS8f5urqQjB9olbl.HRXwM0eyHbV7mnNWmsMUA0h.bGglkpe', '2026-09-10 07:49:33'),
(18, '123242', '1705876302', 'farel', 'kuliah', '213314', 'buku pinjam', '$2y$10$bQMr39Kda.J0El4ucbTgaO.RbC7aONJ1jq4qODPa256pWfqg3Uy.q', '2026-10-08 04:34:12'),
(21, '2425123', NULL, 'Muhammad Farid Malik', 'XII RPL 2', '085798231433', 'fariddd', '$2y$10$WrnYRmVSkIv.ic/uUOk8a.Hcfw5lla.nkvXnAO28AvXB2iiipEOA2', '2026-10-19 03:46:13'),
(22, '2425888', NULL, 'Budi Santoso', 'XII RPL 1', '08123456789', 'budisantoso', '$2y$10$OvFTpZHl2GgIDHVd9XTEm.00TViHhAbNqmj1azCbSPvLvWO9dBpTm', '2026-10-19 03:57:27');

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id` int(11) NOT NULL,
  `kode_buku` varchar(20) NOT NULL,
  `judul` varchar(150) NOT NULL,
  `pengarang` varchar(100) DEFAULT NULL,
  `penerbit` varchar(100) DEFAULT NULL,
  `tahun_terbit` year(4) DEFAULT NULL,
  `kategori_id` int(11) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id`, `kode_buku`, `judul`, `pengarang`, `penerbit`, `tahun_terbit`, `kategori_id`, `created_at`) VALUES
(13, 'BK-2026091009395020', '3756', 'Pidi Baiq', 'google', '2020', 1, '2026-09-10 07:39:50'),
(14, 'BK-2026092103453597', 'Rekayasa Perangkat Lunak', 'Abijar', 'goggle', '2020', 3, '2026-09-21 01:45:35'),
(15, 'BK-2026100806312225', 'rekayasa perangkat lunak', 'tok dalang', 'bagas', '0000', 1, '2026-10-08 04:31:22'),
(16, 'BK-2026100806330530', '3786mdpl', 'afgan', 'farel', '2026', 1, '2026-10-08 04:33:05'),
(17, 'BK-2026100806373110', '3756', 'afgan', 'farel', '2030', 4, '2026-10-08 04:37:31');

-- --------------------------------------------------------

--
-- Table structure for table `buku_item`
--

CREATE TABLE `buku_item` (
  `id` int(11) NOT NULL,
  `kode_item` varchar(30) NOT NULL,
  `buku_id` int(11) NOT NULL,
  `status` enum('tersedia','dipinjam','rusak','hilang') NOT NULL DEFAULT 'tersedia',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku_item`
--

INSERT INTO `buku_item` (`id`, `kode_item`, `buku_id`, `status`, `created_at`) VALUES
(32, '562350038', 13, 'dipinjam', '2026-09-10 07:40:18'),
(33, '562350038', 13, 'tersedia', '2026-09-10 07:40:18'),
(34, '2022.04461.BOS', 13, 'tersedia', '2026-09-10 07:40:19'),
(35, 'BK-13-004', 13, 'tersedia', '2026-09-10 07:44:25'),
(36, '562350038', 13, 'tersedia', '2026-09-10 07:52:53'),
(37, '9786020385938', 13, 'tersedia', '2026-09-10 07:53:34'),
(41, 'BK-14-001', 14, 'tersedia', '2026-09-21 01:54:32'),
(42, 'BK-14-002', 14, 'tersedia', '2026-09-21 01:55:15'),
(43, 'BK-14-003', 14, 'tersedia', '2026-09-21 02:18:25'),
(44, 'BK-14-004', 14, 'tersedia', '2026-09-21 02:18:36'),
(45, 'BK-14-005', 14, 'tersedia', '2026-09-21 02:18:47'),
(46, 'BK-14-006', 14, 'tersedia', '2026-10-08 04:18:42'),
(47, 'BK-14-007', 14, 'tersedia', '2026-10-08 04:18:42'),
(48, 'BK-14-008', 14, 'tersedia', '2026-10-08 04:18:42'),
(49, 'BK-14-009', 14, 'tersedia', '2026-10-08 04:18:42'),
(50, 'BK-14-010', 14, 'tersedia', '2026-10-08 04:18:42'),
(51, 'BK-15-001', 15, 'tersedia', '2026-10-08 04:31:22'),
(52, 'BK-15-002', 15, 'tersedia', '2026-10-08 04:31:22'),
(53, 'BK-15-003', 15, 'tersedia', '2026-10-08 04:31:22'),
(54, 'BK-15-004', 15, 'tersedia', '2026-10-08 04:31:22'),
(55, 'BK-15-005', 15, 'tersedia', '2026-10-08 04:31:22'),
(56, 'BK-15-006', 15, 'tersedia', '2026-10-08 04:31:22'),
(57, 'BK-15-007', 15, 'tersedia', '2026-10-08 04:31:22'),
(58, 'BK-15-008', 15, 'tersedia', '2026-10-08 04:31:22'),
(59, 'BK-15-009', 15, 'tersedia', '2026-10-08 04:31:22'),
(60, 'BK-15-010', 15, 'tersedia', '2026-10-08 04:31:22'),
(61, 'BK-16-001', 16, 'tersedia', '2026-10-08 04:33:05'),
(62, 'BK-17-001', 17, 'tersedia', '2026-10-08 04:37:31');

-- --------------------------------------------------------

--
-- Table structure for table `kategori`
--

CREATE TABLE `kategori` (
  `id` int(11) NOT NULL,
  `nama_kategori` varchar(100) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `kategori`
--

INSERT INTO `kategori` (`id`, `nama_kategori`) VALUES
(1, 'Fiksi'),
(2, 'Non-Fiksi'),
(3, 'Pelajaran'),
(4, 'Referensi'),
(5, 'Komik');

-- --------------------------------------------------------

--
-- Table structure for table `transaksi`
--

CREATE TABLE `transaksi` (
  `id` int(11) NOT NULL,
  `kode_transaksi` varchar(30) NOT NULL,
  `anggota_id` int(11) NOT NULL,
  `buku_item_id` int(11) NOT NULL,
  `tgl_pinjam` date NOT NULL,
  `tgl_kembali_rencana` date NOT NULL,
  `tgl_kembali_aktual` date DEFAULT NULL,
  `status` enum('dipinjam','dikembalikan','terlambat') NOT NULL DEFAULT 'dipinjam',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Indexes for dumped tables
--

--
-- Indexes for table `admin`
--
ALTER TABLE `admin`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`);

--
-- Indexes for table `anggota`
--
ALTER TABLE `anggota`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `nis` (`nis`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `kode_rfid` (`kode_rfid`);

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_buku` (`kode_buku`),
  ADD KEY `kategori_id` (`kategori_id`);

--
-- Indexes for table `buku_item`
--
ALTER TABLE `buku_item`
  ADD PRIMARY KEY (`id`),
  ADD KEY `buku_id` (`buku_id`),
  ADD KEY `idx_kode_item` (`kode_item`);

--
-- Indexes for table `kategori`
--
ALTER TABLE `kategori`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `kode_transaksi` (`kode_transaksi`),
  ADD KEY `anggota_id` (`anggota_id`),
  ADD KEY `buku_item_id` (`buku_item_id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `admin`
--
ALTER TABLE `admin`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `anggota`
--
ALTER TABLE `anggota`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=23;

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=18;

--
-- AUTO_INCREMENT for table `buku_item`
--
ALTER TABLE `buku_item`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=63;

--
-- AUTO_INCREMENT for table `kategori`
--
ALTER TABLE `kategori`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `transaksi`
--
ALTER TABLE `transaksi`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `buku`
--
ALTER TABLE `buku`
  ADD CONSTRAINT `buku_ibfk_1` FOREIGN KEY (`kategori_id`) REFERENCES `kategori` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `buku_item`
--
ALTER TABLE `buku_item`
  ADD CONSTRAINT `buku_item_ibfk_1` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `transaksi`
--
ALTER TABLE `transaksi`
  ADD CONSTRAINT `transaksi_ibfk_1` FOREIGN KEY (`anggota_id`) REFERENCES `anggota` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
