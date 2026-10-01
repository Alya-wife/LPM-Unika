<?php
require_once __DIR__ . '/config/database.php';
header("Location: " . SITE_URL . "/siklus-ami.php", true, 301);
exit;

$selected_tahun_ami = trim($_GET['tahun_ami'] ?? ($list_tahun_ami[0] ?? '2026/2027'));

$stmt_ami = $db->prepare("SELECT * FROM kalender_ami WHERE is_active = 1 AND tahun_akademik = ? ORDER BY urutan ASC, id ASC");
$stmt_ami->execute([$selected_tahun_ami]);
$kalender_ami_items = $stmt_ami->fetchAll(PDO::FETCH_ASSOC);

$page_title = "Jadwal Pelaksanaan AMI $selected_tahun_ami – Audit Mutu Internal LPM UNIKA";
$meta_desc  = "Jadwal resmi pelaksanaan tahapan Audit Mutu Internal (AMI) Universitas Katolik Soegijapranata Tahun Akademik $selected_tahun_ami.";

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Agenda &amp; Pelaksanaan AMI
        </div>
        <h1 class="page-banner-title">Jadwal Pelaksanaan AMI <?= e($selected_tahun_ami) ?></h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/ami.php">AMI</a>
            <span>/</span>
            <span class="current">Jadwal Pelaksanaan AMI</span>
        </div>
    </div>
</div>

