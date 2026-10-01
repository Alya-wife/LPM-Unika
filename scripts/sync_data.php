<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

echo "Starting database sync and schema update...\n";

// 1. Update USERS table
echo "1. Checking users table...\n";
$userCols = $db->query("SHOW COLUMNS FROM users LIKE 'email'")->fetchAll();
if (empty($userCols)) {
    $db->exec("ALTER TABLE users ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER username");
    echo "Added column 'email' to users table.\n";
}
$db->exec("UPDATE users SET email = 'tu.lpm@unika.ac.id' WHERE username = 'admin' AND (email IS NULL OR email = '')");
// Ensure password for admin is admin123
$adminHash = password_hash('admin123', PASSWORD_DEFAULT);
$db->prepare("UPDATE users SET password = ? WHERE username = 'admin'")->execute([$adminHash]);
echo "User admin updated with email tu.lpm@unika.ac.id and verified password admin123.\n";

// 2. SPMI_KEMENDIKTI table & directory
echo "2. Checking spmi_kemendikti...\n";
$db->exec("
    CREATE TABLE IF NOT EXISTS `spmi_kemendikti` (
      `id` int NOT NULL AUTO_INCREMENT,
      `judul` varchar(255) NOT NULL,
      `deskripsi` text,
      `file_pdf` varchar(255) NOT NULL,
      `tahun` varchar(20) DEFAULT '2025',
      `urutan` int DEFAULT '1',
      `is_published` tinyint(1) DEFAULT '1',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
$kemenDir = __DIR__ . '/../uploads/spmi_kemendikti';
if (!is_dir($kemenDir)) {
    @mkdir($kemenDir, 0755, true);
}
// Seed default official document if empty
$kemenCount = $db->query("SELECT COUNT(*) FROM spmi_kemendikti")->fetchColumn();
if ($kemenCount == 0) {
    // Copy sample pdf if available
    $srcPdf = __DIR__ . '/../uploads/dokumen/2606.pdf';
    $targetPdf1 = 'laporan_spmi_kemendiktisaintek_2025.pdf';
    $targetPdf2 = 'hasil_pelaporan_spmi_nasional_2024.pdf';
    if (file_exists($srcPdf)) {
        @copy($srcPdf, $kemenDir . '/' . $targetPdf1);
        @copy($srcPdf, $kemenDir . '/' . $targetPdf2);
    }
    $db->exec("
        INSERT INTO `spmi_kemendikti` (`id`, `judul`, `deskripsi`, `file_pdf`, `tahun`, `urutan`, `is_published`) VALUES
        (1, 'Laporan Pelaksanaan SPMI Terintegrasi Kemendikti Saintek', 'Rekapitulasi pelaporan data SPMI SCU pada Sistem SPMI Kementerian Pendidikan Tinggi, Sains, dan Teknologi Republik Indonesia.', '$targetPdf1', '2025', 1, 1),
        (2, 'Hasil Evaluasi dan Pengukuran Mutu Nasional SPMI', 'Dokumen umpan balik dan pengakuan capaian implementasi SPMI Perguruan Tinggi dari Kemendiktisaintek.', '$targetPdf2', '2024', 2, 1);
    ");
    echo "Seeded default spmi_kemendikti rows.\n";
}

// 3. AMI TABLES
echo "3. Creating AMI tables...\n";
$db->exec("
    CREATE TABLE IF NOT EXISTS `ami_periode` (
      `id` int NOT NULL AUTO_INCREMENT,
      `nama_periode` varchar(50) NOT NULL,
      `urutan` int DEFAULT '1',
      `is_active` tinyint(1) DEFAULT '1',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$pCount = $db->query("SELECT COUNT(*) FROM ami_periode")->fetchColumn();
if ($pCount == 0) {
    $db->exec("
        INSERT INTO `ami_periode` (`id`, `nama_periode`, `urutan`, `is_active`) VALUES
        (1, '2025/2026', 1, 1),
        (2, '2026/2027', 2, 1);
    ");
    echo "Seeded ami_periode.\n";
}

$db->exec("
    CREATE TABLE IF NOT EXISTS `ami_siklus1_kegiatan` (
      `id` int NOT NULL AUTO_INCREMENT,
      `periode` varchar(50) NOT NULL,
      `judul` varchar(255) NOT NULL,
      `file_dokumen` varchar(255) NOT NULL,
      `file_size` varchar(50) DEFAULT '',
      `keterangan` text,
      `urutan` int DEFAULT '1',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS `ami_siklus1_opening` (
      `id` int NOT NULL AUTO_INCREMENT,
      `periode` varchar(50) NOT NULL,
      `judul` varchar(255) NOT NULL,
      `tanggal_kegiatan` date DEFAULT NULL,
      `foto` varchar(255) DEFAULT '',
      `keterangan` text,
      `urutan` int DEFAULT '1',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS `ami_siklus4_dokumen` (
      `id` int NOT NULL AUTO_INCREMENT,
      `periode` varchar(50) NOT NULL,
      `jenis` varchar(50) NOT NULL,
      `judul` varchar(255) NOT NULL,
      `file_dokumen` varchar(255) NOT NULL,
      `file_size` varchar(50) DEFAULT '',
      `keterangan` text,
      `urutan` int DEFAULT '1',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS `ami_siklus4_dokumentasi` (
      `id` int NOT NULL AUTO_INCREMENT,
      `periode` varchar(50) NOT NULL,
      `tingkat` varchar(50) DEFAULT 'prodi',
      `fakultas` varchar(150) DEFAULT '',
      `prodi` varchar(150) DEFAULT '',
      `judul` varchar(255) NOT NULL,
      `narasi_berita_acara` text,
      `file_berita_acara` varchar(255) DEFAULT '',
      `file_daftar_hadir` varchar(255) DEFAULT '',
      `foto_kegiatan` longtext,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$db->exec("
    CREATE TABLE IF NOT EXISTS `ami_siklus5_rtm` (
      `id` int NOT NULL AUTO_INCREMENT,
      `periode` varchar(50) NOT NULL,
      `tingkat` varchar(50) DEFAULT 'universitas',
      `fakultas` varchar(150) DEFAULT '',
      `prodi` varchar(150) DEFAULT '',
      `judul` varchar(255) NOT NULL,
      `notulensi` text,
      `file_notulensi` varchar(255) DEFAULT '',
      `file_daftar_hadir` varchar(255) DEFAULT '',
      `foto_kegiatan` longtext,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      PRIMARY KEY (`id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

// Create uploads subdirectories
@mkdir(__DIR__ . '/../uploads/ami/siklus1', 0755, true);
@mkdir(__DIR__ . '/../uploads/ami/siklus4', 0755, true);
@mkdir(__DIR__ . '/../uploads/ami/siklus5', 0755, true);
echo "AMI tables and upload folders verified.\n";

// 4. LEMBAGA AKREDITASI: Logos & Links
echo "4. Updating lembaga_akreditasi...\n";
$lemCols = $db->query("SHOW COLUMNS FROM lembaga_akreditasi LIKE 'link_website'")->fetchAll();
if (empty($lemCols)) {
    $db->exec("ALTER TABLE lembaga_akreditasi ADD COLUMN link_website VARCHAR(255) DEFAULT '' AFTER logo");
    echo "Added link_website column to lembaga_akreditasi.\n";
}

$lamData = [
    1 => ['kode' => 'BAN-PT',      'logo' => 'logo_banpt_1789003596.webp',      'link' => 'https://www.banpt.or.id/',  'warna' => '#0D47A1'],
    2 => ['kode' => 'LAMEMBA',     'logo' => 'logo_lamemba_1789004015.webp',    'link' => 'https://lamemba.or.id/',   'warna' => '#1B5E20'],
    3 => ['kode' => 'LAM INFOKOM', 'logo' => 'logo_laminfokom_1789004030.webp', 'link' => 'https://laminfokom.or.id/','warna' => '#B71C1C'],
    4 => ['kode' => 'LAM TEKNIK',  'logo' => 'logo_lamteknik_1789004163.webp',  'link' => 'https://lamteknik.or.id/', 'warna' => '#E65100'],
    5 => ['kode' => 'LAM-PTKes',   'logo' => 'logo_lamptkes_1789004441.webp',   'link' => 'https://lamptkes.org/',    'warna' => '#004D40'],
    6 => ['kode' => 'LAMSPAK',     'logo' => 'logo_lamspak_1789004216.webp',    'link' => 'https://lamspak.or.id/',    'warna' => '#4A148C'],
    7 => ['kode' => 'LAMDEPILAR',  'logo' => 'logo_lamdepilar_1789004235.webp', 'link' => 'https://lamdepilar.or.id/','warna' => '#311B92'],
    8 => ['kode' => 'LAMPTIP',     'logo' => 'logo_lamptip_1789004285.png',     'link' => 'https://lamptip.or.id/',   'warna' => '#006064'],
];

$stmtLam = $db->prepare("UPDATE lembaga_akreditasi SET logo = ?, link_website = ?, warna = ? WHERE id = ?");
foreach ($lamData as $id => $d) {
    $stmtLam->execute([$d['logo'], $d['link'], $d['warna'], $id]);
}
echo "Lembaga akreditasi logos and links updated.\n";

// 5. RESTORE FULL BULETIN DATA FROM lpm_scu.sql
echo "5. Restoring full buletin data...\n";
$bCols = $db->query("SHOW COLUMNS FROM buletin LIKE 'is_aktif'")->fetchAll();
if (empty($bCols)) {
    $db->exec("ALTER TABLE buletin ADD COLUMN is_aktif TINYINT(1) DEFAULT 1 AFTER tanggal_terbit");
}

$buletinSql = "
REPLACE INTO `buletin` (`id`, `judul`, `edisi`, `periode_akademik`, `deskripsi`, `file_path`, `cover_path`, `tanggal_terbit`, `is_aktif`, `created_at`) VALUES
(1, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 14 / April - Agustus 2026', '2025/2026', 'Buletin mutu edisi ke-14 fokus pada peningkatan standar SPMI dan evaluasi implementasi PPEPP.', 'bul_6a98f94116c65.pdf', 'cov_6a98f9411951c.jpg', '2026-09-03', 1, '2026-09-03 11:36:17'),
(2, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 13 / Januari - Maret 2026', '2025/2026', 'Edisi ke-13 mengulas capaian mutu universitas serta persiapan siklus Audit Mutu Internal tahun 2026.', 'bul_6a9fc4852fa26.pdf', 'cov_bul_6aa50cae20b4f.webp', '2025-01-01', 1, '2026-09-08 15:17:09'),
(3, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 01 / Januari 2023', '2022/2023', 'Edisi perdana Buletin Penjaminan Mutu UNIKA Soegijapranata.', 'bul_6aa50cadb5664.pdf', 'cov_bul_6aa50cadb6f36.webp', '2023-01-15', 1, '2026-09-12 08:33:49'),
(4, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 02 / April - Juni 2023', '2022/2023', 'Pelaksanaan Audit Mutu Internal dan Tindak Lanjut RTM Fakultas.', 'bul_6aa50cae20b4f.pdf', 'cov_bul_6aa50cae222dc.webp', '2023-04-10', 1, '2026-09-12 08:33:50'),
(5, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 03 / Juli - September 2023', '2022/2023', 'Strategi Akreditasi Program Studi Menuju Peringkat Unggul BAN-PT.', 'bul_6aa50cae877d6.pdf', 'cov_bul_6aa50cae88d67.webp', '2023-07-20', 1, '2026-09-12 08:33:50'),
(6, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 04 / Oktober - Desember 2023', '2023/2024', 'Penguatan Budaya Mutu dalam Implementasi Kurikulum Kampus Merdeka.', 'bul_6aa50caf18bcf.pdf', 'cov_bul_6aa50caf1a629.webp', '2023-10-18', 1, '2026-09-12 08:33:51'),
(7, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 05 / Januari - Maret 2024', '2023/2024', 'Evaluasi Ketercapaian IKU dan Standar Mutu Pendidikan Tinggi UNIKA.', 'bul_6aa50caf9669a.pdf', 'cov_bul_6aa50caf97e41.webp', '2024-01-22', 1, '2026-09-12 08:33:51'),
(8, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 06 / April - Juni 2024', '2023/2024', 'Hasil Audit Mutu Internal Siklus Ganjil dan Rekomendasi Perbaikan.', 'bul_6aa50cb005c41.pdf', 'cov_bul_6aa50cb00713b.webp', '2024-04-15', 1, '2026-09-12 08:33:52'),
(9, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 07 / Juli - September 2024', '2023/2024', 'Peran Gugus Penjaminan Mutu Fakultas dalam Menjaga Kualitas Pembelajaran.', 'bul_6aa50cb0630a7.pdf', 'cov_bul_6aa50cb064506.webp', '2024-07-10', 1, '2026-09-12 08:33:52'),
(10, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 08 / Oktober - Desember 2024', '2024/2025', 'Penyusunan Instrumen Akreditasi Baru dan Sosialisasi Regulasi LAM.', 'bul_6aa50cb0c28f2.pdf', 'cov_bul_6aa50cb0c3ca1.webp', '2024-10-25', 1, '2026-09-12 08:33:52'),
(11, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 09 / Januari - Maret 2025', '2024/2025', 'Audit Berbasis Risiko dan Penerapan Siklus PPEPP yang Efektif.', 'bul_6aa50cb12aaf1.pdf', 'cov_bul_6aa50cb12bf63.webp', '2025-01-20', 1, '2026-09-12 08:33:53'),
(12, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 10 / April - Juni 2025', '2024/2025', 'Tinjauan Manajemen Universitas dan Kebijakan Peningkatan Mutu Berkelanjutan.', 'bul_6aa50cb18b2c9.pdf', 'cov_bul_6aa50cb18c641.webp', '2025-04-18', 1, '2026-09-12 08:33:53'),
(13, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 11 / Juli - September 2025', '2024/2025', 'Best Practice Penjaminan Mutu Internal Program Studi Berpredikat Unggul.', 'bul_6aa50cb1d8a4c.pdf', 'cov_bul_6aa50cb1d9dd8.webp', '2025-07-12', 1, '2026-09-12 08:33:53'),
(14, 'Buletin Penjaminan Mutu Universitas Katolik Soegijapranata', 'Edisi 12 / Oktober - Desember 2025', '2025/2026', 'Refleksi Akhir Tahun Mutu Akademik dan Tata Kelola SCU Menuju Standar Global.', 'bul_6aa50cb23dfab.pdf', 'cov_bul_6aa50cb23f668.webp', '2025-10-28', 1, '2026-09-12 08:33:54');
";
$db->exec($buletinSql);
$bCountAfter = $db->query("SELECT COUNT(*) FROM buletin")->fetchColumn();
echo "Buletin table now has $bCountAfter records (all 14 editions restored).\n";

// 6. RESTORE FULL BERITA & KEGIATAN DATA FROM lpm_scu.sql
echo "6. Restoring full berita and kegiatan data from lpm_scu.sql...\n";
$colAmi = $db->query("SHOW COLUMNS FROM berita LIKE 'tampil_di_ami'")->fetchAll();
if (empty($colAmi)) {
    $db->exec("ALTER TABLE berita ADD COLUMN tampil_di_ami TINYINT(1) NOT NULL DEFAULT 0 AFTER tanggal_publikasi");
    echo "Added column tampil_di_ami to berita.\n";
}

// Extract INSERT INTO `berita` from lpm_scu.sql
$sqlContent = file_get_contents(__DIR__ . '/../lpm_scu.sql');
if (preg_match('/INSERT INTO `berita`\s*\([^)]+\)\s*VALUES\s*([\s\S]*?);/i', $sqlContent, $matches)) {
    $insertBerita = "REPLACE INTO `berita` (`id`, `judul`, `tipe`, `slug`, `konten`, `gambar`, `tanggal_publikasi`, `tampil_di_ami`, `created_at`) VALUES " . $matches[1] . ";";
    $db->exec($insertBerita);
    $beritaCountAfter = $db->query("SELECT COUNT(*) FROM berita")->fetchColumn();
    echo "Berita table now has $beritaCountAfter records restored.\n";
} else {
    echo "Could not find INSERT INTO berita in SQL dump.\n";
}

// 7. RESTORE BERITA_GAMBAR
if (preg_match('/INSERT INTO `berita_gambar`\s*\([^)]+\)\s*VALUES\s*([\s\S]*?);/i', $sqlContent, $matches)) {
    $insertBg = "REPLACE INTO `berita_gambar` (`id`, `berita_id`, `gambar`, `urutan`, `created_at`) VALUES " . $matches[1] . ";";
    $db->exec($insertBg);
    $bgCount = $db->query("SELECT COUNT(*) FROM berita_gambar")->fetchColumn();
    echo "Berita_gambar table now has $bgCount records restored.\n";
}

// 8. RESTORE PAGES 27 TO 38
echo "8. Checking and restoring pages table...\n";
$pCols = $db->query("SHOW COLUMNS FROM pages")->fetchAll(PDO::FETCH_COLUMN);
$missingCols = [
    'custom_url'     => "VARCHAR(255) DEFAULT NULL AFTER `slug`",
    'status'         => "VARCHAR(20) DEFAULT 'publish' AFTER `ringkasan`",
    'show_in_nav'    => "TINYINT(1) DEFAULT 0 AFTER `status`",
    'nav_position'   => "VARCHAR(50) DEFAULT 'main' AFTER `show_in_nav`",
    'nav_label'      => "VARCHAR(100) DEFAULT NULL AFTER `nav_position`",
    'layout'         => "VARCHAR(50) DEFAULT 'default' AFTER `nav_label`",
    'featured_image' => "VARCHAR(255) DEFAULT NULL AFTER `layout`",
    'urutan'         => "INT DEFAULT 0 AFTER `featured_image`",
    'blocks_json'    => "LONGTEXT DEFAULT NULL AFTER `konten`",
];
foreach ($missingCols as $col => $def) {
    if (!in_array($col, $pCols)) {
        $db->exec("ALTER TABLE `pages` ADD COLUMN `$col` $def");
        echo "Added column $col to pages.\n";
    }
}

if (preg_match('/INSERT INTO `pages`\s*\([^)]+\)\s*VALUES\s*([\s\S]*?);/i', $sqlContent, $matches)) {
    $insertPages = "REPLACE INTO `pages` (`id`, `judul`, `slug`, `custom_url`, `kategori`, `ringkasan`, `status`, `show_in_nav`, `nav_position`, `nav_label`, `layout`, `featured_image`, `urutan`, `konten`, `blocks_json`, `updated_at`) VALUES " . $matches[1] . ";";
    $db->exec($insertPages);
    $pagesCount = $db->query("SELECT COUNT(*) FROM pages")->fetchColumn();
    echo "Pages table now has $pagesCount records.\n";
}

echo "Database sync and data restoration completed successfully!\n";
