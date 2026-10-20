<?php
/**
 * Admin Auth Guard & Role/Permission System – LPM UNIKA
 */
require_once __DIR__ . '/../../config/database.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 1. Cek sesi login
if (php_sapi_name() !== 'cli' && !isset($_SESSION['admin_id'])) {
    redirect(SITE_URL . '/admin/index.php');
    exit;
}

// 2. Muat & Sinkronisasi Data Pengguna Terkini dari Database
if (isset($_SESSION['admin_id'])) {
    try {
        $db_auth = getDB();
        $stmt_auth = $db_auth->prepare("SELECT id, username, email, nama_lengkap, role, akses_halaman, status FROM users WHERE id = ? LIMIT 1");
        $stmt_auth->execute([(int)$_SESSION['admin_id']]);
        $current_admin_user = $stmt_auth->fetch(PDO::FETCH_ASSOC);

        if (!$current_admin_user || (int)$current_admin_user['status'] === 0) {
            // Akun dihapus atau dinonaktifkan
            session_destroy();
            redirect(SITE_URL . '/admin/index.php?error=' . urlencode('Akun Anda dinonaktifkan atau tidak ditemukan. Silakan hubungi Super Admin.'));
            exit;
        }

        $_SESSION['admin_name']  = $current_admin_user['nama_lengkap'] ?: $current_admin_user['username'];
        $_SESSION['admin_email'] = $current_admin_user['email'] ?? '';
        $_SESSION['admin_role']  = $current_admin_user['role'] ?? 'admin';
        
        $raw_perms = $current_admin_user['akses_halaman'] ?? '[]';
        $decoded_perms = json_decode($raw_perms, true);
        $_SESSION['admin_permissions'] = is_array($decoded_perms) ? $decoded_perms : [];

    } catch (Exception $e) {
        // Fallback jika query error
    }
}

/**
 * Cek apakah admin aktif memiliki hak Super Administrator (mengakses segalanya)
 */
function isAdminSuper(): bool {
    return (($_SESSION['admin_role'] ?? '') === 'superadmin');
}

/**
 * Cek apakah admin aktif diizinkan mengakses modul/bagian tertentu
 */
function canAccessModule(string $module): bool {
    if (isAdminSuper()) {
        return true;
    }
    $perms = $_SESSION['admin_permissions'] ?? [];
    return in_array($module, $perms, true);
}

/**
 * Daftar Master Modul Halaman Sidebar
 */
function getAdminModulesList(): array {
    return [
        'beranda' => [
            'name' => 'Halaman Beranda',
            'desc' => 'Hero Slider Banner & Capaian Penghargaan',
            'icon' => 'bi-house-door-fill'
        ],
        'profil' => [
            'name' => 'Halaman Profil',
            'desc' => 'Tentang, Visi & Misi, Visual Bagan Struktur, Tim LPM & GPM',
            'icon' => 'bi-building-fill'
        ],
        'akreditasi' => [
            'name' => 'Halaman Akreditasi',
            'desc' => 'Akreditasi Institusi, Status Nasional, Data Prodi, Pemeringkatan, Lembaga LAM',
            'icon' => 'bi-award-fill'
        ],
        'spmi' => [
            'name' => 'Halaman SPMI',
            'desc' => 'Dokumen & Regulasi SPMI, Kategori Dokumen, Hasil SPMI Kemendikti, Portal SPMI & Kartu Siklus PPEPP',
            'icon' => 'bi-file-earmark-check-fill'
        ],
        'faq' => [
            'name' => 'Halaman FAQ & Edukasi',
            'desc' => 'Tanya Jawab (FAQ), Kategori FAQ, Glosarium Mutu, Kalender Mutu, Buletin JAMUS',
            'icon' => 'bi-question-circle-fill'
        ],
        'ami' => [
            'name' => 'Halaman AMI',
            'desc' => 'Pengantar AMI, Siklus AMI 1–5, Dokumen & Dokumentasi Visitasi / RTM, Periode AMI',
            'icon' => 'bi-clipboard-data-fill'
        ],
        'berita' => [
            'name' => 'Halaman Berita & Kegiatan',
            'desc' => 'Daftar Berita, Review Berita Publikasi, Kategori Berita',
            'icon' => 'bi-newspaper'
        ],
        'layanan' => [
            'name' => 'Menu Layanan',
            'desc' => 'Info Pelatihan/Brosur, Permohonan Studi Banding, Respon Survei & Kuesioner',
            'icon' => 'bi-grid-1x2-fill'
        ],
        'builder' => [
            'name' => 'Visual Page Builder',
            'desc' => 'Pembuat dan Penata Tata Letak Visual Interaktif Berbasis Blok',
            'icon' => 'bi-layout-text-window-reverse'
        ],
        'pages' => [
            'name' => 'Kelola Halaman Web',
            'desc' => 'Daftar Halaman Custom & Kategori Halaman',
            'icon' => 'bi-file-earmark-richtext-fill'
        ],
        'pengaturan' => [
            'name' => 'Pengaturan & Pengguna',
            'desc' => 'Identitas Website, Kontak, Konversi WebP HD, dan Manajemen Pengguna Admin',
            'icon' => 'bi-gear-fill'
        ],
    ];
}

/**
 * Parsing domain-domain yang diizinkan dari input POST
 */
