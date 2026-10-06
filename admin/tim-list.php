<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Struktur Organisasi & Tim LPM / GPM';
$db = getDB();

$kat = $_GET['kat'] ?? 'lpm';
if (!in_array($kat, ['lpm', 'gpm'])) {
    $kat = 'lpm';
}

// Handle Delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    $row = $db->prepare("SELECT foto, kategori FROM tim_lpm WHERE id = ?");
    $row->execute([$id]);
    $item = $row->fetch();
    $item_kat = $item['kategori'] ?? 'lpm';
    if ($item && $item['foto'] && file_exists(__DIR__ . '/../uploads/tim/' . $item['foto'])) {
        @unlink(__DIR__ . '/../uploads/tim/' . $item['foto']);
    }
    $db->prepare("DELETE FROM tim_lpm WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Personel berhasil dihapus.';
    redirect(SITE_URL . '/admin/tim-list.php?kat=' . $item_kat);
}

// Handle Save GPM Narrative & Functions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_gpm_narasi') {
    setPengaturan('gpm_narasi_pengantar', trim($_POST['gpm_narasi_pengantar'] ?? ''));
    setPengaturan('gpm_narasi_tugas', trim($_POST['gpm_narasi_tugas'] ?? ''));
    setPengaturan('gpm_narasi_fungsi', trim($_POST['gpm_narasi_fungsi'] ?? ''));
    setPengaturan('gpm_narasi_dasar_hukum', trim($_POST['gpm_narasi_dasar_hukum'] ?? ''));
    $_SESSION['flash'] = 'Pengaturan narasi, tugas, dan fungsi GPM Fakultas berhasil diperbarui.';
    redirect(SITE_URL . '/admin/tim-list.php?kat=gpm');
}

// Handle Save Pimpinan Universitas & Struktur Map
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_pimpinan_struktur') {
    $rektor_nama = trim($_POST['struktur_rektor_nama'] ?? '');
    $rektor_jabatan = trim($_POST['struktur_rektor_jabatan'] ?? 'Rektorat');
    $wr_nama = trim($_POST['struktur_wr_nama'] ?? '');
    $wr_jabatan = trim($_POST['struktur_wr_jabatan'] ?? 'Wakil Rektor SDM & TK');

    setPengaturan('struktur_rektor_nama', $rektor_nama);
    setPengaturan('struktur_rektor_jabatan', $rektor_jabatan);
    setPengaturan('struktur_wr_nama', $wr_nama);
    setPengaturan('struktur_wr_jabatan', $wr_jabatan);

    // Sync ke struktur_organisasi_json jika ada
    $raw = getPengaturan('struktur_organisasi_json', '');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded) && isset($decoded['nodes'])) {
            foreach ($decoded['nodes'] as &$node) {
                if (($node['id'] ?? '') === 'node_rektor') {
                    if ($rektor_jabatan !== '') $node['title'] = $rektor_jabatan;
                    if ($rektor_nama !== '') $node['subtitle'] = $rektor_nama;
                } elseif (($node['id'] ?? '') === 'node_wr') {
                    if ($wr_jabatan !== '') $node['title'] = $wr_jabatan;
                    if ($wr_nama !== '') $node['subtitle'] = $wr_nama;
                }
            }
            setPengaturan('struktur_organisasi_json', json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        }
    }

    $_SESSION['flash'] = 'Data Rektor & Wakil Rektor untuk bagan struktur berhasil diperbarui.';
    redirect(SITE_URL . '/admin/tim-list.php?kat=' . $kat);
}

// Counts
$count_lpm = (int)$db->query("SELECT COUNT(*) FROM tim_lpm WHERE kategori = 'lpm'")->fetchColumn();
$count_gpm = (int)$db->query("SELECT COUNT(*) FROM tim_lpm WHERE kategori = 'gpm'")->fetchColumn();

