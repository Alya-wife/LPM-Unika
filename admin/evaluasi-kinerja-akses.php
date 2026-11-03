<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Akses Akun Google Evaluasi Kinerja';
$current_admin    = 'evaluasi-kinerja-akses';
$db = getDB();

// 1. Handle Tambah Akun
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_user') {
    $nama    = trim($_POST['nama'] ?? '');
    $email   = strtolower(trim($_POST['email'] ?? ''));
    $jabatan = trim($_POST['jabatan'] ?? '');
    $peran   = in_array($_POST['peran'] ?? '', ['kepala', 'personel']) ? $_POST['peran'] : 'personel';
    $status  = isset($_POST['status']) ? 1 : 0;

    if (empty($nama) || empty($email)) {
        $_SESSION['flash_error'] = 'Nama lengkap dan Email Google wajib diisi.';
    } else {
        // Cek duplikasi email
        $check = $db->prepare("SELECT COUNT(*) FROM evaluasi_peserta WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['flash_error'] = "Email {$email} sudah terdaftar sebagai peserta evaluasi.";
        } else {
            $stmt = $db->prepare("INSERT INTO evaluasi_peserta (nama, email, jabatan, peran, status) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $email, $jabatan, $peran, $status]);
            $_SESSION['flash'] = "Akun Google '{$email}' berhasil didaftarkan.";
        }
    }
    redirect(SITE_URL . '/admin/evaluasi-kinerja-akses.php');
    exit;
}

// 2. Handle Edit Akun
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_user') {
    $id      = (int)($_POST['id'] ?? 0);
    $nama    = trim($_POST['nama'] ?? '');
    $email   = strtolower(trim($_POST['email'] ?? ''));
    $jabatan = trim($_POST['jabatan'] ?? '');
    $peran   = in_array($_POST['peran'] ?? '', ['kepala', 'personel']) ? $_POST['peran'] : 'personel';
    $status  = isset($_POST['status']) ? 1 : 0;

    if ($id > 0 && !empty($nama) && !empty($email)) {
        // Cek duplikasi email pada akun lain
        $check = $db->prepare("SELECT COUNT(*) FROM evaluasi_peserta WHERE email = ? AND id != ?");
        $check->execute([$email, $id]);
        if ($check->fetchColumn() > 0) {
            $_SESSION['flash_error'] = "Email {$email} sudah digunakan oleh akun lain.";
        } else {
            $stmt = $db->prepare("UPDATE evaluasi_peserta SET nama = ?, email = ?, jabatan = ?, peran = ?, status = ? WHERE id = ?");
            $stmt->execute([$nama, $email, $jabatan, $peran, $status, $id]);
            $_SESSION['flash'] = "Data akun Google '{$email}' berhasil diperbarui.";
        }
    } else {
        $_SESSION['flash_error'] = 'Data perubahan tidak lengkap atau tidak valid.';
    }
    redirect(SITE_URL . '/admin/evaluasi-kinerja-akses.php');
    exit;
}

// 3. Handle Toggle Status
if (isset($_GET['action']) && $_GET['action'] === 'toggle_status' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("UPDATE evaluasi_peserta SET status = IF(status=1, 0, 1) WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = 'Status aktif akun Google berhasil diperbarui.';
    redirect(SITE_URL . '/admin/evaluasi-kinerja-akses.php');
    exit;
}

// 4. Handle Hapus Akun
if (isset($_GET['action']) && $_GET['action'] === 'delete' && isset($_GET['id'])) {
    $id = (int)$_GET['id'];
    $stmt = $db->prepare("DELETE FROM evaluasi_peserta WHERE id = ?");
    $stmt->execute([$id]);
    $_SESSION['flash'] = 'Akun peserta evaluasi berhasil dihapus.';
    redirect(SITE_URL . '/admin/evaluasi-kinerja-akses.php');
    exit;
}

// 5. Handle Import dari Tim LPM
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'sync_tim_lpm') {
    $timMembers = $db->query("SELECT nama, jabatan, kategori, level FROM tim_lpm WHERE kategori = 'lpm'")->fetchAll(PDO::FETCH_ASSOC);
    $added = 0;
    foreach ($timMembers as $tm) {
        $cleanName = trim($tm['nama']);
        $check = $db->prepare("SELECT COUNT(*) FROM evaluasi_peserta WHERE nama = ?");
        $check->execute([$cleanName]);
        if ($check->fetchColumn() == 0) {
            // Generate dummy official email if needed
            $slug = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', explode(' ', $cleanName)[0]));
            $email = $slug . '@unika.ac.id';
            $peran = (strpos(strtolower($tm['jabatan']), 'kepala') !== false && strpos(strtolower($tm['jabatan']), 'pusat') === false) ? 'kepala' : 'personel';
            
            $ins = $db->prepare("INSERT INTO evaluasi_peserta (nama, email, jabatan, peran, status) VALUES (?, ?, ?, ?, 1)");
            $ins->execute([$cleanName, $email, $tm['jabatan'], $peran]);
            $added++;
        }
    }
    $_SESSION['flash'] = "Sinkronisasi selesai. Sebanyak {$added} personel baru berhasil ditambahkan dari Tim LPM.";
    redirect(SITE_URL . '/admin/evaluasi-kinerja-akses.php');
    exit;
}

