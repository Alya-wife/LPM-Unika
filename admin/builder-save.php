<?php
require_once __DIR__ . '/../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['admin_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sesi admin berakhir. Silakan login kembali.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metode request tidak valid.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    $input = $_POST;
}

$page_id      = (int)($input['page_id'] ?? 0);
$judul        = trim($input['judul'] ?? '');
$slug         = trim($input['slug'] ?? '');
$kategori     = trim($input['kategori'] ?? 'Umum');
$status       = in_array($input['status'] ?? '', ['publish', 'draft']) ? $input['status'] : 'publish';
$show_in_nav  = !empty($input['show_in_nav']) ? 1 : 0;
$nav_position = in_array($input['nav_position'] ?? '', ['main', 'dropdown']) ? $input['nav_position'] : 'main';
$nav_label    = trim($input['nav_label'] ?? '');
$urutan       = (int)($input['urutan'] ?? 0);
$blocks       = is_array($input['blocks'] ?? null) ? $input['blocks'] : [];

if (!$judul) {
    echo json_encode(['success' => false, 'message' => 'Judul halaman tidak boleh kosong.']);
    exit;
}

if (!$slug) {
    $slug = makeSlug($judul);
} else {
    $slug = makeSlug($slug);
}

if (!$nav_label) {
    $nav_label = $judul;
}

$db = getDB();

// Cek duplikasi slug
$check = $db->prepare("SELECT id FROM pages WHERE slug = ? AND id != ?");
$check->execute([$slug, $page_id]);
if ($check->fetch()) {
    echo json_encode(['success' => false, 'message' => "Slug URL '{$slug}' sudah digunakan oleh halaman lain. Gunakan slug yang berbeda."]);
    exit;
}

$blocks_json   = json_encode($blocks, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
$compiled_html = renderPageBlocks($blocks);
$ringkasan     = '';
if (!empty($blocks[0]['subtitle'])) {
    $ringkasan = truncate($blocks[0]['subtitle'], 160);
} elseif (!empty($blocks[0]['title'])) {
    $ringkasan = truncate($blocks[0]['title'], 160);
}

try {
    if ($page_id > 0) {
        $stmt = $db->prepare("UPDATE pages SET judul = ?, slug = ?, kategori = ?, ringkasan = ?, konten = ?, blocks_json = ?, status = ?, show_in_nav = ?, nav_position = ?, nav_label = ?, urutan = ? WHERE id = ?");
        $stmt->execute([$judul, $slug, $kategori, $ringkasan, $compiled_html, $blocks_json, $status, $show_in_nav, $nav_position, $nav_label, $urutan, $page_id]);
        $saved_id = $page_id;
    } else {
        $stmt = $db->prepare("INSERT INTO pages (judul, slug, kategori, ringkasan, konten, blocks_json, status, show_in_nav, nav_position, nav_label, urutan) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$judul, $slug, $kategori, $ringkasan, $compiled_html, $blocks_json, $status, $show_in_nav, $nav_position, $nav_label, $urutan]);
        $saved_id = (int)$db->lastInsertId();
    }

    $saveMessage = ($status === 'draft')
        ? 'Halaman berhasil disimpan sebagai Draf (Disembunyikan dari publik).'
        : 'Halaman berhasil disimpan dan dipublikasikan.';

    $stmt_info = $db->prepare("SELECT custom_url, slug FROM pages WHERE id = ?");
    $stmt_info->execute([$saved_id]);
    $page_info = $stmt_info->fetch();

    $live_url = SITE_URL . '/page.php?slug=' . $slug;
    if (!empty($page_info['custom_url'])) {
        $live_url = SITE_URL . '/' . ($page_info['custom_url'] === 'index.php' ? '' : ltrim($page_info['custom_url'], '/'));
    }

    echo json_encode([
        'success' => true,
        'message' => $saveMessage,
        'page_id' => $saved_id,
        'slug'    => $slug,
        'status'  => $status,
        'url'     => $live_url
    ]);
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => 'Gagal menyimpan ke database: ' . $e->getMessage()
    ]);
}
