<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Form Penghargaan';
$db = getDB();

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$p = ['judul' => '', 'instansi' => '', 'tahun' => date('Y'), 'file_path' => '', 'is_pdf' => 0];

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM penghargaan WHERE id = ?");
    $stmt->execute([$id]);
    $row = $stmt->fetch();
    if ($row) {
        $p = $row;
    } else {
        $id = 0; // Not found
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul = trim($_POST['judul'] ?? '');
    $instansi = trim($_POST['instansi'] ?? '');
    $tahun = trim($_POST['tahun'] ?? date('Y'));
    $file_path = $p['file_path'];
    $is_pdf = $p['is_pdf'];
    
    // File Upload handling (Support Cropped Base64 & Direct File Upload)
    $cropped_file = trim($_POST['cropped_file_data'] ?? '');
    if (!empty($cropped_file) && preg_match('/^data:image\/(\w+);base64,/', $cropped_file)) {
        $raw = base64_decode(substr($cropped_file, strpos($cropped_file, ',') + 1));
        if ($raw !== false) {
            $upload_dir = __DIR__ . '/../uploads/penghargaan/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0777, true);
            $new_name = 'award_' . time() . '_' . uniqid() . '.webp';
            if (file_put_contents($upload_dir . $new_name, $raw)) {
                if ($file_path && file_exists($upload_dir . $file_path)) {
                    @unlink($upload_dir . $file_path);
                    $old_thumb = $upload_dir . 'thumb_' . pathinfo($file_path, PATHINFO_FILENAME) . '.jpg';
                    if (file_exists($old_thumb)) @unlink($old_thumb);
                }
                $file_path = $new_name;
                $is_pdf = 0;
            }
        }
    } elseif (isset($_FILES['file_penghargaan']) && $_FILES['file_penghargaan']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/penghargaan/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        
        $tmp_name = $_FILES['file_penghargaan']['tmp_name'];
        $name = $_FILES['file_penghargaan']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        
        // Cek ekstensi yang diizinkan (Gambar atau PDF)
        $allowed = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (in_array($ext, $allowed)) {
            $is_pdf = ($ext === 'pdf') ? 1 : 0;
            $upload_success = false;
            $new_name = '';

            if ($is_pdf) {
                $new_name = 'award_' . uniqid() . '.pdf';
                $upload_success = move_uploaded_file($tmp_name, $upload_dir . $new_name);
            } else {
                $saved_name = convertAndSaveWebP($tmp_name, $upload_dir, 'award_');
                if ($saved_name) {
                    $new_name = $saved_name;
                    $upload_success = true;
                }
            }

            if ($upload_success) {
                // Delete old file if exists
                if ($file_path && file_exists($upload_dir . $file_path)) {
                    @unlink($upload_dir . $file_path);
                    $old_thumb = $upload_dir . 'thumb_' . pathinfo($file_path, PATHINFO_FILENAME) . '.jpg';
                    if (file_exists($old_thumb)) {
                        @unlink($old_thumb);
                    }
                }
                $file_path = $new_name;
                
                // Auto generate thumbnail image if PDF (Server-side Python & Client-side Canvas fallback)
                if ($is_pdf) {
                    $gen = getOrGeneratePdfPreview($new_name, 'penghargaan');
                    if (empty($gen) && !empty($_POST['pdf_cover_base64'])) {
                        $raw_b64 = $_POST['pdf_cover_base64'];
                        if (str_contains($raw_b64, 'base64,')) {
                            $raw_b64 = explode('base64,', $raw_b64)[1];
                        }
                        $img_data = base64_decode($raw_b64);
                        if ($img_data) {
                            $thumbName = 'thumb_' . pathinfo($new_name, PATHINFO_FILENAME) . '.jpg';
                            @file_put_contents($upload_dir . $thumbName, $img_data);
                        }
                    }
                }
            }
        }
    }
    
    if (empty($judul)) {
        $_SESSION['flash_error'] = 'Judul Penghargaan wajib diisi.';
    } elseif (empty($file_path)) {
        $_SESSION['flash_error'] = 'File Penghargaan (Sertifikat/Gambar) wajib diupload.';
    } else {
        if ($id > 0) {
            $db->prepare("UPDATE penghargaan SET judul=?, instansi=?, tahun=?, file_path=?, is_pdf=? WHERE id=?")
               ->execute([$judul, $instansi, $tahun, $file_path, $is_pdf, $id]);
            $_SESSION['flash'] = 'Data penghargaan berhasil diupdate.';
        } else {
            $db->prepare("INSERT INTO penghargaan (judul, instansi, tahun, file_path, is_pdf) VALUES (?, ?, ?, ?, ?)")
               ->execute([$judul, $instansi, $tahun, $file_path, $is_pdf]);
            $_SESSION['flash'] = 'Data penghargaan berhasil ditambahkan.';
        }
        redirect(SITE_URL . '/admin/penghargaan-list.php');
    }
}

