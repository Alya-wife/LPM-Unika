<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Kalender Mutu';
$db = getDB();

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM kalender_mutu WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Agenda kalender mutu berhasil dihapus.';
    redirect(SITE_URL . '/admin/kalender-list.php');
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$list = $db->query("SELECT * FROM kalender_mutu ORDER BY urutan ASC, id ASC")->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">Kalender Mutu LPM</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Kelola agenda dan timeline kegiatan penjaminan mutu bulanan yang ditampilkan di website.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= SITE_URL ?>/kalender-mutu.php" target="_blank" class="btn-action" style="background:#fff;border:1.5px solid var(--border);color:var(--navy);text-decoration:none;">
            Lihat di Website &nearr;
        </a>
        <a href="kalender-form.php" class="btn-action btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Agenda Bulan
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

<div class="admin-table-wrap">
    <div class="admin-table-topbar">
        <div class="admin-table-title">Daftar Agenda Bulanan (<?= count($list) ?> Bulan Terdaftar)</div>
    </div>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="40">Urut</th>
                    <th width="180">Bulan &amp; Tahun</th>
                    <th>Rincian Agenda / Kegiatan</th>
                    <th width="120">Tahun Akademik</th>
                    <th width="120">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($list)): ?>
                <tr>
                    <td colspan="5" style="text-align:center;color:var(--text-muted);padding:2.5rem;">
                        Belum ada agenda kalender mutu. Silakan tambahkan agenda baru.
                    </td>
                </tr>
                <?php else: ?>
                <?php foreach ($list as $k): 
                    $lines = array_filter(array_map('trim', explode("\n", $k['kegiatan'])));
                ?>
                <tr>
                    <td style="text-align:center;font-weight:700;color:var(--purple);">
                        <?= (int)$k['urutan'] ?>
                    </td>
                    <td>
                        <div style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:0.95rem;">
                            <?= e($k['bulan_tahun']) ?>
                        </div>
                    </td>
                    <td>
                        <ul style="margin:0;padding-left:1.2rem;font-size:0.85rem;color:var(--text-main);line-height:1.6;">
                            <?php foreach ($lines as $line): ?>
                            <li><?= e($line) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </td>
                    <td>
                        <span class="card-category-badge">
                            <?= e($k['tahun_akademik']) ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:6px;">
                            <a href="kalender-form.php?id=<?= $k['id'] ?>" class="btn-action btn-edit">
                                Edit
                            </a>
                            <a href="kalender-list.php?delete=<?= $k['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus agenda bulan <?= e($k['bulan_tahun']) ?>?')">
                                Hapus
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
