<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Profil LPM';
$meta_desc  = 'Profil Lembaga Penjaminan Mutu SCU – Visi, Misi, dan Struktur Organisasi LPM Soegijapranata Catholic University.';

$db = getDB();

$tentang_judul = getPengaturan('profil_tentang_judul', 'Lembaga Penjaminan Mutu SCU');
$tentang_teks1 = getPengaturan('profil_tentang_teks1', 'Lembaga Penjaminan Mutu (LPM) Soegijapranata Catholic University (SCU) adalah unit kerja yang bertanggung jawab dalam merancang, melaksanakan, memantau, dan mengevaluasi Sistem Penjaminan Mutu Internal (SPMI) di lingkungan universitas.');
$tentang_teks2 = getPengaturan('profil_tentang_teks2', 'LPM berperan sebagai katalisator budaya mutu yang mendorong seluruh unit kerja untuk senantiasa meningkatkan kualitas layanan pendidikan, penelitian, dan pengabdian kepada masyarakat sesuai dengan standar nasional pendidikan tinggi (SN-Dikti) dan regulasi BAN-PT.');

$stat_akr      = getPengaturan('stat_akreditasi', 'A');
$stat_prodi    = getPengaturan('stat_prodi', '27');
$stat_tahun    = getPengaturan('stat_tahun', '40');

$visi          = getPengaturan('profil_visi', 'Terwujudnya budaya mutu melalui sistem penjaminan mutu yang mendukung pencapaian visi Universitas Katolik Soegijapranata.');
$misi_raw      = getPengaturan('profil_misi', '');
$misi_list     = array_filter(array_map('trim', explode("\n", $misi_raw)));
$tujuan_raw    = getPengaturan('profil_tujuan', '');
$tujuan_list   = array_filter(array_map('trim', explode("\n", $tujuan_raw)));

$tugas_raw     = getPengaturan('profil_tugas', 'Sesuai Peraturan Universitas Katolik Soegijapranata No. 01/E.2/Per-UKS/XI/2019 tentang Organisasi dan Tata Kelola Universitas Katolik Soegijapranata, LPM bertugas merencanakan, melaksanakan, mengevaluasi dan mengembangkan sistem penjaminan mutu.');
$fungsi_raw    = getPengaturan('profil_fungsi', '');
$fungsi_list   = array_filter(array_map('trim', explode("\n", $fungsi_raw)));

