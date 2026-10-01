<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

echo "--- 1. SEEDING PENGATURAN SPMI CHART & PORTAL ---\n";
$spmi_settings = [
    'spmi_chart_title' => 'Siklus PPEPP SPMI Interaktif',
    'spmi_chart_badge' => 'Siklus Penjaminan Mutu Berkelanjutan',
    'spmi_chart_desc'  => 'Implementasi penjaminan mutu di Universitas Katolik Soegijapranata berlandaskan pada 5 tahap siklus berkelanjutan (PPEPP). Klik salah satu lingkaran siklus di bawah ini untuk menelaah penjelasan rinci setiap tahapannya.',

    'portal_sista_name'     => 'Portal SISTA',
    'portal_sista_badge'    => 'STANDAR AKADEMIK',
    'portal_sista_desc'     => 'Pemantauan & pelaporan siklus PPEPP, perumusan capaian, bukti dukung pelaksanaan, dan rencana tindak lanjut termonitor secara digital terintegrasi.',
    'portal_sista_status'   => 'active',
    'portal_sista_url'      => 'https://sista.unika.ac.id',
    'portal_sista_cs_title' => 'Tautan Sistem SISTA Belum Dibuka',
    'portal_sista_cs_desc'  => 'Pemantauan dan pelaporan siklus PPEPP diaktifkan sesuai jadwal.',

    'portal_spmi_name'      => 'Portal SPMI Kemendikti',
    'portal_spmi_badge'     => 'PELAPORAN NASIONAL',
    'portal_spmi_desc'      => 'Rekapitulasi dan pelaporan evaluasi pelaksanaan penjaminan mutu perguruan tinggi secara berkala kepada Kementerian Pendidikan Tinggi, Sains, dan Teknologi.',
    'portal_spmi_status'    => 'active',
    'portal_spmi_url'       => 'https://spmi.kemdiktisaintek.go.id/auth/login',
    'portal_spmi_cs_title'  => 'Tautan Sistem SPMI Kemendikti Belum Dibuka',
    'portal_spmi_cs_desc'   => 'Pelaporan evaluasi pelaksanaan penjaminan mutu akan diaktifkan sesuai jadwal.',

    'portal_eppepp_name'     => 'Portal E-PPEPP',
    'portal_eppepp_badge'    => 'SIKLUS MUTU PPEPP',
    'portal_eppepp_desc'     => 'Sistem informasi elektronik implementasi, evaluasi pelaksanaan, dan pengendalian tahapan siklus PPEPP secara berkesinambungan.',
    'portal_eppepp_status'   => 'coming_soon',
    'portal_eppepp_url'      => 'https://e-ppepp.unika.ac.id',
    'portal_eppepp_cs_title' => 'Tautan Sistem e-PPEPP Belum Dibuka',
    'portal_eppepp_cs_desc'  => 'Pelaksanaan dan evaluasi sistem e-PPEPP akan diaktifkan sesuai jadwal.',
];

foreach ($spmi_settings as $key => $val) {
    setPengaturan($key, $val);
}
echo "SPMI settings seeded successfully.\n";

