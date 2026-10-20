<?php
require_once __DIR__ . '/includes/auth.php';
$db = getDB();

$id = (int)($_GET['id'] ?? 0);
$is_edit = false;
$data = [];

if ($id > 0) {
    $stmt = $db->prepare("SELECT * FROM ami_siklus5_rtm WHERE id = ?");
    $stmt->execute([$id]);
    $data = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($data) $is_edit = true;
}

$admin_page_title = $is_edit ? 'Edit Data Rapat Tinjauan Manajemen (Siklus 5)' : 'Tambah Data RTM (Siklus 5)';

$periodes = $db->query("SELECT * FROM ami_periode WHERE is_active = 1 ORDER BY urutan ASC, id DESC")->fetchAll(PDO::FETCH_ASSOC);
$default_periode = $_GET['periode'] ?? ($data['periode'] ?? ($periodes[0]['nama_periode'] ?? '2025/2026'));

// Master Fakultas & Prodi dari akreditasi_prodi
$fakultas_list = $db->query("SELECT DISTINCT fakultas FROM akreditasi_prodi ORDER BY fakultas ASC")->fetchAll(PDO::FETCH_COLUMN);
$prodi_rows = $db->query("SELECT DISTINCT fakultas, program_studi, strata FROM akreditasi_prodi ORDER BY fakultas ASC, program_studi ASC")->fetchAll(PDO::FETCH_ASSOC);

