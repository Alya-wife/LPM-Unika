<?php
date_default_timezone_set('Asia/Jakarta');
/**
 * Konfigurasi Database - LPM SCU
 * Koneksi PDO ke MySQL/MariaDB
 */
// Deteksi otomatis environment (Localhost vs Hosting)
$http_host = $_SERVER['HTTP_HOST'] ?? '';
$is_localhost = (
    in_array($http_host, ['localhost', '127.0.0.1']) ||
    strpos($http_host, 'localhost:') === 0 ||
    strpos($http_host, '127.0.0.1:') === 0 ||
    strpos($http_host, '.test') !== false ||
    php_sapi_name() === 'cli'
);

if ($is_localhost) {
    // Konfigurasi Database Localhost (Laragon / XAMPP)
    define('DB_HOST', 'localhost');
    define('DB_NAME', 'lpm_scu');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_CHARSET', 'utf8mb4');

    define('SITE_NAME', 'LPM UNIKA');

    // Auto-detect URL Localhost
    if (!empty($http_host)) {
        $protocol = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
        if (strpos($http_host, 'lpm.test') !== false) {
            define('SITE_URL', $protocol . $http_host);
        } else {
            define('SITE_URL', $protocol . $http_host . '/LPM');
        }
    } else {
        define('SITE_URL', 'http://localhost/LPM');
    }
} else {
    // Konfigurasi Database Hosting Ezyro (Live)
    define('DB_HOST', 'sql102.ezyro.com');
    define('DB_NAME', 'ezyro_42878139_lpm');
    define('DB_USER', 'ezyro_42878139');        
    define('DB_PASS', 'kvw0tc9y');
    define('DB_CHARSET', 'utf8mb4');

    define('SITE_NAME', 'LPM UNIKA');
    define('SITE_URL', 'https://lpmunika.liveblog365.com');
}

define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

// Google OAuth 2.0 Credentials (dimuat dari config/oauth.local.php atau environment)
if (file_exists(__DIR__ . '/oauth.local.php')) {
    require_once __DIR__ . '/oauth.local.php';
}
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', getenv('GOOGLE_CLIENT_ID') ?: 'YOUR_GOOGLE_CLIENT_ID_HERE.apps.googleusercontent.com');
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', getenv('GOOGLE_CLIENT_SECRET') ?: 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
}

// Inisialisasi Sesi jika belum aktif
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/**
 * Cek apakah pengguna saat ini masuk dengan akun Google Civitas UNIKA (@unika.ac.id)
 */
function isUnikaLoggedIn(): bool {
    return !empty($_SESSION['unika_user']) && !empty($_SESSION['unika_user']['email']);
}

/**
 * Dapatkan data akun Google Civitas UNIKA yang sedang aktif
 */
function getUnikaUser(): ?array {
    return $_SESSION['unika_user'] ?? null;
}

/**
 * Konversi dan simpan file gambar yang diunggah ke format WebP berkualitas HD (terkompresi ringan & tajam)
 *
 * @param string $sourceTmpPath Path temporer file ($_FILES['...']['tmp_name']) atau path file lokal
 * @param string $targetDir Direktori tujuan (misal __DIR__ . '/../uploads/berita/')
 * @param string $prefix Prefix nama file (misal 'cover_', 'slide_', 'tim_')
 * @param int $quality Kualitas WebP (default 85 untuk kualitas HD tajam namun ukuran file sangat kecil)
 * @param int $maxWidth Lebar maksimal gambar dalam piksel (default 1920 untuk Full HD)
 * @return string|false Nama file baru (.webp) jika sukses, false jika gagal
 */
function convertAndSaveWebP(string $sourceTmpPath, string $targetDir, string $prefix = 'img_', int $quality = 85, int $maxWidth = 1920) {
    if (!file_exists($sourceTmpPath) || !is_readable($sourceTmpPath)) {
        return false;
    }

    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0755, true);
    }

    $imageInfo = @getimagesize($sourceTmpPath);
    if (!$imageInfo) {
        return false;
    }

    $mime = $imageInfo['mime'];
    $srcWidth = $imageInfo[0];
    $srcHeight = $imageInfo[1];

    // Buat image resource sesuai tipe mime
    switch ($mime) {
        case 'image/jpeg':
        case 'image/jpg':
            $sourceImage = @imagecreatefromjpeg($sourceTmpPath);
            break;
        case 'image/png':
            $sourceImage = @imagecreatefrompng($sourceTmpPath);
            break;
        case 'image/webp':
            $sourceImage = @imagecreatefromwebp($sourceTmpPath);
            break;
        case 'image/gif':
            $sourceImage = @imagecreatefromgif($sourceTmpPath);
            break;
        default:
            $sourceImage = false;
            break;
    }

    if (!$sourceImage) {
        $ext = strtolower(pathinfo($sourceTmpPath, PATHINFO_EXTENSION) ?: 'jpg');
        $fallbackName = $prefix . uniqid() . '.' . $ext;
        $dest = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $fallbackName;
        if (is_uploaded_file($sourceTmpPath)) {
            return move_uploaded_file($sourceTmpPath, $dest) ? $fallbackName : false;
        } else {
            return copy($sourceTmpPath, $dest) ? $fallbackName : false;
        }
    }

    // Koreksi orientasi EXIF (khusus foto smartphone yang sering terbalik)
    if (function_exists('exif_read_data') && ($mime === 'image/jpeg' || $mime === 'image/jpg')) {
        $exif = @exif_read_data($sourceTmpPath);
        if (!empty($exif['Orientation'])) {
            switch ($exif['Orientation']) {
                case 3:
                    $sourceImage = imagerotate($sourceImage, 180, 0);
                    break;
                case 6:
                    $sourceImage = imagerotate($sourceImage, -90, 0);
                    $tmpW = $srcWidth; $srcWidth = $srcHeight; $srcHeight = $tmpW;
                    break;
                case 8:
                    $sourceImage = imagerotate($sourceImage, 90, 0);
                    $tmpW = $srcWidth; $srcWidth = $srcHeight; $srcHeight = $tmpW;
                    break;
            }
        }
    }

    // Hitung dimensi baru (HD Resizing jika lebih besar dari maxWidth)
    if ($srcWidth > $maxWidth && $maxWidth > 0) {
        $targetWidth = $maxWidth;
        $targetHeight = (int)round(($srcHeight / $srcWidth) * $maxWidth);
    } else {
        $targetWidth = $srcWidth;
        $targetHeight = $srcHeight;
    }

    // Buat canvas gambar baru dengan penanganan transparansi alpha
    $targetImage = imagecreatetruecolor($targetWidth, $targetHeight);
    imagealphablending($targetImage, false);
    imagesavealpha($targetImage, true);
    $transparent = imagecolorallocatealpha($targetImage, 0, 0, 0, 127);
    imagefilledrectangle($targetImage, 0, 0, $targetWidth, $targetHeight, $transparent);

    // Resampling berkualitas tinggi
    imagecopyresampled(
        $targetImage, $sourceImage,
        0, 0, 0, 0,
        $targetWidth, $targetHeight,
        $srcWidth, $srcHeight
    );

    // Simpan ke format WebP
    $newFileName = $prefix . uniqid() . '.webp';
    $targetFilePath = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR . $newFileName;

    $saved = false;
    if (function_exists('imagewebp')) {
        $saved = imagewebp($targetImage, $targetFilePath, $quality);
    }

    imagedestroy($sourceImage);
    imagedestroy($targetImage);

    if ($saved && file_exists($targetFilePath)) {
        return $newFileName;
    }

    return false;
}

/**
 * Mengubah gambar (JPG, PNG, WEBP) menjadi dokumen PDF 1.4 murni PHP (menggunakan GD),
 * tanpa dependensi Imagick ataupun library pihak ketiga eksternal.
 */