$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash_error']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="admin-table-wrap">
    <div class="admin-table-topbar">
        <div class="admin-table-title"><?= $id > 0 ? 'Edit' : 'Tambah' ?> Penghargaan</div>
        <a href="penghargaan-list.php" class="btn-outline">Kembali</a>
    </div>

    <?php if ($flash_error): ?>
    <div class="alert-lpm alert-danger mb-4">
        <?= e($flash_error) ?>
    </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data" class="lpm-form" style="padding:1.5rem;">
        
        <div class="mb-4">
            <label class="form-label fw-bold">Judul Penghargaan <span class="text-danger">*</span></label>
            <input type="text" name="judul" class="form-control" value="<?= e($p['judul']) ?>" required placeholder="Contoh: Akreditasi Unggul BAN-PT">
        </div>
        
        <div class="row mb-4">
            <div class="col-md-8">
                <label class="form-label fw-bold">Instansi / Lembaga Pemberi</label>
                <input type="text" name="instansi" class="form-control" value="<?= e($p['instansi']) ?>" placeholder="Contoh: Badan Akreditasi Nasional Perguruan Tinggi">
            </div>
            <div class="col-md-4 mt-4 mt-md-0">
                <label class="form-label fw-bold">Tahun <span class="text-danger">*</span></label>
                <input type="number" name="tahun" class="form-control" value="<?= e($p['tahun']) ?>" required min="2000" max="2099">
            </div>
        </div>

        <div class="mb-4">
            <label class="form-label fw-bold">Upload File (Sertifikat / Bukti) <?= $id > 0 ? '' : '<span class="text-danger">*</span>' ?></label>
            <p class="text-muted small mb-2">Format yang diizinkan: JPG, PNG, WebP, atau PDF. Maksimal 5MB. File PDF akan secara otomatis dibuatkan pratinjau foto sertifikatnya, dan berkas gambar dapat disesuaikan framingnya menggunakan editor crop.</p>
            <div class="d-flex gap-2 align-items-center">
                <input type="file" name="file_penghargaan" id="inputPenghargaanFile" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" <?= $id > 0 ? '' : 'required' ?>>
                <button type="button" class="btn btn-dark btn-sm d-none align-items-center gap-1" id="btnEditImagePenghargaan" style="white-space:nowrap;padding:0.6rem 0.9rem;border-radius:8px;">
                    <i class="bi bi-crop text-warning"></i> Edit / Crop
                </button>
            </div>
            <input type="hidden" name="pdf_cover_base64" id="pdfCoverBase64">
            <input type="hidden" name="cropped_file_data" id="croppedFileDataPenghargaan">
            
            <!-- Live Preview Container for newly selected file -->
            <div id="livePreviewContainer" class="mt-3 p-3 bg-light rounded d-none" style="border: 2px dashed var(--purple);">
                <p class="mb-2 fw-bold text-navy"><i class="bi bi-eye me-1"></i> Pratinjau Sampul / Berkas Terpilih:</p>
                <div id="livePreviewContent"></div>
            </div>

            <?php if ($p['file_path']): ?>
            <div class="mt-3 p-3 bg-light rounded" style="border: 1px dashed #ccc;">
                <p class="mb-2 fw-bold text-navy">Sampul &amp; Berkas Tersimpan:</p>
                <?php
                $p_preview = getOrGeneratePdfPreview($p['file_path'], 'penghargaan');
                ?>
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <?php if ($p_preview): ?>
                        <div style="position:relative;">
                            <a href="<?= SITE_URL ?>/uploads/penghargaan/<?= e($p['file_path']) ?>" target="_blank" title="Klik untuk membuka dokumen asli">
                                <img id="existingPenghargaanImg" src="<?= SITE_URL ?>/uploads/penghargaan/<?= e($p_preview) ?>" alt="Pratinjau Sertifikat" class="img-thumbnail shadow-sm" style="max-height: 180px; max-width: 260px; object-fit: contain; background: #fff;">
                            </a>
                            <?php if ($p['is_pdf']): ?>
                                <span class="badge bg-danger" style="position:absolute;bottom:6px;right:6px;font-size:0.65rem;box-shadow:0 2px 4px rgba(0,0,0,0.2);">Sampul Hal. 1</span>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    <div>
                        <?php if ($p['is_pdf']): ?>
                            <span class="badge bg-danger mb-2"><i class="bi bi-file-earmark-pdf me-1"></i> Dokumen PDF (Sampul Halaman 1 Aktif)</span>
                            <div class="small text-muted mb-2">Nama berkas: <strong><?= e($p['file_path']) ?></strong></div>
                            <a href="<?= SITE_URL ?>/uploads/penghargaan/<?= e($p['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-danger">
                                <i class="bi bi-box-arrow-up-right me-1"></i> Buka Dokumen PDF Asli
                            </a>
                        <?php else: ?>
                            <span class="badge bg-success mb-2"><i class="bi bi-file-earmark-image me-1"></i> Berkas Gambar</span>
                            <div class="small text-muted mb-2">Nama berkas: <strong><?= e($p['file_path']) ?></strong></div>
                            <div class="d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-outline-primary py-1" onclick="editExistingPenghargaanImg('<?= SITE_URL ?>/uploads/penghargaan/<?= e($p['file_path']) ?>')">
                                    <i class="bi bi-crop text-warning me-1"></i> Edit / Crop Gambar
                                </button>
                                <a href="<?= SITE_URL ?>/uploads/penghargaan/<?= e($p['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-secondary py-1">
                                    <i class="bi bi-zoom-in me-1"></i> Lihat Gambar Penuh
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="border-top pt-4 text-end">
            <button type="submit" class="btn-save px-4 py-2" style="font-size: 1rem;">
                <i class="bi bi-check-circle me-2"></i> Simpan Data
            </button>
        </div>
    </form>
