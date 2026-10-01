<?php
require_once __DIR__ . '/includes/auth.php';
$admin_page_title = 'Manajemen Berita & Kegiatan';
$db = getDB();

// Handle delete
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $id = (int)$_GET['delete'];
    
    // 1. Delete gambar utama jika ada
    $row = $db->prepare("SELECT gambar FROM berita WHERE id = ?");
    $row->execute([$id]);
    $row = $row->fetch();
    if ($row && $row['gambar'] && file_exists(__DIR__ . '/../uploads/berita/' . $row['gambar'])) {
        @unlink(__DIR__ . '/../uploads/berita/' . $row['gambar']);
    }

    // 2. Delete gambar-gambar slider jika ada
    $extra_imgs = $db->prepare("SELECT gambar FROM berita_gambar WHERE berita_id = ?");
    $extra_imgs->execute([$id]);
    while ($img_row = $extra_imgs->fetch()) {
        if ($img_row['gambar'] && file_exists(__DIR__ . '/../uploads/berita/' . $img_row['gambar'])) {
            @unlink(__DIR__ . '/../uploads/berita/' . $img_row['gambar']);
        }
    }
    $db->prepare("DELETE FROM berita_gambar WHERE berita_id = ?")->execute([$id]);

    // 3. Delete berita
    $db->prepare("DELETE FROM berita WHERE id = ?")->execute([$id]);
    $_SESSION['flash'] = 'Berita/kegiatan beserta seluruh fotonya berhasil dihapus.';
    redirect(SITE_URL . '/admin/berita-list.php');
}

// Filter Tahun Akademik, Tipe, & Search
$ta_filter   = trim($_GET['ta'] ?? '');
$tipe_filter = trim($_GET['tipe'] ?? '');
$search      = trim($_GET['q'] ?? '');

$page        = max(1, (int)($_GET['page'] ?? 1));
$per_page    = 15;

$daftar_ta = getDaftarTahunAkademikBerita();

// Build query
$where_clauses = [];
$params = [];

if ($ta_filter) {
    $range = getTahunAkademikDateRange($ta_filter);
    if ($range) {
        $where_clauses[] = "DATE(COALESCE(tanggal_publikasi, created_at)) BETWEEN ? AND ?";
        $params[] = $range['start'];
        $params[] = $range['end'];
    }
}

if ($tipe_filter) {
    $where_clauses[] = "tipe = ?";
    $params[] = $tipe_filter;
}

if ($search) {
    $where_clauses[] = "(judul LIKE ? OR konten LIKE ?)";
    $params[] = '%' . $search . '%';
    $params[] = '%' . $search . '%';
}

$where_sql = !empty($where_clauses) ? ' WHERE ' . implode(' AND ', $where_clauses) : '';

// Count total
$count_stmt = $db->prepare("SELECT COUNT(*) FROM berita $where_sql");
$count_stmt->execute($params);
$total_records = (int)$count_stmt->fetchColumn();

$total_pages = max(1, (int)ceil($total_records / $per_page));
if ($page > $total_pages) $page = $total_pages;
$offset = ($page - 1) * $per_page;

$sql = "SELECT b.*, 
        (SELECT COUNT(*) FROM berita_gambar bg WHERE bg.berita_id = b.id) AS total_slider_extra
        FROM berita b
        $where_sql
        ORDER BY COALESCE(tanggal_publikasi, created_at) DESC, id DESC
        LIMIT $per_page OFFSET $offset";

$stmt = $db->prepare($sql);
$stmt->execute($params);
$berita_list = $stmt->fetchAll();

// URL Helper
function buildAdminBeritaUrl($new_params = []) {
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
    return 'berita-list.php' . (!empty($cleaned) ? '?' . http_build_query($cleaned) : '');
}

