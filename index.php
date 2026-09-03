<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Beranda';
$meta_desc  = 'LPM UNIKA – Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata. Mewujudkan mutu pendidikan tinggi yang unggul dan berkelanjutan.';

$db = getDB();

// Ambil berita terbaru
$berita_terbaru = $db->query("SELECT * FROM berita ORDER BY tanggal_publikasi DESC LIMIT 3")->fetchAll();

// Ambil slide aktif dari database
$hero_slides = $db->query("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY urutan ASC, id ASC")->fetchAll();

// Hitung statistik
$jml_dokumen = $db->query("SELECT COUNT(*) FROM dokumen")->fetchColumn();
$jml_berita  = $db->query("SELECT COUNT(*) FROM berita")->fetchColumn();

// Ambil data pengaturan dinamis
$stat_tahun  = getPengaturan('stat_tahun', '40');
$stat_prodi  = getPengaturan('stat_prodi', '27');
$stat_akr    = getPengaturan('stat_akreditasi', '100');

$sambutan_nama     = getPengaturan('sambutan_nama', 'Stefani Lily Indarto, SE., MM., Ak., CA., CPA.');
$sambutan_jabatan  = getPengaturan('sambutan_jabatan', 'Kepala Lembaga Penjaminan Mutu');
$sambutan_instansi = getPengaturan('sambutan_instansi', 'Universitas Katolik Soegijapranata');
$sambutan_foto     = getPengaturan('sambutan_foto', '');
$sambutan_teks     = getPengaturan('sambutan_teks', '');

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- ========================
     HERO CAROUSEL SECTION
======================== -->
<section class="hero-carousel-section">
    <div id="heroCarousel" class="carousel slide carousel-fade" data-bs-ride="carousel" data-bs-pause="false">
        <?php if (!empty($hero_slides)): ?>
        <!-- Indicators -->
        <div class="carousel-indicators">
            <?php foreach ($hero_slides as $idx => $s): ?>
            <button type="button" data-bs-target="#heroCarousel" data-bs-slide-to="<?= $idx ?>" class="<?= $idx === 0 ? 'active' : '' ?>" aria-current="<?= $idx === 0 ? 'true' : 'false' ?>" aria-label="Slide <?= $idx + 1 ?>"></button>
            <?php endforeach; ?>
        </div>

        <!-- Carousel Inner -->
        <div class="carousel-inner">
            <?php foreach ($hero_slides as $idx => $s): ?>
            <?php
            $bg_img = '';
            if (filter_var($s['gambar'], FILTER_VALIDATE_URL)) {
                $bg_img = $s['gambar'];
            } elseif ($s['gambar'] && file_exists(__DIR__ . '/uploads/slides/' . $s['gambar'])) {
                $bg_img = SITE_URL . '/uploads/slides/' . $s['gambar'];
            }
            $style_bg = $bg_img ? "background-image: url('{$bg_img}');" : "background: var(--navy);";

            // Format Judul dengan highlight jika ada
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
        <!-- Controls -->
        <button class="carousel-control-prev" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev">
            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next" type="button" data-bs-target="#heroCarousel" data-bs-slide="next">
            <span class="carousel-control-next-icon" aria-hidden="true"></span>
            <span class="visually-hidden">Next</span>
        </button>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>


<!-- ========================
     QUICK LINKS
