<?php
$current_page = basename($_SERVER['PHP_SELF'], '.php');
?>
<nav id="main-navbar" class="navbar navbar-expand-xl sticky-top">
    <div class="container-fluid px-lg-4">
        <!-- Brand Logo & Name -->
        <a class="navbar-brand" href="<?= SITE_URL ?>/">
            <div class="brand-logo-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-2.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                </svg>
            </div>
            <div class="brand-text-wrap">
                <div class="brand-title">LPM UNIKA</div>
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

                <!-- 2. Profil -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'profil' ? 'active' : '' ?>" href="<?= SITE_URL ?>/profil.php">
                        Profil
                    </a>
                </li>

                <!-- 3. SPMI -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'spmi' ? 'active' : '' ?>" href="<?= SITE_URL ?>/spmi.php">
                        SPMI
                    </a>
                </li>

                <!-- 4. AMI -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'ami' ? 'active' : '' ?>" href="<?= SITE_URL ?>/ami.php">
                        AMI
                    </a>
                </li>

                <!-- 5. Akreditasi -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'akreditasi' ? 'active' : '' ?>" href="<?= SITE_URL ?>/akreditasi.php">
                        Akreditasi
                    </a>
                </li>

                <!-- 6. Mutu & Data -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'mutu-data' ? 'active' : '' ?>" href="<?= SITE_URL ?>/mutu-data.php">
                        Mutu &amp; Data
                    </a>
                </li>

                <!-- 7. Dokumen -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'dokumen' ? 'active' : '' ?>" href="<?= SITE_URL ?>/dokumen.php">
                        Dokumen
                    </a>
                </li>

                <!-- 8. Knowledge -->
                <li class="nav-item">
                    <a class="nav-link <?= $current_page === 'knowledge' ? 'active' : '' ?>" href="<?= SITE_URL ?>/knowledge.php">
                        Knowledge
                    </a>
                </li>

                <!-- 9. Kegiatan -->
                <li class="nav-item">
                    <a class="nav-link <?= in_array($current_page, ['berita', 'berita-detail']) ? 'active' : '' ?>" href="<?= SITE_URL ?>/berita.php">
                        Kegiatan
                    </a>
                </li>

                <!-- 10. Layanan (CTA Button) -->
                <li class="nav-item">
                    <a class="nav-link nav-cta <?= $current_page === 'layanan' ? 'active' : '' ?>" href="<?= SITE_URL ?>/layanan.php">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14" style="margin-right:4px;vertical-align:-1px;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                        </svg>
                        Layanan
                    </a>
                </li>

            </ul>
        </div>
    </div>
</nav>
