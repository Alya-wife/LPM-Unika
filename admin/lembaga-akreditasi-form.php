<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$is_edit = false;
$d = [
    'kode' => '',
    'nama' => '',
    'lingkup' => '',
    'warna' => '#0A192F',
    'bg' => '#F1F3F7',
    'logo' => '',
    'link_website' => '',
    'urutan' => 1
];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM lembaga_akreditasi WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $row = $stmt->fetch();
    if ($row) {
        $is_edit = true;
        $d = $row;
    }
}

$admin_page_title = $is_edit ? 'Edit Lembaga Akreditasi' : 'Tambah Lembaga Akreditasi';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode         = trim($_POST['kode'] ?? '');
    $nama         = trim($_POST['nama'] ?? '');
    $lingkup      = trim($_POST['lingkup'] ?? '');
    $warna        = trim($_POST['warna'] ?? '#0A192F');
    $bg           = trim($_POST['bg'] ?? '#F1F3F7');
    $link_website = trim($_POST['link_website'] ?? '');
    $urutan       = (int)($_POST['urutan'] ?? 1);
    $id           = (int)($_POST['id'] ?? 0);

    if (!$kode || !$nama) {
        $error = 'Kode lembaga dan Nama lembaga wajib diisi.';
    } else {
        $upload_dir = __DIR__ . '/../uploads/akreditasi/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $logo = $d['logo'] ?? null;

        // Handle Remove Logo
        if (!empty($_POST['hapus_logo']) && empty($_FILES['logo']['name'])) {
            if ($logo && file_exists($upload_dir . $logo)) @unlink($upload_dir . $logo);
            $logo = null;
        }

        // Handle Logo Upload (Support Cropped Base64 & Direct File Upload)
        $cropped_logo = trim($_POST['cropped_logo_data'] ?? '');
        if (!empty($cropped_logo) && preg_match('/^data:image\/(\w+);base64,/', $cropped_logo)) {
            $raw = base64_decode(substr($cropped_logo, strpos($cropped_logo, ',') + 1));
            if ($raw !== false) {
                $clean_kode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $kode)) ?: 'lembaga';
                $new_logo = 'logo_' . $clean_kode . '_' . time() . '.webp';
                if (file_put_contents($upload_dir . $new_logo, $raw)) {
                    if ($logo && file_exists($upload_dir . $logo)) @unlink($upload_dir . $logo);
                    $logo = $new_logo;
                } else {
                    $error = 'Gagal menyimpan file logo hasil crop.';
                }
            }
        } elseif (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
                $clean_kode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $kode)) ?: 'lembaga';
                if ($ext === 'svg') {
                    $new_logo = 'logo_' . $clean_kode . '_' . time() . '.svg';
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $new_logo)) {
                        if ($logo && file_exists($upload_dir . $logo)) @unlink($upload_dir . $logo);
                        $logo = $new_logo;
                    }
                } else {
                    $saved_logo = convertAndSaveWebP($_FILES['logo']['tmp_name'], $upload_dir, 'logo_' . $clean_kode . '_');
                    if ($saved_logo) {
                        if ($logo && file_exists($upload_dir . $logo)) @unlink($upload_dir . $logo);
                        $logo = $saved_logo;
                    }
                }
            }
        }

        if ($is_edit && $id) {
            $stmt = $db->prepare("UPDATE lembaga_akreditasi SET kode=?, nama=?, lingkup=?, warna=?, bg=?, logo=?, link_website=?, urutan=? WHERE id=?");
            $stmt->execute([$kode, $nama, $lingkup, $warna, $bg, $logo, $link_website, $urutan, $id]);
            $_SESSION['flash'] = 'Data tautan resmi lembaga akreditasi berhasil diperbarui.';
        } else {
            $stmt = $db->prepare("INSERT INTO lembaga_akreditasi (kode, nama, lingkup, warna, bg, logo, link_website, urutan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kode, $nama, $lingkup, $warna, $bg, $logo, $link_website, $urutan]);
            $_SESSION['flash'] = 'Lembaga akreditasi baru berhasil ditambahkan.';
        }

        redirect(SITE_URL . '/admin/lembaga-akreditasi-list.php');
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-8">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="lembaga-akreditasi-list.php" style="color:var(--text-muted);">Lembaga Akreditasi</a>
            <span>/</span>
            <span style="color:var(--navy);"><?= $admin_page_title ?></span>
        </div>

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
                <div class="admin-table-title"><?= $admin_page_title ?></div>
                <a href="lembaga-akreditasi-list.php" class="btn-action" style="background:var(--bg-main);color:var(--text-muted);border:1.5px solid var(--border);text-decoration:none;">
                    Kembali
                </a>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Kode Singkat Lembaga <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="kode" class="form-control" placeholder="Contoh: LAMEMBA" value="<?= e($d['kode']) ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Nama Lengkap Lembaga <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: Lembaga Akreditasi Mandiri Ekonomi..." value="<?= e($d['nama']) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Lingkup Rumpun Ilmu / Fakultas
                            </label>
                            <input type="text" name="lingkup" class="form-control" placeholder="Contoh: Fakultas Ekonomi dan Bisnis (FEB)" value="<?= e($d['lingkup'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Warna Teks / Border
                            </label>
                            <div class="d-flex gap-2">
                                <input type="color" name="warna" class="form-control form-control-color" value="<?= e($d['warna'] ?: '#0A192F') ?>" style="width:50px;height:42px;">
                                <input type="text" class="form-control" value="<?= e($d['warna'] ?: '#0A192F') ?>" oninput="this.previousElementSibling.value = this.value">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Warna Latar Badge (Hex)
                            </label>
                            <div class="d-flex gap-2">
                                <input type="color" name="bg" class="form-control form-control-color" value="<?= e($d['bg'] ?: '#F1F3F7') ?>" style="width:50px;height:42px;">
                                <input type="text" class="form-control" value="<?= e($d['bg'] ?: '#F1F3F7') ?>" oninput="this.previousElementSibling.value = this.value">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Masa Berlaku Sertifikat / Akreditasi
                            </label>
                            <input type="date" name="masa_berlaku" class="form-control" value="<?= e($d['masa_berlaku'] ?? '') ?>">
                            <div class="form-text" style="font-size:0.75rem;">Notifikasi peringatan merah akan aktif otomatis jika tersisa 2 tahun (730 hari) sebelum tanggal ini.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Urutan Tampilan
                            </label>
                            <input type="number" name="urutan" class="form-control" value="<?= (int)($d['urutan'] ?? 1) ?>" min="1">
                        </div>

                        <!-- Link Website Lembaga Akreditasi -->
                        <div class="col-12 p-3 my-2" style="background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:10px;">
                            <label class="form-label fw-bold text-navy" style="color:var(--navy);font-family:var(--font-heading);">
                                <i class="bi bi-link-45deg me-1" style="font-size:1.15rem;color:var(--purple);"></i> Link Website Resmi Lembaga <span style="color:#C62828;">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-globe2"></i></span>
                                <input type="url" name="link_website" class="form-control" placeholder="https://example.or.id" value="<?= e($d['link_website'] ?? '') ?>" required>
                                <?php if (!empty($d['link_website'])): ?>
                                <a href="<?= e($d['link_website']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Link
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="form-text" style="font-size:0.75rem;">
                                Masukkan tautan resmi website lembaga (misal: <code>https://www.banpt.or.id/</code> atau <code>https://lamemba.or.id/</code>). Pengunjung akan diarahkan langsung ke website resmi tanpa perlu upload berkas fisik.
                            </div>
                        </div>

                        <!-- Upload Logo Lembaga -->
                        <div class="col-12 p-3 my-2" style="background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:10px;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label fw-bold mb-0" style="font-family:var(--font-heading);color:var(--navy);font-size:0.95rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="18" height="18" class="me-1" style="vertical-align:-3px;color:var(--purple);">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                    Logo Lembaga Akreditasi
                                </label>
                                <span class="badge bg-light text-muted border" style="font-size:0.75rem;">PNG / SVG / JPG / WebP</span>
                            </div>

                            <div class="row align-items-center g-3">
                                <!-- Pratinjau Logo Saat Ini / Baru -->
                                <div class="col-auto">
                                    <div id="logoPreviewWrapper" style="width:90px;height:90px;border-radius:12px;background:#ffffff;border:2px dashed #CBD5E1;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:6px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                                        <?php if (!empty($d['logo']) && file_exists(__DIR__ . '/../uploads/akreditasi/' . $d['logo'])): ?>
                                            <img id="logoPreviewImg" src="<?= SITE_URL ?>/uploads/akreditasi/<?= e($d['logo']) ?>" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;">
                                        <?php else: ?>
                                            <img id="logoPreviewImg" src="" alt="Preview" style="max-width:100%;max-height:100%;object-fit:contain;display:none;">
                                            <span id="logoPreviewPlaceholder" style="font-size:0.7rem;color:#94A3B8;text-align:center;font-weight:600;line-height:1.2;">
                                                Belum Ada Logo
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Input & Keterangan -->
                                <div class="col">
                                    <div class="d-flex gap-2 align-items-center">
                                        <input type="file" name="logo" id="inputLogoLembaga" class="form-control" accept=".png,.jpg,.jpeg,.svg,.webp">
                                        <button type="button" class="btn btn-dark btn-sm d-none align-items-center gap-1" id="btnEditLogoLembaga" style="white-space:nowrap;padding:0.6rem 0.9rem;border-radius:8px;">
                                            <i class="bi bi-crop text-warning"></i> Edit / Crop
                                        </button>
                                    </div>
                                    <input type="hidden" name="cropped_logo_data" id="croppedLogoData">
                                    <div class="form-text mt-1" style="font-size:0.78rem;color:#64748B;">
                                        <span class="badge bg-light text-dark border me-1"><i class="bi bi-hdd-fill text-warning me-1"></i>Batas Ukuran: Maksimal 5 MB</span>
                                        Pilih file logo resmi lembaga. Disarankan format <strong>PNG transparan</strong> atau gunakan editor crop agar logo pas di tengah.
                                    </div>
                                    <?php if (!empty($d['logo']) && file_exists(__DIR__ . '/../uploads/akreditasi/' . $d['logo'])): ?>
                                    <div class="d-flex align-items-center gap-3 mt-2">
                                        <button type="button" class="btn btn-sm btn-outline-primary py-0 px-2" style="font-size:0.75rem;height:26px;" onclick="editExistingLogoLembaga('<?= SITE_URL ?>/uploads/akreditasi/<?= e($d['logo']) ?>')">
                                            <i class="bi bi-crop text-warning me-1"></i>Edit Logo Tersimpan
                                        </button>
                                        <div class="form-check m-0">
                                            <input class="form-check-input" type="checkbox" name="hapus_logo" value="1" id="checkHapusLogo">
                                            <label class="form-check-label small text-danger" for="checkHapusLogo">
                                                Hapus logo saat ini
                                            </label>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="border-top mt-4 pt-3 d-flex justify-content-end gap-2">
                        <a href="lembaga-akreditasi-list.php" class="btn-action" style="background:var(--bg-main);color:var(--text-muted);border:1.5px solid var(--border);text-decoration:none;">
                            Batal
                        </a>
                        <button type="submit" class="btn-save px-4 py-2">
                            Simpan Lembaga
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Modal Cropper Universal Lembaga Akreditasi -->
<div class="modal fade" id="modalCropLembaga" tabindex="-1" aria-labelledby="modalCropLembagaLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
            <div class="modal-header text-white" style="background:linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);padding:1.25rem 1.75rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-crop" style="font-size:1.4rem;color:#FFD54F;"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="modalCropLembagaLabel">Editor Logo: Crop, Rotate, Flip &amp; Zoom</h5>
                        <small style="color:rgba(255,255,255,0.85);">Sesuaikan framing dan proporsi logo lembaga akreditasi.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" style="background:#F1F5F9;">
                <div class="crop-modal-container mb-3 shadow-inner" style="max-height:480px;background:#0F172A;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:10px;">
                    <img id="cropperSourceImgLembaga" src="" alt="Source" style="max-width:100%;max-height:460px;display:block;">
                </div>
                <div class="card p-3 border shadow-sm rounded-3 bg-white">
                    <div class="row g-2 align-items-center">
                        <div class="col-lg-4 col-md-6 col-12 d-flex gap-2">
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipHLembaga">
                                <i class="bi bi-symmetry-vertical text-warning"></i> Flip H (⇄)
                            </button>
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipVLembaga">
                                <i class="bi bi-symmetry-horizontal text-warning"></i> Flip V (⇅)
                            </button>
                        </div>
                        <div class="col-lg-3 col-md-6 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateLeftLembaga">
                                <i class="bi bi-arrow-counterclockwise"></i> ↺ 90°
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateRightLembaga">
                                <i class="bi bi-arrow-clockwise"></i> ↻ 90°
                            </button>
                        </div>
                        <div class="col-lg-5 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomInLembaga">
                                <i class="bi bi-zoom-in"></i> +
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomOutLembaga">
                                <i class="bi bi-zoom-out"></i> -
                            </button>
                            <button type="button" class="btn btn-outline-danger flex-fill fw-bold py-2" id="btnResetCropLembaga">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top flex-wrap">
                        <span class="text-muted small fw-bold me-1 text-uppercase">Rasio:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active ratio-btn-lembaga fw-semibold" data-ratio="1">1:1 Persegi (Logo)</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-lembaga fw-semibold" data-ratio="1.333333">4:3 Galeri</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-lembaga fw-semibold" data-ratio="1.777778">16:9 Banner</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-lembaga fw-semibold" data-ratio="NaN">Bebas</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between" style="background:#ffffff;border-top:1px solid #E2E8F0;padding:1rem 1.75rem;">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius:8px;">Batal</button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-bold d-inline-flex align-items-center gap-2" id="btnApplyCropLembaga" style="background:var(--navy);border-color:var(--navy);border-radius:8px;">
                    <i class="bi bi-check-lg fs-5"></i> Terapkan Hasil Penyesuaian
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let cropperLembaga = null;
let scaleXLembaga = 1;
let scaleYLembaga = 1;
let currentRatioLembaga = 1;

const modalCropLembagaEl  = document.getElementById('modalCropLembaga');
const bsModalLembaga      = new bootstrap.Modal(modalCropLembagaEl);
const sourceImgLembaga    = document.getElementById('cropperSourceImgLembaga');
const fileInputLembaga    = document.getElementById('inputLogoLembaga');
const btnEditLembaga      = document.getElementById('btnEditLogoLembaga');
const logoPreviewImg      = document.getElementById('logoPreviewImg');
const logoPlaceholder     = document.getElementById('logoPreviewPlaceholder');
const hiddenCropLembaga   = document.getElementById('croppedLogoData');
const applyBtnLembaga     = document.getElementById('btnApplyCropLembaga');

function initCropperLembaga() {
    if (cropperLembaga) cropperLembaga.destroy();
    scaleXLembaga = 1;
    scaleYLembaga = 1;
    cropperLembaga = new Cropper(sourceImgLembaga, {
        aspectRatio: currentRatioLembaga,
        viewMode: 1,
        autoCropArea: 0.95,
        responsive: true,
        guides: true,
        checkCrossOrigin: false
    });
}

modalCropLembagaEl.addEventListener('shown.bs.modal', function() { initCropperLembaga(); });
modalCropLembagaEl.addEventListener('hidden.bs.modal', function() {
    if (cropperLembaga) {
        cropperLembaga.destroy();
        cropperLembaga = null;
    }
});

// Controls
document.getElementById('btnRotateLeftLembaga')?.addEventListener('click', function() { if (cropperLembaga) cropperLembaga.rotate(-90); });
document.getElementById('btnRotateRightLembaga')?.addEventListener('click', function() { if (cropperLembaga) cropperLembaga.rotate(90); });
document.getElementById('btnFlipHLembaga')?.addEventListener('click', function() { if (cropperLembaga) { scaleXLembaga = -scaleXLembaga; cropperLembaga.scaleX(scaleXLembaga); } });
document.getElementById('btnFlipVLembaga')?.addEventListener('click', function() { if (cropperLembaga) { scaleYLembaga = -scaleYLembaga; cropperLembaga.scaleY(scaleYLembaga); } });
document.getElementById('btnZoomInLembaga')?.addEventListener('click', function() { if (cropperLembaga) cropperLembaga.zoom(0.1); });
document.getElementById('btnZoomOutLembaga')?.addEventListener('click', function() { if (cropperLembaga) cropperLembaga.zoom(-0.1); });
document.getElementById('btnResetCropLembaga')?.addEventListener('click', function() { if (cropperLembaga) { cropperLembaga.reset(); scaleXLembaga = 1; scaleYLembaga = 1; } });

document.querySelectorAll('.ratio-btn-lembaga').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.ratio-btn-lembaga').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');
        const val = parseFloat(this.getAttribute('data-ratio'));
        currentRatioLembaga = isNaN(val) ? NaN : val;
        if (cropperLembaga) cropperLembaga.setAspectRatio(currentRatioLembaga);
    });
});

