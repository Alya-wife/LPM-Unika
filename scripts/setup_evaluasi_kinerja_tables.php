<?php
/**
 * Setup & Migrasi Tabel Evaluasi Kinerja LPM UNIKA
 */
require_once __DIR__ . '/../config/database.php';

$db = getDB();

echo "Mempersiapkan tabel-tabel Evaluasi Kinerja...\n";

// 1. Tambah pengaturan default jika belum ada
$settings_defaults = [
    'evaluasi_kinerja_aktif'       => '1',
    'evaluasi_tahun_akademik_aktif' => '2025/2026',
    'evaluasi_judul'               => 'Sistem Evaluasi Kinerja Personel LPM',
    'evaluasi_petunjuk_umum'       => 'Sistem evaluasi kinerja internal berkala Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata. Penilaian mencakup evaluasi diri, evaluasi rekan sejawat (360 peer review), dan penilaian komprehensif oleh Kepala LPM guna menjamin mutu kerja profesional dan berintegritas.',
];

foreach ($settings_defaults as $k => $v) {
    $exists = $db->prepare("SELECT COUNT(*) FROM pengaturan WHERE kunci = ?");
    $exists->execute([$k]);
    if ($exists->fetchColumn() == 0) {
        $ins = $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?)");
        $ins->execute([$k, $v]);
    }
}

