<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Manajemen Penghargaan';
$db = getDB();

// Handle capaian mutu & akreditasi institusi update (Beranda)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_capaian_mutu') {
    $akred_badge     = trim($_POST['akred_institusi_badge'] ?? 'Akreditasi Institusi');
    $akred_judul     = trim($_POST['akred_institusi_judul'] ?? "Capaian Mutu\nUniversitas Katolik Soegijapranata");
    $akred_peringkat = trim($_POST['akred_institusi_peringkat'] ?? 'UNGGUL');
    $akred_sk        = trim($_POST['akred_institusi_sk'] ?? '');
    $akred_teks      = trim($_POST['akred_institusi_teks'] ?? '');

    setPengaturan('akred_institusi_badge', $akred_badge);
    setPengaturan('akred_institusi_judul', $akred_judul);
    setPengaturan('akred_institusi_peringkat', $akred_peringkat);
    setPengaturan('akred_institusi_sk', $akred_sk);
    setPengaturan('akred_institusi_teks', $akred_teks);

    // Handle upload file sertifikat jika ada
    $upload_dir = __DIR__ . '/../uploads/akreditasi/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0777, true);
    }

    if (isset($_FILES['file_sertifikat']) && $_FILES['file_sertifikat']['error'] === UPLOAD_ERR_OK) {
        $tmp = $_FILES['file_sertifikat']['tmp_name'];
        $name = $_FILES['file_sertifikat']['name'];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (in_array($ext, ['pdf', 'jpg', 'jpeg', 'png', 'webp'])) {
            $new_name = saveOrConvertToPdf($tmp, $upload_dir, 'akred_institusi_cert_', $name);
            if ($new_name) {
                $old = getPengaturan('akred_institusi_file');
                if ($old && file_exists($upload_dir . $old) && strpos($old, 'akred_institusi_cert_') === 0) {
                    @unlink($upload_dir . $old);
                    $old_thumb = $upload_dir . pathinfo($old, PATHINFO_FILENAME) . '.webp';
                    if (file_exists($old_thumb)) @unlink($old_thumb);
                    $old_thumb_jpg = $upload_dir . 'thumb_' . pathinfo($old, PATHINFO_FILENAME) . '.jpg';
                    if (file_exists($old_thumb_jpg)) @unlink($old_thumb_jpg);
                }
                setPengaturan('akred_institusi_file', $new_name);
                getOrGeneratePdfPreview($new_name, 'akreditasi');
            }
        }
    }

    $_SESSION['flash'] = 'Data Capaian Mutu & Sertifikat Akreditasi Beranda berhasil diperbarui.';
    redirect(SITE_URL . '/admin/penghargaan-list.php');
}

// Handle statistik capaian update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_stats') {
    $stat_tahun      = trim($_POST['stat_tahun'] ?? '40');
    $stat_prodi      = trim($_POST['stat_prodi'] ?? '');
    $stat_akreditasi = trim($_POST['stat_akreditasi'] ?? '');
    $stat_dokumen    = trim($_POST['stat_dokumen'] ?? '');

    setPengaturan('stat_tahun', $stat_tahun);
    setPengaturan('stat_prodi', $stat_prodi);
    setPengaturan('stat_akreditasi_persen', $stat_akreditasi);
    setPengaturan('stat_dokumen', $stat_dokumen);

    $_SESSION['flash'] = 'Statistik capaian beranda berhasil diperbarui.';
    redirect(SITE_URL . '/admin/penghargaan-list.php');
}

// Handle setting layout update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_layout') {
    $layout = $_POST['layout_penghargaan'] === 'grid' ? 'grid' : 'slider';
    $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('layout_penghargaan', ?) ON DUPLICATE KEY UPDATE nilai = ?")->execute([$layout, $layout]);
    $_SESSION['flash'] = 'Layout beranda berhasil diperbarui.';
    redirect(SITE_URL . '/admin/penghargaan-list.php');
}

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $row = $db->prepare("SELECT file_path FROM penghargaan WHERE id = ?");
    $row->execute([$id]);
    $row = $row->fetch();
    if ($row && $row['file_path'] && file_exists(__DIR__ . '/../uploads/penghargaan/' . $row['file_path'])) {
        unlink(__DIR__ . '/../uploads/penghargaan/' . $row['file_path']);
    }
    $db->prepare("DELETE FROM penghargaan WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Penghargaan berhasil dihapus.';
    redirect(SITE_URL . '/admin/penghargaan-list.php');
}

