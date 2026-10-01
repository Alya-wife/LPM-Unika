<?php
require_once __DIR__ . '/config/database.php';

// Force sync handler jika diminta
if (isset($_GET['sync']) && $_GET['sync'] === '1') {
    getEduRankRankings(true);
    header("Location: " . SITE_URL . "/pemeringkatan.php?synced=1");
    exit;
}

$page_title   = 'Pemeringkatan Universitas & Jurusan';
$meta_desc    = 'Peringkat resmi Universitas Katolik Soegijapranata di tingkat Indonesia dan Kota Semarang serta peringkat per bidang studi berdasarkan evaluasi EduRank.org.';
$current_page = 'pemeringkatan';

$edurank_data = getEduRankRankings();

$indonesia_rank = $edurank_data['indonesia']['rank'] ?? 65;
$indonesia_of   = $edurank_data['indonesia']['total'] ?? 562;
$semarang_rank  = $edurank_data['semarang']['rank'] ?? 3;
$semarang_of    = $edurank_data['semarang']['total'] ?? 14;
$topics         = $edurank_data['topics'] ?? [];
$last_sync      = $edurank_data['updated_at'] ?? date('Y-m-d H:i:s');
$edurank_url    = $edurank_data['url'] ?? 'https://edurank.org/uni/soegijapranata-catholic-university/rankings/';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            EduRank Official Rankings
        </div>
        <h1 class="page-banner-title">Pemeringkatan Universitas &amp; Jurusan</h1>
        <p class="text-white-50 mt-2 mb-0" style="max-width:720px;font-size:0.95rem;line-height:1.6;">
            Capaian peringkat resmi Universitas Katolik Soegijapranata di tingkat Nasional dan Kota Semarang serta peringkat per disiplin ilmu/jurusan yang bersumber langsung dari <strong>EduRank.org</strong>.
        </p>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/akreditasi-institusi.php">Akreditasi</a>
            <span>/</span>
            <span class="current">Pemeringkatan</span>
        </div>
    </div>
</div>