// Render Table Rows HTML
function renderAdminBeritaRowsHtml($list, $offset) {
    if (empty($list)) return '';
    ob_start();
    foreach ($list as $i => $b): 
        $tgl_item = $b['tanggal_publikasi'] ?: $b['created_at'];
        $item_ta  = getTahunAkademik($tgl_item);
        $total_imgs = ($b['gambar'] ? 1 : 0) + (int)$b['total_slider_extra'];
    ?>
    <tr>
        <td style="color:var(--text-muted);font-size:0.8rem;"><?= $offset + $i + 1 ?></td>
        <td>
            <div style="font-weight:600;color:var(--navy);display:flex;align-items:center;gap:0.4rem;flex-wrap:wrap;">
                <span><?= e(mb_substr($b['judul'], 0, 55)) ?><?= mb_strlen($b['judul']) > 55 ? '...' : '' ?></span>
                <?php if (!empty($b['tampil_di_ami'])): ?>
                <span class="badge" style="background:#FEF3C7;color:#D97706;border:1px solid #FCD34D;font-size:0.68rem;padding:0.15rem 0.45rem;border-radius:4px;font-weight:700;" title="Ditampilkan di Halaman AMI">
                    <i class="bi bi-star-fill" style="font-size:0.65rem;"></i> AMI
                </span>
                <?php endif; ?>
            </div>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;"><?= truncate($b['konten'], 60) ?></div>
        </td>
        <td style="white-space:nowrap;">
            <span class="card-category-badge" style="white-space:nowrap;display:inline-block;padding:0.25rem 0.65rem;"><?= e($b['tipe'] ?: 'Berita') ?></span>
        </td>
        <td>
            <?php if ($item_ta): ?>
            <span class="badge" style="background:#EDE9FE;color:#6D28D9;font-weight:700;font-size:0.74rem;padding:0.3rem 0.6rem;border-radius:4px;">
                TA <?= e($item_ta) ?>
            </span>
            <?php else: ?>
            <span style="color:var(--text-muted);font-size:0.75rem;">-</span>
            <?php endif; ?>
        </td>
        <td>
            <div style="position:relative;display:inline-block;">
                <?php if ($b['gambar'] && file_exists(__DIR__ . '/../uploads/berita/' . $b['gambar'])): ?>
                <img src="<?= SITE_URL ?>/uploads/berita/<?= e($b['gambar']) ?>" alt="" style="width:48px;height:48px;object-fit:cover;border-radius:6px;">
                <?php else: ?>
                <div style="width:48px;height:48px;background:var(--bg-main);border-radius:6px;display:flex;align-items:center;justify-content:center;border:1px solid var(--border);">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="var(--text-light)" width="22" height="22">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.25 15.75l5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909m-18 3.75h16.5a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5H3.75A1.5 1.5 0 0 0 2.25 6v12a1.5 1.5 0 0 0 1.5 1.5Zm10.5-11.25h.008v.008h-.008V8.25Zm.375 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Z" />
                    </svg>
                </div>
                <?php endif; ?>
                <?php if ($total_imgs > 1): ?>
                <span class="badge bg-dark" style="position:absolute;bottom:-4px;right:-4px;font-size:0.65rem;padding:2px 5px;border-radius:10px;box-shadow:0 1px 2px rgba(0,0,0,0.3);" title="<?= $total_imgs ?> foto dalam slider">
                    <i class="bi bi-images"></i> <?= $total_imgs ?>
                </span>
                <?php endif; ?>
            </div>
        </td>
        <td style="font-size:0.8rem;color:var(--text-muted);"><?= formatTanggal($tgl_item) ?></td>
        <td>
            <div style="display:flex;gap:0.4rem;flex-wrap:wrap;">
                <a href="berita-form.php?id=<?= $b['id'] ?>" class="btn-action btn-edit" style="text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125M18 14v4.75A2.25 2.25 0 0 1 15.75 21H5.25A2.25 2.25 0 0 1 3 18.75V8.25A2.25 2.25 0 0 1 5.25 6H10" />
                    </svg>
                    Edit
                </a>
                <a href="<?= SITE_URL ?>/berita-detail.php?slug=<?= e($b['slug']) ?>" target="_blank" class="btn-action" style="background:#E3F2FD;color:#1565C0;text-decoration:none;">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Lihat
                </a>
                <a href="berita-list.php?delete=<?= $b['id'] ?>" class="btn-action btn-delete" style="text-decoration:none;" onclick="return confirm('Yakin hapus berita/kegiatan ini beserta seluruh fotonya?')">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                    </svg>
                    Hapus
                </a>
            </div>
        </td>
    </tr>
    <?php endforeach;
    return ob_get_clean();
}

