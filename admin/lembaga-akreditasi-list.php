<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Lembaga Akreditasi (7 LAM & BAN-PT)';
$db = getDB();

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $stmt = $db->prepare("SELECT logo, file_akreditasi FROM lembaga_akreditasi WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if ($item) {
        if ($item['file_akreditasi']) {
            $f_path = __DIR__ . '/../uploads/akreditasi/' . $item['file_akreditasi'];
            if (file_exists($f_path)) @unlink($f_path);
        }
        $db->prepare("DELETE FROM lembaga_akreditasi WHERE id = ?")->execute([$id]);
        $_SESSION['flash'] = 'Data lembaga akreditasi berhasil dihapus.';
    }
    redirect(SITE_URL . '/admin/lembaga-akreditasi-list.php');
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$list = $db->query("SELECT * FROM lembaga_akreditasi ORDER BY urutan ASC, id ASC")->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">Lembaga Akreditasi (7 LAM &amp; BAN-PT)</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Kelola daftar lembaga, logo, dan tautan resmi website lembaga akreditasi untuk rujukan langsung di website.</p>
    </div>
    <a href="lembaga-akreditasi-form.php" class="btn-add">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Tambah Lembaga
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
        <div class="admin-table-title">Daftar Lembaga Akreditasi (<?= count($list) ?>)</div>
    </div>

    <?php if (empty($list)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-muted);">
        Belum ada data lembaga akreditasi. <a href="lembaga-akreditasi-form.php" class="text-purple fw-bold">Tambah sekarang</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="60" class="text-center">Urutan</th>
                    <th width="80">Logo</th>
                    <th width="140">Kode</th>
                    <th>Nama Lembaga &amp; Lingkup</th>
                    <th width="180">Link Website Resmi</th>
                    <th width="140">Masa Berlaku</th>
                    <th width="130">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $today = new DateTime();
                foreach ($list as $item): 
                    $berlaku_text = '-';
                    $is_expired = false;
                    $is_warning = false;

                    if (!empty($item['masa_berlaku']) && $item['masa_berlaku'] !== '0000-00-00') {
                        $date_mb = new DateTime($item['masa_berlaku']);
                        $berlaku_text = $date_mb->format('d M Y');
                        $diff = (int)$today->diff($date_mb)->format("%r%a");
                        if ($diff <= 0) $is_expired = true;
                        elseif ($diff <= 730) $is_warning = true;
                    }
                ?>
                <tr <?= $is_expired ? 'style="background-color: #fff5f5;"' : ($is_warning ? 'style="background-color: #fffdf0;"' : '') ?>>
                    <td style="font-weight:700;color:var(--navy);text-align:center;"><?= (int)$item['urutan'] ?></td>
                    <td>
                        <div style="width:54px;height:54px;border-radius:10px;background:#ffffff;display:flex;align-items:center;justify-content:center;border:1px solid #E2E8F0;overflow:hidden;padding:5px;box-shadow:0 2px 6px rgba(0,0,0,0.04);">
                            <?php if ($item['logo'] && file_exists(__DIR__ . '/../uploads/akreditasi/' . $item['logo'])): ?>
                                <img src="<?= SITE_URL ?>/uploads/akreditasi/<?= e($item['logo']) ?>" alt="<?= e($item['kode']) ?>" style="max-width:100%;max-height:100%;object-fit:contain;">
                            <?php else: ?>
                                <div style="width:100%;height:100%;border-radius:6px;background:<?= e($item['bg'] ?: '#f1f5f9') ?>;display:flex;align-items:center;justify-content:center;">
                                    <span style="font-size:0.75rem;font-weight:800;color:<?= e($item['warna'] ?: '#0A192F') ?>;"><?= substr($item['kode'], 0, 4) ?></span>
                                </div>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <span style="font-family:var(--font-heading);font-size:0.95rem;font-weight:800;color:<?= e($item['warna'] ?: 'var(--navy)') ?>;">
                            <?= e($item['kode']) ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:700;color:var(--navy);font-size:0.92rem;margin-bottom:3px;"><?= e($item['nama']) ?></div>
                        <div style="font-size:0.8rem;color:var(--text-muted);"><?= e($item['lingkup'] ?? '-') ?></div>
                    </td>
                    <td>
                        <?php if (!empty($item['link_website'])): ?>
                            <a href="<?= e($item['link_website']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm btn-outline-primary fw-bold py-1 px-2" style="font-size:0.78rem;">
                                <i class="bi bi-box-arrow-up-right me-1"></i>
                                Buka Website
                            </a>
                            <div class="text-truncate text-muted mt-1" style="max-width:170px;font-size:0.7rem;" title="<?= e($item['link_website']) ?>">
                                <?= e($item['link_website']) ?>
                            </div>
                        <?php else: ?>
                            <span class="badge bg-light text-muted border px-2 py-1" style="font-size:0.75rem;">Belum ada link</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_expired): ?>
                            <span class="text-danger fw-bold" style="font-size:0.8rem;">
                                <i class="bi bi-exclamation-circle-fill"></i> Kadaluarsa:<br><?= $berlaku_text ?>
                            </span>
                        <?php elseif ($is_warning): ?>
                            <span class="text-warning-emphasis fw-bold" style="font-size:0.8rem;">
                                <i class="bi bi-clock-history"></i> Segera Habis:<br><?= $berlaku_text ?>
                            </span>
                        <?php else: ?>
                            <span style="font-size:0.85rem;color:var(--text-muted);"><?= $berlaku_text ?></span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <a href="lembaga-akreditasi-form.php?id=<?= $item['id'] ?>" class="btn-action btn-edit">Edit</a>
                            <a href="lembaga-akreditasi-list.php?delete=<?= $item['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus data lembaga akreditasi ini?')">Hapus</a>
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
