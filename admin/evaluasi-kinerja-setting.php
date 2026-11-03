<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Pengaturan Evaluasi Kinerja';
$current_admin    = 'evaluasi-kinerja-setting';
$db = getDB();

// Handle Save Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $status_aktif   = isset($_POST['evaluasi_kinerja_aktif']) && $_POST['evaluasi_kinerja_aktif'] === '1' ? '1' : '0';
    $tahun_akademik = trim($_POST['evaluasi_tahun_akademik_aktif'] ?? '2025/2026');
    $judul          = trim($_POST['evaluasi_judul'] ?? 'Sistem Evaluasi Kinerja Personel LPM');
    $petunjuk       = trim($_POST['evaluasi_petunjuk_umum'] ?? '');

    $saveKeys = [
        'evaluasi_kinerja_aktif'        => $status_aktif,
        'evaluasi_tahun_akademik_aktif' => $tahun_akademik,
        'evaluasi_judul'                => $judul,
        'evaluasi_petunjuk_umum'        => $petunjuk,
    ];

    $stmtUpsert = $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)");
    foreach ($saveKeys as $k => $v) {
        $stmtUpsert->execute([$k, $v]);
    }

    $_SESSION['flash'] = 'Pengaturan Evaluasi Kinerja berhasil disimpan.';
    redirect(SITE_URL . '/admin/evaluasi-kinerja-setting.php');
    exit;
}

$cur_aktif = getPengaturan('evaluasi_kinerja_aktif', '1');
$cur_ta    = getPengaturan('evaluasi_tahun_akademik_aktif', '2025/2026');
$cur_judul = getPengaturan('evaluasi_judul', 'Sistem Evaluasi Kinerja Personel LPM');
$cur_petunjuk = getPengaturan('evaluasi_petunjuk_umum', 'Sistem evaluasi kinerja internal berkala Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata.');

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        
        <!-- Breadcrumb -->
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <span>Layanan</span>
            <span>/</span>
            <span style="color:var(--navy);">Pengaturan Evaluasi Kinerja</span>
        </div>

        <?php if (!empty($_SESSION['flash'])): ?>
        <div class="alert-lpm alert-success mb-4 d-flex align-items-center gap-2">
            <i class="bi bi-check-circle-fill"></i>
            <div><?= e($_SESSION['flash']); unset($_SESSION['flash']); ?></div>
        </div>
        <?php endif; ?>

        <div class="admin-table-wrap p-4 p-md-5 mb-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--navy);">
                        <i class="bi bi-gear-fill text-primary me-2"></i> Pengaturan Portal Evaluasi Kinerja
                    </h4>
                    <p class="text-muted mb-0 small">
                        Kelola visibilitas menu navigasi pada dropdown header Layanan dan tentukan periode Tahun Akademik aktif.
                    </p>
                </div>
                <div>
                    <a href="<?= SITE_URL ?>/evaluasi-kinerja.php" target="_blank" class="btn btn-sm btn-outline-primary" style="border-radius:8px;">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka Halaman Publik
                    </a>
                </div>
            </div>

            <form method="POST" action="">
                <input type="hidden" name="action" value="save_settings">

                <!-- 1. Visibilitas Menu Dropdown Layanan (Alur Permintaan User) -->
                <div class="p-4 mb-4 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                    <label class="form-label fw-bold mb-2 text-dark" style="font-size:1rem;">
                        <i class="bi bi-eye-fill text-primary me-1"></i> Aktifkan Halaman Evaluasi Kinerja?
                    </label>
                    <p class="text-muted small mb-3">
                        Tentukan apakah menu <strong>Evaluasi Kinerja</strong> akan ditampilkan di deretan dropdown menu Layanan di navigasi website publik:
                    </p>
                    
                    <div class="d-flex flex-wrap gap-4">
                        <div class="form-check p-3 rounded-3 border bg-white" style="min-width:240px;cursor:pointer;">
                            <input class="form-check-input ms-0 me-2" type="radio" name="evaluasi_kinerja_aktif" id="evalAktifYa" value="1" <?= $cur_aktif === '1' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold text-success cursor-pointer" for="evalAktifYa">
                                <i class="bi bi-check-circle-fill me-1"></i> Ya (Aktif)
                            </label>
                            <div class="small text-muted mt-1" style="margin-left:1.5rem;">
                                Menu tombol akan <strong>muncul di jejeran dropdown Layanan</strong> dan dapat diakses peserta.
                            </div>
                        </div>

                        <div class="form-check p-3 rounded-3 border bg-white" style="min-width:240px;cursor:pointer;">
                            <input class="form-check-input ms-0 me-2" type="radio" name="evaluasi_kinerja_aktif" id="evalAktifTidak" value="0" <?= $cur_aktif === '0' ? 'checked' : '' ?>>
                            <label class="form-check-label fw-bold text-danger cursor-pointer" for="evalAktifTidak">
                                <i class="bi bi-x-circle-fill me-1"></i> Tidak (Non-Aktif / Disembunyikan)
                            </label>
                            <div class="small text-muted mt-1" style="margin-left:1.5rem;">
                                Menu <strong>tidak ada di jejeran dropdown</strong> dan akses publik menampilkan info evaluasi ditutup.
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Tahun Akademik Aktif -->
                <div class="mb-4">
                    <label class="form-label fw-bold" style="color:var(--navy);">
                        Tahun Akademik Periode Evaluasi <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="evaluasi_tahun_akademik_aktif" class="form-control" style="max-width:350px;border-radius:8px;" value="<?= e($cur_ta) ?>" required placeholder="Contoh: 2025/2026">
                    <div class="form-text">
                        Tahun akademik ini menjadi filter dan penanda seluruh formulir jawaban evaluasi diri, peer review rekan kerja, dan penilaian Kepala LPM.
                    </div>
                </div>

                <!-- 3. Judul Sistem -->
                <div class="mb-4">
                    <label class="form-label fw-bold" style="color:var(--navy);">
                        Judul Header Portal Evaluasi
                    </label>
                    <input type="text" name="evaluasi_judul" class="form-control" style="border-radius:8px;" value="<?= e($cur_judul) ?>" required>
                </div>

                <!-- 4. Petunjuk Umum -->
                <div class="mb-4">
                    <label class="form-label fw-bold" style="color:var(--navy);">
                        Petunjuk &amp; Deskripsi Umum
                    </label>
                    <textarea name="evaluasi_petunjuk_umum" class="form-control" rows="4" style="border-radius:8px;"><?= e($cur_petunjuk) ?></textarea>
                    <div class="form-text">
                        Deskripsi pengantar yang ditampilkan kepada peserta sebelum memulai pengisian evaluasi.
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 pt-3 border-top">
                    <button type="submit" class="btn btn-hero-primary px-4 py-2" style="border-radius:8px;">
                        <i class="bi bi-save-fill me-1"></i> Simpan Pengaturan
                    </button>
                </div>

            </form>
        </div>

    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin-footer.php';
?>

