<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Siklus AMI (1 s.d. 5)';
$db = getDB();

// 1. Ambil Master Periode
$periodes = $db->query("SELECT * FROM ami_periode ORDER BY urutan ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
if (empty($periodes)) {
    // Fallback jika belum ada
    $periodes = [
        ['id' => 1, 'nama_periode' => '2025/2026', 'is_active' => 1],
        ['id' => 2, 'nama_periode' => '2026/2027', 'is_active' => 1]
    ];
}

$active_tab = trim($_GET['tab'] ?? 'isian');
if (!in_array($active_tab, ['isian', 'siklus1', 'siklus23', 'siklus4', 'siklus5'])) {
    $active_tab = 'isian';
}

$selected_periode = trim($_GET['periode'] ?? '');
if ($selected_periode === '' || !in_array($selected_periode, array_column($periodes, 'nama_periode'))) {
    $selected_periode = $periodes[0]['nama_periode'];
}

// 2. Handle Actions (Delete items)
if (isset($_GET['delete_kegiatan']) && is_numeric($_GET['delete_kegiatan'])) {
    $del_id = (int)$_GET['delete_kegiatan'];
    $db->prepare("DELETE FROM ami_siklus1_kegiatan WHERE id = ?")->execute([$del_id]);
    $_SESSION['flash'] = 'Dokumen kegiatan Siklus 1 berhasil dihapus.';
    redirect("ami-siklus-list.php?tab=siklus1&periode=" . urlencode($selected_periode));
}

if (isset($_GET['delete_opening']) && is_numeric($_GET['delete_opening'])) {
    $del_id = (int)$_GET['delete_opening'];
    $db->prepare("DELETE FROM ami_siklus1_opening WHERE id = ?")->execute([$del_id]);
    $_SESSION['flash'] = 'Foto opening meeting Siklus 1 berhasil dihapus.';
    redirect("ami-siklus-list.php?tab=siklus1&periode=" . urlencode($selected_periode));
}

if (isset($_GET['delete_dok4']) && is_numeric($_GET['delete_dok4'])) {
    $del_id = (int)$_GET['delete_dok4'];
    $db->prepare("DELETE FROM ami_siklus4_dokumen WHERE id = ?")->execute([$del_id]);
    $_SESSION['flash'] = 'Dokumen Siklus 4 berhasil dihapus.';
    redirect("ami-siklus-list.php?tab=siklus4&periode=" . urlencode($selected_periode));
}

if (isset($_GET['delete_dokt4']) && is_numeric($_GET['delete_dokt4'])) {
    $del_id = (int)$_GET['delete_dokt4'];
    $db->prepare("DELETE FROM ami_siklus4_dokumentasi WHERE id = ?")->execute([$del_id]);
    $_SESSION['flash'] = 'Dokumentasi audit lapangan Siklus 4 berhasil dihapus.';
    redirect("ami-siklus-list.php?tab=siklus4&periode=" . urlencode($selected_periode));
}

if (isset($_GET['delete_rtm5']) && is_numeric($_GET['delete_rtm5'])) {
    $del_id = (int)$_GET['delete_rtm5'];
    $db->prepare("DELETE FROM ami_siklus5_rtm WHERE id = ?")->execute([$del_id]);
    $_SESSION['flash'] = 'Data Rapat Tinjauan Manajemen (Siklus 5) berhasil dihapus.';
    redirect("ami-siklus-list.php?tab=siklus5&periode=" . urlencode($selected_periode));
}

// 3. Handle Save Isian 5 Tahapan Siklus AMI
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_isian_siklus'])) {
    setPengaturan('ami_siklus_banner_desc', trim($_POST['ami_siklus_banner_desc'] ?? ''));

    setPengaturan('ami_stage1_title', trim($_POST['ami_stage1_title'] ?? ''));
    setPengaturan('ami_stage1_desc', trim($_POST['ami_stage1_desc'] ?? ''));

    setPengaturan('ami_stage2_title', trim($_POST['ami_stage2_title'] ?? ''));
    setPengaturan('ami_stage2_desc', trim($_POST['ami_stage2_desc'] ?? ''));

    setPengaturan('ami_stage3_title', trim($_POST['ami_stage3_title'] ?? ''));
    setPengaturan('ami_stage3_desc', trim($_POST['ami_stage3_desc'] ?? ''));

    setPengaturan('ami_stage4_title', trim($_POST['ami_stage4_title'] ?? ''));
    setPengaturan('ami_stage4_desc', trim($_POST['ami_stage4_desc'] ?? ''));

    setPengaturan('ami_stage5_title', trim($_POST['ami_stage5_title'] ?? ''));
    setPengaturan('ami_stage5_desc', trim($_POST['ami_stage5_desc'] ?? ''));

    $_SESSION['flash'] = 'Isian judul dan narasi 5 tahapan Siklus AMI berhasil diperbarui.';
    redirect("ami-siklus-list.php?tab=isian&periode=" . urlencode($selected_periode));
}

