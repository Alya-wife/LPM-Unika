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
    <!-- Custom LPM CSS -->
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">

    <?= isset($extra_css) ? $extra_css : '' ?>
</head>
<body>
