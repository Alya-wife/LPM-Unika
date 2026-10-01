<?php
require_once __DIR__ . '/config/database.php';

$page_title   = 'Pemeringkatan Universitas & Jurusan – Internasional, Nasional, Lokal';
$meta_desc    = 'Capaian peringkat resmi Universitas Katolik Soegijapranata (SCU) pada skala Internasional (Global), Nasional (Indonesia), dan Lokal (Semarang & Jawa Tengah).';
$current_page = 'pemeringkatan';

$db = getDB();

// Ambil konfigurasi warna tema segmentasi dari database (dapat diubah admin)
$color_int = getPengaturan('pemeringkatan_color_internasional', '#1E3A8A');
$color_nas = getPengaturan('pemeringkatan_color_nasional', '#991B1B');
$color_lok = getPengaturan('pemeringkatan_color_lokal', '#0D9488');

// Ambil semua data pemeringkatan aktif
$rankings_int = [];
$rankings_nas = [];
$rankings_lok = [];

try {
    $stmt = $db->query("
        SELECT * FROM pemeringkatan 
        WHERE is_active = 1 
        ORDER BY urutan ASC, id DESC
    ");
    $all = $stmt->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all as $item) {
        if ($item['kategori'] === 'internasional') {
            $rankings_int[] = $item;
        } elseif ($item['kategori'] === 'nasional') {
            $rankings_nas[] = $item;
        } else {
            $rankings_lok[] = $item;
        }
    }
} catch (Exception $e) {}

$total_all = count($rankings_int) + count($rankings_nas) + count($rankings_lok);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
:root {
    --rank-color-int: <?= htmlspecialchars($color_int) ?>;
    --rank-color-nas: <?= htmlspecialchars($color_nas) ?>;
    --rank-color-lok: <?= htmlspecialchars($color_lok) ?>;
}

/* Navigasi Cepat Antar Section (Sticky Jump Bar) */
.section-jump-wrapper {
    position: sticky;
    top: 76px;
    z-index: 100;
    background: rgba(255, 255, 255, 0.95);
    backdrop-filter: blur(12px);
    border-bottom: 1px solid #E2E8F0;
    box-shadow: 0 4px 18px rgba(7, 23, 57, 0.05);
}
.jump-pill {
    padding: 0.6rem 1.25rem;
    border-radius: 50px;
    font-weight: 700;
    font-size: 0.88rem;
    text-decoration: none;
    transition: all 0.22s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
    border: 1.5px solid transparent;
}
.jump-pill-int {
    background: rgba(30, 58, 138, 0.08);
    color: var(--rank-color-int);
    border-color: rgba(30, 58, 138, 0.2);
}
.jump-pill-int:hover {
    background: var(--rank-color-int);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(30, 58, 138, 0.25);
    transform: translateY(-2px);
}
.jump-pill-nas {
    background: rgba(153, 27, 27, 0.08);
    color: var(--rank-color-nas);
    border-color: rgba(153, 27, 27, 0.2);
}
.jump-pill-nas:hover {
    background: var(--rank-color-nas);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(153, 27, 27, 0.25);
    transform: translateY(-2px);
}
.jump-pill-lok {
    background: rgba(13, 148, 136, 0.08);
    color: var(--rank-color-lok);
    border-color: rgba(13, 148, 136, 0.2);
}
.jump-pill-lok:hover {
    background: var(--rank-color-lok);
    color: #ffffff;
    box-shadow: 0 4px 12px rgba(13, 148, 136, 0.25);
    transform: translateY(-2px);
}

/* Section Header Styling */
.section-ranking-header {
    margin-bottom: 2rem;
    padding-bottom: 1.25rem;
    border-bottom: 2px solid #E2E8F0;
    position: relative;
}
.section-ranking-header::after {
    content: '';
    position: absolute;
    bottom: -2px;
    left: 0;
    width: 80px;
    height: 3px;
    border-radius: 3px;
}
.section-header-int::after { background: var(--rank-color-int); }
.section-header-nas::after { background: var(--rank-color-nas); }
.section-header-lok::after { background: var(--rank-color-lok); }

.badge-segmen-tag {
    font-size: 0.76rem;
    font-weight: 800;
    letter-spacing: 0.8px;
    text-transform: uppercase;
    padding: 0.4rem 0.9rem;
    border-radius: 50px;
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
}

