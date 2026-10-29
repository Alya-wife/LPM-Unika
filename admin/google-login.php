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

// 2. Ambil data admin dari tabel users
$stmt = $db->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$user = $stmt->fetch(PDO::FETCH_ASSOC);

// Bootstrap fallback untuk akun developer / root kampus awal jika belum terdaftar
$allowed_bootstrap = ['tu.lpm@unika.ac.id', 'ravywhienelda@gmail.com'];
if (!$user && in_array($email, $allowed_bootstrap, true)) {
    // Cari user id 1 untuk dihubungkan
    $root_user = $db->query("SELECT * FROM users WHERE id = 1 LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    if ($root_user) {
        $db->prepare("UPDATE users SET email = ?, nama_lengkap = COALESCE(NULLIF(nama_lengkap, ''), ?), role = 'superadmin', status = 1 WHERE id = 1")->execute([$email, $name]);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        $dummy_password = password_hash(bin2hex(random_bytes(16)), PASSWORD_DEFAULT);
        $db->prepare("INSERT INTO users (id, nama_lengkap, username, email, password, role, akses_halaman, status) VALUES (1, ?, ?, ?, ?, 'superadmin', NULL, 1)")->execute([$name, explode('@', $email)[0], $email, $dummy_password]);
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
    }
}

if (!$user) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Akses Ditolak: Akun Google ' . $email . ' belum didaftarkan oleh Administrator. Silakan hubungi Super Admin untuk menambahkan akun ini.'
    ]);
    exit;
}

if (isset($user['status']) && (int)$user['status'] === 0) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Akses Ditolak: Akun administrator ' . $email . ' sedang dinonaktifkan. Silakan hubungi Super Admin.'
    ]);
    exit;
}

// Perbarui nama atau foto jika belum ada di database
if (empty($user['nama_lengkap']) && !empty($name)) {
    $db->prepare("UPDATE users SET nama_lengkap = ? WHERE id = ?")->execute([$name, $user['id']]);
    $user['nama_lengkap'] = $name;
}

$admin_user_id   = (int)$user['id'];
$admin_user_name = $user['nama_lengkap'] ?: ($name ?: 'Administrator');
$admin_role      = $user['role'] ?? 'admin';
$admin_perms     = json_decode($user['akses_halaman'] ?? '[]', true) ?: [];

// 5. Berikan Sesi Admin
$_SESSION['admin_id']          = $admin_user_id;
$_SESSION['admin_name']        = $admin_user_name;
$_SESSION['admin_email']       = $email;
$_SESSION['admin_picture']     = $picture;
$_SESSION['admin_role']        = $admin_role;
$_SESSION['admin_permissions'] = is_array($admin_perms) ? $admin_perms : [];

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
