<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kotak Masuk Layanan & Feedback';
$db = getDB();

$tab = $_GET['tab'] ?? 'active';
if (!in_array($tab, ['active', 'archive'])) {
    $tab = 'active';
}

// Handle Reply Form Submit
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'balas_pesan') {
    $id          = (int)($_POST['id'] ?? 0);
    $balasan     = trim($_POST['balasan'] ?? '');
    $open_mailto = (isset($_POST['send_email']) && $_POST['send_email'] === '1');
    $subject     = trim($_POST['subject'] ?? '[LPM SCU] Tanggapan Pengajuan Layanan');

    if ($id > 0 && !empty($balasan)) {
        // Save reply into database
        $stmt = $db->prepare("UPDATE feedback SET balasan = ?, tanggal_balas = CURRENT_TIMESTAMP(), status = 'Sudah Dibalas' WHERE id = ?");
        $stmt->execute([$balasan, $id]);

        // Attempt PHP mail() if supported by host
        $fb_stmt = $db->prepare("SELECT * FROM feedback WHERE id = ?");
        $fb_stmt->execute([$id]);
        $fb_row = $fb_stmt->fetch();

        if ($fb_row) {
            $to = $fb_row['email'];
            $headers = "From: LPM SCU <lpm@unika.ac.id>\r\n" .
                       "Reply-To: lpm@unika.ac.id\r\n" .
                       "Content-Type: text/plain; charset=UTF-8\r\n" .
                       "X-Mailer: PHP/" . phpversion();
            @mail($to, $subject, $balasan, $headers);

            if ($open_mailto) {
                $mailto_url = 'mailto:' . rawurlencode($to)
                    . '?subject=' . rawurlencode($subject)
                    . '&body=' . rawurlencode($balasan);
                $_SESSION['redirect_mailto'] = $mailto_url;
            }
        }

        $_SESSION['flash'] = 'Balasan berhasil disimpan dalam sistem' . ($open_mailto ? ' dan aplikasi email dibuka otomatis.' : '.');
    } else {
        $_SESSION['flash_error'] = 'Isi balasan tidak boleh kosong.';
    }
    redirect(SITE_URL . '/admin/feedback-list.php?tab=' . $tab);
}

