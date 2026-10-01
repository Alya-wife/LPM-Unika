<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Tautan Portal SPMI';
$db = getDB();

$flash = $_SESSION['flash'] ?? '';
$error = '';
unset($_SESSION['flash']);

// List of portals to manage
$portals_def = [
    'sista' => [
        'name'        => 'Portal SISTA',
        'badge'       => 'STANDAR AKADEMIK',
        'icon'        => 'bi-mortarboard',
        'default_url' => 'https://sista.unika.ac.id',
        'default_st'  => 'active',
        'btn_label'   => 'Buka Portal SISTA',
        'default_cs_title' => 'Tautan Sistem SISTA Belum Dibuka',
        'default_cs_desc'  => 'Pemantauan dan pelaporan siklus PPEPP diaktifkan sesuai jadwal.',
    ],
    'spmi' => [
        'name'        => 'Portal SPMI Kemendikti',
        'badge'       => 'PELAPORAN NASIONAL',
        'icon'        => 'bi-shield-check',
        'default_url' => 'https://spmi.kemdiktisaintek.go.id/auth/login',
        'default_st'  => 'active',
        'btn_label'   => 'Buka Portal SPMI',
        'default_cs_title' => 'Tautan Sistem SPMI Kemendikti Belum Dibuka',
        'default_cs_desc'  => 'Pelaporan evaluasi pelaksanaan penjaminan mutu akan diaktifkan sesuai jadwal.',
    ],
    'eppepp' => [
        'name'        => 'Portal E-PPEPP',
        'badge'       => 'SIKLUS MUTU PPEPP',
        'icon'        => 'bi-arrow-repeat',
        'default_url' => 'https://e-ppepp.unika.ac.id',
        'default_st'  => 'coming_soon',
        'btn_label'   => 'Buka Portal E-PPEPP',
        'default_cs_title' => 'Tautan Sistem e-PPEPP Belum Dibuka',
        'default_cs_desc'  => 'Pelaksanaan dan evaluasi sistem e-PPEPP akan diaktifkan sesuai jadwal.',
    ]
];

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $validation_errors = [];

    foreach ($portals_def as $key => $p) {
        $st = trim($_POST[$key . '_status'] ?? $p['default_st']);
        $url = trim($_POST[$key . '_url'] ?? '');
        $cs_title = trim($_POST[$key . '_cs_title'] ?? $p['default_cs_title']);
        $cs_desc = trim($_POST[$key . '_cs_desc'] ?? $p['default_cs_desc']);

        // Validasi: Bila aktif, URL WAJIB diisi!
        if ($st === 'active') {
            if (empty($url)) {
                $validation_errors[] = "Tautan URL untuk <strong>{$p['name']}</strong> wajib diisi jika status dipilih Aktif.";
            } elseif (!filter_var($url, FILTER_VALIDATE_URL)) {
                $validation_errors[] = "Format URL untuk <strong>{$p['name']}</strong> tidak valid (harus diawali http:// atau https://).";
            }
        }

        if (empty($cs_title)) {
            $cs_title = $p['default_cs_title'];
        }
        if (empty($cs_desc)) {
            $cs_desc = $p['default_cs_desc'];
        }

        if (empty($validation_errors)) {
            setPengaturan('portal_' . $key . '_status', $st);
            setPengaturan('portal_' . $key . '_url', $url);
            setPengaturan('portal_' . $key . '_cs_title', $cs_title);
            setPengaturan('portal_' . $key . '_cs_desc', $cs_desc);
        }
    }

    if (!empty($validation_errors)) {
        $error = implode('<br>', $validation_errors);
    } else {
        $_SESSION['flash'] = 'Pengaturan tautan portal SPMI berhasil disimpan.';
        redirect(SITE_URL . '/admin/spmi-portal-setting.php');
    }
}

