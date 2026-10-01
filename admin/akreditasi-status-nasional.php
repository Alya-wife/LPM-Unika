<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Status Akreditasi Nasional';
$db = getDB();

$flash = '';
$error = '';

// Ambil data program studi untuk hitungan otomatis
$prodi_list     = $db->query("SELECT * FROM akreditasi_prodi ORDER BY fakultas ASC, strata ASC, program_studi ASC")->fetchAll();
$lembaga_list   = $db->query("SELECT * FROM lembaga_akreditasi ORDER BY urutan ASC, id ASC")->fetchAll();

// Hitung rekapitulasi otomatis
$auto_peringkat = [
    'Baik' => 0,
    'Baik Sekali' => 0,
    'Terakreditasi' => 0,
    'Terakreditasi Sementara' => 0,
    'Unggul' => 0,
];

$auto_lembaga = [];
$lembaga_lookup = [];
foreach ($lembaga_list as $lem_item) {
    $kode_lem = trim($lem_item['kode']);
    $auto_lembaga[$kode_lem] = 0;
    $clean_key = strtolower(str_replace([' ', '-', '_'], '', $kode_lem));
    $lembaga_lookup[$clean_key] = $kode_lem;
}

$auto_jenjang = [
    'Doktor' => 0,
    'Magister' => 0,
    'Profesi' => 0,
    'Sarjana' => 0,
];

foreach ($prodi_list as $p) {
    // Peringkat
    $per = trim($p['peringkat']);
    if (isset($auto_peringkat[$per])) {
        $auto_peringkat[$per]++;
    } else {
        $auto_peringkat[$per] = 1;
    }

    // Lembaga
    $lem_raw = trim($p['lembaga']);
    $lem_clean = strtolower(str_replace([' ', '-', '_'], '', $lem_raw));
    if (isset($lembaga_lookup[$lem_clean])) {
        $canonical = $lembaga_lookup[$lem_clean];
        $auto_lembaga[$canonical]++;
    } elseif (isset($auto_lembaga[$lem_raw])) {
        $auto_lembaga[$lem_raw]++;
    } else {
        $auto_lembaga[$lem_raw] = 1;
    }

    // Jenjang
    $str = trim($p['strata']);
    if (stripos($str, 'S3') !== false || stripos($str, 'Doktor') !== false) {
        $auto_jenjang['Doktor']++;
    } elseif (stripos($str, 'S2') !== false || stripos($str, 'Magister') !== false) {
        $auto_jenjang['Magister']++;
    } elseif (stripos($str, 'Profesi') !== false) {
        $auto_jenjang['Profesi']++;
    } else {
        $auto_jenjang['Sarjana']++;
    }
}

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = trim($_POST['akred_nasional_title'] ?? '');
    $desc  = trim($_POST['akred_nasional_desc'] ?? '');
    $mode  = ($_POST['akred_nasional_mode'] ?? 'otomatis') === 'manual' ? 'manual' : 'otomatis';

    setPengaturan('akred_nasional_title', $title ?: 'STATUS AKREDITASI NASIONAL PROGRAM STUDI');
    setPengaturan('akred_nasional_desc', $desc ?: 'Ringkasan capaian status akreditasi seluruh program studi Universitas Katolik Soegijapranata berdasarkan peringkat, lembaga akreditasi resmi, dan jenjang pendidikan.');
    setPengaturan('akred_nasional_mode', $mode);

    if ($mode === 'manual' && isset($_POST['custom'])) {
        $custom_peringkat = [];
        if (isset($_POST['custom']['peringkat']) && is_array($_POST['custom']['peringkat'])) {
            foreach ($_POST['custom']['peringkat'] as $lbl => $val) {
                $custom_peringkat[trim($lbl)] = (int)$val;
            }
        }
        $custom_lembaga = [];
        if (isset($_POST['custom']['lembaga']) && is_array($_POST['custom']['lembaga'])) {
            foreach ($_POST['custom']['lembaga'] as $lbl => $val) {
                $custom_lembaga[trim($lbl)] = (int)$val;
            }
        }
        $custom_jenjang = [];
        if (isset($_POST['custom']['jenjang']) && is_array($_POST['custom']['jenjang'])) {
            foreach ($_POST['custom']['jenjang'] as $lbl => $val) {
                $custom_jenjang[trim($lbl)] = (int)$val;
            }
        }
        $custom_payload = json_encode([
            'peringkat' => $custom_peringkat,
            'lembaga'   => $custom_lembaga,
            'jenjang'   => $custom_jenjang,
        ], JSON_UNESCAPED_UNICODE);
        setPengaturan('akred_nasional_custom_data', $custom_payload);
    }

    $_SESSION['flash'] = 'Pengaturan Status Akreditasi Nasional berhasil disimpan.';
    redirect(SITE_URL . '/admin/akreditasi-status-nasional.php');
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$akred_nasional_title  = getPengaturan('akred_nasional_title', 'STATUS AKREDITASI NASIONAL PROGRAM STUDI');
$akred_nasional_desc   = getPengaturan('akred_nasional_desc', 'Ringkasan capaian status akreditasi seluruh program studi Universitas Katolik Soegijapranata berdasarkan peringkat, lembaga akreditasi resmi, dan jenjang pendidikan.');
$akred_nasional_mode   = getPengaturan('akred_nasional_mode', 'otomatis');
$akred_nasional_custom = json_decode(getPengaturan('akred_nasional_custom_data', '{}'), true);