// 4. Handle Save Pengaturan Siklus 2 & 3 (e-AMI Links)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_eami_links'])) {
    setPengaturan('ami_siklus2_judul', trim($_POST['ami_siklus2_judul'] ?? 'e-AMI: Siklus 2'));
    setPengaturan('ami_siklus2_url', trim($_POST['ami_siklus2_url'] ?? ''));
    setPengaturan('ami_siklus2_status', trim($_POST['ami_siklus2_status'] ?? 'coming_soon'));
    setPengaturan('ami_siklus2_desc', trim($_POST['ami_siklus2_desc'] ?? ''));

    setPengaturan('ami_siklus3_judul', trim($_POST['ami_siklus3_judul'] ?? 'e-AMI: Siklus 3'));
    setPengaturan('ami_siklus3_url', trim($_POST['ami_siklus3_url'] ?? ''));
    setPengaturan('ami_siklus3_status', trim($_POST['ami_siklus3_status'] ?? 'coming_soon'));
    setPengaturan('ami_siklus3_desc', trim($_POST['ami_siklus3_desc'] ?? ''));

    $_SESSION['flash'] = 'Pengaturan link e-AMI Siklus 2 dan Siklus 3 berhasil disimpan.';
    redirect("ami-siklus-list.php?tab=siklus23&periode=" . urlencode($selected_periode));
}

// 5. Query Data Sesuai Periode
// Siklus 1
$stmt_keg = $db->prepare("SELECT * FROM ami_siklus1_kegiatan WHERE periode = ? ORDER BY urutan ASC, id DESC");
$stmt_keg->execute([$selected_periode]);
$siklus1_kegiatan = $stmt_keg->fetchAll(PDO::FETCH_ASSOC);

$stmt_open = $db->prepare("SELECT * FROM ami_siklus1_opening WHERE periode = ? ORDER BY urutan ASC, id DESC");
$stmt_open->execute([$selected_periode]);
$siklus1_opening = $stmt_open->fetchAll(PDO::FETCH_ASSOC);

// Siklus 4
$stmt_dok4 = $db->prepare("SELECT * FROM ami_siklus4_dokumen WHERE periode = ? ORDER BY jenis ASC, urutan ASC, id DESC");
$stmt_dok4->execute([$selected_periode]);
$siklus4_dokumen = $stmt_dok4->fetchAll(PDO::FETCH_ASSOC);

$stmt_dokt4 = $db->prepare("SELECT * FROM ami_siklus4_dokumentasi WHERE periode = ? ORDER BY tingkat ASC, fakultas ASC, prodi ASC, id DESC");
$stmt_dokt4->execute([$selected_periode]);
$siklus4_dokumentasi = $stmt_dokt4->fetchAll(PDO::FETCH_ASSOC);

// Siklus 5
$stmt_rtm5 = $db->prepare("SELECT * FROM ami_siklus5_rtm WHERE periode = ? ORDER BY CASE WHEN tingkat = 'universitas' THEN 1 WHEN tingkat = 'fakultas' THEN 2 ELSE 3 END ASC, fakultas ASC, id DESC");
$stmt_rtm5->execute([$selected_periode]);
$siklus5_rtm = $stmt_rtm5->fetchAll(PDO::FETCH_ASSOC);

// Pengaturan Isian 5 Tahapan Siklus
$st_banner_desc = getPengaturan('ami_siklus_banner_desc', 'Rangkaian terintegrasi tahapan Siklus 1 hingga Siklus 5 AMI Universitas Katolik Soegijapranata untuk menjamin standar mutu pendidikan tinggi yang unggul dan berkelanjutan.');
$st1_title = getPengaturan('ami_stage1_title', 'Perencanaan & Sosialisasi');
$st1_desc  = getPengaturan('ami_stage1_desc', 'Penetapan jadwal siklus audit, penunjukan tim auditor tersertifikasi, dan pengiriman surat pemberitahuan ke seluruh auditee.');
$st2_title = getPengaturan('ami_stage2_title', 'Pengisian Dokumen Kinerja (DED)');
$st2_desc  = getPengaturan('ami_stage2_desc', 'Program Studi dan Unit Kerja mengisi instrumen evaluasi diri dan mengunggah bukti fisik pendukung.');
$st3_title = getPengaturan('ami_stage3_title', 'Audit Dokumen (Desk Evaluation)');
$st3_desc  = getPengaturan('ami_stage3_desc', 'Auditor memeriksa kecukupan dan kesesuaian dokumen bukti kerja terhadap standar mutu sebelum visitasi.');
$st4_title = getPengaturan('ami_stage4_title', 'Audit Lapangan (Visitasi)');
$st4_desc  = getPengaturan('ami_stage4_desc', 'Auditor melakukan verifikasi langsung, wawancara auditee, konfirmasi temuan KTS (Ketidaksesuaian), dan penandatanganan berita acara.');
$st5_title = getPengaturan('ami_stage5_title', 'Rapat Tinjauan Manajemen (RTM)');
$st5_desc  = getPengaturan('ami_stage5_desc', 'Penyampaian rekapitulasi temuan audit kepada Rektorat dan Pimpinan Unit untuk perumusan Rencana Tindak Lanjut (RTL).');

// Pengaturan Siklus 2 & 3
$s2_judul = getPengaturan('ami_siklus2_judul', 'e-AMI: Siklus 2');
$s2_url = getPengaturan('ami_siklus2_url', '');
$s2_status = getPengaturan('ami_siklus2_status', 'coming_soon');
$s2_desc = getPengaturan('ami_siklus2_desc', 'Portal sistem e-AMI untuk pelaksanaan audit desk evaluation Siklus 2.');

