<?php
/**
 * Modular Beranda Sections Renderer
 * Digunakan bersama oleh index.php (halaman publik) dan admin/builder-preview.php (visual builder)
 */
require_once __DIR__ . '/../config/database.php';

function getBerandaData() {
    static $data = null;
    if ($data !== null) return $data;

    $db = getDB();
    $data = [];

    // 1. Hero Slides
    $data['hero_slides'] = $db->query("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY urutan ASC, id ASC")->fetchAll();

    // 2. Berita terbaru
    $data['berita_terbaru'] = $db->query("SELECT * FROM berita ORDER BY tanggal_publikasi DESC LIMIT 3")->fetchAll();

    // 3. Penghargaan
    $data['penghargaan_list'] = [];
    try {
        $data['penghargaan_list'] = $db->query("SELECT * FROM penghargaan ORDER BY tahun DESC, id DESC LIMIT 8")->fetchAll(PDO::FETCH_OBJ);
    } catch (Exception $e) {}
    $data['layout_penghargaan'] = getPengaturan('layout_penghargaan', 'slider');

    // 4. Statistik
    $jml_dokumen = (int)$db->query("SELECT COUNT(*) FROM dokumen")->fetchColumn();
    $stat_dokumen_custom = getPengaturan('stat_dokumen', '');
    if ($stat_dokumen_custom !== '' && is_numeric($stat_dokumen_custom)) {
        $jml_dokumen = (int)$stat_dokumen_custom;
    }

    // Program Studi
    $actual_prodi_count = (int)$db->query("SELECT COUNT(*) FROM akreditasi_prodi")->fetchColumn();
    $stat_prodi_custom  = getPengaturan('stat_prodi', '');
    if ($stat_prodi_custom !== '' && is_numeric($stat_prodi_custom)) {
        $jml_prodi = (int)$stat_prodi_custom;
    } else {
        $jml_prodi = $actual_prodi_count;
    }

    // Persentase Akreditasi
    $count_terakreditasi = (int)$db->query("SELECT COUNT(*) FROM akreditasi_prodi WHERE peringkat IS NOT NULL AND TRIM(peringkat) != '' AND LOWER(peringkat) NOT LIKE '%belum%'")->fetchColumn();
    if ($actual_prodi_count > 0) {
        $actual_akred_pct = ($count_terakreditasi >= $actual_prodi_count) ? 100 : round(($count_terakreditasi / $actual_prodi_count) * 100);
    } else {
        $actual_akred_pct = 100;
    }
    $stat_akr_custom = getPengaturan('stat_akreditasi_persen', '');
    if ($stat_akr_custom === '') {
        $old_akr = getPengaturan('stat_akreditasi', '');
        if (is_numeric($old_akr)) {
            $stat_akr_custom = $old_akr;
        }
    }
    if ($stat_akr_custom !== '' && is_numeric($stat_akr_custom)) {
        $jml_akr = (int)$stat_akr_custom;
    } else {
        $jml_akr = $actual_akred_pct;
    }

    $data['stat_tahun']  = getPengaturan('stat_tahun', '40');
    $data['stat_prodi']  = $jml_prodi;
    $data['stat_akr']    = $jml_akr;
    $data['jml_dokumen'] = $jml_dokumen;

    // 5. Akreditasi
    $data['akred_file']      = getPengaturan('akred_institusi_file', file_exists(__DIR__ . '/../uploads/akreditasi/2023_Sertifikat_Akreditasi_ISK_AIPT_UNIKA_Unggul.pdf') ? '2023_Sertifikat_Akreditasi_ISK_AIPT_UNIKA_Unggul.pdf' : '');
    $data['akred_peringkat'] = getPengaturan('akred_institusi_peringkat', 'UNGGUL');
    $data['akred_sk']        = getPengaturan('akred_institusi_sk', 'Nomor SK: -');
    $data['akred_teks']      = getPengaturan('akred_institusi_teks', 'Universitas kami terus berkomitmen untuk memberikan standar pendidikan terbaik sesuai dengan pedoman Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT).');
    $data['akred_badge']     = getPengaturan('akred_institusi_badge', 'Akreditasi Institusi');
    $data['akred_judul']     = getPengaturan('akred_institusi_judul', "Capaian Mutu\nUniversitas Katolik Soegijapranata");

    return $data;
}

