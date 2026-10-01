<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$admin_page_title = 'Buletin JAMUS';

// Delete action
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $row = $db->prepare("SELECT * FROM buletin WHERE id = ?");
    $row->execute([(int)$_GET['delete']]);
    $item = $row->fetch();
    if ($item) {
        // Remove files
        $pdf_path = __DIR__ . '/../uploads/buletin/' . $item['file_path'];
        if ($item['file_path'] && file_exists($pdf_path)) unlink($pdf_path);
        $cover_path = __DIR__ . '/../uploads/buletin/covers/' . $item['cover_path'];
        if ($item['cover_path'] && file_exists($cover_path)) unlink($cover_path);
        $db->prepare("DELETE FROM buletin WHERE id = ?")->execute([(int)$_GET['delete']]);
        $_SESSION['flash'] = 'Buletin berhasil dihapus.';
    }
    redirect(SITE_URL . '/admin/buletin-list.php');
}

// Toggle aktif
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $db->prepare("UPDATE buletin SET is_aktif = NOT is_aktif WHERE id = ?")->execute([(int)$_GET['toggle']]);
    redirect(SITE_URL . '/admin/buletin-list.php');
}

// Filter Periode & Tahun
$filter_periode = trim($_GET['periode'] ?? '');
$filter_tahun   = (isset($_GET['tahun']) && is_numeric($_GET['tahun'])) ? (int)$_GET['tahun'] : 0;

$all_periodes_buletin = $db->query("SELECT DISTINCT periode_akademik FROM buletin WHERE periode_akademik IS NOT NULL AND periode_akademik != '' ORDER BY periode_akademik DESC")->fetchAll(PDO::FETCH_COLUMN);
$all_years_buletin    = $db->query("SELECT DISTINCT YEAR(tanggal_terbit) as thn FROM buletin WHERE tanggal_terbit IS NOT NULL AND tanggal_terbit > '1970-01-01' ORDER BY thn DESC")->fetchAll(PDO::FETCH_COLUMN);

$where = [];
$params = [];

if ($filter_periode !== '') {
    $where[] = "periode_akademik = ?";
    $params[] = $filter_periode;
}
if ($filter_tahun > 0) {
    $where[] = "YEAR(tanggal_terbit) = ?";
    $params[] = $filter_tahun;
}

$sql = "SELECT * FROM buletin";
if (!empty($where)) {
    $sql .= " WHERE " . implode(" AND ", $where);
}
$sql .= " ORDER BY tanggal_terbit DESC, id DESC";

$stmt_b = $db->prepare($sql);
$stmt_b->execute($params);
$buletin_list = $stmt_b->fetchAll();

require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Flash Message -->
<?php if (!empty($_SESSION['flash'])): ?>
<div class="alert-lpm alert-success mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <?= e($_SESSION['flash']) ?>
</div>
<?php unset($_SESSION['flash']); endif; ?>

<!-- Page Header -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);margin-bottom:0.25rem;">Buletin JAMUS</h4>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Kelola edisi Buletin Jaminan Mutu Universitas – tampil di halaman Knowledge Center.</p>
    </div>
    <a href="buletin-form.php" class="btn-action btn-edit" style="display:inline-flex;align-items:center;gap:0.4rem;text-decoration:none;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2.5" stroke="currentColor" width="15" height="15">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
        </svg>
        Upload Buletin Baru
    </a>
</div>