function convertImageToPdf(string $imagePath, string $outputPdfPath): bool {
    if (!file_exists($imagePath) || !is_readable($imagePath)) {
        return false;
    }

    $imageInfo = @getimagesize($imagePath);
    if (!$imageInfo) {
        return false;
    }

    $width = $imageInfo[0];
    $height = $imageInfo[1];
    $mime = $imageInfo['mime'];

    // Load gambar menggunakan GD
    $gdImg = null;
    switch ($mime) {
        case 'image/jpeg':
            $gdImg = @imagecreatefromjpeg($imagePath);
            break;
        case 'image/png':
            $gdImg = @imagecreatefrompng($imagePath);
            break;
        case 'image/webp':
            $gdImg = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($imagePath) : null;
            break;
    }

    if (!$gdImg) {
        return false;
    }

    // Buat kanvas truecolor dengan background putih jika gambar memiliki transparansi
    $trueColor = imagecreatetruecolor($width, $height);
    $white = imagecolorallocate($trueColor, 255, 255, 255);
    imagefill($trueColor, 0, 0, $white);
    imagecopy($trueColor, $gdImg, 0, 0, 0, 0, $width, $height);
    imagedestroy($gdImg);

    // Encode ke buffer JPEG
    ob_start();
    imagejpeg($trueColor, null, 92);
    $jpegData = ob_get_clean();
    imagedestroy($trueColor);

    $imgLen = strlen($jpegData);

    // Dimensi halaman PDF dalam point (72 pt per inch)
    // Standar proporsional basis A4 (max 842 pt)
    if ($width >= $height) {
        $pdfW = 842.0;
        $pdfH = round(842.0 * ($height / $width), 2);
    } else {
        $pdfH = 842.0;
        $pdfW = round(842.0 * ($width / $height), 2);
    }

    $contentStream = "q\n" . sprintf("%.2f 0 0 %.2f 0 0 cm\n", $pdfW, $pdfH) . "/Im1 Do\nQ\n";
    $contentLen = strlen($contentStream);

    // Susun objek spesifikasi PDF 1.4
    $pdf = "%PDF-1.4\n";
    $offsets = [];

    // Objek 1: Catalog
    $offsets[1] = strlen($pdf);
    $pdf .= "1 0 obj\n<< /Type /Catalog /Pages 2 0 R >>\nendobj\n";

    // Objek 2: Pages
    $offsets[2] = strlen($pdf);
    $pdf .= "2 0 obj\n<< /Type /Pages /Kids [3 0 R] /Count 1 >>\nendobj\n";

    // Objek 3: Page
    $offsets[3] = strlen($pdf);
    $pdf .= "3 0 obj\n<< /Type /Page /Parent 2 0 R /MediaBox [0 0 {$pdfW} {$pdfH}] /Resources << /XObject << /Im1 4 0 R >> >> /Contents 5 0 R >>\nendobj\n";

    // Objek 4: Image XObject (DCTDecode / JPEG Stream)
    $offsets[4] = strlen($pdf);
    $pdf .= "4 0 obj\n<< /Type /XObject /Subtype /Image /Width {$width} /Height {$height} /ColorSpace /DeviceRGB /BitsPerComponent 8 /Filter /DCTDecode /Length {$imgLen} >>\nstream\n" . $jpegData . "\nendstream\nendobj\n";

    // Objek 5: Content stream
    $offsets[5] = strlen($pdf);
    $pdf .= "5 0 obj\n<< /Length {$contentLen} >>\nstream\n" . $contentStream . "endstream\nendobj\n";

    // xref table
    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 6\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= 5; $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }

    // trailer
    $pdf .= "trailer\n<< /Size 6 /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

    return (file_put_contents($outputPdfPath, $pdf) !== false);
}

/**
 * Menyimpan file akreditasi (Sertifikat / SK). Jika file yang diupload bukan PDF (misal JPG, JPEG, PNG, WEBP),
 * otomatis dikonversi menjadi dokumen PDF (.pdf) sebelum disimpan ke direktori dan database.
 * Juga secara otomatis membuat thumbnail WebP pendamping agar pratinjau dokumen instan tetap cepat.
 *
 * @param string $sourceTmpPath Path file sementara (tmp_name)
 * @param string $targetDir Folder tujuan penyimpanan
 * @param string $prefix Prefix penamaan file
 * @param string|null $originalName Nama file asli dari user ($_FILES['...']['name'])
 * @return string|false Nama file PDF yang tersimpan, atau false jika gagal.
 */
function saveOrConvertToPdf(string $sourceTmpPath, string $targetDir, string $prefix = 'akred_', ?string $originalName = null) {
    if (!file_exists($sourceTmpPath) || !is_readable($sourceTmpPath)) {
        return false;
    }

    $targetDir = rtrim($targetDir, '/\\') . DIRECTORY_SEPARATOR;
    if (!is_dir($targetDir)) {
        @mkdir($targetDir, 0777, true);
    }

    $ext = '';
    if ($originalName) {
        $ext = strtolower(pathinfo($originalName, PATHINFO_EXTENSION));
    }
    if (!$ext) {
        $imgInfo = @getimagesize($sourceTmpPath);
        if ($imgInfo && !empty($imgInfo['mime'])) {
            $mimeMap = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/webp' => 'webp',
            ];
            $ext = $mimeMap[$imgInfo['mime']] ?? '';
        }
    }

    $baseName = $prefix . time() . '_' . uniqid();

    // 1. Jika sudah PDF, langsung simpan file PDF
    if ($ext === 'pdf') {
        $targetFileName = $baseName . '.pdf';
        $destPath = $targetDir . $targetFileName;
        if (move_uploaded_file($sourceTmpPath, $destPath) || copy($sourceTmpPath, $destPath)) {
            return $targetFileName;
        }
        return false;
    }

    // 2. Jika bukan PDF (Gambar: JPG, JPEG, PNG, WEBP), otomatis ubah menjadi PDF!
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        $targetFileName = $baseName . '.pdf';
        $destPath = $targetDir . $targetFileName;
        $converted = convertImageToPdf($sourceTmpPath, $destPath);
        if ($converted && file_exists($destPath)) {
            // Buat file thumbnail .webp dengan nama basename yang sama untuk preview instan
            $thumbPath = $targetDir . $baseName . '.webp';
            if ($ext === 'webp') {
                @copy($sourceTmpPath, $thumbPath);
            } else {
                $gdImg = ($ext === 'png') ? @imagecreatefrompng($sourceTmpPath) : @imagecreatefromjpeg($sourceTmpPath);
                if ($gdImg && function_exists('imagewebp')) {
                    @imagewebp($gdImg, $thumbPath, 85);
                    imagedestroy($gdImg);
                }
            }
            return $targetFileName;
        }
    }

    return false;
}

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $host = DB_HOST;
        $dsn = "mysql:host=" . $host . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
            // Jika gagal dengan error 2002 (UNIX socket missing di Linux hosting), fallback otomatis ke TCP 127.0.0.1
            if (strpos($e->getMessage(), '2002') !== false && $host !== '127.0.0.1') {
                try {
                    $dsnFallback = "mysql:host=127.0.0.1;port=3306;dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
                    $pdo = new PDO($dsnFallback, DB_USER, DB_PASS, $options);
                    return $pdo;
                } catch (PDOException $e2) {
                    die(json_encode(['error' => 'Database connection failed: ' . $e2->getMessage()]));
                }
            }
            die(json_encode(['error' => 'Database connection failed: ' . $e->getMessage()]));
        }
    }
    return $pdo;
}

// Helper: Sanitize output
function e(string $str): string {
    return htmlspecialchars($str, ENT_QUOTES, 'UTF-8');
}

// Helper: Redirect
function redirect(string $url) {
    header("Location: $url");
    exit;
}

// Helper: Format tanggal Indonesia
function formatTanggal(string $date): string {
    $bulan = [
        1 => 'Januari', 'Februari', 'Maret', 'April',
        'Mei', 'Juni', 'Juli', 'Agustus',
        'September', 'Oktober', 'November', 'Desember'
    ];
    $t = strtotime($date);
    return date('d', $t) . ' ' . $bulan[(int)date('n', $t)] . ' ' . date('Y', $t);
}

// Helper: Slug generator
function makeSlug(string $text): string {
    $text = strtolower(trim($text));
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', $text);
    return trim($text, '-');
}

// Helper: Truncate text
function truncate(string $text, int $length = 150): string {
    $text = strip_tags($text);
    if (strlen($text) <= $length) return $text;
    return substr($text, 0, $length) . '...';
}

// Helper: Get all settings with caching
function getAllPengaturan(bool $refresh = false): array {
    static $settings = null;
    if ($settings === null || $refresh) {
        try {
            $db = getDB();
            $stmt = $db->query("SELECT kunci, nilai FROM pengaturan");
            $settings = [];
            while ($row = $stmt->fetch()) {
                $settings[$row['kunci']] = $row['nilai'];
            }
        } catch (Exception $e) {
            $settings = [];
        }
    }
    return $settings;
}

