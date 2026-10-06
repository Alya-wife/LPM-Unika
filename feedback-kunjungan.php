<?php
require_once __DIR__ . '/config/database.php';

// Dialihkan ke Survei Kepuasan Layanan LPM baru (menggabungkan feedback kunjungan dan kritik & saran)
redirect(SITE_URL . '/survei-kepuasan.php');
exit;

$token_data   = null;
$token_error  = '';
$already_used = false;
$used_data    = null;

if ($submitted) {
    $token_code = '';
}

// 1. Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'submit_kuesioner') {
    $submitted_token = strtoupper(trim($_POST['token'] ?? ''));
    $stmt = $db->prepare("SELECT * FROM kunjungan_feedback_token WHERE token = ?");
    $stmt->execute([$submitted_token]);
    $valid_token = $stmt->fetch();

    if (!$valid_token) {
        $token_error = 'Kode token "' . e($submitted_token) . '" tidak ditemukan dalam sistem.';
    } elseif ($valid_token['status'] === 'Digunakan') {
        $used_time = !empty($valid_token['used_at']) ? date('d/m/Y H:i', strtotime($valid_token['used_at'])) : '-';
        $token_error = 'Kode token ini telah digunakan untuk mengirimkan umpan balik pada ' . $used_time . ' WIB. Terima kasih atas partisipasi Anda.';
    } elseif ($valid_token['status'] === 'Nonaktif') {
        $token_error = 'Kode token ini telah dinonaktifkan oleh administrator LPM.';
    } else {
        $now = date('Y-m-d H:i:s');
        if ($now < $valid_token['berlaku_mulai']) {
            $tgl_mulai = date('d/m/Y H:i', strtotime($valid_token['berlaku_mulai']));
            $token_error = 'Kode token ini baru dapat digunakan mulai tanggal ' . $tgl_mulai . ' WIB (pada hari pelaksanaan kunjungan).';
        } elseif ($now > $valid_token['berlaku_sampai']) {
            $tgl_akhir = date('d/m/Y H:i', strtotime($valid_token['berlaku_sampai']));
            $token_error = 'Masa berlaku kode token ini telah berakhir pada ' . $tgl_akhir . ' WIB. Silakan hubungi sekretariat LPM jika Anda membutuhkan akses baru.';
        } else {
            $nama_pengisi    = trim($_POST['nama_pengisi'] ?? '');
            $jabatan_pengisi = trim($_POST['jabatan_pengisi'] ?? '');
            $email_pengisi   = trim($_POST['email_pengisi'] ?? '');

            if (empty($email_pengisi)) {
                $token_error = 'Alamat email instansi wajib diisi.';
            } elseif (!filter_var($email_pengisi, FILTER_VALIDATE_EMAIL)) {
                $token_error = 'Format alamat email tidak valid.';
            } elseif (!isInstitutionalEmail($email_pengisi)) {
                $token_error = 'Mohon gunakan alamat email resmi instansi/lembaga Anda.';
            }

            if (empty($token_error)) {
                // Fetch active questions
            $questions = $db->query("SELECT * FROM kunjungan_kuesioner_pertanyaan WHERE is_aktif = 1 ORDER BY urutan ASC, id ASC")->fetchAll();
            $skala_scores = [];
            $answers = [];

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
                    }
                } else {
                    $text_answer = trim($_POST['pertanyaan_' . $qid] ?? '');
                    $answers[] = [
                        'pertanyaan_id' => $qid,
                        'nilai_skor'    => null,
                        'jawaban_teks'  => $text_answer
                    ];
                }
            }

            $avg_score = count($skala_scores) > 0 ? (array_sum($skala_scores) / count($skala_scores)) : null;

            $ins_resp = $db->prepare("INSERT INTO kunjungan_feedback_respon 
                (token_id, kunjungan_id, nama_institusi, tanggal_kunjungan, nama_pengisi, jabatan_pengisi, email_pengisi, rata_rata_skor) 
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $ins_resp->execute([
                $valid_token['id'],
                $valid_token['kunjungan_id'],
                $valid_token['nama_institusi'],
                $valid_token['tanggal_kunjungan'],
                $nama_pengisi,
                $jabatan_pengisi,
                $email_pengisi,
                $avg_score
            ]);
            $respon_id = $db->lastInsertId();

            // Insert answers
            $ins_ans = $db->prepare("INSERT INTO kunjungan_feedback_jawaban (respon_id, pertanyaan_id, nilai_skor, jawaban_teks) VALUES (?, ?, ?, ?)");
            foreach ($answers as $ans) {
                $ins_ans->execute([$respon_id, $ans['pertanyaan_id'], $ans['nilai_skor'], $ans['jawaban_teks']]);
            }

            // Mark token as used
            $upd_tok = $db->prepare("UPDATE kunjungan_feedback_token SET status = 'Digunakan', used_at = CURRENT_TIMESTAMP WHERE id = ?");
            $upd_tok->execute([$valid_token['id']]);

            redirect(SITE_URL . '/feedback-kunjungan.php?submitted=1&instansi=' . urlencode($valid_token['nama_institusi']));
            }
        }
    }
}