// Current values
$portal_values = [];
foreach ($portals_def as $key => $p) {
    $portal_values[$key] = [
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
            <span style="color:var(--navy);font-weight:600;">Tautan Portal SPMI</span>
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

        <!-- Intro Card -->
        <div class="card mb-4 border-0 shadow-sm" style="border-radius:12px;background:#F8FAFC;border:1px solid #E2E8F0;">
            <div class="card-body p-3 d-flex align-items-center gap-3">
                <div style="width:42px;height:42px;border-radius:10px;background:#EDE9FE;display:flex;align-items:center;justify-content:center;color:#6D28D9;font-size:1.3rem;flex-shrink:0;">
                    <i class="bi bi-box-arrow-up-right"></i>
                </div>
                <div>
                    <strong style="color:var(--navy);font-size:0.95rem;">Pengaturan Tautan 3 Portal Utama SPMI</strong>
                    <div class="small text-muted">
                        Atur status ketersediaan masing-masing portal (<strong>Aktif &amp; Terbuka</strong> atau <strong>Coming Soon / Segera Hadir</strong>). Bila status diaktifkan, tautan URL wajib disertakan.
                    </div>
                </div>
            </div>
        </div>

        <form method="POST" id="formPortals">
            <div class="row g-4 mb-4">
                <?php foreach ($portals_def as $key => $p): 
                    $val = $portal_values[$key];
                    $is_active = ($val['status'] === 'active');
                ?>
                <div class="col-12">
                    <div class="card border-0 shadow-sm" style="border-radius:14px;overflow:hidden;border:1px solid #E2E8F0;">
                        <div class="card-header p-3 px-4 d-flex align-items-center justify-content-between" style="background:#F1F5F9;border-bottom:1px solid #E2E8F0;">
                            <div class="d-flex align-items-center gap-2">
                                <span class="badge" style="background:#0F172A;color:#FFD54F;font-size:0.72rem;letter-spacing:0.5px;padding:0.35rem 0.65rem;">
                                    <?= e($p['badge']) ?>
                                </span>
                                <h5 class="mb-0 fw-bold" style="color:var(--navy);font-size:1.05rem;">
                                    <?= e($p['name']) ?>
                                </h5>
                            </div>
                            <span class="badge status-pill-<?= $key ?>" style="background:<?= $is_active ? '#DCFCE7' : '#E0F2FE' ?>;color:<?= $is_active ? '#15803D' : '#0369A1' ?>;font-weight:700;font-size:0.75rem;padding:0.35rem 0.75rem;border-radius:20px;border:1px solid <?= $is_active ? '#86EFAC' : '#BAE6FD' ?>;">
                                <i class="bi <?= $is_active ? 'bi-check-circle-fill' : 'bi-hourglass-split' ?> me-1"></i>
                                <?= $is_active ? 'Status: Aktif' : 'Status: Coming Soon' ?>
                            </span>
                        </div>
                        <div class="card-body p-4">
                            <div class="row g-4 align-items-start">
                                <!-- Kolom Pengaturan Input -->
                                <div class="col-lg-7 col-md-12">
                                    <!-- Pilih Status -->
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
                                            <input type="url" class="form-control portal-url-input" id="<?= $key ?>_url" name="<?= $key ?>_url" placeholder="https://..." value="<?= e($val['url']) ?>" data-portal="<?= $key ?>">
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

                                <!-- Kolom Live Visual Preview -->
                                <div class="col-lg-5 col-md-12">
                                    <div class="small fw-bold text-muted mb-2">Pratinjau Tampilan Card Publik:</div>
                                    <div class="p-3 rounded-4 shadow-sm text-white" style="background:linear-gradient(145deg, #0A192F 0%, #132D54 100%);border:1px solid rgba(255,255,255,0.15);">
                                        <div class="d-flex align-items-center justify-content-between mb-2">
                                            <span class="badge" style="background:rgba(255,255,255,0.15);color:#FFD54F;font-size:0.68rem;padding:0.25rem 0.5rem;font-weight:700;">
                                                <?= e($p['badge']) ?>
                                            </span>
                                            <div style="width:28px;height:28px;border-radius:8px;background:rgba(255,255,255,0.1);display:flex;align-items:center;justify-content:center;color:#FFD54F;font-size:0.8rem;">
                                                <i class="bi <?= e($p['icon']) ?>"></i>
                                            </div>
                                        </div>
                                        <div class="fw-bold text-white mb-1" style="font-size:1.05rem;">
                                            <?= e($p['name']) ?>
                                        </div>
                                        <div class="text-white-50 small mb-3" style="font-size:0.75rem;line-height:1.45;">
                                            Contoh deskripsi penjelasan fungsi sistem informasi modul SPMI terkait.
                                        </div>

                                        <!-- Preview Bagian Aksi / Coming Soon -->
                                        <div class="pt-2 border-top" style="border-color:rgba(255,255,255,0.12) !important;">
                                            <!-- Preview State Aktif -->
                                            <div id="preview_active_<?= $key ?>" style="display:<?= $is_active ? 'block' : 'none' ?>;">
                                                <div class="btn w-100 py-2 fw-bold rounded-3 d-flex align-items-center justify-content-center gap-2 text-white" style="background:linear-gradient(135deg, #7B1FA2, #6A1B9A);border:none;font-size:0.84rem;box-shadow:0 4px 12px rgba(123,31,162,0.4);pointer-events:none;">
                                                    <span><?= e($p['btn_label']) ?></span>
                                                    <i class="bi bi-box-arrow-up-right" style="font-size:0.75rem;"></i>
                                                </div>
                                            </div>

                                            <!-- Preview State Coming Soon (Persis Gambar 2) -->
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
                    <i class="bi bi-check-circle-fill me-1"></i> Simpan Perubahan Portal
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Handle radio toggle perubahan status
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

    // Live update preview text
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

    // Form client-side validation
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
