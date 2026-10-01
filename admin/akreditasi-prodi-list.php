<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Manajemen Akreditasi Program Studi';
$db = getDB();

if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = $db->prepare("SELECT file_sertifikat FROM akreditasi_prodi WHERE id = ?");
    $row->execute([$id]);
    $data = $row->fetch();
    if ($data && $data['file_sertifikat']) {
        $path = __DIR__ . '/../uploads/akreditasi_prodi/' . $data['file_sertifikat'];
        if (file_exists($path)) {
            unlink($path);
        }
    }
    $db->prepare("DELETE FROM akreditasi_prodi WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Data Akreditasi Program Studi berhasil dihapus.';
    redirect(SITE_URL . '/admin/akreditasi-prodi-list.php');
}

// Handle Form Pengaturan Section "STATUS AKREDITASI NASIONAL PROGRAM STUDI"
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_rekap_nasional') {
    $title = trim($_POST['akred_nasional_title'] ?? 'STATUS AKREDITASI NASIONAL PROGRAM STUDI');
    $desc  = trim($_POST['akred_nasional_desc'] ?? '');
    $mode  = ($_POST['akred_nasional_mode'] ?? 'otomatis') === 'manual' ? 'manual' : 'otomatis';

    setPengaturan('akred_nasional_title', $title);
    setPengaturan('akred_nasional_desc', $desc);
    setPengaturan('akred_nasional_mode', $mode);

    if ($mode === 'manual') {
        $custom_data = [
            'peringkat' => $_POST['custom_peringkat'] ?? [],
            'lembaga'   => $_POST['custom_lembaga'] ?? [],
            'jenjang'   => $_POST['custom_jenjang'] ?? [],
        ];
        foreach ($custom_data as $cat => $items) {
            foreach ($items as $k => $v) {
                $custom_data[$cat][$k] = (int)$v;
            }
        }
        setPengaturan('akred_nasional_custom_data', json_encode($custom_data));
    }

    $_SESSION['flash'] = 'Pengaturan Bagian "Status Akreditasi Nasional Program Studi" berhasil disimpan.';
    redirect(SITE_URL . '/admin/akreditasi-prodi-list.php');
}

$akred_nasional_title = getPengaturan('akred_nasional_title', 'STATUS AKREDITASI NASIONAL PROGRAM STUDI');
$akred_nasional_desc  = getPengaturan('akred_nasional_desc', 'Ringkasan capaian status akreditasi seluruh program studi Universitas Katolik Soegijapranata berdasarkan peringkat, lembaga akreditasi resmi, dan jenjang pendidikan.');
$akred_nasional_mode  = getPengaturan('akred_nasional_mode', 'otomatis');
$akred_nasional_custom = json_decode(getPengaturan('akred_nasional_custom_data', '{}'), true);

$list = $db->query("SELECT * FROM akreditasi_prodi ORDER BY fakultas ASC, strata ASC, program_studi ASC")->fetchAll(PDO::FETCH_OBJ);

// Hitung rekapitulasi otomatis dari data prodi
$auto_peringkat = ['Unggul' => 0, 'Baik Sekali' => 0, 'Baik' => 0, 'Terakreditasi' => 0, 'Terakreditasi Sementara' => 0];
$auto_lembaga   = ['BAN-PT' => 0, 'LAMEMBA' => 0, 'LAM-PTKes' => 0, 'LAM INFOKOM' => 0, 'LAMTEK' => 0, 'LAM SAMA' => 0, 'LAMDIK' => 0];
$auto_jenjang   = ['Sarjana' => 0, 'Magister' => 0, 'Doktor' => 0, 'Profesi' => 0];

