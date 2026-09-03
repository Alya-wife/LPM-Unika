<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Pusat Dokumen & Download Center';
$meta_desc  = 'Pusat unduhan dokumen resmi LPM UNIKA: Regulasi, Panduan, Instrumen, SOP, Kebijakan, Manual, dan Formulir Mutu.';

$db = getDB();

$kategori_filter = trim($_GET['kategori'] ?? '');
$search          = trim($_GET['q'] ?? '');

// Ambil kategori dokumen unik
$kategori_list = $db->query("SELECT nama_kategori FROM kategori_dokumen ORDER BY id ASC")->fetchAll(PDO::FETCH_COLUMN);

// Add Buletin JAMUS to dropdown if not already there
if (!in_array('Buletin JAMUS', $kategori_list)) {
    $kategori_list[] = 'Buletin JAMUS';
}

$is_buletin_kat = ($kategori_filter === 'Buletin JAMUS');

if ($is_buletin_kat) {
    // Show only buletin records
    $sql = "SELECT id, judul AS nama_dokumen, 'Buletin JAMUS' AS kategori,
                   file_path, 'buletin' AS _source, created_at
            FROM buletin WHERE is_aktif = 1";
    $params = [];
    if ($search) {
        $sql .= " AND judul LIKE ?";
        $params[] = '%' . $search . '%';
    }
    $sql .= " ORDER BY tanggal_terbit DESC, id DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $dokumen_list = $stmt->fetchAll();
} else {
    // Normal dokumen query
    $sql = "SELECT id, nama_dokumen, kategori, file_path, 'dokumen' AS _source, created_at
            FROM dokumen WHERE 1=1";
    $params = [];
    if ($kategori_filter) {
        $sql .= " AND (kategori = ? OR kategori LIKE ?)";
        $params[] = $kategori_filter;
        $params[] = '%' . $kategori_filter . '%';
    }
    if ($search) {
        $sql .= " AND nama_dokumen LIKE ?";
        $params[] = '%' . $search . '%';
    }
    $sql .= " ORDER BY created_at DESC";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);
    $dokumen_list = $stmt->fetchAll();
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Pusat Unduhan
        </div>
        <h1 class="page-banner-title">Pusat Dokumen &amp; Download Center</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Dokumen</span>
        </div>
    </div>
</div>

