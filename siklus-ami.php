<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Siklus Audit Mutu Internal (AMI) – Siklus 1 s.d. 5';
$meta_desc  = 'Pelaksanaan Siklus 1 sampai 5 Audit Mutu Internal (AMI) Universitas Katolik Soegijapranata: Rangkaian Kegiatan, e-AMI, Audit Lapangan, dan RTM.';

$db = getDB();

$is_ajax = (isset($_GET['ajax']) && $_GET['ajax'] == '1') || 
           (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

if (!$is_ajax) {
    require_once __DIR__ . '/includes/header.php';
    require_once __DIR__ . '/includes/navbar.php';
}

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

<?php if (!$is_ajax): ?>
<style>
/* AJAX Top Loading Bar */
.ami-top-loader {
    position: absolute;
    top: 0;
    left: 0;
    width: 0;
    height: 3.5px;
    background: linear-gradient(90deg, #F59E0B, #2563EB, #7C3AED);
    z-index: 1000;
    transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1), opacity 0.2s ease;
    opacity: 0;
    box-shadow: 0 0 10px rgba(37, 99, 235, 0.6);
}
.ami-top-loader.loading {
    opacity: 1;
    width: 65%;
    transition: width 0.4s cubic-bezier(0.1, 0.7, 0.1, 1);
}
.ami-top-loader.finishing {
    opacity: 1;
    width: 100%;
    transition: width 0.15s ease-out;
}
.ami-top-loader.done {
    opacity: 0;
    transition: opacity 0.25s ease 0.1s;
}

/* AJAX Container Smooth Transition */
#amiDynamicContainer {
    transition: opacity 0.2s ease, transform 0.2s ease;
}
#amiDynamicContainer.ami-fade-out {
    opacity: 0.35;
    pointer-events: none;
    transform: translateY(4px);
}
#amiDynamicContainer.ami-fade-in {
    animation: amiDynamicFadeIn 0.28s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}
@keyframes amiDynamicFadeIn {
    0% {
        opacity: 0.35;
        transform: translateY(6px);
    }
    100% {
        opacity: 1;
        transform: translateY(0);
    }
}

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

