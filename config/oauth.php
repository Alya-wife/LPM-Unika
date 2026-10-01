<?php
/**
 * ============================================================================
 * KONFIGURASI GOOGLE OAUTH 2.0 - LPM UNIKA
 * ============================================================================
 * Berkas ini terpisah khusus untuk autentikasi Google Sign-In (Administrator & Civitas).
 * Pengguna atau pemilik baru cukup mengganti Client ID dan Client Secret resmi di bawah ini:
 */

// Memuat kredensial lokal jika tersedia
if (file_exists(__DIR__ . '/oauth.local.php')) {
    require_once __DIR__ . '/oauth.local.php';
}

// 1. Google Client ID (dari Google Cloud Console -> APIs & Services -> Credentials)
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', 'MASUKKAN_GOOGLE_CLIENT_ID_DISINI.apps.googleusercontent.com');
}

// 2. Google Client Secret (dari Google Cloud Console -> APIs & Services -> Credentials)
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', 'MASUKKAN_GOOGLE_CLIENT_SECRET_DISINI');
}