$s3_judul = getPengaturan('ami_siklus3_judul', 'e-AMI: Siklus 3');
$s3_url = getPengaturan('ami_siklus3_url', '');
$s3_status = getPengaturan('ami_siklus3_status', 'coming_soon');
$s3_desc = getPengaturan('ami_siklus3_desc', 'Portal sistem e-AMI untuk pelaksanaan tahapan Siklus 3.');

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4">
    <i class="bi bi-check-circle-fill me-2"></i><?= e($flash) ?>
</div>
<?php endif; ?>

<!-- Top Header & Filter Periode -->
<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--navy);"><i class="bi bi-arrow-repeat me-2 text-primary"></i>Kelola Siklus AMI (1 s.d. 5)</h4>
        <div class="text-muted small">Kelola informasi rangkaian kegiatan, pembukaan, tautan e-AMI, dokumen audit lapangan, serta hasil RTM.</div>
    </div>

    <!-- Filter Periode Switcher -->
    <div class="d-flex align-items-center gap-2 bg-white p-2 rounded-3 border shadow-sm">
        <label class="small fw-bold text-muted text-nowrap mb-0 ps-1"><i class="bi bi-calendar-event me-1"></i>Periode Aktif:</label>
        <select class="form-select form-select-sm fw-bold border-primary" style="width:auto;min-width:140px;" onchange="location.href='?tab=<?= e($active_tab) ?>&periode=' + encodeURIComponent(this.value)">
            <?php foreach ($periodes as $p): ?>
            <option value="<?= e($p['nama_periode']) ?>" <?= $selected_periode === $p['nama_periode'] ? 'selected' : '' ?>>
                <?= e($p['nama_periode']) ?>
            </option>
            <?php endforeach; ?>
        </select>
        <a href="ami-periode.php" class="btn btn-sm btn-outline-secondary" title="Kelola Master Periode">
            <i class="bi bi-gear-fill"></i>
        </a>
    </div>
</div>

<!-- Navigasi Tabs Siklus AMI -->
<ul class="nav nav-pills custom-admin-tabs mb-4 p-2 bg-white rounded-3 border shadow-sm flex-nowrap overflow-auto" id="amiTab" role="tablist">
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $active_tab === 'isian' ? 'active' : '' ?>" href="?tab=isian&periode=<?= urlencode($selected_periode) ?>">
            <i class="bi bi-diagram-3-fill me-1"></i> Isian 5 Tahapan Siklus
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $active_tab === 'siklus1' ? 'active' : '' ?>" href="?tab=siklus1&periode=<?= urlencode($selected_periode) ?>">
            <i class="bi bi-1-circle-fill me-1"></i> Siklus 1: Kegiatan &amp; Opening
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $active_tab === 'siklus23' ? 'active' : '' ?>" href="?tab=siklus23&periode=<?= urlencode($selected_periode) ?>">
            <i class="bi bi-link-45deg me-1"></i> Siklus 2 &amp; 3: e-AMI Hyperlink
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $active_tab === 'siklus4' ? 'active' : '' ?>" href="?tab=siklus4&periode=<?= urlencode($selected_periode) ?>">
            <i class="bi bi-4-circle-fill me-1"></i> Siklus 4: Audit Lapangan
        </a>
    </li>
    <li class="nav-item" role="presentation">
        <a class="nav-link <?= $active_tab === 'siklus5' ? 'active' : '' ?>" href="?tab=siklus5&periode=<?= urlencode($selected_periode) ?>">
            <i class="bi bi-5-circle-fill me-1"></i> Siklus 5: RTM
        </a>
    </li>
</ul>

<style>
.custom-admin-tabs .nav-link {
    color: #475569;
    font-weight: 600;
    font-size: 0.88rem;
    padding: 0.55rem 1rem;
    border-radius: 8px;
    white-space: nowrap;
    transition: all 0.2s ease;
}
.custom-admin-tabs .nav-link:hover {
    color: var(--navy);
    background: #f1f5f9;
}
.custom-admin-tabs .nav-link.active {
    color: #fff;
    background: var(--navy);
    box-shadow: 0 4px 12px rgba(10,25,47,0.15);
}
</style>

<!-- TAB CONTENT -->

