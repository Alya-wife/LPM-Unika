<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Edit Konten Teks Halaman Utama';
$db = getDB();

$flash = '';
$error = '';

$fields = [
    // Beranda
    'home_badge_text'         => 'Sistem Penjaminan Mutu Internal (SPMI)',
    'home_headline'           => 'Mewujudkan Budaya Mutu & Keunggulan Akademik Berkelanjutan',
    'home_subheadline'        => 'Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata berkomitmen mengawal standar mutu tridharma perguruan tinggi berstandar nasional dan internasional.',
    // SPMI
    'spmi_banner_subtitle'    => 'Kerangka terpadu penetapan, pelaksanaan, evaluasi, pengendalian, dan peningkatan standar mutu (PPEPP) di UNIKA Soegijapranata.',
    'spmi_intro_text'         => 'Sistem Penjaminan Mutu Internal (SPMI) merupakan kegiatan sistemik penjaminan mutu pendidikan tinggi oleh UNIKA Soegijapranata untuk mengawal dan meningkatkan mutu pendidikan tinggi secara berencana dan berkelanjutan.',
    // AMI
    'ami_banner_subtitle'     => 'Mekanisme pengawasan dan evaluasi independen berkala untuk memastikan ketercapaian standar mutu tridharma perguruan tinggi.',
    'ami_intro_text'          => 'Audit Mutu Internal (AMI) di lingkungan Universitas Katolik Soegijapranata diselenggarakan secara periodik setiap tahun akademik oleh auditor internal bersertifikasi.',
    // Akreditasi
    'akreditasi_banner_sub'   => 'Informasi rekam jejak status pengakuan mutu kelembagaan dan seluruh program studi di lingkungan UNIKA Soegijapranata.',
    // Layanan
    'layanan_banner_sub'      => 'Kanal terpadu permohonan kunjungan studi banding, konsultasi sistem penjaminan mutu, serta penyampaian aspirasi.',
    // Kontak
    'kontak_banner_sub'       => 'Hubungi kami untuk informasi, konsultasi SPMI, atau koordinasi penjaminan mutu Universitas Katolik Soegijapranata.'
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($fields as $key => $default_val) {
        $val = trim($_POST[$key] ?? '');
        setPengaturan($key, $val);
    }

    $_SESSION['flash'] = 'Konten teks halaman utama berhasil diperbarui secara langsung ke website!';
    redirect(SITE_URL . '/admin/pengaturan-halaman.php');
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Load current values
$current_values = [];
foreach ($fields as $key => $default_val) {
    $current_values[$key] = getPengaturan($key, $default_val);
}

$active_tab = $_GET['tab'] ?? 'beranda';

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-10">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <span style="color:var(--navy);">Edit Konten Halaman Utama</span>
        </div>

        <?php if ($flash): ?>
        <div class="alert-lpm alert-success mb-4 d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <div><?= e($flash) ?></div>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 style="font-size:1.35rem;font-weight:800;color:var(--navy);margin:0;">Editor Konten Teks Halaman Utama</h2>
                <p style="font-size:0.875rem;color:var(--text-muted);margin:0.25rem 0 0;">
                    Ubah teks banner, headline, dan pengantar halaman inti (Beranda, SPMI, AMI, Akreditasi, Layanan) layaknya WordPress Page Section Editor.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="page-list.php" class="btn btn-outline-primary btn-sm d-inline-flex align-items-center gap-1" style="border-radius:6px;font-weight:600;">
                    <i class="bi bi-file-earmark-plus"></i> Kelola Halaman Khusus &rarr;
                </a>
            </div>
        </div>

        <!-- Navigation Tabs -->
        <ul class="nav nav-pills mb-4 gap-2 p-2 rounded-3 bg-white border" id="pageTabs">
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'beranda' ? 'active' : '' ?>" href="pengaturan-halaman.php?tab=beranda" style="font-weight:600;font-size:0.88rem;border-radius:8px;">
                    🏠 Halaman Beranda
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'spmi' ? 'active' : '' ?>" href="pengaturan-halaman.php?tab=spmi" style="font-weight:600;font-size:0.88rem;border-radius:8px;">
                    📋 Halaman SPMI
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'ami' ? 'active' : '' ?>" href="pengaturan-halaman.php?tab=ami" style="font-weight:600;font-size:0.88rem;border-radius:8px;">
                    🔍 Halaman AMI
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'akreditasi' ? 'active' : '' ?>" href="pengaturan-halaman.php?tab=akreditasi" style="font-weight:600;font-size:0.88rem;border-radius:8px;">
                    🏆 Halaman Akreditasi
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link <?= $active_tab === 'layanan' ? 'active' : '' ?>" href="pengaturan-halaman.php?tab=layanan" style="font-weight:600;font-size:0.88rem;border-radius:8px;">
                    🤝 Halaman Layanan &amp; Kontak
                </a>
            </li>
        </ul>

        <div class="admin-table-wrap">
            <div class="admin-table-topbar">
                <div class="admin-table-title">
                    <?php
                    $tab_titles = [
                        'beranda'    => 'Pengaturan Teks Halaman Beranda (Home)',
                        'spmi'       => 'Pengaturan Teks Halaman SPMI',
                        'ami'        => 'Pengaturan Teks Halaman AMI',
                        'akreditasi' => 'Pengaturan Teks Halaman Akreditasi',
                        'layanan'    => 'Pengaturan Teks Halaman Layanan &amp; Kontak'
                    ];
                    echo $tab_titles[$active_tab] ?? 'Pengaturan Konten Halaman';
                    ?>
                </div>
            </div>

            <div style="padding:1.75rem;">
                <form method="POST">
                    <!-- TAB 1: BERANDA -->
                    <div style="<?= $active_tab === 'beranda' ? 'display:block;' : 'display:none;' ?>">
                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);">
                                Badge Sorotan Atas (Hero Top Pill)
                            </label>
                            <input type="text" name="home_badge_text" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" value="<?= e($current_values['home_badge_text']) ?>">
                            <small class="text-muted">Teks kecil di atas headline utama dengan warna aksen emas.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);">
                                Headline Utama Beranda (Hero Heading H1)
                            </label>
                            <input type="text" name="home_headline" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;font-weight:700;" value="<?= e($current_values['home_headline']) ?>">
                            <small class="text-muted">Judul besar yang pertama kali dilihat oleh pengunjung pada halaman depan.</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);">
                                Subheadline / Paragraf Pengantar Beranda
                            </label>
                            <textarea name="home_subheadline" class="form-control" rows="3" style="border:1.5px solid var(--border);padding:0.7rem 1rem;"><?= e($current_values['home_subheadline']) ?></textarea>
                            <small class="text-muted">Penjelasan komitmen mutu LPM di bawah headline utama.</small>
                        </div>

                        <div class="p-3 rounded-3" style="background:#F0FDF4;border:1px solid #BBF7D0;font-size:0.83rem;color:#166534;">
                            💡 <strong>Kelola Elemen Beranda Lainnya:</strong>
                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                <a href="slider-list.php" class="btn btn-sm btn-outline-success">Kelola Gambar Banner Slider &rarr;</a>
                                <a href="penghargaan-list.php" class="btn btn-sm btn-outline-success">Kelola Capaian &amp; Penghargaan &rarr;</a>
                                <a href="sambutan.php" class="btn btn-sm btn-outline-success">Kelola Sambutan Kepala LPM &rarr;</a>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 2: SPMI -->
                    <div style="<?= $active_tab === 'spmi' ? 'display:block;' : 'display:none;' ?>">
                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);">
                                Subjudul Banner Halaman SPMI
                            </label>
                            <textarea name="spmi_banner_subtitle" class="form-control" rows="2" style="border:1.5px solid var(--border);padding:0.7rem 1rem;"><?= e($current_values['spmi_banner_subtitle']) ?></textarea>
                            <small class="text-muted">Teks pengantar di banner atas halaman /spmi.php</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);">
                                Penjelasan Pengantar SPMI &amp; Siklus PPEPP
                            </label>
                            <textarea name="spmi_intro_text" class="form-control" rows="4" style="border:1.5px solid var(--border);padding:0.7rem 1rem;"><?= e($current_values['spmi_intro_text']) ?></textarea>
                            <small class="text-muted">Deskripsi makna SPMI yang diletakkan di bagian atas penjelasan siklus mutu.</small>
                        </div>

                        <div class="p-3 rounded-3" style="background:#EDE7F6;border:1px solid #D1C4E9;font-size:0.83rem;color:#4527A0;">
                            💡 <strong>Kelola Dokumen Terkait SPMI:</strong>
                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                <a href="dokumen-list.php" class="btn btn-sm btn-outline-primary">Kelola Dokumen Regulasi SPMI &rarr;</a>
                                <a href="buletin-list.php" class="btn btn-sm btn-outline-primary">Kelola Buletin Mutu JAMUS &rarr;</a>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 3: AMI -->
                    <div style="<?= $active_tab === 'ami' ? 'display:block;' : 'display:none;' ?>">
                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);">
                                Subjudul Banner Halaman AMI
                            </label>
                            <textarea name="ami_banner_subtitle" class="form-control" rows="2" style="border:1.5px solid var(--border);padding:0.7rem 1rem;"><?= e($current_values['ami_banner_subtitle']) ?></textarea>
                            <small class="text-muted">Teks pengantar di banner atas halaman /ami.php</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);">
                                Deskripsi Siklus &amp; Pelaksanaan AMI
                            </label>
                            <textarea name="ami_intro_text" class="form-control" rows="4" style="border:1.5px solid var(--border);padding:0.7rem 1rem;"><?= e($current_values['ami_intro_text']) ?></textarea>
                            <small class="text-muted">Penjelasan komitmen pelaksanaan audit mutu internal berkala oleh para auditor tersertifikasi.</small>
                        </div>

                        <div class="p-3 rounded-3" style="background:#FEF3C7;border:1px solid #FDE68A;font-size:0.83rem;color:#92400E;">
                            💡 <strong>Kelola Jadwal AMI:</strong>
                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                <a href="kalender-ami-list.php" class="btn btn-sm btn-warning">Kelola Jadwal &amp; Timeline Siklus AMI &rarr;</a>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 4: AKREDITASI -->
                    <div style="<?= $active_tab === 'akreditasi' ? 'display:block;' : 'display:none;' ?>">
                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);">
                                Subjudul Banner Halaman Akreditasi
                            </label>
                            <textarea name="akreditasi_banner_sub" class="form-control" rows="3" style="border:1.5px solid var(--border);padding:0.7rem 1rem;"><?= e($current_values['akreditasi_banner_sub']) ?></textarea>
                            <small class="text-muted">Teks pengantar di banner atas halaman /akreditasi.php</small>
                        </div>

                        <div class="p-3 rounded-3" style="background:#F0FDF4;border:1px solid #BBF7D0;font-size:0.83rem;color:#166534;">
                            💡 <strong>Kelola Data Akreditasi Lengkap:</strong>
                            <div class="mt-2 d-flex gap-2 flex-wrap">
                                <a href="akreditasi-institusi.php" class="btn btn-sm btn-outline-success">Akreditasi Institusi &rarr;</a>
                                <a href="akreditasi-prodi-list.php" class="btn btn-sm btn-outline-success">Data Akreditasi Program Studi &rarr;</a>
                                <a href="lembaga-akreditasi-list.php" class="btn btn-sm btn-outline-success">Lembaga Akreditasi (LAM) &rarr;</a>
                            </div>
                        </div>
                    </div>

                    <!-- TAB 5: LAYANAN & KONTAK -->
                    <div style="<?= $active_tab === 'layanan' ? 'display:block;' : 'display:none;' ?>">
                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);">
                                Subjudul Banner Halaman Layanan
                            </label>
                            <textarea name="layanan_banner_sub" class="form-control" rows="2" style="border:1.5px solid var(--border);padding:0.7rem 1rem;"><?= e($current_values['layanan_banner_sub']) ?></textarea>
                            <small class="text-muted">Teks pengantar di banner atas halaman /layanan.php</small>
                        </div>

                        <div class="mb-4">
                            <label class="form-label" style="font-weight:700;color:var(--navy);">
                                Subjudul Banner Halaman Kontak
                            </label>
                            <textarea name="kontak_banner_sub" class="form-control" rows="2" style="border:1.5px solid var(--border);padding:0.7rem 1rem;"><?= e($current_values['kontak_banner_sub']) ?></textarea>
                            <small class="text-muted">Teks pengantar di banner atas halaman /kontak.php</small>
                        </div>

                        <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid var(--border);font-size:0.83rem;color:var(--text-muted);">
                            💡 <strong>Alamat &amp; Kontak Resmi:</strong> Nomor telepon, email, peta, dan jam operasional dapat diatur melalui <a href="pengaturan.php" class="text-purple font-weight-600">Pengaturan Identitas &amp; Kontak</a>.
                        </div>
                    </div>

                    <!-- Hidden input to retain other tabs data if not in current view -->
                    <?php foreach ($fields as $key => $default_val): ?>
                        <?php
                        // Check if key is in another tab
                        $is_current_tab_field = false;
                        if ($active_tab === 'beranda' && in_array($key, ['home_badge_text', 'home_headline', 'home_subheadline'])) $is_current_tab_field = true;
                        if ($active_tab === 'spmi' && in_array($key, ['spmi_banner_subtitle', 'spmi_intro_text'])) $is_current_tab_field = true;
                        if ($active_tab === 'ami' && in_array($key, ['ami_banner_subtitle', 'ami_intro_text'])) $is_current_tab_field = true;
                        if ($active_tab === 'akreditasi' && in_array($key, ['akreditasi_banner_sub'])) $is_current_tab_field = true;
                        if ($active_tab === 'layanan' && in_array($key, ['layanan_banner_sub', 'kontak_banner_sub'])) $is_current_tab_field = true;
                        
                        if (!$is_current_tab_field):
                        ?>
                        <input type="hidden" name="<?= e($key) ?>" value="<?= e($current_values[$key]) ?>">
                        <?php endif; ?>
                    <?php endforeach; ?>

                    <div class="col-12 mt-4" style="padding-top:1.25rem;border-top:1px solid var(--border);">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <span class="text-muted" style="font-size:0.82rem;">
                                Seluruh perubahan langsung tersimpan ke sistem database LPM.
                            </span>
                            <button type="submit" class="btn-submit px-4" style="font-size:0.95rem;">
                                <i class="bi bi-save2 me-1"></i> Simpan Perubahan Konten
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
