<?php
require_once __DIR__ . '/config/database.php';
$db = getDB();

$page_title = 'Survei Kepuasan Pelayanan LPM';
$meta_desc  = 'Formulir Survei Kepuasan Pelayanan Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata.';

// Handle State
$submitted     = isset($_GET['submitted']) && $_GET['submitted'] === '1';
$error_message = '';

// 1. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_survei') {
    $nama_pengisi        = trim($_POST['nama_pengisi'] ?? '');
    $email_pengisi       = trim($_POST['email_pengisi'] ?? '');
    $jenis_kelamin       = trim($_POST['jenis_kelamin'] ?? '');
    $pendidikan_terakhir = trim($_POST['pendidikan_terakhir'] ?? '');
    $nama_institusi      = trim($_POST['nama_institusi'] ?? '');
    $kategori_layanan    = trim($_POST['kategori_layanan'] ?? 'Pelayanan LPM');
    $status_responden    = trim($_POST['status_responden'] ?? '');
    $saran_masukan       = trim($_POST['saran_masukan'] ?? '');

    // Validation (email wajib dan harus email resmi instansi untuk mencegah spam)
    if (empty($nama_pengisi) || empty($email_pengisi) || empty($nama_institusi)) {
        $error_message = 'Mohon melengkapi Nama, Alamat Email, dan Instansi/Lembaga Anda.';
    } elseif (!filter_var($email_pengisi, FILTER_VALIDATE_EMAIL)) {
        $error_message = 'Format alamat email tidak valid.';
    } elseif (!isInstitutionalEmail($email_pengisi)) {
        $error_message = 'Mohon gunakan alamat email resmi instansi/lembaga Anda. Email pribadi publik tidak dapat digunakan.';
    } elseif (empty($jenis_kelamin)) {
        $error_message = 'Mohon memilih Jenis Kelamin.';
    } elseif (empty($pendidikan_terakhir)) {
        $error_message = 'Mohon memilih Pendidikan Terakhir.';
    } elseif (empty($status_responden)) {
        $error_message = 'Mohon memilih Status Responden.';
    } else {
        // Fetch active questions
        $questions = $db->query("SELECT * FROM kunjungan_kuesioner_pertanyaan WHERE is_aktif = 1 ORDER BY urutan ASC, id ASC")->fetchAll();
        $skala_scores = [];
        $answers = [];
        $missing_question = false;

        foreach ($questions as $q) {
            $qid = $q['id'];
            if ($q['tipe'] === 'skala') {
                $score = isset($_POST['pertanyaan_' . $qid]) ? (int)$_POST['pertanyaan_' . $qid] : null;
                if ($score && $score >= 1 && $score <= 5) {
                    $skala_scores[] = $score;
                    $answers[] = [
                        'pertanyaan_id' => $qid,
                        'nilai_skor'    => $score,
                        'jawaban_teks'  => null
                    ];
                } else {
                    $missing_question = true;
                }
            }
        }

        if ($missing_question) {
            $error_message = 'Mohon memberikan penilaian untuk seluruh 8 butir pertanyaan survei kepuasan yang tersedia.';
        } else {
            $tanggal_kunjungan = date('Y-m-d');
            $avg_score = count($skala_scores) > 0 ? (array_sum($skala_scores) / count($skala_scores)) : null;

            try {
                // Insert main response (umur null, token_id null)
                $ins_stmt = $db->prepare("INSERT INTO kunjungan_feedback_respon 
                    (token_id, kunjungan_id, nama_institusi, tanggal_kunjungan, nama_pengisi, email_pengisi, jenis_kelamin, umur, pendidikan_terakhir, kategori_layanan, status_responden, saran_masukan, rata_rata_skor) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $ins_stmt->execute([
                    null,
                    null,
                    $nama_institusi,
                    $tanggal_kunjungan,
                    $nama_pengisi,
                    $email_pengisi,
                    $jenis_kelamin,
                    null,
                    $pendidikan_terakhir,
                    $kategori_layanan,
                    $status_responden,
                    $saran_masukan,
                    $avg_score
                ]);
                $respon_id = $db->lastInsertId();

                // Insert answers
                $ins_ans = $db->prepare("INSERT INTO kunjungan_feedback_jawaban (respon_id, pertanyaan_id, nilai_skor, jawaban_teks) VALUES (?, ?, ?, ?)");
                foreach ($answers as $ans) {
                    $ins_ans->execute([$respon_id, $ans['pertanyaan_id'], $ans['nilai_skor'], $ans['jawaban_teks']]);
                }

                // If saran_masukan is provided, also insert into feedback table so it's accessible in message inbox
                if (!empty($saran_masukan)) {
                    try {
                        $fb_stmt = $db->prepare("INSERT INTO feedback (nama, email, jenis_layanan, instansi, pesan) VALUES (?, ?, ?, ?, ?)");
                        $fb_stmt->execute([$nama_pengisi, $email_pengisi, $kategori_layanan, $nama_institusi, $saran_masukan]);
                    } catch (Exception $e) {}
                }

                redirect(SITE_URL . '/survei-kepuasan.php?submitted=1');
            } catch (Exception $e) {
                $error_message = 'Terjadi kesalahan sistem saat menyimpan jawaban: ' . $e->getMessage();
            }
        }
    }
}