======================== -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="row g-3 justify-content-center">
            <!-- Kebijakan Mutu -->
            <div class="col-6 col-md-4 col-lg-2">
                <a href="spmi.php?kategori=Kebijakan" class="quick-link-card text-decoration-none">
                    <div class="quick-link-icon" style="background: linear-gradient(135deg, #EDE7F6, #D1C4E9);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#4A148C" width="26" height="26">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 0 0 2.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 0 0-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25ZM6.75 12h.008v.008H6.75V12Zm0 3h.008v.008H6.75V15Zm0 3h.008v.008H6.75V18Z" />
                        </svg>
                    </div>
                    <span class="quick-link-label">Kebijakan Mutu</span>
                </a>
            </div>
            <!-- Manual Mutu -->
            <div class="col-6 col-md-4 col-lg-2">
                <a href="spmi.php?kategori=Manual" class="quick-link-card text-decoration-none">
                    <div class="quick-link-icon" style="background: linear-gradient(135deg, #E3F2FD, #BBDEFB);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#1565C0" width="26" height="26">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <span class="quick-link-label">Manual Mutu</span>
                </a>
            </div>
            <!-- Standar Mutu -->
            <div class="col-6 col-md-4 col-lg-2">
                <a href="spmi.php?kategori=Standar" class="quick-link-card text-decoration-none">
                    <div class="quick-link-icon" style="background: linear-gradient(135deg, #E8F5E9, #C8E6C9);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#2E7D32" width="26" height="26">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 0 1-1.043 3.296 3.745 3.745 0 0 1-3.296 1.043A3.745 3.745 0 0 1 12 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 0 1-3.296-1.043 3.745 3.745 0 0 1-1.043-3.296A3.745 3.745 0 0 1 3 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 0 1 1.043-3.296 3.746 3.746 0 0 1 3.296-1.043A3.746 3.746 0 0 1 12 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 0 1 3.296 1.043 3.746 3.746 0 0 1 1.043 3.296A3.745 3.745 0 0 1 21 12Z" />
                        </svg>
                    </div>
                    <span class="quick-link-label">Standar Mutu</span>
                </a>
            </div>
            <!-- Formulir Mutu -->
            <div class="col-6 col-md-4 col-lg-2">
                <a href="spmi.php?kategori=Formulir" class="quick-link-card text-decoration-none">
                    <div class="quick-link-icon" style="background: linear-gradient(135deg, #FFF8E1, #FFE082);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#E65100" width="26" height="26">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                        </svg>
                    </div>
                    <span class="quick-link-label">Formulir Mutu</span>
                </a>
            </div>
            <!-- Akreditasi -->
            <div class="col-6 col-md-4 col-lg-2">
                <a href="profil.php#akreditasi" class="quick-link-card text-decoration-none">
                    <div class="quick-link-icon" style="background: linear-gradient(135deg, #FCE4EC, #F8BBD9);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#AD1457" width="26" height="26">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                        </svg>
                    </div>
                    <span class="quick-link-label">Akreditasi</span>
                </a>
            </div>
            <!-- Kontak -->
            <div class="col-6 col-md-4 col-lg-2">
                <a href="kontak.php" class="quick-link-card text-decoration-none">
                    <div class="quick-link-icon" style="background: linear-gradient(135deg, #E0F7FA, #B2EBF2);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#00838F" width="26" height="26">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <span class="quick-link-label">Hubungi Kami</span>
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ========================
     STATS BAR
======================== -->
<div class="stats-bar">
    <div class="container">
        <div class="row g-0">
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num" data-count="<?= (int)$stat_tahun ?>">0<span class="accent">+</span></div>
                    <div class="stat-label">Tahun Berdiri</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num" data-count="<?= (int)$stat_prodi ?>">0<span class="accent">+</span></div>
                    <div class="stat-label">Program Studi</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num" data-count="<?= (int)$stat_akr ?>">0<span class="accent">%</span></div>
                    <div class="stat-label">Prodi Terakreditasi</div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-item">
                    <div class="stat-num" data-count="<?= (int)$jml_dokumen ?>">0<span class="accent">+</span></div>
                    <div class="stat-label">Dokumen Mutu</div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ========================
     SAMBUTAN KEPALA LPM
======================== -->
<section class="py-5 py-md-6">
    <div class="container">
        <div class="text-center mb-4">
            <span class="section-tag">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
                Sambutan
            </span>
            <h2 class="section-title">Sambutan Kepala LPM</h2>
        </div>
        <div class="sambutan-section">
            <div class="row g-0">
                <!-- Photo Column -->
                <div class="col-lg-3 sambutan-img-col">
                    <div class="sambutan-img-bg">
                        <div class="sambutan-photo-wrap">
                            <?php if ($sambutan_foto && file_exists(__DIR__ . '/uploads/profil/' . $sambutan_foto)): ?>
                                <img src="<?= SITE_URL ?>/uploads/profil/<?= e($sambutan_foto) ?>" alt="<?= e($sambutan_nama) ?>" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div class="sambutan-name"><?= e($sambutan_nama) ?></div>
                        <div class="sambutan-title"><?= e($sambutan_jabatan) ?></div>
                        <div class="sambutan-divider"></div>
                        <div style="font-size:0.78rem;color:rgba(255,255,255,0.45);"><?= e($sambutan_instansi) ?></div>
                    </div>
                </div>
                <!-- Content Column -->
                <div class="col-lg-9">
                    <div class="sambutan-content">
                        <div class="sambutan-quote">"</div>
                        <div class="sambutan-text" style="white-space: pre-line; line-height: 1.85;">
                            <?= e($sambutan_teks) ?>
                        </div>
                        <br>
                        <div style="font-style:normal; font-size:0.875rem; font-weight:600; color:var(--navy); margin-top:0.5rem;">
                            <?= e($sambutan_nama) ?>
                        </div>
                        <div style="font-size:0.8rem; color:var(--text-muted);"><?= e($sambutan_jabatan) ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ========================
     BERITA TERBARU
