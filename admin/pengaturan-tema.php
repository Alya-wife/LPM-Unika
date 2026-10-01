<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kustomisasi Tema & Warna Website';
$db = getDB();

$flash = '';
$error = '';

// Handle Reset Default
if (isset($_POST['action']) && $_POST['action'] === 'reset_default') {
    setPengaturan('theme_preset', 'unika_default');
    setPengaturan('theme_primary_color', '#0A192F');
    setPengaturan('theme_accent_color', '#6A1B9A');
    setPengaturan('theme_gold_color', '#F59E0B');
    setPengaturan('theme_bg_main', '#F8F9FA');
    setPengaturan('theme_navbar_style', 'default');
    setPengaturan('theme_font_heading', 'Plus Jakarta Sans');

    $_SESSION['flash'] = 'Tema dan warna berhasil dikembalikan ke pengaturan standar (Default UNIKA).';
    redirect(SITE_URL . '/admin/pengaturan-tema.php');
}

// Handle Save Custom Theme
if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] === 'save_theme')) {
    $theme_preset        = trim($_POST['theme_preset'] ?? 'custom');
    $theme_primary_color = trim($_POST['theme_primary_color'] ?? '#0A192F');
    $theme_accent_color  = trim($_POST['theme_accent_color'] ?? '#6A1B9A');
    $theme_gold_color    = trim($_POST['theme_gold_color'] ?? '#F59E0B');
    $theme_bg_main       = trim($_POST['theme_bg_main'] ?? '#F8F9FA');
    $theme_navbar_style  = trim($_POST['theme_navbar_style'] ?? 'default');
    $theme_font_heading  = trim($_POST['theme_font_heading'] ?? 'Plus Jakarta Sans');

    // Validasi format hex warna dasar
    $validateHex = function($color, $fallback) {
        $color = trim($color);
        return preg_match('/^#[a-f0-9]{6}$/i', $color) ? $color : $fallback;
    };

    $theme_primary_color = $validateHex($theme_primary_color, '#0A192F');
    $theme_accent_color  = $validateHex($theme_accent_color, '#6A1B9A');
    $theme_gold_color    = $validateHex($theme_gold_color, '#F59E0B');
    $theme_bg_main       = $validateHex($theme_bg_main, '#F8F9FA');

    setPengaturan('theme_preset', $theme_preset);
    setPengaturan('theme_primary_color', $theme_primary_color);
    setPengaturan('theme_accent_color', $theme_accent_color);
    setPengaturan('theme_gold_color', $theme_gold_color);
    setPengaturan('theme_bg_main', $theme_bg_main);
    setPengaturan('theme_navbar_style', $theme_navbar_style);
    setPengaturan('theme_font_heading', $theme_font_heading);

    $_SESSION['flash'] = 'Pengaturan tema dan warna website berhasil disimpan. Seluruh halaman kini menggunakan skema warna ini!';
    redirect(SITE_URL . '/admin/pengaturan-tema.php');
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

$theme_preset        = getPengaturan('theme_preset', 'unika_default');
$theme_primary_color = getPengaturan('theme_primary_color', '#0A192F');
$theme_accent_color  = getPengaturan('theme_accent_color', '#6A1B9A');
$theme_gold_color    = getPengaturan('theme_gold_color', '#F59E0B');
$theme_bg_main       = getPengaturan('theme_bg_main', '#F8F9FA');
$theme_navbar_style  = getPengaturan('theme_navbar_style', 'default');
$theme_font_heading  = getPengaturan('theme_font_heading', 'Plus Jakarta Sans');

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-11">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <span style="color:var(--navy);">Tema &amp; Warna Website</span>
        </div>

        <?php if ($flash): ?>
        <div class="alert-lpm alert-success mb-4 d-flex align-items-center gap-2">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
            </svg>
            <div><?= e($flash) ?></div>
        </div>
        <?php endif; ?>

        <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
            <div>
                <h2 style="font-size:1.35rem;font-weight:800;color:var(--navy);margin:0;">Theme &amp; Color Customizer</h2>
                <p style="font-size:0.875rem;color:var(--text-muted);margin:0.25rem 0 0;">
                    Ubah nuansa warna utama, aksen tombol, gaya navbar, dan tipografi seluruh website seperti di WordPress Customizer.
                </p>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= SITE_URL ?>/" target="_blank" class="btn btn-outline-secondary btn-sm d-inline-flex align-items-center gap-1" style="border-radius:6px;font-weight:600;">
                    <i class="bi bi-box-arrow-up-right"></i> Buka Website Live
                </a>
            </div>
        </div>

        <form method="POST" id="themeForm">
            <input type="hidden" name="action" value="save_theme">
            <input type="hidden" name="theme_preset" id="themePresetInput" value="<?= e($theme_preset) ?>">

            <div class="row g-4">
                <!-- Kolom Kiri: Kontrol Pengaturan Warna -->
                <div class="col-lg-7">
                    <!-- 1. Preset Tema Cepat -->
                    <div class="card-lpm mb-4" style="background:#fff;border:1px solid var(--border);border-radius:14px;padding:1.5rem;box-shadow:var(--shadow-sm);">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <h5 style="font-weight:700;color:var(--navy);margin:0;font-size:1.05rem;">
                                🎨 1. Preset Warna Cepat (1-Click)
                            </h5>
                            <span class="badge bg-light text-dark" style="font-size:0.75rem;border:1px solid var(--border);">Pilih Cepat</span>
                        </div>
                        <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:1rem;">
                            Klik salah satu kombinasi palet siap pakai di bawah ini, atau sesuaikan warna secara manual pada kotak pemilih warna.
                        </p>

                        <div class="row g-2" id="presetContainer">
                            <!-- Preset 1: Default UNIKA -->
                            <div class="col-sm-6">
                                <button type="button" class="btn-preset-card w-100 text-start p-2 rounded-3 border d-flex align-items-center justify-content-between" 
                                    data-preset="unika_default" 
                                    data-primary="#0A192F" 
                                    data-accent="#6A1B9A" 
                                    data-gold="#F59E0B"
                                    data-bg="#F8F9FA">
                                    <div>
                                        <div style="font-weight:700;font-size:0.82rem;color:#0A192F;">UNIKA Heritage (Default)</div>
                                        <div style="font-size:0.72rem;color:#6b7280;">Royal Navy &amp; Purple</div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <span style="width:18px;height:18px;border-radius:50%;background:#0A192F;display:inline-block;border:1px solid rgba(0,0,0,0.1);"></span>
                                        <span style="width:18px;height:18px;border-radius:50%;background:#6A1B9A;display:inline-block;border:1px solid rgba(0,0,0,0.1);"></span>
                                        <span style="width:18px;height:18px;border-radius:50%;background:#F59E0B;display:inline-block;border:1px solid rgba(0,0,0,0.1);"></span>
                                    </div>
                                </button>
                            </div>

                            <!-- Preset 2: Emerald Mutu -->
                            <div class="col-sm-6">
                                <button type="button" class="btn-preset-card w-100 text-start p-2 rounded-3 border d-flex align-items-center justify-content-between" 
                                    data-preset="emerald" 
                                    data-primary="#064E3B" 
                                    data-accent="#0D9488" 
                                    data-gold="#F59E0B"
                                    data-bg="#F0FDF4">
                                    <div>
                                        <div style="font-weight:700;font-size:0.82rem;color:#064E3B;">Emerald Mutu Kampus</div>
                                        <div style="font-size:0.72rem;color:#6b7280;">Forest Green &amp; Teal</div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <span style="width:18px;height:18px;border-radius:50%;background:#064E3B;display:inline-block;"></span>
                                        <span style="width:18px;height:18px;border-radius:50%;background:#0D9488;display:inline-block;"></span>
                                        <span style="width:18px;height:18px;border-radius:50%;background:#F59E0B;display:inline-block;"></span>
                                    </div>
                                </button>
                            </div>

                            <!-- Preset 3: Ocean Sapphire -->
                            <div class="col-sm-6">
                                <button type="button" class="btn-preset-card w-100 text-start p-2 rounded-3 border d-flex align-items-center justify-content-between" 
                                    data-preset="sapphire" 
                                    data-primary="#0F2C59" 
                                    data-accent="#0284C7" 
                                    data-gold="#EAB308"
                                    data-bg="#F8FAFC">
                                    <div>
                                        <div style="font-weight:700;font-size:0.82rem;color:#0F2C59;">Ocean Sapphire</div>
                                        <div style="font-size:0.72rem;color:#6b7280;">Deep Blue &amp; Sky Cyan</div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <span style="width:18px;height:18px;border-radius:50%;background:#0F2C59;display:inline-block;"></span>
                                        <span style="width:18px;height:18px;border-radius:50%;background:#0284C7;display:inline-block;"></span>
                                        <span style="width:18px;height:18px;border-radius:50%;background:#EAB308;display:inline-block;"></span>
                                    </div>
                                </button>
                            </div>

                            <!-- Preset 4: Royal Crimson -->
                            <div class="col-sm-6">
                                <button type="button" class="btn-preset-card w-100 text-start p-2 rounded-3 border d-flex align-items-center justify-content-between" 
                                    data-preset="crimson" 
                                    data-primary="#450A0A" 
                                    data-accent="#DC2626" 
                                    data-gold="#FBBF24"
                                    data-bg="#FEF2F2">
                                    <div>
                                        <div style="font-weight:700;font-size:0.82rem;color:#450A0A;">Royal Crimson Red</div>
                                        <div style="font-size:0.72rem;color:#6b7280;">Wine Red &amp; Amber Gold</div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <span style="width:18px;height:18px;border-radius:50%;background:#450A0A;display:inline-block;"></span>
                                        <span style="width:18px;height:18px;border-radius:50%;background:#DC2626;display:inline-block;"></span>
                                        <span style="width:18px;height:18px;border-radius:50%;background:#FBBF24;display:inline-block;"></span>
                                    </div>
                                </button>
                            </div>

                            <!-- Preset 5: Modern Slate Indigo -->
                            <div class="col-sm-6">
                                <button type="button" class="btn-preset-card w-100 text-start p-2 rounded-3 border d-flex align-items-center justify-content-between" 
                                    data-preset="slate_indigo" 
                                    data-primary="#0F172A" 
                                    data-accent="#4F46E5" 
                                    data-gold="#F59E0B"
                                    data-bg="#F8FAFC">
                                    <div>
                                        <div style="font-weight:700;font-size:0.82rem;color:#0F172A;">Slate &amp; Indigo Tech</div>
                                        <div style="font-size:0.72rem;color:#6b7280;">Dark Slate &amp; Indigo Blue</div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <span style="width:18px;height:18px;border-radius:50%;background:#0F172A;display:inline-block;"></span>
                                        <span style="width:18px;height:18px;border-radius:50%;background:#4F46E5;display:inline-block;"></span>
                                        <span style="width:18px;height:18px;border-radius:50%;background:#F59E0B;display:inline-block;"></span>
                                    </div>
                                </button>
                            </div>

                            <!-- Preset 6: Elegant Dark Gold -->
                            <div class="col-sm-6">
                                <button type="button" class="btn-preset-card w-100 text-start p-2 rounded-3 border d-flex align-items-center justify-content-between" 
                                    data-preset="dark_gold" 
                                    data-primary="#18181B" 
                                    data-accent="#D97706" 
                                    data-gold="#FBBF24"
                                    data-bg="#FAFAFA">
                                    <div>
                                        <div style="font-weight:700;font-size:0.82rem;color:#18181B;">Prestige Charcoal &amp; Gold</div>
                                        <div style="font-size:0.72rem;color:#6b7280;">Charcoal Black &amp; Warm Gold</div>
                                    </div>
                                    <div class="d-flex gap-1">
                                        <span style="width:18px;height:18px;border-radius:50%;background:#18181B;display:inline-block;"></span>
                                        <span style="width:18px;height:18px;border-radius:50%;background:#D97706;display:inline-block;"></span>
                                        <span style="width:18px;height:18px;border-radius:50%;background:#FBBF24;display:inline-block;"></span>
                                    </div>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Color Pickers Detail -->
                    <div class="card-lpm mb-4" style="background:#fff;border:1px solid var(--border);border-radius:14px;padding:1.5rem;box-shadow:var(--shadow-sm);">
                        <h5 style="font-weight:700;color:var(--navy);margin-bottom:1rem;font-size:1.05rem;">
                            🎯 2. Palet Warna Spesifik (Color Pickers)
                        </h5>

                        <div class="row g-3">
                            <!-- Warna Utama (Primary / Navy) -->
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight:700;color:var(--navy);font-size:0.875rem;">
                                    Warna Utama (Primary / Headings)
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" class="form-control form-control-color p-1" id="colorPickerPrimary" value="<?= e($theme_primary_color) ?>" style="width:50px;height:42px;border-radius:8px;cursor:pointer;">
                                    <input type="text" name="theme_primary_color" id="hexPrimary" class="form-control text-uppercase" value="<?= e($theme_primary_color) ?>" maxlength="7" style="font-family:monospace;font-weight:700;border:1.5px solid var(--border);">
                                </div>
                                <small class="text-muted" style="font-size:0.75rem;">Dipakai untuk: Judul besar, sidebar footer, kartu utama, dan teks penekanan.</small>
                            </div>

                            <!-- Warna Aksen (Accent / Purple) -->
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight:700;color:var(--navy);font-size:0.875rem;">
                                    Warna Aksen (Secondary / Tombol)
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" class="form-control form-control-color p-1" id="colorPickerAccent" value="<?= e($theme_accent_color) ?>" style="width:50px;height:42px;border-radius:8px;cursor:pointer;">
                                    <input type="text" name="theme_accent_color" id="hexAccent" class="form-control text-uppercase" value="<?= e($theme_accent_color) ?>" maxlength="7" style="font-family:monospace;font-weight:700;border:1.5px solid var(--border);">
                                </div>
                                <small class="text-muted" style="font-size:0.75rem;">Dipakai untuk: Tombol utama, hover link, gradient header, dan ikon unggulan.</small>
                            </div>

                            <!-- Warna Emas / Highlight -->
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight:700;color:var(--navy);font-size:0.875rem;">
                                    Warna Highlight / Status Badge
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" class="form-control form-control-color p-1" id="colorPickerGold" value="<?= e($theme_gold_color) ?>" style="width:50px;height:42px;border-radius:8px;cursor:pointer;">
                                    <input type="text" name="theme_gold_color" id="hexGold" class="form-control text-uppercase" value="<?= e($theme_gold_color) ?>" maxlength="7" style="font-family:monospace;font-weight:700;border:1.5px solid var(--border);">
                                </div>
                                <small class="text-muted" style="font-size:0.75rem;">Dipakai untuk: Badge Unggul/A, aksen bintang, countdown, dan garis sorotan.</small>
                            </div>

                            <!-- Warna Latar Belakang Website -->
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight:700;color:var(--navy);font-size:0.875rem;">
                                    Warna Latar Website (Background)
                                </label>
                                <div class="d-flex align-items-center gap-2">
                                    <input type="color" class="form-control form-control-color p-1" id="colorPickerBg" value="<?= e($theme_bg_main) ?>" style="width:50px;height:42px;border-radius:8px;cursor:pointer;">
                                    <input type="text" name="theme_bg_main" id="hexBg" class="form-control text-uppercase" value="<?= e($theme_bg_main) ?>" maxlength="7" style="font-family:monospace;font-weight:700;border:1.5px solid var(--border);">
                                </div>
                                <small class="text-muted" style="font-size:0.75rem;">Default: #F8F9FA (Abu terang modern). Hindari warna yang terlalu gelap.</small>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Gaya Navbar & Tipografi -->
                    <div class="card-lpm mb-4" style="background:#fff;border:1px solid var(--border);border-radius:14px;padding:1.5rem;box-shadow:var(--shadow-sm);">
                        <h5 style="font-weight:700;color:var(--navy);margin-bottom:1rem;font-size:1.05rem;">
                            ✨ 3. Gaya Header &amp; Tipografi
                        </h5>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" style="font-weight:700;color:var(--navy);font-size:0.875rem;">
                                    Gaya Tampilan Navbar Atas
                                </label>
                                <select name="theme_navbar_style" id="navbarStyleSelect" class="form-select" style="border:1.5px solid var(--border);padding:0.65rem 1rem;">
                                    <option value="default" <?= $theme_navbar_style === 'default' ? 'selected' : '' ?>>Putih Bersih Glassmorphic (Standar)</option>
                                    <option value="colored" <?= $theme_navbar_style === 'colored' ? 'selected' : '' ?>>Warna Senada Primary (Elegan)</option>
                                    <option value="dark" <?= $theme_navbar_style === 'dark' ? 'selected' : '' ?>>Dark Solid Modern</option>
                                </select>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" style="font-weight:700;color:var(--navy);font-size:0.875rem;">
                                    Font Judul &amp; Heading
                                </label>
                                <select name="theme_font_heading" id="fontHeadingSelect" class="form-select" style="border:1.5px solid var(--border);padding:0.65rem 1rem;">
                                    <option value="Plus Jakarta Sans" <?= $theme_font_heading === 'Plus Jakarta Sans' ? 'selected' : '' ?>>Plus Jakarta Sans (Standar UNIKA)</option>
                                    <option value="Inter" <?= $theme_font_heading === 'Inter' ? 'selected' : '' ?>>Inter (Clean &amp; Minimalis)</option>
                                    <option value="Outfit" <?= $theme_font_heading === 'Outfit' ? 'selected' : '' ?>>Outfit (Modern Tech &amp; Ramah)</option>
                                    <option value="Montserrat" <?= $theme_font_heading === 'Montserrat' ? 'selected' : '' ?>>Montserrat (Tegas &amp; Formal)</option>
                                    <option value="Poppins" <?= $theme_font_heading === 'Poppins' ? 'selected' : '' ?>>Poppins (Geometris Populer)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Aksi Simpan & Reset -->
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pt-2">
                        <button type="button" class="btn btn-outline-danger btn-sm px-3" onclick="confirmResetDefaults()" style="border-radius:8px;font-weight:600;">
                            <i class="bi bi-arrow-counterclockwise"></i> Kembalikan ke Standar Default
                        </button>
                        <button type="submit" class="btn-submit px-4" style="font-size:0.95rem;">
                            <i class="bi bi-check-circle-fill me-1"></i> Simpan Tema Baru
                        </button>
                    </div>
                </div>

                <!-- Kolom Kanan: Live Interactive Preview Box -->
                <div class="col-lg-5">
                    <div style="position:sticky;top:90px;">
                        <div class="card-lpm" style="background:#fff;border:1px solid var(--border);border-radius:14px;box-shadow:var(--shadow-md);overflow:hidden;">
                            <div style="padding:1rem 1.25rem;background:#f8fafc;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;">
                                <div style="font-weight:700;font-size:0.88rem;color:var(--navy);display:flex;align-items:center;gap:6px;">
                                    <span style="width:10px;height:10px;border-radius:50%;background:#10B981;display:inline-block;animation:pulse 2s infinite;"></span>
                                    Pratinjau Langsung (Live Preview)
                                </div>
                                <span class="badge bg-secondary" style="font-size:0.65rem;">Interaktif</span>
                            </div>

                            <!-- Preview Mock Canvas -->
                            <div id="previewCanvas" style="padding:1.5rem;background:<?= e($theme_bg_main) ?>;transition:all 0.25s ease;">
                                
                                <!-- Mock Navbar -->
                                <div id="previewNavbar" style="background:#ffffff;border:1px solid var(--border);border-radius:8px;padding:0.6rem 0.85rem;margin-bottom:1rem;display:flex;align-items:center;justify-content:space-between;box-shadow:0 2px 6px rgba(0,0,0,0.04);transition:all 0.25s ease;">
                                    <div style="display:flex;align-items:center;gap:6px;">
                                        <div style="width:22px;height:22px;border-radius:4px;background:var(--navy);display:flex;align-items:center;justify-content:center;color:#fff;font-size:10px;font-weight:800;" id="previewNavLogo">
                                            U
                                        </div>
                                        <div style="font-size:0.75rem;font-weight:800;color:var(--navy);" id="previewNavTitle">LPM UNIKA</div>
                                    </div>
                                    <div style="display:flex;gap:6px;font-size:0.7rem;color:var(--text-muted);" id="previewNavLinks">
                                        <span style="font-weight:700;color:var(--navy);" id="previewNavHome">Beranda</span>
                                        <span>Profil</span>
                                        <span>SPMI</span>
                                        <span style="background:var(--purple);color:#fff;padding:0.15rem 0.45rem;border-radius:4px;font-size:0.65rem;" id="previewNavBtn">Layanan</span>
                                    </div>
                                </div>

                                <!-- Mock Hero Card -->
                                <div id="previewHero" style="background:linear-gradient(135deg, <?= e($theme_primary_color) ?> 0%, #1B263B 100%);border-radius:10px;padding:1.25rem;color:#ffffff;margin-bottom:1rem;box-shadow:0 4px 12px rgba(0,0,0,0.08);transition:all 0.25s ease;">
                                    <div style="display:inline-block;padding:0.2rem 0.6rem;background:rgba(255,255,255,0.15);border-radius:50px;font-size:0.68rem;font-weight:600;margin-bottom:0.5rem;color:<?= e($theme_gold_color) ?>;" id="previewBadge">
                                        ★ SPMI UNIKA Unggul
                                    </div>
                                    <h4 style="font-size:1rem;font-weight:800;margin-bottom:0.4rem;line-height:1.3;" id="previewHeadline">
                                        Mewujudkan Budaya Mutu &amp; Integritas Akademik
                                    </h4>
                                    <p style="font-size:0.72rem;color:rgba(255,255,255,0.8);margin-bottom:0.75rem;line-height:1.5;">
                                        Sistem penjaminan mutu terpadu dengan standar nasional dan rekognisi internasional.
                                    </p>
                                    <div class="d-flex gap-2">
                                        <button type="button" class="btn btn-sm text-white" id="previewBtnAccent" style="background:<?= e($theme_accent_color) ?>;font-size:0.72rem;font-weight:700;border:none;padding:0.3rem 0.75rem;border-radius:6px;">
                                            Jelajahi SPMI &rarr;
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-light" style="font-size:0.72rem;padding:0.3rem 0.65rem;border-radius:6px;">
                                            Dokumen
                                        </button>
                                    </div>
                                </div>

                                <!-- Mock Component Grid -->
                                <div class="row g-2">
                                    <div class="col-6">
                                        <div style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:0.85rem;box-shadow:0 1px 4px rgba(0,0,0,0.03);">
                                            <div style="font-size:0.68rem;font-weight:700;color:<?= e($theme_accent_color) ?>;" id="previewCardSub">STATUS AKREDITASI</div>
                                            <div style="font-size:1.15rem;font-weight:800;color:<?= e($theme_primary_color) ?>;" id="previewCardTitle">UNIKA UNGGUL</div>
                                            <div style="font-size:0.65rem;color:#6b7280;margin-top:2px;">BAN-PT SK No. 2606</div>
                                            <div class="mt-2">
                                                <span style="display:inline-block;padding:0.15rem 0.45rem;border-radius:4px;font-size:0.62rem;font-weight:700;background:#FEF3C7;color:#92400E;" id="previewGoldPill">
                                                    ★ Terakreditasi Unggul
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div style="background:#fff;border:1px solid var(--border);border-radius:8px;padding:0.85rem;box-shadow:0 1px 4px rgba(0,0,0,0.03);">
                                            <div style="font-size:0.68rem;font-weight:700;color:<?= e($theme_accent_color) ?>;" id="previewCardSub2">AUDIT MUTU</div>
                                            <div style="font-size:1.15rem;font-weight:800;color:<?= e($theme_primary_color) ?>;" id="previewCardTitle2">Siklus AMI 2026</div>
                                            <div style="font-size:0.65rem;color:#6b7280;margin-top:2px;">38 Auditor Tersertifikasi</div>
                                            <div class="mt-2">
                                                <span style="display:inline-block;padding:0.15rem 0.45rem;border-radius:4px;font-size:0.62rem;font-weight:700;background:<?= e($theme_accent_color) ?>15;color:<?= e($theme_accent_color) ?>;" id="previewAccentPill">
                                                    ● Aktif Berjalan
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Mock Footer Strip -->
                                <div id="previewFooter" style="background:<?= e($theme_primary_color) ?>;border-radius:6px;padding:0.65rem 0.85rem;margin-top:1rem;color:#ffffff;display:flex;align-items:center;justify-content:space-between;font-size:0.65rem;transition:all 0.25s ease;">
                                    <span>&copy; <?= date('Y') ?> LPM UNIKA Soegijapranata</span>
                                    <span style="color:<?= e($theme_gold_color) ?>;" id="previewFooterGold">Semarang, Jawa Tengah</span>
                                </div>
                            </div>
                        </div>

                        <!-- Info Bantuan -->
                        <div class="mt-3 p-3 rounded-3" style="background:#F0FDF4;border:1px solid #BBF7D0;font-size:0.8rem;color:#166534;">
                            <i class="bi bi-info-circle-fill me-1"></i>
                            <strong>Info Sistem:</strong> Perubahan warna akan langsung diterapkan pada seluruh halaman publik (Beranda, Profil, SPMI, AMI, Akreditasi, Dokumen, Layanan, dan Halaman Khusus) seketika setelah tombol <em>Simpan Tema Baru</em> ditekan.
                        </div>
                    </div>
                </div>
            </div>
        </form>

        <!-- Form Terpisah untuk Reset Default -->
        <form method="POST" id="resetForm" style="display:none;">
            <input type="hidden" name="action" value="reset_default">
        </form>
    </div>
</div>

<script>
// Interaktivitas Color Pickers & Live Preview
document.addEventListener('DOMContentLoaded', function() {
    const pickerPrimary = document.getElementById('colorPickerPrimary');
    const hexPrimary    = document.getElementById('hexPrimary');
    const pickerAccent  = document.getElementById('colorPickerAccent');
    const hexAccent     = document.getElementById('hexAccent');
    const pickerGold    = document.getElementById('colorPickerGold');
    const hexGold       = document.getElementById('hexGold');
    const pickerBg      = document.getElementById('colorPickerBg');
    const hexBg         = document.getElementById('hexBg');
    const navStyle      = document.getElementById('navbarStyleSelect');
    const fontHeading   = document.getElementById('fontHeadingSelect');
    const presetInput   = document.getElementById('themePresetInput');

    // Elemen Live Preview
    const pCanvas       = document.getElementById('previewCanvas');
    const pHero         = document.getElementById('previewHero');
    const pBadge        = document.getElementById('previewBadge');
    const pBtnAccent    = document.getElementById('previewBtnAccent');
    const pCardSub      = document.getElementById('previewCardSub');
    const pCardTitle    = document.getElementById('previewCardTitle');
    const pCardSub2     = document.getElementById('previewCardSub2');
    const pCardTitle2   = document.getElementById('previewCardTitle2');
    const pAccentPill   = document.getElementById('previewAccentPill');
    const pFooter       = document.getElementById('previewFooter');
    const pFooterGold   = document.getElementById('previewFooterGold');
    const pNavbar       = document.getElementById('previewNavbar');
    const pNavTitle     = document.getElementById('previewNavTitle');
    const pNavLogo      = document.getElementById('previewNavLogo');
    const pNavHome      = document.getElementById('previewNavHome');
    const pNavBtn       = document.getElementById('previewNavBtn');

    function updatePreview() {
        const prim = hexPrimary.value;
        const acc  = hexAccent.value;
        const gld  = hexGold.value;
        const bg   = hexBg.value;
        const nSty = navStyle.value;

        // Background Canvas
        pCanvas.style.backgroundColor = bg;

        // Hero Mockup
        pHero.style.background = `linear-gradient(135deg, ${prim} 0%, #1a2332 100%)`;
        pBadge.style.color = gld;
        pBtnAccent.style.backgroundColor = acc;

        // Cards & Text
        pCardTitle.style.color = prim;
        pCardSub.style.color   = acc;
        pCardTitle2.style.color = prim;
        pCardSub2.style.color   = acc;
        pAccentPill.style.backgroundColor = acc + '20';
        pAccentPill.style.color = acc;

        // Footer Mock
        pFooter.style.backgroundColor = prim;
        pFooterGold.style.color = gld;

        // Navbar Mock
        pNavLogo.style.backgroundColor = prim;
        pNavBtn.style.backgroundColor  = acc;

        if (nSty === 'colored') {
            pNavbar.style.backgroundColor = prim;
            pNavTitle.style.color = '#ffffff';
            pNavHome.style.color  = '#ffffff';
        } else if (nSty === 'dark') {
            pNavbar.style.backgroundColor = '#0F172A';
            pNavTitle.style.color = '#ffffff';
            pNavHome.style.color  = '#ffffff';
        } else {
            pNavbar.style.backgroundColor = '#ffffff';
            pNavTitle.style.color = prim;
            pNavHome.style.color  = prim;
        }

        // Font
        const fFam = fontHeading.value;
        pCardTitle.style.fontFamily  = fFam;
        pCardTitle2.style.fontFamily = fFam;
        document.getElementById('previewHeadline').style.fontFamily = fFam;
    }

    // Sync input color & text
    function linkColorInput(picker, text) {
        picker.addEventListener('input', function() {
            text.value = picker.value.toUpperCase();
            presetInput.value = 'custom';
            updatePreview();
        });
        text.addEventListener('input', function() {
            if (/^#[0-9A-Fa-f]{6}$/.test(text.value)) {
                picker.value = text.value;
                presetInput.value = 'custom';
                updatePreview();
            }
        });
    }

    linkColorInput(pickerPrimary, hexPrimary);
    linkColorInput(pickerAccent, hexAccent);
    linkColorInput(pickerGold, hexGold);
    linkColorInput(pickerBg, hexBg);

    navStyle.addEventListener('change', updatePreview);
    fontHeading.addEventListener('change', updatePreview);

    // Click Preset Cards
    document.querySelectorAll('.btn-preset-card').forEach(btn => {
        btn.addEventListener('click', function() {
            const pId  = this.getAttribute('data-preset');
            const prim = this.getAttribute('data-primary');
            const acc  = this.getAttribute('data-accent');
            const gld  = this.getAttribute('data-gold');
            const bg   = this.getAttribute('data-bg');

            presetInput.value = pId;

            pickerPrimary.value = prim;
            hexPrimary.value    = prim;

            pickerAccent.value  = acc;
            hexAccent.value     = acc;

            pickerGold.value    = gld;
            hexGold.value       = gld;

            pickerBg.value      = bg;
            hexBg.value         = bg;

            // Highlight chosen preset card
            document.querySelectorAll('.btn-preset-card').forEach(b => b.classList.remove('border-primary', 'bg-light'));
            this.classList.add('border-primary', 'bg-light');

            updatePreview();
        });
    });

    // Run initial update
    updatePreview();
});

function confirmResetDefaults() {
    if (confirm('Yakin ingin mengembalikan seluruh warna dan gaya website ke pengaturan awal UNIKA?')) {
        document.getElementById('resetForm').submit();
    }
}
</script>

<style>
.btn-preset-card {
    background: #ffffff;
    cursor: pointer;
    transition: all 0.2s ease;
}
.btn-preset-card:hover {
    border-color: var(--purple) !important;
    background: #f8fafc;
    transform: translateY(-1px);
}
@keyframes pulse {
    0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.7); }
    70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(16, 185, 129, 0); }
    100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); }
}
</style>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
