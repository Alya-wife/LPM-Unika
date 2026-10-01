<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Pemeringkatan Kampus';
$current_admin = 'pemeringkatan-list';
$db = getDB();

// Handle Simpan Warna Segmentasi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_colors') {
    $c_int = trim($_POST['color_internasional'] ?? '#1E3A8A');
    $c_nas = trim($_POST['color_nasional'] ?? '#991B1B');
    $c_lok = trim($_POST['color_lokal'] ?? '#0D9488');

    if ($c_int) setPengaturan('pemeringkatan_color_internasional', $c_int);
    if ($c_nas) setPengaturan('pemeringkatan_color_nasional', $c_nas);
    if ($c_lok) setPengaturan('pemeringkatan_color_lokal', $c_lok);

    $_SESSION['flash'] = 'Pengaturan tema warna segmentasi Internasional, Nasional, dan Lokal berhasil disimpan!';
    redirect(SITE_URL . '/admin/pemeringkatan-list.php');
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT file_sertifikat FROM pemeringkatan WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if ($item) {
        if (!empty($item['file_sertifikat'])) {
            $f1 = __DIR__ . '/../uploads/pemeringkatan/' . $item['file_sertifikat'];
            $f2 = __DIR__ . '/../uploads/akreditasi/' . $item['file_sertifikat'];
            if (file_exists($f1)) @unlink($f1);
            if (file_exists($f2)) @unlink($f2);
        }
        $db->prepare("DELETE FROM pemeringkatan WHERE id = ?")->execute([$id]);
        $_SESSION['flash'] = 'Data pemeringkatan berhasil dihapus.';
    }
    redirect(SITE_URL . '/admin/pemeringkatan-list.php');
}

