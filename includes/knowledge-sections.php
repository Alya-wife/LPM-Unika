<?php
/**
 * Modular Sections untuk Halaman Knowledge Center
 * Terintegrasi dengan Visual Page Builder (advance-setting.php) dan knowledge.php publik.
 */

function getKnowledgeData($periode_kalender = '') {
    static $cache = null;
    if ($cache !== null && empty($periode_kalender)) return $cache;

    $db = getDB();
    $kalender_periode_aktif = getPengaturan('kalender_periode_aktif', '2026/2027');
    $selected_kalender_periode = $periode_kalender ?: ($kalender_periode_aktif);

    $stmt_kal = $db->prepare("SELECT * FROM kalender_mutu WHERE is_active = 1 AND tahun_akademik = ? ORDER BY urutan ASC, id ASC");
    $stmt_kal->execute([$selected_kalender_periode]);
    $kalender_list = $stmt_kal->fetchAll();
    if (empty($kalender_list)) {
        $kalender_list = $db->query("SELECT * FROM kalender_mutu WHERE is_active = 1 ORDER BY urutan ASC, id ASC")->fetchAll();
    }

    $all_years_kc = $db->query("SELECT DISTINCT YEAR(tanggal_terbit) as thn FROM buletin WHERE is_aktif = 1 AND tanggal_terbit IS NOT NULL AND tanggal_terbit > '1970-01-01' ORDER BY thn DESC")->fetchAll(PDO::FETCH_COLUMN);
    $all_buletin_kc = $db->query("SELECT *, YEAR(tanggal_terbit) as thn FROM buletin WHERE is_aktif = 1 ORDER BY tanggal_terbit DESC, id DESC")->fetchAll();

    $data = [
        'selected_kalender_periode' => $selected_kalender_periode,
        'kalender_list'             => $kalender_list,
        'all_years_kc'              => $all_years_kc,
        'all_buletin_kc'            => $all_buletin_kc,
    ];

    if (empty($periode_kalender)) $cache = $data;
    return $data;
}

