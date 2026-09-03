<?php
require_once __DIR__ . '/config/database.php';

$slug = trim($_GET['slug'] ?? '');
if (!$slug) {
    redirect(SITE_URL . '/');
}

$db = getDB();
$stmt = $db->prepare("SELECT * FROM pages WHERE slug = ?");
$stmt->execute([$slug]);
$page = $stmt->fetch();

if (!$page) {
    // 404 Not Found Page
    $page_title = 'Halaman Tidak Ditemukan';
    require_once __DIR__ . '/includes/header.php';
    require_once __DIR__ . '/includes/navbar.php';
    ?>
    <div class="page-banner">
        <div class="container position-relative">
            <h1 class="page-banner-title">Halaman Tidak Ditemukan</h1>
            <div class="breadcrumb-lpm">
                <a href="<?= SITE_URL ?>/">Beranda</a>
                <span>/</span>
                <span class="current">404</span>
            </div>
        </div>
    </div>
    <section class="py-5 text-center">
        <div class="container">
            <div style="max-width:550px;margin:3rem auto;padding:2.5rem;background:#fff;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);border:1px solid var(--border);">
                <div style="width:70px;height:70px;border-radius:50%;background:#FFEBEE;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#C62828" width="36" height="36">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                </div>
                <h3 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:0.75rem;">Halaman Tidak Ditemukan</h3>
                <p style="font-size:0.9rem;color:var(--text-muted);line-height:1.7;margin-bottom:1.5rem;">
                    Maaf, halaman dengan alamat yang Anda tuju tidak tersedia atau telah dipindahkan.
                </p>
                <a href="<?= SITE_URL ?>/" class="btn-hero-primary" style="display:inline-flex;padding:0.65rem 1.8rem;">
                    Kembali ke Beranda
                </a>
            </div>
        </div>
    </section>
    <?php
    require_once __DIR__ . '/includes/footer.php';
    exit;
}

$page_title = $page['judul'];
$meta_desc  = $page['ringkasan'] ?: $page['judul'] . ' - Lembaga Penjaminan Mutu Soegijapranata Catholic University (SCU).';

// Check if content is empty
$raw_konten = trim(strip_tags($page['konten'], '<img><iframe><video><audio>'));
$is_empty   = empty($raw_konten);