$peng_list = $db->query("SELECT * FROM penghargaan ORDER BY tahun DESC, id DESC")->fetchAll();
$current_layout = getPengaturan('layout_penghargaan', 'slider');

$actual_doc_count     = (int)$db->query("SELECT COUNT(*) FROM dokumen")->fetchColumn();
$actual_prodi_count   = (int)$db->query("SELECT COUNT(*) FROM akreditasi_prodi")->fetchColumn();
$count_terakreditasi  = (int)$db->query("SELECT COUNT(*) FROM akreditasi_prodi WHERE peringkat IS NOT NULL AND TRIM(peringkat) != '' AND LOWER(peringkat) NOT LIKE '%belum%'")->fetchColumn();
if ($actual_prodi_count > 0) {
    $actual_akred_pct = ($count_terakreditasi >= $actual_prodi_count) ? 100 : round(($count_terakreditasi / $actual_prodi_count) * 100);
} else {
    $actual_akred_pct = 100;
}

$stat_tahun           = getPengaturan('stat_tahun', '40');
$stat_prodi           = getPengaturan('stat_prodi', '');
$stat_akreditasi      = getPengaturan('stat_akreditasi_persen', '');
if ($stat_akreditasi === '') {
    $old_akr = getPengaturan('stat_akreditasi', '');
    if (is_numeric($old_akr)) {
        $stat_akreditasi = $old_akr;
    }
}
$stat_dokumen         = getPengaturan('stat_dokumen', '');

// Data Capaian Mutu & Akreditasi Beranda
$akred_badge     = getPengaturan('akred_institusi_badge', 'Akreditasi Institusi');
$akred_judul     = getPengaturan('akred_institusi_judul', "Capaian Mutu\nUniversitas Katolik Soegijapranata");
$akred_peringkat = getPengaturan('akred_institusi_peringkat', 'UNGGUL');
$akred_sk        = getPengaturan('akred_institusi_sk', 'Nomor SK: -');
$akred_teks      = getPengaturan('akred_institusi_teks', 'Universitas kami terus berkomitmen untuk memberikan standar pendidikan terbaik sesuai dengan pedoman Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT).');
$default_cert    = file_exists(__DIR__ . '/../uploads/akreditasi/2023_Sertifikat_Akreditasi_ISK_AIPT_UNIKA_Unggul.pdf') ? '2023_Sertifikat_Akreditasi_ISK_AIPT_UNIKA_Unggul.pdf' : '';
$akred_file      = getPengaturan('akred_institusi_file', $default_cert);
$akred_preview   = $akred_file ? getOrGeneratePdfPreview($akred_file, 'akreditasi') : '';

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <?= e($flash) ?>
</div>
<?php endif; ?>

