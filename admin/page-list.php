<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Halaman Dinamis';
$db = getDB();

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT judul, featured_image FROM pages WHERE id = ?");
    $stmt->execute([$id]);
    $p = $stmt->fetch();
    if ($p) {
        if (!empty($p['featured_image']) && file_exists(UPLOAD_PATH . $p['featured_image'])) {
            @unlink(UPLOAD_PATH . $p['featured_image']);
        }
        $db->prepare("DELETE FROM pages WHERE id = ?")->execute([$id]);
        $_SESSION['flash'] = 'Halaman "' . $p['judul'] . '" berhasil dihapus.';
    }
    redirect(SITE_URL . '/admin/page-list.php');
}

// Handle Quick Toggle Status (Publish/Draft)
if (isset($_GET['toggle_status']) && is_numeric($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $p = $db->query("SELECT status FROM pages WHERE id = $id")->fetch();
    if ($p) {
        $new_status = ($p['status'] === 'draft') ? 'publish' : 'draft';
        $db->prepare("UPDATE pages SET status = ? WHERE id = ?")->execute([$new_status, $id]);
        $_SESSION['flash'] = 'Status publikasi halaman berhasil diubah.';
    }
    redirect(SITE_URL . '/admin/page-list.php');
}

// Handle Quick Toggle Show in Nav
if (isset($_GET['toggle_nav']) && is_numeric($_GET['toggle_nav'])) {
    $id = (int)$_GET['toggle_nav'];
    $p = $db->query("SELECT show_in_nav FROM pages WHERE id = $id")->fetch();
    if ($p) {
        $new_nav = ($p['show_in_nav'] == 1) ? 0 : 1;
        $db->prepare("UPDATE pages SET show_in_nav = ? WHERE id = ?")->execute([$new_nav, $id]);
        $_SESSION['flash'] = 'Pengaturan penempatan menu navbar berhasil diperbarui.';
    }
    redirect(SITE_URL . '/admin/page-list.php');
}

$kategori_filter = trim($_GET['kategori'] ?? '');
$status_filter   = trim($_GET['status'] ?? '');
$search          = trim($_GET['q'] ?? '');

$sql = "SELECT * FROM pages WHERE 1=1";
$params = [];

if ($kategori_filter) {
    $sql .= " AND kategori = ?";
    $params[] = $kategori_filter;
}
if ($status_filter) {
    $sql .= " AND status = ?";
    $params[] = $status_filter;
}
if ($search) {
    $sql .= " AND (judul LIKE ? OR slug LIKE ? OR ringkasan LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " ORDER BY urutan ASC, updated_at DESC";
$stmt = $db->prepare($sql);
$stmt->execute($params);
$pages = $stmt->fetchAll();

try {
    $kategori_list = $db->query("SELECT nama_kategori FROM kategori_page ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $kategori_list = [];
}
if (empty($kategori_list)) {
    $kategori_list = $db->query("SELECT DISTINCT kategori FROM pages WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC")->fetchAll(PDO::FETCH_COLUMN);
}

$total_pages     = (int)$db->query("SELECT COUNT(*) FROM pages")->fetchColumn();
$total_published = (int)$db->query("SELECT COUNT(*) FROM pages WHERE status = 'publish'")->fetchColumn();
$total_draft     = (int)$db->query("SELECT COUNT(*) FROM pages WHERE status = 'draft'")->fetchColumn();
$total_nav       = (int)$db->query("SELECT COUNT(*) FROM pages WHERE show_in_nav = 1")->fetchColumn();

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.35rem;font-weight:800;color:var(--navy);margin:0;">Manajemen Halaman Statis &amp; Dinamis</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0.25rem 0 0;">
            Kelola halaman website, status penerbitan (Publish/Draft), dan penempatan menu navigasi ala WordPress.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="kategori-page.php" class="btn btn-outline-secondary d-inline-flex align-items-center gap-1" style="border-radius:var(--radius-sm);font-size:0.85rem;padding:0.55rem 1rem;font-weight:600;">
            <i class="bi bi-tags"></i> Kelola Kategori
        </a>
        <a href="advance-setting.php" class="btn btn-primary d-inline-flex align-items-center gap-1" style="background:var(--primary);border:none;border-radius:var(--radius-sm);font-size:0.85rem;padding:0.55rem 1.1rem;font-weight:700;">
            <i class="bi bi-layout-text-window-reverse"></i> Visual Page Builder
        </a>
        <a href="advance-setting.php?new=1" class="btn btn-outline-primary d-inline-flex align-items-center gap-1" style="border-radius:var(--radius-sm);font-size:0.85rem;padding:0.55rem 1rem;font-weight:600;">
            <i class="bi bi-plus-lg"></i> Buat Halaman Baru
        </a>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4 d-flex align-items-center gap-2">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <div><?= e($flash) ?></div>
</div>
<?php endif; ?>

<!-- Quick Stat Cards -->
<div class="row g-3 mb-4">
    <div class="col-6 col-md-3">
        <a href="page-list.php" class="card-lpm text-decoration-none d-block p-3" style="background:#fff;border:1.5px solid var(--border);border-radius:12px;">
            <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Total Halaman</div>
            <div style="font-size:1.4rem;font-weight:800;color:var(--navy);margin-top:2px;"><?= $total_pages ?></div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="page-list.php?status=publish" class="card-lpm text-decoration-none d-block p-3" style="background:#fff;border:1.5px solid #BBF7D0;border-radius:12px;">
            <div style="font-size:0.75rem;font-weight:700;color:#166534;text-transform:uppercase;">Diterbitkan (Live)</div>
            <div style="font-size:1.4rem;font-weight:800;color:#15803D;margin-top:2px;"><?= $total_published ?></div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <a href="page-list.php?status=draft" class="card-lpm text-decoration-none d-block p-3" style="background:#fff;border:1.5px solid #FDE68A;border-radius:12px;">
            <div style="font-size:0.75rem;font-weight:700;color:#854D0E;text-transform:uppercase;">Draf (Draft)</div>
            <div style="font-size:1.4rem;font-weight:800;color:#B45309;margin-top:2px;"><?= $total_draft ?></div>
        </a>
    </div>
    <div class="col-6 col-md-3">
        <div class="card-lpm p-3" style="background:#fff;border:1.5px solid #DDD6FE;border-radius:12px;">
            <div style="font-size:0.75rem;font-weight:700;color:#5B21B6;text-transform:uppercase;">Semat di Navbar</div>
            <div style="font-size:1.4rem;font-weight:800;color:var(--purple);margin-top:2px;"><?= $total_nav ?></div>
        </div>
    </div>
</div>

<!-- Filter Tabs & Search Bar -->
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="page-list.php" class="btn-action <?= (!$kategori_filter && !$status_filter) ? 'btn-edit' : '' ?>" style="<?= (!$kategori_filter && !$status_filter) ? '' : 'background:#fff;border:1px solid var(--border);color:var(--text-muted);' ?>">
            Semua (<?= $total_pages ?>)
        </a>
        <?php foreach ($kategori_list as $kat): ?>
        <a href="page-list.php?kategori=<?= urlencode($kat) ?>" class="btn-action <?= $kategori_filter === $kat ? 'btn-edit' : '' ?>" style="<?= $kategori_filter === $kat ? '' : 'background:#fff;border:1px solid var(--border);color:var(--text-muted);' ?>">
            <?= e($kat) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- Search Box -->
    <form method="GET" class="d-flex gap-2">
        <?php if ($kategori_filter): ?><input type="hidden" name="kategori" value="<?= e($kategori_filter) ?>"><?php endif; ?>
        <input type="text" name="q" class="form-control form-control-sm" placeholder="Cari halaman..." value="<?= e($search) ?>" style="border:1.5px solid var(--border);border-radius:6px;width:180px;">
        <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius:6px;">Cari</button>
        <?php if ($search || $kategori_filter || $status_filter): ?>
        <a href="page-list.php" class="btn btn-sm btn-light text-muted" title="Reset filter">✕</a>
        <?php endif; ?>
    </form>
</div>

<div class="admin-table-wrap">
    <div class="admin-table-topbar">
        <div class="admin-table-title">
            Daftar Halaman Website <?= $kategori_filter ? '— Kategori: ' . e($kategori_filter) : '' ?>
        </div>
    </div>

    <?php if (empty($pages)): ?>
    <div style="padding:3.5rem 1rem;text-align:center;color:var(--text-muted);">
        <i class="bi bi-file-earmark-text" style="font-size:2.5rem;color:#cbd5e1;display:block;margin-bottom:0.75rem;"></i>
        <div style="font-weight:700;color:var(--navy);font-size:1.1rem;">Belum ada halaman yang cocok</div>
        <p style="font-size:0.85rem;margin:0.35rem 0 1.25rem;">Buat halaman baru atau ubah kriteria pencarian Anda.</p>
        <a href="page-form.php" class="btn-add d-inline-flex">
            + Tambah Halaman Baru
        </a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Judul Halaman &amp; Tautan</th>
                    <th width="120">Kategori</th>
                    <th width="120">Status</th>
                    <th width="140">Menu Navigasi</th>
                    <th width="110">Layout</th>
                    <th width="130">Terakhir Update</th>
                    <th width="160" class="text-center">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($pages as $p): ?>
                <?php
                $is_published = ($p['status'] ?? 'publish') === 'publish';
                $is_in_nav    = !empty($p['show_in_nav']);
                $layout_label = ($p['layout'] ?? 'default') === 'fullwidth' ? 'Full Width' : 'Default';
                $is_core      = !empty($p['custom_url']);
                $live_url     = $is_core ? (SITE_URL . '/' . ($p['custom_url'] === 'index.php' ? '' : $p['custom_url'])) : (SITE_URL . '/page.php?slug=' . urlencode($p['slug']));
                $display_url  = $is_core ? ($p['custom_url'] === 'index.php' ? '/' : '/' . $p['custom_url']) : ('/page.php?slug=' . $p['slug']);
                ?>
                <tr>
                    <td>
                        <div style="display:flex;align-items:center;gap:10px;">
                            <?php if (!empty($p['featured_image'])): ?>
                                <img src="<?= UPLOAD_URL . e($p['featured_image']) ?>" alt="Thumb" style="width:40px;height:40px;object-fit:cover;border-radius:6px;border:1px solid var(--border);flex-shrink:0;">
                            <?php else: ?>
                                <div style="width:40px;height:40px;background:#f1f5f9;border-radius:6px;display:flex;align-items:center;justify-content:center;color:#64748b;flex-shrink:0;">
                                    <i class="bi bi-file-earmark-text fs-5"></i>
                                </div>
                            <?php endif; ?>
                            <div>
                                <div style="font-weight:700;color:var(--navy);font-size:0.92rem;display:flex;align-items:center;gap:6px;">
                                    <a href="advance-setting.php?id=<?= $p['id'] ?>" style="color:var(--navy);text-decoration:none;">
                                        <?= e($p['judul']) ?>
                                    </a>
                                    <?php if ($is_core): ?>
                                        <span class="badge bg-light text-primary border" style="font-size:0.65rem;font-weight:600;">Halaman Utama</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                                    <code><?= e($display_url) ?></code>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <span class="card-category-badge" style="font-size:0.75rem;"><?= e($p['kategori'] ?: 'Umum') ?></span>
                    </td>
                    <td>
                        <a href="page-list.php?toggle_status=<?= $p['id'] ?>" title="Klik untuk ubah status" style="text-decoration:none;">
                            <?php if ($is_published): ?>
                                <span class="badge" style="background:#DCFCE7;color:#15803D;border:1px solid #BBF7D0;font-size:0.75rem;cursor:pointer;">
                                    <i class="bi bi-check-circle-fill me-1"></i>Terbit (Live)
                                </span>
                            <?php else: ?>
                                <span class="badge" style="background:#FEF3C7;color:#B45309;border:1px solid #FDE68A;font-size:0.75rem;cursor:pointer;">
                                    <i class="bi bi-clock-fill me-1"></i>Draf
                                </span>
                            <?php endif; ?>
                        </a>
                    </td>
                    <td>
                        <?php if ($is_core): ?>
                            <span class="badge bg-light text-muted border" style="font-size:0.75rem;">
                                Navigasi Utama
                            </span>
                        <?php else: ?>
                            <a href="page-list.php?toggle_nav=<?= $p['id'] ?>" title="Klik untuk aktifkan/nonaktifkan pin navbar" style="text-decoration:none;">
                                <?php if ($is_in_nav): ?>
                                    <span class="badge" style="background:#EFF6FF;color:#1E3A8A;border:1px solid #BFDBFE;font-size:0.75rem;cursor:pointer;">
                                        <i class="bi bi-pin-angle-fill me-1"></i>Tampil di Nav
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background:#F1F5F9;color:#64748B;font-size:0.75rem;cursor:pointer;">
                                        – Tidak di Nav
                                    </span>
                                <?php endif; ?>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-size:0.75rem;color:var(--text-muted);background:#f8fafc;padding:0.2rem 0.5rem;border-radius:4px;border:1px solid var(--border);">
                            <?= $layout_label ?>
                        </span>
                    </td>
                    <td style="font-size:0.78rem;color:var(--text-muted);">
                        <?= formatTanggal($p['updated_at']) ?>
                    </td>
                    <td class="text-center">
                        <div style="display:inline-flex;gap:0.35rem;">
                            <a href="advance-setting.php?id=<?= $p['id'] ?>" class="btn-action" style="background:#EFF6FF;color:#1E3A8A;border:1px solid #BFDBFE;font-weight:600;" title="Edit Visual Page Builder">
                                <i class="bi bi-layout-text-window-reverse"></i> Builder
                            </a>
                            <a href="<?= $live_url ?>" target="_blank" class="btn-action" style="background:#ECEFF1;color:#455A64;" title="Lihat Halaman Live">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="page-form.php?id=<?= $p['id'] ?>" class="btn-action btn-edit" title="Edit Form">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <?php if (!$is_core): ?>
                            <a href="page-list.php?delete=<?= $p['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus halaman <?= addslashes($p['judul']) ?>?')" title="Hapus">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
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
