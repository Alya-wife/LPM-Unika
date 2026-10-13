<?php
require_once __DIR__ . '/includes/auth.php';

// Hanya Super Admin yang berhak mengelola akun pengguna
if (!isAdminSuper()) {
    $_SESSION['flash_error'] = 'Akses Ditolak: Hanya Super Administrator yang berhak mengelola pengguna.';
    redirect(SITE_URL . '/admin/dashboard.php');
    exit;
}

$admin_page_title = 'Manajemen Akun Google Administrator';
$db = getDB();

// Handle aksi Hapus Pengguna
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $del_id = (int)$_GET['id'];
    if ($del_id === (int)$_SESSION['admin_id']) {
        $_SESSION['flash_error'] = 'Anda tidak dapat menghapus akun Anda sendiri yang sedang aktif.';
    } elseif ($del_id === 1) {
        $_SESSION['flash_error'] = 'Akun Super Administrator utama (ID 1) tidak dapat dihapus.';
    } else {
        $stmt_del = $db->prepare("DELETE FROM users WHERE id = ?");
        $stmt_del->execute([$del_id]);
        $_SESSION['flash'] = 'Akun administrator berhasil dihapus.';
    }
    redirect(SITE_URL . '/admin/user-list.php');
    exit;
}

// Handle aksi Toggle Status Aktif/Nonaktif
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $tog_id = (int)$_GET['id'];
    if ($tog_id === (int)$_SESSION['admin_id']) {
        $_SESSION['flash_error'] = 'Anda tidak dapat menonaktifkan akun Anda sendiri.';
    } elseif ($tog_id === 1) {
        $_SESSION['flash_error'] = 'Akun Super Administrator utama tidak dapat dinonaktifkan.';
    } else {
        $curr = $db->prepare("SELECT status FROM users WHERE id = ?");
        $curr->execute([$tog_id]);
        $st = $curr->fetchColumn();
        $new_st = ($st == 1) ? 0 : 1;
        $db->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new_st, $tog_id]);
        $_SESSION['flash'] = 'Status akun administrator berhasil diperbarui.';
    }
    redirect(SITE_URL . '/admin/user-list.php');
    exit;
}