// Ambil halaman lain dalam kategori yang sama untuk sidebar navigasi
$stmt_related = $db->prepare("SELECT id, judul, slug FROM pages WHERE kategori = ? AND id != ? ORDER BY judul ASC LIMIT 6");
$stmt_related->execute([$page['kategori'], $page['id']]);
$related_pages = $stmt_related->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            <?= e($page['kategori']) ?>
        </div>
        <h1 class="page-banner-title"><?= e($page['judul']) ?></h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span><?= e($page['kategori']) ?></span>
            <span>/</span>
            <span class="current"><?= e(truncate($page['judul'], 30)) ?></span>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<section class="py-5 py-md-6" style="background:var(--bg-main);">
    <div class="container">
        <div class="row g-5">
            <!-- Content Column -->
            <div class="<?= !empty($related_pages) ? 'col-lg-8' : 'col-12' ?>">
                <div class="card-lpm p-4 p-md-5" style="border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);background:#fff;">
                    
                    <?php if ($is_empty): ?>
                    <!-- Modern Empty State UI -->
                    <div class="empty-state-box text-center py-5">
                        <div class="empty-state-icon-wrap" style="width:90px;height:90px;border-radius:50%;background:linear-gradient(135deg, rgba(10,25,47,0.08), rgba(106,27,154,0.1));display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;box-shadow:0 8px 25px rgba(10,25,47,0.08);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--navy)" width="44" height="44">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <span class="card-category-badge mb-2">Tahap Sinkronisasi Data</span>
                        <h3 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:0.75rem;">
                            Materi Sedang Disiapkan
                        </h3>
                        <p style="font-size:0.95rem;color:var(--text-muted);line-height:1.75;max-width:540px;margin:0 auto 1.75rem;">
                            Materi untuk halaman <strong>"<?= e($page['judul']) ?>"</strong> sedang dalam proses penyusunan oleh tim LPM UNIKA. Silakan hubungi kami untuk informasi lebih lanjut.
                        </p>
                        
                        <div class="d-flex gap-3 justify-content-center flex-wrap">
                            <a href="<?= SITE_URL ?>/layanan.php" class="btn-hero-primary" style="padding:0.65rem 1.5rem;font-size:0.875rem;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                                </svg>
                                Hubungi Tim LPM
                            </a>
                            <a href="<?= SITE_URL ?>/" class="btn-hero-secondary" style="padding:0.65rem 1.5rem;font-size:0.875rem;background:#fff;border:1.5px solid var(--border);color:var(--navy);">
                                Kembali ke Beranda
                            </a>
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- Rich Dynamic Content -->
                    <div class="page-rendered-content" style="line-height:1.85;color:var(--text-main);font-size:1rem;">
                        <?= $page['konten'] ?>
                    </div>
                    <?php endif; ?>

                    <div style="margin-top:2.5rem;padding-top:1.25rem;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;font-size:0.8rem;color:var(--text-muted);">
                        <div>Terakhir diperbarui: <?= formatTanggal($page['updated_at']) ?></div>
                        <a href="javascript:history.back()" style="color:var(--navy);font-weight:600;display:inline-flex;align-items:center;gap:4px;">
                            &larr; Kembali ke Sebelumnya
                        </a>
                    </div>
                </div>
            </div>

            <!-- Sidebar Navigation -->
            <?php if (!empty($related_pages)): ?>
            <div class="col-lg-4">
                <div style="position:sticky;top:100px;">
                    <div class="card-lpm p-4 mb-4" style="border:1px solid var(--border);border-radius:var(--radius-lg);background:#fff;">
                        <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:1rem;padding-bottom:0.6rem;border-bottom:2px solid var(--border);">
                            Menu <?= e($page['kategori']) ?>
                        </h5>
                        <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.6rem;">
                            <?php foreach ($related_pages as $rp): ?>
                            <li>
                                <a href="<?= SITE_URL ?>/page.php?slug=<?= e($rp['slug']) ?>" style="display:flex;align-items:center;justify-content:space-between;padding:0.6rem 0.85rem;background:var(--bg-main);border-radius:var(--radius-sm);color:var(--navy);font-size:0.85rem;font-weight:600;text-decoration:none;transition:var(--transition);" onmouseover="this.style.background='rgba(10,25,47,0.08)'" onmouseout="this.style.background='var(--bg-main)'">
                                    <span><?= e($rp['judul']) ?></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14" style="opacity:0.6;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                    </svg>
                                </a>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                    </div>

                    <!-- Contact Helper Box -->
                    <div class="card-lpm p-4" style="background:linear-gradient(135deg, var(--navy), var(--navy-mid));color:#fff;border-radius:var(--radius-lg);">
                        <div style="display:flex;align-items:center;gap:10px;margin-bottom:0.75rem;">
                            <div style="width:36px;height:36px;border-radius:50%;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="20" height="20">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                                </svg>
                            </div>
                            <h6 style="font-family:var(--font-heading);font-weight:700;color:#fff;margin:0;">Butuh Informasi?</h6>
                        </div>
                        <p style="font-size:0.8rem;color:rgba(255,255,255,0.8);line-height:1.6;margin-bottom:1rem;">
                            Hubungi tim LPM UNIKA untuk pertanyaan mengenai halaman ini atau kebutuhan konsultasi mutu.
                        </p>
                        <a href="<?= SITE_URL ?>/layanan.php" class="btn-hero-secondary w-100 text-center" style="padding:0.5rem 1rem;font-size:0.8rem;background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.25);">
                            Hubungi Kami &rarr;
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