// 2. Token Verification for Form Display
if (!empty($token_code)) {
    $stmt = $db->prepare("SELECT * FROM kunjungan_feedback_token WHERE token = ?");
    $stmt->execute([$token_code]);
    $token_row = $stmt->fetch();

    if (!$token_row) {
        $token_error = 'Kode token "' . e($token_code) . '" tidak ditemukan dalam sistem. Mohon periksa kembali kode yang diberikan oleh tim LPM.';
    } elseif ($token_row['status'] === 'Digunakan') {
        $already_used = true;
        $used_data    = $token_row;
    } elseif ($token_row['status'] === 'Nonaktif') {
        $token_error = 'Kode token ini telah dinonaktifkan oleh administrator LPM.';
    } else {
        $now = date('Y-m-d H:i:s');
        if ($now < $token_row['berlaku_mulai']) {
            $tgl_mulai = date('d/m/Y H:i', strtotime($token_row['berlaku_mulai']));
            $token_error = 'Kode token ini baru dapat digunakan mulai tanggal ' . $tgl_mulai . ' WIB (pada hari pelaksanaan kunjungan).';
        } elseif ($now > $token_row['berlaku_sampai']) {
            $tgl_akhir = date('d/m/Y H:i', strtotime($token_row['berlaku_sampai']));
            $token_error = 'Masa berlaku kode token ini telah berakhir pada ' . $tgl_akhir . ' WIB. Silakan hubungi sekretariat LPM jika Anda membutuhkan akses baru.';
        } else {
            $token_data = $token_row;
        }
    }
}

// Fetch active questions if token is valid
$active_questions = [];
if ($token_data) {
    $active_questions = $db->query("SELECT * FROM kunjungan_kuesioner_pertanyaan WHERE is_aktif = 1 ORDER BY urutan ASC, id ASC")->fetchAll();
}

