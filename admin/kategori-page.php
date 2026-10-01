<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Kategori Halaman Website';
$db = getDB();

$flash = '';
$error = '';

// Handle Delete (Dengan opsi relokasi halaman jika ada halaman terkait)
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $target_kategori = trim($_POST['target_kategori'] ?? 'Umum');

    $row = $db->prepare("SELECT id, nama_kategori FROM kategori_page WHERE id = ?");
    $row->execute([$id]);
    $kat = $row->fetch();

    if ($kat) {
        $count_stmt = $db->prepare("SELECT COUNT(*) FROM pages WHERE CONVERT(kategori USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci");
        $count_stmt->execute([$kat['nama_kategori']]);
        $total_pages = (int)$count_stmt->fetchColumn();

        if ($total_pages > 0) {
            $up = $db->prepare("UPDATE pages SET kategori = ? WHERE CONVERT(kategori USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci");
            $up->execute([$target_kategori, $kat['nama_kategori']]);
        }

        $db->prepare("DELETE FROM kategori_page WHERE id = ?")->execute([$id]);
        $_SESSION['flash'] = 'Kategori Halaman "' . $kat['nama_kategori'] . '" berhasil dihapus.' . ($total_pages > 0 ? " ($total_pages halaman dialihkan ke kategori $target_kategori)" : '');
    }
    redirect(SITE_URL . '/admin/kategori-page.php');
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action     = $_POST['action'];
    $nama       = trim($_POST['nama_kategori'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $urutan     = (int)($_POST['urutan'] ?? 0);
    $id         = (int)($_POST['id'] ?? 0);

    if (!$nama) {
        $error = 'Nama kategori halaman wajib diisi.';
    } else {
        $slug = makeSlug($nama);

        if ($action === 'create') {
            $check = $db->prepare("SELECT id FROM kategori_page WHERE slug = ? OR nama_kategori = ?");
            $check->execute([$slug, $nama]);
            if ($check->fetch()) {
                $error = "Kategori halaman '{$nama}' sudah ada.";
            } else {
                $stmt = $db->prepare("INSERT INTO kategori_page (nama_kategori, slug, keterangan, urutan) VALUES (?, ?, ?, ?)");
                $stmt->execute([$nama, $slug, $keterangan, $urutan]);
                $_SESSION['flash'] = "Kategori halaman baru '{$nama}' berhasil ditambahkan.";
                redirect(SITE_URL . '/admin/kategori-page.php');
            }
        } elseif ($action === 'edit' && $id > 0) {
            $old_stmt = $db->prepare("SELECT id, nama_kategori FROM kategori_page WHERE id = ?");
            $old_stmt->execute([$id]);
            $old_kat = $old_stmt->fetch();

            if ($old_kat) {
                $check = $db->prepare("SELECT id FROM kategori_page WHERE (slug = ? OR nama_kategori = ?) AND id != ?");
                $check->execute([$slug, $nama, $id]);
                if ($check->fetch()) {
                    $error = "Nama kategori '{$nama}' sudah digunakan kategori lain.";
                } else {
                    $stmt = $db->prepare("UPDATE kategori_page SET nama_kategori = ?, slug = ?, keterangan = ?, urutan = ? WHERE id = ?");
                    $stmt->execute([$nama, $slug, $keterangan, $urutan, $id]);

                    // Sinkronisasi kategori pada tabel pages jika nama kategori berubah
                    if ($old_kat['nama_kategori'] !== $nama) {
                        $up_page = $db->prepare("UPDATE pages SET kategori = ? WHERE CONVERT(kategori USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci");
                        $up_page->execute([$nama, $old_kat['nama_kategori']]);
                        $affected = $up_page->rowCount();
                    } else {
                        $affected = 0;
                    }

                    $_SESSION['flash'] = "Kategori halaman berhasil diperbarui menjadi '{$nama}'." . ($affected > 0 ? " ($affected halaman otomatis disinkronkan)" : '');
                    redirect(SITE_URL . '/admin/kategori-page.php');
                }
            }
        }
    }
}

$kategori_list = $db->query("SELECT * FROM kategori_page ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
$count_stmt = $db->prepare("SELECT COUNT(*) FROM pages WHERE CONVERT(kategori USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci");
$total_all_pages = (int)$db->query("SELECT COUNT(*) FROM pages")->fetchColumn();

foreach ($kategori_list as &$k) {
    $count_stmt->execute([$k['nama_kategori']]);
    $k['total_page'] = (int)$count_stmt->fetchColumn();
}
unset($k);

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="content-header d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
    <div>
        <div class="d-flex align-items-center gap-2 mb-1">
            <a href="page-list.php" class="text-decoration-none text-muted small">&larr; Kembali ke Daftar Halaman</a>
        </div>
        <h1 style="font-size:1.35rem;font-weight:800;color:var(--navy);margin:0;">Kelola Kategori Halaman Website</h1>
        <p style="font-size:0.875rem;color:var(--text-muted);margin:0;">
            Kelola pengelompokan halaman statis dan halaman dinamis LPM UNIKA (Profil, SPMI, AMI, Akreditasi, dll.).
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="page-list.php" class="btn btn-outline-secondary btn-sm fw-semibold d-flex align-items-center gap-1" style="border-radius:8px;">
            <i class="bi bi-file-earmark-text"></i> Daftar Halaman
        </a>
        <button type="button" class="btn-primary-lpm btn-sm d-flex align-items-center gap-1" data-bs-toggle="modal" data-bs-target="#modalTambahKategori">
            <i class="bi bi-plus-lg"></i> Tambah Kategori Halaman
        </button>
    </div>
</div>

<?php if ($flash): ?>
    <div class="alert alert-success alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
        <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($flash) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<?php if ($error): ?>
    <div class="alert alert-danger alert-dismissible fade show rounded-3 mb-4 shadow-sm" role="alert">
        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= htmlspecialchars($error) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
<?php endif; ?>

<!-- Stats Overview -->
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-lg-4">
        <div class="p-3 bg-white rounded-3 border d-flex align-items-center gap-3 shadow-xs">
            <div style="width:44px;height:44px;border-radius:10px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;color:#6D28D9;font-size:1.25rem;">
                <i class="bi bi-folder-fill"></i>
            </div>
            <div>
                <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;">Total Kategori Halaman</div>
                <div style="font-size:1.35rem;font-weight:800;color:var(--navy);"><?= count($kategori_list) ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="p-3 bg-white rounded-3 border d-flex align-items-center gap-3 shadow-xs">
            <div style="width:44px;height:44px;border-radius:10px;background:#E0F2FE;display:flex;align-items:center;justify-content:center;color:#0369A1;font-size:1.25rem;">
                <i class="bi bi-file-earmark-richtext-fill"></i>
            </div>
            <div>
                <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;">Total Halaman Terdaftar</div>
                <div style="font-size:1.35rem;font-weight:800;color:var(--navy);"><?= $total_all_pages ?></div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-lg-4">
        <div class="p-3 bg-white rounded-3 border d-flex align-items-center gap-3 shadow-xs">
            <div style="width:44px;height:44px;border-radius:10px;background:#DCFCE7;display:flex;align-items:center;justify-content:center;color:#15803D;font-size:1.25rem;">
                <i class="bi bi-check-circle-fill"></i>
            </div>
            <div>
                <div style="font-size:0.75rem;color:var(--text-muted);font-weight:600;text-transform:uppercase;">Kategori Aktif Terpakai</div>
                <div style="font-size:1.35rem;font-weight:800;color:var(--navy);">
                    <?= count(array_filter($kategori_list, fn($c) => $c['total_page'] > 0)) ?>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card-table">
    <div class="table-responsive">
        <table class="table mb-0 align-middle">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th width="80" class="text-center">Urutan</th>
                    <th>Nama Kategori Halaman</th>
                    <th>Slug</th>
                    <th>Keterangan</th>
                    <th width="150" class="text-center">Jumlah Halaman</th>
                    <th width="140" class="text-end pe-3">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($kategori_list)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-4 text-muted">
                            Belum ada kategori halaman yang ditambahkan.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($kategori_list as $kat): ?>
                        <tr>
                            <td class="text-center">
                                <span class="badge bg-light text-dark border"><?= (int)$kat['urutan'] ?></span>
                            </td>
                            <td>
                                <strong style="color:var(--navy);font-size:0.95rem;"><?= htmlspecialchars($kat['nama_kategori']) ?></strong>
                            </td>
                            <td>
                                <code style="font-size:0.8rem;background:#F1F5F9;padding:2px 8px;border-radius:4px;color:#475569;"><?= htmlspecialchars($kat['slug']) ?></code>
                            </td>
                            <td class="text-muted" style="font-size:0.85rem;max-width:260px;">
                                <?= htmlspecialchars($kat['keterangan'] ?: '-') ?>
                            </td>
                            <td class="text-center">
                                <?php if ($kat['total_page'] > 0): ?>
                                    <a href="page-list.php?kategori=<?= urlencode($kat['nama_kategori']) ?>" class="badge rounded-pill fw-semibold text-decoration-none" style="background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;padding:5px 12px;">
                                        <i class="bi bi-file-earmark-text me-1"></i> <?= $kat['total_page'] ?> Halaman
                                    </a>
                                <?php else: ?>
                                    <span class="badge bg-light text-muted border">0 Halaman</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end pe-3">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" title="Edit Kategori"
                                        onclick='openEditModal(<?= json_encode($kat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        <i class="bi bi-pencil-square"></i>
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-danger" title="Hapus Kategori"
                                        onclick='openDeleteModal(<?= json_encode($kat, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP) ?>)'>
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Tambah Kategori -->
<div class="modal fade" id="modalTambahKategori" tabindex="-1" aria-labelledby="modalTambahKategoriLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="create">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalTambahKategoriLabel" style="color:var(--navy);">
                    <i class="bi bi-folder-plus text-primary me-2"></i> Tambah Kategori Halaman
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kategori" class="form-control" placeholder="Contoh: Tata Pamong, Kerjasama, dll." required>
                    <div class="form-text">Nama kategori digunakan untuk mengelompokkan halaman pada daftar dan filter halaman.</div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Keterangan / Catatan Singkat</label>
                    <textarea name="keterangan" class="form-control" rows="2" placeholder="Deskripsi ringkas cakupan kategori halaman ini..."></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nomor Urutan Tampilan</label>
                    <input type="number" name="urutan" class="form-control" value="<?= count($kategori_list) + 1 ?>" min="1">
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold" style="background:var(--navy);border:none;">
                    <i class="bi bi-save me-1"></i> Simpan Kategori
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Edit Kategori -->
<div class="modal fade" id="modalEditKategori" tabindex="-1" aria-labelledby="modalEditKategoriLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="edit">
            <input type="hidden" name="id" id="edit_id">
            <div class="modal-header border-bottom">
                <h5 class="modal-title fw-bold" id="modalEditKategoriLabel" style="color:var(--navy);">
                    <i class="bi bi-pencil-square text-primary me-2"></i> Edit Kategori Halaman
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <div class="alert alert-info py-2 px-3 small rounded-3 mb-3">
                    <i class="bi bi-info-circle me-1"></i> Perubahan nama kategori akan <strong>secara otomatis memperbarui</strong> data kategori pada seluruh halaman yang menggunakannya.
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nama Kategori <span class="text-danger">*</span></label>
                    <input type="text" name="nama_kategori" id="edit_nama" class="form-control" required>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Keterangan / Catatan Singkat</label>
                    <textarea name="keterangan" id="edit_keterangan" class="form-control" rows="2"></textarea>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-bold">Nomor Urutan Tampilan</label>
                    <input type="number" name="urutan" id="edit_urutan" class="form-control" min="1">
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold" style="background:var(--navy);border:none;">
                    <i class="bi bi-check-lg me-1"></i> Simpan Perubahan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Konfirmasi Hapus Kategori -->
<div class="modal fade" id="modalDeleteKategori" tabindex="-1" aria-labelledby="modalDeleteKategoriLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content border-0 shadow">
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="id" id="delete_id">
            <div class="modal-header border-bottom bg-danger-subtle text-danger">
                <h5 class="modal-title fw-bold" id="modalDeleteKategoriLabel">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> Hapus Kategori Halaman
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-4">
                <p class="mb-3" style="font-size:0.95rem;">
                    Anda yakin ingin menghapus kategori halaman <strong id="delete_nama" class="text-danger"></strong>?
                </p>

                <div id="delete_has_page" style="display:none;">
                    <div class="alert alert-warning py-2 px-3 small rounded-3 mb-3">
                        <i class="bi bi-exclamation-circle-fill me-1"></i> Kategori ini memiliki <strong id="delete_count">0</strong> halaman terdaftar. Silakan pilih kategori tujuan untuk memindahkan halaman-halaman tersebut agar tidak hilang:
                    </div>
                    <label class="form-label small fw-bold">Pindahkan Halaman ke Kategori:</label>
                    <select name="target_kategori" id="delete_target_kategori" class="form-select form-select-sm mb-2">
                        <?php foreach ($kategori_list as $k_opt): ?>
                            <option value="<?= htmlspecialchars($k_opt['nama_kategori']) ?>"><?= htmlspecialchars($k_opt['nama_kategori']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div id="delete_no_page" style="display:none;">
                    <p class="text-muted small mb-0">Tidak ada halaman yang terhubung dengan kategori ini. Kategori dapat dihapus dengan aman.</p>
                </div>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                <button type="submit" class="btn btn-sm btn-danger px-4 fw-bold">
                    <i class="bi bi-trash me-1"></i> Hapus Kategori Sekarang
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function openEditModal(kat) {
    document.getElementById('edit_id').value = kat.id;
    document.getElementById('edit_nama').value = kat.nama_kategori;
    document.getElementById('edit_keterangan').value = kat.keterangan || '';
    document.getElementById('edit_urutan').value = kat.urutan || '1';
    new bootstrap.Modal(document.getElementById('modalEditKategori')).show();
}

function openDeleteModal(kat) {
    document.getElementById('delete_id').value = kat.id;
    document.getElementById('delete_nama').textContent = '"' + kat.nama_kategori + '"';

    const hasPage = kat.total_page > 0;
    document.getElementById('delete_has_page').style.display = hasPage ? 'block' : 'none';
    document.getElementById('delete_no_page').style.display = hasPage ? 'none' : 'block';

    if (hasPage) {
        document.getElementById('delete_count').textContent = kat.total_page;
        const targetSelect = document.getElementById('delete_target_kategori');
        for (let i = 0; i < targetSelect.options.length; i++) {
            if (targetSelect.options[i].value === kat.nama_kategori) {
                targetSelect.options[i].disabled = true;
            } else {
                targetSelect.options[i].disabled = false;
                targetSelect.options[i].selected = true;
            }
        }
    }
    new bootstrap.Modal(document.getElementById('modalDeleteKategori')).show();
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
