<?php
require_once __DIR__ . '/../config/database.php';
$db = getDB();

echo "Running migrations...\n";

// 1. Check status column in berita
$cols = $db->query("SHOW COLUMNS FROM berita LIKE 'status'")->fetchAll();
if (empty($cols)) {
    $db->exec("ALTER TABLE berita ADD COLUMN status ENUM('draft', 'published') NOT NULL DEFAULT 'published' AFTER tanggal_publikasi");
    echo "Added status column to berita.\n";
} else {
    echo "Column status already exists in berita.\n";
}

// 2. Seed faqs table if empty
$faq_count = (int)$db->query("SELECT COUNT(*) FROM faqs")->fetchColumn();
if ($faq_count === 0) {
    $initial_faqs = [
        [
            'pertanyaan' => 'Apa perbedaan mendasar antara SPMI dan SPME (Akreditasi)?',
            'jawaban'    => 'SPMI (Sistem Penjaminan Mutu Internal) dijalankan secara mandiri oleh internal perguruan tinggi melalui siklus PPEPP. Sedangkan SPME (Sistem Penjaminan Mutu Eksternal) adalah evaluasi dan pengakuan yang dilakukan oleh pihak eksternal independen seperti BAN-PT dan LAM.',
            'kategori'   => 'SPMI & PPEPP',
            'urutan'     => 1
        ],
        [
            'pertanyaan' => 'Kapan Audit Mutu Internal (AMI) dilaksanakan?',
            'jawaban'    => 'AMI di lingkungan UNIKA dilaksanakan secara berkala 1 (satu) kali setiap tahun akademik untuk seluruh program studi dan unit pendukung, disusul dengan Rapat Tinjauan Manajemen (RTM).',
            'kategori'   => 'Audit Mutu Internal (AMI)',
            'urutan'     => 2
        ],
        [
            'pertanyaan' => 'Apa yang harus dipersiapkan Program Studi menghadapi AMI?',
            'jawaban'    => 'Program Studi perlu memperbarui Dokumen Evaluasi Diri (DED), mengunggah bukti fisik ketercapaian standar SPMI, laporan kepuasan mahasiswa/dosen, serta menyiapkan tim prodi untuk wawancara visitasi auditor.',
            'kategori'   => 'Audit Mutu Internal (AMI)',
            'urutan'     => 3
        ],
        [
            'pertanyaan' => 'Apa yang dimaksud dengan Siklus PPEPP?',
            'jawaban'    => 'Siklus PPEPP adalah pilar utama dalam Sistem Penjaminan Mutu Internal (SPMI) di Unika Soegijapranata yang terdiri dari lima tahapan kerja terstruktur: Penetapan, Pelaksanaan, Evaluasi, Pengendalian, dan Peningkatan.',
            'kategori'   => 'SPMI & PPEPP',
            'urutan'     => 4
        ],
        [
            'pertanyaan' => 'Bagaimana masa berlaku dan siklus akreditasi program studi?',
            'jawaban'    => 'Sertifikat akreditasi dari BAN-PT maupun LAM berlaku selama 5 (lima) tahun. Menjelang 6 bulan masa berlaku berakhir, UPPS/Prodi wajib melakukan perpanjangan instrumen atau pengajuan reakreditasi.',
            'kategori'   => 'Akreditasi',
            'urutan'     => 5
        ],
    ];

    $stmt_faq = $db->prepare("INSERT INTO faqs (pertanyaan, jawaban, kategori, urutan, is_active) VALUES (?, ?, ?, ?, 1)");
    foreach ($initial_faqs as $f) {
        $stmt_faq->execute([$f['pertanyaan'], $f['jawaban'], $f['kategori'], $f['urutan']]);
    }
    echo "Seeded " . count($initial_faqs) . " FAQs.\n";
}

