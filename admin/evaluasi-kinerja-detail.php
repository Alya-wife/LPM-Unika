<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Rincian Rekap Evaluasi Kinerja Personel';
$current_admin    = 'evaluasi-kinerja-detail';
$db = getDB();

$personel_id = (int)($_GET['id'] ?? 0);
$ta_filter   = trim($_GET['ta'] ?? getPengaturan('evaluasi_tahun_akademik_aktif', '2025/2026'));

if ($personel_id <= 0) {
    redirect(SITE_URL . '/admin/evaluasi-kinerja-rekap.php');
    exit;
}

// Ambil data personel
$stmtP = $db->prepare("SELECT * FROM evaluasi_peserta WHERE id = ? LIMIT 1");
$stmtP->execute([$personel_id]);
$personel = $stmtP->fetch(PDO::FETCH_ASSOC);

if (!$personel) {
    redirect(SITE_URL . '/admin/evaluasi-kinerja-rekap.php');
    exit;
}

// 1. Ambil Jawaban Evaluasi Diri
$stmtDiri = $db->prepare("SELECT q.nomor_urut, q.bagian, q.pertanyaan, ans.jawaban_teks 
                          FROM evaluasi_pertanyaan q 
                          LEFT JOIN evaluasi_diri_jawaban ans ON ans.pertanyaan_id = q.id AND ans.peserta_id = ? AND ans.tahun_akademik = ?
                          WHERE q.kategori = 'diri'
                          ORDER BY q.nomor_urut ASC");
$stmtDiri->execute([$personel_id, $ta_filter]);
$jawabanDiri = $stmtDiri->fetchAll(PDO::FETCH_ASSOC);

// Cek status submission diri
$stmtSubDiri = $db->prepare("SELECT * FROM evaluasi_submission WHERE penilai_id = ? AND tahun_akademik = ? AND tipe_evaluasi = 'diri' LIMIT 1");
$stmtSubDiri->execute([$personel_id, $ta_filter]);
$subDiriInfo = $stmtSubDiri->fetch(PDO::FETCH_ASSOC);

// 2. Ambil Rekap Penilaian Antar Personel (Peer Review)
// A. Rata-rata Skor per Butir Skala
$stmtPeerSkor = $db->prepare("SELECT q.id, q.nomor_urut, q.bagian, q.pertanyaan, 
                              ROUND(AVG(NULLIF(ans.skor, 0)), 2) as avg_skor,
                              COUNT(NULLIF(ans.skor, 0)) as total_penilai_valid,
                              SUM(CASE WHEN ans.skor = 1 THEN 1 ELSE 0 END) as skor_1,
                              SUM(CASE WHEN ans.skor = 2 THEN 1 ELSE 0 END) as skor_2,
                              SUM(CASE WHEN ans.skor = 3 THEN 1 ELSE 0 END) as skor_3,
                              SUM(CASE WHEN ans.skor = 0 THEN 1 ELSE 0 END) as skor_na
                              FROM evaluasi_pertanyaan q 
                              LEFT JOIN evaluasi_antar_jawaban ans ON ans.pertanyaan_id = q.id AND ans.dinilai_id = ? AND ans.tahun_akademik = ?
                              WHERE q.kategori = 'antar_personel' AND q.tipe_jawaban = 'skala_3'
                              GROUP BY q.id
                              ORDER BY q.nomor_urut ASC");
$stmtPeerSkor->execute([$personel_id, $ta_filter]);
$peerSkorList = $stmtPeerSkor->fetchAll(PDO::FETCH_ASSOC);

// B. Masukan Terbuka (Q10, Q11, Q12)
$stmtPeerTeks = $db->prepare("SELECT q.id, q.nomor_urut, q.pertanyaan, ans.jawaban_teks, p.nama as penilai_nama
                              FROM evaluasi_pertanyaan q 
                              JOIN evaluasi_antar_jawaban ans ON ans.pertanyaan_id = q.id AND ans.dinilai_id = ? AND ans.tahun_akademik = ?
                              LEFT JOIN evaluasi_peserta p ON p.id = ans.penilai_id
                              WHERE q.kategori = 'antar_personel' AND q.tipe_jawaban = 'teks' AND ans.jawaban_teks IS NOT NULL AND TRIM(ans.jawaban_teks) != ''
                              ORDER BY q.nomor_urut ASC, ans.id ASC");
$stmtPeerTeks->execute([$personel_id, $ta_filter]);
$peerTeksList = $stmtPeerTeks->fetchAll(PDO::FETCH_ASSOC);

// Group teks berdasarkan pertanyaan
$groupedPeerTeks = [];
foreach ($peerTeksList as $pt) {
    $groupedPeerTeks[$pt['nomor_urut']]['pertanyaan'] = $pt['pertanyaan'];
    $groupedPeerTeks[$pt['nomor_urut']]['jawaban'][]   = [
        'penilai' => $pt['penilai_nama'],
        'teks'    => $pt['jawaban_teks']
    ];
}

// 3. Ambil Penilaian oleh Kepala LPM
$stmtKepala = $db->prepare("SELECT q.nomor_urut, q.bagian, q.pertanyaan, ans.skor, ans.catatan 
                            FROM evaluasi_pertanyaan q 
                            LEFT JOIN evaluasi_kepala_jawaban ans ON ans.pertanyaan_id = q.id AND ans.dinilai_id = ? AND ans.tahun_akademik = ?
                            WHERE q.kategori = 'kepala'
                            ORDER BY q.nomor_urut ASC");
$stmtKepala->execute([$personel_id, $ta_filter]);
$jawabanKepala = $stmtKepala->fetchAll(PDO::FETCH_ASSOC);

// Hitung total rata-rata skor Kepala LPM
$scoresK = array_filter(array_column($jawabanKepala, 'skor'), fn($s) => $s !== null && (int)$s > 0);
$avgSkorKepala = !empty($scoresK) ? round(array_sum($scoresK) / count($scoresK), 2) : 0;

require_once __DIR__ . '/includes/admin-header.php';
?>

<!-- Print Stylesheet -->
<style>
@media print {
    body { background: #fff !important; font-size: 11pt !important; color: #000 !important; }
    .admin-sidebar, .admin-topbar, .admin-footer, .breadcrumb, .no-print, .btn, .nav-pills { display: none !important; }
    .admin-main { margin-left: 0 !important; padding: 0 !important; }
    .admin-table-wrap, .card { box-shadow: none !important; border: 1px solid #ccc !important; }
    .print-only-header { display: block !important; margin-bottom: 25px; border-bottom: 2px solid #000; padding-bottom: 12px; }
}
.print-only-header { display: none; }
</style>

<div class="row justify-content-center">
    <div class="col-xl-11 col-lg-12">
        
        <!-- Breadcrumb & Navigasi -->
        <div class="d-flex align-items-center justify-content-between mb-4 no-print flex-wrap gap-2">
            <div style="display:flex;align-items:center;gap:0.5rem;font-size:0.85rem;color:var(--text-muted);">
                <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
                <span>/</span>
                <a href="evaluasi-kinerja-rekap.php?ta=<?= urlencode($ta_filter) ?>" style="color:var(--text-muted);">Rekapitulasi Evaluasi</a>
                <span>/</span>
                <span style="color:var(--navy);">Rincian Personel</span>
            </div>
            <div class="d-flex gap-2">
                <a href="<?= SITE_URL ?>/admin/evaluasi-kinerja-rekap.php?ta=<?= urlencode($ta_filter) ?>" class="btn btn-sm btn-outline-secondary" style="border-radius:8px;">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Rekap
                </a>
                <button type="button" class="btn btn-sm btn-primary" onclick="window.print()" style="border-radius:8px;">
                    <i class="bi bi-printer-fill me-1"></i> Cetak / Print Lembar Rekap
                </button>
            </div>
        </div>

        <!-- Kop Surat Resmi LPM UNIKA (Muncul saat Print) -->
        <div class="print-only-header text-center">
            <h4 style="font-weight:bold;margin-bottom:2px;letter-spacing:0.5px;">UNIVERSITAS KATOLIK SOEGIJAPRANATA (SCU)</h4>
            <h3 style="font-weight:bold;margin-bottom:4px;color:#0A192F;">LEMBAGA PENJAMINAN MUTU (LPM)</h3>
            <p style="font-size:9pt;margin-bottom:0;color:#555;">Gedung Thomas Aquinas Lantai 5, Jl. Pawiyatan Luhur IV/1 Bendan Duwur Semarang 50234 | Email: lpm@unika.ac.id</p>
            <h5 style="margin-top:12px;font-weight:bold;text-decoration:underline;">LEMBAR LAPORAN REKAPITULASI EVALUASI KINERJA PERSONEL</h5>
            <p style="font-size:10pt;font-weight:600;margin-bottom:0;">Periode Penilaian: Tahun Akademik <?= e($ta_filter) ?></p>
        </div>

        <!-- Kartu Profil Personel -->
        <div class="card shadow-sm border-0 mb-4" style="border-radius:var(--radius-lg);">
            <div class="card-body p-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:62px;height:62px;border-radius:50%;background:linear-gradient(135deg, var(--navy), var(--purple));color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:1.5rem;">
                        <?php if (!empty($personel['avatar'])): ?>
                            <img src="<?= e($personel['avatar']) ?>" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                        <?php else: ?>
                            <?= strtoupper(substr($personel['nama'], 0, 1)) ?>
                        <?php endif; ?>
                    </div>
                    <div>
                        <div class="d-flex align-items-center gap-2 mb-1">
                            <h4 class="fw-bold mb-0" style="color:var(--navy);"><?= e($personel['nama']) ?></h4>
                            <?php if ($personel['peran'] === 'kepala'): ?>
                                <span class="badge" style="background:#FEF3C7;color:#92400E;border:1px solid #FCD34D;">Kepala LPM</span>
                            <?php else: ?>
                                <span class="badge" style="background:#E0E7FF;color:#3730A3;border:1px solid #C7D2FE;">Personel LPM</span>
                            <?php endif; ?>
                        </div>
                        <div class="text-muted small">
                            <i class="bi bi-briefcase me-1"></i> <?= e($personel['jabatan']) ?> &bull; <i class="bi bi-envelope me-1"></i> <?= e($personel['email']) ?>
                        </div>
                    </div>
                </div>

                <div class="text-end">
                    <div class="small text-muted text-uppercase fw-bold">Periode Evaluasi</div>
                    <div class="fs-5 fw-bold text-dark"><?= e($ta_filter) ?></div>
                    <div class="small">
                        Evaluasi Diri: 
                        <?php if ($subDiriInfo): ?>
                            <span class="badge bg-success">Terkirim (<?= date('d M Y', strtotime($subDiriInfo['submitted_at'])) ?>)</span>
                        <?php else: ?>
                            <span class="badge bg-secondary">Belum Kirim</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 3 Seksi Hasil Rekap Evaluasi -->

        <!-- SEKSI 1: EVALUASI DIRI SENDIRI (PENILAIAN PRIBADI) -->
        <div class="card shadow-sm border-0 mb-4" style="border-radius:var(--radius-lg);">
            <div class="card-header bg-white border-bottom p-4">
                <h5 class="fw-bold mb-1" style="color:var(--navy);">
                    <i class="bi bi-person-fill text-primary me-2"></i> 1. Lembar Evaluasi Diri (Penilaian Pribadi)
                </h5>
                <p class="text-muted small mb-0">Uraian capaian, tanggung jawab, dan refleksi pengembangan diri yang diisikan oleh personel bersangkutan.</p>
            </div>
            <div class="card-body p-4">
                <?php if (empty($subDiriInfo)): ?>
                    <div class="alert alert-light border text-center py-4 text-muted">
                        <i class="bi bi-info-circle display-6 d-block mb-2"></i>
                        Personel belum mengisi / mengirimkan lembar formulir Evaluasi Diri untuk Tahun Akademik <?= e($ta_filter) ?>.
                    </div>
                <?php else: ?>
                    <div class="row g-4">
                        <?php foreach ($jawabanDiri as $jd): ?>
                        <div class="col-12 pb-3 border-bottom">
                            <div class="fw-bold mb-1 d-flex align-items-start gap-2" style="color:var(--navy);font-size:0.95rem;">
                                <span class="badge bg-purple text-white" style="background:var(--purple);font-size:0.75rem;border-radius:4px;"><?= (int)$jd['nomor_urut'] ?></span>
                                <div>
                                    <div><?= e($jd['pertanyaan']) ?></div>
                                    <?php if (!empty($jd['bagian'])): ?>
                                        <div class="text-muted small fw-normal"><?= e($jd['bagian']) ?></div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="p-3 mt-2 rounded-2" style="background:#F8FAFC;border:1px solid #E2E8F0;font-size:0.9rem;line-height:1.6;white-space:pre-wrap;">
                                <?= !empty($jd['jawaban_teks']) ? e($jd['jawaban_teks']) : '<em class="text-muted">Tidak ada jawaban / dikosongkan</em>' ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($personel['peran'] !== 'kepala'): ?>
        <!-- SEKSI 2: HASIL PENILAIAN DARI REKAN KERJA (PEER REVIEW) -->
        <div class="card shadow-sm border-0 mb-4" style="border-radius:var(--radius-lg);">
            <div class="card-header bg-white border-bottom p-4">
                <h5 class="fw-bold mb-1" style="color:var(--navy);">
                    <i class="bi bi-people-fill text-info me-2"></i> 2. Rekapitulasi Penilaian Rekan Sejawat (360 Peer Review)
                </h5>
                <p class="text-muted small mb-0">Rata-rata skor skala 1–3 (1: Sering, 2: Kadang-kadang, 3: Terbatas) dan kumpulan masukan kualitatif dari rekan kerja.</p>
            </div>
            <div class="card-body p-4">
                <h6 class="fw-bold text-dark mb-3"><i class="bi bi-bar-chart-fill me-1 text-primary"></i> A. Rata-rata Skor per Butir Aspek</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light small text-uppercase text-muted">
                            <tr>
                                <th style="width:40px;">No</th>
                                <th>Aspek / Pernyataan Penilaian</th>
                                <th class="text-center" style="width:110px;">Skor 1 (Sering)</th>
                                <th class="text-center" style="width:110px;">Skor 2 (Kadang)</th>
                                <th class="text-center" style="width:110px;">Skor 3 (Terbatas)</th>
                                <th class="text-center" style="width:80px;">N/A</th>
                                <th class="text-center" style="width:130px;">Rata-rata Skor</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($peerSkorList as $ps): ?>
                            <tr>
                                <td class="fw-bold text-center text-muted"><?= (int)$ps['nomor_urut'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size:0.9rem;"><?= e($ps['pertanyaan']) ?></div>
                                    <div class="text-muted small"><?= e($ps['bagian']) ?></div>
                                </td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$ps['skor_1'] ?></span></td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$ps['skor_2'] ?></span></td>
                                <td class="text-center"><span class="badge bg-light text-dark border"><?= (int)$ps['skor_3'] ?></span></td>
                                <td class="text-center text-muted small"><?= (int)$ps['skor_na'] ?></td>
                                <td class="text-center">
                                    <?php if ($ps['avg_skor'] > 0): ?>
                                        <span class="badge bg-primary fs-6 px-2 py-1"><?= number_format((float)$ps['avg_skor'], 2) ?></span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- B. Kumpulan Masukan Terbuka Rekan Kerja -->
                <h6 class="fw-bold text-dark mb-3 pt-3 border-top"><i class="bi bi-chat-quote-fill me-1 text-info"></i> B. Masukan Konstruktif &amp; Saran Kualitatif Rekan Kerja</h6>
                <?php if (empty($groupedPeerTeks)): ?>
                    <div class="text-muted small p-3 bg-light rounded-2">Belum ada catatan kualitatif yang masuk dari rekan kerja.</div>
                <?php else: ?>
                    <div class="row g-3">
                        <?php foreach ($groupedPeerTeks as $noTeks => $gt): ?>
                        <div class="col-12">
                            <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                                <div class="fw-bold mb-2 text-dark" style="font-size:0.92rem;">
                                    <i class="bi bi-question-circle-fill text-primary me-1"></i> <?= e($gt['pertanyaan']) ?>
                                </div>
                                <div class="d-flex flex-column gap-2 mt-2">
                                    <?php foreach ($gt['jawaban'] as $ansItem): ?>
                                    <div class="p-2 px-3 bg-white rounded-2 border small" style="border-left:3px solid var(--purple) !important;">
                                        <div class="text-dark" style="font-size:0.88rem;line-height:1.5;">&ldquo;<?= nl2br(e($ansItem['teks'])) ?>&rdquo;</div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>

            </div>
        </div>

        <!-- SEKSI 3: PENILAIAN OLEH KEPALA LPM -->
        <div class="card shadow-sm border-0 mb-4" style="border-radius:var(--radius-lg);">
            <div class="card-header bg-white border-bottom p-4 d-flex align-items-center justify-content-between flex-wrap gap-2">
                <div>
                    <h5 class="fw-bold mb-1" style="color:var(--navy);">
                        <i class="bi bi-award-fill text-warning me-2"></i> 3. Lembar Penilaian Kinerja oleh Kepala LPM
                    </h5>
                    <p class="text-muted small mb-0">Skor skala 1–5 beserta catatan bukti pendukung dari Kepala LPM.</p>
                </div>
                <div>
                    <?php if ($avgSkorKepala > 0): ?>
                        <span class="badge bg-purple text-white fs-6 p-2 px-3" style="background:var(--purple);">
                            Skor Rata-rata Pimpinan: <?= number_format($avgSkorKepala, 2) ?> / 5.00
                        </span>
                    <?php else: ?>
                        <span class="badge bg-secondary p-2">Belum Dinilai Kepala LPM</span>
                    <?php endif; ?>
                </div>
            </div>
            <div class="card-body p-4">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle">
                        <thead class="table-light small text-uppercase text-muted">
                            <tr>
                                <th style="width:40px;">No</th>
                                <th>Aspek Kinerja yang Dinilai</th>
                                <th class="text-center" style="width:130px;">Skor (1–5)</th>
                                <th>Catatan / Bukti Pendukung Pimpinan</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($jawabanKepala as $jk): 
                                $skorVal = $jk['skor'];
                            ?>
                            <tr>
                                <td class="fw-bold text-center text-muted"><?= (int)$jk['nomor_urut'] ?></td>
                                <td>
                                    <div class="fw-bold text-dark" style="font-size:0.92rem;"><?= e($jk['pertanyaan']) ?></div>
                                </td>
                                <td class="text-center">
                                    <?php if ($skorVal !== null && (int)$skorVal > 0): ?>
                                        <span class="badge bg-purple text-white px-3 py-1 fs-6" style="background:var(--purple);border-radius:6px;">
                                            <?= (int)$skorVal ?> / 5
                                        </span>
                                    <?php elseif ($skorVal !== null && (int)$skorVal === 0): ?>
                                        <span class="badge bg-light text-dark border">0 (N/A)</span>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if (!empty($jk['catatan'])): ?>
                                        <div class="small text-dark" style="font-style:italic;">&ldquo;<?= e($jk['catatan']) ?>&rdquo;</div>
                                    <?php else: ?>
                                        <span class="text-muted small">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Area Tanda Tangan Resmi saat Cetak -->
        <div class="print-only-header mt-5 pt-4">
            <div style="display:flex;justify-content:space-between;text-align:center;">
                <div style="width:40%;">
                    <p style="margin-bottom:60px;">Personel yang Dinilai,</p>
                    <p style="font-weight:bold;text-decoration:underline;margin-bottom:2px;"><?= e($personel['nama']) ?></p>
                    <p style="font-size:9pt;"><?= e($personel['jabatan']) ?></p>
                </div>
                <div style="width:40%;">
                    <p style="margin-bottom:60px;">Semarang, <?= date('d F Y') ?><br>Kepala Lembaga Penjaminan Mutu,</p>
                    <p style="font-weight:bold;text-decoration:underline;margin-bottom:2px;">Stefani Lily Indarto, SE., MM., Ak., CA., CPA.</p>
                    <p style="font-size:9pt;">NPP: -</p>
                </div>
            </div>
        </div>

    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin-footer.php';
?>
