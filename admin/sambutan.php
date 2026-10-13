<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Sambutan Kepala LPM';
$db = getDB();

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['sambutan_nama'] ?? '');
    $jabatan  = trim($_POST['sambutan_jabatan'] ?? '');
    $instansi = trim($_POST['sambutan_instansi'] ?? '');
    $teks     = trim($_POST['sambutan_teks'] ?? '');

    if (!$nama || !$teks) {
        $error = 'Nama pimpinan dan isi teks sambutan wajib diisi.';
    } else {
        setPengaturan('sambutan_nama', $nama);
        setPengaturan('sambutan_jabatan', $jabatan);
        setPengaturan('sambutan_instansi', $instansi);
        setPengaturan('sambutan_teks', $teks);

        $dir = __DIR__ . '/../uploads/profil/';
        if (!is_dir($dir)) @mkdir($dir, 0755, true);

        // Upload foto (Support Cropped Base64 & Direct File Upload)
        $cropped_foto = trim($_POST['cropped_foto_data'] ?? '');
        if (!empty($cropped_foto) && preg_match('/^data:image\/(\w+);base64,/', $cropped_foto)) {
            $raw = base64_decode(substr($cropped_foto, strpos($cropped_foto, ',') + 1));
            if ($raw !== false) {
                $c_name = 'kepala_' . time() . '_' . uniqid() . '.webp';
                if (file_put_contents($dir . $c_name, $raw)) {
                    $old = getPengaturan('sambutan_foto');
                    if ($old && file_exists($dir . $old)) @unlink($dir . $old);
                    setPengaturan('sambutan_foto', $c_name);
                } else {
                    $error = 'Gagal menyimpan foto hasil crop ke server.';
                }
            }
        } elseif (!empty($_FILES['sambutan_foto']['name'])) {
            $allowed = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($_FILES['sambutan_foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['sambutan_foto']['size'] > 5 * 1024 * 1024) {
                $error = 'Ukuran foto maksimal 5MB.';
            } else {
                $saved_foto = convertAndSaveWebP($_FILES['sambutan_foto']['tmp_name'], $dir, 'kepala_');
                if ($saved_foto) {
                    $old = getPengaturan('sambutan_foto');
                    if ($old && file_exists($dir . $old)) @unlink($dir . $old);
                    setPengaturan('sambutan_foto', $saved_foto);
                } else {
                    $error = 'Gagal mengonversi foto kepala LPM ke format WebP.';
                }
            }
        }

        if (!$error) {
            $_SESSION['flash'] = 'Sambutan Kepala LPM berhasil diperbarui.';
            redirect(SITE_URL . '/admin/sambutan.php');
        }
    }
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$nama     = getPengaturan('sambutan_nama', 'Stefani Lily Indarto, SE., MM., Ak., CA., CPA.');
$jabatan  = getPengaturan('sambutan_jabatan', 'Kepala Lembaga Penjaminan Mutu');
$instansi = getPengaturan('sambutan_instansi', 'Universitas Katolik Soegijapranata');
$foto     = getPengaturan('sambutan_foto', '');
$teks     = getPengaturan('sambutan_teks', '');

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <span style="color:var(--navy);">Sambutan Kepala LPM</span>
        </div>

        <?php if ($flash): ?>
        <div class="alert-lpm alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <?= e($flash) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert-lpm alert-danger mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div class="admin-table-title">Edit Sambutan Kepala Lembaga Penjaminan Mutu</div>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Nama Lengkap &amp; Gelar <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="sambutan_nama" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($nama) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Jabatan
                            </label>
                            <input type="text" name="sambutan_jabatan" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($jabatan) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Nama Institusi / Universitas
                            </label>
                            <input type="text" name="sambutan_instansi" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($instansi) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Foto Kepala LPM
                            </label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="file" id="sambutanFotoInput" name="sambutan_foto" class="form-control" accept="image/*">
                                <button type="button" class="btn btn-dark btn-sm d-none align-items-center gap-1" id="btnEditFotoSambutan" style="white-space:nowrap;padding:0.6rem 0.9rem;border-radius:8px;">
                                    <i class="bi bi-crop text-warning"></i> Edit / Crop
                                </button>
                            </div>
                            <input type="hidden" name="cropped_foto_data" id="croppedFotoData">
                            <div class="form-text mt-1">
                                <span class="badge bg-light text-dark border me-1"><i class="bi bi-hdd-fill text-warning me-1"></i>Batas Ukuran: Maksimal 5 MB</span> Format: JPG, PNG, atau WebP.
                            </div>
                            <!-- Live Preview Foto Baru -->
                            <div id="livePreviewSambutan" class="mt-2 d-none">
                                <div class="d-flex align-items-center gap-2 p-2 bg-light rounded border">
                                    <img id="livePreviewImgSambutan" src="" alt="Preview Foto" style="width:50px;height:50px;border-radius:50%;object-fit:cover;border:2px solid var(--purple);">
                                    <div>
                                        <div class="small fw-bold text-success"><i class="bi bi-check-circle-fill me-1"></i>Foto siap disimpan</div>
                                        <small class="text-muted">Klik "Edit / Crop" untuk mengatur framing ulang.</small>
                                    </div>
                                </div>
                            </div>
                            <?php if ($foto && file_exists(__DIR__ . '/../uploads/profil/' . $foto)): ?>
                            <div class="mt-2" id="existingFotoWrapper" style="font-size:0.8rem;color:var(--text-muted);display:flex;align-items:center;gap:10px;">
                                <span>Foto saat ini:</span>
                                <img id="existingFotoImg" src="<?= SITE_URL ?>/uploads/profil/<?= e($foto) ?>" alt="" style="width:45px;height:45px;border-radius:50%;object-fit:cover;border:2px solid var(--purple);">
                                <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.75rem;height:26px;" onclick="editExistingFotoSambutan('<?= SITE_URL ?>/uploads/profil/<?= e($foto) ?>')">
                                    <i class="bi bi-crop text-warning me-1"></i>Edit
                                </button>
                            </div>
                            <?php else: ?>
                            <small class="text-muted d-block mt-1">Jika kosong, akan menggunakan ikon avatar placeholder elegan.</small>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Isi Sambutan / Pesan Pimpinan <span style="color:#C62828;">*</span>
                            </label>
                            <textarea name="sambutan_teks" class="form-control" rows="8" style="border:1.5px solid var(--border);padding:0.85rem 1rem;line-height:1.75;" placeholder="Tuliskan kata sambutan di sini..." required><?= e($teks) ?></textarea>
                            <small class="text-muted d-block mt-1">Gunakan pemisah baris/enter untuk membagi paragraf sambutan.</small>
                        </div>

                        <div class="col-12" style="padding-top:1rem;border-top:1px solid var(--border);">
                            <div style="display:flex;justify-content:flex-end;">
                                <button type="submit" class="btn-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Simpan Perubahan Sambutan
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cropper Universal Sambutan -->
<div class="modal fade" id="modalCropSambutan" tabindex="-1" aria-labelledby="modalCropSambutanLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
            <div class="modal-header text-white" style="background:linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);padding:1.25rem 1.75rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-crop" style="font-size:1.4rem;color:#FFD54F;"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="modalCropSambutanLabel">Editor Foto Pimpinan: Crop, Rotate, Flip &amp; Zoom</h5>
                        <small style="color:rgba(255,255,255,0.85);">Sesuaikan framing foto Kepala Lembaga Penjaminan Mutu.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" style="background:#F1F5F9;">
                <div class="crop-modal-container mb-3 shadow-inner" style="max-height:480px;background:#0F172A;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:10px;">
                    <img id="cropperSourceImgSambutan" src="" alt="Source" style="max-width:100%;max-height:460px;display:block;">
                </div>
                <div class="card p-3 border shadow-sm rounded-3 bg-white">
                    <div class="row g-2 align-items-center">
                        <div class="col-lg-4 col-md-6 col-12 d-flex gap-2">
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipHSambutan">
                                <i class="bi bi-symmetry-vertical text-warning"></i> Flip H (⇄)
                            </button>
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipVSambutan">
                                <i class="bi bi-symmetry-horizontal text-warning"></i> Flip V (⇅)
                            </button>
                        </div>
                        <div class="col-lg-3 col-md-6 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateLeftSambutan">
                                <i class="bi bi-arrow-counterclockwise"></i> ↺ 90°
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateRightSambutan">
                                <i class="bi bi-arrow-clockwise"></i> ↻ 90°
                            </button>
                        </div>
                        <div class="col-lg-5 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomInSambutan">
                                <i class="bi bi-zoom-in"></i> +
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomOutSambutan">
                                <i class="bi bi-zoom-out"></i> -
                            </button>
                            <button type="button" class="btn btn-outline-danger flex-fill fw-bold py-2" id="btnResetCropSambutan">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top flex-wrap">
                        <span class="text-muted small fw-bold me-1 text-uppercase">Rasio:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active ratio-btn-sambutan fw-semibold" data-ratio="1">1:1 Persegi (Profil)</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-sambutan fw-semibold" data-ratio="1.333333">4:3 Potret</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-sambutan fw-semibold" data-ratio="1.777778">16:9 Landscape</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-sambutan fw-semibold" data-ratio="NaN">Bebas</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between" style="background:#ffffff;border-top:1px solid #E2E8F0;padding:1rem 1.75rem;">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius:8px;">Batal</button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-bold d-inline-flex align-items-center gap-2" id="btnApplyCropSambutan" style="background:var(--navy);border-color:var(--navy);border-radius:8px;">
                    <i class="bi bi-check-lg fs-5"></i> Terapkan Hasil Penyesuaian
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let cropperSambutan = null;
let scaleXSambutan = 1;
let scaleYSambutan = 1;
let currentRatioSambutan = 1; // 1:1 default for profile
let isExistingEdit = false;

const modalCropSambutanEl  = document.getElementById('modalCropSambutan');
const bsModalSambutan      = new bootstrap.Modal(modalCropSambutanEl);
const sourceImgSambutan    = document.getElementById('cropperSourceImgSambutan');
const fileInputSambutan    = document.getElementById('sambutanFotoInput');
const btnEditSambutan      = document.getElementById('btnEditFotoSambutan');
const livePreviewWrap      = document.getElementById('livePreviewSambutan');
const livePreviewImg       = document.getElementById('livePreviewImgSambutan');
const hiddenCropSambutan   = document.getElementById('croppedFotoData');
const applyBtnSambutan     = document.getElementById('btnApplyCropSambutan');

function initCropperSambutan() {
    if (cropperSambutan) cropperSambutan.destroy();
    scaleXSambutan = 1;
    scaleYSambutan = 1;
    cropperSambutan = new Cropper(sourceImgSambutan, {
        aspectRatio: currentRatioSambutan,
        viewMode: 1,
        autoCropArea: 0.95,
        responsive: true,
        guides: true,
        checkCrossOrigin: false
    });
}

modalCropSambutanEl.addEventListener('shown.bs.modal', function() { initCropperSambutan(); });
modalCropSambutanEl.addEventListener('hidden.bs.modal', function() {
    if (cropperSambutan) {
        cropperSambutan.destroy();
        cropperSambutan = null;
    }
});

// Controls
document.getElementById('btnRotateLeftSambutan')?.addEventListener('click', function() { if (cropperSambutan) cropperSambutan.rotate(-90); });
document.getElementById('btnRotateRightSambutan')?.addEventListener('click', function() { if (cropperSambutan) cropperSambutan.rotate(90); });
document.getElementById('btnFlipHSambutan')?.addEventListener('click', function() { if (cropperSambutan) { scaleXSambutan = -scaleXSambutan; cropperSambutan.scaleX(scaleXSambutan); } });
document.getElementById('btnFlipVSambutan')?.addEventListener('click', function() { if (cropperSambutan) { scaleYSambutan = -scaleYSambutan; cropperSambutan.scaleY(scaleYSambutan); } });
document.getElementById('btnZoomInSambutan')?.addEventListener('click', function() { if (cropperSambutan) cropperSambutan.zoom(0.1); });
document.getElementById('btnZoomOutSambutan')?.addEventListener('click', function() { if (cropperSambutan) cropperSambutan.zoom(-0.1); });
document.getElementById('btnResetCropSambutan')?.addEventListener('click', function() { if (cropperSambutan) { cropperSambutan.reset(); scaleXSambutan = 1; scaleYSambutan = 1; } });

document.querySelectorAll('.ratio-btn-sambutan').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.ratio-btn-sambutan').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');
        const val = parseFloat(this.getAttribute('data-ratio'));
        currentRatioSambutan = isNaN(val) ? NaN : val;
        if (cropperSambutan) cropperSambutan.setAspectRatio(currentRatioSambutan);
    });
});

