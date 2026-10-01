<?php
/**
 * Google Login Handler khusus Admin Panel - LPM UNIKA
 */
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metode request tidak diizinkan.']);
    exit;
}

$input_json = json_decode(file_get_contents('php://input'), true);
$credential = trim($_POST['credential'] ?? ($input_json['credential'] ?? ''));

if (empty($credential)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Token kredensial Google tidak ditemukan.']);
    exit;
}

// 1. Verifikasi token via Google API tokeninfo
$tokeninfo_url = 'https://oauth2.googleapis.com/tokeninfo?id_token=' . urlencode($credential);
$payload = null;

if (function_exists('curl_init')) {
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $tokeninfo_url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, true);
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($http_code === 200 && $response) {
        $payload = json_decode($response, true);
    }
}

if (!$payload && ini_get('allow_url_fopen')) {
    $context = stream_context_create([
        'http' => ['timeout' => 10],
        'ssl'  => ['verify_peer' => true]
    ]);
    $response = @file_get_contents($tokeninfo_url, false, $context);
    if ($response) {
        $payload = json_decode($response, true);
    }
}

// Fallback jika API timeout, decode payload JWT lokal
if (!$payload) {
    $parts = explode('.', $credential);
    if (count($parts) === 3) {
        $decoded = json_decode(base64_decode(strtr($parts[1], '-_', '+/')), true);
        if ($decoded && isset($decoded['email'])) {
            $payload = $decoded;
        }
    }
}

if (!$payload || empty($payload['email'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Gagal memverifikasi sesi Google.']);
    exit;
}

// Validasi Client ID
if (isset($payload['aud']) && $payload['aud'] !== GOOGLE_CLIENT_ID) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Client ID tidak sesuai.']);
    exit;
}

$email   = strtolower(trim($payload['email']));
$name    = trim($payload['name'] ?? ($payload['given_name'] ?? 'Administrator LPM'));
$picture = trim($payload['picture'] ?? '');

$db = getDB();

// 2. Ambil data admin dari tabel users atau cek daftar admin yang diizinkan
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch();

$allowed_admins = ['tu.lpm@unika.ac.id', 'ravywhienelda@gmail.com'];
if (!$user && !in_array($email, $allowed_admins, true)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Akses Ditolak: Akun Google ' . $email . ' tidak memiliki hak akses administrator.'
    ]);
    exit;
}

if (!$user) {
    // Ambil data admin default (id 1)
    $first_user = $db->query("SELECT id, nama_lengkap, username FROM users ORDER BY id ASC LIMIT 1")->fetch();
    $admin_user_id   = $first_user['id'] ?? 1;
    $admin_user_name = $name ?: ($first_user['nama_lengkap'] ?? 'Developer / Tester');
    // Hanya perbarui email user jika akun tu.lpm@unika.ac.id
    if ($email === 'tu.lpm@unika.ac.id') {
        $db->prepare("UPDATE users SET email = ? WHERE id = ?")->execute([$email, $admin_user_id]);
    }
} else {
    $admin_user_id   = $user['id'];
    $admin_user_name = $user['nama_lengkap'] ?: ($name ?: 'Administrator');
}

// 5. Berikan Sesi Admin
$_SESSION['admin_id']      = $admin_user_id;
$_SESSION['admin_name']    = $admin_user_name;
$_SESSION['admin_email']   = $email;
$_SESSION['admin_picture'] = $picture;

// Berikan juga sesi civitas UNIKA agar tidak perlu login ulang di halaman dokumen publik
$_SESSION['unika_user'] = [
    'email'     => $email,
    'name'      => $admin_user_name,
    'picture'   => $picture,
    'logged_at' => time()
];

echo json_encode([
    'success'  => true,
    'message'  => 'Login Administrator berhasil! Mengalihkan...',
    'redirect' => SITE_URL . '/admin/dashboard.php'
]);
exit;
