<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Akreditasi Institusi & Program Studi';
$meta_desc  = 'Status Akreditasi Institusi dan Program Studi Universitas Katolik Soegijapranata (UNIKA) oleh BAN-PT dan Lembaga Akreditasi Mandiri (LAM).';

$db = getDB();

// Ambil data akreditasi dari database jika ada
$prodi_list = $db->query("SELECT * FROM akreditasi ORDER BY urutan ASC, id ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Pengakuan Mutu Nasional
        </div>
        <h1 class="page-banner-title">Akreditasi Institusi &amp; Program Studi</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Akreditasi</span>
        </div>
    </div>
</div>

<!-- ==============================================
     1. PENGANTAR & STATUS AKREDITASI INSTITUSI
============================================== -->
<section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <span class="section-tag mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .982-3.172M12 3a4.5 4.5 0 0 0-4.5 4.5v1.875a3.375 3.375 0 0 0 3.375 3.375h2.25a3.375 3.375 0 0 0 3.375-3.375V7.5A4.5 4.5 0 0 0 12 3Z" />
                    </svg>
                    Peringkat Nasional
                </span>
                <h2 class="section-title mb-3">Akreditasi Institusi UNIKA</h2>
                <p style="color:var(--text-main);line-height:1.8;font-size:1rem;margin-bottom:1.25rem;">
                    Universitas Katolik Soegijapranata senantiasa menjaga dan meningkatkan reputasi akademik melalui akreditasi nasional oleh Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT) serta Lembaga Akreditasi Mandiri (LAM) yang relevan untuk setiap rumpun keilmuan program studi.
                </p>
                <p style="color:var(--text-muted);line-height:1.75;font-size:0.95rem;margin-bottom:1.5rem;">
                    Dengan capaian peringkat akreditasi tertinggi, UNIKA menjamin proses pembelajaran, penelitian, pengabdian masyarakat, dan tata kelola berstandar mutu unggul bagi seluruh mahasiswa dan pemangku kepentingan.
                </p>

                <div class="d-flex gap-3 flex-wrap">
                    <a href="#daftar-akreditasi" class="btn-hero-primary" style="background:var(--navy);border:none;padding:0.7rem 1.4rem;font-size:0.9rem;">
                        Lihat Capaian Prodi &darr;
                    </a>
                    <a href="<?= SITE_URL ?>/layanan.php" class="btn-hero-secondary" style="background:#fff;border:1.5px solid var(--border);color:var(--navy);padding:0.7rem 1.4rem;font-size:0.9rem;">
                        Konsultasi Pendampingan Akreditasi
                    </a>
                </div>
            </div>

            <!-- Institusi Unggul Badge Card -->
            <div class="col-lg-5">
                <div class="card-lpm p-4 text-center" style="background:linear-gradient(135deg, var(--navy), #132D54);color:#fff;border-radius:var(--radius-lg);box-shadow:0 12px 35px rgba(10,25,47,0.18);">
                    <div style="width:70px;height:70px;margin:0 auto 1.25rem;border-radius:50%;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="#FFD54F" width="36" height="36">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                        </svg>
                    </div>
                    <span class="card-category-badge mb-2" style="background:#FFD54F;color:#0A192F;font-weight:800;">STATUS RESMI</span>
                    <h3 style="font-family:var(--font-heading);font-weight:800;color:#fff;margin-bottom:0.25rem;font-size:1.8rem;">
                        TERAKREDITASI
                    </h3>
                    <div style="font-size:1.25rem;color:#FFD54F;font-weight:800;letter-spacing:1px;margin-bottom:1rem;">
                        UNGGUL / A
                    </div>
                    <p style="font-size:0.85rem;color:rgba(255,255,255,0.78);line-height:1.6;margin:0;">
                        Berdasarkan Keputusan Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT) untuk Institusi Universitas Katolik Soegijapranata.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================
     2. LOGO & BADAN AKREDITASI (BAN-PT & 7 LAM)
