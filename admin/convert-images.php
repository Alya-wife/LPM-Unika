<?php
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../config/database.php';

$admin_page_title = 'Konversi Gambar WebP HD';
$db = getDB();

$run_conversion = isset($_POST['start_conversion']) || (isset($_GET['run']) && $_GET['run'] === '1');

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <div>
        <h2 style="font-size:1.25rem;font-weight:700;color:var(--navy);margin:0;">
            Konversi Gambar WebP HD
        </h2>
        <p style="font-size:0.85rem;color:var(--text-muted);margin:0;">
            Optimalisasi kompresi gambar server ke format modern WebP HD untuk mempercepat loading website dan efisiensi ruang hosting.
        </p>
    </div>
    <a href="dashboard.php" class="btn btn-sm btn-outline-secondary fw-semibold" style="border-radius:8px;">
        <i class="bi bi-arrow-left me-1"></i> Kembali ke Dashboard
    </a>
</div>

<?php if (!$run_conversion): ?>
<div class="card border-0 rounded-4 shadow-sm bg-white mb-4 overflow-hidden">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex align-items-center gap-2">
        <i class="bi bi-info-circle-fill text-primary fs-5"></i>
        <h5 class="m-0 fw-bold" style="font-size:0.98rem;color:var(--navy);">
            Tentang Proses Optimasi WebP HD
        </h5>
    </div>
    <div class="card-body p-4">
        <p class="text-muted" style="font-size:0.9rem;line-height:1.7;">
            Program ini memindai berkas gambar lama di direktori server dan mengonversinya ke format WebP berkualitas tinggi secara otomatis.
        </p>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;height:100%;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-check-circle-fill text-success mt-1"></i>
                        <div>
                            <strong style="font-size:0.88rem;color:var(--navy);">Pemindaian Folder Menyeluruh</strong>
                            <div class="text-muted small mt-1">
                                Memeriksa seluruh subdirektori <code>uploads/</code> (berita, banner slides, data tim, piagam penghargaan, cover buletin, dan berkas akreditasi).
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;height:100%;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-check-circle-fill text-success mt-1"></i>
                        <div>
                            <strong style="font-size:0.88rem;color:var(--navy);">Kompresi HD &amp; Koreksi EXIF</strong>
                            <div class="text-muted small mt-1">
                                Mengonversi JPG dan PNG ke WebP HD (maksimal lebar 1920px proporsional) serta mengoreksi orientasi foto smartphone yang miring/terbalik.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;height:100%;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-check-circle-fill text-success mt-1"></i>
                        <div>
                            <strong style="font-size:0.88rem;color:var(--navy);">Pembaruan Basis Data Otomatis</strong>
                            <div class="text-muted small mt-1">
                                Nama file pada tabel database diperbarui secara otomatis sehingga gambar di website tetap terhubung tanpa kendala.
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="p-3 rounded-3" style="background:#F8FAFC;border:1px solid #E2E8F0;height:100%;">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bi bi-check-circle-fill text-success mt-1"></i>
                        <div>
                            <strong style="font-size:0.88rem;color:var(--navy);">Pembersihan Berkas Lama</strong>
                            <div class="text-muted small mt-1">
                                Berkas lama yang berukuran besar dihapus setelah konversi berhasil untuk menghemat penggunaan kuota penyimpanan server hosting.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="p-4 rounded-3 text-center" style="background:#FAF5FF;border:1.5px dashed #D8B4FE;">
            <h6 class="fw-bold mb-2" style="color:var(--navy);">Siap Menjalankan Konversi?</h6>
            <p class="text-muted small mb-3">
                Aman dijalankan kapan saja. Berkas yang sudah berformat .webp tidak akan diproses ulang.
            </p>
            <form method="POST">
                <input type="hidden" name="start_conversion" value="1">
                <button type="submit" class="btn btn-primary px-5 py-2 fw-bold" style="background:var(--navy);border:none;border-radius:10px;">
                    <i class="bi bi-arrow-repeat me-1"></i> Mulai Konversi Gambar Sekarang
                </button>
            </form>
        </div>
    </div>
</div>

