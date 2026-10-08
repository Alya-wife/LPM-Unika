<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Knowledge Center – Pusat Pengetahuan Mutu';
$meta_desc  = 'Knowledge Center LPM UNIKA: Tanya Jawab (FAQ), Glosarium Mutu, Buletin JAMUS, dan Artikel Penjaminan Mutu Perguruan Tinggi.';
$db = getDB();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Pusat Edukasi &amp; Pengetahuan
        </div>
        <h1 class="page-banner-title">Knowledge Center</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Knowledge</span>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/knowledge-sections.php';

// Ambil susunan seksi dari Visual Page Builder
$kc_blocks = null;
try {
    $stmt_k = $db->query("SELECT blocks_json FROM pages WHERE slug = 'knowledge'");
    $row_k = $stmt_k->fetch();
    if (!empty($row_k['blocks_json'])) {
        $kc_blocks = json_decode($row_k['blocks_json'], true);
    }
} catch (Exception $e) {}

if (!empty($kc_blocks) && is_array($kc_blocks)) {
    foreach ($kc_blocks as $block) {
        if ((isset($block['status']) && $block['status'] === 'draft') || (isset($block['is_visible']) && !$block['is_visible'])) continue;
        renderKnowledgeSection($block['type'], $block, false);
    }
} else {
    // Alur Default 5 Seksi Knowledge Center
    renderKnowledgeSection('knowledge_pengantar', [], false);
    renderKnowledgeSection('knowledge_kalender', [], false);
    renderKnowledgeSection('knowledge_buletin', [], false);
    renderKnowledgeSection('knowledge_glosarium', [], false);
    renderKnowledgeSection('knowledge_faq', [], false);
}
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
