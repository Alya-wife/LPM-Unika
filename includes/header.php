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
