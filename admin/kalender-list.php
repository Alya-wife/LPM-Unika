<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Kalender Mutu';
$db = getDB();

// Handle Active Period setting
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['set_active_periode'])) {
    $p_aktif = trim($_POST['kalender_periode_aktif'] ?? '2026/2027');
    setPengaturan('kalender_periode_aktif', $p_aktif);
    $_SESSION['flash'] = "Periode aktif Kalender Mutu untuk tampilan website diset ke: $p_aktif";
    redirect(SITE_URL . '/admin/kalender-list.php');
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM kalender_mutu WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Agenda kalender mutu berhasil dihapus.';
    redirect(SITE_URL . '/admin/kalender-list.php');
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$periode_aktif = getPengaturan('kalender_periode_aktif', '2026/2027');

// Fetch distinct academic periods
$all_periodes = $db->query("SELECT DISTINCT tahun_akademik FROM kalender_mutu ORDER BY tahun_akademik DESC")->fetchAll(PDO::FETCH_COLUMN);
if (empty($all_periodes)) {
    $all_periodes = ['2024/2025', '2025/2026', '2026/2027'];
}

$filter_periode = $_GET['periode'] ?? '';
if ($filter_periode) {
    $stmt = $db->prepare("SELECT * FROM kalender_mutu WHERE tahun_akademik = ? ORDER BY urutan ASC, id ASC");
    $stmt->execute([$filter_periode]);
    $list = $stmt->fetchAll();
} else {
    $list = $db->query("SELECT * FROM kalender_mutu ORDER BY tahun_akademik DESC, urutan ASC, id ASC")->fetchAll();
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">Kalender Mutu LPM</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Kelola agenda bulanan dan pilih periode mana yang akan ditampilkan di Knowledge Center &amp; Website.</p>
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

<!-- Box Pengaturan Periode Aktif Website & Filter Admin -->
<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="p-3 bg-white border rounded shadow-sm">
            <label class="fw-bold text-navy mb-2" style="font-size:0.88rem;display:flex;align-items:center;gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="var(--purple)" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                </svg>
                Periode Aktif yang Tampil di Website / Knowledge Center
            </label>
            <form method="POST" class="d-flex gap-2">
                <input type="hidden" name="set_active_periode" value="1">
                <select name="kalender_periode_aktif" class="form-select form-select-sm" style="border:1.5px solid var(--border);">
                    <?php foreach ($all_periodes as $p): ?>
                    <option value="<?= e($p) ?>" <?= $p === $periode_aktif ? 'selected' : '' ?>>
                        Periode <?= e($p) ?> <?= $p === $periode_aktif ? '(Saat Ini Aktif di Publik)' : '' ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <button type="submit" class="btn btn-sm btn-primary px-3 text-nowrap fw-bold">Set Aktif</button>
            </form>
        </div>
    </div>
    <div class="col-md-6">
        <div class="p-3 bg-white border rounded shadow-sm">
            <label class="fw-bold text-navy mb-2" style="font-size:0.88rem;display:flex;align-items:center;gap:6px;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="var(--navy)" width="16" height="16">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
                </svg>
                Filter Tampilan Tabel Admin (Riwayat Periode)
            </label>
            <form method="GET" class="d-flex gap-2">
                <select name="periode" class="form-select form-select-sm" onchange="this.form.submit()" style="border:1.5px solid var(--border);">
                    <option value="">Semua Periode Akademik</option>
                    <?php foreach ($all_periodes as $p): ?>
                    <option value="<?= e($p) ?>" <?= $filter_periode === $p ? 'selected' : '' ?>>
                        Periode <?= e($p) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
                <?php if ($filter_periode): ?>
                <a href="kalender-list.php" class="btn btn-sm btn-outline-secondary text-nowrap">Reset</a>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

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