function parseSubmittedAllowedDomains(string $field_prefix = ''): string {
    $arr_key    = ($field_prefix ? $field_prefix . '_' : '') . 'allowed_domains_arr';
    $custom_key = ($field_prefix ? $field_prefix . '_' : '') . 'custom_domains';

    $domains = $_POST[$arr_key] ?? [];
    if (!is_array($domains)) {
        $domains = [];
    }

    $custom_str = trim($_POST[$custom_key] ?? '');
    if ($custom_str !== '') {
        $parts = explode(',', $custom_str);
        foreach ($parts as $p) {
            $p = trim(strtolower($p));
            if ($p !== '') {
                // Bersihkan jika ada @ ganda misalnya @user@gmail.com
                if ($p[0] === '@' && strpos($p, '@', 1) !== false) {
                    $p = substr($p, 1);
                }
                // Jika domain polos tanpa @ (contoh: gmail.com), tambahkan @ di depan
                if ($p[0] !== '@' && strpos($p, '@') === false) {
                    $p = '@' . $p;
                }
                $domains[] = $p;
            }
        }
    }

    $domains = array_unique(array_filter($domains));
    if (empty($domains)) {
        return '@unika.ac.id,@student.unika.ac.id';
    }
    return implode(',', $domains);
}

/**
 * Ekstrak domain atau akun kustom di luar default civitas (@unika.ac.id & @student.unika.ac.id)
 */
function extractCustomAllowedDomains(?string $domains_str): string {
    if (!$domains_str) return '';
    $arr = array_map('trim', explode(',', strtolower($domains_str)));
    $custom = [];
    foreach ($arr as $d) {
        if ($d && $d !== '@unika.ac.id' && $d !== '@student.unika.ac.id') {
            $custom[] = $d;
        }
    }
    return implode(', ', $custom);
}
if (!function_exists('extractCustomDomains')) {
    function extractCustomDomains(?string $domains_str): string {
        return extractCustomAllowedDomains($domains_str);
    }
}

// 3. Otomatisasi Guard Izin Akses Halaman Berdasarkan Skrip yang Dijalankan
$page_module_map = [
    // Beranda
    'slider-list' => 'beranda',
    'slider-form' => 'beranda',
    'penghargaan-list' => 'beranda',
    'penghargaan-form' => 'beranda',

    // Profil
    'profil-edit' => 'profil',
    'struktur-organisasi' => 'profil',
    'tim-list' => 'profil',
    'tim-form' => 'profil',

    // Akreditasi
    'akreditasi-institusi' => 'akreditasi',
    'akreditasi-status-nasional' => 'akreditasi',
    'akreditasi-prodi-list' => 'akreditasi',
    'akreditasi-prodi-form' => 'akreditasi',
    'pemeringkatan-list' => 'akreditasi',
    'pemeringkatan-form' => 'akreditasi',
    'pemeringkatan-setting' => 'akreditasi',
    'lembaga-akreditasi-list' => 'akreditasi',
    'lembaga-akreditasi-form' => 'akreditasi',

    // SPMI
    'dokumen-list' => 'spmi',
    'dokumen-form' => 'spmi',
    'kategori-dokumen' => 'spmi',
    'spmi-kemendikti-list' => 'spmi',
    'spmi-kemendikti-form' => 'spmi',
    'spmi-portal-setting' => 'spmi',
    'spmi-ppepp-card' => 'spmi',

    // FAQ
    'faq-setting' => 'faq',
    'kategori-faq' => 'faq',
    'glosarium-setting' => 'faq',
    'kalender-list' => 'faq',
    'kalender-form' => 'faq',
    'buletin-list' => 'faq',
    'buletin-form' => 'faq',

    // AMI
    'ami-pengantar' => 'ami',
    'ami-siklus-list' => 'ami',
    'ami-siklus1-form' => 'ami',
    'ami-siklus4-dok-form' => 'ami',
    'ami-siklus4-form' => 'ami',
    'ami-siklus5-form' => 'ami',
    'ami-periode' => 'ami',

    // Berita
    'berita-list' => 'berita',
    'berita-form' => 'berita',
    'berita-review' => 'berita',
    'kategori-berita' => 'berita',

    // Layanan
    'kunjungan-list' => 'layanan',
    'kunjungan-unit' => 'layanan',
    'feedback-kunjungan-list' => 'layanan',
    'feedback-kunjungan-detail' => 'layanan',
    'feedback-kunjungan-pertanyaan' => 'layanan',
    'feedback-list' => 'layanan',
    'layanan-form-setting' => 'layanan',

    // Builder
    'advance-setting' => 'builder',
    'builder-preview' => 'builder',
    'builder-save' => 'builder',

    // Pages
    'page-list' => 'pages',
    'page-form' => 'pages',
    'kategori-page' => 'pages',

    // Pengaturan & Manajemen Pengguna
    'pengaturan' => 'pengaturan',
    'convert-images' => 'pengaturan',
    'user-list' => 'pengaturan',
    'user-form' => 'pengaturan',
];

if (php_sapi_name() !== 'cli' && !empty($_SERVER['PHP_SELF'])) {
    $current_script_name = basename($_SERVER['PHP_SELF'], '.php');
    if (isset($page_module_map[$current_script_name])) {
        $required_module = $page_module_map[$current_script_name];
        if (!canAccessModule($required_module)) {
            $modules_dict = getAdminModulesList();
            $mod_title = $modules_dict[$required_module]['name'] ?? $required_module;
            $_SESSION['flash_error'] = "Akses Ditolak: Anda tidak memiliki wewenang administrator untuk membuka menu '{$mod_title}'.";
            redirect(SITE_URL . '/admin/dashboard.php');
            exit;
        }
    }
}
