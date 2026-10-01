<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Pemeringkatan Kampus';
$db = getDB();

// Handle Force Sync dari EduRank
if (isset($_POST['action']) && $_POST['action'] === 'sync_edurank') {
    $crawled = getEduRankRankings(true);
    if (!empty($crawled['indonesia']['rank'])) {
        setPengaturan('edurank_indonesia_rank', (string)$crawled['indonesia']['rank']);
        setPengaturan('edurank_indonesia_of', (string)$crawled['indonesia']['total']);
        setPengaturan('edurank_semarang_rank', (string)$crawled['semarang']['rank']);
        setPengaturan('edurank_semarang_of', (string)$crawled['semarang']['total']);
        if (!empty($crawled['url'])) {
            setPengaturan('edurank_url', $crawled['url']);
        }
    }
    $_SESSION['flash'] = 'Data pemeringkatan EduRank.org berhasil disinkronkan secara langsung dari sumber resmi!';
    redirect(SITE_URL . '/admin/pemeringkatan-setting.php');
}

// Handle Simpan Semua Pengaturan Manual & GreenMetric
if (isset($_POST['action']) && $_POST['action'] === 'save_all') {
    // 1. URL Sumber
    $edurank_url = trim($_POST['edurank_url'] ?? 'https://edurank.org/uni/soegijapranata-catholic-university/rankings/');
    setPengaturan('edurank_url', $edurank_url);

    // 2. EduRank Indonesia
    setPengaturan('edurank_indonesia_rank', trim($_POST['edurank_indonesia_rank'] ?? '65'));
    setPengaturan('edurank_indonesia_of', trim($_POST['edurank_indonesia_of'] ?? '562'));
    setPengaturan('edurank_indonesia_badge', trim($_POST['edurank_indonesia_badge'] ?? 'EduRank Official ' . date('Y')));
    setPengaturan('edurank_desc_indonesia', trim($_POST['edurank_desc_indonesia'] ?? ''));

    // 3. EduRank Semarang
    setPengaturan('edurank_semarang_rank', trim($_POST['edurank_semarang_rank'] ?? '3'));
    setPengaturan('edurank_semarang_of', trim($_POST['edurank_semarang_of'] ?? '14'));
    setPengaturan('edurank_semarang_badge', trim($_POST['edurank_semarang_badge'] ?? 'EduRank Official ' . date('Y')));
    setPengaturan('edurank_desc_semarang', trim($_POST['edurank_desc_semarang'] ?? ''));

    // 4. UI GreenMetric
    setPengaturan('greenmetric_rank', trim($_POST['greenmetric_rank'] ?? '1398'));
    setPengaturan('greenmetric_scope', trim($_POST['greenmetric_scope'] ?? 'World'));
    setPengaturan('greenmetric_title', trim($_POST['greenmetric_title'] ?? 'UI GreenMetric'));
    setPengaturan('greenmetric_badge', trim($_POST['greenmetric_badge'] ?? 'UI GreenMetric Official 2025'));
    setPengaturan('greenmetric_desc', trim($_POST['greenmetric_desc'] ?? ''));

    // Handle Upload Sertifikat GreenMetric
    if (!empty($_POST['hapus_sertifikat_greenmetric'])) {
        $old_cert = getPengaturan('greenmetric_certificate', 'sertifikat_ui_greenmetric_2025.webp');
        if ($old_cert && $old_cert !== 'sertifikat_ui_greenmetric_2025.webp') {
            $f = __DIR__ . '/../uploads/akreditasi/' . $old_cert;
            if (file_exists($f)) @unlink($f);
        }
        setPengaturan('greenmetric_certificate', '');
    }

    if (!empty($_FILES['greenmetric_certificate']['name'])) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png', 'webp'];
        $ext = strtolower(pathinfo($_FILES['greenmetric_certificate']['name'], PATHINFO_EXTENSION));
        if (in_array($ext, $allowed)) {
            $upload_dir = __DIR__ . '/../uploads/akreditasi/';
            if (!is_dir($upload_dir)) mkdir($upload_dir, 0755, true);

            if ($ext === 'pdf') {
                $filename = 'sertifikat_greenmetric_' . time() . '.pdf';
                if (move_uploaded_file($_FILES['greenmetric_certificate']['tmp_name'], $upload_dir . $filename)) {
                    setPengaturan('greenmetric_certificate', $filename);
                }
            } else {
                $saved = convertAndSaveWebP($_FILES['greenmetric_certificate']['tmp_name'], $upload_dir, 'sertifikat_greenmetric_');
                if ($saved) {
                    setPengaturan('greenmetric_certificate', $saved);
                }
            }
        }
    }

    $_SESSION['flash'] = 'Semua pengaturan teks, angka pemeringkatan EduRank & UI GreenMetric berhasil disimpan!';
    redirect(SITE_URL . '/admin/pemeringkatan-setting.php');
}

