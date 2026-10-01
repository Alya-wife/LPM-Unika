<?php
/**
 * Modular AMI Sections Renderer
 * Digunakan bersama oleh ami.php (halaman publik) dan admin/builder-preview.php (visual builder)
 */
require_once __DIR__ . '/../config/database.php';

function getAmiData($selected_tahun_ami = '') {
    static $cache = [];
    $cache_key = 'ta_' . $selected_tahun_ami;
    if (isset($cache[$cache_key])) return $cache[$cache_key];

    $db = getDB();
    $data = [];

    $data['ami_intro_text'] = getPengaturan('ami_intro_text', 'Audit Mutu Internal (AMI) Universitas Katolik Soegijapranata merupakan proses pengujian yang sistematik, mandiri, dan terdokumentasi untuk memastikan bahwa pelaksanaan penjaminan mutu di seluruh Program Studi dan Unit Kerja telah sesuai dengan Standar SPMI UNIKA.');

    // Kalender AMI
    $data['list_tahun_ami'] = $db->query("SELECT DISTINCT tahun_akademik FROM kalender_ami WHERE is_active = 1 ORDER BY tahun_akademik DESC")->fetchAll(PDO::FETCH_COLUMN);
    if (!$selected_tahun_ami) {
        $selected_tahun_ami = trim($_GET['tahun_ami'] ?? ($data['list_tahun_ami'][0] ?? '2026/2027'));
    }
    $data['selected_tahun_ami'] = $selected_tahun_ami;

    $stmt_ami = $db->prepare("SELECT * FROM kalender_ami WHERE is_active = 1 AND tahun_akademik = ? ORDER BY urutan ASC, id ASC");
    $stmt_ami->execute([$selected_tahun_ami]);
    $data['kalender_ami_items'] = $stmt_ami->fetchAll(PDO::FETCH_ASSOC);

    // Kegiatan / Berita AMI
    try {
        $data['kegiatan_ami_items'] = $db->query("
            SELECT b.*, (SELECT COUNT(*) FROM berita_gambar bg WHERE bg.berita_id = b.id) AS total_extra_gambar 
            FROM berita b 
            WHERE b.status = 'published' AND (b.tipe = 'Kegiatan' OR b.kategori LIKE '%AMI%' OR b.judul LIKE '%AMI%' OR b.konten LIKE '%Audit Mutu Internal%') 
            ORDER BY b.tanggal_publikasi DESC, b.created_at DESC 
            LIMIT 10
        ")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $data['kegiatan_ami_items'] = [];
    }

    $cache[$cache_key] = $data;
    return $data;
}

function renderAmiSection($type, $block = [], $is_builder = false, $selected_tahun_ami = '') {
    $data = getAmiData($selected_tahun_ami);
    $bg   = !empty($block['bg_color']) ? "background-color: {$block['bg_color']} !important;" : "";
    $tc   = !empty($block['text_color']) ? "color: {$block['text_color']} !important;" : "";

    switch ($type) {
        case 'ami_pengantar':
            ?>
            <section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-10">
                            <span class="section-tag mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                <?= htmlspecialchars(getPengaturan('ami_intro_badge', 'Evaluasi Mutu')) ?>
                            </span>
                            <h2 class="section-title mb-3"><?= htmlspecialchars(getPengaturan('ami_intro_title', 'Tentang Audit Mutu Internal (AMI)')) ?></h2>
                            <p style="color:var(--text-main);line-height:1.85;font-size:1.05rem;margin-bottom:1.25rem;font-weight:500;">
                                <?= nl2br(htmlspecialchars(getPengaturan('ami_intro_text', 'Audit Mutu Internal (AMI) Universitas Katolik Soegijapranata merupakan proses pengujian yang sistematik, mandiri, dan terdokumentasi untuk memastikan bahwa pelaksanaan penjaminan mutu di seluruh Program Studi dan Unit Kerja telah sesuai dengan Standar SPMI UNIKA.'))) ?>
                            </p>
                            <div class="p-4 rounded-4 mb-4" style="background:#F8FAFC;border:1px solid var(--border);border-left:5px solid var(--navy);box-shadow:0 2px 10px rgba(10,25,47,0.03);">
                                <p style="color:var(--text-muted);line-height:1.8;font-size:0.95rem;margin:0;">
                                    <?= nl2br(htmlspecialchars(getPengaturan('ami_intro_desc2', 'AMI bukan kegiatan mencari kesalahan (audit kepatuhan semata), melainkan proses kolaboratif untuk mengidentifikasi potensi peningkatan mutu (opportunity for improvement), mitigasi risiko akademik, dan kesiapan akreditasi eksternal.'))) ?>
                                </p>
                            </div>

                            <div class="row g-3">
                                <div class="col-sm-6">
                                    <div class="p-3 h-100" style="background:var(--bg-main);border-radius:var(--radius-sm);border-left:3px solid var(--purple);">
                                        <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.95rem;margin-bottom:0.35rem;"><?= htmlspecialchars(getPengaturan('ami_prinsip_judul', 'Prinsip Kerja AMI')) ?></div>
                                        <div style="font-size:0.85rem;color:var(--text-muted);line-height:1.6;"><?= nl2br(htmlspecialchars(getPengaturan('ami_prinsip_text', 'Objektif, profesional, independen, berbasis bukti (evidence-based), dan berorientasi solusi.'))) ?></div>
                                    </div>
                                </div>
                                <div class="col-sm-6">
                                    <div class="p-3 h-100" style="background:var(--bg-main);border-radius:var(--radius-sm);border-left:3px solid #1565C0;">
                                        <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.95rem;margin-bottom:0.35rem;"><?= htmlspecialchars(getPengaturan('ami_sasaran_judul', 'Sasaran Audit')) ?></div>
                                        <div style="font-size:0.85rem;color:var(--text-muted);line-height:1.6;"><?= nl2br(htmlspecialchars(getPengaturan('ami_sasaran_text', 'Seluruh Fakultas, Program Studi, Lembaga Penelitian, Pengabdian, dan Unit Pelaksana Teknis.'))) ?></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'ami_alur':
            $ami_stages = [
                ['1', 'Perencanaan & Sosialisasi', 'Penetapan jadwal siklus audit, penunjukan tim auditor tersertifikasi, dan pengiriman surat pemberitahuan ke seluruh auditee.'],
                ['2', 'Pengisian Dokumen Kinerja (DED)', 'Program Studi dan Unit Kerja mengisi instrumen evaluasi diri dan mengunggah bukti fisik pendukung.'],
                ['3', 'Audit Dokumen (Desk Evaluation)', 'Auditor memeriksa kecukupan dan kesesuaian dokumen bukti kerja terhadap standar mutu sebelum visitasi.'],
                ['4', 'Audit Lapangan (Visitasi)', 'Auditor melakukan verifikasi langsung, wawancara auditee, konfirmasi temuan KTS (Ketidaksesuaian), dan penandatanganan berita acara.'],
                ['5', 'Rapat Tinjauan Manajemen (RTM)', 'Penyampaian rekapitulasi temuan audit kepada Rektorat dan Pimpinan Unit untuk perumusan Rencana Tindak Lanjut (RTL).'],
            ];
            ?>
            <section class="py-5" style="background:var(--bg-main);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="section-tag mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                            </svg>
                            <?= htmlspecialchars($block['badge'] ?? 'Prosedur Kerja') ?>
                        </span>
                        <h2 class="section-title"><?= htmlspecialchars($block['title'] ?? 'Alur & Tahapan Siklus AMI') ?></h2>
                        <p class="section-desc mx-auto">Tahapan audit yang dijalankan oleh tim LPM dan auditor mutu internal UNIKA.</p>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($ami_stages as $st): ?>
                        <div class="col-md-6 col-lg">
                            <div class="card-lpm p-4 h-100" style="background:#fff;border:1px solid var(--border);border-top:4px solid var(--navy);border-radius:var(--radius-md);">
                                <div style="width:36px;height:36px;background:var(--navy);border-radius:50%;color:#fff;font-family:var(--font-heading);font-weight:800;font-size:1rem;display:flex;align-items:center;justify-content:center;margin-bottom:0.85rem;">
                                    <?= $st[0] ?>
                                </div>
                                <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.95rem;margin-bottom:0.4rem;">
                                    <?= $st[1] ?>
                                </h5>
                                <p style="font-size:0.8rem;color:var(--text-muted);line-height:1.55;margin:0;">
                                    <?= $st[2] ?>
                                </p>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="text-center mt-4">
                        <a href="<?= SITE_URL ?>/siklus-ami.php" class="btn btn-primary px-4 py-2 rounded-pill fw-bold" style="box-shadow:0 4px 15px rgba(11,31,68,0.15);">
                            <i class="bi bi-arrow-repeat me-1"></i> Buka Informasi &amp; Dokumentasi Siklus 1 s.d. 5 AMI &rarr;
                        </a>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'ami_jadwal':
            $list_tahun_ami     = $data['list_tahun_ami'];
            $selected_tahun_ami = $data['selected_tahun_ami'];
            $kalender_ami_items = $data['kalender_ami_items'];
            $kegiatan_ami_items = $data['kegiatan_ami_items'];
            ?>
            <section class="py-5 py-md-6" id="jadwal-ami" style="background:#F8FAFC;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <?php if (!empty($list_tahun_ami)): ?>
                    <!-- Period Switcher Pills -->
                    <div class="d-flex justify-content-center align-items-center gap-2 flex-wrap mb-4">
                        <span style="font-size:0.85rem;color:var(--text-muted);font-weight:600;margin-right:4px;">Pilih Periode Tahun Akademik AMI:</span>
                        <?php foreach ($list_tahun_ami as $th): ?>
                        <a href="?tahun_ami=<?= urlencode($th) ?>#jadwal-ami" class="btn btn-sm <?= $selected_tahun_ami === $th ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>" style="border-radius:20px;padding:0.4rem 1.2rem;font-size:0.85rem;">
                            Periode <?= htmlspecialchars($th) ?>
                        </a>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- Main Schedule Card -->
                    <div class="card-lpm mb-4" style="border-radius:var(--radius-xl);overflow:hidden;box-shadow:0 10px 30px rgba(10,25,47,0.06);border:1px solid var(--border);background:#fff;">
                        <div style="background:linear-gradient(135deg, #0A192F 0%, #1B263B 100%);color:#ffffff;padding:2rem 2.25rem;border-bottom:4px solid #F59E0B;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div>
                                    <div class="d-inline-flex align-items-center gap-2 mb-2" style="background:rgba(245,158,11,0.18);border:1px solid rgba(245,158,11,0.4);color:#FBBF24;padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:700;letter-spacing:0.5px;">
                                        JADWAL PELAKSANAAN RESMI
                                    </div>
                                    <h2 style="font-family:var(--font-heading);font-weight:800;font-size:clamp(1.3rem, 2.5vw, 1.8rem);color:#ffffff;margin:0 0 0.35rem;">
                                        <?= htmlspecialchars($block['title'] ?? ('Jadwal Pelaksanaan Audit Mutu Internal (AMI) ' . $selected_tahun_ami)) ?>
                                    </h2>
                                    <div style="font-size:0.92rem;color:rgba(255,255,255,0.8);">
                                        Lembaga Penjaminan Mutu &bull; Universitas Katolik Soegijapranata
                                    </div>
                                </div>
                                <div>
                                    <a href="<?= SITE_URL ?>/siklus-ami.php" class="btn btn-sm btn-outline-light" style="border-radius:20px;padding:0.45rem 1.2rem;font-size:0.85rem;font-weight:600;">
                                        Lihat Siklus AMI (1 s.d. 5) &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Content Area: Table of Schedule -->
                        <div style="padding:1.5rem 2rem;">
                            <?php if (empty($kalender_ami_items)): ?>
                            <div class="col-12 text-center py-5">
                                <p class="text-muted">Jadwal pelaksanaan AMI untuk periode <strong><?= htmlspecialchars($selected_tahun_ami) ?></strong> belum tersedia.</p>
                            </div>
                            <?php else: ?>
                            <div class="table-responsive">
                                <table class="table align-middle" style="margin-bottom:0;">
                                    <thead>
                                        <tr style="background:#F1F5F9;border-bottom:2px solid #CBD5E1;">
                                            <th style="width:70px;padding:1rem 1.25rem;font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:0.85rem;text-align:center;">No</th>
                                            <th style="width:240px;padding:1rem 1.25rem;font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:0.85rem;">Tanggal / Waktu</th>
                                            <th style="padding:1rem 1.25rem;font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:0.85rem;">Keterangan Kegiatan</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php $no = 1; foreach ($kalender_ami_items as $ami): 
                                            $agenda_lines = array_filter(array_map('trim', explode("\n", $ami['kegiatan'])));
                                        ?>
                                        <tr style="border-bottom:1px solid #E2E8F0;">
                                            <td style="padding:1.15rem 1.25rem;text-align:center;vertical-align:top;">
                                                <div style="width:32px;height:32px;background:rgba(21,101,192,0.1);color:#1565C0;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:0.85rem;">
                                                    <?= $no++ ?>
                                                </div>
                                            </td>
                                            <td style="padding:1.15rem 1.25rem;vertical-align:top;">
                                                <div style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:0.95rem;line-height:1.3;">
                                                    <?= htmlspecialchars($ami['bulan_tahun']) ?>
                                                </div>
                                                <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                                                    Periode <?= htmlspecialchars($ami['tahun_akademik']) ?>
                                                </div>
                                            </td>
                                            <td style="padding:1.15rem 1.25rem;vertical-align:top;">
                                                <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.45rem;">
                                                    <?php foreach ($agenda_lines as $act): ?>
                                                    <li style="display:flex;align-items:flex-start;gap:8px;font-size:0.9rem;color:var(--text-main);line-height:1.6;">
                                                        <span style="color:#F59E0B;font-size:1.2rem;line-height:1;margin-top:2px;">▸</span>
                                                        <span><?= htmlspecialchars($act) ?></span>
                                                    </li>
                                                    <?php endforeach; ?>
                                                </ul>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($kegiatan_ami_items)): ?>
                    <!-- Slider Berita & Kegiatan AMI -->
                    <div class="mt-5 pt-3" id="berita-kegiatan-ami">
                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                            <div>
                                <div class="d-inline-flex align-items-center gap-2 mb-2" style="background:#FEF3C7;border:1px solid #FCD34D;color:#92400E;padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:700;letter-spacing:0.5px;">
                                    DOKUMENTASI &amp; KEGIATAN AMI
                                </div>
                                <h3 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:clamp(1.2rem, 2vw, 1.5rem);margin:0 0 0.25rem;">
                                    Berita &amp; Dokumentasi Pelaksanaan AMI
                                </h3>
                                <p style="color:var(--text-muted);font-size:0.875rem;margin:0;">
                                    Dokumentasi foto dan publikasi kegiatan Audit Mutu Internal.
                                </p>
                            </div>
                        </div>

                        <div class="ami-slider-track" style="display:flex;gap:1.5rem;overflow-x:auto;scroll-behavior:smooth;scroll-snap-type:x mandatory;padding-bottom:1.25rem;">
                            <?php foreach ($kegiatan_ami_items as $b): 
                                $tgl_b = $b['tanggal_publikasi'] ?: $b['created_at'];
                            ?>
                            <div class="card-lpm" style="flex:0 0 320px;max-width:320px;scroll-snap-align:start;border-radius:14px;overflow:hidden;border:1px solid var(--border);background:#fff;display:flex;flex-direction:column;">
                                <div style="position:relative;height:185px;background:#F1F5F9;overflow:hidden;">
                                    <?php if ($b['gambar'] && file_exists(__DIR__ . '/../uploads/berita/' . $b['gambar'])): ?>
                                        <img src="<?= SITE_URL ?>/uploads/berita/<?= htmlspecialchars($b['gambar']) ?>" alt="<?= htmlspecialchars($b['judul']) ?>" style="width:100%;height:100%;object-fit:cover;">
                                    <?php else: ?>
                                        <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, #0A192F 0%, #1565C0 100%);color:#fff;">
                                            <i class="bi bi-newspaper" style="font-size:2.5rem;opacity:0.4;"></i>
                                        </div>
                                    <?php endif; ?>
                                </div>
                                <div style="padding:1.25rem;display:flex;flex-direction:column;flex-grow:1;">
                                    <div class="mb-2" style="font-size:0.75rem;color:var(--text-muted);">
                                        <i class="bi bi-calendar3 me-1"></i><?= formatTanggal($tgl_b) ?>
                                    </div>
                                    <h5 style="font-family:var(--font-heading);font-weight:700;font-size:0.98rem;line-height:1.45;margin-bottom:0.6rem;color:var(--navy);">
                                        <a href="<?= SITE_URL ?>/berita-detail.php?slug=<?= htmlspecialchars($b['slug']) ?>" style="color:inherit;text-decoration:none;">
                                            <?= htmlspecialchars(mb_substr($b['judul'], 0, 65)) ?><?= mb_strlen($b['judul']) > 65 ? '...' : '' ?>
                                        </a>
                                    </h5>
                                    <p style="font-size:0.825rem;color:var(--text-main);line-height:1.6;margin-bottom:1rem;flex-grow:1;">
                                        <?= truncate(strip_tags($b['konten']), 85) ?>
                                    </p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </section>
            <?php
            break;

        case 'ami_instrumen_auditor':
            ?>
            <section class="py-5" style="background:#ffffff;<?= $bg ?><?= $tc ?>" id="instrumen">
                <div class="container">
                    <div class="row g-5">
                        <div class="col-lg-6">
                            <span class="section-tag mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                </svg>
                                <?= htmlspecialchars($block['badge'] ?? 'Pedoman & Instrumen') ?>
                            </span>
                            <h3 class="section-title mb-3" style="font-size:1.5rem;"><?= htmlspecialchars($block['title'] ?? 'Pedoman & Instrumen AMI') ?></h3>
                            <p style="color:var(--text-muted);font-size:0.9rem;line-height:1.7;margin-bottom:1.5rem;">
                                Instrumen AMI disusun mengacu pada Kriteria SN-Dikti dan Matriks Penilaian Akreditasi LAM/BAN-PT, mencakup bidang Akademik, Tata Pamong, SDM, Keuangan, Sarpras, Penelitian, Pengabdian, serta Luaran Capaian.
                            </p>
                            <div class="list-group" style="border-radius:var(--radius-md);">
                                <div class="list-group-item p-3 d-flex justify-content-between align-items-center" style="border-color:var(--border);">
                                    <div>
                                        <div style="font-weight:600;color:var(--navy);font-size:0.9rem;">Pedoman Operasional AMI UNIKA</div>
                                        <small class="text-muted">Panduan tata cara pelaksanaan dan kode etik auditor</small>
                                    </div>
                                    <a href="<?= SITE_URL ?>/spmi.php#dokumen-spmi" class="btn-action btn-edit">Lihat</a>
                                </div>
                                <div class="list-group-item p-3 d-flex justify-content-between align-items-center" style="border-color:var(--border);">
                                    <div>
                                        <div style="font-weight:600;color:var(--navy);font-size:0.9rem;">Instrumen Audit Program Studi</div>
                                        <small class="text-muted">Formulir evaluasi diri kriteria akademik dan kurikulum</small>
                                    </div>
                                    <a href="<?= SITE_URL ?>/spmi.php#dokumen-spmi" class="btn-action btn-edit">Lihat</a>
                                </div>
                                <div class="list-group-item p-3 d-flex justify-content-between align-items-center" style="border-color:var(--border);">
                                    <div>
                                        <div style="font-weight:600;color:var(--navy);font-size:0.9rem;">Form Berita Acara &amp; Temuan KTS</div>
                                        <small class="text-muted">Form pencatatan tindakan koreksi dan tindak lanjut RTM</small>
                                    </div>
                                    <a href="<?= SITE_URL ?>/spmi.php#dokumen-spmi" class="btn-action btn-edit">Lihat</a>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <span class="section-tag mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />
                                </svg>
                                Auditor Mutu
                            </span>
                            <h3 class="section-title mb-3" style="font-size:1.5rem;">Auditor Mutu Internal</h3>
                            <p style="color:var(--text-muted);font-size:0.9rem;line-height:1.7;margin-bottom:1.5rem;">
                                Universitas Katolik Soegijapranata memiliki auditor mutu internal bersertifikat nasional yang terdiri dari dosen dan tenaga kependidikan berpengalaman dari berbagai disiplin ilmu.
                            </p>
                            <div class="p-4" style="background:var(--bg-main);border-radius:var(--radius-md);border:1px solid var(--border);">
                                <h6 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:0.75rem;">Kualifikasi Auditor Mutu UNIKA:</h6>
                                <ul style="font-size:0.85rem;color:var(--text-muted);line-height:1.7;margin:0;padding-left:1.2rem;">
                                    <li>Telah lulus Pelatihan Auditor Mutu Internal bersertifikat.</li>
                                    <li>Memahami Standar Nasional Pendidikan Tinggi (SN-Dikti) dan kriteria akreditasi LAM.</li>
                                    <li>Bebas dari konflik kepentingan <em>(conflict of interest)</em> terhadap program studi yang diaudit.</li>
                                    <li>Menjunjung tinggi kerahasiaan data dan kode etik audit mutu.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;
    }
}
