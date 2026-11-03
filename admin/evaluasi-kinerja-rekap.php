<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Rekapitulasi Evaluasi Kinerja Personel';
$current_admin    = 'evaluasi-kinerja-rekap';
$db = getDB();

// Hak Akses Khusus: Hanya admin yang login dengan email lpm@unika.ac.id atau lpm.sekertaris@unika.ac.id
$cur_admin_email = strtolower(trim($_SESSION['admin_email'] ?? ''));
$allowed_rekap_emails = ['lpm@unika.ac.id', 'lpm.sekertaris@unika.ac.id'];

if (!in_array($cur_admin_email, $allowed_rekap_emails, true)) {
    require_once __DIR__ . '/includes/admin-header.php';
    ?>
    <div style="max-width:580px;margin:3.5rem auto;padding:2.5rem;background:#fff;border-radius:var(--radius-lg);box-shadow:var(--shadow-sm);border:1px solid var(--border);text-align:center;">
        <div style="width:72px;height:72px;border-radius:50%;background:#FEE2E2;color:#DC2626;display:flex;align-items:center;justify-content:center;margin:0 auto 1.25rem;font-size:2rem;">
            <i class="bi bi-shield-lock-fill"></i>
        </div>
        <h3 style="font-family:var(--font-heading);font-weight:700;color:var(--navy);margin-bottom:0.75rem;">Akses Terbatas</h3>
        <p style="font-size:0.92rem;color:var(--text-muted);line-height:1.7;margin-bottom:1.5rem;">
            Halaman Rekapitulasi &amp; Hasil Penilaian Evaluasi Kinerja bersifat rahasia dan <strong>hanya dapat diakses</strong> oleh Administrator yang login menggunakan akun email resmi:
            <br><strong style="color:var(--navy);">lpm@unika.ac.id</strong> atau <strong style="color:var(--navy);">lpm.sekertaris@unika.ac.id</strong>.
        </p>
        <div class="p-3 mb-4 rounded-3 text-start" style="background:#F8FAFC;border:1px solid #E2E8F0;font-size:0.84rem;color:#64748B;">
            <div>Email akun Anda saat ini: <strong style="color:#DC2626;"><?= e($cur_admin_email ?: 'Belum terhubung email / login non-Google') ?></strong></div>
        </div>
        <a href="<?= SITE_URL ?>/admin/dashboard.php" class="btn btn-outline-secondary px-4 py-2" style="font-size:0.88rem;border-radius:8px;">
            <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
        </a>
    </div>
    <?php
    require_once __DIR__ . '/includes/admin-footer.php';
    exit;
}

// 1. Tahun Akademik Filter
$tahun_aktif_setting = getPengaturan('evaluasi_tahun_akademik_aktif', '2025/2026');
$ta_filter           = trim($_GET['ta'] ?? $tahun_aktif_setting);

// Ambil list seluruh tahun akademik yang pernah ada di submission atau pengaturan
$taList = $db->query("SELECT DISTINCT tahun_akademik FROM evaluasi_submission WHERE tahun_akademik IS NOT NULL AND tahun_akademik != '' UNION SELECT '2025/2026' UNION SELECT '2024/2025' ORDER BY tahun_akademik DESC")->fetchAll(PDO::FETCH_COLUMN);

// Handle Export CSV
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=rekap_evaluasi_kinerja_lpm_' . str_replace('/', '_', $ta_filter) . '.csv');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['No', 'Nama Personel', 'Email', 'Jabatan', 'Tingkat Peran', 'Status Evaluasi Diri', 'Rata-rata Skor Peer Review (1-3)', 'Jumlah Penilai Rekan', 'Skor Evaluasi Kepala LPM (1-5)']);

    $stmtExp = $db->prepare("SELECT p.*, 
        (SELECT COUNT(*) FROM evaluasi_submission WHERE penilai_id = p.id AND tahun_akademik = ? AND tipe_evaluasi = 'diri') as diri_done,
        (SELECT ROUND(AVG(skor), 2) FROM evaluasi_antar_jawaban WHERE dinilai_id = p.id AND tahun_akademik = ? AND skor > 0) as avg_antar,
        (SELECT COUNT(DISTINCT penilai_id) FROM evaluasi_antar_jawaban WHERE dinilai_id = p.id AND tahun_akademik = ?) as jml_penilai,
        (SELECT ROUND(AVG(skor), 2) FROM evaluasi_kepala_jawaban WHERE dinilai_id = p.id AND tahun_akademik = ? AND skor > 0) as avg_kepala
        FROM evaluasi_peserta p 
        WHERE p.status = 1
        ORDER BY p.peran ASC, p.id ASC");
    $stmtExp->execute([$ta_filter, $ta_filter, $ta_filter, $ta_filter]);
    $expData = $stmtExp->fetchAll(PDO::FETCH_ASSOC);

    $noExp = 1;
    foreach ($expData as $row) {
        fputcsv($output, [
            $noExp++,
            $row['nama'],
            $row['email'],
            $row['jabatan'],
            $row['peran'] === 'kepala' ? 'Kepala LPM' : 'Personel LPM',
            $row['diri_done'] > 0 ? 'Sudah Kirim' : 'Belum Kirim',
            $row['avg_antar'] ? $row['avg_antar'] : '0.00',
            $row['jml_penilai'],
            $row['avg_kepala'] ? $row['avg_kepala'] : '0.00',
        ]);
    }
    fclose($output);
    exit;
}