// Helper: Get single setting with fallback
function getPengaturan(string $kunci, string $default = ''): string {
    $all = getAllPengaturan();
    return isset($all[$kunci]) && $all[$kunci] !== '' ? $all[$kunci] : $default;
}

// Helper: Save or update setting
function setPengaturan(string $kunci, string $nilai): void {
    $db = getDB();
    $stmt = $db->prepare("INSERT INTO pengaturan (kunci, nilai) VALUES (?, ?) ON DUPLICATE KEY UPDATE nilai = VALUES(nilai)");
    $stmt->execute([$kunci, $nilai]);
    getAllPengaturan(true);
}

// Helper: Ambil data blok seksi halaman untuk Visual Page Builder (Elementor-style)
function getPageBlocks(string $slug): array {
    try {
        $db = getDB();
        $stmt = $db->prepare("SELECT blocks_json FROM pages WHERE slug = ?");
        $stmt->execute([$slug]);
        $res = $stmt->fetchColumn();
        if ($res) {
            $decoded = json_decode($res, true);
            if (is_array($decoded) && !empty($decoded)) {
                return $decoded;
            }
        }
    } catch (Exception $e) {}
    return [];
}

// Helper: Kompilasi dan render array blok menjadi HTML interaktif
function renderPageBlocks(array $blocks): string {
    if (empty($blocks)) return '';
    $html = '';
    foreach ($blocks as $idx => $b) {
        $type = $b['type'] ?? 'rich_text';
        $bg   = !empty($b['bg_color']) ? 'background:' . htmlspecialchars($b['bg_color']) . ';' : '';
        $tc   = !empty($b['text_color']) ? 'color:' . htmlspecialchars($b['text_color']) . ';' : '';
        $pad  = ($b['padding'] ?? 'normal') === 'large' ? 'py-5 py-md-6' : (($b['padding'] ?? 'normal') === 'small' ? 'py-3 py-md-4' : 'py-4 py-md-5');
        
        switch ($type) {
            case 'hero':
                $align = ($b['align'] ?? 'center') === 'left' ? 'text-start' : 'text-center';
                $html .= '<section class="builder-section builder-hero ' . $pad . ' ' . $align . '" style="' . $bg . $tc . '">';
                $html .= '<div class="container position-relative">';
                if (!empty($b['badge'])) {
                    $html .= '<div class="hero-badge mb-3"><span class="hero-badge-dot"></span>' . htmlspecialchars($b['badge']) . '</div>';
                }
                if (!empty($b['title'])) {
                    $html .= '<h1 class="page-banner-title mb-3" style="' . $tc . '">' . htmlspecialchars($b['title']) . '</h1>';
                }
                if (!empty($b['subtitle'])) {
                    $html .= '<p class="lead mb-4 mx-auto" style="max-width:750px;opacity:0.9;' . $tc . '">' . nl2br(htmlspecialchars($b['subtitle'])) . '</p>';
                }
                if (!empty($b['btn_text']) || !empty($b['btn_secondary_text'])) {
                    $html .= '<div class="d-flex gap-3 justify-content-center flex-wrap">';
                    if (!empty($b['btn_text'])) {
                        $html .= '<a href="' . htmlspecialchars($b['btn_link'] ?? '#') . '" class="btn-hero-primary">' . htmlspecialchars($b['btn_text']) . '</a>';
                    }
                    if (!empty($b['btn_secondary_text'])) {
                        $html .= '<a href="' . htmlspecialchars($b['btn_secondary_link'] ?? '#') . '" class="btn-hero-secondary">' . htmlspecialchars($b['btn_secondary_text']) . '</a>';
                    }
                    $html .= '</div>';
                }
                $html .= '</div></section>';
                break;

            case 'text_image':
                $pos = $b['image_pos'] ?? 'right';
                $html .= '<section class="builder-section builder-text-image ' . $pad . '" style="' . $bg . $tc . '">';
                $html .= '<div class="container"><div class="row g-5 align-items-center">';
                $col_text = '<div class="col-lg-7">';
                if (!empty($b['tag'])) $col_text .= '<span class="section-tag mb-2">' . htmlspecialchars($b['tag']) . '</span>';
                if (!empty($b['title'])) $col_text .= '<h2 class="section-title mb-3" style="' . $tc . '">' . htmlspecialchars($b['title']) . '</h2>';
                if (!empty($b['content'])) $col_text .= '<div style="line-height:1.8;' . $tc . '">' . $b['content'] . '</div>';
                if (!empty($b['btn_text'])) $col_text .= '<div class="mt-4"><a href="' . htmlspecialchars($b['btn_link'] ?? '#') . '" class="btn-hero-primary">' . htmlspecialchars($b['btn_text']) . '</a></div>';
                $col_text .= '</div>';

                $col_img = '<div class="col-lg-5 text-center">';
                if (!empty($b['image'])) {
                    $img_src = (strpos($b['image'], 'http') === 0 || strpos($b['image'], '/') === 0) ? $b['image'] : UPLOAD_URL . $b['image'];
                    $col_img .= '<img src="' . htmlspecialchars($img_src) . '" alt="Gambar" class="img-fluid rounded-4 shadow-sm" style="max-height:420px;width:100%;object-fit:cover;">';
                }
                $col_img .= '</div>';

                if ($pos === 'left') {
                    $html .= $col_img . $col_text;
                } else {
                    $html .= $col_text . $col_img;
                }
                $html .= '</div></div></section>';
                break;

            case 'cards_grid':
                $cols = (int)($b['columns'] ?? 3);
                $col_cls = $cols === 4 ? 'col-lg-3 col-md-6' : ($cols === 2 ? 'col-md-6' : 'col-lg-4 col-md-6');
                $html .= '<section class="builder-section builder-cards ' . $pad . '" style="' . $bg . $tc . '">';
                $html .= '<div class="container">';
                if (!empty($b['title']) || !empty($b['tag'])) {
                    $html .= '<div class="text-center mb-5" style="max-width:700px;margin:0 auto;">';
                    if (!empty($b['tag'])) $html .= '<span class="section-tag mb-2">' . htmlspecialchars($b['tag']) . '</span>';
                    if (!empty($b['title'])) $html .= '<h2 class="section-title mb-2" style="' . $tc . '">' . htmlspecialchars($b['title']) . '</h2>';
                    if (!empty($b['subtitle'])) $html .= '<p class="text-muted">' . htmlspecialchars($b['subtitle']) . '</p>';
                    $html .= '</div>';
                }
                $html .= '<div class="row g-4">';
                $cards = is_array($b['cards'] ?? null) ? $b['cards'] : [];
                foreach ($cards as $c) {
                    $html .= '<div class="' . $col_cls . '">';
                    $html .= '<div class="card-lpm h-100 p-4" style="background:#fff;border:1px solid var(--border);border-radius:var(--radius-md);box-shadow:var(--shadow-sm);transition:var(--transition);">';
                    if (!empty($c['icon'])) {
                        $html .= '<div style="width:48px;height:48px;border-radius:12px;background:rgba(106,27,154,0.1);display:flex;align-items:center;justify-content:center;color:var(--purple);font-size:1.4rem;margin-bottom:1.25rem;"><i class="bi ' . htmlspecialchars($c['icon']) . '"></i></div>';
                    }
                    if (!empty($c['badge'])) {
                        $html .= '<span class="badge bg-light text-primary mb-2" style="font-size:0.75rem;">' . htmlspecialchars($c['badge']) . '</span>';
                    }
                    if (!empty($c['title'])) {
                        $html .= '<h5 style="font-weight:700;color:var(--navy);margin-bottom:0.5rem;">' . htmlspecialchars($c['title']) . '</h5>';
                    }
                    if (!empty($c['desc'])) {
                        $html .= '<p style="color:var(--text-muted);font-size:0.9rem;line-height:1.6;margin:0;">' . nl2br(htmlspecialchars($c['desc'])) . '</p>';
                    }
                    $html .= '</div></div>';
                }
                $html .= '</div></div></section>';
                break;

            case 'cta':
                $html .= '<section class="builder-section builder-cta ' . $pad . ' text-center" style="' . $bg . $tc . '">';
                $html .= '<div class="container" style="max-width:850px;">';
                if (!empty($b['title'])) {
                    $html .= '<h2 class="mb-3" style="font-weight:800;color:' . (!empty($b['text_color']) ? htmlspecialchars($b['text_color']) : '#ffffff') . ';">' . htmlspecialchars($b['title']) . '</h2>';
                }
                if (!empty($b['subtitle'])) {
                    $html .= '<p class="lead mb-4" style="opacity:0.9;color:' . (!empty($b['text_color']) ? htmlspecialchars($b['text_color']) : '#ffffff') . ';">' . htmlspecialchars($b['subtitle']) . '</p>';
                }
                if (!empty($b['btn_text'])) {
                    $html .= '<a href="' . htmlspecialchars($b['btn_link'] ?? '#') . '" class="btn-hero-primary" style="background:#ffffff;color:var(--navy);font-weight:700;padding:0.75rem 2rem;">' . htmlspecialchars($b['btn_text']) . '</a>';
                }
                $html .= '</div></section>';
                break;

            case 'accordion':
                $acc_id = 'builder_acc_' . $idx;
                $html .= '<section class="builder-section builder-faq ' . $pad . '" style="' . $bg . $tc . '">';
                $html .= '<div class="container" style="max-width:800px;">';
                if (!empty($b['title'])) {
                    $html .= '<div class="text-center mb-4">';
                    if (!empty($b['tag'])) $html .= '<span class="section-tag mb-2">' . htmlspecialchars($b['tag']) . '</span>';
                    $html .= '<h2 class="section-title mb-2" style="' . $tc . '">' . htmlspecialchars($b['title']) . '</h2>';
                    $html .= '</div>';
                }
                $html .= '<div class="accordion" id="' . $acc_id . '">';
                $items = is_array($b['items'] ?? null) ? $b['items'] : [];
                foreach ($items as $qidx => $it) {
                    $item_id = $acc_id . '_item_' . $qidx;
                    $html .= '<div class="accordion-item mb-2" style="border:1px solid var(--border);border-radius:10px;overflow:hidden;">';
                    $html .= '<h2 class="accordion-header"><button class="accordion-button ' . ($qidx === 0 ? '' : 'collapsed') . '" type="button" data-bs-toggle="collapse" data-bs-target="#' . $item_id . '" style="font-weight:600;font-size:0.95rem;">' . htmlspecialchars($it['question'] ?? 'Pertanyaan') . '</button></h2>';
                    $html .= '<div id="' . $item_id . '" class="accordion-collapse collapse ' . ($qidx === 0 ? 'show' : '') . '" data-bs-parent="#' . $acc_id . '">';
                    $html .= '<div class="accordion-body" style="font-size:0.92rem;line-height:1.7;color:var(--text-muted);">' . nl2br(htmlspecialchars($it['answer'] ?? '')) . '</div>';
                    $html .= '</div></div>';
                }
                $html .= '</div></div></section>';
                break;

            case 'hyperlink':
                $layout     = $b['layout'] ?? 'grid-3';
                $shape      = $b['btn_shape'] ?? 'rounded';
                $style_mode = $b['btn_style'] ?? 'solid';
                $btn_size   = $b['btn_size'] ?? 'normal';
                $btn_bg     = !empty($b['btn_bg_color']) ? htmlspecialchars($b['btn_bg_color']) : '#1E3A8A';
                $btn_tc     = !empty($b['btn_text_color']) ? htmlspecialchars($b['btn_text_color']) : '#FFFFFF';
                $links      = is_array($b['links'] ?? null) ? $b['links'] : [];

                $radius = ($shape === 'pill') ? '50px' : (($shape === 'square') ? '2px' : '10px');

                if ($btn_size === 'large') {
                    $pad_btn = '0.9rem 1.75rem';
                    $fs_btn  = '1.05rem';
                } elseif ($btn_size === 'small') {
                    $pad_btn = '0.4rem 0.9rem';
                    $fs_btn  = '0.82rem';
                } else {
                    $pad_btn = '0.65rem 1.25rem';
                    $fs_btn  = '0.92rem';
                }

                $html .= '<section class="builder-section builder-hyperlinks ' . $pad . '" style="' . $bg . $tc . '">';
                $html .= '<div class="container">';

                if (!empty($b['title']) || !empty($b['badge'])) {
                    $html .= '<div class="text-center mb-4" style="max-width:750px;margin:0 auto;">';
                    if (!empty($b['badge'])) {
                        $html .= '<span class="section-tag mb-2">' . htmlspecialchars($b['badge']) . '</span>';
                    }
                    if (!empty($b['title'])) {
                        $html .= '<h2 class="section-title mb-2" style="' . $tc . '">' . htmlspecialchars($b['title']) . '</h2>';
                    }
                    if (!empty($b['subtitle'])) {
                        $html .= '<p class="text-muted">' . nl2br(htmlspecialchars($b['subtitle'])) . '</p>';
                    }
                    $html .= '</div>';
                }

                if ($layout === 'flex-wrap') {
                    $html .= '<div class="d-flex flex-wrap justify-content-center align-items-center gap-3">';
                } elseif ($layout === 'list') {
                    $html .= '<div class="vstack gap-3 mx-auto" style="max-width:720px;">';
                } else {
                    $cols_num = ($layout === 'grid-4') ? 4 : (($layout === 'grid-2') ? 2 : 3);
                    $col_cls = ($cols_num === 4) ? 'col-lg-3 col-md-6' : (($cols_num === 2) ? 'col-md-6' : 'col-lg-4 col-md-6');
                    $html .= '<div class="row g-3 justify-content-center">';
                }

                foreach ($links as $l) {
                    $l_title  = htmlspecialchars($l['title'] ?? 'Tautan');
                    $l_url    = htmlspecialchars($l['url'] ?? '#');
                    $l_desc   = !empty($l['desc']) ? htmlspecialchars($l['desc']) : '';
                    $l_icon   = !empty($l['icon']) ? htmlspecialchars($l['icon']) : 'bi-link-45deg';
                    $l_target = (!empty($l['target']) && $l['target'] === '_self') ? '_self' : '_blank';
                    $rel      = ($l_target === '_blank') ? ' rel="noopener noreferrer"' : '';

                    if ($style_mode === 'card') {
                        $card_style = 'display:flex;align-items:center;gap:14px;padding:1.1rem 1.35rem;border-radius:' . $radius . ';background:#ffffff;border:1.5px solid #E2E8F0;text-decoration:none;transition:all 0.25s ease;box-shadow:0 2px 10px rgba(0,0,0,0.03);';
                        $item_inner = '
                        <a href="' . $l_url . '" target="' . $l_target . '"' . $rel . ' class="builder-link-card hover-lift" style="' . $card_style . '">
                            <div style="width:44px;height:44px;border-radius:' . ($shape === 'pill' ? '50%' : '10px') . ';background:' . $btn_bg . ';color:' . $btn_tc . ';display:flex;align-items:center;justify-content:center;font-size:1.25rem;flex-shrink:0;">
                                <i class="bi ' . $l_icon . '"></i>
                            </div>
                            <div style="flex:1;min-width:0;">
                                <div style="font-weight:700;color:var(--navy);font-size:' . $fs_btn . ';line-height:1.3;" class="text-truncate">' . $l_title . '</div>
                                ' . ($l_desc ? '<div style="font-size:0.78rem;color:#64748B;line-height:1.4;margin-top:2px;" class="text-truncate">' . $l_desc . '</div>' : '') . '
                            </div>
                            <i class="bi bi-arrow-right text-muted" style="font-size:1.1rem;flex-shrink:0;"></i>
                        </a>';
                    } elseif ($style_mode === 'outline') {
                        $btn_css = 'display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:' . $pad_btn . ';border-radius:' . $radius . ';font-size:' . $fs_btn . ';font-weight:700;text-decoration:none;transition:all 0.25s ease;border:2px solid ' . $btn_bg . ';color:' . $btn_bg . ';background:transparent;';
                        $item_inner = '<a href="' . $l_url . '" target="' . $l_target . '"' . $rel . ' class="builder-btn-outline" style="' . $btn_css . '"><i class="bi ' . $l_icon . '"></i><span>' . $l_title . '</span>' . ($l_target === '_blank' ? '<i class="bi bi-box-arrow-up-right" style="font-size:0.75em;opacity:0.7;"></i>' : '') . '</a>';
                    } elseif ($style_mode === 'soft') {
                        $btn_css = 'display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:' . $pad_btn . ';border-radius:' . $radius . ';font-size:' . $fs_btn . ';font-weight:700;text-decoration:none;transition:all 0.25s ease;background:rgba(30,58,138,0.08);color:' . $btn_bg . ';border:1px solid rgba(30,58,138,0.15);';
                        $item_inner = '<a href="' . $l_url . '" target="' . $l_target . '"' . $rel . ' class="builder-btn-soft" style="' . $btn_css . '"><i class="bi ' . $l_icon . '"></i><span>' . $l_title . '</span>' . ($l_target === '_blank' ? '<i class="bi bi-box-arrow-up-right" style="font-size:0.75em;opacity:0.7;"></i>' : '') . '</a>';
                    } else {
                        $btn_css = 'display:inline-flex;align-items:center;justify-content:center;gap:8px;padding:' . $pad_btn . ';border-radius:' . $radius . ';font-size:' . $fs_btn . ';font-weight:700;text-decoration:none;transition:all 0.25s ease;background:' . $btn_bg . ';color:' . $btn_tc . ';border:none;box-shadow:0 3px 10px rgba(0,0,0,0.08);';
                        $item_inner = '<a href="' . $l_url . '" target="' . $l_target . '"' . $rel . ' class="builder-btn-solid" style="' . $btn_css . '"><i class="bi ' . $l_icon . '"></i><span>' . $l_title . '</span>' . ($l_target === '_blank' ? '<i class="bi bi-box-arrow-up-right" style="font-size:0.75em;opacity:0.8;"></i>' : '') . '</a>';
                    }

                    if ($layout === 'flex-wrap' || $layout === 'list') {
                        $html .= $item_inner;
                    } else {
                        $html .= '<div class="' . $col_cls . '">' . ($style_mode !== 'card' ? '<div class="w-100">' . str_replace('display:inline-flex', 'display:flex;width:100%', $item_inner) . '</div>' : $item_inner) . '</div>';
                    }
                }

                if ($layout === 'flex-wrap' || $layout === 'list') {
                    $html .= '</div>';
                } else {
                    $html .= '</div>';
                }

                $html .= '</div></section>';
                break;

            case 'rich_text':
            default:
                $html .= '<section class="builder-section builder-rich ' . $pad . '" style="' . $bg . $tc . '">';
                $html .= '<div class="container">';
                $html .= '<div class="page-rendered-content" style="' . $tc . ';line-height:1.8;">' . ($b['content'] ?? '') . '</div>';
                $html .= '</div></section>';
                break;
        }
    }
    return $html;
}

