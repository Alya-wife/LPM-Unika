<?php
require_once 'config/database.php';
$page_title = 'Beranda';
include_once 'includes/header.php';

// Fetch latest news
$stmt = $pdo->query("SELECT * FROM berita ORDER BY tanggal_publikasi DESC LIMIT 3");
$berita_terbaru = $stmt->fetchAll();

// Ambil pengaturan
$stmt_pengaturan = $pdo->query("SELECT * FROM pengaturan");
$pengaturan = [];
while ($row = $stmt_pengaturan->fetch()) {
    $pengaturan[$row->kunci] = $row->nilai;
}

// Ambil Kepala LPM dari struktur organisasi
$stmt_kepala = $pdo->query("SELECT * FROM struktur_organisasi WHERE parent_id IS NULL ORDER BY urutan ASC, id ASC LIMIT 1");
$kepala_lpm = $stmt_kepala->fetch();

// Ambil Lembaga Terkait
$stmt_lembaga = $pdo->query("SELECT * FROM lembaga_terkait ORDER BY id DESC");
$lembaga_list = $stmt_lembaga->fetchAll();
?>

<?php
$banner = $pengaturan['banner_utama'] ?? 'default-banner.jpg';
$banner_urls = [];
if ($banner === 'default-banner.jpg' || empty(trim($banner))) {
    $banner_urls[] = "https://images.unsplash.com/photo-1541339907198-e08756dedf3f?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80";
    $banner_urls[] = "https://images.unsplash.com/photo-1562774053-701939374585?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80"; // College campus
    $banner_urls[] = "https://images.unsplash.com/photo-1523050854058-8df90110c9f1?ixlib=rb-4.0.3&auto=format&fit=crop&w=1920&q=80"; // Graduation
} else {
    $images = explode(',', $banner);
    foreach ($images as $img) {
        $img = trim($img);
        if ($img) {
            if (strpos($img, 'http') === 0) {
                $banner_urls[] = htmlspecialchars($img);
            } else {
                $banner_urls[] = "uploads/" . htmlspecialchars($img);
            }
        }
    }
}
$is_carousel = count($banner_urls) > 1;
?>
<!-- Hero Section -->
<div class="position-relative hero-wrapper mb-5"
    style="border-bottom-left-radius: 40px; border-bottom-right-radius: 40px; overflow: hidden; box-shadow: 0 10px 30px rgba(0,0,0,0.1);">
    <?php if ($is_carousel): ?>
        <!-- Carousel Background -->
        <div id="heroCarousel" class="carousel slide carousel-fade position-absolute w-100 h-100" data-bs-ride="carousel"
            data-bs-interval="4000" style="z-index: 0;">
            <div class="carousel-inner w-100 h-100">
                <?php foreach ($banner_urls as $index => $url): ?>
                    <div class="carousel-item <?= $index === 0 ? 'active' : '' ?> w-100 h-100"
                        style="background: linear-gradient(rgba(10, 25, 47, 0.85), rgba(10, 25, 47, 0.85)), url('<?= $url ?>') center/cover no-repeat;">
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <style>
            .hero-wrapper:hover .custom-carousel-btn {
                opacity: 1 !important;
            }
        </style>
    <?php endif; ?>

    <section class="position-relative text-center d-flex align-items-center justify-content-center"
        style="min-height: 500px; padding: 120px 0; z-index: 1; color: white; pointer-events: none; <?= !$is_carousel ? "background: linear-gradient(rgba(10, 25, 47, 0.85), rgba(10, 25, 47, 0.85)), url('{$banner_urls[0]}') center/cover no-repeat;" : '' ?>">
        <div class="container py-4" style="pointer-events: auto;">
            <h1 class="display-4 fw-bold mb-4"><?= $pengaturan['hero_title'] ?? 'Selamat Datang di<br>Lembaga Penjaminan Mutu (LPM) SCU' ?></h1>
            <p class="lead mb-5 mx-auto" style="max-width: 700px;"><?= $pengaturan['hero_subtitle'] ?? 'Mengawal mutu akademik dan non-akademik di Universitas Katolik Soegijapranata untuk mencapai keunggulan yang berkelanjutan.' ?></p>
            <div class="d-flex justify-content-center gap-3">
                <a href="spmi.php" class="btn btn-purple btn-lg px-4 shadow-sm">Lihat Dokumen Mutu</a>
                <a href="profil.php" class="btn btn-outline-light btn-lg px-4">Tentang Kami</a>
            </div>
        </div>
    </section>

    <?php if ($is_carousel): ?>
        <!-- Smooth Carousel Controls (Moved outside overlay) -->
        <button class="carousel-control-prev custom-carousel-btn position-absolute top-50 start-0 translate-middle-y" type="button" data-bs-target="#heroCarousel" data-bs-slide="prev" style="width: 8%; opacity: 0; transition: all 0.4s ease; z-index: 10; border: none; background: transparent; pointer-events: auto;">
            <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 50px; height: 50px; background-color: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); transition: all 0.3s ease; margin: 0 auto;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.3)'; this.style.transform='scale(1.1)';" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.15)'; this.style.transform='scale(1)';">
                <i class="bi bi-chevron-left text-white fs-4"></i>
            </div>
            <span class="visually-hidden">Previous</span>
        </button>
        <button class="carousel-control-next custom-carousel-btn position-absolute top-50 end-0 translate-middle-y" type="button" data-bs-target="#heroCarousel" data-bs-slide="next" style="width: 8%; opacity: 0; transition: all 0.4s ease; z-index: 10; border: none; background: transparent; pointer-events: auto;">
            <div class="d-flex align-items-center justify-content-center rounded-circle" style="width: 50px; height: 50px; background-color: rgba(255,255,255,0.15); backdrop-filter: blur(10px); border: 1px solid rgba(255,255,255,0.2); transition: all 0.3s ease; margin: 0 auto;" onmouseover="this.style.backgroundColor='rgba(255,255,255,0.3)'; this.style.transform='scale(1.1)';" onmouseout="this.style.backgroundColor='rgba(255,255,255,0.15)'; this.style.transform='scale(1)';">
                <i class="bi bi-chevron-right text-white fs-4"></i>
            </div>
            <span class="visually-hidden">Next</span>
        </button>
    <?php endif; ?>
