<?php
$current_admin = basename($_SERVER['PHP_SELF'], '.php');
$admin_name    = $_SESSION['admin_name'] ?? 'Admin';
$accred_nav_alerts = checkAccreditationExpirations();
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($admin_page_title) ? e($admin_page_title) . ' – Admin LPM UNIKA' : 'Admin LPM UNIKA' ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=<?= time() ?>">
    <style>
    /* Disable smooth scroll in admin panel to prevent jarring jump animations */
    html, body, .admin-sidebar, .admin-content {
        scroll-behavior: auto !important;
    }
    .admin-red-dot {
        width: 9px;
        height: 9px;
        background-color: #EF4444;
        border-radius: 50%;
        display: inline-block;
        margin-left: auto;
        box-shadow: 0 0 0 2px rgba(239, 68, 68, 0.4);
        animation: pulse-dot 1.5s infinite;
        flex-shrink: 0;
    }
    @keyframes pulse-dot {
        0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0.7); }
        70% { transform: scale(1.15); box-shadow: 0 0 0 6px rgba(239, 68, 68, 0); }
        100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(239, 68, 68, 0); }
    }
    .admin-nav-dropdown {
        margin-bottom: 0.35rem;
    }
    .admin-dropdown-toggle {
        width: 100%;
        background: none;
        border: none;
        padding: 0.65rem 0.9rem;
        color: rgba(255,255,255,0.7);
        display: flex;
        align-items: center;
        justify-content: space-between;
        border-radius: 8px;
        font-size: 0.84rem;
        font-weight: 600;
        transition: all 0.2s ease;
        cursor: pointer;
        text-align: left;
    }
    .admin-dropdown-toggle:hover {
        background: rgba(255,255,255,0.08);
        color: #fff;
    }
    .admin-dropdown-toggle.active {
        background: rgba(124, 58, 237, 0.28);
        color: #fff;
        border-left: 3px solid #A78BFA;
    }
    .admin-dropdown-toggle .nav-chevron {
        font-size: 0.75rem;
        transition: transform 0.25s ease;
        color: rgba(255,255,255,0.5);
    }
    .admin-nav-dropdown.open .admin-dropdown-toggle .nav-chevron {
        transform: rotate(180deg);
        color: #A78BFA;
    }
    .admin-dropdown-menu {
        padding: 0.3rem 0 0.4rem 1rem;
        margin-left: 0.85rem;
        border-left: 1.5px dashed rgba(255,255,255,0.18);
        display: none;
    }
    .admin-nav-dropdown.open .admin-dropdown-menu {
        display: block;
    }
    .admin-subnav-link {
        display: flex;
        align-items: center;
        justify-content: space-between;
        padding: 0.42rem 0.65rem;
        font-size: 0.8rem;
        color: rgba(255,255,255,0.65);
        text-decoration: none;
        border-radius: 6px;
        transition: all 0.15s ease;
        margin-bottom: 2px;
    }
    .admin-subnav-link:hover {
        color: #fff;
        background: rgba(255,255,255,0.06);
    }
    .admin-subnav-link.active {
        color: #EDE9FE;
        font-weight: 700;
        background: rgba(124, 58, 237, 0.35);
    }
    </style>
    <?= isset($extra_css) ? $extra_css : '' ?>
</head>
<body style="background:var(--bg-main);">