<!-- Form Kelola Capaian Mutu & Akreditasi Institusi Beranda -->
<div class="admin-table-wrap mb-4" style="padding: 1.5rem 1.75rem; border: 1.5px solid #E2E8F0; background: #FFFFFF; border-radius: 12px;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-3" style="border-bottom: 1px solid #F1F5F9;">
        <div>
            <h5 class="mb-1" style="font-weight: 700; color: var(--navy); font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="var(--purple)" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .982-3.172M12 3a4.5 4.5 0 0 0-4.5 4.5v1.875a3.375 3.375 0 0 0 3.375 3.375h2.25a3.375 3.375 0 0 0 3.375-3.375V7.5A4.5 4.5 0 0 0 12 3Z" />
                </svg>
                Capaian Mutu &amp; Sertifikat Akreditasi (Beranda)
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                Sesuaikan teks capaian mutu universitas, peringkat akreditasi, nomor SK, dan unggah berkas sertifikat yang tampil di bagian tengah halaman Beranda.
            </p>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="<?= SITE_URL ?>/admin/akreditasi-institusi.php" class="btn btn-sm btn-outline-primary" style="font-size: 0.8rem; border-radius: 6px;">
                <i class="bi bi-gear-fill me-1"></i> Detail Akreditasi Institusi
            </a>
            <a href="<?= SITE_URL ?>/index.php#akreditasi" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size: 0.8rem; border-radius: 6px;">
                Lihat di Beranda &rarr;
            </a>
        </div>
    </div>

    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="update_capaian_mutu">
        <div class="row g-3">
            <div class="col-lg-7">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight: 600; font-size: 0.85rem; color: #334155;">Badge Kategori</label>
                        <input type="text" name="akred_institusi_badge" class="form-control" value="<?= e($akred_badge) ?>" placeholder="Contoh: Akreditasi Institusi" style="border-radius: 8px;">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label" style="font-weight: 600; font-size: 0.85rem; color: #334155;">Peringkat Akreditasi</label>
                        <input type="text" name="akred_institusi_peringkat" class="form-control" value="<?= e($akred_peringkat) ?>" placeholder="Contoh: UNGGUL" required style="border-radius: 8px; font-weight:700; color:var(--purple);">
                    </div>
                    <div class="col-12">
                        <label class="form-label" style="font-weight: 600; font-size: 0.85rem; color: #334155;">Judul Bagian Capaian Mutu</label>
                        <input type="text" name="akred_institusi_judul" class="form-control" value="<?= e($akred_judul) ?>" placeholder="Capaian Mutu Universitas Katolik Soegijapranata" required style="border-radius: 8px;">
                    </div>
                    <div class="col-12">
                        <label class="form-label" style="font-weight: 600; font-size: 0.85rem; color: #334155;">Nomor SK Akreditasi</label>
                        <input type="text" name="akred_institusi_sk" class="form-control" value="<?= e($akred_sk) ?>" placeholder="Contoh: No. 101/SK/BAN-PT/Ak/PT/III/2023" style="border-radius: 8px;">
                    </div>
                    <div class="col-12">
                        <label class="form-label" style="font-weight: 600; font-size: 0.85rem; color: #334155;">Teks Komitmen / Deskripsi Capaian Mutu</label>
                        <textarea name="akred_institusi_teks" rows="3" class="form-control" style="border-radius: 8px;" placeholder="Tuliskan komitmen mutu atau ringkasan capaian akreditasi..."><?= e($akred_teks) ?></textarea>
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div style="background: #F8FAFC; border: 1.5px dashed #CBD5E1; border-radius: 10px; padding: 1.25rem;" class="h-100 d-flex flex-column justify-content-between">
                    <div>
                        <label class="form-label d-flex align-items-center justify-content-between" style="font-weight: 600; font-size: 0.85rem; color: #334155;">
                            <span>Berkas Sertifikat Akreditasi</span>
                            <span class="badge bg-primary-subtle text-primary border" style="font-size:0.7rem;">Otomatis PDF</span>
                        </label>

                        <?php if ($akred_file && file_exists(__DIR__ . '/../uploads/akreditasi/' . $akred_file)): ?>
                            <div class="p-2 mb-3 bg-white border rounded-3 d-flex align-items-center gap-3">
                                <div style="width: 64px; height: 64px; border-radius: 6px; overflow: hidden; background: #EEF2F6; flex-shrink: 0; display: flex; align-items: center; justify-content: center;">
                                    <?php if ($akred_preview && file_exists(__DIR__ . '/../uploads/akreditasi/' . $akred_preview)): ?>
                                        <img src="<?= SITE_URL ?>/uploads/akreditasi/<?= e($akred_preview) ?>" alt="Sertifikat" style="width: 100%; height: 100%; object-fit: cover;">
                                    <?php else: ?>
                                        <i class="bi bi-file-earmark-pdf text-danger fs-2"></i>
                                    <?php endif; ?>
                                </div>
                                <div style="min-width: 0; flex-grow: 1;">
                                    <div class="text-truncate fw-semibold text-navy small mb-1"><?= e($akred_file) ?></div>
                                    <div class="d-flex align-items-center gap-2">
                                        <a href="<?= SITE_URL ?>/uploads/akreditasi/<?= e($akred_file) ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:0.75rem; border-radius:4px;">
                                            <i class="bi bi-box-arrow-up-right me-1"></i> Buka Berkas
                                        </a>
                                        <span class="badge bg-success-subtle text-success py-1 px-2" style="font-size:0.7rem;">Aktif di Beranda</span>
                                    </div>
                                </div>
                            </div>
                        <?php else: ?>
                            <div class="alert alert-warning py-2 px-3 small mb-3" style="border-radius: 8px;">
                                <i class="bi bi-exclamation-triangle-fill me-1"></i> Belum ada sertifikat yang diunggah. Tampilan di beranda saat ini masih berupa placeholder kosong.
                            </div>
                        <?php endif; ?>

                        <div class="mb-2">
                            <label class="form-label small text-muted mb-1"><?= $akred_file ? 'Ganti Berkas Sertifikat:' : 'Unggah Berkas Baru:' ?></label>
                            <input type="file" name="file_sertifikat" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp" style="border-radius: 6px;">
                            <div class="mt-1">
                                <span class="badge bg-light text-dark border me-1"><i class="bi bi-hdd-fill text-warning me-1"></i>Batas Ukuran: Maksimal 20 MB</span>
                                <small class="text-muted" style="font-size: 0.73rem;">Mendukung format .pdf, .webp, .jpg, .png. File gambar otomatis diubah ke dokumen PDF.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3 pt-2" style="border-top: 1px dashed #E2E8F0;">
            <button type="submit" class="btn-save" style="margin-top: 0; padding: 0.55rem 1.4rem; font-size: 0.875rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Simpan Capaian Mutu &amp; Akreditasi
            </button>
        </div>
    </form>