echo "--- 2. SEEDING PENGATURAN AMI STAGES (1 s.d. 5) ---\n";
$ami_settings = [
    'ami_siklus_banner_desc' => 'Rangkaian terintegrasi tahapan Siklus 1 hingga Siklus 5 AMI Universitas Katolik Soegijapranata untuk menjamin standar mutu pendidikan tinggi yang unggul dan berkelanjutan.',
    'ami_stage1_title'       => 'Perencanaan & Sosialisasi',
    'ami_stage1_desc'        => 'Penetapan jadwal siklus audit, penunjukan tim auditor tersertifikasi, dan pengiriman surat pemberitahuan ke seluruh auditee.',
    'ami_stage2_title'       => 'Pengisian Dokumen Kinerja (DED)',
    'ami_stage2_desc'        => 'Program Studi dan Unit Kerja mengisi instrumen evaluasi diri dan mengunggah bukti fisik pendukung pada sistem e-AMI.',
    'ami_stage3_title'       => 'Audit Dokumen (Desk Evaluation)',
    'ami_stage3_desc'        => 'Auditor memeriksa kecukupan dan kesesuaian dokumen bukti kerja terhadap standar mutu sebelum pelaksanaan visitasi.',
    'ami_stage4_title'       => 'Audit Lapangan (Visitasi)',
    'ami_stage4_desc'        => 'Auditor melakukan verifikasi langsung, wawancara auditee, konfirmasi temuan KTS (Ketidaksesuaian), dan penandatanganan berita acara.',
    'ami_stage5_title'       => 'Rapat Tinjauan Manajemen (RTM)',
    'ami_stage5_desc'        => 'Penyampaian rekapitulasi temuan audit kepada Rektorat dan Pimpinan Unit untuk perumusan Rencana Tindak Lanjut (RTL).',

    // Siklus 2 & 3 e-AMI Links
    'ami_siklus2_judul'      => 'e-AMI: Siklus 2 - Pengisian Dokumen Kinerja (DED)',
    'ami_siklus2_url'        => 'https://e-ami.unika.ac.id/siklus2',
    'ami_siklus2_status'     => 'active',
    'ami_siklus2_desc'       => "Portal sistem e-AMI untuk pengisian dokumen evaluasi diri (DED), pengunggahan berkas bukti fisik ketercapaian standar mutu SPMI, dan sinkronisasi data akademik Program Studi.",

    'ami_siklus3_judul'      => 'e-AMI: Siklus 3 - Audit Dokumen (Desk Evaluation)',
    'ami_siklus3_url'        => 'https://e-ami.unika.ac.id/siklus3',
    'ami_siklus3_status'     => 'active',
    'ami_siklus3_desc'       => "Portal kerja auditor mutu internal untuk pelaksanaan desk evaluation, penelaahan bukti sahih indikator kinerja, pencatatan temuan observasi / ketidaksesuaian (KTS), dan penyusunan daftar tilik sebelum visitasi.",
];

foreach ($ami_settings as $key => $val) {
    setPengaturan($key, $val);
}
echo "AMI Stages settings seeded successfully.\n";

echo "--- 3. VERIFY AMI UPLOAD FILES ---\n";
// Pastikan file dummy ada, bila belum ada buat salinan dari file yang sudah ada
$basePdf = __DIR__ . '/../uploads/ami/siklus1/panduan_ami_2025_2026.pdf';
$baseImg = __DIR__ . '/../uploads/ami/siklus1/opening_meeting_2025.webp';

if (!file_exists($basePdf)) {
    // buat file dummy bila kosong
    @mkdir(dirname($basePdf), 0755, true);
    file_put_contents($basePdf, "%PDF-1.4\n1 0 obj<</Type/Catalog/Pages 2 0 R>>endobj 2 0 obj<</Type/Pages/Kids[3 0 R]/Count 1>>endobj 3 0 obj<</Type/Page/MediaBox[0 0 595 842]/Parent 2 0 R/Resources<<>>>>endobj\nxref\n0 4\n0000000000 65535 f\n0000000010 00000 n\n0000000060 00000 n\n0000000111 00000 n\ntrailer<</Size 4/Root 1 0 R>>\nstartxref\n190\n%%EOF");
}

$filesToEnsure = [
    'ami/siklus1/panduan_ami_2025_2026.pdf'           => $basePdf,
    'ami/siklus1/surat_tugas_auditor_2025.pdf'        => $basePdf,
    'ami/siklus1/instrumen_audit_prodi_2025.pdf'      => $basePdf,
    'ami/siklus1/sop_pelaksanaan_ami_scu.pdf'         => $basePdf,
    'ami/siklus1/opening_meeting_2025.webp'           => $baseImg,
    'ami/siklus1/sosialisasi_ami_2025.webp'           => $baseImg,

    'ami/siklus4/prosedur_mutu_audit_lapangan.pdf'    => $basePdf,
    'ami/siklus4/panduan_penilaian_kts.pdf'           => $basePdf,
    'ami/siklus4/jadwal_audit_lapangan_2025_2026.pdf' => $basePdf,
    'ami/siklus4/jadwal_audit_unit_non_akademik.pdf'  => $basePdf,
    'ami/siklus4/ba_fik_2025.pdf'                     => $basePdf,
    'ami/siklus4/presensi_fik_2025.pdf'               => $basePdf,
    'ami/siklus4/foto_audit_fik.webp'                 => $baseImg,
    'ami/siklus4/foto_audit_ti.webp'                  => $baseImg,
    'ami/siklus4/foto_audit_manajemen.webp'           => $baseImg,

    'ami/siklus5/notulensi_rtm_univ_2025.pdf'         => $basePdf,
    'ami/siklus5/presensi_rtm_univ_2025.pdf'          => $basePdf,
    'ami/siklus5/foto_rtm_univ.webp'                  => $baseImg,
    'ami/siklus5/foto_rtm_fik.webp'                   => $baseImg,
    'ami/siklus5/foto_rtm_akuntansi.webp'             => $baseImg,
];