</div>

<!-- Modal Cropper Universal Penghargaan -->
<div class="modal fade" id="modalCropPenghargaan" tabindex="-1" aria-labelledby="modalCropPenghargaanLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
            <div class="modal-header text-white" style="background:linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);padding:1.25rem 1.75rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-crop" style="font-size:1.4rem;color:#FFD54F;"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="modalCropPenghargaanLabel">Editor Gambar Penghargaan: Crop, Rotate, Flip &amp; Zoom</h5>
                        <small style="color:rgba(255,255,255,0.85);">Sesuaikan framing foto atau sertifikat penghargaan.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" style="background:#F1F5F9;">
                <div class="crop-modal-container mb-3 shadow-inner" style="max-height:480px;background:#0F172A;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:10px;">
                    <img id="cropperSourceImgPenghargaan" src="" alt="Source" style="max-width:100%;max-height:460px;display:block;">
                </div>
                <div class="card p-3 border shadow-sm rounded-3 bg-white">
                    <div class="row g-2 align-items-center">
                        <div class="col-lg-4 col-md-6 col-12 d-flex gap-2">
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipHPenghargaan">
                                <i class="bi bi-symmetry-vertical text-warning"></i> Flip H (⇄)
                            </button>
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipVPenghargaan">
                                <i class="bi bi-symmetry-horizontal text-warning"></i> Flip V (⇅)
                            </button>
                        </div>
                        <div class="col-lg-3 col-md-6 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateLeftPenghargaan">
                                <i class="bi bi-arrow-counterclockwise"></i> ↺ 90°
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateRightPenghargaan">
                                <i class="bi bi-arrow-clockwise"></i> ↻ 90°
                            </button>
                        </div>
                        <div class="col-lg-5 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomInPenghargaan">
                                <i class="bi bi-zoom-in"></i> +
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomOutPenghargaan">
                                <i class="bi bi-zoom-out"></i> -
                            </button>
                            <button type="button" class="btn btn-outline-danger flex-fill fw-bold py-2" id="btnResetCropPenghargaan">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top flex-wrap">
                        <span class="text-muted small fw-bold me-1 text-uppercase">Rasio:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active ratio-btn-penghargaan fw-semibold" data-ratio="1.333333">4:3 Galeri</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-penghargaan fw-semibold" data-ratio="1.777778">16:9 Banner</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-penghargaan fw-semibold" data-ratio="1">1:1 Persegi</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-penghargaan fw-semibold" data-ratio="NaN">Bebas</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between" style="background:#ffffff;border-top:1px solid #E2E8F0;padding:1rem 1.75rem;">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius:8px;">Batal</button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-bold d-inline-flex align-items-center gap-2" id="btnApplyCropPenghargaan" style="background:var(--navy);border-color:var(--navy);border-radius:8px;">
                    <i class="bi bi-check-lg fs-5"></i> Terapkan Hasil Penyesuaian
                </button>
            </div>
        </div>
    </div>
