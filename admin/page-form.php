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

$admin_page_title = $is_edit ? 'Edit Halaman Dinamis' : 'Tambah Halaman Baru';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul     = trim($_POST['judul'] ?? '');
    $slug      = trim($_POST['slug'] ?? '');
    $kategori  = trim($_POST['kategori'] ?? 'Umum');
    $ringkasan = trim($_POST['ringkasan'] ?? '');
    $konten    = trim($_POST['konten'] ?? '');
    $id        = (int)($_POST['id'] ?? 0);

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
            $error = "Slug '{$slug}' sudah digunakan oleh halaman lain. Gunakan judul atau slug yang berbeda.";
        } else {
            if ($is_edit && $id) {
                $stmt = $db->prepare("UPDATE pages SET judul=?, slug=?, kategori=?, ringkasan=?, konten=? WHERE id=?");
                $stmt->execute([$judul, $slug, $kategori, $ringkasan, $konten, $id]);
                $_SESSION['flash'] = 'Halaman berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("INSERT INTO pages (judul, slug, kategori, ringkasan, konten) VALUES (?, ?, ?, ?, ?)");
                $stmt->execute([$judul, $slug, $kategori, $ringkasan, $konten]);
                $_SESSION['flash'] = 'Halaman baru berhasil disimpan.';
            }
            redirect(SITE_URL . '/admin/page-list.php');
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
        height: 500,
        menubar: true,
        plugins: [
            "advlist", "autolink", "lists", "link", "image", "charmap", "preview",
            "anchor", "searchreplace", "visualblocks", "code", "fullscreen",
            "insertdatetime", "media", "table", "help", "wordcount"
        ],
        toolbar: "undo redo | blocks | " +
            "bold italic backcolor | alignleft aligncenter " +
            "alignright alignjustify | bullist numlist outdent indent | " +
            "table link image | removeformat | code fullscreen help",
        content_style: "body { font-family:Inter,sans-serif; font-size:15px; color:#1f2937; line-height:1.75; }",
        branding: false
    });
});
</script>
';

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="page-list.php" style="color:var(--text-muted);">Kelola Halaman</a>
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


        <!-- Editor Tip -->
        <div class="p-3 mb-4" style="background:#EDE7F6;border:1px solid #D1C4E9;border-radius:var(--radius-md);">
            <div style="font-size:0.85rem;color:#4527A0;">
                💡 <strong>Tips Penggunaan Editor:</strong> Anda dapat mengetik langsung, atau salin-tempel (<em>copy-paste</em>) teks dari dokumen Word / file materi lainnya. Format paragraf, judul, tabel, dan gambar akan otomatis terjaga rapi oleh editor.
            </div>
        </div>

        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div class="admin-table-title"><?= $admin_page_title ?></div>
                <a href="page-list.php" class="btn-action" style="background:var(--bg-main);color:var(--text-muted);border:1.5px solid var(--border);text-decoration:none;">
                    Kembali
                </a>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?= $page['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Judul Halaman <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="judul" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Tugas & Fungsi LPM" value="<?= e($is_edit ? $page['judul'] : ($_POST['judul'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Kategori Halaman
                            </label>
                            <select name="kategori" class="form-select" style="border:1.5px solid var(--border);padding:0.7rem 1rem;">
                                <?php
                                $kats = ['Profil', 'SPMI', 'AMI', 'Akreditasi', 'Mutu & Data', 'Dokumen', 'Knowledge Center', 'Layanan', 'Umum'];
                                foreach ($kats as $k):
                                ?>
                                <option value="<?= $k ?>" <?= ($is_edit ? $page['kategori'] : ($_POST['kategori'] ?? 'Umum')) === $k ? 'selected' : '' ?>><?= $k ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Slug URL (Kosongkan untuk otomatis)
                            </label>
                            <div class="input-group">
                                <span class="input-group-text" style="background:#f1f5f9;font-size:0.8rem;border:1.5px solid var(--border);border-right:0;">
                                    <?= SITE_URL ?>/page.php?slug=
                                </span>
                                <input type="text" name="slug" class="form-control" style="border:1.5px solid var(--border);border-left:0;padding:0.7rem 1rem;" placeholder="tugas-fungsi" value="<?= e($is_edit ? $page['slug'] : ($_POST['slug'] ?? '')) ?>">
                            </div>
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Ringkasan Singkat (Meta Description)
                            </label>
                            <input type="text" name="ringkasan" class="form-control" style="border:1.5px solid var(--border);padding:0.65rem 1rem;" placeholder="Penjelasan 1 kalimat mengenai isi halaman ini..." value="<?= e($is_edit ? $page['ringkasan'] : ($_POST['ringkasan'] ?? '')) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Isi Konten Halaman (Rich Text Editor)
                            </label>
                            <textarea id="konten_editor" name="konten" class="form-control" rows="18"><?= htmlspecialchars($is_edit ? $page['konten'] : ($_POST['konten'] ?? ''), ENT_QUOTES) ?></textarea>
                            <small class="text-muted d-block mt-2">
                                💡 Jika isi konten dibiarkan kosong, website otomatis menampilkan UI <strong>Empty State</strong> dengan informasi bahwa materi sedang disiapkan oleh Admin.
                            </small>
                        </div>

                        <div class="col-12" style="padding-top:1rem;border-top:1px solid var(--border);margin-top:1rem;">
                            <div style="display:flex;gap:1rem;align-items:center;justify-content:flex-end;">
                                <a href="page-list.php" style="color:var(--text-muted);font-size:0.875rem;">Batal</a>
                                <button type="submit" class="btn-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M12 3v8.25m0 0-3-3m3 3 3-3" />
                                    </svg>
                                    <?= $is_edit ? 'Simpan Perubahan Halaman' : 'Publikasikan Halaman' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
