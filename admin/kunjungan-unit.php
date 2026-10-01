<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Pengaturan Kunjungan (Jam Operasional)';
$db = getDB();

// Handle Save Jam Operasional Settings
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_settings') {
    $jam_mulai = trim($_POST['kunjungan_jam_mulai'] ?? '08:00');
    $jam_selesai = trim($_POST['kunjungan_jam_selesai'] ?? '15:00');

    $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('kunjungan_jam_mulai', ?) ON DUPLICATE KEY UPDATE nilai = ?")->execute([$jam_mulai, $jam_mulai]);
    $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES ('kunjungan_jam_selesai', ?) ON DUPLICATE KEY UPDATE nilai = ?")->execute([$jam_selesai, $jam_selesai]);

    $_SESSION['flash'] = 'Pengaturan jam operasional kunjungan berhasil disimpan.';
    redirect(SITE_URL . '/admin/kunjungan-unit.php');
}

$jam_mulai = getPengaturan('kunjungan_jam_mulai', '08:00');
$jam_selesai = getPengaturan('kunjungan_jam_selesai', '15:00');

$flash = $_SESSION['flash'] ?? '';
$flash_error = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_error']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">Pengaturan Kunjungan ke LPM (Jam
            Operasional)</h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">Kelola batasan jam operasional pelaksanaan
            kunjungan resmi institusi luar ke Lembaga Penjaminan Mutu.</p>
    </div>
    <a href="kunjungan-list.php" class="btn-outline">
        &larr; Kembali ke Kotak Masuk Kunjungan
    </a>
</div>

<?php if ($flash): ?>
    <div class="alert-lpm alert-success mb-4">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"
            width="18" height="18">
            <path stroke-linecap="round" stroke-linejoin="round"
                d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
        </svg>
            <?= e($flash) ?>
    </div>
<?php endif; ?>

<?php if ($flash_error): ?>
    <div class="alert-lpm alert-danger mb-4">
            <?= e($flash_error) ?>
    </div>
<?php endif; ?>

<div class="row g-4">
    <!-- Form Pengaturan Jam Operasional Kunjungan -->
    <div class="col-lg-6">
        <div class="admin-table-wrap p-4 mb-4">
            <h5
                style="font-weight:700;color:var(--navy);font-size:1.05rem;margin-bottom:1rem;display:flex;align-items:center;gap:8px;">
                ⏰ Jam Operasional Kunjungan
            </h5>
            <form method="post" action="kunjungan-unit.php">
                <input type="hidden" name="action" value="save_settings">
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:0.85rem;">Jam Mulai Operasional</label>
                    <input type="time" name="kunjungan_jam_mulai" class="form-control" value="<?= e($jam_mulai) ?>"
                        required style="border:1.5px solid var(--border);padding:0.65rem 1rem;">
                    <div class="form-text">Batas awal jam kunjungan yang dapat dipilih tamu (misal: 08:00 WIB).</div>
                </div>
                <div class="mb-4">
                    <label class="form-label fw-bold" style="font-size:0.85rem;">Jam Selesai Operasional</label>
                    <input type="time" name="kunjungan_jam_selesai" class="form-control" value="<?= e($jam_selesai) ?>"
                        required style="border:1.5px solid var(--border);padding:0.65rem 1rem;">
                    <div class="form-text">Batas akhir jam kunjungan yang dapat dipilih tamu (misal: 15:00 WIB).</div>
                </div>
                <button type="submit" class="btn-save w-100 justify-content-center" style="padding:0.75rem 1rem;">
                    Simpan Jam Operasional
                </button>
            </form>
        </div>
    </div>

    <!-- Info Ketentuan Kebijakan Kunjungan -->
    <div class="col-lg-6">
        <div class="admin-table-wrap p-4">
            <h5
                style="font-weight:700;color:var(--navy);font-size:1.05rem;margin-bottom:1rem;display:flex;align-items:center;gap:8px;">
                <i class="bi bi-shield-check text-primary me-2"></i> Kebijakan Penerimaan Kunjungan ke LPM
            </h5>
            <div style="font-size:0.875rem;color:var(--text);line-height:1.6;">
                <div class="p-3 mb-3"
                    style="background:#F0FDF4;border:1px solid #BBF7D0;border-radius:var(--radius-sm);">
                    <div style="font-weight:700;color:#166534;margin-bottom:4px;"><i class="bi bi-building me-1"></i> Tujuan Kunjungan Khusus LPM</div>
                    <div style="color:#15803D;font-size:0.82rem;">
                        Permohonan kunjungan yang masuk melalui portal publik diarahkan langsung ke <strong>Lembaga
                            Penjaminan Mutu (LPM)</strong> untuk kegiatan studi banding, konsultasi mutu, benchmarking
                        SPMI, atau evaluasi penjaminan mutu.
                    </div>
                </div>

                <div class="p-3 mb-3"
                    style="background:#FEF3C7;border:1px solid #FDE68A;border-radius:var(--radius-sm);">
                    <div style="font-weight:700;color:#92400E;margin-bottom:4px;"><i class="bi bi-people me-1"></i> Ketentuan Kuota Peserta (Maksimal
                        20 Orang)</div>
                    <div style="color:#B45309;font-size:0.82rem;">
                        Formulir publik membatasi input rincian data peserta hingga <strong>maksimal 20 orang</strong>.
                        Rombongan yang membawa lebih dari 20 orang diwajibkan melampirkan daftar peserta lengkap dalam
                        surat permohonan resmi (PDF).
                    </div>
                </div>

                <div class="p-3"
                    style="background:var(--bg-card);border:1px solid var(--border);border-radius:var(--radius-sm);">
                    <div style="font-weight:700;color:var(--navy);margin-bottom:4px;"><i class="bi bi-clock-history me-1"></i> Konfirmasi &amp; Respon Kunjungan
                    </div>
                    <div style="color:var(--text-muted);font-size:0.82rem;">
                        Seluruh permohonan kunjungan yang diajukan akan masuk ke menu <strong>Kotak Masuk
                            Kunjungan</strong> dengan status <em>Menunggu</em> untuk ditinjau kelayakan jadwal dan
                        ketersediaan tim LPM.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>