</div>

<!-- PDF.js library for instant client-side rendering of PDF pages -->
<script src="<?= SITE_URL ?>/assets/js/pdf.min.js"></script>
<script>
if (typeof pdfjsLib !== 'undefined') {
    pdfjsLib.GlobalWorkerOptions.workerSrc = '<?= SITE_URL ?>/assets/js/pdf.worker.min.js';
}

let cropperPenghargaan = null;
let scaleXPenghargaan = 1;
let scaleYPenghargaan = 1;
let currentRatioPenghargaan = 4 / 3;
let isExistingPenghargaanEdit = false;

const modalCropPenghargaanEl  = document.getElementById('modalCropPenghargaan');
const bsModalPenghargaan      = new bootstrap.Modal(modalCropPenghargaanEl);
const sourceImgPenghargaan    = document.getElementById('cropperSourceImgPenghargaan');
const btnEditPenghargaan      = document.getElementById('btnEditImagePenghargaan');
const hiddenCropPenghargaan   = document.getElementById('croppedFileDataPenghargaan');
const applyBtnPenghargaan     = document.getElementById('btnApplyCropPenghargaan');

function initCropperPenghargaan() {
    if (cropperPenghargaan) cropperPenghargaan.destroy();
    scaleXPenghargaan = 1;
    scaleYPenghargaan = 1;
    cropperPenghargaan = new Cropper(sourceImgPenghargaan, {
        aspectRatio: currentRatioPenghargaan,
        viewMode: 1,
        autoCropArea: 0.95,
        responsive: true,
        guides: true,
        checkCrossOrigin: false
    });
}

modalCropPenghargaanEl.addEventListener('shown.bs.modal', function() { initCropperPenghargaan(); });
modalCropPenghargaanEl.addEventListener('hidden.bs.modal', function() {
    if (cropperPenghargaan) {
        cropperPenghargaan.destroy();
        cropperPenghargaan = null;
    }
});