/* Card Styling */
.ranking-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 18px;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 16px rgba(7, 23, 57, 0.03);
    position: relative;
}
.ranking-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 36px rgba(7, 23, 57, 0.1);
    border-color: #CBD5E1;
}

.ranking-hero-number {
    font-size: 2.85rem;
    font-weight: 900;
    font-family: var(--font-heading);
    letter-spacing: -1.2px;
    line-height: 1;
    margin-bottom: 0.35rem;
}
.ranking-hero-sub {
    font-size: 0.92rem;
    font-weight: 700;
    color: #64748B;
    margin-bottom: 1rem;
    display: block;
}

/* Section Divider */
.section-separator {
    margin: 4.5rem 0;
    position: relative;
    text-align: center;
}
.section-separator hr {
    border-top: 1px dashed #CBD5E1;
    margin: 0;
    opacity: 0.7;
}
.section-separator-icon {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: #F8FAFC;
    padding: 0.5rem 1.25rem;
    color: #94A3B8;
    font-size: 1.1rem;
    border-radius: 50px;
    border: 1px solid #E2E8F0;
}
</style>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Rekognisi &amp; Prestasi Mutu
        </div>
        <h1 class="page-banner-title">Pemeringkatan Universitas &amp; Jurusan</h1>
        <p class="text-white-50 mt-2 mb-0" style="max-width:760px;font-size:0.95rem;line-height:1.6;">
            Capaian peringkat resmi dan rekognisi mutu <strong>Universitas Katolik Soegijapranata (SCU)</strong> dari lembaga pemeringkat independen bereputasi pada skala <strong>Internasional</strong>, <strong>Nasional</strong>, dan <strong>Lokal</strong>.
        </p>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/akreditasi-institusi.php">Akreditasi</a>
            <span>/</span>
            <span class="current">Pemeringkatan</span>
        </div>
    </div>
</div>

<!-- Sticky Jump Bar Navigasi Antar Section -->
<div class="section-jump-wrapper py-3">
    <div class="container">
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="small fw-bold text-muted text-uppercase me-1" style="font-size:0.75rem;letter-spacing:0.5px;">
                    <i class="bi bi-compass me-1"></i> Lompat Ke:
                </span>
                <a href="#section-internasional" class="jump-pill jump-pill-int">
                    <i class="bi bi-globe2"></i> Internasional
                    <span class="badge rounded-pill bg-white text-dark ms-1 shadow-sm"><?= count($rankings_int) ?></span>
                </a>
                <a href="#section-nasional" class="jump-pill jump-pill-nas">
                    <i class="bi bi-flag-fill"></i> Nasional
                    <span class="badge rounded-pill bg-white text-dark ms-1 shadow-sm"><?= count($rankings_nas) ?></span>
                </a>
                <a href="#section-lokal" class="jump-pill jump-pill-lok">
                    <i class="bi bi-geo-alt-fill"></i> Lokal
                    <span class="badge rounded-pill bg-white text-dark ms-1 shadow-sm"><?= count($rankings_lok) ?></span>
                </a>
            </div>
            <div class="text-muted small d-none d-md-block">
                <i class="bi bi-award-fill text-warning me-1"></i> Total <strong><?= $total_all ?></strong> Capaian Resmi Terdata
            </div>
        </div>
    </div>
</div>