// Helper: Pemetaan nama personel dinamis untuk bagan struktur organisasi LPM
function getStrukturLpmMap(array $timList = []): array {
    if (empty($timList)) {
        try {
            $db = getDB();
            $timList = $db->query("SELECT * FROM tim_lpm WHERE kategori = 'lpm' ORDER BY urutan ASC, id ASC")->fetchAll();
        } catch (Exception $e) {
            $timList = [];
        }
    }

    $map = [
        'rektor' => 'Dr. Ferdinandus Hindiarto, M.Si.',
        'wr1' => 'Robertus Setiawan Aji N., S.T., M.CompIT., Ph.D.',
        'kepala' => '',
        'kepala_lpm' => '',
        'sekretaris' => '',
        'staf_tu' => '',
        'ka_ppspm' => '',
        'ka_ami' => '',
        'ka_pemeringkatan' => '',
        'tenaga_ahli' => 'Profesional Pendukung'
    ];

    foreach ($timList as $t) {
        $nama = trim($t['nama'] ?? '');
        $jab  = strtolower($t['jabatan'] ?? '');
        $lvl  = strtolower($t['level'] ?? '');

        if (empty($map['kepala']) && ($lvl === 'pimpinan' || strpos($jab, 'kepala') !== false)) {
            $map['kepala'] = $nama;
        } elseif (empty($map['sekretaris']) && ($lvl === 'sekretaris' || strpos($jab, 'sekretaris') !== false)) {
            $map['sekretaris'] = $nama;
        } elseif (empty($map['ka_ppspm']) && (strpos($jab, 'pspm') !== false || strpos($jab, 'ppspm') !== false || strpos($jab, 'pengembangan') !== false || strpos($jab, 'sistem penjaminan') !== false)) {
            $map['ka_ppspm'] = $nama;
        } elseif (empty($map['ka_ami']) && (strpos($jab, 'ami') !== false || strpos($jab, 'audit') !== false)) {
            $map['ka_ami'] = $nama;
        } elseif (empty($map['ka_pemeringkatan']) && strpos($jab, 'pemeringkatan') !== false) {
            $map['ka_pemeringkatan'] = $nama;
        } elseif (empty($map['staf_tu']) && ($lvl === 'staf' || strpos($jab, 'tata usaha') !== false || preg_match('/\b(staf|tu)\b/i', $jab))) {
            $map['staf_tu'] = $nama;
        } elseif (empty($map['tenaga_ahli']) && (strpos($jab, 'ahli') !== false || strpos($lvl, 'ahli') !== false)) {
            $map['tenaga_ahli'] = $nama;
        }
    }

    // Default fallbacks jika kosong di database
    if (empty($map['kepala'])) $map['kepala'] = getPengaturan('sambutan_nama', 'Stefani Lily Indarto, SE., MM., Ak., CA., CPA.');
    $map['kepala_lpm'] = $map['kepala'];
    if (empty($map['sekretaris'])) $map['sekretaris'] = 'Vera Retnowati, ST., MM.';
    if (empty($map['staf_tu'])) $map['staf_tu'] = 'Hermawan, S.M.';
    if (empty($map['ka_ppspm'])) $map['ka_ppspm'] = 'Ir. I.M. Tri Hesti Mulyani, MT.';
    if (empty($map['ka_ami'])) $map['ka_ami'] = 'dr. Maya Yanuarty, M.Biomed';
    if (empty($map['ka_pemeringkatan'])) $map['ka_pemeringkatan'] = 'Ir. Lintang Jata Angghita, ST., M.Ling';
    if (empty($map['tenaga_ahli'])) $map['tenaga_ahli'] = 'Profesional Pendukung';

    return $map;
}

