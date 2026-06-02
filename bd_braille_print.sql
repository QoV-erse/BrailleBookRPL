-- phpMyAdmin SQL Dump
-- version 5.2.1
-- https://www.phpmyadmin.net/
--
-- Host: 127.0.0.1
-- Generation Time: Jun 02, 2026 at 06:17 AM
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
-- Database: `bd_braille_print`
--

-- --------------------------------------------------------

--
-- Table structure for table `buku`
--

CREATE TABLE `buku` (
  `id` int(11) NOT NULL,
  `judul` varchar(200) NOT NULL,
  `pengarang` varchar(100) NOT NULL,
  `penerbit` varchar(100) NOT NULL,
  `jml_halaman` int(5) NOT NULL,
  `ukuran` enum('Besar','Kecil') NOT NULL COMMENT 'Besar=25.5x30.5cm, Kecil=1.5x25.5cm',
  `jml_lembaran` int(5) NOT NULL,
  `kategori` enum('Al-Quran','Islam','Panduan','Solat') NOT NULL,
  `sinopsis` text NOT NULL,
  `gambar` varchar(255) DEFAULT 'default.jpg',
  `stok_tersedia` int(5) DEFAULT 0,
  `harga` decimal(15,2) NOT NULL DEFAULT 75000.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `buku`
--

INSERT INTO `buku` (`id`, `judul`, `pengarang`, `penerbit`, `jml_halaman`, `ukuran`, `jml_lembaran`, `kategori`, `sinopsis`, `gambar`, `stok_tersedia`, `harga`, `created_at`) VALUES
(1, 'Al-Qur\'an Braille Juz 1', 'Tim Yayasan Raudlatul Makfufin', 'Yayasan Raudlatul Makfufin', 120, 'Besar', 60, 'Al-Quran', 'Al-Qur\'an Braille Juz 1 lengkap dengan tajwid untuk penyandang tunanetra. Dicetak dengan standar Braille internasional menggunakan kertas berkualitas tinggi.', 'default.jpg', 0, 75000.00, '2026-05-12 08:07:38'),
(2, 'Buku Iqro Braille Jilid 1', 'Tim Yayasan Raudlatul Makfufin', 'Yayasan Raudlatul Makfufin', 64, 'Kecil', 32, 'Islam', 'Buku Iqro Braille jilid 1 untuk belajar membaca Al-Qur\'an bagi penyandang tunanetra. Dilengkapi dengan panduan huruf hijaiyah dalam format Braille.', 'default.jpg', 5, 75000.00, '2026-05-12 08:07:38'),
(3, 'Panduan Lengkap Sholat Braille', 'Tim Yayasan Raudlatul Makfufin', 'Yayasan Raudlatul Makfufin', 96, 'Besar', 48, 'Solat', 'Panduan lengkap tata cara sholat dalam format Braille. Mencakup bacaan niat, gerakan sholat, doa-doa, dan tuntunan praktis untuk penyandang tunanetra.', 'default.jpg', 0, 75000.00, '2026-05-12 08:07:38'),
(5, '7 Surat Pilihan + Terjemah', 'Tim Penyusun Buku Yarfin', 'Yayasan Raudlatul Makfufin', 147, 'Besar', 76, 'Al-Quran', 'Kumpulan 7 surat pilihan (Al Kahfi, As Sajdah, Yasin, Al Dukhon, Al Rahman, Al Waqiah, Al Mulk) lengkap beserta terjemahannya dalam format braille.', 'default.jpg', 0, 125000.00, '2026-05-24 09:24:01'),
(6, '7 Surat Pilihan (Tanpa Terjemah)', 'Tim Penyusun Buku Yarfin', 'Yayasan Raudlatul Makfufin', 60, 'Besar', 32, 'Al-Quran', 'Kumpulan 7 surat pilihan (Al Kahfi, As Sajdah, Yasin, Al Dukhon, Al Rahman, Al Waqiah, Al Mulk) tanpa terjemah dalam format braille.', 'default.jpg', 0, 92000.00, '2026-05-24 09:24:01'),
(7, '7 Surat Pilihan (Buku Kecil)', 'Tim Penyusun Buku Yarfin', 'Yayasan Raudlatul Makfufin', 112, 'Kecil', 58, 'Al-Quran', 'Kumpulan 7 surat pilihan dalam format buku braille ukuran kecil, praktis dibawa ke mana saja.', 'default.jpg', 0, 63000.00, '2026-05-24 09:24:01'),
(8, '7 Surat Pilihan + Bacaan Zikir, Tahlil dan Doa + Asmaul Husna', 'Tim Penyusun Buku Yarfin', 'Yayasan Raudlatul Makfufin', 75, 'Besar', 40, 'Islam', '7 surat pilihan tanpa terjemah dilengkapi bacaan zikir, tahlil dan doa serta Asmaul Husna dalam format braille.', 'default.jpg', 0, 98000.00, '2026-05-24 09:24:01'),
(9, '8 Surat Pilihan Tanpa Terjemah', 'Tim Penyusun Buku Yarfin', 'Yayasan Raudlatul Makfufin', 133, 'Besar', 69, 'Al-Quran', 'Kumpulan 8 surat pilihan (Al Baqarah, Al Kahfi, As Sajdah, Yasin, Al Dukhon, Al Rahman, Al Waqiah, Al Mulk) tanpa terjemah dalam format braille.', 'default.jpg', 0, 120000.00, '2026-05-24 09:24:01'),
(10, '11 Surat Pilihan', 'Tim Penyusun Buku Yarfin', 'Yayasan Raudlatul Makfufin', 112, 'Besar', 58, 'Al-Quran', 'Kumpulan 11 surat pilihan (Yusuf, Kahfi, Maryam, Lukman, As Sajdah, Yasin, Ad Dukhan, Ar Rahman, Al Waqiah, Al Hasyr, Al Mulk) dalam format braille.', 'default.jpg', 0, 111000.00, '2026-05-24 09:24:01'),
(11, '150 Hadits Pilihan untuk Pembinaan Akhlak dan Iman', 'Drs. H. A. Mustafa', 'Yayasan Raudlatul Makfufin', 119, 'Besar', 67, 'Islam', 'Kumpulan 150 hadits pilihan yang membahas tentang pembinaan akhlak dan iman, disusun untuk memudahkan para penyandang tunanetra mempelajari hadits.', 'default.jpg', 0, 118000.00, '2026-05-24 09:24:01'),
(12, 'Al Qur\'an Al Karim Juz 30 / Amma (Standar)', 'Yayasan Raudlatul Makfufin', 'Yayasan Raudlatul Makfufin', 143, 'Besar', 73, 'Al-Quran', 'Al-Qur\'an Juz 30 / Juz Amma dalam format braille standar, lengkap semua surat dari An-Naba hingga An-Nas.', 'default.jpg', 0, 123000.00, '2026-05-24 09:24:01'),
(13, 'Al Qur\'an Al Karim Juz 30 / Amma (Kecil)', 'Yayasan Raudlatul Makfufin', 'Yayasan Raudlatul Makfufin', 109, 'Kecil', 56, 'Al-Quran', 'Al-Qur\'an Juz 30 / Juz Amma dalam format braille ukuran kecil, praktis untuk dibawa bepergian.', 'default.jpg', 0, 63000.00, '2026-05-24 09:24:01'),
(14, 'Al-Amtsilah At-Tashrifiyah', 'As-Syaikh Ma\'shum bin Ali', 'Yayasan Raudlatul Makfufin', 120, 'Besar', 63, 'Panduan', 'Kitab tashrif (ilmu sharaf) klasik yang membahas perubahan bentuk kata dalam bahasa Arab, diterjemahkan ke dalam format braille untuk para penyandang tunanetra.', 'default.jpg', 0, 115000.00, '2026-05-24 09:24:01'),
(15, 'Al Lughotu Al Arabiyah (Bahasa Arab)', 'Ustadz Abdul Jabbar', 'Yayasan Raudlatul Makfufin', 107, 'Besar', 57, 'Panduan', 'Buku pembelajaran bahasa Arab dalam format braille, cocok untuk pemula yang ingin mempelajari bahasa Arab dari dasar.', 'default.jpg', 0, 111000.00, '2026-05-24 09:24:01'),
(16, 'Dzikir Pagi-Petang', 'Sa\'id bin Ali Wahf Al Qahthani', 'Yayasan Raudlatul Makfufin', 60, 'Kecil', 32, 'Islam', 'Kumpulan bacaan dzikir pagi dan petang sesuai sunnah Rasulullah SAW dalam format braille ukuran kecil.', 'default.jpg', 0, 54000.00, '2026-05-24 09:24:01'),
(17, 'Himpunan Doa-Doa Pilihan', 'Ahmad Sunarto', 'Yayasan Raudlatul Makfufin', 145, 'Besar', 81, 'Islam', 'Kumpulan doa-doa pilihan dari Al-Qur\'an dan Hadits disertai terjemahan, disusun dalam format braille untuk penyandang tunanetra.', 'default.jpg', 0, 129000.00, '2026-05-24 09:24:01'),
(18, 'Ilmu Tajwid - Pedoman Membaca Al-Qur\'an Braille bagi Tunanetra', 'Tim Penyusun Buku Yarfin', 'Yayasan Raudlatul Makfufin', 93, 'Besar', 49, 'Panduan', 'Panduan lengkap ilmu tajwid khusus dirancang untuk penyandang tunanetra dalam membaca Al-Qur\'an Braille dengan benar sesuai kaidah tajwid.', 'default.jpg', 0, 105000.00, '2026-05-24 09:24:01'),
(19, 'Kelas Tajwid untuk Segala Usia - Metode Syafii Ilmu Tajwid Praktis', 'Abu Ya\'la Kurnaedi, LC. Nizar Saad Jabal, LC. M.Pd', 'Yayasan Raudlatul Makfufin', 126, 'Besar', 72, 'Panduan', 'Buku ilmu tajwid praktis menggunakan metode Syafii, cocok untuk segala usia yang ingin belajar membaca Al-Qur\'an dengan tajwid yang baik dan benar.', 'default.jpg', 0, 122000.00, '2026-05-24 09:24:01'),
(20, 'Maulid Simtudduror dan Sholawat Nabi Muhammad SAW', 'Ali bin Muhammad bin Husain Al-Habsyi', 'Yayasan Raudlatul Makfufin', 85, 'Kecil', 44, 'Islam', 'Kitab maulid Simtudduror karya Al-Habib Ali Al-Habsyi beserta kumpulan sholawat kepada Nabi Muhammad SAW dalam format braille ukuran kecil.', 'default.jpg', 0, 58000.00, '2026-05-24 09:24:01'),
(21, 'Pandai Membaca Al-Qur\'an - Metode Cepat dan Praktis Membaca Al-Qur\'an Braille Edisi Revisi', 'Tim Penyusun Buku Yarfin', 'Yayasan Raudlatul Makfufin', 72, 'Besar', 39, 'Panduan', 'Metode cepat dan praktis belajar membaca Al-Qur\'an Braille edisi revisi, dirancang khusus untuk penyandang tunanetra agar dapat membaca Al-Qur\'an dengan mudah.', 'default.jpg', 0, 97000.00, '2026-05-24 09:24:01'),
(22, 'Surah Yasin Dilengkapi Bacaan Dzikir, Tahlil dan Doa', 'Tim Penyusun Buku Yarfin', 'Yayasan Raudlatul Makfufin', 42, 'Kecil', 23, 'Al-Quran', 'Surah Yasin lengkap dilengkapi bacaan dzikir, tahlil dan doa-doa pilihan dalam format braille ukuran kecil.', 'default.jpg', 0, 50000.00, '2026-05-24 09:24:01'),
(23, 'Surah Yasin dan Asmaul Husna Dilengkapi Bacaan Dzikir, Tahlil dan Doa', 'Tim Penyusun Buku Yarfin', 'Yayasan Raudlatul Makfufin', 53, 'Kecil', 29, 'Al-Quran', 'Surah Yasin dan Asmaul Husna dilengkapi dengan bacaan dzikir, tahlil dan doa-doa pilihan dalam format braille ukuran kecil.', 'default.jpg', 0, 53000.00, '2026-05-24 09:24:01'),
(24, 'Terjemah Hadits Arbain Annawawiyah - 40 Hadits Pilihan', 'As Syeikh Imam Nawawi', 'Yayasan Raudlatul Makfufin', 68, 'Besar', 41, 'Islam', 'Terjemahan 40 hadits pilihan dari Imam Nawawi (Arbain Annawawiyah) yang membahas pokok-pokok ajaran Islam, disusun dalam format braille.', 'default.jpg', 0, 99000.00, '2026-05-24 09:24:01');

-- --------------------------------------------------------

--
-- Table structure for table `detail_pesanan`
--

CREATE TABLE `detail_pesanan` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `buku_id` int(11) NOT NULL,
  `jumlah` int(5) NOT NULL DEFAULT 1,
  `harga_satuan` decimal(15,2) NOT NULL DEFAULT 0.00,
  `subtotal` decimal(15,2) NOT NULL DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `detail_pesanan`
--

INSERT INTO `detail_pesanan` (`id`, `pesanan_id`, `buku_id`, `jumlah`, `harga_satuan`, `subtotal`, `created_at`) VALUES
(1, 1, 2, 1, 75000.00, 75000.00, '2026-05-17 10:10:37'),
(2, 2, 3, 2, 75000.00, 150000.00, '2026-05-17 10:11:29'),
(3, 3, 3, 2, 75000.00, 150000.00, '2026-05-17 10:16:02'),
(4, 4, 3, 1, 75000.00, 75000.00, '2026-05-17 22:32:09'),
(5, 5, 2, 1, 75000.00, 75000.00, '2026-05-18 04:31:22'),
(6, 6, 2, 2, 75000.00, 150000.00, '2026-05-18 06:12:03'),
(7, 7, 2, 1, 75000.00, 75000.00, '2026-05-18 06:18:08'),
(8, 8, 3, 14, 75000.00, 1050000.00, '2026-05-18 06:34:20'),
(9, 9, 2, 1, 75000.00, 75000.00, '2026-05-20 00:28:23'),
(10, 10, 6, 1, 92000.00, 92000.00, '2026-05-24 09:28:34'),
(11, 11, 6, 1, 92000.00, 92000.00, '2026-05-25 04:01:17');

-- --------------------------------------------------------

--
-- Table structure for table `favorit`
--

CREATE TABLE `favorit` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `buku_id` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

-- --------------------------------------------------------

--
-- Table structure for table `konfirmasi_bayar`
--

CREATE TABLE `konfirmasi_bayar` (
  `id` int(11) NOT NULL,
  `pesanan_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `nama_pengirim` varchar(100) NOT NULL,
  `jumlah_bayar` decimal(15,2) NOT NULL,
  `tgl_transfer` date NOT NULL,
  `bank_asal` varchar(50) NOT NULL DEFAULT 'BCA',
  `bukti_catatan` text DEFAULT NULL,
  `status` enum('menunggu','dikonfirmasi','ditolak') NOT NULL DEFAULT 'menunggu',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `konfirmasi_bayar`
--

INSERT INTO `konfirmasi_bayar` (`id`, `pesanan_id`, `user_id`, `nama_pengirim`, `jumlah_bayar`, `tgl_transfer`, `bank_asal`, `bukti_catatan`, `status`, `created_at`) VALUES
(1, 11, 3, 'abyaz', 53500.00, '2026-06-02', 'BCA', '', 'menunggu', '2026-06-02 03:47:58');

-- --------------------------------------------------------

--
-- Table structure for table `password_resets`
--

CREATE TABLE `password_resets` (
  `id` int(11) NOT NULL,
  `email` varchar(100) NOT NULL,
  `token` varchar(64) NOT NULL,
  `expires_at` datetime NOT NULL,
  `used` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `password_resets`
--

INSERT INTO `password_resets` (`id`, `email`, `token`, `expires_at`, `used`, `created_at`) VALUES
(2, 'aby11wibowo@gmail.com', '219d8f292abd36429c4a5e301bd5fc92b845c92037e0f4a1d4a29994798bd2ed', '2026-06-02 11:45:38', 0, '2026-06-02 03:45:38');

-- --------------------------------------------------------

--
-- Table structure for table `pesanan`
--

CREATE TABLE `pesanan` (
  `id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `jumlah` int(5) NOT NULL DEFAULT 1,
  `dp_nominal` decimal(15,2) DEFAULT 0.00,
  `total_harga` decimal(15,2) DEFAULT 0.00,
  `ongkir` decimal(15,2) NOT NULL DEFAULT 15000.00,
  `status` enum('pending','diproses','selesai','batal') DEFAULT 'pending',
  `estimasi_selesai` date DEFAULT NULL,
  `catatan` text DEFAULT NULL,
  `alamat` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `pesanan`
--

INSERT INTO `pesanan` (`id`, `user_id`, `jumlah`, `dp_nominal`, `total_harga`, `ongkir`, `status`, `estimasi_selesai`, `catatan`, `alamat`, `created_at`, `updated_at`) VALUES
(1, 2, 1, 45000.00, 90000.00, 15000.00, 'batal', '2026-05-24', 'aa', 'aaa', '2026-05-17 10:10:37', '2026-05-24 09:54:39'),
(2, 2, 2, 82500.00, 165000.00, 15000.00, 'pending', '2026-05-24', 'fff', 'ff', '2026-05-17 10:11:29', '2026-05-17 10:11:29'),
(3, 2, 2, 82500.00, 165000.00, 15000.00, 'pending', '2026-05-24', 'fff', 'ff', '2026-05-17 10:16:02', '2026-05-17 10:16:02'),
(4, 2, 1, 45000.00, 90000.00, 15000.00, 'batal', '2026-05-25', '', 'www', '2026-05-17 22:32:09', '2026-05-18 05:16:26'),
(5, 3, 1, 45000.00, 90000.00, 15000.00, 'selesai', '2026-05-25', '', 'Jl.melati', '2026-05-18 04:31:22', '2026-05-18 05:16:18'),
(6, 3, 2, 82500.00, 165000.00, 15000.00, 'pending', '2026-05-25', '', 'tttt', '2026-05-18 06:12:03', '2026-05-18 06:12:03'),
(7, 4, 1, 45000.00, 90000.00, 15000.00, 'selesai', '2026-05-25', '', 'ooooooo', '2026-05-18 06:18:08', '2026-05-19 04:30:37'),
(8, 3, 14, 532500.00, 1065000.00, 15000.00, 'pending', '2026-05-25', '', 'nnnnn', '2026-05-18 06:34:20', '2026-05-18 06:34:20'),
(9, 3, 1, 45000.00, 90000.00, 15000.00, 'diproses', '2026-05-27', '', 'Jl. cinere', '2026-05-20 00:28:23', '2026-05-25 06:05:34'),
(10, 3, 1, 53500.00, 107000.00, 15000.00, 'batal', '2026-05-31', '', 'aaaa', '2026-05-24 09:28:34', '2026-05-24 09:30:04'),
(11, 3, 1, 53500.00, 107000.00, 15000.00, 'pending', '2026-06-01', '', 'sss', '2026-05-25 04:01:17', '2026-05-25 04:01:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `nama_lengkap` varchar(100) NOT NULL,
  `role` enum('admin','user') NOT NULL DEFAULT 'user',
  `google_id` varchar(255) DEFAULT NULL,
  `avatar` varchar(255) DEFAULT 'default-avatar.png',
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  `updated_at` timestamp NOT NULL DEFAULT current_timestamp() ON UPDATE current_timestamp()
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `email`, `password`, `nama_lengkap`, `role`, `google_id`, `avatar`, `created_at`, `updated_at`) VALUES
(1, 'admin', 'admin@braille-store.com', '$2y$10$PqLcIwunMEAPqAO1QMV4MeyOhlOQ0rFz/yUdm5vT4PYu750GvmjlO', 'Administrator Yayasan', 'admin', NULL, 'default-avatar.png', '2026-05-12 08:07:38', '2026-05-17 09:36:47'),
(2, 'userdemo', 'user@demo.com', '$2y$10$PqLcIwunMEAPqAO1QMV4MeyOhlOQ0rFz/yUdm5vT4PYu750GvmjlO', 'Demo User', 'user', NULL, 'default-avatar.png', '2026-05-12 08:07:38', '2026-05-17 09:37:03'),
(3, 'aby', 'aby11wibowo@gmail.com', '$2y$10$iCF9cveIUwe1H0OIxCDcvexhh2G4l9fSpE8zZ2qGUxKj12wFZSC4W', 'abyaz', 'user', NULL, 'default-avatar.png', '2026-05-17 09:35:37', '2026-05-24 03:26:29'),
(4, 'AL', 'contoh@gmain.com', '$2y$10$TgP3izMTvoJwCrPkmv2nIOqlc9kdR3vFwHRvUYYKwMOWHrKjmXDOW', 'Alfitra', 'user', NULL, 'default-avatar.png', '2026-05-18 06:17:08', '2026-05-18 06:17:08');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `buku`
--
ALTER TABLE `buku`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `buku_id` (`buku_id`);

--
-- Indexes for table `favorit`
--
ALTER TABLE `favorit`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `unique_favorit` (`user_id`,`buku_id`),
  ADD KEY `buku_id` (`buku_id`);

--
-- Indexes for table `konfirmasi_bayar`
--
ALTER TABLE `konfirmasi_bayar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pesanan_id` (`pesanan_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `password_resets`
--
ALTER TABLE `password_resets`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `token` (`token`),
  ADD KEY `idx_token` (`token`),
  ADD KEY `idx_email` (`email`);

--
-- Indexes for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `username` (`username`),
  ADD UNIQUE KEY `email` (`email`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `buku`
--
ALTER TABLE `buku`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `favorit`
--
ALTER TABLE `favorit`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `konfirmasi_bayar`
--
ALTER TABLE `konfirmasi_bayar`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `password_resets`
--
ALTER TABLE `password_resets`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pesanan`
--
ALTER TABLE `pesanan`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=12;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int(11) NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `detail_pesanan`
--
ALTER TABLE `detail_pesanan`
  ADD CONSTRAINT `detail_pesanan_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `detail_pesanan_ibfk_2` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `favorit`
--
ALTER TABLE `favorit`
  ADD CONSTRAINT `favorit_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `favorit_ibfk_2` FOREIGN KEY (`buku_id`) REFERENCES `buku` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `konfirmasi_bayar`
--
ALTER TABLE `konfirmasi_bayar`
  ADD CONSTRAINT `konfirmasi_bayar_ibfk_1` FOREIGN KEY (`pesanan_id`) REFERENCES `pesanan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `konfirmasi_bayar_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pesanan`
--
ALTER TABLE `pesanan`
  ADD CONSTRAINT `pesanan_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