// Controls
document.getElementById('btnRotateLeftPenghargaan')?.addEventListener('click', function() { if (cropperPenghargaan) cropperPenghargaan.rotate(-90); });
document.getElementById('btnRotateRightPenghargaan')?.addEventListener('click', function() { if (cropperPenghargaan) cropperPenghargaan.rotate(90); });
document.getElementById('btnFlipHPenghargaan')?.addEventListener('click', function() { if (cropperPenghargaan) { scaleXPenghargaan = -scaleXPenghargaan; cropperPenghargaan.scaleX(scaleXPenghargaan); } });
document.getElementById('btnFlipVPenghargaan')?.addEventListener('click', function() { if (cropperPenghargaan) { scaleYPenghargaan = -scaleYPenghargaan; cropperPenghargaan.scaleY(scaleYPenghargaan); } });
document.getElementById('btnZoomInPenghargaan')?.addEventListener('click', function() { if (cropperPenghargaan) cropperPenghargaan.zoom(0.1); });
document.getElementById('btnZoomOutPenghargaan')?.addEventListener('click', function() { if (cropperPenghargaan) cropperPenghargaan.zoom(-0.1); });
document.getElementById('btnResetCropPenghargaan')?.addEventListener('click', function() { if (cropperPenghargaan) { cropperPenghargaan.reset(); scaleXPenghargaan = 1; scaleYPenghargaan = 1; } });

document.querySelectorAll('.ratio-btn-penghargaan').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.ratio-btn-penghargaan').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');
        const val = parseFloat(this.getAttribute('data-ratio'));
        currentRatioPenghargaan = isNaN(val) ? NaN : val;
        if (cropperPenghargaan) cropperPenghargaan.setAspectRatio(currentRatioPenghargaan);
    });
});

