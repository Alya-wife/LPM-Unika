<?php recordVisitorLog(); ?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?= isset($meta_desc) ? e($meta_desc) : 'Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata - Mewujudkan mutu pendidikan tinggi yang unggul dan berkelanjutan.' ?>">
    <meta name="author" content="LPM UNIKA">
    <meta property="og:title" content="<?= isset($page_title) ? e($page_title) . ' | LPM UNIKA' : 'LPM UNIKA - Lembaga Penjaminan Mutu' ?>">
    <meta property="og:type" content="website">
    <meta property="og:image" content="<?= SITE_URL ?>/assets/images/og-lpm.png">

    <title><?= isset($page_title) ? e($page_title) . ' | LPM UNIKA' : 'LPM UNIKA - Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata' ?></title>

    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons CSS -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom LPM CSS -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css?v=<?= filemtime(__DIR__ . '/../assets/css/style.css') ?>">

    <?php
    $theme_primary = getPengaturan('theme_primary_color', '#0A192F');
    $theme_accent  = getPengaturan('theme_accent_color', '#6A1B9A');
    $theme_gold    = getPengaturan('theme_gold_color', '#F59E0B');
    $theme_bg      = getPengaturan('theme_bg_main', '#F8F9FA');
    $theme_nav     = getPengaturan('theme_navbar_style', 'default');
    $theme_font    = getPengaturan('theme_font_heading', 'Plus Jakarta Sans');

    // Load additional Google Font if not default
    if (in_array($theme_font, ['Poppins', 'Outfit', 'Montserrat'])) {
        $font_param = str_replace(' ', '+', $theme_font) . ':wght@400;500;600;700;800';
        echo '<link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=' . $font_param . '&display=swap">' . "\n";
    }
    ?>
    <style id="lpm-dynamic-theme-vars">
    :root {
        --navy: <?= e($theme_primary) ?>;
        --purple: <?= e($theme_accent) ?>;
        --purple-dark: <?= e($theme_accent) ?>;
        --gold: <?= e($theme_gold) ?>;
        --bg-main: <?= e($theme_bg) ?>;
        <?php if ($theme_font !== 'Plus Jakarta Sans'): ?>
        --font-heading: '<?= e($theme_font) ?>', sans-serif;
        <?php endif; ?>
    }
    <?php if ($theme_nav === 'colored'): ?>
    #main-navbar {
        background: <?= e($theme_primary) ?> !important;
        border-bottom: 1px solid rgba(255,255,255,0.12) !important;
    }
    #main-navbar .brand-title, #main-navbar .brand-subtitle, #main-navbar .nav-link {
        color: #ffffff !important;
    }
    #main-navbar .nav-link:hover, #main-navbar .nav-link.active {
        color: <?= e($theme_gold) ?> !important;
    }
    #main-navbar .navbar-toggler-icon {
        filter: invert(1);
    }
    <?php elseif ($theme_nav === 'dark'): ?>
    #main-navbar {
        background: #0F172A !important;
        border-bottom: 1px solid rgba(255,255,255,0.12) !important;
    }
    #main-navbar .brand-title, #main-navbar .brand-subtitle, #main-navbar .nav-link {
        color: #ffffff !important;
    }
    #main-navbar .nav-link:hover, #main-navbar .nav-link.active {
        color: <?= e($theme_gold) ?> !important;
    }
    #main-navbar .navbar-toggler-icon {
        filter: invert(1);
    }
    <?php endif; ?>
    </style>

    <?= isset($extra_css) ? $extra_css : '' ?>
</head>
<body>
<?php
// Guard Akses Halaman: Jika berstatus Draf dan diakses non-admin, tampilkan layar peninjauan & blokir akses
if (function_exists('findPageRecord')) {
    $scriptName = basename($_SERVER['SCRIPT_NAME'] ?? '');
    $isAdminDir = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/admin/') !== false);
    $isApiDir   = (strpos($_SERVER['SCRIPT_NAME'] ?? '', '/api/') !== false);
    
    if (!$isAdminDir && !$isApiDir && !in_array($scriptName, ['page.php', 'auth-google.php'])) {
        $pageRec = findPageRecord($scriptName);
        if ($pageRec) {
            $isDraft = ($pageRec['status'] ?? 'publish') === 'draft';
            $isAdmin = !empty($_SESSION['admin_id']);
            
            if ($isDraft) {
                if (!$isAdmin) {
                    // Blokir pengunjung umum, tampilkan layar peninjauan draf
                    require_once __DIR__ . '/navbar.php';
                    ?>
                    <div class="page-banner">
                        <div class="container position-relative">
                            <h1 class="page-banner-title">Halaman Dalam Peninjauan</h1>
                            <div class="breadcrumb-lpm">
                                <a href="<?= SITE_URL ?>/">Beranda</a>
                                <span>/</span>
                                <span class="current">Draft</span>
                            </div>
                        </div>
                    </div>
                    <section class="py-5 text-center">
                        <div class="container">
                            <div style="max-width:550px;margin:3rem auto;padding:2.5rem;background:#fff;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);border:1px solid var(--border);">
                                <div style="width:70px;height:70px;border-radius:50%;background:#FEF3C7;display:flex;align-items:center;justify-content:center;margin:0 auto 1.5rem;">
                                    <i class="bi bi-clock-history text-warning fs-1"></i>
                                </div>
                                <h3 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:0.75rem;">Halaman Belum Diterbitkan</h3>
                                <p style="font-size:0.9rem;color:var(--text-muted);line-height:1.7;margin-bottom:1.5rem;">
                                    Halaman <strong><?= e($pageRec['judul']) ?></strong> saat ini masih berstatus <strong>Draf (Disembunyikan)</strong> dan sedang dalam tahap penyusunan oleh Administrator LPM UNIKA.
                                </p>
                                <a href="<?= SITE_URL ?>/" class="btn-hero-primary" style="display:inline-flex;padding:0.65rem 1.8rem;">
                                    Kembali ke Beranda
                                </a>
                            </div>
                        </div>
                    </section>
                    <?php
                    require_once __DIR__ . '/footer.php';
                    exit;
                } else {
                    // Admin yang sedang login: Izinkan pratinjau dengan banner indikator
                    $GLOBALS['lpm_admin_draft_preview'] = $pageRec;
                }
            }
        }
    }
}
?>
