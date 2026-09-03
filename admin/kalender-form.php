<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$id      = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$is_edit = ($id > 0);

$item = [
    'bulan_tahun'    => '',
    'tahun_akademik' => '2026–2027',
    'urutan'         => 1,
    'kegiatan'       => '',
    'is_active'      => 1
];

if ($is_edit) {
    $stmt = $db->prepare("SELECT * FROM kalender_mutu WHERE id = ?");
    $stmt->execute([$id]);
    $item = $stmt->fetch();
    if (!$item) {
        $_SESSION['flash'] = 'Data agenda tidak ditemukan.';
        redirect(SITE_URL . '/admin/kalender-list.php');
    }
} else {
    // Cari urutan terakhir + 1
    $last_urutan = (int)$db->query("SELECT MAX(urutan) FROM kalender_mutu")->fetchColumn();
    $item['urutan'] = $last_urutan + 1;
}

$admin_page_title = $is_edit ? 'Edit Agenda Kalender Mutu' : 'Tambah Agenda Kalender Mutu';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $bulan_tahun    = trim($_POST['bulan_tahun'] ?? '');
    $tahun_akademik = trim($_POST['tahun_akademik'] ?? '2026–2027');
    $urutan         = (int)($_POST['urutan'] ?? 1);
    $kegiatan       = trim($_POST['kegiatan'] ?? '');
    $id_post        = (int)($_POST['id'] ?? 0);

    if (!$bulan_tahun || !$kegiatan) {
        $error = 'Nama bulan/tahun dan rincian kegiatan wajib diisi.';
    } else {
        if ($is_edit && $id_post) {
            $stmt = $db->prepare("UPDATE kalender_mutu SET bulan_tahun = ?, tahun_akademik = ?, urutan = ?, kegiatan = ? WHERE id = ?");
            $stmt->execute([$bulan_tahun, $tahun_akademik, $urutan, $kegiatan, $id_post]);
            $_SESSION['flash'] = 'Agenda kalender mutu berhasil diperbarui.';
        } else {
            $stmt = $db->prepare("INSERT INTO kalender_mutu (bulan_tahun, tahun_akademik, urutan, kegiatan) VALUES (?, ?, ?, ?)");
            $stmt->execute([$bulan_tahun, $tahun_akademik, $urutan, $kegiatan]);
            $_SESSION['flash'] = 'Agenda kalender mutu baru berhasil ditambahkan.';
        }
        redirect(SITE_URL . '/admin/kalender-list.php');
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-8">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="kalender-list.php" style="color:var(--text-muted);">Kalender Mutu</a>
            <span>/</span>
            <span style="color:var(--navy);"><?= $is_edit ? 'Edit Agenda' : 'Tambah Baru' ?></span>
        </div>

        <?php if ($error): ?>
        <div class="alert-lpm alert-danger mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div class="admin-table-title"><?= $admin_page_title ?></div>
                <a href="kalender-list.php" class="btn-action" style="background:var(--bg-main);color:var(--text-muted);border:1.5px solid var(--border);text-decoration:none;">
                    &larr; Kembali
                </a>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST">
                    <?php if ($is_edit): ?>
                    <input type="hidden" name="id" value="<?= $item['id'] ?>">
                    <?php endif; ?>

                    <div class="row g-4">
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Nama Bulan &amp; Tahun <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="bulan_tahun" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;font-weight:600;" placeholder="Contoh: SEPTEMBER 2026" value="<?= e($is_edit ? $item['bulan_tahun'] : ($_POST['bulan_tahun'] ?? '')) ?>" required>
                            <small class="text-muted">Gunakan format huruf kapital seperti: OKTOBER 2026</small>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Periode / Tahun Akademik
                            </label>
                            <input type="text" name="tahun_akademik" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="2026–2027" value="<?= e($is_edit ? $item['tahun_akademik'] : ($_POST['tahun_akademik'] ?? '2026–2027')) ?>" required>
                        </div>

                        <div class="col-md-2">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Nomor Urut
                            </label>
                            <input type="number" name="urutan" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" min="1" max="50" value="<?= (int)($is_edit ? $item['urutan'] : ($_POST['urutan'] ?? $item['urutan'])) ?>" required>
                        </div>

                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Rincian Butir Kegiatan / Agenda <span style="color:#C62828;">*</span>
                            </label>
                            <textarea name="kegiatan" class="form-control" rows="7" style="border:1.5px solid var(--border);padding:0.85rem 1rem;line-height:1.75;" placeholder="Tuliskan setiap agenda pada baris baru..." required><?= e($is_edit ? $item['kegiatan'] : ($_POST['kegiatan'] ?? '')) ?></textarea>
                            <small class="text-muted d-block mt-2">
                                💡 <strong>Tips:</strong> Masukkan setiap agenda di <strong>baris baru (tekan Enter)</strong>. Website akan otomatis menampilkannya sebagai butir peluru <em>(bullet points)</em> rapi.
                            </small>
                        </div>

                        <div class="col-12" style="padding-top:1rem;border-top:1px solid var(--border);">
                            <div style="display:flex;justify-content:flex-end;gap:1rem;align-items:center;">
                                <a href="kalender-list.php" style="color:var(--text-muted);font-size:0.875rem;">Batal</a>
                                <button type="submit" class="btn-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <?= $is_edit ? 'Simpan Perubahan' : 'Tambahkan Agenda' ?>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