foreach ($list as $p) {
    $per = trim($p->peringkat);
    if (isset($auto_peringkat[$per])) $auto_peringkat[$per]++;
    else $auto_peringkat[$per] = 1;

    $lem = trim($p->lembaga);
    if (isset($auto_lembaga[$lem])) $auto_lembaga[$lem]++;
    else $auto_lembaga[$lem] = 1;

    $str = trim($p->strata);
    if (stripos($str, 'S3') !== false || stripos($str, 'Doktor') !== false) $auto_jenjang['Doktor']++;
    elseif (stripos($str, 'S2') !== false || stripos($str, 'Magister') !== false) $auto_jenjang['Magister']++;
    elseif (stripos($str, 'Profesi') !== false) $auto_jenjang['Profesi']++;
    else $auto_jenjang['Sarjana']++;
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <?= e($flash) ?>
</div>
<?php endif; ?>

<!-- Panel Pengaturan Section "STATUS AKREDITASI NASIONAL PROGRAM STUDI" -->
<div class="admin-table-wrap mb-4" style="background:#fff;border-radius:14px;border:1px solid #E2E8F0;box-shadow:0 2px 10px rgba(0,0,0,0.03);overflow:hidden;">
    <div class="p-3 px-4 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background:linear-gradient(135deg, #0A192F 0%, #1E293B 100%);color:#fff;">
        <div class="d-flex align-items-center gap-2">
            <div style="width:32px;height:32px;border-radius:8px;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;color:#FBBF24;">
                <i class="bi bi-sliders2"></i>
            </div>
            <div>
                <div style="font-family:var(--font-heading);font-weight:700;font-size:0.95rem;color:#fff;">
                    Pengaturan Section: STATUS AKREDITASI NASIONAL PROGRAM STUDI
                </div>
                <div style="font-size:0.75rem;color:rgba(255,255,255,0.7);">
                    Ubah teks judul, deskripsi, serta pilih mode hitung otomatis atau input manual rekapitulasi.
                </div>
            </div>
        </div>
        <button type="button" class="btn btn-sm btn-outline-light d-flex align-items-center gap-1" style="font-size:0.8rem;border-radius:6px;" data-bs-toggle="collapse" data-bs-target="#collapseRekapNasional" aria-expanded="false">
            <i class="bi bi-pencil-square"></i>
            <span>Buka / Tutup Pengaturan</span>
        </button>
    </div>

    <div class="collapse" id="collapseRekapNasional">
        <form method="POST" style="padding:1.5rem;">
            <input type="hidden" name="action" value="save_rekap_nasional">
            
            <div class="row g-3 mb-4">
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.85rem;color:var(--navy);">Judul Section di Website</label>
                    <input type="text" name="akred_nasional_title" class="form-control" style="border:1.5px solid var(--border);border-radius:8px;" value="<?= e($akred_nasional_title) ?>" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label fw-bold" style="font-size:0.85rem;color:var(--navy);">Mode Rekapitulasi</label>
                    <select name="akred_nasional_mode" id="rekapModeSelect" class="form-select" style="border:1.5px solid var(--border);border-radius:8px;" onchange="toggleManualRekap(this.value)">
                        <option value="otomatis" <?= $akred_nasional_mode === 'otomatis' ? 'selected' : '' ?>>Otomatis (Dihitung dari data tabel Program Studi di bawah)</option>
                        <option value="manual" <?= $akred_nasional_mode === 'manual' ? 'selected' : '' ?>>Kustom / Manual (Input angka secara manual)</option>
                    </select>
                </div>
                <div class="col-12">
                    <label class="form-label fw-bold" style="font-size:0.85rem;color:var(--navy);">Deskripsi / Pengantar Section</label>
                    <textarea name="akred_nasional_desc" class="form-control" rows="2" style="border:1.5px solid var(--border);border-radius:8px;"><?= e($akred_nasional_desc) ?></textarea>
                </div>
            </div>

            <!-- Bagian Input Angka Manual (Muncul jika mode Manual) -->
            <div id="manualRekapWrap" style="<?= $akred_nasional_mode === 'manual' ? 'display:block;' : 'display:none;' ?>;background:#F8FAFC;border:1px solid #E2E8F0;border-radius:10px;padding:1.25rem;margin-bottom:1.5rem;">
                <div class="d-flex align-items-center gap-2 mb-3">
                    <span class="badge bg-warning text-dark"><i class="bi bi-info-circle-fill"></i> Mode Input Manual Aktif</span>
                    <span style="font-size:0.8rem;color:var(--text-muted);">Isi angka yang ingin ditampilkan pada 3 kartu rekapitulasi di halaman akreditasi publik:</span>
                </div>

                <div class="row g-4">
                    <!-- Kartu 1: Peringkat -->
                    <div class="col-md-4">
                        <div class="card p-3 h-100 shadow-none border">
                            <h6 class="fw-bold mb-3" style="color:var(--navy);font-size:0.88rem;border-bottom:2px solid #CBD5E1;padding-bottom:0.4rem;">
                                1. Peringkat Akreditasi
                            </h6>
                            <?php 
                            $peringkat_keys = ['Unggul', 'Baik Sekali', 'Baik', 'Terakreditasi', 'Terakreditasi Sementara'];
                            foreach ($peringkat_keys as $pk): 
                                $val_p = isset($akred_nasional_custom['peringkat'][$pk]) ? (int)$akred_nasional_custom['peringkat'][$pk] : ($auto_peringkat[$pk] ?? 0);
                            ?>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label style="font-size:0.8rem;font-weight:600;margin:0;"><?= $pk ?></label>
                                <input type="number" min="0" name="custom_peringkat[<?= $pk ?>]" class="form-control form-control-sm text-end" style="width:75px;" value="<?= $val_p ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Kartu 2: Lembaga -->
                    <div class="col-md-4">
                        <div class="card p-3 h-100 shadow-none border">
                            <h6 class="fw-bold mb-3" style="color:var(--navy);font-size:0.88rem;border-bottom:2px solid #CBD5E1;padding-bottom:0.4rem;">
                                2. Lembaga Akreditasi (LAM)
                            </h6>
                            <?php 
                            $lembaga_keys = ['BAN-PT', 'LAMEMBA', 'LAM-PTKes', 'LAM INFOKOM', 'LAMTEK', 'LAM SAMA', 'LAMDIK'];
                            foreach ($lembaga_keys as $lk): 
                                $val_l = isset($akred_nasional_custom['lembaga'][$lk]) ? (int)$akred_nasional_custom['lembaga'][$lk] : ($auto_lembaga[$lk] ?? 0);
                            ?>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label style="font-size:0.8rem;font-weight:600;margin:0;"><?= $lk ?></label>
                                <input type="number" min="0" name="custom_lembaga[<?= $lk ?>]" class="form-control form-control-sm text-end" style="width:75px;" value="<?= $val_l ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>

                    <!-- Kartu 3: Jenjang -->
                    <div class="col-md-4">
                        <div class="card p-3 h-100 shadow-none border">
                            <h6 class="fw-bold mb-3" style="color:var(--navy);font-size:0.88rem;border-bottom:2px solid #CBD5E1;padding-bottom:0.4rem;">
                                3. Jenjang Pendidikan
                            </h6>
                            <?php 
                            $jenjang_keys = ['Sarjana', 'Magister', 'Doktor', 'Profesi'];
                            foreach ($jenjang_keys as $jk): 
                                $val_j = isset($akred_nasional_custom['jenjang'][$jk]) ? (int)$akred_nasional_custom['jenjang'][$jk] : ($auto_jenjang[$jk] ?? 0);
                            ?>
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <label style="font-size:0.8rem;font-weight:600;margin:0;"><?= $jk ?></label>
                                <input type="number" min="0" name="custom_jenjang[<?= $jk ?>]" class="form-control form-control-sm text-end" style="width:75px;" value="<?= $val_j ?>">
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div class="d-flex justify-content-end gap-2">
                <button type="submit" class="btn btn-sm btn-primary px-4 py-2" style="font-weight:600;background:var(--navy);border-color:var(--navy);border-radius:8px;">
                    <i class="bi bi-check2-circle me-1"></i> Simpan Pengaturan Status Akreditasi
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function toggleManualRekap(mode) {
    const wrap = document.getElementById('manualRekapWrap');
    if (mode === 'manual') {
        wrap.style.display = 'block';
    } else {
        wrap.style.display = 'none';
    }
}
</script>

<div class="admin-table-wrap">
    <div class="admin-table-topbar">
        <div class="admin-table-title">Daftar Rincian Program Studi (<?= count($list) ?>)</div>
        <a href="akreditasi-prodi-form.php" class="btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Prodi
        </a>
    </div>

    <?php if (empty($list)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-muted);">
        Belum ada data akreditasi prodi.
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th>Program Studi</th>
                    <th>Fakultas</th>
                    <th>Peringkat</th>
                    <th>SK & Lembaga</th>
                    <th>Masa Berlaku</th>
                    <th width="130">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $today = new DateTime();
                foreach ($list as $d): 
                    $berlaku = new DateTime($d->masa_berlaku);
                    $diff = (int)$today->diff($berlaku)->format("%r%a");
                    $is_expired = ($diff <= 0);
                    $is_warning = ($diff > 0 && $diff <= 730);
                ?>
                <tr <?= $is_expired ? 'style="background-color: #fff5f5;"' : ($is_warning ? 'style="background-color: #fffdf0;"' : '') ?>>
                    <td>
                        <div style="font-weight:600;color:var(--navy);"><?= e($d->strata) ?> <?= e($d->program_studi) ?></div>
                        <?php if ($d->file_sertifikat): ?>
                            <a href="<?= SITE_URL ?>/uploads/akreditasi_prodi/<?= e($d->file_sertifikat) ?>" target="_blank" class="small text-primary text-decoration-none mt-1 d-inline-block"><i class="bi bi-file-earmark-pdf"></i> Lihat File</a>
                        <?php endif; ?>
                    </td>
                    <td><div class="small"><?= e($d->fakultas) ?></div></td>
                    <td><span class="badge bg-purple px-2 py-1"><?= e($d->peringkat) ?></span></td>
                    <td>
                        <div style="font-size:0.85rem;font-weight:600;"><?= e($d->lembaga) ?></div>
                        <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($d->no_sk) ?></div>
                    </td>
                    <td>
                        <?php if ($is_expired): ?>
                            <div class="text-danger fw-bold" style="font-size:0.85rem;">
                                <i class="bi bi-exclamation-triangle-fill"></i> Kadaluarsa:<br>
                                <?= $berlaku->format('d M Y') ?>
                            </div>
                        <?php elseif ($is_warning): ?>
                            <div class="text-warning-emphasis fw-bold" style="font-size:0.85rem;color:#B45309;">
                                <span class="badge bg-warning text-dark mb-1" style="font-size:0.7rem;"><i class="bi bi-clock-history"></i> Segera Habis (&le; 2 thn)</span><br>
                                <?= $berlaku->format('d M Y') ?> <span class="text-muted fw-normal">(sisa <?= $diff ?> hr)</span>
                            </div>
                        <?php else: ?>
                            <div class="text-success" style="font-size:0.85rem;">
                                <?= $berlaku->format('d M Y') ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <a href="akreditasi-prodi-form.php?id=<?= $d->id ?>" class="btn-action btn-edit">Edit</a>
                            <a href="akreditasi-prodi-list.php?delete=<?= $d->id ?>" class="btn-action btn-delete" onclick="return confirm('Yakin hapus data ini?')">Hapus</a>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