// Data Pimpinan untuk Bagan Struktur
$rektor_nama_val    = getPengaturan('struktur_rektor_nama', 'Dr. Ferdinandus Hindiarto, M.Si.');
$rektor_jabatan_val = getPengaturan('struktur_rektor_jabatan', 'Rektorat');
$wr_nama_val        = getPengaturan('struktur_wr_nama', 'Robertus Setiawan Aji N., S.T., M.CompIT., Ph.D.');
$wr_jabatan_val     = getPengaturan('struktur_wr_jabatan', 'Wakil Rektor SDM & TK');

// Query
$stmt = $db->prepare("SELECT * FROM tim_lpm WHERE kategori = ? ORDER BY urutan ASC, id ASC");
$stmt->execute([$kat]);
$tim = $stmt->fetchAll();

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">
            Kelola Personel <?= $kat === 'gpm' ? 'Gugus Penjaminan Mutu (GPM) Fakultas' : 'LPM UNIKA' ?>
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Kelola data pimpinan, koordinator, dan personel yang tampil pada kartu di halaman profil.
        </p>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <a href="struktur-organisasi.php" class="btn btn-outline-primary d-inline-flex align-items-center gap-2" style="font-size:0.85rem;font-weight:600;border-radius:8px;">
            <i class="bi bi-diagram-3-fill"></i>
            Visual Editor Bagan (Drag &amp; Drop)
        </a>
        <a href="tim-form.php?kategori=<?= $kat ?>" class="btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Personel <?= $kat === 'gpm' ? 'GPM' : 'LPM' ?>
        </a>
    </div>
</div>

<!-- Card Pimpinan Universitas (Rektor & Wakil Rektor) -->
<div class="card mb-4 border-0 shadow-sm" style="background:#ffffff;border:1px solid #E2E8F0;border-left:5px solid #1E3A8A !important;border-radius:14px;overflow:hidden;">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-mortarboard-fill text-primary fs-5"></i>
            <div>
                <h5 class="m-0 fw-bold" style="font-size:1rem;color:var(--navy);">Pengaturan Rektor &amp; Wakil Rektor (Bagan Struktur Organisasi)</h5>
                <small class="text-muted">Ubah nama dan sebutan jabatan Rektor &amp; Wakil Rektor yang tampil di pucuk hirarki bagan profil.</small>
            </div>
        </div>
        <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="collapse" data-bs-target="#collapsePimpinan" aria-expanded="false" aria-controls="collapsePimpinan" style="border-radius:6px;font-size:0.8rem;">
            <i class="bi bi-pencil-square me-1"></i> Buka / Tutup Form
        </button>
    </div>
    <div class="collapse show" id="collapsePimpinan">
        <div class="card-body p-4 bg-light">
            <form action="tim-list.php?kat=<?= $kat ?>" method="POST">
                <input type="hidden" name="action" value="save_pimpinan_struktur">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:var(--navy);">Nama Jabatan Rektor</label>
                        <input type="text" name="struktur_rektor_jabatan" class="form-control form-control-sm" value="<?= e($rektor_jabatan_val) ?>" placeholder="Contoh: Rektorat / Rektor" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:var(--navy);">Nama &amp; Gelar Rektor</label>
                        <input type="text" name="struktur_rektor_nama" class="form-control form-control-sm" value="<?= e($rektor_nama_val) ?>" placeholder="Nama lengkap rektor beserta gelar" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:var(--navy);">Nama Jabatan Wakil Rektor</label>
                        <input type="text" name="struktur_wr_jabatan" class="form-control form-control-sm" value="<?= e($wr_jabatan_val) ?>" placeholder="Contoh: Wakil Rektor SDM & TK" required>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label fw-semibold" style="font-size:0.82rem;color:var(--navy);">Nama &amp; Gelar Wakil Rektor</label>
                        <input type="text" name="struktur_wr_nama" class="form-control form-control-sm" value="<?= e($wr_nama_val) ?>" placeholder="Nama lengkap wakil rektor beserta gelar" required>
                    </div>
                </div>
                <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top flex-wrap gap-2">
                    <div class="text-muted" style="font-size:0.8rem;">
                        <i class="bi bi-info-circle me-1"></i> Perubahan di sini otomatis langsung memperbarui kotak bagan di halaman profil dan sinkron ke visual editor bagan.
                    </div>
                    <button type="submit" class="btn btn-sm btn-primary px-3 py-1 fw-bold" style="border-radius:6px;">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Pimpinan Universitas
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div class="mb-4 p-3 rounded-3 d-flex align-items-center justify-content-between flex-wrap gap-2" style="background:#F0FDF4; border:1.5px solid #BBF7D0;">
    <div class="d-flex align-items-center gap-2">
        <i class="bi bi-diagram-3-fill text-success fs-5"></i>
        <div style="font-size:0.85rem; color:#166534;">
            <strong>Visual Builder Bagan Struktur:</strong> Ingin menggeser posisi kotak, menambah kotak baru, memindahkan hirarki, atau menarik garis koneksi antar blok? Gunakan editor visual interaktif kami.
        </div>
    </div>
    <div class="d-flex gap-2">
        <a href="struktur-organisasi.php" class="btn btn-sm btn-success fw-bold" style="font-size:0.78rem; border-radius:6px; white-space:nowrap;">
            <i class="bi bi-sliders me-1"></i> Buka Editor Bagan &rarr;
        </a>
        <a href="<?= SITE_URL ?>/profil.php#struktur" target="_blank" class="btn btn-sm btn-outline-success" style="font-size:0.78rem; border-radius:6px; white-space:nowrap;">
            Lihat di Web &rarr;
        </a>
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