/**
 * Helper untuk mendapatkan URL foto personel (tim_lpm) dengan auto-fallback ekstensi
 */
function getTimFotoUrl(?string $foto): string {
    if (empty($foto)) return '';
    $dir = __DIR__ . '/../uploads/tim/';
    if (file_exists($dir . $foto)) {
        return SITE_URL . '/uploads/tim/' . $foto;
    }
    $base = pathinfo($foto, PATHINFO_FILENAME);
    foreach (['webp', 'jpg', 'jpeg', 'png', 'JPG', 'PNG', 'WEBP'] as $ext) {
        if (file_exists($dir . $base . '.' . $ext)) {
            return SITE_URL . '/uploads/tim/' . $base . '.' . $ext;
        }
    }
    return SITE_URL . '/uploads/tim/' . $foto;
}

// Helper: Check Accreditation Expirations (institusi & prodi)
function checkAccreditationExpirations(): array {
    $alerts = [
        'expired' => [],
        'warning' => [], // <= 730 days remaining
        'total' => 0
    ];
    
    try {
        $db = getDB();
        $today = new DateTime();

        // 1. Institutional Accreditation
        $inst_mb = getPengaturan('akred_institusi_masa_berlaku', '');
        if ($inst_mb) {
            $date = new DateTime($inst_mb);
            $diff = (int)$today->diff($date)->format("%r%a");
            if ($diff <= 0) {
                $alerts['expired'][] = [
                    'tipe' => 'Institusi',
                    'nama' => 'Universitas Katolik Soegijapranata',
                    'peringkat' => getPengaturan('akred_institusi_peringkat', 'UNGGUL'),
                    'tanggal' => $date->format('Y-m-d'),
                    'days' => abs($diff),
                    'link' => SITE_URL . '/admin/akreditasi-institusi.php'
                ];
            } else if ($diff <= 730) {
                $alerts['warning'][] = [
                    'tipe' => 'Institusi',
                    'nama' => 'Universitas Katolik Soegijapranata',
                    'peringkat' => getPengaturan('akred_institusi_peringkat', 'UNGGUL'),
                    'tanggal' => $date->format('Y-m-d'),
                    'days' => $diff,
                    'link' => SITE_URL . '/admin/akreditasi-institusi.php'
                ];
            }
        }

        // 2. Prodi Accreditation
        $prodis = $db->query("SELECT * FROM akreditasi_prodi WHERE masa_berlaku IS NOT NULL AND masa_berlaku > '1970-01-01'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($prodis as $p) {
            $date = new DateTime($p['masa_berlaku']);
            $diff = (int)$today->diff($date)->format("%r%a");
            if ($diff <= 0) {
                $alerts['expired'][] = [
                    'tipe' => 'Prodi',
                    'nama' => $p['strata'] . ' ' . $p['program_studi'] . ' (' . $p['fakultas'] . ')',
                    'peringkat' => $p['peringkat'],
                    'tanggal' => $date->format('Y-m-d'),
                    'days' => abs($diff),
                    'link' => SITE_URL . '/admin/akreditasi-prodi-form.php?id=' . $p['id']
                ];
            } else if ($diff <= 730) {
                $alerts['warning'][] = [
                    'tipe' => 'Prodi',
                    'nama' => $p['strata'] . ' ' . $p['program_studi'] . ' (' . $p['fakultas'] . ')',
                    'peringkat' => $p['peringkat'],
                    'tanggal' => $date->format('Y-m-d'),
                    'days' => $diff,
                    'link' => SITE_URL . '/admin/akreditasi-prodi-form.php?id=' . $p['id']
                ];
            }
        }

        // 3. Lembaga Akreditasi (7 LAM + BAN PT)
        $lembagas = $db->query("SELECT * FROM lembaga_akreditasi WHERE masa_berlaku IS NOT NULL AND masa_berlaku > '1970-01-01'")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($lembagas as $l) {
            $date = new DateTime($l['masa_berlaku']);
            $diff = (int)$today->diff($date)->format("%r%a");
            if ($diff <= 0) {
                $alerts['expired'][] = [
                    'tipe' => 'Lembaga',
                    'nama' => $l['kode'] . ' - ' . $l['nama'],
                    'peringkat' => 'Akreditasi',
                    'tanggal' => $date->format('Y-m-d'),
                    'days' => abs($diff),
                    'link' => SITE_URL . '/admin/lembaga-akreditasi-form.php?id=' . $l['id']
                ];
            } else if ($diff <= 730) {
                $alerts['warning'][] = [
                    'tipe' => 'Lembaga',
                    'nama' => $l['kode'] . ' - ' . $l['nama'],
                    'peringkat' => 'Akreditasi',
                    'tanggal' => $date->format('Y-m-d'),
                    'days' => $diff,
                    'link' => SITE_URL . '/admin/lembaga-akreditasi-form.php?id=' . $l['id']
                ];
            }
        }

        $alerts['total'] = count($alerts['expired']) + count($alerts['warning']);

        // Count per tipe for notification dots
        $alerts['prodi_count'] = 0;
        $alerts['lembaga_count'] = 0;
        $alerts['institusi_count'] = 0;

        foreach (array_merge($alerts['expired'], $alerts['warning']) as $al) {
            if ($al['tipe'] === 'Prodi') $alerts['prodi_count']++;
            elseif ($al['tipe'] === 'Lembaga') $alerts['lembaga_count']++;
            elseif ($al['tipe'] === 'Institusi') $alerts['institusi_count']++;
        }
        $alerts['has_prodi_alert'] = ($alerts['prodi_count'] > 0);
        $alerts['has_lembaga_alert'] = ($alerts['lembaga_count'] > 0);
        $alerts['has_institusi_alert'] = ($alerts['institusi_count'] > 0);

    } catch (Exception $e) {}

    return $alerts;
}