// Ambil Seluruh Data Peserta
$pesertaList = $db->query("SELECT * FROM evaluasi_peserta ORDER BY peran ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

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
            <span style="color:var(--navy);">Kelola Akses Akun Google</span>
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

        <div class="admin-table-wrap p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--navy);">
                        <i class="bi bi-person-badge-fill text-primary me-2"></i> Manajemen Akses Akun Google Peserta Evaluasi
                    </h4>
                    <p class="text-muted mb-0 small">
                        Hanya akun Google yang terdaftar aktif di bawah ini yang dapat login ke portal Evaluasi Kinerja LPM.
                    </p>
                </div>
                <div class="d-flex gap-2">
                    <form method="POST" action="" onsubmit="return confirm('Sinkronkan seluruh data personel dari tabel Tim LPM?');">
                        <input type="hidden" name="action" value="sync_tim_lpm">
                        <button type="submit" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                            <i class="bi bi-arrow-repeat me-1"></i> Sinkronisasi dari Tim LPM
                        </button>
                    </form>
                    <button type="button" class="btn btn-sm btn-hero-primary" data-bs-toggle="modal" data-bs-target="#modalTambahAkun" style="border-radius:8px;">
                        <i class="bi bi-plus-circle me-1"></i> Tambah Akun Google
                    </button>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead class="table-light text-uppercase small text-muted" style="letter-spacing:0.5px;">
                        <tr>
                            <th style="width:50px;">No</th>
                            <th>Nama Lengkap</th>
                            <th>Email Google</th>
                            <th>Jabatan / Posisi</th>
                            <th class="text-center" style="width:140px;">Tingkatan Peran</th>
                            <th class="text-center" style="width:110px;">Status</th>
                            <th class="text-end" style="width:130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($pesertaList)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="bi bi-people display-6 d-block mb-2"></i>
                                Belum ada akun peserta evaluasi yang didaftarkan.
                            </td>
                        </tr>
                        <?php else: 
                            $no = 1;
                            foreach ($pesertaList as $p):
                        ?>
                        <tr>
                            <td class="text-muted fw-bold"><?= $no++ ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:36px;height:36px;border-radius:50%;background:#F1F5F9;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--navy);font-size:0.9rem;">
                                        <?php if (!empty($p['avatar'])): ?>
                                            <img src="<?= e($p['avatar']) ?>" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                                        <?php else: ?>
                                            <?= strtoupper(substr($p['nama'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div class="fw-bold" style="color:var(--navy);"><?= e($p['nama']) ?></div>
                                </div>
                            </td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace" style="font-size:0.83rem;font-weight:500;">
                                    <i class="bi bi-google text-danger me-1"></i> <?= e($p['email']) ?>
                                </span>
                            </td>
                            <td class="text-muted small"><?= e($p['jabatan']) ?></td>
                            <td class="text-center">
                                <?php if ($p['peran'] === 'kepala'): ?>
                                    <span class="badge" style="background:#FEF3C7;color:#92400E;border:1px solid #FCD34D;font-size:0.75rem;">
                                        <i class="bi bi-star-fill me-1"></i> Kepala LPM
                                    </span>
                                <?php else: ?>
                                    <span class="badge" style="background:#E0E7FF;color:#3730A3;border:1px solid #C7D2FE;font-size:0.75rem;">
                                        <i class="bi bi-person-fill me-1"></i> Personel LPM
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ((int)$p['status'] === 1): ?>
                                    <a href="<?= SITE_URL ?>/admin/evaluasi-kinerja-akses.php?action=toggle_status&id=<?= $p['id'] ?>" class="badge bg-success text-decoration-none" title="Klik untuk menonaktifkan">
                                        <i class="bi bi-check-circle me-1"></i> Aktif
                                    </a>
                                <?php else: ?>
                                    <a href="<?= SITE_URL ?>/admin/evaluasi-kinerja-akses.php?action=toggle_status&id=<?= $p['id'] ?>" class="badge bg-danger text-decoration-none" title="Klik untuk mengaktifkan">
                                        <i class="bi bi-x-circle me-1"></i> Nonaktif
                                    </a>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="d-inline-flex gap-1">
                                    <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#modalEditAkun_<?= $p['id'] ?>" title="Edit Akun" style="padding:0.25rem 0.5rem;">
                                        <i class="bi bi-pencil"></i>
                                    </button>
                                    <a href="<?= SITE_URL ?>/admin/evaluasi-kinerja-akses.php?action=delete&id=<?= $p['id'] ?>" class="btn btn-sm btn-outline-danger" onclick="return confirm('Apakah Anda yakin ingin menghapus akun peserta ini? Seluruh riwayat jawaban yang pernah diinput akan tetap tercatat di sistem.');" title="Hapus Akun" style="padding:0.25rem 0.5rem;">
                                        <i class="bi bi-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>

                        <!-- MODAL EDIT AKUN -->
                        <div class="modal fade" id="modalEditAkun_<?= $p['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog">
                                <div class="modal-content" style="border-radius:var(--radius-lg);overflow:hidden;">
                                    <div class="modal-header bg-navy text-white" style="background:var(--navy);">
                                        <h5 class="modal-title fw-bold" style="font-size:1.05rem;">
                                            <i class="bi bi-pencil-square me-2"></i> Edit Akun Google Peserta
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="POST" action="">
                                        <input type="hidden" name="action" value="edit_user">
                                        <input type="hidden" name="id" value="<?= $p['id'] ?>">

                                        <div class="modal-body p-4">
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Nama Lengkap &amp; Gelar <span class="text-danger">*</span></label>
                                                <input type="text" name="nama" class="form-control" value="<?= e($p['nama']) ?>" required>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Email Google Akun <span class="text-danger">*</span></label>
                                                <input type="email" name="email" class="form-control" value="<?= e($p['email']) ?>" required>
                                                <div class="form-text">Dapat berupa email kampus @unika.ac.id atau akun Google yang didaftarkan.</div>
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Jabatan / Posisi di LPM</label>
                                                <input type="text" name="jabatan" class="form-control" value="<?= e($p['jabatan']) ?>">
                                            </div>
                                            <div class="mb-3">
                                                <label class="form-label fw-bold">Tingkatan Peran Akun <span class="text-danger">*</span></label>
                                                <select name="peran" class="form-select" required>
                                                    <option value="personel" <?= $p['peran'] === 'personel' ? 'selected' : '' ?>>Personel LPM (Evaluasi Diri &amp; Antar Rekan)</option>
                                                    <option value="kepala" <?= $p['peran'] === 'kepala' ? 'selected' : '' ?>>Kepala LPM (Penilaian Kinerja Seluruh Personel &amp; Refleksi)</option>
                                                </select>
                                            </div>
                                            <div class="form-check form-switch mt-3">
                                                <input class="form-check-input" type="checkbox" name="status" id="statusEdit_<?= $p['id'] ?>" value="1" <?= (int)$p['status'] === 1 ? 'checked' : '' ?>>
                                                <label class="form-check-label fw-bold" for="statusEdit_<?= $p['id'] ?>">Status Akun Aktif (Izinkan Login)</label>
                                            </div>
                                        </div>
                                        <div class="modal-footer border-top">
                                            <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn btn-hero-primary px-4">
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

<!-- MODAL TAMBAH AKUN BARU -->
<div class="modal fade" id="modalTambahAkun" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content" style="border-radius:var(--radius-lg);overflow:hidden;">
            <div class="modal-header bg-navy text-white" style="background:var(--navy);">
                <h5 class="modal-title fw-bold" style="font-size:1.05rem;">
                    <i class="bi bi-person-plus-fill me-2"></i> Daftarkan Akun Google Baru
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="">
                <input type="hidden" name="action" value="add_user">

                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold">Nama Lengkap &amp; Gelar <span class="text-danger">*</span></label>
                        <input type="text" name="nama" class="form-control" placeholder="Contoh: dr. Maya Yanuarty, M.Biomed" required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Email Akun Google <span class="text-danger">*</span></label>
                        <input type="email" name="email" class="form-control" placeholder="Contoh: nama@unika.ac.id" required>
                        <div class="form-text">Hanya email yang didaftarkan di sini yang diizinkan masuk ke portal.</div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Jabatan / Posisi di LPM</label>
                        <input type="text" name="jabatan" class="form-control" placeholder="Contoh: Ketua Pusat Audit Mutu Internal">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold">Tingkatan Peran Akun <span class="text-danger">*</span></label>
                        <select name="peran" class="form-select" required>
                            <option value="personel" selected>Personel LPM (Evaluasi Diri &amp; Antar Rekan)</option>
                            <option value="kepala">Kepala LPM (Penilaian Kinerja Seluruh Personel &amp; Refleksi)</option>
                        </select>
                    </div>
                    <div class="form-check form-switch mt-3">
                        <input class="form-check-input" type="checkbox" name="status" id="statusAdd" value="1" checked>
                        <label class="form-check-label fw-bold" for="statusAdd">Status Akun Aktif Langsung</label>
                    </div>
                </div>
                <div class="modal-footer border-top">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-hero-primary px-4">
                        <i class="bi bi-check-circle me-1"></i> Daftarkan Akun
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin-footer.php';
?>

