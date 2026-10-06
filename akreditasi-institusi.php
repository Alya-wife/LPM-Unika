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
?>

<!-- Banner Rujukan Terpadu ke Menu FAQ -->
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
// Render Modal Dokumen Institusi (SK & Sertifikat)
renderAkreditasiModals();
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