<!-- Filter Bar -->
<div class="mb-4 p-3 bg-white border rounded d-flex align-items-center justify-content-between flex-wrap gap-2 shadow-sm">
    <div style="font-weight:700;color:var(--navy);font-size:0.88rem;display:flex;align-items:center;gap:6px;">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="var(--purple)" width="16" height="16">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3c2.755 0 5.455.232 8.083.678.533.09.917.556.917 1.096v1.044a2.25 2.25 0 0 1-.659 1.591l-5.432 5.432a2.25 2.25 0 0 0-.659 1.591v2.927a2.25 2.25 0 0 1-1.244 2.013L9.75 21v-6.568a2.25 2.25 0 0 0-.659-1.591L3.659 7.409A2.25 2.25 0 0 1 3 5.818V4.774c0-.54.384-1.006.917-1.096A48.32 48.32 0 0 1 12 3Z" />
        </svg>
        Filter Arsip Buletin:
    </div>
    <form method="GET" class="d-flex align-items-center gap-2 flex-wrap">
        <select name="tahun" class="form-select form-select-sm" onchange="this.form.submit()" style="border:1.5px solid var(--border);min-width:140px;">
            <option value="">-- Semua Tahun --</option>
            <?php foreach ($all_years_buletin as $y): ?>
            <option value="<?= $y ?>" <?= ($filter_tahun == $y) ? 'selected' : '' ?>>Tahun <?= $y ?></option>
            <?php endforeach; ?>
        </select>
        <select name="periode" class="form-select form-select-sm" onchange="this.form.submit()" style="border:1.5px solid var(--border);min-width:180px;">
            <option value="">-- Semua Periode --</option>
            <?php foreach ($all_periodes_buletin as $p): ?>
            <option value="<?= e($p) ?>" <?= $filter_periode === $p ? 'selected' : '' ?>>Periode <?= e($p) ?></option>
            <?php endforeach; ?>
        </select>
        <?php if ($filter_periode || $filter_tahun): ?>
        <a href="buletin-list.php" class="btn btn-sm btn-outline-secondary text-nowrap">Reset</a>
        <?php endif; ?>
    </form>
</div>

<!-- Bookshelf Preview Grid (Admin View) -->
<?php if (empty($buletin_list)): ?>
<div class="text-center py-5 card-lpm" style="background:#fff;border:2px dashed var(--border);border-radius:var(--radius-lg);">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="var(--text-muted)" width="52" height="52" class="mb-3">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
    </svg>
    <h6 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:0.5rem;">Belum Ada Edisi Buletin</h6>
    <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1.25rem;">Mulai unggah edisi Buletin JAMUS pertama untuk ditampilkan di Knowledge Center.</p>
    <a href="buletin-form.php" class="btn-action btn-edit" style="text-decoration:none;">Upload Sekarang</a>
</div>
<?php else: ?>

<!-- Stats Bar -->
<div class="row g-3 mb-4">
    <?php
    $total = count($buletin_list);
    $aktif = count(array_filter($buletin_list, fn($b) => $b['is_aktif']));
    ?>
    <div class="col-sm-4">
        <div class="p-3 text-center" style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-md);">
            <div style="font-family:var(--font-heading);font-weight:900;font-size:1.8rem;color:var(--navy);"><?= $total ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);">Total Edisi</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="p-3 text-center" style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-md);">
            <div style="font-family:var(--font-heading);font-weight:900;font-size:1.8rem;color:#2E7D32;"><?= $aktif ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);">Aktif Tampil</div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="p-3 text-center" style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-md);">
            <div style="font-family:var(--font-heading);font-weight:900;font-size:1.8rem;color:var(--purple);"><?= $total - $aktif ?></div>
            <div style="font-size:0.8rem;color:var(--text-muted);">Disembunyikan</div>
        </div>
    </div>
</div>

