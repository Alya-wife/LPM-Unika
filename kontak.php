<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Kontak';
$meta_desc  = 'Hubungi Lembaga Penjaminan Mutu SCU – Alamat, nomor telepon, email, dan formulir kontak kami.';

// Handle form submission
$success = $error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama    = trim($_POST['nama'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $subjek  = trim($_POST['subjek'] ?? '');
    $pesan   = trim($_POST['pesan'] ?? '');
    $instansi = trim($_POST['instansi'] ?? 'Kontak Website');

    if (!$nama || !$email || !$subjek || !$pesan) {
        $error = 'Semua field wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format email tidak valid.';
    } else {
        // Simpan ke database feedback
        $db = getDB();
        $stmt = $db->prepare("INSERT INTO feedback (nama, email, jenis_layanan, instansi, pesan, status, is_archived, tanggal) VALUES (?, ?, ?, ?, ?, 'Belum Dibaca', 0, NOW())");
        $stmt->execute([$nama, $email, $subjek, $instansi, $pesan]);

        // Kirim email notifikasi
        kirimNotifikasiEmailAspirasi($nama, $email, $subjek, $instansi, $pesan);

        $success = 'Pesan Anda telah berhasil dikirim ke tim LPM UNIKA. Kami akan segera menghubungi Anda.';
    }
}

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<!-- Page Banner -->
<div class="page-banner">
    <div class="container position-relative">
        <div class="hero-badge mb-3">
            <span class="hero-badge-dot"></span>
            Hubungi Kami
        </div>
        <h1 class="page-banner-title">Kontak LPM UNIKA</h1>
        <div class="breadcrumb-lpm">
            <a href="<?= SITE_URL ?>/">Beranda</a>
            <span>/</span>
            <span class="current">Kontak</span>
        </div>
    </div>
</div>

<?php
require_once __DIR__ . '/includes/kontak-sections.php';

$form_data = [
    'success' => $success,
    'error'   => $error
];

// Ambil susunan seksi dari Visual Page Builder
$kontak_blocks = null;
try {
    $stmt_k = getDB()->query("SELECT blocks_json FROM pages WHERE slug = 'kontak'");
    $row_k = $stmt_k->fetch();
    if (!empty($row_k['blocks_json'])) {
        $kontak_blocks = json_decode($row_k['blocks_json'], true);
    }
} catch (Exception $e) {}

if (!empty($kontak_blocks) && is_array($kontak_blocks)) {
    foreach ($kontak_blocks as $block) {
        if ((isset($block['status']) && $block['status'] === 'draft') || (isset($block['is_visible']) && !$block['is_visible'])) continue;
        renderKontakSection($block['type'], $block, false, $form_data);
    }
} else {
    // Alur Default 2 Seksi Kontak
    renderKontakSection('kontak_info', [], false, $form_data);
    renderKontakSection('kontak_form', [], false, $form_data);
}
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