<!-- Tab Filter LPM vs GPM -->
<div class="mb-4">
    <div style="background:#F1F5F9;padding:6px;border-radius:12px;display:inline-flex;gap:6px;box-shadow:inset 0 1px 2px rgba(0,0,0,0.05);">
        <a href="tim-list.php?kat=lpm" style="padding:0.5rem 1.25rem;border-radius:8px;font-weight:700;font-size:0.85rem;text-decoration:none;transition:all 0.2s ease;<?= $kat === 'lpm' ? 'background:var(--navy);color:#ffffff;box-shadow:0 2px 6px rgba(10,25,47,0.25);' : 'color:var(--text-muted);' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16" class="me-1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" />
            </svg>
            1. Personel LPM UNIKA
            <span class="badge rounded-pill ms-1" style="background:<?= $kat === 'lpm' ? '#FFD54F' : '#CBD5E1' ?>;color:<?= $kat === 'lpm' ? '#0A192F' : '#475569' ?>;font-size:0.75rem;"><?= $count_lpm ?></span>
        </a>
        <a href="tim-list.php?kat=gpm" style="padding:0.5rem 1.25rem;border-radius:8px;font-weight:700;font-size:0.85rem;text-decoration:none;transition:all 0.2s ease;<?= $kat === 'gpm' ? 'background:var(--navy);color:#ffffff;box-shadow:0 2px 6px rgba(10,25,47,0.25);' : 'color:var(--text-muted);' ?>">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16" class="me-1">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 21a9.004 9.004 0 0 0 8.716-6.747M12 21a9.004 9.004 0 0 1-8.716-6.747M12 21c2.485 0 4.5-4.03 4.5-9S14.485 3 12 3m0 18c-2.485 0-4.5-4.03-4.5-9S9.515 3 12 3m0 0a8.997 8.997 0 0 1 7.843 4.582M12 3a8.997 8.997 0 0 0-7.843 4.582m15.686 0A11.953 11.953 0 0 1 12 10.5c-2.998 0-5.74-1.1-7.843-2.918m15.686 0A8.959 8.959 0 0 1 21 12c0 .778-.099 1.533-.284 2.253m0 0A17.919 17.919 0 0 1 12 16.5c-3.162 0-6.133-.815-8.716-2.247m0 0A9.015 9.015 0 0 1 3 12c0-1.605.42-3.113 1.157-4.418" />
            </svg>
            2. Gugus Penjaminan Mutu (GPM) Fakultas
            <span class="badge rounded-pill ms-1" style="background:<?= $kat === 'gpm' ? '#FFD54F' : '#CBD5E1' ?>;color:<?= $kat === 'gpm' ? '#0A192F' : '#475569' ?>;font-size:0.75rem;"><?= $count_gpm ?></span>
        </a>
    </div>
