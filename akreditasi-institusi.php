<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Akreditasi Institusi – Peringkat Unggul BAN-PT';
$meta_desc  = 'Status Akreditasi Institusi Universitas Katolik Soegijapranata (UNIKA) terakreditasi UNGGUL oleh Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT).';

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
            Status Resmi BAN-PT
        </div>
        <h1 class="page-banner-title">Akreditasi Institusi</h1>
        <p class="text-white-50 mt-2 mb-0" style="max-width:700px;font-size:0.95rem;line-height:1.6;">
            Universitas Katolik Soegijapranata meraih peringkat akreditasi tertinggi <strong>UNGGUL</strong> berdasarkan Surat Keputusan Badan Akreditasi Nasional Perguruan Tinggi.
        </p>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/akreditasi.php">Akreditasi</a>
            <span>/</span>
            <span class="current">Akreditasi Institusi</span>
        </div>
    </div>
</div>

<?php
// Render Seksi Utama Akreditasi Institusi
renderAkreditasiSection('akreditasi_institusi');
renderAkreditasiSection('akreditasi_faq');
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