function renderKnowledgeSection($type, $block = [], $is_builder = false) {
    $data = getKnowledgeData();
    $bg   = !empty($block['bg_color']) ? "background-color: {$block['bg_color']} !important;" : "";
    $tc   = !empty($block['text_color']) ? "color: {$block['text_color']} !important;" : "";

    switch ($type) {
        case 'knowledge_pengantar':
            ?>
            <section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="row g-5 align-items-center">
                        <div class="col-lg-7">
                            <span class="section-tag mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                                </svg>
                                <?= htmlspecialchars($block['badge'] ?? 'Pusat Edukasi Mutu') ?>
                            </span>
                            <h2 class="section-title mb-3"><?= htmlspecialchars($block['title'] ?? 'Pustaka & Pengetahuan Penjaminan Mutu') ?></h2>
                            <p style="color:var(--text-main);line-height:1.8;font-size:1rem;margin-bottom:1.25rem;">
                                <?= nl2br(htmlspecialchars($block['subtitle'] ?? 'Knowledge Center LPM UNIKA adalah ruang referensi digital yang disediakan bagi seluruh dosen, tenaga kependidikan, auditor, dan mahasiswa untuk memahami prinsip, siklus, regulasi, dan istilah teknis Sistem Penjaminan Mutu Internal.')) ?>
                            </p>
                            <div class="d-flex gap-3 flex-wrap">
                                <a href="#faq-section" class="btn-hero-primary" style="background:var(--navy);border:none;padding:0.65rem 1.4rem;font-size:0.88rem;text-decoration:none;">
                                    Lihat FAQ Mutu &darr;
                                </a>
                                <a href="#kalender-section" class="btn-hero-secondary" style="background:#fff;border:1.5px solid var(--border);color:var(--navy);padding:0.65rem 1.4rem;font-size:0.88rem;text-decoration:none;">
                                    Kalender Mutu &darr;
                                </a>
                            </div>
                        </div>

                        <div class="col-lg-5">
                            <div class="card-lpm p-4" style="background:var(--bg-main);border-radius:var(--radius-lg);border-left:5px solid var(--purple);box-shadow:0 4px 15px rgba(0,0,0,0.04);">
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
            <?php
            break;

        case 'knowledge_kalender':
            $selected_kalender_periode = $data['selected_kalender_periode'];
            $kalender_list = $data['kalender_list'];
            ?>
            <section class="py-5" id="kalender-section" style="background:#F8FAFC;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="section-tag mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                            </svg>
                            <?= htmlspecialchars($block['badge'] ?? 'Jadwal & Agenda Resmi') ?>
                        </span>
                        <h2 class="section-title"><?= htmlspecialchars($block['title'] ?? ('Kalender Mutu Periode ' . $selected_kalender_periode)) ?></h2>
                        <p class="section-desc mx-auto"><?= htmlspecialchars($block['subtitle'] ?? 'Jadwal pelaksanaan monitoring, evaluasi, AMI, dan pelaporan mutu Universitas Katolik Soegijapranata.') ?></p>
                    </div>

                    <!-- Poster Header Banner -->
                    <div class="mb-4" style="background:linear-gradient(135deg, #4A148C 0%, #6A1B9A 50%, #7B1FA2 100%);border-radius:var(--radius-lg);padding:1.75rem 2rem;color:#fff;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;box-shadow:0 8px 25px rgba(106,27,154,0.18);">
                        <div>
                            <span class="badge mb-2" style="background:#FBBF24;color:#4A148C;font-weight:800;font-size:0.75rem;padding:0.3rem 0.65rem;">TAHUN AKADEMIK <?= htmlspecialchars($selected_kalender_periode) ?></span>
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
                            <div class="p-3 p-md-4 h-100 d-flex align-items-start gap-3" style="background:#ffffff;border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:0 2px 10px rgba(10,25,47,0.03);">
                                <div style="min-width:110px;max-width:120px;text-align:center;background:#F8FAFC;border:1.5px solid rgba(106,27,154,0.18);border-radius:10px;padding:0.6rem 0.4rem;flex-shrink:0;">
                                    <div style="font-family:var(--font-heading);font-weight:900;color:var(--navy);font-size:0.9rem;line-height:1.2;">
                                        <?= htmlspecialchars($nama_bulan) ?>
                                    </div>
                                    <?php if ($tahun_str): ?>
                                    <div style="font-family:var(--font-heading);font-weight:800;color:var(--purple);font-size:0.8rem;margin-top:2px;">
                                        <?= htmlspecialchars($tahun_str) ?>
                                    </div>
                                    <?php endif; ?>
                                </div>
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
            <?php
            break;

        case 'knowledge_buletin':
            $all_years_kc   = $data['all_years_kc'] ?? [];
            $all_buletin_kc = $data['all_buletin_kc'] ?? [];
            if (empty($all_buletin_kc)) {
                global $db;
                if ($db) {
                    $all_buletin_kc = $db->query("SELECT *, YEAR(tanggal_terbit) as thn FROM buletin WHERE is_aktif = 1 ORDER BY tanggal_terbit DESC, id DESC")->fetchAll();
                }
            }
            $buletin_total  = count($all_buletin_kc);
            $cover_palette = [
                ['bg' => '#4A148C', 'stripe' => '#7B1FA2', 'accent' => '#CE93D8'],
                ['bg' => '#0D47A1', 'stripe' => '#1565C0', 'accent' => '#90CAF9'],
                ['bg' => '#1B5E20', 'stripe' => '#2E7D32', 'accent' => '#A5D6A7'],
                ['bg' => '#B71C1C', 'stripe' => '#C62828', 'accent' => '#EF9A9A'],
                ['bg' => '#E65100', 'stripe' => '#BF360C', 'accent' => '#FFCC80'],
                ['bg' => '#006064', 'stripe' => '#00838F', 'accent' => '#80DEEA'],
            ];
            ?>
            <section class="py-5" id="buletin-section" style="background:linear-gradient(180deg, #F8FAFC 0%, #EEEFF4 100%);border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="d-flex justify-content-between align-items-end flex-wrap gap-3 mb-4">
                        <div>
                            <span class="section-tag mb-2" style="display:inline-flex;"><?= htmlspecialchars($block['badge'] ?? 'Publikasi Berkala') ?></span>
                            <h2 class="section-title" id="buletinSectionTitle" style="margin-bottom:0.4rem;"><?= htmlspecialchars($block['title'] ?? 'Buletin JAMUS') ?></h2>
                            <p style="color:var(--text-muted);font-size:0.9rem;max-width:560px;margin:0;line-height:1.65;">
                                <?= htmlspecialchars($block['subtitle'] ?? 'Koleksi terbitan berkala penjaminan mutu: perkembangan SPMI, best practice prodi, laporan AMI, dan artikel opini.') ?>
                            </p>
                        </div>
                        <a href="<?= SITE_URL ?>/buletin.php" class="btn-hero-primary" style="white-space:nowrap;text-decoration:none;background:var(--purple);border:none;padding:0.65rem 1.4rem;font-size:0.88rem;flex-shrink:0;">
                            Buka Arsip Lengkap &rarr;
                        </a>
                    </div>

                    <!-- Lemari Rak Buku Buletin JAMUS 3D -->
                    <div class="shelf-books-container mt-4">
                        <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3 g-md-4">
                            <?php 
                            $counter = 0;
                            foreach ($all_buletin_kc as $b): 
                                if ($counter >= 6) break;
                                $cp = $cover_palette[$counter % count($cover_palette)];
                                $counter++;
                                $cov_file   = function_exists('getOrGenerateBuletinCover') ? getOrGenerateBuletinCover($b['cover_path'] ?? null, $b['file_path'] ?? null, (int)($b['id'] ?? 0)) : '';
                                $has_cover  = !empty($cov_file);
                                $cover_url  = $has_cover ? SITE_URL . '/uploads/buletin/covers/' . $cov_file : null;
                                $pdf_file   = !empty($b['file_path']) ? $b['file_path'] : (!empty($b['file_pdf']) ? $b['file_pdf'] : '');
                                $pdf_path   = $pdf_file ? SITE_URL . '/uploads/buletin/' . rawurlencode($pdf_file) : SITE_URL . '/buletin.php';
                            ?>
                            <div class="col" data-year="<?= $b['thn'] ?? '' ?>">
                                <a href="<?= $pdf_path ?>" target="_blank" class="book-card" title="<?= htmlspecialchars($b['judul'] . ' – ' . ($b['edisi'] ?? '')) ?>">
                                    <!-- Badan Buku 3D -->
                                    <div class="book-spine-shell">
                                        <?php if ($has_cover): ?>
                                            <img src="<?= $cover_url ?>" alt="Cover <?= htmlspecialchars($b['edisi'] ?? $b['judul']) ?>" class="book-cover-img" loading="lazy">
                                        <?php else: ?>
                                            <div class="book-placeholder-cover" style="background:linear-gradient(160deg, <?= $cp['bg'] ?> 0%, <?= $cp['stripe'] ?> 100%);">
                                                <div class="cover-deco-stripe" style="background:repeating-linear-gradient(-45deg, <?= $cp['accent'] ?> 0px, <?= $cp['accent'] ?> 2px, transparent 2px, transparent 14px);"></div>
                                                <div class="cover-logo-text">JAMUS</div>
                                                <div class="cover-edisi-badge"><?= htmlspecialchars($b['edisi'] ?? ('JAMUS ' . ($b['thn'] ?? ''))) ?></div>
                                            </div>
                                        <?php endif; ?>

                                        <!-- Overlay Tombol Buka PDF Saat Hover -->
                                        <div class="book-download-overlay">
                                            <div class="book-download-btn">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="12" height="12">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                                </svg>
                                                Buka PDF
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Bayangan Kontak di Atas Kayu -->
                                    <div class="book-shelf-shadow"></div>

                                    <!-- Informasi Judul & Edisi di Bawah Buku -->
                                    <div class="book-info-block">
                                        <div class="book-title-text"><?= htmlspecialchars($b['judul']) ?></div>
                                        <?php if (!empty($b['edisi'])): ?>
                                        <span class="book-edisi-tag"><?= htmlspecialchars($b['edisi']) ?></span>
                                        <?php endif; ?>
                                        <?php if (!empty($b['tanggal_terbit'])): ?>
                                        <div class="book-date-tag">
                                            <?= date('M Y', strtotime($b['tanggal_terbit'])) ?><?= !empty($b['periode_akademik']) ? ' • ' . htmlspecialchars($b['periode_akademik']) : '' ?>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Ambalan Kayu Fisik 3D Solid -->
                        <div class="hardwood-shelf-plank">
                            <div class="plank-surface-top"></div>
                            <div class="plank-edge-front">
                                <span class="plank-engraving">LEMBAGA PENJAMINAN MUTU • UNIKA SOEGIJAPRANATA</span>
                            </div>
                            <div class="plank-drop-shadow"></div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'knowledge_glosarium':
            $terms = [
                ['SPMI', 'Sistem Penjaminan Mutu Internal: instrumen otonom perguruan tinggi untuk mengendalikan mutu secara berkelanjutan.'],
                ['PPEPP', 'Siklus Penjaminan Mutu: Penetapan, Pelaksanaan, Evaluasi, Pengendalian, dan Peningkatan.'],
                ['AMI', 'Audit Mutu Internal: proses pemeriksaan objektif dan berkala atas kesesuaian aktivitas unit terhadap standar.'],
                ['KTS', 'Ketidaksesuaian: kondisi di mana pelaksanaan di lapangan belum memenuhi tolok ukur standar mutu yang ditetapkan.'],
                ['RTL', 'Rencana Tindak Lanjut: perumusan langkah perbaikan dan target waktu pemenuhan atas temuan audit mutu.'],
                ['RTM', 'Rapat Tinjauan Manajemen: rapat formal pimpinan universitas untuk mengevaluasi efektivitas pelaksanaan SPMI.'],
            ];
            ?>
            <section class="py-5" id="glosarium-section" style="background:#ffffff;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="section-tag mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                            </svg>
                            <?= htmlspecialchars($block['badge'] ?? 'Kamus Istilah') ?>
                        </span>
                        <h2 class="section-title"><?= htmlspecialchars($block['title'] ?? 'Glosarium Istilah Mutu') ?></h2>
                        <p class="section-desc mx-auto"><?= htmlspecialchars($block['subtitle'] ?? 'Definisi istilah teknis yang lazim digunakan dalam pengelolaan mutu pendidikan tinggi.') ?></p>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($terms as $t): ?>
                        <div class="col-md-6 col-lg-4">
                            <div class="p-4 h-100" style="background:var(--bg-main);border-radius:var(--radius-md);border-left:4px solid var(--purple);">
                                <h5 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.1rem;margin-bottom:0.4rem;">
                                    <?= htmlspecialchars($t[0]) ?>
                                </h5>
                                <p style="font-size:0.83rem;color:var(--text-muted);line-height:1.6;margin:0;">
                                    <?= htmlspecialchars($t[1]) ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'knowledge_faq':
            try {
                $db_faq = getDB();
                $faqs_db = $db_faq->query("SELECT pertanyaan, jawaban, kategori FROM faqs WHERE is_active = 1 ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
            } catch (Exception $e) {
                $faqs_db = [];
            }
            if (!empty($faqs_db)) {
                $faqs = array_map(function($f) {
                    return [$f['pertanyaan'], $f['jawaban'], $f['kategori'] ?? 'Umum'];
                }, $faqs_db);
            } else {
                $faqs = [
                    ['Apa perbedaan mendasar antara SPMI dan SPME (Akreditasi)?', 'SPMI (Sistem Penjaminan Mutu Internal) dijalankan secara mandiri oleh internal perguruan tinggi melalui siklus PPEPP. Sedangkan SPME (Sistem Penjaminan Mutu Eksternal) adalah evaluasi dan pengakuan yang dilakukan oleh pihak eksternal independen seperti BAN-PT dan LAM.', 'SPMI & PPEPP'],
                    ['Kapan Audit Mutu Internal (AMI) dilaksanakan?', 'AMI di lingkungan UNIKA dilaksanakan secara berkala 1 (satu) kali setiap tahun akademik untuk seluruh program studi dan unit pendukung, disusul dengan Rapat Tinjauan Manajemen (RTM).', 'AMI'],
                    ['Apa yang harus dipersiapkan Program Studi menghadapi AMI?', 'Program Studi perlu memperbarui Dokumen Evaluasi Diri (DED), mengunggah bukti fisik ketercapaian standar SPMI, laporan kepuasan mahasiswa/dosen, serta menyiapkan tim prodi untuk wawancara visitasi auditor.', 'AMI'],
                    ['Apa yang dimaksud dengan Siklus PPEPP?', 'Siklus PPEPP adalah pilar utama dalam Sistem Penjaminan Mutu Internal (SPMI) di Unika Soegijapranata yang terdiri dari lima tahapan kerja terstruktur: Penetapan, Pelaksanaan, Evaluasi, Pengendalian, dan Peningkatan.', 'SPMI & PPEPP'],
                ];
            }
            ?>
            <section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>" id="faq-section">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="section-tag mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                            </svg>
                            <?= htmlspecialchars($block['badge'] ?? 'Pertanyaan Populer') ?>
                        </span>
                        <h2 class="section-title"><?= htmlspecialchars($block['title'] ?? 'Tanya Jawab (FAQ) Mutu') ?></h2>
                        <p class="section-desc mx-auto"><?= htmlspecialchars($block['subtitle'] ?? 'Pertanyaan yang sering diajukan terkait SPMI, AMI, dan Akreditasi.') ?></p>
                    </div>

                    <div class="row justify-content-center">
                        <div class="col-lg-9">
                            <div class="accordion" id="accordionFaq">
                                <?php foreach ($faqs as $i => $faq): ?>
                                <div class="accordion-item mb-3" style="border:1px solid var(--border);border-radius:var(--radius-sm);overflow:hidden;">
                                    <h2 class="accordion-header">
                                        <button class="accordion-button <?= $i > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq-<?= $i ?>" style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.95rem;">
                                            <?= htmlspecialchars($faq[0]) ?>
                                        </button>
                                    </h2>
                                    <div id="faq-<?= $i ?>" class="accordion-collapse collapse <?= $i === 0 ? 'show' : '' ?>" data-bs-parent="#accordionFaq">
                                        <div class="accordion-body" style="font-size:0.88rem;color:var(--text-muted);line-height:1.75;">
                                            <?= htmlspecialchars($faq[1]) ?>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;
    }
}