// 2. Tabel Peserta Evaluasi (Akun Google Terdaftar)
$db->exec("CREATE TABLE IF NOT EXISTS evaluasi_peserta (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama VARCHAR(150) NOT NULL,
    email VARCHAR(191) NOT NULL UNIQUE,
    jabatan VARCHAR(150) NOT NULL,
    peran ENUM('kepala', 'personel') NOT NULL DEFAULT 'personel',
    avatar VARCHAR(255) NULL,
    status TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 3. Tabel Master Pertanyaan
$db->exec("CREATE TABLE IF NOT EXISTS evaluasi_pertanyaan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    kategori ENUM('diri', 'antar_personel', 'kepala') NOT NULL,
    bagian VARCHAR(100) NULL,
    nomor_urut INT NOT NULL DEFAULT 1,
    pertanyaan TEXT NOT NULL,
    tipe_jawaban ENUM('teks', 'skala_3', 'skala_5') NOT NULL DEFAULT 'teks',
    keterangan TEXT NULL,
    wajib TINYINT(1) NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 4. Tabel Jawaban Evaluasi Diri (Penilaian Pribadi)
$db->exec("CREATE TABLE IF NOT EXISTS evaluasi_diri_jawaban (
    id INT AUTO_INCREMENT PRIMARY KEY,
    peserta_id INT NOT NULL,
    tahun_akademik VARCHAR(20) NOT NULL,
    pertanyaan_id INT NOT NULL,
    jawaban_teks TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_diri_ans (peserta_id, tahun_akademik, pertanyaan_id),
    INDEX idx_diri_thn (tahun_akademik, peserta_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 5. Tabel Jawaban Evaluasi Antar Personel (Rekan Kerja)
$db->exec("CREATE TABLE IF NOT EXISTS evaluasi_antar_jawaban (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penilai_id INT NOT NULL,
    dinilai_id INT NOT NULL,
    tahun_akademik VARCHAR(20) NOT NULL,
    pertanyaan_id INT NOT NULL,
    skor TINYINT NULL,
    jawaban_teks TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_antar_ans (penilai_id, dinilai_id, tahun_akademik, pertanyaan_id),
    INDEX idx_antar_thn (tahun_akademik, dinilai_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 6. Tabel Jawaban Evaluasi Kepala LPM (Penilaian Personel oleh Kepala)
$db->exec("CREATE TABLE IF NOT EXISTS evaluasi_kepala_jawaban (
    id INT AUTO_INCREMENT PRIMARY KEY,
    penilai_id INT NOT NULL,
    dinilai_id INT NOT NULL,
    tahun_akademik VARCHAR(20) NOT NULL,
    pertanyaan_id INT NOT NULL,
    skor TINYINT NULL,
    catatan TEXT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_kepala_ans (dinilai_id, tahun_akademik, pertanyaan_id),
    INDEX idx_kepala_thn (tahun_akademik, dinilai_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 7. Tabel Status Submission / Pengiriman Formulir
$db->exec("CREATE TABLE IF NOT EXISTS evaluasi_submission (
    id INT AUTO_INCREMENT PRIMARY KEY,
    tahun_akademik VARCHAR(20) NOT NULL,
    penilai_id INT NOT NULL,
    dinilai_id INT NULL,
    tipe_evaluasi ENUM('diri', 'antar_personel', 'kepala') NOT NULL,
    frekuensi_kerja TINYINT NULL,
    status ENUM('draft', 'submitted') NOT NULL DEFAULT 'submitted',
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_sub (tahun_akademik, penilai_id, dinilai_id, tipe_evaluasi),
    INDEX idx_sub_thn (tahun_akademik, penilai_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 8. Seeding Peserta Awal (Mengambil dari tim_lpm & user aktif)
$countPeserta = (int)$db->query("SELECT COUNT(*) FROM evaluasi_peserta")->fetchColumn();
if ($countPeserta === 0) {
    echo "Seeding peserta evaluasi awal...\n";
    $peserta_list = [
        [
            'nama'    => 'Stefani Lily Indarto, SE., MM., Ak., CA., CPA.',
            'email'   => 'lily@unika.ac.id',
            'jabatan' => 'Kepala Lembaga Penjaminan Mutu',
            'peran'   => 'kepala',
        ],
        [
            'nama'    => 'Vera Retnowati, ST., MM.',
            'email'   => 'vera@unika.ac.id',
            'jabatan' => 'Sekretaris Lembaga Penjaminan Mutu',
            'peran'   => 'personel',
        ],
        [
            'nama'    => 'Ir. I.M. Tri Hesti Mulyani, MT.',
            'email'   => 'hesti@unika.ac.id',
            'jabatan' => 'Ketua Pusat Pengembangan Sistem Penjaminan Mutu',
            'peran'   => 'personel',
        ],
        [
            'nama'    => 'dr. Maya Yanuarty, M.Biomed',
            'email'   => 'maya@unika.ac.id',
            'jabatan' => 'Ketua Pusat Audit Mutu Internal',
            'peran'   => 'personel',
        ],
        [
            'nama'    => 'Ir. Lintang Jata Angghita, ST., M.Ling',
            'email'   => 'lintang@unika.ac.id',
            'jabatan' => 'Ketua Pusat Pemeringkatan',
            'peran'   => 'personel',
        ],
        [
            'nama'    => 'Hermawan, S.M.',
            'email'   => 'tu.lpm@unika.ac.id',
            'jabatan' => 'Staf Tata Usaha LPM',
            'peran'   => 'personel',
        ],
        [
            'nama'    => 'Ravy Whienelda (Tester / Dev)',
            'email'   => 'ravywhienelda@gmail.com',
            'jabatan' => 'Personel Tim LPM (Testing & Verifikasi)',
            'peran'   => 'personel',
        ],
    ];

    $stmt_ins_p = $db->prepare("INSERT INTO evaluasi_peserta (nama, email, jabatan, peran, status) VALUES (?, ?, ?, ?, 1)");
    foreach ($peserta_list as $p) {
        $stmt_ins_p->execute([$p['nama'], $p['email'], $p['jabatan'], $p['peran']]);
    }
}

// 9. Seeding Pertanyaan (Jika masih kosong)
$countQ = (int)$db->query("SELECT COUNT(*) FROM evaluasi_pertanyaan")->fetchColumn();
if ($countQ === 0) {
    echo "Seeding daftar pertanyaan evaluasi kinerja...\n";
    $insQ = $db->prepare("INSERT INTO evaluasi_pertanyaan (kategori, bagian, nomor_urut, pertanyaan, tipe_jawaban, keterangan, wajib, is_active) VALUES (?, ?, ?, ?, ?, ?, 1, 1)");

    // A. Kategori: Evaluasi Diri (Penilaian Pribadi) - 11 Butir
    $pertanyaan_diri = [
        // Bagian I. Capaian Kinerja
        [
            'bagian' => 'Bagian I. Capaian Kinerja',
            'nomor'  => 1,
            'teks'   => 'Apakah Anda telah menyusun dan melaksanakan rencana kerja sesuai tanggung jawab jabatan, serta apa capaian utama yang dihasilkan selama periode evaluasi?',
            'tipe'   => 'teks',
            'ket'    => 'Tuliskan bukti atau contoh konkret untuk mendukung jawaban Anda.'
        ],
        [
            'bagian' => 'Bagian I. Capaian Kinerja',
            'nomor'  => 2,
            'teks'   => 'Apakah pekerjaan diselesaikan tepat waktu, sesuai standar kualitas, dan didukung dokumentasi serta bukti pelaksanaan yang memadai?',
            'tipe'   => 'teks',
            'ket'    => 'Tuliskan bukti atau contoh konkret untuk mendukung jawaban Anda.'
        ],
        // Bagian II. Tanggung Jawab dan Profesionalitas
        [
            'bagian' => 'Bagian II. Tanggung Jawab dan Profesionalitas',
            'nomor'  => 3,
            'teks'   => 'Seberapa konsisten Anda menjalankan tanggung jawab jabatan serta menindaklanjuti penugasan dan keputusan rapat?',
            'tipe'   => 'teks',
            'ket'    => 'Uraikan pelaksanaan tindak lanjut dan konsistensi Anda.'
        ],
        [
            'bagian' => 'Bagian II. Tanggung Jawab dan Profesionalitas',
            'nomor'  => 4,
            'teks'   => 'Seberapa proaktif Anda mengidentifikasi masalah, mengusulkan solusi, dan menyampaikan kendala sebelum berdampak pada pencapaian target?',
            'tipe'   => 'teks',
            'ket'    => 'Tuliskan contoh inisiatif solusi yang telah Anda usulkan.'
        ],
        [
            'bagian' => 'Bagian II. Tanggung Jawab dan Profesionalitas',
            'nomor'  => 5,
            'teks'   => 'Bagaimana Anda memastikan pekerjaan tetap berjalan dan tanggung jawab terselesaikan ketika menghadapi hambatan?',
            'tipe'   => 'teks',
            'ket'    => 'Jelaskan strategi atau tindakan nyata yang Anda tempuh.'
        ],
        // Bagian III. Komunikasi dan Kerjasama
        [
            'bagian' => 'Bagian III. Komunikasi dan Kerjasama',
            'nomor'  => 6,
            'teks'   => 'Bagaimana Anda menjaga komunikasi, koordinasi, dan kerja sama dengan rekan kerja di lingkungan LPM, baik pimpinan, sekretaris, kepala pusat, maupun staf, dalam mendukung pelaksanaan tugas dan pencapaian tujuan bersama?',
            'tipe'   => 'teks',
            'ket'    => 'Jelaskan pola komunikasi dan koordinasi yang Anda terapkan.'
        ],
        [
            'bagian' => 'Bagian III. Komunikasi dan Kerjasama',
            'nomor'  => 7,
            'teks'   => 'Bagaimana kontribusi Anda dalam pekerjaan lintas pusat, termasuk penyediaan informasi dan dokumen yang dibutuhkan secara tepat waktu?',
            'tipe'   => 'teks',
            'ket'    => 'Uraikan keterlibatan Anda dalam sinergi kerja lintas pusat/bidang.'
        ],
        [
            'bagian' => 'Bagian III. Komunikasi dan Kerjasama',
            'nomor'  => 8,
            'teks'   => 'Apa kendala koordinasi yang Anda hadapi, bagaimana mengatasinya, dan apa kontribusi Anda dalam menciptakan suasana kerja yang profesional dan produktif?',
            'tipe'   => 'teks',
            'ket'    => 'Tuliskan solusi konkret yang telah Anda terapkan.'
        ],
        // Bagian IV. Pengembangan Diri
        [
            'bagian' => 'Bagian IV. Pengembangan Diri',
            'nomor'  => 9,
            'teks'   => 'Apa kelemahan atau kesenjangan kinerja yang Anda identifikasi, dan masukan apa yang telah diterima beserta tindak lanjutnya?',
            'tipe'   => 'teks',
            'ket'    => 'Jelaskan proses refleksi dan langkah perbaikan yang diambil.'
        ],
        [
            'bagian' => 'Bagian IV. Pengembangan Diri',
            'nomor'  => 10,
            'teks'   => 'Kompetensi apa yang perlu Anda tingkatkan, dan apa tiga target perbaikan yang akan Anda capai pada periode berikutnya?',
            'tipe'   => 'teks',
            'ket'    => 'Sebutkan 3 target spesifik peningkatan kompetensi Anda.'
        ],
        [
            'bagian' => 'Bagian IV. Pengembangan Diri',
            'nomor'  => 11,
            'teks'   => 'Dukungan apa yang Anda perlukan dari Kepala LPM atau universitas untuk mendukung pengembangan diri dan peningkatan kinerja?',
            'tipe'   => 'teks',
            'ket'    => 'Tuliskan usulan kebutuhan fasilitas, pelatihan, atau pendampingan.'
        ],
    ];

    foreach ($pertanyaan_diri as $pd) {
        $insQ->execute(['diri', $pd['bagian'], $pd['nomor'], $pd['teks'], $pd['tipe'], $pd['ket']]);
    }

    // B. Kategori: Evaluasi Antar Personel (Rekan Kerja) - 9 Skala + 3 Terbuka
    $pertanyaan_antar = [
        // Bagian A. Komunikasi dan Responsivitas
        [
            'bagian' => 'A. Komunikasi dan Responsivitas',
            'nomor'  => 1,
            'teks'   => 'Rekan kerja merespons komunikasi, terbuka terhadap koordinasi, dan bersedia memberikan klarifikasi terkait pekerjaan.',
            'tipe'   => 'skala_3',
            'ket'    => 'Skala: (1) Sering, (2) Kadang-kadang, (3) Terbatas, atau N/A'
        ],
        [
            'bagian' => 'A. Komunikasi dan Responsivitas',
            'nomor'  => 2,
            'teks'   => 'Rekan kerja menyampaikan informasi yang diperlukan secara tepat waktu serta memberitahukan kendala atau perubahan rencana yang berdampak pada pihak lain.',
            'tipe'   => 'skala_3',
            'ket'    => 'Skala: (1) Sering, (2) Kadang-kadang, (3) Terbatas, atau N/A'
        ],
        [
            'bagian' => 'A. Komunikasi dan Responsivitas',
            'nomor'  => 3,
            'teks'   => 'Rekan kerja menjaga komunikasi yang terbuka dan efektif untuk mendukung kelancaran pekerjaan bersama.',
            'tipe'   => 'skala_3',
            'ket'    => 'Skala: (1) Sering, (2) Kadang-kadang, (3) Terbatas, atau N/A'
        ],
        // Bagian B. Tanggung Jawab dan Keandalan
        [
            'bagian' => 'B. Tanggung Jawab dan Keandalan',
            'nomor'  => 4,
            'teks'   => 'Rekan kerja menyelesaikan tugas sesuai tanggung jawab, kesepakatan, dan tenggat waktu yang telah ditetapkan.',
            'tipe'   => 'skala_3',
            'ket'    => 'Skala: (1) Sering, (2) Kadang-kadang, (3) Terbatas, atau N/A'
        ],
        [
            'bagian' => 'B. Tanggung Jawab dan Keandalan',
            'nomor'  => 5,
            'teks'   => 'Rekan kerja menindaklanjuti pekerjaan yang telah disepakati serta memastikan serah terima informasi dan dokumen berjalan dengan baik.',
            'tipe'   => 'skala_3',
            'ket'    => 'Skala: (1) Sering, (2) Kadang-kadang, (3) Terbatas, atau N/A'
        ],
        [
            'bagian' => 'B. Tanggung Jawab dan Keandalan',
            'nomor'  => 6,
            'teks'   => 'Rekan kerja dapat diandalkan dalam memenuhi komitmen dan mendukung kelancaran pelaksanaan tugas bersama.',
            'tipe'   => 'skala_3',
            'ket'    => 'Skala: (1) Sering, (2) Kadang-kadang, (3) Terbatas, atau N/A'
        ],
        // Bagian C. Kolaborasi dan Kontribusi
        [
            'bagian' => 'C. Kolaborasi dan Kontribusi',
            'nomor'  => 7,
            'teks'   => 'Rekan kerja bersedia membantu dan berbagi data, informasi, serta pengetahuan untuk mendukung penyelesaian pekerjaan bersama.',
            'tipe'   => 'skala_3',
            'ket'    => 'Skala: (1) Sering, (2) Kadang-kadang, (3) Terbatas, atau N/A'
        ],
        [
            'bagian' => 'C. Kolaborasi dan Kontribusi',
            'nomor'  => 8,
            'teks'   => 'Rekan kerja berkontribusi terhadap pencapaian target LPM secara keseluruhan, tidak hanya berfokus pada tugas atau tanggung jawabnya sendiri.',
            'tipe'   => 'skala_3',
            'ket'    => 'Skala: (1) Sering, (2) Kadang-kadang, (3) Terbatas, atau N/A'
        ],
        [
            'bagian' => 'C. Kolaborasi dan Kontribusi',
            'nomor'  => 9,
            'teks'   => 'Rekan kerja menyikapi perbedaan pendapat secara profesional dan mengutamakan penyelesaian masalah demi kepentingan bersama.',
            'tipe'   => 'skala_3',
            'ket'    => 'Skala: (1) Sering, (2) Kadang-kadang, (3) Terbatas, atau N/A'
        ],
        // PERTANYAAN TERBUKA
        [
            'bagian' => 'PERTANYAAN TERBUKA',
            'nomor'  => 10,
            'teks'   => 'Apa kontribusi positif rekan kerja yang paling Anda rasakan dalam mendukung pekerjaan bersama?',
            'tipe'   => 'teks',
            'ket'    => 'Tuliskan apresiasi atau kontribusi positif rekan kerja Anda.'
        ],
        [
            'bagian' => 'PERTANYAAN TERBUKA',
            'nomor'  => 11,
            'teks'   => 'Aspek kerja apa yang perlu ditingkatkan oleh rekan kerja tersebut, dan apa contoh perilaku atau kejadian serta dampaknya terhadap proses atau hasil pekerjaan bersama?',
            'tipe'   => 'teks',
            'ket'    => 'Berikan masukan konstruktif yang membangun.'
        ],
        [
            'bagian' => 'PERTANYAAN TERBUKA',
            'nomor'  => 12,
            'teks'   => 'Apa saran konkret yang dapat Anda berikan agar kerja sama dengan rekan kerja tersebut menjadi lebih efektif?',
            'tipe'   => 'teks',
            'ket'    => 'Tuliskan usulan solusi atau saran konkret perbaikan.'
        ],
    ];

    foreach ($pertanyaan_antar as $pa) {
        $insQ->execute(['antar_personel', $pa['bagian'], $pa['nomor'], $pa['teks'], $pa['tipe'], $pa['ket']]);
    }

    // C. Kategori: Evaluasi Kepala LPM (Penilaian Personel oleh Kepala) - 12 Aspek Skala 1-5 + Catatan
    $pertanyaan_kepala = [
        [
            'bagian' => 'Form Penilaian Kinerja Personel oleh Kepala LPM',
            'nomor'  => 1,
            'teks'   => 'Pencapaian target dan program kerja',
            'tipe'   => 'skala_5',
            'ket'    => 'Skala 1 (Tanggung jawab utama tidak terlaksana secara memadai) s.d 5 (Konsisten melampaui harapan), atau N/A. Sertakan catatan bukti pendukung.'
        ],
        [
            'bagian' => 'Form Penilaian Kinerja Personel oleh Kepala LPM',
            'nomor'  => 2,
            'teks'   => 'Kualitas dan ketepatan waktu hasil kerja',
            'tipe'   => 'skala_5',
            'ket'    => 'Kesesuaian standar mutu dan ketepatan tenggat waktu penyelesaian tugas.'
        ],
        [
            'bagian' => 'Form Penilaian Kinerja Personel oleh Kepala LPM',
            'nomor'  => 3,
            'teks'   => 'Konsistensi menjalankan tupoksi',
            'tipe'   => 'skala_5',
            'ket'    => 'Tanggung jawab berkelanjutan atas tugas pokok dan fungsi jabatan.'
        ],
        [
            'bagian' => 'Form Penilaian Kinerja Personel oleh Kepala LPM',
            'nomor'  => 4,
            'teks'   => 'Tanggung jawab dan akuntabilitas',
            'tipe'   => 'skala_5',
            'ket'    => 'Keberanian bertanggung jawab dan transparansi atas hasil pekerjaan.'
        ],
        [
            'bagian' => 'Form Penilaian Kinerja Personel oleh Kepala LPM',
            'nomor'  => 5,
            'teks'   => 'Inisiatif dan kemampuan menyelesaikan masalah',
            'tipe'   => 'skala_5',
            'ket'    => 'Proaktif memecahkan kendala teknis dan menawarkan solusi konstruktif.'
        ],
        [
            'bagian' => 'Form Penilaian Kinerja Personel oleh Kepala LPM',
            'nomor'  => 6,
            'teks'   => 'Responsivitas terhadap komunikasi dan penugasan',
            'tipe'   => 'skala_5',
            'ket'    => 'Kecepatan respon dan keterbukaan dalam koordinasi serta instruksi pimpinan.'
        ],
        [
            'bagian' => 'Form Penilaian Kinerja Personel oleh Kepala LPM',
            'nomor'  => 7,
            'teks'   => 'Pelaporan kemajuan dan penyampaian kendala',
            'tipe'   => 'skala_5',
            'ket'    => 'Ketertiban memberikan update kemajuan pekerjaan dan early warning kendala.'
        ],
        [
            'bagian' => 'Form Penilaian Kinerja Personel oleh Kepala LPM',
            'nomor'  => 8,
            'teks'   => 'Koordinasi dan kerja sama lintas pusat',
            'tipe'   => 'skala_5',
            'ket'    => 'Kemampuan berkolaborasi secara sinergis antar bidang di lingkungan LPM.'
        ],
        [
            'bagian' => 'Form Penilaian Kinerja Personel oleh Kepala LPM',
            'nomor'  => 9,
            'teks'   => 'Kontribusi terhadap program kerja bersama LPM',
            'tipe'   => 'skala_5',
            'ket'    => 'Dukungan aktif terhadap kegiatan akreditasi, audit, pemeringkatan, dan agenda besar LPM.'
        ],
        [
            'bagian' => 'Form Penilaian Kinerja Personel oleh Kepala LPM',
            'nomor'  => 10,
            'teks'   => 'Keterbukaan terhadap masukan dan tindak lanjut perbaikan',
            'tipe'   => 'skala_5',
            'ket'    => 'Sikap positif menerima saran dan konsistensi merealisasikan tindakan korektif.'
        ],
        [
            'bagian' => 'Form Penilaian Kinerja Personel oleh Kepala LPM',
            'nomor'  => 11,
            'teks'   => 'Dokumentasi dan pengelolaan bukti kerja',
            'tipe'   => 'skala_5',
            'ket'    => 'Kerapian pengarsipan dokumen, notulensi, regulasi, dan arsip digital.'
        ],
        [
            'bagian' => 'Form Penilaian Kinerja Personel oleh Kepala LPM',
            'nomor'  => 12,
            'teks'   => 'Perbaikan berkelanjutan dan pengembangan kompetensi',
            'tipe'   => 'skala_5',
            'ket'    => 'Kemauan belajar, peningkatan keahlian profesional, dan inovasi kerja.'
        ],
    ];

    foreach ($pertanyaan_kepala as $pk) {
        $insQ->execute(['kepala', $pk['bagian'], $pk['nomor'], $pk['teks'], $pk['tipe'], $pk['ket']]);
    }
}

echo "Setup tabel-tabel Evaluasi Kinerja berhasil diselesaikan!\n";
