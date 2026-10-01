<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Buletin JAMUS – Publikasi Mutu LPM UNIKA';
$meta_desc  = 'Buletin Jaminan Mutu Universitas (JAMUS) LPM UNIKA – koleksi lengkap edisi buletin penjaminan mutu Universitas Katolik Soegijapranata.';

$db = getDB();

// Palette warna untuk placeholder cover (jika cover gambar tidak ada)
define('COVER_PALETTE', [
    ['bg' => '#4A148C', 'stripe' => '#7B1FA2', 'accent' => '#CE93D8'],
    ['bg' => '#0D47A1', 'stripe' => '#1565C0', 'accent' => '#90CAF9'],
    ['bg' => '#1B5E20', 'stripe' => '#2E7D32', 'accent' => '#A5D6A7'],
    ['bg' => '#B71C1C', 'stripe' => '#C62828', 'accent' => '#EF9A9A'],
    ['bg' => '#E65100', 'stripe' => '#BF360C', 'accent' => '#FFCC80'],
    ['bg' => '#006064', 'stripe' => '#00838F', 'accent' => '#80DEEA'],
]);

// Ambil seluruh buletin yang aktif (diurutkan berdasarkan tanggal terbit terbaru)
$all_buletin_list = $db->query("SELECT *, YEAR(tanggal_terbit) as thn FROM buletin WHERE is_aktif = 1 ORDER BY tanggal_terbit DESC, id DESC")->fetchAll();

// Kelompokkan buletin berdasarkan tahun terbit
$buletin_by_year = [];
foreach ($all_buletin_list as $b) {
    $th = $b['thn'] ?: (!empty($b['tanggal_terbit']) ? date('Y', strtotime($b['tanggal_terbit'])) : date('Y', strtotime($b['created_at'])));
    $buletin_by_year[$th][] = $b;
}

// Daftar tahun yang tersedia, terurut dari yang terbaru
$all_years_buletin = array_keys($buletin_by_year);
rsort($all_years_buletin);

// Cek parameter filter awal dari URL jika ada
$initial_tahun = (isset($_GET['tahun']) && is_numeric($_GET['tahun'])) ? (int)$_GET['tahun'] : 0;
$initial_filter = ($initial_tahun > 0 && in_array($initial_tahun, $all_years_buletin)) ? (string)$initial_tahun : 'all';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Publikasi Berkala LPM UNIKA
        </div>
        <h1 class="page-banner-title">Arsip Buletin JAMUS</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/knowledge.php">Knowledge</a>
            <span>/</span>
            <span class="current">Buletin JAMUS</span>
        </div>
    </div>
</div>

<!-- Intro strip -->
<div style="background:linear-gradient(135deg, #4A148C, #7B1FA2);padding:2rem 0;border-bottom:4px solid #FBBF24;">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-md-8">
                <p style="font-size:1rem;color:rgba(255,255,255,0.92);line-height:1.75;margin:0;">
                    <strong style="color:#FBBF24;">Buletin Jaminan Mutu Universitas (JAMUS)</strong> merupakan publikasi berkala Lembaga Penjaminan Mutu yang menyajikan perkembangan SPMI, best practice program studi, laporan AMI, serta opini mutakhir dari pemerhati mutu di UNIKA Soegijapranata.
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="<?= SITE_URL ?>/spmi.php#dokumen-spmi" class="btn-hero-secondary" style="background:rgba(255,255,255,0.12);border:1.5px solid rgba(255,255,255,0.4);color:#fff;text-decoration:none;padding:0.65rem 1.4rem;font-size:0.88rem;border-radius:25px;display:inline-flex;align-items:center;gap:6px;">
                    Lihat di Dokumen Mutu &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<style>
/* ── Bookshelf & Bookcase Master Styles ── */
.bookshelf-section {
    background-color: #EFECE6;
    background-image:
        radial-gradient(ellipse at 50% 0%, rgba(255,255,255,0.85) 0%, rgba(239,236,230,0) 65%),
        repeating-linear-gradient(
            90deg,
            rgba(180, 160, 130, 0.04) 0px,
            rgba(180, 160, 130, 0.04) 1px,
            transparent 1px,
            transparent 36px
        );
    position: relative;
}

