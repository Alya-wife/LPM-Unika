<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Knowledge Center – Pusat Pengetahuan Mutu';
$meta_desc  = 'Knowledge Center LPM UNIKA: Tanya Jawab (FAQ), Glosarium Mutu, Buletin JAMUS, dan Artikel Penjaminan Mutu Perguruan Tinggi.';
$db = getDB();
$kalender_list = $db->query("SELECT * FROM kalender_mutu WHERE is_active = 1 ORDER BY urutan ASC, id ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Pusat Edukasi &amp; Pengetahuan
        </div>
        <h1 class="page-banner-title">Knowledge Center</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Knowledge</span>
        </div>
    </div>
</div>

<!-- ==============================================
     1. PENGANTAR KNOWLEDGE (Halaman Atas)
============================================== -->
<section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <span class="section-tag mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    Pusat Edukasi Mutu
                </span>
                <h2 class="section-title mb-3">Pustaka &amp; Pengetahuan Penjaminan Mutu</h2>
                <p style="color:var(--text-main);line-height:1.8;font-size:1rem;margin-bottom:1.25rem;">
                    Knowledge Center LPM UNIKA adalah ruang referensi digital yang disediakan bagi seluruh dosen, tenaga kependidikan, auditor, dan mahasiswa untuk memahami prinsip, siklus, regulasi, dan istilah teknis Sistem Penjaminan Mutu Internal.
                </p>
                <div class="d-flex gap-3 flex-wrap">
                    <a href="#faq-section" class="btn-hero-primary" style="background:var(--navy);border:none;padding:0.65rem 1.4rem;font-size:0.88rem;">
                        Lihat FAQ Mutu &darr;
                    </a>
                    <a href="#kalender-section" class="btn-hero-secondary" style="background:#fff;border:1.5px solid var(--border);color:var(--navy);padding:0.65rem 1.4rem;font-size:0.88rem;">
                        Kalender Mutu 2026–2027 &darr;
                    </a>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card-lpm p-4" style="background:var(--bg-main);border-radius:var(--radius-lg);border-left:5px solid var(--purple);">
                    <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:1.05rem;margin-bottom:0.75rem;">
                        Mengapa Budaya Mutu Penting?
                    </div>
                    <p style="font-size:0.875rem;color:var(--text-muted);line-height:1.75;margin-bottom:1rem;">
                        Mutu bukan sekadar kepatuhan dokumen menjelang akreditasi, melainkan kebiasaan sehari-hari <em>(habit of excellence)</em> seluruh civitas akademika UNIKA dalam melayani dan mendidik generasi muda bangsa.
                    </p>
                    <div class="d-flex align-items-center gap-2" style="font-size:0.8rem;font-weight:700;color:var(--purple);">
                        <span>Talenta Pro Patria et Humanitate</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================
     2. FAQ MUTU (Scroll ke bawah)
============================================== -->
<section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);" id="faq-section">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tag mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                </svg>
                Pertanyaan Populer
            </span>
            <h2 class="section-title">Tanya Jawab (FAQ) Mutu</h2>
            <p class="section-desc mx-auto">Pertanyaan yang sering diajukan terkait SPMI, AMI, dan Akreditasi.</p>
        </div>

        <div class="row justify-content-center">
            <div class="col-lg-9">
                <div class="accordion" id="accordionFaq">
                    <?php
                    $faqs = [
                        ['Apa perbedaan mendasar antara SPMI dan SPME (Akreditasi)?', 'SPMI (Sistem Penjaminan Mutu Internal) dijalankan secara mandiri oleh internal perguruan tinggi melalui siklus PPEPP. Sedangkan SPME (Sistem Penjaminan Mutu Eksternal) adalah evaluasi dan pengakuan yang dilakukan oleh pihak eksternal independen seperti BAN-PT dan LAM.'],
                        ['Kapan Audit Mutu Internal (AMI) dilaksanakan?', 'AMI di lingkungan UNIKA dilaksanakan secara berkala 1 (satu) kali setiap tahun akademik untuk seluruh program studi dan unit pendukung, disusul dengan Rapat Tinjauan Manajemen (RTM).'],
                        ['Apa yang harus dipersiapkan Program Studi menghadapi AMI?', 'Program Studi perlu memperbarui Dokumen Evaluasi Diri (DED), mengunggah bukti fisik ketercapaian standar SPMI, laporan kepuasan mahasiswa/dosen, serta menyiapkan tim prodi untuk wawancara visitasi auditor.'],
                        ['Apa yang dimaksud dengan Siklus PPEPP?', 'Siklus PPEPP adalah pilar utama dalam Sistem Penjaminan Mutu Internal (SPMI) di Unika Soegijapranata yang terdiri dari lima tahapan kerja terstruktur: <strong>Penetapan</strong> (merumuskan standar mutu melampaui SN Dikti), <strong>Pelaksanaan</strong> (menerapkan standar dalam aktivitas operasional), <strong>Evaluasi</strong> (mengukur ketercapaian dan mendeteksi penyimpangan secara dini), <strong>Pengendalian</strong> (analisis tindak lanjut dan tindakan korektif melalui Rapat Tinjauan Manajemen/RTM), serta <strong>Peningkatan</strong> (menaikkan indikator capaian mutu standar yang telah dipenuhi). Seluruh siklus ini dimonitor melalui <a href="https://sista.unika.ac.id" target="_blank">Portal SISTA UNIKA</a>.'],
                    ];
                    foreach ($faqs as $i => $faq):
                    ?>
                    <div class="accordion-item mb-3" style="border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;">
                        <h2 class="accordion-header">
                            <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq-<?= $i ?>" style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.95rem;">
                                <?= $faq[0] ?>
                            </button>
                        </h2>
                        <div id="faq-<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#accordionFaq">
                            <div class="accordion-body" style="font-size:0.88rem;color:var(--text-muted);line-height:1.75;">
                                <?= $faq[1] ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================
     3. GLOSARIUM MUTU (Scroll ke bawah)
