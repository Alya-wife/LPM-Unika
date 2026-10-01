<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$is_edit = false;
$d = [
    'kode' => '',
    'nama' => '',
    'lingkup' => '',
    'warna' => '#0A192F',
    'bg' => '#F1F3F7',
    'logo' => '',
    'link_website' => '',
    'urutan' => 1
];

if (isset($_GET['id']) && is_numeric($_GET['id'])) {
    $stmt = $db->prepare("SELECT * FROM lembaga_akreditasi WHERE id = ?");
    $stmt->execute([(int)$_GET['id']]);
    $row = $stmt->fetch();
    if ($row) {
        $is_edit = true;
        $d = $row;
    }
}

$admin_page_title = $is_edit ? 'Edit Lembaga Akreditasi' : 'Tambah Lembaga Akreditasi';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $kode         = trim($_POST['kode'] ?? '');
    $nama         = trim($_POST['nama'] ?? '');
    $lingkup      = trim($_POST['lingkup'] ?? '');
    $warna        = trim($_POST['warna'] ?? '#0A192F');
    $bg           = trim($_POST['bg'] ?? '#F1F3F7');
    $link_website = trim($_POST['link_website'] ?? '');
    $urutan       = (int)($_POST['urutan'] ?? 1);
    $id           = (int)($_POST['id'] ?? 0);

    if (!$kode || !$nama) {
        $error = 'Kode lembaga dan Nama lembaga wajib diisi.';
    } else {
        $upload_dir = __DIR__ . '/../uploads/akreditasi/';
        if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

        $logo = $d['logo'] ?? null;

        // Handle Remove Logo
        if (!empty($_POST['hapus_logo']) && empty($_FILES['logo']['name'])) {
            if ($logo && file_exists($upload_dir . $logo)) @unlink($upload_dir . $logo);
            $logo = null;
        }

        // Handle Logo Upload
        if (!empty($_FILES['logo']['name']) && $_FILES['logo']['error'] === UPLOAD_ERR_OK) {
            $ext = strtolower(pathinfo($_FILES['logo']['name'], PATHINFO_EXTENSION));
            if (in_array($ext, ['png', 'jpg', 'jpeg', 'svg', 'webp'])) {
                $clean_kode = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $kode)) ?: 'lembaga';
                if ($ext === 'svg') {
                    $new_logo = 'logo_' . $clean_kode . '_' . time() . '.svg';
                    if (move_uploaded_file($_FILES['logo']['tmp_name'], $upload_dir . $new_logo)) {
                        if ($logo && file_exists($upload_dir . $logo)) @unlink($upload_dir . $logo);
                        $logo = $new_logo;
                    }
                } else {
                    $saved_logo = convertAndSaveWebP($_FILES['logo']['tmp_name'], $upload_dir, 'logo_' . $clean_kode . '_');
                    if ($saved_logo) {
                        if ($logo && file_exists($upload_dir . $logo)) @unlink($upload_dir . $logo);
                        $logo = $saved_logo;
                    }
                }
            }
        }

        if ($is_edit && $id) {
            $stmt = $db->prepare("UPDATE lembaga_akreditasi SET kode=?, nama=?, lingkup=?, warna=?, bg=?, logo=?, link_website=?, urutan=? WHERE id=?");
            $stmt->execute([$kode, $nama, $lingkup, $warna, $bg, $logo, $link_website, $urutan, $id]);
            $_SESSION['flash'] = 'Data tautan resmi lembaga akreditasi berhasil diperbarui.';
        } else {
            $stmt = $db->prepare("INSERT INTO lembaga_akreditasi (kode, nama, lingkup, warna, bg, logo, link_website, urutan) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$kode, $nama, $lingkup, $warna, $bg, $logo, $link_website, $urutan]);
            $_SESSION['flash'] = 'Lembaga akreditasi baru berhasil ditambahkan.';
        }

        redirect(SITE_URL . '/admin/lembaga-akreditasi-list.php');
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-8">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="lembaga-akreditasi-list.php" style="color:var(--text-muted);">Lembaga Akreditasi</a>
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
                <a href="lembaga-akreditasi-list.php" class="btn-action" style="background:var(--bg-main);color:var(--text-muted);border:1.5px solid var(--border);text-decoration:none;">
                    Kembali
                </a>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST" enctype="multipart/form-data">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?= $d['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Kode Singkat Lembaga <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="kode" class="form-control" placeholder="Contoh: LAMEMBA" value="<?= e($d['kode']) ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Nama Lengkap Lembaga <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="nama" class="form-control" placeholder="Contoh: Lembaga Akreditasi Mandiri Ekonomi..." value="<?= e($d['nama']) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Lingkup Rumpun Ilmu / Fakultas
                            </label>
                            <input type="text" name="lingkup" class="form-control" placeholder="Contoh: Fakultas Ekonomi dan Bisnis (FEB)" value="<?= e($d['lingkup'] ?? '') ?>">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Warna Teks / Border
                            </label>
                            <div class="d-flex gap-2">
                                <input type="color" name="warna" class="form-control form-control-color" value="<?= e($d['warna'] ?: '#0A192F') ?>" style="width:50px;height:42px;">
                                <input type="text" class="form-control" value="<?= e($d['warna'] ?: '#0A192F') ?>" oninput="this.previousElementSibling.value = this.value">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Warna Latar Badge (Hex)
                            </label>
                            <div class="d-flex gap-2">
                                <input type="color" name="bg" class="form-control form-control-color" value="<?= e($d['bg'] ?: '#F1F3F7') ?>" style="width:50px;height:42px;">
                                <input type="text" class="form-control" value="<?= e($d['bg'] ?: '#F1F3F7') ?>" oninput="this.previousElementSibling.value = this.value">
                            </div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Masa Berlaku Sertifikat / Akreditasi
                            </label>
                            <input type="date" name="masa_berlaku" class="form-control" value="<?= e($d['masa_berlaku'] ?? '') ?>">
                            <div class="form-text" style="font-size:0.75rem;">Notifikasi peringatan merah akan aktif otomatis jika tersisa 2 tahun (730 hari) sebelum tanggal ini.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Urutan Tampilan
                            </label>
                            <input type="number" name="urutan" class="form-control" value="<?= (int)($d['urutan'] ?? 1) ?>" min="1">
                        </div>

                        <!-- Link Website Lembaga Akreditasi -->
                        <div class="col-12 p-3 my-2" style="background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:10px;">
                            <label class="form-label fw-bold text-navy" style="color:var(--navy);font-family:var(--font-heading);">
                                <i class="bi bi-link-45deg me-1" style="font-size:1.15rem;color:var(--purple);"></i> Link Website Resmi Lembaga <span style="color:#C62828;">*</span>
                            </label>
                            <div class="input-group">
                                <span class="input-group-text bg-white text-muted"><i class="bi bi-globe2"></i></span>
                                <input type="url" name="link_website" class="form-control" placeholder="https://example.or.id" value="<?= e($d['link_website'] ?? '') ?>" required>
                                <?php if (!empty($d['link_website'])): ?>
                                <a href="<?= e($d['link_website']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary">
                                    <i class="bi bi-box-arrow-up-right me-1"></i> Buka Link
                                </a>
                                <?php endif; ?>
                            </div>
                            <div class="form-text" style="font-size:0.75rem;">
                                Masukkan tautan resmi website lembaga (misal: <code>https://www.banpt.or.id/</code> atau <code>https://lamemba.or.id/</code>). Pengunjung akan diarahkan langsung ke website resmi tanpa perlu upload berkas fisik.
                            </div>
                        </div>

                        <!-- Upload Logo Lembaga -->
                        <div class="col-12 p-3 my-2" style="background:#F8FAFC;border:1.5px solid #E2E8F0;border-radius:10px;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label class="form-label fw-bold mb-0" style="font-family:var(--font-heading);color:var(--navy);font-size:0.95rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="18" height="18" class="me-1" style="vertical-align:-3px;color:var(--purple);">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                                    </svg>
                                    Logo Lembaga Akreditasi
                                </label>
                                <span class="badge bg-light text-muted border" style="font-size:0.75rem;">PNG / SVG / JPG / WebP</span>
                            </div>

                            <div class="row align-items-center g-3">
                                <!-- Pratinjau Logo Saat Ini / Baru -->
                                <div class="col-auto">
                                    <div id="logoPreviewWrapper" style="width:90px;height:90px;border-radius:12px;background:#ffffff;border:2px dashed #CBD5E1;display:flex;align-items:center;justify-content:center;overflow:hidden;padding:6px;box-shadow:0 2px 8px rgba(0,0,0,0.04);">
                                        <?php if (!empty($d['logo']) && file_exists(__DIR__ . '/../uploads/akreditasi/' . $d['logo'])): ?>
                                            <img id="logoPreviewImg" src="<?= SITE_URL ?>/uploads/akreditasi/<?= e($d['logo']) ?>" alt="Logo" style="max-width:100%;max-height:100%;object-fit:contain;">
                                        <?php else: ?>
                                            <img id="logoPreviewImg" src="" alt="Preview" style="max-width:100%;max-height:100%;object-fit:contain;display:none;">
                                            <span id="logoPreviewPlaceholder" style="font-size:0.7rem;color:#94A3B8;text-align:center;font-weight:600;line-height:1.2;">
                                                Belum Ada Logo
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- Input & Keterangan -->
                                <div class="col">
                                    <input type="file" name="logo" id="inputLogoLembaga" class="form-control" accept=".png,.jpg,.jpeg,.svg,.webp" onchange="previewLogoImage(this)">
                                    <div class="form-text mt-1" style="font-size:0.78rem;color:#64748B;">
                                        Pilih file logo resmi lembaga. Disarankan menggunakan format <strong>PNG transparan</strong> agar serasi dengan kartu akreditasi di website.
                                    </div>
                                    <?php if (!empty($d['logo']) && file_exists(__DIR__ . '/../uploads/akreditasi/' . $d['logo'])): ?>
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" name="hapus_logo" value="1" id="checkHapusLogo">
                                        <label class="form-check-label small text-danger" for="checkHapusLogo">
                                            Hapus logo saat ini (kembalikan ke teks inisial)
                                        </label>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <script>
                        function previewLogoImage(input) {
                            var img = document.getElementById('logoPreviewImg');
                            var placeholder = document.getElementById('logoPreviewPlaceholder');
                            if (input.files && input.files[0]) {
                                var reader = new FileReader();
                                reader.onload = function(e) {
                                    img.src = e.target.result;
                                    img.style.display = 'block';
                                    if (placeholder) placeholder.style.display = 'none';
                                }
                                reader.readAsDataURL(input.files[0]);
                            }
                        }
                        </script>
                    </div>

                    <div class="border-top mt-4 pt-3 d-flex justify-content-end gap-2">
                        <a href="lembaga-akreditasi-list.php" class="btn-action" style="background:var(--bg-main);color:var(--text-muted);border:1.5px solid var(--border);text-decoration:none;">
                            Batal
                        </a>
                        <button type="submit" class="btn-save px-4 py-2">
                            Simpan Lembaga
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
