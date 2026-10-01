<?php
/**
 * Script Konversi Massal Gambar yang Ada ke Format WebP HD
 * Mengonversi seluruh file gambar di folder uploads dan memperbarui database otomatis.
 * Dapat dijalankan via CLI (Terminal) ataupun via Browser.
 */

// Cek apakah diakses lewat browser atau terminal
$is_cli = (php_sapi_name() === 'cli');

if (!$is_cli) {
    header('Content-Type: text/plain; charset=utf-8');
    // Jika lewat browser, pastikan ada session login admin atau token key
    session_start();
    $token = $_GET['key'] ?? '';
    $is_auth = (!empty($_SESSION['admin_logged_in']) || !empty($_SESSION['user_id']) || $token === 'unika2026');
    if (!$is_auth) {
        http_response_code(403);
        die("AKSES DITOLAK: Silakan login sebagai admin atau tambahkan parameter ?key=unika2026 di URL browser.");
    }
}

@set_time_limit(600);
@ini_set('memory_limit', '512M');
if (!$is_cli) {
    ob_implicit_flush(true);
    while (ob_get_level()) { ob_end_flush(); }
}

require_once __DIR__ . '/../config/database.php';

$db = getDB();

echo "========================================================\n";
echo "  MULAI KONVERSI MASSAL GAMBAR KE FORMAT WEBP HD\n";
echo "========================================================\n\n";

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
$sql_queries = [];
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

        // Cek apakah file adalah gambar valid
        $size_before = filesize($full_file_path);
        $total_bytes_before += $size_before;

        $webp_filename = pathinfo($file, PATHINFO_FILENAME) . '.webp';
        $webp_full_path = $folder_path . DIRECTORY_SEPARATOR . $webp_filename;

        // Konversi ke WebP
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

        // Koreksi orientasi EXIF jika ada
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

        // Resizing HD (maksimal lebar 1920px jika sangat besar)
        $maxWidth = 1920;
        if ($srcWidth > $maxWidth) {
            $targetW = $maxWidth;
            $targetH = (int)round(($srcHeight / $srcWidth) * $maxWidth);
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

        // Simpan WebP dengan kualitas 85 (kualitas HD tajam)
        $success = imagewebp($targetImg, $webp_full_path, 85);
        imagedestroy($srcImg);
        imagedestroy($targetImg);

        if ($success && file_exists($webp_full_path)) {
            $size_after = filesize($webp_full_path);
            $total_bytes_after += $size_after;
            $total_converted++;

            $saved_kb = round(($size_before - $size_after) / 1024, 1);
            $percent = round((($size_before - $size_after) / $size_before) * 100, 1);
            echo "  [OK] {$file} -> {$webp_filename} (Hemat {$saved_kb} KB / -{$percent}%)\n";

            // Update Database Lokal
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
                    $sql_queries[] = "UPDATE `{$tbl}` SET `{$col}` = '{$webp_filename}' WHERE `{$col}` = '{$file}';";
                } catch (Exception $e) {}
            }

            // Hapus file lama yang tidak terkompresi
            @unlink($full_file_path);
        }
    }
}

// Simpan script SQL update untuk hosting
$sql_file_content = "-- SQL UPDATE GAMBAR WEBP LPM UNIKA\n-- Dibuat otomatis: " . date('Y-m-d H:i:s') . "\n\n";
$sql_file_content .= implode("\n", array_unique($sql_queries)) . "\n";
$sql_export_path = $root_dir . DIRECTORY_SEPARATOR . 'update_images_to_webp.sql';
file_put_contents($sql_export_path, $sql_file_content);

$mb_before = round($total_bytes_before / (1024 * 1024), 2);
$mb_after  = round($total_bytes_after / (1024 * 1024), 2);
$mb_saved  = round($mb_before - $mb_after, 2);
$overall_percent = $total_bytes_before > 0 ? round(($mb_saved / $mb_before) * 100, 1) : 0;

echo "\n========================================================\n";
echo "  SELESAI! HASIL KONVERSI:\n";
echo "========================================================\n";
echo "Total gambar berhasil dikonversi: {$total_converted} file\n";
echo "Ukuran sebelum konversi         : {$mb_before} MB\n";
echo "Ukuran setelah konversi (WebP HD): {$mb_after} MB\n";
echo "Penghematan Kuota & Kecepatan   : Hemat {$mb_saved} MB (-{$overall_percent}% Lebih Ringan & Cepat!)\n";
echo "File query SQL untuk hosting disimpan di: update_images_to_webp.sql\n";
echo "========================================================\n";
