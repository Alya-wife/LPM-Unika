<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kotak Masuk Permohonan Kunjungan';
$db = getDB();

$tab = $_GET['tab'] ?? 'active';
if (!in_array($tab, ['active', 'archive'])) {
    $tab = 'active';
}

// Handle Reply Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'balas_kunjungan') {
    $id          = (int)($_POST['id'] ?? 0);
    $balasan     = trim($_POST['balasan'] ?? '');
    $open_mailto = (isset($_POST['send_email']) && $_POST['send_email'] === '1');
    $subject     = trim($_POST['subject'] ?? '[LPM SCU] Tanggapan Permohonan Kunjungan Resmi');

    if ($id > 0 && !empty($balasan)) {
        // Handle upload file_balasan (Surat Balasan Resmi LPM)
        $file_balasan_name = null;
        if (isset($_FILES['file_balasan']) && $_FILES['file_balasan']['error'] === UPLOAD_ERR_OK) {
            $allowed_exts = ['pdf'];
            $file_tmp  = $_FILES['file_balasan']['tmp_name'];
            $orig_name = $_FILES['file_balasan']['name'];
            $file_ext  = strtolower(pathinfo($orig_name, PATHINFO_EXTENSION));

            if (in_array($file_ext, $allowed_exts)) {
                $upload_dir = __DIR__ . '/../uploads/kunjungan/';
                if (!is_dir($upload_dir)) {
                    @mkdir($upload_dir, 0777, true);
                }

                // Remove previous file_balasan if exists
                $old = $db->query("SELECT file_balasan FROM permohonan_kunjungan WHERE id = {$id}")->fetch();
                if ($old && !empty($old['file_balasan']) && file_exists($upload_dir . $old['file_balasan'])) {
                    @unlink($upload_dir . $old['file_balasan']);
                }

                $file_balasan_name = 'balasan_lpm_' . $id . '_' . time() . '.pdf';
                move_uploaded_file($file_tmp, $upload_dir . $file_balasan_name);
            } else {
                $_SESSION['flash_error'] = 'Format file surat balasan harus berupa dokumen PDF.';
                redirect(SITE_URL . '/admin/kunjungan-list.php?tab=' . $tab);
            }
        }

        if ($file_balasan_name) {
            $stmt = $db->prepare("UPDATE permohonan_kunjungan SET balasan = ?, file_balasan = ?, tanggal_balas = CURRENT_TIMESTAMP(), status = 'Sudah Dibalas' WHERE id = ?");
            $stmt->execute([$balasan, $file_balasan_name, $id]);
        } else {
            $stmt = $db->prepare("UPDATE permohonan_kunjungan SET balasan = ?, tanggal_balas = CURRENT_TIMESTAMP(), status = 'Sudah Dibalas' WHERE id = ?");
            $stmt->execute([$balasan, $id]);
        }

        $k_stmt = $db->prepare("SELECT * FROM permohonan_kunjungan WHERE id = ?");
        $k_stmt->execute([$id]);
        $k_row = $k_stmt->fetch();

        if ($k_row) {
            $to = $k_row['email'];
            
            // Build email body with official letter link if available
            $email_body = $balasan;
            if (!empty($k_row['file_balasan'])) {
                $download_url = SITE_URL . '/uploads/kunjungan/' . $k_row['file_balasan'];
                $email_body .= "\n\n------------------------------------------------------------\n"
                             . "Surat Resmi Balasan dari LPM dapat diunduh pada tautan berikut:\n"
                             . $download_url . "\n"
                             . "------------------------------------------------------------";
            }

            $headers = "From: LPM SCU <lpm@unika.ac.id>\r\n" .
                       "Reply-To: lpm@unika.ac.id\r\n" .
                       "Content-Type: text/plain; charset=UTF-8\r\n" .
                       "X-Mailer: PHP/" . phpversion();
            @mail($to, $subject, $email_body, $headers);

            if ($open_mailto) {
                $mailto_url = 'mailto:' . rawurlencode($to)
                    . '?subject=' . rawurlencode($subject)
                    . '&body=' . rawurlencode($email_body);
                $_SESSION['redirect_mailto'] = $mailto_url;
            }
        }

        $_SESSION['flash'] = 'Balasan permohonan kunjungan' . ($file_balasan_name ? ' beserta surat resmi LPM' : '') . ' berhasil disimpan dalam sistem' . ($open_mailto ? ' dan aplikasi email dibuka.' : '.');
    } else {
        $_SESSION['flash_error'] = 'Isi balasan tidak boleh kosong.';
    }
    redirect(SITE_URL . '/admin/kunjungan-list.php?tab=' . $tab);
}

