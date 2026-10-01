<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Chart & Portal SPMI';
$db = getDB();

$flash = $_SESSION['flash'] ?? '';
$error = '';
unset($_SESSION['flash']);

// Default Chart PPEPP Settings
$chart_defaults = [
    'title' => 'Siklus PPEPP SPMI Interaktif',
    'badge' => 'Siklus Penjaminan Mutu Berkelanjutan',
    'desc'  => 'Implementasi penjaminan mutu di Universitas Katolik Soegijapranata berlandaskan pada 5 tahap siklus berkelanjutan (PPEPP). Klik salah satu lingkaran siklus di bawah ini untuk menelaah penjelasan rinci setiap tahapannya.',
];

// List of portals to manage
$portals_def = [
    'sista' => [
        'name'             => 'Portal SISTA',
        'badge'            => 'STANDAR AKADEMIK',
        'desc'             => 'Pemantauan & pelaporan siklus PPEPP, perumusan capaian, bukti dukung pelaksanaan, dan rencana tindak lanjut termonitor secara digital terintegrasi.',
        'icon'             => 'bi-mortarboard',
        'default_url'      => 'https://sista.unika.ac.id',
        'default_st'       => 'active',
        'btn_label'        => 'Buka Portal SISTA',
        'default_cs_title' => 'Tautan Sistem SISTA Belum Dibuka',
        'default_cs_desc'  => 'Pemantauan dan pelaporan siklus PPEPP diaktifkan sesuai jadwal.',
    ],
    'spmi' => [
        'name'             => 'Portal SPMI Kemendikti',
        'badge'            => 'PELAPORAN NASIONAL',
        'desc'             => 'Rekapitulasi dan pelaporan evaluasi pelaksanaan penjaminan mutu perguruan tinggi secara berkala kepada Kementerian Pendidikan Tinggi, Sains, dan Teknologi.',
        'icon'             => 'bi-shield-check',
        'default_url'      => 'https://spmi.kemdiktisaintek.go.id/auth/login',
        'default_st'       => 'active',
        'btn_label'        => 'Buka Portal SPMI',
        'default_cs_title' => 'Tautan Sistem SPMI Kemendikti Belum Dibuka',
        'default_cs_desc'  => 'Pelaporan evaluasi pelaksanaan penjaminan mutu akan diaktifkan sesuai jadwal.',
    ],
    'eppepp' => [
        'name'             => 'Portal E-PPEPP',
        'badge'            => 'SIKLUS MUTU PPEPP',
        'desc'             => 'Sistem informasi elektronik implementasi, evaluasi pelaksanaan, dan pengendalian tahapan siklus PPEPP secara berkesinambungan.',
        'icon'             => 'bi-arrow-repeat',
        'default_url'      => 'https://e-ppepp.unika.ac.id',
        'default_st'       => 'coming_soon',
        'btn_label'        => 'Buka Portal E-PPEPP',
        'default_cs_title' => 'Tautan Sistem e-PPEPP Belum Dibuka',
        'default_cs_desc'  => 'Pelaksanaan dan evaluasi sistem e-PPEPP akan diaktifkan sesuai jadwal.',
    ]
];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validation_errors = [];

    // 1. Chart Settings
    $spmi_chart_title = trim($_POST['spmi_chart_title'] ?? $chart_defaults['title']);
    $spmi_chart_badge = trim($_POST['spmi_chart_badge'] ?? $chart_defaults['badge']);
    $spmi_chart_desc  = trim($_POST['spmi_chart_desc'] ?? $chart_defaults['desc']);

    if (empty($spmi_chart_title)) $spmi_chart_title = $chart_defaults['title'];
    if (empty($spmi_chart_badge)) $spmi_chart_badge = $chart_defaults['badge'];
    if (empty($spmi_chart_desc))  $spmi_chart_desc  = $chart_defaults['desc'];

    // 2. Portals Settings
    $portal_post_data = [];
    foreach ($portals_def as $key => $p) {
        $p_name     = trim($_POST[$key . '_name'] ?? $p['name']);
        $p_badge    = trim($_POST[$key . '_badge'] ?? $p['badge']);
        $p_desc     = trim($_POST[$key . '_desc'] ?? $p['desc']);
        $st         = trim($_POST[$key . '_status'] ?? $p['default_st']);
        $url        = trim($_POST[$key . '_url'] ?? '');
        $cs_title   = trim($_POST[$key . '_cs_title'] ?? $p['default_cs_title']);
        $cs_desc    = trim($_POST[$key . '_cs_desc'] ?? $p['default_cs_desc']);

        if (empty($p_name))  $p_name  = $p['name'];
        if (empty($p_badge)) $p_badge = $p['badge'];
        if (empty($p_desc))  $p_desc  = $p['desc'];

        // Validasi: Bila aktif, URL WAJIB diisi dan valid!
        if ($st === 'active') {
            if (empty($url)) {
                $validation_errors[] = "Tautan URL untuk <strong>{$p_name}</strong> wajib diisi jika status dipilih Aktif.";
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $validation_errors[] = "Format URL untuk <strong>{$p_name}</strong> tidak valid (harus diawali http:// atau https://).";
            }
        }

        if (empty($cs_title)) {
            $cs_title = $p['default_cs_title'];
        }
        if (empty($cs_desc)) {
            $cs_desc = $p['default_cs_desc'];
        }

        $portal_post_data[$key] = [
            'name'     => $p_name,
            'badge'    => $p_badge,
            'desc'     => $p_desc,
            'st'       => $st,
            'url'      => $url,
            'cs_title' => $cs_title,
            'cs_desc'  => $cs_desc
        ];
    }

    if (empty($validation_errors)) {
        // Simpan Chart Settings
        setPengaturan('spmi_chart_title', $spmi_chart_title);
        setPengaturan('spmi_chart_badge', $spmi_chart_badge);
        setPengaturan('spmi_chart_desc',  $spmi_chart_desc);

        // Simpan Portals Settings
        foreach ($portal_post_data as $key => $d) {
            setPengaturan('portal_' . $key . '_name',     $d['name']);
            setPengaturan('portal_' . $key . '_badge',    $d['badge']);
            setPengaturan('portal_' . $key . '_desc',     $d['desc']);
            setPengaturan('portal_' . $key . '_status',   $d['st']);
            setPengaturan('portal_' . $key . '_url',      $d['url']);
            setPengaturan('portal_' . $key . '_cs_title', $d['cs_title']);
            setPengaturan('portal_' . $key . '_cs_desc',  $d['cs_desc']);
        }

        $_SESSION['flash'] = 'Pengaturan judul chart, penjelasan portal, dan tautan SPMI berhasil disimpan.';
        redirect(SITE_URL . '/admin/spmi-portal-setting.php');
    } else {
        $error = implode('<br>', $validation_errors);
    }
}

