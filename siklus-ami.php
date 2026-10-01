<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Siklus Audit Mutu Internal (AMI) – Siklus 1 s.d. 5';
$meta_desc  = 'Pelaksanaan Siklus 1 sampai 5 Audit Mutu Internal (AMI) Universitas Katolik Soegijapranata: Rangkaian Kegiatan, e-AMI, Audit Lapangan, dan RTM.';

$db = getDB();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

// 1. Ambil Data Periode Aktif
$periodes = [];
try {
    $periodes = $db->query("SELECT * FROM ami_periode WHERE is_active = 1 ORDER BY urutan ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

if (empty($periodes)) {
    $periodes = [
        ['id' => 1, 'nama_periode' => '2025/2026'],
        ['id' => 2, 'nama_periode' => '2026/2027']
    ];
}

$selected_periode = trim($_GET['periode'] ?? '');
if ($selected_periode === '' || !in_array($selected_periode, array_column($periodes, 'nama_periode'))) {
    $selected_periode = $periodes[0]['nama_periode'];
}

$active_siklus = (int)($_GET['siklus'] ?? 1);
if ($active_siklus < 1 || $active_siklus > 5) {
    $active_siklus = 1;
}

// 2. Query Data untuk Setiap Siklus Sesuai Periode
// Siklus 1: Kegiatan & Opening
$s1_kegiatan = [];
$s1_opening  = [];
try {
    $stmt_keg = $db->prepare("SELECT * FROM ami_siklus1_kegiatan WHERE periode = ? ORDER BY urutan ASC, id DESC");
    $stmt_keg->execute([$selected_periode]);
    $s1_kegiatan = $stmt_keg->fetchAll(PDO::FETCH_ASSOC);

    $stmt_open = $db->prepare("SELECT * FROM ami_siklus1_opening WHERE periode = ? ORDER BY urutan ASC, id DESC");
    $stmt_open->execute([$selected_periode]);
    $s1_opening = $stmt_open->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Siklus 2 & 3: Link e-AMI
$s2_judul  = getPengaturan('ami_siklus2_judul', 'e-AMI: Siklus 2');
$s2_url    = getPengaturan('ami_siklus2_url', '');
$s2_status = getPengaturan('ami_siklus2_status', 'coming_soon');
$s2_desc   = getPengaturan('ami_siklus2_desc', 'Portal sistem e-AMI untuk pelaksanaan audit desk evaluation dan verifikasi dokumen Siklus 2.');

$s3_judul  = getPengaturan('ami_siklus3_judul', 'e-AMI: Siklus 3');
$s3_url    = getPengaturan('ami_siklus3_url', '');
$s3_status = getPengaturan('ami_siklus3_status', 'coming_soon');
$s3_desc   = getPengaturan('ami_siklus3_desc', 'Portal sistem e-AMI untuk evaluasi hasil audit lapangan dan verifikasi temuan Siklus 3.');

// Siklus 4: Dokumen & Dokumentasi
$s4_dokumen     = [];
$s4_prosedur    = [];
$s4_jadwal      = [];
$s4_dokumentasi = [];
try {
    $stmt_dok4 = $db->prepare("SELECT * FROM ami_siklus4_dokumen WHERE periode = ? ORDER BY jenis ASC, urutan ASC, id DESC");
    $stmt_dok4->execute([$selected_periode]);
    $s4_dokumen = $stmt_dok4->fetchAll(PDO::FETCH_ASSOC);

    $s4_prosedur = array_filter($s4_dokumen, fn($d) => $d['jenis'] === 'prosedur');
    $s4_jadwal   = array_filter($s4_dokumen, fn($d) => $d['jenis'] === 'jadwal');

    $stmt_dokt4 = $db->prepare("SELECT * FROM ami_siklus4_dokumentasi WHERE periode = ? ORDER BY tingkat ASC, fakultas ASC, prodi ASC, id DESC");
    $stmt_dokt4->execute([$selected_periode]);
    $s4_dokumentasi = $stmt_dokt4->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

// Siklus 5: RTM
$s5_rtm = [];
try {
    $stmt_rtm5 = $db->prepare("SELECT * FROM ami_siklus5_rtm WHERE periode = ? ORDER BY CASE WHEN tingkat = 'universitas' THEN 1 WHEN tingkat = 'fakultas' THEN 2 ELSE 3 END ASC, fakultas ASC, id DESC");
    $stmt_rtm5->execute([$selected_periode]);
    $s5_rtm = $stmt_rtm5->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

?>

<style>
/* Styling Premium Khusus Siklus AMI */
/* Card Siklus AMI 5 Tahapan (Sesuai Desain Foto 2) */
.ami-stage-card {
    background: #ffffff;
    border: 1.5px solid #E2E8F0;
    border-top: 5px solid #071739 !important;
    border-radius: 14px;
    padding: 1.6rem 1.3rem;
    display: flex;
    flex-direction: column;
    height: 100%;
    text-decoration: none !important;
    color: inherit;
    transition: all 0.25s cubic-bezier(0.16, 1, 0.3, 1);
    box-shadow: 0 4px 14px rgba(0,0,0,0.04);
    position: relative;
    overflow: hidden;
}
.ami-stage-card:hover {
    border-color: #071739;
    transform: translateY(-4px);
    box-shadow: 0 12px 24px rgba(7,23,57,0.12);
}
.ami-stage-card.active {
    border-color: #071739;
    border-width: 2px;
    transform: translateY(-4px);
    box-shadow: 0 14px 28px rgba(7,23,57,0.16);
    background: #ffffff;
}
.ami-stage-card.active::after {
    content: '';
    position: absolute;
    bottom: 0;
    left: 15%;
    right: 15%;
    height: 3px;
    background: #071739;
    border-radius: 3px 3px 0 0;
}
.ami-stage-number {
    width: 38px;
    height: 38px;
    border-radius: 50%;
    background: #071739;
    color: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 1.05rem;
    margin-bottom: 1.15rem;
    flex-shrink: 0;
    transition: all 0.2s ease;
}
.ami-stage-card.active .ami-stage-number {
    box-shadow: 0 0 0 4px rgba(7,23,57,0.15);
}
.ami-stage-title {
    font-family: var(--font-heading);
    font-size: 1.02rem;
    font-weight: 800;
    color: #071739;
    margin-bottom: 0.75rem;
    line-height: 1.35;
}
.ami-stage-desc {
    font-size: 0.84rem;
    color: #475569;
    line-height: 1.6;
    margin: 0;
}

/* Card Rangkaian Dokumen */
.doc-ami-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 12px;
    padding: 1.25rem;
    display: flex;
    align-items: center;
    gap: 1rem;
    transition: all 0.2s ease;
}
.doc-ami-card:hover {
    border-color: #CBD5E1;
    box-shadow: 0 6px 18px rgba(0,0,0,0.04);
    transform: translateY(-2px);
}
.doc-icon-box {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.4rem;
    flex-shrink: 0;
}

/* Filter Periode Pills */
.periode-filter-box {
    background: #ffffff;
    border-radius: 50px;
    padding: 4px;
    border: 1.5px solid #E2E8F0;
    display: inline-flex;
    align-items: center;
    gap: 4px;
}
.periode-pill {
    padding: 0.4rem 1.1rem;
    border-radius: 50px;
    font-size: 0.85rem;
    font-weight: 700;
    text-decoration: none !important;
    color: #64748B;
    transition: all 0.2s ease;
}
.periode-pill:hover {
    color: var(--navy);
    background: #F1F5F9;
}
.periode-pill.active {
    background: var(--navy, #0B1F44);
    color: #ffffff;
    box-shadow: 0 3px 10px rgba(11,31,68,0.15);
}

/* Photo Gallery Item */
.ami-gallery-item {
    position: relative;
    border-radius: 12px;
    overflow: hidden;
    height: 220px;
    cursor: pointer;
    box-shadow: 0 4px 15px rgba(0,0,0,0.06);
}
.ami-gallery-item img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.ami-gallery-item:hover img {
    transform: scale(1.06);
}
.ami-gallery-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(11,31,68,0.88) 0%, rgba(11,31,68,0.2) 60%, transparent 100%);
    padding: 1rem;
    display: flex;
    flex-direction: column;
    justify-content: flex-end;
    color: #ffffff;
}

/* Portal e-AMI Card */
.eami-portal-card {
    background: linear-gradient(135deg, #0B1F44 0%, #15325B 100%);
    color: #ffffff;
    border-radius: 20px;
    padding: 3rem 2.5rem;
    text-align: center;
    position: relative;
    overflow: hidden;
    box-shadow: 0 20px 40px rgba(11,31,68,0.16);
}
.eami-portal-card::before {
    content: '';
    position: absolute;
    top: -50%;
    right: -20%;
    width: 400px;
    height: 400px;
    border-radius: 50%;
    background: radial-gradient(circle, rgba(106,27,154,0.3) 0%, transparent 70%);
    pointer-events: none;
}
</style>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Pelaksanaan Mutu Komprehensif
        </div>
        <h1 class="page-banner-title">Siklus Audit Mutu Internal (AMI)</h1>
        <p class="text-white-50 mt-2 mb-0" style="max-width:720px;font-size:0.95rem;line-height:1.6;">
            <?= e(getPengaturan('ami_siklus_banner_desc', 'Rangkaian terintegrasi tahapan Siklus 1 hingga Siklus 5 AMI Universitas Katolik Soegijapranata untuk menjamin standar mutu pendidikan tinggi yang unggul dan berkelanjutan.')) ?>
        </p>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/ami.php">AMI</a>
            <span>/</span>
            <span class="current">Siklus AMI</span>
        </div>
    </div>
</div>

<!-- Main Content Area -->
<section class="py-5" style="background:#F8FAFC;min-height:70vh;">
    <div class="container">

        <!-- Top Bar: Filter Periode & Info Status -->
        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
            <div>
                <span class="text-uppercase fw-bold text-muted" style="font-size:0.75rem;letter-spacing:1px;">Pilih Periode Audit:</span>
                <div class="periode-filter-box mt-1">
                    <?php foreach ($periodes as $p): ?>
                    <a href="?siklus=<?= $active_siklus ?>&periode=<?= urlencode($p['nama_periode']) ?>" class="periode-pill <?= $selected_periode === $p['nama_periode'] ? 'active' : '' ?>">
                        <i class="bi bi-calendar2-check me-1"></i><?= e($p['nama_periode']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="d-flex align-items-center gap-2">
                <span class="badge bg-white text-dark border px-3 py-2 shadow-sm" style="font-size:0.85rem;">
                    <i class="bi bi-calendar3 text-primary me-1"></i> Periode Aktif: <strong><?= e($selected_periode) ?></strong>
                </span>
            </div>
        </div>

        <!-- 5 Tahapan Siklus AMI (Gaya Desain Foto Kedua) -->
        <div class="row row-cols-1 row-cols-md-3 row-cols-lg-5 g-3 mb-5" id="siklusContent">
            <?php
            $stages = [
                1 => [
                    'num'   => '1',
                    'title' => getPengaturan('ami_stage1_title', 'Perencanaan & Sosialisasi'),
                    'desc'  => getPengaturan('ami_stage1_desc', 'Penetapan jadwal siklus audit, penunjukan tim auditor tersertifikasi, dan pengiriman surat pemberitahuan ke seluruh auditee.')
                ],
                2 => [
                    'num'   => '2',
                    'title' => getPengaturan('ami_stage2_title', 'Pengisian Dokumen Kinerja (DED)'),
                    'desc'  => getPengaturan('ami_stage2_desc', 'Program Studi dan Unit Kerja mengisi instrumen evaluasi diri dan mengunggah bukti fisik pendukung.')
                ],
                3 => [
                    'num'   => '3',
                    'title' => getPengaturan('ami_stage3_title', 'Audit Dokumen (Desk Evaluation)'),
                    'desc'  => getPengaturan('ami_stage3_desc', 'Auditor memeriksa kecukupan dan kesesuaian dokumen bukti kerja terhadap standar mutu sebelum visitasi.')
                ],
                4 => [
                    'num'   => '4',
                    'title' => getPengaturan('ami_stage4_title', 'Audit Lapangan (Visitasi)'),
                    'desc'  => getPengaturan('ami_stage4_desc', 'Auditor melakukan verifikasi langsung, wawancara auditee, konfirmasi temuan KTS (Ketidaksesuaian), dan penandatanganan berita acara.')
                ],
                5 => [
                    'num'   => '5',
                    'title' => getPengaturan('ami_stage5_title', 'Rapat Tinjauan Manajemen (RTM)'),
                    'desc'  => getPengaturan('ami_stage5_desc', 'Penyampaian rekapitulasi temuan audit kepada Rektorat dan Pimpinan Unit untuk perumusan Rencana Tindak Lanjut (RTL).')
                ],
            ];
            foreach ($stages as $s_num => $st):
                $is_curr = ($active_siklus === $s_num);
            ?>
            <div class="col">
                <a href="?siklus=<?= $s_num ?>&periode=<?= urlencode($selected_periode) ?>#siklusContent" class="ami-stage-card <?= $is_curr ? 'active' : '' ?>">
                    <div class="ami-stage-number"><?= $st['num'] ?></div>
                    <div class="ami-stage-title"><?= e($st['title']) ?></div>
                    <p class="ami-stage-desc"><?= e($st['desc']) ?></p>
                    <?php if ($is_curr): ?>
                    <div class="mt-3 pt-2 border-top border-light-subtle d-flex align-items-center gap-1 text-primary fw-bold" style="font-size:0.75rem;">
                        <i class="bi bi-arrow-down-circle-fill"></i> Sedang Ditampilkan
                    </div>
                    <?php endif; ?>
                </a>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- TAB BODY CONTENT -->

        <?php if ($active_siklus === 1): ?>
        <!-- ============================================== -->
        <!-- SIKLUS 1: RANGKAIAN KEGIATAN & OPENING MEETING -->
        <!-- ============================================== -->
        <div class="row g-4 mb-5">
            <!-- 1.1 Rangkaian Kegiatan Softfile Dokumen -->
            <div class="col-lg-6">
                <div class="card p-4 border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(14,116,144,0.1);color:#0e7490;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                            <i class="bi bi-file-earmark-text-fill"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0" style="color:var(--navy);font-size:1.2rem;">Rangkaian Kegiatan AMI</h4>
                            <span class="text-muted small">Dokumen softfile pelaksanaan periode <?= e($selected_periode) ?></span>
                        </div>
                    </div>

                    <?php if (empty($s1_kegiatan)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-file-earmark-x fs-1 text-muted opacity-50 mb-2 d-block"></i>
                        Belum ada dokumen rangkaian kegiatan yang diunggah untuk periode <?= e($selected_periode) ?>.
                    </div>
                    <?php else: ?>
                    <div class="vstack gap-3">
                        <?php foreach ($s1_kegiatan as $keg): ?>
                        <div class="doc-ami-card">
                            <div class="doc-icon-box" style="background:rgba(30,58,138,0.08);color:#1e3a8a;">
                                <i class="bi bi-file-earmark-pdf"></i>
                            </div>
                            <div class="flex-grow-1 min-w-0">
                                <h6 class="fw-bold mb-1 text-truncate" style="color:var(--navy);font-size:0.95rem;"><?= e($keg['judul']) ?></h6>
                                <div class="text-muted small d-flex align-items-center gap-2">
                                    <span><i class="bi bi-hdd me-1"></i><?= e($keg['file_size'] ?? 'Softfile') ?></span>
                                    <span>•</span>
                                    <span class="text-truncate"><?= basename($keg['file_dokumen']) ?></span>
                                </div>
                                <?php if (!empty($keg['keterangan'])): ?>
                                <p class="text-muted small mb-0 mt-1" style="font-size:0.8rem;line-height:1.4;"><?= e($keg['keterangan']) ?></p>
                                <?php endif; ?>
                            </div>
                            <a href="<?= SITE_URL ?>/uploads/<?= e($keg['file_dokumen']) ?>" target="_blank" download class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1 flex-shrink-0">
                                <i class="bi bi-download me-1"></i> Unduh
                            </a>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 1.2 Opening Meeting AMI (Foto, Judul, Tanggal) -->
            <div class="col-lg-6">
                <div class="card p-4 border-0 shadow-sm rounded-4 h-100 bg-white">
                    <div class="d-flex align-items-center gap-3 mb-4">
                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(106,27,154,0.1);color:var(--purple);display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                            <i class="bi bi-camera-fill"></i>
                        </div>
                        <div>
                            <h4 class="fw-bold mb-0" style="color:var(--navy);font-size:1.2rem;">Opening Meeting AMI</h4>
                            <span class="text-muted small">Dokumentasi foto dan tanggal pembukaan periode <?= e($selected_periode) ?></span>
                        </div>
                    </div>

                    <?php if (empty($s1_opening)): ?>
                    <div class="text-center py-5 text-muted">
                        <i class="bi bi-image fs-1 text-muted opacity-50 mb-2 d-block"></i>
                        Belum ada foto opening meeting untuk periode <?= e($selected_periode) ?>.
                    </div>
                    <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($s1_opening as $op): ?>
                        <div class="col-sm-6">
                            <div class="ami-gallery-item" onclick="openPhotoModal('<?= SITE_URL ?>/uploads/<?= e($op['foto']) ?>', '<?= e($op['judul']) ?>', '<?= !empty($op['tanggal_kegiatan']) ? formatTanggal($op['tanggal_kegiatan']) : '' ?>')">
                                <img src="<?= SITE_URL ?>/uploads/<?= e($op['foto']) ?>" alt="<?= e($op['judul']) ?>">
                                <div class="ami-gallery-overlay">
                                    <?php if (!empty($op['tanggal_kegiatan'])): ?>
                                    <span class="badge bg-warning text-dark align-self-start mb-2" style="font-size:0.7rem;">
                                        <i class="bi bi-calendar-event me-1"></i><?= formatTanggal($op['tanggal_kegiatan']) ?>
                                    </span>
                                    <?php endif; ?>
                                    <div class="fw-bold text-white small" style="line-height:1.3;"><?= e($op['judul']) ?></div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php elseif ($active_siklus === 2): ?>
        <!-- ============================================== -->
        <!-- SIKLUS 2: HYPERLINK e-AMI                     -->
        <!-- ============================================== -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8">
                <div class="eami-portal-card">
                    <div style="width:72px;height:72px;border-radius:20px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2rem;color:#FFD54F;">
                        <i class="bi bi-cpu-fill"></i>
                    </div>
                    <span class="badge bg-primary px-3 py-2 rounded-pill mb-3" style="font-size:0.85rem;letter-spacing:0.5px;">TAHAP SIKLUS 2</span>
                    <h2 class="fw-bold text-white mb-3"><?= e($s2_judul) ?></h2>
                    <p class="lead text-white-50 mx-auto mb-4" style="max-width:600px;font-size:1.05rem;line-height:1.7;">
                        <?= nl2br(e($s2_desc)) ?>
                    </p>

                    <?php if ($s2_status === 'active' && !empty($s2_url)): ?>
                    <div>
                        <a href="<?= e($s2_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-warning btn-lg px-5 py-3 fw-bold rounded-pill text-dark shadow-lg hover-lift">
                            <i class="bi bi-box-arrow-up-right me-2"></i> Akses Portal e-AMI Sekarang
                        </a>
                        <div class="text-white-50 small mt-3"><i class="bi bi-shield-lock me-1"></i> Membuka sistem portal e-AMI di tab peramban baru.</div>
                    </div>
                    <?php else: ?>
                    <div class="p-4 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-15 d-inline-block">
                        <div class="badge bg-warning text-dark px-3 py-2 rounded-pill mb-2"><i class="bi bi-hourglass-split me-1"></i> Segera Hadir</div>
                        <div class="text-white fw-bold">Tautan Sistem e-AMI Belum Dibuka</div>
                        <div class="text-white-50 small mt-1">Sistem pelaksanaan Siklus 2 akan segera diaktifkan sesuai jadwal kalender audit internal.</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php elseif ($active_siklus === 3): ?>
        <!-- ============================================== -->
        <!-- SIKLUS 3: HYPERLINK e-AMI                     -->
        <!-- ============================================== -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-8">
                <div class="eami-portal-card" style="background:linear-gradient(135deg, #3B0764 0%, #1E1B4B 100%);">
                    <div style="width:72px;height:72px;border-radius:20px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;font-size:2rem;color:#38BDF8;">
                        <i class="bi bi-shield-check"></i>
                    </div>
                    <span class="badge bg-purple px-3 py-2 rounded-pill mb-3" style="font-size:0.85rem;letter-spacing:0.5px;">TAHAP SIKLUS 3</span>
                    <h2 class="fw-bold text-white mb-3"><?= e($s3_judul) ?></h2>
                    <p class="lead text-white-50 mx-auto mb-4" style="max-width:600px;font-size:1.05rem;line-height:1.7;">
                        <?= nl2br(e($s3_desc)) ?>
                    </p>

                    <?php if ($s3_status === 'active' && !empty($s3_url)): ?>
                    <div>
                        <a href="<?= e($s3_url) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-info btn-lg px-5 py-3 fw-bold rounded-pill text-dark shadow-lg hover-lift">
                            <i class="bi bi-box-arrow-up-right me-2"></i> Akses Portal e-AMI Siklus 3
                        </a>
                        <div class="text-white-50 small mt-3"><i class="bi bi-shield-lock me-1"></i> Membuka portal e-AMI visitasi di tab peramban baru.</div>
                    </div>
                    <?php else: ?>
                    <div class="p-4 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-15 d-inline-block">
                        <div class="badge bg-info text-dark px-3 py-2 rounded-pill mb-2"><i class="bi bi-hourglass-split me-1"></i> Segera Hadir</div>
                        <div class="text-white fw-bold">Tautan Sistem e-AMI Siklus 3 Belum Dibuka</div>
                        <div class="text-white-50 small mt-1">Pelaksanaan visitasi dan rekapitulasi audit e-AMI akan diaktifkan sesuai jadwal.</div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php elseif ($active_siklus === 4): ?>
        <!-- ============================================== -->
        <!-- SIKLUS 4: AUDIT LAPANGAN                       -->
        <!-- ============================================== -->
        <div class="vstack gap-4 mb-5">
            <!-- 4.1 Dokumen Prosedur & Jadwal Audit Lapangan -->
            <div class="row g-4">
                <!-- Prosedur Mutu -->
                <div class="col-md-6">
                    <div class="card p-4 border-0 shadow-sm rounded-4 h-100 bg-white">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width:44px;height:44px;border-radius:10px;background:rgba(30,58,138,0.1);color:#1e3a8a;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                                <i class="bi bi-journal-bookmark-fill"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0" style="color:var(--navy);font-size:1.1rem;">Prosedur Sistem Mutu Audit Lapangan</h5>
                                <span class="text-muted small">Standar operasional prosedur pelaksanaan audit lapangan</span>
                            </div>
                        </div>

                        <?php if (empty($s4_prosedur)): ?>
                        <div class="text-center py-4 text-muted small">
                            Belum ada dokumen prosedur audit lapangan untuk periode <?= e($selected_periode) ?>.
                        </div>
                        <?php else: ?>
                        <div class="vstack gap-2">
                            <?php foreach ($s4_prosedur as $p): ?>
                            <div class="doc-ami-card p-3">
                                <div class="doc-icon-box" style="background:#EFF6FF;color:#2563EB;">
                                    <i class="bi bi-file-earmark-check"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <h6 class="fw-bold mb-1 text-truncate" style="color:var(--navy);font-size:0.92rem;"><?= e($p['judul']) ?></h6>
                                    <div class="text-muted small text-truncate"><?= basename($p['file_dokumen']) ?> (<?= e($p['file_size'] ?? 'PDF') ?>)</div>
                                </div>
                                <a href="<?= SITE_URL ?>/uploads/<?= e($p['file_dokumen']) ?>" target="_blank" download class="btn btn-sm btn-primary rounded-pill px-3 py-1 flex-shrink-0">
                                    <i class="bi bi-download me-1"></i> Unduh
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Jadwal Audit Lapangan -->
                <div class="col-md-6">
                    <div class="card p-4 border-0 shadow-sm rounded-4 h-100 bg-white">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div style="width:44px;height:44px;border-radius:10px;background:rgba(2,132,199,0.1);color:#0284c7;display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                                <i class="bi bi-calendar-range-fill"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0" style="color:var(--navy);font-size:1.1rem;">Jadwal Audit Lapangan</h5>
                                <span class="text-muted small">Alokasi jadwal visitasi dan auditor yang bertugas</span>
                            </div>
                        </div>

                        <?php if (empty($s4_jadwal)): ?>
                        <div class="text-center py-4 text-muted small">
                            Belum ada dokumen jadwal audit lapangan untuk periode <?= e($selected_periode) ?>.
                        </div>
                        <?php else: ?>
                        <div class="vstack gap-2">
                            <?php foreach ($s4_jadwal as $j): ?>
                            <div class="doc-ami-card p-3">
                                <div class="doc-icon-box" style="background:#F0FDF4;color:#16A34A;">
                                    <i class="bi bi-calendar3"></i>
                                </div>
                                <div class="flex-grow-1 min-w-0">
                                    <h6 class="fw-bold mb-1 text-truncate" style="color:var(--navy);font-size:0.92rem;"><?= e($j['judul']) ?></h6>
                                    <div class="text-muted small text-truncate"><?= basename($j['file_dokumen']) ?> (<?= e($j['file_size'] ?? 'PDF') ?>)</div>
                                </div>
                                <a href="<?= SITE_URL ?>/uploads/<?= e($j['file_dokumen']) ?>" target="_blank" download class="btn btn-sm btn-outline-success rounded-pill px-3 py-1 flex-shrink-0">
                                    <i class="bi bi-download me-1"></i> Unduh
                                </a>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 4.2 Dokumentasi Audit Lapangan (Tingkat Fakultas & Prodi) -->
            <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 bg-white">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 mb-4 border-bottom">
                    <div>
                        <h4 class="fw-bold mb-1" style="color:var(--navy);"><i class="bi bi-journal-text text-purple me-2"></i>Dokumentasi Audit Lapangan Fakultas &amp; Program Studi</h4>
                        <div class="text-muted small">Dokumentasi hasil audit lapangan, narasi berita acara, presensi kehadiran, dan galeri foto kegiatan.</div>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" id="filterSiklus4BtnGroup">
                        <button type="button" class="btn btn-outline-primary active" onclick="filterSiklus4('all', this)">Semua (<?= count($s4_dokumentasi) ?>)</button>
                        <button type="button" class="btn btn-outline-primary" onclick="filterSiklus4('fakultas', this)">Tingkat Fakultas</button>
                        <button type="button" class="btn btn-outline-primary" onclick="filterSiklus4('prodi', this)">Tingkat Program Studi</button>
                    </div>
                </div>

                <?php if (empty($s4_dokumentasi)): ?>
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-folder2-open fs-1 text-muted opacity-50 mb-2 d-block"></i>
                    Belum ada dokumentasi audit lapangan untuk periode <?= e($selected_periode) ?>.
                </div>
                <?php else: ?>
                <div class="row g-4" id="s4DokumentasiGrid">
                    <?php foreach ($s4_dokumentasi as $item): ?>
                    <?php 
                    $photos = !empty($item['foto_kegiatan']) ? json_decode($item['foto_kegiatan'], true) : [];
                    $tingkat_cls = ($item['tingkat'] === 'prodi') ? 'tingkat-prodi' : 'tingkat-fakultas';
                    ?>
                    <div class="col-lg-6 s4-item <?= $tingkat_cls ?>">
                        <div class="card h-100 p-4 border rounded-4 shadow-none bg-light" style="border-left: 4px solid <?= $item['tingkat'] === 'prodi' ? 'var(--purple)' : 'var(--primary)' ?> !important;">
                            <div class="d-flex align-items-center justify-content-between mb-2">
                                <span class="badge <?= $item['tingkat'] === 'prodi' ? 'bg-purple' : 'bg-primary' ?> px-2 py-1" style="font-size:0.75rem;">
                                    <?= $item['tingkat'] === 'prodi' ? 'Tingkat Program Studi' : 'Tingkat Fakultas' ?>
                                </span>
                                <span class="text-muted small fw-bold">Periode <?= e($item['periode']) ?></span>
                            </div>

                            <h5 class="fw-bold mb-1" style="color:var(--navy);font-size:1.1rem;line-height:1.4;"><?= e($item['judul']) ?></h5>
                            
                            <div class="text-muted small mb-3">
                                <strong><?= e($item['fakultas']) ?></strong>
                                <?php if (!empty($item['prodi'])): ?>
                                <span> • <?= e($item['prodi']) ?></span>
                                <?php endif; ?>
                            </div>

                            <?php if (!empty($item['narasi_berita_acara'])): ?>
                            <div class="p-3 bg-white rounded-3 border mb-3 small" style="line-height:1.6;color:#334155;">
                                <div class="fw-bold text-dark mb-1 small"><i class="bi bi-card-text me-1 text-primary"></i>Ringkasan Berita Acara:</div>
                                <?= nl2br(e($item['narasi_berita_acara'])) ?>
                            </div>
                            <?php endif; ?>

                            <!-- Berkas Download (Berita Acara & Daftar Hadir) -->
                            <div class="d-flex flex-wrap gap-2 mb-3">
                                <?php if (!empty($item['file_berita_acara'])): ?>
                                <a href="<?= SITE_URL ?>/uploads/<?= e($item['file_berita_acara']) ?>" target="_blank" download class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size:0.8rem;">
                                    <i class="bi bi-file-earmark-check-fill me-1"></i> Softfile Berita Acara
                                </a>
                                <?php endif; ?>

                                <?php if (!empty($item['file_daftar_hadir'])): ?>
                                <a href="<?= SITE_URL ?>/uploads/<?= e($item['file_daftar_hadir']) ?>" target="_blank" download class="btn btn-sm btn-outline-success rounded-pill px-3 py-1" style="font-size:0.8rem;">
                                    <i class="bi bi-card-checklist me-1"></i> Daftar Hadir
                                </a>
                                <?php endif; ?>
                            </div>

                            <!-- Galeri Foto Dokumentasi -->
                            <?php if (!empty($photos)): ?>
                            <div class="mt-auto pt-2 border-top">
                                <div class="small fw-bold text-muted mb-2"><i class="bi bi-images me-1"></i>Galeri Foto Kegiatan (<?= count($photos) ?>):</div>
                                <div class="d-flex gap-2 overflow-auto pb-1" style="scrollbar-width:none;">
                                    <?php foreach ($photos as $pimg): ?>
                                    <div style="width:70px;height:55px;border-radius:8px;overflow:hidden;flex-shrink:0;cursor:pointer;" onclick="openPhotoModal('<?= SITE_URL ?>/uploads/<?= e($pimg) ?>', '<?= e($item['judul']) ?>', '<?= e($item['fakultas']) ?>')">
                                        <img src="<?= SITE_URL ?>/uploads/<?= e($pimg) ?>" alt="Foto" style="width:100%;height:100%;object-fit:cover;">
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <?php elseif ($active_siklus === 5): ?>
        <!-- ============================================== -->
        <!-- SIKLUS 5: RAPAT TINJAUAN MANAJEMEN (RTM)       -->
        <!-- ============================================== -->
        <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 bg-white mb-5">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 pb-3 mb-4 border-bottom">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--navy);"><i class="bi bi-people-fill text-purple me-2"></i>Rapat Tinjauan Manajemen (RTM)</h4>
                    <div class="text-muted small">Penyampaian hasil audit, perumusan kebijakan mutu, dan komitmen tindak lanjut tingkat Universitas, Fakultas, dan Prodi.</div>
                </div>
                <div class="btn-group btn-group-sm" role="group" id="filterSiklus5BtnGroup">
                    <button type="button" class="btn btn-outline-primary active" onclick="filterSiklus5('all', this)">Semua (<?= count($s5_rtm) ?>)</button>
                    <button type="button" class="btn btn-outline-primary" onclick="filterSiklus5('universitas', this)">Universitas</button>
                    <button type="button" class="btn btn-outline-primary" onclick="filterSiklus5('fakultas', this)">Fakultas</button>
                    <button type="button" class="btn btn-outline-primary" onclick="filterSiklus5('prodi', this)">Program Studi</button>
                </div>
            </div>

            <?php if (empty($s5_rtm)): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-chat-square-quote fs-1 text-muted opacity-50 mb-2 d-block"></i>
                Belum ada data Rapat Tinjauan Manajemen untuk periode <?= e($selected_periode) ?>.
            </div>
            <?php else: ?>
            <div class="row g-4" id="s5RtmGrid">
                <?php foreach ($s5_rtm as $rtm): ?>
                <?php 
                $photos = !empty($rtm['foto_kegiatan']) ? json_decode($rtm['foto_kegiatan'], true) : [];
                $t_cls = 'rtm-' . $rtm['tingkat'];
                ?>
                <div class="col-lg-6 s5-item <?= $t_cls ?>">
                    <div class="card h-100 p-4 border rounded-4 shadow-none bg-light" style="border-left: 4px solid <?= $rtm['tingkat'] === 'universitas' ? '#DC2626' : ($rtm['tingkat'] === 'fakultas' ? 'var(--primary)' : 'var(--purple)') ?> !important;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <?php if ($rtm['tingkat'] === 'universitas'): ?>
                            <span class="badge bg-danger px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-building me-1"></i>Tingkat Universitas</span>
                            <?php elseif ($rtm['tingkat'] === 'fakultas'): ?>
                            <span class="badge bg-primary px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-diagram-3 me-1"></i>Tingkat Fakultas</span>
                            <?php else: ?>
                            <span class="badge bg-purple px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-mortarboard me-1"></i>Tingkat Program Studi</span>
                            <?php endif; ?>
                            <span class="text-muted small fw-bold">Periode <?= e($rtm['periode']) ?></span>
                        </div>

                        <h5 class="fw-bold mb-1" style="color:var(--navy);font-size:1.1rem;line-height:1.4;"><?= e($rtm['judul']) ?></h5>

                        <div class="text-muted small mb-3">
                            <?php if ($rtm['tingkat'] === 'universitas'): ?>
                            <strong>Universitas Katolik Soegijapranata</strong>
                            <?php elseif ($rtm['tingkat'] === 'fakultas'): ?>
                            <strong><?= e($rtm['fakultas']) ?></strong>
                            <?php else: ?>
                            <strong><?= e($rtm['prodi']) ?></strong> (Fakultas <?= e($rtm['fakultas']) ?>)
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($rtm['notulensi'])): ?>
                        <div class="p-3 bg-white rounded-3 border mb-3 small" style="line-height:1.6;color:#334155;">
                            <div class="fw-bold text-dark mb-1 small"><i class="bi bi-file-text me-1 text-primary"></i>Ringkasan Notulensi:</div>
                            <?= nl2br(e($rtm['notulensi'])) ?>
                        </div>
                        <?php endif; ?>

                        <!-- Berkas Notulensi & Daftar Hadir -->
                        <div class="d-flex flex-wrap gap-2 mb-3">
                            <?php if (!empty($rtm['file_notulensi'])): ?>
                            <a href="<?= SITE_URL ?>/uploads/<?= e($rtm['file_notulensi']) ?>" target="_blank" download class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" style="font-size:0.8rem;">
                                <i class="bi bi-file-earmark-text-fill me-1"></i> Berkas Notulensi
                            </a>
                            <?php endif; ?>

                            <?php if (!empty($rtm['file_daftar_hadir'])): ?>
                            <a href="<?= SITE_URL ?>/uploads/<?= e($rtm['file_daftar_hadir']) ?>" target="_blank" download class="btn btn-sm btn-outline-success rounded-pill px-3 py-1" style="font-size:0.8rem;">
                                <i class="bi bi-card-checklist me-1"></i> Daftar Hadir Peserta
                            </a>
                            <?php endif; ?>
                        </div>

                        <!-- Galeri Foto Kegiatan RTM -->
                        <?php if (!empty($photos)): ?>
                        <div class="mt-auto pt-2 border-top">
                            <div class="small fw-bold text-muted mb-2"><i class="bi bi-images me-1"></i>Dokumentasi Foto RTM (<?= count($photos) ?>):</div>
                            <div class="d-flex gap-2 overflow-auto pb-1" style="scrollbar-width:none;">
                                <?php foreach ($photos as $pimg): ?>
                                <div style="width:70px;height:55px;border-radius:8px;overflow:hidden;flex-shrink:0;cursor:pointer;" onclick="openPhotoModal('<?= SITE_URL ?>/uploads/<?= e($pimg) ?>', '<?= e($rtm['judul']) ?>', '<?= e($rtm['tingkat']) ?>')">
                                    <img src="<?= SITE_URL ?>/uploads/<?= e($pimg) ?>" alt="Foto RTM" style="width:100%;height:100%;object-fit:cover;">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <?php endif; ?>

    </div>
</section>

<!-- Modal Lightbox Pratinjau Foto -->
<div class="modal fade" id="photoLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header border-0 bg-dark text-white p-3">
                <div class="min-w-0">
                    <h6 class="modal-title fw-bold text-truncate" id="photoModalTitle">Pratinjau Foto</h6>
                    <small class="text-white-50" id="photoModalSub"></small>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0 bg-black text-center" style="max-height:80vh;display:flex;align-items:center;justify-content:center;">
                <img id="photoModalImg" src="" alt="Pratinjau Foto" style="max-width:100%;max-height:75vh;object-fit:contain;">
            </div>
        </div>
    </div>
</div>

<script>
function openPhotoModal(imgSrc, title, subtitle) {
    document.getElementById('photoModalImg').src = imgSrc;
    document.getElementById('photoModalTitle').textContent = title || 'Dokumentasi Kegiatan AMI';
    document.getElementById('photoModalSub').textContent = subtitle || '';
    
    var modalEl = document.getElementById('photoLightboxModal');
    var modal = new bootstrap.Modal(modalEl);
    modal.show();
}

// Filter Tingkat Siklus 4
function filterSiklus4(type, btn) {
    var btns = document.querySelectorAll('#filterSiklus4BtnGroup .btn');
    btns.forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');

    var items = document.querySelectorAll('.s4-item');
    items.forEach(function(item) {
        if (type === 'all') {
            item.style.display = '';
        } else if (type === 'fakultas') {
            item.style.display = item.classList.contains('tingkat-fakultas') ? '' : 'none';
        } else if (type === 'prodi') {
            item.style.display = item.classList.contains('tingkat-prodi') ? '' : 'none';
        }
    });
}

// Filter Tingkat Siklus 5
function filterSiklus5(type, btn) {
    var btns = document.querySelectorAll('#filterSiklus5BtnGroup .btn');
    btns.forEach(function(b) { b.classList.remove('active'); });
    btn.classList.add('active');

    var items = document.querySelectorAll('.s5-item');
    items.forEach(function(item) {
        if (type === 'all') {
            item.style.display = '';
        } else if (type === 'universitas') {
            item.style.display = item.classList.contains('rtm-universitas') ? '' : 'none';
        } else if (type === 'fakultas') {
            item.style.display = item.classList.contains('rtm-fakultas') ? '' : 'none';
        } else if (type === 'prodi') {
            item.style.display = item.classList.contains('rtm-prodi') ? '' : 'none';
        }
    });
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
