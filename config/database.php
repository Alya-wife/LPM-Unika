<?php
/**
 * Konfigurasi Database - LPM SCU
 * Koneksi PDO ke MySQL/MariaDB
 */

define('DB_HOST', 'localhost');
define('DB_NAME', 'lpm_scu');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_CHARSET', 'utf8mb4');

define('SITE_NAME', 'LPM UNIKA');
define('SITE_URL', 'http://localhost/LPM');
define('UPLOAD_PATH', __DIR__ . '/../uploads/');
define('UPLOAD_URL', SITE_URL . '/uploads/');

function getDB(): PDO {
    static $pdo = null;
    if ($pdo === null) {
        $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
        $options = [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ];
        try {
            $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        } catch (PDOException $e) {
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
function redirect(string $url): never {
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
function getAllPengaturan(): array {
    static $settings = null;
    if ($settings === null) {
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
}

