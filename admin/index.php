<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika sudah login
if (isset($_SESSION['admin_id'])) {
    redirect(SITE_URL . '/admin/dashboard.php');
}
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login – LPM UNIKA</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--navy) 0%, #1a1040 50%, var(--navy-mid) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem 1rem;
            position: relative;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(106,27,154,0.18) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(74,20,140,0.15) 0%, transparent 40%);
            pointer-events: none;
        }
        .login-card {
            background: rgba(255,255,255,0.07);
            border: 1px solid rgba(255,255,255,0.14);
            backdrop-filter: blur(24px);
            border-radius: var(--radius-xl);
            padding: 2.75rem 2.25rem;
            width: 100%;
            max-width: 440px;
            box-shadow: 0 25px 60px rgba(0,0,0,0.45);
            text-align: center;
        }
        .login-title {
            font-family: var(--font-heading);
            font-size: 1.4rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 0.35rem;
        }
        .login-sub {
            font-size: 0.83rem;
            color: rgba(255,255,255,0.65);
            line-height: 1.5;
            margin-bottom: 1.75rem;
        }
        .back-link {
            text-align: center;
            margin-top: 1.75rem;
            font-size: 0.82rem;
            color: rgba(255,255,255,0.4);
        }
        .back-link a {
            color: rgba(255,255,255,0.65);
            text-decoration: none;
            transition: var(--transition);
        }
        .back-link a:hover { color: #fff; }
    </style>
</head>
<body>
    <div class="login-card animate-fadeInUp">
        <!-- Logo -->
        <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:1.5rem;">
            <div class="brand-logo-wrap" style="width:48px;height:48px;background:rgba(255,255,255,0.1);border-radius:12px;display:flex;align-items:center;justify-content:center;padding:5px;">
                <img src="<?= SITE_URL ?>/assets/images/logo-unika.png" alt="Logo UNIKA" class="brand-logo-img" style="max-width:100%;max-height:100%;">
            </div>
            <div style="text-align:left;">
                <div class="brand-title" style="color:#fff;font-weight:800;font-size:1.1rem;">LPM UNIKA</div>
                <div class="brand-subtitle" style="color:var(--text-muted);font-size:0.75rem;">Portal Administrator</div>
            </div>
        </div>

        <div class="login-title">Autentikasi Administrator</div>
        <div class="login-sub">Masuk ke panel kontrol Lembaga Penjaminan Mutu menggunakan akun Google resmi.</div>

        <!-- Info box akun Google admin -->
        <div class="p-3 mb-4 rounded-3 text-start" style="background:rgba(255,255,255,0.06);border:1px solid rgba(255,255,255,0.12);font-size:0.82rem;color:rgba(255,255,255,0.75);line-height:1.6;">
            <div class="d-flex align-items-center gap-2 mb-1 text-warning fw-bold">
                <i class="bi bi-shield-lock-fill"></i>
                <span>Akses Khusus Pengelola</span>
            </div>
            Silakan masuk dengan akun Google Administrator resmi UNIKA (<code>tu.lpm@unika.ac.id</code> atau akun resmi terdaftar).
        </div>

        <!-- Loading State Google -->
        <div id="adminLoginLoading" style="display:none;padding:0.85rem;background:rgba(255,255,255,0.08);border-radius:10px;color:#fff;font-size:0.88rem;align-items:center;justify-content:center;gap:10px;margin-bottom:1.25rem;">
            <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
            <span>Memverifikasi akun Google...</span>
        </div>

        <!-- Google Sign-In Button Container -->
        <div class="d-flex flex-column align-items-center mb-3">
            <div id="gsiAdminBtn" style="min-height:44px;display:flex;justify-content:center;width:100%;"></div>
            <div id="googleErrorMsg" style="display:none;width:100%;margin-top:0.85rem;text-align:left;background:rgba(239,68,68,0.18);border:1px solid #EF4444;color:#ffcaca;border-radius:var(--radius-sm);padding:0.75rem 1rem;font-size:0.83rem;line-height:1.5;"></div>
        </div>

        <div class="back-link border-top pt-3" style="border-color:rgba(255,255,255,0.1) !important;">
            <a href="<?= SITE_URL ?>/">
                <i class="bi bi-arrow-left me-1"></i> Kembali ke Beranda Website
            </a>
        </div>
    </div>

    <!-- Google Identity Services Script -->
    <script src="https://accounts.google.com/gsi/client" async defer></script>
    <script>
    function initAdminGoogleAuth() {
        if (typeof google === 'undefined' || !google.accounts || !google.accounts.id) {
            setTimeout(initAdminGoogleAuth, 300);
            return;
        }

        try {
            google.accounts.id.initialize({
                client_id: "<?= GOOGLE_CLIENT_ID ?>",
                callback: handleAdminGoogleResponse,
                auto_select: false
            });

            const btnContainer = document.getElementById('gsiAdminBtn');
            if (btnContainer) {
                btnContainer.innerHTML = '';
                google.accounts.id.renderButton(btnContainer, {
                    theme: 'outline',
                    size: 'large',
                    text: 'signin_with',
                    shape: 'rectangular',
                    logo_alignment: 'left',
                    width: 320
                });
            }
        } catch (e) {
            console.warn('Google init error:', e);
        }
    }

    function handleAdminGoogleResponse(response) {
        const errBox  = document.getElementById('googleErrorMsg');
        const loadBox = document.getElementById('adminLoginLoading');
        const btnBox  = document.getElementById('gsiAdminBtn');

        if (errBox) errBox.style.display = 'none';

        if (!response || !response.credential) {
            if (errBox) {
                errBox.textContent = 'Gagal menerima kredensial dari Google. Silakan coba lagi.';
                errBox.style.display = 'block';
            }
            return;
        }

        if (loadBox) loadBox.style.display = 'flex';
        if (btnBox) btnBox.style.opacity = '0.5';

        fetch('<?= SITE_URL ?>/admin/google-login.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ credential: response.credential })
        })
        .then(res => res.json())
        .then(data => {
            if (data.success && data.redirect) {
                window.location.href = data.redirect;
            } else {
                if (loadBox) loadBox.style.display = 'none';
                if (btnBox) btnBox.style.opacity = '1';
                if (errBox) {
                    errBox.textContent = data.message || 'Akses ditolak: Akun Google Anda bukan Administrator resmi.';
                    errBox.style.display = 'block';
                }
            }
        })
        .catch(err => {
            console.error('Google login error:', err);
            if (loadBox) loadBox.style.display = 'none';
            if (btnBox) btnBox.style.opacity = '1';
            if (errBox) {
                errBox.textContent = 'Terjadi kesalahan koneksi saat memproses login Google.';
                errBox.style.display = 'block';
            }
        });
    }

    document.addEventListener('DOMContentLoaded', initAdminGoogleAuth);
    </script>
</body>
</html>
