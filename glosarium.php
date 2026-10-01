<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Glosarium Penjaminan Mutu – Kamus Istilah SPMI';
$meta_desc  = 'Kamus dan terminologi resmi penjaminan mutu pendidikan tinggi: SPMI, PPEPP, AMI, KTS, RTL, RTM, SN Dikti, dan Akreditasi.';

$db = getDB();

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
require_once __DIR__ . '/includes/knowledge-sections.php';

$search_term = trim($_GET['q'] ?? '');

try {
    if ($search_term !== '') {
        $stmt_glos = $db->prepare("
            SELECT * FROM glosarium 
            WHERE is_active = 1 
              AND (istilah LIKE ? OR COALESCE(nama, istilah_lengkap, '') LIKE ? OR definisi LIKE ? OR COALESCE(kategori, sumber, '') LIKE ?) 
            ORDER BY urutan ASC, istilah ASC
        ");
        $param = '%' . $search_term . '%';
        $stmt_glos->execute([$param, $param, $param, $param]);
        $all_terms = $stmt_glos->fetchAll(PDO::FETCH_ASSOC);
    } else {
        $all_terms = $db->query("SELECT * FROM glosarium WHERE is_active = 1 ORDER BY urutan ASC, istilah ASC")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (Exception $e) {
    $all_terms = [];
}
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Kamus &amp; Terminologi Mutu
        </div>
        <h1 class="page-banner-title">Glosarium Penjaminan Mutu</h1>
        <p class="text-white-50 mt-2 mb-0" style="max-width:700px;font-size:0.95rem;line-height:1.6;">
            Panduan referensi terminologi, singkatan resmi, dan konsep teknis yang digunakan dalam penjaminan mutu pendidikan tinggi.
        </p>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <a href="<?= SITE_URL ?>/faq.php">FAQ</a>
            <span>/</span>
            <span class="current">Glosarium</span>
        </div>
    </div>
</div>

<section class="py-5" style="background:var(--bg-main);">
    <div class="container">
        <!-- Search Toolbar -->
        <div class="card-lpm p-3 p-md-4 mb-4" style="background:#ffffff;border:1px solid var(--border);border-radius:var(--radius-md);">
            <form method="GET" action="glosarium.php" class="row g-3 align-items-center">
                <div class="col-md-9">
                    <div class="input-group">
                        <span class="input-group-text bg-white border-end-0" style="border-color:var(--border);">
                            <i class="bi bi-search text-muted"></i>
                        </span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Ketik istilah atau definisi (misal: SPMI, KTS, RTM)..." value="<?= htmlspecialchars($search_term) ?>" style="border-color:var(--border);font-size:0.9rem;">
                    </div>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary w-100 fw-bold" style="font-size:0.88rem;border-radius:8px;">
                        Cari Istilah
                    </button>
                    <?php if ($search_term): ?>
                    <a href="glosarium.php" class="btn btn-outline-secondary" style="border-radius:8px;font-size:0.88rem;">Reset</a>
                    <?php endif; ?>
                </div>
            </form>
        </div>

        <!-- Terminology Cards Grid -->
        <?php if (empty($all_terms)): ?>
        <div class="text-center py-5">
            <div style="font-size:3rem;color:var(--text-muted);margin-bottom:1rem;">
                <i class="bi bi-spellcheck"></i>
            </div>
            <h4 class="fw-bold" style="color:var(--navy);">Istilah tidak ditemukan</h4>
            <p class="text-muted small">Coba cari dengan kata kunci lain seperti "SPMI", "Audit", atau "Standar".</p>
        </div>
        <?php else: ?>
        <div class="row g-4">
            <?php foreach ($all_terms as $t): ?>
            <?php 
            $kategori_label = !empty($t['kategori']) ? $t['kategori'] : (!empty($t['sumber']) ? $t['sumber'] : 'Umum');
            $istilah_teks   = !empty($t['istilah']) ? $t['istilah'] : '';
            $nama_teks      = !empty($t['nama']) ? $t['nama'] : (!empty($t['istilah_lengkap']) ? $t['istilah_lengkap'] : $istilah_teks);
            $definisi_teks  = !empty($t['definisi']) ? $t['definisi'] : '';
            ?>
            <div class="col-md-6 col-lg-4">
                <div class="card-lpm p-4 h-100 d-flex flex-column" style="background:#ffffff;border:1px solid var(--border);border-left:4px solid var(--purple);border-radius:var(--radius-md);box-shadow:0 4px 15px rgba(10,25,47,0.04);transition:transform 0.2s, box-shadow 0.2s;">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span class="badge" style="background:rgba(123,31,162,0.1);color:var(--purple);font-size:0.75rem;padding:0.3rem 0.65rem;border-radius:15px;font-weight:700;">
                            <?= htmlspecialchars($kategori_label) ?>
                        </span>
                        <span style="font-family:var(--font-heading);font-weight:900;color:var(--gold);font-size:1.1rem;">
                            <?= htmlspecialchars($istilah_teks) ?>
                        </span>
                    </div>

                    <h4 style="font-family:var(--font-heading);font-weight:800;color:var(--navy);font-size:1.05rem;line-height:1.35;margin-bottom:0.6rem;">
                        <?= htmlspecialchars($nama_teks) ?>
                    </h4>

                    <p style="font-size:0.85rem;color:var(--text-muted);line-height:1.65;margin:0;flex-grow:1;">
                        <?= htmlspecialchars($definisi_teks) ?>
                    </p>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