</div>

<!-- Sambutan Section -->
<section class="py-5 bg-white">
    <div class="container py-5">
        <div class="row align-items-center">
            <div class="col-lg-5 mb-5 mb-lg-0">
                <div class="position-relative">
                    <!-- Aksen Biru Tua (Navy) di belakang gambar -->
                    <div class="position-absolute rounded" style="background-color: var(--navy-dark); width: 100%; height: 100%; top: 20px; left: -20px; z-index: 0; opacity: 0.9;"></div>
                    <?php 
                    $foto_kepala = ($kepala_lpm && $kepala_lpm->foto) ? 'uploads/struktur/' . htmlspecialchars($kepala_lpm->foto) : 'https://images.unsplash.com/photo-1560250097-0b93528c311a?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80';
                    ?>
                    <img src="<?= $foto_kepala ?>"
                        alt="Sambutan Kepala LPM" class="img-fluid rounded shadow-lg position-relative object-fit-cover" style="z-index: 1; max-height: 500px; width: 100%;">
                </div>
            </div>
            <div class="col-lg-7 px-lg-5">
                <div class="d-flex align-items-center mb-3">
                    <i class="bi bi-chat-quote-fill text-purple me-3" style="font-size: 2.5rem;"></i>
                    <h2 class="section-title text-navy mb-0" style="padding-bottom: 0;">Sambutan Kepala LPM</h2>
                </div>
                
                <div class="text-secondary" style="font-size: 1.05rem; line-height: 1.8;">
                    <?= $pengaturan['sambutan'] ?? '' ?>
                </div>
                
                <div class="mt-4 pt-3 border-top" style="border-color: rgba(10,25,47,0.1) !important;">
                    <p class="fw-bold text-navy mb-0 fs-5"><?= htmlspecialchars($kepala_lpm->nama ?? 'Kepala LPM') ?></p>
                    <p class="text-muted small mb-0"><?= htmlspecialchars($kepala_lpm->jabatan ?? 'Lembaga Penjaminan Mutu') ?></p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Berita Terbaru -->
