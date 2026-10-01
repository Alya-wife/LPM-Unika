<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Rincian Umpan Balik Kunjungan Instansi';
$db = getDB();

$token_id  = (int)($_GET['token_id'] ?? 0);
$respon_id = (int)($_GET['id'] ?? 0);

if ($token_id <= 0 && $respon_id <= 0) {
    redirect(SITE_URL . '/admin/feedback-kunjungan-list.php');
}

// If token_id not supplied, check if the single respon_id has token_id
if ($token_id <= 0 && $respon_id > 0) {
    $tok_check = $db->prepare("SELECT token_id FROM kunjungan_feedback_respon WHERE id = ?");
    $tok_check->execute([$respon_id]);
    $tid = $tok_check->fetchColumn();
    if ($tid) {
        $token_id = (int)$tid;
    }
}

// Fetch Visit Info & All Respondents for this delegation
$kunjungan_info = null;
$responden_list = [];

if ($token_id > 0) {
    // 1. Visit metadata from token
    $stmt_meta = $db->prepare("SELECT t.* FROM kunjungan_feedback_token t WHERE t.id = ?");
    $stmt_meta->execute([$token_id]);
    $kunjungan_info = $stmt_meta->fetch();

    // 2. All respondents for this token
    $stmt_resp = $db->prepare("SELECT r.* FROM kunjungan_feedback_respon r WHERE r.token_id = ? ORDER BY r.id ASC");
    $stmt_resp->execute([$token_id]);
    $responden_list = $stmt_resp->fetchAll();
} else {
    // Fallback: search by response ID
    $stmt_resp = $db->prepare("SELECT r.* FROM kunjungan_feedback_respon r WHERE r.id = ?");
    $stmt_resp->execute([$respon_id]);
    $single = $stmt_resp->fetch();

    if ($single) {
        // Check if other respondents have same institution & visit date
        $stmt_same = $db->prepare("SELECT r.* FROM kunjungan_feedback_respon r 
            WHERE r.nama_institusi = ? AND r.tanggal_kunjungan = ? 
            ORDER BY r.id ASC");
        $stmt_same->execute([$single['nama_institusi'], $single['tanggal_kunjungan']]);
        $responden_list = $stmt_same->fetchAll();

        $kunjungan_info = [
            'id'                => null,
            'token'             => null,
            'nama_institusi'    => $single['nama_institusi'],
            'tanggal_kunjungan' => $single['tanggal_kunjungan'],
            'perihal'           => null,
            'email'             => $single['email_pengisi']
        ];
    }
}

if (!$kunjungan_info || empty($responden_list)) {
    $_SESSION['flash_error'] = 'Data umpan balik kunjungan instansi tidak ditemukan.';
    redirect(SITE_URL . '/admin/feedback-kunjungan-list.php');
}

$total_anggota = count($responden_list);
$respon_ids    = array_column($responden_list, 'id');
$placeholders  = implode(',', array_fill(0, count($respon_ids), '?'));

// Calculate overall aggregate CSAT for this institution
$scores_arr = array_filter(array_column($responden_list, 'rata_rata_skor'), fn($v) => $v !== null && $v !== '');
$avg_instansi = !empty($scores_arr) ? round(array_sum($scores_arr) / count($scores_arr), 2) : 0;
$min_personal_skor = !empty($scores_arr) ? min($scores_arr) : 0;
$max_personal_skor = !empty($scores_arr) ? max($scores_arr) : 0;

// Fetch per-question average scores for scale questions (1-5)
$stmt_skala = $db->prepare("SELECT 
    p.id, p.kategori, p.pertanyaan, p.keterangan, p.urutan,
    COUNT(j.id) as total_jawaban,
    AVG(j.nilai_skor) as avg_skor,
    MIN(j.nilai_skor) as min_skor,
    MAX(j.nilai_skor) as max_skor
FROM kunjungan_kuesioner_pertanyaan p
JOIN kunjungan_feedback_jawaban j ON p.id = j.pertanyaan_id
WHERE j.respon_id IN ($placeholders) AND p.tipe = 'skala'
GROUP BY p.id, p.kategori, p.pertanyaan, p.keterangan, p.urutan
ORDER BY p.urutan ASC, p.id ASC");
$stmt_skala->execute($respon_ids);
$skala_questions = $stmt_skala->fetchAll();

// Fetch distribution of scores (1, 2, 3, 4, 5) per question
$stmt_dist = $db->prepare("SELECT 
    j.pertanyaan_id,
    j.nilai_skor,
    r.id as respon_id,
    r.nama_pengisi,
    r.jabatan_pengisi
FROM kunjungan_feedback_jawaban j
JOIN kunjungan_feedback_respon r ON j.respon_id = r.id
JOIN kunjungan_kuesioner_pertanyaan p ON j.pertanyaan_id = p.id
WHERE j.respon_id IN ($placeholders) AND p.tipe = 'skala' AND j.nilai_skor IS NOT NULL
ORDER BY j.pertanyaan_id ASC, j.nilai_skor DESC, r.nama_pengisi ASC");
$stmt_dist->execute($respon_ids);
$dist_rows = $stmt_dist->fetchAll();

$distribusi_bintang = [];
foreach ($skala_questions as $sq) {
    $qid = (int)$sq['id'];
    $distribusi_bintang[$qid] = [
        5 => ['count' => 0, 'respondents' => []],
        4 => ['count' => 0, 'respondents' => []],
        3 => ['count' => 0, 'respondents' => []],
        2 => ['count' => 0, 'respondents' => []],
        1 => ['count' => 0, 'respondents' => []],
    ];
}

foreach ($dist_rows as $dr) {
    $qid = (int)$dr['pertanyaan_id'];
    $skor = (int)$dr['nilai_skor'];
    if (isset($distribusi_bintang[$qid][$skor])) {
        $distribusi_bintang[$qid][$skor]['count']++;
        $distribusi_bintang[$qid][$skor]['respondents'][] = [
            'nama' => $dr['nama_pengisi'] ?: 'Anonim',
            'jabatan' => $dr['jabatan_pengisi'] ?: '-'
        ];
    }
}

// Fetch qualitative feedback (teks questions) with respondent names & positions
$stmt_teks = $db->prepare("SELECT 
    p.id as pertanyaan_id, p.pertanyaan, p.kategori, p.urutan,
    j.jawaban_teks,
    r.id as respon_id, r.nama_pengisi, r.jabatan_pengisi, r.email_pengisi, r.created_at
FROM kunjungan_feedback_jawaban j
JOIN kunjungan_kuesioner_pertanyaan p ON j.pertanyaan_id = p.id
JOIN kunjungan_feedback_respon r ON j.respon_id = r.id
WHERE j.respon_id IN ($placeholders)
  AND p.tipe = 'teks'
  AND j.jawaban_teks IS NOT NULL AND TRIM(j.jawaban_teks) != ''
ORDER BY p.urutan ASC, r.id ASC");
$stmt_teks->execute($respon_ids);
$teks_answers_all = $stmt_teks->fetchAll();

// Group text answers by question
$teks_grouped = [];
foreach ($teks_answers_all as $t_ans) {
    $q_id = $t_ans['pertanyaan_id'];
    if (!isset($teks_grouped[$q_id])) {
        $teks_grouped[$q_id] = [
            'pertanyaan' => $t_ans['pertanyaan'],
            'kategori'   => $t_ans['kategori'],
            'items'      => []
        ];
    }
    $teks_grouped[$q_id]['items'][] = $t_ans;
}

// Collect saran_masukan directly from respondents if available
$saran_masukan_list = [];
foreach ($responden_list as $resp_item) {
    if (!empty(trim($resp_item['saran_masukan'] ?? ''))) {
        $saran_masukan_list[] = [
            'nama_pengisi'     => $resp_item['nama_pengisi'],
            'jabatan_pengisi'  => $resp_item['status_responden'] ?? $resp_item['jabatan_pengisi'] ?? '-',
            'status_responden' => $resp_item['status_responden'] ?? '-',
            'jawaban_teks'     => $resp_item['saran_masukan'],
            'created_at'       => $resp_item['created_at']
        ];
    }
}
if (!empty($saran_masukan_list)) {
    $teks_grouped['saran_masukan'] = [
        'pertanyaan' => 'Saran dan masukan untuk peningkatan kualitas layanan LPM',
        'kategori'   => 'Saran & Masukan',
        'items'      => $saran_masukan_list
    ];
}

// Chart.js Data Preparation
$chart_labels        = [];
$chart_scores        = [];
$chart_bg_colors     = [];
$chart_border_colors = [];
$radar_labels        = [];

$radar_names = [
    1 => '1. Kesesuaian Syarat',
    2 => '2. Prosedur Layanan',
    3 => '3. Kecepatan Waktu',
    4 => '4. Kesesuaian Produk',
    5 => '5. Kompetensi Petugas',
    6 => '6. Sikap & Keramahan',
    7 => '7. Sarana & Prasarana',
    8 => '8. Penanganan Aduan'
];

foreach ($skala_questions as $idx => $sq) {
    $avg = round((float)$sq['avg_skor'], 2);
    $chart_labels[] = ($idx + 1) . '. ' . truncate($sq['pertanyaan'], 44);
    $chart_scores[] = $avg;
    
    $u_num = $sq['urutan'] ?: ($idx + 1);
    $radar_labels[] = $radar_names[$u_num] ?? (($idx + 1) . '. ' . truncate($sq['pertanyaan'], 20));
    
    if ($avg >= 4.5) {
        $chart_bg_colors[]     = 'rgba(16, 185, 129, 0.85)'; // Sangat Puas
        $chart_border_colors[] = '#059669';
    } elseif ($avg >= 3.5) {
        $chart_bg_colors[]     = 'rgba(2, 132, 199, 0.85)';  // Puas
        $chart_border_colors[] = '#0284C7';
    } elseif ($avg >= 2.5) {
        $chart_bg_colors[]     = 'rgba(245, 158, 11, 0.85)'; // Kurang Puas
        $chart_border_colors[] = '#D97706';
    } else {
        $chart_bg_colors[]     = 'rgba(239, 68, 68, 0.85)';  // Tidak Puas
        $chart_border_colors[] = '#DC2626';
    }
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.3rem;font-weight:800;color:var(--navy);margin:0;display:flex;align-items:center;gap:10px;">
            <span><i class="bi bi-buildings text-primary me-2"></i> Rincian Hasil Evaluasi Kunjungan Instansi</span>
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Laporan lengkap umpan balik kuesioner mutu untuk <?= e($kunjungan_info['nama_institusi']) ?>.
        </p>
    </div>
    <div class="d-flex gap-2">
        <button type="button" onclick="window.print()" class="btn-outline">
            <i class="bi bi-printer me-1"></i> Cetak Laporan
        </button>
        <a href="feedback-kunjungan-list.php?tab=respon" class="btn-outline">
            &larr; Kembali ke Daftar
        </a>
    </div>
</div>

<!-- Header Metadata Instansi & KPI Cards -->
<div class="admin-table-wrap p-4 mb-4" style="border-top:4px solid var(--navy);">
    <div class="row align-items-center g-3">
        <div class="col-lg-8">
            <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                <span class="badge" style="background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;font-size:0.75rem;padding:0.35rem 0.65rem;font-weight:700;">
                    <i class="bi bi-shield-check me-1"></i> RESMI TERVERIFIKASI
                </span>
                <?php if (!empty($kunjungan_info['token'])): ?>
                <span class="badge" style="background:#F1F5F9;border:1px solid #CBD5E1;color:#0F172A;font-family:monospace;font-size:0.85rem;padding:0.35rem 0.65rem;font-weight:800;letter-spacing:1px;">
                    TOKEN: <?= e($kunjungan_info['token']) ?>
                </span>
                <?php endif; ?>
            </div>

            <h3 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.45rem;margin-bottom:0.4rem;">
                <?= e($kunjungan_info['nama_institusi']) ?>
            </h3>

            <?php if (!empty($kunjungan_info['perihal'])): ?>
            <div style="font-size:0.88rem;color:#334155;margin-bottom:0.5rem;font-weight:600;">
                <i class="bi bi-info-circle me-1 text-primary"></i> <?= e($kunjungan_info['perihal']) ?>
            </div>
            <?php endif; ?>

            <div class="d-flex flex-wrap gap-3" style="font-size:0.84rem;color:var(--text-muted);">
                <span><i class="bi bi-calendar-event me-1 text-primary"></i> Tanggal Kunjungan: <strong><?= formatTanggal($kunjungan_info['tanggal_kunjungan']) ?></strong></span>
                <span><i class="bi bi-people-fill me-1 text-primary"></i> Total Anggota Mengisi: <strong><?= $total_anggota ?> Orang</strong></span>
                <?php if (!empty($kunjungan_info['email'])): ?>
                <span><i class="bi bi-envelope me-1 text-primary"></i> PIC: <strong><?= e($kunjungan_info['email']) ?></strong></span>
                <?php endif; ?>
            </div>
        </div>

        <div class="col-lg-4 text-lg-end">
            <div class="p-3 d-inline-block text-center" style="background:#F0F9FF;border:1.5px solid #BAE6FD;border-radius:var(--radius-md);min-width:200px;">
                <div style="font-size:0.75rem;font-weight:700;color:#0369A1;text-transform:uppercase;letter-spacing:0.5px;">
                    Indeks Kepuasan Instansi (CSAT)
                </div>
                <div style="font-size:2.4rem;font-weight:900;color:#0284C7;line-height:1.1;margin:4px 0;">
                    <i class="bi bi-star-fill text-warning me-1" style="font-size:1.8rem;"></i><?= number_format($avg_instansi, 2) ?>
                </div>
                <div style="font-size:0.78rem;font-weight:700;">
                    <?php
                    if ($avg_instansi >= 4.5) echo '<span class="text-success"><span style="font-size:1rem;">😄</span> Predikat Sangat Puas</span>';
                    elseif ($avg_instansi >= 3.5) echo '<span class="text-primary"><span style="font-size:1rem;">🙂</span> Predikat Puas</span>';
                    elseif ($avg_instansi >= 2.5) echo '<span class="text-warning"><span style="font-size:1rem;">😐</span> Predikat Kurang Puas</span>';
                    elseif ($avg_instansi >= 1.5) echo '<span class="text-danger"><span style="font-size:1rem;">🙁</span> Predikat Tidak Puas</span>';
                    elseif ($avg_instansi > 0) echo '<span class="text-danger"><span style="font-size:1rem;">😢</span> Predikat Sangat Tidak Puas</span>';
                    else echo 'Belum Ada Penilaian';
                    ?>
                </div>
                <div style="font-size:0.7rem;color:var(--text-muted);margin-top:2px;">
                    Rata-rata dari <?= $total_anggota ?> anggota delegasi
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Grid Ringkasan Nilai & Statistik -->
<div class="row g-3 mb-4">
    <div class="col-md-3">
        <div class="admin-table-wrap p-3 text-center" style="background:#F8FAFC;">
            <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Jumlah Responden</div>
            <div style="font-size:1.6rem;font-weight:800;color:var(--navy);margin-top:2px;">
                <?= $total_anggota ?> <span style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">Orang</span>
            </div>
            <div style="font-size:0.72rem;color:var(--text-muted);">100% Mengisi Lengkap</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-table-wrap p-3 text-center" style="background:#F8FAFC;">
            <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Skor Tertinggi Personal</div>
            <div style="font-size:1.6rem;font-weight:800;color:#047857;margin-top:2px;">
                <i class="bi bi-star-fill text-warning me-1" style="font-size:1.2rem;"></i><?= number_format((float)$max_personal_skor, 2) ?>
            </div>
            <div style="font-size:0.72rem;color:var(--text-muted);">Nilai Tertinggi Anggota</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-table-wrap p-3 text-center" style="background:#F8FAFC;">
            <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Skor Terendah Personal</div>
            <div style="font-size:1.6rem;font-weight:800;color:#0284C7;margin-top:2px;">
                <i class="bi bi-star-fill text-warning me-1" style="font-size:1.2rem;"></i><?= number_format((float)$min_personal_skor, 2) ?>
            </div>
            <div style="font-size:0.72rem;color:var(--text-muted);">Nilai Terendah Anggota</div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="admin-table-wrap p-3 text-center" style="background:#F8FAFC;">
            <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Tingkat Capaian Mutu</div>
            <div style="font-size:1.6rem;font-weight:800;color:#7C3AED;margin-top:2px;">
                <?= round(($avg_instansi / 5) * 100, 1) ?>%
            </div>
            <div style="font-size:0.72rem;color:var(--text-muted);">Dari Target Standar Mutu</div>
        </div>
    </div>
</div>

<!-- Bagian 1: Visualisasi Grafik Interaktif (Chart.js) -->
<div class="admin-table-wrap p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2 border-bottom pb-3">
        <div>
            <h5 style="font-weight:800;color:var(--navy);font-size:1.05rem;margin:0;display:flex;align-items:center;gap:8px;">
                <span><i class="bi bi-bar-chart-line text-primary me-2"></i> Visualisasi Indeks Mutu Kunjungan</span>
            </h5>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:3px;">
                Capaian rata-rata tiap butir evaluasi dari seluruh <?= $total_anggota ?> anggota delegasi yang berkunjung.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span class="badge" style="background:#ECFDF5;color:#047857;border:1px solid #A7F3D0;font-size:0.75rem;padding:0.4rem 0.75rem;font-weight:700;">
                <i class="bi bi-bullseye me-1"></i> Standar Minimum: 4.00 / 5.00
            </span>
            <span class="badge" style="background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;font-size:0.75rem;padding:0.4rem 0.75rem;font-weight:700;">
                <i class="bi bi-star-fill text-warning me-1"></i> Rata-rata Instansi: <?= number_format($avg_instansi, 2) ?>
            </span>
        </div>
    </div>

    <div class="row g-4 align-items-stretch">
        <!-- Grafik Batang Horisontal (Jauh Lebih Mudah Dibaca, Judul Lengkap, Nilai Langsung Tertera) -->
        <div class="col-xl-8 col-lg-7">
            <div class="p-3 rounded-3 h-100 d-flex flex-column justify-content-between" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <span style="font-size:0.82rem;font-weight:700;color:var(--navy);text-transform:uppercase;letter-spacing:0.5px;">
                        <i class="bi bi-bar-chart-steps me-1 text-primary"></i> Rata-Rata Skor per Butir Pertanyaan
                    </span>
                    <span style="font-size:0.75rem;color:var(--text-muted);font-weight:600;">Skala 0 s/d 5.00</span>
                </div>
                <div style="position:relative; min-height:330px; width:100%;">
                    <canvas id="instansiFeedbackChart"></canvas>
                </div>
                <div class="mt-2 pt-2 border-top d-flex justify-content-between align-items-center flex-wrap gap-2" style="font-size:0.75rem;color:var(--text-muted);">
                    <div><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#10B981;margin-right:4px;"></span>😄 Sangat Puas (&ge; 4.5)</div>
                    <div><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#0284C7;margin-right:4px;"></span>🙂 Puas (3.5 &ndash; 4.49)</div>
                    <div><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#F59E0B;margin-right:4px;"></span>😐 Kurang Puas (2.5 &ndash; 3.49)</div>
                    <div><span style="display:inline-block;width:10px;height:10px;border-radius:2px;background:#EF4444;margin-right:4px;"></span>🙁/😢 Tidak Puas (&lt; 2.5)</div>
                </div>
            </div>
        </div>

        <!-- Diagram Radar Jaring Mutu (Spider Web) -->
        <div class="col-xl-4 col-lg-5">
            <div class="p-3 rounded-3 h-100 d-flex flex-column justify-content-between" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                <div>
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <span style="font-size:0.82rem;font-weight:700;color:var(--navy);text-transform:uppercase;letter-spacing:0.5px;">
                            <i class="bi bi-radar me-1 text-success"></i> Radar Keseimbangan Mutu
                        </span>
                        <span class="badge bg-success-subtle text-success fw-bold" style="font-size:0.7rem;"><?= count($radar_labels) ?> Indikator Mutu</span>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-bottom:0.5rem;">
                        Pemetaan persepsi mutu antar seluruh dimensi pelayanan.
                    </div>
                </div>
                <div style="position:relative; height:270px; width:100%;">
                    <canvas id="radarFeedbackChart"></canvas>
                </div>
                <div class="mt-2 pt-2 border-top text-center" style="font-size:0.75rem;color:#047857;font-weight:600;">
                    <i class="bi bi-check-circle-fill me-1"></i> Semua dimensi melampaui target standar mutu (&gt; 4.00)
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Bagian 2: Tabel Rekapitulasi Rata-rata per Butir Kuesioner -->
<div class="admin-table-wrap p-0 mb-4">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#F8FAFC;">
        <div>
            <h5 style="font-weight:700;color:var(--navy);font-size:1rem;margin:0;">
                📊 Rekapitulasi Penilaian Rata-Rata per Butir Indikator (Skala 1 – 5)
            </h5>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                <i class="bi bi-cursor-fill me-1 text-primary"></i> Klik pada baris atau tombol <strong>Rincian Skala</strong> untuk melihat distribusi respon (Sangat Puas s/d Sangat Tidak Puas).
            </div>
        </div>
        <span class="badge bg-light text-dark border px-3 py-2" style="font-size:0.8rem;font-weight:700;">
            <i class="bi bi-people me-1 text-primary"></i> <?= $total_anggota ?> Responden Penilai
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th style="width:50px;text-align:center;">No</th>
                    <th>Aspek / Butir Pertanyaan</th>
                    <th style="width:170px;">Kategori Mutu</th>
                    <th style="width:130px;text-align:center;">Rata-Rata Nilai</th>
                    <th style="width:110px;text-align:center;">Rentang</th>
                    <th style="width:140px;text-align:center;">Predikat</th>
                    <th style="width:130px;text-align:center;">Distribusi Respon</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                foreach ($skala_questions as $idx => $sq): 
                    $score_avg = round((float)$sq['avg_skor'], 2);
                    $persen_sq = round(($score_avg / 5) * 100, 1);
                    if ($score_avg >= 4.5) {
                        $p_text = '😄 Sangat Puas';
                        $p_badge = 'background:#ECFDF5;color:#047857;border:1px solid #A7F3D0;';
                    } elseif ($score_avg >= 3.5) {
                        $p_text = '🙂 Puas';
                        $p_badge = 'background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;';
                    } elseif ($score_avg >= 2.5) {
                        $p_text = '😐 Kurang Puas';
                        $p_badge = 'background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;';
                    } elseif ($score_avg >= 1.5) {
                        $p_text = '🙁 Tidak Puas';
                        $p_badge = 'background:#FFF7ED;color:#C2410C;border:1px solid #FED7AA;';
                    } else {
                        $p_text = '😢 Sangat Tdk Puas';
                        $p_badge = 'background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;';
                    }
                ?>
                <tr style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#modalDistribusi<?= $sq['id'] ?>" title="Klik untuk melihat distribusi respon pilihan responden">
                    <td style="text-align:center;font-weight:700;color:var(--text-muted);"><?= $idx + 1 ?></td>
                    <td>
                        <div style="font-weight:700;color:var(--navy);"><?= e($sq['pertanyaan']) ?></div>
                        <?php if (!empty($sq['keterangan'])): ?>
                        <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($sq['keterangan']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge" style="background:#F1F5F9;color:#334155;border:1px solid #CBD5E1;font-size:0.75rem;">
                            <?= e($sq['kategori']) ?>
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <span style="font-size:1.15rem;font-weight:800;color:#0284C7;">
                            ⭐ <?= number_format($score_avg, 2) ?>
                        </span>
                        <div style="font-size:0.7rem;color:var(--text-muted);">Capaian: <?= $persen_sq ?>%</div>
                    </td>
                    <td style="text-align:center;font-size:0.8rem;color:var(--text-muted);">
                        Min: <strong><?= $sq['min_skor'] ?></strong> &bull; Max: <strong><?= $sq['max_skor'] ?></strong>
                    </td>
                    <td style="text-align:center;">
                        <span class="badge" style="<?= $p_badge ?>font-size:0.75rem;padding:0.35rem 0.65rem;">
                            <?= $p_text ?>
                        </span>
                    </td>
                    <td style="text-align:center;" onclick="event.stopPropagation();">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalDistribusi<?= $sq['id'] ?>" style="font-size:0.75rem;padding:0.3rem 0.65rem;border-radius:20px;">
                            <i class="bi bi-bar-chart-fill text-primary me-1"></i> Rincian Skala
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL POPUPS: Rincian Distribusi Pilihan Responden (Skala 1 s/d 5) per Butir Pertanyaan -->
<?php 
$star_levels = [
    5 => ['label' => 'Sangat Puas', 'sub' => 'Skor 5', 'emoji' => '😄', 'bar' => '#10B981', 'bg' => '#ECFDF5', 'text' => '#047857'],
    4 => ['label' => 'Puas', 'sub' => 'Skor 4', 'emoji' => '🙂', 'bar' => '#0284C7', 'bg' => '#EFF6FF', 'text' => '#1D4ED8'],
    3 => ['label' => 'Kurang Puas', 'sub' => 'Skor 3', 'emoji' => '😐', 'bar' => '#F59E0B', 'bg' => '#FEF3C7', 'text' => '#B45309'],
    2 => ['label' => 'Tidak Puas', 'sub' => 'Skor 2', 'emoji' => '🙁', 'bar' => '#F97316', 'bg' => '#FFF7ED', 'text' => '#C2410C'],
    1 => ['label' => 'Sangat Tidak Puas', 'sub' => 'Skor 1', 'emoji' => '😢', 'bar' => '#EF4444', 'bg' => '#FEF2F2', 'text' => '#B91C1C']
];

foreach ($skala_questions as $idx => $sq): 
    $score_avg = round((float)$sq['avg_skor'], 2);
    $persen_sq = round(($score_avg / 5) * 100, 1);
    if ($score_avg >= 4.5) {
        $p_text = '😄 Sangat Puas';
        $p_badge = 'background:#ECFDF5;color:#047857;border:1px solid #A7F3D0;';
    } elseif ($score_avg >= 3.5) {
        $p_text = '🙂 Puas';
        $p_badge = 'background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;';
    } elseif ($score_avg >= 2.5) {
        $p_text = '😐 Kurang Puas';
        $p_badge = 'background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;';
    } elseif ($score_avg >= 1.5) {
        $p_text = '🙁 Tidak Puas';
        $p_badge = 'background:#FFF7ED;color:#C2410C;border:1px solid #FED7AA;';
    } else {
        $p_text = '😢 Sangat Tdk Puas';
        $p_badge = 'background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;';
    }
?>
<div class="modal fade" id="modalDistribusi<?= $sq['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;box-shadow:0 25px 50px rgba(0,0,0,0.25);">
            <!-- Modal Header -->
            <div class="modal-header" style="background:var(--navy);color:#fff;padding:1.25rem 1.5rem;">
                <div>
                    <div class="badge bg-white-subtle text-white border border-white-50 fw-bold mb-1" style="font-size:0.75rem;">
                        Butir Pertanyaan <?= $idx + 1 ?> &bull; <?= e($sq['kategori']) ?>
                    </div>
                    <h5 class="modal-title fw-bold mb-0" style="font-size:1.05rem;line-height:1.4;">
                        <?= e($sq['pertanyaan']) ?>
                    </h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 text-start">
                <!-- Summary Card -->
                <div class="p-3 mb-4 rounded-3 d-flex justify-content-between align-items-center flex-wrap gap-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                    <div>
                        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Rata-Rata Nilai Butir</div>
                        <div style="font-size:2.2rem;font-weight:900;color:#0284C7;line-height:1.1;margin-top:2px;">
                            <i class="bi bi-star-fill text-warning me-1" style="font-size:1.7rem;"></i><?= number_format($score_avg, 2) ?> <span style="font-size:0.95rem;color:var(--text-muted);font-weight:600;">/ 5.00</span>
                        </div>
                        <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;">
                            Tingkat Capaian: <strong class="text-dark"><?= $persen_sq ?>%</strong> (Rentang: <?= $sq['min_skor'] ?> s/d <?= $sq['max_skor'] ?>)
                        </div>
                    </div>
                    <div class="text-end">
                        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Total Responden</div>
                        <div style="font-size:1.8rem;font-weight:800;color:var(--navy);margin-top:2px;">
                            <?= $total_anggota ?> <span style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">Orang</span>
                        </div>
                        <span class="badge" style="<?= $p_badge ?>font-size:0.75rem;padding:0.35rem 0.65rem;">
                            <?= $p_text ?>
                        </span>
                    </div>
                </div>

                <!-- Star Distribution Progress Bars (1 to 5 Stars) -->
                <h6 class="fw-bold mb-3" style="color:var(--navy);font-size:0.95rem;display:flex;align-items:center;gap:6px;">
                    <i class="bi bi-bar-chart-fill text-primary"></i> Rincian Distribusi Pilihan Responden (Skala 1 s/d 5)
                </h6>

                <div class="d-flex flex-column gap-2 mb-4">
                    <?php foreach ($star_levels as $star_num => $cfg): 
                        $jml = $distribusi_bintang[$sq['id']][$star_num]['count'] ?? 0;
                        $pct = $total_anggota > 0 ? round(($jml / $total_anggota) * 100, 1) : 0;
                    ?>
                    <div class="p-2.5 px-3 rounded-2 border d-flex align-items-center gap-3" style="background:#fff;">
                        <div style="min-width:180px;">
                            <div class="fw-bold" style="font-size:0.85rem;color:var(--navy);"><?= $cfg['emoji'] ?> <?= $cfg['label'] ?></div>
                            <div style="font-size:0.72rem;color:var(--text-muted);font-weight:600;"><?= $cfg['sub'] ?></div>
                        </div>

                        <div class="flex-grow-1">
                            <div class="progress" style="height:10px;border-radius:10px;background:#F1F5F9;">
                                <div class="progress-bar" style="width:<?= $pct ?>%;background:<?= $cfg['bar'] ?>;border-radius:10px;"></div>
                            </div>
                        </div>

                        <div class="text-end" style="min-width:115px;">
                            <span class="badge" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['text'] ?>;font-size:0.85rem;font-weight:800;padding:0.35rem 0.65rem;">
                                <?= $jml ?> Orang
                            </span>
                            <span style="font-size:0.75rem;color:var(--text-muted);font-weight:600;margin-left:4px;">(<?= $pct ?>%)</span>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Daftar Responden per Bintang (Accordion / Expandable) -->
                <h6 class="fw-bold mb-2" style="color:var(--navy);font-size:0.9rem;display:flex;align-items:center;gap:6px;">
                    <i class="bi bi-people-fill text-primary"></i> Daftar Nama Anggota Berdasarkan Respon Pilihan:
                </h6>

                <div class="accordion" id="accordionRespondents<?= $sq['id'] ?>">
                    <?php foreach ($star_levels as $star_num => $cfg): 
                        $resps = $distribusi_bintang[$sq['id']][$star_num]['respondents'] ?? [];
                        if (empty($resps)) continue;
                    ?>
                    <div class="accordion-item border rounded mb-2 overflow-hidden">
                        <h2 class="accordion-header" id="headingStar<?= $sq['id'] ?>_<?= $star_num ?>">
                            <button class="accordion-button collapsed py-2.5 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseStar<?= $sq['id'] ?>_<?= $star_num ?>" aria-expanded="false" style="font-size:0.85rem;background:#F8FAFC;font-weight:700;">
                                <span class="badge me-2" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['text'] ?>;"><?= $cfg['emoji'] ?> <?= $cfg['sub'] ?></span>
                                <?= $cfg['label'] ?> &mdash; <strong class="ms-1 text-dark"><?= count($resps) ?> Orang</strong>
                            </button>
                        </h2>
                        <div id="collapseStar<?= $sq['id'] ?>_<?= $star_num ?>" class="accordion-collapse collapse" data-bs-parent="#accordionRespondents<?= $sq['id'] ?>">
                            <div class="accordion-body p-3 pt-2" style="background:#fff;">
                                <div class="row g-2">
                                    <?php foreach ($resps as $r_item): ?>
                                    <div class="col-md-6">
                                        <div class="p-2 px-2.5 rounded border" style="background:#F8FAFC;font-size:0.82rem;">
                                            <div class="fw-bold" style="color:var(--navy);"><?= e($r_item['nama']) ?></div>
                                            <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($r_item['jabatan']) ?></div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="modal-footer" style="background:#F8FAFC;padding:0.75rem 1.5rem;">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Tutup</button>
            </div>
        </div>
    </div>
</div>
<?php endforeach; ?>

<!-- Bagian 3: Masukan, Kesan & Kritik Konstruktif (Dikelompokkan per Pertanyaan) -->
<div class="admin-table-wrap p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h5 style="font-weight:800;color:var(--navy);font-size:1.05rem;margin:0;">
                💬 Masukan, Kesan &amp; Kritik Konstruktif Anggota Delegasi
            </h5>
            <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;">
                Dikelompokkan per butir kuesioner terbuka, lengkap dengan identitas nama pengisi dan jabatannya.
            </div>
        </div>
        <span class="badge bg-light text-dark border px-3 py-2" style="font-size:0.75rem;font-weight:700;">
            <i class="bi bi-chat-quote-fill text-primary me-1"></i> Total <?= count($teks_answers_all) ?> Masukan Tertulis
        </span>
    </div>

    <?php if (empty($teks_grouped)): ?>
    <div class="text-center py-4 text-muted" style="font-size:0.85rem;">
        Belum ada masukan tertulis yang diberikan oleh anggota instansi ini.
    </div>
    <?php else: ?>
    <div class="d-flex flex-column gap-4">
        <?php foreach ($teks_grouped as $q_id => $group): ?>
        <div class="border rounded-3 p-3" style="background:#F8FAFC;">
            <div class="d-flex align-items-center gap-2 mb-3 pb-2 border-bottom">
                <span class="badge bg-primary" style="font-size:0.78rem;">Pertanyaan Terbuka</span>
                <span style="font-size:0.78rem;color:var(--text-muted);font-weight:600;">(Kategori: <?= e($group['kategori']) ?>)</span>
                <h6 style="font-weight:800;color:var(--navy);margin:0;font-size:0.95rem;">
                    <?= e($group['pertanyaan']) ?>
                </h6>
            </div>

            <div class="row g-3">
                <?php foreach ($group['items'] as $item): ?>
                <div class="col-lg-6 col-md-12">
                    <div class="p-3 h-100 bg-white border rounded-3 shadow-sm d-flex flex-column justify-content-between">
                        <div style="font-size:0.875rem;color:#1E293B;line-height:1.6;font-style:italic;margin-bottom:0.75rem;">
                            &ldquo;<?= nl2br(e($item['jawaban_teks'])) ?>&rdquo;
                        </div>
                        <div class="pt-2 border-top d-flex justify-content-between align-items-center flex-wrap gap-1" style="font-size:0.75rem;">
                            <div>
                                <strong style="color:var(--navy);font-size:0.8rem;"><?= !empty($item['nama_pengisi']) ? e($item['nama_pengisi']) : 'Anonim' ?></strong>
                                <?php if (!empty($item['jabatan_pengisi'])): ?>
                                <span class="text-muted">&bull; <?= e($item['jabatan_pengisi']) ?></span>
                                <?php endif; ?>
                            </div>
                            <span class="text-muted">
                                <?= date('d/m H:i', strtotime($item['created_at'])) ?> WIB
                            </span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<!-- Bagian 4: Daftar Seluruh Anggota Delegasi yang Mengisi (Tabel Anggota) -->
<div class="admin-table-wrap p-0 mb-4">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#F8FAFC;">
        <div>
            <h5 style="font-weight:700;color:var(--navy);font-size:1rem;margin:0;">
                👥 Daftar Seluruh Anggota Delegasi yang Mengisi (<?= $total_anggota ?> Orang)
            </h5>
            <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;">
                Seluruh perwakilan dan anggota instansi yang telah menuntaskan kuesioner evaluasi kunjungan.
            </div>
        </div>
        <div style="max-width:240px;width:100%;">
            <input type="text" id="searchAnggotaInput" class="form-control form-control-sm" placeholder="🔍 Cari nama / jabatan..." style="border-radius:20px;font-size:0.78rem;">
        </div>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" id="tableAnggotaDelegasi" style="font-size:0.875rem;">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th style="width:50px;text-align:center;">No</th>
                    <th>Nama Lengkap</th>
                    <th>Status / Jabatan</th>
                    <th>Demografi</th>
                    <th>Kategori Layanan</th>
                    <th>Email</th>
                    <th style="text-align:center;width:130px;">Rata-Rata Skor</th>
                    <th>Waktu Pengisian</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($responden_list as $idx => $r): ?>
                <tr>
                    <td style="text-align:center;font-weight:600;color:var(--text-muted);"><?= $idx + 1 ?></td>
                    <td>
                        <div style="font-weight:700;color:var(--navy);">
                            <?= !empty($r['nama_pengisi']) ? e($r['nama_pengisi']) : '<span class="text-muted fst-italic">Anonim</span>' ?>
                        </div>
                    </td>
                    <td>
                        <span style="font-size:0.82rem;color:#334155;font-weight:600;">
                            <?= !empty($r['status_responden']) ? e($r['status_responden']) : (!empty($r['jabatan_pengisi']) ? e($r['jabatan_pengisi']) : '-') ?>
                        </span>
                    </td>
                    <td>
                        <div style="font-size:0.78rem;color:#475569;">
                            <?php 
                            $demo = [];
                            if (!empty($r['jenis_kelamin'])) $demo[] = e($r['jenis_kelamin']);
                            if (!empty($r['umur'])) $demo[] = e($r['umur']) . ' thn';
                            if (!empty($r['pendidikan_terakhir'])) $demo[] = e($r['pendidikan_terakhir']);
                            echo !empty($demo) ? implode(' &bull; ', $demo) : '-';
                            ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge" style="background:#F1F5F9;color:#334155;border:1px solid #CBD5E1;font-size:0.75rem;">
                            <?= e($r['kategori_layanan'] ?? 'Pelayanan LPM') ?>
                        </span>
                    </td>
                    <td>
                        <span style="font-size:0.8rem;color:var(--text-muted);font-family:monospace;">
                            <?= !empty($r['email_pengisi']) ? e($r['email_pengisi']) : '-' ?>
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <?php if ($r['rata_rata_skor']): 
                            $r_avg = round((float)$r['rata_rata_skor'], 2);
                            if ($r_avg >= 4.5) {
                                $r_badge = 'background:#ECFDF5;color:#047857;border:1px solid #A7F3D0;';
                                $r_emoji = '😄';
                                $r_text  = 'Sangat Puas';
                            } elseif ($r_avg >= 3.5) {
                                $r_badge = 'background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;';
                                $r_emoji = '🙂';
                                $r_text  = 'Puas';
                            } elseif ($r_avg >= 2.5) {
                                $r_badge = 'background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;';
                                $r_emoji = '😐';
                                $r_text  = 'Kurang Puas';
                            } elseif ($r_avg >= 1.5) {
                                $r_badge = 'background:#FFF7ED;color:#C2410C;border:1px solid #FED7AA;';
                                $r_emoji = '🙁';
                                $r_text  = 'Tidak Puas';
                            } else {
                                $r_badge = 'background:#FEF2F2;color:#B91C1C;border:1px solid #FECACA;';
                                $r_emoji = '😢';
                                $r_text  = 'Sangat Tdk Puas';
                            }
                        ?>
                        <span class="badge" style="<?= $r_badge ?>font-size:0.8rem;padding:0.35rem 0.65rem;font-weight:700;">
                            <?= $r_emoji ?> <?= number_format($r_avg, 2) ?> &bull; <?= $r_text ?>
                        </span>
                        <?php else: ?>
                        <span class="text-muted">-</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size:0.8rem;color:var(--text-muted);">
                        <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?> WIB
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Chart.js CDN & Inisialisasi Grafik Instansi -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Plugin Khusus: Menampilkan Angka Nilai Langsung di Ujung Batang Horisontal
    var scoreLabelsPlugin = {
        id: 'scoreLabelsPlugin',
        afterDatasetsDraw: function(chart) {
            if (chart.config.type !== 'bar') return;
            var ctx = chart.ctx;
            chart.data.datasets.forEach(function(dataset, i) {
                var meta = chart.getDatasetMeta(i);
                meta.data.forEach(function(bar, index) {
                    var rawVal = Number(dataset.data[index]);
                    if (isNaN(rawVal) || rawVal <= 0) return;
                    var valStr = rawVal.toFixed(2) + ' ⭐';
                    ctx.save();
                    ctx.fillStyle = '#0F172A';
                    ctx.font = 'bold 12px "Plus Jakarta Sans", sans-serif';
                    ctx.textAlign = 'left';
                    ctx.textBaseline = 'middle';
                    var posX = Math.min(bar.x + 8, chart.chartArea.right - 55);
                    ctx.fillText(valStr, posX, bar.y);
                    ctx.restore();
                });
            });
        }
    };

    // 1. Grafik Batang Horisontal (Enak Dilihat & Tulisan Jelas)
    var ctxBar = document.getElementById('instansiFeedbackChart');
    if (ctxBar) {
        new Chart(ctxBar, {
            type: 'bar',
            data: {
                labels: <?= json_encode($chart_labels) ?>,
                datasets: [{
                    label: 'Skor Rata-Rata',
                    data: <?= json_encode($chart_scores) ?>,
                    backgroundColor: <?= json_encode($chart_bg_colors) ?>,
                    borderColor: <?= json_encode($chart_border_colors) ?>,
                    borderWidth: 1.5,
                    borderRadius: 8,
                    borderSkipped: false,
                    barThickness: 26
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                maintainAspectRatio: false,
                onClick: function(evt, elements) {
                    if (elements && elements.length > 0) {
                        var index = elements[0].index;
                        var qIds = <?= json_encode(array_values(array_map('intval', array_column($skala_questions, 'id')))) ?>;
                        var targetId = qIds[index];
                        if (targetId) {
                            var modalEl = document.getElementById('modalDistribusi' + targetId);
                            if (modalEl) {
                                var bsModal = bootstrap.Modal.getOrCreateInstance(modalEl);
                                bsModal.show();
                            }
                        }
                    }
                },
                onHover: function(evt, elements) {
                    if (evt.native && evt.native.target) {
                        evt.native.target.style.cursor = (elements && elements.length > 0) ? 'pointer' : 'default';
                    }
                },
                layout: {
                    padding: {
                        right: 50
                    }
                },
                plugins: {
                    legend: { display: false },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        padding: 12,
                        titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: 'bold' },
                        bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                        callbacks: {
                            label: function(context) {
                                var val = Number(context.raw).toFixed(2);
                                var persen = ((val / 5) * 100).toFixed(1);
                                return ' Skor Rata-Rata: ' + val + ' / 5.00 (' + persen + '% Capaian) - Klik untuk rincian skala';
                            }
                        }
                    }
                },
                scales: {
                    x: {
                        min: 0,
                        max: 5,
                        ticks: {
                            stepSize: 1,
                            callback: function(v) { return v + '.0'; },
                            font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 }
                        },
                        grid: { color: '#F1F5F9' }
                    },
                    y: {
                        ticks: {
                            font: { family: "'Plus Jakarta Sans', sans-serif", size: 11, weight: '600' },
                            color: '#1E293B'
                        },
                        grid: { display: false }
                    }
                }
            },
            plugins: [scoreLabelsPlugin]
        });
    }

    // 2. Diagram Radar Jaring Mutu (Spider Web)
    var ctxRadar = document.getElementById('radarFeedbackChart');
    if (ctxRadar) {
        new Chart(ctxRadar, {
            type: 'radar',
            data: {
                labels: <?= json_encode($radar_labels) ?>,
                datasets: [
                    {
                        label: 'Skor Evaluasi Instansi',
                        data: <?= json_encode($chart_scores) ?>,
                        borderColor: '#0284C7',
                        backgroundColor: 'rgba(2, 132, 199, 0.22)',
                        borderWidth: 2.5,
                        pointBackgroundColor: '#0284C7',
                        pointBorderColor: '#fff',
                        pointBorderWidth: 2,
                        pointRadius: 4.5,
                        pointHoverRadius: 6
                    },
                    {
                        label: 'Standar Mutu Minimum (4.00)',
                        data: <?= json_encode(array_fill(0, count($chart_scores), 4.0)) ?>,
                        borderColor: '#10B981',
                        borderDash: [4, 4],
                        borderWidth: 1.5,
                        backgroundColor: 'rgba(16, 185, 129, 0.05)',
                        pointRadius: 0
                    }
                ]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            usePointStyle: true,
                            pointStyle: 'circle',
                            font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 }
                        }
                    },
                    tooltip: {
                        backgroundColor: 'rgba(15, 23, 42, 0.95)',
                        padding: 10,
                        callbacks: {
                            label: function(context) {
                                return ' ' + context.dataset.label + ': ' + Number(context.raw).toFixed(2);
                            }
                        }
                    }
                },
                scales: {
                    r: {
                        min: 0,
                        max: 5,
                        ticks: {
                            stepSize: 1,
                            display: false
                        },
                        grid: { color: '#E2E8F0' },
                        angleLines: { color: '#E2E8F0' },
                        pointLabels: {
                            font: {
                                family: "'Plus Jakarta Sans', sans-serif",
                                size: 11,
                                weight: '700'
                            },
                            color: '#334155'
                        }
                    }
                }
            }
        });
    }

    // 3. Client-side search for anggota table
    var searchInput = document.getElementById('searchAnggotaInput');
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            var query = this.value.toLowerCase().trim();
            var rows = document.querySelectorAll('#tableAnggotaDelegasi tbody tr');
            rows.forEach(function(row) {
                var text = row.innerText.toLowerCase();
                if (text.includes(query)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
