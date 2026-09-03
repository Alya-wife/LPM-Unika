<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$is_edit = false;
$person  = [];
if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM tim_lpm WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $person = $stmt->fetch();
    if ($person) $is_edit = true;
}

$admin_page_title = $is_edit ? 'Edit Personel Tim' : 'Tambah Personel Tim';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama      = trim($_POST['nama'] ?? '');
    $jabatan   = trim($_POST['jabatan'] ?? '');
    $bidang    = trim($_POST['bidang'] ?? '');
    $deskripsi = trim($_POST['deskripsi'] ?? '');
    $level     = trim($_POST['level'] ?? 'koordinator');
    $urutan    = (int)($_POST['urutan'] ?? 1);
    $id        = (int)($_POST['id'] ?? 0);

    if (!$nama || !$jabatan) {
        $error = 'Nama dan jabatan wajib diisi.';
    } else {
        $foto = $is_edit ? $person['foto'] : '';

        // Upload foto
        if (!empty($_FILES['foto']['name'])) {
            $allowed = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($_FILES['foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['foto']['size'] > 3 * 1024 * 1024) {
                $error = 'Ukuran foto maksimal 3MB.';
            } else {
                $dir = __DIR__ . '/../uploads/tim/';
                if (!is_dir($dir)) mkdir($dir, 0755, true);
                $filename = 'tim_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['foto']['tmp_name'], $dir . $filename)) {
                    if ($is_edit && $person['foto']) {
                        $old_path = $dir . $person['foto'];
                        if (file_exists($old_path)) @unlink($old_path);
                    }
                    $foto = $filename;
                }
            }
        }

        if (!$error) {
            if ($is_edit && $id) {
                $stmt = $db->prepare("UPDATE tim_lpm SET nama=?, jabatan=?, bidang=?, deskripsi=?, foto=?, level=?, urutan=? WHERE id=?");
                $stmt->execute([$nama, $jabatan, $bidang, $deskripsi, $foto, $level, $urutan, $id]);
                $_SESSION['flash'] = 'Personel tim berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("INSERT INTO tim_lpm (nama, jabatan, bidang, deskripsi, foto, level, urutan) VALUES (?,?,?,?,?,?,?)");
                $stmt->execute([$nama, $jabatan, $bidang, $deskripsi, $foto, $level, $urutan]);
                $_SESSION['flash'] = 'Personel tim berhasil ditambahkan.';
            }
            redirect(SITE_URL . '/admin/tim-list.php');
        }
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-7">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="tim-list.php" style="color:var(--text-muted);">Struktur Tim</a>
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
                <a href="tim-list.php" class="btn-action" style="background:var(--bg-main);color:var(--text-muted);border:1.5px solid var(--border);text-decoration:none;">
                    Kembali
                </a>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?= $person['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Nama Lengkap &amp; Gelar <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="nama" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Dr. Theresia Dwi H." value="<?= e($is_edit ? $person['nama'] : ($_POST['nama'] ?? '')) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Jabatan <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="jabatan" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Koordinator SPMI" value="<?= e($is_edit ? $person['jabatan'] : ($_POST['jabatan'] ?? '')) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Level Struktur
                            </label>
                            <select name="level" class="form-select" style="border:1.5px solid var(--border);padding:0.7rem 1rem;">
                                <option value="pimpinan" <?= ($is_edit && $person['level'] === 'pimpinan') ? 'selected' : '' ?>>Pimpinan (Kepala LPM)</option>
                                <option value="koordinator" <?= (!$is_edit || $person['level'] === 'koordinator') ? 'selected' : '' ?>>Koordinator Bidang</option>
                                <option value="anggota" <?= ($is_edit && $person['level'] === 'anggota') ? 'selected' : '' ?>>Anggota / Staf</option>
                            </select>
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Bidang Tugas / Deskripsi Singkat
                            </label>
                            <input type="text" name="bidang" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Sistem Penjaminan Mutu Internal" value="<?= e($is_edit ? $person['bidang'] : ($_POST['bidang'] ?? '')) ?>">
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Deskripsi Tugas & Wewenang
                            </label>
                            <textarea name="deskripsi" class="form-control" rows="4" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Jelaskan tugas dan wewenang personel ini secara detail..."><?= e($is_edit ? ($person['deskripsi'] ?? '') : ($_POST['deskripsi'] ?? '')) ?></textarea>
                            <small class="text-muted">Opsional. Akan ditampilkan di halaman profil tim pada website.</small>
                        </div>

                        <div class="col-md-8">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Foto Profil (JPG/PNG, Maks 3MB)
                            </label>
                            <input type="file" name="foto" class="form-control" accept="image/*">
                            <?php if ($is_edit && $person['foto'] && file_exists(__DIR__ . '/../uploads/tim/' . $person['foto'])): ?>
                            <div class="mt-2" style="font-size:0.8rem;color:var(--text-muted);">
                                Foto saat ini:
                                <img src="<?= SITE_URL ?>/uploads/tim/<?= e($person['foto']) ?>" alt="" style="width:40px;height:40px;border-radius:50%;margin-left:6px;object-fit:cover;vertical-align:middle;">
                            </div>
                            <?php else: ?>
                            <small class="text-muted d-block mt-1">Opsional. Jika kosong, akan menggunakan avatar ikon default.</small>
                            <?php endif; ?>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Urutan Tampil
                            </label>
                            <input type="number" name="urutan" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" min="1" value="<?= (int)($is_edit ? $person['urutan'] : ($_POST['urutan'] ?? 1)) ?>">
                        </div>

                        <div class="col-12" style="padding-top:1rem;border-top:1px solid var(--border);margin-top:1rem;">
                            <div style="display:flex;gap:1rem;align-items:center;justify-content:flex-end;">
                                <a href="tim-list.php" style="color:var(--text-muted);font-size:0.875rem;">Batal</a>
                                <button type="submit" class="btn-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 3.75H6.912a2.25 2.25 0 0 0-2.15 1.588L2.35 13.177a2.25 2.25 0 0 0-.1.661V18a2.25 2.25 0 0 0 2.25 2.25h15A2.25 2.25 0 0 0 21.75 18v-4.162c0-.224-.034-.447-.1-.661L19.24 5.338a2.25 2.25 0 0 0-2.15-1.588H15M2.25 13.5h3.86a2.25 2.25 0 0 1 2.012 1.244l.256.512a2.25 2.25 0 0 0 2.013 1.244h3.218a2.25 2.25 0 0 0 2.013-1.244l.256-.512a2.25 2.25 0 0 1 2.013-1.244h3.859M12 3v8.25m0 0-3-3m3 3 3-3" />
                                    </svg>
                                    <?= $is_edit ? 'Simpan Perubahan' : 'Tambahkan ke Tim' ?>
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