// Current values
$chart_val = [
    'title' => getPengaturan('spmi_chart_title', $chart_defaults['title']),
    'badge' => getPengaturan('spmi_chart_badge', $chart_defaults['badge']),
    'desc'  => getPengaturan('spmi_chart_desc',  $chart_defaults['desc']),
];

$portal_values = [];
foreach ($portals_def as $key => $p) {
    $portal_values[$key] = [
        'name'     => getPengaturan('portal_' . $key . '_name', $p['name']),
        'badge'    => getPengaturan('portal_' . $key . '_badge', $p['badge']),
        'desc'     => getPengaturan('portal_' . $key . '_desc', $p['desc']),
        'status'   => getPengaturan('portal_' . $key . '_status', $p['default_st']),
        'url'      => getPengaturan('portal_' . $key . '_url', $p['default_url']),
        'cs_title' => getPengaturan('portal_' . $key . '_cs_title', $p['default_cs_title']),
        'cs_desc'  => getPengaturan('portal_' . $key . '_cs_desc', $p['default_cs_desc']),
    ];
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-10 col-lg-11">
        <!-- Breadcrumb -->
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);text-decoration:none;">Dashboard</a>
            <span>/</span>
            <a href="dokumen-list.php" style="color:var(--text-muted);text-decoration:none;">Halaman SPMI</a>
            <span>/</span>
            <span style="color:var(--navy);font-weight:600;">Chart &amp; Portal SPMI</span>
        </div>

        <?php if ($flash): ?>
        <div class="alert-lpm alert-success mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <?= e($flash) ?>
        </div>
        <?php endif; ?>

        <?php if ($error): ?>
        <div class="alert-lpm alert-danger mb-4">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <div><?= $error ?></div>
        </div>
        <?php endif; ?>

        <!-- Intro Banner Card -->
        <div class="card mb-4 border-0 shadow-sm" style="border-radius:12px;background:#F8FAFC;border:1px solid #E2E8F0;">
            <div class="card-body p-4 d-flex align-items-center gap-3">
                <div style="width:48px;height:48px;border-radius:12px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;color:#6D28D9;font-size:1.4rem;flex-shrink:0;">
                    <i class="bi bi-pie-chart-fill"></i>
                </div>
                <div>
                    <h5 class="fw-bold mb-1" style="color:var(--navy);font-size:1.1rem;">Pengelolaan Konten SPMI (Chart PPEPP &amp; 3 Portal Utama)</h5>
                    <div class="small text-muted" style="line-height:1.5;">
                        Di sini Anda dapat mengedit <strong>Judul &amp; Penjelasan Chart Siklus PPEPP</strong> serta <strong>Judul, Badge Kategori, Penjelasan, Status &amp; Tautan</strong> ketiga portal mutu (Portal SISTA, Portal SPMI Kemendikti, dan Portal E-PPEPP).
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" id="formPortals">

            <!-- ============================================================ -->
            <!-- BAGIAN 1: PENGATURAN JUDUL & PENJELASAN CHART SIKLUS PPEPP  -->
            <!-- ============================================================ -->
            <div class="card border-0 shadow-sm mb-4" style="border-radius:14px;overflow:hidden;border:1px solid #E2E8F0;">
                <div class="card-header p-3 px-4 d-flex align-items-center justify-content-between" style="background:#0F172A;color:#ffffff;">
                    <div class="d-flex align-items-center gap-2">
                        <i class="bi bi-diagram-3-fill text-warning fs-5"></i>
                        <h5 class="mb-0 fw-bold" style="font-size:1.05rem;color:#ffffff;">
                            1. Judul &amp; Penjelasan Chart Siklus PPEPP SPMI
                        </h5>
                    </div>
                    <span class="badge" style="background:rgba(255,255,255,0.15);color:#FFD54F;font-size:0.75rem;">
                        Diagram Interaktif SPMI
                    </span>
                </div>
                <div class="card-body p-4">
                    <div class="row g-4 align-items-start">
                        <!-- Kolom Form Input Chart -->
                        <div class="col-lg-7 col-md-12">
                            <div class="mb-3">
                                <label class="form-label fw-bold small text-navy" for="spmi_chart_badge">
                                    Label Subjudul / Tagline Chart <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control" id="spmi_chart_badge" name="spmi_chart_badge" value="<?= e($chart_val['badge']) ?>" required placeholder="Contoh: Siklus Penjaminan Mutu Berkelanjutan">
                                <div class="form-text small text-muted">
                                    Teks badge kecil di atas judul diagram lingkaran PPEPP.
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold small text-navy" for="spmi_chart_title">
                                    Judul Chart Siklus PPEPP <span class="text-danger">*</span>
                                </label>
                                <input type="text" class="form-control fw-bold" id="spmi_chart_title" name="spmi_chart_title" value="<?= e($chart_val['title']) ?>" required placeholder="Contoh: Siklus PPEPP SPMI Interaktif">
                                <div class="form-text small text-muted">
                                    Judul utama yang tampil besar di atas diagram siklus pada halaman SPMI.
                                </div>
                            </div>

                            <div class="mb-0">
                                <label class="form-label fw-bold small text-navy" for="spmi_chart_desc">
                                    Penjelasan / Narasi Pengantar Chart <span class="text-danger">*</span>
                                </label>
                                <textarea class="form-control" id="spmi_chart_desc" name="spmi_chart_desc" rows="4" required style="line-height:1.6;" placeholder="Tuliskan narasi penjelasan implementasi siklus PPEPP..."><?= e($chart_val['desc']) ?></textarea>
                                <div class="form-text small text-muted">
                                    Deskripsi pengantar yang memberikan penjelasan fungsi dan alur siklus PPEPP kepada pengunjung.
                                </div>
                            </div>
                        </div>

                        <!-- Kolom Live Pratinjau Header Chart -->
                        <div class="col-lg-5 col-md-12">
                            <div class="small fw-bold text-muted mb-2">Pratinjau Tampilan Header Chart (Publik):</div>
                            <div class="p-4 rounded-4 shadow-sm bg-white border text-center" style="background:#F8FAFC;">
                                <div class="d-inline-flex align-items-center gap-1 badge rounded-pill px-3 py-1 mb-2 prev-chart-badge" style="background:#EFF6FF;color:#1D4ED8;font-size:0.75rem;font-weight:700;">
                                    <i class="bi bi-arrow-repeat me-1"></i>
                                    <span><?= e($chart_val['badge']) ?></span>
                                </div>
                                <h4 class="fw-bold mb-2 prev-chart-title" style="color:var(--navy);font-size:1.25rem;">
                                    <?= e($chart_val['title']) ?>
                                </h4>
                                <p class="text-muted small mb-0 prev-chart-desc" style="line-height:1.65;font-size:0.82rem;">
                                    <?= nl2br(e($chart_val['desc'])) ?>
                                </p>
                            </div>
                            <div class="text-center mt-2">
                                <small class="text-muted"><i class="bi bi-info-circle me-1"></i>Diagram lingkaran siklus 5 tahap akan berada langsung di bawah teks ini.</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- ============================================================ -->
            <!-- BAGIAN 2: PENGATURAN 3 PORTAL UTAMA SPMI                     -->
            <!-- ============================================================ -->
            <div class="d-flex align-items-center justify-content-between mb-3 mt-4">
                <div>
                    <h5 class="fw-bold mb-0" style="color:var(--navy);font-size:1.15rem;">
                        <i class="bi bi-grid-fill text-primary me-2"></i>2. Pengaturan 3 Portal Utama SPMI
                    </h5>
                    <div class="text-muted small">Atur judul portal, badge kategori, penjelasan fungsi, status ketersediaan, dan tautan masing-masing portal.</div>
                </div>
            </div>

            <div class="row g-4 mb-4">
                <?php foreach ($portals_def as $key => $p): 
                    $val = $portal_values[$key];
                    $is_active = ($val['status'] === 'active');
                ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden;border:1px solid #E2E8F0;">
                        <div class="card-header p-3 px-4 d-flex align-items-center justify-content-between" style="background:#F1F5F9;border-bottom:1px solid #E2E8F0;">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge prev-badge-pill-<?= $key ?>" style="background:#0F172A;color:#FFD54F;font-size:0.72rem;letter-spacing:0.5px;padding:0.35rem 0.65rem;">
                                    <?= e($val['badge']) ?>
                                </span>
                                <h5 class="mb-0 fw-bold prev-name-pill-<?= $key ?>" style="color:var(--navy);font-size:1.05rem;">
                                    <?= e($val['name']) ?>
                                </h5>
                            </div>
                            <span class="badge status-pill-<?= $key ?>" style="background:<?= $is_active ? '#DCFCE7' : '#E0F2FE' ?>;color:<?= $is_active ? '#15803D' : '#0369A1' ?>;font-weight:700;font-size:0.75rem;padding:0.35rem 0.75rem;border-radius:20px;border:1px solid <?= $is_active ? '#86EFAC' : '#BAE6FD' ?>;">
                                <i class="bi <?= $is_active ? 'bi-check-circle-fill' : 'bi-hourglass-split' ?> me-1"></i>
                                <?= $is_active ? 'Status: Aktif' : 'Status: Coming Soon' ?>
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4 align-items-start">
                                <!-- Kolom Pengaturan Input Portal -->
                                <div class="col-lg-7 col-md-12">

                                    <div class="row g-3 mb-3">
                                        <!-- Judul Portal -->
                                        <div class="col-md-7">
                                            <label class="form-label fw-bold small text-navy" for="<?= $key ?>_name">
                                                Judul Portal <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control portal-name-input" id="<?= $key ?>_name" name="<?= $key ?>_name" value="<?= e($val['name']) ?>" required data-portal="<?= $key ?>">
                                        </div>

                                        <!-- Badge Kategori -->
                                        <div class="col-md-5">
                                            <label class="form-label fw-bold small text-navy" for="<?= $key ?>_badge">
                                                Badge Kategori <span class="text-danger">*</span>
                                            </label>
                                            <input type="text" class="form-control portal-badge-input" id="<?= $key ?>_badge" name="<?= $key ?>_badge" value="<?= e($val['badge']) ?>" required data-portal="<?= $key ?>">
                                        </div>
                                    </div>

                                    <!-- Penjelasan / Deskripsi Portal -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-navy" for="<?= $key ?>_desc">
                                            Penjelasan Portal <span class="text-danger">*</span>
                                        </label>
                                        <textarea class="form-control portal-desc-input" id="<?= $key ?>_desc" name="<?= $key ?>_desc" rows="3" required style="line-height:1.55;" data-portal="<?= $key ?>" placeholder="Tuliskan penjelasan fungsi dari portal ini..."><?= e($val['desc']) ?></textarea>
                                        <div class="form-text small text-muted">
                                            Teks penjelasan detail fungsi modul sistem ini yang akan tampil di card portal halaman SPMI publik.
                                        </div>
                                    </div>

                                    <!-- Pilih Status Ketersediaan -->
                                    <div class="mb-3">
                                        <label class="form-label fw-bold small text-navy" for="<?= $key ?>_status">
                                            Status Ketersediaan Portal <span class="text-danger">*</span>
                                        </label>
                                        <div class="d-flex gap-3">
                                            <div class="form-check p-2 px-3 rounded-3 border flex-grow-1" style="background:#FAF5FF;cursor:pointer;">
                                                <input class="form-check-input status-radio" type="radio" name="<?= $key ?>_status" id="<?= $key ?>_status_active" value="active" <?= $is_active ? 'checked' : '' ?> data-portal="<?= $key ?>">
                                                <label class="form-check-label fw-semibold text-dark small" for="<?= $key ?>_status_active" style="cursor:pointer;">
                                                    <i class="bi bi-check-circle-fill text-success me-1"></i> Aktif (Buka Portal dengan Link)
                                                </label>
                                            </div>
                                            <div class="form-check p-2 px-3 rounded-3 border flex-grow-1" style="background:#F0F9FF;cursor:pointer;">
                                                <input class="form-check-input status-radio" type="radio" name="<?= $key ?>_status" id="<?= $key ?>_status_cs" value="coming_soon" <?= !$is_active ? 'checked' : '' ?> data-portal="<?= $key ?>">
                                                <label class="form-check-label fw-semibold text-dark small" for="<?= $key ?>_status_cs" style="cursor:pointer;">
                                                    <i class="bi bi-hourglass-split text-info me-1"></i> Coming Soon (Segera Hadir)
                                                </label>
                                            </div>
                                        </div>
                                    </div>

                                    <!-- Input URL (Wajib bila Aktif) -->
                                    <div class="mb-3 url-wrap-<?= $key ?>">
                                        <label class="form-label fw-bold small text-navy" for="<?= $key ?>_url">
                                            Tautan URL Portal <span class="text-danger">*</span>
                                        </label>
                                        <div class="input-group">
                                            <span class="input-group-text bg-light text-muted"><i class="bi bi-link-45deg"></i></span>
                                            <input type="url" class="form-control portal-url-input" id="<?= $key ?>_url" name="<?= $key ?>_url" placeholder="https://..." value="<?= e($val['url']) ?>" data-portal="<?= $key ?>" <?= $is_active ? 'required' : '' ?>>
                                        </div>
                                        <div class="form-text small text-muted">
                                            Tautan sistem yang akan dibuka ketika pengunjung mengklik tombol di website publik.
                                        </div>
                                    </div>

                                    <!-- Kustomisasi Teks Coming Soon -->
                                    <div class="p-3 rounded-3 border bg-light cs-custom-wrap-<?= $key ?>" style="display:<?= !$is_active ? 'block' : 'none' ?>;">
                                        <div class="fw-bold small text-navy mb-2 d-flex align-items-center gap-1">
                                            <i class="bi bi-sliders text-primary"></i> Teks Kotak Segera Hadir
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label small text-muted mb-1" for="<?= $key ?>_cs_title">Judul Kotak Segera Hadir:</label>
                                            <input type="text" class="form-control form-control-sm cs-title-input" id="<?= $key ?>_cs_title" name="<?= $key ?>_cs_title" value="<?= e($val['cs_title']) ?>" data-portal="<?= $key ?>">
                                        </div>
                                        <div>
                                            <label class="form-label small text-muted mb-1" for="<?= $key ?>_cs_desc">Keterangan Tambahan:</label>
                                            <input type="text" class="form-control form-control-sm cs-desc-input" id="<?= $key ?>_cs_desc" name="<?= $key ?>_cs_desc" value="<?= e($val['cs_desc']) ?>" data-portal="<?= $key ?>">
                                        </div>
                                    </div>
                                </div>

                                <!-- Kolom Live Visual Preview Card Publik -->
                                <div class="col-lg-5 col-md-12">
                                    <div class="small fw-bold text-muted mb-2">Pratinjau Tampilan Card Publik:</div>
                                    <div class="p-4 rounded-4 shadow-sm text-white" style="background:linear-gradient(145deg, #0A192F 0%, #132D54 100%);border:1px solid rgba(255,255,255,0.15);">
                                        <div class="d-flex align-items-center justify-content-between mb-3">
                                            <span class="badge prev-card-badge-<?= $key ?>" style="background:rgba(255,255,255,0.15);color:#FFD54F;font-size:0.72rem;padding:0.35rem 0.65rem;font-weight:700;letter-spacing:0.5px;">
                                                <?= e($val['badge']) ?>
                                            </span>
                                            <div style="width:34px;height:34px;border-radius:10px;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;color:#FFD54F;font-size:0.95rem;">
                                                <i class="bi <?= e($p['icon']) ?>"></i>
                                            </div>
                                        </div>
                                        <div class="fw-bold text-white mb-2 prev-card-name-<?= $key ?>" style="font-size:1.15rem;">
                                            <?= e($val['name']) ?>
                                        </div>
                                        <div class="text-white-50 small mb-4 prev-card-desc-<?= $key ?>" style="font-size:0.84rem;line-height:1.6;min-height:55px;">
                                            <?= nl2br(e($val['desc'])) ?>
                                        </div>

                                        <!-- Preview Bagian Aksi / Coming Soon -->
                                        <div class="pt-3 border-top" style="border-color:rgba(255,255,255,0.12) !important;">
                                            <!-- Preview State Aktif -->
                                            <div id="preview_active_<?= $key ?>" style="display:<?= $is_active ? 'block' : 'none' ?>;">
                                                <div class="btn w-100 py-2 fw-bold rounded-3 d-flex align-items-center justify-content-center gap-2 text-white" style="background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border:none;font-size:0.86rem;box-shadow:0 4px 12px rgba(123,31,162,0.4);pointer-events:none;">
                                                    <span class="prev-btn-label-<?= $key ?>">Buka <?= e($val['name']) ?></span>
                                                    <i class="bi bi-box-arrow-up-right" style="font-size:0.75rem;"></i>
                                                </div>
                                            </div>

                                            <!-- Preview State Coming Soon -->
                                            <div id="preview_cs_<?= $key ?>" style="display:<?= !$is_active ? 'block' : 'none' ?>;">
                                                <div class="p-3 rounded-4 bg-white bg-opacity-10 border border-white border-opacity-15 text-center w-100">
                                                    <div class="badge bg-info text-dark px-3 py-1 rounded-pill mb-2 fw-bold" style="font-size:0.72rem;">
                                                        <i class="bi bi-hourglass-split me-1"></i> Segera Hadir
                                                    </div>
                                                    <div class="text-white fw-bold prev-cs-title-<?= $key ?>" style="font-size:0.88rem;">
                                                        <?= e($val['cs_title']) ?>
                                                    </div>
                                                    <div class="text-white-50 small mt-1 prev-cs-desc-<?= $key ?>" style="font-size:0.74rem;line-height:1.4;">
                                                        <?= e($val['cs_desc']) ?>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <div class="d-flex justify-content-end gap-2 pb-5">
                <a href="dokumen-list.php" class="btn btn-outline-secondary px-4 fw-semibold" style="border-radius:8px;">
                    Batal
                </a>
                <button type="submit" class="btn btn-primary px-4 fw-bold shadow-sm" style="border-radius:8px;background:var(--navy);border:none;">
                    <i class="bi bi-check-circle-fill me-1"></i> Simpan Seluruh Pengaturan SPMI
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // 1. Live preview Chart PPEPP Header
    const chartBadgeInput = document.getElementById('spmi_chart_badge');
    const chartTitleInput = document.getElementById('spmi_chart_title');
    const chartDescInput  = document.getElementById('spmi_chart_desc');

    const prevChartBadge = document.querySelector('.prev-chart-badge span');
    const prevChartTitle = document.querySelector('.prev-chart-title');
    const prevChartDesc  = document.querySelector('.prev-chart-desc');

    if (chartBadgeInput && prevChartBadge) {
        chartBadgeInput.addEventListener('input', function() {
            prevChartBadge.textContent = this.value.trim() || 'Siklus Penjaminan Mutu Berkelanjutan';
        });
    }
    if (chartTitleInput && prevChartTitle) {
        chartTitleInput.addEventListener('input', function() {
            prevChartTitle.textContent = this.value.trim() || 'Siklus PPEPP SPMI Interaktif';
        });
    }
    if (chartDescInput && prevChartDesc) {
        chartDescInput.addEventListener('input', function() {
            prevChartDesc.textContent = this.value.trim() || 'Implementasi penjaminan mutu di Universitas Katolik Soegijapranata berlandaskan pada 5 tahap siklus berkelanjutan (PPEPP).';
        });
    }

    // 2. Live preview Portal Inputs (Name, Badge, Desc)
    document.querySelectorAll('.portal-name-input').forEach(function(inp) {
        inp.addEventListener('input', function() {
            const pKey = this.dataset.portal;
            const val = this.value.trim() || 'Portal';
            const nameEl = document.querySelector('.prev-card-name-' + pKey);
            const pillEl = document.querySelector('.prev-name-pill-' + pKey);
            const btnEl  = document.querySelector('.prev-btn-label-' + pKey);
            if (nameEl) nameEl.textContent = val;
            if (pillEl) pillEl.textContent = val;
            if (btnEl)  btnEl.textContent  = 'Buka ' + val;
        });
    });

    document.querySelectorAll('.portal-badge-input').forEach(function(inp) {
        inp.addEventListener('input', function() {
            const pKey = this.dataset.portal;
            const val = this.value.trim() || 'KATEGORI';
            const badgeEl = document.querySelector('.prev-card-badge-' + pKey);
            const pillEl  = document.querySelector('.prev-badge-pill-' + pKey);
            if (badgeEl) badgeEl.textContent = val;
            if (pillEl)  pillEl.textContent  = val;
        });
    });

    document.querySelectorAll('.portal-desc-input').forEach(function(inp) {
        inp.addEventListener('input', function() {
            const pKey = this.dataset.portal;
            const descEl = document.querySelector('.prev-card-desc-' + pKey);
            if (descEl) descEl.textContent = this.value.trim() || 'Deskripsi fungsi sistem informasi modul SPMI terkait.';
        });
    });

    // 3. Handle radio toggle perubahan status
    document.querySelectorAll('.status-radio').forEach(function(radio) {
        radio.addEventListener('change', function() {
            const portal = this.dataset.portal;
            const val = this.value;
            const isAct = (val === 'active');

            const prevAct = document.getElementById('preview_active_' + portal);
            const prevCs  = document.getElementById('preview_cs_' + portal);
            const csCustomWrap = document.querySelector('.cs-custom-wrap-' + portal);
            const pill = document.querySelector('.status-pill-' + portal);
            const urlInput = document.getElementById(portal + '_url');

            if (isAct) {
                if (prevAct) prevAct.style.display = 'block';
                if (prevCs)  prevCs.style.display = 'none';
                if (csCustomWrap) csCustomWrap.style.display = 'none';
                if (pill) {
                    pill.style.background = '#DCFCE7';
                    pill.style.color = '#15803D';
                    pill.style.borderColor = '#86EFAC';
                    pill.innerHTML = '<i class="bi bi-check-circle-fill me-1"></i> Status: Aktif';
                }
                if (urlInput) urlInput.setAttribute('required', 'required');
            } else {
                if (prevAct) prevAct.style.display = 'none';
                if (prevCs)  prevCs.style.display = 'block';
                if (csCustomWrap) csCustomWrap.style.display = 'block';
                if (pill) {
                    pill.style.background = '#E0F2FE';
                    pill.style.color = '#0369A1';
                    pill.style.borderColor = '#BAE6FD';
                    pill.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Status: Coming Soon';
                }
                if (urlInput) urlInput.removeAttribute('required');
            }
        });
    });

    // 4. Live update preview text Coming Soon
    document.querySelectorAll('.cs-title-input').forEach(function(inp) {
        inp.addEventListener('input', function() {
            const portal = this.dataset.portal;
            const target = document.querySelector('.prev-cs-title-' + portal);
            if (target) target.textContent = this.value.trim() || 'Tautan Sistem Belum Dibuka';
        });
    });

    document.querySelectorAll('.cs-desc-input').forEach(function(inp) {
        inp.addEventListener('input', function() {
            const portal = this.dataset.portal;
            const target = document.querySelector('.prev-cs-desc-' + portal);
            if (target) target.textContent = this.value.trim() || 'Pelaksanaan sistem akan diaktifkan sesuai jadwal.';
        });
    });

    // 5. Form client-side validation
    const form = document.getElementById('formPortals');
    if (form) {
        form.addEventListener('submit', function(e) {
            let hasError = false;
            ['sista', 'spmi', 'eppepp'].forEach(function(pKey) {
                const actRadio = document.getElementById(pKey + '_status_active');
                const urlInput = document.getElementById(pKey + '_url');
                if (actRadio && actRadio.checked) {
                    if (!urlInput || !urlInput.value.trim()) {
                        alert('Tautan URL untuk portal harus diisi jika status dipilih Aktif!');
                        if (urlInput) urlInput.focus();
                        e.preventDefault();
                        hasError = true;
                    }
                }
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
