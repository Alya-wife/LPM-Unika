<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola 8 Butir Kuesioner Survei Kepuasan';
$db = getDB();

// 1. Handle Add Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $tipe       = in_array($_POST['tipe'], ['skala', 'teks']) ? $_POST['tipe'] : 'skala';
    $kategori   = trim($_POST['kategori'] ?? 'Umum');
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $urutan     = (int)($_POST['urutan'] ?? 0);
    $is_aktif   = isset($_POST['is_aktif']) ? 1 : 0;

    if (!empty($pertanyaan)) {
        $stmt = $db->prepare("INSERT INTO kunjungan_kuesioner_pertanyaan (tipe, kategori, pertanyaan, keterangan, urutan, is_aktif) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$tipe, $kategori, $pertanyaan, $keterangan, $urutan, $is_aktif]);
        $_SESSION['flash'] = 'Butir pertanyaan kuesioner berhasil ditambahkan.';
    } else {
        $_SESSION['flash_error'] = 'Kalimat pertanyaan tidak boleh kosong.';
    }
    redirect(SITE_URL . '/admin/feedback-kunjungan-pertanyaan.php');
}

// 2. Handle Edit Question
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_question') {
    $id         = (int)($_POST['id'] ?? 0);
    $tipe       = in_array($_POST['tipe'], ['skala', 'teks']) ? $_POST['tipe'] : 'skala';
    $kategori   = trim($_POST['kategori'] ?? 'Umum');
    $pertanyaan = trim($_POST['pertanyaan'] ?? '');
    $keterangan = trim($_POST['keterangan'] ?? '');
    $urutan     = (int)($_POST['urutan'] ?? 0);
    $is_aktif   = isset($_POST['is_aktif']) ? 1 : 0;

    if ($id > 0 && !empty($pertanyaan)) {
        $stmt = $db->prepare("UPDATE kunjungan_kuesioner_pertanyaan SET tipe = ?, kategori = ?, pertanyaan = ?, keterangan = ?, urutan = ?, is_aktif = ? WHERE id = ?");
        $stmt->execute([$tipe, $kategori, $pertanyaan, $keterangan, $urutan, $is_aktif, $id]);
        $_SESSION['flash'] = 'Butir pertanyaan kuesioner berhasil diperbarui.';
    } else {
        $_SESSION['flash_error'] = 'Gagal memperbarui: Data pertanyaan tidak valid.';
    }
    redirect(SITE_URL . '/admin/feedback-kunjungan-pertanyaan.php');
}

// 3. Handle Toggle Status Active
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("UPDATE kunjungan_kuesioner_pertanyaan SET is_aktif = IF(is_aktif=1, 0, 1) WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = 'Status aktif butir pertanyaan berhasil diubah.';
    redirect(SITE_URL . '/admin/feedback-kunjungan-pertanyaan.php');
}

// 4. Handle Delete Question
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("DELETE FROM kunjungan_kuesioner_pertanyaan WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Butir pertanyaan berhasil dihapus.';
    redirect(SITE_URL . '/admin/feedback-kunjungan-pertanyaan.php');
}

// Fetch all questions
$questions = $db->query("SELECT * FROM kunjungan_kuesioner_pertanyaan ORDER BY tipe ASC, urutan ASC, id ASC")->fetchAll();

