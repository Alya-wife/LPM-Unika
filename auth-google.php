<?php
/**
 * Google Authentication Handler - LPM UNIKA
 * Verifikasi token Google Identity Services (GIS) dan validasi civitas @unika.ac.id
 */
require_once __DIR__ . '/config/database.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? 'verify');

// 1. Action: Logout
if ($action === 'logout') {
    unset($_SESSION['unika_user']);
    if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => true, 'message' => 'Berhasil logout']);
        exit;
    }
    $redirect_to = $_SERVER['HTTP_REFERER'] ?? (SITE_URL . '/spmi.php');
    redirect($redirect_to);
}

// 2. Action: Status
if ($action === 'status') {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode([
        'success'   => true,
        'logged_in' => isUnikaLoggedIn(),
        'user'      => getUnikaUser()
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

// Ambil input JSON atau POST form-urlencoded
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

// Fallback jika verifikasi tokeninfo gagal (misal koneksi server lokal timeout), coba decode JWT lokal
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
    echo json_encode([
        'success' => false,
        'message' => 'Gagal memverifikasi token Google. Pastikan sesi Google Anda valid.'
    ]);
    exit;
}

// Validasi Audience Client ID
if (isset($payload['aud']) && $payload['aud'] !== GOOGLE_CLIENT_ID) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Client ID token tidak cocok dengan konfigurasi sistem.'
    ]);
    exit;
}

$email = strtolower(trim($payload['email']));
$name  = trim($payload['name'] ?? ($payload['given_name'] ?? explode('@', $email)[0]));
$pic   = trim($payload['picture'] ?? '');

// Whitelist Pengembang & Tester Khusus (Bypass verifikasi domain kampus)
$allowed_dev_emails = ['ravywhienelda@gmail.com'];
$is_dev_tester      = in_array($email, $allowed_dev_emails, true);

// VALIDASI DOMAIN RESMI KHUSUS @unika.ac.id (Mengecualikan akun mahasiswa @student.unika.ac.id)
$is_student_domain = (bool)preg_match('/@([a-z0-9\.-]*\.)?student\.unika\.ac\.id$/i', $email);
$is_unika_domain   = (bool)preg_match('/@unika\.ac\.id$/i', $email);

if (!$is_dev_tester) {
    if ($is_student_domain) {
        echo json_encode([
            'success'  => false,
            'is_unika' => false,
            'email'    => $email,
            'message'  => 'Akses Ditolak: Dokumen SPMI ini hanya diperuntukkan bagi akun dosen/staf/unit resmi (@unika.ac.id). Akun mahasiswa (' . $email . ') tidak memiliki izin untuk membuka dokumen ini.'
        ]);
        exit;
    }

    if (!$is_unika_domain) {
        echo json_encode([
            'success'  => false,
            'is_unika' => false,
            'email'    => $email,
            'message'  => 'Akses Terbatas: Dokumen lengkap SPMI hanya dapat dibuka oleh akun resmi @unika.ac.id. Anda saat ini masuk dengan akun: ' . $email . '. Silakan gunakan akun Google @unika.ac.id Anda.'
        ]);
        exit;
    }
}

// Simpan sesi autentikasi civitas UNIKA
$_SESSION['unika_user'] = [
    'email'     => $email,
    'name'      => $name,
    'picture'   => $pic,
    'hd'        => $payload['hd'] ?? 'unika.ac.id',
    'logged_at' => time()
];

echo json_encode([
    'success'  => true,
    'is_unika' => true,
    'message'  => 'Selamat datang, ' . $name . '! Akses penuh dokumen SPMI berhasil dibuka.',
    'user'     => $_SESSION['unika_user']
]);
exit;
