<?php
require_once __DIR__ . '/../config/database.php';

$db = getDB();

echo "Mulai migrasi tabel spmi_ppepp_stages...\n";

// 1. Buat Tabel spmi_ppepp_stages
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
echo "Tabel spmi_ppepp_stages siap.\n";

// 2. Data default 5 tahapan PPEPP
$default_stages = [
    [
        'tahap_ke'         => 1,
        'inisial'          => 'P',
        'label'            => 'Penetapan',
        'badge_teks'       => 'TAHAP 1 DARI 5 • SIKLUS SPMI',
        'judul'            => 'Penetapan Standar Dikti (P)',
        'tagline'          => 'Penetapan Standar Dikti;',
        'warna'            => '#C0392B',
        'deskripsi'        => 'Tahap awal perumusan, penyelarasan, dan penetapan seluruh tolok ukur standar mutu akademik, non-akademik, serta standar ciri khas Universitas Katolik Soegijapranata yang disusun melampaui Standar Nasional Pendidikan Tinggi (SN Dikti).',
        'prosedur_label'   => 'PROSEDUR OPERASIONAL:',
        'langkah_prosedur' => "Penyusunan draf standar mutu bersama tim pakar LPM dan UPPS.\nUji publik dan review komprehensif bersama Gugus Penjaminan Mutu (GPM).\nPemberian pertimbangan dan persetujuan formal oleh Senat Akademik Universitas.\nPenetapan resmi pemberlakuan standar melalui Surat Keputusan Rektor.",
        'dokumen_label'    => 'DOKUMEN TERKAIT:',
        'dokumen_teks'     => 'Kebijakan SPMI, Manual Penetapan Standar, dan Buku Standar SPMI (Pendidikan, Riset, PkM, & Identitas).',
        'aktor_label'      => 'PENANGGUNG JAWAB:',
        'aktor_teks'       => 'Rektor, Senat Akademik Universitas, & LPM',
        'tombol_teks'      => 'Dokumen Terkait',
        'tombol_url'       => '#dokumen-spmi',
        'footer_teks'      => 'Standar Mutu UNIKA Soegijapranata'
    ],
    [
        'tahap_ke'         => 2,
        'inisial'          => 'P',
        'label'            => 'Pelaksanaan',
        'badge_teks'       => 'TAHAP 2 DARI 5 • SIKLUS SPMI',
        'judul'            => 'Pelaksanaan Standar Dikti (P)',
        'tagline'          => 'Pelaksanaan Standar Dikti;',
        'warna'            => '#689F38',
        'deskripsi'        => 'Tahap implementasi seluruh indikator capaian standar yang telah disahkan ke dalam operasional tridharma perguruan tinggi dan tata kelola unit kerja di lingkungan kampus UNIKA.',
        'prosedur_label'   => 'PROSEDUR OPERASIONAL:',
        'langkah_prosedur' => "Sosialisasi menyeluruh isi standar mutu kepada dosen, tendik, dan mahasiswa.\nPenerapan SOP operasional dalam perkuliahan, bimbingan, penelitian, dan pengabdian.\nPenyusunan dan pengarsipan portofolio pembelajaran serta bukti fisik ketercapaian standar.\nPencatatan real-time melalui sistem informasi akademik dan portal terpadu kampus.",
        'dokumen_label'    => 'DOKUMEN TERKAIT:',
        'dokumen_teks'     => 'SOP Pelaksanaan, Modul Perkuliahan, Rencana Pembelajaran (RPS), Kontrak Kinerja Unit, dan Bukti Tridharma.',
        'aktor_label'      => 'PENANGGUNG JAWAB:',
        'aktor_teks'       => 'Dekan, Ketua Program Studi, Kepala Lembaga/Biro, Dosen, & Tendik',
        'tombol_teks'      => 'Dokumen Terkait',
        'tombol_url'       => '#dokumen-spmi',
        'footer_teks'      => 'Standar Mutu UNIKA Soegijapranata'
    ],
    [
        'tahap_ke'         => 3,
        'inisial'          => 'E',
        'label'            => 'Evaluasi',
        'badge_teks'       => 'TAHAP 3 DARI 5 • SIKLUS SPMI',
        'judul'            => 'Evaluasi (Pelaksanaan) Standar Dikti (E)',
        'tagline'          => 'Evaluasi (Pelaksanaan) Standar Dikti;',
        'warna'            => '#6A4C93',
        'deskripsi'        => 'Tahap pemantauan, pengukuran, dan pengujian berkala terhadap kesesuaian pelaksanaan kegiatan dengan tolok ukur standar mutu, guna mendeteksi secara dini potensi hambatan maupun ketidaksesuaian.',
        'prosedur_label'   => 'PROSEDUR OPERASIONAL:',
        'langkah_prosedur' => "Pengisian Dokumen Evaluasi Diri (DED) berbasis data riil oleh UPPS/Unit.\nPelaksanaan Audit Mutu Internal (AMI) terjadwal oleh auditor internal bersertifikat.\nVisitasi lapangan, wawancara auditee, dan uji petik bukti dukung fisik/digital.\nPenyusunan Laporan Hasil Audit Mutu, rekapitulasi temuan KTS, dan potensi risiko.",
        'dokumen_label'    => 'DOKUMEN TERKAIT:',
        'dokumen_teks'     => 'Instrumen Audit Mutu Internal (AMI), Dokumen Evaluasi Diri (DED), Laporan Temuan KTS, dan Survei Kepuasan.',
        'aktor_label'      => 'PENANGGUNG JAWAB:',
        'aktor_teks'       => 'Kepala Pusat AMI, Tim Auditor Internal Tersertifikasi, & Auditee',
        'tombol_teks'      => 'Dokumen Terkait',
        'tombol_url'       => '#dokumen-spmi',
        'footer_teks'      => 'Standar Mutu UNIKA Soegijapranata'
    ],
    [
        'tahap_ke'         => 4,
        'inisial'          => 'P',
        'label'            => 'Pengendalian',
        'badge_teks'       => 'TAHAP 4 DARI 5 • SIKLUS SPMI',
        'judul'            => 'Pengendalian (Pelaksanaan) Standar Dikti (P)',
        'tagline'          => 'Pengendalian (Pelaksanaan) Standar Dikti; dan',
        'warna'            => '#00838F',
        'deskripsi'        => 'Tahap analisis dan perumusan tindakan korektif terhadap temuan evaluasi melalui forum resmi Rapat Tinjauan Manajemen (RTM) agar penyimpangan segera teratasi dan tidak berulang.',
        'prosedur_label'   => 'PROSEDUR OPERASIONAL:',
        'langkah_prosedur' => "Penyusunan Rencana Tindak Lanjut (RTL) dan tenggat waktu perbaikan oleh auditee.\nPenyelenggaraan Rapat Tinjauan Manajemen (RTM) berjenjang (Prodi, Fakultas, Universitas).\nKeputusan pimpinan terkait alokasi sumber daya pendukung percepatan pemenuhan standar.\nVerifikasi dan monitoring penutupan status temuan KTS oleh GPM dan LPM.",
        'dokumen_label'    => 'DOKUMEN TERKAIT:',
        'dokumen_teks'     => 'Risalah RTM, Lembar Rencana Tindak Lanjut (RTL), Bukti Perbaikan Tindak Lanjut, dan Berita Acara RTM.',
        'aktor_label'      => 'PENANGGUNG JAWAB:',
        'aktor_teks'       => 'Rektor, Wakil Rektor, Dekan Fakultas, Kepala Unit Kerja, & GPM',
        'tombol_teks'      => 'Dokumen Terkait',
        'tombol_url'       => '#dokumen-spmi',
        'footer_teks'      => 'Standar Mutu UNIKA Soegijapranata'
    ],
    [
        'tahap_ke'         => 5,
        'inisial'          => 'P',
        'label'            => 'Peningkatan',
        'badge_teks'       => 'TAHAP 5 DARI 5 • SIKLUS SPMI',
        'judul'            => 'Peningkatan Standar Dikti (P)',
        'tagline'          => 'Peningkatan Standar Dikti.',
        'warna'            => '#E65100',
        'deskripsi'        => 'Tahap menaikkan target atau memperluas kriteria standar mutu yang telah tercapai secara konsisten (kaizen berkelanjutan) agar kualitas institusi terus meningkat menuju standar internasional.',
        'prosedur_label'   => 'PROSEDUR OPERASIONAL:',
        'langkah_prosedur' => "Kajian kelayakan terhadap standar yang telah berhasil dipenuhi secara penuh dan konsisten.\nBenchmarking mutu ke institusi mitra terbaik di tingkat nasional dan internasional.\nRevisi rumusan indikator capaian standar menjadi lebih tinggi dan adaptif masa depan.\nPengesahan standar edisi terbaru sebagai titik awal siklus Penetapan berikutnya.",
        'dokumen_label'    => 'DOKUMEN TERKAIT:',
        'dokumen_teks'     => 'Naskah Rekomendasi Peningkatan Mutu, Laporan Studi Banding/Benchmarking, dan Draf Revisi Standar Baru.',
        'aktor_label'      => 'PENANGGUNG JAWAB:',
        'aktor_teks'       => 'Rektor, Senat Akademik Universitas, Dewan Pakar Mutu, & LPM',
        'tombol_teks'      => 'Dokumen Terkait',
        'tombol_url'       => '#dokumen-spmi',
        'footer_teks'      => 'Standar Mutu UNIKA Soegijapranata'
    ]
];

