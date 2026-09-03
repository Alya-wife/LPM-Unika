<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$is_edit = false;
$slide   = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM hero_slides WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $slide = $stmt->fetch();
    if ($slide) $is_edit = true;
}

$admin_page_title = $is_edit ? 'Edit Slide Beranda' : 'Tambah Slide Beranda';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul              = trim($_POST['judul'] ?? '');
    $subjudul           = trim($_POST['subjudul'] ?? '');
    $highlight_text     = trim($_POST['highlight_text'] ?? '');
    $deskripsi          = trim($_POST['deskripsi'] ?? '');
    $btn_text           = trim($_POST['btn_text'] ?? '');
    $btn_link           = trim($_POST['btn_link'] ?? '');
    $btn_secondary_text = trim($_POST['btn_secondary_text'] ?? '');
    $btn_secondary_link = trim($_POST['btn_secondary_link'] ?? '');
    $urutan             = (int)($_POST['urutan'] ?? 1);
    $is_active          = isset($_POST['is_active']) ? 1 : 0;
    $id                 = (int)($_POST['id'] ?? 0);

    if (!$judul) {
        $error = 'Judul slide wajib diisi.';
    } else {
        $gambar = $is_edit ? $slide['gambar'] : '';

        // If direct image URL provided
        $image_url = trim($_POST['image_url'] ?? '');
        if ($image_url) {
            $gambar = $image_url;
        }

        // If file uploaded
        if (!empty($_FILES['gambar_file']['name'])) {
            $allowed = ['jpg','jpeg','png','webp','gif'];
            $ext = strtolower(pathinfo($_FILES['gambar_file']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Format gambar tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['gambar_file']['size'] > 5 * 1024 * 1024) {
                $error = 'Ukuran file maksimal 5MB.';
            } else {
                $dir = __DIR__ . '/../uploads/slides/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $filename = 'slide_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['gambar_file']['tmp_name'], $dir . $filename)) {
                    // Remove old local image if editing
                    if ($is_edit && $slide['gambar'] && !filter_var($slide['gambar'], FILTER_VALIDATE_URL)) {
                        $old_path = $dir . $slide['gambar'];
                        if (file_exists($old_path)) @unlink($old_path);
                    }
                    $gambar = $filename;
                }
            }
        }

        if (!$error) {
            if ($is_edit && $id) {
                $stmt = $db->prepare("UPDATE hero_slides SET judul=?, subjudul=?, highlight_text=?, deskripsi=?, gambar=?, btn_text=?, btn_link=?, btn_secondary_text=?, btn_secondary_link=?, urutan=?, is_active=? WHERE id=?");
                $stmt->execute([$judul, $subjudul, $highlight_text, $deskripsi, $gambar, $btn_text, $btn_link, $btn_secondary_text, $btn_secondary_link, $urutan, $is_active, $id]);
                $_SESSION['flash'] = 'Slide berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("INSERT INTO hero_slides (judul, subjudul, highlight_text, deskripsi, gambar, btn_text, btn_link, btn_secondary_text, btn_secondary_link, urutan, is_active) VALUES (?,?,?,?,?,?,?,?,?,?,?)");
                $stmt->execute([$judul, $subjudul, $highlight_text, $deskripsi, $gambar, $btn_text, $btn_link, $btn_secondary_text, $btn_secondary_link, $urutan, $is_active]);
                $_SESSION['flash'] = 'Slide berhasil ditambahkan.';
            }
            redirect(SITE_URL . '/admin/slider-list.php');
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="slider-list.php" style="color:var(--text-muted);">Slider Beranda</a>
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
                <a href="slider-list.php" class="btn-action" style="background:var(--bg-main);color:var(--text-muted);border:1.5px solid var(--border);text-decoration:none;">
                    Kembali
                </a>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?= $slide['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Judul Slide Utama <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="judul" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Lembaga Penjaminan Mutu SCU" value="<?= e($is_edit ? $slide['judul'] : ($_POST['judul'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Urutan Tampil
                            </label>
                            <input type="number" name="urutan" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" min="1" value="<?= (int)($is_edit ? $slide['urutan'] : ($_POST['urutan'] ?? 1)) ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Subjudul / Badge Atas
                            </label>
                            <input type="text" name="subjudul" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Soegijapranata Catholic University" value="<?= e($is_edit ? $slide['subjudul'] : ($_POST['subjudul'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Kata Berwarna Emas (Highlight)
                            </label>
                            <input type="text" name="highlight_text" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Kata yang akan disorot warna emas di judul" value="<?= e($is_edit ? $slide['highlight_text'] : ($_POST['highlight_text'] ?? '')) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Deskripsi / Penjelasan Singkat
                            </label>
                            <textarea name="deskripsi" class="form-control" rows="3" style="border:1.5px solid var(--border);padding:0.75rem 1rem;" placeholder="Deskripsi ringkas yang muncul di bawah judul..."><?= e($is_edit ? $slide['deskripsi'] : ($_POST['deskripsi'] ?? '')) ?></textarea>
                        </div>

                        <!-- Gambar Background -->
                        <div class="col-12" style="background:#F8F9FA;padding:1.25rem;border-radius:var(--radius-md);border:1px solid var(--border);">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:0.5rem;display:block;">
                                Gambar Latar Belakang Slide
                            </label>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label style="font-size:0.8rem;color:var(--text-muted);display:block;margin-bottom:0.25rem;">Opsi 1: Upload File Gambar (JPG/PNG/WEBP, maks 5MB)</label>
                                    <input type="file" name="gambar_file" class="form-control" accept="image/*">
                                </div>
                                <div class="col-md-6">
                                    <label style="font-size:0.8rem;color:var(--text-muted);display:block;margin-bottom:0.25rem;">Opsi 2: Atau Masukkan URL Gambar Eksternal</label>
                                    <input type="url" name="image_url" class="form-control" placeholder="https://images.unsplash.com/..." value="<?= ($is_edit && filter_var($slide['gambar'], FILTER_VALIDATE_URL)) ? e($slide['gambar']) : '' ?>">
                                </div>
                            </div>
                            <?php if ($is_edit && $slide['gambar']): ?>
                            <div class="mt-2" style="font-size:0.8rem;color:var(--text-muted);">
                                Gambar saat ini: 
                                <?php
                                $cur_img = filter_var($slide['gambar'], FILTER_VALIDATE_URL) ? $slide['gambar'] : SITE_URL . '/uploads/slides/' . $slide['gambar'];
                                ?>
                                <img src="<?= e($cur_img) ?>" alt="" style="height:50px;border-radius:4px;margin-left:6px;vertical-align:middle;border:1px solid var(--border);">
                            </div>
                            <?php endif; ?>
                            <small class="text-muted d-block mt-2">Ukuran resolusi ideal gambar landscape: 1920 x 800 pixel.</small>
                        </div>

                        <!-- Tombol Utama -->
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Teks Tombol Utama
                            </label>
                            <input type="text" name="btn_text" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Akses Dokumen SPMI" value="<?= e($is_edit ? $slide['btn_text'] : ($_POST['btn_text'] ?? 'Akses Dokumen SPMI')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Link Tombol Utama
                            </label>
                            <input type="text" name="btn_link" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: spmi.php" value="<?= e($is_edit ? $slide['btn_link'] : ($_POST['btn_link'] ?? 'spmi.php')) ?>">
                        </div>

                        <!-- Tombol Sekunder -->
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Teks Tombol Sekunder (Opsional)
                            </label>
                            <input type="text" name="btn_secondary_text" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Profil LPM" value="<?= e($is_edit ? $slide['btn_secondary_text'] : ($_POST['btn_secondary_text'] ?? '')) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Link Tombol Sekunder (Opsional)
                            </label>
                            <input type="text" name="btn_secondary_link" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: profil.php" value="<?= e($is_edit ? $slide['btn_secondary_link'] : ($_POST['btn_secondary_link'] ?? '')) ?>">
                        </div>

                        <div class="col-12 mt-3">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" id="is_active" name="is_active" <?= (!$is_edit || $slide['is_active']) ? 'checked' : '' ?>>
                                <label class="form-check-label" for="is_active" style="font-weight:600;color:var(--navy);">
                                    Aktifkan Slide ini (Ditampilkan di Beranda)
                                </label>
                            </div>
                        </div>

                        <div class="col-12" style="padding-top:1rem;border-top:1px solid var(--border);margin-top:1rem;">
                            <div style="display:flex;gap:1rem;align-items:center;justify-content:flex-end;">
                                <a href="slider-list.php" style="color:var(--text-muted);font-size:0.875rem;">Batal</a>
                                <button type="submit" class="btn-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M12 3v8.25m0 0-3-3m3 3 3-3" />
                                    </svg>
                                    <?= $is_edit ? 'Simpan Perubahan Slide' : 'Tambahkan Slide' ?>
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
