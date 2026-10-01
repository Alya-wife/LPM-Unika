<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Info Pelatihan';
$meta_desc  = 'Informasi Program Pelatihan Penjaminan Mutu, Sertifikasi Auditor Mutu Internal (AMI), Bimtek SPMI PPEPP, Klinik Akreditasi Perguruan Tinggi, dan Brosur Resmi LPM Universitas Katolik Soegijapranata.';

$db = getDB();

// 1. Pengaturan Narasi, Banner & Kontak dari Admin
$pelatihan_hero_title     = getPengaturan('pelatihan_hero_title', 'Info Pelatihan Lembaga Penjaminan Mutu');
$pelatihan_hero_desc      = getPengaturan('pelatihan_hero_desc', 'Program Pelatihan, Bimbingan Teknis, Sertifikasi Auditor Mutu Internal (AMI), dan Klinik Akreditasi Perguruan Tinggi Mitra');
$pelatihan_narasi_lengkap = getPengaturan('pelatihan_narasi_lengkap', "Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata berkomitmen mengawal dan menumbuhkan budaya mutu pendidikan tinggi yang unggul, berkelanjutan, dan berdaya saing global.\n\nBerbekal pengalaman mengawal akreditasi institusi UNGGUL serta amanah Kementerian sebagai pelaksana Program Asuh Perguruan Tinggi Unggul selama bertahun-tahun yang telah sukses mendampingi puluhan perguruan tinggi dan program studi di Indonesia, LPM UNIKA secara konsisten menyelenggarakan berbagai program pelatihan, bimbingan teknis, dan sertifikasi penjaminan mutu terstruktur bagi perguruan tinggi mitra, fakultas, serta lembaga pendidikan.\n\nProgram pelatihan difasilitasi langsung oleh para pakar penjaminan mutu, asesor BAN-PT/LAM bersertifikasi nasional, serta auditor mutu internal berpengalaman. Seluruh kurikulum dan materi dirancang aplikatif, berbasis studi kasus riil tata kelola kampus, dan selaras dengan regulasi Permendikbudristek No. 53 Tahun 2023 tentang Penjaminan Mutu Pendidikan Tinggi serta standar instrumen akreditasi terkini.");
$pelatihan_email          = getPengaturan('pelatihan_email_kontak', getPengaturan('email', 'lpm@unika.ac.id'));
if (empty($pelatihan_email)) {
    $pelatihan_email = 'lpm@unika.ac.id';
}
$pelatihan_telepon        = getPengaturan('pelatihan_telepon', '(024) 8441555 Ext. 1473');
$pelatihan_jam            = getPengaturan('pelatihan_jam_layanan', 'Senin – Jumat (08:00 – 15:30 WIB)');
$pelatihan_alamat         = getPengaturan('pelatihan_alamat', 'Kampus UNIKA Bendan Dhuwur, Semarang');