<?php if ($active_tab === 'isian'): ?>
<!-- ========================================== -->
<!-- TAB ISIAN: KELOLA 5 TAHAPAN SIKLUS AMI     -->
<!-- ========================================== -->
<div class="admin-table-wrap p-4 p-md-5 mb-4">
    <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom flex-wrap gap-2">
        <div>
            <h4 class="fw-bold mb-1" style="color:var(--navy);"><i class="bi bi-diagram-3-fill me-2 text-primary"></i>Kelola Isian &amp; Narasi 5 Tahapan Siklus AMI</h4>
            <p class="text-muted small mb-0">
                Atur judul dan deskripsi ringkas untuk masing-masing kartu Siklus 1 sampai Siklus 5 yang ditampilkan pada navigasi atas halaman publik Siklus AMI.
            </p>
        </div>
        <a href="<?= SITE_URL ?>/siklus-ami.php" target="_blank" class="btn btn-sm btn-outline-primary">
            <i class="bi bi-eye me-1"></i> Lihat Halaman Publik Siklus AMI
        </a>
    </div>

    <form method="post">
        <!-- 1. Teks Banner Pengantar -->
        <div class="p-3 bg-light rounded-3 border mb-4">
            <h6 class="fw-bold text-dark mb-2"><i class="bi bi-card-heading me-2 text-primary"></i>Subjudul / Deskripsi Banner Siklus AMI</h6>
            <div class="mb-0">
                <textarea name="ami_siklus_banner_desc" rows="2" class="form-control"><?= e($st_banner_desc) ?></textarea>
                <div class="form-text">Teks penjelasan ringkas di bawah judul besar banner halaman publik Siklus AMI.</div>
            </div>
        </div>

        <!-- 2. Form Isian 5 Kartu Siklus -->
        <h6 class="fw-bold text-dark mb-3"><i class="bi bi-123 me-2 text-primary"></i>Isian Judul &amp; Deskripsi 5 Kartu Siklus</h6>
        
        <div class="row g-4">
            <!-- Siklus 1 -->
            <div class="col-md-6 col-lg-4">
                <div class="p-3 bg-white rounded-3 border h-100 shadow-sm" style="border-top: 4px solid #071739 !important;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div style="width:32px;height:32px;border-radius:50%;background:#071739;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9rem;">1</div>
                        <h6 class="fw-bold mb-0" style="color:var(--navy);">Siklus 1</h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Judul Tahap <span class="text-danger">*</span></label>
                        <input type="text" name="ami_stage1_title" class="form-control" value="<?= e($st1_title) ?>" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small">Deskripsi Ringkas <span class="text-danger">*</span></label>
                        <textarea name="ami_stage1_desc" rows="4" class="form-control" required><?= e($st1_desc) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Siklus 2 -->
            <div class="col-md-6 col-lg-4">
                <div class="p-3 bg-white rounded-3 border h-100 shadow-sm" style="border-top: 4px solid #071739 !important;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div style="width:32px;height:32px;border-radius:50%;background:#071739;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9rem;">2</div>
                        <h6 class="fw-bold mb-0" style="color:var(--navy);">Siklus 2</h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Judul Tahap <span class="text-danger">*</span></label>
                        <input type="text" name="ami_stage2_title" class="form-control" value="<?= e($st2_title) ?>" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small">Deskripsi Ringkas <span class="text-danger">*</span></label>
                        <textarea name="ami_stage2_desc" rows="4" class="form-control" required><?= e($st2_desc) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Siklus 3 -->
            <div class="col-md-6 col-lg-4">
                <div class="p-3 bg-white rounded-3 border h-100 shadow-sm" style="border-top: 4px solid #071739 !important;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div style="width:32px;height:32px;border-radius:50%;background:#071739;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9rem;">3</div>
                        <h6 class="fw-bold mb-0" style="color:var(--navy);">Siklus 3</h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Judul Tahap <span class="text-danger">*</span></label>
                        <input type="text" name="ami_stage3_title" class="form-control" value="<?= e($st3_title) ?>" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small">Deskripsi Ringkas <span class="text-danger">*</span></label>
                        <textarea name="ami_stage3_desc" rows="4" class="form-control" required><?= e($st3_desc) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Siklus 4 -->
            <div class="col-md-6 col-lg-6">
                <div class="p-3 bg-white rounded-3 border h-100 shadow-sm" style="border-top: 4px solid #071739 !important;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div style="width:32px;height:32px;border-radius:50%;background:#071739;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9rem;">4</div>
                        <h6 class="fw-bold mb-0" style="color:var(--navy);">Siklus 4</h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Judul Tahap <span class="text-danger">*</span></label>
                        <input type="text" name="ami_stage4_title" class="form-control" value="<?= e($st4_title) ?>" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small">Deskripsi Ringkas <span class="text-danger">*</span></label>
                        <textarea name="ami_stage4_desc" rows="4" class="form-control" required><?= e($st4_desc) ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Siklus 5 -->
            <div class="col-md-12 col-lg-6">
                <div class="p-3 bg-white rounded-3 border h-100 shadow-sm" style="border-top: 4px solid #071739 !important;">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <div style="width:32px;height:32px;border-radius:50%;background:#071739;color:#fff;display:flex;align-items:center;justify-content:center;font-weight:800;font-size:0.9rem;">5</div>
                        <h6 class="fw-bold mb-0" style="color:var(--navy);">Siklus 5</h6>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small">Judul Tahap <span class="text-danger">*</span></label>
                        <input type="text" name="ami_stage5_title" class="form-control" value="<?= e($st5_title) ?>" required>
                    </div>
                    <div class="mb-0">
                        <label class="form-label fw-bold small">Deskripsi Ringkas <span class="text-danger">*</span></label>
                        <textarea name="ami_stage5_desc" rows="4" class="form-control" required><?= e($st5_desc) ?></textarea>
                    </div>
                </div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-3 mt-4 pt-3 border-top">
            <button type="submit" name="save_isian_siklus" class="btn btn-primary px-4 py-2">
                <i class="bi bi-save me-1"></i> Simpan Isian Siklus AMI
            </button>
            <a href="ami-siklus-list.php?tab=isian&periode=<?= urlencode($selected_periode) ?>" class="btn btn-light px-3 py-2">
                Batal
            </a>
        </div>
    </form>
</div>

