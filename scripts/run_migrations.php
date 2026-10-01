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
// 5. Layanan Brosur & Pengaturan Pelatihan
$db->exec("
    CREATE TABLE IF NOT EXISTS `layanan_brosur` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `judul_brosur` VARCHAR(255) NOT NULL,
      `tahun` INT NOT NULL DEFAULT 2026,
      `deskripsi` TEXT NULL,
      `file_brosur` VARCHAR(255) NOT NULL,
      `tipe_file` VARCHAR(20) NOT NULL DEFAULT 'pdf',
      `ukuran_file` VARCHAR(50) NULL,
      `link_pendaftaran` VARCHAR(255) NULL,
      `urutan` INT NOT NULL DEFAULT 1,
      `is_active` TINYINT(1) NOT NULL DEFAULT 1,
      `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
      `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$brosurCount = $db->query("SELECT COUNT(*) FROM layanan_brosur")->fetchColumn();
if ($brosurCount == 0) {
    $db->exec("
        INSERT INTO `layanan_brosur` (`id`, `judul_brosur`, `tahun`, `deskripsi`, `file_brosur`, `tipe_file`, `ukuran_file`, `link_pendaftaran`, `urutan`, `is_active`) VALUES
        (1, 'Brosur Program Pelatihan & Kemitraan Mutu LPM SCU', 2026, 'Panduan silabus materi pelatihan penjaminan mutu, bimtek SPMI PPEPP, sertifikasi auditor mutu internal (AMI), dan klinik borang akreditasi prodi/institusi.', 'Brosur_Layanan_Pelatihan_LPM_UNIKA_2026.pdf', 'pdf', '458 KB', 'https://bit.ly/DaftarPelatihanLPM-SCU', 1, 1);
    ");
    echo "Seeded initial layanan_brosur record.\n";
}

// 6. Kolom Status pada Tabel Berita (Draft vs Published)
$beritaCols = $db->query("SHOW COLUMNS FROM berita LIKE 'status'")->fetchAll();
if (empty($beritaCols)) {
    $db->exec("ALTER TABLE berita ADD COLUMN status ENUM('draft', 'published') NOT NULL DEFAULT 'published' AFTER tampil_di_ami");
    echo "Added status column to berita table.\n";
}

// 7. Tabel FAQs
$db->exec("
    CREATE TABLE IF NOT EXISTS `faqs` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `pertanyaan` VARCHAR(255) NOT NULL,
        `jawaban` TEXT NOT NULL,
        `kategori` VARCHAR(100) DEFAULT 'Umum',
        `urutan` INT DEFAULT 1,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$faqCount = (int)$db->query("SELECT COUNT(*) FROM faqs")->fetchColumn();
if ($faqCount === 0) {
    $db->exec("
        INSERT INTO `faqs` (`pertanyaan`, `jawaban`, `kategori`, `urutan`, `is_active`) VALUES
        ('Apa itu Sistem Penjaminan Mutu Internal (SPMI)?', 'SPMI adalah kegiatan sistemik penjaminan mutu pendidikan tinggi oleh setiap perguruan tinggi secara otonom untuk mengendalikan dan meningkatkan penyelenggaraan pendidikan tinggi secara berencana dan berkelanjutan.', 'SPMI', 1, 1),
        ('Apa perbedaan SPMI dan SPME (Akreditasi)?', 'SPMI dijalankan secara internal oleh perguruan tinggi, sedangkan SPME (Sistem Penjaminan Mutu Eksternal) dilakukan oleh lembaga eksternal seperti BAN-PT atau LAM melalui proses akreditasi.', 'SPMI', 2, 1),
        ('Apa siklus utama dalam SPMI di UNIKA?', 'Siklus SPMI berpedoman pada PPEPP: Penetapan Standar, Pelaksanaan Standar, Evaluasi Pelaksanaan Standar, Pengendalian Pelaksanaan Standar, dan Peningkatan Standar Pendidikan Tinggi.', 'SPMI', 3, 1),
        ('Kapan Audit Mutu Internal (AMI) dilaksanakan?', 'AMI dilaksanakan secara berkala setiap tahun akademik untuk memastikan ketercapaian dan kepatuhan standar mutu pada setiap program studi dan unit kerja.', 'AMI', 4, 1),
        ('Bagaimana cara mengajukan permohonan kunjungan studi banding ke LPM UNIKA?', 'Permohonan dapat diajukan secara resmi melalui menu Layanan > Kunjungan & Studi Banding dengan mengisi formulir pengajuan dan melampirkan surat permohonan resmi institusi.', 'Layanan', 5, 1);
    ");
    echo "Seeded initial faqs table.\n";
}

// 8. Tabel Glosarium
$db->exec("
    CREATE TABLE IF NOT EXISTS `glosarium` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `istilah` VARCHAR(100) NOT NULL,
        `istilah_lengkap` VARCHAR(255) DEFAULT NULL,
        `definisi` TEXT NOT NULL,
        `sumber` VARCHAR(150) DEFAULT 'Kemendikbudristek / SPMI',
        `urutan` INT DEFAULT 1,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$gloCount = (int)$db->query("SELECT COUNT(*) FROM glosarium")->fetchColumn();
if ($gloCount === 0) {
    $db->exec("
        INSERT INTO `glosarium` (`istilah`, `istilah_lengkap`, `definisi`, `sumber`, `urutan`, `is_active`) VALUES
        ('AMI', 'Audit Mutu Internal', 'Proses pengujian yang sistematik, mandiri, dan terdokumentasi untuk memastikan pelaksanaan kegiatan di perguruan tinggi sesuai prosedur dan standar.', 'Pedoman SPMI', 1, 1),
        ('PPEPP', 'Penetapan, Pelaksanaan, Evaluasi, Pengendalian, Peningkatan', 'Siklus penjaminan mutu pendidikan tinggi yang mencakup lima tahapan pokok secara berkelanjutan.', 'Permendikbudristek', 2, 1),
        ('SPMI', 'Sistem Penjaminan Mutu Internal', 'Kegiatan sistemik penjaminan mutu pendidikan tinggi oleh perguruan tinggi secara otonom.', 'UU No. 12 Tahun 2012', 3, 1),
        ('RTM', 'Rapat Tinjauan Manajemen', 'Pertemuan pimpinan untuk mengevaluasi efektivitas penerapan sistem mutu dan menindaklanjuti temuan audit.', 'Pedoman AMI', 4, 1),
        ('BAN-PT', 'Badan Akreditasi Nasional Perguruan Tinggi', 'Badan akreditasi yang bertugas melakukan akreditasi perguruan tinggi.', 'Permendikbud', 5, 1),
        ('LAM', 'Lembaga Akreditasi Mandiri', 'Lembaga yang dibentuk masyarakat atau profesi untuk melakukan akreditasi program studi.', 'Permendikbud', 6, 1);
    ");
    echo "Seeded initial glosarium table.\n";
}

// 9. Pengaturan Tautan Portal SPMI & UI GreenMetric
$portalSettings = [
    'portal_sista_status'     => 'active',
    'portal_sista_url'        => 'https://sista.unika.ac.id',
    'portal_sista_cs_title'   => 'Tautan Sistem SISTA Belum Dibuka',
    'portal_sista_cs_desc'    => 'Pemantauan dan pelaporan siklus PPEPP diaktifkan sesuai jadwal.',
    'portal_spmi_status'      => 'active',
    'portal_spmi_url'         => 'https://spmi.kemdiktisaintek.go.id/auth/login',
    'portal_spmi_cs_title'    => 'Tautan Sistem SPMI Kemendikti Belum Dibuka',
    'portal_spmi_cs_desc'     => 'Pelaporan evaluasi pelaksanaan penjaminan mutu akan diaktifkan sesuai jadwal.',
    'portal_eppepp_status'    => 'coming_soon',
    'portal_eppepp_url'       => 'https://e-ppepp.unika.ac.id',
    'portal_eppepp_cs_title'  => 'Tautan Sistem e-PPEPP Belum Dibuka',
    'portal_eppepp_cs_desc'   => 'Pelaksanaan visitasi dan rekapitulasi audit e-PPEPP akan diaktifkan sesuai jadwal.',
    'greenmetric_rank'        => '#84',
    'greenmetric_scope'       => 'Peringkat Nasional (Indonesia)',
    'greenmetric_badge'       => 'UI GREENMETRIC 2024',
    'greenmetric_title'       => 'World University Rankings Network',
    'greenmetric_desc'        => 'Komitmen Universitas Katolik Soegijapranata dalam pembangunan kampus berkelanjutan, pengelolaan energi hijau ramah lingkungan, dan pelestarian alam terukur secara global.',
];

foreach ($portalSettings as $k => $v) {
    if (getPengaturan($k, '') === '') {
        setPengaturan($k, $v);
    }
}
echo "Synced portal & greenmetric settings.\n";

echo "All migrations finished successfully!\n";
