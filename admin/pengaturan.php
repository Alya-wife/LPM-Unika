<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Identitas & Kontak Website';
$db = getDB();

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'site_title',
        'site_subtitle',
        'alamat',
        'telepon',
        'email',
        'jam_kerja',
        'website_url',
        'maps_embed_url',
        'footer_desc'
    ];

    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        setPengaturan($f, $val);
    }

    $_SESSION['flash'] = 'Pengaturan identitas dan kontak website berhasil disimpan.';
    redirect(SITE_URL . '/admin/pengaturan.php');
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$site_title     = getPengaturan('site_title', 'LPM UNIKA');
$site_subtitle  = getPengaturan('site_subtitle', 'Soegijapranata Catholic University');
$alamat         = getPengaturan('alamat', "Ruang Lembaga Penjaminan Mutu\nGedung Thomas Aquinas Lantai 5\nKampus Universitas Katolik Soegijapranata\nJalan Pawiyatan Luhur IV/1 Bendan Duwur Semarang 50234");
$telepon        = getPengaturan('telepon', '024-8441555 Ext 1473');
$email          = getPengaturan('email', 'lpm@unika.ac.id');
$jam_kerja      = getPengaturan('jam_kerja', 'Senin – Jumat: 08.00 – 16.00 WIB');
$website_url    = getPengaturan('website_url', 'https://www.unika.ac.id');
$maps_embed_url = getPengaturan('maps_embed_url', '');
$footer_desc    = getPengaturan('footer_desc', 'Lembaga Penjaminan Mutu Soegijapranata Catholic University berkomitmen untuk mewujudkan mutu pendidikan tinggi yang unggul, berkelanjutan, dan berdaya saing global.');

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <span style="color:var(--navy);">Pengaturan Identitas &amp; Kontak</span>
        </div>

        <?php if ($flash): ?>
        <div class="alert-lpm alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <?= e($flash) ?>
        </div>
        <?php endif; ?>

        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div class="admin-table-title">Pengaturan Identitas Kampus, Alamat &amp; Kontak Resmi</div>
            </div>
            <div style="padding:1.75rem;">
                <form method="POST">
                    <!-- Brand & Nama -->
                    <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:2px solid var(--border);">
                        1. Identitas Website &amp; Lembaga
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Nama Singkat Unit (Navbar Brand)
                            </label>
                            <input type="text" name="site_title" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($site_title) ?>" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Nama Panjang Universitas / Subjudul
                            </label>
                            <input type="text" name="site_subtitle" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($site_subtitle) ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Deskripsi Footer Singkat
                            </label>
                            <textarea name="footer_desc" class="form-control" rows="2" style="border:1.5px solid var(--border);padding:0.75rem 1rem;"><?= e($footer_desc) ?></textarea>
                        </div>
                    </div>

                    <!-- Kontak & Lokasi -->
                    <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:2px solid var(--border);">
                        2. Kontak &amp; Jam Pelayanan
                    </h5>
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Alamat Lengkap Kantor
                            </label>
                            <textarea name="alamat" class="form-control" rows="4" style="border:1.5px solid var(--border);padding:0.7rem 1rem;line-height:1.6;" required><?= e($alamat) ?></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Nomor Telepon Kantor
                            </label>
                            <input type="text" name="telepon" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($telepon) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Email Resmi
                            </label>
                            <input type="email" name="email" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($email) ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Jam Operasional / Kerja
                            </label>
                            <input type="text" name="jam_kerja" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($jam_kerja) ?>">
                        </div>
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                Website Resmi Universitas
                            </label>
                            <input type="url" name="website_url" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($website_url) ?>">
                        </div>
                    </div>

                    <!-- Peta -->
                    <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:1rem;padding-bottom:0.5rem;border-bottom:2px solid var(--border);">
                        3. Google Maps Embed
                    </h5>
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label" style="font-family:var(--font-heading);font-weight:600;color:var(--navy);">
                                URL Embed Google Maps (src di dalam iframe Google Maps)
                            </label>
                            <textarea name="maps_embed_url" class="form-control" rows="3" style="border:1.5px solid var(--border);padding:0.75rem 1rem;" placeholder="https://www.google.com/maps/embed?..."><?= e($maps_embed_url) ?></textarea>
                            <small class="text-muted">Masukkan link URL embed Google Maps untuk ditampilkan di halaman kontak.</small>
                        </div>

                        <div class="col-12" style="padding-top:1rem;border-top:1px solid var(--border);margin-top:1rem;">
                            <div style="display:flex;justify-content:flex-end;">
                                <button type="submit" class="btn-submit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    Simpan Pengaturan
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
