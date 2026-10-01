<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$is_edit = false;
$akr     = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM akreditasi WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $akr = $stmt->fetch();
    if ($akr) $is_edit = true;
}

$admin_page_title = $is_edit ? 'Edit Capaian Akreditasi' : 'Tambah Capaian Akreditasi';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $lembaga    = trim($_POST['lembaga'] ?? '');
    $peringkat  = trim($_POST['peringkat'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $warna      = trim($_POST['warna'] ?? '#0A192F');
    $urutan     = (int)($_POST['urutan'] ?? 1);
    $id         = (int)($_POST['id'] ?? 0);
    $link_url   = trim($_POST['link_url'] ?? '');
    $file_path  = $akr['file_path'] ?? '';

    // Handle file upload
    if (isset($_FILES['file_sertifikat']) && $_FILES['file_sertifikat']['error'] === UPLOAD_ERR_OK) {
        $upload_dir = __DIR__ . '/../uploads/penghargaan/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0777, true);
        }
        $tmp_name = $_FILES['file_sertifikat']['tmp_name'];
        $name     = $_FILES['file_sertifikat']['name'];
        $ext      = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        $allowed  = ['jpg', 'jpeg', 'png', 'webp', 'pdf'];
        if (in_array($ext, $allowed)) {
            $new_name = saveOrConvertToPdf($tmp_name, $upload_dir, 'akred_', $name);
            if ($new_name) {
                if ($file_path && file_exists($upload_dir . $file_path)) {
                    @unlink($upload_dir . $file_path);
                    $old_thumb = $upload_dir . pathinfo($file_path, PATHINFO_FILENAME) . '.webp';
                    if (file_exists($old_thumb)) @unlink($old_thumb);
                }
                $file_path = $new_name;
                getOrGeneratePdfPreview($new_name, 'penghargaan');
            }
        }
    }

    if (!$lembaga || !$peringkat) {
        $error = 'Nama lembaga dan peringkat akreditasi wajib diisi.';
    } else {
        if ($is_edit && $id) {
            $stmt = $db->prepare("UPDATE akreditasi SET lembaga=?, peringkat=?, keterangan=?, warna=?, urutan=?, file_path=?, link_url=? WHERE id=?");
            $stmt->execute([$lembaga, $peringkat, $keterangan, $warna, $urutan, $file_path, $link_url ?: null, $id]);
            $_SESSION['flash'] = 'Data akreditasi berhasil diperbarui.';
        } else {
            $stmt = $db->prepare("INSERT INTO akreditasi (lembaga, peringkat, keterangan, warna, urutan, file_path, link_url) VALUES (?,?,?,?,?,?,?)");
            $stmt->execute([$lembaga, $peringkat, $keterangan, $warna, $urutan, $file_path, $link_url ?: null]);
            $_SESSION['flash'] = 'Data akreditasi berhasil ditambahkan.';
        }
        redirect(SITE_URL . '/admin/akreditasi-list.php');
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-7">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="akreditasi-list.php" style="color:var(--text-muted);">Akreditasi</a>
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
                <a href="akreditasi-list.php" class="btn-action" style="background:var(--bg-main);color:var(--text-muted);border:1.5px solid var(--border);text-decoration:none;">
                    Kembali
                </a>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?= $akr['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Lembaga / Badan Akreditasi <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="lembaga" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: BAN-PT Institusi / AQAS" value="<?= e($is_edit ? $akr['lembaga'] : ($_POST['lembaga'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Peringkat / Status <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="peringkat" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: A / Unggul / Internasional" value="<?= e($is_edit ? $akr['peringkat'] : ($_POST['peringkat'] ?? '')) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Keterangan Lengkap
                            </label>
                            <textarea name="keterangan" class="form-control" rows="3" style="border:1.5px solid var(--border);padding:0.75rem 1rem;" placeholder="Contoh: Akreditasi Unggul (A) dari Badan Akreditasi Nasional Perguruan Tinggi"><?= e($is_edit ? $akr['keterangan'] : ($_POST['keterangan'] ?? '')) ?></textarea>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Warna Aksen Peringkat
                            </label>
                            <div style="display:flex;align-items:center;gap:10px;">
                                <input type="color" name="warna" id="warna_picker" class="form-control form-control-color" style="width:50px;height:45px;padding:3px;" value="<?= e($is_edit ? $akr['warna'] : ($_POST['warna'] ?? '#0A192F')) ?>">
                                <input type="text" id="warna_text" class="form-control" style="border:1.5px solid var(--border);padding:0.65rem 1rem;" value="<?= e($is_edit ? $akr['warna'] : ($_POST['warna'] ?? '#0A192F')) ?>" readonly>
                            </div>
                            <small class="text-muted">Pilih warna teks angka/status agar menarik secara visual.</small>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Urutan Tampil
                            </label>
                            <input type="number" name="urutan" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" min="1" value="<?= (int)($is_edit ? $akr['urutan'] : ($_POST['urutan'] ?? 1)) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Tautan Sumber / Link URL (Opsional)
                            </label>
                            <p class="text-muted small mb-2">Jika data bersumber dari situs eksternal (contoh: tautan EduRank atau UI GreenMetric), cantumkan URL lengkap di sini.</p>
                            <input type="url" name="link_url" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="https://edurank.org/..." value="<?= e($is_edit ? ($akr['link_url'] ?? '') : ($_POST['link_url'] ?? '')) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Upload File Sertifikat / Piagam (Opsional) <span class="badge bg-light text-primary border ms-1" style="font-size:0.75rem;">Otomatis PDF</span>
                            </label>
                            <p class="text-muted small mb-2">Format: PDF atau Gambar (JPG, PNG, WebP). Jika berupa gambar, otomatis dikonversi menjadi dokumen PDF.</p>
                            <input type="file" name="file_sertifikat" id="file_sertifikat_input" class="form-control" accept=".jpg,.jpeg,.png,.webp,.pdf" style="border:1.5px solid var(--border);padding:0.6rem 1rem;">
                            
                            <div id="preview_cert_box" class="mt-2 p-2 bg-light rounded d-none" style="border: 2px dashed var(--purple);">
                                <small class="fw-bold text-navy d-block mb-1">Pratinjau Berkas Baru:</small>
                                <div id="preview_cert_content"></div>
                            </div>

                            <?php if (!empty($akr['file_path'])): ?>
                            <?php $akr_preview = getOrGeneratePdfPreview($akr['file_path'], 'penghargaan'); ?>
                            <div class="mt-2 p-3 bg-light rounded d-flex align-items-center gap-3 border">
                                <?php if ($akr_preview): ?>
                                    <a href="<?= SITE_URL ?>/uploads/penghargaan/<?= e($akr['file_path']) ?>" target="_blank">
                                        <img src="<?= SITE_URL ?>/uploads/penghargaan/<?= e($akr_preview) ?>" alt="Sertifikat" class="img-thumbnail" style="max-height:120px;max-width:180px;object-fit:contain;">
                                    </a>
                                <?php endif; ?>
                                <div>
                                    <span class="small fw-bold text-navy d-block mb-1"><i class="bi bi-file-earmark-check me-1"></i> File Sertifikat Tersimpan:</span>
                                    <span class="small text-muted d-block mb-1"><?= e($akr['file_path']) ?></span>
                                    <a href="<?= SITE_URL ?>/uploads/penghargaan/<?= e($akr['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-success rounded-pill px-3" style="font-size:0.75rem;">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Berkas Asli
                                    </a>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12" style="padding-top:1rem;border-top:1px solid var(--border);margin-top:1rem;">
                            <div style="display:flex;gap:1rem;align-items:center;justify-content:flex-end;">
                                <a href="akreditasi-list.php" style="color:var(--text-muted);font-size:0.875rem;">Batal</a>
                                <button type="submit" class="btn-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M12 3v8.25m0 0-3-3m3 3 3-3" />
                                    </svg>
                                    <?= $is_edit ? 'Simpan Perubahan' : 'Tambahkan Akreditasi' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- PDF.js library for instant client-side rendering of PDF pages -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
<script>
if (typeof pdfjsLib !== 'undefined') {
    pdfjsLib.GlobalWorkerOptions.workerSrc = 'https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.worker.min.js';
}

document.getElementById('warna_picker').addEventListener('input', function() {
    document.getElementById('warna_text').value = this.value;
});

var fileInput = document.getElementById('file_sertifikat_input');
if (fileInput) {
    fileInput.addEventListener('change', function() {
        var file = this.files[0];
        if (!file) return;
        var box = document.getElementById('preview_cert_box');
        var content = document.getElementById('preview_cert_content');
        content.innerHTML = '';
        box.classList.remove('d-none');
        
        if (file.type === 'application/pdf' || file.name.toLowerCase().endsWith('.pdf')) {
            content.innerHTML = '<div class="text-muted small py-2"><div class="spinner-border spinner-border-sm text-purple me-1"></div> Merender pratinjau halaman PDF...</div>';
            var fileReader = new FileReader();
            fileReader.onload = function() {
                var typedarray = new Uint8Array(this.result);
                pdfjsLib.getDocument(typedarray).promise.then(function(pdf) {
                    pdf.getPage(1).then(function(page) {
                        var viewport = page.getViewport({scale: 1.0});
                        var canvas = document.createElement('canvas');
                        canvas.className = 'img-thumbnail shadow-sm';
                        canvas.style.maxHeight = '140px';
                        canvas.style.maxWidth = '200px';
                        canvas.style.objectFit = 'contain';
                        var context = canvas.getContext('2d');
                        canvas.height = viewport.height;
                        canvas.width = viewport.width;
                        page.render({canvasContext: context, viewport: viewport}).promise.then(function() {
                            content.innerHTML = '';
                            var wrap = document.createElement('div');
                            wrap.className = 'd-flex align-items-center gap-2';
                            wrap.appendChild(canvas);
                            var info = document.createElement('div');
                            info.innerHTML = '<span class="badge bg-danger mb-1"><i class="bi bi-file-earmark-pdf"></i> PDF Siap</span><div class="small text-muted fw-bold">' + file.name + '</div>';
                            wrap.appendChild(info);
                            content.appendChild(wrap);
                        });
                    });
                }).catch(function(err) {
                    content.innerHTML = '<span class="text-danger small">Gagal merender preview: ' + err.message + '</span>';
                });
            };
            fileReader.readAsArrayBuffer(file);
        } else if (file.type.startsWith('image/')) {
            var reader = new FileReader();
            reader.onload = function(evt) {
                content.innerHTML = '<div class="d-flex align-items-center gap-2"><img src="' + evt.target.result + '" class="img-thumbnail" style="max-height:140px;max-width:200px;"><div class="small text-muted"><span class="badge bg-success mb-1">Gambar</span><div>' + file.name + '</div></div></div>';
            };
            reader.readAsDataURL(file);
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