</div>

<!-- Form Kelola Statistik Capaian Beranda -->
<div class="admin-table-wrap mb-4" style="padding: 1.5rem 1.75rem; border: 1.5px solid #E2E8F0; background: #FFFFFF; border-radius: 12px;">
    <div class="d-flex flex-wrap align-items-center justify-content-between gap-3 mb-3 pb-3" style="border-bottom: 1px solid #F1F5F9;">
        <div>
            <h5 class="mb-1" style="font-weight: 700; color: var(--navy); font-size: 1.15rem; display: flex; align-items: center; gap: 0.5rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="var(--gold)" width="20" height="20">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
                Statistik Capaian Beranda
            </h5>
            <p class="text-muted mb-0" style="font-size: 0.85rem;">
                Sesuaikan 4 angka counter capaian yang tampil mencolok di Stats Bar halaman depan (Beranda).
            </p>
        </div>
        <a href="<?= SITE_URL ?>/index.php#capaian" target="_blank" class="btn btn-sm btn-outline-secondary" style="font-size: 0.8rem; border-radius: 6px;">
            Lihat di Beranda &rarr;
        </a>
    </div>

    <form method="POST">
        <input type="hidden" name="action" value="update_stats">
        <div class="row g-3">
            <!-- 1. Tahun Berdiri -->
            <div class="col-md-3 col-6">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem; color: #334155;">
                    Tahun Berdiri (+)
                </label>
                <div class="input-group">
                    <input type="number" name="stat_tahun" class="form-control" value="<?= e($stat_tahun) ?>" required style="border-radius: 8px 0 0 8px;">
                    <span class="input-group-text" style="background:#F8FAFC; font-weight:700; color:var(--gold); border-radius:0 8px 8px 0;">+</span>
                </div>
                <small class="text-muted" style="font-size:0.75rem;">Label: Tahun Berdiri</small>
            </div>

            <!-- 2. Program Studi -->
            <div class="col-md-3 col-6">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem; color: #334155;">
                    Program Studi (+)
                </label>
                <div class="input-group">
                    <input type="number" name="stat_prodi" class="form-control" placeholder="Auto (<?= $actual_prodi_count ?>)" value="<?= e($stat_prodi) ?>" style="border-radius: 8px 0 0 8px;">
                    <span class="input-group-text" style="background:#F8FAFC; font-weight:700; color:var(--gold); border-radius:0 8px 8px 0;">+</span>
                </div>
                <small class="text-muted" style="font-size:0.75rem;">Kosongkan = otomatis dari database (<?= $actual_prodi_count ?>)</small>
            </div>

            <!-- 3. Prodi Terakreditasi -->
            <div class="col-md-3 col-6">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem; color: #334155;">
                    Prodi Terakreditasi (%)
                </label>
                <div class="input-group">
                    <input type="number" name="stat_akreditasi" class="form-control" placeholder="Auto (<?= $actual_akred_pct ?>%)" value="<?= e($stat_akreditasi) ?>" min="0" max="100" style="border-radius: 8px 0 0 8px;">
                    <span class="input-group-text" style="background:#F8FAFC; font-weight:700; color:var(--gold); border-radius:0 8px 8px 0;">%</span>
                </div>
                <small class="text-muted" style="font-size:0.75rem;">Kosongkan = otomatis dari database (<?= $actual_akred_pct ?>%)</small>
            </div>

            <!-- 4. Dokumen Mutu -->
            <div class="col-md-3 col-6">
                <label class="form-label" style="font-weight: 600; font-size: 0.85rem; color: #334155;">
                    Dokumen Mutu (+)
                </label>
                <div class="input-group">
                    <input type="number" name="stat_dokumen" class="form-control" placeholder="Auto (<?= $actual_doc_count ?>)" value="<?= e($stat_dokumen) ?>" style="border-radius: 8px 0 0 8px;">
                    <span class="input-group-text" style="background:#F8FAFC; font-weight:700; color:var(--gold); border-radius:0 8px 8px 0;">+</span>
                </div>
                <small class="text-muted" style="font-size:0.75rem;">Kosongkan = otomatis dari database (<?= $actual_doc_count ?>)</small>
            </div>
        </div>

        <div class="d-flex justify-content-end mt-3 pt-2" style="border-top: 1px dashed #E2E8F0;">
            <button type="submit" class="btn-save" style="margin-top: 0; padding: 0.55rem 1.4rem; font-size: 0.875rem;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                </svg>
                Simpan Statistik Capaian
            </button>
        </div>
    </form>
