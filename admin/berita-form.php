<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

// Edit mode
$is_edit = false;
$berita  = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM berita WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $berita = $stmt->fetch();
    if ($berita) $is_edit = true;
}

$admin_page_title = $is_edit ? 'Edit Berita' : 'Tambah Berita';
$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul   = trim($_POST['judul'] ?? '');
    $tipe    = trim($_POST['tipe'] ?? 'Berita');
    $konten  = trim($_POST['konten'] ?? '');
    $tanggal = $_POST['tanggal_publikasi'] ?? '';
    $id      = (int)($_POST['id'] ?? 0);

    if (!$judul || !$konten) {
        $error = 'Judul dan konten wajib diisi.';
    } else {
        $slug = makeSlug($judul);
        // Ensure unique slug
        $check = $db->prepare("SELECT id FROM berita WHERE slug = ? AND id != ?");
        $check->execute([$slug, $id]);
        if ($check->fetch()) {
            $slug .= '-' . time();
        }

        // Handle file upload
        $gambar_filename = $is_edit ? $berita['gambar'] : null;
        if (!empty($_FILES['gambar']['name'])) {
            $allowed = ['jpg','jpeg','png','gif','webp'];
            $ext     = strtolower(pathinfo($_FILES['gambar']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Format file tidak didukung. Gunakan JPG, PNG, atau GIF.';
            } elseif ($_FILES['gambar']['size'] > 3 * 1024 * 1024) {
                $error = 'Ukuran file maksimal 3MB.';
            } else {
                $upload_dir = __DIR__ . '/../uploads/berita/';
                if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);
                $gambar_filename = uniqid('berita_') . '.' . $ext;
                move_uploaded_file($_FILES['gambar']['tmp_name'], $upload_dir . $gambar_filename);
            }
        }

        if (!$error) {
            if ($is_edit && $id) {
                $stmt = $db->prepare("UPDATE berita SET judul=?, tipe=?, slug=?, konten=?, gambar=?, tanggal_publikasi=? WHERE id=?");
                $stmt->execute([$judul, $tipe, $slug, $konten, $gambar_filename, $tanggal ?: null, $id]);
                $_SESSION['flash'] = 'Berita/kegiatan berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("INSERT INTO berita (judul, tipe, slug, konten, gambar, tanggal_publikasi) VALUES (?,?,?,?,?,?)");
                $stmt->execute([$judul, $tipe, $slug, $konten, $gambar_filename, $tanggal ?: null]);
                $_SESSION['flash'] = 'Berita/kegiatan berhasil ditambahkan.';
            }
            redirect(SITE_URL . '/admin/berita-list.php');
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-8">
        <!-- Breadcrumb -->
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="berita-list.php" style="color:var(--text-muted);">Berita</a>
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
                <a href="berita-list.php" class="btn-action" style="background:var(--bg-main);color:var(--text-muted);border:1.5px solid var(--border);text-decoration:none;">
                    Kembali
                </a>
            </div>
            <div style="padding:1.5rem;">
                <form method="POST" enctype="multipart/form-data" id="form-berita">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?= $berita['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:600;color:var(--navy);" for="judul">
                                Judul Berita / Kegiatan <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" id="judul" name="judul" class="form-control"
                                style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.7rem 1rem;"
                                placeholder="Judul berita atau kegiatan..."
                                value="<?= e($is_edit ? $berita['judul'] : ($_POST['judul'] ?? '')) ?>" required>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:600;color:var(--navy);" for="tipe">
                                Tipe Publikasi
                            </label>
                            <select id="tipe" name="tipe" class="form-select" style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.7rem 1rem;">
                                <?php
                                $tipes = ['Berita', 'Sosialisasi', 'Penghargaan', 'Kegiatan LPM'];
                                $cur_tipe = $is_edit ? ($berita['tipe'] ?? 'Berita') : ($_POST['tipe'] ?? 'Berita');
                                foreach ($tipes as $t):
                                ?>
                                <option value="<?= $t ?>" <?= $cur_tipe === $t ? 'selected' : '' ?>><?= $t ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:600;color:var(--navy);" for="tanggal">
                                Tanggal Publikasi
                            </label>
                            <input type="date" id="tanggal" name="tanggal_publikasi" class="form-control"
                                style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.7rem 1rem;"
                                value="<?= e($is_edit ? $berita['tanggal_publikasi'] : ($_POST['tanggal_publikasi'] ?? date('Y-m-d'))) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:600;color:var(--navy);" for="gambar">
                                Gambar (maks. 3MB, JPG/PNG/GIF)
                            </label>
                            <input type="file" id="gambar" name="gambar" class="form-control"
                                style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.6rem 1rem;"
                                accept="image/*">
                            <?php if ($is_edit && $berita['gambar']): ?>
                            <div style="margin-top:0.5rem;font-size:0.78rem;color:var(--text-muted);">
                                Gambar saat ini:
                                <img src="<?= SITE_URL ?>/uploads/berita/<?= e($berita['gambar']) ?>" alt="" style="height:40px;border-radius:4px;margin-left:6px;vertical-align:middle;">
                            </div>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-size:0.83rem;font-weight:600;color:var(--navy);" for="konten">
                                Konten / Isi Berita <span style="color:#C62828;">*</span>
                            </label>
                            <textarea id="konten" name="konten" class="form-control" rows="12"
                                style="border:1.5px solid var(--border);border-radius:var(--radius-sm);padding:0.75rem 1rem;font-size:0.9rem;line-height:1.7;"
                                placeholder="Tulis isi berita di sini..."
                                required><?= e($is_edit ? $berita['konten'] : ($_POST['konten'] ?? '')) ?></textarea>
                            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:0.3rem;">
                                Tips: Gunakan paragraf yang jelas untuk keterbacaan optimal.
                            </div>
                        </div>

                        <div class="col-12" style="padding-top:0.5rem;border-top:1px solid var(--border);margin-top:0.5rem;">
                            <div style="display:flex;gap:1rem;align-items:center;justify-content:flex-end;">
                                <a href="berita-list.php" style="color:var(--text-muted);font-size:0.875rem;font-weight:500;">Batal</a>
                                <button type="submit" class="btn-submit" id="btn-save">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M12 3v8.25m0 0-3-3m3 3 3-3" />
                                    </svg>
                                    <?= $is_edit ? 'Simpan Perubahan' : 'Publish Berita' ?>
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
