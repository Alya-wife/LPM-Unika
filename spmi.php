<?php
require_once __DIR__ . '/config/database.php';

// Endpoint AJAX untuk filter & pencarian dokumen mutu SPMI secara realtime
if (isset($_GET['ajax']) && $_GET['ajax'] === 'dokumen') {
    require_once __DIR__ . '/includes/spmi-sections.php';
    handleSpmiDokumenAjax();
    exit;
}

$db = getDB();

// Tentukan tampilan (view): mengenal, ppepp, dokumen
$view = trim($_GET['view'] ?? '');
if (empty($view) && (isset($_GET['kategori']) || isset($_GET['tahun']))) {
    $view = 'dokumen';
}
if (!in_array($view, ['mengenal', 'ppepp', 'dokumen'])) {
    $view = 'mengenal'; // Default ke Mengenal SPMI
}

if ($view === 'mengenal') {
    $page_title   = 'Mengenal SPMI – Sistem Penjaminan Mutu Internal';
    $meta_desc    = 'Mengenal SPMI Universitas Katolik Soegijapranata, pengantar sistem penjaminan mutu internal, Portal SISTA, Portal SPMI Kemendikti, dan Portal E-PPEPP.';
    $banner_title = 'Mengenal SPMI &amp; Portal Mutu';
    $crumb_label  = 'Mengenal SPMI';
} elseif ($view === 'ppepp') {
    $page_title   = 'Siklus PPEPP – Sistem Penjaminan Mutu Internal';
    $meta_desc    = '5 Tahapan Siklus PPEPP SPMI UNIKA: Penetapan, Pelaksanaan, Evaluasi, Pengendalian, dan Peningkatan Mutu Berkelanjutan.';
    $banner_title = 'Siklus Mutu PPEPP';
    $crumb_label  = 'Siklus PPEPP';
} elseif ($view === 'dokumen') {
    $page_title   = 'Dokumen Mutu SPMI – Sistem Penjaminan Mutu Internal';
    $meta_desc    = 'Dokumen Mutu SPMI LPM UNIKA: Kebijakan Mutu, Manual Mutu, Standar Mutu, Formulir Mutu dengan pencarian dan filter tahun.';
    $banner_title = 'Dokumen Mutu SPMI';
    $crumb_label  = 'Dokumen SPMI';
}

// Filter kategori & tahun
$kategori_filter = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';
$tahun_filter    = isset($_GET['tahun']) ? trim($_GET['tahun']) : '';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/spmi-sections.php';

$spmi_banner_sub = getPengaturan('spmi_banner_subtitle', '');
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Sistem Penjaminan Mutu Internal
        </div>
        <h1 class="page-banner-title"><?= $banner_title ?></h1>
        <?php if ($spmi_banner_sub && $view === 'mengenal'): ?>
        <p class="text-white-50 mt-2 mb-0" style="max-width:700px;font-size:0.95rem;line-height:1.6;"><?= e($spmi_banner_sub) ?></p>
        <?php endif; ?>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/spmi.php">SPMI</a>
            <span>/</span>
            <span class="current"><?= htmlspecialchars($crumb_label) ?></span>
        </div>
    </div>
</div>

<!-- Sub-navigation Pills (Sticky) -->
<div class="bg-white border-bottom py-3 sticky-top" style="top:68px;z-index:90;box-shadow:0 2px 12px rgba(10,25,47,0.04);">
    <div class="container">
        <div class="d-flex justify-content-center flex-wrap gap-2">
            <a href="<?= SITE_URL ?>/spmi.php?view=mengenal"
               class="nav-pill-lpm <?= $view === 'mengenal' ? 'active' : 'inactive' ?>">
                <i class="bi bi-info-circle-fill"></i> Mengenal SPMI
            </a>
            <a href="<?= SITE_URL ?>/spmi.php?view=ppepp"
               class="nav-pill-lpm <?= $view === 'ppepp' ? 'active' : 'inactive' ?>">
                <i class="bi bi-arrow-repeat"></i> Siklus PPEPP
            </a>
            <a href="<?= SITE_URL ?>/spmi.php?view=dokumen"
               class="nav-pill-lpm <?= $view === 'dokumen' ? 'active' : 'inactive' ?>">
                <i class="bi bi-file-earmark-check-fill"></i> Dokumen SPMI
            </a>
        </div>
    </div>
</div>

<?php
// Ambil susunan seksi dari Visual Page Builder (jika ada kustomisasi)
$spmi_blocks = null;
try {
    $stmt_s = $db->query("SELECT blocks_json FROM pages WHERE slug = 'spmi'");
    $row_s = $stmt_s->fetch();
    if (!empty($row_s['blocks_json'])) {
        $spmi_blocks = json_decode($row_s['blocks_json'], true);
    }
} catch (Exception $e) {}

$view_block_map = [
    'mengenal' => ['spmi_pengantar', 'spmi_kemendikti'],
    'ppepp'    => ['spmi_ppepp'],
    'dokumen'  => ['spmi_dokumen'],
];

if (!empty($spmi_blocks) && is_array($spmi_blocks)) {
    $allowed_types = $view_block_map[$view] ?? [];
    $rendered_any = false;
    foreach ($spmi_blocks as $block) {
        if (isset($block['is_visible']) && !$block['is_visible']) continue;
        if (in_array($block['type'], $allowed_types)) {
            renderSpmiSection($block['type'], $block, false, $kategori_filter, $tahun_filter);
            $rendered_any = true;
        }
    }
    if (!$rendered_any) {
        renderDefaultSpmiView($view, $kategori_filter, $tahun_filter);
    }
} else {
    renderDefaultSpmiView($view, $kategori_filter, $tahun_filter);
}

if (!function_exists('renderDefaultSpmiView')) {
    function renderDefaultSpmiView($view, $kategori_filter, $tahun_filter) {
        if ($view === 'mengenal') {
            renderSpmiSection('spmi_pengantar', [], false, $kategori_filter, $tahun_filter);
            renderSpmiSection('spmi_kemendikti', [], false, $kategori_filter, $tahun_filter);
        } elseif ($view === 'ppepp') {
            renderSpmiSection('spmi_ppepp', [], false, $kategori_filter, $tahun_filter);
        } elseif ($view === 'dokumen') {
            renderSpmiSection('spmi_dokumen', [], false, $kategori_filter, $tahun_filter);
        }
    }
}
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
