<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$is_edit = false;
$page    = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM pages WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $page = $stmt->fetch();
    if ($page) $is_edit = true;
}

$admin_page_title = $is_edit ? 'Edit Halaman: ' . ($page['judul'] ?? '') : 'Tambah Halaman Baru';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul          = trim($_POST['judul'] ?? '');
    $slug           = trim($_POST['slug'] ?? '');
    $kategori       = trim($_POST['kategori'] ?? 'Umum');
    $ringkasan      = trim($_POST['ringkasan'] ?? '');
    $konten         = trim($_POST['konten'] ?? '');
    $status         = in_array($_POST['status'] ?? '', ['publish', 'draft']) ? $_POST['status'] : 'publish';
    $show_in_nav    = isset($_POST['show_in_nav']) ? 1 : 0;
    $layout         = in_array($_POST['layout'] ?? '', ['default', 'fullwidth', 'card']) ? $_POST['layout'] : 'default';
    $urutan         = (int)($_POST['urutan'] ?? 0);
    $id             = (int)($_POST['id'] ?? 0);

    if (!$judul) {
        $error = 'Judul halaman wajib diisi.';
    } else {
        if (!$slug) {
            $slug = makeSlug($judul);
        } else {
            $slug = makeSlug($slug);
        }

        // Check unique slug
        $check = $db->prepare("SELECT id FROM pages WHERE slug = ? AND id != ?");
        $check->execute([$slug, $id]);
        if ($check->fetch()) {
            $error = "Slug URL '{$slug}' sudah digunakan oleh halaman lain. Gunakan judul atau slug yang berbeda.";
        } else {
            $featured_image = $is_edit ? ($page['featured_image'] ?? '') : '';

            // Handle image upload jika ada (Support Cropped Base64 & Direct File Upload)
            $cropped_image = trim($_POST['cropped_image_data'] ?? '');
            if (!empty($cropped_image) && preg_match('/^data:image\/(\w+);base64,/', $cropped_image)) {
                $raw = base64_decode(substr($cropped_image, strpos($cropped_image, ',') + 1));
                if ($raw !== false) {
                    $fname = 'page_' . time() . '_' . rand(100,999) . '.webp';
                    $dest  = UPLOAD_PATH . $fname;
                    if (file_put_contents($dest, $raw)) {
                        if ($is_edit && !empty($page['featured_image']) && $page['featured_image'] !== $fname) {
                            deleteUploadedFile($page['featured_image']);
                        }
                        $featured_image = $fname;
                    } else {
                        $error = 'Gagal menyimpan gambar halaman hasil penyesuaian.';
                    }
                }
            } elseif (!empty($_FILES['featured_image_file']['name'])) {
                $allowed = ['jpg','jpeg','png','webp','gif'];
                $ext = strtolower(pathinfo($_FILES['featured_image_file']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $error = 'Format gambar tidak valid. Gunakan format JPG, PNG, atau WebP.';
                } else {
                    $saved_name = convertAndSaveWebP($_FILES['featured_image_file']['tmp_name'], UPLOAD_PATH, 'page_');
                    if ($saved_name) {
                        if ($is_edit && !empty($page['featured_image']) && $page['featured_image'] !== $saved_name) {
                            deleteUploadedFile($page['featured_image']);
                        }
                        $featured_image = $saved_name;
                    } else {
                        $fname = 'page_' . time() . '_' . rand(100,999) . '.' . $ext;
                        $dest  = UPLOAD_PATH . $fname;
                        if (move_uploaded_file($_FILES['featured_image_file']['tmp_name'], $dest)) {
                            if ($is_edit && !empty($page['featured_image']) && $page['featured_image'] !== $fname) {
                                deleteUploadedFile($page['featured_image']);
                            }
                            $featured_image = $fname;
                        }
                    }
                }
            } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
                if ($is_edit && !empty($page['featured_image'])) {
                    deleteUploadedFile($page['featured_image']);
                }
                $featured_image = null;
            }

            if (!$error) {
                if ($is_edit && $id) {
                    $stmt = $db->prepare("UPDATE pages SET judul=?, slug=?, kategori=?, ringkasan=?, konten=?, status=?, show_in_nav=?, layout=?, featured_image=?, urutan=? WHERE id=?");
                    $stmt->execute([$judul, $slug, $kategori, $ringkasan, $konten, $status, $show_in_nav, $layout, $featured_image, $urutan, $id]);
                    $_SESSION['flash'] = 'Halaman "' . $judul . '" berhasil diperbarui.';
                } else {
                    $stmt = $db->prepare("INSERT INTO pages (judul, slug, kategori, ringkasan, konten, status, show_in_nav, layout, featured_image, urutan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$judul, $slug, $kategori, $ringkasan, $konten, $status, $show_in_nav, $layout, $featured_image, $urutan]);
                    $_SESSION['flash'] = 'Halaman baru "' . $judul . '" berhasil dibuat dan disimpan.';
                }
                redirect(SITE_URL . '/admin/page-list.php');
            }
        }
    }
}

