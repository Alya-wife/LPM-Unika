<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Rekapitulasi Survei Kepuasan Layanan LPM';
$db = getDB();

$tab = $_GET['tab'] ?? 'respon';
if (!in_array($tab, ['respon', 'tahunan', 'token', 'settings'])) {
    $tab = 'respon';
}

// 1. Handle Save General Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $judul  = trim($_POST['feedback_kunjungan_judul'] ?? 'Kuesioner Umpan Balik Kunjungan Mitra Institusi');
    $durasi_menit = max(1, (int)($_POST['feedback_kunjungan_durasi_menit'] ?? 60));

    setPengaturan('feedback_kunjungan_judul', $judul);
    setPengaturan('feedback_kunjungan_durasi_menit', (string)$durasi_menit);

    $_SESSION['flash'] = 'Pengaturan umum kuesioner kunjungan berhasil disimpan.';
    redirect(SITE_URL . '/admin/feedback-kunjungan-list.php?tab=settings');
}

// 3. Handle Generate Token Manual (Walk-In or New Visit)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'generate_token_manual') {
    $nama_institusi    = trim($_POST['nama_institusi'] ?? '');
    $tanggal_kunjungan = trim($_POST['tanggal_kunjungan'] ?? date('Y-m-d'));
    $perihal           = trim($_POST['perihal'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $kunjungan_id      = !empty($_POST['kunjungan_id']) ? (int)$_POST['kunjungan_id'] : null;
    $durasi_menit      = max(1, (int)($_POST['durasi_menit'] ?? 60));

    if (!empty($nama_institusi) && !empty($tanggal_kunjungan)) {
        $token_code = generateUniqueFeedbackToken();
        
        $now = time();
        if (!empty($tanggal_kunjungan) && $tanggal_kunjungan > date('Y-m-d')) {
            $mulai_ts = strtotime($tanggal_kunjungan . ' ' . date('H:i:s'));
        } else {
            $mulai_ts = $now;
        }

        $berlaku_mulai  = date('Y-m-d H:i:s', $mulai_ts);
        $berlaku_sampai = date('Y-m-d H:i:s', $mulai_ts + ($durasi_menit * 60));

        $ins = $db->prepare("INSERT INTO kunjungan_feedback_token 
            (token, kunjungan_id, nama_institusi, email, tanggal_kunjungan, perihal, berlaku_mulai, berlaku_sampai, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Aktif')");
        $ins->execute([$token_code, $kunjungan_id, $nama_institusi, $email, $tanggal_kunjungan, $perihal, $berlaku_mulai, $berlaku_sampai]);

        if ($durasi_menit % 1440 === 0) {
            $durasi_str = ($durasi_menit / 1440) . ' hari';
        } elseif ($durasi_menit < 60) {
            $durasi_str = $durasi_menit . ' menit';
        } elseif ($durasi_menit % 60 === 0) {
            $durasi_str = ($durasi_menit / 60) . ' jam';
        } else {
            $durasi_str = floor($durasi_menit / 60) . ' jam ' . ($durasi_menit % 60) . ' menit';
        }

        $_SESSION['flash'] = 'Kode token berhasil digenerate: <strong class="badge bg-dark font-monospace" style="font-size:1rem;letter-spacing:1px;">' . $token_code . '</strong> untuk ' . e($nama_institusi) . ' (Timer aktif: ' . $durasi_str . ' s/d ' . date('d/m/Y H:i', strtotime($berlaku_sampai)) . ' WIB)';
    } else {
        $_SESSION['flash_error'] = 'Nama Institusi dan Tanggal Kunjungan wajib diisi.';
    }
    redirect(SITE_URL . '/admin/feedback-kunjungan-list.php?tab=token');
}

// 4. Handle Edit Token Info (Only allowed if NOT expired)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'edit_token') {
    $token_id = (int)($_POST['id'] ?? 0);
    $stmt = $db->prepare("SELECT * FROM kunjungan_feedback_token WHERE id = ?");
    $stmt->execute([$token_id]);
    $tok = $stmt->fetch();

    if (!$tok) {
        $_SESSION['flash_error'] = 'Token tidak ditemukan.';
        redirect(SITE_URL . '/admin/feedback-kunjungan-list.php?tab=token');
    }

    $now = date('Y-m-d H:i:s');
    if ($now > $tok['berlaku_sampai']) {
        $_SESSION['flash_error'] = 'Masa berlaku token ini telah habis, data terkunci dan tidak dapat diedit.';
        redirect(SITE_URL . '/admin/feedback-kunjungan-list.php?tab=token');
    }

    $nama_institusi    = trim($_POST['nama_institusi'] ?? '');
    $tanggal_kunjungan = trim($_POST['tanggal_kunjungan'] ?? '');
    $perihal           = trim($_POST['perihal'] ?? '');
    $email             = trim($_POST['email'] ?? '');
    $berlaku_sampai    = $tok['berlaku_sampai'];

    if (!empty($_POST['update_timer']) && $_POST['update_timer'] === '1' && !empty($_POST['durasi_menit'])) {
        $durasi_menit   = max(1, (int)$_POST['durasi_menit']);
        $berlaku_sampai = date('Y-m-d H:i:s', time() + ($durasi_menit * 60));
    }

    if (!empty($nama_institusi) && !empty($tanggal_kunjungan)) {
        // Update information only (token code remains untouched)
        $upd = $db->prepare("UPDATE kunjungan_feedback_token SET 
            nama_institusi = ?, 
            tanggal_kunjungan = ?, 
            perihal = ?, 
            email = ?, 
            berlaku_sampai = ? 
            WHERE id = ?");
        $upd->execute([$nama_institusi, $tanggal_kunjungan, $perihal, $email, $berlaku_sampai, $token_id]);

        // Sync to feedback response if exists
        $db->prepare("UPDATE kunjungan_feedback_respon SET nama_institusi = ?, tanggal_kunjungan = ? WHERE token_id = ?")->execute([$nama_institusi, $tanggal_kunjungan, $token_id]);

        $_SESSION['flash'] = 'Informasi token <strong>' . e($tok['token']) . '</strong> berhasil diperbarui.';
    } else {
        $_SESSION['flash_error'] = 'Nama Institusi dan Tanggal Kunjungan wajib diisi.';
    }
    redirect(SITE_URL . '/admin/feedback-kunjungan-list.php?tab=token');
}

// 5. Handle Delete / Deactivate Token (Allowed for any token)
if (
    (isset($_GET['action']) && $_GET['action'] === 'delete_token' && isset($_GET['id']) && is_numeric($_GET['id'])) ||
    ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_token' && isset($_POST['id']) && is_numeric($_POST['id']))
) {
    $tok_id = (int)($_POST['id'] ?? $_GET['id']);
    $ret_filter = $_REQUEST['filter'] ?? 'all';
    if (!in_array($ret_filter, ['all', 'active', 'history'])) {
        $ret_filter = 'all';
    }
    
    // Check if token exists
    $check_stmt = $db->prepare("SELECT t.*, 
        (SELECT COUNT(*) FROM kunjungan_feedback_respon r WHERE r.token_id = t.id) as respon_count 
        FROM kunjungan_feedback_token t WHERE t.id = ?");
    $check_stmt->execute([$tok_id]);
    $tok_data = $check_stmt->fetch();

    if (!$tok_data) {
        $_SESSION['flash_error'] = 'Token tidak ditemukan.';
    } else {
        $token_code_disp = $tok_data['token'];
        $instansi_disp   = $tok_data['nama_institusi'];
        $resp_cnt        = (int)$tok_data['respon_count'];

        // Clean up any associated responses and answers first if any exist
        $respon_ids = $db->query("SELECT id FROM kunjungan_feedback_respon WHERE token_id = " . (int)$tok_id)->fetchAll(PDO::FETCH_COLUMN);
        if (!empty($respon_ids)) {
            $placeholders = implode(',', array_fill(0, count($respon_ids), '?'));
            $db->prepare("DELETE FROM kunjungan_feedback_jawaban WHERE respon_id IN ($placeholders)")->execute($respon_ids);
            $db->prepare("DELETE FROM kunjungan_feedback_respon WHERE token_id = ?")->execute([$tok_id]);
        }

        // Delete the token
        $db->prepare("DELETE FROM kunjungan_feedback_token WHERE id = ?")->execute([$tok_id]);

        $extra_msg = $resp_cnt > 0 ? " beserta $resp_cnt data respon terkait" : "";
        $_SESSION['flash'] = 'Kode token <strong>' . e($token_code_disp) . '</strong> (' . e($instansi_disp) . ') berhasil dihapus' . $extra_msg . '.';
    }
    redirect(SITE_URL . '/admin/feedback-kunjungan-list.php?tab=token&filter=' . urlencode($ret_filter));
}

// 6. Handle Delete Individual Response
if ((isset($_GET['action']) && $_GET['action'] === 'delete_respon' && isset($_GET['id']) && is_numeric($_GET['id'])) ||
    ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_respon' && isset($_POST['id']) && is_numeric($_POST['id']))) {
    $del_resp_id = (int)($_POST['id'] ?? $_GET['id']);
    $db->prepare("DELETE FROM kunjungan_feedback_jawaban WHERE respon_id = ?")->execute([$del_resp_id]);
    $db->prepare("DELETE FROM kunjungan_feedback_respon WHERE id = ?")->execute([$del_resp_id]);
    $_SESSION['flash'] = 'Data respon survei kepuasan berhasil dihapus.';
    redirect(SITE_URL . '/admin/feedback-kunjungan-list.php?tab=respon');
}

// Statistics
$total_respon    = 0;
$total_token     = 0;
$total_instansi  = 0;
$unexpired_count = 0;
$expired_count   = 0;
$active_token    = 0;
$history_count   = 0;
$avg_csat        = 0;

try {
    $total_respon    = (int)$db->query("SELECT COUNT(*) FROM kunjungan_feedback_respon")->fetchColumn();
    $total_token     = (int)$db->query("SELECT COUNT(*) FROM kunjungan_feedback_token")->fetchColumn();
    $total_instansi  = (int)$db->query("SELECT COUNT(DISTINCT COALESCE(token_id, nama_institusi)) FROM kunjungan_feedback_respon")->fetchColumn();
    $unexpired_count = (int)$db->query("SELECT COUNT(*) FROM kunjungan_feedback_token WHERE berlaku_sampai >= NOW()")->fetchColumn();
    $expired_count   = (int)$db->query("SELECT COUNT(*) FROM kunjungan_feedback_token WHERE berlaku_sampai < NOW()")->fetchColumn();
    $active_token    = (int)$db->query("SELECT COUNT(*) FROM kunjungan_feedback_token WHERE status = 'Aktif' AND berlaku_sampai >= NOW()")->fetchColumn();
    $history_count   = (int)$db->query("SELECT COUNT(*) FROM kunjungan_feedback_token WHERE status = 'Digunakan' OR berlaku_sampai < NOW() OR id IN (SELECT token_id FROM kunjungan_feedback_respon WHERE token_id IS NOT NULL)")->fetchColumn();
    $avg_csat_val    = $db->query("SELECT AVG(rata_rata_skor) FROM kunjungan_feedback_respon WHERE rata_rata_skor IS NOT NULL")->fetchColumn();
    $avg_csat        = $avg_csat_val ? round((float)$avg_csat_val, 2) : 0;
} catch (Exception $e) {
    // Fail-safe default stats
}

// Fetch Data for Tab: Respon (Dikelompokkan per Instansi Kunjungan & Respon Individu)
$respon_instansi_list = [];
$respon_individu_list = [];
$kategori_filter = trim($_GET['kategori'] ?? 'all');
$view_mode       = trim($_GET['view'] ?? 'individu');

if ($tab === 'respon') {
    // 1. Grouped by Instansi
    $where_inst = "";
    $params_inst = [];
    if ($kategori_filter !== 'all') {
        $where_inst = " WHERE r.kategori_layanan = ? ";
        $params_inst[] = $kategori_filter;
    }
    $stmt_inst = $db->prepare("SELECT 
        COALESCE(r.token_id, 0) as token_id,
        MIN(r.id) as first_respon_id,
        r.nama_institusi,
        r.tanggal_kunjungan,
        t.token,
        t.perihal,
        COUNT(r.id) as total_responden,
        AVG(r.rata_rata_skor) as avg_skor_instansi,
        MIN(r.created_at) as first_submitted_at,
        MAX(r.created_at) as last_submitted_at
    FROM kunjungan_feedback_respon r 
    LEFT JOIN kunjungan_feedback_token t ON r.token_id = t.id 
    $where_inst
    GROUP BY COALESCE(r.token_id, 0), r.nama_institusi, r.tanggal_kunjungan, t.token, t.perihal 
    ORDER BY last_submitted_at DESC");
    $stmt_inst->execute($params_inst);
    $respon_instansi_list = $stmt_inst->fetchAll();

    // 2. Individual Respondents
    $where_ind = "";
    $params_ind = [];
    if ($kategori_filter !== 'all') {
        $where_ind = " WHERE r.kategori_layanan = ? ";
        $params_ind[] = $kategori_filter;
    }
    $stmt_ind = $db->prepare("SELECT r.*, t.token, t.perihal 
        FROM kunjungan_feedback_respon r 
        LEFT JOIN kunjungan_feedback_token t ON r.token_id = t.id 
        $where_ind 
        ORDER BY r.created_at DESC");
    $stmt_ind->execute($params_ind);
    $respon_individu_list = $stmt_ind->fetchAll();
}

// Helper Functions for Quality/Academic Period (1 September – 1 Agustus)
if (!function_exists('hitungPeriodeKunjungan')) {
    function hitungPeriodeKunjungan(?string $dateStr = null): string {
        $time = $dateStr ? strtotime($dateStr) : time();
        if (!$time) $time = time();
        $month = (int)date('n', $time);
        $day   = (int)date('j', $time);
        $year  = (int)date('Y', $time);
        if ($month >= 9 || ($month === 8 && $day > 1)) {
            return $year . '/' . ($year + 1);
        } else {
            return ($year - 1) . '/' . $year;
        }
    }
}

if (!function_exists('getRentangTanggalPeriode')) {
    function getRentangTanggalPeriode(string $periodeStr): array {
        $parts = explode('/', $periodeStr);
        if (count($parts) === 2 && is_numeric($parts[0]) && is_numeric($parts[1])) {
            $sy = (int)$parts[0];
            $ey = (int)$parts[1];
        } else {
            $sy = (int)date('Y');
            $ey = $sy + 1;
        }
        return [
            'start'      => sprintf('%04d-09-01 00:00:00', $sy),
            'end'        => sprintf('%04d-08-01 23:59:59', $ey),
            'start_year' => $sy,
            'end_year'   => $ey,
            'label'      => $sy . '/' . $ey
        ];
    }
}

// Fetch Data for Tab: Rekapan Mutu per Periode (Tahunan)
$periodes_list = [];
$selected_periode = hitungPeriodeKunjungan();
$p_start_year = (int)date('Y');
$p_end_year = $p_start_year + 1;
$thn_total_instansi = 0;
$thn_total_responden = 0;
$thn_avg_csat = 0;
$thn_pertanyaan_list = [];
$thn_instansi_list = [];
$thn_chart_labels = [];
$thn_chart_scores = [];
$thn_chart_colors = [];
$thn_distribusi_bintang = [];

if ($tab === 'tahunan') {
    // 1. Ambil semua tanggal unik dari respon untuk menentukan daftar periode secara otomatis
    $all_dates = $db->query("SELECT DISTINCT tanggal_kunjungan FROM kunjungan_feedback_respon WHERE tanggal_kunjungan IS NOT NULL ORDER BY tanggal_kunjungan DESC")->fetchAll(PDO::FETCH_COLUMN);
    
    $detected_periodes = [];
    $current_p = hitungPeriodeKunjungan();
    $detected_periodes[] = $current_p;

    foreach ($all_dates as $d_val) {
        $p_val = hitungPeriodeKunjungan($d_val);
        if (!in_array($p_val, $detected_periodes)) {
            $detected_periodes[] = $p_val;
        }
    }
    
    // Urutkan periode secara descending (terbaru ke terlama, misal 2026/2027, 2025/2026, dst)
    rsort($detected_periodes);
    $periodes_list = array_values($detected_periodes);

    // Periode yang dipilih
    $selected_periode = isset($_GET['periode']) && in_array($_GET['periode'], $periodes_list) 
        ? $_GET['periode'] 
        : $periodes_list[0];

    // Rentang tanggal periode (1 September thn_awal s/d 1 Agustus thn_akhir)
    $p_range = getRentangTanggalPeriode($selected_periode);
    $p_start = $p_range['start'];
    $p_end   = $p_range['end'];
    $p_start_year = $p_range['start_year'];
    $p_end_year   = $p_range['end_year'];

    // Stats for selected period
    $stmt_thn_inst = $db->prepare("SELECT COUNT(DISTINCT COALESCE(token_id, nama_institusi)) 
        FROM kunjungan_feedback_respon 
        WHERE tanggal_kunjungan >= ? AND tanggal_kunjungan <= ?");
    $stmt_thn_inst->execute([$p_start, $p_end]);
    $thn_total_instansi = (int)$stmt_thn_inst->fetchColumn();

    $stmt_thn_resp = $db->prepare("SELECT COUNT(*) 
        FROM kunjungan_feedback_respon 
        WHERE tanggal_kunjungan >= ? AND tanggal_kunjungan <= ?");
    $stmt_thn_resp->execute([$p_start, $p_end]);
    $thn_total_responden = (int)$stmt_thn_resp->fetchColumn();

    $stmt_thn_avg = $db->prepare("SELECT AVG(rata_rata_skor) 
        FROM kunjungan_feedback_respon 
        WHERE tanggal_kunjungan >= ? AND tanggal_kunjungan <= ? AND rata_rata_skor IS NOT NULL");
    $stmt_thn_avg->execute([$p_start, $p_end]);
    $thn_avg_csat = $stmt_thn_avg->fetchColumn();
    $thn_avg_csat = $thn_avg_csat ? round((float)$thn_avg_csat, 2) : 0;

    // Per-question averages for selected period
    $stmt_q_avg = $db->prepare("SELECT 
        p.id, p.kategori, p.pertanyaan, p.keterangan, p.urutan,
        COUNT(j.id) as total_jawaban,
        AVG(j.nilai_skor) as avg_skor,
        MIN(j.nilai_skor) as min_skor,
        MAX(j.nilai_skor) as max_skor
    FROM kunjungan_kuesioner_pertanyaan p
    LEFT JOIN kunjungan_feedback_jawaban j ON p.id = j.pertanyaan_id
    LEFT JOIN kunjungan_feedback_respon r ON j.respon_id = r.id AND r.tanggal_kunjungan >= ? AND r.tanggal_kunjungan <= ?
    WHERE p.tipe = 'skala' AND p.is_aktif = 1
    GROUP BY p.id, p.kategori, p.pertanyaan, p.keterangan, p.urutan
    ORDER BY p.urutan ASC, p.id ASC");
    $stmt_q_avg->execute([$p_start, $p_end]);
    $thn_pertanyaan_list = $stmt_q_avg->fetchAll();

    // Distribusi nilai bintang (1, 2, 3, 4, 5) per butir pertanyaan untuk periode terpilih
    $stmt_thn_dist = $db->prepare("SELECT 
        j.pertanyaan_id,
        j.nilai_skor,
        r.id as respon_id,
        r.nama_pengisi,
        r.jabatan_pengisi,
        r.nama_institusi
    FROM kunjungan_feedback_jawaban j
    JOIN kunjungan_feedback_respon r ON j.respon_id = r.id
    JOIN kunjungan_kuesioner_pertanyaan p ON j.pertanyaan_id = p.id
    WHERE r.tanggal_kunjungan >= ? AND r.tanggal_kunjungan <= ?
      AND p.tipe = 'skala' AND j.nilai_skor IS NOT NULL
    ORDER BY j.pertanyaan_id ASC, j.nilai_skor DESC, r.nama_pengisi ASC");
    $stmt_thn_dist->execute([$p_start, $p_end]);
    $thn_dist_rows = $stmt_thn_dist->fetchAll();

    $thn_distribusi_bintang = [];
    foreach ($thn_pertanyaan_list as $qp) {
        $qid = (int)$qp['id'];
        $thn_distribusi_bintang[$qid] = [
            5 => ['count' => 0, 'respondents' => []],
            4 => ['count' => 0, 'respondents' => []],
            3 => ['count' => 0, 'respondents' => []],
            2 => ['count' => 0, 'respondents' => []],
            1 => ['count' => 0, 'respondents' => []],
        ];
    }
    foreach ($thn_dist_rows as $dr) {
        $qid = (int)$dr['pertanyaan_id'];
        $skor = (int)$dr['nilai_skor'];
        if (isset($thn_distribusi_bintang[$qid][$skor])) {
            $thn_distribusi_bintang[$qid][$skor]['count']++;
            $thn_distribusi_bintang[$qid][$skor]['respondents'][] = [
                'nama' => $dr['nama_pengisi'] ?: 'Anonim',
                'jabatan' => $dr['jabatan_pengisi'] ?: '-',
                'institusi' => $dr['nama_institusi'] ?: '-'
            ];
        }
    }

    // Breakdown of instansi for selected period
    $stmt_thn_breakdown = $db->prepare("SELECT 
        COALESCE(r.token_id, 0) as token_id,
        MIN(r.id) as first_respon_id,
        r.nama_institusi,
        r.tanggal_kunjungan,
        t.token,
        t.perihal,
        COUNT(r.id) as total_responden,
        AVG(r.rata_rata_skor) as avg_skor
    FROM kunjungan_feedback_respon r
    LEFT JOIN kunjungan_feedback_token t ON r.token_id = t.id
    WHERE r.tanggal_kunjungan >= ? AND r.tanggal_kunjungan <= ?
    GROUP BY COALESCE(r.token_id, 0), r.nama_institusi, r.tanggal_kunjungan, t.token, t.perihal
    ORDER BY r.tanggal_kunjungan DESC");
    $stmt_thn_breakdown->execute([$p_start, $p_end]);
    $thn_instansi_list = $stmt_thn_breakdown->fetchAll();

    // Chart.js data
    $thn_palette = [
        'Pelayanan & Fasilitas'   => 'rgba(2, 132, 199, 0.85)',
        'Materi & Substansi Mutu' => 'rgba(16, 185, 129, 0.85)',
        'Manajemen Waktu'         => 'rgba(99, 102, 241, 0.85)',
        'Manfaat & Dampak'        => 'rgba(139, 92, 246, 0.85)',
    ];

    foreach ($thn_pertanyaan_list as $i => $qp) {
        $val = $qp['avg_skor'] ? round((float)$qp['avg_skor'], 2) : 0;
        $thn_chart_labels[] = 'P' . ($i + 1) . ': ' . truncate($qp['pertanyaan'], 44);
        $thn_chart_scores[] = $val;
        $cat = $qp['kategori'] ?? 'Lainnya';
        $thn_chart_colors[] = $thn_palette[$cat] ?? 'rgba(2, 132, 199, 0.85)';
    }
}

$token_filter = $_GET['filter'] ?? 'all';
if (!in_array($token_filter, ['all', 'active', 'history'])) {
    $token_filter = 'all';
}

$token_list = [];
if ($tab === 'token') {
    $sql_token = "SELECT t.*, 
        (SELECT COUNT(*) FROM kunjungan_feedback_respon r WHERE r.token_id = t.id) as respon_count 
        FROM kunjungan_feedback_token t ";
    if ($token_filter === 'history') {
        $sql_token .= "WHERE t.berlaku_sampai < NOW() ";
    } elseif ($token_filter === 'active') {
        $sql_token .= "WHERE t.berlaku_sampai >= NOW() ";
    }
    $sql_token .= "ORDER BY t.id DESC";
    $token_list = $db->query($sql_token)->fetchAll();
}

$flash       = $_SESSION['flash'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_error']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.3rem;font-weight:800;color:var(--navy);margin:0;display:flex;align-items:center;gap:10px;">
            <span>⭐ Feedback Kunjungan Mitra Institusi</span>
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Kelola kuesioner kepuasan, kode token tamu per instansi, serta pantau indeks mutu dan saran masukan pasca kunjungan.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <a href="feedback-kunjungan-pertanyaan.php" class="btn-outline">
            <i class="bi bi-list-check me-1"></i> Kelola Butir Kuesioner
        </a>
        <button type="button" class="btn-add" data-bs-toggle="modal" data-bs-target="#modalGenerateToken">
            <i class="bi bi-plus-circle me-1"></i> Buat Token Kunjungan
        </button>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4">
    <i class="bi bi-check-circle-fill me-2"></i> <?= $flash ?>
</div>
<?php endif; ?>

<?php if ($flash_error): ?>
<div class="alert-lpm alert-danger mb-4">
    <i class="bi bi-exclamation-triangle-fill me-2"></i> <?= e($flash_error) ?>
</div>
<?php endif; ?>

<!-- Summary Metric Cards -->
<div class="row g-3 mb-4">
    <!-- Card 1: Indeks Mutu CSAT -->
    <div class="col-lg-3 col-md-6">
        <div class="admin-table-wrap p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div style="font-size:0.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">
                        Indeks Rata-rata (CSAT)
                    </div>
                    <div style="font-size:1.85rem;font-weight:800;color:#0284C7;line-height:1.2;margin-top:4px;">
                        <?= $avg_csat > 0 ? $avg_csat : '-' ?> <span style="font-size:1rem;color:var(--text-muted);font-weight:600;">/ 5.00</span>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">
                        <?php 
                        if ($avg_csat >= 4.5) echo '<i class="bi bi-award-fill text-warning me-1"></i> Predikat Sangat Memuaskan';
                        elseif ($avg_csat >= 3.5) echo '<i class="bi bi-hand-thumbs-up-fill text-primary me-1"></i> Predikat Memuaskan / Baik';
                        elseif ($avg_csat > 0) echo '<i class="bi bi-exclamation-triangle-fill text-warning me-1"></i> Perlu Peningkatan Layanan';
                        else echo 'Belum ada penilaian masuk';
                        ?>
                    </div>
                </div>
                <div style="width:42px;height:42px;border-radius:var(--radius-sm);background:#E0F2FE;color:#0284C7;display:flex;align-items:center;justify-content:center;font-size:1.25rem;">
                    <i class="bi bi-star-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 2: Total Respon Masuk -->
    <div class="col-lg-3 col-md-6">
        <div class="admin-table-wrap p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div style="font-size:0.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">
                        Respon Masuk
                    </div>
                    <div style="font-size:1.85rem;font-weight:800;color:var(--navy);line-height:1.2;margin-top:4px;">
                        <?= $total_respon ?>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">
                        Kuesioner Tamu Diterima
                    </div>
                </div>
                <div style="width:42px;height:42px;border-radius:var(--radius-sm);background:#F3E8FF;color:#7E22CE;display:flex;align-items:center;justify-content:center;font-size:1.25rem;">
                    <i class="bi bi-chat-heart"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 3: Token Kunjungan Aktif -->
    <div class="col-lg-3 col-md-6">
        <div class="admin-table-wrap p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div style="font-size:0.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">
                        Token Kunjungan
                    </div>
                    <div style="font-size:1.85rem;font-weight:800;color:#10B981;line-height:1.2;margin-top:4px;">
                        <?= $active_token ?> <span style="font-size:0.95rem;color:var(--text-muted);font-weight:600;">/ <?= $total_token ?></span>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">
                        Token Aktif Siap Digunakan
                    </div>
                </div>
                <div style="width:42px;height:42px;border-radius:var(--radius-sm);background:#DCFCE7;color:#15803D;display:flex;align-items:center;justify-content:center;font-size:1.25rem;">
                    <i class="bi bi-key-fill"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Card 4: Instansi Tamu Terlayani -->
    <div class="col-lg-3 col-md-6">
        <div class="admin-table-wrap p-3 h-100">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <div style="font-size:0.78rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">
                        Instansi Tamu
                    </div>
                    <div style="font-size:1.85rem;font-weight:800;color:#4338CA;line-height:1.2;margin-top:4px;">
                        <?= $total_instansi ?>
                    </div>
                    <div style="font-size:0.75rem;color:var(--text-muted);margin-top:4px;">
                        Mitra Mengisi Kuesioner
                    </div>
                </div>
                <div style="width:42px;height:42px;border-radius:var(--radius-sm);background:#EEF2FF;color:#4338CA;display:flex;align-items:center;justify-content:center;font-size:1.25rem;">
                    <i class="bi bi-buildings-fill"></i>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Tabs Navigation -->
<ul class="nav nav-tabs mb-4" style="border-bottom:2px solid var(--border);">
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'respon' ? 'active fw-bold' : '' ?>" href="feedback-kunjungan-list.php?tab=respon" style="color:var(--navy);">
            <i class="bi bi-buildings me-1"></i> Rekapitulasi Respon Kunjungan (<?= $total_instansi ?> Instansi)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'tahunan' ? 'active fw-bold' : '' ?>" href="feedback-kunjungan-list.php?tab=tahunan" style="color:var(--navy);">
            <i class="bi bi-bar-chart-line-fill text-primary me-1"></i> Rekapan Mutu per Periode
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'token' ? 'active fw-bold' : '' ?>" href="feedback-kunjungan-list.php?tab=token" style="color:var(--navy);">
            <i class="bi bi-ticket-perforated me-1"></i> Manajemen Kode Token (<?= $total_token ?>)
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link <?= $tab === 'settings' ? 'active fw-bold' : '' ?>" href="feedback-kunjungan-list.php?tab=settings" style="color:var(--navy);">
            <i class="bi bi-gear me-1"></i> Pengaturan Kuesioner
        </a>
    </li>
</ul>

<!-- TAB CONTENT 1: Rekapitulasi Respon -->
<?php if ($tab === 'respon'): ?>
<div class="admin-table-wrap p-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 style="font-weight:700;color:var(--navy);font-size:1rem;margin:0;">
                <i class="bi bi-ui-checks-grid text-primary me-2"></i> Rekapitulasi Respon Survei Kepuasan Layanan LPM
            </h5>
            <div style="font-size:0.8rem;color:var(--text-muted);margin-top:2px;">
                Memuat data seluruh responden yang telah mengisi survei kepuasan, baik layanan rutin LPM maupun kunjungan studi banding.
            </div>
        </div>
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <!-- View Mode Switcher -->
            <div class="btn-group btn-group-sm" role="group">
                <a href="feedback-kunjungan-list.php?tab=respon&kategori=<?= urlencode($kategori_filter) ?>&view=individu" 
                   class="btn <?= $view_mode === 'individu' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-person-lines-fill me-1"></i> Respon Individu (<?= count($respon_individu_list) ?>)
                </a>
                <a href="feedback-kunjungan-list.php?tab=respon&kategori=<?= urlencode($kategori_filter) ?>&view=instansi" 
                   class="btn <?= $view_mode === 'instansi' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>">
                    <i class="bi bi-buildings me-1"></i> Per Instansi (<?= count($respon_instansi_list) ?>)
                </a>
            </div>
        </div>
    </div>

    <!-- Category Filters -->
    <div class="p-3 bg-light border-bottom d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2 flex-wrap">
            <span style="font-size:0.82rem;font-weight:700;color:var(--navy);">Filter Kategori:</span>
            <a href="feedback-kunjungan-list.php?tab=respon&view=<?= $view_mode ?>&kategori=all" 
               class="btn btn-sm <?= $kategori_filter === 'all' ? 'btn-dark fw-bold' : 'btn-outline-secondary' ?>" 
               style="font-size:0.78rem;border-radius:20px;padding:0.25rem 0.75rem;">
                Semua Kategori (<?= $total_respon ?>)
            </a>
            <a href="feedback-kunjungan-list.php?tab=respon&view=<?= $view_mode ?>&kategori=<?= urlencode('Kunjungan Studi Banding') ?>" 
               class="btn btn-sm <?= $kategori_filter === 'Kunjungan Studi Banding' ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>" 
               style="font-size:0.78rem;border-radius:20px;padding:0.25rem 0.75rem;">
                <i class="bi bi-building-check me-1"></i> Kunjungan Studi Banding
            </a>
            <a href="feedback-kunjungan-list.php?tab=respon&view=<?= $view_mode ?>&kategori=<?= urlencode('Pelayanan LPM') ?>" 
               class="btn btn-sm <?= $kategori_filter === 'Pelayanan LPM' ? 'btn-success fw-bold' : 'btn-outline-secondary' ?>" 
               style="font-size:0.78rem;border-radius:20px;padding:0.25rem 0.75rem;">
                <i class="bi bi-award me-1"></i> Pelayanan LPM
            </a>
        </div>
        <button type="button" onclick="window.print()" class="btn btn-sm btn-outline-secondary" style="font-size:0.78rem;border-radius:20px;">
            <i class="bi bi-printer me-1"></i> Cetak Rekapitulasi
        </button>
    </div>

    <?php if ($view_mode === 'individu'): ?>
        <!-- TABEL INDIVIDU -->
        <?php if (empty($respon_individu_list)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-clipboard2-x" style="font-size:2.5rem;color:#CBD5E1;display:block;margin-bottom:0.75rem;"></i>
            <div style="font-weight:700;">Belum Ada Respon Survei Kepuasan Masuk</div>
            <div style="font-size:0.82rem;margin-top:4px;">Respon yang diisi melalui formulir publik akan tampil di sini.</div>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                <thead style="background:#F8FAFC;">
                    <tr>
                        <th style="width:40px;text-align:center;">No</th>
                        <th>Responden</th>
                        <th>Kategori Layanan</th>
                        <th>Status Responden</th>
                        <th>Instansi / Lembaga</th>
                        <th>Demografi</th>
                        <th style="text-align:center;">CSAT</th>
                        <th>Saran &amp; Masukan</th>
                        <th>Waktu Pengisian</th>
                        <th style="text-align:center;width:110px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($respon_individu_list as $idx => $r): 
                        $detail_url = 'feedback-kunjungan-detail.php?id=' . $r['id'] . (!empty($r['token_id']) ? '&token_id=' . $r['token_id'] : '');
                        $skor = $r['rata_rata_skor'] ? round((float)$r['rata_rata_skor'], 2) : 0;
                    ?>
                    <tr>
                        <td style="text-align:center;color:var(--text-muted);font-weight:600;"><?= $idx + 1 ?></td>
                        <td>
                            <div style="font-weight:700;color:var(--navy);"><?= e($r['nama_pengisi'] ?: 'Anonim') ?></div>
                            <div style="font-size:0.75rem;color:var(--text-muted);font-family:monospace;"><?= e($r['email_pengisi'] ?: '-') ?></div>
                        </td>
                        <td>
                            <?php if (($r['kategori_layanan'] ?? '') === 'Kunjungan Studi Banding'): ?>
                                <span class="badge" style="background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;font-size:0.73rem;">
                                    <i class="bi bi-building-check me-1"></i> Studi Banding
                                </span>
                            <?php else: ?>
                                <span class="badge" style="background:#ECFDF5;color:#047857;border:1px solid #A7F3D0;font-size:0.73rem;">
                                    <i class="bi bi-award me-1"></i> Pelayanan LPM
                                </span>
                            <?php endif; ?>
                            <?php if (!empty($r['token'])): ?>
                                <div class="mt-1" style="font-family:monospace;font-size:0.7rem;color:#64748B;">
                                    <i class="bi bi-ticket-perforated"></i> <?= e($r['token']) ?>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-size:0.8rem;color:#334155;font-weight:600;">
                                <?= e($r['status_responden'] ?: ($r['jabatan_pengisi'] ?: '-')) ?>
                            </span>
                        </td>
                        <td>
                            <div style="font-weight:600;color:var(--navy);"><?= e($r['nama_institusi'] ?: '-') ?></div>
                        </td>
                        <td>
                            <div style="font-size:0.76rem;color:#64748B;">
                                <?php
                                $d_info = [];
                                if (!empty($r['jenis_kelamin'])) $d_info[] = e($r['jenis_kelamin']);
                                if (!empty($r['umur'])) $d_info[] = e($r['umur']) . ' thn';
                                if (!empty($r['pendidikan_terakhir'])) $d_info[] = e($r['pendidikan_terakhir']);
                                echo !empty($d_info) ? implode(' &bull; ', $d_info) : '-';
                                ?>
                            </div>
                        </td>
                        <td style="text-align:center;">
                            <?php if ($skor > 0): ?>
                            <span class="badge" style="background:#E0F2FE;color:#0284C7;font-size:0.85rem;padding:0.35rem 0.6rem;font-weight:800;border:1px solid #BAE6FD;">
                                ⭐ <?= number_format($skor, 2) ?>
                            </span>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if (!empty($r['saran_masukan'])): ?>
                                <div style="font-size:0.78rem;color:#334155;line-height:1.4;max-width:220px;" title="<?= e($r['saran_masukan']) ?>">
                                    &ldquo;<?= e(truncate($r['saran_masukan'], 50)) ?>&rdquo;
                                </div>
                            <?php else: ?>
                                <span class="text-muted fst-italic" style="font-size:0.75rem;">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.78rem;color:var(--text-muted);white-space:nowrap;">
                            <?= date('d/m/Y H:i', strtotime($r['created_at'])) ?> WIB
                        </td>
                        <td style="text-align:center;white-space:nowrap;">
                            <a href="<?= $detail_url ?>" class="btn-action btn-edit me-1" style="text-decoration:none;" title="Lihat Rincian Jawaban">
                                <i class="bi bi-eye"></i>
                            </a>
                            <a href="feedback-kunjungan-list.php?action=delete_respon&id=<?= $r['id'] ?>" class="btn-action btn-delete" style="text-decoration:none;" onclick="return confirm('Hapus respon survei ini? Data jawaban terkait juga akan dihapus.');" title="Hapus Respon">
                                <i class="bi bi-trash"></i>
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

    <?php else: ?>
        <!-- TABEL GRUP INSTANSI -->
        <?php if (empty($respon_instansi_list)): ?>
        <div class="text-center py-5 text-muted">
            <i class="bi bi-clipboard2-x" style="font-size:2.5rem;color:#CBD5E1;display:block;margin-bottom:0.75rem;"></i>
            <div style="font-weight:700;">Belum Ada Respon Kunjungan Masuk</div>
            <div style="font-size:0.82rem;margin-top:4px;">Kode token yang dibuat dapat dibagikan kepada instansi yang telah menyelesaikan kunjungan.</div>
        </div>
        <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                <thead style="background:#F8FAFC;">
                    <tr>
                        <th style="width:50px;text-align:center;">No</th>
                        <th>Institusi Tamu</th>
                        <th>Tanggal Kunjungan &amp; Agenda</th>
                        <th style="text-align:center;">Jumlah Responden</th>
                        <th style="text-align:center;">Rata-rata Skor (CSAT)</th>
                        <th>Waktu Pengisian Terakhir</th>
                        <th style="text-align:center;width:130px;">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($respon_instansi_list as $idx => $r): 
                        $detail_url = $r['token_id'] > 0 
                            ? 'feedback-kunjungan-detail.php?token_id=' . $r['token_id'] 
                            : 'feedback-kunjungan-detail.php?id=' . $r['first_respon_id'];
                        $avg_inst = $r['avg_skor_instansi'] ? round((float)$r['avg_skor_instansi'], 2) : 0;
                    ?>
                    <tr>
                        <td style="text-align:center;color:var(--text-muted);font-weight:600;"><?= $idx + 1 ?></td>
                        <td>
                            <div style="font-weight:700;color:var(--navy);font-size:0.95rem;"><?= e($r['nama_institusi'] ?: 'Lembaga / Umum') ?></div>
                            <?php if (!empty($r['token'])): ?>
                            <div class="mt-1">
                                <span class="badge" style="background:#F1F5F9;border:1px solid #CBD5E1;color:#0F172A;font-family:monospace;font-size:0.75rem;padding:0.2rem 0.45rem;">
                                    <i class="bi bi-ticket-perforated me-1"></i><?= e($r['token']) ?>
                                </span>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-semibold text-dark">
                                <i class="bi bi-calendar-event me-1 text-muted"></i><?= formatTanggal($r['tanggal_kunjungan']) ?>
                            </div>
                            <?php if (!empty($r['perihal'])): ?>
                            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                                <?= e(truncate($r['perihal'], 55)) ?>
                            </div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align:center;">
                            <span class="badge" style="background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;font-size:0.85rem;padding:0.4rem 0.75rem;font-weight:800;border-radius:20px;">
                                <i class="bi bi-people-fill me-1"></i> <?= $r['total_responden'] ?> Orang
                            </span>
                        </td>
                        <td style="text-align:center;">
                            <?php if ($avg_inst > 0): ?>
                            <span class="badge" style="background:#E0F2FE;color:#0284C7;font-size:0.9rem;padding:0.4rem 0.7rem;font-weight:800;border:1px solid #BAE6FD;">
                                ⭐ <?= number_format($avg_inst, 2) ?>
                            </span>
                            <div style="font-size:0.7rem;color:var(--text-muted);margin-top:3px;">
                                <?= $avg_inst >= 4.5 ? 'Sangat Memuaskan' : ($avg_inst >= 3.5 ? 'Memuaskan' : 'Cukup') ?>
                            </div>
                            <?php else: ?>
                            <span class="text-muted">-</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-size:0.8rem;color:var(--text-muted);">
                            <?= date('d/m/Y H:i', strtotime($r['last_submitted_at'])) ?> WIB
                        </td>
                        <td style="text-align:center;">
                            <a href="<?= $detail_url ?>" class="btn-action btn-edit" style="text-decoration:none;" title="Lihat Rekapitulasi Rincian Instansi">
                                <i class="bi bi-eye"></i> Rincian
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    <?php endif; ?>
</div>

<!-- TAB CONTENT 2: Rekapan Mutu per Periode (Tahunan) & Grafik -->
<?php elseif ($tab === 'tahunan'): ?>
<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 style="font-weight:700;color:var(--navy);font-size:1.05rem;margin:0;">
            <i class="bi bi-bar-chart-line text-primary me-2"></i> Rekapitulasi Mutu Tamu per Periode (<?= e($selected_periode) ?>)
        </h5>
        <div style="font-size:0.8rem;color:var(--text-muted);margin-top:2px;">
            Laporan agregasi skor rata-rata kuesioner periode <strong>1 September <?= $p_start_year ?> – 1 Agustus <?= $p_end_year ?></strong> (Otomatis per Siklus Mutu).
        </div>
    </div>
    <div class="d-flex align-items-center gap-2 flex-wrap">
        <div class="btn-group" role="group">
            <?php foreach ($periodes_list as $prd): ?>
            <a href="feedback-kunjungan-list.php?tab=tahunan&periode=<?= urlencode($prd) ?>" 
               class="btn btn-sm <?= $selected_periode === $prd ? 'btn-primary fw-bold' : 'btn-outline-secondary' ?>" 
               style="font-size:0.8rem;padding:0.35rem 0.85rem;">
                <i class="bi bi-calendar-check me-1"></i> Periode <?= e($prd) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <button type="button" onclick="window.print()" class="btn-outline btn-sm" style="font-size:0.8rem;">
            <i class="bi bi-printer me-1"></i> Cetak Laporan Periode
        </button>
    </div>
</div>

<!-- Periodic KPI Metric Cards -->
<div class="row g-3 mb-4">
    <div class="col-md-4">
        <div class="admin-table-wrap p-3" style="background:#F8FAFC;border-left:4px solid #0284C7;">
            <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Instansi Berkunjung (Periode <?= e($selected_periode) ?>)</div>
            <div style="font-size:1.75rem;font-weight:800;color:var(--navy);margin-top:4px;">
                <?= $thn_total_instansi ?> <span style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">Instansi</span>
            </div>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">Mitra institusi terlayani</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-table-wrap p-3" style="background:#F8FAFC;border-left:4px solid #10B981;">
            <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Total Responden Mengisi</div>
            <div style="font-size:1.75rem;font-weight:800;color:#047857;margin-top:4px;">
                <?= $thn_total_responden ?> <span style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">Orang</span>
            </div>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">Akumulasi jawaban kuesioner masuk</div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="admin-table-wrap p-3" style="background:#F8FAFC;border-left:4px solid #F59E0B;">
            <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Indeks Kepuasan Periode (CSAT)</div>
            <div style="font-size:1.75rem;font-weight:800;color:#D97706;margin-top:4px;">
                <i class="bi bi-star-fill text-warning me-1" style="font-size:1.35rem;"></i><?= $thn_avg_csat > 0 ? number_format($thn_avg_csat, 2) : '-' ?> <span style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">/ 5.00</span>
            </div>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                <?= $thn_avg_csat >= 4.5 ? 'Predikat Sangat Memuaskan' : ($thn_avg_csat >= 3.5 ? 'Predikat Memuaskan' : 'Perlu Peningkatan') ?>
            </div>
        </div>
    </div>
</div>

<!-- Annual Chart: Nilai Rata-rata per Butir Pertanyaan -->
<div class="admin-table-wrap p-4 mb-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h6 style="font-weight:800;color:var(--navy);margin:0;font-size:0.95rem;">
                <i class="bi bi-graph-up text-primary me-2"></i> Grafik Nilai Rata-Rata per Butir Pertanyaan Kuesioner (Periode <?= e($selected_periode) ?>)
            </h6>
            <div style="font-size:0.78rem;color:var(--text-muted);">
                Membandingkan capaian rata-rata skor Likert (1.00 s/d 5.00) untuk tiap butir kuesioner pada periode <?= e($selected_periode) ?>. <span class="text-primary fw-bold"><i class="bi bi-cursor-fill"></i> Klik pada batang untuk melihat rincian bintang 1-5.</span>
            </div>
        </div>
        <span class="badge" style="background:#ECFDF5;color:#047857;border:1px solid #A7F3D0;font-size:0.75rem;padding:0.35rem 0.65rem;">
            Target Mutu Minimal: 4.00 / 5.00
        </span>
    </div>
    <div style="position:relative; height:320px; width:100%;">
        <canvas id="annualFeedbackChart"></canvas>
    </div>
</div>

<!-- Table: Nilai Rata-rata Per Butir Kuesioner -->
<div class="admin-table-wrap p-0 mb-4">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2" style="background:#F8FAFC;">
        <div>
            <h6 style="font-weight:700;color:var(--navy);font-size:0.92rem;margin:0;">
                <i class="bi bi-table text-primary me-2"></i> Rekapitulasi Penilaian Rata-Rata per Butir Kuesioner (Periode <?= e($selected_periode) ?>)
            </h6>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                <i class="bi bi-cursor-fill me-1 text-primary"></i> Klik pada baris atau tombol <strong>Bintang 1-5</strong> untuk melihat detail jumlah responden tiap bintang.
            </div>
        </div>
        <span style="font-size:0.78rem;color:var(--text-muted);">
            Dihitung dari <?= $thn_total_responden ?> responden penilai (1 Sep <?= $p_start_year ?> – 1 Agu <?= $p_end_year ?>)
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th style="width:50px;text-align:center;">No</th>
                    <th>Aspek / Butir Pertanyaan Kuesioner</th>
                    <th style="width:170px;">Kategori Mutu</th>
                    <th style="width:130px;text-align:center;">Rata-Rata Nilai</th>
                    <th style="width:130px;text-align:center;">Capaian Mutu</th>
                    <th style="width:150px;text-align:center;">Predikat</th>
                    <th style="width:130px;text-align:center;">Detail Bintang</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($thn_pertanyaan_list)): ?>
                <tr>
                    <td colspan="7" class="text-center py-4 text-muted">Belum ada butir pertanyaan atau data jawaban pada periode <?= e($selected_periode) ?>.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($thn_pertanyaan_list as $i => $q): 
                    $skor_q = $q['avg_skor'] ? round((float)$q['avg_skor'], 2) : 0;
                    $persen_capaian = round(($skor_q / 5) * 100, 1);
                    if ($skor_q >= 4.5) {
                        $pred_txt = 'Sangat Memuaskan';
                        $pred_badge = 'background:#ECFDF5;color:#047857;border:1px solid #A7F3D0;';
                    } elseif ($skor_q >= 4.0) {
                        $pred_txt = 'Memuaskan';
                        $pred_badge = 'background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;';
                    } elseif ($skor_q >= 3.0) {
                        $pred_txt = 'Cukup Baik';
                        $pred_badge = 'background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;';
                    } else {
                        $pred_txt = 'Kurang / Perlu Perbaikan';
                        $pred_badge = 'background:#FEE2E2;color:#B91C1C;border:1px solid #FECACA;';
                    }
                ?>
                <tr style="cursor:pointer;" data-bs-toggle="modal" data-bs-target="#modalThnDistribusi<?= $q['id'] ?>" title="Klik untuk melihat detail jumlah pemberi bintang 1 - 5">
                    <td style="text-align:center;font-weight:700;color:var(--text-muted);"><?= $i + 1 ?></td>
                    <td>
                        <div style="font-weight:700;color:var(--navy);"><?= e($q['pertanyaan']) ?></div>
                        <?php if (!empty($q['keterangan'])): ?>
                        <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($q['keterangan']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge" style="background:#F1F5F9;color:#334155;border:1px solid #CBD5E1;font-size:0.74rem;">
                            <?= e($q['kategori']) ?>
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <span style="font-size:1.1rem;font-weight:800;color:#0284C7;">
                            ⭐ <?= number_format($skor_q, 2) ?>
                        </span>
                        <div style="font-size:0.7rem;color:var(--text-muted);">Skala 1 - 5</div>
                    </td>
                    <td style="text-align:center;">
                        <div class="progress" style="height:6px;border-radius:10px;margin-bottom:4px;">
                            <div class="progress-bar bg-success" style="width:<?= $persen_capaian ?>%"></div>
                        </div>
                        <span style="font-size:0.75rem;font-weight:700;color:#047857;"><?= $persen_capaian ?>%</span>
                    </td>
                    <td style="text-align:center;">
                        <span class="badge" style="<?= $pred_badge ?>font-size:0.75rem;padding:0.35rem 0.65rem;">
                            <?= $pred_txt ?>
                        </span>
                    </td>
                    <td style="text-align:center;" onclick="event.stopPropagation();">
                        <button type="button" class="btn btn-sm btn-outline-primary fw-bold" data-bs-toggle="modal" data-bs-target="#modalThnDistribusi<?= $q['id'] ?>" style="font-size:0.75rem;padding:0.3rem 0.65rem;border-radius:20px;">
                            <i class="bi bi-bar-chart-fill text-warning me-1"></i> Bintang 1-5
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL POPUPS: Rincian Jumlah Pemberi Bintang (1-5) per Butir Pertanyaan Periode -->
<?php 
$thn_star_levels = [
    5 => ['label' => 'Bintang 5 (Sangat Baik)', 'stars' => '⭐⭐⭐⭐⭐', 'bar' => '#10B981', 'bg' => '#ECFDF5', 'text' => '#047857'],
    4 => ['label' => 'Bintang 4 (Baik)', 'stars' => '⭐⭐⭐⭐', 'bar' => '#0284C7', 'bg' => '#EFF6FF', 'text' => '#1D4ED8'],
    3 => ['label' => 'Bintang 3 (Cukup)', 'stars' => '⭐⭐⭐', 'bar' => '#F59E0B', 'bg' => '#FEF3C7', 'text' => '#B45309'],
    2 => ['label' => 'Bintang 2 (Kurang)', 'stars' => '⭐⭐', 'bar' => '#F97316', 'bg' => '#FFF7ED', 'text' => '#C2410C'],
    1 => ['label' => 'Bintang 1 (Sangat Kurang)', 'stars' => '⭐', 'bar' => '#EF4444', 'bg' => '#FEF2F2', 'text' => '#B91C1C']
];

foreach ($thn_pertanyaan_list as $i => $q): 
    $skor_q = $q['avg_skor'] ? round((float)$q['avg_skor'], 2) : 0;
    $persen_capaian = round(($skor_q / 5) * 100, 1);
    if ($skor_q >= 4.5) {
        $p_text = 'Sangat Memuaskan';
        $p_badge = 'background:#ECFDF5;color:#047857;border:1px solid #A7F3D0;';
    } elseif ($skor_q >= 4.0) {
        $p_text = 'Memuaskan';
        $p_badge = 'background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;';
    } elseif ($skor_q >= 3.0) {
        $p_text = 'Cukup Baik';
        $p_badge = 'background:#FEF3C7;color:#92400E;border:1px solid #FDE68A;';
    } else {
        $p_text = 'Kurang Baik';
        $p_badge = 'background:#FEE2E2;color:#B91C1C;border:1px solid #FECACA;';
    }
?>
<div class="modal fade" id="modalThnDistribusi<?= $q['id'] ?>" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;box-shadow:0 25px 50px rgba(0,0,0,0.25);">
            <!-- Modal Header -->
            <div class="modal-header" style="background:var(--navy);color:#fff;padding:1.25rem 1.5rem;">
                <div>
                    <div class="badge bg-white-subtle text-white border border-white-50 fw-bold mb-1" style="font-size:0.75rem;">
                        Butir Pertanyaan <?= $i + 1 ?> &bull; <?= e($q['kategori']) ?> (Periode <?= e($selected_periode) ?>)
                    </div>
                    <h5 class="modal-title fw-bold mb-0" style="font-size:1.05rem;line-height:1.4;">
                        <?= e($q['pertanyaan']) ?>
                    </h5>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>

            <!-- Modal Body -->
            <div class="modal-body p-4 text-start">
                <!-- Summary Card -->
                <div class="p-3 mb-4 rounded-3 d-flex justify-content-between align-items-center flex-wrap gap-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                    <div>
                        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Rata-Rata Nilai Butir (Periode <?= e($selected_periode) ?>)</div>
                        <div style="font-size:2.2rem;font-weight:900;color:#0284C7;line-height:1.1;margin-top:2px;">
                            ⭐ <?= number_format($skor_q, 2) ?> <span style="font-size:0.95rem;color:var(--text-muted);font-weight:600;">/ 5.00</span>
                        </div>
                        <div style="font-size:0.78rem;color:var(--text-muted);margin-top:2px;">
                            Tingkat Capaian: <strong class="text-dark"><?= $persen_capaian ?>%</strong> (Rentang: <?= $q['min_skor'] ?? 0 ?> s/d <?= $q['max_skor'] ?? 5 ?>)
                        </div>
                    </div>
                    <div class="text-end">
                        <div style="font-size:0.75rem;font-weight:700;color:var(--text-muted);text-transform:uppercase;">Total Responden Periode Ini</div>
                        <div style="font-size:1.8rem;font-weight:800;color:var(--navy);margin-top:2px;">
                            <?= $thn_total_responden ?> <span style="font-size:0.85rem;color:var(--text-muted);font-weight:600;">Orang</span>
                        </div>
                        <span class="badge" style="<?= $p_badge ?>font-size:0.75rem;padding:0.35rem 0.65rem;">
                            <?= $p_text ?>
                        </span>
                    </div>
                </div>

                <!-- Star Distribution Progress Bars (1 to 5 Stars) -->
                <h6 class="fw-bold mb-3" style="color:var(--navy);font-size:0.95rem;display:flex;align-items:center;gap:6px;">
                    <i class="bi bi-bar-chart-fill text-primary"></i> Rincian Jumlah Pemberi Bintang (1 s/d 5)
                </h6>

                <div class="d-flex flex-column gap-2 mb-4">
                    <?php foreach ($thn_star_levels as $star_num => $cfg): 
                        $jml = $thn_distribusi_bintang[$q['id']][$star_num]['count'] ?? 0;
                        $pct = $thn_total_responden > 0 ? round(($jml / $thn_total_responden) * 100, 1) : 0;
                    ?>
                    <div class="p-2.5 px-3 rounded-2 border d-flex align-items-center gap-3" style="background:#fff;">
                        <div style="min-width:170px;">
                            <div class="fw-bold" style="font-size:0.85rem;color:var(--navy);"><?= $cfg['label'] ?></div>
                            <div style="font-size:0.72rem;letter-spacing:1px;"><?= $cfg['stars'] ?></div>
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
                    <i class="bi bi-people-fill text-primary"></i> Daftar Nama Anggota Berdasarkan Bintang:
                </h6>

                <div class="accordion" id="accordionThnRespondents<?= $q['id'] ?>">
                    <?php foreach ($thn_star_levels as $star_num => $cfg): 
                        $resps = $thn_distribusi_bintang[$q['id']][$star_num]['respondents'] ?? [];
                        if (empty($resps)) continue;
                    ?>
                    <div class="accordion-item border rounded mb-2 overflow-hidden">
                        <h2 class="accordion-header" id="headingThnStar<?= $q['id'] ?>_<?= $star_num ?>">
                            <button class="accordion-button collapsed py-2.5 px-3" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThnStar<?= $q['id'] ?>_<?= $star_num ?>" aria-expanded="false" style="font-size:0.85rem;background:#F8FAFC;font-weight:700;">
                                <span class="badge me-2" style="background:<?= $cfg['bg'] ?>;color:<?= $cfg['text'] ?>;"><?= $cfg['stars'] ?></span>
                                <?= $cfg['label'] ?> &mdash; <strong class="ms-1 text-dark"><?= count($resps) ?> Orang</strong>
                            </button>
                        </h2>
                        <div id="collapseThnStar<?= $q['id'] ?>_<?= $star_num ?>" class="accordion-collapse collapse" data-bs-parent="#accordionThnRespondents<?= $q['id'] ?>">
                            <div class="accordion-body p-3 pt-2" style="background:#fff;">
                                <div class="row g-2">
                                    <?php foreach ($resps as $r_item): ?>
                                    <div class="col-md-6">
                                        <div class="p-2 px-2.5 rounded border" style="background:#F8FAFC;font-size:0.82rem;">
                                            <div class="fw-bold" style="color:var(--navy);"><?= e($r_item['nama']) ?></div>
                                            <div style="font-size:0.75rem;color:var(--text-muted);"><?= e($r_item['jabatan']) ?> &bull; <span class="fw-semibold text-primary"><?= e($r_item['institusi']) ?></span></div>
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

<!-- Table: Breakdown Instansi yang Berkunjung di Periode Tersebut -->
<div class="admin-table-wrap p-0 mb-4">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center" style="background:#F8FAFC;">
        <h6 style="font-weight:700;color:var(--navy);font-size:0.92rem;margin:0;">
            <i class="bi bi-buildings text-primary me-2"></i> Daftar Kunjungan Instansi pada Periode <?= e($selected_periode) ?>
        </h6>
        <span style="font-size:0.78rem;color:var(--text-muted);">
            Total <?= count($thn_instansi_list) ?> instansi tamu
        </span>
    </div>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th style="width:50px;text-align:center;">No</th>
                    <th>Institusi Tamu</th>
                    <th>Tanggal Kunjungan</th>
                    <th style="text-align:center;">Jumlah Responden</th>
                    <th style="text-align:center;">Rata-Rata CSAT</th>
                    <th style="text-align:center;width:120px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($thn_instansi_list)): ?>
                <tr>
                    <td colspan="6" class="text-center py-4 text-muted">Belum ada kunjungan pada periode <?= e($selected_periode) ?>.</td>
                </tr>
                <?php else: ?>
                <?php foreach ($thn_instansi_list as $idx => $ti): 
                    $inst_detail_url = $ti['token_id'] > 0 
                        ? 'feedback-kunjungan-detail.php?token_id=' . $ti['token_id'] 
                        : 'feedback-kunjungan-detail.php?id=' . $ti['first_respon_id'];
                    $ti_avg = $ti['avg_skor'] ? round((float)$ti['avg_skor'], 2) : 0;
                ?>
                <tr>
                    <td style="text-align:center;color:var(--text-muted);font-weight:600;"><?= $idx + 1 ?></td>
                    <td>
                        <div style="font-weight:700;color:var(--navy);"><?= e($ti['nama_institusi']) ?></div>
                        <?php if (!empty($ti['perihal'])): ?>
                        <div style="font-size:0.75rem;color:var(--text-muted);"><?= e(truncate($ti['perihal'], 50)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= formatTanggal($ti['tanggal_kunjungan']) ?></td>
                    <td style="text-align:center;">
                        <span class="badge" style="background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;font-size:0.8rem;padding:0.35rem 0.65rem;border-radius:20px;">
                            <i class="bi bi-people-fill me-1"></i> <?= $ti['total_responden'] ?> Orang
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <span class="badge" style="background:#E0F2FE;color:#0284C7;font-size:0.85rem;padding:0.35rem 0.65rem;font-weight:800;border:1px solid #BAE6FD;">
                            ⭐ <?= number_format($ti_avg, 2) ?>
                        </span>
                    </td>
                    <td style="text-align:center;">
                        <a href="<?= $inst_detail_url ?>" class="btn-action btn-edit" title="Lihat Rincian Instansi">
                            <i class="bi bi-eye"></i> Rincian
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- TAB CONTENT 2: Manajemen Token -->
<?php elseif ($tab === 'token'): ?>
<div class="admin-table-wrap p-0">
    <div class="p-3 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h5 style="font-weight:700;color:var(--navy);font-size:1rem;margin:0;">
                Daftar Kode Token Kunjungan
            </h5>
            <div style="font-size:0.8rem;color:var(--text-muted);">
                Tiap kode token bersifat unik dan single-use (otomatis terkunci &amp; masuk ke riwayat setelah kuesioner terisi).
            </div>
        </div>
        <button type="button" class="btn-add btn-sm" data-bs-toggle="modal" data-bs-target="#modalGenerateToken">
            <i class="bi bi-plus-circle me-1"></i> Buat Token Baru
        </button>
    </div>

    <!-- Sub-Filter Bar (Semua, Belum Kadaluarsa, Kadaluarsa) -->
    <div class="px-3 pt-2.5 pb-2.5 border-bottom d-flex gap-2 flex-wrap align-items-center" style="background:#F8FAFC;">
        <span style="font-size:0.8rem;font-weight:700;color:var(--text-muted);margin-right:4px;">Filter Tampilan:</span>
        <a href="feedback-kunjungan-list.php?tab=token&filter=all" class="btn btn-sm <?= $token_filter === 'all' ? 'btn-dark fw-bold' : 'btn-outline-secondary' ?>" style="font-size:0.78rem;padding:0.25rem 0.75rem;border-radius:20px;">
            Semua Token (<?= $total_token ?>)
        </a>
        <a href="feedback-kunjungan-list.php?tab=token&filter=active" class="btn btn-sm <?= $token_filter === 'active' ? 'btn-dark fw-bold' : 'btn-outline-secondary' ?>" style="font-size:0.78rem;padding:0.25rem 0.75rem;border-radius:20px;">
            <i class="bi bi-check-circle me-1"></i> Belum Kadaluarsa (<?= $unexpired_count ?>)
        </a>
        <a href="feedback-kunjungan-list.php?tab=token&filter=history" class="btn btn-sm <?= $token_filter === 'history' ? 'btn-secondary fw-bold text-white' : 'btn-outline-secondary' ?>" style="font-size:0.78rem;padding:0.25rem 0.75rem;border-radius:20px;">
            <i class="bi bi-clock-history me-1"></i> Kadaluarsa (<?= $expired_count ?>)
        </a>
    </div>

    <?php if (empty($token_list)): ?>
    <div class="text-center py-5 text-muted">
        <i class="bi bi-key" style="font-size:2.5rem;color:#CBD5E1;display:block;margin-bottom:0.75rem;"></i>
        <div style="font-weight:700;">Belum Ada Token pada Kategori Ini</div>
        <div style="font-size:0.82rem;margin-top:4px;">Silakan buat token baru atau pilih filter kategori token lainnya di atas.</div>
    </div>
    <?php else: ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
            <thead style="background:#F8FAFC;">
                <tr>
                    <th style="width:50px;text-align:center;">No</th>
                    <th>Kode Token</th>
                    <th>Institusi Tamu</th>
                    <th>Tanggal Kunjungan</th>
                    <th>Masa Berlaku</th>
                    <th style="text-align:center;">Status</th>
                    <th style="text-align:center;width:200px;">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $now = date('Y-m-d H:i:s');
                foreach ($token_list as $idx => $t): 
                    $is_expired    = ($now > $t['berlaku_sampai']);
                    $has_responden = ($t['status'] === 'Digunakan' || (int)($t['respon_count'] ?? 0) > 0);
                    $can_delete    = true;
                ?>
                <tr>
                    <td style="text-align:center;color:var(--text-muted);font-weight:600;"><?= $idx + 1 ?></td>
                    <td>
                        <div class="d-flex align-items-center gap-2">
                            <span class="badge" style="background:#F1F5F9;border:1.5px solid #CBD5E1;color:#0F172A;font-family:monospace;font-size:0.95rem;padding:0.4rem 0.65rem;letter-spacing:1px;font-weight:800;">
                                <?= e($t['token']) ?>
                            </span>
                            <button type="button" class="btn btn-sm btn-outline-secondary p-1" title="Salin Kode" onclick="copyText('<?= e($t['token']) ?>', this)">
                                <i class="bi bi-clipboard"></i>
                            </button>
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:700;color:var(--navy);"><?= e($t['nama_institusi']) ?></div>
                        <?php if (!empty($t['perihal'])): ?>
                        <div style="font-size:0.75rem;color:var(--text-muted);"><?= e(truncate($t['perihal'], 50)) ?></div>
                        <?php endif; ?>
                    </td>
                    <td><?= formatTanggal($t['tanggal_kunjungan']) ?></td>
                    <td style="font-size:0.8rem;">
                        <div>Mulai: <strong><?= date('d/m/Y H:i', strtotime($t['berlaku_mulai'])) ?> WIB</strong></div>
                        <div class="<?= $is_expired ? 'text-danger fw-bold' : 'text-primary fw-bold' ?>">
                            Sampai: <strong><?= date('d/m/Y H:i', strtotime($t['berlaku_sampai'])) ?> WIB</strong>
                        </div>
                    </td>
                    <td style="text-align:center;">
                        <?php if ($has_responden): ?>
                        <span class="badge bg-success" style="font-size:0.75rem;padding:0.35rem 0.6rem;" title="Kuesioner telah terisi">
                            <i class="bi bi-check-circle-fill me-1"></i> Sudah Diisi
                        </span>
                        <?php elseif ($is_expired): ?>
                        <span class="badge bg-secondary" style="font-size:0.75rem;padding:0.35rem 0.6rem;">
                            <i class="bi bi-clock-history me-1"></i> Kadaluarsa
                        </span>
                        <?php elseif ($t['status'] === 'Aktif'): 
                            $diff = strtotime($t['berlaku_sampai']) - time();
                            if ($diff > 0) {
                                $mins = (int)ceil($diff / 60);
                                if ($mins < 60) {
                                    $sisa_txt = $mins . ' m';
                                } elseif ($mins < 1440) {
                                    $sisa_txt = floor($mins / 60) . 'j ' . ($mins % 60) . 'm';
                                } else {
                                    $sisa_txt = floor($mins / 1440) . ' Hari';
                                }
                            } else {
                                $sisa_txt = '0m';
                            }
                        ?>
                        <span class="badge" style="background:#ECFDF5;color:#047857;border:1px solid #A7F3D0;font-size:0.75rem;padding:0.35rem 0.6rem;" title="Token sedang aktif dan dapat digunakan">
                            <i class="bi bi-stopwatch me-1"></i> Aktif (Sisa <?= $sisa_txt ?>)
                        </span>
                        <?php else: ?>
                        <span class="badge bg-danger" style="font-size:0.75rem;padding:0.35rem 0.6rem;">
                            <?= e($t['status']) ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align:center;">
                        <div class="d-inline-flex gap-1 align-items-center">
                            <?php if (!$is_expired): ?>
                            <button type="button" class="btn-action btn-edit" data-bs-toggle="modal" data-bs-target="#modalEditToken<?= $t['id'] ?>" title="Edit Informasi Kunjungan">
                                <i class="bi bi-pencil-square"></i> Edit
                            </button>
                            <?php else: ?>
                            <span class="badge bg-light text-muted border" style="font-size:0.72rem;padding:0.35rem 0.5rem;font-weight:600;" title="Masa berlaku telah berakhir">
                                <i class="bi bi-lock-fill text-secondary"></i> Terkunci
                            </span>
                            <?php endif; ?>

                            <a href="<?= SITE_URL ?>/feedback-kunjungan.php?token=<?= e($t['token']) ?>" target="_blank" class="btn-action btn-edit" title="Buka Form Kuesioner">
                                <i class="bi bi-box-arrow-up-right"></i> Form
                            </a>

                            <?php if ($has_responden): ?>
                            <a href="feedback-kunjungan-list.php?tab=respon" class="btn-action" style="background:#EFF6FF;color:#1D4ED8;border:1px solid #BFDBFE;text-decoration:none;font-size:0.75rem;padding:0.25rem 0.5rem;border-radius:6px;display:inline-flex;align-items:center;gap:4px;" title="Lihat Rekapitulasi Respon">
                                <i class="bi bi-chat-left-quote"></i> Respon
                            </a>
                            <?php endif; ?>

                            <!-- Tombol Hapus Token (Aktif maupun Kadaluarsa dapat dihapus) -->
                            <button type="button" class="btn-action btn-delete" data-bs-toggle="modal" data-bs-target="#modalDeleteToken<?= $t['id'] ?>" title="Hapus Kode Token">
                                <i class="bi bi-trash3"></i> Hapus
                            </button>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- MODALS UNTUK TOKEN (Ditempatkan di luar tabel agar positioning & z-index Bootstrap bekerja optimal) -->
    <?php foreach ($token_list as $t): 
        $is_expired    = ($now > $t['berlaku_sampai']);
        $has_responden = ($t['status'] === 'Digunakan' || (int)($t['respon_count'] ?? 0) > 0);
    ?>
        <?php if (!$is_expired && $t['status'] === 'Aktif'): ?>
        <!-- MODAL: Edit Token Informasi -->
        <div class="modal fade" id="modalEditToken<?= $t['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
                    <div class="modal-header" style="background:var(--navy);color:#fff;padding:1.25rem 1.5rem;">
                        <h5 class="modal-title fw-bold d-flex align-items-center gap-2" style="font-size:1.05rem;">
                            <i class="bi bi-pencil-square text-warning"></i> Edit Informasi Token Kunjungan
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post" action="feedback-kunjungan-list.php?tab=token">
                        <input type="hidden" name="action" value="edit_token">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <div class="modal-body p-4 text-start">
                            
                            <!-- Token Code Display (Read-Only) -->
                            <div class="mb-3 p-2.5 rounded-3" style="background:#F8FAFC;border:1.5px dashed #CBD5E1;">
                                <label class="form-label fw-bold mb-1" style="font-size:0.75rem;color:var(--text-muted);text-transform:uppercase;letter-spacing:0.5px;">Kode Token (Permanen / Tidak Berubah)</label>
                                <div class="d-flex align-items-center gap-2">
                                    <span class="badge" style="background:#0F172A;color:#fff;font-family:monospace;font-size:1rem;padding:0.4rem 0.75rem;letter-spacing:1px;font-weight:800;">
                                        <?= e($t['token']) ?>
                                    </span>
                                    <small class="text-muted" style="font-size:0.75rem;"><i class="bi bi-shield-lock-fill text-warning me-1"></i>Kode token tetap sama &amp; tidak berubah</small>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size:0.85rem;">Nama Institusi / Tamu <span class="text-danger">*</span></label>
                                <input type="text" name="nama_institusi" class="form-control" value="<?= e($t['nama_institusi']) ?>" required style="border:1.5px solid var(--border);">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size:0.85rem;">Tanggal Pelaksanaan Kunjungan <span class="text-danger">*</span></label>
                                <input type="date" name="tanggal_kunjungan" class="form-control" value="<?= e($t['tanggal_kunjungan']) ?>" required style="border:1.5px solid var(--border);">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size:0.85rem;">Perihal / Topik Kunjungan</label>
                                <input type="text" name="perihal" class="form-control" value="<?= e($t['perihal']) ?>" placeholder="Contoh: Studi Banding Tata Kelola SPMI" style="border:1.5px solid var(--border);">
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-bold" style="font-size:0.85rem;">Email PIC Instansi (Opsional)</label>
                                <input type="email" name="email" class="form-control" value="<?= e($t['email']) ?>" placeholder="humas@instansi.ac.id" style="border:1.5px solid var(--border);">
                            </div>

                            <div class="mb-2 p-3 rounded-3" style="background:#F0FDF4;border:1px solid #BBF7D0;">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="form-label fw-bold mb-0 text-success" style="font-size:0.82rem;">
                                        <i class="bi bi-stopwatch me-1"></i> Masa Berlaku Saat Ini
                                    </label>
                                    <span class="badge bg-success" style="font-size:0.75rem;">
                                        <?= date('d/m/Y H:i', strtotime($t['berlaku_sampai'])) ?> WIB
                                    </span>
                                </div>
                                <div class="form-check form-switch mt-2">
                                    <input class="form-check-input" type="checkbox" role="switch" id="toggleTimerEdit<?= $t['id'] ?>" name="update_timer" value="1" onchange="document.getElementById('timerEditSection<?= $t['id'] ?>').style.display = this.checked ? 'block' : 'none';">
                                    <label class="form-check-label fw-semibold" for="toggleTimerEdit<?= $t['id'] ?>" style="font-size:0.8rem;color:#166534;">
                                        Perpanjang / setel ulang timer masa berlaku dari sekarang
                                    </label>
                                </div>

                                <div id="timerEditSection<?= $t['id'] ?>" style="display:none;margin-top:0.75rem;">
                                    <div class="input-group mb-2">
                                        <span class="input-group-text bg-white" style="border:1.5px solid var(--border);border-right:none;"><i class="bi bi-hourglass-split"></i></span>
                                        <input type="number" name="durasi_menit" id="durasiMenitEdit<?= $t['id'] ?>" class="form-control" value="60" min="1" max="10080" style="border:1.5px solid var(--border);font-weight:700;font-size:1rem;color:var(--navy);">
                                        <span class="input-group-text bg-light fw-bold" style="border:1.5px solid var(--border);border-left:none;">Menit</span>
                                    </div>
                                    <div class="d-flex flex-wrap gap-1">
                                        <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitEdit<?= $t['id'] ?>').value = 15;">15 Menit</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitEdit<?= $t['id'] ?>').value = 30;">30 Menit</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitEdit<?= $t['id'] ?>').value = 45;">45 Menit</button>
                                        <button type="button" class="btn btn-sm btn-primary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitEdit<?= $t['id'] ?>').value = 60;">1 Jam</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitEdit<?= $t['id'] ?>').value = 120;">2 Jam</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitEdit<?= $t['id'] ?>').value = 240;">4 Jam</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitEdit<?= $t['id'] ?>').value = 1440;">1 Hari</button>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitEdit<?= $t['id'] ?>').value = 2880;">2 Hari</button>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="modal-footer bg-light px-4 py-3" style="border-top:1px solid var(--border);">
                            <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                            <button type="submit" class="btn-save">
                                <i class="bi bi-check2-circle me-1"></i> Simpan Perubahan
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- MODAL: Hapus Token -->
        <div class="modal fade" id="modalDeleteToken<?= $t['id'] ?>" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered" style="max-width:480px;">
                <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;box-shadow:0 20px 40px rgba(0,0,0,0.2);">
                    <div class="modal-header" style="background:#DC2626;color:#fff;padding:1.2rem 1.5rem;">
                        <h5 class="modal-title fw-bold d-flex align-items-center gap-2" style="font-size:1.05rem;">
                            <i class="bi bi-trash3-fill"></i> Konfirmasi Hapus Token
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <form method="post" action="feedback-kunjungan-list.php?tab=token">
                        <input type="hidden" name="action" value="delete_token">
                        <input type="hidden" name="id" value="<?= $t['id'] ?>">
                        <input type="hidden" name="filter" value="<?= e($token_filter) ?>">
                        <div class="modal-body p-4 text-start">
                            <p style="font-size:0.92rem;color:var(--navy);margin-bottom:1rem;">
                                Apakah Anda yakin ingin menghapus kode token kunjungan berikut?
                            </p>

                            <div class="p-3 rounded-3 mb-3" style="background:#FEF2F2;border:1.5px solid #FECACA;">
                                <div class="d-flex align-items-center gap-2 mb-2">
                                    <span class="badge" style="background:#991B1B;color:#fff;font-family:monospace;font-size:1rem;padding:0.4rem 0.75rem;letter-spacing:1px;font-weight:800;">
                                        <?= e($t['token']) ?>
                                    </span>
                                    <?php if ($is_expired): ?>
                                    <span class="badge bg-secondary" style="font-size:0.75rem;">Kadaluarsa</span>
                                    <?php else: ?>
                                    <span class="badge bg-success" style="font-size:0.75rem;">Aktif</span>
                                    <?php endif; ?>
                                </div>
                                <div style="font-weight:700;color:var(--navy);font-size:0.95rem;">
                                    <?= e($t['nama_institusi']) ?>
                                </div>
                                <?php if (!empty($t['perihal'])): ?>
                                <div style="font-size:0.8rem;color:var(--text-muted);margin-top:2px;">
                                    <?= e($t['perihal']) ?>
                                </div>
                                <?php endif; ?>
                                <div style="font-size:0.78rem;color:var(--text-muted);margin-top:4px;">
                                    <i class="bi bi-calendar-event me-1"></i> Tanggal: <?= formatTanggal($t['tanggal_kunjungan']) ?>
                                </div>
                            </div>

                            <?php if ($has_responden): ?>
                            <div class="alert alert-warning d-flex align-items-start gap-2 py-2.5 px-3 mb-0" style="font-size:0.82rem;border-radius:10px;">
                                <i class="bi bi-exclamation-triangle-fill text-warning flex-shrink-0 mt-0.5"></i>
                                <div>
                                    <strong>Peringatan Data Terkait:</strong> Token ini telah digunakan oleh <strong><?= (int)$t['respon_count'] ?> responden</strong>. Menghapus token ini akan sekaligus membersihkan data responden &amp; jawaban kuesioner terkait.
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="text-muted" style="font-size:0.82rem;">
                                <i class="bi bi-info-circle me-1"></i> Token ini belum memiliki respon. Data token akan dihapus secara permanen.
                            </div>
                            <?php endif; ?>
                        </div>
                        <div class="modal-footer bg-light px-4 py-3" style="border-top:1px solid var(--border);">
                            <button type="button" class="btn btn-secondary px-3 py-1.5" data-bs-dismiss="modal" style="font-size:0.85rem;border-radius:8px;">Batal</button>
                            <button type="submit" class="btn btn-danger px-3 py-1.5 fw-semibold d-inline-flex align-items-center gap-1.5" style="font-size:0.85rem;border-radius:8px;">
                                <i class="bi bi-trash3-fill"></i> Ya, Hapus Token
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<!-- TAB CONTENT 3: Pengaturan -->
<?php elseif ($tab === 'settings'): ?>
<div class="row">
    <div class="col-lg-7">
        <div class="admin-table-wrap p-4">
            <h5 style="font-weight:700;color:var(--navy);font-size:1.05rem;margin-bottom:1.25rem;">
                <i class="bi bi-gear text-primary me-2"></i> Pengaturan Umum Kuesioner Umpan Balik
            </h5>
            <form method="post" action="feedback-kunjungan-list.php">
                <input type="hidden" name="action" value="save_settings">

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:0.85rem;">Judul Kuesioner di Halaman Publik</label>
                    <input type="text" name="feedback_kunjungan_judul" class="form-control" value="<?= e(getPengaturan('feedback_kunjungan_judul', 'Kuesioner Umpan Balik Kunjungan Mitra Institusi')) ?>" required style="border:1.5px solid var(--border);">
                    <div class="form-text">Judul besar yang ditampilkan di hero banner halaman kuesioner.</div>
                </div>

                <div class="mb-4">
                    <label class="form-label fw-bold" style="font-size:0.85rem;"><i class="bi bi-clock me-1"></i> Standar Durasi Timer Default (Menit)</label>
                    <input type="number" name="feedback_kunjungan_durasi_menit" class="form-control" value="<?= e(getPengaturan('feedback_kunjungan_durasi_menit', '60')) ?>" min="1" max="10080" required style="border:1.5px solid var(--border);">
                    <div class="form-text">Nilai default awal pada timer saat membuka modal buat token (misal: 60 menit). Anda tetap bebas memilih atau mengetikkan menit berapa pun secara fleksibel setiap kali membuat token.</div>
                </div>

                <button type="submit" class="btn-save px-4">
                    Simpan Pengaturan
                </button>
            </form>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="admin-table-wrap p-4">
            <h5 style="font-weight:700;color:var(--navy);font-size:1.05rem;margin-bottom:1rem;">
                ℹ️ Informasi Kebijakan &amp; Skala
            </h5>
            <div style="font-size:0.85rem;color:var(--text);line-height:1.6;">
                <p>
                    Kuesioner kepuasan ini menggunakan standar <strong>Skala Likert 1 s/d 5</strong> yang merupakan praktik baku penjaminan mutu pendidikan tinggi:
                </p>
                <ul class="mb-3 ps-3">
                    <li><strong>1:</strong> Sangat Kurang</li>
                    <li><strong>2:</strong> Kurang</li>
                    <li><strong>3:</strong> Cukup</li>
                    <li><strong>4:</strong> Baik / Memuaskan</li>
                    <li><strong>5:</strong> Sangat Baik / Sangat Memuaskan</li>
                </ul>
                <p class="mb-0 text-muted" style="font-size:0.8rem;">
                    Butir pertanyaan dapat disesuaikan sewaktu-waktu melalui menu <a href="feedback-kunjungan-pertanyaan.php" class="fw-bold text-primary">Kelola Butir Kuesioner</a>.
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- MODAL: Generate Token Manual -->
<div class="modal fade" id="modalGenerateToken" tabindex="-1" aria-labelledby="modalGenerateTokenLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius:var(--radius-md);border:1px solid var(--border);">
            <div class="modal-header" style="background:var(--navy);color:#fff;">
                <h5 class="modal-title" id="modalGenerateTokenLabel" style="font-size:1rem;font-weight:700;">
                    <i class="bi bi-key me-2"></i> Buat Kode Token Kunjungan
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="post" action="feedback-kunjungan-list.php">
                <input type="hidden" name="action" value="generate_token_manual">
                <div class="modal-body p-4">
                    <p style="font-size:0.82rem;color:var(--text-muted);margin-bottom:1.25rem;">
                        Masukkan rincian identitas instansi tamu yang berkunjung. Sistem akan membuat kode token unik yang berlaku khusus untuk kunjungan tersebut.
                    </p>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;">Nama Institusi / Tamu <span class="text-danger">*</span></label>
                        <input type="text" name="nama_institusi" class="form-control" placeholder="Contoh: Universitas Sanata Dharma" required style="border:1.5px solid var(--border);">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;">Tanggal Pelaksanaan Kunjungan <span class="text-danger">*</span></label>
                        <input type="date" name="tanggal_kunjungan" class="form-control" value="<?= date('Y-m-d') ?>" required style="border:1.5px solid var(--border);">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;">Perihal / Topik Kunjungan</label>
                        <input type="text" name="perihal" class="form-control" placeholder="Contoh: Studi Banding Tata Kelola SPMI & Kurikulum OBE" style="border:1.5px solid var(--border);">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-bold" style="font-size:0.85rem;">Email PIC Instansi (Opsional)</label>
                        <input type="email" name="email" class="form-control" placeholder="humas@instansi.ac.id" style="border:1.5px solid var(--border);">
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label fw-bold mb-0" style="font-size:0.85rem;">
                                <i class="bi bi-stopwatch text-primary me-1"></i> Masa Berlaku Token (Timer) <span class="text-danger">*</span>
                            </label>
                            <span class="badge" id="timerBadgePreview" style="background:#EEF2FF;color:#4F46E5;border:1px solid #C7D2FE;font-size:0.75rem;font-weight:700;">
                                1 Jam
                            </span>
                        </div>
                        
                        <div class="input-group mb-2">
                            <span class="input-group-text bg-white" style="border:1.5px solid var(--border);border-right:none;color:var(--text-muted);">
                                <i class="bi bi-hourglass-split"></i>
                            </span>
                            <input type="number" name="durasi_menit" id="durasiMenitInput" class="form-control" value="<?= e(getPengaturan('feedback_kunjungan_durasi_menit', '60')) ?>" min="1" max="10080" required style="border:1.5px solid var(--border);font-size:1.05rem;font-weight:700;color:var(--navy);" oninput="updateTimerPreview()">
                            <span class="input-group-text bg-light fw-bold" style="border:1.5px solid var(--border);border-left:none;color:var(--navy);font-size:0.85rem;">
                                Menit
                            </span>
                        </div>

                        <!-- Quick Timer Presets -->
                        <div class="mb-2">
                            <div class="text-muted mb-1" style="font-size:0.72rem;font-weight:600;text-transform:uppercase;letter-spacing:0.5px;">Pilihan Cepat Timer:</div>
                            <div class="d-flex flex-wrap gap-1" id="timerQuickButtons">
                                <button type="button" class="btn btn-sm btn-outline-secondary timer-btn" data-mins="15" style="border-radius:20px;font-size:0.75rem;padding:0.25rem 0.65rem;" onclick="setTimer(15, this)">⏱️ 15 Menit</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary timer-btn" data-mins="30" style="border-radius:20px;font-size:0.75rem;padding:0.25rem 0.65rem;" onclick="setTimer(30, this)">⏱️ 30 Menit</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary timer-btn" data-mins="45" style="border-radius:20px;font-size:0.75rem;padding:0.25rem 0.65rem;" onclick="setTimer(45, this)">⏱️ 45 Menit</button>
                                <button type="button" class="btn btn-sm btn-primary timer-btn active" data-mins="60" style="border-radius:20px;font-size:0.75rem;padding:0.25rem 0.65rem;" onclick="setTimer(60, this)">⏱️ 1 Jam</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary timer-btn" data-mins="120" style="border-radius:20px;font-size:0.75rem;padding:0.25rem 0.65rem;" onclick="setTimer(120, this)">⏱️ 2 Jam</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary timer-btn" data-mins="240" style="border-radius:20px;font-size:0.75rem;padding:0.25rem 0.65rem;" onclick="setTimer(240, this)">⏱️ 4 Jam</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary timer-btn" data-mins="1440" style="border-radius:20px;font-size:0.75rem;padding:0.25rem 0.65rem;" onclick="setTimer(1440, this)">⏱️ 1 Hari</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary timer-btn" data-mins="2880" style="border-radius:20px;font-size:0.75rem;padding:0.25rem 0.65rem;" onclick="setTimer(2880, this)">⏱️ 2 Hari</button>
                            </div>
                        </div>

                        <!-- Live Timer Alert Box -->
                        <div class="p-2.5 rounded-3 d-flex align-items-center gap-2 mt-2" style="background:#F0FDF4;border:1px solid #BBF7D0;font-size:0.8rem;color:#166534;">
                            <i class="bi bi-clock-history fs-5 text-success"></i>
                            <div style="line-height:1.4;">
                                Token aktif mulai <strong>sekarang</strong> dan berakhir pada:
                                <br><strong id="previewWaktuBerakhir" style="font-size:0.9rem;color:#15803D;">--:-- WIB</strong>
                            </div>
                        </div>
                        <div class="form-text mt-1" style="font-size:0.75rem;color:var(--text-muted);">
                            <i class="bi bi-info-circle me-1"></i> Timer fleksibel! Anda dapat mengetikkan jumlah menit secara manual atau memilih tombol cepat di atas.
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light" style="border-top:1px solid var(--border);">
                    <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn-save">
                        <i class="bi bi-magic me-1"></i> Generate Token Sekarang
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function copyText(text, btn) {
    navigator.clipboard.writeText(text).then(function() {
        var originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="bi bi-check text-success"></i>';
        setTimeout(function() {
            btn.innerHTML = originalHtml;
        }, 1500);
    });
}

function updateTimerPreview() {
    var input = document.getElementById('durasiMenitInput');
    if (!input) return;
    var mins = parseInt(input.value) || 0;
    if (mins < 1) mins = 1;

    // Update badge preview
    var badge = document.getElementById('timerBadgePreview');
    if (badge) {
        if (mins < 60) {
            badge.innerText = mins + ' Menit';
        } else if (mins % 1440 === 0) {
            badge.innerText = (mins / 1440) + ' Hari';
        } else if (mins % 60 === 0) {
            badge.innerText = (mins / 60) + ' Jam';
        } else if (mins > 1440) {
            var d = Math.floor(mins / 1440);
            var remH = Math.floor((mins % 1440) / 60);
            var remM = mins % 60;
            var txt = d + ' Hari';
            if (remH > 0) txt += ' ' + remH + ' Jam';
            if (remM > 0) txt += ' ' + remM + ' Menit';
            badge.innerText = txt;
        } else {
            var h = Math.floor(mins / 60);
            var m = mins % 60;
            badge.innerText = h + ' Jam ' + m + ' Menit';
        }
    }

    // Update preset buttons state
    var btns = document.querySelectorAll('.timer-btn');
    btns.forEach(function(b) {
        var bMins = parseInt(b.getAttribute('data-mins') || 0);
        if (bMins === mins) {
            b.classList.remove('btn-outline-secondary');
            b.classList.add('btn-primary', 'active');
        } else {
            b.classList.remove('btn-primary', 'active');
            b.classList.add('btn-outline-secondary');
        }
    });

    // Calculate end date & time
    var now = new Date();
    var endTime = new Date(now.getTime() + (mins * 60000));
    var hh = String(endTime.getHours()).padStart(2, '0');
    var mm = String(endTime.getMinutes()).padStart(2, '0');

    var isSameDay = (endTime.toDateString() === now.toDateString());
    var previewEl = document.getElementById('previewWaktuBerakhir');
    if (previewEl) {
        if (isSameDay) {
            previewEl.innerText = hh + ':' + mm + ' WIB (Hari ini)';
        } else {
            var dayNames = ['Min', 'Sen', 'Sel', 'Rab', 'Kam', 'Jum', 'Sab'];
            var dayName = dayNames[endTime.getDay()];
            var dd = String(endTime.getDate()).padStart(2, '0');
            var mo = String(endTime.getMonth() + 1).padStart(2, '0');
            previewEl.innerText = hh + ':' + mm + ' WIB (' + dayName + ', ' + dd + '/' + mo + ')';
        }
    }
}

function setTimer(minutes, btn) {
    var input = document.getElementById('durasiMenitInput');
    if (input) {
        input.value = minutes;
        updateTimerPreview();
    }
}

document.addEventListener('DOMContentLoaded', function() {
    updateTimerPreview();
    var modalEl = document.getElementById('modalGenerateToken');
    if (modalEl) {
        modalEl.addEventListener('shown.bs.modal', function () {
            updateTimerPreview();
        });
    }
});
</script>

<?php if ($tab === 'tahunan'): ?>
<!-- Chart.js CDN & Inisialisasi Grafik Tahunan -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.2/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    var ctx = document.getElementById('annualFeedbackChart');
    if (!ctx) return;

    var labels = <?= json_encode($thn_chart_labels) ?>;
    var dataScores = <?= json_encode($thn_chart_scores) ?>;
    var bgColors = <?= json_encode($thn_chart_colors) ?>;

    // Plugin Khusus: Angka Nilai Langsung di Ujung Batang Horisontal
    var scoreLabelsPlugin = {
        id: 'scoreLabelsPlugin',
        afterDatasetsDraw: function(chart) {
            if (chart.config.type !== 'bar') return;
            var c = chart.ctx;
            chart.data.datasets.forEach(function(dataset, i) {
                var meta = chart.getDatasetMeta(i);
                meta.data.forEach(function(bar, index) {
                    var rawVal = Number(dataset.data[index]);
                    if (isNaN(rawVal) || rawVal <= 0) return;
                    var valStr = rawVal.toFixed(2) + ' ⭐';
                    c.save();
                    c.fillStyle = '#0F172A';
                    c.font = 'bold 12px "Plus Jakarta Sans", sans-serif';
                    c.textAlign = 'left';
                    c.textBaseline = 'middle';
                    var posX = Math.min(bar.x + 8, chart.chartArea.right - 55);
                    c.fillText(valStr, posX, bar.y);
                    c.restore();
                });
            });
        }
    };

    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: labels,
            datasets: [{
                label: 'Rata-Rata Skor',
                data: dataScores,
                backgroundColor: bgColors,
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
                    var qIds = <?= json_encode(array_values(array_map('intval', array_column($thn_pertanyaan_list, 'id')))) ?>;
                    var targetId = qIds[index];
                    if (targetId) {
                        var modalEl = document.getElementById('modalThnDistribusi' + targetId);
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
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(15, 23, 42, 0.95)',
                    padding: 12,
                    titleFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12, weight: 'bold' },
                    bodyFont: { family: "'Plus Jakarta Sans', sans-serif", size: 12 },
                    callbacks: {
                        label: function(context) {
                            var val = Number(context.raw).toFixed(2);
                            var persen = ((val / 5) * 100).toFixed(1);
                            return ' Skor Rata-Rata: ' + val + ' / 5.00 (' + persen + '% Capaian) - Klik untuk detail bintang';
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
                    grid: {
                        color: '#F1F5F9'
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    ticks: {
                        font: {
                            family: "'Plus Jakarta Sans', sans-serif",
                            size: 11,
                            weight: '600'
                        },
                        color: '#1E293B'
                    }
                }
            }
        },
        plugins: [scoreLabelsPlugin]
    });
});
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
