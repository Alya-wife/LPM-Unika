<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Manajemen Dokumen SPMI';
$db = getDB();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id  = (int)$_GET['delete'];
    $row = $db->prepare("SELECT file_path FROM dokumen WHERE id = ?");
    $row->execute([$id]);
    $row = $row->fetch();
    if ($row && $row['file_path'] && file_exists(__DIR__ . '/../uploads/dokumen/' . $row['file_path'])) {
        unlink(__DIR__ . '/../uploads/dokumen/' . $row['file_path']);
    }
    $db->prepare("DELETE FROM dokumen WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Dokumen berhasil dihapus.';
    redirect(SITE_URL . '/admin/dokumen-list.php');
}

$filter_kategori = trim($_GET['kategori'] ?? '');
$kategori_options = $db->query("SELECT nama_kategori FROM kategori_dokumen ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);

if ($filter_kategori !== '') {
    $stmt = $db->prepare("SELECT * FROM dokumen WHERE CONVERT(kategori USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci ORDER BY created_at DESC");
    $stmt->execute([$filter_kategori]);
    $dok_list = $stmt->fetchAll();
} else {
    $dok_list = $db->query("SELECT * FROM dokumen ORDER BY kategori, created_at DESC")->fetchAll();
}

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

<div class="admin-table-wrap">
    <div class="admin-table-topbar d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <div class="admin-table-title">Daftar Dokumen SPMI (<?= count($dok_list) ?>)</div>
            <form method="GET" class="d-flex align-items-center gap-2">
                <select name="kategori" class="form-select form-select-sm" style="font-size:0.8rem;border-radius:6px;min-width:180px;" onchange="this.form.submit()">
                    <option value="">-- Semua Kategori Dokumen --</option>
                    <?php foreach ($kategori_options as $k_opt): ?>
                    <option value="<?= e($k_opt) ?>" <?= $filter_kategori === $k_opt ? 'selected' : '' ?>><?= e($k_opt) ?></option>
                    <?php endforeach; ?>
                </select>
                <?php if ($filter_kategori): ?>
                <a href="dokumen-list.php" class="btn btn-sm btn-outline-secondary" style="font-size:0.75rem;border-radius:6px;">Reset</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="d-flex align-items-center gap-2">
            <a href="kategori-dokumen.php" class="btn-action" style="background:#fff;border:1.5px solid var(--border);color:var(--navy);text-decoration:none;padding:0.45rem 0.9rem;">
                <i class="bi bi-tags me-1"></i> Kelola Kategori
            </a>
            <a href="dokumen-form.php" class="btn-add">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                </svg>
                Upload Dokumen
            </a>
        </div>
    </div>

    <?php if (empty($dok_list)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-muted);">
        Belum ada dokumen. <a href="dokumen-form.php" class="text-purple font-weight-600">Upload sekarang</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th>Nama Dokumen</th>
                    <th width="120">Kategori</th>
                    <th width="150">File</th>
                    <th width="120">Tanggal</th>
                    <th width="130">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($dok_list as $i => $d): ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:0.8rem;"><?= $i + 1 ?></td>
                    <td><div style="font-weight:500;"><?= e($d['nama_dokumen']) ?></div></td>
                    <td>
                        <?php
                        $kat_lc = strtolower($d['kategori']);
                        ?>
                        <span class="doc-cat-badge cat-<?= $kat_lc ?>"><?= e($d['kategori']) ?></span>
                    </td>
                    <td>
                        <?php if ($d['file_path'] && file_exists(__DIR__ . '/../uploads/dokumen/' . $d['file_path'])): ?>
                        <a href="<?= SITE_URL ?>/uploads/dokumen/<?= e($d['file_path']) ?>" class="btn-download" target="_blank">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                            </svg>
                            Unduh
                        </a>
                        <?php else: ?>
                        <span style="font-size:0.78rem;color:var(--text-light);">File tidak ada</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.8rem;color:var(--text-muted);"><?= formatTanggal($d['created_at']) ?></td>
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <a href="dokumen-form.php?id=<?= $d['id'] ?>" class="btn-action btn-edit" style="text-decoration:none;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Edit
                            </a>
                            <a href="dokumen-list.php?delete=<?= $d['id'] ?>" class="btn-action btn-delete" style="text-decoration:none;" onclick="return confirm('Yakin hapus dokumen ini?')">
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
