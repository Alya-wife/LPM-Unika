<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();

echo "Creating tables for Layanan Pelatihan, Biaya, Jadwal, Brosur...\n";

// 1. Table: layanan_pelatihan
$db->exec("CREATE TABLE IF NOT EXISTS layanan_pelatihan (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_pelatihan VARCHAR(255) NOT NULL,
    kategori VARCHAR(100) DEFAULT 'Pelatihan Mutu',
    deskripsi TEXT,
    sasaran_peserta VARCHAR(255),
    durasi VARCHAR(100),
    materi_pokok TEXT,
    metode VARCHAR(100) DEFAULT 'Offline / Luring / Hybrid',
    fasilitas TEXT,
    urutan INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 2. Table: layanan_biaya
$db->exec("CREATE TABLE IF NOT EXISTS layanan_biaya (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_paket VARCHAR(255) NOT NULL,
    nominal VARCHAR(100) NOT NULL,
    periode_satuan VARCHAR(100) DEFAULT 'per peserta',
    deskripsi_singkat TEXT,
    rincian_fasilitas TEXT,
    is_populer TINYINT(1) DEFAULT 0,
    urutan INT DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 3. Table: layanan_jadwal
$db->exec("CREATE TABLE IF NOT EXISTS layanan_jadwal (
    id INT AUTO_INCREMENT PRIMARY KEY,
    nama_kegiatan VARCHAR(255) NOT NULL,
    tanggal_mulai DATE,
    tanggal_selesai DATE,
    lokasi VARCHAR(255),
    status ENUM('Terlaksana', 'Akan Datang', 'Pendaftaran Dibuka') DEFAULT 'Terlaksana',
    institusi_peserta TEXT,
    jumlah_peserta INT DEFAULT 0,
    keterangan TEXT,
    urutan INT DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// 4. Table: layanan_brosur
$db->exec("CREATE TABLE IF NOT EXISTS layanan_brosur (
    id INT AUTO_INCREMENT PRIMARY KEY,
    judul_brosur VARCHAR(255) NOT NULL,
    tahun VARCHAR(20) DEFAULT '2026',
    file_brosur VARCHAR(255) NOT NULL,
    file_cover VARCHAR(255) NULL,
    deskripsi TEXT,
    link_pendaftaran VARCHAR(255) NULL,
    is_active TINYINT(1) DEFAULT 1,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

// Seed initial data if empty
$countPel = $db->query("SELECT COUNT(*) FROM layanan_pelatihan")->fetchColumn();
if ($countPel == 0) {
    $insPel = $db->prepare("INSERT INTO layanan_pelatihan (nama_pelatihan, kategori, deskripsi, sasaran_peserta, durasi, materi_pokok, metode, fasilitas, urutan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    $insPel->execute([
        'Pelatihan & Sertifikasi Auditor Mutu Internal (AMI)',
        'Sertifikasi Kompetensi',
        'Pelatihan intensif bagi calon auditor internal perguruan tinggi guna membekali pemahaman audit berbasis bukti, penyusunan instrumen Desk Evaluation, teknik wawancara audit visitasi, perumusan temuan KTS/OB, serta pembuatan berita acara dan PTK.',
        'Dosen, Tenaga Kependidikan, Tim Penjaminan Mutu (GPM/UPPS/LPM)',
        '2 Hari (16 Jam Pelajaran)',
        "1. Kebijakan & Regulasi SN-Dikti dan Akreditasi BAN-PT/LAM\n2. Prinsip & Standar Audit Mutu Internal (ISO 19011:2018)\n3. Teknik Audit Dokumen (Desk Evaluation) & Audit Lapangan\n4. Klasifikasi Temuan: KTS (Mayor/Minor) dan Observasi (OB)\n5. Simulasi Audit Lapangan & Role-Play Wawancara Auditee\n6. Penyusunan Laporan Hasil Audit & Permintaan Tindakan Koreksi (PTK)",
        'Luring (Tatap Muka di Kampus UNIKA / In-House Mitra)',
        "Sertifikat Kelulusan Resmi LPM UNIKA Soegijapranata\nModul Lengkap (Cetak & Softcopy)\nTemplate Berkas Instrumen & Form AMI Lengkap Siap Pakai\nKonsumsi & Seminar Kit Lengkap",
        1
    ]);

    $insPel->execute([
        'Bimtek Perancangan & Implementasi SPMI Berbasis Siklus PPEPP',
        'Tata Kelola Mutu',
        'Bimbingan teknis perancangan Sistem Penjaminan Mutu Internal (SPMI) yang selaras dengan Standar Nasional Pendidikan Tinggi (SN-Dikti) dan standar perguruan tinggi, mencakup siklus Penetapan, Pelaksanaan, Evaluasi, Pengendalian, dan Peningkatan (PPEPP).',
        'Pimpinan Perguruan Tinggi, Dekan, Kaprodi, dan Pengelola LPM Mitra',
        '2 Hari (14 Jam Pelajaran)',
        "1. Desain Kebijakan SPMI, Manual Mutu, dan Standar Dikti\n2. Perumusan Indikator Kinerja Utama (IKU) & Tambahan (IKT)\n3. Penyusunan Prosedur Operasional Standar (POS/SOP)\n4. Mekanisme Rapat Tinjauan Manajemen (RTM)\n5. Rencana Tindak Lanjut (RTL) & Peningkatan Standar Mutu",
        'Luring / Hybrid',
        "Sertifikat Kehadiran Resmi\nKumpulan Dokumen SPMI Acuan Resmi UNIKA\nKonsultasi Langsung Bersama Pakar SPMI UNIKA\nModul Digital Lengkap",
        2
    ]);

    $insPel->execute([
        'Workshop Klinik LED & LKPS Akreditasi Program Studi Menuju Unggul',
        'Pendampingan Akreditasi',
        'Workshop asistensi dan bedah instrumen Laporan Evaluasi Diri (LED) serta Laporan Kinerja Program Studi (LKPS) berbasis 9 kriteria kualifikasi unggul untuk LAMEMBA, LAM INFOKOM, LAM TEKNIK, LAM-PTKes, dan BAN-PT.',
        'Tim Akreditasi Program Studi & Unit Pengelola Program Studi (UPPS)',
        '1 Hari Intensif (8 Jam Pelajaran)',
        "1. Strategi Pemenuhan Syarat Perlu Peringkat Unggul\n2. Analisis Kuantitatif LKPS & Validasi Data Dukung\n3. Bedah Narasi Kualitatif LED (Kriteria 1 s.d. 9)\n4. Simulasi Tanya-Jawab Asesmen Lapangan Bersama Asesor Senior\n5. Evaluasi Kesenjangan (Gap Analysis) Skor Akreditasi",
        'Tatap Muka / Online via Zoom',
        "Sertifikat Partisipasi\nMatriks Analisis Kesiapan Instrumen Unggul\nCatatan & Rekomendasi Review Dokumen Akreditasi",
        3
    ]);

    $insPel->execute([
        'Benchmarking & Pelatihan Manajemen Gugus Penjaminan Mutu (GPM)',
        'Penguatan Unit Mutu',
        'Penguatan peran dan fungsi Gugus Penjaminan Mutu (GPM) di tingkat fakultas dan program studi guna memastikan monitoring evaluasi pembelajaran berkala berjalan efektif dan berkelanjutan.',
        'Ketua GPM, Anggota GPM, dan Sekretariat Mutu Fakultas',
        '1 Hari (6 Jam Pelajaran)',
        "1. Struktur & Uraian Tugas Efektif Personil GPM\n2. Monitoring & Evaluasi Perkuliahan Tengah/Akhir Semester\n3. Survei Kepuasan Pemangku Kepentingan (Mahasiswa, Dosen, Alumni)\n4. Pengelolaan Arsip Bukti Fisik SPMI Tingkat Fakultas",
        'Luring di Kampus UNIKA Soegijapranata',
        "Sertifikat Pelatihan\nKunjungan ke Kantor LPM & Fakultas UNIKA\nTemplate Instrumen Monitoring GPM",
        4
    ]);
}

// Seed initial Biaya
$countBiaya = $db->query("SELECT COUNT(*) FROM layanan_biaya")->fetchColumn();
if ($countBiaya == 0) {
    $insB = $db->prepare("INSERT INTO layanan_biaya (nama_paket, nominal, periode_satuan, deskripsi_singkat, rincian_fasilitas, is_populer, urutan) VALUES (?, ?, ?, ?, ?, ?, ?)");

    $insB->execute([
        'Paket Reguler: Pelatihan Auditor AMI',
        'Rp 2.500.000',
        'per peserta',
        'Program pelatihan reguler terbuka untuk dosen dan staf penjaminan mutu perguruan tinggi mitra dengan jadwal terselenggara di Kampus UNIKA Soegijapranata.',
        "Pelatihan Tatap Muka 2 Hari Penuh\nSertifikat Resmi Auditor Mutu Internal bersertifikat UNIKA\nModul Lengkap Hardcopy & Flashdisk Materi Softcopy\nSeminar Kit, Goodie Bag, & Alat Tulis\n2x Makan Siang & 4x Coffee Break Eksklusif\nAkses Jaringan Komunitas Auditor Mutu",
        1,
        1
    ]);

    $insB->execute([
        'Paket In-House Training (Di Kampus Mitra)',
        'Rp 15.000.000',
        'per paket (maks. 25 peserta)',
        'Tim instruktur ahli LPM UNIKA hadir langsung di kampus institusi Anda untuk memberikan pelatihan SPMI atau AMI secara terfokus.',
        "Pelatihan 2 Hari di Lokasi Kampus Mitra\nKuota Peserta hingga 25 Orang Dosen/Tendik\n2 Orang Instruktur Pakar Mutu Senior LPM UNIKA\nSertifikat Kelulusan untuk seluruh peserta yang memenuhi syarat\nTemplate Instrumen SPMI/AMI siap kustomisasi kampus mitra\nKonsultasi tindak lanjut pasca-pelatihan selama 1 bulan",
        0,
        2
    ]);

    $insB->execute([
        'Klinik Pendampingan LED/LKPS Program Studi',
        'Rp 10.000.000',
        'per program studi',
        'Asistensi mendalam dan review menyeluruh terhadap dokumen borang akreditasi program studi oleh asesor berpengalaman sebelum disubmit.',
        "Bedah Tuntas Dokumen LED & LKPS 9 Kriteria\nSimulasi Asesmen Lapangan (Mock Assessment)\nLaporan Evaluasi Rinci & Rekomendasi Peningkatan Nilai\nSesi Pembahasan Bersama Pimpinan UPPS & Task Force\nDurasi Asistensi: 2 Hari Kerja",
        0,
        3
    ]);
}

// Seed initial Jadwal
$countJadwal = $db->query("SELECT COUNT(*) FROM layanan_jadwal")->fetchColumn();
if ($countJadwal == 0) {
    $insJ = $db->prepare("INSERT INTO layanan_jadwal (nama_kegiatan, tanggal_mulai, tanggal_selesai, lokasi, status, institusi_peserta, jumlah_peserta, keterangan, urutan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $insJ->execute([
        'Pelatihan & Sertifikasi Auditor Mutu Internal (AMI) Angkatan VIII',
        '2026-11-19',
        '2026-11-20',
        'Gedung Fransiskus Asisi Lt. 3, Kampus UNIKA Soegijapranata Semarang',
        'Pendaftaran Dibuka',
        'Perguruan Tinggi Negeri dan Swasta se-Indonesia',
        40,
        'Pendaftaran dibuka hingga 10 November 2026 atau jika kuota telah terpenuhi.',
        1
    ]);

    $insJ->execute([
        'In-House Training Implementasi SPMI & Siklus PPEPP',
        '2026-09-18',
        '2026-09-19',
        'Auditorium Kampus Mitra (Jawa Tengah)',
        'Terlaksana',
        'Universitas Mitra Wilayah LLDIKTI VI',
        28,
        'Telah sukses dilaksanakan dengan predikat sangat baik dan kelulusan 100%.',
        2
    ]);

    $insJ->execute([
        'Pelatihan Auditor Mutu Internal (AMI) Angkatan VII',
        '2026-08-14',
        '2026-08-15',
        'Ruang Seminar Gedung Thomas Aquinas UNIKA Soegijapranata',
        'Terlaksana',
        '14 Perguruan Tinggi dari Jawa Tengah, DIY, dan Jawa Timur',
        38,
        'Diikuti oleh 38 peserta dosen dan pengelola SPMI.',
        3
    ]);

    $insJ->execute([
        'Bimtek Penyusunan Standar SPMI Berbasis SN-Dikti Terbaru',
        '2026-07-22',
        '2026-07-23',
        'Hotel Santika Premiere Semarang & UNIKA SCU',
        'Terlaksana',
        'Konsorsium Perguruan Tinggi Swasta',
        32,
        'Pendampingan penyusunan standar pendidikan, penelitian, dan pengabdian.',
        4
    ]);

    $insJ->execute([
        'Workshop Klinik Akreditasi Program Studi Menuju Peringkat Unggul',
        '2026-06-10',
        '2026-06-10',
        'Ruang Rapat LPM UNIKA Soegijapranata',
        'Terlaksana',
        'Fakultas internal UNIKA dan Perguruan Tinggi Mitra',
        22,
        'Bedah berkas instrumen LED dan LKPS kriteria 1 sampai 9.',
        5
    ]);
}

// Seed initial Brosur
$countBrosur = $db->query("SELECT COUNT(*) FROM layanan_brosur")->fetchColumn();
if ($countBrosur == 0) {
    $insBr = $db->prepare("INSERT INTO layanan_brosur (judul_brosur, tahun, file_brosur, file_cover, deskripsi, link_pendaftaran, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");

    $insBr->execute([
        'Brosur Resmi Program Pelatihan & Pendampingan Mutu LPM SCU 2026',
        '2026',
        'Brosur_Layanan_Pelatihan_LPM_UNIKA_2026.pdf',
        'brosur_cover_2026.webp',
        'Memuat rincian paket pelatihan auditor AMI, bimbingan teknis SPMI, klinik akreditasi program studi, profil narasumber/instruktur, jadwal pelaksanaan tahun 2026, serta prosedur pendaftaran.',
        'https://wa.me/6281234567890?text=Halo%20LPM%20UNIKA,%20saya%20ingin%20mendaftar%20Pelatihan%20Auditor%20AMI',
        1
    ]);
}

// Create uploads directory for layanan
$layanan_upload_dir = __DIR__ . '/../uploads/layanan';
if (!is_dir($layanan_upload_dir)) {
    mkdir($layanan_upload_dir, 0755, true);
}

// Copy sample brochure PDF if not exists
$sample_pdf = $layanan_upload_dir . '/Brosur_Layanan_Pelatihan_LPM_UNIKA_2026.pdf';
if (!file_exists($sample_pdf)) {
    // Check if we have another PDF in uploads/kunjungan or akreditasi to copy or create dummy
    $existing_pdf = glob(__DIR__ . '/../uploads/akreditasi/*.pdf');
    if (!empty($existing_pdf)) {
        copy($existing_pdf[0], $sample_pdf);
    } else {
        file_put_contents($sample_pdf, "%PDF-1.4\n%LPM SCU Brosur Pelatihan\n%%EOF");
    }
}

echo "Setup completed successfully!\n";
