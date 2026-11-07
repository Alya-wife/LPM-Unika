<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Pertanyaan Evaluasi Kinerja';
$current_admin    = 'evaluasi-kinerja-pertanyaan';
$db = getDB();

// 1. Handle Tambah Pertanyaan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_question') {
    $kategori     = in_array($_POST['kategori'] ?? '', ['diri', 'antar_personel', 'kepala']) ? $_POST['kategori'] : 'diri';
    $bagian       = trim($_POST['bagian'] ?? '');
    $nomor_urut   = (int)($_POST['nomor_urut'] ?? 1);
    $pertanyaan   = trim($_POST['pertanyaan'] ?? '');
    $tipe_jawaban = in_array($_POST['tipe_jawaban'] ?? '', ['teks', 'skala_3', 'skala_5']) ? $_POST['tipe_jawaban'] : 'teks';
    $keterangan   = trim($_POST['keterangan'] ?? '');
    $is_active    = isset($_POST['is_active']) ? 1 : 0;

    if (!empty($pertanyaan)) {
        $stmt = $db->prepare("INSERT INTO evaluasi_pertanyaan (kategori, bagian, nomor_urut, pertanyaan, tipe_jawaban, keterangan, is_active) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$kategori, $bagian, $nomor_urut, $pertanyaan, $tipe_jawaban, $keterangan, $is_active]);
        $_SESSION['flash'] = 'Butir pertanyaan evaluasi berhasil ditambahkan.';
    } else {
        $_SESSION['flash_error'] = 'Pertanyaan tidak boleh kosong.';
    }
    redirect(SITE_URL . '/admin/evaluasi-kinerja-pertanyaan.php?tab=' . $kategori);
    exit;
}

// 2. Handle Edit Pertanyaan
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_question') {
    $id           = (int)($_POST['id'] ?? 0);
    $kategori     = in_array($_POST['kategori'] ?? '', ['diri', 'antar_personel', 'kepala']) ? $_POST['kategori'] : 'diri';
    $bagian       = trim($_POST['bagian'] ?? '');
    $nomor_urut   = (int)($_POST['nomor_urut'] ?? 1);
    $pertanyaan   = trim($_POST['pertanyaan'] ?? '');
    $tipe_jawaban = in_array($_POST['tipe_jawaban'] ?? '', ['teks', 'skala_3', 'skala_5']) ? $_POST['tipe_jawaban'] : 'teks';
    $keterangan   = trim($_POST['keterangan'] ?? '');
    $is_active    = isset($_POST['is_active']) ? 1 : 0;

    if ($id > 0 && !empty($pertanyaan)) {
        $stmt = $db->prepare("UPDATE evaluasi_pertanyaan SET kategori = ?, bagian = ?, nomor_urut = ?, pertanyaan = ?, tipe_jawaban = ?, keterangan = ?, is_active = ? WHERE id = ?");
        $stmt->execute([$kategori, $bagian, $nomor_urut, $pertanyaan, $tipe_jawaban, $keterangan, $is_active, $id]);
        $_SESSION['flash'] = 'Butir pertanyaan berhasil diperbarui.';
    } else {
        $_SESSION['flash_error'] = 'Gagal menyimpan: Kalimat pertanyaan tidak valid.';
    }
    redirect(SITE_URL . '/admin/evaluasi-kinerja-pertanyaan.php?tab=' . $kategori);
    exit;
}

// 3. Handle Toggle Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("UPDATE evaluasi_pertanyaan SET is_active = IF(is_active=1, 0, 1) WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = 'Status aktif pertanyaan berhasil diubah.';
    $tab = $_GET['tab'] ?? 'diri';
    redirect(SITE_URL . '/admin/evaluasi-kinerja-pertanyaan.php?tab=' . urlencode($tab));
    exit;
}

// 4. Handle Hapus
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("DELETE FROM evaluasi_pertanyaan WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = 'Butir pertanyaan berhasil dihapus.';
    $tab = $_GET['tab'] ?? 'diri';
    redirect(SITE_URL . '/admin/evaluasi-kinerja-pertanyaan.php?tab=' . urlencode($tab));
    exit;
}

