<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Pemeringkatan Kampus';
$current_admin = 'pemeringkatan-list';
$db = getDB();

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
if (!in_array($kategori_filter, ['all', 'lokal', 'nasional', 'internasional'])) {
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

$sql .= " ORDER BY CASE WHEN kategori = 'lokal' THEN 1 WHEN kategori = 'nasional' THEN 2 ELSE 3 END ASC, urutan ASC, id DESC";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$list = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung rekap per segmen
$counts = [
    'all'           => (int)$db->query("SELECT COUNT(*) FROM pemeringkatan")->fetchColumn(),
    'lokal'         => (int)$db->query("SELECT COUNT(*) FROM pemeringkatan WHERE kategori = 'lokal'")->fetchColumn(),
    'nasional'      => (int)$db->query("SELECT COUNT(*) FROM pemeringkatan WHERE kategori = 'nasional'")->fetchColumn(),
    'internasional' => (int)$db->query("SELECT COUNT(*) FROM pemeringkatan WHERE kategori = 'internasional'")->fetchColumn(),
];

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">
            Kelola Pemeringkatan Kampus
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Kelola data pemeringkatan dan rekognisi resmi SCU pada skala <strong>Lokal</strong>, <strong>Nasional</strong>, dan <strong>Internasional</strong>.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= SITE_URL ?>/pemeringkatan.php" target="_blank" class="btn btn-sm btn-outline-secondary fw-semibold d-inline-flex align-items-center gap-1" style="border-radius:8px;">
            <i class="bi bi-box-arrow-up-right"></i> Lihat Halaman Publik
        </a>
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

<!-- Tabs Segmentasi Filter -->
<div class="card border-0 rounded-4 shadow-sm bg-white mb-4">
    <div class="p-3 border-bottom d-flex align-items-center justify-content-between flex-wrap gap-3">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <a href="?kategori=all<?= $q ? '&q='.urlencode($q) : '' ?>" class="btn btn-sm <?= $kategori_filter === 'all' ? 'btn-dark' : 'btn-outline-secondary' ?> rounded-pill px-3 py-1 fw-bold">
                Semua Segmen (<?= $counts['all'] ?>)
            </a>
            <a href="?kategori=lokal<?= $q ? '&q='.urlencode($q) : '' ?>" class="btn btn-sm <?= $kategori_filter === 'lokal' ? 'btn-primary' : 'btn-outline-primary' ?> rounded-pill px-3 py-1 fw-bold">
                <i class="bi bi-geo-alt-fill me-1"></i> Lokal (<?= $counts['lokal'] ?>)
            </a>
            <a href="?kategori=nasional<?= $q ? '&q='.urlencode($q) : '' ?>" class="btn btn-sm <?= $kategori_filter === 'nasional' ? 'btn-warning text-dark' : 'btn-outline-warning text-dark' ?> rounded-pill px-3 py-1 fw-bold">
                <i class="bi bi-flag-fill me-1"></i> Nasional (<?= $counts['nasional'] ?>)
            </a>
            <a href="?kategori=internasional<?= $q ? '&q='.urlencode($q) : '' ?>" class="btn btn-sm <?= $kategori_filter === 'internasional' ? 'btn-purple text-white' : 'btn-outline-purple' ?> rounded-pill px-3 py-1 fw-bold">
                <i class="bi bi-globe2 me-1"></i> Internasional (<?= $counts['internasional'] ?>)
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
                    <th style="width:130px;">Segmen</th>
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
                <tr>
                    <td class="text-center text-muted fw-bold"><?= $i + 1 ?></td>
                    <td>
                        <?php if ($item['kategori'] === 'lokal'): ?>
                        <span class="badge bg-primary px-2 py-1 rounded-pill" style="font-size:0.75rem;">
                            <i class="bi bi-geo-alt-fill me-1"></i>Lokal
                        </span>
                        <?php elseif ($item['kategori'] === 'nasional'): ?>
                        <span class="badge bg-warning text-dark px-2 py-1 rounded-pill" style="font-size:0.75rem;">
                            <i class="bi bi-flag-fill me-1"></i>Nasional
                        </span>
                        <?php else: ?>
                        <span class="badge bg-purple text-white px-2 py-1 rounded-pill" style="font-size:0.75rem;">
                            <i class="bi bi-globe2 me-1"></i>Internasional
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div class="fw-bold" style="color:var(--navy);font-size:1.05rem;">
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
                                <i class="bi bi-building me-1 text-primary"></i><?= e($item['lembaga']) ?>
                            </span>
                            <?php if (!empty($item['badge_teks'])): ?>
                            <span class="badge bg-light-subtle text-muted border" style="font-size:0.72rem;">
                                <?= e($item['badge_teks']) ?>
                            </span>
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

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