function renderBerandaSection($type, $block = [], $is_builder = false) {
    $data = getBerandaData();
    $bg   = !empty($block['bg_color']) ? "background-color: {$block['bg_color']} !important;" : "";
    $tc   = !empty($block['text_color']) ? "color: {$block['text_color']} !important;" : "";
    $pad_class = ($block['padding'] ?? 'normal') === 'large' ? 'py-6' : (($block['padding'] ?? 'normal') === 'compact' ? 'py-3' : 'py-5');

    switch ($type) {
        case 'beranda_hero':
        case 'hero':
            $hero_slides = $data['hero_slides'];
            ?>
            <section class="hero-carousel-section" style="<?= $bg ?>">
                <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-pause="false">
                    <?php if (!empty($hero_slides)): ?>
                        <div class="carousel-indicators">
                            <?php foreach ($hero_slides as $idx => $s): ?>
                                <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $idx ?>"
                                    class="<?= $idx === 0 ? 'active' : '' ?>" aria-current="<?= $idx === 0 ? 'true' : 'false' ?>"
                                    aria-label="Slide <?= $idx + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="carousel-inner">
                            <?php foreach ($hero_slides as $idx => $s): ?>
                                <?php
                                $bg_img = '';
                                if (filter_var($s['gambar'], FILTER_VALIDATE_URL)) {
                                    $bg_img = $s['gambar'];
                                } elseif ($s['gambar'] && file_exists(__DIR__ . '/../uploads/slides/' . $s['gambar'])) {
                                    $bg_img = SITE_URL . '/uploads/slides/' . $s['gambar'];
                                }
                                $style_bg = $bg_img ? "background-image: url('{$bg_img}');" : "background: var(--navy);";
                                $judul_display = e($s['judul']);
                                if ($s['highlight_text']) {
                                    $hl = e($s['highlight_text']);
                                    $judul_display = str_ireplace($hl, '<span class="highlight-navy">' . $hl . '</span>', $judul_display);
                                }
                                ?>
                                <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>" style="<?= $style_bg ?>">
                                    <div class="carousel-overlay"></div>
                                    <div class="container h-100 position-relative z-index-2">
                                        <div class="row h-100 align-items-center">
                                            <div class="col-lg-8">
                                                <?php if ($s['subjudul']): ?>
                                                    <div class="hero-badge animate-fadeInUp">
                                                        <span class="hero-badge-dot"></span>
                                                        <?= e($s['subjudul']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                <h1 class="hero-title animate-fadeInUp animate-delay-100">
                                                    <?= $judul_display ?>
                                                </h1>
                                                <?php if ($s['deskripsi']): ?>
                                                    <p class="hero-desc animate-fadeInUp animate-delay-200">
                                                        <?= e($s['deskripsi']) ?>
                                                    </p>
                                                <?php endif; ?>
                                                <div class="hero-actions animate-fadeInUp animate-delay-300">
                                                    <?php if ($s['btn_text']): ?>
                                                        <a href="<?= e($s['btn_link'] ?: '#') ?>" class="btn-hero-primary">
                                                            <?= e($s['btn_text']) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($s['btn_secondary_text']): ?>
                                                        <a href="<?= e($s['btn_secondary_link'] ?: '#') ?>" class="btn-hero-secondary">
                                                            <?= e($s['btn_secondary_text']) ?>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        <?php if (count($hero_slides) > 1): ?>
                            <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
                                <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Previous</span>
                            </button>
                            <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
                                <span class="carousel-control-next-icon" aria-hidden="true"></span>
                                <span class="visually-hidden">Next</span>
                            </button>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="carousel-inner">
                            <div class="carousel-item active" style="background: var(--navy); min-height: 480px; display:flex; align-items:center;">
                                <div class="container text-white py-5">
                                    <div class="hero-badge mb-3"><span class="hero-badge-dot"></span>LPM UNIKA</div>
                                    <h1 class="hero-title">Lembaga Penjaminan Mutu UNIKA</h1>
                                    <p class="hero-desc">Berkomitmen mewujudkan mutu pendidikan tinggi yang unggul dan berkelanjutan.</p>
                                    <a href="spmi.php" class="btn-hero-primary">Akses Dokumen SPMI</a>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            <?php
            break;

        case 'beranda_quick_links':
            ?>
            <section class="<?= $pad_class ?>" style="<?= $bg ?: 'background:#FFFFFF;' ?>">
                <div class="container">
                    <div class="row g-3 justify-content-center">
                        <div class="col-6 col-md-4 col-lg-2">
                            <a href="spmi.php?kategori=Kebijakan" class="quick-link-card text-decoration-none">
                                <div class="quick-link-icon" style="background: linear-gradient(135deg, #EDE7F6, #D1C4E9);">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#4A148C" width="26" height="26">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                                    </svg>
                                </div>
                                <span class="quick-link-label" style="<?= $tc ?>">Kebijakan Mutu</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <a href="spmi.php?kategori=Manual" class="quick-link-card text-decoration-none">
                                <div class="quick-link-icon" style="background: linear-gradient(135deg, #E3F2FD, #BBDEFB);">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#1565C0" width="26" height="26">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                                    </svg>
                                </div>
                                <span class="quick-link-label" style="<?= $tc ?>">Manual Mutu</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <a href="spmi.php?kategori=Standar" class="quick-link-card text-decoration-none">
                                <div class="quick-link-icon" style="background: linear-gradient(135deg, #E8F5E9, #C8E6C9);">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2E7D32" width="26" height="26">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                                    </svg>
                                </div>
                                <span class="quick-link-label" style="<?= $tc ?>">Standar Mutu</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <a href="spmi.php?kategori=Formulir" class="quick-link-card text-decoration-none">
                                <div class="quick-link-icon" style="background: linear-gradient(135deg, #FFF8E1, #FFE082);">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#E65100" width="26" height="26">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                    </svg>
                                </div>
                                <span class="quick-link-label" style="<?= $tc ?>">Formulir Mutu</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <a href="akreditasi.php" class="quick-link-card text-decoration-none">
                                <div class="quick-link-icon" style="background: linear-gradient(135deg, #FCE4EC, #F8BBD9);">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#AD1457" width="26" height="26">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                    </svg>
                                </div>
                                <span class="quick-link-label" style="<?= $tc ?>">Akreditasi</span>
                            </a>
                        </div>
                        <div class="col-6 col-md-4 col-lg-2">
                            <a href="kontak.php" class="quick-link-card text-decoration-none">
                                <div class="quick-link-icon" style="background: linear-gradient(135deg, #E0F7FA, #B2EBF2);">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#00838F" width="26" height="26">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                    </svg>
                                </div>
                                <span class="quick-link-label" style="<?= $tc ?>">Hubungi Kami</span>
                            </a>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'beranda_stats':
            ?>
            <div class="stats-bar" style="<?= $bg ?>">
                <div class="container">
                    <div class="row g-0">
                        <div class="col-6 col-md-3">
                            <div class="stat-item">
                                <div class="stat-num" style="<?= $tc ?>" data-count="<?= (int)$data['stat_tahun'] ?>"><?= (int)$data['stat_tahun'] ?><span class="accent">+</span></div>
                                <div class="stat-label">Tahun Berdiri</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-item">
                                <div class="stat-num" style="<?= $tc ?>" data-count="<?= (int)$data['stat_prodi'] ?>"><?= (int)$data['stat_prodi'] ?><span class="accent">+</span></div>
                                <div class="stat-label">Program Studi</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-item">
                                <div class="stat-num" style="<?= $tc ?>" data-count="<?= (int)$data['stat_akr'] ?>"><?= (int)$data['stat_akr'] ?><span class="accent">%</span></div>
                                <div class="stat-label">Prodi Terakreditasi</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="stat-item">
                                <div class="stat-num" style="<?= $tc ?>" data-count="<?= (int)$data['jml_dokumen'] ?>"><?= (int)$data['jml_dokumen'] ?><span class="accent">+</span></div>
                                <div class="stat-label">Dokumen Mutu</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            break;

        case 'beranda_penghargaan':
            $penghargaan_list = $data['penghargaan_list'];
            $layout_penghargaan = $data['layout_penghargaan'];
            ?>
            <section class="<?= $pad_class ?> bg-light" style="<?= $bg ?>">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="section-tag">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .981-3.172M5.25 4.236c-.982.143-1.954.317-2.916.52A6.003 6.003 0 0 0 7.73 9.728M5.25 4.236V4.5c0 2.108.966 3.99 2.48 5.228M5.25 4.236V2.721C7.456 2.41 9.71 2.25 12 2.25c2.291 0 4.545.16 6.75.47v1.516M7.73 9.728a6.726 6.726 0 0 0 2.748 1.35m8.272-6.842V4.5c0 2.108-.966 3.99-2.48 5.228m2.48-5.492a46.32 46.32 0 0 1 2.916.52 6.003 6.003 0 0 1-5.395 4.972m0 0a6.726 6.726 0 0 1-2.749 1.35m0 0a6.772 6.772 0 0 1-3.044 0" />
                            </svg>
                            Penghargaan
                        </span>
                        <h2 class="section-title" style="<?= $tc ?>">Penghargaan &amp; Rekognisi Mutu</h2>
                    </div>

                    <?php if (empty($penghargaan_list)): ?>
                        <div class="text-center p-5 bg-white rounded-4 shadow-sm" style="border: 1px dashed #ccc;">
                            <p class="text-muted mb-0">Belum ada data penghargaan yang diunggah.</p>
                        </div>
                    <?php else: ?>
                        <?php if ($layout_penghargaan === 'slider'): ?>
                            <div id="penghargaanCarousel" class="carousel slide" data-bs-ride="carousel">
                                <div class="carousel-inner pb-4">
                                    <?php
                                    $chunks = array_chunk($penghargaan_list, 3);
                                    foreach ($chunks as $index => $chunk):
                                    ?>
                                        <div class="carousel-item <?= $index === 0 ? 'active' : '' ?>">
                                            <div class="row g-4 justify-content-center">
                                                <?php foreach ($chunk as $p): ?>
                                                    <div class="col-md-4">
                                                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden card-hover">
                                                            <?php
                                                            $p_img = getOrGeneratePdfPreview($p->file_path, 'penghargaan');
                                                            if ($p_img):
                                                            ?>
                                                                <img src="<?= SITE_URL ?>/uploads/penghargaan/<?= e($p_img) ?>" class="card-img-top object-fit-cover" style="height: 200px;" alt="<?= e($p->judul) ?>">
                                                            <?php elseif ($p->is_pdf): ?>
                                                                <div class="bg-light d-flex align-items-center justify-content-center" style="height: 200px;">
                                                                    <i class="bi bi-file-earmark-pdf-fill text-danger display-1"></i>
                                                                </div>
                                                            <?php else: ?>
                                                                <img src="<?= SITE_URL ?>/uploads/penghargaan/<?= e($p->file_path) ?>" class="card-img-top object-fit-cover" style="height: 200px;" alt="<?= e($p->judul) ?>">
                                                            <?php endif; ?>
                                                            <div class="card-body p-4 d-flex flex-column">
                                                                <div class="d-flex justify-content-between align-items-start mb-2">
                                                                    <span class="badge bg-purple-light text-purple rounded-pill"><?= e($p->tahun) ?></span>
                                                                </div>
                                                                <h5 class="card-title fw-bold text-navy mb-3"><?= e($p->judul) ?></h5>
                                                                <p class="card-text text-muted small mb-4 flex-grow-1"><i class="bi bi-building me-2"></i><?= e($p->instansi) ?></p>
                                                                <a href="<?= SITE_URL ?>/uploads/penghargaan/<?= e($p->file_path) ?>" target="_blank" class="btn btn-outline-purple btn-sm rounded-pill mt-auto w-100">
                                                                    Lihat Sertifikat <i class="bi bi-arrows-fullscreen ms-1"></i>
                                                                </a>
                                                            </div>
                                                        </div>
                                                    </div>
                                                <?php endforeach; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <?php if (count($penghargaan_list) > 3): ?>
                                    <div class="d-flex justify-content-center mt-3 gap-2">
                                        <button class="btn btn-purple rounded-circle p-2 shadow-sm d-flex align-items-center justify-content-center" type="button" data-bs-target="#penghargaanCarousel" data-bs-slide="prev" style="width: 40px; height: 40px;">
                                            <i class="bi bi-chevron-left"></i>
                                        </button>
                                        <button class="btn btn-purple rounded-circle p-2 shadow-sm d-flex align-items-center justify-content-center" type="button" data-bs-target="#penghargaanCarousel" data-bs-slide="next" style="width: 40px; height: 40px;">
                                            <i class="bi bi-chevron-right"></i>
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <div class="row g-4 justify-content-center">
                                <?php foreach ($penghargaan_list as $p): ?>
                                    <div class="col-md-4 col-lg-3">
                                        <div class="card h-100 border-0 shadow-sm rounded-4 overflow-hidden card-hover">
                                            <?php
                                            $p_img = getOrGeneratePdfPreview($p->file_path, 'penghargaan');
                                            if ($p_img):
                                            ?>
                                                <img src="<?= SITE_URL ?>/uploads/penghargaan/<?= e($p_img) ?>" class="card-img-top object-fit-cover" style="height: 180px;" alt="<?= e($p->judul) ?>">
                                            <?php elseif ($p->is_pdf): ?>
                                                <div class="bg-light d-flex align-items-center justify-content-center border-bottom" style="height: 180px;">
                                                    <i class="bi bi-file-earmark-pdf-fill text-danger display-1"></i>
                                                </div>
                                            <?php else: ?>
                                                <img src="<?= SITE_URL ?>/uploads/penghargaan/<?= e($p->file_path) ?>" class="card-img-top object-fit-cover" style="height: 180px;" alt="<?= e($p->judul) ?>">
                                            <?php endif; ?>
                                            <div class="card-body p-3 d-flex flex-column">
                                                <span class="badge bg-purple-light text-purple rounded-pill mb-2 align-self-start"><?= e($p->tahun) ?></span>
                                                <h6 class="card-title fw-bold text-navy mb-2"><?= e($p->judul) ?></h6>
                                                <p class="card-text text-muted small mb-3 flex-grow-1" style="font-size: 0.8rem;"><i class="bi bi-building me-1"></i><?= e($p->instansi) ?></p>
                                                <a href="<?= SITE_URL ?>/uploads/penghargaan/<?= e($p->file_path) ?>" target="_blank" class="btn btn-outline-purple btn-sm rounded-pill mt-auto w-100">
                                                    Lihat Sertifikat <i class="bi bi-arrows-fullscreen ms-1"></i>
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </section>
            <?php
            break;

        case 'beranda_akreditasi':
            $akred_badge     = !empty($block['badge']) ? $block['badge'] : $data['akred_badge'];
            $akred_judul     = !empty($block['title']) ? $block['title'] : $data['akred_judul'];
            $akred_teks      = !empty($block['content']) ? $block['content'] : $data['akred_teks'];
            $akred_peringkat = $data['akred_peringkat'];
            $akred_sk        = $data['akred_sk'];
            $akred_file      = $data['akred_file'];
            ?>
            <section class="<?= $pad_class ?>" style="<?= $bg ?: 'background:#FFFFFF;' ?>">
                <div class="container py-2">
                    <div class="row align-items-center justify-content-center">
                        <div class="col-lg-6 mb-4 mb-lg-0">
                            <div class="pe-lg-5">
                                <span class="badge bg-purple px-3 py-2 rounded-pill mb-3"><?= e($akred_badge) ?></span>
                                <h2 class="display-6 fw-bold text-navy mb-4" style="<?= $tc ?>"><?= nl2br(e($akred_judul)) ?></h2>
                                <p class="lead text-muted mb-4"><?= nl2br(e($akred_teks)) ?></p>
                                <div class="d-flex align-items-center mb-4">
                                    <svg width="55" height="70" viewBox="0 0 24 32" fill="none" xmlns="http://www.w3.org/2000/svg" class="me-3" style="filter: drop-shadow(0 4px 6px rgba(0,0,0,0.1));">
                                        <path d="M7 18 L7 29 L12 26 L17 29 L17 18 Z" fill="#FBC02D" />
                                        <path d="M12 2 L14.5 3.5 L17.5 3 L18.5 6 L21.5 7 L20.5 10 L22 13 L19.5 15 L19.5 18 L16.5 19 L14.5 21.5 L12 20 L9.5 21.5 L7.5 19 L4.5 18 L4.5 15 L2 13 L3.5 10 L2.5 7 L5.5 6 L6.5 3 L9.5 3.5 Z" fill="#FFD54F" />
                                    </svg>
                                    <div>
                                        <h4 class="fw-bold mb-1" style="<?= $tc ?>">AKREDITASI <?= e(strtoupper($akred_peringkat)) ?></h4>
                                        <p class="text-muted small mb-0"><?= e($akred_sk) ?></p>
                                    </div>
                                </div>
                                <div class="d-flex flex-wrap gap-3 mt-2">
                                    <a href="akreditasi-prodi.php" class="btn btn-akred-prodi fw-semibold px-4 py-2 rounded-pill d-flex align-items-center">
                                        Lihat Akreditasi Program Studi <i class="bi bi-list-check ms-2"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                        <div class="col-lg-5 text-center">
                            <?php if ($akred_file && file_exists(__DIR__ . '/../uploads/akreditasi/' . $akred_file)): ?>
                                <?php $akred_preview = getOrGeneratePdfPreview($akred_file, 'akreditasi'); ?>
                                <a href="uploads/akreditasi/<?= e($akred_file) ?>" target="_blank" class="d-block position-relative shadow-lg rounded-4 overflow-hidden cert-hover-card" style="border: 5px solid white; transform: rotate(2deg); transition: transform 0.35s cubic-bezier(0.2, 0.8, 0.35, 1.2), box-shadow 0.35s ease;" onmouseover="this.style.transform='rotate(0deg) scale(1.03)'" onmouseout="this.style.transform='rotate(2deg) scale(1)'">
                                    <?php if ($akred_preview): ?>
                                        <img src="uploads/akreditasi/<?= e($akred_preview) ?>" alt="Sertifikat Akreditasi SCU" class="img-fluid" style="object-fit: cover; width: 100%; max-height: 380px;">
                                    <?php else: ?>
                                        <div class="bg-light p-4 text-center">
                                            <i class="bi bi-file-earmark-pdf text-danger display-1 mb-2"></i>
                                            <div class="fw-bold text-navy">Dokumen Sertifikat Akreditasi</div>
                                        </div>
                                    <?php endif; ?>
                                </a>
                            <?php else: ?>
                                <div class="bg-light rounded-4 shadow-sm d-flex flex-column align-items-center justify-content-center p-5" style="height: 350px; border: 2px dashed #ccc;">
                                    <i class="bi bi-image text-muted display-1 mb-3"></i>
                                    <p class="text-muted">Sertifikat Akreditasi siap ditampilkan.</p>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'beranda_berita':
            $berita_terbaru = $data['berita_terbaru'];
            ?>
            <section class="<?= $pad_class ?>" style="<?= $bg ?: 'background: var(--bg-main);' ?>">
                <div class="container">
                    <div class="d-flex align-items-end justify-content-between mb-4 flex-wrap gap-3">
                        <div>
                            <span class="section-tag">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                                </svg>
                                Terbaru
                            </span>
                            <h2 class="section-title mb-0" style="<?= $tc ?>">Berita &amp; Kegiatan</h2>
                        </div>
                        <a href="berita.php" class="btn-add" style="flex-shrink:0;">
                            Lihat Semua
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </a>
                    </div>

                    <?php if (empty($berita_terbaru)): ?>
                        <div class="text-center py-5 text-muted">Belum ada berita yang dipublikasikan.</div>
                    <?php else: ?>
                        <div class="row g-4">
                            <?php foreach ($berita_terbaru as $i => $b): ?>
                                <div class="col-md-6 col-lg-4">
                                    <a href="berita-detail.php?slug=<?= e($b['slug']) ?>" class="card-lpm d-block text-decoration-none">
                                        <div class="card-img-wrap">
                                            <?php if ($b['gambar'] && file_exists(__DIR__ . '/../uploads/berita/' . $b['gambar'])): ?>
                                                <img src="<?= SITE_URL ?>/uploads/berita/<?= e($b['gambar']) ?>" alt="<?= e($b['judul']) ?>">
                                            <?php else: ?>
                                                <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--navy-mid),var(--purple-dark));display:flex;align-items:center;justify-content:center;min-height:200px;">
                                                    <i class="bi bi-image text-white-50 fs-1"></i>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                        <div class="card-body-lpm">
                                            <div class="card-meta">
                                                <span class="card-category-badge">Kegiatan LPM</span>
                                                <span><?= formatTanggal($b['tanggal_publikasi'] ?: $b['created_at']) ?></span>
                                            </div>
                                            <div class="card-title-lpm"><?= e($b['judul']) ?></div>
                                            <div class="card-desc-lpm"><?= truncate($b['konten'], 120) ?></div>
                                            <span class="card-link">Baca Selengkapnya <i class="bi bi-arrow-right ms-1"></i></span>
                                        </div>
                                    </a>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </section>
            <?php
            break;

        case 'beranda_cta':
        case 'cta':
            $cta_title    = !empty($block['title']) ? $block['title'] : 'Butuh Informasi Lebih Lanjut?';
            $cta_sub      = !empty($block['subtitle']) ? $block['subtitle'] : 'Tim LPM UNIKA siap membantu Anda. Hubungi kami untuk pertanyaan seputar penjaminan mutu, akreditasi, dan dokumen SPMI.';
            $cta_btn_text = !empty($block['btn_text']) ? $block['btn_text'] : 'Hubungi Kami';
            $cta_btn_link = !empty($block['btn_link']) ? $block['btn_link'] : 'kontak.php';
            $cta_btn2_txt = !empty($block['btn2_text']) ? $block['btn2_text'] : 'Akses Dokumen SPMI';
            $cta_btn2_lnk = !empty($block['btn2_link']) ? $block['btn2_link'] : 'spmi.php';
            ?>
            <section style="<?= $bg ?: 'background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 60%, var(--purple-dark) 100%);' ?> padding: 4rem 0; position:relative; overflow:hidden;">
                <div style="position:absolute;inset:0;background:radial-gradient(circle at 70% 50%, rgba(255,255,255,0.06) 0%, transparent 60%);pointer-events:none;"></div>
                <div class="container text-center position-relative">
                    <h2 style="font-family:var(--font-heading);font-size:clamp(1.6rem,3vw,2.2rem);font-weight:800;color:#fff;margin-bottom:0.75rem; <?= $tc ?>">
                        <?= e($cta_title) ?>
                    </h2>
                    <p style="color:rgba(255,255,255,0.75);max-width:500px;margin:0 auto 2rem;line-height:1.75;">
                        <?= e($cta_sub) ?>
                    </p>
                    <div class="d-flex gap-3 justify-content-center flex-wrap">
                        <a href="<?= e($cta_btn_link) ?>" class="btn-hero-primary">
                            <i class="bi bi-chat-dots-fill me-2"></i> <?= e($cta_btn_text) ?>
                        </a>
                        <a href="<?= e($cta_btn2_lnk) ?>" class="btn-hero-secondary">
                            <?= e($cta_btn2_txt) ?>
                        </a>
                    </div>
                </div>
            </section>
            <?php
            break;

        default:
            // Custom blocks added by admin (text_image, cards_grid, accordion, rich_text)
            echo renderPageBlocks([$block]);
            break;
    }
}