/* Accordion & Table Gaya Akreditasi untuk Siklus 4 & 5 */
.accordion-lpm .accordion-button {
    font-weight: 700;
    color: var(--navy);
    transition: all 0.2s ease;
}
.accordion-lpm .accordion-button:not(.collapsed) {
    background-color: #f1f5f9 !important;
    color: var(--navy);
}
.accordion-lpm .accordion-button:focus {
    box-shadow: none;
}
.btn-filter-ami {
    background: #ffffff;
    border: 1px solid #CBD5E1;
    color: #475569;
    border-radius: 20px;
    padding: 0.35rem 0.85rem;
    font-size: 0.8rem;
    font-weight: 600;
    transition: all 0.15s ease;
    cursor: pointer;
}
.btn-filter-ami:hover {
    background: #F1F5F9;
    border-color: #94A3B8;
    color: #0F172A;
}
.btn-filter-ami.active {
    background: var(--navy) !important;
    border-color: var(--navy) !important;
    color: #ffffff !important;
    box-shadow: 0 2px 6px rgba(10,25,47,0.25);
}
.ami-thumb-box {
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
.ami-thumb-box:hover {
    transform: scale(1.05);
    box-shadow: 0 4px 12px rgba(0,0,0,0.15);
}
.ami-row {
    transition: background-color 0.15s ease;
}
.ami-row:hover {
    background-color: #F8FAFC;
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
/* AMI Card Carousel Slider */
.ami-card-carousel {
    position: relative;
    border-radius: 16px 16px 0 0;
    overflow: hidden;
}
.ami-card-carousel .carousel-inner {
    border-radius: 16px 16px 0 0;
}
.ami-card-carousel .ami-card-cover {
    border-radius: 0;
}
.ami-card-carousel .carousel-indicators {
    margin-bottom: 0.65rem;
    z-index: 4;
}
.ami-card-carousel .carousel-indicators [data-bs-target] {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    margin: 0 3px;
    background-color: #ffffff;
    opacity: 0.65;
    border: none;
    transition: all 0.25s ease;
}
.ami-card-carousel .carousel-indicators .active {
    width: 22px;
    border-radius: 10px;
    opacity: 1;
    background-color: #FFD54F;
}
.ami-card-carousel .carousel-control-prev,
.ami-card-carousel .carousel-control-next {
    width: 38px;
    height: 38px;
    top: 50%;
    transform: translateY(-50%);
    background: rgba(11, 31, 68, 0.65);
    border-radius: 50%;
    margin: 0 10px;
    opacity: 0;
    transition: all 0.25s ease;
    z-index: 4;
    border: 1px solid rgba(255,255,255,0.2);
}
.ami-card-carousel:hover .carousel-control-prev,
.ami-card-carousel:hover .carousel-control-next {
    opacity: 0.95;
}
.ami-card-carousel .carousel-control-prev:hover,
.ami-card-carousel .carousel-control-next:hover {
    background: rgba(11, 31, 68, 0.95);
    opacity: 1;
    transform: translateY(-50%) scale(1.08);
}
.ami-card-carousel .carousel-control-prev-icon,
.ami-card-carousel .carousel-control-next-icon {
    width: 16px;
    height: 16px;
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
<section class="py-5" style="background:#F8FAFC;min-height:70vh;position:relative;">
    <!-- Top Progress Loader for AJAX -->
    <div id="amiTopLoader" class="ami-top-loader"></div>
<?php endif; ?>

    <div class="container position-relative" id="amiDynamicContainer" data-periode="<?= e($selected_periode) ?>" data-siklus="<?= $active_siklus ?>" data-tingkat="<?= e($selected_tingkat) ?>">

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
                        <a href="?siklus=<?= $active_siklus ?>&periode=<?= urlencode($p['nama_periode']) ?><?= ($selected_tingkat !== 'all' && ($active_siklus === 4 || $active_siklus === 5)) ? '&tingkat='.urlencode($selected_tingkat) : '' ?>#siklusContent" class="periode-pill <?= $selected_periode === $p['nama_periode'] ? 'active' : '' ?>" data-periode="<?= e($p['nama_periode']) ?>">
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
                        <button type="button" class="btn-filter-tingkat <?= $selected_tingkat === 'universitas' ? 'active' : '' ?>" data-tingkat="universitas" onclick="applyTingkatFilter('universitas', this)">
                            <i class="bi bi-bank2 me-1 text-danger"></i> Tingkat Universitas
                        </button>
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
                <a href="?siklus=<?= $s_num ?>&periode=<?= urlencode($selected_periode) ?><?= ($selected_tingkat !== 'all' && ($s_num === 4 || $s_num === 5)) ? '&tingkat='.urlencode($selected_tingkat) : '' ?>#siklusContent" class="ami-stage-card <?= $is_curr ? 'active' : '' ?>" data-siklus="<?= $s_num ?>">
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

<?php
// Helper render baris tabel dokumen kegiatan AMI (Gaya Akreditasi)
if (!function_exists('renderAmiTableRow')) {
    function renderAmiTableRow($item, $row_idx, $is_rtm, $doc3_name, $doc3_field) {
        $item_tingkat = strtolower(trim($item['tingkat'] ?? 'fakultas'));
        $photos = !empty($item['foto_kegiatan']) ? json_decode($item['foto_kegiatan'], true) : [];
        if (!is_array($photos)) $photos = [];

        $res_u_st = (int)($item['is_restricted_undangan'] ?? $item['is_restricted'] ?? 0);
        $res_u_pg = (int)($item['max_preview_pages_undangan'] ?? $item['max_preview_pages'] ?? 3);
        $res_u_dm = $item['allowed_domains_undangan'] ?? $item['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id';

        $res_dh_st = (int)($item['is_restricted_daftar_hadir'] ?? $item['is_restricted'] ?? 0);
        $res_dh_pg = (int)($item['max_preview_pages_daftar_hadir'] ?? $item['max_preview_pages'] ?? 3);
        $res_dh_dm = $item['allowed_domains_daftar_hadir'] ?? $item['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id';

        $res_3_st = (int)($item['is_restricted_' . ($is_rtm ? 'notulensi' : 'berita_acara')] ?? $item['is_restricted'] ?? 0);
        $res_3_pg = (int)($item['max_preview_pages_' . ($is_rtm ? 'notulensi' : 'berita_acara')] ?? $item['max_preview_pages'] ?? 3);
        $res_3_dm = $item['allowed_domains_' . ($is_rtm ? 'notulensi' : 'berita_acara')] ?? $item['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id';
        ?>
        <tr class="ami-row"
            data-tingkat="<?= e($item_tingkat) ?>"
            data-fakultas="<?= strtolower(e($item['fakultas'] ?? '')) ?>"
            data-prodi="<?= strtolower(e($item['prodi'] ?? '')) ?>"
            data-judul="<?= strtolower(e($item['judul'] ?? '')) ?>">
            <td class="text-center text-muted fw-bold" style="font-size:0.85rem;vertical-align:middle;"><?= $row_idx ?></td>
            <td class="px-3 py-3" style="vertical-align:middle;">
                <?php if ($item_tingkat === 'universitas'): ?>
                    <div class="fw-bold" style="color:var(--navy);font-size:0.95rem;">Universitas Katolik Soegijapranata</div>
                    <span class="badge border mt-1" style="font-size:0.72rem;font-weight:700;background-color:#FEE2E2 !important;color:#991B1B !important;border-color:#FECACA !important;">
                        <i class="bi bi-bank2 me-1"></i>Tingkat Universitas
                    </span>
                <?php elseif ($item_tingkat === 'fakultas'): ?>
                    <div class="fw-bold" style="color:var(--navy);font-size:0.95rem;"><?= e($item['fakultas']) ?></div>
                    <span class="badge border mt-1" style="font-size:0.72rem;font-weight:700;background-color:#DBEAFE !important;color:#1E40AF !important;border-color:#BFDBFE !important;">
                        <i class="bi bi-diagram-3 me-1"></i>Tingkat Fakultas
                    </span>
                <?php else: ?>
                    <div class="fw-bold" style="color:var(--navy);font-size:0.95rem;"><?= e($item['prodi']) ?></div>
                    <div class="text-muted small mb-1"><?= e($item['fakultas']) ?></div>
                    <span class="badge border" style="font-size:0.72rem;font-weight:700;background-color:#F3E8FF !important;color:#6B21A8 !important;border-color:#DDD6FE !important;">
                        <i class="bi bi-mortarboard me-1"></i>Program Studi
                    </span>
                <?php endif; ?>
            </td>
            <td class="px-3 py-3" style="vertical-align:middle;">
                <div class="fw-bold text-dark mb-1" style="font-size:0.92rem;line-height:1.45;"><?= e($item['judul']) ?></div>
                <div class="text-muted small"><i class="bi bi-calendar-event me-1"></i>Periode <?= e($item['periode']) ?></div>
            </td>
            <td class="px-3 py-3" style="min-width:280px;vertical-align:middle;">
                <div class="d-flex flex-column gap-2">
                    <!-- 1. Undangan -->
                    <?php if (!empty($item['file_undangan'])): ?>
                    <div class="d-flex align-items-center justify-content-between p-1 px-2 rounded-2 gap-2" style="background:#FFFBEB;border:1px solid #FDE68A;">
                        <span class="fw-bold text-nowrap flex-shrink-0" style="color:#B45309;font-size:0.75rem;display:inline-flex;align-items:center;gap:5px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M4 4h16c1.1 0 2 .9 2 2v12c0 1.1-.9 2-2 2H4c-1.1 0-2-.9-2-2V6c0-1.1.9-2 2-2z"></path><polyline points="22,6 12,13 2,6"></polyline></svg>
                            Undangan
                        </span>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="button" class="btn btn-sm d-inline-flex align-items-center justify-content-center gap-1" style="width:68px;height:26px;font-size:0.72rem;background:#ffffff;color:#92400E;border:1px solid #FCD34D;border-radius:4px;font-weight:700;padding:0;" onclick="openAmiDocWithCheck('<?= SITE_URL ?>/uploads/<?= e($item['file_undangan']) ?>', 'Undangan - <?= e(addslashes($item['judul'])) ?>', <?= $res_u_st ?>, <?= $res_u_pg ?>, '<?= e(addslashes($res_u_dm)) ?>')" title="Lihat Pratinjau Undangan">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                Lihat
                            </button>
                            <button type="button" class="btn btn-sm d-inline-flex align-items-center justify-content-center gap-1" style="width:68px;height:26px;font-size:0.72rem;background:#D97706;color:#ffffff;border:1px solid transparent;border-radius:4px;font-weight:700;padding:0;" onclick="downloadAmiDocWithCheck('<?= SITE_URL ?>/uploads/<?= e($item['file_undangan']) ?>', 'Undangan - <?= e(addslashes($item['judul'])) ?>', <?= $res_u_st ?>, <?= $res_u_pg ?>, '<?= e(addslashes($res_u_dm)) ?>')" title="Unduh Undangan">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Unduh
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 2. Daftar Hadir -->
                    <?php if (!empty($item['file_daftar_hadir'])): ?>
                    <div class="d-flex align-items-center justify-content-between p-1 px-2 rounded-2 gap-2" style="background:#F0FDF4;border:1px solid #BBF7D0;">
                        <span class="fw-bold text-nowrap flex-shrink-0" style="color:#15803D;font-size:0.75rem;display:inline-flex;align-items:center;gap:5px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                            Daftar Hadir
                        </span>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="button" class="btn btn-sm d-inline-flex align-items-center justify-content-center gap-1" style="width:68px;height:26px;font-size:0.72rem;background:#ffffff;color:#166534;border:1px solid #86EFAC;border-radius:4px;font-weight:700;padding:0;" onclick="openAmiDocWithCheck('<?= SITE_URL ?>/uploads/<?= e($item['file_daftar_hadir']) ?>', 'Daftar Hadir - <?= e(addslashes($item['judul'])) ?>', <?= $res_dh_st ?>, <?= $res_dh_pg ?>, '<?= e(addslashes($res_dh_dm)) ?>')" title="Lihat Pratinjau Daftar Hadir">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                Lihat
                            </button>
                            <button type="button" class="btn btn-sm d-inline-flex align-items-center justify-content-center gap-1" style="width:68px;height:26px;font-size:0.72rem;background:#15803D;color:#ffffff;border:1px solid transparent;border-radius:4px;font-weight:700;padding:0;" onclick="downloadAmiDocWithCheck('<?= SITE_URL ?>/uploads/<?= e($item['file_daftar_hadir']) ?>', 'Daftar Hadir - <?= e(addslashes($item['judul'])) ?>', <?= $res_dh_st ?>, <?= $res_dh_pg ?>, '<?= e(addslashes($res_dh_dm)) ?>')" title="Unduh Daftar Hadir">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Unduh
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 3. Foto Kegiatan -->
                    <?php if (!empty($photos)): ?>
                    <div class="d-flex align-items-center justify-content-between p-1 px-2 rounded-2 gap-2" style="background:#FAF5FF;border:1px solid #E9D5FF;">
                        <span class="fw-bold text-nowrap flex-shrink-0" style="color:#7E22CE;font-size:0.75rem;display:inline-flex;align-items:center;gap:5px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"></path><circle cx="12" cy="13" r="4"></circle></svg>
                            Foto
                        </span>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="button" class="btn btn-sm d-inline-flex align-items-center justify-content-center" style="width:68px;height:26px;font-size:0.72rem;background:#ffffff;color:#6B21A8;border:1px solid #C084FC;border-radius:4px;font-weight:700;padding:0;white-space:nowrap;" onclick="openAmiGallery(<?= htmlspecialchars(json_encode($photos)) ?>, '<?= e(addslashes($item['judul'])) ?>', '<?= e(addslashes($item['prodi'] ?: ($item['fakultas'] ?: 'Universitas'))) ?>')" title="Lihat <?= count($photos) ?> Foto Kegiatan">
                                <?= count($photos) ?> Gambar
                            </button>
                            <button type="button" class="btn btn-sm d-inline-flex align-items-center justify-content-center gap-1" style="width:68px;height:26px;font-size:0.72rem;background:#7E22CE;color:#ffffff;border:1px solid transparent;border-radius:4px;font-weight:700;padding:0;" onclick="openAmiGallery(<?= htmlspecialchars(json_encode($photos)) ?>, '<?= e(addslashes($item['judul'])) ?>', '<?= e(addslashes($item['prodi'] ?: ($item['fakultas'] ?: 'Universitas'))) ?>')" title="Lihat <?= count($photos) ?> Foto Kegiatan">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                Lihat
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 4. Berita Acara / Notulensi -->
                    <?php if (!empty($item[$doc3_field])): ?>
                    <div class="d-flex align-items-center justify-content-between p-1 px-2 rounded-2 gap-2" style="background:#EFF6FF;border:1px solid #BFDBFE;">
                        <span class="fw-bold text-nowrap flex-shrink-0" style="color:#1D4ED8;font-size:0.75rem;display:inline-flex;align-items:center;gap:5px;">
                            <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                            <?= $doc3_name ?>
                        </span>
                        <div class="d-flex gap-1 flex-shrink-0">
                            <button type="button" class="btn btn-sm d-inline-flex align-items-center justify-content-center gap-1" style="width:68px;height:26px;font-size:0.72rem;background:#ffffff;color:#1E40AF;border:1px solid #93C5FD;border-radius:4px;font-weight:700;padding:0;" onclick="openAmiDocWithCheck('<?= SITE_URL ?>/uploads/<?= e($item[$doc3_field]) ?>', '<?= $doc3_name ?> - <?= e(addslashes($item['judul'])) ?>', <?= $res_3_st ?>, <?= $res_3_pg ?>, '<?= e(addslashes($res_3_dm)) ?>')" title="Lihat Pratinjau <?= $doc3_name ?>">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                Lihat
                            </button>
                            <button type="button" class="btn btn-sm d-inline-flex align-items-center justify-content-center gap-1" style="width:68px;height:26px;font-size:0.72rem;background:#1D4ED8;color:#ffffff;border:1px solid transparent;border-radius:4px;font-weight:700;padding:0;" onclick="downloadAmiDocWithCheck('<?= SITE_URL ?>/uploads/<?= e($item[$doc3_field]) ?>', '<?= $doc3_name ?> - <?= e(addslashes($item['judul'])) ?>', <?= $res_3_st ?>, <?= $res_3_pg ?>, '<?= e(addslashes($res_3_dm)) ?>')" title="Unduh <?= $doc3_name ?>">
                                <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                Unduh
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <?php if (empty($item['file_undangan']) && empty($item['file_daftar_hadir']) && empty($photos) && empty($item[$doc3_field])): ?>
                        <span class="badge bg-light text-muted border px-2 py-1 text-center" style="font-size:0.72rem;font-weight:500;">
                            Belum Ada Lampiran
                        </span>
                    <?php endif; ?>
                </div>
            </td>
        </tr>
        <?php
    }
}

// Helper render accordion bertingkat (Universitas -> Fakultas -> Prodi) gaya Akreditasi
if (!function_exists('renderAmiDropdownHierarchy')) {
    function renderAmiDropdownHierarchy($type, $items, $selected_periode) {
        $is_rtm = ($type === 'rtm');
        $doc3_name = $is_rtm ? 'Notulensi' : 'Berita Acara';
        $doc3_field = $is_rtm ? 'file_notulensi' : 'file_berita_acara';
        $prefix = $is_rtm ? 's5' : 's4';

        // Pengelompokan data berjenjang: Universitas dan Fakultas (membawahi Prodi)
        $univ_items = [];
        $fakultas_groups = [];

        foreach ($items as $item) {
            $tingkat = strtolower(trim($item['tingkat'] ?? 'fakultas'));
            if ($tingkat === 'universitas') {
                $univ_items[] = $item;
            } else {
                $fak = !empty($item['fakultas']) ? trim($item['fakultas']) : 'Lainnya';
                if (!isset($fakultas_groups[$fak])) {
                    $fakultas_groups[$fak] = [
                        'fakultas_items' => [],
                        'prodi_items'    => []
                    ];
                }
                if ($tingkat === 'fakultas' || empty($item['prodi'])) {
                    $fakultas_groups[$fak]['fakultas_items'][] = $item;
                } else {
                    $fakultas_groups[$fak]['prodi_items'][] = $item;
                }
            }
        }
        ksort($fakultas_groups);

        $total_items = count($items);
        $total_fak_items = 0;
        $total_prodi_items = 0;
        foreach ($fakultas_groups as $g) {
            $total_fak_items += count($g['fakultas_items']);
            $total_prodi_items += count($g['prodi_items']);
        }
        ?>

        <!-- Search Bar & Controls Gaya Akreditasi -->
        <div class="p-3 mb-4 rounded-4" style="background:#F8FAFC;border:1px solid #E2E8F0;">
            <div class="row g-3 align-items-center">
                <div class="col-lg-7">
                    <div class="position-relative">
                        <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted" style="pointer-events:none;">
                            <i class="bi bi-search" style="font-size:1.1rem;"></i>
                        </span>
                        <input type="text" class="form-control form-control-lg ps-5 pe-5 ami-search-input" placeholder="Ketik nama unit, fakultas, prodi, atau judul kegiatan..." style="font-size:0.95rem;border-radius:12px;border:1.5px solid #CBD5E1;box-shadow:none;background:#ffffff;">
                        <button type="button" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted p-0 pe-3 d-none ami-clear-search" style="text-decoration:none;" title="Hapus pencarian">
                            <i class="bi bi-x-circle-fill" style="font-size:1.15rem;"></i>
                        </button>
                    </div>
                </div>
                <div class="col-lg-5 d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                    <span class="badge px-3 py-2 ami-counter-badge" style="font-size:0.85rem;border-radius:20px;background:var(--navy);color:#fff;">
                        Menampilkan <?= $total_items ?> dari <?= $total_items ?> Kegiatan
                    </span>
                    <button type="button" class="btn btn-sm btn-outline-secondary ami-toggle-all-btn" style="border-radius:20px;font-size:0.8rem;white-space:nowrap;padding:0.45rem 0.9rem;">
                        <i class="bi bi-arrows-expand me-1"></i> <span class="toggle-text">Buka Semua</span>
                    </button>
                </div>
            </div>

            <!-- Quick Filter Chips: Semua, Universitas, Fakultas, Program Studi -->
            <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top align-items-center">
                <span class="text-muted small fw-semibold me-1"><i class="bi bi-funnel me-1"></i>Filter Tingkat:</span>
                <button type="button" class="btn btn-sm btn-filter-ami active" data-filter="all">Semua (<?= $total_items ?>)</button>
                <button type="button" class="btn btn-sm btn-filter-ami" data-filter="universitas"><i class="bi bi-bank2 me-1 text-danger"></i>Universitas (<?= count($univ_items) ?>)</button>
                <button type="button" class="btn btn-sm btn-filter-ami" data-filter="fakultas"><i class="bi bi-diagram-3 me-1 text-primary"></i>Fakultas (<?= $total_fak_items ?>)</button>
                <button type="button" class="btn btn-sm btn-filter-ami" data-filter="prodi"><i class="bi bi-mortarboard me-1 text-purple"></i>Program Studi (<?= $total_prodi_items ?>)</button>
            </div>
        </div>

        <!-- Alert Jika Pencarian Kosong -->
        <div class="alert alert-warning text-center p-4 d-none rounded-4 ami-no-results-alert" role="alert">
            <i class="bi bi-search me-2" style="font-size:1.5rem;"></i>
            <h6 class="fw-bold mb-1 mt-2">Tidak Ada Data yang Sesuai</h6>
            <p class="small text-muted mb-2">Tidak ditemukan dokumen audit / RTM dengan kata kunci atau filter yang Anda pilih.</p>
            <button type="button" class="btn btn-sm btn-outline-dark ami-reset-search-btn" style="border-radius:20px;">
                Reset Pencarian
            </button>
        </div>

        <?php if ($total_items === 0): ?>
            <div class="text-center py-5 text-muted">
                <i class="bi bi-folder2-open fs-1 text-muted opacity-50 mb-2 d-block"></i>
                Belum ada data <?= $is_rtm ? 'Rapat Tinjauan Manajemen (RTM)' : 'audit lapangan' ?> untuk periode <?= e($selected_periode) ?>.
            </div>
        <?php else: ?>
            <!-- Accordion List: Tingkat Universitas & Fakultas (di dalamnya Prodi) -->
            <div class="accordion accordion-lpm" id="accordionAmi_<?= $prefix ?>">
                
                <!-- 1. TINGKAT UNIVERSITAS (Jika ada) -->
                <?php if (!empty($univ_items)): ?>
                <div class="accordion-item border-0 mb-3 rounded-4 shadow-sm ami-accordion-item" style="overflow:hidden;border:1px solid #E2E8F0 !important;" data-tingkat="universitas" data-fakultas="universitas">
                    <h2 class="accordion-header">
                        <button class="accordion-button fw-bold py-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAmiUniv_<?= $prefix ?>" style="background-color:#f8f9fc;color:var(--navy);box-shadow:none;">
                            <div class="d-flex align-items-center w-100 justify-content-between pe-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-bank2 text-danger fs-5"></i>
                                    <span class="fakultas-title-text">Universitas Katolik Soegijapranata</span>
                                </div>
                                <span class="badge rounded-pill px-3 py-2 ami-count-badge" style="background:#FEE2E2;color:#991B1B;font-weight:700;font-size:0.8rem;">
                                    <?= count($univ_items) ?> Kegiatan Universitas
                                </span>
                            </div>
                        </button>
                    </h2>
                    <div id="collapseAmiUniv_<?= $prefix ?>" class="accordion-collapse collapse show">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead style="background-color:#f8fafc;border-bottom:2px solid #E2E8F0;">
                                        <tr>
                                            <th class="py-3 px-3 fw-bold text-center" width="45" style="color:#64748B;font-size:0.85rem;">#</th>
                                            <th class="py-3 px-3 fw-bold" style="color:#475569;font-size:0.85rem;min-width:180px;">Tingkat / Unit</th>
                                            <th class="py-3 px-3 fw-bold" style="color:#475569;font-size:0.85rem;">Judul Kegiatan</th>
                                            <th class="py-3 px-3 fw-bold text-center" width="280" style="color:#475569;font-size:0.85rem;">Lampiran</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $u_idx = 0;
                                        foreach ($univ_items as $item) {
                                            renderAmiTableRow($item, ++$u_idx, $is_rtm, $doc3_name, $doc3_field);
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 2. TINGKAT FAKULTAS (DI DALAMNYA ADA PRODI MASING-MASING) -->
                <?php 
                $f_count = 0;
                foreach ($fakultas_groups as $fak => $g): 
                    $f_count++;
                    $collapse_id = 'collapseAmi_' . $prefix . '_' . $f_count;
                    $items_in_fak = count($g['fakultas_items']) + count($g['prodi_items']);
                    $is_open = (empty($univ_items) && $f_count === 1);
                ?>
                <div class="accordion-item border-0 mb-3 rounded-4 shadow-sm ami-accordion-item" style="overflow:hidden;border:1px solid #E2E8F0 !important;" data-tingkat="fakultas" data-fakultas="<?= strtolower(e($fak)) ?>">
                    <h2 class="accordion-header">
                        <button class="accordion-button fw-bold py-3 <?= $is_open ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapse_id ?>" style="background-color:#f8f9fc;color:var(--navy);box-shadow:none;">
                            <div class="d-flex align-items-center w-100 justify-content-between pe-3">
                                <div class="d-flex align-items-center gap-2">
                                    <i class="bi bi-building text-primary fs-5"></i>
                                    <span class="fakultas-title-text"><?= htmlspecialchars($fak) ?></span>
                                </div>
                                <span class="badge rounded-pill px-3 py-2 ami-count-badge" style="background:#EDE7F6;color:#4A148C;font-weight:700;font-size:0.8rem;">
                                    <?= count($g['prodi_items']) ?> Prodi &bull; <?= $items_in_fak ?> Kegiatan
                                </span>
                            </div>
                        </button>
                    </h2>
                    <div id="<?= $collapse_id ?>" class="accordion-collapse collapse <?= $is_open ? 'show' : '' ?>">
                        <div class="accordion-body p-0">
                            <div class="table-responsive">
                                <table class="table table-hover mb-0 align-middle">
                                    <thead style="background-color:#f8fafc;border-bottom:2px solid #E2E8F0;">
                                        <tr>
                                            <th class="py-3 px-3 fw-bold text-center" width="45" style="color:#64748B;font-size:0.85rem;">#</th>
                                            <th class="py-3 px-3 fw-bold" style="color:#475569;font-size:0.85rem;min-width:180px;">Program Studi / Unit</th>
                                            <th class="py-3 px-3 fw-bold" style="color:#475569;font-size:0.85rem;">Judul Kegiatan</th>
                                            <th class="py-3 px-3 fw-bold text-center" width="280" style="color:#475569;font-size:0.85rem;">Lampiran</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php 
                                        $row_idx = 0;
                                        // 1. Dokumen Tingkat Fakultas
                                        foreach ($g['fakultas_items'] as $item) {
                                            renderAmiTableRow($item, ++$row_idx, $is_rtm, $doc3_name, $doc3_field);
                                        }
                                        // 2. Dokumen Tingkat Program Studi di dalam Fakultas
                                        foreach ($g['prodi_items'] as $item) {
                                            renderAmiTableRow($item, ++$row_idx, $is_rtm, $doc3_name, $doc3_field);
                                        }
                                        ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>

            </div>
        <?php endif; ?>
        <?php
    }
}
?>

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
                                    <?php if (!empty($keg['is_restricted'])): ?>
                                    <span>•</span>
                                    <span class="badge bg-warning text-dark"><i class="bi bi-lock-fill me-1"></i>Dibatasi</span>
                                    <?php endif; ?>
                                </div>
                                <?php if (!empty($keg['keterangan'])): ?>
                                <p class="text-muted small mb-0 mt-1" style="font-size:0.8rem;line-height:1.4;"><?= e($keg['keterangan']) ?></p>
                                <?php endif; ?>
                            </div>
                            <div class="doc-ami-actions">
                                <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" onclick="openAmiDocWithCheck('<?= SITE_URL ?>/uploads/<?= e($keg['file_dokumen']) ?>', '<?= e(addslashes($keg['judul'])) ?>', <?= (int)($keg['is_restricted'] ?? 0) ?>, <?= (int)($keg['max_preview_pages'] ?? 3) ?>, '<?= e(addslashes($keg['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id')) ?>')">
                                    <i class="bi bi-eye me-1"></i> Pratinjau
                                </button>
                                <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1" onclick="downloadAmiDocWithCheck('<?= SITE_URL ?>/uploads/<?= e($keg['file_dokumen']) ?>', '<?= e(addslashes($keg['judul'])) ?>', <?= (int)($keg['is_restricted'] ?? 0) ?>, <?= (int)($keg['max_preview_pages'] ?? 3) ?>, '<?= e(addslashes($keg['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id')) ?>')">
                                    <i class="bi bi-download me-1"></i> Unduh
                                </button>
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
                    <?php elseif (count($s1_opening) > 1): ?>
                    <!-- Auto-sliding Carousel Opening Meeting -->
                    <div id="carouselS1Opening" class="carousel slide ami-card-carousel mb-3 shadow-sm" data-bs-ride="carousel" data-bs-interval="4000" data-bs-touch="true" data-bs-pause="hover" style="border-radius:14px;overflow:hidden;">
                        <div class="carousel-indicators">
                            <?php foreach ($s1_opening as $idx => $op): ?>
                            <button type="button" data-bs-target="#carouselS1Opening" data-bs-slide-to="<?= $idx ?>" class="<?= $idx === 0 ? 'active' : '' ?>" aria-current="<?= $idx === 0 ? 'true' : 'false' ?>" aria-label="Foto <?= $idx + 1 ?>"></button>
                            <?php endforeach; ?>
                        </div>
                        <div class="carousel-inner">
                            <?php foreach ($s1_opening as $idx => $op): ?>
                            <div class="carousel-item <?= $idx === 0 ? 'active' : '' ?>">
                                <div class="ami-gallery-item" style="height:250px;border-radius:0;" onclick="openPhotoModal('<?= SITE_URL ?>/uploads/<?= e($op['foto']) ?>', '<?= e($op['judul']) ?> (<?= $idx + 1 ?>/<?= count($s1_opening) ?>)', '<?= !empty($op['tanggal_kegiatan']) ? formatTanggal($op['tanggal_kegiatan']) : '' ?>')">
                                    <img src="<?= SITE_URL ?>/uploads/<?= e($op['foto']) ?>" alt="<?= e($op['judul']) ?>" loading="lazy">
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
                        <button class="carousel-control-prev" type="button" data-bs-target="#carouselS1Opening" data-bs-slide="prev" onclick="event.stopPropagation();" title="Sebelumnya">
                            <span class="carousel-control-prev-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Sebelumnya</span>
                        </button>
                        <button class="carousel-control-next" type="button" data-bs-target="#carouselS1Opening" data-bs-slide="next" onclick="event.stopPropagation();" title="Selanjutnya">
                            <span class="carousel-control-next-icon" aria-hidden="true"></span>
                            <span class="visually-hidden">Selanjutnya</span>
                        </button>
                    </div>

                    <div class="row g-2">
                        <?php foreach ($s1_opening as $idx => $op): ?>
                        <div class="col-4">
                            <div class="ami-gallery-item" style="height:70px;border-radius:8px;" onclick="bootstrap.Carousel.getOrCreateInstance(document.getElementById('carouselS1Opening')).to(<?= $idx ?>);openPhotoModal('<?= SITE_URL ?>/uploads/<?= e($op['foto']) ?>', '<?= e($op['judul']) ?>', '<?= !empty($op['tanggal_kegiatan']) ? formatTanggal($op['tanggal_kegiatan']) : '' ?>')">
                                <img src="<?= SITE_URL ?>/uploads/<?= e($op['foto']) ?>" alt="<?= e($op['judul']) ?>" loading="lazy">
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php else: ?>
                    <!-- Single Opening Photo -->
                    <?php $op = $s1_opening[0]; ?>
                    <div class="ami-gallery-item" onclick="openPhotoModal('<?= SITE_URL ?>/uploads/<?= e($op['foto']) ?>', '<?= e($op['judul']) ?>', '<?= !empty($op['tanggal_kegiatan']) ? formatTanggal($op['tanggal_kegiatan']) : '' ?>')">
                        <img src="<?= SITE_URL ?>/uploads/<?= e($op['foto']) ?>" alt="<?= e($op['judul']) ?>" loading="lazy">
                        <div class="ami-gallery-overlay">
                            <?php if (!empty($op['tanggal_kegiatan'])): ?>
                            <span class="badge bg-warning text-dark align-self-start mb-2" style="font-size:0.7rem;">
                                <i class="bi bi-calendar-event me-1"></i><?= formatTanggal($op['tanggal_kegiatan']) ?>
                            </span>
                            <?php endif; ?>
                            <div class="fw-bold text-white small" style="line-height:1.3;"><?= e($op['judul']) ?></div>
                        </div>
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
                                    <div class="text-muted small text-truncate">
                                        <?= basename($p['file_dokumen']) ?> (<?= e($p['file_size'] ?? 'PDF') ?>)
                                        <?php if (!empty($p['is_restricted'])): ?>
                                        <span class="badge bg-warning text-dark ms-1" style="font-size:0.68rem;"><i class="bi bi-lock-fill me-1"></i>Dibatasi</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="doc-ami-actions">
                                    <button type="button" class="btn btn-sm btn-outline-primary rounded-pill px-3 py-1" onclick="openAmiDocWithCheck('<?= SITE_URL ?>/uploads/<?= e($p['file_dokumen']) ?>', '<?= e(addslashes($p['judul'])) ?>', <?= (int)($p['is_restricted'] ?? 0) ?>, <?= (int)($p['max_preview_pages'] ?? 3) ?>, '<?= e(addslashes($p['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id')) ?>')">
                                        <i class="bi bi-eye me-1"></i> Pratinjau
                                    </button>
                                    <button type="button" class="btn btn-sm btn-primary rounded-pill px-3 py-1" onclick="downloadAmiDocWithCheck('<?= SITE_URL ?>/uploads/<?= e($p['file_dokumen']) ?>', '<?= e(addslashes($p['judul'])) ?>', <?= (int)($p['is_restricted'] ?? 0) ?>, <?= (int)($p['max_preview_pages'] ?? 3) ?>, '<?= e(addslashes($p['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id')) ?>')">
                                        <i class="bi bi-download me-1"></i> Unduh
                                    </button>
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
                                    <div class="text-muted small text-truncate">
                                        <?= basename($j['file_dokumen']) ?> (<?= e($j['file_size'] ?? 'PDF') ?>)
                                        <?php if (!empty($j['is_restricted'])): ?>
                                        <span class="badge bg-warning text-dark ms-1" style="font-size:0.68rem;"><i class="bi bi-lock-fill me-1"></i>Dibatasi</span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="doc-ami-actions">
                                    <button type="button" class="btn btn-sm btn-outline-success rounded-pill px-3 py-1" onclick="openAmiDocWithCheck('<?= SITE_URL ?>/uploads/<?= e($j['file_dokumen']) ?>', '<?= e(addslashes($j['judul'])) ?>', <?= (int)($j['is_restricted'] ?? 0) ?>, <?= (int)($j['max_preview_pages'] ?? 3) ?>, '<?= e(addslashes($j['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id')) ?>')">
                                        <i class="bi bi-eye me-1"></i> Pratinjau
                                    </button>
                                    <button type="button" class="btn btn-sm btn-success text-white rounded-pill px-3 py-1" onclick="downloadAmiDocWithCheck('<?= SITE_URL ?>/uploads/<?= e($j['file_dokumen']) ?>', '<?= e(addslashes($j['judul'])) ?>', <?= (int)($j['is_restricted'] ?? 0) ?>, <?= (int)($j['max_preview_pages'] ?? 3) ?>, '<?= e(addslashes($j['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id')) ?>')">
                                        <i class="bi bi-download me-1"></i> Unduh
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- 4.2 Dokumentasi Audit Lapangan (Tingkat Universitas, Fakultas & Prodi) -->
            <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 bg-white ami-accordion-container" id="s4Container">
                <div class="pb-3 mb-4 border-bottom">
                    <h4 class="fw-bold mb-1" style="color:var(--navy);"><i class="bi bi-journal-text text-purple me-2"></i>Dokumentasi Audit Lapangan</h4>
                    <div class="text-muted small">Daftar lengkap berkas berita acara, presensi kehadiran, undangan, dan foto kegiatan audit mutu lapangan per tingkatan satuan kerja.</div>
                </div>

                <?php renderAmiDropdownHierarchy('audit_lapangan', $s4_dokumentasi, $selected_periode); ?>
            </div>
        </div>

        <?php elseif ($active_siklus === 5): ?>
        <!-- ============================================== -->
        <!-- SIKLUS 5: RAPAT TINJAUAN MANAJEMEN (RTM)       -->
        <!-- ============================================== -->
        <div class="card p-4 p-md-5 border-0 shadow-sm rounded-4 bg-white mb-5 ami-accordion-container" id="s5Container">
            <div class="pb-3 mb-4 border-bottom">
                <h4 class="fw-bold mb-1" style="color:var(--navy);"><i class="bi bi-people-fill text-purple me-2"></i>Rapat Tinjauan Manajemen (RTM)</h4>
                <div class="text-muted small">Penyampaian hasil audit, perumusan kebijakan mutu, dan komitmen tindak lanjut tingkat Universitas, Fakultas, dan Prodi.</div>
            </div>

            <?php renderAmiDropdownHierarchy('rtm', $s5_rtm, $selected_periode); ?>
        </div>

        <?php endif; ?>

    </div><!-- /#amiDynamicContainer -->

<?php if ($is_ajax) { exit; } ?>

</section>

<!-- Modal Lightbox Pratinjau Galeri Foto (Grid Semua Foto Sekaligus Tanpa Slide & Scroll) -->
<div class="modal fade" id="photoLightboxModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden" style="background:#0F172A;">
            <div class="modal-header border-0 bg-dark text-white p-3">
                <div class="min-w-0 me-3">
                    <h6 class="modal-title fw-bold text-truncate" id="photoModalTitle">Dokumentasi Foto Kegiatan</h6>
                    <small class="text-white-50" id="photoModalSub"></small>
                </div>
                <div class="d-flex align-items-center gap-2 ms-auto">
                    <span id="photoModalCounter" class="badge rounded-pill text-white px-3 py-1" style="font-size:0.75rem;background:#7E22CE !important;"></span>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-3 p-md-4" style="min-height:320px;max-height:85vh;overflow-y:auto;background:#090D16;display:flex;align-items:center;justify-content:center;">
                <div id="photoGalleryContainer" style="width:100%;"></div>
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
                    <a href="#" id="docModalFallbackDownload" download class="btn btn-primary px-4 py-2 rounded-pill fw-bold shadow-sm" style="background:var(--purple);border-color:var(--purple);color:#fff;box-shadow:0 4px 12px rgba(106,27,154,0.25);">
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
// =========================================================================
// Galeri Foto: Menampilkan Semua 1, 2, 3, 4 Foto Sekaligus Tanpa Slide & Scroll
// =========================================================================
function openAmiGallery(photos, title, subtitle) {
    if (!photos) return;
    if (typeof photos === 'string') {
        try {
            photos = JSON.parse(photos);
        } catch(e) {
            photos = [photos];
        }
    }
    if (!Array.isArray(photos)) photos = [photos];
    if (photos.length === 0) return;

    var modalEl = document.getElementById('photoLightboxModal');
    var titleEl = document.getElementById('photoModalTitle');
    var subEl = document.getElementById('photoModalSub');
    var badgeEl = document.getElementById('photoModalCounter');
    var container = document.getElementById('photoGalleryContainer');

    if (titleEl) titleEl.textContent = title || 'Dokumentasi Foto Kegiatan';
    if (subEl) subEl.textContent = subtitle || '';
    if (badgeEl) {
        badgeEl.style.display = 'inline-block';
        badgeEl.textContent = photos.length + ' Foto Kegiatan';
    }

    if (container) {
        container.innerHTML = '';
        var n = photos.length;

        // Layout konsisten 2x2 berasio 16:9 untuk 1, 2, 3, 4 foto
        container.style.cssText = 'display:grid;grid-template-columns:repeat(2, 1fr);gap:14px;width:100%;max-width:960px;margin:0 auto;';

        photos.forEach(function(photo, idx) {
            var fullUrl = (photo.indexOf('http') === 0 || photo.indexOf('<?= SITE_URL ?>') === 0) 
                          ? photo 
                          : ('<?= SITE_URL ?>/uploads/' + photo);

            var itemDiv = document.createElement('div');
            itemDiv.className = 'ami-grid-photo-item position-relative rounded-3 overflow-hidden shadow';
            
            // Jika hanya 1 foto, pusatkan dengan ukuran setara satu kolom 16:9
            var singleStyle = (n === 1) ? 'grid-column:1 / -1;max-width:540px;margin:0 auto;' : '';
            itemDiv.style.cssText = 'aspect-ratio:16/9;width:100%;background:#000;display:flex;align-items:center;justify-content:center;cursor:pointer;position:relative;' + singleStyle;

            var img = document.createElement('img');
            img.src = fullUrl;
            img.alt = (title || 'Foto') + ' (' + (idx + 1) + ')';
            img.style.cssText = 'width:100%;height:100%;object-fit:cover;transition:transform 0.25s ease;';
            img.loading = 'lazy';

            itemDiv.onmouseenter = function() { img.style.transform = 'scale(1.03)'; };
            itemDiv.onmouseleave = function() { img.style.transform = 'scale(1)'; };

            // Badge nomor foto
            var numBadge = document.createElement('span');
            numBadge.className = 'position-absolute bottom-0 end-0 m-2 badge bg-dark bg-opacity-75 text-white px-2 py-1 shadow-sm';
            numBadge.style.fontSize = '0.72rem';
            numBadge.style.pointerEvents = 'none';
            numBadge.innerHTML = '<i class="bi bi-camera me-1 text-warning"></i> Foto ' + (idx + 1);

            // Overlay informasi klik buka tab baru
            var overlay = document.createElement('div');
            overlay.className = 'position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center text-white';
            overlay.style.cssText = 'background:rgba(0,0,0,0.35);opacity:0;transition:opacity 0.2s ease;pointer-events:none;';
            overlay.innerHTML = '<span class="badge bg-dark bg-opacity-75 px-3 py-2 rounded-pill"><i class="bi bi-arrows-fullscreen me-1"></i> Buka Resolusi Penuh</span>';
            itemDiv.addEventListener('mouseenter', function() { overlay.style.opacity = '1'; });
            itemDiv.addEventListener('mouseleave', function() { overlay.style.opacity = '0'; });

            itemDiv.appendChild(img);
            itemDiv.appendChild(numBadge);
            itemDiv.appendChild(overlay);

            itemDiv.onclick = function() {
                window.open(fullUrl, '_blank');
            };

            container.appendChild(itemDiv);
        });
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalEl);
    modal.show();
}

// Legacy single photo modal
function openPhotoModal(imgSrc, title, subtitle) {
    openAmiGallery([imgSrc], title, subtitle);
}

// =========================================================================
// Modal Pratinjau Dokumen (Preview Sebelum Unduh)
// =========================================================================
function openDocModal(docUrl, docTitle, docSubtitle, isRestricted, maxPages, allowedDomains) {
    var ext = (docUrl.split('.').pop() || '').toLowerCase();
    if (ext === 'pdf' && typeof openPdfViewer === 'function') {
        openPdfViewer(docUrl, docTitle, isRestricted, maxPages, allowedDomains);
        return;
    }

    var iframe = document.getElementById('docModalIframe');
    var fallback = document.getElementById('docModalFallback');
    var loading = document.getElementById('docModalLoading');
    var titleEl = document.getElementById('docModalTitle');
    var subEl = document.getElementById('docModalSubtitle');
    var newTabBtn = document.getElementById('docModalNewTabBtn');
    var dlBtn = document.getElementById('docModalDownloadBtn');
    var fbDlBtn = document.getElementById('docModalFallbackDownload');

    if (titleEl) titleEl.textContent = docTitle || 'Dokumen AMI';
    if (subEl) subEl.textContent = docSubtitle || docUrl.split('/').pop();
    if (newTabBtn) newTabBtn.href = docUrl;
    if (dlBtn) dlBtn.href = docUrl;
    if (fbDlBtn) fbDlBtn.href = docUrl;

    if (ext === 'doc' || ext === 'docx') {
        if (iframe) iframe.style.display = 'none';
        if (loading) loading.style.display = 'none';
        if (fallback) fallback.style.display = 'block';
    } else {
        if (fallback) fallback.style.display = 'none';
        if (iframe) {
            iframe.style.display = 'block';
            iframe.src = docUrl;
        }
        if (loading) loading.style.display = 'block';
    }

    var modal = new bootstrap.Modal(document.getElementById('docPreviewModal'));
    modal.show();
}

// Buka Dokumen AMI dengan Pemeriksaan Batasan & Viewer Universal
function openAmiDocWithCheck(docUrl, docTitle, isRestricted, maxPages, allowedDomains) {
    var ext = (docUrl.split('.').pop() || '').toLowerCase();
    if (ext === 'pdf' && typeof openPdfViewer === 'function') {
        openPdfViewer(docUrl, docTitle, isRestricted, maxPages, allowedDomains);
        return;
    }
    openDocModal(docUrl, docTitle, '', isRestricted, maxPages, allowedDomains);
}

// Unduh Dokumen AMI dengan Pengecekan Batasan Hak Akses
function downloadAmiDocWithCheck(docUrl, docTitle, isRestricted, maxPages, allowedDomains) {
    var restricted = (isRestricted == 1 || isRestricted === true || isRestricted === '1');
    if (restricted) {
        var hasAccess = false;
        if (window.UNIKA_CONFIG && window.UNIKA_CONFIG.isLoggedIn && window.UNIKA_CONFIG.currentUser) {
            var email = window.UNIKA_CONFIG.currentUser.email || '';
            if (typeof isUserAllowedForDoc === 'function') {
                hasAccess = isUserAllowedForDoc(email, allowedDomains);
            }
        }
        if (!hasAccess) {
            if (typeof openPdfViewer === 'function') {
                openPdfViewer(docUrl, docTitle, isRestricted, maxPages, allowedDomains);
            } else {
                alert('Dokumen ini dibatasi untuk civitas akademika dengan domain resmi (' + (allowedDomains || '@unika.ac.id') + '). Silakan masuk terlebih dahulu.');
            }
            return;
        }
    }
    var link = document.createElement('a');
    link.href = docUrl;
    link.download = docUrl.split('/').pop();
    link.target = '_blank';
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}

// Reset iframe on modal close
document.getElementById('docPreviewModal').addEventListener('hidden.bs.modal', function () {
    document.getElementById('docModalIframe').src = '';
});

// =========================================================================
// Filter Engine Accordion Bertingkat Gaya Akreditasi
// =========================================================================
function filterContainer(container) {
    if (!container) return;
    var searchInput = container.querySelector('.ami-search-input');
    var q = searchInput ? searchInput.value.toLowerCase().trim() : '';

    var activeChip = container.querySelector('.btn-filter-ami.active');
    var filterTingkat = activeChip ? activeChip.getAttribute('data-filter') : 'all';

    var clearBtn = container.querySelector('.ami-clear-search');
    if (clearBtn) {
        clearBtn.classList.toggle('d-none', q === '');
    }

    var allRows = container.querySelectorAll('.ami-row');
    var totalVisible = 0;
    var totalAll = allRows.length;

    var accordionItems = container.querySelectorAll('.ami-accordion-item');
    accordionItems.forEach(function(accItem) {
        var rowsInItem = accItem.querySelectorAll('.ami-row');
        var visibleInItem = 0;

        rowsInItem.forEach(function(row) {
            var rowTingkat = row.getAttribute('data-tingkat') || '';
            var rowFak = row.getAttribute('data-fakultas') || '';
            var rowProdi = row.getAttribute('data-prodi') || '';
            var rowJudul = row.getAttribute('data-judul') || '';

            var matchesTingkat = (filterTingkat === 'all' || rowTingkat === filterTingkat);
            var matchesQuery = (q === '') || 
                               (rowJudul.indexOf(q) !== -1) || 
                               (rowFak.indexOf(q) !== -1) || 
                               (rowProdi.indexOf(q) !== -1) || 
                               (rowTingkat.indexOf(q) !== -1);

            if (matchesTingkat && matchesQuery) {
                row.style.display = '';
                visibleInItem++;
                totalVisible++;
            } else {
                row.style.display = 'none';
            }
        });

        var countBadge = accItem.querySelector('.ami-count-badge');
        if (visibleInItem > 0) {
            accItem.style.display = '';
            if (countBadge) {
                countBadge.textContent = visibleInItem + ' Kegiatan';
            }
            // Buka otomatis accordion jika pengguna sedang mengetik pencarian
            if (q !== '') {
                var collapseEl = accItem.querySelector('.accordion-collapse');
                if (collapseEl && !collapseEl.classList.contains('show')) {
                    var bsCollapse = bootstrap.Collapse.getOrCreateInstance(collapseEl, { toggle: false });
                    bsCollapse.show();
                }
            }
        } else {
            accItem.style.display = 'none';
        }
    });

    var counterBadge = container.querySelector('.ami-counter-badge');
    if (counterBadge) {
        counterBadge.textContent = 'Menampilkan ' + totalVisible + ' dari ' + totalAll + ' Kegiatan';
    }

    var emptyAlert = container.querySelector('.ami-no-results-alert');
    if (emptyAlert) {
        emptyAlert.classList.toggle('d-none', totalVisible > 0);
    }
}

function initAmiAccordionFilters() {
    var containers = document.querySelectorAll('.ami-accordion-container');
    containers.forEach(function(container) {
        // 1. Search Input
        var searchInput = container.querySelector('.ami-search-input');
        if (searchInput && !searchInput.dataset.bound) {
            searchInput.dataset.bound = 'true';
            searchInput.addEventListener('input', function() {
                filterContainer(container);
            });
        }

        // 2. Clear Search Button
        var clearBtn = container.querySelector('.ami-clear-search');
        if (clearBtn && !clearBtn.dataset.bound) {
            clearBtn.dataset.bound = 'true';
            clearBtn.addEventListener('click', function() {
                if (searchInput) {
                    searchInput.value = '';
                    searchInput.focus();
                }
                filterContainer(container);
            });
        }

        // 3. Reset Button on Alert
        var resetBtn = container.querySelector('.ami-reset-search-btn');
        if (resetBtn && !resetBtn.dataset.bound) {
            resetBtn.dataset.bound = 'true';
            resetBtn.addEventListener('click', function() {
                if (searchInput) searchInput.value = '';
                var allChips = container.querySelectorAll('.btn-filter-ami');
                allChips.forEach(function(c) { c.classList.remove('active'); });
                var allBtn = container.querySelector('.btn-filter-ami[data-filter="all"]');
                if (allBtn) allBtn.classList.add('active');
                filterContainer(container);
            });
        }

        // 4. Quick Filter Chips
        var filterChips = container.querySelectorAll('.btn-filter-ami');
        filterChips.forEach(function(chip) {
            if (!chip.dataset.bound) {
                chip.dataset.bound = 'true';
                chip.addEventListener('click', function() {
                    filterChips.forEach(function(c) { c.classList.remove('active'); });
                    chip.classList.add('active');
                    var filterVal = chip.getAttribute('data-filter');
                    
                    // Sinkronkan dengan tombol filter tingkat di header atas
                    var topBtns = document.querySelectorAll('#tingkatFilterBox .btn-filter-tingkat');
                    topBtns.forEach(function(b) {
                        b.classList.toggle('active', b.getAttribute('data-tingkat') === filterVal);
                    });

                    filterContainer(container);
                });
            }
        });

        // 5. Toggle All Button (Buka Semua / Tutup Semua)
        var toggleAllBtn = container.querySelector('.ami-toggle-all-btn');
        if (toggleAllBtn && !toggleAllBtn.dataset.bound) {
            toggleAllBtn.dataset.bound = 'true';
            toggleAllBtn.addEventListener('click', function() {
                var collapseEls = container.querySelectorAll('.accordion-collapse');
                var anyClosed = false;
                collapseEls.forEach(function(c) {
                    if (!c.classList.contains('show')) anyClosed = true;
                });

                collapseEls.forEach(function(c) {
                    var inst = bootstrap.Collapse.getOrCreateInstance(c, { toggle: false });
                    if (anyClosed) {
                        inst.show();
                    } else {
                        inst.hide();
                    }
                });

                var toggleText = toggleAllBtn.querySelector('.toggle-text');
                var toggleIcon = toggleAllBtn.querySelector('i');
                if (anyClosed) {
                    if (toggleText) toggleText.textContent = 'Tutup Semua';
                    if (toggleIcon) toggleIcon.className = 'bi bi-arrows-collapse me-1';
                } else {
                    if (toggleText) toggleText.textContent = 'Buka Semua';
                    if (toggleIcon) toggleIcon.className = 'bi bi-arrows-expand me-1';
                }
            });
        }

        // Jalankan kalkulasi awal
        filterContainer(container);
    });
}

// Filter Tingkat Satuan Kerja (Terhubung dengan Header Atas & Accordion Chips)
function applyTingkatFilter(tingkat, btn) {
    if (!tingkat) tingkat = 'all';

    // 1. Update status aktif pada tombol filter atas
    var allFilterBtns = document.querySelectorAll('#tingkatFilterBox .btn-filter-tingkat');
    allFilterBtns.forEach(function(b) {
        if (b.getAttribute('data-tingkat') === tingkat) {
            b.classList.add('active');
        } else {
            b.classList.remove('active');
        }
    });

    // 2. Hubungkan dengan filter chips di dalam accordion container
    var containers = document.querySelectorAll('.ami-accordion-container');
    containers.forEach(function(cont) {
        var chip = cont.querySelector('.btn-filter-ami[data-filter="' + tingkat + '"]');
        if (chip) {
            var allChips = cont.querySelectorAll('.btn-filter-ami');
            allChips.forEach(function(c) { c.classList.remove('active'); });
            chip.classList.add('active');
        }
        filterContainer(cont);
    });

    // 3. Update query string tanpa reload halaman
    if (window.history && window.history.replaceState) {
        var u = new URL(window.location.href);
        if (tingkat === 'all') {
            u.searchParams.delete('tingkat');
        } else {
            u.searchParams.set('tingkat', tingkat);
        }
        window.history.replaceState({ path: u.toString() }, '', u.toString());
    }

    var dynContainer = document.getElementById('amiDynamicContainer');
    if (dynContainer) {
        dynContainer.setAttribute('data-tingkat', tingkat);
    }
}

// Inisialisasi awal saat halaman dimuat
document.addEventListener('DOMContentLoaded', function() {
    initAmiAccordionFilters();

    var initTingkat = '<?= e($selected_tingkat) ?>';
    if (initTingkat && initTingkat !== 'all') {
        applyTingkatFilter(initTingkat);
    }
});

// =========================================================================
// AJAX ENGINE: Pergantian Siklus AMI & Periode Tanpa Reload (Smooth & Instant)
// =========================================================================
var amiAbortCtrl = null;

function loadAmiAjax(targetUrl, pushState) {
    if (typeof pushState === 'undefined') pushState = true;

    var dynamicContainer = document.getElementById('amiDynamicContainer');
    var topLoader = document.getElementById('amiTopLoader');

    if (!dynamicContainer) {
        window.location.href = targetUrl;
        return;
    }

    // Batalkan request AJAX sebelumnya bila pengguna mengklik dengan cepat
    if (amiAbortCtrl) {
        amiAbortCtrl.abort();
    }
    amiAbortCtrl = new AbortController();

    // Bangun URL dengan parameter ajax=1
    var urlObj = new URL(targetUrl, window.location.href);
    urlObj.searchParams.set('ajax', '1');

    // Tampilkan indikator loading dan animasi redup halus
    if (topLoader) {
        topLoader.classList.remove('finishing', 'done');
        topLoader.classList.add('loading');
    }
    dynamicContainer.classList.remove('ami-fade-in');
    dynamicContainer.classList.add('ami-fade-out');

    fetch(urlObj.toString(), {
        signal: amiAbortCtrl.signal,
        headers: {
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(function(res) {
        if (!res.ok) throw new Error('HTTP ' + res.status);
        return res.text();
    })
    .then(function(html) {
        if (topLoader) {
            topLoader.classList.remove('loading');
            topLoader.classList.add('finishing');
        }

        // Parse HTML response
        var tempDiv = document.createElement('div');
        tempDiv.innerHTML = html;
        var newContainer = tempDiv.querySelector('#amiDynamicContainer');

        if (newContainer) {
            dynamicContainer.innerHTML = newContainer.innerHTML;
            if (newContainer.dataset.periode) dynamicContainer.dataset.periode = newContainer.dataset.periode;
            if (newContainer.dataset.siklus) dynamicContainer.dataset.siklus = newContainer.dataset.siklus;
            if (newContainer.dataset.tingkat) dynamicContainer.dataset.tingkat = newContainer.dataset.tingkat;
        } else {
            dynamicContainer.innerHTML = html;
        }

        // Inisialisasi filter accordion untuk konten baru yang dimuat
        initAmiAccordionFilters();

        // Bersihkan parameter ajax=1 untuk URL browser
        var cleanUrl = new URL(targetUrl, window.location.href);
        cleanUrl.searchParams.delete('ajax');

        // Update history URL jika pushState aktif
        if (pushState) {
            window.history.pushState({ path: cleanUrl.toString() }, '', cleanUrl.toString());
        }

        // Perbarui document.title agar rapi
        var siklusVal = dynamicContainer.dataset.siklus || cleanUrl.searchParams.get('siklus') || '1';
        var periodeVal = dynamicContainer.dataset.periode || cleanUrl.searchParams.get('periode') || '';
        document.title = 'Siklus ' + siklusVal + (periodeVal ? ' (' + periodeVal + ')' : '') + ' – Siklus AMI | LPM Unika';

        // Terapkan filter tingkat jika ada pada URL baru
        var targetTingkat = cleanUrl.searchParams.get('tingkat') || 'all';
        applyTingkatFilter(targetTingkat);

        // Re-inisialisasi Bootstrap tooltip bila digunakan
        if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
            var tooltips = [].slice.call(dynamicContainer.querySelectorAll('[data-bs-toggle="tooltip"]'));
            tooltips.forEach(function (el) {
                new bootstrap.Tooltip(el);
            });
        }

        // Transisi masuk yang halus
        dynamicContainer.classList.remove('ami-fade-out');
        dynamicContainer.classList.add('ami-fade-in');
        setTimeout(function() {
            dynamicContainer.classList.remove('ami-fade-in');
        }, 300);

        // Selesaikan top loader
        if (topLoader) {
            setTimeout(function() {
                topLoader.classList.remove('finishing');
                topLoader.classList.add('done');
            }, 180);
        }

        // Scroll halus jika posisi pengguna berada jauh di bawah
        var rect = dynamicContainer.getBoundingClientRect();
        if (rect.top < 60 || rect.top > 320) {
            var navbarOffset = 90;
            var elementPosition = rect.top + window.pageYOffset;
            var offsetPosition = elementPosition - navbarOffset;
            window.scrollTo({
                top: offsetPosition,
                behavior: 'smooth'
            });
        }
    })
    .catch(function(err) {
        if (err.name === 'AbortError') return; // Request lama dibatalkan karena klik baru
        console.error('AJAX Transition error:', err);
        // Fallback: muat ulang halaman biasa jika AJAX mengalami gangguan koneksi
        window.location.href = targetUrl;
    });
}

// Delegasi event klik pada Kartu Siklus dan Periode Pill
document.addEventListener('click', function(e) {
    // 1. Klik pada Kartu 5 Tahapan Siklus
    var stageCard = e.target.closest('.ami-stage-card');
    if (stageCard) {
        var href = stageCard.getAttribute('href');
        if (href && !href.startsWith('javascript:')) {
            e.preventDefault();
            loadAmiAjax(href, true);
            return;
        }
    }

    // 2. Klik pada Pill Periode
    var periodePill = e.target.closest('.periode-pill');
    if (periodePill) {
        var href = periodePill.getAttribute('href');
        if (href && !href.startsWith('javascript:')) {
            e.preventDefault();
            loadAmiAjax(href, true);
            return;
        }
    }
});

// Tangani tombol browser Back & Forward (Popstate) agar tetap AJAX & mulus
window.addEventListener('popstate', function(e) {
    loadAmiAjax(window.location.href, false);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
