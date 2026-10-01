<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

echo "Setting up tables for Pelatihan Eksternal CMS...\n";

// 1. Table: layanan_keunggulan
$db->exec("CREATE TABLE IF NOT EXISTS layanan_keunggulan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT NOT NULL,
    icon VARCHAR(100) DEFAULT 'bi-award-fill',
    urutan INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

// Seed layanan_keunggulan if empty
$cnt = $db->query("SELECT COUNT(*) FROM layanan_keunggulan")->fetchColumn();
if ($cnt == 0) {
    $ins = $db->prepare("INSERT INTO layanan_keunggulan (judul, deskripsi, icon, urutan, is_active) VALUES (?, ?, ?, ?, 1)");
    $ins->execute([
        'Fasilitator Asesor & Pakar Nasional',
        'Dibina langsung oleh akademisi berlatar belakang Asesor BAN-PT / LAM bersertifikasi nasional, auditor mutu ISO/IRCA, dan pengelola SPMI berpengalaman UNIKA Soegijapranata.',
        'bi-person-video3',
        1
    ]);
    $ins->execute([
        'Sertifikat Resmi Ber-Nomor Registrasi',
        'Sertifikat resmi kelulusan diterbitkan dengan Surat Keputusan (SK) LPM UNIKA, dilengkapi kode verifikasi digital yang sah sebagai bukti kualifikasi kompetensi untuk akreditasi.',
        'bi-award-fill',
        2
    ]);
    $ins->execute([
        'Paket Toolkit SPMI & Instrumen Siap Pakai',
        'Peserta memperoleh master instrumen audit mutu, form rekapitulasi temuan KTS/OB, template formulir Rapat Tinjauan Manajemen (RTM), serta softcopy pedoman SPMI terkini.',
        'bi-folder-check',
        3
    ]);
    $ins->execute([
        'Klinik Asistensi Pasca-Pelatihan',
        'Mitra mendapatkan hak konsultasi daring lanjutan pasca-kegiatan guna memastikan hasil pelatihan dan audit mutu dapat diimplementasikan dengan lancar di kampus masing-masing.',
        'bi-headset',
        4
    ]);
    echo "Seeded layanan_keunggulan.\n";
}

// 2. Table: layanan_alur
$db->exec("CREATE TABLE IF NOT EXISTS layanan_alur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    langkah_ke INT DEFAULT 1,
    judul VARCHAR(255) NOT NULL,
    deskripsi TEXT NOT NULL,
    urutan INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$cnt = $db->query("SELECT COUNT(*) FROM layanan_alur")->fetchColumn();
if ($cnt == 0) {
    $ins = $db->prepare("INSERT INTO layanan_alur (langkah_ke, judul, deskripsi, urutan, is_active) VALUES (?, ?, ?, ?, 1)");
    $ins->execute([1, 'Pilih Program / Konsultasi', 'Mitra memilih paket tema pelatihan yang tersedia atau mengajukan kebutuhan khusus materi sesuai kondisi SPMI kampus.', 1]);
    $ins->execute([2, 'Surat Permohonan Resmi', 'Instansi mitra mengirimkan surat permohonan resmi atau mengisi formulir pendaftaran daring untuk penetapan kuota peserta.', 2]);
    $ins->execute([3, 'Konfirmasi Jadwal & Silabus', 'LPM UNIKA menerbitkan surat kesediaan narasumber, menyepakati jadwal, serta menyampaikan rincian administrasi kegiatan.', 3]);
    $ins->execute([4, 'Pelatihan & Praktik Audit', 'Penyampaian materi interaktif, studi kasus instrumen akreditasi riil, simulasi audit, dan perumusan berita acara temuan.', 4]);
    $ins->execute([5, 'Sertifikasi & Toolkit Mutu', 'Penerbitan Sertifikat Resmi Auditor/Pelatihan ber-nomor register unik LPM UNIKA disertai paket dokumen kerja SPMI.', 5]);
    echo "Seeded layanan_alur.\n";
}

// 3. Table: layanan_faq
$db->exec("CREATE TABLE IF NOT EXISTS layanan_faq (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pertanyaan VARCHAR(255) NOT NULL,
    jawaban TEXT NOT NULL,
    urutan INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

$cnt = $db->query("SELECT COUNT(*) FROM layanan_faq")->fetchColumn();
if ($cnt == 0) {
    $ins = $db->prepare("INSERT INTO layanan_faq (pertanyaan, jawaban, urutan, is_active) VALUES (?, ?, ?, 1)");
    $ins->execute([
        'Apakah sertifikat auditor AMI ini diakui untuk penilaian akreditasi LAM dan BAN-PT?',
        'Ya, sangat diakui. Sertifikat pelatihan dan sertifikasi kompetensi auditor penjaminan mutu internal yang diterbitkan oleh LPM UNIKA memenuhi regulasi SN-Dikti serta diakui oleh para asesor BAN-PT dan Lembaga Akreditasi Mandiri (LAMEMBA, LAM-PTKes, LAM INFOKOM, LAM TEKNIK, dll.) sebagai bukti sahih kualifikasi auditor internal dalam siklus evaluasi (E) SPMI.',
        1
    ]);
    $ins->execute([
        'Apakah materi pelatihan dapat disesuaikan dengan kebutuhan khusus kampus kami (In-House Training)?',
        'Bisa. Kami menyediakan skema In-House Training yang fleksibel. Tim fasilitator LPM UNIKA dapat hadir langsung di kampus Anda atau via daring, dengan studi kasus yang dibedah langsung dari dokumen manual mutu, standar dikti, atau borang akreditasi program studi yang sedang Anda persiapkan.',
        2
    ]);
    $ins->execute([
        'Bagaimana mekanisme pembayaran resmi dan kelengkapan bukti administrasi SPJ?',
        'Demi keamanan dan tata kelola keuangan yang akuntabel, seluruh pembayaran investasi wajib ditransfer ke Rekening Resmi Universitas Katolik Soegijapranata (bukan rekening pribadi). Sekretariat LPM UNIKA akan menerbitkan surat penawaran resmi, invoice tagihan, kuitansi bermaterai, serta faktur pajak yang sah untuk kelengkapan berkas SPJ institusi Anda.',
        3
    ]);
    $ins->execute([
        'Berapa jumlah peserta minimal & maksimal per kelas?',
        'Untuk pelatihan reguler publik, kuota per kelas dibatasi antara 15 hingga 25 peserta agar sesi pendampingan dan simulasi audit berlangsung interaktif. Untuk skema in-house training, kuota peserta dapat disesuaikan hingga 30 orang per angkatan.',
        4
    ]);
    echo "Seeded layanan_faq.\n";
}

// 4. Default Settings in `pengaturan`
setPengaturan('pelatihan_hero_title', getPengaturan('pelatihan_hero_title', 'Pelatihan Eksternal & Pengembangan Mutu'));
setPengaturan('pelatihan_hero_desc', getPengaturan('pelatihan_hero_desc', 'Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata menyelenggarakan bimbingan teknis, workshop klinik akreditasi, dan pelatihan sertifikasi auditor penjaminan mutu internal bagi perguruan tinggi mitra.'));
setPengaturan('pelatihan_email_kontak', getPengaturan('pelatihan_email_kontak', 'lpm@unika.ac.id'));
setPengaturan('pelatihan_telepon', getPengaturan('pelatihan_telepon', '(024) 8441555 Ext. 1473'));
setPengaturan('pelatihan_jam_layanan', getPengaturan('pelatihan_jam_layanan', 'Senin – Jumat (08:00 – 15:30 WIB)'));
setPengaturan('pelatihan_alamat', getPengaturan('pelatihan_alamat', 'Kampus UNIKA Bendan Dhuwur, Semarang'));

echo "Done setup_pelatihan_cms.php!\n";
