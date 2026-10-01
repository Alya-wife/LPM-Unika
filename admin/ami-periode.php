<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Master Periode AMI';
$db = getDB();

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM ami_periode WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Periode AMI berhasil dihapus.';
    redirect(SITE_URL . '/admin/ami-periode.php');
}

// Handle Toggle Active
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $db->prepare("UPDATE ami_periode SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Status periode berhasil diperbarui.';
    redirect(SITE_URL . '/admin/ami-periode.php');
}

// Handle Add / Edit
$edit_id = (int)($_GET['edit'] ?? 0);
$edit_data = null;
if ($edit_id > 0) {
    $stmt = $db->prepare("SELECT * FROM ami_periode WHERE id = ?");
    $stmt->execute([$edit_id]);
    $edit_data = $stmt->fetch(PDO::FETCH_ASSOC);
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_periode = trim($_POST['nama_periode'] ?? '');
    $urutan = (int)($_POST['urutan'] ?? 1);
    $is_active = isset($_POST['is_active']) ? 1 : 0;

    if ($nama_periode === '') {
        $error = 'Nama Periode (misal: 2025/2026) wajib diisi.';
    } else {
        if ($edit_id > 0) {
            $stmt = $db->prepare("UPDATE ami_periode SET nama_periode = ?, urutan = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$nama_periode, $urutan, $is_active, $edit_id]);
            $_SESSION['flash'] = 'Periode AMI berhasil diperbarui.';
        } else {
            $stmt = $db->prepare("INSERT INTO ami_periode (nama_periode, urutan, is_active) VALUES (?, ?, ?)");
            $stmt->execute([$nama_periode, $urutan, $is_active]);
            $_SESSION['flash'] = 'Periode AMI baru berhasil ditambahkan.';
        }
        redirect(SITE_URL . '/admin/ami-periode.php');
    }
}

$list = $db->query("SELECT * FROM ami_periode ORDER BY urutan ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4">
    <i class="bi bi-check-circle-fill me-2"></i><?= e($flash) ?>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert-lpm alert-danger mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Form Tambah / Edit -->
    <div class="col-lg-4">
        <div class="admin-table-wrap p-4">
            <h5 class="fw-bold mb-3" style="color:var(--navy);">
                <?= $edit_id ? '<i class="bi bi-pencil-square me-2"></i>Edit Periode' : '<i class="bi bi-plus-circle me-2"></i>Tambah Periode AMI' ?>
            </h5>
            <p class="text-muted small mb-4">
                Kelola periode tahun pelaksanaan audit AMI untuk filter Siklus 1, 4, dan 5.
            </p>

            <form method="post">
                <div class="mb-3">
                    <label class="form-label fw-bold small">Nama Periode <span class="text-danger">*</span></label>
                    <input type="text" name="nama_periode" class="form-control" placeholder="Contoh: 2025/2026" value="<?= e($edit_data['nama_periode'] ?? ($_POST['nama_periode'] ?? '')) ?>" required>
                    <div class="form-text">Format tahun lengkap, contoh: 2025/2026.</div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold small">Urutan Tampil</label>
                    <input type="number" name="urutan" class="form-control" value="<?= e($edit_data['urutan'] ?? ($_POST['urutan'] ?? 1)) ?>">
                </div>

                <div class="form-check form-switch mb-4">
                    <input class="form-check-input" type="checkbox" name="is_active" id="isActiveSwitch" value="1" <?= (!isset($edit_data) || !empty($edit_data['is_active'])) ? 'checked' : '' ?>>
                    <label class="form-check-label small fw-bold" for="isActiveSwitch">Aktifkan Periode Ini</label>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="bi bi-save me-1"></i> Simpan
                    </button>
                    <?php if ($edit_id): ?>
                    <a href="ami-periode.php" class="btn btn-light px-3">Batal</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabel Daftar Periode -->
    <div class="col-lg-8">
        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div>
                    <div class="admin-table-title">Daftar Periode AMI (<?= count($list) ?>)</div>
                    <div class="text-muted small">Periode yang aktif akan muncul pada opsi filter di halaman publik dan form input admin.</div>
                </div>
            </div>

            <?php if (empty($list)): ?>
            <div style="padding:3rem;text-align:center;color:var(--text-muted);">
                Belum ada periode AMI. Silakan tambahkan pada formulir di samping.
            </div>
            <?php else: ?>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th width="60" class="text-center">Urutan</th>
                            <th>Nama Periode</th>
                            <th width="120" class="text-center">Status</th>
                            <th width="130" class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($list as $p): ?>
                        <tr>
                            <td class="text-center fw-bold"><?= $p['urutan'] ?></td>
                            <td>
                                <strong style="color:var(--navy);font-size:0.95rem;"><?= e($p['nama_periode']) ?></strong>
                            </td>
                            <td class="text-center">
                                <a href="?toggle=<?= $p['id'] ?>" class="badge text-decoration-none <?= $p['is_active'] ? 'bg-success' : 'bg-secondary' ?>" title="Klik untuk ubah status">
                                    <?= $p['is_active'] ? '<i class="bi bi-check-circle me-1"></i>Aktif' : '<i class="bi bi-x-circle me-1"></i>Nonaktif' ?>
                                </a>
                            </td>
                            <td class="text-center">
                                <div class="d-inline-flex gap-1">
                                    <a href="?edit=<?= $p['id'] ?>" class="btn-action btn-edit" title="Edit">
                                        <i class="bi bi-pencil-square"></i>
                                    </a>
                                    <a href="?delete=<?= $p['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus periode <?= e($p['nama_periode']) ?>?')" title="Hapus">
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
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