// Render Pagination HTML
function renderAdminBeritaPaginationHtml($cur_page, $total_p) {
    if ($total_p <= 1) return '';
    ob_start();
    ?>
    <div class="pagination-lpm" style="margin:0;gap:4px;">
        <?php if ($cur_page > 1): ?>
            <button type="button" class="page-btn" data-page="<?= $cur_page - 1 ?>" style="width:32px;height:32px;font-size:0.75rem;" title="Sebelumnya">&laquo;</button>
        <?php endif; ?>

        <?php
        $start_p = max(1, $cur_page - 2);
        $end_p   = min($total_p, $cur_page + 2);
        if ($start_p > 1) {
            echo '<button type="button" class="page-btn" data-page="1" style="width:32px;height:32px;font-size:0.75rem;">1</button>';
            if ($start_p > 2) echo '<span style="align-self:center;padding:0 4px;font-size:0.75rem;color:var(--text-muted);">...</span>';
        }
        for ($p = $start_p; $p <= $end_p; $p++) {
            $active_cls = ($p === $cur_page) ? 'active' : '';
            echo '<button type="button" class="page-btn ' . $active_cls . '" data-page="' . $p . '" style="width:32px;height:32px;font-size:0.75rem;">' . $p . '</button>';
        }
        if ($end_p < $total_p) {
            if ($end_p < $total_p - 1) echo '<span style="align-self:center;padding:0 4px;font-size:0.75rem;color:var(--text-muted);">...</span>';
            echo '<button type="button" class="page-btn" data-page="' . $total_p . '" style="width:32px;height:32px;font-size:0.75rem;">' . $total_p . '</button>';
        }
        ?>

        <?php if ($cur_page < $total_p): ?>
            <button type="button" class="page-btn" data-page="<?= $cur_page + 1 ?>" style="width:32px;height:32px;font-size:0.75rem;" title="Selanjutnya">&raquo;</button>
        <?php endif; ?>
    </div>
    <?php
    return ob_get_clean();
}

// Handle AJAX Request (Instant live typing & seamless filters)
if (isset($_GET['ajax']) && $_GET['ajax'] == '1') {
    header('Content-Type: application/json');
    echo json_encode([
        'total_records'   => $total_records,
        'total_pages'     => $total_pages,
        'page'            => $page,
        'offset'          => $offset,
        'per_page'        => $per_page,
        'rows_html'       => renderAdminBeritaRowsHtml($berita_list, $offset),
        'pagination_html' => renderAdminBeritaPaginationHtml($page, $total_pages)
    ]);
    exit;
}

$flash = $_SESSION['flash'] ?? '';
unset($_SESSION['flash']);

require_once __DIR__ . '/includes/admin-header.php';
?>

<?php if ($flash): ?>
<div class="alert-lpm alert-success mb-4">
    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
        <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
    </svg>
    <?= e($flash) ?>
</div>
<?php endif; ?>

