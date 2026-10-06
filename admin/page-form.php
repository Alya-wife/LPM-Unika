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

            // Handle image upload jika ada
            if (!empty($_FILES['featured_image_file']['name'])) {
                $allowed = ['jpg','jpeg','png','webp','gif'];
                $ext = strtolower(pathinfo($_FILES['featured_image_file']['name'], PATHINFO_EXTENSION));
                if (!in_array($ext, $allowed)) {
                    $error = 'Format gambar tidak valid. Gunakan format JPG, PNG, atau WebP.';
                } else {
                    $fname = 'page_' . time() . '_' . rand(100,999) . '.' . $ext;
                    $dest  = UPLOAD_PATH . $fname;
                    if (move_uploaded_file($_FILES['featured_image_file']['tmp_name'], $dest)) {
                        $featured_image = $fname;
                    }
                }
            } elseif (isset($_POST['remove_image']) && $_POST['remove_image'] == '1') {
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
                            <small class="text-muted d-block mt-1">Otomatis muncul pada menu dropdown "Halaman" di navbar depan website.</small>
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
                            <img src="<?= UPLOAD_URL . e($page['featured_image']) ?>" alt="Featured Image" class="img-fluid rounded-3 border" style="max-height:160px;object-fit:cover;width:100%;">
                            <div class="form-check mt-2 text-start">
                                <input class="form-check-input" type="checkbox" name="remove_image" value="1" id="removeImg">
                                <label class="form-check-label text-danger" for="removeImg" style="font-size:0.8rem;cursor:pointer;">
                                    Hapus gambar ini
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>

                        <div class="mb-2">
                            <label class="form-label" style="font-weight:600;font-size:0.85rem;">Unggah Gambar Baru</label>
                            <input type="file" name="featured_image_file" class="form-control" accept="image/*" style="border:1.5px solid var(--border);font-size:0.85rem;">
                            <div class="form-text mt-1">
                                <span class="badge bg-light text-dark border me-1"><i class="bi bi-hdd-fill text-warning me-1"></i>Batas Ukuran: Maksimal 5 MB</span> Format: JPG, PNG, atau WebP (Opsional).
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
