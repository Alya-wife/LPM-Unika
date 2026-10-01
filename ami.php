<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Audit Mutu Internal (AMI)';
$meta_desc  = 'Audit Mutu Internal (AMI) LPM UNIKA: Pedoman, Instrumen, Auditor, Jadwal Siklus, dan Hasil Audit Mutu Internal Universitas Katolik Soegijapranata.';

$db = getDB();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/ami-sections.php';

$ami_banner_sub = getPengaturan('ami_banner_subtitle', '');
$selected_tahun_ami = trim($_GET['tahun_ami'] ?? '');
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Evaluasi &amp; Pengendalian Mutu
        </div>
        <h1 class="page-banner-title">Pengantar Audit Mutu Internal (AMI)</h1>
        <?php if ($ami_banner_sub): ?>
        <p class="text-white-50 mt-2 mb-0" style="max-width:700px;font-size:0.95rem;line-height:1.6;"><?= e($ami_banner_sub) ?></p>
        <?php endif; ?>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/ami.php">AMI</a>
            <span>/</span>
            <span class="current">Pengantar AMI</span>
        </div>
    </div>
</div>

<?php
// Pengantar AMI: Hanya memuat definisi dan prinsip dasar AMI
renderAmiSection('ami_pengantar', [], false, $selected_tahun_ami);
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