<div class="admin-table-wrap">
    <div class="admin-table-topbar flex-wrap gap-3">
        <div>
            <div class="admin-table-title" id="adminTableTitle">Daftar Berita &amp; Kegiatan (Total <?= $total_records ?>)</div>
            <div style="font-size:0.75rem;color:var(--text-muted);margin-top:2px;">
                Kelola publikasi berita, pengumuman, dan dokumentasi kegiatan LPM.
            </div>
        </div>
        <a href="berita-form.php" class="btn-add">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="18" height="18">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
            </svg>
            Tambah Berita / Kegiatan
        </a>
    </div>

    <!-- Filter Bar: Live-Typing Search, Tahun Akademik & Tipe -->
    <div style="padding:1rem 1.5rem;background:#F8FAFC;border-bottom:1px solid var(--border);">
        <div class="row g-2 align-items-center">
            <!-- Search Live Input (No Click Needed) -->
            <div class="col-md-4 col-12">
                <div class="position-relative">
                    <input type="text" id="adminSearchInput" class="form-control form-control-sm" placeholder="Ketik untuk mencari langsung..." value="<?= e($search) ?>" style="font-size:0.84rem;padding-right:2rem;" autocomplete="off">
                    <button type="button" id="adminClearSearch" class="btn btn-sm position-absolute top-50 end-0 translate-middle-y me-1 text-muted" style="border:none;background:transparent;display:<?= $search ? 'block' : 'none' ?>;padding:0.1rem 0.4rem;cursor:pointer;">
                        <i class="bi bi-x-circle-fill" style="font-size:0.9rem;color:#94A3B8;"></i>
                    </button>
                </div>
            </div>

            <!-- Filter Tahun Akademik -->
            <div class="col-auto">
                <select id="adminFilterTA" class="form-select form-select-sm" style="min-width:170px;font-size:0.82rem;">
                    <option value="">Semua Tahun Akademik</option>
                    <?php foreach ($daftar_ta as $ta_opt): ?>
                    <option value="<?= e($ta_opt) ?>" <?= $ta_filter === $ta_opt ? 'selected' : '' ?>>
                        TA <?= e($ta_opt) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- Filter Tipe -->
            <div class="col-auto">
                <select id="adminFilterTipe" class="form-select form-select-sm" style="min-width:140px;font-size:0.82rem;">
                    <option value="">Semua Tipe</option>
                    <?php
                    $tipes = ['Berita', 'Kegiatan LPM', 'Artikel Mutu', 'Sosialisasi', 'Penghargaan'];
                    foreach ($tipes as $tp):
                    ?>
                    <option value="<?= $tp ?>" <?= $tipe_filter === $tp ? 'selected' : '' ?>><?= $tp ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="col-auto" id="adminResetWrap" style="display:<?= ($ta_filter || $tipe_filter || $search) ? 'block' : 'none' ?>;">
                <button type="button" id="adminResetBtn" class="btn btn-sm btn-outline-danger" style="font-size:0.78rem;">
                    Reset Filter
                </button>
            </div>
        </div>
    </div>

    <!-- Empty State -->
    <div id="adminEmptyState" style="padding:3rem;text-align:center;color:var(--text-muted);<?= !empty($berita_list) ? 'display:none;' : '' ?>">
        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1" stroke="currentColor" width="48" height="48" style="opacity:0.2;display:block;margin:0 auto 1rem;">
            <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5h1.5m-1.5 3h1.5m-7.5 3h7.5m-7.5 3h7.5m3-9h3.375c.621 0 1.125.504 1.125 1.125V18a2.25 2.25 0 0 1-2.25 2.25M16.5 7.5V18a2.25 2.25 0 0 0 2.25 2.25M16.5 7.5V4.875c0-.621-.504-1.125-1.125-1.125H4.125C3.504 3.75 3 4.254 3 4.875V18a2.25 2.25 0 0 0 2.25 2.25h13.5M6 7.5h3v3H6v-3Z" />
        </svg>
        <span id="adminEmptyText">
            <?= ($ta_filter || $tipe_filter || $search) ? 'Tidak ada data berita/kegiatan yang sesuai filter.' : 'Belum ada berita atau kegiatan.' ?>
        </span>
    </div>

    <!-- Table -->
    <div id="adminTableContainer" style="overflow-x:auto;<?= empty($berita_list) ? 'display:none;' : '' ?>">
        <table class="admin-table mb-0">
            <thead>
                <tr>
                    <th width="40">#</th>
                    <th>Judul Berita / Kegiatan</th>
                    <th width="130">Tipe</th>
                    <th width="120">Tahun Akademik</th>
                    <th width="100">Foto / Slider</th>
                    <th width="130">Tanggal</th>
                    <th width="150">Aksi</th>
                </tr>
            </thead>
            <tbody id="adminTableBody">
                <?= renderAdminBeritaRowsHtml($berita_list, $offset) ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination Footer -->
    <div id="adminFooterWrap" style="padding:1rem 1.5rem;background:#F8FAFC;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:1rem;<?= empty($berita_list) ? 'display:none;' : '' ?>">
        <div id="adminSummaryText" style="font-size:0.8rem;color:var(--text-muted);">
            Menampilkan <strong><?= $total_records > 0 ? $offset + 1 : 0 ?>–<?= min($offset + $per_page, $total_records) ?></strong> dari <strong><?= $total_records ?></strong> total data berita/kegiatan
        </div>
        <div id="adminPaginationWrap">
            <?= renderAdminBeritaPaginationHtml($page, $total_pages) ?>
        </div>
    </div>
</div>

