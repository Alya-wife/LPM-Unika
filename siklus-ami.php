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

$selected_tingkat = trim($_GET['tingkat'] ?? 'all');
if (!in_array($selected_tingkat, ['all', 'universitas', 'fakultas', 'prodi'])) {
    $selected_tingkat = 'all';
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

/* Card Rangkaian Dokumen (Fix Overflow & Responsive) */
.doc-ami-card {
    background: #ffffff;
    border: 1.5px solid #E2E8F0;
    border-radius: 12px;
    padding: 1.15rem 1.25rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    min-width: 0;
    overflow: hidden;
    transition: all 0.2s ease;
}
.doc-ami-card:hover {
    border-color: #CBD5E1;
    box-shadow: 0 6px 18px rgba(0,0,0,0.05);
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
.doc-ami-info {
    min-width: 0;
    flex: 1 1 auto;
    overflow: hidden;
}
.doc-ami-title {
    font-weight: 700;
    color: var(--navy);
    font-size: 0.93rem;
    word-break: break-word;
    overflow-wrap: break-word;
    line-height: 1.35;
    margin-bottom: 0.3rem;
}
.doc-ami-actions {
    display: flex;
    align-items: center;
    gap: 0.4rem;
    flex-shrink: 0;
}
@media (max-width: 576px) {
    .doc-ami-card {
        flex-direction: column;
        align-items: stretch;
        gap: 0.75rem;
    }
    .doc-ami-actions {
        justify-content: flex-end;
    }
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
    flex-wrap: wrap;
}
.periode-pill {
    padding: 0.45rem 1.15rem;
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

/* Button Filter Tingkat */
.btn-filter-tingkat {
    border: 1.5px solid #E2E8F0;
    background: #F8FAFC;
    color: #475569;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 0.45rem 1rem;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.2s ease;
    display: inline-flex;
    align-items: center;
    text-decoration: none !important;
}
.btn-filter-tingkat:hover {
    background: #EDF2F7;
    color: var(--navy);
    border-color: #CBD5E1;
}
.btn-filter-tingkat.active {
    background: var(--navy);
    color: #ffffff;
    border-color: var(--navy);
    box-shadow: 0 2px 8px rgba(11,31,68,0.18);
}

/* Card Audit Lapangan (Siklus 4) & RTM (Siklus 5) Redesign */
.ami-audit-card {
    background: #ffffff;
    border: 1.5px solid #E2E8F0;
    border-radius: 16px;
    overflow: hidden;
    display: flex;
    flex-direction: column;
    height: 100%;
    transition: all 0.25s ease;
    box-shadow: 0 4px 12px rgba(0,0,0,0.03);
}
.ami-audit-card:hover {
    border-color: #CBD5E1;
    box-shadow: 0 10px 25px rgba(0,0,0,0.08);
    transform: translateY(-3px);
}
.ami-card-cover {
    height: 200px;
    position: relative;
    background: #0B1F44;
    cursor: pointer;
    overflow: hidden;
}
.ami-card-cover img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.4s ease;
}
.ami-card-cover:hover img {
    transform: scale(1.05);
}
.ami-card-cover-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(to top, rgba(11,31,68,0.7) 0%, transparent 60%);
    display: flex;
    align-items: flex-end;
    padding: 0.85rem 1rem;
    color: #ffffff;
    pointer-events: none;
}
.ami-card-body {
    padding: 1.25rem 1.25rem 0.75rem;
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
}
.ami-card-footer {
    padding: 0.85rem 1.25rem 1.25rem;
    background: #FAFAFA;
    border-top: 1px solid #F1F5F9;
}
.ami-attach-row {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 10px;
    padding: 0.55rem 0.75rem;
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.75rem;
    transition: all 0.15s ease;
}
.ami-attach-row:hover {
    border-color: #CBD5E1;
    background: #F8FAFC;
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

        <!-- Panel Filter Terpadu: Periode Terlebih Dahulu, Lalu Filter Tingkat -->
        <div class="card p-3 p-md-4 border-0 shadow-sm rounded-4 bg-white mb-4">
            <!-- 1. Filter Periode Audit -->
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 <?= ($active_siklus === 4 || $active_siklus === 5) ? 'pb-3 border-bottom' : '' ?>">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-primary rounded-pill px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-1-circle-fill me-1"></i>Langkah 1</span>
                        <span class="text-uppercase fw-bold text-dark small" style="letter-spacing:0.5px;">Pilih Periode Audit:</span>
                    </div>
                    <div class="periode-filter-box">
                        <?php foreach ($periodes as $p): ?>
                        <a href="?siklus=<?= $active_siklus ?>&periode=<?= urlencode($p['nama_periode']) ?><?= $selected_tingkat !== 'all' ? '&tingkat='.urlencode($selected_tingkat) : '' ?>#siklusContent" class="periode-pill <?= $selected_periode === $p['nama_periode'] ? 'active' : '' ?>">
                            <i class="bi bi-calendar2-check me-1"></i><?= e($p['nama_periode']) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-light text-dark border px-3 py-2 shadow-sm" style="font-size:0.85rem;">
                        <i class="bi bi-calendar3 text-primary me-1"></i> Periode Terpilih: <strong><?= e($selected_periode) ?></strong>
                    </span>
                </div>
            </div>

            <!-- 2. Filter Tingkat Unit (Khusus Siklus 4 & 5) -->
            <?php if ($active_siklus === 4 || $active_siklus === 5): ?>
            <div class="pt-3 d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <div class="d-flex align-items-center gap-2 mb-2">
                        <span class="badge bg-purple rounded-pill px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-2-circle-fill me-1"></i>Langkah 2</span>
                        <span class="text-uppercase fw-bold text-dark small" style="letter-spacing:0.5px;">Filter Tingkat Satuan Kerja:</span>
                    </div>
                    <div class="d-flex flex-wrap gap-2" id="tingkatFilterBox">
                        <button type="button" class="btn-filter-tingkat <?= $selected_tingkat === 'all' ? 'active' : '' ?>" data-tingkat="all" onclick="applyTingkatFilter('all', this)">
                            <i class="bi bi-grid-fill me-1"></i> Semua Tingkat
                        </button>
                        <?php if ($active_siklus === 5): ?>
                        <button type="button" class="btn-filter-tingkat <?= $selected_tingkat === 'universitas' ? 'active' : '' ?>" data-tingkat="universitas" onclick="applyTingkatFilter('universitas', this)">
                            <i class="bi bi-building me-1 text-danger"></i> Tingkat Universitas
                        </button>
                        <?php endif; ?>
                        <button type="button" class="btn-filter-tingkat <?= $selected_tingkat === 'fakultas' ? 'active' : '' ?>" data-tingkat="fakultas" onclick="applyTingkatFilter('fakultas', this)">
                            <i class="bi bi-diagram-3 me-1 text-primary"></i> Tingkat Fakultas
                        </button>
                        <button type="button" class="btn-filter-tingkat <?= $selected_tingkat === 'prodi' ? 'active' : '' ?>" data-tingkat="prodi" onclick="applyTingkatFilter('prodi', this)">
                            <i class="bi bi-mortarboard me-1 text-purple"></i> Tingkat Program Studi
                        </button>
                    </div>
                </div>

                <div class="text-muted small fst-italic">
                    <i class="bi bi-info-circle me-1"></i>Menampilkan dokumen audit sesuai tingkatan yang dipilih
                </div>
            </div>
            <?php endif; ?>
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
                            <div class="doc-ami-info">
                                <div class="doc-ami-title"><?= e($keg['judul']) ?></div>
                                <div class="text-muted small d-flex align-items-center gap-2 flex-wrap">
                                    <span><i class="bi bi-hdd me-1"></i><?= e($keg['file_size'] ?? 'Softfile') ?></span>
                                    <span>•</span>
                                    <span class="text-truncate" style="max-width:220px;"><?= basename($keg['file_dokumen']) ?></span>
                                </div>
                                <?php if (!empty($keg['keterangan'])): ?>
                                <p class="text-muted small mb-0 mt-1" style="font-size:0.8rem;line-height:1.4;"><?= e($keg['keterangan']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="doc-ami-actions">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" onclick="openDocModal('<?= SITE_URL ?>/uploads/<?= e($keg['file_dokumen']) ?>', '<?= e(addslashes($keg['judul'])) ?>')">
                                    <i class="bi bi-eye me-1"></i> Pratinjau
                                </button>
                                <a href="<?= SITE_URL ?>/uploads/<?= e($keg['file_dokumen']) ?>" target="_blank" download class="btn btn-sm btn-primary rounded-pill px-3 py-1">
                                    <i class="bi bi-download me-1"></i> Unduh
                                </a>
                            </div>
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
                                <div class="doc-ami-info">
                                    <div class="doc-ami-title"><?= e($p['judul']) ?></div>
                                    <div class="text-muted small text-truncate"><?= basename($p['file_dokumen']) ?> (<?= e($p['file_size'] ?? 'PDF') ?>)</div>
                                </div>
                                <div class="doc-ami-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" onclick="openDocModal('<?= SITE_URL ?>/uploads/<?= e($p['file_dokumen']) ?>', '<?= e(addslashes($p['judul'])) ?>')">
                                        <i class="bi bi-eye me-1"></i> Pratinjau
                                    </button>
                                    <a href="<?= SITE_URL ?>/uploads/<?= e($p['file_dokumen']) ?>" target="_blank" download class="btn btn-sm btn-primary rounded-pill px-3 py-1">
                                        <i class="bi bi-download me-1"></i> Unduh
                                    </a>
                                </div>
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
                                <div class="doc-ami-info">
                                    <div class="doc-ami-title"><?= e($j['judul']) ?></div>
                                    <div class="text-muted small text-truncate"><?= basename($j['file_dokumen']) ?> (<?= e($j['file_size'] ?? 'PDF') ?>)</div>
                                </div>
                                <div class="doc-ami-actions">
                                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1" onclick="openDocModal('<?= SITE_URL ?>/uploads/<?= e($j['file_dokumen']) ?>', '<?= e(addslashes($j['judul'])) ?>')">
                                        <i class="bi bi-eye me-1"></i> Pratinjau
                                    </button>
                                    <a href="<?= SITE_URL ?>/uploads/<?= e($j['file_dokumen']) ?>" target="_blank" download class="btn btn-sm btn-success text-white rounded-pill px-3 py-1">
                                        <i class="bi bi-download me-1"></i> Unduh
                                    </a>
                                </div>
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
                        <div class="text-muted small">Dokumentasi hasil audit lapangan, berkas berita acara, presensi kehadiran, undangan, dan galeri foto kegiatan.</div>
                    </div>
                    <div class="btn-group btn-group-sm" role="group" id="filterSiklus4BtnGroup">
                        <button type="button" class="btn btn-outline-primary <?= $selected_tingkat === 'all' ? 'active' : '' ?>" data-tingkat="all" onclick="applyTingkatFilter('all', this)">Semua (<?= count($s4_dokumentasi) ?>)</button>
                        <button type="button" class="btn btn-outline-primary <?= $selected_tingkat === 'fakultas' ? 'active' : '' ?>" data-tingkat="fakultas" onclick="applyTingkatFilter('fakultas', this)">Tingkat Fakultas</button>
                        <button type="button" class="btn btn-outline-primary <?= $selected_tingkat === 'prodi' ? 'active' : '' ?>" data-tingkat="prodi" onclick="applyTingkatFilter('prodi', this)">Tingkat Program Studi</button>
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
                    $main_photo = !empty($photos) ? $photos[0] : '';
                    $tingkat_cls = ($item['tingkat'] === 'prodi') ? 'tingkat-prodi' : 'tingkat-fakultas';
                    ?>
                    <div class="col-lg-6 s4-item <?= $tingkat_cls ?>" data-tingkat="<?= e($item['tingkat']) ?>">
                        <div class="ami-audit-card" style="border-top: 4px solid <?= $item['tingkat'] === 'prodi' ? 'var(--purple)' : 'var(--primary)' ?> !important;">
                            <!-- 1. Foto Kegiatan di Bagian Atas -->
                            <?php if (!empty($main_photo)): ?>
                            <div class="ami-card-cover" onclick="openPhotoModal('<?= SITE_URL ?>/uploads/<?= e($main_photo) ?>', '<?= e(addslashes($item['judul'])) ?>', '<?= e(addslashes($item['fakultas'])) ?>')">
                                <img src="<?= SITE_URL ?>/uploads/<?= e($main_photo) ?>" alt="Dokumentasi Audit Lapangan">
                                <div class="ami-card-cover-overlay">
                                    <span class="badge bg-dark bg-opacity-75"><i class="bi bi-zoom-in me-1"></i> Klik untuk memperbesar foto</span>
                                </div>
                                <?php if (count($photos) > 1): ?>
                                <span class="position-absolute top-0 end-0 m-3 badge bg-dark bg-opacity-75 px-2 py-1 shadow-sm">
                                    <i class="bi bi-images me-1 text-warning"></i> <?= count($photos) ?> Foto
                                </span>
                                <?php endif; ?>
                            </div>
                            <?php else: ?>
                            <div class="ami-card-cover d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg, #0B1F44 0%, #1E3A8A 100%);">
                                <div class="text-center text-white-50">
                                    <i class="bi bi-journal-check fs-1 text-white opacity-50 mb-1 d-block"></i>
                                    <span class="small">Dokumentasi Audit Lapangan</span>
                                </div>
                            </div>
                            <?php endif; ?>

                            <!-- 2. Judul & Informasi Unit di Tengah -->
                            <div class="ami-card-body">
                                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                    <span class="badge <?= $item['tingkat'] === 'prodi' ? 'bg-purple' : 'bg-primary' ?> px-2 py-1" style="font-size:0.75rem;">
                                        <?= $item['tingkat'] === 'prodi' ? '<i class="bi bi-mortarboard me-1"></i>Tingkat Program Studi' : '<i class="bi bi-diagram-3 me-1"></i>Tingkat Fakultas' ?>
                                    </span>
                                    <span class="text-muted small fw-bold"><i class="bi bi-calendar-event me-1"></i>Periode <?= e($item['periode']) ?></span>
                                </div>

                                <h5 class="fw-bold mb-2" style="color:var(--navy);font-size:1.05rem;line-height:1.45;"><?= e($item['judul']) ?></h5>

                                <div class="text-muted small mb-1">
                                    <strong><i class="bi bi-building me-1"></i><?= e($item['fakultas']) ?></strong>
                                    <?php if (!empty($item['prodi'])): ?>
                                    <span> • <?= e($item['prodi']) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>

                            <!-- 3. Lampiran Dokumen di Bagian Bawah -->
                            <div class="ami-card-footer">
                                <div class="small fw-bold text-muted mb-2"><i class="bi bi-paperclip me-1"></i>Lampiran Dokumen Resmi:</div>
                                <div class="vstack gap-2">
                                    <!-- Berita Acara -->
                                    <?php if (!empty($item['file_berita_acara'])): ?>
                                    <div class="ami-attach-row">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <i class="bi bi-file-earmark-check-fill text-primary fs-5 flex-shrink-0"></i>
                                            <div class="min-w-0">
                                                <div class="fw-bold small text-dark text-truncate">Berita Acara</div>
                                                <div class="text-muted" style="font-size:0.72rem;"><?= basename($item['file_berita_acara']) ?></div>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:0.75rem;" onclick="openDocModal('<?= SITE_URL ?>/uploads/<?= e($item['file_berita_acara']) ?>', 'Berita Acara - <?= e(addslashes($item['judul'])) ?>')">
                                                <i class="bi bi-eye me-1"></i> Pratinjau
                                            </button>
                                            <a href="<?= SITE_URL ?>/uploads/<?= e($item['file_berita_acara']) ?>" download class="btn btn-sm btn-primary py-1 px-2" style="font-size:0.75rem;" title="Unduh Berita Acara">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Daftar Hadir -->
                                    <?php if (!empty($item['file_daftar_hadir'])): ?>
                                    <div class="ami-attach-row">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <i class="bi bi-card-checklist text-success fs-5 flex-shrink-0"></i>
                                            <div class="min-w-0">
                                                <div class="fw-bold small text-dark text-truncate">Daftar Hadir</div>
                                                <div class="text-muted" style="font-size:0.72rem;"><?= basename($item['file_daftar_hadir']) ?></div>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <button type="button" class="btn btn-sm btn-outline-success py-1 px-2" style="font-size:0.75rem;" onclick="openDocModal('<?= SITE_URL ?>/uploads/<?= e($item['file_daftar_hadir']) ?>', 'Daftar Hadir - <?= e(addslashes($item['judul'])) ?>')">
                                                <i class="bi bi-eye me-1"></i> Pratinjau
                                            </button>
                                            <a href="<?= SITE_URL ?>/uploads/<?= e($item['file_daftar_hadir']) ?>" download class="btn btn-sm btn-success text-white py-1 px-2" style="font-size:0.75rem;" title="Unduh Daftar Hadir">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Undangan -->
                                    <?php if (!empty($item['file_undangan'])): ?>
                                    <div class="ami-attach-row">
                                        <div class="d-flex align-items-center gap-2 min-w-0">
                                            <i class="bi bi-envelope-paper-fill text-purple fs-5 flex-shrink-0"></i>
                                            <div class="min-w-0">
                                                <div class="fw-bold small text-dark text-truncate">Undangan</div>
                                                <div class="text-muted" style="font-size:0.72rem;"><?= basename($item['file_undangan']) ?></div>
                                            </div>
                                        </div>
                                        <div class="d-flex gap-1 flex-shrink-0">
                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size:0.75rem;" onclick="openDocModal('<?= SITE_URL ?>/uploads/<?= e($item['file_undangan']) ?>', 'Undangan - <?= e(addslashes($item['judul'])) ?>')">
                                                <i class="bi bi-eye me-1"></i> Pratinjau
                                            </button>
                                            <a href="<?= SITE_URL ?>/uploads/<?= e($item['file_undangan']) ?>" download class="btn btn-sm btn-secondary py-1 px-2" style="font-size:0.75rem;" title="Unduh Undangan">
                                                <i class="bi bi-download"></i>
                                            </a>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if (empty($item['file_berita_acara']) && empty($item['file_daftar_hadir']) && empty($item['file_undangan'])): ?>
                                    <div class="text-muted small text-center py-2 bg-white rounded-3 border">Belum ada lampiran dokumen</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
                <div id="s4EmptyFilterNotice" class="text-center py-5 text-muted" style="display:none;">
                    <i class="bi bi-filter-circle fs-1 text-muted opacity-50 mb-2 d-block"></i>
                    Tidak ada dokumen audit lapangan untuk tingkatan yang dipilih pada periode ini.
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
                    <button type="button" class="btn btn-outline-primary <?= $selected_tingkat === 'all' ? 'active' : '' ?>" data-tingkat="all" onclick="applyTingkatFilter('all', this)">Semua (<?= count($s5_rtm) ?>)</button>
                    <button type="button" class="btn btn-outline-primary <?= $selected_tingkat === 'universitas' ? 'active' : '' ?>" data-tingkat="universitas" onclick="applyTingkatFilter('universitas', this)">Universitas</button>
                    <button type="button" class="btn btn-outline-primary <?= $selected_tingkat === 'fakultas' ? 'active' : '' ?>" data-tingkat="fakultas" onclick="applyTingkatFilter('fakultas', this)">Fakultas</button>
                    <button type="button" class="btn btn-outline-primary <?= $selected_tingkat === 'prodi' ? 'active' : '' ?>" data-tingkat="prodi" onclick="applyTingkatFilter('prodi', this)">Program Studi</button>
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
                $main_photo = !empty($photos) ? $photos[0] : '';
                $t_cls = 'rtm-' . $rtm['tingkat'];
                ?>
                <div class="col-lg-6 s5-item <?= $t_cls ?>" data-tingkat="<?= e($rtm['tingkat']) ?>">
                    <div class="ami-audit-card" style="border-top: 4px solid <?= $rtm['tingkat'] === 'universitas' ? '#DC2626' : ($rtm['tingkat'] === 'fakultas' ? 'var(--primary)' : 'var(--purple)') ?> !important;">
                        <!-- 1. Foto Kegiatan di Bagian Atas -->
                        <?php if (!empty($main_photo)): ?>
                        <div class="ami-card-cover" onclick="openPhotoModal('<?= SITE_URL ?>/uploads/<?= e($main_photo) ?>', '<?= e(addslashes($rtm['judul'])) ?>', '<?= e(addslashes($rtm['fakultas'] ?: 'Universitas')) ?>')">
                            <img src="<?= SITE_URL ?>/uploads/<?= e($main_photo) ?>" alt="Dokumentasi RTM">
                            <div class="ami-card-cover-overlay">
                                <span class="badge bg-dark bg-opacity-75"><i class="bi bi-zoom-in me-1"></i> Klik untuk memperbesar foto</span>
                            </div>
                            <?php if (count($photos) > 1): ?>
                            <span class="position-absolute top-0 end-0 m-3 badge bg-dark bg-opacity-75 px-2 py-1 shadow-sm">
                                <i class="bi bi-images me-1 text-warning"></i> <?= count($photos) ?> Foto
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php else: ?>
                        <div class="ami-card-cover d-flex align-items-center justify-content-center" style="background:linear-gradient(135deg, #0B1F44 0%, #3B0764 100%);">
                            <div class="text-center text-white-50">
                                <i class="bi bi-people fs-1 text-white opacity-50 mb-1 d-block"></i>
                                <span class="small">Dokumentasi RTM</span>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- 2. Judul & Informasi Unit di Tengah -->
                        <div class="ami-card-body">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <?php if ($rtm['tingkat'] === 'universitas'): ?>
                                <span class="badge bg-danger px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-building me-1"></i>Tingkat Universitas</span>
                                <?php elseif ($rtm['tingkat'] === 'fakultas'): ?>
                                <span class="badge bg-primary px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-diagram-3 me-1"></i>Tingkat Fakultas</span>
                                <?php else: ?>
                                <span class="badge bg-purple px-2 py-1" style="font-size:0.75rem;"><i class="bi bi-mortarboard me-1"></i>Tingkat Program Studi</span>
                                <?php endif; ?>
                                <span class="text-muted small fw-bold"><i class="bi bi-calendar-event me-1"></i>Periode <?= e($rtm['periode']) ?></span>
                            </div>

                            <h5 class="fw-bold mb-2" style="color:var(--navy);font-size:1.05rem;line-height:1.45;"><?= e($rtm['judul']) ?></h5>

                            <div class="text-muted small mb-1">
                                <?php if ($rtm['tingkat'] === 'universitas'): ?>
                                <strong><i class="bi bi-building me-1"></i>Universitas Katolik Soegijapranata</strong>
                                <?php elseif ($rtm['tingkat'] === 'fakultas'): ?>
                                <strong><i class="bi bi-building me-1"></i><?= e($rtm['fakultas']) ?></strong>
                                <?php else: ?>
                                <strong><i class="bi bi-mortarboard me-1"></i><?= e($rtm['prodi']) ?></strong> (Fakultas <?= e($rtm['fakultas']) ?>)
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- 3. Lampiran Dokumen di Bagian Bawah -->
                        <div class="ami-card-footer">
                            <div class="small fw-bold text-muted mb-2"><i class="bi bi-paperclip me-1"></i>Lampiran Dokumen Resmi:</div>
                            <div class="vstack gap-2">
                                <!-- Notulensi -->
                                <?php if (!empty($rtm['file_notulensi'])): ?>
                                <div class="ami-attach-row">
                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="bi bi-file-earmark-text-fill text-primary fs-5 flex-shrink-0"></i>
                                        <div class="min-w-0">
                                            <div class="fw-bold small text-dark text-truncate">Notulensi</div>
                                            <div class="text-muted" style="font-size:0.72rem;"><?= basename($rtm['file_notulensi']) ?></div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0">
                                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:0.75rem;" onclick="openDocModal('<?= SITE_URL ?>/uploads/<?= e($rtm['file_notulensi']) ?>', 'Notulensi - <?= e(addslashes($rtm['judul'])) ?>')">
                                            <i class="bi bi-eye me-1"></i> Pratinjau
                                        </button>
                                        <a href="<?= SITE_URL ?>/uploads/<?= e($rtm['file_notulensi']) ?>" download class="btn btn-sm btn-primary py-1 px-2" style="font-size:0.75rem;" title="Unduh Notulensi">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Daftar Hadir -->
                                <?php if (!empty($rtm['file_daftar_hadir'])): ?>
                                <div class="ami-attach-row">
                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="bi bi-card-checklist text-success fs-5 flex-shrink-0"></i>
                                        <div class="min-w-0">
                                            <div class="fw-bold small text-dark text-truncate">Daftar Hadir</div>
                                            <div class="text-muted" style="font-size:0.72rem;"><?= basename($rtm['file_daftar_hadir']) ?></div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0">
                                        <button type="button" class="btn btn-sm btn-outline-success py-1 px-2" style="font-size:0.75rem;" onclick="openDocModal('<?= SITE_URL ?>/uploads/<?= e($rtm['file_daftar_hadir']) ?>', 'Daftar Hadir - <?= e(addslashes($rtm['judul'])) ?>')">
                                            <i class="bi bi-eye me-1"></i> Pratinjau
                                        </button>
                                        <a href="<?= SITE_URL ?>/uploads/<?= e($rtm['file_daftar_hadir']) ?>" download class="btn btn-sm btn-success text-white py-1 px-2" style="font-size:0.75rem;" title="Unduh Daftar Hadir">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <!-- Undangan -->
                                <?php if (!empty($rtm['file_undangan'])): ?>
                                <div class="ami-attach-row">
                                    <div class="d-flex align-items-center gap-2 min-w-0">
                                        <i class="bi bi-envelope-paper-fill text-purple fs-5 flex-shrink-0"></i>
                                        <div class="min-w-0">
                                            <div class="fw-bold small text-dark text-truncate">Undangan</div>
                                            <div class="text-muted" style="font-size:0.72rem;"><?= basename($rtm['file_undangan']) ?></div>
                                        </div>
                                    </div>
                                    <div class="d-flex gap-1 flex-shrink-0">
                                        <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size:0.75rem;" onclick="openDocModal('<?= SITE_URL ?>/uploads/<?= e($rtm['file_undangan']) ?>', 'Undangan - <?= e(addslashes($rtm['judul'])) ?>')">
                                            <i class="bi bi-eye me-1"></i> Pratinjau
                                        </button>
                                        <a href="<?= SITE_URL ?>/uploads/<?= e($rtm['file_undangan']) ?>" download class="btn btn-sm btn-secondary py-1 px-2" style="font-size:0.75rem;" title="Unduh Undangan">
                                            <i class="bi bi-download"></i>
                                        </a>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if (empty($rtm['file_notulensi']) && empty($rtm['file_daftar_hadir']) && empty($rtm['file_undangan'])): ?>
                                <div class="text-muted small text-center py-2 bg-white rounded-3 border">Belum ada lampiran dokumen</div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <div id="s5EmptyFilterNotice" class="text-center py-5 text-muted" style="display:none;">
                <i class="bi bi-filter-circle fs-1 text-muted opacity-50 mb-2 d-block"></i>
                Tidak ada data RTM untuk tingkatan yang dipilih pada periode ini.
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

<!-- Modal Pratinjau Dokumen (Preview Sebelum Unduh) -->
<div class="modal fade" id="docPreviewModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">
            <div class="modal-header bg-dark text-white p-3 border-0">
                <div class="d-flex align-items-center gap-2 min-w-0 me-3">
                    <div style="width:36px;height:36px;border-radius:8px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                        <i class="bi bi-file-earmark-text fs-5 text-warning"></i>
                    </div>
                    <div class="min-w-0">
                        <h6 class="modal-title fw-bold text-truncate mb-0" id="docModalTitle">Pratinjau Dokumen</h6>
                        <small class="text-white-50 text-truncate d-block" id="docModalSubtitle"></small>
                    </div>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto flex-shrink-0">
                    <a href="#" id="docModalNewTabBtn" target="_blank" class="btn btn-sm btn-outline-light d-none d-sm-inline-flex align-items-center">
                        <i class="bi bi-box-arrow-up-right me-1"></i> Buka di Tab Baru
                    </a>
                    <a href="#" id="docModalDownloadBtn" download class="btn btn-sm btn-warning text-dark fw-bold d-inline-flex align-items-center">
                        <i class="bi bi-download me-1"></i> Unduh Berkas
                    </a>
                    <button type="button" class="btn-close btn-close-white ms-2" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0 bg-light" style="min-height:65vh;max-height:82vh;display:flex;flex-direction:column;position:relative;">
                <div id="docModalLoading" class="position-absolute top-50 start-50 translate-middle text-center text-muted" style="z-index:5;">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <div class="small fw-semibold">Memuat pratinjau dokumen...</div>
                </div>
                <!-- Iframe Viewer for PDF -->
                <iframe id="docModalIframe" src="" style="width:100%;height:75vh;border:none;flex-grow:1;display:block;" onload="document.getElementById('docModalLoading').style.display='none';"></iframe>
                <!-- Fallback container for Word docx / doc or unsupported files -->
                <div id="docModalFallback" class="p-5 text-center my-auto" style="display:none;">
                    <div style="width:80px;height:80px;border-radius:20px;background:#EFF6FF;color:#2563EB;display:flex;align-items:center;justify-content:center;font-size:2.5rem;margin:0 auto 1.5rem;">
                        <i class="bi bi-file-earmark-word"></i>
                    </div>
                    <h5 class="fw-bold mb-2" style="color:var(--navy);">Dokumen Tidak Dapat Dipratinjau Langsung di Browser</h5>
                    <p class="text-muted small mx-auto mb-4" style="max-width:520px;">
                        Berkas ini berformat dokumen (seperti Microsoft Word atau arsip). Silakan unduh dokumen untuk membuka dan membaca berkas secara utuh di perangkat Anda.
                    </p>
                    <a href="#" id="docModalFallbackDownload" download class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow-sm">
                        <i class="bi bi-download me-1"></i> Unduh Berkas Sekarang
                    </a>
                </div>
            </div>
            <div class="modal-footer bg-white border-top p-2 px-3 d-flex justify-content-between align-items-center">
                <span class="text-muted small"><i class="bi bi-info-circle me-1"></i> Gunakan tombol Unduh untuk menyimpan salinan dokumen ke komputer/perangkat Anda.</span>
                <button type="button" class="btn btn-sm btn-secondary px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
// Modal Lightbox Foto
function openPhotoModal(imgSrc, title, subtitle) {
    document.getElementById('photoModalImg').src = imgSrc;
    document.getElementById('photoModalTitle').textContent = title || 'Dokumentasi Kegiatan AMI';
    document.getElementById('photoModalSub').textContent = subtitle || '';
    
    var modalEl = document.getElementById('photoLightboxModal');
    var modal = new bootstrap.Modal(modalEl);
    modal.show();
}

// Modal Pratinjau Dokumen (Preview Sebelum Unduh)
function openDocModal(docUrl, docTitle, docSubtitle) {
    var iframe = document.getElementById('docModalIframe');
    var fallback = document.getElementById('docModalFallback');
    var loading = document.getElementById('docModalLoading');
    var titleEl = document.getElementById('docModalTitle');
    var subEl = document.getElementById('docModalSubtitle');
    var newTabBtn = document.getElementById('docModalNewTabBtn');
    var dlBtn = document.getElementById('docModalDownloadBtn');
    var fbDlBtn = document.getElementById('docModalFallbackDownload');

    titleEl.textContent = docTitle || 'Dokumen AMI';
    subEl.textContent = docSubtitle || docUrl.split('/').pop();
    newTabBtn.href = docUrl;
    dlBtn.href = docUrl;
    fbDlBtn.href = docUrl;

    var ext = docUrl.split('.').pop().toLowerCase();
    if (ext === 'doc' || ext === 'docx') {
        iframe.style.display = 'none';
        loading.style.display = 'none';
        fallback.style.display = 'block';
    } else {
        fallback.style.display = 'none';
        iframe.style.display = 'block';
        loading.style.display = 'block';
        iframe.src = docUrl;
    }

    var modal = new bootstrap.Modal(document.getElementById('docPreviewModal'));
    modal.show();
}

// Reset iframe on modal close
document.getElementById('docPreviewModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('docModalIframe').src = '';
});

// Filter Tingkat Satuan Kerja (Terhubung dengan Filter Periode & Button Filter)
function applyTingkatFilter(tingkat, btn) {
    // 1. Update status aktif pada tombol filter
    var allFilterBtns = document.querySelectorAll('#tingkatFilterBox .btn-filter-tingkat, #filterSiklus4BtnGroup .btn, #filterSiklus5BtnGroup .btn');
    allFilterBtns.forEach(function(b) {
        if (b.getAttribute('data-tingkat') === tingkat) {
            b.classList.add('active');
        } else {
            b.classList.remove('active');
        }
    });

    // 2. Filter Siklus 4 Items
    var s4Items = document.querySelectorAll('.s4-item');
    var s4VisibleCount = 0;
    s4Items.forEach(function(item) {
        var itemTingkat = item.getAttribute('data-tingkat');
        if (tingkat === 'all' || itemTingkat === tingkat) {
            item.style.display = '';
            s4VisibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    var s4Empty = document.getElementById('s4EmptyFilterNotice');
    if (s4Empty) {
        s4Empty.style.display = (s4VisibleCount === 0 && s4Items.length > 0) ? 'block' : 'none';
    }

    // 3. Filter Siklus 5 Items
    var s5Items = document.querySelectorAll('.s5-item');
    var s5VisibleCount = 0;
    s5Items.forEach(function(item) {
        var itemTingkat = item.getAttribute('data-tingkat');
        if (tingkat === 'all' || itemTingkat === tingkat) {
            item.style.display = '';
            s5VisibleCount++;
        } else {
            item.style.display = 'none';
        }
    });
    var s5Empty = document.getElementById('s5EmptyFilterNotice');
    if (s5Empty) {
        s5Empty.style.display = (s5VisibleCount === 0 && s5Items.length > 0) ? 'block' : 'none';
    }
}

// Inisialisasi filter awal jika ada parameter tingkat
document.addEventListener('DOMContentLoaded', function() {
    var initTingkat = '<?= e($selected_tingkat) ?>';
    if (initTingkat && initTingkat !== 'all') {
        applyTingkatFilter(initTingkat);
    }
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
