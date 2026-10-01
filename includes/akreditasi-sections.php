<?php
/**
 * Modular Akreditasi Sections Renderer
 * Digunakan bersama oleh akreditasi.php (halaman publik) dan admin/builder-preview.php (visual builder)
 */
require_once __DIR__ . '/../config/database.php';

function getAkreditasiData() {
    static $data = null;
    if ($data !== null) return $data;

    $db = getDB();
    $data = [];

    $data['prodi_list']       = $db->query("SELECT * FROM akreditasi_prodi ORDER BY fakultas ASC, strata ASC, program_studi ASC")->fetchAll(PDO::FETCH_ASSOC);
    $data['akred_peringkat']  = getPengaturan('akred_institusi_peringkat', 'UNGGUL');
    $data['akred_card_badge'] = getPengaturan('akred_card_badge', 'STATUS RESMI');
    $data['akred_card_title'] = getPengaturan('akred_card_title', 'TERAKREDITASI');
    $data['akred_card_desc']  = getPengaturan('akred_card_desc', 'Berdasarkan Keputusan Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT) untuk Institusi Universitas Katolik Soegijapranata.');
    $data['akred_file']       = getPengaturan('akred_institusi_file', '');
    $data['akred_sk']         = getPengaturan('akred_institusi_sk', '');
    $data['lembaga_list']     = $db->query("SELECT * FROM lembaga_akreditasi ORDER BY urutan ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);

    // Ambil dokumen lembaga
    $lembaga_dokumen_raw = $db->query("SELECT * FROM lembaga_dokumen ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
    $lembaga_dokumen_map = [];
    foreach ($lembaga_dokumen_raw as $ld) {
        $lembaga_dokumen_map[$ld['lembaga_id']][] = $ld;
    }
    $data['lembaga_dokumen_map'] = $lembaga_dokumen_map;

    // Kelompokkan data prodi berdasarkan fakultas
    $fakultas_data = [];
    foreach ($data['prodi_list'] as $p) {
        $fak = !empty($p['fakultas']) ? $p['fakultas'] : 'Lainnya';
        if (!isset($fakultas_data[$fak])) {
            $fakultas_data[$fak] = [];
        }
        $fakultas_data[$fak][] = $p;
    }
    $data['fakultas_data'] = $fakultas_data;

    // Hitung rekapitulasi data akreditasi secara dinamis
    $rekap_peringkat = [
        'Baik' => 0,
        'Baik Sekali' => 0,
        'Terakreditasi' => 0,
        'Terakreditasi Sementara' => 0,
        'Unggul' => 0,
    ];
    $rekap_lembaga = [];
    $lembaga_lookup = [];
    foreach ($data['lembaga_list'] as $lem_item) {
        $kode_lem = trim($lem_item['kode']);
        $rekap_lembaga[$kode_lem] = 0;
        $clean_key = strtolower(str_replace([' ', '-', '_'], '', $kode_lem));
        $lembaga_lookup[$clean_key] = $kode_lem;
    }

    $rekap_jenjang = [
        'Doktor' => 0,
        'Magister' => 0,
        'Profesi' => 0,
        'Sarjana' => 0,
    ];

    foreach ($data['prodi_list'] as $p) {
        // Peringkat
        $per = trim($p['peringkat']);
        if (isset($rekap_peringkat[$per])) {
            $rekap_peringkat[$per]++;
        } else {
            $rekap_peringkat[$per] = 1;
        }

        // Lembaga
        $lem_raw = trim($p['lembaga']);
        $lem_clean = strtolower(str_replace([' ', '-', '_'], '', $lem_raw));
        if (isset($lembaga_lookup[$lem_clean])) {
            $canonical = $lembaga_lookup[$lem_clean];
            $rekap_lembaga[$canonical]++;
        } elseif (isset($rekap_lembaga[$lem_raw])) {
            $rekap_lembaga[$lem_raw]++;
        } else {
            $rekap_lembaga[$lem_raw] = 1;
        }

        // Jenjang / Strata
        $str = trim($p['strata']);
        if (stripos($str, 'S3') !== false || stripos($str, 'Doktor') !== false) {
            $rekap_jenjang['Doktor']++;
        } elseif (stripos($str, 'S2') !== false || stripos($str, 'Magister') !== false) {
            $rekap_jenjang['Magister']++;
        } elseif (stripos($str, 'Profesi') !== false) {
            $rekap_jenjang['Profesi']++;
        } else {
            $rekap_jenjang['Sarjana']++;
        }
    }

    // Pengaturan khusus section Status Akreditasi Nasional
    $data['akred_nasional_title'] = getPengaturan('akred_nasional_title', 'STATUS AKREDITASI NASIONAL PROGRAM STUDI');
    $data['akred_nasional_desc']  = getPengaturan('akred_nasional_desc', 'Ringkasan capaian status akreditasi seluruh program studi Universitas Katolik Soegijapranata berdasarkan peringkat, lembaga akreditasi resmi, dan jenjang pendidikan.');
    $akred_nasional_mode          = getPengaturan('akred_nasional_mode', 'otomatis');
    $akred_nasional_custom        = json_decode(getPengaturan('akred_nasional_custom_data', '{}'), true);

    if ($akred_nasional_mode === 'manual' && is_array($akred_nasional_custom)) {
        if (!empty($akred_nasional_custom['peringkat']) && is_array($akred_nasional_custom['peringkat'])) {
            $rekap_peringkat = $akred_nasional_custom['peringkat'];
        }
        if (!empty($akred_nasional_custom['lembaga']) && is_array($akred_nasional_custom['lembaga'])) {
            $rekap_lembaga = $akred_nasional_custom['lembaga'];
        }
        if (!empty($akred_nasional_custom['jenjang']) && is_array($akred_nasional_custom['jenjang'])) {
            $rekap_jenjang = $akred_nasional_custom['jenjang'];
        }
    }

    $data['rekap_peringkat'] = $rekap_peringkat;
    $data['rekap_lembaga']   = $rekap_lembaga;
    $data['rekap_jenjang']   = $rekap_jenjang;

    return $data;
}

function renderAkreditasiSection($type, $block = [], $is_builder = false) {
    $data = getAkreditasiData();
    $bg   = !empty($block['bg_color']) ? "background-color: {$block['bg_color']} !important;" : "";
    $tc   = !empty($block['text_color']) ? "color: {$block['text_color']} !important;" : "";

    switch ($type) {
        case 'akreditasi_institusi':
            ?>
            <!-- 1. PENGANTAR & STATUS AKREDITASI INSTITUSI -->
            <section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="row g-5 align-items-center">
                        <div class="col-lg-7">
                            <span class="section-tag mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .982-3.172M12 3a4.5 4.5 0 0 0-4.5 4.5v1.875a3.375 3.375 0 0 0 3.375 3.375h2.25a3.375 3.375 0 0 0 3.375-3.375V7.5A4.5 4.5 0 0 0 12 3Z" />
                                </svg>
                                <?= htmlspecialchars($block['badge'] ?? 'Peringkat Nasional') ?>
                            </span>
                            <h2 class="section-title mb-3"><?= htmlspecialchars($block['title'] ?? 'Akreditasi Institusi UNIKA') ?></h2>
                            <p style="color:var(--text-main);line-height:1.8;font-size:1rem;margin-bottom:1.25rem;">
                                <?= nl2br(htmlspecialchars($block['subtitle'] ?? 'Universitas Katolik Soegijapranata senantiasa menjaga dan meningkatkan reputasi akademik melalui akreditasi nasional oleh Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT) serta Lembaga Akreditasi Mandiri (LAM) yang relevan untuk setiap rumpun keilmuan program studi.')) ?>
                            </p>
                            <p style="color:var(--text-muted);line-height:1.75;font-size:0.95rem;margin-bottom:1.5rem;">
                                Dengan capaian peringkat akreditasi tertinggi <strong>UNGGUL</strong>, UNIKA menjamin proses pembelajaran, penelitian, pengabdian masyarakat, dan tata kelola berstandar mutu unggul bagi seluruh civitas akademika dan masyarakat luas.
                            </p>

                            <div class="d-flex gap-3 flex-wrap align-items-center">
                                <a href="#daftar-akreditasi" class="btn-hero-primary" style="background:var(--navy);border:none;padding:0.7rem 1.4rem;font-size:0.9rem;text-decoration:none;">
                                    Lihat Capaian Prodi &darr;
                                </a>
                            </div>
                        </div>

                        <!-- Institusi Unggul Badge Card -->
                        <div class="col-lg-5">
                            <div class="card-lpm p-4 text-center" style="background:linear-gradient(135deg, var(--navy), #132D54);color:#fff;border-radius:var(--radius-lg);box-shadow:0 12px 35px rgba(10,25,47,0.18);">
                                <div style="width:70px;height:70px;margin:0 auto 1.25rem;border-radius:50%;background:rgba(255,255,255,0.12);display:flex;align-items:center;justify-content:center;box-shadow:0 4px 15px rgba(0,0,0,0.2);">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.6" stroke="#FFD54F" width="36" height="36">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.499.04.701.663.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345L11.48 3.5Z" />
                                    </svg>
                                </div>
                                <span class="card-category-badge mb-2" style="background:#FFD54F;color:#0A192F;font-weight:800;"><?= htmlspecialchars($data['akred_card_badge']) ?></span>
                                <h3 style="font-family:var(--font-heading);font-weight:800;color:#fff;margin-bottom:0.25rem;font-size:1.8rem;">
                                    <?= htmlspecialchars($data['akred_card_title']) ?>
                                </h3>
                                <div style="font-size:1.4rem;color:#FFD54F;font-weight:800;letter-spacing:1px;margin-bottom:0.85rem;">
                                    <?= htmlspecialchars($data['akred_peringkat']) ?>
                                </div>
                                <p style="font-size:0.85rem;color:rgba(255,255,255,0.78);line-height:1.6;margin:0 0 1.25rem;">
                                    <?= htmlspecialchars($data['akred_card_desc']) ?>
                                </p>

                                <!-- Tombol Berkas SK & Sertifikat Akreditasi Institusi -->
                                <div class="d-flex flex-column align-items-center gap-2">
                                    <button type="button" class="btn w-100" data-bs-toggle="modal" data-bs-target="#modalDokumenInstitusi" style="background:#FFD54F;color:#0A192F;font-weight:700;font-size:0.875rem;padding:0.7rem 1.4rem;border-radius:25px;display:inline-flex;align-items:center;justify-content:center;gap:8px;text-decoration:none;box-shadow:0 4px 15px rgba(0,0,0,0.2);border:none;transition:transform 0.15s ease;">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                        </svg>
                                        Lihat &amp; Unduh Berkas (SK &amp; Sertifikat)
                                    </button>
                                    <small style="color:rgba(255,255,255,0.7);font-size:0.75rem;">
                                        Tersedia file SK dan Sertifikat resmi BAN-PT langsung di server
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'akreditasi_statistik':
            $rekap_peringkat = $data['rekap_peringkat'];
            $rekap_lembaga   = $data['rekap_lembaga'];
            $rekap_jenjang   = $data['rekap_jenjang'];
            ?>
            <!-- 2. STATUS AKREDITASI NASIONAL PROGRAM STUDI -->
            <section class="py-5" style="background:#F8FAFC;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="section-tag mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 3v11.25A2.25 2.25 0 0 0 6 16.5h2.25M3.75 3h-1.5m1.5 0h16.5m0 0h1.5m-1.5 0v11.25A2.25 2.25 0 0 1 18 16.5h-2.25m-7.5 0h7.5m-7.5 0-1 3m8.5-3 1 3m0 0 .5 1.5m-.5-1.5h-9.5m0 0-.5 1.5m.75-9 3-3 2.143 2.143L15.75 6" />
                            </svg>
                            <?= htmlspecialchars($block['badge'] ?? 'Rekapitulasi Mutu') ?>
                        </span>
                        <h2 class="section-title text-uppercase" style="letter-spacing:0.5px;color:var(--navy);font-family:var(--font-heading);font-weight:800;"><?= htmlspecialchars($block['title'] ?? $data['akred_nasional_title']) ?></h2>
                        <p class="section-desc mx-auto" style="max-width:650px;">
                            <?= nl2br(htmlspecialchars($block['subtitle'] ?? $data['akred_nasional_desc'])) ?>
                        </p>
                    </div>

                    <div class="row g-4">
                        <!-- 1. Peringkat -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 shadow-sm border-0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06) !important;">
                                <div style="background:var(--navy);color:#fff;padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;">
                                    <span style="font-weight:700;font-size:1rem;letter-spacing:0.3px;">Peringkat</span>
                                    <span style="font-weight:700;font-size:0.95rem;">Jumlah</span>
                                </div>
                                <div class="p-3 d-flex flex-column justify-content-between h-100">
                                    <div class="d-flex flex-column gap-2 mb-3">
                                        <?php 
                                        $total_peringkat = 0;
                                        foreach ($rekap_peringkat as $label => $jml): 
                                            $total_peringkat += $jml;
                                            $is_unggul = (strtoupper($label) === 'UNGGUL');
                                        ?>
                                        <div class="d-flex align-items-center justify-content-between p-2 px-3 rounded-3" style="background:#F8FAFC;border:1px solid #EDF2F7;">
                                            <span style="font-weight:600;color:#1E293B;font-size:0.875rem;"><?= htmlspecialchars($label) ?></span>
                                            <span class="badge" style="background:<?= $is_unggul ? '#E8F5E9' : '#0A192F' ?>;color:<?= $is_unggul ? '#1B5E20' : '#FFFFFF' ?>;font-weight:700;font-size:0.85rem;min-width:32px;padding:0.4rem 0.6rem;border-radius:8px;">
                                                <?= $jml ?>
                                            </span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="font-weight:800;color:var(--navy);">
                                        <span style="font-size:0.95rem;">Total Program Studi</span>
                                        <span class="badge px-3 py-2" style="font-size:0.95rem;border-radius:8px;background:var(--navy) !important;color:#fff;"><?= $total_peringkat ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 2. Lembaga Akreditasi -->
                        <div class="col-lg-4 col-md-6">
                            <div class="card h-100 shadow-sm border-0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06) !important;">
                                <div style="background:var(--navy);color:#fff;padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;">
                                    <span style="font-weight:700;font-size:1rem;letter-spacing:0.3px;">Lembaga Akreditasi</span>
                                    <span style="font-weight:700;font-size:0.95rem;">Jumlah</span>
                                </div>
                                <div class="p-3 d-flex flex-column justify-content-between h-100">
                                    <div class="d-flex flex-column gap-2 mb-3">
                                        <?php 
                                        $total_lembaga = 0;
                                        foreach ($rekap_lembaga as $label => $jml): 
                                            $total_lembaga += $jml;
                                        ?>
                                        <div class="d-flex align-items-center justify-content-between p-2 px-3 rounded-3" style="background:#F8FAFC;border:1px solid #EDF2F7;">
                                            <span style="font-weight:600;color:<?= $jml > 0 ? '#1E293B' : '#64748B' ?>;font-size:0.875rem;"><?= htmlspecialchars($label) ?></span>
                                            <span class="badge" style="background:<?= $jml > 0 ? '#0A192F' : '#E2E8F0' ?>;color:<?= $jml > 0 ? '#FFFFFF' : '#64748B' ?>;font-weight:700;font-size:0.85rem;min-width:32px;padding:0.4rem 0.6rem;border-radius:8px;">
                                                <?= $jml > 0 ? $jml : '-' ?>
                                            </span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="font-weight:800;color:var(--navy);">
                                        <span style="font-size:0.95rem;">Total Program Studi</span>
                                        <span class="badge px-3 py-2" style="font-size:0.95rem;border-radius:8px;background:var(--navy) !important;color:#fff;"><?= $total_lembaga ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- 3. Jenjang -->
                        <div class="col-lg-4 col-md-12">
                            <div class="card h-100 shadow-sm border-0" style="background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 4px 20px rgba(0,0,0,0.06) !important;">
                                <div style="background:var(--navy);color:#fff;padding:1rem 1.25rem;display:flex;align-items:center;justify-content:space-between;">
                                    <span style="font-weight:700;font-size:1rem;letter-spacing:0.3px;">Jenjang Pendidikan</span>
                                    <span style="font-weight:700;font-size:0.95rem;">Jumlah</span>
                                </div>
                                <div class="p-3 d-flex flex-column justify-content-between h-100">
                                    <div class="d-flex flex-column gap-2 mb-3">
                                        <?php 
                                        $total_jenjang = 0;
                                        foreach ($rekap_jenjang as $label => $jml): 
                                            $total_jenjang += $jml;
                                        ?>
                                        <div class="d-flex align-items-center justify-content-between p-2 px-3 rounded-3" style="background:#F8FAFC;border:1px solid #EDF2F7;">
                                            <span style="font-weight:600;color:#1E293B;font-size:0.875rem;"><?= htmlspecialchars($label) ?></span>
                                            <span class="badge" style="background:#0A192F;color:#FFFFFF;font-weight:700;font-size:0.85rem;min-width:32px;padding:0.4rem 0.6rem;border-radius:8px;">
                                                <?= $jml ?>
                                            </span>
                                        </div>
                                        <?php endforeach; ?>
                                    </div>
                                    <div class="pt-3 border-top d-flex justify-content-between align-items-center" style="font-weight:800;color:var(--navy);">
                                        <span style="font-size:0.95rem;">Total Program Studi</span>
                                        <span class="badge px-3 py-2" style="font-size:0.95rem;border-radius:8px;background:var(--navy) !important;color:#fff;"><?= $total_jenjang ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'akreditasi_lam':
            $lembaga_list        = $data['lembaga_list'];
            $lembaga_dokumen_map = $data['lembaga_dokumen_map'];
            ?>
            <!-- 3. LOGO & BADAN AKREDITASI (BAN-PT & 7 LAM) -->
            <section class="py-5" id="lembaga-akreditasi" style="background:var(--bg-main);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="section-tag mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-2.18-7.52a48.42 48.42 0 0 0-5.64 0A1.875 1.875 0 0 0 3.75 4.5v15a1.875 1.875 0 0 0 1.875 1.875h12.75A1.875 1.875 0 0 0 20.25 19.5V4.5a1.875 1.875 0 0 0-1.875-1.875c-1.87-.14-3.75-.14-5.64 0Z" />
                            </svg>
                            <?= htmlspecialchars($block['badge'] ?? 'Lembaga Akreditasi') ?>
                        </span>
                        <h2 class="section-title"><?= htmlspecialchars($block['title'] ?? 'Badan Akreditasi Nasional & Lembaga Akreditasi Mandiri (LAM)') ?></h2>
                        <p class="section-desc mx-auto">
                            <?= nl2br(htmlspecialchars($block['subtitle'] ?? 'Kerjasama akreditasi program studi UNIKA bersama BAN-PT dan Lembaga Akreditasi Mandiri resmi nasional. Kunjungi tautan resmi lembaga untuk informasi, instrumen, dan pedoman akreditasi terkini.')) ?>
                        </p>
                    </div>

                    <div class="row g-4">
                        <?php foreach ($lembaga_list as $lem): 
                            $lem_warna = !empty($lem['warna']) ? $lem['warna'] : '#1E3A8A';
                            $lem_kode  = $lem['kode'] ?? '';
                            $lem_nama  = $lem['nama'] ?? '';
                            $lem_lingkup = $lem['lingkup'] ?? 'Nasional';
                        ?>
                        <div class="col-md-6 col-lg-3">
                            <div class="card p-4 h-100 text-center d-flex flex-column border shadow-sm" style="background:#ffffff;border-radius:14px;box-shadow:0 4px 15px rgba(0,0,0,0.04) !important;">
                                <!-- Logo / Emblema Container -->
                                <div style="width:80px;height:80px;margin:0 auto 1rem;border-radius:14px;background:#ffffff;display:flex;align-items:center;justify-content:center;border:1.5px solid <?= htmlspecialchars($lem_warna) ?>;overflow:hidden;padding:8px;flex-shrink:0;box-shadow:0 3px 10px rgba(0,0,0,0.05);">
                                    <?php if (!empty($lem['logo']) && file_exists(__DIR__ . '/../uploads/akreditasi/' . $lem['logo'])): ?>
                                        <img src="<?= SITE_URL ?>/uploads/akreditasi/<?= htmlspecialchars($lem['logo']) ?>" alt="<?= htmlspecialchars($lem_kode) ?>" style="max-width:100%;max-height:100%;object-fit:contain;">
                                    <?php else: ?>
                                        <div style="font-family:var(--font-heading);font-weight:900;color:<?= htmlspecialchars($lem_warna) ?>;font-size:<?= strlen($lem_kode) > 7 ? '0.75rem' : '0.9rem' ?>;line-height:1.1;text-align:center;">
                                            <?= htmlspecialchars($lem_kode) ?>
                                        </div>
                                    <?php endif; ?>
                                </div>

                                <h5 style="font-family:var(--font-heading);font-weight:800;color:#0F172A;font-size:1.05rem;margin-bottom:0.4rem;">
                                    <?= htmlspecialchars($lem_kode) ?>
                                </h5>
                                
                                <!-- Nama lengkap dengan styling fleksibel tanpa overflow -->
                                <div style="font-size:0.8rem;color:#334155;line-height:1.45;margin-bottom:0.75rem;min-height:44px;display:flex;align-items:center;justify-content:center;word-break:break-word;">
                                    <?= htmlspecialchars($lem_nama) ?>
                                </div>

                                <!-- Lingkup Keilmuan Badge -->
                                <div class="mb-3">
                                    <span style="display:inline-block;max-width:100%;background:#F1F5F9;border:1px solid #E2E8F0;border-radius:6px;padding:0.35rem 0.65rem;font-size:0.72rem;font-weight:600;color:<?= htmlspecialchars($lem_warna) ?>;word-break:break-word;">
                                        <?= htmlspecialchars($lem_lingkup) ?>
                                    </span>
                                </div>

                                <!-- Tautan Website Resmi Lembaga Akreditasi -->
                                <div class="pt-3 mt-auto border-top">
                                    <?php if (!empty($lem['link_website'])): ?>
                                        <a href="<?= htmlspecialchars($lem['link_website']) ?>" target="_blank" rel="noopener noreferrer" class="btn btn-sm w-100 fw-bold shadow-sm d-inline-flex align-items-center justify-content-center gap-2" style="background:<?= htmlspecialchars($lem_warna) ?>;color:#fff;border-radius:20px;padding:0.5rem 0.8rem;font-size:0.8rem;text-decoration:none;">
                                            <i class="bi bi-box-arrow-up-right"></i>
                                            Kunjungi Website Resmi
                                        </a>
                                    <?php else: ?>
                                        <span class="badge bg-light text-muted border py-2 w-100" style="font-size:0.75rem;border-radius:20px;font-weight:500;">
                                            Tautan Belum Tersedia
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'akreditasi_prodi':
            $fakultas_data    = $data['fakultas_data'];
            $prodi_list       = $data['prodi_list'];
            $rekap_peringkat  = $data['rekap_peringkat'];
            $today            = new DateTime();
            ?>
            <!-- 4. DAFTAR AKREDITASI PROGRAM STUDI PER FAKULTAS -->
            <section class="py-5" id="daftar-akreditasi" style="background:#ffffff;border-top:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <!-- Section Header -->
                    <div class="d-flex align-items-end justify-content-between mb-4 flex-wrap gap-3">
                        <div>
                            <span class="section-tag mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 0 1 0 3.75H5.625a1.875 1.875 0 0 1 0-3.75Z" />
                                </svg>
                                <?= htmlspecialchars($block['badge'] ?? 'Program Studi') ?>
                            </span>
                            <h2 class="section-title mb-0"><?= htmlspecialchars($block['title'] ?? 'Status Akreditasi Program Studi per Fakultas') ?></h2>
                            <p class="text-muted small mt-1 mb-0">
                                <?= htmlspecialchars($block['subtitle'] ?? 'Daftar lengkap akreditasi prodi UNIKA yang dikelompokkan per fakultas, dilengkapi pratinjau dan unduh berkas SK & Sertifikat.') ?>
                            </p>
                        </div>

                    </div>

                    <!-- Search Bar & Controls -->
                    <div class="p-3 mb-4 rounded-4" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                        <div class="row g-3 align-items-center">
                            <div class="col-lg-7">
                                <div class="position-relative">
                                    <span class="position-absolute top-50 start-0 translate-middle-y ps-3 text-muted" style="pointer-events:none;">
                                        <i class="bi bi-search" style="font-size:1.1rem;"></i>
                                    </span>
                                    <input type="text" id="prodiSearchInput" class="form-control form-control-lg ps-5 pe-5" placeholder="Ketik nama prodi, fakultas, strata (S1/S2/S3), peringkat, atau no SK..." style="font-size:0.95rem;border-radius:12px;border:1.5px solid #CBD5E1;box-shadow:none;background:#ffffff;">
                                    <button type="button" id="prodiClearSearch" class="btn btn-link position-absolute top-50 end-0 translate-middle-y text-muted p-0 pe-3 d-none" style="text-decoration:none;" title="Hapus pencarian">
                                        <i class="bi bi-x-circle-fill" style="font-size:1.15rem;"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="col-lg-5 d-flex align-items-center justify-content-lg-end gap-2 flex-wrap">
                                <span class="badge px-3 py-2" id="prodiCounterBadge" style="font-size:0.85rem;border-radius:20px;background:var(--navy);color:#fff;">
                                    Menampilkan <?= count($prodi_list) ?> dari <?= count($prodi_list) ?> Prodi
                                </span>
                                <button type="button" class="btn btn-sm btn-outline-secondary" id="btnToggleAllAccordions" style="border-radius:20px;font-size:0.8rem;white-space:nowrap;padding:0.45rem 0.9rem;">
                                    <i class="bi bi-arrows-expand me-1"></i> <span id="toggleAllText">Buka Semua</span>
                                </button>
                            </div>
                        </div>

                        <!-- Quick Filter Chips -->
                        <div class="d-flex flex-wrap gap-2 mt-3 pt-3 border-top align-items-center">
                            <span class="text-muted small fw-semibold me-1"><i class="bi bi-funnel me-1"></i>Filter Cepat:</span>
                            <button type="button" class="btn btn-sm btn-filter active" data-filter="all">Semua (<?= count($prodi_list) ?>)</button>
                            <button type="button" class="btn btn-sm btn-filter" data-filter="unggul">Unggul (<?= $rekap_peringkat['Unggul'] ?? 0 ?>)</button>
                            <button type="button" class="btn btn-sm btn-filter" data-filter="baik sekali">Baik Sekali (<?= $rekap_peringkat['Baik Sekali'] ?? 0 ?>)</button>
                            <button type="button" class="btn btn-sm btn-filter" data-filter="baik">Baik (<?= $rekap_peringkat['Baik'] ?? 0 ?>)</button>
                            <span class="text-muted d-none d-md-inline" style="opacity:0.4;">|</span>
                            <button type="button" class="btn btn-sm btn-filter" data-filter="s1">Sarjana / S1</button>
                            <button type="button" class="btn btn-sm btn-filter" data-filter="s2">Magister / S2</button>
                            <button type="button" class="btn btn-sm btn-filter" data-filter="s3">Doktor / S3</button>
                            <button type="button" class="btn btn-sm btn-filter" data-filter="profesi">Profesi</button>
                        </div>
                    </div>

                    <!-- Alert Jika Hasil Pencarian Kosong -->
                    <div id="noProdiFoundAlert" class="alert alert-warning text-center p-4 d-none rounded-4" role="alert">
                        <i class="bi bi-search me-2" style="font-size:1.5rem;"></i>
                        <h6 class="fw-bold mb-1 mt-2">Tidak Ada Program Studi yang Sesuai</h6>
                        <p class="small text-muted mb-2">Tidak ditemukan program studi dengan kata kunci atau filter yang Anda pilih.</p>
                        <button type="button" class="btn btn-sm btn-outline-dark" id="btnResetSearch" style="border-radius:20px;">
                            Reset Pencarian
                        </button>
                    </div>

                    <!-- Accordion Fakultas -->
                    <div class="accordion accordion-lpm" id="accordionFakultas">
                        <?php 
                        $i = 0;
                        foreach ($fakultas_data as $fakultas => $prodis): 
                            $i++;
                            $collapse_id = 'collapseFak' . $i;
                        ?>
                        <div class="accordion-item border-0 mb-3 rounded-4 shadow-sm fakultas-accordion-item" style="overflow:hidden;border:1px solid #E2E8F0 !important;" data-fakultas="<?= strtolower(htmlspecialchars($fakultas)) ?>">
                            <h2 class="accordion-header">
                                <button class="accordion-button fw-bold py-3 <?= $i === 1 ? '' : 'collapsed' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#<?= $collapse_id ?>" style="background-color:#f8f9fc;color:var(--navy);box-shadow:none;">
                                    <div class="d-flex align-items-center w-100 justify-content-between pe-3">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="bi bi-building text-primary"></i>
                                            <span class="fakultas-title-text"><?= htmlspecialchars($fakultas) ?></span>
                                        </div>
                                        <span class="badge rounded-pill px-3 py-2 prodi-count-badge" style="background:#EDE7F6;color:#4A148C;font-weight:700;font-size:0.8rem;">
                                            <?= count($prodis) ?> Prodi
                                        </span>
                                    </div>
                                </button>
                            </h2>
                            <div id="<?= $collapse_id ?>" class="accordion-collapse collapse <?= $i === 1 ? 'show' : '' ?>" data-bs-parent="#accordionFakultas">
                                <div class="accordion-body p-0">
                                    <div class="table-responsive">
                                        <table class="table table-hover mb-0 align-middle">
                                            <thead style="background-color:#f8fafc;border-bottom:2px solid #E2E8F0;">
                                                <tr>
                                                    <th class="py-3 px-3 fw-bold text-center" width="45" style="color:#64748B;font-size:0.85rem;">#</th>
                                                    <th class="py-3 px-3 fw-bold" style="color:#475569;font-size:0.85rem;">Program Studi</th>
                                                    <th class="py-3 px-3 fw-bold" width="90" style="color:#475569;font-size:0.85rem;">Strata</th>
                                                    <th class="py-3 px-3 fw-bold" width="130" style="color:#475569;font-size:0.85rem;">Peringkat</th>
                                                    <th class="py-3 px-3 fw-bold" style="color:#475569;font-size:0.85rem;">Lembaga &amp; No. SK</th>
                                                    <th class="py-3 px-3 fw-bold" width="130" style="color:#475569;font-size:0.85rem;">Masa Berlaku</th>
                                                    <th class="py-3 px-3 fw-bold text-center" width="260" style="color:#475569;font-size:0.85rem;min-width:260px;">Dokumen (SK &amp; Sertifikat)</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                foreach ($prodis as $idx => $p): 
                                                    $berlaku = (!empty($p['masa_berlaku']) && $p['masa_berlaku'] !== '0000-00-00') ? new DateTime($p['masa_berlaku']) : null;
                                                    $diff = $berlaku ? (int)$today->diff($berlaku)->format("%r%a") : null;
                                                    $is_expired = ($diff !== null && $diff <= 0);

                                                    $peringkat_upper = strtoupper($p['peringkat'] ?? '');
                                                    $badge_bg = '#F1F5F9';
                                                    $badge_color = '#334155';
                                                    if (str_contains($peringkat_upper, 'UNGGUL') || str_contains($peringkat_upper, 'A')) {
                                                        $badge_bg = '#E8F5E9';
                                                        $badge_color = '#1B5E20';
                                                    } elseif (str_contains($peringkat_upper, 'BAIK SEKALI') || str_contains($peringkat_upper, 'B')) {
                                                        $badge_bg = '#E3F2FD';
                                                        $badge_color = '#1565C0';
                                                    } elseif (str_contains($peringkat_upper, 'BAIK') || str_contains($peringkat_upper, 'C')) {
                                                        $badge_bg = '#FFF8E1';
                                                        $badge_color = '#F57F17';
                                                    }
                                                ?>
                                                <tr class="prodi-row" 
                                                    data-prodi="<?= strtolower(htmlspecialchars($p['program_studi'] ?? '')) ?>" 
                                                    data-fakultas="<?= strtolower(htmlspecialchars($p['fakultas'] ?? '')) ?>"
                                                    data-strata="<?= strtolower(htmlspecialchars($p['strata'] ?? '')) ?>"
                                                    data-peringkat="<?= strtolower(htmlspecialchars($p['peringkat'] ?? '')) ?>"
                                                    data-lembaga="<?= strtolower(htmlspecialchars($p['lembaga'] ?? '')) ?>"
                                                    data-nosk="<?= strtolower(htmlspecialchars($p['no_sk'] ?? '')) ?>">
                                                    <td class="text-center text-muted" style="font-size:0.82rem;"><?= $idx + 1 ?></td>
                                                    <td class="px-3 py-3">
                                                        <div class="fw-bold" style="color:var(--navy);font-size:0.95rem;"><?= htmlspecialchars($p['program_studi'] ?? '') ?></div>
                                                        <div class="text-muted small"><?= htmlspecialchars($p['fakultas'] ?? '') ?></div>
                                                    </td>
                                                    <td class="px-3 py-3">
                                                        <span class="badge bg-secondary" style="font-size:0.75rem;padding:0.35rem 0.6rem;border-radius:6px;"><?= htmlspecialchars($p['strata'] ?? '') ?></span>
                                                    </td>
                                                    <td class="px-3 py-3">
                                                        <span class="badge" style="background:<?= $badge_bg ?>;color:<?= $badge_color ?>;font-weight:800;font-size:0.82rem;padding:0.4rem 0.8rem;border-radius:20px;">
                                                            <?= htmlspecialchars($p['peringkat'] ?? '') ?>
                                                        </span>
                                                    </td>
                                                    <td class="px-3 py-3">
                                                        <div class="fw-bold" style="font-size:0.9rem;color:#0F172A;"><?= htmlspecialchars($p['lembaga'] ?? '') ?></div>
                                                        <div class="text-muted" style="font-size:0.75rem;word-break:break-word;"><?= htmlspecialchars($p['no_sk'] ?? '') ?></div>
                                                    </td>
                                                    <td class="px-3 py-3">
                                                        <?php if ($is_expired): ?>
                                                            <span class="text-danger fw-bold" style="font-size:0.82rem;">
                                                                <i class="bi bi-x-circle me-1"></i> Kadaluarsa
                                                            </span>
                                                        <?php elseif ($berlaku): ?>
                                                            <span class="text-success fw-medium" style="font-size:0.82rem;">
                                                                <i class="bi bi-check-circle me-1"></i> <?= $berlaku->format('d M Y') ?>
                                                            </span>
                                                        <?php else: ?>
                                                            <span class="text-muted" style="font-size:0.82rem;">-</span>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td class="px-3 py-3">
                                                        <div class="d-flex flex-column gap-2" style="min-width: 250px;">
                                                            <!-- Berkas SK -->
                                                            <?php if (!empty($p['file_sk']) && file_exists(__DIR__ . '/../uploads/akreditasi_prodi/' . $p['file_sk'])): ?>
                                                                <div class="d-flex align-items-center justify-content-between p-1 px-2 rounded-2 gap-2" style="background:#EFF6FF;border:1px solid #BFDBFE;">
                                                                    <span class="fw-bold text-nowrap flex-shrink-0" style="color:#1D4ED8;font-size:0.75rem;display:inline-flex;align-items:center;gap:5px;">
                                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                                                                        SK
                                                                    </span>
                                                                    <div class="d-flex gap-1 flex-shrink-0">
                                                                        <a href="<?= SITE_URL ?>/uploads/akreditasi_prodi/<?= htmlspecialchars($p['file_sk']) ?>" target="_blank" class="btn btn-sm py-1 px-2 d-inline-flex align-items-center gap-1" style="font-size:0.72rem;background:#ffffff;color:#1E40AF;border:1px solid #93C5FD;border-radius:4px;font-weight:700;text-decoration:none;" title="Buka & Lihat SK di Tab Baru">
                                                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                                            Lihat
                                                                        </a>
                                                                        <a href="<?= SITE_URL ?>/uploads/akreditasi_prodi/<?= htmlspecialchars($p['file_sk']) ?>" download class="btn btn-sm py-1 px-2 d-inline-flex align-items-center gap-1" style="font-size:0.72rem;background:#1D4ED8;color:#ffffff;border:none;border-radius:4px;font-weight:700;text-decoration:none;" title="Unduh Berkas SK">
                                                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                                                            Unduh
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>

                                                            <!-- Berkas Sertifikat -->
                                                            <?php if (!empty($p['file_sertifikat']) && file_exists(__DIR__ . '/../uploads/akreditasi_prodi/' . $p['file_sertifikat'])): ?>
                                                                <div class="d-flex align-items-center justify-content-between p-1 px-2 rounded-2 gap-2" style="background:#F0FDF4;border:1px solid #BBF7D0;">
                                                                    <span class="fw-bold text-nowrap flex-shrink-0" style="color:#15803D;font-size:0.75rem;display:inline-flex;align-items:center;gap:5px;">
                                                                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="8" r="7"></circle><polyline points="8.21 13.89 7 23 12 20 17 23 15.79 13.88"></polyline></svg>
                                                                        Sertifikat
                                                                    </span>
                                                                    <div class="d-flex gap-1 flex-shrink-0">
                                                                        <a href="<?= SITE_URL ?>/uploads/akreditasi_prodi/<?= htmlspecialchars($p['file_sertifikat']) ?>" target="_blank" class="btn btn-sm py-1 px-2 d-inline-flex align-items-center gap-1" style="font-size:0.72rem;background:#ffffff;color:#166534;border:1px solid #86EFAC;border-radius:4px;font-weight:700;text-decoration:none;" title="Buka & Lihat Sertifikat di Tab Baru">
                                                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path><circle cx="12" cy="12" r="3"></circle></svg>
                                                                            Lihat
                                                                        </a>
                                                                        <a href="<?= SITE_URL ?>/uploads/akreditasi_prodi/<?= htmlspecialchars($p['file_sertifikat']) ?>" download class="btn btn-sm py-1 px-2 d-inline-flex align-items-center gap-1" style="font-size:0.72rem;background:#15803D;color:#ffffff;border:none;border-radius:4px;font-weight:700;text-decoration:none;" title="Unduh Berkas Sertifikat">
                                                                            <svg width="11" height="11" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"></path><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                                                                            Unduh
                                                                        </a>
                                                                    </div>
                                                                </div>
                                                            <?php endif; ?>

                                                            <!-- Jika Belum Ada Dokumen -->
                                                            <?php if (empty($p['file_sk']) && empty($p['file_sertifikat'])): ?>
                                                                <span class="badge bg-light text-muted border px-2 py-1 text-center" style="font-size:0.72rem;font-weight:500;">
                                                                    Dalam Proses
                                                                </span>
                                                            <?php endif; ?>
                                                        </div>
                                                    </td>
                                                </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'akreditasi_dokumen':
            // Bagian "Unduh Dokumen & Sertifikat Akreditasi Institusi" dihapus sesuai permintaan ("unduh dokumen & sertifikat hapus, di atas sudah ada")
            break;

        case 'akreditasi_faq':
            $faqs = [
                ['q' => 'Apa status akreditasi institusi UNIKA Soegijapranata saat ini?', 'a' => 'Universitas Katolik Soegijapranata terakreditasi UNGGUL oleh Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT).'],
                ['q' => 'Bagaimana cara memperoleh legalisir sertifikat akreditasi?', 'a' => 'Legalisir sertifikat akreditasi dapat diajukan secara online melalui layanan permohonan LPM atau datang langsung ke Sekretariat LPM.'],
                ['q' => 'Apakah seluruh program studi di UNIKA sudah terakreditasi?', 'a' => 'Ya, seluruh program studi aktif di Universitas Katolik Soegijapranata telah terakreditasi oleh BAN-PT atau LAM yang berwenang.'],
            ];
            ?>
            <!-- 6. FAQ AKREDITASI -->
            <section class="py-5" style="background:var(--bg-main);border-top:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="text-center mb-5">
                        <span class="section-tag mb-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                            </svg>
                            <?= htmlspecialchars($block['badge'] ?? 'Informasi Penting') ?>
                        </span>
                        <h2 class="section-title"><?= htmlspecialchars($block['title'] ?? 'Tanya Jawab (FAQ) Akreditasi') ?></h2>
                        <p class="section-desc mx-auto"><?= htmlspecialchars($block['subtitle'] ?? 'Pertanyaan umum seputar akreditasi institusi dan program studi di UNIKA.') ?></p>
                    </div>

                    <div class="max-w-800 mx-auto">
                        <div class="accordion accordion-lpm" id="faqAccordion">
                            <?php foreach ($faqs as $idx => $f): ?>
                            <div class="accordion-item mb-3" style="border:1px solid var(--border);border-radius:var(--radius-md);overflow:hidden;">
                                <h2 class="accordion-header">
                                    <button class="accordion-button <?= $idx > 0 ? 'collapsed' : '' ?>" type="button" data-bs-toggle="collapse" data-bs-target="#faq_<?= $idx ?>">
                                        <?= htmlspecialchars($f['q']) ?>
                                    </button>
                                </h2>
                                <div id="faq_<?= $idx ?>" class="accordion-collapse collapse <?= $idx === 0 ? 'show' : '' ?>" data-bs-parent="#faqAccordion">
                                    <div class="accordion-body" style="font-size:0.92rem;color:var(--text-muted);line-height:1.7;">
                                        <?= htmlspecialchars($f['a']) ?>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;
    }
}

/**
 * Render semua modal akreditasi (modal institusi & modal berkas LAM)
 */
function renderAkreditasiModals() {
    $data = getAkreditasiData();
    $akred_file = $data['akred_file'];
    $lembaga_list = $data['lembaga_list'];
    $lembaga_dokumen_map = $data['lembaga_dokumen_map'];

    $file_sertifikat_institusi = '2023_Sertifikat_Akreditasi_ISK_AIPT_UNIKA_Unggul.pdf';
    if (!empty($akred_file) && file_exists(__DIR__ . '/../uploads/akreditasi/' . $akred_file)) {
        $file_sertifikat_institusi = $akred_file;
    }
    $file_sk_institusi = '2023_SK_Akreditasi_ISK_AIPT_UNIKA_Unggul.pdf';
    $sk_setting = getPengaturan('akred_institusi_sk_file', '');
    if (!empty($sk_setting) && file_exists(__DIR__ . '/../uploads/akreditasi/' . $sk_setting)) {
        $file_sk_institusi = $sk_setting;
    }
    ?>
    <!-- MODAL BERKAS AKREDITASI INSTITUSI (SK & SERTIFIKAT) -->
    <div class="modal fade" id="modalDokumenInstitusi" tabindex="-1" aria-labelledby="modalDokumenInstitusiLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius:var(--radius-lg);overflow:hidden;border:none;box-shadow:0 20px 40px rgba(10,25,47,0.2);">
                <div class="modal-header text-white" style="background:linear-gradient(135deg, var(--navy), #132D54);padding:1.25rem 1.75rem;">
                    <div class="d-flex align-items-center gap-2">
                        <div style="width:36px;height:36px;border-radius:50%;background:#FFD54F;display:flex;align-items:center;justify-content:center;color:#0A192F;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                            </svg>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 fw-bold" id="modalDokumenInstitusiLabel">Dokumen Akreditasi Institusi UNIKA</h5>
                            <small style="color:rgba(255,255,255,0.75);">Peringkat UNGGUL – Keputusan Resmi BAN-PT</small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background:#F8FAFC;">
                    <p class="text-muted small mb-3">
                        Berikut adalah dokumen resmi Surat Keputusan (SK) dan Sertifikat Akreditasi Perguruan Tinggi Universitas Katolik Soegijapranata yang dapat langsung dilihat atau diunduh dari server lokal:
                    </p>

                    <div class="vstack gap-3">
                        <!-- Dokumen 1: Sertifikat -->
                        <div class="card p-3 border shadow-sm" style="border-radius:var(--radius-md);background:#ffffff;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:48px;height:48px;border-radius:10px;background:#FFF9C4;display:flex;align-items:center;justify-content:center;color:#F57F17;font-weight:800;font-size:1.3rem;">
                                        <i class="bi bi-award"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="color:var(--navy);font-size:1rem;">
                                            Sertifikat Akreditasi Institusi (Unggul)
                                        </div>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars($file_sertifikat_institusi) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="<?= SITE_URL ?>/uploads/akreditasi/<?= htmlspecialchars($file_sertifikat_institusi) ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-semibold px-3 py-2" style="border-radius:20px;">
                                        <i class="bi bi-eye me-1"></i> Lihat Dokumen
                                    </a>
                                    <a href="<?= SITE_URL ?>/uploads/akreditasi/<?= htmlspecialchars($file_sertifikat_institusi) ?>" download class="btn btn-sm fw-bold px-3 py-2" style="background:#FFD54F;color:#0A192F;border-radius:20px;border:none;">
                                        <i class="bi bi-download me-1"></i> Unduh
                                    </a>
                                </div>
                            </div>
                        </div>

                        <!-- Dokumen 2: SK -->
                        <div class="card p-3 border shadow-sm" style="border-radius:var(--radius-md);background:#ffffff;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:48px;height:48px;border-radius:10px;background:#E3F2FD;display:flex;align-items:center;justify-content:center;color:#1565C0;font-weight:800;font-size:1.3rem;">
                                        <i class="bi bi-file-earmark-text"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="color:var(--navy);font-size:1rem;">
                                            Surat Keputusan (SK) Akreditasi Institusi
                                        </div>
                                        <div class="text-muted small">
                                            <?= htmlspecialchars($file_sk_institusi) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="<?= SITE_URL ?>/uploads/akreditasi/<?= htmlspecialchars($file_sk_institusi) ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-semibold px-3 py-2" style="border-radius:20px;">
                                        <i class="bi bi-eye me-1"></i> Lihat Dokumen
                                    </a>
                                    <a href="<?= SITE_URL ?>/uploads/akreditasi/<?= htmlspecialchars($file_sk_institusi) ?>" download class="btn btn-sm fw-bold px-3 py-2" style="background:#FFD54F;color:#0A192F;border-radius:20px;border:none;">
                                        <i class="bi bi-download me-1"></i> Unduh
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer d-flex justify-content-between align-items-center" style="background:#ffffff;border-top:1px solid var(--border);padding:1rem 1.5rem;">
                    <span class="text-muted small">
                        <i class="bi bi-shield-check text-success me-1"></i> File terverifikasi dan disimpan di server lokal LPM
                    </span>
                    <button type="button" class="btn btn-sm btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius:20px;">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL BERKAS MASING-MASING LEMBAGA AKREDITASI -->
    <?php foreach ($lembaga_list as $lem): 
        $l_docs = $lembaga_dokumen_map[$lem['id']] ?? [];
        if (empty($l_docs)) continue;
        $lem_warna = !empty($lem['warna']) ? $lem['warna'] : '#1E3A8A';
    ?>
    <div class="modal fade" id="modalLembaga<?= $lem['id'] ?>" tabindex="-1" aria-labelledby="modalLembagaLabel<?= $lem['id'] ?>" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content" style="border-radius:var(--radius-lg);overflow:hidden;border:none;box-shadow:0 20px 40px rgba(10,25,47,0.2);">
                <div class="modal-header text-white" style="background:linear-gradient(135deg, <?= htmlspecialchars($lem_warna) ?>, #0A192F);padding:1.25rem 1.75rem;">
                    <div class="d-flex align-items-center gap-3">
                        <div style="width:40px;height:40px;border-radius:10px;background:#fff;display:flex;align-items:center;justify-content:center;color:<?= htmlspecialchars($lem_warna) ?>;font-weight:900;font-size:0.9rem;">
                            <?= htmlspecialchars($lem['kode']) ?>
                        </div>
                        <div>
                            <h5 class="modal-title mb-0 fw-bold" id="modalLembagaLabel<?= $lem['id'] ?>">Daftar Berkas &amp; Dokumen <?= htmlspecialchars($lem['kode']) ?></h5>
                            <small style="color:rgba(255,255,255,0.8);"><?= htmlspecialchars($lem['nama'] ?? '') ?></small>
                        </div>
                    </div>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4" style="background:#F8FAFC;">
                    <p class="text-muted small mb-3">
                        Berikut adalah dokumen instrumen, panduan, dan format resmi dari <?= htmlspecialchars($lem['kode']) ?>. Anda dapat melihat pratinjau di browser atau mengunduh berkas langsung:
                    </p>
                    <div class="vstack gap-2">
                        <?php foreach ($l_docs as $didx => $doc): 
                            $ext = strtolower(pathinfo($doc['file_path'], PATHINFO_EXTENSION));
                            $icon_bg = ($ext === 'pdf') ? '#FFEBEE' : '#E3F2FD';
                            $icon_color = ($ext === 'pdf') ? '#C62828' : '#1565C0';
                            $icon_class = ($ext === 'pdf') ? 'bi bi-file-earmark-pdf' : 'bi bi-file-earmark-word';
                        ?>
                        <div class="card p-3 border shadow-sm" style="border-radius:var(--radius-md);background:#ffffff;">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                                <div class="d-flex align-items-center gap-3">
                                    <div style="width:42px;height:42px;border-radius:8px;background:<?= $icon_bg ?>;display:flex;align-items:center;justify-content:center;color:<?= $icon_color ?>;font-size:1.2rem;">
                                        <i class="<?= $icon_class ?>"></i>
                                    </div>
                                    <div>
                                        <div class="fw-bold" style="color:var(--navy);font-size:0.92rem;">
                                            <?= htmlspecialchars($doc['nama_dokumen']) ?>
                                        </div>
                                        <div class="text-muted small">
                                            <?= strtoupper($ext) ?> &bull; <?= htmlspecialchars($doc['ukuran_file']) ?>
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex gap-2">
                                    <a href="<?= SITE_URL ?>/uploads/akreditasi/<?= htmlspecialchars($doc['file_path']) ?>" target="_blank" class="btn btn-sm btn-outline-primary fw-semibold px-3 py-1 d-inline-flex align-items-center gap-1" style="border-radius:20px;font-size:0.8rem;">
                                        <i class="bi bi-eye"></i> Lihat
                                    </a>
                                    <a href="<?= SITE_URL ?>/uploads/akreditasi/<?= htmlspecialchars($doc['file_path']) ?>" download class="btn btn-sm fw-bold px-3 py-1 d-inline-flex align-items-center gap-1" style="background:<?= htmlspecialchars($lem_warna) ?>;color:#fff;border-radius:20px;font-size:0.8rem;border:none;text-decoration:none;">
                                        <i class="bi bi-download"></i> Unduh
                                    </a>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer" style="background:#ffffff;border-top:1px solid var(--border);padding:0.75rem 1.5rem;">
                    <button type="button" class="btn btn-sm btn-secondary px-4 py-2" data-bs-dismiss="modal" style="border-radius:20px;">
                        Tutup
                    </button>
                </div>
            </div>
        </div>
    </div>
    <?php endforeach;
}
