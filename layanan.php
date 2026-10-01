<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Kritik Saran & Permohonan Layanan';
$meta_desc  = 'Formulir Kritik Saran & Feedback Mutu serta Pengajuan Permohonan Kunjungan Resmi ke Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata.';

$db = getDB();
$success_feedback = '';
$error_feedback = '';
$success_kunjungan = '';
$error_kunjungan = '';

$active_tab = 'feedback'; // Default tab: Kritik Saran & Feedback Mutu
$req_tab = trim($_GET['tab'] ?? '');

// Redirect to dedicated sub-menu pages if requested
if (in_array($req_tab, ['pelatihan', 'biaya', 'jadwal', 'brosur'])) {
    redirect(SITE_URL . '/pelatihan-eksternal.php' . ($req_tab !== 'pelatihan' ? '#' . $req_tab : ''));
} elseif (in_array($req_tab, ['token', 'feedback-kunjungan'])) {
    redirect(SITE_URL . '/feedback-kunjungan.php');
} elseif ($req_tab === 'kunjungan') {
    $active_tab = 'kunjungan';
    $page_title = 'Pelayanan Permohonan Kunjungan & Studi Banding';
    $meta_desc  = 'Formulir Pengajuan Permohonan Kunjungan Resmi dan Studi Banding ke Lembaga Penjaminan Mutu (LPM) Universitas Katolik Soegijapranata.';
} else {
    $active_tab = 'feedback';
}

// Load operating hours & active visit units
$jam_mulai = getPengaturan('kunjungan_jam_mulai', '08:00');
$jam_selesai = getPengaturan('kunjungan_jam_selesai', '15:00');

$tujuan_units = $db->query("SELECT * FROM kunjungan_tujuan_unit WHERE is_active = 1 ORDER BY urutan ASC, nama_unit ASC")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action_form = $_POST['action_form'] ?? 'feedback';

    if ($action_form === 'feedback') {
        $active_tab = 'feedback';
        $instansi      = trim($_POST['instansi'] ?? ($_POST['nama'] ?? ''));
        $nama          = $instansi; // Disimpan sebagai nama instansi
        $email         = trim($_POST['email'] ?? '');
        $jenis_layanan = trim($_POST['jenis_layanan'] ?? '');
        $pesan         = trim($_POST['pesan'] ?? '');

        if (!$nama || !$email || !$pesan) {
            $error_feedback = 'Nama instansi / institusi, alamat email, dan isi masukan wajib diisi.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_feedback = 'Format alamat email tidak valid.';
        } else {
            $stmt = $db->prepare("INSERT INTO feedback (nama, email, jenis_layanan, instansi, pesan) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$nama, $email, $jenis_layanan, $instansi, $pesan]);
            kirimNotifikasiEmailAspirasi($nama, $email, $jenis_layanan, $instansi, $pesan);
            $success_feedback = getPengaturan('form_feedback_success_msg', 'Terima kasih! Kritik, saran, atau masukan dari instansi Anda telah berhasil dikirim ke tim LPM UNIKA.');
        }
    } elseif ($action_form === 'kunjungan') {
        $active_tab = 'kunjungan';
        $nama_institusi    = trim($_POST['nama_institusi'] ?? '');
        $email             = trim($_POST['email'] ?? '');
        $tanggal_kunjungan = trim($_POST['tanggal_kunjungan'] ?? '');
        $waktu_kunjungan   = trim($_POST['waktu_kunjungan'] ?? '');
        $perihal           = trim($_POST['perihal'] ?? '');
        $jumlah_peserta    = min(20, max(1, (int)($_POST['jumlah_peserta'] ?? 1)));
        $nama_pic          = trim($_POST['nama_pic'] ?? '');
        $telepon_pic       = trim($_POST['telepon_pic'] ?? '');

        $nama_audiensi_arr  = $_POST['nama_audiensi'] ?? [];
        $jabatan_audiensi_arr = $_POST['jabatan_audiensi'] ?? [];

        // Build detail audiensi array (maksimal 20 orang)
        $detail_audiensi_list = [];
        for ($i = 0; $i < $jumlah_peserta; $i++) {
            $n = trim($nama_audiensi_arr[$i] ?? '');
            $j = trim($jabatan_audiensi_arr[$i] ?? '');
            if ($n !== '' || $j !== '') {
                $detail_audiensi_list[] = [
                    'nama' => $n,
                    'jabatan' => $j
                ];
            }
        }

        // Tujuan kunjungan khusus ke Lembaga Penjaminan Mutu
        $tujuan_unit_nama = 'Lembaga Penjaminan Mutu (LPM)';

        $jadwal_mode   = getPengaturan('kunjungan_jadwal_mode', 'jumat_minggu_4_flexible');
        $min_lead_days = (int)getPengaturan('kunjungan_min_lead_days', '14');
        $min_date      = date('Y-m-d', strtotime("+{$min_lead_days} days"));

        // Validasi input
        if (!$nama_institusi || !$email || !$tanggal_kunjungan || !$waktu_kunjungan || !$perihal || !$nama_pic || !$telepon_pic) {
            $error_kunjungan = 'Semua field wajib diisi lengkap.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error_kunjungan = 'Format email institusi tidak valid.';
        } elseif ($tanggal_kunjungan < date('Y-m-d')) {
            $error_kunjungan = 'Tanggal kunjungan tidak boleh di masa lalu.';
        } elseif ($tanggal_kunjungan < $min_date) {
            $error_kunjungan = "Penyampaian permohonan kunjungan studi banding paling lambat {$min_lead_days} hari sebelum kegiatan berlangsung.";
        } elseif ($jadwal_mode === 'jumat_minggu_4_strict' && ((int)date('N', strtotime($tanggal_kunjungan)) !== 5 || (int)date('j', strtotime($tanggal_kunjungan)) < 22 || (int)date('j', strtotime($tanggal_kunjungan)) > 28)) {
            $error_kunjungan = 'Sesuai kebijakan resmi LPM, Studi Banding dijadwalkan khusus pada setiap Jumat Minggu ke-IV.';
        } elseif (empty($_FILES['surat_permohonan']['name']) || $_FILES['surat_permohonan']['error'] !== UPLOAD_ERR_OK) {
            $error_kunjungan = 'Surat permohonan kunjungan resmi wajib diupload dalam format PDF.';
        } else {
            $file_info = $_FILES['surat_permohonan'];
            $ext = strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION));

            if ($ext !== 'pdf') {
                $error_kunjungan = 'Format berkas surat permohonan harus berformat PDF.';
            } elseif ($file_info['size'] > 10 * 1024 * 1024) { // 10MB
                $error_kunjungan = 'Ukuran berkas surat permohonan tidak boleh lebih dari 10 MB.';
            } else {
                $upload_dir = __DIR__ . '/uploads/kunjungan/';
                if (!is_dir($upload_dir)) {
                    mkdir($upload_dir, 0755, true);
                }

                $filename = 'surat_kunjungan_' . time() . '_' . rand(1000, 9999) . '.pdf';
                $target_path = $upload_dir . $filename;

                if (move_uploaded_file($file_info['tmp_name'], $target_path)) {
                    $json_audiensi = json_encode($detail_audiensi_list, JSON_UNESCAPED_UNICODE);

                    $stmt = $db->prepare("INSERT INTO permohonan_kunjungan 
                        (nama_institusi, email, tanggal_kunjungan, waktu_kunjungan, perihal, tujuan_unit, jumlah_peserta, data_audiensi, nama_pic, telepon_pic, file_surat, status, is_archived, created_at)
                        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'Belum Dibaca', 0, NOW())");
                    
                    $stmt->execute([
                        $nama_institusi,
                        $email,
                        $tanggal_kunjungan,
                        $waktu_kunjungan,
                        $perihal,
                        $tujuan_unit_nama,
                        $jumlah_peserta,
                        $json_audiensi,
                        $nama_pic,
                        $telepon_pic,
                        $filename
                    ]);

                    $success_kunjungan = getPengaturan('form_kunjungan_success_msg', 'Permohonan kunjungan resmi dari institusi Anda berhasil dikirim ke LPM UNIKA! Tim kami akan melakukan verifikasi dan mengonfirmasi via email/WhatsApp PIC.');
                } else {
                    $error_kunjungan = 'Gagal mengunggah berkas surat permohonan. Silakan coba lagi.';
                }
            }
        }
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';

