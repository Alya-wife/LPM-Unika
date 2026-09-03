<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'SPMI – Sistem Penjaminan Mutu Internal';
$meta_desc  = 'Dokumen SPMI LPM UNIKA: Kebijakan Mutu, Manual Mutu, Standar Mutu, Formulir Mutu, dan Siklus PPEPP Universitas Katolik Soegijapranata.';

$db = getDB();

// Filter kategori
$kategori_filter = isset($_GET['kategori']) ? trim($_GET['kategori']) : '';

// Ambil semua kategori unik yang ada di database
$categories_in_db = $db->query("SELECT DISTINCT kategori FROM dokumen")->fetchAll(PDO::FETCH_COLUMN);
$allowed_kategori = array_unique(array_merge(['Kebijakan', 'Manual', 'Standar', 'Formulir'], $categories_in_db));
if (!in_array($kategori_filter, $allowed_kategori)) $kategori_filter = '';

// Query dokumen
if ($kategori_filter) {
    $stmt = $db->prepare("SELECT * FROM dokumen WHERE kategori = ? ORDER BY created_at DESC");
    $stmt->execute([$kategori_filter]);
} else {
    $stmt = $db->query("SELECT * FROM dokumen ORDER BY kategori, created_at DESC");
}
$dokumen_all = $stmt->fetchAll();