// 2. Query Ringkasan Data Rekapitulasi
$stmtPeserta = $db->prepare("SELECT p.*, 
    (SELECT COUNT(*) FROM evaluasi_submission WHERE penilai_id = p.id AND tahun_akademik = ? AND tipe_evaluasi = 'diri') as diri_done,
    (SELECT submitted_at FROM evaluasi_submission WHERE penilai_id = p.id AND tahun_akademik = ? AND tipe_evaluasi = 'diri' LIMIT 1) as diri_tgl,
    (SELECT ROUND(AVG(skor), 2) FROM evaluasi_antar_jawaban WHERE dinilai_id = p.id AND tahun_akademik = ? AND skor > 0) as avg_antar,
    (SELECT COUNT(DISTINCT penilai_id) FROM evaluasi_antar_jawaban WHERE dinilai_id = p.id AND tahun_akademik = ?) as jml_penilai_rekan,
    (SELECT ROUND(AVG(skor), 2) FROM evaluasi_kepala_jawaban WHERE dinilai_id = p.id AND tahun_akademik = ? AND skor > 0) as avg_kepala,
    (SELECT COUNT(*) FROM evaluasi_submission WHERE dinilai_id = p.id AND tahun_akademik = ? AND tipe_evaluasi = 'kepala') as kepala_done
    FROM evaluasi_peserta p 
    WHERE p.status = 1
    ORDER BY p.peran ASC, p.id ASC");
$stmtPeserta->execute([$ta_filter, $ta_filter, $ta_filter, $ta_filter, $ta_filter, $ta_filter]);
$rekapPeserta = $stmtPeserta->fetchAll(PDO::FETCH_ASSOC);

$totalPeserta = count($rekapPeserta);
$diriSelesai  = count(array_filter($rekapPeserta, fn($p) => $p['diri_done'] > 0));
$persenDiri   = $totalPeserta > 0 ? round(($diriSelesai / $totalPeserta) * 100) : 0;

$totalPeerSub = (int)$db->prepare("SELECT COUNT(*) FROM evaluasi_submission WHERE tahun_akademik = ? AND tipe_evaluasi = 'antar_personel'")->execute([$ta_filter]) ? $db->prepare("SELECT COUNT(*) FROM evaluasi_submission WHERE tahun_akademik = ? AND tipe_evaluasi = 'antar_personel'") : 0;
$stmtCountPeer = $db->prepare("SELECT COUNT(*) FROM evaluasi_submission WHERE tahun_akademik = ? AND tipe_evaluasi = 'antar_personel'");
$stmtCountPeer->execute([$ta_filter]);
$totalPeerForms = (int)$stmtCountPeer->fetchColumn();

$stmtCountKep = $db->prepare("SELECT COUNT(*) FROM evaluasi_submission WHERE tahun_akademik = ? AND tipe_evaluasi = 'kepala'");
$stmtCountKep->execute([$ta_filter]);
$totalKepalaForms = (int)$stmtCountKep->fetchColumn();

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-11 col-lg-12">
        
        <!-- Breadcrumb -->
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <span>Layanan</span>
            <span>/</span>
            <span style="color:var(--navy);">Rekapitulasi Evaluasi Kinerja</span>
        </div>

        <!-- Filter Bar & Actions -->
        <div class="card shadow-sm border-0 mb-4" style="border-radius:var(--radius-lg);">
            <div class="card-body p-3 p-md-4 d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div class="d-flex align-items-center gap-3">
                    <div style="width:46px;height:46px;border-radius:10px;background:rgba(106,27,154,0.1);color:var(--purple);display:flex;align-items:center;justify-content:center;font-size:1.3rem;">
                        <i class="bi bi-clipboard-data-fill"></i>
                    </div>
                    <div>
                        <h5 class="fw-bold mb-0" style="color:var(--navy);">Rekap Penilaian Evaluasi Kinerja</h5>
                        <div class="text-muted small">Periode Tahun Akademik: <strong><?= e($ta_filter) ?></strong></div>
                    </div>
                </div>

                <div class="d-flex flex-wrap align-items-center gap-2 ms-auto">
                    <!-- Dropdown Filter Tahun Akademik -->
                    <form method="GET" action="" class="d-flex align-items-center gap-2">
                        <label class="small fw-bold text-muted text-nowrap">Tahun Akademik:</label>
                        <select name="ta" class="form-select form-select-sm" onchange="this.form.submit()" style="border-radius:6px;width:150px;font-weight:600;">
                            <?php foreach ($taList as $taItem): ?>
                                <option value="<?= e($taItem) ?>" <?= $taItem === $ta_filter ? 'selected' : '' ?>><?= e($taItem) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </form>

                    <a href="<?= SITE_URL ?>/admin/evaluasi-kinerja-rekap.php?ta=<?= urlencode($ta_filter) ?>&export=csv" class="btn btn-sm btn-outline-success" style="border-radius:6px;">
                        <i class="bi bi-file-earmark-spreadsheet me-1"></i> Export CSV
                    </a>
                </div>
            </div>
        </div>

        <!-- Stat Cards Widget -->
        <div class="row g-3 mb-4">
            <div class="col-sm-6 col-xl-3">
                <div class="p-3 bg-white rounded-3 shadow-sm border h-100 d-flex align-items-center gap-3">
                    <div style="width:48px;height:48px;border-radius:10px;background:#EEF2FF;color:#4F46E5;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                        <i class="bi bi-people-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold" style="font-size:0.7rem;">Total Personel</div>
                        <div class="fs-4 fw-bold text-dark"><?= $totalPeserta ?></div>
                        <div class="small text-muted" style="font-size:0.75rem;">Terdaftar di sistem</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="p-3 bg-white rounded-3 shadow-sm border h-100 d-flex align-items-center gap-3">
                    <div style="width:48px;height:48px;border-radius:10px;background:#ECFDF5;color:#059669;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                        <i class="bi bi-person-check-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold" style="font-size:0.7rem;">Evaluasi Diri</div>
                        <div class="fs-4 fw-bold text-success"><?= $diriSelesai ?> <span class="text-muted fs-6 fw-normal">/ <?= $totalPeserta ?></span></div>
                        <div class="small text-success fw-bold" style="font-size:0.75rem;"><?= $persenDiri ?>% Partisipasi</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="p-3 bg-white rounded-3 shadow-sm border h-100 d-flex align-items-center gap-3">
                    <div style="width:48px;height:48px;border-radius:10px;background:#F0FDF4;color:#16A34A;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                        <i class="bi bi-chat-heart-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold" style="font-size:0.7rem;">Peer Review Masuk</div>
                        <div class="fs-4 fw-bold text-dark"><?= $totalPeerForms ?></div>
                        <div class="small text-muted" style="font-size:0.75rem;">Formulir antar rekan</div>
                    </div>
                </div>
            </div>

            <div class="col-sm-6 col-xl-3">
                <div class="p-3 bg-white rounded-3 shadow-sm border h-100 d-flex align-items-center gap-3">
                    <div style="width:48px;height:48px;border-radius:10px;background:#FEF3C7;color:#D97706;display:flex;align-items:center;justify-content:center;font-size:1.4rem;">
                        <i class="bi bi-award-fill"></i>
                    </div>
                    <div>
                        <div class="text-muted small text-uppercase fw-bold" style="font-size:0.7rem;">Penilaian Kepala LPM</div>
                        <div class="fs-4 fw-bold text-warning"><?= $totalKepalaForms ?></div>
                        <div class="small text-muted" style="font-size:0.75rem;">Personel telah dinilai</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabel Rekapitulasi Utama -->
        <div class="admin-table-wrap p-4 mb-4">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-3 border-bottom">
                <h5 class="fw-bold mb-0" style="color:var(--navy);">
                    <i class="bi bi-table me-2 text-primary"></i> Tabel Rekapitulasi Kinerja per Personel
                </h5>
                <span class="badge bg-light text-dark border">Periode <?= e($ta_filter) ?></span>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover">
                    <thead class="table-light text-uppercase small text-muted">
                        <tr>
                            <th style="width:45px;">No</th>
                            <th>Nama Personel &amp; Jabatan</th>
                            <th class="text-center" style="width:130px;">Tingkatan</th>
                            <th class="text-center" style="width:140px;">Evaluasi Diri</th>
                            <th class="text-center" style="width:160px;">Peer Review (Skala 1–3)</th>
                            <th class="text-center" style="width:160px;">Kepala LPM (Skala 1–5)</th>
                            <th class="text-end" style="width:130px;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($rekapPeserta)): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                Tidak ada data personel untuk tahun akademik <?= e($ta_filter) ?>.
                            </td>
                        </tr>
                        <?php else: 
                            $noR = 1;
                            foreach ($rekapPeserta as $p):
                                $hasDiri = ($p['diri_done'] > 0);
                                $avgPeer = (float)$p['avg_antar'];
                                $avgKep  = (float)$p['avg_kepala'];
                        ?>
                        <tr>
                            <td class="fw-bold text-muted"><?= $noR++ ?></td>
                            <td>
                                <div class="d-flex align-items-center gap-2">
                                    <div style="width:36px;height:36px;border-radius:50%;background:#F1F5F9;display:flex;align-items:center;justify-content:center;font-weight:700;color:var(--navy);font-size:0.85rem;">
                                        <?php if (!empty($p['avatar'])): ?>
                                            <img src="<?= e($p['avatar']) ?>" alt="Avatar" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">
                                        <?php else: ?>
                                            <?= strtoupper(substr($p['nama'], 0, 1)) ?>
                                        <?php endif; ?>
                                    </div>
                                    <div>
                                        <div class="fw-bold text-dark" style="font-size:0.92rem;"><?= e($p['nama']) ?></div>
                                        <div class="text-muted small"><?= e($p['jabatan']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td class="text-center">
                                <?php if ($p['peran'] === 'kepala'): ?>
                                    <span class="badge" style="background:#FEF3C7;color:#92400E;border:1px solid #FCD34D;font-size:0.72rem;">Kepala LPM</span>
                                <?php else: ?>
                                    <span class="badge" style="background:#E0E7FF;color:#3730A3;border:1px solid #C7D2FE;font-size:0.72rem;">Personel</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($hasDiri): ?>
                                    <span class="badge bg-success" style="font-size:0.72rem;" title="Terkirim: <?= e($p['diri_tgl']) ?>">
                                        <i class="bi bi-check-circle-fill me-1"></i> Sudah Kirim
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-secondary" style="font-size:0.72rem;">
                                        <i class="bi bi-clock me-1"></i> Belum Kirim
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($p['peran'] === 'kepala'): ?>
                                    <span class="text-muted small">N/A (Pimpinan)</span>
                                <?php elseif ($avgPeer > 0): ?>
                                    <span class="badge bg-primary fs-6 px-2 py-1" style="border-radius:6px;">
                                        <?= number_format($avgPeer, 2) ?>
                                    </span>
                                    <div class="small text-muted" style="font-size:0.72rem;"><?= (int)$p['jml_penilai_rekan'] ?> rekan menilai</div>
                                <?php else: ?>
                                    <span class="text-muted small">Belum ada nilai</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <?php if ($p['peran'] === 'kepala'): ?>
                                    <span class="text-muted small">N/A (Kepala)</span>
                                <?php elseif ($avgKep > 0): ?>
                                    <span class="badge bg-purple text-white fs-6 px-2 py-1" style="background:var(--purple);border-radius:6px;">
                                        <?= number_format($avgKep, 2) ?> <span style="font-size:0.75rem;">/ 5.00</span>
                                    </span>
                                    <div class="small text-success fw-bold" style="font-size:0.72rem;"><i class="bi bi-check-circle me-1"></i> Dinilai</div>
                                <?php else: ?>
                                    <span class="badge bg-warning text-dark" style="font-size:0.72rem;">Belum Dinilai</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <a href="<?= SITE_URL ?>/admin/evaluasi-kinerja-detail.php?id=<?= $p['id'] ?>&ta=<?= urlencode($ta_filter) ?>" class="btn btn-sm btn-outline-primary" style="border-radius:6px;font-size:0.8rem;">
                                    <i class="bi bi-eye-fill me-1"></i> Rincian &amp; Cetak
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </div>
</div>

<?php
require_once __DIR__ . '/includes/admin-footer.php';
?>