<!-- Table -->
<div class="card-lpm" style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);overflow:hidden;">
    <div class="table-responsive">
        <table class="table table-hover mb-0" style="font-size:0.88rem;">
            <thead>
                <tr style="background:var(--bg-main);border-bottom:2px solid var(--border);">
                    <th style="padding:0.9rem 1.25rem;font-family:var(--font-heading);font-weight:700;color:var(--navy);white-space:nowrap;">Cover</th>
                    <th style="padding:0.9rem 1.25rem;font-family:var(--font-heading);font-weight:700;color:var(--navy);">Judul & Edisi</th>
                    <th style="padding:0.9rem 1.25rem;font-family:var(--font-heading);font-weight:700;color:var(--navy);white-space:nowrap;">Terbit</th>
                    <th style="padding:0.9rem 1.25rem;font-family:var(--font-heading);font-weight:700;color:var(--navy);text-align:center;">Status</th>
                    <th style="padding:0.9rem 1.25rem;font-family:var(--font-heading);font-weight:700;color:var(--navy);text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($buletin_list as $b): 
                    $cov_file = getOrGenerateBuletinCover($b['cover_path'] ?? null, $b['file_path'] ?? null, (int)$b['id']);
                ?>
                <tr style="border-bottom:1px solid var(--border);">
                    <td style="padding:0.75rem 1.25rem;vertical-align:middle;">
                        <?php if ($cov_file): ?>
                            <img src="<?= SITE_URL ?>/uploads/buletin/covers/<?= e($cov_file) ?>"
                                 alt="cover"
                                 style="width:48px;height:64px;object-fit:cover;border-radius:4px;box-shadow:0 2px 6px rgba(0,0,0,0.15);">
                        <?php else: ?>
                            <?php
                            $colors = ['#7B1FA2','#1565C0','#2E7D32','#B71C1C','#E65100','#4A148C'];
                            $bg = $colors[$b['id'] % count($colors)];
                            $initials = mb_strtoupper(mb_substr($b['edisi'], 0, 2));
                            ?>
                            <div style="width:48px;height:64px;background:<?= $bg ?>;border-radius:4px;display:flex;align-items:center;justify-content:center;color:#fff;font-weight:900;font-size:0.75rem;text-align:center;box-shadow:0 2px 6px rgba(0,0,0,0.15);font-family:var(--font-heading);">
                                <?= $initials ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:0.75rem 1.25rem;vertical-align:middle;">
                        <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:3px;"><?= e($b['judul']) ?></div>
                        <span style="background:rgba(123,31,162,0.1);color:var(--purple);font-size:0.75rem;font-weight:700;padding:2px 8px;border-radius:20px;"><?= e($b['edisi']) ?></span>
                        <?php if ($b['deskripsi']): ?>
                        <div style="font-size:0.78rem;color:var(--text-muted);margin-top:4px;max-width:300px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;"><?= e($b['deskripsi']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="padding:0.75rem 1.25rem;vertical-align:middle;white-space:nowrap;color:var(--text-muted);">
                        <?= $b['tanggal_terbit'] ? date('d M Y', strtotime($b['tanggal_terbit'])) : '—' ?>
                    </td>
                    <td style="padding:0.75rem 1.25rem;vertical-align:middle;text-align:center;">
                        <a href="buletin-list.php?toggle=<?= $b['id'] ?>"
                           class="badge <?= $b['is_aktif'] ? 'bg-success' : 'bg-secondary' ?>"
                           style="text-decoration:none;padding:5px 10px;font-size:0.72rem;cursor:pointer;"
                           title="Klik untuk toggle">
                            <?= $b['is_aktif'] ? 'Aktif' : 'Tersembunyi' ?>
                        </a>
                    </td>
                    <td style="padding:0.75rem 1.25rem;vertical-align:middle;text-align:center;">
                        <div class="d-flex gap-2 justify-content-center">
                            <a href="<?= SITE_URL ?>/uploads/buletin/<?= e($b['file_path']) ?>"
                               target="_blank"
                               class="btn-action"
                               style="background:#E3F2FD;color:#1565C0;border:none;padding:5px 10px;font-size:0.75rem;text-decoration:none;"
                               title="Preview PDF">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                </svg>
                            </a>
                            <a href="buletin-form.php?id=<?= $b['id'] ?>"
                               class="btn-action btn-edit"
                               style="padding:5px 10px;font-size:0.75rem;text-decoration:none;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                </svg>
                                Edit
                            </a>
                            <a href="buletin-list.php?delete=<?= $b['id'] ?>"
                               class="btn-action btn-delete"
                               style="padding:5px 10px;font-size:0.75rem;text-decoration:none;"
                               onclick="return confirm('Yakin hapus buletin ini? File PDF dan cover akan ikut terhapus.')">
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
</div>

<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
