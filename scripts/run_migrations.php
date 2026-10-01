<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

echo "Running migrations...\n";

// 1. Users table
$uCols = $db->query("SHOW COLUMNS FROM users LIKE 'email'")->fetchAll();
if (empty($uCols)) {
    $db->exec("ALTER TABLE users ADD COLUMN email VARCHAR(150) DEFAULT NULL AFTER username");
    echo "Added email column to users.\n";
}
$adminHash = password_hash('admin123', PASSWORD_DEFAULT);
$db->prepare("UPDATE users SET email = 'tu.lpm@unika.ac.id', password = ? WHERE username = 'admin'")->execute([$adminHash]);
echo "Updated admin user.\n";

// 2. AMI Tables
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

@mkdir(__DIR__ . '/../uploads/ami/siklus1', 0755, true);
@mkdir(__DIR__ . '/../uploads/ami/siklus4', 0755, true);
@mkdir(__DIR__ . '/../uploads/ami/siklus5', 0755, true);
echo "AMI tables and directories created.\n";

// 3. SPMI KEMENDIKTI
$kemenDir = __DIR__ . '/../uploads/spmi_kemendikti';
if (!is_dir($kemenDir)) {
    @mkdir($kemenDir, 0755, true);
}
$db->exec("
    CREATE TABLE IF NOT EXISTS `spmi_kemendikti` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `judul` VARCHAR(255) NOT NULL,
      `deskripsi` TEXT NULL,
      `file_pdf` VARCHAR(255) NOT NULL,
      `tahun` INT NOT NULL DEFAULT 2025,
      `urutan` INT NOT NULL DEFAULT 1,
      `is_published` TINYINT(1) NOT NULL DEFAULT 1,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$kemenCount = $db->query("SELECT COUNT(*) FROM spmi_kemendikti")->fetchColumn();
if ($kemenCount == 0) {
    $srcPdf = __DIR__ . '/../uploads/dokumen/2606.pdf';
    $targetPdf1 = 'laporan_spmi_kemendiktisaintek_2025.pdf';
    $targetPdf2 = 'hasil_pelaporan_spmi_nasional_2024.pdf';
    if (file_exists($srcPdf)) {
        @copy($srcPdf, $kemenDir . '/' . $targetPdf1);
        @copy($srcPdf, $kemenDir . '/' . $targetPdf2);
    }
    $db->exec("
        INSERT INTO `spmi_kemendikti` (`id`, `judul`, `deskripsi`, `file_pdf`, `tahun`, `urutan`, `is_published`) VALUES
        (1, 'Laporan Pelaksanaan SPMI Terintegrasi Kemendikti Saintek', 'Rekapitulasi pelaporan data pelaksanaan dan evaluasi SPMI SCU pada Sistem SPMI Kementerian Pendidikan Tinggi, Sains, dan Teknologi Republik Indonesia.', '$targetPdf1', '2025', 1, 1),
        (2, 'Hasil Evaluasi dan Pengukuran Mutu Nasional SPMI', 'Dokumen umpan balik dan pengakuan capaian implementasi standar mutu SPMI Perguruan Tinggi dari Kemendiktisaintek.', '$targetPdf2', '2024', 2, 1);
    ");
    echo "Seeded spmi_kemendikti records.\n";
}

// 4. Lembaga Akreditasi Logos & Links
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
echo "Updated lembaga_akreditasi logos and links.\n";

echo "All migrations finished successfully!\n";