$layanan_banner_sub = getPengaturan('layanan_banner_sub', '');
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <h1 class="page-banner-title" id="page-banner-title"><?= $active_tab === 'kunjungan' ? 'Permohonan Kunjungan Resmi' : 'Kritik &amp; Saran' ?></h1>
        <?php if ($layanan_banner_sub): ?>
        <p class="text-white-50 mt-2 mb-0" id="page-banner-desc" style="max-width:700px;font-size:0.95rem;line-height:1.6;"><?= e($layanan_banner_sub) ?></p>
        <?php else: ?>
        <p class="text-white-50 mt-2 mb-0" id="page-banner-desc" style="max-width:700px;font-size:0.95rem;line-height:1.6;">
            <?= $active_tab === 'kunjungan' ? 'Formulir pengajuan kunjungan studi banding, benchmarking pengelolaan mutu, SPMI, AMI, dan akreditasi ke LPM UNIKA.' : 'Formulir penyampaian kritik konstruktif, saran perbaikan mutu kelembagaan, dan pengajuan surat permohonan kunjungan resmi ke LPM UNIKA.' ?>
        </p>
        <?php endif; ?>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/layanan.php">Layanan</a>
            <span>/</span>
            <span class="current" id="page-breadcrumb-current"><?= $active_tab === 'kunjungan' ? 'Permohonan Kunjungan' : 'Kritik &amp; Saran' ?></span>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/layanan-sections.php';

$params = [
    'active_tab'        => $active_tab,
    'success_feedback'  => $success_feedback,
    'error_feedback'    => $error_feedback,
    'success_kunjungan' => $success_kunjungan,
    'error_kunjungan'   => $error_kunjungan,
    'tujuan_units'      => $tujuan_units,
    'jam_mulai'         => $jam_mulai,
    'jam_selesai'       => $jam_selesai
];

// Ambil susunan seksi dari Visual Page Builder
$layanan_blocks = null;
try {
    $stmt_l = getDB()->query("SELECT blocks_json FROM pages WHERE slug = 'layanan'");
    $row_l = $stmt_l->fetch();
    if (!empty($row_l['blocks_json'])) {
        $layanan_blocks = json_decode($row_l['blocks_json'], true);
    }
} catch (Exception $e) {}

if (!empty($layanan_blocks) && is_array($layanan_blocks)) {
    foreach ($layanan_blocks as $block) {
        if (isset($block['is_visible']) && !$block['is_visible']) continue;
        if (($block['type'] ?? '') === 'layanan_cards') continue; // Hilangkan kotak atas sesuai permintaan
        renderLayananSection($block['type'], $block, false, $params);
    }
} else {
    // Alur Langsung Formulir Kritik & Saran (tanpa kotak atas)
    renderLayananSection('layanan_form', [], false, $params);
}
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
