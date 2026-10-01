<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Pelatihan Eksternal & Pengembangan Mutu';
$meta_desc  = 'Program Pelatihan Eksternal, Sertifikasi Auditor Mutu Internal (AMI), Bimtek SPMI PPEPP, Klinik Akreditasi LED/LKPS, dan Brosur Resmi LPM Universitas Katolik Soegijapranata.';

$db = getDB();

// 1. Pengaturan Teks & Kontak dari Admin
$pelatihan_hero_title = getPengaturan('pelatihan_hero_title', 'Pelatihan Eksternal & Pengembangan Mutu');
$pelatihan_hero_desc  = getPengaturan('pelatihan_hero_desc', 'Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata menyelenggarakan bimbingan teknis, workshop klinik akreditasi, dan pelatihan sertifikasi auditor penjaminan mutu internal bagi perguruan tinggi mitra.');
$pelatihan_email      = getPengaturan('pelatihan_email_kontak', 'lpm@unika.ac.id');
$pelatihan_telepon    = getPengaturan('pelatihan_telepon', '(024) 8441555 Ext. 1473');
$pelatihan_jam        = getPengaturan('pelatihan_jam_layanan', 'Senin – Jumat (08:00 – 15:30 WIB)');
$pelatihan_alamat     = getPengaturan('pelatihan_alamat', 'Kampus UNIKA Bendan Dhuwur, Semarang');

// 2. Fetch Data Dinamis (Dikelola oleh Admin)
$brosur_list     = [];
$alur_list       = [];
$keunggulan_list = [];
$jadwal_list     = [];
$faq_list        = [];

