<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Halaman Dinamis';
$db = getDB();

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM pages WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Halaman berhasil dihapus.';
    redirect(SITE_URL . '/admin/page-list.php');
}

$kategori_filter = trim($_GET['kategori'] ?? '');
$sql = "SELECT * FROM pages";
$params = [];
if ($kategori_filter) {
    $sql .= " WHERE kategori = ?";
    $params[] = $kategori_filter;
}
$sql .= " ORDER BY kategori ASC, judul ASC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$pages = $stmt->fetchAll();

$kategori_list = $db->query("SELECT DISTINCT kategori FROM pages ORDER BY kategori ASC")->fetchAll(PDO::FETCH_COLUMN);

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">Manajemen Halaman Statis &amp; Dinamis</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Kelola teks materi untuk sitemap website (Profil, SPMI, AMI, Akreditasi, Mutu, Knowledge Center).
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="page-form.php" class="btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Buat Halaman Baru
        </a>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <?= e($flash) ?>
</div>
<?php endif; ?>

<!-- Filter Tabs -->
<div class="d-flex gap-2 flex-wrap mb-3">
    <a href="page-list.php" class="btn-action <?= !$kategori_filter ? 'btn-edit' : '' ?>" style="<?= !$kategori_filter ? '' : 'background:#fff;border:1px solid var(--border);color:var(--text-muted);' ?>">
        Semua (<?= count($pages) ?>)
    </a>
    <?php foreach ($kategori_list as $kat): ?>
    <a href="page-list.php?kategori=<?= urlencode($kat) ?>" class="btn-action <?= $kategori_filter === $kat ? 'btn-edit' : '' ?>" style="<?= $kategori_filter === $kat ? '' : 'background:#fff;border:1px solid var(--border);color:var(--text-muted);' ?>">
        <?= e($kat) ?>
    </a>
    <?php endforeach; ?>
</div>

<div class="admin-table-wrap">
    <div class="admin-table-topbar">
        <div class="admin-table-title">Daftar Halaman Website</div>
    </div>

    <?php if (empty($pages)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-muted);">
        Belum ada halaman. <a href="page-form.php" class="text-purple font-weight-600">Tambah halaman baru</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Judul Halaman</th>
                    <th width="150">Kategori</th>
                    <th width="150">Status Konten</th>
                    <th width="140">Terakhir Update</th>
                    <th width="150">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                <?php
                $has_content = !empty(trim(strip_tags($p['konten'], '<img><iframe>')));
                ?>
                <tr>
                    <td>
                        <div style="font-weight:700;color:var(--navy);font-size:0.92rem;"><?= e($p['judul']) ?></div>
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                            <code>/page.php?slug=<?= e($p['slug']) ?></code>
                        </div>
                    </td>
                    <td>
                        <span class="card-category-badge"><?= e($p['kategori']) ?></span>
                    </td>
                    <td>
                        <?php if ($has_content): ?>
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.75rem;font-weight:600;color:#2E7D32;background:#E8F5E9;padding:0.25rem 0.65rem;border-radius:50px;">
                                ● Ada Konten
                            </span>
                        <?php else: ?>
                            <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.75rem;font-weight:600;color:#C62828;background:#FFEBEE;padding:0.25rem 0.65rem;border-radius:50px;" title="Menampilkan Empty State di web publik">
                                ○ Menunggu Drive
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.8rem;color:var(--text-muted);"><?= formatTanggal($p['updated_at']) ?></td>
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <a href="<?= SITE_URL ?>/page.php?slug=<?= e($p['slug']) ?>" target="_blank" class="btn-action" style="background:#ECEFF1;color:#455A64;" title="Lihat Halaman">
                                Preview
                            </a>
                            <a href="page-form.php?id=<?= $p['id'] ?>" class="btn-action btn-edit">
                                Edit
                            </a>
                            <a href="page-list.php?delete=<?= $p['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus halaman ini?')">
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
