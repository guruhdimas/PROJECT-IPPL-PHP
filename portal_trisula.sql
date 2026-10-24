-- phpMyAdmin SQL Dump
-- version 5.2.3
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Oct 24, 2026 at 04:58 PM
-- Server version: 8.4.3
-- PHP Version: 8.3.33

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `portal_trisula`
--

-- --------------------------------------------------------

--
-- Table structure for table `absensi`
--

CREATE TABLE `absensi` (
  `id` int NOT NULL,
  `pertemuan_id` int NOT NULL,
  `siswa_id` int NOT NULL,
  `status` enum('hadir','izin','sakit','alpa') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

-- --------------------------------------------------------

--
-- Table structure for table `ekskul`
--

CREATE TABLE `ekskul` (
  `id` int UNSIGNED NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pembimbing` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `pembimbing_id` int UNSIGNED DEFAULT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu','Minggu') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL,
  `kuota` smallint UNSIGNED NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ekskul`
--

INSERT INTO `ekskul` (`id`, `nama`, `deskripsi`, `pembimbing`, `pembimbing_id`, `hari`, `jam_mulai`, `jam_selesai`, `kuota`) VALUES
(1, 'Robotic & IoT Club', 'Merakit robot, mempelajari sensor, mikrokontroler, Internet of Things, dan mengikuti kompetisi teknologi.', 'Abdul Husein, S.T', 2, 'Sabtu', '08:00:00', '11:00:00', 8),
(2, 'Basket dan Futsal Club', 'Membangun kebugaran, kerja sama tim, kedisiplinan, dan kemampuan berkompetisi.', 'Lukman Hakim, S.Pd', 2, 'Sabtu', '14:00:00', '17:00:00', 60),
(3, 'Cyber Security & Programming', 'Belajar pemrograman, jaringan komputer, keamanan digital, dan pengembangan website.', 'Abdul Husein, S.T', 2, 'Sabtu', '09:00:00', '12:00:00', 10),
(4, 'English Debate & Speech', 'Melatih cara berkomunikasi, argumentasi, presentasi, dan kemampuan berbahasa Inggris.', 'Ajeng Mirna, S.Pd', 2, 'Sabtu', '15:00:00', '17:00:00', 6),
(5, 'Seni & Budaya Nusantara', 'Mengembangkan bakat tari tradisional, musik, teater, dan pelestarian budaya Indonesia.', 'Dewi Ratna, S.Pd', 2, 'Minggu', '09:00:00', '12:00:00', 17),
(6, 'Science & Research Club', 'Mengembangkan penelitian, eksperimen, karya ilmiah, dan inovasi untuk kompetisi sains.', 'Fathul Masrani, S.T, M.T', 2, 'Sabtu', '09:00:00', '12:00:00', 8);

-- --------------------------------------------------------

--
-- Table structure for table `ekskul_absensi`
--

CREATE TABLE `ekskul_absensi` (
  `id` int NOT NULL,
  `pertemuan_id` int NOT NULL,
  `siswa_id` int NOT NULL,
  `status` enum('hadir','izin','sakit','alpa') NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ekskul_absensi`
--

INSERT INTO `ekskul_absensi` (`id`, `pertemuan_id`, `siswa_id`, `status`, `created_at`) VALUES
(1, 2, 1, 'hadir', '2026-10-23 18:46:35'),
(2, 1, 1, 'hadir', '2026-10-23 18:47:18'),
(3, 3, 14, 'hadir', '2026-10-24 01:23:06'),
(4, 3, 1, 'alpa', '2026-10-24 01:23:23'),
(5, 3, 6, 'alpa', '2026-10-24 01:23:23'),
(6, 3, 15, 'sakit', '2026-10-24 01:23:23'),
(7, 3, 16, 'sakit', '2026-10-24 01:23:23'),
(8, 3, 17, 'izin', '2026-10-24 01:23:23'),
(9, 3, 18, 'izin', '2026-10-24 01:23:23'),
(10, 3, 19, 'hadir', '2026-10-24 01:23:23'),
(11, 3, 20, 'hadir', '2026-10-24 01:23:23'),
(12, 3, 21, 'izin', '2026-10-24 01:23:23'),
(13, 3, 22, 'izin', '2026-10-24 01:23:23'),
(14, 3, 23, 'sakit', '2026-10-24 01:23:23'),
(15, 3, 24, 'sakit', '2026-10-24 01:23:23'),
(16, 3, 7, 'alpa', '2026-10-24 01:23:23'),
(17, 3, 25, 'alpa', '2026-10-24 01:23:23'),
(18, 3, 26, 'sakit', '2026-10-24 01:23:23'),
(19, 3, 8, 'sakit', '2026-10-24 01:23:23'),
(20, 3, 9, 'izin', '2026-10-24 01:23:23'),
(21, 3, 10, 'izin', '2026-10-24 01:23:23'),
(22, 3, 11, 'hadir', '2026-10-24 01:23:23'),
(23, 3, 12, 'hadir', '2026-10-24 01:23:23'),
(24, 3, 13, 'hadir', '2026-10-24 01:23:23'),
(48, 5, 1, 'hadir', '2026-10-24 16:54:12'),
(49, 5, 6, 'hadir', '2026-10-24 16:54:12'),
(50, 5, 7, 'sakit', '2026-10-24 16:54:12'),
(51, 5, 8, 'sakit', '2026-10-24 16:54:12');

-- --------------------------------------------------------

--
-- Table structure for table `ekskul_nilai`
--

CREATE TABLE `ekskul_nilai` (
  `id` int UNSIGNED NOT NULL,
  `siswa_id` int UNSIGNED NOT NULL,
  `aspek` varchar(50) NOT NULL,
  `nilai` tinyint UNSIGNED NOT NULL,
  `catatan` varchar(255) NOT NULL DEFAULT '',
  `semester` varchar(30) NOT NULL,
  `urutan` tinyint UNSIGNED NOT NULL DEFAULT '0',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ekskul_nilai`
--

INSERT INTO `ekskul_nilai` (`id`, `siswa_id`, `aspek`, `nilai`, `catatan`, `semester`, `urutan`, `created_at`) VALUES
(8, 1, 'Kemandirian', 100, 'Sangat aktif dan mandiri dalam kegiatan', 'Ganjil 2026', 1, '2026-10-24 23:57:04'),
(9, 1, 'Kerjasama', 100, 'Mampu bekerja tim dengan baik', 'Ganjil 2026', 2, '2026-10-24 23:57:04'),
(10, 1, 'Kedisiplinan', 100, 'Perlu meningkatkan ketepatan waktu', 'Ganjil 2026', 3, '2026-10-24 23:57:04'),
(11, 1, 'Kreativitas', 100, 'Sangat pintar kali', 'Ganjil 2026', 4, '2026-10-24 23:57:04'),
(12, 1, 'Kepemimpinan', 100, 'Perlu meningkatkan kemampuan memimpin kelompok', 'Ganjil 2026', 5, '2026-10-24 23:57:04');

-- --------------------------------------------------------

--
-- Table structure for table `ekskul_pengumuman`
--

CREATE TABLE `ekskul_pengumuman` (
  `id` int UNSIGNED NOT NULL,
  `ekskul_id` int UNSIGNED NOT NULL,
  `judul` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `isi` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ekskul_pengumuman`
--

INSERT INTO `ekskul_pengumuman` (`id`, `ekskul_id`, `judul`, `isi`, `tanggal`) VALUES
(1, 1, 'Jadwal Latihan Pekan Ini', 'Latihan Project Robotic akan dilaksanakan  08.00 – 11.00 di Lab.', '2026-10-23'),
(2, 1, 'Tugas Konektivitas & IoT Cloud', 'Deadline Hari Rabu Tanggal 21 Mei.', '2026-10-11');

-- --------------------------------------------------------

--
-- Table structure for table `ekskul_pertemuan`
--

CREATE TABLE `ekskul_pertemuan` (
  `id` int NOT NULL,
  `ekskul_id` int NOT NULL,
  `tanggal` date NOT NULL,
  `jam` time NOT NULL DEFAULT '08:00:00',
  `kegiatan` varchar(150) NOT NULL,
  `lokasi` varchar(100) NOT NULL DEFAULT '-',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `ekskul_pertemuan`
--

INSERT INTO `ekskul_pertemuan` (`id`, `ekskul_id`, `tanggal`, `jam`, `kegiatan`, `lokasi`, `created_at`) VALUES
(1, 1, '2026-10-23', '02:27:00', 'yyyyy', 'YYYYY', '2026-10-23 19:27:05'),
(2, 2, '2026-10-24', '08:22:00', 'ggggggggggg', 'gggggggggggggg', '2026-10-24 01:21:32'),
(3, 2, '2026-10-24', '10:22:00', 'dfdfdfdfd', 'dfvdfdfdfdf', '2026-10-24 01:22:19'),
(4, 4, '2026-10-25', '15:47:00', 'GATAU', 'ENGLISH', '2026-10-24 08:47:48'),
(5, 4, '2026-10-24', '01:55:00', 't', 'test', '2026-10-24 16:53:13');

-- --------------------------------------------------------

--
-- Table structure for table `ekskul_tugas`
--

CREATE TABLE `ekskul_tugas` (
  `id` int UNSIGNED NOT NULL,
  `ekskul_id` int UNSIGNED NOT NULL,
  `judul` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deadline` date NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `ekskul_tugas`
--

INSERT INTO `ekskul_tugas` (`id`, `ekskul_id`, `judul`, `keterangan`, `deadline`) VALUES
(1, 1, 'Tugas Konektivitas & IoT Clouds', 'Kumpulkan laporan dalam bentuk pdf', '2026-11-06'),
(2, 1, 'Tuning Algoritma Kontrol PID', 'Kumpulkan laporan dalam bentuk pdf', '2026-12-02'),
(3, 1, 'Mini-Project ROS & Visi Komputer', 'Kumpulkan laporan dalam bentuk pdf', '2026-09-23'),
(4, 2, 'pfgdfgdg', 'pfdgfdgdfgfdgfdgfd', '2026-10-31'),
(5, 2, 'Pemrograman Web', 'Live Coding', '2026-10-25');

-- --------------------------------------------------------

--
-- Table structure for table `info_komentar`
--

CREATE TABLE `info_komentar` (
  `id` int UNSIGNED NOT NULL,
  `pengumuman_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED DEFAULT NULL,
  `nama` varchar(100) NOT NULL,
  `peran` enum('siswa','pembimbing') NOT NULL DEFAULT 'siswa',
  `isi` varchar(500) NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `info_komentar`
--

INSERT INTO `info_komentar` (`id`, `pengumuman_id`, `user_id`, `nama`, `peran`, `isi`, `created_at`) VALUES
(1, 1, 1, 'Budi Setiawan', 'siswa', 'Siap, akan hadir!', '2026-10-23 14:41:36'),
(2, 1, NULL, 'Rina Kusuma', 'siswa', 'baik pak!', '2026-10-23 14:56:36'),
(3, 1, 1, 'Budi Setiawan', 'siswa', 'j', '2026-10-23 15:42:43'),
(4, 1, 1, 'Budi Setiawan 1', 'siswa', 'y', '2026-10-24 01:39:39'),
(5, 1, 1, 'Budi Setiawan 1', 'siswa', 'TEST KOMEN', '2026-10-24 15:45:23');

-- --------------------------------------------------------

--
-- Table structure for table `info_pengumuman`
--

CREATE TABLE `info_pengumuman` (
  `id` int UNSIGNED NOT NULL,
  `ekskul_id` int UNSIGNED NOT NULL,
  `penulis` varchar(100) NOT NULL DEFAULT '',
  `judul` varchar(150) NOT NULL,
  `isi` text NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `info_pengumuman`
--

INSERT INTO `info_pengumuman` (`id`, `ekskul_id`, `penulis`, `judul`, `isi`, `created_at`) VALUES
(1, 1, 'Abdul Husein, S.T', 'Jadwal Latihan Pekan Ini', 'Latihan Project Robotic akan dilaksanakan 08.00 – 11.00 di Lab.', '2026-10-23 13:41:36'),
(2, 1, 'Abdul Husein, S.T', 'Tugas Konektivitas & IoT Cloud', 'Pendaftaran peserta lomba seni dibuka hingga 25 September. Segera daftarkan diri kalian ke sekretariat OSIS sebelum batas waktu.', '2026-10-23 10:41:36'),
(3, 2, 'Lukman Hakim, S.Pd', 'Perubahan Jadwal Latihan Bola', 'Latihan hari sabtu ini ditunda.', '2026-10-22 15:41:36');

-- --------------------------------------------------------

--
-- Table structure for table `jadwal`
--

CREATE TABLE `jadwal` (
  `id` int UNSIGNED NOT NULL,
  `kegiatan` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hari` enum('Senin','Selasa','Rabu','Kamis','Jumat','Sabtu') COLLATE utf8mb4_unicode_ci NOT NULL,
  `jam_mulai` time NOT NULL,
  `jam_selesai` time NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `jadwal`
--

INSERT INTO `jadwal` (`id`, `kegiatan`, `hari`, `jam_mulai`, `jam_selesai`) VALUES
(1, 'Latihan Project Robotic', 'Selasa', '08:00:00', '11:00:00'),
(2, 'Latihan Bola', 'Sabtu', '15:00:00', '17:00:00'),
(3, 'Kelas IoT Cloud', 'Rabu', '13:00:00', '15:00:00');

-- --------------------------------------------------------

--
-- Table structure for table `pembimbing_profil`
--

CREATE TABLE `pembimbing_profil` (
  `user_id` int UNSIGNED NOT NULL,
  `foto` varchar(255) DEFAULT NULL,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `pembimbing_profil`
--

INSERT INTO `pembimbing_profil` (`user_id`, `foto`, `updated_at`) VALUES
(2, 'uploads/profil/pb2_d24c95151794.jpg', '2026-10-24 16:02:11');

-- --------------------------------------------------------

--
-- Table structure for table `pendaftaran`
--

CREATE TABLE `pendaftaran` (
  `id` int UNSIGNED NOT NULL,
  `siswa_id` int UNSIGNED NOT NULL,
  `ekskul_id` int UNSIGNED NOT NULL,
  `nis` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hp_siswa` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_ortu` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `hp_ortu` varchar(20) COLLATE utf8mb4_unicode_ci NOT NULL,
  `alasan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `pengalaman` text COLLATE utf8mb4_unicode_ci,
  `status` enum('menunggu','disetujui','ditolak') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'menunggu',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `diproses_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pendaftaran`
--

INSERT INTO `pendaftaran` (`id`, `siswa_id`, `ekskul_id`, `nis`, `hp_siswa`, `nama_ortu`, `hp_ortu`, `alasan`, `pengalaman`, `status`, `created_at`, `diproses_at`) VALUES
(1, 3, 1, '202210051', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:32', '2026-10-23 14:56:32'),
(2, 4, 1, '202210052', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:32', '2026-10-23 14:56:32'),
(3, 5, 1, '202210053', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:32', '2026-10-23 14:56:32'),
(4, 6, 2, '20240001', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(5, 7, 2, '20240002', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(6, 8, 2, '20240003', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(7, 9, 2, '20240004', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(8, 10, 2, '20240005', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(9, 11, 2, '20240006', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(10, 12, 2, '20240007', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(11, 13, 2, '20240008', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(12, 14, 2, '20240009', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(13, 15, 2, '20240010', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(14, 16, 2, '20240011', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(15, 17, 2, '20240012', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(16, 18, 2, '20240013', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(17, 19, 2, '20240014', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(18, 20, 2, '20240015', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(19, 21, 2, '20240016', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(20, 22, 2, '20240017', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(21, 23, 2, '20240018', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(22, 24, 2, '20240019', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(23, 25, 2, '20240020', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(24, 26, 2, '20240021', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(35, 6, 3, '20240001', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(36, 7, 3, '20240002', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(37, 8, 3, '20240003', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(38, 9, 3, '20240004', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(39, 10, 3, '20240005', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(40, 11, 3, '20240006', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(41, 12, 3, '20240007', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(42, 6, 4, '20240001', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(43, 7, 4, '20240002', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(44, 8, 4, '20240003', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(45, 6, 5, '20240001', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(46, 7, 5, '20240002', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(47, 8, 5, '20240003', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(48, 9, 5, '20240004', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(49, 10, 5, '20240005', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(52, 6, 6, '20240001', '081200000000', 'Orang Tua', '081200000001', 'Data contoh', NULL, 'disetujui', '2026-10-23 14:56:33', '2026-10-23 14:56:33'),
(53, 1, 1, '202210045', '081200000000', 'Orang Tua', '081200000001', 'Data demo', NULL, 'disetujui', '2026-10-23 15:03:27', '2026-10-23 15:03:27'),
(54, 1, 2, '202210045', '081200000000', 'Orang Tua', '081200000001', 'Data demo', NULL, 'disetujui', '2026-10-23 15:03:27', '2026-10-23 15:03:27'),
(56, 1, 3, '202210045', '08121234567', 'dfdfdfdf', '0812876543', 'dfsgfgdfgfdg', NULL, 'disetujui', '2026-10-23 15:05:44', '2026-10-24 08:58:32'),
(59, 39, 1, '202110072', '081200000000', 'Orang Tua', '081200000001', 'Ingin belajar dan berprestasi', NULL, 'menunggu', '2026-10-10 08:00:00', NULL),
(60, 38, 1, '202110071', '081200000000', 'Orang Tua', '081200000001', 'Ingin belajar dan berprestasi', NULL, 'ditolak', '2026-10-10 08:00:00', '2026-10-24 23:54:42'),
(61, 40, 1, '202210073', '081200000000', 'Orang Tua', '081200000001', 'Ingin belajar dan berprestasi', NULL, 'menunggu', '2026-10-10 08:00:00', NULL),
(62, 41, 3, '202110074', '081200000000', 'Orang Tua', '081200000001', 'Tertarik keamanan digital', NULL, 'menunggu', '2026-10-11 09:00:00', NULL),
(63, 1, 4, '202210045', '08121234567', 'GURUH DIMAS NURDOYO', '0812876543', 'GATAUUU', NULL, 'disetujui', '2026-10-24 15:42:09', '2026-10-24 15:44:11'),
(65, 38, 3, '202110071', '08121234567', 'Mulyono Ganteng', '0812876543', 'Ingin menjadi koruptor', NULL, 'disetujui', '2026-10-24 23:24:23', '2026-10-24 23:56:17');

-- --------------------------------------------------------

--
-- Table structure for table `pengumpulan`
--

CREATE TABLE `pengumpulan` (
  `id` int UNSIGNED NOT NULL,
  `tugas_id` int UNSIGNED NOT NULL,
  `siswa_id` int UNSIGNED NOT NULL,
  `nama_file` varchar(160) COLLATE utf8mb4_unicode_ci NOT NULL,
  `path_file` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` enum('dikirim','selesai') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'dikirim',
  `uploaded_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `nilai` varchar(3) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `dinilai_at` datetime DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengumpulan`
--

INSERT INTO `pengumpulan` (`id`, `tugas_id`, `siswa_id`, `nama_file`, `path_file`, `status`, `uploaded_at`, `nilai`, `pesan`, `dinilai_at`) VALUES
(1, 3, 1, 'laporan-ros.pdf', 'uploads/tugas/demo/laporan-ros.pdf', 'selesai', '2026-10-23 15:08:30', NULL, NULL, NULL),
(2, 1, 1, 'laporan-iot.pdf', 'uploads/tugas/demo/laporan-iot.pdf', 'selesai', '2026-10-23 15:08:30', 'A+', '', '2026-10-24 15:48:49');

-- --------------------------------------------------------

--
-- Table structure for table `pengumuman`
--

CREATE TABLE `pengumuman` (
  `id` int UNSIGNED NOT NULL,
  `penulis` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `judul` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `label` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `isi` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `penulis_id` int UNSIGNED DEFAULT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengumuman`
--

INSERT INTO `pengumuman` (`id`, `penulis`, `judul`, `label`, `isi`, `created_at`, `penulis_id`) VALUES
(1, 'Abdul Husein, S.T', 'Jadwal Latihan Pekan Ini', 'Robotic', 'Latihan Project Robotic akan dilaksanakan  08.00 – 11.00 di Lab.', '2026-10-23 12:46:47', 2),
(2, 'Abdul Husein, S.T', 'Tugas Konektivitas & IoT Cloud', 'Robotic', 'Deadline Hari Rabu Tanggal 21 Mei.', '2026-10-23 09:46:47', 2),
(3, 'Lukman Hakim, S.Pd', 'Perubahan Jadwal Latihan Bola', 'Basket', 'Latihan hari sabtu ini ditunda.', '2026-10-22 08:46:47', NULL);

-- --------------------------------------------------------

--
-- Table structure for table `pengumuman_komentar`
--

CREATE TABLE `pengumuman_komentar` (
  `id` int UNSIGNED NOT NULL,
  `pengumuman_id` int UNSIGNED NOT NULL,
  `user_id` int UNSIGNED NOT NULL,
  `isi` varchar(500) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengumuman_komentar`
--

INSERT INTO `pengumuman_komentar` (`id`, `pengumuman_id`, `user_id`, `isi`, `created_at`) VALUES
(1, 1, 1, 'Siap, akan hadir!', '2026-10-24 00:50:57'),
(2, 1, 37, 'baik pak!', '2026-10-24 01:05:57');

-- --------------------------------------------------------

--
-- Table structure for table `penilaian`
--

CREATE TABLE `penilaian` (
  `id` int UNSIGNED NOT NULL,
  `siswa_id` int UNSIGNED NOT NULL,
  `aspek` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nilai` tinyint UNSIGNED NOT NULL,
  `predikat` char(1) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `catatan` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `semester` varchar(30) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'Ganjil 2026'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `penilaian`
--

INSERT INTO `penilaian` (`id`, `siswa_id`, `aspek`, `nilai`, `predikat`, `catatan`, `semester`) VALUES
(1, 1, 'Kemandirian', 90, 'A', 'Sangat aktif dan mandiri dalam kegiatan', 'Ganjil 2026'),
(2, 1, 'Kerjasama', 81, 'B', 'Mampu bekerja tim dengan baik', 'Ganjil 2026'),
(3, 1, 'Kedisiplinan', 75, 'C', 'Perlu meningkatkan ketepatan waktu', 'Ganjil 2026'),
(4, 1, 'Kreativitas', 88, 'B', 'Ide-ide segar dan inovatif', 'Ganjil 2026'),
(5, 1, 'Kepemimpinan', 70, 'B', 'Perlu meningkatkan kemampuan memimpin kelompok', 'Ganjil 2026'),
(6, 1, 'Kehadiran', 70, 'B', 'Perlu meningkatkan kehadiran', 'Ganjil 2026');

-- --------------------------------------------------------

--
-- Table structure for table `pertemuan`
--

CREATE TABLE `pertemuan` (
  `id` int UNSIGNED NOT NULL,
  `ekskul_id` int UNSIGNED NOT NULL,
  `judul` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tanggal` date NOT NULL,
  `jam` time NOT NULL,
  `lokasi` varchar(80) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pertemuan`
--

INSERT INTO `pertemuan` (`id`, `ekskul_id`, `judul`, `tanggal`, `jam`, `lokasi`) VALUES
(1, 1, 'Pertemuan 6: Kinematika Robotika Dasar (Forward Kinematics)', '2026-10-09', '08:00:00', 'Lab AI'),
(2, 1, 'Pertemuan 7: Kinematika Invers (Inverse Kinematics)', '2026-10-16', '08:00:00', 'Lab AI'),
(3, 1, 'Jadwal Latihan Pekan Ini', '2026-10-26', '08:00:00', 'Lab AI');

-- --------------------------------------------------------

--
-- Table structure for table `presensi`
--

CREATE TABLE `presensi` (
  `id` int UNSIGNED NOT NULL,
  `pertemuan_id` int UNSIGNED NOT NULL,
  `siswa_id` int UNSIGNED NOT NULL,
  `status` enum('hadir','izin','sakit','alpa') COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `siswa_profil`
--

CREATE TABLE `siswa_profil` (
  `user_id` int UNSIGNED NOT NULL,
  `email` varchar(120) NOT NULL DEFAULT '',
  `telepon` varchar(20) NOT NULL DEFAULT '',
  `alamat` varchar(255) NOT NULL DEFAULT '',
  `bio` text,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_0900_ai_ci;

--
-- Dumping data for table `siswa_profil`
--

INSERT INTO `siswa_profil` (`user_id`, `email`, `telepon`, `alamat`, `bio`, `updated_at`) VALUES
(1, '', '', '', '', '2026-10-23 09:12:48');

-- --------------------------------------------------------

--
-- Table structure for table `tugas`
--

CREATE TABLE `tugas` (
  `id` int UNSIGNED NOT NULL,
  `siswa_id` int UNSIGNED NOT NULL,
  `judul` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `deadline` date NOT NULL,
  `status` enum('aktif','selesai') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'aktif'
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tugas`
--

INSERT INTO `tugas` (`id`, `siswa_id`, `judul`, `deadline`, `status`) VALUES
(1, 1, 'Konektivitas & IoT Cloud', '2026-10-26', 'aktif'),
(2, 1, 'Laporan Project Robotic', '2026-10-29', 'aktif');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int UNSIGNED NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `role` enum('siswa','pembimbing') COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT 'siswa',
  `nis` varchar(20) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `angkatan` year DEFAULT NULL,
  `kelas` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama`, `role`, `nis`, `angkatan`, `kelas`, `created_at`) VALUES
(1, 'budi', '$2y$10$y9BRzXL2VRFTlbdOAFZEKOlClxpEnd63qid2zkeDK3JKmnmoc..ty', 'Budi Setiawan 1', 'siswa', '202210045', '2025', 'Kelas XI IPA 2', '2026-10-23 07:41:36'),
(2, 'pak.andi', '$2y$10$ObPT/Nynp5.YmU2Ejb1QbOPdIMPDEFgmlIfA0n28KwhQEsHoxOmkK', 'Abdul Husein, S.T', 'pembimbing', NULL, NULL, NULL, '2026-10-23 07:41:36'),
(3, 'bima', '!', 'Bima Pratama', 'siswa', '202210051', '2025', 'X IPA 1', '2026-10-23 07:56:32'),
(4, 'eka', '!', 'Eka Wulandari', 'siswa', '202210052', '2025', 'X IPA 1', '2026-10-23 07:56:32'),
(5, 'gilang', '!', 'Gilang Ramadhan', 'siswa', '202210053', '2025', 'XI IPA 1', '2026-10-23 07:56:32'),
(6, 'contoh1', '!', 'Siswa Contoh 1', 'siswa', '20240001', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(7, 'contoh2', '!', 'Siswa Contoh 2', 'siswa', '20240002', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(8, 'contoh3', '!', 'Siswa Contoh 3', 'siswa', '20240003', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(9, 'contoh4', '!', 'Siswa Contoh 4', 'siswa', '20240004', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(10, 'contoh5', '!', 'Siswa Contoh 5', 'siswa', '20240005', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(11, 'contoh6', '!', 'Siswa Contoh 6', 'siswa', '20240006', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(12, 'contoh7', '!', 'Siswa Contoh 7', 'siswa', '20240007', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(13, 'contoh8', '!', 'Siswa Contoh 8', 'siswa', '20240008', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(14, 'contoh9', '!', 'Siswa Contoh 9', 'siswa', '20240009', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(15, 'contoh10', '!', 'Siswa Contoh 10', 'siswa', '20240010', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(16, 'contoh11', '!', 'Siswa Contoh 11', 'siswa', '20240011', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(17, 'contoh12', '!', 'Siswa Contoh 12', 'siswa', '20240012', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(18, 'contoh13', '!', 'Siswa Contoh 13', 'siswa', '20240013', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(19, 'contoh14', '!', 'Siswa Contoh 14', 'siswa', '20240014', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(20, 'contoh15', '!', 'Siswa Contoh 15', 'siswa', '20240015', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(21, 'contoh16', '!', 'Siswa Contoh 16', 'siswa', '20240016', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(22, 'contoh17', '!', 'Siswa Contoh 17', 'siswa', '20240017', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(23, 'contoh18', '!', 'Siswa Contoh 18', 'siswa', '20240018', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(24, 'contoh19', '!', 'Siswa Contoh 19', 'siswa', '20240019', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(25, 'contoh20', '!', 'Siswa Contoh 20', 'siswa', '20240020', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(26, 'contoh21', '!', 'Siswa Contoh 21', 'siswa', '20240021', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(27, 'contoh22', '!', 'Siswa Contoh 22', 'siswa', '20240022', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(28, 'contoh23', '!', 'Siswa Contoh 23', 'siswa', '20240023', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(29, 'contoh24', '!', 'Siswa Contoh 24', 'siswa', '20240024', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(30, 'contoh25', '!', 'Siswa Contoh 25', 'siswa', '20240025', '2024', 'X IPA 1', '2026-10-23 07:56:32'),
(37, 'rina', '!', 'Rina Kusuma', 'siswa', '202210060', '2025', 'XI IPA 2', '2026-10-23 18:50:57'),
(38, 'dimas', '$2y$10$Zmvp3PvjzxcWSu.YgewLjutJSV..6bnZBETixA8h29bk0hxtV.mOy', 'Dimas Saputra', 'siswa', '202110071', '2024', 'XII IPA 2', '2026-10-23 19:00:25'),
(39, 'agus', '!', 'Agus Santoso', 'siswa', '202110072', '2024', 'XII IPA 1', '2026-10-23 19:00:25'),
(40, 'rahayu', '!', 'Rahayu Dwi Kartika', 'siswa', '202210073', '2025', 'XI IPS 1', '2026-10-23 19:00:25'),
(41, 'randi', '!', 'Randi Darmawan', 'siswa', '202110074', '2024', 'XII IPA 2', '2026-10-23 19:00:25'),
(42, 'triyanda', '$2y$10$qBEm9s0Dwe00LvqJYjw7RegcRgvXIYhZCRkhQhcTvy.sgtfiPJpRK', 'Triyanda', 'siswa', '202210050', '2025', 'Kelas XI IPA 2', '2026-10-24 16:13:58');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `absensi`
--
ALTER TABLE `absensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_absensi` (`pertemuan_id`,`siswa_id`),
  ADD KEY `idx_absensi_siswa` (`siswa_id`);

--
-- Indexes for table `ekskul`
--
ALTER TABLE `ekskul`
  ADD PRIMARY KEY (`id`),
  ADD KEY `fk_ekskul_pembimbing` (`pembimbing_id`);

--
-- Indexes for table `ekskul_absensi`
--
ALTER TABLE `ekskul_absensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_ea` (`pertemuan_id`,`siswa_id`),
  ADD KEY `idx_ea_siswa` (`siswa_id`);

--
-- Indexes for table `ekskul_nilai`
--
ALTER TABLE `ekskul_nilai`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_en_siswa` (`siswa_id`,`semester`);

--
-- Indexes for table `ekskul_pengumuman`
--
ALTER TABLE `ekskul_pengumuman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ekskul_id` (`ekskul_id`);

--
-- Indexes for table `ekskul_pertemuan`
--
ALTER TABLE `ekskul_pertemuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ep_ekskul` (`ekskul_id`),
  ADD KEY `idx_ep_tanggal` (`tanggal`);

--
-- Indexes for table `ekskul_tugas`
--
ALTER TABLE `ekskul_tugas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ekskul_id` (`ekskul_id`);

--
-- Indexes for table `info_komentar`
--
ALTER TABLE `info_komentar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ik_pengumuman` (`pengumuman_id`);

--
-- Indexes for table `info_pengumuman`
--
ALTER TABLE `info_pengumuman`
  ADD PRIMARY KEY (`id`),
  ADD KEY `idx_ip_ekskul` (`ekskul_id`),
  ADD KEY `idx_ip_waktu` (`created_at`);

--
-- Indexes for table `jadwal`
--
ALTER TABLE `jadwal`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pembimbing_profil`
--
ALTER TABLE `pembimbing_profil`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_siswa_ekskul` (`siswa_id`,`ekskul_id`),
  ADD KEY `ekskul_id` (`ekskul_id`);

--
-- Indexes for table `pengumpulan`
--
ALTER TABLE `pengumpulan`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_tugas_siswa` (`tugas_id`,`siswa_id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indexes for table `pengumuman`
--
ALTER TABLE `pengumuman`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `pengumuman_komentar`
--
ALTER TABLE `pengumuman_komentar`
  ADD PRIMARY KEY (`id`),
  ADD KEY `pengumuman_id` (`pengumuman_id`),
  ADD KEY `user_id` (`user_id`);

--
-- Indexes for table `penilaian`
--
ALTER TABLE `penilaian`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indexes for table `pertemuan`
--
ALTER TABLE `pertemuan`
  ADD PRIMARY KEY (`id`),
  ADD KEY `ekskul_id` (`ekskul_id`);

--
-- Indexes for table `presensi`
--
ALTER TABLE `presensi`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_presensi` (`pertemuan_id`,`siswa_id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indexes for table `siswa_profil`
--
ALTER TABLE `siswa_profil`
  ADD PRIMARY KEY (`user_id`);

--
-- Indexes for table `tugas`
--
ALTER TABLE `tugas`
  ADD PRIMARY KEY (`id`),
  ADD KEY `siswa_id` (`siswa_id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `uq_username_role` (`username`,`role`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `absensi`
--
ALTER TABLE `absensi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `ekskul`
--
ALTER TABLE `ekskul`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `ekskul_absensi`
--
ALTER TABLE `ekskul_absensi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=56;

--
-- AUTO_INCREMENT for table `ekskul_nilai`
--
ALTER TABLE `ekskul_nilai`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `ekskul_pengumuman`
--
ALTER TABLE `ekskul_pengumuman`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `ekskul_pertemuan`
--
ALTER TABLE `ekskul_pertemuan`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `ekskul_tugas`
--
ALTER TABLE `ekskul_tugas`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `info_komentar`
--
ALTER TABLE `info_komentar`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=6;

--
-- AUTO_INCREMENT for table `info_pengumuman`
--
ALTER TABLE `info_pengumuman`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `jadwal`
--
ALTER TABLE `jadwal`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=66;

--
-- AUTO_INCREMENT for table `pengumpulan`
--
ALTER TABLE `pengumpulan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `pengumuman`
--
ALTER TABLE `pengumuman`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `pengumuman_komentar`
--
ALTER TABLE `pengumuman_komentar`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `penilaian`
--
ALTER TABLE `penilaian`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=7;

--
-- AUTO_INCREMENT for table `pertemuan`
--
ALTER TABLE `pertemuan`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `presensi`
--
ALTER TABLE `presensi`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT;

--
-- AUTO_INCREMENT for table `tugas`
--
ALTER TABLE `tugas`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int UNSIGNED NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=44;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `ekskul`
--
ALTER TABLE `ekskul`
  ADD CONSTRAINT `fk_ekskul_pembimbing` FOREIGN KEY (`pembimbing_id`) REFERENCES `users` (`id`) ON DELETE SET NULL;

--
-- Constraints for table `ekskul_pengumuman`
--
ALTER TABLE `ekskul_pengumuman`
  ADD CONSTRAINT `ekskul_pengumuman_ibfk_1` FOREIGN KEY (`ekskul_id`) REFERENCES `ekskul` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `ekskul_tugas`
--
ALTER TABLE `ekskul_tugas`
  ADD CONSTRAINT `ekskul_tugas_ibfk_1` FOREIGN KEY (`ekskul_id`) REFERENCES `ekskul` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pendaftaran`
--
ALTER TABLE `pendaftaran`
  ADD CONSTRAINT `pendaftaran_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pendaftaran_ibfk_2` FOREIGN KEY (`ekskul_id`) REFERENCES `ekskul` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengumpulan`
--
ALTER TABLE `pengumpulan`
  ADD CONSTRAINT `pengumpulan_ibfk_1` FOREIGN KEY (`tugas_id`) REFERENCES `ekskul_tugas` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengumpulan_ibfk_2` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pengumuman_komentar`
--
ALTER TABLE `pengumuman_komentar`
  ADD CONSTRAINT `pengumuman_komentar_ibfk_1` FOREIGN KEY (`pengumuman_id`) REFERENCES `pengumuman` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `pengumuman_komentar_ibfk_2` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `penilaian`
--
ALTER TABLE `penilaian`
  ADD CONSTRAINT `penilaian_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `pertemuan`
--
ALTER TABLE `pertemuan`
  ADD CONSTRAINT `pertemuan_ibfk_1` FOREIGN KEY (`ekskul_id`) REFERENCES `ekskul` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `presensi`
--
ALTER TABLE `presensi`
  ADD CONSTRAINT `presensi_ibfk_1` FOREIGN KEY (`pertemuan_id`) REFERENCES `pertemuan` (`id`) ON DELETE CASCADE,
  ADD CONSTRAINT `presensi_ibfk_2` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;

--
-- Constraints for table `tugas`
--
ALTER TABLE `tugas`
  ADD CONSTRAINT `tugas_ibfk_1` FOREIGN KEY (`siswa_id`) REFERENCES `users` (`id`) ON DELETE CASCADE;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
