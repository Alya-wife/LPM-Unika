<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Kontak';
$meta_desc  = 'Hubungi Lembaga Penjaminan Mutu SCU – Alamat, nomor telepon, email, dan formulir kontak kami.';

// Handle form submission
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subjek  = trim($_POST['subjek'] ?? '');
    $pesan   = trim($_POST['pesan'] ?? '');

    if (!$nama || !$email || !$subjek || !$pesan) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // In production: send email via PHPMailer or similar
        // For now, just simulate success
        $success = 'Pesan Anda telah berhasil dikirim. Tim LPM akan menghubungi Anda segera.';
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Hubungi Kami
        </div>
        <h1 class="page-banner-title">Kontak LPM UNIKA</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Kontak</span>
        </div>
    </div>
</div>

<section class="py-5 py-md-6">
    <div class="container">
        <div class="row g-5">
            <!-- Informasi Kontak -->
            <div class="col-lg-5">
                <span class="section-tag">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                    </svg>
                    Lokasi & Kontak
                </span>
                <h2 class="section-title">Informasi Kontak</h2>
                <p class="section-desc mb-4">
                    Kami dengan senang hati siap membantu Anda. Silakan hubungi kami melalui saluran berikut atau kunjungi langsung kantor LPM UNIKA.
                </p>

                <!-- Alamat -->
                <div class="kontak-info-item">
                    <div class="kontak-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 10.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 10.5c0 7.142-7.5 11.25-7.5 11.25S4.5 17.642 4.5 10.5a7.5 7.5 0 1 1 15 0Z" />
                        </svg>
                    </div>
                    <div>
                        <div class="kontak-info-label">Alamat</div>
                        <div class="kontak-info-val"><?= nl2br(e(getPengaturan('alamat', 'Jl. Pawiyatan Luhur IV/1, Bendan Dhuwur, Semarang 50234, Jawa Tengah'))) ?></div>
                    </div>
                </div>

                <!-- Telepon -->
                <div class="kontak-info-item">
                    <div class="kontak-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 6.75c0 8.284 6.716 15 15 15h2.25a2.25 2.25 0 0 0 2.25-2.25v-1.372c0-.516-.351-.966-.852-1.091l-4.423-1.106c-.44-.11-.902.055-1.173.417l-.97 1.293c-.282.376-.769.542-1.21.38a12.035 12.035 0 0 1-7.143-7.143c-.162-.441.004-.928.38-1.21l1.293-.97c.363-.271.527-.734.417-1.173L6.963 3.102a1.125 1.125 0 0 0-1.091-.852H4.5A2.25 2.25 0 0 0 2.25 4.5v2.25Z" />
                        </svg>
                    </div>
                    <div>
                        <div class="kontak-info-label">Telepon</div>
                        <?php $telp = getPengaturan('telepon', '(024) 8441 010'); ?>
                        <div class="kontak-info-val"><a href="tel:<?= preg_replace('/[^0-9+]/', '', $telp) ?>" style="color:var(--navy);"><?= e($telp) ?></a></div>
                    </div>
                </div>

                <!-- Email -->
                <div class="kontak-info-item">
                    <div class="kontak-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
                        </svg>
                    </div>
                    <div>
                        <div class="kontak-info-label">Email</div>
                        <?php $mail = getPengaturan('email', 'lpm@unika.ac.id'); ?>
                        <div class="kontak-info-val"><a href="mailto:<?= e($mail) ?>" style="color:var(--navy);"><?= e($mail) ?></a></div>
                    </div>
                </div>

                <!-- Jam Kerja -->
                <div class="kontak-info-item">
                    <div class="kontak-icon">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                    </div>
                    <div>
                        <div class="kontak-info-label">Jam Operasional</div>
                        <div class="kontak-info-val"><?= e(getPengaturan('jam_kerja', 'Senin – Jumat: 08.00 – 16.00 WIB')) ?></div>
                    </div>
                </div>

                <!-- Google Maps Embed -->
                <?php
                $map_url = getPengaturan('maps_embed_url', 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3960.0927505866!2d110.41095357403498!3d-7.044023469178!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x2e708c6c8dbc7c2d%3A0x6e9c0e63f52dc05!2sSoegijapranata%20Catholic%20University!5e0!3m2!1sen!2sid!4v1700000000000!5m2!1sen!2sid');
                if ($map_url):
                ?>
                <div style="border-radius:var(--radius-md);overflow:hidden;border:1px solid var(--border);box-shadow:var(--shadow-sm);margin-top:1rem;">
                    <iframe
                        src="<?= e($map_url) ?>"
                        width="100%"
                        height="220"
                        style="border:0;display:block;"
                        allowfullscreen=""
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        title="Peta Lokasi Kantor LPM">
                    </iframe>
                </div>
                <?php endif; ?>
            </div>

            <!-- Form Kontak -->
            <div class="col-lg-7">
                <div class="form-lpm">
                    <div style="margin-bottom:1.75rem;">
                        <h2 style="font-family:var(--font-heading);font-size:1.4rem;font-weight:700;color:var(--navy);margin-bottom:0.35rem;">Kirim Pesan</h2>
                        <p style="font-size:0.875rem;color:var(--text-muted);">Isi formulir berikut dan tim kami akan merespons dalam 1–2 hari kerja.</p>
                    </div>

                    <?php if ($success): ?>
                    <div class="alert-lpm alert-success">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <?= e($success) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                    <div class="alert-lpm alert-danger">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <?= e($error) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST" action="kontak.php" id="form-kontak" novalidate>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="nama" class="form-label">Nama Lengkap <span style="color:#C62828;">*</span></label>
                                <input type="text" id="nama" name="nama" class="form-control" placeholder="Nama Anda" value="<?= e($_POST['nama'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Alamat Email <span style="color:#C62828;">*</span></label>
                                <input type="email" id="email" name="email" class="form-control" placeholder="email@domain.com" value="<?= e($_POST['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-12">
                                <label for="instansi" class="form-label">Instansi / Universitas</label>
                                <input type="text" id="instansi" name="instansi" class="form-control" placeholder="Asal instansi Anda (opsional)" value="<?= e($_POST['instansi'] ?? '') ?>">
                            </div>
                            <div class="col-12">
                                <label for="subjek" class="form-label">Subjek Pesan <span style="color:#C62828;">*</span></label>
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
                                <label for="pesan" class="form-label">Pesan <span style="color:#C62828;">*</span></label>
                                <textarea id="pesan" name="pesan" class="form-control" rows="6" placeholder="Tulis pesan Anda di sini..." required><?= e($_POST['pesan'] ?? '') ?></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn-submit" id="btn-kirim">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                    </svg>
                                    Kirim Pesan
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
