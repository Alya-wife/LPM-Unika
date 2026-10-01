<?php
// Template konfigurasi Google OAuth 2.0
// Salin berkas ini ke 'config/oauth.local.php' dan isi dengan kredensial Google Cloud Console Anda
if (!defined('GOOGLE_CLIENT_ID')) {
    define('GOOGLE_CLIENT_ID', 'YOUR_GOOGLE_CLIENT_ID_HERE.apps.googleusercontent.com');
}
if (!defined('GOOGLE_CLIENT_SECRET')) {
    define('GOOGLE_CLIENT_SECRET', 'YOUR_GOOGLE_CLIENT_SECRET_HERE');
}