document.getElementById('inputPenghargaanFile').addEventListener('change', function(e) {
    var file = this.files[0];
    if (!file) return;
    
    var container = document.getElementById('livePreviewContainer');
    var content = document.getElementById('livePreviewContent');
    var hiddenBase64 = document.getElementById('pdfCoverBase64');
    content.innerHTML = '';
    container.classList.remove('d-none');
    hiddenBase64.value = '';
    hiddenCropPenghargaan.value = '';
    btnEditPenghargaan.classList.add('d-none');
    
    if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
        content.innerHTML = '<div class="text-muted small py-2"><div class="spinner-border spinner-border-sm text-purple me-2"></div>Membaca file PDF dan mengekstrak sampul halaman pertama...</div>';
        var fileReader = new FileReader();
        fileReader.onload = function() {
            var typedarray = new Uint8Array(this.result);
            if (typeof pdfjsLib === 'undefined') {
                content.innerHTML = '<div class="alert alert-warning small py-2 mb-0"><i class="bi bi-info-circle me-1"></i> Berkas PDF ' + file.name + ' siap diunggah. Sampul halaman pertama akan diproses otomatis oleh server.</div>';
                return;
            }
            pdfjsLib.getDocument(typedarray).promise.then(function(pdf) {
                pdf.getPage(1).then(function(page) {
                    var scale = 1.5;
                    var viewport = page.getViewport({scale: scale});
                    var canvas = document.createElement('canvas');
                    canvas.className = 'img-thumbnail shadow-sm';
                    canvas.style.maxHeight = '200px';
                    canvas.style.maxWidth = '300px';
                    canvas.style.objectFit = 'contain';
                    canvas.style.background = '#ffffff';
                    var context = canvas.getContext('2d');
                    canvas.height = viewport.height;
                    canvas.width = viewport.width;
                    page.render({canvasContext: context, viewport: viewport}).promise.then(function() {
                        content.innerHTML = '';
                        var wrap = document.createElement('div');
                        wrap.className = 'd-flex align-items-center gap-3 flex-wrap';
                        wrap.appendChild(canvas);
                        
                        try {
                            hiddenBase64.value = canvas.toDataURL('image/jpeg', 0.92);
                        } catch (err) {}

                        var info = document.createElement('div');
                        info.innerHTML = '<span class="badge bg-danger mb-1 me-1"><i class="bi bi-file-earmark-pdf"></i> Dokumen PDF</span>' +
                                         '<span class="badge bg-success mb-1"><i class="bi bi-check2-circle"></i> Sampul Hal. 1 Terbaca</span>' +
                                         '<div class="small text-navy fw-bold mt-1">' + file.name + '</div>' +
                                         '<div class="small text-muted">Ukuran berkas: ' + (file.size/1024).toFixed(1) + ' KB</div>' +
                                         '<div class="text-success small fw-semibold mt-1"><i class="bi bi-image me-1"></i> Halaman pertama ini otomatis dijadikan gambar sampul penghargaan.</div>';
                        wrap.appendChild(info);
                        content.appendChild(wrap);
                    });
                });
            }).catch(function(err) {
                content.innerHTML = '<div class="alert alert-warning small py-2 mb-0"><i class="bi bi-info-circle me-1"></i> Berkas PDF ' + file.name + ' siap diunggah. Sampul halaman pertama akan diproses otomatis oleh sistem di server.</div>';
            });
        };
        fileReader.readAsArrayBuffer(file);
    } else if (file.type.startsWith('image/')) {
        var reader = new FileReader();
        reader.onload = function(evt) {
            isExistingPenghargaanEdit = false;
            sourceImgPenghargaan.src = evt.target.result;
            btnEditPenghargaan.classList.remove('d-none');
            btnEditPenghargaan.classList.add('d-inline-flex');
            
            content.innerHTML = '<div class="d-flex align-items-center gap-3 flex-wrap"><img id="livePenghargaanImgPreview" src="' + evt.target.result + '" class="img-thumbnail shadow-sm" style="max-height:200px;max-width:300px;object-fit:contain;"><div class="small text-muted"><span class="badge bg-success mb-1"><i class="bi bi-image me-1"></i>Gambar Terpilih</span><div class="fw-bold text-navy">' + file.name + '</div><div>(' + (file.size/1024).toFixed(1) + ' KB)</div><div class="mt-2"><button type="button" class="btn btn-sm btn-dark" onclick="bsModalPenghargaan.show()"><i class="bi bi-crop text-warning me-1"></i>Buka Editor Crop</button></div></div></div>';
            
            bsModalPenghargaan.show();
        };
        reader.readAsDataURL(file);
    }
});

btnEditPenghargaan?.addEventListener('click', function() {
    if (sourceImgPenghargaan.src) {
        bsModalPenghargaan.show();
    }
});

window.editExistingPenghargaanImg = function(imgUrl) {
    isExistingPenghargaanEdit = true;
    sourceImgPenghargaan.src = imgUrl;
    bsModalPenghargaan.show();
};

applyBtnPenghargaan?.addEventListener('click', function() {
    if (!cropperPenghargaan) return;
    const canvas = cropperPenghargaan.getCroppedCanvas({
        maxWidth: 1920,
        maxHeight: 1200,
        imageSmoothingQuality: 'high'
    });
    if (canvas) {
        const dataUrl = canvas.toDataURL('image/webp', 0.88);
        hiddenCropPenghargaan.value = dataUrl;

        var liveImg = document.getElementById('livePenghargaanImgPreview');
        if (liveImg) liveImg.src = dataUrl;

        var existImg = document.getElementById('existingPenghargaanImg');
        if (existImg && isExistingPenghargaanEdit) existImg.src = dataUrl;

        btnEditPenghargaan.classList.remove('d-none');
        btnEditPenghargaan.classList.add('d-inline-flex');
        bsModalPenghargaan.hide();
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