$edurank_data = getEduRankRankings();

// Nilai EduRank dari DB dengan fallback crawler
$indonesia_rank = getPengaturan('edurank_indonesia_rank', (string)($edurank_data['indonesia']['rank'] ?? '65'));
$indonesia_of   = getPengaturan('edurank_indonesia_of', (string)($edurank_data['indonesia']['total'] ?? '562'));
$indonesia_badge= getPengaturan('edurank_indonesia_badge', 'EduRank Official ' . date('Y'));
$indonesia_desc = getPengaturan('edurank_desc_indonesia', 'Akreditasi Unggul dari Badan Akreditasi Nasional Perguruan Tinggi');

$semarang_rank  = getPengaturan('edurank_semarang_rank', (string)($edurank_data['semarang']['rank'] ?? '3'));
$semarang_of    = getPengaturan('edurank_semarang_of', (string)($edurank_data['semarang']['total'] ?? '14'));
$semarang_badge = getPengaturan('edurank_semarang_badge', 'EduRank Official ' . date('Y'));
$semarang_desc  = getPengaturan('edurank_desc_semarang', 'Peringkat perguruan tinggi terkemuka di Kota Semarang berdasarkan luaran riset akademik dan reputasi institusi.');

$edurank_url    = getPengaturan('edurank_url', $edurank_data['url'] ?? 'https://edurank.org/uni/soegijapranata-catholic-university/rankings/');
$last_sync      = $edurank_data['updated_at'] ?? date('Y-m-d H:i:s');

// Nilai GreenMetric
$gm_rank        = getPengaturan('greenmetric_rank', '1398');
$gm_scope       = getPengaturan('greenmetric_scope', 'World');
$gm_title       = getPengaturan('greenmetric_title', 'UI GreenMetric');
$gm_badge       = getPengaturan('greenmetric_badge', 'UI GreenMetric Official 2025');
$gm_desc        = getPengaturan('greenmetric_desc', 'Peringkat World\'s Most Sustainable University & kampus hijau di Kota Semarang dalam pengelolaan keberlanjutan dan lingkungan ramah energi.');
$gm_cert        = getPengaturan('greenmetric_certificate', 'sertifikat_ui_greenmetric_2025.webp');

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">
            Kelola Pemeringkatan Kampus
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Kelola data peringkat EduRank &amp; UI GreenMetric. Dapat disinkronkan secara otomatis maupun disunting manual untuk teks dan angka.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= SITE_URL ?>/pemeringkatan.php" target="_blank" class="btn btn-sm btn-outline-secondary fw-bold d-flex align-items-center gap-1" style="border-radius:8px;">
            <i class="bi bi-box-arrow-up-right"></i> Lihat Halaman Publik
        </a>
        <form method="POST" class="m-0">
            <input type="hidden" name="action" value="sync_edurank">
            <button type="submit" class="btn btn-sm btn-primary fw-bold d-flex align-items-center gap-1" style="border-radius:8px;background:var(--navy);border:none;">
                <i class="bi bi-arrow-repeat"></i> Sinkronkan Live EduRank
            </button>
        </form>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert" style="font-size:0.88rem;">
    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($flash) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Status Banner EduRank Sync -->