// Ambil semua pengguna
$users = $db->query("SELECT * FROM users ORDER BY (role = 'superadmin') DESC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$modules_dict = getAdminModulesList();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
    <div>
        <h4 class="fw-bold mb-1" style="color:var(--navy);"><i class="bi bi-google text-danger me-2"></i>Manajemen Akun Google Administrator</h4>
        <p class="text-muted small mb-0">
            Daftarkan email akun Google resmi pengurus untuk masuk via Google Sign-In, dan atur wewenang akses per halaman sidebar.
        </p>
    </div>
    <a href="<?= SITE_URL ?>/admin/user-form.php" class="btn-add">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Tambah Akun Google Admin
    </a>
</div>

<div class="admin-table-wrap">
    <div class="admin-table-topbar d-flex align-items-center justify-content-between">
        <div class="admin-table-title d-flex align-items-center gap-2">
            <i class="bi bi-shield-lock-fill text-primary"></i>
            <span>Daftar Akun Administrator Terdaftar (<?= count($users) ?> Akun)</span>
        </div>
    </div>

    <div class="table-responsive">
        <table class="admin-table table align-middle mb-0">
            <thead>
                <tr>
                    <th style="width:50px;">No</th>
                    <th>Akun Google &amp; Administrator</th>
                    <th style="width:160px;">Peran (Role)</th>
                    <th>Wewenang Akses Modul Halaman</th>
                    <th style="width:120px;">Status</th>
                    <th style="width:130px;" class="text-end">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($users)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Belum ada akun pengguna.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($users as $idx => $u): 
                    $is_super = ($u['role'] === 'superadmin');
                    $perms = json_decode($u['akses_halaman'] ?? '[]', true) ?: [];
                    $is_current = ((int)$u['id'] === (int)$_SESSION['admin_id']);
                ?>
                <tr>
                    <td class="text-muted small"><?= $idx + 1 ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-3">
                            <div style="width:40px;height:40px;border-radius:10px;background:<?= $is_super ? 'linear-gradient(135deg, #F59E0B, #B45309)' : 'rgba(30,58,138,0.1)' ?>;color:<?= $is_super ? '#fff' : '#1e3a8a' ?>;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:0.95rem;flex-shrink:0;">
                                <?= strtoupper(substr($u['nama_lengkap'] ?: ($u['email'] ?: 'A'), 0, 1)) ?>
                            </div>
                            <div>
                                <div class="fw-bold text-dark" style="font-size:0.9rem;">
                                    <?= e($u['nama_lengkap'] ?: 'Administrator') ?>
                                </div>
                                <div class="text-muted small d-flex align-items-center gap-1 mt-1" style="font-size:0.78rem;">
                                    <i class="bi bi-google text-danger" style="font-size:0.75rem;"></i>
                                    <span class="font-monospace"><?= e($u['email'] ?: '(Belum ada email)') ?></span>
                                </div>
                            </div>
                        </div>
                    </td>
                    <td>
                        <?php if ($is_super): ?>
                        <span class="badge rounded-pill" style="background:linear-gradient(135deg, #F59E0B, #D97706);color:#fff;font-size:0.75rem;padding:0.35rem 0.75rem;">
                            <i class="bi bi-shield-shaded me-1"></i> Super Admin
                        </span>
                        <?php else: ?>
                        <span class="badge rounded-pill bg-info text-dark" style="font-size:0.75rem;padding:0.35rem 0.75rem;">
                            <i class="bi bi-person-badge me-1"></i> Admin Terbatas
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($is_super): ?>
                        <div class="text-success small fw-semibold d-flex align-items-center gap-1">
                            <i class="bi bi-check-all fs-5 text-success"></i>
                            <span>Akses Penuh (Semua Halaman &amp; Pengaturan)</span>
                        </div>
                        <?php else: ?>
                            <?php if (empty($perms)): ?>
                            <span class="badge bg-danger-subtle text-danger border border-danger small">
                                Belum ada hak akses halaman
                            </span>
                            <?php else: ?>
                            <div class="d-flex flex-wrap gap-1" style="max-width:380px;">
                                <?php foreach ($perms as $p_code): 
                                    $mod_info = $modules_dict[$p_code] ?? null;
                                    if ($mod_info):
                                ?>
                                <span class="badge bg-light text-dark border" style="font-size:0.72rem;padding:0.25rem 0.5rem;" title="<?= e($mod_info['desc']) ?>">
                                    <i class="bi <?= $mod_info['icon'] ?> me-1 text-primary"></i><?= e($mod_info['name']) ?>
                                </span>
                                <?php endif; endforeach; ?>
                            </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ((int)($u['status'] ?? 1) === 1): ?>
                            <?php if (!$is_current && (int)$u['id'] !== 1): ?>
                            <a href="<?= SITE_URL ?>/admin/user-list.php?action=toggle_status&id=<?= $u['id'] ?>" class="status-badge-btn status-active" onclick="return confirm('Nonaktifkan akun administrator ini?')" title="Status: Aktif (Klik untuk Nonaktifkan)">
                                <span class="status-indicator-dot"></span>
                                <span>Aktif</span>
                            </a>
                            <?php else: ?>
                            <span class="status-badge-btn status-active is-locked" title="Akun utama aktif">
                                <span class="status-indicator-dot"></span>
                                <span>Aktif</span>
                            </span>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="<?= SITE_URL ?>/admin/user-list.php?action=toggle_status&id=<?= $u['id'] ?>" class="status-badge-btn status-inactive" onclick="return confirm('Aktifkan kembali akun administrator ini?')" title="Status: Nonaktif (Klik untuk Mengaktifkan)">
                                <span class="status-indicator-dot"></span>
                                <span>Nonaktif</span>
                            </a>
                        <?php endif; ?>
                    </td>
                    <td class="text-end">
                        <div class="d-inline-flex gap-2">
                            <a href="<?= SITE_URL ?>/admin/user-form.php?id=<?= $u['id'] ?>" class="btn-action btn-edit" title="Edit Akses Pengguna">
                                <i class="bi bi-pencil-square"></i>
                            </a>
                            
                            <?php if (!$is_current && (int)$u['id'] !== 1): ?>
                            <!-- Toggle Status Action Button -->
                            <?php if ((int)($u['status'] ?? 1) === 1): ?>
                            <a href="<?= SITE_URL ?>/admin/user-list.php?action=toggle_status&id=<?= $u['id'] ?>" class="btn-action btn-toggle-suspend" title="Nonaktifkan Akun" onclick="return confirm('Nonaktifkan akun administrator ini?')">
                                <i class="bi bi-pause-circle"></i>
                            </a>
                            <?php else: ?>
                            <a href="<?= SITE_URL ?>/admin/user-list.php?action=toggle_status&id=<?= $u['id'] ?>" class="btn-action btn-toggle-activate" title="Aktifkan Akun" onclick="return confirm('Aktifkan kembali akun administrator ini?')">
                                <i class="bi bi-play-circle"></i>
                            </a>
                            <?php endif; ?>

                            <!-- Delete -->
                            <a href="<?= SITE_URL ?>/admin/user-list.php?action=delete&id=<?= $u['id'] ?>" class="btn-action btn-delete" title="Hapus Akun" onclick="return confirm('Apakah Anda yakin ingin menghapus akun ini secara permanen?')">
                                <i class="bi bi-trash"></i>
                            </a>
                            <?php endif; ?>
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