try {
    $brosur_list     = $db->query("SELECT * FROM layanan_brosur WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $alur_list       = $db->query("SELECT * FROM layanan_alur WHERE is_active = 1 ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $keunggulan_list = $db->query("SELECT * FROM layanan_keunggulan WHERE is_active = 1 ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $jadwal_list     = $db->query("SELECT * FROM layanan_jadwal ORDER BY tanggal_mulai DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
    $faq_list        = $db->query("SELECT * FROM layanan_faq WHERE is_active = 1 ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$extra_css = '
<style>
.pelatihan-hero {
    background: linear-gradient(135deg, #0A192F 0%, #1E3A8A 60%, #0284C7 100%);
    color: #ffffff;
    padding: 4.25rem 1rem 3.75rem;
    position: relative;
    overflow: hidden;
}
.pelatihan-hero h1 {
    color: #ffffff !important;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: clamp(1.85rem, 3.5vw, 2.6rem);
    margin-bottom: 0.85rem;
    text-shadow: 0 2px 10px rgba(0,0,0,0.25);
}
.pelatihan-section-title {
    font-family: var(--font-heading);
    font-weight: 800;
    color: var(--navy);
    font-size: clamp(1.35rem, 2.5vw, 1.65rem);
    margin-bottom: 0.45rem;
}
.pelatihan-card {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: var(--radius-lg);
    box-shadow: 0 4px 20px rgba(10,25,47,0.04);
}
</style>
';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Banner (Tanpa Badge di atas judul) -->
<section class="pelatihan-hero">
    <div class="container text-center">
        <h1><?= htmlspecialchars($pelatihan_hero_title) ?></h1>
        <p style="font-size:1.05rem;color:rgba(255,255,255,0.92);max-width:780px;margin:0 auto;line-height:1.7;">
            <?= nl2br(htmlspecialchars($pelatihan_hero_desc)) ?>
        </p>
    </div>
</section>

<!-- Main Single-Page Content -->
<section class="py-5" style="background:#F8FAFC;">
    <div class="container">

        <!-- ========================================================================= -->
        <!-- 1. BROSUR RESMI PELATIHAN                                                 -->
        <!-- ========================================================================= -->
        <?php if (!empty($brosur_list)): ?>
        <div class="mb-5">
            <div class="row g-4 justify-content-center">
                <?php foreach ($brosur_list as $br): ?>
                <div class="col-lg-10 col-xl-9">
                    <div class="p-4 p-md-5 rounded-4 d-flex flex-column flex-md-row align-items-center gap-4" style="background:linear-gradient(135deg, #0A192F, #132D54);color:#ffffff;box-shadow:0 12px 35px rgba(10,25,47,0.18);">
                        <div style="width:90px;height:90px;min-width:90px;background:rgba(255,255,255,0.12);border-radius:20px;display:flex;align-items:center;justify-content:center;color:#FFD54F;font-size:3rem;">
                            <i class="bi bi-file-earmark-pdf"></i>
                        </div>
                        <div class="flex-grow-1 text-center text-md-start">
                            <div class="badge bg-warning text-dark px-3 py-1 mb-2 fw-bold" style="border-radius:20px;font-size:0.75rem;">
                                EDISI RESMI TAHUN <?= htmlspecialchars($br['tahun']) ?>
                            </div>
                            <h4 style="font-family:var(--font-heading);font-weight:800;font-size:1.35rem;margin-bottom:0.4rem;color:#ffffff;">
                                <?= htmlspecialchars($br['judul_brosur']) ?>
                            </h4>
                            <p style="font-size:0.875rem;color:rgba(255,255,255,0.85);margin-bottom:1.25rem;line-height:1.6;">
                                <?= htmlspecialchars($br['deskripsi'] ?? 'Panduan lengkap silabus materi pelatihan, jadwal pelaksanaan, format pendaftaran peserta, dan fasilitas kemitraan.') ?>
                            </p>
                            <div class="d-flex flex-wrap gap-2 justify-content-center justify-content-md-start">
                                <button type="button" class="btn btn-warning px-4 py-2 fw-bold rounded-pill" onclick="openPdfModal('<?= SITE_URL ?>/uploads/layanan/<?= htmlspecialchars($br['file_brosur']) ?>', '<?= htmlspecialchars($br['judul_brosur']) ?>')" style="font-size:0.9rem;">
                                    <i class="bi bi-eye-fill me-1"></i> Baca Brosur Online
                                </button>
                                <a href="<?= SITE_URL ?>/uploads/layanan/<?= htmlspecialchars($br['file_brosur']) ?>" download class="btn btn-outline-light px-4 py-2 fw-bold rounded-pill" style="font-size:0.9rem;">
                                    <i class="bi bi-download me-1"></i> Unduh Berkas PDF
                                </a>
                                <?php if (!empty($br['link_pendaftaran'])): ?>
                                <a href="<?= htmlspecialchars($br['link_pendaftaran']) ?>" target="_blank" class="btn btn-success px-4 py-2 fw-bold rounded-pill" style="font-size:0.9rem;">
                                    <i class="bi bi-pencil-square me-1"></i> Formulir Pendaftaran Daring
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========================================================================= -->
        <!-- 2. ALUR & MEKANISME KERJASAMA PELATIHAN (CMS DINAMIS)                     -->
        <!-- ========================================================================= -->
        <?php if (!empty($alur_list)): ?>
        <div class="mb-5 pb-2">
            <div class="text-center mb-4">
                <h3 class="pelatihan-section-title">
                    Alur &amp; Mekanisme Kerjasama Pelatihan Mutu
                </h3>
                <p style="font-size:0.92rem;color:var(--text-muted);max-width:760px;" class="mx-auto">
                    Standar baku bagi perguruan tinggi mitra, fakultas, atau lembaga pendidikan dalam menyelenggarakan pelatihan publik maupun <em>in-house training</em> bersama LPM UNIKA Soegijapranata.
                </p>
            </div>

            <div class="row g-3">
                <?php 
                $bg_colors = ['#EFF6FF', '#F0FDF4', '#FEF3C7', '#F3E8FF', '#ECFDF5'];
                $tx_colors = ['#2563EB', '#16A34A', '#D97706', '#9333EA', '#059669'];
                $idx = 0;
                foreach ($alur_list as $al): 
                    $bg_badge = $bg_colors[$idx % count($bg_colors)];
                    $tx_badge = $tx_colors[$idx % count($tx_colors)];
                    $idx++;
                ?>
                <div class="col-md-4 col-lg">
                    <div class="card-lpm p-4 h-100 text-center" style="background:#ffffff;border:1px solid var(--border);border-radius:14px;box-shadow:0 3px 12px rgba(10,25,47,0.03);">
                        <div style="width:48px;height:48px;border-radius:50%;background:<?= $bg_badge ?>;color:<?= $tx_badge ?>;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:1.15rem;margin-bottom:1rem;">
                            <?= htmlspecialchars($al['langkah_ke']) ?>
                        </div>
                        <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:1rem;margin-bottom:0.4rem;">
                            <?= htmlspecialchars($al['judul']) ?>
                        </h5>
                        <p style="font-size:0.83rem;color:var(--text-muted);line-height:1.55;margin:0;">
                            <?= htmlspecialchars($al['deskripsi']) ?>
                        </p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========================================================================= -->
        <!-- 3. STANDAR KEUNGGULAN & FASILITAS PESERTA (CMS DINAMIS)                   -->
        <!-- ========================================================================= -->
        <?php if (!empty($keunggulan_list)): ?>
        <div class="mb-5 pb-2">
            <div class="card-lpm p-4 p-md-5" style="background:#ffffff;border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:0 4px 20px rgba(10,25,47,0.04);">
                <div class="text-center mb-4">
                    <h3 class="pelatihan-section-title">
                        Standar Keunggulan &amp; Fasilitas Peserta
                    </h3>
                    <p style="font-size:0.9rem;color:var(--text-muted);max-width:700px;" class="mx-auto">
                        Seluruh pelatihan penjaminan mutu di LPM UNIKA diselenggarakan dengan jaminan standar profesionalitas perguruan tinggi unggul.
                    </p>
                </div>

                <div class="row g-4">
                    <?php 
                    $icon_bgs = ['#EFF6FF', '#F0FDF4', '#FEF3C7', '#F3E8FF'];
                    $icon_txs = ['#2563EB', '#16A34A', '#D97706', '#9333EA'];
                    $k_idx = 0;
                    foreach ($keunggulan_list as $kg): 
                        $i_bg = $icon_bgs[$k_idx % count($icon_bgs)];
                        $i_tx = $icon_txs[$k_idx % count($icon_txs)];
                        $k_idx++;
                    ?>
                    <div class="col-md-6">
                        <div class="d-flex gap-3 align-items-start">
                            <div style="width:46px;height:46px;min-width:46px;border-radius:12px;background:<?= $i_bg ?>;color:<?= $i_tx ?>;display:flex;align-items:center;justify-content:center;font-size:1.35rem;">
                                <i class="bi <?= htmlspecialchars($kg['icon'] ?? 'bi-award-fill') ?>"></i>
                            </div>
                            <div>
                                <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:1.02rem;margin-bottom:0.3rem;">
                                    <?= htmlspecialchars($kg['judul']) ?>
                                </h5>
                                <p style="font-size:0.86rem;color:var(--text-muted);line-height:1.6;margin:0;">
                                    <?= htmlspecialchars($kg['deskripsi']) ?>
                                </p>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========================================================================= -->
        <!-- 4. KALENDER & JADWAL TERLAKSANA (DI BAWAHNYA STANDAR KEUNGGULAN)          -->
        <!-- ========================================================================= -->
        <div class="mb-5 pb-2">
            <div class="card-lpm p-4 p-md-5" style="background:#ffffff;border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:0 4px 20px rgba(10,25,47,0.05);">
                <div class="text-center mb-4">
                    <h3 class="pelatihan-section-title">
                        Agenda &amp; Rekapitulasi Jadwal Terlaksana
                    </h3>
                    <p style="font-size:0.92rem;color:var(--text-muted);max-width:720px;" class="mx-auto">
                        Daftar pelaksanaan program pelatihan, workshop penjaminan mutu, dan bimbingan teknis yang diselenggarakan bersama perguruan tinggi mitra.
                    </p>
                </div>

                <?php if (!empty($jadwal_list)): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                        <thead style="background:#F8FAFC;border-bottom:2px solid #E2E8F0;color:var(--navy);">
                            <tr>
                                <th style="width:40px;" class="text-center">No</th>
                                <th>Nama Pelatihan / Agenda</th>
                                <th>Tanggal Pelaksanaan</th>
                                <th>Lokasi / Tempat</th>
                                <th class="text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $no = 1;
                            foreach ($jadwal_list as $j): 
                                $tgl_teks = date('d M Y', strtotime($j['tanggal_mulai']));
                                if (!empty($j['tanggal_selesai']) && $j['tanggal_selesai'] !== $j['tanggal_mulai']) {
                                    $tgl_teks .= ' - ' . date('d M Y', strtotime($j['tanggal_selesai']));
                                }

                                $status_badge = 'bg-secondary';
                                $st = strtolower($j['status']);
                                if ($st === 'terlaksana') {
                                    $status_badge = 'bg-success';
                                } elseif (strpos($st, 'dibuka') !== false || $st === 'pendaftaran dibuka') {
                                    $status_badge = 'bg-primary';
                                } elseif ($st === 'penuh') {
                                    $status_badge = 'bg-warning text-dark';
                                } elseif ($st === 'akan datang') {
                                    $status_badge = 'bg-info text-dark';
                                }
                            ?>
                            <tr>
                                <td class="text-center text-muted fw-bold"><?= $no++ ?></td>
                                <td>
                                    <div class="fw-bold text-navy" style="font-size:0.92rem;"><?= htmlspecialchars($j['nama_kegiatan'] ?? $j['nama_pelatihan'] ?? '') ?></div>
                                    <?php if (!empty($j['institusi_peserta'])): ?>
                                        <div style="font-size:0.8rem;color:var(--text-muted);margin-top:2px;">
                                            <i class="bi bi-people me-1"></i><?= htmlspecialchars($j['institusi_peserta']) ?>
                                            <?php if (!empty($j['jumlah_peserta'])): ?>
                                                <span class="badge bg-light text-dark border ms-1"><?= (int)$j['jumlah_peserta'] ?> Peserta</span>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                    <?php if (!empty($j['keterangan'])): ?>
                                        <small class="text-muted d-block mt-1" style="font-size:0.78rem;font-style:italic;"><?= htmlspecialchars($j['keterangan']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td style="white-space:nowrap;">
                                    <i class="bi bi-calendar3 text-primary me-1"></i>
                                    <strong><?= $tgl_teks ?></strong>
                                </td>
                                <td>
                                    <i class="bi bi-geo-alt-fill text-danger me-1"></i><?= htmlspecialchars($j['lokasi'] ?? 'Kampus UNIKA Soegijapranata, Semarang') ?>
                                </td>
                                <td class="text-center">
                                    <span class="badge <?= $status_badge ?> px-3 py-1 rounded-pill fw-bold" style="font-size:0.75rem;letter-spacing:0.5px;">
                                        <?= htmlspecialchars(strtoupper($j['status'])) ?>
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php else: ?>
                    <div class="text-center py-5 text-muted">Belum ada agenda jadwal pelatihan yang diunggah.</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 5. PERTANYAAN UMUM SEPUTAR PELATIHAN (FAQ DINAMIS)                        -->
        <!-- ========================================================================= -->
        <?php if (!empty($faq_list)): ?>
        <div class="mb-5 pb-2">
            <div class="text-center mb-4">
                <h3 class="pelatihan-section-title">
                    Pertanyaan Umum Seputar Pelatihan
                </h3>
                <p style="font-size:0.9rem;color:var(--text-muted);max-width:650px;" class="mx-auto">
                    Jawaban atas pertanyaan yang sering diajukan oleh calon peserta dan pimpinan institusi mitra.
                </p>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="accordion" id="accordionFaqPelatihan">
                        <?php 
                        $f_idx = 0;
                        foreach ($faq_list as $fq): 
                            $is_first = ($f_idx === 0);
                            $f_idx++;
                        ?>
                        <div class="accordion-item mb-3" style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">
                            <h2 class="accordion-header" id="faqHeading<?= $fq['id'] ?>">
                                <button class="accordion-button <?= $is_first ? '' : 'collapsed' ?> fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse<?= $fq['id'] ?>" aria-expanded="<?= $is_first ? 'true' : 'false' ?>" aria-controls="faqCollapse<?= $fq['id'] ?>" style="font-size:0.95rem;color:var(--navy);">
                                    <?= htmlspecialchars($fq['pertanyaan']) ?>
                                </button>
                            </h2>
                            <div id="faqCollapse<?= $fq['id'] ?>" class="accordion-collapse collapse <?= $is_first ? 'show' : '' ?>" aria-labelledby="faqHeading<?= $fq['id'] ?>" data-bs-parent="#accordionFaqPelatihan">
                                <div class="accordion-body" style="font-size:0.88rem;color:var(--text-main);line-height:1.65;background:#FAFAFA;">
                                    <?= nl2br(htmlspecialchars($fq['jawaban'])) ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- ========================================================================= -->
        <!-- 6. NARAHUBUNG & KONSULTASI RESMI KERJASAMA (VIA EMAIL RESMI)             -->
        <!-- ========================================================================= -->
        <div>
            <div class="p-4 p-md-5 rounded-4 text-center text-md-start d-flex flex-column flex-md-row align-items-center justify-content-between gap-4" style="background:linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);color:#ffffff;box-shadow:0 8px 30px rgba(10,25,47,0.15);">
                <div>
                    <h4 style="font-family:var(--font-heading);font-weight:800;font-size:1.3rem;margin-bottom:0.4rem;color:#ffffff;">
                        Konsultasi Kemitraan &amp; Pelatihan Institusi
                    </h4>
                    <p style="font-size:0.88rem;color:rgba(255,255,255,0.85);margin-bottom:0.75rem;max-width:620px;line-height:1.6;">
                        Sekretariat Lembaga Penjaminan Mutu (LPM) siap membantu perencanaan pelatihan, penyesuaian silabus, dan jadwal pelaksanaan bagi perguruan tinggi Anda.
                    </p>
                    <div class="d-flex flex-wrap gap-3 text-white-50" style="font-size:0.82rem;">
                        <span><i class="bi bi-building text-warning me-1"></i> <?= htmlspecialchars($pelatihan_alamat) ?></span>
                        <span><i class="bi bi-clock text-warning me-1"></i> <?= htmlspecialchars($pelatihan_jam) ?></span>
                        <span><i class="bi bi-telephone text-warning me-1"></i> <?= htmlspecialchars($pelatihan_telepon) ?></span>
                    </div>
                </div>
                <div class="d-flex flex-column sm:flex-row gap-2 flex-shrink-0">
                    <a href="mailto:<?= htmlspecialchars($pelatihan_email) ?>?subject=Permohonan%20Informasi%20&%20Kerjasama%20Pelatihan%20LPM%20UNIKA" class="btn btn-warning px-4 py-2 fw-bold rounded-pill text-dark d-inline-flex align-items-center justify-content-center gap-2" style="font-size:0.92rem;">
                        <i class="bi bi-envelope-fill"></i> Hubungi via Email: <?= htmlspecialchars($pelatihan_email) ?>
                    </a>
                </div>
            </div>
        </div>

    </div>
</section>

<!-- Modal Baca Brosur Online -->
<div class="modal fade" id="modalPdfViewer" tabindex="-1" aria-labelledby="modalPdfTitle" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--radius-lg);overflow:hidden;border:none;">
            <div class="modal-header bg-dark text-white px-4 py-3">
                <h5 class="modal-title fs-6 fw-bold" id="modalPdfTitle">Brosur Pelatihan Resmi</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-0" style="height:75vh;background:#525659;">
                <iframe id="pdfIframe" src="" style="width:100%;height:100%;border:none;"></iframe>
            </div>
            <div class="modal-footer bg-light px-4 py-2 d-flex justify-content-between">
                <span class="text-muted" style="font-size:0.8rem;">Gunakan kontrol viewer PDF untuk zoom in/out atau mencetak brosur.</span>
                <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>

<script>
function openPdfModal(pdfUrl, pdfTitle) {
    document.getElementById('modalPdfTitle').innerText = pdfTitle;
    document.getElementById('pdfIframe').src = pdfUrl;
    var myModal = new bootstrap.Modal(document.getElementById('modalPdfViewer'));
    myModal.show();
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