// Fetch active questions for display
$questions = $db->query("SELECT * FROM kunjungan_kuesioner_pertanyaan WHERE is_aktif = 1 ORDER BY urutan ASC, id ASC")->fetchAll();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
/* Modern Survey Styling */
:root {
    --survei-bg: #F8FAFC;
    --survei-card: #FFFFFF;
    --survei-navy: #0A192F;
    --survei-blue: #0284C7;
    --survei-amber: #D97706;
    --survei-emerald: #059669;
}

.survei-container {
    max-width: 860px;
    margin: 0 auto;
}

.survei-header-card {
    background: linear-gradient(135deg, #0A192F 0%, #1E3A8A 60%, #0369A1 100%);
    border-radius: 20px;
    padding: 3rem 2rem 2.5rem;
    color: #ffffff;
    text-align: center;
    box-shadow: 0 12px 30px rgba(10, 25, 47, 0.12);
    position: relative;
    overflow: hidden;
    margin-bottom: 2rem;
}

.survei-header-card::before {
    content: '';
    position: absolute;
    top: -50px;
    right: -50px;
    width: 180px;
    height: 180px;
    background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, rgba(255,255,255,0) 70%);
    border-radius: 50%;
}

.survei-logo-wrap {
    width: 90px;
    height: 90px;
    background: #ffffff;
    border-radius: 50%;
    padding: 10px;
    margin: 0 auto 1.25rem;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 6px 16px rgba(0,0,0,0.15);
}

.survei-logo-wrap img {
    max-width: 100%;
    max-height: 100%;
    object-fit: contain;
}

.survei-main-title {
    font-family: var(--font-heading);
    font-size: 1.65rem;
    font-weight: 800;
    letter-spacing: -0.5px;
    margin-bottom: 0.35rem;
    text-transform: uppercase;
}

.survei-main-subtitle {
    font-size: 1.15rem;
    font-weight: 600;
    opacity: 0.95;
    margin-bottom: 0.2rem;
}

.survei-main-sub2 {
    font-size: 0.95rem;
    opacity: 0.85;
    letter-spacing: 0.5px;
    margin-bottom: 1.25rem;
}

.survei-intro-text {
    max-width: 650px;
    margin: 0 auto;
    font-size: 0.92rem;
    line-height: 1.65;
    color: rgba(255, 255, 255, 0.9);
    background: rgba(255, 255, 255, 0.08);
    padding: 0.85rem 1.25rem;
    border-radius: 12px;
    border: 1px solid rgba(255, 255, 255, 0.15);
}

.survei-card {
    background: #ffffff;
    border-radius: 16px;
    border: 1px solid #E2E8F0;
    box-shadow: 0 4px 16px rgba(10, 25, 47, 0.04);
    padding: 2rem;
    margin-bottom: 1.75rem;
    transition: all 0.25s ease;
}

.survei-section-badge {
    display: inline-flex;
    align-items: center;
    gap: 8px;
    padding: 0.35rem 0.85rem;
    background: #EFF6FF;
    color: #1D4ED8;
    border-radius: 20px;
    font-size: 0.8rem;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    margin-bottom: 1.25rem;
}

.survei-form-label {
    font-weight: 700;
    font-size: 0.9rem;
    color: #1E293B;
    margin-bottom: 0.4rem;
}