<div class="py-5" style="background:#F8FAFC;min-height:80vh;">
    <div class="container">

        <!-- ========================================== -->
        <!-- SECTION 1: INTERNASIONAL (GLOBAL / WORLD)  -->
        <!-- ========================================== -->
        <section id="section-internasional" class="pt-2">
            <div class="section-ranking-header section-header-int d-flex align-items-end justify-content-between flex-wrap gap-3">
                <div>
                    <div class="badge-segmen-tag mb-2" style="background:rgba(30, 58, 138, 0.1);color:var(--rank-color-int);">
                        <i class="bi bi-globe2"></i> Skala Internasional
                    </div>
                    <h2 class="fw-bold mb-1" style="font-size:1.55rem;color:var(--navy);">
                        Rekognisi Pemeringkatan Internasional
                    </h2>
                    <p class="text-muted small mb-0" style="max-width:700px;font-size:0.88rem;">
                        Pengakuan reputasi global, indeks sitasi saintis, dan metrik keberlanjutan kampus di kancah perguruan tinggi dunia.
                    </p>
                </div>
                <div>
                    <span class="badge rounded-pill px-3 py-2 fw-bold" style="background:var(--rank-color-int);color:#ffffff;font-size:0.82rem;">
                        <?= count($rankings_int) ?> Capaian Global
                    </span>
                </div>
            </div>

            <?php if (empty($rankings_int)): ?>
            <div class="text-center py-4 text-muted bg-white rounded-4 border p-4">
                <i class="bi bi-globe2 fs-2 opacity-50 mb-2 d-block"></i>
                <div class="fw-semibold">Belum ada data pemeringkatan internasional yang dipublikasikan.</div>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($rankings_int as $r): ?>
                <?php 
                $card_color = !empty($r['warna']) ? $r['warna'] : $color_int;
                $file_url = '';
                if (!empty($r['file_sertifikat'])) {
                    if (file_exists(__DIR__ . '/uploads/pemeringkatan/' . $r['file_sertifikat'])) {
                        $file_url = SITE_URL . '/uploads/pemeringkatan/' . $r['file_sertifikat'];
                    } elseif (file_exists(__DIR__ . '/uploads/akreditasi/' . $r['file_sertifikat'])) {
                        $file_url = SITE_URL . '/uploads/akreditasi/' . $r['file_sertifikat'];
                    } else {
                        $file_url = SITE_URL . '/uploads/pemeringkatan/' . $r['file_sertifikat'];
                    }
                }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="ranking-card" style="border-top: 5px solid <?= htmlspecialchars($card_color) ?> !important;">
                        
                        <!-- Header Kartu: Badge & Tahun -->
                        <div class="p-4 pb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span class="badge px-3 py-1 rounded-pill" style="background:rgba(30, 58, 138, 0.08);color:<?= htmlspecialchars($card_color) ?>;font-weight:800;font-size:0.75rem;">
                                <i class="bi bi-globe2 me-1"></i> INTERNASIONAL
                            </span>
                            <span class="badge bg-light text-muted border px-2 py-1 rounded-3" style="font-size:0.75rem;">
                                <?= e($r['tahun']) ?>
                            </span>
                        </div>

                        <!-- Body Kartu: Angka Peringkat, Judul, Lembaga, Deskripsi -->
                        <div class="p-4 pt-3 flex-grow-1 d-flex flex-column text-center">
                            <div class="mt-2 mb-1">
                                <span class="ranking-hero-number" style="color:<?= htmlspecialchars($card_color) ?>;">
                                    <?= e($r['peringkat'] ?: '-') ?>
                                </span>
                                <?php if (!empty($r['peringkat_dari'])): ?>
                                <span class="ranking-hero-sub"><?= e($r['peringkat_dari']) ?></span>
                                <?php endif; ?>
                            </div>

                            <h3 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.15rem;line-height:1.4;margin-bottom:0.75rem;">
                                <?= e($r['judul']) ?>
                            </h3>

                            <div class="mb-3 d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.78rem;">
                                    <i class="bi bi-patch-check-fill me-1" style="color:<?= htmlspecialchars($card_color) ?>;"></i> <?= e($r['lembaga']) ?>
                                </span>
                                <?php if (!empty($r['badge_teks'])): ?>
                                <span class="badge rounded-pill px-3 py-1" style="background:#F1F5F9;color:#334155;font-weight:700;font-size:0.75rem;">
                                    <?= e($r['badge_teks']) ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($r['deskripsi'])): ?>
                            <p class="text-muted small mb-4 flex-grow-1 text-start" style="font-size:0.86rem;line-height:1.65;">
                                <?= nl2br(e($r['deskripsi'])) ?>
                            </p>
                            <?php endif; ?>

                            <!-- Tombol Aksi -->
                            <div class="pt-3 border-top mt-auto vstack gap-2" style="border-color:#F1F5F9 !important;">
                                <?php if (!empty($file_url)): ?>
                                <button type="button" class="btn btn-warning w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size:0.88rem;" onclick="openCertModal('<?= $file_url ?>', '<?= e(addslashes($r['judul'])) ?>', '<?= e(addslashes($r['lembaga'])) ?>')">
                                    <i class="bi bi-file-earmark-check-fill"></i>
                                    <span>Lihat Berkas Sertifikat</span>
                                </button>
                                <?php endif; ?>

                                <?php if (!empty($r['link_url'])): ?>
                                <a href="<?= e($r['link_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn <?= !empty($file_url) ? 'btn-outline-dark' : 'btn-dark' ?> w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size:0.88rem;">
                                    <i class="bi bi-box-arrow-up-right" style="font-size:0.82rem;"></i>
                                    <span>Kunjungi Sumber Berita / Rilis</span>
                                </a>
                                <?php endif; ?>

                                <?php if (empty($file_url) && empty($r['link_url'])): ?>
                                <span class="text-muted small py-2">Data resmi terverifikasi</span>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- Separator -->
        <div class="section-separator">
            <hr>
            <div class="section-separator-icon">
                <i class="bi bi-arrow-down-short"></i>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- SECTION 2: NASIONAL (INDONESIA)            -->
        <!-- ========================================== -->
        <section id="section-nasional">
            <div class="section-ranking-header section-header-nas d-flex align-items-end justify-content-between flex-wrap gap-3">
                <div>
                    <div class="badge-segmen-tag mb-2" style="background:rgba(153, 27, 27, 0.1);color:var(--rank-color-nas);">
                        <i class="bi bi-flag-fill"></i> Skala Nasional
                    </div>
                    <h2 class="fw-bold mb-1" style="font-size:1.55rem;color:var(--navy);">
                        Rekognisi Pemeringkatan Nasional
                    </h2>
                    <p class="text-muted small mb-0" style="max-width:700px;font-size:0.88rem;">
                        Peringkat dan apresiasi universitas, kinerja riset, mutu lulusan, dan dampak kampus di tingkat Republik Indonesia.
                    </p>
                </div>
                <div>
                    <span class="badge rounded-pill px-3 py-2 fw-bold" style="background:var(--rank-color-nas);color:#ffffff;font-size:0.82rem;">
                        <?= count($rankings_nas) ?> Capaian Nasional
                    </span>
                </div>
            </div>

            <?php if (empty($rankings_nas)): ?>
            <div class="text-center py-4 text-muted bg-white rounded-4 border p-4">
                <i class="bi bi-flag-fill fs-2 opacity-50 mb-2 d-block"></i>
                <div class="fw-semibold">Belum ada data pemeringkatan nasional yang dipublikasikan.</div>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($rankings_nas as $r): ?>
                <?php 
                $card_color = !empty($r['warna']) ? $r['warna'] : $color_nas;
                $file_url = '';
                if (!empty($r['file_sertifikat'])) {
                    if (file_exists(__DIR__ . '/uploads/pemeringkatan/' . $r['file_sertifikat'])) {
                        $file_url = SITE_URL . '/uploads/pemeringkatan/' . $r['file_sertifikat'];
                    } elseif (file_exists(__DIR__ . '/uploads/akreditasi/' . $r['file_sertifikat'])) {
                        $file_url = SITE_URL . '/uploads/akreditasi/' . $r['file_sertifikat'];
                    } else {
                        $file_url = SITE_URL . '/uploads/pemeringkatan/' . $r['file_sertifikat'];
                    }
                }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="ranking-card" style="border-top: 5px solid <?= htmlspecialchars($card_color) ?> !important;">
                        
                        <!-- Header Kartu: Badge & Tahun -->
                        <div class="p-4 pb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span class="badge px-3 py-1 rounded-pill" style="background:rgba(153, 27, 27, 0.08);color:<?= htmlspecialchars($card_color) ?>;font-weight:800;font-size:0.75rem;">
                                <i class="bi bi-flag-fill me-1"></i> NASIONAL
                            </span>
                            <span class="badge bg-light text-muted border px-2 py-1 rounded-3" style="font-size:0.75rem;">
                                <?= e($r['tahun']) ?>
                            </span>
                        </div>

                        <!-- Body Kartu: Angka Peringkat, Judul, Lembaga, Deskripsi -->
                        <div class="p-4 pt-3 flex-grow-1 d-flex flex-column text-center">
                            <div class="mt-2 mb-1">
                                <span class="ranking-hero-number" style="color:<?= htmlspecialchars($card_color) ?>;">
                                    <?= e($r['peringkat'] ?: '-') ?>
                                </span>
                                <?php if (!empty($r['peringkat_dari'])): ?>
                                <span class="ranking-hero-sub"><?= e($r['peringkat_dari']) ?></span>
                                <?php endif; ?>
                            </div>

                            <h3 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.15rem;line-height:1.4;margin-bottom:0.75rem;">
                                <?= e($r['judul']) ?>
                            </h3>

                            <div class="mb-3 d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.78rem;">
                                    <i class="bi bi-patch-check-fill me-1" style="color:<?= htmlspecialchars($card_color) ?>;"></i> <?= e($r['lembaga']) ?>
                                </span>
                                <?php if (!empty($r['badge_teks'])): ?>
                                <span class="badge rounded-pill px-3 py-1" style="background:#F1F5F9;color:#334155;font-weight:700;font-size:0.75rem;">
                                    <?= e($r['badge_teks']) ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($r['deskripsi'])): ?>
                            <p class="text-muted small mb-4 flex-grow-1 text-start" style="font-size:0.86rem;line-height:1.65;">
                                <?= nl2br(e($r['deskripsi'])) ?>
                            </p>
                            <?php endif; ?>

                            <!-- Tombol Aksi -->
                            <div class="pt-3 border-top mt-auto vstack gap-2" style="border-color:#F1F5F9 !important;">
                                <?php if (!empty($file_url)): ?>
                                <button type="button" class="btn btn-warning w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size:0.88rem;" onclick="openCertModal('<?= $file_url ?>', '<?= e(addslashes($r['judul'])) ?>', '<?= e(addslashes($r['lembaga'])) ?>')">
                                    <i class="bi bi-file-earmark-check-fill"></i>
                                    <span>Lihat Berkas Sertifikat</span>
                                </button>
                                <?php endif; ?>

                                <?php if (!empty($r['link_url'])): ?>
                                <a href="<?= e($r['link_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn <?= !empty($file_url) ? 'btn-outline-dark' : 'btn-dark' ?> w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size:0.88rem;">
                                    <i class="bi bi-box-arrow-up-right" style="font-size:0.82rem;"></i>
                                    <span>Kunjungi Sumber Berita / Rilis</span>
                                </a>
                                <?php endif; ?>

                                <?php if (empty($file_url) && empty($r['link_url'])): ?>
                                <span class="text-muted small py-2">Data resmi terverifikasi</span>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

        <!-- Separator -->
        <div class="section-separator">
            <hr>
            <div class="section-separator-icon">
                <i class="bi bi-arrow-down-short"></i>
            </div>
        </div>

        <!-- ========================================== -->
        <!-- SECTION 3: LOKAL (SEMARANG & JAWA TENGAH)  -->
        <!-- ========================================== -->
        <section id="section-lokal">
            <div class="section-ranking-header section-header-lok d-flex align-items-end justify-content-between flex-wrap gap-3">
                <div>
                    <div class="badge-segmen-tag mb-2" style="background:rgba(13, 148, 136, 0.1);color:var(--rank-color-lok);">
                        <i class="bi bi-geo-alt-fill"></i> Skala Lokal &amp; Regional
                    </div>
                    <h2 class="fw-bold mb-1" style="font-size:1.55rem;color:var(--navy);">
                        Rekognisi Pemeringkatan Lokal
                    </h2>
                    <p class="text-muted small mb-0" style="max-width:700px;font-size:0.88rem;">
                        Posisi terdepan Universitas Katolik Soegijapranata sebagai PTS terbaik di Kota Semarang dan wilayah Jawa Tengah.
                    </p>
                </div>
                <div>
                    <span class="badge rounded-pill px-3 py-2 fw-bold" style="background:var(--rank-color-lok);color:#ffffff;font-size:0.82rem;">
                        <?= count($rankings_lok) ?> Capaian Lokal
                    </span>
                </div>
            </div>

            <?php if (empty($rankings_lok)): ?>
            <div class="text-center py-4 text-muted bg-white rounded-4 border p-4">
                <i class="bi bi-geo-alt-fill fs-2 opacity-50 mb-2 d-block"></i>
                <div class="fw-semibold">Belum ada data pemeringkatan lokal yang dipublikasikan.</div>
            </div>
            <?php else: ?>
            <div class="row g-4">
                <?php foreach ($rankings_lok as $r): ?>
                <?php 
                $card_color = !empty($r['warna']) ? $r['warna'] : $color_lok;
                $file_url = '';
                if (!empty($r['file_sertifikat'])) {
                    if (file_exists(__DIR__ . '/uploads/pemeringkatan/' . $r['file_sertifikat'])) {
                        $file_url = SITE_URL . '/uploads/pemeringkatan/' . $r['file_sertifikat'];
                    } elseif (file_exists(__DIR__ . '/uploads/akreditasi/' . $r['file_sertifikat'])) {
                        $file_url = SITE_URL . '/uploads/akreditasi/' . $r['file_sertifikat'];
                    } else {
                        $file_url = SITE_URL . '/uploads/pemeringkatan/' . $r['file_sertifikat'];
                    }
                }
                ?>
                <div class="col-md-6 col-lg-4">
                    <div class="ranking-card" style="border-top: 5px solid <?= htmlspecialchars($card_color) ?> !important;">
                        
                        <!-- Header Kartu: Badge & Tahun -->
                        <div class="p-4 pb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                            <span class="badge px-3 py-1 rounded-pill" style="background:rgba(13, 148, 136, 0.08);color:<?= htmlspecialchars($card_color) ?>;font-weight:800;font-size:0.75rem;">
                                <i class="bi bi-geo-alt-fill me-1"></i> LOKAL (SEMARANG &amp; JATENG)
                            </span>
                            <span class="badge bg-light text-muted border px-2 py-1 rounded-3" style="font-size:0.75rem;">
                                <?= e($r['tahun']) ?>
                            </span>
                        </div>

                        <!-- Body Kartu: Angka Peringkat, Judul, Lembaga, Deskripsi -->
                        <div class="p-4 pt-3 flex-grow-1 d-flex flex-column text-center">
                            <div class="mt-2 mb-1">
                                <span class="ranking-hero-number" style="color:<?= htmlspecialchars($card_color) ?>;">
                                    <?= e($r['peringkat'] ?: '-') ?>
                                </span>
                                <?php if (!empty($r['peringkat_dari'])): ?>
                                <span class="ranking-hero-sub"><?= e($r['peringkat_dari']) ?></span>
                                <?php endif; ?>
                            </div>

                            <h3 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.15rem;line-height:1.4;margin-bottom:0.75rem;">
                                <?= e($r['judul']) ?>
                            </h3>

                            <div class="mb-3 d-flex align-items-center justify-content-center gap-2 flex-wrap">
                                <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.78rem;">
                                    <i class="bi bi-patch-check-fill me-1" style="color:<?= htmlspecialchars($card_color) ?>;"></i> <?= e($r['lembaga']) ?>
                                </span>
                                <?php if (!empty($r['badge_teks'])): ?>
                                <span class="badge rounded-pill px-3 py-1" style="background:#F1F5F9;color:#334155;font-weight:700;font-size:0.75rem;">
                                    <?= e($r['badge_teks']) ?>
                                </span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($r['deskripsi'])): ?>
                            <p class="text-muted small mb-4 flex-grow-1 text-start" style="font-size:0.86rem;line-height:1.65;">
                                <?= nl2br(e($r['deskripsi'])) ?>
                            </p>
                            <?php endif; ?>

                            <!-- Tombol Aksi -->
                            <div class="pt-3 border-top mt-auto vstack gap-2" style="border-color:#F1F5F9 !important;">
                                <?php if (!empty($file_url)): ?>
                                <button type="button" class="btn btn-warning w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size:0.88rem;" onclick="openCertModal('<?= $file_url ?>', '<?= e(addslashes($r['judul'])) ?>', '<?= e(addslashes($r['lembaga'])) ?>')">
                                    <i class="bi bi-file-earmark-check-fill"></i>
                                    <span>Lihat Berkas Sertifikat</span>
                                </button>
                                <?php endif; ?>

                                <?php if (!empty($r['link_url'])): ?>
                                <a href="<?= e($r['link_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn <?= !empty($file_url) ? 'btn-outline-dark' : 'btn-dark' ?> w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size:0.88rem;">
                                    <i class="bi bi-box-arrow-up-right" style="font-size:0.82rem;"></i>
                                    <span>Kunjungi Sumber Berita / Rilis</span>
                                </a>
                                <?php endif; ?>

                                <?php if (empty($file_url) && empty($r['link_url'])): ?>
                                <span class="text-muted small py-2">Data resmi terverifikasi</span>
                                <?php endif; ?>
                            </div>
                        </div>

                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </section>

    </div>