// Tambahkan TinyMCE CDN script
$extra_css = '
<script src="https://cdn.tiny.cloud/1/no-api-key/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    tinymce.init({
        selector: "#konten_editor",
        height: 550,
        menubar: true,
        plugins: [
            "advlist", "autolink", "lists", "link", "image", "charmap", "preview",
            "anchor", "searchreplace", "visualblocks", "code", "fullscreen",
            "insertdatetime", "media", "table", "help", "wordcount"
        ],
        toolbar: "undo redo | blocks fontfamily fontsize | " +
            "bold italic underline forecolor backcolor | alignleft aligncenter " +
            "alignright alignjustify | bullist numlist outdent indent | " +
            "table link image media | removeformat | code fullscreen preview",
        content_style: "body { font-family:Inter,sans-serif; font-size:15px; color:#1f2937; line-height:1.75; }",
        branding: false
    });
});
</script>
';

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-11">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="page-list.php" style="color:var(--text-muted);">Kelola Halaman</a>
            <span>/</span>
            <span style="color:var(--navy);"><?= $admin_page_title ?></span>
        </div>

        <?php if ($error): ?>
        <div class="alert-lpm alert-danger mb-4 d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <div><?= e($error) ?></div>
        </div>
        <?php endif; ?>

        <form method="POST" enctype="multipart/form-data">
            <?php if ($is_edit): ?>
            <input type="hidden" name="id" value="<?= $page['id'] ?>">
            <?php endif; ?>

            <div class="row g-4">
                <!-- Kolom Kiri: Konten Utama Editor -->
                <div class="col-lg-8">
                    <div class="card-lpm mb-4" style="background:#fff;border:1px solid var(--border);border-radius:14px;padding:1.75rem;box-shadow:var(--shadow-sm);">
                        <div class="mb-3">
                            <label class="form-label" style="font-weight:700;color:var(--navy);font-size:1rem;">
                                Judul Halaman <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="judul" class="form-control form-control-lg" style="border:1.5px solid var(--border);font-weight:700;" placeholder="Contoh: Standar Operasional Prosedur SPMI" value="<?= e($is_edit ? $page['judul'] : ($_POST['judul'] ?? '')) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;color:var(--navy);font-size:0.875rem;">
                                Tautan Ramah / Slug URL
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f8fafc;font-size:0.8rem;border:1.5px solid var(--border);border-right:0;color:var(--text-muted);">
                                    <?= SITE_URL ?>/page.php?slug=
                                </span>
                                <input type="text" name="slug" class="form-control" style="border:1.5px solid var(--border);border-left:0;padding:0.6rem 0.85rem;" placeholder="sop-spmi" value="<?= e($is_edit ? $page['slug'] : ($_POST['slug'] ?? '')) ?>">
                            </div>
                            <small class="text-muted">Biarkan kosong jika ingin slug dibuat otomatis dari judul halaman.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;color:var(--navy);font-size:0.875rem;">
                                Ringkasan Singkat / Cuplikan Meta (Excerpt)
                            </label>
                            <textarea name="ringkasan" class="form-control" rows="2" style="border:1.5px solid var(--border);padding:0.65rem 0.85rem;" placeholder="Ringkasan 1-2 kalimat tentang halaman ini untuk pencarian dan media sosial..."><?= e($is_edit ? ($page['ringkasan'] ?? '') : ($_POST['ringkasan'] ?? '')) ?></textarea>
                        </div>

                        <div class="mb-3">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label mb-0" style="font-weight:700;color:var(--navy);font-size:0.95rem;">
                                    Isi Konten Materi Halaman (Rich Text Editor)
                                </label>
                                <span class="badge bg-light text-dark" style="border:1px solid var(--border);font-size:0.72rem;">WYSIWYG Editor</span>
                            </div>
                            <textarea id="konten_editor" name="konten" class="form-control" rows="18"><?= htmlspecialchars($is_edit ? $page['konten'] : ($_POST['konten'] ?? ''), ENT_QUOTES) ?></textarea>
                            <small class="text-muted d-block mt-2">
                                💡 Anda dapat menempelkan (<em>paste</em>) tulisan langsung dari dokumen Word, Google Docs, atau menambahkan gambar, tabel, dan tautan dokumen.
                            </small>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Atribut Halaman ala WordPress (Sidebar) -->
                <div class="col-lg-4">
                    <!-- Kotak 1: Status Publikasi & Aksi -->
                    <div class="card-lpm mb-4" style="background:#fff;border:1px solid var(--border);border-radius:14px;padding:1.5rem;box-shadow:var(--shadow-sm);">
                        <h6 style="font-weight:800;color:var(--navy);margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:1.5px solid var(--border);display:flex;align-items:center;gap:6px;">
                            <i class="bi bi-send-check text-primary"></i> Status Publikasi
                        </h6>

                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;font-size:0.85rem;">Status</label>
                            <?php $curr_status = $is_edit ? ($page['status'] ?? 'publish') : ($_POST['status'] ?? 'publish'); ?>
                            <select name="status" class="form-select" style="border:1.5px solid var(--border);font-weight:600;">
                                <option value="publish" <?= $curr_status === 'publish' ? 'selected' : '' ?>>🟢 Diterbitkan (Published / Publik)</option>
                                <option value="draft" <?= $curr_status === 'draft' ? 'selected' : '' ?>>🟡 Simpan sebagai Draf (Draft)</option>
                            </select>
                            <small class="text-muted">Halaman draf hanya dapat dilihat oleh admin.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" style="font-weight:600;font-size:0.85rem;">Urutan Menu (Nomor)</label>
                            <input type="number" name="urutan" class="form-control" style="border:1.5px solid var(--border);" value="<?= (int)($is_edit ? ($page['urutan'] ?? 0) : ($_POST['urutan'] ?? 0)) ?>">
                            <small class="text-muted">Urutan posisi saat ditampilkan (0 = paling awal).</small>
                        </div>

                        <div class="d-grid gap-2 pt-2 border-top">
                            <button type="submit" class="btn-submit w-100 justify-content-center py-2" style="font-size:0.95rem;">
                                <i class="bi bi-save2-fill me-1"></i> <?= $is_edit ? 'Simpan Perubahan' : 'Terbitkan Halaman' ?>
                            </button>
                            <?php if ($is_edit): ?>
                            <a href="<?= SITE_URL ?>/page.php?slug=<?= e($page['slug']) ?>" target="_blank" class="btn btn-outline-secondary btn-sm" style="border-radius:8px;font-weight:600;">
                                <i class="bi bi-eye me-1"></i> Pratinjau Halaman Live ↗
                            </a>
                            <?php endif; ?>
                            <a href="page-list.php" class="btn btn-light btn-sm text-muted" style="border-radius:8px;">
                                Kembali ke Daftar Halaman
                            </a>
                        </div>
                    </div>

                    <!-- Kotak 2: Pengaturan Menu & Navigasi -->
                    <div class="card-lpm mb-4" style="background:#fff;border:1px solid var(--border);border-radius:14px;padding:1.5rem;box-shadow:var(--shadow-sm);">
                        <h6 style="font-weight:800;color:var(--navy);margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:1.5px solid var(--border);display:flex;align-items:center;gap:6px;">
                            <i class="bi bi-menu-button-wide text-primary"></i> Penempatan Menu &amp; Layout
                        </h6>

                        <!-- Tampilkan di Navbar -->
                        <?php $show_nav = $is_edit ? (int)($page['show_in_nav'] ?? 0) : (isset($_POST['show_in_nav']) ? 1 : 0); ?>
                        <div class="form-check form-switch mb-3 p-0 ps-5" style="min-height:auto;">
                            <input class="form-check-input ms-n5" type="checkbox" role="switch" id="show_in_nav" name="show_in_nav" value="1" <?= $show_nav ? 'checked' : '' ?> style="width:2.5em;height:1.3em;">
                            <label class="form-check-label" for="show_in_nav" style="font-weight:700;color:var(--navy);font-size:0.88rem;cursor:pointer;">
                                Sematkan di Navbar Website
                            </label>
                            <small class="text-muted d-block mt-1">Otomatis tampil pada navigasi depan website (hanya saat status halaman Terbit/Live).</small>
                        </div>

                        <!-- Kategori -->
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <label class="form-label mb-0" style="font-weight:600;font-size:0.85rem;">Kategori Halaman</label>
                                <a href="kategori-page.php" target="_blank" class="small text-decoration-none text-primary fw-semibold" style="font-size:0.75rem;">
                                    + Kelola Kategori
                                </a>
                            </div>
                            <select name="kategori" class="form-select" style="border:1.5px solid var(--border);">
                                <?php
                                try {
                                    $kats = $db->query("SELECT nama_kategori FROM kategori_page ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
                                } catch (Exception $e) {
                                    $kats = [];
                                }
                                if (empty($kats)) {
                                    $kats = ['Profil', 'SPMI', 'AMI', 'Akreditasi', 'Mutu & Data', 'Dokumen', 'Knowledge Center', 'Layanan', 'Umum'];
                                }
                                $curr_selected_cat = $is_edit ? ($page['kategori'] ?? 'Umum') : ($_POST['kategori'] ?? 'Umum');
                                if (!in_array($curr_selected_cat, $kats) && !empty($curr_selected_cat)) {
                                    $kats[] = $curr_selected_cat;
                                }
                                foreach ($kats as $k):
                                ?>
                                <option value="<?= htmlspecialchars($k) ?>" <?= $curr_selected_cat === $k ? 'selected' : '' ?>><?= htmlspecialchars($k) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- Template Layout -->
                        <div class="mb-2">
                            <label class="form-label" style="font-weight:600;font-size:0.85rem;">Template Layout</label>
                            <?php $curr_layout = $is_edit ? ($page['layout'] ?? 'default') : ($_POST['layout'] ?? 'default'); ?>
                            <select name="layout" class="form-select" style="border:1.5px solid var(--border);">
                                <option value="default" <?= $curr_layout === 'default' ? 'selected' : '' ?>>Standar (Dengan Sidebar Kategori)</option>
                                <option value="fullwidth" <?= $curr_layout === 'fullwidth' ? 'selected' : '' ?>>Full Width (Lebar Penuh Tanpa Sidebar)</option>
                                <option value="card" <?= $curr_layout === 'card' ? 'selected' : '' ?>>Card Boxed (Tampilan Kotak Bersih)</option>
                            </select>
                            <small class="text-muted">Gunakan Full Width untuk dokumen bertabel lebar.</small>
                        </div>
                    </div>

                    <!-- Kotak 3: Gambar Unggulan / Featured Image -->
                    <div class="card-lpm mb-4" style="background:#fff;border:1px solid var(--border);border-radius:14px;padding:1.5rem;box-shadow:var(--shadow-sm);">
                        <h6 style="font-weight:800;color:var(--navy);margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:1.5px solid var(--border);display:flex;align-items:center;gap:6px;">
                            <i class="bi bi-image text-primary"></i> Gambar Unggulan (Thumbnail)
                        </h6>

                        <?php if ($is_edit && !empty($page['featured_image'])): ?>
                        <div class="mb-3 text-center">
                            <img id="existingPageImg" src="<?= UPLOAD_URL . e($page['featured_image']) ?>" alt="Featured Image" class="img-fluid rounded-3 border" style="max-height:160px;object-fit:cover;width:100%;">
                            <div class="d-flex align-items-center justify-content-between mt-2">
                                <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:0.75rem;" onclick="editExistingPageImg('<?= UPLOAD_URL . e($page['featured_image']) ?>')">
                                    <i class="bi bi-crop text-warning me-1"></i> Edit / Crop
                                </button>
                                <div class="form-check text-start m-0">
                                    <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeImg">
                                    <label class="form-check-label text-danger" for="removeImg" style="font-size:0.8rem;cursor:pointer;">
                                        Hapus gambar ini
                                    </label>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="mb-2">
                            <label class="form-label" style="font-weight:600;font-size:0.85rem;">Unggah Gambar Baru</label>
                            <div class="d-flex gap-2 align-items-center">
                                <input type="file" id="pageImgInput" name="featured_image_file" class="form-control" accept="image/*" style="border:1.5px solid var(--border);font-size:0.85rem;">
                                <button type="button" class="btn btn-dark btn-sm d-none align-items-center gap-1" id="btnEditPageImg" style="white-space:nowrap;padding:0.55rem 0.85rem;border-radius:8px;">
                                    <i class="bi bi-crop text-warning"></i> Crop
                                </button>
                            </div>
                            <input type="hidden" name="cropped_image_data" id="croppedImageDataPage">
                            <div class="form-text mt-1">
                                <span class="badge bg-light text-dark border me-1"><i class="bi bi-hdd-fill text-warning me-1"></i>Batas Ukuran: Maksimal 5 MB</span> Format: JPG, PNG, atau WebP (Opsional).
                            </div>
                            <!-- Live Preview Gambar Baru -->
                            <div id="livePreviewPageWrap" class="mt-2 d-none">
                                <div class="p-2 bg-light rounded border text-center">
                                    <img id="livePreviewPageImg" src="" alt="Preview" style="max-height:130px;width:100%;object-fit:cover;border-radius:6px;">
                                    <div class="small fw-bold text-success mt-1"><i class="bi bi-check-circle-fill me-1"></i>Gambar siap disimpan</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- Modal Cropper Universal Page -->
<div class="modal fade" id="modalCropPage" tabindex="-1" aria-labelledby="modalCropPageLabel" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
            <div class="modal-header text-white" style="background:linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);padding:1.25rem 1.75rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-crop" style="font-size:1.4rem;color:#FFD54F;"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="modalCropPageLabel">Editor Gambar Halaman: Crop, Rotate, Flip &amp; Zoom</h5>
                        <small style="color:rgba(255,255,255,0.85);">Sesuaikan framing gambar sampul halaman web.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" style="background:#F1F5F9;">
                <div class="crop-modal-container mb-3 shadow-inner" style="max-height:480px;background:#0F172A;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:10px;">
                    <img id="cropperSourceImgPage" src="" alt="Source" style="max-width:100%;max-height:460px;display:block;">
                </div>
                <div class="card p-3 border shadow-sm rounded-3 bg-white">
                    <div class="row g-2 align-items-center">
                        <div class="col-lg-4 col-md-6 col-12 d-flex gap-2">
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipHPage">
                                <i class="bi bi-symmetry-vertical text-warning"></i> Flip H (⇄)
                            </button>
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipVPage">
                                <i class="bi bi-symmetry-horizontal text-warning"></i> Flip V (⇅)
                            </button>
                        </div>
                        <div class="col-lg-3 col-md-6 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateLeftPage">
                                <i class="bi bi-arrow-counterclockwise"></i> ↺ 90°
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateRightPage">
                                <i class="bi bi-arrow-clockwise"></i> ↻ 90°
                            </button>
                        </div>
                        <div class="col-lg-5 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomInPage">
                                <i class="bi bi-zoom-in"></i> +
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomOutPage">
                                <i class="bi bi-zoom-out"></i> -
                            </button>
                            <button type="button" class="btn btn-outline-danger flex-fill fw-bold py-2" id="btnResetCropPage">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top flex-wrap">
                        <span class="text-muted small fw-bold me-1 text-uppercase">Rasio:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active ratio-btn-page fw-semibold" data-ratio="1.777778">16:9 Banner</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-page fw-semibold" data-ratio="1.333333">4:3 Galeri</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-page fw-semibold" data-ratio="1">1:1 Persegi</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-page fw-semibold" data-ratio="NaN">Bebas</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between" style="background:#ffffff;border-top:1px solid #E2E8F0;padding:1rem 1.75rem;">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius:8px;">Batal</button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-bold d-inline-flex align-items-center gap-2" id="btnApplyCropPage" style="background:var(--navy);border-color:var(--navy);border-radius:8px;">
                    <i class="bi bi-check-lg fs-5"></i> Terapkan Hasil Penyesuaian
                </button>
            </div>
        </div>
    </div>
</div>

<script>
let cropperPage = null;
let scaleXPage = 1;
let scaleYPage = 1;
let currentRatioPage = 16 / 9;
let isExistingPageEdit = false;

const modalCropPageEl  = document.getElementById('modalCropPage');
const bsModalPage      = new bootstrap.Modal(modalCropPageEl);
const sourceImgPage    = document.getElementById('cropperSourceImgPage');
const fileInputPage    = document.getElementById('pageImgInput');
const btnEditPage      = document.getElementById('btnEditPageImg');
const livePreviewWrapPage = document.getElementById('livePreviewPageWrap');
const livePreviewImgPage  = document.getElementById('livePreviewPageImg');
const hiddenCropPage   = document.getElementById('croppedImageDataPage');
const applyBtnPage     = document.getElementById('btnApplyCropPage');

function initCropperPage() {
    if (cropperPage) cropperPage.destroy();
    scaleXPage = 1;
    scaleYPage = 1;
    cropperPage = new Cropper(sourceImgPage, {
        aspectRatio: currentRatioPage,
        viewMode: 1,
        autoCropArea: 0.95,
        responsive: true,
        guides: true,
        checkCrossOrigin: false
    });
}

modalCropPageEl.addEventListener('shown.bs.modal', function() { initCropperPage(); });
modalCropPageEl.addEventListener('hidden.bs.modal', function() {
    if (cropperPage) {
        cropperPage.destroy();
        cropperPage = null;
    }
});

// Controls
document.getElementById('btnRotateLeftPage')?.addEventListener('click', function() { if (cropperPage) cropperPage.rotate(-90); });
document.getElementById('btnRotateRightPage')?.addEventListener('click', function() { if (cropperPage) cropperPage.rotate(90); });
document.getElementById('btnFlipHPage')?.addEventListener('click', function() { if (cropperPage) { scaleXPage = -scaleXPage; cropperPage.scaleX(scaleXPage); } });
document.getElementById('btnFlipVPage')?.addEventListener('click', function() { if (cropperPage) { scaleYPage = -scaleYPage; cropperPage.scaleY(scaleYPage); } });
document.getElementById('btnZoomInPage')?.addEventListener('click', function() { if (cropperPage) cropperPage.zoom(0.1); });
document.getElementById('btnZoomOutPage')?.addEventListener('click', function() { if (cropperPage) cropperPage.zoom(-0.1); });
document.getElementById('btnResetCropPage')?.addEventListener('click', function() { if (cropperPage) { cropperPage.reset(); scaleXPage = 1; scaleYPage = 1; } });

document.querySelectorAll('.ratio-btn-page').forEach(function(btn) {
    btn.addEventListener('click', function() {
        document.querySelectorAll('.ratio-btn-page').forEach(function(b) { b.classList.remove('active'); });
        this.classList.add('active');
        const val = parseFloat(this.getAttribute('data-ratio'));
        currentRatioPage = isNaN(val) ? NaN : val;
        if (cropperPage) cropperPage.setAspectRatio(currentRatioPage);
    });
});

fileInputPage?.addEventListener('change', function() {
    if (this.files && this.files[0]) {
        const file = this.files[0];
        if (file.type.startsWith('image/')) {
            const reader = new FileReader();
            reader.onload = function(e) {
                isExistingPageEdit = false;
                sourceImgPage.src = e.target.result;
                livePreviewImgPage.src = e.target.result;
                livePreviewWrapPage.classList.remove('d-none');
                btnEditPage.classList.remove('d-none');
                btnEditPage.classList.add('d-inline-flex');
                hiddenCropPage.value = '';
                bsModalPage.show();
            };
            reader.readAsDataURL(file);
        }
    }
});

btnEditPage?.addEventListener('click', function() {
    if (sourceImgPage.src) {
        bsModalPage.show();
    }
});

window.editExistingPageImg = function(imgUrl) {
    isExistingPageEdit = true;
    sourceImgPage.src = imgUrl;
    bsModalPage.show();
};

applyBtnPage?.addEventListener('click', function() {
    if (!cropperPage) return;
    const canvas = cropperPage.getCroppedCanvas({
        maxWidth: 1920,
        maxHeight: 1200,
        imageSmoothingQuality: 'high'
    });
    if (canvas) {
        const dataUrl = canvas.toDataURL('image/webp', 0.88);
        hiddenCropPage.value = dataUrl;
        livePreviewImgPage.src = dataUrl;
        livePreviewWrapPage.classList.remove('d-none');
        btnEditPage.classList.remove('d-none');
        btnEditPage.classList.add('d-inline-flex');

        const existingImg = document.getElementById('existingPageImg');
        if (existingImg && isExistingPageEdit) {
            existingImg.src = dataUrl;
        }

        bsModalPage.hide();
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