============================================== -->
<section class="py-5" id="glosarium-section" style="background:#ffffff;border-bottom:1px solid var(--border);">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tag mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                Kamus Istilah
            </span>
            <h2 class="section-title">Glosarium Istilah Mutu</h2>
            <p class="section-desc mx-auto">Definisi istilah teknis yang lazim digunakan dalam pengelolaan mutu pendidikan tinggi.</p>
        </div>

        <div class="row g-4">
            <?php
            $terms = [
                ['SPMI', 'Sistem Penjaminan Mutu Internal: instrumen otonom perguruan tinggi untuk mengendalikan mutu secara berkelanjutan.'],
                ['PPEPP', 'Siklus Penjaminan Mutu: Penetapan, Pelaksanaan, Evaluasi, Pengendalian, dan Peningkatan.'],
                ['AMI', 'Audit Mutu Internal: proses pemeriksaan objektif dan berkala atas kesesuaian aktivitas unit terhadap standar.'],
                ['KTS', 'Ketidaksesuaian: kondisi di mana pelaksanaan di lapangan belum memenuhi tolok ukur standar mutu yang ditetapkan.'],
                ['RTL', 'Rencana Tindak Lanjut: perumusan langkah perbaikan dan target waktu pemenuhan atas temuan audit mutu.'],
                ['RTM', 'Rapat Tinjauan Manajemen: rapat formal pimpinan universitas untuk mengevaluasi efektivitas pelaksanaan SPMI.'],
            ];
            foreach ($terms as $t):
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="p-4 h-100" style="background:var(--bg-main);border-radius:var(--radius-md);border-left:4px solid var(--purple);">
                    <h5 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.1rem;margin-bottom:0.4rem;">
                        <?= $t[0] ?>
                    </h5>
                    <p style="font-size:0.83rem;color:var(--text-muted);line-height:1.6;margin:0;">
                        <?= $t[1] ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==============================================
     4. KALENDER MUTU RESMI (Scroll ke bawah)