<!-- Sidebar -->
<aside class="admin-sidebar" id="adminSidebar">
    <script>
    // Immediate sidebar scroll restoration before paint to prevent jump to top
    try {
        var _savedScroll = sessionStorage.getItem('admin_sidebar_scroll');
        if (_savedScroll) {
            document.getElementById('adminSidebar').scrollTop = parseInt(_savedScroll, 10);
        }
    } catch(e) {}
    function toggleAdminNav(btn) {
        const parent = btn.closest('.admin-nav-dropdown');
        if (parent.classList.contains('open')) {
            parent.classList.remove('open');
        } else {
            parent.classList.add('open');
        }
    }
    </script>
    <div class="admin-sidebar-header">
        <div style="display:flex;align-items:center;gap:12px;">
            <div class="brand-logo-wrap" style="width:38px;height:38px;">
                <img src="<?= SITE_URL ?>/assets/images/logo-unika.png" alt="Logo UNIKA" class="brand-logo-img">
            </div>
            <div>
                <div style="font-family:var(--font-heading);font-size:0.9rem;font-weight:800;color:#fff;">LPM UNIKA</div>
                <div style="font-size:0.65rem;color:rgba(255,255,255,0.4);letter-spacing:0.3px;">Admin Panel</div>
            </div>
        </div>
    </div>

    <nav class="admin-sidebar-nav" style="padding:0.75rem 0.6rem;">
        <!-- 1. Dashboard Utama (Direct Link) -->
        <div style="margin-bottom:0.5rem;">
            <a href="<?= SITE_URL ?>/admin/dashboard.php" class="admin-nav-link <?= $current_admin === 'dashboard' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6A2.25 2.25 0 0 1 6 3.75h2.25A2.25 2.25 0 0 1 10.5 6v2.25a2.25 2.25 0 0 1-2.25 2.25H6a2.25 2.25 0 0 1-2.25-2.25V6ZM3.75 15.75A2.25 2.25 0 0 1 6 13.5h2.25a2.25 2.25 0 0 1 2.25 2.25V18a2.25 2.25 0 0 1-2.25 2.25H6A2.25 2.25 0 0 1 3.75 18v-2.25ZM13.5 6a2.25 2.25 0 0 1 2.25-2.25H18A2.25 2.25 0 0 1 20.25 6v2.25A2.25 2.25 0 0 1 18 10.5h-2.25a2.25 2.25 0 0 1-2.25-2.25V6ZM13.5 15.75a2.25 2.25 0 0 1 2.25-2.25H18a2.25 2.25 0 0 1 2.25 2.25V18A2.25 2.25 0 0 1 18 20.25h-2.25A2.25 2.25 0 0 1 13.5 18v-2.25Z" />
                </svg>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- 2. Halaman Beranda (Dropdown) -->
        <?php $grp_beranda = in_array($current_admin, ['slider-list','slider-form','penghargaan-list','penghargaan-form']); ?>
        <div class="admin-nav-dropdown <?= $grp_beranda ? 'open' : '' ?>">
            <button type="button" class="admin-dropdown-toggle <?= $grp_beranda ? 'active' : '' ?>" onclick="toggleAdminNav(this)">
                <span class="d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m2.25 12 8.954-8.955c.44-.439 1.152-.439 1.591 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75M8.25 21h8.25" />
                    </svg>
                    <span>Halaman Beranda</span>
                </span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <div class="admin-dropdown-menu">
                <a href="<?= SITE_URL ?>/admin/slider-list.php" class="admin-subnav-link <?= in_array($current_admin, ['slider-list','slider-form']) ? 'active' : '' ?>">
                    <span>• Hero Slider Banner</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/penghargaan-list.php" class="admin-subnav-link <?= in_array($current_admin, ['penghargaan-list','penghargaan-form']) ? 'active' : '' ?>">
                    <span>• Capaian &amp; Penghargaan</span>
                </a>
            </div>
        </div>

        <!-- 3. Halaman Profil (Dropdown) -->
        <?php $grp_profil = in_array($current_admin, ['profil-edit','tim-list','tim-form']); ?>
        <div class="admin-nav-dropdown <?= $grp_profil ? 'open' : '' ?>">
            <button type="button" class="admin-dropdown-toggle <?= $grp_profil ? 'active' : '' ?>" onclick="toggleAdminNav(this)">
                <span class="d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 6.042A8.967 8.967 0 0 0 6 3.75c-1.052 0-2.062.18-3 .512v14.25A8.987 8.987 0 0 1 6 18c2.305 0 4.408.867 6 2.292m0-14.25a8.966 8.966 0 0 1 6-2.292c1.052 0 2.062.18 3 .512v14.25A8.987 8.987 0 0 0 18 18a8.967 8.967 0 0 0-6 2.292m0-14.25v14.25" />
                    </svg>
                    <span>Halaman Profil</span>
                </span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <div class="admin-dropdown-menu">
                <a href="<?= SITE_URL ?>/admin/profil-edit.php" class="admin-subnav-link <?= $current_admin === 'profil-edit' ? 'active' : '' ?>">
                    <span>• Tentang, Visi &amp; Misi</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/tim-list.php" class="admin-subnav-link <?= in_array($current_admin, ['tim-list','tim-form']) ? 'active' : '' ?>">
                    <span>• Struktur Tim LPM</span>
                </a>
            </div>
        </div>

        <!-- 4. Halaman Akreditasi (Dropdown) -->
        <?php $grp_akred = in_array($current_admin, ['akreditasi-institusi','akreditasi-status-nasional','lembaga-akreditasi-list','lembaga-akreditasi-form','akreditasi-prodi-list','akreditasi-prodi-form','pemeringkatan-setting']); ?>
        <div class="admin-nav-dropdown <?= $grp_akred ? 'open' : '' ?>">
            <button type="button" class="admin-dropdown-toggle <?= $grp_akred ? 'active' : '' ?>" onclick="toggleAdminNav(this)">
                <span class="d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .982-3.172M12 3a4.5 4.5 0 0 0-4.5 4.5v1.875a3.375 3.375 0 0 0 3.375 3.375h2.25a3.375 3.375 0 0 0 3.375-3.375V7.5A4.5 4.5 0 0 0 12 3Z" />
                    </svg>
                    <span>Halaman Akreditasi</span>
                </span>
                <?php if ($accred_nav_alerts['has_institusi_alert'] || $accred_nav_alerts['has_lembaga_alert'] || $accred_nav_alerts['has_prodi_alert']): ?>
                    <span class="admin-red-dot" style="margin-right:6px;"></span>
                <?php endif; ?>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <div class="admin-dropdown-menu">
                <a href="<?= SITE_URL ?>/admin/akreditasi-institusi.php" class="admin-subnav-link <?= $current_admin === 'akreditasi-institusi' ? 'active' : '' ?>">
                    <span>• Akreditasi Institusi</span>
                    <?php if ($accred_nav_alerts['has_institusi_alert']): ?>
                        <span class="admin-red-dot" title="Masa berlaku segera habis!"></span>
                    <?php endif; ?>
                </a>
                <a href="<?= SITE_URL ?>/admin/akreditasi-status-nasional.php" class="admin-subnav-link <?= $current_admin === 'akreditasi-status-nasional' ? 'active' : '' ?>">
                    <span>• Status Akreditasi Nasional</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/akreditasi-prodi-list.php" class="admin-subnav-link <?= in_array($current_admin, ['akreditasi-prodi-list','akreditasi-prodi-form']) ? 'active' : '' ?>">
                    <span>• Data Program Studi</span>
                    <?php if ($accred_nav_alerts['has_prodi_alert']): ?>
                        <span class="admin-red-dot" title="<?= $accred_nav_alerts['prodi_count'] ?> prodi segera habis!"></span>
                    <?php endif; ?>
                </a>
                <a href="<?= SITE_URL ?>/admin/pemeringkatan-setting.php" class="admin-subnav-link <?= $current_admin === 'pemeringkatan-setting' ? 'active' : '' ?>">
                    <span>• Pemeringkatan Kampus</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/lembaga-akreditasi-list.php" class="admin-subnav-link <?= in_array($current_admin, ['lembaga-akreditasi-list','lembaga-akreditasi-form']) ? 'active' : '' ?>">
                    <span>• Lembaga Akreditasi (LAM)</span>
                    <?php if ($accred_nav_alerts['has_lembaga_alert']): ?>
                        <span class="admin-red-dot" title="Ada sertifikat LAM segera habis!"></span>
                    <?php endif; ?>
                </a>
            </div>
        </div>

        <!-- 5. Halaman SPMI (Dropdown) -->
        <?php $grp_spmi = in_array($current_admin, ['dokumen-list','dokumen-form','kategori-dokumen','spmi-kemendikti-list','spmi-kemendikti-form','spmi-portal-setting']); ?>
        <div class="admin-nav-dropdown <?= $grp_spmi ? 'open' : '' ?>">
            <button type="button" class="admin-dropdown-toggle <?= $grp_spmi ? 'active' : '' ?>" onclick="toggleAdminNav(this)">
                <span class="d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                    </svg>
                    <span>Halaman SPMI</span>
                </span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <div class="admin-dropdown-menu">
                <a href="<?= SITE_URL ?>/admin/dokumen-list.php" class="admin-subnav-link <?= in_array($current_admin, ['dokumen-list','dokumen-form']) ? 'active' : '' ?>">
                    <span>• Dokumen &amp; Regulasi</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/kategori-dokumen.php" class="admin-subnav-link <?= $current_admin === 'kategori-dokumen' ? 'active' : '' ?>">
                    <span>• Kategori Dokumen</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/spmi-kemendikti-list.php" class="admin-subnav-link <?= in_array($current_admin, ['spmi-kemendikti-list','spmi-kemendikti-form']) ? 'active' : '' ?>">
                    <span>• Hasil SPMI Kemendikti</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/spmi-portal-setting.php" class="admin-subnav-link <?= $current_admin === 'spmi-portal-setting' ? 'active' : '' ?>">
                    <span>• Tautan Portal SPMI</span>
                </a>
            </div>
        </div>

        <!-- 6. Halaman FAQ & Edukasi (Dropdown) -->
        <?php $grp_faq = in_array($current_admin, ['faq-setting','glosarium-setting','kalender-list','kalender-form','buletin-list','buletin-form']); ?>
        <div class="admin-nav-dropdown <?= $grp_faq ? 'open' : '' ?>">
            <button type="button" class="admin-dropdown-toggle <?= $grp_faq ? 'active' : '' ?>" onclick="toggleAdminNav(this)">
                <span class="d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 5.25h.008v.008H12v-.008Z" />
                    </svg>
                    <span>Halaman FAQ</span>
                </span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <div class="admin-dropdown-menu">
                <a href="<?= SITE_URL ?>/admin/faq-setting.php" class="admin-subnav-link <?= $current_admin === 'faq-setting' ? 'active' : '' ?>">
                    <span>• Tanya Jawab (FAQ)</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/glosarium-setting.php" class="admin-subnav-link <?= $current_admin === 'glosarium-setting' ? 'active' : '' ?>">
                    <span>• Glosarium Mutu</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/kalender-list.php" class="admin-subnav-link <?= in_array($current_admin, ['kalender-list','kalender-form']) ? 'active' : '' ?>">
                    <span>• Kalender Mutu</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/buletin-list.php" class="admin-subnav-link <?= in_array($current_admin, ['buletin-list','buletin-form']) ? 'active' : '' ?>">
                    <span>• Buletin JAMUS</span>
                </a>
            </div>
        </div>

        <!-- 6. Halaman AMI (Dropdown) -->
        <?php $grp_ami = in_array($current_admin, ['ami-pengantar','kalender-ami-list','kalender-ami-form','ami-siklus-list','ami-siklus1-form','ami-siklus4-dok-form','ami-siklus4-form','ami-siklus5-form','ami-periode']); ?>
        <div class="admin-nav-dropdown <?= $grp_ami ? 'open' : '' ?>">
            <button type="button" class="admin-dropdown-toggle <?= $grp_ami ? 'active' : '' ?>" onclick="toggleAdminNav(this)">
                <span class="d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                    </svg>
                    <span>Halaman AMI</span>
                </span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <div class="admin-dropdown-menu">
                <a href="<?= SITE_URL ?>/admin/ami-pengantar.php" class="admin-subnav-link <?= $current_admin === 'ami-pengantar' ? 'active' : '' ?>">
                    <span>• Kelola Pengantar AMI</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/ami-siklus-list.php?tab=isian" class="admin-subnav-link <?= ($current_admin === 'ami-siklus-list' && ($_GET['tab'] ?? 'isian') === 'isian') ? 'active' : '' ?>">
                    <span>• Isian 5 Tahapan Siklus</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/ami-siklus-list.php?tab=siklus1" class="admin-subnav-link <?= ($current_admin === 'ami-siklus-list' && ($_GET['tab'] ?? '') !== 'isian') || in_array($current_admin, ['ami-siklus1-form','ami-siklus4-dok-form','ami-siklus4-form','ami-siklus5-form']) ? 'active' : '' ?>">
                    <span>• Dokumen &amp; Data Siklus (1-5)</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/kalender-ami-list.php" class="admin-subnav-link <?= in_array($current_admin, ['kalender-ami-list','kalender-ami-form']) ? 'active' : '' ?>">
                    <span>• Jadwal Pelaksanaan AMI</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/ami-periode.php" class="admin-subnav-link <?= $current_admin === 'ami-periode' ? 'active' : '' ?>">
                    <span>• Master Periode AMI</span>
                </a>
            </div>
        </div>

        <!-- 7. Halaman Berita & Kegiatan (Direct Link) -->
        <div style="margin-bottom:0.35rem;">
            <a href="<?= SITE_URL ?>/admin/berita-list.php" class="admin-nav-link <?= in_array($current_admin, ['berita-list','berita-form']) ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
                </svg>
                <span>Halaman Berita &amp; Kegiatan</span>
            </a>
        </div>

        <!-- 8. Halaman Layanan (3 Sub-Menu Selaras Publik) -->
        <?php
        $unread_feedback  = 0;
        $unread_kunjungan = 0;
        try {
            $db_inst = getDB();
            $unread_feedback  = (int)$db_inst->query("SELECT COUNT(*) FROM feedback WHERE status = 'Belum Dibaca' AND is_archived = 0")->fetchColumn();
            $unread_kunjungan = (int)$db_inst->query("SELECT COUNT(*) FROM permohonan_kunjungan WHERE status = 'Belum Dibaca' AND is_archived = 0")->fetchColumn();
        } catch (Exception $e) {}
        $grp_layanan = in_array($current_admin, ['kunjungan-list','feedback-kunjungan-list','feedback-kunjungan-detail','feedback-list','kunjungan-unit','feedback-kunjungan-pertanyaan','layanan-form-setting']);
        ?>
        <div class="admin-nav-dropdown <?= $grp_layanan ? 'open' : '' ?>">
            <button type="button" class="admin-dropdown-toggle <?= $grp_layanan ? 'active' : '' ?>" onclick="toggleAdminNav(this)">
                <span class="d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
                    </svg>
                    <span>Menu Layanan</span>
                </span>
                <?php if ($unread_kunjungan > 0 || $unread_feedback > 0): ?>
                    <span class="badge bg-danger ms-auto me-2" style="font-size:0.65rem;padding:0.2rem 0.45rem;"><?= ($unread_kunjungan + $unread_feedback) ?></span>
                <?php endif; ?>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <div class="admin-dropdown-menu">
                <!-- Sub Menu 1: Info Pelatihan -->
                <div style="padding:0.45rem 1rem 0.15rem;font-size:0.68rem;text-transform:uppercase;letter-spacing:0.8px;color:#94a3b8;font-weight:700;">
                    1. Info Pelatihan
                </div>
                <a href="<?= SITE_URL ?>/admin/layanan-form-setting.php?tab=pelatihan-umum" class="admin-subnav-link <?= ($current_admin === 'layanan-form-setting' && in_array(($_GET['tab'] ?? ''), ['pelatihan-umum', 'brosur', ''])) ? 'active' : '' ?>">
                    <span>• Narasi &amp; Brosur Pelatihan</span>
                </a>

                <!-- Sub Menu 2: Kunjungan Studi Banding -->
                <div style="padding:0.6rem 1rem 0.15rem;font-size:0.68rem;text-transform:uppercase;letter-spacing:0.8px;color:#94a3b8;font-weight:700;">
                    2. Kunjungan Studi Banding
                </div>
                <a href="<?= SITE_URL ?>/admin/kunjungan-list.php" class="admin-subnav-link <?= $current_admin === 'kunjungan-list' ? 'active' : '' ?>">
                    <span>• Permohonan Masuk</span>
                    <?php if ($unread_kunjungan > 0): ?>
                        <span class="badge bg-warning text-dark ms-auto" style="font-size:0.65rem;"><?= $unread_kunjungan ?></span>
                    <?php endif; ?>
                </a>
                <a href="<?= SITE_URL ?>/admin/layanan-form-setting.php?tab=kunjungan" class="admin-subnav-link <?= ($current_admin === 'layanan-form-setting' && ($_GET['tab'] ?? '') === 'kunjungan') ? 'active' : '' ?>">
                    <span>• Pengaturan Form Kunjungan</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/kunjungan-unit.php" class="admin-subnav-link <?= $current_admin === 'kunjungan-unit' ? 'active' : '' ?>">
                    <span>• Pengaturan Tujuan Unit</span>
                </a>

                <!-- Sub Menu 3: Survey Kepuasan Layanan LPM -->
                <div style="padding:0.6rem 1rem 0.15rem;font-size:0.68rem;text-transform:uppercase;letter-spacing:0.8px;color:#94a3b8;font-weight:700;">
                    3. Survey Kepuasan Layanan
                </div>
                <a href="<?= SITE_URL ?>/admin/feedback-kunjungan-list.php" class="admin-subnav-link <?= in_array($current_admin, ['feedback-kunjungan-list', 'feedback-kunjungan-detail']) ? 'active' : '' ?>">
                    <span>• Rekap Respon Survei</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/feedback-kunjungan-pertanyaan.php" class="admin-subnav-link <?= $current_admin === 'feedback-kunjungan-pertanyaan' ? 'active' : '' ?>">
                    <span>• Kelola 8 Butir Kuesioner</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/layanan-form-setting.php?tab=feedback" class="admin-subnav-link <?= ($current_admin === 'layanan-form-setting' && ($_GET['tab'] ?? '') === 'feedback') ? 'active' : '' ?>">
                    <span>• Pengaturan Form Survei</span>
                </a>
            </div>
        </div>

        <!-- 9. Visual Page Builder (Elementor Style) -->
        <div style="margin-bottom:0.35rem;">
            <a href="<?= SITE_URL ?>/admin/advance-setting.php" class="admin-nav-link <?= $current_admin === 'advance-setting' ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.53 16.122a3 3 0 0 0-5.78 1.128 2.25 2.25 0 0 1-2.4 2.245 4.5 4.5 0 0 0 8.4-2.245c0-.399-.078-.78-.22-1.128Zm0 0a15.998 15.998 0 0 0 3.388-1.62m-5.043-.025a15.994 15.994 0 0 1 1.622-3.395m3.42 3.42a15.995 15.995 0 0 0 4.764-4.648l3.876-5.814a1.151 1.151 0 0 0-1.597-1.597L14.146 6.32a15.996 15.996 0 0 0-4.649 4.763m3.42 3.42a6.776 6.776 0 0 0-3.42-3.42" />
                </svg>
                <span>Visual Page Builder</span>
            </a>
        </div>

        <!-- 10. Kelola Halaman (Pages) -->
        <div style="margin-bottom:0.35rem;">
            <a href="<?= SITE_URL ?>/admin/page-list.php" class="admin-nav-link <?= in_array($current_admin, ['page-list','page-form']) ? 'active' : '' ?>">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <span>Daftar Halaman Website</span>
            </a>
        </div>

        <!-- 10. Pengaturan Sistem (Dropdown) -->
        <?php $grp_pengaturan = in_array($current_admin, ['pengaturan', 'convert-images']); ?>
        <div class="admin-nav-dropdown <?= $grp_pengaturan ? 'open' : '' ?>">
            <button type="button" class="admin-dropdown-toggle <?= $grp_pengaturan ? 'active' : '' ?>" onclick="toggleAdminNav(this)">
                <span class="d-flex align-items-center gap-2">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="18" height="18">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    <span>Pengaturan Sistem</span>
                </span>
                <i class="bi bi-chevron-down nav-chevron"></i>
            </button>
            <div class="admin-dropdown-menu">
                <a href="<?= SITE_URL ?>/admin/pengaturan.php" class="admin-subnav-link <?= $current_admin === 'pengaturan' ? 'active' : '' ?>">
                    <span>• Identitas &amp; Kontak</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/convert-images.php" class="admin-subnav-link <?= $current_admin === 'convert-images' ? 'active' : '' ?>">
                    <span>• Konversi WebP HD</span>
                </a>
                <a href="<?= SITE_URL ?>/" target="_blank" class="admin-subnav-link">
                    <span>• Buka Website Live</span>
                </a>
                <a href="<?= SITE_URL ?>/admin/logout.php" class="admin-subnav-link" style="color:#FCA5A5 !important;" onclick="return confirm('Yakin ingin keluar?')">
                    <span>• Keluar Akun</span>
                </a>
            </div>
        </div>
    </nav>

    <!-- Admin User Info -->
    <div style="padding:1rem 1.5rem;border-top:1px solid rgba(255,255,255,0.08);flex-shrink:0;">
        <div style="display:flex;align-items:center;gap:10px;">
            <div style="width:36px;height:36px;background:var(--purple-glow);border-radius:50%;display:flex;align-items:center;justify-content:center;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="rgba(255,255,255,0.7)" width="18" height="18">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                </svg>
            </div>
            <div>
                <div style="font-family:var(--font-heading);font-size:0.8rem;font-weight:600;color:rgba(255,255,255,0.85);"><?= e($admin_name) ?></div>
                <div style="font-size:0.68rem;color:rgba(255,255,255,0.35);">Administrator</div>
            </div>
        </div>
    </div>
