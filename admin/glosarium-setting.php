<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Glosarium Mutu';
$db = getDB();

$flash = $_SESSION['flash'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['flash'], $_SESSION['error']);

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM glosarium WHERE id = ?");
    $stmt->execute([$del_id]);
    $_SESSION['flash'] = 'Istilah glosarium berhasil dihapus.';
    redirect(SITE_URL . '/admin/glosarium-setting.php');
}

// Handle Toggle Active
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $tog_id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE glosarium SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
    $stmt->execute([$tog_id]);
    $_SESSION['flash'] = 'Status aktif istilah berhasil diperbarui.';
    redirect(SITE_URL . '/admin/glosarium-setting.php');
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_glosarium'])) {
    $id       = (int)($_POST['id'] ?? 0);
    $istilah  = trim($_POST['istilah'] ?? '');
    $nama     = trim($_POST['nama'] ?? '');
    $kategori = trim($_POST['kategori'] ?? 'Umum');
    $definisi = trim($_POST['definisi'] ?? '');
    $urutan   = (int)($_POST['urutan'] ?? 1);
    $is_active= isset($_POST['is_active']) ? 1 : 0;

    if (empty($istilah) || empty($definisi)) {
        $_SESSION['error'] = 'Istilah / singkatan dan definisi wajib diisi.';
    } else {
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE glosarium SET istilah = ?, nama = ?, kategori = ?, istilah_lengkap = ?, sumber = ?, definisi = ?, urutan = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$istilah, $nama, $kategori, $nama, $kategori, $definisi, $urutan, $is_active, $id]);
            $_SESSION['flash'] = 'Data istilah glosarium berhasil diperbarui.';
        } else {
            $stmt = $db->prepare("INSERT INTO glosarium (istilah, nama, kategori, istilah_lengkap, sumber, definisi, urutan, is_active) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$istilah, $nama, $kategori, $nama, $kategori, $definisi, $urutan, $is_active]);
            $_SESSION['flash'] = 'Istilah glosarium baru berhasil ditambahkan.';
        }
        redirect(SITE_URL . '/admin/glosarium-setting.php');
    }
}

$terms = $db->query("SELECT * FROM glosarium ORDER BY urutan ASC, istilah ASC")->fetchAll(PDO::FETCH_ASSOC);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">
            Kelola Glosarium Mutu
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Kamus istilah dan terminologi resmi penjaminan mutu perguruan tinggi (SPMI, PPEPP, AMI, KTS, dsb).
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= SITE_URL ?>/glosarium.php" target="_blank" class="btn btn-sm btn-outline-secondary fw-semibold d-flex align-items-center gap-1" style="border-radius:8px;">
            <i class="bi bi-box-arrow-up-right"></i> Lihat Halaman Glosarium
        </a>
        <button type="button" class="btn btn-sm btn-primary fw-bold d-flex align-items-center gap-1" style="border-radius:8px;background:var(--navy);border:none;" onclick="openGlosModal()">
            <i class="bi bi-plus-lg"></i> Tambah Istilah Mutu
        </button>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert" style="font-size:0.88rem;">
    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($flash) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<?php if ($error): ?>
