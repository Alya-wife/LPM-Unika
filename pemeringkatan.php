<?php
require_once __DIR__ . '/config/database.php';

$page_title   = 'Pemeringkatan Universitas & Jurusan – Lokal, Nasional, Internasional';
$meta_desc    = 'Capaian peringkat resmi Universitas Katolik Soegijapranata (SCU) pada skala Lokal (Semarang & Jateng), Nasional (Indonesia), dan Internasional (Global).';
$current_page = 'pemeringkatan';

$db = getDB();

// Ambil data pemeringkatan aktif dari tabel pemeringkatan
$rankings = [];
try {
    $stmt = $db->query("
        SELECT * FROM pemeringkatan 
        WHERE is_active = 1 
        ORDER BY CASE WHEN kategori = 'lokal' THEN 1 WHEN kategori = 'nasional' THEN 2 ELSE 3 END ASC, urutan ASC, id DESC
    ");
    $rankings = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$counts = [
    'all'           => count($rankings),
    'lokal'         => count(array_filter($rankings, fn($r) => $r['kategori'] === 'lokal')),
    'nasional'      => count(array_filter($rankings, fn($r) => $r['kategori'] === 'nasional')),
    'internasional' => count(array_filter($rankings, fn($r) => $r['kategori'] === 'internasional')),
];

$active_segmen = trim($_GET['segmen'] ?? 'all');
if (!in_array($active_segmen, ['all', 'lokal', 'nasional', 'internasional'])) {
    $active_segmen = 'all';
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
/* Styling Premium Pemeringkatan Kampus */
.ranking-segmen-btn {
    border: 1.5px solid #E2E8F0;
    background: #ffffff;
    color: #475569;
    font-weight: 700;
    font-size: 0.88rem;
    padding: 0.65rem 1.35rem;
    border-radius: 50px;
    transition: all 0.22s ease;
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    cursor: pointer;
    box-shadow: 0 2px 6px rgba(0,0,0,0.02);
}
.ranking-segmen-btn:hover {
    border-color: #071739;
    color: #071739;
    background: #F8FAFC;
    transform: translateY(-2px);
}
.ranking-segmen-btn.active {
    background: #071739;
    color: #ffffff !important;
    border-color: #071739;
    box-shadow: 0 4px 14px rgba(7,23,57,0.18);
}
.ranking-segmen-btn.active-lokal {
    background: #0284C7;
    border-color: #0284C7;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(2,132,199,0.22);
}
.ranking-segmen-btn.active-nasional {
    background: #D97706;
    border-color: #D97706;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(217,119,6,0.22);
}
.ranking-segmen-btn.active-internasional {
    background: #7C3AED;
    border-color: #7C3AED;
    color: #ffffff !important;
    box-shadow: 0 4px 14px rgba(124,58,237,0.22);
}

.ranking-card {
    background: #ffffff;
    border: 1.5px solid #E2E8F0;
    border-radius: 18px;
    overflow: hidden;
    height: 100%;
    display: flex;
    flex-direction: column;
    transition: all 0.28s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 15px rgba(0,0,0,0.03);
    position: relative;
}
.ranking-card:hover {
    transform: translateY(-6px);
    box-shadow: 0 16px 32px rgba(7,23,57,0.1);
    border-color: #CBD5E1;
}

/* Category Accent Strip */
.ranking-card.cat-lokal {
    border-top: 5px solid #0284C7 !important;
}
.ranking-card.cat-nasional {
    border-top: 5px solid #D97706 !important;
}
.ranking-card.cat-internasional {
    border-top: 5px solid #7C3AED !important;
}

.ranking-hero-number {
    font-size: 2.75rem;
    font-weight: 900;
    font-family: var(--font-heading);
    letter-spacing: -1.2px;
    line-height: 1.1;
    margin-bottom: 0.25rem;
}
.cat-lokal .ranking-hero-number { color: #0284C7; }
.cat-nasional .ranking-hero-number { color: #D97706; }
.cat-internasional .ranking-hero-number { color: #7C3AED; }

.ranking-hero-sub {
    font-size: 0.95rem;
    font-weight: 700;
    color: #64748B;
    margin-bottom: 1rem;
    display: block;
}

/* Summary Counter Cards */
.ranking-counter-card {
    background: #ffffff;
    border: 1.5px solid #E2E8F0;
    border-radius: 16px;
    padding: 1.25rem 1.5rem;
    display: flex;
    align-items: center;
    gap: 1.25rem;
    box-shadow: 0 4px 12px rgba(0,0,0,0.02);
}
.ranking-counter-icon {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.6rem;
    flex-shrink: 0;
}
</style>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Rekognisi &amp; Prestasi Mutu
        </div>
        <h1 class="page-banner-title">Pemeringkatan Universitas &amp; Jurusan</h1>
        <p class="text-white-50 mt-2 mb-0" style="max-width:760px;font-size:0.95rem;line-height:1.6;">
            Capaian peringkat resmi dan rekognisi mutu <strong>Universitas Katolik Soegijapranata (SCU)</strong> dari lembaga pemeringkat independen bereputasi pada skala <strong>Lokal</strong>, <strong>Nasional</strong>, dan <strong>Internasional</strong>.
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

        <!-- Ringkasan Statistik 3 Segmentasi -->
        <div class="row g-3 mb-5">
            <div class="col-md-4">
                <div class="ranking-counter-card">
                    <div class="ranking-counter-icon" style="background:rgba(2,132,199,0.1);color:#0284C7;">
                        <i class="bi bi-geo-alt-fill"></i>
                    </div>
                    <div>
                        <div class="text-uppercase fw-bold text-muted small" style="letter-spacing:0.5px;font-size:0.75rem;">Skala Wilayah</div>
                        <h4 class="fw-bold mb-0" style="color:var(--navy);font-size:1.15rem;">Lokal (Kota &amp; Jateng)</h4>
                        <div class="text-primary fw-bold small mt-1"><?= $counts['lokal'] ?> Capaian Resmi</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ranking-counter-card">
                    <div class="ranking-counter-icon" style="background:rgba(217,119,6,0.1);color:#D97706;">
                        <i class="bi bi-flag-fill"></i>
                    </div>
                    <div>
                        <div class="text-uppercase fw-bold text-muted small" style="letter-spacing:0.5px;font-size:0.75rem;">Skala Wilayah</div>
                        <h4 class="fw-bold mb-0" style="color:var(--navy);font-size:1.15rem;">Nasional (Indonesia)</h4>
                        <div class="text-warning fw-bold small mt-1" style="color:#D97706 !important;"><?= $counts['nasional'] ?> Capaian Resmi</div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="ranking-counter-card">
                    <div class="ranking-counter-icon" style="background:rgba(124,58,237,0.1);color:#7C3AED;">
                        <i class="bi bi-globe2"></i>
                    </div>
                    <div>
                        <div class="text-uppercase fw-bold text-muted small" style="letter-spacing:0.5px;font-size:0.75rem;">Skala Wilayah</div>
                        <h4 class="fw-bold mb-0" style="color:var(--navy);font-size:1.15rem;">Internasional (Global)</h4>
                        <div class="text-purple fw-bold small mt-1" style="color:#7C3AED !important;"><?= $counts['internasional'] ?> Capaian Resmi</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter Segmentasi Interaktif (Semua, Lokal, Nasional, Internasional) -->
        <div class="card p-3 p-md-4 border-0 shadow-sm rounded-4 bg-white mb-5">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-primary rounded-pill px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-filter me-1"></i>Pilih Segmentasi</span>
                        <span class="text-uppercase fw-bold text-dark small" style="letter-spacing:0.5px;">Filter Rekognisi Pemeringkatan:</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2" id="rankingSegmenFilter">
                        <button type="button" class="ranking-segmen-btn <?= $active_segmen === 'all' ? 'active' : '' ?>" data-segmen="all" onclick="applyRankingFilter('all', this)">
                            <i class="bi bi-grid-fill"></i> Semua Rekognisi (<?= $counts['all'] ?>)
                        </button>
                        <button type="button" class="ranking-segmen-btn <?= $active_segmen === 'lokal' ? 'active-lokal' : '' ?>" data-segmen="lokal" onclick="applyRankingFilter('lokal', this)">
                            <i class="bi bi-geo-alt-fill text-primary"></i> Lokal (<?= $counts['lokal'] ?>)
                        </button>
                        <button type="button" class="ranking-segmen-btn <?= $active_segmen === 'nasional' ? 'active-nasional' : '' ?>" data-segmen="nasional" onclick="applyRankingFilter('nasional', this)">
                            <i class="bi bi-flag-fill text-warning"></i> Nasional (<?= $counts['nasional'] ?>)
                        </button>
                        <button type="button" class="ranking-segmen-btn <?= $active_segmen === 'internasional' ? 'active-internasional' : '' ?>" data-segmen="internasional" onclick="applyRankingFilter('internasional', this)">
                            <i class="bi bi-globe2 text-purple"></i> Internasional (<?= $counts['internasional'] ?>)
                        </button>
                    </div>
                </div>

                <div class="text-muted small fst-italic">
                    <i class="bi bi-info-circle me-1"></i>Menampilkan rekognisi pemeringkatan kampus sesuai skala wilayah
                </div>
            </div>
        </div>

        <!-- Grid Kartu Pemeringkatan -->
        <div class="row g-4" id="rankingGrid">
            <?php if (empty($rankings)): ?>
            <div class="col-12 text-center py-5 text-muted">
                <i class="bi bi-award fs-1 opacity-50 mb-3 d-block"></i>
                <h5>Belum ada data pemeringkatan yang dipublikasikan.</h5>
                <p class="small">Data pemeringkatan akan segera diperbarui oleh pengelola LPM.</p>
            </div>
            <?php else: ?>
            <?php foreach ($rankings as $r): ?>
            <?php 
            $cat_class = 'cat-' . $r['kategori'];
            $file_url = '';
            if (!empty($r['file_sertifikat'])) {
                if (file_exists(__DIR__ . '/uploads/pemeringkatan/' . $r['file_sertifikat'])) {
                    $file_url = SITE_URL . '/uploads/pemeringkatan/' . $r['file_sertifikat'];
                } elseif (file_exists(__DIR__ . '/uploads/akreditasi/' . $r['file_sertifikat'])) {
                    $file_url = SITE_URL . '/uploads/akreditasi/' . $r['file_sertifikat'];
                } else {
                    $file_url = SITE_URL . '/uploads/pemeringkatan/' . $r['file_sertifikat'];
                }
            }
            ?>
            <div class="col-md-6 col-lg-4 ranking-item" data-segmen="<?= e($r['kategori']) ?>">
                <div class="ranking-card <?= $cat_class ?>">
                    
                    <!-- Header Kartu: Badge Segmen & Tahun -->
                    <div class="p-4 pb-0 d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <div>
                            <?php if ($r['kategori'] === 'lokal'): ?>
                            <span class="badge px-3 py-1 rounded-pill" style="background:#E0F2FE;color:#0369A1;font-weight:800;font-size:0.75rem;">
                                <i class="bi bi-geo-alt-fill me-1"></i> LOKAL (SEMARANG &amp; JATENG)
                            </span>
                            <?php elseif ($r['kategori'] === 'nasional'): ?>
                            <span class="badge px-3 py-1 rounded-pill" style="background:#FEF3C7;color:#92400E;font-weight:800;font-size:0.75rem;">
                                <i class="bi bi-flag-fill me-1"></i> NASIONAL (INDONESIA)
                            </span>
                            <?php else: ?>
                            <span class="badge px-3 py-1 rounded-pill" style="background:#F3E8FF;color:#6B21A8;font-weight:800;font-size:0.75rem;">
                                <i class="bi bi-globe2 me-1"></i> INTERNASIONAL (GLOBAL)
                            </span>
                            <?php endif; ?>
                        </div>
                        <span class="badge bg-light text-muted border px-2 py-1 rounded-3" style="font-size:0.75rem;">
                            <?= e($r['tahun']) ?>
                        </span>
                    </div>

                    <!-- Body Kartu: Angka Peringkat, Judul, Lembaga, Deskripsi -->
                    <div class="p-4 pt-3 flex-grow-1 d-flex flex-column text-center">
                        <div class="mt-2 mb-1">
                            <span class="ranking-hero-number"><?= e($r['peringkat'] ?: '-') ?></span>
                            <?php if (!empty($r['peringkat_dari'])): ?>
                            <span class="ranking-hero-sub"><?= e($r['peringkat_dari']) ?></span>
                            <?php endif; ?>
                        </div>

                        <h3 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.2rem;line-height:1.4;margin-bottom:0.75rem;">
                            <?= e($r['judul']) ?>
                        </h3>

                        <div class="mb-3 d-flex align-items-center justify-content-center gap-2 flex-wrap">
                            <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.78rem;">
                                <i class="bi bi-patch-check-fill text-primary me-1"></i> <?= e($r['lembaga']) ?>
                            </span>
                            <?php if (!empty($r['badge_teks'])): ?>
                            <span class="badge rounded-pill px-3 py-1" style="background:#F1F5F9;color:#334155;font-weight:700;font-size:0.75rem;">
                                <?= e($r['badge_teks']) ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($r['deskripsi'])): ?>
                        <p class="text-muted small mb-4 flex-grow-1 text-start" style="font-size:0.86rem;line-height:1.65;">
                            <?= nl2br(e($r['deskripsi'])) ?>
                        </p>
                        <?php endif; ?>

                        <!-- Tombol Aksi di Bagian Bawah -->
                        <div class="pt-3 border-top mt-auto vstack gap-2" style="border-color:#F1F5F9 !important;">
                            <?php if (!empty($file_url)): ?>
                            <button type="button" class="btn btn-warning w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size:0.88rem;" onclick="openCertModal('<?= $file_url ?>', '<?= e(addslashes($r['judul'])) ?>', '<?= e(addslashes($r['lembaga'])) ?>')">
                                <i class="bi bi-file-earmark-check-fill"></i>
                                <span>Lihat Berkas Sertifikat</span>
                            </button>
                            <?php endif; ?>

                            <?php if (!empty($r['link_url'])): ?>
                            <a href="<?= e($r['link_url']) ?>" target="_blank" rel="noopener noreferrer" class="btn <?= !empty($file_url) ? 'btn-outline-dark' : 'btn-dark' ?> w-100 py-2 fw-bold rounded-3 shadow-sm d-flex align-items-center justify-content-center gap-2" style="font-size:0.88rem;">
                                <i class="bi bi-box-arrow-up-right" style="font-size:0.82rem;"></i>
                                <span>Kunjungi Sumber Berita / Rilis</span>
                            </a>
                            <?php endif; ?>

                            <?php if (empty($file_url) && empty($r['link_url'])): ?>
                            <span class="text-muted small py-2">Data resmi terverifikasi</span>
                            <?php endif; ?>
                        </div>
                    </div>

                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>
        </div>

        <!-- Notifikasi saat filter tidak menemukan data -->
        <div id="rankingEmptyFilterNotice" class="text-center py-5 text-muted" style="display:none;">
            <i class="bi bi-filter-circle fs-1 text-muted opacity-50 mb-2 d-block"></i>
            Tidak ada rekognisi pemeringkatan untuk segmentasi yang dipilih.
        </div>

    </div>
</section>

<!-- Modal Lightbox Pratinjau Sertifikat Dokumen / PDF / Gambar -->
<div class="modal fade" id="certPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-3 border-0">
                <div class="d-flex align-items-center gap-2 min-w-0 me-3">
                    <div style="width:38px;height:38px;border-radius:10px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-award-fill fs-5 text-warning"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="modal-title fw-bold text-truncate mb-0" id="certModalTitle">Pratinjau Sertifikat Pemeringkatan</h6>
                        <small class="text-white-50 text-truncate d-block" id="certModalSubtitle"></small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
                    <a href="#" id="certModalNewTabBtn" target="_blank" class="btn btn-sm btn-outline-light d-none d-sm-inline-flex align-items-center">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka di Tab Baru
                    </a>
                    <a href="#" id="certModalDownloadBtn" download class="btn btn-sm btn-warning text-dark fw-bold d-inline-flex align-items-center">
                        <i class="bi bi-download me-1"></i> Unduh Berkas
                    </a>
                    <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0 bg-light text-center" style="min-height:65vh;max-height:82vh;display:flex;flex-direction:column;justify-content:center;position:relative;">
                <div id="certModalLoading" class="position-absolute top-50 start-50 translate-middle text-center text-muted" style="z-index:5;">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div class="small fw-semibold">Memuat pratinjau sertifikat...</div>
                </div>
                <!-- Iframe Viewer for PDF -->
                <iframe id="certModalIframe" src="" style="width:100%;height:75vh;border:none;flex-grow:1;display:none;" onload="document.getElementById('certModalLoading').style.display='none';"></iframe>
                <!-- Image Viewer for WebP/JPG/PNG -->
                <img id="certModalImg" src="" alt="Sertifikat" style="max-width:100%;max-height:75vh;object-fit:contain;margin:auto;display:none;" onload="document.getElementById('certModalLoading').style.display='none';">
            </div>
            <div class="modal-footer bg-white border-top p-2 px-3 d-flex justify-content-between align-items-center">
                <span class="text-muted small"><i class="bi bi-patch-check-fill text-success me-1"></i> Sertifikat Resmi Lembaga Penjaminan Mutu &amp; Universitas Katolik Soegijapranata.</span>
                <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Filter Segmentasi Pemeringkatan (Lokal, Nasional, Internasional)
function applyRankingFilter(segmen, btn) {
    if (!segmen) segmen = 'all';

    // 1. Perbarui status tombol aktif
    var btns = document.querySelectorAll('#rankingSegmenFilter .ranking-segmen-btn');
    btns.forEach(function(b) {
        b.classList.remove('active', 'active-lokal', 'active-nasional', 'active-internasional');
        if (b.getAttribute('data-segmen') === segmen) {
            if (segmen === 'lokal') b.classList.add('active-lokal');
            else if (segmen === 'nasional') b.classList.add('active-nasional');
            else if (segmen === 'internasional') b.classList.add('active-internasional');
            else b.classList.add('active');
        }
    });

    // 2. Filter item kartu pada grid
    var items = document.querySelectorAll('.ranking-item');
    var visibleCount = 0;
    items.forEach(function(it) {
        var itSegmen = it.getAttribute('data-segmen');
        if (segmen === 'all' || itSegmen === segmen) {
            it.style.display = '';
            visibleCount++;
        } else {
            it.style.display = 'none';
        }
    });

    var emptyNotice = document.getElementById('rankingEmptyFilterNotice');
    if (emptyNotice) {
        emptyNotice.style.display = (visibleCount === 0 && items.length > 0) ? 'block' : 'none';
    }

    // 3. Perbarui URL tanpa reload (History API)
    if (window.history && window.history.replaceState) {
        var u = new URL(window.location.href);
        if (segmen === 'all') {
            u.searchParams.delete('segmen');
        } else {
            u.searchParams.set('segmen', segmen);
        }
        window.history.replaceState({}, '', u.toString());
    }
}

// Inisialisasi segmen awal dari URL
document.addEventListener('DOMContentLoaded', function() {
    var initSegmen = '<?= e($active_segmen) ?>';
    if (initSegmen && initSegmen !== 'all') {
        var activeBtn = document.querySelector('#rankingSegmenFilter [data-segmen="' + initSegmen + '"]');
        if (activeBtn) {
            applyRankingFilter(initSegmen, activeBtn);
        }
    }
});

// Modal Pratinjau Sertifikat
function openCertModal(fileUrl, title, subtitle) {
    var iframe = document.getElementById('certModalIframe');
    var img = document.getElementById('certModalImg');
    var loading = document.getElementById('certModalLoading');
    var titleEl = document.getElementById('certModalTitle');
    var subEl = document.getElementById('certModalSubtitle');
    var newTabBtn = document.getElementById('certModalNewTabBtn');
    var dlBtn = document.getElementById('certModalDownloadBtn');

    titleEl.textContent = title || 'Sertifikat Pemeringkatan';
    subEl.textContent = subtitle || fileUrl.split('/').pop();
    newTabBtn.href = fileUrl;
    dlBtn.href = fileUrl;

    iframe.style.display = 'none';
    img.style.display = 'none';
    loading.style.display = 'block';

    var ext = fileUrl.split('.').pop().toLowerCase();
    if (ext === 'pdf') {
        iframe.src = fileUrl;
        iframe.style.display = 'block';
    } else {
        img.src = fileUrl;
        img.style.display = 'block';
    }

    var modal = new bootstrap.Modal(document.getElementById('certPreviewModal'));
    modal.show();
}

// Reset saat modal ditutup
document.getElementById('certPreviewModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('certModalIframe').src = '';
    document.getElementById('certModalImg').src = '';
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