<section class="py-5 py-md-6" style="background:#F8FAFC;">
    <div class="container">

        <!-- Navigasi Histori Periode & Switcher -->
        <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap mb-4">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span style="font-size:0.85rem;color:var(--text-muted);font-weight:700;">Histori Tahun Akademik AMI:</span>
                <?php foreach ($list_tahun_ami as $th): ?>
                <a href="kalender-ami.php?tahun_ami=<?= urlencode($th) ?>" class="btn btn-sm <?= $selected_tahun_ami === $th ? 'btn-primary fw-bold shadow-sm' : 'btn-outline-secondary' ?>" style="border-radius:20px;padding:0.4rem 1.2rem;font-size:0.85rem;">
                    Periode <?= e($th) ?> <?= $th === ($list_tahun_ami[0] ?? '') ? '(Terkini)' : '' ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Tautan silang ke Kalender Mutu Umum -->
            <a href="<?= SITE_URL ?>/kalender-mutu.php" class="btn btn-sm btn-outline-purple" style="border-radius:20px;padding:0.45rem 1.2rem;font-size:0.85rem;font-weight:600;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="15" height="15" class="me-1">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                </svg>
                Lihat Kalender Mutu Akademik &rarr;
            </a>
        </div>

        <!-- Main Schedule Container (Jadwal Pelaksanaan AMI Biasa) -->
        <div class="card-lpm mb-4" style="border-radius:var(--radius-xl);overflow:hidden;box-shadow:0 10px 30px rgba(10,25,47,0.06);border:1px solid var(--border);background:#fff;">
            <!-- Schedule Header -->
            <div style="background:linear-gradient(135deg, #0A192F 0%, #1B263B 100%);color:#ffffff;padding:2rem 2.25rem;border-bottom:4px solid #F59E0B;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <div class="d-inline-flex align-items-center gap-2 mb-2" style="background:rgba(245,158,11,0.18);border:1px solid rgba(245,158,11,0.4);color:#FBBF24;padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:700;letter-spacing:0.5px;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                            </svg>
                            JADWAL PELAKSANAAN RESMI
                        </div>
                        <h2 style="font-family:var(--font-heading);font-weight:800;font-size:clamp(1.3rem, 2.5vw, 1.8rem);color:#ffffff;margin:0 0 0.35rem;">
                            Jadwal Pelaksanaan Audit Mutu Internal (AMI)
                        </h2>
                        <div style="font-size:0.92rem;color:rgba(255,255,255,0.8);">
                            Tahun Akademik <strong><?= e($selected_tahun_ami) ?></strong> &bull; Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata
                        </div>
                    </div>
                    <div>
                        <span class="badge" style="background:rgba(255,255,255,0.12);border:1px solid rgba(255,255,255,0.25);color:#ffffff;padding:8px 16px;border-radius:12px;font-size:0.85rem;font-weight:600;">
                            Total: <?= count($kalender_ami_items) ?> Tahap Kegiatan
                        </span>
                    </div>
                </div>
            </div>

            <!-- Schedule Table / List -->
            <div style="padding:1.5rem 2rem;">
                <?php if (empty($kalender_ami_items)): ?>
                <div class="text-center py-5">
                    <h5 class="text-navy fw-bold">Jadwal Belum Tersedia</h5>
                    <p class="text-muted">Jadwal pelaksanaan AMI untuk periode <strong><?= e($selected_tahun_ami) ?></strong> belum diinputkan.</p>
                    <a href="<?= SITE_URL ?>/admin/kalender-ami-list.php" class="btn btn-sm btn-primary">
                        Kelola di Panel Admin &rarr;
                    </a>
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
                            <tr style="border-bottom:1px solid #E2E8F0;transition:background 0.15s;">
                                <!-- No -->
                                <td style="padding:1.15rem 1.25rem;text-align:center;vertical-align:top;">
                                    <div style="width:32px;height:32px;background:rgba(21,101,192,0.1);color:#1565C0;border-radius:50%;display:inline-flex;align-items:center;justify-content:center;font-weight:800;font-size:0.85rem;">
                                        <?= $no++ ?>
                                    </div>
                                </td>

                                <!-- Tanggal / Waktu -->
                                <td style="padding:1.15rem 1.25rem;vertical-align:top;">
                                    <div style="display:flex;align-items:flex-start;gap:8px;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#1565C0" width="18" height="18" style="flex-shrink:0;margin-top:2px;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                                        </svg>
                                        <div>
                                            <div style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:0.95rem;line-height:1.3;">
                                                <?= e($ami['bulan_tahun']) ?>
                                            </div>
                                            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                                                Periode <?= e($ami['tahun_akademik']) ?>
                                            </div>
                                        </div>
                                    </div>
                                </td>

                                <!-- Keterangan Kegiatan -->
                                <td style="padding:1.15rem 1.25rem;vertical-align:top;">
                                    <ul style="list-style:none;padding:0;margin:0;display:flex;flex-direction:column;gap:0.45rem;">
                                        <?php foreach ($agenda_lines as $act): ?>
                                        <li style="display:flex;align-items:flex-start;gap:8px;font-size:0.9rem;color:var(--text-main);line-height:1.6;">
                                            <span style="color:#F59E0B;font-size:1.2rem;line-height:1;margin-top:2px;">▸</span>
                                            <span><?= e($act) ?></span>
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

                <!-- Footer Note inside card -->
                <div class="mt-4 pt-3 text-center" style="border-top:1px solid var(--border);font-size:0.825rem;color:var(--text-muted);">
                    Seluruh tahapan pelaksanaan Audit Mutu Internal diselenggarakan oleh Lembaga Penjaminan Mutu sesuai dengan Siklus Penjaminan Mutu Internal (SPMI) Universitas Katolik Soegijapranata.
                </div>
            </div>
        </div>

        <?php
        // Query Berita / Kegiatan yang ditandai tampil di Halaman AMI
        $stmt_ami_berita = $db->query("
            SELECT b.*,
            (SELECT COUNT(*) FROM berita_gambar bg WHERE bg.berita_id = b.id) AS total_extra_gambar
            FROM berita b
            WHERE b.status = 'published' AND b.tampil_di_ami = 1
            ORDER BY COALESCE(b.tanggal_publikasi, b.created_at) DESC, b.id DESC
        ");
        $kegiatan_ami_items = $stmt_ami_berita ? $stmt_ami_berita->fetchAll(PDO::FETCH_ASSOC) : [];
        ?>

        <?php if (!empty($kegiatan_ami_items)): ?>
        <style>
        #amiSliderTrack::-webkit-scrollbar {
            height: 6px;
        }
        #amiSliderTrack::-webkit-scrollbar-track {
            background: #E2E8F0;
            border-radius: 10px;
        }
        #amiSliderTrack::-webkit-scrollbar-thumb {
            background: #CBD5E1;
            border-radius: 10px;
        }
        #amiSliderTrack::-webkit-scrollbar-thumb:hover {
            background: #94A3B8;
        }
        .ami-slider-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 12px 25px rgba(10,25,47,0.09) !important;
        }
        </style>

        <!-- Slider Berita & Kegiatan AMI (Scroll Kesamping) -->
        <div class="my-5 pt-2" id="berita-kegiatan-ami">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3 mb-4">
                <div>
                    <div class="d-inline-flex align-items-center gap-2 mb-2" style="background:#FEF3C7;border:1px solid #FCD34D;color:#92400E;padding:4px 12px;border-radius:20px;font-size:0.75rem;font-weight:700;letter-spacing:0.5px;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                        </svg>
                        DOKUMENTASI &amp; KEGIATAN AMI
                    </div>
                    <h3 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:clamp(1.2rem, 2vw, 1.5rem);margin:0 0 0.25rem;">
                        Berita &amp; Dokumentasi Pelaksanaan AMI
                    </h3>
                    <p style="color:var(--text-muted);font-size:0.875rem;margin:0;">
                        Dokumentasi foto dan publikasi kegiatan Audit Mutu Internal LPM SCU. Geser ke samping untuk melihat kegiatan lainnya.
                    </p>
                </div>

                <!-- Tombol Panah Navigasi Slider -->
                <div class="d-flex align-items-center gap-2">
                    <button type="button" class="btn btn-sm btn-light border shadow-sm rounded-circle d-flex align-items-center justify-content-center" id="btnScrollAmiLeft" style="width:40px;height:40px;cursor:pointer;" title="Geser ke Kiri">
                        <i class="bi bi-chevron-left" style="font-size:1.1rem;color:var(--navy);"></i>
                    </button>
                    <button type="button" class="btn btn-sm btn-light border shadow-sm rounded-circle d-flex align-items-center justify-content-center" id="btnScrollAmiRight" style="width:40px;height:40px;cursor:pointer;" title="Geser ke Kanan">
                        <i class="bi bi-chevron-right" style="font-size:1.1rem;color:var(--navy);"></i>
                    </button>
                </div>
            </div>

            <!-- Container Geser Scroll Kesamping -->
            <div class="ami-slider-track" id="amiSliderTrack" style="display:flex;gap:1.5rem;overflow-x:auto;scroll-behavior:smooth;scroll-snap-type:x mandatory;padding-bottom:1.25rem;-webkit-overflow-scrolling:touch;">
                <?php foreach ($kegiatan_ami_items as $b): 
                    $tgl_b = $b['tanggal_publikasi'] ?: $b['created_at'];
                    $ta_b  = getTahunAkademik($tgl_b);
                    $total_foto = ($b['gambar'] ? 1 : 0) + (int)$b['total_extra_gambar'];
                ?>
                <div class="ami-slider-card card-lpm" style="flex:0 0 320px;max-width:320px;scroll-snap-align:start;border-radius:14px;overflow:hidden;border:1px solid var(--border);background:#fff;display:flex;flex-direction:column;box-shadow:0 4px 15px rgba(0,0,0,0.04);transition:transform 0.2s, box-shadow 0.2s;">
                    <!-- Thumbnail Image -->
                    <div style="position:relative;height:185px;background:#F1F5F9;overflow:hidden;">
                        <?php if ($b['gambar'] && file_exists(__DIR__ . '/uploads/berita/' . $b['gambar'])): ?>
                            <img src="<?= SITE_URL ?>/uploads/berita/<?= e($b['gambar']) ?>" alt="<?= e($b['judul']) ?>" style="width:100%;height:100%;object-fit:cover;transition:transform 0.3s;" onmouseover="this.style.transform='scale(1.05)'" onmouseout="this.style.transform='scale(1)'">
                        <?php else: ?>
                            <div style="width:100%;height:100%;display:flex;align-items:center;justify-content:center;background:linear-gradient(135deg, #0A192F 0%, #1565C0 100%);color:#fff;">
                                <i class="bi bi-newspaper" style="font-size:2.5rem;opacity:0.4;"></i>
                            </div>
                        <?php endif; ?>
                        
                        <!-- Badge Kategori Tipe -->
                        <span class="badge" style="position:absolute;top:12px;left:12px;background:rgba(10,25,47,0.85);backdrop-filter:blur(4px);color:#fff;font-size:0.7rem;font-weight:600;padding:0.35rem 0.65rem;border-radius:6px;">
                            <?= e($b['tipe'] ?: 'Kegiatan') ?>
                        </span>

                        <!-- Badge Indikator Foto Slider -->
                        <?php if ($total_foto > 1): ?>
                        <span class="badge bg-dark" style="position:absolute;bottom:10px;right:10px;font-size:0.7rem;padding:0.25rem 0.55rem;border-radius:6px;background:rgba(0,0,0,0.75)!important;backdrop-filter:blur(4px);" title="<?= $total_foto ?> Foto Dokumentasi">
                            <i class="bi bi-images"></i> <?= $total_foto ?> Foto
                        </span>
                        <?php endif; ?>
                    </div>

                    <!-- Card Body -->
                    <div style="padding:1.25rem;display:flex;flex-direction:column;flex-grow:1;">
                        <!-- Tanggal & Tahun Akademik -->
                        <div class="d-flex align-items-center justify-content-between mb-2" style="font-size:0.75rem;color:var(--text-muted);">
                            <span><i class="bi bi-calendar3 me-1"></i><?= formatTanggal($tgl_b) ?></span>
                            <?php if ($ta_b): ?>
                            <span class="badge" style="background:#EDE9FE;color:#6D28D9;font-weight:700;font-size:0.68rem;padding:0.2rem 0.5rem;border-radius:4px;">
                                TA <?= e($ta_b) ?>
                            </span>
                            <?php endif; ?>
                        </div>

                        <!-- Judul Berita -->
                        <h5 style="font-family:var(--font-heading);font-weight:700;font-size:0.98rem;line-height:1.45;margin-bottom:0.6rem;color:var(--navy);">
                            <a href="<?= SITE_URL ?>/berita-detail.php?slug=<?= e($b['slug']) ?>" style="color:inherit;text-decoration:none;">
                                <?= e(mb_substr($b['judul'], 0, 65)) ?><?= mb_strlen($b['judul']) > 65 ? '...' : '' ?>
                            </a>
                        </h5>

                        <!-- Ringkasan Excerpt -->
                        <p style="font-size:0.825rem;color:var(--text-main);line-height:1.6;margin-bottom:1rem;flex-grow:1;">
                            <?= truncate(strip_tags($b['konten']), 85) ?>
                        </p>

                        <!-- Footer Link -->
                        <div style="padding-top:0.75rem;border-top:1px solid #F1F5F9;margin-top:auto;">
                            <a href="<?= SITE_URL ?>/berita-detail.php?slug=<?= e($b['slug']) ?>" class="d-inline-flex align-items-center gap-1" style="font-size:0.82rem;font-weight:700;color:var(--blue);text-decoration:none;">
                                Baca Rincian Kegiatan <i class="bi bi-arrow-right"></i>
                            </a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const track = document.getElementById('amiSliderTrack');
            const btnL  = document.getElementById('btnScrollAmiLeft');
            const btnR  = document.getElementById('btnScrollAmiRight');
            if (track && btnL && btnR) {
                btnL.addEventListener('click', function() {
                    track.scrollBy({ left: -340, behavior: 'smooth' });
                });
                btnR.addEventListener('click', function() {
                    track.scrollBy({ left: 340, behavior: 'smooth' });
                });
            }
        });
        </script>
        <?php endif; ?>

        <!-- Tombol Aksi Persiapan & Instrumen -->
        <div class="card-lpm p-4 text-center mt-4" style="background:#fff;border-radius:var(--radius-md);border:1px solid var(--border);">
            <div class="row align-items-center g-3">
                <div class="col-md-8 text-md-start">
                    <h5 class="fw-bold text-navy mb-1" style="color:var(--navy);">Perlu Instrumen atau Pendampingan AMI?</h5>
                    <p class="text-muted small mb-0">Unit kerja dan program studi dapat mengunduh panduan evaluasi diri atau mengajukan pendampingan langsung ke tim LPM.</p>
                </div>
                <div class="col-md-4 text-md-end d-flex gap-2 justify-content-md-end flex-wrap">
                    <a href="<?= SITE_URL ?>/spmi.php#dokumen-spmi" class="btn btn-sm btn-outline-primary fw-bold px-3 py-2">
                        Unduh Instrumen
                    </a>
                    <a href="<?= SITE_URL ?>/kontak.php" class="btn btn-sm btn-primary fw-bold px-3 py-2">
                        Hubungi LPM &rarr;
                    </a>
                </div>
            </div>
        </div>

    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
