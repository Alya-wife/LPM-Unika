-- phpMyAdmin SQL Dump
-- version 5.2.0
-- https://www.phpmyadmin.net/
--
-- Host: localhost:3306
-- Generation Time: Sep 03, 2026 at 07:39 AM
-- Server version: 8.0.30
-- PHP Version: 8.1.10

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";


/*!40101 SET @OLD_CHARACTER_SET_CLIENT=@@CHARACTER_SET_CLIENT */;
/*!40101 SET @OLD_CHARACTER_SET_RESULTS=@@CHARACTER_SET_RESULTS */;
/*!40101 SET @OLD_COLLATION_CONNECTION=@@COLLATION_CONNECTION */;
/*!40101 SET NAMES utf8mb4 */;

--
-- Database: `lpm_scu`
--

-- --------------------------------------------------------

--
-- Table structure for table `akreditasi`
--

CREATE TABLE `akreditasi` (
  `id` int NOT NULL,
  `lembaga` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `peringkat` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `keterangan` text COLLATE utf8mb4_unicode_ci,
  `warna` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'var(--navy)',
  `urutan` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `akreditasi`
--

INSERT INTO `akreditasi` (`id`, `lembaga`, `peringkat`, `keterangan`, `warna`, `urutan`, `created_at`) VALUES
(1, 'BAN-PT Institusi', 'A', 'Akreditasi Unggul (A) dari Badan Akreditasi Nasional Perguruan Tinggi', '#4A148C', 1, '2026-09-03 01:47:11'),
(2, 'LAM-PTKes', 'A', 'Seluruh Program Studi Kesehatan terakreditasi LAM-PTKes', '#1565C0', 2, '2026-09-03 01:47:11'),
(3, 'AQAS', 'Internasional', 'Pengakuan internasional dari Accreditation Agency in the Field of Study and Teaching (AQAS), Jerman', '#2E7D32', 3, '2026-09-03 01:47:11');

-- --------------------------------------------------------

--
-- Table structure for table `berita`
--

CREATE TABLE `berita` (
  `id` int NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `tipe` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'Berita',
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `konten` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `gambar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_publikasi` date DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `berita`
--

INSERT INTO `berita` (`id`, `judul`, `tipe`, `slug`, `konten`, `gambar`, `tanggal_publikasi`, `created_at`) VALUES
(1, 'Mencoba 01', 'Berita', 'mencoba-01', 'Lorem ipsum dolor sit amet, consectetur adipiscing elit. Ut pretium pretium leo at luctus. Aenean efficitur molestie est sit amet iaculis. In lobortis euismod est vitae imperdiet. Fusce tincidunt facilisis consectetur. Curabitur iaculis at lorem id varius. Etiam sit amet feugiat eros. Nam eros est, malesuada non egestas in, pulvinar a mi. Aliquam ac congue tortor, nec accumsan quam. Donec posuere et enim sed malesuada. Mauris convallis nisi eget fringilla placerat. Duis gravida non metus quis congue. Sed mollis dolor vitae nisl blandit lobortis. Suspendisse eget placerat elit, vel malesuada nunc. Nullam maximus vitae augue eget posuere.\r\n\r\nSed id fringilla mauris, lobortis aliquet nulla. Aliquam erat volutpat. Integer volutpat placerat metus nec rhoncus. Fusce eleifend dignissim libero, eget sollicitudin lectus porta vitae. Proin pulvinar tempus tincidunt. Cras luctus malesuada mauris a sagittis. Proin hendrerit nibh id risus ultricies aliquam.\r\n\r\nCurabitur efficitur dui sit amet vestibulum pulvinar. Suspendisse porttitor, felis in cursus auctor, ante est feugiat magna, sed ornare justo dolor non massa. Vestibulum quis consequat ex, fringilla pharetra velit. Integer porta diam vel facilisis rhoncus. Integer egestas lorem a imperdiet dapibus. Vestibulum sit amet sagittis lorem. Maecenas bibendum nibh a est fringilla, ut pellentesque metus fermentum. Nullam sollicitudin urna ac euismod auctor. Pellentesque tempus, sapien vitae dignissim pellentesque, dolor sem pulvinar lectus, sit amet vestibulum arcu tellus eget leo.\r\n\r\nMorbi posuere tincidunt orci, non dapibus leo maximus a. Duis vehicula gravida metus ut blandit. Vestibulum a luctus leo, ut placerat lectus. Praesent non posuere ex. Aenean id justo at ante tempor hendrerit. Aliquam congue sagittis lorem ac sagittis. Nunc placerat nibh in sapien feugiat, ac tempor ante suscipit. Integer in ex leo. Quisque a sem et elit aliquet sagittis. Suspendisse potenti. Maecenas commodo vulputate mauris, id tincidunt augue sollicitudin at. Curabitur dapibus nisi nec augue ultricies, vel feugiat orci efficitur. Aliquam vitae malesuada felis. Pellentesque pulvinar tristique eleifend.\r\n\r\nCurabitur dui augue, vulputate non semper tempus, fermentum sed arcu. Sed egestas augue vitae augue malesuada, ac varius odio venenatis. Sed vulputate vel tortor eget consectetur. Integer id lobortis eros. Suspendisse venenatis ex eget lacus tincidunt pulvinar. Donec et arcu lacinia, interdum dolor at, bibendum ex. Vivamus nulla odio, consectetur quis ipsum vitae, rutrum luctus ante. Morbi quis lacus vel quam accumsan mattis.', 'berita_6a98fa66a45fd.png', '2026-09-03', '2026-09-03 04:41:10'),
(2, 'yeye menang', 'Penghargaan', 'yeye-menang', 'wertyu', NULL, '2026-09-03', '2026-09-03 07:09:35');

-- --------------------------------------------------------

--
-- Table structure for table `buletin`
--

CREATE TABLE `buletin` (
  `id` int NOT NULL,
  `judul` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `edisi` varchar(100) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `deskripsi` text CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci,
  `file_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci NOT NULL,
  `cover_path` varchar(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `tanggal_terbit` date DEFAULT NULL,
  `is_aktif` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `buletin`
--

INSERT INTO `buletin` (`id`, `judul`, `edisi`, `deskripsi`, `file_path`, `cover_path`, `tanggal_terbit`, `is_aktif`, `created_at`) VALUES
(1, 'Buletin Penjaminan Mutu Soegijapranata Catholic University', 'Edisi 14 / April - Agustus 2026', 'Ya gitu', 'bul_6a98f94116c65.pdf', 'cov_6a98f9411951c.jpg', '2026-09-03', 1, '2026-09-03 04:36:17');

-- --------------------------------------------------------

--
-- Table structure for table `dokumen`
--

CREATE TABLE `dokumen` (
  `id` int NOT NULL,
  `nama_dokumen` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `file_path` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `dokumen`
--

INSERT INTO `dokumen` (`id`, `nama_dokumen`, `kategori`, `file_path`, `created_at`) VALUES
(1, 'D. STANDAR TAMBAHAN', 'Standar Mutu', 'dok_6a98ea3c4a85c.pdf', '2026-09-03 03:32:12');

-- --------------------------------------------------------

--
-- Table structure for table `feedback`
--

CREATE TABLE `feedback` (
  `id` int NOT NULL,
  `nama` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `email` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jenis_layanan` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `instansi` varchar(150) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `pesan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `status` varchar(30) COLLATE utf8mb4_unicode_ci DEFAULT 'Belum Dibaca',
  `tanggal` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `feedback`
--

INSERT INTO `feedback` (`id`, `nama`, `email`, `jenis_layanan`, `instansi`, `pesan`, `status`, `tanggal`) VALUES
(1, 'Budi Santoso, M.Kom.', 'budi.santoso@unika.ac.id', 'Konsultasi Mutu', 'Fakultas Ilmu Komputer', 'Mohon jadwal pendampingan revisi dokumen instrumen evaluasi AMI untuk program studi Sistem Informasi.', 'Belum Dibaca', '2026-09-03 02:09:38'),
(2, 'Budi Santoso, M.Kom.', 'budi.santoso@unika.ac.id', 'Konsultasi Mutu', 'Fakultas Ilmu Komputer', 'Mohon jadwal pendampingan revisi dokumen instrumen evaluasi AMI untuk program studi Sistem Informasi.', 'Belum Dibaca', '2026-09-03 02:09:38');

-- --------------------------------------------------------

--
-- Table structure for table `hero_slides`
--

CREATE TABLE `hero_slides` (
  `id` int NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `subjudul` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `highlight_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `gambar` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `btn_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Akses Dokumen SPMI',
  `btn_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT 'spmi.php',
  `btn_secondary_text` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `btn_secondary_link` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `urutan` int DEFAULT '1',
  `is_active` tinyint(1) DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `hero_slides`
--

INSERT INTO `hero_slides` (`id`, `judul`, `subjudul`, `highlight_text`, `deskripsi`, `gambar`, `btn_text`, `btn_link`, `btn_secondary_text`, `btn_secondary_link`, `urutan`, `is_active`, `created_at`) VALUES
(1, 'Lembaga Penjaminan Mutu SCU hore hore', 'Soegijapranata Catholic University', 'Penjaminan Mutu', 'Berkomitmen mewujudkan mutu pendidikan tinggi yang unggul, berkelanjutan, dan berdaya saing global melalui sistem penjaminan mutu internal yang terstruktur.', 'https://images.unsplash.com/photo-1541339907198-e08756dedf3f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80', 'Akses Dokumen SPMI', 'spmi.php', 'Profil LPM', 'profil.php', 1, 1, '2026-09-03 01:47:11'),
(2, 'Ekosistem Mutu Internal', 'Sistem Terintegrasi', 'Mutu Internal', 'Memastikan setiap program studi dan unit kerja menjalankan siklus Penetapan, Pelaksanaan, Evaluasi, Pengendalian, dan Peningkatan (PPEPP) secara konsisten.', 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80', 'Kebijakan Mutu', 'spmi.php?kategori=Kebijakan', '', '', 2, 1, '2026-09-03 01:47:11'),
(3, 'Menuju Standar Global', 'Akreditasi Unggul', 'Standar Global', 'Mendukung penuh pencapaian akreditasi unggul nasional dan internasional untuk seluruh program studi di Soegijapranata Catholic University.', 'https://images.unsplash.com/photo-1577415124269-b911f4402a14?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80', 'Lihat Akreditasi', 'profil.php#akreditasi', '', '', 3, 1, '2026-09-03 01:47:11'),
(4, 'tralala', 'Soegijapranata Catholic University', 'Penjaminan Mutu', 'coba ke 4', 'slide_6a99219cea9cc.png', 'Akses Dokumen SPMI', 'spmi.php', '', '', 4, 1, '2026-09-03 07:28:28');

-- --------------------------------------------------------

--
-- Table structure for table `kalender_mutu`
--

CREATE TABLE `kalender_mutu` (
  `id` int NOT NULL,
  `tahun_akademik` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL DEFAULT '2026–2027',
  `bulan_tahun` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `urutan` int NOT NULL DEFAULT '1',
  `kegiatan` text COLLATE utf8mb4_unicode_ci NOT NULL,
  `is_active` tinyint(1) NOT NULL DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kalender_mutu`
--

INSERT INTO `kalender_mutu` (`id`, `tahun_akademik`, `bulan_tahun`, `urutan`, `kegiatan`, `is_active`, `created_at`, `updated_at`) VALUES
(1, '2026–2027', 'SEPTEMBER 2026', 1, 'Sosialisasi Kalender Mutu\nPenetapan sasaran mutu dan indikator kinerja mutu institusi\nUpdate data pelaporan SPMI', 1, '2026-09-03 03:53:57', '2026-09-03 03:53:57'),
(2, '2026–2027', 'OKTOBER 2026', 2, 'Update dokumen SPMI dan indikator standar mutu yang melampaui dan terukur\nPemutakhiran instrumen monitoring mutu\nMonev data PDDIKTI\nMonev ketercapaian visi misi, SISTA, pengelolaan risiko', 1, '2026-09-03 03:53:57', '2026-09-03 03:53:57'),
(3, '2026–2027', 'NOVEMBER 2026', 3, 'Perencanaan dan persiapan AMI\nRekrutmen Auditor AMI\nKoordinasi rutin GPM\nBuletin Jamus', 1, '2026-09-03 03:53:57', '2026-09-03 03:53:57'),
(4, '2026–2027', 'DESEMBER 2026', 4, 'Monev : SISTA, Pembelajaran Semester Gasal, Tata Kelola\nLaporan pencapaian pemeringkatan universitas\nBenchmarking', 1, '2026-09-03 03:53:57', '2026-09-03 03:53:57'),
(5, '2026–2027', 'JANUARI 2027', 5, 'Pelaksanaan AMI\nMonev tracer study', 1, '2026-09-03 03:53:57', '2026-09-03 03:53:57'),
(6, '2026–2027', 'FEBRUARI 2027', 6, 'Penyusunan Laporan AMI\nAudit berbasis resiko\nKoordinasi rutin GPM\nBuletin Jamus', 1, '2026-09-03 03:53:57', '2026-09-03 03:53:57'),
(7, '2026–2027', 'MARET 2027', 7, 'Rapat Tinjauan Manajemen (RTM)\nEvaluasi hasil AMI\nAMI Excellence Award', 1, '2026-09-03 03:53:57', '2026-09-03 03:53:57'),
(8, '2026–2027', 'APRIL 2027', 8, 'Monitoring capaian indikator mutu\nPendampingan persiapan akreditasi BAN-PT/ LAM', 1, '2026-09-03 03:53:57', '2026-09-03 03:53:57'),
(9, '2026–2027', 'MEI 2027', 9, 'Identifikasi kebutuhan peningkatan standar\nKoordinasi rutin GPM\nBuletin Jamus', 1, '2026-09-03 03:53:57', '2026-09-03 03:53:57'),
(10, '2026–2027', 'JUNI 2027', 10, 'Monitoring indikator kinerja mutu\nEvaluasi kepuasan pemangku kepentingan\nPenyusunan laporan mutu semester', 1, '2026-09-03 03:53:57', '2026-09-03 03:53:57'),
(11, '2026–2027', 'JULI 2027', 11, 'Review dokumen SPMI\nEvaluasi efektivitas program LPM\nMonev : Pembelajaran Genap, Penelitian dan PkM', 1, '2026-09-03 03:53:57', '2026-09-03 03:53:57'),
(12, '2026–2027', 'AGUSTUS 2027', 12, 'Evaluasi pelaksanaan Kalender Mutu\nPenyusunan Kalender Mutu periode berikutnya\nKoordinasi rutin dengan GPM\nBuletin Jamus\nMonev : luaran capaian tridharma dan ketercapaian IKU', 1, '2026-09-03 03:53:57', '2026-09-03 03:53:57');

-- --------------------------------------------------------

--
-- Table structure for table `kategori_dokumen`
--

CREATE TABLE `kategori_dokumen` (
  `id` int NOT NULL,
  `nama_kategori` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `kategori_dokumen`
--

INSERT INTO `kategori_dokumen` (`id`, `nama_kategori`, `slug`) VALUES
(1, 'Kebijakan Mutu', 'kebijakan-mutu'),
(2, 'Manual Mutu', 'manual-mutu'),
(3, 'Standar Mutu', 'standar-mutu'),
(4, 'Formulir Mutu', 'formulir-mutu'),
(5, 'Regulasi', 'regulasi'),
(6, 'Panduan', 'panduan'),
(7, 'Instrumen', 'instrumen'),
(8, 'Laporan AMI', 'laporan-ami'),
(9, 'SOP', 'sop'),
(10, 'Download Center', 'download-center');

-- --------------------------------------------------------

--
-- Table structure for table `pages`
--

CREATE TABLE `pages` (
  `id` int NOT NULL,
  `judul` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `slug` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `kategori` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT 'Umum',
  `ringkasan` text COLLATE utf8mb4_unicode_ci,
  `konten` longtext COLLATE utf8mb4_unicode_ci,
  `updated_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pages`
--

INSERT INTO `pages` (`id`, `judul`, `slug`, `kategori`, `ringkasan`, `konten`, `updated_at`) VALUES
(1, 'Tugas dan Fungsi LPM', 'tugas-fungsi', 'Profil', 'Tugas dan Fungsi Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata berdasarkan Peraturan No. 01/E.2/Per-UKS/XI/2019.', '<p>Sesuai Peraturan Universitas Katolik Soegijapranata No. 01/E.2/Per-UKS/XI/2019 tentang Organisasi dan Tata Kelola Universitas Katolik Soegijapranata, LPM bertugas merencanakan, melaksanakan, mengevaluasi dan mengembangkan sistem penjaminan mutu.</p>\n<p>Dalam melaksanakan tugas tersebut, LPM menyelenggarakan fungsi:</p>\n<ol>\n<li>Penyediaan data pelaksanaan sistem penjaminan mutu internal sebagai bahan penyusunan kebijakan Universitas.</li>\n<li>Pemberian rekomendasi dan/atau usulan kepada Rektor dalam penyelenggaraan dan pengembangan SPMI.</li>\n<li>Pengkajian dan pengembangan dokumen SPMI maupun dokumen mutu lainnya.</li>\n<li>Pengkoordinasian, pelaksanaan dan monitoring SPMI.</li>\n<li>Pengkoordinasian penyusunan prosedur sistem mutu, instruksi kerja maupun dokumen mutu lainnya.</li>\n<li>Pelaksanaan dan monitoring Audit Mutu Internal (AMI).</li>\n<li>Pengkoordinasian, pembimbingan teknis, monitoring dan evaluasi pelaksanaan proses akreditasi perguruan tinggi (APT) dan program studi (APS).</li>\n<li>Pengawasan pelaksanaan Rapat Tinjauan Manajemen.</li>\n</ol>', '2026-09-03 03:39:09'),
(2, 'Pengantar SPMI SCU', 'pengantar-spmi', 'SPMI', 'Pengenalan dan landasan Sistem Penjaminan Mutu Internal di Soegijapranata Catholic University.', '<p>Sistem Penjaminan Mutu Internal (SPMI) di lingkungan Soegijapranata Catholic University merupakan kegiatan sistemik penjaminan mutu pendidikan tinggi oleh universitas untuk mengawal dan meningkatkan mutu secara berencana dan berkelanjutan.</p><p>SPMI berlandaskan pada nilai-nilai Ignasian, Standar Nasional Pendidikan Tinggi (SN-Dikti), serta tuntutan kebutuhan para pemangku kepentingan (stakeholders).</p>', '2026-09-03 02:03:28'),
(3, 'Alur & Siklus PPEPP', 'alur-ppepp', 'SPMI', 'Penjelasan komprehensif alur PPEPP: Penetapan, Pelaksanaan, Evaluasi, Pengendalian, dan Peningkatan Standar Mutu.', '<p>Siklus PPEPP adalah metodologi penjaminan mutu yang diterapkan secara konsisten di SCU:</p><ol><li><strong>Penetapan (P):</strong> Perumusan dan penetapan standar mutu universitas oleh pimpinan tertinggi.</li><li><strong>Pelaksanaan (P):</strong> Penerapan standar mutu oleh seluruh unit akademik dan non-akademik.</li><li><strong>Evaluasi (E):</strong> Pengukuran capaian standar melalui Audit Mutu Internal (AMI) dan survei kepuasan.</li><li><strong>Pengendalian (P):</strong> Analisis akar masalah dan tindakan korektif terhadap standar yang belum tercapai.</li><li><strong>Peningkatan (P):</strong> Peningkatan target atau rumusan standar yang telah berhasil dipenuhi.</li></ol><div class=\"alert alert-info mt-3\">Untuk sistem pelaporan terintegrasi PPEPP, kunjungi portal <a href=\"https://sista.unika.ac.id\" target=\"_blank\" class=\"fw-bold text-decoration-underline\">SISTA SCU &rarr;</a></div>', '2026-09-03 02:03:28'),
(4, 'Tentang Audit Mutu Internal (AMI)', 'tentang-ami', 'AMI', 'Tujuan, prinsip, dan ruang lingkup Audit Mutu Internal di lingkungan SCU.', '<p>Audit Mutu Internal (AMI) adalah proses pengujian yang sistematik, mandiri, dan terdokumentasi untuk memastikan bahwa pelaksanaan kegiatan di SCU sesuai prosedur dan hasilnya telah sesuai dengan standar yang ditetapkan.</p><p>AMI bukan merupakan proses investigasi atau mencari kesalahan, melainkan proses konfirmasi kesesuaian dan identifikasi peluang peningkatan mutu (opportunities for improvement).</p>', '2026-09-03 02:03:28'),
(5, 'Pedoman AMI', 'pedoman-ami', 'AMI', 'Pedoman operasional standar pelaksanaan Audit Mutu Internal bagi auditor dan auditee.', '<p>Pedoman AMI mengatur tahapan pelaksanaan audit, kode etik auditor internal, kriteria penilaian, format laporan desk evaluation, hingga prosedur rapat tinjauan manajemen (RTM).</p><p>Dokumen lengkap pedoman dapat diunduh melalui bagian Download Center atau Pusat Dokumen.</p>', '2026-09-03 02:03:28'),
(6, 'Instrumen AMI', 'instrumen-ami', 'AMI', 'Daftar instrumen dan checklist audit mutu internal untuk prodi dan unit kerja.', '', '2026-09-03 02:03:28'),
(7, 'Daftar Auditor Mutu Internal', 'auditor-ami', 'AMI', 'Daftar auditor internal bersertifikat di lingkungan Soegijapranata Catholic University.', '', '2026-09-03 02:03:28'),
(8, 'Jadwal Siklus AMI', 'jadwal-ami', 'AMI', 'Kalender dan linimasa pelaksanaan Audit Mutu Internal periode berjalan.', '', '2026-09-03 02:03:28'),
(9, 'Hasil & Laporan AMI', 'hasil-ami', 'AMI', 'Ringkasan eksekutif capaian dan laporan hasil audit mutu internal tahunan.', '', '2026-09-03 02:03:28'),
(10, 'Tindak Lanjut & Rapat Tinjauan Manajemen (RTM)', 'tindak-lanjut-ami', 'AMI', 'Monitoring tindakan perbaikan temuan audit dan pelaksanaan Rapat Tinjauan Manajemen.', '', '2026-09-03 02:03:28'),
(11, 'Akreditasi Institusi Perguruan Tinggi', 'akreditasi-institusi', 'Akreditasi', 'Status, sertifikat, dan riwayat akreditasi institusi Soegijapranata Catholic University.', '<p>Soegijapranata Catholic University (SCU) terakreditasi <strong>A (Unggul)</strong> oleh Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT). SCU senantiasa berkomitmen menjaga dan meningkatkan mutu tata kelola institusi dalam mewujudkan pendidikan berkelas dunia.</p>', '2026-09-03 02:03:28'),
(12, 'Akreditasi Program Studi', 'akreditasi-prodi', 'Akreditasi', 'Daftar peringkat akreditasi seluruh program studi di lingkungan SCU.', '', '2026-09-03 02:03:28'),
(13, 'Lembaga Akreditasi Mandiri (LAM)', 'lembaga-akreditasi', 'Akreditasi', 'Informasi pengakreditasian program studi melalui LAMEMBA, LAM INFOKOM, LAM TEKNIK, LAM-PTKes, LAMSPAK, LAMDEPILAR, dan LAMPTIP.', '<p>Program studi di lingkungan SCU terakreditasi oleh lembaga akreditasi nasional yang diakui pemerintah, antara lain:</p><ul><li><strong>BAN-PT:</strong> Badan Akreditasi Nasional Perguruan Tinggi</li><li><strong>LAMEMBA:</strong> Ekonomi, Manajemen, Bisnis, dan Akuntansi</li><li><strong>LAM INFOKOM:</strong> Informatika dan Komputer</li><li><strong>LAM TEKNIK:</strong> Program Studi Keteknikan</li><li><strong>LAM-PTKes:</strong> Pendidikan Tinggi Kesehatan</li><li><strong>LAMSPAK:</strong> Sains, Pertanian, Kelautan</li><li><strong>LAMDEPILAR:</strong> Desain, Arsitektur, dan Lingkungan</li><li><strong>LAMPTIP:</strong> Pendidikan dan Ilmu Keguruan</li></ul>', '2026-09-03 02:03:28'),
(14, 'Indikator Kinerja Mutu', 'indikator-mutu', 'Mutu & Data', 'Indikator Kinerja Utama (IKU) dan Indikator Kinerja Tambahan (IKT) universitas.', '', '2026-09-03 02:03:28'),
(15, 'Dashboard Mutu Universitas', 'dashboard-mutu', 'Mutu & Data', 'Visualisasi data metrik mutu akademik, riset, dan kepuasan layanan.', '', '2026-09-03 02:03:28'),
(16, 'Sinkronisasi PDDikti', 'pddikti', 'Mutu & Data', 'Pelaporan data pangkalan pendidikan tinggi dan verifikasi pelaporan berkala.', '', '2026-09-03 02:03:28'),
(17, 'Tanya Jawab (FAQ) Mutu', 'tanya-jawab', 'Knowledge Center', 'Pertanyaan yang sering diajukan seputar penjaminan mutu, akreditasi, dan SPMI.', '', '2026-09-03 02:03:28'),
(18, 'Glosarium Istilah Mutu', 'glossary', 'Knowledge Center', 'Daftar istilah dan definisi dalam sistem penjaminan mutu pendidikan tinggi.', '', '2026-09-03 02:03:28'),
(19, 'Buletin JAMUS (Jaminan Mutu SCU)', 'buletin-jamus', 'Knowledge Center', 'Publikasi berkala buletin LPM berisi ulasan budaya mutu dan best practice.', '', '2026-09-03 02:03:28'),
(20, 'Artikel & Opini Mutu', 'artikel-mutu', 'Knowledge Center', 'Kumpulan tulisan dan artikel reflektif seputar penjaminan mutu pendidikan.', '', '2026-09-03 02:03:28'),
(21, 'Kalender Mutu', 'kalender-mutu', 'Knowledge Center', 'Agenda kegiatan mutu, workshop, survei kepuasan, dan rapat evaluasi tahunan.', '', '2026-09-03 02:03:28'),
(22, 'Visi, Misi dan Tujuan', 'visi-misi', 'Profil', 'Visi, Misi dan Tujuan Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata', '<h2>Visi</h2><blockquote style=\"font-size:1.15rem;font-style:italic;color:#0A192F;border-left:4px solid #6A1B9A;padding-left:1rem;margin:1rem 0;\">Terwujudnya budaya mutu melalui sistem penjaminan mutu yang mendukung pencapaian visi Universitas Katolik Soegijapranata.</blockquote><h2 style=\"margin-top:2rem;\">Misi</h2><ol style=\"line-height:1.8;padding-left:1.25rem;\"><li>Menyelenggarakan penyediaan data yang sahih dan valid di bidang penjaminan mutu melalui sistem monitoring dan evaluasi yang terstruktur, terencana dan berkelanjutan.</li><li>Menyelenggarakan sistem penjaminan mutu yang efektif, partisipatif, dan dihidupi oleh seluruh sivitas akademika dan tenaga kependidikan.</li><li>Membangun budaya mutu melalui sinergi, pendampingan, dan kolaborasi dengan gugus mutu serta para pemangku kepentingan.</li><li>Memfasilitasi pengembangan kapasitas dan kompetensi sumber daya manusia di bidang penjaminan mutu.</li><li>Mendorong peningkatan mutu berkelanjutan untuk mendukung pencapaian visi dan reputasi Universitas.</li></ol><h2 style=\"margin-top:2rem;\">Tujuan</h2><ol style=\"line-height:1.8;padding-left:1.25rem;\"><li>Terwujudnya sistem penjaminan mutu yang terstruktur, terencana, efektif dan berkelanjutan.</li><li>Terwujudnya sistem monitoring, evaluasi, dan penyediaan data mutu yang sahih, valid dan terintegrasi sebagai dasar pengambilan keputusan.</li><li>Meningkatnya kapasitas dan kompetensi sumber daya manusia di bidang penjaminan mutu.</li><li>Terwujudnya sistem penjaminan mutu berbasis teknologi informasi yang efektif, efisien dan terintegrasi.</li><li>Terwujudnya budaya mutu yang dihidupi oleh seluruh sivitas akademika dan tenaga kependidikan.</li><li>Meningkatnya efektivitas peningkatan mutu berkelanjutan dalam mendukung pencapaian visi dan reputasi Universitas.</li></ol>', '2026-09-03 03:13:41'),
(23, 'Struktur Organisasi & Personel LPM', 'struktur-organisasi', 'Profil', 'Bagan struktur organisasi dan susunan tim Lembaga Penjaminan Mutu UNIKA', '<h2>Bagan Struktur Organisasi</h2><p>Struktur Organisasi Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata:</p><div style=\"text-align:center;margin:1.5rem 0;background:#fff;padding:1.5rem;border-radius:8px;border:1px solid #E2E8F0;\"><img src=\"http://localhost/LPM/assets/images/struktur-organisasi.png\" alt=\"Struktur Organisasi LPM UNIKA\" style=\"max-width:100%;height:auto;border-radius:4px;box-shadow:0 4px 15px rgba(0,0,0,0.06);\"></div><h2>Susunan Personel & Tugas Fungsi</h2><div style=\"margin-bottom:1.5rem;padding:1.25rem;background:#F8FAFC;border-radius:8px;border-left:4px solid #6A1B9A;\"><h4 style=\"margin:0 0 0.25rem;color:#0A192F;\">Stefani Lily Indarto, SE., MM., Ak., CA., CPA.</h4><div style=\"font-weight:700;color:#6A1B9A;font-size:0.9rem;margin-bottom:0.5rem;\">Kepala Lembaga Penjaminan Mutu</div><p style=\"margin:0;font-size:0.9rem;color:#475569;line-height:1.6;\">Memimpin keseluruhan perumusan, pelaksanaan, serta evaluasi kebijakan penjaminan mutu universitas, pengawalan Audit Mutu Internal (AMI), akreditasi, pemeringkatan secara berkelanjutan dan memastikan sinergi di tingkat universitas dengan fakultas/program studi/unit.</p></div><div style=\"margin-bottom:1.5rem;padding:1.25rem;background:#F8FAFC;border-radius:8px;border-left:4px solid #6A1B9A;\"><h4 style=\"margin:0 0 0.25rem;color:#0A192F;\">Vera Retnowati, ST., MM.</h4><div style=\"font-weight:700;color:#6A1B9A;font-size:0.9rem;margin-bottom:0.5rem;\">Sekretaris Lembaga Penjaminan Mutu</div><p style=\"margin:0;font-size:0.9rem;color:#475569;line-height:1.6;\">Mengkoordinasikan pengelolaan program kerja, keuangan, dan administrasi dokumen, serta memastikan tata kelola lembaga berjalan efektif, tertib, dan berkelanjutan.</p></div><div style=\"margin-bottom:1.5rem;padding:1.25rem;background:#F8FAFC;border-radius:8px;border-left:4px solid #6A1B9A;\"><h4 style=\"margin:0 0 0.25rem;color:#0A192F;\">Ir. I.M. Tri Hesti Mulyani, MT.</h4><div style=\"font-weight:700;color:#6A1B9A;font-size:0.9rem;margin-bottom:0.5rem;\">Ketua Pusat Pengembangan Sistem Penjaminan Mutu</div><p style=\"margin:0;font-size:0.9rem;color:#475569;line-height:1.6;\">Mengembangkan dan memastikan pelaksanaan SPMI melalui pengelolaan dokumen mutu, implementasi dan pelaporan mutu di seluruh fakultas/program studi/unit.</p></div><div style=\"margin-bottom:1.5rem;padding:1.25rem;background:#F8FAFC;border-radius:8px;border-left:4px solid #6A1B9A;\"><h4 style=\"margin:0 0 0.25rem;color:#0A192F;\">dr. Maya Yanuarty, M.Biomed</h4><div style=\"font-weight:700;color:#6A1B9A;font-size:0.9rem;margin-bottom:0.5rem;\">Ketua Pusat Audit Mutu Internal</div><p style=\"margin:0;font-size:0.9rem;color:#475569;line-height:1.6;\">Melaksanakan Audit Mutu Internal (AMI) secara terencana dan obyektif melalui koordinasi audit, pelaporan hasil evaluasi, serta rekomendasi perbaikan untuk memastikan efektifitas SPMI dan mendorong perbaikan berkelanjutan universitas.</p></div><div style=\"margin-bottom:1.5rem;padding:1.25rem;background:#F8FAFC;border-radius:8px;border-left:4px solid #6A1B9A;\"><h4 style=\"margin:0 0 0.25rem;color:#0A192F;\">Ir. Lintang Jata Angghita, ST., M.Ling</h4><div style=\"font-weight:700;color:#6A1B9A;font-size:0.9rem;margin-bottom:0.5rem;\">Ketua Pusat Pemeringkatan</div><p style=\"margin:0;font-size:0.9rem;color:#475569;line-height:1.6;\">Mengelola strategi peningkatan posisi universitas dalam berbagai skema pemeringkatan, serta memperkuat reputasi akademik dan kelembagaan.</p></div><div style=\"margin-bottom:1.5rem;padding:1.25rem;background:#F8FAFC;border-radius:8px;border-left:4px solid #6A1B9A;\"><h4 style=\"margin:0 0 0.25rem;color:#0A192F;\">Hermawan, S.M.</h4><div style=\"font-weight:700;color:#6A1B9A;font-size:0.9rem;margin-bottom:0.5rem;\">Staf Tata Usaha Lembaga Penjaminan Mutu</div><p style=\"margin:0;font-size:0.9rem;color:#475569;line-height:1.6;\">Mendukung operasional lembaga melalui layanan administrasi, dokumentasi, pelaporan kegiatan lapangan dan koordinasi kegiatan rutin harian.</p></div>', '2026-09-03 03:17:17'),
(24, 'Siklus PPEPP SPMI', 'ppepp', 'SPMI', 'Siklus PPEPP adalah pilar utama dalam Sistem Penjaminan Mutu Internal (SPMI) di Unika Soegijapranata yang terdiri dari Penetapan, Pelaksanaan, Evaluasi, Pengendalian, dan Peningkatan.', '<p><strong>Siklus PPEPP</strong> adalah pilar utama dalam Sistem Penjaminan Mutu Internal (SPMI) di Unika Soegijapranata. PPEPP merupakan akronim dari lima tahapan kerja terstruktur yaitu:</p>\n<ul>\n<li>Penetapan</li>\n<li>Pelaksanaan</li>\n<li>Evaluasi</li>\n<li>Pengendalian</li>\n<li>Peningkatan</li>\n</ul>\n<hr>\n<h3>1. Penetapan (P)</h3>\n<p>Tahap merumuskan dan menetapkan standar mutu/indicator capaian standar akademik, non-akademik, dan tambahan yang disusun melampaui Standar Nasional Pendidikan Tinggi (SN Dikti).</p>\n\n<h3>2. Pelaksanaan (P)</h3>\n<p>Tahap menerapkan standar mutu/indicator capaian standar akademik, non-akademik, dan tambahan yang telah ditetapkan kedalam aktivitas operasional kampus.</p>\n\n<h3>3. Evaluasi (E)</h3>\n<p>Tahap pemantauan untuk mengukur dan menilai tingkat ketercapaian pelaksanaan terhadap standar/indikator yang sudah ditetapkan. Tahap ini berfungsi mendeteksi secara dini jika terjadi penyimpangan atau hambatan dalam pelaksanaan.</p>\n\n<h3>4. Pengendalian (P)</h3>\n<p>Tahap analisis tindak lanjut terhadap hasil temuan evaluasi melalui Rapat Tinjauan Manajemen di tingkat Program Studi/Fakultas/Unit/Lembaga/Universitas sesuai hasil temuan. Tindak lanjut merupakan tindakan korektif terhadap temuan yang belum optimal dan tindakan untuk mempertahankan hasil evaluasi yang sudah optimal.</p>\n\n<h3>5. Peningkatan (P)</h3>\n<p>Tahap menaikkan indicator capaian atau mutu standar yang sebelumnya sudah berhasil dipenuhi. Tahap ini merupakan tahap akhir dalam satu siklus kegiatan PPEPP.</p>\n\n<div class=\'mt-4 p-4\' style=\'background:#f1f5f9;border-radius:8px;\'>\n<h4>Portal SISTA UNIKA</h4>\n<p>Sistem Informasi Standar Akademik untuk pemantauan dan pelaporan siklus PPEPP dapat diakses melalui link resmi:</p>\n<p><a href=\'https://sista.unika.ac.id\' target=\'_blank\' class=\'btn btn-primary\'>Kunjungi Portal SISTA (sista.unika.ac.id) &nearr;</a></p>\n</div>', '2026-09-03 03:44:02');

-- --------------------------------------------------------

--
-- Table structure for table `pengaturan`
--

CREATE TABLE `pengaturan` (
  `kunci` varchar(100) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nilai` mediumtext COLLATE utf8mb4_unicode_ci
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `pengaturan`
--

INSERT INTO `pengaturan` (`kunci`, `nilai`) VALUES
('alamat', 'Ruang Lembaga Penjaminan Mutu\nGedung Thomas Aquinas Lantai 5\nKampus Universitas Katolik Soegijapranata\nJalan Pawiyatan Luhur IV/1 Bendan Duwur Semarang 50234'),
('email', 'lpm@unika.ac.id'),
('footer_desc', 'Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata berkomitmen untuk mewujudkan mutu pendidikan tinggi yang unggul, berkelanjutan, dan berdaya saing global.'),
('jam_kerja', 'Senin – Jumat: 08.00 – 16.00 WIB'),
('maps_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.0927505866!2d110.41095357403498!3d-7.044023469178!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e708c6c8dbc7c2d%3A0x6e9c0e63f52dc05!2sSoegijapranata%20Catholic%20University!5e0!3m2!1sen!2sid!4v1700000000000!5m2!1sen!2sid'),
('profil_fungsi', 'Penyediaan data pelaksanaan sistem penjaminan mutu internal sebagai bahan penyusunan kebijakan Universitas.\nPemberian rekomendasi dan/atau usulan kepada Rektor dalam penyelenggaraan dan pengembangan SPMI.\nPengkajian dan pengembangan dokumen SPMI maupun dokumen mutu lainnya.\nPengkoordinasian, pelaksanaan dan monitoring SPMI.\nPengkoordinasian penyusunan prosedur sistem mutu, instruksi kerja maupun dokumen mutu lainnya.\nPelaksanaan dan monitoring Audit Mutu Internal (AMI).\nPengkoordinasian, pembimbingan teknis, monitoring dan evaluasi pelaksanaan proses akreditasi perguruan tinggi (APT) dan program studi (APS).\nPengawasan pelaksanaan Rapat Tinjauan Manajemen.'),
('profil_misi', 'Menyelenggarakan penyediaan data yang sahih dan valid di bidang penjaminan mutu melalui sistem monitoring dan evaluasi yang terstruktur, terencana dan berkelanjutan.\r\nMenyelenggarakan sistem penjaminan mutu yang efektif, partisipatif, dan dihidupi oleh seluruh sivitas akademika dan tenaga kependidikan.\r\nMembangun budaya mutu melalui sinergi, pendampingan, dan kolaborasi dengan gugus mutu serta para pemangku kepentingan.\r\nMemfasilitasi pengembangan kapasitas dan kompetensi sumber daya manusia di bidang penjaminan mutu.\r\nMendorong peningkatan mutu berkelanjutan untuk mendukung pencapaian visi dan reputasi Universitas.'),
('profil_tentang_judul', 'Lembaga Penjaminan Mutu SCU'),
('profil_tentang_teks1', 'Universitas Katolik Soegijapranata sudah mulai melaksanakan penjaminan mutu secara sistematis sejak tahun 2005 dengan dibentuknya Kantor Jaminan Mutu (KJM) berdasarkan Keputusan Rektor No: E.2/1467/Kep/IV/2005, yang kemudian diubah menjadi Lembaga Jaminan Mutu Pendidikan (LJMP) berdasarkan Keputusan Rektor No: E.2/2572/ Kep/VIII/2005.\r\n\r\nPada tahun 2013, sesuai dengan Statuta Universitas Katolik Soegijapranata, LJMP diubah namanya menjadi Lembaga Penjaminan Mutu (LPM). Awalnya, sistem manajemen mutu yang diterapkan di Unika Soegijapranata yaitu ISO (The International Organization for Standardization) 9001:2000 yang kemudian dikembangkan menjadi ISO 9001:2008 sampai dengan tahun akademik 2015/2016.\r\nMulai tahun akademik 2016/2017, Universitas Katolik Soegijapranata mengembangkan Sistem Penjaminan Mutu Internal (SPMI) sebagaimana diwajibkan Permenristekdikti No. 62 Tahun 2016 tentang Sistem Penjaminan Mutu Pendidikan Tinggi (SPM Dikti) berdasarkan Permendikbud No. 3 Tahun 2020 tentang Standar Nasional Pendidikan 10 buku pedoman universitas 2022 - 2023 Tinggi (SN Dikti).'),
('profil_tentang_teks2', 'Dalam perkembangannya, penjaminan mutu dilakukan untuk merekam dan memantau secara berkelanjutan tentang Penetapan, Pelaksanaan, Evaluasi, Pengendalian dan Peningkatan (PPEPP) Standar SPMI. Implementasi siklus SPM Universitas Katolik Soegijapranata didasarkan pada acuan Standar Nasional Pendidikan Tinggi (SN Dikti) dan standar Pendidikan Tinggi yang ditetapkan oleh Universitas Katolik Soegijapranata. Selanjutnya Unit Pengelola Program Studi (UPPS) menyusun dan menjalankan rencana strategis dan rencana operasional dengan mengacu pada standar akademik Universitas Katolik Soegijapranata. Untuk evaluasi pelaksanaan standar, LPM bekerjasama dengan Gugus Penjaminan Mutu (GPM) memonitor pelaksanaan standar dan dilakukan koordinasi untuk membahas temuan-temuan yang muncul di masing-masing fakultas/prodi.\r\n\r\nSebagai bagian dari siklus penjaminan mutu internal, Universitas Katolik Soegijapranata juga melakukan benchmarking untuk meningkatkan mutu implementasi SPMI. Komitmen Universitas Katolik Soegijapranata dalam menjalankan SPMI telah mengantarkan pada pencapaian hibah “Program Asuh Perguruan Tinggi Unggul” selama tiga tahun (2017, 2018 dan 2019) dan telah mendampingi/mengasuh lebih dari 60 program studi di luar Universitas Katolik Soegijapranata menuju program studi unggul. Pada bulan April tahun 2023, Universitas Katolik Soegijapranata berhasil mendapatkan Peringkat Akreditasi UNGGUL berdasarkan SK BAN PT No. 263/SK/BAN-PT/Ak.KP/PT/IV/2023 dan seluruh Program Studi telah terakreditasi.'),
('profil_tugas', 'Sesuai Peraturan Universitas Katolik Soegijapranata No. 01/E.2/Per-UKS/XI/2019 tentang Organisasi dan Tata Kelola Universitas Katolik Soegijapranata, LPM bertugas merencanakan, melaksanakan, mengevaluasi dan mengembangkan sistem penjaminan mutu.'),
('profil_tujuan', 'Terwujudnya sistem penjaminan mutu yang terstruktur, terencana, efektif dan berkelanjutan.\r\nTerwujudnya sistem monitoring, evaluasi, dan penyediaan data mutu yang sahih, valid dan terintegrasi sebagai dasar pengambilan keputusan.\r\nMeningkatnya kapasitas dan kompetensi sumber daya manusia di bidang penjaminan mutu.\r\nTerwujudnya sistem penjaminan mutu berbasis teknologi informasi yang efektif, efisien dan terintegrasi.\r\nTerwujudnya budaya mutu yang dihidupi oleh seluruh sivitas akademika dan tenaga kependidikan.\r\nMeningkatnya efektivitas peningkatan mutu berkelanjutan dalam mendukung pencapaian visi dan reputasi Universitas.'),
('profil_visi', 'Terwujudnya budaya mutu melalui sistem penjaminan mutu yang mendukung pencapaian visi Universitas Katolik Soegijapranata.'),
('sambutan_foto', ''),
('sambutan_instansi', 'Universitas Katolik Soegijapranata'),
('sambutan_jabatan', 'Kepala Lembaga Penjaminan Mutu'),
('sambutan_nama', 'Stefani Lily Indarto, SE., MM., Ak., CA., CPA.'),
('sambutan_teks', 'Puji syukur kepada Tuhan Yang Maha Esa atas berkat dan rahmat-Nya, sehingga Lembaga Penjaminan Mutu (LPM) Soegijapranata Catholic University dapat terus menjalankan peran strategisnya dalam menjamin dan meningkatkan mutu pendidikan tinggi.\n\nLPM SCU hadir sebagai motor penggerak budaya mutu di lingkungan Universitas, dengan misi utama merancang, memantau, dan mengevaluasi pelaksanaan Sistem Penjaminan Mutu Internal (SPMI) secara konsisten melalui siklus PPEPP – Penetapan, Pelaksanaan, Evaluasi, Pengendalian, dan Peningkatan Standar Mutu.\n\nKami mengundang seluruh civitas akademika dan pemangku kepentingan untuk bersama-sama membangun ekosistem pendidikan yang berkualitas, inovatif, dan berdampak bagi masyarakat.'),
('site_subtitle', 'Universitas Katolik Soegijapranata'),
('site_title', 'LPM UNIKA'),
('stat_akreditasi', 'A'),
('stat_ami', '88'),
('stat_ppepp', '92'),
('stat_prodi', '27'),
('stat_standar', '95'),
('stat_tahun', '40'),
('telepon', '024-8441555 Ext 1473'),
('website_url', 'https://www.unika.ac.id');

-- --------------------------------------------------------

--
-- Table structure for table `tim_lpm`
--

CREATE TABLE `tim_lpm` (
  `id` int NOT NULL,
  `nama` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `jabatan` varchar(150) COLLATE utf8mb4_unicode_ci NOT NULL,
  `bidang` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `deskripsi` text COLLATE utf8mb4_unicode_ci,
  `foto` varchar(255) COLLATE utf8mb4_unicode_ci DEFAULT '',
  `level` varchar(50) COLLATE utf8mb4_unicode_ci DEFAULT 'koordinator',
  `urutan` int DEFAULT '1',
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `tim_lpm`
--

INSERT INTO `tim_lpm` (`id`, `nama`, `jabatan`, `bidang`, `deskripsi`, `foto`, `level`, `urutan`, `created_at`) VALUES
(11, 'Stefani Lily Indarto, SE., MM., Ak., CA., CPA.', 'Kepala Lembaga Penjaminan Mutu', 'Pimpinan Lembaga', 'Memimpin keseluruhan perumusan, pelaksanaan, serta evaluasi kebijakan penjaminan mutu universitas, pengawalan Audit Mutu Internal (AMI), akreditasi, pemeringkatan secara berkelanjutan dan memastikan sinergi di tingkat universitas dengan fakultas/program studi/unit.', '', 'pimpinan', 1, '2026-09-03 03:17:17'),
(12, 'Vera Retnowati, ST., MM.', 'Sekretaris Lembaga Penjaminan Mutu', 'Manajemen & Tata Kelola', 'Mengkoordinasikan pengelolaan program kerja, keuangan, dan administrasi dokumen, serta memastikan tata kelola lembaga berjalan efektif, tertib, dan berkelanjutan.', '', 'sekretaris', 2, '2026-09-03 03:17:17'),
(13, 'Ir. I.M. Tri Hesti Mulyani, MT.', 'Ketua Pusat Pengembangan Sistem Penjaminan Mutu', 'Pengembangan SPMI', 'Mengembangkan dan memastikan pelaksanaan SPMI melalui pengelolaan dokumen mutu, implementasi dan pelaporan mutu di seluruh fakultas/program studi/unit.', '', 'koordinator', 3, '2026-09-03 03:17:17'),
(14, 'dr. Maya Yanuarty, M.Biomed', 'Ketua Pusat Audit Mutu Internal', 'Audit Mutu Internal (AMI)', 'Melaksanakan Audit Mutu Internal (AMI) secara terencana dan obyektif melalui koordinasi audit, pelaporan hasil evaluasi, serta rekomendasi perbaikan untuk memastikan efektifitas SPMI dan mendorong perbaikan berkelanjutan universitas.', '', 'koordinator', 4, '2026-09-03 03:17:17'),
(15, 'Ir. Lintang Jata Angghita, ST., M.Ling', 'Ketua Pusat Pemeringkatan', 'Pemeringkatan & Reputasi', 'Mengelola strategi peningkatan posisi universitas dalam berbagai skema pemeringkatan, serta memperkuat reputasi akademik dan kelembagaan.', '', 'koordinator', 5, '2026-09-03 03:17:17'),
(16, 'Hermawan, S.M.', 'Staf Tata Usaha Lembaga Penjaminan Mutu', 'Administrasi & Kesekretariatan', 'Mendukung operasional lembaga melalui layanan administrasi, dokumentasi, pelaporan kegiatan lapangan dan koordinasi kegiatan rutin harian.', '', 'staf', 6, '2026-09-03 03:17:17');

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int NOT NULL,
  `username` varchar(50) COLLATE utf8mb4_unicode_ci NOT NULL,
  `password` varchar(255) COLLATE utf8mb4_unicode_ci NOT NULL,
  `nama_lengkap` varchar(100) COLLATE utf8mb4_unicode_ci DEFAULT NULL,
  `created_at` timestamp NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

--
-- Dumping data for table `users`
--

INSERT INTO `users` (`id`, `username`, `password`, `nama_lengkap`, `created_at`) VALUES
(1, 'admin', '$2y$10$4IRytGichPc1sZqAW1AiHuf18pVc/c/qXvsXz40ZqTfhbe1KXiiCO', 'Administrator LPM', '2026-09-01 12:40:01');

--
-- Indexes for dumped tables
--

--
-- Indexes for table `akreditasi`
--
ALTER TABLE `akreditasi`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `berita`
--
ALTER TABLE `berita`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `buletin`
--
ALTER TABLE `buletin`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `dokumen`
--
ALTER TABLE `dokumen`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `feedback`
--
ALTER TABLE `feedback`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `hero_slides`
--
ALTER TABLE `hero_slides`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kalender_mutu`
--
ALTER TABLE `kalender_mutu`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `kategori_dokumen`
--
ALTER TABLE `kategori_dokumen`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `pages`
--
ALTER TABLE `pages`
  ADD PRIMARY KEY (`id`),
  ADD UNIQUE KEY `slug` (`slug`);

--
-- Indexes for table `pengaturan`
--
ALTER TABLE `pengaturan`
  ADD PRIMARY KEY (`kunci`);

--
-- Indexes for table `tim_lpm`
--
ALTER TABLE `tim_lpm`
  ADD PRIMARY KEY (`id`);

--
-- Indexes for table `users`
--
ALTER TABLE `users`
  ADD PRIMARY KEY (`id`);

--
-- AUTO_INCREMENT for dumped tables
--

--
-- AUTO_INCREMENT for table `akreditasi`
--
ALTER TABLE `akreditasi`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=4;

--
-- AUTO_INCREMENT for table `berita`
--
ALTER TABLE `berita`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `buletin`
--
ALTER TABLE `buletin`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `dokumen`
--
ALTER TABLE `dokumen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;

--
-- AUTO_INCREMENT for table `feedback`
--
ALTER TABLE `feedback`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=3;

--
-- AUTO_INCREMENT for table `hero_slides`
--
ALTER TABLE `hero_slides`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=5;

--
-- AUTO_INCREMENT for table `kalender_mutu`
--
ALTER TABLE `kalender_mutu`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=13;

--
-- AUTO_INCREMENT for table `kategori_dokumen`
--
ALTER TABLE `kategori_dokumen`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=21;

--
-- AUTO_INCREMENT for table `pages`
--
ALTER TABLE `pages`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=25;

--
-- AUTO_INCREMENT for table `tim_lpm`
--
ALTER TABLE `tim_lpm`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=17;

--
-- AUTO_INCREMENT for table `users`
--
ALTER TABLE `users`
  MODIFY `id` int NOT NULL AUTO_INCREMENT, AUTO_INCREMENT=2;
COMMIT;

/*!40101 SET CHARACTER_SET_CLIENT=@OLD_CHARACTER_SET_CLIENT */;
/*!40101 SET CHARACTER_SET_RESULTS=@OLD_CHARACTER_SET_RESULTS */;
/*!40101 SET COLLATION_CONNECTION=@OLD_COLLATION_CONNECTION */;