$display_peringkat = ($akred_nasional_mode === 'manual' && !empty($akred_nasional_custom['peringkat'])) ? $akred_nasional_custom['peringkat'] : $auto_peringkat;
$display_lembaga   = ($akred_nasional_mode === 'manual' && !empty($akred_nasional_custom['lembaga']))   ? $akred_nasional_custom['lembaga']   : $auto_lembaga;
$display_jenjang   = ($akred_nasional_mode === 'manual' && !empty($akred_nasional_custom['jenjang']))   ? $akred_nasional_custom['jenjang']   : $auto_jenjang;

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-11">
        <!-- Breadcrumb & Topbar -->
        <div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;margin-bottom:1.5rem;">
            <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.85rem;color:var(--text-muted);">
                <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
                <span>/</span>
                <span style="color:var(--text-muted);">Akreditasi</span>
                <span>/</span>
                <span style="color:var(--navy);font-weight:600;">Status Akreditasi Nasional</span>
            </div>
            <div class="d-flex align-items-center gap-2">
                <a href="akreditasi-prodi-list.php" class="btn btn-sm btn-outline-secondary" style="font-size:0.8rem;border-radius:8px;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="16" height="16" class="me-1">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.25 6.75h12M8.25 12h12m-12 5.25h12M3.75 6.75h.007v.008H3.75V6.75Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0ZM3.75 12h.007v.008H3.75V12Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm-.375 5.25h.007v.008H3.75v-.008Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                    Kelola Data Prodi (<?= count($prodi_list) ?>)
                </a>
                <a href="<?= SITE_URL ?>/akreditasi.php#status-nasional" target="_blank" class="btn btn-sm btn-primary" style="font-size:0.8rem;border-radius:8px;">
                    Lihat di Halaman Akreditasi &rarr;
                </a>
            </div>
        </div>

        <?php if ($flash): ?>
        <div class="alert-lpm alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <?= e($flash) ?>
        </div>
        <?php endif; ?>

        <!-- Info Box Penjelasan Otomatis -->
        <div class="card mb-4 border-0 shadow-sm" style="border-radius:12px;background:linear-gradient(135deg, #EFF6FF, #F8FAFC);border-left:5px solid #2563EB !important;padding:1.25rem 1.5rem;">
            <div class="d-flex align-items-start gap-3">
                <div style="width:36px;height:36px;background:#DBEAFE;color:#1D4ED8;border-radius:50%;display:flex;align-items:center;justify-content:center;flex-shrink:0;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m11.25 11.25.041-.02a.75.75 0 0 1 1.063.852l-.708 2.836a.75.75 0 0 0 1.063.853l.041-.021M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9-3.75h.008v.008H12V8.25Z" />
                    </svg>
                </div>
                <div>
                    <h6 style="font-weight:700;color:#1E3A8A;margin-bottom:0.35rem;font-size:0.95rem;">
                        Informasi Cara Kerja Status Akreditasi Nasional
                    </h6>
                    <p style="font-size:0.85rem;color:#334155;margin:0;line-height:1.6;">
                        Secara standar sistem, 3 kartu rekapitulasi di bawah ini <strong>terhitung secara otomatis</strong> dari daftar program studi yang Anda masukkan di menu <a href="akreditasi-prodi-list.php" style="color:#2563EB;font-weight:600;">Akreditasi Program Studi</a> (total saat ini: <strong><?= count($prodi_list) ?> prodi</strong>).<br>
                        Melalui halaman ini, Anda dapat mengubah teks judul &amp; deskripsi, atau mengganti mode ke <strong>Input Manual</strong> jika ingin menentukan angka kustom secara langsung.
                    </p>
                </div>
            </div>
        </div>

        <!-- Live Preview Kartu (Mirip Tampilan Halaman Publik) -->
        <div class="admin-table-wrap mb-4" style="padding:1.5rem 1.75rem;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-4 pb-2 border-bottom">
                <div>
                    <h5 style="font-weight:700;color:var(--navy);font-size:1.15rem;margin:0;">
                        Tampilan Status Akreditasi Nasional Saat Ini
                    </h5>
                    <small class="text-muted">
                        Status saat ini: <span class="badge" style="background:<?= $akred_nasional_mode === 'manual' ? '#FEF3C7' : '#DCFCE7' ?>;color:<?= $akred_nasional_mode === 'manual' ? '#92400E' : '#166534' ?>;font-weight:700;">Mode <?= ucfirst($akred_nasional_mode) ?></span>
                    </small>
                </div>
                <div class="text-end">
                    <h6 class="mb-0 text-uppercase" style="font-weight:800;color:var(--navy);letter-spacing:0.5px;"><?= e($akred_nasional_title) ?></h6>
                    <small class="text-muted" style="max-width:450px;display:inline-block;"><?= e($akred_nasional_desc) ?></small>
                </div>
            </div>

            <!-- 3 Kartu Rekapitulasi -->
            <div class="row g-4">
                <!-- 1. Peringkat -->
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm" style="border-radius:12px;overflow:hidden;background:#FFFFFF;border:1px solid #E2E8F0 !important;">
                        <div style="background:var(--navy);color:#fff;padding:0.75rem 1rem;display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-weight:700;font-size:0.9rem;">Peringkat</span>
                            <span style="font-weight:700;font-size:0.85rem;">Jumlah</span>
                        </div>
                        <div class="p-3 d-flex flex-column justify-content-between h-100">
                            <div class="d-flex flex-column gap-2 mb-3">
                                <?php 
                                $tot_p = 0;
                                foreach ($display_peringkat as $lbl => $jml): 
                                    $tot_p += $jml;
                                    $is_unggul = (strtoupper($lbl) === 'UNGGUL');
                                ?>
                                <div class="d-flex align-items-center justify-content-between p-2 px-3 rounded-2" style="background:#F8FAFC;border:1px solid #EDF2F7;">
                                    <span style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?= e($lbl) ?></span>
                                    <span class="badge" style="background:<?= $is_unggul ? '#E8F5E9' : '#0A192F' ?>;color:<?= $is_unggul ? '#1B5E20' : '#FFFFFF' ?>;font-weight:700;font-size:0.82rem;min-width:30px;padding:0.35rem 0.55rem;border-radius:6px;">
                                        <?= $jml ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="pt-2 border-top d-flex justify-content-between align-items-center" style="font-weight:700;color:var(--navy);font-size:0.9rem;">
                                <span>Total Program Studi</span>
                                <span class="badge" style="background:var(--navy);color:#fff;border-radius:6px;padding:0.35rem 0.65rem;"><?= $tot_p ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. Lembaga Akreditasi -->
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm" style="border-radius:12px;overflow:hidden;background:#FFFFFF;border:1px solid #E2E8F0 !important;">
                        <div style="background:var(--navy);color:#fff;padding:0.75rem 1rem;display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-weight:700;font-size:0.9rem;">Lembaga Akreditasi</span>
                            <span style="font-weight:700;font-size:0.85rem;">Jumlah</span>
                        </div>
                        <div class="p-3 d-flex flex-column justify-content-between h-100">
                            <div class="d-flex flex-column gap-2 mb-3">
                                <?php 
                                $tot_l = 0;
                                foreach ($display_lembaga as $lbl => $jml): 
                                    $tot_l += $jml;
                                ?>
                                <div class="d-flex align-items-center justify-content-between p-2 px-3 rounded-2" style="background:#F8FAFC;border:1px solid #EDF2F7;">
                                    <span style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?= e($lbl) ?></span>
                                    <span class="badge" style="background:<?= $jml > 0 ? '#0A192F' : '#E2E8F0' ?>;color:<?= $jml > 0 ? '#FFFFFF' : '#64748B' ?>;font-weight:700;font-size:0.82rem;min-width:30px;padding:0.35rem 0.55rem;border-radius:6px;">
                                        <?= $jml > 0 ? $jml : '-' ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="pt-2 border-top d-flex justify-content-between align-items-center" style="font-weight:700;color:var(--navy);font-size:0.9rem;">
                                <span>Total Program Studi</span>
                                <span class="badge" style="background:var(--navy);color:#fff;border-radius:6px;padding:0.35rem 0.65rem;"><?= $tot_l ?></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 3. Jenjang Pendidikan -->
                <div class="col-lg-4">
                    <div class="card h-100 border-0 shadow-sm" style="border-radius:12px;overflow:hidden;background:#FFFFFF;border:1px solid #E2E8F0 !important;">
                        <div style="background:var(--navy);color:#fff;padding:0.75rem 1rem;display:flex;align-items:center;justify-content:space-between;">
                            <span style="font-weight:700;font-size:0.9rem;">Jenjang Pendidikan</span>
                            <span style="font-weight:700;font-size:0.85rem;">Jumlah</span>
                        </div>
                        <div class="p-3 d-flex flex-column justify-content-between h-100">
                            <div class="d-flex flex-column gap-2 mb-3">
                                <?php 
                                $tot_j = 0;
                                foreach ($display_jenjang as $lbl => $jml): 
                                    $tot_j += $jml;
                                ?>
                                <div class="d-flex align-items-center justify-content-between p-2 px-3 rounded-2" style="background:#F8FAFC;border:1px solid #EDF2F7;">
                                    <span style="font-weight:600;color:#1E293B;font-size:0.85rem;"><?= e($lbl) ?></span>
                                    <span class="badge" style="background:<?= $jml > 0 ? '#0A192F' : '#E2E8F0' ?>;color:<?= $jml > 0 ? '#FFFFFF' : '#64748B' ?>;font-weight:700;font-size:0.82rem;min-width:30px;padding:0.35rem 0.55rem;border-radius:6px;">
                                        <?= $jml ?>
                                    </span>
                                </div>
                                <?php endforeach; ?>
                            </div>
                            <div class="pt-2 border-top d-flex justify-content-between align-items-center" style="font-weight:700;color:var(--navy);font-size:0.9rem;">
                                <span>Total Program Studi</span>
                                <span class="badge" style="background:var(--navy);color:#fff;border-radius:6px;padding:0.35rem 0.65rem;"><?= $tot_j ?></span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Form Edit Judul, Deskripsi & Mode -->
        <div class="admin-table-wrap mb-4" style="padding:1.75rem;">
            <div class="admin-table-title mb-3 pb-2 border-bottom">
                Pengaturan Judul &amp; Mode Perhitungan Section
            </div>

            <form method="POST">
                <!-- Judul Section -->
                <div class="mb-3">
                    <label class="form-label" style="font-weight:600;color:var(--navy);">Judul Section</label>
                    <input type="text" name="akred_nasional_title" class="form-control" value="<?= e($akred_nasional_title) ?>" required style="border-radius:8px;">
                    <small class="text-muted">Judul utama section yang tampil berhuruf kapital di halaman akreditasi.</small>
                </div>

                <!-- Deskripsi Section -->
                <div class="mb-4">
                    <label class="form-label" style="font-weight:600;color:var(--navy);">Deskripsi / Keterangan Singkat</label>
                    <textarea name="akred_nasional_desc" class="form-control" rows="2" style="border-radius:8px;"><?= e($akred_nasional_desc) ?></textarea>
                    <small class="text-muted">Paragraf pengantar yang menjelaskan capaian akreditasi prodi di bawah judul.</small>
                </div>

                <!-- Pilihan Mode -->
                <div class="mb-4 p-3 rounded-3" style="background:#F8FAFC;border:1.5px solid #E2E8F0;">
                    <label class="form-label d-block mb-2" style="font-weight:700;color:var(--navy);">
                        Metode Penentuan Angka Rekapitulasi:
                    </label>
                    <div class="d-flex flex-column flex-md-row gap-3">
                        <label class="d-flex align-items-start gap-2 p-2 rounded-2" style="cursor:pointer;flex:1;background:#FFFFFF;border:1px solid #CBD5E1;">
                            <input type="radio" name="akred_nasional_mode" value="otomatis" <?= $akred_nasional_mode === 'otomatis' ? 'checked' : '' ?> onchange="toggleManualInputs(false)" style="margin-top:3px;">
                            <div>
                                <span style="font-weight:600;color:#1E293B;font-size:0.9rem;">Mode Otomatis (Direkomendasikan)</span>
                                <p class="text-muted mb-0" style="font-size:0.8rem;line-height:1.4;">
                                    Angka dihitung otomatis oleh sistem dari tabel program studi. Setiap ada penambahan prodi baru, data otomatis update.
                                </p>
                            </div>
                        </label>
                        <label class="d-flex align-items-start gap-2 p-2 rounded-2" style="cursor:pointer;flex:1;background:#FFFFFF;border:1px solid #CBD5E1;">
                            <input type="radio" name="akred_nasional_mode" value="manual" <?= $akred_nasional_mode === 'manual' ? 'checked' : '' ?> onchange="toggleManualInputs(true)" style="margin-top:3px;">
                            <div>
                                <span style="font-weight:600;color:#1E293B;font-size:0.9rem;">Mode Input Manual</span>
                                <p class="text-muted mb-0" style="font-size:0.8rem;line-height:1.4;">
                                    Gunakan angka kustom yang Anda tentukan sendiri di bawah ini (mengabaikan hitungan otomatis database prodi).
                                </p>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Form Input Manual (Collapsible) -->
                <div id="manualInputContainer" style="display:<?= $akred_nasional_mode === 'manual' ? 'block' : 'none' ?>;margin-bottom:1.5rem;padding:1.25rem;background:#FFFBEB;border:1.5px solid #FDE68A;border-radius:10px;">
                    <h6 style="font-weight:700;color:#92400E;margin-bottom:0.75rem;display:flex;align-items:center;gap:0.4rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                        </svg>
                        Formulir Angka Manual (Kustom)
                    </h6>
                    <p style="font-size:0.82rem;color:#78350F;margin-bottom:1rem;">
                        Ubah angka di bawah sesuai kebutuhan. Angka inilah yang akan tampil di halaman depan akreditasi.
                    </p>

                    <div class="row g-4">
                        <!-- Custom Peringkat -->
                        <div class="col-md-4">
                            <div style="background:#FFFFFF;padding:1rem;border-radius:8px;border:1px solid #FCD34D;">
                                <span style="font-weight:700;color:#1E293B;font-size:0.85rem;display:block;margin-bottom:0.65rem;">1. Peringkat</span>
                                <?php foreach ($auto_peringkat as $lbl => $val_auto): 
                                    $cur_val = $akred_nasional_custom['peringkat'][$lbl] ?? $val_auto;
                                ?>
                                <div class="mb-2">
                                    <label class="form-label mb-1" style="font-size:0.8rem;color:#475569;"><?= e($lbl) ?></label>
                                    <input type="number" name="custom[peringkat][<?= e($lbl) ?>]" class="form-control form-control-sm" value="<?= (int)$cur_val ?>" min="0">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Custom Lembaga -->
                        <div class="col-md-4">
                            <div style="background:#FFFFFF;padding:1rem;border-radius:8px;border:1px solid #FCD34D;">
                                <span style="font-weight:700;color:#1E293B;font-size:0.85rem;display:block;margin-bottom:0.65rem;">2. Lembaga Akreditasi</span>
                                <?php foreach ($auto_lembaga as $lbl => $val_auto): 
                                    $cur_val = $akred_nasional_custom['lembaga'][$lbl] ?? $val_auto;
                                ?>
                                <div class="mb-2">
                                    <label class="form-label mb-1" style="font-size:0.8rem;color:#475569;"><?= e($lbl) ?></label>
                                    <input type="number" name="custom[lembaga][<?= e($lbl) ?>]" class="form-control form-control-sm" value="<?= (int)$cur_val ?>" min="0">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>

                        <!-- Custom Jenjang -->
                        <div class="col-md-4">
                            <div style="background:#FFFFFF;padding:1rem;border-radius:8px;border:1px solid #FCD34D;">
                                <span style="font-weight:700;color:#1E293B;font-size:0.85rem;display:block;margin-bottom:0.65rem;">3. Jenjang Pendidikan</span>
                                <?php foreach ($auto_jenjang as $lbl => $val_auto): 
                                    $cur_val = $akred_nasional_custom['jenjang'][$lbl] ?? $val_auto;
                                ?>
                                <div class="mb-2">
                                    <label class="form-label mb-1" style="font-size:0.8rem;color:#475569;"><?= e($lbl) ?></label>
                                    <input type="number" name="custom[jenjang][<?= e($lbl) ?>]" class="form-control form-control-sm" value="<?= (int)$cur_val ?>" min="0">
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="d-flex justify-content-end pt-3 border-top">
                    <button type="submit" class="btn-submit">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" />
                        </svg>
                        Simpan Pengaturan Status Akreditasi
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function toggleManualInputs(isManual) {
    const box = document.getElementById('manualInputContainer');
    if (box) {
        box.style.display = isManual ? 'block' : 'none';
    }
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