<section class="py-5 bg-light-gradient">
    <div class="container py-4">
        <div class="d-flex justify-content-between align-items-end mb-4">
            <h2 class="section-title mb-0">Berita & Pengumuman</h2>
            <a href="berita.php" class="btn btn-outline-navy btn-sm">Lihat Semua <i class="bi bi-arrow-right"></i></a>
        </div>
        <div class="row g-4">
            <?php if (count($berita_terbaru) > 0): ?>
                <?php foreach ($berita_terbaru as $b): ?>
                    <div class="col-md-4">
                        <div class="card h-100">
                            <img src="<?= $b->gambar ? 'uploads/berita/' . htmlspecialchars($b->gambar) : 'https://images.unsplash.com/photo-1524178232363-1fb2b075b655?ixlib=rb-4.0.3&auto=format&fit=crop&w=800&q=80' ?>"
                                class="card-img-top" alt="<?= htmlspecialchars($b->judul) ?>">
                            <div class="card-body">
                                <p class="text-muted small mb-2"><i class="bi bi-calendar3 me-1"></i>
                                    <?= date('d M Y', strtotime($b->tanggal_publikasi)) ?>
                                </p>
                                <h5 class="card-title fw-bold"><a
                                        href="berita-detail.php?slug=<?= htmlspecialchars($b->slug) ?>"
                                        class="text-navy text-decoration-none"><?= htmlspecialchars($b->judul) ?></a></h5>
                                <p class="card-text text-muted"><?= substr(strip_tags($b->konten), 0, 100) ?>...</p>
                            </div>
                            <div class="card-footer bg-transparent border-0 pt-0">
                                <a href="berita-detail.php?slug=<?= htmlspecialchars($b->slug) ?>"
                                    class="text-purple fw-semibold text-decoration-none">Baca selengkapnya <i
                                        class="bi bi-chevron-right small"></i></a>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <div class="col-12 text-center py-4">
                    <p class="text-muted">Belum ada berita yang dipublikasikan.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Akreditasi Institusi Highlight -->
<section class="py-5 bg-white">
    <div class="container py-5">
        <div class="row align-items-center justify-content-center">
            <div class="col-lg-6 mb-4 mb-lg-0">
                <div class="pe-lg-5">
                    <span class="badge bg-purple px-3 py-2 rounded-pill mb-3">Akreditasi Institusi</span>
                    <h2 class="display-6 fw-bold text-navy mb-4">Capaian Mutu<br>Universitas Katolik Soegijapranata</h2>
                    <p class="lead text-muted mb-4">Universitas kami terus berkomitmen untuk memberikan standar pendidikan terbaik sesuai dengan pedoman Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT).</p>
                    <div class="d-flex align-items-center mb-4">
                        <i class="bi bi-award-fill text-warning display-4 me-3"></i>
                        <div>
                            <h4 class="fw-bold mb-1">AKREDITASI <?= htmlspecialchars(strtoupper($pengaturan['akred_institusi_peringkat'] ?? 'UNGGUL')) ?></h4>
                            <p class="text-muted small mb-0"><?= htmlspecialchars($pengaturan['akred_institusi_sk'] ?? 'Nomor SK: -') ?></p>
                        </div>
                    </div>
                    <div class="d-flex flex-wrap gap-3 mt-2">
                        <a href="akreditasi-prodi.php" class="btn btn-purple fw-semibold px-4 py-2">
                            Lihat Akreditasi Program Studi <i class="bi bi-list-check ms-2"></i>
                        </a>
                    </div>
                </div>
            </div>
            <div class="col-lg-5 text-center">
                <?php if (!empty($pengaturan['akred_institusi_file'])): ?>
                    <a href="uploads/<?= htmlspecialchars($pengaturan['akred_institusi_file']) ?>" target="_blank" class="d-block position-relative shadow-lg rounded-4 overflow-hidden" style="border: 5px solid white; transform: rotate(2deg); transition: transform 0.3s ease;" onmouseover="this.style.transform='rotate(0deg) scale(1.02)'" onmouseout="this.style.transform='rotate(2deg) scale(1)'">
                        <img src="uploads/<?= htmlspecialchars($pengaturan['akred_institusi_file']) ?>" alt="Sertifikat Akreditasi SCU" class="img-fluid" style="object-fit: cover;">
                        <div class="position-absolute top-0 start-0 w-100 h-100 bg-navy bg-opacity-25 d-flex align-items-center justify-content-center opacity-0 transition-hover" onmouseover="this.style.opacity='1'" onmouseout="this.style.opacity='0'">
                            <span class="btn btn-light rounded-circle p-3"><i class="bi bi-zoom-in fs-3 text-navy"></i></span>
                        </div>
                    </a>
                <?php else: ?>
                    <div class="bg-light rounded-4 shadow-sm d-flex flex-column align-items-center justify-content-center p-5" style="height: 350px; border: 2px dashed #ccc;">
                        <i class="bi bi-image text-muted display-1 mb-3"></i>
                        <p class="text-muted">Sertifikat Akreditasi belum diunggah oleh Admin.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<!-- Quick Links / Statistik -->
