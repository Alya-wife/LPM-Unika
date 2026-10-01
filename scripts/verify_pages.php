<?php
require_once __DIR__ . '/../config/database.php';

$pages = [
    'spmi.php',
    'siklus-ami.php',
    'akreditasi.php',
    'lembaga-akreditasi.php',
    'akreditasi-institusi.php',
    'pemeringkatan.php',
    'buletin.php',
    'berita.php',
    'admin/index.php'
];

$base_url = 'http://localhost/LPM/';

echo "=== VERIFYING PAGES HTTP STATUS ===\n";
foreach ($pages as $p) {
    $url = $base_url . $p;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    $html = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $has_fatal = (stripos($html, 'Fatal error') !== false || stripos($html, 'Parse error') !== false || stripos($html, 'Uncaught PDOException') !== false);
    $status_text = ($http_code === 200 && !$has_fatal) ? "OK (200)" : "ERROR ($http_code)";
    if ($has_fatal) $status_text .= " [HAS PHP FATAL ERROR]";

    echo sprintf("%-30s : %s (Length: %d bytes)\n", $p, $status_text, strlen($html));
}

echo "\n=== VERIFYING SPECIFIC CONTENT CHECKS ===\n";

// 1. Check spmi.php has Kemendikti
$html_spmi = file_get_contents($base_url . 'spmi.php');
echo "SPMI Kemendikti present: " . (strpos($html_spmi, 'Hasil SPMI Kemendikti Saintek') !== false ? "YES" : "NO") . "\n";
echo "SPMI Kemendikti Laporan 2025 present: " . (strpos($html_spmi, 'Laporan Pelaksanaan SPMI Terintegrasi Kemendikti Saintek') !== false ? "YES" : "NO") . "\n";

// 2. Check akreditasi.php no konsultasi pendampingan and no duplicate dokumen
$html_akred = file_get_contents($base_url . 'akreditasi.php');
echo "Akreditasi Konsultasi Pendampingan removed: " . (strpos($html_akred, 'Konsultasi Pendampingan Akreditasi') === false ? "YES" : "NO") . "\n";
echo "Akreditasi Unduh Dokumen & Sertifikat section removed: " . (strpos($html_akred, 'Unduh Dokumen & Sertifikat Akreditasi Institusi') === false ? "YES" : "NO") . "\n";

// 3. Check lembaga-akreditasi.php
$html_lam = file_get_contents($base_url . 'lembaga-akreditasi.php');
echo "LAM BAN-PT logo present: " . (strpos($html_lam, 'logo_banpt_1789003596.webp') !== false ? "YES" : "NO") . "\n";
echo "LAM website link present: " . (strpos($html_lam, 'https://www.banpt.or.id/') !== false ? "YES" : "NO") . "\n";
echo "LAM bottom documents removed: " . (strpos($html_lam, 'Unduh Dokumen & Sertifikat Akreditasi Institusi') === false ? "YES" : "NO") . "\n";

// 4. Check pemeringkatan.php has UI GreenMetric and NO prodi section
$html_rank = file_get_contents($base_url . 'pemeringkatan.php');
echo "Pemeringkatan UI GreenMetric present: " . (strpos($html_rank, 'UI GreenMetric') !== false && strpos($html_rank, '#1398') !== false ? "YES" : "NO") . "\n";
echo "Pemeringkatan Prodi section removed: " . (strpos($html_rank, 'Peringkat Bidang Studi & Jurusan') === false ? "YES" : "NO") . "\n";

// 5. Check buletin & berita count in DB
$db = getDB();
$buletin_count = $db->query("SELECT COUNT(*) FROM buletin")->fetchColumn();
$berita_count = $db->query("SELECT COUNT(*) FROM berita")->fetchColumn();
echo "Buletin total count: $buletin_count\n";
echo "Berita total count: $berita_count\n";

// 6. Check admin credentials
$admin_u = $db->query("SELECT * FROM users WHERE username = 'admin'")->fetch();
$auth_ok = password_verify('admin123', $admin_u['password']);
echo "Admin Login check (admin / admin123): " . ($auth_ok ? "SUCCESS" : "FAILED") . "\n";
