<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Berita & Kegiatan';
$meta_desc  = 'Informasi terkini seputar kegiatan, program, dan pencapaian Lembaga Penjaminan Mutu SCU.';

$db = getDB();

// 1. Filter Tipe
$tipe_filter  = trim($_GET['tipe'] ?? '');
$allowed_tipe = ['Berita', 'Kegiatan LPM', 'Artikel Mutu', 'Sosialisasi', 'Penghargaan'];
if (!in_array($tipe_filter, $allowed_tipe)) $tipe_filter = '';

// 2. Filter Tahun Akademik
$ta_filter = trim($_GET['ta'] ?? '');
$daftar_ta = getDaftarTahunAkademikBerita();
if ($ta_filter && !in_array($ta_filter, $daftar_ta)) {
    if (!getTahunAkademikDateRange($ta_filter)) {
        $ta_filter = '';
    }
}

// 3. Search Query
$search = trim($_GET['q'] ?? '');

// 4. Pagination Setup
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 9; // 3x3 grid per page

$where_clauses = [];
$params = [];

if ($tipe_filter) {
    $where_clauses[] = "b.tipe = ?";
    $params[] = $tipe_filter;
}

if ($ta_filter) {
    $range = getTahunAkademikDateRange($ta_filter);
    if ($range) {
        $where_clauses[] = "DATE(COALESCE(b.tanggal_publikasi, b.created_at)) BETWEEN ? AND ?";
        $params[] = $range['start'];
        $params[] = $range['end'];
    }
}

