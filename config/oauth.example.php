<?php
/**
 * Contoh Berkas Konfigurasi Google OAuth
 * 
 * Salin berkas ini menjadi config/oauth.local.php (yang sudah di-ignore oleh git)
 * dan sesuaikan dengan Client ID & Secret Google OAuth Anda:
 */

if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE.apps.googleusercontent.com');
}

if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
}
