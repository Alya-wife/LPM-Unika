<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Tanya Jawab (FAQ) Penjaminan Mutu';
$meta_desc  = 'Pertanyaan yang sering diajukan seputar SPMI, siklus PPEPP, Audit Mutu Internal (AMI), dan Akreditasi di UNIKA Soegijapranata.';

$db = getDB();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/knowledge-sections.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Tanya Jawab &amp; Bantuan
        </div>
        <h1 class="page-banner-title">Tanya Jawab (FAQ) Mutu</h1>
        <p class="text-white-50 mt-2 mb-0" style="max-width:700px;font-size:0.95rem;line-height:1.6;">
            Temukan jawaban atas pertanyaan lazim terkait tata kelola penjaminan mutu, pelaksanaan audit internal, hingga persiapan akreditasi program studi.
        </p>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">FAQ</span>
        </div>
    </div>
</div>

<?php
// Render FAQ Section
renderKnowledgeSection('knowledge_faq', [], false);
?>

<!-- Quick CTA if questions not found -->
<section class="py-5" style="background:#F8FAFC;border-bottom:1px solid var(--border);">
    <div class="container">
        <div class="card-lpm p-4 p-md-5 text-center" style="background:linear-gradient(135deg, #0A192F, #1E3A8A);border-radius:var(--radius-lg);color:#ffffff;box-shadow:0 10px 30px rgba(10,25,47,0.12);">
            <div class="d-inline-flex align-items-center justify-content-center mb-3" style="width:52px;height:52px;background:rgba(255,255,255,0.1);border-radius:50%;color:#FFD54F;font-size:1.5rem;">
                <i class="bi bi-question-circle"></i>
            </div>
            <h3 style="font-family:var(--font-heading);font-weight:800;font-size:1.45rem;color:#ffffff;margin-bottom:0.75rem;">
                Pertanyaan Anda Belum Terjawab di Atas?
            </h3>
            <p style="color:rgba(255,255,255,0.85);font-size:0.95rem;max-width:620px;margin:0 auto 1.5rem;line-height:1.7;">
                Tim Lembaga Penjaminan Mutu (LPM) siap membantu memberikan bimbingan teknis dan konsultasi mutu untuk unit kerja serta program studi.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="<?= SITE_URL ?>/layanan.php#kritik-saran" class="btn-hero-primary" style="background:var(--gold);color:var(--navy);font-weight:700;padding:0.7rem 1.6rem;text-decoration:none;border-radius:30px;">
                    <i class="bi bi-chat-text-fill me-1"></i> Kirim Pertanyaan / Saran &rarr;
                </a>
                <a href="<?= SITE_URL ?>/glosarium.php" class="btn-hero-secondary" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.3);color:#fff;padding:0.7rem 1.6rem;text-decoration:none;border-radius:30px;">
                    <i class="bi bi-book me-1"></i> Buka Glosarium Mutu
                </a>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
