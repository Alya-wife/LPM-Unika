<?php
require_once __DIR__ . '/../config/database.php';
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Redirect jika sudah login
if (isset($_SESSION['admin_id'])) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$error_msg = '';

// Handle Form Login Standar (Username / Email & Password)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'login') {
    $login_input = trim($_POST['username'] ?? '');
    $password    = trim($_POST['password'] ?? '');

    if ($login_input === '' || $password === '') {
        $error_msg = 'Username / Email dan Password wajib diisi.';
    } else {
        $db = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? OR email = ? LIMIT 1");
        $stmt->execute([$login_input, $login_input]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id']      = $user['id'];
            $_SESSION['admin_name']    = $user['nama_lengkap'] ?: $user['username'];
            $_SESSION['admin_email']   = $user['email'] ?? '';
            $_SESSION['admin_picture'] = '';

            // Sesi civitas UNIKA
            $_SESSION['unika_user'] = [
                'email'     => $user['email'] ?? 'admin@unika.ac.id',
                'name'      => $user['nama_lengkap'] ?: $user['username'],
                'picture'   => '',
                'logged_at' => time()
            ];

            redirect(SITE_URL . '/admin/dashboard.php');
        } else {
            $error_msg = 'Username atau Password salah. Silakan coba lagi.';
        }
    }
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
            padding: 2.5rem 2.25rem;
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
            margin-bottom: 1.5rem;
        }
        .form-control-admin {
            background: rgba(255,255,255,0.09);
            border: 1px solid rgba(255,255,255,0.18);
            color: #ffffff !important;
            border-radius: 10px;
            padding: 0.75rem 1rem 0.75rem 2.6rem;
            font-size: 0.9rem;
            transition: all 0.2s ease;
        }
        .form-control-admin:focus {
            background: rgba(255,255,255,0.14);
            border-color: #FFD54F;
            box-shadow: 0 0 0 3px rgba(255,213,79,0.25);
            color: #ffffff;
        }
        .form-control-admin::placeholder {
            color: rgba(255,255,255,0.45);
        }
        .input-icon-wrap {
            position: relative;
        }
        .input-icon-wrap i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: rgba(255,255,255,0.5);
            font-size: 1rem;
            pointer-events: none;
        }
        .btn-admin-submit {
            background: linear-gradient(135deg, #FFD54F, #FFC107);
            color: #0A192F;
            font-weight: 800;
            font-size: 0.95rem;
            border: none;
            border-radius: 10px;
            padding: 0.8rem;
            width: 100%;
            box-shadow: 0 6px 20px rgba(255,193,7,0.3);
            transition: all 0.2s ease;
        }
        .btn-admin-submit:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(255,193,7,0.4);
            background: #FFD54F;
            color: #0A192F;
        }
        .login-divider {
            display: flex;
            align-items: center;
            text-align: center;
            margin: 1.5rem 0 1rem;
            color: rgba(255,255,255,0.4);
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            text-uppercase: uppercase;
        }
        .login-divider::before,
        .login-divider::after {
            content: '';
            flex: 1;
            border-bottom: 1px solid rgba(255,255,255,0.12);
        }
        .login-divider span {
            padding: 0 0.8rem;
        }
        .back-link {
            text-align: center;
            margin-top: 1.5rem;
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
        <div style="display:flex;align-items:center;justify-content:center;gap:12px;margin-bottom:1.25rem;">
            <div class="brand-logo-wrap" style="width:44px;height:44px;background:rgba(255,255,255,0.1);border-radius:10px;display:flex;align-items:center;justify-content:center;padding:4px;">
                <img src="<?= SITE_URL ?>/assets/images/logo-unika.png" alt="Logo UNIKA" class="brand-logo-img" style="max-width:100%;max-height:100%;">
            </div>
            <div style="text-align:left;">
                <div class="brand-title" style="color:#fff;font-weight:800;font-size:1.05rem;">LPM UNIKA</div>
                <div class="brand-subtitle" style="color:var(--text-muted);font-size:0.75rem;">Portal Administrator</div>
            </div>
        </div>

        <div class="login-title">Autentikasi Administrator</div>
        <div class="login-sub">Masuk ke panel kontrol Lembaga Penjaminan Mutu Universitas Katolik Soegijapranata</div>

        <?php if ($error_msg): ?>
        <div class="alert alert-danger p-2 mb-3 text-start" style="font-size:0.84rem;border-radius:10px;background:rgba(239,68,68,0.2);border:1px solid #EF4444;color:#ffcaca;">
            <i class="bi bi-exclamation-triangle-fill me-1"></i> <?= htmlspecialchars($error_msg) ?>
        </div>
        <?php endif; ?>

        <!-- Form Login Standar (Username & Password) -->
        <form method="POST" action="" class="text-start mb-3">
            <input type="hidden" name="action" value="login">
            
            <div class="mb-3">
                <label class="form-label text-white-50" style="font-size:0.8rem;font-weight:600;">Username atau Email</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-person-fill"></i>
                    <input type="text" name="username" class="form-control form-control-admin" placeholder="admin atau tu.lpm@unika.ac.id" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autofocus autocomplete="username">
                </div>
            </div>

            <div class="mb-3">
                <label class="form-label text-white-50" style="font-size:0.8rem;font-weight:600;">Kata Sandi (Password)</label>
                <div class="input-icon-wrap">
                    <i class="bi bi-lock-fill"></i>
                    <input type="password" name="password" id="inputPassword" class="form-control form-control-admin" placeholder="Masukkan password" required autocomplete="current-password">
                </div>
            </div>

            <button type="submit" class="btn btn-admin-submit mt-1">
                <i class="bi bi-box-arrow-in-right me-1"></i> Masuk Sekarang
            </button>
        </form>

        <!-- Divider Opsi Google -->
        <div class="login-divider">
            <span>atau masuk via google</span>
        </div>

        <!-- Loading State Google -->
        <div id="adminLoginLoading" style="display:none;padding:0.75rem;background:rgba(255,255,255,0.08);border-radius:10px;color:#fff;font-size:0.85rem;align-items:center;justify-content:center;gap:10px;margin-bottom:1rem;">
            <div class="spinner-border spinner-border-sm text-warning" role="status"></div>
            <span>Memverifikasi akun Google...</span>
        </div>

        <!-- Google Sign-In Button Container -->
        <div class="d-flex flex-column align-items-center mb-2">
            <div id="gsiAdminBtn" style="min-height:44px;"></div>
            <div id="googleErrorMsg" style="display:none;width:100%;margin-top:0.75rem;text-align:left;background:rgba(239,68,68,0.18);border:1px solid #EF4444;color:#ffcaca;border-radius:var(--radius-sm);padding:0.65rem 0.9rem;font-size:0.82rem;line-height:1.5;"></div>
        </div>

        <div class="back-link">
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