if ($search) {
    $where_clauses[] = "(b.judul LIKE ? OR b.konten LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Count total matching records
$count_stmt = $db->prepare("SELECT COUNT(*) FROM berita b $where_sql");
$count_stmt->execute($params);
$total_records = (int)$count_stmt->fetchColumn();

$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

// Fetch current page records
$sql = "SELECT b.*, 
        (SELECT COUNT(*) FROM berita_gambar bg WHERE bg.berita_id = b.id) AS total_extra_gambar
        FROM berita b
        $where_sql
        ORDER BY COALESCE(b.tanggal_publikasi, b.created_at) DESC, b.id DESC
        LIMIT $per_page OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$all_berita = $stmt->fetchAll();

// URL Helper for Pagination & Filters
function buildBeritaUrl($new_params = []) {
    $current = [
        'ta'   => $_GET['ta'] ?? '',
        'tipe' => $_GET['tipe'] ?? '',
        'q'    => $_GET['q'] ?? '',
        'page' => $_GET['page'] ?? 1
    ];
    $merged = array_merge($current, $new_params);
    $cleaned = [];
    foreach ($merged as $k => $v) {
        if ($v !== '' && $v !== null && !($k === 'page' && (int)$v <= 1)) {
            $cleaned[$k] = $v;
        }
    }
    return 'berita.php' . (!empty($cleaned) ? '?' . http_build_query($cleaned) : '');
}

// Render HTML for Cards Grid
function renderBeritaCardsHtml($berita_items) {
    if (empty($berita_items)) return '';
    ob_start();
    foreach ($berita_items as $b): 
        $tgl_item   = $b['tanggal_publikasi'] ?: $b['created_at'];
        $item_ta    = getTahunAkademik($tgl_item);
        $total_foto = ($b['gambar'] ? 1 : 0) + (int)$b['total_extra_gambar'];
    ?>
    <div class="col-md-6 col-lg-4">
        <a href="berita-detail.php?slug=<?= e($b['slug']) ?>" class="card-lpm d-block text-decoration-none" style="height:100%;">
            <div class="card-img-wrap position-relative">
                <?php if ($b['gambar'] && file_exists(__DIR__ . '/uploads/berita/' . $b['gambar'])): ?>
                    <img src="<?= SITE_URL ?>/uploads/berita/<?= e($b['gambar']) ?>" alt="<?= e($b['judul']) ?>" loading="lazy">
                <?php else: ?>
                    <div style="width:100%;height:200px;background:linear-gradient(135deg,var(--navy-mid),var(--purple-dark));display:flex;align-items:center;justify-content:center;">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="rgba(255,255,255,0.3)" width="48" height="48">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                        </svg>
                    </div>
                <?php endif; ?>

                <?php if ($total_foto > 1): ?>
                <div style="position:absolute;top:12px;right:12px;background:rgba(17,24,39,0.75);backdrop-filter:blur(4px);color:#fff;font-size:0.72rem;font-weight:700;padding:4px 8px;border-radius:20px;display:flex;align-items:center;gap:4px;box-shadow:0 2px 6px rgba(0,0,0,0.2);">
                    <i class="bi bi-images"></i> <?= $total_foto ?> Foto
                </div>
                <?php endif; ?>
            </div>

            <div class="card-body-lpm">
                <div class="card-meta flex-wrap gap-2 align-items-center">
                    <span class="card-category-badge" style="white-space:nowrap;display:inline-block;"><?= e($b['tipe'] ?: 'Berita') ?></span>
                    <?php if ($item_ta): ?>
                    <span class="badge" style="background:#EDE9FE;color:#6D28D9;font-weight:700;font-size:0.72rem;padding:0.25rem 0.5rem;border-radius:4px;">
                        TA <?= e($item_ta) ?>
                    </span>
                    <?php endif; ?>
                    <span style="font-size:0.78rem;color:var(--text-muted);margin-left:auto;">
                        <?= formatTanggal($tgl_item) ?>
                    </span>
                </div>
                <div class="card-title-lpm"><?= e($b['judul']) ?></div>
                <div class="card-desc-lpm"><?= truncate($b['konten'], 120) ?></div>
                <span class="card-link">
                    Baca Selengkapnya
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" />
                    </svg>
                </span>
            </div>
        </a>
    </div>
    <?php endforeach;
    return ob_get_clean();
}

// Render HTML for Pagination Bar
function renderBeritaPaginationHtml($cur_page, $total_p) {
    if ($total_p <= 1) return '';
    ob_start();
    ?>
    <div class="pagination-lpm" style="margin:0;gap:4px;">
        <?php if ($cur_page > 1): ?>
            <button type="button" class="page-btn" data-page="<?= $cur_page - 1 ?>" title="Halaman Sebelumnya">&laquo;</button>
        <?php endif; ?>

        <?php
        $start_p = max(1, $cur_page - 2);
        $end_p   = min($total_p, $cur_page + 2);
        if ($start_p > 1) {
            echo '<button type="button" class="page-btn" data-page="1">1</button>';
            if ($start_p > 2) echo '<span class="px-2 align-self-center text-muted" style="font-weight:bold;">...</span>';
        }
        for ($p = $start_p; $p <= $end_p; $p++) {
            $active_cls = ($p === $cur_page) ? 'active' : '';
            echo '<button type="button" class="page-btn ' . $active_cls . '" data-page="' . $p . '">' . $p . '</button>';
        }
        if ($end_p < $total_p) {
            if ($end_p < $total_p - 1) echo '<span class="px-2 align-self-center text-muted" style="font-weight:bold;">...</span>';
            echo '<button type="button" class="page-btn" data-page="' . $total_p . '">' . $total_p . '</button>';
        }
        ?>

        <?php if ($cur_page < $total_p): ?>
            <button type="button" class="page-btn" data-page="<?= $cur_page + 1 ?>" title="Halaman Selanjutnya">&raquo;</button>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Filter description tags
$filter_descs = [];
if ($search) $filter_descs[] = 'Cari "' . e($search) . '"';
if ($ta_filter) $filter_descs[] = 'TA ' . e($ta_filter);
if ($tipe_filter) $filter_descs[] = e($tipe_filter);

// Handle AJAX Request (Zero latency, Seamless Live Swapping)
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'total_records'   => $total_records,
        'total_pages'     => $total_pages,
        'page'            => $page,
        'offset'          => $offset,
        'per_page'        => $per_page,
        'grid_html'       => renderBeritaCardsHtml($all_berita),
        'pagination_html' => renderBeritaPaginationHtml($page, $total_pages),
        'filter_descs'    => $filter_descs
    ]);
    exit;
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
/* Modern Filter Bar Styles */
.filter-card-pro {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 18px;
    box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.05);
    padding: 1.5rem 1.75rem;
    margin-bottom: 2.5rem;
}

.filter-row {
    display: flex;
    align-items: center;
    gap: 1.25rem;
}

.filter-row:not(:last-child) {
    padding-bottom: 1.1rem;
    margin-bottom: 1.1rem;
    border-bottom: 1px solid #F1F5F9;
}

.filter-label {
    font-family: var(--font-heading);
    font-size: 0.86rem;
    font-weight: 700;
    color: var(--navy);
    display: inline-flex;
    align-items: center;
    gap: 8px;
    min-width: 145px;
    flex-shrink: 0;
}

.filter-options {
    display: flex;
    flex-wrap: wrap;
    align-items: center;
    gap: 0.5rem;
    flex-grow: 1;
}

/* Tahun Akademik Buttons */
.btn-ta-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    color: #475569;
    font-size: 0.83rem;
    font-weight: 600;
    padding: 0.42rem 1.1rem;
    border-radius: 50px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
}