fileInputLembaga?.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const file = this.files[0];
        // Jika SVG, tidak perlu di-crop (vektor)
        if (file.name.toLowerCase().endsWith('.svg')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                logoPreviewImg.src = e.target.result;
                logoPreviewImg.style.display = 'block';
                if (logoPlaceholder) logoPlaceholder.style.display = 'none';
                btnEditLembaga.classList.add('d-none');
            };
            reader.readAsDataURL(file);
            return;
        }

        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                sourceImgLembaga.src = e.target.result;
                logoPreviewImg.src = e.target.result;
                logoPreviewImg.style.display = 'block';
                if (logoPlaceholder) logoPlaceholder.style.display = 'none';
                btnEditLembaga.classList.remove('d-none');
                btnEditLembaga.classList.add('d-inline-flex');
                hiddenCropLembaga.value = '';
                bsModalLembaga.show();
            };
            reader.readAsDataURL(file);
        }
    }
});

btnEditLembaga?.addEventListener('click', function() {
    if (sourceImgLembaga.src) {
        bsModalLembaga.show();
    }
});

window.editExistingLogoLembaga = function(imgUrl) {
    sourceImgLembaga.src = imgUrl;
    bsModalLembaga.show();
};

applyBtnLembaga?.addEventListener('click', function() {
    if (!cropperLembaga) return;
    const canvas = cropperLembaga.getCroppedCanvas({
        maxWidth: 1000,
        maxHeight: 1000,
        imageSmoothingQuality: 'high'
    });
    if (canvas) {
        const dataUrl = canvas.toDataURL('image/webp', 0.9);
        hiddenCropLembaga.value = dataUrl;
        logoPreviewImg.src = dataUrl;
        logoPreviewImg.style.display = 'block';
        if (logoPlaceholder) logoPlaceholder.style.display = 'none';
        btnEditLembaga.classList.remove('d-none');
        btnEditLembaga.classList.add('d-inline-flex');
        bsModalLembaga.hide();
    }
});
</script>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