<section class="py-5" style="background:#F8FAFC;min-height:75vh;">
    <div class="container">

        <!-- Notification Bar & Real-Time Sync Status -->
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-3 mb-4 p-3 px-4 bg-white rounded-4 border shadow-sm">
            <div class="d-flex align-items-center gap-2">
                <span class="badge rounded-pill d-inline-flex align-items-center gap-1" style="background:#ECFDF5;color:#047857;border:1px solid #A7F3D0;font-size:0.8rem;padding:6px 12px;font-weight:700;">
                    <i class="bi bi-patch-check-fill text-success"></i> Terverifikasi EduRank.org
                </span>
                <span class="text-muted" style="font-size:0.84rem;">
                    Terakhir disinkronkan: <strong><?= date('d M Y, H:i', strtotime($last_sync)) ?> WIB</strong>
                </span>
            </div>
            <div>
                <a href="<?= SITE_URL ?>/pemeringkatan.php?sync=1" class="btn btn-sm btn-outline-primary fw-bold rounded-pill px-3 py-1 d-inline-flex align-items-center gap-2" style="font-size:0.82rem;" title="Tarik data terbaru langsung dari EduRank.org">
                    <i class="bi bi-arrow-repeat"></i> Perbarui Data Real-Time
                </a>
            </div>
        </div>

        <?php if (isset($_GET['synced'])): ?>
        <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert" style="font-size:0.88rem;">
            <i class="bi bi-check-circle-fill me-2"></i> Data pemeringkatan EduRank berhasil diperbarui secara langsung dari server EduRank.org!
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php endif; ?>

        <!-- ============================================================== -->
        <!-- SEKSI 1: KARTU PERINGKAT UTAMA (INDONESIA & SEMARANG)          -->
        <!-- Sesuai Desain & Instruksi: Cukup Indonesia & Semarang          -->
        <!-- ============================================================== -->
        <div class="row g-4 justify-content-center mb-4">
            
            <!-- Kartu 1: In Indonesia -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 p-4 text-center rounded-4 shadow-sm d-flex flex-column" style="background:#ffffff;border:1px solid #E2E8F0;transition:transform 0.2s ease, box-shadow 0.2s ease;">
                    <div class="mb-2">
                        <span style="font-size:2.6rem;font-weight:900;color:#D97706;font-family:var(--font-heading);letter-spacing:-1px;">
                            #<?= htmlspecialchars($indonesia_rank) ?>
                        </span>
                        <span style="font-size:1.15rem;font-weight:700;color:#64748B;">
                            of <?= htmlspecialchars($indonesia_of) ?>
                        </span>
                    </div>

                    <h3 style="font-family:var(--font-heading);font-weight:800;color:#0F172A;font-size:1.35rem;margin-bottom:0.75rem;">
                        In Indonesia
                    </h3>

                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill" style="background:#FEF3C7;color:#92400E;font-size:0.78rem;font-weight:800;">
                            <i class="bi bi-patch-check-fill" style="color:#D97706;"></i> EduRank Official <?= date('Y') ?>
                        </span>
                    </div>

                    <p style="font-size:0.875rem;color:#475569;line-height:1.65;margin-bottom:1.5rem;" class="flex-grow-1">
                        Akreditasi Unggul dari Badan Akreditasi Nasional Perguruan Tinggi
                    </p>

                    <div class="pt-3 border-top mt-auto" style="border-color:#F1F5F9 !important;">
                        <a href="<?= htmlspecialchars($edurank_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-dark w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="background:#0F172A;border:none;font-size:0.88rem;">
                            <i class="bi bi-box-arrow-up-right" style="font-size:0.82rem;"></i>
                            <span>Lihat di EduRank</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Kartu 2: In Semarang -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 p-4 text-center rounded-4 shadow-sm d-flex flex-column" style="background:#ffffff;border:1px solid #E2E8F0;transition:transform 0.2s ease, box-shadow 0.2s ease;">
                    <div class="mb-2">
                        <span style="font-size:2.6rem;font-weight:900;color:#D97706;font-family:var(--font-heading);letter-spacing:-1px;">
                            #<?= htmlspecialchars($semarang_rank) ?>
                        </span>
                        <span style="font-size:1.15rem;font-weight:700;color:#64748B;">
                            of <?= htmlspecialchars($semarang_of) ?>
                        </span>
                    </div>

                    <h3 style="font-family:var(--font-heading);font-weight:800;color:#0F172A;font-size:1.35rem;margin-bottom:0.75rem;">
                        In Semarang
                    </h3>

                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill" style="background:#FEF3C7;color:#92400E;font-size:0.78rem;font-weight:800;">
                            <i class="bi bi-patch-check-fill" style="color:#D97706;"></i> EduRank Official <?= date('Y') ?>
                        </span>
                    </div>

                    <p style="font-size:0.875rem;color:#475569;line-height:1.65;margin-bottom:1.5rem;" class="flex-grow-1">
                        Peringkat perguruan tinggi terkemuka di Kota Semarang berdasarkan luaran riset akademik dan reputasi institusi.
                    </p>

                    <div class="pt-3 border-top mt-auto" style="border-color:#F1F5F9 !important;">
                        <a href="<?= htmlspecialchars($edurank_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-dark w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="background:#0F172A;border:none;font-size:0.88rem;">
                            <i class="bi bi-box-arrow-up-right" style="font-size:0.82rem;"></i>
                            <span>Lihat di EduRank</span>
                        </a>
                    </div>
                </div>
            </div>

            <!-- Kartu 3: UI GreenMetric (Sustainable Campus Semarang & World) -->
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 p-4 text-center rounded-4 shadow-sm d-flex flex-column" style="background:#ffffff;border:1px solid #E2E8F0;transition:transform 0.2s ease, box-shadow 0.2s ease;">
                    <div class="mb-2">
                        <span style="font-size:2.6rem;font-weight:900;color:#16A34A;font-family:var(--font-heading);letter-spacing:-1px;">
                            #1398
                        </span>
                        <span style="font-size:1.15rem;font-weight:700;color:#64748B;">
                            World
                        </span>
                    </div>

                    <h3 style="font-family:var(--font-heading);font-weight:800;color:#0F172A;font-size:1.35rem;margin-bottom:0.75rem;">
                        UI GreenMetric
                    </h3>

                    <div class="mb-3">
                        <span class="d-inline-flex align-items-center gap-1 px-3 py-1 rounded-pill" style="background:#DCFCE7;color:#15803D;font-size:0.78rem;font-weight:800;">
                            <i class="bi bi-patch-check-fill" style="color:#16A34A;"></i> UI GreenMetric Official 2025
                        </span>
                    </div>

                    <p style="font-size:0.875rem;color:#475569;line-height:1.65;margin-bottom:1.5rem;" class="flex-grow-1">
                        Peringkat World's Most Sustainable University &amp; kampus hijau di Kota Semarang dalam pengelolaan keberlanjutan dan lingkungan ramah energi.
                    </p>

                    <div class="pt-3 border-top mt-auto d-flex flex-column gap-2" style="border-color:#F1F5F9 !important;">
                        <a href="<?= SITE_URL ?>/uploads/akreditasi/sertifikat_ui_greenmetric_2025.webp" target="_blank" class="btn btn-success w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="background:#16A34A;border:none;font-size:0.88rem;">
                            <i class="bi bi-file-earmark-image" style="font-size:0.88rem;"></i>
                            <span>Lihat Sertifikat</span>
                        </a>
                        <a href="https://greenmetric.ui.ac.id/" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary w-100 py-2 fw-bold rounded-3 d-flex align-items-center justify-content-center gap-2" style="font-size:0.82rem;">
                            <i class="bi bi-box-arrow-up-right" style="font-size:0.8rem;"></i>
                            <span>greenmetric.ui.ac.id</span>
                        </a>
                    </div>
                </div>
            </div>

        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
