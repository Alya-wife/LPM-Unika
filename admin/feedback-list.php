<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kotak Masuk Layanan & Feedback';
$db = getDB();

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM feedback WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Pesan berhasil dihapus.';
    redirect(SITE_URL . '/admin/feedback-list.php');
}

// Handle Mark as Read/Selesai
if (isset($_GET['status']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $st = $_GET['status'] === 'selesai' ? 'Selesai' : 'Belum Dibaca';
    $db->prepare("UPDATE feedback SET status = ? WHERE id = ?")->execute([$st, $id]);
    redirect(SITE_URL . '/admin/feedback-list.php');
}

$feedbacks = $db->query("SELECT * FROM feedback ORDER BY tanggal DESC")->fetchAll();
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">Kotak Masuk Pengajuan Layanan &amp; Aspirasi</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Daftar pesan masuk, permohonan konsultasi mutu, pendampingan akreditasi, dan kritik/saran dari civitas akademika.</p>
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
        <div class="admin-table-title">Daftar Pesan Masuk (<?= count($feedbacks) ?>)</div>
    </div>

    <?php if (empty($feedbacks)): ?>
    <div style="padding:3.5rem;text-align:center;color:var(--text-muted);">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--text-muted)" width="48" height="48" style="opacity:0.3;margin-bottom:1rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
        </svg>
        <p style="font-size:0.9rem;margin:0;">Belum ada pesan atau pengajuan layanan yang masuk.</p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="140">Tanggal</th>
                    <th>Nama &amp; Pengirim</th>
                    <th width="180">Jenis Layanan</th>
                    <th>Isi Pesan / Aspirasi</th>
                    <th width="110">Status</th>
                    <th width="140">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feedbacks as $f): ?>
                <tr style="<?= $f['status'] === 'Belum Dibaca' ? 'background:#F8FAFC;' : '' ?>">
                    <td style="font-size:0.8rem;color:var(--text-muted);"><?= formatTanggal($f['tanggal']) ?></td>
                    <td>
                        <div style="font-weight:700;color:var(--navy);font-size:0.9rem;"><?= e($f['nama']) ?></div>
                        <div style="font-size:0.75rem;color:var(--text-muted);">
                            <a href="mailto:<?= e($f['email']) ?>" style="color:var(--navy);"><?= e($f['email']) ?></a>
                        </div>
                        <?php if ($f['instansi']): ?>
                        <div style="font-size:0.72rem;color:var(--text-light);margin-top:2px;"><?= e($f['instansi']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="card-category-badge" style="font-size:0.72rem;white-space:normal;display:inline-block;"><?= e($f['jenis_layanan'] ?: 'Umum') ?></span>
                    </td>
                    <td>
                        <div style="font-size:0.85rem;color:var(--text-main);line-height:1.6;white-space:pre-line;">
                            <?= e($f['pesan']) ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($f['status'] === 'Selesai'): ?>
                            <span style="font-size:0.72rem;font-weight:600;color:#2E7D32;background:#E8F5E9;padding:0.25rem 0.65rem;border-radius:50px;">
                                Selesai
                            </span>
                        <?php else: ?>
                            <span style="font-size:0.72rem;font-weight:600;color:#E65100;background:#FFF3E0;padding:0.25rem 0.65rem;border-radius:50px;">
                                Baru
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <?php if ($f['status'] !== 'Selesai'): ?>
                            <a href="feedback-list.php?status=selesai&id=<?= $f['id'] ?>" class="btn-action" style="background:#E8F5E9;color:#2E7D32;" title="Tandai Selesai">
                                Selesai
                            </a>
                            <?php endif; ?>
                            <a href="feedback-list.php?delete=<?= $f['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus pesan ini?')">
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
