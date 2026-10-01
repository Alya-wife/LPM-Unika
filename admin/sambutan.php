<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Sambutan Kepala LPM';
$db = getDB();

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama     = trim($_POST['sambutan_nama'] ?? '');
    $jabatan  = trim($_POST['sambutan_jabatan'] ?? '');
    $instansi = trim($_POST['sambutan_instansi'] ?? '');
    $teks     = trim($_POST['sambutan_teks'] ?? '');

    if (!$nama || !$teks) {
        $error = 'Nama pimpinan dan isi teks sambutan wajib diisi.';
    } else {
        setPengaturan('sambutan_nama', $nama);
        setPengaturan('sambutan_jabatan', $jabatan);
        setPengaturan('sambutan_instansi', $instansi);
        setPengaturan('sambutan_teks', $teks);

        // Upload foto
        if (!empty($_FILES['sambutan_foto']['name'])) {
            $allowed = ['jpg','jpeg','png','webp'];
            $ext = strtolower(pathinfo($_FILES['sambutan_foto']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, $allowed)) {
                $error = 'Format foto tidak didukung. Gunakan JPG, PNG, atau WEBP.';
            } elseif ($_FILES['sambutan_foto']['size'] > 3 * 1024 * 1024) {
                $error = 'Ukuran foto maksimal 3MB.';
            } else {
                $dir = __DIR__ . '/../uploads/profil/';
                $saved_foto = convertAndSaveWebP($_FILES['sambutan_foto']['tmp_name'], $dir, 'kepala_');
                if ($saved_foto) {
                    $old = getPengaturan('sambutan_foto');
                    if ($old && file_exists($dir . $old)) @unlink($dir . $old);
                    setPengaturan('sambutan_foto', $saved_foto);
                } else {
                    $error = 'Gagal mengonversi foto kepala LPM ke format WebP.';
                }
            }
        }

        if (!$error) {
            $_SESSION['flash'] = 'Sambutan Kepala LPM berhasil diperbarui.';
            redirect(SITE_URL . '/admin/sambutan.php');
        }
    }
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$nama     = getPengaturan('sambutan_nama', 'Stefani Lily Indarto, SE., MM., Ak., CA., CPA.');
$jabatan  = getPengaturan('sambutan_jabatan', 'Kepala Lembaga Penjaminan Mutu');
$instansi = getPengaturan('sambutan_instansi', 'Universitas Katolik Soegijapranata');
$foto     = getPengaturan('sambutan_foto', '');
$teks     = getPengaturan('sambutan_teks', '');

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <span style="color:var(--navy);">Sambutan Kepala LPM</span>
        </div>

        <?php if ($flash): ?>
        <div class="alert-lpm alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <?= e($flash) ?>
        </div>
        <?php endif; ?>

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
                <div class="admin-table-title">Edit Sambutan Kepala Lembaga Penjaminan Mutu</div>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST" enctype="multipart/form-data">
                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Nama Lengkap &amp; Gelar <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="sambutan_nama" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($nama) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Jabatan
                            </label>
                            <input type="text" name="sambutan_jabatan" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($jabatan) ?>" required>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Nama Institusi / Universitas
                            </label>
                            <input type="text" name="sambutan_instansi" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($instansi) ?>">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Foto Kepala LPM (Maks 3MB)
                            </label>
                            <input type="file" name="sambutan_foto" class="form-control" accept="image/*">
                            <?php if ($foto && file_exists(__DIR__ . '/../uploads/profil/' . $foto)): ?>
                            <div class="mt-2" style="font-size:0.8rem;color:var(--text-muted);display:flex;align-items:center;gap:10px;">
                                <span>Foto saat ini:</span>
                                <img src="<?= SITE_URL ?>/uploads/profil/<?= e($foto) ?>" alt="" style="width:45px;height:45px;border-radius:50%;object-fit:cover;border:2px solid var(--purple);">
                            </div>
                            <?php else: ?>
                            <small class="text-muted d-block mt-1">Jika kosong, akan menggunakan ikon avatar placeholder elegan.</small>
                            <?php endif; ?>
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Isi Sambutan / Pesan Pimpinan <span style="color:#C62828;">*</span>
                            </label>
                            <textarea name="sambutan_teks" class="form-control" rows="8" style="border:1.5px solid var(--border);padding:0.85rem 1rem;line-height:1.75;" placeholder="Tuliskan kata sambutan di sini..." required><?= e($teks) ?></textarea>
                            <small class="text-muted d-block mt-1">Gunakan pemisah baris/enter untuk membagi paragraf sambutan.</small>
                        </div>

                        <div class="col-12" style="padding-top:1rem;border-top:1px solid var(--border);">
                            <div style="display:flex;justify-content:flex-end;">
                                <button type="submit" class="btn-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Simpan Perubahan Sambutan
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
