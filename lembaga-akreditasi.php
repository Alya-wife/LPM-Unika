<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Lembaga Akreditasi – BAN-PT & LAM';
$meta_desc  = 'Direktori Lembaga Akreditasi Nasional (BAN-PT dan Lembaga Akreditasi Mandiri) yang menaungi program studi di Universitas Katolik Soegijapranata.';

$db = getDB();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/akreditasi-sections.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Badan Akreditasi Resmi
        </div>
        <h1 class="page-banner-title">Lembaga Akreditasi</h1>
        <p class="text-white-50 mt-2 mb-0" style="max-width:700px;font-size:0.95rem;line-height:1.6;">
            Program studi di lingkungan UNIKA Soegijapranata diakreditasi oleh Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT) serta Lembaga Akreditasi Mandiri (LAM) sesuai rumpun keilmuan.
        </p>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/akreditasi.php">Akreditasi</a>
            <span>/</span>
            <span class="current">Lembaga Akreditasi</span>
        </div>
    </div>
</div>

<?php
// Render Seksi Lembaga Akreditasi (BAN-PT & LAM)
renderAkreditasiSection('akreditasi_lam');
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
