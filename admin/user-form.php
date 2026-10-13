<?php
require_once __DIR__ . '/includes/auth.php';

// Hanya Super Admin yang berhak mengelola akun pengguna
if (!isAdminSuper()) {
    $_SESSION['flash_error'] = 'Akses Ditolak: Hanya Super Administrator yang berhak mengelola pengguna.';
    redirect(SITE_URL . '/admin/dashboard.php');
    exit;
}

$db = getDB();
$id = (int)($_GET['id'] ?? 0);
$is_edit = false;
$user_data = [];

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$id]);
    $user_data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($user_data) {
        $is_edit = true;
    }
}

$admin_page_title = $is_edit ? 'Edit Pengguna Administrator' : 'Tambah Pengguna Administrator';
$modules_dict = getAdminModulesList();
$error = '';

$current_role  = $_POST['role'] ?? ($user_data['role'] ?? 'admin');
$current_perms = $_POST['akses_halaman'] ?? (json_decode($user_data['akses_halaman'] ?? '[]', true) ?: []);
if (!is_array($current_perms)) $current_perms = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email        = strtolower(trim($_POST['email'] ?? ''));
    $nama_lengkap = trim($_POST['nama_lengkap'] ?? '');
    $role         = trim($_POST['role'] ?? 'admin');
    $status       = isset($_POST['status']) ? 1 : 0;
    
    // Pastikan akun utama id 1 tetap superadmin dan aktif
    if ($is_edit && (int)$id === 1) {
        $role   = 'superadmin';
        $status = 1;
    }

    $raw_perms = $_POST['akses_halaman'] ?? [];
    if (!is_array($raw_perms)) $raw_perms = [];
    $valid_perms = array_values(array_intersect(array_keys($modules_dict), $raw_perms));

    if ($email === '') {
        $error = 'Email Akun Google administrator wajib diisi.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Format alamat email tidak valid.';
    } else {
        // Cek duplikasi email
        $chk_e = $db->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $chk_e->execute([$email, $id]);
        if ($chk_e->fetch()) {
            $error = "Akun Google dengan email '{$email}' sudah terdaftar dalam sistem.";
        }
    }

    if (!$error && $nama_lengkap === '') {
        // Auto default nama dari email jika dikosongkan
        $prefix = explode('@', $email)[0];
        $nama_lengkap = ucwords(str_replace(['.', '_', '-'], ' ', $prefix));
    }

    $username = explode('@', $email)[0];

    if (!$error && $role === 'admin' && empty($valid_perms)) {
        $error = 'Untuk pengguna dengan peran Admin Terbatas, silakan centang minimal satu modul halaman wewenang.';
    }

    if (!$error) {
        $perms_json = ($role === 'superadmin') ? null : json_encode($valid_perms);

        if ($is_edit) {
            $stmt_upd = $db->prepare("UPDATE users SET nama_lengkap = ?, username = ?, email = ?, role = ?, akses_halaman = ?, status = ? WHERE id = ?");
            $stmt_upd->execute([$nama_lengkap, $username, $email, $role, $perms_json, $status, $id]);
            $_SESSION['flash'] = "Data akun Google administrator '{$email}' berhasil diperbarui.";
        } else {
            $stmt_ins = $db->prepare("INSERT INTO users (nama_lengkap, username, email, password, role, akses_halaman, status) VALUES (?, ?, ?, NULL, ?, ?, ?)");
            $stmt_ins->execute([$nama_lengkap, $username, $email, $role, $perms_json, $status]);
            $_SESSION['flash'] = "Akun Google '{$email}' berhasil didaftarkan sebagai administrator.";
        }

        redirect(SITE_URL . '/admin/user-list.php');
        exit;
    }

    $current_role  = $role;
    $current_perms = $valid_perms;
}

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        
        <!-- Breadcrumb -->
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="user-list.php" style="color:var(--text-muted);">Manajemen Pengguna</a>
            <span>/</span>
            <span style="color:var(--navy);"><?= $admin_page_title ?></span>
        </div>

        <?php if ($error): ?>
        <div class="alert-lpm alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
        </div>
        <?php endif; ?>

        <div class="admin-table-wrap p-4 p-md-5">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--navy);"><?= $admin_page_title ?></h4>
                    <p class="text-muted small mb-0">
                        Atur identitas kredensial dan batas hak akses halaman sidebar untuk akun ini.
                    </p>
                </div>
                <a href="user-list.php" class="btn btn-sm btn-outline-secondary rounded-pill px-3 py-1">
                    <i class="bi bi-arrow-left me-1"></i> Kembali ke Daftar
                </a>
            </div>

            <form method="post" autocomplete="off">
                
                <div class="alert alert-light border rounded-3 p-3 mb-4 d-flex align-items-center gap-3">
                    <div style="width:40px;height:40px;border-radius:10px;background:rgba(66,133,244,0.1);color:#4285F4;display:flex;align-items:center;justify-content:center;font-size:1.35rem;flex-shrink:0;">
                        <i class="bi bi-google"></i>
                    </div>
                    <div style="font-size:0.82rem;line-height:1.5;" class="text-muted">
                        <strong class="text-dark d-block">Autentikasi Akun Google:</strong>
                        Sistem login admin sepenuhnya terintegrasi dengan Google Sign-In kampus. Cukup daftarkan alamat email Google resmi pengguna di bawah ini tanpa perlu membuat username atau password manual.
                    </div>
                </div>

                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <label class="form-label fw-bold small">
                            <i class="bi bi-google text-danger me-1"></i> Email Akun Google Administrator <span class="text-danger">*</span>
                        </label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-envelope-at"></i></span>
                            <input type="email" name="email" class="form-control" placeholder="Contoh: nama@unika.ac.id" value="<?= e($user_data['email'] ?? ($_POST['email'] ?? '')) ?>" required>
                        </div>
                        <div class="form-text" style="font-size:0.75rem;">
                            Alamat email Google resmi (misal: <code>@unika.ac.id</code>) yang akan digunakan untuk masuk via Google Sign-In.
                        </div>
                    </div>

                    <div class="col-md-6">
                        <label class="form-label fw-bold small">Nama Lengkap / Catatan Pengguna</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light text-muted"><i class="bi bi-person"></i></span>
                            <input type="text" name="nama_lengkap" class="form-control" placeholder="Contoh: Dr. Budi Santoso, M.Kom" value="<?= e($user_data['nama_lengkap'] ?? ($_POST['nama_lengkap'] ?? '')) ?>">
                        </div>
                        <div class="form-text" style="font-size:0.75rem;">
                            Opsional. Jika dikosongkan, nama tampilan akan otomatis diambil dari profil Google saat pengguna pertama kali masuk.
                        </div>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Pemilihan Peran (Role) -->
                <h6 class="fw-bold text-dark mb-2 d-flex align-items-center gap-2">
                    <i class="bi bi-shield-lock-fill text-warning"></i> Peran &amp; Hak Akses (Role)
                </h6>
                <p class="text-muted small mb-3">
                    Pilih tingkat wewenang pengguna dalam mengelola panel kontrol LPM UNIKA.
                </p>

                <div class="row g-3 mb-4">
                    <!-- Option 1: Super Admin -->
                    <div class="col-md-6">
                        <label class="card h-100 p-3 border rounded-3 position-relative cursor-pointer role-card <?= $current_role === 'superadmin' ? 'border-warning bg-warning-subtle shadow-sm' : 'bg-light' ?>" style="cursor:pointer;" for="roleSuper">
                            <div class="d-flex align-items-start gap-3">
                                <input class="form-check-input mt-1 flex-shrink-0" type="radio" name="role" id="roleSuper" value="superadmin" <?= $current_role === 'superadmin' ? 'checked' : '' ?> onchange="toggleRolePanels()">
                                <div>
                                    <div class="fw-bold text-dark d-flex align-items-center gap-2" style="font-size:0.95rem;">
                                        <span>Super Administrator</span>
                                        <span class="badge bg-warning text-dark" style="font-size:0.68rem;">Akses Penuh</span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size:0.8rem;line-height:1.45;">
                                        Dapat mengakses seluruh menu sidebar, mengelola dokumen, konfigurasi sistem, dan mengatur akun pengguna lain.
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>

                    <!-- Option 2: Admin Terbatas -->
                    <div class="col-md-6">
                        <label class="card h-100 p-3 border rounded-3 position-relative cursor-pointer role-card <?= $current_role === 'admin' ? 'border-primary bg-primary-subtle shadow-sm' : 'bg-light' ?>" style="cursor:pointer;" for="roleAdmin">
                            <div class="d-flex align-items-start gap-3">
                                <input class="form-check-input mt-1 flex-shrink-0" type="radio" name="role" id="roleAdmin" value="admin" <?= $current_role === 'admin' ? 'checked' : '' ?> onchange="toggleRolePanels()">
                                <div>
                                    <div class="fw-bold text-dark d-flex align-items-center gap-2" style="font-size:0.95rem;">
                                        <span>Admin Terbatas (Kustom Wewenang)</span>
                                        <span class="badge bg-info text-dark" style="font-size:0.68rem;">Pilihan Halaman</span>
                                    </div>
                                    <div class="text-muted small mt-1" style="font-size:0.8rem;line-height:1.45;">
                                        Hanya dapat mengakses halaman-halaman tertentu di sidebar yang dicentang di bawah ini.
                                    </div>
                                </div>
                            </div>
                        </label>
                    </div>
                </div>

                <!-- Modul Halaman Sidebar Checkbox (Tampil jika Admin Terbatas) -->
                <div id="panelModulesGrid" class="card border rounded-3 p-3 p-md-4 mb-4" style="background:#f8fafc;display:<?= $current_role === 'admin' ? 'block' : 'none' ?>;">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 pb-2 mb-3 border-bottom">
                        <div>
                            <div class="fw-bold text-dark" style="font-size:0.92rem;">
                                <i class="bi bi-layout-sidebar-inset text-primary me-1"></i> Pilih Halaman Sidebar yang Diizinkan:
                            </div>
                            <span class="text-muted small">Centang bagian mana saja yang boleh dilihat dan dikelola oleh admin ini.</span>
                        </div>
                        <div class="btn-group btn-group-sm">
                            <button type="button" class="btn btn-outline-primary" onclick="selectAllModules(true)">
                                <i class="bi bi-check-all me-1"></i> Centang Semua
                            </button>
                            <button type="button" class="btn btn-outline-secondary" onclick="selectAllModules(false)">
                                <i class="bi bi-x me-1"></i> Hapus Semua
                            </button>
                        </div>
                    </div>

                    <div class="row g-3">
                        <?php foreach ($modules_dict as $mod_key => $mod_data): 
                            $is_checked = in_array($mod_key, $current_perms, true);
                        ?>
                        <div class="col-md-6 col-lg-4">
                            <label class="card h-100 p-3 border rounded-3 bg-white module-checkbox-label cursor-pointer" for="mod_<?= $mod_key ?>" style="cursor:pointer;transition:all 0.15s ease;">
                                <div class="d-flex align-items-start gap-2">
                                    <input class="form-check-input mod-chk mt-1 flex-shrink-0" type="checkbox" name="akses_halaman[]" value="<?= $mod_key ?>" id="mod_<?= $mod_key ?>" <?= $is_checked ? 'checked' : '' ?>>
                                    <div>
                                        <div class="fw-bold text-dark d-flex align-items-center gap-1" style="font-size:0.85rem;">
                                            <i class="bi <?= $mod_data['icon'] ?> text-primary"></i>
                                            <span><?= e($mod_data['name']) ?></span>
                                        </div>
                                        <div class="text-muted small mt-1" style="font-size:0.75rem;line-height:1.35;">
                                            <?= e($mod_data['desc']) ?>
                                        </div>
                                    </div>
                                </div>
                            </label>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <hr class="my-4">

                <!-- Status Aktif / Nonaktif -->
                <div class="d-flex align-items-center justify-content-between p-3 rounded-3 bg-light border mb-4">
                    <div>
                        <div class="fw-bold text-dark small">Status Keaktifan Akun</div>
                        <div class="text-muted small" style="font-size:0.75rem;">
                            Jika dinonaktifkan, pengguna tidak akan dapat login ke panel kontrol administrator.
                        </div>
                    </div>
                    <div class="form-check form-switch m-0">
                        <input class="form-check-input" type="checkbox" role="switch" name="status" value="1" id="statusSwitch" <?= ((int)($user_data['status'] ?? 1) === 1) ? 'checked' : '' ?> style="width:2.6rem;height:1.35rem;cursor:pointer;">
                    </div>
                </div>

                <!-- Tombol Submit -->
                <div class="d-flex align-items-center justify-content-end gap-3 pt-3 border-top mt-4">
                    <a href="user-list.php" style="color:var(--text-muted);font-size:0.875rem;text-decoration:none;">Batal</a>
                    <button type="submit" class="btn-submit" style="padding:0.65rem 1.8rem;">
                        <i class="bi bi-check2-circle me-1"></i> Simpan Data Pengguna
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>


function toggleRolePanels() {
    var isSuper = document.getElementById('roleSuper').checked;
    var panel = document.getElementById('panelModulesGrid');
    if (panel) {
        panel.style.display = isSuper ? 'none' : 'block';
    }
    // Update visual styles
    document.querySelectorAll('.role-card').forEach(function(c) {
        c.classList.remove('border-warning', 'bg-warning-subtle', 'border-primary', 'bg-primary-subtle', 'shadow-sm');
        c.classList.add('bg-light');
    });
    if (isSuper) {
        document.querySelector('label[for="roleSuper"]').classList.add('border-warning', 'bg-warning-subtle', 'shadow-sm');
        document.querySelector('label[for="roleSuper"]').classList.remove('bg-light');
    } else {
        document.querySelector('label[for="roleAdmin"]').classList.add('border-primary', 'bg-primary-subtle', 'shadow-sm');
        document.querySelector('label[for="roleAdmin"]').classList.remove('bg-light');
    }
}

function selectAllModules(checkAll) {
    document.querySelectorAll('.mod-chk').forEach(function(cb) {
        cb.checked = checkAll;
    });
}
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
