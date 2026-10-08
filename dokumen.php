<?php
require_once __DIR__ . '/config/database.php';
// Halaman Dokumen di-drop (tidak terpakai) - dialihkan ke SPMI (Pusat Dokumen Terpadu)
header("Location: " . SITE_URL . "/spmi.php#dokumen-spmi", true, 301);
exit;
$page_title = 'Pusat Dokumen & Database Pencarian Berkas';
$meta_desc  = 'Pusat pencarian dan basis data dokumen resmi LPM UNIKA: Regulasi, SPMI, Sertifikat & SK Akreditasi Prodi, Buletin JAMUS, dan Instrumen LAM.';

$db = getDB();

// 1. Gather all documents from all database sources across the web
$raw_docs = [];

// Sumber 1: Dokumen Mutu / SPMI (Tabel: dokumen)
$sql_dok = "SELECT id, nama_dokumen AS judul, kategori, file_path, 'dokumen' AS dir_folder, created_at FROM dokumen";
$stmt = $db->query($sql_dok);
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $raw_docs[] = [
        'id'            => 'dok_' . $r['id'],
        'judul'         => $r['judul'],
        'sumber'        => 'SPMI & Mutu Internal',
        'kategori'      => $r['kategori'] ?: 'Dokumen SPMI',
        'file_path'     => $r['file_path'],
        'dir_folder'    => 'dokumen',
        'created_at'    => $r['created_at'],
        'info_tambahan' => 'Dokumen Resmi Penjaminan Mutu Internal'
    ];
}

// Sumber 2: Akreditasi Program Studi - SK & Sertifikat (Tabel: akreditasi_prodi)
$sql_prodi = "SELECT id, fakultas, program_studi, strata, peringkat, lembaga, no_sk, file_sk, file_sertifikat, masa_berlaku, created_at FROM akreditasi_prodi";
$stmt = $db->query($sql_prodi);
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    if (!empty($r['file_sk'])) {
        $raw_docs[] = [
            'id'            => 'prodi_sk_' . $r['id'],
            'judul'         => 'SK Akreditasi ' . $r['strata'] . ' ' . $r['program_studi'],
            'sumber'        => 'Akreditasi Program Studi',
            'kategori'      => 'SK Akreditasi',
            'file_path'     => $r['file_sk'],
            'dir_folder'    => 'akreditasi_prodi',
            'created_at'    => $r['created_at'],
            'info_tambahan' => 'Peringkat: ' . $r['peringkat'] . ' • No SK: ' . ($r['no_sk'] ?: '-') . ' • Lembaga: ' . $r['lembaga']
        ];
    }
    if (!empty($r['file_sertifikat'])) {
        $raw_docs[] = [
            'id'            => 'prodi_sert_' . $r['id'],
            'judul'         => 'Sertifikat Akreditasi ' . $r['strata'] . ' ' . $r['program_studi'],
            'sumber'        => 'Akreditasi Program Studi',
            'kategori'      => 'Sertifikat Akreditasi',
            'file_path'     => $r['file_sertifikat'],
            'dir_folder'    => 'akreditasi_prodi',
            'created_at'    => $r['created_at'],
            'info_tambahan' => 'Peringkat: ' . $r['peringkat'] . ' • Masa Berlaku: ' . ($r['masa_berlaku'] ? date('d-m-Y', strtotime($r['masa_berlaku'])) : '-') . ' • ' . $r['lembaga']
        ];
    }
}

// Sumber 3: Instrumen & Panduan Lembaga Akreditasi (Tabel: lembaga_dokumen)
$sql_ld = "SELECT ld.*, la.kode AS lembaga_kode, la.nama AS lembaga_nama 
           FROM lembaga_dokumen ld 
           LEFT JOIN lembaga_akreditasi la ON ld.lembaga_id = la.id";