</aside>

<!-- Main Content Area -->
<div class="admin-content">
    <!-- Top Bar -->
    <div class="admin-topbar">
        <h1 class="admin-topbar-title"><?= isset($admin_page_title) ? e($admin_page_title) : 'Dashboard' ?></h1>
        <div style="display:flex;align-items:center;gap:0.75rem;">
            <!-- Notification Icon Dropdown for Accreditation Warnings -->
            <div class="dropdown">
                <button type="button" class="btn p-0 d-flex align-items-center justify-content-center position-relative" id="dropdownNotificationBtn" data-bs-toggle="dropdown" aria-expanded="false" style="width:38px;height:38px;border-radius:10px;background:#ffffff;border:1px solid #E2E8F0;color:var(--navy);box-shadow:0 1px 3px rgba(0,0,0,0.05);transition:all 0.15s ease;" title="Pemberitahuan Masa Berlaku Akreditasi">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="20" height="20">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                    </svg>
                    <?php if (!empty($accred_nav_alerts['total']) && $accred_nav_alerts['total'] > 0): ?>
                        <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="font-size:0.65rem;padding:0.25rem 0.45rem;border:2px solid #fff;">
                            <?= $accred_nav_alerts['total'] ?>
                        </span>
                    <?php endif; ?>
                </button>
                <div class="dropdown-menu dropdown-menu-end shadow-lg border-0 p-0 rounded-4 mt-2" aria-labelledby="dropdownNotificationBtn" style="width:380px;max-width:92vw;box-shadow:0 12px 35px rgba(0,0,0,0.18) !important;overflow:hidden;">
                    <div style="background:linear-gradient(135deg, var(--navy), #1E3A8A);color:#fff;padding:0.9rem 1.25rem;display:flex;align-items:center;justify-content:space-between;">
                        <div class="d-flex align-items-center gap-2">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="#FFD54F" width="18" height="18">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                            </svg>
                            <span style="font-weight:700;font-size:0.875rem;">Masa Berlaku Akreditasi</span>
                        </div>
                        <span class="badge <?= ($accred_nav_alerts['total'] ?? 0) > 0 ? 'bg-danger' : 'bg-success' ?> rounded-pill" style="font-size:0.72rem;padding:0.3rem 0.6rem;">
                            <?= ($accred_nav_alerts['total'] ?? 0) ?> Peringatan
                        </span>
                    </div>

                    <div style="max-height:360px;overflow-y:auto;padding:0.25rem 0;">
                        <?php if (empty($accred_nav_alerts['total']) || $accred_nav_alerts['total'] === 0): ?>
                            <div class="text-center py-4 text-muted small">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="#16A34A" width="32" height="32" class="d-block mx-auto mb-2">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                </svg>
                                Seluruh status akreditasi masih dalam masa berlaku aman.
                            </div>
                        <?php else: ?>
                            <?php foreach ($accred_nav_alerts['expired'] as $notif_item): ?>
                                <a href="<?= $notif_item['link'] ?>" class="dropdown-item p-3 border-bottom text-wrap d-block" style="background:#FFF5F5;text-decoration:none;">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="badge bg-danger" style="font-size:0.68rem;padding:0.2rem 0.5rem;">KADALUARSA</span>
                                        <span style="font-size:0.72rem;color:#DC2626;font-weight:700;"><?= formatTanggal($notif_item['tanggal']) ?></span>
                                    </div>
                                    <div style="font-size:0.83rem;font-weight:700;color:#0F172A;line-height:1.35;margin-bottom:0.25rem;">
                                        <?= e($notif_item['nama']) ?>
                                    </div>
                                    <div style="font-size:0.74rem;color:#64748B;display:flex;align-items:center;gap:4px;">
                                        Perbarui data sekarang &rarr;
                                    </div>
                                </a>
                            <?php endforeach; ?>

                            <?php foreach ($accred_nav_alerts['warning'] as $notif_item): ?>
                                <a href="<?= $notif_item['link'] ?>" class="dropdown-item p-3 border-bottom text-wrap d-block" style="background:#FFFBEB;text-decoration:none;">
                                    <div class="d-flex align-items-center justify-content-between mb-1">
                                        <span class="badge" style="background:#F59E0B;color:#fff;font-size:0.68rem;padding:0.2rem 0.5rem;">SISA <?= $notif_item['days'] ?> HARI</span>
                                        <span style="font-size:0.72rem;color:#B45309;font-weight:700;">s.d. <?= formatTanggal($notif_item['tanggal']) ?></span>
                                    </div>
                                    <div style="font-size:0.83rem;font-weight:700;color:#0F172A;line-height:1.35;margin-bottom:0.25rem;">
                                        <?= e($notif_item['nama']) ?>
                                    </div>
                                    <div style="font-size:0.74rem;color:#64748B;display:flex;align-items:center;gap:4px;">
                                        Persiapkan re-akreditasi &rarr;
                                    </div>
                                </a>
                            <?php endforeach; ?>
                            <?php unset($notif_item); ?>
                        <?php endif; ?>
                    </div>

                    <div style="padding:0.75rem 1.25rem;background:#F8FAFC;border-top:1px solid #E2E8F0;text-align:center;">
                        <a href="<?= SITE_URL ?>/admin/akreditasi-prodi-list.php" style="font-size:0.8rem;font-weight:700;color:var(--navy);text-decoration:none;display:inline-flex;align-items:center;gap:4px;">
                            Buka Daftar Akreditasi Prodi &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <span style="font-size:0.82rem;color:var(--text-muted);"><?= date('d F Y') ?></span>
            <a href="<?= SITE_URL ?>/admin/logout.php" class="btn-action btn-delete" onclick="return confirm('Yakin ingin keluar?')">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15m3 0 3-3m0 0-3-3m3 3H9" />
                </svg>
                Keluar
            </a>
        </div>
    </div>

    <div class="admin-main">
