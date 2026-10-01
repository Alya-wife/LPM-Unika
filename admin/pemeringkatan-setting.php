<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Kelola Pemeringkatan EduRank';
$db = getDB();

// Handle Force Sync
if (isset($_POST['action']) && $_POST['action'] === 'sync_edurank') {
    getEduRankRankings(true);
    $_SESSION['flash'] = 'Data pemeringkatan EduRank.org berhasil disinkronkan secara langsung!';
    redirect(SITE_URL . '/admin/pemeringkatan-setting.php');
}

// Handle Save Custom Text
if (isset($_POST['action']) && $_POST['action'] === 'save_text') {
    setPengaturan('edurank_desc_indonesia', trim($_POST['edurank_desc_indonesia'] ?? ''));
    setPengaturan('edurank_desc_semarang', trim($_POST['edurank_desc_semarang'] ?? ''));
    $_SESSION['flash'] = 'Catatan kartu pemeringkatan berhasil disimpan.';
    redirect(SITE_URL . '/admin/pemeringkatan-setting.php');
}

$edurank_data = getEduRankRankings();

$indonesia_rank = $edurank_data['indonesia']['rank'] ?? 65;
$indonesia_of   = $edurank_data['indonesia']['total'] ?? 562;
$semarang_rank  = $edurank_data['semarang']['rank'] ?? 3;
$semarang_of    = $edurank_data['semarang']['total'] ?? 14;
$topics         = $edurank_data['topics'] ?? [];
$last_sync      = $edurank_data['updated_at'] ?? date('Y-m-d H:i:s');
$edurank_url    = $edurank_data['url'] ?? 'https://edurank.org/uni/soegijapranata-catholic-university/rankings/';

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">
            Kelola Pemeringkatan EduRank.org
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Pantau dan sinkronkan data peringkat resmi Universitas Katolik Soegijapranata dan peringkat per jurusan secara real-time.
        </p>
    </div>
    <div class="d-flex gap-2">
        <a href="<?= SITE_URL ?>/pemeringkatan.php" target="_blank" class="btn btn-sm btn-outline-secondary fw-bold d-flex align-items-center gap-1" style="border-radius:8px;">
            <i class="bi bi-box-arrow-up-right"></i> Lihat Halaman Publik
        </a>
        <form method="POST" class="m-0">
            <input type="hidden" name="action" value="sync_edurank">
            <button type="submit" class="btn btn-sm btn-primary fw-bold d-flex align-items-center gap-1" style="border-radius:8px;background:var(--navy);border:none;">
                <i class="bi bi-arrow-repeat"></i> Sinkronkan Live Sekarang
            </button>
        </form>
    </div>
</div>

<?php if ($flash): ?>
<div class="alert alert-success alert-dismissible fade show rounded-3 mb-4" role="alert" style="font-size:0.88rem;">
    <i class="bi bi-check-circle-fill me-2"></i> <?= htmlspecialchars($flash) ?>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<!-- Status Banner -->
<div class="card mb-4 border-0 shadow-sm" style="background:#ffffff;border:1px solid #E2E8F0;border-left:5px solid #D97706 !important;border-radius:14px;">
    <div class="card-body p-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div style="width:48px;height:48px;border-radius:12px;background:rgba(217,119,6,0.1);color:#D97706;display:flex;align-items:center;justify-content:center;font-size:1.5rem;flex-shrink:0;">
                <i class="bi bi-patch-check-fill"></i>
            </div>
            <div>
                <h5 class="fw-bold mb-1" style="font-size:1.05rem;color:var(--navy);">Sumber Data Resmi: EduRank.org</h5>
                <div style="font-size:0.84rem;color:#64748B;">
                    <span>URL Sumber: <a href="<?= htmlspecialchars($edurank_url) ?>" target="_blank" class="text-primary text-decoration-none"><?= htmlspecialchars($edurank_url) ?></a></span>
                    <br>
                    <span>Terakhir Disinkronkan: <strong><?= date('d M Y, H:i:s', strtotime($last_sync)) ?> WIB</strong> (Smart Cache TTL: 6 Jam)</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Preview Ringkasan Peringkat Utama -->
<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card p-4 border-0 rounded-4 shadow-sm text-center" style="background:#ffffff;border:1px solid #E2E8F0;">
            <div style="font-size:2.4rem;font-weight:900;color:#D97706;line-height:1;">
                #<?= htmlspecialchars($indonesia_rank) ?> <span style="font-size:1rem;color:#64748B;font-weight:700;">of <?= htmlspecialchars($indonesia_of) ?></span>
            </div>
            <h5 class="fw-bold mt-2 mb-1" style="color:var(--navy);">In Indonesia</h5>
            <span class="badge bg-light text-muted border mx-auto">Tingkat Nasional</span>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card p-4 border-0 rounded-4 shadow-sm text-center" style="background:#ffffff;border:1px solid #E2E8F0;">
            <div style="font-size:2.4rem;font-weight:900;color:#D97706;line-height:1;">
                #<?= htmlspecialchars($semarang_rank) ?> <span style="font-size:1rem;color:#64748B;font-weight:700;">of <?= htmlspecialchars($semarang_of) ?></span>
            </div>
            <h5 class="fw-bold mt-2 mb-1" style="color:var(--navy);">In Semarang</h5>
            <span class="badge bg-light text-muted border mx-auto">Tingkat Kota Semarang</span>
        </div>
    </div>
</div>

<!-- Daftar Rincian Peringkat Jurusan yang Ditarik Otomatis -->
<div class="card border-0 rounded-4 shadow-sm bg-white overflow-hidden">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-list-check text-primary fs-5"></i>
            <h5 class="m-0 fw-bold" style="font-size:0.98rem;color:var(--navy);">
                Rincian Bidang Studi / Jurusan Terdaftar di EduRank (<?= count($topics) ?> Kategori)
            </h5>
        </div>
        <small class="text-muted">Ditampilkan khusus peringkat tingkat Indonesia pada halaman publik.</small>
    </div>
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                <thead style="background:#F8FAFC;">
                    <tr>
                        <th class="ps-4" width="30%">Rumpun Kategori</th>
                        <th width="45%">Disiplin Ilmu / Sub-Bidang</th>
                        <th class="text-center" width="25%">Peringkat di Indonesia</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($topics)): ?>
                    <tr>
                        <td colspan="3" class="text-center py-4 text-muted">Belum ada data topik tersimpan. Silakan klik tombol "Sinkronkan Live Sekarang".</td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($topics as $t): ?>
                            <?php foreach ($t['subfields'] as $sIdx => $sf): ?>
                            <tr>
                                <?php if ($sIdx === 0): ?>
                                <td rowspan="<?= count($t['subfields']) ?>" class="ps-4 fw-bold align-top" style="background:#FAF5FF;color:#6B21A8;border-right:1px solid #E2E8F0;">
                                    <i class="bi bi-journal-bookmark me-1"></i><?= htmlspecialchars($t['category']) ?>
                                </td>
                                <?php endif; ?>
                                <td class="fw-semibold text-dark"><?= htmlspecialchars($sf['name']) ?></td>
                                <td class="text-center">
                                    <span class="badge rounded-pill fw-bold" style="background:#ECFDF5;color:#065F46;border:1px solid #A7F3D0;font-size:0.82rem;padding:4px 12px;">
                                        #<?= htmlspecialchars($sf['indonesia_rank']) ?> di Indonesia
                                    </span>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