$stmt = $db->query($sql_ld);
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $raw_docs[] = [
        'id'            => 'ld_' . $r['id'],
        'judul'         => $r['nama_dokumen'],
        'sumber'        => 'Instrumen Lembaga Akreditasi',
        'kategori'      => 'Instrumen ' . ($r['lembaga_kode'] ?: 'Akreditasi'),
        'file_path'     => $r['file_path'],
        'dir_folder'    => 'akreditasi',
        'created_at'    => $r['created_at'],
        'info_tambahan' => 'Lembaga: ' . ($r['lembaga_nama'] ?: $r['lembaga_kode']) . ' (' . strtoupper($r['tipe_file'] ?: 'PDF') . ($r['ukuran_file'] ? ' • ' . $r['ukuran_file'] : '') . ')'
    ];
}

// Sumber 4: Buletin JAMUS (Tabel: buletin)
$sql_bul = "SELECT id, judul, edisi, periode_akademik, file_path, tanggal_terbit, created_at FROM buletin WHERE is_aktif = 1";
$stmt = $db->query($sql_bul);
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $raw_docs[] = [
        'id'            => 'bul_' . $r['id'],
        'judul'         => $r['judul'] . ($r['edisi'] ? ' (' . $r['edisi'] . ')' : ''),
        'sumber'        => 'Buletin Mutu (JAMUS)',
        'kategori'      => 'Buletin JAMUS',
        'file_path'     => $r['file_path'],
        'dir_folder'    => 'buletin',
        'created_at'    => $r['tanggal_terbit'] ?: $r['created_at'],
        'info_tambahan' => $r['periode_akademik'] ? 'Tahun Akademik: ' . $r['periode_akademik'] : 'Publikasi Penjaminan Mutu'
    ];
}

// Sumber 5: Akreditasi Institusi / LAM (Tabel: lembaga_akreditasi)
$sql_la = "SELECT id, kode, nama, file_akreditasi, masa_berlaku, created_at FROM lembaga_akreditasi WHERE file_akreditasi IS NOT NULL AND file_akreditasi != ''";
$stmt = $db->query($sql_la);
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $raw_docs[] = [
        'id'            => 'la_' . $r['id'],
        'judul'         => 'Sertifikat & SK Akreditasi ' . $r['nama'],
        'sumber'        => 'Akreditasi Institusi',
        'kategori'      => 'Akreditasi Institusi',
        'file_path'     => $r['file_akreditasi'],
        'dir_folder'    => 'akreditasi',
        'created_at'    => $r['created_at'],
        'info_tambahan' => 'Lembaga: ' . $r['kode'] . ($r['masa_berlaku'] ? ' • Berlaku: s.d. ' . date('d-m-Y', strtotime($r['masa_berlaku'])) : '')
    ];
}

// Sumber 6: Dokumen SPMI Kemendikti (Tabel: spmi_kemendikti)
$sql_kemen = "SELECT id, judul, deskripsi, file_pdf, tahun, created_at FROM spmi_kemendikti WHERE is_published = 1";
$stmt = $db->query($sql_kemen);
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $raw_docs[] = [
        'id'            => 'kemen_' . $r['id'],
        'judul'         => $r['judul'],
        'sumber'        => 'SPMI Kemendikti',
        'kategori'      => 'Regulasi Nasional',
        'file_path'     => $r['file_pdf'],
        'dir_folder'    => 'spmi_kemendikti',
        'created_at'    => $r['created_at'],
        'info_tambahan' => ($r['tahun'] ? 'Tahun Terbit: ' . $r['tahun'] : 'Pedoman SPMI Nasional')
    ];
}

// 2. Preprocess documents with verified paths and full metadata
$all_docs = [];
$daftar_sumber = [];