<section class="text-white position-relative" style="background: linear-gradient(to bottom, #f4f7f6 0%, var(--navy-dark) 15%, var(--navy-dark) 100%); padding-top: 80px; padding-bottom: 3rem;">
    <div class="container">
        <div class="row g-4 text-center justify-content-center">
            <div class="col-md-3 col-6">
                <div class="p-3 transition-hover" style="transition: transform 0.3s ease;">
                    <div class="display-3 fw-bold mb-2 text-white"><?= htmlspecialchars($pengaturan['stat_akreditasi'] ?? 'A') ?></div>
                    <p class="mb-0 fs-6 fw-light" style="opacity: 0.8; letter-spacing: 1px;">Akreditasi Institusi</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 transition-hover" style="transition: transform 0.3s ease;">
                    <div class="display-3 fw-bold mb-2 text-white"><?= htmlspecialchars($pengaturan['stat_prodi'] ?? '40+') ?></div>
                    <p class="mb-0 fs-6 fw-light" style="opacity: 0.8; letter-spacing: 1px;">Program Studi</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 transition-hover" style="transition: transform 0.3s ease;">
                    <div class="display-3 fw-bold mb-2 text-white"><?= htmlspecialchars($pengaturan['stat_dokumen'] ?? '150+') ?></div>
                    <p class="mb-0 fs-6 fw-light" style="opacity: 0.8; letter-spacing: 1px;">Dokumen Mutu</p>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="p-3 transition-hover" style="transition: transform 0.3s ease;">
                    <div class="display-3 fw-bold mb-2 text-white"><?= htmlspecialchars($pengaturan['stat_auditor'] ?? '12') ?></div>
                    <p class="mb-0 fs-6 fw-light" style="opacity: 0.8; letter-spacing: 1px;">Auditor Internal</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Lembaga Terkait / Mitra (Marquee) -->
<?php if(count($lembaga_list) > 0): ?>
<section class="py-5 bg-white border-top">
    <div class="container-fluid overflow-hidden px-0">
        <div class="text-center mb-4">
            <p class="text-muted fw-bold text-uppercase mb-0" style="letter-spacing: 2px; font-size: 0.9rem;">Jejaring & Lembaga Terkait</p>
        </div>
        <div class="marquee-container d-flex align-items-center py-2">
            <div class="marquee-content d-flex align-items-center">
                <!-- Kita duplikat isi listnya agar scrollingnya mulus tanpa putus -->
                <?php for($i = 0; $i < 2; $i++): ?>
                    <?php foreach($lembaga_list as $l): ?>
                        <a href="<?= htmlspecialchars($l->url_situs) ?>" target="_blank" class="mx-5 text-decoration-none grayscale-hover transition-hover d-flex align-items-center justify-content-center" title="<?= htmlspecialchars($l->nama_lembaga) ?>" style="min-width: 150px;">
                            <?php 
                                $img_src = (strpos($l->logo, 'http') === 0) ? $l->logo : 'uploads/lembaga/' . $l->logo;
                            ?>
                            <img src="<?= htmlspecialchars($img_src) ?>" alt="<?= htmlspecialchars($l->nama_lembaga) ?>" style="max-height: 60px; object-fit: contain; filter: grayscale(100%) opacity(70%); transition: all 0.3s ease;" onmouseover="this.style.filter='grayscale(0%) opacity(100%)'" onmouseout="this.style.filter='grayscale(100%) opacity(70%)'">
                        </a>
                    <?php endforeach; ?>
                <?php endfor; ?>
            </div>
        </div>
    </div>
</section>

<style>
.marquee-container {
    width: 100%;
    overflow: hidden;
    white-space: nowrap;
    position: relative;
}
.marquee-content {
    display: inline-flex;
    animation: marquee 25s linear infinite;
}
.marquee-container:hover .marquee-content {
    animation-play-state: paused;
}
@keyframes marquee {
    0% { transform: translateX(0); }
    100% { transform: translateX(-50%); } /* geser 50% karena konten diduplikat 2x */
}
</style>
<?php endif; ?>

<?php include_once 'includes/footer.php'; ?>