/* Filter Bar Buttons (Instant / Zero-Reload) */
.shelf-filter-wrapper {
    background: #ffffff;
    border: 1px solid rgba(139, 111, 71, 0.2);
    border-radius: 50px;
    padding: 6px 10px;
    display: inline-flex;
    align-items: center;
    gap: 6px;
    box-shadow: 0 4px 20px rgba(74, 53, 32, 0.08);
    flex-wrap: wrap;
    justify-content: center;
    max-width: 100%;
}

.shelf-filter-btn {
    border: none;
    background: transparent;
    color: #5A4A38;
    font-weight: 700;
    font-size: 0.84rem;
    padding: 7px 16px;
    border-radius: 30px;
    cursor: pointer;
    transition: all 0.22s cubic-bezier(0.2, 0, 0, 1);
    display: inline-flex;
    align-items: center;
    gap: 6px;
    outline: none !important;
}
.shelf-filter-btn:hover {
    background: rgba(139, 111, 71, 0.1);
    color: #2D1E10;
}
.shelf-filter-btn.active {
    background: linear-gradient(135deg, #7B1FA2 0%, #4A148C 100%);
    color: #ffffff;
    box-shadow: 0 3px 12px rgba(74, 20, 140, 0.35);
}
.shelf-filter-btn .filter-count-badge {
    background: rgba(0, 0, 0, 0.08);
    font-size: 0.72rem;
    padding: 1px 7px;
    border-radius: 12px;
    font-weight: 800;
}
.shelf-filter-btn.active .filter-count-badge {
    background: #FBBF24;
    color: #1A1A1A;
}

/* ── Bookcase Cabinet Frame ── */
.bookcase-cabinet {
    max-width: 1140px;
    margin: 0 auto;
    position: relative;
    padding: 0 20px;
}

/* Side Upright Pillars */
.bookcase-cabinet::before,
.bookcase-cabinet::after {
    content: '';
    position: absolute;
    top: 0;
    bottom: 0;
    width: 14px;
    background: linear-gradient(90deg, #6B4F2B 0%, #4A3520 60%, #302113 100%);
    box-shadow: 0 0 10px rgba(0,0,0,0.25);
    border-radius: 3px;
    z-index: 5;
    pointer-events: none;
}
.bookcase-cabinet::before { left: 6px; }
.bookcase-cabinet::after  { right: 6px; }

/* ── Year Shelf Unit ── */
.bookcase-shelf-unit {
    margin-bottom: 50px;
    transition: opacity 0.35s ease, transform 0.35s ease;
}
.bookcase-shelf-unit.fade-enter {
    animation: shelfAppear 0.4s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes shelfAppear {
    from { opacity: 0; transform: translateY(12px); }
    to   { opacity: 1; transform: translateY(0); }
}

/* Plakat Kuningan / Brass Nameplate Header */
.shelf-header-beam {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 22px;
    padding: 0 12px;
    flex-wrap: wrap;
    gap: 10px;
}

.shelf-brass-plaque {
    display: inline-flex;
    align-items: center;
    gap: 9px;
    background: linear-gradient(135deg, #ECC46A 0%, #FDF3C7 35%, #D4AF37 70%, #996515 100%);
    border: 2px solid #8A5B15;
    border-radius: 8px;
    padding: 7px 18px;
    box-shadow:
        inset 0 1px 0 rgba(255,255,255,0.7),
        inset 0 -1px 2px rgba(0,0,0,0.3),
        0 4px 12px rgba(74, 53, 32, 0.28);
    position: relative;
}

/* Plaque corner screws */
.plaque-screw {
    width: 6px;
    height: 6px;
    background: #6B4310;
    border-radius: 50%;
    position: absolute;
    box-shadow: inset 0 1px 1px rgba(0,0,0,0.5), 0 1px 1px rgba(255,255,255,0.4);
}
.plaque-screw.tl { top: 4px; left: 4px; }
.plaque-screw.tr { top: 4px; right: 4px; }
.plaque-screw.bl { bottom: 4px; left: 4px; }
.plaque-screw.br { bottom: 4px; right: 4px; }

.plaque-title {
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 0.95rem;
    color: #381E05;
    letter-spacing: 1px;
    text-shadow: 0 1px 0 rgba(255,255,255,0.6);
}
.plaque-count-pill {
    background: #381E05;
    color: #FDF3C7;
    font-size: 0.7rem;
    font-weight: 800;
    padding: 3px 9px;
    border-radius: 12px;
    letter-spacing: 0.5px;
}

.shelf-meta-hint {
    font-size: 0.8rem;
    color: #795548;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 6px;
}

/* ── Shelf Rack (Books sitting on wooden beam) ── */
.shelf-books-container {
    position: relative;
    padding-bottom: 22px;
}

/* Solid Hardwood Shelf Plank with 3D Depth */
.hardwood-shelf-plank {
    position: absolute;
    bottom: 0;
    left: 0;
    right: 0;
    height: 24px;
    z-index: 2;
}
/* Top surface of the wood plank (bevel perspective) */
.plank-surface-top {
    height: 6px;
    background: linear-gradient(90deg, #A88352 0%, #BD9967 30%, #A88352 70%, #96703F 100%);
    border-radius: 3px 3px 0 0;
    border-top: 1.5px solid rgba(255,255,255,0.4);
    box-shadow: inset 0 2px 4px rgba(0,0,0,0.15);
}
/* Front face of the wood plank */
.plank-edge-front {
    height: 18px;
    background: linear-gradient(180deg, #8B6338 0%, #68451F 50%, #4A2E0F 100%);
    border-radius: 0 0 5px 5px;
    box-shadow: 0 8px 20px rgba(50, 32, 12, 0.45);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: hidden;
}
.plank-engraving {
    font-size: 0.62rem;
    letter-spacing: 2.5px;
    color: rgba(255, 235, 195, 0.28);
    font-weight: 900;
    text-transform: uppercase;
    font-family: var(--font-heading);
    user-select: none;
}
/* Ambient shadow beneath the shelf */
.plank-drop-shadow {
    position: absolute;
    bottom: -14px;
    left: 4px;
    right: 4px;
    height: 14px;
    background: radial-gradient(ellipse at 50% 0%, rgba(40,24,10,0.4) 0%, rgba(40,24,10,0) 80%);
    pointer-events: none;
}

/* ── Book Card Styles ── */
.book-card {
    cursor: pointer;
    text-decoration: none;
    display: block;
    outline: none;
    position: relative;
    z-index: 3;
    padding-bottom: 8px;
}
.book-card:hover,
.book-card:focus {
    text-decoration: none;
}

/* The 3D Book Elevation & Hover Animation */
.book-spine-shell {
    position: relative;
    border-radius: 3px 8px 8px 3px;
    overflow: hidden;
    aspect-ratio: 1 / 1.41;
    background: #1e1e1e;
    box-shadow:
        -2px 0 0 #B0B0B0,
        -5px 0 0 #8C8C8C,
        3px 6px 18px rgba(0,0,0,0.32),
        6px 14px 28px rgba(0,0,0,0.18);
    transition: transform 0.28s cubic-bezier(0.2, 0.8, 0.35, 1.2), box-shadow 0.28s ease;
}

.book-card:hover .book-spine-shell {
    transform: translateY(-16px) rotate(-0.5deg);
    box-shadow:
        -2px 0 0 #B0B0B0,
        -5px 0 0 #8C8C8C,
        0 18px 32px rgba(0,0,0,0.4),
        0 30px 45px rgba(0,0,0,0.22);
}

/* Spine Shadow on Left Edge */
.book-spine-shell::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 14px; height: 100%;
    background: linear-gradient(90deg, rgba(0,0,0,0.38) 0%, rgba(0,0,0,0.1) 60%, transparent 100%);
    z-index: 2;
}

/* Glossy Sheen Overlay */
.book-spine-shell::after {
    content: '';
    position: absolute;
    top: 0; left: 12%;
    width: 32%; height: 100%;
    background: linear-gradient(105deg, rgba(255,255,255,0.28) 0%, rgba(255,255,255,0) 75%);
    z-index: 3;
    pointer-events: none;
}

.book-cover-img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    display: block;
    background: #2D3748;
}

/* Fallback Placeholder Cover */
.book-placeholder-cover {
    width: 100%;
    height: 100%;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 16px 10px;
    position: relative;
    overflow: hidden;
}
.book-placeholder-cover .cover-deco-stripe {
    position: absolute;
    bottom: 0; left: 0; right: 0;
    height: 38%;
    opacity: 0.25;
}
.book-placeholder-cover .cover-logo-text {
    font-family: var(--font-heading);
    font-weight: 900;
    font-size: 1.25rem;
    color: #fff;
    letter-spacing: 1.5px;
    text-shadow: 0 2px 8px rgba(0,0,0,0.4);
}
.book-placeholder-cover .cover-edisi-badge {
    background: rgba(255,255,255,0.22);
    border: 1px solid rgba(255,255,255,0.4);
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 20px;
    margin-top: 8px;
}

/* Download / Open Overlay on Hover */
.book-download-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(15,23,42,0.88) 0%, rgba(15,23,42,0) 55%);
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding-bottom: 14px;
    opacity: 0;
    transition: opacity 0.22s ease;
    z-index: 10;
}
.book-card:hover .book-download-overlay {
    opacity: 1;
}
.book-download-btn {
    background: #FBBF24;
    color: #1A1A1A;
    font-weight: 800;
    font-size: 0.72rem;
    padding: 6px 14px;
    border-radius: 20px;
    box-shadow: 0 3px 10px rgba(0,0,0,0.4);
    display: flex;
    align-items: center;
    gap: 6px;
    transition: transform 0.15s ease;
}
.book-card:hover .book-download-btn {
    transform: scale(1.05);
}

/* Contact Shadow on the Wood Plank underneath the book */
.book-shelf-shadow {
    width: 82%;
    height: 7px;
    background: radial-gradient(ellipse at 50% 50%, rgba(30,18,6,0.65) 0%, rgba(30,18,6,0) 75%);
    margin: 3px auto 0;
    border-radius: 50%;
    transition: transform 0.28s ease, opacity 0.28s ease;
}
.book-card:hover .book-shelf-shadow {
    transform: scale(0.85);
    opacity: 0.35;
}

/* Book Typography Meta */
.book-info-block {
    padding: 10px 4px 0;
    text-align: center;
}
.book-title-text {
    font-family: var(--font-heading);
    font-weight: 700;
    font-size: 0.8rem;
    color: #2D1E10;
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 4px;
}
.book-edisi-tag {
    font-size: 0.73rem;
    color: var(--purple);
    font-weight: 800;
    display: block;
}
.book-date-tag {
    font-size: 0.68rem;
    color: #795548;
    margin-top: 2px;
    font-weight: 500;
}
</style>

<!-- Bookshelf Main Section -->
<section class="py-5 py-md-6 bookshelf-section" id="shelfSection">
    <div class="container">

        <!-- Section Header -->
        <div class="text-center mb-4">
            <span class="section-tag mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                Perpustakaan Digital Mutu
            </span>
            <h2 class="section-title">Rak Pengarsipan Buletin JAMUS</h2>
            <p class="section-desc mx-auto">
                Koleksi buletin penjaminan mutu yang disusun rapi di atas rak buku per tahun terbit. Pilih rak tertentu untuk memfokuskan bacaan atau jelajahi seluruh koleksi.
            </p>

            <!-- Instant Filter Pills (Tanpa Reload / Tanpa Loncat Scroll) -->
            <div class="mt-4 d-flex justify-content-center">
                <div class="shelf-filter-wrapper">
                    <button type="button"
                            class="shelf-filter-btn <?= ($initial_filter === 'all') ? 'active' : '' ?>"
                            data-filter="all"
                            title="Tampilkan seluruh koleksi">
                        <span>Semua Koleksi</span>
                        <span class="filter-count-badge"><?= count($all_buletin_list) ?></span>
                    </button>
                    <?php foreach ($all_years_buletin as $y): ?>
                    <button type="button"
                            class="shelf-filter-btn <?= ($initial_filter === (string)$y) ? 'active' : '' ?>"
                            data-filter="<?= $y ?>"
                            title="Tampilkan Koleksi Tahun <?= $y ?>">
                        <span>Tahun <?= $y ?></span>
                        <span class="filter-count-badge"><?= count($buletin_by_year[$y] ?? []) ?></span>
                    </button>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>

        <?php if (empty($all_buletin_list)): ?>
        <!-- Empty State -->
        <div class="text-center py-5 bg-white rounded-4 border p-4 shadow-sm" style="max-width:500px;margin:0 auto;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="var(--text-muted)" width="64" height="64" class="mb-3 d-block mx-auto">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
            </svg>
            <h5 style="font-weight:700;color:var(--navy);">Koleksi Buletin Masih Kosong</h5>
            <p class="text-muted small">Belum ada edisi buletin yang dipublikasikan saat ini.</p>
        </div>

        <?php else: ?>

        <!-- Lemari Buku Utama (Bookcase Cabinet) -->
        <div class="bookcase-cabinet mt-4">

            <?php foreach ($buletin_by_year as $tahun => $books): 
                $is_visible = ($initial_filter === 'all' || $initial_filter === (string)$tahun);
            ?>
            <!-- Rak Buku Tahun <?= e($tahun) ?> -->
            <div class="bookcase-shelf-unit"
                 data-year="<?= e($tahun) ?>"
                 id="rak-<?= e($tahun) ?>"
                 style="<?= $is_visible ? '' : 'display:none;' ?>">

                <!-- Plakat Kuningan Nama Koleksi -->
                <div class="shelf-header-beam">
                    <div class="shelf-brass-plaque">
                        <div class="plaque-screw tl"></div>
                        <div class="plaque-screw tr"></div>
                        <div class="plaque-screw bl"></div>
                        <div class="plaque-screw br"></div>
                        <span style="color:#6B4310;font-size:0.85rem;">✦</span>
                        <span class="plaque-title">KOLEKSI TAHUN <?= e($tahun) ?></span>
                        <span class="plaque-count-pill"><?= count($books) ?> Edisi</span>
                    </div>

                    <div class="shelf-meta-hint d-none d-sm-flex">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Klik sampul buletin untuk membuka &amp; membaca PDF
                    </div>
                </div>

                <!-- Kontainer Barisan Buku & Ambalan Kayu -->
                <div class="shelf-books-container">
                    <div class="row row-cols-2 row-cols-sm-3 row-cols-md-4 row-cols-lg-6 g-3 g-md-4">
                        <?php foreach ($books as $b):
                            $cov_file = getOrGenerateBuletinCover($b['cover_path'] ?? null, $b['file_path'] ?? null, (int)$b['id']);
                            $palette = COVER_PALETTE[$b['id'] % count(COVER_PALETTE)];
                            $has_cover = !empty($cov_file);
                            $cover_url = $has_cover ? SITE_URL . '/uploads/buletin/covers/' . $cov_file : null;
                            $pdf_url   = SITE_URL . '/uploads/buletin/' . $b['file_path'];
                        ?>
                        <div class="col">
                            <a href="<?= $pdf_url ?>"
                               target="_blank"
                               class="book-card"
                               title="<?= e($b['judul']) ?> – <?= e($b['edisi']) ?>">

                                <!-- Badan Buku 3D -->
                                <div class="book-spine-shell">
                                    <?php if ($has_cover): ?>
                                        <img src="<?= $cover_url ?>"
                                             alt="Cover <?= e($b['edisi']) ?>"
                                             class="book-cover-img"
                                             loading="lazy">
                                    <?php else: ?>
                                        <div class="book-placeholder-cover"
                                             style="background:linear-gradient(160deg, <?= $palette['bg'] ?> 0%, <?= $palette['stripe'] ?> 100%);">
                                            <div class="cover-deco-stripe"
                                                 style="background:repeating-linear-gradient(
                                                     -45deg,
                                                     <?= $palette['accent'] ?> 0px,
                                                     <?= $palette['accent'] ?> 2px,
                                                     transparent 2px,
                                                     transparent 14px
                                                 );"></div>
                                            <div class="cover-logo-text">JAMUS</div>
                                            <div class="cover-edisi-badge"><?= e($b['edisi']) ?></div>
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
                                    <div class="book-title-text"><?= e($b['judul']) ?></div>
                                    <span class="book-edisi-tag"><?= e($b['edisi']) ?></span>
                                    <?php if ($b['tanggal_terbit']): ?>
                                    <div class="book-date-tag">
                                        <?= date('M Y', strtotime($b['tanggal_terbit'])) ?> • <?= e($b['periode_akademik']) ?>
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
            <?php endforeach; ?>

        </div><!-- /.bookcase-cabinet -->

        <!-- Action strip -->
        <div class="text-center mt-5">
            <a href="<?= SITE_URL ?>/spmi.php#dokumen-spmi"
               class="btn-hero-secondary"
               style="background:#fff;color:var(--navy);border:1.5px solid rgba(139,111,71,0.25);text-decoration:none;padding:0.65rem 1.6rem;font-size:0.9rem;border-radius:25px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                Lihat Seluruh Buletin di Dokumen Mutu &rarr;
            </a>
        </div>

        <?php endif; ?>

    </div>
</section>

<!-- Info Strip at bottom -->
<section class="py-4" style="background:#ffffff;border-top:1px solid var(--border);">
    <div class="container">
        <div class="row g-4 align-items-center">
            <div class="col-md-7">
                <div style="display:flex;align-items:flex-start;gap:1rem;">
                    <div style="width:40px;height:40px;background:rgba(123,31,162,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="var(--purple)" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                        </svg>
                    </div>
                    <div>
                        <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.95rem;margin-bottom:2px;">Ingin Berkontribusi di Buletin JAMUS?</div>
                        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;line-height:1.6;">
                            LPM UNIKA membuka kesempatan bagi dosen, mahasiswa, dan tenaga kependidikan untuk mengirimkan artikel opini, laporan kegiatan, atau ringkasan best practice program studi.
                        </p>
                    </div>
                </div>
            </div>
            <div class="col-md-5 text-md-end">
                <a href="<?= SITE_URL ?>/kontak.php" class="btn-hero-primary" style="text-decoration:none;background:var(--purple);border:none;padding:0.65rem 1.4rem;font-size:0.88rem;border-radius:25px;">
                    Kirim Naskah ke LPM &rarr;
                </a>
            </div>
        </div>
    </div>
</section>

<!-- Client-Side Instant Bookshelf Filter (Zero-Reload / Zero-Scroll Jump) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const filterButtons = document.querySelectorAll('.shelf-filter-btn');
    const shelfUnits = document.querySelectorAll('.bookcase-shelf-unit');

    if (!filterButtons.length || !shelfUnits.length) return;

    filterButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            const filterValue = this.getAttribute('data-filter');

            // 1. Update status aktif tombol
            filterButtons.forEach(b => b.classList.remove('active'));
            this.classList.add('active');

            // 2. Filter rak buku per tahun secara instan tanpa reload halaman
            shelfUnits.forEach(shelf => {
                const shelfYear = shelf.getAttribute('data-year');
                if (filterValue === 'all' || filterValue === shelfYear) {
                    shelf.style.display = 'block';
                    shelf.classList.add('fade-enter');
                } else {
                    shelf.style.display = 'none';
                    shelf.classList.remove('fade-enter');
                }
            });

            // 3. Perbarui URL browser secara hening tanpa reload & tanpa scrolling ke atas
            const currentUrl = new URL(window.location.href);
            if (filterValue === 'all') {
                currentUrl.searchParams.delete('tahun');
            } else {
                currentUrl.searchParams.set('tahun', filterValue);
            }
            window.history.replaceState({ tahun: filterValue }, '', currentUrl.toString());
        });
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
