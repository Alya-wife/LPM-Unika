<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Jadwal Pelaksanaan AMI';
$db = getDB();

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM kalender_ami WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Jadwal kegiatan AMI berhasil dihapus.';
    redirect(SITE_URL . '/admin/kalender-ami-list.php');
}

// Handle Status Toggle
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $db->prepare("UPDATE kalender_ami SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Status kegiatan AMI berhasil diperbarui.';
    redirect(SITE_URL . '/admin/kalender-ami-list.php');
}

// Filter Tahun Akademik
$filter_tahun = trim($_GET['tahun'] ?? '');

$query = "SELECT * FROM kalender_ami";
$params = [];
if ($filter_tahun !== '') {
    $query .= " WHERE tahun_akademik = ?";
    $params[] = $filter_tahun;
}
$query .= " ORDER BY tahun_akademik DESC, urutan ASC, id ASC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$list = $stmt->fetchAll(PDO::FETCH_OBJ);

// Get list of distinct academic years for filter
$tahun_list = $db->query("SELECT DISTINCT tahun_akademik FROM kalender_ami ORDER BY tahun_akademik DESC")->fetchAll(PDO::FETCH_COLUMN);

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4">
    <?= e($flash) ?>
</div>
<?php endif; ?>

<div class="admin-table-wrap">
    <div class="admin-table-topbar flex-wrap gap-3">
        <div>
            <div class="admin-table-title">Daftar Jadwal Pelaksanaan AMI (<?= count($list) ?>)</div>
            <div class="text-muted small">Kelola rincian tanggal dan kegiatan Audit Mutu Internal per tahun akademik.</div>
        </div>
        <div class="d-flex align-items-center gap-2">
            <!-- Filter Form -->
            <form method="get" class="d-flex align-items-center gap-2">
                <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()">
                    <option value="">-- Semua Periode --</option>
                    <?php foreach ($tahun_list as $th): ?>
                    <option value="<?= e($th) ?>" <?= $filter_tahun === $th ? 'selected' : '' ?>><?= e($th) ?></option>
                    <?php endforeach; ?>
                </select>
            </form>
            <a href="kalender-ami-form.php" class="btn-add">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Tambah Jadwal AMI
            </a>
        </div>
    </div>

    <?php if (empty($list)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-muted);">
        Belum ada jadwal pelaksanaan AMI<?= $filter_tahun ? ' untuk periode ' . e($filter_tahun) : '' ?>.
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="80">Urutan</th>
                    <th>Tahun Akademik</th>
                    <th>Tanggal / Waktu Pelaksanaan</th>
                    <th>Keterangan Kegiatan</th>
                    <th>Status</th>
                    <th width="140">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $item): ?>
                <tr>
                    <td class="text-center fw-bold"><?= $item->urutan ?></td>
                    <td><span class="badge bg-navy px-2 py-1"><?= e($item->tahun_akademik) ?></span></td>
                    <td class="fw-bold" style="color:var(--purple);"><?= e($item->bulan_tahun) ?></td>
                    <td><?= nl2br(e($item->kegiatan)) ?></td>
                    <td>
                        <a href="kalender-ami-list.php?toggle=<?= $item->id ?>" class="badge text-decoration-none <?= $item->is_active ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $item->is_active ? 'Aktif' : 'Non-Aktif' ?>
                        </a>
                    </td>
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <a href="kalender-ami-form.php?id=<?= $item->id ?>" class="btn-action btn-edit">Edit</a>
                            <a href="kalender-ami-list.php?delete=<?= $item->id ?>" class="btn-action btn-delete" onclick="return confirm('Yakin hapus data agenda AMI ini?')">Hapus</a>
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