// 3. Seed glosarium table if empty
$glos_count = (int)$db->query("SELECT COUNT(*) FROM glosarium")->fetchColumn();
if ($glos_count === 0) {
    $initial_terms = [
        ['istilah' => 'SPMI', 'nama' => 'Sistem Penjaminan Mutu Internal', 'kategori' => 'Sistem & Regulasi', 'definisi' => 'Instrumen otonom terencana dan berkelanjutan yang dijalankan oleh internal perguruan tinggi untuk mengendalikan serta meningkatkan mutu penyelenggaraan pendidikan tinggi.'],
        ['istilah' => 'PPEPP', 'nama' => 'Penetapan, Pelaksanaan, Evaluasi, Pengendalian, Peningkatan', 'kategori' => 'Alur Kerja', 'definisi' => 'Siklus lima tahapan kerja baku yang wajib dijalankan dalam tata kelola penjaminan mutu internal secara konsisten dan berkesinambungan.'],
        ['istilah' => 'AMI', 'nama' => 'Audit Mutu Internal', 'kategori' => 'Evaluasi', 'definisi' => 'Proses pengujian yang independen, sistematis, dan terdokumentasi untuk memastikan pelaksanaan kegiatan di perguruan tinggi sesuai dengan standar SPMI.'],
        ['istilah' => 'GPM', 'nama' => 'Gugus Penjaminan Mutu', 'kategori' => 'Organisasi', 'definisi' => 'Unit fungsional pelaksana penjaminan mutu di tingkat Fakultas yang bertugas mengoordinasikan pemantauan dan evaluasi mutu akademik di lingkungan fakultas.'],
        ['istilah' => 'UPPS', 'nama' => 'Unit Pengelola Program Studi', 'kategori' => 'Organisasi', 'definisi' => 'Entitas akademik (biasanya Fakultas atau Sekolah Pascasarjana) yang menaungi dan bertanggung jawab langsung atas operasional serta akreditasi program studi.'],
        ['istilah' => 'KTS', 'nama' => 'Ketidaksesuaian', 'kategori' => 'Audit & Temuan', 'definisi' => 'Kondisi di mana pelaksanaan atau bukti hasil di lapangan tidak memenuhi kriteria tolok ukur standar mutu SPMI yang telah ditetapkan.'],
        ['istilah' => 'RTL', 'nama' => 'Rencana Tindak Lanjut', 'kategori' => 'Pengendalian', 'definisi' => 'Dokumen komitmen resmi yang dirumuskan oleh pimpinan unit/auditee untuk memperbaiki atau memenuhi rekomendasi temuan audit mutu dalam batas waktu tertentu.'],
        ['istilah' => 'RTM', 'nama' => 'Rapat Tinjauan Manajemen', 'kategori' => 'Pengendalian', 'definisi' => 'Rapat formal pimpinan universitas bersama dekan dan kepala unit untuk meninjau efektivitas SPMI dan mengambil keputusan strategis tindak lanjut mutu.'],
        ['istilah' => 'IKU & IKT', 'nama' => 'Indikator Kinerja Utama & Indikator Kinerja Tambahan', 'kategori' => 'Standar Mutu', 'definisi' => 'Tolok ukur keberhasilan universitas yang mengacu pada capaian standar nasional kementerian (IKU) serta standar keunggulan spesifik ciri khas UNIKA (IKT).'],
        ['istilah' => 'DED', 'nama' => 'Dokumen Evaluasi Diri', 'kategori' => 'Akreditasi & AMI', 'definisi' => 'Laporan komprehensif yang disusun mandiri oleh Program Studi berisi potret capaian tridharma, analisis SWOT, dan kesiapan sebelum diaudit atau diakreditasi.'],
        ['istilah' => 'SN Dikti', 'nama' => 'Standar Nasional Pendidikan Tinggi', 'kategori' => 'Regulasi Nasional', 'definisi' => 'Satuan standar minimal tentang sistem pendidikan tinggi di seluruh wilayah hukum NKRI yang mencakup standar pendidikan, penelitian, dan pengabdian masyarakat.'],
        ['istilah' => 'LAM', 'nama' => 'Lembaga Akreditasi Mandiri', 'kategori' => 'Akreditasi', 'definisi' => 'Lembaga independen yang dibentuk oleh masyarakat/organisasi profesi yang bertugas melakukan penilaian akreditasi program studi sesuai rumpun keilmuan.']
    ];

    $stmt_glos = $db->prepare("INSERT INTO glosarium (istilah, nama, kategori, definisi, urutan, is_active) VALUES (?, ?, ?, ?, ?, 1)");
    $ord = 1;
    foreach ($initial_terms as $g) {
        $stmt_glos->execute([$g['istilah'], $g['nama'], $g['kategori'], $g['definisi'], $ord++]);
    }
    echo "Seeded " . count($initial_terms) . " terms to glosarium.\n";
}

echo "Migration finished successfully.\n";