$stmt = $db->prepare("
    INSERT INTO `spmi_ppepp_stages` 
    (`tahap_ke`, `inisial`, `label`, `badge_teks`, `judul`, `tagline`, `warna`, `deskripsi`, `prosedur_label`, `langkah_prosedur`, `dokumen_label`, `dokumen_teks`, `aktor_label`, `aktor_teks`, `tombol_teks`, `tombol_url`, `footer_teks`)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ON DUPLICATE KEY UPDATE 
    `inisial` = VALUES(`inisial`),
    `label` = VALUES(`label`),
    `badge_teks` = VALUES(`badge_teks`),
    `judul` = VALUES(`judul`),
    `tagline` = VALUES(`tagline`),
    `warna` = VALUES(`warna`),
    `deskripsi` = VALUES(`deskripsi`),
    `prosedur_label` = VALUES(`prosedur_label`),
    `langkah_prosedur` = VALUES(`langkah_prosedur`),
    `dokumen_label` = VALUES(`dokumen_label`),
    `dokumen_teks` = VALUES(`dokumen_teks`),
    `aktor_label` = VALUES(`aktor_label`),
    `aktor_teks` = VALUES(`aktor_teks`),
    `tombol_teks` = VALUES(`tombol_teks`),
    `tombol_url` = VALUES(`tombol_url`),
    `footer_teks` = VALUES(`footer_teks`)
");

foreach ($default_stages as $stg) {
    $stmt->execute([
        $stg['tahap_ke'],
        $stg['inisial'],
        $stg['label'],
        $stg['badge_teks'],
        $stg['judul'],
        $stg['tagline'],
        $stg['warna'],
        $stg['deskripsi'],
        $stg['prosedur_label'],
        $stg['langkah_prosedur'],
        $stg['dokumen_label'],
        $stg['dokumen_teks'],
        $stg['aktor_label'],
        $stg['aktor_teks'],
        $stg['tombol_teks'],
        $stg['tombol_url'],
        $stg['footer_teks']
    ]);
}

echo "Seeding 5 tahap PPEPP berhasil diselesaikan!\n";
