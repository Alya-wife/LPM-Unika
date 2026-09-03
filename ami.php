<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Audit Mutu Internal (AMI)';
$meta_desc  = 'Audit Mutu Internal (AMI) LPM UNIKA: Pedoman, Instrumen, Auditor, Jadwal Siklus, dan Hasil Audit Mutu Internal Universitas Katolik Soegijapranata.';

$db = getDB();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Evaluasi &amp; Pengendalian Mutu
        </div>
        <h1 class="page-banner-title">Audit Mutu Internal (AMI)</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">AMI</span>
        </div>
    </div>
</div>

<!-- ==============================================
     1. PENGANTAR & TENTANG AMI (Bagian Atas)
============================================== -->
<section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);">
    <div class="container">
        <div class="row g-5 align-items-center">
            <div class="col-lg-7">
                <span class="section-tag mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    Evaluasi Mutu
                </span>
                <h2 class="section-title mb-3">Tentang Audit Mutu Internal (AMI)</h2>
                <p style="color:var(--text-main);line-height:1.8;font-size:1rem;margin-bottom:1.25rem;">
                    Audit Mutu Internal (AMI) Universitas Katolik Soegijapranata merupakan proses pengujian yang sistematik, mandiri, dan terdokumentasi untuk memastikan bahwa pelaksanaan penjaminan mutu di seluruh Program Studi dan Unit Kerja telah sesuai dengan Standar SPMI UNIKA.
                </p>
                <p style="color:var(--text-muted);line-height:1.75;font-size:0.95rem;margin-bottom:1.5rem;">
                    AMI bukan kegiatan mencari kesalahan (audit kepatuhan semata), melainkan proses kolaboratif untuk mengidentifikasi potensi peningkatan mutu <em>(opportunity for improvement)</em>, mitigasi risiko akademik, dan kesiapan akreditasi eksternal.
                </p>

                <div class="row g-3">
                    <div class="col-sm-6">
                        <div class="p-3" style="background:var(--bg-main);border-radius:var(--radius-sm);border-left:3px solid var(--purple);">
                            <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.9rem;margin-bottom:0.25rem;">Prinsip Kerja AMI</div>
                            <div style="font-size:0.8rem;color:var(--text-muted);line-height:1.5;">Objektif, profesional, independen, berbasis bukti <em>(evidence-based)</em>, dan berorientasi solusi.</div>
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="p-3" style="background:var(--bg-main);border-radius:var(--radius-sm);border-left:3px solid #1565C0;">
                            <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.9rem;margin-bottom:0.25rem;">Sasaran Audit</div>
                            <div style="font-size:0.8rem;color:var(--text-muted);line-height:1.5;">Seluruh Fakultas, Program Studi, Lembaga Penelitian, Pengabdian, dan Unit Pelaksana Teknis.</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quick Action Box -->
            <div class="col-lg-5">
                <div class="card-lpm p-4" style="background:linear-gradient(135deg, var(--navy), #132D54);color:#fff;border-radius:var(--radius-lg);box-shadow:0 12px 35px rgba(10,25,47,0.18);">
                    <div style="display:flex;align-items:center;gap:12px;margin-bottom:1rem;">
                        <div style="width:48px;height:48px;border-radius:12px;background:rgba(255,255,255,0.15);display:flex;align-items:center;justify-content:center;color:#FFD54F;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="26" height="26">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 9v7.5" />
                            </svg>
                        </div>
                        <div>
                            <h5 style="font-family:var(--font-heading);font-weight:700;color:#fff;margin:0;">Siklus AMI Aktif</h5>
                            <span style="font-size:0.78rem;color:rgba(255,255,255,0.7);">Periode Tahun Akademik Berjalan</span>
                        </div>
                    </div>
                    <p style="font-size:0.85rem;color:rgba(255,255,255,0.8);line-height:1.6;margin-bottom:1.25rem;">
                        Unit kerja yang memerlukan panduan pengisian instrumen audit atau penjadwalan visitasi auditor dapat menghubungi Sekretariat LPM.
                    </p>
                    <div class="d-flex flex-column gap-2">
                        <a href="<?= SITE_URL ?>/dokumen.php?kategori=Instrumen" class="btn-hero-secondary text-center" style="background:rgba(255,255,255,0.15);color:#fff;border:1px solid rgba(255,255,255,0.25);padding:0.65rem 1rem;font-size:0.88rem;">
                            Unduh Instrumen &amp; Form AMI &rarr;
                        </a>
                        <a href="<?= SITE_URL ?>/layanan.php" class="btn-hero-primary justify-content-center" style="background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border:none;padding:0.65rem 1rem;font-size:0.88rem;">
                            Konsultasi Persiapan AMI
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- ==============================================
     2. ALUR DAN TAHAPAN AMI (Scroll ke bawah)