======================== -->
<section class="py-5 py-md-6" style="background: var(--bg-main);">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <span class="section-tag">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                    </svg>
                    Terbaru
                </span>
                <h2 class="section-title mb-0">Berita &amp; Kegiatan</h2>
            </div>
            <a href="berita.php" class="btn-add" style="flex-shrink:0;">
                Lihat Semua
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                </svg>
            </a>
        </div>

        <?php if (empty($berita_terbaru)): ?>
        <div class="text-center py-5" style="color:var(--text-muted);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="48" height="48" style="opacity:0.3; display:block; margin:0 auto 1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
            </svg>
            Belum ada berita. <a href="admin/" class="text-purple">Tambah di Admin</a>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($berita_terbaru as $i => $b): ?>
            <div class="col-md-6 col-lg-4">
                <a href="berita-detail.php?slug=<?= e($b['slug']) ?>" class="card-lpm d-block text-decoration-none">
                    <div class="card-img-wrap">
                        <?php if ($b['gambar'] && file_exists(__DIR__ . '/uploads/berita/' . $b['gambar'])): ?>
                            <img src="<?= SITE_URL ?>/uploads/berita/<?= e($b['gambar']) ?>" alt="<?= e($b['judul']) ?>">
                        <?php else: ?>
                            <div style="width:100%;height:100%;background:linear-gradient(135deg,var(--navy-mid),var(--purple-dark));display:flex;align-items:center;justify-content:center;min-height:200px;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="rgba(255,255,255,0.3)" width="48" height="48">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            </div>
                        <?php endif; ?>
                    </div>
                    <div class="card-body-lpm">
                        <div class="card-meta">
                            <span class="card-category-badge">Kegiatan LPM</span>
                            <span>
                                <?php if ($b['tanggal_publikasi']): ?>
                                    <?= formatTanggal($b['tanggal_publikasi']) ?>
                                <?php else: ?>
                                    <?= formatTanggal($b['created_at']) ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <div class="card-title-lpm"><?= e($b['judul']) ?></div>
                        <div class="card-desc-lpm"><?= truncate($b['konten'], 120) ?></div>
                        <span class="card-link">
                            Baca Selengkapnya
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                            </svg>
                        </span>
                    </div>
                </a>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- ========================
     CTA BANNER
======================== -->
<section style="background: linear-gradient(135deg, var(--navy) 0%, var(--navy-mid) 60%, var(--purple-dark) 100%); padding: 4rem 0; position:relative; overflow:hidden;">
    <div style="position:absolute;inset:0;background:radial-gradient(circle at 70% 50%, rgba(255,255,255,0.06) 0%, transparent 60%);pointer-events:none;"></div>
    <div class="container text-center position-relative">
        <h2 style="font-family:var(--font-heading);font-size:clamp(1.6rem,3vw,2.2rem);font-weight:800;color:#fff;margin-bottom:0.75rem;">
            Butuh Informasi Lebih Lanjut?
        </h2>
        <p style="color:rgba(255,255,255,0.75);max-width:500px;margin:0 auto 2rem;line-height:1.75;">
            Tim LPM UNIKA siap membantu Anda. Hubungi kami untuk pertanyaan seputar penjaminan mutu, akreditasi, dan dokumen SPMI.
        </p>
        <div class="d-flex gap-3 justify-content-center flex-wrap">
            <a href="kontak.php" class="btn-hero-primary">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                </svg>
                Hubungi Kami
            </a>
            <a href="spmi.php" class="btn-hero-secondary">
                Akses Dokumen SPMI
            </a>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