<!-- Seamless Live-Updating Admin Engine -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    let currentTA     = "<?= e($ta_filter) ?>";
    let currentTipe   = "<?= e($tipe_filter) ?>";
    let currentSearch = "<?= e($search) ?>";
    let currentPage   = <?= $page ?>;
    let debounceTimer = null;
    const cache = new Map();

    const searchInput      = document.getElementById('adminSearchInput');
    const clearSearchBtn   = document.getElementById('adminClearSearch');
    const filterTA         = document.getElementById('adminFilterTA');
    const filterTipe       = document.getElementById('adminFilterTipe');
    const resetWrap        = document.getElementById('adminResetWrap');
    const resetBtn         = document.getElementById('adminResetBtn');
    const tableBody        = document.getElementById('adminTableBody');
    const tableContainer   = document.getElementById('adminTableContainer');
    const emptyState       = document.getElementById('adminEmptyState');
    const emptyText        = document.getElementById('adminEmptyText');
    const tableTitle       = document.getElementById('adminTableTitle');
    const summaryText      = document.getElementById('adminSummaryText');
    const paginationWrap   = document.getElementById('adminPaginationWrap');
    const footerWrap       = document.getElementById('adminFooterWrap');

    function fetchAdminList(page = 1, showFade = true) {
        currentPage = page;

        const url = new URL(window.location.origin + window.location.pathname);
        if (currentTA) url.searchParams.set('ta', currentTA);
        if (currentTipe) url.searchParams.set('tipe', currentTipe);
        if (currentSearch) url.searchParams.set('q', currentSearch);
        if (currentPage > 1) url.searchParams.set('page', currentPage);

        window.history.replaceState({}, '', url.toString());

        const ajaxUrl = new URL(url.toString());
        ajaxUrl.searchParams.set('ajax', '1');
        const cacheKey = ajaxUrl.toString();

        const applyData = (data) => {
            tableTitle.textContent = `Daftar Berita & Kegiatan (Total ${data.total_records})`;

            if (data.total_records > 0) {
                tableContainer.style.display = 'block';
                footerWrap.style.display = 'flex';
                emptyState.style.display = 'none';

                tableBody.innerHTML = data.rows_html;
                tableBody.style.opacity = '1';

                summaryText.innerHTML = `Menampilkan <strong>${data.offset + 1}–${Math.min(data.offset + data.per_page, data.total_records)}</strong> dari <strong>${data.total_records}</strong> total data berita/kegiatan`;
                paginationWrap.innerHTML = data.pagination_html;
            } else {
                tableContainer.style.display = 'none';
                footerWrap.style.display = 'none';
                emptyState.style.display = 'block';
                emptyText.textContent = (currentTA || currentTipe || currentSearch) 
                    ? 'Tidak ada data berita/kegiatan yang sesuai filter.' 
                    : 'Belum ada berita atau kegiatan.';
            }

            const hasActiveFilter = Boolean(currentTA || currentTipe || currentSearch);
            resetWrap.style.display = hasActiveFilter ? 'block' : 'none';
            clearSearchBtn.style.display = currentSearch ? 'block' : 'none';
        };

        if (cache.has(cacheKey)) {
            applyData(cache.get(cacheKey));
            return;
        }

        if (showFade && tableBody) tableBody.style.opacity = '0.4';

        fetch(ajaxUrl.toString())
            .then(res => res.json())
            .then(data => {
                cache.set(cacheKey, data);
                applyData(data);
            })
            .catch(err => {
                if (tableBody) tableBody.style.opacity = '1';
                console.error(err);
            });
    }

    // Live search as user types (Instant, No submit click needed!)
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            currentSearch = this.value.trim();
            clearTimeout(debounceTimer);
            debounceTimer = setTimeout(() => {
                fetchAdminList(1, false);
            }, 160);
        });

        searchInput.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                this.value = '';
                currentSearch = '';
                fetchAdminList(1, false);
            }
        });
    }

    // Clear search
    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            if (searchInput) {
                searchInput.value = '';
                searchInput.focus();
            }
            currentSearch = '';
            fetchAdminList(1, false);
        });
    }

    // Filter TA change (Seamless!)
    if (filterTA) {
        filterTA.addEventListener('change', function() {
            currentTA = this.value;
            fetchAdminList(1, true);
        });
    }

    // Filter Tipe change (Seamless!)
    if (filterTipe) {
        filterTipe.addEventListener('change', function() {
            currentTipe = this.value;
            fetchAdminList(1, true);
        });
    }

    // Pagination delegation (Seamless page switch!)
    if (paginationWrap) {
        paginationWrap.addEventListener('click', function(e) {
            const btn = e.target.closest('.page-btn');
            if (!btn) return;
            e.preventDefault();
            const targetPage = parseInt(btn.getAttribute('data-page'));
            if (targetPage && targetPage !== currentPage) {
                fetchAdminList(targetPage, true);
            }
        });
    }

    // Reset filters
    if (resetBtn) {
        resetBtn.addEventListener('click', function() {
            currentTA = '';
            currentTipe = '';
            currentSearch = '';
            if (searchInput) searchInput.value = '';
            if (filterTA) filterTA.value = '';
            if (filterTipe) filterTipe.value = '';
            fetchAdminList(1, true);
        });
    }
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