<?php else: ?>
<div class="card border-0 rounded-4 shadow-sm bg-white mb-4 overflow-hidden">
    <div class="card-header bg-white py-3 px-4 border-bottom d-flex justify-content-between align-items-center">
        <div class="d-flex align-items-center gap-2">
            <i class="bi bi-terminal-fill text-primary fs-5"></i>
            <h5 class="m-0 fw-bold" style="font-size:0.98rem;color:var(--navy);">
                Log Eksekusi Konversi
            </h5>
        </div>
        <span class="badge bg-success-subtle text-success border border-success-subtle px-3 py-1">
            Proses Selesai
        </span>
    </div>
    <div class="card-body p-4">
        <div style="background:#0F172A;color:#38BDF8;font-family:'Courier New',Courier,monospace;font-size:0.85rem;padding:20px;border-radius:10px;max-height:420px;overflow-y:auto;line-height:1.6;white-space:pre-wrap;" id="logTerminal">
<?php
// Disable output buffering for log output
@ini_set('output_buffering', 'off');
@ini_set('zlib.output_compression', false);
@ini_set('implicit_flush', true);
ob_implicit_flush(true);
while (ob_get_level()) { ob_end_flush(); }

@set_time_limit(600);
@ini_set('memory_limit', '512M');

echo "=== MEMULAI KONVERSI GAMBAR KE WEBP HD ===\n";
echo "Waktu Eksekusi: " . date('Y-m-d H:i:s') . "\n\n";

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

    echo "Memeriksa direktori: {$rel_folder}...\n";
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

        // Koreksi orientasi EXIF
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

        // Resizing HD batas 1920px
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
            echo "  [OK] {$file} -> {$webp_filename} (Hemat {$saved_kb} KB / -{$percent}%)\n";

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

            // Hapus file lama yang berukuran besar
            @unlink($full_file_path);
        }
    }
}

$mb_before = round($total_bytes_before / (1024 * 1024), 2);
$mb_after  = round($total_bytes_after / (1024 * 1024), 2);
$mb_saved  = round($mb_before - $mb_after, 2);
$overall_percent = $total_bytes_before > 0 ? round(($mb_saved / $mb_before) * 100, 1) : 0;

echo "\n=== PROSES KONVERSI SELESAI ===\n";
echo "Total berkas dikonversi : {$total_converted} file\n";
echo "Ukuran sebelum konversi : {$mb_before} MB\n";
echo "Ukuran sesudah konversi : {$mb_after} MB\n";
echo "Total ruang dihemat     : {$mb_saved} MB (-{$overall_percent}%)\n";
?>
        </div>

        <div class="row g-3 mt-4">
            <div class="col-md-4">
                <div class="p-3 rounded-3 text-center bg-light border">
                    <div class="text-muted small">Total Berkas Dikonversi</div>
                    <h3 class="fw-bold text-primary mb-0 mt-1"><?= $total_converted ?> File</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3 text-center bg-light border">
                    <div class="text-muted small">Ukuran Sebelumnya</div>
                    <h3 class="fw-bold text-secondary mb-0 mt-1"><?= $mb_before ?> MB</h3>
                </div>
            </div>
            <div class="col-md-4">
                <div class="p-3 rounded-3 text-center bg-light border">
                    <div class="text-muted small">Ukuran Sesudah (WebP HD)</div>
                    <h3 class="fw-bold text-success mb-0 mt-1">
                        <?= $mb_after ?> MB <small class="fs-6">(-<?= $overall_percent ?>%)</small>
                    </h3>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center gap-2 mt-4">
            <a href="convert-images.php" class="btn btn-outline-secondary px-4 fw-semibold" style="border-radius:8px;">
                <i class="bi bi-arrow-repeat me-1"></i> Jalankan Lagi
            </a>
            <a href="dashboard.php" class="btn btn-primary px-4 fw-bold" style="border-radius:8px;background:var(--navy);border:none;">
                <i class="bi bi-house me-1"></i> Kembali ke Dashboard
            </a>
        </div>
    </div>
</div>

<script>
const terminal = document.getElementById('logTerminal');
if (terminal) {
    terminal.scrollTop = terminal.scrollHeight;
}
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
