<?php
require_once __DIR__ . '/../config/database.php';
session_start();

// Redirect if already logged in
if (isset($_SESSION['admin_id'])) {
    redirect(SITE_URL . '/admin/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $db   = getDB();
        $stmt = $db->prepare("SELECT * FROM users WHERE username = ? LIMIT 1");
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['admin_id']   = $user['id'];
            $_SESSION['admin_name'] = $user['nama_lengkap'] ?? $user['username'];
            redirect(SITE_URL . '/admin/dashboard.php');
        } else {
            $error = 'Username atau password salah.';
        }
    } else {
        $error = 'Username dan password wajib diisi.';
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
    <link rel="stylesheet" href="<?= SITE_URL ?>/assets/css/style.css">
    <style>
        body {
            min-height: 100vh;
            background: linear-gradient(135deg, var(--navy) 0%, #1a1040 50%, var(--navy-mid) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
        }
        body::before {
            content: '';
            position: fixed;
            inset: 0;
            background:
                radial-gradient(circle at 20% 50%, rgba(106,27,154,0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 20%, rgba(74,20,140,0.12) 0%, transparent 40%);
            pointer-events: none;
        }
        .login-card {
            background: rgba(255,255,255,0.05);
            border: 1px solid rgba(255,255,255,0.1);
            backdrop-filter: blur(24px);
            border-radius: var(--radius-xl);
            padding: 2.5rem;
            width: 100%;
            max-width: 420px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.4);
        }
        .login-title {
            font-family: var(--font-heading);
            font-size: 1.5rem;
            font-weight: 800;
            color: #fff;
            margin-bottom: 0.25rem;
        }
        .login-sub {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.5);
            margin-bottom: 2rem;
        }
        .login-label {
            font-family: var(--font-heading);
            font-size: 0.8rem;
            font-weight: 600;
            color: rgba(255,255,255,0.75);
            margin-bottom: 0.4rem;
            display: block;
        }
        .login-input {
            width: 100%;
            background: rgba(255,255,255,0.07);
            border: 1.5px solid rgba(255,255,255,0.15);
            border-radius: var(--radius-sm);
            padding: 0.7rem 1rem;
            color: #fff;
            font-size: 0.9rem;
            font-family: var(--font-body);
            transition: var(--transition);
            outline: none;
            margin-bottom: 1rem;
        }
        .login-input::placeholder { color: rgba(255,255,255,0.3); }
        .login-input:focus {
            border-color: var(--purple-light);
            background: rgba(255,255,255,0.1);
            box-shadow: 0 0 0 3px rgba(106,27,154,0.2);
        }
        .login-btn {
            width: 100%;
            background: linear-gradient(135deg, var(--purple-dark), var(--purple));
            color: #fff;
            font-family: var(--font-heading);
            font-weight: 700;
            font-size: 0.95rem;
            padding: 0.85rem;
            border-radius: 50px;
            border: none;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(106,27,154,0.4);
            transition: var(--transition);
            margin-top: 0.5rem;
        }
        .login-btn:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 30px rgba(106,27,154,0.5);
        }
        .login-error {
            background: rgba(198,40,40,0.15);
            border: 1px solid rgba(198,40,40,0.3);
            color: #ffb3b3;
            border-radius: var(--radius-sm);
            padding: 0.75rem 1rem;
            font-size: 0.85rem;
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
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
        <div style="display:flex;align-items:center;gap:12px;margin-bottom:1.75rem;">
            <div class="brand-logo-wrap">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M4.26 10.147a60.438 60.438 0 0 0-.491 6.347A48.62 48.62 0 0 1 12 20.904a48.62 48.62 0 0 1 8.232-4.41 60.46 60.46 0 0 0-.491-6.347m-15.482 0a50.636 50.636 0 0 0-2.658-.813A59.906 59.906 0 0 1 12 3.493a59.903 59.903 0 0 1 10.399 5.84c-.896.248-1.783.52-2.658.814m-15.482 0A50.717 50.717 0 0 1 12 13.489a50.702 50.702 0 0 1 3.741-2.342M6.75 15a.75.75 0 1 0 0-1.5.75.75 0 0 0 0 1.5Zm0 0v-3.675A55.378 55.378 0 0 1 12 8.443m-7.007 11.55A5.981 5.981 0 0 0 6.75 15.75v-1.5" />
                </svg>
            </div>
            <div>
                <div class="brand-title" style="color:#fff;">LPM UNIKA</div>
                <div class="brand-subtitle">Admin Panel</div>
            </div>
        </div>

        <div class="login-title">Selamat Datang</div>
        <div class="login-sub">Masuk ke panel administrasi LPM UNIKA</div>

        <?php if ($error): ?>
        <div class="login-error">
            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="16" height="16">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />
            </svg>
            <?= e($error) ?>
        </div>
        <?php endif; ?>

        <form method="POST" action="" id="form-login">
            <label class="login-label" for="username">Username</label>
            <input type="text" id="username" name="username" class="login-input" placeholder="Masukkan username" value="<?= e($_POST['username'] ?? '') ?>" required autocomplete="username">

            <label class="login-label" for="password">Password</label>
            <input type="password" id="password" name="password" class="login-input" placeholder="Masukkan password" required autocomplete="current-password">

            <button type="submit" class="login-btn" id="btn-login">
                Masuk ke Admin Panel
            </button>
        </form>

        <div class="back-link">
            <a href="<?= SITE_URL ?>/">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" width="13" height="13" style="vertical-align:middle;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                Kembali ke Website
            </a>
        </div>
    </div>
</body>
</html>
