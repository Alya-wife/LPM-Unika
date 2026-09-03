<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Buletin JAMUS – Publikasi Mutu LPM UNIKA';
$meta_desc  = 'Buletin Jaminan Mutu Universitas (JAMUS) LPM UNIKA – koleksi lengkap edisi buletin penjaminan mutu Universitas Katolik Soegijapranata.';

$db = getDB();

// Palette warna untuk placeholder cover (bergantian)
define('COVER_PALETTE', [
    ['bg' => '#4A148C', 'stripe' => '#7B1FA2', 'accent' => '#CE93D8'],
    ['bg' => '#0D47A1', 'stripe' => '#1565C0', 'accent' => '#90CAF9'],
    ['bg' => '#1B5E20', 'stripe' => '#2E7D32', 'accent' => '#A5D6A7'],
    ['bg' => '#B71C1C', 'stripe' => '#C62828', 'accent' => '#EF9A9A'],
    ['bg' => '#E65100', 'stripe' => '#BF360C', 'accent' => '#FFCC80'],
    ['bg' => '#006064', 'stripe' => '#00838F', 'accent' => '#80DEEA'],
]);

$buletin_list = $db->query("SELECT * FROM buletin WHERE is_aktif = 1 ORDER BY tanggal_terbit DESC, id DESC")->fetchAll();

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
        <h1 class="page-banner-title">Buletin JAMUS</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/knowledge.php">Knowledge</a>
            <span>/</span>
            <span class="current">Buletin JAMUS</span>
        </div>
    </div>
</div>

<style>
/* ── Bookshelf styles ── */
.bookshelf-section { background: #F1F3F7; }

.shelf-rack {
    position: relative;
    padding-bottom: 26px;
    margin-bottom: 16px;
}
.shelf-rack::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: -12px;
    right: -12px;
    height: 22px;
    background: linear-gradient(180deg, #8B6F47 0%, #6B4F2B 60%, #4A3520 100%);
    border-radius: 0 0 6px 6px;
    box-shadow: 0 6px 16px rgba(74,53,32,0.35);
}
.shelf-rack::before {
    content: '';
    position: absolute;
    bottom: 22px;
    left: -12px;
    right: -12px;
    height: 4px;
    background: linear-gradient(90deg, rgba(255,255,255,0.25) 0%, rgba(255,255,255,0.06) 100%);
    border-radius: 2px 2px 0 0;
}

.book-card {
    cursor: pointer;
    transition: transform 0.28s cubic-bezier(0.175, 0.885, 0.32, 1.275),
                box-shadow 0.28s ease;
    text-decoration: none;
    display: block;
    outline: none;
}
.book-card:hover,
.book-card:focus {
    transform: translateY(-18px) scale(1.03);
    text-decoration: none;
}
.book-cover-wrap {
    position: relative;
    border-radius: 3px 8px 8px 3px;
    overflow: hidden;
    aspect-ratio: 3/4;
    box-shadow:
        -3px 0 0 #bbb,
        -6px 0 0 #aaa,
        4px 6px 20px rgba(0,0,0,0.28),
        8px 12px 30px rgba(0,0,0,0.14);
}
/* Spine illusion */
.book-cover-wrap::before {
    content: '';
    position: absolute;
    top: 0; left: 0;
    width: 12px; height: 100%;
    background: rgba(0,0,0,0.25);
    z-index: 2;
}
/* Glossy shine */
.book-cover-wrap::after {
    content: '';
    position: absolute;
    top: 0; left: 10%;
    width: 30%; height: 100%;
    background: linear-gradient(105deg, rgba(255,255,255,0.28) 0%, rgba(255,255,255,0) 80%);
    z-index: 3;
    pointer-events: none;
}

.book-cover-img {
    width: 100%; height: 100%;
    object-fit: cover;
    display: block;
}

.book-placeholder-cover {
    width: 100%; height: 100%;
    display: flex; flex-direction: column;
    align-items: center; justify-content: center;
    padding: 14px 10px;
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
    font-size: clamp(1rem, 2vw, 1.3rem);
    color: #fff;
    text-align: center;
    letter-spacing: 1.5px;
    line-height: 1.15;
    z-index: 1;
    text-shadow: 0 2px 8px rgba(0,0,0,0.4);
    word-break: break-word;
}
.book-placeholder-cover .cover-edisi-badge {
    background: rgba(255,255,255,0.2);
    border: 1px solid rgba(255,255,255,0.4);
    color: #fff;
    font-size: 0.65rem;
    font-weight: 700;
    padding: 3px 8px;
    border-radius: 20px;
    z-index: 1;
    margin-top: 8px;
    text-align: center;
    max-width: 90%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
}

.book-meta {
    padding: 10px 2px 0;
    text-align: center;
}
.book-meta .book-title {
    font-family: var(--font-heading);
    font-weight: 700;
    font-size: 0.8rem;
    color: var(--navy);
    line-height: 1.35;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    margin-bottom: 4px;
}
.book-meta .book-edisi {
    font-size: 0.72rem;
    color: var(--purple);
    font-weight: 700;
    display: block;
}

/* Download button on hover */
.book-download-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(10,25,47,0.85) 0%, rgba(10,25,47,0) 55%);
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding-bottom: 14px;
    opacity: 0;
    transition: opacity 0.25s ease;
    z-index: 10;
    border-radius: 0 8px 8px 0;
}
.book-card:hover .book-download-overlay {
    opacity: 1;
}
.book-download-btn {
    background: #FBBF24;
    color: #1a1a1a;
    font-weight: 800;
    font-size: 0.7rem;
    padding: 5px 12px;
    border-radius: 20px;
    letter-spacing: 0.5px;
    box-shadow: 0 2px 8px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    gap: 5px;
}

