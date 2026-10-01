<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Kategori Berita & Kegiatan';
$db = getDB();

$flash = '';
$error = '';

// Handle Delete (Dengan opsi relokasi berita jika ada berita terkait)
if (isset($_POST['action']) && $_POST['action'] === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    $target_kategori = trim($_POST['target_kategori'] ?? 'Berita');

    $row = $db->prepare("SELECT id, nama_kategori FROM kategori_berita WHERE id = ?");
    $row->execute([$id]);
    $kat = $row->fetch();

    if ($kat) {
        $count_stmt = $db->prepare("SELECT COUNT(*) FROM berita WHERE CONVERT(tipe USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci");
        $count_stmt->execute([$kat['nama_kategori']]);
        $total_berita = (int)$count_stmt->fetchColumn();

        if ($total_berita > 0) {
            $up = $db->prepare("UPDATE berita SET tipe = ? WHERE CONVERT(tipe USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci");
            $up->execute([$target_kategori, $kat['nama_kategori']]);
        }

        $db->prepare("DELETE FROM kategori_berita WHERE id = ?")->execute([$id]);
        $_SESSION['flash'] = 'Kategori berita "' . $kat['nama_kategori'] . '" berhasil dihapus.' . ($total_berita > 0 ? " ($total_berita berita dialihkan ke kategori $target_kategori)" : '');
    }
    redirect(SITE_URL . '/admin/kategori-berita.php');
}

// Handle Add / Edit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $nama   = trim($_POST['nama_kategori'] ?? '');
    $id     = (int)($_POST['id'] ?? 0);

    if (!$nama) {
        $error = 'Nama kategori berita wajib diisi.';
    } else {
        $slug = makeSlug($nama);

        if ($action === 'create') {
            $check = $db->prepare("SELECT id FROM kategori_berita WHERE slug = ? OR nama_kategori = ?");
            $check->execute([$slug, $nama]);
            if ($check->fetch()) {
                $error = "Kategori berita '{$nama}' sudah ada.";
            } else {
                $stmt = $db->prepare("INSERT INTO kategori_berita (nama_kategori, slug) VALUES (?, ?)");
                $stmt->execute([$nama, $slug]);
                $_SESSION['flash'] = "Kategori berita baru '{$nama}' berhasil ditambahkan.";
                redirect(SITE_URL . '/admin/kategori-berita.php');
            }
        } elseif ($action === 'edit' && $id > 0) {
            $old_stmt = $db->prepare("SELECT id, nama_kategori FROM kategori_berita WHERE id = ?");
            $old_stmt->execute([$id]);
            $old_kat = $old_stmt->fetch();

            if ($old_kat) {
                $check = $db->prepare("SELECT id FROM kategori_berita WHERE (slug = ? OR nama_kategori = ?) AND id != ?");
                $check->execute([$slug, $nama, $id]);
                if ($check->fetch()) {
                    $error = "Nama kategori '{$nama}' sudah digunakan kategori lain.";
                } else {
                    $stmt = $db->prepare("UPDATE kategori_berita SET nama_kategori = ?, slug = ? WHERE id = ?");
                    $stmt->execute([$nama, $slug, $id]);

                    // Sinkronisasi tipe berita pada tabel berita jika berubah
                    if ($old_kat['nama_kategori'] !== $nama) {
                        $up_berita = $db->prepare("UPDATE berita SET tipe = ? WHERE CONVERT(tipe USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci");
                        $up_berita->execute([$nama, $old_kat['nama_kategori']]);
                        $affected = $up_berita->rowCount();
                    } else {
                        $affected = 0;
                    }

                    $_SESSION['flash'] = "Kategori berita berhasil diperbarui menjadi '{$nama}'." . ($affected > 0 ? " ($affected berita otomatis disinkronkan)" : '');
                    redirect(SITE_URL . '/admin/kategori-berita.php');
                }
            }
        }
    }
}

