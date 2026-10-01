<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Pengantar & Definisi AMI';
$db = getDB();

$flash = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $fields = [
        'ami_banner_subtitle',
        'ami_intro_badge',
        'ami_intro_title',
        'ami_intro_text',
        'ami_intro_desc2',
        'ami_prinsip_judul',
        'ami_prinsip_text',
        'ami_sasaran_judul',
        'ami_sasaran_text',
        'ami_box_title',
        'ami_box_subtitle',
        'ami_box_desc'
    ];

    foreach ($fields as $f) {
        $val = trim($_POST[$f] ?? '');
        setPengaturan($f, $val);
    }

    $_SESSION['flash'] = 'Konten Pengantar & Definisi AMI berhasil diperbarui.';
    redirect(SITE_URL . '/admin/ami-pengantar.php');
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

// Default values
$banner_sub    = getPengaturan('ami_banner_subtitle', 'Audit Mutu Internal (AMI) LPM UNIKA: Pedoman, Instrumen, Auditor, Jadwal Siklus, dan Hasil Audit Mutu Internal Universitas Katolik Soegijapranata.');
$intro_badge   = getPengaturan('ami_intro_badge', 'Evaluasi Mutu');
$intro_title   = getPengaturan('ami_intro_title', 'Tentang Audit Mutu Internal (AMI)');
$intro_text    = getPengaturan('ami_intro_text', 'Audit Mutu Internal (AMI) Universitas Katolik Soegijapranata merupakan proses pengujian yang sistematik, mandiri, dan terdokumentasi untuk memastikan bahwa pelaksanaan penjaminan mutu di seluruh Program Studi dan Unit Kerja telah sesuai dengan Standar SPMI UNIKA.');
$intro_desc2   = getPengaturan('ami_intro_desc2', 'AMI bukan kegiatan mencari kesalahan (audit kepatuhan semata), melainkan proses kolaboratif untuk mengidentifikasi potensi peningkatan mutu (opportunity for improvement), mitigasi risiko akademik, dan kesiapan akreditasi eksternal.');
$prinsip_judul = getPengaturan('ami_prinsip_judul', 'Prinsip Kerja AMI');
$prinsip_text  = getPengaturan('ami_prinsip_text', 'Objektif, profesional, independen, berbasis bukti (evidence-based), dan berorientasi solusi.');
$sasaran_judul = getPengaturan('ami_sasaran_judul', 'Sasaran Audit');
$sasaran_text  = getPengaturan('ami_sasaran_text', 'Seluruh Fakultas, Program Studi, Lembaga Penelitian, Pengabdian, dan Unit Pelaksana Teknis.');
$box_title     = getPengaturan('ami_box_title', 'Siklus AMI Aktif');
$box_subtitle  = getPengaturan('ami_box_subtitle', 'Periode Tahun Akademik Berjalan');
$box_desc      = getPengaturan('ami_box_desc', 'Unit kerja yang memerlukan panduan pengisian instrumen audit atau penjadwalan visitasi auditor dapat menghubungi Sekretariat LPM.');

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <span style="color:var(--navy);"><?= $admin_page_title ?></span>
        </div>

        <?php if ($flash): ?>
        <div class="alert-lpm alert-success mb-4">
            <i class="bi bi-check-circle-fill me-2"></i><?= e($flash) ?>
        </div>
        <?php endif; ?>

        <div class="admin-table-wrap p-4 p-md-5">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--navy);"><i class="bi bi-card-text me-2 text-primary"></i>Kelola Pengantar &amp; Definisi AMI</h4>
                    <p class="text-muted small mb-0">
                        Atur narasi definisi, filosofi, prinsip kerja, sasaran audit, dan teks banner yang tampil di halaman publik <strong>Pengantar AMI</strong>.
                    </p>
                </div>
                <a href="<?= SITE_URL ?>/ami.php" target="_blank" class="btn btn-sm btn-outline-primary">
                    <i class="bi bi-eye me-1"></i> Lihat Halaman Publik
                </a>
            </div>

            <form method="post">
                <div class="row g-4">
                    <!-- 1. Banner Header -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-image me-2 text-primary"></i>Teks Banner Halaman Pengantar</h6>
                            <div class="mb-0">
                                <label class="form-label fw-bold small">Subjudul / Deskripsi Banner</label>
                                <textarea name="ami_banner_subtitle" rows="2" class="form-control"><?= e($banner_sub) ?></textarea>
                                <div class="form-text">Teks penjelasan ringkas di bawah judul besar banner halaman Pengantar AMI.</div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Definisi Utama AMI -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-info-circle me-2 text-primary"></i>Definisi Utama AMI</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Label Tag / Badge</label>
                                    <input type="text" name="ami_intro_badge" class="form-control" value="<?= e($intro_badge) ?>">
                                </div>
                                <div class="col-md-8">
                                    <label class="form-label fw-bold small">Judul Utama Seksi</label>
                                    <input type="text" name="ami_intro_title" class="form-control" value="<?= e($intro_title) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small">Paragraf 1: Definisi Resmi AMI <span class="text-danger">*</span></label>
                                    <textarea name="ami_intro_text" rows="4" class="form-control" required><?= e($intro_text) ?></textarea>
                                    <div class="form-text">Pengertian konseptual dan dasar pelaksanaan audit mutu internal di UNIKA Soegijapranata.</div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small">Paragraf 2: Filosofi &amp; Pendekatan Kolaboratif</label>
                                    <textarea name="ami_intro_desc2" rows="3" class="form-control"><?= e($intro_desc2) ?></textarea>
                                    <div class="form-text">Penegasan bahwa AMI bukan mencari kesalahan, melainkan mitigasi risiko &amp; continuous improvement.</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 3. Prinsip Kerja & Sasaran Audit -->
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-shield-check me-2 text-purple"></i>Prinsip Kerja AMI</h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Judul Kotak</label>
                                <input type="text" name="ami_prinsip_judul" class="form-control" value="<?= e($prinsip_judul) ?>">
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold small">Isi Prinsip Kerja</label>
                                <textarea name="ami_prinsip_text" rows="3" class="form-control"><?= e($prinsip_text) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-bullseye me-2 text-info"></i>Sasaran Audit</h6>
                            <div class="mb-3">
                                <label class="form-label fw-bold small">Judul Kotak</label>
                                <input type="text" name="ami_sasaran_judul" class="form-control" value="<?= e($sasaran_judul) ?>">
                            </div>
                            <div class="mb-0">
                                <label class="form-label fw-bold small">Isi Sasaran Audit</label>
                                <textarea name="ami_sasaran_text" rows="3" class="form-control"><?= e($sasaran_text) ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Kotak Aksi / Banner Samping -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-box me-2 text-warning"></i>Kotak Informasi Samping</h6>
                            <div class="row g-3">
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Judul Kotak</label>
                                    <input type="text" name="ami_box_title" class="form-control" value="<?= e($box_title) ?>">
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label fw-bold small">Subjudul Kotak</label>
                                    <input type="text" name="ami_box_subtitle" class="form-control" value="<?= e($box_subtitle) ?>">
                                </div>
                                <div class="col-12">
                                    <label class="form-label fw-bold small">Teks Deskripsi Pendukung</label>
                                    <textarea name="ami_box_desc" rows="2" class="form-control"><?= e($box_desc) ?></textarea>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan Pengantar AMI
                    </button>
                    <a href="dashboard.php" class="btn btn-light px-3 py-2">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