============================================== -->
<section class="py-5" style="background:var(--bg-main);">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tag mb-2">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                </svg>
                Prosedur Kerja
            </span>
            <h2 class="section-title">Alur &amp; Tahapan Siklus AMI</h2>
            <p class="section-desc mx-auto">Tahapan audit yang dijalankan oleh tim LPM dan auditor mutu internal UNIKA.</p>
        </div>

        <div class="row g-4">
            <?php
            $ami_stages = [
                ['1', 'Perencanaan & Sosialisasi', 'Penetapan jadwal siklus audit, penunjukan tim auditor tersertifikasi, dan pengiriman surat pemberitahuan ke seluruh auditee.'],
                ['2', 'Pengisian Dokumen Kinerja (DED)', 'Program Studi dan Unit Kerja mengisi instrumen evaluasi diri dan mengunggah bukti fisik pendukung.'],
                ['3', 'Audit Dokumen (Desk Evaluation)', 'Auditor memeriksa kecukupan dan kesesuaian dokumen bukti kerja terhadap standar mutu sebelum visitasi.'],
                ['4', 'Audit Lapangan (Visitasi)', 'Auditor melakukan verifikasi langsung, wawancara auditee, konfirmasi temuan KTS (Ketidaksesuaian), dan penandatanganan berita acara.'],
                ['5', 'Rapat Tinjauan Manajemen (RTM)', 'Penyampaian rekapitulasi temuan audit kepada Rektorat dan Pimpinan Unit untuk perumusan Rencana Tindak Lanjut (RTL).'],
            ];
            foreach ($ami_stages as $st):
            ?>
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
    </div>
</section>

<!-- ==============================================
     3. INSTRUMEN & AUDITOR MUTU (Scroll ke bawah)
============================================== -->
<section class="py-5" style="background:#ffffff;">
    <div class="container">
        <div class="row g-5">
            <div class="col-lg-6">
                <span class="section-tag mb-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    Pedoman &amp; Instrumen
                </span>
                <h3 class="section-title mb-3" style="font-size:1.5rem;">Pedoman &amp; Instrumen AMI</h3>
                <p style="color:var(--text-muted);font-size:0.9rem;line-height:1.7;margin-bottom:1.5rem;">
                    Instrumen AMI disusun mengacu pada Kriteria SN-Dikti dan Matriks Penilaian Akreditasi LAM/BAN-PT, mencakup bidang Akademik, Tata Pamong, Sumber Daya Manusia, Keuangan, Sarana Prasarana, Penelitian, Pengabdian, serta Luaran Capaian.
                </p>
                <div class="list-group" style="border-radius:var(--radius-md);">
                    <div class="list-group-item p-3 d-flex justify-content-between align-items-center" style="border-color:var(--border);">
                        <div>
                            <div style="font-weight:600;color:var(--navy);font-size:0.9rem;">Pedoman Operasional AMI UNIKA</div>
                            <small class="text-muted">Panduan tata cara pelaksanaan dan kode etik auditor</small>
                        </div>
                        <a href="<?= SITE_URL ?>/dokumen.php?kategori=Panduan" class="btn-action btn-edit">Lihat</a>
                    </div>
                    <div class="list-group-item p-3 d-flex justify-content-between align-items-center" style="border-color:var(--border);">
                        <div>
                            <div style="font-weight:600;color:var(--navy);font-size:0.9rem;">Instrumen Audit Program Studi</div>
                            <small class="text-muted">Formulir evaluasi diri kriteria akademik dan kurikulum</small>
                        </div>
                        <a href="<?= SITE_URL ?>/dokumen.php?kategori=Instrumen" class="btn-action btn-edit">Lihat</a>
                    </div>
                    <div class="list-group-item p-3 d-flex justify-content-between align-items-center" style="border-color:var(--border);">
                        <div>
                            <div style="font-weight:600;color:var(--navy);font-size:0.9rem;">Form Berita Acara &amp; Temuan KTS</div>
                            <small class="text-muted">Form pencatatan tindakan koreksi dan tindak lanjut RTM</small>
                        </div>
                        <a href="<?= SITE_URL ?>/dokumen.php?kategori=Formulir" class="btn-action btn-edit">Lihat</a>
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

<?php require_once __DIR__ . '/includes/footer.php'; ?>