============================================== -->
<section class="py-5 py-md-6" id="kalender-section" style="background:#F8FAFC;border-bottom:1px solid var(--border);">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tag mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                </svg>
                Jadwal &amp; Agenda Resmi
            </span>
            <h2 class="section-title">Kalender Mutu 2026–2027</h2>
            <p class="section-desc mx-auto">Jadwal pelaksanaan monitoring, evaluasi, AMI, dan pelaporan mutu Universitas Katolik Soegijapranata.</p>
        </div>

        <!-- Poster Header Banner -->
        <div class="mb-4" style="background:linear-gradient(135deg, #4A148C 0%, #6A1B9A 50%, #7B1FA2 100%);border-radius:var(--radius-lg);padding:1.75rem 2rem;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;box-shadow:0 8px 25px rgba(106,27,154,0.18);">
            <div>
                <span class="badge mb-2" style="background:#FBBF24;color:#4A148C;font-weight:800;font-size:0.75rem;padding:0.3rem 0.65rem;">TAHUN AKADEMIK 2026–2027</span>
                <h3 style="font-family:var(--font-heading);font-weight:900;font-size:1.35rem;color:#ffffff;margin:0;">
                    Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata
                </h3>
            </div>
            <a href="<?= SITE_URL ?>/kalender-mutu.php" class="btn-hero-primary" style="background:#FBBF24;color:#4A148C;border:none;padding:0.65rem 1.4rem;font-size:0.88rem;font-weight:800;text-decoration:none;">
                Lihat Kalender Lengkap &nearr;
            </a>
        </div>

        <!-- Monthly Grid (2 Columns) -->
        <div class="row g-3">
            <?php foreach ($kalender_list as $item): 
                $agenda_lines = array_filter(array_map('trim', explode("\n", $item['kegiatan'])));
                $parts = explode(' ', trim($item['bulan_tahun']), 2);
                $nama_bulan = $parts[0] ?? $item['bulan_tahun'];
                $tahun_str  = $parts[1] ?? '';
            ?>
            <div class="col-lg-6">
                <div class="p-3 p-md-4 h-100 d-flex align-items-start gap-3" style="background:#ffffff;border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:0 2px 10px rgba(10,25,47,0.03);transition:all 0.2s;">
                    <!-- Badge Bulan -->
                    <div style="min-width:110px;max-width:120px;text-align:center;background:#F8FAFC;border:1.5px solid rgba(106,27,154,0.18);border-radius:10px;padding:0.6rem 0.4rem;flex-shrink:0;">
                        <div style="font-family:var(--font-heading);font-weight:900;color:var(--navy);font-size:0.9rem;line-height:1.2;">
                            <?= htmlspecialchars($nama_bulan) ?>
                        </div>
                        <?php if ($tahun_str): ?>
                        <div style="font-family:var(--font-heading);font-weight:800;color:var(--purple);font-size:0.8rem;margin-top:2px;">
                            <?= htmlspecialchars($tahun_str) ?>
                        </div>
                        <?php endif; ?>
                        <div style="width:24px;height:24px;margin:5px auto 0;background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border-radius:50%;display:flex;align-items:center;justify-content:center;color:#fff;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="12" height="12">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                            </svg>
                        </div>
                    </div>

                    <!-- Agenda List -->
                    <div style="flex-grow:1;padding-top:0.2rem;">
                        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.45rem;">
                            <?php foreach ($agenda_lines as $act): ?>
                            <li style="display:flex;align-items:flex-start;gap:8px;font-size:0.85rem;color:var(--text-main);line-height:1.55;font-weight:500;">
                                <span style="color:var(--purple);font-size:1rem;line-height:1;margin-top:2px;">•</span>
                                <span><?= htmlspecialchars($act) ?></span>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==============================================
     5. BULETIN JAMUS – BOOKSHELF PREVIEW
============================================== -->
<?php
// Fetch up to 6 latest active buletin for preview
$buletin_preview = $db->query("SELECT * FROM buletin WHERE is_aktif = 1 ORDER BY tanggal_terbit DESC, id DESC LIMIT 6")->fetchAll();
$buletin_total   = (int)$db->query("SELECT COUNT(*) FROM buletin WHERE is_aktif = 1")->fetchColumn();

