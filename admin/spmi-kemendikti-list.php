<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Manajemen Hasil SPMI Kemendikti Saintek';
$db = getDB();

// Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT file_pdf FROM spmi_kemendikti WHERE id = ?");
    $stmt->execute([$id]);
    $file = $stmt->fetchColumn();
    if ($file && file_exists(__DIR__ . '/../uploads/spmi_kemendikti/' . $file)) {
        unlink(__DIR__ . '/../uploads/spmi_kemendikti/' . $file);
    }
    $db->prepare("DELETE FROM spmi_kemendikti WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Dokumen Hasil SPMI Kemendikti Saintek berhasil dihapus.';
    redirect(SITE_URL . '/admin/spmi-kemendikti-list.php');
}

// Toggle Publish
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $db->prepare("UPDATE spmi_kemendikti SET is_published = NOT is_published WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Status publikasi dokumen diperbarui.';
    redirect(SITE_URL . '/admin/spmi-kemendikti-list.php');
}

$list = $db->query("SELECT * FROM spmi_kemendikti ORDER BY tahun DESC, urutan ASC, id DESC")->fetchAll(PDO::FETCH_OBJ);
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
    <div class="admin-table-topbar">
        <div>
            <div class="admin-table-title">Daftar Hasil SPMI Kemendikti Saintek (<?= count($list) ?>)</div>
            <div class="text-muted small">Upload dan kelola berkas/laporan Hasil SPMI Kemendikti Saintek.</div>
        </div>
        <a href="spmi-kemendikti-form.php" class="btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Dokumen Kemendikti
        </a>
    </div>

    <?php if (empty($list)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-muted);">
        Belum ada dokumen Hasil SPMI Kemendikti Saintek yang diunggah.
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="70">Urutan</th>
                    <th>Judul Dokumen / Laporan SPMI</th>
                    <th width="100">Tahun</th>
                    <th>File PDF</th>
                    <th>Status</th>
                    <th width="140">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($list as $item): ?>
                <tr>
                    <td class="text-center fw-bold"><?= $item->urutan ?></td>
                    <td>
                        <div style="font-weight:600;color:var(--navy);"><?= e($item->judul) ?></div>
                        <?php if ($item->deskripsi): ?>
                        <div style="font-size:0.8rem;color:var(--text-muted);"><?= e(truncate($item->deskripsi, 100)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><span class="badge bg-purple px-2 py-1"><?= e($item->tahun) ?></span></td>
                    <td>
                        <?php if ($item->file_pdf): ?>
                        <a href="<?= SITE_URL ?>/uploads/spmi_kemendikti/<?= e($item->file_pdf) ?>" target="_blank" class="fw-bold text-primary text-decoration-none small">
                            <i class="bi bi-file-earmark-pdf-fill"></i> Lihat PDF
                        </a>
                        <?php else: ?>
                        <span class="text-muted small">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="spmi-kemendikti-list.php?toggle=<?= $item->id ?>" class="badge text-decoration-none <?= $item->is_published ? 'bg-success' : 'bg-secondary' ?>">
                            <?= $item->is_published ? 'Dipublikasikan' : 'Draft' ?>
                        </a>
                    </td>
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <a href="spmi-kemendikti-form.php?id=<?= $item->id ?>" class="btn-action btn-edit">Edit</a>
                            <a href="spmi-kemendikti-list.php?delete=<?= $item->id ?>" class="btn-action btn-delete" onclick="return confirm('Yakin hapus dokumen ini?')">Hapus</a>
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
