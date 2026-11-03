<?php
/**
 * Google Authentication Handler khusus Peserta Evaluasi Kinerja - LPM UNIKA
 */
require_once __DIR__ . '/config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$action = $_GET['action'] ?? ($_POST['action'] ?? 'verify');

// 1. Action: Logout
if ($action === 'logout') {
    unset($_SESSION['evaluasi_user']);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => 'Berhasil keluar']);
        exit;
    }
    redirect(SITE_URL . '/evaluasi-kinerja.php');
    exit;
}

// 2. Action: Status
if ($action === 'status') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'   => true,
        'logged_in' => !empty($_SESSION['evaluasi_user']),
        'user'      => $_SESSION['evaluasi_user'] ?? null
    ]);
    exit;
}

// 3. Action: Verify Google Credential
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

// Verifikasi token via Google API tokeninfo
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

// Fallback jika API timeout, coba decode JWT lokal
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
    echo json_encode(['success' => false, 'message' => 'Gagal memverifikasi akun Google. Silakan coba login kembali.']);
    exit;
}

// Validasi Client ID jika dikonfigurasi
if (defined('GOOGLE_CLIENT_ID') && GOOGLE_CLIENT_ID && isset($payload['aud']) && $payload['aud'] !== GOOGLE_CLIENT_ID) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Client ID Google tidak sesuai dengan sistem LPM.']);
    exit;
}

$email = strtolower(trim($payload['email']));
$name  = trim($payload['name'] ?? ($payload['given_name'] ?? explode('@', $email)[0]));
$pic   = trim($payload['picture'] ?? '');

$db = getDB();

// Cek apakah email terdaftar di evaluasi_peserta
$stmt = $db->prepare("SELECT * FROM evaluasi_peserta WHERE email = ? LIMIT 1");
$stmt->execute([$email]);
$peserta = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$peserta) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Akses Ditolak: Akun Google ' . $email . ' belum terdaftar sebagai peserta Evaluasi Kinerja LPM. Silakan hubungi Administrator LPM untuk mendaftarkan akun Anda.'
    ]);
    exit;
}

if ((int)$peserta['status'] === 0) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Akses Ditangguhkan: Akun peserta atas nama ' . $peserta['nama'] . ' (' . $email . ') saat ini dinonaktifkan oleh Administrator LPM.'
    ]);
    exit;
}

// Update avatar jika ada foto dari profil Google
if ($pic && empty($peserta['avatar'])) {
    $db->prepare("UPDATE evaluasi_peserta SET avatar = ? WHERE id = ?")->execute([$pic, $peserta['id']]);
    $peserta['avatar'] = $pic;
}

// Set session
$_SESSION['evaluasi_user'] = [
    'id'        => (int)$peserta['id'],
    'nama'      => $peserta['nama'],
    'email'     => $peserta['email'],
    'jabatan'   => $peserta['jabatan'],
    'peran'     => $peserta['peran'], // 'kepala' atau 'personel'
    'avatar'    => $peserta['avatar'] ?: $pic,
    'logged_at' => time()
];

echo json_encode([
    'success' => true,
    'message' => 'Selamat datang, ' . $peserta['nama'] . '! Berhasil masuk ke portal Evaluasi Kinerja.',
    'user'    => $_SESSION['evaluasi_user']
]);
exit;