foreach ($filesToEnsure as $relPath => $src) {
    $fullPath = __DIR__ . '/../uploads/' . $relPath;
    if (!file_exists($fullPath)) {
        @mkdir(dirname($fullPath), 0755, true);
        if (file_exists($src)) {
            copy($src, $fullPath);
        } else {
            file_put_contents($fullPath, "dummy content");
        }
    }
}
echo "Dummy upload files verified.\n";

echo "--- 4. SEEDING SIKLUS 1 KEGIATAN ---\n";
$db->exec("TRUNCATE TABLE `ami_siklus1_kegiatan`");
$stmtK1 = $db->prepare("INSERT INTO `ami_siklus1_kegiatan` (`periode`, `judul`, `file_dokumen`, `file_size`, `keterangan`, `urutan`) VALUES (?, ?, ?, ?, ?, ?)");

$kegiatan1 = [
    ['2025/2026', 'Jadwal & Panduan Lengkap Pelaksanaan AMI Siklus XIX TA 2025/2026', 'ami/siklus1/panduan_ami_2025_2026.pdf', '4.3 MB', 'Pedoman operasional, timeline rangkaian kegiatan audit internal, tata cara pelaporan kinerja, dan kalender visitasi.', 1],
    ['2025/2026', 'Surat Tugas Penunjukan Auditor Mutu Internal SCU 2025/2026', 'ami/siklus1/surat_tugas_auditor_2025.pdf', '1.2 MB', 'Penetapan nama tim auditor mutu internal tersertifikasi universitas dan alokasi penugasan audit pada seluruh fakultas/prodi.', 2],
    ['2025/2026', 'Instrumen Audit Kinerja Akademik & SPMI Berbasis 9 Kriteria Dikti', 'ami/siklus1/instrumen_audit_prodi_2025.pdf', '2.8 MB', 'Format instrumen asesmen diri (DED) yang telah disesuaikan dengan regulasi akreditasi LAM, BAN-PT, dan Permendikbudristek 53/2023.', 3],
    ['2025/2026', 'Standar Operasional Prosedur (SOP) Audit Mutu Internal & Kode Etik Asesor', 'ami/siklus1/sop_pelaksanaan_ami_scu.pdf', '850 KB', 'SOP resmi tata kelola audit internal, kode etik independensi auditor, dan mekanisme tindak lanjut temuan audit.', 4],
];
foreach ($kegiatan1 as $row) {
    $stmtK1->execute($row);
}
echo "Inserted " . count($kegiatan1) . " rows into ami_siklus1_kegiatan.\n";

echo "--- 5. SEEDING SIKLUS 1 OPENING MEETING ---\n";
$db->exec("TRUNCATE TABLE `ami_siklus1_opening`");
$stmtOp = $db->prepare("INSERT INTO `ami_siklus1_opening` (`periode`, `judul`, `tanggal_kegiatan`, `foto`, `keterangan`, `urutan`) VALUES (?, ?, ?, ?, ?, ?)");

$opening1 = [
    ['2025/2026', 'Opening Meeting Audit Mutu Internal (AMI) Siklus XIX Tingkat Universitas', '2025-10-14', 'ami/siklus1/opening_meeting_2025.webp', 'Pembukaan resmi rangkaian AMI 2025/2026 dihadiri Rektorat, Dekanat, Auditor, dan Tim LPM UNIKA di Ruang Teater Gedung Thomas Aquinas.', 1],
    ['2025/2026', 'Sosialisasi Teknis Pengisian Instrumen DED & Panduan Sistem e-AMI', '2025-10-18', 'ami/siklus1/sosialisasi_ami_2025.webp', 'Pemaparan pedoman pengisian instrumen evaluasi diri dan jadwal pengunggahan berkas bukti kerja kepada seluruh Ketua Program Studi dan Kepala Unit Kerja.', 2],
];
foreach ($opening1 as $row) {
    $stmtOp->execute($row);
}
echo "Inserted " . count($opening1) . " rows into ami_siklus1_opening.\n";

