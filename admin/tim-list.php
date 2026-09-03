<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Struktur Organisasi & Tim LPM';
$db = getDB();

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = $db->prepare("SELECT foto FROM tim_lpm WHERE id = ?");
    $row->execute([$id]);
    $item = $row->fetch();
    if ($item && $item['foto'] && file_exists(__DIR__ . '/../uploads/tim/' . $item['foto'])) {
        @unlink(__DIR__ . '/../uploads/tim/' . $item['foto']);
    }
    $db->prepare("DELETE FROM tim_lpm WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Personel tim berhasil dihapus.';
    redirect(SITE_URL . '/admin/tim-list.php');
}

$tim = $db->query("SELECT * FROM tim_lpm ORDER BY urutan ASC, id ASC")->fetchAll();
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">Struktur Organisasi &amp; Personel Tim LPM</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Kelola nama pimpinan, koordinator bidang, dan anggota yang tampil pada bagan struktur organisasi di halaman profil.</p>
    </div>
    <a href="tim-form.php" class="btn-add">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Tambah Anggota Tim
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
        <div class="admin-table-title">Daftar Anggota Struktur LPM (<?= count($tim) ?>)</div>
    </div>

    <?php if (empty($tim)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-muted);">
        Belum ada anggota tim. <a href="tim-form.php" class="text-purple font-weight-600">Tambah sekarang</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="60">Urutan</th>
                    <th width="80">Foto</th>
                    <th>Nama Personel</th>
                    <th>Jabatan</th>
                    <th>Bidang Tugas</th>
                    <th width="110">Level</th>
                    <th width="130">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tim as $t): ?>
                <tr>
                    <td style="font-weight:700;font-size:1rem;color:var(--navy);text-align:center;"><?= (int)$t['urutan'] ?></td>
                    <td>
                        <?php if ($t['foto'] && file_exists(__DIR__ . '/../uploads/tim/' . $t['foto'])): ?>
                            <img src="<?= SITE_URL ?>/uploads/tim/<?= e($t['foto']) ?>" alt="" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
                        <?php else: ?>
                            <div style="width:48px;height:48px;border-radius:50%;background:var(--purple-glow);display:flex;align-items:center;justify-content:center;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--purple)" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:700;color:var(--navy);font-size:0.92rem;"><?= e($t['nama']) ?></div>
                    </td>
                    <td>
                        <div style="font-weight:600;color:var(--purple);font-size:0.85rem;"><?= e($t['jabatan']) ?></div>
                    </td>
                    <td>
                        <div style="font-size:0.8rem;color:var(--text-muted);"><?= e($t['bidang'] ?: '-') ?></div>
                    </td>
                    <td>
                        <span class="card-category-badge" style="text-transform:capitalize;">
                            <?= e($t['level'] ?: 'anggota') ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <a href="tim-form.php?id=<?= $t['id'] ?>" class="btn-action btn-edit">Edit</a>
                            <a href="tim-list.php?delete=<?= $t['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus personel ini?')">Hapus</a>
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
