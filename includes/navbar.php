<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$current_slug = trim($_GET['slug'] ?? '');

$nav_main_pages = [];
$nav_dropdown_pages = [];
try {
    $db_nav = getDB();
    $nav_main_pages = $db_nav->query("SELECT id, judul, slug, nav_label FROM pages WHERE show_in_nav = 1 AND (nav_position = 'main' OR nav_position IS NULL OR nav_position = '') AND status = 'publish' ORDER BY urutan ASC, judul ASC")->fetchAll();
    $nav_dropdown_pages = $db_nav->query("SELECT id, judul, slug, nav_label FROM pages WHERE show_in_nav = 1 AND nav_position = 'dropdown' AND status = 'publish' ORDER BY urutan ASC, judul ASC")->fetchAll();
} catch (Exception $e) {
    $nav_main_pages = [];
    $nav_dropdown_pages = [];
}
?>
<nav id="main-navbar" class="navbar navbar-expand-xl sticky-top">
    <div class="container-fluid px-lg-4">
        <!-- Brand Logo & Name -->
        <a class="navbar-brand" href="<?= SITE_URL ?>/">
            <div class="brand-logo-wrap">
                <img src="<?= SITE_URL ?>/assets/images/logo-unika.png" alt="Logo UNIKA Soegijapranata" class="brand-logo-img">
            </div>
            <div class="brand-text-wrap">
                <div class="brand-title">Lembaga Penjaminan Mutu</div>
                <div class="brand-subtitle">Universitas Katolik Soegijapranata</div>
            </div>
        </a>

        <!-- Hamburger Toggler for Mobile -->
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain" aria-controls="navbarMain" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>

        <!-- Direct Navigation Items (No Dropdowns) -->
        <div class="collapse navbar-collapse" id="navbarMain">
            <ul class="navbar-nav ms-auto align-items-xl-center">
                
                <!-- 1. Beranda -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'index' ? 'active' : '' ?>" href="<?= SITE_URL ?>/">
                        Beranda
                    </a>
                </li>

                <!-- 2. Profil (Whimsical Dropdown) -->
                <li class="nav-item dropdown nav-hover-dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($current_page, ['profil', 'profil-sejarah', 'visi-misi', 'struktur-organisasi']) ? 'active' : '' ?>" href="<?= SITE_URL ?>/profil.php" id="navbarProfil" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Profil
                    </a>
                    <div class="dropdown-menu whimsical-dropdown" aria-labelledby="navbarProfil" style="min-width:270px;">
                        <div class="whimsical-category-label">
                            <i class="bi bi-shield-check"></i> Tata Kelola &amp; Organisasi
                        </div>
                        <div class="whimsical-grid" style="grid-template-columns: 1fr;">
                            <a href="<?= SITE_URL ?>/profil.php?view=sejarah" class="whimsical-item <?= ($current_page === 'profil' && ($_GET['view'] ?? '') === 'sejarah') || $current_page === 'profil-sejarah' ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap">
                                    <i class="bi bi-clock-history"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Profil &amp; Sejarah LPM</div>
                                </div>
                            </a>
                            <a href="<?= SITE_URL ?>/profil.php?view=visi-misi" class="whimsical-item <?= ($current_page === 'profil' && ($_GET['view'] ?? '') === 'visi-misi') || $current_page === 'visi-misi' ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap">
                                    <i class="bi bi-compass"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Visi dan Misi</div>
                                </div>
                            </a>
                            <a href="<?= SITE_URL ?>/profil.php?view=struktur" class="whimsical-item <?= ($current_page === 'profil' && ($_GET['view'] ?? '') === 'struktur') || $current_page === 'struktur-organisasi' ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap">
                                    <i class="bi bi-diagram-3"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Struktur Organisasi</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </li>

                <!-- 3. SPMI -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'spmi' ? 'active' : '' ?>" href="<?= SITE_URL ?>/spmi.php">
                        SPMI
                    </a>
                </li>

                <!-- 4. AMI (Whimsical Dropdown) -->
                <li class="nav-item dropdown nav-hover-dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($current_page, ['ami', 'siklus-ami']) ? 'active' : '' ?>" href="<?= SITE_URL ?>/ami.php" id="navbarAmi" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        AMI
                    </a>
                    <div class="dropdown-menu whimsical-dropdown" aria-labelledby="navbarAmi" style="min-width:300px;">
                        <div class="whimsical-category-label">
                            <i class="bi bi-shield-check"></i> Audit Mutu Internal
                        </div>
                        <div class="whimsical-grid" style="grid-template-columns: 1fr;">
                            <a href="<?= SITE_URL ?>/ami.php" class="whimsical-item <?= $current_page === 'ami' ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap">
                                    <i class="bi bi-info-circle-fill"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Pengantar AMI</div>
                                </div>
                            </a>
                            <a href="<?= SITE_URL ?>/siklus-ami.php" class="whimsical-item <?= $current_page === 'siklus-ami' ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap">
                                    <i class="bi bi-arrow-repeat"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Siklus AMI</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </li>

                <!-- 5. Akreditasi (Whimsical Dropdown) -->
                <li class="nav-item dropdown nav-hover-dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($current_page, ['akreditasi', 'akreditasi-institusi', 'lembaga-akreditasi', 'penghargaan', 'akreditasi-prodi']) ? 'active' : '' ?>" href="<?= SITE_URL ?>/akreditasi.php" id="navbarAkreditasi" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Akreditasi
                    </a>
                    <div class="dropdown-menu whimsical-dropdown" aria-labelledby="navbarAkreditasi">
                        <div class="whimsical-category-label">
                            <i class="bi bi-patch-check-fill"></i> Status &amp; Rekognisi Mutu
                        </div>
                        <div class="whimsical-grid">
                            <a href="<?= SITE_URL ?>/akreditasi-institusi.php" class="whimsical-item <?= $current_page === 'akreditasi-institusi' ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap">
                                    <i class="bi bi-award-fill"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Akreditasi Institusi</div>
                                </div>
                            </a>
                            <a href="<?= SITE_URL ?>/lembaga-akreditasi.php" class="whimsical-item <?= $current_page === 'lembaga-akreditasi' ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap">
                                    <i class="bi bi-bank2"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Lembaga Akreditasi</div>
                                </div>
                            </a>
                            <a href="<?= SITE_URL ?>/pemeringkatan.php" class="whimsical-item <?= in_array($current_page, ['pemeringkatan', 'penghargaan']) ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap" style="background:rgba(217,119,6,0.1);color:#D97706;">
                                    <i class="bi bi-bar-chart-line-fill"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Pemeringkatan</div>
                                </div>
                            </a>
                            <a href="<?= SITE_URL ?>/akreditasi-prodi.php" class="whimsical-item <?= $current_page === 'akreditasi-prodi' ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap">
                                    <i class="bi bi-mortarboard-fill"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Daftar Program Studi</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </li>

                <!-- 6. FAQ & Knowledge (Whimsical Dropdown) -->
                <li class="nav-item dropdown nav-hover-dropdown">
                    <a class="nav-link dropdown-toggle <?= in_array($current_page, ['faq', 'glosarium', 'kalender-mutu', 'buletin', 'knowledge']) ? 'active' : '' ?>" href="<?= SITE_URL ?>/faq.php" id="navbarFAQ" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        FAQ
                    </a>
                    <div class="dropdown-menu whimsical-dropdown whimsical-dropdown-faq" aria-labelledby="navbarFAQ">
                        <div class="whimsical-category-label">
                            <i class="bi bi-lightbulb-fill"></i> Pusat Pengetahuan &amp; Edukasi
                        </div>
                        <div class="whimsical-grid">
                            <a href="<?= SITE_URL ?>/faq.php" class="whimsical-item <?= $current_page === 'faq' ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap">
                                    <i class="bi bi-question-circle-fill"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Tanya Jawab (FAQ)</div>
                                </div>
                            </a>
                            <a href="<?= SITE_URL ?>/glosarium.php" class="whimsical-item <?= $current_page === 'glosarium' ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap">
                                    <i class="bi bi-book-fill"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Glosarium Mutu</div>
                                </div>
                            </a>
                            <a href="<?= SITE_URL ?>/kalender-mutu.php" class="whimsical-item <?= $current_page === 'kalender-mutu' ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap">
                                    <i class="bi bi-calendar3-event-fill"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Kalender Mutu</div>
                                </div>
                            </a>
                            <a href="<?= SITE_URL ?>/buletin.php" class="whimsical-item <?= $current_page === 'buletin' ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap">
                                    <i class="bi bi-journal-bookmark-fill"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Buletin JAMUS</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </li>

                <!-- 7. Kegiatan -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['berita', 'berita-detail']) ? 'active' : '' ?>" href="<?= SITE_URL ?>/berita.php">
                        Kegiatan
                    </a>
                </li>

                <!-- Direct Main Navbar Pages -->
                <?php foreach ($nav_main_pages as $nmp): ?>
                <li class="nav-item">
                    <a class="nav-link <?= ($current_page === 'page' && $current_slug === $nmp['slug']) ? 'active' : '' ?>" href="<?= SITE_URL ?>/page.php?slug=<?= e($nmp['slug']) ?>">
                        <?= e($nmp['nav_label'] ?: $nmp['judul']) ?>
                    </a>
                </li>
                <?php endforeach; ?>

                <!-- Dropdown Custom Pages -->
                <?php if (!empty($nav_dropdown_pages)): ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?= $current_page === 'page' ? 'active' : '' ?>" href="#" id="navbarCustomPages" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        Lainnya
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end shadow-sm" aria-labelledby="navbarCustomPages" style="border-radius:10px;border:1px solid var(--border);padding:0.5rem;">
                        <?php foreach ($nav_dropdown_pages as $ndp): ?>
                        <li>
                            <a class="dropdown-item py-2 px-3 <?= ($current_page === 'page' && $current_slug === $ndp['slug']) ? 'active font-weight-bold' : '' ?>" href="<?= SITE_URL ?>/page.php?slug=<?= e($ndp['slug']) ?>" style="border-radius:6px;font-size:0.85rem;">
                                <?= e($ndp['nav_label'] ?: $ndp['judul']) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php endif; ?>

                <!-- 8. Layanan & Feedback (Whimsical Dropdown CTA) -->
                <li class="nav-item dropdown nav-hover-dropdown">
                    <a class="nav-link nav-cta dropdown-toggle <?= in_array($current_page, ['layanan', 'kunjungan', 'pelatihan-eksternal', 'pelatihan', 'survei-kepuasan', 'feedback-kunjungan', 'kritik-saran']) ? 'active' : '' ?>" href="<?= SITE_URL ?>/layanan.php" id="navbarLayanan" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14" style="margin-right:4px;vertical-align:-1px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                        </svg>
                        Layanan
                    </a>
                    <div class="dropdown-menu dropdown-menu-end whimsical-dropdown" aria-labelledby="navbarLayanan" style="min-width:340px;">
                        <div class="whimsical-category-label">
                            <i class="bi bi-briefcase-fill"></i> Layanan &amp; Kemitraan Mutu
                        </div>
                        <div class="whimsical-grid" style="grid-template-columns: 1fr;">
                            <!-- Sub Menu 1: Info Pelatihan -->
                            <a href="<?= SITE_URL ?>/pelatihan.php" class="whimsical-item <?= in_array($current_page, ['pelatihan-eksternal', 'pelatihan']) ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap" style="background:rgba(124,58,237,0.12);color:#7C3AED;">
                                    <i class="bi bi-mortarboard-fill"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Info Pelatihan</div>
                                </div>
                            </a>
                            <!-- Sub Menu 2: Kunjungan Studi Banding -->
                            <a href="<?= SITE_URL ?>/kunjungan.php" class="whimsical-item <?= in_array($current_page, ['kunjungan']) ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap" style="background:rgba(2,132,199,0.12);color:#0284C7;">
                                    <i class="bi bi-building-fill-check"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Kunjungan Studi Banding</div>
                                </div>
                            </a>
                            <!-- Sub Menu 3: Survey Kepuasan Layanan LPM -->
                            <a href="<?= SITE_URL ?>/survei-kepuasan.php" class="whimsical-item <?= in_array($current_page, ['survei-kepuasan', 'feedback-kunjungan', 'kritik-saran', 'layanan']) ? 'active' : '' ?>">
                                <div class="whimsical-icon-wrap" style="background:rgba(16,185,129,0.12);color:#059669;">
                                    <i class="bi bi-emoji-smile-fill"></i>
                                </div>
                                <div class="whimsical-item-body">
                                    <div class="whimsical-item-title">Survey Kepuasan Layanan LPM</div>
                                </div>
                            </a>
                        </div>
                    </div>
                </li>

            </ul>
        </div>
    </div>
</nav>