// Helper: Kirim notifikasi email aspirasi/feedback
function kirimNotifikasiEmailAspirasi(string $nama, string $email_pengirim, string $jenis, string $instansi, string $pesan): bool {
    try {
        $tujuan = getPengaturan('email', 'lpm@unika.ac.id');
        $site_title = getPengaturan('site_title', 'LPM UNIKA');
        $subject = "[Aspirasi Baru] $jenis - dari " . strip_tags($nama);

        $headers  = "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        $headers .= "From: LPM Website <noreply@unika.ac.id>\r\n";
        $headers .= "Reply-To: " . strip_tags($email_pengirim) . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        $waktu = date('d F Y, H:i') . ' WIB';
        $body = '
        <!DOCTYPE html>
        <html>
        <head><meta charset="utf-8"></head>
        <body style="font-family:sans-serif;color:#1E293B;background:#f8fafc;padding:20px;">
            <div style="max-width:600px;margin:0 auto;background:#fff;border-radius:8px;border:1px solid #e2e8f0;padding:24px;">
                <h3 style="color:#0A192F;margin-top:0;border-bottom:2px solid #4A148C;padding-bottom:12px;">Pemberitahuan Aspirasi &amp; Pengajuan Baru</h3>
                <p>Website ' . htmlspecialchars($site_title) . ' telah menerima pengajuan aspirasi/layanan mutu dengan rincian sebagai berikut:</p>
                <table style="width:100%;border-collapse:collapse;margin:16px 0;font-size:14px;">
                    <tr><td style="padding:8px 0;font-weight:bold;width:140px;color:#64748B;">Nama Pengirim:</td><td style="padding:8px 0;font-weight:600;">' . htmlspecialchars($nama) . '</td></tr>
                    <tr><td style="padding:8px 0;font-weight:bold;color:#64748B;">Alamat Email:</td><td style="padding:8px 0;"><a href="mailto:' . htmlspecialchars($email_pengirim) . '">' . htmlspecialchars($email_pengirim) . '</a></td></tr>
                    <tr><td style="padding:8px 0;font-weight:bold;color:#64748B;">Instansi/Unit:</td><td style="padding:8px 0;">' . htmlspecialchars($instansi ?: '-') . '</td></tr>
                    <tr><td style="padding:8px 0;font-weight:bold;color:#64748B;">Jenis Layanan:</td><td style="padding:8px 0;"><span style="background:#EDE7F6;color:#4A148C;padding:3px 8px;border-radius:4px;font-weight:bold;">' . htmlspecialchars($jenis ?: 'Umum') . '</span></td></tr>
                    <tr><td style="padding:8px 0;font-weight:bold;color:#64748B;">Waktu Kirim:</td><td style="padding:8px 0;">' . $waktu . '</td></tr>
                </table>
                <div style="background:#F1F5F9;border-left:4px solid #4A148C;padding:14px;border-radius:4px;margin-top:16px;">
                    <div style="font-weight:bold;font-size:13px;color:#475569;margin-bottom:6px;">Isi Aspirasi / Pesan:</div>
                    <div style="font-size:14px;line-height:1.6;white-space:pre-wrap;">' . htmlspecialchars($pesan) . '</div>
                </div>
                <p style="margin-top:24px;font-size:12px;color:#94a3b8;text-align:center;">Email ini dikirim secara otomatis dari formulir Aspirasi / Layanan Website LPM UNIKA.</p>
            </div>
        </body>
        </html>';

        // Panggil fungsi mail() bawaan PHP
        @mail($tujuan, $subject, $body, $headers);
        return true;
    } catch (Exception $e) {
        return false;
    }
}

// Helper: Record Visitor Log
function recordVisitorLog(): void {
    static $recorded = false;
    if ($recorded) return;
    $recorded = true;

    try {
        $uri = $_SERVER['REQUEST_URI'] ?? '';
        if (strpos($uri, '/admin/') !== false) return;

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 250);
        $url = substr($uri, 0, 250);
        $date = date('Y-m-d');

        $db = getDB();
        $stmt = $db->prepare("INSERT INTO visitor_logs (ip_address, user_agent, page_url, visit_date) VALUES (?, ?, ?, ?)");
        $stmt->execute([$ip, $ua, $url, $date]);
    } catch (Exception $e) {}
}