foreach ($raw_docs as $d) {
    $folder = $d['dir_folder'];
    $file_full = __DIR__ . '/uploads/' . $folder . '/' . $d['file_path'];

    // Fallback if file in alternate folder
    if (!file_exists($file_full) && file_exists(__DIR__ . '/uploads/akreditasi/' . $d['file_path'])) {
        $folder = 'akreditasi';
        $file_full = __DIR__ . '/uploads/akreditasi/' . $d['file_path'];
    } elseif (!file_exists($file_full) && file_exists(__DIR__ . '/uploads/akreditasi_prodi/' . $d['file_path'])) {
        $folder = 'akreditasi_prodi';
        $file_full = __DIR__ . '/uploads/akreditasi_prodi/' . $d['file_path'];
    }

    $has_file = !empty($d['file_path']) && file_exists($file_full);
    $ext = strtolower(pathinfo($d['file_path'], PATHINFO_EXTENSION));

    $d['has_file']       = $has_file;
    $d['file_url']       = SITE_URL . '/uploads/' . $folder . '/' . rawurlencode($d['file_path']);
    $d['ext']            = $ext;
    $d['is_pdf']         = ($ext === 'pdf');
    $d['formatted_date'] = !empty($d['created_at']) ? formatTanggal($d['created_at']) : '-';

    $all_docs[] = $d;

    $s = $d['sumber'];
    $daftar_sumber[$s] = ($daftar_sumber[$s] ?? 0) + 1;
}

// 3. Ambil Parameter Filter & Search dari URL
$sumber_filter   = trim($_GET['sumber'] ?? '');
$kategori_filter = trim($_GET['kategori'] ?? '');
$search          = trim($_GET['q'] ?? '');

// 4. Proses Penyaringan Awal (Server-Side Initial State)
$filtered_docs = array_filter($all_docs, function($item) use ($sumber_filter, $kategori_filter, $search) {
    if ($sumber_filter && $item['sumber'] !== $sumber_filter) {
        return false;
    }
    if ($kategori_filter && stripos($item['kategori'], $kategori_filter) === false) {
        return false;
    }
    if ($search) {
        $q = mb_strtolower($search);
        $search_space = mb_strtolower(
            $item['judul'] . ' ' . 
            $item['kategori'] . ' ' . 
            $item['sumber'] . ' ' . 
            $item['info_tambahan'] . ' ' . 
            $item['file_path']
        );
        if (mb_strpos($search_space, $q) === false) {
            return false;
        }
    }
    return true;
});

// Urutkan dokumen
usort($filtered_docs, function($a, $b) {
    $t_a = !empty($a['created_at']) ? strtotime($a['created_at']) : 0;
    $t_b = !empty($b['created_at']) ? strtotime($b['created_at']) : 0;
    if ($t_a === $t_b) return strcmp($a['judul'], $b['judul']);
    return ($t_a > $t_b) ? -1 : 1;
});

// 5. Pagination Setup
$total_records = count($filtered_docs);
$per_page      = 15;
$total_pages   = max(1, (int)ceil($total_records / $per_page));
$page          = max(1, min($total_pages, (int)($_GET['page'] ?? 1)));
$offset        = ($page - 1) * $per_page;

$paginated_docs = array_slice($filtered_docs, $offset, $per_page);

require_once __DIR__ . '/includes/dokumen-sections.php';

// Cek custom layout dari Builder DB
$saved_blocks = [];
try {
    $stmt_page = $db->prepare("SELECT blocks_json FROM pages WHERE slug = 'dokumen' OR custom_url = 'dokumen.php' OR custom_url = '/dokumen.php' LIMIT 1");
    $stmt_page->execute();
    $page_row = $stmt_page->fetch(PDO::FETCH_ASSOC);
    if (!empty($page_row['blocks_json'])) {
        $saved_blocks = json_decode($page_row['blocks_json'], true);
    }
} catch (Exception $e) {}

$default_sections = ['dokumen_header', 'dokumen_table'];
$sections_to_render = [];

if (!empty($saved_blocks) && is_array($saved_blocks)) {
    foreach ($saved_blocks as $blk) {
        if ((isset($blk['status']) && $blk['status'] === 'draft') || (isset($blk['is_visible']) && !$blk['is_visible'])) continue;
        $type = $blk['type'] ?? '';
        if ($type) {
            $sections_to_render[] = [
                'type' => $type,
                'data' => $blk['data'] ?? []
            ];
        }
    }
}