// Handle Soft Delete / Archive (Pindahkan ke Riwayat & Arsip)
if (isset($_GET['action']) && $_GET['action'] === 'archive' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("UPDATE feedback SET is_archived = 1 WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Pesan telah dipindahkan ke Riwayat & Arsip Pesan.';
    redirect(SITE_URL . '/admin/feedback-list.php?tab=active');
}

// Handle Restore from Archive (Pulihkan Kembali ke Pesan Aktif)
if (isset($_GET['action']) && $_GET['action'] === 'restore' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("UPDATE feedback SET is_archived = 0 WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Pesan berhasil dipulihkan kembali ke Kotak Masuk Pesan Aktif.';
    redirect(SITE_URL . '/admin/feedback-list.php?tab=archive');
}

// Handle Permanent Delete (Hapus Permanen Langsung dari Web Admin)
if (isset($_GET['action']) && $_GET['action'] === 'permanent_delete' && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $db->prepare("DELETE FROM feedback WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Pesan berhasil dihapus secara permanen dari sistem.';
    redirect(SITE_URL . '/admin/feedback-list.php?tab=archive');
}

// Handle Empty All Archive (Kosongkan Seluruh Arsip Langsung dari Web)
if (isset($_GET['action']) && $_GET['action'] === 'empty_archive') {
    $db->exec("DELETE FROM feedback WHERE is_archived = 1");
    $_SESSION['flash'] = 'Seluruh riwayat arsip pesan berhasil dikosongkan secara permanen.';
    redirect(SITE_URL . '/admin/feedback-list.php?tab=archive');
}

// Handle Mark as Selesai / Belum Dibaca
if (isset($_GET['status']) && isset($_GET['id']) && is_numeric($_GET['id'])) {
    $id = (int)$_GET['id'];
    $st = $_GET['status'] === 'selesai' ? 'Selesai' : 'Belum Dibaca';
    $db->prepare("UPDATE feedback SET status = ? WHERE id = ?")->execute([$st, $id]);
    redirect(SITE_URL . '/admin/feedback-list.php?tab=' . $tab);
}

// Queries
$feedbacks_active   = $db->query("SELECT * FROM feedback WHERE is_archived = 0 ORDER BY tanggal DESC")->fetchAll();
$feedbacks_archived = $db->query("SELECT * FROM feedback WHERE is_archived = 1 ORDER BY tanggal DESC")->fetchAll();
$feedbacks_display  = ($tab === 'archive') ? $feedbacks_archived : $feedbacks_active;

$flash       = $_SESSION['flash'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
$redirect_mailto = $_SESSION['redirect_mailto'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_error'], $_SESSION['redirect_mailto']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<style>
/* Custom High Contrast & Beautiful Action Buttons */
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
    transition: all 0.2s cubic-bezier(0.25, 0.8, 0.25, 1);
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
    box-shadow: 0 2px 5px rgba(2, 132, 199, 0.15);
}
.btn-restore-info:hover {
    background: #BAE6FD;
    color: #0369A1 !important;
    transform: translateY(-1px);
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
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">Kotak Masuk Pengajuan Layanan &amp; Aspirasi</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Kelola, balas pesan, pulihkan arsip, atau hapus pesan secara penuh dari sistem web.</p>
    </div>
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

<!-- TAB NAVIGATION: Pesan Masuk Aktif vs Riwayat & Arsip -->
<div class="mb-4">
    <div style="background:#F1F5F9;padding:6px;border-radius:12px;display:inline-flex;gap:6px;box-shadow:inset 0 1px 2px rgba(0,0,0,0.05);">
        <a href="feedback-list.php?tab=active" style="padding:0.5rem 1.25rem;border-radius:8px;font-weight:700;font-size:0.85rem;text-decoration:none;transition:all 0.2s ease;<?= $tab === 'active' ? 'background:var(--navy);color:#ffffff;box-shadow:0 2px 6px rgba(10,25,47,0.25);' : 'color:var(--text-muted);' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16" class="me-1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
            </svg>
            Pesan Masuk Aktif
            <span class="badge rounded-pill ms-1" style="background:<?= $tab === 'active' ? '#FFD54F' : '#CBD5E1' ?>;color:<?= $tab === 'active' ? '#0A192F' : '#475569' ?>;font-size:0.75rem;"><?= count($feedbacks_active) ?></span>
        </a>
        <a href="feedback-list.php?tab=archive" style="padding:0.5rem 1.25rem;border-radius:8px;font-weight:700;font-size:0.85rem;text-decoration:none;transition:all 0.2s ease;<?= $tab === 'archive' ? 'background:var(--navy);color:#ffffff;box-shadow:0 2px 6px rgba(10,25,47,0.25);' : 'color:var(--text-muted);' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16" class="me-1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 7.5l-.625 10.632a2.25 2.25 0 01-2.247 2.118H6.622a2.25 2.25 0 01-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125z" />
            </svg>
            Riwayat &amp; Arsip Pesan
            <span class="badge rounded-pill ms-1" style="background:<?= $tab === 'archive' ? '#FFD54F' : '#CBD5E1' ?>;color:<?= $tab === 'archive' ? '#0A192F' : '#475569' ?>;font-size:0.75rem;"><?= count($feedbacks_archived) ?></span>
        </a>
    </div>
</div>

<div class="admin-table-wrap">
    <div class="admin-table-topbar d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="admin-table-title">
            <?= $tab === 'archive' ? 'Riwayat Arsip Pesan (' . count($feedbacks_display) . ')' : 'Daftar Pesan Masuk Aktif (' . count($feedbacks_display) . ')' ?>
        </div>

        <?php if ($tab === 'archive' && !empty($feedbacks_archived)): ?>
        <a href="feedback-list.php?action=empty_archive" class="btn-archive-danger" onclick="return confirm('Apakah Anda yakin ingin mengosongkan SELURUH riwayat arsip pesan secara permanen? Data yang dihapus permanen tidak dapat dikembalikan.')">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14" class="me-1">
                <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
            </svg>
            Kosongkan Seluruh Arsip (Hapus Permanen)
        </a>
        <?php endif; ?>
    </div>

    <?php if (empty($feedbacks_display)): ?>
    <div style="padding:3.5rem;text-align:center;color:var(--text-muted);">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--text-muted)" width="48" height="48" style="opacity:0.3;margin-bottom:1rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 0 1-2.25 2.25h-15a2.25 2.25 0 0 1-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0 0 19.5 4.5h-15a2.25 2.25 0 0 0-2.25 2.25m19.5 0v.243a2.25 2.25 0 0 1-1.07 1.916l-7.5 4.615a2.25 2.25 0 0 1-2.36 0L3.32 8.91a2.25 2.25 0 0 1-1.07-1.916V6.75" />
        </svg>
        <p style="font-size:0.9rem;margin:0;">
            <?= $tab === 'archive' ? 'Belum ada pesan yang diarsipkan di riwayat.' : 'Belum ada pesan atau pengajuan layanan yang masuk.' ?>
        </p>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="130">Tanggal</th>
                    <th width="200">Nama &amp; Pengirim</th>
                    <th width="160">Jenis Layanan</th>
                    <th>Isi Pesan &amp; Balasan Admin</th>
                    <th width="110">Status</th>
                    <th width="160">Aksi Admin</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($feedbacks_display as $f): ?>
                <tr style="<?= $f['status'] === 'Belum Dibaca' && $tab === 'active' ? 'background:#F8FAFC;' : '' ?>">
                    <td style="font-size:0.8rem;color:var(--text-muted);"><?= formatTanggal($f['tanggal']) ?></td>
                    <td>
                        <div style="font-weight:700;color:var(--navy);font-size:0.9rem;"><?= e($f['nama']) ?></div>
                        <div style="font-size:0.78rem;color:var(--text-muted);">
                            <a href="mailto:<?= e($f['email']) ?>" style="color:var(--navy);text-decoration:underline;"><?= e($f['email']) ?></a>
                        </div>
                        <?php if ($f['instansi']): ?>
                        <div style="font-size:0.72rem;color:var(--text-light);margin-top:2px;"><?= e($f['instansi']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="card-category-badge" style="font-size:0.72rem;white-space:normal;display:inline-block;"><?= e($f['jenis_layanan'] ?: 'Umum') ?></span>
                    </td>
                    <td>
                        <div style="font-size:0.85rem;color:var(--text-main);line-height:1.6;white-space:pre-line;">
                            <?= e($f['pesan']) ?>
                        </div>

                        <!-- Display Admin Reply if exists -->
                        <?php if (!empty($f['balasan'])): ?>
                        <div style="margin-top:10px;padding:10px 14px;background:#F1F5F9;border-left:4px solid var(--purple);border-radius:6px;font-size:0.83rem;">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <span style="font-weight:700;color:var(--purple);display:inline-flex;align-items:center;gap:4px;">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                    </svg>
                                    Balasan Resmi Admin LPM:
                                </span>
                                <?php if (!empty($f['tanggal_balas'])): ?>
                                <span style="font-size:0.72rem;color:var(--text-muted);"><?= formatTanggal($f['tanggal_balas']) ?></span>
                                <?php endif; ?>
                            </div>
                            <div style="color:var(--navy);white-space:pre-line;line-height:1.5;"><?= e($f['balasan']) ?></div>
                        </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($tab === 'archive'): ?>
                            <span style="font-size:0.72rem;font-weight:600;color:#616161;background:#EEEEEE;padding:0.25rem 0.65rem;border-radius:50px;">
                                Arsip
                            </span>
                        <?php elseif ($f['status'] === 'Sudah Dibalas'): ?>
                            <span style="font-size:0.72rem;font-weight:600;color:#1565C0;background:#E3F2FD;padding:0.25rem 0.65rem;border-radius:50px;">
                                Dibalas
                            </span>
                        <?php elseif ($f['status'] === 'Selesai'): ?>
                            <span style="font-size:0.72rem;font-weight:600;color:#2E7D32;background:#E8F5E9;padding:0.25rem 0.65rem;border-radius:50px;">
                                Selesai
                            </span>
                        <?php else: ?>
                            <span style="font-size:0.72rem;font-weight:600;color:#E65100;background:#FFF3E0;padding:0.25rem 0.65rem;border-radius:50px;">
                                Baru
                            </span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($tab === 'active'): ?>
                            <!-- AKSI PESAN AKTIF -->
                            <div style="display:flex;flex-direction:column;gap:0.4rem;">
                                <!-- Button Balas Pesan -->
                                <button type="button" class="btn-balas-primary" data-bs-toggle="modal" data-bs-target="#modalBalas<?= $f['id'] ?>">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14" class="me-1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" />
                                    </svg>
                                    Balas Pesan
                                </button>

                                <div style="display:flex;gap:0.3rem;">
                                    <?php if ($f['status'] !== 'Selesai'): ?>
                                    <a href="feedback-list.php?status=selesai&id=<?= $f['id'] ?>&tab=active" class="btn-selesai-success" style="flex-grow:1;" title="Tandai Selesai">
                                        Selesai
                                    </a>
                                    <?php endif; ?>
                                    <a href="feedback-list.php?action=archive&id=<?= $f['id'] ?>" class="btn-archive-danger" style="flex-grow:1;" onclick="return confirm('Pindahkan pesan ini ke Riwayat & Arsip? Data tetap tersimpan di riwayat.')" title="Pindahkan ke Riwayat & Arsip">
                                        Arsip
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- AKSI PESAN ARSIP: PULIHKAN ATAU HAPUS PERMANEN LANGSUNG DARI WEB -->
                            <div style="display:flex;flex-direction:column;gap:0.4rem;">
                                <a href="feedback-list.php?action=restore&id=<?= $f['id'] ?>" class="btn-restore-info" onclick="return confirm('Pulihkan pesan ini kembali ke Kotak Masuk Pesan Aktif?')" title="Kembalikan pesan ke Kotak Masuk Aktif">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="14" height="14" class="me-1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M16.023 9.348h4.992v-.001M2.985 19.644v-4.992m0 0h4.992m-4.993 0 3.181 3.183a8.25 8.25 0 0 0 13.803-3.7M4.031 9.865a8.25 8.25 0 0 1 13.803-3.7l3.181 3.182m0-4.991v4.99" />
                                    </svg>
                                    Pulihkan
                                </a>
                                <a href="feedback-list.php?action=permanent_delete&id=<?= $f['id'] ?>" class="btn-archive-danger" onclick="return confirm('Hapus permanen pesan ini dari sistem web? Data akan dihapus selamanya.')" title="Hapus permanen dari database">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13" class="me-1">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                    </svg>
                                    Hapus Permanen
                                </a>
                            </div>
                        <?php endif; ?>

                        <!-- MODAL BALAS PESAN -->
                        <div class="modal fade" id="modalBalas<?= $f['id'] ?>" tabindex="-1" aria-labelledby="modalBalasLabel<?= $f['id'] ?>" aria-hidden="true">
                            <div class="modal-dialog modal-dialog-centered modal-lg">
                                <div class="modal-content" style="border-radius:16px;border:none;overflow:hidden;box-shadow:0 15px 40px rgba(0,0,0,0.2);">
                                    <div class="modal-header" style="background:linear-gradient(135deg, var(--navy), #132D54);color:#fff;padding:1.25rem 1.5rem;">
                                        <h5 class="modal-title fw-bold" id="modalBalasLabel<?= $f['id'] ?>" style="font-size:1.1rem;">
                                            Balas Pesan Pengajuan: <?= e($f['nama']) ?>
                                        </h5>
                                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                                    </div>
                                    <form method="post" action="feedback-list.php?tab=<?= $tab ?>">
                                        <input type="hidden" name="action" value="balas_pesan">
                                        <input type="hidden" name="id" value="<?= $f['id'] ?>">

                                        <div class="modal-body p-4" style="text-align:left;">
                                            <!-- Info Pesan Pengirim -->
                                            <div class="p-3 mb-4 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;">
                                                <div class="row g-2 mb-2" style="font-size:0.85rem;">
                                                    <div class="col-sm-6">
                                                        <strong style="color:var(--navy);">Pengirim:</strong> <?= e($f['nama']) ?> (<?= e($f['email']) ?>)
                                                    </div>
                                                    <div class="col-sm-6">
                                                        <strong style="color:var(--navy);">Layanan:</strong> <?= e($f['jenis_layanan'] ?: 'Umum') ?>
                                                    </div>
                                                </div>
                                                <div style="font-size:0.83rem;color:var(--text-muted);background:#fff;padding:10px;border-radius:6px;border:1px solid #E2E8F0;white-space:pre-line;">
                                                    <strong style="color:var(--navy);display:block;margin-bottom:3px;">Isi Pesan Masuk:</strong>
                                                    <?= e($f['pesan']) ?>
                                                </div>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold" style="color:var(--navy);">Subject / Judul Email</label>
                                                <input type="text" name="subject" class="form-control" value="[LPM SCU] Tanggapan Layanan: <?= e($f['jenis_layanan'] ?: 'Konsultasi Mutu') ?>" required>
                                            </div>

                                            <div class="mb-3">
                                                <label class="form-label fw-bold" style="color:var(--navy);">Teks Balasan Resmi LPM</label>
                                                <?php
                                                $default_reply = $f['balasan'] ?: "Yth. Bapak/Ibu " . $f['nama'] . ",\n\nTerima kasih telah menghubungi Lembaga Penjaminan Mutu (LPM) UNIKA Soegijapranata mengenai " . ($f['jenis_layanan'] ?: 'layanan kami') . ".\n\nMenindaklanjuti pengajuan Anda, berikut tanggapan dari tim kami:\n[Tuliskan tanggapan / jadwal / informasi di sini]\n\nDemikian disampaikan. Jika membutuhkan informasi lebih lanjut, silakan menghubungi kantor LPM UNIKA.\n\nSalam hangat,\nLembaga Penjaminan Mutu (LPM)\nUniversitas Katolik Soegijapranata";
                                                ?>
                                                <textarea name="balasan" class="form-control" rows="7" required style="font-size:0.88rem;line-height:1.6;"><?= e($default_reply) ?></textarea>
                                            </div>

                                            <div class="form-check mb-3 p-3 rounded-3" style="background:#FFF8E1;border:1px solid #FFE082;">
                                                <input class="form-check-input ms-0 me-2" type="checkbox" name="send_email" value="1" id="sendEmailCheck<?= $f['id'] ?>" checked>
                                                <label class="form-check-label small fw-semibold" for="sendEmailCheck<?= $f['id'] ?>" style="color:#E65100;">
                                                    Buka otomatis di Aplikasi Email / Gmail / Outlook (ter-direct mailto:) setelah disimpan
                                                </label>
                                            </div>
                                        </div>

                                        <div class="modal-footer bg-light px-4 py-3">
                                            <button type="button" class="btn btn-outline-secondary btn-sm rounded-pill px-3" data-bs-dismiss="modal">Batal</button>
                                            <button type="submit" class="btn-balas-primary" style="padding:0.5rem 1.5rem;font-size:0.88rem;">
                                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16" class="me-1">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                                                </svg>
                                                Simpan &amp; Kirim Balasan
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