fileInputSambutan?.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const file = this.files[0];
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                isExistingEdit = false;
                sourceImgSambutan.src = e.target.result;
                livePreviewImg.src = e.target.result;
                livePreviewWrap.classList.remove('d-none');
                btnEditSambutan.classList.remove('d-none');
                btnEditSambutan.classList.add('d-inline-flex');
                hiddenCropSambutan.value = '';
                bsModalSambutan.show();
            };
            reader.readAsDataURL(file);
        }
    }
});

btnEditSambutan?.addEventListener('click', function() {
    if (sourceImgSambutan.src) {
        bsModalSambutan.show();
    }
});

window.editExistingFotoSambutan = function(imgUrl) {
    isExistingEdit = true;
    sourceImgSambutan.src = imgUrl;
    bsModalSambutan.show();
};

applyBtnSambutan?.addEventListener('click', function() {
    if (!cropperSambutan) return;
    const canvas = cropperSambutan.getCroppedCanvas({
        maxWidth: 1200,
        maxHeight: 1200,
        imageSmoothingQuality: 'high'
    });
    if (canvas) {
        const dataUrl = canvas.toDataURL('image/webp', 0.88);
        hiddenCropSambutan.value = dataUrl;
        livePreviewImg.src = dataUrl;
        livePreviewWrap.classList.remove('d-none');
        btnEditSambutan.classList.remove('d-none');
        btnEditSambutan.classList.add('d-inline-flex');

        const existingImg = document.getElementById('existingFotoImg');
        if (existingImg && isExistingEdit) {
            existingImg.src = dataUrl;
        }

        bsModalSambutan.hide();
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
