<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Berita & Kegiatan';
$meta_desc  = 'Informasi terkini seputar kegiatan, program, dan pencapaian Lembaga Penjaminan Mutu SCU.';

$db = getDB();

// Filter Tipe
$tipe_filter  = trim($_GET['tipe'] ?? '');
$allowed_tipe = ['Berita', 'Sosialisasi', 'Penghargaan', 'Kegiatan LPM'];
if (!in_array($tipe_filter, $allowed_tipe)) $tipe_filter = '';

// Pagination
$per_page    = 9;
$current_pg  = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$offset      = ($current_pg - 1) * $per_page;

if ($tipe_filter) {
    $count_stmt = $db->prepare("SELECT COUNT(*) FROM berita WHERE tipe = ?");
    $count_stmt->execute([$tipe_filter]);
    $total = $count_stmt->fetchColumn();

    $stmt = $db->prepare("SELECT * FROM berita WHERE tipe = ? ORDER BY tanggal_publikasi DESC, created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $tipe_filter);
    $stmt->bindValue(2, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(3, $offset, PDO::PARAM_INT);
    $stmt->execute();
} else {
    $total = $db->query("SELECT COUNT(*) FROM berita")->fetchColumn();
    $stmt = $db->prepare("SELECT * FROM berita ORDER BY tanggal_publikasi DESC, created_at DESC LIMIT ? OFFSET ?");
    $stmt->bindValue(1, $per_page, PDO::PARAM_INT);
    $stmt->bindValue(2, $offset, PDO::PARAM_INT);
    $stmt->execute();
}

$pages = (int)ceil($total / $per_page);
$berita_list = $stmt->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Informasi Terkini
        </div>
        <h1 class="page-banner-title">Berita &amp; Kegiatan LPM</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Berita &amp; Kegiatan</span>
        </div>
    </div>
</div>

<section class="py-5 py-md-6">
    <div class="container">
        <!-- Filter Tipe Publikasi -->
        <div class="d-flex gap-2 flex-wrap mb-4 justify-content-center">
            <a href="berita.php" class="btn-action <?= !$tipe_filter ? 'btn-edit' : '' ?>" style="<?= !$tipe_filter ? '' : 'background:#fff;border:1.5px solid var(--border);color:var(--text-muted);' ?>padding:0.5rem 1.2rem;font-size:0.85rem;">
                Semua
            </a>
            <?php foreach ($allowed_tipe as $tp): ?>
            <a href="berita.php?tipe=<?= urlencode($tp) ?>" class="btn-action <?= $tipe_filter === $tp ? 'btn-edit' : '' ?>" style="<?= $tipe_filter === $tp ? '' : 'background:#fff;border:1.5px solid var(--border);color:var(--text-muted);' ?>padding:0.5rem 1.2rem;font-size:0.85rem;">
                <?= e($tp) ?>
            </a>
            <?php endforeach; ?>
        </div>

        <?php if (empty($berita_list)): ?>
        <div style="text-align:center;padding:4rem 2rem;">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="56" height="56" style="opacity:0.2;display:block;margin:0 auto 1rem;color:var(--text-muted);">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
            </svg>
            <h3 style="font-family:var(--font-heading);font-size:1.2rem;color:var(--navy);margin-bottom:0.5rem;">Belum Ada Berita</h3>
            <p style="color:var(--text-muted);font-size:0.9rem;">Tambahkan berita melalui <a href="admin/" class="text-purple font-weight-600">Admin Panel</a></p>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($berita_list as $b): ?>
            <div class="col-md-6 col-lg-4">
                <a href="berita-detail.php?slug=<?= e($b['slug']) ?>" class="card-lpm d-block text-decoration-none" style="height:100%;">
                    <div class="card-img-wrap">
                        <?php if ($b['gambar'] && file_exists(__DIR__ . '/uploads/berita/' . $b['gambar'])): ?>
                            <img src="<?= SITE_URL ?>/uploads/berita/<?= e($b['gambar']) ?>" alt="<?= e($b['judul']) ?>">
                        <?php else: ?>
                            <div style="width:100%;height:200px;background:linear-gradient(135deg,var(--navy-mid),var(--purple-dark));display:flex;align-items:center;justify-content:center;">
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
                                <?= formatTanggal($b['tanggal_publikasi'] ?: $b['created_at']) ?>
                            </span>
                        </div>
                        <div class="card-title-lpm"><?= e($b['judul']) ?></div>
                        <div class="card-desc-lpm"><?= truncate($b['konten'], 130) ?></div>
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

        <!-- Pagination -->
        <?php if ($pages > 1): ?>
        <div class="pagination-lpm mt-5">
            <?php if ($current_pg > 1): ?>
            <a href="berita.php?page=<?= $current_pg - 1 ?>" class="page-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                </svg>
            </a>
            <?php endif; ?>
            <?php for ($i = 1; $i <= $pages; $i++): ?>
            <a href="berita.php?page=<?= $i ?>" class="page-btn <?= $i === $current_pg ? 'active' : '' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($current_pg < $pages): ?>
            <a href="berita.php?page=<?= $current_pg + 1 ?>" class="page-btn">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                </svg>
            </a>
            <?php endif; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
