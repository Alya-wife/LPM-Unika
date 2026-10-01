<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Daftar Akreditasi Program Studi & Unduh SK/Sertifikat';
$meta_desc  = 'Pencarian status akreditasi seluruh program studi di UNIKA Soegijapranata dan unduhan berkas resmi SK serta Sertifikat akreditasi.';

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
            Program Studi &amp; Berkas Resmi
        </div>
        <h1 class="page-banner-title">Daftar Akreditasi Program Studi</h1>
        <p class="text-white-50 mt-2 mb-0" style="max-width:700px;font-size:0.95rem;line-height:1.6;">
            Cari peringkat akreditasi program studi Anda dan unduh salinan digital resmi Surat Keputusan (SK) serta Sertifikat Akreditasi.
        </p>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/akreditasi.php">Akreditasi</a>
            <span>/</span>
            <span class="current">Program Studi</span>
        </div>
    </div>
</div>

<?php
// Render Statistik Ringkasan & Tabel Seluruh Program Studi dengan Unduhan File
renderAkreditasiSection('akreditasi_statistik');
renderAkreditasiSection('akreditasi_prodi');
renderAkreditasiSection('akreditasi_dokumen');
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