// Kelompokkan per kategori
$dokumen_grouped = [];
foreach ($dokumen_all as $d) {
    $dokumen_grouped[$d['kategori']][] = $d;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Sistem Penjaminan Mutu Internal
        </div>
        <h1 class="page-banner-title">SPMI – Sistem Penjaminan Mutu Internal</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">SPMI</span>
        </div>
    </div>
</div>

<!-- ==============================================
     1. PENGANTAR SPMI (Halaman Atas)
============================================== -->
<section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <span class="section-tag mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                    Pengantar Mutu
                </span>
                <h2 class="section-title mb-3">Mengenal SPMI di UNIKA</h2>
                <p style="color:var(--text-main);line-height:1.8;font-size:1rem;margin-bottom:1.25rem;">
                    Sistem Penjaminan Mutu Internal (SPMI) Universitas Katolik Soegijapranata merupakan kegiatan sistemik penjaminan mutu pendidikan tinggi yang dilaksanakan secara mandiri oleh universitas untuk mengendalikan dan meningkatkan penyelenggaraan pendidikan tinggi secara berencana dan berkelanjutan.
                </p>
                <p style="color:var(--text-muted);line-height:1.75;font-size:0.95rem;margin-bottom:1.5rem;">
                    SPMI UNIKA dirancang berlandaskan nilai-nilai Kristiani dan semangat Santo Soegijapranata <em>(Talenta Pro Patria et Humanitate)</em> untuk memastikan lulusan memiliki integritas moral, keunggulan akademik, serta kepekaan sosial yang tinggi.
                </p>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3" style="background:var(--bg-main);border-radius:var(--radius-sm);border-left:3px solid var(--purple);">
                            <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.9rem;margin-bottom:0.25rem;">Tujuan Pokok SPMI</div>
                            <div style="font-size:0.8rem;color:var(--text-muted);line-height:1.5;">Memelihara dan meningkatkan mutu pendidikan tinggi secara berkelanjutan <em>(continuous improvement)</em>.</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3" style="background:var(--bg-main);border-radius:var(--radius-sm);border-left:3px solid #1565C0;">
                            <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.9rem;margin-bottom:0.25rem;">Landasan Regulasi</div>
                            <div style="font-size:0.8rem;color:var(--text-muted);line-height:1.5;">UU No. 12/2012 tentang Pendidikan Tinggi dan Permendikbudristek No. 53/2023.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Card Akses Portal SISTA -->
            <div class="col-lg-5">
                <div class="card-lpm p-4 text-center" style="background:linear-gradient(135deg, var(--navy), #132D54);color:#fff;border-radius:var(--radius-lg);box-shadow:0 12px 35px rgba(10,25,47,0.18);">
                    <div style="width:60px;height:60px;margin:0 auto 1.25rem;border-radius:50%;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="#FFD54F" width="30" height="30">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                        </svg>
                    </div>
                    <span class="card-category-badge mb-2" style="background:rgba(255,255,255,0.15);color:#fff;">Sistem Informasi Resmi</span>
                    <h4 style="font-family:var(--font-heading);font-weight:700;color:#fff;margin-bottom:0.6rem;">
                        Portal SISTA UNIKA
                    </h4>
                    <p style="font-size:0.88rem;color:rgba(255,255,255,0.78);line-height:1.6;margin-bottom:1.5rem;">
                        Sistem Informasi Standar Akademik untuk pemantauan capaian standar mutu, pengisian instrumen, dan pelaporan unit kerja di lingkungan Universitas Katolik Soegijapranata.
                    </p>
                    <a href="https://sista.unika.ac.id" target="_blank" class="btn-hero-primary w-100 justify-content-center" style="background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border:none;padding:0.8rem 1.5rem;font-size:0.95rem;font-weight:700;box-shadow:0 4px 16px rgba(106,27,154,0.4);">
                        Buka Portal SISTA &nearr;
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================
     2. SIKLUS PPEPP (Scroll ke bawah)
============================================== -->
<section class="py-5 py-md-6" style="background:var(--bg-main);" id="siklus-ppepp">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tag mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                </svg>
                Alur Kerja Mutu
            </span>
            <h2 class="section-title">Siklus PPEPP SPMI</h2>
            <p class="section-desc max-w-800 mx-auto" style="font-size:0.95rem;line-height:1.75;">
                Siklus PPEPP adalah pilar utama dalam Sistem Penjaminan Mutu Internal (SPMI) di Unika Soegijapranata. PPEPP merupakan akronim dari lima tahapan kerja terstruktur: <strong>Penetapan</strong>, <strong>Pelaksanaan</strong>, <strong>Evaluasi</strong>, <strong>Pengendalian</strong>, dan <strong>Peningkatan</strong>.
            </p>
        </div>

        <?php
        $ppepp_steps = [
            [
                'inisial'   => 'P',
                'tahap'     => '1',
                'nama'      => 'Penetapan (P)',
                'deskripsi' => 'Tahap merumuskan dan menetapkan standar mutu/indicator capaian standar akademik, non-akademik, dan tambahan yang disusun melampaui Standar Nasional Pendidikan Tinggi (SN Dikti).',
                'warna'     => '#7B1FA2',
                'bg_badge'  => 'rgba(123, 31, 162, 0.12)',
            ],
            [
                'inisial'   => 'P',
                'tahap'     => '2',
                'nama'      => 'Pelaksanaan (P)',
                'deskripsi' => 'Tahap menerapkan standar mutu/indicator capaian standar akademik, non-akademik, dan tambahan yang telah ditetapkan kedalam aktivitas operasional kampus.',
                'warna'     => '#1565C0',
                'bg_badge'  => 'rgba(21, 101, 192, 0.12)',
            ],
            [
                'inisial'   => 'E',
                'tahap'     => '3',
                'nama'      => 'Evaluasi (E)',
                'deskripsi' => 'Tahap pemantauan untuk mengukur dan menilai tingkat ketercapaian pelaksanaan terhadap standar/indikator yang sudah ditetapkan. Tahap ini berfungsi mendeteksi secara dini jika terjadi penyimpangan atau hambatan dalam pelaksanaan.',
                'warna'     => '#00897B',
                'bg_badge'  => 'rgba(0, 137, 123, 0.12)',
            ],
            [
                'inisial'   => 'P',
                'tahap'     => '4',
                'nama'      => 'Pengendalian (P)',
                'deskripsi' => 'Tahap analisis tindak lanjut terhadap hasil temuan evaluasi melalui Rapat Tinjauan Manajemen di tingkat Program Studi/Fakultas/Unit/Lembaga/Universitas sesuai hasil temuan. Tindak lanjut merupakan tindakan korektif terhadap temuan yang belum optimal dan tindakan untuk mempertahankan hasil evaluasi yang sudah optimal.',
                'warna'     => '#E65100',
                'bg_badge'  => 'rgba(230, 81, 0, 0.12)',
            ],
            [
                'inisial'   => 'P',
                'tahap'     => '5',
                'nama'      => 'Peningkatan (P)',
                'deskripsi' => 'Tahap menaikkan indicator capaian atau mutu standar yang sebelumnya sudah berhasil dipenuhi. Tahap ini merupakan tahap akhir dalam satu siklus kegiatan PPEPP.',
                'warna'     => '#C2185B',
                'bg_badge'  => 'rgba(194, 24, 91, 0.12)',
            ],
        ];
        ?>
        <!-- Grid 5 Tahapan PPEPP -->
        <div class="row g-4 mb-5">
            <?php foreach ($ppepp_steps as $step): ?>
            <div class="col-lg-4 col-md-6 <?= $step['tahap'] === '4' ? 'offset-lg-2' : '' ?>">
                <div class="card-lpm p-4 h-100 d-flex flex-column" style="background:#ffffff;border:1px solid var(--border);border-top:4px solid <?= $step['warna'] ?>;border-radius:var(--radius-md);box-shadow:0 4px 20px rgba(10,25,47,0.04);transition:transform 0.2s, box-shadow 0.2s;">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <div style="width:44px;height:44px;background:<?= $step['warna'] ?>;border-radius:12px;color:#fff;font-family:var(--font-heading);font-weight:800;font-size:1.25rem;display:flex;align-items:center;justify-content:center;box-shadow:0 4px 12px rgba(0,0,0,0.12);">
                            <?= $step['inisial'] ?>
                        </div>
                        <span style="font-size:0.75rem;font-weight:700;color:<?= $step['warna'] ?>;background:<?= $step['bg_badge'] ?>;padding:0.3rem 0.75rem;border-radius:20px;letter-spacing:0.3px;">
                            Tahap <?= $step['tahap'] ?>
                        </span>
                    </div>
                    <h4 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.15rem;margin-bottom:0.75rem;">
                        <?= $step['nama'] ?>
                    </h4>
                    <p style="font-size:0.88rem;color:var(--text-main);line-height:1.75;margin:0;flex-grow:1;">
                        <?= $step['deskripsi'] ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Callout Banner: Integrasi Portal SISTA -->
        <div style="background:linear-gradient(135deg, #0A192F, #132D54);border-radius:var(--radius-lg);padding:2rem 2.25rem;color:#ffffff;box-shadow:0 10px 30px rgba(10,25,47,0.15);">
            <div class="row align-items-center g-4">
                <div class="col-lg-8">
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge" style="background:rgba(255,255,255,0.15);color:#FFD54F;font-size:0.75rem;padding:0.35rem 0.7rem;font-weight:700;letter-spacing:0.5px;">
                            SISTEM INFORMASI STANDAR AKADEMIK
                        </span>
                    </div>
                    <h3 style="font-family:var(--font-heading);font-size:1.35rem;font-weight:800;color:#ffffff;margin-bottom:0.6rem;">
                        Pemantauan &amp; Pelaporan Siklus PPEPP melalui Portal SISTA
                    </h3>
                    <p style="color:rgba(255,255,255,0.8);font-size:0.92rem;line-height:1.7;margin:0;">
                        Seluruh perumusan capaian, pengisian bukti dukung pelaksanaan, evaluasi berkala, hingga rencana tindak lanjut pengendalian mutu di lingkungan Universitas Katolik Soegijapranata dimonitor secara digital dan terintegrasi melalui portal SISTA.
                    </p>
                </div>
                <div class="col-lg-4 text-lg-end">
                    <a href="https://sista.unika.ac.id" target="_blank" class="btn-hero-primary d-inline-flex align-items-center gap-2" style="background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border:none;padding:0.85rem 1.75rem;font-size:0.95rem;font-weight:700;box-shadow:0 6px 20px rgba(123,31,162,0.4);text-decoration:none;">
                        <span>Buka Portal SISTA</span>
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 6H5.25A2.25 2.25 0 0 0 3 8.25v10.5A2.25 2.25 0 0 0 5.25 21h10.5A2.25 2.25 0 0 0 18 18.75V10.5m-10.5 6L21 3m0 0h-5.25M21 3v5.25" />
                        </svg>
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================
     3. DOKUMEN MUTU SPMI (Scroll ke bawah)
============================================== -->
<section class="py-5" id="dokumen-spmi">
    <div class="container">
        <div class="d-flex align-items-end justify-content-between mb-4 flex-wrap gap-3">
            <div>
                <span class="section-tag mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    Berkas Resmi
                </span>
                <h2 class="section-title mb-0">Dokumen Mutu SPMI</h2>
            </div>
            
            <!-- Filter Kategori Tabs -->
            <div style="display:flex;gap:0.5rem;flex-wrap:wrap;">
                <a href="spmi.php#dokumen-spmi" class="btn-action <?= $kategori_filter === '' ? 'btn-edit' : '' ?>" style="<?= $kategori_filter === '' ? '' : 'background:var(--bg-white);color:var(--text-muted);border:1.5px solid var(--border);' ?>">
                    Semua Dokumen
                </a>
                <?php foreach ($allowed_kategori as $kat): ?>
                <a href="spmi.php?kategori=<?= urlencode($kat) ?>#dokumen-spmi" class="btn-action <?= $kategori_filter === $kat ? 'btn-edit' : '' ?>" style="<?= $kategori_filter === $kat ? '' : 'background:var(--bg-white);color:var(--text-muted);border:1.5px solid var(--border);' ?>">
                    <?= e($kat) ?>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

        <?php if (empty($dokumen_all)): ?>
        <div class="card-lpm p-5 text-center" style="background:#ffffff;border:1px solid var(--border);border-radius:var(--radius-lg);">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--text-muted)" width="48" height="48" style="opacity:0.3;margin-bottom:1rem;">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
            </svg>
            <h5 style="color:var(--navy);font-weight:700;">Dokumen Sedang Disiapkan</h5>
            <p style="color:var(--text-muted);font-size:0.875rem;max-width:480px;margin:0 auto 1.5rem;">
                Berkas dokumen SPMI untuk kategori ini sedang diunggah dan disinkronkan oleh Administrator LPM UNIKA.
            </p>
            <a href="<?= SITE_URL ?>/layanan.php" class="btn-hero-primary" style="display:inline-flex;padding:0.6rem 1.4rem;font-size:0.85rem;">
                Hubungi Kami untuk Salinan Dokumen &rarr;
            </a>
        </div>
        <?php else: ?>

        <?php foreach (array_keys($dokumen_grouped) as $kat): ?>
        <?php if (isset($dokumen_grouped[$kat]) && !empty($dokumen_grouped[$kat])): ?>
        <div class="doc-table-wrap mb-4">
            <div class="doc-table-header d-flex justify-content-between align-items-center">
                <span class="doc-table-header-title">
                    Kategori: <?= e($kat) ?>
                </span>
                <span style="font-size:0.75rem;color:rgba(255,255,255,0.7);"><?= count($dokumen_grouped[$kat]) ?> Dokumen</span>
            </div>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Nama Dokumen Mutu</th>
                        <th width="160">Kategori</th>
                        <th width="140">Tanggal Unggah</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dokumen_grouped[$kat] as $i => $d): 
                        $file_full = __DIR__ . '/uploads/dokumen/' . $d['file_path'];
                        $file_url  = SITE_URL . '/uploads/dokumen/' . e($d['file_path']);
                        $ext       = strtolower(pathinfo($d['file_path'], PATHINFO_EXTENSION));
                        $is_pdf    = ($ext === 'pdf');
                        $has_file  = $d['file_path'] && file_exists($file_full);
                        $safe_title = addslashes(htmlspecialchars($d['nama_dokumen'], ENT_QUOTES));
                    ?>
                    <tr>
                        <td style="color:var(--text-muted);font-size:0.8rem;text-align:center;"><?= $i + 1 ?></td>
                        <td>
                            <?php if ($has_file && $is_pdf): ?>
                            <a href="javascript:void(0)" onclick="openPdfViewer('<?= $file_url ?>', '<?= $safe_title ?>')" style="font-weight:600;color:var(--navy);text-decoration:none;cursor:pointer;" class="doc-title-link" title="Klik untuk membuka dokumen PDF">
                                <?= e($d['nama_dokumen']) ?>
                            </a>
                            <?php elseif ($has_file): ?>
                            <a href="<?= $file_url ?>" download style="font-weight:600;color:var(--navy);text-decoration:none;">
                                <?= e($d['nama_dokumen']) ?>
                            </a>
                            <?php else: ?>
                            <div style="font-weight:600;color:var(--navy);"><?= e($d['nama_dokumen']) ?></div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="card-category-badge"><?= e($d['kategori']) ?></span>
                        </td>
                        <td style="font-size:0.82rem;color:var(--text-muted);"><?= formatTanggal($d['created_at']) ?></td>
                        <td>
                            <?php if ($has_file): ?>
                                <?php if ($is_pdf): ?>
                                <button type="button" class="btn-download" onclick="openPdfViewer('<?= $file_url ?>', '<?= $safe_title ?>')" style="border:none;background:rgba(123,31,162,0.1);color:var(--purple);font-weight:700;cursor:pointer;" title="Buka dan baca PDF">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    Buka PDF
                                </button>
                                <?php else: ?>
                                <a href="<?= $file_url ?>" class="btn-download" download>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    Unduh
                                </a>
                                <?php endif; ?>
                            <?php else: ?>
                            <span style="font-size:0.75rem;color:var(--text-muted);">Tersedia di LPM</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
        <?php endforeach; ?>

        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
