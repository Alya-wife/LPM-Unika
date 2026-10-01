<footer id="main-footer">
    <div class="container">
        <div class="row g-5">
            <!-- Brand Column -->
            <div class="col-lg-6">
                <div class="d-flex align-items-center gap-3 mb-3">
                    <div class="brand-logo-wrap">
                        <img src="<?= SITE_URL ?>/assets/images/logo-unika.png" alt="Logo UNIKA Soegijapranata" class="brand-logo-img">
                    </div>
                    <div>
                        <div class="footer-brand-title"><?= e(getPengaturan('site_title', 'LPM UNIKA')) ?></div>
                        <div class="footer-brand-sub"><?= e(getPengaturan('site_subtitle', 'Lembaga Penjaminan Mutu')) ?></div>
                    </div>
                </div>
                <p class="footer-desc" style="max-width:520px;line-height:1.75;"><?= e(getPengaturan('footer_desc', 'Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata berkomitmen untuk mewujudkan mutu pendidikan tinggi yang unggul, berkelanjutan, dan berdaya saing global.')) ?></p>
                <div class="d-inline-flex align-items-center gap-2 px-3 py-1 mt-2" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);border-radius:20px;font-size:0.75rem;color:#FFD54F;">
                    <i class="bi bi-patch-check-fill text-warning"></i>
                    <span>Terakreditasi <strong>UNGGUL</strong> &bull; BAN-PT</span>
                </div>
            </div>

            <!-- Kontak Column -->
            <div class="col-lg-6">
                <div class="footer-heading">Informasi Kontak &amp; Sekretariat</div>
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
        <div class="footer-bottom d-flex flex-wrap justify-content-between align-items-center gap-2">
            <div class="footer-copyright">
                &copy; <?= date('Y') ?> LPM Universitas Katolik Soegijapranata (UNIKA). All rights reserved.
            </div>
            <div class="footer-copyright" style="font-size:0.75rem;color:rgba(255,255,255,0.45);">
                Lembaga Penjaminan Mutu &bull; Universitas Katolik Soegijapranata
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