<?php elseif ($active_tab === 'siklus1'): ?>
<!-- ========================================== -->
<!-- TAB 1: SIKLUS 1 (Kegiatan & Opening Meeting) -->
<!-- ========================================== -->
<div class="row g-4">
    <!-- Subseksi 1: Rangkaian Kegiatan Dokumen Softfile -->
    <div class="col-lg-6">
        <div class="admin-table-wrap h-100">
            <div class="admin-table-topbar flex-wrap gap-2">
                <div>
                    <div class="admin-table-title"><i class="bi bi-file-earmark-text text-primary me-2"></i>Rangkaian Kegiatan AMI (<?= count($siklus1_kegiatan) ?>)</div>
                    <div class="text-muted small">Upload softfile dokumen panduan &amp; rangkaian kegiatan periode <?= e($selected_periode) ?>.</div>
                </div>
                <a href="ami-siklus1-form.php?type=kegiatan&periode=<?= urlencode($selected_periode) ?>" class="btn-add btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Dokumen
                </a>
            </div>

            <?php if (empty($siklus1_kegiatan)): ?>
            <div class="p-4 text-center text-muted">
                Belum ada dokumen rangkaian kegiatan untuk periode <?= e($selected_periode) ?>.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th width="50" class="text-center">#</th>
                            <th>Judul &amp; File Dokumen</th>
                            <th width="100">Ukuran</th>
                            <th width="100" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($siklus1_kegiatan as $idx => $keg): ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $keg['urutan'] ?></td>
                            <td>
                                <div class="fw-bold" style="color:var(--navy);"><?= e($keg['judul']) ?></div>
                                <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                                    <a href="<?= SITE_URL ?>/uploads/<?= e($keg['file_dokumen']) ?>" target="_blank" class="text-decoration-none text-primary">
                                        <i class="bi bi-download me-1"></i><?= basename($keg['file_dokumen']) ?>
                                    </a>
                                </div>
                                <?php if (!empty($keg['keterangan'])): ?>
                                <div class="text-muted small mt-1 font-monospace" style="font-size:0.75rem;"><?= e(truncate($keg['keterangan'], 80)) ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span class="badge bg-light text-dark"><?= e($keg['file_size'] ?? '-') ?></span></td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <a href="ami-siklus1-form.php?type=kegiatan&id=<?= $keg['id'] ?>&periode=<?= urlencode($selected_periode) ?>" class="btn-action btn-edit" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="?tab=siklus1&periode=<?= urlencode($selected_periode) ?>&delete_kegiatan=<?= $keg['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus dokumen <?= e($keg['judul']) ?>?')" title="Hapus">
                                        <i class="bi bi-trash"></i>
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
    </div>

    <!-- Subseksi 2: Opening Meeting (Foto, Judul, Tanggal) -->
    <div class="col-lg-6">
        <div class="admin-table-wrap h-100">
            <div class="admin-table-topbar flex-wrap gap-2">
                <div>
                    <div class="admin-table-title"><i class="bi bi-camera-fill text-purple me-2"></i>Foto Opening Meeting AMI (<?= count($siklus1_opening) ?>)</div>
                    <div class="text-muted small">Galeri foto pembukaan AMI dengan tanggal kegiatan periode <?= e($selected_periode) ?>.</div>
                </div>
                <a href="ami-siklus1-form.php?type=opening&periode=<?= urlencode($selected_periode) ?>" class="btn-add btn-sm" style="background:var(--purple);border-color:var(--purple);">
                    <i class="bi bi-plus-lg me-1"></i> Tambah Foto
                </a>
            </div>

            <?php if (empty($siklus1_opening)): ?>
            <div class="p-4 text-center text-muted">
                Belum ada foto opening meeting untuk periode <?= e($selected_periode) ?>.
            </div>
            <?php else: ?>
            <div class="p-3">
                <div class="row g-3">
                    <?php foreach ($siklus1_opening as $op): ?>
                    <div class="col-sm-6">
                        <div class="card h-100 border shadow-sm overflow-hidden">
                            <div class="position-relative" style="height:140px;background:#000;">
                                <img src="<?= SITE_URL ?>/uploads/<?= e($op['foto']) ?>" alt="<?= e($op['judul']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                <?php if (!empty($op['tanggal_kegiatan'])): ?>
                                <span class="position-absolute top-0 start-0 m-2 badge bg-dark bg-opacity-75">
                                    <i class="bi bi-calendar-event me-1"></i><?= formatTanggal($op['tanggal_kegiatan']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="card-body p-3 d-flex flex-column">
                                <h6 class="fw-bold mb-1" style="color:var(--navy);font-size:0.9rem;"><?= e($op['judul']) ?></h6>
                                <?php if (!empty($op['keterangan'])): ?>
                                <p class="text-muted small mb-2 flex-grow-1" style="font-size:0.8rem;"><?= e(truncate($op['keterangan'], 80)) ?></p>
                                <?php endif; ?>
                                <div class="d-flex justify-content-end gap-1 mt-auto pt-2 border-top">
                                    <a href="ami-siklus1-form.php?type=opening&id=<?= $op['id'] ?>&periode=<?= urlencode($selected_periode) ?>" class="btn btn-xs btn-outline-primary py-1 px-2" style="font-size:0.75rem;">
                                        <i class="bi bi-pencil-square"></i> Edit
                                    </a>
                                    <a href="?tab=siklus1&periode=<?= urlencode($selected_periode) ?>&delete_opening=<?= $op['id'] ?>" class="btn btn-xs btn-outline-danger py-1 px-2" style="font-size:0.75rem;" onclick="return confirm('Hapus foto opening ini?')">
                                        <i class="bi bi-trash"></i> Hapus
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php elseif ($active_tab === 'siklus23'): ?>
<!-- ========================================== -->
<!-- TAB 2: SIKLUS 2 & 3 (e-AMI Hyperlink) -->
<!-- ========================================== -->
<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <div class="admin-table-wrap p-4 p-md-5">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                <div>
                    <h5 class="fw-bold mb-1" style="color:var(--navy);"><i class="bi bi-link-45deg me-2 text-primary"></i>Pengaturan Integrasi Hyperlink e-AMI (Siklus 2 &amp; 3)</h5>
                    <p class="text-muted small mb-0">Kelola tautan menuju portal e-AMI. Jika link belum tersedia, Anda dapat mengaktifkan status "Segera Hadir".</p>
                </div>
            </div>

            <form method="post">
                <input type="hidden" name="save_eami_links" value="1">

                <div class="row g-4">
                    <!-- SIKLUS 2 -->
                    <div class="col-md-6">
                        <div class="p-4 rounded-3 border h-100 bg-white" style="border-top:4px solid var(--primary) !important;">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-primary px-2 py-1">Siklus 2</span>
                                <h6 class="fw-bold mb-0 text-dark">Portal e-AMI Siklus 2</h6>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Judul Tombol / Kartu</label>
                                <input type="text" name="ami_siklus2_judul" class="form-control" value="<?= e($s2_judul) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Status Tautan</label>
                                <select name="ami_siklus2_status" class="form-select">
                                    <option value="active" <?= $s2_status === 'active' ? 'selected' : '' ?>>Aktif (Dapat Diklik Langsung)</option>
                                    <option value="coming_soon" <?= $s2_status === 'coming_soon' ? 'selected' : '' ?>>Segera Hadir / Belum Dibuka</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">URL Hyperlink e-AMI</label>
                                <input type="url" name="ami_siklus2_url" class="form-control" placeholder="https://e-ami.unika.ac.id/siklus2" value="<?= e($s2_url) ?>">
                                <div class="form-text">Masukkan alamat website e-AMI lengkap (dengan https://).</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Keterangan / Panduan Singkat</label>
                                <textarea name="ami_siklus2_desc" rows="3" class="form-control"><?= e($s2_desc) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- SIKLUS 3 -->
                    <div class="col-md-6">
                        <div class="p-4 rounded-3 border h-100 bg-white" style="border-top:4px solid var(--purple) !important;">
                            <div class="d-flex align-items-center gap-2 mb-3">
                                <span class="badge bg-purple px-2 py-1">Siklus 3</span>
                                <h6 class="fw-bold mb-0 text-dark">Portal e-AMI Siklus 3</h6>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Judul Tombol / Kartu</label>
                                <input type="text" name="ami_siklus3_judul" class="form-control" value="<?= e($s3_judul) ?>" required>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Status Tautan</label>
                                <select name="ami_siklus3_status" class="form-select">
                                    <option value="active" <?= $s3_status === 'active' ? 'selected' : '' ?>>Aktif (Dapat Diklik Langsung)</option>
                                    <option value="coming_soon" <?= $s3_status === 'coming_soon' ? 'selected' : '' ?>>Segera Hadir / Belum Dibuka</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">URL Hyperlink e-AMI</label>
                                <input type="url" name="ami_siklus3_url" class="form-control" placeholder="https://e-ami.unika.ac.id/siklus3" value="<?= e($s3_url) ?>">
                                <div class="form-text">Masukkan alamat website e-AMI lengkap (dengan https://).</div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small">Keterangan / Panduan Singkat</label>
                                <textarea name="ami_siklus3_desc" rows="3" class="form-control"><?= e($s3_desc) ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-save me-1"></i> Simpan Pengaturan e-AMI
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php elseif ($active_tab === 'siklus4'): ?>
<!-- ========================================== -->
<!-- TAB 3: SIKLUS 4 (Audit Lapangan) -->
<!-- ========================================== -->
<div class="vstack gap-4">
    <!-- Bagian 1 & 2: Dokumen Prosedur & Jadwal Audit Lapangan -->
    <div class="admin-table-wrap">
        <div class="admin-table-topbar flex-wrap gap-2">
            <div>
                <div class="admin-table-title"><i class="bi bi-file-earmark-ruled-fill text-primary me-2"></i>Dokumen Prosedur Mutu &amp; Jadwal Audit Lapangan (<?= count($siklus4_dokumen) ?>)</div>
                <div class="text-muted small">Kelola softfile dokumen Prosedur Sistem Mutu Audit Lapangan dan Jadwal Audit periode <?= e($selected_periode) ?>.</div>
            </div>
            <div class="d-flex gap-2">
                <a href="ami-siklus4-dok-form.php?jenis=prosedur&periode=<?= urlencode($selected_periode) ?>" class="btn-add btn-sm">
                    <i class="bi bi-plus-lg me-1"></i> Upload Prosedur Mutu
                </a>
                <a href="ami-siklus4-dok-form.php?jenis=jadwal&periode=<?= urlencode($selected_periode) ?>" class="btn-add btn-sm" style="background:#0284c7;border-color:#0284c7;">
                    <i class="bi bi-plus-lg me-1"></i> Upload Jadwal Audit
                </a>
            </div>
        </div>

        <?php if (empty($siklus4_dokumen)): ?>
        <div class="p-4 text-center text-muted">
            Belum ada dokumen prosedur atau jadwal audit lapangan untuk periode <?= e($selected_periode) ?>.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="160">Kategori Dokumen</th>
                        <th>Judul Dokumen</th>
                        <th width="120">Ukuran File</th>
                        <th width="120" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($siklus4_dokumen as $dok): ?>
                    <tr>
                        <td>
                            <?php if ($dok['jenis'] === 'prosedur'): ?>
                            <span class="badge bg-primary px-2 py-1"><i class="bi bi-shield-check me-1"></i>Prosedur Mutu</span>
                            <?php else: ?>
                            <span class="badge bg-info text-dark px-2 py-1"><i class="bi bi-calendar3 me-1"></i>Jadwal Audit</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold" style="color:var(--navy);"><?= e($dok['judul']) ?></div>
                            <div class="small text-muted d-flex align-items-center gap-2 mt-1">
                                <a href="<?= SITE_URL ?>/uploads/<?= e($dok['file_dokumen']) ?>" target="_blank" class="text-decoration-none text-primary">
                                    <i class="bi bi-file-earmark-arrow-down me-1"></i><?= basename($dok['file_dokumen']) ?>
                                </a>
                            </div>
                        </td>
                        <td><span class="badge bg-light text-dark"><?= e($dok['file_size'] ?? '-') ?></span></td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <a href="ami-siklus4-dok-form.php?id=<?= $dok['id'] ?>&periode=<?= urlencode($selected_periode) ?>" class="btn-action btn-edit" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="?tab=siklus4&periode=<?= urlencode($selected_periode) ?>&delete_dok4=<?= $dok['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus dokumen <?= e($dok['judul']) ?>?')" title="Hapus">
                                    <i class="bi bi-trash"></i>
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

    <!-- Bagian 3: Formulir Terpadu Dokumentasi Audit Lapangan (Tingkat Fakultas & Prodi) -->
    <div class="admin-table-wrap">
        <div class="admin-table-topbar flex-wrap gap-2">
            <div>
                <div class="admin-table-title"><i class="bi bi-journal-text text-purple me-2"></i>Dokumentasi Audit Lapangan Fakultas &amp; Program Studi (<?= count($siklus4_dokumentasi) ?>)</div>
                <div class="text-muted small">Memuat narasi berita acara, softfile BA, daftar hadir, dan galeri foto kegiatan periode <?= e($selected_periode) ?>.</div>
            </div>
            <a href="ami-siklus4-form.php?periode=<?= urlencode($selected_periode) ?>" class="btn-add btn-sm">
                <i class="bi bi-plus-lg me-1"></i> Tambah Dokumentasi
            </a>
        </div>

        <?php if (empty($siklus4_dokumentasi)): ?>
        <div class="p-4 text-center text-muted">
            Belum ada data dokumentasi audit lapangan untuk periode <?= e($selected_periode) ?>.
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="admin-table">
                <thead>
                    <tr>
                        <th width="140">Tingkat / Unit</th>
                        <th>Judul &amp; Narasi Berita Acara</th>
                        <th width="200">Lampiran Berkas</th>
                        <th width="120">Galeri Foto</th>
                        <th width="110" class="text-center">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($siklus4_dokumentasi as $item): ?>
                    <?php 
                    $photos = !empty($item['foto_kegiatan']) ? json_decode($item['foto_kegiatan'], true) : [];
                    ?>
                    <tr>
                        <td>
                            <?php if ($item['tingkat'] === 'prodi'): ?>
                            <span class="badge bg-purple px-2 py-1 mb-1">Tingkat Prodi</span>
                            <div class="fw-bold small" style="color:var(--navy);"><?= e($item['prodi']) ?></div>
                            <div class="text-muted" style="font-size:0.75rem;">Fakultas: <?= e($item['fakultas']) ?></div>
                            <?php else: ?>
                            <span class="badge bg-primary px-2 py-1 mb-1">Tingkat Fakultas</span>
                            <div class="fw-bold small" style="color:var(--navy);"><?= e($item['fakultas']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-bold" style="color:var(--navy);"><?= e($item['judul']) ?></div>
                            <?php if (!empty($item['narasi_berita_acara'])): ?>
                            <div class="text-muted small mt-1" style="font-size:0.82rem;line-height:1.5;">
                                <?= nl2br(e(truncate($item['narasi_berita_acara'], 120))) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="vstack gap-1">
                                <?php if (!empty($item['file_berita_acara'])): ?>
                                <a href="<?= SITE_URL ?>/uploads/<?= e($item['file_berita_acara']) ?>" target="_blank" class="small text-decoration-none text-primary d-inline-flex align-items-center">
                                    <i class="bi bi-file-earmark-check-fill text-primary me-1"></i> Softfile BA
                                </a>
                                <?php endif; ?>

                                <?php if (!empty($item['file_daftar_hadir'])): ?>
                                <a href="<?= SITE_URL ?>/uploads/<?= e($item['file_daftar_hadir']) ?>" target="_blank" class="small text-decoration-none text-success d-inline-flex align-items-center">
                                    <i class="bi bi-card-checklist text-success me-1"></i> Daftar Hadir
                                </a>
                                <?php endif; ?>

                                <?php if (empty($item['file_berita_acara']) && empty($item['file_daftar_hadir'])): ?>
                                <span class="text-muted small">-</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($photos)): ?>
                            <span class="badge bg-light text-dark border">
                                <i class="bi bi-images text-purple me-1"></i><?= count($photos) ?> Foto
                            </span>
                            <?php else: ?>
                            <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </td>
                        <td class="text-center">
                            <div class="d-inline-flex gap-1">
                                <a href="ami-siklus4-form.php?id=<?= $item['id'] ?>&periode=<?= urlencode($selected_periode) ?>" class="btn-action btn-edit" title="Edit">
                                    <i class="bi bi-pencil-square"></i>
                                </a>
                                <a href="?tab=siklus4&periode=<?= urlencode($selected_periode) ?>&delete_dokt4=<?= $item['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus dokumentasi <?= e($item['judul']) ?>?')" title="Hapus">
                                    <i class="bi bi-trash"></i>
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
</div>

<?php elseif ($active_tab === 'siklus5'): ?>
<!-- ========================================== -->
<!-- TAB 4: SIKLUS 5 (Rapat Tinjauan Manajemen) -->
<!-- ========================================== -->
<div class="admin-table-wrap">
    <div class="admin-table-topbar flex-wrap gap-2">
        <div>
            <div class="admin-table-title"><i class="bi bi-people-fill text-purple me-2"></i>Rapat Tinjauan Manajemen (RTM) - Tingkat Univ, Fakultas &amp; Prodi (<?= count($siklus5_rtm) ?>)</div>
            <div class="text-muted small">Kelola notulensi, berkas daftar hadir, dan foto kegiatan RTM periode <?= e($selected_periode) ?>.</div>
        </div>
        <a href="ami-siklus5-form.php?periode=<?= urlencode($selected_periode) ?>" class="btn-add btn-sm">
            <i class="bi bi-plus-lg me-1"></i> Tambah Data RTM
        </a>
    </div>

    <?php if (empty($siklus5_rtm)): ?>
    <div class="p-4 text-center text-muted">
        Belum ada data Rapat Tinjauan Manajemen untuk periode <?= e($selected_periode) ?>.
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="150">Tingkat / Unit</th>
                    <th>Judul &amp; Ringkasan Notulensi RTM</th>
                    <th width="200">Lampiran Dokumen</th>
                    <th width="120">Galeri Foto</th>
                    <th width="110" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($siklus5_rtm as $rtm): ?>
                <?php 
                $photos = !empty($rtm['foto_kegiatan']) ? json_decode($rtm['foto_kegiatan'], true) : [];
                ?>
                <tr>
                    <td>
                        <?php if ($rtm['tingkat'] === 'universitas'): ?>
                        <span class="badge bg-danger px-2 py-1 mb-1">Tingkat Universitas</span>
                        <div class="fw-bold small" style="color:var(--navy);">UNIKA Soegijapranata</div>
                        <?php elseif ($rtm['tingkat'] === 'fakultas'): ?>
                        <span class="badge bg-primary px-2 py-1 mb-1">Tingkat Fakultas</span>
                        <div class="fw-bold small" style="color:var(--navy);"><?= e($rtm['fakultas']) ?></div>
                        <?php else: ?>
                        <span class="badge bg-purple px-2 py-1 mb-1">Tingkat Program Studi</span>
                        <div class="fw-bold small" style="color:var(--navy);"><?= e($rtm['prodi']) ?></div>
                        <div class="text-muted" style="font-size:0.75rem;">Fakultas: <?= e($rtm['fakultas']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-bold" style="color:var(--navy);"><?= e($rtm['judul']) ?></div>
                        <?php if (!empty($rtm['notulensi'])): ?>
                        <div class="text-muted small mt-1" style="font-size:0.82rem;line-height:1.5;">
                            <?= nl2br(e(truncate($rtm['notulensi'], 130))) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="vstack gap-1">
                            <?php if (!empty($rtm['file_notulensi'])): ?>
                            <a href="<?= SITE_URL ?>/uploads/<?= e($rtm['file_notulensi']) ?>" target="_blank" class="small text-decoration-none text-primary d-inline-flex align-items-center">
                                <i class="bi bi-file-earmark-text-fill text-primary me-1"></i> File Notulensi
                            </a>
                            <?php endif; ?>

                            <?php if (!empty($rtm['file_daftar_hadir'])): ?>
                            <a href="<?= SITE_URL ?>/uploads/<?= e($rtm['file_daftar_hadir']) ?>" target="_blank" class="small text-decoration-none text-success d-inline-flex align-items-center">
                                <i class="bi bi-card-checklist text-success me-1"></i> Daftar Hadir
                            </a>
                            <?php endif; ?>

                            <?php if (empty($rtm['file_notulensi']) && empty($rtm['file_daftar_hadir'])): ?>
                            <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if (!empty($photos)): ?>
                        <span class="badge bg-light text-dark border">
                            <i class="bi bi-images text-purple me-1"></i><?= count($photos) ?> Foto
                        </span>
                        <?php else: ?>
                        <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex gap-1">
                            <a href="ami-siklus5-form.php?id=<?= $rtm['id'] ?>&periode=<?= urlencode($selected_periode) ?>" class="btn-action btn-edit" title="Edit">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            <a href="?tab=siklus5&periode=<?= urlencode($selected_periode) ?>&delete_rtm5=<?= $rtm['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus data RTM <?= e($rtm['judul']) ?>?')" title="Hapus">
                                <i class="bi bi-trash"></i>
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

<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
