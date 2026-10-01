<?php
/**
 * Modular Sections untuk Halaman Layanan & Kunjungan
 * Terintegrasi dengan Visual Page Builder (advance-setting.php) dan layanan.php publik.
 */

function renderLayananSection($type, $block = [], $is_builder = false, $params = []) {
    $bg = !empty($block['bg_color']) ? "background-color: {$block['bg_color']} !important;" : "";
    $tc = !empty($block['text_color']) ? "color: {$block['text_color']} !important;" : "";

    $active_tab        = $params['active_tab'] ?? 'feedback';
    $success_feedback  = $params['success_feedback'] ?? '';
    $error_feedback    = $params['error_feedback'] ?? '';
    $success_kunjungan = $params['success_kunjungan'] ?? '';
    $error_kunjungan   = $params['error_kunjungan'] ?? '';
    $tujuan_units      = $params['tujuan_units'] ?? [];
    $jam_mulai         = $params['jam_mulai'] ?? '08:00';
    $jam_selesai       = $params['jam_selesai'] ?? '15:00';

    $db = getDB();

    // Load data pendukung untuk sub-bagian Layanan Pelatihan
    try {
        $pelatihan_list = $db->query("SELECT * FROM layanan_pelatihan WHERE is_active = 1 ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $pelatihan_list = []; }

    try {
        $biaya_list = $db->query("SELECT * FROM layanan_biaya WHERE is_active = 1 ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $biaya_list = []; }

    try {
        $jadwal_list = $db->query("SELECT * FROM layanan_jadwal ORDER BY urutan ASC, tanggal_mulai DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $jadwal_list = []; }

    try {
        $brosur_list = $db->query("SELECT * FROM layanan_brosur WHERE is_active = 1 ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $brosur_list = []; }

    switch ($type) {
        case 'layanan_cards':
            ?>
            <section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="text-center mb-5">
                        <h2 class="section-title" style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:clamp(1.5rem,3vw,2rem);margin-bottom:0.5rem;">
                            <?= htmlspecialchars($block['title'] ?? 'Layanan & Kemitraan Mutu LPM UNIKA') ?>
                        </h2>
                        <p class="section-desc mx-auto" style="font-size:0.95rem;color:var(--text-muted);max-width:760px;line-height:1.65;">
                            <?= htmlspecialchars($block['subtitle'] ?? 'LPM Universitas Katolik Soegijapranata menyediakan 3 kanal utama pelayanan: Program Pelatihan Eksternal, Pengajuan Kritik & Saran, serta Evaluasi Kepuasan Feedback Kunjungan Mitra.') ?>
                        </p>
                    </div>

                    <!-- 3 Kartu Layanan Sesuai 3 Sub Menu -->
                    <div class="row g-4 justify-content-center">
                        <!-- Sub Menu 1: Pelatihan Eksternal -->
                        <div class="col-md-6 col-lg-4">
                            <div class="card-lpm p-4 text-center h-100 d-flex flex-column" style="background:#ffffff;border:1px solid var(--border);border-top:4px solid #7C3AED;border-radius:var(--radius-md);box-shadow:0 2px 12px rgba(10,25,47,0.04);">
                                <div style="width:58px;height:58px;border-radius:50%;background:rgba(124,58,237,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;color:#7C3AED;font-size:1.6rem;">
                                    <i class="bi bi-mortarboard-fill"></i>
                                </div>
                                <h4 style="font-family:var(--font-heading);font-size:1.15rem;font-weight:800;color:var(--navy);margin-bottom:0.5rem;">Pelatihan Eksternal</h4>
                                <p style="font-size:0.875rem;color:var(--text-muted);line-height:1.7;margin-bottom:1.25rem;">
                                    Program sertifikasi auditor AMI, pelatihan SPMI PPEPP, klinik borang LED/LKPS, informasi biaya, jadwal terlaksana, dan unduh brosur resmi.
                                </p>
                                <div class="mt-auto">
                                    <a href="<?= SITE_URL ?>/pelatihan-eksternal.php" class="btn btn-sm btn-outline-primary fw-bold rounded-pill w-100 py-2">
                                        Lihat Pelatihan Eksternal &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Sub Menu 2: Kritik & Saran -->
                        <div class="col-md-6 col-lg-4">
                            <div class="card-lpm p-4 text-center h-100 d-flex flex-column" style="background:#ffffff;border:1px solid var(--border);border-top:4px solid #0284C7;border-radius:var(--radius-md);box-shadow:0 2px 12px rgba(10,25,47,0.04);">
                                <div style="width:58px;height:58px;border-radius:50%;background:rgba(2,132,199,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;color:#0284C7;font-size:1.6rem;">
                                    <i class="bi bi-chat-left-heart-fill"></i>
                                </div>
                                <h4 style="font-family:var(--font-heading);font-size:1.15rem;font-weight:800;color:var(--navy);margin-bottom:0.5rem;">Kritik &amp; Saran</h4>
                                <p style="font-size:0.875rem;color:var(--text-muted);line-height:1.7;margin-bottom:1.25rem;">
                                    Kanal penyampaian kritik konstruktif, saran perbaikan mutu kelembagaan, aspirasi kemitraan, serta formulir permohonan kunjungan resmi.
                                </p>
                                <div class="d-flex flex-wrap gap-2 mt-auto justify-content-center">
                                    <button type="button" class="btn btn-sm btn-primary fw-bold rounded-pill flex-grow-1 py-2" onclick="switchLayananTab('feedback'); document.getElementById('layanan-form-wrap')?.scrollIntoView({behavior:'smooth'});">
                                        <i class="bi bi-chat-left-heart me-1"></i> Isi Kritik &amp; Saran
                                    </button>
                                    <button type="button" class="btn btn-sm btn-outline-secondary fw-bold rounded-pill flex-grow-1 py-2" onclick="switchLayananTab('kunjungan'); document.getElementById('layanan-form-wrap')?.scrollIntoView({behavior:'smooth'});">
                                        <i class="bi bi-calendar-plus me-1"></i> Form Kunjungan
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Sub Menu 3: Feedback Kunjungan -->
                        <div class="col-md-6 col-lg-4">
                            <div class="card-lpm p-4 text-center h-100 d-flex flex-column" style="background:#ffffff;border:1px solid var(--border);border-top:4px solid #D97706;border-radius:var(--radius-md);box-shadow:0 2px 12px rgba(10,25,47,0.04);">
                                <div style="width:58px;height:58px;border-radius:50%;background:rgba(217,119,6,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;color:#D97706;font-size:1.6rem;">
                                    <i class="bi bi-shield-lock-fill"></i>
                                </div>
                                <h4 style="font-family:var(--font-heading);font-size:1.15rem;font-weight:800;color:var(--navy);margin-bottom:0.5rem;">Feedback Kunjungan</h4>
                                <p style="font-size:0.875rem;color:var(--text-muted);line-height:1.7;margin-bottom:1.25rem;">
                                    Pengisian kuesioner evaluasi dan kepuasan pelayanan bagi institusi tamu yang telah selesai berkunjung ke LPM UNIKA berbasis kode token resmi.
                                </p>
                                <div class="mt-auto">
                                    <a href="<?= SITE_URL ?>/feedback-kunjungan.php" class="btn btn-sm btn-warning text-dark fw-bold rounded-pill w-100 py-2 shadow-sm">
                                        Buka Feedback Kunjungan &rarr;
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'layanan_form':
            ?>
            <section class="py-5" id="layanan-form-wrap" style="background:var(--bg-main);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-11 col-xl-10">

                            <?php if ($active_tab === 'kunjungan'): ?>
                            <!-- ============================================== -->
                            <!-- SEKSI 1: PELAYANAN PERMOHONAN KUNJUNGAN KE LPM -->
                            <!-- ============================================== -->
                            <?php
                            $kj_badge            = getPengaturan('form_kunjungan_badge', 'PELAYANAN KUNJUNGAN INSTANSI LUAR');
                            $kj_title            = getPengaturan('form_kunjungan_title', 'Pelayanan Kunjungan ke Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata');
                            $kj_desc             = getPengaturan('form_kunjungan_desc', 'Diperuntukkan bagi universitas, sekolah, atau instansi luar yang ingin melakukan studi banding, kunjungan kerja, atau benchmarking ke Lembaga Penjaminan Mutu (LPM) UNIKA Soegijapranata.');
                            $kj_jam_mulai        = getPengaturan('kunjungan_jam_mulai', '08:00');
                            $kj_jam_selesai      = getPengaturan('kunjungan_jam_selesai', '15:00');
                            $kj_max_peserta      = (int)getPengaturan('kunjungan_max_peserta', '20');
                            $kj_jadwal_mode      = getPengaturan('kunjungan_jadwal_mode', 'jumat_minggu_4_flexible');
                            $kj_min_lead_days    = (int)getPengaturan('kunjungan_min_lead_days', '14');
                            $kj_narasi_fasilitas = getPengaturan('kunjungan_narasi_fasilitas', 'Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata menyediakan fasilitas bagi perguruan tinggi/lembaga yang hendak melaksanakan studi banding/benchmarking terkait pengelolaan penjaminan mutu, SPMI, AMI ataupun akreditasi.');
                            $kj_narasi_jadwal    = getPengaturan('kunjungan_narasi_jadwal', 'Studi Banding dijadwalkan di setiap Jumat Minggu ke-IV.');
                            $kj_narasi_prosedur  = getPengaturan('kunjungan_narasi_prosedur', 'Bagi perguruan tinggi/lembaga yang akan melaksanakan studi banding ke Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata dapat mengajukan permohonan terlebih dahulu dengan melengkapi formulir online yang disediakan di halaman ini.');
                            $kj_narasi_catatan   = getPengaturan('kunjungan_narasi_catatan', 'Penyampaian permohonan kunjungan studi banding paling lambat 2 minggu sebelum kegiatan berlangsung.');
                            $kj_file_label       = getPengaturan('form_kunjungan_file_label', 'Upload Surat Permohonan Kunjungan Resmi (PDF, Max 10 MB)');
                            $kj_btn              = getPengaturan('form_kunjungan_btn_text', 'Kirim Permohonan Kunjungan Resmi');

                            $min_date_val = date('Y-m-d', strtotime("+{$kj_min_lead_days} days"));
                            ?>
                            <div id="sub-page-kunjungan">

                                <!-- 1. NARASI & PROSEDUR PENGAJUAN STUDI BANDING (SEBELUM FORMULIR) -->
                                <div class="card-lpm p-4 p-md-4 mb-4" style="background:#ffffff;border:1px solid #E2E8F0;border-left:5px solid #7C3AED;border-radius:18px;box-shadow:0 4px 18px rgba(10,25,47,0.04);">
                                    <div class="d-flex align-items-start gap-3">
                                        <div style="width:48px;height:48px;border-radius:14px;background:rgba(124,58,237,0.1);color:#7C3AED;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">
                                            <i class="bi bi-bank2"></i>
                                        </div>
                                        <div style="flex-grow:1;">
                                            <h4 style="font-family:var(--font-heading);font-size:1.18rem;font-weight:800;color:var(--navy);margin-bottom:0.45rem;">
                                                Fasilitas Studi Banding &amp; Benchmarking Mutu
                                            </h4>
                                            <p style="font-size:0.92rem;color:#475569;line-height:1.75;margin-bottom:0.85rem;">
                                                <?= nl2br(htmlspecialchars($kj_narasi_fasilitas)) ?>
                                            </p>
                                            
                                            <div class="d-inline-flex align-items-center gap-2 px-3 py-2 mb-3" style="background:#F5F3FF;border:1px solid #DDD6FE;border-radius:10px;font-size:0.88rem;color:#6D28D9;font-weight:700;">
                                                <i class="bi bi-calendar2-week-fill" style="color:#7C3AED;"></i>
                                                <span><?= htmlspecialchars($kj_narasi_jadwal) ?></span>
                                            </div>

                                            <div class="pt-3 border-top" style="border-color:#F1F5F9 !important;">
                                                <h5 style="font-family:var(--font-heading);font-size:0.98rem;font-weight:700;color:var(--navy);margin-bottom:0.4rem;">
                                                    <i class="bi bi-file-earmark-text-fill text-primary me-1"></i> Prosedur Pengajuan Studi Banding
                                                </h5>
                                                <p style="font-size:0.88rem;color:#475569;line-height:1.7;margin-bottom:0.65rem;">
                                                    <?= nl2br(htmlspecialchars($kj_narasi_prosedur)) ?>
                                                </p>
                                                <div class="d-flex align-items-center gap-2 p-2 px-3" style="background:#FFFBEB;border:1px solid #FDE68A;border-radius:8px;font-size:0.84rem;color:#92400E;">
                                                    <i class="bi bi-exclamation-circle-fill text-warning flex-shrink-0"></i>
                                                    <span><strong>Catatan:</strong> <?= htmlspecialchars($kj_narasi_catatan) ?></span>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 2. KARTU FORMULIR RESMI KUNJUNGAN -->
                                <div class="card-lpm p-4 p-md-5" style="background:#ffffff;border:1px solid #E2E8F0;border-radius:24px;box-shadow:0 10px 40px rgba(10,25,47,0.06);">
                                    
                                    <!-- Header Title (Label ungu & deskripsi dihapus sesuai permintaan) -->
                                    <div class="text-center mb-4">
                                        <h2 style="font-family:var(--font-heading);font-weight:800;color:#0F172A;font-size:clamp(1.25rem, 2.5vw, 1.65rem);line-height:1.35;max-width:760px;margin:0 auto;">
                                            <?= htmlspecialchars($kj_title) ?>
                                        </h2>
                                    </div>

                                    <?php if ($success_kunjungan): ?>
                                    <div class="alert-lpm alert-success mb-4 d-flex align-items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        <div><?= htmlspecialchars($success_kunjungan) ?></div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($error_kunjungan): ?>
                                    <div class="alert-lpm alert-danger mb-4 d-flex align-items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                        </svg>
                                        <div><?= htmlspecialchars($error_kunjungan) ?></div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Catatan Ketentuan Jumlah Peserta & Jam Operasional (Disederhanakan Jadi Satu) -->
                                    <div class="p-3 mb-4 d-flex align-items-start gap-3" style="background:#F8FAFC;border:1px solid #E2E8F0;border-left:4px solid #0284C7;border-radius:12px;">
                                        <div style="color:#0284C7;font-size:1.25rem;flex-shrink:0;margin-top:2px;">
                                            <i class="bi bi-info-circle-fill"></i>
                                        </div>
                                        <div style="font-size:0.86rem;color:#334155;line-height:1.65;">
                                            <div style="font-weight:700;color:#0F172A;margin-bottom:4px;font-size:0.88rem;">
                                                Ketentuan Permohonan Kunjungan:
                                            </div>
                                            <ul style="margin:0;padding-left:1.15rem;font-size:0.84rem;color:#475569;">
                                                <li>Jumlah peserta kunjungan yang diinputkan langsung pada formulir ini <strong>dibatasi maksimal <?= $kj_max_peserta ?> orang</strong>.</li>
                                                <li>Jam pelaksanaan kunjungan resmi yang dapat dijadwalkan berada pada kurun waktu <strong><?= htmlspecialchars($kj_jam_mulai) ?> s.d. <?= htmlspecialchars($kj_jam_selesai) ?> WIB</strong>.</li>
                                            </ul>
                                        </div>
                                    </div>

                                    <!-- Formulir Pendaftaran -->
                                    <form method="POST" enctype="multipart/form-data" id="formPermohonanKunjungan">
                                        <input type="hidden" name="action_form" value="kunjungan">
                                        
                                        <div class="row g-3">
                                            <!-- Row 1: Nama Institusi & Email Official -->
                                            <div class="col-md-6">
                                                <label class="form-label" style="font-weight:700;font-size:0.86rem;color:#1E293B;">
                                                    Nama Institusi / Perguruan Tinggi Pengunjung <span style="color:#DC2626;">*</span>
                                                </label>
                                                <input type="text" name="nama_institusi" class="form-control form-control-kunjungan" placeholder="Contoh: Universitas Sanata Dharma" value="<?= htmlspecialchars($_POST['nama_institusi'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" style="font-weight:700;font-size:0.86rem;color:#1E293B;">
                                                    Email Official / Resmi Institusi <span style="color:#DC2626;">*</span>
                                                </label>
                                                <input type="email" name="email" class="form-control form-control-kunjungan" placeholder="humas@institusi.ac.id" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                            </div>

                                            <!-- Row 2: Tanggal Kunjungan & Waktu Kunjungan -->
                                            <div class="col-md-6">
                                                <label class="form-label" style="font-weight:700;font-size:0.86rem;color:#1E293B;">
                                                    Tanggal Kunjungan <span style="color:#DC2626;">*</span>
                                                </label>
                                                <input type="date" name="tanggal_kunjungan" id="input_tanggal_kunjungan" class="form-control form-control-kunjungan" min="<?= $min_date_val ?>" value="<?= htmlspecialchars($_POST['tanggal_kunjungan'] ?? '') ?>" required>
                                                <div class="form-text" id="tanggal_helper_text" style="font-size:0.77rem;color:#64748B;">
                                                    <?php if ($kj_jadwal_mode === 'jumat_minggu_4_strict'): ?>
                                                        <span style="color:#6D28D9;font-weight:600;"><i class="bi bi-info-circle me-1"></i>Hanya diperkenankan hari Jumat Minggu ke-IV (minimal <?= $kj_min_lead_days ?> hari ke depan).</span>
                                                    <?php else: ?>
                                                        Pilih tanggal pelaksanaan kunjungan di masa mendatang (disarankan Jumat Minggu ke-IV).
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" style="font-weight:700;font-size:0.86rem;color:#1E293B;">
                                                    Waktu / Jam Kunjungan <span style="color:#DC2626;">*</span>
                                                </label>
                                                <input type="time" name="waktu_kunjungan" class="form-control form-control-kunjungan" value="<?= htmlspecialchars($_POST['waktu_kunjungan'] ?? $kj_jam_mulai) ?>" required>
                                                <div class="form-text" style="font-size:0.77rem;color:#64748B;">
                                                    Jam buka kunjungan: <?= htmlspecialchars($kj_jam_mulai) ?> - <?= htmlspecialchars($kj_jam_selesai) ?> WIB.
                                                </div>
                                            </div>

                                            <!-- Row 3: Perihal & Jumlah Peserta -->
                                            <div class="col-md-7 col-lg-8">
                                                <label class="form-label" style="font-weight:700;font-size:0.86rem;color:#1E293B;">
                                                    Perihal / Topik Kunjungan ke LPM <span style="color:#DC2626;">*</span>
                                                </label>
                                                <input type="text" name="perihal" class="form-control form-control-kunjungan" placeholder="Contoh: Studi Banding &amp; Benchmarking Pengelolaan Penjaminan Mutu" value="<?= htmlspecialchars($_POST['perihal'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-5 col-lg-4">
                                                <label class="form-label" style="font-weight:700;font-size:0.86rem;color:#1E293B;">
                                                    Jumlah Peserta (Maks. <?= $kj_max_peserta ?> di form) <span style="color:#DC2626;">*</span>
                                                </label>
                                                <input type="number" name="jumlah_peserta" id="input_jumlah_peserta" class="form-control form-control-kunjungan" min="1" max="<?= $kj_max_peserta ?>" value="<?= htmlspecialchars($_POST['jumlah_peserta'] ?? '1') ?>" required>
                                                <div class="form-text" style="font-size:0.77rem;color:#64748B;">
                                                    Maksimal <?= $kj_max_peserta ?> orang pada input formulir.
                                                </div>
                                            </div>

                                            <!-- Dynamic Audiensi Container -->
                                            <div class="col-12">
                                                <div class="p-3 p-md-4 mt-2 mb-2" style="background:#F8FAFC;border:1.5px dashed #CBD5E1;border-radius:16px;">
                                                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                                                        <div style="font-weight:700;color:#1E293B;font-size:0.9rem;">
                                                            Daftar Nama Audiensi &amp; Jabatan Peserta Kunjungan (Maksimal <?= $kj_max_peserta ?> Peserta) <span style="color:#DC2626;">*</span>
                                                        </div>
                                                        <div class="d-flex align-items-center gap-2">
                                                            <span id="badge_peserta_count" class="badge" style="background:#E2E8F0;color:#334155;font-size:0.78rem;font-weight:600;padding:6px 12px;border-radius:20px;">
                                                                1 Peserta (Maksimal <?= $kj_max_peserta ?>)
                                                            </span>
                                                            <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2" style="font-size:0.75rem;border-radius:6px;font-weight:600;" onclick="tambahPesertaBtn()" title="Tambah 1 Baris Peserta">
                                                                <i class="bi bi-plus-lg"></i> Tambah
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2" style="font-size:0.75rem;border-radius:6px;font-weight:600;" onclick="kurangiPesertaBtn()" title="Hapus 1 Baris Terakhir">
                                                                <i class="bi bi-dash-lg"></i> Hapus
                                                            </button>
                                                        </div>
                                                    </div>

                                                    <div id="audiensi_rows_container">
                                                        <!-- Dynamic Rows Injected by JS -->
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Row PIC: Nama PIC & Telepon PIC -->
                                            <div class="col-md-6">
                                                <label class="form-label" style="font-weight:700;font-size:0.86rem;color:#1E293B;">
                                                    Nama PIC / Penanggung Jawab Rombongan <span style="color:#DC2626;">*</span>
                                                </label>
                                                <input type="text" name="nama_pic" class="form-control form-control-kunjungan" placeholder="Nama Lengkap PIC" value="<?= htmlspecialchars($_POST['nama_pic'] ?? '') ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" style="font-weight:700;font-size:0.86rem;color:#1E293B;">
                                                    Nomor Telepon / WhatsApp PIC <span style="color:#DC2626;">*</span>
                                                </label>
                                                <input type="text" name="telepon_pic" class="form-control form-control-kunjungan" placeholder="Contoh: 08123456789" value="<?= htmlspecialchars($_POST['telepon_pic'] ?? '') ?>" required>
                                            </div>

                                            <!-- Row Upload Surat Permohonan Resmi -->
                                            <div class="col-12">
                                                <label class="form-label" style="font-weight:700;font-size:0.86rem;color:#1E293B;">
                                                    <?= htmlspecialchars($kj_file_label) ?> <span style="color:#DC2626;">*</span>
                                                </label>
                                                <input type="file" name="surat_permohonan" class="form-control form-control-kunjungan" accept=".pdf" required style="padding:0.6rem 0.85rem;">
                                                <div class="form-text" style="font-size:0.78rem;color:#64748B;margin-top:6px;line-height:1.5;">
                                                    Upload surat resmi berkepala surat institusi bertandatangan/stempel (Format PDF, maksimal 10 MB).
                                                </div>
                                            </div>

                                            <!-- Submit Button -->
                                            <div class="col-12 text-end mt-4">
                                                <button type="submit" class="btn btn-kunjungan-kirim shadow">
                                                    <?= htmlspecialchars($kj_btn) ?>
                                                </button>
                                            </div>
                                        </div>
                                    </form>

                                </div>
                            </div>

                            <style>
                                .form-control-kunjungan {
                                    border: 1.5px solid #E2E8F0;
                                    border-radius: 10px;
                                    padding: 0.68rem 1rem;
                                    font-size: 0.9rem;
                                    color: #1E293B;
                                    background: #FFFFFF;
                                    transition: all 0.2s ease;
                                }
                                .form-control-kunjungan:focus {
                                    border-color: #7C3AED;
                                    box-shadow: 0 0 0 3px rgba(124, 58, 237, 0.12);
                                    outline: none;
                                }
                                .btn-kunjungan-kirim {
                                    background: linear-gradient(135deg, #1E1B4B 0%, #3B0764 50%, #581C87 100%);
                                    color: #ffffff !important;
                                    font-weight: 700;
                                    font-size: 0.94rem;
                                    border-radius: 50px;
                                    padding: 0.8rem 2.2rem;
                                    border: none;
                                    box-shadow: 0 4px 18px rgba(59, 7, 100, 0.35);
                                    transition: all 0.25s ease;
                                    display: inline-flex;
                                    align-items: center;
                                    justify-content: center;
                                }
                                .btn-kunjungan-kirim:hover {
                                    transform: translateY(-2px);
                                    box-shadow: 0 8px 24px rgba(59, 7, 100, 0.45);
                                    filter: brightness(1.1);
                                }
                                .audiensi-num-badge {
                                    color: #64748B;
                                    font-weight: 800;
                                    font-size: 0.85rem;
                                    width: 32px;
                                    text-align: center;
                                    display: inline-block;
                                }
                            </style>

                            <script>
                            (function() {
                                var maxPesertaLimit = <?= $kj_max_peserta ?>;
                                var jadwalMode = <?= json_encode($kj_jadwal_mode) ?>;
                                var minLeadDays = <?= $kj_min_lead_days ?>;

                                var initialNamaArr = <?= json_encode($_POST['nama_audiensi'] ?? []) ?>;
                                var initialJabatanArr = <?= json_encode($_POST['jabatan_audiensi'] ?? []) ?>;

                                var numInput = document.getElementById('input_jumlah_peserta');
                                var container = document.getElementById('audiensi_rows_container');
                                var badgeCount = document.getElementById('badge_peserta_count');
                                var dateInput = document.getElementById('input_tanggal_kunjungan');

                                function getStoredValues() {
                                    var values = [];
                                    var rows = container.querySelectorAll('.audiensi-row-item');
                                    rows.forEach(function(row) {
                                        var namaInp = row.querySelector('.input-audiensi-nama');
                                        var jabatanInp = row.querySelector('.input-audiensi-jabatan');
                                        values.push({
                                            nama: namaInp ? namaInp.value : '',
                                            jabatan: jabatanInp ? jabatanInp.value : ''
                                        });
                                    });
                                    return values;
                                }

                                function renderRows(targetCount) {
                                    var currentValues = getStoredValues();
                                    targetCount = Math.max(1, Math.min(maxPesertaLimit, parseInt(targetCount) || 1));
                                    
                                    if (numInput) numInput.value = targetCount;
                                    if (badgeCount) badgeCount.textContent = targetCount + ' Peserta (Maksimal ' + maxPesertaLimit + ')';

                                    var html = '';
                                    for (var i = 1; i <= targetCount; i++) {
                                        var defaultNama = (currentValues[i - 1] && currentValues[i - 1].nama !== '') ? currentValues[i - 1].nama : (initialNamaArr[i - 1] || '');
                                        var defaultJabatan = (currentValues[i - 1] && currentValues[i - 1].jabatan !== '') ? currentValues[i - 1].jabatan : (initialJabatanArr[i - 1] || '');

                                        html += '<div class="row g-2 mb-2 align-items-center audiensi-row-item">' +
                                                    '<div class="col-auto">' +
                                                        '<span class="audiensi-num-badge">#' + i + '</span>' +
                                                    '</div>' +
                                                    '<div class="col-md-6 col-12">' +
                                                        '<input type="text" name="nama_audiensi[]" class="form-control form-control-kunjungan input-audiensi-nama" placeholder="Nama Lengkap Audiensi #' + i + '" value="' + defaultNama.replace(/"/g, '&quot;') + '" required>' +
                                                    '</div>' +
                                                    '<div class="col-md col-12">' +
                                                        '<input type="text" name="jabatan_audiensi[]" class="form-control form-control-kunjungan input-audiensi-jabatan" placeholder="Jabatan #' + i + ' (misal: Dekan / Dosen)" value="' + defaultJabatan.replace(/"/g, '&quot;') + '" required>' +
                                                    '</div>' +
                                                '</div>';
                                    }
                                    container.innerHTML = html;
                                }

                                if (numInput) {
                                    numInput.addEventListener('input', function() {
                                        var val = parseInt(this.value);
                                        if (!isNaN(val)) {
                                            renderRows(val);
                                        }
                                    });
                                    numInput.addEventListener('change', function() {
                                        var val = parseInt(this.value);
                                        if (isNaN(val) || val < 1) val = 1;
                                        if (val > maxPesertaLimit) val = maxPesertaLimit;
                                        renderRows(val);
                                    });
                                }

                                window.tambahPesertaBtn = function() {
                                    var current = parseInt(numInput.value) || 1;
                                    if (current < maxPesertaLimit) {
                                        renderRows(current + 1);
                                    } else {
                                        alert('Jumlah peserta formulir dibatasi maksimal ' + maxPesertaLimit + ' orang.');
                                    }
                                };

                                window.kurangiPesertaBtn = function() {
                                    var current = parseInt(numInput.value) || 1;
                                    if (current > 1) {
                                        renderRows(current - 1);
                                    }
                                };

                                // Initial Render
                                var startCount = parseInt(numInput ? numInput.value : 1) || 1;
                                if (initialNamaArr.length > startCount) {
                                    startCount = Math.min(maxPesertaLimit, initialNamaArr.length);
                                }
                                renderRows(startCount);

                                // Strict Date Check handler
                                if (dateInput && jadwalMode === 'jumat_minggu_4_strict') {
                                    dateInput.addEventListener('change', function() {
                                        if (!this.value) return;
                                        var parts = this.value.split('-');
                                        if (parts.length === 3) {
                                            var d = new Date(parseInt(parts[0]), parseInt(parts[1]) - 1, parseInt(parts[2]));
                                            var dayOfWeek = d.getDay(); // 5 = Friday
                                            var dateNum = d.getDate();
                                            // Friday week 4 is always date 22 to 28
                                            if (dayOfWeek !== 5 || dateNum < 22 || dateNum > 28) {
                                                alert('Perhatian: Sesuai kebijakan resmi LPM UNIKA, studi banding dijadwalkan khusus pada setiap hari Jumat Minggu ke-IV. Mohon pilih tanggal hari Jumat di antara tanggal 22 sampai 28.');
                                            }
                                        }
                                    });
                                }
                            })();
                            </script>

                            </div>
                            <?php else: ?>
                            <!-- ============================================== -->
                            <!-- SEKSI 2: KRITIK SARAN & FEEDBACK MUTU          -->
                            <!-- ============================================== -->
                            <div id="sub-page-feedback">
                                <div class="card-lpm p-4 p-md-5" style="background:#ffffff;border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:0 4px 20px rgba(10,25,47,0.05);">
                                    <div class="text-center mb-4">
                                        <!-- Judul langsung tanpa badge per Poin 20 -->
                                        <h3 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.45rem;margin-bottom:0.4rem;">
                                            Kritik Saran &amp; Feedback Mutu
                                        </h3>
                                        <p style="font-size:0.875rem;color:var(--text-muted);max-width:680px;" class="mx-auto">
                                            Sampaikan kritik konstruktif, saran perbaikan tata kelola, apresiasi, atau umpan balik mutu dari institusi Anda kepada Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata.
                                        </p>
                                    </div>

                                    <?php if ($success_feedback): ?>
                                    <div class="alert-lpm alert-success mb-4 d-flex align-items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                        </svg>
                                        <div><?= htmlspecialchars($success_feedback) ?></div>
                                    </div>
                                    <?php endif; ?>

                                    <?php if ($error_feedback): ?>
                                    <div class="alert-lpm alert-danger mb-4 d-flex align-items-center gap-2">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                        </svg>
                                        <div><?= htmlspecialchars($error_feedback) ?></div>
                                    </div>
                                    <?php endif; ?>

                                    <form method="POST">
                                        <input type="hidden" name="action_form" value="feedback">
                                        <div class="row g-3">
                                            <!-- Poin 19: Khusus Nama Instansi beserta contohnya -->
                                            <div class="col-md-6">
                                                <label class="form-label" style="font-weight:600;font-size:0.85rem;color:var(--navy);">
                                                    Nama Instansi / Institusi <span style="color:#C62828;">*</span>
                                                </label>
                                                <input type="text" name="instansi" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Universitas Katolik Widya Mandala / LLDIKTI Wilayah VI / Lembaga Mitra" value="<?= htmlspecialchars($_POST['instansi'] ?? ($_POST['nama'] ?? '')) ?>" required>
                                            </div>
                                            <div class="col-md-6">
                                                <label class="form-label" style="font-weight:600;font-size:0.85rem;color:var(--navy);">
                                                    Alamat Email Kontak / PIC <span style="color:#C62828;">*</span>
                                                </label>
                                                <input type="email" name="email" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="kontak@instansi.ac.id" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label" style="font-weight:600;font-size:0.85rem;color:var(--navy);">Kategori Masukan</label>
                                                <select name="jenis_layanan" class="form-select" style="border:1.5px solid var(--border);padding:0.7rem 1rem;">
                                                    <option value="Kritik &amp; Saran Pelayanan">Kritik &amp; Saran Pelayanan</option>
                                                    <option value="Saran &amp; Masukan Perbaikan LPM">Saran &amp; Masukan Perbaikan LPM</option>
                                                    <option value="Aspirasi Peningkatan Mutu">Aspirasi Peningkatan Mutu</option>
                                                    <option value="Feedback / Umpan Balik Kemitraan">Feedback / Umpan Balik Kemitraan</option>
                                                    <option value="Apresiasi Layanan &amp; Testimoni">Apresiasi Layanan &amp; Testimoni</option>
                                                </select>
                                            </div>

                                            <div class="col-12">
                                                <label class="form-label" style="font-weight:600;font-size:0.85rem;color:var(--navy);">Isi Pesan / Masukan <span style="color:#C62828;">*</span></label>
                                                <textarea name="pesan" rows="5" class="form-control" style="border:1.5px solid var(--border);padding:0.75rem 1rem;line-height:1.7;" placeholder="Tuliskan kritik, saran, atau masukan perbaikan Anda..." required><?= htmlspecialchars($_POST['pesan'] ?? '') ?></textarea>
                                            </div>

                                            <div class="col-12 text-end mt-3">
                                                <button type="submit" class="btn btn-primary px-4 py-2" style="background:var(--navy);border:none;font-weight:700;border-radius:50px;">
                                                    Kirim Masukan &rarr;
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                </div>
                            </div>
                            <?php endif; ?>

                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;
    }
}