// Helper: Get Visitor Statistics with Multi-Period Chart Data
function getVisitorStats(): array {
    $stats = [
        'today_visitors' => 0,
        'today_hits'     => 0,
        'month_visitors' => 0,
        'total_hits'     => 0,
        'chart_14d'      => ['labels' => [], 'hits' => [], 'visitors' => []],
        'chart_30d'      => ['labels' => [], 'hits' => [], 'visitors' => []],
        'chart_1y'       => ['labels' => [], 'hits' => [], 'visitors' => []],
    ];

    try {
        $db = getDB();
        $today = date('Y-m-d');
        $month = date('Y-m');

        // Today unique visitors
        $stmt = $db->prepare("SELECT COUNT(DISTINCT ip_address) FROM visitor_logs WHERE visit_date = ?");
        $stmt->execute([$today]);
        $stats['today_visitors'] = (int)$stmt->fetchColumn();

        // Today total hits
        $stmt = $db->prepare("SELECT COUNT(*) FROM visitor_logs WHERE visit_date = ?");
        $stmt->execute([$today]);
        $stats['today_hits'] = (int)$stmt->fetchColumn();

        // Month unique visitors
        $stmt = $db->prepare("SELECT COUNT(DISTINCT ip_address) FROM visitor_logs WHERE visit_date LIKE ?");
        $stmt->execute([$month . '-%']);
        $stats['month_visitors'] = (int)$stmt->fetchColumn();

        // Total hits
        $stats['total_hits'] = (int)$db->query("SELECT COUNT(*) FROM visitor_logs")->fetchColumn();

        // 1. Data 14 Hari Terakhir
        $startDate14 = date('Y-m-d', strtotime('-13 days'));
        $stmt14 = $db->prepare("SELECT visit_date, COUNT(*) as hits, COUNT(DISTINCT ip_address) as visitors FROM visitor_logs WHERE visit_date >= ? GROUP BY visit_date ORDER BY visit_date ASC");
        $stmt14->execute([$startDate14]);
        $map14 = [];
        while ($r = $stmt14->fetch(PDO::FETCH_ASSOC)) {
            $map14[$r['visit_date']] = ['hits' => (int)$r['hits'], 'visitors' => (int)$r['visitors']];
        }
        for ($i = 13; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $stats['chart_14d']['labels'][]   = date('d M', strtotime($d));
            $stats['chart_14d']['hits'][]     = $map14[$d]['hits'] ?? 0;
            $stats['chart_14d']['visitors'][] = $map14[$d]['visitors'] ?? 0;
        }

        // 2. Data 30 Hari (1 Bulan) Terakhir
        $startDate30 = date('Y-m-d', strtotime('-29 days'));
        $stmt30 = $db->prepare("SELECT visit_date, COUNT(*) as hits, COUNT(DISTINCT ip_address) as visitors FROM visitor_logs WHERE visit_date >= ? GROUP BY visit_date ORDER BY visit_date ASC");
        $stmt30->execute([$startDate30]);
        $map30 = [];
        while ($r = $stmt30->fetch(PDO::FETCH_ASSOC)) {
            $map30[$r['visit_date']] = ['hits' => (int)$r['hits'], 'visitors' => (int)$r['visitors']];
        }
        for ($i = 29; $i >= 0; $i--) {
            $d = date('Y-m-d', strtotime("-$i days"));
            $stats['chart_30d']['labels'][]   = date('d M', strtotime($d));
            $stats['chart_30d']['hits'][]     = $map30[$d]['hits'] ?? 0;
            $stats['chart_30d']['visitors'][] = $map30[$d]['visitors'] ?? 0;
        }

        // 3. Data 12 Bulan (1 Tahun) Terakhir
        $startDate1y = date('Y-m-01', strtotime('-11 months'));
        $stmt1y = $db->prepare("SELECT DATE_FORMAT(visit_date, '%Y-%m') as ym, COUNT(*) as hits, COUNT(DISTINCT ip_address) as visitors FROM visitor_logs WHERE visit_date >= ? GROUP BY ym ORDER BY ym ASC");
        $stmt1y->execute([$startDate1y]);
        $map1y = [];
        while ($r = $stmt1y->fetch(PDO::FETCH_ASSOC)) {
            $map1y[$r['ym']] = ['hits' => (int)$r['hits'], 'visitors' => (int)$r['visitors']];
        }
        for ($i = 11; $i >= 0; $i--) {
            $ym = date('Y-m', strtotime("-$i months"));
            $stats['chart_1y']['labels'][]   = date('M Y', strtotime($ym . '-01'));
            $stats['chart_1y']['hits'][]     = $map1y[$ym]['hits'] ?? 0;
            $stats['chart_1y']['visitors'][] = $map1y[$ym]['visitors'] ?? 0;
        }

    } catch (Exception $e) {}

    return $stats;
}

