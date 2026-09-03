<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Slider Beranda';
$db = getDB();

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = $db->prepare("SELECT gambar FROM hero_slides WHERE id = ?");
    $row->execute([$id]);
    $item = $row->fetch();
    if ($item && $item['gambar'] && !filter_var($item['gambar'], FILTER_VALIDATE_URL)) {
        $path = __DIR__ . '/../uploads/slides/' . $item['gambar'];
        if (file_exists($path)) @unlink($path);
    }
    $db->prepare("DELETE FROM hero_slides WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Slide berhasil dihapus.';
    redirect(SITE_URL . '/admin/slider-list.php');
}

// Handle Toggle Status
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $id = (int)$_GET['toggle'];
    $db->prepare("UPDATE hero_slides SET is_active = IF(is_active=1, 0, 1) WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Status aktif slide berhasil diubah.';
    redirect(SITE_URL . '/admin/slider-list.php');
}

$slides = $db->query("SELECT * FROM hero_slides ORDER BY urutan ASC, id ASC")->fetchAll();
$flash  = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">Slider Gambar &amp; Banner Beranda</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Atur gambar background, teks judul, tombol, dan urutan slide hero pada beranda.</p>
    </div>
    <a href="slider-form.php" class="btn-add">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Tambah Slide Baru
    </a>
</div>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <?= e($flash) ?>
</div>
<?php endif; ?>

<div class="admin-table-wrap">
    <div class="admin-table-topbar">
        <div class="admin-table-title">Daftar Slide Aktif &amp; Arsip (<?= count($slides) ?>)</div>
    </div>

    <?php if (empty($slides)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-muted);">
        Belum ada slide. <a href="slider-form.php" class="text-purple font-weight-600">Tambah slide pertama</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="50">Urutan</th>
                    <th width="140">Gambar Background</th>
                    <th>Judul &amp; Informasi Slide</th>
                    <th width="140">Tombol Aksi</th>
                    <th width="100">Status</th>
                    <th width="140">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($slides as $s): ?>
                <?php
                $img_src = '';
                if (filter_var($s['gambar'], FILTER_VALIDATE_URL)) {
                    $img_src = $s['gambar'];
                } elseif ($s['gambar'] && file_exists(__DIR__ . '/../uploads/slides/' . $s['gambar'])) {
                    $img_src = SITE_URL . '/uploads/slides/' . $s['gambar'];
                }
                ?>
                <tr>
                    <td style="font-weight:700;font-size:1rem;color:var(--navy);text-align:center;"><?= (int)$s['urutan'] ?></td>
                    <td>
                        <?php if ($img_src): ?>
                            <img src="<?= e($img_src) ?>" alt="" style="width:120px;height:70px;object-fit:cover;border-radius:var(--radius-sm);border:1px solid var(--border);">
                        <?php else: ?>
                            <div style="width:120px;height:70px;background:var(--navy);border-radius:var(--radius-sm);display:flex;align-items:center;justify-content:center;color:rgba(255,255,255,0.4);font-size:0.75rem;">
                                No Image
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($s['subjudul']): ?>
                            <span class="card-category-badge mb-1"><?= e($s['subjudul']) ?></span>
                        <?php endif; ?>
                        <div style="font-weight:700;font-size:0.95rem;color:var(--navy);margin-top:2px;">
                            <?= e($s['judul']) ?>
                        </div>
                        <div style="font-size:0.8rem;color:var(--text-muted);margin-top:3px;">
                            <?= truncate($s['deskripsi'], 90) ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($s['btn_text']): ?>
                            <div style="font-size:0.8rem;font-weight:600;color:var(--navy);">
                                <?= e($s['btn_text']) ?>
                            </div>
                            <div style="font-size:0.72rem;color:var(--text-light);word-break:break-all;">
                                → <?= e($s['btn_link']) ?>
                            </div>
                        <?php else: ?>
                            <span style="font-size:0.75rem;color:var(--text-light);">-</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <a href="slider-list.php?toggle=<?= $s['id'] ?>" class="btn-action" style="<?= $s['is_active'] ? 'background:#E8F5E9;color:#2E7D32;' : 'background:#FFEBEE;color:#C62828;' ?>" title="Klik untuk mengubah status">
                            <?= $s['is_active'] ? '● Tampil' : '○ Nonaktif' ?>
                        </a>
                    </td>
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <a href="slider-form.php?id=<?= $s['id'] ?>" class="btn-action btn-edit">
                                Edit
                            </a>
                            <a href="slider-list.php?delete=<?= $s['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus slide ini?')">
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