echo "--- 6. SEEDING SIKLUS 4 DOKUMEN (PROSEDUR & JADWAL) ---\n";
$db->exec("TRUNCATE TABLE `ami_siklus4_dokumen`");
$stmtD4 = $db->prepare("INSERT INTO `ami_siklus4_dokumen` (`periode`, `jenis`, `judul`, `file_dokumen`, `file_size`, `keterangan`, `urutan`) VALUES (?, ?, ?, ?, ?, ?, ?)");

$dok4 = [
    ['2025/2026', 'prosedur', 'Standar Prosedur Mutu Visitasi & Verifikasi Lapangan AMI', 'ami/siklus4/prosedur_mutu_audit_lapangan.pdf', '1.8 MB', 'SOP tata kelola kunjungan visitasi langsung ke fakultas dan unit kerja.', 1],
    ['2025/2026', 'prosedur', 'Panduan Penilaian & Klasifikasi Temuan KTS Mayor / Minor', 'ami/siklus4/panduan_penilaian_kts.pdf', '950 KB', 'Pedoman klasifikasi kategori ketidaksesuaian serta standar penetapan rekomendasi.', 2],
    ['2025/2026', 'jadwal', 'Jadwal Visitasi Audit Lapangan Tingkat Fakultas & Program Studi', 'ami/siklus4/jadwal_audit_lapangan_2025_2026.pdf', '1.2 MB', 'Matriks jadwal pelaksanaan visitasi lapangan dan nama auditor yang bertugas.', 1],
    ['2025/2026', 'jadwal', 'Daftar Pembagian Tim Asesor dan Jadwal Audit Unit Kerja Non-Akademik', 'ami/siklus4/jadwal_audit_unit_non_akademik.pdf', '880 KB', 'Jadwal visitasi audit penjaminan mutu lembaga, biro, dan unit pendukung kampus.', 2],
];
foreach ($dok4 as $row) {
    $stmtD4->execute($row);
}
echo "Inserted " . count($dok4) . " rows into ami_siklus4_dokumen.\n";

echo "--- 7. SEEDING SIKLUS 4 DOKUMENTASI AUDIT LAPANGAN ---\n";
$db->exec("TRUNCATE TABLE `ami_siklus4_dokumentasi`");
$stmtDt4 = $db->prepare("INSERT INTO `ami_siklus4_dokumentasi` (`periode`, `tingkat`, `fakultas`, `prodi`, `judul`, `narasi_berita_acara`, `file_berita_acara`, `file_daftar_hadir`, `foto_kegiatan`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$dokumentasi4 = [
    [
        '2025/2026',
        'fakultas',
        'Fakultas Ilmu Komputer',
        '',
        'Audit Lapangan Pengelolaan Fakultas Ilmu Komputer (FIK)',
        "Visitasi audit lapangan Fakultas Ilmu Komputer mencakup evaluasi tata kelola Renstra fakultas, ketercapaian IKU penelitian dan pengabdian dosen, serta sarana prasarana laboratorium riset berbasis AI & Cloud. Temuan observasi telah disepakati bersama Dekanat untuk ditindaklanjuti.",
        'ami/siklus4/ba_fik_2025.pdf',
        'ami/siklus4/presensi_fik_2025.pdf',
        json_encode(['ami/siklus4/foto_audit_fik.webp'])
    ],
    [
        '2025/2026',
        'prodi',
        'Fakultas Ilmu Komputer',
        'S1 Teknik Informatika',
        'Visitasi Audit Mutu Akademik Program Studi S1 Teknik Informatika',
        "Audit lapangan memverifikasi pemenuhan Capaian Pembelajaran Lulusan (CPL), integrasi kurikulum OBE berbasis industri global, rasio dosen-mahasiswa, serta portofolio asesmen tugas akhir mahasiswa. Kinerja prodi melampaui Standar Nasional Dikti dengan kategori Memuaskan.",
        'ami/siklus4/ba_fik_2025.pdf',
        'ami/siklus4/presensi_fik_2025.pdf',
        json_encode(['ami/siklus4/foto_audit_ti.webp'])
    ],
    [
        '2025/2026',
        'prodi',
        'Fakultas Ekonomi dan Bisnis',
        'S1 Manajemen',
        'Visitasi Audit Dokumen & Lapangan Program Studi S1 Manajemen',
        "Pelaksanaan visitasi dan konfirmasi bukti luaran akreditasi LAMEMBA, publikasi bereputasi internasional dosen dan mahasiswa, serta realisasi program student exchange ke perguruan tinggi mitra luar negeri berjalan tertib dan terverifikasi.",
        'ami/siklus4/ba_fik_2025.pdf',
        'ami/siklus4/presensi_fik_2025.pdf',
        json_encode(['ami/siklus4/foto_audit_manajemen.webp'])
    ],
];
foreach ($dokumentasi4 as $row) {
    $stmtDt4->execute($row);
}
echo "Inserted " . count($dokumentasi4) . " rows into ami_siklus4_dokumentasi.\n";