.btn-ta-pill:hover {
    background: #EDE9FE;
    border-color: #C4B5FD;
    color: #6D28D9;
    transform: translateY(-1px);
}

.btn-ta-pill.active {
    background: linear-gradient(135deg, #7C3AED 0%, #5B21B6 100%) !important;
    border-color: #5B21B6 !important;
    color: #FFFFFF !important;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(124, 58, 237, 0.35);
    transform: translateY(-1px);
}

/* Kategori Buttons */
.btn-cat-pill {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    color: #475569;
    font-size: 0.83rem;
    font-weight: 600;
    padding: 0.42rem 1.1rem;
    border-radius: 50px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
}

.btn-cat-pill:hover {
    background: #EFF6FF;
    border-color: #93C5FD;
    color: #1D4ED8;
    transform: translateY(-1px);
}

.btn-cat-pill.active {
    background: linear-gradient(135deg, #1E3A8A 0%, #0F172A 100%) !important;
    border-color: #0F172A !important;
    color: #FFFFFF !important;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.35);
    transform: translateY(-1px);
}

.btn-reset-filter {
    background: transparent;
    border: 1px solid #CBD5E1;
    color: #64748B;
    font-size: 0.78rem;
    font-weight: 600;
    padding: 0.35rem 0.85rem;
    border-radius: 50px;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 5px;
    transition: all 0.2s;
    cursor: pointer;
}

.btn-reset-filter:hover {
    background: #FEE2E2;
    border-color: #FCA5A5;
    color: #DC2626;
}

.page-btn {
    text-decoration: none !important;
    user-select: none;
}

#beritaGrid {
    transition: opacity 0.15s ease-in-out;
}
</style>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Publikasi &amp; Dokumentasi
        </div>
        <h1 class="page-banner-title">Berita &amp; Kegiatan</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Berita &amp; Kegiatan</span>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/berita-sections.php';

$params = [
    'search'        => $search,
    'ta_filter'     => $ta_filter,
    'tipe_filter'   => $tipe_filter,
    'daftar_ta'     => $daftar_ta,
    'allowed_tipe'  => $allowed_tipe,
    'all_berita'    => $all_berita,
    'total_records' => $total_records,
    'offset'        => $offset,
    'per_page'      => $per_page,
    'page'          => $page,
    'total_pages'   => $total_pages,
    'filter_descs'  => $filter_descs
];

// Ambil susunan seksi dari Visual Page Builder
$berita_blocks = null;
try {
    $stmt_b = $db->query("SELECT blocks_json FROM pages WHERE slug = 'berita'");
    $row_b = $stmt_b->fetch();
    if (!empty($row_b['blocks_json'])) {
        $berita_blocks = json_decode($row_b['blocks_json'], true);
    }
} catch (Exception $e) {}

if (!empty($berita_blocks) && is_array($berita_blocks)) {
    foreach ($berita_blocks as $block) {
        if (isset($block['is_visible']) && !$block['is_visible']) continue;
        renderBeritaSection($block['type'], $block, false, $params);
    }
} else {
    // Alur Default 2 Seksi Berita & Kegiatan
    renderBeritaSection('berita_highlight', [], false, $params);
    renderBeritaSection('berita_grid', [], false, $params);
}
?>

<!-- Seamless Client-Side Dynamic Engine with In-Memory Caching (Zero Stutter / Zero Latency) -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentTA     = "<?= e($ta_filter) ?>";
    let currentTipe   = "<?= e($tipe_filter) ?>";
    let currentSearch = "<?= e($search) ?>";
    let currentPage   = <?= $page ?>;
    let debounceTimer = null;
    const cache = new Map();

    const searchInput    = document.getElementById('searchJudulInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const taButtons      = document.querySelectorAll('.btn-ta-pill');
    const catButtons     = document.querySelectorAll('.btn-cat-pill');
    const resetBtn       = document.getElementById('resetFilterBtn');
    const emptyResetBtn  = document.getElementById('btnEmptyReset');
    const grid           = document.getElementById('beritaGrid');
    const emptyState     = document.getElementById('filterEmptyState');
    const paginationWrap = document.getElementById('paginationWrap');
    const countInfo      = document.getElementById('countInfo');

    function fetchBerita(page = 1, showFade = true) {
        currentPage = page;

        // Build clean URL
        const url = new URL(window.location.origin + window.location.pathname);
        if (currentTA) url.searchParams.set('ta', currentTA);
        if (currentTipe) url.searchParams.set('tipe', currentTipe);
        if (currentSearch) url.searchParams.set('q', currentSearch);
        if (currentPage > 1) url.searchParams.set('page', currentPage);

        // Seamless URL update without page reload
        window.history.replaceState({}, '', url.toString());

        const ajaxUrl = new URL(url.toString());
        ajaxUrl.searchParams.set('ajax', '1');
        const cacheKey = ajaxUrl.toString();

        const applyData = (data) => {
            if (data.total_records > 0) {
                grid.style.display = 'flex';
                grid.innerHTML = data.grid_html;
                grid.style.opacity = '1';
                emptyState.style.display = 'none';
            } else {
                grid.style.display = 'none';
                emptyState.style.display = 'block';
            }

            if (paginationWrap) {
                paginationWrap.innerHTML = data.pagination_html;
                paginationWrap.style.display = (data.total_pages > 1) ? 'flex' : 'none';
            }

            if (countInfo) {
                let info = '';
                if (data.total_records > 0) {
                    info = `Menampilkan <strong>${data.offset + 1}–${Math.min(data.offset + data.per_page, data.total_records)}</strong> dari <strong style="color:var(--navy);">${data.total_records}</strong> kegiatan`;
                } else {
                    info = `Ditemukan <strong style="color:var(--navy);">0</strong> kegiatan`;
                }
                if (data.filter_descs && data.filter_descs.length > 0) {
                    info += ` <span style="color:var(--purple);font-weight:600;">(${data.filter_descs.join(' • ')})</span>`;
                }
                countInfo.innerHTML = info;
            }

            const hasActiveFilter = Boolean(currentTA || currentTipe || currentSearch);
            if (resetBtn) resetBtn.style.display = hasActiveFilter ? 'inline-flex' : 'none';
            if (clearSearchBtn) clearSearchBtn.style.display = currentSearch ? 'block' : 'none';
        };

        // If in cache, render with 0.0ms instant speed!
        if (cache.has(cacheKey)) {
            applyData(cache.get(cacheKey));
            return;
        }

        if (grid && showFade) grid.style.opacity = '0.35';

        fetch(ajaxUrl.toString())
            .then(res => res.json())
            .then(data => {
                cache.set(cacheKey, data);
                applyData(data);
            })
            .catch(err => {
                if (grid) grid.style.opacity = '1';
                console.error(err);
            });
    }

    // Real-Time Search as user types (No click required, instant live typing!)
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            currentSearch = this.value.trim();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchBerita(1, false);
            }, 160);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                currentSearch = '';
                fetchBerita(1, false);
            }
        });
    }

    // Clear search button
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            currentSearch = '';
            fetchBerita(1, false);
        });
    }

    // TA Buttons click (Seamless switch!)
    taButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            currentTA = this.getAttribute('data-val') || '';
            taButtons.forEach(b => b.classList.toggle('active', (b.getAttribute('data-val') || '') === currentTA));
            fetchBerita(1, true);
        });
    });

    // Kategori Buttons click (Seamless switch!)
    catButtons.forEach(btn => {
        btn.addEventListener('click', function(e) {
            e.preventDefault();
            currentTipe = this.getAttribute('data-val') || '';
            catButtons.forEach(b => b.classList.toggle('active', (b.getAttribute('data-val') || '') === currentTipe));
            fetchBerita(1, true);
        });
    });

    // Pagination Buttons click (Seamless page switch!)
    if (paginationWrap) {
        paginationWrap.addEventListener('click', function(e) {
            const btn = e.target.closest('.page-btn');
            if (!btn) return;
            e.preventDefault();
            const targetPage = parseInt(btn.getAttribute('data-page'));
            if (targetPage && targetPage !== currentPage) {
                fetchBerita(targetPage, true);
                const section = document.getElementById('beritaSection');
                if (section) {
                    const topPos = section.getBoundingClientRect().top + window.scrollY - 80;
                    if (window.scrollY > topPos) {
                        window.scrollTo({ top: topPos, behavior: 'smooth' });
                    }
                }
            }
        });
    }

    // Reset all filters
    function resetAll() {
        currentTA = '';
        currentTipe = '';
        currentSearch = '';
        if (searchInput) searchInput.value = '';
        taButtons.forEach(b => b.classList.toggle('active', (b.getAttribute('data-val') || '') === ''));
        catButtons.forEach(b => b.classList.toggle('active', (b.getAttribute('data-val') || '') === ''));
        fetchBerita(1, true);
    }

    if (resetBtn) resetBtn.addEventListener('click', resetAll);
    if (emptyResetBtn) emptyResetBtn.addEventListener('click', resetAll);
});
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
