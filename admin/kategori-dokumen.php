<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Kategori Dokumen';
$db = getDB();

$flash = '';
$error = '';

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $db->prepare("DELETE FROM kategori_dokumen WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Kategori dokumen berhasil dihapus.';
    redirect(SITE_URL . '/admin/kategori-dokumen.php');
}

// Handle Add
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama = trim($_POST['nama_kategori'] ?? '');
    if (!$nama) {
        $error = 'Nama kategori wajib diisi.';
    } else {
        $slug = makeSlug($nama);
        $check = $db->prepare("SELECT id FROM kategori_dokumen WHERE slug = ?");
        $check->execute([$slug]);
        if ($check->fetch()) {
            $error = "Kategori dengan nama '{$nama}' sudah ada.";
        } else {
            $stmt = $db->prepare("INSERT INTO kategori_dokumen (nama_kategori, slug) VALUES (?, ?)");
            $stmt->execute([$nama, $slug]);
            $_SESSION['flash'] = 'Kategori dokumen berhasil ditambahkan.';
            redirect(SITE_URL . '/admin/kategori-dokumen.php');
        }
    }
}

$kategori_list = $db->query("SELECT id, nama_kategori, slug FROM kategori_dokumen ORDER BY id ASC")->fetchAll();
$count_stmt = $db->prepare("SELECT COUNT(*) FROM dokumen WHERE CONVERT(kategori USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci");
foreach ($kategori_list as &$k) {
    $count_stmt->execute([$k['nama_kategori']]);
    $k['total_dok'] = (int)$count_stmt->fetchColumn();
}
unset($k);
$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">Kategori Dokumen &amp; Regulasi</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Kelola kategori pengelompokan dokumen yang diunggah ke website.</p>
    </div>
    <a href="dokumen-list.php" class="btn-action" style="background:#fff;border:1.5px solid var(--border);color:var(--navy);text-decoration:none;">
        &larr; Kembali ke Daftar Dokumen
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

<?php if ($error): ?>
<div class="alert-lpm alert-danger mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
    </svg>
    <?= e($error) ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <div class="col-lg-5">
        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div class="admin-table-title">Tambah Kategori Baru</div>
            </div>
            <div style="padding:1.5rem;">
                <form method="POST">
                    <div class="mb-3">
                        <label class="form-label" style="font-family:var(--font-heading);font-weight:600;font-size:0.85rem;color:var(--navy);">
                            Nama Kategori Dokumen <span style="color:#C62828;">*</span>
                        </label>
                        <input type="text" name="nama_kategori" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Dokumen Regulasi / Panduan" required>
                        <small class="text-muted d-block mt-1">Kategori ini otomatis muncul sebagai opsi upload dokumen dan filter di website publik.</small>
                    </div>
                    <button type="submit" class="btn-submit w-100">
                        Tambah Kategori
                    </button>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-7">
        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div class="admin-table-title">Daftar Kategori Aktif (<?= count($kategori_list) ?>)</div>
            </div>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th width="40">#</th>
                            <th>Nama Kategori</th>
                            <th width="120">Jumlah Dokumen</th>
                            <th width="90">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($kategori_list as $idx => $k): ?>
                        <tr>
                            <td style="color:var(--text-muted);text-align:center;"><?= $idx + 1 ?></td>
                            <td>
                                <div style="font-weight:700;color:var(--navy);"><?= e($k['nama_kategori']) ?></div>
                                <div style="font-size:0.75rem;color:var(--text-muted);">Slug: <code><?= e($k['slug']) ?></code></div>
                            </td>
                            <td>
                                <span class="card-category-badge"><?= (int)$k['total_dok'] ?> file</span>
                            </td>
                            <td>
                                <a href="kategori-dokumen.php?delete=<?= $k['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Hapus kategori ini?')">
                                    Hapus
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