</div>

<!-- Modal Lightbox Pratinjau Sertifikat Dokumen / PDF / Gambar -->
<div class="modal fade" id="certPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-3 border-0">
                <div class="d-flex align-items-center gap-2 min-w-0 me-3">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-award-fill fs-5 text-warning"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="modal-title fw-bold text-truncate mb-0" id="certModalTitle">Pratinjau Sertifikat Pemeringkatan</h6>
                        <small class="text-white-50 text-truncate d-block" id="certModalSubtitle"></small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
                    <a href="#" id="certModalNewTabBtn" target="_blank" class="btn btn-sm btn-outline-light d-none d-sm-inline-flex align-items-center">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka di Tab Baru
                    </a>
                    <a href="#" id="certModalDownloadBtn" download class="btn btn-sm btn-warning text-dark fw-bold d-inline-flex align-items-center">
                        <i class="bi bi-download me-1"></i> Unduh Berkas
                    </a>
                    <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0 bg-light text-center" style="min-height:65vh;max-height:82vh;display:flex;flex-direction:column;justify-content:center;position:relative;">
                <div id="certModalLoading" class="position-absolute top-50 start-50 translate-middle text-center text-muted" style="z-index:5;">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div class="small fw-semibold">Memuat pratinjau sertifikat...</div>
                </div>
                <!-- Iframe Viewer for PDF -->
                <iframe id="certModalIframe" src="" style="width:100%;height:75vh;border:none;flex-grow:1;display:none;" onload="document.getElementById('certModalLoading').style.display='none';"></iframe>
                <!-- Image Viewer for WebP/JPG/PNG -->
                <img id="certModalImg" src="" alt="Sertifikat" style="max-width:100%;max-height:75vh;object-fit:contain;margin:auto;display:none;" onload="document.getElementById('certModalLoading').style.display='none';">
            </div>
            <div class="modal-footer bg-white border-top p-2 px-3 d-flex justify-content-between align-items-center">
                <span class="text-muted small"><i class="bi bi-patch-check-fill text-success me-1"></i> Sertifikat Resmi Lembaga Penjaminan Mutu &amp; Universitas Katolik Soegijapranata.</span>
                <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Smooth scroll for anchor jump links with offset for sticky header