<section class="py-5" style="background:var(--bg-main);">
    <div class="container">
        <!-- Drive Notification Banner -->
        <div class="p-4 mb-4" style="background:linear-gradient(135deg, var(--navy), var(--navy-mid));border-radius:var(--radius-lg);color:#fff;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;box-shadow:var(--shadow-sm);">
            <div>
                <h5 style="font-family:var(--font-heading);font-weight:700;color:#fff;margin-bottom:0.25rem;">
                    Pusat Dokumen &amp; Regulasi LPM UNIKA
                </h5>
                <p style="font-size:0.85rem;color:rgba(255,255,255,0.75);margin:0;">
                    Unduh dokumen SPMI, panduan akreditasi, instrumen AMI, dan berkas resmi lainnya langsung dari sistem.
                </p>
            </div>
        </div>

        <!-- Search and Filter Bar -->
        <div class="card-lpm p-4 mb-4" style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-lg);">
            <form method="GET" action="dokumen.php" class="row g-3 align-items-center">
                <div class="col-md-7">
                    <div style="position:relative;">
                        <input type="text" name="q" class="form-control" style="border:1.5px solid var(--border);padding:0.7rem 1rem 0.7rem 2.75rem;" placeholder="Cari nama dokumen, regulasi, panduan..." value="<?= e($search) ?>">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="var(--text-muted)" width="18" height="18" style="position:absolute;left:14px;top:50%;transform:translateY(-50%);">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                        </svg>
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="kategori" class="form-select" style="border:1.5px solid var(--border);padding:0.7rem 1rem;">
                        <option value="">-- Semua Kategori --</option>
                        <?php foreach ($kategori_list as $kat): ?>
                        <option value="<?= e($kat) ?>" <?= $kategori_filter === $kat ? 'selected' : '' ?>><?= e($kat) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn-hero-primary w-100" style="padding:0.7rem 1rem;justify-content:center;border:none;">
                        Cari Dokumen
                    </button>
                </div>
            </form>
        </div>

        <!-- Table of Documents -->
        <div class="doc-table-wrap">
            <div class="doc-table-header d-flex justify-content-between align-items-center">
                <span class="doc-table-header-title">
                    Daftar Dokumen <?= $kategori_filter ? '– ' . e($kategori_filter) : '' ?>
                </span>
                <span style="font-size:0.8rem;color:rgba(255,255,255,0.7);"><?= count($dokumen_list) ?> Dokumen Ditemukan</span>
            </div>

            <?php if (empty($dokumen_list)): ?>
            <div class="text-center py-5" style="background:#fff;">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--text-muted)" width="48" height="48" style="opacity:0.3;margin-bottom:1rem;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m0 12.75h7.5m-7.5 3H12M10.5 2.25H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                </svg>
                <h5 style="color:var(--navy);font-weight:700;">Belum Ada Dokumen</h5>
                <p style="color:var(--text-muted);font-size:0.875rem;max-width:450px;margin:0 auto 1.5rem;">
                    Dokumen untuk kategori ini sedang disiapkan oleh Administrator LPM UNIKA.
                </p>
                <a href="<?= SITE_URL ?>/layanan.php" class="btn-hero-primary" style="padding:0.6rem 1.4rem;font-size:0.85rem;display:inline-flex;">
                    Hubungi Kami &rarr;
                </a>
            </div>
            <?php else: ?>
            <table class="doc-table">
                <thead>
                    <tr>
                        <th width="50">#</th>
                        <th>Nama Dokumen &amp; Berkas</th>
                        <th width="160">Kategori</th>
                        <th width="140">Tanggal Upload</th>
                        <th width="120">Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($dokumen_list as $i => $d):
                        $source    = $d['_source'] ?? 'dokumen';
                        $file_dir  = $source === 'buletin' ? 'buletin' : 'dokumen';
                        $file_full = __DIR__ . '/uploads/' . $file_dir . '/' . $d['file_path'];
                        $file_url  = SITE_URL . '/uploads/' . $file_dir . '/' . e($d['file_path']);
                        $ext       = strtolower(pathinfo($d['file_path'], PATHINFO_EXTENSION));
                        $is_pdf    = ($ext === 'pdf');
                        $has_file  = $d['file_path'] && file_exists($file_full);
                        $safe_title = addslashes(htmlspecialchars($d['nama_dokumen'], ENT_QUOTES));
                    ?>
                    <tr>
                        <td style="color:var(--text-muted);font-size:0.85rem;text-align:center;"><?= $i + 1 ?></td>
                        <td>
                            <?php if ($has_file && $is_pdf): ?>
                            <a href="javascript:void(0)" onclick="openPdfViewer('<?= $file_url ?>', '<?= $safe_title ?>')" style="font-weight:600;color:var(--navy);text-decoration:none;cursor:pointer;" class="doc-title-link" title="Klik untuk membuka dokumen PDF">
                                <?= e($d['nama_dokumen']) ?>
                            </a>
                            <?php elseif ($has_file): ?>
                            <a href="<?= $file_url ?>" download style="font-weight:600;color:var(--navy);text-decoration:none;">
                                <?= e($d['nama_dokumen']) ?>
                            </a>
                            <?php else: ?>
                            <div style="font-weight:600;color:var(--navy);font-size:0.9rem;"><?= e($d['nama_dokumen']) ?></div>
                            <?php endif; ?>

                            <?php if ($source === 'buletin'): ?>
                            <div style="font-size:0.75rem;color:var(--purple);font-weight:600;margin-top:2px;">📚 Buletin JAMUS</div>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="card-category-badge"><?= e($d['kategori']) ?></span>
                        </td>
                        <td style="font-size:0.8rem;color:var(--text-muted);"><?= formatTanggal($d['created_at']) ?></td>
                        <td>
                            <?php if ($has_file): ?>
                                <?php if ($is_pdf): ?>
                                <button type="button" class="btn-download" onclick="openPdfViewer('<?= $file_url ?>', '<?= $safe_title ?>')" style="border:none;background:rgba(123,31,162,0.1);color:var(--purple);font-weight:700;cursor:pointer;" title="Buka dan baca PDF">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                                    </svg>
                                    Buka PDF
                                </button>
                                <?php else: ?>
                                <a href="<?= $file_url ?>" class="btn-download" download>
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" />
                                    </svg>
                                    Unduh
                                </a>
                                <?php endif; ?>
                            <?php else: ?>
                            <span style="font-size:0.75rem;color:var(--text-muted);">File tidak tersedia</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
