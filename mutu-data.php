<?php
require_once __DIR__ . '/config/database.php';
// Halaman Mutu & Data di-drop (tidak terpakai) - dialihkan ke Akreditasi
header("Location: " . SITE_URL . "/akreditasi.php", true, 301);
exit;
$page_title = 'Mutu & Data';
$meta_desc  = 'Dashboard Mutu, Indikator Kinerja Utama (IKU/IKT), dan Data PDDikti Universitas Katolik Soegijapranata.';

$db = getDB();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Statistik &amp; Kinerja
        </div>
        <h1 class="page-banner-title">Mutu &amp; Data</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Mutu &amp; Data</span>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/mutu-data-sections.php';

// Ambil susunan seksi dari Visual Page Builder
$mutu_blocks = null;
try {
    $stmt_m = $db->query("SELECT blocks_json FROM pages WHERE slug = 'mutu-data'");
    $row_m = $stmt_m->fetch();
    if (!empty($row_m['blocks_json'])) {
        $mutu_blocks = json_decode($row_m['blocks_json'], true);
    }
} catch (Exception $e) {}

if (!empty($mutu_blocks) && is_array($mutu_blocks)) {
    foreach ($mutu_blocks as $block) {
        if ((isset($block['status']) && $block['status'] === 'draft') || (isset($block['is_visible']) && !$block['is_visible'])) continue;
        renderMutuDataSection($block['type'], $block, false);
    }
} else {
    // Alur Default 2 Seksi Mutu & Data
    renderMutuDataSection('mutu_data_dashboard', [], false);
    renderMutuDataSection('mutu_data_iku', [], false);
}
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