$fakultas_prodi_map = [];
foreach ($prodi_rows as $row) {
    $f = $row['fakultas'];
    if (!isset($fakultas_prodi_map[$f])) {
        $fakultas_prodi_map[$f] = [];
    }
    $fakultas_prodi_map[$f][] = $row['program_studi'] . ' (' . $row['strata'] . ')';
}

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $periode = trim($_POST['periode'] ?? '');
    $tingkat = trim($_POST['tingkat'] ?? 'universitas'); // 'universitas', 'fakultas', 'prodi'
    if (!in_array($tingkat, ['universitas', 'fakultas', 'prodi'])) $tingkat = 'universitas';

    $fakultas = ($tingkat !== 'universitas') ? trim($_POST['fakultas'] ?? '') : null;
    $prodi = ($tingkat === 'prodi') ? trim($_POST['prodi'] ?? '') : null;
    $judul = trim($_POST['judul'] ?? '');
    $notulensi = '';

    if ($periode === '' || $judul === '') {
        $error = 'Periode dan Judul RTM wajib diisi.';
    } elseif ($tingkat === 'fakultas' && empty($fakultas)) {
        $error = 'Harap pilih Fakultas untuk RTM tingkat Fakultas.';
    } elseif ($tingkat === 'prodi' && (empty($fakultas) || empty($prodi))) {
        $error = 'Harap pilih Fakultas dan Program Studi untuk RTM tingkat Prodi.';
    } else {
        $upload_dir = __DIR__ . '/../uploads/ami/siklus5/';
        if (!is_dir($upload_dir)) @mkdir($upload_dir, 0777, true);

        // 1. File Notulensi
        $file_notulensi = $data['file_notulensi'] ?? null;
        if (!empty($_FILES['file_notulensi']['name'])) {
            $ext = strtolower(pathinfo($_FILES['file_notulensi']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'doc', 'docx'])) {
                $error = 'Format file Notulensi harus PDF atau Word.';
            } else {
                $not_name = 'notulensi_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['file_notulensi']['tmp_name'], $upload_dir . $not_name)) {
                    if ($is_edit && !empty($data['file_notulensi'])) {
                        deleteUploadedFile($data['file_notulensi']);
                    }
                    $file_notulensi = 'ami/siklus5/' . $not_name;
                }
            }
        }

        // 2. File Daftar Hadir
        $file_daftar_hadir = $data['file_daftar_hadir'] ?? null;
        if (!$error && !empty($_FILES['file_daftar_hadir']['name'])) {
            $ext = strtolower(pathinfo($_FILES['file_daftar_hadir']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'doc', 'docx', 'xls', 'xlsx'])) {
                $error = 'Format file Daftar Hadir harus PDF, Word, atau Excel.';
            } else {
                $dh_name = 'presensi_rtm_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['file_daftar_hadir']['tmp_name'], $upload_dir . $dh_name)) {
                    if ($is_edit && !empty($data['file_daftar_hadir'])) {
                        deleteUploadedFile($data['file_daftar_hadir']);
                    }
                    $file_daftar_hadir = 'ami/siklus5/' . $dh_name;
                }
            }
        }

        // 3. File Undangan RTM
        $file_undangan = $data['file_undangan'] ?? null;
        if (!$error && !empty($_FILES['file_undangan']['name'])) {
            $ext = strtolower(pathinfo($_FILES['file_undangan']['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['pdf', 'doc', 'docx'])) {
                $error = 'Format file Undangan harus PDF atau Word.';
            } else {
                $und_name = 'undangan_rtm_' . time() . '_' . uniqid() . '.' . $ext;
                if (move_uploaded_file($_FILES['file_undangan']['tmp_name'], $upload_dir . $und_name)) {
                    if ($is_edit && !empty($data['file_undangan'])) {
                        deleteUploadedFile($data['file_undangan']);
                    }
                    $file_undangan = 'ami/siklus5/' . $und_name;
                }
            }
        }

        // 4. Foto Kegiatan RTM
        $existing_photos = [];
        if (!empty($data['foto_kegiatan'])) {
            $decoded = json_decode($data['foto_kegiatan'], true);
            if (is_array($decoded)) $existing_photos = $decoded;
        }

        if (isset($_POST['delete_photos']) && is_array($_POST['delete_photos'])) {
            foreach ($_POST['delete_photos'] as $dp) {
                deleteUploadedFile($dp);
            }
            $existing_photos = array_values(array_diff($existing_photos, $_POST['delete_photos']));
        }

        // Cek jika ada foto hasil crop / edit (Base64)
        $has_cropped = false;
        if (!empty($_POST['cropped_photos']) && is_array($_POST['cropped_photos'])) {
            foreach ($_POST['cropped_photos'] as $c_data) {
                if (preg_match('/^data:image\/(\w+);base64,/', $c_data, $c_match)) {
                    $raw = base64_decode(substr($c_data, strpos($c_data, ',') + 1));
                    if ($raw !== false) {
                        $img = @imagecreatefromstring($raw);
                        if ($img) {
                            $c_name = 'rtm_crop_' . time() . '_' . uniqid() . '.webp';
                            $target_crop_file = $upload_dir . $c_name;
                            imagepalettetotruecolor($img);
                            imagealphablending($img, false);
                            imagesavealpha($img, true);
                            if (imagewebp($img, $target_crop_file, 85)) {
                                $existing_photos[] = 'ami/siklus5/' . $c_name;
                                $has_cropped = true;
                            }
                            imagedestroy($img);
                        }
                    }
                }
            }
        }

        // Proses upload semua foto kegiatan baru dari $_FILES (baik dari multi-select, drag & drop, dll)
        if (!$error && !empty($_FILES['foto_kegiatan']['name'][0])) {
            $count = count($_FILES['foto_kegiatan']['name']);
            for ($i = 0; $i < $count; $i++) {
                if ($_FILES['foto_kegiatan']['error'][$i] === UPLOAD_ERR_OK) {
                    $tmp_name = $_FILES['foto_kegiatan']['tmp_name'][$i];
                    $saved_webp = convertAndSaveWebP($tmp_name, $upload_dir, 'rtm_', 85, 1920);
                    if ($saved_webp) {
                        $existing_photos[] = 'ami/siklus5/' . $saved_webp;
                    }
                }
            }
        }

        $foto_kegiatan_json = !empty($existing_photos) ? json_encode($existing_photos) : null;

        // Pengaturan Pembatasan Akses Masing-Masing Dokumen
        $res_undangan_is_restricted     = !empty($_POST['undangan_is_restricted']) ? 1 : 0;
        $res_undangan_max_pages         = (int)($_POST['undangan_max_preview_pages'] ?? 3);
        $res_undangan_allowed_domains   = parseSubmittedAllowedDomains('undangan');

        $res_daftar_hadir_is_restricted   = !empty($_POST['daftar_hadir_is_restricted']) ? 1 : 0;
        $res_daftar_hadir_max_pages       = (int)($_POST['daftar_hadir_max_preview_pages'] ?? 3);
        $res_daftar_hadir_allowed_domains = parseSubmittedAllowedDomains('daftar_hadir');

        $res_notulensi_is_restricted     = !empty($_POST['notulensi_is_restricted']) ? 1 : 0;
        $res_notulensi_max_pages         = (int)($_POST['notulensi_max_preview_pages'] ?? 3);
        $res_notulensi_allowed_domains   = parseSubmittedAllowedDomains('notulensi');

        // Status restriction global (kompatibilitas)
        $is_restricted     = ($res_undangan_is_restricted || $res_daftar_hadir_is_restricted || $res_notulensi_is_restricted) ? 1 : 0;
        $max_preview_pages = 3;
        $allowed_domains   = '@unika.ac.id,@student.unika.ac.id';

        if (!$error) {
            if ($is_edit) {
                $stmt = $db->prepare("
                    UPDATE ami_siklus5_rtm 
                    SET periode = ?, tingkat = ?, fakultas = ?, prodi = ?, judul = ?, notulensi = '', 
                        file_notulensi = ?, file_daftar_hadir = ?, file_undangan = ?, foto_kegiatan = ?, 
                        is_restricted = ?, max_preview_pages = ?, allowed_domains = ?,
                        is_restricted_undangan = ?, max_preview_pages_undangan = ?, allowed_domains_undangan = ?,
                        is_restricted_daftar_hadir = ?, max_preview_pages_daftar_hadir = ?, allowed_domains_daftar_hadir = ?,
                        is_restricted_notulensi = ?, max_preview_pages_notulensi = ?, allowed_domains_notulensi = ?
                    WHERE id = ?
                ");
                $stmt->execute([
                    $periode, $tingkat, $fakultas, $prodi, $judul,
                    $file_notulensi, $file_daftar_hadir, $file_undangan, $foto_kegiatan_json,
                    $is_restricted, $max_preview_pages, $allowed_domains,
                    $res_undangan_is_restricted, $res_undangan_max_pages, $res_undangan_allowed_domains,
                    $res_daftar_hadir_is_restricted, $res_daftar_hadir_max_pages, $res_daftar_hadir_allowed_domains,
                    $res_notulensi_is_restricted, $res_notulensi_max_pages, $res_notulensi_allowed_domains,
                    $id
                ]);
                $_SESSION['flash'] = 'Data Rapat Tinjauan Manajemen berhasil diperbarui.';
            } else {
                $stmt = $db->prepare("
                    INSERT INTO ami_siklus5_rtm 
                    (periode, tingkat, fakultas, prodi, judul, notulensi, file_notulensi, file_daftar_hadir, file_undangan, foto_kegiatan, 
                     is_restricted, max_preview_pages, allowed_domains,
                     is_restricted_undangan, max_preview_pages_undangan, allowed_domains_undangan,
                     is_restricted_daftar_hadir, max_preview_pages_daftar_hadir, allowed_domains_daftar_hadir,
                     is_restricted_notulensi, max_preview_pages_notulensi, allowed_domains_notulensi) 
                    VALUES (?, ?, ?, ?, ?, '', ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([
                    $periode, $tingkat, $fakultas, $prodi, $judul,
                    $file_notulensi, $file_daftar_hadir, $file_undangan, $foto_kegiatan_json,
                    $is_restricted, $max_preview_pages, $allowed_domains,
                    $res_undangan_is_restricted, $res_undangan_max_pages, $res_undangan_allowed_domains,
                    $res_daftar_hadir_is_restricted, $res_daftar_hadir_max_pages, $res_daftar_hadir_allowed_domains,
                    $res_notulensi_is_restricted, $res_notulensi_max_pages, $res_notulensi_allowed_domains
                ]);
                $_SESSION['flash'] = 'Data Rapat Tinjauan Manajemen berhasil ditambahkan.';
            }
            redirect(SITE_URL . '/admin/ami-siklus-list.php?tab=siklus5&periode=' . urlencode($periode));
        }
    }
}

$cur_tingkat = $data['tingkat'] ?? ($_POST['tingkat'] ?? 'universitas');
$cur_fakultas = $data['fakultas'] ?? ($_POST['fakultas'] ?? '');
$cur_prodi = $data['prodi'] ?? ($_POST['prodi'] ?? '');
$current_photos = !empty($data['foto_kegiatan']) ? json_decode($data['foto_kegiatan'], true) : [];

// Pembatasan terpisah masing-masing dokumen
$res_undangan_is_restricted   = (int)($data['is_restricted_undangan'] ?? $data['is_restricted'] ?? 0);
$res_undangan_max_pages       = (int)($data['max_preview_pages_undangan'] ?? $data['max_preview_pages'] ?? 3);
$res_undangan_allowed_domains = $data['allowed_domains_undangan'] ?? $data['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id';

$res_daftar_hadir_is_restricted   = (int)($data['is_restricted_daftar_hadir'] ?? $data['is_restricted'] ?? 0);
$res_daftar_hadir_max_pages       = (int)($data['max_preview_pages_daftar_hadir'] ?? $data['max_preview_pages'] ?? 3);
$res_daftar_hadir_allowed_domains = $data['allowed_domains_daftar_hadir'] ?? $data['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id';

$res_notulensi_is_restricted   = (int)($data['is_restricted_notulensi'] ?? $data['is_restricted'] ?? 0);
$res_notulensi_max_pages       = (int)($data['max_preview_pages_notulensi'] ?? $data['max_preview_pages'] ?? 3);
$res_notulensi_allowed_domains = $data['allowed_domains_notulensi'] ?? $data['allowed_domains'] ?? '@unika.ac.id,@student.unika.ac.id';

require_once __DIR__ . '/includes/admin-header.php';
?>

<div class="row justify-content-center">
    <div class="col-xl-9 col-lg-10">
        <div style="display:flex;align-items:center;gap:0.5rem;margin-bottom:1.5rem;font-size:0.85rem;color:var(--text-muted);">
            <a href="dashboard.php" style="color:var(--text-muted);">Dashboard</a>
            <span>/</span>
            <a href="ami-siklus-list.php?tab=siklus5&periode=<?= urlencode($default_periode) ?>" style="color:var(--text-muted);">Siklus 5 (RTM)</a>
            <span>/</span>
            <span style="color:var(--navy);"><?= $admin_page_title ?></span>
        </div>

        <?php if ($error): ?>
        <div class="alert-lpm alert-danger mb-4">
            <i class="bi bi-exclamation-triangle-fill me-2"></i><?= e($error) ?>
        </div>
        <?php endif; ?>

        <div class="admin-table-wrap p-4 p-md-5">
            <div class="d-flex align-items-center justify-content-between pb-3 mb-4 border-bottom flex-wrap gap-2">
                <div>
                    <h4 class="fw-bold mb-1" style="color:var(--navy);"><?= $admin_page_title ?></h4>
                    <p class="text-muted small mb-0">
                        Kelola data Rapat Tinjauan Manajemen (RTM) tingkat Universitas, Fakultas, dan Program Studi.
                    </p>
                </div>
                <span class="badge bg-purple px-3 py-2" style="font-size:0.85rem;">
                    <i class="bi bi-people-fill me-1"></i> Siklus 5: RTM
                </span>
            </div>

            <form method="post" enctype="multipart/form-data">
                <div class="row g-4">
                    <!-- 1. Metadata Tingkat RTM -->
                    <div class="col-12">
                        <div class="p-3 bg-light rounded-3 border">
                            <h6 class="fw-bold text-dark mb-3"><i class="bi bi-diagram-3-fill me-2 text-primary"></i>Lingkup Tingkat RTM</h6>
                            <div class="row g-3">
                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Periode AMI <span class="text-danger">*</span></label>
                                    <select name="periode" class="form-select" required>
                                        <?php foreach ($periodes as $p): ?>
                                        <option value="<?= e($p['nama_periode']) ?>" <?= $default_periode === $p['nama_periode'] ? 'selected' : '' ?>>
                                            <?= e($p['nama_periode']) ?>
                                        </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-md-4">
                                    <label class="form-label fw-bold small">Tingkatan RTM <span class="text-danger">*</span></label>
                                    <select name="tingkat" id="tingkatSelect" class="form-select" onchange="handleTingkatChange()" required>
                                        <option value="universitas" <?= $cur_tingkat === 'universitas' ? 'selected' : '' ?>>Tingkat Universitas</option>
                                        <option value="fakultas" <?= $cur_tingkat === 'fakultas' ? 'selected' : '' ?>>Tingkat Fakultas</option>
                                        <option value="prodi" <?= $cur_tingkat === 'prodi' ? 'selected' : '' ?>>Tingkat Program Studi</option>
                                    </select>
                                </div>

                                <div class="col-md-4" id="fakultasGroup" style="display: <?= $cur_tingkat !== 'universitas' ? 'block' : 'none' ?>;">
                                    <label class="form-label fw-bold small">Pilih Fakultas <span class="text-danger">*</span></label>
                                    <select name="fakultas" id="fakultasSelect" class="form-select" onchange="handleFakultasChange()">
                                        <option value="">-- Pilih Fakultas --</option>
                                        <?php foreach ($fakultas_list as $f): ?>
                                        <option value="<?= e($f) ?>" <?= $cur_fakultas === $f ? 'selected' : '' ?>><?= e($f) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>

                                <div class="col-12" id="prodiGroup" style="display: <?= $cur_tingkat === 'prodi' ? 'block' : 'none' ?>;">
                                    <label class="form-label fw-bold small">Pilih Program Studi <span class="text-danger">*</span></label>
                                    <select name="prodi" id="prodiSelect" class="form-select">
                                        <option value="">-- Pilih Program Studi --</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 2. Judul Kegiatan -->
                    <div class="col-12">
                        <label class="form-label fw-bold small">Judul Kegiatan RTM <span class="text-danger">*</span></label>
                        <input type="text" name="judul" class="form-control" placeholder="Contoh: Rapat Tinjauan Manajemen Fakultas Ilmu Komputer Periode 2025/2026" value="<?= e($data['judul'] ?? ($_POST['judul'] ?? '')) ?>" required>
                    </div>

                    <!-- 3. Berkas Dokumen (Undangan, Daftar Hadir, Notulensi) dengan Button Pembatasan Sendiri-Sendiri -->
                    <div class="col-md-4">
                        <div class="p-3 border rounded h-100 bg-white d-flex flex-column">
                            <label class="form-label fw-bold small d-flex align-items-center justify-content-between mb-1">
                                <span><i class="bi bi-envelope-paper text-warning me-1"></i> Undangan</span>
                            </label>
                            <input type="file" name="file_undangan" class="form-control check-filesize" accept=".pdf,.doc,.docx" data-max-mb="20">
                            <div class="form-text mt-1">
                                <span class="badge bg-light text-dark border"><i class="bi bi-shield-check text-success me-1"></i>Maks 20 MB</span> PDF / DOC / DOCX
                            </div>

                            <?php if (!empty($data['file_undangan'])): ?>
                            <div class="mt-2 p-2 bg-light rounded d-flex align-items-center justify-content-between">
                                <span class="small text-truncate" style="max-width:140px;"><?= basename($data['file_undangan']) ?></span>
                                <a href="<?= SITE_URL ?>/uploads/<?= e($data['file_undangan']) ?>" target="_blank" class="btn btn-xs btn-outline-warning py-0 px-2" style="font-size:0.75rem;">
                                    Lihat File
                                </a>
                            </div>
                            <?php endif; ?>

                            <!-- Kontrol Proteksi Akses Berkas Undangan (Switch On/Off Kiri Kanan) -->
                            <div class="mt-auto pt-3 border-top">
                                <div class="d-flex align-items-center justify-content-between p-2 rounded-3" style="background:#FFFBEB;border:1px solid #FDE68A;">
                                    <div class="d-flex align-items-center gap-2 min-w-0 me-1">
                                        <div style="width:28px;height:28px;border-radius:6px;background:<?= $res_undangan_is_restricted ? 'rgba(217,119,6,0.15)' : '#E2E8F0' ?>;color:<?= $res_undangan_is_restricted ? '#D97706' : '#64748B' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;" id="iconBoxUndanganS5">
                                            <i class="bi bi-shield-lock-fill" style="font-size:0.85rem;"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-bold text-dark text-truncate" style="font-size:0.78rem;">Proteksi Akses</div>
                                            <div class="text-muted text-truncate" id="statusLabelUndanganS5" style="font-size:0.7rem;">
                                                <?= $res_undangan_is_restricted ? '<span class="text-warning fw-bold">Dibatasi</span>' : 'Bebas / Publik' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch m-0 flex-shrink-0">
                                        <input class="form-check-input ami-protect-toggle" type="checkbox" role="switch" 
                                               id="toggleUndanganS5" name="undangan_is_restricted" value="1" 
                                               <?= $res_undangan_is_restricted ? 'checked' : '' ?>
                                               data-panel="panelUndanganS5"
                                               data-icon="iconBoxUndanganS5"
                                               data-label="statusLabelUndanganS5"
                                               data-color="#D97706"
                                               style="width:2.4rem;height:1.25rem;cursor:pointer;">
                                    </div>
                                </div>
                                <div id="panelUndanganS5" class="mt-2 p-2 rounded-2 border bg-light" style="display:<?= $res_undangan_is_restricted ? 'block' : 'none' ?>;font-size:0.75rem;">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold text-dark mb-1" style="font-size:0.72rem;">
                                            <i class="bi bi-file-earmark-ruled text-warning me-1"></i>Batas Pratinjau Publik:
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" min="0" max="50" class="form-control text-center fw-bold" name="undangan_max_preview_pages" value="<?= $res_undangan_max_pages ?>" style="max-width:70px;">
                                            <span class="input-group-text bg-white small">Hal</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label fw-bold text-dark mb-1" style="font-size:0.72rem;">
                                            <i class="bi bi-person-check text-success me-1"></i>Izin Akses Penuh:
                                        </label>
                                        <div class="d-flex flex-column gap-1">
                                            <label class="form-check m-0 d-flex align-items-center gap-1 cursor-pointer">
                                                <input class="form-check-input m-0" type="checkbox" name="undangan_allowed_domains_arr[]" value="@unika.ac.id" <?= (strpos($res_undangan_allowed_domains, '@unika.ac.id') !== false || !$res_undangan_is_restricted) ? 'checked' : '' ?>>
                                                <span class="small" style="font-size:0.72rem;">Dosen / Tendik</span>
                                            </label>
                                            <label class="form-check m-0 d-flex align-items-center gap-1 cursor-pointer">
                                                <input class="form-check-input m-0" type="checkbox" name="undangan_allowed_domains_arr[]" value="@student.unika.ac.id" <?= (strpos($res_undangan_allowed_domains, '@student.unika.ac.id') !== false || !$res_undangan_is_restricted) ? 'checked' : '' ?>>
                                                <span class="small" style="font-size:0.72rem;">Mahasiswa</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-top">
                                        <label class="form-label fw-bold text-dark mb-1" style="font-size:0.72rem;">
                                            <i class="bi bi-at text-info me-1"></i>Akun / Domain Khusus:
                                        </label>
                                        <input type="text" class="form-control form-control-sm" name="undangan_custom_domains" value="<?= e(extractCustomDomains($res_undangan_allowed_domains)) ?>" placeholder="Contoh: @gmail.com, auditor@partner.id" style="font-size:0.73rem;">
                                        <div class="text-muted" style="font-size:0.67rem;line-height:1.2;margin-top:2px;">
                                            Opsional. Pisahkan koma jika lebih dari satu akun/domain.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 border rounded h-100 bg-white d-flex flex-column">
                            <label class="form-label fw-bold small d-flex align-items-center justify-content-between mb-1">
                                <span><i class="bi bi-card-checklist text-success me-1"></i> Daftar Hadir</span>
                            </label>
                            <input type="file" name="file_daftar_hadir" class="form-control check-filesize" accept=".pdf,.doc,.docx,.xls,.xlsx" data-max-mb="20">
                            <div class="form-text mt-1">
                                <span class="badge bg-light text-dark border"><i class="bi bi-shield-check text-success me-1"></i>Maks 20 MB</span> PDF / Word / Excel
                            </div>

                            <?php if (!empty($data['file_daftar_hadir'])): ?>
                            <div class="mt-2 p-2 bg-light rounded d-flex align-items-center justify-content-between">
                                <span class="small text-truncate" style="max-width:140px;"><?= basename($data['file_daftar_hadir']) ?></span>
                                <a href="<?= SITE_URL ?>/uploads/<?= e($data['file_daftar_hadir']) ?>" target="_blank" class="btn btn-xs btn-outline-success py-0 px-2" style="font-size:0.75rem;">
                                    Lihat File
                                </a>
                            </div>
                            <?php endif; ?>

                            <!-- Kontrol Proteksi Akses Berkas Daftar Hadir (Switch On/Off Kiri Kanan) -->
                            <div class="mt-auto pt-3 border-top">
                                <div class="d-flex align-items-center justify-content-between p-2 rounded-3" style="background:#ECFDF5;border:1px solid #A7F3D0;">
                                    <div class="d-flex align-items-center gap-2 min-w-0 me-1">
                                        <div style="width:28px;height:28px;border-radius:6px;background:<?= $res_daftar_hadir_is_restricted ? 'rgba(16,185,129,0.15)' : '#E2E8F0' ?>;color:<?= $res_daftar_hadir_is_restricted ? '#10B981' : '#64748B' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;" id="iconBoxDHS5">
                                            <i class="bi bi-shield-lock-fill" style="font-size:0.85rem;"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-bold text-dark text-truncate" style="font-size:0.78rem;">Proteksi Akses</div>
                                            <div class="text-muted text-truncate" id="statusLabelDHS5" style="font-size:0.7rem;">
                                                <?= $res_daftar_hadir_is_restricted ? '<span class="text-success fw-bold">Dibatasi</span>' : 'Bebas / Publik' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch m-0 flex-shrink-0">
                                        <input class="form-check-input ami-protect-toggle" type="checkbox" role="switch" 
                                               id="toggleDHS5" name="daftar_hadir_is_restricted" value="1" 
                                               <?= $res_daftar_hadir_is_restricted ? 'checked' : '' ?>
                                               data-panel="panelDHS5"
                                               data-icon="iconBoxDHS5"
                                               data-label="statusLabelDHS5"
                                               data-color="#10B981"
                                               style="width:2.4rem;height:1.25rem;cursor:pointer;">
                                    </div>
                                </div>
                                <div id="panelDHS5" class="mt-2 p-2 rounded-2 border bg-light" style="display:<?= $res_daftar_hadir_is_restricted ? 'block' : 'none' ?>;font-size:0.75rem;">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold text-dark mb-1" style="font-size:0.72rem;">
                                            <i class="bi bi-file-earmark-ruled text-success me-1"></i>Batas Pratinjau Publik:
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" min="0" max="50" class="form-control text-center fw-bold" name="daftar_hadir_max_preview_pages" value="<?= $res_daftar_hadir_max_pages ?>" style="max-width:70px;">
                                            <span class="input-group-text bg-white small">Hal</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label fw-bold text-dark mb-1" style="font-size:0.72rem;">
                                            <i class="bi bi-person-check text-success me-1"></i>Izin Akses Penuh:
                                        </label>
                                        <div class="d-flex flex-column gap-1">
                                            <label class="form-check m-0 d-flex align-items-center gap-1 cursor-pointer">
                                                <input class="form-check-input m-0" type="checkbox" name="daftar_hadir_allowed_domains_arr[]" value="@unika.ac.id" <?= (strpos($res_daftar_hadir_allowed_domains, '@unika.ac.id') !== false || !$res_daftar_hadir_is_restricted) ? 'checked' : '' ?>>
                                                <span class="small" style="font-size:0.72rem;">Dosen / Tendik</span>
                                            </label>
                                            <label class="form-check m-0 d-flex align-items-center gap-1 cursor-pointer">
                                                <input class="form-check-input m-0" type="checkbox" name="daftar_hadir_allowed_domains_arr[]" value="@student.unika.ac.id" <?= (strpos($res_daftar_hadir_allowed_domains, '@student.unika.ac.id') !== false || !$res_daftar_hadir_is_restricted) ? 'checked' : '' ?>>
                                                <span class="small" style="font-size:0.72rem;">Mahasiswa</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-top">
                                        <label class="form-label fw-bold text-dark mb-1" style="font-size:0.72rem;">
                                            <i class="bi bi-at text-info me-1"></i>Akun / Domain Khusus:
                                        </label>
                                        <input type="text" class="form-control form-control-sm" name="daftar_hadir_custom_domains" value="<?= e(extractCustomDomains($res_daftar_hadir_allowed_domains)) ?>" placeholder="Contoh: @gmail.com, auditor@partner.id" style="font-size:0.73rem;">
                                        <div class="text-muted" style="font-size:0.67rem;line-height:1.2;margin-top:2px;">
                                            Opsional. Pisahkan koma jika lebih dari satu akun/domain.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-4">
                        <div class="p-3 border rounded h-100 bg-white d-flex flex-column">
                            <label class="form-label fw-bold small d-flex align-items-center justify-content-between mb-1">
                                <span><i class="bi bi-file-earmark-text text-primary me-1"></i> Notulensi</span>
                            </label>
                            <input type="file" name="file_notulensi" class="form-control check-filesize" accept=".pdf,.doc,.docx" data-max-mb="20">
                            <div class="form-text mt-1">
                                <span class="badge bg-light text-dark border"><i class="bi bi-shield-check text-success me-1"></i>Maks 20 MB</span> PDF / DOC / DOCX
                            </div>

                            <?php if (!empty($data['file_notulensi'])): ?>
                            <div class="mt-2 p-2 bg-light rounded d-flex align-items-center justify-content-between">
                                <span class="small text-truncate" style="max-width:140px;"><?= basename($data['file_notulensi']) ?></span>
                                <a href="<?= SITE_URL ?>/uploads/<?= e($data['file_notulensi']) ?>" target="_blank" class="btn btn-xs btn-outline-primary py-0 px-2" style="font-size:0.75rem;">
                                    Lihat File
                                </a>
                            </div>
                            <?php endif; ?>

                            <!-- Kontrol Proteksi Akses Berkas Notulensi (Switch On/Off Kiri Kanan) -->
                            <div class="mt-auto pt-3 border-top">
                                <div class="d-flex align-items-center justify-content-between p-2 rounded-3" style="background:#EFF6FF;border:1px solid #BFDBFE;">
                                    <div class="d-flex align-items-center gap-2 min-w-0 me-1">
                                        <div style="width:28px;height:28px;border-radius:6px;background:<?= $res_notulensi_is_restricted ? 'rgba(59,130,246,0.15)' : '#E2E8F0' ?>;color:<?= $res_notulensi_is_restricted ? '#3B82F6' : '#64748B' ?>;display:flex;align-items:center;justify-content:center;flex-shrink:0;" id="iconBoxNotulensiS5">
                                            <i class="bi bi-shield-lock-fill" style="font-size:0.85rem;"></i>
                                        </div>
                                        <div class="min-w-0">
                                            <div class="fw-bold text-dark text-truncate" style="font-size:0.78rem;">Proteksi Akses</div>
                                            <div class="text-muted text-truncate" id="statusLabelNotulensiS5" style="font-size:0.7rem;">
                                                <?= $res_notulensi_is_restricted ? '<span class="text-primary fw-bold">Dibatasi</span>' : 'Bebas / Publik' ?>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="form-check form-switch m-0 flex-shrink-0">
                                        <input class="form-check-input ami-protect-toggle" type="checkbox" role="switch" 
                                               id="toggleNotulensiS5" name="notulensi_is_restricted" value="1" 
                                               <?= $res_notulensi_is_restricted ? 'checked' : '' ?>
                                               data-panel="panelNotulensiS5"
                                               data-icon="iconBoxNotulensiS5"
                                               data-label="statusLabelNotulensiS5"
                                               data-color="#3B82F6"
                                               style="width:2.4rem;height:1.25rem;cursor:pointer;">
                                    </div>
                                </div>
                                <div id="panelNotulensiS5" class="mt-2 p-2 rounded-2 border bg-light" style="display:<?= $res_notulensi_is_restricted ? 'block' : 'none' ?>;font-size:0.75rem;">
                                    <div class="mb-2">
                                        <label class="form-label fw-bold text-dark mb-1" style="font-size:0.72rem;">
                                            <i class="bi bi-file-earmark-ruled text-primary me-1"></i>Batas Pratinjau Publik:
                                        </label>
                                        <div class="input-group input-group-sm">
                                            <input type="number" min="0" max="50" class="form-control text-center fw-bold" name="notulensi_max_preview_pages" value="<?= $res_notulensi_max_pages ?>" style="max-width:70px;">
                                            <span class="input-group-text bg-white small">Hal</span>
                                        </div>
                                    </div>
                                    <div>
                                        <label class="form-label fw-bold text-dark mb-1" style="font-size:0.72rem;">
                                            <i class="bi bi-person-check text-success me-1"></i>Izin Akses Penuh:
                                        </label>
                                        <div class="d-flex flex-column gap-1">
                                            <label class="form-check m-0 d-flex align-items-center gap-1 cursor-pointer">
                                                <input class="form-check-input m-0" type="checkbox" name="notulensi_allowed_domains_arr[]" value="@unika.ac.id" <?= (strpos($res_notulensi_allowed_domains, '@unika.ac.id') !== false || !$res_notulensi_is_restricted) ? 'checked' : '' ?>>
                                                <span class="small" style="font-size:0.72rem;">Dosen / Tendik</span>
                                            </label>
                                            <label class="form-check m-0 d-flex align-items-center gap-1 cursor-pointer">
                                                <input class="form-check-input m-0" type="checkbox" name="notulensi_allowed_domains_arr[]" value="@student.unika.ac.id" <?= (strpos($res_notulensi_allowed_domains, '@student.unika.ac.id') !== false || !$res_notulensi_is_restricted) ? 'checked' : '' ?>>
                                                <span class="small" style="font-size:0.72rem;">Mahasiswa</span>
                                            </label>
                                        </div>
                                    </div>
                                    <div class="mt-2 pt-2 border-top">
                                        <label class="form-label fw-bold text-dark mb-1" style="font-size:0.72rem;">
                                            <i class="bi bi-at text-info me-1"></i>Akun / Domain Khusus:
                                        </label>
                                        <input type="text" class="form-control form-control-sm" name="notulensi_custom_domains" value="<?= e(extractCustomDomains($res_notulensi_allowed_domains)) ?>" placeholder="Contoh: @gmail.com, auditor@partner.id" style="font-size:0.73rem;">
                                        <div class="text-muted" style="font-size:0.67rem;line-height:1.2;margin-top:2px;">
                                            Opsional. Pisahkan koma jika lebih dari satu akun/domain.
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Galeri Foto Kegiatan RTM (Drag & Drop + Ctrl Multi-Select) -->
                    <div class="col-12">
                        <div class="p-3 border rounded bg-white">
                            <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                                <label class="form-label fw-bold small mb-0">
                                    <i class="bi bi-images text-purple me-1"></i> Unggah Foto Kegiatan Rapat Tinjauan Manajemen
                                </label>
                                <span class="badge bg-light text-muted border" id="photoCountBadgeS5">0 foto baru dipilih</span>
                            </div>

                            <!-- Dropzone Area -->
                            <div id="fotoDropzoneS5" class="p-4 rounded-3 text-center position-relative" 
                                 style="border:2px dashed #93C5FD;background:#F8FAFC;cursor:pointer;transition:all 0.2s ease;">
                                <div class="mb-2">
                                    <i class="bi bi-cloud-arrow-up-fill text-primary" style="font-size:2.5rem;"></i>
                                </div>
                                <div class="fw-bold text-dark" style="font-size:0.95rem;">
                                    Tarik &amp; Lepas (Drag &amp; Drop) Foto di Sini
                                </div>
                                <div class="text-muted small mt-1">
                                    atau <span class="text-primary fw-bold text-decoration-underline">Klik untuk Memilih File</span> (Bisa langsung pilih banyak foto sekaligus dengan <strong>Ctrl + Klik</strong> atau <strong>Ctrl + A</strong>)
                                </div>
                                <div class="mt-2">
                                    <span class="badge bg-white text-muted border me-1" style="font-size:0.72rem;">JPG, PNG, WebP</span>
                                    <span class="badge bg-white text-muted border" style="font-size:0.72rem;">Maks 10 MB per foto</span>
                                </div>
                            </div>
                            <input type="file" name="foto_kegiatan[]" id="fotoKegiatanInputS5" class="d-none" accept="image/jpeg,image/png,image/webp" multiple>

                            <!-- Live Preview Foto Baru yang Dipilih -->
                            <div id="selectedPhotosContainerS5" class="mt-3 d-none">
                                <div class="small fw-bold text-primary mb-2 d-flex align-items-center justify-content-between">
                                    <span><i class="bi bi-check-circle-fill text-success me-1"></i> Foto Baru yang Siap Diunggah:</span>
                                    <button type="button" class="btn btn-xs btn-outline-danger py-0 px-2" id="btnClearAllNewPhotosS5" style="font-size:0.72rem;">Hapus Semua Pilihan</button>
                                </div>
                                <div class="row g-2" id="selectedPhotosGridS5"></div>
                            </div>

                            <!-- Kontainer Hasil Foto Tambahan dari Editor Crop -->
                            <div id="croppedPhotosContainerS5" class="mt-3 d-none">
                                <div class="small fw-bold text-success mb-2">
                                    <i class="bi bi-crop text-warning me-1"></i> Foto Hasil Crop / Penyesuaian:
                                </div>
                                <div class="row g-2" id="croppedPhotosGridS5"></div>
                            </div>

                            <?php if (!empty($current_photos)): ?>
                            <div class="mt-3">
                                <div class="small fw-bold text-muted mb-2">Foto Dokumentasi Tersimpan:</div>
                                <div class="row g-2">
                                    <?php foreach ($current_photos as $idx => $photo): ?>
                                    <div class="col-6 col-sm-4 col-md-3" id="photoItemWrapS5_<?= $idx ?>">
                                        <div class="card p-1 border position-relative h-100 shadow-sm" id="cardExistingS5_<?= $idx ?>">
                                            <div style="height:110px; border-radius:4px; overflow:hidden; background:#000;">
                                                <img src="<?= SITE_URL ?>/uploads/<?= e($photo) ?>" alt="Foto" id="imgThumbS5_<?= $idx ?>" style="height:100%;object-fit:cover;width:100%;">
                                            </div>
                                            <div class="d-flex align-items-center justify-content-between mt-2 px-1 gap-1 flex-wrap">
                                                <button type="button" class="btn btn-xs btn-outline-primary py-1 px-2 fw-semibold btn-crop-existing-s5 d-inline-flex align-items-center gap-1"
                                                        data-photo-url="<?= SITE_URL ?>/uploads/<?= e($photo) ?>"
                                                        data-photo-path="<?= e($photo) ?>"
                                                        data-idx="<?= $idx ?>"
                                                        title="Sesuaikan, putar, atau potong foto tersimpan ini"
                                                        style="font-size:0.75rem; border-radius:4px;">
                                                    <i class="bi bi-crop text-warning"></i> Sesuaikan
                                                </button>
                                                <div class="form-check m-0">
                                                    <input class="form-check-input check-del-s5" type="checkbox" name="delete_photos[]" value="<?= e($photo) ?>" id="delPhotoRtm_<?= $idx ?>" data-idx="<?= $idx ?>">
                                                    <label class="form-check-label text-danger" for="delPhotoRtm_<?= $idx ?>" style="font-size:0.75rem; cursor:pointer;">Hapus</label>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-3 mt-4 pt-3 border-top">
                    <button type="submit" class="btn btn-primary px-4 py-2">
                        <i class="bi bi-save me-1"></i> Simpan Data RTM
                    </button>
                    <a href="ami-siklus-list.php?tab=siklus5&periode=<?= urlencode($default_periode) ?>" class="btn btn-light px-3 py-2">
                        Batal
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal Cropper S5 -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.css">
<div class="modal fade" id="modalCropS5" tabindex="-1" aria-labelledby="modalCropS5Label" aria-hidden="true" data-bs-backdrop="static">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content" style="border-radius:16px;overflow:hidden;border:none;box-shadow:0 25px 50px rgba(0,0,0,0.3);">
            <div class="modal-header text-white" style="background:linear-gradient(135deg, #0A192F 0%, #1E3A8A 100%);padding:1.25rem 1.75rem;">
                <div class="d-flex align-items-center gap-2">
                    <i class="bi bi-crop" style="font-size:1.4rem;color:#FFD54F;"></i>
                    <div>
                        <h5 class="modal-title mb-0 fw-bold" id="modalCropS5Label">Editor Gambar RTM: Crop, Rotate, Flip &amp; Zoom</h5>
                        <small style="color:rgba(255,255,255,0.85);">Sesuaikan framing kartu foto dokumentasi rapat tinjauan manajemen.</small>
                    </div>
                </div>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body p-3 p-md-4" style="background:#F1F5F9;">
                <div class="crop-modal-container mb-3 shadow-inner" style="max-height:480px;background:#0F172A;display:flex;align-items:center;justify-content:center;overflow:hidden;border-radius:10px;">
                    <img id="cropperSourceImgS5" src="" alt="Source" crossorigin="anonymous" style="max-width:100%;max-height:460px;display:block;">
                </div>
                <div class="card p-3 border shadow-sm rounded-3 bg-white">
                    <div class="row g-2 align-items-center">
                        <div class="col-lg-4 col-md-6 col-12 d-flex gap-2">
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipHS5">
                                <i class="bi bi-symmetry-vertical text-warning"></i> Flip H (⇄)
                            </button>
                            <button type="button" class="btn btn-dark flex-fill fw-bold d-flex align-items-center justify-content-center gap-1 py-2" id="btnFlipVS5">
                                <i class="bi bi-symmetry-horizontal text-warning"></i> Flip V (⇅)
                            </button>
                        </div>
                        <div class="col-lg-3 col-md-6 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateLeftS5">
                                <i class="bi bi-arrow-counterclockwise"></i> ↺ 90°
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnRotateRightS5">
                                <i class="bi bi-arrow-clockwise"></i> ↻ 90°
                            </button>
                        </div>
                        <div class="col-lg-5 col-12 d-flex gap-1">
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomInS5">
                                <i class="bi bi-zoom-in"></i> +
                            </button>
                            <button type="button" class="btn btn-outline-secondary flex-fill fw-bold py-2" id="btnZoomOutS5">
                                <i class="bi bi-zoom-out"></i> -
                            </button>
                            <button type="button" class="btn btn-outline-danger flex-fill fw-bold py-2" id="btnResetCropS5">
                                <i class="bi bi-arrow-repeat"></i> Reset
                            </button>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 mt-3 pt-3 border-top flex-wrap">
                        <span class="text-muted small fw-bold me-1 text-uppercase">Rasio:</span>
                        <div class="btn-group btn-group-sm" role="group">
                            <button type="button" class="btn btn-outline-primary active ratio-btn-s5 fw-semibold" data-ratio="1.777778">16:9 Card</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-s5 fw-semibold" data-ratio="1.333333">4:3 Galeri</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-s5 fw-semibold" data-ratio="1">1:1 Persegi</button>
                            <button type="button" class="btn btn-outline-primary ratio-btn-s5 fw-semibold" data-ratio="NaN">Bebas</button>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer d-flex justify-content-between" style="background:#ffffff;border-top:1px solid #E2E8F0;padding:1rem 1.75rem;">
                <button type="button" class="btn btn-outline-secondary px-4 py-2 fw-semibold" data-bs-dismiss="modal" style="border-radius:8px;">Batal</button>
                <button type="button" class="btn btn-primary px-4 py-2 fw-bold d-inline-flex align-items-center gap-2" id="btnApplyCropS5" style="background:var(--navy);border-color:var(--navy);border-radius:8px;">
                    <i class="bi bi-check-lg fs-5"></i> Terapkan Hasil Penyesuaian
                </button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.1/cropper.min.js"></script>
<script>
var fakultasProdiMap = <?= json_encode($fakultas_prodi_map) ?>;
var selectedProdi = <?= json_encode($cur_prodi) ?>;

function handleTingkatChange() {
    var tingkat = document.getElementById('tingkatSelect').value;
    var fakultasGroup = document.getElementById('fakultasGroup');
    var prodiGroup = document.getElementById('prodiGroup');
    
    if (tingkat === 'universitas') {
        fakultasGroup.style.display = 'none';
        prodiGroup.style.display = 'none';
    } else if (tingkat === 'fakultas') {
        fakultasGroup.style.display = 'block';
        prodiGroup.style.display = 'none';
    } else if (tingkat === 'prodi') {
        fakultasGroup.style.display = 'block';
        prodiGroup.style.display = 'block';
        handleFakultasChange();
    }
}

function handleFakultasChange() {
    var fSelect = document.getElementById('fakultasSelect');
    var pSelect = document.getElementById('prodiSelect');
    var selectedF = fSelect.value;
    
    pSelect.innerHTML = '<option value="">-- Pilih Program Studi --</option>';
    
    if (selectedF && fakultasProdiMap[selectedF]) {
        fakultasProdiMap[selectedF].forEach(function(item) {
            var opt = document.createElement('option');
            opt.value = item;
            opt.textContent = item;
            if (item === selectedProdi) {
                opt.selected = true;
            }
            pSelect.appendChild(opt);
        });
    }
}

// Validasi ukuran file dokumen
document.querySelectorAll('.check-filesize').forEach(function(input) {
    input.addEventListener('change', function() {
        if (this.files && this.files[0]) {
            var maxMb = parseFloat(this.getAttribute('data-max-mb')) || 20;
            var sizeMb = this.files[0].size / (1024 * 1024);
            if (sizeMb > maxMb) {
                alert('Peringatan: Ukuran berkas (' + sizeMb.toFixed(2) + ' MB) melebihi batas maksimal ' + maxMb + ' MB. Silakan unggah dokumen yang lebih kecil.');
                this.value = '';
            }
        }
    });
});

// Cropper untuk foto kegiatan Siklus 5
document.addEventListener('DOMContentLoaded', function() {
    handleTingkatChange();
    handleFakultasChange();

    var fotoInput        = document.getElementById('fotoKegiatanInputS5');
    var btnOpenCropper   = document.getElementById('btnOpenCropperS5');
    var modalEl          = document.getElementById('modalCropS5');
    var cropperSourceImg = document.getElementById('cropperSourceImgS5');
    var btnApplyCrop     = document.getElementById('btnApplyCropS5');
    var containerGrid    = document.getElementById('croppedPhotosGridS5');
    var containerWrap    = document.getElementById('croppedPhotosContainerS5');

    if (!fotoInput || !modalEl) return;

    var bsModal = new bootstrap.Modal(modalEl);
    var cropper = null;
    var scaleX = 1;
    var scaleY = 1;
    var currentRatio = 16 / 9;

    var currentTargetExistingIdx  = null;
    var currentTargetExistingPath = null;

    // =========================================================================
    // 1. Switch Proteksi Dokumen (On/Off Kiri Kanan)
    // =========================================================================
    document.querySelectorAll('.ami-protect-toggle').forEach(function(toggle) {
        toggle.addEventListener('change', function() {
            var panel = document.getElementById(this.dataset.panel);
            var iconBox = document.getElementById(this.dataset.icon);
            var statusLabel = document.getElementById(this.dataset.label);
            var color = this.dataset.color || '#D97706';

            if (this.checked) {
                if (panel) panel.style.display = 'block';
                if (iconBox) {
                    iconBox.style.background = color + '25';
                    iconBox.style.color = color;
                }
                if (statusLabel) {
                    statusLabel.innerHTML = '<span class="fw-bold" style="color:' + color + ';">Dibatasi</span>';
                }
            } else {
                if (panel) panel.style.display = 'none';
                if (iconBox) {
                    iconBox.style.background = '#E2E8F0';
                    iconBox.style.color = '#64748B';
                }
                if (statusLabel) {
                    statusLabel.textContent = 'Bebas / Publik';
                }
            }
        });
    });

    // =========================================================================
    // 2. Drag & Drop Multi-Foto + Ctrl Multi-Select
    // =========================================================================
    var selectedFiles = [];
    var dropzone = document.getElementById('fotoDropzoneS5');
    var badgeCount = document.getElementById('photoCountBadgeS5');
    var previewContainer = document.getElementById('selectedPhotosContainerS5');
    var previewGrid = document.getElementById('selectedPhotosGridS5');
    var btnClearAll = document.getElementById('btnClearAllNewPhotosS5');

    function syncInputFilesAndRenderPreviews() {
        var dt = new DataTransfer();
        selectedFiles.forEach(function(f) {
            dt.items.add(f);
        });
        fotoInput.files = dt.files;

        if (badgeCount) {
            badgeCount.textContent = selectedFiles.length + ' foto baru dipilih';
            badgeCount.className = selectedFiles.length > 0 ? 'badge bg-primary text-white' : 'badge bg-light text-muted border';
        }

        if (selectedFiles.length === 0) {
            if (previewContainer) previewContainer.classList.add('d-none');
            if (previewGrid) previewGrid.innerHTML = '';
            return;
        }

        if (previewContainer) previewContainer.classList.remove('d-none');
        if (previewGrid) {
            previewGrid.innerHTML = '';
            selectedFiles.forEach(function(file, idx) {
                var col = document.createElement('div');
                col.className = 'col-6 col-sm-4 col-md-3';

                var card = document.createElement('div');
                card.className = 'card p-1 border position-relative h-100 shadow-sm';

                var imgBox = document.createElement('div');
                imgBox.style.cssText = 'height:110px;border-radius:4px;overflow:hidden;background:#0F172A;display:flex;align-items:center;justify-content:center;';

                var img = document.createElement('img');
                img.style.cssText = 'width:100%;height:100%;object-fit:cover;';
                img.src = URL.createObjectURL(file);
                imgBox.appendChild(img);

                var info = document.createElement('div');
                info.className = 'p-1';
                var sizeKb = Math.round(file.size / 1024);
                var sizeStr = sizeKb > 1024 ? (sizeKb / 1024).toFixed(1) + ' MB' : sizeKb + ' KB';
                info.innerHTML = '<div class="small fw-bold text-truncate" title="' + file.name + '" style="font-size:0.75rem;">' + file.name + '</div>' +
                                 '<div class="text-muted" style="font-size:0.68rem;"><i class="bi bi-file-earmark-image me-1"></i>' + sizeStr + '</div>';

                // Tombol Hapus per item
                var btnDel = document.createElement('button');
                btnDel.type = 'button';
                btnDel.className = 'btn btn-xs btn-danger position-absolute top-0 end-0 m-1 rounded-circle p-0 d-flex align-items-center justify-content-center';
                btnDel.style.cssText = 'width:22px;height:22px;line-height:1;font-size:13px;box-shadow:0 2px 5px rgba(0,0,0,0.3);';
                btnDel.innerHTML = '&times;';
                btnDel.title = 'Hapus foto ini dari daftar unggah';
                btnDel.onclick = function(e) {
                    e.stopPropagation();
                    selectedFiles.splice(idx, 1);
                    syncInputFilesAndRenderPreviews();
                };

                // Tombol Crop per item opsional
                var btnCrop = document.createElement('button');
                btnCrop.type = 'button';
                btnCrop.className = 'btn btn-xs btn-outline-primary w-100 mt-1 py-1 d-flex align-items-center justify-content-center gap-1';
                btnCrop.style.fontSize = '0.72rem';
                btnCrop.innerHTML = '<i class="bi bi-crop text-warning"></i> Crop / Edit';
                btnCrop.onclick = function(e) {
                    e.stopPropagation();
                    openCropForSelectedFile(file, idx);
                };

                card.appendChild(btnDel);
                card.appendChild(imgBox);
                card.appendChild(info);
                card.appendChild(btnCrop);
                col.appendChild(card);
                previewGrid.appendChild(col);
            });
        }
    }

    function addFilesToSelection(fileList) {
        if (!fileList || fileList.length === 0) return;
        for (var i = 0; i < fileList.length; i++) {
            var f = fileList[i];
            if (f.type.match(/^image\//)) {
                var exists = selectedFiles.some(function(item) {
                    return item.name === f.name && item.size === f.size;
                });
                if (!exists) {
                    selectedFiles.push(f);
                }
            }
        }
        syncInputFilesAndRenderPreviews();
    }

    // Klik Dropzone untuk membuka file chooser (bisa Ctrl-Klik / Ctrl-A)
    if (dropzone) {
        dropzone.addEventListener('click', function() {
            fotoInput.click();
        });

        ['dragenter', 'dragover'].forEach(function(evtName) {
            dropzone.addEventListener(evtName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.style.background = '#EFF6FF';
                dropzone.style.borderColor = '#2563EB';
                dropzone.style.transform = 'scale(1.01)';
            });
        });

        ['dragleave', 'drop'].forEach(function(evtName) {
            dropzone.addEventListener(evtName, function(e) {
                e.preventDefault();
                e.stopPropagation();
                dropzone.style.background = '#F8FAFC';
                dropzone.style.borderColor = '#93C5FD';
                dropzone.style.transform = 'scale(1)';
            });
        });

        dropzone.addEventListener('drop', function(e) {
            if (e.dataTransfer && e.dataTransfer.files) {
                addFilesToSelection(e.dataTransfer.files);
            }
        });
    }

    fotoInput.addEventListener('change', function() {
        addFilesToSelection(this.files);
    });

    if (btnClearAll) {
        btnClearAll.addEventListener('click', function() {
            selectedFiles = [];
            syncInputFilesAndRenderPreviews();
        });
    }

    // =========================================================================
    // 3. Cropper Integration (Opsional Manual)
    // =========================================================================
    var currentTargetNewIdx = null;

    function openCropForSelectedFile(file, idx) {
        currentTargetNewIdx = idx;
        currentTargetExistingIdx = null;
        currentTargetExistingPath = null;

        var reader = new FileReader();
        reader.onload = function(evt) {
            cropperSourceImg.src = evt.target.result;
            bsModal.show();
        };
        reader.readAsDataURL(file);
    }

    // Sesuaikan Foto yang Sudah Terupload / Tersimpan
    document.querySelectorAll('.btn-crop-existing-s5').forEach(function(btn) {
        btn.addEventListener('click', function() {
            var url = this.getAttribute('data-photo-url');
            var path = this.getAttribute('data-photo-path');
            var idx = this.getAttribute('data-idx');
            currentTargetExistingIdx = idx;
            currentTargetExistingPath = path;
            currentTargetNewIdx = null;

            cropperSourceImg.src = url;
            bsModal.show();
        });
    });

    // Visual feedback saat checkbox hapus dicentang manual
    document.querySelectorAll('.check-del-s5').forEach(function(chk) {
        chk.addEventListener('change', function() {
            var card = document.getElementById('cardExistingS5_' + this.dataset.idx);
            if (card) {
                if (this.checked) {
                    card.style.opacity = '0.45';
                    card.style.borderColor = '#EF4444';
                } else {
                    card.style.opacity = '1';
                    card.style.borderColor = '#CBD5E1';
                }
            }
        });
    });

    modalEl.addEventListener('shown.bs.modal', function() { initCropper(); });
    modalEl.addEventListener('hidden.bs.modal', function() { if (cropper) { cropper.destroy(); cropper = null; } });

    document.getElementById('btnRotateLeftS5')?.addEventListener('click', function() { if (cropper) cropper.rotate(-90); });
    document.getElementById('btnRotateRightS5')?.addEventListener('click', function() { if (cropper) cropper.rotate(90); });
    document.getElementById('btnFlipHS5')?.addEventListener('click', function() { if (cropper) { scaleX = -scaleX; cropper.scaleX(scaleX); } });
    document.getElementById('btnFlipVS5')?.addEventListener('click', function() { if (cropper) { scaleY = -scaleY; cropper.scaleY(scaleY); } });
    document.getElementById('btnZoomInS5')?.addEventListener('click', function() { if (cropper) cropper.zoom(0.1); });
    document.getElementById('btnZoomOutS5')?.addEventListener('click', function() { if (cropper) cropper.zoom(-0.1); });
    document.getElementById('btnResetCropS5')?.addEventListener('click', function() { if (cropper) { cropper.reset(); scaleX = 1; scaleY = 1; } });

    document.querySelectorAll('.ratio-btn-s5').forEach(function(btn) {
        btn.addEventListener('click', function() {
            document.querySelectorAll('.ratio-btn-s5').forEach(function(b) { b.classList.remove('active'); });
            this.classList.add('active');
            var val = parseFloat(this.getAttribute('data-ratio'));
            currentRatio = isNaN(val) ? NaN : val;
            if (cropper) cropper.setAspectRatio(currentRatio);
        });
    });

    btnApplyCrop?.addEventListener('click', function() {
        if (!cropper) return;
        var canvas = cropper.getCroppedCanvas({ maxWidth: 1920, maxHeight: 1200, imageSmoothingQuality: 'high' });
        if (canvas) {
            var dataUrl = canvas.toDataURL('image/webp', 0.88);
            var col = document.createElement('div');
            col.className = 'col-6 col-sm-4 col-md-3';

            var linkedIdx = currentTargetExistingIdx;
            var existingNote = '';

            if (linkedIdx !== null) {
                existingNote = '<div class="px-1 py-1 bg-warning-subtle text-dark border-top small text-truncate" style="font-size:0.68rem;font-weight:600;"><i class="bi bi-arrow-repeat me-1 text-warning"></i>Menggantikan foto tersimpan</div>';
                
                // Tandai foto lama tersimpan untuk digantikan
                var chkDel = document.getElementById('delPhotoRtm_' + linkedIdx);
                var cardOld = document.getElementById('cardExistingS5_' + linkedIdx);
                if (chkDel) chkDel.checked = true;
                if (cardOld) {
                    cardOld.style.opacity = '0.45';
                    cardOld.style.borderColor = '#EF4444';
                }
            } else if (currentTargetNewIdx !== null) {
                // Jika mengedit salah satu foto baru yang dipilih, hapus file aslinya dari selectedFiles
                selectedFiles.splice(currentTargetNewIdx, 1);
                syncInputFilesAndRenderPreviews();
            }

            col.innerHTML = '<div class="card p-1 border position-relative shadow-sm">' +
                '<img src="' + dataUrl + '" style="height:100px;object-fit:cover;border-radius:4px;">' +
                existingNote +
                '<input type="hidden" name="cropped_photos[]" value="' + dataUrl + '">' +
                '<button type="button" class="btn btn-xs btn-danger position-absolute top-0 end-0 m-1 p-1" style="line-height:1;border-radius:50%;" title="Batalkan foto hasil crop ini">&times;</button>' +
                '</div>';

            var btnDel = col.querySelector('button');
            btnDel.addEventListener('click', function() {
                if (linkedIdx !== null) {
                    var chkDel = document.getElementById('delPhotoRtm_' + linkedIdx);
                    var cardOld = document.getElementById('cardExistingS5_' + linkedIdx);
                    if (chkDel) chkDel.checked = false;
                    if (cardOld) {
                        cardOld.style.opacity = '1';
                        cardOld.style.borderColor = '#CBD5E1';
                    }
                }
                col.remove();
                if (containerGrid.children.length === 0) {
                    containerWrap.classList.add('d-none');
                }
            });

            containerGrid.appendChild(col);
            containerWrap.classList.remove('d-none');

            bsModal.hide();
        }
    });
});
</script>

<?php require_once __DIR__ . '/includes/admin-footer.php'; ?>