$kategori_list = $db->query("SELECT id, nama_kategori, slug FROM kategori_berita ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
$count_stmt = $db->prepare("SELECT COUNT(*) FROM berita WHERE CONVERT(tipe USING utf8mb4) COLLATE utf8mb4_unicode_ci = CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci");
foreach ($kategori_list as &$k) {
    $count_stmt->execute([$k['nama_kategori']]);
    $k['total_berita'] = (int)$count_stmt->fetchColumn();
}
unset($k);

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">Kategori Berita &amp; Kegiatan</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Kelola master kategori atau tipe publikasi artikel dan kegiatan LPM.</p>
    </div>
    <div class="d-flex gap-2">
        <a href="berita-list.php" class="btn-action" style="background:#fff;border:1.5px solid var(--border);color:var(--navy);text-decoration:none;">
            &larr; Daftar Berita &amp; Kegiatan
        </a>
        <a href="berita-form.php" class="btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tulis Berita Baru
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

<?php if ($error): ?>
<div class="alert-lpm alert-danger mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
    </svg>
    <?= e($error) ?>
</div>
<?php endif; ?>

<div class="row g-4">
    <!-- Form Tambah Kategori Berita -->
    <div class="col-lg-4">
        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div class="admin-table-title">Tambah Kategori Berita Baru</div>
            </div>
            <div style="padding:1.5rem;">
                <form method="POST">
                    <input type="hidden" name="action" value="create">
                    <div class="mb-3">
                        <label class="form-label" style="font-family:var(--font-heading);font-weight:600;font-size:0.85rem;color:var(--navy);">
                            Nama Kategori / Tipe Publikasi <span style="color:#C62828;">*</span>
                        </label>
                        <input type="text" name="nama_kategori" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;font-size:0.88rem;" placeholder="Contoh: Workshop Mutu, Pengumuman, dll" required>
                        <small class="text-muted d-block mt-1">Kategori baru otomatis muncul sebagai pilihan tipe saat menulis berita dan filter pencarian publik.</small>
                    </div>
                    <button type="submit" class="btn-submit w-100 fw-bold py-2" style="background:var(--navy);border-radius:8px;">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Kategori Berita
                    </button>
                </form>
            </div>
        </div>

        <div class="p-3 mt-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;font-size:0.8rem;color:var(--text-muted);line-height:1.6;">
            <i class="bi bi-info-circle-fill text-primary me-1"></i> <strong>Informasi Sinkronisasi:</strong>
            <br>Perubahan nama kategori akan otomatis menyinkronkan seluruh berita yang memiliki kategori bersangkutan.
        </div>
    </div>

    <!-- Tabel Daftar Kategori Berita -->
    <div class="col-lg-8">
        <div class="admin-table-wrap">
            <div class="admin-table-topbar d-flex justify-content-between align-items-center">
                <div class="admin-table-title">Daftar Kategori Berita (<?= count($kategori_list) ?>)</div>
                <small class="text-muted">Total <?= array_sum(array_column($kategori_list, 'total_berita')) ?> Berita Terdata</small>
            </div>
            <div style="overflow-x:auto;">
                <table class="admin-table">
                    <thead>
                        <tr>
                            <th width="40" style="text-align:center;">#</th>
                            <th>Nama Kategori</th>
                            <th width="140" style="text-align:center;">Jumlah Berita</th>
                            <th width="150" style="text-align:center;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($kategori_list)): ?>
                        <tr>
                            <td colspan="4" class="text-center py-4 text-muted">Belum ada kategori berita.</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($kategori_list as $idx => $k): ?>
                            <tr>
                                <td style="color:var(--text-muted);text-align:center;font-weight:600;"><?= $idx + 1 ?></td>
                                <td>
                                    <div style="font-weight:700;color:var(--navy);font-size:0.92rem;"><?= e($k['nama_kategori']) ?></div>
                                    <div style="font-size:0.75rem;color:var(--text-muted);">Slug: <code><?= e($k['slug']) ?></code></div>
                                </td>
                                <td style="text-align:center;">
                                    <a href="berita-list.php?kategori=<?= urlencode($k['nama_kategori']) ?>" class="badge text-decoration-none" style="background:#EDE9FE;color:#6D28D9;font-weight:700;font-size:0.78rem;padding:0.35rem 0.65rem;border-radius:6px;" title="Lihat berita dalam kategori ini">
                                        <i class="bi bi-newspaper me-1"></i> <?= (int)$k['total_berita'] ?> berita
                                    </a>
                                </td>
                                <td style="text-align:center;">
                                    <div class="d-flex justify-content-center gap-1">
                                        <button type="button" class="btn-action btn-edit" onclick="openEditModal(<?= $k['id'] ?>, '<?= e(addslashes($k['nama_kategori'])) ?>')" title="Edit Nama Kategori">
                                            <i class="bi bi-pencil-square"></i> Edit
                                        </button>
                                        <button type="button" class="btn-action btn-delete" onclick="openDeleteModal(<?= $k['id'] ?>, '<?= e(addslashes($k['nama_kategori'])) ?>', <?= (int)$k['total_berita'] ?>)" title="Hapus Kategori">
                                            <i class="bi bi-trash"></i> Hapus
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
    </div>
</div>

<!-- Modal Edit Kategori Berita -->
<div class="modal fade" id="modalEditKat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;border:none;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background:linear-gradient(135deg, var(--navy) 0%, #1E3A8A 100%);color:#fff;">
                <h5 class="modal-title fw-bold text-white" style="font-size:1rem;color:#ffffff !important;"><i class="bi bi-pencil-square me-2"></i> Edit Kategori Berita</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="edit">
                    <input type="hidden" name="id" id="edit_kat_id" value="">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Nama Kategori / Tipe <span style="color:#C62828;">*</span></label>
                        <input type="text" name="nama_kategori" id="edit_kat_nama" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" required>
                        <small class="text-muted d-block mt-1">Nama baru akan langsung disinkronkan ke semua berita yang memakai kategori ini.</small>
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-primary px-4 fw-bold" style="background:var(--navy);border:none;">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Hapus Kategori Berita -->
<div class="modal fade" id="modalDeleteKat" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:12px;overflow:hidden;border:none;box-shadow:0 10px 30px rgba(0,0,0,0.2);">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title fw-bold text-white" style="font-size:1rem;color:#ffffff !important;"><i class="bi bi-exclamation-triangle-fill me-2"></i> Konfirmasi Hapus Kategori Berita</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <form method="POST">
                <div class="modal-body p-4">
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="id" id="del_kat_id" value="">
                    
                    <p style="font-size:0.92rem;color:var(--navy);margin-bottom:0.75rem;">
                        Apakah Anda yakin ingin menghapus kategori berita <strong id="del_kat_nama"></strong>?
                    </p>

                    <div id="del_berita_warning" style="display:none;background:#FEF2F2;border:1px solid #FCA5A5;border-radius:8px;padding:0.85rem;" class="mb-3">
                        <div class="d-flex align-items-center gap-2 text-danger fw-bold small mb-1">
                            <i class="bi bi-info-circle-fill"></i> Peringatan Berita Terkait:
                        </div>
                        <div style="font-size:0.8rem;color:#7F1D1D;" class="mb-2">
                            Terdapat <strong id="del_berita_count">0</strong> berita di kategori ini. Silakan pilih kategori baru untuk memindahkan berita tersebut:
                        </div>
                        <select name="target_kategori" id="del_target_kategori" class="form-select form-select-sm" style="border-radius:6px;">
                            <?php foreach ($kategori_list as $other_k): ?>
                            <option value="<?= e($other_k['nama_kategori']) ?>"><?= e($other_k['nama_kategori']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div id="del_berita_safe" style="display:none;font-size:0.82rem;color:var(--text-muted);">
                        Kategori ini tidak memiliki berita terkait dan dapat dihapus langsung dengan aman.
                    </div>
                </div>
                <div class="modal-footer bg-light p-3">
                    <button type="button" class="btn btn-sm btn-outline-secondary px-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-sm btn-danger px-4 fw-bold">
                        <i class="bi bi-trash me-1"></i> Ya, Hapus Kategori
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(id, nama) {
    document.getElementById('edit_kat_id').value = id;
    document.getElementById('edit_kat_nama').value = nama;
    new bootstrap.Modal(document.getElementById('modalEditKat')).show();
}

function openDeleteModal(id, nama, count) {
    document.getElementById('del_kat_id').value = id;
    document.getElementById('del_kat_nama').textContent = '"' + nama + '"';
    
    var warningDiv = document.getElementById('del_berita_warning');
    var safeDiv = document.getElementById('del_berita_safe');
    var countSpan = document.getElementById('del_berita_count');
    var selectTarget = document.getElementById('del_target_kategori');

    if (count > 0) {
        warningDiv.style.display = 'block';
        safeDiv.style.display = 'none';
        countSpan.textContent = count;
        for (var i = 0; i < selectTarget.options.length; i++) {
            if (selectTarget.options[i].value === nama) {
                selectTarget.options[i].style.display = 'none';
            } else {
                selectTarget.options[i].style.display = '';
            }
        }
    } else {
        warningDiv.style.display = 'none';
        safeDiv.style.display = 'block';
    }

    new bootstrap.Modal(document.getElementById('modalDeleteKat')).show();
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
