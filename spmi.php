<?php
require_once __DIR__ . '/config/database.php';

// Endpoint AJAX untuk filter & pencarian dokumen mutu SPMI secara realtime
if (isset($_GET['ajax']) && $_GET['ajax'] === 'dokumen') {
    require_once __DIR__ . '/includes/spmi-sections.php';
    handleSpmiDokumenAjax();
    exit;
}

$page_title = 'SPMI – Sistem Penjaminan Mutu Internal';
$meta_desc  = 'Dokumen SPMI LPM UNIKA: Kebijakan Mutu, Manual Mutu, Standar Mutu, Formulir Mutu, dan Siklus PPEPP Universitas Katolik Soegijapranata.';

$db = getDB();

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
        <h1 class="page-banner-title">SPMI – Sistem Penjaminan Mutu Internal</h1>
        <?php if ($spmi_banner_sub): ?>
        <p class="text-white-50 mt-2 mb-0" style="max-width:700px;font-size:0.95rem;line-height:1.6;"><?= e($spmi_banner_sub) ?></p>
        <?php endif; ?>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">SPMI</span>
        </div>
    </div>
</div>

<?php
// Ambil susunan seksi dari Visual Page Builder
$spmi_blocks = null;
try {
    $stmt_s = $db->query("SELECT blocks_json FROM pages WHERE slug = 'spmi'");
    $row_s = $stmt_s->fetch();
    if (!empty($row_s['blocks_json'])) {
        $spmi_blocks = json_decode($row_s['blocks_json'], true);
    }
} catch (Exception $e) {}

if (!empty($spmi_blocks) && is_array($spmi_blocks)) {
    foreach ($spmi_blocks as $block) {
        if (isset($block['is_visible']) && !$block['is_visible']) continue;
        renderSpmiSection($block['type'], $block, false, $kategori_filter, $tahun_filter);
    }
} else {
    // Alur Default 4 Seksi SPMI
    renderSpmiSection('spmi_pengantar', [], false, $kategori_filter, $tahun_filter);
    renderSpmiSection('spmi_ppepp', [], false, $kategori_filter, $tahun_filter);
    renderSpmiSection('spmi_kemendikti', [], false, $kategori_filter, $tahun_filter);
    renderSpmiSection('spmi_dokumen', [], false, $kategori_filter, $tahun_filter);
}
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