<div class="card mb-4 border-0 shadow-sm" style="background:#ffffff;border:1px solid #E2E8F0;border-left:5px solid #D97706 !important;border-radius:14px;">
    <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(217,119,6,0.1);color:#D97706;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">
                <i class="bi bi-patch-check-fill"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1" style="font-size:1.05rem;color:var(--navy);">Sinkronisasi Otomatis &amp; Edit Manual</h5>
                <div style="font-size:0.84rem;color:#64748B;">
                    <span>Sumber URL EduRank: <a href="<?= htmlspecialchars($edurank_url) ?>" target="_blank" class="text-primary text-decoration-none"><?= htmlspecialchars($edurank_url) ?></a></span>
                    <br>
                    <span>Terakhir Disinkronkan: <strong><?= date('d M Y, H:i', strtotime($last_sync)) ?> WIB</strong>. Anda dapat mengedit teks &amp; angka di bawah secara manual kapan saja.</span>
                </div>
            </div>
        </div>
    </div>
</div>

<form method="POST" enctype="multipart/form-data">
    <input type="hidden" name="action" value="save_all">

    <!-- TABEL 1: PENGATURAN EDURANK -->
    <div class="card border-0 rounded-4 shadow-sm bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-award text-warning fs-5"></i>
                <h5 class="m-0 fw-bold" style="font-size:1rem;color:var(--navy);">1. Pengaturan Pemeringkatan EduRank.org</h5>
            </div>
            <span class="badge bg-light text-muted border">EduRank Global</span>
        </div>
        <div class="card-body p-4">
            <div class="mb-4">
                <label class="form-label fw-bold small text-muted">URL Sumber EduRank (untuk tautan "Lihat di EduRank" &amp; crawl otomatis)</label>
                <div class="input-group">
                    <span class="input-group-text bg-light border-end-0"><i class="bi bi-link-45deg"></i></span>
                    <input type="url" name="edurank_url" class="form-control border-start-0" value="<?= htmlspecialchars($edurank_url) ?>" required>
                </div>
            </div>

            <div class="row g-4">
                <!-- Kartu Indonesia -->
                <div class="col-lg-6">
                    <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                        <h6 class="fw-bold mb-3" style="color:var(--navy);">
                            <i class="bi bi-geo-alt-fill text-danger me-1"></i> Kartu 1: In Indonesia (Tingkat Nasional)
                        </h6>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Peringkat (#)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text fw-bold">#</span>
                                    <input type="text" name="edurank_indonesia_rank" class="form-control fw-bold" value="<?= htmlspecialchars($indonesia_rank) ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Total Kampus (of)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">of</span>
                                    <input type="text" name="edurank_indonesia_of" class="form-control fw-bold" value="<?= htmlspecialchars($indonesia_of) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Badge Teks</label>
                            <input type="text" name="edurank_indonesia_badge" class="form-control form-control-sm" value="<?= htmlspecialchars($indonesia_badge) ?>">
                        </div>

                        <div>
                            <label class="form-label small fw-semibold">Deskripsi / Catatan Kartu</label>
                            <textarea name="edurank_desc_indonesia" rows="3" class="form-control form-control-sm"><?= htmlspecialchars($indonesia_desc) ?></textarea>
                        </div>
                    </div>
                </div>

                <!-- Kartu Semarang -->
                <div class="col-lg-6">
                    <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                        <h6 class="fw-bold mb-3" style="color:var(--navy);">
                            <i class="bi bi-buildings text-primary me-1"></i> Kartu 2: In Semarang (Tingkat Kota)
                        </h6>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Peringkat (#)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text fw-bold">#</span>
                                    <input type="text" name="edurank_semarang_rank" class="form-control fw-bold" value="<?= htmlspecialchars($semarang_rank) ?>" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Total Kampus (of)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text">of</span>
                                    <input type="text" name="edurank_semarang_of" class="form-control fw-bold" value="<?= htmlspecialchars($semarang_of) ?>" required>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Badge Teks</label>
                            <input type="text" name="edurank_semarang_badge" class="form-control form-control-sm" value="<?= htmlspecialchars($semarang_badge) ?>">
                        </div>

                        <div>
                            <label class="form-label small fw-semibold">Deskripsi / Catatan Kartu</label>
                            <textarea name="edurank_desc_semarang" rows="3" class="form-control form-control-sm"><?= htmlspecialchars($semarang_desc) ?></textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TABEL 2: PENGATURAN UI GREENMETRIC -->
    <div class="card border-0 rounded-4 shadow-sm bg-white mb-4 overflow-hidden">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-tree-fill text-success fs-5"></i>
                <h5 class="m-0 fw-bold" style="font-size:1rem;color:var(--navy);">2. Pengaturan UI GreenMetric</h5>
            </div>
            <span class="badge bg-success-subtle text-success border border-success-subtle fw-bold">Kampus Hijau &amp; Berkelanjutan</span>
        </div>
        <div class="card-body p-4">
            <div class="row g-4">
                <div class="col-lg-6">
                    <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                        <h6 class="fw-bold mb-3" style="color:var(--navy);">Peringkat &amp; Identitas Kartu</h6>

                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Peringkat (#)</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text fw-bold">#</span>
                                    <input type="text" name="greenmetric_rank" class="form-control fw-bold text-success" value="<?= htmlspecialchars($gm_rank) ?>" placeholder="1398" required>
                                </div>
                            </div>
                            <div class="col-6">
                                <label class="form-label small fw-semibold">Skala / Lingkup</label>
                                <input type="text" name="greenmetric_scope" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($gm_scope) ?>" placeholder="World" required>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Judul Kartu</label>
                            <input type="text" name="greenmetric_title" class="form-control form-control-sm fw-bold" value="<?= htmlspecialchars($gm_title) ?>" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label small fw-semibold">Badge Teks</label>
                            <input type="text" name="greenmetric_badge" class="form-control form-control-sm" value="<?= htmlspecialchars($gm_badge) ?>">
                        </div>

                        <div>
                            <label class="form-label small fw-semibold">Deskripsi Kartu</label>
                            <textarea name="greenmetric_desc" rows="3" class="form-control form-control-sm"><?= htmlspecialchars($gm_desc) ?></textarea>
                        </div>
                    </div>
                </div>

                <div class="col-lg-6">
                    <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                        <h6 class="fw-bold mb-3" style="color:var(--navy);">Berkas Sertifikat Resmi UI GreenMetric</h6>

                        <?php if ($gm_cert): ?>
                        <div class="p-3 mb-3 bg-white rounded-3 border d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center gap-2 text-truncate me-2">
                                <i class="bi bi-file-earmark-check-fill text-success fs-4"></i>
                                <div class="text-truncate">
                                    <div class="fw-bold small text-truncate"><?= htmlspecialchars($gm_cert) ?></div>
                                    <a href="<?= SITE_URL ?>/uploads/akreditasi/<?= htmlspecialchars($gm_cert) ?>" target="_blank" class="text-primary text-decoration-none" style="font-size:0.78rem;">
                                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka / Unduh Sertifikat
                                    </a>
                                </div>
                            </div>
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" name="hapus_sertifikat_greenmetric" value="1" id="hapusCert">
                                <label class="form-check-label text-danger small fw-semibold" for="hapusCert">
                                    Hapus
                                </label>
                            </div>
                        </div>
                        <?php else: ?>
                        <div class="alert alert-secondary py-2 small mb-3">
                            <i class="bi bi-info-circle me-1"></i> Belum ada sertifikat UI GreenMetric yang diunggah.
                        </div>
                        <?php endif; ?>

                        <div>
                            <label class="form-label small fw-semibold">Unggah Sertifikat Baru (PDF / WebP / JPG / PNG)</label>
                            <input type="file" name="greenmetric_certificate" class="form-control form-control-sm" accept=".pdf,.jpg,.jpeg,.png,.webp">
                            <small class="text-muted" style="font-size:0.75rem;">Maksimal 5MB. Gambar akan otomatis dioptimasi ke format WebP HD.</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tombol Simpan -->
    <div class="d-flex justify-content-end gap-2 mb-5">
        <a href="<?= SITE_URL ?>/admin/pemeringkatan-setting.php" class="btn btn-outline-secondary px-4 fw-semibold" style="border-radius:10px;">
            Batal
        </a>
        <button type="submit" class="btn btn-primary px-5 fw-bold" style="border-radius:10px;background:var(--navy);border:none;">
            <i class="bi bi-check2-circle me-1"></i> Simpan Semua Perubahan
        </button>
    </div>
</form>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
