<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Capaian Akreditasi';
$db = getDB();

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM akreditasi WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Data capaian akreditasi berhasil dihapus.';
    redirect(SITE_URL . '/admin/akreditasi-list.php');
}

$akreditasi = $db->query("SELECT * FROM akreditasi ORDER BY urutan ASC, id ASC")->fetchAll();
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">Capaian &amp; Pengakuan Akreditasi</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Kelola kartu akreditasi nasional dan internasional yang tampil di halaman profil dan beranda.</p>
    </div>
    <a href="akreditasi-form.php" class="btn-add">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Tambah Akreditasi
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
        <div class="admin-table-title">Daftar Pengakuan Akreditasi (<?= count($akreditasi) ?>)</div>
    </div>

    <?php if (empty($akreditasi)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-muted);">
        Belum ada data akreditasi. <a href="akreditasi-form.php" class="text-purple font-weight-600">Tambah sekarang</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="60">Urutan</th>
                    <th width="140">Peringkat</th>
                    <th>Lembaga Akreditasi</th>
                    <th>Keterangan</th>
                    <th width="120">Warna Label</th>
                    <th width="130">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($akreditasi as $a): ?>
                <tr>
                    <td style="font-weight:700;font-size:1rem;color:var(--navy);text-align:center;"><?= (int)$a['urutan'] ?></td>
                    <td>
                        <span style="font-family:var(--font-heading);font-size:1.5rem;font-weight:800;color:<?= e($a['warna'] ?: 'var(--navy)') ?>;">
                            <?= e($a['peringkat']) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:700;color:var(--navy);font-size:0.95rem;"><?= e($a['lembaga']) ?></div>
                    </td>
                    <td>
                        <div style="font-size:0.83rem;color:var(--text-muted);"><?= e($a['keterangan']) ?></div>
                    </td>
                    <td>
                        <div style="display:flex;align-items:center;gap:6px;">
                            <span style="width:18px;height:18px;border-radius:4px;background:<?= e($a['warna'] ?: '#0A192F') ?>;display:inline-block;border:1px solid #ccc;"></span>
                            <code style="font-size:0.75rem;"><?= e($a['warna']) ?></code>
                        </div>
                    </td>
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <a href="akreditasi-form.php?id=<?= $a['id'] ?>" class="btn-action btn-edit">Edit</a>
                            <a href="akreditasi-list.php?delete=<?= $a['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus akreditasi ini?')">Hapus</a>
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
