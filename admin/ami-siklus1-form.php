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
            // Opening Meeting (Support Multiple Upload)
            $tanggal_kegiatan = trim($_POST['tanggal_kegiatan'] ?? '') ?: null;
            $foto = $data['foto'] ?? '';
            $target_dir = __DIR__ . '/../uploads/ami/siklus1/';

            if ($is_edit) {
                if (!empty($_FILES['foto']['name']) && !is_array($_FILES['foto']['name'])) {
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
                // Tambah baru (bisa unggah 1 atau lebih dari 1 foto sekaligus)
                $uploaded_photos = [];
                $file_names = isset($_FILES['foto']['name']) ? (is_array($_FILES['foto']['name']) ? $_FILES['foto']['name'] : [$_FILES['foto']['name']]) : [];
                $file_tmps  = isset($_FILES['foto']['tmp_name']) ? (is_array($_FILES['foto']['tmp_name']) ? $_FILES['foto']['tmp_name'] : [$_FILES['foto']['tmp_name']]) : [];
                $file_errors= isset($_FILES['foto']['error']) ? (is_array($_FILES['foto']['error']) ? $_FILES['foto']['error'] : [$_FILES['foto']['error']]) : [];

                if (empty($file_names) || empty($file_names[0])) {
                    $error = 'Foto opening meeting wajib diunggah.';
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
                        <label class="form-label fw-bold small">Unggah Foto Opening Meeting <?= $is_edit ? '<span class="text-muted fw-normal">(Biarkan kosong jika tidak diganti)</span>' : '<span class="text-danger">*</span> <span class="text-muted fw-normal">(Bisa pilih lebih dari 1 foto sekaligus)</span>' ?></label>
                        <?php if ($is_edit): ?>
                        <input type="file" name="foto" class="form-control" accept="image/*">
                        <?php else: ?>
                        <input type="file" name="foto[]" class="form-control" accept="image/*" multiple required>
                        <?php endif; ?>
                        <div class="form-text">Mendukung format JPG, PNG, WebP (bisa pilih beberapa foto sekaligus). Gambar otomatis dikonversi ke WebP kualitas HD.</div>

                        <?php if (!empty($data['foto'])): ?>
                        <div class="mt-3 p-2 border rounded d-inline-block bg-light">
                            <div class="small text-muted mb-1">Foto saat ini:</div>
                            <img src="<?= SITE_URL ?>/uploads/<?= e($data['foto']) ?>" alt="Preview" style="max-height:160px;border-radius:6px;object-fit:cover;">
                        </div>
                        <?php endif; ?>
                    </div>

                    <?php else: ?>
                    <!-- Form Kegiatan (Dokumen) -->
                    <div class="col-12">
                        <label class="form-label fw-bold small">Unggah File Dokumen Softfile <?= $is_edit ? '<span class="text-muted fw-normal">(Biarkan kosong jika tidak diganti)</span>' : '<span class="text-danger">*</span>' ?></label>
                        <input type="file" name="file_dokumen" id="fileDokumen" class="form-control" accept=".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx" <?= $is_edit ? '' : 'required' ?> onchange="autoFillJudul(this)">
                        <div class="form-text">Mendukung format PDF, Word (.doc, .docx), Excel (.xls, .xlsx). Maksimal 30MB.</div>

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

<script>
function autoFillJudul(input) {
    if (input.files && input.files[0]) {
        var filename = input.files[0].name;
        // Hapus ekstensi
        var nameWithoutExt = filename.replace(/\.[^/.]+$/, "");
        // Bersihkan underscore dan dash menjadi spasi
        var cleanTitle = nameWithoutExt.replace(/[_-]+/g, " ").trim();
        // Kapitalisasi kata pertama jika memungkinkan
        cleanTitle = cleanTitle.charAt(0).toUpperCase() + cleanTitle.slice(1);
        
        var judulInput = document.getElementById('inputJudul');
        if (judulInput && (judulInput.value.trim() === '' || confirm('Perbarui judul form dengan nama file "' + cleanTitle + '"?'))) {
            judulInput.value = cleanTitle;
        }
    }
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
