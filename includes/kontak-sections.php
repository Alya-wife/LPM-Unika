<?php
/**
 * Modular Sections untuk Halaman Kontak
 * Terintegrasi dengan Visual Page Builder (advance-setting.php) dan kontak.php publik.
 */

function renderKontakSection($type, $block = [], $is_builder = false, $form_data = []) {
    $bg = !empty($block['bg_color']) ? "background-color: {$block['bg_color']} !important;" : "";
    $tc = !empty($block['text_color']) ? "color: {$block['text_color']} !important;" : "";

    $success = $form_data['success'] ?? '';
    $error   = $form_data['error'] ?? '';

    switch ($type) {
        case 'kontak_info':
            $alamat   = getPengaturan('alamat', 'Jl. Pawiyatan Luhur IV/1, Bendan Dhuwur, Semarang 50234, Jawa Tengah');
            $telp     = getPengaturan('telepon', '(024) 8441 010');
            $mail     = getPengaturan('email', 'lpm@unika.ac.id');
            $jam      = getPengaturan('jam_kerja', 'Senin – Jumat: 08.00 – 16.00 WIB');
            $map_url  = getPengaturan('maps_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.0927505866!2d110.41095357403498!3d-7.044023469178!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e708c6c8dbc7c2d%3A0x6e9c0e63f52dc05!2sSoegijapranata%20Catholic%20University!5e0!3m2!1sen!2sid!4v1700000000000!5m2!1sen!2sid');
            ?>
            <section class="py-5" style="background:#ffffff;border-bottom:1px solid var(--border);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="row g-5 align-items-center">
                        <div class="col-lg-6">
                            <span class="section-tag mb-2">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                                </svg>
                                <?= htmlspecialchars($block['badge'] ?? 'Lokasi & Kontak') ?>
                            </span>
                            <h2 class="section-title mb-3"><?= htmlspecialchars($block['title'] ?? 'Informasi Kontak Sekretariat LPM') ?></h2>
                            <p style="color:var(--text-muted);font-size:0.95rem;line-height:1.75;margin-bottom:1.75rem;">
                                <?= htmlspecialchars($block['subtitle'] ?? 'Kami dengan senang hati siap membantu Anda. Silakan hubungi kami melalui saluran berikut atau kunjungi langsung kantor LPM UNIKA.') ?>
                            </p>

                            <div class="d-flex flex-column gap-3 mb-4">
                                <div class="p-3" style="background:var(--bg-main);border-radius:var(--radius-sm);border-left:3px solid var(--navy);">
                                    <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.88rem;margin-bottom:0.25rem;">Alamat Kantor</div>
                                    <div style="font-size:0.85rem;color:var(--text-muted);"><?= nl2br(htmlspecialchars($alamat)) ?></div>
                                </div>
                                <div class="row g-2">
                                    <div class="col-sm-6">
                                        <div class="p-3" style="background:var(--bg-main);border-radius:var(--radius-sm);border-left:3px solid var(--purple);">
                                            <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.88rem;margin-bottom:0.25rem;">Email Resmi</div>
                                            <div style="font-size:0.85rem;color:var(--navy);font-weight:600;"><?= htmlspecialchars($mail) ?></div>
                                        </div>
                                    </div>
                                    <div class="col-sm-6">
                                        <div class="p-3" style="background:var(--bg-main);border-radius:var(--radius-sm);border-left:3px solid #1565C0;">
                                            <div style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:0.88rem;margin-bottom:0.25rem;">Jam Layanan</div>
                                            <div style="font-size:0.85rem;color:var(--text-muted);"><?= htmlspecialchars($jam) ?></div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-6">
                            <?php if ($map_url): ?>
                            <div style="border-radius:var(--radius-lg);overflow:hidden;border:1px solid var(--border);box-shadow:0 8px 25px rgba(0,0,0,0.06);">
                                <iframe src="<?= htmlspecialchars($map_url) ?>" width="100%" height="320" style="border:0;display:block;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" title="Peta Lokasi Kantor LPM"></iframe>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;

        case 'kontak_form':
            ?>
            <section class="py-5" style="background:var(--bg-main);<?= $bg ?><?= $tc ?>">
                <div class="container">
                    <div class="row justify-content-center">
                        <div class="col-lg-8">
                            <div class="card-lpm p-4 p-md-5" style="background:#ffffff;border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:0 4px 20px rgba(10,25,47,0.05);">
                                <div class="text-center mb-4">
                                    <span class="section-tag mb-2"><?= htmlspecialchars($block['badge'] ?? 'Sampaikan Aspirasi') ?></span>
                                    <h3 style="font-family:var(--font-heading);font-size:1.4rem;font-weight:800;color:var(--navy);margin-bottom:0.35rem;">
                                        <?= htmlspecialchars($block['title'] ?? 'Kirim Pesan & Kritik Saran') ?>
                                    </h3>
                                    <p style="font-size:0.875rem;color:var(--text-muted);margin:0;">
                                        <?= htmlspecialchars($block['subtitle'] ?? 'Isi formulir berikut dan tim kami akan merespons dalam 1–2 hari kerja.') ?>
                                    </p>
                                </div>

                                <?php if ($success): ?>
                                <div class="alert alert-success d-flex align-items-center gap-2 mb-4" style="border-radius:var(--radius-sm);font-size:0.9rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                    </svg>
                                    <div><?= htmlspecialchars($success) ?></div>
                                </div>
                                <?php endif; ?>

                                <?php if ($error): ?>
                                <div class="alert alert-danger d-flex align-items-center gap-2 mb-4" style="border-radius:var(--radius-sm);font-size:0.9rem;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                                    </svg>
                                    <div><?= htmlspecialchars($error) ?></div>
                                </div>
                                <?php endif; ?>

                                <form method="POST" action="kontak.php" id="form-kontak" novalidate>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label for="nama" class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--navy);">Nama Lengkap <span style="color:#C62828;">*</span></label>
                                            <input type="text" id="nama" name="nama" class="form-control" placeholder="Nama Anda" value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-md-6">
                                            <label for="email" class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--navy);">Alamat Email <span style="color:#C62828;">*</span></label>
                                            <input type="email" id="email" name="email" class="form-control" placeholder="email@domain.com" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>" required>
                                        </div>
                                        <div class="col-12">
                                            <label for="instansi" class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--navy);">Instansi / Program Studi</label>
                                            <input type="text" id="instansi" name="instansi" class="form-control" placeholder="Asal instansi / unit Anda (opsional)" value="<?= htmlspecialchars($_POST['instansi'] ?? '') ?>">
                                        </div>
                                        <div class="col-12">
                                            <label for="subjek" class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--navy);">Subjek Pesan <span style="color:#C62828;">*</span></label>
                                            <select id="subjek" name="subjek" class="form-select" required>
                                                <option value="">-- Pilih Subjek --</option>
                                                <option value="Pertanyaan Umum" <?= ($_POST['subjek'] ?? '') === 'Pertanyaan Umum' ? 'selected' : '' ?>>Pertanyaan Umum</option>
                                                <option value="Dokumen SPMI" <?= ($_POST['subjek'] ?? '') === 'Dokumen SPMI' ? 'selected' : '' ?>>Dokumen SPMI</option>
                                                <option value="Akreditasi" <?= ($_POST['subjek'] ?? '') === 'Akreditasi' ? 'selected' : '' ?>>Akreditasi</option>
                                                <option value="Audit Mutu Internal" <?= ($_POST['subjek'] ?? '') === 'Audit Mutu Internal' ? 'selected' : '' ?>>Audit Mutu Internal (AMI)</option>
                                                <option value="Kerjasama" <?= ($_POST['subjek'] ?? '') === 'Kerjasama' ? 'selected' : '' ?>>Kerjasama</option>
                                                <option value="Lainnya" <?= ($_POST['subjek'] ?? '') === 'Lainnya' ? 'selected' : '' ?>>Lainnya</option>
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label for="pesan" class="form-label" style="font-size:0.85rem;font-weight:600;color:var(--navy);">Pesan <span style="color:#C62828;">*</span></label>
                                            <textarea id="pesan" name="pesan" class="form-control" rows="5" placeholder="Tuliskan pertanyaan atau pesan Anda..." required><?= htmlspecialchars($_POST['pesan'] ?? '') ?></textarea>
                                        </div>
                                        <div class="col-12 text-end mt-4">
                                            <button type="submit" class="btn btn-primary px-4 py-2" style="background:var(--navy);border:none;font-weight:700;border-radius:50px;">
                                                Kirim Pesan &rarr;
                                            </button>
                                        </div>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </section>
            <?php
            break;
    }
}