$flash       = $_SESSION['flash'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_error']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.3rem;font-weight:800;color:var(--navy);margin:0;display:flex;align-items:center;gap:8px;">
            <i class="bi bi-list-check text-primary"></i>
            <span>Kelola 8 Butir Kuesioner Survei Kepuasan Layanan</span>
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Atur butir pertanyaan skala Likert 1-5 dan masukan saran terbuka untuk formulir Survey Kepuasan Layanan LPM.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="feedback-kunjungan-list.php" class="btn-outline">
            <i class="bi bi-arrow-left me-1"></i> Rekap Survei
        </a>
        <a href="layanan-form-setting.php?tab=feedback" class="btn-outline">
            <i class="bi bi-gear me-1"></i> Pengaturan Form Survei
        </a>
        <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#modalAddQuestion">
            <i class="bi bi-plus-circle me-1"></i> Tambah Pertanyaan
        </button>
        <a href="<?= SITE_URL ?>/survei-kepuasan.php" target="_blank" class="btn-outline text-primary">
            <i class="bi bi-box-arrow-up-right me-1"></i> Form Publik &nearr;
        </a>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4">
    <i class="bi bi-check-circle-fill me-2"></i> <?= $flash ?>
</div>
<?php endif; ?>

<?php if ($flash_error): ?>
<div class="alert-lpm alert-danger mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($flash_error) ?>
</div>
<?php endif; ?>

<div class="admin-table-wrap p-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center">
        <h5 style="font-weight:700;color:var(--navy);font-size:1rem;margin:0;">
            Daftar Butir Pertanyaan (Total: <?= count($questions) ?>)
        </h5>
        <span style="font-size:0.8rem;color:var(--text-muted);">
            Pertanyaan aktif akan otomatis tampil pada formulir feedback tamu
        </span>
    </div>

    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th style="width:60px;text-align:center;">Urutan</th>
                    <th style="width:110px;">Tipe</th>
                    <th style="width:150px;">Kategori</th>
                    <th>Kalimat Pertanyaan &amp; Petunjuk</th>
                    <th style="width:110px;text-align:center;">Status</th>
                    <th style="width:140px;text-align:center;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($questions)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Belum ada butir pertanyaan kuesioner.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($questions as $q): ?>
                <tr>
                    <td style="text-align:center;font-weight:700;color:var(--navy);">
                        <span class="badge bg-light text-dark border"><?= $q['urutan'] ?></span>
                    </td>
                    <td>
                        <?php if ($q['tipe'] === 'skala'): ?>
                        <span class="badge" style="background:#E0F2FE;color:#0284C7;font-size:0.75rem;padding:0.35rem 0.6rem;font-weight:700;">
                            <i class="bi bi-star-fill text-warning me-1"></i> Skala 1–5
                        </span>
                        <?php else: ?>
                        <span class="badge" style="background:#F3E8FF;color:#7E22CE;font-size:0.75rem;padding:0.35rem 0.6rem;font-weight:700;">
                            <i class="bi bi-pencil-square me-1"></i> Teks Saran
                        </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span style="font-size:0.8rem;color:#475569;font-weight:600;">
                            <?= e($q['kategori'] ?? 'Umum') ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-weight:700;color:var(--navy);"><?= e($q['pertanyaan']) ?></div>
                        <?php if (!empty($q['keterangan'])): ?>
                        <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;"><?= e($q['keterangan']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <a href="feedback-kunjungan-pertanyaan.php?action=toggle_status&id=<?= $q['id'] ?>" class="badge <?= $q['is_aktif'] ? 'bg-success' : 'bg-secondary' ?>" style="font-size:0.75rem;text-decoration:none;padding:0.35rem 0.6rem;" title="Klik untuk mengubah status">
                            <?= $q['is_aktif'] ? 'Aktif' : 'Nonaktif' ?>
                        </a>
                    </td>
                    <td style="text-align:center;">
                        <div class="d-inline-flex gap-1">
                            <button type="button" class="btn-action btn-edit" onclick='openEditModal(<?= json_encode($q) ?>)' title="Edit Butir Pertanyaan">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                            <a href="feedback-kunjungan-pertanyaan.php?action=delete&id=<?= $q['id'] ?>" class="btn-action btn-delete" onclick="return confirm('Apakah Anda yakin ingin menghapus butir pertanyaan ini?');" title="Hapus Pertanyaan">
                                <i class="bi bi-trash3"></i> Hapus
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

<!-- MODAL: Tambah Pertanyaan -->
<div class="modal fade" id="modalAddQuestion" tabindex="-1" aria-labelledby="modalAddQuestionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--radius-md);border:1px solid var(--border);">
            <div class="modal-header" style="background:var(--navy);color:#fff;">
                <h5 class="modal-title" id="modalAddQuestionLabel" style="font-size:1rem;font-weight:700;">
                    <i class="bi bi-plus-circle me-2"></i> Tambah Butir Pertanyaan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="feedback-kunjungan-pertanyaan.php">
                <input type="hidden" name="action" value="add_question">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;">Tipe Pertanyaan</label>
                        <select name="tipe" class="form-select" required style="border:1.5px solid var(--border);">
                            <option value="skala" selected>Skala Likert (Pilihan Angka 1 s/d 5)</option>
                            <option value="teks">Teks Terbuka (Kolom Masukan & Saran)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;">Kategori Aspek</label>
                        <input type="text" name="kategori" class="form-control" placeholder="Contoh: Pelayanan & Fasilitas, Materi Mutu, Manajemen Waktu" required style="border:1.5px solid var(--border);">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;">Kalimat Pertanyaan <span class="text-danger">*</span></label>
                        <textarea name="pertanyaan" rows="3" class="form-control" placeholder="Tuliskan pertanyaan evaluasi..." required style="border:1.5px solid var(--border);"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;">Petunjuk / Keterangan Tambahan (Opsional)</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Penjelasan singkat aspek yang dinilai..." style="border:1.5px solid var(--border);">
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-bold" style="font-size:0.85rem;">Nomor Urutan Tampil</label>
                            <input type="number" name="urutan" class="form-control" value="0" min="0" style="border:1.5px solid var(--border);">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_aktif" value="1" id="checkAktif" checked>
                                <label class="form-check-label fw-bold" for="checkAktif" style="font-size:0.85rem;">
                                    Aktif Digunakan
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-top:1px solid var(--border);">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-save"><i class="bi bi-check2-circle me-1"></i> Simpan Pertanyaan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: Edit Pertanyaan -->
<div class="modal fade" id="modalEditQuestion" tabindex="-1" aria-labelledby="modalEditQuestionLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--radius-md);border:1px solid var(--border);">
            <div class="modal-header" style="background:var(--navy);color:#fff;">
                <h5 class="modal-title" id="modalEditQuestionLabel" style="font-size:1rem;font-weight:700;">
                    <i class="bi bi-pencil me-2"></i> Edit Butir Pertanyaan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="feedback-kunjungan-pertanyaan.php">
                <input type="hidden" name="action" value="edit_question">
                <input type="hidden" name="id" id="edit_id">
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;">Tipe Pertanyaan</label>
                        <select name="tipe" id="edit_tipe" class="form-select" required style="border:1.5px solid var(--border);">
                            <option value="skala">Skala Likert (Pilihan Angka 1 s/d 5)</option>
                            <option value="teks">Teks Terbuka (Kolom Masukan & Saran)</option>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;">Kategori Aspek</label>
                        <input type="text" name="kategori" id="edit_kategori" class="form-control" required style="border:1.5px solid var(--border);">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;">Kalimat Pertanyaan <span class="text-danger">*</span></label>
                        <textarea name="pertanyaan" id="edit_pertanyaan" rows="3" class="form-control" required style="border:1.5px solid var(--border);"></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;">Petunjuk / Keterangan Tambahan</label>
                        <input type="text" name="keterangan" id="edit_keterangan" class="form-control" style="border:1.5px solid var(--border);">
                    </div>

                    <div class="row g-3">
                        <div class="col-6">
                            <label class="form-label fw-bold" style="font-size:0.85rem;">Nomor Urutan Tampil</label>
                            <input type="number" name="urutan" id="edit_urutan" class="form-control" min="0" style="border:1.5px solid var(--border);">
                        </div>
                        <div class="col-6 d-flex align-items-end">
                            <div class="form-check mb-2">
                                <input class="form-check-input" type="checkbox" name="is_aktif" value="1" id="edit_is_aktif">
                                <label class="form-check-label fw-bold" for="edit_is_aktif" style="font-size:0.85rem;">
                                    Aktif Digunakan
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-top:1px solid var(--border);">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-save"><i class="bi bi-check2-circle me-1"></i> Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openEditModal(q) {
    document.getElementById('edit_id').value = q.id;
    document.getElementById('edit_tipe').value = q.tipe;
    document.getElementById('edit_kategori').value = q.kategori || '';
    document.getElementById('edit_pertanyaan').value = q.pertanyaan;
    document.getElementById('edit_keterangan').value = q.keterangan || '';
    document.getElementById('edit_urutan').value = q.urutan;
    document.getElementById('edit_is_aktif').checked = (q.is_aktif == 1);

    var modal = new bootstrap.Modal(document.getElementById('modalEditQuestion'));
    modal.show();
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
