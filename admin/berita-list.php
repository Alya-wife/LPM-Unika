<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Manajemen Berita';
$db = getDB();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    // Delete gambar file if exists
    $row = $db->prepare("SELECT gambar FROM berita WHERE id = ?");
    $row->execute([$id]);
    $row = $row->fetch();
    if ($row && $row['gambar'] && file_exists(__DIR__ . '/../uploads/berita/' . $row['gambar'])) {
        unlink(__DIR__ . '/../uploads/berita/' . $row['gambar']);
    }
    $db->prepare("DELETE FROM berita WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Berita berhasil dihapus.';
    redirect(SITE_URL . '/admin/berita-list.php');
}

$berita_list = $db->query("SELECT * FROM berita ORDER BY created_at DESC")->fetchAll();

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
    <div class="admin-table-topbar">
        <div class="admin-table-title">Daftar Berita &amp; Kegiatan (<?= count($berita_list) ?>)</div>
        <a href="berita-form.php" class="btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Berita
        </a>
    </div>

    <?php if (empty($berita_list)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-muted);">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="48" height="48" style="opacity:0.2;display:block;margin:0 auto 1rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
        </svg>
        Belum ada berita.
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th>Judul</th>
                    <th width="120">Tipe</th>
                    <th width="80">Gambar</th>
                    <th width="130">Tanggal</th>
                    <th width="130">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($berita_list as $i => $b): ?>
                <tr>
                    <td style="color:var(--text-muted);font-size:0.8rem;"><?= $i + 1 ?></td>
                    <td>
                        <div style="font-weight:600;color:var(--navy);"><?= e(mb_substr($b['judul'], 0, 60)) ?><?= mb_strlen($b['judul']) > 60 ? '...' : '' ?></div>
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;"><?= truncate($b['konten'], 60) ?></div>
                    </td>
                    <td>
                        <span class="card-category-badge"><?= e($b['tipe'] ?: 'Berita') ?></span>
                    </td>
                    <td>
                        <?php if ($b['gambar'] && file_exists(__DIR__ . '/../uploads/berita/' . $b['gambar'])): ?>
                        <img src="<?= SITE_URL ?>/uploads/berita/<?= e($b['gambar']) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                        <?php else: ?>
                        <div style="width:48px;height:48px;background:var(--bg-main);border-radius:6px;display:flex;align-items:center;justify-content:center;border:1px solid var(--border);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--text-light)" width="22" height="22">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                            </svg>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.8rem;color:var(--text-muted);"><?= formatTanggal($b['tanggal_publikasi'] ?: $b['created_at']) ?></td>
                    <td>
                        <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                            <a href="berita-form.php?id=<?= $b['id'] ?>" class="btn-action btn-edit" style="text-decoration:none;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                                </svg>
                                Edit
                            </a>
                            <a href="<?= SITE_URL ?>/berita-detail.php?slug=<?= e($b['slug']) ?>" target="_blank" class="btn-action" style="background:#E3F2FD;color:#1565C0;text-decoration:none;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                                Lihat
                            </a>
                            <a href="berita-list.php?delete=<?= $b['id'] ?>" class="btn-action btn-delete" style="text-decoration:none;" onclick="return confirm('Yakin hapus berita ini?')">
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