if (empty($sections_to_render)) {
    foreach ($default_sections as $sec) {
        $sections_to_render[] = ['type' => $sec, 'data' => []];
    }
}

$ctx = [
    'all_docs'        => $all_docs,
    'daftar_sumber'   => $daftar_sumber,
    'sumber_filter'   => $sumber_filter,
    'kategori_filter' => $kategori_filter,
    'search'          => $search,
    'paginated_docs'  => $paginated_docs,
    'total_records'   => $total_records,
    'total_pages'     => $total_pages,
    'page'            => $page,
    'offset'          => $offset,
    'per_page'        => $per_page
];

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<style>
/* Modern Document Search & Database Styles */
.doc-search-box {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 20px;
    box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05);
    padding: 1.75rem 2rem;
    margin-bottom: 2rem;
}

.doc-source-pills {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    margin-bottom: 1.5rem;
}

.source-pill-btn {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    background: #F8FAFC;
    border: 1.5px solid #E2E8F0;
    color: #475569;
    font-size: 0.82rem;
    font-weight: 600;
    padding: 0.45rem 1.1rem;
    border-radius: 50px;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
    user-select: none;
}

.source-pill-btn:hover {
    background: #EDE9FE;
    border-color: #C4B5FD;
    color: #6D28D9;
    transform: translateY(-1px);
}

.source-pill-btn.active {
    background: linear-gradient(135deg, #1E3A8A 0%, #0F172A 100%) !important;
    border-color: #0F172A !important;
    color: #FFFFFF !important;
    font-weight: 700;
    box-shadow: 0 4px 12px rgba(15, 23, 42, 0.3);
    transform: translateY(-1px);
}

.source-pill-btn .pill-count {
    background: rgba(255, 255, 255, 0.25);
    color: inherit;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 1px 7px;
    border-radius: 12px;
}

.source-pill-btn:not(.active) .pill-count {
    background: #E2E8F0;
    color: #64748B;
}

.doc-table-card {
    background: #ffffff;
    border: 1px solid #E2E8F0;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 4px 20px -4px rgba(0, 0, 0, 0.05);
}

.doc-badge-source {
    display: inline-block;
    font-size: 0.72rem;
    font-weight: 700;
    padding: 0.2rem 0.6rem;
    border-radius: 6px;
    background: #EFF6FF;
    color: #1D4ED8;
    border: 1px solid #DBEAFE;
}

.doc-badge-source.spmi {
    background: #EDE9FE;
    color: #6D28D9;
    border-color: #DDD6FE;
}

.doc-badge-source.akreditasi {
    background: #ECFDF5;
    color: #047857;
    border-color: #A7F3D0;
}

.doc-badge-source.buletin {
    background: #FFFBEB;
    color: #B45309;
    border-color: #FDE68A;
}

.doc-badge-source.instrumen {
    background: #F1F5F9;
    color: #334155;
    border-color: #CBD5E1;
}

.file-icon-badge {
    width: 38px;
    height: 38px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-weight: 800;
    font-size: 0.7rem;
    letter-spacing: 0.5px;
    flex-shrink: 0;
}

.file-icon-pdf {
    background: #FEE2E2;
    color: #DC2626;
    border: 1px solid #FCA5A5;
}

.file-icon-doc {
    background: #EFF6FF;
    color: #2563EB;
    border: 1px solid #BFDBFE;
}

.file-icon-xls {
    background: #ECFDF5;
    color: #059669;
    border: 1px solid #A7F3D0;
}

.file-icon-other {
    background: #F1F5F9;
    color: #64748B;
    border: 1px solid #CBD5E1;
}

.page-btn {
    text-decoration: none !important;
    user-select: none;
}

/* Seamless Fade Transition */
#docTableBody {
    transition: opacity 0.15s ease-in-out;
}
</style>

<div class="dynamic-page-sections">

<?php
foreach ($sections_to_render as $sec) {
    renderDokumenSection($sec['type'], $ctx, $sec['data']);
}
?>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
