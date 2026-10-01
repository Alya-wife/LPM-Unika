<?php
require_once __DIR__ . '/config/database.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
if (!$slug) redirect(SITE_URL . '/berita.php');

$db   = getDB();
$stmt = $db->prepare("SELECT * FROM berita WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$berita = $stmt->fetch();

if (!$berita) {
    http_response_code(404);
    redirect(SITE_URL . '/berita.php');
}

$is_admin = !empty($_SESSION['admin_id']);
if (($berita['status'] ?? 'published') === 'draft' && !$is_admin) {
    http_response_code(404);
    redirect(SITE_URL . '/berita.php');
}

// Ambil semua foto (Cover + Galeri Slider)
$all_images = [];
if (!empty($berita['gambar']) && file_exists(__DIR__ . '/uploads/berita/' . $berita['gambar'])) {
    $all_images[] = $berita['gambar'];
}
$stmt_extras = $db->prepare("SELECT gambar FROM berita_gambar WHERE berita_id = ? ORDER BY urutan ASC, id ASC");
$stmt_extras->execute([(int)$berita['id']]);
while ($extra = $stmt_extras->fetch()) {
    if (!empty($extra['gambar']) && file_exists(__DIR__ . '/uploads/berita/' . $extra['gambar'])) {
        if (!in_array($extra['gambar'], $all_images)) {
            $all_images[] = $extra['gambar'];
        }
    }
}

// Berita lainnya (khusus yang sudah terbit)
$related = $db->prepare("SELECT * FROM berita WHERE status = 'published' AND slug != ? ORDER BY tanggal_publikasi DESC LIMIT 3");
$related->execute([$slug]);
$related_list = $related->fetchAll();

$tgl_publikasi = $berita['tanggal_publikasi'] ?: $berita['created_at'];
$tahun_akademik = getTahunAkademik($tgl_publikasi);

$page_title = $berita['judul'];
$meta_desc  = truncate($berita['konten'], 160);

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

if (($berita['status'] ?? 'published') === 'draft' && $is_admin): ?>
<div style="background:#FEF3C7;color:#92400E;border-bottom:2px solid #FCD34D;padding:0.75rem 1rem;text-align:center;font-size:0.9rem;font-weight:600;display:flex;align-items:center;justify-content:center;gap:12px;flex-wrap:wrap;">
    <span><i class="bi bi-shield-exclamation me-1"></i> Mode Pratinjau Draft: Berita ini belum diterbitkan ke publik.</span>
    <a href="<?= SITE_URL ?>/admin/berita-list.php?action=publish&id=<?= $berita['id'] ?>" class="btn btn-sm btn-success fw-bold px-3 py-1 shadow-sm" onclick="return confirm('Terbitkan berita ini ke publik?')">
        <i class="bi bi-cloud-arrow-up-fill me-1"></i> Terbitkan Sekarang
    </a>
    <a href="<?= SITE_URL ?>/admin/berita-form.php?id=<?= $berita['id'] ?>" class="btn btn-sm btn-outline-dark fw-semibold px-2 py-1">
        <i class="bi bi-pencil-square me-1"></i> Edit Berita
    </a>
</div>
<?php endif;
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <h1 class="page-banner-title" style="font-size:clamp(1.4rem,3vw,2.2rem);max-width:760px;">
            <?= e($berita['judul']) ?>
        </h1>
        <div class="breadcrumb-lpm mt-2">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/berita.php">Berita &amp; Kegiatan</a>
            <span>/</span>
            <span class="current"><?= e(mb_substr($berita['judul'], 0, 40)) ?>...</span>
        </div>
    </div>
</div>

<section class="py-5 py-md-6">
    <div class="container">
        <div class="row g-5">
            <!-- Main Content -->
            <div class="col-lg-8">
                <!-- Meta Info -->
                <div class="d-flex align-items-center gap-2 flex-wrap mb-4">
                    <span class="card-category-badge"><?= e($berita['tipe'] ?: 'Kegiatan LPM') ?></span>
                    <?php if ($tahun_akademik): ?>
                    <a href="<?= SITE_URL ?>/berita.php?ta=<?= urlencode($tahun_akademik) ?>" class="badge text-decoration-none" style="background:#EDE9FE;color:#6D28D9;font-weight:700;font-size:0.75rem;padding:0.35rem 0.7rem;border-radius:4px;">
                        TA <?= e($tahun_akademik) ?>
                    </a>
                    <?php endif; ?>
                    <span style="font-size:0.85rem;color:var(--text-muted);display:flex;align-items:center;gap:5px;margin-left:auto;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5" />
                        </svg>
                        <?= formatTanggal($tgl_publikasi) ?>
                    </span>
                </div>

                <!-- Media Section: Slider vs Single Image -->
                <?php if (count($all_images) > 1): ?>
                <!-- Carousel / Slider Kegiatan LPM -->
                <div class="kegiatan-slider-wrap mb-4" style="background:#0F172A;border-radius:var(--radius-lg);overflow:hidden;box-shadow:0 8px 30px rgba(0,0,0,0.12);">
                    <div id="kegiatanCarousel" class="carousel slide position-relative" data-bs-ride="carousel" data-bs-interval="4500">
                        
                        <!-- Floating Slide Counter -->
                        <div style="position:absolute;top:16px;right:16px;z-index:10;background:rgba(15,23,42,0.75);backdrop-filter:blur(6px);color:#fff;font-size:0.75rem;font-weight:700;padding:4px 12px;border-radius:20px;border:1px solid rgba(255,255,255,0.2);">
                            <span id="sliderCurrentIndex">1</span> / <?= count($all_images) ?> Foto
                        </div>

                        <!-- Slides -->
                        <div class="carousel-inner" style="max-height:500px;">
                            <?php foreach ($all_images as $idx => $img_name): ?>
                            <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                                <img src="<?= SITE_URL ?>/uploads/berita/<?= e($img_name) ?>" 
                                     alt="<?= e($berita['judul']) ?> - Foto <?= $idx + 1 ?>" 
                                     class="d-block w-100" 
                                     style="height:480px;object-fit:contain;background:#090D16;cursor:pointer;"
                                     onclick="openImageModal('<?= SITE_URL ?>/uploads/berita/<?= e($img_name) ?>')">
                            </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Prev Button -->
                        <button class="carousel-control-prev" type="button" data-bs-target="#kegiatanCarousel" data-bs-slide="prev" style="width:50px;opacity:0.9;">
                            <span class="d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(15,23,42,0.65);backdrop-filter:blur(4px);border-radius:50%;border:1px solid rgba(255,255,255,0.25);transition:all 0.2s;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="#fff" width="18" height="18">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
                                </svg>
                            </span>
                            <span class="visually-hidden">Previous</span>
                        </button>

                        <!-- Next Button -->
                        <button class="carousel-control-next" type="button" data-bs-target="#kegiatanCarousel" data-bs-slide="next" style="width:50px;opacity:0.9;">
                            <span class="d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(15,23,42,0.65);backdrop-filter:blur(4px);border-radius:50%;border:1px solid rgba(255,255,255,0.25);transition:all 0.2s;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="#fff" width="18" height="18">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m8.25 4.5 7.5 7.5-7.5 7.5" />
                                </svg>
                            </span>
                            <span class="visually-hidden">Next</span>
                        </button>
                    </div>

                    <!-- Thumbnails Strip -->
                    <div class="p-2 d-flex gap-2 justify-content-center flex-wrap" style="background:#0F172A;border-top:1px solid rgba(255,255,255,0.08);">
                        <?php foreach ($all_images as $idx => $img_name): ?>
                        <div class="carousel-thumb-item <?= $idx === 0 ? 'active' : '' ?>" 
                             data-bs-target="#kegiatanCarousel" 
                             data-bs-slide-to="<?= $idx ?>" 
                             id="thumb-<?= $idx ?>"
                             style="width:68px;height:48px;border-radius:6px;overflow:hidden;cursor:pointer;border:2px solid <?= $idx === 0 ? '#8B5CF6' : 'rgba(255,255,255,0.2)' ?>;opacity:<?= $idx === 0 ? '1' : '0.6' ?>;transition:all 0.2s;"
                             onmouseover="this.style.opacity='1'" 
                             onmouseout="if(!this.classList.contains('active')) this.style.opacity='0.6'">
                            <img src="<?= SITE_URL ?>/uploads/berita/<?= e($img_name) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <?php elseif (count($all_images) === 1): ?>
                <!-- Single Featured Image -->
                <div style="border-radius:var(--radius-lg);overflow:hidden;margin-bottom:2rem;box-shadow:0 4px 20px rgba(0,0,0,0.08);">
                    <img src="<?= SITE_URL ?>/uploads/berita/<?= e($all_images[0]) ?>" alt="<?= e($berita['judul']) ?>" style="width:100%;max-height:460px;object-fit:cover;cursor:pointer;" onclick="openImageModal('<?= SITE_URL ?>/uploads/berita/<?= e($all_images[0]) ?>')">
                </div>
                <?php endif; ?>

                <!-- Content -->
                <div style="font-size:1.02rem;color:var(--text-muted);line-height:1.9;margin-bottom:2rem;">
                    <?= nl2br(e($berita['konten'])) ?>
                </div>

                <!-- Share Buttons -->
                <div style="border-top:1px solid var(--border);padding-top:1.5rem;display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
                    <span style="font-size:0.85rem;font-weight:600;color:var(--navy);font-family:var(--font-heading);">Bagikan:</span>
                    <a href="https://www.facebook.com/sharer/sharer.php?u=<?= urlencode(SITE_URL . '/berita-detail.php?slug=' . $berita['slug']) ?>" target="_blank" class="btn-action btn-edit">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                        </svg>
                        Facebook
                    </a>
                    <a href="https://twitter.com/intent/tweet?url=<?= urlencode(SITE_URL . '/berita-detail.php?slug=' . $berita['slug']) ?>&text=<?= urlencode($berita['judul']) ?>" target="_blank" class="btn-action" style="background:#E7F5FF;color:#1565C0;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                            <path d="M18.244 2.25h3.308l-7.227 8.26 8.502 11.24H16.17l-5.214-6.817L4.99 21.75H1.68l7.73-8.835L1.254 2.25H8.08l4.713 6.231zm-1.161 17.52h1.833L7.084 4.126H5.117z"/>
                        </svg>
                        Twitter/X
                    </a>
                    <a href="https://wa.me/?text=<?= urlencode($berita['judul'] . ' ' . SITE_URL . '/berita-detail.php?slug=' . $berita['slug']) ?>" target="_blank" class="btn-action" style="background:#E8F5E9;color:#2E7D32;">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" width="14" height="14">
                            <path d="M17.472 14.382c-.297-.149-1.758-.867-2.03-.967-.273-.099-.471-.148-.67.15-.197.297-.767.966-.94 1.164-.173.199-.347.223-.644.075-.297-.15-1.255-.463-2.39-1.475-.883-.788-1.48-1.761-1.653-2.059-.173-.297-.018-.458.13-.606.134-.133.298-.347.446-.52.149-.174.198-.298.298-.497.099-.198.05-.371-.025-.52-.075-.149-.669-1.612-.916-2.207-.242-.579-.487-.5-.669-.51-.173-.008-.371-.01-.57-.01-.198 0-.52.074-.792.372-.272.297-1.04 1.016-1.04 2.479 0 1.462 1.065 2.875 1.213 3.074.149.198 2.096 3.2 5.077 4.487.709.306 1.262.489 1.694.625.712.227 1.36.195 1.871.118.571-.085 1.758-.719 2.006-1.413.248-.694.248-1.289.173-1.413-.074-.124-.272-.198-.57-.347m-5.421 7.403h-.004a9.87 9.87 0 0 1-5.031-1.378l-.361-.214-3.741.982.998-3.648-.235-.374a9.86 9.86 0 0 1-1.51-5.26c.001-5.45 4.436-9.884 9.888-9.884 2.64 0 5.122 1.03 6.988 2.898a9.825 9.825 0 0 1 2.893 6.994c-.003 5.45-4.437 9.884-9.885 9.884m8.413-18.297A11.815 11.815 0 0 0 12.05 0C5.495 0 .16 5.335.157 11.892c0 2.096.547 4.142 1.588 5.945L.057 24l6.305-1.654a11.882 11.882 0 0 0 5.683 1.448h.005c6.554 0 11.89-5.335 11.893-11.893a11.821 11.821 0 0 0-3.48-8.413Z"/>
                        </svg>
                        WhatsApp
                    </a>
                </div>
            </div>

            <!-- Sidebar -->
            <div class="col-lg-4">
                <div style="position:sticky;top:100px;">
                    <div style="font-family:var(--font-heading);font-size:1rem;font-weight:700;color:var(--navy);margin-bottom:1.25rem;padding-bottom:0.75rem;border-bottom:2px solid var(--purple);">
                        Berita / Kegiatan Lainnya
                    </div>
                    <?php if (empty($related_list)): ?>
                    <p style="font-size:0.85rem;color:var(--text-muted);">Belum ada berita lainnya.</p>
                    <?php else: ?>
                    <?php foreach ($related_list as $r): 
                        $rel_tgl = $r['tanggal_publikasi'] ?: $r['created_at'];
                        $rel_ta  = getTahunAkademik($rel_tgl);
                    ?>
                    <a href="berita-detail.php?slug=<?= e($r['slug']) ?>" class="d-flex gap-3 mb-3 text-decoration-none" style="padding:0.875rem;background:var(--bg-white);border:1px solid var(--border);border-radius:var(--radius-sm);transition:var(--transition);" onmouseover="this.style.borderColor='rgba(106,27,154,0.3)'" onmouseout="this.style.borderColor='var(--border)'">
                        <div style="flex-shrink:0;width:60px;height:60px;border-radius:8px;background:linear-gradient(135deg,var(--navy-mid),var(--purple-dark));display:flex;align-items:center;justify-content:center;overflow:hidden;">
                            <?php if ($r['gambar'] && file_exists(__DIR__ . '/uploads/berita/' . $r['gambar'])): ?>
                                <img src="<?= SITE_URL ?>/uploads/berita/<?= e($r['gambar']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="rgba(255,255,255,0.5)" width="26" height="26">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <div style="font-size:0.83rem;font-weight:600;color:var(--navy);line-height:1.4;"><?= e(mb_substr($r['judul'], 0, 55)) ?><?= mb_strlen($r['judul']) > 55 ? '...' : '' ?></div>
                            <div class="d-flex align-items-center gap-2 mt-1" style="font-size:0.72rem;color:var(--text-muted);">
                                <?php if ($rel_ta): ?>
                                <span class="badge" style="background:#EDE9FE;color:#6D28D9;font-weight:600;font-size:0.68rem;padding:1px 5px;border-radius:3px;">
                                    TA <?= e($rel_ta) ?>
                                </span>
                                <?php endif; ?>
                                <span><?= formatTanggal($rel_tgl) ?></span>
                            </div>
                        </div>
                    </a>
                    <?php endforeach; ?>
                    <?php endif; ?>

                    <a href="berita.php" class="btn-add mt-3 w-100 justify-content-center">
                        Lihat Semua Berita &amp; Kegiatan
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Image Lightbox Modal -->
<div class="modal fade" id="imageLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="background:transparent;border:none;">
            <div class="modal-body p-0 position-relative text-center">
                <button type="button" class="btn-close btn-close-white position-absolute" style="top:-35px;right:0;filter:drop-shadow(0 2px 4px rgba(0,0,0,0.5));" data-bs-dismiss="modal" aria-label="Close"></button>
                <img id="lightboxImg" src="" alt="" style="max-width:100%;max-height:85vh;object-fit:contain;border-radius:8px;box-shadow:0 10px 40px rgba(0,0,0,0.5);">
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const carouselEl = document.getElementById('kegiatanCarousel');
    if (carouselEl) {
        const counterEl = document.getElementById('sliderCurrentIndex');
        const thumbs = document.querySelectorAll('.carousel-thumb-item');

        carouselEl.addEventListener('slide.bs.carousel', function(event) {
            const nextIndex = event.to;
            if (counterEl) {
                counterEl.textContent = nextIndex + 1;
            }
            thumbs.forEach((t, i) => {
                if (i === nextIndex) {
                    t.classList.add('active');
                    t.style.borderColor = '#8B5CF6';
                    t.style.opacity = '1';
                } else {
                    t.classList.remove('active');
                    t.style.borderColor = 'rgba(255,255,255,0.2)';
                    t.style.opacity = '0.6';
                }
            });
        });
    }
});

function openImageModal(imgSrc) {
    const modalImg = document.getElementById('lightboxImg');
    if (modalImg) {
        modalImg.src = imgSrc;
        const modal = new bootstrap.Modal(document.getElementById('imageLightboxModal'));
        modal.show();
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