/* Empty state */
.bookshelf-empty {
    text-align: center;
    padding: 4rem 2rem;
    background: #fff;
    border-radius: var(--radius-xl);
    border: 2px dashed var(--border);
}

/* Section wood wall background */
.shelf-wall {
    background:
        repeating-linear-gradient(
            180deg,
            rgba(139,111,71,0.05) 0px,
            rgba(139,111,71,0.05) 1px,
            transparent 1px,
            transparent 40px
        ),
        #F1F3F7;
}
</style>

<!-- Intro strip -->
<div style="background:linear-gradient(135deg, #4A148C, #7B1FA2);padding:2rem 0;border-bottom:4px solid #FBBF24;">
    <div class="container">
        <div class="row align-items-center g-4">
            <div class="col-md-8">
                <p style="font-size:1rem;color:rgba(255,255,255,0.9);line-height:1.75;margin:0;">
                    <strong style="color:#FBBF24;">Buletin Jaminan Mutu Universitas (JAMUS)</strong> merupakan publikasi berkala Lembaga Penjaminan Mutu yang menyajikan perkembangan SPMI, best practice program studi, laporan AMI, serta artikel opini dari para pemerhati mutu di UNIKA Soegijapranata.
                </p>
            </div>
            <div class="col-md-4 text-md-end">
                <a href="<?= SITE_URL ?>/dokumen.php?kategori=Buletin+JAMUS" class="btn-hero-secondary" style="background:rgba(255,255,255,0.12);border:1.5px solid rgba(255,255,255,0.4);color:#fff;text-decoration:none;padding:0.65rem 1.4rem;font-size:0.88rem;">
                    Lihat di Pusat Dokumen &rarr;
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Bookshelf Main Section -->
<section class="py-5 py-md-6 shelf-wall bookshelf-section">
    <div class="container">

        <?php if (empty($buletin_list)): ?>
        <!-- Empty State -->
        <div class="bookshelf-empty">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="0.9" stroke="var(--text-muted)" width="72" height="72" class="mb-4" style="display:block;margin:0 auto;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
            </svg>
            <h4 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);margin-bottom:0.5rem;">Rak Buletin Masih Kosong</h4>
            <p style="color:var(--text-muted);font-size:0.95rem;max-width:450px;margin:0 auto 1.5rem;">
                Edisi Buletin JAMUS belum diunggah. Silakan hubungi admin LPM untuk informasi lebih lanjut.
            </p>
            <a href="<?= SITE_URL ?>/kontak.php" class="btn-hero-primary" style="text-decoration:none;background:var(--purple);border:none;padding:0.65rem 1.5rem;">
                Hubungi LPM
            </a>
        </div>

        <?php else: ?>

        <!-- Section Header -->
        <div class="text-center mb-5">
            <span class="section-tag mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                </svg>
                <?= count($buletin_list) ?> Edisi Tersedia
            </span>
            <h2 class="section-title">Rak Buletin JAMUS</h2>
            <p class="section-desc mx-auto">Klik sampul buletin untuk membuka dan mengunduh PDF edisi yang diinginkan.</p>
        </div>

        <!-- Bookshelf Grid -->
        <?php
        // Split into rows of 6 books each (one "shelf")
        $per_row = 6;
        $rows    = array_chunk($buletin_list, $per_row);
        ?>

        <?php foreach ($rows as $row_books): ?>
        <div class="shelf-rack px-3 mx-auto" style="max-width:1100px;">
            <div class="row row-cols-3 row-cols-sm-4 row-cols-md-5 row-cols-lg-6 g-3 g-md-4">
                <?php foreach ($row_books as $idx => $b):
                    $palette = COVER_PALETTE[$b['id'] % count(COVER_PALETTE)];
                    $has_cover = !empty($b['cover_path']);
                    $cover_url = $has_cover
                        ? SITE_URL . '/uploads/buletin/covers/' . $b['cover_path']
                        : null;
                    $pdf_url   = SITE_URL . '/uploads/buletin/' . $b['file_path'];
                ?>
                <div class="col">
                    <a href="<?= $pdf_url ?>"
                       target="_blank"
                       class="book-card"
                       title="<?= e($b['judul']) ?> – <?= e($b['edisi']) ?>">

                        <!-- Book Cover -->
                        <div class="book-cover-wrap">
                            <?php if ($has_cover): ?>
                                <img src="<?= $cover_url ?>"
                                     alt="Cover <?= e($b['edisi']) ?>"
                                     class="book-cover-img"
                                     loading="lazy">
                            <?php else: ?>
                                <!-- Placeholder cover with color & text -->
                                <div class="book-placeholder-cover"
                                     style="background:linear-gradient(160deg, <?= $palette['bg'] ?> 0%, <?= $palette['stripe'] ?> 100%);">
                                    <!-- Decorative diagonal stripes -->
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

                            <!-- Download Overlay on hover -->
                            <div class="book-download-overlay">
                                <div class="book-download-btn">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="11" height="11">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    Buka PDF
                                </div>
                            </div>
                        </div>

                        <!-- Book Meta below cover -->
                        <div class="book-meta">
                            <div class="book-title"><?= e($b['judul']) ?></div>
                            <span class="book-edisi"><?= e($b['edisi']) ?></span>
                            <?php if ($b['tanggal_terbit']): ?>
                            <div style="font-size:0.68rem;color:var(--text-muted);margin-top:2px;">
                                <?= date('M Y', strtotime($b['tanggal_terbit'])) ?>
                            </div>
                            <?php endif; ?>
                        </div>

                    </a>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- Total & action strip -->
        <div class="text-center mt-5">
            <a href="<?= SITE_URL ?>/dokumen.php?kategori=Buletin+JAMUS"
               class="btn-hero-secondary"
               style="background:#fff;color:var(--navy);border-color:var(--border);text-decoration:none;padding:0.65rem 1.6rem;font-size:0.9rem;">
                Lihat di Pusat Dokumen &rarr;
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
                <a href="<?= SITE_URL ?>/kontak.php" class="btn-hero-primary" style="text-decoration:none;background:var(--purple);border:none;padding:0.65rem 1.4rem;font-size:0.88rem;">
                    Kirim Naskah ke LPM &rarr;
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