// Handle Soft Delete / Archive
if (isset($_GET['action']) && $_GET['action'] === 'archive' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("UPDATE permohonan_kunjungan SET is_archived = 1 WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Permohonan kunjungan telah dipindahkan ke Riwayat & Arsip.';
    redirect(SITE_URL . '/admin/kunjungan-list.php?tab=' . $tab);
}

// Handle Restore from Archive
if (isset($_GET['action']) && $_GET['action'] === 'restore' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("UPDATE permohonan_kunjungan SET is_archived = 0 WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Permohonan kunjungan berhasil dipulihkan ke Kotak Masuk Aktif.';
    redirect(SITE_URL . '/admin/kunjungan-list.php?tab=' . $tab);
}

// Handle Permanent Delete
if (isset($_GET['action']) && $_GET['action'] === 'permanent_delete' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $row = $db->query("SELECT file_surat, file_balasan FROM permohonan_kunjungan WHERE id = {$id}")->fetch();
    if ($row) {
        if ($row['file_surat'] && file_exists(__DIR__ . '/../uploads/kunjungan/' . $row['file_surat'])) {
            @unlink(__DIR__ . '/../uploads/kunjungan/' . $row['file_surat']);
        }
        if (!empty($row['file_balasan']) && file_exists(__DIR__ . '/../uploads/kunjungan/' . $row['file_balasan'])) {
            @unlink(__DIR__ . '/../uploads/kunjungan/' . $row['file_balasan']);
        }
    }
    $db->prepare("DELETE FROM permohonan_kunjungan WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Permohonan kunjungan berhasil dihapus secara permanen.';
    redirect(SITE_URL . '/admin/kunjungan-list.php?tab=' . $tab);
}

// Handle Empty Archive
if (isset($_GET['action']) && $_GET['action'] === 'empty_archive') {
    $archived = $db->query("SELECT file_surat, file_balasan FROM permohonan_kunjungan WHERE is_archived = 1")->fetchAll();
    foreach ($archived as $a) {
        if ($a['file_surat'] && file_exists(__DIR__ . '/../uploads/kunjungan/' . $a['file_surat'])) {
            @unlink(__DIR__ . '/../uploads/kunjungan/' . $a['file_surat']);
        }
        if (!empty($a['file_balasan']) && file_exists(__DIR__ . '/../uploads/kunjungan/' . $a['file_balasan'])) {
            @unlink(__DIR__ . '/../uploads/kunjungan/' . $a['file_balasan']);
        }
    }
    $db->exec("DELETE FROM permohonan_kunjungan WHERE is_archived = 1");
    $_SESSION['flash'] = 'Seluruh riwayat arsip permohonan kunjungan berhasil dikosongkan.';
    redirect(SITE_URL . '/admin/kunjungan-list.php?tab=archive');
}

// Handle Status Change
if (isset($_GET['status']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $st = $_GET['status'] === 'selesai' ? 'Selesai' : 'Belum Dibaca';
    $db->prepare("UPDATE permohonan_kunjungan SET status = ? WHERE id = ?")->execute([$st, $id]);
    redirect(SITE_URL . '/admin/kunjungan-list.php?tab=' . $tab);
}

// Handle Generate Token for Kunjungan
if (isset($_REQUEST['action']) && $_REQUEST['action'] === 'generate_token_for_kunjungan' && isset($_REQUEST['id']) && is_numeric($_REQUEST['id'])) {
    $id = (int)$_REQUEST['id'];
    $k_stmt = $db->prepare("SELECT * FROM permohonan_kunjungan WHERE id = ?");
    $k_stmt->execute([$id]);
    $k_data = $k_stmt->fetch();

    if ($k_data) {
        $token_code   = generateUniqueFeedbackToken();
        $tgl          = $k_data['tanggal_kunjungan'];
        $durasi_menit = max(1, (int)($_REQUEST['durasi_menit'] ?? getPengaturan('feedback_kunjungan_durasi_menit', '60')));
        $now          = time();

        if (!empty($tgl) && $tgl > date('Y-m-d')) {
            $mulai_ts = strtotime($tgl . ' ' . date('H:i:s'));
        } else {
            $mulai_ts = $now;
        }

        $berlaku_mulai  = date('Y-m-d H:i:s', $mulai_ts);
        $berlaku_sampai = date('Y-m-d H:i:s', $mulai_ts + ($durasi_menit * 60));

        $ins = $db->prepare("INSERT INTO kunjungan_feedback_token 
            (token, kunjungan_id, nama_institusi, email, tanggal_kunjungan, perihal, berlaku_mulai, berlaku_sampai, status) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'Aktif')");
        $ins->execute([$token_code, $id, $k_data['nama_institusi'], $k_data['email'], $tgl, $k_data['perihal'], $berlaku_mulai, $berlaku_sampai]);

        if ($durasi_menit % 1440 === 0) {
            $durasi_str = ($durasi_menit / 1440) . ' hari';
        } elseif ($durasi_menit < 60) {
            $durasi_str = $durasi_menit . ' menit';
        } elseif ($durasi_menit % 60 === 0) {
            $durasi_str = ($durasi_menit / 60) . ' jam';
        } else {
            $durasi_str = floor($durasi_menit / 60) . ' jam ' . ($durasi_menit % 60) . ' menit';
        }

        $_SESSION['flash'] = 'Kode token feedback berhasil digenerate: <strong class="badge bg-dark font-monospace" style="font-size:1rem;letter-spacing:1px;">' . $token_code . '</strong> untuk ' . e($k_data['nama_institusi']) . ' (Timer aktif: ' . $durasi_str . ' s/d ' . date('d/m/Y H:i', strtotime($berlaku_sampai)) . ' WIB)';
    }
    redirect(SITE_URL . '/admin/kunjungan-list.php?tab=' . $tab);
}

// Queries
$kunjungan_active   = $db->query("SELECT p.*, f.token, f.status AS token_status FROM permohonan_kunjungan p LEFT JOIN kunjungan_feedback_token f ON f.kunjungan_id = p.id WHERE p.is_archived = 0 ORDER BY p.created_at DESC")->fetchAll();
$kunjungan_archived = $db->query("SELECT p.*, f.token, f.status AS token_status FROM permohonan_kunjungan p LEFT JOIN kunjungan_feedback_token f ON f.kunjungan_id = p.id WHERE p.is_archived = 1 ORDER BY p.created_at DESC")->fetchAll();
$kunjungan_display  = ($tab === 'archive') ? $kunjungan_archived : $kunjungan_active;

$flash       = $_SESSION['flash'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
$redirect_mailto = $_SESSION['redirect_mailto'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_error'], $_SESSION['redirect_mailto']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<style>
.btn-balas-primary {
    background: linear-gradient(135deg, #6A1B9A 0%, #4A148C 100%);
    color: #ffffff !important;
    border: none;
    padding: 0.45rem 0.95rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.8rem;
    box-shadow: 0 3px 8px rgba(106, 27, 154, 0.3);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    cursor: pointer;
    text-decoration: none;
}
.btn-balas-primary:hover {
    background: linear-gradient(135deg, #7B1FA2 0%, #6A1B9A 100%);
    color: #ffffff !important;
    box-shadow: 0 5px 14px rgba(106, 27, 154, 0.45);
    transform: translateY(-1px);
}
.btn-archive-danger {
    background: #FFEBEE;
    color: #C62828 !important;
    border: 1px solid #FFCDD2;
    padding: 0.38rem 0.85rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.78rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    text-decoration: none;
}
.btn-archive-danger:hover {
    background: #FFCDD2;
    color: #B71C1C !important;
}
.btn-selesai-success {
    background: #E8F5E9;
    color: #2E7D32 !important;
    border: 1px solid #C8E6C9;
    padding: 0.38rem 0.85rem;
    border-radius: 20px;
    font-weight: 600;
    font-size: 0.78rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    text-decoration: none;
}
.btn-selesai-success:hover {
    background: #C8E6C9;
    color: #1B5E20 !important;
}
.btn-restore-info {
    background: #E0F2FE;
    color: #0284C7 !important;
    border: 1px solid #BAE6FD;
    padding: 0.4rem 0.9rem;
    border-radius: 20px;
    font-weight: 700;
    font-size: 0.78rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    transition: all 0.2s ease;
    text-decoration: none;
}
.btn-restore-info:hover {
    background: #BAE6FD;
    color: #0369A1 !important;
}
</style>

<?php if ($redirect_mailto): ?>
<script>
    window.addEventListener('DOMContentLoaded', function() {
        window.location.href = <?= json_encode($redirect_mailto) ?>;
    });
</script>
<?php endif; ?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">Kotak Masuk Pelayanan Kunjungan ke LPM (Instansi Luar)</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Kelola permohonan kunjungan resmi dari universitas/lembaga lain ke Lembaga Penjaminan Mutu UNIKA Soegijapranata.</p>
    </div>
    <a href="kunjungan-unit.php" class="btn-outline">
        <i class="bi bi-clock me-1"></i> Pengaturan Jam Operasional Kunjungan
    </a>
</div>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <?= e($flash) ?>
</div>
<?php endif; ?>

<?php if ($flash_error): ?>
<div class="alert-lpm alert-danger mb-4">
    <?= e($flash_error) ?>
</div>
<?php endif; ?>

<!-- TAB NAVIGATION -->
<div class="mb-4">
    <div style="background:#F1F5F9;padding:6px;border-radius:12px;display:inline-flex;gap:6px;box-shadow:inset 0 1px 2px rgba(0,0,0,0.05);">
        <a href="kunjungan-list.php?tab=active" style="padding:0.5rem 1.25rem;border-radius:8px;font-weight:700;font-size:0.85rem;text-decoration:none;transition:all 0.2s ease;<?= $tab === 'active' ? 'background:var(--navy);color:#ffffff;box-shadow:0 2px 6px rgba(10,25,47,0.25);' : 'color:var(--text-muted);' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16" class="me-1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3.75m-6-3.75v3.75m-6-3.75v3.75m12 4.5H5.25a2.25 2.25 0 01-2.25-2.25V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v9a2.25 2.25 0 01-2.25 2.25z" />
            </svg>
            Permohonan Kunjungan Aktif
            <span class="badge rounded-pill ms-1" style="background:<?= $tab === 'active' ? '#FFD54F' : '#CBD5E1' ?>;color:<?= $tab === 'active' ? '#0A192F' : '#475569' ?>;font-size:0.75rem;"><?= count($kunjungan_active) ?></span>
        </a>
        <a href="kunjungan-list.php?tab=archive" style="padding:0.5rem 1.25rem;border-radius:8px;font-weight:700;font-size:0.85rem;text-decoration:none;transition:all 0.2s ease;<?= $tab === 'archive' ? 'background:var(--navy);color:#ffffff;box-shadow:0 2px 6px rgba(10,25,47,0.25);' : 'color:var(--text-muted);' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16" class="me-1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
            Riwayat &amp; Arsip Kunjungan
            <span class="badge rounded-pill ms-1" style="background:<?= $tab === 'archive' ? '#FFD54F' : '#CBD5E1' ?>;color:<?= $tab === 'archive' ? '#0A192F' : '#475569' ?>;font-size:0.75rem;"><?= count($kunjungan_archived) ?></span>
        </a>
    </div>
</div>

<div class="admin-table-wrap">
    <div class="admin-table-topbar d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="admin-table-title">
            <?= $tab === 'archive' ? 'Riwayat Arsip Kunjungan (' . count($kunjungan_display) . ')' : 'Daftar Permohonan Kunjungan Aktif (' . count($kunjungan_display) . ')' ?>
        </div>
        <?php if ($tab === 'archive' && !empty($kunjungan_archived)): ?>
        <a href="kunjungan-list.php?action=empty_archive" class="btn-archive-danger" onclick="return confirm('Kosongkan seluruh riwayat arsip permohonan kunjungan secara permanen?')">
            Kosongkan Seluruh Arsip (Hapus Permanen)
        </a>
        <?php endif; ?>
    </div>

    <?php if (empty($kunjungan_display)): ?>
    <div style="padding:3.5rem;text-align:center;color:var(--text-muted);">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--text-muted)" width="48" height="48" style="opacity:0.3;margin-bottom:1rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3.75m-6-3.75v3.75m-6-3.75v3.75m12 4.5H5.25a2.25 2.25 0 01-2.25-2.25V6.75A2.25 2.25 0 015.25 4.5h13.5A2.25 2.25 0 0121 6.75v9a2.25 2.25 0 01-2.25 2.25z" />
        </svg>
        <p style="font-size:0.9rem;margin:0;">
            <?= $tab === 'archive' ? 'Belum ada permohonan kunjungan yang diarsipkan.' : 'Belum ada permohonan kunjungan resmi yang masuk.' ?>
        </p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="120">Jadwal Kunjungan</th>
                    <th width="220">Institusi &amp; Contact PIC</th>
                    <th width="110">Peserta</th>
                    <th>Perihal &amp; Balasan Admin</th>
                    <th width="100">Surat &amp; Audiensi</th>
                    <th width="100">Status</th>
                    <th width="150">Aksi Admin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($kunjungan_display as $k):
                    $audiensi_list = json_decode($k['data_audiensi'] ?? '[]', true) ?: [];
                ?>
                <tr style="<?= $k['status'] === 'Belum Dibaca' && $tab === 'active' ? 'background:#F8FAFC;' : '' ?>">
                    <td>
                        <div style="font-weight:700;color:var(--navy);font-size:0.88rem;"><?= formatTanggal($k['tanggal_kunjungan']) ?></div>
                        <div style="font-size:0.8rem;color:var(--purple);font-weight:600;margin-top:2px;">
                            ⏰ <?= date('H:i', strtotime($k['waktu_kunjungan'])) ?> WIB
                        </div>
                    </td>
                    <td>
                        <div style="font-weight:800;color:var(--navy);font-size:0.95rem;"><?= e($k['nama_institusi']) ?></div>
                        <div style="font-size:0.78rem;color:var(--text-muted);">
                            <a href="mailto:<?= e($k['email']) ?>" style="color:var(--navy);text-decoration:underline;"><?= e($k['email']) ?></a>
                        </div>
                        <div style="font-size:0.75rem;color:var(--text-main);margin-top:3px;">
                            <strong>PIC:</strong> <?= e($k['nama_pic']) ?> (<?= e($k['telepon_pic']) ?>)
                        </div>
                    </td>
                    <td>
                        <span class="badge bg-light text-dark border font-weight-normal" style="font-size:0.8rem;"><?= (int)$k['jumlah_peserta'] ?> Orang</span>
                    </td>
                    <td>
                        <div style="font-size:0.85rem;color:var(--text-main);line-height:1.55;white-space:pre-line;">
                            <?= e($k['perihal']) ?>
                        </div>

                        <!-- Admin Reply log -->
                        <?php if (!empty($k['balasan'])): ?>
                        <div style="margin-top:8px;padding:8px 12px;background:#F1F5F9;border-left:4px solid var(--purple);border-radius:6px;font-size:0.82rem;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span style="font-weight:700;color:var(--purple);">Balasan Resmi Admin LPM:</span>
                                <?php if ($k['tanggal_balas']): ?>
                                <span style="font-size:0.7rem;color:var(--text-muted);"><?= formatTanggal($k['tanggal_balas']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="color:var(--navy);white-space:pre-line;line-height:1.45;"><?= e($k['balasan']) ?></div>

                            <?php if (!empty($k['file_balasan']) && file_exists(__DIR__ . '/../uploads/kunjungan/' . $k['file_balasan'])): ?>
                            <div class="mt-2 pt-2" style="border-top:1px dashed #CBD5E1;">
                                <a href="<?= SITE_URL ?>/uploads/kunjungan/<?= e($k['file_balasan']) ?>" target="_blank" class="btn btn-sm btn-outline-success" style="font-size:0.75rem;padding:0.25rem 0.65rem;border-radius:20px;">
                                    <i class="bi bi-file-earmark-pdf me-1"></i> Buka Surat Balasan Resmi LPM (PDF)
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="display:flex;flex-direction:column;gap:0.3rem;">
                            <?php if ($k['file_surat'] && file_exists(__DIR__ . '/../uploads/kunjungan/' . $k['file_surat'])): ?>
                            <a href="<?= SITE_URL ?>/uploads/kunjungan/<?= e($k['file_surat']) ?>" target="_blank" class="btn btn-sm btn-outline-danger" style="font-size:0.72rem;padding:0.2rem 0.5rem;border-radius:12px;" title="Surat Masuk dari Tamu">
                                <i class="bi bi-file-earmark-arrow-down me-1"></i> Surat Masuk
                            </a>
                            <?php else: ?>
                            <span style="font-size:0.7rem;color:var(--text-light);">Tanpa File</span>
                            <?php endif; ?>

                            <?php if (!empty($k['file_balasan']) && file_exists(__DIR__ . '/../uploads/kunjungan/' . $k['file_balasan'])): ?>
                            <a href="<?= SITE_URL ?>/uploads/kunjungan/<?= e($k['file_balasan']) ?>" target="_blank" class="btn btn-sm btn-outline-success" style="font-size:0.72rem;padding:0.2rem 0.5rem;border-radius:12px;" title="Surat Resmi Balasan dari LPM">
                                <i class="bi bi-file-earmark-arrow-up me-1"></i> Surat Balasan
                            </a>
                            <?php endif; ?>

                            <button type="button" class="btn btn-sm btn-outline-primary" style="font-size:0.72rem;padding:0.2rem 0.5rem;border-radius:12px;" data-bs-toggle="modal" data-bs-target="#modalDetail<?= $k['id'] ?>">
                                <i class="bi bi-people me-1"></i> <?= (int)$k['jumlah_peserta'] ?> Audiensi
                            </button>
                        </div>
                    </td>
                    <td>
                        <?php if ($tab === 'archive'): ?>
                            <span style="font-size:0.72rem;font-weight:600;color:#616161;background:#EEEEEE;padding:0.25rem 0.65rem;border-radius:50px;">Arsip</span>
                        <?php elseif ($k['status'] === 'Sudah Dibalas'): ?>
                            <span style="font-size:0.72rem;font-weight:600;color:#1565C0;background:#E3F2FD;padding:0.25rem 0.65rem;border-radius:50px;">Dibalas</span>
                        <?php elseif ($k['status'] === 'Selesai'): ?>
                            <span style="font-size:0.72rem;font-weight:600;color:#2E7D32;background:#E8F5E9;padding:0.25rem 0.65rem;border-radius:50px;">Selesai</span>
                        <?php else: ?>
                            <span style="font-size:0.72rem;font-weight:600;color:#E65100;background:#FFF3E0;padding:0.25rem 0.65rem;border-radius:50px;">Baru</span>
                        <?php endif; ?>

                        <!-- Feedback Token Status -->
                        <div class="mt-2">
                            <?php if (!empty($k['token'])): ?>
                                <span class="badge" style="background:#E0F2FE;color:#0284C7;border:1px solid #BAE6FD;font-size:0.7rem;padding:0.25rem 0.5rem;" title="Token Feedback Dibuat: <?= e($k['token_status'] ?? 'Aktif') ?>">
                                    ⭐ <code><?= e($k['token']) ?></code>
                                </span>
                            <?php elseif ($tab === 'active'): ?>
                                <button type="button" class="btn btn-sm btn-outline-info" style="font-size:0.68rem;padding:0.2rem 0.45rem;border-radius:12px;" data-bs-toggle="modal" data-bs-target="#modalTokenKunjungan<?= $k['id'] ?>" title="Setel Timer & Buat Token Feedback Tamu">
                                    ⏱️ + Token Feedback
                                </button>
                            <?php endif; ?>
                        </div>
                    </td>
                    <td>
                        <?php if ($tab === 'active'): ?>
                            <div style="display:flex;flex-direction:column;gap:0.4rem;">
                                <button type="button" class="btn-balas-primary" data-bs-toggle="modal" data-bs-target="#modalBalasKunjungan<?= $k['id'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13" class="me-1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                    </svg>
                                    Balas Pesan
                                </button>
                                <div style="display:flex;gap:0.3rem;">
                                    <?php if ($k['status'] !== 'Selesai'): ?>
                                    <a href="kunjungan-list.php?status=selesai&id=<?= $k['id'] ?>&tab=active" class="btn-selesai-success" style="flex-grow:1;" title="Tandai Selesai">
                                        Selesai
                                    </a>
                                    <?php endif; ?>
                                    <a href="kunjungan-list.php?action=archive&id=<?= $k['id'] ?>" class="btn-archive-danger" style="flex-grow:1;" onclick="return confirm('Pindahkan permohonan kunjungan ini ke Riwayat & Arsip?')">
                                        Arsip
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <div style="display:flex;flex-direction:column;gap:0.4rem;">
                                <a href="kunjungan-list.php?action=restore&id=<?= $k['id'] ?>" class="btn-restore-info" onclick="return confirm('Pulihkan permohonan ini ke Kotak Masuk Aktif?')">
                                    Pulihkan
                                </a>
                                <a href="kunjungan-list.php?action=permanent_delete&id=<?= $k['id'] ?>" class="btn-archive-danger" onclick="return confirm('Hapus permanen permohonan kunjungan ini dari sistem?')">
                                    Hapus Permanen
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- MODAL DETAIL AUDIENSI -->
                        <div class="modal fade" id="modalDetail<?= $k['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;">
                                    <div class="modal-header" style="background:var(--navy);color:#fff;padding:1.25rem 1.5rem;">
                                        <h5 class="modal-title fw-bold" style="font-size:1.1rem;">
                                            Detail Permohonan Kunjungan: <?= e($k['nama_institusi']) ?>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body p-4 text-start">
                                        <div class="row g-3 mb-4" style="font-size:0.88rem;">
                                            <div class="col-sm-6">
                                                <strong style="color:var(--navy);">Nama Institusi:</strong> <?= e($k['nama_institusi']) ?><br>
                                                <strong style="color:var(--navy);">Email Official:</strong> <?= e($k['email']) ?><br>
                                                <strong style="color:var(--navy);">Tujuan Kunjungan:</strong> <?= e($k['tujuan_unit']) ?>
                                            </div>
                                            <div class="col-sm-6">
                                                <strong style="color:var(--navy);">Tanggal &amp; Jam:</strong> <?= formatTanggal($k['tanggal_kunjungan']) ?> Jam <?= date('H:i', strtotime($k['waktu_kunjungan'])) ?> WIB<br>
                                                <strong style="color:var(--navy);">PIC Kunjungan:</strong> <?= e($k['nama_pic']) ?> (<?= e($k['telepon_pic']) ?>)<br>
                                                <strong style="color:var(--navy);">Jumlah Audiensi:</strong> <?= (int)$k['jumlah_peserta'] ?> Orang
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <h6 style="font-weight:700;color:var(--navy);font-size:0.95rem;">Perihal &amp; Tujuan Kunjungan:</h6>
                                            <div style="font-size:0.85rem;background:#F8FAFC;padding:12px;border-radius:8px;border:1px solid #E2E8F0;white-space:pre-line;">
                                                <?= e($k['perihal']) ?>
                                            </div>
                                        </div>

                                        <div class="mb-3">
                                            <h6 style="font-weight:700;color:var(--navy);font-size:0.95rem;">Daftar Pesan / Audiensi (<?= count($audiensi_list) ?> Peserta):</h6>
                                            <?php if (empty($audiensi_list)): ?>
                                                <p class="text-muted small">Tidak ada daftar nama rincian audiensi.</p>
                                            <?php else: ?>
                                                <table class="table table-sm table-bordered" style="font-size:0.83rem;">
                                                    <thead class="bg-light">
                                                        <tr>
                                                            <th width="40">#</th>
                                                            <th>Nama Lengkap Audiensi</th>
                                                            <th>Jabatan / Posisi</th>
                                                        </tr>
                                                    </thead>
                                                    <tbody>
                                                        <?php foreach ($audiensi_list as $idx => $aud): ?>
                                                        <tr>
                                                            <td><?= $idx + 1 ?></td>
                                                            <td style="font-weight:600;color:var(--navy);"><?= e($aud['nama'] ?? '-') ?></td>
                                                            <td><?= e($aud['jabatan'] ?? '-') ?></td>
                                                        </tr>
                                                        <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    <div class="modal-footer bg-light px-4 py-3">
                                        <?php if ($k['file_surat'] && file_exists(__DIR__ . '/../uploads/kunjungan/' . $k['file_surat'])): ?>
                                        <a href="<?= SITE_URL ?>/uploads/kunjungan/<?= e($k['file_surat']) ?>" target="_blank" class="btn btn-danger btn-sm rounded-pill px-3">
                                            <i class="bi bi-file-earmark-arrow-down me-1"></i> Surat Masuk Tamu (PDF)
                                        </a>
                                        <?php endif; ?>

                                        <?php if (!empty($k['file_balasan']) && file_exists(__DIR__ . '/../uploads/kunjungan/' . $k['file_balasan'])): ?>
                                        <a href="<?= SITE_URL ?>/uploads/kunjungan/<?= e($k['file_balasan']) ?>" target="_blank" class="btn btn-success btn-sm rounded-pill px-3">
                                            <i class="bi bi-file-earmark-arrow-up me-1"></i> Surat Balasan Resmi LPM (PDF)
                                        </a>
                                        <?php endif; ?>

                                        <button type="button" class="btn btn-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Tutup</button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- MODAL BALAS KUNJUNGAN -->
                        <div class="modal fade" id="modalBalasKunjungan<?= $k['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;">
                                    <div class="modal-header" style="background:linear-gradient(135deg, var(--navy), #132D54);color:#fff;padding:1.25rem 1.5rem;">
                                        <h5 class="modal-title fw-bold" style="font-size:1.1rem;">
                                            Balas Permohonan Kunjungan: <?= e($k['nama_institusi']) ?>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="post" action="kunjungan-list.php?tab=<?= $tab ?>" enctype="multipart/form-data">
                                        <input type="hidden" name="action" value="balas_kunjungan">
                                        <input type="hidden" name="id" value="<?= $k['id'] ?>">

                                        <div class="modal-body p-4 text-start">
                                            <div class="p-3 mb-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;font-size:0.83rem;">
                                                <strong>Instansi Pengirim:</strong> <?= e($k['nama_institusi']) ?> (<?= e($k['email']) ?>)<br>
                                                <strong>Tanggal &amp; Jam Disetujui:</strong> <?= formatTanggal($k['tanggal_kunjungan']) ?> Jam <?= date('H:i', strtotime($k['waktu_kunjungan'])) ?> WIB ke <?= e($k['tujuan_unit']) ?>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold" style="color:var(--navy);">Subject Email</label>
                                                <input type="text" name="subject" class="form-control" value="[LPM SCU] Konfirmasi Permohonan Kunjungan: <?= e($k['nama_institusi']) ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold" style="color:var(--navy);">Teks Balasan Resmi LPM SCU</label>
                                                <?php
                                                $default_reply = $k['balasan'] ?: "Yth. Pimpinan / Tim " . $k['nama_institusi'] . ",\n\nSalam hangat dari Universitas Katolik Soegijapranata (SCU).\n\nMenindaklanjuti Surat Permohonan Kunjungan Resmi dari " . $k['nama_institusi'] . " yang diajukan untuk tanggal " . formatTanggal($k['tanggal_kunjungan']) . " Pukul " . date('H:i', strtotime($k['waktu_kunjungan'])) . " WIB dengan tujuan " . $k['tujuan_unit'] . ",\n\nDengan ini kami sampaikan bahwa Lembaga Penjaminan Mutu (LPM) bersama unit terkait menyambut baik permohonan kunjungan tersebut.\n\n[Tuliskan catatan teknis/ruangan penerimaan/konfirmasi jadwal di sini]\n\nMohon konfirmasi kembali kepada PIC kami jika terdapat hal teknis yang perlu disesuaikan.\n\nSalam hangat,\nLembaga Penjaminan Mutu (LPM)\nUniversitas Katolik Soegijapranata";
                                                ?>
                                                <textarea name="balasan" class="form-control" rows="7" required style="font-size:0.88rem;line-height:1.6;"><?= e($default_reply) ?></textarea>
                                            </div>

                                            <!-- Upload Surat Balasan Resmi LPM -->
                                            <div class="mb-3 p-3 rounded-3" style="background:#F0FDF4;border:1.5px solid #BBF7D0;">
                                                <label class="form-label fw-bold d-flex justify-content-between align-items-center mb-1" style="color:#166534;font-size:0.88rem;">
                                                    <span><i class="bi bi-file-earmark-pdf text-danger me-1"></i> Upload Surat Resmi Balasan dari LPM (Opsional)</span>
                                                    <small class="text-muted fw-normal" style="font-size:0.75rem;">Format: Dokumen PDF (Maks. 5 MB)</small>
                                                </label>

                                                <?php if (!empty($k['file_balasan']) && file_exists(__DIR__ . '/../uploads/kunjungan/' . $k['file_balasan'])): ?>
                                                <div class="p-2.5 mb-2 rounded-2 d-flex align-items-center justify-content-between" style="background:#fff;border:1px solid #86EFAC;font-size:0.82rem;">
                                                    <div class="d-flex align-items-center gap-2">
                                                        <i class="bi bi-file-earmark-check-fill text-success fs-5"></i>
                                                        <div>
                                                            <div class="fw-bold text-dark"><?= e($k['file_balasan']) ?></div>
                                                            <small class="text-muted">Surat balasan resmi LPM saat ini tersimpan di sistem.</small>
                                                        </div>
                                                    </div>
                                                    <a href="<?= SITE_URL ?>/uploads/kunjungan/<?= e($k['file_balasan']) ?>" target="_blank" class="btn btn-sm btn-outline-success py-1 px-2.5" style="font-size:0.75rem;border-radius:20px;">
                                                        <i class="bi bi-eye me-1"></i> Lihat Surat
                                                    </a>
                                                </div>
                                                <?php endif; ?>

                                                <input type="file" name="file_balasan" class="form-control" accept=".pdf,application/pdf" style="font-size:0.85rem;border:1.5px solid #86EFAC;background:#fff;">
                                                <div class="form-text" style="font-size:0.75rem;color:#15803D;">
                                                    <i class="bi bi-info-circle me-1"></i> Unggah surat resmi persetujuan/jawaban kunjungan berkop LPM (PDF). Tautan unduh surat resmi ini akan otomatis disertakan dalam email konfirmasi ke instansi tamu.
                                                </div>
                                            </div>

                                            <div class="form-check mb-3 p-3 rounded-3" style="background:#FFF8E1;border:1px solid #FFE082;">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" name="send_email" value="1" id="sendEmailCheckKunjungan<?= $k['id'] ?>" checked>
                                                <label class="form-check-label small fw-semibold" for="sendEmailCheckKunjungan<?= $k['id'] ?>" style="color:#E65100;">
                                                    Buka otomatis di Aplikasi Email / Gmail / Outlook (ter-direct mailto:) setelah disimpan
                                                </label>
                                            </div>
                                        </div>

                                        <div class="modal-footer bg-light px-4 py-3">
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn-balas-primary" style="padding:0.5rem 1.5rem;font-size:0.88rem;">
                                                Simpan &amp; Kirim Balasan Kunjungan
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                        <!-- MODAL SETEL TIMER TOKEN FEEDBACK -->
                        <div class="modal fade" id="modalTokenKunjungan<?= $k['id'] ?>" tabindex="-1" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered">
                                <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;box-shadow:0 20px 40px rgba(0,0,0,0.15);">
                                    <div class="modal-header" style="background:var(--navy);color:#fff;padding:1.25rem 1.5rem;">
                                        <h5 class="modal-title fw-bold d-flex align-items-center gap-2" style="font-size:1.05rem;">
                                            <i class="bi bi-stopwatch text-warning"></i> Setel Timer Token Feedback
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                                    </div>
                                    <form method="post" action="kunjungan-list.php?tab=<?= $tab ?>">
                                        <input type="hidden" name="action" value="generate_token_for_kunjungan">
                                        <input type="hidden" name="id" value="<?= $k['id'] ?>">
                                        <div class="modal-body p-4 text-start">
                                            <div class="p-3 mb-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;font-size:0.83rem;">
                                                <strong>Instansi Tamu:</strong> <?= e($k['nama_institusi']) ?><br>
                                                <strong>Tanggal Kunjungan:</strong> <?= formatTanggal($k['tanggal_kunjungan']) ?>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold mb-1" style="font-size:0.85rem;color:var(--navy);">
                                                    Masa Berlaku Token (Timer Menit) <span class="text-danger">*</span>
                                                </label>
                                                <div class="input-group mb-2">
                                                    <span class="input-group-text bg-white" style="border:1.5px solid var(--border);border-right:none;"><i class="bi bi-hourglass-split"></i></span>
                                                    <input type="number" name="durasi_menit" id="durasiMenitKunjungan<?= $k['id'] ?>" class="form-control" value="60" min="1" max="10080" required style="border:1.5px solid var(--border);font-weight:700;font-size:1rem;color:var(--navy);">
                                                    <span class="input-group-text bg-light fw-bold" style="border:1.5px solid var(--border);border-left:none;">Menit</span>
                                                </div>
                                                <div class="d-flex flex-wrap gap-1 mb-2">
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitKunjungan<?= $k['id'] ?>').value = 15;">15 Menit</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitKunjungan<?= $k['id'] ?>').value = 30;">30 Menit</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitKunjungan<?= $k['id'] ?>').value = 45;">45 Menit</button>
                                                    <button type="button" class="btn btn-sm btn-primary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitKunjungan<?= $k['id'] ?>').value = 60;">1 Jam</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitKunjungan<?= $k['id'] ?>').value = 120;">2 Jam</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitKunjungan<?= $k['id'] ?>').value = 240;">4 Jam</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitKunjungan<?= $k['id'] ?>').value = 1440;">1 Hari</button>
                                                    <button type="button" class="btn btn-sm btn-outline-secondary" style="border-radius:20px;font-size:0.75rem;padding:0.2rem 0.55rem;" onclick="document.getElementById('durasiMenitKunjungan<?= $k['id'] ?>').value = 2880;">2 Hari</button>
                                                </div>
                                                <div class="form-text" style="font-size:0.75rem;color:var(--text-muted);">
                                                    <i class="bi bi-info-circle me-1"></i> Fleksibel! Masukkan berapa menit token ini berlaku sebelum kadaluarsa.
                                                </div>
                                            </div>
                                        </div>
                                        <div class="modal-footer bg-light px-4 py-3">
                                            <button type="button" class="btn-cancel" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn-save">
                                                <i class="bi bi-magic me-1"></i> Buat Token Sekarang
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>

                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
