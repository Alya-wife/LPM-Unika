<footer id="main-footer">
    <div class="container">
        <div class="row g-5">
            <!-- Brand Column -->
            <div class="col-lg-4">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="brand-logo-wrap">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-2.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                        </svg>
                    </div>
                    <div>
                        <div class="footer-brand-title"><?= e(getPengaturan('site_title', 'LPM UNIKA')) ?></div>
                        <div class="footer-brand-sub"><?= e(getPengaturan('site_subtitle', 'Lembaga Penjaminan Mutu')) ?></div>
                    </div>
                </div>
                <p class="footer-desc"><?= e(getPengaturan('footer_desc', 'Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata berkomitmen untuk mewujudkan mutu pendidikan tinggi yang unggul, berkelanjutan, dan berdaya saing global.')) ?></p>
            </div>

            <!-- Menu Column -->
            <div class="col-sm-6 col-lg-2">
                <div class="footer-heading">Navigasi</div>
                <ul class="footer-links">
                    <li><a href="<?= SITE_URL ?>/">Beranda</a></li>
                    <li><a href="<?= SITE_URL ?>/profil.php">Profil LPM</a></li>
                    <li><a href="<?= SITE_URL ?>/spmi.php">SPMI</a></li>
                    <li><a href="<?= SITE_URL ?>/berita.php">Berita &amp; Kegiatan</a></li>
                    <li><a href="<?= SITE_URL ?>/kontak.php">Kontak</a></li>
                </ul>
            </div>

            <!-- SPMI Column -->
            <div class="col-sm-6 col-lg-2">
                <div class="footer-heading">Dokumen SPMI</div>
                <ul class="footer-links">
                    <li><a href="<?= SITE_URL ?>/spmi.php?kategori=Kebijakan">Kebijakan Mutu</a></li>
                    <li><a href="<?= SITE_URL ?>/spmi.php?kategori=Manual">Manual Mutu</a></li>
                    <li><a href="<?= SITE_URL ?>/spmi.php?kategori=Standar">Standar Mutu</a></li>
                    <li><a href="<?= SITE_URL ?>/spmi.php?kategori=Formulir">Formulir Mutu</a></li>
                </ul>
            </div>

            <!-- Kontak Column -->
            <div class="col-lg-4">
                <div class="footer-heading">Kontak</div>
                <ul class="footer-links" style="list-style:none;padding:0;margin:0;">
                    <!-- GPS Lokasi -->
                    <li class="d-flex align-items-start gap-2 mb-3">
                        <span style="color:#A78BFA;flex-shrink:0;margin-top:2px;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="18" height="18">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                            </svg>
                        </span>
                        <span style="line-height:1.6;font-size:0.85rem;color:rgba(255,255,255,0.75);">
                            <?= nl2br(e(getPengaturan('alamat', "Ruang Lembaga Penjaminan Mutu\nGedung Thomas Aquinas Lantai 5\nKampus Universitas Katolik Soegijapranata\nJalan Pawiyatan Luhur IV/1 Bendan Duwur Semarang 50234"))) ?>
                        </span>
                    </li>
                    <!-- Telepon -->
                    <?php $f_telp = getPengaturan('telepon', '024-8441555 Ext 1473'); ?>
                    <li class="d-flex align-items-center gap-2 mb-3">
                        <span style="color:#A78BFA;flex-shrink:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="18" height="18">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                            </svg>
                        </span>
                        <a href="tel:<?= preg_replace('/[^0-9+]/', '', $f_telp) ?>" style="font-size:0.85rem;color:rgba(255,255,255,0.85);text-decoration:none;">
                            <?= e($f_telp) ?>
                        </a>
                    </li>
                    <!-- Email -->
                    <?php $f_mail = getPengaturan('email', 'lpm@unika.ac.id'); ?>
                    <li class="d-flex align-items-center gap-2">
                        <span style="color:#A78BFA;flex-shrink:0;">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" width="18" height="18">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                            </svg>
                        </span>
                        <a href="mailto:<?= e($f_mail) ?>" style="font-size:0.85rem;color:rgba(255,255,255,0.85);text-decoration:none;">
                            <?= e($f_mail) ?>
                        </a>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Footer Bottom -->
        <div class="footer-bottom">
            <div class="footer-copyright">
                &copy; <?= date('Y') ?> LPM Universitas Katolik Soegijapranata (UNIKA). All rights reserved.
            </div>
            <div class="footer-social">
                <!-- Instagram -->
                <a href="#" class="social-btn" aria-label="Instagram">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                    </svg>
                </a>
                <!-- YouTube -->
                <a href="#" class="social-btn" aria-label="YouTube">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M23.498 6.186a3.016 3.016 0 0 0-2.122-2.136C19.505 3.545 12 3.545 12 3.545s-7.505 0-9.377.505A3.017 3.017 0 0 0 .502 6.186C0 8.07 0 12 0 12s0 3.93.502 5.814a3.016 3.016 0 0 0 2.122 2.136c1.871.505 9.376.505 9.376.505s7.505 0 9.377-.505a3.015 3.015 0 0 0 2.122-2.136C24 15.93 24 12 24 12s0-3.93-.502-5.814zM9.545 15.568V8.432L15.818 12l-6.273 3.568z"/>
                    </svg>
                </a>
                <!-- Facebook -->
                <a href="#" class="social-btn" aria-label="Facebook">
                    <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor">
                        <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
                    </svg>
                </a>
            </div>
        </div>
    </div>
</footer>

<!-- Universal PDF Viewer Modal -->
<?php require_once __DIR__ . '/pdf-modal.php'; ?>

<!-- Bootstrap JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<!-- Custom JS -->
<script src="<?= SITE_URL ?>/assets/js/main.js"></script>
<?= isset($extra_js) ? $extra_js : '' ?>

</body>
</html>