// Handle Toggle Status
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $db->prepare("UPDATE pemeringkatan SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Status keterlihatan pemeringkatan berhasil diperbarui.';
    redirect(SITE_URL . '/admin/pemeringkatan-list.php');
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$kategori_filter = trim($_GET['kategori'] ?? 'all');
if (!in_array($kategori_filter, ['all', 'internasional', 'nasional', 'lokal'])) {
    $kategori_filter = 'all';
}

$q = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM pemeringkatan WHERE 1=1";
$params = [];

if ($kategori_filter !== 'all') {
    $sql .= " AND kategori = ?";
    $params[] = $kategori_filter;
}

if ($q !== '') {
    $sql .= " AND (judul LIKE ? OR lembaga LIKE ? OR peringkat LIKE ? OR deskripsi LIKE ?)";
    $like = '%' . $q . '%';
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
    $params[] = $like;
}

// Urutkan per section: Internasional dahulu, kemudian Nasional, kemudian Lokal
$sql .= " ORDER BY CASE WHEN kategori = 'internasional' THEN 1 WHEN kategori = 'nasional' THEN 2 ELSE 3 END ASC, urutan ASC, id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung rekap per segmen
$counts = [
    'all'           => (int)$db->query("SELECT COUNT(*) FROM pemeringkatan")->fetchColumn(),
    'internasional' => (int)$db->query("SELECT COUNT(*) FROM pemeringkatan WHERE kategori = 'internasional'")->fetchColumn(),
    'nasional'      => (int)$db->query("SELECT COUNT(*) FROM pemeringkatan WHERE kategori = 'nasional'")->fetchColumn(),
    'lokal'         => (int)$db->query("SELECT COUNT(*) FROM pemeringkatan WHERE kategori = 'lokal'")->fetchColumn(),
];

$cur_color_int = getPengaturan('pemeringkatan_color_internasional', '#1E3A8A');
$cur_color_nas = getPengaturan('pemeringkatan_color_nasional', '#991B1B');
$cur_color_lok = getPengaturan('pemeringkatan_color_lokal', '#0D9488');

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">
            Kelola Pemeringkatan Kampus
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Kelola data pemeringkatan per section (<strong>Internasional</strong>, <strong>Nasional</strong>, <strong>Lokal</strong>) serta atur tema warnanya.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= SITE_URL ?>/pemeringkatan.php" target="_blank" class="btn btn-sm btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-1" style="border-radius:8px;">
            <i class="bi bi-box-arrow-up-right"></i> Lihat Halaman Publik
        </a>
        <button type="button" class="btn btn-sm btn-outline-primary fw-bold d-inline-flex align-items-center gap-1" data-bs-toggle="collapse" data-bs-target="#colorSettingsCard" style="border-radius:8px;">
            <i class="bi bi-palette-fill"></i> Atur Tema Warna
        </button>
        <a href="<?= SITE_URL ?>/admin/pemeringkatan-form.php" class="btn btn-sm btn-primary fw-bold d-inline-flex align-items-center gap-1" style="border-radius:8px;background:var(--navy);border:none;">
            <i class="bi bi-plus-lg"></i> Tambah Pemeringkatan
        </a>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4">
    <i class="bi bi-check-circle-fill me-2 fs-5"></i>
    <span><?= e($flash) ?></span>
</div>
<?php endif; ?>

<!-- Form Pengaturan Tema Warna Segmentasi (Bisa Diubah Admin) -->
<div class="collapse mb-4" id="colorSettingsCard">
    <div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden" style="border-left: 5px solid var(--navy) !important;">
        <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
            <div class="d-flex align-items-center gap-2">
                <i class="bi bi-palette-fill text-primary fs-5"></i>
                <h5 class="m-0 fw-bold" style="font-size:1rem;color:var(--navy);">Pengaturan Tema Warna Segmentasi Wilayah</h5>
            </div>
            <button type="button" class="btn-close" data-bs-toggle="collapse" data-bs-target="#colorSettingsCard" aria-label="Close"></button>
        </div>
        <div class="card-body p-4">
            <p class="text-muted small mb-4">
                Pilih warna aksen utama untuk masing-masing seksi pemeringkatan. Warna ini akan diterapkan pada judul seksi, kartu pemeringkatan, badge, dan angka peringkat di web publik.
            </p>
            <form method="POST">
                <input type="hidden" name="action" value="save_colors">
                <div class="row g-4 mb-4">
                    <!-- 1. Internasional -->
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 border" style="background:#F8FAFC;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-globe2 fs-5" style="color:<?= e($cur_color_int) ?>;"></i>
                                <span class="fw-bold small text-dark">Seksi Internasional</span>
                            </div>
                            <label class="form-label small text-muted">Warna Aksen Utama</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <input type="color" name="color_internasional" id="colorIntInput" class="form-control form-control-color border-0 p-0" value="<?= e($cur_color_int) ?>" style="width:44px;height:38px;border-radius:8px;cursor:pointer;">
                                <input type="text" id="colorIntText" class="form-control form-control-sm font-monospace fw-bold" value="<?= e($cur_color_int) ?>" oninput="document.getElementById('colorIntInput').value=this.value;">
                            </div>
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="badge border py-1 px-2" style="background:#1E3A8A;color:#fff;" onclick="setPreset('colorInt', '#1E3A8A')">Navy</button>
                                <button type="button" class="badge border py-1 px-2" style="background:#4338CA;color:#fff;" onclick="setPreset('colorInt', '#4338CA')">Indigo</button>
                                <button type="button" class="badge border py-1 px-2" style="background:#6B21A8;color:#fff;" onclick="setPreset('colorInt', '#6B21A8')">Purple</button>
                                <button type="button" class="badge border py-1 px-2" style="background:#0F172A;color:#fff;" onclick="setPreset('colorInt', '#0F172A')">Dark Slate</button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Nasional -->
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 border" style="background:#F8FAFC;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-flag-fill fs-5" style="color:<?= e($cur_color_nas) ?>;"></i>
                                <span class="fw-bold small text-dark">Seksi Nasional</span>
                            </div>
                            <label class="form-label small text-muted">Warna Aksen Utama</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <input type="color" name="color_nasional" id="colorNasInput" class="form-control form-control-color border-0 p-0" value="<?= e($cur_color_nas) ?>" style="width:44px;height:38px;border-radius:8px;cursor:pointer;">
                                <input type="text" id="colorNasText" class="form-control form-control-sm font-monospace fw-bold" value="<?= e($cur_color_nas) ?>" oninput="document.getElementById('colorNasInput').value=this.value;">
                            </div>
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="badge border py-1 px-2" style="background:#991B1B;color:#fff;" onclick="setPreset('colorNas', '#991B1B')">Crimson</button>
                                <button type="button" class="badge border py-1 px-2" style="background:#B45309;color:#fff;" onclick="setPreset('colorNas', '#B45309')">Warm Gold</button>
                                <button type="button" class="badge border py-1 px-2" style="background:#831843;color:#fff;" onclick="setPreset('colorNas', '#831843')">Wine</button>
                                <button type="button" class="badge border py-1 px-2" style="background:#C2410C;color:#fff;" onclick="setPreset('colorNas', '#C2410C')">Rust</button>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Lokal -->
                    <div class="col-md-4">
                        <div class="p-3 rounded-3 border" style="background:#F8FAFC;">
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <i class="bi bi-geo-alt-fill fs-5" style="color:<?= e($cur_color_lok) ?>;"></i>
                                <span class="fw-bold small text-dark">Seksi Lokal</span>
                            </div>
                            <label class="form-label small text-muted">Warna Aksen Utama</label>
                            <div class="d-flex align-items-center gap-2 mb-2">
                                <input type="color" name="color_lokal" id="colorLokInput" class="form-control form-control-color border-0 p-0" value="<?= e($cur_color_lok) ?>" style="width:44px;height:38px;border-radius:8px;cursor:pointer;">
                                <input type="text" id="colorLokText" class="form-control form-control-sm font-monospace fw-bold" value="<?= e($cur_color_lok) ?>" oninput="document.getElementById('colorLokInput').value=this.value;">
                            </div>
                            <div class="d-flex gap-1 flex-wrap">
                                <button type="button" class="badge border py-1 px-2" style="background:#0D9488;color:#fff;" onclick="setPreset('colorLok', '#0D9488')">Deep Teal</button>
                                <button type="button" class="badge border py-1 px-2" style="background:#0369A1;color:#fff;" onclick="setPreset('colorLok', '#0369A1')">Ocean Blue</button>
                                <button type="button" class="badge border py-1 px-2" style="background:#15803D;color:#fff;" onclick="setPreset('colorLok', '#15803D')">Emerald</button>
                                <button type="button" class="badge border py-1 px-2" style="background:#0891B2;color:#fff;" onclick="setPreset('colorLok', '#0891B2')">Cyan Dark</button>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2">
                    <button type="submit" class="btn btn-sm btn-primary fw-bold px-4" style="background:var(--navy);border:none;border-radius:8px;">
                        <i class="bi bi-check2 me-1"></i> Terapkan &amp; Simpan Warna
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Tabs Segmentasi Filter -->
<div class="card border-0 rounded-4 shadow-sm bg-white mb-4">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="?kategori=all<?= $q ? '&q='.urlencode($q) : '' ?>" class="btn btn-sm <?= $kategori_filter === 'all' ? 'btn-dark' : 'btn-outline-secondary' ?> rounded-pill px-3 py-1 fw-bold">
                Semua Section (<?= $counts['all'] ?>)
            </a>
            <a href="?kategori=internasional<?= $q ? '&q='.urlencode($q) : '' ?>" class="btn btn-sm rounded-pill px-3 py-1 fw-bold" style="<?= $kategori_filter === 'internasional' ? 'background:'.$cur_color_int.';color:#fff;' : 'border:1.5px solid '.$cur_color_int.';color:'.$cur_color_int.';' ?>">
                <i class="bi bi-globe2 me-1"></i> 1. Internasional (<?= $counts['internasional'] ?>)
            </a>
            <a href="?kategori=nasional<?= $q ? '&q='.urlencode($q) : '' ?>" class="btn btn-sm rounded-pill px-3 py-1 fw-bold" style="<?= $kategori_filter === 'nasional' ? 'background:'.$cur_color_nas.';color:#fff;' : 'border:1.5px solid '.$cur_color_nas.';color:'.$cur_color_nas.';' ?>">
                <i class="bi bi-flag-fill me-1"></i> 2. Nasional (<?= $counts['nasional'] ?>)
            </a>
            <a href="?kategori=lokal<?= $q ? '&q='.urlencode($q) : '' ?>" class="btn btn-sm rounded-pill px-3 py-1 fw-bold" style="<?= $kategori_filter === 'lokal' ? 'background:'.$cur_color_lok.';color:#fff;' : 'border:1.5px solid '.$cur_color_lok.';color:'.$cur_color_lok.';' ?>">
                <i class="bi bi-geo-alt-fill me-1"></i> 3. Lokal (<?= $counts['lokal'] ?>)
            </a>
        </div>

        <form method="GET" class="d-flex gap-2 m-0">
            <?php if ($kategori_filter !== 'all'): ?>
            <input type="hidden" name="kategori" value="<?= e($kategori_filter) ?>">
            <?php endif; ?>
            <div class="input-group input-group-sm" style="width:260px;">
                <input type="text" name="q" class="form-control" placeholder="Cari judul, lembaga, peringkat..." value="<?= e($q) ?>">
                <button type="submit" class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
            </div>
            <?php if ($q): ?>
            <a href="?kategori=<?= e($kategori_filter) ?>" class="btn btn-sm btn-outline-danger"><i class="bi bi-x-lg"></i></a>
            <?php endif; ?>
        </form>
    </div>

    <div class="admin-table-wrap m-0 border-0 shadow-none">
        <table class="admin-table">
            <thead>
                <tr>
                    <th style="width:50px;" class="text-center">No</th>
                    <th style="width:140px;">Section / Segmen</th>
                    <th style="width:150px;">Peringkat</th>
                    <th>Judul &amp; Lembaga Penilai</th>
                    <th style="width:160px;">Sumber &amp; Berkas</th>
                    <th style="width:75px;" class="text-center">Tahun</th>
                    <th style="width:80px;" class="text-center">Status</th>
                    <th style="width:110px;" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list)): ?>
                <tr>
                    <td colspan="8" class="text-center py-5 text-muted">
                        <i class="bi bi-award fs-1 d-block opacity-50 mb-2"></i>
                        Tidak ada data pemeringkatan yang sesuai kriteria pencarian.
                        <div class="mt-2">
                            <a href="pemeringkatan-form.php" class="btn btn-sm btn-primary rounded-pill px-3">
                                <i class="bi bi-plus-lg me-1"></i> Tambah Pemeringkatan
                            </a>
                        </div>
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($list as $i => $item): ?>
                <?php 
                // Tentukan warna aktif kartu
                $item_color = $item['warna'];
                if (!$item_color) {
                    if ($item['kategori'] === 'internasional') $item_color = $cur_color_int;
                    elseif ($item['kategori'] === 'nasional') $item_color = $cur_color_nas;
                    else $item_color = $cur_color_lok;
                }
                ?>
                <tr>
                    <td class="text-center text-muted fw-bold"><?= $i + 1 ?></td>
                    <td>
                        <?php if ($item['kategori'] === 'internasional'): ?>
                        <span class="badge text-white px-2 py-1 rounded-pill" style="font-size:0.75rem;background:<?= e($cur_color_int) ?>;">
                            <i class="bi bi-globe2 me-1"></i>Internasional
                        </span>
                        <?php elseif ($item['kategori'] === 'nasional'): ?>
                        <span class="badge text-white px-2 py-1 rounded-pill" style="font-size:0.75rem;background:<?= e($cur_color_nas) ?>;">
                            <i class="bi bi-flag-fill me-1"></i>Nasional
                        </span>
                        <?php else: ?>
                        <span class="badge text-white px-2 py-1 rounded-pill" style="font-size:0.75rem;background:<?= e($cur_color_lok) ?>;">
                            <i class="bi bi-geo-alt-fill me-1"></i>Lokal
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-bold" style="color:<?= e($item_color) ?>;font-size:1.05rem;">
                            <?= e($item['peringkat'] ?: '-') ?>
                        </div>
                        <?php if (!empty($item['peringkat_dari'])): ?>
                        <small class="text-muted d-block" style="font-size:0.75rem;"><?= e($item['peringkat_dari']) ?></small>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-bold text-dark mb-1" style="font-size:0.92rem;"><?= e($item['judul']) ?></div>
                        <div class="d-flex align-items-center gap-2 flex-wrap">
                            <span class="badge bg-light text-dark border" style="font-size:0.75rem;">
                                <i class="bi bi-building me-1" style="color:<?= e($item_color) ?>;"></i><?= e($item['lembaga']) ?>
                            </span>
                            <?php if (!empty($item['badge_teks'])): ?>
                            <span class="badge bg-light-subtle text-muted border" style="font-size:0.72rem;">
                                <?= e($item['badge_teks']) ?>
                            </span>
                            <?php endif; ?>
                            <?php if (!empty($item['warna'])): ?>
                            <span class="badge border py-0 px-1 rounded-circle" style="background:<?= e($item['warna']) ?>;width:12px;height:12px;" title="Warna khusus: <?= e($item['warna']) ?>"></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($item['deskripsi'])): ?>
                        <div class="text-muted small mt-1 text-truncate" style="max-width:380px;font-size:0.78rem;">
                            <?= e($item['deskripsi']) ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="vstack gap-1">
                            <?php if (!empty($item['link_url'])): ?>
                            <a href="<?= e($item['link_url']) ?>" target="_blank" class="btn btn-sm btn-outline-primary py-0 px-2 d-inline-flex align-items-center gap-1" style="font-size:0.75rem;width:fit-content;">
                                <i class="bi bi-box-arrow-up-right"></i> Link Sumber
                            </a>
                            <?php endif; ?>

                            <?php if (!empty($item['file_sertifikat'])): ?>
                            <?php 
                            $f_url = SITE_URL . '/uploads/pemeringkatan/' . $item['file_sertifikat'];
                            if (!file_exists(__DIR__ . '/../uploads/pemeringkatan/' . $item['file_sertifikat'])) {
                                $f_url = SITE_URL . '/uploads/akreditasi/' . $item['file_sertifikat'];
                            }
                            ?>
                            <a href="<?= $f_url ?>" target="_blank" class="btn btn-sm btn-outline-success py-0 px-2 d-inline-flex align-items-center gap-1" style="font-size:0.75rem;width:fit-content;">
                                <i class="bi bi-file-earmark-pdf"></i> Berkas Sertifikat
                            </a>
                            <?php endif; ?>

                            <?php if (empty($item['link_url']) && empty($item['file_sertifikat'])): ?>
                            <span class="text-muted small">-</span>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td class="text-center fw-semibold text-muted">
                        <?= e($item['tahun'] ?: '-') ?>
                    </td>
                    <td class="text-center">
                        <a href="?toggle_status=<?= $item['id'] ?>&kategori=<?= e($kategori_filter) ?>" class="badge <?= $item['is_active'] ? 'bg-success' : 'bg-secondary' ?> text-decoration-none px-2 py-1" title="Klik untuk mengubah status">
                            <?= $item['is_active'] ? 'Aktif' : 'Draft' ?>
                        </a>
                    </td>
                    <td class="text-center">
                        <div class="d-inline-flex align-items-center gap-1">
                            <a href="pemeringkatan-form.php?id=<?= $item['id'] ?>" class="btn-action edit" title="Edit Data">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <a href="?delete=<?= $item['id'] ?>" class="btn-action delete" onclick="return confirm('Apakah Anda yakin ingin menghapus data pemeringkatan ini?');" title="Hapus Data">
                                <i class="bi bi-trash"></i>
                            </a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function setPreset(targetPrefix, hexColor) {
    document.getElementById(targetPrefix + 'Input').value = hexColor;
    document.getElementById(targetPrefix + 'Text').value = hexColor;
}
document.getElementById('colorIntInput').addEventListener('input', function() {
    document.getElementById('colorIntText').value = this.value;
});
document.getElementById('colorNasInput').addEventListener('input', function() {
    document.getElementById('colorNasText').value = this.value;
});
document.getElementById('colorLokInput').addEventListener('input', function() {
    document.getElementById('colorLokText').value = this.value;
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
