<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$type = trim($_GET['type'] ?? 'kegiatan'); // 'kegiatan' or 'opening'
if (!in_array($type, ['kegiatan', 'opening'])) $type = 'kegiatan';

$id = (int)($_GET['id'] ?? 0);
$is_edit = false;
$data = [];

if ($id > 0) {
    $table = ($type === 'kegiatan') ? 'ami_siklus1_kegiatan' : 'ami_siklus1_opening';
    $stmt = $db->prepare("SELECT * FROM {$table} WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) $is_edit = true;
}

$admin_page_title = ($type === 'kegiatan')
    ? ($is_edit ? 'Edit Dokumen Rangkaian Kegiatan AMI' : 'Tambah Dokumen Rangkaian Kegiatan AMI')
    : ($is_edit ? 'Edit Foto Opening Meeting AMI' : 'Tambah Foto Opening Meeting AMI');

$periodes = $db->query("SELECT * FROM ami_periode WHERE is_active = 1 ORDER BY urutan ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$default_periode = $_GET['periode'] ?? ($data['periode'] ?? ($periodes[0]['nama_periode'] ?? '2025/2026'));

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $periode = trim($_POST['periode'] ?? '');
    $judul = trim($_POST['judul'] ?? '');
    $urutan = (int)($_POST['urutan'] ?? 1);
    $keterangan = trim($_POST['keterangan'] ?? '');

    if ($periode === '' || $judul === '') {
        $error = 'Periode dan Judul wajib diisi.';
    } else {
        if ($type === 'kegiatan') {
            $file_dokumen = $data['file_dokumen'] ?? '';
            $file_size = $data['file_size'] ?? '';

            if (!empty($_FILES['file_dokumen']['name'])) {
                $orig_name = $_FILES['file_dokumen']['name'];
                $ext = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));
                $allowed = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx'];

                if (!in_array($ext, $allowed)) {
                    $error = 'Format file tidak didukung. Harap upload dokumen PDF, Word, atau Excel.';
                } elseif ($_FILES['file_dokumen']['size'] > 30 * 1024 * 1024) {
                    $error = 'Ukuran file dokumen maksimal 30MB.';
                } else {
                    $upload_dir = __DIR__ . '/../uploads/ami/siklus1/';
                    if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);

                    $new_name = 'kegiatan_' . time() . '_' . uniqid() . '.' . $ext;
                    if (move_uploaded_file($_FILES['file_dokumen']['tmp_name'], $upload_dir . $new_name)) {
                        $file_dokumen = 'ami/siklus1/' . $new_name;
                        // Format bytes to human readable
                        $bytes = $_FILES['file_dokumen']['size'];
                        if ($bytes >= 1048576) {
                            $file_size = number_format($bytes / 1048576, 2) . ' MB';
                        } else {
                            $file_size = number_format($bytes / 1024, 1) . ' KB';
                        }
                    } else {
                        $error = 'Gagal menyimpan file yang diunggah.';
                    }
                }
            } elseif (!$is_edit) {
                $error = 'Dokumen softfile wajib diunggah.';
            }

            if (!$error) {
                if ($is_edit) {
                    $stmt = $db->prepare("UPDATE ami_siklus1_kegiatan SET periode = ?, judul = ?, file_dokumen = ?, file_size = ?, keterangan = ?, urutan = ? WHERE id = ?");
                    $stmt->execute([$periode, $judul, $file_dokumen, $file_size, $keterangan, $urutan, $id]);
                    $_SESSION['flash'] = 'Dokumen rangkaian kegiatan berhasil diperbarui.';
                } else {
                    $stmt = $db->prepare("INSERT INTO ami_siklus1_kegiatan (periode, judul, file_dokumen, file_size, keterangan, urutan) VALUES (?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$periode, $judul, $file_dokumen, $file_size, $keterangan, $urutan]);
                    $_SESSION['flash'] = 'Dokumen rangkaian kegiatan berhasil ditambahkan.';
                }
                redirect(SITE_URL . '/admin/ami-siklus-list.php?tab=siklus1&periode=' . urlencode($periode));
            }

        } else {
            // Opening Meeting (Support Multiple Upload & Image Editor / Cropper)
            $tanggal_kegiatan = trim($_POST['tanggal_kegiatan'] ?? '') ?: null;
            $foto = $data['foto'] ?? '';
            $target_dir = __DIR__ . '/../uploads/ami/siklus1/';
            if (!is_dir($target_dir)) @mkdir($target_dir, 0777, true);

            $cropped_data = trim($_POST['cropped_image_data'] ?? '');
            $has_cropped = false;

            if (!empty($cropped_data) && preg_match('/^data:image\/(\w+);base64,/', $cropped_data, $c_match)) {
                $raw_data = substr($cropped_data, strpos($cropped_data, ',') + 1);
                $decoded = base64_decode($raw_data);
                if ($decoded !== false) {
                    $c_ext = strtolower($c_match[1]);
                    if (!in_array($c_ext, ['jpg', 'jpeg', 'png', 'webp'])) $c_ext = 'webp';
                    $c_filename = 'opening_crop_' . time() . '_' . uniqid() . '.' . ($c_ext === 'jpeg' ? 'jpg' : $c_ext);
                    if (file_put_contents($target_dir . $c_filename, $decoded)) {
                        if ($c_ext !== 'webp') {
                            $saved_c_webp = convertAndSaveWebP($target_dir . $c_filename, $target_dir, 'opening_', 88, 1920);
                            if ($saved_c_webp) {
                                @unlink($target_dir . $c_filename);
                                $foto = 'ami/siklus1/' . $saved_c_webp;
                            } else {
                                $foto = 'ami/siklus1/' . $c_filename;
                            }
                        } else {
                            $foto = 'ami/siklus1/' . $c_filename;
                        }
                        $has_cropped = true;
                    }
                }
            }

            if ($is_edit) {
                if (!$has_cropped && !empty($_FILES['foto']['name']) && !is_array($_FILES['foto']['name'])) {
                    $saved_webp = convertAndSaveWebP($_FILES['foto']['tmp_name'], $target_dir, 'opening_', 88, 1920);
                    if ($saved_webp) {
                        $foto = 'ami/siklus1/' . $saved_webp;
                    } else {
                        $error = 'Gagal memproses gambar foto. Pastikan format JPG, PNG, atau WebP valid.';
                    }
                }
                if (!$error) {
                    $stmt = $db->prepare("UPDATE ami_siklus1_opening SET periode = ?, judul = ?, tanggal_kegiatan = ?, foto = ?, keterangan = ?, urutan = ? WHERE id = ?");
                    $stmt->execute([$periode, $judul, $tanggal_kegiatan, $foto, $keterangan, $urutan, $id]);
                    $_SESSION['flash'] = 'Foto opening meeting berhasil diperbarui.';
                    redirect(SITE_URL . '/admin/ami-siklus-list.php?tab=siklus1&periode=' . urlencode($periode));
                }
            } else {
                // Tambah baru
                $uploaded_photos = [];
                if ($has_cropped) {
                    // Jika ada gambar hasil crop, gunakan gambar tersebut (cegah upload ganda)
                    $uploaded_photos[] = $foto;
                } else {
                    // Hanya baca dari $_FILES jika tidak ada gambar hasil crop
                    $file_names = isset($_FILES['foto']['name']) ? (is_array($_FILES['foto']['name']) ? $_FILES['foto']['name'] : [$_FILES['foto']['name']]) : [];
                    $file_tmps  = isset($_FILES['foto']['tmp_name']) ? (is_array($_FILES['foto']['tmp_name']) ? $_FILES['foto']['tmp_name'] : [$_FILES['foto']['tmp_name']]) : [];
                    $file_errors= isset($_FILES['foto']['error']) ? (is_array($_FILES['foto']['error']) ? $_FILES['foto']['error'] : [$_FILES['foto']['error']]) : [];

                    if (empty($file_names) || empty($file_names[0])) {
                        $error = 'Foto opening meeting wajib diunggah atau disesuaikan.';
                    } else {
                        for ($i = 0; $i < count($file_names); $i++) {
                            if (isset($file_errors[$i]) && $file_errors[$i] === UPLOAD_ERR_OK) {
                                $saved_webp = convertAndSaveWebP($file_tmps[$i], $target_dir, 'opening_', 88, 1920);
                                if ($saved_webp) {
                                    $uploaded_photos[] = 'ami/siklus1/' . $saved_webp;
                                }
                            }
                        }
                        if (empty($uploaded_photos)) {
                            $error = 'Gagal memproses gambar foto. Pastikan format JPG, PNG, atau WebP valid.';
                        }
                    }
                }

                if (!$error) {
                    $stmt = $db->prepare("INSERT INTO ami_siklus1_opening (periode, judul, tanggal_kegiatan, foto, keterangan, urutan) VALUES (?, ?, ?, ?, ?, ?)");
                    foreach ($uploaded_photos as $idx => $u_foto) {
                        $item_judul = (count($uploaded_photos) > 1 && $idx > 0) ? ($judul . ' (' . ($idx + 1) . ')') : $judul;
                        $stmt->execute([$periode, $item_judul, $tanggal_kegiatan, $u_foto, $keterangan, $urutan + $idx]);
                    }
                    $_SESSION['flash'] = count($uploaded_photos) . ' foto opening meeting berhasil ditambahkan.';
                    redirect(SITE_URL . '/admin/ami-siklus-list.php?tab=siklus1&periode=' . urlencode($periode));
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-8 col-lg-9">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="ami-siklus-list.php?tab=siklus1&periode=<?= urlencode($default_periode) ?>" style="color:var(--text-muted);">Siklus AMI (Siklus 1)</a>
            <span>/</span>
            <span style="color:var(--navy);"><?= $admin_page_title ?></span>
        </div>

        <?php if ($error): ?>
        <div class="alert-lpm alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
        </div>
        <?php endif; ?>

        <div class="admin-table-wrap p-4 p-md-5">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--navy);"><?= $admin_page_title ?></h4>
                    <p class="text-muted small mb-0">
                        <?= ($type === 'kegiatan') ? 'Unggah dokumen softfile rangkaian kegiatan AMI untuk dapat diunduh publik.' : 'Unggah foto dokumentasi Opening Meeting AMI dengan judul dan tanggal kegiatan.' ?>
                    </p>
                </div>
                <span class="badge <?= $type === 'kegiatan' ? 'bg-primary' : 'bg-purple' ?> px-3 py-2" style="font-size:0.85rem;">
                    <?= $type === 'kegiatan' ? '<i class="bi bi-file-earmark-text me-1"></i> Rangkaian Kegiatan' : '<i class="bi bi-camera-fill me-1"></i> Opening Meeting' ?>
                </span>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Periode AMI <span class="text-danger">*</span></label>
                        <select name="periode" class="form-select" required>
                            <?php foreach ($periodes as $p): ?>
                            <option value="<?= e($p['nama_periode']) ?>" <?= $default_periode === $p['nama_periode'] ? 'selected' : '' ?>>
                                <?= e($p['nama_periode']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Urutan Tampil</label>
                        <input type="number" name="urutan" class="form-control" value="<?= e($data['urutan'] ?? 1) ?>" min="1">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold small">Judul <?= $type === 'kegiatan' ? 'Dokumen Kegiatan' : 'Foto / Kegiatan Opening' ?> <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control" id="inputJudul" placeholder="Contoh: <?= $type === 'kegiatan' ? 'Panduan Rangkaian Kegiatan AMI 2025/2026' : 'Opening Meeting AMI Bersama Rektor & Pimpinan Fakultas' ?>" value="<?= e($data['judul'] ?? ($_POST['judul'] ?? '')) ?>" required>
                    </div>

                    <?php if ($type === 'opening'): ?>
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Tanggal Pelaksanaan Kegiatan</label>
                        <input type="date" name="tanggal_kegiatan" class="form-control" value="<?= e($data['tanggal_kegiatan'] ?? date('Y-m-d')) ?>">
                    </div>

                    <div class="col-12">
                        <label class="form-label fw-bold small">
                            Unggah Foto Opening Meeting <?= $is_edit ? '<span class="text-muted fw-normal">(Biarkan kosong jika tidak diganti)</span>' : '<span class="text-danger">*</span>' ?>
                        </label>
                        
                        <div class="d-flex align-items-center gap-2 flex-wrap mb-2">
                            <input type="file" id="fotoInput" name="<?= $is_edit ? 'foto' : 'foto[]' ?>" class="form-control" accept="image/jpeg,image/png,image/webp" <?= $is_edit ? '' : 'required' ?> <?= $is_edit ? '' : 'multiple' ?> style="max-width:380px;">
                            <button type="button" class="btn btn-outline-primary d-none fw-semibold" id="btnOpenCropper" style="border-radius:8px;">
                                <i class="bi bi-crop me-1"></i> Sesuaikan / Edit Gambar (Crop &amp; Rotate)
                            </button>
                        </div>

                        <!-- Hidden input untuk menampung gambar hasil crop (Base64) -->
                        <input type="hidden" name="cropped_image_data" id="croppedImageData" value="">

                        <!-- Pratinjau Card & Hasil Penyesuaian -->
                        <div id="cropPreviewWrapper" class="mt-3 p-3 border rounded-3 bg-light <?= empty($data['foto']) ? 'd-none' : '' ?>">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="small fw-bold text-muted" id="previewLabel">
                                    <?= !empty($data['foto']) ? 'Foto Saat Ini:' : 'Pratinjau Foto Siap Disimpan:' ?>
                                </span>
                                <span class="badge bg-success-subtle text-success border border-success d-none" id="cropBadge">
                                    <i class="bi bi-check-circle me-1"></i> Telah Disesuaikan &amp; Di-crop
                                </span>
                            </div>
                            <div style="max-width:360px;border-radius:10px;overflow:hidden;box-shadow:0 4px 12px rgba(0,0,0,0.1);background:#000;">
                                <img id="cropPreviewImg" src="<?= !empty($data['foto']) ? SITE_URL . '/uploads/' . e($data['foto']) : '' ?>" alt="Preview" style="width:100%;height:200px;object-fit:cover;display:block;">
                            </div>
                        </div>

                        <div class="form-text mt-2">
                            <span class="badge bg-light text-dark border me-1"><i class="bi bi-hdd-fill text-warning me-1"></i>Batas Ukuran: Maksimal 10 MB per foto</span>
                            <span class="badge bg-info-subtle text-dark border me-1"><i class="bi bi-magic me-1"></i>Editor Interaktif</span>
                            Format JPG, PNG, atau WebP. Anda dapat langsung mengedit gambar (crop proporsi card, putar orientasi 90°, flip, dan zoom) sebelum disimpan. File otomatis dikompres ke WebP HD.
                        </div>
                    </div>

                    <?php else: ?>
                    <!-- Form Kegiatan (Dokumen) -->
                    <div class="col-12">
                        <label class="form-label fw-bold small">Unggah File Dokumen Softfile <?= $is_edit ? '<span class="text-muted fw-normal">(Biarkan kosong jika tidak diganti)</span>' : '<span class="text-danger">*</span>' ?></label>
                        <input type="file" name="file_dokumen" id="fileDokumen" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" <?= $is_edit ? '' : 'required' ?> onchange="autoFillJudul(this)">
                        
                        <div class="mt-2 d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-warning-subtle text-dark border">
                                <i class="bi bi-hdd-fill text-warning me-1"></i> Batas Ukuran: Maksimal 30 MB
                            </span>
                            <span class="small text-muted">Format didukung: PDF, Word (.doc, .docx), Excel (.xls, .xlsx), PowerPoint (.ppt, .pptx).</span>
                        </div>

                        <?php if (!empty($data['file_dokumen'])): ?>
                        <div class="mt-3 p-3 border rounded bg-light d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2">
                                <i class="bi bi-file-earmark-pdf-fill text-danger fs-4"></i>
                                <div>
                                    <div class="fw-bold small"><?= basename($data['file_dokumen']) ?></div>
                                    <div class="text-muted" style="font-size:0.75rem;">Ukuran: <?= e($data['file_size'] ?? '-') ?></div>
                                </div>
                            </div>
                            <a href="<?= SITE_URL ?>/uploads/<?= e($data['file_dokumen']) ?>" target="_blank" class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-download me-1"></i> Unduh Dokumen
                            </a>
                        </div>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>

                    <div class="col-12">
                        <label class="form-label fw-bold small">Catatan / Deskripsi Tambahan (Opsional)</label>
                        <textarea name="keterangan" rows="3" class="form-control" placeholder="Tuliskan keterangan singkat mengenai dokumen atau foto ini..."><?= e($data['keterangan'] ?? '') ?></textarea>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-save me-1"></i> Simpan Data
                    </button>
                    <a href="ami-siklus-list.php?tab=siklus1&periode=<?= urlencode($default_periode) ?>" class="btn btn-light px-3 py-2">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ==============================================================
     MODAL CROPPER.JS DENGAN FITUR CROP, ROTATE, FLIP & ZOOM
============================================================== -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<div class="modal fade" id="modalCropImage" tabindex="-1" aria-labelledby="modalCropImageLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
            <div class="modal-header text-white" style="background:linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);padding:1.25rem 1.75rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-crop" style="font-size:1.4rem;color:#FFD54F;"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="modalCropImageLabel">Editor Gambar AMI: Crop, Rotate, Flip &amp; Zoom</h5>
                        <small style="color:rgba(255,255,255,0.85);">Sesuaikan framing kartu, putar orientasi foto miring/terbalik dari HP, cermin flip, dan atur zoom.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" style="background:#F1F5F9;">
                <div class="crop-modal-container mb-3 shadow-inner" style="max-height:480px;background:#0F172A;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:10px;">
                    <img id="cropperSourceImg" src="" alt="Source" style="max-width:100%;max-height:460px;display:block;">
                </div>

                <!-- Toolbar Kontrol: Flip, Rotate, Zoom, dan Rasio -->
                <div class="card p-3 border shadow-sm rounded-3 bg-white">
                    <div class="row g-2 align-items-center">
                        <!-- 1. Flip (Horizontal & Vertical) -->
                        <div class="col-lg-4 col-md-6 col-12 d-flex gap-2">
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipH" title="Balik Horizontal (kiri-kanan)">
                                <i class="bi bi-symmetry-vertical text-warning"></i>
                                <span>Flip Horizontal (⇄)</span>
                            </button>
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipV" title="Balik Vertikal (atas-bawah)">
                                <i class="bi bi-symmetry-horizontal text-warning"></i>
                                <span>Flip Vertical (⇅)</span>
                            </button>
                        </div>

                        <!-- 2. Rotate (90 deg) -->
                        <div class="col-lg-3 col-md-6 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateLeft" title="Putar 90° Kiri">
                                <i class="bi bi-arrow-counterclockwise"></i> ↺ 90° Kiri
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateRight" title="Putar 90° Kanan">
                                <i class="bi bi-arrow-clockwise"></i> ↻ 90° Kanan
                            </button>
                        </div>

                        <!-- 3. Zoom In, Out & Reset -->
                        <div class="col-lg-5 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomIn" title="Perbesar">
                                <i class="bi bi-zoom-in"></i> Zoom +
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomOut" title="Perkecil">
                                <i class="bi bi-zoom-out"></i> Zoom -
                            </button>
                            <button type="button" class="btn btn-outline-danger flex-fill fw-bold py-2" id="btnResetCrop" title="Reset posisi awal">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                        </div>
                    </div>

                    <!-- 4. Pilihan Rasio Aspek Card -->
                    <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top flex-wrap">
                        <span class="text-muted small fw-bold me-1 text-uppercase" style="letter-spacing:0.5px;">
                            <i class="bi bi-aspect-ratio me-1 text-primary"></i> Rasio Card:
                        </span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active ratio-btn fw-semibold" data-ratio="1.777778">
                                16:9 (Card Standar)
                            </button>
                            <button type="button" class="btn btn-outline-primary ratio-btn fw-semibold" data-ratio="1.333333">
                                4:3 (Card Galeri)
                            </button>
                            <button type="button" class="btn btn-outline-primary ratio-btn fw-semibold" data-ratio="1">
                                1:1 (Persegi)
                            </button>
                            <button type="button" class="btn btn-outline-primary ratio-btn fw-semibold" data-ratio="NaN">
                                Bebas (Free)
                            </button>
                        </div>
                    </div>
                </div>
            </div>
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

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
function autoFillJudul(input) {
    if (input.files && input.files[0]) {
        var file = input.files[0];
        // Validasi batas ukuran file dokumen (Maks 30MB)
        var sizeMB = file.size / (1024 * 1024);
        if (sizeMB > 30) {
            alert('Peringatan: Ukuran file (' + sizeMB.toFixed(2) + ' MB) melebihi batas maksimal 30 MB. Silakan pilih file yang lebih kecil.');
            input.value = '';
            return;
        }

        var filename = file.name;
        var nameWithoutExt = filename.replace(/\.[^/.]+$/, "");
        var cleanTitle = nameWithoutExt.replace(/[_-]+/g, " ").trim();
        cleanTitle = cleanTitle.charAt(0).toUpperCase() + cleanTitle.slice(1);
        
        var judulInput = document.getElementById('inputJudul');
        if (judulInput && (judulInput.value.trim() === '' || confirm('Perbarui judul form dengan nama file "' + cleanTitle + '"?'))) {
            judulInput.value = cleanTitle;
        }
    }
}

// Inisialisasi Cropper.js untuk Foto Opening
document.addEventListener('DOMContentLoaded', function() {
    var fotoInput         = document.getElementById('fotoInput');
    var btnOpenCropper    = document.getElementById('btnOpenCropper');
    var modalEl           = document.getElementById('modalCropImage');
    var cropperSourceImg  = document.getElementById('cropperSourceImg');
    var btnApplyCrop      = document.getElementById('btnApplyCrop');
    var croppedImageData  = document.getElementById('croppedImageData');
    var cropPreviewWrapper= document.getElementById('cropPreviewWrapper');
    var cropPreviewImg    = document.getElementById('cropPreviewImg');
    var cropBadge         = document.getElementById('cropBadge');
    var previewLabel      = document.getElementById('previewLabel');

    if (!fotoInput || !modalEl) return;

    var bsModal = new bootstrap.Modal(modalEl);
    var cropper = null;
    var scaleX = 1;
    var scaleY = 1;
    var currentRatio = 16 / 9;

    function initCropper() {
        if (cropper) cropper.destroy();
        scaleX = 1;
        scaleY = 1;
        cropper = new Cropper(cropperSourceImg, {
            aspectRatio: currentRatio,
            viewMode: 2,
            autoCropArea: 0.95,
            responsive: true,
            restore: false,
            guides: true,
            center: true,
            highlight: false,
            cropBoxMovable: true,
            cropBoxResizable: true,
            toggleDragModeOnDblclick: false
        });
    }

    fotoInput.addEventListener('change', function(e) {
        var files = e.target.files;
        if (files && files.length > 0) {
            var file = files[0];
            if (!file.type.match(/^image\//)) {
                alert('File yang dipilih bukan gambar.');
                return;
            }
            var reader = new FileReader();
            reader.onload = function(evt) {
                cropperSourceImg.src = evt.target.result;
                if (btnOpenCropper) btnOpenCropper.classList.remove('d-none');
                bsModal.show();
            };
            reader.readAsDataURL(file);
        }
    });

    if (btnOpenCropper) {
        btnOpenCropper.addEventListener('click', function() {
            if (cropperSourceImg.src) {
                bsModal.show();
            }
        });
    }

    modalEl.addEventListener('shown.bs.modal', function() {
        initCropper();
    });

    modalEl.addEventListener('hidden.bs.modal', function() {
        if (cropper) {
            cropper.destroy();
            cropper = null;
        }
    });

    // Kontrol Rotate
    document.getElementById('btnRotateLeft')?.addEventListener('click', function() {
        if (cropper) cropper.rotate(-90);
    });
    document.getElementById('btnRotateRight')?.addEventListener('click', function() {
        if (cropper) cropper.rotate(90);
    });

    // Kontrol Flip
    document.getElementById('btnFlipH')?.addEventListener('click', function() {
        if (!cropper) return;
        scaleX = -scaleX;
        cropper.scaleX(scaleX);
    });
    document.getElementById('btnFlipV')?.addEventListener('click', function() {
        if (!cropper) return;
        scaleY = -scaleY;
        cropper.scaleY(scaleY);
    });

    // Kontrol Zoom & Reset
    document.getElementById('btnZoomIn')?.addEventListener('click', function() {
        if (cropper) cropper.zoom(0.1);
    });
    document.getElementById('btnZoomOut')?.addEventListener('click', function() {
        if (cropper) cropper.zoom(-0.1);
    });
    document.getElementById('btnResetCrop')?.addEventListener('click', function() {
        if (cropper) {
            cropper.reset();
            scaleX = 1;
            scaleY = 1;
        }
    });

    // Rasio Selector
    document.querySelectorAll('.ratio-btn').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.ratio-btn').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            var val = parseFloat(this.getAttribute('data-ratio'));
            currentRatio = isNaN(val) ? NaN : val;
            if (cropper) cropper.setAspectRatio(currentRatio);
        });
    });

    // Terapkan Crop
    btnApplyCrop?.addEventListener('click', function() {
        if (!cropper) return;
        var canvas = cropper.getCroppedCanvas({
            maxWidth: 1920,
            maxHeight: 1200,
            imageSmoothingQuality: 'high'
        });
        if (canvas) {
            var dataUrl = canvas.toDataURL('image/webp', 0.88);
            croppedImageData.value = dataUrl;
            cropPreviewImg.src = dataUrl;
            cropPreviewWrapper.classList.remove('d-none');
            cropBadge.classList.remove('d-none');
            if (previewLabel) previewLabel.innerText = 'Pratinjau Hasil Edit Siap Disimpan:';
            // Kosongkan file input asli agar tidak terkirim ganda di $_FILES
            fotoInput.value = '';
            fotoInput.removeAttribute('required');
            bsModal.hide();
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
