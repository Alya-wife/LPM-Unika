<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Tinjau Berita & Kegiatan';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
if (!$id) {
    $_SESSION['flash'] = 'ID Berita tidak valid.';
    redirect(SITE_URL . '/admin/berita-list.php');
}

$stmt = $db->prepare("SELECT * FROM berita WHERE id = ?");
$stmt->execute([$id]);
$berita = $stmt->fetch();

if (!$berita) {
    $_SESSION['flash'] = 'Berita/kegiatan tidak ditemukan.';
    redirect(SITE_URL . '/admin/berita-list.php');
}

// Handle Publish via Review
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'publish') {
    $stmt_pub = $db->prepare("UPDATE berita SET status = 'published' WHERE id = ?");
    $stmt_pub->execute([$id]);
    $_SESSION['flash'] = 'Berita "' . $berita['judul'] . '" berhasil diterbitkan dan sekarang dapat diakses oleh publik!';
    redirect(SITE_URL . '/admin/berita-list.php');
}

// Foto slider tambahan jika ada
$stmt_img = $db->prepare("SELECT * FROM berita_gambar WHERE berita_id = ? ORDER BY urutan ASC, id ASC");
$stmt_img->execute([$id]);
$slider_images = $stmt_img->fetchAll();

$tgl = $berita['tanggal_publikasi'] ?: $berita['created_at'];
$item_ta = getTahunAkademik($tgl);
$current_status = $berita['status'] ?? 'draft';

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <!-- Breadcrumb -->
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.25rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);text-decoration:none;">Dashboard</a>
            <span>/</span>
            <a href="berita-list.php" style="color:var(--text-muted);text-decoration:none;">Berita &amp; Kegiatan</a>
            <span>/</span>
            <span style="color:var(--navy);font-weight:600;">Tinjau Berita</span>
        </div>

        <!-- Banner Panduan Tinjauan -->
        <div class="card mb-4 border-0 shadow-sm" style="border-radius:12px;background:<?= $current_status === 'draft' ? '#FFFBEB' : '#EFF6FF' ?>;border:1px solid <?= $current_status === 'draft' ? '#FCD34D' : '#BFDBFE' ?>;border-left:5px solid <?= $current_status === 'draft' ? '#F59E0B' : '#3B82F6' ?> !important;">
            <div class="card-body p-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:40px;height:40px;border-radius:10px;background:<?= $current_status === 'draft' ? '#FDE68A' : '#DBEAFE' ?>;display:flex;align-items:center;justify-content:center;color:<?= $current_status === 'draft' ? '#D97706' : '#1D4ED8' ?>;font-size:1.35rem;flex-shrink:0;">
                        <i class="bi <?= $current_status === 'draft' ? 'bi-eye-fill' : 'bi-check-circle-fill' ?>"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="color:<?= $current_status === 'draft' ? '#92400E' : '#1E40AF' ?>;font-size:0.95rem;">
                            <?= $current_status === 'draft' ? 'Mode Peninjauan Berita (Draft - Belum Terbit)' : 'Mode Peninjauan Berita (Sudah Terbit ke Publik)' ?>
                        </div>
                        <div class="small" style="color:<?= $current_status === 'draft' ? '#B45309' : '#1D4ED8' ?>;">
                            Periksa kembali keseluruhan judul, isi teks, tanggal, dan foto berita di bawah ini sebelum diterbitkan ke publik.
                        </div>
                    </div>
                </div>
                <div>
                    <a href="berita-list.php" class="btn btn-sm btn-outline-secondary" style="border-radius:6px;">
                        <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                    </a>
                </div>
            </div>
        </div>

        <!-- Preview Card Berita Lengkap -->
        <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden;background:#fff;border:1px solid #E2E8F0;">
            <!-- Header Preview -->
            <div class="p-4 border-bottom" style="background:#F8FAFC;">
                <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                    <span class="badge" style="background:#EDE9FE;color:#6D28D9;font-weight:700;font-size:0.75rem;padding:0.35rem 0.65rem;border-radius:6px;">
                        <?= e($berita['tipe'] ?: 'Berita') ?>
                    </span>

                    <?php if ($item_ta): ?>
                    <span class="badge" style="background:#E0F2FE;color:#0369A1;font-weight:700;font-size:0.75rem;padding:0.35rem 0.65rem;border-radius:6px;">
                        TA <?= e($item_ta) ?>
                    </span>
                    <?php endif; ?>

                    <?php if (!empty($berita['tampil_di_ami'])): ?>
                    <span class="badge" style="background:#FEF3C7;color:#D97706;border:1px solid #FCD34D;font-size:0.72rem;padding:0.3rem 0.6rem;border-radius:6px;font-weight:700;">
                        <i class="bi bi-star-fill"></i> Tampil di AMI
                    </span>
                    <?php endif; ?>

                    <span class="ms-auto badge" style="background:<?= $current_status === 'draft' ? '#FFFBEB' : '#ECFDF5' ?>;color:<?= $current_status === 'draft' ? '#B45309' : '#047857' ?>;border:1px solid <?= $current_status === 'draft' ? '#FDE68A' : '#A7F3D0' ?>;font-size:0.75rem;padding:0.35rem 0.65rem;border-radius:6px;font-weight:700;">
                        <i class="bi <?= $current_status === 'draft' ? 'bi-clock-history' : 'bi-check-circle-fill' ?>"></i>
                        Status: <?= $current_status === 'draft' ? 'Draft (Belum Upload)' : 'Terbit (Aktif di Website)' ?>
                    </span>
                </div>

                <h1 class="h3 fw-bold mb-3" style="color:var(--navy);line-height:1.35;"><?= e($berita['judul']) ?></h1>

                <div class="d-flex align-items-center gap-3 text-muted small flex-wrap">
                    <div>
                        <i class="bi bi-calendar3 me-1 text-primary"></i>
                        <?= formatTanggal($tgl) ?>
                    </div>
                    <div>
                        <i class="bi bi-person me-1 text-primary"></i>
                        Lembaga Penjaminan Mutu (LPM) UNIKA
                    </div>
                    <div>
                        <i class="bi bi-link-45deg me-1 text-primary"></i>
                        Slug: <code style="color:#475569;background:#F1F5F9;padding:1px 5px;border-radius:4px;"><?= e($berita['slug']) ?></code>
                    </div>
                </div>
            </div>

            <!-- Body Preview: Media & Konten -->
            <div class="p-4">
                <!-- Cover Image -->
                <?php if ($berita['gambar'] && file_exists(__DIR__ . '/../uploads/berita/' . $berita['gambar'])): ?>
                <div class="mb-4 text-center" style="background:#0F172A;border-radius:12px;overflow:hidden;padding:0.5rem;">
                    <img src="<?= SITE_URL ?>/uploads/berita/<?= e($berita['gambar']) ?>" alt="<?= e($berita['judul']) ?>" style="max-width:100%;max-height:480px;object-fit:contain;border-radius:8px;">
                    <div class="text-white-50 small mt-2 pb-1">
                        <i class="bi bi-image me-1"></i> Foto Cover Utama (WebP Teroptimasi)
                    </div>
                </div>
                <?php else: ?>
                <div class="alert alert-light border d-flex align-items-center gap-2 mb-4" style="border-radius:8px;font-size:0.85rem;color:#64748B;">
                    <i class="bi bi-info-circle text-secondary"></i>
                    Berita ini tidak memiliki foto cover utama. Tampilan akan menggunakan banner default standar.
                </div>
                <?php endif; ?>

                <!-- Foto Slider Tambahan jika ada -->
                <?php if (!empty($slider_images)): ?>
                <div class="mb-4 p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <strong style="font-size:0.85rem;color:var(--navy);">
                            <i class="bi bi-images me-1 text-primary"></i> Foto Tambahan / Galeri Slider (<?= count($slider_images) ?> Foto):
                        </strong>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                        <?php foreach ($slider_images as $s_img): ?>
                            <?php if (file_exists(__DIR__ . '/../uploads/berita/' . $s_img['gambar'])): ?>
                            <div style="width:90px;height:70px;border-radius:6px;overflow:hidden;border:1px solid #CBD5E1;box-shadow:0 1px 3px rgba(0,0,0,0.05);">
                                <img src="<?= SITE_URL ?>/uploads/berita/<?= e($s_img['gambar']) ?>" alt="" style="width:100%;height:100%;object-fit:cover;">
                            </div>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Teks Konten Lengkap -->
                <div class="berita-content-review" style="font-size:1rem;line-height:1.8;color:#334155;border-top:1px dashed #E2E8F0;padding-top:1.5rem;">
                    <?= nl2br(e($berita['konten'])) ?>
                </div>
            </div>

            <!-- BAGIAN PALING BAWAH: Aksi Tinjau Ulang (Edit atau Terbitkan) -->
            <div class="p-4 border-top" style="background:#F8FAFC;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="fw-bold" style="color:var(--navy);font-size:0.95rem;">
                            Apakah isi berita ini sudah benar?
                        </div>
                        <div class="small text-muted">
                            Jika masih ingin melakukan revisi teks atau foto, pilih <strong>Edit</strong>. Jika sudah sesuai, klik <strong>Terbitkan</strong>.
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2">
                        <!-- Tombol Edit -->
                        <a href="berita-form.php?id=<?= $berita['id'] ?>" class="btn btn-outline-secondary px-4 py-2 fw-semibold" style="border-radius:8px;font-size:0.9rem;">
                            <i class="bi bi-pencil-square me-1"></i> Edit
                        </a>

                        <!-- Tombol Terbitkan (Memicu Peringatan Kecil / Modal) -->
                        <button type="button" class="btn btn-success px-4 py-2 fw-bold shadow-sm" style="border-radius:8px;background:#16A34A;border:none;font-size:0.9rem;" data-bs-toggle="modal" data-bs-target="#modalPeringatanTerbit">
                            <i class="bi bi-cloud-arrow-up-fill me-1"></i> Terbitkan
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODAL PERINGATAN KECIL / KONFIRMASI PENERBITAN -->
<div class="modal fade" id="modalPeringatanTerbit" tabindex="-1" aria-labelledby="modalPeringatanTerbitLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width:440px;">
        <div class="modal-content border-0 shadow" style="border-radius:16px;overflow:hidden;">
            <div class="modal-header border-0 pb-0 pt-4 px-4 text-center d-block">
                <div style="width:58px;height:58px;border-radius:50%;background:#FEF3C7;color:#D97706;display:inline-flex;align-items:center;justify-content:center;font-size:1.85rem;margin-bottom:0.75rem;">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                </div>
                <h5 class="modal-title fw-bold" id="modalPeringatanTerbitLabel" style="color:var(--navy);font-size:1.15rem;">
                    Konfirmasi Penerbitan Berita
                </h5>
            </div>
            <div class="modal-body px-4 py-3 text-center">
                <p class="text-secondary small mb-3" style="line-height:1.6;">
                    Pastikan seluruh data, judul, foto, dan isi berita telah Anda periksa dengan teliti. Setelah diterbitkan, berita ini akan langsung tampil di halaman website resmi LPM UNIKA.
                </p>
                <div class="p-3 rounded-3 text-start small mb-2" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                    <div class="text-muted" style="font-size:0.75rem;">Judul yang akan diterbitkan:</div>
                    <div class="fw-bold text-dark mt-1" style="font-size:0.88rem;line-height:1.35;">
                        <?= e($berita['judul']) ?>
                    </div>
                </div>
                <div class="text-muted" style="font-size:0.82rem;">
                    Apakah Anda yakin ingin menerbitkan berita ini sekarang?
                </div>
            </div>
            <div class="modal-footer border-0 px-4 pb-4 pt-1 d-flex justify-content-center gap-2">
                <button type="button" class="btn btn-light px-3 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius:8px;font-size:0.88rem;">
                    Batal / Periksa Lagi
                </button>
                <form method="POST" style="margin:0;">
                    <input type="hidden" name="action" value="publish">
                    <button type="submit" class="btn btn-success px-4 py-2 fw-bold shadow-sm" style="border-radius:8px;background:#16A34A;border:none;font-size:0.88rem;">
                        <i class="bi bi-check-circle-fill me-1"></i> Iya, Terbitkan
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