// Color palette for placeholder covers
$cover_palette = [
    ['bg' => '#4A148C', 'stripe' => '#7B1FA2', 'accent' => '#CE93D8'],
    ['bg' => '#0D47A1', 'stripe' => '#1565C0', 'accent' => '#90CAF9'],
    ['bg' => '#1B5E20', 'stripe' => '#2E7D32', 'accent' => '#A5D6A7'],
    ['bg' => '#B71C1C', 'stripe' => '#C62828', 'accent' => '#EF9A9A'],
    ['bg' => '#E65100', 'stripe' => '#BF360C', 'accent' => '#FFCC80'],
    ['bg' => '#006064', 'stripe' => '#00838F', 'accent' => '#80DEEA'],
];
?>
<section class="py-5 py-md-6" id="buletin-section"
         style="background:linear-gradient(180deg, #F8FAFC 0%, #EEEFF4 100%);">
    <div class="container">

        <!-- Section header -->
        <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
            <div>
                <span class="section-tag mb-2" style="display:inline-flex;">Publikasi Berkala</span>
                <h2 class="section-title" style="margin-bottom:0.4rem;">Buletin JAMUS</h2>
                <p style="color:var(--text-muted);font-size:0.9rem;max-width:520px;margin:0;line-height:1.65;">
                    Terbitan berkala Lembaga Penjaminan Mutu: perkembangan SPMI, best practice, laporan AMI, dan artikel opini dari civitas UNIKA.
                </p>
            </div>
            <a href="<?= SITE_URL ?>/buletin.php"
               class="btn-hero-primary"
               style="white-space:nowrap;text-decoration:none;background:var(--purple);border:none;padding:0.65rem 1.4rem;font-size:0.88rem;flex-shrink:0;">
                <?= $buletin_total > 0 ? 'Lihat Semua ' . $buletin_total . ' Edisi →' : 'Kunjungi Rak Buletin →' ?>
            </a>
        </div>

        <?php if (empty($buletin_preview)): ?>
        <!-- Empty State -->
        <div class="p-5 text-center" style="background:#fff;border:2px dashed var(--border);border-radius:var(--radius-xl);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="var(--text-muted)" width="56" height="56" class="mb-3" style="display:block;margin:0 auto;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
            </svg>
            <h6 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:0.5rem;">Belum Ada Edisi Tersedia</h6>
            <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Admin LPM belum mengunggah edisi Buletin JAMUS. Periksa kembali nanti.</p>
        </div>

        <?php else: ?>

        <!-- Mini Bookshelf Rack -->
        <style>
        .kc-shelf-rack { position:relative; padding-bottom:24px; }
        .kc-shelf-rack::after {
            content:'';
            position:absolute; bottom:0; left:-8px; right:-8px;
            height:20px;
            background:linear-gradient(180deg,#8B6F47 0%,#6B4F2B 55%,#4A3520 100%);
            border-radius:0 0 6px 6px;
            box-shadow:0 5px 14px rgba(74,53,32,0.3);
        }
        .kc-book {
            transition:transform 0.25s cubic-bezier(0.175,0.885,0.32,1.275), box-shadow 0.25s;
            display:block; text-decoration:none;
        }
        .kc-book:hover { transform:translateY(-14px) scale(1.04); }
        .kc-cover {
            position:relative;
            border-radius:2px 6px 6px 2px;
            overflow:hidden;
            aspect-ratio:3/4;
            box-shadow:-2px 0 0 #bbb, -4px 0 0 #aaa, 3px 5px 16px rgba(0,0,0,0.28), 6px 10px 24px rgba(0,0,0,0.12);
        }
        .kc-cover::before {
            content:''; position:absolute; top:0; left:0;
            width:10px; height:100%; background:rgba(0,0,0,0.22); z-index:2;
        }
        .kc-cover::after {
            content:''; position:absolute; top:0; left:8%;
            width:28%; height:100%;
            background:linear-gradient(105deg,rgba(255,255,255,0.26) 0%,rgba(255,255,255,0) 80%);
            z-index:3; pointer-events:none;
        }
        .kc-cover img { width:100%; height:100%; object-fit:cover; display:block; }
        .kc-placeholder {
            width:100%; height:100%;
            display:flex; flex-direction:column;
            align-items:center; justify-content:center;
            padding:10px 6px; position:relative; overflow:hidden;
        }
        .kc-placeholder .kc-stripe {
            position:absolute; bottom:0; left:0; right:0;
            height:36%; opacity:.22;
        }
        .kc-placeholder .kc-label {
            font-family:var(--font-heading); font-weight:900;
            font-size:clamp(0.75rem,1.5vw,1rem); color:#fff;
            text-align:center; line-height:1.15; z-index:1;
            text-shadow:0 2px 6px rgba(0,0,0,0.4);
        }
        .kc-placeholder .kc-tag {
            background:rgba(255,255,255,0.18); border:1px solid rgba(255,255,255,0.38);
            color:#fff; font-size:0.6rem; font-weight:700;
            padding:2px 7px; border-radius:20px; z-index:1; margin-top:6px;
            max-width:90%; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;
            text-align:center;
        }
        .kc-dl {
            position:absolute; inset:0;
            background:linear-gradient(to top,rgba(10,25,47,0.82) 0%,transparent 55%);
            display:flex; align-items:flex-end; justify-content:center;
            padding-bottom:10px; opacity:0;
            transition:opacity 0.2s; z-index:10; border-radius:0 6px 6px 0;
        }
        .kc-book:hover .kc-dl { opacity:1; }
        .kc-dl-btn {
            background:#FBBF24; color:#1a1a1a;
            font-weight:800; font-size:0.65rem;
            padding:4px 10px; border-radius:20px;
            display:flex; align-items:center; gap:4px;
        }
        .kc-meta { padding:8px 2px 0; text-align:center; }
        .kc-meta-title {
            font-family:var(--font-heading); font-weight:700;
            font-size:0.75rem; color:var(--navy); line-height:1.3;
            display:-webkit-box; -webkit-line-clamp:2;
            -webkit-box-orient:vertical; overflow:hidden;
            margin-bottom:3px;
        }
        .kc-meta-edisi { font-size:0.68rem; color:var(--purple); font-weight:700; }
        </style>

        <div class="kc-shelf-rack px-2">
            <div class="row row-cols-3 row-cols-sm-4 row-cols-md-5 row-cols-lg-6 g-3">
                <?php foreach ($buletin_preview as $b):
                    $pal      = $cover_palette[$b['id'] % count($cover_palette)];
                    $has_cov  = !empty($b['cover_path']);
                    $pdf_url  = SITE_URL . '/uploads/buletin/' . $b['file_path'];
                    $cov_url  = $has_cov ? SITE_URL . '/uploads/buletin/covers/' . $b['cover_path'] : null;
                ?>
                <div class="col">
                    <a href="<?= $pdf_url ?>" target="_blank" class="kc-book"
                       title="<?= e($b['judul']) ?> – <?= e($b['edisi']) ?>">
                        <div class="kc-cover">
                            <?php if ($has_cov): ?>
                                <img src="<?= $cov_url ?>" alt="<?= e($b['edisi']) ?>" loading="lazy">
                            <?php else: ?>
                                <div class="kc-placeholder"
                                     style="background:linear-gradient(160deg,<?= $pal['bg'] ?> 0%,<?= $pal['stripe'] ?> 100%);">
                                    <div class="kc-stripe"
                                         style="background:repeating-linear-gradient(-45deg,<?= $pal['accent'] ?> 0,<?= $pal['accent'] ?> 2px,transparent 2px,transparent 12px);"></div>
                                    <div class="kc-label">JAMUS</div>
                                    <div class="kc-tag"><?= e($b['edisi']) ?></div>
                                </div>
                            <?php endif; ?>
                            <div class="kc-dl">
                                <div class="kc-dl-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="10" height="10">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3"/>
                                    </svg>
                                    Buka
                                </div>
                            </div>
                        </div>
                        <div class="kc-meta">
                            <div class="kc-meta-title"><?= e($b['judul']) ?></div>
                            <span class="kc-meta-edisi"><?= e($b['edisi']) ?></span>
                        </div>
                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if ($buletin_total > 6): ?>
        <div class="text-center mt-4">
            <a href="<?= SITE_URL ?>/buletin.php" style="font-size:0.85rem;color:var(--purple);font-weight:700;text-decoration:none;">
                + <?= $buletin_total - 6 ?> edisi lainnya tersedia →
            </a>
        </div>
        <?php endif; ?>

        <?php endif; ?>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>