document.querySelectorAll('.jump-pill').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
        var targetId = this.getAttribute('href');
        if (targetId && targetId.startsWith('#')) {
            var targetElem = document.querySelector(targetId);
            if (targetElem) {
                e.preventDefault();
                var headerOffset = 140;
                var elementPosition = targetElem.getBoundingClientRect().top;
                var offsetPosition = elementPosition + window.pageYOffset - headerOffset;
                window.scrollTo({
                    top: offsetPosition,
                    behavior: 'smooth'
                });
            }
        }
    });
});

// Modal Pratinjau Sertifikat
function openCertModal(fileUrl, title, subtitle) {
    var iframe = document.getElementById('certModalIframe');
    var img = document.getElementById('certModalImg');
    var loading = document.getElementById('certModalLoading');
    var titleEl = document.getElementById('certModalTitle');
    var subEl = document.getElementById('certModalSubtitle');
    var newTabBtn = document.getElementById('certModalNewTabBtn');
    var dlBtn = document.getElementById('certModalDownloadBtn');

    titleEl.textContent = title || 'Sertifikat Pemeringkatan';
    subEl.textContent = subtitle || fileUrl.split('/').pop();
    newTabBtn.href = fileUrl;
    dlBtn.href = fileUrl;

    iframe.style.display = 'none';
    img.style.display = 'none';
    loading.style.display = 'block';

    var ext = fileUrl.split('.').pop().toLowerCase();
    if (ext === 'pdf') {
        iframe.src = fileUrl;
        iframe.style.display = 'block';
    } else {
        img.src = fileUrl;
        img.style.display = 'block';
    }

    var modalEl = document.getElementById('certPreviewModal');
    var modal = (typeof bootstrap !== 'undefined' && bootstrap.Modal.getOrCreateInstance)
        ? bootstrap.Modal.getOrCreateInstance(modalEl)
        : new bootstrap.Modal(modalEl);
    modal.show();
}

// Reset saat modal ditutup
document.getElementById('certPreviewModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('certModalIframe').src = '';
    document.getElementById('certModalImg').src = '';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
