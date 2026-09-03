<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Profil & Visi Misi';
$db = getDB();

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $judul_tentang = trim($_POST['profil_tentang_judul'] ?? '');
    $teks1         = trim($_POST['profil_tentang_teks1'] ?? '');
    $teks2         = trim($_POST['profil_tentang_teks2'] ?? '');
    $visi          = trim($_POST['profil_visi'] ?? '');
    $misi          = trim($_POST['profil_misi'] ?? '');
    $tujuan        = trim($_POST['profil_tujuan'] ?? '');
    $tugas         = trim($_POST['profil_tugas'] ?? '');
    $fungsi        = trim($_POST['profil_fungsi'] ?? '');
    $stat_akr      = trim($_POST['stat_akreditasi'] ?? 'A');
    $stat_prodi    = trim($_POST['stat_prodi'] ?? '27');
    $stat_tahun    = trim($_POST['stat_tahun'] ?? '40');

    if (!$judul_tentang || !$visi || !$misi) {
        $error = 'Judul tentang, visi, dan misi wajib diisi.';
    } else {
        setPengaturan('profil_tentang_judul', $judul_tentang);
        setPengaturan('profil_tentang_teks1', $teks1);
        setPengaturan('profil_tentang_teks2', $teks2);
        setPengaturan('profil_visi', $visi);
        setPengaturan('profil_misi', $misi);
        setPengaturan('profil_tujuan', $tujuan);
        setPengaturan('profil_tugas', $tugas);
        setPengaturan('profil_fungsi', $fungsi);
        setPengaturan('stat_akreditasi', $stat_akr);
        setPengaturan('stat_prodi', $stat_prodi);
        setPengaturan('stat_tahun', $stat_tahun);

        $_SESSION['flash'] = 'Konten Profil, Visi, Misi, Tujuan, serta Tugas & Fungsi berhasil diperbarui.';
        redirect(SITE_URL . '/admin/profil-edit.php');
    }
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$judul_tentang = getPengaturan('profil_tentang_judul', 'Lembaga Penjaminan Mutu UNIKA');
$teks1         = getPengaturan('profil_tentang_teks1', '');
$teks2         = getPengaturan('profil_tentang_teks2', '');
$visi          = getPengaturan('profil_visi', '');
$misi          = getPengaturan('profil_misi', '');
$tujuan        = getPengaturan('profil_tujuan', '');
$tugas         = getPengaturan('profil_tugas', '');
$fungsi        = getPengaturan('profil_fungsi', '');
$stat_akr      = getPengaturan('stat_akreditasi', 'A');
$stat_prodi    = getPengaturan('stat_prodi', '27');
$stat_tahun    = getPengaturan('stat_tahun', '40');

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <span style="color:var(--navy);">Kelola Profil, Visi &amp; Misi</span>
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

        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div class="admin-table-title">Pengaturan Halaman Profil (Tentang Kami, Visi &amp; Misi)</div>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST">
                    <!-- Bagian 1: Tentang Kami -->
                    <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:2px solid var(--border);">
                        1. Bagian "Tentang LPM"
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Judul Bagian Tentang <span style="color:#C62828;">*</span>
                            </label>
                            <input type="text" name="profil_tentang_judul" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($judul_tentang) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Paragraf Pertama (Penjelasan Umum)
                            </label>
                            <textarea name="profil_tentang_teks1" class="form-control" rows="3" style="border:1.5px solid var(--border);padding:0.75rem 1rem;"><?= e($teks1) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Paragraf Kedua (Peran Katalisator Budaya Mutu)
                            </label>
                            <textarea name="profil_tentang_teks2" class="form-control" rows="3" style="border:1.5px solid var(--border);padding:0.75rem 1rem;"><?= e($teks2) ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Akreditasi Institusi
                            </label>
                            <input type="text" name="stat_akreditasi" class="form-control" style="border:1.5px solid var(--border);padding:0.6rem 1rem;" value="<?= e($stat_akr) ?>" placeholder="A / Unggul">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Jumlah Program Studi
                            </label>
                            <input type="text" name="stat_prodi" class="form-control" style="border:1.5px solid var(--border);padding:0.6rem 1rem;" value="<?= e($stat_prodi) ?>" placeholder="Contoh: 27">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Tahun Pengalaman
                            </label>
                            <input type="text" name="stat_tahun" class="form-control" style="border:1.5px solid var(--border);padding:0.6rem 1rem;" value="<?= e($stat_tahun) ?>" placeholder="Contoh: 40+">
                        </div>
                    </div>

                    <!-- Bagian 2: Visi, Misi & Tujuan -->
                    <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:2px solid var(--border);">
                        2. Bagian "Visi, Misi &amp; Tujuan"
                    </h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Rumusan Visi LPM <span style="color:#C62828;">*</span>
                            </label>
                            <textarea name="profil_visi" class="form-control" rows="3" style="border:1.5px solid var(--border);padding:0.75rem 1rem;" placeholder="Visi LPM UNIKA..." required><?= e($visi) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Butir-Butir Misi LPM <span style="color:#C62828;">*</span>
                            </label>
                            <textarea name="profil_misi" class="form-control" rows="6" style="border:1.5px solid var(--border);padding:0.75rem 1rem;line-height:1.7;" placeholder="Tuliskan butir misi per baris..." required><?= e($misi) ?></textarea>
                            <small class="text-muted d-block mt-1">
                                💡 <strong>Tips:</strong> Tuliskan setiap butir misi di <strong>baris baru (tekan Enter)</strong>. Sistem otomatis mengubah setiap baris menjadi nomor urut.
                            </small>
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Butir-Butir Tujuan LPM
                            </label>
                            <textarea name="profil_tujuan" class="form-control" rows="6" style="border:1.5px solid var(--border);padding:0.75rem 1rem;line-height:1.7;" placeholder="Tuliskan butir tujuan per baris..."><?= e($tujuan) ?></textarea>
                            <small class="text-muted d-block mt-1">
                                💡 <strong>Tips:</strong> Tuliskan setiap butir tujuan di <strong>baris baru (tekan Enter)</strong>. Sistem otomatis mengubah setiap baris menjadi nomor urut.
                            </small>
                        </div>

                    </div>

                    <!-- Bagian 3: Tugas & Fungsi -->
                    <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-top:2rem;margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:2px solid var(--border);">
                        3. Bagian "Tugas dan Fungsi"
                    </h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Tugas Utama LPM (Berdasarkan SK/Peraturan)
                            </label>
                            <textarea name="profil_tugas" class="form-control" rows="3" style="border:1.5px solid var(--border);padding:0.75rem 1rem;line-height:1.7;" placeholder="Sesuai Peraturan Universitas..."><?= e($tugas) ?></textarea>
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Butir-Butir Fungsi Penyelenggaraan LPM
                            </label>
                            <textarea name="profil_fungsi" class="form-control" rows="8" style="border:1.5px solid var(--border);padding:0.75rem 1rem;line-height:1.7;" placeholder="Tuliskan butir fungsi per baris..."><?= e($fungsi) ?></textarea>
                            <small class="text-muted d-block mt-1">
                                💡 <strong>Tips:</strong> Tuliskan setiap butir fungsi di <strong>baris baru (tekan Enter)</strong>. Sistem otomatis mengubah setiap baris menjadi kartu fungsi bernomor.
                            </small>
                        </div>

                        <div class="col-12" style="padding-top:1rem;border-top:1px solid var(--border);margin-top:1rem;">
                            <div style="display:flex;justify-content:flex-end;">
                                <button type="submit" class="btn-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Simpan Perubahan Profil &amp; Tupoksi
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