<div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert" style="font-size:0.88rem;">
    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-book-half text-primary fs-5"></i>
            <h5 class="m-0 fw-bold" style="font-size:0.98rem;color:var(--navy);">
                Daftar Istilah Glosarium (<?= count($terms) ?> Istilah)
            </h5>
        </div>
        <small class="text-muted">Urutan angka terkecil akan tampil paling awal.</small>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th class="ps-4" width="7%">Urutan</th>
                    <th width="12%">Istilah</th>
                    <th width="22%">Kepanjangan / Nama</th>
                    <th width="15%">Kategori</th>
                    <th width="32%">Definisi</th>
                    <th class="text-center" width="6%">Status</th>
                    <th class="text-end pe-4" width="8%">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($terms)): ?>
                <tr>
                    <td colspan="7" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        Belum ada istilah glosarium yang ditambahkan.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($terms as $tm): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-center">
                            <span class="badge bg-light text-dark border"><?= (int)$tm['urutan'] ?></span>
                        </td>
                        <td>
                            <span class="fw-bold text-primary font-monospace fs-6">
                                <?= htmlspecialchars($tm['istilah']) ?>
                            </span>
                        </td>
                        <td class="fw-semibold text-dark">
                            <?= htmlspecialchars($tm['nama'] ?: '-') ?>
                        </td>
                        <td>
                            <span class="badge rounded-pill fw-semibold" style="background:#FAF5FF;color:#7E22CE;border:1px solid #E9D5FF;">
                                <?= htmlspecialchars($tm['kategori']) ?>
                            </span>
                        </td>
                        <td class="text-muted" style="max-width:300px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($tm['definisi']) ?>">
                            <?= htmlspecialchars(mb_strimwidth($tm['definisi'], 0, 95, '...')) ?>
                        </td>
                        <td class="text-center">
                            <a href="<?= SITE_URL ?>/admin/glosarium-setting.php?toggle=<?= $tm['id'] ?>" class="text-decoration-none" title="Klik untuk ubah status">
                                <?php if ($tm['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Nonaktif</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td class="text-end pe-4">
                            <button type="button" class="btn btn-sm btn-outline-primary me-1" onclick='editGlos(<?= json_encode($tm, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                <i class="bi bi-pencil-square"></i>
                            </button>
                            <a href="<?= SITE_URL ?>/admin/glosarium-setting.php?delete=<?= $tm['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Hapus istilah ini?')">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Add/Edit Glosarium -->
<div class="modal fade" id="modalGlos" tabindex="-1" aria-labelledby="modalGlosLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="save_glosarium" value="1">
            <input type="hidden" name="id" id="glos_id" value="0">

            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalGlosLabel" style="color:var(--navy);">Tambah Istilah Glosarium</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Istilah / Singkatan</label>
                        <input type="text" name="istilah" id="glos_istilah" class="form-control fw-bold" placeholder="Misal: SPMI" required>
                    </div>
                    <div class="col-md-5">
                        <label class="form-label small fw-bold">Kepanjangan / Nama Lengkap</label>
                        <input type="text" name="nama" id="glos_nama" class="form-control" placeholder="Misal: Sistem Penjaminan Mutu Internal">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label small fw-bold">Nomor Urut</label>
                        <input type="number" name="urutan" id="glos_urutan" class="form-control" value="1" min="1" required>
                    </div>
                    <div class="col-md-12">
                        <label class="form-label small fw-bold">Kategori Istilah</label>
                        <input type="text" name="kategori" id="glos_kategori" class="form-control form-control-sm" placeholder="Misal: Sistem & Regulasi, Alur Kerja, Evaluasi, Organisasi, dsb." value="Umum" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Definisi &amp; Penjelasan</label>
                        <textarea name="definisi" id="glos_definisi" rows="4" class="form-control" placeholder="Tuliskan definisi atau penjelasan istilah..." required></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="glos_is_active" value="1" checked>
                            <label class="form-check-label fw-semibold" for="glos_is_active">Aktifkan dan tampilkan di website publik</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold" style="background:var(--navy);border:none;">
                    <i class="bi bi-save me-1"></i> Simpan Istilah
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openGlosModal() {
    document.getElementById('modalGlosLabel').textContent = 'Tambah Istilah Glosarium';
    document.getElementById('glos_id').value = '0';
    document.getElementById('glos_istilah').value = '';
    document.getElementById('glos_nama').value = '';
    document.getElementById('glos_kategori').value = 'Umum';
    document.getElementById('glos_definisi').value = '';
    document.getElementById('glos_urutan').value = '<?= count($terms) + 1 ?>';
    document.getElementById('glos_is_active').checked = true;
    new bootstrap.Modal(document.getElementById('modalGlos')).show();
}

function editGlos(item) {
    document.getElementById('modalGlosLabel').textContent = 'Edit Istilah Glosarium';
    document.getElementById('glos_id').value = item.id;
    document.getElementById('glos_istilah').value = item.istilah;
    document.getElementById('glos_nama').value = item.nama;
    document.getElementById('glos_kategori').value = item.kategori;
    document.getElementById('glos_definisi').value = item.definisi;
    document.getElementById('glos_urutan').value = item.urutan;
    document.getElementById('glos_is_active').checked = (item.is_active == 1);
    new bootstrap.Modal(document.getElementById('modalGlos')).show();
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
