<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$is_edit = false;
$slide   = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM hero_slides WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $slide = $stmt->fetch();
    if ($slide) $is_edit = true;
}

$admin_page_title = $is_edit ? 'Edit Gambar Slide Beranda' : 'Tambah Gambar Slide Beranda';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul        = trim($_POST['judul'] ?? '');
    $urutan       = (int)($_POST['urutan'] ?? 1);
    $is_active    = isset($_POST['is_active']) ? 1 : 0;
    $id           = (int)($_POST['id'] ?? 0);
    $cropped_data = trim($_POST['cropped_image_data'] ?? '');
    $image_url    = trim($_POST['image_url'] ?? '');

    // Nilai default untuk kolom teks lama agar kompatibel dengan tabel hero_slides
    $subjudul           = $is_edit ? ($slide['subjudul'] ?? '') : '';
    $highlight_text     = $is_edit ? ($slide['highlight_text'] ?? '') : '';
    $deskripsi          = $is_edit ? ($slide['deskripsi'] ?? '') : '';
    $btn_text           = $is_edit ? ($slide['btn_text'] ?? '') : '';
    $btn_link           = $is_edit ? ($slide['btn_link'] ?? '') : '';
    $btn_secondary_text = $is_edit ? ($slide['btn_secondary_text'] ?? '') : '';
    $btn_secondary_link = $is_edit ? ($slide['btn_secondary_link'] ?? '') : '';

    if (!$judul) {
        $error = 'Label / Nama slide wajib diisi.';
    } else {
        $gambar = $is_edit ? ($slide['gambar'] ?? '') : '';
        $dir = __DIR__ . '/../uploads/slides/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        // 1. Prioritaskan gambar hasil crop / rotate / flip (Base64)
        if (!empty($cropped_data)) {
            if (preg_match('/^data:image\/(\w+);base64,/', $cropped_data, $type)) {
                $raw_data = substr($cropped_data, strpos($cropped_data, ',') + 1);
                $decoded = base64_decode($raw_data);
                if ($decoded === false) {
                    $error = 'Gagal memproses data gambar hasil penyesuaian.';
                } else {
                    $tmpFile = tempnam(sys_get_temp_dir(), 'slide_crop_');
                    file_put_contents($tmpFile, $decoded);
                    $saved_slide = convertAndSaveWebP($tmpFile, $dir, 'slide_', 88, 1920);
                    @unlink($tmpFile);

                    if ($saved_slide) {
                        // Hapus file lama jika ada dan bukan link eksternal
                        if ($is_edit && $slide['gambar'] && !filter_var($slide['gambar'], FILTER_VALIDATE_URL)) {
                            $old_path = $dir . $slide['gambar'];
                            if (file_exists($old_path)) @unlink($old_path);
                        }
                        $gambar = $saved_slide;
                    } else {
                        $error = 'Gagal mengonversi gambar ke format WebP.';
                    }
                }
            }
        }
        // 2. Jika file diupload biasa tanpa penyesuaian crop
        elseif (!empty($_FILES['gambar_file']['name'])) {
            $allowed = ['jpg','jpeg','png','webp','gif'];
            $ext = strtolower(pathinfo($_FILES['gambar_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['gambar_file']['size'] > 5 * 1024 * 1024) {
                $error = 'Ukuran file gambar maksimal 5MB.';
            } else {
                $saved_slide = convertAndSaveWebP($_FILES['gambar_file']['tmp_name'], $dir, 'slide_', 88, 1920);
                if ($saved_slide) {
                    if ($is_edit && $slide['gambar'] && !filter_var($slide['gambar'], FILTER_VALIDATE_URL)) {
                        $old_path = $dir . $slide['gambar'];
                        if (file_exists($old_path)) @unlink($old_path);
                    }
                    $gambar = $saved_slide;
                } else {
                    $error = 'Gagal memproses file gambar.';
                }
            }
        }
        // 3. Jika memasukkan URL gambar eksternal
        elseif (!empty($image_url)) {
            $gambar = $image_url;
        }

        if (!$error) {
            if (empty($gambar)) {
                $error = 'Gambar latar belakang slide wajib dipilih atau diunggah.';
            } else {
                if ($is_edit && $id) {
                    $stmt = $db->prepare("UPDATE hero_slides SET judul=?, subjudul=?, highlight_text=?, deskripsi=?, gambar=?, btn_text=?, btn_link=?, btn_secondary_text=?, btn_secondary_link=?, urutan=?, is_active=? WHERE id=?");
                    $stmt->execute([$judul, $subjudul, $highlight_text, $deskripsi, $gambar, $btn_text, $btn_link, $btn_secondary_text, $btn_secondary_link, $urutan, $is_active, $id]);
                    $_SESSION['flash'] = 'Gambar slide berhasil diperbarui.';
                } else {
                    $stmt = $db->prepare("INSERT INTO hero_slides (judul, subjudul, highlight_text, deskripsi, gambar, btn_text, btn_link, btn_secondary_text, btn_secondary_link, urutan, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                    $stmt->execute([$judul, $subjudul, $highlight_text, $deskripsi, $gambar, $btn_text, $btn_link, $btn_secondary_text, $btn_secondary_link, $urutan, $is_active]);
                    $_SESSION['flash'] = 'Gambar slide baru berhasil ditambahkan.';
                }
                redirect(SITE_URL . '/admin/slider-list.php');
            }
        }
    }
}

// Persiapkan URL gambar saat ini jika ada
$current_image_src = '';
if ($is_edit && !empty($slide['gambar'])) {
    $current_image_src = filter_var($slide['gambar'], FILTER_VALIDATE_URL) 
        ? $slide['gambar'] 
        : SITE_URL . '/uploads/slides/' . $slide['gambar'];
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Cropper.js CSS -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<style>
.upload-dropzone {
    border: 2px dashed #CBD5E1;
    background: #F8FAFC;
    border-radius: 14px;
    padding: 2.5rem 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.25s ease;
}
.upload-dropzone:hover, .upload-dropzone.dragover {
    border-color: #2563EB;
    background: #EFF6FF;
}
.banner-preview-box {
    width: 100%;
    aspect-ratio: 16 / 7;
    max-height: 340px;
    background: #0F172A;
    border-radius: 14px;
    overflow: hidden;
    position: relative;
    border: 1.5px solid #E2E8F0;
    box-shadow: 0 4px 20px rgba(0,0,0,0.08);
    display: flex;
    align-items: center;
    justify-content: center;
}
.banner-preview-box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center;
}
.crop-modal-container {
    max-height: 500px;
    background: #0B132B;
    display: flex;
    align-items: center;
    justify-content: center;
    overflow: hidden;
    border-radius: 12px;
}
.crop-modal-container img {
    max-width: 100%;
    max-height: 480px;
    display: block;
}
</style>

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <!-- Breadcrumb -->
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="slider-list.php" style="color:var(--text-muted);">Slider Beranda</a>
            <span>/</span>
            <span style="color:var(--navy);font-weight:600;"><?= $admin_page_title ?></span>
        </div>

        <?php if ($error): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 rounded-3 mb-4 shadow-sm" role="alert">
            <i class="bi bi-exclamation-triangle-fill fs-5"></i>
            <div><?= e($error) ?></div>
        </div>
        <?php endif; ?>

        <!-- Form Card -->
        <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden mb-4">
            <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div class="d-flex align-items-center gap-2">
                    <div style="width:38px;height:38px;border-radius:10px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;color:#6D28D9;font-size:1.15rem;">
                        <i class="bi bi-image"></i>
                    </div>
                    <div>
                        <h5 class="m-0 fw-bold" style="font-size:1.05rem;color:var(--navy);"><?= $admin_page_title ?></h5>
                        <small class="text-muted">Kelola gambar slide background yang bergeser di belakang teks utama beranda.</small>
                    </div>
                </div>
                <a href="slider-list.php" class="btn btn-outline-secondary btn-sm px-3 fw-semibold" style="border-radius:8px;">
                    <i class="bi bi-arrow-left me-1"></i> Kembali
                </a>
            </div>

            <div class="card-body p-4">
                <form method="POST" enctype="multipart/form-data" id="slideForm">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?= $slide['id'] ?>">
                    <?php endif; ?>

                    <!-- Input Tersembunyi untuk Data Hasil Crop (Base64) -->
                    <input type="hidden" name="cropped_image_data" id="croppedImageData" value="">

                    <!-- Baris 1: Label Slide & Urutan -->
                    <div class="row g-3 mb-4">
                        <div class="col-md-8">
                            <label class="form-label fw-bold" style="color:var(--navy);font-size:0.9rem;">
                                Label / Nama Slide (Referensi Admin) <span class="text-danger">*</span>
                            </label>
                            <input type="text" name="judul" class="form-control py-2 px-3" style="border-radius:8px;border:1.5px solid #CBD5E1;" placeholder="Contoh: Gedung Thomas Aquinas / Suasana Kampus Bendan" value="<?= e($is_edit ? $slide['judul'] : ($_POST['judul'] ?? '')) ?>" required>
                            <small class="text-muted">Nama ini hanya digunakan sebagai keterangan pengenal foto di tabel admin.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold" style="color:var(--navy);font-size:0.9rem;">
                                Urutan Tampil
                            </label>
                            <input type="number" name="urutan" class="form-control py-2 px-3" style="border-radius:8px;border:1.5px solid #CBD5E1;" min="1" value="<?= (int)($is_edit ? $slide['urutan'] : ($_POST['urutan'] ?? 1)) ?>">
                            <small class="text-muted">Urutan rotasi slide foto (1 = pertama).</small>
                        </div>
                    </div>

                    <!-- Baris 2: Input Gambar Simpel dengan Crop, Rotate, dan Flip -->
                    <div class="mb-4">
                        <label class="form-label fw-bold d-flex justify-content-between align-items-center mb-2" style="color:var(--navy);font-size:0.95rem;">
                            <span><i class="bi bi-card-image me-1 text-primary"></i> Gambar Latar Belakang Slide <span class="text-danger">*</span></span>
                            <small class="text-muted fw-normal">Rasio ideal: Landscape 16:9 (1920 × 1080 px)</small>
                        </label>

                        <!-- Area Dropzone / Pilih File Baru (Sembunyi jika sudah ada gambar aktif) -->
                        <div id="dropzoneArea" class="upload-dropzone mb-3 <?= !empty($current_image_src) ? 'd-none' : '' ?>">
                            <div class="mb-3">
                                <i class="bi bi-cloud-arrow-up text-primary" style="font-size:2.8rem;"></i>
                            </div>
                            <h6 class="fw-bold mb-1" style="color:var(--navy);">Pilih atau Tarik File Gambar ke Sini</h6>
                            <p class="text-muted small mb-3">Mendukung format JPG, PNG, atau WEBP (Maksimal 5MB). Otomatis dikonversi ke format WebP.</p>
                            <button type="button" class="btn btn-primary px-4 py-2 fw-semibold" id="btnSelectFile" style="border-radius:8px;background:var(--navy);border-color:var(--navy);">
                                <i class="bi bi-folder2-open me-1"></i> Pilih File Gambar
                            </button>
                            <input type="file" id="gambarFileInput" name="gambar_file" accept="image/*" class="d-none">

                            <div class="mt-3 pt-2 border-top">
                                <a href="javascript:void(0)" class="text-decoration-none small text-secondary fw-semibold" id="btnToggleUrlInput">
                                    <i class="bi bi-link-45deg me-1"></i> Atau gunakan link URL gambar eksternal (Unsplash/Web)
                                </a>
                            </div>
                        </div>

                        <!-- Input URL Eksternal (Opsional / Collapse) -->
                        <div id="urlInputWrapper" class="mb-3 p-3 bg-light rounded-3 border" style="display:none;">
                            <label class="form-label small fw-bold text-muted mb-1">URL Gambar Eksternal:</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="bi bi-link"></i></span>
                                <input type="url" name="image_url" id="imageUrlInput" class="form-control" placeholder="https://images.unsplash.com/..." value="<?= ($is_edit && filter_var($slide['gambar'] ?? '', FILTER_VALIDATE_URL)) ? e($slide['gambar']) : '' ?>">
                                <button type="button" class="btn btn-outline-primary fw-semibold" id="btnApplyUrl">Gunakan URL</button>
                            </div>
                            <small class="text-muted d-block mt-1">Masukkan URL langsung ke file gambar (berakhir dengan .jpg, .png, dll atau Unsplash direct link).</small>
                        </div>

                        <!-- Area Live Preview Gambar Aktif & Kontrol Penyesuaian -->
                        <div id="previewCardArea" class="<?= empty($current_image_src) ? 'd-none' : '' ?>">
                            <div class="banner-preview-box mb-2">
                                <img id="bannerPreviewImg" src="<?= e($current_image_src) ?>" alt="Preview Slide">
                            </div>

                            <!-- Baris Tombol Aksi: Crop, Rotate, Flip, dan Ganti Gambar -->
                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 p-3 bg-light rounded-3 border">
                                <div class="d-flex align-items-center gap-2">
                                    <!-- Tombol Buka Modal Cropper -->
                                    <button type="button" class="btn btn-primary fw-bold d-inline-flex align-items-center gap-2" id="btnOpenCropperModal" style="background:#1E3A8A;border-color:#1E3A8A;border-radius:8px;">
                                        <i class="bi bi-crop text-warning fs-6"></i>
                                        <span>Sesuaikan Gambar (Crop, Rotate &amp; Flip)</span>
                                    </button>

                                    <!-- Tombol Ganti Foto -->
                                    <button type="button" class="btn btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-1" id="btnChangeImage" style="border-radius:8px;">
                                        <i class="bi bi-arrow-repeat"></i> Ganti Gambar
                                    </button>
                                </div>

                                <div id="cropStatusBadge" class="d-none">
                                    <span class="badge bg-success-subtle text-success border border-success px-3 py-2 fw-semibold" style="font-size:0.82rem;border-radius:20px;">
                                        <i class="bi bi-check-circle-fill me-1"></i> Gambar telah disesuaikan &amp; siap disimpan
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Baris 3: Status Aktif Switch -->
                    <div class="mb-4 pt-3 border-top">
                        <div class="form-check form-switch fs-6">
                            <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" <?= (!$is_edit || $slide['is_active']) ? 'checked' : '' ?> style="cursor:pointer;">
                            <label class="form-check-label fw-bold ms-2" for="is_active" style="color:var(--navy);cursor:pointer;">
                                Aktifkan Slide ini (Ditampilkan dalam rotasi slider beranda)
                            </label>
                        </div>
                        <small class="text-muted ms-5 d-block">Jika dinonaktifkan, gambar slide ini tidak akan dimuat pada banner beranda pengunjung.</small>
                    </div>

                    <!-- Baris Tombol Submit -->
                    <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                        <a href="slider-list.php" class="btn btn-link text-muted text-decoration-none fw-semibold">
                            Batal
                        </a>
                        <button type="submit" class="btn btn-primary px-4 py-2 fw-bold d-inline-flex align-items-center gap-2" style="background:var(--navy);border-color:var(--navy);border-radius:8px;">
                            <i class="bi bi-check-lg fs-5"></i>
                            <?= $is_edit ? 'Simpan Perubahan Slide' : 'Tambahkan Slide Baru' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- ==============================================================
     MODAL CROPPER.JS DENGAN FITUR CROP, ROTATE, DAN FLIP
============================================================== -->
<div class="modal fade" id="modalCropSlide" tabindex="-1" aria-labelledby="modalCropSlideLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
            <!-- Modal Header -->
            <div class="modal-header text-white" style="background:linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);padding:1.25rem 1.75rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-crop" style="font-size:1.4rem;color:#FFD54F;"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="modalCropSlideLabel">Sesuaikan Gambar Slide Banner</h5>
                        <small style="color:rgba(255,255,255,0.85);">Gunakan fitur Crop rasio banner, Putar (Rotate 90°), Cermin (Flip Horizontal &amp; Vertical), dan Zoom.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-3 p-md-4" style="background:#F1F5F9;">
                <!-- Cropper Canvas Container -->
                <div class="crop-modal-container mb-3 shadow-inner">
                    <img id="cropperImageSource" src="" alt="Source Image">
                </div>

                <!-- Toolbar Kontrol: Flip, Rotate, Zoom, dan Rasio -->
                <div class="card p-3 border shadow-sm rounded-3 bg-white">
                    <div class="row g-2 align-items-center">
                        
                        <!-- 1. Fitur Flip (Horizontal & Vertical) -->
                        <div class="col-lg-4 col-md-6 col-12 d-flex gap-2">
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipH" title="Balik gambar secara cermin horizontal (kiri-kanan)">
                                <i class="bi bi-symmetry-vertical text-warning"></i>
                                <span>Flip Horizontal (⇄)</span>
                            </button>
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipV" title="Balik gambar secara cermin vertikal (atas-bawah)">
                                <i class="bi bi-symmetry-horizontal text-warning"></i>
                                <span>Flip Vertical (⇅)</span>
                            </button>
                        </div>

                        <!-- 2. Fitur Rotate (Kiri 90° & Kanan 90°) -->
                        <div class="col-lg-3 col-md-6 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateLeft" title="Putar 90 Derajat ke Kiri">
                                <i class="bi bi-arrow-counterclockwise"></i> ↺ Putar Kiri
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateRight" title="Putar 90 Derajat ke Kanan">
                                <i class="bi bi-arrow-clockwise"></i> ↻ Putar Kanan
                            </button>
                        </div>

                        <!-- 3. Fitur Zoom In, Zoom Out & Reset -->
                        <div class="col-lg-5 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomIn" title="Perbesar (Zoom In)">
                                <i class="bi bi-zoom-in"></i> Zoom +
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomOut" title="Perkecil (Zoom Out)">
                                <i class="bi bi-zoom-out"></i> Zoom -
                            </button>
                            <button type="button" class="btn btn-outline-danger flex-fill fw-bold py-2" id="btnResetCrop" title="Kembalikan semua penyesuaian ke posisi awal">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- 4. Pilihan Rasio Aspek Banner -->
                    <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top flex-wrap">
                        <span class="text-muted small fw-bold me-1 text-uppercase" style="letter-spacing:0.5px;">
                            <i class="bi bi-aspect-ratio me-1 text-primary"></i> Rasio Crop:
                        </span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active ratio-btn fw-semibold" data-ratio="1.777778">
                                16:9 (Landscape Standar Web)
                            </button>
                            <button type="button" class="btn btn-outline-primary ratio-btn fw-semibold" data-ratio="2.333333">
                                21:9 (Ultra-wide Banner)
                            </button>
                            <button type="button" class="btn btn-outline-primary ratio-btn fw-semibold" data-ratio="NaN">
                                Bebas (Free Ratio)
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="modal-footer d-flex justify-content-between" style="background:#ffffff;border-top:1px solid #E2E8F0;padding:1rem 1.75rem;">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius:8px;">
                    Batal
                </button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-bold d-inline-flex align-items-center gap-2" id="btnApplyCrop" style="background:var(--navy);border-color:var(--navy);border-radius:8px;">
                    <i class="bi bi-check-lg fs-5"></i>
                    Terapkan Hasil Penyesuaian
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Cropper.js JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Elements
    const dropzoneArea       = document.getElementById('dropzoneArea');
    const previewCardArea    = document.getElementById('previewCardArea');
    const bannerPreviewImg   = document.getElementById('bannerPreviewImg');
    const fileInput          = document.getElementById('gambarFileInput');
    const btnSelectFile      = document.getElementById('btnSelectFile');
    const btnChangeImage     = document.getElementById('btnChangeImage');
    const btnOpenModal       = document.getElementById('btnOpenCropperModal');
    const cropModalEl        = document.getElementById('modalCropSlide');
    const cropImageSource    = document.getElementById('cropperImageSource');
    const btnApplyCrop       = document.getElementById('btnApplyCrop');
    const hiddenCroppedData  = document.getElementById('croppedImageData');
    const cropStatusBadge    = document.getElementById('cropStatusBadge');

    // External URL elements
    const btnToggleUrlInput  = document.getElementById('btnToggleUrlInput');
    const urlInputWrapper    = document.getElementById('urlInputWrapper');
    const imageUrlInput      = document.getElementById('imageUrlInput');
    const btnApplyUrl        = document.getElementById('btnApplyUrl');

    const bsCropModal = new bootstrap.Modal(cropModalEl);

    let cropperInstance = null;
    let currentScaleX   = 1;
    let currentScaleY   = 1;
    let activeRatio     = 16 / 9; // default 16:9 landscape

    // 1. File Selection Trigger
    if (btnSelectFile) {
        btnSelectFile.addEventListener('click', () => fileInput.click());
    }
    if (btnChangeImage) {
        btnChangeImage.addEventListener('click', () => fileInput.click());
    }

    // Toggle External URL Input
    if (btnToggleUrlInput) {
        btnToggleUrlInput.addEventListener('click', function() {
            if (urlInputWrapper.style.display === 'none') {
                urlInputWrapper.style.display = 'block';
                imageUrlInput.focus();
            } else {
                urlInputWrapper.style.display = 'none';
            }
        });
    }

    if (btnApplyUrl) {
        btnApplyUrl.addEventListener('click', function() {
            const url = imageUrlInput.value.trim();
            if (!url) {
                alert('Silakan masukkan link URL gambar yang valid.');
                return;
            }
            bannerPreviewImg.src = url;
            cropImageSource.src = url;
            dropzoneArea.classList.add('d-none');
            previewCardArea.classList.remove('d-none');
            cropStatusBadge.classList.add('d-none');
            hiddenCroppedData.value = '';
        });
    }

    // Drag and drop events on Dropzone
    ['dragenter', 'dragover'].forEach(eventName => {
        dropzoneArea.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzoneArea.classList.add('dragover');
        }, false);
    });

    ['dragleave', 'drop'].forEach(eventName => {
        dropzoneArea.addEventListener(eventName, (e) => {
            e.preventDefault();
            e.stopPropagation();
            dropzoneArea.classList.remove('dragover');
        }, false);
    });

    dropzoneArea.addEventListener('drop', (e) => {
        const dt = e.dataTransfer;
        const files = dt.files;
        if (files && files.length > 0) {
            handleSelectedFile(files[0]);
        }
    });

    fileInput.addEventListener('change', function(e) {
        if (e.target.files && e.target.files.length > 0) {
            handleSelectedFile(e.target.files[0]);
        }
    });

    function handleSelectedFile(file) {
        if (!file.type.match(/^image\//)) {
            alert('File harus berupa gambar dengan format JPG, PNG, atau WEBP.');
            return;
        }
        if (file.size > 5 * 1024 * 1024) {
            alert('Ukuran file gambar melebihi batas maksimal 5MB.');
            return;
        }

        const reader = new FileReader();
        reader.onload = function(evt) {
            const imgSrc = evt.target.result;
            cropImageSource.src = imgSrc;
            bannerPreviewImg.src = imgSrc;

            dropzoneArea.classList.add('d-none');
            previewCardArea.classList.remove('d-none');

            // Buka modal cropper secara otomatis agar admin langsung bisa crop/rotate/flip jika diinginkan
            bsCropModal.show();
        };
        reader.readAsDataURL(file);
    }

    // Tombol buka modal manual dari kartu preview
    btnOpenModal.addEventListener('click', function() {
        if (!cropImageSource.src || cropImageSource.src === window.location.href) {
            cropImageSource.src = bannerPreviewImg.src;
        }
        bsCropModal.show();
    });

    // Inisialisasi Cropper saat Modal tampil
    cropModalEl.addEventListener('shown.bs.modal', function() {
        if (cropperInstance) {
            cropperInstance.destroy();
        }
        currentScaleX = 1;
        currentScaleY = 1;

        cropperInstance = new Cropper(cropImageSource, {
            aspectRatio: activeRatio,
            viewMode: 1,
            dragMode: 'move',
            autoCropArea: 0.95,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false,
            checkCrossOrigin: false,
        });
    });

    // Bersihkan Cropper saat Modal ditutup
    cropModalEl.addEventListener('hidden.bs.modal', function() {
        if (cropperInstance) {
            cropperInstance.destroy();
            cropperInstance = null;
        }
    });

    // 1. FITUR FLIP HORIZONTAL (Kiri-Kanan)
    const btnFlipH = document.getElementById('btnFlipH');
    btnFlipH.addEventListener('click', function() {
        if (!cropperInstance) return;
        currentScaleX = currentScaleX === 1 ? -1 : 1;
        cropperInstance.scaleX(currentScaleX);
    });

    // 2. FITUR FLIP VERTICAL (Atas-Bawah)
    const btnFlipV = document.getElementById('btnFlipV');
    btnFlipV.addEventListener('click', function() {
        if (!cropperInstance) return;
        currentScaleY = currentScaleY === 1 ? -1 : 1;
        cropperInstance.scaleY(currentScaleY);
    });

    // 3. FITUR ROTATE (-90° Kiri & +90° Kanan)
    document.getElementById('btnRotateLeft').addEventListener('click', function() {
        if (cropperInstance) cropperInstance.rotate(-90);
    });
    document.getElementById('btnRotateRight').addEventListener('click', function() {
        if (cropperInstance) cropperInstance.rotate(90);
    });

    // 4. FITUR ZOOM IN & ZOOM OUT
    document.getElementById('btnZoomIn').addEventListener('click', function() {
        if (cropperInstance) cropperInstance.zoom(0.1);
    });
    document.getElementById('btnZoomOut').addEventListener('click', function() {
        if (cropperInstance) cropperInstance.zoom(-0.1);
    });

    // 5. FITUR RESET
    document.getElementById('btnResetCrop').addEventListener('click', function() {
        if (cropperInstance) {
            currentScaleX = 1;
            currentScaleY = 1;
            cropperInstance.reset();
        }
    });

    // 6. PILIHAN RASIO ASPEK
    document.querySelectorAll('.ratio-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.ratio-btn').forEach(b => b.classList.remove('active'));
            this.classList.add('active');
            const ratioVal = parseFloat(this.getAttribute('data-ratio'));
            activeRatio = isNaN(ratioVal) ? NaN : ratioVal;
            if (cropperInstance) {
                cropperInstance.setAspectRatio(activeRatio);
            }
        });
    });

    // 7. TERAPKAN HASIL CROP
    btnApplyCrop.addEventListener('click', function() {
        if (!cropperInstance) return;

        // Ambil canvas hasil crop dengan resolusi tinggi untuk banner (lebar 1920px)
        const canvas = cropperInstance.getCroppedCanvas({
            width: 1920,
            maxWidth: 2560,
            maxHeight: 1440,
            imageSmoothingEnabled: true,
            imageSmoothingQuality: 'high'
        });

        if (canvas) {
            try {
                const base64Image = canvas.toDataURL('image/jpeg', 0.90);
                hiddenCroppedData.value = base64Image;

                // Tampilkan hasil crop langsung pada Live Preview
                bannerPreviewImg.src = base64Image;
                cropStatusBadge.classList.remove('d-none');

                // Tutup modal
                bsCropModal.hide();
            } catch (err) {
                console.error('Canvas export error:', err);
                alert('Gambar berhasil disesuaikan di layar, silakan klik tombol Simpan Slide di bawah.');
                bsCropModal.hide();
            }
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