// Ambil Pertanyaan berdasarkan kategori
$active_tab = $_GET['tab'] ?? 'diri';
if (!in_array($active_tab, ['diri', 'antar_personel', 'kepala'])) {
    $active_tab = 'diri';
}

$stmtQ = $db->prepare("SELECT * FROM evaluasi_pertanyaan WHERE kategori = ? ORDER BY nomor_urut ASC, id ASC");
$stmtQ->execute([$active_tab]);
$pertanyaanList = $stmtQ->fetchAll(PDO::FETCH_ASSOC);

// Hitung total pertanyaan per tab
$countDiri  = (int)$db->query("SELECT COUNT(*) FROM evaluasi_pertanyaan WHERE kategori = 'diri'")->fetchColumn();
$countAntar = (int)$db->query("SELECT COUNT(*) FROM evaluasi_pertanyaan WHERE kategori = 'antar_personel'")->fetchColumn();
$countKepala = (int)$db->query("SELECT COUNT(*) FROM evaluasi_pertanyaan WHERE kategori = 'kepala'")->fetchColumn();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-11 col-lg-12">
        
        <!-- Breadcrumb -->
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <span>Layanan</span>
            <span>/</span>
            <span style="color:var(--navy);">Kelola Pertanyaan Evaluasi</span>
        </div>

        <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert-lpm alert-success mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill"></i>
            <div><?= e($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
        </div>
        <?php endif; ?>

        <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert-lpm alert-danger mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-exclamation-triangle-fill"></i>
            <div><?= e($_SESSION['flash_error']); unset($_SESSION['flash_error']); ?></div>
        </div>
        <?php endif; ?>

        <!-- Nav Tabs Kategori Pertanyaan -->
        <ul class="nav nav-pills mb-4 p-2 bg-white rounded-3 shadow-sm border">
            <li class="nav-item">
                <a class="nav-link py-2 px-3 fw-bold <?= $active_tab === 'diri' ? 'active' : '' ?>" href="<?= SITE_URL ?>/admin/evaluasi-kinerja-pertanyaan.php?tab=diri">
                    <i class="bi bi-person-fill me-1"></i> Penilaian Pribadi (Diri Sendiri)
                    <span class="badge rounded-pill bg-light text-dark ms-2"><?= $countDiri ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-2 px-3 fw-bold <?= $active_tab === 'antar_personel' ? 'active' : '' ?>" href="<?= SITE_URL ?>/admin/evaluasi-kinerja-pertanyaan.php?tab=antar_personel">
                    <i class="bi bi-people-fill me-1"></i> Penilaian Antar Personel (Rekan Kerja)
                    <span class="badge rounded-pill bg-light text-dark ms-2"><?= $countAntar ?></span>
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link py-2 px-3 fw-bold <?= $active_tab === 'kepala' ? 'active' : '' ?>" href="<?= SITE_URL ?>/admin/evaluasi-kinerja-pertanyaan.php?tab=kepala">
                    <i class="bi bi-award-fill me-1"></i> Penilaian oleh Kepala LPM
                    <span class="badge rounded-pill bg-light text-dark ms-2"><?= $countKepala ?></span>
                </a>
            </li>
        </ul>

        <div class="admin-table-wrap p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--navy);">
                        <?php if ($active_tab === 'diri'): ?>
                            <i class="bi bi-journal-text text-primary me-2"></i> Butir Pertanyaan Evaluasi Diri (11 Butir)
                        <?php elseif ($active_tab === 'antar_personel'): ?>
                            <i class="bi bi-people-fill text-info me-2"></i> Butir Penilaian Antar Personel (9 Skala + 3 Terbuka)
                        <?php else: ?>
                            <i class="bi bi-award-fill text-warning me-2"></i> Butir Aspek Kinerja oleh Kepala LPM (12 Butir Skala 1–5 + Catatan)
                        <?php endif; ?>
                    </h4>
                    <p class="text-muted mb-0 small">
                        Admin dapat menyunting kalimat pertanyaan, mengatur nomor urut, serta mengelompokkan bagian.
                    </p>
                </div>
                <div>
                    <button type="button" class="btn btn-sm text-white d-inline-flex align-items-center gap-1 px-3 py-2" data-bs-toggle="modal" data-bs-target="#modalTambahPertanyaan" style="background:var(--navy);border:none;border-radius:8px;font-weight:600;font-size:0.83rem;box-shadow:0 2px 6px rgba(10,25,47,0.2);">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Pertanyaan
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th style="width:50px;">No</th>
                            <th style="width:180px;">Bagian / Kelompok</th>
                            <th>Kalimat Pertanyaan</th>
                            <th style="width:120px;">Tipe Jawaban</th>
                            <th class="text-center" style="width:100px;">Status</th>
                            <th class="text-end" style="width:120px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pertanyaanList)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5 text-muted">
                                <i class="bi bi-question-circle display-6 d-block mb-2"></i>
                                Belum ada butir pertanyaan pada kategori ini.
                            </td>
                        </tr>
                        <?php else: 
                            foreach ($pertanyaanList as $q):
                        ?>
                        <tr>
                            <td class="fw-bold text-muted"><?= (int)$q['nomor_urut'] ?></td>
                            <td>
                                <span class="badge bg-light text-dark border text-wrap text-start" style="font-size:0.75rem;line-height:1.4;">
                                    <?= e($q['bagian'] ?: 'Umum') ?>
                                </span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark" style="font-size:0.92rem;line-height:1.5;"><?= e($q['pertanyaan']) ?></div>
                                <?php if (!empty($q['keterangan'])): ?>
                                    <div class="text-muted small mt-1"><i class="bi bi-info-circle me-1"></i> <?= e($q['keterangan']) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($q['tipe_jawaban'] === 'teks'): ?>
                                    <span class="badge bg-secondary" style="font-size:0.72rem;">Isian Teks / Bukti</span>
                                <?php elseif ($q['tipe_jawaban'] === 'skala_3'): ?>
                                    <span class="badge bg-info text-dark" style="font-size:0.72rem;">Skala 1–3 &amp; N/A</span>
                                <?php else: ?>
                                    <span class="badge bg-purple text-white" style="background:var(--purple);font-size:0.72rem;">Skala 1–5 + Catatan</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ((int)$q['is_active'] === 1): ?>
                                    <a href="<?= SITE_URL ?>/admin/evaluasi-kinerja-pertanyaan.php?action=toggle_status&id=<?= $q['id'] ?>&tab=<?= $active_tab ?>" class="badge bg-success text-decoration-none">
                                        Aktif
                                    </a>
                                <?php else: ?>
                                    <a href="<?= SITE_URL ?>/admin/evaluasi-kinerja-pertanyaan.php?action=toggle_status&id=<?= $q['id'] ?>&tab=<?= $active_tab ?>" class="badge bg-danger text-decoration-none">
                                        Nonaktif
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditQ_<?= $q['id'] ?>" title="Edit Pertanyaan" style="padding:0.25rem 0.5rem;">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?= SITE_URL ?>/admin/evaluasi-kinerja-pertanyaan.php?action=delete&id=<?= $q['id'] ?>&tab=<?= $active_tab ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus butir pertanyaan ini?');" title="Hapus" style="padding:0.25rem 0.5rem;">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- MODAL EDIT PERTANYAAN -->
                        <div class="modal fade" id="modalEditQ_<?= $q['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-lg">
                                <div class="modal-content" style="border-radius:var(--radius-lg);overflow:hidden;">
                                    <div class="modal-header bg-navy text-white" style="background:var(--navy);">
                                        <h5 class="modal-title fw-bold" style="font-size:1.05rem;">
                                            <i class="bi bi-pencil-square me-2"></i> Edit Pertanyaan #<?= $q['nomor_urut'] ?>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="edit_question">
                                        <input type="hidden" name="id" value="<?= $q['id'] ?>">
                                        <input type="hidden" name="kategori" value="<?= $q['kategori'] ?>">

                                        <div class="modal-body p-4">
                                            <div class="row g-3 mb-3">
                                                <div class="col-md-3">
                                                    <label class="form-label fw-bold">Nomor Urut</label>
                                                    <input type="number" name="nomor_urut" class="form-control" value="<?= (int)$q['nomor_urut'] ?>" required>
                                                </div>
                                                <div class="col-md-5">
                                                    <label class="form-label fw-bold">Bagian / Kelompok</label>
                                                    <input type="text" name="bagian" class="form-control" value="<?= e($q['bagian']) ?>" placeholder="Contoh: Bagian I. Capaian Kinerja">
                                                </div>
                                                <div class="col-md-4">
                                                    <label class="form-label fw-bold">Tipe Jawaban</label>
                                                    <select name="tipe_jawaban" class="form-select">
                                                        <option value="teks" <?= $q['tipe_jawaban'] === 'teks' ? 'selected' : '' ?>>Isian Teks / Bukti Konkret</option>
                                                        <option value="skala_3" <?= $q['tipe_jawaban'] === 'skala_3' ? 'selected' : '' ?>>Skala 1–3 &amp; N/A (Antar Personel)</option>
                                                        <option value="skala_5" <?= $q['tipe_jawaban'] === 'skala_5' ? 'selected' : '' ?>>Skala 1–5 &amp; Catatan (Kepala LPM)</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Kalimat Pertanyaan <span class="text-danger">*</span></label>
                                                <textarea name="pertanyaan" class="form-control" rows="3" required><?= e($q['pertanyaan']) ?></textarea>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Keterangan / Petunjuk Tambahan</label>
                                                <input type="text" name="keterangan" class="form-control" value="<?= e($q['keterangan']) ?>" placeholder="Petunjuk pengisian untuk peserta">
                                            </div>

                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" name="is_active" id="activeEdit_<?= $q['id'] ?>" value="1" <?= (int)$q['is_active'] === 1 ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-bold" for="activeEdit_<?= $q['id'] ?>">Tampilkan di Formulir Peserta</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn text-white px-4" style="background:var(--navy);border-radius:8px;font-weight:600;">
                                                <i class="bi bi-save me-1"></i> Simpan Perubahan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <!-- END MODAL EDIT -->

                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

        </div>

    </div>
</div>

<!-- MODAL TAMBAH PERTANYAAN -->
<div class="modal fade" id="modalTambahPertanyaan" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content" style="border-radius:var(--radius-lg);overflow:hidden;">
            <div class="modal-header bg-navy text-white" style="background:var(--navy);">
                <h5 class="modal-title fw-bold" style="font-size:1.05rem;">
                    <i class="bi bi-plus-circle me-2"></i> Tambah Pertanyaan Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_question">
                <input type="hidden" name="kategori" value="<?= $active_tab ?>">

                <div class="modal-body p-4">
                    <div class="row g-3 mb-3">
                        <div class="col-md-3">
                            <label class="form-label fw-bold">Nomor Urut</label>
                            <input type="number" name="nomor_urut" class="form-control" value="<?= count($pertanyaanList) + 1 ?>" required>
                        </div>
                        <div class="col-md-5">
                            <label class="form-label fw-bold">Bagian / Kelompok</label>
                            <input type="text" name="bagian" class="form-control" placeholder="Contoh: Bagian I. Capaian Kinerja">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label fw-bold">Tipe Jawaban</label>
                            <select name="tipe_jawaban" class="form-select">
                                <option value="teks" <?= $active_tab === 'diri' ? 'selected' : '' ?>>Isian Teks / Bukti Konkret</option>
                                <option value="skala_3" <?= $active_tab === 'antar_personel' ? 'selected' : '' ?>>Skala 1–3 &amp; N/A (Antar Personel)</option>
                                <option value="skala_5" <?= $active_tab === 'kepala' ? 'selected' : '' ?>>Skala 1–5 &amp; Catatan (Kepala LPM)</option>
                            </select>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Kalimat Pertanyaan <span class="text-danger">*</span></label>
                        <textarea name="pertanyaan" class="form-control" rows="3" placeholder="Tuliskan butir pertanyaan atau pernyataan evaluasi..." required></textarea>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold">Keterangan / Petunjuk Tambahan</label>
                        <input type="text" name="keterangan" class="form-control" placeholder="Petunjuk pengisian untuk peserta">
                    </div>

                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="is_active" id="activeAdd" value="1" checked>
                        <label class="form-check-label fw-bold" for="activeAdd">Tampilkan di Formulir Peserta</label>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn text-white px-4" style="background:var(--navy);border-radius:8px;font-weight:600;">
                        <i class="bi bi-check-circle me-1"></i> Tambahkan Pertanyaan
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin-footer.php';
?>