</div>

<?php if ($kat === 'gpm'): 
    $gpm_pengantar   = getPengaturan('gpm_narasi_pengantar', "Gugus Penjaminan Mutu merupakan organ fakultas yang dipimpin oleh seorang koordinator.\nDan jumlah anggota Gugus Penjaminan Mutu ditetapkan oleh Dekan dengan mempertimbangkan jumlah program studi yang dikelola.");
    $gpm_tugas       = getPengaturan('gpm_narasi_tugas', "Membantu Dekan dalam melaksanakan sistem penjaminan mutu pada Fakultas dan Program Studi.");
    $gpm_fungsi      = getPengaturan('gpm_narasi_fungsi', "Pemberian saran dan rekomendasi kepada Dekan dalam perumusan kebijakan pengembangan pelaksanaan SPMI pada tingkat Fakultas\nPengoordinasian pelaksanaan SPMI pada Fakultas maupun Program Studi sesuai peraturan perundang-undangan maupun peraturan yang berlaku di Universitas\nFasilitasi penyusunan prosedur mutu, instruksi kerja maupun dokumen mutu lainnya dalam pelaksanaan SPMI\nPenyusunan instrumen monitoring dan evaluasi yang bersifat khusus untuk Fakultas dan/atau Program Studi\nMonitoring dan evaluasi pelaksanaan SPMI pada Fakultas maupun Program Studi\nPengoordinasian dalam pelaksanaan proses akreditasi Program Studi\nPengoordinasian dengan LPM dalam pelaksanaan SPMI pada Fakultas maupun pelaksanaan proses akreditasi Program Studi\nPengoordinasian pembukaan program pendidikan baru dalam memenuhi akreditasi minimal\nPelaporan pelaksanaan sistem penjaminan mutu tingkat Fakultas kepada Rektor melalui LPM.");
    $gpm_dasar_hukum = getPengaturan('gpm_narasi_dasar_hukum', 'Buku Organisasi & Tata Kelola | Unika Soegijapranata (Pasal 31)');
?>
<div class="card mb-4 border-0 shadow-sm" style="background:#ffffff;border:1px solid #E2E8F0;border-left:5px solid #16A34A !important;border-radius:14px;overflow:hidden;">
    <div class="card-header bg-white py-3 px-4 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-file-earmark-text-fill text-success fs-5"></i>
            <div>
                <h5 class="m-0 fw-bold" style="font-size:1rem;color:var(--navy);">Pengaturan Narasi, Tugas &amp; 9 Fungsi GPM (Pasal 31)</h5>
                <small class="text-muted">Teks ini tampil pada kartu penjelasan di atas daftar personel GPM di halaman profil publik.</small>
            </div>
        </div>
        <button class="btn btn-sm btn-outline-success fw-bold" type="button" data-bs-toggle="collapse" data-bs-target="#collapseGpmSettings" aria-expanded="false" aria-controls="collapseGpmSettings">
            <i class="bi bi-pencil-square me-1"></i> Edit Narasi &amp; Regulasi GPM
        </button>
    </div>
    <div class="collapse" id="collapseGpmSettings">
        <div class="card-body p-4" style="background:#F8FAFC;">
            <form method="POST">
                <input type="hidden" name="action" value="save_gpm_narasi">
                
                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:0.85rem;">Dasar Regulasi / Rujukan Dokumen</label>
                    <input type="text" name="gpm_narasi_dasar_hukum" class="form-control" value="<?= htmlspecialchars($gpm_dasar_hukum) ?>" placeholder="Contoh: Buku Organisasi &amp; Tata Kelola | Unika Soegijapranata (Pasal 31)">
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:0.85rem;">Narasi Pengantar GPM Fakultas</label>
                    <textarea name="gpm_narasi_pengantar" rows="3" class="form-control" placeholder="Tuliskan narasi pengantar organ GPM..."><?= htmlspecialchars($gpm_pengantar) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:0.85rem;">Tugas Gugus Penjaminan Mutu (Pasal 31 Ayat 1)</label>
                    <textarea name="gpm_narasi_tugas" rows="2" class="form-control" placeholder="Tuliskan tugas pokok GPM..."><?= htmlspecialchars($gpm_tugas) ?></textarea>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold" style="font-size:0.85rem;">9 Butir Fungsi Gugus Penjaminan Mutu (Pasal 31 Ayat 2)</label>
                    <small class="text-muted d-block mb-1">Tuliskan tiap butir fungsi dipisahkan baris baru (Enter). Nomor urut 1–9 akan digenerate otomatis pada tampilan profil.</small>
                    <textarea name="gpm_narasi_fungsi" rows="8" class="form-control" style="line-height:1.6;font-family:inherit;font-size:0.85rem;"><?= htmlspecialchars($gpm_fungsi) ?></textarea>
                </div>

                <div class="text-end">
                    <button type="submit" class="btn btn-success fw-bold px-4 py-2" style="border-radius:8px;">
                        <i class="bi bi-save me-1"></i> Simpan Perubahan Narasi GPM
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
<?php endif; ?>

