<?php
/**
 * Utility Konversi Gambar Lama ke Format WebP HD di Hosting
 * Dapat diakses via Admin Panel atau via Browser dengan kunci otorisasi: ?key=unika2026
 */

// Autentikasi: Login admin ATAU menggunakan secret key di URL
$secret_key = 'unika2026';
$is_authenticated = false;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$input_key = $_GET['key'] ?? ($_POST['key'] ?? '');

if (!empty($_SESSION['admin_id']) || !empty($_SESSION['admin_email']) || !empty($_SESSION['user_id']) || !empty($_SESSION['admin_logged_in'])) {
    $is_authenticated = true;
} elseif ($input_key === $secret_key) {
    $is_authenticated = true;
}

if (!$is_authenticated) {
    http_response_code(403);
    die('<div style="font-family:sans-serif;text-align:center;padding:50px;"><h2>Akses Ditolak</h2><p>Silakan login sebagai admin terlebih dahulu, atau akses dengan parameter key: <code>?key=' . htmlspecialchars($secret_key) . '</code></p></div>');
}

require_once __DIR__ . '/../config/database.php';
$db = getDB();

$admin_page_title = 'Konversi Gambar WebP HD';
$run_conversion = isset($_POST['start_conversion']) || (isset($_GET['run']) && $_GET['run'] === '1');
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Konversi Gambar WebP HD - LPM UNIKA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        body { font-family: 'Inter', sans-serif; background-color: #f8fafc; color: #1e293b; padding: 30px 15px; }
        .converter-card { max-width: 900px; margin: 0 auto; background: #ffffff; border-radius: 16px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); border: 1px solid #e2e8f0; overflow: hidden; }
        .converter-header { background: linear-gradient(135deg, #1e3a8a, #0f172a); color: white; padding: 25px 30px; }
        .log-terminal { background: #0f172a; color: #38bdf8; font-family: 'Courier New', Courier, monospace; font-size: 0.88rem; padding: 20px; border-radius: 10px; max-height: 450px; overflow-y: auto; line-height: 1.6; white-space: pre-wrap; }
        .log-ok { color: #4ade80; }
        .log-warn { color: #facc15; }
        .log-err { color: #f87171; }
        .stat-badge { background: #f1f5f9; border-radius: 8px; padding: 15px; text-align: center; border: 1px solid #e2e8f0; }
    </style>
</head>
<body>

<div class="converter-card">
    <div class="converter-header d-flex align-items-center justify-content-between">
        <div>
            <h4 class="mb-1 fw-bold">Konverter Gambar WebP HD Massal</h4>
            <p class="mb-0 text-white-50" style="font-size: 0.9rem;">Optimasi gambar di server hosting agar loading website super cepat dan hemat bandwidth.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-light btn-sm px-3">Kembali ke Admin</a>
    </div>

    <div class="p-4 p-md-5">
        <?php if (!$run_conversion): ?>
            <div class="alert alert-info border-0 shadow-sm mb-4">
                <h6 class="fw-bold mb-2">📌 Apa yang dilakukan oleh program ini?</h6>
                <ul class="mb-0 ps-3" style="font-size: 0.92rem;">
                    <li>Memeriksa seluruh folder di <code>uploads/</code> (berita, slides, tim, penghargaan, akreditasi, buletin, dll).</li>
                    <li>Mencari semua gambar format lama (<code>.jpg</code>, <code>.jpeg</code>, <code>.png</code>).</li>
                    <li>Mengonversi ke <strong>WebP Full HD</strong> (maks. lebar 1920px proporsional, kualitas 85% tajam & jernih).</li>
                    <li>Otomatis memperbaiki rotasi foto kamera HP yang terbalik/miring berdasarkan metadata EXIF.</li>
                    <li>Memperbarui nama file di Database hosting secara otomatis.</li>
                    <li>Menghapus file lama yang berukuran besar untuk menghemat ruang penyimpanan hosting.</li>
                </ul>
            </div>

            <div class="text-center py-4">
                <form method="POST" action="<?= htmlspecialchars($_SERVER['REQUEST_URI'] ?? '') ?>">
                    <input type="hidden" name="start_conversion" value="1">
                    <?php if (!empty($input_key)): ?>
                        <input type="hidden" name="key" value="<?= htmlspecialchars($input_key) ?>">
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-lg px-5 py-3 rounded-pill fw-semibold shadow">
                        ⚡ Mulai Konversi Semua Gambar Sekarang
                    </button>
                </form>
                <p class="text-muted mt-3" style="font-size: 0.85rem;">Aman dijalankan kapan saja. Gambar yang sudah berformat .webp tidak akan diproses ulang.</p>
            </div>
        <?php else: ?>
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h6 class="fw-bold mb-0">Log Proses Konversi Realtime:</h6>
                <span class="badge bg-success px-3 py-2">Sedang Berjalan...</span>
            </div>

            <div class="log-terminal" id="logTerminal">
<?php
// Disable output buffering for real-time log output
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
@ini_set('implicit_flush', true);
ob_implicit_flush(true);
while (ob_get_level()) { ob_end_flush(); }

@set_time_limit(600); // 10 menit
@ini_set('memory_limit', '512M');

echo "=== MEMULAI KONVERSI GAMBAR KE WEBP HD ===\n";
echo "Waktu Mulai: " . date('Y-m-d H:i:s') . "\n\n";

$target_folders = [
    'uploads/berita'            => ['table' => 'berita', 'col' => 'gambar', 'multi_table' => 'berita_gambar', 'multi_col' => 'gambar'],
    'uploads/slides'            => ['table' => 'slider', 'col' => 'gambar'],
    'uploads/tim'               => ['table' => 'tim_lpm', 'col' => 'foto'],
    'uploads/penghargaan'       => ['table' => 'penghargaan', 'col' => 'file_path'],
    'uploads/buletin/covers'    => ['table' => 'buletin', 'col' => 'cover_path'],
    'uploads/profil'            => ['table' => 'pengaturan', 'col' => 'nilai'],
    'uploads/akreditasi'        => ['table' => 'lembaga_akreditasi', 'col' => 'logo'],
    'uploads/akreditasi_prodi'  => ['table' => 'akreditasi_prodi', 'col' => 'sertifikat_file']
];

$root_dir = realpath(__DIR__ . '/..');
$total_converted = 0;
$total_bytes_before = 0;
$total_bytes_after = 0;

foreach ($target_folders as $rel_folder => $db_target) {
    $folder_path = $root_dir . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $rel_folder);
    if (!is_dir($folder_path)) {
        continue;
    }

    echo "Memeriksa folder: {$rel_folder}...\n";
    $files = scandir($folder_path);

    foreach ($files as $file) {
        if ($file === '.' || $file === '..') continue;

        $full_file_path = $folder_path . DIRECTORY_SEPARATOR . $file;
        if (!is_file($full_file_path)) continue;

        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        if (!in_array($ext, ['jpg', 'jpeg', 'png'])) continue;

        $size_before = filesize($full_file_path);
        $webp_filename = pathinfo($file, PATHINFO_FILENAME) . '.webp';
        $webp_full_path = $folder_path . DIRECTORY_SEPARATOR . $webp_filename;

        // Cek gambar valid
        $imageInfo = @getimagesize($full_file_path);
        if (!$imageInfo) continue;

        $mime = $imageInfo['mime'];
        $srcWidth = $imageInfo[0];
        $srcHeight = $imageInfo[1];

        switch ($mime) {
            case 'image/jpeg':
            case 'image/jpg':
                $srcImg = @imagecreatefromjpeg($full_file_path);
                break;
            case 'image/png':
                $srcImg = @imagecreatefrompng($full_file_path);
                break;
            default:
                $srcImg = false;
                break;
        }

        if (!$srcImg) continue;

        // EXIF auto rotation
        if (function_exists('exif_read_data') && ($mime === 'image/jpeg' || $mime === 'image/jpg')) {
            $exif = @exif_read_data($full_file_path);
            if (!empty($exif['Orientation'])) {
                switch ($exif['Orientation']) {
                    case 3:
                        $srcImg = imagerotate($srcImg, 180, 0);
                        break;
                    case 6:
                        $srcImg = imagerotate($srcImg, -90, 0);
                        $tmpW = $srcWidth; $srcWidth = $srcHeight; $srcHeight = $tmpW;
                        break;
                    case 8:
                        $srcImg = imagerotate($srcImg, 90, 0);
                        $tmpW = $srcWidth; $srcWidth = $srcHeight; $srcHeight = $tmpW;
                        break;
                }
            }
        }

        // Resizing HD maks lebar 1920px
        $maxWidth = 1920;
        if ($srcWidth > $maxWidth) {
            $targetW = $maxWidth;
            $targetH = (int)round(($srcHeight / $srcWidth) * $targetW);
        } else {
            $targetW = $srcWidth;
            $targetH = $srcHeight;
        }

        $targetImg = imagecreatetruecolor($targetW, $targetH);
        imagealphablending($targetImg, false);
        imagesavealpha($targetImg, true);
        $transparent = imagecolorallocatealpha($targetImg, 0, 0, 0, 127);
        imagefilledrectangle($targetImg, 0, 0, $targetW, $targetH, $transparent);

        imagecopyresampled($targetImg, $srcImg, 0, 0, 0, 0, $targetW, $targetH, $srcWidth, $srcHeight);

        // Simpan WebP kualitas 85
        $success = imagewebp($targetImg, $webp_full_path, 85);
        imagedestroy($srcImg);
        imagedestroy($targetImg);

        if ($success && file_exists($webp_full_path)) {
            $size_after = filesize($webp_full_path);
            $total_bytes_before += $size_before;
            $total_bytes_after += $size_after;
            $total_converted++;

            $saved_kb = round(($size_before - $size_after) / 1024, 1);
            $percent = $size_before > 0 ? round((($size_before - $size_after) / $size_before) * 100, 1) : 0;
            echo "  ✓ {$file} -> {$webp_filename} (Hemat {$saved_kb} KB / -{$percent}%)\n";

            // Update Database
            $tables_to_update = [];
            if (!empty($db_target['table']) && !empty($db_target['col'])) {
                $tables_to_update[] = [$db_target['table'], $db_target['col']];
            }
            if (!empty($db_target['multi_table']) && !empty($db_target['multi_col'])) {
                $tables_to_update[] = [$db_target['multi_table'], $db_target['multi_col']];
            }

            foreach ($tables_to_update as $t) {
                $tbl = $t[0];
                $col = $t[1];
                try {
                    $q = "UPDATE `{$tbl}` SET `{$col}` = ? WHERE `{$col}` = ?";
                    $db->prepare($q)->execute([$webp_filename, $file]);
                } catch (Exception $e) {}
            }

            // Hapus file lama yang berat
            @unlink($full_file_path);
        }
    }
}

$mb_before = round($total_bytes_before / (1024 * 1024), 2);
$mb_after  = round($total_bytes_after / (1024 * 1024), 2);
$mb_saved  = round($mb_before - $mb_after, 2);
$overall_percent = $total_bytes_before > 0 ? round(($mb_saved / $mb_before) * 100, 1) : 0;

echo "\n=== PROSES KONVERSI SELESAI ===\n";
echo "Total gambar dikonversi : {$total_converted} file\n";
echo "Ukuran sebelum konversi : {$mb_before} MB\n";
echo "Ukuran sesudah konversi : {$mb_after} MB\n";
echo "Total kuota dihemat     : {$mb_saved} MB (-{$overall_percent}%)\n";
?>
            </div>

            <div class="row g-3 mt-4">
                <div class="col-md-4">
                    <div class="stat-badge">
                        <div class="text-muted small">Total Gambar Dikonversi</div>
                        <h3 class="fw-bold text-primary mb-0 mt-1"><?= $total_converted ?> File</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-badge">
                        <div class="text-muted small">Ukuran Sebelumnya</div>
                        <h3 class="fw-bold text-secondary mb-0 mt-1"><?= $mb_before ?> MB</h3>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="stat-badge">
                        <div class="text-muted small">Ukuran Sekarang (WebP HD)</div>
                        <h3 class="fw-bold text-success mb-0 mt-1"><?= $mb_after ?> MB <small class="text-success fs-6">(-<?= $overall_percent ?>%)</small></h3>
                    </div>
                </div>
            </div>

            <div class="text-center mt-4">
                <a href="convert-images.php" class="btn btn-outline-secondary me-2">Jalankan Lagi</a>
                <a href="dashboard.php" class="btn btn-primary px-4">Kembali ke Dashboard Admin</a>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    // Auto scroll log terminal to bottom
    const terminal = document.getElementById('logTerminal');
    if (terminal) {
        terminal.scrollTop = terminal.scrollHeight;
    }
</script>
</body>
</html>