</div>

<!-- Pengaturan Layout -->
<div class="admin-table-wrap mb-4" style="padding: 1.5rem;">
    <h5 class="mb-3" style="font-weight: 600; color: var(--navy); font-size: 1.1rem;">Pengaturan Layout Beranda</h5>
    <form method="post" class="d-flex align-items-center gap-3">
        <input type="hidden" name="action" value="update_layout">
        <div style="flex-grow: 1; max-width: 300px;">
            <select name="layout_penghargaan" class="form-control" style="border-radius: 8px;">
                <option value="slider" <?= $current_layout === 'slider' ? 'selected' : '' ?>>Tampilan Slider / Carousel (Geser)</option>
                <option value="grid" <?= $current_layout === 'grid' ? 'selected' : '' ?>>Tampilan Grid (Kotak Berjajar)</option>
            </select>
        </div>
        <button type="submit" class="btn-save" style="margin-top: 0; padding: 0.6rem 1.5rem; border-radius: 8px;">Simpan Layout</button>
    </form>
</div>

<div class="admin-table-wrap">
    <div class="admin-table-topbar">
        <div class="admin-table-title">Daftar Penghargaan & Prestasi (<?= count($peng_list) ?>)</div>
        <a href="penghargaan-form.php" class="btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Penghargaan
        </a>
    </div>

    <?php if (empty($peng_list)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-muted);">
        Belum ada data penghargaan. <a href="penghargaan-form.php" class="text-purple font-weight-600">Tambah sekarang</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="40" class="text-center">#</th>
                    <th width="90">Sampul</th>
                    <th>Judul Penghargaan</th>
                    <th width="220">Instansi / Lembaga</th>
                    <th width="90" class="text-center">Tahun</th>
                    <th width="130">Berkas Asli</th>
                    <th width="130">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($peng_list as $i => $d): 
                    $p_preview = getOrGeneratePdfPreview($d['file_path'] ?? '', 'penghargaan');
                ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:0.8rem;text-align:center;"><?= $i + 1 ?></td>
                    <td>
                        <?php if ($p_preview && file_exists(__DIR__ . '/../uploads/penghargaan/' . $p_preview)): ?>
                            <a href="<?= SITE_URL ?>/uploads/penghargaan/<?= e($d['file_path']) ?>" target="_blank" title="Buka berkas penuh">
                                <div style="width:72px;height:52px;border-radius:8px;overflow:hidden;border:1px solid #CBD5E1;background:#ffffff;box-shadow:0 2px 6px rgba(0,0,0,0.06);position:relative;display:flex;align-items:center;justify-content:center;">
                                    <img src="<?= SITE_URL ?>/uploads/penghargaan/<?= e($p_preview) ?>" alt="Sampul" style="width:100%;height:100%;object-fit:cover;">
                                    <?php if ($d['is_pdf']): ?>
                                        <span style="position:absolute;bottom:2px;right:2px;background:rgba(220,38,38,0.92);color:#fff;font-size:0.58rem;font-weight:800;padding:1px 4px;border-radius:3px;line-height:1.1;">PDF</span>
                                    <?php endif; ?>
                                </div>
                            </a>
                        <?php else: ?>
                            <div style="width:72px;height:52px;border-radius:8px;background:#F1F5F9;border:1px solid #E2E8F0;display:flex;align-items:center;justify-content:center;color:#94A3B8;font-size:0.68rem;text-align:center;">
                                No Sampul
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:600;color:var(--navy);font-size:0.92rem;line-height:1.4;"><?= e($d['judul']) ?></div>
                    </td>
                    <td>
                        <div style="font-size:0.85rem;color:var(--text-muted);"><?= e($d['instansi'] ?: '-') ?></div>
                    </td>
                    <td class="text-center">
                        <span style="background-color: var(--purple, #6A1B9A); color: #FFFFFF; padding: 0.25rem 0.65rem; border-radius: 12px; font-weight: 700; font-size: 0.8rem; display: inline-block; letter-spacing: 0.5px;">
                            <?= e($d['tahun']) ?>
                        </span>
                    </td>
                    <td>
                        <?php if ($d['file_path'] && file_exists(__DIR__ . '/../uploads/penghargaan/' . $d['file_path'])): ?>
                            <a href="<?= SITE_URL ?>/uploads/penghargaan/<?= e($d['file_path']) ?>" target="_blank" class="text-decoration-none d-inline-flex align-items-center gap-1">
                                <?php if ($d['is_pdf']): ?>
                                    <span class="badge bg-danger-subtle text-danger border border-danger-subtle px-2 py-1" style="font-size:0.75rem;">
                                        <i class="bi bi-file-earmark-pdf-fill me-1"></i> Buka PDF
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-primary-subtle text-primary border border-primary-subtle px-2 py-1" style="font-size:0.75rem;">
                                        <i class="bi bi-file-earmark-image-fill me-1"></i> Buka Gambar
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php else: ?>
                            <span style="font-size:0.78rem;color:var(--text-light);">File tidak ada</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <a href="penghargaan-form.php?id=<?= $d['id'] ?>" class="btn-action btn-edit" style="text-decoration:none;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Edit
                            </a>
                            <a href="penghargaan-list.php?delete=<?= $d['id'] ?>" class="btn-action btn-delete" style="text-decoration:none;" onclick="return confirm('Yakin hapus penghargaan ini?')">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                </svg>
                                Hapus
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
