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

$s4Undangan = $db->query("SHOW COLUMNS FROM ami_siklus4_dokumentasi LIKE 'file_undangan'")->fetchAll();
if (empty($s4Undangan)) {
    $db->exec("ALTER TABLE ami_siklus4_dokumentasi ADD COLUMN file_undangan VARCHAR(255) DEFAULT '' AFTER file_daftar_hadir");
    echo "Added file_undangan column to ami_siklus4_dokumentasi.\n";
}

$s5Undangan = $db->query("SHOW COLUMNS FROM ami_siklus5_rtm LIKE 'file_undangan'")->fetchAll();
if (empty($s5Undangan)) {
    $db->exec("ALTER TABLE ami_siklus5_rtm ADD COLUMN file_undangan VARCHAR(255) DEFAULT '' AFTER file_daftar_hadir");
    echo "Added file_undangan column to ami_siklus5_rtm.\n";
}

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
        `nama` VARCHAR(255) DEFAULT NULL,
        `kategori` VARCHAR(100) NOT NULL DEFAULT 'Umum',
        `istilah_lengkap` VARCHAR(255) DEFAULT NULL,
        `definisi` TEXT NOT NULL,
        `sumber` VARCHAR(150) DEFAULT 'Kemendikbudristek / SPMI',
        `urutan` INT DEFAULT 1,
        `is_active` TINYINT(1) DEFAULT 1,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
// Pastikan kolom nama dan kategori ada jika tabel lama sudah terbuat
$chkGlosNama = $db->query("SHOW COLUMNS FROM glosarium LIKE 'nama'")->fetchAll();
if (empty($chkGlosNama)) {
    $db->exec("ALTER TABLE glosarium ADD COLUMN `nama` VARCHAR(255) NULL DEFAULT NULL AFTER `istilah`");
    $db->exec("UPDATE glosarium SET nama = istilah_lengkap WHERE nama IS NULL");
}
$chkGlosKat = $db->query("SHOW COLUMNS FROM glosarium LIKE 'kategori'")->fetchAll();
if (empty($chkGlosKat)) {
    $db->exec("ALTER TABLE glosarium ADD COLUMN `kategori` VARCHAR(100) NOT NULL DEFAULT 'Umum' AFTER `nama`");
    $db->exec("UPDATE glosarium SET kategori = sumber WHERE sumber IS NOT NULL AND sumber != ''");
}
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