$extra_css = '
<style>
.feedback-hero-banner {
    background: linear-gradient(135deg, #0A192F 0%, #1E3A8A 60%, #0284C7 100%);
    color: #fff;
    padding: 3.5rem 1rem 3rem;
    position: relative;
    overflow: hidden;
}
.feedback-hero-banner h1,
.feedback-hero-banner .hero-title {
    color: #FFFFFF !important;
    text-shadow: 0 2px 12px rgba(0, 0, 0, 0.25);
}
.feedback-hero-banner::after {
    content: "";
    position: absolute;
    top: 0; right: 0; bottom: 0; left: 0;
    background: radial-gradient(circle at 85% 20%, rgba(255,255,255,0.1) 0%, transparent 60%);
    pointer-events: none;
}
.token-box-card {
    background: #FFFFFF;
    border-radius: var(--radius-lg);
    box-shadow: 0 10px 30px rgba(10, 25, 47, 0.08);
    border: 1px solid var(--border);
    padding: 2.25rem;
}
.token-input-field {
    letter-spacing: 3px;
    font-weight: 800;
    text-transform: uppercase;
    font-size: 1.25rem;
    text-align: center;
    border: 2px solid #CBD5E1;
    border-radius: var(--radius-md);
    padding: 0.75rem 1rem;
    transition: all 0.2s ease;
}
.token-input-field:focus {
    border-color: #0284C7;
    box-shadow: 0 0 0 4px rgba(2, 132, 199, 0.18);
}
.institution-meta-card {
    background: linear-gradient(135deg, #F0F9FF 0%, #E0F2FE 100%);
    border: 1.5px solid #BAE6FD;
    border-radius: var(--radius-md);
    padding: 1.25rem 1.5rem;
    margin-bottom: 2rem;
}
.scale-legend-card {
    background: #F8FAFC;
    border: 1px solid #E2E8F0;
    border-radius: var(--radius-md);
    padding: 1rem 1.25rem;
    margin-bottom: 2rem;
}
.scale-legend-item {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    margin-right: 1.25rem;
    font-size: 0.84rem;
    font-weight: 600;
    color: #334155;
}
.scale-badge {
    width: 22px;
    height: 22px;
    border-radius: 50%;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.75rem;
    font-weight: 800;
    color: #fff;
}
.scale-badge-1 { background: #EF4444; }
.scale-badge-2 { background: #F97316; }
.scale-badge-3 { background: #EAB308; }
.scale-badge-4 { background: #10B981; }
.scale-badge-5 { background: #0284C7; }

.question-card {
    background: #FFFFFF;
    border: 1px solid var(--border);
    border-radius: var(--radius-md);
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
}
.question-card:hover {
    box-shadow: 0 6px 18px rgba(0,0,0,0.06);
}
.question-title {
    font-family: var(--font-heading);
    font-size: 1.02rem;
    font-weight: 700;
    color: var(--navy);
    margin-bottom: 0.35rem;
}
.question-desc {
    font-size: 0.85rem;
    color: var(--text-muted);
    margin-bottom: 1.15rem;
}

/* Likert 1-5 Option Pill Group */
.likert-options-wrap {
    display: grid;
    grid-template-columns: repeat(5, 1fr);
    gap: 10px;
}
@media (max-width: 768px) {
    .likert-options-wrap {
        grid-template-columns: 1fr;
        gap: 8px;
    }
}
.likert-option-label {
    border: 1.5px solid #E2E8F0;
    border-radius: var(--radius-sm);
    padding: 0.85rem 0.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    background: #FFFFFF;
    user-select: none;
}
.likert-option-label:hover {
    border-color: #0284C7;
    background: #F0F9FF;
}
.likert-radio {
    display: none;
}
.likert-radio:checked + .likert-option-label {
    border-color: #0284C7;
    background: #0284C7;
    color: #FFFFFF !important;
    box-shadow: 0 4px 12px rgba(2, 132, 199, 0.35);
}
.likert-radio:checked + .likert-option-label .likert-score-num {
    background: #FFFFFF;
    color: #0284C7;
}
.likert-radio:checked + .likert-option-label .likert-score-text {
    color: #FFFFFF;
}
.likert-score-num {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: #F1F5F9;
    color: #0F172A;
    font-weight: 800;
    font-size: 0.95rem;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 6px;
    transition: all 0.2s ease;
}
.likert-score-text {
    font-size: 0.78rem;
    font-weight: 700;
    color: #475569;
    line-height: 1.2;
}

.success-thankyou-box {
    background: #FFFFFF;
    border-radius: var(--radius-lg);
    box-shadow: 0 15px 35px rgba(0,0,0,0.06);
    border: 1px solid var(--border);
    padding: 3.5rem 2rem;
    text-align: center;
    max-width: 720px;
    margin: 2rem auto;
}
.success-icon-circle {
    width: 80px;
    height: 80px;
    border-radius: 50%;
    background: linear-gradient(135deg, #10B981, #059669);
    color: #FFFFFF;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 2.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 8px 25px rgba(16, 185, 129, 0.35);
}
</style>
';

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Hero Banner -->
<section class="feedback-hero-banner">
    <div class="container text-center">
        <h1 class="hero-title" style="font-family:var(--font-heading);font-weight:800;font-size:clamp(1.75rem,3.5vw,2.5rem);margin-bottom:0.75rem;color:#FFFFFF !important;text-shadow:0 2px 12px rgba(0,0,0,0.25);">
            <?= e(getPengaturan('feedback_kunjungan_judul', 'Kuesioner Umpan Balik Kunjungan Mitra Institusi')) ?>
        </h1>
        <p style="font-size:1rem;color:rgba(255,255,255,0.92);max-width:680px;margin:0 auto;line-height:1.6;">
            Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata berkomitmen untuk senantiasa mendengarkan evaluasi, apresiasi, dan masukan konstruktif dari seluruh mitra institusi.
        </p>
    </div>
</section>

<div class="container py-5">

    <?php if ($submitted): ?>
        <!-- State: Sukses Submit Kuesioner -->
        <div class="success-thankyou-box">
            <div class="success-icon-circle">
                <i class="bi bi-check-lg"></i>
            </div>
            <h2 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.85rem;margin-bottom:0.75rem;">
                Terima Kasih! Jawaban Kuesioner Anda Telah Tersimpan
            </h2>
            <?php if (!empty($_GET['instansi'])): ?>
            <div class="badge bg-light text-dark px-3 py-2 mb-3" style="font-size:0.9rem;border:1px solid #E2E8F0;">
                🏛️ <?= e($_GET['instansi']) ?>
            </div>
            <?php endif; ?>
            <p style="color:var(--text);font-size:0.98rem;line-height:1.65;max-width:600px;margin:0 auto 1.25rem;">
                Terima kasih telah mengisi kuesioner umpan balik kunjungan ini. Seluruh tanggapan, penilaian, serta rekomendasi yang Bapak/Ibu berikan <strong>telah berhasil tersimpan dengan aman</strong> dalam pangkalan data penjaminan mutu kami.
            </p>
            <p style="color:var(--text-muted);font-size:0.875rem;max-width:580px;margin:0 auto 2rem;line-height:1.6;">
                Masukan ini sangat berharga bagi <strong>Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata</strong> untuk senantiasa menyempurnakan kualitas penerimaan audiensi dan tata kelola SPMI.
            </p>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="<?= SITE_URL ?>/feedback-kunjungan.php" class="btn-save" style="padding:0.75rem 1.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-size:0.95rem;">
                    <i class="bi bi-arrow-left-circle-fill"></i> Kembali ke Pintu Masuk Kode Token
                </a>
                <a href="<?= SITE_URL ?>/" class="btn-outline" style="padding:0.75rem 1.85rem;font-size:0.95rem;">
                    Halaman Utama LPM
                </a>
            </div>
        </div>

    <?php elseif ($already_used && $used_data): ?>
        <!-- State: Token Sudah Terisi Sebelumnya -->
        <div class="success-thankyou-box">
            <div class="success-icon-circle" style="background:linear-gradient(135deg, #0284C7, #0369A1);box-shadow:0 8px 25px rgba(2, 132, 199, 0.35);">
                <i class="bi bi-patch-check-fill"></i>
            </div>
            <h2 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.85rem;margin-bottom:0.75rem;">
                Jawaban Kuesioner Telah Tersimpan
            </h2>
            <div class="badge bg-light text-dark px-3 py-2 mb-3" style="font-size:0.9rem;border:1px solid #E2E8F0;">
                🏛️ <?= e($used_data['nama_institusi']) ?>
            </div>
            <p style="color:var(--text);font-size:0.98rem;line-height:1.65;max-width:600px;margin:0 auto 1.25rem;">
                Terima kasih telah mengisi kuesioner ini. Jawaban dan masukan evaluasi kunjungan dari institusi Anda <strong>telah berhasil tersimpan dengan baik</strong> dalam sistem pada <strong><?= !empty($used_data['used_at']) ? date('d/m/Y H:i', strtotime($used_data['used_at'])) : '-' ?> WIB</strong>.
            </p>
            <div class="p-3 mb-4 rounded-3 d-inline-block text-start" style="background:#F8FAFC;border:1px dashed #CBD5E1;max-width:500px;">
                <div style="font-size:0.82rem;color:var(--text-muted);line-height:1.5;">
                    <i class="bi bi-info-circle-fill text-primary me-1"></i> Kode token <strong><?= e($used_data['token']) ?></strong> bersifat <em>single-use</em> dan saat ini telah otomatis terkunci demi menjamin integritas data survei.
                </div>
            </div>
            <div class="d-flex justify-content-center gap-3 flex-wrap">
                <a href="<?= SITE_URL ?>/feedback-kunjungan.php" class="btn-save" style="padding:0.75rem 1.85rem;text-decoration:none;display:inline-flex;align-items:center;gap:8px;font-size:0.95rem;">
                    <i class="bi bi-arrow-left-circle-fill"></i> Kembali ke Pintu Masuk Kode Token
                </a>
                <a href="<?= SITE_URL ?>/" class="btn-outline" style="padding:0.75rem 1.85rem;font-size:0.95rem;">
                    Halaman Utama LPM
                </a>
            </div>
        </div>

    <?php elseif (!$token_data): ?>
        <!-- State: Input / Verifikasi Token -->
        <div class="token-box-card mx-auto" style="max-width:580px;">
            <div class="text-center mb-4">
                <div style="width:60px;height:60px;border-radius:50%;background:#F0F9FF;color:#0284C7;display:inline-flex;align-items:center;justify-content:center;font-size:1.75rem;margin-bottom:1rem;">
                    <i class="bi bi-shield-lock"></i>
                </div>
                <h3 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:0.5rem;">
                    Masukkan Kode Feedback Kunjungan
                </h3>
                <p style="color:var(--text-muted);font-size:0.875rem;margin:0;">
                    Kuesioner evaluasi kepuasan dikhususkan bagi instansi tamu yang telah menyelesaikan agenda kunjungan resmi ke LPM UNIKA Soegijapranata.
                </p>
            </div>

            <?php if ($token_error): ?>
            <div class="alert-lpm alert-danger mb-4" style="font-size:0.875rem;">
                <i class="bi bi-exclamation-triangle-fill me-2"></i>
                <?= e($token_error) ?>
            </div>
            <?php endif; ?>

            <form method="get" action="feedback-kunjungan.php" id="form-token-feedback">
                <div class="mb-4">
                    <label class="form-label fw-bold" style="font-size:0.875rem;color:var(--navy);">
                        Kode Token (Diberikan oleh Tim LPM) <span class="text-danger">*</span>
                    </label>
                    <input type="text" name="token" id="input-token-feedback" class="form-control token-input-field" placeholder="LPM-XXXXXX" value="<?= e($token_code) ?>" required autofocus autocomplete="off">
                    <div class="form-text text-center mt-2" style="font-size:0.8rem;color:var(--text-muted);">
                        Format kode: <strong>LPM-XXXXXX</strong>. Masukkan kode token resmi institusi Anda.
                    </div>

                    <!-- Live Token Notice -->
                    <div id="live-token-info" class="mt-3 text-start" style="display:none;"></div>
                </div>

                <button type="submit" id="btn-submit-feedback-token" class="btn-save w-100 justify-content-center" style="padding:0.8rem 1rem;font-size:0.95rem;">
                    <i class="bi bi-unlock me-2"></i> Buka Kuesioner Kepuasan
                </button>
            </form>

            <div class="mt-4 pt-3 text-center border-top" style="font-size:0.82rem;color:var(--text-muted);line-height:1.5;">
                <i class="bi bi-info-circle text-primary me-1"></i> Apabila ada kendala pengisian feedback silahkan hubungi sekretariat LPM melalui telepon <strong>(024) 8441555 Ext 1473</strong> atau email <strong>lpm@unika.ac.id</strong>.
            </div>
        </div>

    <?php else: ?>
        <!-- State: Formulir Kuesioner Aktif (Token Valid) -->
        <div class="row justify-content-center">
            <div class="col-lg-10">

                <!-- Meta Informasi Instansi Tamu -->
                <div class="institution-meta-card">
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                        <span class="badge" style="background:#0284C7;color:#fff;font-weight:700;font-size:0.75rem;letter-spacing:0.5px;">
                            <i class="bi bi-check-circle-fill me-1"></i> TOKEN RESMI TERVERIFIKASI
                        </span>
                        <span style="font-size:0.82rem;font-weight:600;color:#0369A1;">
                            Kode: <code style="background:#fff;padding:2px 6px;border-radius:4px;border:1px solid #BAE6FD;color:#0284C7;font-weight:700;"><?= e($token_data['token']) ?></code>
                        </span>
                    </div>
                    <h4 style="font-family:var(--font-heading);font-weight:800;color:#0C4A6E;margin-bottom:0.4rem;">
                        <?= e($token_data['nama_institusi']) ?>
                    </h4>
                    <div class="d-flex flex-wrap gap-3 mb-2" style="font-size:0.85rem;color:#0369A1;">
                        <span><i class="bi bi-calendar-event me-1"></i> Tanggal Kunjungan: <strong><?= formatTanggal($token_data['tanggal_kunjungan']) ?></strong></span>
                        <?php if (!empty($token_data['perihal'])): ?>
                        <span><i class="bi bi-bookmark-check me-1"></i> Perihal: <strong><?= e(truncate($token_data['perihal'], 80)) ?></strong></span>
                        <?php endif; ?>
                    </div>
                    <!-- Pemberitahuan Masa Berlaku Token yang Ditetapkan Admin -->
                    <?php if (!empty($token_data['berlaku_sampai'])): 
                        $t_akhir = strtotime($token_data['berlaku_sampai']);
                        $diff_s = $t_akhir - time();
                        $d_s = floor($diff_s / 86400);
                        $h_s = floor(($diff_s % 86400) / 3600);
                        $m_s = floor(($diff_s % 3600) / 60);
                        $sisa_txt = [];
                        if ($d_s > 0) $sisa_txt[] = $d_s . ' hari';
                        if ($h_s > 0) $sisa_txt[] = $h_s . ' jam';
                        if ($m_s > 0 || empty($sisa_txt)) $sisa_txt[] = $m_s . ' menit';
                        $sisa_formatted = implode(' ', $sisa_txt);

                        $bulan_arr = [
                            1 => 'Januari', 'Februari', 'Maret', 'April',
                            'Mei', 'Juni', 'Juli', 'Agustus',
                            'September', 'Oktober', 'November', 'Desember'
                        ];
                        $tgl_akhir_ind = date('d', $t_akhir) . ' ' . $bulan_arr[(int)date('n', $t_akhir)] . ' ' . date('Y', $t_akhir);
                        $jam_akhir_ind = date('H:i', $t_akhir);
                    ?>
                    <div class="p-3 rounded-3 d-flex align-items-center gap-3 mt-3" style="background:#EFF6FF;border:1.5px solid #60A5FA;color:#1E40AF;box-shadow:0 4px 15px rgba(37,99,235,0.08);">
                        <div style="width:44px;height:44px;min-width:44px;border-radius:50%;background:#2563EB;color:#fff;display:flex;align-items:center;justify-content:center;font-size:1.35rem;">
                            <i class="bi bi-clock-history"></i>
                        </div>
                        <div>
                            <div style="font-weight:700;font-size:0.92rem;color:#1E3A8A;margin-bottom:2px;">
                                Pemberitahuan Masa Berlaku Token:
                            </div>
                            <div style="font-size:0.875rem;line-height:1.45;">
                                Kuesioner evaluasi ini dapat diisi sampai dengan <strong><?= $tgl_akhir_ind ?> pukul <?= $jam_akhir_ind ?> WIB</strong> (Sisa waktu: <strong class="badge bg-primary px-2 py-1 ms-1" style="font-size:0.8rem;"><?= $sisa_formatted ?></strong>).
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- Panduan Skala Nilai (1 - 5) -->
                <div class="scale-legend-card">
                    <div class="d-flex align-items-center gap-2 mb-2" style="font-weight:700;color:var(--navy);font-size:0.9rem;">
                        <i class="bi bi-info-circle text-primary"></i> Panduan Skala Penilaian Kuesioner (1 – 5):
                    </div>
                    <div class="d-flex flex-wrap align-items-center">
                        <div class="scale-legend-item">
                            <span class="scale-badge scale-badge-1">1</span>
                            <span>Sangat Kurang</span>
                        </div>
                        <div class="scale-legend-item">
                            <span class="scale-badge scale-badge-2">2</span>
                            <span>Kurang</span>
                        </div>
                        <div class="scale-legend-item">
                            <span class="scale-badge scale-badge-3">3</span>
                            <span>Cukup</span>
                        </div>
                        <div class="scale-legend-item">
                            <span class="scale-badge scale-badge-4">4</span>
                            <span>Baik</span>
                        </div>
                        <div class="scale-legend-item">
                            <span class="scale-badge scale-badge-5">5</span>
                            <span>Sangat Baik</span>
                        </div>
                    </div>
                </div>

                <!-- Formulir Pengisian -->
                <form method="post" action="feedback-kunjungan.php?token=<?= urlencode($token_data['token']) ?>" id="formFeedbackKunjungan">
                    <input type="hidden" name="action" value="submit_kuesioner">
                    <input type="hidden" name="token" value="<?= e($token_data['token']) ?>">

                    <?php if ($token_error): ?>
                    <div class="alert-lpm alert-danger mb-4">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($token_error) ?>
                    </div>
                    <?php endif; ?>

                    <!-- Bagian 1: Kuesioner Skala -->
                    <div class="mb-4">
                        <h4 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:8px;">
                            <span>Bagian I: Penilaian Pelaksanaan &amp; Substansi Kunjungan</span>
                        </h4>

                        <?php 
                        $no_skala = 1;
                        foreach ($active_questions as $q): 
                            if ($q['tipe'] !== 'skala') continue;
                        ?>
                        <div class="question-card">
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <span class="badge bg-secondary-subtle text-dark" style="font-size:0.8rem;padding:0.35rem 0.6rem;font-weight:700;">
                                    Butir <?= $no_skala++ ?>
                                </span>
                                <?php if (!empty($q['kategori'])): ?>
                                <span class="badge" style="background:#F1F5F9;color:#475569;font-size:0.75rem;padding:0.35rem 0.6rem;">
                                    <?= e($q['kategori']) ?>
                                </span>
                                <?php endif; ?>
                            </div>
                            <div class="question-title"><?= e($q['pertanyaan']) ?> <span class="text-danger">*</span></div>
                            <?php if (!empty($q['keterangan'])): ?>
                            <div class="question-desc"><?= e($q['keterangan']) ?></div>
                            <?php endif; ?>

                            <div class="likert-options-wrap">
                                <?php 
                                $labels = [
                                    1 => 'Sangat Kurang',
                                    2 => 'Kurang',
                                    3 => 'Cukup',
                                    4 => 'Baik',
                                    5 => 'Sangat Baik'
                                ];
                                for ($score = 1; $score <= 5; $score++): 
                                    $radio_id = 'q_' . $q['id'] . '_score_' . $score;
                                ?>
                                <div>
                                    <input type="radio" name="pertanyaan_<?= $q['id'] ?>" value="<?= $score ?>" id="<?= $radio_id ?>" class="likert-radio" required>
                                    <label for="<?= $radio_id ?>" class="likert-option-label">
                                        <div class="likert-score-num"><?= $score ?></div>
                                        <div class="likert-score-text"><?= $labels[$score] ?></div>
                                    </label>
                                </div>
                                <?php endfor; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Bagian 2: Saran & Masukan Kualitatif -->
                    <div class="mb-4">
                        <h4 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.25rem;margin-bottom:1.25rem;display:flex;align-items:center;gap:8px;">
                            <span>Bagian II: Masukan, Saran &amp; Rekomendasi Terbuka</span>
                        </h4>

                        <?php 
                        $no_teks = 1;
                        foreach ($active_questions as $q): 
                            if ($q['tipe'] !== 'teks') continue;
                        ?>
                        <div class="question-card">
                            <div class="d-flex align-items-start gap-2 mb-2">
                                <span class="badge bg-secondary-subtle text-dark" style="font-size:0.8rem;padding:0.35rem 0.6rem;font-weight:700;">
                                    Saran <?= $no_teks++ ?>
                                </span>
                            </div>
                            <div class="question-title"><?= e($q['pertanyaan']) ?></div>
                            <?php if (!empty($q['keterangan'])): ?>
                            <div class="question-desc"><?= e($q['keterangan']) ?></div>
                            <?php endif; ?>

                            <textarea name="pertanyaan_<?= $q['id'] ?>" rows="4" class="form-control" placeholder="Ketikkan tanggapan, inspirasi, atau rekomendasi Anda di sini..." style="border:1.5px solid #CBD5E1;border-radius:var(--radius-sm);font-size:0.92rem;"></textarea>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- Bagian 3: Identitas Perwakilan Pengisi -->
                    <div class="question-card mb-4" style="background:#F8FAFC;">
                        <h5 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);font-size:1.05rem;margin-bottom:0.75rem;">
                            👤 Identitas Perwakilan Pengisi (Opsional)
                        </h5>
                        <p style="font-size:0.85rem;color:var(--text-muted);margin-bottom:1.25rem;">
                            Data perwakilan pengisi digunakan tim LPM untuk keperluan arsip korespondensi dan tindak lanjut kemitraan mutu.
                        </p>

                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:0.85rem;">Nama Lengkap Pengisi</label>
                                <input type="text" name="nama_pengisi" class="form-control" placeholder="Contoh: Dr. Budi Santoso, M.Sc." style="border:1.5px solid var(--border);">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" style="font-size:0.85rem;">Jabatan / Unit Kerja</label>
                                <input type="text" name="jabatan_pengisi" class="form-control" placeholder="Contoh: Kepala LPM / Wakil Rektor I" style="border:1.5px solid var(--border);">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold" style="font-size:0.85rem;">Alamat Email <span class="text-danger">*</span></label>
                                <input type="email" name="email_pengisi" class="form-control" placeholder="Masukkan alamat email Anda" value="<?= e($_POST['email_pengisi'] ?? '') ?>" required style="border:1.5px solid var(--border);">
                            </div>
                        </div>
                    </div>

                    <!-- Tombol Submit -->
                    <div class="text-center mt-4">
                        <button type="submit" class="btn-save btn-lg px-5 py-3" style="font-size:1.05rem;box-shadow:0 8px 24px rgba(106, 27, 154, 0.35);">
                            <i class="bi bi-send-fill me-2"></i> Kirim Umpan Balik Kunjungan
                        </button>
                        <div class="form-text mt-2 text-muted" style="font-size:0.82rem;">
                            Pastikan data kuesioner telah terisi lengkap. Setelah dikirim, kode token akan otomatis terkunci.
                        </div>
                    </div>

                    <div class="mt-4 pt-3 text-center border-top" style="font-size:0.82rem;color:var(--text-muted);line-height:1.5;">
                        <i class="bi bi-info-circle text-primary me-1"></i> Apabila ada kendala pengisian feedback silahkan hubungi sekretariat LPM melalui telepon <strong>(024) 8441555 Ext 1473</strong> atau email <strong>lpm@unika.ac.id</strong>.
                    </div>

                </form>

            </div>
        </div>
    <?php endif; ?>

</div>

<script>
// Realtime Token Check via API for feedback-kunjungan.php
(function() {
    var tokenInput = document.getElementById('input-token-feedback');
    var noticeBox = document.getElementById('live-token-info');
    var timer = null;

    if (!tokenInput || !noticeBox) return;

    function checkToken(val) {
        var cleanToken = val.trim().toUpperCase();
        if (cleanToken.length < 4) {
            noticeBox.style.display = 'none';
            noticeBox.innerHTML = '';
            return;
        }

        fetch('<?= SITE_URL ?>/api/check-token.php?token=' + encodeURIComponent(cleanToken))
            .then(function(res) { return res.json(); })
            .then(function(data) {
                noticeBox.style.display = 'block';
                if (data.valid) {
                    noticeBox.innerHTML = `
                        <div class="p-3 rounded-3" style="background:#EFF6FF;border:1.5px solid #60A5FA;color:#1E40AF;box-shadow:0 4px 12px rgba(37,99,235,0.08);">
                            <div class="d-flex align-items-center gap-2 mb-2 fw-bold text-primary" style="font-size:0.92rem;">
                                <i class="bi bi-patch-check-fill fs-5"></i>
                                <span>Token Terverifikasi &amp; Aktif!</span>
                            </div>
                            <div class="mb-2" style="font-size:0.875rem;">
                                <strong>Institusi Tamu:</strong> <span class="text-dark fw-bold">${data.nama_institusi}</span>
                            </div>
                            <div class="p-2 px-3 rounded-2" style="background:#DBEAFE;border:1px solid #BFDBFE;font-size:0.84rem;line-height:1.5;">
                                <i class="bi bi-clock-history text-primary me-1"></i>
                                <strong>Pemberitahuan Masa Berlaku:</strong> Token ini berlaku sampai <strong>${data.berlaku_sampai_formatted}</strong> (Sisa waktu pengisian: <strong>${data.sisa_waktu}</strong>).
                            </div>
                        </div>
                    `;
                } else if (data.status === 'expired') {
                    noticeBox.innerHTML = `
                        <div class="p-3 rounded-3" style="background:#FEF2F2;border:1.5px solid #F87171;color:#991B1B;">
                            <div class="d-flex align-items-center gap-2 mb-1 fw-bold text-danger" style="font-size:0.92rem;">
                                <i class="bi bi-exclamation-octagon-fill fs-5"></i>
                                <span>Masa Berlaku Token Telah Berakhir</span>
                            </div>
                            <div class="mb-1" style="font-size:0.875rem;"><strong>Institusi:</strong> ${data.nama_institusi || '-'}</div>
                            <div class="p-2 px-3 rounded-2" style="background:#FEE2E2;border:1px solid #FECACA;font-size:0.84rem;">
                                <i class="bi bi-x-circle text-danger me-1"></i> ${data.message}
                            </div>
                        </div>
                    `;
                } else if (data.status === 'used') {
                    noticeBox.innerHTML = `
                        <div class="p-3 rounded-3" style="background:#F0FDF4;border:1.5px solid #4ADE80;color:#166534;">
                            <div class="d-flex align-items-center gap-2 mb-1 fw-bold text-success" style="font-size:0.92rem;">
                                <i class="bi bi-check-all fs-5"></i>
                                <span>Token Sudah Pernah Digunakan</span>
                            </div>
                            <div style="font-size:0.85rem;">${data.message}</div>
                        </div>
                    `;
                } else if (cleanToken.length >= 6) {
                    noticeBox.innerHTML = `
                        <div class="p-2 px-3 rounded-3 text-muted" style="background:#F8FAFC;border:1px dashed #CBD5E1;font-size:0.82rem;">
                            <i class="bi bi-info-circle text-warning me-1"></i> ${data.message}
                        </div>
                    `;
                } else {
                    noticeBox.style.display = 'none';
                }
            })
            .catch(function() {
                noticeBox.style.display = 'none';
            });
    }

    tokenInput.addEventListener('input', function() {
        clearTimeout(timer);
        var v = this.value;
        timer = setTimeout(function() {
            checkToken(v);
        }, 350);
    });

    if (tokenInput.value.trim().length >= 4) {
        checkToken(tokenInput.value);
    }
})();
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