// Helper: Generate Unique Feedback Token
function generateUniqueFeedbackToken(): string {
    $db = getDB();
    $chars = '23456789ABCDEFGHJKLMNPQRSTUVWXYZ';
    do {
        $token = 'LPM-';
        for ($i = 0; $i < 6; $i++) {
            $token .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $exists = (int)$db->query("SELECT COUNT(*) FROM kunjungan_feedback_token WHERE token = " . $db->quote($token))->fetchColumn();
    } while ($exists > 0);

    return $token;
}

/**
 * Helper: Convert first page of PDF to image or return existing image preview.
 * Returns relative filename of the image (or empty string on failure).
 */
/**
 * Helper: Convert first page of PDF to image or return existing image preview.
 * Returns relative filename of the image (or empty string on failure).
 */
function getOrGeneratePdfPreview(string $fileName, string $subDir = 'penghargaan'): string {
    if (empty($fileName)) {
        return '';
    }
    
    $baseDir = UPLOAD_PATH . trim($subDir, '/') . '/';
    $ext = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    $baseNoExt = pathinfo($fileName, PATHINFO_FILENAME);
    
    // 1. Jika fileName adalah gambar (jpg, jpeg, png, webp)
    if (in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
        if (file_exists($baseDir . $fileName) && filesize($baseDir . $fileName) > 0) {
            return $fileName;
        }
        // Cek jika sudah dikonversi ke .webp
        if (file_exists($baseDir . $baseNoExt . '.webp') && filesize($baseDir . $baseNoExt . '.webp') > 0) {
            return $baseNoExt . '.webp';
        }
        if (file_exists($baseDir . $baseNoExt . '.jpg') && filesize($baseDir . $baseNoExt . '.jpg') > 0) {
            return $baseNoExt . '.jpg';
        }
        return $fileName;
    }
    
    // 2. Jika fileName adalah PDF, cari thumbnail yang sudah ada
    if ($ext === 'pdf') {
        $candidates = [
            'thumb_' . $baseNoExt . '.webp',
            'thumb_' . $baseNoExt . '.jpg',
            'thumb_' . $baseNoExt . '.png',
            $baseNoExt . '.webp',
            $baseNoExt . '.jpg',
            $baseNoExt . '.png'
        ];
        foreach ($candidates as $cand) {
            if (file_exists($baseDir . $cand) && filesize($baseDir . $cand) > 0) {
                return $cand;
            }
        }
    }
    
    return '';
}

/**
 * Helper: Get or automatically generate Buletin cover image from PDF first page.
 * Checks uploads/buletin/covers/ for existing cover.
 * Returns relative filename of cover (e.g. 'cov_xxx.webp' or 'cov_xxx.jpg') or empty string.
 */
function getOrGenerateBuletinCover(?string $coverPath, ?string $filePath, int $buletinId = 0): string {
    $coversDir = UPLOAD_PATH . 'buletin/covers/';
    
    // 1. Cek jika coverPath diberikan
    if (!empty($coverPath)) {
        if (file_exists($coversDir . $coverPath) && filesize($coversDir . $coverPath) > 0) {
            return $coverPath;
        }
        $base = pathinfo($coverPath, PATHINFO_FILENAME);
        if (file_exists($coversDir . $base . '.webp') && filesize($coversDir . $base . '.webp') > 0) {
            return $base . '.webp';
        }
        if (file_exists($coversDir . $base . '.jpg') && filesize($coversDir . $base . '.jpg') > 0) {
            return $base . '.jpg';
        }
    }
    
    // 2. Cek kandidat dari nama PDF
    if (!empty($filePath)) {
        $baseName = pathinfo($filePath, PATHINFO_FILENAME);
        $candidates = [
            'cov_' . $baseName . '.webp',
            'cov_' . $baseName . '.jpg',
            'cov_' . $baseName . '.png',
            $baseName . '.webp',
            $baseName . '.jpg',
            $baseName . '.png'
        ];
        foreach ($candidates as $cand) {
            if (file_exists($coversDir . $cand) && filesize($coversDir . $cand) > 0) {
                return $cand;
            }
        }
    }
    
    return '';
}

/**
 * Helper: Mengambil data ranking EduRank secara real-time dengan Smart Caching
 * 
 * @param bool $forceRefresh Paksa refresh data langsung dari EduRank
 * @return array Data ranking Indonesia, Semarang, Asia, Dunia
 */
function getEduRankRankings(bool $forceRefresh = false): array {
    $cacheDir  = __DIR__ . '/../uploads/';
    $cacheFile = $cacheDir . 'cache_edurank.json';
    $cacheTime = 21600; // 6 jam cache
    $url       = 'https://edurank.org/uni/soegijapranata-catholic-university/rankings/';

    $default = [
        'indonesia' => ['rank' => 65, 'total' => '562', 'formatted' => '#65 of 562'],
        'semarang'  => ['rank' => 3,  'total' => '14',  'formatted' => '#3 of 14'],
        'asia'      => ['rank' => 1242, 'total' => '5,830', 'formatted' => '#1242 of 5,830'],
        'world'     => ['rank' => 3818, 'total' => '14,131', 'formatted' => '#3818 of 14,131'],
        'url'       => $url,
        'updated_at'=> date('Y-m-d H:i:s'),
        'from_cache'=> true
    ];

    if (!$forceRefresh && file_exists($cacheFile) && (time() - filemtime($cacheFile) < $cacheTime)) {
        $cached = json_decode(@file_get_contents($cacheFile), true);
        if (is_array($cached) && !empty($cached['indonesia']['rank'])) {
            return $cached;
        }
    }

    // Fetch live dari EduRank via cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 6);
    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/122.0.0.0 Safari/537.36');
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);

    $html     = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($html && $httpCode === 200) {
        preg_match('/<span class="ranks__place">(\d+)<\/span>\s*<span class="ranks__of">of\s*([\d,]+)<\/span>\s*<\/td>\s*<th[^>]*>In\s*<a[^>]*>Indonesia<\/a>/i', $html, $mId);
        preg_match('/<span class="ranks__place">(\d+)<\/span>\s*<span class="ranks__of">of\s*([\d,]+)<\/span>\s*<\/td>\s*<th[^>]*>In\s*<a[^>]*>Semarang<\/a>/i', $html, $mSmg);
        preg_match('/<span class="ranks__place">(\d+)<\/span>\s*<span class="ranks__of">of\s*([\d,]+)<\/span>\s*<\/td>\s*<th[^>]*>In\s*<a[^>]*>Asia<\/a>/i', $html, $mAsia);
        preg_match('/<span class="ranks__place">(\d+)<\/span>\s*<span class="ranks__of">of\s*([\d,]+)<\/span>\s*<\/td>\s*<th[^>]*>In\s*<a[^>]*>the World<\/a>/i', $html, $mWorld);

        $idRank  = !empty($mId[1]) ? (int)$mId[1] : 65;
        $idTotal = !empty($mId[2]) ? trim($mId[2]) : '562';

        $smgRank  = !empty($mSmg[1]) ? (int)$mSmg[1] : 3;
        $smgTotal = !empty($mSmg[2]) ? trim($mSmg[2]) : '14';

        $asiaRank  = !empty($mAsia[1]) ? (int)$mAsia[1] : 1242;
        $asiaTotal = !empty($mAsia[2]) ? trim($mAsia[2]) : '5,830';

        $worldRank  = !empty($mWorld[1]) ? (int)$mWorld[1] : 3818;
        $worldTotal = !empty($mWorld[2]) ? trim($mWorld[2]) : '14,131';

        // Parsing Peringkat Per Bidang Studi / Jurusan (Khusus Tingkat Indonesia)
        $topics = [];
        $dom = new DOMDocument();
        @$dom->loadHTML($html);
        $xpath = new DOMXPath($dom);

        $topicSections = $xpath->query('//div[contains(@class, "block-cont")]');
        foreach ($topicSections as $section) {
            $h2 = $xpath->query('.//h2', $section)->item(0);
            if (!$h2) continue;
            
            $topicTitle = trim(preg_replace('/\s+rankings$/i', '', $h2->textContent));
            $table = $xpath->query('.//table[contains(@class, "table")]', $section)->item(0);
            if (!$table) continue;

            $rows = $xpath->query('.//tbody/tr', $table);
            $subfields = [];
            foreach ($rows as $row) {
                $th = $xpath->query('.//th', $row)->item(0);
                $tds = $xpath->query('.//td', $row);
                if ($th && $tds->length >= 1) {
                    $subfieldName = trim($th->textContent);
                    $indonesiaRank = trim($tds->item(0)->textContent);
                    if ($subfieldName !== '' && is_numeric($indonesiaRank)) {
                        $subfields[] = [
                            'name'           => $subfieldName,
                            'indonesia_rank' => (int)$indonesiaRank
                        ];
                    }
                }
            }

            if (!empty($subfields)) {
                $topics[] = [
                    'category'  => $topicTitle,
                    'subfields' => $subfields
                ];
            }
        }

        $data = [
            'indonesia' => [
                'rank'      => $idRank,
                'total'     => $idTotal,
                'formatted' => '#' . $idRank . ' of ' . $idTotal
            ],
            'semarang' => [
                'rank'      => $smgRank,
                'total'     => $smgTotal,
                'formatted' => '#' . $smgRank . ' of ' . $smgTotal
            ],
            'asia' => [
                'rank'      => $asiaRank,
                'total'     => $asiaTotal,
                'formatted' => '#' . $asiaRank . ' of ' . $asiaTotal
            ],
            'world' => [
                'rank'      => $worldRank,
                'total'     => $worldTotal,
                'formatted' => '#' . $worldRank . ' of ' . $worldTotal
            ],
            'topics'    => $topics,
            'url'       => $url,
            'updated_at'=> date('Y-m-d H:i:s'),
            'from_cache'=> false
        ];

        if (!is_dir($cacheDir)) {
            @mkdir($cacheDir, 0755, true);
        }
        @file_put_contents($cacheFile, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));

        // Sinkronisasi otomatis ke tabel akreditasi jika ada
        try {
            $db = getDB();
            $stmt = $db->prepare("UPDATE akreditasi SET peringkat = ? WHERE id = 1");
            $stmt->execute(['#' . $idRank . ' of ' . $idTotal]);

            $stmt = $db->prepare("UPDATE akreditasi SET peringkat = ? WHERE id = 2");
            $stmt->execute(['#' . $smgRank . ' of ' . $smgTotal]);
        } catch (Exception $e) {}

        return $data;
    }

    if (file_exists($cacheFile)) {
        $cached = json_decode(@file_get_contents($cacheFile), true);
        if (is_array($cached) && !empty($cached['indonesia']['rank'])) {
            return $cached;
        }
    }

    return $default;
}

/**
 * Menghitung Tahun Akademik berdasarkan tanggal tertentu.
 * Tahun akademik dihitung: 1 September tahun N s/d 31 Agustus tahun N+1.
 * Contoh:
 * 2024-09-01 s/d 2025-08-31 => '2024/2025'
 * 2025-09-01 s/d 2026-08-31 => '2025/2026'
 */
function getTahunAkademik(?string $dateStr): ?string {
    if (!$dateStr) return null;
    $time = strtotime($dateStr);
    if (!$time) return null;
    $year  = (int)date('Y', $time);
    $month = (int)date('n', $time);
    if ($month >= 9) {
        return $year . '/' . ($year + 1);
    } else {
        return ($year - 1) . '/' . $year;
    }
}

/**
 * Mendapatkan rentang tanggal awal dan akhir untuk tahun akademik (YYYY/YYYY+1).
 * Contoh input: '2024/2025'
 * Output: ['start' => '2024-09-01', 'end' => '2025-08-31']
 */
function getTahunAkademikDateRange(string $ta): ?array {
    $parts = explode('/', trim($ta));
    if (count($parts) !== 2 || !is_numeric($parts[0]) || !is_numeric($parts[1])) {
        return null;
    }
    $startYear = (int)$parts[0];
    $endYear   = (int)$parts[1];
    return [
        'start' => sprintf('%04d-09-01', $startYear),
        'end'   => sprintf('%04d-08-31', $endYear)
    ];
}

/**
 * Mengambil daftar Tahun Akademik unik yang ada pada data berita/kegiatan di database.
 * Diurutkan dari tahun akademik terbaru ke terlama.
 */
function getDaftarTahunAkademikBerita(): array {
    $taList = [];
    try {
        $db = getDB();
        $stmt = $db->query("SELECT DISTINCT COALESCE(tanggal_publikasi, created_at) AS tgl FROM berita WHERE COALESCE(tanggal_publikasi, created_at) IS NOT NULL ORDER BY tgl DESC");
        $dates = $stmt->fetchAll(PDO::FETCH_COLUMN);
        foreach ($dates as $d) {
            $ta = getTahunAkademik($d);
            if ($ta && !in_array($ta, $taList)) {
                $taList[] = $ta;
            }
        }
    } catch (Exception $e) {}
    
    usort($taList, function($a, $b) {
        $ya = (int)explode('/', $a)[0];
        $yb = (int)explode('/', $b)[0];
        return $yb <=> $ya;
    });

    return $taList;
}