// 10. Table kategori_berita
$db->exec("
    CREATE TABLE IF NOT EXISTS `kategori_berita` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nama_kategori` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$kbCount = (int)$db->query("SELECT COUNT(*) FROM kategori_berita")->fetchColumn();
if ($kbCount === 0) {
    $default_news_cats = ['Berita', 'Kegiatan LPM', 'Artikel Mutu', 'Sosialisasi', 'Penghargaan'];
    $ins_kb = $db->prepare("INSERT IGNORE INTO kategori_berita (nama_kategori, slug) VALUES (?, ?)");
    foreach ($default_news_cats as $nc) {
        $ins_kb->execute([$nc, makeSlug($nc)]);
    }
    echo "Seeded initial kategori_berita table.\n";
}

// 11. Table kategori_faq
$db->exec("
    CREATE TABLE IF NOT EXISTS `kategori_faq` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nama_kategori` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `keterangan` TEXT NULL,
        `urutan` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$kfaqCount = (int)$db->query("SELECT COUNT(*) FROM kategori_faq")->fetchColumn();
if ($kfaqCount === 0) {
    $default_faq_cats = [
        ['SPMI', 'spmi', 'Pertanyaan seputar Sistem Penjaminan Mutu Internal dan siklus PPEPP', 1],
        ['AMI', 'ami', 'Pertanyaan seputar pelaksanaan dan instrumen Audit Mutu Internal', 2],
        ['Layanan', 'layanan', 'Pertanyaan seputar permohonan kunjungan, audiensi, dan kritik saran', 3],
        ['Akreditasi', 'akreditasi', 'Pertanyaan seputar instrumen dan persiapan akreditasi LAM & BAN-PT', 4],
        ['Umum', 'umum', 'Pertanyaan umum lainnya terkait mutu pendidikan tinggi', 5],
    ];
    $ins_faq = $db->prepare("INSERT IGNORE INTO kategori_faq (nama_kategori, slug, keterangan, urutan) VALUES (?, ?, ?, ?)");
    foreach ($default_faq_cats as $fc) {
        $ins_faq->execute($fc);
    }
    echo "Seeded initial kategori_faq table.\n";
}

// 12. Table kategori_page
$db->exec("
    CREATE TABLE IF NOT EXISTS `kategori_page` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `nama_kategori` VARCHAR(100) NOT NULL,
        `slug` VARCHAR(100) NOT NULL UNIQUE,
        `keterangan` TEXT NULL,
        `urutan` INT DEFAULT 0,
        `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$kpCount = (int)$db->query("SELECT COUNT(*) FROM kategori_page")->fetchColumn();
if ($kpCount === 0) {
    $default_page_cats = [
        ['Profil', 'profil', 'Halaman informasi profil, sejarah, dan struktur organisasi', 1],
        ['SPMI', 'spmi', 'Halaman sistem penjaminan mutu internal', 2],
        ['AMI', 'ami', 'Halaman proses dan pelaporan audit mutu internal', 3],
        ['Akreditasi', 'akreditasi', 'Halaman akreditasi nasional dan internasional', 4],
        ['Mutu & Data', 'mutu-data', 'Halaman capaian data mutu dan statistik universitas', 5],
        ['Dokumen', 'dokumen', 'Halaman repository regulasi dan formulir', 6],
        ['Knowledge Center', 'knowledge-center', 'Halaman pusat pengetahuan dan edukasi mutu', 7],
        ['Layanan', 'layanan', 'Halaman layanan LPM kepada unit, dosen, mahasiswa dan mitra', 8],
        ['Kontak', 'kontak', 'Halaman saluran komunikasi dan alamat LPM', 9],
        ['Umum', 'umum', 'Halaman umum lainnya', 10],
    ];
    $ins_kp = $db->prepare("INSERT IGNORE INTO kategori_page (nama_kategori, slug, keterangan, urutan) VALUES (?, ?, ?, ?)");
    foreach ($default_page_cats as $pc) {
        $ins_kp->execute($pc);
    }
    echo "Seeded initial kategori_page table.\n";
}

// 13. Hero Banner Settings (Teks Tetap)
$heroSettings = [
    'hero_welcome_text'  => 'Selamat datang di',
    'hero_title'         => 'Lembaga Penjaminan Mutu Unika',
    'hero_tagline'       => 'GROW WITH QUALITY, SERVE WITH HEART',
    'hero_desc'          => 'Mewujudkan tata kelola penjaminan mutu pendidikan tinggi yang unggul, terencana, dan berkelanjutan.',
    'hero_btn_text'      => 'Akses Dokumen SPMI',
    'hero_btn_link'      => 'spmi.php',
    'hero_btn2_text'     => 'Profil Lembaga',
    'hero_btn2_link'     => 'profil.php',
];
foreach ($heroSettings as $k => $v) {
    if (getPengaturan($k, '') === '') {
        setPengaturan($k, $v);
    }
}
// 14. Pemeringkatan Table (Lokal, Nasional, Internasional)
$db->exec("
    CREATE TABLE IF NOT EXISTS `pemeringkatan` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `judul` VARCHAR(255) NOT NULL,
      `lembaga` VARCHAR(150) NOT NULL,
      `kategori` ENUM('lokal', 'nasional', 'internasional') NOT NULL DEFAULT 'nasional',
      `peringkat` VARCHAR(100) NULL,
      `peringkat_dari` VARCHAR(100) NULL,
      `badge_teks` VARCHAR(100) NULL,
      `warna` VARCHAR(30) NULL DEFAULT NULL,
      `deskripsi` TEXT NULL,
      `link_url` VARCHAR(500) NULL,
      `file_sertifikat` VARCHAR(255) NULL,
      `tahun` VARCHAR(20) NOT NULL DEFAULT '2026',
      `urutan` INT NOT NULL DEFAULT 1,
      `is_active` TINYINT(1) NOT NULL DEFAULT 1,
      `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
      `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
      INDEX `idx_kategori` (`kategori`),
      INDEX `idx_urutan` (`urutan`),
      INDEX `idx_is_active` (`is_active`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");

$wCols = $db->query("SHOW COLUMNS FROM pemeringkatan LIKE 'warna'")->fetchAll();
if (empty($wCols)) {
    $db->exec("ALTER TABLE pemeringkatan ADD COLUMN warna VARCHAR(30) NULL DEFAULT NULL AFTER badge_teks");
}

$colorSettings = [
    'pemeringkatan_color_internasional' => '#1E3A8A',
    'pemeringkatan_color_nasional'      => '#991B1B',
    'pemeringkatan_color_lokal'         => '#0D9488',
];
foreach ($colorSettings as $k => $v) {
    if (getPengaturan($k, '') === '') {
        setPengaturan($k, $v);
    }
}

$pRankCount = (int)$db->query("SELECT COUNT(*) FROM pemeringkatan")->fetchColumn();

if ($pRankCount === 0) {
    $initialRankings = [
        // 1. Lokal (Semarang & Jateng)
        [
            'judul' => 'EduRank: PTS Nomor 1 di Kota Semarang',
            'lembaga' => 'EduRank.org & Espos.id',
            'kategori' => 'lokal',
            'peringkat' => '#1',
            'peringkat_dari' => 'PTS di Kota Semarang',
            'badge_teks' => 'PTS Terbaik di Semarang 2026',
            'deskripsi' => 'Berdasarkan rilis pemeringkatan EduRank 2026 yang diwartakan Espos.id, Soegijapranata Catholic University (SCU) kembali mengukuhkan dominasinya sebagai Perguruan Tinggi Swasta (PTS) nomor satu di Kota Semarang dengan keunggulan kinerja riset 45%, keunggulan non-akademik/reputasi web 45%, dan dampak alumni 10%.',
            'link_url' => 'https://news.espos.id/masih-nomor-satu-di-semarang-scu-juga-masuk-100-besar-pts-terbaik-di-indonesia-2208781',
            'file_sertifikat' => '',
            'tahun' => '2026',
            'urutan' => 1,
            'is_active' => 1
        ],
        [
            'judul' => 'Suara Merdeka: Kampus Swasta Terbaik di Semarang',
            'lembaga' => 'Suara Merdeka',
            'kategori' => 'lokal',
            'peringkat' => 'Top Tier',
            'peringkat_dari' => 'Kota Semarang',
            'badge_teks' => 'Rujukan Utama Camaba 2026',
            'deskripsi' => 'Ulasan komprehensif Suara Merdeka menempatkan SCU dalam daftar jajaran kampus swasta terbaik dan terfavorit di Kota Semarang tahun 2026 sebagai referensi utama calon mahasiswa baru dengan reputasi mutu akademik unggul dan fasilitas berstandar tinggi.',
            'link_url' => 'https://www.suaramerdeka.com/pendidikan/0417103643/daftar-6-kampus-swasta-terbaik-di-semarang-tahun-2026-referensi-mantap-buat-camaba',
            'file_sertifikat' => '',
            'tahun' => '2026',
            'urutan' => 2,
            'is_active' => 1
        ],
        [
            'judul' => 'uniRank: Daftar Universitas Terkemuka di Jawa Tengah',
            'lembaga' => 'uniRank (University Ranking)',
            'kategori' => 'lokal',
            'peringkat' => 'Top Ranked',
            'peringkat_dari' => '55 Kampus Jawa Tengah',
            'badge_teks' => 'uniRank Official 2026',
            'deskripsi' => 'Pemeringkatan resmi uniRank 2026 yang mengevaluasi 55 perguruan tinggi terakreditasi di Jawa Tengah, menilai keterkenalan institusi, validitas perizinan, dan mutu penyelenggaraan pendidikan tinggi.',
            'link_url' => 'https://www.unirank.org/id/central-java/a-z/',
            'file_sertifikat' => '',
            'tahun' => '2026',
            'urutan' => 3,
            'is_active' => 1
        ],
        [
            'judul' => 'EduRank: Peringkat Universitas di Kota Semarang',
            'lembaga' => 'EduRank.org',
            'kategori' => 'lokal',
            'peringkat' => '#3',
            'peringkat_dari' => 'of 14 Kampus Semarang',
            'badge_teks' => 'EduRank Official 2026',
            'deskripsi' => 'Peringkat perguruan tinggi terkemuka di Kota Semarang berdasarkan luaran riset akademik dan reputasi institusi.',
            'link_url' => 'https://edurank.org/uni/soegijapranata-catholic-university/rankings/',
            'file_sertifikat' => '',
            'tahun' => '2026',
            'urutan' => 4,
            'is_active' => 1
        ],

        // 2. Nasional (Indonesia)
        [
            'judul' => 'EduRank: 100 Besar Kampus Terbaik di Indonesia',
            'lembaga' => 'EduRank.org',
            'kategori' => 'nasional',
            'peringkat' => 'Top 100',
            'peringkat_dari' => 'Nasional (Negeri & Swasta)',
            'badge_teks' => '100 Besar Kampus Terbaik Indonesia',
            'deskripsi' => 'SCU berhasil menembus jajaran 100 besar perguruan tinggi terbaik di Indonesia (kategori universitas negeri maupun swasta) versi EduRank 2026, ditopang oleh produktivitas publikasi ilmiah, sitasi riset, dan prestasi non-akademik.',
            'link_url' => 'https://news.espos.id/masih-nomor-satu-di-semarang-scu-juga-masuk-100-besar-pts-terbaik-di-indonesia-2208781',
            'file_sertifikat' => '',
            'tahun' => '2026',
            'urutan' => 1,
            'is_active' => 1
        ],
        [
            'judul' => 'Entrepreneurial Marketing Campus for Impact',
            'lembaga' => 'Marketeers & MCorp (Indonesia Marketing Festival 2026)',
            'kategori' => 'nasional',
            'peringkat' => 'Awardee 2026',
            'peringkat_dari' => 'Indonesia Marketing Festival ke-14',
            'badge_teks' => 'Marketeers Official 2026',
            'deskripsi' => 'Penghargaan bergengsi dari Marketeers dan MCorp pada ajang The 14th Annual Indonesia Marketing Festival 2026 atas peran aktif SCU dalam mengimplementasikan nilai Entrepreneurial Marketing: Creativity, Innovation, Entrepreneurship, and Leadership di lingkungan kampus.',
            'link_url' => 'https://www.marketeers.com',
            'file_sertifikat' => 'sertifikat_marketing_campus_for_impact_2026.pdf',
            'tahun' => '2026',
            'urutan' => 2,
            'is_active' => 1
        ],
        [
            'judul' => 'EduRank: Peringkat Universitas di Indonesia',
            'lembaga' => 'EduRank.org',
            'kategori' => 'nasional',
            'peringkat' => '#65',
            'peringkat_dari' => 'of 562 Kampus Indonesia',
            'badge_teks' => 'EduRank Official 2026',
            'deskripsi' => 'Akreditasi Unggul dari Badan Akreditasi Nasional Perguruan Tinggi dan menempati peringkat ke-65 dari 562 perguruan tinggi se-Indonesia berdasarkan data EduRank.org.',
            'link_url' => 'https://edurank.org/uni/soegijapranata-catholic-university/rankings/',
            'file_sertifikat' => '',
            'tahun' => '2026',
            'urutan' => 3,
            'is_active' => 1
        ],

        // 3. Internasional (Global / World)
        [
            'judul' => 'AD Scientific Index: World Scientist & University Rankings',
            'lembaga' => 'AD Scientific Index',
            'kategori' => 'internasional',
            'peringkat' => 'World Ranked',
            'peringkat_dari' => '24.200 Institusi Global',
            'badge_teks' => 'AD Scientific Index 2026',
            'deskripsi' => 'Pemeringkatan internasional berbasis kinerja ilmuwan dan peneliti institusi (metrik Total H-Index, i10-Index, dan Sitasi publikasi ilmiah) yang memetakan kontribusi riset para akademisi SCU di kancah global dan Asia.',
            'link_url' => 'https://adscientificindex.com/university/universitas-katolik-soegijapranata/10171/',
            'file_sertifikat' => '',
            'tahun' => '2026',
            'urutan' => 1,
            'is_active' => 1
        ],
        [
            'judul' => 'uniRank: Global & National University Ranking Profile',
            'lembaga' => 'uniRank World Universities',
            'kategori' => 'internasional',
            'peringkat' => 'Certified',
            'peringkat_dari' => 'World Directory',
            'badge_teks' => 'uniRank Certified 2026',
            'deskripsi' => 'Profil pemeringkatan internasional uniRank yang memuat evaluasi reputasi web independen, akreditasi program studi institusi, serta keterbukaan informasi akademik bagi komunitas internasional.',
            'link_url' => 'https://www.unirank.org/id/uni/soegijapranata-catholic-university/',
            'file_sertifikat' => '',
            'tahun' => '2026',
            'urutan' => 2,
            'is_active' => 1
        ],
        [
            'judul' => 'UI GreenMetric: World\'s Most Sustainable University',
            'lembaga' => 'UI GreenMetric',
            'kategori' => 'internasional',
            'peringkat' => '#1398',
            'peringkat_dari' => 'World',
            'badge_teks' => 'UI GreenMetric Official 2025',
            'deskripsi' => 'Peringkat World\'s Most Sustainable University & kampus hijau di Kota Semarang dalam pengelolaan keberlanjutan dan lingkungan ramah energi.',
            'link_url' => 'https://greenmetric.ui.ac.id',
            'file_sertifikat' => 'sertifikat_ui_greenmetric_2025.webp',
            'tahun' => '2025',
            'urutan' => 3,
            'is_active' => 1
        ],
    ];

    $ins_rank = $db->prepare("
        INSERT INTO pemeringkatan (judul, lembaga, kategori, peringkat, peringkat_dari, badge_teks, deskripsi, link_url, file_sertifikat, tahun, urutan, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    foreach ($initialRankings as $r) {
        $ins_rank->execute([
            $r['judul'],
            $r['lembaga'],
            $r['kategori'],
            $r['peringkat'],
            $r['peringkat_dari'],
            $r['badge_teks'],
            $r['deskripsi'],
            $r['link_url'],
            $r['file_sertifikat'],
            $r['tahun'],
            $r['urutan'],
            $r['is_active'] ?? 1,
        ]);
    }
    echo "Seeded initial pemeringkatan table.\n";
}

// 15. Tabel Tahapan Kartu Siklus SPMI PPEPP (Dapat diedit penuh oleh admin)
$db->exec("
    CREATE TABLE IF NOT EXISTS `spmi_ppepp_stages` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `tahap_ke` INT NOT NULL UNIQUE,
      `inisial` VARCHAR(10) NOT NULL DEFAULT 'P',
      `label` VARCHAR(100) NOT NULL,
      `badge_teks` VARCHAR(150) NOT NULL,
      `judul` VARCHAR(255) NOT NULL,
      `tagline` VARCHAR(255) NULL,
      `warna` VARCHAR(30) NOT NULL DEFAULT '#C0392B',
      `deskripsi` TEXT NOT NULL,
      `prosedur_label` VARCHAR(150) NOT NULL DEFAULT 'PROSEDUR OPERASIONAL:',
      `langkah_prosedur` TEXT NOT NULL,
      `dokumen_label` VARCHAR(150) NOT NULL DEFAULT 'DOKUMEN TERKAIT:',
      `dokumen_teks` TEXT NOT NULL,
      `aktor_label` VARCHAR(150) NOT NULL DEFAULT 'PENANGGUNG JAWAB:',
      `aktor_teks` TEXT NOT NULL,
      `tombol_teks` VARCHAR(100) NOT NULL DEFAULT 'Dokumen Terkait',
      `tombol_url` VARCHAR(255) NOT NULL DEFAULT '#dokumen-spmi',
      `footer_teks` VARCHAR(255) NOT NULL DEFAULT 'Standar Mutu UNIKA Soegijapranata',
      `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
");
$stagesCount = (int)$db->query("SELECT COUNT(*) FROM spmi_ppepp_stages")->fetchColumn();
if ($stagesCount === 0) {
    require_once __DIR__ . '/migrate_spmi_ppepp_stages.php';
}

echo "All migrations finished successfully!\n";