$tim_list      = $db->query("SELECT * FROM tim_lpm ORDER BY urutan ASC, id ASC")->fetchAll();
$akr_list      = $db->query("SELECT * FROM akreditasi ORDER BY urutan ASC, id ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            <?= e(getPengaturan('site_title', 'LPM UNIKA')) ?>
        </div>
        <h1 class="page-banner-title">Profil LPM</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Profil</span>
        </div>
    </div>
</div>

<!-- Tentang LPM -->
<section class="py-5 py-md-6">
    <div class="container">
        <div class="row g-5 align-items-start">
            <div class="col-lg-6">
                <span class="section-tag">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    Tentang Kami
                </span>
                <h2 class="section-title"><?= e($tentang_judul) ?></h2>
                <?php if ($tentang_teks1): ?>
                <p class="section-desc mb-3">
                    <?= nl2br(e($tentang_teks1)) ?>
                </p>
                <?php endif; ?>
                <?php if ($tentang_teks2): ?>
                <p style="font-size:0.975rem;color:var(--text-muted);line-height:1.85;margin-bottom:1.5rem;">
                    <?= nl2br(e($tentang_teks2)) ?>
                </p>
                <?php endif; ?>
                <div class="d-flex gap-3 flex-wrap">
                    <div class="d-flex align-items-center gap-2" style="font-size:0.875rem;font-weight:600;color:var(--navy);">
                        <div style="width:32px;height:32px;background:rgba(10,25,47,0.08);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="var(--navy)" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        Akreditasi Institusi <?= e($stat_akr) ?>
                    </div>
                    <div class="d-flex align-items-center gap-2" style="font-size:0.875rem;font-weight:600;color:var(--navy);">
                        <div style="width:32px;height:32px;background:rgba(10,25,47,0.08);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="var(--navy)" width="16" height="16">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        100% Prodi Terakreditasi
                    </div>
                </div>
            </div>
            <div class="col-lg-6">
                <!-- Visual Info Grid -->
                <div class="row g-3">
                    <div class="col-6">
                        <div class="card-lpm p-4 text-center">
                            <div style="font-family:var(--font-heading);font-size:2.5rem;font-weight:800;color:var(--navy);"><?= e($stat_akr) ?></div>
                            <div style="font-size:0.82rem;color:var(--text-muted);margin-top:0.25rem;">Akreditasi BAN-PT</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card-lpm p-4 text-center">
                            <div style="font-family:var(--font-heading);font-size:2.5rem;font-weight:800;color:var(--navy);"><?= e($stat_prodi) ?></div>
                            <div style="font-size:0.82rem;color:var(--text-muted);margin-top:0.25rem;">Program Studi</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card-lpm p-4 text-center">
                            <div style="font-family:var(--font-heading);font-size:2.5rem;font-weight:800;color:var(--navy);"><?= e($stat_tahun) ?><?= is_numeric($stat_tahun) ? '+' : '' ?></div>
                            <div style="font-size:0.82rem;color:var(--text-muted);margin-top:0.25rem;">Tahun Pengalaman</div>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="card-lpm p-4 text-center">
                            <div style="font-family:var(--font-heading);font-size:2.5rem;font-weight:800;color:var(--navy);">5</div>
                            <div style="font-size:0.82rem;color:var(--text-muted);margin-top:0.25rem;">Siklus PPEPP</div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Visi & Misi -->
<section class="py-5 py-md-6" style="background:var(--bg-white);">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tag">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                </svg>
                Arah &amp; Komitmen
            </span>
            <h2 class="section-title">Visi, Misi &amp; Tujuan LPM UNIKA</h2>
        </div>

        <!-- 1. Visi (Card Utama) -->
        <div class="mb-4">
            <div style="background:linear-gradient(135deg, var(--navy), #132D54);border-radius:var(--radius-lg);padding:2.5rem 2rem;box-shadow:0 8px 30px rgba(10,25,47,0.12);text-align:center;">
                <div style="display:inline-flex;align-items:center;justify-content:center;width:52px;height:52px;background:rgba(255,255,255,0.12);border-radius:50%;margin-bottom:1rem;color:#FFD54F;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="currentColor" width="28" height="28">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                </div>
                <h3 style="font-family:var(--font-heading);font-size:1.35rem;font-weight:800;color:#fff;margin-bottom:0.75rem;letter-spacing:0.5px;">
                    VISI LPM
                </h3>
                <p style="color:#FFFFFF;font-size:1.15rem;line-height:1.85;max-width:820px;margin:0 auto;font-style:italic;font-weight:500;">
                    "<?= e($visi) ?>"
                </p>
            </div>
        </div>

        <!-- 2. Misi & Tujuan (2 Kolom) -->
        <div class="row g-4">
            <!-- Misi -->
            <div class="col-lg-6">
                <div class="card-lpm p-4 h-100" style="background:#ffffff;border:1px solid var(--border);border-top:4px solid var(--purple);border-radius:var(--radius-md);">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div style="width:46px;height:46px;background:rgba(106,27,154,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--purple);">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                            </svg>
                        </div>
                        <div>
                            <h4 style="font-family:var(--font-heading);font-size:1.25rem;font-weight:800;color:var(--navy);margin:0;">
                                MISI LPM
                            </h4>
                            <span style="font-size:0.75rem;color:var(--text-muted);">Langkah aksi strategis penjaminan mutu</span>
                        </div>
                    </div>
                    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:1rem;">
                        <?php foreach ($misi_list as $i => $m): ?>
                        <li style="display:flex;align-items:flex-start;gap:0.85rem;">
                            <span style="width:28px;height:28px;min-width:28px;background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-heading);font-size:0.78rem;font-weight:800;color:#fff;margin-top:2px;">
                                <?= $i+1 ?>
                            </span>
                            <span style="font-size:0.9rem;color:var(--text-main);line-height:1.65;">
                                <?= e($m) ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>

            <!-- Tujuan -->
            <div class="col-lg-6">
                <div class="card-lpm p-4 h-100" style="background:#ffffff;border:1px solid var(--border);border-top:4px solid #1565C0;border-radius:var(--radius-md);">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div style="width:46px;height:46px;background:rgba(21,101,192,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;color:#1565C0;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                            </svg>
                        </div>
                        <div>
                            <h4 style="font-family:var(--font-heading);font-size:1.25rem;font-weight:800;color:var(--navy);margin:0;">
                                TUJUAN LPM
                            </h4>
                            <span style="font-size:0.75rem;color:var(--text-muted);">Target capaian penjaminan mutu berkelanjutan</span>
                        </div>
                    </div>
                    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:1rem;">
                        <?php foreach ($tujuan_list as $i => $t): ?>
                        <li style="display:flex;align-items:flex-start;gap:0.85rem;">
                            <span style="width:28px;height:28px;min-width:28px;background:linear-gradient(135deg, #1E88E5, #1565C0);border-radius:50%;display:flex;align-items:center;justify-content:center;font-family:var(--font-heading);font-size:0.78rem;font-weight:800;color:#fff;margin-top:2px;">
                                <?= $i+1 ?>
                            </span>
                            <span style="font-size:0.9rem;color:var(--text-main);line-height:1.65;">
                                <?= e($t) ?>
                            </span>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Tugas dan Fungsi -->
<section class="py-5 py-md-6" style="background:#F8FAFC;border-top:1px solid var(--border);border-bottom:1px solid var(--border);" id="tugas-fungsi">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tag">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.35 3.836c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 0 0 .75-.75 2.25 2.25 0 0 0-.1-.664m-5.8 0A2.251 2.251 0 0 1 13.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m8.9-4.414c.376.023.75.05 1.124.08 1.131.094 1.976 1.057 1.976 2.192V16.5A2.25 2.25 0 0 1 18 18.75h-2.25m-7.5-10.5H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V18.75m-7.5-10.5h6.375c.621 0 1.125.504 1.125 1.125v1.875m-7.5-3h6.375" />
                </svg>
                Landasan Operasional
            </span>
            <h2 class="section-title">Tugas dan Fungsi</h2>
            <p class="section-desc mx-auto">Mandat pelaksanaan dan penyelenggaraan sistem penjaminan mutu Universitas Katolik Soegijapranata.</p>
        </div>

        <!-- Box Tugas Utama -->
        <div class="mb-5">
            <div style="background:#ffffff;border:1px solid var(--border);border-left:5px solid var(--purple);border-radius:var(--radius-lg);padding:2rem 2.25rem;box-shadow:0 4px 20px rgba(10,25,47,0.04);">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div style="width:44px;height:44px;background:rgba(106,27,154,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;color:var(--purple);flex-shrink:0;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="24" height="24">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                    </div>
                    <div>
                        <h3 style="font-family:var(--font-heading);font-size:1.25rem;font-weight:800;color:var(--navy);margin:0;">
                            TUGAS UTAMA LPM
                        </h3>
                        <span style="font-size:0.75rem;color:var(--text-muted);">Peraturan Universitas Katolik Soegijapranata No. 01/E.2/Per-UKS/XI/2019</span>
                    </div>
                </div>
                <p style="color:var(--text-main);font-size:1.025rem;line-height:1.85;margin:0;font-weight:500;">
                    <?= nl2br(e($tugas_raw)) ?>
                </p>
            </div>
        </div>

        <!-- 8 Butir Fungsi LPM -->
        <div>
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4">
                <div class="d-flex align-items-center gap-2">
                    <span style="width:6px;height:24px;background:var(--purple);border-radius:4px;display:inline-block;"></span>
                    <h4 style="font-family:var(--font-heading);font-size:1.2rem;font-weight:800;color:var(--navy);margin:0;">
                        Dalam melaksanakan tugas tersebut, LPM menyelenggarakan fungsi:
                    </h4>
                </div>
                <span class="badge" style="background:rgba(10,25,47,0.06);color:var(--navy);font-size:0.8rem;padding:0.4rem 0.8rem;border-radius:6px;font-weight:600;">
                    8 Butir Penyelenggaraan Fungsi
                </span>
            </div>

            <div class="row g-3">
                <?php foreach ($fungsi_list as $idx => $f): ?>
                <div class="col-lg-6">
                    <div class="card-lpm p-3 p-md-4 h-100 d-flex align-items-start gap-3" style="background:#ffffff;border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:0 2px 10px rgba(10,25,47,0.03);transition:transform 0.18s, box-shadow 0.18s;">
                        <span style="width:32px;height:32px;min-width:32px;background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border-radius:8px;display:flex;align-items:center;justify-content:center;font-family:var(--font-heading);font-size:0.85rem;font-weight:800;color:#fff;margin-top:2px;">
                            <?= $idx + 1 ?>
                        </span>
                        <div style="font-size:0.915rem;color:var(--text-main);line-height:1.65;font-weight:500;">
                            <?= e($f) ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</section>

<!-- Struktur Organisasi & Personel -->
<section class="py-5 py-md-6" id="struktur" style="background:var(--bg-main);">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tag">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
                Struktur &amp; Tim
            </span>
            <h2 class="section-title">Struktur Organisasi LPM</h2>
            <p class="section-desc mx-auto">Bagan struktural dan susunan personel Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata.</p>
        </div>

        <!-- 1. Bagan Struktur Organisasi Visual (HTML CSS) -->
        <div class="mb-5">
            <style>
            /* ============ ORG CHART STYLES ============ */
            .org-chart-wrap {
                overflow-x: auto;
                padding: 2rem 1rem 1.5rem;
                background: #ffffff;
                border: 1px solid var(--border);
                border-radius: var(--radius-lg);
                box-shadow: 0 6px 25px rgba(10,25,47,0.06);
            }
            .org-chart {
                display: flex;
                flex-direction: column;
                align-items: center;
                gap: 0;
                min-width: 780px;
            }
            /* Node */
            .org-node {
                position: relative;
                display: inline-flex;
                flex-direction: column;
                align-items: center;
                text-align: center;
            }
            .org-box {
                padding: 0.6rem 1.1rem;
                border-radius: 10px;
                font-family: var(--font-heading);
                font-weight: 700;
                font-size: 0.78rem;
                line-height: 1.4;
                white-space: normal;
                min-width: 130px;
                max-width: 170px;
                border: 2px solid transparent;
                cursor: default;
                transition: transform 0.18s, box-shadow 0.18s;
            }
            .org-box:hover { transform: translateY(-2px); box-shadow: 0 6px 20px rgba(10,25,47,0.14); }
            .org-box-top     { background: linear-gradient(135deg,#0A192F,#132D54); color:#fff; border-color:#0A192F; }
            .org-box-wr      { background: linear-gradient(135deg,#1565C0,#1E3A8A); color:#fff; border-color:#1565C0; }
            .org-box-head    { background: linear-gradient(135deg,#7B1FA2,#6A1B9A); color:#fff; border-color:#7B1FA2; min-width:160px; max-width:200px; padding:0.8rem 1.2rem; font-size:0.82rem; }
            .org-box-sec     { background: linear-gradient(135deg,#0288D1,#0277BD); color:#fff; border-color:#0288D1; }
            .org-box-ahli    { background: #F3E5F5; color:#6A1B9A; border-color:#CE93D8; }
            .org-box-staff   { background: #E8EAF6; color:#283593; border-color:#9FA8DA; font-size:0.73rem; }
            .org-box-pusat   { background: linear-gradient(135deg,#00897B,#00695C); color:#fff; border-color:#00897B; }
            .org-box-sub     { background: #E0F2F1; color:#004D40; border-color:#80CBC4; font-size:0.73rem; }
            /* Connector lines */
            .org-vline {
                width: 2px;
                background: #CBD5E1;
                align-self: center;
            }
            .org-hline {
                height: 2px;
                background: #CBD5E1;
                align-self: center;
            }
            /* Row of children */
            .org-children-wrap {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            .org-row {
                display: flex;
                align-items: flex-start;
                justify-content: center;
                position: relative;
            }
            .org-col {
                display: flex;
                flex-direction: column;
                align-items: center;
            }
            /* Horizontal bracket connecting siblings */
            .org-bracket {
                position: relative;
                display: flex;
                align-items: flex-start;
                justify-content: center;
            }
            .org-bracket::before {
                content: '';
                position: absolute;
                top: 0;
                left: calc(50% - 50% + 85px);
                right: calc(50% - 50% + 85px);
                height: 2px;
                background: #CBD5E1;
            }
            /* Label on org-box */
            .org-box-label {
                font-size: 0.65rem;
                font-weight: 600;
                opacity: 0.75;
                margin-top: 2px;
                letter-spacing: 0.3px;
            }
            </style>

            <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:0.75rem;padding:0 0.5rem 1.25rem;">
                <div>
                    <span style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:1.05rem;">Bagan Struktur Organisasi</span>
                    <span style="font-size:0.8rem;color:var(--text-muted);margin-left:0.5rem;">Lembaga Penjaminan Mutu – Universitas Katolik Soegijapranata</span>
                </div>
                <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.72rem;color:var(--text-muted);"><span style="width:10px;height:10px;border-radius:3px;background:linear-gradient(135deg,#7B1FA2,#6A1B9A);display:inline-block;"></span> Pimpinan</span>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.72rem;color:var(--text-muted);"><span style="width:10px;height:10px;border-radius:3px;background:linear-gradient(135deg,#0288D1,#0277BD);display:inline-block;"></span> Sekretariat</span>
                    <span style="display:inline-flex;align-items:center;gap:5px;font-size:0.72rem;color:var(--text-muted);"><span style="width:10px;height:10px;border-radius:3px;background:linear-gradient(135deg,#00897B,#00695C);display:inline-block;"></span> Pusat / Divisi</span>
                </div>
            </div>

            <div class="org-chart-wrap">
                <div class="org-chart">

                    <!-- LEVEL 1: Rektorat -->
                    <div class="org-node">
                        <div class="org-box org-box-top">
                            Rektorat
                            <div class="org-box-label">Pimpinan Universitas</div>
                        </div>
                    </div>
                    <div class="org-vline" style="height:28px;"></div>

                    <!-- LEVEL 2: WR SDM TK -->
                    <div class="org-node">
                        <div class="org-box org-box-wr">
                            WR Bidang SDM &amp; TK
                            <div class="org-box-label">Wakil Rektor</div>
                        </div>
                    </div>
                    <div class="org-vline" style="height:28px;"></div>

                    <!-- LEVEL 3: Kepala LPM -->
                    <div class="org-node">
                        <div class="org-box org-box-head" title="Stefani Lily Indarto, SE., MM., Ak., CA., CPA.">
                            Kepala LPM
                            <div class="org-box-label">Stefani Lily Indarto, SE., MM., Ak., CA., CPA.</div>
                        </div>
                    </div>
                    <div class="org-vline" style="height:28px;"></div>

                    <!-- Horizontal line spanning all 5 branches -->
                    <div style="position:relative;width:100%;display:flex;justify-content:center;">
                        <div style="display:flex;align-items:flex-start;width:820px;">
                            <!-- Branch 1: Tenaga Ahli -->
                            <div class="org-col" style="flex:1;">
                                <div class="org-vline" style="height:28px;"></div>
                                <div class="org-box org-box-ahli" title="Tenaga ahli profesional yang mendukung LPM">
                                    Tenaga Ahli
                                    <div class="org-box-label">Profesional Pendukung</div>
                                </div>
                            </div>

                            <!-- Branch 2: Sekretaris + Staf TU -->
                            <div class="org-col" style="flex:1.2;">
                                <div class="org-vline" style="height:28px;"></div>
                                <div class="org-box org-box-sec" title="Vera Retnowati, ST., MM.">
                                    Sekretaris LPM
                                    <div class="org-box-label">Vera Retnowati, ST., MM.</div>
                                </div>
                                <div class="org-vline" style="height:20px;"></div>
                                <div class="org-box org-box-staff" title="Hermawan, S.M.">
                                    Staf Tata Usaha
                                    <div class="org-box-label">Hermawan, S.M.</div>
                                </div>
                            </div>

                            <!-- Branch 3: Ka. PPSPM -->
                            <div class="org-col" style="flex:1.3;">
                                <div class="org-vline" style="height:28px;"></div>
                                <div class="org-box org-box-pusat" title="Ir. I.M. Tri Hesti Mulyani, MT.">
                                    Ka. Pusat PPSPM
                                    <div class="org-box-label">Ir. I.M. Tri Hesti Mulyani, MT.</div>
                                </div>
                                <div class="org-vline" style="height:20px;"></div>
                                <div class="org-box org-box-sub">
                                    Gugus Penjaminan Mutu
                                    <div class="org-box-label">(GPM)</div>
                                </div>
                            </div>

                            <!-- Branch 4: Ka. AMI -->
                            <div class="org-col" style="flex:1.2;">
                                <div class="org-vline" style="height:28px;"></div>
                                <div class="org-box org-box-pusat" title="dr. Maya Yanuarty, M.Biomed">
                                    Ka. Pusat AMI
                                    <div class="org-box-label">dr. Maya Yanuarty, M.Biomed</div>
                                </div>
                                <div class="org-vline" style="height:20px;"></div>
                                <div class="org-box org-box-sub">
                                    Auditor Mutu Internal
                                    <div class="org-box-label">Tim Auditor</div>
                                </div>
                            </div>

                            <!-- Branch 5: Ka. Pemeringkatan -->
                            <div class="org-col" style="flex:1.3;">
                                <div class="org-vline" style="height:28px;"></div>
                                <div class="org-box org-box-pusat" title="Ir. Lintang Jata Angghita, ST., M.Ling">
                                    Ka. Pusat Pemeringkatan
                                    <div class="org-box-label">Ir. Lintang Jata Angghita, ST., M.Ling</div>
                                </div>
                            </div>
                        </div>

                        <!-- Horizontal connector line -->
                        <div style="position:absolute;top:0;left:50%;transform:translateX(-50%);width:calc(820px * 0.80);height:2px;background:#CBD5E1;"></div>
                    </div>

                </div>
            </div>

            <!-- Legend / Caption -->
            <div style="display:flex;justify-content:center;gap:2rem;flex-wrap:wrap;margin-top:1rem;padding:0.75rem;background:rgba(10,25,47,0.03);border-radius:8px;font-size:0.78rem;color:var(--text-muted);">
                <span>💡 Arahkan kursor ke kotak untuk melihat nama lengkap personel</span>
                <span>📋 PPSPM: Pusat Pengembangan Sistem Penjaminan Mutu</span>
                <span>🔍 AMI: Audit Mutu Internal</span>
            </div>
        </div>


        <!-- 2. Susunan Personel & Uraian Tugas -->
        <div class="text-center mb-4">
            <h3 style="font-family:var(--font-heading);font-size:1.35rem;font-weight:800;color:var(--navy);margin-bottom:0.4rem;">
                Susunan Personel &amp; Tanggung Jawab
            </h3>
            <p style="font-size:0.88rem;color:var(--text-muted);margin:0;">
                Tugas dan wewenang tim Lembaga Penjaminan Mutu dalam mengawal mutu akademik dan reputasi universitas.
            </p>
        </div>

        <?php if (!empty($tim_list)): ?>
        <div class="row g-4">
            <?php foreach ($tim_list as $t): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-lpm p-4 h-100 d-flex flex-column" style="background:#ffffff;border:1px solid var(--border);border-top:3.5px solid <?= $t['level'] === 'pimpinan' ? '#0A192F' : ($t['level'] === 'sekretaris' ? '#1565C0' : 'var(--purple)') ?>;border-radius:var(--radius-md);">
                    <div class="d-flex align-items-center gap-3 mb-3">
                        <div style="width:58px;height:58px;min-width:58px;border-radius:50%;overflow:hidden;background:rgba(10,25,47,0.08);display:flex;align-items:center;justify-content:center;border:2px solid var(--border);">
                            <?php if ($t['foto'] && file_exists(__DIR__ . '/uploads/tim/' . $t['foto'])): ?>
                                <img src="<?= SITE_URL ?>/uploads/tim/<?= e($t['foto']) ?>" alt="<?= e($t['nama']) ?>" style="width:100%;height:100%;object-fit:cover;">
                            <?php else: ?>
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--navy)" width="30" height="30">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h5 style="font-family:var(--font-heading);font-size:0.98rem;font-weight:800;color:var(--navy);line-height:1.3;margin:0 0 0.25rem;">
                                <?= e($t['nama']) ?>
                            </h5>
                            <span class="badge" style="background:<?= $t['level'] === 'pimpinan' ? '#0A192F' : ($t['level'] === 'sekretaris' ? '#1565C0' : 'rgba(106,27,154,0.12)') ?>;color:<?= $t['level'] === 'pimpinan' || $t['level'] === 'sekretaris' ? '#fff' : 'var(--purple)' ?>;font-weight:700;font-size:0.75rem;padding:0.3rem 0.6rem;border-radius:4px;white-space:normal;text-align:left;display:inline-block;line-height:1.25;">
                                <?= e($t['jabatan']) ?>
                            </span>
                        </div>
                    </div>

                    <?php if (!empty($t['deskripsi'])): ?>
                    <div style="font-size:0.835rem;color:var(--text-muted);line-height:1.65;margin-top:0.25rem;flex-grow:1;">
                        <?= e($t['deskripsi']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<!-- Akreditasi -->
<section class="py-5 py-md-6" style="background:var(--bg-white);" id="akreditasi">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tag">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                </svg>
                Pengakuan
            </span>
            <h2 class="section-title">Capaian Akreditasi</h2>
        </div>
        <?php if (!empty($akr_list)): ?>
        <div class="row g-4 justify-content-center">
            <?php foreach ($akr_list as $a): ?>
            <div class="col-md-4">
                <div class="card-lpm p-4 text-center h-100">
                    <div style="font-family:var(--font-heading);font-size:3rem;font-weight:800;color:<?= e($a['warna'] ?: 'var(--navy)') ?>;line-height:1;">
                        <?= e($a['peringkat']) ?>
                    </div>
                    <div style="font-family:var(--font-heading);font-size:1rem;font-weight:700;color:var(--navy);margin:0.75rem 0 0.35rem;">
                        <?= e($a['lembaga']) ?>
                    </div>
                    <?php if ($a['keterangan']): ?>
                    <div style="font-size:0.825rem;color:var(--text-muted);line-height:1.65;">
                        <?= e($a['keterangan']) ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