echo "--- 8. SEEDING SIKLUS 5 RTM (RAPAT TINJAUAN MANAJEMEN) ---\n";
$db->exec("TRUNCATE TABLE `ami_siklus5_rtm`");
$stmtR5 = $db->prepare("INSERT INTO `ami_siklus5_rtm` (`periode`, `tingkat`, `fakultas`, `prodi`, `judul`, `notulensi`, `file_notulensi`, `file_daftar_hadir`, `foto_kegiatan`) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");

$rtm5 = [
    [
        '2025/2026',
        'universitas',
        '',
        '',
        'Rapat Tinjauan Manajemen (RTM) Pleno Tingkat Universitas',
        "RTM Pleno Universitas dipimpin langsung oleh Rektor dan para Wakil Rektor didampingi Kepala LPM. Disampaikan rekapitulasi temuan audit mutu Siklus XIX, komitmen alokasi anggaran percepatan akreditasi internasional, dan pengesahan Rencana Tindak Lanjut (RTL) Universitas Tahun Akademik 2025/2026.",
        'ami/siklus5/notulensi_rtm_univ_2025.pdf',
        'ami/siklus5/presensi_rtm_univ_2025.pdf',
        json_encode(['ami/siklus5/foto_rtm_univ.webp'])
    ],
    [
        '2025/2026',
        'fakultas',
        'Fakultas Ilmu Komputer',
        '',
        'RTM Tingkat Fakultas Ilmu Komputer & Penetapan Action Plan',
        "Pembahasan tindak lanjut hasil audit lapangan FIK mengenai modernisasi server laboratorium komputasi, percepatan jabatan fungsional Lektor Kepala dosen muda, serta peningkatan hibah penelitian kolaboratif industri.",
        'ami/siklus5/notulensi_rtm_univ_2025.pdf',
        'ami/siklus5/presensi_rtm_univ_2025.pdf',
        json_encode(['ami/siklus5/foto_rtm_fik.webp'])
    ],
    [
        '2025/2026',
        'prodi',
        'Fakultas Ekonomi dan Bisnis',
        'S1 Akuntansi',
        'RTM Tingkat Program Studi S1 Akuntansi',
        "Evaluasi masa studi mahasiswa, pembaruan silabus mata kuliah audit forensik dan big data analytics, serta pemantauan waktu tunggu lulusan pertama memperoleh pekerjaan sesuai bidang kompetensi.",
        'ami/siklus5/notulensi_rtm_univ_2025.pdf',
        'ami/siklus5/presensi_rtm_univ_2025.pdf',
        json_encode(['ami/siklus5/foto_rtm_akuntansi.webp'])
    ],
];
foreach ($rtm5 as $row) {
    $stmtR5->execute($row);
}
echo "Inserted " . count($rtm5) . " rows into ami_siklus5_rtm.\n";

echo "=== ALL DUMMY DATA FOR SIKLUS AMI 1 s.d. 5 SEEDED SUCCESSFULLY! ===\n";