<div class="admin-table-wrap">
    <div class="admin-table-topbar">
        <div class="admin-table-title">
            Daftar <?= $kat === 'gpm' ? 'Personel Gugus Penjaminan Mutu (GPM) Fakultas' : 'Personel Tim LPM UNIKA' ?> (<?= count($tim) ?>)
        </div>
    </div>

    <?php if (empty($tim)): ?>
    <div style="padding:3rem;text-align:center;color:var(--text-muted);">
        Belum ada data personel pada kategori ini. <a href="tim-form.php?kategori=<?= $kat ?>" class="text-purple font-weight-600">Tambah sekarang</a>
    </div>
    <?php else: ?>
    <div style="overflow-x:auto;">
        <table class="admin-table">
            <thead>
                <tr>
                    <th width="60">Urutan</th>
                    <th width="80">Foto</th>
                    <th>Nama Personel</th>
                    <th>Jabatan / Fakultas</th>
                    <th>Bidang Tugas</th>
                    <th width="110">Level</th>
                    <th width="130">Aksi</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($tim as $t): ?>
                <tr>
                    <td style="font-weight:700;font-size:1rem;color:var(--navy);text-align:center;"><?= (int)$t['urutan'] ?></td>
                    <td>
                        <?php 
                        $f_url = getTimFotoUrl($t['foto'] ?? '');
                        if ($f_url): 
                        ?>
                            <img src="<?= htmlspecialchars($f_url) ?>" alt="" style="width:48px;height:48px;border-radius:50%;object-fit:cover;border:2px solid var(--border);">
                        <?php else: ?>
                            <div style="width:48px;height:48px;border-radius:50%;background:var(--purple-glow);display:flex;align-items:center;justify-content:center;">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--purple)" width="24" height="24">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                                </svg>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:700;color:var(--navy);font-size:0.92rem;"><?= e($t['nama']) ?></div>
                    </td>
                    <td>
                        <div style="font-weight:600;color:var(--purple);font-size:0.85rem;"><?= e($t['jabatan']) ?></div>
                    </td>
                    <td>
                        <div style="font-size:0.8rem;color:var(--text-muted);"><?= e($t['bidang'] ?: '-') ?></div>
                    </td>
                    <td>
                        <span class="card-category-badge" style="text-transform:capitalize;">
                            <?= e($t['level'] ?: 'koordinator') ?>
                        </span>
                    </td>
                    <td>
                        <div style="display:flex;gap:0.4rem;">
                            <a href="tim-form.php?id=<?= $t['id'] ?>" class="btn-action btn-edit">Edit</a>
                            <a href="tim-list.php?delete=<?= $t['id'] ?>&kat=<?= $kat ?>" class="btn-action btn-delete" onclick="return confirm('Hapus personel ini?')">Hapus</a>
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
