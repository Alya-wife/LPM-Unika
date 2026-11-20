<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Akreditasi Institusi & Program Studi';
$meta_desc  = 'Status Akreditasi Institusi dan Program Studi Universitas Katolik Soegijapranata (UNIKA) oleh BAN-PT dan Lembaga Akreditasi Mandiri (LAM).';

$db = getDB();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/akreditasi-sections.php';

$akreditasi_banner_sub = getPengaturan('akreditasi_banner_sub', '');
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Pengakuan Mutu Nasional
        </div>
        <h1 class="page-banner-title">Akreditasi Institusi &amp; Program Studi</h1>
        <?php if ($akreditasi_banner_sub): ?>
        <p class="text-white-50 mt-2 mb-0" style="max-width:700px;font-size:0.95rem;line-height:1.6;"><?= htmlspecialchars($akreditasi_banner_sub) ?></p>
        <?php endif; ?>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Akreditasi</span>
        </div>
    </div>
</div>

<?php
// Ambil susunan seksi dari Visual Page Builder
$akreditasi_blocks = null;
try {
    $stmt_ak = $db->query("SELECT blocks_json FROM pages WHERE slug = 'akreditasi'");
    $row_ak = $stmt_ak->fetch();
    if (!empty($row_ak['blocks_json'])) {
        $akreditasi_blocks = json_decode($row_ak['blocks_json'], true);
    }
} catch (Exception $e) {}

if (!empty($akreditasi_blocks) && is_array($akreditasi_blocks)) {
    foreach ($akreditasi_blocks as $block) {
        if ((isset($block['status']) && $block['status'] === 'draft') || (isset($block['is_visible']) && !$block['is_visible'])) continue;
        if ($block['type'] === 'akreditasi_faq') continue;
        renderAkreditasiSection($block['type'], $block);
    }
} else {
    // Alur Default Seksi Akreditasi
    renderAkreditasiSection('akreditasi_institusi');
    renderAkreditasiSection('akreditasi_statistik');
    renderAkreditasiSection('akreditasi_lam');
    renderAkreditasiSection('akreditasi_prodi');
}

// Banner Rujukan Terpadu ke Menu FAQ
?>
<section class="py-5" style="background:#F8FAFC;border-top:1px solid var(--border);">
    <div class="container">
        <div class="card p-4 p-md-5 border-0 rounded-4 text-center position-relative overflow-hidden shadow-sm" style="background:linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);color:#fff;">
            <div style="max-width:680px;margin:0 auto;position:relative;z-index:2;">
                <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width:52px;height:52px;background:rgba(255,255,255,0.1);border-radius:50%;color:#FFD54F;font-size:1.5rem;">
                    <i class="bi bi-question-circle-fill"></i>
                </div>
                <h3 class="fw-bold mb-2 text-white" style="font-family:var(--font-heading);">Punya Pertanyaan Seputar Akreditasi?</h3>
                <p class="text-white-50 mb-4" style="line-height:1.7;font-size:0.95rem;">
                    Seluruh tanya jawab resmi seputar akreditasi institusi, akreditasi program studi LAM &amp; BAN-PT, serta legalisir dokumen kini telah dipindahkan dan terpusat di menu <strong>Tanya Jawab (FAQ) Mutu</strong>.
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="<?= SITE_URL ?>/faq.php?kategori=Akreditasi#faq-section" class="btn btn-warning fw-bold px-4 py-2 rounded-pill text-dark shadow-sm">
                        <i class="bi bi-patch-question-fill me-1"></i> Buka FAQ Akreditasi &rarr;
                    </a>
                    <a href="<?= SITE_URL ?>/layanan.php" class="btn btn-outline-light px-4 py-2 rounded-pill">
                        <i class="bi bi-headset me-1"></i> Hubungi Layanan LPM
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
<?php
// Render Modal Dokumen Institusi & Modal Dokumen Masing-masing LAM
renderAkreditasiModals();

require_once __DIR__ . '/includes/footer.php';
?>