============================================== -->
<section class="py-5" style="background:var(--bg-main);">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tag mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-2.18-7.52a48.42 48.42 0 0 0-5.64 0A1.875 1.875 0 0 0 3.75 4.5v15a1.875 1.875 0 0 0 1.875 1.875h12.75A1.875 1.875 0 0 0 20.25 19.5V4.5a1.875 1.875 0 0 0-1.875-1.875c-1.87-.14-3.75-.14-5.64 0Z" />
                </svg>
                Lembaga Akreditasi
            </span>
            <h2 class="section-title">Badan Akreditasi Nasional &amp; Lembaga Akreditasi Mandiri (LAM)</h2>
            <p class="section-desc mx-auto">Kerjasama akreditasi program studi UNIKA bersama BAN-PT dan 7 Lembaga Akreditasi Mandiri resmi nasional.</p>
        </div>

        <?php
        $lembaga_akreditasi = [
            [
                'kode' => 'BAN PT',
                'nama' => 'Badan Akreditasi Nasional Perguruan Tinggi',
                'warna' => '#0D47A1',
                'bg' => '#E3F2FD',
                'lingkup' => 'Akreditasi Perguruan Tinggi & Prodi Umum',
                'file' => 'logo-banpt.png'
            ],
            [
                'kode' => 'LAMEMBA',
                'nama' => 'Lembaga Akreditasi Mandiri Ekonomi, Manajemen, Bisnis dan Akuntansi',
                'warna' => '#1B5E20',
                'bg' => '#E8F5E9',
                'lingkup' => 'Fakultas Ekonomi dan Bisnis (FEB)',
                'file' => 'logo-lamemba.png'
            ],
            [
                'kode' => 'LAM INFOKOM',
                'nama' => 'Lembaga Akreditasi Mandiri Informatika dan Komputer',
                'warna' => '#B71C1C',
                'bg' => '#FFEBEE',
                'lingkup' => 'Ilmu Komputer & Sistem Informasi',
                'file' => 'logo-laminfokom.png'
            ],
            [
                'kode' => 'LAM TEKNIK',
                'nama' => 'Lembaga Akreditasi Mandiri Program Studi Keteknikan',
                'warna' => '#E65100',
                'bg' => '#FFF3E0',
                'lingkup' => 'Teknik Elektro, Sipil, dan Rekayasa',
                'file' => 'logo-lamteknik.png'
            ],
            [
                'kode' => 'LAMPTKes',
                'nama' => 'Lembaga Akreditasi Mandiri Pendidikan Tinggi Kesehatan',
                'warna' => '#004D40',
                'bg' => '#E0F2F1',
                'lingkup' => 'Kedokteran & Ilmu Kesehatan',
                'file' => 'logo-lamptkes.png'
            ],
            [
                'kode' => 'LAMSPAK',
                'nama' => 'Lembaga Akreditasi Mandiri Sains Alam dan Ilmu Formal',
                'warna' => '#4A148C',
                'bg' => '#F3E5F5',
                'lingkup' => 'Teknologi Pangan, Sains & Ilmu Alam',
                'file' => 'logo-lamspak.png'
            ],
            [
                'kode' => 'LAMDEPILAR',
                'nama' => 'Lembaga Akreditasi Mandiri Desain, Arsitektur, Seni Rupa, dan Perencanaan',
                'warna' => '#311B92',
                'bg' => '#EDE7F6',
                'lingkup' => 'Arsitektur, Desain Komunikasi Visual (DKV)',
                'file' => 'logo-lamdepilar.png'
            ],
            [
                'kode' => 'LAMPTIP',
                'nama' => 'Lembaga Akreditasi Mandiri Kependidikan',
                'warna' => '#006064',
                'bg' => '#E0F7FA',
                'lingkup' => 'Program Studi Pendidikan & Kependidikan',
                'file' => 'logo-lamptip.png'
            ],
        ];
        ?>

        <div class="row g-4">
            <?php foreach ($lembaga_akreditasi as $lem): ?>
            <div class="col-md-6 col-lg-3">
                <div class="card-lpm p-4 h-100 text-center" style="background:#ffffff;border:1px solid var(--border);border-radius:var(--radius-md);transition:all 0.2s ease;">
                    <!-- Logo / Emblema Container -->
                    <div style="width:72px;height:72px;margin:0 auto 1rem;border-radius:14px;background:<?= $lem['bg'] ?>;display:flex;align-items:center;justify-content:center;border:1.5px solid <?= $lem['warna'] ?>;overflow:hidden;padding:6px;">
                        <?php if (file_exists(__DIR__ . '/uploads/akreditasi/' . $lem['file'])): ?>
                            <img src="<?= SITE_URL ?>/uploads/akreditasi/<?= $lem['file'] ?>" alt="<?= e($lem['kode']) ?>" style="max-width:100%;max-height:100%;object-fit:contain;">
                        <?php else: ?>
                            <div style="font-family:var(--font-heading);font-weight:900;color:<?= $lem['warna'] ?>;font-size:<?= strlen($lem['kode']) > 7 ? '0.75rem' : '0.9rem' ?>;line-height:1.1;text-align:center;">
                                <?= e($lem['kode']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <h5 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.05rem;margin-bottom:0.35rem;">
                        <?= e($lem['kode']) ?>
                    </h5>
                    <div style="font-size:0.75rem;color:var(--text-muted);line-height:1.5;margin-bottom:0.75rem;min-height:36px;">
                        <?= e($lem['nama']) ?>
                    </div>
                    <div style="background:var(--bg-main);border-radius:4px;padding:0.3rem 0.6rem;font-size:0.72rem;font-weight:600;color:<?= $lem['warna'] ?>;">
                        <?= e($lem['lingkup']) ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</section>

<!-- ==============================================
     3. TABEL CAPAIAN AKREDITASI PROGRAM STUDI
============================================== -->
<section class="py-5" id="daftar-akreditasi" style="background:#ffffff;">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <span class="section-tag mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                    </svg>
                    Program Studi
                </span>
                <h2 class="section-title mb-0">Daftar Akreditasi Program Studi</h2>
            </div>
            <a href="<?= SITE_URL ?>/layanan.php" class="btn-action btn-edit">
                Ajukan Pendampingan Akreditasi &rarr;
            </a>
        </div>

        <div class="doc-table-wrap">
            <div class="doc-table-header d-flex justify-content-between align-items-center">
                <span class="doc-table-header-title">Data Peringkat Akreditasi Program Studi UNIKA</span>
                <span style="font-size:0.8rem;color:rgba(255,255,255,0.75);"><?= count($prodi_list) ?> Program Studi Terdaftar</span>
            </div>

            <?php if (empty($prodi_list)): ?>
            <div class="text-center py-5" style="background:#fff;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--text-muted)" width="48" height="48" style="opacity:0.3;margin-bottom:1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .982-3.172M12 3a4.5 4.5 0 0 0-4.5 4.5v1.875a3.375 3.375 0 0 0 3.375 3.375h2.25a3.375 3.375 0 0 0 3.375-3.375V7.5A4.5 4.5 0 0 0 12 3Z" />
                </svg>
                <h5 style="color:var(--navy);font-weight:700;">Data Sedang Dimutakhirkan</h5>
                <p style="color:var(--text-muted);font-size:0.875rem;max-width:450px;margin:0 auto 1.5rem;">
                    Daftar peringkat akreditasi program studi dapat diperbarui langsung melalui panel admin oleh LPM UNIKA.
                </p>
                <a href="<?= SITE_URL ?>/admin/akreditasi-list.php" class="btn-hero-primary" style="display:inline-flex;padding:0.6rem 1.4rem;font-size:0.85rem;">
                    Kelola di Admin &rarr;
                </a>
            </div>
            <?php else: ?>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th width="45">#</th>
                        <th width="140">Peringkat</th>
                        <th width="200">Badan / Lembaga Akreditasi</th>
                        <th>Keterangan / Rincian Akreditasi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($prodi_list as $idx => $p): ?>
                    <tr>
                        <td style="color:var(--text-muted);font-size:0.85rem;text-align:center;"><?= $idx + 1 ?></td>
                        <td>
                            <span class="badge" style="background:<?= in_array($p['peringkat'], ['Unggul', 'A']) ? '#E8F5E9' : '#EDE7F6' ?>;color:<?= in_array($p['peringkat'], ['Unggul', 'A']) ? '#2E7D32' : '#5E35B1' ?>;font-weight:800;font-size:0.85rem;padding:0.4rem 0.8rem;border-radius:20px;">
                                <?= e($p['peringkat']) ?>
                            </span>
                        </td>
                        <td style="font-weight:700;color:var(--navy);font-size:0.95rem;">
                            <?= e($p['lembaga']) ?>
                        </td>
                        <td style="font-size:0.88rem;color:var(--text-muted);line-height:1.5;">
                            <?= e($p['keterangan'] ?? '-') ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