// 2. Fetch Berkas Brosur Aktif dari Admin
$brosur_list = [];
try {
    $brosur_list = $db->query("SELECT * FROM layanan_brosur WHERE is_active = 1 ORDER BY urutan ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {}

$extra_css = '
<style>
.pelatihan-hero {
    background: linear-gradient(135deg, #0A192F 0%, #1E3A8A 55%, #0284C7 100%);
    color: #ffffff;
    padding: 4.5rem 1rem 3.75rem;
    position: relative;
    overflow: hidden;
}
.pelatihan-hero::after {
    content: "";
    position: absolute;
    top: -50%;
    right: -10%;
    width: 600px;
    height: 600px;
    background: radial-gradient(circle, rgba(255,255,255,0.08) 0%, transparent 70%);
    border-radius: 50%;
    pointer-events: none;
}
.pelatihan-hero h1 {
    color: #ffffff !important;
    font-family: var(--font-heading);
    font-weight: 800;
    font-size: clamp(1.9rem, 3.5vw, 2.7rem);
    margin-bottom: 0.85rem;
    letter-spacing: -0.5px;
    text-shadow: 0 2px 10px rgba(0,0,0,0.25);
}
.pelatihan-narrative-card {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 20px;
    box-shadow: 0 10px 30px rgba(10,25,47,0.05);
    padding: 2.75rem 2.5rem;
    transition: transform 0.25s ease, box-shadow 0.25s ease;
}
@media (max-width: 767px) {
    .pelatihan-narrative-card {
        padding: 1.75rem 1.25rem;
    }
}
.pelatihan-narrative-body p {
    color: var(--text-main);
    font-size: 1.05rem;
    line-height: 1.85;
    margin-bottom: 1.25rem;
}
.pelatihan-narrative-body p:last-child {
    margin-bottom: 0;
}
.brosur-showcase-card {
    background: #ffffff;
    border: 1px solid var(--border);
    border-radius: 20px;
    box-shadow: 0 12px 35px rgba(10,25,47,0.06);
    overflow: hidden;
}
.brosur-toolbar {
    background: linear-gradient(135deg, #0A192F 0%, #132D54 100%);
    color: #ffffff;
    padding: 1.5rem 2rem;
    border-bottom: 1px solid rgba(255,255,255,0.1);
}
.brosur-tab-btn {
    border-radius: 30px;
    padding: 0.55rem 1.35rem;
    font-weight: 600;
    font-size: 0.92rem;
    border: 1.5px solid #CBD5E1;
    background: #ffffff;
    color: var(--navy);
    transition: all 0.2s ease;
}
.brosur-tab-btn.active, .brosur-tab-btn:hover {
    background: var(--navy);
    color: #ffffff;
    border-color: var(--navy);
    box-shadow: 0 4px 12px rgba(10,25,47,0.15);
}
.pdf-embed-frame {
    width: 100%;
    min-height: 720px;
    height: 75vh;
    border: none;
    background: #525659;
}
.docx-preview-box {
    background: #F8FAFC;
    border: 2px dashed #93C5FD;
    border-radius: 16px;
    padding: 3rem 1.5rem;
    text-align: center;
}
.img-poster-view {
    max-height: 850px;
    width: auto;
    max-width: 100%;
    border-radius: 12px;
    box-shadow: 0 8px 25px rgba(0,0,0,0.12);
}
</style>
';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Banner -->
<section class="pelatihan-hero">
    <div class="container text-center position-relative" style="z-index:2;">
        <h1><?= htmlspecialchars($pelatihan_hero_title) ?></h1>
        <p style="font-size:1.1rem;color:rgba(255,255,255,0.92);max-width:820px;margin:0 auto;line-height:1.75;">
            <?= nl2br(htmlspecialchars($pelatihan_hero_desc)) ?>
        </p>
    </div>
</section>

<!-- Main Page Content -->
<section class="py-5" style="background:#F8FAFC;">
    <div class="container">

        <!-- ========================================================================= -->
        <!-- 1. NARASI INFO PELATIHAN LPM UNIKA (CMS ADMIN)                            -->
        <!-- ========================================================================= -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-11 col-xl-10">
                <div class="pelatihan-narrative-card">
                    <div class="d-flex align-items-center gap-3 mb-4 border-bottom pb-3 flex-wrap">
                        <div style="width:52px;height:52px;border-radius:14px;background:rgba(30,58,138,0.08);color:var(--primary);display:flex;align-items:center;justify-content:center;font-size:1.6rem;">
                            <i class="bi bi-mortarboard-fill"></i>
                        </div>
                        <div>
                            <div class="badge bg-primary-subtle text-primary px-3 py-1 rounded-pill fw-bold text-uppercase" style="font-size:0.75rem;letter-spacing:0.5px;">
                                Layanan Penjaminan Mutu &amp; Kemitraan
                            </div>
                            <h2 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:clamp(1.25rem, 2.2vw, 1.55rem);margin:0.25rem 0 0;">
                                Profil &amp; Program Pelatihan Penjaminan Mutu
                            </h2>
                        </div>
                    </div>

                    <!-- Teks Narasi Komprehensif -->
                    <div class="pelatihan-narrative-body">
                        <?php 
                        $paragraphs = preg_split('/\r\n|\r|\n/', trim($pelatihan_narasi_lengkap));
                        $current_p = '';
                        foreach ($paragraphs as $line) {
                            $clean_line = trim($line);
                            if ($clean_line === '') {
                                if ($current_p !== '') {
                                    echo '<p>' . nl2br(htmlspecialchars($current_p)) . '</p>';
                                    $current_p = '';
                                }
                            } else {
                                $current_p .= ($current_p === '' ? '' : "\n") . $clean_line;
                            }
                        }
                        if ($current_p !== '') {
                            echo '<p>' . nl2br(htmlspecialchars($current_p)) . '</p>';
                        }
                        ?>
                    </div>


                </div>
            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 2. BROSUR TERPAMPANG DI HALAMAN + AKSI UNDUH                               -->
        <!-- ========================================================================= -->
        <div class="row justify-content-center mb-5">
            <div class="col-lg-11 col-xl-10">

                <div class="text-center mb-4">
                    <h3 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:clamp(1.4rem, 2.5vw, 1.85rem);margin-bottom:0.4rem;">
                        Brosur &amp; Silabus Program Pelatihan Mutu
                    </h3>
                    <p class="text-muted mx-auto" style="max-width:720px;font-size:0.95rem;">
                        Brosur resmi dapat langsung Anda telaah pada jendela tampilan di bawah ini atau mengunduh dokumen melalui tombol yang tersedia.
                    </p>

                    <!-- Nav Selector jika Brosur Aktif Lebih Dari 1 -->
                    <?php if (count($brosur_list) > 1): ?>
                    <div class="d-flex justify-content-center gap-2 flex-wrap mt-3" id="brosurTabSelector">
                        <?php foreach ($brosur_list as $b_idx => $b_item): ?>
                        <button type="button" class="brosur-tab-btn <?= $b_idx === 0 ? 'active' : '' ?>" onclick="switchBrosur(<?= $b_idx ?>)" id="btnBrosurTab<?= $b_idx ?>">
                            <i class="bi bi-file-earmark-text me-1"></i> <?= htmlspecialchars($b_item['judul_brosur']) ?> (<?= htmlspecialchars($b_item['tahun']) ?>)
                        </button>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>

                <?php if (!empty($brosur_list)): ?>
                    <?php foreach ($brosur_list as $idx => $br): 
                        $f_name = $br['file_brosur'];
                        $f_ext  = strtolower(pathinfo($f_name, PATHINFO_EXTENSION));
                        $f_url  = SITE_URL . '/uploads/layanan/' . rawurlencode($f_name);

                        // Tentukan jenis viewer
                        $is_pdf   = ($f_ext === 'pdf');
                        $is_image = in_array($f_ext, ['png', 'jpg', 'jpeg', 'webp']);
                        $is_docx  = in_array($f_ext, ['docx', 'doc']);

                        if ($is_image) {
                            $format_badge = '<span class="badge bg-success px-3 py-1 rounded-pill"><i class="bi bi-image me-1"></i>FORMAT GAMBAR / POSTER (' . strtoupper($f_ext) . ')</span>';
                            $btn_unduh_text = 'Unduh Brosur (' . strtoupper($f_ext) . ')';
                        } elseif ($is_docx) {
                            $format_badge = '<span class="badge bg-primary px-3 py-1 rounded-pill"><i class="bi bi-file-earmark-word me-1"></i>FORMAT DOKUMEN WORD (' . strtoupper($f_ext) . ')</span>';
                            $btn_unduh_text = 'Unduh Berkas Word (' . strtoupper($f_ext) . ')';
                        } else {
                            $format_badge = '<span class="badge bg-danger px-3 py-1 rounded-pill"><i class="bi bi-file-earmark-pdf me-1"></i>FORMAT RESMI PDF</span>';
                            $btn_unduh_text = 'Unduh Brosur PDF';
                        }
                    ?>
                    <div class="brosur-showcase-card mb-4" id="brosurContainer<?= $idx ?>" style="<?= $idx > 0 ? 'display:none;' : '' ?>">
                        
                        <!-- Toolbar & Action Bar Terintegrasi -->
                        <div class="brosur-toolbar">
                            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-3">
                                <div>
                                    <div class="d-flex align-items-center gap-2 mb-1 flex-wrap">
                                        <?= $format_badge ?>
                                        <span class="badge bg-light text-dark px-2 py-1 rounded-pill fw-semibold" style="font-size:0.75rem;">
                                            EDISI <?= htmlspecialchars($br['tahun']) ?>
                                        </span>
                                        <?php if (!empty($br['ukuran_file'])): ?>
                                        <span class="badge bg-secondary-subtle text-white border px-2 py-1 rounded-pill" style="font-size:0.75rem;">
                                            <i class="bi bi-hdd-fill me-1"></i><?= htmlspecialchars($br['ukuran_file']) ?>
                                        </span>
                                        <?php endif; ?>
                                    </div>
                                    <h4 style="font-family:var(--font-heading);font-weight:800;color:#ffffff;font-size:1.35rem;margin:0.25rem 0 0.2rem;">
                                        <?= htmlspecialchars($br['judul_brosur']) ?>
                                    </h4>
                                    <?php if (!empty($br['deskripsi'])): ?>
                                    <p style="color:rgba(255,255,255,0.85);font-size:0.88rem;margin:0;line-height:1.5;">
                                        <?= htmlspecialchars($br['deskripsi']) ?>
                                    </p>
                                    <?php endif; ?>
                                </div>

                                <!-- Tombol Aksi Unduh & Aksi Lain -->
                                <div class="d-flex flex-wrap gap-2 align-items-center justify-content-md-end flex-shrink-0">
                                    <a href="<?= $f_url ?>" download class="btn btn-warning text-dark fw-bold px-4 py-2 rounded-pill shadow-sm d-inline-flex align-items-center gap-2" style="font-size:0.92rem;">
                                        <i class="bi bi-download"></i> <?= $btn_unduh_text ?>
                                    </a>
                                    <a href="<?= $f_url ?>" target="_blank" class="btn btn-outline-light fw-semibold px-3 py-2 rounded-pill d-inline-flex align-items-center gap-1" style="font-size:0.9rem;" title="Buka berkas di jendela baru">
                                        <i class="bi bi-box-arrow-up-right"></i> Layar Penuh
                                    </a>

                                </div>
                            </div>
                        </div>

                        <!-- Viewer / Tampilan Terpampang Langsung di Halaman -->
                        <div class="brosur-viewer-content">
                            <?php if ($is_pdf): ?>
                                <!-- PDF Terpampang Langsung via Embedded Viewer -->
                                <div class="position-relative">
                                    <iframe src="<?= $f_url ?>#toolbar=1&navpanes=0&scrollbar=1" class="pdf-embed-frame" title="<?= htmlspecialchars($br['judul_brosur']) ?>">
                                        <div class="p-5 text-center bg-light">
                                            <p class="text-muted mb-3">Browser Anda tidak mendukung pratinjau PDF langsung di halaman.</p>
                                            <a href="<?= $f_url ?>" download class="btn btn-primary fw-bold">
                                                <i class="bi bi-download me-1"></i> Unduh Berkas PDF Brosur
                                            </a>
                                        </div>
                                    </iframe>
                                </div>

                            <?php elseif ($is_image): ?>
                                <!-- Gambar Terpampang Langsung -->
                                <div class="text-center p-3 p-md-4 bg-light">
                                    <a href="<?= $f_url ?>" target="_blank" title="Klik untuk memperbesar gambar">
                                        <img src="<?= $f_url ?>" class="img-poster-view img-fluid" alt="<?= htmlspecialchars($br['judul_brosur']) ?>">
                                    </a>
                                    <div class="mt-3 text-muted small">
                                        <i class="bi bi-zoom-in me-1"></i> Klik pada poster untuk membuka resolusi penuh di tab baru.
                                    </div>
                                </div>

                            <?php elseif ($is_docx): ?>
                                <!-- Berkas Word DOCX Terpampang dengan Card Interaktif -->
                                <div class="p-4 p-md-5 bg-white">
                                    <div class="docx-preview-box">
                                        <div style="font-size:3.5rem;color:#2563EB;line-height:1;margin-bottom:1rem;">
                                            <i class="bi bi-file-earmark-word-fill"></i>
                                        </div>
                                        <h5 class="fw-bold text-navy mb-2"><?= htmlspecialchars($br['judul_brosur']) ?></h5>
                                        <p class="text-muted mx-auto mb-4" style="max-width:550px;font-size:0.9rem;">
                                            Dokumen resmi panduan dan formulir pendaftaran pelatihan tersedia dalam format Microsoft Word (.<?= strtoupper($f_ext) ?>). Anda dapat mengunduh dan menyunting sesuai kebutuhan surat tugas institusi Anda.
                                        </p>
                                        <div class="d-flex justify-content-center gap-2 flex-wrap">
                                            <a href="<?= $f_url ?>" download class="btn btn-primary fw-bold px-4 py-2 rounded-pill">
                                                <i class="bi bi-download me-1"></i> Unduh Berkas Word (.<?= strtoupper($f_ext) ?>)
                                            </a>
                                            <a href="https://view.officeapps.live.com/op/view.aspx?src=<?= urlencode($f_url) ?>" target="_blank" class="btn btn-outline-secondary fw-semibold px-4 py-2 rounded-pill">
                                                <i class="bi bi-eye me-1"></i> Baca via Office Viewer Daring
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- Footer Brosur Info -->
                        <div class="px-4 py-3 bg-light border-top d-flex flex-wrap justify-content-between align-items-center gap-2">
                            <span class="text-muted" style="font-size:0.83rem;">
                                <i class="bi bi-shield-check text-success me-1"></i> Dokumen resmi diterbitkan oleh <strong>Lembaga Penjaminan Mutu UNIKA Soegijapranata</strong>.
                            </span>
                            <a href="<?= $f_url ?>" download class="text-decoration-none fw-bold text-primary" style="font-size:0.85rem;">
                                <i class="bi bi-cloud-arrow-down-fill me-1"></i> Klik di sini jika ingin mengunduh langsung berkas ini
                            </a>
                        </div>

                    </div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div class="text-center p-5 bg-white rounded-4 border">
                        <i class="bi bi-file-earmark-text text-muted" style="font-size:3rem;"></i>
                        <h5 class="fw-bold text-navy mt-3">Brosur Pelatihan Segera Dirilis</h5>
                        <p class="text-muted mb-0">Dokumen brosur dan jadwal pelatihan periode terbaru sedang dalam tahap finalisasi administrasi.</p>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- ========================================================================= -->
        <!-- 3. KONSULTASI RESMI & NARAHUBUNG KEMITRAAN                                -->
        <!-- ========================================================================= -->
        <div class="row justify-content-center">
            <div class="col-lg-11 col-xl-10">
                <div class="p-4 p-md-5 rounded-4 text-center text-md-start d-flex flex-column flex-md-row align-items-center justify-content-between gap-4" style="background:linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);color:#ffffff;box-shadow:0 8px 30px rgba(10,25,47,0.15);">
                    <div>
                        <div class="badge bg-warning text-dark px-3 py-1 rounded-pill fw-bold mb-2" style="font-size:0.75rem;">
                            SEKRETARIAT RESMI LPM
                        </div>
                        <h4 style="font-family:var(--font-heading);font-weight:800;font-size:1.35rem;margin-bottom:0.4rem;color:#ffffff;">
                            Konsultasi Kemitraan &amp; Pelatihan Institusi
                        </h4>
                        <p style="font-size:0.88rem;color:rgba(255,255,255,0.85);margin-bottom:0.85rem;max-width:650px;line-height:1.65;">
                            Ingin merancang program pelatihan khusus (In-House Training), bimbingan teknis borang akreditasi, atau verifikasi jadwal narasumber bagi institusi Anda? Tim LPM UNIKA siap mendampingi kebutuhan institusi Anda.
                        </p>
                        <div class="d-flex flex-wrap gap-3 text-white-50" style="font-size:0.82rem;">
                            <span><i class="bi bi-building text-warning me-1"></i> <?= htmlspecialchars($pelatihan_alamat) ?></span>
                            <span><i class="bi bi-clock text-warning me-1"></i> <?= htmlspecialchars($pelatihan_jam) ?></span>
                            <span><i class="bi bi-telephone text-warning me-1"></i> <?= htmlspecialchars($pelatihan_telepon) ?></span>
                        </div>
                    </div>
                    <div class="flex-shrink-0 d-flex flex-wrap gap-2 justify-content-center justify-content-md-end">
                        <a href="mailto:<?= htmlspecialchars($pelatihan_email) ?>?subject=Permohonan%20Konsultasi%20dan%20Kerjasama%20Pelatihan%20LPM%20UNIKA" class="btn btn-warning px-4 py-3 fw-bold rounded-pill text-dark d-inline-flex align-items-center gap-2 shadow" style="font-size:0.95rem;">
                            <i class="bi bi-envelope-fill"></i> Hubungi via Email: <?= htmlspecialchars($pelatihan_email) ?>
                        </a>
                        <a href="https://mail.google.com/mail/?view=cm&fs=1&to=<?= urlencode($pelatihan_email) ?>&su=<?= urlencode('Permohonan Konsultasi dan Kerjasama Pelatihan LPM UNIKA') ?>" target="_blank" class="btn btn-light px-3 py-3 fw-bold rounded-pill text-dark d-inline-flex align-items-center gap-2 shadow-sm" style="font-size:0.92rem;" title="Buka langsung di tab Gmail web">
                            <i class="bi bi-google text-danger"></i> Buka via Gmail
                        </a>
                        <button type="button" class="btn btn-outline-light px-3 py-3 fw-semibold rounded-pill d-inline-flex align-items-center gap-2" onclick="copyEmailLpm('<?= htmlspecialchars($pelatihan_email) ?>', this)" title="Salin alamat email LPM">
                            <i class="bi bi-clipboard"></i> <span class="copy-lbl">Salin</span>
                        </button>
                    </div>
                </div>
            </div>
        </div>

    </div>
</section>

<script>
function switchBrosur(targetIndex) {
    var containers = document.querySelectorAll('.brosur-showcase-card');
    containers.forEach(function(el, idx) {
        if (idx === targetIndex) {
            el.style.display = 'block';
        } else {
            el.style.display = 'none';
        }
    });

    var buttons = document.querySelectorAll('#brosurTabSelector .brosur-tab-btn');
    buttons.forEach(function(btn, idx) {
        if (idx === targetIndex) {
            btn.classList.add('active');
        } else {
            btn.classList.remove('active');
        }
    });
}

function copyEmailLpm(email, btn) {
    if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(email).then(function() {
            var lbl = btn.querySelector('.copy-lbl');
            var icon = btn.querySelector('i');
            if (lbl) lbl.textContent = 'Tersalin!';
            if (icon) icon.className = 'bi bi-check2 text-success';
            setTimeout(function() {
                if (lbl) lbl.textContent = 'Salin';
                if (icon) icon.className = 'bi bi-clipboard';
            }, 2500);
        }).catch(function() {
            prompt('Salin alamat email berikut:', email);
        });
    } else {
        prompt('Salin alamat email berikut:', email);
    }
}
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