.survei-form-label .req {
    color: #EF4444;
    margin-left: 2px;
}

.survei-form-control {
    border: 1.5px solid #CBD5E1;
    border-radius: 10px;
    padding: 0.65rem 0.95rem;
    font-size: 0.92rem;
    color: #0F172A;
    transition: all 0.2s ease;
}

.survei-form-control:focus {
    border-color: #0284C7;
    box-shadow: 0 0 0 3px rgba(2, 132, 199, 0.15);
    outline: none;
}

/* Question Likert Item */
.pertanyaan-item {
    padding: 1.4rem 0;
    border-bottom: 1px solid #F1F5F9;
}

.pertanyaan-item:last-child {
    border-bottom: none;
    padding-bottom: 0;
}

.pertanyaan-title {
    font-size: 1rem;
    font-weight: 700;
    color: #0F172A;
    line-height: 1.5;
    margin-bottom: 1rem;
    display: flex;
    align-items: flex-start;
    gap: 10px;
}

.pertanyaan-num {
    background: #0A192F;
    color: #ffffff;
    min-width: 28px;
    height: 28px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.85rem;
    font-weight: 800;
    margin-top: 1px;
}

/* Likert Scales Container */
.likert-scale-grid {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}

@media (max-width: 680px) {
    .likert-scale-grid {
        grid-template-columns: repeat(5, 1fr);
        gap: 6px;
    }
}

.likert-option-label {
    display: block;
    cursor: pointer;
    margin: 0;
    user-select: none;
}

.likert-option-label input[type="radio"] {
    position: absolute;
    opacity: 0;
    pointer-events: none;
}

.likert-btn-box {
    border: 2px solid #E2E8F0;
    background: #F8FAFC;
    border-radius: 14px;
    padding: 12px 6px;
    text-align: center;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    min-height: 100px;
}

.likert-emoji {
    font-size: 2.1rem;
    line-height: 1;
    margin-bottom: 8px;
    transition: transform 0.2s ease;
    filter: drop-shadow(0 2px 4px rgba(0,0,0,0.06));
}

.likert-text {
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
    line-height: 1.25;
}

/* Hover & Active States */
.likert-option-label:hover .likert-btn-box {
    transform: translateY(-2px);
    box-shadow: 0 4px 10px rgba(0,0,0,0.06);
}

.likert-option-label:hover .likert-emoji {
    transform: scale(1.15);
}

/* Selected States per Score */
.likert-option-label input[type="radio"]:checked + .likert-btn-box.skala-5 {
    border-color: #10B981;
    background: #ECFDF5;
    box-shadow: 0 4px 14px rgba(16, 185, 129, 0.25);
}
.likert-option-label input[type="radio"]:checked + .likert-btn-box.skala-5 .likert-text {
    color: #065F46;
    font-weight: 800;
}

.likert-option-label input[type="radio"]:checked + .likert-btn-box.skala-4 {
    border-color: #84CC16;
    background: #F7FEE7;
    box-shadow: 0 4px 14px rgba(132, 204, 22, 0.25);
}
.likert-option-label input[type="radio"]:checked + .likert-btn-box.skala-4 .likert-text {
    color: #3F6212;
    font-weight: 800;
}

.likert-option-label input[type="radio"]:checked + .likert-btn-box.skala-3 {
    border-color: #F59E0B;
    background: #FFFBEB;
    box-shadow: 0 4px 14px rgba(245, 158, 11, 0.25);
}
.likert-option-label input[type="radio"]:checked + .likert-btn-box.skala-3 .likert-text {
    color: #92400E;
    font-weight: 800;
}

.likert-option-label input[type="radio"]:checked + .likert-btn-box.skala-2 {
    border-color: #F97316;
    background: #FFF7ED;
    box-shadow: 0 4px 14px rgba(249, 115, 22, 0.25);
}
.likert-option-label input[type="radio"]:checked + .likert-btn-box.skala-2 .likert-text {
    color: #9A3412;
    font-weight: 800;
}

.likert-option-label input[type="radio"]:checked + .likert-btn-box.skala-1 {
    border-color: #EF4444;
    background: #FEF2F2;
    box-shadow: 0 4px 14px rgba(239, 68, 68, 0.25);
}
.likert-option-label input[type="radio"]:checked + .likert-btn-box.skala-1 .likert-text {
    color: #991B1B;
    font-weight: 800;
}

