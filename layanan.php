<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Layanan & Konsultasi Mutu';
$meta_desc  = 'Layanan Lembaga Penjaminan Mutu SCU: Konsultasi Mutu, Pendampingan Akreditasi, Pelatihan, dan Formulir Aspirasi/Feedback.';

$db = getDB();
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama          = trim($_POST['nama'] ?? '');
    $email         = trim($_POST['email'] ?? '');
    $jenis_layanan = trim($_POST['jenis_layanan'] ?? '');
    $instansi      = trim($_POST['instansi'] ?? '');
    $pesan         = trim($_POST['pesan'] ?? '');

    if (!$nama || !$email || !$pesan) {
        $error = 'Nama, email, dan pesan wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid.';
    } else {
        $stmt = $db->prepare("INSERT INTO feedback (nama, email, jenis_layanan, instansi, pesan) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$nama, $email, $jenis_layanan, $instansi, $pesan]);
        $success = 'Terima kasih! Pesan dan pengajuan Anda telah berhasil dikirim ke tim LPM UNIKA. Kami akan segera menindaklanjutinya.';
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
            Pelayanan Publik
        </div>
        <h1 class="page-banner-title">Layanan &amp; Konsultasi Mutu</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Layanan</span>
        </div>
    </div>
</div>

<!-- Layanan Cards -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <span class="section-tag">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" width="14" height="14">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09ZM18.259 8.715 18 9.75l-.259-1.035a3.375 3.375 0 0 0-2.455-2.456L14.25 6l1.036-.259a3.375 3.375 0 0 0 2.455-2.456L18 2.25l.259 1.035a3.375 3.375 0 0 0 2.456 2.456L21.75 6l-1.035.259a3.375 3.375 0 0 0-2.456 2.456ZM16.894 20.567 16.5 21.75l-.394-1.183a2.25 2.25 0 0 0-1.423-1.423L13.5 18.75l1.183-.394a2.25 2.25 0 0 0 1.423-1.423l.394-1.183.394 1.183a2.25 2.25 0 0 0 1.423 1.423l1.183.394-1.183.394a2.25 2.25 0 0 0-1.423 1.423Z" />
                </svg>
                Fasilitas
            </span>
            <h2 class="section-title">Program Layanan LPM UNIKA</h2>
            <p class="section-desc mx-auto">Kami mendampingi fakultas, program studi, dan unit kerja dalam mewujudkan standar mutu unggul.</p>
        </div>

        <div class="row g-4">
            <div class="col-md-4">
                <div class="card-lpm p-4 text-center h-100">
                    <div style="width:60px;height:60px;border-radius:50%;background:rgba(10,25,47,0.08);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--navy)" width="30" height="30">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                        </svg>
                    </div>
                    <h4 style="font-family:var(--font-heading);font-size:1.15rem;font-weight:700;color:var(--navy);">Konsultasi Mutu</h4>
                    <p style="font-size:0.875rem;color:var(--text-muted);line-height:1.7;">
                        Layanan konsultasi implementasi siklus PPEPP, perumusan standar mutu unit, dan penyusunan instrumen evaluasi kinerja berkala.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-lpm p-4 text-center h-100">
                    <div style="width:60px;height:60px;border-radius:50%;background:rgba(106,27,154,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--purple)" width="30" height="30">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M16.5 18.75h-9m9 0a3 3 0 0 1 3 3h-15a3 3 0 0 1 3-3m9 0v-3.375c0-.621-.503-1.125-1.125-1.125h-.871M7.5 18.75v-3.375c0-.621.504-1.125 1.125-1.125h.872m5.007 0H9.497m5.007 0a7.454 7.454 0 0 1-.982-3.172M9.497 14.25a7.454 7.454 0 0 0 .982-3.172M12 3a4.5 4.5 0 0 0-4.5 4.5v1.875a3.375 3.375 0 0 0 3.375 3.375h2.25a3.375 3.375 0 0 0 3.375-3.375V7.5A4.5 4.5 0 0 0 12 3Z" />
                        </svg>
                    </div>
                    <h4 style="font-family:var(--font-heading);font-size:1.15rem;font-weight:700;color:var(--navy);">Pendampingan Akreditasi</h4>
                    <p style="font-size:0.875rem;color:var(--text-muted);line-height:1.7;">
                        Bimbingan intensif persiapan borang akreditasi, simulasi asesmen lapangan, serta pemenuhan syarat unggul BAN-PT dan 7 LAM.
                    </p>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card-lpm p-4 text-center h-100">
                    <div style="width:60px;height:60px;border-radius:50%;background:rgba(21,101,192,0.1);display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="#1565C0" width="30" height="30">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-2.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                        </svg>
                    </div>
                    <h4 style="font-family:var(--font-heading);font-size:1.15rem;font-weight:700;color:var(--navy);">Pelatihan &amp; Workshop</h4>
                    <p style="font-size:0.875rem;color:var(--text-muted);line-height:1.7;">
                        Pelatihan auditor mutu internal, lokakarya kurikulum OBE (Outcome-Based Education), dan sosialisasi kebijakan mutu terbaru.
                    </p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Feedback & Consultation Form Section -->
<section class="py-5" style="background:var(--bg-white);">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-8">
                <div class="card-lpm p-4 p-md-5" style="border:1px solid var(--border);border-radius:var(--radius-lg);box-shadow:var(--shadow-md);">
                    <div class="text-center mb-4">
                        <span class="section-tag">Formulir Interaktif</span>
                        <h3 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);">
                            Pengajuan Layanan &amp; Aspirasi Mutu
                        </h3>
                        <p style="font-size:0.875rem;color:var(--text-muted);">
                            Sampaikan kebutuhan konsultasi, pendampingan akreditasi, permohonan narasumber, atau kritik dan saran kepada LPM UNIKA.
                        </p>
                    </div>

                    <?php if ($success): ?>
                    <div class="alert-lpm alert-success mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                        </svg>
                        <?= e($success) ?>
                    </div>
                    <?php endif; ?>

                    <?php if ($error): ?>
                    <div class="alert-lpm alert-danger mb-4">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="20" height="20">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
                        </svg>
                        <?= e($error) ?>
                    </div>
                    <?php endif; ?>

                    <form method="POST">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label" style="font-family:var(--font-heading);font-weight:600;font-size:0.85rem;color:var(--navy);">
                                    Nama Lengkap <span style="color:#C62828;">*</span>
                                </label>
                                <input type="text" name="nama" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Nama Anda" value="<?= e($_POST['nama'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-family:var(--font-heading);font-weight:600;font-size:0.85rem;color:var(--navy);">
                                    Alamat Email Resmi <span style="color:#C62828;">*</span>
                                </label>
                                <input type="email" name="email" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="email@unika.ac.id" value="<?= e($_POST['email'] ?? '') ?>" required>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label" style="font-family:var(--font-heading);font-weight:600;font-size:0.85rem;color:var(--navy);">
                                    Jenis Layanan / Pengajuan
                                </label>
                                <select name="jenis_layanan" class="form-select" style="border:1.5px solid var(--border);padding:0.7rem 1rem;">
                                    <option value="Konsultasi Mutu">Konsultasi Mutu &amp; SPMI</option>
                                    <option value="Pendampingan Akreditasi">Pendampingan Akreditasi Prodi</option>
                                    <option value="Pelatihan Mutu">Permohonan Pelatihan / Workshop</option>
                                    <option value="Permintaan Data Mutu">Permintaan Data &amp; Regulasi</option>
                                    <option value="Kritik & Saran / Feedback">Kritik, Saran &amp; Feedback</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label" style="font-family:var(--font-heading);font-weight:600;font-size:0.85rem;color:var(--navy);">
                                    Fakultas / Program Studi / Unit
                                </label>
                                <input type="text" name="instansi" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem;" placeholder="Contoh: Fakultas Ilmu Komputer" value="<?= e($_POST['instansi'] ?? '') ?>">
                            </div>

                            <div class="col-12">
                                <label class="form-label" style="font-family:var(--font-heading);font-weight:600;font-size:0.85rem;color:var(--navy);">
                                    Isi Pesan / Rincian Kebutuhan <span style="color:#C62828;">*</span>
                                </label>
                                <textarea name="pesan" rows="5" class="form-control" style="border:1.5px solid var(--border);padding:0.75rem 1rem;line-height:1.7;" placeholder="Jelaskan kebutuhan konsultasi atau sampaikan aspirasi mutu Anda..." required><?= e($_POST['pesan'] ?? '') ?></textarea>
                            </div>

                            <div class="col-12 text-end mt-3">
                                <button type="submit" class="btn-hero-primary" style="padding:0.8rem 2.2rem;border:none;">
                                    Kirim Pengajuan Layanan
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
