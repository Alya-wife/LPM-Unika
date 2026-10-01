<?php
require_once __DIR__ . '/config/database.php';
$page_title = 'Beranda';
$meta_desc = 'LPM UNIKA – Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata. Mewujudkan mutu pendidikan tinggi yang unggul dan berkelanjutan.';

$db = getDB();

// Ambil berita terbaru
$berita_terbaru = $db->query("SELECT * FROM berita ORDER BY tanggal_publikasi DESC LIMIT 3")->fetchAll();

// Ambil slide aktif dari database
$hero_slides = $db->query("SELECT * FROM hero_slides WHERE is_active = 1 ORDER BY urutan ASC, id ASC")->fetchAll();

// Ambil data penghargaan (Max 8)
$penghargaan_list = [];
try {
    $penghargaan_list = $db->query("SELECT * FROM penghargaan ORDER BY tahun DESC, id DESC LIMIT 8")->fetchAll(PDO::FETCH_OBJ);
} catch (Exception $e) {
    // Tabel belum siap
}
$layout_penghargaan = getPengaturan('layout_penghargaan', 'slider');

// Hitung statistik
$actual_doc_count   = (int)$db->query("SELECT COUNT(*) FROM dokumen")->fetchColumn();
$actual_prodi_count = (int)$db->query("SELECT COUNT(*) FROM akreditasi_prodi")->fetchColumn();
$count_terakreditasi = (int)$db->query("SELECT COUNT(*) FROM akreditasi_prodi WHERE peringkat IS NOT NULL AND TRIM(peringkat) != '' AND LOWER(peringkat) NOT LIKE '%belum%'")->fetchColumn();
if ($actual_prodi_count > 0) {
    $actual_akred_pct = ($count_terakreditasi >= $actual_prodi_count) ? 100 : round(($count_terakreditasi / $actual_prodi_count) * 100);
} else {
    $actual_akred_pct = 100;
}
$jml_berita = $db->query("SELECT COUNT(*) FROM berita")->fetchColumn();

// Ambil data pengaturan dinamis statistik capaian
$stat_tahun          = getPengaturan('stat_tahun', '40');
$stat_prodi_custom   = getPengaturan('stat_prodi', '');
$stat_prodi          = ($stat_prodi_custom !== '' && is_numeric($stat_prodi_custom)) ? (int)$stat_prodi_custom : $actual_prodi_count;

$stat_akr_custom     = getPengaturan('stat_akreditasi_persen', '');
if ($stat_akr_custom === '') {
    $old_akr = getPengaturan('stat_akreditasi', '');
    if (is_numeric($old_akr)) $stat_akr_custom = $old_akr;
}
$stat_akr            = ($stat_akr_custom !== '' && is_numeric($stat_akr_custom)) ? (int)$stat_akr_custom : $actual_akred_pct;

$stat_dokumen_custom = getPengaturan('stat_dokumen', '');
$jml_dokumen         = ($stat_dokumen_custom !== '' && is_numeric($stat_dokumen_custom)) ? (int)$stat_dokumen_custom : $actual_doc_count;

$sambutan_nama = getPengaturan('sambutan_nama', 'Stefani Lily Indarto, SE., MM., Ak., CA., CPA.');
$sambutan_jabatan = getPengaturan('sambutan_jabatan', 'Kepala Lembaga Penjaminan Mutu');
$sambutan_instansi = getPengaturan('sambutan_instansi', 'Universitas Katolik Soegijapranata');
$sambutan_foto = getPengaturan('sambutan_foto', '');
$sambutan_teks = getPengaturan('sambutan_teks', '');

$akred_file = getPengaturan('akred_institusi_file', file_exists(__DIR__ . '/uploads/akreditasi/2023_Sertifikat_Akreditasi_ISK_AIPT_UNIKA_Unggul.pdf') ? '2023_Sertifikat_Akreditasi_ISK_AIPT_UNIKA_Unggul.pdf' : '');
$akred_peringkat = getPengaturan('akred_institusi_peringkat', 'UNGGUL');
$akred_sk = getPengaturan('akred_institusi_sk', 'Nomor SK: -');
$akred_teks = getPengaturan('akred_institusi_teks', 'Universitas kami terus berkomitmen untuk memberikan standar pendidikan terbaik sesuai dengan pedoman Badan Akreditasi Nasional Perguruan Tinggi (BAN-PT).');
$akred_badge = getPengaturan('akred_institusi_badge', 'Akreditasi Institusi');
$akred_judul = getPengaturan('akred_institusi_judul', "Capaian Mutu\nUniversitas Katolik Soegijapranata");

require_once __DIR__ . '/includes/header.php';
require_once __DIR__ . '/includes/navbar.php';
?>

<?php
require_once __DIR__ . '/includes/beranda-sections.php';

// Ambil susunan dan penyesuaian seksi dari Visual Page Builder
$beranda_blocks = null;
try {
    $stmt_b = $db->query("SELECT blocks_json FROM pages WHERE slug = 'beranda'");
    $row_b = $stmt_b->fetch();
    if (!empty($row_b['blocks_json'])) {
        $beranda_blocks = json_decode($row_b['blocks_json'], true);
    }
} catch (Exception $e) {}

if (!empty($beranda_blocks) && is_array($beranda_blocks)) {
    foreach ($beranda_blocks as $block) {
        if (isset($block['is_visible']) && !$block['is_visible']) continue;
        if (($block['type'] ?? '') === 'beranda_quick_links') continue; // Dihapus sesuai permintaan pengguna
        renderBerandaSection($block['type'], $block);
    }
} else {
    // Alur Default Seksi Beranda (kotak pintasan quick links dihapus sesuai permintaan)
    renderBerandaSection('beranda_hero');
    renderBerandaSection('beranda_stats');
    renderBerandaSection('beranda_penghargaan');
    renderBerandaSection('beranda_akreditasi');
    renderBerandaSection('beranda_berita');
    renderBerandaSection('beranda_cta');
}
?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>