.likert-option-label input[type="radio"]:checked + .likert-btn-box .likert-emoji {
    transform: scale(1.22);
}

.btn-simpan-jawaban {
    background: linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);
    color: #ffffff;
    font-weight: 800;
    font-size: 1.1rem;
    padding: 0.95rem 2.5rem;
    border-radius: 50px;
    border: none;
    box-shadow: 0 8px 20px rgba(10, 25, 47, 0.25);
    transition: all 0.25s ease;
    display: inline-flex;
    align-items: center;
    gap: 10px;
}

.btn-simpan-jawaban:hover {
    background: linear-gradient(135deg, #1E3A8A 0%, #0284C7 100%);
    color: #ffffff;
    transform: translateY(-2px);
    box-shadow: 0 12px 24px rgba(2, 132, 199, 0.35);
}

/* Radio Custom Pill */
.radio-custom-pill {
    display: flex;
    gap: 10px;
    flex-wrap: wrap;
}

.radio-custom-pill label {
    flex: 1;
    min-width: 140px;
    margin: 0;
    cursor: pointer;
}

.radio-custom-pill input[type="radio"] {
    display: none;
}

.radio-custom-pill .pill-box {
    border: 1.5px solid #CBD5E1;
    background: #F8FAFC;
    padding: 0.65rem 1rem;
    border-radius: 10px;
    text-align: center;
    font-size: 0.9rem;
    font-weight: 600;
    color: #334155;
    transition: all 0.2s ease;
}

.radio-custom-pill input[type="radio"]:checked + .pill-box {
    border-color: #0284C7;
    background: #EFF6FF;
    color: #0369A1;
    font-weight: 700;
}
</style>

<div class="py-5" style="background:var(--survei-bg);min-height:80vh;">
    <div class="container">
        <div class="survei-container">

            <!-- Breadcrumb Navigation -->
            <nav aria-label="breadcrumb" class="mb-4">
                <ol class="breadcrumb" style="font-size:0.875rem;">
                    <li class="breadcrumb-item"><a href="<?= SITE_URL ?>/" class="text-decoration-none text-muted"><i class="bi bi-house-door me-1"></i>Beranda</a></li>
                    <li class="breadcrumb-item text-muted">Layanan</li>
                    <li class="breadcrumb-item active text-primary fw-semibold" aria-current="page">Survei Kepuasan Layanan LPM</li>
                </ol>
            </nav>

            <?php if ($submitted): ?>
            <!-- Thank You / Success Card -->
            <div class="survei-card text-center py-5 px-4" style="border-top:6px solid #10B981;">
                <div style="width:84px;height:84px;background:#ECFDF5;border:2px solid #A7F3D0;border-radius:50%;margin:0 auto 1.5rem;display:flex;align-items:center;justify-content:center;color:#059669;font-size:2.8rem;">
                    <i class="bi bi-check2-circle"></i>
                </div>
                <h2 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.85rem;margin-bottom:0.75rem;">
                    Terima Kasih Atas Partisipasi Anda!
                </h2>
                <p style="max-width:580px;margin:0 auto 2rem;color:var(--text-muted);font-size:1.02rem;line-height:1.7;">
                    Jawaban dan evaluasi survei kepuasan Anda telah berhasil disimpan dalam sistem Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata. Penilaian serta saran konstruktif yang Anda berikan sangat berarti bagi peningkatan mutu berkelanjutan layanan kami.
                </p>
                <div class="d-flex justify-content-center gap-3 flex-wrap">
                    <a href="<?= SITE_URL ?>/" class="btn btn-outline-secondary px-4 py-2 rounded-pill fw-bold">
                        <i class="bi bi-house me-1"></i> Kembali ke Beranda
                    </a>
                    <a href="<?= SITE_URL ?>/survei-kepuasan.php" class="btn btn-primary px-4 py-2 rounded-pill fw-bold" style="background:#0284C7;border-color:#0284C7;">
                        <i class="bi bi-arrow-repeat me-1"></i> Isi Survei Baru
                    </a>
                </div>
            </div>

            <?php else: ?>

            <!-- Header Card Sesuai Gambar -->
            <div class="survei-header-card">
                <div class="survei-logo-wrap">
                    <img src="<?= SITE_URL ?>/assets/images/logo-unika.png" alt="Logo UNIKA Soegijapranata">
                </div>
                <div class="survei-main-title">Survei Kepuasan Pelayanan</div>
                <div class="survei-main-subtitle">Lembaga Penjaminan Mutu</div>
                <div class="survei-main-sub2">Universitas Katolik Soegijapranata</div>
                
                <div class="survei-intro-text">
                    <i class="bi bi-info-circle-fill me-1"></i>
                    Penilaian Bapak/Ibu/Saudara/i sangat bermanfaat untuk peningkatan layanan Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata pada masa yang akan datang.
                </div>
            </div>

            <?php if (!empty($error_message)): ?>
            <div class="alert alert-danger d-flex align-items-center gap-3 mb-4 rounded-3 shadow-sm border-0" role="alert" style="background:#FEF2F2;color:#991B1B;border-left:5px solid #EF4444 !important;">
                <i class="bi bi-exclamation-triangle-fill fs-4 text-danger"></i>
                <div class="fw-semibold"><?= e($error_message) ?></div>
            </div>
            <?php endif; ?>

            <!-- Form Survei -->
            <form method="POST" action="<?= SITE_URL ?>/survei-kepuasan.php" id="surveiForm">
                <input type="hidden" name="action" value="submit_survei">

                <!-- SECTION 1: DATA DIRI RESPONDEN -->
                <div class="survei-card">
                    <div class="survei-section-badge">
                        <i class="bi bi-person-badge-fill"></i> Data Diri Responden
                    </div>

                    <div class="row g-3">
                        <!-- 1. Nama -->
                        <div class="col-md-6">
                            <label class="survei-form-label" for="nama_pengisi">
                                Nama Lengkap <span class="req">*</span>
                            </label>
                            <input type="text" class="form-control survei-form-control" id="nama_pengisi" name="nama_pengisi" value="<?= e($_POST['nama_pengisi'] ?? '') ?>" placeholder="Tuliskan nama lengkap Anda" required>
                        </div>

                        <!-- 2. Email -->
                        <div class="col-md-6">
                            <label class="survei-form-label" for="email_pengisi">
                                Alamat Email <span class="req">*</span>
                            </label>
                            <input type="email" class="form-control survei-form-control" id="email_pengisi" name="email_pengisi" value="<?= e($_POST['email_pengisi'] ?? '') ?>" placeholder="Masukkan alamat email Anda" required>
                        </div>

                        <!-- 3. Jenis Kelamin -->
                        <div class="col-md-6">
                            <label class="survei-form-label">
                                Jenis Kelamin <span class="req">*</span>
                            </label>
                            <div class="radio-custom-pill">
                                <label>
                                    <input type="radio" name="jenis_kelamin" value="Laki-laki" <?= ($_POST['jenis_kelamin'] ?? '') === 'Laki-laki' ? 'checked' : '' ?> required>
                                    <div class="pill-box">
                                        <i class="bi bi-gender-male me-1"></i> Laki-laki
                                    </div>
                                </label>
                                <label>
                                    <input type="radio" name="jenis_kelamin" value="Perempuan" <?= ($_POST['jenis_kelamin'] ?? '') === 'Perempuan' ? 'checked' : '' ?> required>
                                    <div class="pill-box">
                                        <i class="bi bi-gender-female me-1"></i> Perempuan
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- 4. Pendidikan Terakhir -->
                        <div class="col-md-6">
                            <label class="survei-form-label" for="pendidikan_terakhir">
                                Pendidikan Terakhir <span class="req">*</span>
                            </label>
                            <select class="form-select survei-form-control" id="pendidikan_terakhir" name="pendidikan_terakhir" required>
                                <option value="" selected disabled>-- Pilih Pendidikan Terakhir --</option>
                                <?php
                                $edu_options = [
                                    'Diploma (1-3)' => 'Diploma (1-3)',
                                    'S1 (Sarjana)'  => 'S1 (Sarjana)',
                                    'S2 (Magister)' => 'S2 (Magister)',
                                    'S3 (Doktor)'   => 'S3 (Doktor)'
                                ];
                                $cur_edu = $_POST['pendidikan_terakhir'] ?? '';
                                foreach ($edu_options as $val => $lbl):
                                ?>
                                    <option value="<?= $val ?>" <?= $cur_edu === $val ? 'selected' : '' ?>><?= $lbl ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 5. Perguruan Tinggi / Instansi / Sekolah / Lembaga -->
                        <div class="col-md-6">
                            <label class="survei-form-label" for="nama_institusi">
                                Perguruan Tinggi / Instansi / Sekolah / Lembaga <span class="req">*</span>
                            </label>
                            <input type="text" class="form-control survei-form-control" id="nama_institusi" name="nama_institusi" value="<?= e($_POST['nama_institusi'] ?? '') ?>" placeholder="Nama asal instansi atau lembaga Anda" required>
                        </div>

                        <!-- 6. Kategori Layanan -->
                        <div class="col-md-6">
                            <label class="survei-form-label">
                                Kategori <span class="req">*</span>
                            </label>
                            <div class="radio-custom-pill">
                                <label>
                                    <input type="radio" name="kategori_layanan" value="Kunjungan Studi Banding" <?= (($_POST['kategori_layanan'] ?? '') === 'Kunjungan Studi Banding') ? 'checked' : '' ?> required>
                                    <div class="pill-box">
                                        <i class="bi bi-building-check me-1"></i> Kunjungan Studi Banding
                                    </div>
                                </label>
                                <label>
                                    <input type="radio" name="kategori_layanan" value="Pelayanan LPM" <?= (($_POST['kategori_layanan'] ?? 'Pelayanan LPM') === 'Pelayanan LPM') ? 'checked' : '' ?> required>
                                    <div class="pill-box">
                                        <i class="bi bi-award me-1"></i> Pelayanan LPM
                                    </div>
                                </label>
                            </div>
                        </div>

                        <!-- 7. Status Responden -->
                        <div class="col-12">
                            <label class="survei-form-label" for="status_responden">
                                Status Responden <span class="req">*</span>
                            </label>
                            <select class="form-select survei-form-control" id="status_responden" name="status_responden" required>
                                <option value="" selected disabled>-- Pilih Status Responden --</option>
                                <?php
                                $status_opts = [
                                    'Dosen Universitas Katolik Soegijapranata',
                                    'Tendik Universitas Katolik Soegijapranata',
                                    'Mahasiswa Universitas Katolik Soegijapranata',
                                    'Alumni Universitas Katolik Soegijapranata',
                                    'Mitra Universitas Katolik Soegijapranata',
                                    'Masyarakat Umum'
                                ];
                                $cur_status = $_POST['status_responden'] ?? '';
                                foreach ($status_opts as $s_item):
                                ?>
                                    <option value="<?= $s_item ?>" <?= $cur_status === $s_item ? 'selected' : '' ?>><?= $s_item ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                    </div>
                </div>

                <!-- SECTION 2: 8 BUTIR PERTANYAAN SURVEI KEPUASAN -->
                <div class="survei-card">
                    <div class="survei-section-badge" style="background:#ECFDF5;color:#047857;">
                        <i class="bi bi-stars"></i> Penilaian Kepuasan Pelayanan
                    </div>
                    <p class="text-muted" style="font-size:0.88rem;margin-bottom:1.5rem;line-height:1.6;">
                        Silakan pilih salah satu ikon ekspresi yang paling mewakili tingkat kepuasan Anda terhadap setiap indikator pelayanan di bawah ini.
                    </p>

                    <div class="pertanyaan-list">
                        <?php 
                        $likert_items = [
                            5 => ['text' => 'Sangat Puas', 'emoji' => '😄', 'class' => 'skala-5'],
                            4 => ['text' => 'Puas', 'emoji' => '🙂', 'class' => 'skala-4'],
                            3 => ['text' => 'Kurang Puas', 'emoji' => '😐', 'class' => 'skala-3'],
                            2 => ['text' => 'Tidak Puas', 'emoji' => '🙁', 'class' => 'skala-2'],
                            1 => ['text' => 'Sangat Tidak Puas', 'emoji' => '😢', 'class' => 'skala-1']
                        ];

                        foreach ($questions as $idx => $q): 
                            $qid = $q['id'];
                            $selected_val = isset($_POST['pertanyaan_' . $qid]) ? (int)$_POST['pertanyaan_' . $qid] : null;
                        ?>
                        <div class="pertanyaan-item" id="item_pertanyaan_<?= $qid ?>">
                            <div class="pertanyaan-title">
                                <span class="pertanyaan-num"><?= $q['urutan'] ?: ($idx + 1) ?></span>
                                <div>
                                    <?= e($q['pertanyaan']) ?>
                                    <span class="text-danger">*</span>
                                </div>
                            </div>

                            <div class="likert-scale-grid">
                                <?php foreach ($likert_items as $score => $meta): ?>
                                <label class="likert-option-label" title="<?= $meta['text'] ?>">
                                    <input type="radio" name="pertanyaan_<?= $qid ?>" value="<?= $score ?>" <?= $selected_val === $score ? 'checked' : '' ?> required>
                                    <div class="likert-btn-box <?= $meta['class'] ?>">
                                        <div class="likert-emoji"><?= $meta['emoji'] ?></div>
                                        <div class="likert-text"><?= $meta['text'] ?></div>
                                    </div>
                                </label>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- SECTION 3: SARAN DAN MASUKAN -->
                <div class="survei-card">
                    <div class="survei-section-badge" style="background:#FFFBEB;color:#B45309;">
                        <i class="bi bi-chat-heart-fill"></i> Saran dan Masukan
                    </div>

                    <div class="mb-2">
                        <label class="survei-form-label" for="saran_masukan">
                            Saran dan masukan <span class="fw-normal text-muted">(tuliskan disini)</span>:
                        </label>
                        <textarea class="form-control survei-form-control" id="saran_masukan" name="saran_masukan" rows="4" placeholder="Tuliskan saran konstruktif, kritik, atau apresiasi Anda untuk kemajuan dan peningkatan kualitas layanan LPM..."><?= e($_POST['saran_masukan'] ?? '') ?></textarea>
                    </div>
                </div>

                <!-- SECTION 4: TOMBOL SIMPAN JAWABAN -->
                <div class="text-center py-3 mb-5">
                    <button type="submit" class="btn btn-simpan-jawaban">
                        <i class="bi bi-send-check-fill"></i> Simpan Jawaban
                    </button>
                    <div class="text-muted mt-2" style="font-size:0.82rem;">
                        <i class="bi bi-shield-lock me-1"></i> Data dan identitas Anda dijaga kerahasiaannya untuk keperluan evaluasi mutu kelembagaan.
                    </div>
                </div>

            </form>

            <?php endif; ?>

        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var surveiForm = document.getElementById('surveiForm');
    var emailInput = document.getElementById('email_pengisi');
    if (!surveiForm || !emailInput) return;

    var blockedDomains = [
        'gmail.com', 'googlemail.com', 'yahoo.com', 'yahoo.co.id', 'yahoo.co.uk', 'ymail.com', 'rocketmail.com',
        'hotmail.com', 'hotmail.co.id', 'outlook.com', 'outlook.co.id', 'live.com', 'msn.com',
        'icloud.com', 'me.com', 'mac.com', 'aol.com', 'aim.com', 'zoho.com', 'proton.me', 'protonmail.com',
        'mail.com', 'gmx.com', 'yandex.com', 'tutanota.com', 'mailinator.com', 'tempmail.com', '10minutemail.com'
    ];

    function checkInstitutional(showNotice) {
        var val = (emailInput.value || '').trim().toLowerCase();
        if (!val) return true;

        var atPos = val.lastIndexOf('@');
        if (atPos === -1) return true;

        var domain = val.substring(atPos + 1);
        var isAcademic = /\.(ac\.id|edu|sch\.id|go\.id|mil\.id|gov|ac\.[a-z]{2,3}|edu\.[a-z]{2,3})$/i.test(domain);
        var isBlocked = blockedDomains.some(function(b) {
            return domain === b || domain.endsWith('.' + b);
        });

        if (isBlocked || (!isAcademic && domain.indexOf('.') === -1)) {
            if (showNotice) {
                emailInput.setCustomValidity('Mohon gunakan alamat email resmi instansi/lembaga Anda.');
                emailInput.reportValidity();
            }
            return false;
        }

        emailInput.setCustomValidity('');
        return true;
    }

    emailInput.addEventListener('input', function() {
        emailInput.setCustomValidity('');
    });

    emailInput.addEventListener('blur', function() {
        if (emailInput.value) {
            checkInstitutional(true);
        }
    });

    surveiForm.addEventListener('submit', function(e) {
        if (!checkInstitutional(true)) {
            e.preventDefault();
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
