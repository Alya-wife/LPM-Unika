<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Tanya Jawab (FAQ)';
$db = getDB();

$flash = $_SESSION['flash'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['flash'], $_SESSION['error']);

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $del_id = (int)$_GET['delete'];
    $stmt = $db->prepare("DELETE FROM faqs WHERE id = ?");
    $stmt->execute([$del_id]);
    $_SESSION['flash'] = 'Item FAQ berhasil dihapus.';
    redirect(SITE_URL . '/admin/faq-setting.php');
}

// Handle Toggle Active
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    $tog_id = (int)$_GET['toggle'];
    $stmt = $db->prepare("UPDATE faqs SET is_active = IF(is_active = 1, 0, 1) WHERE id = ?");
    $stmt->execute([$tog_id]);
    $_SESSION['flash'] = 'Status aktif FAQ berhasil diperbarui.';
    redirect(SITE_URL . '/admin/faq-setting.php');
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['save_faq'])) {
    $id         = (int)($_POST['id'] ?? 0);
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $jawaban    = trim($_POST['jawaban'] ?? '');
    $kategori   = trim($_POST['kategori'] ?? 'Umum');
    $urutan     = (int)($_POST['urutan'] ?? 1);
    $is_active  = isset($_POST['is_active']) ? 1 : 0;

    if (empty($pertanyaan) || empty($jawaban)) {
        $_SESSION['error'] = 'Pertanyaan dan jawaban wajib diisi.';
    } else {
        if ($id > 0) {
            $stmt = $db->prepare("UPDATE faqs SET pertanyaan = ?, jawaban = ?, kategori = ?, urutan = ?, is_active = ? WHERE id = ?");
            $stmt->execute([$pertanyaan, $jawaban, $kategori, $urutan, $is_active, $id]);
            $_SESSION['flash'] = 'Data FAQ berhasil diperbarui.';
        } else {
            $stmt = $db->prepare("INSERT INTO faqs (pertanyaan, jawaban, kategori, urutan, is_active) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$pertanyaan, $jawaban, $kategori, $urutan, $is_active]);
            $_SESSION['flash'] = 'Pertanyaan FAQ baru berhasil ditambahkan.';
        }
        redirect(SITE_URL . '/admin/faq-setting.php');
    }
}

// Filter Category
$kategori_filter = trim($_GET['kategori'] ?? '');

// Get category list
try {
    $faq_categories = $db->query("SELECT nama_kategori FROM kategori_faq ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_COLUMN);
} catch (Exception $e) {
    $faq_categories = [];
}
if (empty($faq_categories)) {
    $faq_categories = $db->query("SELECT DISTINCT kategori FROM faqs WHERE kategori IS NOT NULL AND kategori != '' ORDER BY kategori ASC")->fetchAll(PDO::FETCH_COLUMN);
}

// Get FAQ items
if ($kategori_filter) {
    $stmt_faq = $db->prepare("SELECT * FROM faqs WHERE CONVERT(kategori USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci ORDER BY urutan ASC, id ASC");
    $stmt_faq->execute([$kategori_filter]);
    $faqs = $stmt_faq->fetchAll(PDO::FETCH_ASSOC);
} else {
    $faqs = $db->query("SELECT * FROM faqs ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">
            Kelola Tanya Jawab (FAQ) Mutu
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Kelola daftar pertanyaan yang sering diajukan seputar penjaminan mutu, SPMI, AMI, dan Akreditasi pada halaman publik.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="<?= SITE_URL ?>/faq.php" target="_blank" class="btn btn-sm btn-outline-secondary fw-semibold d-flex align-items-center gap-1" style="border-radius:8px;">
            <i class="bi bi-box-arrow-up-right"></i> Lihat Halaman FAQ
        </a>
        <a href="kategori-faq.php" class="btn btn-sm btn-outline-primary fw-semibold d-flex align-items-center gap-1" style="border-radius:8px;">
            <i class="bi bi-tags"></i> Kelola Kategori FAQ
        </a>
        <button type="button" class="btn btn-sm btn-primary fw-bold d-flex align-items-center gap-1" style="border-radius:8px;background:var(--navy);border:none;" onclick="openFaqModal()">
            <i class="bi bi-plus-lg"></i> Tambah Pertanyaan FAQ
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

<!-- Filter Kategori Tabs / Badges -->
<div class="d-flex align-items-center gap-2 mb-3 overflow-auto pb-1 flex-wrap">
    <span class="small fw-bold text-muted me-1"><i class="bi bi-funnel"></i> Kategori:</span>
    <a href="faq-setting.php" class="badge rounded-pill text-decoration-none px-3 py-2 <?= empty($kategori_filter) ? 'bg-primary text-white' : 'bg-white text-secondary border' ?>" style="font-size:0.8rem;">
        Semua Kategori
    </a>
    <?php foreach ($faq_categories as $fcat): ?>
        <a href="faq-setting.php?kategori=<?= urlencode($fcat) ?>" class="badge rounded-pill text-decoration-none px-3 py-2 <?= $kategori_filter === $fcat ? 'bg-primary text-white' : 'bg-white text-secondary border' ?>" style="font-size:0.8rem;">
            <?= htmlspecialchars($fcat) ?>
        </a>
    <?php endforeach; ?>
</div>

<div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-question-circle-fill text-primary fs-5"></i>
            <h5 class="m-0 fw-bold" style="font-size:0.98rem;color:var(--navy);">
                Daftar Pertanyaan &amp; Jawaban FAQ (<?= count($faqs) ?> Item)
            </h5>
        </div>
        <small class="text-muted">Urutan angka terkecil akan tampil paling atas pada accordion.</small>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th class="ps-4" width="8%">Urutan</th>
                    <th width="18%">Kategori</th>
                    <th width="32%">Pertanyaan</th>
                    <th width="28%">Ringkasan Jawaban</th>
                    <th class="text-center text-nowrap" style="width:100px;">Status</th>
                    <th class="text-end pe-4 text-nowrap" style="width:110px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($faqs)): ?>
                <tr>
                    <td colspan="6" class="text-center py-5 text-muted">
                        <i class="bi bi-inbox fs-2 d-block mb-2"></i>
                        Belum ada pertanyaan FAQ yang ditambahkan.
                    </td>
                </tr>
                <?php else: ?>
                    <?php foreach ($faqs as $fq): ?>
                    <tr>
                        <td class="ps-4 fw-bold text-center">
                            <span class="badge bg-light text-dark border"><?= (int)$fq['urutan'] ?></span>
                        </td>
                        <td>
                            <span class="badge rounded-pill fw-semibold" style="background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;">
                                <?= htmlspecialchars($fq['kategori']) ?>
                            </span>
                        </td>
                        <td class="fw-semibold text-dark">
                            <?= htmlspecialchars($fq['pertanyaan']) ?>
                        </td>
                        <td class="text-muted" style="max-width:280px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;" title="<?= htmlspecialchars($fq['jawaban']) ?>">
                            <?= htmlspecialchars(mb_strimwidth($fq['jawaban'], 0, 90, '...')) ?>
                        </td>
                        <td class="text-center text-nowrap">
                            <a href="<?= SITE_URL ?>/admin/faq-setting.php?toggle=<?= $fq['id'] ?>" class="text-decoration-none" title="Klik untuk ubah status">
                                <?php if ($fq['is_active']): ?>
                                    <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">Aktif</span>
                                <?php else: ?>
                                    <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Nonaktif</span>
                                <?php endif; ?>
                            </a>
                        </td>
                        <td class="text-end pe-4 text-nowrap">
                            <div class="d-inline-flex align-items-center justify-content-end gap-1">
                                <button type="button" class="btn btn-sm btn-outline-primary d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;padding:0;border-radius:7px;" title="Edit Pertanyaan FAQ" onclick='editFaq(<?= json_encode($fq, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                    <i class="bi bi-pencil-square"></i>
                                </button>
                                <a href="<?= SITE_URL ?>/admin/faq-setting.php?delete=<?= $fq['id'] ?>" class="btn btn-sm btn-outline-danger d-inline-flex align-items-center justify-content-center" style="width:32px;height:32px;padding:0;border-radius:7px;" title="Hapus Pertanyaan FAQ" onclick="return confirm('Hapus pertanyaan FAQ ini?')">
                                    <i class="bi bi-trash"></i>
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

<!-- Modal Add/Edit FAQ -->
<div class="modal fade" id="modalFaq" tabindex="-1" aria-labelledby="modalFaqLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="save_faq" value="1">
            <input type="hidden" name="id" id="faq_id" value="0">

            <div class="modal-header bg-light">
                <h5 class="modal-title fw-bold" id="modalFaqLabel" style="color:var(--navy);">Tambah Pertanyaan FAQ</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="row g-3">
                    <div class="col-md-8">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label small fw-bold mb-0">Kategori FAQ</label>
                            <a href="kategori-faq.php" target="_blank" class="small text-decoration-none text-primary fw-semibold" style="font-size:0.75rem;">
                                <i class="bi bi-gear-fill me-1"></i>Kelola Kategori
                            </a>
                        </div>
                        <select name="kategori" id="faq_kategori" class="form-select form-select-sm" required>
                            <?php foreach ($faq_categories as $fcat): ?>
                                <option value="<?= htmlspecialchars($fcat) ?>"><?= htmlspecialchars($fcat) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Nomor Urutan</label>
                        <input type="number" name="urutan" id="faq_urutan" class="form-control form-control-sm" value="1" min="1" required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Pertanyaan</label>
                        <input type="text" name="pertanyaan" id="faq_pertanyaan" class="form-control" placeholder="Tuliskan pertanyaan..." required>
                    </div>
                    <div class="col-12">
                        <label class="form-label small fw-bold">Jawaban Lengkap</label>
                        <textarea name="jawaban" id="faq_jawaban" rows="5" class="form-control" placeholder="Tuliskan jawaban yang jelas dan informatif..." required></textarea>
                    </div>
                    <div class="col-12">
                        <div class="form-check form-switch mt-2">
                            <input class="form-check-input" type="checkbox" name="is_active" id="faq_is_active" value="1" checked>
                            <label class="form-check-label fw-semibold" for="faq_is_active">Aktifkan dan tampilkan di website publik</label>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold" style="background:var(--navy);border:none;">
                    <i class="bi bi-save me-1"></i> Simpan FAQ
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openFaqModal() {
    document.getElementById('modalFaqLabel').textContent = 'Tambah Pertanyaan FAQ';
    document.getElementById('faq_id').value = '0';
    document.getElementById('faq_pertanyaan').value = '';
    document.getElementById('faq_jawaban').value = '';
    const katSelect = document.getElementById('faq_kategori');
    if (katSelect.options.length > 0) {
        katSelect.selectedIndex = 0;
    }
    document.getElementById('faq_urutan').value = '<?= count($faqs) + 1 ?>';
    document.getElementById('faq_is_active').checked = true;
    new bootstrap.Modal(document.getElementById('modalFaq')).show();
}

function editFaq(item) {
    document.getElementById('modalFaqLabel').textContent = 'Edit Pertanyaan FAQ';
    document.getElementById('faq_id').value = item.id;
    document.getElementById('faq_pertanyaan').value = item.pertanyaan;
    document.getElementById('faq_jawaban').value = item.jawaban;
    
    const katSelect = document.getElementById('faq_kategori');
    let found = false;
    for (let i = 0; i < katSelect.options.length; i++) {
        if (katSelect.options[i].value === item.kategori) {
            katSelect.selectedIndex = i;
            found = true;
            break;
        }
    }
    if (!found && item.kategori) {
        const opt = document.createElement('option');
        opt.value = item.kategori;
        opt.textContent = item.kategori;
        katSelect.appendChild(opt);
        katSelect.value = item.kategori;
    }

    document.getElementById('faq_urutan').value = item.urutan;
    document.getElementById('faq_is_active').checked = (item.is_active == 1);
    new bootstrap.Modal(document.getElementById('modalFaq')).show();
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
